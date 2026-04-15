<?php

namespace App\Http\Controllers;

use App\Models\VulnAssessment;
use App\Models\VulnFinding;
use App\Models\VulnHostOs;
use App\Models\VulnRemediation;
use App\Models\VulnScan;
use App\Models\VulnTracked;
use App\Models\VulnTrackedHistory;
use App\Services\OsDetector;
use App\Services\VulnClassifier;
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
        return view('vuln_assessments.create');
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
        // Active = New + Pending (not yet resolved)
        $stats  = null;
        $topIps = collect();

        $hasTracked = VulnTracked::where('assessment_id', $assessment->id)->exists();

        if ($hasTracked) {
            $stats = VulnTracked::where('assessment_id', $assessment->id)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->whereIn('tracking_status', ['New', 'Pending'])
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as low
                ")->first();

            $topIps = VulnTracked::where('assessment_id', $assessment->id)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->whereIn('tracking_status', ['New', 'Pending'])
                ->selectRaw('ip_address, COUNT(*) as total,
                    SUM(CASE WHEN severity="Critical" THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity="High" THEN 1 ELSE 0 END) as high')
                ->groupBy('ip_address')
                ->orderByDesc('critical')->orderByDesc('high')->orderByDesc('total')
                ->limit(10)
                ->get();
        } elseif ($activeScan) {
            // Fallback to raw findings if tracking hasn't been built yet
            // (i.e. scans uploaded before the tracking engine was added)
            $stats = VulnFinding::where('scan_id', $activeScan->id)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as low
                ")->first();
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
            ->whereIn('tracking_status', ['New', 'Pending'])
            ->distinct('ip_address')->count('ip_address');

        // Remediation progress — scoped to active (New+Pending) C/H/M/L tracked vulns
        $remStats = null;
        if ($hasTracked) {
            $remStats = VulnRemediation::where('vuln_remediations.assessment_id', $assessment->id)
                ->whereExists(function ($sub) use ($assessment) {
                    $sub->select(DB::raw(1))
                        ->from('vuln_tracked')
                        ->whereColumn('vuln_tracked.plugin_id',  'vuln_remediations.plugin_id')
                        ->whereColumn('vuln_tracked.ip_address', 'vuln_remediations.ip_address')
                        ->where('vuln_tracked.assessment_id', $assessment->id)
                        ->whereIn('vuln_tracked.tracking_status', ['New', 'Pending'])
                        ->whereIn('vuln_tracked.severity', ['Critical', 'High', 'Medium', 'Low']);
                })
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status='Resolved'      THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status='Accepted Risk' THEN 1 ELSE 0 END) as accepted,
                    SUM(CASE WHEN status='In Progress'   THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status='Open'          THEN 1 ELSE 0 END) as open_count
                ")->first();
        }

        // OS distribution from vuln_host_os
        $osDistribution = VulnHostOs::where('assessment_id', $assessment->id)
            ->selectRaw("COALESCE(os_override_family, os_family) as family, COUNT(*) as cnt")
            ->groupBy('family')
            ->orderByDesc('cnt')
            ->get();

        $osHostCount = VulnHostOs::where('assessment_id', $assessment->id)->count();

        return view('vuln_assessments.show', compact(
            'assessment', 'baseline', 'latestScan', 'activeScan',
            'stats', 'topIps', 'comparison', 'hostComparison', 'activeHostCount', 'remStats',
            'osDistribution', 'osHostCount'
        ));
    }

    public function findings(Request $request, VulnAssessment $vulnAssessment)
    {
        $assessment = $vulnAssessment;
        $latestScan = $assessment->latestScan() ?? $assessment->baselineScan();

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

        // ── Tracking status filter (New / Pending / Resolved) ─────────────
        $trackingFilter = $request->input('tracking');   // new | pending | resolved | all
        if ($trackingFilter === 'resolved') {
            $query->where('vuln_tracked.tracking_status', 'Resolved');
        } elseif ($trackingFilter === 'new') {
            $query->where('vuln_tracked.tracking_status', 'New');
        } elseif ($trackingFilter === 'pending') {
            $query->where('vuln_tracked.tracking_status', 'Pending');
        } else {
            // Default: show active only (New + Pending); hide already resolved
            $query->whereIn('vuln_tracked.tracking_status', ['New', 'Pending']);
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
            ->orderByRaw("CASE vuln_tracked.tracking_status WHEN 'New' THEN 1 WHEN 'Pending' THEN 2 WHEN 'Resolved' THEN 3 END")
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
            ->whereIn('vuln_tracked.tracking_status', ['New', 'Pending'])
            ->leftJoin('vuln_remediations', function ($join) use ($assessment) {
                $join->on('vuln_remediations.plugin_id',  '=', 'vuln_tracked.plugin_id')
                     ->on('vuln_remediations.ip_address', '=', 'vuln_tracked.ip_address')
                     ->where('vuln_remediations.assessment_id', '=', $assessment->id);
            })
            ->selectRaw("COALESCE(vuln_remediations.status, 'Open') as rem_status, COUNT(*) as cnt")
            ->groupBy('rem_status')
            ->pluck('cnt', 'rem_status');

        return view('vuln_assessments.findings', compact(
            'assessment', 'latestScan', 'findings', 'remediations',
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
            $this->runTrackingEngine($assessment, $scan);
        });

        $label = $isBaseline ? 'Baseline scan' : 'Latest scan';
        return redirect()->route('vuln-assessments.show', $assessment)
            ->with('success', "{$label} \"{$filename}\" imported successfully.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TRACKING ENGINE
    // Runs inside the upload transaction after findings are written.
    // Implements the New / Pending / Resolved lifecycle per (ip + plugin_id).
    // ─────────────────────────────────────────────────────────────────────────
    private function runTrackingEngine(VulnAssessment $assessment, VulnScan $scan): void
    {
        $scanTime = $scan->created_at ?? now();

        // ── 1. Load what this scan actually contains (from vuln_findings) ──
        // We query the DB so we get fully-populated rows (vuln_category etc.)
        $scanFindings = VulnFinding::where('scan_id', $scan->id)
            ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
            ->get();

        // Build current fingerprint map: "ip|plugin_id" → finding row
        $currentMap = $scanFindings->keyBy(fn($f) => $f->ip_address . '|' . $f->plugin_id);

        // ── 2. Load all existing tracked items for this assessment ──
        $existingTracked = VulnTracked::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy(fn($t) => $t->ip_address . '|' . $t->plugin_id);

        // ── 3. Load remediations (to skip 'Accepted Risk' from auto-resolve) ──
        $remediations = VulnRemediation::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy(fn($r) => $r->plugin_id . '|' . $r->ip_address);

        // ── 4. Process each finding in the current scan ────────────────────
        foreach ($scanFindings as $finding) {
            $fp = $finding->ip_address . '|' . $finding->plugin_id;

            // Common fields to keep current from the latest scan
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

            if (isset($existingTracked[$fp])) {
                // ── CASE: Already tracked ─────────────────────────────────
                $tracked      = $existingTracked[$fp];
                $prevStatus   = $tracked->tracking_status;
                $prevSeverity = $tracked->severity;
                $newSeverity  = $finding->severity;
                $sevChanged   = $prevSeverity !== $newSeverity;

                // Determine new tracking status: it's visible → Pending
                $newStatus = 'Pending';

                $tracked->update(array_merge($currentData, [
                    'tracking_status' => $newStatus,
                ]));

                // Write history entries
                if ($prevStatus === 'Resolved') {
                    // Vulnerability re-appeared after being resolved (regression)
                    VulnTrackedHistory::create([
                        'tracked_id'  => $tracked->id,
                        'scan_id'     => $scan->id,
                        'event_type'  => 'reappeared',
                        'prev_status' => 'Resolved',
                        'new_status'  => 'Pending',
                        'changed_at'  => $scanTime,
                    ]);
                } elseif ($prevStatus === 'New') {
                    // First re-confirmation: New → Pending
                    VulnTrackedHistory::create([
                        'tracked_id'  => $tracked->id,
                        'scan_id'     => $scan->id,
                        'event_type'  => 'status_changed',
                        'prev_status' => 'New',
                        'new_status'  => 'Pending',
                        'changed_at'  => $scanTime,
                    ]);
                } else {
                    // Already Pending — log still_present
                    VulnTrackedHistory::create([
                        'tracked_id'  => $tracked->id,
                        'scan_id'     => $scan->id,
                        'event_type'  => 'still_present',
                        'prev_status' => 'Pending',
                        'new_status'  => 'Pending',
                        'changed_at'  => $scanTime,
                    ]);
                }

                // Log severity change as a separate entry (regardless of status)
                if ($sevChanged) {
                    VulnTrackedHistory::create([
                        'tracked_id'   => $tracked->id,
                        'scan_id'      => $scan->id,
                        'event_type'   => 'severity_changed',
                        'prev_severity'=> $prevSeverity,
                        'new_severity' => $newSeverity,
                        'changed_at'   => $scanTime,
                    ]);
                }
            } else {
                // ── CASE: Brand new ip+vuln combination ──────────────────
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
                    'tracked_id'  => $tracked->id,
                    'scan_id'     => $scan->id,
                    'event_type'  => 'created',
                    'new_status'  => 'New',
                    'new_severity'=> $finding->severity,
                    'changed_at'  => $scanTime,
                ]);
            }
        }

        // ── 5. Resolve findings that are MISSING from this scan ───────────────
        // Only consider IPs that this scan actually covers. Findings for hosts
        // not present in this scan are left untouched — they may be scanned
        // separately (e.g. different Nessus file per host).
        $scannedIps = $scanFindings->pluck('ip_address')->unique()->flip(); // O(1) lookup

        foreach ($existingTracked as $fp => $tracked) {
            // Skip if the host wasn't covered by this scan at all
            if (!$scannedIps->has($tracked->ip_address)) {
                continue;
            }

            if ($currentMap->has($fp)) {
                continue; // still present — handled above
            }

            if ($tracked->tracking_status === 'Resolved') {
                continue; // already resolved — no change needed
            }

            // Never auto-resolve a manually accepted risk
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
                'tracked_id'  => $tracked->id,
                'scan_id'     => $scan->id,
                'event_type'  => 'resolved',
                'prev_status' => $prevStatus,
                'new_status'  => 'Resolved',
                'changed_at'  => $scanTime,
            ]);
        }
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
            $key = ($row['plugin_id'] ?? '') . '|' . ($row['ip_address'] ?? '');
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
