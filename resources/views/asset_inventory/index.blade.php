@extends('layouts.app')
@section('title', 'Asset Inventory')

@section('content')
<style>
    .sev-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:4px; }
    .badge-crit  { background:#fee2e2; color:#991b1b; }
    .badge-high  { background:#fef3c7; color:#92400e; }
    .badge-med   { background:#e0f2fe; color:#0c4a6e; }
    .badge-low   { background:#f0fdf4; color:#166534; }
    .badge-scope { padding:.2rem .6rem; border-radius:20px; font-size:.68rem; font-weight:700; }
    .vuln-pill   { display:inline-flex; align-items:center; gap:3px; padding:.18rem .55rem;
                   border-radius:20px; font-size:.72rem; font-weight:700; }
    .tbl th { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
              color:#64748b; border-bottom:2px solid #e2e8f0; white-space:nowrap; }
    .tbl td { font-size:.84rem; vertical-align:middle; border-bottom:1px solid #f1f5f9; }
    .tbl tr:hover td { background:#fafafa; }
    .filter-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1.25rem; }
    select.form-select, input.form-control { font-size:.83rem; border-radius:8px; }
</style>

{{-- Page header --}}
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Asset Inventory</li>
        </ol></nav>
        <h4 class="mb-0"><i class="bi bi-pc-display-horizontal me-2" style="color:var(--primary)"></i>Asset Inventory</h4>
        <p class="mb-0">All hosts discovered from Nessus scan uploads across every assessment.</p>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-3">
    @foreach([
        ['Total Assets',     $total,    'bi-hdd-stack-fill',  '#dbeafe','#1e40af'],
        ['Active',           $active,   'bi-check-circle-fill','#dcfce7','#166534'],
        ['With Critical',    $withCrit, 'bi-shield-fill-x',   '#fee2e2','#991b1b'],
        ['With High',        $withHigh, 'bi-exclamation-triangle-fill','#fef3c7','#92400e'],
    ] as [$label, $val, $icon, $bg, $col])
    <div class="col-6 col-md-3">
        <div class="card stat-widget" style="background:{{ $bg }}">
            <div class="sw-icon" style="background:{{ $col }}22">
                <i class="bi {{ $icon }}" style="color:{{ $col }}"></i>
            </div>
            <div>
                <div class="sw-label" style="color:{{ $col }}">{{ $label }}</div>
                <div class="sw-value" style="color:{{ $col }}">{{ number_format($val) }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('asset-inventory.index') }}" class="filter-card">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search IP, hostname, OS, kernel…"
                   value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="os_family" class="form-select">
                <option value="">All OS Families</option>
                @foreach(['Windows','Linux','Network','macOS'] as $fam)
                    <option value="{{ $fam }}" @selected(request('os_family') === $fam)>{{ $fam }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="scope" class="form-select">
                <option value="">All Scopes</option>
                @foreach($scopeOptions as $s)
                    <option value="{{ $s }}" @selected(request('scope') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="env" class="form-select">
                <option value="">All Environments</option>
                @foreach($envOptions as $e)
                    <option value="{{ $e }}" @selected(request('env') === $e)>{{ $e }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                @foreach($statusOptions as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-sm w-100" style="background:var(--primary);color:#fff;border-radius:8px">
                <i class="bi bi-funnel-fill"></i>
            </button>
            @if(request()->hasAny(['search','scope','env','status','os_family']))
            <a href="{{ route('asset-inventory.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
                <i class="bi bi-x"></i>
            </a>
            @endif
        </div>
    </div>
</form>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        @if($assets->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-hdd-stack" style="font-size:2.5rem;opacity:.3"></i>
            <p class="mt-2 mb-0">No assets found. Upload a Nessus scan to populate this inventory.</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table tbl mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">IP Address</th>
                        <th>Hostname</th>
                        <th>Operating System</th>
                        <th>Kernel / Build</th>
                        <th>Vulns</th>
                        <th>Open Ports</th>
                        <th>Scope</th>
                        <th>Env</th>
                        <th>Last Scanned</th>
                        <th>Status</th>
                        <th class="pe-3"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($assets as $asset)
                    @php $badge = $asset->osFamilyBadge(); @endphp
                    <tr>
                        {{-- IP --}}
                        <td class="ps-3">
                            <span style="font-family:monospace;font-weight:600;color:#0f172a">{{ $asset->ip_address }}</span>
                        </td>

                        {{-- Hostname --}}
                        <td>
                            @if($asset->hostname)
                                <span style="color:#374151">{{ $asset->hostname }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- OS --}}
                        <td>
                            @if($asset->os)
                            <span style="display:inline-flex;align-items:center;gap:5px">
                                <span style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};padding:.15rem .45rem;border-radius:6px;font-size:.7rem;font-weight:700">
                                    <i class="bi {{ $badge['icon'] }}"></i>
                                    {{ $asset->os_family ?? 'Unknown' }}
                                </span>
                                <span style="color:#374151;font-size:.82rem">{{ Str::limit($asset->os, 40) }}</span>
                            </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Kernel --}}
                        <td>
                            @if($asset->os_kernel)
                                <code style="font-size:.78rem;background:#f1f5f9;padding:.15rem .45rem;border-radius:5px;color:#374151">{{ $asset->os_kernel }}</code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Vuln counts --}}
                        <td>
                            <div style="display:flex;gap:3px;flex-wrap:wrap">
                                @if($asset->vuln_critical > 0)
                                <span class="vuln-pill badge-crit">C {{ $asset->vuln_critical }}</span>
                                @endif
                                @if($asset->vuln_high > 0)
                                <span class="vuln-pill badge-high">H {{ $asset->vuln_high }}</span>
                                @endif
                                @if($asset->vuln_medium > 0)
                                <span class="vuln-pill badge-med">M {{ $asset->vuln_medium }}</span>
                                @endif
                                @if($asset->vuln_low > 0)
                                <span class="vuln-pill badge-low">L {{ $asset->vuln_low }}</span>
                                @endif
                                @if($asset->totalVulns() === 0)
                                <span class="text-muted" style="font-size:.8rem">None</span>
                                @endif
                            </div>
                        </td>

                        {{-- Open ports --}}
                        <td>
                            @php $ports = $asset->openPortsArray(); @endphp
                            @if(count($ports))
                                <span style="font-size:.75rem;color:#64748b">
                                    {{ implode(', ', array_slice($ports, 0, 6)) }}
                                    @if(count($ports) > 6)
                                        <span class="text-muted">+{{ count($ports) - 6 }}</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Scope --}}
                        <td>
                            @if($asset->identified_scope)
                            @php
                                $scopeColors = ['PCI'=>['#fdf4ff','#7e22ce'],'DMZ'=>['#fef3c7','#92400e'],
                                    'Internal'=>['#e0f2fe','#0c4a6e'],'External'=>['#fee2e2','#991b1b'],
                                    'Third-Party'=>['#f1f5f9','#475569']];
                                [$sbg,$scol] = $scopeColors[$asset->identified_scope] ?? ['#f1f5f9','#475569'];
                            @endphp
                            <span class="badge-scope" style="background:{{ $sbg }};color:{{ $scol }}">{{ $asset->identified_scope }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </td>

                        {{-- Environment --}}
                        <td>
                            @if($asset->environment)
                            @php
                                $envColors = ['PROD'=>['#fee2e2','#991b1b'],'UAT'=>['#fef9c3','#854d0e'],'STAGE'=>['#f1f5f9','#475569']];
                                [$ebg,$ecol] = $envColors[$asset->environment] ?? ['#f1f5f9','#475569'];
                            @endphp
                            <span class="badge-scope" style="background:{{ $ebg }};color:{{ $ecol }}">{{ $asset->environment }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </td>

                        {{-- Last scanned --}}
                        <td style="white-space:nowrap;color:#64748b;font-size:.8rem">
                            {{ $asset->last_scanned_at?->format('d M Y') ?? '—' }}
                        </td>

                        {{-- Status --}}
                        <td>
                            @php
                                $stColors = ['Active'=>['#dcfce7','#166534'],'Inactive'=>['#f1f5f9','#475569'],'Decommissioned'=>['#fee2e2','#991b1b']];
                                [$stbg,$stcol] = $stColors[$asset->status] ?? ['#f1f5f9','#475569'];
                            @endphp
                            <span class="badge-scope" style="background:{{ $stbg }};color:{{ $stcol }}">{{ $asset->status }}</span>
                        </td>

                        {{-- Actions --}}
                        <td class="pe-3">
                            <a href="{{ route('asset-inventory.show', $asset) }}"
                               class="btn btn-sm" style="background:var(--primary);color:#fff;border-radius:7px;font-size:.78rem;padding:.25rem .7rem">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($assets->hasPages())
        <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="text-muted" style="font-size:.8rem">
                Showing {{ $assets->firstItem() }}–{{ $assets->lastItem() }} of {{ $assets->total() }} assets
            </span>
            {{ $assets->links('pagination::bootstrap-5') }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
