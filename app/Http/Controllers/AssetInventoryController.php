<?php

namespace App\Http\Controllers;

use App\Models\AssetInventory;
use App\Models\VulnFinding;
use App\Models\VulnScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
