<?php

namespace App\Http\Controllers;

use App\Models\AssetInventory;
use App\Models\VulnFinding;
use App\Models\VulnHostOs;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = AssetInventory::query();

        if ($s = $request->search) {
            $query->where(fn($q) => $q
                ->where('ip_address', 'like', "%{$s}%")
                ->orWhere('hostname',  'like', "%{$s}%")
                ->orWhere('os',        'like', "%{$s}%")
                ->orWhere('os_kernel', 'like', "%{$s}%")
            );
        }

        if ($v = $request->scope)     $query->where('identified_scope', $v);
        if ($v = $request->env)       $query->where('environment', $v);
        if ($v = $request->status)    $query->where('status', $v);
        if ($v = $request->os_family) $query->where('os_family', 'like', "%{$v}%");

        $assets = $query->orderByDesc('last_scanned_at')->paginate(25)->withQueryString();

        $total    = AssetInventory::count();
        $active   = AssetInventory::where('status', 'Active')->count();
        $withCrit = AssetInventory::where('vuln_critical', '>', 0)->count();
        $withHigh = AssetInventory::where('vuln_high', '>', 0)->count();

        return view('asset_inventory.index', [
            'assets'        => $assets,
            'total'         => $total,
            'active'        => $active,
            'withCrit'      => $withCrit,
            'withHigh'      => $withHigh,
            'scopeOptions'  => ['PCI', 'DMZ', 'Internal', 'External', 'Third-Party'],
            'envOptions'    => ['PROD', 'UAT', 'STAGE'],
            'statusOptions' => ['Active', 'Inactive', 'Decommissioned'],
        ]);
    }

    public function show(AssetInventory $assetInventory): View
    {
        $findings = VulnFinding::where('ip_address', $assetInventory->ip_address)
            ->with('assessment:id,name')
            ->orderByRaw("FIELD(severity,'Critical','High','Medium','Low','Info')")
            ->orderByDesc('cvss_score')
            ->limit(100)
            ->get();

        $assessments = $findings
            ->pluck('assessment')
            ->filter()
            ->unique('id')
            ->values();

        $osHistory = VulnHostOs::where('ip_address', $assetInventory->ip_address)
            ->with('assessment:id,name')
            ->orderByDesc('updated_at')
            ->get();

        return view('asset_inventory.show', compact(
            'assetInventory', 'findings', 'assessments', 'osHistory'
        ));
    }
}
