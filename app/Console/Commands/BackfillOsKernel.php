<?php

namespace App\Console\Commands;

use App\Models\VulnFinding;
use App\Models\VulnHostOs;
use App\Services\OsDetector;
use Illuminate\Console\Command;

class BackfillOsKernel extends Command
{
    protected $signature   = 'vuln:backfill-kernel {--force : Re-run even if os_kernel is already set}';
    protected $description = 'Backfill os_kernel for existing vuln_host_os and vuln_findings from stored plugin output';

    // Plugins that carry OS/kernel/build text, in preference order
    const KERNEL_PLUGINS = ['11936', '10785', '25221', '45590', '45003'];

    public function handle(): int
    {
        $force = $this->option('force');

        // ── 1. Backfill vuln_host_os ──────────────────────────────────────
        $hosts = VulnHostOs::when(!$force, fn($q) => $q->whereNull('os_kernel'))->get();
        $this->info("Backfilling {$hosts->count()} vuln_host_os record(s)…");

        $hostUpdated = 0;
        foreach ($hosts as $host) {
            $kernel = $this->resolveKernel($host->assessment_id, $host->ip_address, $host->os_family);
            if ($kernel) {
                $host->update(['os_kernel' => $kernel]);
                $hostUpdated++;
                $this->line("  + {$host->ip_address} [{$host->os_family}] → {$kernel}");
            } else {
                $this->line("  - {$host->ip_address} [{$host->os_family}] no data found");
            }
        }
        $this->info("vuln_host_os: {$hostUpdated}/{$hosts->count()} updated.");

        // ── 2. Backfill vuln_findings ─────────────────────────────────────
        $combos = VulnFinding::when(!$force, fn($q) => $q->whereNull('os_kernel'))
            ->select('assessment_id', 'ip_address', 'os_family')
            ->distinct()
            ->get();

        $this->info("Backfilling findings for {$combos->count()} distinct host(s)…");

        $findingsUpdated = 0;
        foreach ($combos as $combo) {
            $kernel = $this->resolveKernel($combo->assessment_id, $combo->ip_address, $combo->os_family);
            if ($kernel) {
                $cnt = VulnFinding::where('assessment_id', $combo->assessment_id)
                    ->where('ip_address', $combo->ip_address)
                    ->whereNull('os_kernel')
                    ->update(['os_kernel' => $kernel]);
                $findingsUpdated += $cnt;
            }
        }
        $this->info("vuln_findings: {$findingsUpdated} row(s) updated.");

        $this->info('Done.');
        return self::SUCCESS;
    }

    /**
     * Find the best kernel/build for a host by scanning stored plugin outputs.
     * Uses FIELD() to respect preference order in a single query.
     */
    private function resolveKernel(int $assessmentId, string $ip, ?string $family): ?string
    {
        // Fetch all relevant plugin outputs, then sort by preference in PHP
        // (avoids FIELD() which is MySQL-only and not available in SQLite)
        $rows = VulnFinding::where('assessment_id', $assessmentId)
            ->where('ip_address', $ip)
            ->whereIn('plugin_id', self::KERNEL_PLUGINS)
            ->whereNotNull('plugin_output')
            ->where('plugin_output', '!=', '')
            ->select('plugin_id', 'plugin_output')
            ->get()
            ->unique('plugin_id')   // one row per plugin
            ->keyBy('plugin_id');   // indexed by plugin_id for quick lookup

        foreach (self::KERNEL_PLUGINS as $pid) {
            $output = $rows->get($pid)?->plugin_output;
            if (!$output) continue;

            $kernel = ($family === 'Windows')
                ? OsDetector::extractWindowsBuildPublic($output)
                : OsDetector::extractLinuxKernelPublic($output);

            if ($kernel) return $kernel;
        }

        return null;
    }
}
