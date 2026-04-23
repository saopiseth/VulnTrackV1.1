<?php

namespace App\Http\Controllers;

use App\Models\SlaPolicy;
use App\Models\User;
use App\Models\VulnAssessment;
use App\Models\VulnRemediation;
use App\Models\VulnTracked;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    const DEFAULT_WIDGETS = [
        ['id' => 'stat_assessments',   'label' => 'Total Assessments',      'icon' => 'bi-clipboard2-pulse-fill', 'size' => 'stat'],
        ['id' => 'stat_findings',      'label' => 'Open Findings',           'icon' => 'bi-bug-fill',              'size' => 'stat'],
        ['id' => 'stat_remediated',    'label' => 'Remediated',              'icon' => 'bi-patch-check-fill',      'size' => 'stat'],
        ['id' => 'stat_users',         'label' => 'Users',                   'icon' => 'bi-people-fill',           'size' => 'stat'],
        ['id' => 'severity_breakdown', 'label' => 'Findings by Severity',    'icon' => 'bi-bar-chart-fill',        'size' => 'wide'],
        ['id' => 'remediation_status', 'label' => 'Remediation Status',      'icon' => 'bi-clipboard2-check-fill', 'size' => 'wide'],
        ['id' => 'recent_assessments', 'label' => 'Recent Assessments',      'icon' => 'bi-clock-history',         'size' => 'wide'],
        ['id' => 'sla_status',         'label' => 'SLA Status',              'icon' => 'bi-stopwatch-fill',        'size' => 'narrow'],
        ['id' => 'top_vulns',          'label' => 'Top Vulnerabilities',     'icon' => 'bi-exclamation-octagon-fill','size' => 'narrow'],
        ['id' => 'quick_actions',      'label' => 'Quick Actions',           'icon' => 'bi-lightning-fill',        'size' => 'narrow'],
    ];

    public function saveLayout(Request $request)
    {
        $request->validate([
            'layout'          => ['required', 'array'],
            'layout.*.id'     => ['required', 'string'],
            'layout.*.visible'=> ['required', 'boolean'],
        ]);

        $validIds = collect(self::DEFAULT_WIDGETS)->pluck('id')->all();
        $layout = collect($request->layout)
            ->filter(fn($w) => in_array($w['id'], $validIds))
            ->values()
            ->all();

        Auth::user()->update(['dashboard_layout' => $layout]);

        return response()->json(['ok' => true]);
    }

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

        // ── Widget layout ────────────────────────────────────────────────────
        $savedLayout = Auth::user()->dashboard_layout ?? [];
        $savedIds    = collect($savedLayout)->pluck('id')->all();

        // Merge saved order + visibility with defaults (add new widgets at end)
        $allDefaults = collect(self::DEFAULT_WIDGETS);
        $merged = collect($savedLayout)->map(function ($saved) use ($allDefaults) {
            $def = $allDefaults->firstWhere('id', $saved['id']);
            return $def ? array_merge($def, ['visible' => (bool)($saved['visible'] ?? true)]) : null;
        })->filter();

        // Append any new default widgets not yet in saved layout
        $allDefaults->each(function ($def) use ($savedIds, &$merged) {
            if (!in_array($def['id'], $savedIds)) {
                $merged->push(array_merge($def, ['visible' => true]));
            }
        });

        $widgets = $merged->values()->all();

        return view('dashboard', compact(
            'totalUsers', 'totalAssessments',
            'openFindings', 'criticalHighOpen',
            'severityCounts',
            'remStatusCounts', 'resolvedCount', 'inProgressCount',
            'openRemCount', 'acceptedCount', 'totalRem', 'resolvedPct',
            'slaBreached', 'defaultSla',
            'recentAssessments', 'sevByAssessment',
            'topVulns',
            'widgets'
        ));
    }
}
