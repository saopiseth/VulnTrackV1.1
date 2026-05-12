@extends('layouts.app')
@section('title', $assessment->name)

@section('content')
<style>
    :root { --lime: var(--primary); --lime-dark: var(--primary-dark); --lime-muted: rgb(232,244,195); }

    /* ── Badge helpers ── */
    .badge-env { padding:.22rem .7rem; border-radius:20px; font-size:.7rem; font-weight:700; display:inline-block; }
    .env-production  { background:#fee2e2; color:#991b1b; }
    .env-uat         { background:#fef9c3; color:#854d0e; }
    .env-internal    { background:#e0f2fe; color:#0c4a6e; }
    .env-development { background:#f1f5f9; color:#475569; }

    /* ── Stat strip ── */
    .stat-strip { background:#fff; border:1px solid #e8f5c2; border-radius:10px; padding:.7rem 1.1rem; text-align:center; }
    .stat-strip .lbl { font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; margin-bottom:.1rem; }
    .stat-strip .val { font-size:1.55rem; font-weight:800; line-height:1.15; }

    /* ── Panel cards ── */
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:12px; padding:1.25rem 1.35rem; margin-bottom:1rem; }
    .section-label {
        font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.7px;
        color:var(--lime-dark); margin-bottom:.85rem;
        padding-bottom:.45rem; border-bottom:2px solid var(--lime);
        display:flex; align-items:center; gap:.4rem;
    }

    /* ── Pill tabs ── */
    .pill-nav { display:flex; gap:.3rem; flex-wrap:wrap; background:#f1f5f9; padding:.35rem; border-radius:10px; margin-bottom:1.25rem; border:1px solid #e2e8f0; width:fit-content; }
    .pill-nav .p-tab {
        padding:.38rem .85rem; border-radius:7px; font-size:.81rem; font-weight:600;
        color:#64748b; text-decoration:none; border:none; background:transparent; cursor:pointer;
        white-space:nowrap; transition: background .15s, color .15s;
    }
    .pill-nav .p-tab.active, .pill-nav .p-tab:hover { background:#fff; color:var(--lime-dark); box-shadow:0 1px 4px rgba(0,0,0,.08); }
    .pill-nav .p-tab .cnt { font-size:.65rem; background:var(--lime-muted); color:var(--lime-dark); border-radius:10px; padding:.05rem .38rem; margin-left:.25rem; font-weight:700; }

    /* ── Scan item ── */
    .scan-row { border:1px solid #e8f5c2; border-radius:10px; padding:.8rem 1rem; margin-bottom:.55rem; display:flex; align-items:center; gap:.75rem; }
    .scan-row:hover { border-color:var(--lime); }

    .scan-badge { font-size:.62rem; font-weight:700; padding:.12rem .45rem; border-radius:20px; text-transform:uppercase; letter-spacing:.4px; }
    .scan-baseline { background:var(--lime-muted); color:var(--lime-dark); }
    .scan-latest   { background:#dbeafe; color:#1e40af; }

    /* ── Progress ── */
    .prog-bar { height:7px; border-radius:20px; background:#e8f5c2; overflow:hidden; }
    .prog-fill { height:100%; border-radius:20px; background:var(--lime); }
    .prog-thin { height:3px; border-radius:10px; background:#e2e8f0; overflow:hidden; margin-top:.25rem; }

</style>

{{-- ── Hero header ── --}}
<div style="background:linear-gradient(135deg,#f8fafc 0%,#f0f7e6 100%);border:1px solid #e8f5c2;border-radius:14px;
            padding:1.4rem 1.75rem;margin-bottom:1.25rem">

    {{-- Breadcrumb --}}
    <nav style="margin-bottom:.55rem">
        <ol class="breadcrumb mb-0" style="font-size:.73rem">
            <li class="breadcrumb-item">
                <a href="{{ route('vuln-assessments.index') }}" style="color:#94a3b8;text-decoration:none">VA Assessments</a>
            </li>
            <li class="breadcrumb-item active" style="color:#374151">{{ Str::limit($assessment->name, 50) }}</li>
        </ol>
    </nav>

    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div style="min-width:0">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h4 style="color:#0f172a;margin:0;font-size:1.25rem;font-weight:700">{{ $assessment->name }}</h4>
                @if($assessment->environment)
                <span class="badge-env env-{{ strtolower($assessment->environment) }}">{{ $assessment->environment }}</span>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-3" style="font-size:.78rem;color:#64748b">
                @if($assessment->period_start || $assessment->period_end)
                <span><i class="bi bi-calendar3 me-1"></i>{{ $assessment->period_start?->format('d M Y') ?? '—' }} – {{ $assessment->period_end?->format('d M Y') ?? '—' }}</span>
                @endif
                <span><i class="bi bi-person me-1"></i>{{ $assessment->creator?->name ?? '—' }}</span>
                <span><i class="bi bi-cloud-upload me-1"></i>{{ $assessment->scans->count() }} scan{{ $assessment->scans->count() !== 1 ? 's' : '' }}</span>
            </div>
            @if($assessment->description)
            <div style="margin-top:.55rem;font-size:.8rem;color:#475569;line-height:1.55;max-width:560px">
                {{ Str::limit($assessment->description, 150) }}
            </div>
            @endif
        </div>

        {{-- Action buttons --}}
        <div class="d-flex gap-2 flex-wrap flex-shrink-0 align-items-center">
            @if($activeScan)
            <a href="{{ route('vuln-assessments.findings', $assessment) }}" class="btn btn-sm"
                style="background:var(--lime);color:#fff;border-radius:8px;font-weight:600;border:none;
                       padding:.4rem .95rem;font-size:.81rem">
                <i class="bi bi-table me-1"></i>Findings
            </a>
            <a href="{{ route('vuln-assessments.progress', $assessment) }}" class="btn btn-sm"
                style="border:1.5px solid var(--lime);border-radius:8px;color:var(--lime-dark);background:#fff;
                       font-weight:600;font-size:.81rem;padding:.4rem .95rem">
                <i class="bi bi-bar-chart-line me-1"></i>Progress
            </a>
            <a href="{{ route('vuln-assessments.kri-report', $assessment) }}" class="btn btn-sm"
                style="border:1.5px solid #1d4ed8;border-radius:8px;color:#1d4ed8;background:#fff;
                       font-weight:600;font-size:.81rem;padding:.4rem .95rem">
                <i class="bi bi-speedometer2 me-1"></i>KRI Report
            </a>
            @endif
            {{-- Report dropdown --}}
            <div class="dropdown">
                <button class="btn btn-sm dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        style="background:#e2e8f0;color:#374151;border-radius:8px;font-weight:500;border:none;
                               padding:.4rem .95rem;font-size:.81rem">
                    <i class="bi bi-file-earmark-text me-1"></i>Report
                </button>
                <ul class="dropdown-menu dropdown-menu-end"
                    style="border-radius:12px;border:1px solid #e2e8f0;min-width:180px;padding:.4rem;box-shadow:0 8px 24px rgba(0,0,0,.12)">
                    <li class="px-2 pb-1" style="font-size:.7rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding-top:.4rem">
                        Download Report
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('vuln-assessments.report.pdf', $assessment) }}"
                           style="border-radius:8px;font-size:.83rem;padding:.45rem .75rem">
                            <span style="width:28px;height:28px;border-radius:7px;background:#fef2f2;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="bi bi-file-earmark-pdf-fill" style="color:#dc2626"></i>
                            </span>
                            <span>
                                <span style="display:block;font-weight:600;color:#0f172a">PDF Report</span>
                                <span style="font-size:.72rem;color:#94a3b8">Formatted, print-ready</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('vuln-assessments.report.word', $assessment) }}"
                           style="border-radius:8px;font-size:.83rem;padding:.45rem .75rem">
                            <span style="width:28px;height:28px;border-radius:7px;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="bi bi-file-earmark-word-fill" style="color:#1d4ed8"></i>
                            </span>
                            <span>
                                <span style="display:block;font-weight:600;color:#0f172a">Word Report</span>
                                <span style="font-size:.72rem;color:#94a3b8">Editable .doc format</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('vuln-assessments.report.excel', $assessment) }}"
                           style="border-radius:8px;font-size:.83rem;padding:.45rem .75rem">
                            <span style="width:28px;height:28px;border-radius:7px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="bi bi-file-earmark-spreadsheet-fill" style="color:#16a34a"></i>
                            </span>
                            <span>
                                <span style="display:block;font-weight:600;color:#0f172a">Excel / CSV</span>
                                <span style="font-size:.72rem;color:#94a3b8">Raw findings data</span>
                            </span>
                        </a>
                    </li>
                </ul>
            </div>

            <button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal"
                style="background:#e2e8f0;color:#374151;border-radius:8px;font-weight:500;border:none;
                       padding:.4rem .95rem;font-size:.81rem">
                <i class="bi bi-upload me-1"></i>Upload
            </button>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" style="border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Severity stat strip ── --}}
@if($activeScan && $stats)
<div class="row g-2 mb-3">
    <div class="col-4 col-md-2">
        <div class="stat-strip">
            <div class="lbl" style="color:#94a3b8">Total</div>
            <div class="val" style="color:#0f172a">{{ $stats->total }}</div>
        </div>
    </div>
    <div class="col-4 col-md-2">
        <div class="stat-strip" style="border-color:#fca5a5;background:#fff8f8">
            <div class="lbl" style="color:#991b1b">Critical</div>
            <div class="val" style="color:#dc2626">{{ $stats->critical }}</div>
        </div>
    </div>
    <div class="col-4 col-md-2">
        <div class="stat-strip" style="border-color:#fdba74;background:#fffbf7">
            <div class="lbl" style="color:#9a3412">High</div>
            <div class="val" style="color:#ea580c">{{ $stats->high }}</div>
        </div>
    </div>
    <div class="col-4 col-md-2">
        <div class="stat-strip" style="border-color:#fde047;background:#fdfcf0">
            <div class="lbl" style="color:#854d0e">Medium</div>
            <div class="val" style="color:#d97706">{{ $stats->medium }}</div>
        </div>
    </div>
    <div class="col-4 col-md-2">
        <div class="stat-strip" style="border-color:#cbd5e1;background:#f8fafc">
            <div class="lbl" style="color:#475569">Low</div>
            <div class="val" style="color:#64748b">{{ $stats->low }}</div>
        </div>
    </div>
    <div class="col-4 col-md-2">
        <div class="stat-strip" style="border-color:#bfdbfe;background:#f0f7ff">
            <div class="lbl" style="color:#1e40af">Hosts</div>
            <div class="val" style="color:#1d4ed8">{{ $activeHostCount }}</div>
        </div>
    </div>
</div>
@elseif(!$activeScan)
<div class="va-card mb-3" style="text-align:center;padding:2.5rem;border-style:dashed">
    <i class="bi bi-cloud-upload" style="font-size:2.2rem;color:var(--lime);opacity:.5;display:block;margin-bottom:.75rem"></i>
    <div style="font-weight:600;color:#64748b;margin-bottom:.5rem">No scan data yet</div>
    <button class="btn btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#uploadModal"
        style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none">
        <i class="bi bi-upload me-1"></i> Upload First Scan
    </button>
</div>
@endif

{{-- ── Pill tabs ── --}}
<div class="pill-nav" role="tablist">
    @if($topIps->count() || $assessment->scope_group_id)
    <button class="p-tab active" data-bs-toggle="tab" data-bs-target="#tab-os" role="tab">
        <i class="bi bi-hdd-network me-1"></i>Vulnerable Hosts
        @if($topIps->count())<span class="cnt">{{ $topIps->count() }}</span>@endif
    </button>
    @endif
    <button class="p-tab{{ !$topIps->count() && !$assessment->scope_group_id ? ' active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-scans" role="tab">
        <i class="bi bi-cloud-upload me-1"></i>Scans
        <span class="cnt">{{ $assessment->scans->count() }}</span>
    </button>
</div>

<div class="tab-content">

{{-- ══════════════════════════════════════════════════════════════
     TAB: Vulnerable Hosts
══════════════════════════════════════════════════════════════ --}}
@if($topIps->count() || $assessment->scope_group_id)
<div class="tab-pane fade show active" id="tab-os" role="tabpanel">

    {{-- ── Scope Group picker ──────────────────────────────────────── --}}
    <div class="va-card mb-3" style="padding:1rem 1.25rem">
        <form method="POST" action="{{ route('vuln-assessments.scope-group.update', $assessment) }}"
              style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
            @csrf @method('PATCH')
            <label style="font-size:.78rem;font-weight:600;color:#374151;white-space:nowrap;margin:0">
                <i class="bi bi-diagram-3-fill me-1" style="color:var(--lime-dark)"></i>Scope Group
            </label>
            <select name="scope_group_id" class="form-select form-select-sm"
                    style="max-width:260px;border-radius:8px;font-size:.82rem;border-color:#e2e8f0">
                <option value="">— None —</option>
                @foreach($scopeGroups as $sg)
                <option value="{{ $sg->id }}" {{ $assessment->scope_group_id == $sg->id ? 'selected' : '' }}>
                    {{ $sg->name }} ({{ $sg->items_count }} assets)
                </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm"
                    style="background:var(--lime);color:#fff;border:none;border-radius:8px;font-weight:600;padding:.3rem .9rem;font-size:.8rem">
                Apply
            </button>
            @if($assessment->scope_group_id)
            <span style="font-size:.75rem;color:#64748b">
                <i class="bi bi-check-circle-fill me-1" style="color:#22c55e"></i>
                Linked — scope metadata auto-populated by IP match
            </span>
            @endif
        </form>
        @if(session('success') && str_contains(session('success'), 'Scope'))
        <div style="margin-top:.5rem;font-size:.78rem;color:#059669">
            <i class="bi bi-check2 me-1"></i>{{ session('success') }}
        </div>
        @endif
    </div>

    {{-- ── Vulnerable Hosts IP table ───────────────────────────────── --}}
    @if($topIps->count())
    <div class="va-card mb-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="section-label mb-0"><i class="bi bi-hdd-network"></i>Vulnerable Hosts</div>
            <span style="font-size:.71rem;color:#94a3b8;font-weight:600">
                {{ $topIps->count() }} host{{ $topIps->count() !== 1 ? 's' : '' }} &middot; all scans
            </span>
        </div>
    @else
    <div class="va-card mb-3" style="text-align:center;padding:2.5rem;color:#94a3b8">
        <i class="bi bi-cloud-upload" style="font-size:1.8rem;display:block;margin-bottom:.6rem;opacity:.4"></i>
        No scan data yet — upload a scan to populate vulnerability findings.
    </div>
    @endif

    @if($topIps->count())
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                <thead>
                    <tr style="border-bottom:2px solid #e8f5c2">
                        <th style="padding:.45rem .55rem;text-align:left;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8">#</th>
                        <th style="padding:.45rem .55rem;text-align:left;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;white-space:nowrap">IP Address</th>
                        <th style="padding:.45rem .55rem;text-align:left;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8">Hostname</th>
                        <th style="padding:.45rem .55rem;text-align:left;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8">OS</th>
                        <th style="padding:.45rem .55rem;text-align:center;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#991b1b">Crit</th>
                        <th style="padding:.45rem .55rem;text-align:center;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#c2410c">High</th>
                        <th style="padding:.45rem .55rem;text-align:center;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#b45309">Med</th>
                        <th style="padding:.45rem .55rem;text-align:center;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#475569">Low</th>
                        <th style="padding:.45rem .55rem;text-align:center;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#059669;white-space:nowrap">Active</th>
                        <th style="padding:.45rem .55rem;text-align:center;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b">Resolved</th>
                        <th style="padding:.45rem .55rem;text-align:left;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;white-space:nowrap">First Detected</th>
                        <th style="padding:.45rem .55rem;text-align:left;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8">System</th>
                        <th style="padding:.45rem .55rem;text-align:left;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;white-space:nowrap">Criticality</th>
                        <th style="padding:.45rem .55rem;text-align:left;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8">Owner</th>
                        <th style="padding:.45rem .55rem;text-align:left;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8">Scope</th>
                        <th style="padding:.45rem .55rem;text-align:center;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($topIps as $i => $ip)
                @php
                    $osLower = strtolower($ip->os_name ?? '');
                    $osIcon  = str_contains($osLower,'windows') ? 'bi-windows'
                             : (preg_match('/linux|ubuntu|centos|debian|redhat/',$osLower) ? 'bi-ubuntu'
                             : (str_contains($osLower,'cisco') ? 'bi-router' : ($osLower ? 'bi-cpu' : '')));
                    $osBg    = str_contains($osLower,'windows') ? ['#dbeafe','#1e40af']
                             : (preg_match('/linux|ubuntu|centos|debian|redhat/',$osLower) ? ['#d1fae5','#065f46']
                             : ['#f1f5f9','#475569']);
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;{{ $loop->even ? 'background:#fafafa' : '' }}"
                    onmouseover="this.style.background='#f8fce8'" onmouseout="this.style.background='{{ $loop->even ? '#fafafa' : '' }}'"
                    data-ip="{{ $ip->ip_address }}"
                    data-hostname="{{ $ip->scope_hostname ?? $ip->hostname }}"
                    data-system="{{ $ip->system_name }}"
                    data-criticality="{{ $ip->system_criticality }}"
                    data-owner="{{ $ip->system_owner }}"
                    data-scope="{{ $ip->identified_scope }}"
                    data-env="{{ $ip->environment }}"
                    data-sla="{{ $ip->remediation_sla }}"
                    data-update-url="{{ route('vuln-assessments.hosts.update', [$assessment, $ip->ip_address]) }}"
                    data-destroy-url="{{ route('vuln-assessments.hosts.destroy', [$assessment, $ip->ip_address]) }}">

                    <td style="padding:.5rem .55rem;color:#cbd5e1;font-size:.71rem;font-weight:600">{{ $i + 1 }}</td>

                    <td style="padding:.5rem .55rem;white-space:nowrap">
                        <a href="{{ route('vuln-assessments.findings', ['vulnAssessment'=>$assessment->uuid,'ip'=>$ip->ip_address]) }}"
                           style="font-family:monospace;font-weight:700;color:#0f172a;font-size:.82rem;text-decoration:none"
                           onmouseover="this.style.color='var(--lime-dark)'" onmouseout="this.style.color='#0f172a'">
                            {{ $ip->ip_address }}
                        </a>
                    </td>

                    <td style="padding:.5rem .55rem;color:#475569;font-size:.78rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        {{ $ip->hostname ?: '—' }}
                    </td>

                    <td style="padding:.5rem .55rem;white-space:nowrap">
                        @if($ip->os_name && $osIcon)
                        <span style="display:inline-flex;align-items:center;gap:.28rem;background:{{ $osBg[0] }};
                                     color:{{ $osBg[1] }};border-radius:6px;padding:.13rem .48rem;font-size:.67rem;font-weight:600">
                            <i class="bi {{ $osIcon }}" style="font-size:.63rem"></i>{{ Str::limit($ip->os_name, 20) }}
                        </span>
                        @else<span style="color:#cbd5e1;font-size:.75rem">—</span>@endif
                    </td>

                    @foreach([
                        [$ip->critical,'#fee2e2','#991b1b'],
                        [$ip->high,    '#ffedd5','#c2410c'],
                        [$ip->medium,  '#fef9c3','#b45309'],
                        [$ip->low,     '#f1f5f9','#475569'],
                    ] as [$cnt,$bg,$col])
                    <td style="padding:.5rem .3rem;text-align:center">
                        @if($cnt > 0)
                        <span style="display:inline-block;min-width:24px;background:{{ $bg }};color:{{ $col }};
                                     border-radius:6px;padding:.08rem .35rem;font-size:.73rem;font-weight:800">{{ $cnt }}</span>
                        @else<span style="color:#e2e8f0;font-size:.71rem">—</span>@endif
                    </td>
                    @endforeach

                    <td style="padding:.5rem .3rem;text-align:center">
                        @if($ip->active_count > 0)
                        <span style="font-weight:800;color:#059669;font-size:.8rem">{{ $ip->active_count }}</span>
                        @else<span style="color:#cbd5e1;font-size:.75rem">—</span>@endif
                    </td>

                    <td style="padding:.5rem .3rem;text-align:center">
                        @if($ip->resolved_count > 0)
                        <span style="font-weight:700;color:#64748b;font-size:.8rem">{{ $ip->resolved_count }}</span>
                        @else<span style="color:#e2e8f0;font-size:.71rem">—</span>@endif
                    </td>

                    <td style="padding:.5rem .55rem;white-space:nowrap;color:#64748b;font-size:.74rem">
                        @if($ip->first_detected)
                        <i class="bi bi-calendar3 me-1" style="color:#94a3b8;font-size:.67rem"></i>
                        {{ \Carbon\Carbon::parse($ip->first_detected)->format('d M Y') }}
                        @else—@endif
                    </td>

                    <td style="padding:.5rem .55rem;color:#475569;font-size:.78rem;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        {{ $ip->system_name ?: '—' }}
                    </td>

                    <td style="padding:.5rem .55rem;white-space:nowrap">
                        @php
                            $cm = \App\Models\AssessmentScope::criticalityLevels()[$ip->system_criticality] ?? null;
                        @endphp
                        @if($cm)
                        <span style="display:inline-block;background:{{ $cm['bg'] }};color:{{ $cm['color'] }};
                                     border-radius:6px;padding:.1rem .45rem;font-size:.67rem;font-weight:700;white-space:nowrap">
                            {{ $cm['label'] }}
                        </span>
                        @else<span style="color:#cbd5e1;font-size:.75rem">—</span>@endif
                    </td>

                    <td style="padding:.5rem .55rem;color:#475569;font-size:.78rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        {{ $ip->system_owner ?: '—' }}
                    </td>

                    <td style="padding:.5rem .55rem;white-space:nowrap">
                        @if($ip->identified_scope)
                        <span style="display:inline-block;background:#f0fdf4;color:#166534;
                                     border-radius:6px;padding:.1rem .45rem;font-size:.67rem;font-weight:700">
                            {{ $ip->identified_scope }}
                        </span>
                        @else<span style="color:#cbd5e1;font-size:.75rem">—</span>@endif
                    </td>

                    <td style="padding:.5rem .55rem;text-align:center;white-space:nowrap">
                        <button type="button" class="js-host-edit"
                                style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:7px;color:#374151;padding:.25rem .5rem;font-size:.72rem;cursor:pointer;margin-right:3px"
                                title="Edit host scope">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button type="button" class="js-host-delete"
                                style="background:#fef2f2;border:1px solid #fecaca;border-radius:7px;color:#dc2626;padding:.25rem .5rem;font-size:.72rem;cursor:pointer"
                                title="Remove host from tracking">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                        <form class="js-host-delete-form" method="POST" style="display:none">
                            @csrf @method('DELETE')
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     TAB: Scans
══════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade{{ !$topIps->count() && !$assessment->scope_group_id ? ' show active' : '' }}" id="tab-scans" role="tabpanel">
    @forelse($assessment->scans as $scan)
    <div class="scan-row">
        <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;
                    justify-content:center;font-size:1.05rem;
                    background:{{ $scan->is_baseline ? 'var(--lime-muted)' : '#dbeafe' }};
                    color:{{ $scan->is_baseline ? 'var(--lime-dark)' : '#1e40af' }}">
            <i class="bi bi-file-earmark-bar-graph"></i>
        </div>
        <div style="flex:1;min-width:0">
            <div style="font-weight:600;color:#0f172a;font-size:.88rem;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                {{ $scan->filename }}
                @if($scan->is_baseline)
                <span class="scan-badge scan-baseline">Baseline</span>
                @else
                <span class="scan-badge scan-latest">Latest</span>
                @endif
            </div>
            <div style="font-size:.73rem;color:#94a3b8;margin-top:.2rem;display:flex;gap:.75rem;flex-wrap:wrap">
                <span><i class="bi bi-list-check me-1"></i>{{ $scan->finding_count }} findings</span>
                <span style="color:#1e40af"><i class="bi bi-hdd-network me-1"></i>{{ $scan->host_count }} host{{ $scan->host_count !== 1 ? 's' : '' }}</span>
                <span>{{ $scan->created_at->format('d M Y, H:i') }}</span>
                @if($scan->creator)<span><i class="bi bi-person me-1"></i>{{ $scan->creator->name }}</span>@endif
                @if($scan->notes)<span><i class="bi bi-chat-dots me-1"></i>{{ $scan->notes }}</span>@endif
            </div>
        </div>
    </div>
    @empty
    <div style="text-align:center;padding:3rem;color:#94a3b8">
        <i class="bi bi-cloud-upload" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
        No scans uploaded yet. The first upload will be set as the Baseline.
    </div>
    @endforelse

    <div class="mt-3">
        <button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal"
            style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.2rem">
            <i class="bi bi-upload me-1"></i>Upload {{ $assessment->scans->count() === 0 ? 'Baseline' : 'New' }} Scan
        </button>
    </div>
</div>

</div>{{-- /tab-content --}}

{{-- ══ Upload Modal — multi-file, AJAX + chunked ══ --}}
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
            <div class="modal-header" style="border-bottom:2px solid var(--lime);padding:1rem 1.5rem">
                <h5 class="modal-title" style="font-size:.95rem;font-weight:700;color:#0f172a">
                    <i class="bi bi-upload me-2" style="color:var(--lime)"></i>
                    Upload {{ $assessment->scans->count() === 0 ? 'Baseline' : 'Latest' }} Scan
                </h5>
                <button type="button" class="btn-close" id="uploadModalClose" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" style="padding:1.5rem">
                <div id="upload-error" class="alert alert-danger" style="font-size:.85rem;border-radius:8px;display:none"></div>

                @if($assessment->scans->count() === 0)
                <div class="alert" style="background:#e8f5c2;border:1px solid #c8e87a;border-radius:9px;
                                          font-size:.83rem;color:var(--lime-dark);padding:.65rem 1rem;margin-bottom:1rem">
                    <i class="bi bi-info-circle me-1"></i>
                    The first upload will be set as the <strong>Baseline Scan</strong>.
                </div>
                @endif

                <div class="mb-3">
                    <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151">
                        Scan Files <span style="color:#dc2626">*</span>
                    </label>
                    <input type="file" id="scan-file-input" class="form-control" accept=".xml,.nessus,.csv,.txt"
                           multiple style="border-radius:8px;font-size:.875rem">
                    <div style="font-size:.73rem;color:#94a3b8;margin-top:.4rem">
                        Supported: <strong>.nessus</strong> (XML), <strong>.csv</strong> (Tenable export). Max 50 MB per file.
                        Files &gt;5 MB upload in chunks automatically.
                    </div>
                </div>

                {{-- Per-file progress rows (populated by JS on file selection) --}}
                <div id="file-list" style="display:none;margin-bottom:1rem;max-height:280px;overflow-y:auto"></div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151">Notes</label>
                    <input type="text" id="upload-notes" class="form-control"
                           placeholder="Optional notes (applied to all files)" style="border-radius:8px;font-size:.875rem">
                </div>
            </div>

            <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.75rem 1.5rem">
                <button type="button" id="upload-cancel-btn" class="btn btn-sm" data-bs-dismiss="modal"
                    style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff;font-weight:500">Cancel</button>
                <button type="button" id="upload-submit-btn" class="btn btn-sm"
                    style="background:var(--lime);color:#fff;border-radius:8px;font-weight:600;border:none;padding:.45rem 1.2rem">
                    <i class="bi bi-cloud-upload me-1"></i>Import Scan
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    const CHUNK_SIZE = 5 * 1024 * 1024;
    const UPLOAD_URL = '{{ route('vuln-assessments.upload', $assessment) }}';
    const CHUNK_URL  = '{{ route('vuln-assessments.upload.chunk', $assessment) }}';
    const STATUS_BASE = '{{ url('/vuln-assessments/' . $assessment->uuid . '/scan-status') }}/';
    const REDIRECT   = '{{ route('vuln-assessments.show', $assessment) }}';
    const CSRF       = document.querySelector('meta[name="csrf-token"]').content;

    const fileInput  = document.getElementById('scan-file-input');
    const notesInput = document.getElementById('upload-notes');
    const submitBtn  = document.getElementById('upload-submit-btn');
    const cancelBtn  = document.getElementById('upload-cancel-btn');
    const errorBox   = document.getElementById('upload-error');
    const fileList   = document.getElementById('file-list');

    function fmt(bytes) {
        return bytes < 1048576
            ? (bytes / 1024).toFixed(1) + ' KB'
            : (bytes / 1048576).toFixed(1) + ' MB';
    }

    // ── Render a row for each selected file ──────────────────────
    fileInput.addEventListener('change', function () {
        const files = Array.from(fileInput.files);
        if (!files.length) { fileList.style.display = 'none'; return; }

        fileList.innerHTML = '';
        files.forEach(function (file, i) {
            const row = document.createElement('div');
            row.id = 'frow-' + i;
            row.style.cssText = 'border:1px solid #e2e8f0;border-radius:9px;padding:.55rem .85rem;margin-bottom:.45rem';
            row.innerHTML =
                '<div style="display:flex;align-items:center;gap:.55rem;margin-bottom:.3rem">' +
                    '<i class="bi bi-file-earmark-bar-graph" style="color:#64748b;font-size:.85rem;flex-shrink:0"></i>' +
                    '<span style="font-size:.82rem;font-weight:600;color:#0f172a;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="' + file.name + '">' + file.name + '</span>' +
                    '<span style="font-size:.72rem;color:#94a3b8;flex-shrink:0">' + fmt(file.size) + '</span>' +
                    '<span id="fstatus-' + i + '" style="font-size:.71rem;font-weight:700;padding:.1rem .5rem;border-radius:20px;background:#f1f5f9;color:#64748b;flex-shrink:0">Pending</span>' +
                '</div>' +
                '<div style="height:5px;border-radius:20px;background:#e2e8f0;overflow:hidden">' +
                    '<div id="fbar-' + i + '" style="height:100%;width:0%;border-radius:20px;background:var(--lime);transition:width .2s ease"></div>' +
                '</div>';
            fileList.appendChild(row);
        });
        fileList.style.display = 'block';
        submitBtn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Import ' + files.length + ' File' + (files.length > 1 ? 's' : '');
    });

    function rowStatus(i, label, color, bg) {
        const el = document.getElementById('fstatus-' + i);
        if (!el) return;
        el.textContent = label;
        el.style.color = color;
        el.style.background = bg;
    }
    function rowBar(i, pct) {
        const el = document.getElementById('fbar-' + i);
        if (el) el.style.width = pct + '%';
    }

    function showError(msg) {
        errorBox.textContent   = msg;
        errorBox.style.display = 'block';
        submitBtn.disabled     = false;
        cancelBtn.disabled     = false;
        submitBtn.innerHTML    = '<i class="bi bi-cloud-upload me-1"></i>Retry';
    }

    // ── Regular upload (≤ 5 MB) — returns Promise<{scanId}|{skipped}> ──
    function uploadRegular(file, notes, onProgress) {
        return new Promise(function (resolve, reject) {
            const fd = new FormData();
            fd.append('scan_file', file);
            fd.append('notes', notes);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', UPLOAD_URL);
            xhr.setRequestHeader('X-CSRF-TOKEN', CSRF);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) onProgress(Math.round((e.loaded / e.total) * 90));
            });

            xhr.onload = function () {
                const res = (() => { try { return JSON.parse(xhr.responseText); } catch (_) { return {}; } })();
                if (xhr.status === 200) {
                    resolve({ scanId: res.scan_id });
                } else if (xhr.status === 422) {
                    resolve({ skipped: true });
                } else {
                    reject(res.errors?.scan_file || res.message || 'Upload failed (HTTP ' + xhr.status + ')');
                }
            };
            xhr.onerror = function () { reject('Network error'); };
            xhr.send(fd);
        });
    }

    // ── Chunked upload (> 5 MB) — returns {scanId}|{skipped} ────
    async function uploadChunked(file, notes, onProgress) {
        const total    = Math.ceil(file.size / CHUNK_SIZE);
        const uploadId = ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
            (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));

        for (let i = 0; i < total; i++) {
            const fd = new FormData();
            fd.append('upload_id',    uploadId);
            fd.append('chunk_index',  i);
            fd.append('total_chunks', total);
            fd.append('filename',     file.name);
            fd.append('notes',        notes);
            fd.append('chunk',        file.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE), file.name);

            onProgress(Math.round(((i + 1) / total) * 90));

            const resp = await fetch(CHUNK_URL, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: fd,
            });
            const data = await resp.json();

            if (resp.status === 422) return { skipped: true };
            if (!resp.ok) throw (data.errors?.scan_file || data.message || 'Chunk failed');
            if (data.status === 'queued') return { scanId: data.scan_id };
        }
        throw 'All chunks sent but no scan_id returned';
    }

    // ── Main: upload all files then poll all for completion ───────
    async function runUploads() {
        const files = Array.from(fileInput.files);
        const notes = notesInput.value;

        errorBox.style.display = 'none';
        submitBtn.disabled     = true;
        cancelBtn.disabled     = true;

        const queued = []; // { index, scanId }

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            rowStatus(i, 'Uploading', '#1e40af', '#dbeafe');
            rowBar(i, 0);

            try {
                const onProg  = (pct) => rowBar(i, pct);
                const result  = file.size > CHUNK_SIZE
                    ? await uploadChunked(file, notes, onProg)
                    : await uploadRegular(file, notes, onProg);

                if (result.skipped) {
                    rowBar(i, 100);
                    rowStatus(i, 'Skipped', '#475569', '#f1f5f9');
                } else {
                    rowBar(i, 95);
                    rowStatus(i, 'Queued', '#92400e', '#fef3c7');
                    queued.push({ index: i, scanId: result.scanId });
                }
            } catch (err) {
                rowStatus(i, 'Error', '#991b1b', '#fee2e2');
                showError('Failed to upload "' + file.name + '": ' + err);
                return;
            }
        }

        // If every file was a duplicate skip, just reload
        if (queued.length === 0) { window.location.href = REDIRECT; return; }

        // Poll until all queued scans are processed
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Processing…';

        const pending = new Set(queued.map(q => q.scanId));

        await new Promise(function (resolve) {
            const timer = setInterval(async function () {
                for (const { index, scanId } of queued) {
                    if (!pending.has(scanId)) continue;
                    try {
                        const resp = await fetch(STATUS_BASE + scanId, {
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        });
                        const data = await resp.json();
                        if (data.status === 'completed') {
                            pending.delete(scanId);
                            rowBar(index, 100);
                            rowStatus(index, 'Done', '#166534', '#dcfce7');
                        } else if (data.status === 'failed') {
                            pending.delete(scanId);
                            rowStatus(index, 'Failed', '#991b1b', '#fee2e2');
                        }
                    } catch (_) { /* network hiccup — keep polling */ }
                }
                if (pending.size === 0) { clearInterval(timer); resolve(); }
            }, 2000);
        });

        window.location.href = REDIRECT;
    }

    submitBtn.addEventListener('click', function () {
        if (!fileInput.files.length) {
            showError('Please select at least one file.');
            return;
        }
        runUploads();
    });

    // Reset on close
    document.getElementById('uploadModal').addEventListener('hidden.bs.modal', function () {
        fileInput.value        = '';
        notesInput.value       = '';
        errorBox.style.display = 'none';
        fileList.style.display = 'none';
        fileList.innerHTML     = '';
        submitBtn.disabled     = false;
        cancelBtn.disabled     = false;
        submitBtn.innerHTML    = '<i class="bi bi-cloud-upload me-1"></i>Import Scan';
    });
})();

@if($errors->any())
new bootstrap.Modal(document.getElementById('uploadModal')).show();
@endif
</script>
@endpush

{{-- ════ Edit Host Modal ════ --}}
<div class="modal fade" id="editHostModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none">
            <div class="modal-header px-4 pt-4 pb-3" style="border-bottom:1px solid #f1f5f9">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Edit Host</h5>
                    <div id="editHostIpLabel" style="font-size:.8rem;color:#64748b;margin-top:.2rem;font-family:monospace"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editHostForm" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem">Host Name</label>
                            <input type="text" name="hostname" id="eh-hostname" class="form-control"
                                   style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem">Role / Functionality</label>
                            <input type="text" name="system_name" id="eh-system" class="form-control"
                                   style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem">Identify Scope</label>
                            <select name="identified_scope" id="eh-scope" class="form-select"
                                    style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
                                <option value="">— Select —</option>
                                @foreach(\App\Models\AssessmentScope::scopeOptions() as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem">Environment</label>
                            <select name="environment" id="eh-env" class="form-select"
                                    style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
                                <option value="">— Select —</option>
                                @foreach(\App\Models\AssessmentScope::environmentOptions() as $e)
                                <option value="{{ $e }}">{{ $e }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem">System Criticality</label>
                            <select name="system_criticality" id="eh-criticality" class="form-select"
                                    style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
                                <option value="">— Select —</option>
                                @foreach(\App\Models\AssessmentScope::criticalityLevels() as $k => $lv)
                                <option value="{{ $k }}">{{ $k }} – {{ $lv['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem">Asset Owner</label>
                            <input type="text" name="system_owner" id="eh-owner" class="form-control"
                                   style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem">Remediation SLA</label>
                            <select name="remediation_sla" id="eh-sla" class="form-select"
                                    style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
                                <option value="">— Select —</option>
                                @foreach(\App\Models\AssessmentScope::remediationSlaOptions() as $sla)
                                <option value="{{ $sla }}">{{ $sla }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if(!$assessment->scope_group_id)
                    <div class="alert mt-3 mb-0" style="border-radius:10px;border:none;background:#fef9c3;color:#92400e;font-size:.82rem">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        No scope group linked to this assessment. Link a scope group first to save host metadata.
                    </div>
                    @endif
                </div>
                <div class="modal-footer px-4 pb-4" style="border-top:1px solid #f1f5f9">
                    <button type="button" class="btn" data-bs-dismiss="modal"
                            style="border-color:#e2e8f0;color:#64748b;border-radius:10px;font-size:.875rem">Cancel</button>
                    <button type="submit" class="btn" {{ !$assessment->scope_group_id ? 'disabled' : '' }}
                            style="background:#0f172a;color:#fff;border-radius:10px;font-size:.875rem;font-weight:600">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function () {
    const editModal = new bootstrap.Modal(document.getElementById('editHostModal'));
    const editForm  = document.getElementById('editHostForm');

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-host-edit');
        if (!btn) return;
        const d = btn.closest('tr').dataset;
        document.getElementById('editHostIpLabel').textContent = d.ip;
        document.getElementById('eh-hostname').value    = d.hostname    || '';
        document.getElementById('eh-system').value      = d.system      || '';
        document.getElementById('eh-scope').value       = d.scope       || '';
        document.getElementById('eh-env').value         = d.env         || '';
        document.getElementById('eh-criticality').value = d.criticality || '';
        document.getElementById('eh-owner').value       = d.owner       || '';
        document.getElementById('eh-sla').value         = d.sla         || '';
        editForm.action = d.updateUrl;
        editModal.show();
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-host-delete');
        if (!btn) return;
        const d = btn.closest('tr').dataset;
        if (!confirm('Remove all vulnerability tracking for ' + d.ip + ' from this assessment?\nThis cannot be undone.')) return;
        const form = btn.closest('tr').querySelector('.js-host-delete-form');
        form.action = d.destroyUrl;
        form.submit();
    });
});
</script>
@endpush

@endsection
