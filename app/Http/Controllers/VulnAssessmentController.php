<?php

namespace App\Http\Controllers;

use App\Models\AssessmentScope;
use App\Models\AssessmentScopeGroup;
use App\Models\AuditLog;
use App\Models\SiteSetting;
use App\Models\SlaPolicy;
use App\Models\UserGroup;
use App\Models\VulnAssessment;
use App\Models\VulnFinding;
use App\Models\VulnHostOs;
use App\Models\VulnRemediation;
use App\Models\VulnScan;
use App\Models\VulnTracked;
use App\Jobs\ProcessScanUpload;
use App\Http\Requests\AssignRemediationGroupRequest;
use App\Services\VulnClassifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VulnAssessmentController extends Controller
{
    private int $pptShapeId = 10;
    private float $pptScaleX = 1.0;
    private float $pptScaleY = 1.0;
    private float $pptFontScale = 1.0;
    private array $pptTheme = [];

    public function index()
    {
        $assessments = VulnAssessment::with(['creator', 'scans'])
            ->latest()
            ->paginate(15);

        return view('vuln_assessments.index', compact('assessments'));
    }

    public function create()
    {
        $scopeGroups = AssessmentScopeGroup::withCount('items')->orderBy('name')->get();
        $slaPolicies = SlaPolicy::orderByDesc('is_default')->orderBy('name')->get();

        return view('vuln_assessments.create', compact('scopeGroups', 'slaPolicies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'period_start'   => ['nullable', 'date'],
            'period_end'     => ['nullable', 'date', 'after_or_equal:period_start'],
            'environment'    => ['nullable', 'in:Production,UAT,Internal,Development'],
            'scanner_type'   => ['nullable', 'string', 'max:255'],
            'scope_group_id' => ['nullable', 'integer', 'exists:assessment_scope_groups,id'],
            'sla_policy_id'  => ['nullable', 'integer', 'exists:sla_policies,id'],
        ]);

        // Auto-apply default SLA if none selected
        if (empty($data['sla_policy_id'])) {
            $data['sla_policy_id'] = SlaPolicy::where('is_default', true)->value('id');
        }

        $data['created_by'] = Auth::id();
        $assessment = VulnAssessment::create($data);

        return redirect()->route('vuln-assessments.show', $assessment)
            ->with('success', 'Assessment created. Upload a scan to get started.');
    }

    public function show(VulnAssessment $vulnAssessment)
    {
        $assessment  = $vulnAssessment->load('scans.creator');
        $baseline    = $assessment->baselineScan();
        $latestScan  = $assessment->latestScan();

        $activeScan = $latestScan ?? $baseline;

        // Load scope IPs early; null means no filter (no scope group applied).
        $scopeIps  = null;
        $scopeByIp = collect();
        if ($assessment->scope_group_id) {
            $scopeByIp = DB::table('assessment_scopes')
                ->where('group_id', $assessment->scope_group_id)
                ->whereNotNull('ip_address')
                ->select('id', 'ip_address', 'hostname', 'system_name', 'system_criticality',
                         'system_owner', 'identified_scope', 'environment', 'remediation_sla')
                ->get()
                ->keyBy('ip_address');
            $scopeIps = $scopeByIp->keys()->all();
        }

        // â”€â”€ Stats from vuln_tracked (cumulative across ALL scans) â”€â”€â”€â”€â”€â”€â”€â”€â”€
        // Active = New | Open | Unresolved | Reopened (not yet resolved)
        $stats  = null;
        $topIps = collect();

        $hasTracked = VulnTracked::where('assessment_id', $assessment->id)->exists();

        $openStatuses = VulnTracked::openStatuses(); // ['New','Open','Unresolved','Reopened']

        if ($hasTracked) {
            // Active-only stats for the summary bar
            $stats = VulnTracked::where('assessment_id', $assessment->id)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->whereIn('tracking_status', $openStatuses)
                ->when($scopeIps !== null, fn($q) => $q->whereIn('ip_address', $scopeIps))
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as low
                ")->first();

            // ALL IPs ever seen â€” no tracking_status filter so Resolved IPs stay visible.
            // Scope data loaded separately (no fan-out join).
            $openIn = implode("','", $openStatuses);
            $topIps = VulnTracked::where('assessment_id', $assessment->id)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->when($scopeIps !== null, fn($q) => $q->whereIn('ip_address', $scopeIps))
                ->selectRaw("ip_address,
                    MIN(hostname)      as hostname,
                    MIN(os_name)       as os_name,
                    MIN(os_family)     as os_family,
                    MIN(first_seen_at) as first_detected,
                    COUNT(*)           as total,
                    SUM(CASE WHEN tracking_status IN ('$openIn')                             THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN tracking_status  = 'Resolved'                              THEN 1 ELSE 0 END) as resolved_count,
                    SUM(CASE WHEN severity='Critical' AND tracking_status IN ('$openIn')     THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity='High'     AND tracking_status IN ('$openIn')     THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity='Medium'   AND tracking_status IN ('$openIn')     THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity='Low'      AND tracking_status IN ('$openIn')     THEN 1 ELSE 0 END) as low")
                ->groupBy('ip_address')
                ->orderByRaw("
                    SUM(CASE WHEN tracking_status IN ('$openIn') THEN 1 ELSE 0 END) DESC,
                    SUM(CASE WHEN severity='Critical' AND tracking_status IN ('$openIn') THEN 1 ELSE 0 END) DESC,
                    SUM(CASE WHEN severity='High'     AND tracking_status IN ('$openIn') THEN 1 ELSE 0 END) DESC,
                    ip_address ASC")
                ->get();

            $topIps = $topIps->map(function ($row) use ($scopeByIp) {
                $scope = $scopeByIp->get($row->ip_address);
                $row->scope_id           = $scope?->id;
                $row->scope_hostname     = $scope?->hostname;
                $row->system_name        = $scope?->system_name;
                $row->system_criticality = $scope?->system_criticality;
                $row->system_owner       = $scope?->system_owner;
                $row->identified_scope   = $scope?->identified_scope;
                $row->environment        = $scope?->environment;
                $row->remediation_sla    = $scope?->remediation_sla;
                return $row;
            });

        } elseif ($assessment->scans->isNotEmpty()) {
            // Fallback: no tracked data yet â€” aggregate across ALL uploaded scans
            $allScanIds = $assessment->scans->pluck('id');

            $stats = VulnFinding::whereIn('scan_id', $allScanIds)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->when($scopeIps !== null, fn($q) => $q->whereIn('ip_address', $scopeIps))
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as low
                ")->first();

            $topIps = VulnFinding::whereIn('scan_id', $allScanIds)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->when($scopeIps !== null, fn($q) => $q->whereIn('ip_address', $scopeIps))
                ->selectRaw("ip_address,
                    MIN(hostname)  as hostname,
                    MIN(os_name)   as os_name,
                    MIN(os_family) as os_family,
                    MIN(created_at) as first_detected,
                    COUNT(DISTINCT plugin_id) as total,
                    0 as active_count, 0 as resolved_count,
                    SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as low,
                    NULL as system_name, NULL as system_criticality,
                    NULL as system_owner, NULL as identified_scope")
                ->groupBy('ip_address')
                ->orderByDesc('critical')->orderByDesc('high')->orderByDesc('medium')->orderByDesc('total')
                ->get();
        }

        $comparison   = null;

        // ── Comparison: current vuln_tracked state vs baseline ────────────────
        // Only meaningful when a non-baseline scan exists.
        // Read directly from vuln_tracked (not history) so all scans are covered.
        //   Open       = Open | Persistent  (in baseline, still present)
        //   Reopened   = Reopened           (was fixed, regression)
        //   New        = New                (appeared after the baseline)
        //   Resolved   = Resolved           (was present, now gone)
        $hostComparison = null;
        if ($latestScan) {
            $base = VulnTracked::where('assessment_id', $assessment->id)
                        ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                        ->when($scopeIps !== null, fn($q) => $q->whereIn('ip_address', $scopeIps));

            $comparison = [
                'open'     => (clone $base)->whereIn('tracking_status', ['Open', 'Persistent'])->count(),
                'reopened' => (clone $base)->where('tracking_status', 'Reopened')->count(),
                'new'      => (clone $base)->where('tracking_status', 'New')->count(),
                'resolved' => (clone $base)->where('tracking_status', 'Resolved')->count(),
            ];
        }

        // Host-level comparison (baseline vs latest)
        if ($baseline && $latestScan) {
            $baselineIps = $baseline->hostSet();
            $latestIps   = $latestScan->hostSet();
            if ($scopeIps !== null) {
                $baselineIps = $baselineIps->intersect($scopeIps);
                $latestIps   = $latestIps->intersect($scopeIps);
            }
            $hostComparison = [
                'baseline_count' => $baselineIps->count(),
                'latest_count'   => $latestIps->count(),
                'new'            => $latestIps->diff($baselineIps)->count(),
                'removed'        => $baselineIps->diff($latestIps)->count(),
                'persistent'     => $baselineIps->intersect($latestIps)->count(),
                'new_ips'        => $latestIps->diff($baselineIps)->sort()->values(),
                'removed_ips'    => $baselineIps->diff($latestIps)->sort()->values(),
            ];
        }

        // Unique active hosts across ALL scans (from tracking table)
        $activeHostCount = VulnTracked::where('assessment_id', $assessment->id)
            ->whereIn('tracking_status', VulnTracked::openStatuses())
            ->when($scopeIps !== null, fn($q) => $q->whereIn('ip_address', $scopeIps))
            ->distinct('ip_address')->count('ip_address');

        // Remediation progress â€” driven by vuln_tracked (scan-confirmed) + vuln_remediations (workflow)
        $remStats = null;
        if ($hasTracked) {
            $openIn = implode("','", VulnTracked::openStatuses()); // 'New','Open','Unresolved','Reopened'
            $remStats = DB::table('vuln_tracked as vt')
                ->where('vt.assessment_id', $assessment->id)
                ->whereIn('vt.severity', ['Critical', 'High', 'Medium', 'Low'])
                ->when($scopeIps !== null, fn($q) => $q->whereIn('vt.ip_address', $scopeIps))
                ->leftJoin('vuln_remediations as vr', function ($j) use ($assessment) {
                    $j->on('vr.plugin_id',  '=', 'vt.plugin_id')
                      ->on('vr.ip_address', '=', 'vt.ip_address')
                      ->where('vr.assessment_id', '=', $assessment->id);
                })
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN vt.tracking_status = 'Resolved'                                                                         THEN 1 ELSE 0 END) as resolved_by_scan,
                    SUM(CASE WHEN vt.tracking_status IN ('$openIn') AND (vr.status IS NULL OR vr.status = 'Open')                         THEN 1 ELSE 0 END) as open_count,
                    SUM(CASE WHEN vt.tracking_status IN ('$openIn') AND vr.status = 'In Progress'                                        THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN vt.tracking_status IN ('$openIn') AND vr.status = 'Accepted Risk'                                      THEN 1 ELSE 0 END) as accepted,
                    SUM(CASE WHEN vt.severity = 'Critical' AND vt.tracking_status IN ('$openIn')                                         THEN 1 ELSE 0 END) as active_critical,
                    SUM(CASE WHEN vt.severity = 'High'     AND vt.tracking_status IN ('$openIn')                                         THEN 1 ELSE 0 END) as active_high,
                    SUM(CASE WHEN vt.severity = 'Medium'   AND vt.tracking_status IN ('$openIn')                                         THEN 1 ELSE 0 END) as active_medium,
                    SUM(CASE WHEN vt.severity = 'Low'      AND vt.tracking_status IN ('$openIn')                                         THEN 1 ELSE 0 END) as active_low
                ")
                ->first();
        }

        // OS distribution from vuln_host_os
        $osDistribution = VulnHostOs::where('assessment_id', $assessment->id)
            ->when($scopeIps !== null, fn($q) => $q->whereIn('ip_address', $scopeIps))
            ->selectRaw("COALESCE(os_override_family, os_family) as family, COUNT(*) as cnt")
            ->groupBy('family')
            ->orderByDesc('cnt')
            ->get();

        $osHostCount = VulnHostOs::where('assessment_id', $assessment->id)
            ->when($scopeIps !== null, fn($q) => $q->whereIn('ip_address', $scopeIps))
            ->count();

        $scopeGroups = AssessmentScopeGroup::withCount('items')->orderBy('name')->get();

        return view('vuln_assessments.show', compact(
            'assessment', 'baseline', 'latestScan', 'activeScan',
            'stats', 'topIps', 'comparison', 'hostComparison', 'activeHostCount', 'remStats',
            'osDistribution', 'osHostCount', 'scopeGroups'
        ));
    }

    public function kriReport(VulnAssessment $vulnAssessment)
    {
        $assessment = $vulnAssessment->load('scans.creator', 'slaPolicy');
        $data = $this->buildKriReportData($assessment);

        return view('vuln_assessments.kri_report', array_merge(['assessment' => $assessment], $data));
    }

    public function kriReportPowerPoint(VulnAssessment $vulnAssessment)
    {
        abort_unless(class_exists(\ZipArchive::class), 500, 'PowerPoint export requires the PHP zip extension.');

        $assessment = $vulnAssessment->load('scans.creator', 'slaPolicy');
        $data = $this->buildKriReportData($assessment);
        abort_unless($data['kri'], 404, 'No KRI data available for export.');

        $path = $this->buildKriPowerPoint($assessment, $data);
        $filename = str()->slug($assessment->name) . '_KRI_Report_' . now()->format('Ymd') . '.pptx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ])->deleteFileAfterSend(true);
    }

    private function buildKriReportData(VulnAssessment $assessment): array
    {
        $baseline = $assessment->baselineScan();
        $latestScan = $assessment->latestScan();
        $activeScan = $latestScan ?? $baseline;

        // Load scope IPs early; null means no filter (no scope group applied).
        $scopeIps  = null;
        $scopeByIp = collect();
        if ($assessment->scope_group_id) {
            $scopeByIp = DB::table('assessment_scopes')
                ->where('group_id', $assessment->scope_group_id)
                ->whereNotNull('ip_address')
                ->select('id', 'ip_address', 'hostname', 'system_name', 'system_criticality',
                         'system_owner', 'identified_scope', 'environment', 'remediation_sla')
                ->get()
                ->keyBy('ip_address');
            $scopeIps = $scopeByIp->keys()->all();
        }

        // Load scope IPs early; null means no filter (no scope group applied).
        $scopeIps  = null;
        $scopeByIp = collect();
        if ($assessment->scope_group_id) {
            $scopeByIp = DB::table('assessment_scopes')
                ->where('group_id', $assessment->scope_group_id)
                ->whereNotNull('ip_address')
                ->select('id', 'ip_address', 'hostname', 'system_name', 'system_criticality',
                         'system_owner', 'identified_scope', 'environment', 'remediation_sla')
                ->get()
                ->keyBy('ip_address');
            $scopeIps = $scopeByIp->keys()->all();
        }
        $openStatuses = VulnTracked::openStatuses();
        $hasTracked = VulnTracked::where('assessment_id', $assessment->id)->exists();
        $stats = null;
        $topIps = collect();
        $comparison = null;
        $remStats = null;
        $vulnAgeTrend = collect();

        if ($hasTracked) {
            $stats = VulnTracked::where('assessment_id', $assessment->id)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->whereIn('tracking_status', $openStatuses)
                ->when($scopeIps !== null, fn($q) => $q->whereIn('ip_address', $scopeIps))
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as low
                ")->first();

            $openIn = implode("','", $openStatuses);
            $topIps = VulnTracked::where('assessment_id', $assessment->id)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->when($scopeIps !== null, fn($q) => $q->whereIn('ip_address', $scopeIps))
                ->selectRaw("ip_address,
                    MIN(hostname)      as hostname,
                    MIN(os_name)       as os_name,
                    MIN(os_family)     as os_family,
                    MIN(first_seen_at) as first_detected,
                    COUNT(*)           as total,
                    SUM(CASE WHEN tracking_status IN ('$openIn')                             THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN tracking_status  = 'Resolved'                              THEN 1 ELSE 0 END) as resolved_count,
                    SUM(CASE WHEN severity='Critical' AND tracking_status IN ('$openIn')     THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity='High'     AND tracking_status IN ('$openIn')     THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity='Medium'   AND tracking_status IN ('$openIn')     THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity='Low'      AND tracking_status IN ('$openIn')     THEN 1 ELSE 0 END) as low")
                ->groupBy('ip_address')
                ->orderByRaw("
                    SUM(CASE WHEN tracking_status IN ('$openIn') THEN 1 ELSE 0 END) DESC,
                    SUM(CASE WHEN severity='Critical' AND tracking_status IN ('$openIn') THEN 1 ELSE 0 END) DESC,
                    SUM(CASE WHEN severity='High'     AND tracking_status IN ('$openIn') THEN 1 ELSE 0 END) DESC,
                    ip_address ASC")
                ->get();

            if ($assessment->scope_group_id) {
                $scopeByIp = DB::table('assessment_scopes')
                    ->where('group_id', $assessment->scope_group_id)
                    ->whereNotNull('ip_address')
                    ->select('ip_address', 'system_name', 'system_criticality', 'system_owner', 'identified_scope')
                    ->get()
                    ->keyBy('ip_address');

                $topIps = $topIps->map(function ($row) use ($scopeByIp) {
                    $scope = $scopeByIp->get($row->ip_address);
                    $row->system_name        = $scope?->system_name;
                    $row->system_criticality = $scope?->system_criticality;
                    $row->system_owner       = $scope?->system_owner;
                    $row->identified_scope   = $scope?->identified_scope;
                    return $row;
                });
            }

            // Vulnerability count per assessment (by name) for each top IP
            $topIpAddresses = $topIps->pluck('ip_address')->filter()->values()->all();
            $vulnAgeByIp = collect();
            if (!empty($topIpAddresses)) {
                $vulnAgeByIp = DB::table('vuln_tracked as vt')
                    ->join('vuln_assessments as va', 'va.id', '=', 'vt.assessment_id')
                    ->whereIn('vt.ip_address', $topIpAddresses)
                    ->whereIn('vt.severity', ['Critical', 'High', 'Medium', 'Low'])
                    ->selectRaw('vt.ip_address, va.id as assessment_id, va.uuid as assessment_uuid, va.name as assessment_name, COUNT(*) as cnt')
                    ->groupBy('vt.ip_address', 'va.id', 'va.uuid', 'va.name')
                    ->orderBy('va.id')
                    ->get()
                    ->groupBy('ip_address')
                    ->map(fn ($rows) => $rows->map(fn ($r) => [
                        'name'  => $r->assessment_name,
                        'uuid'  => $r->assessment_uuid,
                        'count' => (int) $r->cnt,
                    ])->values()->all());
            }

            $topIps = $topIps->map(function ($row) use ($vulnAgeByIp) {
                $row->vuln_age_quarters = $vulnAgeByIp->get($row->ip_address, []);
                return $row;
            });

            // Trend: latest 4 assessments with data for IPs in this assessment
            if (!empty($topIpAddresses)) {
                $latestFourIds = DB::table('vuln_tracked as vt')
                    ->join('vuln_assessments as va', 'va.id', '=', 'vt.assessment_id')
                    ->whereIn('vt.ip_address', $topIpAddresses)
                    ->whereIn('vt.severity', ['Critical', 'High', 'Medium', 'Low'])
                    ->orderByDesc('va.id')
                    ->limit(4)
                    ->distinct()
                    ->pluck('va.id');

                $vulnAgeTrend = DB::table('vuln_tracked as vt')
                    ->join('vuln_assessments as va', 'va.id', '=', 'vt.assessment_id')
                    ->whereIn('vt.ip_address', $topIpAddresses)
                    ->whereIn('va.id', $latestFourIds)
                    ->whereIn('vt.severity', ['Critical', 'High', 'Medium', 'Low'])
                    ->selectRaw("va.id as assessment_id, va.uuid, va.name,
                        COUNT(*) as total,
                        SUM(CASE WHEN vt.severity='Critical' THEN 1 ELSE 0 END) as critical,
                        SUM(CASE WHEN vt.severity='High'     THEN 1 ELSE 0 END) as high,
                        SUM(CASE WHEN vt.severity='Medium'   THEN 1 ELSE 0 END) as medium,
                        SUM(CASE WHEN vt.severity='Low'      THEN 1 ELSE 0 END) as low")
                    ->groupBy('va.id', 'va.uuid', 'va.name')
                    ->orderBy('va.id')
                    ->get();
            }

            $remStats = DB::table('vuln_tracked as vt')
                ->where('vt.assessment_id', $assessment->id)
                ->whereIn('vt.severity', ['Critical', 'High', 'Medium', 'Low'])
                ->when($scopeIps !== null, fn($q) => $q->whereIn('vt.ip_address', $scopeIps))
                ->leftJoin('vuln_remediations as vr', function ($j) use ($assessment) {
                    $j->on('vr.plugin_id',  '=', 'vt.plugin_id')
                        ->on('vr.ip_address', '=', 'vt.ip_address')
                        ->where('vr.assessment_id', '=', $assessment->id);
                })
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN vt.tracking_status = 'Resolved'                                                                         THEN 1 ELSE 0 END) as resolved_by_scan,
                    SUM(CASE WHEN vt.tracking_status IN ('$openIn') AND (vr.status IS NULL OR vr.status = 'Open')                         THEN 1 ELSE 0 END) as open_count,
                    SUM(CASE WHEN vt.tracking_status IN ('$openIn') AND vr.status = 'In Progress'                                        THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN vt.tracking_status IN ('$openIn') AND vr.status = 'Accepted Risk'                                      THEN 1 ELSE 0 END) as accepted
                ")
                ->first();

            if ($latestScan) {
                $base = VulnTracked::where('assessment_id', $assessment->id)
                    ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low']);

                $comparison = [
                    'new'      => (clone $base)->whereIn('tracking_status', ['New', 'Reopened'])->count(),
                    'resolved' => (clone $base)->where('tracking_status', 'Resolved')->count(),
                ];
            }
        } elseif ($assessment->scans->isNotEmpty()) {
            $allScanIds = $assessment->scans->pluck('id');
            $stats = VulnFinding::whereIn('scan_id', $allScanIds)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->when($scopeIps !== null, fn($q) => $q->whereIn('ip_address', $scopeIps))
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as low
                ")->first();
        }

        $activeHostCount = $hasTracked
            ? VulnTracked::where('assessment_id', $assessment->id)
                ->whereIn('tracking_status', $openStatuses)
                ->distinct('ip_address')
                ->count('ip_address')
            : 0;

        $slaPolicy = $assessment->slaPolicy ?? SlaPolicy::where('is_default', true)->first();
        $kri = null;
        if ($activeScan && $stats) {
            $totalTracked = $hasTracked
                ? VulnTracked::where('assessment_id', $assessment->id)
                    ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                    ->count()
                : (int) $stats->total;

            $slaCounts = ['on_track' => 0, 'approaching' => 0, 'breached' => 0, 'met' => 0];
            if ($hasTracked && $slaPolicy) {
                $trackedForSla = VulnTracked::where('assessment_id', $assessment->id)
                    ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                    ->get(['severity', 'first_seen_at', 'tracking_status', 'resolved_at']);

                foreach ($trackedForSla as $finding) {
                    if (!$finding->first_seen_at) {
                        continue;
                    }

                    [$status] = $slaPolicy->slaStatus(
                        $finding->severity,
                        \Carbon\Carbon::parse($finding->first_seen_at),
                        $finding->tracking_status === VulnTracked::STATUS_RESOLVED,
                        $finding->resolved_at ? \Carbon\Carbon::parse($finding->resolved_at) : null
                    );

                    if ($status === 'on-track') {
                        $slaCounts['on_track']++;
                    } elseif (isset($slaCounts[$status])) {
                        $slaCounts[$status]++;
                    }
                }
            }

            $resolvedByScan = (int) ($remStats->resolved_by_scan ?? 0);
            $activeTotal = (int) ($stats->total ?? 0);
            $criticalHigh = (int) ($stats->critical ?? 0) + (int) ($stats->high ?? 0);
            $riskScore = ($stats->critical * 10) + ($stats->high * 7) + ($stats->medium * 4) + ($stats->low * 1);

            $kri = [
                'risk_score'             => $riskScore,
                'active_total'           => $activeTotal,
                'critical_high'          => $criticalHigh,
                'critical_high_pct'      => $activeTotal > 0 ? round(($criticalHigh / $activeTotal) * 100) : 0,
                'active_hosts'           => $activeHostCount,
                'mission_critical_hosts' => $topIps->filter(fn($ip) => (int) ($ip->system_criticality ?? 0) === 1 && (int) ($ip->active_count ?? 0) > 0)->count(),
                'remediation_pct'        => $totalTracked > 0 ? round(($resolvedByScan / $totalTracked) * 100) : 0,
                'resolved_by_scan'       => $resolvedByScan,
                'in_progress'            => (int) ($remStats->in_progress ?? 0),
                'accepted_risk'          => (int) ($remStats->accepted ?? 0),
                'open_remediation'       => (int) ($remStats->open_count ?? $activeTotal),
                'sla_policy'             => $slaPolicy?->name,
                'sla_on_track'           => $slaCounts['on_track'],
                'sla_approaching'        => $slaCounts['approaching'],
                'sla_breached'           => $slaCounts['breached'],
                'sla_met'                => $slaCounts['met'],
                'new_findings'           => (int) ($comparison['new'] ?? 0),
                'resolved_findings'      => (int) ($comparison['resolved'] ?? $resolvedByScan),
            ];
        }

        return compact('baseline', 'latestScan', 'activeScan', 'stats', 'topIps', 'activeHostCount', 'remStats', 'comparison', 'kri', 'vulnAgeTrend');
    }

    private function buildKriPowerPoint(VulnAssessment $assessment, array $data): string
    {
        $kri = $data['kri'];
        $stats = $data['stats'];
        $topIps = $data['topIps'];
        $templatePath = resource_path('pptx/kri_template.pptx');
        $path = storage_path('app/kri-report-' . $assessment->uuid . '-' . uniqid() . '.pptx');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        abort_unless(is_file($templatePath), 500, 'PowerPoint export template is missing.');

        $this->pptShapeId = 10;
        $this->pptTheme = $this->pptThemeColors();

        $template = new \ZipArchive();
        abort_unless($template->open($templatePath) === true, 500, 'Unable to open PowerPoint export template.');
        $this->pptApplyTemplateScale($template);

        $charts = $this->pptKriChartData($stats, $kri);
        $media = [
            'severity' => $this->pptDoughnutChartPng($charts['severity']),
            'workflow' => $this->pptDoughnutChartPng($charts['workflow']),
            'sla'      => $this->pptDoughnutChartPng($charts['sla']),
        ];

        $slides = [
            $this->pptSlideExecutive($assessment, $kri),
            $this->pptSlideCharts($assessment, $charts),
            $this->pptSlideRemediation($assessment, $charts, $kri),
            $this->pptSlideHosts($assessment, $topIps),
        ];

        $zip = new \ZipArchive();
        abort_unless($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true, 500, 'Unable to create PowerPoint export.');

        $dynamicParts = [
            '[Content_Types].xml',
            'ppt/presentation.xml',
            'ppt/_rels/presentation.xml.rels',
        ];

        for ($i = 0; $i < $template->numFiles; $i++) {
            $name = $template->getNameIndex($i);
            if (!$name || in_array($name, $dynamicParts, true) || $this->pptShouldSkipTemplatePart($name)) {
                continue;
            }

            $contents = $template->getFromIndex($i);
            if ($contents !== false) {
                $zip->addFromString($name, $contents);
            }
        }

        $zip->addFromString('[Content_Types].xml', $this->pptTemplateContentTypes($template, count($slides), true));
        $zip->addFromString('ppt/presentation.xml', $this->pptTemplatePresentationXml($template, count($slides)));
        $zip->addFromString('ppt/_rels/presentation.xml.rels', $this->pptTemplatePresentationRels($template, count($slides)));
        $zip->addFromString('ppt/media/kri_severity.png', $media['severity']);
        $zip->addFromString('ppt/media/kri_workflow.png', $media['workflow']);
        $zip->addFromString('ppt/media/kri_sla.png', $media['sla']);

        foreach ($slides as $i => $slide) {
            $n = $i + 1;
            $imageTargets = match ($n) {
                2 => ['../media/kri_severity.png', '../media/kri_workflow.png'],
                3 => ['../media/kri_sla.png'],
                default => [],
            };

            $zip->addFromString("ppt/slides/slide{$n}.xml", $slide);
            $zip->addFromString("ppt/slides/_rels/slide{$n}.xml.rels", $this->pptSlideRels($imageTargets));
        }

        abort_unless($zip->close(), 500, 'Unable to finalize PowerPoint export.');
        $template->close();

        return $path;
    }

    private function pptSlideExecutive(VulnAssessment $assessment, array $kri): string
    {
        $theme = $this->pptTheme;
        $shapes = [
            $this->pptGradientRect(0, 0, 12192000, 900000, $theme['secondary'], $theme['primary'], 'rect'),
            $this->pptText('Vulnerability KRI Report', 430000, 260000, 6500000, 380000, 30, $theme['onDark'], true),
            $this->pptText($assessment->name . ' | Generated ' . now()->format('d M Y'), 7350000, 330000, 4100000, 260000, 13, $theme['onDarkMuted']),
            $this->pptText('Executive Summary', 430000, 1180000, 5200000, 360000, 24, $theme['secondary'], true),
            $this->pptText('Key risk indicators highlight current exposure, remediation progress, SLA posture, and host concentration for governance review.', 430000, 1600000, 9000000, 280000, 14, $theme['muted']),
            $this->pptMetric('Critical / High Exposure', number_format($kri['critical_high']), $kri['critical_high_pct'] . '% of active findings', 430000,  2200000, $theme['severity']['High']),
            $this->pptMetric('SLA Breached',             number_format($kri['sla_breached']),  number_format($kri['sla_approaching']) . ' approaching deadline', 4480000, 2200000, $theme['severity']['Medium']),
            $this->pptMetric('Remediation Complete',     $kri['remediation_pct'] . '%',        number_format($kri['resolved_by_scan']) . ' scan-confirmed resolved', 8530000, 2200000, $theme['success']),
            $this->pptInsightBox('Management Focus', [
                'Prioritize critical and high vulnerabilities on the highest-risk hosts.',
                'Track accepted-risk exceptions separately from remediation completion.',
                'Use SLA trend and breach counts for weekly operational follow-up.',
            ], 430000, 4200000, 11000000, 1450000),
        ];

        return $this->pptSlide($shapes);
    }

    private function pptSlideCharts(VulnAssessment $assessment, array $charts): string
    {
        $shapes = [
            $this->pptSlideTitle('Exposure And Remediation Mix', 'Doughnut charts show active severity concentration and remediation workflow distribution.'),
            $this->pptChartPanel('Active Severity Distribution', 'Critical and High exposure should drive immediate treatment priorities.', 'rId2', $charts['severity'], 430000, 1250000),
            $this->pptChartPanel('Remediation Workflow', 'Resolved items are scan-confirmed; accepted risk remains visible as an exception category.', 'rId3', $charts['workflow'], 6250000, 1250000),
        ];

        return $this->pptSlide($shapes);
    }

    private function pptSlideRemediation(VulnAssessment $assessment, array $charts, array $kri): string
    {
        $theme = $this->pptTheme;
        $shapes = [
            $this->pptSlideTitle('SLA Health And Action Plan', 'SLA status, deadline pressure, and remediation completion for operational governance.'),
            $this->pptChartPanel('SLA Health', 'Breached and approaching items require owner follow-up before the next governance checkpoint.', 'rId2', $charts['sla'], 430000, 1250000),
            $this->pptRect(6250000, 1250000, 5400000, 4050000, $theme['surface'], $theme['border']),
            $this->pptText('KRI Action Summary', 6550000, 1530000, 4200000, 280000, 20, $theme['secondary'], true),
            $this->pptText('SLA policy: ' . ($kri['sla_policy'] ?: 'No SLA policy configured'), 6550000, 1940000, 4200000, 240000, 13, $theme['muted']),
            $this->pptMetric('On Track', number_format($kri['sla_on_track']), 'active items inside SLA', 6550000, 2420000, $theme['success']),
            $this->pptMetric('Approaching', number_format($kri['sla_approaching']), 'requires near-term attention', 9200000, 2420000, $theme['severity']['Medium']),
            $this->pptMetric('Breached', number_format($kri['sla_breached']), 'escalate to owners', 6550000, 3950000, $theme['severity']['Critical']),
            $this->pptMetric('Resolved', number_format($kri['resolved_by_scan']), 'scan-confirmed closures', 9200000, 3950000, $theme['success']),
        ];

        return $this->pptSlide($shapes);
    }

    private function pptSlideHosts(VulnAssessment $assessment, $topIps): string
    {
        $theme = $this->pptTheme;
        $shapes = [
            $this->pptSlideTitle('Highest Risk Hosts', 'Top hosts by active findings and critical/high exposure.'),
            $this->pptRect(430000, 1160000, 11000000, 420000, $theme['primary'], $theme['primary'], 'rect'),
        ];

        $y = 1260000;
        $shapes[] = $this->pptText('IP Address', 500000, $y, 1600000, 240000, 12, $theme['onDark'], true);
        $shapes[] = $this->pptText('Hostname', 2200000, $y, 2100000, 240000, 12, $theme['onDark'], true);
        $shapes[] = $this->pptText('Critical', 4600000, $y, 900000, 240000, 12, $theme['onDark'], true);
        $shapes[] = $this->pptText('High', 5700000, $y, 900000, 240000, 12, $theme['onDark'], true);
        $shapes[] = $this->pptText('Active', 6800000, $y, 900000, 240000, 12, $theme['onDark'], true);
        $shapes[] = $this->pptText('Owner', 7900000, $y, 1400000, 240000, 12, $theme['onDark'], true);

        foreach ($topIps->take(8)->values() as $i => $ip) {
            $y = 1740000 + ($i * 420000);
            $fill = $i % 2 === 0 ? $theme['background'] : $theme['surface'];
            $shapes[] = $this->pptRect(430000, $y - 60000, 11000000, 360000, $fill, $theme['border'], 'rect');
            $shapes[] = $this->pptText($ip->ip_address, 500000, $y, 1600000, 220000, 11, $theme['secondary'], true);
            $shapes[] = $this->pptText($ip->hostname ?: '-', 2200000, $y, 2100000, 220000, 11, $theme['muted']);
            $shapes[] = $this->pptBadge((string) $ip->critical, 4620000, $y - 20000, $theme['severity']['Critical']);
            $shapes[] = $this->pptBadge((string) $ip->high, 5720000, $y - 20000, $theme['severity']['High']);
            $shapes[] = $this->pptText((string) $ip->active_count, 6900000, $y, 600000, 220000, 11, $theme['success'], true);
            $shapes[] = $this->pptText($ip->system_owner ?: '-', 7900000, $y, 1400000, 220000, 11, $theme['muted']);
        }

        return $this->pptSlide($shapes);
    }

    private function pptBarChart(string $title, array $rows, int $x, int $y): string
    {
        $max = max(1, ...array_map(fn($row) => (int) $row[1], $rows));
        $shapes = [$this->pptText($title, $x, $y, 3800000, 260000, 14, '0F172A', true)];
        foreach ($rows as $i => [$label, $value, $color]) {
            $rowY = $y + 430000 + ($i * 360000);
            $width = (int) round(((int) $value / $max) * 2200000);
            $shapes[] = $this->pptText($label, $x, $rowY, 950000, 220000, 10, '475569', true);
            $shapes[] = $this->pptRect($x + 1050000, $rowY + 35000, 2200000, 130000, 'E2E8F0', 'E2E8F0');
            $shapes[] = $this->pptRect($x + 1050000, $rowY + 35000, max(50000, $width), 130000, $color, $color);
            $shapes[] = $this->pptText(number_format($value), $x + 3400000, $rowY, 500000, 220000, 10, '64748B', true);
        }
        return implode('', $shapes);
    }

    private function pptMetric(string $label, string $value, string $note, int $x, int $y, string $color, int $w = 2400000, int $h = 1220000): string
    {
        $theme = $this->pptTheme;

        return $this->pptRect($x, $y, $w, $h, $theme['surface'], $theme['border'])
            . $this->pptRect($x, $y, 65000, $h, $color, $color, 'rect')
            . $this->pptText($label, $x + 170000, $y + 150000, $w - 300000, 220000, 11, $theme['muted'], true)
            . $this->pptText($value, $x + 170000, $y + 420000, $w - 300000, 380000, 22, $color, true)
            . $this->pptText($note, $x + 170000, $y + 860000, $w - 300000, 240000, 11, $theme['muted']);
    }

    private function pptSlide(array $shapes): string
    {
        $background = $this->pptTheme['background'] ?? 'F8FAFC';
        $content = implode('', $shapes) . $this->pptFooter();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
            . '<p:cSld><p:bg><p:bgPr><a:solidFill><a:srgbClr val="' . $background . '"/></a:solidFill><a:effectLst/></p:bgPr></p:bg><p:spTree>'
            . '<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>'
            . $content
            . '</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sld>';
    }

    private function pptText(string $text, int $x, int $y, int $w, int $h, int $pt = 12, string $color = '0F172A', bool $bold = false): string
    {
        $id = ++$this->pptShapeId;
        [$x, $y, $w, $h] = $this->pptScaleBox($x, $y, $w, $h);
        $pt = $this->pptScalePoint($pt);
        $safe = $this->pptXml($text);
        $boldAttr = $bold ? ' b="1"' : '';
        return '<p:sp><p:nvSpPr><p:cNvPr id="' . $id . '" name="Text"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="' . $x . '" y="' . $y . '"/><a:ext cx="' . $w . '" cy="' . $h . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr><p:txBody><a:bodyPr wrap="square"/><a:lstStyle/><a:p><a:r><a:rPr lang="en-US" sz="' . ($pt * 100) . '"' . $boldAttr . '><a:solidFill><a:srgbClr val="' . $color . '"/></a:solidFill><a:latin typeface="Segoe UI"/><a:ea typeface="Aptos"/><a:cs typeface="Calibri"/></a:rPr><a:t>' . $safe . '</a:t></a:r></a:p></p:txBody></p:sp>';
    }

    private function pptRect(int $x, int $y, int $w, int $h, string $fill, string $line, string $shape = 'roundRect'): string
    {
        $id = ++$this->pptShapeId;
        [$x, $y, $w, $h] = $this->pptScaleBox($x, $y, $w, $h);
        return '<p:sp><p:nvSpPr><p:cNvPr id="' . $id . '" name="Box"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="' . $x . '" y="' . $y . '"/><a:ext cx="' . $w . '" cy="' . $h . '"/></a:xfrm><a:prstGeom prst="' . $shape . '"><a:avLst/></a:prstGeom><a:solidFill><a:srgbClr val="' . $fill . '"/></a:solidFill><a:ln w="9525"><a:solidFill><a:srgbClr val="' . $line . '"/></a:solidFill></a:ln></p:spPr><p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody></p:sp>';
    }

    private function pptGradientRect(int $x, int $y, int $w, int $h, string $start, string $end, string $shape = 'roundRect'): string
    {
        $id = ++$this->pptShapeId;
        [$x, $y, $w, $h] = $this->pptScaleBox($x, $y, $w, $h);

        return '<p:sp><p:nvSpPr><p:cNvPr id="' . $id . '" name="Gradient"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="' . $x . '" y="' . $y . '"/><a:ext cx="' . $w . '" cy="' . $h . '"/></a:xfrm><a:prstGeom prst="' . $shape . '"><a:avLst/></a:prstGeom><a:gradFill><a:gsLst><a:gs pos="0"><a:srgbClr val="' . $start . '"/></a:gs><a:gs pos="100000"><a:srgbClr val="' . $end . '"/></a:gs></a:gsLst><a:lin ang="0" scaled="1"/></a:gradFill><a:ln><a:noFill/></a:ln></p:spPr><p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody></p:sp>';
    }

    private function pptBadge(string $text, int $x, int $y, string $color): string
    {
        return $this->pptRect($x, $y, 620000, 260000, $this->pptTint($color, 0.88), $this->pptTint($color, 0.55))
            . $this->pptText($text, $x + 40000, $y + 45000, 540000, 150000, 10, $color, true);
    }

    private function pptFooter(): string
    {
        $theme = $this->pptTheme;
        $company = SiteSetting::get('company_name', 'VulnTrack') ?: 'VulnTrack';

        return $this->pptRect(430000, 6500000, 11330000, 35000, $theme['accent'], $theme['accent'], 'rect')
            . $this->pptRect(430000, 6380000, 120000, 120000, $theme['primary'], $theme['primary'], 'ellipse')
            . $this->pptText($company . ' | Vulnerability KRI Report', 610000, 6325000, 5200000, 220000, 10, $theme['muted'])
            . $this->pptText('Generated ' . now()->format('d M Y'), 9200000, 6325000, 2300000, 220000, 10, $theme['muted']);
    }

    private function pptSlideTitle(string $title, string $subtitle): string
    {
        $theme = $this->pptTheme;

        return $this->pptGradientRect(0, 0, 12192000, 820000, $theme['secondary'], $theme['primary'], 'rect')
            . $this->pptText($title, 430000, 220000, 6900000, 320000, 24, $theme['onDark'], true)
            . $this->pptText($subtitle, 430000, 900000, 9000000, 280000, 13, $theme['muted']);
    }

    private function pptChartPanel(string $title, string $note, string $relId, array $rows, int $x, int $y): string
    {
        $theme = $this->pptTheme;

        return $this->pptRect($x, $y, 5400000, 4350000, $theme['surface'], $theme['border'])
            . $this->pptText($title, $x + 280000, $y + 260000, 4200000, 280000, 18, $theme['secondary'], true)
            . $this->pptText($note, $x + 280000, $y + 610000, 4500000, 420000, 12, $theme['muted'])
            . $this->pptImage($relId, $title, $x + 390000, $y + 1150000, 2350000, 2350000)
            . $this->pptLegend($rows, $x + 2920000, $y + 1260000, 2050000);
    }

    private function pptLegend(array $rows, int $x, int $y, int $w): string
    {
        $total = array_sum(array_map(fn($row) => (int) $row[1], $rows));
        $shapes = '';

        foreach ($rows as $i => [$label, $value, $color]) {
            $rowY = $y + ($i * 430000);
            $pct = $total > 0 ? round(((int) $value / $total) * 100) : 0;
            $shapes .= $this->pptRect($x, $rowY + 35000, 170000, 170000, $color, $color, 'rect');
            $shapes .= $this->pptText($label, $x + 240000, $rowY, 1050000, 220000, 12, $this->pptTheme['secondary'], true);
            $shapes .= $this->pptText(number_format($value) . ' (' . $pct . '%)', $x + 1350000, $rowY, $w - 1350000, 220000, 12, $this->pptTheme['muted']);
        }

        return $shapes;
    }

    private function pptInsightBox(string $title, array $items, int $x, int $y, int $w, int $h): string
    {
        $theme = $this->pptTheme;
        $shapes = $this->pptRect($x, $y, $w, $h, $theme['surface'], $theme['border'])
            . $this->pptRect($x, $y, $w, 85000, $theme['accent'], $theme['accent'], 'rect')
            . $this->pptText($title, $x + 280000, $y + 210000, $w - 560000, 260000, 17, $theme['secondary'], true);

        foreach ($items as $i => $item) {
            $rowY = $y + 600000 + ($i * 300000);
            $shapes .= $this->pptRect($x + 300000, $rowY + 65000, 90000, 90000, $theme['primary'], $theme['primary'], 'ellipse');
            $shapes .= $this->pptText($item, $x + 470000, $rowY, $w - 760000, 230000, 12, $theme['muted']);
        }

        return $shapes;
    }

    private function pptImage(string $relId, string $name, int $x, int $y, int $w, int $h): string
    {
        $id = ++$this->pptShapeId;
        [$x, $y, $w, $h] = $this->pptScaleBox($x, $y, $w, $h);
        $safeName = $this->pptXml($name);

        return '<p:pic><p:nvPicPr><p:cNvPr id="' . $id . '" name="' . $safeName . '"/><p:cNvPicPr/><p:nvPr/></p:nvPicPr><p:blipFill><a:blip r:embed="' . $relId . '"/><a:stretch><a:fillRect/></a:stretch></p:blipFill><p:spPr><a:xfrm><a:off x="' . $x . '" y="' . $y . '"/><a:ext cx="' . $w . '" cy="' . $h . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></p:spPr></p:pic>';
    }

    private function pptKriChartData($stats, array $kri): array
    {
        $severity = $this->pptTheme['severity'];

        return [
            'severity' => [
                ['Critical', (int) ($stats->critical ?? 0), $severity['Critical']],
                ['High', (int) ($stats->high ?? 0), $severity['High']],
                ['Medium', (int) ($stats->medium ?? 0), $severity['Medium']],
                ['Low', (int) ($stats->low ?? 0), $severity['Low']],
            ],
            'workflow' => [
                ['Open', (int) ($kri['open_remediation'] ?? 0), $severity['Critical']],
                ['In Progress', (int) ($kri['in_progress'] ?? 0), $severity['Medium']],
                ['Accepted', (int) ($kri['accepted_risk'] ?? 0), $this->pptTheme['muted']],
                ['Resolved', (int) ($kri['resolved_by_scan'] ?? 0), $this->pptTheme['success']],
            ],
            'sla' => [
                ['Breached', (int) ($kri['sla_breached'] ?? 0), $severity['Critical']],
                ['Approaching', (int) ($kri['sla_approaching'] ?? 0), $severity['Medium']],
                ['On Track', (int) ($kri['sla_on_track'] ?? 0), $this->pptTheme['success']],
                ['Met', (int) ($kri['sla_met'] ?? 0), $this->pptTheme['accent']],
            ],
        ];
    }

    private function pptDoughnutChartPng(array $rows): string
    {
        abort_unless(extension_loaded('gd'), 500, 'PowerPoint chart export requires the PHP GD extension.');

        $size = 720;
        $image = imagecreatetruecolor($size, $size);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefilledrectangle($image, 0, 0, $size, $size, $transparent);
        imagealphablending($image, true);
        imageantialias($image, true);

        $total = array_sum(array_map(fn($row) => max(0, (int) $row[1]), $rows));
        $cx = (int) ($size / 2);
        $cy = (int) ($size / 2);
        $outer = 600;
        $inner = 310;

        if ($total <= 0) {
            $grey = imagecolorallocate($image, 226, 232, 240);
            imagefilledellipse($image, $cx, $cy, $outer, $outer, $grey);
        } else {
            $start = -90.0;
            foreach ($rows as [$label, $value, $hex]) {
                $value = max(0, (int) $value);
                if ($value === 0) {
                    continue;
                }

                $end = $start + (($value / $total) * 360.0);
                [$r, $g, $b] = $this->pptHexToRgb($hex);
                $color = imagecolorallocate($image, $r, $g, $b);
                imagefilledarc($image, $cx, $cy, $outer, $outer, (int) round($start), (int) round($end), $color, IMG_ARC_PIE);
                $start = $end;
            }
        }

        $hole = imagecolorallocate($image, 255, 255, 255);
        imagefilledellipse($image, $cx, $cy, $inner, $inner, $hole);
        $ring = imagecolorallocate($image, 226, 232, 240);
        imageellipse($image, $cx, $cy, $outer, $outer, $ring);
        imageellipse($image, $cx, $cy, $inner, $inner, $ring);

        $dark = imagecolorallocate($image, 30, 41, 59);
        $muted = imagecolorallocate($image, 100, 116, 139);
        $totalText = number_format($total);
        $labelText = 'Total';
        $boldFont = $this->pptChartFont(true);
        $regularFont = $this->pptChartFont();
        if ($boldFont && $regularFont && function_exists('imagettftext')) {
            $totalBox = imagettfbbox(42, 0, $boldFont, $totalText);
            $labelBox = imagettfbbox(18, 0, $regularFont, $labelText);
            imagettftext($image, 42, 0, (int) ($cx - (($totalBox[2] - $totalBox[0]) / 2)), $cy - 8, $dark, $boldFont, $totalText);
            imagettftext($image, 18, 0, (int) ($cx - (($labelBox[2] - $labelBox[0]) / 2)), $cy + 28, $muted, $regularFont, $labelText);
        } else {
            $totalFont = 5;
            $labelFont = 3;
            imagestring($image, $totalFont, (int) ($cx - (imagefontwidth($totalFont) * strlen($totalText) / 2)), $cy - 28, $totalText, $dark);
            imagestring($image, $labelFont, (int) ($cx - (imagefontwidth($labelFont) * strlen($labelText) / 2)), $cy + 8, $labelText, $muted);
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return $png ?: '';
    }

    private function pptHexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function pptChartFont(bool $bold = false): ?string
    {
        $fonts = $bold
            ? [
                'C:\Windows\Fonts\segoeuib.ttf',
                'C:\Windows\Fonts\arialbd.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
                '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf',
            ]
            : [
                'C:\Windows\Fonts\segoeui.ttf',
                'C:\Windows\Fonts\arial.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
            ];

        foreach ($fonts as $font) {
            if (is_file($font)) {
                return $font;
            }
        }

        return null;
    }

    private function pptThemeColors(): array
    {
        $primary = $this->pptSettingColor('theme_color', $this->pptSettingColor('theme_primary', '#2563EB'));
        $secondary = $this->pptSettingColor('theme_secondary_color', '#1E293B');
        $accent = $this->pptSettingColor('theme_accent_color', $this->pptSettingColor('report_accent_color', '#0EA5E9'));

        return [
            'primary'     => $primary,
            'secondary'   => $secondary,
            'accent'      => $accent,
            'background'  => $this->pptTint($primary, 0.94),
            'surface'     => 'FFFFFF',
            'border'      => $this->pptTint($secondary, 0.82),
            'muted'       => '64748B',
            'onDark'      => 'FFFFFF',
            'onDarkMuted' => 'DBEAFE',
            'success'     => '16A34A',
            'severity'    => [
                'Critical' => 'DC2626',
                'High'     => 'EA580C',
                'Medium'   => 'D97706',
                'Low'      => $primary,
                'Info'     => '64748B',
            ],
        ];
    }

    private function pptSettingColor(string $key, string $default): string
    {
        return $this->pptColor(SiteSetting::get($key, $default), $default);
    }

    private function pptColor(mixed $value, string $default): string
    {
        $value = (string) ($value ?: $default);
        $value = preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $default;

        return strtoupper(ltrim($value, '#'));
    }

    private function pptTint(string $hex, float $amount): string
    {
        [$r, $g, $b] = $this->pptHexToRgb($hex);
        $amount = max(0, min(1, $amount));

        return sprintf(
            '%02X%02X%02X',
            (int) round($r + ((255 - $r) * $amount)),
            (int) round($g + ((255 - $g) * $amount)),
            (int) round($b + ((255 - $b) * $amount)),
        );
    }

    private function pptContentTypes(int $slideCount): string
    {
        $slides = '';
        for ($i = 1; $i <= $slideCount; $i++) {
            $slides .= '<Override PartName="/ppt/slides/slide' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/><Override PartName="/ppt/presProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presProps+xml"/><Override PartName="/ppt/viewProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.viewProps+xml"/><Override PartName="/ppt/tableStyles.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.tableStyles+xml"/><Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/><Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/><Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>' . $slides . '</Types>';
    }

    private function pptRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function pptPresentationXml(int $slideCount): string
    {
        $ids = '';
        for ($i = 1; $i <= $slideCount; $i++) {
            $ids .= '<p:sldId id="' . (255 + $i) . '" r:id="rId' . $i . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" saveSubsetFonts="1"><p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rId' . ($slideCount + 1) . '"/></p:sldMasterIdLst><p:sldIdLst>' . $ids . '</p:sldIdLst><p:sldSz cx="12192000" cy="6858000" type="wide"/><p:notesSz cx="6858000" cy="9144000"/><p:defaultTextStyle><a:defPPr><a:defRPr lang="en-US"/></a:defPPr></p:defaultTextStyle></p:presentation>';
    }

    private function pptPresentationRels(int $slideCount): string
    {
        $rels = '';
        for ($i = 1; $i <= $slideCount; $i++) {
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide' . $i . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . ($slideCount + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>';
        $rels .= '<Relationship Id="rId' . ($slideCount + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/presProps" Target="presProps.xml"/>';
        $rels .= '<Relationship Id="rId' . ($slideCount + 3) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/viewProps" Target="viewProps.xml"/>';
        $rels .= '<Relationship Id="rId' . ($slideCount + 4) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>';
        $rels .= '<Relationship Id="rId' . ($slideCount + 5) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/tableStyles" Target="tableStyles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
    }

    private function pptPresPropsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:presentationPr xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:showPr showNarration="1"><p:present/></p:showPr></p:presentationPr>';
    }

    private function pptViewPropsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:viewPr xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:normalViewPr><p:restoredLeft sz="15620"/><p:restoredTop sz="94660"/></p:normalViewPr><p:slideViewPr><p:cSldViewPr><p:cViewPr varScale="1"><p:scale><a:sx n="100" d="100"/><a:sy n="100" d="100"/></p:scale><p:origin x="0" y="0"/></p:cViewPr><p:guideLst/></p:cSldViewPr></p:slideViewPr><p:notesTextViewPr><p:cViewPr><p:scale><a:sx n="100" d="100"/><a:sy n="100" d="100"/></p:scale><p:origin x="0" y="0"/></p:cViewPr></p:notesTextViewPr><p:gridSpacing cx="72008" cy="72008"/></p:viewPr>';
    }

    private function pptTableStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><a:tblStyleLst xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" def="{5C22544A-7EE6-4342-B048-85BDC9FD1C3A}"/>';
    }

    private function pptSlideRels(array $imageTargets = []): string
    {
        $rels = '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>';
        foreach (array_values($imageTargets) as $i => $target) {
            $rels .= '<Relationship Id="rId' . ($i + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="' . $this->pptXml($target) . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
    }

    private function pptTemplateContentTypes(\ZipArchive $template, int $slideCount, bool $includePng = false): string
    {
        $xml = $this->pptTemplatePart($template, '[Content_Types].xml');
        $xml = preg_replace('#<Override PartName="/ppt/slides/slide\d+\.xml"[^>]*/>#', '', $xml) ?? $xml;

        if ($includePng && !str_contains($xml, 'Extension="png"')) {
            $xml = preg_replace('/(<Types[^>]*>)/', '$1<Default Extension="png" ContentType="image/png"/>', $xml, 1) ?? $xml;
        }

        $slides = '';
        for ($i = 1; $i <= $slideCount; $i++) {
            $slides .= '<Override PartName="/ppt/slides/slide' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>';
        }

        return str_replace('</Types>', $slides . '</Types>', $xml);
    }

    private function pptTemplatePresentationXml(\ZipArchive $template, int $slideCount): string
    {
        $xml = $this->pptTemplatePart($template, 'ppt/presentation.xml');
        $xml = preg_replace('#<p:sldIdLst>.*?</p:sldIdLst>#s', '', $xml) ?? $xml;

        $ids = '';
        for ($i = 1; $i <= $slideCount; $i++) {
            $ids .= '<p:sldId id="' . (255 + $i) . '" r:id="rId' . (99 + $i) . '"/>';
        }

        return str_replace('</p:sldMasterIdLst>', '</p:sldMasterIdLst><p:sldIdLst>' . $ids . '</p:sldIdLst>', $xml);
    }

    private function pptTemplatePresentationRels(\ZipArchive $template, int $slideCount): string
    {
        $xml = $this->pptTemplatePart($template, 'ppt/_rels/presentation.xml.rels');
        $xml = preg_replace('#<Relationship Id="[^"]+" Type="http://schemas\.openxmlformats\.org/officeDocument/2006/relationships/slide" Target="slides/slide\d+\.xml"/>#', '', $xml) ?? $xml;

        $rels = '';
        for ($i = 1; $i <= $slideCount; $i++) {
            $rels .= '<Relationship Id="rId' . (99 + $i) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide' . $i . '.xml"/>';
        }

        return str_replace('</Relationships>', $rels . '</Relationships>', $xml);
    }

    private function pptTemplatePart(\ZipArchive $template, string $name): string
    {
        $contents = $template->getFromName($name);
        abort_unless($contents !== false, 500, "PowerPoint export template is missing {$name}.");

        return $contents;
    }

    private function pptShouldSkipTemplatePart(string $name): bool
    {
        return str_starts_with($name, 'ppt/slides/');
    }

    private function pptApplyTemplateScale(\ZipArchive $template): void
    {
        $xml = $this->pptTemplatePart($template, 'ppt/presentation.xml');
        if (preg_match('/<p:sldSz[^>]*cx="(\d+)"[^>]*cy="(\d+)"/', $xml, $matches)) {
            $this->pptScaleX = max(0.1, ((int) $matches[1]) / 12192000);
            $this->pptScaleY = max(0.1, ((int) $matches[2]) / 6858000);
            $this->pptFontScale = max(0.75, min(2.5, min($this->pptScaleX, $this->pptScaleY)));
            return;
        }

        $this->pptScaleX = 1.0;
        $this->pptScaleY = 1.0;
        $this->pptFontScale = 1.0;
    }

    private function pptScaleBox(int $x, int $y, int $w, int $h): array
    {
        return [
            (int) round($x * $this->pptScaleX),
            (int) round($y * $this->pptScaleY),
            (int) round($w * $this->pptScaleX),
            (int) round($h * $this->pptScaleY),
        ];
    }

    private function pptScalePoint(int $pt): int
    {
        return max(8, (int) round($pt * $this->pptFontScale));
    }

    private function pptSlideMasterXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:cSld><p:bg><p:bgPr><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:effectLst/></p:bgPr></p:bg><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld><p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/><p:sldLayoutIdLst><p:sldLayoutId id="2147483649" r:id="rId1"/></p:sldLayoutIdLst><p:txStyles><p:titleStyle/><p:bodyStyle/><p:otherStyle/></p:txStyles></p:sldMaster>';
    }

    private function pptSlideMasterRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="../theme/theme1.xml"/></Relationships>';
    }

    private function pptSlideLayoutXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="blank" preserve="1"><p:cSld name="Blank"><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sldLayout>';
    }

    private function pptSlideLayoutRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/></Relationships>';
    }

    private function pptThemeXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="VulnTrack"><a:themeElements><a:clrScheme name="VulnTrack"><a:dk1><a:srgbClr val="0F172A"/></a:dk1><a:lt1><a:srgbClr val="FFFFFF"/></a:lt1><a:dk2><a:srgbClr val="334155"/></a:dk2><a:lt2><a:srgbClr val="F8FAFC"/></a:lt2><a:accent1><a:srgbClr val="1D4ED8"/></a:accent1><a:accent2><a:srgbClr val="DC0000"/></a:accent2><a:accent3><a:srgbClr val="FD8C00"/></a:accent3><a:accent4><a:srgbClr val="16A34A"/></a:accent4><a:accent5><a:srgbClr val="0EA5E9"/></a:accent5><a:accent6><a:srgbClr val="64748B"/></a:accent6><a:hlink><a:srgbClr val="1D4ED8"/></a:hlink><a:folHlink><a:srgbClr val="475569"/></a:folHlink></a:clrScheme><a:fontScheme name="VulnTrack"><a:majorFont><a:latin typeface="Arial"/><a:ea typeface=""/><a:cs typeface=""/></a:majorFont><a:minorFont><a:latin typeface="Arial"/><a:ea typeface=""/><a:cs typeface=""/></a:minorFont></a:fontScheme><a:fmtScheme name="VulnTrack"><a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:gradFill rotWithShape="1"><a:gsLst><a:gs pos="0"><a:schemeClr val="phClr"/></a:gs><a:gs pos="100000"><a:schemeClr val="phClr"/></a:gs></a:gsLst><a:lin ang="5400000" scaled="0"/></a:gradFill><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:fillStyleLst><a:lnStyleLst><a:ln w="9525" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln><a:ln w="25400" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln><a:ln w="38100" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln></a:lnStyleLst><a:effectStyleLst><a:effectStyle><a:effectLst/></a:effectStyle><a:effectStyle><a:effectLst/></a:effectStyle><a:effectStyle><a:effectLst/></a:effectStyle></a:effectStyleLst><a:bgFillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:bgFillStyleLst></a:fmtScheme></a:themeElements><a:objectDefaults/><a:extraClrSchemeLst/></a:theme>';
    }

    private function pptAppXml(int $slideCount): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>VulnTrack</Application><PresentationFormat>Widescreen</PresentationFormat><Slides>' . $slideCount . '</Slides></Properties>';
    }

    private function pptCoreXml(string $title): string
    {
        $now = now()->toAtomString();
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>' . $this->pptXml($title) . ' KRI Report</dc:title><dc:creator>VulnTrack</dc:creator><cp:lastModifiedBy>VulnTrack</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified></cp:coreProperties>';
    }

    private function pptXml(string $value): string
    {
        $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    public function findings(Request $request, VulnAssessment $vulnAssessment)
    {
        $assessment = $vulnAssessment;
        $baseline   = $assessment->baselineScan();
        $latestScan = $assessment->latestScan() ?? $baseline;

        // Abort only if zero scans have ever been uploaded
        abort_unless($assessment->scans()->exists(), 404);

        $displaySeverities  = ['Critical', 'High', 'Medium', 'Low'];
        $unresolvedStatuses = ['Open', 'In Progress'];

        // â”€â”€ Base query: vuln_tracked (ALL scans, cumulative) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $query = VulnTracked::where('vuln_tracked.assessment_id', $assessment->id)
            ->whereIn('vuln_tracked.severity', $displaySeverities)
            ->select('vuln_tracked.*', 'vf.plugin_output')
            ->visibleTo(Auth::user());

        // Join latest finding to get plugin_output
        $query->leftJoin('vuln_findings as vf', function ($join) use ($assessment) {
            $join->on('vf.plugin_id',    '=', 'vuln_tracked.plugin_id')
                 ->on('vf.ip_address',   '=', 'vuln_tracked.ip_address')
                 ->on('vf.scan_id',      '=', 'vuln_tracked.last_scan_id')
                 ->where('vf.assessment_id', '=', $assessment->id);
        });

        // Subquery join: get system_name from assessment_scopes for this assessment, by IP
        $scopeSub = DB::table('assessment_scopes as s')
            ->join('vuln_assessment_scope as vas', 'vas.assessment_scope_id', '=', 's.id')
            ->where('vas.vuln_assessment_id', $assessment->id)
            ->select('s.ip_address', 's.system_name');

        $query->leftJoinSub($scopeSub, 'scope_ip', function ($join) {
            $join->on('scope_ip.ip_address', '=', 'vuln_tracked.ip_address');
        });

        $query->addSelect('scope_ip.system_name');

        // Join remediations for remediation-status filtering
        $query->leftJoin('vuln_remediations', function ($join) use ($assessment) {
            $join->on('vuln_remediations.plugin_id',  '=', 'vuln_tracked.plugin_id')
                 ->on('vuln_remediations.ip_address', '=', 'vuln_tracked.ip_address')
                 ->where('vuln_remediations.assessment_id', '=', $assessment->id);
        });

        // ── Tracking status filter ────────────────────────────────────────────
        // new | open | reopened | persistent | resolved | all | (default = all)
        $trackingFilter = $request->input('tracking');
        if ($trackingFilter === 'resolved') {
            $query->where('vuln_tracked.tracking_status', VulnTracked::STATUS_RESOLVED);
        } elseif ($trackingFilter === 'new') {
            $query->where('vuln_tracked.tracking_status', VulnTracked::STATUS_NEW);
        } elseif ($trackingFilter === 'open') {
            $query->where('vuln_tracked.tracking_status', VulnTracked::STATUS_OPEN);
        } elseif ($trackingFilter === 'reopened') {
            $query->where('vuln_tracked.tracking_status', VulnTracked::STATUS_REOPENED);
        } elseif ($trackingFilter === 'persistent') {
            $query->where('vuln_tracked.tracking_status', VulnTracked::STATUS_PERSISTENT);
        } elseif ($trackingFilter === 'all' || is_null($trackingFilter)) {
            // no filter — show all statuses by default
        } else {
            // Fallback: all active
            $query->whereIn('vuln_tracked.tracking_status', VulnTracked::openStatuses());
        }

        // â”€â”€ Standard filters â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ($request->filled('severity') && in_array($request->severity, $displaySeverities)) {
            $query->where('vuln_tracked.severity', $request->severity);
        }
        if ($request->filled('category') && in_array($request->category, VulnFinding::categories())) {
            $query->where('vuln_tracked.vuln_category', $request->category);
        }
        if ($request->filled('os_family') && in_array($request->os_family, ['Windows', 'Linux', 'Unix', 'Other'])) {
            $query->where('vuln_tracked.os_family', $request->os_family);
        }
        if ($request->filled('ip')) {
            $query->where('vuln_tracked.ip_address', $request->ip);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('vuln_tracked.vuln_name',  'like', "%$s%")
                  ->orWhere('vuln_tracked.ip_address','like', "%$s%")
                  ->orWhere('vuln_tracked.plugin_id', 'like', "%$s%")
                  ->orWhere('vuln_tracked.cve',       'like', "%$s%");
            });
        }

        // â”€â”€ Remediation status filter â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $remStatusFilter = $request->input('rem_status');
        if ($remStatusFilter === 'unresolved') {
            $query->where(function ($q) use ($unresolvedStatuses) {
                $q->whereNull('vuln_remediations.status')
                  ->orWhereIn('vuln_remediations.status', $unresolvedStatuses);
            });
        } elseif ($remStatusFilter && in_array($remStatusFilter, VulnRemediation::statuses())) {
            $query->where('vuln_remediations.status', $remStatusFilter);
        }

        $findings = $query
            ->orderByRaw("CASE vuln_tracked.tracking_status WHEN 'Persistent' THEN 1 WHEN 'Reopened' THEN 2 WHEN 'New' THEN 3 WHEN 'Open' THEN 4 WHEN 'Resolved' THEN 5 ELSE 6 END")
            ->orderByRaw("CASE vuln_tracked.severity WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 WHEN 'Low' THEN 4 ELSE 5 END")
            ->paginate(30)
            ->withQueryString();

        // â”€â”€ Remediations keyed for display â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $remediations = VulnRemediation::where('assessment_id', $assessment->id)
            ->with('assignedGroup.members')
            ->get()
            ->keyBy(fn($r) => $r->plugin_id . '|' . $r->ip_address);

        // â”€â”€ Remediation status counts (all tracking statuses included) â”€â”€â”€â”€â”€â”€â”€
        $remStatusCounts = VulnTracked::where('vuln_tracked.assessment_id', $assessment->id)
            ->whereIn('vuln_tracked.severity', $displaySeverities)
            ->leftJoin('vuln_remediations', function ($join) use ($assessment) {
                $join->on('vuln_remediations.plugin_id',  '=', 'vuln_tracked.plugin_id')
                     ->on('vuln_remediations.ip_address', '=', 'vuln_tracked.ip_address')
                     ->where('vuln_remediations.assessment_id', '=', $assessment->id);
            })
            ->selectRaw("COALESCE(vuln_remediations.status, 'Open') as rem_status, COUNT(*) as cnt")
            ->groupBy('rem_status')
            ->pluck('cnt', 'rem_status');

        // â”€â”€ Tracking status counts (for filter tab badges) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $trackingCounts = VulnTracked::where('assessment_id', $assessment->id)
            ->whereIn('severity', $displaySeverities)
            ->selectRaw('tracking_status, COUNT(*) as cnt')
            ->groupBy('tracking_status')
            ->pluck('cnt', 'tracking_status');

        $userGroups = UserGroup::orderBy('name')->get();
        $slaPolicy  = $assessment->slaPolicy
                   ?? SlaPolicy::where('is_default', true)->first();

        // â”€â”€ SLA status counts across all findings (not just current page) â”€â”€â”€
        $slaCounts = null;
        if ($slaPolicy) {
            $allTracked = VulnTracked::where('assessment_id', $assessment->id)
                ->whereIn('severity', $displaySeverities)
                ->get(['severity', 'first_seen_at', 'tracking_status', 'resolved_at']);

            $slaCounts = ['on-track' => 0, 'approaching' => 0, 'breached' => 0, 'met' => 0];
            foreach ($allTracked as $t) {
                [$status] = $slaPolicy->slaStatus(
                    $t->severity,
                    \Carbon\Carbon::parse($t->first_seen_at),
                    $t->tracking_status === 'Resolved',
                    $t->resolved_at ? \Carbon\Carbon::parse($t->resolved_at) : null
                );
                if (isset($slaCounts[$status])) {
                    $slaCounts[$status]++;
                }
            }
        }

        return view('vuln_assessments.findings', compact(
            'assessment', 'baseline', 'latestScan', 'findings', 'remediations',
            'trackingFilter', 'trackingCounts',
            'remStatusCounts', 'remStatusFilter', 'userGroups', 'slaPolicy', 'slaCounts'
        ));
    }

    public function vulnUpload(VulnAssessment $vulnAssessment)
    {
        $assessment = $vulnAssessment->load('scans.creator');

        $initialScans      = $assessment->scans->where('is_verification', false)->values();
        $verificationScans = $assessment->scans->where('is_verification', true)->values();

        $hasTracked = VulnTracked::where('assessment_id', $assessment->id)->exists();
        $trackingStats = null;
        if ($hasTracked) {
            $openIn = implode("','", VulnTracked::openStatuses());
            $trackingStats = VulnTracked::where('assessment_id', $assessment->id)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN tracking_status IN ('$openIn') THEN 1 ELSE 0 END) as open_count,
                    SUM(CASE WHEN tracking_status = 'Resolved'   THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN tracking_status = 'New'        THEN 1 ELSE 0 END) as new_count,
                    SUM(CASE WHEN tracking_status = 'Reopened'   THEN 1 ELSE 0 END) as reopened,
                    SUM(CASE WHEN tracking_status = 'Persistent' THEN 1 ELSE 0 END) as persistent
                ")->first();
        }

        return view('vuln_assessments.vuln_upload', compact(
            'assessment', 'initialScans', 'verificationScans', 'trackingStats'
        ));
    }

    public function uploadScan(Request $request, VulnAssessment $vulnAssessment)
    {
        $this->authorize('manage', $vulnAssessment);

        $request->validate([
            'scan_file'       => ['required', 'file', 'max:1048576'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'is_verification' => ['nullable', 'boolean'],
        ]);

        $assessment = $vulnAssessment;
        $file       = $request->file('scan_file');
        $filename   = $file->getClientOriginalName();
        $ext        = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['xml', 'nessus', 'csv', 'txt'], true)) {
            $msg = 'Unsupported file type ".' . $ext . '". Accepted: .nessus, .xml, .csv';
            return $request->wantsJson()
                ? response()->json(['errors' => ['scan_file' => $msg]], 422)
                : back()->withErrors(['scan_file' => $msg]);
        }

        $dupError = '"' . $filename . '" has already been uploaded to this assessment. '
                  . 'Rename the file or delete the existing scan before re-uploading.';

        if ($assessment->scans()->where('filename', $filename)
                ->whereIn('upload_status', ['pending', 'processing', 'completed'])->exists()) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => ['scan_file' => $dupError]], 422);
            }
            return back()->withErrors(['scan_file' => $dupError]);
        }

        $isBaseline = $assessment->scans()
            ->whereIn('upload_status', ['pending', 'processing', 'completed'])
            ->count() === 0;

        // Persist the file so the queue job can access it after the HTTP response.
        $path = $file->store('scan-uploads', 'local');

        $scan = VulnScan::create([
            'assessment_id'  => $assessment->id,
            'filename'       => $filename,
            'is_baseline'    => $isBaseline,
            'is_verification' => $request->boolean('is_verification'),
            'notes'          => $request->notes,
            'created_by'     => Auth::id(),
            'upload_status'  => 'pending',
            'file_path'      => $path,
        ]);

        try {
            ProcessScanUpload::dispatchSync($scan->id, $path, $ext);
        } catch (\Throwable $e) {
            // Scan status already set to 'failed' inside the job — just surface the error.
            if ($request->wantsJson()) {
                return response()->json(['errors' => ['scan_file' => 'Processing failed: ' . $e->getMessage()]], 500);
            }
            return back()->withErrors(['scan_file' => 'Processing failed: ' . $e->getMessage()]);
        }

        AuditLog::record('scan.uploaded', $assessment, ['filename' => $filename, 'is_baseline' => $isBaseline]);

        if ($request->wantsJson()) {
            return response()->json(['scan_id' => $scan->id, 'status' => 'pending']);
        }

        return redirect()->route('vuln-assessments.show', $assessment)
            ->with('success', '”' . $filename . '” uploaded and processed successfully.');
    }

    public function uploadStatus(VulnAssessment $vulnAssessment, VulnScan $scan): \Illuminate\Http\JsonResponse
    {
        abort_unless($scan->assessment_id === $vulnAssessment->id, 404);

        return response()->json([
            'status'   => $scan->upload_status,
            'message'  => $scan->upload_error,
            'redirect' => route('vuln-assessments.show', $vulnAssessment),
        ]);
    }

    // Accepts a single 5 MB slice of a file; assembles and queues when all chunks arrive.
    public function uploadChunk(Request $request, VulnAssessment $vulnAssessment): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', $vulnAssessment);

        $request->validate([
            'upload_id'    => ['required', 'string', 'regex:/^[a-f0-9\-]{36}$/i'],
            'chunk_index'  => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:210'],
            'filename'     => ['required', 'string', 'max:255'],
            'notes'        => ['nullable', 'string', 'max:1000'],
            'chunk'        => ['required', 'file'],
            'is_verification' => ['nullable', 'boolean'],
        ]);

        $uploadId    = $request->input('upload_id');
        $chunkIndex  = (int) $request->input('chunk_index');
        $totalChunks = (int) $request->input('total_chunks');
        $filename    = $request->input('filename');
        $chunkDir    = "chunks/{$uploadId}";

        // Early duplicate check on the first chunk so the browser can skip
        // the remaining chunks rather than uploading the whole file for nothing.
        if ($chunkIndex === 0) {
            $dupError = '"' . $filename . '" has already been uploaded to this assessment.';
            if ($vulnAssessment->scans()->where('filename', $filename)
                    ->whereIn('upload_status', ['pending', 'processing', 'completed'])->exists()) {
                return response()->json(['message' => $dupError], 422);
            }
        }

        $request->file('chunk')->storeAs($chunkDir, "chunk_{$chunkIndex}", 'local');

        $received = count(Storage::disk('local')->files($chunkDir));
        if ($received < $totalChunks) {
            return response()->json(['status' => 'chunk_received', 'received' => $received, 'total' => $totalChunks]);
        }

        // All chunks received - reassemble into a single file.
        $ext       = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $finalPath = 'scan-uploads/' . $uploadId . '.' . $ext;
        $fullPath  = Storage::disk('local')->path($finalPath);

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $out = fopen($fullPath, 'wb');
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = Storage::disk('local')->path($chunkDir . '/chunk_' . $i);
            if (!file_exists($chunkPath)) {
                fclose($out);
                @unlink($fullPath);
                Storage::disk('local')->deleteDirectory($chunkDir);
                return response()->json(['message' => "Assembly failed: chunk {$i} is missing. Please retry the upload."], 500);
            }
            $in = fopen($chunkPath, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);
        Storage::disk('local')->deleteDirectory($chunkDir);

        $assessment = $vulnAssessment;
        $dupError   = '"' . $filename . '" has already been uploaded to this assessment.';

        if ($assessment->scans()->where('filename', $filename)
                ->whereIn('upload_status', ['pending', 'processing', 'completed'])->exists()) {
            Storage::disk('local')->delete($finalPath);
            return response()->json(['message' => $dupError], 422);
        }

        $isBaseline = $assessment->scans()
            ->whereIn('upload_status', ['pending', 'processing', 'completed'])
            ->count() === 0;

        $scan = VulnScan::create([
            'assessment_id'  => $assessment->id,
            'filename'       => $filename,
            'is_baseline'    => $isBaseline,
            'is_verification' => (bool) $request->input('is_verification'),
            'notes'          => $request->input('notes'),
            'created_by'     => Auth::id(),
            'upload_status'  => 'pending',
            'file_path'      => $finalPath,
        ]);

        try {
            ProcessScanUpload::dispatchSync($scan->id, $finalPath, $ext);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Processing failed: ' . $e->getMessage()], 500);
        }

        return response()->json(['status' => 'queued', 'scan_id' => $scan->id]);
    }



    public function updateRemediation(
        AssignRemediationGroupRequest $request,
        VulnAssessment $vulnAssessment,
        VulnRemediation $remediation
    ): \Illuminate\Http\RedirectResponse {
        $this->authorize('manage', $vulnAssessment);
        abort_unless($remediation->assessment_id === $vulnAssessment->id, 403);

        // Only assigned_group_id ever reaches here; the Form Request strips
        // every other field (including status) before validation runs.
        $remediation->update([
            'assigned_group_id' => $request->validated('assigned_group_id'),
            'updated_by'        => Auth::id(),
        ]);

        return back()->with('success', 'Group assigned successfully.');
    }

    public function bulkUpdateRemediation(Request $request, VulnAssessment $vulnAssessment): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('manage', $vulnAssessment);

        $data = $request->validate([
            'finding_ids'       => ['required', 'string'],
            // '__clear__' removes the group; any other non-empty value must be a real group ID.
            'assigned_group_id' => ['required', 'string'],
        ]);

        $ids = array_filter(array_map('intval', explode(',', $data['finding_ids'])));
        if (empty($ids)) {
            return back()->with('error', 'No findings selected.');
        }

        $rawGroupId = $data['assigned_group_id'];

        if ($rawGroupId === '__clear__') {
            $groupId = null;
        } elseif (ctype_digit($rawGroupId)) {
            $groupId = (int) $rawGroupId;
            abort_unless(UserGroup::where('id', $groupId)->exists(), 422);
        } else {
            // Empty string = “No Group” placeholder selected; nothing to do.
            return back();
        }

        $tracked = VulnTracked::whereIn('id', $ids)
            ->where('assessment_id', $vulnAssessment->id)
            ->get(['id', 'plugin_id', 'ip_address']);

        $count = 0;
        foreach ($tracked as $t) {
            $rem = VulnRemediation::firstOrCreate(
                [
                    'assessment_id' => $vulnAssessment->id,
                    'plugin_id'     => $t->plugin_id,
                    'ip_address'    => $t->ip_address,
                ],
                ['status' => 'Open']
            );

            // Status is intentionally excluded; only group assignment is permitted.
            $rem->update([
                'assigned_group_id' => $groupId,
                'updated_by'        => Auth::id(),
            ]);

            $count++;
        }

        $verb = $groupId ? 'assigned' : 'unassigned';
        return back()->with('success', 'Group ' . $verb . ' for ' . $count . ' finding(s).');
    }

    /**
     * Re-run VulnClassifier on findings that have no category (or all, if force=true).
     * Works across every scan in the assessment so historical scans are also fixed.
     */
    public function reclassify(Request $request, VulnAssessment $vulnAssessment)
    {
        $this->authorize('manage', $vulnAssessment);

        $forceAll = $request->boolean('force', false);

        $query = VulnFinding::where('assessment_id', $vulnAssessment->id);

        if (!$forceAll) {
            $query->whereNull('vuln_category');
        }

        $updated  = 0;
        $skipped  = 0;

        // Chunk to avoid loading all findings into memory at once
        $query->select([
            'id', 'vuln_name', 'description', 'os_detected',
            'port', 'protocol', 'plugin_output', 'cve',
        ])->chunkById(200, function ($chunk) use (&$updated, &$skipped) {
            foreach ($chunk as $finding) {
                $result = VulnClassifier::classify(
                    $finding->vuln_name   ?? '',
                    $finding->description ?? '',
                    $finding->os_detected ?? '',
                    $finding->port        ?? '',
                    $finding->protocol    ?? '',
                    $finding->plugin_output ?? '',
                    $finding->cve         ?? ''
                );

                // Only write if the classifier returned something meaningful
                if ($result['category'] !== 'Other' || $result['affected_component'] !== null) {
                    VulnFinding::where('id', $finding->id)->update([
                        'vuln_category'      => $result['category'],
                        'affected_component' => $result['affected_component'],
                    ]);
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        });

        $total = $updated + $skipped;
        $msg   = "Auto-classified {$updated} of {$total} findings.";
        if ($skipped > 0) {
            $msg .= " {$skipped} could not be classified (marked 'Other') â€” review manually.";
        }

        return back()->with('success', $msg);
    }

    // ─── Vulnerable Hosts: edit scope / delete tracking ──────────

    public function updateHost(Request $request, VulnAssessment $vulnAssessment, string $ip)
    {
        $this->authorize('update', $vulnAssessment);
        abort_unless($vulnAssessment->scope_group_id, 422, 'No scope group linked to this assessment.');

        $data = $request->validate([
            'hostname'           => ['nullable', 'string', 'max:255'],
            'system_name'        => ['nullable', 'string', 'max:255'],
            'system_criticality' => ['nullable', 'integer', 'between:1,5'],
            'system_owner'       => ['nullable', 'string', 'max:100'],
            'identified_scope'   => ['nullable', 'in:PCI,Swift,Non-Bank,Public,Critical,Less Critical'],
            'environment'        => ['nullable', 'in:PROD,UAT,STAGE,DR,DEV,Non-Prod,DEV-QA'],
            'remediation_sla'    => ['nullable', 'in:Priority Level 1,Priority Level 2,Priority Level 3,Priority Level 4'],
        ]);

        \App\Models\AssessmentScope::updateOrCreate(
            ['group_id' => $vulnAssessment->scope_group_id, 'ip_address' => $ip],
            array_merge($data, ['created_by' => Auth::id()])
        );

        return back()->with('success', 'Host updated.');
    }

    public function destroyHost(VulnAssessment $vulnAssessment, string $ip)
    {
        $this->authorize('update', $vulnAssessment);

        VulnTracked::where('assessment_id', $vulnAssessment->id)
            ->where('ip_address', $ip)
            ->delete();

        return back()->with('success', 'Host removed from tracking.');
    }

    // ─────────────────────────────────────────────────────────────

    public function destroyScan(VulnAssessment $vulnAssessment, VulnScan $scan)
    {
        $this->authorize('update', $vulnAssessment);
        abort_if($scan->assessment_id !== $vulnAssessment->id, 403);

        // Capture affected IPs before deletion for asset count recalculation
        $affectedIps = VulnFinding::where('scan_id', $scan->id)
            ->distinct()
            ->pluck('ip_address')
            ->filter()
            ->values()
            ->toArray();

        $filename = $scan->filename;
        $filePath = $scan->file_path;

        DB::transaction(function () use ($scan, $vulnAssessment) {
            // Remove vuln_tracked rows that reference this scan's ID directly
            // (no ON DELETE CASCADE on first_scan_id / last_scan_id columns)
            DB::table('vuln_tracked_history')
                ->where('scan_id', $scan->id)
                ->delete();

            DB::table('vuln_tracked')
                ->where('assessment_id', $vulnAssessment->id)
                ->where(fn ($q) => $q
                    ->where('first_scan_id', $scan->id)
                    ->orWhere('last_scan_id', $scan->id)
                )
                ->delete();

            // Delete scan — cascades vuln_findings, vuln_host_os, vuln_remediations
            $scan->delete();
        });

        // Delete uploaded file from storage if it still exists
        if ($filePath && Storage::disk('local')->exists($filePath)) {
            Storage::disk('local')->delete($filePath);
        }

        // Recalculate asset_inventories vuln counts for affected IPs
        if (!empty($affectedIps)) {
            $counts = DB::table('vuln_findings')
                ->whereIn('ip_address', $affectedIps)
                ->selectRaw('ip_address, severity, COUNT(*) as cnt')
                ->groupBy('ip_address', 'severity')
                ->get()
                ->groupBy('ip_address');

            foreach ($affectedIps as $ip) {
                $c = $counts->get($ip, collect());
                DB::table('asset_inventories')->where('ip_address', $ip)->update([
                    'vuln_critical' => $c->where('severity', 'Critical')->sum('cnt'),
                    'vuln_high'     => $c->where('severity', 'High')->sum('cnt'),
                    'vuln_medium'   => $c->where('severity', 'Medium')->sum('cnt'),
                    'vuln_low'      => $c->where('severity', 'Low')->sum('cnt'),
                    'updated_at'    => now(),
                ]);
            }
        }

        return back()->with('success', "Scan \"{$filename}\" and all its findings have been deleted.");
    }

    // ─────────────────────────────────────────────────────────────

    public function destroy(VulnAssessment $vulnAssessment)
    {
        $this->authorize('delete', $vulnAssessment);

        AuditLog::record('assessment.deleted', null, ['id' => $vulnAssessment->id, 'name' => $vulnAssessment->name]);

        DB::transaction(function () use ($vulnAssessment) {
            // vuln_tracked.first_scan_id / last_scan_id and vuln_tracked_history.scan_id
            // reference vuln_scans without ON DELETE CASCADE, so we must remove tracked
            // rows before the assessment cascade reaches vuln_scans.
            // Deleting vuln_tracked cascades vuln_tracked_history automatically.
            DB::table('vuln_tracked')->where('assessment_id', $vulnAssessment->id)->delete();

            // All blocking scan references are now gone.
            // cascadeOnDelete on vuln_scans, vuln_findings, vuln_remediations,
            // vuln_host_os, and vuln_assessment_scope handles the rest.
            $vulnAssessment->delete();
        });

        return redirect()->route('vuln-assessments.index')
            ->with('success', 'Assessment deleted.');
    }

    public function updateScopeGroup(Request $request, VulnAssessment $vulnAssessment)
    {
        $this->authorize('manage', $vulnAssessment);

        $data = $request->validate([
            'scope_group_id' => ['nullable', 'integer', 'exists:assessment_scope_groups,id'],
        ]);

        $vulnAssessment->update(['scope_group_id' => $data['scope_group_id'] ?? null]);

        return back()->with('success', 'Scope group updated.');
    }

    // â”€â”€ Reports â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private function csvSafe(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        // Prefix formula-injection characters so spreadsheet apps don't execute them
        if (in_array($value[0] ?? '', ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }
        return $value;
    }

    private function buildReportData(VulnAssessment $a): array
    {
        $user = Auth::user();

        $active = VulnTracked::where('assessment_id', $a->id)
            ->whereIn('tracking_status', VulnTracked::openStatuses())
            ->visibleTo($user)
            ->selectRaw("severity, COUNT(*) as cnt")
            ->groupBy('severity')->pluck('cnt', 'severity');

        $resolved = VulnTracked::where('assessment_id', $a->id)
            ->where('tracking_status', 'Resolved')
            ->visibleTo($user)
            ->selectRaw("severity, COUNT(*) as cnt")
            ->groupBy('severity')->pluck('cnt', 'severity');

        $topHosts = VulnTracked::where('assessment_id', $a->id)
            ->whereIn('tracking_status', VulnTracked::openStatuses())
            ->visibleTo($user)
            ->selectRaw("ip_address, hostname, COUNT(*) as total,
                SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as c,
                SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as h,
                SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as m,
                SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as l")
            ->groupBy('ip_address', 'hostname')
            ->orderByDesc('c')->orderByDesc('h')->orderByDesc('m')
            ->limit(20)->get();

        $findings = VulnTracked::where('assessment_id', $a->id)
            ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
            ->whereIn('tracking_status', VulnTracked::openStatuses())
            ->visibleTo($user)
            ->orderByRaw("CASE severity WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 WHEN 'Low' THEN 4 ELSE 5 END")
            ->orderBy('ip_address')
            ->get(['vuln_name', 'severity', 'ip_address', 'hostname', 'port', 'protocol', 'cve', 'tracking_status', 'first_seen_at']);

        return compact('active', 'resolved', 'topHosts', 'findings');
    }

    public function reportPdf(VulnAssessment $vulnAssessment)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $a    = $vulnAssessment->load('creator', 'scans', 'scopeEntries');
        $data = array_merge($this->buildReportData($a), $this->buildDetailedReportData($a), $this->buildReportMeta());

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                    'vuln_assessments.report_pdf',
                    array_merge(['a' => $a], $data)
                )
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isRemoteEnabled'         => false,
                    'isHtml5ParserEnabled'    => true,
                    'isFontSubsettingEnabled' => true,
                    'defaultMediaType'        => 'print',
                    'dpi'                     => 96,
                    'defaultFont'             => 'dejavu sans',
                ]);

        $filename = str()->slug($a->name) . '_report_' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    public function reportWord(VulnAssessment $vulnAssessment)
    {
        $a    = $vulnAssessment->load('creator', 'scans', 'scopeEntries');
        $data = array_merge($this->buildReportData($a), $this->buildDetailedReportData($a), $this->buildReportMeta());
        $html = view('vuln_assessments.report_word', array_merge(['a' => $a], $data))->render();

        $filename = str()->slug($a->name) . '_report_' . now()->format('Ymd') . '.doc';
        return response($html, 200, [
            'Content-Type'        => 'application/msword',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /** Reads customisable report header/footer settings from SiteSettings. */
    private function buildReportMeta(): array
    {
        $get = fn(string $key, string $default) => \App\Models\SiteSetting::get($key) ?: $default;

        $accentHex = $get('report_accent_color', '#84cc16');
        // Derive a darker shade for text uses
        $h  = ltrim($accentHex, '#');
        $ar = hexdec(substr($h, 0, 2)); $ag = hexdec(substr($h, 2, 2)); $ab = hexdec(substr($h, 4, 2));
        $accentDark = sprintf('#%02x%02x%02x', (int)($ar * 0.7), (int)($ag * 0.7), (int)($ab * 0.7));

        return [
            'rpt_company'         => $get('report_company',         config('app.name', 'Security Assessment')),
            'rpt_confidentiality' => $get('report_confidentiality', 'Confidential â€” Internal Use Only'),
            'rpt_prepared_by'     => $get('report_prepared_by',     'Vulnerability Management Team'),
            'rpt_tool'            => $get('report_tool',            'Tenable Nessus'),
            'rpt_footer'          => $get('report_footer_text',     ''),
            'rpt_disclaimer'      => $get('report_disclaimer',      'This document contains confidential and proprietary information. It is intended solely for authorised personnel. Any reproduction, distribution, or disclosure without prior written approval is strictly prohibited.'),
            'rpt_accent'          => $accentHex,
            'rpt_accent_dark'     => $accentDark,
        ];
    }

    /** Builds the rich grouped data shared by both PDF and Word report generators. */
    private function buildDetailedReportData(VulnAssessment $a): array
    {
        $user = Auth::user();

        // Findings grouped: severity â†’ plugin_id â†’ [vuln info + affected hosts list]
        $rawFindings = VulnTracked::where('assessment_id', $a->id)
            ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
            ->visibleTo($user)
            ->orderByRaw("CASE severity WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 WHEN 'Low' THEN 4 ELSE 5 END")
            ->orderByDesc('cvss_score')
            ->orderBy('vuln_name')
            ->orderBy('ip_address')
            ->get();

        $findingsBySeverity = [];
        foreach ($rawFindings as $f) {
            $sev = $f->severity;
            $pid = $f->plugin_id;
            if (!isset($findingsBySeverity[$sev][$pid])) {
                $findingsBySeverity[$sev][$pid] = [
                    'vuln_name'       => $f->vuln_name,
                    'severity'        => $f->severity,
                    'cvss_score'      => $f->cvss_score,
                    'plugin_id'       => $f->plugin_id,
                    'cve'             => $f->cve,
                    'description'     => $f->description ? mb_substr(strip_tags($f->description), 0, 1500) : null,
                    'remediation_text'=> $f->remediation_text ? mb_substr(strip_tags($f->remediation_text), 0, 800) : null,
                    'hosts'           => [],
                ];
            }
            $findingsBySeverity[$sev][$pid]['hosts'][] = [
                'ip_address'     => $f->ip_address,
                'hostname'       => $f->hostname,
                'port'           => $f->port,
                'protocol'       => $f->protocol,
                'tracking_status'=> $f->tracking_status,
                'first_seen_at'  => $f->first_seen_at,
                'last_seen_at'   => $f->last_seen_at,
            ];
        }

        // Per-host summary with open/closed breakdown
        $hostsSummary = VulnTracked::where('assessment_id', $a->id)
            ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
            ->visibleTo($user)
            ->selectRaw("ip_address, hostname, os_name,
                SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as c,
                SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as h,
                SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as m,
                SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as l,
                COUNT(*) as total,
                SUM(CASE WHEN tracking_status='Resolved'          THEN 1 ELSE 0 END) as closed_count,
                SUM(CASE WHEN tracking_status IN ('New','Open','Unresolved','Reopened') THEN 1 ELSE 0 END) as open_count")
            ->groupBy('ip_address', 'hostname', 'os_name')
            ->orderByDesc('c')->orderByDesc('h')->orderByDesc('m')
            ->get();

        return compact('findingsBySeverity', 'hostsSummary');
    }

    public function reportExcel(VulnAssessment $vulnAssessment)
    {
        $a    = $vulnAssessment->load('creator');
        $user = Auth::user();

        $findings = VulnTracked::where('vuln_tracked.assessment_id', $a->id)
            ->whereIn('vuln_tracked.severity', ['Critical', 'High', 'Medium', 'Low'])
            ->visibleTo($user)
            ->select([
                'vuln_tracked.plugin_id',
                'vuln_tracked.vuln_name',
                'vuln_tracked.severity',
                'vuln_tracked.cvss_score',
                'vuln_tracked.cve',
                'vuln_tracked.vuln_category',
                'vuln_tracked.affected_component',
                'vuln_tracked.ip_address',
                'vuln_tracked.hostname',
                'vuln_tracked.port',
                'vuln_tracked.protocol',
                'vuln_tracked.os_detected',
                'vuln_tracked.os_name',
                'vuln_tracked.os_family',
                'vuln_tracked.description',
                'vuln_tracked.remediation_text',
                'vuln_tracked.tracking_status',
                'vuln_tracked.first_seen_at',
                'vuln_tracked.last_seen_at',
                DB::raw("(
                    SELECT vf.plugin_output
                    FROM   vuln_findings vf
                    WHERE  vf.scan_id    = vuln_tracked.last_scan_id
                      AND  vf.plugin_id  = vuln_tracked.plugin_id
                      AND  vf.ip_address = vuln_tracked.ip_address
                    LIMIT 1
                ) as plugin_output"),
            ])
            ->orderByRaw("CASE vuln_tracked.severity WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 WHEN 'Low' THEN 4 ELSE 5 END")
            ->orderBy('vuln_tracked.ip_address')
            ->orderBy('vuln_tracked.vuln_name')
            ->get();

        $filename = str()->slug($a->name) . '_report_' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($a, $findings) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens with correct encoding
            fwrite($out, "\xEF\xBB\xBF");

            // ── Assessment Summary ────────────────────────────────────
            fputcsv($out, ['Assessment Report', $a->name]);
            fputcsv($out, ['Generated',         now()->format('d M Y H:i')]);
            fputcsv($out, ['Period',             ($a->period_start?->format('d M Y') ?? '-') . ' to ' . ($a->period_end?->format('d M Y') ?? '-')]);
            fputcsv($out, ['Environment',        $a->environment ?? '-']);
            fputcsv($out, ['Scanner',            $a->scanner_type ?? '-']);
            fputcsv($out, ['Total Findings',     $findings->count()]);
            fputcsv($out, ['Critical',           $findings->where('severity', 'Critical')->count()]);
            fputcsv($out, ['High',               $findings->where('severity', 'High')->count()]);
            fputcsv($out, ['Medium',             $findings->where('severity', 'Medium')->count()]);
            fputcsv($out, ['Low',                $findings->where('severity', 'Low')->count()]);
            fputcsv($out, []);

            // ── Column Headers ────────────────────────────────────────
            fputcsv($out, [
                'Plugin ID',
                'Vulnerability Name',
                'Severity',
                'CVSS Score',
                'CVE',
                'Category',
                'Affected Component',
                'IP Address',
                'Hostname',
                'Port',
                'Protocol',
                'OS Detected',
                'OS Name',
                'OS Family',
                'Status',
                'First Seen',
                'Last Seen',
                'Description',
                'Remediation',
                'Plugin Output',
            ]);

            // ── Rows ──────────────────────────────────────────────────
            foreach ($findings as $f) {
                $status = match ($f->tracking_status) {
                    'Resolved'              => 'Resolved',
                    'Unresolved', 'Reopened',
                    'Pending'               => 'In-Progress',
                    default                 => 'Open',
                };

                fputcsv($out, [
                    $this->csvSafe($f->plugin_id),
                    $this->csvSafe($f->vuln_name),
                    $this->csvSafe($f->severity),
                    $f->cvss_score !== null ? number_format((float)$f->cvss_score, 1) : '',
                    $this->csvSafe($f->cve),
                    $this->csvSafe($f->vuln_category),
                    $this->csvSafe($f->affected_component),
                    $this->csvSafe($f->ip_address),
                    $this->csvSafe($f->hostname),
                    $this->csvSafe($f->port),
                    $this->csvSafe($f->protocol),
                    $this->csvSafe($f->os_detected),
                    $this->csvSafe($f->os_name),
                    $this->csvSafe($f->os_family),
                    $status,
                    $f->first_seen_at?->format('d M Y') ?? '',
                    $f->last_seen_at?->format('d M Y') ?? '',
                    $this->csvSafe($f->description),
                    $this->csvSafe($f->remediation_text),
                    $this->csvSafe($f->plugin_output),
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    //â”€â”€ OS Assets + Override â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function osAssets(Request $request, VulnAssessment $vulnAssessment)
    {
        $assessment = $vulnAssessment;

        $query = VulnHostOs::where('assessment_id', $assessment->id)
            ->with('overrideBy');

        if ($request->filled('family')) {
            $family = $request->family;
            $query->where(function ($q) use ($family) {
                $q->where('os_override_family', $family)
                  ->orWhere(function ($q2) use ($family) {
                      $q2->whereNull('os_override_family')->where('os_family', $family);
                  });
            });
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ip_address', 'like', "%$s%")
                  ->orWhere('hostname', 'like', "%$s%")
                  ->orWhere('os_name', 'like', "%$s%")
                  ->orWhere('os_override', 'like', "%$s%");
            });
        }

        $hosts = $query->orderByDesc('os_confidence')->paginate(30)->withQueryString();

        $osDistribution = VulnHostOs::where('assessment_id', $assessment->id)
            ->selectRaw("COALESCE(os_override_family, os_family) as family, COUNT(*) as cnt")
            ->groupBy('family')
            ->orderByDesc('cnt')
            ->get();

        return view('vuln_assessments.os_assets', compact('assessment', 'hosts', 'osDistribution'));
    }

    public function osOverride(Request $request, VulnAssessment $vulnAssessment, VulnHostOs $hostOs)
    {
        $this->authorize('manage', $vulnAssessment);
        abort_unless($hostOs->assessment_id === $vulnAssessment->id, 403);

        $data = $request->validate([
            'os_override'        => ['nullable', 'string', 'max:255'],
            'os_override_family' => ['nullable', 'in:Windows,Linux,Unix,Other'],
            'os_override_note'   => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['os_override']) {
            $hostOs->update([
                'os_override'        => $data['os_override'],
                'os_override_family' => $data['os_override_family'] ?? $hostOs->os_family,
                'os_override_note'   => $data['os_override_note'],
                'os_override_by'     => Auth::id(),
                'os_override_at'     => now(),
            ]);
        } else {
            // Clear override
            $hostOs->update([
                'os_override'        => null,
                'os_override_family' => null,
                'os_override_note'   => null,
                'os_override_by'     => null,
                'os_override_at'     => null,
            ]);
        }

        return back()->with('success', 'OS override saved.');
    }

    // â”€â”€ Progress â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function progress(VulnAssessment $vulnAssessment)
    {
        $assessment = $vulnAssessment;
        $scans      = $assessment->scans()->orderBy('id')->get();

        abort_unless($scans->isNotEmpty(), 404);

        $severities = ['Critical', 'High', 'Medium', 'Low'];

        // Per-scan severity counts (one query per scan â€” typically < 10 scans)
        $scanLabels     = [];
        $severityTrend  = array_fill_keys($severities, []);

        foreach ($scans as $scan) {
            $prefix = $scan->is_baseline ? 'Baseline' : 'Scan #' . $scan->id;
            $scanLabels[] = $prefix . ' (' . $scan->created_at->format('d M Y') . ')';

            $counts = VulnFinding::where('scan_id', $scan->id)
                ->whereIn('severity', $severities)
                ->selectRaw('severity, COUNT(*) as cnt')
                ->groupBy('severity')
                ->pluck('cnt', 'severity');

            foreach ($severities as $sev) {
                $severityTrend[$sev][] = (int) ($counts[$sev] ?? 0);
            }
        }

        // Current tracking status distribution
        $trackingCounts = VulnTracked::where('assessment_id', $assessment->id)
            ->whereIn('severity', $severities)
            ->selectRaw('tracking_status, COUNT(*) as cnt')
            ->groupBy('tracking_status')
            ->pluck('cnt', 'tracking_status');

        // Remediation status distribution
        $remCounts = VulnRemediation::where('assessment_id', $assessment->id)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        // Severity distribution (current state from vuln_tracked)
        $currentSevCounts = VulnTracked::where('assessment_id', $assessment->id)
            ->whereIn('severity', $severities)
            ->selectRaw('severity, COUNT(*) as cnt')
            ->groupBy('severity')
            ->pluck('cnt', 'severity');

        // Per-scan Ã— remediation status trend (x = scan, lines = rem status)
        $remStatuses = ['Open', 'In Progress', 'Resolved', 'Accepted Risk'];

        // For each scan: count findings joined with their current remediation status
        $scanRemTrend = array_fill_keys($remStatuses, []);

        foreach ($scans as $scan) {
            $counts = DB::table('vuln_findings as vf')
                ->where('vf.scan_id', $scan->id)
                ->whereIn('vf.severity', $severities)
                ->leftJoin('vuln_remediations as vr', function ($join) use ($assessment) {
                    $join->on('vr.plugin_id',      '=', 'vf.plugin_id')
                         ->on('vr.ip_address',     '=', 'vf.ip_address')
                         ->where('vr.assessment_id', '=', $assessment->id);
                })
                ->selectRaw("COALESCE(vr.status, 'Open') as rem_status, COUNT(*) as cnt")
                ->groupBy('rem_status')
                ->pluck('cnt', 'rem_status');

            foreach ($remStatuses as $status) {
                $scanRemTrend[$status][] = (int) ($counts[$status] ?? 0);
            }
        }

        // Vulnerability status by system owner (from asset_inventories)
        $groupStatusRaw = DB::table('vuln_tracked as vt')
            ->where('vt.assessment_id', $assessment->id)
            ->whereIn('vt.severity', ['Critical', 'High', 'Medium', 'Low'])
            ->leftJoin('vuln_remediations as vr', function ($j) use ($assessment) {
                $j->on('vr.plugin_id',  '=', 'vt.plugin_id')
                  ->on('vr.ip_address', '=', 'vt.ip_address')
                  ->where('vr.assessment_id', '=', $assessment->id);
            })
            ->leftJoin('asset_inventories as ai', 'ai.ip_address', '=', 'vt.ip_address')
            ->selectRaw("COALESCE(NULLIF(ai.system_owner, ''), 'Unassigned') as group_name,
                         COALESCE(vr.status, 'Open') as status,
                         COUNT(*) as cnt")
            ->groupBy('group_name', 'status')
            ->orderBy('group_name')
            ->get();

        $groupNames   = $groupStatusRaw->pluck('group_name')->unique()->values()->toArray();
        $groupStatData = array_fill_keys($remStatuses, []);

        foreach ($remStatuses as $status) {
            foreach ($groupNames as $group) {
                $row = $groupStatusRaw->first(fn($r) => $r->group_name === $group && $r->status === $status);
                $groupStatData[$status][] = $row ? (int) $row->cnt : 0;
            }
        }

        // Summary totals
        $totalTracked  = $trackingCounts->sum();
        $totalResolved = (int) ($trackingCounts[VulnTracked::STATUS_RESOLVED] ?? 0);
        $totalOpen     = $totalTracked - $totalResolved;

        return view('vuln_assessments.progress', compact(
            'assessment', 'scans',
            'scanLabels', 'severityTrend',
            'trackingCounts', 'remCounts', 'currentSevCounts',
            'scanRemTrend', 'remStatuses',
            'groupNames', 'groupStatData',
            'totalTracked', 'totalResolved', 'totalOpen'
        ));
    }
}
