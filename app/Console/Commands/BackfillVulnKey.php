<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillVulnKey extends Command
{
    protected $signature = 'vuln:backfill-keys
                            {--chunk=500 : Records per batch}
                            {--dry-run   : Report counts without writing}';

    protected $description = 'Backfill NULL vuln_key values in vuln_findings and vuln_tracked using sha1(plugin_id|ip_address|port|protocol)';

    public function handle(): int
    {
        $chunk  = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('[dry-run] No changes will be written.');
        }

        $this->backfillTable('vuln_findings', $chunk, $dryRun);
        $this->backfillTable('vuln_tracked',  $chunk, $dryRun);

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function backfillTable(string $table, int $chunk, bool $dryRun): void
    {
        $total = DB::table($table)->whereNull('vuln_key')->count();

        if ($total === 0) {
            $this->line("  {$table}: nothing to backfill.");
            return;
        }

        $this->info("  {$table}: {$total} record(s) need vuln_key.");

        if ($dryRun) {
            return;
        }

        $bar       = $this->output->createProgressBar($total);
        $processed = 0;

        DB::table($table)
            ->whereNull('vuln_key')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($table, $bar, &$processed) {
                $cases    = [];
                $bindings = [];
                $ids      = [];

                foreach ($rows as $row) {
                    $key = sha1(implode('|', [
                        trim($row->plugin_id         ?? ''),
                        strtolower(trim($row->ip_address ?? '')),
                        (string) ($row->port          ?? ''),
                        strtolower(trim($row->protocol ?? '')),
                    ]));

                    $cases[]    = 'WHEN ? THEN ?';
                    $bindings[] = $row->id;
                    $bindings[] = $key;
                    $ids[]      = $row->id;
                }

                if (empty($ids)) {
                    return;
                }

                // Single UPDATE with CASE WHEN — one round-trip per chunk
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $casesSql     = implode(' ', $cases);
                DB::statement(
                    "UPDATE `{$table}` SET vuln_key = CASE id {$casesSql} END WHERE id IN ({$placeholders})",
                    array_merge($bindings, $ids)
                );

                $processed += count($ids);
                $bar->advance(count($ids));
            });

        $bar->finish();
        $this->newLine();
        $this->info("  {$table}: {$processed} record(s) updated.");
    }
}
