<?php

namespace App\Jobs;

use App\Models\HardeningFinding;
use App\Models\HardeningVerification;
use App\Services\HardeningParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessHardeningVerification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(
        public readonly int    $verificationId,
        public readonly string $filePath,
        public readonly string $fileExtension,
    ) {}

    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '0');
        set_time_limit(0);
        ignore_user_abort(true);

        $verificationId = $this->verificationId;

        register_shutdown_function(function () use ($verificationId) {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $msg = "[HardeningVerification FATAL id={$verificationId}] {$err['message']} in {$err['file']}:{$err['line']}";
                error_log($msg);
                file_put_contents(storage_path('logs/scan_fatal.log'), date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
                try {
                    DB::table('hardening_verifications')
                        ->where('id', $verificationId)
                        ->where('upload_status', 'processing')
                        ->update(['upload_status' => 'failed', 'upload_error' => mb_substr($msg, 0, 500), 'updated_at' => now()]);
                } catch (\Throwable) {}
            }
        });

        $verification = HardeningVerification::findOrFail($this->verificationId);
        $verification->update(['upload_status' => 'processing']);

        try {
            try { DB::statement('SET SESSION max_allowed_packet = 67108864'); } catch (\Throwable) {}

            $fullPath   = Storage::disk('local')->path($this->filePath);
            $parser     = new HardeningParserService();
            $now        = now()->toDateTimeString();

            // Load all initial assessment findings keyed by finding_key
            $initialFindings = HardeningFinding::where('hardening_assessment_id', $verification->hardening_assessment_id)
                ->get()
                ->keyBy('finding_key');

            // Collect all parsed rows from the verification scan
            $verificationRows = [];
            $flushRows = function (array $rows) use (&$verificationRows): void {
                foreach ($rows as $row) {
                    $verificationRows[$row['finding_key']] = $row;
                }
            };

            if (in_array($this->fileExtension, ['xml', 'nessus'])) {
                $parser->parseXml($fullPath, $flushRows);
            } else {
                $parser->parseCsv($fullPath, $flushRows);
            }

            // Compare verification scan against initial findings
            $results  = [];
            $resolved = 0;
            $stillOpen = 0;
            $newFindings = 0;
            $notFound = 0;

            // Process each initial finding
            foreach ($initialFindings as $key => $finding) {
                if (isset($verificationRows[$key])) {
                    $vRow   = $verificationRows[$key];
                    $status = in_array($vRow['compliance_status'], ['Compliant', 'Not Applicable'])
                        ? 'Resolved'
                        : 'Still Open';

                    if ($status === 'Resolved') $resolved++;
                    else $stillOpen++;

                    $results[] = [
                        'hardening_verification_id' => $verification->id,
                        'hardening_finding_id'      => $finding->id,
                        'plugin_id'                 => $vRow['plugin_id'],
                        'plugin_name'               => $vRow['plugin_name'],
                        'plugin_family'             => $vRow['plugin_family'],
                        'description'               => $vRow['description'],
                        'solution'                  => $vRow['solution'],
                        'plugin_output'             => $vRow['plugin_output'],
                        'severity'                  => $vRow['severity'],
                        'cvss_score'                => $vRow['cvss_score'],
                        'compliance_result'         => $vRow['compliance_result'],
                        'compliance_status'         => $vRow['compliance_status'],
                        'verification_status'       => $status,
                        'finding_key'               => $key,
                        'created_at'                => $now,
                        'updated_at'                => $now,
                    ];
                } else {
                    // Finding from initial assessment not found in verification scan at all
                    $notFound++;
                    $results[] = [
                        'hardening_verification_id' => $verification->id,
                        'hardening_finding_id'      => $finding->id,
                        'plugin_id'                 => $finding->plugin_id,
                        'plugin_name'               => $finding->plugin_name,
                        'plugin_family'             => $finding->plugin_family,
                        'description'               => $finding->description,
                        'solution'                  => $finding->solution,
                        'plugin_output'             => null,
                        'severity'                  => $finding->severity,
                        'cvss_score'                => $finding->cvss_score,
                        'compliance_result'         => null,
                        'compliance_status'         => null,
                        'verification_status'       => 'Not Found in Verification',
                        'finding_key'               => $key,
                        'created_at'                => $now,
                        'updated_at'                => $now,
                    ];
                }
            }

            // New findings in verification scan not present in initial assessment
            foreach ($verificationRows as $key => $vRow) {
                if (!$initialFindings->has($key)) {
                    $newFindings++;
                    $results[] = [
                        'hardening_verification_id' => $verification->id,
                        'hardening_finding_id'      => null,
                        'plugin_id'                 => $vRow['plugin_id'],
                        'plugin_name'               => $vRow['plugin_name'],
                        'plugin_family'             => $vRow['plugin_family'],
                        'description'               => $vRow['description'],
                        'solution'                  => $vRow['solution'],
                        'plugin_output'             => $vRow['plugin_output'],
                        'severity'                  => $vRow['severity'],
                        'cvss_score'                => $vRow['cvss_score'],
                        'compliance_result'         => $vRow['compliance_result'],
                        'compliance_status'         => $vRow['compliance_status'],
                        'verification_status'       => 'New Finding',
                        'finding_key'               => $key,
                        'created_at'                => $now,
                        'updated_at'                => $now,
                    ];
                }
            }

            DB::transaction(function () use ($results) {
                foreach (array_chunk($results, 25) as $chunk) {
                    DB::table('hardening_verification_results')->insert($chunk);
                }
            });

            $verification->update([
                'upload_status'   => 'completed',
                'resolved_count'  => $resolved,
                'still_open_count'=> $stillOpen,
                'new_finding_count'=> $newFindings,
                'not_found_count' => $notFound,
            ]);

        } catch (\Throwable $e) {
            $msg = '[HardeningVerification EXCEPTION id=' . $this->verificationId . '] '
                . get_class($e) . ': ' . $e->getMessage()
                . ' in ' . $e->getFile() . ':' . $e->getLine();
            error_log($msg);
            file_put_contents(storage_path('logs/scan_fatal.log'), date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
            $verification->update([
                'upload_status' => 'failed',
                'upload_error'  => mb_substr($e->getMessage(), 0, 500),
            ]);
            throw $e;
        }
    }
}
