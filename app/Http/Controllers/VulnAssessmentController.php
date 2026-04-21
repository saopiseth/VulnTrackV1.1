<?php

namespace App\Http\Controllers;

use App\Models\AssessmentScope;
use App\Models\AssessmentScopeGroup;
use App\Models\VulnAssessment;
use App\Models\VulnFinding;
use App\Models\VulnHostOs;
use App\Models\VulnRemediation;
use App\Models\VulnScan;
use App\Models\VulnTracked;
use App\Models\VulnTrackedHistory;
use App\Services\OsDetector;
use App\Services\VulnClassifier;
use App\Services\VulnTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VulnAssessmentController extends Controller
{
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

        return view('vuln_assessments.create', compact('scopeGroups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'period_start' => ['nullable', 'date'],
            'period_end'   => ['nullable', 'date', 'after_or_equal:period_start'],
            'environment'  => ['nullable', 'in:Production,UAT,Internal,Development'],
            'scanner_type' => ['nullable', 'string', 'max:255'],
            'scope_group_id' => ['nullable', 'integer', 'exists:assessment_scope_groups,id'],
        ]);

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

        // ── Stats from vuln_tracked (cumulative across ALL scans) ─────────
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
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as low
                ")->first();

            // ALL IPs ever seen — no tracking_status filter so Resolved IPs stay visible.
            // Scope data loaded separately (no fan-out join).
            $openIn = implode("','", $openStatuses);
            $topIps = VulnTracked::where('assessment_id', $assessment->id)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
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

            // Scope metadata — look up by the assessment's scope_group_id, keyed by ip_address
            $scopeByIp = collect();
            if ($assessment->scope_group_id) {
                $scopeByIp = DB::table('assessment_scopes')
                    ->where('group_id', $assessment->scope_group_id)
                    ->whereNotNull('ip_address')
                    ->select('ip_address', 'system_name', 'system_criticality', 'system_owner', 'identified_scope')
                    ->get()
                    ->keyBy('ip_address');
            }

            $topIps = $topIps->map(function ($row) use ($scopeByIp) {
                $scope = $scopeByIp->get($row->ip_address);
                $row->system_name        = $scope?->system_name;
                $row->system_criticality = $scope?->system_criticality;
                $row->system_owner       = $scope?->system_owner;
                $row->identified_scope   = $scope?->identified_scope;
                return $row;
            });

        } elseif ($assessment->scans->isNotEmpty()) {
            // Fallback: no tracked data yet — aggregate across ALL uploaded scans
            $allScanIds = $assessment->scans->pluck('id');

            $stats = VulnFinding::whereIn('scan_id', $allScanIds)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as low
                ")->first();

            $topIps = VulnFinding::whereIn('scan_id', $allScanIds)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
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

        // ── Scan-level comparison (vuln_tracked_history for latest scan) ───
        $hostComparison = null;
        if ($latestScan) {
            $comparison = [
                'new'        => VulnTracked::where('assessment_id', $assessment->id)
                                    ->where('tracking_status', 'New')
                                    ->where('first_scan_id', $latestScan->id)->count(),
                'resolved'   => VulnTrackedHistory::where('scan_id', $latestScan->id)
                                    ->where('event_type', 'resolved')->count(),
                'persistent' => VulnTrackedHistory::where('scan_id', $latestScan->id)
                                    ->whereIn('event_type', ['still_present', 'status_changed'])->count(),
            ];
        } elseif ($baseline) {
            $comparison = null;
        }

        // Host-level comparison (baseline vs latest)
        if ($baseline && $latestScan) {
            $baselineIps = $baseline->hostSet();
            $latestIps   = $latestScan->hostSet();
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
            ->distinct('ip_address')->count('ip_address');

        // Remediation progress — driven by vuln_tracked (scan-confirmed) + vuln_remediations (workflow)
        $remStats = null;
        if ($hasTracked) {
            $openIn = implode("','", VulnTracked::openStatuses()); // 'New','Open','Unresolved','Reopened'
            $remStats = DB::table('vuln_tracked as vt')
                ->where('vt.assessment_id', $assessment->id)
                ->whereIn('vt.severity', ['Critical', 'High', 'Medium', 'Low'])
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
            ->selectRaw("COALESCE(os_override_family, os_family) as family, COUNT(*) as cnt")
            ->groupBy('family')
            ->orderByDesc('cnt')
            ->get();

        $osHostCount = VulnHostOs::where('assessment_id', $assessment->id)->count();

        $scopeGroups = AssessmentScopeGroup::withCount('items')->orderBy('name')->get();

        return view('vuln_assessments.show', compact(
            'assessment', 'baseline', 'latestScan', 'activeScan',
            'stats', 'topIps', 'comparison', 'hostComparison', 'activeHostCount', 'remStats',
            'osDistribution', 'osHostCount', 'scopeGroups'
        ));
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

        // ── Base query: vuln_tracked (ALL scans, cumulative) ─────────────
        $query = VulnTracked::where('vuln_tracked.assessment_id', $assessment->id)
            ->whereIn('vuln_tracked.severity', $displaySeverities)
            ->select('vuln_tracked.*');

        // Join remediations for remediation-status filtering
        $query->leftJoin('vuln_remediations', function ($join) use ($assessment) {
            $join->on('vuln_remediations.plugin_id',  '=', 'vuln_tracked.plugin_id')
                 ->on('vuln_remediations.ip_address', '=', 'vuln_tracked.ip_address')
                 ->where('vuln_remediations.assessment_id', '=', $assessment->id);
        });

        // ── Tracking status filter ────────────────────────────────────────
        // new | unresolved | open | reopened | resolved | all | (default = all active)
        $trackingFilter = $request->input('tracking');
        if ($trackingFilter === 'resolved') {
            $query->where('vuln_tracked.tracking_status', VulnTracked::STATUS_RESOLVED);
        } elseif ($trackingFilter === 'new') {
            $query->where('vuln_tracked.tracking_status', VulnTracked::STATUS_NEW);
        } elseif ($trackingFilter === 'unresolved') {
            $query->where('vuln_tracked.tracking_status', VulnTracked::STATUS_UNRESOLVED);
        } elseif ($trackingFilter === 'open') {
            $query->where('vuln_tracked.tracking_status', VulnTracked::STATUS_OPEN);
        } elseif ($trackingFilter === 'reopened') {
            $query->where('vuln_tracked.tracking_status', VulnTracked::STATUS_REOPENED);
        } elseif ($trackingFilter === 'all' || is_null($trackingFilter)) {
            // no filter — show all statuses by default
        } else {
            // Fallback: all active (New + Open + Unresolved + Reopened)
            $query->whereIn('vuln_tracked.tracking_status', VulnTracked::openStatuses());
        }

        // ── Standard filters ──────────────────────────────────────────────
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

        // ── Remediation status filter ─────────────────────────────────────
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
            ->orderByRaw("CASE vuln_tracked.tracking_status WHEN 'New' THEN 1 WHEN 'Reopened' THEN 2 WHEN 'Unresolved' THEN 3 WHEN 'Open' THEN 4 WHEN 'Resolved' THEN 5 ELSE 6 END")
            ->orderByRaw("CASE vuln_tracked.severity WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 WHEN 'Low' THEN 4 ELSE 5 END")
            ->paginate(30)
            ->withQueryString();

        // ── Remediations keyed for display ────────────────────────────────
        $remediations = VulnRemediation::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy(fn($r) => $r->plugin_id . '|' . $r->ip_address);

        // ── Tracking status counts (for filter tabs) ──────────────────────
        $trackingCounts = VulnTracked::where('assessment_id', $assessment->id)
            ->whereIn('severity', $displaySeverities)
            ->selectRaw('tracking_status, COUNT(*) as cnt')
            ->groupBy('tracking_status')
            ->pluck('cnt', 'tracking_status');

        // ── Remediation status counts ─────────────────────────────────────
        $remStatusCounts = VulnTracked::where('vuln_tracked.assessment_id', $assessment->id)
            ->whereIn('vuln_tracked.severity', $displaySeverities)
            ->whereIn('vuln_tracked.tracking_status', VulnTracked::openStatuses())
            ->leftJoin('vuln_remediations', function ($join) use ($assessment) {
                $join->on('vuln_remediations.plugin_id',  '=', 'vuln_tracked.plugin_id')
                     ->on('vuln_remediations.ip_address', '=', 'vuln_tracked.ip_address')
                     ->where('vuln_remediations.assessment_id', '=', $assessment->id);
            })
            ->selectRaw("COALESCE(vuln_remediations.status, 'Open') as rem_status, COUNT(*) as cnt")
            ->groupBy('rem_status')
            ->pluck('cnt', 'rem_status');

        return view('vuln_assessments.findings', compact(
            'assessment', 'baseline', 'latestScan', 'findings', 'remediations',
            'trackingCounts', 'trackingFilter',
            'remStatusCounts', 'remStatusFilter'
        ));
    }

    public function uploadScan(Request $request, VulnAssessment $vulnAssessment)
    {
        $this->authorize('manage', $vulnAssessment);

        $request->validate([
            'scan_file' => ['required', 'file', 'max:51200', 'mimes:xml,csv,txt'],
            'notes'     => ['nullable', 'string', 'max:1000'],
        ]);

        $assessment = $vulnAssessment;
        $file       = $request->file('scan_file');
        $ext        = strtolower($file->getClientOriginalExtension());
        $filename   = $file->getClientOriginalName();
        $isBaseline = $assessment->scans()->count() === 0;

        // Reject duplicate uploads — same filename already exists for this assessment.
        // This prevents re-uploading a "Before" scan after an "After" rescan, which would
        // incorrectly reopen already-resolved findings.
        if ($assessment->scans()->where('filename', $filename)->exists()) {
            return back()->withErrors([
                'scan_file' => "\"$filename\" has already been uploaded to this assessment. "
                    . 'Rename the file or delete the existing scan before re-uploading.',
            ]);
        }

        $parsed = in_array($ext, ['xml', 'nessus'])
            ? $this->parseXml($file->getRealPath())
            : $this->parseCsv($file->getRealPath());

        $rows      = $parsed['rows'];
        $hostOsMap = $parsed['hostOs']; // keyed by ip_address

        DB::transaction(function () use ($assessment, $filename, $isBaseline, $rows, $hostOsMap, $request) {
            $scan = VulnScan::create([
                'assessment_id' => $assessment->id,
                'filename'      => $filename,
                'is_baseline'   => $isBaseline,
                'notes'         => $request->notes,
                'created_by'    => Auth::id(),
            ]);

            $inserted = 0;
            foreach ($rows as $row) {
                // Deduplicate within this scan by plugin_id + ip_address (same vuln on multiple ports = 1 finding per host)
                $finding = VulnFinding::firstOrCreate(
                    [
                        'scan_id'    => $scan->id,
                        'plugin_id'  => $row['plugin_id'],
                        'ip_address' => $row['ip_address'],
                    ],
                    array_merge($row, [
                        'scan_id'       => $scan->id,
                        'assessment_id' => $assessment->id,
                    ])
                );

                if ($finding->wasRecentlyCreated) {
                    $inserted++;
                }

                // Ensure a remediation record exists for actionable findings only (Info excluded)
                if (($row['severity'] ?? 'Info') !== 'Info') {
                    VulnRemediation::firstOrCreate([
                        'assessment_id' => $assessment->id,
                        'plugin_id'     => $row['plugin_id'],
                        'ip_address'    => $row['ip_address'],
                    ], ['status' => 'Open']);
                }
            }

            $hostCount = VulnFinding::where('scan_id', $scan->id)
                ->distinct('ip_address')
                ->count('ip_address');

            $scan->update(['finding_count' => $inserted, 'host_count' => $hostCount]);

            // ── Upsert vuln_host_os per IP ────────────────────
            foreach ($hostOsMap as $ip => $osData) {
                $existing = VulnHostOs::where('assessment_id', $assessment->id)
                    ->where('ip_address', $ip)
                    ->first();

                if ($existing) {
                    // Append to history if OS changed
                    $history = $existing->os_history ?? [];
                    if ($existing->os_name && $existing->os_name !== $osData['os_name']) {
                        $history[] = [
                            'os_name'      => $existing->os_name,
                            'os_family'    => $existing->os_family,
                            'confidence'   => $existing->os_confidence,
                            'scan_id'      => $existing->scan_id,
                            'detected_at'  => $existing->updated_at?->toDateTimeString(),
                        ];
                    }
                    // Update if new confidence >= existing (prefer more confident detection)
                    if ($osData['os_confidence'] >= $existing->os_confidence) {
                        $existing->update([
                            'scan_id'          => $scan->id,
                            'hostname'         => $osData['hostname'] ?? $existing->hostname,
                            'os_name'          => $osData['os_name'],
                            'os_family'        => $osData['os_family'],
                            'os_confidence'    => $osData['os_confidence'],
                            'os_kernel'        => $osData['os_kernel'] ?? null,
                            'detection_sources'=> $osData['detection_sources'],
                            'os_history'       => $history,
                        ]);
                    } else {
                        $existing->update(['scan_id' => $scan->id, 'os_history' => $history]);
                    }
                } else {
                    VulnHostOs::create(array_merge($osData, [
                        'assessment_id' => $assessment->id,
                        'scan_id'       => $scan->id,
                    ]));
                }
            }

            // ── Tracking engine: compare this scan against all previous state ──
            (new VulnTrackingService())->track($assessment, $scan);
        });

        $label = $isBaseline ? 'Baseline scan' : 'Latest scan';
        return redirect()->route('vuln-assessments.show', $assessment)
            ->with('success', "{$label} \"{$filename}\" imported successfully.");
    }



    public function updateRemediation(Request $request, VulnAssessment $vulnAssessment, VulnRemediation $remediation)
    {
        $this->authorize('update', $vulnAssessment);
        abort_unless($remediation->assessment_id === $vulnAssessment->id, 403);

        $data = $request->validate([
            'status'      => ['required', 'in:Open,In Progress,Resolved,Accepted Risk'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'due_date'    => ['nullable', 'date'],
            'comments'    => ['nullable', 'string'],
        ]);

        $data['updated_by'] = Auth::id();
        $remediation->update($data);

        return back()->with('success', 'Remediation updated.');
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
            $msg .= " {$skipped} could not be classified (marked 'Other') — review manually.";
        }

        return back()->with('success', $msg);
    }

    public function destroy(VulnAssessment $vulnAssessment)
    {
        $this->authorize('delete', $vulnAssessment);

        $vulnAssessment->delete();
        return redirect()->route('vuln-assessments.index')
            ->with('success', 'Assessment deleted.');
    }

    public function updateScopeGroup(Request $request, VulnAssessment $vulnAssessment)
    {
        $data = $request->validate([
            'scope_group_id' => ['nullable', 'integer', 'exists:assessment_scope_groups,id'],
        ]);

        $vulnAssessment->update(['scope_group_id' => $data['scope_group_id'] ?? null]);

        return back()->with('success', 'Scope group updated.');
    }

    // ── Reports ───────────────────────────────────────────────

    private function buildReportData(VulnAssessment $a): array
    {
        $active = VulnTracked::where('assessment_id', $a->id)
            ->whereIn('tracking_status', VulnTracked::openStatuses())
            ->selectRaw("severity, COUNT(*) as cnt")
            ->groupBy('severity')->pluck('cnt', 'severity');

        $resolved = VulnTracked::where('assessment_id', $a->id)
            ->where('tracking_status', 'Resolved')
            ->selectRaw("severity, COUNT(*) as cnt")
            ->groupBy('severity')->pluck('cnt', 'severity');

        $topHosts = VulnTracked::where('assessment_id', $a->id)
            ->whereIn('tracking_status', VulnTracked::openStatuses())
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
            ->orderByRaw("CASE severity WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 WHEN 'Low' THEN 4 ELSE 5 END")
            ->orderBy('ip_address')
            ->get(['vuln_name', 'severity', 'ip_address', 'hostname', 'port', 'protocol', 'cve', 'tracking_status', 'first_seen_at']);

        return compact('active', 'resolved', 'topHosts', 'findings');
    }

    public function reportPdf(VulnAssessment $vulnAssessment)
    {
        $a    = $vulnAssessment->load('creator', 'scans', 'scopeEntries');
        $data = array_merge($this->buildReportData($a), $this->buildDetailedReportData($a));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                    'vuln_assessments.report_pdf',
                    array_merge(['a' => $a], $data)
                )->setPaper('a4', 'portrait');

        $filename = str()->slug($a->name) . '_report_' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    public function reportWord(VulnAssessment $vulnAssessment)
    {
        $a    = $vulnAssessment->load('creator', 'scans', 'scopeEntries');
        $data = array_merge($this->buildReportData($a), $this->buildDetailedReportData($a));
        $html = view('vuln_assessments.report_word', array_merge(['a' => $a], $data))->render();

        $filename = str()->slug($a->name) . '_report_' . now()->format('Ymd') . '.doc';
        return response($html, 200, [
            'Content-Type'        => 'application/msword',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /** Builds the rich grouped data shared by both PDF and Word report generators. */
    private function buildDetailedReportData(VulnAssessment $a): array
    {
        // Findings grouped: severity → plugin_id → [vuln info + affected hosts list]
        $rawFindings = VulnTracked::where('assessment_id', $a->id)
            ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
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
                    'description'     => $f->description,
                    'remediation_text'=> $f->remediation_text,
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
        $a       = $vulnAssessment->load('creator');
        $data    = $this->buildReportData($a);
        $findings = $data['findings'];

        $filename = str()->slug($a->name) . '_report_' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($a, $findings) {
            $out = fopen('php://output', 'w');

            // Summary sheet header
            fputcsv($out, ['Assessment Report — ' . $a->name]);
            fputcsv($out, ['Generated', now()->format('d M Y H:i')]);
            fputcsv($out, ['Period', ($a->period_start?->format('d M Y') ?? '—') . ' – ' . ($a->period_end?->format('d M Y') ?? '—')]);
            fputcsv($out, ['Environment', $a->environment ?? '—']);
            fputcsv($out, []);

            // Findings
            fputcsv($out, ['Vulnerability Name', 'Severity', 'IP Address', 'Hostname', 'Port', 'Protocol', 'CVE', 'Status', 'First Seen']);
            foreach ($findings as $f) {
                fputcsv($out, [
                    $f->vuln_name,
                    $f->severity,
                    $f->ip_address,
                    $f->hostname,
                    $f->port,
                    $f->protocol,
                    $f->cve,
                    $f->tracking_status,
                    $f->first_seen_at?->format('d M Y'),
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Parsers ────────────────────────────────────────────────

    private function parseXml(string $path): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path);
        if (!$xml) return ['rows' => [], 'hostOs' => []];

        $sevMap = ['0' => 'Info', '1' => 'Low', '2' => 'Medium', '3' => 'High', '4' => 'Critical'];
        $rows      = [];
        $hostOsMap = [];

        $reports = [];
        if (isset($xml->Report)) {
            foreach ($xml->Report as $r) $reports[] = $r;
        } else {
            $reports[] = $xml;
        }

        foreach ($reports as $report) {
            foreach ($report->ReportHost ?? [] as $host) {
                $ip       = (string) ($host->HostProperties->xpath('tag[@name="host-ip"]')[0] ?? $host['name'] ?? '');
                $hostname = (string) ($host->HostProperties->xpath('tag[@name="hostname"]')[0] ?? '');
                $tsRaw    = (string) ($host->HostProperties->xpath('tag[@name="HOST_START"]')[0] ?? '');
                $ts       = $tsRaw ? date('Y-m-d H:i:s', strtotime($tsRaw)) : null;

                // ── OS detection via OsDetector (multi-source) ──
                $reportItems = iterator_to_array($host->ReportItem ?? []);
                $osResult    = OsDetector::detectFromXml($host->HostProperties, $reportItems);

                $osDetected  = $osResult['os_name'];  // backward-compat string
                $osName      = $osResult['os_name'];
                $osFamily    = $osResult['os_family'];
                $osConfidence= $osResult['os_confidence'];
                $osKernel    = $osResult['os_kernel'];

                // Collect per-host OS data for vuln_host_os upsert
                if ($ip) {
                    $hostOsMap[$ip] = [
                        'ip_address'       => $ip,
                        'hostname'         => $hostname ?: null,
                        'os_name'          => $osName,
                        'os_family'        => $osFamily,
                        'os_confidence'    => $osConfidence,
                        'os_kernel'        => $osKernel,
                        'detection_sources'=> $osResult['detection_sources'],
                    ];
                }

                foreach ($host->ReportItem ?? [] as $item) {
                    $sevRaw   = (string) ($item['severity'] ?? '0');
                    $sev      = $sevMap[$sevRaw] ?? 'Info';
                    $vulnName = (string) ($item['pluginName'] ?? 'Unknown');
                    $desc     = (string) ($item->description ?? '');
                    $portVal  = (string) ($item['port'] ?? '');
                    $protoVal = (string) ($item['protocol'] ?? '');
                    $output   = (string) ($item->plugin_output ?? '');
                    $cveVal   = (string) ($item->cve ?? '');

                    // CVSS: prefer v3 base score, fall back to v2
                    $cvssRaw  = (string) ($item->cvss3_base_score ?? $item->cvss_base_score ?? '');
                    $cvssScore = $cvssRaw !== '' ? (float) $cvssRaw : null;

                    $classification = VulnClassifier::classify(
                        $vulnName, $desc, $osDetected, $portVal, $protoVal, $output, $cveVal
                    );

                    $rows[] = [
                        'ip_address'         => $ip,
                        'hostname'           => $hostname ?: null,
                        'os_detected'        => $osDetected,
                        'os_name'            => $osName,
                        'os_family'          => $osFamily,
                        'os_confidence'      => $osConfidence,
                        'os_kernel'          => $osKernel,
                        'vuln_category'      => $classification['category'],
                        'affected_component' => $classification['affected_component'],
                        'plugin_id'          => (string) ($item['pluginID'] ?? '0'),
                        'cve'                => $cveVal,
                        'cvss_score'         => $cvssScore,
                        'severity'           => $sev,
                        'vuln_name'          => $vulnName,
                        'description'        => $desc,
                        'remediation_text'   => (string) ($item->solution ?? ''),
                        'port'               => $portVal,
                        'protocol'           => $protoVal,
                        'plugin_output'      => $output,
                        'scan_timestamp'     => $ts,
                    ];
                }
            }
        }

        // Deduplicate rows within this file by plugin_id + ip_address + port
        $rows = $this->deduplicateRows($rows);

        return ['rows' => $rows, 'hostOs' => $hostOsMap];
    }

    private function parseCsv(string $path): array
    {
        $handle  = fopen($path, 'r');
        $headers = array_map(fn($h) => strtolower(trim($h)), fgetcsv($handle) ?: []);
        $rows      = [];
        $hostOsMap = []; // keyed by ip_address — keep best confidence per IP

        $col = function (array $row, array $keys) use ($headers): string {
            foreach ($keys as $k) {
                $idx = array_search($k, $headers);
                if ($idx !== false && isset($row[$idx]) && trim($row[$idx]) !== '') {
                    return trim($row[$idx]);
                }
            }
            return '';
        };

        $sevNorm = [
            'critical' => 'Critical', 'high' => 'High',
            'medium'   => 'Medium',   'moderate' => 'Medium',
            'low'      => 'Low',      'none' => 'Info', 'info' => 'Info',
        ];

        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) < 2) continue;

            $sev = $sevNorm[strtolower($col($line, ['risk', 'severity', 'cvss_severity', 'level']))] ?? null;
            if (!$sev) continue;

            $ip = $col($line, ['host', 'ip address', 'ip_address', 'ip', 'asset']);
            if (!$ip) continue;

            $vulnName   = $col($line, ['name', 'plugin name', 'title', 'vulnerability']);
            $desc       = $col($line, ['description', 'synopsis', 'detail']);
            $osRawCsv   = $col($line, ['operating system', 'os', 'detected os', 'os_detected']) ?: null;
            $portVal    = $col($line, ['port']);
            $protoVal   = $col($line, ['protocol']);
            $output     = $col($line, ['plugin output', 'plugin_output']);
            $cveVal     = $col($line, ['cve']);
            $hostname   = $col($line, ['dns name', 'hostname', 'fqdn', 'netbios']) ?: null;
            $cvssRawCsv = $col($line, ['cvss3_base_score', 'cvss_base_score', 'cvss score', 'cvss_score', 'cvss']);
            $cvssScoreCsv = $cvssRawCsv !== '' ? (float) $cvssRawCsv : null;

            // ── OS detection via OsDetector ──
            $osResult   = OsDetector::detectFromCsv($osRawCsv, $vulnName, $desc, $output);
            $osDetected = $osResult['os_name'];

            // Track best OS per IP
            if ($ip) {
                if (!isset($hostOsMap[$ip]) || $osResult['os_confidence'] > $hostOsMap[$ip]['os_confidence']) {
                    $hostOsMap[$ip] = [
                        'ip_address'       => $ip,
                        'hostname'         => $hostname,
                        'os_name'          => $osResult['os_name'],
                        'os_family'        => $osResult['os_family'],
                        'os_confidence'    => $osResult['os_confidence'],
                        'os_kernel'        => $osResult['os_kernel'],
                        'detection_sources'=> $osResult['detection_sources'],
                    ];
                }
                // Update hostname if we now have one and didn't before
                if ($hostname && !$hostOsMap[$ip]['hostname']) {
                    $hostOsMap[$ip]['hostname'] = $hostname;
                }
            }

            $classification = VulnClassifier::classify(
                $vulnName, $desc, $osDetected, $portVal, $protoVal, $output, $cveVal
            );

            $rows[] = [
                'ip_address'         => $ip,
                'hostname'           => $hostname,
                'os_detected'        => $osDetected,
                'os_name'            => $osResult['os_name'],
                'os_family'          => $osResult['os_family'],
                'os_confidence'      => $osResult['os_confidence'],
                'os_kernel'          => $osResult['os_kernel'],
                'vuln_category'      => $classification['category'],
                'affected_component' => $classification['affected_component'],
                'plugin_id'          => $col($line, ['plugin id', 'plugin_id', 'pluginid']) ?: '0',
                'cve'                => $cveVal,
                'cvss_score'         => $cvssScoreCsv,
                'severity'           => $sev,
                'vuln_name'          => $vulnName,
                'description'        => $desc,
                'remediation_text'   => $col($line, ['solution', 'remediation', 'fix', 'recommendation']),
                'port'               => $portVal,
                'protocol'           => $protoVal,
                'plugin_output'      => $output,
                'scan_timestamp'     => null,
            ];
        }

        fclose($handle);

        // Deduplicate rows within this file by plugin_id + ip_address + port
        $rows = $this->deduplicateRows($rows);

        return ['rows' => $rows, 'hostOs' => $hostOsMap];
    }

    /**
     * Remove duplicate findings within a single scan file.
     * Deduplication key: plugin_id + ip_address (same vulnerability on multiple ports = one finding per host).
     */
    private function deduplicateRows(array $rows): array
    {
        $seen   = [];
        $unique = [];
        foreach ($rows as $row) {
            // Composite key matches the DB unique constraint: plugin + ip + port
            $key = ($row['plugin_id']   ?? '') . '|'
                 . ($row['ip_address']  ?? '') . '|'
                 . ($row['port']        ?? '');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[]   = $row;
            }
        }
        return $unique;
    }

    // ── OS Assets + Override ────────────────────────────────────

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
}
