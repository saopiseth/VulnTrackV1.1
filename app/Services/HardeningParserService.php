<?php

namespace App\Services;

/**
 * Parses Nessus .nessus (XML) and .csv files for hardening/configuration findings.
 *
 * Compliance status mapping:
 *   - Nessus Policy Compliance plugins → PASSED=Compliant, FAILED=Non-Compliant,
 *     WARNING=Partially Compliant, ERROR=Not Applicable
 *   - Regular vulnerability severity → Critical/High=Non-Compliant,
 *     Medium=Partially Compliant, Low=Partially Compliant, Info=Not Applicable
 */
class HardeningParserService
{
    private const SEVERITY_MAP = [
        '0' => 'Info', '1' => 'Low', '2' => 'Medium', '3' => 'High', '4' => 'Critical',
    ];

    /**
     * Parse a Nessus XML file and return an array of finding rows.
     */
    public function parseXml(string $filePath, callable $flushRows): array
    {
        $reader = new \XMLReader();
        if (!@$reader->open($filePath)) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }

        $rows      = [];
        $seen      = [];
        $batchSize = 100;

        while ($reader->read()) {
            if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->name !== 'ReportHost') {
                continue;
            }

            $dom  = new \DOMDocument('1.0', 'UTF-8');
            $node = $dom->importNode($reader->expand(), true);
            $dom->appendChild($node);
            $host = simplexml_import_dom($node);

            foreach ($host->ReportItem ?? [] as $item) {
                $pluginId = (string) ($item['pluginID'] ?? '0');
                $port     = (string) ($item['port']     ?? '');
                $proto    = (string) ($item['protocol'] ?? '');

                $dedupeKey = $pluginId . '|' . $port . '|' . $proto;
                if (isset($seen[$dedupeKey])) {
                    continue;
                }
                $seen[$dedupeKey] = true;

                $sevRaw      = (string) ($item['severity'] ?? '0');
                $severity    = self::SEVERITY_MAP[$sevRaw] ?? 'Info';
                $pluginName  = (string) ($item['pluginName']  ?? 'Unknown');
                $pluginFamily= (string) ($item['pluginFamily'] ?? '');
                $desc        = (string) ($item->description ?? '');
                $solution    = (string) ($item->solution ?? '');
                $output      = mb_substr((string) ($item->plugin_output ?? ''), 0, 1_000_000);
                $cve         = (string) ($item->cve ?? '');
                $cvssRaw     = (string) ($item->cvss3_base_score ?? $item->cvss_base_score ?? '');
                $cvssScore   = $cvssRaw !== '' ? (float) $cvssRaw : null;
                $service     = (string) ($item['svc_name'] ?? '');

                [$complianceResult, $complianceStatus] = $this->resolveCompliance(
                    $pluginFamily, $output, $severity
                );

                $rows[] = [
                    'plugin_id'         => $pluginId,
                    'plugin_name'       => $pluginName,
                    'plugin_family'     => $pluginFamily,
                    'description'       => $desc,
                    'solution'          => $solution,
                    'plugin_output'     => $output,
                    'severity'          => $severity,
                    'cvss_score'        => $cvssScore,
                    'cve'               => $cve,
                    'port'              => $port,
                    'protocol'          => $proto,
                    'service'           => $service,
                    'compliance_result' => $complianceResult,
                    'compliance_status' => $complianceStatus,
                    'finding_key'       => sha1($pluginId . '|' . $port . '|' . $proto),
                ];

                if (count($rows) >= $batchSize || memory_get_usage(true) > 160 * 1024 * 1024) {
                    $flushRows($rows);
                    $rows = [];
                }
            }

            unset($dom, $node, $host);
        }

        $reader->close();

        if (!empty($rows)) {
            $flushRows($rows);
        }

        return [];
    }

    /**
     * Parse a Nessus CSV export and return finding rows.
     */
    public function parseCsv(string $filePath, callable $flushRows): array
    {
        $handle  = fopen($filePath, 'r');
        $headers = array_map(fn($h) => strtolower(trim($h)), fgetcsv($handle) ?: []);
        $rows    = [];
        $seen    = [];

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
            'critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium',
            'moderate' => 'Medium',   'low'  => 'Low',  'none'   => 'Info', 'info' => 'Info',
        ];

        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) < 2) continue;

            $pluginId    = $col($line, ['plugin id', 'plugin_id', 'pluginid']) ?: '0';
            $port        = $col($line, ['port']);
            $proto       = $col($line, ['protocol']);
            $dedupeKey   = $pluginId . '|' . $port . '|' . $proto;
            if (isset($seen[$dedupeKey])) continue;
            $seen[$dedupeKey] = true;

            $sevRaw      = $col($line, ['risk', 'severity', 'level']);
            $severity    = $sevNorm[strtolower($sevRaw)] ?? 'Info';
            $pluginName  = $col($line, ['name', 'plugin name', 'title', 'vulnerability']);
            $pluginFamily= $col($line, ['plugin family', 'plugin_family', 'family']);
            $desc        = $col($line, ['description', 'synopsis', 'detail']);
            $solution    = $col($line, ['solution', 'remediation', 'fix']);
            $output      = mb_substr($col($line, ['plugin output', 'plugin_output']), 0, 1_000_000);
            $cve         = $col($line, ['cve']);
            $cvssRaw     = $col($line, ['cvss3_base_score', 'cvss_base_score', 'cvss score', 'cvss_score']);
            $cvssScore   = $cvssRaw !== '' ? (float) $cvssRaw : null;

            [$complianceResult, $complianceStatus] = $this->resolveCompliance(
                $pluginFamily, $output, $severity
            );

            $rows[] = [
                'plugin_id'         => $pluginId,
                'plugin_name'       => $pluginName,
                'plugin_family'     => $pluginFamily,
                'description'       => $desc,
                'solution'          => $solution,
                'plugin_output'     => $output,
                'severity'          => $severity,
                'cvss_score'        => $cvssScore,
                'cve'               => $cve,
                'port'              => $port,
                'protocol'          => $proto,
                'service'           => '',
                'compliance_result' => $complianceResult,
                'compliance_status' => $complianceStatus,
                'finding_key'       => sha1($pluginId . '|' . $port . '|' . $proto),
            ];

            if (count($rows) >= 100 || memory_get_usage(true) > 160 * 1024 * 1024) {
                $flushRows($rows);
                $rows = [];
            }
        }

        fclose($handle);

        if (!empty($rows)) {
            $flushRows($rows);
        }

        return [];
    }

    /**
     * Determine compliance result + status from plugin family, output, and severity.
     *
     * Nessus Policy Compliance plugins write a result prefix into plugin_output:
     *   "PASSED: ..." / "FAILED: ..." / "WARNING: ..." / "ERROR: ..."
     *
     * For all other plugins the severity drives the status.
     *
     * @return array{0: string|null, 1: string}  [raw_result, normalized_status]
     */
    public function resolveCompliance(string $pluginFamily, string $pluginOutput, string $severity): array
    {
        $isCompliancePlugin = stripos($pluginFamily, 'Policy Compliance') !== false
            || stripos($pluginFamily, 'compliance') !== false;

        if ($isCompliancePlugin && $pluginOutput !== '') {
            $firstLine = strtoupper(trim(strtok($pluginOutput, "\n")));

            if (str_starts_with($firstLine, 'PASSED')) {
                return ['PASSED', 'Compliant'];
            }
            if (str_starts_with($firstLine, 'FAILED')) {
                return ['FAILED', 'Non-Compliant'];
            }
            if (str_starts_with($firstLine, 'WARNING')) {
                return ['WARNING', 'Partially Compliant'];
            }
            if (str_starts_with($firstLine, 'ERROR')) {
                return ['ERROR', 'Not Applicable'];
            }
        }

        // Fallback: map severity to compliance status
        return [null, match ($severity) {
            'Critical', 'High' => 'Non-Compliant',
            'Medium', 'Low'    => 'Partially Compliant',
            default            => 'Not Applicable',
        }];
    }
}
