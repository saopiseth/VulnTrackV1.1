@extends('layouts.app')
@section('title', $assessment->name . ' — Findings')

@section('content')
<style>
    :root { --lime: var(--primary); --lime-dark: var(--primary-dark); --lime-light: rgb(240,248,210); --lime-muted: rgb(232,244,195); }
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .badge-sev { padding:.22rem .65rem; border-radius:20px; font-size:.7rem; font-weight:700; display:inline-block; white-space:nowrap; }
    .sev-critical { background:#fee2e2; color:#991b1b; }
    .sev-high     { background:#ffedd5; color:#9a3412; }
    .sev-medium   { background:#fef9c3; color:#854d0e; }
    .sev-low      { background:#f1f5f9; color:#475569; }
    .rem-open        { background:#fee2e2; color:#991b1b; }
    .rem-in-progress { background:#fef9c3; color:#854d0e; }
    .rem-resolved    { background:#d1fae5; color:#065f46; }
    .rem-accepted    { background:#f1f5f9; color:#475569; }
    thead.lime-head th { background:var(--lime-muted) !important; color:var(--lime-dark) !important; }

    /* Category badge */
    .cat-badge { display:inline-flex; align-items:center; gap:.28rem; font-size:.68rem; font-weight:700;
        padding:.15rem .5rem; border-radius:20px; white-space:nowrap; }
    .cat-badge i { font-size:.7rem; }

    /* Detail modal enhancements */
    .detail-section { margin-bottom:1.25rem; }
    .detail-section-title {
        font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px;
        color:#94a3b8; margin-bottom:.5rem; padding-bottom:.3rem;
        border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:.4rem;
    }
    .detail-meta-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(140px,1fr)); gap:.75rem; }
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
    .group-popover .popover-header { background:rgb(232,244,195); color:var(--primary-dark); font-size:.82rem; border-bottom:1px solid var(--primary-light); }
    .group-popover .popover-body { padding:.65rem .85rem; }
    .bulk-bar {
        position:fixed; bottom:1.25rem; z-index:1050;
        left:calc(var(--sidebar-width, 260px) + 1.5rem); right:1.5rem;
        background:#0f172a; color:#fff; border-radius:12px;
        padding:.65rem 1.25rem; display:none; align-items:center; gap:.75rem;
        box-shadow:0 8px 32px rgba(0,0,0,.35); flex-wrap:wrap;
    }
    .bulk-bar.visible { display:flex; }
    @media (max-width:991.98px) {
        .bulk-bar { left:1rem; right:1rem; }
    }
</style>

{{-- ── Page header ─────────────────────────────────────────────── --}}
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


{{-- Remediation Status Filter --}}
@php
    $remStatusStyles = [
        'Open'          => ['bg'=>'#fee2e2','color'=>'#991b1b','border'=>'#fca5a5','icon'=>'bi-circle-fill'],
        'In Progress'   => ['bg'=>'#fef9c3','color'=>'#854d0e','border'=>'#fde047','icon'=>'bi-arrow-repeat'],
        'Resolved'      => ['bg'=>'#d1fae5','color'=>'#065f46','border'=>'#6ee7b7','icon'=>'bi-check-circle-fill'],
        'Accepted Risk' => ['bg'=>'#f1f5f9','color'=>'#475569','border'=>'#cbd5e1','icon'=>'bi-shield-check'],
    ];
    $unresolvedTotal = ($remStatusCounts['Open'] ?? 0) + ($remStatusCounts['In Progress'] ?? 0);
@endphp
<div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
    <span style="font-size:.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Status:</span>

    {{-- All --}}
    <a href="{{ route('vuln-assessments.findings', array_merge([$assessment], request()->only(['tracking','search','ip']))) }}"
       class="btn btn-sm" style="border-radius:8px;font-weight:600;font-size:.78rem;
        background:{{ !$remStatusFilter ? '#0f172a' : '#fff' }};
        color:{{ !$remStatusFilter ? '#fff' : '#64748b' }};
        border:1.5px solid {{ !$remStatusFilter ? '#0f172a' : '#e2e8f0' }}">
        All <span style="opacity:.75">({{ $remStatusCounts->sum() }})</span>
    </a>

    {{-- Per-status tabs --}}
    @foreach($remStatusStyles as $st => $style)
    <a href="{{ route('vuln-assessments.findings', array_merge([$assessment], request()->only(['tracking','search','ip']), ['rem_status'=>$st])) }}"
       class="btn btn-sm" style="border-radius:8px;font-weight:700;font-size:.78rem;
        background:{{ $remStatusFilter===$st ? $style['color'] : $style['bg'] }};
        color:{{ $remStatusFilter===$st ? '#fff' : $style['color'] }};
        border:1.5px solid {{ $style['border'] }}">
        <i class="bi {{ $style['icon'] }} me-1"></i>
        {{ $st }} <span style="opacity:.8">({{ $remStatusCounts[$st] ?? 0 }})</span>
    </a>
    @endforeach
</div>


{{-- Search + IP + Category filter --}}
<div class="va-card" style="padding:.85rem 1.25rem;margin-bottom:1.25rem">
    <form method="GET" class="row g-2 align-items-end">
        @if(request('rem_status'))<input type="hidden" name="rem_status" value="{{ request('rem_status') }}">@endif
        @if(request('tracking'))<input type="hidden" name="tracking" value="{{ request('tracking') }}">@endif

        {{-- Keyword search --}}
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text" style="border-radius:8px 0 0 8px;background:#f8fafc"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search vulnerability name, IP, plugin ID, CVE…"
                    value="{{ request('search') }}" style="border-radius:0 8px 8px 0">
            </div>
        </div>

        {{-- IP filter --}}
        <div class="col-6 col-md-2">
            <input type="text" name="ip" class="form-control form-control-sm" placeholder="Filter by IP"
                value="{{ request('ip') }}" style="border-radius:8px;font-family:monospace">
        </div>

        {{-- Category filter --}}
        <div class="col-6 col-md-3">
            <select name="category" class="form-select form-select-sm" style="border-radius:8px;font-size:.8rem">
                <option value="">All Categories</option>
                @foreach(\App\Models\VulnFinding::categories() as $cat)
                @php [$cBg, $cCol] = \App\Models\VulnFinding::categoryStyle($cat); @endphp
                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                    {{ $cat }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-sm" style="background:var(--primary);color:#fff;border-radius:8px;border:none;font-weight:600">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
            @if(request()->hasAny(['search','ip','rem_status','category']))
            <a href="{{ route('vuln-assessments.findings', array_filter(['tracking' => request('tracking')])) }}"
               class="btn btn-sm" style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff;font-weight:500">
               <i class="bi bi-x me-1"></i>Clear
            </a>
            @endif
        </div>
    </form>
</div>

{{-- Findings Table --}}
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
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">System Name</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">OS / Application</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Category</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Remediation</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Assigned To</th>
                    <th style="padding:.65rem .85rem;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;text-align:center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php $rowNum = $findings->firstItem(); @endphp
                @forelse($findings as $f)
                @php
                    $sevClass = 'sev-' . strtolower($f->severity);
                    $fp       = $f->plugin_id . '|' . $f->ip_address;
                    $rem      = $remediations->get($fp);
                    $remStatus= $rem?->status ?? 'Open';
                    $remClass = 'rem-' . str_replace([' ','_'], '-', strtolower($remStatus));

                    // Tracking status badge
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
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;font-family:monospace;font-size:.76rem;color:#374151">
                        <div style="font-weight:600">{{ $f->plugin_id }}</div>
                        @if($f->cve)<div style="color:#64748b;font-size:.72rem">{{ $f->cve }}</div>@endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;max-width:240px">
                        <div style="font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $f->vuln_name }}">{{ $f->vuln_name }}</div>
                        @if($f->description)
                        <div style="font-size:.73rem;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $f->description }}">{{ Str::limit($f->description, 65) }}</div>
                        @endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9">
                        <div style="font-family:monospace;font-weight:600;color:#0f172a;font-size:.8rem">{{ $f->ip_address }}</div>
                        @if($f->hostname)
                        <div style="font-size:.72rem;color:#64748b">{{ $f->hostname }}</div>
                        @endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;max-width:150px">
                        @if($f->system_name)
                            <div style="font-weight:600;color:#0f172a;font-size:.8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $f->system_name }}">
                                <i class="bi bi-hdd-network" style="color:#94a3b8;font-size:.72rem;margin-right:.25rem"></i>{{ $f->system_name }}
                            </div>
                        @else
                            <span style="color:#cbd5e1;font-size:.75rem">—</span>
                        @endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9">
                        @php
                            $osDisplay    = $f->os_name ?? $f->os_detected;
                            $appComponent = $f->affected_component;

                            // Derive OS family (fall back to name-based detection)
                            $osFam = $f->os_family;
                            if (!$osFam || $osFam === 'Other') {
                                $haystack = strtolower($osDisplay ?? $appComponent ?? '');
                                if (preg_match('/windows/i', $haystack))
                                    $osFam = 'Windows';
                                elseif (preg_match('/linux|ubuntu|centos|red\s?hat|rhel|debian|fedora|suse|mint|arch|rocky|alma/i', $haystack))
                                    $osFam = 'Linux';
                                elseif (preg_match('/unix|freebsd|solaris|aix|hp-ux/i', $haystack))
                                    $osFam = 'Unix';
                                else
                                    $osFam = 'Other';
                            }

                            $osFamIcons = [
                                'Windows' => ['icon'=>'bi-windows',       'bg'=>'#dbeafe','color'=>'#1e40af'],
                                'Linux'   => ['icon'=>'bi-ubuntu',        'bg'=>'#d1fae5','color'=>'#065f46'],
                                'Unix'    => ['icon'=>'bi-terminal-fill', 'bg'=>'#ffedd5','color'=>'#7c2d12'],
                                'Other'   => ['icon'=>'bi-cpu-fill',      'bg'=>'#f3f4f6','color'=>'#374151'],
                            ];
                            $osFamMeta = $osFamIcons[$osFam] ?? $osFamIcons['Other'];
                        @endphp

                        {{-- OS family badge --}}
                        @if($osFam && $osFam !== 'Other')
                        <div style="display:inline-flex;align-items:center;gap:.3rem;font-size:.68rem;font-weight:600;
                            background:{{ $osFamMeta['bg'] }};color:{{ $osFamMeta['color'] }};
                            padding:.12rem .45rem;border-radius:5px"
                            title="{{ $osDisplay ?? $appComponent ?? $osFam }}">
                            <i class="bi {{ $osFamMeta['icon'] }}" style="font-size:.72rem"></i>
                            {{ $osFam }}
                        </div>
                        @else
                        <span style="color:#cbd5e1;font-size:.75rem">—</span>
                        @endif

                        {{-- OS version (name already includes version, e.g. "Ubuntu 22.04") --}}
                        @if($f->os_name)
                        <div style="font-size:.68rem;color:#475569;margin-top:.18rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px"
                             title="{{ $f->os_name }}">
                            {{ $f->os_name }}
                        </div>
                        @endif

                    </td>

                    {{-- ── Category column ── --}}
                    @php
                        $cat = $f->vuln_category ?? 'Other';
                        [$catBg, $catColor, $catIcon] = \App\Models\VulnFinding::categoryStyle($cat);
                    @endphp
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;white-space:nowrap">
                        <div style="display:inline-flex;align-items:center;gap:.28rem;
                                    background:{{ $catBg }};color:{{ $catColor }};
                                    font-size:.68rem;font-weight:700;
                                    padding:.2rem .55rem;border-radius:20px;
                                    border:1px solid {{ $catColor }}22">
                            <i class="bi {{ $catIcon }}" style="font-size:.65rem"></i>
                            {{ $cat }}
                        </div>
                        @if($f->affected_component)
                        <div style="font-size:.67rem;color:#94a3b8;margin-top:.22rem;
                                    max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                             title="{{ $f->affected_component }}">
                            {{ $f->affected_component }}
                        </div>
                        @endif
                    </td>

                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9">
                        <span class="badge-sev {{ $remClass }}" style="font-size:.68rem">{{ $remStatus }}</span>
                        @if($rem?->assigned_to)
                        <div style="font-size:.7rem;color:#94a3b8;margin-top:.2rem"><i class="bi bi-person"></i> {{ $rem->assigned_to }}</div>
                        @endif
                        @if($rem?->due_date)
                        <div style="font-size:.7rem;color:{{ $rem->due_date->isPast() && $remStatus !== 'Resolved' ? '#dc2626' : '#94a3b8' }};margin-top:.1rem">
                            <i class="bi bi-calendar3"></i> {{ $rem->due_date->format('d M Y') }}
                        </div>
                        @endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9">
                        @if($rem?->assignedGroup)
                            @php
                                $memberHtml = '<div style="min-width:140px">';
                                foreach ($rem->assignedGroup->members as $m) {
                                    $memberHtml .= '<div style="font-size:.8rem;color:#0f172a;padding:.18rem 0">' . e($m->name) . '</div>';
                                }
                                if ($rem->assignedGroup->members->isEmpty()) {
                                    $memberHtml .= '<div style="font-size:.78rem;color:#94a3b8">No members yet</div>';
                                }
                                $memberHtml .= '</div>';
                            @endphp
                            <span class="group-badge-hover" tabindex="0"
                                data-bs-toggle="popover"
                                data-bs-trigger="hover focus"
                                data-bs-placement="left"
                                data-bs-html="true"
                                data-bs-title="{!! htmlspecialchars('<i class=\"bi bi-people-fill me-1\" style=\"color:var(--primary)\"></i>' . $rem->assignedGroup->name, ENT_QUOTES) !!}"
                                data-bs-content="{!! htmlspecialchars($memberHtml, ENT_QUOTES) !!}"
                                style="display:inline-flex;align-items:center;gap:.3rem;background:rgb(232,244,195);color:var(--primary-dark);font-size:.7rem;font-weight:700;padding:.22rem .65rem;border-radius:20px;cursor:pointer;border:1px solid var(--primary-light)">
                                <i class="bi bi-people-fill" style="font-size:.65rem"></i>
                                {{ $rem->assignedGroup->name }}
                                @if($rem->assignedGroup->members->isNotEmpty())
                                <span style="background:var(--primary);color:#fff;border-radius:50%;width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800">
                                    {{ $rem->assignedGroup->members->count() }}
                                </span>
                                @endif
                            </span>
                        @else
                            <span style="color:#cbd5e1;font-size:.75rem">—</span>
                        @endif
                    </td>
                    <td style="padding:.6rem .85rem;vertical-align:middle;border-color:#f1f5f9;text-align:center">
                        <div class="d-flex justify-content-center gap-1">
                            <button class="btn btn-sm" style="border-radius:8px;border:1px solid #e2e8f0;color:#64748b;padding:.22rem .55rem;font-size:.74rem"
                                title="Assign Group"
                                data-bs-toggle="modal" data-bs-target="#remModal{{ $f->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm" style="border-radius:8px;border:1.5px solid var(--primary);color:var(--primary-dark);padding:.22rem .55rem;font-size:.74rem;background:rgb(232,244,195)"
                                title="View Detail"
                                data-bs-toggle="modal" data-bs-target="#detailModal{{ $f->id }}">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                {{-- ═══ Assign Group Modal ═══ --}}
                <div class="modal fade" id="remModal{{ $f->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
                            <div class="modal-header" style="border-bottom:2px solid var(--primary);padding:1rem 1.5rem">
                                <h5 class="modal-title" style="font-size:.9rem;font-weight:700;color:#0f172a">
                                    <i class="bi bi-people-fill me-2" style="color:var(--primary)"></i>Assign to Group
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            {{-- Route: existing record → updateRemediation; no record yet → bulkUpdateRemediation (uses firstOrCreate) --}}
                            @if($rem)
                            <form method="POST" action="{{ route('vuln-assessments.remediation.update', [$assessment, $rem]) }}">
                                @csrf @method('PATCH')
                            @else
                            <form method="POST" action="{{ route('vuln-assessments.remediation.bulk-update', $assessment) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="finding_ids" value="{{ $f->id }}">
                            @endif

                                <div class="modal-body" style="padding:1.5rem">

                                    {{-- Finding summary --}}
                                    <div style="background:#f8fafc;border-radius:8px;padding:.6rem .85rem;margin-bottom:1.25rem;font-size:.8rem;color:#374151">
                                        <span class="badge-sev {{ $sevClass }}" style="font-size:.68rem;margin-right:.4rem">{{ $f->severity }}</span>
                                        <strong>{{ $f->vuln_name }}</strong><br>
                                        <span style="font-family:monospace;font-size:.75rem;color:#64748b">
                                            {{ $f->ip_address }} &middot; Plugin {{ $f->plugin_id }}
                                        </span>
                                    </div>

                                    {{-- Current status — read-only, managed by tracking engine --}}
                                    <div class="mb-4 p-3" style="background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0">
                                        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:.45rem">
                                            <i class="bi bi-lock-fill me-1"></i>Remediation Status
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge-sev {{ $remClass }}">{{ $remStatus }}</span>
                                            <span style="font-size:.75rem;color:#94a3b8">Managed automatically by the tracking engine</span>
                                        </div>
                                    </div>

                                    {{-- Assign to Group — only editable field --}}
                                    <div class="mb-1">
                                        <label style="font-size:.82rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">
                                            <i class="bi bi-people-fill me-1" style="color:var(--primary)"></i>Assign to User Group
                                        </label>
                                        <select name="assigned_group_id" class="form-select form-select-sm" style="border-radius:8px">
                                            <option value="">— Unassigned —</option>
                                            @foreach($userGroups as $g)
                                            <option value="{{ $g->id }}" @selected($rem?->assigned_group_id == $g->id)>
                                                {{ $g->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <div style="font-size:.72rem;color:#94a3b8;margin-top:.3rem">
                                            Select a group to own remediation of this finding. Choose "Unassigned" to clear.
                                        </div>
                                    </div>

                                </div>
                                <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.75rem 1.5rem">
                                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                                        style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff;font-weight:500">
                                        Cancel
                                    </button>
                                    <button type="submit" class="btn btn-sm"
                                        style="background:var(--primary);color:#fff;border-radius:8px;font-weight:600;border:none;padding:.4rem 1.2rem">
                                        <i class="bi bi-check-lg me-1"></i>Assign Group
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ═══ Vulnerability Detail Modal ═══ --}}
                <div class="modal fade" id="detailModal{{ $f->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">

                            {{-- Header --}}
                            <div class="modal-header" style="border-bottom:2px solid var(--primary);padding:1rem 1.5rem;gap:.75rem;flex-wrap:wrap">
                                <div style="flex:1;min-width:0">
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                        <span class="badge-sev {{ $sevClass }}" style="font-size:.75rem">{{ $f->severity }}</span>
                                        <span class="badge-sev" style="font-size:.68rem;background:{{ $tsBg }};color:{{ $tsColor }}">{{ $tsIcon }} {{ $f->tracking_status }}</span>
                                        <span class="badge-sev {{ $remClass }}" style="font-size:.68rem">{{ $remStatus }}</span>
                                    </div>
                                    <h5 class="modal-title mb-0" style="font-size:.95rem;font-weight:700;color:#0f172a;line-height:1.3">
                                        {{ $f->vuln_name }}
                                    </h5>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body" style="padding:1.5rem">

                                {{-- Severity Banner --}}
                                @php
                                    $bannerClass = 'sev-banner sev-banner-' . strtolower($f->severity);
                                    $bannerIconColors = ['Critical'=>'#991b1b','High'=>'#9a3412','Medium'=>'#854d0e','Low'=>'#475569'];
                                    $bannerBg = ['Critical'=>'#fee2e2','High'=>'#ffedd5','Medium'=>'#fef9c3','Low'=>'#f1f5f9'];
                                    $bannerIcon = ['Critical'=>'bi-exclamation-octagon-fill','High'=>'bi-exclamation-triangle-fill','Medium'=>'bi-exclamation-circle-fill','Low'=>'bi-info-circle-fill'];
                                    $bColor = $bannerIconColors[$f->severity] ?? '#475569';
                                    $bBg    = $bannerBg[$f->severity]        ?? '#f1f5f9';
                                    $bIcon  = $bannerIcon[$f->severity]       ?? 'bi-info-circle-fill';
                                @endphp
                                <div class="{{ $bannerClass }}">
                                    <div class="sev-banner-icon" style="background:{{ $bColor }}22">
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

                                {{-- Host Information --}}
                                <div class="detail-section">
                                    <div class="detail-section-title">
                                        <i class="bi bi-hdd-network-fill" style="color:var(--primary)"></i>
                                        Host Information
                                    </div>
                                    <div class="detail-meta-grid">
                                        <div class="detail-meta-item">
                                            <div class="label">IP Address</div>
                                            <div class="value mono" style="color:#0f172a;font-weight:700">{{ $f->ip_address }}</div>
                                        </div>
                                        <div class="detail-meta-item">
                                            <div class="label">Hostname</div>
                                            <div class="value">{{ $f->hostname ?: '—' }}</div>
                                        </div>
                                        <div class="detail-meta-item">
                                            <div class="label">Port / Protocol</div>
                                            <div class="value mono">{{ $f->port ? $f->port . ($f->protocol ? '/'.$f->protocol : '') : '—' }}</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Vulnerability Details --}}
                                <div class="detail-section">
                                    <div class="detail-section-title">
                                        <i class="bi bi-bug-fill" style="color:#dc2626"></i>
                                        Vulnerability Details
                                    </div>
                                    <div class="detail-meta-grid mb-3">
                                        <div class="detail-meta-item">
                                            <div class="label">Plugin ID</div>
                                            <div class="value mono" style="font-weight:700">{{ $f->plugin_id }}</div>
                                        </div>
                                        <div class="detail-meta-item">
                                            <div class="label">CVE</div>
                                            <div class="value mono">
                                                @if($f->cve)
                                                    {{ $f->cve }}
                                                @else
                                                    <span style="color:#94a3b8">—</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="detail-meta-item">
                                            <div class="label">Severity</div>
                                            <div class="value"><span class="badge-sev {{ $sevClass }}">{{ $f->severity }}</span></div>
                                        </div>
                                        <div class="detail-meta-item">
                                            <div class="label">First Seen</div>
                                            <div class="value">{{ $f->first_seen_at->format('d M Y') }}</div>
                                        </div>
                                        <div class="detail-meta-item">
                                            <div class="label">Last Seen</div>
                                            <div class="value">{{ $f->last_seen_at->format('d M Y') }}</div>
                                        </div>
                                    </div>

                                    @if($f->description)
                                    <div class="label mb-1">Description</div>
                                    <div class="desc-box">{{ $f->description }}</div>
                                    @endif
                                </div>

                                {{-- Auto Classification --}}
                                <div class="detail-section">
                                    <div class="detail-section-title">
                                        <i class="bi bi-tags-fill" style="color:var(--primary)"></i>
                                        Auto Classification
                                    </div>
                                    @php
                                        $fCat  = $f->vuln_category ?? 'Other';
                                        [$fbg,$fcol,$ficon] = \App\Models\VulnFinding::categoryStyle($fCat);
                                    @endphp
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

                                {{-- Remediation --}}
                                @if($f->remediation_text)
                                <div class="detail-section">
                                    <div class="detail-section-title">
                                        <i class="bi bi-wrench-adjustable-circle-fill" style="color:var(--primary)"></i>
                                        Recommended Fix
                                    </div>
                                    <div class="rem-box">{{ $f->remediation_text }}</div>
                                </div>
                                @endif

                                {{-- Remediation Status --}}
                                <div class="detail-section">
                                    <div class="detail-section-title">
                                        <i class="bi bi-clipboard2-check-fill" style="color:#2563eb"></i>
                                        Remediation Tracking
                                    </div>
                                    <div class="detail-meta-grid">
                                        <div class="detail-meta-item">
                                            <div class="label">Status</div>
                                            <div class="value"><span class="badge-sev {{ $remClass }}">{{ $remStatus }}</span></div>
                                        </div>
                                        <div class="detail-meta-item">
                                            <div class="label">Assigned To</div>
                                            <div class="value">{{ $rem?->assigned_to ?: '—' }}</div>
                                        </div>
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

                                {{-- Plugin Output --}}
                                @if($f->plugin_output)
                                <div class="detail-section" style="margin-bottom:0">
                                    <div class="detail-section-title">
                                        <i class="bi bi-terminal-fill" style="color:#475569"></i>
                                        Plugin Output
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
                    <td colspan="12" style="text-align:center;padding:3rem;color:#94a3b8">
                        <i class="bi bi-bug" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
                        No findings match the current filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($findings->hasPages())
    <div style="padding:.75rem 1.5rem;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between">
        <div style="font-size:.82rem;color:#64748b">
            Showing {{ $findings->firstItem() }}–{{ $findings->lastItem() }} of {{ $findings->total() }} findings
        </div>
        {{ $findings->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

{{-- ── Bulk Assign Bar ──────────────────────────────────────────── --}}
<form method="POST" id="bulkForm"
      action="{{ route('vuln-assessments.remediation.bulk-update', $assessment) }}">
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
                <option value="__clear__">✕ Clear Group</option>
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

@endsection

@push('scripts')
<script>
// ── Group popovers ──────────────────────────────────────────────
document.querySelectorAll('.group-badge-hover').forEach(function (el) {
    new bootstrap.Popover(el, {
        trigger: 'hover focus',
        html: true,
        placement: 'left',
        customClass: 'group-popover'
    });
});

// ── Multi-select & bulk assign ──────────────────────────────────
(function () {
    var chkAll   = document.getElementById('chk-all');
    var bulkBar  = document.getElementById('bulkBar');
    var bulkIds  = document.getElementById('bulkIds');
    var bulkCount = document.getElementById('bulkCount');

    if (!bulkBar || !bulkIds || !bulkCount) return;

    function getChecked() {
        return Array.prototype.slice.call(document.querySelectorAll('.row-chk:checked'));
    }

    function updateBar() {
        var checked = getChecked();
        bulkCount.textContent = checked.length;
        if (checked.length > 0) {
            bulkBar.classList.add('visible');
            bulkIds.value = checked.map(function(c){ return c.value; }).join(',');
        } else {
            bulkBar.classList.remove('visible');
            bulkIds.value = '';
        }
    }

    function setRowHighlight(chk) {
        var row = chk.closest('tr');
        if (row) row.classList.toggle('row-selected', chk.checked);
    }

    if (chkAll) {
        chkAll.addEventListener('change', function () {
            document.querySelectorAll('.row-chk').forEach(function(c) {
                c.checked = chkAll.checked;
                setRowHighlight(c);
            });
            updateBar();
        });
    }

    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('row-chk')) return;
        setRowHighlight(e.target);
        var all  = document.querySelectorAll('.row-chk');
        var chkd = getChecked();
        if (chkAll) {
            chkAll.indeterminate = chkd.length > 0 && chkd.length < all.length;
            chkAll.checked = chkd.length > 0 && chkd.length === all.length;
        }
        updateBar();
    });

    var cancelBtn = document.getElementById('bulkCancel');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            document.querySelectorAll('.row-chk').forEach(function(c) {
                c.checked = false; setRowHighlight(c);
            });
            if (chkAll) { chkAll.checked = false; chkAll.indeterminate = false; }
            updateBar();
        });
    }
})();

function copyOutput(id) {
    const el = document.getElementById('output-' + id);
    if (!el) return;
    navigator.clipboard.writeText(el.innerText).then(() => {
        const btns = document.querySelectorAll('[onclick="copyOutput(' + id + ')"]');
        btns.forEach(b => { b.innerHTML = '<i class="bi bi-check me-1"></i>Copied'; });
        setTimeout(() => btns.forEach(b => { b.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copy'; }), 2000);
    });
}
</script>
@endpush
