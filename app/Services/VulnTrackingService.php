<?php

namespace App\Services;

use App\Models\VulnAssessment;
use App\Models\VulnScan;
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
        $now      = now()->toDateTimeString();

        $stats = [
            'created'          => 0,
            'reopened'         => 0,
            'still_open'       => 0,
            'resolved'         => 0,
            'severity_changed' => 0,
        ];

        // ── 1. Load this scan's findings — exclude plugin_output (can be MBs per row)
        // to avoid OOM when thousands of findings are loaded into PHP memory at once.
        $currentMap = DB::table('vuln_findings')
            ->where('scan_id', $scan->id)
            ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
            ->select([
                'vuln_key', 'ip_address', 'plugin_id', 'cve', 'hostname', 'vuln_name',
                'description', 'remediation_text', 'severity', 'cvss_score',
                'port', 'protocol', 'vuln_category', 'affected_component',
                'os_detected', 'os_name', 'os_family',
            ])
            ->get()
            ->keyBy('vuln_key');

        // ── 2. Load existing tracked items — only the columns we actually need ─
        // Large text fields (description, remediation_text, plugin_output) are
        // excluded; they come from the current finding and are not needed for
        // decision-making on existing rows.
        $existingTracked = DB::table('vuln_tracked')
            ->where('assessment_id', $assessment->id)
            ->select([
                'id', 'assessment_id', 'ip_address', 'plugin_id', 'vuln_key', 'cve',
                'tracking_status', 'severity', 'first_seen_at', 'first_scan_id',
                'created_at', 'reopen_count', 'verification_seen_count',
            ])
            ->get()
            ->keyBy('vuln_key');

        $isFirstScan = $existingTracked->isEmpty();

        // ── 3. Accepted-risk remediations (never auto-close these) ───────────
        $acceptedRisks = DB::table('vuln_remediations')
            ->where('assessment_id', $assessment->id)
            ->where('status', 'Accepted Risk')
            ->get(['plugin_id', 'ip_address'])
            ->keyBy(fn($r) => $r->plugin_id . '|' . $r->ip_address);

        $historyBatch      = [];
        $trackedUpdateRows = [];  // existing tracked rows to batch-update
        $trackedCreateRows = [];  // new tracked rows to batch-insert
        $reopenedRemPairs  = [];  // (plugin_id, ip_address) pairs needing remediation reset

        // ── 4. Process every finding present in the current scan ─────────────
        foreach ($currentMap as $fp => $finding) {

            $fields = $this->currentFieldsFromRow($finding, $scanTime, $scan->id, $now);

            if ($existingTracked->has($fp)) {

                $tracked      = $existingTracked[$fp];
                $prevStatus   = $tracked->tracking_status;
                $prevSeverity = $tracked->severity;

                // Start from the full existing row so every NOT NULL column has a
                // value for the INSERT part of the upsert (required by MySQL strict mode
                // even when ON DUPLICATE KEY UPDATE fires instead of a real insert).
                $baseRow = (array) $tracked;

                if ($prevStatus === 'Resolved') {
                    // ── Reopen: was closed, now reappears ─────────────────────
                    $trackedUpdateRows[] = array_merge($baseRow, $fields, [
                        'tracking_status'         => 'Reopened',
                        'resolved_at'             => null,
                        'reopen_count'            => ($tracked->reopen_count ?? 0) + 1,
                        'verification_seen_count' => $tracked->verification_seen_count ?? 0,
                    ]);
                    $reopenedRemPairs[] = [
                        'plugin_id'  => $tracked->plugin_id,
                        'ip_address' => $tracked->ip_address,
                    ];
                    $historyBatch[] = $this->historyRow(
                        $tracked->id, $scan->id, $scanTime,
                        'reappeared', $prevStatus, 'Reopened'
                    );
                    $stats['reopened']++;

                } else {
                    // ── Confirm still present ─────────────────────────────────
                    $newVerifCount = $tracked->verification_seen_count ?? 0;
                    if ($scan->is_verification) {
                        $newVerifCount++;
                    }

                    $newStatus = ($scan->is_verification
                        && in_array($prevStatus, ['Open', 'Reopened'])
                        && $newVerifCount >= self::PERSISTENT_THRESHOLD)
                        ? 'Persistent' : $prevStatus;

                    $trackedUpdateRows[] = array_merge($baseRow, $fields, [
                        'tracking_status'         => $newStatus,
                        'resolved_at'             => null,
                        'reopen_count'            => $tracked->reopen_count ?? 0,
                        'verification_seen_count' => $newVerifCount,
                    ]);
                    $eventType = $newStatus === $prevStatus ? 'still_present' : 'status_changed';
                    $historyBatch[] = $this->historyRow(
                        $tracked->id, $scan->id, $scanTime,
                        $eventType, $prevStatus, $newStatus
                    );
                    $stats['still_open']++;
                }

                if ($prevSeverity !== $finding->severity) {
                    $historyBatch[] = $this->historyRow(
                        $tracked->id, $scan->id, $scanTime,
                        'severity_changed', null, null, $prevSeverity, $finding->severity
                    );
                    $stats['severity_changed']++;
                }

            } else {
                // ── New finding ───────────────────────────────────────────────
                $initStatus      = $isFirstScan ? 'Open' : 'New';
                $trackedCreateRows[] = array_merge($fields, [
                    'assessment_id'           => $assessment->id,
                    'ip_address'              => $finding->ip_address,
                    'plugin_id'               => $finding->plugin_id,
                    'vuln_key'                => $finding->vuln_key,
                    'cve'                     => $finding->cve,
                    'tracking_status'         => $initStatus,
                    'first_seen_at'           => $scanTime,
                    'first_scan_id'           => $scan->id,
                    'reopen_count'            => 0,
                    'verification_seen_count' => 0,
                    'resolved_at'             => null,
                    'created_at'              => $now,
                ]);
                $stats['created']++;
            }
        }

        // ── 5. Bulk-update existing tracked items (replaces N individual UPDATEs)
        $updateCols = [
            'hostname', 'vuln_name', 'description', 'remediation_text', 'severity',
            'cvss_score', 'port', 'protocol', 'vuln_category', 'affected_component',
            'os_detected', 'os_name', 'os_family',
            'last_seen_at', 'last_scan_id',
            'tracking_status', 'resolved_at', 'reopen_count', 'verification_seen_count',
            'updated_at',
        ];
        // Chunk size kept small (100) because rows include plugin_output which can
        // be large — avoids exceeding MySQL's max_allowed_packet per statement.
        foreach (array_chunk($trackedUpdateRows, 100) as $chunk) {
            DB::table('vuln_tracked')->upsert($chunk, ['id'], $updateCols);
        }

        // ── 6. Bulk-insert new tracked items, then fetch IDs for history ──────
        if (!empty($trackedCreateRows)) {
            $createKeyToStatus   = array_column($trackedCreateRows, 'tracking_status', 'vuln_key');
            $createKeyToSeverity = array_column($trackedCreateRows, 'severity', 'vuln_key');

            foreach (array_chunk($trackedCreateRows, 100) as $chunk) {
                DB::table('vuln_tracked')->insert($chunk);
            }

            $newTrackedIds = DB::table('vuln_tracked')
                ->where('assessment_id', $assessment->id)
                ->whereIn('vuln_key', array_keys($createKeyToStatus))
                ->pluck('id', 'vuln_key');

            foreach ($createKeyToStatus as $vk => $initStatus) {
                $tid = $newTrackedIds[$vk] ?? null;
                if ($tid) {
                    $historyBatch[] = $this->historyRow(
                        $tid, $scan->id, $scanTime,
                        'created', null, $initStatus, null, $createKeyToSeverity[$vk] ?? null
                    );
                }
            }
        }

        // ── 7. Reset reopened remediations ────────────────────────────────────
        foreach ($reopenedRemPairs as $pair) {
            DB::table('vuln_remediations')
                ->where('assessment_id', $assessment->id)
                ->where('plugin_id',  $pair['plugin_id'])
                ->where('ip_address', $pair['ip_address'])
                ->where('status', '!=', 'Accepted Risk')
                ->update(['status' => 'Open', 'updated_at' => $now]);
        }

        // ── 8. Resolve: plugin absent from a verification scan ───────────────
        if (!$scan->is_verification) {
            foreach (array_chunk($historyBatch, 500) as $chunk) {
                DB::table('vuln_tracked_history')->insert($chunk);
            }
            return $stats;
        }

        if (!empty($scannedHostIps)) {
            $scannedIps = collect(array_flip($scannedHostIps));
        } else {
            $scannedIps = $currentMap->map(fn($f) => $f->ip_address)->flip();
        }

        $toResolve = $existingTracked->filter(
            function ($tracked) use ($currentMap, $acceptedRisks, $scannedIps) {
                if ($currentMap->has($tracked->vuln_key)) return false;
                if ($tracked->tracking_status === 'Resolved') return false;
                if ($acceptedRisks->has($tracked->plugin_id . '|' . $tracked->ip_address)) return false;
                if (!$scannedIps->has($tracked->ip_address)) return false;
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
                    'updated_at'      => $now,
                ]);

            foreach ($toResolve as $tracked) {
                DB::table('vuln_remediations')
                    ->where('assessment_id', $assessment->id)
                    ->where('plugin_id',  $tracked->plugin_id)
                    ->where('ip_address', $tracked->ip_address)
                    ->where('status', '!=', 'Accepted Risk')
                    ->update(['status' => 'Resolved', 'updated_at' => $now]);

                $historyBatch[] = $this->historyRow(
                    $tracked->id, $scan->id, $scanTime,
                    'resolved', $tracked->tracking_status, 'Resolved'
                );
            }
            $stats['resolved'] += $toResolve->count();
        }

        // ── 9. Flush history batch ────────────────────────────────────────────
        foreach (array_chunk($historyBatch, 500) as $chunk) {
            DB::table('vuln_tracked_history')->insert($chunk);
        }

        return $stats;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function currentFieldsFromRow(object $f, Carbon $scanTime, int $scanId, string $now): array
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
            // plugin_output is intentionally excluded: copying it into the tracking
            // table for every finding loaded into PHP memory causes OOM on large scans.
            // The output is always accessible via vuln_findings by vuln_key.
            'last_seen_at'       => $scanTime,
            'last_scan_id'       => $scanId,
            'updated_at'         => $now,
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
