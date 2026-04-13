@extends('layouts.app')
@section('title', $assessment->name)

@section('content')
<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-light: rgb(240,248,210); --lime-muted: rgb(232,244,195); }
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .va-card h6 { font-size:.8rem; font-weight:700; color:var(--lime-dark); text-transform:uppercase; letter-spacing:.8px; margin-bottom:1rem; padding-bottom:.5rem; border-bottom:2px solid var(--lime); }
    .stat-box { border-radius:12px; padding:1rem 1.2rem; }
    .badge-env { padding:.25rem .7rem; border-radius:20px; font-size:.72rem; font-weight:700; display:inline-block; }
    .env-production { background:#fee2e2; color:#991b1b; }
    .env-uat        { background:#fef9c3; color:#854d0e; }
    .env-internal   { background:#e0f2fe; color:#0c4a6e; }
    .env-development{ background:#f1f5f9; color:#475569; }
    .scan-item { border:1px solid #e8f5c2; border-radius:10px; padding:.85rem 1rem; margin-bottom:.6rem; display:flex; align-items:center; gap:.75rem; }
    .scan-baseline-badge { background:var(--lime-muted); color:var(--lime-dark); font-size:.65rem; font-weight:700; padding:.15rem .5rem; border-radius:20px; text-transform:uppercase; letter-spacing:.5px; }
    .scan-latest-badge { background:#dbeafe; color:#1e40af; font-size:.65rem; font-weight:700; padding:.15rem .5rem; border-radius:20px; text-transform:uppercase; letter-spacing:.5px; }
    .cmp-box { border-radius:12px; padding:1.1rem 1.3rem; text-align:center; }
    .nav-tabs .nav-link { font-size:.84rem; font-weight:500; color:#64748b; border:none; border-bottom:2px solid transparent; border-radius:0; padding:.6rem 1rem; }
    .nav-tabs .nav-link.active { color:var(--lime-dark); border-bottom-color:var(--lime); font-weight:700; background:transparent; }
    .nav-tabs { border-bottom:2px solid #e2e8f0; margin-bottom:1.25rem; }
    thead.lime-head th { background:var(--lime-muted) !important; color:var(--lime-dark) !important; }
    .progress { height:8px; border-radius:20px; }
</style>

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4>{{ $assessment->name }}</h4>
        <p>
            @if($assessment->period_start || $assessment->period_end)
            <span style="color:#64748b;font-size:.85rem">
                <i class="bi bi-calendar3 me-1"></i>
                {{ $assessment->period_start?->format('d M Y') ?? '—' }}
                &nbsp;&ndash;&nbsp;
                {{ $assessment->period_end?->format('d M Y') ?? '—' }}
            </span>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($activeScan)
        <a href="{{ route('vuln-assessments.findings', $assessment) }}" class="btn btn-sm"
            style="background:rgb(152,194,10);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1rem">
            <i class="bi bi-table me-1"></i> View Findings
        </a>
        @endif
        @if($osHostCount > 0)
        <a href="{{ route('vuln-assessments.os-assets', $assessment) }}" class="btn btn-sm"
            style="background:#0f172a;color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1rem">
            <i class="bi bi-cpu me-1"></i> OS Assets
        </a>
        @endif
        {{-- Auto-Classify button: shown when there are unclassified findings --}}
        @if($activeScan)
        <div class="dropdown">
            <button class="btn btn-sm dropdown-toggle" data-bs-toggle="dropdown"
                style="background:#475569;color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1rem">
                <i class="bi bi-tags-fill me-1"></i> Classify
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="font-size:.83rem;min-width:220px;border-radius:10px;border:1px solid #e8f5c2">
                <li>
                    <form method="POST" action="{{ route('vuln-assessments.reclassify', $assessment) }}">
                        @csrf
                        <button type="submit" class="dropdown-item" style="padding:.55rem 1rem">
                            <i class="bi bi-magic me-2" style="color:rgb(118,151,7)"></i>
                            Auto-classify unclassified
                        </button>
                    </form>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('vuln-assessments.reclassify', $assessment) }}"
                        onsubmit="return confirm('Re-classify ALL findings? This will overwrite any manual categories.')">
                        @csrf
                        <input type="hidden" name="force" value="1">
                        <button type="submit" class="dropdown-item" style="padding:.55rem 1rem">
                            <i class="bi bi-arrow-repeat me-2" style="color:#dc2626"></i>
                            Re-classify all (overwrite)
                        </button>
                    </form>
                </li>
            </ul>
        </div>
        @endif
        <button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal"
            style="background:#0f172a;color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1rem">
            <i class="bi bi-upload me-1"></i> Upload Scan
        </button>
        <a href="{{ route('vuln-assessments.index') }}" class="btn btn-sm"
            style="border:1.5px solid rgb(152,194,10);border-radius:9px;color:rgb(118,151,7);background:#fff;font-weight:500">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Tabs --}}
<ul class="nav nav-tabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-overview"><i class="bi bi-grid me-1"></i>Overview</a></li>
    @if($comparison)
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-comparison"><i class="bi bi-arrow-left-right me-1"></i>Comparison</a></li>
    @endif
    @if($osHostCount > 0)
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-os"><i class="bi bi-cpu me-1"></i>OS Distribution <span style="font-size:.7rem;background:#e8f5c2;color:rgb(118,151,7);border-radius:10px;padding:.05rem .4rem;margin-left:.25rem">{{ $osHostCount }}</span></a></li>
    @endif
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-scans"><i class="bi bi-cloud-upload me-1"></i>Scans ({{ $assessment->scans->count() }})</a></li>
</ul>

<div class="tab-content">

{{-- ── TAB: Overview ── --}}
<div class="tab-pane fade show active" id="tab-overview">
    <div class="row g-3">

        {{-- Severity stats --}}
        <div class="col-12">
            @if($activeScan && $stats)
            <div class="row g-2 mb-3">
                <div class="col-6 col-md">
                    <div class="stat-box" style="background:#fff;border:1px solid #e8f5c2">
                        <div style="font-size:.7rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Total</div>
                        <div style="font-size:1.6rem;font-weight:800;color:#0f172a">{{ $stats->total }}</div>
                        <div style="font-size:.72rem;color:#94a3b8">C / H / M / L findings</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="stat-box" style="background:#fee2e2;border:1px solid #fca5a5">
                        <div style="font-size:.7rem;color:#991b1b;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Critical</div>
                        <div style="font-size:1.6rem;font-weight:800;color:#991b1b">{{ $stats->critical }}</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="stat-box" style="background:#ffedd5;border:1px solid #fdba74">
                        <div style="font-size:.7rem;color:#9a3412;font-weight:600;text-transform:uppercase;letter-spacing:.5px">High</div>
                        <div style="font-size:1.6rem;font-weight:800;color:#9a3412">{{ $stats->high }}</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="stat-box" style="background:#fef9c3;border:1px solid #fde047">
                        <div style="font-size:.7rem;color:#854d0e;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Medium</div>
                        <div style="font-size:1.6rem;font-weight:800;color:#854d0e">{{ $stats->medium }}</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="stat-box" style="background:#f1f5f9;border:1px solid #cbd5e1">
                        <div style="font-size:.7rem;color:#475569;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Low</div>
                        <div style="font-size:1.6rem;font-weight:800;color:#475569">{{ $stats->low }}</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="stat-box" style="background:#eff6ff;border:1px solid #bfdbfe">
                        <div style="font-size:.7rem;color:#1e40af;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Hosts</div>
                        <div style="font-size:1.6rem;font-weight:800;color:#1e40af">{{ $activeHostCount }}</div>
                        <div style="font-size:.72rem;color:#94a3b8">unique IPs</div>
                    </div>
                </div>
            </div>
            @else
            <div class="va-card" style="text-align:center;padding:3rem;color:#94a3b8">
                <i class="bi bi-cloud-upload" style="font-size:2.5rem;display:block;margin-bottom:1rem;opacity:.4"></i>
                <div style="font-weight:600;margin-bottom:.5rem">No scan data yet</div>
                <button class="btn btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#uploadModal"
                    style="background:rgb(152,194,10);color:#fff;border-radius:9px;font-weight:600;border:none">
                    <i class="bi bi-upload me-1"></i> Upload First Scan
                </button>
            </div>
            @endif
        </div>

        {{-- Remediation Progress --}}
        @if($remStats && $remStats->total > 0)
        <div class="col-lg-6">
            <div class="va-card">
                <h6><i class="bi bi-check2-circle me-2"></i>Remediation Progress</h6>
                @php
                    $pct = $remStats->total > 0 ? round(($remStats->resolved + $remStats->accepted) / $remStats->total * 100) : 0;
                @endphp
                <div style="margin-bottom:1rem">
                    <div class="d-flex justify-content-between" style="font-size:.8rem;margin-bottom:.35rem">
                        <span style="font-weight:600;color:#374151">Overall Closure Rate</span>
                        <span style="font-weight:700;color:rgb(118,151,7)">{{ $pct }}%</span>
                    </div>
                    <div class="progress" style="background:#e8f5c2">
                        <div class="progress-bar" style="width:{{ $pct }}%;background:rgb(152,194,10);border-radius:20px"></div>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <div style="font-size:.75rem;color:#94a3b8;font-weight:600">Open</div>
                        <div style="font-size:1.2rem;font-weight:700;color:#dc2626">{{ $remStats->open_count }}</div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:.75rem;color:#94a3b8;font-weight:600">In Progress</div>
                        <div style="font-size:1.2rem;font-weight:700;color:#d97706">{{ $remStats->in_progress }}</div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:.75rem;color:#94a3b8;font-weight:600">Resolved</div>
                        <div style="font-size:1.2rem;font-weight:700;color:#059669">{{ $remStats->resolved }}</div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:.75rem;color:#94a3b8;font-weight:600">Accepted Risk</div>
                        <div style="font-size:1.2rem;font-weight:700;color:#64748b">{{ $remStats->accepted }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Top Vulnerable IPs --}}
        @if($topIps->count())
        <div class="col-lg-6">
            <div class="va-card">
                <h6><i class="bi bi-hdd-network me-2"></i>Top Vulnerable IPs</h6>
                @foreach($topIps as $ip)
                @php
                    $pctIp  = $stats->total > 0 ? round($ip->total / $stats->total * 100) : 0;
                    // Fetch one representative finding for OS/hostname
                    $hostInfo = \App\Models\VulnFinding::where('scan_id', $activeScan->id)
                        ->where('ip_address', $ip->ip_address)
                        ->whereNotNull('os_detected')
                        ->select('hostname','os_detected')
                        ->first()
                        ?? \App\Models\VulnFinding::where('scan_id', $activeScan->id)
                            ->where('ip_address', $ip->ip_address)
                            ->select('hostname','os_detected')
                            ->first();
                    $osLower  = strtolower($hostInfo?->os_detected ?? '');
                    $osIcon   = str_contains($osLower, 'windows') ? 'bi-windows'
                              : (preg_match('/linux|ubuntu|centos|debian|redhat/', $osLower) ? 'bi-ubuntu'
                              : (str_contains($osLower, 'cisco') ? 'bi-router' : 'bi-cpu'));
                @endphp
                <div style="margin-bottom:.9rem">
                    <div class="d-flex justify-content-between align-items-start" style="font-size:.82rem;margin-bottom:.3rem">
                        <div>
                            <div style="font-family:monospace;font-weight:700;color:#0f172a">{{ $ip->ip_address }}</div>
                            @if($hostInfo?->hostname)
                            <div style="font-size:.72rem;color:#64748b">{{ $hostInfo->hostname }}</div>
                            @endif
                            @if($hostInfo?->os_detected)
                            <div style="font-size:.68rem;background:#f1f5f9;color:#475569;padding:.1rem .4rem;border-radius:5px;display:inline-flex;align-items:center;gap:.25rem;margin-top:.2rem">
                                <i class="bi {{ $osIcon }}" style="font-size:.7rem"></i> {{ Str::limit($hostInfo->os_detected, 30) }}
                            </div>
                            @endif
                        </div>
                        <div class="text-end">
                            @if($ip->critical > 0)<span style="font-size:.7rem;background:#fee2e2;color:#991b1b;border-radius:12px;padding:.1rem .5rem;font-weight:700;display:block;margin-bottom:2px">C:{{ $ip->critical }}</span>@endif
                            @if($ip->high > 0)<span style="font-size:.7rem;background:#ffedd5;color:#9a3412;border-radius:12px;padding:.1rem .5rem;font-weight:700;display:block">H:{{ $ip->high }}</span>@endif
                            <span style="font-size:.72rem;color:#64748b">{{ $ip->total }} total</span>
                        </div>
                    </div>
                    <div class="progress" style="background:#f1f5f9">
                        <div class="progress-bar" style="width:{{ $pctIp }}%;background:rgb(152,194,10);border-radius:20px"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Assessment Info --}}
        <div class="col-12">
            <div class="va-card">
                <h6><i class="bi bi-info-circle me-2"></i>Assessment Info</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Created By</div>
                        <div style="font-weight:600;color:#0f172a;font-size:.9rem">{{ $assessment->creator?->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Assessment Period</div>
                        <div style="font-weight:500;color:#374151;font-size:.88rem">
                            {{ $assessment->period_start?->format('d M Y') ?? '—' }}
                            @if($assessment->period_start || $assessment->period_end) &ndash; @endif
                            {{ $assessment->period_end?->format('d M Y') ?? '' }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Baseline Scan</div>
                        <div style="font-size:.85rem;color:#374151">{{ $baseline ? Str::limit($baseline->filename, 30) : '—' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Latest Scan</div>
                        <div style="font-size:.85rem;color:#374151">{{ $latestScan ? Str::limit($latestScan->filename, 30) : '—' }}</div>
                    </div>
                    @if($assessment->description)
                    <div class="col-12">
                        <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.4rem">Description</div>
                        <div style="font-size:.875rem;color:#374151;line-height:1.6">{{ $assessment->description }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ── TAB: Comparison ── --}}
@if($comparison)
<div class="tab-pane fade" id="tab-comparison">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="cmp-box" style="background:#fee2e2;border:1px solid #fca5a5">
                <div style="font-size:.72rem;color:#991b1b;font-weight:700;text-transform:uppercase;letter-spacing:.5px">New Vulnerabilities</div>
                <div style="font-size:2.2rem;font-weight:800;color:#991b1b;line-height:1.2">{{ $comparison['new'] }}</div>
                <div style="font-size:.78rem;color:#b91c1c">Exist in latest but not baseline</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cmp-box" style="background:#d1fae5;border:1px solid #6ee7b7">
                <div style="font-size:.72rem;color:#065f46;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Resolved</div>
                <div style="font-size:2.2rem;font-weight:800;color:#065f46;line-height:1.2">{{ $comparison['resolved'] }}</div>
                <div style="font-size:.78rem;color:#047857">Exist in baseline but not latest</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cmp-box" style="background:#fef9c3;border:1px solid #fde047">
                <div style="font-size:.72rem;color:#854d0e;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Persistent</div>
                <div style="font-size:2.2rem;font-weight:800;color:#854d0e;line-height:1.2">{{ $comparison['persistent'] }}</div>
                <div style="font-size:.78rem;color:#92400e">Exist in both scans</div>
            </div>
        </div>
    </div>

    {{-- Host tracking comparison --}}
    @if($hostComparison)
    <div class="va-card" style="margin-bottom:1rem">
        <h6><i class="bi bi-hdd-network me-2"></i>Host / IP Tracking</h6>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:.9rem 1rem;text-align:center">
                    <div style="font-size:.65rem;color:#1e40af;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Baseline Hosts</div>
                    <div style="font-size:1.8rem;font-weight:800;color:#1e40af;line-height:1.2">{{ $hostComparison['baseline_count'] }}</div>
                    <div style="font-size:.72rem;color:#94a3b8">unique IPs</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:.9rem 1rem;text-align:center">
                    <div style="font-size:.65rem;color:#1e40af;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Latest Hosts</div>
                    <div style="font-size:1.8rem;font-weight:800;color:#1e40af;line-height:1.2">{{ $hostComparison['latest_count'] }}</div>
                    <div style="font-size:.72rem;color:#94a3b8">unique IPs</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:.9rem 1rem;text-align:center">
                    <div style="font-size:.65rem;color:#991b1b;font-weight:700;text-transform:uppercase;letter-spacing:.5px">New IPs</div>
                    <div style="font-size:1.8rem;font-weight:800;color:#991b1b;line-height:1.2">{{ $hostComparison['new'] }}</div>
                    <div style="font-size:.72rem;color:#94a3b8">added in latest</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:10px;padding:.9rem 1rem;text-align:center">
                    <div style="font-size:.65rem;color:#065f46;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Removed IPs</div>
                    <div style="font-size:1.8rem;font-weight:800;color:#065f46;line-height:1.2">{{ $hostComparison['removed'] }}</div>
                    <div style="font-size:.72rem;color:#94a3b8">gone from latest</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div style="background:#fef9c3;border:1px solid #fde047;border-radius:10px;padding:.9rem 1rem;text-align:center">
                    <div style="font-size:.65rem;color:#854d0e;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Persistent</div>
                    <div style="font-size:1.8rem;font-weight:800;color:#854d0e;line-height:1.2">{{ $hostComparison['persistent'] }}</div>
                    <div style="font-size:.72rem;color:#94a3b8">in both scans</div>
                </div>
            </div>
        </div>

        {{-- New IP list --}}
        @if($hostComparison['new_ips']->isNotEmpty())
        <div class="mb-2">
            <div style="font-size:.75rem;font-weight:700;color:#991b1b;margin-bottom:.4rem">
                <i class="bi bi-plus-circle-fill me-1"></i> New IPs in latest scan
            </div>
            <div class="d-flex flex-wrap gap-1">
                @foreach($hostComparison['new_ips'] as $ip)
                <span style="font-family:monospace;font-size:.78rem;background:#fee2e2;color:#991b1b;border-radius:6px;padding:.15rem .55rem;border:1px solid #fca5a5">{{ $ip }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Removed IP list --}}
        @if($hostComparison['removed_ips']->isNotEmpty())
        <div>
            <div style="font-size:.75rem;font-weight:700;color:#065f46;margin-bottom:.4rem">
                <i class="bi bi-dash-circle-fill me-1"></i> IPs not present in latest scan
            </div>
            <div class="d-flex flex-wrap gap-1">
                @foreach($hostComparison['removed_ips'] as $ip)
                <span style="font-family:monospace;font-size:.78rem;background:#d1fae5;color:#065f46;border-radius:6px;padding:.15rem .55rem;border:1px solid #6ee7b7">{{ $ip }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    <div class="va-card" style="padding:.85rem 1.5rem">
        <div class="d-flex gap-3 align-items-center flex-wrap" style="font-size:.85rem">
            <div>
                <span style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Baseline</span><br>
                <span style="font-weight:600;color:#0f172a">{{ $baseline->filename }}</span>
                <span style="font-size:.75rem;color:#94a3b8;margin-left:.5rem">({{ $baseline->finding_count }} findings · {{ $baseline->created_at->format('d M Y') }})</span>
            </div>
            <i class="bi bi-arrow-right" style="color:#94a3b8;font-size:1.2rem"></i>
            <div>
                <span style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase">Latest</span><br>
                <span style="font-weight:600;color:#0f172a">{{ $latestScan->filename }}</span>
                <span style="font-size:.75rem;color:#94a3b8;margin-left:.5rem">({{ $latestScan->finding_count }} findings · {{ $latestScan->created_at->format('d M Y') }})</span>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-2">
        <a href="{{ route('vuln-assessments.findings', $assessment) }}" class="btn btn-sm"
            style="background:rgb(152,194,10);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.5rem 1.5rem">
            <i class="bi bi-table me-1"></i> View All Findings with Comparison Labels
        </a>
    </div>
</div>
@endif

{{-- ── TAB: OS Distribution ── --}}
@if($osHostCount > 0)
<div class="tab-pane fade" id="tab-os">
    @php
        $totalOsHosts = $osDistribution->sum('cnt');
        $familyMeta = [
            'Windows' => ['icon' => 'bi-windows',       'bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Windows Component'],
            'Linux'   => ['icon' => 'bi-ubuntu',        'bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Linux OS Component'],
            'Unix'    => ['icon' => 'bi-terminal-fill', 'bg' => '#ffedd5', 'color' => '#7c2d12', 'label' => 'Unix-based OS'],
            'Other'   => ['icon' => 'bi-cpu-fill',      'bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'Other'],
        ];
    @endphp

    {{-- Distribution cards --}}
    <div class="row g-2 mb-3">
        @foreach($osDistribution as $dist)
        @php $meta = $familyMeta[$dist->family] ?? $familyMeta['Other']; $pct = $totalOsHosts > 0 ? round($dist->cnt / $totalOsHosts * 100) : 0; @endphp
        <div class="col-md-3 col-6">
            <div class="va-card" style="border-color:{{ $meta['bg'] }};padding:1rem 1.25rem;text-align:center">
                <div style="font-size:1.3rem;margin-bottom:.4rem;color:{{ $meta['color'] }}">
                    <i class="bi {{ $meta['icon'] }}"></i>
                </div>
                <div style="font-size:1.6rem;font-weight:800;color:{{ $meta['color'] }}">{{ $dist->cnt }}</div>
                <div style="font-size:.72rem;font-weight:700;color:{{ $meta['color'] }};text-transform:uppercase;letter-spacing:.5px">{{ $meta['label'] }}</div>
                <div style="font-size:.78rem;color:#94a3b8;margin-top:.15rem">{{ $pct }}% of hosts</div>
                {{-- Mini bar --}}
                <div style="height:5px;background:#f1f5f9;border-radius:20px;margin-top:.6rem;overflow:hidden">
                    <div style="height:100%;width:{{ $pct }}%;background:{{ $meta['color'] }};border-radius:20px"></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- OS Name breakdown --}}
    <div class="va-card">
        <h6><i class="bi bi-list-ul me-2"></i>OS Name Breakdown</h6>
        @php
            $osNames = \App\Models\VulnHostOs::where('assessment_id', $assessment->id)
                ->selectRaw("COALESCE(os_override, os_name, 'Unknown') as name, COALESCE(os_override_family, os_family) as family, COUNT(*) as cnt")
                ->groupBy('name', 'family')
                ->orderByDesc('cnt')
                ->limit(20)
                ->get();
        @endphp
        <div class="row g-2">
            @foreach($osNames as $row)
            @php $meta = $familyMeta[$row->family] ?? $familyMeta['Other']; $pct = $totalOsHosts > 0 ? round($row->cnt / $totalOsHosts * 100) : 0; @endphp
            <div class="col-md-6">
                <div style="display:flex;align-items:center;gap:.6rem;padding:.4rem .6rem;border-radius:8px;background:#f8fafc">
                    <span style="min-width:22px;height:22px;border-radius:6px;background:{{ $meta['bg'] }};display:flex;align-items:center;justify-content:center">
                        <i class="bi {{ $meta['icon'] }}" style="font-size:.7rem;color:{{ $meta['color'] }}"></i>
                    </span>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:.78rem;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $row->name }}</div>
                        <div style="height:3px;background:#e2e8f0;border-radius:10px;margin-top:.2rem;overflow:hidden">
                            <div style="height:100%;width:{{ $pct }}%;background:{{ $meta['color'] }};border-radius:10px"></div>
                        </div>
                    </div>
                    <span style="font-size:.78rem;font-weight:700;color:{{ $meta['color'] }};min-width:24px;text-align:right">{{ $row->cnt }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="text-center mt-2">
        <a href="{{ route('vuln-assessments.os-assets', $assessment) }}" class="btn btn-sm"
            style="background:rgb(152,194,10);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.5rem 1.5rem">
            <i class="bi bi-cpu me-1"></i> View Full OS Assets List
        </a>
    </div>
</div>
@endif

{{-- ── TAB: Scans ── --}}
<div class="tab-pane fade" id="tab-scans">
    @forelse($assessment->scans as $scan)
    <div class="scan-item">
        <div style="flex:1">
            <div style="font-weight:600;color:#0f172a;font-size:.88rem">
                {{ $scan->filename }}
                @if($scan->is_baseline)
                    <span class="scan-baseline-badge ms-2">Baseline</span>
                @else
                    <span class="scan-latest-badge ms-2">Latest</span>
                @endif
            </div>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:.2rem">
                {{ $scan->finding_count }} findings
                &middot;
                <span style="color:#1e40af;font-weight:600">
                    <i class="bi bi-hdd-network" style="font-size:.7rem"></i> {{ $scan->host_count }} host{{ $scan->host_count !== 1 ? 's' : '' }}
                </span>
                &middot; Uploaded {{ $scan->created_at->format('d M Y, H:i') }}
                @if($scan->creator) &middot; by {{ $scan->creator->name }} @endif
                @if($scan->notes) &middot; {{ $scan->notes }} @endif
            </div>
        </div>
        <div style="font-size:1.4rem;color:{{ $scan->is_baseline ? 'rgb(152,194,10)' : '#60a5fa' }}">
            <i class="bi bi-file-earmark-bar-graph"></i>
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
            style="background:rgb(152,194,10);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.2rem">
            <i class="bi bi-upload me-1"></i> Upload {{ $assessment->scans->count() === 0 ? 'Baseline' : 'New' }} Scan
        </button>
    </div>
</div>

</div>{{-- /tab-content --}}

{{-- Upload Modal --}}
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
            <div class="modal-header" style="border-bottom:2px solid rgb(152,194,10);padding:1rem 1.5rem">
                <h5 class="modal-title" style="font-size:.95rem;font-weight:700;color:#0f172a">
                    <i class="bi bi-upload me-2" style="color:rgb(152,194,10)"></i>
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
                    <div class="alert" style="background:#e8f5c2;border:1px solid #c8e87a;border-radius:9px;font-size:.83rem;color:rgb(118,151,7);padding:.65rem 1rem">
                        <i class="bi bi-info-circle me-1"></i>
                        The first upload will be set as the <strong>Baseline Scan</strong>. Subsequent uploads will be treated as the Latest Scan for comparison.
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151">Scan File <span style="color:#dc2626">*</span></label>
                        <input type="file" name="scan_file" class="form-control" accept=".xml,.nessus,.csv,.txt" required style="border-radius:8px;font-size:.875rem">
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:.4rem">
                            Supported: <strong>.nessus</strong> (XML), <strong>.csv</strong> (Tenable export). Max 50 MB.
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional notes about this scan" style="border-radius:8px;font-size:.875rem">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.75rem 1.5rem">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                        style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff;font-weight:500">Cancel</button>
                    <button type="submit" class="btn btn-sm"
                        style="background:rgb(152,194,10);color:#fff;border-radius:8px;font-weight:600;border:none;padding:.45rem 1.2rem">
                        <i class="bi bi-cloud-upload me-1"></i> Import Scan
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
