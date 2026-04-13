<?php

namespace App\Http\Controllers;

use App\Models\VulnAssessment;
use App\Models\VulnFinding;
use App\Models\VulnHostOs;
use App\Models\VulnRemediation;
use App\Models\VulnScan;
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

        // Stats (from all findings of the latest scan, or baseline if only one)
        $activeScan = $latestScan ?? $baseline;

        $stats = null;
        $topIps = collect();
        $comparison = null;
        $findingsQuery = null;

        if ($activeScan) {
            // Stats and top IPs are restricted to displayed severities only (Info excluded)
            $stats = VulnFinding::where('scan_id', $activeScan->id)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as low
                ")->first();

            $topIps = VulnFinding::where('scan_id', $activeScan->id)
                ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                ->selectRaw('ip_address, COUNT(*) as total,
                    SUM(CASE WHEN severity="Critical" THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity="High" THEN 1 ELSE 0 END) as high')
                ->groupBy('ip_address')
                ->orderByDesc('critical')->orderByDesc('high')->orderByDesc('total')
                ->limit(10)
                ->get();
        }

        // Comparison (baseline vs latest)
        if ($baseline && $latestScan) {
            $baselineKeys  = $baseline->fingerprintSet();
            $latestKeys    = $latestScan->fingerprintSet();
            $comparison = [
                'new'        => $latestKeys->diff($baselineKeys)->count(),
                'resolved'   => $baselineKeys->diff($latestKeys)->count(),
                'persistent' => $baselineKeys->intersect($latestKeys)->count(),
            ];
        }

        // Remediation progress — scoped to C/H/M/L findings in the active scan only (Info excluded)
        $remStats = null;
        if ($activeScan) {
            $remStats = VulnRemediation::where('assessment_id', $assessment->id)
                ->whereExists(function ($sub) use ($activeScan) {
                    $sub->select(DB::raw(1))
                        ->from('vuln_findings')
                        ->whereColumn('vuln_findings.plugin_id', 'vuln_remediations.plugin_id')
                        ->whereColumn('vuln_findings.ip_address', 'vuln_remediations.ip_address')
                        ->where('vuln_findings.scan_id', $activeScan->id)
                        ->whereIn('vuln_findings.severity', ['Critical', 'High', 'Medium', 'Low']);
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
            'stats', 'topIps', 'comparison', 'remStats',
            'osDistribution', 'osHostCount'
        ));
    }

    public function findings(Request $request, VulnAssessment $vulnAssessment)
    {
        $assessment = $vulnAssessment;
        $baseline   = $assessment->baselineScan();
        $latestScan = $assessment->latestScan();
        $activeScan = $latestScan ?? $baseline;

        abort_unless($activeScan, 404);

        $baselineKeys = $baseline ? $baseline->fingerprintSet() : collect();
        $latestKeys   = $latestScan ? $latestScan->fingerprintSet() : collect();

        // Always restrict display to actionable severities only (Info is stored but not shown)
        $displaySeverities = ['Critical', 'High', 'Medium', 'Low'];

        $query = VulnFinding::where('scan_id', $activeScan->id)
            ->whereIn('severity', $displaySeverities);

        if ($request->filled('severity') && in_array($request->severity, $displaySeverities)) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('category') && in_array($request->category, \App\Models\VulnFinding::categories())) {
            $query->where('vuln_category', $request->category);
        }
        if ($request->filled('os_family') && in_array($request->os_family, ['Windows', 'Linux', 'Unix', 'Other'])) {
            $query->where('os_family', $request->os_family);
        }
        if ($request->filled('os_name')) {
            $query->where('os_name', 'like', '%' . $request->os_name . '%');
        }
        if ($request->filled('ip'))       $query->where('ip_address', $request->ip);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('vuln_name', 'like', "%$s%")
                  ->orWhere('ip_address', 'like', "%$s%")
                  ->orWhere('plugin_id', 'like', "%$s%")
                  ->orWhere('cve', 'like', "%$s%");
            });
        }

        $findings = $query->orderByRaw("CASE severity WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 WHEN 'Low' THEN 4 ELSE 5 END")->paginate(30)->withQueryString();

        // Load remediations keyed by plugin_id|ip_address
        $remediations = VulnRemediation::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy(fn($r) => $r->plugin_id . '|' . $r->ip_address);

        return view('vuln_assessments.findings', compact(
            'assessment', 'activeScan', 'findings', 'remediations',
            'baselineKeys', 'latestKeys', 'baseline', 'latestScan'
        ));
    }

    public function uploadScan(Request $request, VulnAssessment $vulnAssessment)
    {
        $request->validate([
            'scan_file' => ['required', 'file', 'max:51200'],
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

            $scan->update(['finding_count' => $inserted]);

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
        });

        $label = $isBaseline ? 'Baseline scan' : 'Latest scan';
        return redirect()->route('vuln-assessments.show', $assessment)
            ->with('success', "{$label} \"{$filename}\" imported successfully.");
    }

    public function updateRemediation(Request $request, VulnAssessment $vulnAssessment, VulnRemediation $remediation)
    {
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

                // Collect per-host OS data for vuln_host_os upsert
                if ($ip) {
                    $hostOsMap[$ip] = [
                        'ip_address'       => $ip,
                        'hostname'         => $hostname ?: null,
                        'os_name'          => $osName,
                        'os_family'        => $osFamily,
                        'os_confidence'    => $osConfidence,
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
