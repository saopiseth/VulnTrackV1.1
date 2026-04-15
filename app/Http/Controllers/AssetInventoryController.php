<?php

namespace App\Http\Controllers;

use App\Models\AssetInventory;
use App\Models\VulnFinding;
use App\Models\VulnHostOs;
use App\Models\VulnScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AssetInventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = AssetInventory::with('creator')->latest();

        if ($request->filled('scope'))  $query->where('identified_scope', $request->scope);
        if ($request->filled('env'))    $query->where('environment', $request->env);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('level'))  $query->where('classification_level', $request->level);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ip_address', 'like', "%$s%")
                  ->orWhere('hostname', 'like', "%$s%")
                  ->orWhere('system_name', 'like', "%$s%")
                  ->orWhere('tags', 'like', "%$s%");
            });
        }

        $assets = $query->paginate(20)->withQueryString();

        // Stats for summary cards
        $stats = [
            'total'        => AssetInventory::count(),
            'pci'          => AssetInventory::where('identified_scope', 'PCI')->count(),
            'dmz'          => AssetInventory::where('identified_scope', 'DMZ')->count(),
            'critical'     => AssetInventory::where('classification_level', 1)->count(),
            'active'       => AssetInventory::where('status', 'Active')->count(),
            'high_vulns'   => AssetInventory::where('vuln_critical', '>', 0)->count(),
        ];

        return view('inventory.index', compact('assets', 'stats'));
    }

    public function create()
    {
        return view('inventory.create', ['asset' => new AssetInventory()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        // Auto-classify if not manually set
        if ($request->boolean('auto_classify')) {
            $data = $this->autoClassify($data);
        }

        $data['created_by'] = Auth::id();
        AssetInventory::create($data);

        return redirect()->route('inventory.index')
            ->with('success', 'Asset added to inventory successfully.');
    }

    public function show(AssetInventory $inventory)
    {
        return view('inventory.show', ['asset' => $inventory->load('creator')]);
    }

    public function edit(AssetInventory $inventory)
    {
        return view('inventory.create', ['asset' => $inventory]);
    }

    public function update(Request $request, AssetInventory $inventory)
    {
        $data = $this->validateRequest($request);

        if ($request->boolean('auto_classify')) {
            $data = $this->autoClassify($data);
        }

        $inventory->update($data);

        return redirect()->route('inventory.show', $inventory)
            ->with('success', 'Asset updated successfully.');
    }

    public function destroy(AssetInventory $inventory)
    {
        $this->authorize('delete', $inventory);

        $inventory->delete();
        return redirect()->route('inventory.index')
            ->with('success', 'Asset removed from inventory.');
    }

    /**
     * AJAX endpoint: auto-classify and return JSON suggestions.
     */
    public function classify(Request $request)
    {
        $ip       = $request->input('ip_address', '');
        $hostname = $request->input('hostname');
        $ports    = $request->input('open_ports');
        $tags     = $request->input('tags');
        $criticalVulns = (int) $request->input('vuln_critical', 0);

        $scope      = AssetInventory::classifyScope($ip, $hostname, $ports, $tags);
        $env        = AssetInventory::classifyEnvironment($hostname);
        $systemName = AssetInventory::classifySystemName($hostname, $tags);
        [$level, $critLevel] = AssetInventory::classifyLevel($scope, $env, $systemName, $criticalVulns);

        return response()->json([
            'identified_scope'    => $scope,
            'environment'         => $env,
            'system_name'         => $systemName,
            'classification_level'=> $level,
            'critical_level'      => $critLevel,
        ]);
    }

    /**
     * GET endpoint: fetch hostname, OS, ports, and vuln counts
     * from the latest uploaded vulnerability scan for a given IP.
     */
    public function scanData(Request $request)
    {
        $ip = trim($request->input('ip', ''));
        if (!$ip) {
            return response()->json(['found' => false, 'message' => 'IP address required.']);
        }

        // Find the latest scan that has findings for this IP
        $latestScan = VulnScan::whereHas('findings', fn($q) => $q->where('ip_address', $ip))
            ->latest()
            ->first();

        if (!$latestScan) {
            return response()->json(['found' => false, 'message' => 'No scan data found for this IP.']);
        }

        $findings = VulnFinding::where('scan_id', $latestScan->id)
            ->where('ip_address', $ip)
            ->get();

        // ── Hostname ─────────────────────────────────────────
        $hostname = $findings->first(fn($f) => !empty($f->hostname))?->hostname;

        // ── OS – from OS identification findings ─────────────
        $os = null;
        $osKeywords = ['os identification', 'operating system', 'common platform enumeration', 'cpe', 'host os'];

        $osFinding = $findings->first(function ($f) use ($osKeywords) {
            $name = strtolower($f->vuln_name ?? '');
            foreach ($osKeywords as $kw) {
                if (str_contains($name, $kw)) return true;
            }
            return false;
        });

        if ($osFinding && $osFinding->plugin_output) {
            $out = $osFinding->plugin_output;
            // Nessus format: "Remote operating system : Windows Server 2019 Standard\n..."
            if (preg_match('/remote operating system\s*:\s*(.+)/i', $out, $m)) {
                $os = trim($m[1]);
            } elseif (preg_match('/operating system\s*:\s*(.+)/i', $out, $m)) {
                $os = trim($m[1]);
            } elseif (preg_match('/os\s*:\s*(.+)/i', $out, $m)) {
                $os = trim($m[1]);
            } else {
                // Fallback: first non-empty line of plugin output
                $firstLine = collect(explode("\n", $out))->map('trim')->first(fn($l) => $l !== '');
                if ($firstLine) $os = $firstLine;
            }
        }

        // If still no OS, try description of OS-related finding
        if (!$os && $osFinding && $osFinding->description) {
            if (preg_match('/running\s+([\w\s]+(?:windows|linux|ubuntu|centos|redhat|debian|aix|solaris|freebsd)[^\n.]*)/i', $osFinding->description, $m)) {
                $os = trim($m[1]);
            }
        }

        // ── Open ports ────────────────────────────────────────
        $ports = $findings->pluck('port')
            ->filter(fn($p) => $p && $p !== '0')
            ->unique()
            ->sort()
            ->values()
            ->implode(', ');

        // ── Vuln counts ───────────────────────────────────────
        $counts = $findings->groupBy('severity')->map->count();

        return response()->json([
            'found'         => true,
            'hostname'      => $hostname,
            'os'            => $os,
            'open_ports'    => $ports ?: null,
            'vuln_critical' => $counts->get('Critical', 0),
            'vuln_high'     => $counts->get('High', 0),
            'vuln_medium'   => $counts->get('Medium', 0),
            'vuln_low'      => $counts->get('Low', 0),
            'last_scanned'  => $latestScan->created_at->format('Y-m-d H:i'),
            'assessment'    => $latestScan->assessment->name ?? null,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'ip_address'           => ['required', 'string', 'max:45'],
            'hostname'             => ['nullable', 'string', 'max:255'],
            'identified_scope'     => ['required', 'in:PCI,DMZ,Internal,External,Third-Party'],
            'environment'          => ['required', 'in:PROD,UAT,STAGE'],
            'system_name'          => ['nullable', 'string', 'max:255'],
            'classification_level' => ['required', 'integer', 'min:1', 'max:5'],
            'critical_level'       => ['required', 'in:Mission-Critical,Business-Critical,Business Operational,Administrative,None-Bank'],
            'os'                   => ['nullable', 'string', 'max:255'],
            'open_ports'           => ['nullable', 'string', 'max:500'],
            'vuln_critical'        => ['nullable', 'integer', 'min:0'],
            'vuln_high'            => ['nullable', 'integer', 'min:0'],
            'vuln_medium'          => ['nullable', 'integer', 'min:0'],
            'vuln_low'             => ['nullable', 'integer', 'min:0'],
            'tags'                 => ['nullable', 'string', 'max:500'],
            'notes'                => ['nullable', 'string'],
            'status'               => ['required', 'in:Active,Inactive,Decommissioned'],
            'last_scanned_at'      => ['nullable', 'date'],
        ]);
    }

    private function autoClassify(array $data): array
    {
        $scope      = AssetInventory::classifyScope($data['ip_address'], $data['hostname'] ?? null, $data['open_ports'] ?? null, $data['tags'] ?? null);
        $env        = AssetInventory::classifyEnvironment($data['hostname'] ?? null);
        $systemName = AssetInventory::classifySystemName($data['hostname'] ?? null, $data['tags'] ?? null);
        [$level, $critLevel] = AssetInventory::classifyLevel($scope, $env, $systemName, (int)($data['vuln_critical'] ?? 0));

        $data['identified_scope']     = $scope;
        $data['environment']          = $env;
        $data['system_name']          = $data['system_name'] ?: $systemName;
        $data['classification_level'] = $level;
        $data['critical_level']       = $critLevel;

        return $data;
    }

    public function osAssets(Request $request)
    {
        $query = VulnHostOs::with(['overrideBy', 'assessment']);

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

        // Distribution is expensive — cache for 5 min, bust on override/criticality writes
        $osDistribution = Cache::store('file')->remember('inventory.os_distribution', 300, function () {
            return VulnHostOs::selectRaw("COALESCE(os_override_family, os_family) as family, COUNT(*) as cnt")
                ->groupBy('family')
                ->orderByDesc('cnt')
                ->get();
        });

        // AJAX: return only the table rows + pagination HTML
        if ($request->ajax()) {
            $familyMeta = [
                'Windows' => ['icon' => 'bi-windows',       'bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Windows'],
                'Linux'   => ['icon' => 'bi-ubuntu',        'bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Linux'],
                'Unix'    => ['icon' => 'bi-terminal-fill', 'bg' => '#ffedd5', 'color' => '#7c2d12', 'label' => 'Unix-based'],
                'Other'   => ['icon' => 'bi-cpu-fill',      'bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'Other'],
            ];
            return response()->json([
                'html'  => view('inventory._os_rows', compact('hosts', 'familyMeta'))->render(),
                'links' => $hosts->links()->toHtml(),
                'total' => $hosts->total(),
            ]);
        }

        return view('inventory.os_assets', compact('hosts', 'osDistribution'));
    }

    public function osOverride(Request $request, VulnHostOs $hostOs)
    {
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
            $hostOs->update([
                'os_override'        => null,
                'os_override_family' => null,
                'os_override_note'   => null,
                'os_override_by'     => null,
                'os_override_at'     => null,
            ]);
        }

        Cache::store('file')->forget('inventory.os_distribution');

        return back()->with('success', 'OS override saved.');
    }

    public function setCriticality(Request $request, VulnHostOs $hostOs)
    {
        $data = $request->validate([
            'asset_criticality' => ['required', 'integer', 'min:1', 'max:5'],
            'system_name'       => ['nullable', 'string', 'max:255'],
            'system_owner'      => ['nullable', 'string', 'max:255'],
        ]);

        $hostOs->update([
            'asset_criticality'  => $data['asset_criticality'],
            'system_name'        => $data['system_name'] ?: null,
            'system_owner'       => $data['system_owner'] ?: null,
            'criticality_set_by' => Auth::id(),
            'criticality_set_at' => now(),
        ]);

        Cache::store('file')->forget('inventory.os_distribution');

        return back()->with('success', 'Asset classification saved.');
    }

    public function hostApps(Request $request, VulnHostOs $hostOs)
    {
        // Collect all plugin-45590 outputs for this host across all scans
        $outputs = VulnFinding::where('assessment_id', $hostOs->assessment_id)
            ->where('ip_address', $hostOs->ip_address)
            ->where('plugin_id', '45590')
            ->whereNotNull('plugin_output')
            ->pluck('plugin_output');

        // Parse CPE application entries from all outputs, deduplicate by name+version
        $apps = collect();
        foreach ($outputs as $output) {
            foreach (explode("\n", $output) as $line) {
                $line = trim($line);
                // Match both cpe:/a: and x-cpe:/a:
                if (!preg_match('/(?:x-)?cpe:\/a:([^:]+):([^:]+):?([^\s]*)\s*(?:->\s*(.+))?/', $line, $m)) {
                    continue;
                }

                $vendor  = str_replace('_', ' ', $m[1]);
                $product = str_replace(['_', '-'], [' ', ' '], $m[2]);
                $version = $m[3] ?? '';
                $name    = trim($m[4] ?? '') ?: ucwords($vendor . ' ' . $product);

                // Clean version — strip trailing .0 segments (8.16.0.0 → 8.16.0)
                $version = rtrim(preg_replace('/(?:\.0)+$/', '', $version), '.');

                $key = strtolower($name . '|' . $version);
                if (!$apps->has($key)) {
                    $apps->put($key, [
                        'name'     => $name,
                        'vendor'   => ucwords($vendor),
                        'product'  => ucwords($product),
                        'version'  => $version ?: '—',
                        'category' => self::categoriseApp($vendor, $product),
                        'is_xcpe'  => str_starts_with($line, 'x-cpe'),
                    ]);
                }
            }
        }

        // Sort: category asc, then name asc
        $apps = $apps->values()->sortBy(['category', 'name']);

        return view('inventory.host_apps', compact('hostOs', 'apps'));
    }

    private static function categoriseApp(string $vendor, string $product): string
    {
        $v = strtolower($vendor);
        $p = strtolower($product);
        $s = $v . ' ' . $p;

        if (str_contains($s, 'microsoft')) {
            if (preg_match('/edge|internet.?explorer|ie\b|browser/', $p)) return 'Browser';
            if (preg_match('/\.net|framework|runtime/', $p))              return 'Runtime';
            if (preg_match('/defender|endpoint|security/', $p))           return 'Security';
            if (preg_match('/visual.?studio|vscode|code/', $p))           return 'Developer Tools';
            if (preg_match('/remote.?desktop|rdp/', $p))                  return 'Remote Access';
            return 'Microsoft';
        }
        if (preg_match('/crowdstrike|tenable|nessus|defender|symantec|mcafee|trend|kaspersky|sentinel/', $s))
            return 'Security';
        if (preg_match('/chrome|firefox|edge|opera|safari|browser/', $s))
            return 'Browser';
        if (preg_match('/java|jre|jdk|openjdk|python|ruby|perl|php|node|dotnet|\.net/', $s))
            return 'Runtime';
        if (preg_match('/apache|nginx|iis|tomcat|jetty|jboss|wildfly/', $s))
            return 'Web Server';
        if (preg_match('/mysql|mssql|postgres|oracle|mongodb|redis|sqlite|mariadb/', $s))
            return 'Database';
        if (preg_match('/git|vscode|eclipse|intellij|notepad|sublime|vim|curl|wget/', $s))
            return 'Developer Tools';
        if (preg_match('/log4j|logback|slf4j|openssl|libssl|libcurl|openvm|vmware/', $s))
            return 'Library / SDK';
        if (preg_match('/vpn|cisco|juniper|fortinet|palo.?alto|ssh|putty|winscp/', $s))
            return 'Network / VPN';
        return 'Other';
    }
}
