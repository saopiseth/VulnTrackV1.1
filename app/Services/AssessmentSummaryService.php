<?php

namespace App\Services;

use App\Models\AssessmentSummary;
use App\Models\VulnAssessment;
use Illuminate\Support\Facades\DB;

class AssessmentSummaryService
{
    // Open statuses mirrored from VulnTrackingService to avoid a class load.
    private const OPEN = "'New','Open','Reopened','Persistent'";

    /**
     * Return the cached summary, rebuilding it on cache miss.
     */
    public function get(VulnAssessment $assessment): AssessmentSummary
    {
        return AssessmentSummary::find($assessment->id) ?? $this->rebuild($assessment);
    }

    /**
     * Compute and persist fresh stats + top-20 host list for the assessment.
     */
    public function rebuild(VulnAssessment $assessment): AssessmentSummary
    {
        $id           = $assessment->id;
        $scopeGroupId = $assessment->scope_group_id;

        // Collect scope IP list when a scope group is assigned.
        $scopeIps = null;
        if ($scopeGroupId) {
            $scopeIps = DB::table('assessment_scopes')
                ->where('group_id', $scopeGroupId)
                ->whereNotNull('ip_address')
                ->pluck('ip_address')
                ->all();
        }

        // ── Aggregate stats ───────────────────────────────────────────────
        $statsRow = DB::table('vuln_tracked as vt')
            ->where('vt.assessment_id', $id)
            ->whereIn('vt.severity', ['Critical', 'High', 'Medium', 'Low'])
            ->when($scopeIps !== null, fn($q) => $q->whereIn('vt.ip_address', $scopeIps))
            ->selectRaw("
                SUM(CASE WHEN vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) as active_total,
                SUM(CASE WHEN vt.tracking_status = 'Resolved'             THEN 1 ELSE 0 END) as resolved_total,
                SUM(CASE WHEN vt.severity = 'Critical' AND vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN vt.severity = 'High'     AND vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN vt.severity = 'Medium'   AND vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) as medium,
                SUM(CASE WHEN vt.severity = 'Low'      AND vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) as low,
                COUNT(DISTINCT CASE WHEN vt.tracking_status IN (" . self::OPEN . ") THEN vt.ip_address END) as host_count
            ")
            ->first();

        // ── Top 20 hosts ──────────────────────────────────────────────────
        // Scope metadata (system_name, criticality, etc.) is embedded in the
        // JSON so show() doesn't need a second scope query on every page load.
        if ($scopeGroupId) {
            $scopeColumns = ",
                MAX(sc.id)                  as scope_id,
                MIN(sc.hostname)            as scope_hostname,
                MIN(sc.system_name)         as system_name,
                MIN(sc.system_criticality)  as system_criticality,
                MIN(sc.system_owner)        as system_owner,
                MIN(sc.identified_scope)    as identified_scope,
                MIN(sc.environment)         as environment,
                MIN(sc.remediation_sla)     as remediation_sla";
        } else {
            $scopeColumns = ",
                NULL as scope_id,
                NULL as scope_hostname,
                NULL as system_name,
                NULL as system_criticality,
                NULL as system_owner,
                NULL as identified_scope,
                NULL as environment,
                NULL as remediation_sla";
        }

        $hostQuery = DB::table('vuln_tracked as vt')
            ->where('vt.assessment_id', $id)
            ->whereIn('vt.severity', ['Critical', 'High', 'Medium', 'Low'])
            ->when($scopeIps !== null, fn($q) => $q->whereIn('vt.ip_address', $scopeIps));

        if ($scopeGroupId) {
            $hostQuery->leftJoin('assessment_scopes as sc', fn($j) =>
                $j->on('sc.ip_address', '=', 'vt.ip_address')
                  ->where('sc.group_id', '=', $scopeGroupId));
        }

        $topHosts = $hostQuery
            ->groupBy('vt.ip_address')
            ->orderByRaw("SUM(CASE WHEN vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) DESC")
            ->orderByRaw("SUM(CASE WHEN vt.severity='Critical' AND vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) DESC")
            ->orderByRaw("SUM(CASE WHEN vt.severity='High'     AND vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) DESC")
            ->orderBy('vt.ip_address')
            ->limit(10)
            ->selectRaw("
                vt.ip_address,
                MIN(vt.hostname)      as hostname,
                MIN(vt.os_name)       as os_name,
                MIN(vt.os_family)     as os_family,
                MIN(vt.first_seen_at) as first_detected,
                COUNT(*)              as total,
                SUM(CASE WHEN vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN vt.tracking_status = 'Resolved'             THEN 1 ELSE 0 END) as resolved_count,
                SUM(CASE WHEN vt.severity = 'Critical' AND vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN vt.severity = 'High'     AND vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN vt.severity = 'Medium'   AND vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) as medium,
                SUM(CASE WHEN vt.severity = 'Low'      AND vt.tracking_status IN (" . self::OPEN . ") THEN 1 ELSE 0 END) as low
                {$scopeColumns}
            ")
            ->get()
            ->map(fn($r) => (array) $r)
            ->all();

        return AssessmentSummary::updateOrCreate(
            ['assessment_id' => $id],
            [
                'active_total'   => (int) ($statsRow->active_total   ?? 0),
                'resolved_total' => (int) ($statsRow->resolved_total ?? 0),
                'critical'       => (int) ($statsRow->critical       ?? 0),
                'high'           => (int) ($statsRow->high           ?? 0),
                'medium'         => (int) ($statsRow->medium         ?? 0),
                'low'            => (int) ($statsRow->low            ?? 0),
                'host_count'     => (int) ($statsRow->host_count     ?? 0),
                'top_hosts_json' => $topHosts,
                'updated_at'     => now(),
            ]
        );
    }

    /**
     * Delete the cached summary so it is rebuilt on the next show() request.
     */
    public function invalidate(int $assessmentId): void
    {
        AssessmentSummary::where('assessment_id', $assessmentId)->delete();
    }
}
