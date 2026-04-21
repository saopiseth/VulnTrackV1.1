<?php

namespace App\Console\Commands;

use App\Models\VulnAssessment;
use App\Models\VulnTracked;
use App\Services\VulnTrackingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Wipes and replays tracking for every assessment (or a single one).
 *
 * Always starts from a clean slate. Duplicate scan filenames (same file
 * uploaded more than once to the same assessment) are skipped — only the
 * earliest upload is replayed, matching the intent of the upload guard added
 * in the controller.
 *
 * Usage:
 *   php artisan vuln:rebuild-tracking           # all assessments
 *   php artisan vuln:rebuild-tracking --id=5    # single assessment
 */
class RebuildVulnTracking extends Command
{
    protected $signature   = 'vuln:rebuild-tracking {--id= : Rebuild only this assessment ID}';
    protected $description = 'Wipe and replay vuln_tracked / vuln_tracked_history from deduplicated scan data';

    public function handle(): int
    {
        $service = new VulnTrackingService();

        $query = VulnAssessment::with(['scans' => fn($q) => $q->orderBy('id')]);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $assessments = $query->get();

        if ($assessments->isEmpty()) {
            $this->error('No assessments found.');
            return self::FAILURE;
        }

        foreach ($assessments as $assessment) {
            $this->line('');
            $this->info("── Assessment {$assessment->id}: {$assessment->name}");

            // ── 1. Wipe existing tracking data ────────────────────────────────
            // Cascades to vuln_tracked_history via FK.
            $deleted = VulnTracked::where('assessment_id', $assessment->id)->delete();
            $this->line("   Cleared {$deleted} tracked rows");

            // ── 2. Deduplicate: first occurrence per filename wins ─────────────
            $seenFilenames = [];
            $uniqueScans   = [];

            foreach ($assessment->scans as $scan) {
                if (isset($seenFilenames[$scan->filename])) {
                    $this->warn(sprintf(
                        '   SKIP  duplicate %-50s scan #%d (kept #%d)',
                        "\"{$scan->filename}\"",
                        $scan->id,
                        $seenFilenames[$scan->filename]
                    ));
                    continue;
                }
                $seenFilenames[$scan->filename] = $scan->id;
                $uniqueScans[] = $scan;
            }

            if (empty($uniqueScans)) {
                $this->line('   No scans to replay — skipping');
                continue;
            }

            // ── 3. Replay in chronological upload order ────────────────────────
            foreach ($uniqueScans as $scan) {
                $stats = DB::transaction(fn() => $service->track($assessment, $scan));

                $label = $scan->is_baseline ? '[baseline]' : '[scan    ]';
                $this->line(sprintf(
                    '   %s #%-3d %-52s  created=%d  still=%d  reopened=%d  resolved=%d',
                    $label,
                    $scan->id,
                    '"' . substr($scan->filename, 0, 50) . '"',
                    $stats['created'],
                    $stats['still_open'],
                    $stats['reopened'],
                    $stats['resolved'],
                ));
            }

            // ── 4. Final status summary ────────────────────────────────────────
            $counts = VulnTracked::where('assessment_id', $assessment->id)
                ->selectRaw('tracking_status, COUNT(*) as cnt')
                ->groupBy('tracking_status')
                ->pluck('cnt', 'tracking_status');

            $this->line('   Status breakdown: ' .
                collect(['Open', 'New', 'Unresolved', 'Reopened', 'Resolved'])
                    ->map(fn($s) => "{$s}=" . ($counts[$s] ?? 0))
                    ->join('  ')
            );
        }

        $this->line('');
        $this->info('Rebuild complete.');
        return self::SUCCESS;
    }
}
