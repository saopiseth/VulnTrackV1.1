<?php

namespace App\Services;

use App\Models\VulnAssessment;
use App\Models\VulnFinding;
use App\Models\VulnRemediation;
use App\Models\VulnScan;
use App\Models\VulnTracked;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tracks vulnerability lifecycle across scans.
 *
 * Unique identity: vuln_key = SHA1(plugin_id|ip|port|protocol)
 * Port is included so the same plugin firing on ports 80 and 443 is tracked
 * as two distinct vulnerabilities (Nessus convention).
 *
 * Status logic per scan upload:
 *
 *   First scan (no existing tracked rows for this assessment):
 *     → All findings created as Open  (baseline established)
 *
 *   Subsequent scans — for each finding present in the current scan:
 *     — vuln_key exists AND status is Resolved   → Reopened (reappeared after fix)
 *     — vuln_key exists AND status is Open|Reopened → Unresolved (confirmed still present)
 *     — vuln_key exists AND status is New|Unresolved → unchanged
 *     — vuln_key does NOT exist                  → New  (never seen before)
 *
 *   After processing current findings (verification scan only):
 *     — Tracked item still active, host WAS scanned, NOT in current findings → Resolved
 *
 * Status lifecycle:
 *   [first scan]  Open
 *   [subsequent]  New → New → Resolved          (new finding fixed before confirmation)
 *                 Open → Unresolved → Resolved  (normal path)
 *                 Resolved → Reopened → Unresolved → Resolved  (regression path)
 *
 * "Active" = New | Open | Unresolved | Reopened
 * "Closed"  = Resolved
 */
class VulnTrackingService
{
    public const OPEN_STATUSES = ['New', 'Open', 'Reopened', 'Persistent'];

    /** Number of verification scans a finding must appear in before becoming Persistent. */
    public const PERSISTENT_THRESHOLD = 2;

    /**
     * @param  string[]  $scannedHostIps  All IPs the scanner visited (from hostOsMap keys).
     *                                    If provided, hosts that were scanned but have zero
     *                                    remaining findings can still have their tracked items
     *                                    resolved.  Falls back to deriving hosts from findings
     *                                    when empty (legacy / CSV path).
     * @return array{created:int, reopened:int, still_open:int, resolved:int, severity_changed:int}
     */
    public function track(VulnAssessment $assessment, VulnScan $scan, array $scannedHostIps = []): array
    {
        $scanTime = $scan->created_at ?? now();

        $stats = [
            'created'          => 0,
            'reopened'         => 0,
            'still_open'       => 0,
            'resolved'         => 0,
            'severity_changed' => 0,
        ];

        // ── 1. Load this scan's findings (vuln_key → finding) ───────────────
        // vuln_key = SHA1(plugin_id|ip|port|protocol), so the same plugin on
        // different ports is treated as distinct vulnerabilities (Nessus convention).
        $currentMap = VulnFinding::where('scan_id', $scan->id)
            ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
            ->get()
            ->keyBy(fn($f) => $f->vuln_key);

        // ── 2. Load all existing tracked items for this assessment ────────────
        $existingTracked = VulnTracked::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy(fn($t) => $t->vuln_key);

        // First scan = no tracked rows exist yet → create everything as Open
        $isFirstScan = $existingTracked->isEmpty();

        // ── 3. Accepted-risk remediations (never auto-close these) ───────────
        $acceptedRisks = VulnRemediation::where('assessment_id', $assessment->id)
            ->where('status', 'Accepted Risk')
            ->get()
            ->keyBy(fn($r) => $r->plugin_id . '|' . $r->ip_address);

        $historyBatch = [];

        // ── 4. Process every finding present in the current scan ─────────────
        foreach ($currentMap as $fp => $finding) {

            $fields = $this->currentFields($finding, $scanTime, $scan->id);

            if ($existingTracked->has($fp)) {

                $tracked      = $existingTracked[$fp];
                $prevStatus   = $tracked->tracking_status;
                $prevSeverity = $tracked->severity;

                if ($prevStatus === 'Resolved') {
                    // ── Reopen: was closed, now reappears ─────────────────────
                    $tracked->update(array_merge($fields, [
                        'tracking_status' => 'Reopened',
                        'resolved_at'     => null,
                        'reopen_count'    => $tracked->reopen_count + 1,
                    ]));
                    // Reset remediation back to Open (unless Accepted Risk)
                    VulnRemediation::where('assessment_id', $assessment->id)
                        ->where('plugin_id',  $tracked->plugin_id)
                        ->where('ip_address', $tracked->ip_address)
                        ->where('status', '!=', 'Accepted Risk')
                        ->update(['status' => 'Open', 'updated_at' => now()]);
                    $historyBatch[] = $this->historyRow(
                        $tracked->id, $scan->id, $scanTime,
                        'reappeared', $prevStatus, 'Reopened'
                    );
                    $stats['reopened']++;

                } else {
                    // ── Confirm still present ─────────────────────────────────
                    // On verification scans, increment the verification counter and
                    // promote to Persistent once the threshold is reached.
                    // On regular scans, status is unchanged (no demotion to Unresolved).
                    $newVerifCount = $tracked->verification_seen_count;
                    if ($scan->is_verification) {
                        $newVerifCount++;
                    }

                    if ($scan->is_verification
                        && in_array($prevStatus, ['Open', 'Reopened'])
                        && $newVerifCount >= self::PERSISTENT_THRESHOLD) {
                        $newStatus = 'Persistent';
                    } else {
                        $newStatus = $prevStatus; // Open|Reopened|New|Persistent stays as-is
                    }

                    $tracked->update(array_merge($fields, [
                        'tracking_status'         => $newStatus,
                        'resolved_at'             => null,
                        'verification_seen_count' => $newVerifCount,
                    ]));
                    $eventType = $newStatus === $prevStatus ? 'still_present' : 'status_changed';
                    $historyBatch[] = $this->historyRow(
                        $tracked->id, $scan->id, $scanTime,
                        $eventType, $prevStatus, $newStatus
                    );
                    $stats['still_open']++;
                }

                // Severity change is orthogonal to status
                if ($prevSeverity !== $finding->severity) {
                    $historyBatch[] = $this->historyRow(
                        $tracked->id, $scan->id, $scanTime,
                        'severity_changed', null, null, $prevSeverity, $finding->severity
                    );
                    $stats['severity_changed']++;
                }

            } else {
                // ── New finding ───────────────────────────────────────────────
                // First scan → Open immediately (baseline established).
                // Subsequent scans → New (never seen before on this assessment).
                $initStatus = $isFirstScan ? 'Open' : 'New';
                $tracked = VulnTracked::create(array_merge($fields, [
                    'assessment_id'   => $assessment->id,
                    'ip_address'      => $finding->ip_address,
                    'plugin_id'       => $finding->plugin_id,
                    'vuln_key'        => $finding->vuln_key,
                    'cve'             => $finding->cve,
                    'tracking_status' => $initStatus,
                    'first_seen_at'   => $scanTime,
                    'first_scan_id'   => $scan->id,
                ]));
                $historyBatch[] = $this->historyRow(
                    $tracked->id, $scan->id, $scanTime,
                    'created', null, $initStatus, null, $finding->severity
                );
                $stats['created']++;
            }
        }

        // ── 5. Resolve: plugin absent from a verification scan ───────────────
        //
        // Auto-resolve is only triggered when the uploaded scan is explicitly
        // flagged as a verification scan (is_verification = true). Regular scans
        // may cover a different set of hosts or plugins and cannot safely conclude
        // that an absent finding has been fixed.
        if (!$scan->is_verification) {
            // Flush history and return without resolving anything.
            foreach (array_chunk($historyBatch, 500) as $chunk) {
                DB::table('vuln_tracked_history')->insert($chunk);
            }
            return $stats;
        }

        // Use the full host list when available (covers hosts with zero remaining
        // findings after remediation).  Fall back to deriving from findings so that
        // the CSV path and legacy calls without $scannedHostIps still work.
        if (!empty($scannedHostIps)) {
            $scannedIps = collect(array_flip($scannedHostIps)); // O(1) lookup
        } else {
            $scannedIps = $currentMap->map(fn($f) => $f->ip_address)->flip();
        }

        $toResolve = $existingTracked->filter(
            function ($tracked) use ($currentMap, $acceptedRisks, $scannedIps) {
                // Present in current scan → handled above
                if ($currentMap->has($tracked->vuln_key)) {
                    return false;
                }
                // Already resolved → nothing to do
                if ($tracked->tracking_status === 'Resolved') {
                    return false;
                }
                // Accepted risk → never auto-close
                if ($acceptedRisks->has($tracked->plugin_id . '|' . $tracked->ip_address)) {
                    return false;
                }
                // Host not present in this scan → scanner didn't visit it, cannot resolve
                if (!$scannedIps->has($tracked->ip_address)) {
                    return false;
                }
                return true;
            }
        );

        if ($toResolve->isNotEmpty()) {
            DB::table('vuln_tracked')
                ->whereIn('id', $toResolve->pluck('id')->all())
                ->update([
                    'tracking_status' => 'Resolved',
                    'resolved_at'     => $scanTime,
                    'last_scan_id'    => $scan->id,
                    'last_seen_at'    => $scanTime,
                    'updated_at'      => now(),
                ]);

            // Sync remediation status to Resolved (skip Accepted Risk)
            foreach ($toResolve as $tracked) {
                VulnRemediation::where('assessment_id', $assessment->id)
                    ->where('plugin_id',  $tracked->plugin_id)
                    ->where('ip_address', $tracked->ip_address)
                    ->where('status', '!=', 'Accepted Risk')
                    ->update(['status' => 'Resolved', 'updated_at' => now()]);

                $historyBatch[] = $this->historyRow(
                    $tracked->id, $scan->id, $scanTime,
                    'resolved', $tracked->tracking_status, 'Resolved'
                );
            }
            $stats['resolved'] += $toResolve->count();
        }

        // ── 6. Flush history batch ────────────────────────────────────────────
        foreach (array_chunk($historyBatch, 500) as $chunk) {
            DB::table('vuln_tracked_history')->insert($chunk);
        }

        return $stats;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function currentFields(VulnFinding $f, Carbon $scanTime, int $scanId): array
    {
        return [
            'hostname'           => $f->hostname,
            'vuln_name'          => $f->vuln_name,
            'description'        => $f->description,
            'remediation_text'   => $f->remediation_text,
            'severity'           => $f->severity,
            'cvss_score'         => $f->cvss_score,
            'port'               => $f->port ?? '',
            'protocol'           => $f->protocol,
            'vuln_category'      => $f->vuln_category,
            'affected_component' => $f->affected_component,
            'os_detected'        => $f->os_detected,
            'os_name'            => $f->os_name,
            'os_family'          => $f->os_family,
            'plugin_output'      => $f->plugin_output,
            'last_seen_at'       => $scanTime,
            'last_scan_id'       => $scanId,
        ];
    }

    private function historyRow(
        int     $trackedId,
        int     $scanId,
        Carbon  $changedAt,
        string  $eventType,
        ?string $prevStatus   = null,
        ?string $newStatus    = null,
        ?string $prevSeverity = null,
        ?string $newSeverity  = null,
        ?string $note         = null
    ): array {
        return [
            'tracked_id'    => $trackedId,
            'scan_id'       => $scanId,
            'event_type'    => $eventType,
            'prev_status'   => $prevStatus,
            'new_status'    => $newStatus,
            'prev_severity' => $prevSeverity,
            'new_severity'  => $newSeverity,
            'note'          => $note,
            'changed_at'    => $changedAt,
        ];
    }
}
