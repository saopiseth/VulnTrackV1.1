<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class NmapParserService
{
    /**
     * Parse an Nmap scan file and return discovered hosts with open ports.
     *
     * Returns: [ 'ip' => [['port'=>int,'protocol'=>str,'service'=>str], ...], ... ]
     */
    public function parse(string $storagePath, string $extension): array
    {
        $content = Storage::disk('local')->get($storagePath);

        if ($extension === 'xml') {
            return $this->parseXml($content);
        }

        // .nmap / .txt — try XML first (gnmap -oX piped to .txt), else greppable text
        if (str_starts_with(ltrim($content), '<?xml') || str_contains($content, '<nmaprun')) {
            return $this->parseXml($content);
        }

        return $this->parseText($content);
    }

    /**
     * Derive /24 subnet string from an IP address. Returns e.g. "10.10.10.0/24".
     */
    public function getSubnet(string $ip, int $prefix = 24): string
    {
        $long = ip2long($ip);
        if ($long === false) {
            return '0.0.0.0/24';
        }
        $mask    = ~((1 << (32 - $prefix)) - 1);
        $network = $long & $mask;
        return long2ip($network) . '/' . $prefix;
    }

    // ── Private parsers ───────────────────────────────────────────────────────

    private function parseXml(string $content): array
    {
        $prev = libxml_use_internal_errors(true);
        $xml  = simplexml_load_string($content);
        libxml_use_internal_errors($prev);

        if ($xml === false) {
            return [];
        }

        $hosts = [];

        foreach ($xml->host as $host) {
            // Skip hosts reported as down
            $state = (string) ($host->status['state'] ?? '');
            if ($state !== '' && $state !== 'up') {
                continue;
            }

            $ip = null;
            foreach ($host->address as $addr) {
                if ((string) $addr['addrtype'] === 'ipv4') {
                    $ip = (string) $addr['addr'];
                    break;
                }
            }

            if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }

            $ports = [];

            if (isset($host->ports->port)) {
                foreach ($host->ports->port as $port) {
                    if ((string) $port->state['state'] !== 'open') {
                        continue;
                    }

                    $svcName = (string) ($port->service['name'] ?? '');
                    if ($svcName === '') {
                        $svcName = 'unknown';
                    }

                    $ports[] = [
                        'port'     => (int) $port['portid'],
                        'protocol' => (string) $port['protocol'],
                        'service'  => $svcName,
                    ];
                }
            }

            $hosts[$ip] = $ports;
        }

        return $hosts;
    }

    private function parseText(string $content): array
    {
        $hosts     = [];
        $currentIp = null;

        foreach (explode("\n", $content) as $rawLine) {
            $line = trim($rawLine);

            // "Nmap scan report for 10.10.10.5" or "Nmap scan report for hostname (10.10.10.5)"
            if (preg_match('/^Nmap scan report for (?:\S+ \()?(\d{1,3}(?:\.\d{1,3}){3})\)?/', $line, $m)) {
                $currentIp = $m[1];
                if (!isset($hosts[$currentIp])) {
                    $hosts[$currentIp] = [];
                }
                continue;
            }

            // "Host is up" keeps currentIp alive; "Host is down" clears it
            if ($currentIp && str_starts_with($line, 'Host is down')) {
                unset($hosts[$currentIp]);
                $currentIp = null;
                continue;
            }

            // Port line: "22/tcp open  ssh" or "3389/tcp open  ms-wbt-server"
            if ($currentIp && preg_match('/^(\d+)\/(tcp|udp)\s+open\s+(\S+)/', $line, $m)) {
                $hosts[$currentIp][] = [
                    'port'     => (int) $m[1],
                    'protocol' => $m[2],
                    'service'  => $m[3],
                ];
            }
        }

        return $hosts;
    }
}
