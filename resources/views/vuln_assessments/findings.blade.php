@extends('layouts.app')
@section('title', $assessment->name . ' — Findings')

@section('content')
<style>
    :root { --lime: var(--primary); --lime-dark: var(--primary-dark); --lime-light: rgb(240,248,210); --lime-muted: rgb(232,244,195); }
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .badge-sev { padding:.22rem .65rem; border-radius:20px; font-size:.7rem; font-weight:700; display:inline-block; white-space:nowrap; }
    .sev-critical { background:#fce4e4; color:#851209; }
    .sev-high     { background:#ffe5e5; color:#f21807; }
    .sev-medium   { background:#fff0e0; color:#f27107; }
    .sev-low      { background:#edfde5; color:#4df207; }
    .rem-open        { background:#fee2e2; color:#991b1b; }
    .rem-in-progress { background:#fef9c3; color:#854d0e; }
    .rem-resolved    { background:#d1fae5; color:#065f46; }
    .rem-accepted    { background:#f1f5f9; color:#475569; }
    thead.lime-head th { background:var(--lime-muted) !important; color:var(--lime-dark) !important; }

    /* Summary cards */
    .sum-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:.65rem; margin-bottom:1.25rem; }
    .sum-card {
        background:#fff; border:1.5px solid #e8f5c2; border-radius:12px;
        padding:.75rem .9rem; text-align:center; text-decoration:none;
        transition:border-color .15s, box-shadow .15s, transform .1s;
        cursor:pointer; display:block;
    }
    .sum-card:hover { border-color:var(--lime); box-shadow:0 2px 10px rgba(0,0,0,.07); transform:translateY(-1px); }
    .sum-card.active { border-color:var(--lime-dark); box-shadow:0 0 0 2px var(--lime); }
    .sum-card .sc-val { font-size:1.65rem; font-weight:800; line-height:1.1; margin-bottom:.15rem; }
    .sum-card .sc-lbl { font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; }
    .sum-card-total   { cursor:default; }
    .sum-card-total:hover { transform:none; box-shadow:none; }

    /* Category badge */
    .cat-badge { display:inline-flex; align-items:center; gap:.28rem; font-size:.68rem; font-weight:700;
        padding:.15rem .5rem; border-radius:20px; white-space:nowrap; }

    /* Detail modal */
    .detail-section { margin-bottom:1.25rem; }
    .detail-section-title {
        font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px;
        color:#94a3b8; margin-bottom:.5rem; padding-bottom:.3rem;
        border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:.4rem;
    }
    .detail-meta-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:.75rem; }
    .detail-meta-item .label { font-size:.68rem; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:.3px; margin-bottom:.2rem; }
    .detail-meta-item .value { font-size:.85rem; color:#0f172a; font-weight:500; word-break:break-word; }
    .detail-meta-item .value.mono { font-family:monospace; font-size:.82rem; }
    .desc-box { background:#f8fafc; border-radius:8px; padding:.85rem 1rem; color:#374151;
        line-height:1.7; white-space:pre-wrap; font-size:.83rem; max-height:180px; overflow-y:auto; }
    .rem-box  { background:#f0fdf4; border-left:4px solid var(--primary); border-radius:0 8px 8px 0;
        padding:.85rem 1rem; color:#374151; line-height:1.7; white-space:pre-wrap; font-size:.83rem; max-height:180px; overflow-y:auto; }
    .out-box  { background:#0f172a; color:#e2e8f0; border-radius:8px; padding:.85rem 1rem;
        font-size:.75rem; overflow-x:auto; white-space:pre-wrap; max-height:220px; overflow-y:auto; font-family:monospace; }
    .sev-banner { border-radius:10px; padding:.65rem 1rem; display:flex; align-items:center; gap:.75rem; margin-bottom:1rem; }
    .sev-banner-critical { background:#fee2e2; border:1px solid #fca5a5; }
    .sev-banner-high     { background:#ffedd5; border:1px solid #fdba74; }
    .sev-banner-medium   { background:#fef9c3; border:1px solid #fde047; }
    .sev-banner-low      { background:#f1f5f9; border:1px solid #cbd5e1; }
    .sev-banner-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center;
        justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .row-selected { background:rgb(240,248,210) !important; }
    .bulk-bar {
        position:fixed; bottom:1.25rem; z-index:1050;
        left:calc(var(--sidebar-width,260px) + 1.5rem); right:1.5rem;
        background:#0f172a; color:#fff; border-radius:12px;
        padding:.65rem 1.25rem; display:none; align-items:center; gap:.75rem;
        box-shadow:0 8px 32px rgba(0,0,0,.35); flex-wrap:wrap;
    }
    .bulk-bar.visible { display:flex; }
    @media (max-width:991.98px) { .bulk-bar { left:1rem; right:1rem; } }
</style>

{{-- Page header --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1" style="font-size:.73rem">
            <li class="breadcrumb-item"><a href="{{ route('vuln-assessments.index') }}" style="color:#94a3b8;text-decoration:none">VA Assessments</a></li>
            <li class="breadcrumb-item"><a href="{{ route('vuln-assessments.show', $assessment) }}" style="color:#94a3b8;text-decoration:none">{{ Str::limit($assessment->name,40) }}</a></li>
            <li class="breadcrumb-item active" style="color:#64748b">Findings</li>
        </ol></nav>
        <h5 style="margin:0;font-weight:700;color:#0f172a">{{ $assessment->name }}</h5>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('vuln-assessments.progress', $assessment) }}" class="btn btn-sm"
            style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.38rem .9rem;font-size:.81rem">
            <i class="bi bi-graph-up-arrow me-1"></i>View Progress
        </a>
        <a href="{{ route('vuln-assessments.show', $assessment) }}" class="btn btn-sm"
            style="border:1.5px solid var(--lime);border-radius:9px;color:var(--lime-dark);background:#fff;font-weight:600;font-size:.81rem;padding:.38rem .9rem">
            <i class="bi bi-arrow-left me-1"></i>Overview
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Summary cards ──────────────────────────────────────────────────────── --}}
@php
    $baseUrl  = route('vuln-assessments.findings', $assessment);
    $activeT  = request('tracking');
    $activeSev= request('severity');

    $cards = [
        ['key'=>'total',     'val'=>$summary['total'],     'label'=>'Total',          'url'=>null,                                             'color'=>'#0f172a'],
        ['key'=>'open',      'val'=>$summary['open'],      'label'=>'Open',           'url'=>$baseUrl.'?tracking=open',                        'color'=>'#854d0e'],
        ['key'=>'resolved',  'val'=>$summary['resolved'],  'label'=>'Resolved',       'url'=>$baseUrl.'?tracking=resolved',                    'color'=>'#065f46'],
        ['key'=>'new',       'val'=>$summary['new'],       'label'=>'New',            'url'=>$baseUrl.'?tracking=new',                         'color'=>'#1d4ed8'],
        ['key'=>'persistent','val'=>$summary['persistent'],'label'=>'Persistent',     'url'=>$baseUrl.'?tracking=persistent',                  'color'=>'#c2410c'],
        ['key'=>'critical',  'val'=>$summary['critical'],  'label'=>'Critical',       'url'=>$baseUrl.'?severity=Critical',                    'color'=>'#851209'],
        ['key'=>'high',      'val'=>$summary['high'],      'label'=>'High',           'url'=>$baseUrl.'?severity=High',                        'color'=>'#f21807'],
        ['key'=>'medium',    'val'=>$summary['medium'],    'label'=>'Medium',         'url'=>$baseUrl.'?severity=Medium',                      'color'=>'#f27107'],
        ['key'=>'low',       'val'=>$summary['low'],       'label'=>'Low',            'url'=>$baseUrl.'?severity=Low',                         'color'=>'#4caf50'],
    ];
@endphp
<div class="sum-cards">
@foreach($cards as $card)
@php
    $isActive = ($card['key'] === 'open'       && $activeT  === 'open')
             || ($card['key'] === 'resolved'   && $activeT  === 'resolved')
             || ($card['key'] === 'new'        && $activeT  === 'new')
             || ($card['key'] === 'persistent' && $activeT  === 'persistent')
             || ($card['key'] === 'critical'   && $activeSev === 'Critical')
             || ($card['key'] === 'high'       && $activeSev === 'High')
             || ($card['key'] === 'medium'     && $activeSev === 'Medium')
             || ($card['key'] === 'low'        && $activeSev === 'Low');
@endphp
@if($card['url'])
<a href="{{ $card['url'] }}" class="sum-card{{ $isActive ? ' active' : '' }}">
    <div class="sc-val" style="color:{{ $card['color'] }}">{{ number_format($card['val']) }}</div>
    <div class="sc-lbl">{{ $card['label'] }}</div>
</a>
@else
<div class="sum-card sum-card-total" title="Click a card or use filters below to load findings">
    <div class="sc-val" style="color:{{ $card['color'] }}">{{ number_format($card['val']) }}</div>
    <div class="sc-lbl">{{ $card['label'] }}</div>
</div>
@endif
@endforeach
</div>

{{-- ── Filter form ────────────────────────────────────────────────────────── --}}
<div class="va-card" style="padding:.9rem 1.25rem;margin-bottom:1.25rem">
    <form method="GET" id="filterForm">
        <div class="row g-2 align-items-end">

            {{-- Keyword search --}}
            <div class="col-12 col-md-4">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">Keyword Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text" style="border-radius:8px 0 0 8px;background:#f8fafc"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Vuln name, plugin, CVE, IP, hostname…"
                        value="{{ request('search') }}" style="border-radius:0 8px 8px 0">
                </div>
            </div>

            {{-- Severity --}}
            <div class="col-6 col-md-2">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">Severity</label>
                <select name="severity" class="form-select form-select-sm" style="border-radius:8px">
                    <option value="">All Severities</option>
                    @foreach(['Critical','High','Medium','Low'] as $sev)
                    <option value="{{ $sev }}" {{ request('severity') === $sev ? 'selected' : '' }}>{{ $sev }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tracking status --}}
            <div class="col-6 col-md-2">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">Tracking Status</label>
                <select name="tracking" class="form-select form-select-sm" style="border-radius:8px">
                    <option value="">All Statuses</option>
                    <option value="new"        {{ request('tracking')==='new'        ? 'selected' : '' }}>New</option>
                    <option value="open"       {{ request('tracking')==='open'       ? 'selected' : '' }}>Open</option>
                    <option value="reopened"   {{ request('tracking')==='reopened'   ? 'selected' : '' }}>Reopened</option>
                    <option value="persistent" {{ request('tracking')==='persistent' ? 'selected' : '' }}>Persistent</option>
                    <option value="resolved"   {{ request('tracking')==='resolved'   ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>

            {{-- Remediation status --}}
            <div class="col-6 col-md-2">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">Remediation</label>
                <select name="rem_status" class="form-select form-select-sm" style="border-radius:8px">
                    <option value="">All</option>
                    <option value="unresolved" {{ request('rem_status')==='unresolved' ? 'selected' : '' }}>Unresolved</option>
                    @foreach(\App\Models\VulnRemediation::statuses() as $st)
                    <option value="{{ $st }}" {{ request('rem_status')===$st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Per page --}}
            <div class="col-6 col-md-2">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">Per Page</label>
                <select name="per_page" class="form-select form-select-sm" style="border-radius:8px">
                    @foreach([25,50,100] as $pp)
                    <option value="{{ $pp }}" {{ request()->integer('per_page',25)===$pp ? 'selected' : '' }}>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Row 2: advanced fields --}}
            <div class="col-6 col-md-2">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">Hostname</label>
                <input type="text" name="hostname" class="form-control form-control-sm" style="border-radius:8px"
                    placeholder="e.g. dc01" value="{{ request('hostname') }}">
            </div>

            <div class="col-6 col-md-2">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">IP Address</label>
                <input type="text" name="ip" class="form-control form-control-sm" style="border-radius:8px;font-family:monospace"
                    placeholder="e.g. 10.0.0" value="{{ request('ip') }}">
            </div>

            <div class="col-6 col-md-2">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">OS Family</label>
                <input type="text" name="os_family" class="form-control form-control-sm" style="border-radius:8px"
                    placeholder="e.g. Windows" value="{{ request('os_family') }}">
            </div>

            <div class="col-6 col-md-2">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">Asset Owner</label>
                <input type="text" name="asset_owner" class="form-control form-control-sm" style="border-radius:8px"
                    placeholder="Owner name" value="{{ request('asset_owner') }}">
            </div>

            <div class="col-4 col-md-1">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">Port</label>
                <input type="text" name="port" class="form-control form-control-sm" style="border-radius:8px;font-family:monospace"
                    placeholder="443" value="{{ request('port') }}">
            </div>

            <div class="col-4 col-md-1">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">Protocol</label>
                <input type="text" name="protocol" class="form-control form-control-sm" style="border-radius:8px;font-family:monospace"
                    placeholder="tcp" value="{{ request('protocol') }}">
            </div>

            <div class="col-4 col-md-2">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">Plugin ID</label>
                <input type="text" name="plugin_id" class="form-control form-control-sm" style="border-radius:8px;font-family:monospace"
                    placeholder="e.g. 10881" value="{{ request('plugin_id') }}">
            </div>

            <div class="col-6 col-md-2">
                <label style="font-size:.72rem;font-weight:600;color:#64748b;display:block;margin-bottom:.2rem">CVE</label>
                <input type="text" name="cve" class="form-control form-control-sm" style="border-radius:8px;font-family:monospace"
                    placeholder="CVE-2024-…" value="{{ request('cve') }}">
            </div>

            {{-- Apply / Reset buttons --}}
            <div class="col-auto d-flex gap-2 align-items-end" style="padding-bottom:0">
                <button type="submit" class="btn btn-sm"
                    style="background:var(--primary);color:#fff;border-radius:8px;border:none;font-weight:600;padding:.38rem 1rem">
                    <i class="bi bi-funnel-fill me-1"></i>Apply
                </button>
                @if($hasFilter)
                <a href="{{ route('vuln-assessments.findings', $assessment) }}" class="btn btn-sm"
                    style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff;font-weight:500">
                    <i class="bi bi-x me-1"></i>Reset
                </a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- ── Findings area ──────────────────────────────────────────────────────── --}}
@if(!$hasFilter)
{{-- Prompt state --}}
<div class="va-card" style="text-align:center;padding:3rem 2rem;border-style:dashed">
    <i class="bi bi-funnel" style="font-size:2.2rem;color:var(--lime);opacity:.5;display:block;margin-bottom:.75rem"></i>
    <div style="font-weight:600;color:#64748b;margin-bottom:.4rem">No filter applied</div>
    <div style="font-size:.83rem;color:#94a3b8">
        Click a summary card above or use the filter form to load findings.<br>
        This page has <strong style="color:#0f172a">{{ number_format($summary['total']) }}</strong> findings total.
    </div>
</div>
@else
{{-- Findings table --}}
<div class="va-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table" style="margin:0;font-size:.82rem">
            <thead class="lime-head">
                <tr>
                    <th style="padding:.65rem .85rem;width:36px">
                        <input type="checkbox" id="chk-all" style="accent-color:var(--lime-dark);width:15px;height:15px;cursor:pointer">
                    </th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">#</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Severity</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Plugin / CVE</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Vulnerability Name</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Host</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">System</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Owner</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Category</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Remediation</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">SLA</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap">Nessus File</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;text-align:center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php $rowNum = $findings->firstItem(); @endphp
                @forelse($findings as $f)
                @php
                    $sevClass  = 'sev-' . strtolower($f->severity);
                    $fp        = $f->plugin_id . '|' . $f->ip_address;
                    $rem       = $remediations->get($fp);
                    $remStatus = $rem?->status ?? 'Open';
                    $remClass  = 'rem-' . str_replace([' ','_'], '-', strtolower($remStatus));
                    [$tsBg, $tsColor, $tsIcon] = \App\Models\VulnTracked::statusStyle($f->tracking_status);
                @endphp
                <tr style="border-color:#f1f5f9" id="row-{{ $f->id }}">
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;width:36px">
                        <input type="checkbox" class="row-chk" value="{{ $f->id }}"
                            style="accent-color:var(--lime-dark);width:15px;height:15px;cursor:pointer">
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;color:#94a3b8;font-size:.75rem">{{ $rowNum++ }}</td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9">
                        <span class="badge-sev {{ $sevClass }}">{{ $f->severity }}</span>
                        <div style="margin-top:.22rem">
                            <span style="display:inline-block;background:{{ $tsBg }};color:{{ $tsColor }};border-radius:5px;padding:.08rem .38rem;font-size:.63rem;font-weight:700">
                                {{ $f->tracking_status }}
                            </span>
                        </div>
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;font-family:monospace;font-size:.76rem;color:#374151">
                        <div style="font-weight:600">{{ $f->plugin_id }}</div>
                        @if($f->cve)<div style="color:#64748b;font-size:.72rem">{{ $f->cve }}</div>@endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;max-width:240px">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#detailModal{{ $f->id }}"
                           style="display:block;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-decoration:none"
                           title="{{ $f->vuln_name }}">{{ $f->vuln_name }}</a>
                        @if($f->description)
                        <div style="font-size:.73rem;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ Str::limit($f->description,65) }}</div>
                        @endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9">
                        <div style="font-family:monospace;font-weight:600;color:#0f172a;font-size:.8rem">{{ $f->ip_address }}</div>
                        @if($f->hostname)<div style="font-size:.72rem;color:#64748b">{{ $f->hostname }}</div>@endif
                        @if($f->port)<div style="font-size:.68rem;color:#94a3b8;font-family:monospace">:{{ $f->port }}{{ $f->protocol ? '/'.$f->protocol : '' }}</div>@endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;max-width:150px">
                        @if($f->system_name)
                        <div style="font-weight:600;color:#0f172a;font-size:.8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $f->system_name }}">
                            <i class="bi bi-hdd-network" style="color:#94a3b8;font-size:.72rem;margin-right:.25rem"></i>{{ $f->system_name }}
                        </div>
                        @else<span style="color:#cbd5e1;font-size:.75rem">—</span>@endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;max-width:130px">
                        @if($f->system_owner)
                        <div style="font-size:.78rem;color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $f->system_owner }}">
                            <i class="bi bi-person" style="color:#94a3b8;font-size:.72rem;margin-right:.2rem"></i>{{ $f->system_owner }}
                        </div>
                        @else<span style="color:#cbd5e1;font-size:.75rem">—</span>@endif
                    </td>
                    @php
                        $cat = $f->vuln_category ?? 'Other';
                        [$catBg, $catColor, $catIcon] = \App\Models\VulnFinding::categoryStyle($cat);
                    @endphp
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;white-space:nowrap">
                        <div style="display:inline-flex;align-items:center;gap:.28rem;background:{{ $catBg }};color:{{ $catColor }};
                                    font-size:.68rem;font-weight:700;padding:.2rem .55rem;border-radius:20px;border:1px solid {{ $catColor }}22">
                            <i class="bi {{ $catIcon }}" style="font-size:.65rem"></i>{{ $cat }}
                        </div>
                        @if($f->affected_component)
                        <div style="font-size:.67rem;color:#94a3b8;margin-top:.22rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                             title="{{ $f->affected_component }}">{{ $f->affected_component }}</div>
                        @endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9">
                        <span class="badge-sev {{ $remClass }}" style="font-size:.68rem">{{ $remStatus }}</span>
                        @if($rem?->assignedGroup)
                        <div style="font-size:.7rem;color:#94a3b8;margin-top:.2rem">
                            <i class="bi bi-people-fill" style="font-size:.65rem"></i> {{ $rem->assignedGroup->name }}
                        </div>
                        @endif
                        @if($rem?->due_date)
                        <div style="font-size:.7rem;color:{{ $rem->due_date->isPast() && $remStatus !== 'Resolved' ? '#dc2626' : '#94a3b8' }};margin-top:.1rem">
                            <i class="bi bi-calendar3"></i> {{ $rem->due_date->format('d M Y') }}
                        </div>
                        @endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;white-space:nowrap">
                        @if($slaPolicy)
                        @php
                            [$slaStatus, $slaLabel, $slaBg, $slaColor] = $slaPolicy->slaStatus(
                                $f->severity,
                                \Carbon\Carbon::parse($f->first_seen_at),
                                $f->tracking_status === 'Resolved',
                                $f->resolved_at ? \Carbon\Carbon::parse($f->resolved_at) : null
                            );
                        @endphp
                        <span style="display:inline-block;background:{{ $slaBg }};color:{{ $slaColor }};border-radius:6px;padding:.1rem .45rem;font-size:.68rem;font-weight:700">{{ $slaLabel }}</span>
                        @else<span style="color:#cbd5e1;font-size:.75rem">—</span>@endif
                    </td>
                    {{-- Nessus File --}}
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9">
                        @if($initialScan)
                            @if($initialScan->file_path)
                            <a href="{{ route('vuln-assessments.download-initial-file', $assessment) }}"
                               style="display:flex;align-items:center;gap:.25rem;color:#1d4ed8;text-decoration:none;font-size:.72rem;font-weight:500;margin-bottom:.1rem;white-space:nowrap"
                               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                <i class="bi bi-file-earmark-zip-fill" style="font-size:.75rem;flex-shrink:0;color:#3b82f6"></i>
                                {{ $initialScan->filename }}
                            </a>
                            @else
                            <div style="display:flex;align-items:center;gap:.25rem;font-size:.72rem;color:#64748b;margin-bottom:.1rem;white-space:nowrap">
                                <i class="bi bi-file-earmark-zip" style="font-size:.75rem;flex-shrink:0;color:#94a3b8"></i>
                                {{ $initialScan->filename }}
                            </div>
                            @endif
                        @endif
                        @if($verificationScan)
                            @if($verificationScan->file_path)
                            <a href="{{ route('vuln-assessments.download-verification-file', $assessment) }}"
                               style="display:flex;align-items:center;gap:.25rem;color:#15803d;text-decoration:none;font-size:.72rem;font-weight:500;white-space:nowrap"
                               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                <i class="bi bi-file-earmark-zip-fill" style="font-size:.75rem;flex-shrink:0;color:#22c55e"></i>
                                {{ $verificationScan->filename }}
                            </a>
                            @else
                            <div style="display:flex;align-items:center;gap:.25rem;font-size:.72rem;color:#64748b;white-space:nowrap">
                                <i class="bi bi-file-earmark-zip" style="font-size:.75rem;flex-shrink:0;color:#94a3b8"></i>
                                {{ $verificationScan->filename }}
                            </div>
                            @endif
                        @endif
                        @if(!$initialScan && !$verificationScan)
                        <span style="color:#cbd5e1;font-size:.75rem">—</span>
                        @endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;text-align:center">
                        <div class="d-flex justify-content-center gap-1">
                            <button class="btn btn-sm" style="border-radius:8px;border:1px solid #e2e8f0;color:#64748b;padding:.22rem .55rem;font-size:.74rem"
                                title="Assign Group" data-bs-toggle="modal" data-bs-target="#remModal{{ $f->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm" style="border-radius:8px;border:1.5px solid var(--primary);color:var(--primary-dark);padding:.22rem .55rem;font-size:.74rem;background:rgb(232,244,195)"
                                title="View Detail" data-bs-toggle="modal" data-bs-target="#detailModal{{ $f->id }}">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                {{-- Assign Group Modal --}}
                <div class="modal fade" id="remModal{{ $f->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
                            <div class="modal-header" style="border-bottom:2px solid var(--primary);padding:1rem 1.5rem">
                                <h5 class="modal-title" style="font-size:.9rem;font-weight:700;color:#0f172a">
                                    <i class="bi bi-people-fill me-2" style="color:var(--primary)"></i>Assign to Group
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            @if($rem)
                            <form method="POST" action="{{ route('vuln-assessments.remediation.update', [$assessment, $rem]) }}">
                                @csrf @method('PATCH')
                            @else
                            <form method="POST" action="{{ route('vuln-assessments.remediation.bulk-update', $assessment) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="finding_ids" value="{{ $f->id }}">
                            @endif
                                <div class="modal-body" style="padding:1.5rem">
                                    <div style="background:#f8fafc;border-radius:8px;padding:.6rem .85rem;margin-bottom:1.25rem;font-size:.8rem;color:#374151">
                                        <span class="badge-sev {{ $sevClass }}" style="font-size:.68rem;margin-right:.4rem">{{ $f->severity }}</span>
                                        <strong>{{ $f->vuln_name }}</strong><br>
                                        <span style="font-family:monospace;font-size:.75rem;color:#64748b">{{ $f->ip_address }} &middot; Plugin {{ $f->plugin_id }}</span>
                                    </div>
                                    <div class="mb-4 p-3" style="background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0">
                                        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:.45rem">
                                            <i class="bi bi-lock-fill me-1"></i>Remediation Status
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge-sev {{ $remClass }}">{{ $remStatus }}</span>
                                            <span style="font-size:.75rem;color:#94a3b8">Managed automatically by the tracking engine</span>
                                        </div>
                                    </div>
                                    <div class="mb-1">
                                        <label style="font-size:.82rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">
                                            <i class="bi bi-people-fill me-1" style="color:var(--primary)"></i>Assign to User Group
                                        </label>
                                        <select name="assigned_group_id" class="form-select form-select-sm" style="border-radius:8px">
                                            <option value="">— Unassigned —</option>
                                            @foreach($userGroups as $g)
                                            <option value="{{ $g->id }}" @selected($rem?->assigned_group_id == $g->id)>{{ $g->name }}</option>
                                            @endforeach
                                        </select>
                                        <div style="font-size:.72rem;color:#94a3b8;margin-top:.3rem">Select a group to own remediation of this finding.</div>
                                    </div>
                                </div>
                                <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.75rem 1.5rem">
                                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                                        style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff;font-weight:500">Cancel</button>
                                    <button type="submit" class="btn btn-sm"
                                        style="background:var(--primary);color:#fff;border-radius:8px;font-weight:600;border:none;padding:.4rem 1.2rem">
                                        <i class="bi bi-check-lg me-1"></i>Assign Group
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Detail Modal --}}
                <div class="modal fade" id="detailModal{{ $f->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
                            <div class="modal-header" style="border-bottom:2px solid var(--primary);padding:1rem 1.5rem;gap:.75rem;flex-wrap:wrap">
                                <div style="flex:1;min-width:0">
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                        <span class="badge-sev {{ $sevClass }}" style="font-size:.75rem">{{ $f->severity }}</span>
                                        <span class="badge-sev" style="font-size:.68rem;background:{{ $tsBg }};color:{{ $tsColor }}">{{ $f->tracking_status }}</span>
                                        <span class="badge-sev {{ $remClass }}" style="font-size:.68rem">{{ $remStatus }}</span>
                                    </div>
                                    <h5 class="modal-title mb-0" style="font-size:.95rem;font-weight:700;color:#0f172a;line-height:1.3">{{ $f->vuln_name }}</h5>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" style="padding:1.5rem">
                                @php
                                    $bannerIconColors = ['Critical'=>'#851209','High'=>'#f21807','Medium'=>'#f27107','Low'=>'#4df207'];
                                    $bannerBg = ['Critical'=>'#fce4e4','High'=>'#ffe5e5','Medium'=>'#fff0e0','Low'=>'#edfde5'];
                                    $bannerBorder = ['Critical'=>'#fca5a5','High'=>'#fdba74','Medium'=>'#fde047','Low'=>'#cbd5e1'];
                                    $bannerIcon = ['Critical'=>'bi-exclamation-octagon-fill','High'=>'bi-exclamation-triangle-fill','Medium'=>'bi-exclamation-circle-fill','Low'=>'bi-info-circle-fill'];
                                    $bColor  = $bannerIconColors[$f->severity] ?? '#475569';
                                    $bBg     = $bannerBg[$f->severity] ?? '#f1f5f9';
                                    $bBorder = $bannerBorder[$f->severity] ?? '#e2e8f0';
                                    $bIcon   = $bannerIcon[$f->severity] ?? 'bi-info-circle-fill';
                                @endphp
                                <div style="border-radius:10px;padding:.65rem 1rem;display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;background:{{ $bBg }};border:1px solid {{ $bBorder }}">
                                    <div style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;background:{{ $bColor }}22">
                                        <i class="bi {{ $bIcon }}" style="color:{{ $bColor }}"></i>
                                    </div>
                                    <div>
                                        <div style="font-size:.82rem;font-weight:700;color:{{ $bColor }}">{{ $f->severity }} Severity Vulnerability</div>
                                        <div style="font-size:.75rem;color:#64748b">Plugin {{ $f->plugin_id }}
                                            @if($f->cve) &nbsp;&middot;&nbsp; <strong>{{ $f->cve }}</strong>@endif
                                            &nbsp;&middot;&nbsp; Since {{ $f->first_seen_at->format('d M Y') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="detail-section">
                                    <div class="detail-section-title"><i class="bi bi-hdd-network-fill" style="color:var(--primary)"></i>Host Information</div>
                                    <div class="detail-meta-grid">
                                        <div class="detail-meta-item"><div class="label">IP Address</div><div class="value mono" style="color:#0f172a;font-weight:700">{{ $f->ip_address }}</div></div>
                                        <div class="detail-meta-item"><div class="label">Hostname</div><div class="value">{{ $f->hostname ?: '—' }}</div></div>
                                        <div class="detail-meta-item"><div class="label">Port / Protocol</div><div class="value mono">{{ $f->port ? $f->port . ($f->protocol ? '/'.$f->protocol : '') : '—' }}</div></div>
                                        <div class="detail-meta-item"><div class="label">OS</div><div class="value">{{ $f->os_name ?: '—' }}</div></div>
                                    </div>
                                </div>

                                <div class="detail-section">
                                    <div class="detail-section-title"><i class="bi bi-bug-fill" style="color:#dc2626"></i>Vulnerability Details</div>
                                    <div class="detail-meta-grid mb-3">
                                        <div class="detail-meta-item"><div class="label">Plugin ID</div><div class="value mono" style="font-weight:700">{{ $f->plugin_id }}</div></div>
                                        <div class="detail-meta-item"><div class="label">CVE</div><div class="value mono">{{ $f->cve ?: '—' }}</div></div>
                                        <div class="detail-meta-item"><div class="label">Severity</div><div class="value"><span class="badge-sev {{ $sevClass }}">{{ $f->severity }}</span></div></div>
                                        <div class="detail-meta-item"><div class="label">First Seen</div><div class="value">{{ $f->first_seen_at->format('d M Y') }}</div></div>
                                        <div class="detail-meta-item"><div class="label">Last Seen</div><div class="value">{{ $f->last_seen_at->format('d M Y') }}</div></div>
                                    </div>
                                    @if($f->description)<div class="label mb-1">Description</div><div class="desc-box">{{ $f->description }}</div>@endif
                                </div>

                                <div class="detail-section">
                                    <div class="detail-section-title"><i class="bi bi-tags-fill" style="color:var(--primary)"></i>Auto Classification</div>
                                    @php $fCat=$f->vuln_category??'Other'; [$fbg,$fcol,$ficon]=\App\Models\VulnFinding::categoryStyle($fCat); @endphp
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <div style="background:{{ $fbg }};border:1.5px solid {{ $fcol }}44;border-radius:10px;padding:.65rem 1rem;min-width:130px;text-align:center">
                                            <div style="font-size:1.3rem;color:{{ $fcol }}"><i class="bi {{ $ficon }}"></i></div>
                                            <div style="font-size:.82rem;font-weight:700;color:{{ $fcol }};margin-top:.2rem">{{ $fCat }}</div>
                                            <div style="font-size:.68rem;color:#94a3b8;margin-top:.1rem">Category</div>
                                        </div>
                                        @if($f->affected_component)
                                        <div>
                                            <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.3px;margin-bottom:.2rem">Affected Component</div>
                                            <div style="font-size:.9rem;font-weight:700;color:#0f172a">{{ $f->affected_component }}</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                @if($f->remediation_text)
                                <div class="detail-section">
                                    <div class="detail-section-title"><i class="bi bi-wrench-adjustable-circle-fill" style="color:var(--primary)"></i>Recommended Fix</div>
                                    <div class="rem-box">{{ $f->remediation_text }}</div>
                                </div>
                                @endif

                                <div class="detail-section">
                                    <div class="detail-section-title"><i class="bi bi-clipboard2-check-fill" style="color:#2563eb"></i>Remediation Tracking</div>
                                    <div class="detail-meta-grid">
                                        <div class="detail-meta-item"><div class="label">Status</div><div class="value"><span class="badge-sev {{ $remClass }}">{{ $remStatus }}</span></div></div>
                                        <div class="detail-meta-item">
                                            <div class="label">Due Date</div>
                                            <div class="value" style="color:{{ $rem?->due_date?->isPast() && $remStatus !== 'Resolved' ? '#dc2626' : 'inherit' }}">
                                                {{ $rem?->due_date?->format('d M Y') ?? '—' }}
                                            </div>
                                        </div>
                                        @if($rem?->comments)
                                        <div class="detail-meta-item" style="grid-column:1/-1">
                                            <div class="label">Comments</div>
                                            <div class="value" style="white-space:pre-wrap;background:#f8fafc;border-radius:6px;padding:.5rem .75rem;font-size:.82rem">{{ $rem->comments }}</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                @if($f->plugin_output)
                                <div class="detail-section" style="margin-bottom:0">
                                    <div class="detail-section-title">
                                        <i class="bi bi-terminal-fill" style="color:#475569"></i>Plugin Output
                                        <button type="button" onclick="copyOutput({{ $f->id }})"
                                            style="margin-left:auto;font-size:.7rem;background:none;border:1px solid #374151;color:#94a3b8;border-radius:5px;padding:.1rem .45rem;cursor:pointer">
                                            <i class="bi bi-clipboard me-1"></i>Copy
                                        </button>
                                    </div>
                                    <pre class="out-box" id="output-{{ $f->id }}">{{ $f->plugin_output }}</pre>
                                </div>
                                @endif
                            </div>
                            <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.75rem 1.5rem;gap:.5rem">
                                <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                                    style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff;font-weight:500">Close</button>
                                <button class="btn btn-sm" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#remModal{{ $f->id }}"
                                    style="background:var(--primary);color:#fff;border-radius:8px;font-weight:600;border:none;padding:.4rem 1.2rem">
                                    <i class="bi bi-people-fill me-1"></i>Assign Group
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                @empty
                <tr>
                    <td colspan="13" style="text-align:center;padding:3rem;color:#94a3b8">
                        <i class="bi bi-bug" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
                        No findings match the current filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($findings->hasPages())
    <div style="padding:.75rem 1.5rem;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
        <div style="font-size:.82rem;color:#64748b">
            Showing {{ $findings->firstItem() }}–{{ $findings->lastItem() }} of {{ number_format($findings->total()) }} findings
        </div>
        {{ $findings->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

{{-- Bulk Assign Bar --}}
<form method="POST" id="bulkForm" action="{{ route('vuln-assessments.remediation.bulk-update', $assessment) }}">
    @csrf @method('PATCH')
    <input type="hidden" name="finding_ids" id="bulkIds">
    <div class="bulk-bar" id="bulkBar">
        <span style="font-size:.82rem;font-weight:600;white-space:nowrap">
            <i class="bi bi-check2-square me-1" style="color:var(--primary)"></i>
            <span id="bulkCount">0</span> selected
        </span>
        <div style="display:flex;align-items:center;gap:.5rem;flex:1;min-width:180px">
            <label style="font-size:.78rem;color:#94a3b8;white-space:nowrap">Assign to:</label>
            <select name="assigned_group_id" class="form-select form-select-sm"
                style="border-radius:8px;font-size:.8rem;background:#1e293b;color:#fff;border-color:#334155;flex:1">
                <option value="">— No Group —</option>
                @foreach($userGroups as $g)
                <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
                <option value="__clear__">&#10005; Clear Group</option>
            </select>
        </div>
        <button type="submit" class="btn btn-sm"
            style="background:var(--primary);color:#fff;border-radius:8px;font-weight:600;border:none;padding:.38rem 1rem;white-space:nowrap">
            <i class="bi bi-people-fill me-1"></i>Assign Group
        </button>
        <button type="button" id="bulkCancel"
            style="background:none;border:1px solid #475569;border-radius:8px;color:#94a3b8;padding:.38rem .75rem;font-size:.8rem;cursor:pointer;white-space:nowrap">
            Cancel
        </button>
    </div>
</form>
@endif

@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    var chkAll    = document.getElementById('chk-all');
    var bulkBar   = document.getElementById('bulkBar');
    var bulkIds   = document.getElementById('bulkIds');
    var bulkCount = document.getElementById('bulkCount');
    if (!bulkBar) return;

    function getChecked() { return Array.from(document.querySelectorAll('.row-chk:checked')); }
    function updateBar() {
        var checked = getChecked();
        bulkCount.textContent = checked.length;
        bulkBar.classList.toggle('visible', checked.length > 0);
        bulkIds.value = checked.map(c => c.value).join(',');
    }
    function setRowHighlight(chk) {
        var row = chk.closest('tr');
        if (row) row.classList.toggle('row-selected', chk.checked);
    }
    if (chkAll) {
        chkAll.addEventListener('change', function () {
            document.querySelectorAll('.row-chk').forEach(c => { c.checked = chkAll.checked; setRowHighlight(c); });
            updateBar();
        });
    }
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('row-chk')) return;
        setRowHighlight(e.target);
        var all = document.querySelectorAll('.row-chk'), chkd = getChecked();
        if (chkAll) { chkAll.indeterminate = chkd.length > 0 && chkd.length < all.length; chkAll.checked = chkd.length === all.length && all.length > 0; }
        updateBar();
    });
    var cancelBtn = document.getElementById('bulkCancel');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            document.querySelectorAll('.row-chk').forEach(c => { c.checked = false; setRowHighlight(c); });
            if (chkAll) { chkAll.checked = false; chkAll.indeterminate = false; }
            updateBar();
        });
    }
})();

function copyOutput(id) {
    var el = document.getElementById('output-' + id);
    if (!el) return;
    navigator.clipboard.writeText(el.innerText).then(function () {
        var btns = document.querySelectorAll('[onclick="copyOutput(' + id + ')"]');
        btns.forEach(b => { b.innerHTML = '<i class="bi bi-check me-1"></i>Copied'; });
        setTimeout(() => btns.forEach(b => { b.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copy'; }), 2000);
    });
}
</script>
@endpush
