<?php

namespace App\Services;

/**
 * Classifies a vulnerability finding into a category and identifies the
 * affected component (OS, Application, Database, Web Server, etc.)
 * based on plugin name, description, OS detected, port, CVE, and plugin output.
 */
class VulnClassifier
{
    // ── Categories ────────────────────────────────────────────
    const CAT_OS         = 'OS';
    const CAT_APPLICATION= 'Application';
    const CAT_DATABASE   = 'Database';
    const CAT_WEB_SERVER = 'Web Server';
    const CAT_NETWORK    = 'Network';
    const CAT_SSL_TLS    = 'SSL/TLS';
    const CAT_POLICY     = 'Policy';
    const CAT_OTHER      = 'Other';

    /**
     * Classify and return ['category' => string, 'affected_component' => string|null]
     */
    public static function classify(
        string  $vulnName,
        ?string $description,
        ?string $osDetected,
        ?string $port,
        ?string $protocol,
        ?string $pluginOutput,
        ?string $cve
    ): array {
        $haystack = strtolower(
            $vulnName . ' ' .
            ($description   ?? '') . ' ' .
            ($pluginOutput  ?? '')
        );

        // ── 1. SSL / TLS (check before network — more specific) ──
        if (self::matchesAny($haystack, [
            'ssl', 'tls', 'certificate', 'x.509', 'https', 'starttls',
            'weak cipher', 'cipher suite', 'poodle', 'beast', 'heartbleed',
            'self-signed', 'expired cert', 'certificate expir',
        ])) {
            return [
                'category'           => self::CAT_SSL_TLS,
                'affected_component' => self::extractSslComponent($haystack, $vulnName),
            ];
        }

        // ── 2. Database ───────────────────────────────────────
        $dbMatch = self::matchNamedComponent($haystack, [
            'mysql'        => 'MySQL',
            'mariadb'      => 'MariaDB',
            'mssql'        => 'Microsoft SQL Server',
            'sql server'   => 'Microsoft SQL Server',
            'oracle db'    => 'Oracle Database',
            'oracle datab' => 'Oracle Database',
            'postgresql'   => 'PostgreSQL',
            'postgres'     => 'PostgreSQL',
            'mongodb'      => 'MongoDB',
            'redis'        => 'Redis',
            'elasticsearch'=> 'Elasticsearch',
            'cassandra'    => 'Cassandra',
        ]);
        if ($dbMatch) {
            return ['category' => self::CAT_DATABASE, 'affected_component' => $dbMatch];
        }
        // DB ports fallback
        if (in_array($port, ['1433','3306','5432','1521','27017','6379','9200','7000','5984'])) {
            $portMap = ['1433'=>'Microsoft SQL Server','3306'=>'MySQL','5432'=>'PostgreSQL',
                        '1521'=>'Oracle Database','27017'=>'MongoDB','6379'=>'Redis',
                        '9200'=>'Elasticsearch'];
            return [
                'category'           => self::CAT_DATABASE,
                'affected_component' => $portMap[$port] ?? 'Database Service',
            ];
        }

        // ── 3. Web Server ─────────────────────────────────────
        $webMatch = self::matchNamedComponent($haystack, [
            'apache http'   => 'Apache HTTP Server',
            'apache tomcat' => 'Apache Tomcat',
            'nginx'         => 'Nginx',
            'iis'           => 'Microsoft IIS',
            'internet information' => 'Microsoft IIS',
            'lighttpd'      => 'Lighttpd',
            'jetty'         => 'Jetty',
            'jboss'         => 'JBoss',
            'weblogic'      => 'Oracle WebLogic',
            'websphere'     => 'IBM WebSphere',
            'glassfish'     => 'GlassFish',
            'haproxy'       => 'HAProxy',
            'caddy'         => 'Caddy',
        ]);
        if ($webMatch) {
            return ['category' => self::CAT_WEB_SERVER, 'affected_component' => $webMatch];
        }
        // Web ports fallback (only if vuln name implies web)
        if (in_array($port, ['80','443','8080','8443','8000','8888']) &&
            self::matchesAny($haystack, ['http', 'web', 'server', 'cgi', 'php', 'asp'])) {
            return ['category' => self::CAT_WEB_SERVER, 'affected_component' => self::guessWebComponent($haystack)];
        }

        // ── 4. Application (installed software) ───────────────
        $appMatch = self::matchNamedComponent($haystack, [
            // Browsers
            'google chrome'         => 'Google Chrome',
            'mozilla firefox'       => 'Mozilla Firefox',
            'microsoft edge'        => 'Microsoft Edge (Chromium)',
            'edge (chromium)'       => 'Microsoft Edge (Chromium)',
            'edge chromium'         => 'Microsoft Edge (Chromium)',
            'internet explorer'     => 'Internet Explorer',
            'safari'                => 'Apple Safari',
            // Java ecosystem
            'java runtime'          => 'Java Runtime Environment (JRE)',
            'java se'               => 'Java SE',
            'jdk'                   => 'Java Development Kit',
            'log4j'                 => 'Apache Log4j',
            'log4shell'             => 'Apache Log4j',
            'spring framework'      => 'Spring Framework',
            'spring4shell'          => 'Spring Framework',
            // Crypto / comms libs
            'openssl'               => 'OpenSSL',
            'openssh'               => 'OpenSSH',
            'libssl'                => 'OpenSSL',
            'gnutls'                => 'GnuTLS',
            'nss'                   => 'Mozilla NSS',
            // Office / productivity
            'microsoft office'      => 'Microsoft Office',
            'microsoft word'        => 'Microsoft Word',
            'microsoft excel'       => 'Microsoft Excel',
            'adobe acrobat'         => 'Adobe Acrobat / Reader',
            'adobe reader'          => 'Adobe Acrobat / Reader',
            'adobe flash'           => 'Adobe Flash',
            // Dev / runtime
            'php'                   => 'PHP',
            'python'                => 'Python',
            'node.js'               => 'Node.js',
            'nodejs'                => 'Node.js',
            'ruby'                  => 'Ruby',
            'perl'                  => 'Perl',
            '.net framework'        => '.NET Framework',
            'dotnet'                => '.NET',
            // VPN / remote access
            'citrix'                => 'Citrix',
            'pulse secure'          => 'Pulse Secure VPN',
            'palo alto globalprotect'=> 'GlobalProtect VPN',
            'anyconnect'            => 'Cisco AnyConnect',
            'fortivpn'              => 'FortiVPN',
            'vpn'                   => 'VPN Client',
            // Security agents
            'symantec'              => 'Symantec',
            'mcafee'                => 'McAfee',
            'crowdstrike'           => 'CrowdStrike',
            'kaspersky'             => 'Kaspersky',
            // Collaboration
            'zoom'                  => 'Zoom',
            'teams'                 => 'Microsoft Teams',
            'slack'                 => 'Slack',
            'skype'                 => 'Skype',
            // Developer tools
            'visual studio code'    => 'Visual Studio Code',
            'vs code'               => 'Visual Studio Code',
            'visual studio'         => 'Microsoft Visual Studio',
            'dbeaver'               => 'DBeaver',
            'notepad++'             => 'Notepad++',
            'notepadpp'             => 'Notepad++',
            'sublimetext'           => 'Sublime Text',
            'sublime text'          => 'Sublime Text',
            'jetbrains'             => 'JetBrains IDE',
            'intellij'              => 'IntelliJ IDEA',
            'pycharm'               => 'PyCharm',
            // Tools / utilities
            'curl installed'        => 'cURL',
            'wget'                  => 'Wget',
            'putty'                 => 'PuTTY',
            'winscp'                => 'WinSCP',
            'filezilla'             => 'FileZilla',
            '7-zip'                 => '7-Zip',
            'winrar'                => 'WinRAR',
            'vlc'                   => 'VLC Media Player',
            'anaconda'              => 'Anaconda (Python)',
            // Monitoring / agents (keep patterns specific — vendor names appear in all Nessus descriptions)
            'zabbix agent'          => 'Zabbix Agent',
            'zabbix'                => 'Zabbix Agent',
            'nessus agent'          => 'Nessus Agent',
            'crowdstrike falcon'    => 'CrowdStrike Sensor',
            'crowdstrike'           => 'CrowdStrike Sensor',
            'splunk forwarder'      => 'Splunk Universal Forwarder',
            'splunk'                => 'Splunk',
            'elastic agent'         => 'Elastic Agent',
            // Other common
            'wordpress'             => 'WordPress',
            'drupal'                => 'Drupal',
            'joomla'                => 'Joomla',
            'sharepoint'            => 'Microsoft SharePoint',
            'exchange server'       => 'Microsoft Exchange',
            'vmware tools'          => 'VMware Tools',
            'vmware'                => 'VMware',
            'virtualbox'            => 'Oracle VirtualBox',
            'docker'                => 'Docker',
            'kubernetes'            => 'Kubernetes',
            'ansible'               => 'Ansible',
            'jenkins'               => 'Jenkins',
            'git'                   => 'Git',
        ]);
        if ($appMatch) {
            return ['category' => self::CAT_APPLICATION, 'affected_component' => $appMatch];
        }

        // Generic Nessus "Software Installed" plugin pattern:
        // Plugin names like "DBeaver Installed (Windows)", "Curl Installed (Windows)", etc.
        if (preg_match('/^(.+?)\s+installed\s*\((?:windows|linux|macos|unix)\)\s*$/i', $vulnName, $m)) {
            return [
                'category'           => self::CAT_APPLICATION,
                'affected_component' => trim($m[1]),
            ];
        }
        // Also catch plain "X Installed" without OS qualifier
        if (preg_match('/^(.+?)\s+installed\s*(?:detection|check|version)?\s*$/i', $vulnName, $m)
            && !self::matchesAny(strtolower($m[1]), ['patch', 'update', 'hotfix'])) {
            return [
                'category'           => self::CAT_APPLICATION,
                'affected_component' => trim($m[1]),
            ];
        }

        // ── 5. OS (operating system level) ────────────────────
        $osMatch = self::matchNamedComponent($haystack, [
            // Windows versions (most specific first)
            'windows server 2025'=> 'Windows Server 2025',
            'windows server 2022'=> 'Windows Server 2022',
            'windows server 2019'=> 'Windows Server 2019',
            'windows server 2016'=> 'Windows Server 2016',
            'windows server 2012'=> 'Windows Server 2012',
            'windows server 2008'=> 'Windows Server 2008',
            'windows server'     => 'Windows Server',
            'windows 11'         => 'Windows 11',
            'windows 10'         => 'Windows 10',
            'windows 8'          => 'Windows 8',
            'windows 7'          => 'Windows 7',
            'windows xp'         => 'Windows XP',
            'ms windows'         => 'Microsoft Windows',
            'microsoft windows'  => 'Microsoft Windows',
            // Windows built-in security components
            'windows defender'   => 'Windows Defender',
            'microsoft defender' => 'Windows Defender',
            'defender antivirus' => 'Windows Defender',
            'antimalware'        => 'Windows Defender',
            'windows update'     => 'Windows Update',
            'windows firewall'   => 'Windows Firewall',
            'bitlocker'          => 'BitLocker',
            'credential guard'   => 'Windows Credential Guard',
            // Windows inventory / forensic Nessus plugins
            'registry editor'    => 'Windows Registry',
            'registry'           => 'Windows Registry',
            'application compatibility cache' => 'Windows App Compatibility',
            'enumerate accounts' => 'Windows User Accounts',
            'windows account'    => 'Windows User Accounts',
            'language settings'  => 'Windows Configuration',
            'recent file'        => 'Windows Shell',
            'opensavemru'        => 'Windows Shell',
            'shellbag'           => 'Windows Shell',
            'prefetch'           => 'Windows Prefetch',
            'windows event log'  => 'Windows Event Log',
            'installed software' => 'Windows Software Inventory',
            'software enumerat'  => 'Windows Software Inventory',
            // Windows services / components
            'smb'                => 'Windows SMB',
            'rdp'                => 'Windows RDP',
            'remote desktop'     => 'Windows RDP',
            'winrm'              => 'Windows Remote Management',
            'wmi'                => 'Windows WMI',
            'ntlm'               => 'Windows NTLM',
            'kerberos'           => 'Kerberos',
            'active directory'   => 'Active Directory',
            'dns server'         => 'DNS Server',
            'dhcp server'        => 'DHCP Server',
            // Linux
            'linux kernel'       => 'Linux Kernel',
            'ubuntu'             => 'Ubuntu Linux',
            'debian'             => 'Debian Linux',
            'centos'             => 'CentOS Linux',
            'red hat'            => 'Red Hat Enterprise Linux',
            'rhel'               => 'Red Hat Enterprise Linux',
            'fedora'             => 'Fedora Linux',
            'suse'               => 'SUSE Linux',
            'amazon linux'       => 'Amazon Linux',
            // Linux services
            'bash'               => 'Bash Shell',
            'shellshock'         => 'Bash Shell (Shellshock)',
            'sudo'               => 'Linux sudo',
            'glibc'              => 'GNU C Library (glibc)',
            'dirtycow'           => 'Linux Kernel (DirtyCOW)',
            'polkit'             => 'Linux PolicyKit',
            // macOS
            'macos'              => 'macOS',
            'mac os x'           => 'macOS',
            'os x'               => 'macOS',
            // Network OS
            'cisco ios'          => 'Cisco IOS',
            'cisco nx-os'        => 'Cisco NX-OS',
            'juniper junos'      => 'Juniper JunOS',
            'fortios'            => 'Fortinet FortiOS',
            'panos'              => 'Palo Alto PAN-OS',
        ]);
        if ($osMatch) {
            return ['category' => self::CAT_OS, 'affected_component' => $osMatch];
        }

        // OS fallback — detect from osDetected field + generic OS keywords in vuln name
        if ($osDetected && self::matchesAny($haystack, [
            'patch', 'update', 'hotfix', 'service pack', 'cumulative', 'security update',
            'privilege escalation', 'elevation of privilege', 'local privilege',
            'kernel', 'memory corruption', 'buffer overflow', 'use after free',
        ])) {
            return [
                'category'           => self::CAT_OS,
                'affected_component' => self::extractOsComponent($osDetected),
            ];
        }

        // ── 6. Network ────────────────────────────────────────
        $netMatch = self::matchNamedComponent($haystack, [
            'netstat'           => 'Network Connections (netstat)',
            'ip assignment'     => 'IP Configuration',
            'ip address'        => 'IP Configuration',
            'routing table'     => 'Routing Table',
            'arp cache'         => 'ARP Cache',
            'network interface' => 'Network Interface',
            'ssh'               => 'SSH',
            'ftp'               => 'FTP',
            'sftp'              => 'SFTP',
            'telnet'            => 'Telnet',
            'snmp'              => 'SNMP',
            'smtp'              => 'SMTP',
            'imap'              => 'IMAP',
            'pop3'              => 'POP3',
            'ldap'              => 'LDAP',
            'ntp'               => 'NTP',
            'dns'               => 'DNS',
            'dhcp'              => 'DHCP',
            'icmp'              => 'ICMP',
            'vnc'               => 'VNC',
            'rdp'               => 'RDP',
            'rpc'               => 'RPC',
            'netbios'           => 'NetBIOS',
            'smb'               => 'SMB',
            'tftp'              => 'TFTP',
            'ipp'               => 'IPP',
            'sip'               => 'SIP',
            'bgp'               => 'BGP',
            'ospf'              => 'OSPF',
            'firewall'          => 'Firewall',
            'router'            => 'Router',
            'switch'            => 'Network Switch',
            'network'           => 'Network Service',
        ]);
        if ($netMatch) {
            return ['category' => self::CAT_NETWORK, 'affected_component' => $netMatch];
        }
        // Network by port
        $portNetMap = [
            '21'=>'FTP', '22'=>'SSH', '23'=>'Telnet', '25'=>'SMTP',
            '53'=>'DNS', '67'=>'DHCP', '68'=>'DHCP', '69'=>'TFTP',
            '110'=>'POP3', '143'=>'IMAP', '161'=>'SNMP', '162'=>'SNMP',
            '389'=>'LDAP', '445'=>'SMB', '514'=>'Syslog', '636'=>'LDAPS',
            '5900'=>'VNC', '5985'=>'WinRM', '5986'=>'WinRM',
        ];
        if ($port && isset($portNetMap[$port])) {
            return ['category' => self::CAT_NETWORK, 'affected_component' => $portNetMap[$port]];
        }

        // ── 7. Policy / Compliance / Configuration ────────────
        if (self::matchesAny($haystack, [
            'compliance', 'policy', 'configuration', 'misconfiguration', 'default password',
            'default credential', 'anonymous', 'null session', 'information disclosure',
            'banner', 'version disclosure', 'audit', 'permission', 'access control',
            'password policy', 'account lockout', 'guest account', 'unnecessary service',
        ])) {
            return [
                'category'           => self::CAT_POLICY,
                'affected_component' => self::extractPolicyComponent($haystack, $vulnName),
            ];
        }

        // ── 8. Fallback — use osDetected to guess OS category ─
        if ($osDetected) {
            return [
                'category'           => self::CAT_OS,
                'affected_component' => self::extractOsComponent($osDetected),
            ];
        }

        return ['category' => self::CAT_OTHER, 'affected_component' => null];
    }

    // ── Helpers ───────────────────────────────────────────────

    private static function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            if (str_contains($haystack, $n)) return true;
        }
        return false;
    }

    /**
     * Return the component label for the first matching needle.
     */
    private static function matchNamedComponent(string $haystack, array $map): ?string
    {
        foreach ($map as $needle => $label) {
            if (str_contains($haystack, $needle)) return $label;
        }
        return null;
    }

    private static function extractSslComponent(string $haystack, string $vulnName): string
    {
        if (str_contains($haystack, 'openssl'))  return 'OpenSSL';
        if (str_contains($haystack, 'gnutls'))   return 'GnuTLS';
        if (str_contains($haystack, 'nss'))      return 'Mozilla NSS';
        if (str_contains($haystack, 'iis'))      return 'Microsoft IIS (SSL/TLS)';
        if (str_contains($haystack, 'apache'))   return 'Apache (SSL/TLS)';
        if (str_contains($haystack, 'nginx'))    return 'Nginx (SSL/TLS)';
        return 'SSL/TLS Service';
    }

    private static function guessWebComponent(string $haystack): string
    {
        if (str_contains($haystack, 'php'))    return 'PHP';
        if (str_contains($haystack, 'asp'))    return 'ASP.NET';
        if (str_contains($haystack, 'cgi'))    return 'CGI Application';
        if (str_contains($haystack, 'tomcat')) return 'Apache Tomcat';
        return 'Web Application';
    }

    private static function extractOsComponent(string $osDetected): string
    {
        $os = strtolower($osDetected);
        if (str_contains($os, 'windows server 2025')) return 'Windows Server 2025';
        if (str_contains($os, 'windows server 2022')) return 'Windows Server 2022';
        if (str_contains($os, 'windows server 2019')) return 'Windows Server 2019';
        if (str_contains($os, 'windows server 2016')) return 'Windows Server 2016';
        if (str_contains($os, 'windows server 2012')) return 'Windows Server 2012';
        if (str_contains($os, 'windows server 2008')) return 'Windows Server 2008';
        if (str_contains($os, 'windows server'))      return 'Windows Server';
        if (str_contains($os, 'windows 11'))  return 'Windows 11';
        if (str_contains($os, 'windows 10'))  return 'Windows 10';
        if (str_contains($os, 'windows 7'))   return 'Windows 7';
        if (str_contains($os, 'windows'))     return 'Microsoft Windows';
        if (str_contains($os, 'ubuntu'))      return 'Ubuntu Linux';
        if (str_contains($os, 'centos'))      return 'CentOS Linux';
        if (str_contains($os, 'red hat') || str_contains($os, 'rhel')) return 'Red Hat Enterprise Linux';
        if (str_contains($os, 'debian'))      return 'Debian Linux';
        if (str_contains($os, 'suse'))        return 'SUSE Linux';
        if (str_contains($os, 'amazon linux'))return 'Amazon Linux';
        if (str_contains($os, 'linux'))       return 'Linux';
        if (str_contains($os, 'macos') || str_contains($os, 'mac os')) return 'macOS';
        if (str_contains($os, 'cisco'))       return 'Cisco IOS';
        if (str_contains($os, 'juniper'))     return 'Juniper JunOS';
        return trim(explode("\n", $osDetected)[0]);
    }

    private static function extractPolicyComponent(string $haystack, string $vulnName): string
    {
        if (str_contains($haystack, 'password'))     return 'Password Policy';
        if (str_contains($haystack, 'anonymous'))    return 'Anonymous Access';
        if (str_contains($haystack, 'information disclosure') || str_contains($haystack, 'banner')) return 'Information Disclosure';
        if (str_contains($haystack, 'default credential') || str_contains($haystack, 'default password')) return 'Default Credentials';
        if (str_contains($haystack, 'permission') || str_contains($haystack, 'access control')) return 'Access Control';
        if (str_contains($haystack, 'audit'))        return 'Audit Configuration';
        return 'Security Policy';
    }
}
