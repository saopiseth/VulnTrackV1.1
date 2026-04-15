<?php

namespace App\Services;

/**
 * Detects, normalises and classifies Operating Systems from Nessus scan data.
 *
 * Detection sources (priority order, highest confidence first):
 *   1. HostProperties tag "operating-system"        → confidence 95
 *   2. HostProperties tag "os"                      → confidence 90
 *   3. CPE strings (cpe:/o: prefix)                 → confidence 85
 *   4. OS Identification plugin output (11936,45590) → confidence 80
 *   5. Plugin name + description keyword match       → confidence 60
 *   6. Service-banner / version detection            → confidence 40
 *
 * Returns: ['os_name', 'os_family', 'os_confidence', 'os_kernel', 'detection_sources']
 */
class OsDetector
{
    // OS families
    const FAM_WINDOWS = 'Windows';
    const FAM_LINUX   = 'Linux';
    const FAM_UNIX    = 'Unix';
    const FAM_OTHER   = 'Other';

    // ─────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────

    /**
     * Full detection from XML/Nessus host data.
     *
     * @param  object  $hostProperties   SimpleXML HostProperties element
     * @param  array   $reportItems      array of SimpleXML ReportItem elements
     * @return array{os_name:string|null,os_family:string,os_confidence:int,os_kernel:string|null,detection_sources:array}
     */
    public static function detectFromXml(object $hostProperties, array $reportItems): array
    {
        $candidates  = [];
        $rawOsFull   = '';   // full multi-line operating-system tag value for kernel extraction

        // ── Source 1 & 2: HostProperties tags ────────────────
        $hpOs = trim((string) ($hostProperties->xpath('tag[@name="operating-system"]')[0] ?? ''));
        if ($hpOs) {
            $rawOsFull = $hpOs;
            $line = trim(explode("\n", $hpOs)[0]);
            $candidates[] = ['raw' => $line, 'confidence' => 95, 'source' => 'HostProperties:operating-system'];
        }

        $hpOsShort = trim((string) ($hostProperties->xpath('tag[@name="os"]')[0] ?? ''));
        if ($hpOsShort && $hpOsShort !== $hpOs) {
            $line = trim(explode("\n", $hpOsShort)[0]);
            $candidates[] = ['raw' => $line, 'confidence' => 90, 'source' => 'HostProperties:os'];
        }

        // ── Source 3: CPE strings ─────────────────────────────
        foreach ($hostProperties->xpath('tag[starts-with(@name,"cpe")]') as $cpeTag) {
            $cpe = strtolower(trim((string) $cpeTag));
            if (str_starts_with($cpe, 'cpe:/o:')) {
                $fromCpe = self::parseCpe($cpe);
                if ($fromCpe) {
                    $candidates[] = ['raw' => $fromCpe, 'confidence' => 85, 'source' => 'CPE'];
                }
            }
        }

        // ── Source 4: OS-identification plugin output ─────────
        foreach ($reportItems as $item) {
            $pluginId   = (string) ($item['pluginID'] ?? '');
            $pluginName = strtolower((string) ($item['pluginName'] ?? ''));

            // Plugin 11936 = OS identification, 45590 = OS reachability test
            $isOsPlugin = in_array($pluginId, ['11936', '45590', '45003'])
                || str_contains($pluginName, 'os identification')
                || str_contains($pluginName, 'operating system identification');

            if ($isOsPlugin) {
                $output = (string) ($item->plugin_output ?? '');
                $parsed = self::parseOsFromPluginOutput($output);
                if ($parsed) {
                    $candidates[] = ['raw' => $parsed, 'confidence' => 80, 'source' => 'Plugin:OsIdentification'];
                }
                // Also check CPE tags inside the item
                foreach ($item->cpe ?? [] as $cpe) {
                    $cpeStr = strtolower(trim((string) $cpe));
                    if (str_starts_with($cpeStr, 'cpe:/o:')) {
                        $fromCpe = self::parseCpe($cpeStr);
                        if ($fromCpe) {
                            $candidates[] = ['raw' => $fromCpe, 'confidence' => 82, 'source' => 'CPE:OsPlugin'];
                        }
                    }
                }
                break;
            }
        }

        // ── Source 5: Plugin name/description keyword scan ───
        foreach ($reportItems as $item) {
            $name = strtolower((string) ($item['pluginName'] ?? ''));
            $desc = strtolower((string) ($item->description ?? ''));
            $haystack = $name . ' ' . $desc;

            $matched = self::matchOsKeywords($haystack);
            if ($matched) {
                $candidates[] = ['raw' => $matched, 'confidence' => 60, 'source' => 'PluginName'];
                break;
            }
        }

        $result = self::resolveBest($candidates);

        // ── Kernel / build extraction ─────────────────────────────────────────
        // Sources are searched in priority order; first non-null result wins.
        //
        // Linux/Unix kernel sources (Nessus real-world order):
        //   1. HostProperties tag[name="kernel"]           e.g. "5.4.0-74-generic"
        //   2. HostProperties tag[name="operating-system"] e.g. "Linux Kernel 5.4.0-74-generic #83-Ubuntu SMP…\nUbuntu 20.04.2"
        //   3. Plugin 11936 (OS Identification) output     e.g. "Remote operating system : Linux Kernel 5.4.0-74-generic"
        //   4. Plugin 25221 (Remote listeners) output
        //
        // Windows build sources:
        //   1. HostProperties tag[name="msrpc-windows-version"] e.g. "10.0.19041"
        //   2. HostProperties tag[name="operating-system"]      e.g. "Microsoft Windows 10 Enterprise 10.0.19041 Build 19041"
        //   3. Plugin 10785 (SMB NativeLanManager) output       e.g. "The Windows version is : 10.0.19041"
        //   4. Plugin 25221 (Remote listeners) output           e.g. "Windows 10 Build 19041"
        //   5. Plugin 11936 output

        $osFamily = $result['os_family'];

        // Index plugin outputs we care about (avoid iterating twice)
        $pluginOutputMap = [];
        foreach ($reportItems as $item) {
            $pid = (string) ($item['pluginID'] ?? '');
            if (in_array($pid, ['10785', '11936', '25221', '45590', '45003'])
                && !isset($pluginOutputMap[$pid])) {
                $pluginOutputMap[$pid] = (string) ($item->plugin_output ?? '');
            }
        }

        $result['os_kernel'] = null;

        if ($osFamily === self::FAM_WINDOWS) {
            // Source 1: dedicated msrpc-windows-version tag
            $msrpc = trim((string) ($hostProperties->xpath('tag[@name="msrpc-windows-version"]')[0] ?? ''));
            if ($msrpc && $k = self::extractWindowsBuild($msrpc)) {
                $result['os_kernel'] = $k;
            }
            // Source 2: operating-system tag (some Nessus versions include build inline)
            if (!$result['os_kernel'] && $rawOsFull
                && $k = self::extractWindowsBuild($rawOsFull)) {
                $result['os_kernel'] = $k;
            }
            // Source 3: Plugin 10785 — most reliable Windows build source
            if (!$result['os_kernel'] && isset($pluginOutputMap['10785'])
                && $k = self::extractWindowsBuild($pluginOutputMap['10785'])) {
                $result['os_kernel'] = $k;
            }
            // Source 4: Plugin 25221
            if (!$result['os_kernel'] && isset($pluginOutputMap['25221'])
                && $k = self::extractWindowsBuild($pluginOutputMap['25221'])) {
                $result['os_kernel'] = $k;
            }
            // Source 5: Plugin 11936
            if (!$result['os_kernel'] && isset($pluginOutputMap['11936'])
                && $k = self::extractWindowsBuild($pluginOutputMap['11936'])) {
                $result['os_kernel'] = $k;
            }
        } else {
            // Linux / Unix
            // Source 1: dedicated kernel tag (rare but unambiguous)
            $hpKernel = trim((string) ($hostProperties->xpath('tag[@name="kernel"]')[0] ?? ''));
            if ($hpKernel && $k = self::extractLinuxKernel($hpKernel)) {
                $result['os_kernel'] = $k;
            }
            // Source 2: operating-system tag (first line often "Linux Kernel X.X.X-XX-generic #…")
            if (!$result['os_kernel'] && $rawOsFull
                && $k = self::extractLinuxKernel($rawOsFull)) {
                $result['os_kernel'] = $k;
            }
            // Source 3: Plugin 11936
            if (!$result['os_kernel'] && isset($pluginOutputMap['11936'])
                && $k = self::extractLinuxKernel($pluginOutputMap['11936'])) {
                $result['os_kernel'] = $k;
            }
            // Source 4: Plugin 25221
            if (!$result['os_kernel'] && isset($pluginOutputMap['25221'])
                && $k = self::extractLinuxKernel($pluginOutputMap['25221'])) {
                $result['os_kernel'] = $k;
            }
        }

        return $result;
    }

    /**
     * Detection from CSV row data.
     *
     * @param  string|null  $osRaw     raw OS column value
     * @param  string|null  $pluginName
     * @param  string|null  $desc
     * @param  string|null  $pluginOutput
     * @return array
     */
    public static function detectFromCsv(?string $osRaw, ?string $pluginName, ?string $desc, ?string $pluginOutput): array
    {
        $candidates = [];

        if ($osRaw) {
            $candidates[] = ['raw' => trim($osRaw), 'confidence' => 90, 'source' => 'CSV:os_column'];
        }

        if ($pluginOutput) {
            $parsed = self::parseOsFromPluginOutput($pluginOutput);
            if ($parsed) {
                $candidates[] = ['raw' => $parsed, 'confidence' => 75, 'source' => 'CSV:plugin_output'];
            }
        }

        $haystack = strtolower(($pluginName ?? '') . ' ' . ($desc ?? ''));
        $matched = self::matchOsKeywords($haystack);
        if ($matched) {
            $candidates[] = ['raw' => $matched, 'confidence' => 55, 'source' => 'CSV:PluginName'];
        }

        $result = self::resolveBest($candidates);

        // Kernel/build from os_raw column or plugin output, family-aware
        $result['os_kernel'] = null;
        foreach (array_filter([$osRaw, $pluginOutput]) as $src) {
            if ($result['os_family'] === self::FAM_WINDOWS) {
                $k = self::extractWindowsBuild($src);
            } else {
                $k = self::extractLinuxKernel($src);
            }
            if ($k) { $result['os_kernel'] = $k; break; }
        }

        return $result;
    }

    /**
     * Return OS family + icon for rendering.
     */
    public static function familyMeta(string $family): array
    {
        return match ($family) {
            self::FAM_WINDOWS => ['color' => '#1e40af', 'bg' => '#dbeafe', 'icon' => 'bi-windows',         'label' => 'Windows Component'],
            self::FAM_LINUX   => ['color' => '#065f46', 'bg' => '#d1fae5', 'icon' => 'bi-ubuntu',          'label' => 'Linux OS Component'],
            self::FAM_UNIX    => ['color' => '#7c2d12', 'bg' => '#ffedd5', 'icon' => 'bi-terminal-fill',   'label' => 'Unix-based OS'],
            default           => ['color' => '#374151', 'bg' => '#f3f4f6', 'icon' => 'bi-cpu-fill',        'label' => 'Other'],
        };
    }

    // ─────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────

    /** Resolve the best candidate and normalise */
    private static function resolveBest(array $candidates): array
    {
        if (empty($candidates)) {
            return ['os_name' => null, 'os_family' => self::FAM_OTHER, 'os_confidence' => 0, 'os_kernel' => null, 'detection_sources' => []];
        }

        // Sort by confidence desc
        usort($candidates, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        $best    = $candidates[0];
        $sources = array_unique(array_column($candidates, 'source'));

        $normalised = self::normalise($best['raw']);

        return [
            'os_name'           => $normalised['os_name'],
            'os_family'         => $normalised['os_family'],
            'os_confidence'     => min(100, $best['confidence']),
            'os_kernel'         => null,   // filled by caller after kernel extraction
            'detection_sources' => array_values($sources),
        ];
    }

    /**
     * Extract Linux/Unix kernel version from raw text.
     *
     * Nessus sources this appears in:
     *   HostProperties tag[name="kernel"]           → "5.4.0-74-generic"
     *   HostProperties tag[name="operating-system"] → "Linux Kernel 5.4.0-74-generic #83-Ubuntu SMP…"
     *   Plugin 11936 output                         → "Remote operating system : Linux Kernel 5.4.0-74-generic"
     *
     * Returns only the version token, e.g. "5.4.0-74-generic".
     */
    public static function extractLinuxKernelPublic(string $raw): ?string { return self::extractLinuxKernel($raw); }
    public static function extractWindowsBuildPublic(string $raw): ?string { return self::extractWindowsBuild($raw); }

    private static function extractLinuxKernel(string $raw): ?string
    {
        // "Linux Kernel 5.4.0-74-generic #83-Ubuntu SMP …" — stop before the SMP comment
        if (preg_match('/(?:linux\s+)?kernel\s+(\d+\.\d+\.\d+[^\s#]*)/i', $raw, $m)) {
            return rtrim($m[1], '-.');
        }
        // Bare version that looks like a kernel flavour (e.g. "5.15.0-91-generic", "4.18.0-305.el8.x86_64")
        if (preg_match('/\b(\d+\.\d+\.\d+[-.](?:generic|amd64|server|default|azure|aws|gcp|x86_64|el\d+[\w.]*|rt[\w.]*))\b/i', $raw, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Extract Windows build number from raw text.
     *
     * Nessus sources this appears in:
     *   HostProperties tag[name="msrpc-windows-version"] → "10.0.19041"
     *   HostProperties tag[name="operating-system"]      → "Microsoft Windows 10 Enterprise 10.0.19041 Build 19041"
     *   Plugin 10785 output                              → "The Windows version is : 10.0.19041"
     *   Plugin 25221 output                              → "Windows 10 Build 19041"
     *
     * Returns "Build NNNNN", e.g. "Build 19041".
     */
    private static function extractWindowsBuild(string $raw): ?string
    {
        // Explicit "Build 19041" or "Build: 19041.1415"
        if (preg_match('/\bbuild[:\s]+(\d{4,5}(?:\.\d+)*)/i', $raw, $m)) {
            return 'Build ' . $m[1];
        }
        // NT version string "10.0.19041" or "6.1.7601" — build is the third segment
        // Matches "The Windows version is : 10.0.19041" and inline "10.0.19041 Build …"
        if (preg_match('/\b\d+\.\d+\.(\d{4,5})\b/i', $raw, $m)) {
            return 'Build ' . $m[1];
        }
        // Service Pack fallback for older Windows
        if (preg_match('/service\s+pack\s+(\d)/i', $raw, $m)) {
            return 'SP' . $m[1];
        }
        return null;
    }

    /**
     * Parse a cpe:/o: string → human OS name
     * e.g. cpe:/o:microsoft:windows_server_2019 → Windows Server 2019
     */
    private static function parseCpe(string $cpe): ?string
    {
        // cpe:/o:vendor:product:version
        $parts = explode(':', $cpe);
        // parts[0]=cpe, [1]=/o, [2]=vendor, [3]=product, [4]=version
        $vendor  = $parts[2] ?? '';
        $product = str_replace('_', ' ', $parts[3] ?? '');
        $version = str_replace('_', '.', $parts[4] ?? '');

        // Microsoft
        if ($vendor === 'microsoft') {
            $p = strtolower($product);
            if (str_contains($p, 'windows server')) {
                return 'Windows Server' . ($version ? ' ' . self::cleanVersion($version) : '');
            }
            if (str_contains($p, 'windows 10')) return 'Windows 10';
            if (str_contains($p, 'windows 11')) return 'Windows 11';
            if (str_contains($p, 'windows 7'))  return 'Windows 7';
            if (str_contains($p, 'windows xp')) return 'Windows XP';
            if (str_contains($p, 'windows'))    return 'Microsoft Windows' . ($version ? ' ' . self::cleanVersion($version) : '');
            return 'Microsoft ' . ucwords($product);
        }

        // Linux distros
        $linuxMap = [
            'ubuntu'    => 'Ubuntu',     'debian'  => 'Debian',
            'centos'    => 'CentOS',     'redhat'  => 'Red Hat Enterprise Linux',
            'red_hat'   => 'Red Hat Enterprise Linux',
            'rhel'      => 'Red Hat Enterprise Linux',
            'rocky'     => 'Rocky Linux','almalinux' => 'AlmaLinux',
            'fedora'    => 'Fedora',     'suse'    => 'SUSE Linux',
            'opensuse'  => 'openSUSE',   'amazon'  => 'Amazon Linux',
            'kali'      => 'Kali Linux', 'arch'    => 'Arch Linux',
            'linux'     => 'Linux',
        ];
        foreach ($linuxMap as $key => $name) {
            if (str_contains($vendor, $key) || str_contains(strtolower($product), $key)) {
                return $name . ($version && $version !== '-' ? ' ' . self::cleanVersion($version) : '');
            }
        }

        // Unix
        $unixMap = [
            'sun'       => 'Solaris',    'oracle:solaris' => 'Solaris',
            'ibm:aix'   => 'IBM AIX',    'hp:hp-ux'       => 'HP-UX',
            'freebsd'   => 'FreeBSD',    'openbsd'        => 'OpenBSD',
            'netbsd'    => 'NetBSD',
        ];
        foreach ($unixMap as $key => $name) {
            if (str_contains($cpe, $key)) {
                return $name . ($version && $version !== '-' ? ' ' . self::cleanVersion($version) : '');
            }
        }

        // Network / specialised
        $specialMap = [
            'cisco'   => 'Cisco IOS',   'juniper' => 'Juniper JunOS',
            'fortinet'=> 'Fortinet FortiOS', 'paloalto' => 'Palo Alto PAN-OS',
            'vmware'  => 'VMware ESXi', 'apple'   => 'macOS',
        ];
        foreach ($specialMap as $key => $name) {
            if (str_contains($vendor, $key)) {
                return $name . ($version && $version !== '-' ? ' ' . self::cleanVersion($version) : '');
            }
        }

        return ucwords($product) ?: null;
    }

    /** Parse OS name from plugin output text */
    private static function parseOsFromPluginOutput(string $output): ?string
    {
        $patterns = [
            '/remote operating system\s*:\s*(.+)/i',
            '/operating system\s*:\s*(.+)/i',
            '/os\s*:\s*(.+)/i',
            '/running\s+(.+?)\s+(?:linux|windows|unix|os)/i',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $output, $m)) {
                return trim($m[1]);
            }
        }
        return null;
    }

    /**
     * Keyword scan of plugin name / description to guess OS.
     * Returns normalised name or null.
     */
    private static function matchOsKeywords(string $haystack): ?string
    {
        $rules = [
            // Windows — specific first
            'windows server 2022'        => 'Windows Server 2022',
            'windows server 2019'        => 'Windows Server 2019',
            'windows server 2016'        => 'Windows Server 2016',
            'windows server 2012'        => 'Windows Server 2012',
            'windows server 2008'        => 'Windows Server 2008',
            'windows server 2003'        => 'Windows Server 2003',
            'windows 11'                 => 'Windows 11',
            'windows 10'                 => 'Windows 10',
            'windows 7'                  => 'Windows 7',
            'windows xp'                 => 'Windows XP',
            'windows server'             => 'Windows Server',
            'microsoft windows'          => 'Microsoft Windows',
            // Linux distros
            'ubuntu 24'                  => 'Ubuntu 24.x',
            'ubuntu 22'                  => 'Ubuntu 22.04 LTS',
            'ubuntu 20'                  => 'Ubuntu 20.04 LTS',
            'ubuntu 18'                  => 'Ubuntu 18.04 LTS',
            'ubuntu'                     => 'Ubuntu Linux',
            'debian 12'                  => 'Debian 12 (Bookworm)',
            'debian 11'                  => 'Debian 11 (Bullseye)',
            'debian 10'                  => 'Debian 10 (Buster)',
            'debian'                     => 'Debian Linux',
            'centos 8'                   => 'CentOS 8',
            'centos 7'                   => 'CentOS 7',
            'centos'                     => 'CentOS Linux',
            'red hat enterprise linux 9' => 'RHEL 9',
            'red hat enterprise linux 8' => 'RHEL 8',
            'red hat enterprise linux 7' => 'RHEL 7',
            'red hat enterprise linux'   => 'Red Hat Enterprise Linux',
            'rhel'                       => 'Red Hat Enterprise Linux',
            'rocky linux'                => 'Rocky Linux',
            'almalinux'                  => 'AlmaLinux',
            'fedora'                     => 'Fedora Linux',
            'opensuse'                   => 'openSUSE',
            'suse linux enterprise'      => 'SUSE Linux Enterprise',
            'suse'                       => 'SUSE Linux',
            'amazon linux 2023'          => 'Amazon Linux 2023',
            'amazon linux 2'             => 'Amazon Linux 2',
            'amazon linux'               => 'Amazon Linux',
            'kali linux'                 => 'Kali Linux',
            'arch linux'                 => 'Arch Linux',
            // Unix-like
            'oracle solaris 11'          => 'Solaris 11',
            'oracle solaris 10'          => 'Solaris 10',
            'solaris'                    => 'Solaris',
            'ibm aix'                    => 'IBM AIX',
            'aix'                        => 'IBM AIX',
            'hp-ux'                      => 'HP-UX',
            'freebsd'                    => 'FreeBSD',
            'openbsd'                    => 'OpenBSD',
            'netbsd'                     => 'NetBSD',
            // Specialised
            'macos'                      => 'macOS',
            'mac os x'                   => 'macOS',
            'vmware esxi 8'              => 'VMware ESXi 8',
            'vmware esxi 7'              => 'VMware ESXi 7',
            'vmware esxi 6'              => 'VMware ESXi 6',
            'vmware esxi'                => 'VMware ESXi',
            'cisco ios-xe'               => 'Cisco IOS-XE',
            'cisco ios'                  => 'Cisco IOS',
            'cisco nx-os'                => 'Cisco NX-OS',
            'cisco asa'                  => 'Cisco ASA',
            'junos'                      => 'Juniper JunOS',
            'fortigate'                  => 'Fortinet FortiOS',
            'fortios'                    => 'Fortinet FortiOS',
            'palo alto'                  => 'Palo Alto PAN-OS',
            'pan-os'                     => 'Palo Alto PAN-OS',
        ];

        foreach ($rules as $keyword => $name) {
            if (str_contains($haystack, $keyword)) {
                return $name;
            }
        }
        return null;
    }

    /**
     * Normalise raw OS string → [os_name, os_family]
     */
    public static function normalise(string $raw): array
    {
        $lower = strtolower(trim($raw));

        // ── Windows ──────────────────────────────────────────
        if (preg_match('/windows|microsoft\s+win/i', $raw)) {
            $name = self::normaliseWindows($raw);
            return ['os_name' => $name, 'os_family' => self::FAM_WINDOWS];
        }

        // ── Linux ─────────────────────────────────────────────
        $linuxPatterns = [
            'ubuntu', 'debian', 'centos', 'red hat', 'rhel', 'rocky linux',
            'almalinux', 'fedora', 'suse', 'opensuse', 'amazon linux',
            'kali linux', 'arch linux', 'linux',
        ];
        foreach ($linuxPatterns as $p) {
            if (str_contains($lower, $p)) {
                return ['os_name' => self::normaliseLinux($raw), 'os_family' => self::FAM_LINUX];
            }
        }

        // ── Unix ─────────────────────────────────────────────
        $unixPatterns = ['solaris', 'aix', 'hp-ux', 'freebsd', 'openbsd', 'netbsd', 'unix'];
        foreach ($unixPatterns as $p) {
            if (str_contains($lower, $p)) {
                return ['os_name' => self::normaliseUnix($raw), 'os_family' => self::FAM_UNIX];
            }
        }

        // ── macOS ─────────────────────────────────────────────
        if (str_contains($lower, 'macos') || str_contains($lower, 'mac os')) {
            return ['os_name' => 'macOS', 'os_family' => self::FAM_OTHER];
        }

        // ── VMware ───────────────────────────────────────────
        if (str_contains($lower, 'vmware') || str_contains($lower, 'esxi')) {
            return ['os_name' => self::extractVersion($raw, 'VMware ESXi'), 'os_family' => self::FAM_OTHER];
        }

        // ── Network OS ───────────────────────────────────────
        if (str_contains($lower, 'cisco ios-xe'))     return ['os_name' => 'Cisco IOS-XE',          'os_family' => self::FAM_OTHER];
        if (str_contains($lower, 'cisco ios'))        return ['os_name' => 'Cisco IOS',             'os_family' => self::FAM_OTHER];
        if (str_contains($lower, 'cisco nx-os'))      return ['os_name' => 'Cisco NX-OS',           'os_family' => self::FAM_OTHER];
        if (str_contains($lower, 'cisco asa'))        return ['os_name' => 'Cisco ASA',             'os_family' => self::FAM_OTHER];
        if (str_contains($lower, 'cisco'))            return ['os_name' => 'Cisco',                 'os_family' => self::FAM_OTHER];
        if (str_contains($lower, 'junos') || str_contains($lower, 'juniper')) return ['os_name' => 'Juniper JunOS', 'os_family' => self::FAM_OTHER];
        if (str_contains($lower, 'fortios') || str_contains($lower, 'fortigate')) return ['os_name' => 'Fortinet FortiOS', 'os_family' => self::FAM_OTHER];
        if (str_contains($lower, 'pan-os') || str_contains($lower, 'palo alto')) return ['os_name' => 'Palo Alto PAN-OS', 'os_family' => self::FAM_OTHER];

        // Fallback — keep original, family = Other
        return ['os_name' => ucfirst(trim($raw)), 'os_family' => self::FAM_OTHER];
    }

    private static function normaliseWindows(string $raw): string
    {
        $r = trim($raw);

        // Strip leading "Microsoft " if followed by "Windows"
        $r = preg_replace('/^microsoft\s+/i', '', $r);

        if (preg_match('/windows server\s+(\d{4})\s*(?:r2)?/i', $r, $m)) {
            $suffix = stripos($r, 'r2') !== false ? ' R2' : '';
            return 'Windows Server ' . $m[1] . $suffix;
        }
        if (preg_match('/windows\s+(\d+)/i', $r, $m)) {
            return 'Windows ' . $m[1];
        }
        if (preg_match('/windows\s+xp/i', $r)) return 'Windows XP';
        if (preg_match('/windows\s+vista/i', $r)) return 'Windows Vista';

        return ucwords(strtolower($r));
    }

    private static function normaliseLinux(string $raw): string
    {
        $r = trim($raw);

        // Ubuntu
        if (preg_match('/ubuntu\s+(\d+[\.\d]*)/i', $r, $m)) return 'Ubuntu ' . $m[1];
        if (preg_match('/ubuntu/i', $r)) return 'Ubuntu Linux';

        // Debian
        if (preg_match('/debian\s+(\d+[\.\d]*)/i', $r, $m)) return 'Debian ' . $m[1];
        if (preg_match('/debian/i', $r)) return 'Debian Linux';

        // CentOS
        if (preg_match('/centos\s+(\d+[\.\d]*)/i', $r, $m)) return 'CentOS ' . $m[1];
        if (preg_match('/centos/i', $r)) return 'CentOS Linux';

        // RHEL
        if (preg_match('/red\s*hat\s+enterprise\s+linux\s+(\d+)/i', $r, $m)) return 'RHEL ' . $m[1];
        if (preg_match('/rhel\s+(\d+)/i', $r, $m)) return 'RHEL ' . $m[1];
        if (preg_match('/red\s*hat/i', $r)) return 'Red Hat Enterprise Linux';

        // Rocky / Alma
        if (preg_match('/rocky\s+linux\s+(\d+)/i', $r, $m)) return 'Rocky Linux ' . $m[1];
        if (preg_match('/rocky/i', $r)) return 'Rocky Linux';
        if (preg_match('/almalinux\s+(\d+)/i', $r, $m)) return 'AlmaLinux ' . $m[1];
        if (preg_match('/almalinux/i', $r)) return 'AlmaLinux';

        // Fedora / SUSE / openSUSE / Amazon / Kali / Arch
        if (preg_match('/fedora\s+(\d+)/i', $r, $m)) return 'Fedora ' . $m[1];
        if (preg_match('/opensuse\s+([\d.]+)/i', $r, $m)) return 'openSUSE ' . $m[1];
        if (preg_match('/suse\s+linux\s+enterprise\s+(?:server\s+)?(\d+)/i', $r, $m)) return 'SLES ' . $m[1];
        if (preg_match('/amazon\s+linux\s+([\d.]+)/i', $r, $m)) return 'Amazon Linux ' . $m[1];
        if (preg_match('/amazon\s+linux/i', $r)) return 'Amazon Linux';
        if (preg_match('/kali\s+linux/i', $r)) return 'Kali Linux';
        if (preg_match('/arch\s+linux/i', $r)) return 'Arch Linux';

        // Generic Linux with kernel version
        if (preg_match('/linux\s+([\d.]+)/i', $r, $m)) return 'Linux ' . $m[1];

        return 'Linux';
    }

    private static function normaliseUnix(string $raw): string
    {
        $r = trim($raw);
        if (preg_match('/solaris\s+([\d.]+)/i', $r, $m)) return 'Solaris ' . $m[1];
        if (preg_match('/solaris/i', $r)) return 'Solaris';
        if (preg_match('/aix\s+([\d.]+)/i', $r, $m)) return 'IBM AIX ' . $m[1];
        if (preg_match('/aix/i', $r)) return 'IBM AIX';
        if (preg_match('/hp-ux\s+([\w.]+)/i', $r, $m)) return 'HP-UX ' . $m[1];
        if (preg_match('/hp-ux/i', $r)) return 'HP-UX';
        if (preg_match('/freebsd\s+([\d.]+)/i', $r, $m)) return 'FreeBSD ' . $m[1];
        if (preg_match('/freebsd/i', $r)) return 'FreeBSD';
        if (preg_match('/openbsd\s+([\d.]+)/i', $r, $m)) return 'OpenBSD ' . $m[1];
        if (preg_match('/netbsd/i', $r)) return 'NetBSD';
        return ucwords(strtolower($r));
    }

    private static function extractVersion(string $raw, string $prefix): string
    {
        if (preg_match('/(\d+[\.\d]*)/i', $raw, $m)) {
            return $prefix . ' ' . $m[1];
        }
        return $prefix;
    }

    private static function cleanVersion(string $v): string
    {
        // Remove trailing dots / dashes
        return rtrim(trim($v), '.-');
    }
}
