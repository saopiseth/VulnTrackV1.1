@extends('layouts.app')
@section('title', $assessment->name)

@section('content')
<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-muted: rgb(232,244,195); }

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

    /* ── Comparison delta cards ── */
    .delta-card { border-radius:12px; padding:1.1rem 1.25rem; }

    /* ── Progress ── */
    .prog-bar { height:7px; border-radius:20px; background:#e8f5c2; overflow:hidden; }
    .prog-fill { height:100%; border-radius:20px; background:var(--lime); }
    .prog-thin { height:3px; border-radius:10px; background:#e2e8f0; overflow:hidden; margin-top:.25rem; }
</style>

{{-- ── Hero header ── --}}
<div style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);border-radius:14px;
            padding:1.4rem 1.75rem;margin-bottom:1.25rem">

    {{-- Breadcrumb --}}
    <nav style="margin-bottom:.55rem">
        <ol class="breadcrumb mb-0" style="font-size:.73rem">
            <li class="breadcrumb-item">
                <a href="{{ route('vuln-assessments.index') }}" style="color:#94a3b8;text-decoration:none">VA Assessments</a>
            </li>
            <li class="breadcrumb-item active" style="color:#e2e8f0">{{ Str::limit($assessment->name, 50) }}</li>
        </ol>
    </nav>

    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div style="min-width:0">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h4 style="color:#f8fafc;margin:0;font-size:1.25rem;font-weight:700">{{ $assessment->name }}</h4>
                @if($assessment->environment)
                <span class="badge-env env-{{ strtolower($assessment->environment) }}">{{ $assessment->environment }}</span>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-3" style="font-size:.78rem;color:#94a3b8">
                @if($assessment->period_start || $assessment->period_end)
                <span><i class="bi bi-calendar3 me-1"></i>{{ $assessment->period_start?->format('d M Y') ?? '—' }} – {{ $assessment->period_end?->format('d M Y') ?? '—' }}</span>
                @endif
                <span><i class="bi bi-person me-1"></i>{{ $assessment->creator?->name ?? '—' }}</span>
                <span><i class="bi bi-cloud-upload me-1"></i>{{ $assessment->scans->count() }} scan{{ $assessment->scans->count() !== 1 ? 's' : '' }}</span>
            </div>
            @if($assessment->description)
            <div style="margin-top:.55rem;font-size:.8rem;color:#cbd5e1;line-height:1.55;max-width:560px">
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
            @endif
            @if($osHostCount > 0)
            <a href="{{ route('vuln-assessments.os-assets', $assessment) }}" class="btn btn-sm"
                style="background:#334155;color:#e2e8f0;border-radius:8px;font-weight:500;border:none;
                       padding:.4rem .95rem;font-size:.81rem">
                <i class="bi bi-cpu me-1"></i>OS Assets
            </a>
            @endif
            @if($activeScan)
            <div class="dropdown">
                <button class="btn btn-sm dropdown-toggle"
                    style="background:#334155;color:#e2e8f0;border-radius:8px;font-weight:500;border:none;
                           padding:.4rem .95rem;font-size:.81rem"
                    data-bs-toggle="dropdown">
                    <i class="bi bi-tags-fill me-1"></i>Classify
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="font-size:.83rem;min-width:220px;border-radius:10px;border:1px solid #e8f5c2">
                    <li>
                        <form method="POST" action="{{ route('vuln-assessments.reclassify', $assessment) }}">
                            @csrf
                            <button type="submit" class="dropdown-item" style="padding:.55rem 1rem">
                                <i class="bi bi-magic me-2" style="color:var(--lime)"></i>Auto-classify unclassified
                            </button>
                        </form>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('vuln-assessments.reclassify', $assessment) }}"
                              onsubmit="return confirm('Re-classify ALL? This will overwrite manual categories.')">
                            @csrf
                            <input type="hidden" name="force" value="1">
                            <button type="submit" class="dropdown-item" style="padding:.55rem 1rem">
                                <i class="bi bi-arrow-repeat me-2 text-danger"></i>Re-classify all
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            @endif
            <button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal"
                style="background:#334155;color:#e2e8f0;border-radius:8px;font-weight:500;border:none;
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
    <button class="p-tab active" data-bs-toggle="tab" data-bs-target="#tab-overview" role="tab">
        <i class="bi bi-grid-1x2 me-1"></i>Overview
    </button>
    @if($comparison)
    <button class="p-tab" data-bs-toggle="tab" data-bs-target="#tab-comparison" role="tab">
        <i class="bi bi-arrow-left-right me-1"></i>Comparison
    </button>
    @endif
    @if($osHostCount > 0)
    <button class="p-tab" data-bs-toggle="tab" data-bs-target="#tab-os" role="tab">
        <i class="bi bi-cpu me-1"></i>OS Distribution
        <span class="cnt">{{ $osHostCount }}</span>
    </button>
    @endif
    <button class="p-tab" data-bs-toggle="tab" data-bs-target="#tab-scans" role="tab">
        <i class="bi bi-cloud-upload me-1"></i>Scans
        <span class="cnt">{{ $assessment->scans->count() }}</span>
    </button>
</div>

<div class="tab-content">

{{-- ══════════════════════════════════════════════════════════════
     TAB: Overview
══════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
    <div class="row g-3">

        {{-- Remediation Progress --}}
        @if($remStats && $remStats->total > 0)
        <div class="col-lg-6">
            <div class="va-card" style="height:100%">
                <div class="section-label"><i class="bi bi-check2-circle"></i>Remediation Progress</div>
                @php
                    $pct = round(($remStats->resolved + $remStats->accepted) / max(1,$remStats->total) * 100);
                @endphp
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.82rem">
                    <span style="color:#374151;font-weight:600">Overall closure rate</span>
                    <span style="font-weight:800;color:var(--lime-dark);font-size:.95rem">{{ $pct }}%</span>
                </div>
                <div class="prog-bar mb-3">
                    <div class="prog-fill" style="width:{{ $pct }}%"></div>
                </div>
                <div class="row g-2">
                    @foreach([
                        ['lbl'=>'Open',          'val'=>$remStats->open_count, 'col'=>'#dc2626'],
                        ['lbl'=>'In Progress',   'val'=>$remStats->in_progress,'col'=>'#d97706'],
                        ['lbl'=>'Resolved',      'val'=>$remStats->resolved,   'col'=>'#059669'],
                        ['lbl'=>'Accepted Risk', 'val'=>$remStats->accepted,   'col'=>'#64748b'],
                    ] as $r)
                    <div class="col-6">
                        <div style="font-size:.72rem;color:#94a3b8;font-weight:600">{{ $r['lbl'] }}</div>
                        <div style="font-size:1.25rem;font-weight:800;color:{{ $r['col'] }}">{{ $r['val'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Top Vulnerable IPs --}}
        @if($topIps->count())
        <div class="col-lg-6">
            <div class="va-card" style="height:100%">
                <div class="section-label"><i class="bi bi-hdd-network"></i>Top Vulnerable IPs</div>
                @foreach($topIps as $ip)
                @php
                    $pctIp  = $stats->total > 0 ? round($ip->total / $stats->total * 100) : 0;
                    $hostInfo = \App\Models\VulnFinding::where('scan_id', $activeScan->id)
                        ->where('ip_address', $ip->ip_address)
                        ->whereNotNull('os_detected')->select('hostname','os_detected')->first()
                        ?? \App\Models\VulnFinding::where('scan_id', $activeScan->id)
                            ->where('ip_address', $ip->ip_address)->select('hostname','os_detected')->first();
                    $osLower = strtolower($hostInfo?->os_detected ?? '');
                    $osIcon  = str_contains($osLower,'windows') ? 'bi-windows'
                             : (preg_match('/linux|ubuntu|centos|debian|redhat/',$osLower) ? 'bi-ubuntu'
                             : (str_contains($osLower,'cisco') ? 'bi-router' : 'bi-cpu'));
                @endphp
                <div style="margin-bottom:.8rem">
                    <div class="d-flex justify-content-between align-items-start" style="font-size:.81rem">
                        <div>
                            <span style="font-family:monospace;font-weight:700;color:#0f172a">{{ $ip->ip_address }}</span>
                            @if($hostInfo?->hostname)
                            <span style="font-size:.7rem;color:#64748b;margin-left:.4rem">{{ $hostInfo->hostname }}</span>
                            @endif
                            @if($hostInfo?->os_detected)
                            <div style="font-size:.67rem;background:#f1f5f9;color:#475569;padding:.08rem .38rem;
                                        border-radius:5px;display:inline-flex;align-items:center;gap:.22rem;margin-top:.15rem">
                                <i class="bi {{ $osIcon }}" style="font-size:.65rem"></i>
                                {{ Str::limit($hostInfo->os_detected, 28) }}
                            </div>
                            @endif
                        </div>
                        <div class="text-end flex-shrink-0 ms-2">
                            @if($ip->critical > 0)
                            <span style="font-size:.68rem;background:#fee2e2;color:#991b1b;border-radius:12px;
                                         padding:.08rem .4rem;font-weight:700;display:block;margin-bottom:1px">C:{{ $ip->critical }}</span>
                            @endif
                            @if($ip->high > 0)
                            <span style="font-size:.68rem;background:#ffedd5;color:#9a3412;border-radius:12px;
                                         padding:.08rem .4rem;font-weight:700;display:block">H:{{ $ip->high }}</span>
                            @endif
                            <div style="font-size:.68rem;color:#94a3b8;margin-top:.1rem">{{ $ip->total }} total</div>
                        </div>
                    </div>
                    <div class="prog-thin">
                        <div style="height:100%;width:{{ $pctIp }}%;background:var(--lime);border-radius:10px"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Assessment Info --}}
        <div class="col-12">
            <div class="va-card">
                <div class="section-label"><i class="bi bi-info-circle"></i>Assessment Info</div>
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Created By</div>
                        <div style="font-weight:600;color:#0f172a;font-size:.88rem;margin-top:.15rem">{{ $assessment->creator?->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Period</div>
                        <div style="font-weight:500;color:#374151;font-size:.86rem;margin-top:.15rem">
                            {{ $assessment->period_start?->format('d M Y') ?? '—' }}
                            @if($assessment->period_start || $assessment->period_end) – @endif
                            {{ $assessment->period_end?->format('d M Y') ?? '' }}
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Baseline</div>
                        <div style="font-size:.83rem;color:#374151;margin-top:.15rem">{{ $baseline ? Str::limit($baseline->filename, 30) : '—' }}</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Latest Scan</div>
                        <div style="font-size:.83rem;color:#374151;margin-top:.15rem">{{ $latestScan ? Str::limit($latestScan->filename, 30) : '—' }}</div>
                    </div>
                    @if($assessment->description)
                    <div class="col-12">
                        <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.35rem">Description</div>
                        <div style="font-size:.875rem;color:#374151;line-height:1.6">{{ $assessment->description }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     TAB: Comparison
══════════════════════════════════════════════════════════════ --}}
@if($comparison)
<div class="tab-pane fade" id="tab-comparison" role="tabpanel">

    {{-- Delta cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="delta-card" style="background:#fee2e2;border:1px solid #fca5a5">
                <div style="font-size:.68rem;color:#991b1b;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.4rem">
                    <i class="bi bi-plus-circle me-1"></i>New Vulnerabilities
                </div>
                <div style="font-size:2.4rem;font-weight:900;color:#dc2626;line-height:1.1">{{ $comparison['new'] }}</div>
                <div style="font-size:.77rem;color:#b91c1c;margin-top:.25rem">Found in latest, not in baseline</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="delta-card" style="background:#d1fae5;border:1px solid #6ee7b7">
                <div style="font-size:.68rem;color:#065f46;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.4rem">
                    <i class="bi bi-check-circle me-1"></i>Resolved
                </div>
                <div style="font-size:2.4rem;font-weight:900;color:#059669;line-height:1.1">{{ $comparison['resolved'] }}</div>
                <div style="font-size:.77rem;color:#047857;margin-top:.25rem">In baseline, not in latest</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="delta-card" style="background:#fef9c3;border:1px solid #fde047">
                <div style="font-size:.68rem;color:#854d0e;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.4rem">
                    <i class="bi bi-arrow-repeat me-1"></i>Persistent
                </div>
                <div style="font-size:2.4rem;font-weight:900;color:#d97706;line-height:1.1">{{ $comparison['persistent'] }}</div>
                <div style="font-size:.77rem;color:#92400e;margin-top:.25rem">Exist in both scans</div>
            </div>
        </div>
    </div>

    {{-- Host tracking comparison --}}
    @if($hostComparison)
    <div class="va-card mb-3">
        <div class="section-label"><i class="bi bi-hdd-network"></i>Host / IP Tracking</div>
        <div class="row g-2 mb-3">
            @foreach([
                ['lbl'=>'Baseline Hosts','val'=>$hostComparison['baseline_count'],'bg'=>'#eff6ff','border'=>'#bfdbfe','col'=>'#1e40af'],
                ['lbl'=>'Latest Hosts',  'val'=>$hostComparison['latest_count'],  'bg'=>'#eff6ff','border'=>'#bfdbfe','col'=>'#1e40af'],
                ['lbl'=>'New IPs',       'val'=>$hostComparison['new'],           'bg'=>'#fee2e2','border'=>'#fca5a5','col'=>'#991b1b'],
                ['lbl'=>'Removed IPs',   'val'=>$hostComparison['removed'],       'bg'=>'#d1fae5','border'=>'#6ee7b7','col'=>'#065f46'],
                ['lbl'=>'Persistent',    'val'=>$hostComparison['persistent'],    'bg'=>'#fef9c3','border'=>'#fde047','col'=>'#854d0e'],
            ] as $hc)
            <div class="col-6 col-md">
                <div style="background:{{ $hc['bg'] }};border:1px solid {{ $hc['border'] }};border-radius:10px;
                             padding:.75rem 1rem;text-align:center">
                    <div style="font-size:.62rem;color:{{ $hc['col'] }};font-weight:700;text-transform:uppercase;letter-spacing:.5px">{{ $hc['lbl'] }}</div>
                    <div style="font-size:1.65rem;font-weight:800;color:{{ $hc['col'] }};line-height:1.2">{{ $hc['val'] }}</div>
                </div>
            </div>
            @endforeach
        </div>

        @if($hostComparison['new_ips']->isNotEmpty())
        <div class="mb-2">
            <div style="font-size:.73rem;font-weight:700;color:#991b1b;margin-bottom:.4rem">
                <i class="bi bi-plus-circle-fill me-1"></i>New IPs in latest scan
            </div>
            <div class="d-flex flex-wrap gap-1">
                @foreach($hostComparison['new_ips'] as $ip)
                <span style="font-family:monospace;font-size:.76rem;background:#fee2e2;color:#991b1b;
                             border-radius:6px;padding:.12rem .5rem;border:1px solid #fca5a5">{{ $ip }}</span>
                @endforeach
            </div>
        </div>
        @endif
        @if($hostComparison['removed_ips']->isNotEmpty())
        <div>
            <div style="font-size:.73rem;font-weight:700;color:#065f46;margin-bottom:.4rem">
                <i class="bi bi-dash-circle-fill me-1"></i>IPs not present in latest scan
            </div>
            <div class="d-flex flex-wrap gap-1">
                @foreach($hostComparison['removed_ips'] as $ip)
                <span style="font-family:monospace;font-size:.76rem;background:#d1fae5;color:#065f46;
                             border-radius:6px;padding:.12rem .5rem;border:1px solid #6ee7b7">{{ $ip }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    <div class="va-card" style="padding:.85rem 1.35rem">
        <div class="d-flex gap-3 align-items-center flex-wrap" style="font-size:.85rem">
            <div>
                <span style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Baseline</span><br>
                <span style="font-weight:600;color:#0f172a">{{ $baseline->filename }}</span>
                <span style="font-size:.73rem;color:#94a3b8;margin-left:.4rem">
                    ({{ $baseline->finding_count }} findings · {{ $baseline->created_at->format('d M Y') }})
                </span>
            </div>
            <i class="bi bi-arrow-right" style="color:#94a3b8;font-size:1.1rem"></i>
            <div>
                <span style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Latest</span><br>
                <span style="font-weight:600;color:#0f172a">{{ $latestScan->filename }}</span>
                <span style="font-size:.73rem;color:#94a3b8;margin-left:.4rem">
                    ({{ $latestScan->finding_count }} findings · {{ $latestScan->created_at->format('d M Y') }})
                </span>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">
        <a href="{{ route('vuln-assessments.findings', $assessment) }}" class="btn btn-sm"
            style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.5rem 1.5rem">
            <i class="bi bi-table me-1"></i>View All Findings with Comparison Labels
        </a>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     TAB: OS Distribution
══════════════════════════════════════════════════════════════ --}}
@if($osHostCount > 0)
<div class="tab-pane fade" id="tab-os" role="tabpanel">
    @php
        $totalOsHosts = $osDistribution->sum('cnt');
        $familyMeta = [
            'Windows' => ['icon'=>'bi-windows',       'bg'=>'#dbeafe','color'=>'#1e40af','label'=>'Windows'],
            'Linux'   => ['icon'=>'bi-ubuntu',         'bg'=>'#d1fae5','color'=>'#065f46','label'=>'Linux'],
            'Unix'    => ['icon'=>'bi-terminal-fill',  'bg'=>'#ffedd5','color'=>'#7c2d12','label'=>'Unix'],
            'Other'   => ['icon'=>'bi-cpu-fill',       'bg'=>'#f3f4f6','color'=>'#374151','label'=>'Other'],
        ];
    @endphp

    <div class="row g-2 mb-3">
        @foreach($osDistribution as $dist)
        @php $meta = $familyMeta[$dist->family] ?? $familyMeta['Other']; $pct = $totalOsHosts > 0 ? round($dist->cnt/$totalOsHosts*100) : 0; @endphp
        <div class="col-md-3 col-6">
            <div class="va-card" style="border-color:{{ $meta['bg'] }};text-align:center;padding:1rem 1.1rem">
                <div style="width:42px;height:42px;border-radius:10px;background:{{ $meta['bg'] }};
                             display:flex;align-items:center;justify-content:center;margin:0 auto .6rem;
                             font-size:1.1rem;color:{{ $meta['color'] }}">
                    <i class="bi {{ $meta['icon'] }}"></i>
                </div>
                <div style="font-size:1.65rem;font-weight:800;color:{{ $meta['color'] }};line-height:1.1">{{ $dist->cnt }}</div>
                <div style="font-size:.72rem;font-weight:700;color:{{ $meta['color'] }};text-transform:uppercase;letter-spacing:.4px">{{ $meta['label'] }}</div>
                <div style="font-size:.73rem;color:#94a3b8;margin-top:.15rem">{{ $pct }}% of hosts</div>
                <div style="height:4px;background:#f1f5f9;border-radius:20px;margin-top:.55rem;overflow:hidden">
                    <div style="height:100%;width:{{ $pct }}%;background:{{ $meta['color'] }};border-radius:20px"></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="va-card">
        <div class="section-label"><i class="bi bi-list-ul"></i>OS Name Breakdown</div>
        @php
            $osNames = \App\Models\VulnHostOs::where('assessment_id', $assessment->id)
                ->selectRaw("COALESCE(os_override, os_name, 'Unknown') as name, COALESCE(os_override_family, os_family) as family, COUNT(*) as cnt")
                ->groupBy('name','family')->orderByDesc('cnt')->limit(20)->get();
        @endphp
        <div class="row g-2">
            @foreach($osNames as $row)
            @php $meta = $familyMeta[$row->family] ?? $familyMeta['Other']; $pct = $totalOsHosts > 0 ? round($row->cnt/$totalOsHosts*100) : 0; @endphp
            <div class="col-md-6">
                <div style="display:flex;align-items:center;gap:.6rem;padding:.42rem .65rem;border-radius:8px;background:#f8fafc">
                    <span style="min-width:24px;height:24px;border-radius:6px;background:{{ $meta['bg'] }};
                                 display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi {{ $meta['icon'] }}" style="font-size:.7rem;color:{{ $meta['color'] }}"></i>
                    </span>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:.78rem;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $row->name }}</div>
                        <div class="prog-thin"><div style="height:100%;width:{{ $pct }}%;background:{{ $meta['color'] }};border-radius:10px"></div></div>
                    </div>
                    <span style="font-size:.8rem;font-weight:700;color:{{ $meta['color'] }};min-width:22px;text-align:right">{{ $row->cnt }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="text-center mt-2">
        <a href="{{ route('vuln-assessments.os-assets', $assessment) }}" class="btn btn-sm"
            style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.5rem 1.5rem">
            <i class="bi bi-cpu me-1"></i>View Full OS Assets List
        </a>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     TAB: Scans
══════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="tab-scans" role="tabpanel">
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

{{-- ══ Upload Modal (unchanged) ══ --}}
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
            <div class="modal-header" style="border-bottom:2px solid var(--lime);padding:1rem 1.5rem">
                <h5 class="modal-title" style="font-size:.95rem;font-weight:700;color:#0f172a">
                    <i class="bi bi-upload me-2" style="color:var(--lime)"></i>
                    Upload {{ $assessment->scans->count() === 0 ? 'Baseline' : 'Latest' }} Scan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('vuln-assessments.upload', $assessment) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body" style="padding:1.5rem">
                    @if($errors->any())
                    <div class="alert alert-danger" style="font-size:.85rem;border-radius:8px">{{ $errors->first() }}</div>
                    @endif
                    @if($assessment->scans->count() === 0)
                    <div class="alert" style="background:#e8f5c2;border:1px solid #c8e87a;border-radius:9px;
                                              font-size:.83rem;color:var(--lime-dark);padding:.65rem 1rem">
                        <i class="bi bi-info-circle me-1"></i>
                        The first upload will be set as the <strong>Baseline Scan</strong>. Subsequent uploads will be the Latest Scan for comparison.
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151">
                            Scan File <span style="color:#dc2626">*</span>
                        </label>
                        <input type="file" name="scan_file" class="form-control" accept=".xml,.nessus,.csv,.txt"
                               required style="border-radius:8px;font-size:.875rem">
                        <div style="font-size:.73rem;color:#94a3b8;margin-top:.4rem">
                            Supported: <strong>.nessus</strong> (XML), <strong>.csv</strong> (Tenable export). Max 50 MB.
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151">Notes</label>
                        <input type="text" name="notes" class="form-control"
                               placeholder="Optional notes about this scan" style="border-radius:8px;font-size:.875rem">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.75rem 1.5rem">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                        style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff;font-weight:500">Cancel</button>
                    <button type="submit" class="btn btn-sm"
                        style="background:var(--lime);color:#fff;border-radius:8px;font-weight:600;border:none;padding:.45rem 1.2rem">
                        <i class="bi bi-cloud-upload me-1"></i>Import Scan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
@if($errors->any())
new bootstrap.Modal(document.getElementById('uploadModal')).show();
@endif
</script>
@endpush
@endsection
