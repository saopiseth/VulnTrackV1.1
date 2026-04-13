@extends('layouts.app')
@section('title', 'Servers Asset Inventory')

@section('content')
<style>
    .scope-pci      { background:#fee2e2;color:#991b1b; }
    .scope-dmz      { background:#fef3c7;color:#92400e; }
    .scope-internal { background:#dbeafe;color:#1e40af; }
    .scope-external { background:#d1fae5;color:#065f46; }
    .scope-third    { background:#f3f4f6;color:#374151; }
    .cl-1 { background:#fee2e2;color:#991b1b; }
    .cl-2 { background:#fef3c7;color:#92400e; }
    .cl-3 { background:#dbeafe;color:#1e40af; }
    .cl-4 { background:#f3f4f6;color:#374151; }
    .cl-5 { background:#f9fafb;color:#6b7280; }
    .badge-scope, .badge-env, .badge-cl {
        font-size:.7rem; font-weight:700; padding:.25rem .55rem;
        border-radius:6px; text-transform:uppercase; letter-spacing:.4px;
    }
    .vuln-dot { width:9px;height:9px;border-radius:50%;display:inline-block;margin-right:3px; }
    .filter-bar { background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.25rem; }
    .table th { font-size:.75rem; text-transform:uppercase; letter-spacing:.5px; color:#64748b; font-weight:600; }
    .table td { font-size:.85rem; vertical-align:middle; }
    .ip-cell { font-family:monospace; font-weight:600; color:#0f172a; }
    .hostname-cell { color:#475569; font-size:.82rem; }
</style>

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Servers Asset Inventory</li>
        </ol></nav>
        <h4><i class="bi bi-hdd-network-fill me-2" style="color:rgb(152,194,10)"></i>Servers Asset Inventory</h4>
        <p>Structured inventory of all scanned assets with scope, environment, and criticality classification.</p>
    </div>
    <a href="{{ route('inventory.create') }}" class="btn btn-sm text-white" style="background:rgb(152,194,10);border-radius:10px;font-weight:600">
        <i class="bi bi-plus-lg me-1"></i> Add Asset
    </a>
</div>

{{-- Flash --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0" style="border-radius:10px" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card text-center p-3">
            <div style="font-size:1.6rem;font-weight:800;color:#0f172a">{{ $stats['total'] }}</div>
            <div style="font-size:.75rem;color:#64748b;font-weight:600">Total Assets</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card text-center p-3" style="border-color:#fca5a5">
            <div style="font-size:1.6rem;font-weight:800;color:#dc2626">{{ $stats['pci'] }}</div>
            <div style="font-size:.75rem;color:#dc2626;font-weight:600">PCI Assets</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card text-center p-3" style="border-color:#fde68a">
            <div style="font-size:1.6rem;font-weight:800;color:#d97706">{{ $stats['dmz'] }}</div>
            <div style="font-size:.75rem;color:#d97706;font-weight:600">DMZ Assets</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card text-center p-3" style="border-color:#fca5a5">
            <div style="font-size:1.6rem;font-weight:800;color:#dc2626">{{ $stats['critical'] }}</div>
            <div style="font-size:.75rem;color:#dc2626;font-weight:600">Mission-Critical</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card text-center p-3" style="border-color:#bbf7d0">
            <div style="font-size:1.6rem;font-weight:800;color:#16a34a">{{ $stats['active'] }}</div>
            <div style="font-size:.75rem;color:#16a34a;font-weight:600">Active</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card text-center p-3" style="border-color:#fca5a5">
            <div style="font-size:1.6rem;font-weight:800;color:#dc2626">{{ $stats['high_vulns'] }}</div>
            <div style="font-size:.75rem;color:#dc2626;font-weight:600">w/ Critical Vulns</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="filter-bar mb-3">
    <form method="GET" action="{{ route('inventory.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label fw-semibold" style="font-size:.8rem">Search</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="IP, hostname, system..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label fw-semibold" style="font-size:.8rem">Scope</label>
            <select name="scope" class="form-select form-select-sm">
                <option value="">All Scopes</option>
                @foreach(['PCI','DMZ','Internal','External','Third-Party'] as $s)
                    <option value="{{ $s }}" @selected(request('scope')===$s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label fw-semibold" style="font-size:.8rem">Environment</label>
            <select name="env" class="form-select form-select-sm">
                <option value="">All Envs</option>
                @foreach(['PROD','UAT','STAGE'] as $e)
                    <option value="{{ $e }}" @selected(request('env')===$e)>{{ $e }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label fw-semibold" style="font-size:.8rem">Classification</label>
            <select name="level" class="form-select form-select-sm">
                <option value="">All Levels</option>
                @foreach([1=>'Mission-Critical',2=>'Business-Critical',3=>'Business Operational',4=>'Administrative',5=>'None-Bank'] as $n=>$l)
                    <option value="{{ $n }}" @selected(request('level')==$n)>{{ $n }} – {{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-1">
            <label class="form-label fw-semibold" style="font-size:.8rem">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach(['Active','Inactive','Decommissioned'] as $st)
                    <option value="{{ $st }}" @selected(request('status')===$st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm text-white w-100" style="background:rgb(152,194,10)">
                <i class="bi bi-funnel-fill"></i> Filter
            </button>
            <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead style="background:#f8fafc">
                    <tr>
                        <th class="ps-3">IP Address</th>
                        <th>Hostname / OS</th>
                        <th>Scope</th>
                        <th>Env</th>
                        <th>System Name</th>
                        <th>Classification</th>
                        <th>Vulnerabilities</th>
                        <th>Ports</th>
                        <th>Status</th>
                        <th class="pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($assets as $asset)
                    <tr>
                        <td class="ps-3">
                            <div class="ip-cell">{{ $asset->ip_address }}</div>
                            @if($asset->last_scanned_at)
                            <div style="font-size:.72rem;color:#94a3b8">Scanned {{ $asset->last_scanned_at->diffForHumans() }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="hostname-cell">{{ $asset->hostname ?? '—' }}</div>
                            @if($asset->os)
                            <div style="font-size:.72rem;color:#94a3b8"><i class="bi bi-cpu me-1"></i>{{ $asset->os }}</div>
                            @endif
                        </td>
                        <td>
                            @php
                                $scopeClass = match($asset->identified_scope) {
                                    'PCI'        => 'scope-pci',
                                    'DMZ'        => 'scope-dmz',
                                    'Internal'   => 'scope-internal',
                                    'External'   => 'scope-external',
                                    default      => 'scope-third',
                                };
                            @endphp
                            <span class="badge-scope {{ $scopeClass }}">{{ $asset->identified_scope }}</span>
                        </td>
                        <td>
                            @php
                                $envColor = match($asset->environment) { 'PROD'=>'#dc2626','UAT'=>'#d97706',default=>'#2563eb' };
                            @endphp
                            <span class="badge-env" style="background:{{ $envColor }}22;color:{{ $envColor }}">{{ $asset->environment }}</span>
                        </td>
                        <td style="max-width:150px">
                            <div style="font-weight:600;color:#0f172a;font-size:.83rem">{{ $asset->system_name ?? '—' }}</div>
                        </td>
                        <td>
                            @php
                                $clClass = 'cl-'.$asset->classification_level;
                            @endphp
                            <span class="badge-cl {{ $clClass }}">
                                {{ $asset->classification_level }} – {{ $asset->critical_level }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                @if($asset->vuln_critical > 0)
                                    <span style="font-size:.72rem;background:#fee2e2;color:#991b1b;padding:.15rem .4rem;border-radius:5px;font-weight:700">C:{{ $asset->vuln_critical }}</span>
                                @endif
                                @if($asset->vuln_high > 0)
                                    <span style="font-size:.72rem;background:#fef3c7;color:#92400e;padding:.15rem .4rem;border-radius:5px;font-weight:700">H:{{ $asset->vuln_high }}</span>
                                @endif
                                @if($asset->vuln_medium > 0)
                                    <span style="font-size:.72rem;background:#dbeafe;color:#1e40af;padding:.15rem .4rem;border-radius:5px;font-weight:700">M:{{ $asset->vuln_medium }}</span>
                                @endif
                                @if($asset->vuln_low > 0)
                                    <span style="font-size:.72rem;background:#f3f4f6;color:#374151;padding:.15rem .4rem;border-radius:5px;font-weight:700">L:{{ $asset->vuln_low }}</span>
                                @endif
                                @if($asset->totalVulns() === 0)
                                    <span style="font-size:.72rem;color:#94a3b8">None</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span style="font-family:monospace;font-size:.78rem;color:#475569">{{ $asset->open_ports ?? '—' }}</span>
                        </td>
                        <td>
                            @php
                                $stColor = match($asset->status) { 'Active'=>'#16a34a','Inactive'=>'#d97706',default=>'#94a3b8' };
                            @endphp
                            <span style="font-size:.75rem;background:{{ $stColor }}22;color:{{ $stColor }};padding:.2rem .5rem;border-radius:6px;font-weight:700">
                                {{ $asset->status }}
                            </span>
                        </td>
                        <td class="pe-3">
                            <div class="d-flex gap-1">
                                <a href="{{ route('inventory.show', $asset) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;padding:.2rem .5rem" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('inventory.edit', $asset) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;padding:.2rem .5rem" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('inventory.destroy', $asset) }}" onsubmit="return confirm('Delete this asset?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" style="border-radius:8px;padding:.2rem .5rem" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-hdd-network" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.4"></i>
                            No assets found. <a href="{{ route('inventory.create') }}">Add the first one.</a>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($assets->hasPages())
    <div class="card-footer bg-white d-flex align-items-center justify-content-between" style="border-radius:0 0 14px 14px">
        <div style="font-size:.82rem;color:#64748b">
            Showing {{ $assets->firstItem() }}–{{ $assets->lastItem() }} of {{ $assets->total() }} assets
        </div>
        {{ $assets->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
