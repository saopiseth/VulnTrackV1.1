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
            {{-- Report dropdown --}}
            <div class="dropdown">
                <button class="btn btn-sm dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        style="background:#334155;color:#e2e8f0;border-radius:8px;font-weight:500;border:none;
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
    @if($topIps->count() || $assessment->scope_group_id)
    <button class="p-tab" data-bs-toggle="tab" data-bs-target="#tab-os" role="tab">
        <i class="bi bi-hdd-network me-1"></i>Vulnerable Hosts
        @if($topIps->count())<span class="cnt">{{ $topIps->count() }}</span>@endif
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

    {{-- ── Zone 1: Assessment meta strip ──────────────────────────── --}}
    <div class="va-card mb-3" style="padding:.85rem 1.35rem">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem 1.5rem;align-items:start">

            <div>
                <div style="font-size:.62rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">
                    <i class="bi bi-person me-1"></i>Created By
                </div>
                <div style="font-weight:600;color:#0f172a;font-size:.86rem">{{ $assessment->creator?->name ?? '—' }}</div>
            </div>

            <div>
                <div style="font-size:.62rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">
                    <i class="bi bi-calendar3 me-1"></i>Assessment Period
                </div>
                <div style="font-weight:500;color:#374151;font-size:.84rem">
                    {{ $assessment->period_start?->format('d M Y') ?? '—' }}
                    @if($assessment->period_start || $assessment->period_end) &nbsp;–&nbsp; @endif
                    {{ $assessment->period_end?->format('d M Y') ?? '' }}
                </div>
            </div>

            <div>
                <div style="font-size:.62rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">
                    <i class="bi bi-layers me-1"></i>Scans
                </div>
                <div style="font-weight:600;color:#0f172a;font-size:.86rem">
                    {{ $assessment->scans->count() }} scan{{ $assessment->scans->count() !== 1 ? 's' : '' }} uploaded
                </div>
                @if($baseline && $latestScan && $baseline->id !== $latestScan->id)
                <div style="font-size:.7rem;color:#94a3b8;margin-top:.1rem">
                    Baseline → {{ $latestScan->created_at->format('d M Y') }}
                </div>
                @endif
            </div>

            @if($assessment->description)
            <div style="grid-column:1/-1">
                <div style="font-size:.62rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">
                    <i class="bi bi-chat-left-text me-1"></i>Description
                </div>
                <div style="font-size:.82rem;color:#475569;line-height:1.55">{{ $assessment->description }}</div>
            </div>
            @endif

        </div>
    </div>

    {{-- ── Zone 2: Remediation Progress (full-width horizontal) ─────── --}}
    @if($remStats && $remStats->total > 0)
    @php
        $pctResolved = round($remStats->resolved_by_scan / max(1,$remStats->total) * 100);
        $pctAccepted = round($remStats->accepted          / max(1,$remStats->total) * 100);
        $pctClosed   = min(100, $pctResolved + $pctAccepted);
        $activeTotal = $remStats->open_count + $remStats->in_progress + $remStats->accepted;
    @endphp
    <div class="va-card mb-3" style="padding:0;overflow:hidden">

        {{-- Top label bar --}}
        <div style="padding:.6rem 1.4rem;border-bottom:1px solid #e8f5c2;display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
                          color:var(--lime-dark);display:flex;align-items:center;gap:.35rem">
                <i class="bi bi-check2-circle"></i>Remediation Progress
            </span>
            <span style="font-size:.75rem;color:#94a3b8">
                <span style="font-weight:800;color:var(--lime-dark);font-size:.95rem">{{ $pctClosed }}%</span>
                &nbsp;closure &nbsp;·&nbsp; {{ $remStats->resolved_by_scan + $remStats->accepted }} / {{ $remStats->total }} vulnerabilities closed
            </span>
        </div>

        {{-- Progress bar --}}
        <div style="height:6px;background:#f1f5f9;display:flex">
            <div style="height:100%;width:{{ $pctResolved }}%;background:#059669;transition:width .5s"></div>
            <div style="height:100%;width:{{ $pctAccepted }}%;background:#94a3b8"></div>
        </div>

        {{-- 5-column stat row --}}
        <div style="display:grid;grid-template-columns:repeat(5,1fr);border-bottom:1px solid #f1f5f9">
            @foreach([
                ['lbl'=>'Resolved',    'sub'=>'confirmed by scan', 'val'=>$remStats->resolved_by_scan,'bg'=>'#f0fdf4','col'=>'#15803d','sub_col'=>'#166534','border'=>'#bbf7d0','icon'=>'bi-patch-check-fill'],
                ['lbl'=>'In Progress', 'sub'=>'team working on it','val'=>$remStats->in_progress,     'bg'=>'#fffbeb','col'=>'#b45309','sub_col'=>'#92400e','border'=>'#fde68a','icon'=>'bi-arrow-repeat'],
                ['lbl'=>'Open',        'sub'=>'no action taken',   'val'=>$remStats->open_count,      'bg'=>'#fff8f8','col'=>'#dc2626','sub_col'=>'#991b1b','border'=>'#fecaca','icon'=>'bi-exclamation-circle-fill'],
                ['lbl'=>'Accepted',    'sub'=>'risk accepted',     'val'=>$remStats->accepted,        'bg'=>'#f8fafc','col'=>'#64748b','sub_col'=>'#475569','border'=>'#e2e8f0','icon'=>'bi-shield-check'],
                ['lbl'=>'Total',       'sub'=>'tracked findings',  'val'=>$remStats->total,           'bg'=>'#fff',   'col'=>'#0f172a','sub_col'=>'#64748b','border'=>'transparent','icon'=>'bi-database'],
            ] as $idx => $rs)
            <div style="padding:1rem 1.2rem;background:{{ $rs['bg'] }};text-align:center;
                         border-left:{{ $idx > 0 ? '1px solid '.$rs['border'] : 'none' }}">
                <div style="display:flex;align-items:center;justify-content:center;gap:.3rem;
                              font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                              color:{{ $rs['sub_col'] }};margin-bottom:.35rem">
                    <i class="bi {{ $rs['icon'] }}"></i>{{ $rs['lbl'] }}
                </div>
                <div style="font-size:1.9rem;font-weight:900;color:{{ $rs['col'] }};line-height:1">{{ $rs['val'] }}</div>
                <div style="font-size:.65rem;color:{{ $rs['sub_col'] }};margin-top:.2rem;opacity:.8">{{ $rs['sub'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Active severity breakdown row --}}
        @if($activeTotal > 0)
        <div style="padding:.55rem 1.4rem;background:#fafafa;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
            <span style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8">
                Active by severity:
            </span>
            @foreach([
                ['lbl'=>'Critical','val'=>$remStats->active_critical,'bg'=>'#fee2e2','col'=>'#991b1b','bar'=>'#dc2626'],
                ['lbl'=>'High',    'val'=>$remStats->active_high,    'bg'=>'#ffedd5','col'=>'#9a3412','bar'=>'#ea580c'],
                ['lbl'=>'Medium',  'val'=>$remStats->active_medium,  'bg'=>'#fef9c3','col'=>'#854d0e','bar'=>'#d97706'],
                ['lbl'=>'Low',     'val'=>$remStats->active_low,     'bg'=>'#f1f5f9','col'=>'#475569','bar'=>'#94a3b8'],
            ] as $sv)
            @if($sv['val'] > 0)
            <div style="display:flex;align-items:center;gap:.4rem">
                <span style="background:{{ $sv['bg'] }};color:{{ $sv['col'] }};border-radius:6px;
                              padding:.18rem .65rem;font-size:.75rem;font-weight:800">
                    {{ $sv['val'] }}
                </span>
                <span style="font-size:.72rem;color:#64748b;font-weight:600">{{ $sv['lbl'] }}</span>
            </div>
            @endif
            @endforeach

            <div style="flex:1;min-width:120px;max-width:260px;margin-left:.5rem">
                <div style="height:5px;border-radius:20px;background:#e2e8f0;overflow:hidden;display:flex;gap:1px">
                    @foreach([[$remStats->active_critical,'#dc2626'],[$remStats->active_high,'#ea580c'],[$remStats->active_medium,'#d97706'],[$remStats->active_low,'#94a3b8']] as [$v,$c])
                    @if($v > 0)<div style="flex:{{ $v }};background:{{ $c }};height:100%"></div>@endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>
    @endif


</div>

{{-- ══════════════════════════════════════════════════════════════
     TAB: Comparison
══════════════════════════════════════════════════════════════ --}}
@if($comparison)
<div class="tab-pane fade" id="tab-comparison" role="tabpanel">
@php
    $netChange  = $comparison['new'] - $comparison['resolved'];
    $netLabel   = $netChange > 0 ? "+{$netChange} more" : ($netChange < 0 ? abs($netChange).' fewer' : 'no change');
    $netColor   = $netChange > 0 ? '#dc2626' : ($netChange < 0 ? '#059669' : '#64748b');
    $netBg      = $netChange > 0 ? '#fee2e2' : ($netChange < 0 ? '#d1fae5' : '#f1f5f9');
    $netIcon    = $netChange > 0 ? 'bi-arrow-up-right' : ($netChange < 0 ? 'bi-arrow-down-right' : 'bi-dash');
@endphp

    {{-- ── Scan banner ────────────────────────────────────────────── --}}
    <div class="va-card mb-3" style="padding:0;overflow:hidden">
        <div style="display:grid;grid-template-columns:1fr auto 1fr;align-items:stretch">

            {{-- Baseline --}}
            <div style="padding:1.1rem 1.35rem;border-right:1px solid #e8f5c2">
                <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
                             color:var(--lime-dark);margin-bottom:.45rem">
                    <i class="bi bi-flag-fill me-1"></i>Baseline
                </div>
                <div style="font-weight:700;color:#0f172a;font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px"
                     title="{{ $baseline->filename }}">
                    {{ Str::limit($baseline->filename, 38) }}
                </div>
                <div class="d-flex gap-3 mt-1" style="font-size:.73rem;color:#64748b">
                    <span><i class="bi bi-list-check me-1"></i>{{ $baseline->finding_count }} findings</span>
                    <span><i class="bi bi-calendar3 me-1"></i>{{ $baseline->created_at->format('d M Y') }}</span>
                </div>
            </div>

            {{-- Net change pill --}}
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                         padding:1rem 1.5rem;background:#f8fafc;border-right:1px solid #e8f5c2;min-width:110px">
                <i class="bi bi-arrow-right" style="font-size:1.1rem;color:#cbd5e1;margin-bottom:.3rem"></i>
                <span style="font-size:.72rem;font-weight:700;padding:.22rem .7rem;border-radius:20px;
                              background:{{ $netBg }};color:{{ $netColor }};white-space:nowrap">
                    <i class="bi {{ $netIcon }} me-1"></i>{{ $netLabel }}
                </span>
                <span style="font-size:.62rem;color:#94a3b8;margin-top:.25rem">vs baseline</span>
            </div>

            {{-- Latest --}}
            <div style="padding:1.1rem 1.35rem">
                <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
                             color:#1e40af;margin-bottom:.45rem">
                    <i class="bi bi-file-earmark-bar-graph-fill me-1"></i>Latest Scan
                </div>
                <div style="font-weight:700;color:#0f172a;font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px"
                     title="{{ $latestScan->filename }}">
                    {{ Str::limit($latestScan->filename, 38) }}
                </div>
                <div class="d-flex gap-3 mt-1" style="font-size:.73rem;color:#64748b">
                    <span><i class="bi bi-list-check me-1"></i>{{ $latestScan->finding_count }} findings</span>
                    <span><i class="bi bi-calendar3 me-1"></i>{{ $latestScan->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Vulnerability delta cards ───────────────────────────────── --}}
    <div class="row g-3 mb-3">

        {{-- Resolved --}}
        <div class="col-md-4">
            <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:14px;padding:1.35rem 1.4rem;height:100%">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#166534">Fixed / Resolved</span>
                    <span style="width:32px;height:32px;border-radius:9px;background:#dcfce7;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-patch-check-fill" style="color:#16a34a;font-size:.9rem"></i>
                    </span>
                </div>
                <div style="font-size:3rem;font-weight:900;color:#15803d;line-height:1">{{ $comparison['resolved'] }}</div>
                <div style="font-size:.76rem;color:#166534;margin-top:.4rem;font-weight:500">
                    Vulnerabilities eliminated since baseline
                </div>
                @if($comparison['resolved'] > 0 && ($comparison['resolved'] + $comparison['persistent'] + $comparison['new']) > 0)
                @php $resolvedPct = round($comparison['resolved'] / max(1,$comparison['resolved']+$comparison['persistent']+$comparison['new'])*100); @endphp
                <div style="margin-top:.75rem;height:4px;background:#bbf7d0;border-radius:20px;overflow:hidden">
                    <div style="height:100%;width:{{ $resolvedPct }}%;background:#16a34a;border-radius:20px"></div>
                </div>
                <div style="font-size:.68rem;color:#166534;margin-top:.25rem">{{ $resolvedPct }}% of total</div>
                @endif
            </div>
        </div>

        {{-- New --}}
        <div class="col-md-4">
            <div style="background:#fff8f8;border:1.5px solid #fca5a5;border-radius:14px;padding:1.35rem 1.4rem;height:100%">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#991b1b">New Findings</span>
                    <span style="width:32px;height:32px;border-radius:9px;background:#fee2e2;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-exclamation-triangle-fill" style="color:#dc2626;font-size:.9rem"></i>
                    </span>
                </div>
                <div style="font-size:3rem;font-weight:900;color:#dc2626;line-height:1">{{ $comparison['new'] }}</div>
                <div style="font-size:.76rem;color:#991b1b;margin-top:.4rem;font-weight:500">
                    New vulnerabilities in latest scan
                </div>
                @if($comparison['new'] > 0)
                @php $newPct = round($comparison['new'] / max(1,$comparison['resolved']+$comparison['persistent']+$comparison['new'])*100); @endphp
                <div style="margin-top:.75rem;height:4px;background:#fecaca;border-radius:20px;overflow:hidden">
                    <div style="height:100%;width:{{ $newPct }}%;background:#dc2626;border-radius:20px"></div>
                </div>
                <div style="font-size:.68rem;color:#991b1b;margin-top:.25rem">{{ $newPct }}% of total</div>
                @endif
            </div>
        </div>

        {{-- Persistent --}}
        <div class="col-md-4">
            <div style="background:#fffbeb;border:1.5px solid #fcd34d;border-radius:14px;padding:1.35rem 1.4rem;height:100%">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#92400e">Still Persistent</span>
                    <span style="width:32px;height:32px;border-radius:9px;background:#fef3c7;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-arrow-repeat" style="color:#d97706;font-size:.9rem"></i>
                    </span>
                </div>
                <div style="font-size:3rem;font-weight:900;color:#b45309;line-height:1">{{ $comparison['persistent'] }}</div>
                <div style="font-size:.76rem;color:#92400e;margin-top:.4rem;font-weight:500">
                    Unresolved across both scans
                </div>
                @if($comparison['persistent'] > 0)
                @php $persPct = round($comparison['persistent'] / max(1,$comparison['resolved']+$comparison['persistent']+$comparison['new'])*100); @endphp
                <div style="margin-top:.75rem;height:4px;background:#fde68a;border-radius:20px;overflow:hidden">
                    <div style="height:100%;width:{{ $persPct }}%;background:#d97706;border-radius:20px"></div>
                </div>
                <div style="font-size:.68rem;color:#92400e;margin-top:.25rem">{{ $persPct }}% of total</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Host / IP tracking ──────────────────────────────────────── --}}
    @if($hostComparison)
    <div class="va-card mb-3">
        <div class="section-label"><i class="bi bi-hdd-network"></i>Host / IP Tracking</div>

        {{-- Host count strip --}}
        <div class="row g-2 mb-3">
            @foreach([
                ['lbl'=>'Baseline Hosts', 'val'=>$hostComparison['baseline_count'], 'icon'=>'bi-flag',          'bg'=>'#eff6ff','border'=>'#bfdbfe','col'=>'#1e40af'],
                ['lbl'=>'Latest Hosts',   'val'=>$hostComparison['latest_count'],   'icon'=>'bi-hdd-network',   'bg'=>'#f0fdf4','border'=>'#86efac','col'=>'#166534'],
                ['lbl'=>'New IPs',        'val'=>$hostComparison['new'],            'icon'=>'bi-plus-circle',   'bg'=>'#fff8f8','border'=>'#fca5a5','col'=>'#991b1b'],
                ['lbl'=>'Removed IPs',    'val'=>$hostComparison['removed'],        'icon'=>'bi-dash-circle',   'bg'=>'#f0fdf4','border'=>'#86efac','col'=>'#166534'],
                ['lbl'=>'Unchanged IPs',  'val'=>$hostComparison['persistent'],     'icon'=>'bi-arrow-repeat',  'bg'=>'#fffbeb','border'=>'#fcd34d','col'=>'#92400e'],
            ] as $hc)
            <div class="col-6 col-md">
                <div style="background:{{ $hc['bg'] }};border:1px solid {{ $hc['border'] }};border-radius:10px;
                             padding:.8rem .9rem;display:flex;align-items:center;gap:.65rem">
                    <div style="width:32px;height:32px;border-radius:8px;background:{{ $hc['border'] }};flex-shrink:0;
                                 display:flex;align-items:center;justify-content:center">
                        <i class="bi {{ $hc['icon'] }}" style="color:{{ $hc['col'] }};font-size:.82rem"></i>
                    </div>
                    <div>
                        <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:{{ $hc['col'] }}">{{ $hc['lbl'] }}</div>
                        <div style="font-size:1.45rem;font-weight:800;color:{{ $hc['col'] }};line-height:1.15">{{ $hc['val'] }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- IP chip lists side by side --}}
        @if($hostComparison['new_ips']->isNotEmpty() || $hostComparison['removed_ips']->isNotEmpty())
        <div class="row g-3">
            @if($hostComparison['new_ips']->isNotEmpty())
            <div class="{{ $hostComparison['removed_ips']->isNotEmpty() ? 'col-md-6' : 'col-12' }}">
                <div style="background:#fff8f8;border:1px solid #fca5a5;border-radius:10px;padding:.85rem 1rem">
                    <div style="font-size:.7rem;font-weight:700;color:#991b1b;margin-bottom:.6rem;display:flex;align-items:center;gap:.35rem">
                        <i class="bi bi-plus-circle-fill"></i>
                        New IPs in latest scan
                        <span style="background:#fee2e2;color:#991b1b;border-radius:20px;padding:.05rem .45rem;font-size:.62rem;margin-left:.2rem">{{ $hostComparison['new_ips']->count() }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($hostComparison['new_ips'] as $ip)
                        <span style="font-family:monospace;font-size:.75rem;background:#fff;color:#991b1b;
                                     border-radius:6px;padding:.18rem .55rem;border:1px solid #fca5a5;font-weight:600">{{ $ip }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            @if($hostComparison['removed_ips']->isNotEmpty())
            <div class="{{ $hostComparison['new_ips']->isNotEmpty() ? 'col-md-6' : 'col-12' }}">
                <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:.85rem 1rem">
                    <div style="font-size:.7rem;font-weight:700;color:#166534;margin-bottom:.6rem;display:flex;align-items:center;gap:.35rem">
                        <i class="bi bi-dash-circle-fill"></i>
                        IPs removed since baseline
                        <span style="background:#dcfce7;color:#166534;border-radius:20px;padding:.05rem .45rem;font-size:.62rem;margin-left:.2rem">{{ $hostComparison['removed_ips']->count() }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($hostComparison['removed_ips'] as $ip)
                        <span style="font-family:monospace;font-size:.75rem;background:#fff;color:#166534;
                                     border-radius:6px;padding:.18rem .55rem;border:1px solid #86efac;font-weight:600">{{ $ip }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- ── View findings CTA ───────────────────────────────────────── --}}
    <div class="d-flex justify-content-center mt-1">
        <a href="{{ route('vuln-assessments.findings', $assessment) }}" class="btn"
            style="background:var(--lime);color:#fff;border-radius:10px;font-weight:600;border:none;
                   padding:.55rem 1.85rem;font-size:.88rem;box-shadow:0 2px 8px rgba(118,151,7,.25)">
            <i class="bi bi-table me-2"></i>View Full Findings Table
        </a>
    </div>

</div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     TAB: Vulnerable Hosts
══════════════════════════════════════════════════════════════ --}}
@if($topIps->count() || $assessment->scope_group_id)
<div class="tab-pane fade" id="tab-os" role="tabpanel">

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
                    onmouseover="this.style.background='#f8fce8'" onmouseout="this.style.background='{{ $loop->even ? '#fafafa' : '' }}'">

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
