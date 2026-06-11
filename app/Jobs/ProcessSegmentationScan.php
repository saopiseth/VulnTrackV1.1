<?php

namespace App\Jobs;

use App\Models\SegmentationDetail;
use App\Models\SegmentationResult;
use App\Models\SegmentationTest;
use App\Services\NmapParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessSegmentationScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct(
        public readonly int    $testId,
        public readonly string $filePath,
        public readonly string $fileExtension,
    ) {}

    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $testId = $this->testId;

        register_shutdown_function(function () use ($testId) {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                DB::table('segmentation_tests')
                    ->where('id', $testId)
                    ->where('upload_status', 'processing')
                    ->update([
                        'upload_status' => 'failed',
                        'upload_error'  => mb_substr($err['message'], 0, 500),
                        'updated_at'    => now()->toDateTimeString(),
                    ]);
            }
        });

        $test = SegmentationTest::findOrFail($this->testId);
        $test->update(['upload_status' => 'processing']);

        try {
            $parser = new NmapParserService();
            $hosts  = $parser->parse($this->filePath, $this->fileExtension);

            // Determine scanner subnet from stored scanner_ip
            $scannerSubnet = $test->scanner_ip
                ? $parser->getSubnet($test->scanner_ip)
                : null;

            if ($scannerSubnet) {
                $test->update(['scanner_subnet' => $scannerSubnet]);
            }

            // Group discovered hosts by their /24 subnet
            $subnetHosts = [];
            foreach ($hosts as $ip => $ports) {
                $subnet = $parser->getSubnet($ip);
                $subnetHosts[$subnet][] = ['ip' => $ip, 'ports' => $ports];
            }

            DB::transaction(function () use ($test, $subnetHosts) {
                $now = now()->toDateTimeString();

                foreach ($subnetHosts as $targetSubnet => $hostsInSubnet) {
                    $result = SegmentationResult::create([
                        'segmentation_test_id' => $test->id,
                        'target_subnet'        => $targetSubnet,
                        'status'               => 'accessible',
                        'host_count'           => count($hostsInSubnet),
                    ]);

                    $detailRows = [];

                    foreach ($hostsInSubnet as $hostData) {
                        if (empty($hostData['ports'])) {
                            // Host is reachable but no open ports detected
                            $detailRows[] = [
                                'segmentation_test_id'   => $test->id,
                                'segmentation_result_id' => $result->id,
                                'host_ip'                => $hostData['ip'],
                                'target_subnet'          => $targetSubnet,
                                'port'                   => null,
                                'protocol'               => null,
                                'service'                => null,
                                'created_at'             => $now,
                                'updated_at'             => $now,
                            ];
                        } else {
                            foreach ($hostData['ports'] as $portData) {
                                $detailRows[] = [
                                    'segmentation_test_id'   => $test->id,
                                    'segmentation_result_id' => $result->id,
                                    'host_ip'                => $hostData['ip'],
                                    'target_subnet'          => $targetSubnet,
                                    'port'                   => $portData['port'],
                                    'protocol'               => $portData['protocol'],
                                    'service'                => $portData['service'],
                                    'created_at'             => $now,
                                    'updated_at'             => $now,
                                ];
                            }
                        }
                    }

                    // Batch insert for performance on large scans
                    foreach (array_chunk($detailRows, 500) as $chunk) {
                        SegmentationDetail::insert($chunk);
                    }
                }
            });

            $test->update(['upload_status' => 'completed']);

        } catch (\Throwable $e) {
            $test->update([
                'upload_status' => 'failed',
                'upload_error'  => mb_substr($e->getMessage(), 0, 500),
            ]);
            throw $e;
        }
    }
}
