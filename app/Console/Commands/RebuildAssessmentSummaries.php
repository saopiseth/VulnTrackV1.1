<?php

namespace App\Console\Commands;

use App\Models\VulnAssessment;
use App\Services\AssessmentSummaryService;
use Illuminate\Console\Command;

class RebuildAssessmentSummaries extends Command
{
    protected $signature = 'vuln:rebuild-summaries
                            {--id=   : Rebuild only the assessment with this ID}
                            {--force : Re-build even when a cached summary already exists}';

    protected $description = 'Rebuild cached assessment_summaries rows from vuln_tracked data';

    public function handle(): int
    {
        $service = new AssessmentSummaryService();

        $query = VulnAssessment::query();

        if ($id = $this->option('id')) {
            $query->where('id', (int) $id);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->warn('No assessments found.');
            return self::SUCCESS;
        }

        $this->info("Rebuilding summaries for {$total} assessment(s)...");
        $bar = $this->output->createProgressBar($total);

        $query->chunk(50, function ($batch) use ($service, $bar) {
            foreach ($batch as $assessment) {
                if ($this->option('force')) {
                    $service->invalidate($assessment->id);
                }
                $service->rebuild($assessment);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
