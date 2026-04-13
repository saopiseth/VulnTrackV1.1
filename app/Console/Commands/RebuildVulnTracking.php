<?php

namespace App\Console\Commands;

use App\Models\VulnAssessment;
use App\Models\VulnFinding;
use App\Models\VulnRemediation;
use App\Models\VulnScan;
use App\Models\VulnTracked;
use App\Models\VulnTrackedHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildVulnTracking extends Command
{
    protected $signature   = 'vuln:rebuild-tracking
                                {--assessment= : Only rebuild a specific assessment ID}
                                {--fresh       : Wipe vuln_tracked first and rebuild from scratch}';

    protected $description = 'Rebuild vuln_tracked and vuln_tracked_history from existing scan data.
                              Run this once after the 2026_04_13_000003 migration to backfill
                              assessments that were uploaded before the tracking engine existed.';

    public function handle(): int
    {
        $assessmentId = $this->option('assessment');
        $fresh        = $this->option('fresh');

        $query = VulnAssessment::with(['scans' => fn($q) => $q->orderBy('id')]);
        if ($assessmentId) {
            $query->where('id', $assessmentId);
        }
        $assessments = $query->get();

        if ($assessments->isEmpty()) {
            $this->warn('No assessments found.');
            return self::SUCCESS;
        }

        if ($fresh) {
            $this->warn('--fresh: wiping vuln_tracked_history and vuln_tracked …');
            $ids = $assessments->pluck('id');
            VulnTrackedHistory::whereIn(
                'tracked_id',
                VulnTracked::whereIn('assessment_id', $ids)->select('id')
            )->delete();
            VulnTracked::whereIn('assessment_id', $ids)->delete();
            $this->info('Tables cleared.');
        }

        foreach ($assessments as $assessment) {
            $scans = $assessment->scans->sortBy('id');
            if ($scans->isEmpty()) {
                $this->line("  Assessment #{$assessment->id} \"{$assessment->name}\": no scans, skipping.");
                continue;
            }

            $this->info("Assessment #{$assessment->id} \"{$assessment->name}\" - {$scans->count()} scan(s)");

            // Load remediations once per assessment
            $remediations = VulnRemediation::where('assessment_id', $assessment->id)
                ->get()
                ->keyBy(fn($r) => $r->plugin_id . '|' . $r->ip_address);

            // Tracked map rebuilt scan by scan
            $trackedMap = VulnTracked::where('assessment_id', $assessment->id)
                ->get()
                ->keyBy(fn($t) => $t->ip_address . '|' . $t->plugin_id);

            foreach ($scans as $scan) {
                $this->line("    ↳ Scan #{$scan->id} v{$scan->scan_version} {$scan->filename}");

                $scanTime = $scan->created_at ?? now();

                // Load actionable findings for this scan
                $findings = VulnFinding::where('scan_id', $scan->id)
                    ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                    ->get();

                $currentMap = $findings->keyBy(fn($f) => $f->ip_address . '|' . $f->plugin_id);

                DB::transaction(function () use (
                    $assessment, $scan, $findings, $currentMap,
                    &$trackedMap, $remediations, $scanTime
                ) {
                    foreach ($findings as $finding) {
                        $fp = $finding->ip_address . '|' . $finding->plugin_id;

                        $currentData = [
                            'hostname'           => $finding->hostname,
                            'vuln_name'          => $finding->vuln_name,
                            'description'        => $finding->description,
                            'remediation_text'   => $finding->remediation_text,
                            'severity'           => $finding->severity,
                            'port'               => $finding->port,
                            'protocol'           => $finding->protocol,
                            'vuln_category'      => $finding->vuln_category,
                            'affected_component' => $finding->affected_component,
                            'os_detected'        => $finding->os_detected,
                            'os_name'            => $finding->os_name,
                            'os_family'          => $finding->os_family,
                            'last_seen_at'       => $scanTime,
                            'last_scan_id'       => $scan->id,
                            'resolved_at'        => null,
                        ];

                        if (isset($trackedMap[$fp])) {
                            $tracked      = $trackedMap[$fp];
                            $prevStatus   = $tracked->tracking_status;
                            $prevSeverity = $tracked->severity;
                            $sevChanged   = $prevSeverity !== $finding->severity;
                            $newStatus    = 'Pending';

                            $tracked->update(array_merge($currentData, [
                                'tracking_status' => $newStatus,
                            ]));

                            if ($prevStatus === 'Resolved') {
                                VulnTrackedHistory::create([
                                    'tracked_id'  => $tracked->id, 'scan_id' => $scan->id,
                                    'event_type'  => 'reappeared',
                                    'prev_status' => 'Resolved', 'new_status' => 'Pending',
                                    'changed_at'  => $scanTime,
                                ]);
                            } elseif ($prevStatus === 'New') {
                                VulnTrackedHistory::create([
                                    'tracked_id'  => $tracked->id, 'scan_id' => $scan->id,
                                    'event_type'  => 'status_changed',
                                    'prev_status' => 'New', 'new_status' => 'Pending',
                                    'changed_at'  => $scanTime,
                                ]);
                            } else {
                                VulnTrackedHistory::create([
                                    'tracked_id'  => $tracked->id, 'scan_id' => $scan->id,
                                    'event_type'  => 'still_present',
                                    'prev_status' => 'Pending', 'new_status' => 'Pending',
                                    'changed_at'  => $scanTime,
                                ]);
                            }

                            if ($sevChanged) {
                                VulnTrackedHistory::create([
                                    'tracked_id'   => $tracked->id, 'scan_id' => $scan->id,
                                    'event_type'   => 'severity_changed',
                                    'prev_severity'=> $prevSeverity,
                                    'new_severity' => $finding->severity,
                                    'changed_at'   => $scanTime,
                                ]);
                            }
                        } else {
                            $tracked = VulnTracked::create(array_merge($currentData, [
                                'assessment_id'  => $assessment->id,
                                'ip_address'     => $finding->ip_address,
                                'plugin_id'      => $finding->plugin_id,
                                'cve'            => $finding->cve,
                                'tracking_status'=> 'New',
                                'first_seen_at'  => $scanTime,
                                'first_scan_id'  => $scan->id,
                            ]));

                            VulnTrackedHistory::create([
                                'tracked_id'  => $tracked->id, 'scan_id' => $scan->id,
                                'event_type'  => 'created',
                                'new_status'  => 'New', 'new_severity' => $finding->severity,
                                'changed_at'  => $scanTime,
                            ]);

                            $trackedMap[$fp] = $tracked;
                        }
                    }

                    // Mark absent as resolved — only for IPs covered by this scan
                    $scannedIps = $findings->pluck('ip_address')->unique()->flip();
                    foreach ($trackedMap as $fp => $tracked) {
                        if (!$scannedIps->has($tracked->ip_address)) {
                            continue; // host not in this scan — don't touch
                        }
                        if ($currentMap->has($fp) || $tracked->tracking_status === 'Resolved') {
                            continue;
                        }
                        $rem = $remediations->get($tracked->plugin_id . '|' . $tracked->ip_address);
                        if ($rem && $rem->status === 'Accepted Risk') {
                            continue;
                        }
                        $prevStatus = $tracked->tracking_status;
                        $tracked->update([
                            'tracking_status' => 'Resolved',
                            'resolved_at'     => $scanTime,
                            'last_scan_id'    => $scan->id,
                        ]);
                        VulnTrackedHistory::create([
                            'tracked_id'  => $tracked->id, 'scan_id' => $scan->id,
                            'event_type'  => 'resolved',
                            'prev_status' => $prevStatus, 'new_status' => 'Resolved',
                            'changed_at'  => $scanTime,
                        ]);
                    }
                }); // end transaction

                $this->line("       new=" .
                    VulnTracked::where('assessment_id', $assessment->id)->where('first_scan_id', $scan->id)->count() .
                    " | active=" .
                    VulnTracked::where('assessment_id', $assessment->id)->whereIn('tracking_status', ['New','Pending'])->count() .
                    " | resolved=" .
                    VulnTracked::where('assessment_id', $assessment->id)->where('tracking_status', 'Resolved')->count()
                );
            }

            $this->info("  Done. Total tracked: " . VulnTracked::where('assessment_id', $assessment->id)->count());
        }

        $this->newLine();
        $this->info('Rebuild complete.');
        return self::SUCCESS;
    }
}
