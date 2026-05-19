<?php

namespace App\Console\Commands;

use App\Models\VulnScan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneStaleFilePaths extends Command
{
    protected $signature   = 'scans:prune-stale-paths';
    protected $description = 'Null out file_path on vuln_scans rows whose files no longer exist on disk';

    public function handle(): int
    {
        $scans = VulnScan::whereNotNull('file_path')->get(['id', 'file_path', 'filename']);

        if ($scans->isEmpty()) {
            $this->info('No scans with file_path found.');
            return 0;
        }

        $pruned = 0;

        foreach ($scans as $scan) {
            if (!Storage::disk('local')->exists($scan->file_path)) {
                $scan->update(['file_path' => null]);
                $this->line("  Pruned scan #{$scan->id} ({$scan->filename})");
                $pruned++;
            }
        }

        $this->info("Done. {$pruned} of {$scans->count()} scan(s) pruned.");
        return 0;
    }
}
