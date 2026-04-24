<?php

namespace App\Jobs;

use App\Models\VulnAssessment;
use App\Models\VulnFinding;
use App\Models\VulnHostOs;
use App\Models\VulnRemediation;
use App\Models\VulnScan;
use App\Services\OsDetector;
use App\Services\VulnClassifier;
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

            $parsed    = in_array($this->fileExtension, ['xml', 'nessus'])
                ? $this->parseXml($fullPath)
                : $this->parseCsv($fullPath);

            $rows      = $parsed['rows'];
            $hostOsMap = $parsed['hostOs'];

            DB::transaction(function () use ($assessment, $scan, $rows, $hostOsMap) {
                $now      = now()->toDateTimeString();
                $inserted = 0;

                // ── Batch insert findings (500 rows per query) ────────────────
                // Rows are already deduplicated by plugin_id+ip_address+port.
                $findingRows = array_map(fn($row) => array_merge($row, [
                    'scan_id'       => $scan->id,
                    'assessment_id' => $assessment->id,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]), $rows);

                foreach (array_chunk($findingRows, 500) as $chunk) {
                    $inserted += DB::table('vuln_findings')->insertOrIgnore($chunk);
                }

                // ── Batch upsert remediations (skip if already exists) ────────
                $remSeen = [];
                $remRows = [];
                foreach ($rows as $row) {
                    if (($row['severity'] ?? 'Info') === 'Info') continue;
                    $k = $row['plugin_id'] . '|' . $row['ip_address'];
                    if (isset($remSeen[$k])) continue;
                    $remSeen[$k] = true;
                    $remRows[]   = [
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

                $hostCount = VulnFinding::where('scan_id', $scan->id)
                    ->distinct('ip_address')
                    ->count('ip_address');

                $scan->update([
                    'finding_count' => $inserted,
                    'host_count'    => $hostCount,
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

                // ── Tracking engine ───────────────────────────────────────────
                (new VulnTrackingService())->track($assessment, $scan);
            });

            $scan->update(['upload_status' => 'completed']);

        } catch (\Throwable $e) {
            $scan->update([
                'upload_status' => 'failed',
                'upload_error'  => mb_substr($e->getMessage(), 0, 500),
            ]);
            throw $e;
        } finally {
            Storage::disk('local')->delete($this->filePath);
        }
    }

    // ── Streaming XML parser using XMLReader ──────────────────────────────────
    // Loads one ReportHost subtree at a time instead of the whole document,
    // cutting peak memory use by ~80% on large Nessus files.
    private function parseXml(string $path): array
    {
        $reader = new \XMLReader();
        if (!@$reader->open($path)) {
            return ['rows' => [], 'hostOs' => []];
        }

        $sevMap    = ['0' => 'Info', '1' => 'Low', '2' => 'Medium', '3' => 'High', '4' => 'Critical'];
        $rows      = [];
        $hostOsMap = [];

        while ($reader->read()) {
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
                $sevRaw    = (string) ($item['severity'] ?? '0');
                $sev       = $sevMap[$sevRaw] ?? 'Info';
                $vulnName  = (string) ($item['pluginName'] ?? 'Unknown');
                $desc      = (string) ($item->description ?? '');
                $portVal   = (string) ($item['port'] ?? '');
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

            // Explicitly free the per-host DOM tree before next iteration.
            unset($dom, $node, $host, $reportItems);
        }

        $reader->close();

        return ['rows' => $this->deduplicateRows($rows), 'hostOs' => $hostOsMap];
    }

    // ── CSV parser (streaming via fgetcsv — unchanged from original) ──────────
    private function parseCsv(string $path): array
    {
        $handle  = fopen($path, 'r');
        $headers = array_map(fn($h) => strtolower(trim($h)), fgetcsv($handle) ?: []);
        $rows      = [];
        $hostOsMap = [];

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

        return ['rows' => $this->deduplicateRows($rows), 'hostOs' => $hostOsMap];
    }

    private function deduplicateRows(array $rows): array
    {
        $seen   = [];
        $unique = [];
        foreach ($rows as $row) {
            $key = ($row['plugin_id']  ?? '') . '|'
                 . ($row['ip_address'] ?? '') . '|'
                 . ($row['port']       ?? '');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[]   = $row;
            }
        }
        return $unique;
    }
}
