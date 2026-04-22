<?php

namespace App\Http\Controllers;

use App\Models\SlaPolicy;
use App\Models\User;
use App\Models\VulnAssessment;
use App\Models\VulnRemediation;
use App\Models\VulnTracked;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $displaySeverities = ['Critical', 'High', 'Medium', 'Low'];
        $openStatuses      = ['New', 'Open', 'Unresolved', 'Reopened'];

        // ── Top stats ────────────────────────────────────────────────────────
        $totalUsers       = User::count();
        $totalAssessments = VulnAssessment::count();

        $openFindings = VulnTracked::whereIn('severity', $displaySeverities)
            ->whereIn('tracking_status', $openStatuses)
            ->count();

        $criticalHighOpen = VulnTracked::whereIn('severity', ['Critical', 'High'])
            ->whereIn('tracking_status', $openStatuses)
            ->count();

        // ── Severity breakdown (all open findings) ───────────────────────────
        $severityCounts = VulnTracked::whereIn('severity', $displaySeverities)
            ->whereIn('tracking_status', $openStatuses)
            ->selectRaw('severity, COUNT(*) as cnt')
            ->groupBy('severity')
            ->pluck('cnt', 'severity');

        // ── Remediation status counts ────────────────────────────────────────
        $remStatusCounts = VulnRemediation::selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $resolvedCount    = $remStatusCounts['Resolved']     ?? 0;
        $inProgressCount  = $remStatusCounts['In Progress']  ?? 0;
        $openRemCount     = $remStatusCounts['Open']         ?? 0;
        $acceptedCount    = $remStatusCounts['Accepted Risk'] ?? 0;
        $totalRem         = $resolvedCount + $inProgressCount + $openRemCount + $acceptedCount;
        $resolvedPct      = $totalRem > 0 ? round(($resolvedCount / $totalRem) * 100) : 0;

        // ── SLA breached count (default policy) ──────────────────────────────
        $slaBreached = 0;
        $defaultSla  = SlaPolicy::where('is_default', true)->first();
        if ($defaultSla) {
            $tracked = VulnTracked::whereIn('severity', $displaySeverities)
                ->whereIn('tracking_status', $openStatuses)
                ->get(['severity', 'first_seen_at']);

            foreach ($tracked as $t) {
                $days = $defaultSla->daysForSeverity($t->severity);
                if ($days && now()->gt(\Carbon\Carbon::parse($t->first_seen_at)->addDays($days))) {
                    $slaBreached++;
                }
            }
        }

        // ── Recent assessments ───────────────────────────────────────────────
        $recentAssessments = VulnAssessment::with('creator')
            ->withCount([
                'remediations as open_count' => fn($q) =>
                    $q->where('status', 'Open'),
                'remediations as resolved_count' => fn($q) =>
                    $q->where('status', 'Resolved'),
            ])
            ->latest()
            ->limit(6)
            ->get();

        // Severity counts per assessment from vuln_tracked
        $assessmentIds = $recentAssessments->pluck('id');
        $sevByAssessment = VulnTracked::whereIn('assessment_id', $assessmentIds)
            ->whereIn('severity', $displaySeverities)
            ->whereIn('tracking_status', $openStatuses)
            ->selectRaw('assessment_id, severity, COUNT(*) as cnt')
            ->groupBy('assessment_id', 'severity')
            ->get()
            ->groupBy('assessment_id');

        // ── Top open vulnerabilities (most widespread) ───────────────────────
        $topVulns = VulnTracked::whereIn('severity', ['Critical', 'High'])
            ->whereIn('tracking_status', $openStatuses)
            ->selectRaw('plugin_id, vuln_name, severity, COUNT(*) as host_count')
            ->groupBy('plugin_id', 'vuln_name', 'severity')
            ->orderByRaw("CASE severity WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 ELSE 3 END")
            ->orderByDesc('host_count')
            ->limit(6)
            ->get();

        return view('dashboard', compact(
            'totalUsers', 'totalAssessments',
            'openFindings', 'criticalHighOpen',
            'severityCounts',
            'remStatusCounts', 'resolvedCount', 'inProgressCount',
            'openRemCount', 'acceptedCount', 'totalRem', 'resolvedPct',
            'slaBreached', 'defaultSla',
            'recentAssessments', 'sevByAssessment',
            'topVulns'
        ));
    }
}
