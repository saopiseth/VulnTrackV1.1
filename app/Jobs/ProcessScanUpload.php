<?php

namespace App\Jobs;

use App\Models\AssetInventory;
use App\Models\VulnAssessment;
use App\Models\VulnFinding;
use App\Models\VulnHostOs;
use App\Models\VulnRemediation;
use App\Models\VulnScan;
use App\Services\OsDetector;
use App\Services\VulnClassifier;
use App\Services\AssessmentSummaryService;
use App\Services\VulnTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessScanUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(
        public readonly int    $scanId,
        public readonly string $filePath,
        public readonly string $fileExtension,
    ) {}

    public function handle(): void
    {
        $scan       = VulnScan::findOrFail($this->scanId);
        $assessment = $scan->assessment;

        $scan->update(['upload_status' => 'processing']);

        try {
            $fullPath = Storage::disk('local')->path($this->filePath);

            DB::transaction(function () use ($assessment, $scan, $fullPath) {
                $now      = now()->toDateTimeString();
                $inserted = 0;
                $flushRows = function (array $rows) use ($assessment, $scan, $now, &$inserted): void {
                    $inserted += $this->persistParsedRows($rows, $assessment, $scan, $now);
                };

                $parseResult = in_array($this->fileExtension, ['xml', 'nessus'])
                    ? $this->parseXml($fullPath, $flushRows)
                    : $this->parseCsv($fullPath, $flushRows);

                $hostOsMap = $parseResult['hostOsMap'];

                $hostCount = VulnFinding::where('scan_id', $scan->id)
                    ->whereIn('severity', ['Critical', 'High', 'Medium', 'Low'])
                    ->distinct('ip_address')
                    ->count('ip_address');

                $scan->update([
                    'finding_count' => $inserted,
                    'host_count'    => $hostCount,
                    'scan_name'     => $parseResult['scanName'] ?: null,
                    'scan_date'     => $parseResult['scanDate'] ?: null,
                ]);

                // ── Upsert vuln_host_os per IP ────────────────────────────────
                foreach ($hostOsMap as $ip => $osData) {
                    $existing = VulnHostOs::where('assessment_id', $assessment->id)
                        ->where('ip_address', $ip)
                        ->first();

                    if ($existing) {
                        $history = $existing->os_history ?? [];
                        if ($existing->os_name && $existing->os_name !== $osData['os_name']) {
                            $history[] = [
                                'os_name'     => $existing->os_name,
                                'os_family'   => $existing->os_family,
                                'confidence'  => $existing->os_confidence,
                                'scan_id'     => $existing->scan_id,
                                'detected_at' => $existing->updated_at?->toDateTimeString(),
                            ];
                        }
                        if ($osData['os_confidence'] >= $existing->os_confidence) {
                            $existing->update([
                                'scan_id'           => $scan->id,
                                'hostname'          => $osData['hostname'] ?? $existing->hostname,
                                'os_name'           => $osData['os_name'],
                                'os_family'         => $osData['os_family'],
                                'os_confidence'     => $osData['os_confidence'],
                                'os_kernel'         => $osData['os_kernel'] ?? null,
                                'detection_sources' => $osData['detection_sources'],
                                'os_history'        => $history,
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

                // ── Sync asset_inventories (global cross-assessment view) ─────
                $ipList = array_keys($hostOsMap);

                // Aggregate vuln counts per IP across ALL findings for these IPs
                $allCounts = DB::table('vuln_findings')
                    ->whereIn('ip_address', $ipList)
                    ->selectRaw("ip_address, severity, COUNT(*) as cnt")
                    ->groupBy('ip_address', 'severity')
                    ->get()
                    ->groupBy('ip_address');

                // Collect unique open ports per IP (exclude port 0 / empty).
                // Nessus can report enough distinct ports to exceed MySQL's default GROUP_CONCAT limit.
                DB::statement('SET SESSION group_concat_max_len = 16777215');
                $allPorts = DB::table('vuln_findings')
                    ->whereIn('ip_address', $ipList)
                    ->whereRaw("port != '' AND port != '0'")
                    ->selectRaw("ip_address, GROUP_CONCAT(DISTINCT port ORDER BY (port+0)) as ports")
                    ->groupBy('ip_address')
                    ->pluck('ports', 'ip_address');

                foreach ($hostOsMap as $ip => $osData) {
                    $ipCounts = $allCounts->get($ip, collect());
                    DB::table('asset_inventories')->upsert(
                        [[
                            'ip_address'      => $ip,
                            'hostname'        => $osData['hostname'] ?? null,
                            'os'              => $osData['os_name'],
                            'os_family'       => $osData['os_family'],
                            'os_kernel'       => $osData['os_kernel'],
                            'open_ports'      => $allPorts[$ip] ?? null,
                            'vuln_critical'   => $ipCounts->where('severity', 'Critical')->sum('cnt'),
                            'vuln_high'       => $ipCounts->where('severity', 'High')->sum('cnt'),
                            'vuln_medium'     => $ipCounts->where('severity', 'Medium')->sum('cnt'),
                            'vuln_low'        => $ipCounts->where('severity', 'Low')->sum('cnt'),
                            'last_scanned_at' => $now,
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ]],
                        ['ip_address'],
                        ['hostname', 'os', 'os_family', 'os_kernel', 'open_ports',
                         'vuln_critical', 'vuln_high', 'vuln_medium', 'vuln_low',
                         'last_scanned_at', 'updated_at']
                    );
                }

                // ── Enrich asset_inventories from assessment_scopes ──────────
                // Use the most recently updated scope record per IP.
                $latestPerIp = DB::table('assessment_scopes')
                    ->whereIn('ip_address', $ipList)
                    ->whereNotNull('ip_address')
                    ->selectRaw('ip_address, MAX(updated_at) AS max_upd')
                    ->groupBy('ip_address');

                $scopeBusinessData = DB::table('assessment_scopes as s')
                    ->joinSub($latestPerIp, 'latest', fn ($j) =>
                        $j->on('s.ip_address', '=', 'latest.ip_address')
                          ->on('s.updated_at', '=', 'latest.max_upd')
                    )
                    ->get(['s.ip_address', 's.system_name', 's.identified_scope',
                           's.environment', 's.system_owner', 's.remediation_sla']);

                foreach ($scopeBusinessData as $scope) {
                    $payload = [];
                    foreach (['system_name', 'identified_scope', 'environment', 'system_owner', 'remediation_sla'] as $f) {
                        if ($scope->$f !== null && $scope->$f !== '') {
                            $payload[$f] = $scope->$f;
                        }
                    }
                    if (!empty($payload)) {
                        $payload['updated_at'] = $now;
                        DB::table('asset_inventories')
                            ->where('ip_address', $scope->ip_address)
                            ->update($payload);
                    }
                }

                // ── Apply Active / Not Found status based on the latest scan date ──
                AssetInventory::applyLatestScanStatus();

                // ── Tracking engine ───────────────────────────────────────────
                (new VulnTrackingService())->track($assessment, $scan, array_keys($hostOsMap));
            });

            $scan->update(['upload_status' => 'completed']);

            // Rebuild the cached summary now that tracking data is fresh.
            (new AssessmentSummaryService())->rebuild($assessment);

        } catch (\Throwable $e) {
            $scan->update([
                'upload_status' => 'failed',
                'upload_error'  => mb_substr($e->getMessage(), 0, 500),
            ]);
            throw $e;
        }
    }

    // ── Streaming XML parser using XMLReader ──────────────────────────────────
    // Loads one ReportHost subtree at a time instead of the whole document,
    // cutting peak memory use by ~80% on large Nessus files.
    private function persistParsedRows(array $rows, VulnAssessment $assessment, VulnScan $scan, string $now): int
    {
        if (empty($rows)) {
            return 0;
        }

        foreach ($rows as &$row) {
            $row['vuln_key'] = sha1(implode('|', [
                trim($row['plugin_id']),
                strtolower(trim($row['ip_address'])),
                (string) $row['port'],
                strtolower(trim($row['protocol'])),
            ]));
            $row = array_merge($row, [
                'scan_id'       => $scan->id,
                'assessment_id' => $assessment->id,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
        unset($row);

        $inserted = 0;
        foreach (array_chunk($rows, 250) as $chunk) {
            $inserted += DB::table('vuln_findings')->insertOrIgnore($chunk);
        }

        $remSeen = [];
        $remRows = [];
        foreach ($rows as $row) {
            if (($row['severity'] ?? 'Info') === 'Info') {
                continue;
            }

            $key = $row['plugin_id'] . '|' . $row['ip_address'];
            if (isset($remSeen[$key])) {
                continue;
            }

            $remSeen[$key] = true;
            $remRows[] = [
                'assessment_id' => $assessment->id,
                'plugin_id'     => $row['plugin_id'],
                'ip_address'    => $row['ip_address'],
                'status'        => 'Open',
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        foreach (array_chunk($remRows, 500) as $chunk) {
            DB::table('vuln_remediations')->insertOrIgnore($chunk);
        }

        return $inserted;
    }

    private function parseXml(string $path, callable $flushRows): array
    {
        $reader = new \XMLReader();
        if (!@$reader->open($path)) {
            return [];
        }

        $sevMap    = ['0' => 'Info', '1' => 'Low', '2' => 'Medium', '3' => 'High', '4' => 'Critical'];
        $rows      = [];
        $seen      = [];
        $hostOsMap = [];
        $batchSize = 50;
        $scanName  = '';
        $minDate   = null;

        while ($reader->read()) {
            // Capture the scan name from the Report element's name attribute.
            if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Report') {
                $scanName = (string) ($reader->getAttribute('name') ?? '');
            }

            if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->name !== 'ReportHost') {
                continue;
            }

            // Expand one host's subtree into a SimpleXML node; the rest of the
            // document stays on disk. Memory is freed at the end of each loop.
            $dom  = new \DOMDocument('1.0', 'UTF-8');
            $node = $dom->importNode($reader->expand(), true);
            $dom->appendChild($node);
            $host = simplexml_import_dom($node);

            $ip       = (string) ($host->HostProperties->xpath('tag[@name="host-ip"]')[0] ?? $host['name'] ?? '');
            $hostname = (string) ($host->HostProperties->xpath('tag[@name="hostname"]')[0] ?? '');
            $tsRaw    = (string) ($host->HostProperties->xpath('tag[@name="HOST_START"]')[0] ?? '');
            $ts       = $tsRaw ? date('Y-m-d H:i:s', strtotime($tsRaw)) : null;
            if ($ts && ($minDate === null || $ts < $minDate)) {
                $minDate = $ts;
            }

            $reportItems = iterator_to_array($host->ReportItem ?? []);
            $osResult    = OsDetector::detectFromXml($host->HostProperties, $reportItems);
            $osDetected  = $osResult['os_name'];
            $osName      = $osResult['os_name'];
            $osFamily    = $osResult['os_family'];
            $osConfidence= $osResult['os_confidence'];
            $osKernel    = $osResult['os_kernel'];

            if ($ip) {
                $hostOsMap[$ip] = [
                    'ip_address'        => $ip,
                    'hostname'          => $hostname ?: null,
                    'os_name'           => $osName,
                    'os_family'         => $osFamily,
                    'os_confidence'     => $osConfidence,
                    'os_kernel'         => $osKernel,
                    'detection_sources' => $osResult['detection_sources'],
                ];
            }

            foreach ($host->ReportItem ?? [] as $item) {
                $pluginId  = (string) ($item['pluginID'] ?? '0');
                $portVal   = (string) ($item['port'] ?? '');
                $dedupeKey = $pluginId . '|' . $ip . '|' . $portVal;
                if (isset($seen[$dedupeKey])) {
                    continue;
                }
                $seen[$dedupeKey] = true;

                $sevRaw    = (string) ($item['severity'] ?? '0');
                $sev       = $sevMap[$sevRaw] ?? 'Info';
                $vulnName  = (string) ($item['pluginName'] ?? 'Unknown');
                $desc      = (string) ($item->description ?? '');
                $protoVal  = (string) ($item['protocol'] ?? '');
                $output    = mb_substr((string) ($item->plugin_output ?? ''), 0, 10_000_000);
                $cveVal    = (string) ($item->cve ?? '');
                $cvssRaw   = (string) ($item->cvss3_base_score ?? $item->cvss_base_score ?? '');
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
                    'plugin_id'          => $pluginId,
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

                if (count($rows) >= $batchSize || memory_get_usage(true) > 160 * 1024 * 1024) {
                    $flushRows($rows);
                    $rows = [];
                }
            }

            // Explicitly free the per-host DOM tree before next iteration.
            unset($dom, $node, $host, $reportItems);
        }

        $reader->close();

        if (!empty($rows)) {
            $flushRows($rows);
        }

        return [
            'hostOsMap' => $hostOsMap,
            'scanName'  => $scanName,
            'scanDate'  => $minDate ? date('Y-m-d', strtotime($minDate)) : null,
        ];
    }

    // ── CSV parser (streaming via fgetcsv — unchanged from original) ──────────
    private function parseCsv(string $path, callable $flushRows): array
    {
        $handle     = fopen($path, 'r');
        $headers    = array_map(fn($h) => strtolower(trim($h)), fgetcsv($handle) ?: []);
        $rows       = [];
        $seen       = [];
        $hostOsMap  = [];
        $batchSize  = 100;
        $csvMinDate = null;

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

            $vulnName    = $col($line, ['name', 'plugin name', 'title', 'vulnerability']);
            $desc        = $col($line, ['description', 'synopsis', 'detail']);
            $osRawCsv    = $col($line, ['operating system', 'os', 'detected os', 'os_detected']) ?: null;
            $portVal     = $col($line, ['port']);
            $pluginId    = $col($line, ['plugin id', 'plugin_id', 'pluginid']) ?: '0';
            $dedupeKey   = $pluginId . '|' . $ip . '|' . $portVal;
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $protoVal    = $col($line, ['protocol']);
            $output      = mb_substr($col($line, ['plugin output', 'plugin_output']), 0, 10_000_000);
            $cveVal      = $col($line, ['cve']);
            $hostname    = $col($line, ['dns name', 'hostname', 'fqdn', 'netbios']) ?: null;
            $cvssRawCsv  = $col($line, ['cvss3_base_score', 'cvss_base_score', 'cvss score', 'cvss_score', 'cvss']);
            $cvssScoreCsv = $cvssRawCsv !== '' ? (float) $cvssRawCsv : null;

            $osResult   = OsDetector::detectFromCsv($osRawCsv, $vulnName, $desc, $output);
            $osDetected = $osResult['os_name'];

            if ($ip) {
                if (!isset($hostOsMap[$ip]) || $osResult['os_confidence'] > $hostOsMap[$ip]['os_confidence']) {
                    $hostOsMap[$ip] = [
                        'ip_address'        => $ip,
                        'hostname'          => $hostname,
                        'os_name'           => $osResult['os_name'],
                        'os_family'         => $osResult['os_family'],
                        'os_confidence'     => $osResult['os_confidence'],
                        'os_kernel'         => $osResult['os_kernel'],
                        'detection_sources' => $osResult['detection_sources'],
                    ];
                }
                if ($hostname && !$hostOsMap[$ip]['hostname']) {
                    $hostOsMap[$ip]['hostname'] = $hostname;
                }
            }

            $classification = VulnClassifier::classify(
                $vulnName, $desc, $osDetected, $portVal, $protoVal, $output, $cveVal
            );

            $rawDate = $col($line, ['first seen', 'first_seen', 'scan date', 'scan_date', 'date']);
            if ($rawDate) {
                $parsed = strtotime($rawDate);
                if ($parsed !== false) {
                    $dateStr = date('Y-m-d', $parsed);
                    if ($csvMinDate === null || $dateStr < $csvMinDate) {
                        $csvMinDate = $dateStr;
                    }
                }
            }

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
                'plugin_id'          => $pluginId,
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

            if (count($rows) >= $batchSize || memory_get_usage(true) > 160 * 1024 * 1024) {
                $flushRows($rows);
                $rows = [];
            }
        }

        fclose($handle);

        if (!empty($rows)) {
            $flushRows($rows);
        }

        return [
            'hostOsMap' => $hostOsMap,
            'scanName'  => null,
            'scanDate'  => $csvMinDate,
        ];
    }
}
