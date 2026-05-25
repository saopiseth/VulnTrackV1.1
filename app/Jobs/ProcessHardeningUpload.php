<?php

namespace App\Jobs;

use App\Models\HardeningAssessment;
use App\Services\HardeningParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessHardeningUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(
        public readonly int    $assessmentId,
        public readonly string $filePath,
        public readonly string $fileExtension,
    ) {}

    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '0');
        set_time_limit(0);
        ignore_user_abort(true);

        $assessmentId = $this->assessmentId;

        register_shutdown_function(function () use ($assessmentId) {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $msg = "[HardeningUpload FATAL id={$assessmentId}] {$err['message']} in {$err['file']}:{$err['line']}";
                error_log($msg);
                file_put_contents(storage_path('logs/scan_fatal.log'), date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
                try {
                    DB::table('hardening_assessments')
                        ->where('id', $assessmentId)
                        ->where('upload_status', 'processing')
                        ->update(['upload_status' => 'failed', 'upload_error' => mb_substr($msg, 0, 500), 'updated_at' => now()]);
                } catch (\Throwable) {}
            }
        });

        $assessment = HardeningAssessment::findOrFail($this->assessmentId);
        $assessment->update(['upload_status' => 'processing']);

        try {
            try { DB::statement('SET SESSION max_allowed_packet = 67108864'); } catch (\Throwable) {}

            $fullPath = Storage::disk('local')->path($this->filePath);
            $parser   = new HardeningParserService();
            $counts   = ['total' => 0, 'compliant' => 0, 'non_compliant' => 0, 'partially' => 0, 'na' => 0];
            $now      = now()->toDateTimeString();

            $flushRows = function (array $rows) use ($assessment, $now, &$counts): void {
                $insert = [];
                foreach ($rows as $row) {
                    $insert[] = array_merge($row, [
                        'hardening_assessment_id' => $assessment->id,
                        'created_at'              => $now,
                        'updated_at'              => $now,
                    ]);
                    $counts['total']++;
                    match ($row['compliance_status']) {
                        'Compliant'           => $counts['compliant']++,
                        'Non-Compliant'       => $counts['non_compliant']++,
                        'Partially Compliant' => $counts['partially']++,
                        default               => $counts['na']++,
                    };
                }

                foreach (array_chunk($insert, 25) as $chunk) {
                    DB::table('hardening_findings')->insertOrIgnore($chunk);
                }
            };

            DB::transaction(function () use ($parser, $fullPath, $flushRows) {
                if (in_array($this->fileExtension, ['xml', 'nessus'])) {
                    $parser->parseXml($fullPath, $flushRows);
                } else {
                    $parser->parseCsv($fullPath, $flushRows);
                }
            });

            $assessment->update([
                'upload_status'           => 'completed',
                'total_findings'          => $counts['total'],
                'compliant_count'         => $counts['compliant'],
                'non_compliant_count'     => $counts['non_compliant'],
                'partially_compliant_count' => $counts['partially'],
                'not_applicable_count'    => $counts['na'],
            ]);

        } catch (\Throwable $e) {
            $msg = '[HardeningUpload EXCEPTION id=' . $this->assessmentId . '] '
                . get_class($e) . ': ' . $e->getMessage()
                . ' in ' . $e->getFile() . ':' . $e->getLine();
            error_log($msg);
            file_put_contents(storage_path('logs/scan_fatal.log'), date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
            $assessment->update([
                'upload_status' => 'failed',
                'upload_error'  => mb_substr($e->getMessage(), 0, 500),
            ]);
            throw $e;
        }
    }
}
