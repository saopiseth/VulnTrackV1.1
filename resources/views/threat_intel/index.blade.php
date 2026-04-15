@extends('layouts.app')
@section('title', 'Threat Intel Feed')

@section('content')
<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-light: rgb(240,248,210); --lime-muted: rgb(232,244,195); }
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:1.25rem; }
    thead.lime-head th { background:var(--lime-muted) !important; color:var(--lime-dark) !important; font-size:.75rem; }
    .type-btn { padding:.28rem .8rem; border-radius:20px; font-size:.78rem; font-weight:600; cursor:pointer;
                border:1.5px solid transparent; text-decoration:none; display:inline-block; transition:all .15s; }
    .type-btn.active  { border-color:var(--lime-dark); background:var(--lime-light); color:var(--lime-dark); }
    .type-btn:not(.active) { background:#f8fafc; color:#64748b; border-color:#e2e8f0; }
    .intel-badge { display:inline-flex; align-items:center; gap:.25rem; padding:.18rem .55rem;
                   border-radius:20px; font-size:.71rem; font-weight:700; white-space:nowrap; }
    .cvss-pill { display:inline-block; padding:.15rem .55rem; border-radius:20px;
                 font-size:.75rem; font-weight:800; font-family:monospace; }
    .crit-option { transition: border-color .15s, background .15s; }
    .crit-option:hover { border-color: #94a3b8 !important; background: #f8fafc !important; }
    #tableBody { transition: opacity .15s; }
    #tableBody.loading { opacity: .35; pointer-events: none; }
    .spinner-row td { padding: 2rem; text-align: center; color: #94a3b8; }
</style>

@php
    use App\Models\ThreatIntelItem;
    $allTypes      = ThreatIntelItem::types();
    $allSeverities = ThreatIntelItem::severities();
    $allStatuses   = ThreatIntelItem::statuses();
@endphp

{{-- Page Header --}}
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h4 style="margin-bottom:.2rem"><i class="bi bi-newspaper me-2" style="color:var(--lime-dark)"></i>Threat Intel Feed</h4>
        <div style="font-size:.84rem;color:#64748b">
            <i class="bi bi-shield-exclamation me-1"></i>Track CVEs, advisories, IOCs and exploits — correlated against your assessed findings
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#importModal"
            style="border:1.5px solid #cbd5e1;border-radius:9px;color:#64748b;background:#fff;font-weight:500">
            <i class="bi bi-upload me-1"></i> Import
        </button>
        <button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"
            style="background:var(--lime);color:#fff;border-radius:9px;border:none;font-weight:600">
            <i class="bi bi-plus-lg me-1"></i> Add Intel
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats Cards --}}
<div class="row g-2 mb-3">
    <div class="col-md d-flex">
        <div style="background:#fff;border:1px solid #e8f5c2;border-radius:12px;padding:.9rem 1rem;width:100%;
                    display:flex;flex-direction:column;justify-content:center;text-align:center">
            <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Total Items</div>
            <div style="font-size:1.6rem;font-weight:800;color:#0f172a;line-height:1.1">{{ $stats['total'] }}</div>
            <div style="font-size:.7rem;color:#cbd5e1;margin-top:.15rem">all types</div>
        </div>
    </div>
    <div class="col-md d-flex">
        <div style="background:#fee2e2;border:1px solid #fecaca;border-radius:12px;padding:.9rem 1rem;width:100%;
                    display:flex;flex-direction:column;justify-content:center;text-align:center">
            <div style="font-size:.68rem;color:#991b1b;font-weight:600;text-transform:uppercase;letter-spacing:.5px">
                <i class="bi bi-record-circle-fill me-1"></i>Active
            </div>
            <div style="font-size:1.5rem;font-weight:800;color:#991b1b;line-height:1.1">{{ $stats['active'] }}</div>
            <div style="font-size:.7rem;color:#fca5a5;margin-top:.15rem">
                {{ $stats['total'] > 0 ? round($stats['active'] / $stats['total'] * 100) : 0 }}% of total
            </div>
        </div>
    </div>
    <div class="col-md d-flex">
        <div style="background:#ffedd5;border:1px solid #fed7aa;border-radius:12px;padding:.9rem 1rem;width:100%;
                    display:flex;flex-direction:column;justify-content:center;text-align:center">
            <div style="font-size:.68rem;color:#9a3412;font-weight:600;text-transform:uppercase;letter-spacing:.5px">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>Critical / High
            </div>
            <div style="font-size:1.5rem;font-weight:800;color:#9a3412;line-height:1.1">{{ $stats['critical_high'] }}</div>
            <div style="font-size:.7rem;color:#fdba74;margin-top:.15rem">high priority</div>
        </div>
    </div>
    <div class="col-md d-flex">
        <div style="background:#dbeafe;border:1px solid #bfdbfe;border-radius:12px;padding:.9rem 1rem;width:100%;
                    display:flex;flex-direction:column;justify-content:center;text-align:center">
            <div style="font-size:.68rem;color:#1e40af;font-weight:600;text-transform:uppercase;letter-spacing:.5px">
                <i class="bi bi-crosshair me-1"></i>In Your System
            </div>
            <div style="font-size:1.5rem;font-weight:800;color:#1e40af;line-height:1.1">{{ $stats['matched'] }}</div>
            <div style="font-size:.7rem;color:#93c5fd;margin-top:.15rem">CVEs matched findings</div>
        </div>
    </div>
    <div class="col-md d-flex">
        <div style="background:#d1fae5;border:1px solid #a7f3d0;border-radius:12px;padding:.9rem 1rem;width:100%;
                    display:flex;flex-direction:column;justify-content:center;text-align:center">
            <div style="font-size:.68rem;color:#065f46;font-weight:600;text-transform:uppercase;letter-spacing:.5px">
                <i class="bi bi-check-circle-fill me-1"></i>Mitigated / Archived
            </div>
            <div style="font-size:1.5rem;font-weight:800;color:#065f46;line-height:1.1">{{ $stats['mitigated'] }}</div>
            <div style="font-size:.7rem;color:#6ee7b7;margin-top:.15rem">resolved</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="va-card" style="padding:.9rem 1.25rem;margin-bottom:1rem">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        {{-- Type tabs --}}
        <div class="d-flex gap-1 flex-wrap" id="typeTabBar">
            <a href="#" class="type-btn {{ !request('type') ? 'active' : '' }}" data-type="">All</a>
            @foreach($allTypes as $t)
            @php $tm = ThreatIntelItem::typeStyle($t); @endphp
            <a href="#" class="type-btn {{ request('type') === $t ? 'active' : '' }}" data-type="{{ $t }}">
                <i class="bi {{ $tm['icon'] }} me-1"></i>{{ $t }}
                @if(($typeCounts[$t] ?? 0) > 0)
                    <span style="font-size:.68rem;opacity:.65;margin-left:.15rem">{{ $typeCounts[$t] }}</span>
                @endif
            </a>
            @endforeach
        </div>

        {{-- Status + Severity + Search --}}
        <div class="ms-auto d-flex gap-2 flex-wrap align-items-center">
            <select id="statusSelect" class="form-select form-select-sm"
                style="border-radius:8px;font-size:.82rem;width:auto;min-width:130px">
                <option value="">All statuses</option>
                @foreach($allStatuses as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>

            <select id="severitySelect" class="form-select form-select-sm"
                style="border-radius:8px;font-size:.82rem;width:auto;min-width:130px">
                <option value="">All severities</option>
                @foreach($allSeverities as $sv)
                <option value="{{ $sv }}" {{ request('severity') === $sv ? 'selected' : '' }}>{{ $sv }}</option>
                @endforeach
            </select>

            <input type="text" id="searchInput" class="form-control form-control-sm"
                placeholder="Search title, CVE, source…"
                value="{{ request('search') }}"
                style="border-radius:8px;width:210px;font-size:.82rem">

            <a href="#" id="clearFilters" class="btn btn-sm {{ !request()->hasAny(['type','status','severity','search']) ? 'd-none' : '' }}"
               style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff">
                <i class="bi bi-x"></i>
            </a>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="va-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.82rem">
            <thead class="lime-head">
                <tr>
                    <th style="padding:.7rem 1rem;width:90px">Severity</th>
                    <th style="padding:.7rem .75rem;width:100px">Type</th>
                    <th style="padding:.7rem .75rem">Title / CVE ID</th>
                    <th style="padding:.7rem .75rem;width:70px">CVSS</th>
                    <th style="padding:.7rem .75rem">Source</th>
                    <th style="padding:.7rem .75rem;width:110px">Status</th>
                    <th style="padding:.7rem .75rem;width:80px">In System</th>
                    <th style="padding:.7rem .75rem;width:110px">Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                @include('threat_intel._items', ['items' => $items, 'allStatuses' => $allStatuses])
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
<div id="paginationContainer" class="{{ $items->hasPages() ? 'd-flex justify-content-center mt-2' : '' }}">
    {{ $items->links() }}
</div>

{{-- ══ Shared: Detail Modal ══ --}}
<div class="modal fade" id="sharedDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
            <div class="modal-header" style="border-bottom:2px solid var(--lime);padding:.85rem 1.25rem">
                <div>
                    <h6 class="modal-title mb-1" id="dm-title" style="font-size:.95rem;font-weight:700;color:#0f172a"></h6>
                    <div class="d-flex gap-2 flex-wrap" id="dm-badges"></div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.25rem">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="d-flex gap-3 mb-3 flex-wrap" id="dm-cve-row"></div>
                        <div class="mb-3" id="dm-desc-row" style="display:none">
                            <div style="font-size:.73rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.4rem">Description</div>
                            <div id="dm-description" style="font-size:.83rem;color:#374151;line-height:1.6;white-space:pre-wrap"></div>
                        </div>
                        <div class="mb-3" id="dm-affected-row" style="display:none">
                            <div style="font-size:.73rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.4rem">
                                <i class="bi bi-hdd-stack me-1"></i>Affected Products
                            </div>
                            <div id="dm-affected" style="font-size:.83rem;color:#374151;white-space:pre-wrap"></div>
                        </div>
                        <div class="mb-3" id="dm-ioc-row" style="display:none">
                            <div style="font-size:.73rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.4rem">
                                <i class="bi bi-radioactive me-1"></i>IOC
                            </div>
                            <div id="dm-ioc"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div style="background:#f8fafc;border-radius:10px;padding:.85rem 1rem">
                            <div class="mb-2" id="dm-source-row" style="display:none">
                                <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px">Source</div>
                                <div id="dm-source" style="font-size:.82rem;font-weight:600;color:#374151"></div>
                                <a id="dm-source-link" href="#" target="_blank" rel="noopener noreferrer"
                                   style="font-size:.72rem;color:var(--lime-dark);text-decoration:none;display:none">
                                    <i class="bi bi-box-arrow-up-right me-1" style="font-size:.65rem"></i>View source
                                </a>
                            </div>
                            <div class="mb-2" id="dm-published-row" style="display:none">
                                <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px">Published</div>
                                <div id="dm-published" style="font-size:.82rem;color:#374151"></div>
                            </div>
                            <div class="mb-2">
                                <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px">Added</div>
                                <div id="dm-added" style="font-size:.78rem;color:#64748b"></div>
                            </div>
                            <div class="mb-2" id="dm-tags-row" style="display:none">
                                <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:.3rem">Tags</div>
                                <div id="dm-tags"></div>
                            </div>
                            <div style="border-top:1px solid #e2e8f0;padding-top:.75rem;margin-top:.75rem" id="dm-correlation-row" style="display:none">
                                <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:.35rem">System Correlation</div>
                                <div id="dm-correlation"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.6rem 1.25rem">
                <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                    style="border:1.5px solid #cbd5e1;border-radius:7px;color:#64748b;background:#fff">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ══ Shared: Status Modal ══ --}}
<div class="modal fade" id="sharedStatusModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
            <div class="modal-header" style="border-bottom:2px solid var(--lime);padding:.85rem 1.25rem">
                <h6 class="modal-title" style="font-size:.9rem;font-weight:700">
                    <i class="bi bi-arrow-repeat me-1" style="color:var(--lime-dark)"></i>Update Status
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="statusForm" action="">
                @csrf @method('PATCH')
                <div class="modal-body" style="padding:1rem 1.25rem">
                    <div id="sm-title" style="font-size:.75rem;color:#94a3b8;margin-bottom:.75rem"></div>
                    <div style="display:flex;flex-direction:column;gap:.4rem" id="sm-options">
                        @foreach(ThreatIntelItem::statuses() as $st)
                        @php $sm = ThreatIntelItem::statusStyle($st); @endphp
                        <label class="crit-option sm-option" data-status="{{ $st }}"
                            style="display:flex;align-items:center;gap:.6rem;padding:.5rem .75rem;
                                border-radius:9px;border:1.5px solid #e2e8f0;background:#fafafa;cursor:pointer">
                            <input type="radio" name="status" value="{{ $st }}"
                                style="accent-color:{{ $sm['color'] }}">
                            <span style="font-size:.82rem;font-weight:600;color:{{ $sm['color'] }}">
                                <i class="bi {{ $sm['icon'] }} me-1"></i>{{ $st }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.6rem 1.25rem">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                        style="border:1.5px solid #cbd5e1;border-radius:7px;color:#64748b;background:#fff">Cancel</button>
                    <button type="submit" class="btn btn-sm"
                        style="background:var(--lime);color:#fff;border-radius:7px;font-weight:600;border:none">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Shared: Delete form --}}
<form id="sharedDeleteForm" method="POST" action="">@csrf @method('DELETE')</form>

{{-- ══════════════ Add Intel Modal ══════════════ --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
            <div class="modal-header" style="border-bottom:2px solid var(--lime);padding:.85rem 1.25rem">
                <h6 class="modal-title" style="font-size:.95rem;font-weight:700">
                    <i class="bi bi-plus-circle me-1" style="color:var(--lime-dark)"></i>Add Threat Intel Item
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('threat-intel.store') }}">
                @csrf
                <div class="modal-body" style="padding:1.25rem">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Title <span style="color:#dc2626">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm" required
                            placeholder="e.g. Apache Log4Shell Remote Code Execution"
                            style="border-radius:7px;font-size:.83rem">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Type <span style="color:#dc2626">*</span></label>
                            <select name="type" id="addType" class="form-select form-select-sm" required
                                style="border-radius:7px;font-size:.83rem" onchange="toggleIocFields()">
                                @foreach($allTypes as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Severity <span style="color:#dc2626">*</span></label>
                            <select name="severity" class="form-select form-select-sm" required style="border-radius:7px;font-size:.83rem">
                                @foreach($allSeverities as $sv)
                                <option value="{{ $sv }}" {{ $sv === 'High' ? 'selected' : '' }}>{{ $sv }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Status <span style="color:#dc2626">*</span></label>
                            <select name="status" class="form-select form-select-sm" required style="border-radius:7px;font-size:.83rem">
                                @foreach($allStatuses as $st)
                                <option value="{{ $st }}" {{ $st === 'Active' ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">CVE ID</label>
                            <input type="text" name="cve_id" class="form-control form-control-sm"
                                placeholder="CVE-2024-12345" style="border-radius:7px;font-size:.83rem;font-family:monospace">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">CVSS Score <span style="color:#94a3b8;font-weight:400">(0–10)</span></label>
                            <input type="number" name="cvss_score" class="form-control form-control-sm"
                                placeholder="9.8" min="0" max="10" step="0.1" style="border-radius:7px;font-size:.83rem">
                        </div>
                    </div>
                    <div class="row g-2 mb-3 d-none" id="iocFields">
                        <div class="col-4">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">IOC Type</label>
                            <select name="ioc_type" class="form-select form-select-sm" style="border-radius:7px;font-size:.83rem">
                                <option value="">— Select —</option>
                                @foreach(ThreatIntelItem::iocTypes() as $it)
                                <option value="{{ $it }}">{{ $it }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-8">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">IOC Value</label>
                            <input type="text" name="ioc_value" class="form-control form-control-sm"
                                placeholder="192.168.1.1 / malicious.domain / hash…"
                                style="border-radius:7px;font-size:.83rem;font-family:monospace">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Description</label>
                        <textarea name="description" class="form-control form-control-sm" rows="3"
                            placeholder="Describe the threat, impact, and attack vector…"
                            style="border-radius:7px;font-size:.83rem;resize:vertical"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Affected Products</label>
                        <textarea name="affected_products" class="form-control form-control-sm" rows="2"
                            placeholder="Apache Log4j 2.0–2.14.1, Windows Server 2016/2019…"
                            style="border-radius:7px;font-size:.83rem;resize:vertical"></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Source</label>
                            <input type="text" name="source" class="form-control form-control-sm"
                                placeholder="NVD, CISA KEV…" style="border-radius:7px;font-size:.83rem">
                        </div>
                        <div class="col-5">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Source URL</label>
                            <input type="url" name="source_url" class="form-control form-control-sm"
                                placeholder="https://nvd.nist.gov/…" style="border-radius:7px;font-size:.83rem">
                        </div>
                        <div class="col-3">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Published</label>
                            <input type="date" name="published_at" class="form-control form-control-sm"
                                style="border-radius:7px;font-size:.83rem">
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Tags
                            <span style="color:#94a3b8;font-weight:400">(comma-separated)</span></label>
                        <input type="text" name="tags" class="form-control form-control-sm"
                            placeholder="rce, log4j, critical-patch" style="border-radius:7px;font-size:.83rem">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.6rem 1.25rem">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                        style="border:1.5px solid #cbd5e1;border-radius:7px;color:#64748b;background:#fff">Cancel</button>
                    <button type="submit" class="btn btn-sm"
                        style="background:var(--lime);color:#fff;border-radius:7px;font-weight:600;border:none">
                        <i class="bi bi-plus-lg me-1"></i>Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════════════ Import Modal ══════════════ --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
            <div class="modal-header" style="border-bottom:2px solid var(--lime);padding:.85rem 1.25rem">
                <h6 class="modal-title" style="font-size:.9rem;font-weight:700">
                    <i class="bi bi-upload me-1" style="color:var(--lime-dark)"></i>Import Intel Items
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('threat-intel.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body" style="padding:1.1rem 1.25rem">
                    <p style="font-size:.8rem;color:#64748b;margin-bottom:.85rem">
                        Upload a <strong>JSON</strong> or <strong>CSV</strong> file.
                        Required fields: <code style="font-size:.75rem">title</code>, <code style="font-size:.75rem">severity</code>.
                        Optional: <code style="font-size:.75rem">type</code>, <code style="font-size:.75rem">cve_id</code>,
                        <code style="font-size:.75rem">cvss_score</code>, <code style="font-size:.75rem">source</code>,
                        <code style="font-size:.75rem">published_at</code>, <code style="font-size:.75rem">description</code>.
                    </p>
                    <input type="file" name="file" accept=".json,.csv,.txt"
                        class="form-control form-control-sm" required style="border-radius:7px;font-size:.82rem">
                    <div style="font-size:.72rem;color:#94a3b8;background:#f8fafc;border-radius:8px;padding:.6rem .75rem;margin-top:.75rem">
                        <i class="bi bi-info-circle me-1"></i>
                        JSON: array of objects or <code>{"items":[…]}</code>. CSV: first row = headers. Max 4 MB.
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.6rem 1.25rem">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                        style="border:1.5px solid #cbd5e1;border-radius:7px;color:#64748b;background:#fff">Cancel</button>
                    <button type="submit" class="btn btn-sm"
                        style="background:var(--lime);color:#fff;border-radius:7px;font-weight:600;border:none">
                        <i class="bi bi-upload me-1"></i>Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    // ── Severity / Type / Status style maps (mirrors PHP model) ──────────────
    const SEV = {
        Critical: { bg:'#fee2e2', color:'#991b1b', icon:'bi-exclamation-octagon-fill' },
        High:     { bg:'#ffedd5', color:'#9a3412', icon:'bi-exclamation-triangle-fill' },
        Medium:   { bg:'#fef9c3', color:'#854d0e', icon:'bi-exclamation-circle-fill'  },
        Low:      { bg:'#f1f5f9', color:'#475569', icon:'bi-info-circle-fill'         },
        Info:     { bg:'#e0f2fe', color:'#0c4a6e', icon:'bi-info-circle'              },
    };
    const TYPE = {
        CVE:      { bg:'#fee2e2', color:'#991b1b', icon:'bi-shield-exclamation'       },
        Advisory: { bg:'#fef3c7', color:'#92400e', icon:'bi-megaphone-fill'           },
        IOC:      { bg:'#ede9fe', color:'#5b21b6', icon:'bi-radioactive'              },
        Exploit:  { bg:'#ffedd5', color:'#7c2d12', icon:'bi-lightning-fill'           },
        Campaign: { bg:'#fce7f3', color:'#9d174d', icon:'bi-person-fill-exclamation'  },
    };
    const STAT = {
        Active:     { bg:'#fee2e2', color:'#991b1b', icon:'bi-record-circle-fill' },
        Monitoring: { bg:'#fef9c3', color:'#854d0e', icon:'bi-eye-fill'           },
        Mitigated:  { bg:'#d1fae5', color:'#065f46', icon:'bi-check-circle-fill'  },
        Archived:   { bg:'#f1f5f9', color:'#94a3b8', icon:'bi-archive-fill'       },
    };

    function badge(text, style) {
        return `<span style="display:inline-flex;align-items:center;gap:.25rem;padding:.18rem .55rem;
                border-radius:20px;font-size:.71rem;font-weight:700;
                background:${style.bg};color:${style.color}">
                <i class="bi ${style.icon}"></i>${text}</span>`;
    }

    // ── Filter state ──────────────────────────────────────────────────────────
    const filters = {
        type:     '{{ request("type") }}',
        status:   '{{ request("status") }}',
        severity: '{{ request("severity") }}',
        search:   '{{ request("search") }}',
        page:     1,
    };

    let searchTimer = null;
    const tbody     = document.getElementById('tableBody');
    const pagCont   = document.getElementById('paginationContainer');
    const clearBtn  = document.getElementById('clearFilters');

    function hasActiveFilters() {
        return filters.type || filters.status || filters.severity || filters.search;
    }

    function setLoading(on) {
        tbody.classList.toggle('loading', on);
    }

    // ── Fetch table rows via AJAX ─────────────────────────────────────────────
    function fetchResults(resetPage = true) {
        if (resetPage) filters.page = 1;
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v); });

        setLoading(true);

        fetch('{{ route("threat-intel.index") }}?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(data => {
            tbody.innerHTML = data.html;
            pagCont.innerHTML = data.links;
            pagCont.className = data.total > 20
                ? 'd-flex justify-content-center mt-2'
                : '';
            attachRowListeners();
            attachPaginationHandlers();
            setLoading(false);
            clearBtn.classList.toggle('d-none', !hasActiveFilters());
            history.replaceState(null, '',
                '{{ route("threat-intel.index") }}?' + params.toString());
        })
        .catch(() => setLoading(false));
    }

    // ── Type tabs ─────────────────────────────────────────────────────────────
    document.getElementById('typeTabBar').addEventListener('click', function (e) {
        const btn = e.target.closest('.type-btn');
        if (!btn) return;
        e.preventDefault();
        filters.type = btn.dataset.type;
        this.querySelectorAll('.type-btn').forEach(b =>
            b.classList.toggle('active', b.dataset.type === filters.type));
        fetchResults();
    });

    // ── Status / Severity selects ─────────────────────────────────────────────
    document.getElementById('statusSelect').addEventListener('change', function () {
        filters.status = this.value; fetchResults();
    });
    document.getElementById('severitySelect').addEventListener('change', function () {
        filters.severity = this.value; fetchResults();
    });

    // ── Debounced search ──────────────────────────────────────────────────────
    document.getElementById('searchInput').addEventListener('input', function () {
        clearTimeout(searchTimer);
        filters.search = this.value;
        searchTimer = setTimeout(() => fetchResults(), 350);
    });

    // ── Clear ─────────────────────────────────────────────────────────────────
    clearBtn.addEventListener('click', function (e) {
        e.preventDefault();
        filters.type = filters.status = filters.severity = filters.search = '';
        filters.page = 1;
        document.getElementById('searchInput').value = '';
        document.getElementById('statusSelect').value = '';
        document.getElementById('severitySelect').value = '';
        document.querySelectorAll('#typeTabBar .type-btn')
            .forEach(b => b.classList.toggle('active', b.dataset.type === ''));
        fetchResults();
    });

    // ── Pagination ────────────────────────────────────────────────────────────
    function attachPaginationHandlers() {
        pagCont.querySelectorAll('a[href]').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                try { filters.page = new URL(link.href).searchParams.get('page') || 1; }
                catch { filters.page = 1; }
                fetchResults(false);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }
    attachPaginationHandlers();

    // ── Row-level buttons (Detail, Status, Delete) ────────────────────────────
    const detailModal = new bootstrap.Modal(document.getElementById('sharedDetailModal'));
    const statusModal = new bootstrap.Modal(document.getElementById('sharedStatusModal'));
    const deleteForm  = document.getElementById('sharedDeleteForm');
    const statusForm  = document.getElementById('statusForm');
    const baseUrl     = '{{ url("/threat-intel") }}';

    function attachRowListeners() {
        tbody.querySelectorAll('tr[data-id]').forEach(row => {
            row.querySelector('.ti-detail-btn')?.addEventListener('click', () => openDetail(row));
            row.querySelector('.ti-status-btn')?.addEventListener('click', () => openStatus(row));
            row.querySelector('.ti-delete-btn')?.addEventListener('click', () => doDelete(row));
        });
    }
    attachRowListeners();

    // ── Detail modal population ───────────────────────────────────────────────
    function openDetail(row) {
        const d = row.dataset;
        const sev  = SEV[d.severity] || {};
        const typ  = TYPE[d.type]    || {};
        const stat = STAT[d.status]  || {};

        document.getElementById('dm-title').textContent = d.title;

        document.getElementById('dm-badges').innerHTML =
            badge(d.severity, sev) + ' ' + badge(d.type, typ) + ' ' + badge(d.status, stat);

        // CVE + CVSS row
        let cveHtml = '';
        if (d.cve) cveHtml += `<div>
            <div style="font-size:.68rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">CVE ID</div>
            <span style="font-family:monospace;font-size:.9rem;font-weight:700;color:#0f172a;
                         background:#f1f5f9;padding:.2rem .6rem;border-radius:7px">${d.cve}</span></div>`;
        if (d.cvss) cveHtml += `<div>
            <div style="font-size:.68rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">CVSS Score</div>
            <span style="display:inline-block;padding:.15rem .55rem;border-radius:20px;
                         font-size:.9rem;font-weight:800;font-family:monospace;
                         background:${sev.bg};color:${sev.color}">${parseFloat(d.cvss).toFixed(1)} / 10</span>
            <span style="font-size:.72rem;color:#94a3b8;margin-left:.35rem">${d.cvssLabel || ''}</span></div>`;
        document.getElementById('dm-cve-row').innerHTML = cveHtml;

        show('dm-desc-row',    d.description,   () => document.getElementById('dm-description').textContent = d.description);
        show('dm-affected-row',d.affected,       () => document.getElementById('dm-affected').textContent   = d.affected);

        // IOC
        if (d.iocValue && d.type === 'IOC') {
            document.getElementById('dm-ioc-row').style.display = '';
            document.getElementById('dm-ioc').innerHTML =
                (d.iocType ? `<span style="background:#ede9fe;color:#5b21b6;font-size:.72rem;font-weight:700;
                    padding:.1rem .4rem;border-radius:6px;margin-right:.4rem">${d.iocType}</span>` : '') +
                `<span style="font-family:monospace;font-size:.83rem;color:#0f172a">${d.iocValue}</span>`;
        } else {
            document.getElementById('dm-ioc-row').style.display = 'none';
        }

        // Right column
        show('dm-source-row', d.source, () => {
            document.getElementById('dm-source').textContent = d.source;
            const lnk = document.getElementById('dm-source-link');
            if (d.sourceUrl) { lnk.href = d.sourceUrl; lnk.style.display = ''; }
            else lnk.style.display = 'none';
        });
        show('dm-published-row', d.published, () => document.getElementById('dm-published').textContent = d.published);

        let added = d.created;
        if (d.creator) added += ' · ' + d.creator;
        document.getElementById('dm-added').textContent = added;

        show('dm-tags-row', d.tags, () => {
            document.getElementById('dm-tags').innerHTML = d.tags.split(',')
                .map(t => t.trim()).filter(Boolean)
                .map(t => `<span style="background:#e2e8f0;color:#475569;font-size:.7rem;
                    padding:.1rem .4rem;border-radius:5px;margin-right:.2rem;
                    margin-bottom:.2rem;display:inline-block">${t}</span>`)
                .join('');
        });

        // Correlation
        const corrRow = document.getElementById('dm-correlation-row');
        if (d.cve) {
            corrRow.style.display = '';
            const m = parseInt(d.matched || 0);
            document.getElementById('dm-correlation').innerHTML = m > 0
                ? `<div style="background:#dbeafe;border-radius:8px;padding:.5rem .75rem">
                    <div style="font-size:.8rem;font-weight:700;color:#1e40af">
                        <i class="bi bi-crosshair me-1"></i>${m} matched finding${m > 1 ? 's' : ''}
                    </div>
                    <div style="font-size:.72rem;color:#3b82f6;margin-top:.15rem">
                        ${d.cve} found in your tracked vulnerabilities
                    </div></div>`
                : `<div style="font-size:.78rem;color:#94a3b8">
                    <i class="bi bi-dash-circle me-1"></i>No findings matched for ${d.cve}</div>`;
        } else {
            corrRow.style.display = 'none';
        }

        detailModal.show();
    }

    function show(id, value, populate) {
        const el = document.getElementById(id);
        if (value) { el.style.display = ''; populate(); }
        else el.style.display = 'none';
    }

    // ── Status modal ──────────────────────────────────────────────────────────
    function openStatus(row) {
        const d = row.dataset;
        document.getElementById('sm-title').textContent = d.title.length > 55
            ? d.title.slice(0, 55) + '…' : d.title;
        statusForm.action = `${baseUrl}/${d.id}/status`;

        // Pre-select current status and update border colours
        statusForm.querySelectorAll('.sm-option').forEach(opt => {
            const radio = opt.querySelector('input[type=radio]');
            const isCur = opt.dataset.status === d.status;
            radio.checked = isCur;
            const sm = STAT[opt.dataset.status] || {};
            opt.style.borderColor  = isCur ? sm.color : '#e2e8f0';
            opt.style.background   = isCur ? sm.bg    : '#fafafa';
        });

        statusModal.show();
    }

    // ── Delete ────────────────────────────────────────────────────────────────
    function doDelete(row) {
        if (!confirm('Delete this intel item? This cannot be undone.')) return;
        deleteForm.action = `${baseUrl}/${row.dataset.id}`;
        deleteForm.submit();
    }

    // ── Add modal: show/hide IOC fields ───────────────────────────────────────
    window.toggleIocFields = function () {
        document.getElementById('iocFields')
            .classList.toggle('d-none', document.getElementById('addType').value !== 'IOC');
    };

})();
</script>
@endpush

@endsection
