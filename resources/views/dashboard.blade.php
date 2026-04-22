@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-muted: rgb(232,244,195); }

    .dash-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; }
    .stat-card  { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.25rem 1.4rem;
                  display:flex; align-items:center; gap:1rem; transition:box-shadow .15s; }
    .stat-card:hover { box-shadow:0 4px 18px rgba(118,151,7,.12); }
    .stat-icon  { width:46px; height:46px; border-radius:12px; display:flex; align-items:center;
                  justify-content:center; font-size:1.15rem; flex-shrink:0; }
    .stat-label { font-size:.7rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px; }
    .stat-value { font-size:1.6rem; font-weight:800; color:#0f172a; line-height:1.1; }
    .stat-sub   { font-size:.74rem; font-weight:600; margin-top:.15rem; }

    .section-title {
        font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.7px;
        color:var(--lime-dark); padding-bottom:.4rem; border-bottom:2px solid var(--lime);
        display:flex; align-items:center; gap:.4rem; margin-bottom:1rem;
    }
    .sev-c { background:#fee2e2; color:#991b1b; }
    .sev-h { background:#ffedd5; color:#9a3412; }
    .sev-m { background:#fef9c3; color:#854d0e; }
    .sev-l { background:#f1f5f9; color:#475569; }
    .sev-badge { padding:.18rem .6rem; border-radius:20px; font-size:.68rem; font-weight:700; display:inline-block; white-space:nowrap; }

    .assess-row { display:flex; align-items:center; gap:.75rem; padding:.6rem .85rem;
        border:1px solid #e8f5c2; border-radius:10px; transition:background .12s; }
    .assess-row:hover { background:var(--lime-muted); }

    .rem-bar-wrap { height:8px; border-radius:99px; background:#f1f5f9; overflow:hidden; display:flex; gap:1px; }
</style>

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4>Dashboard</h4>
        <p>Welcome back, <strong>{{ auth()->user()->name }}</strong> — {{ now()->format('l, d M Y') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('vuln-assessments.create') }}" class="btn btn-sm"
            style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.1rem">
            <i class="bi bi-plus-lg me-1"></i>New Assessment
        </a>
    </div>
</div>

{{-- ── Stat cards ────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    {{-- Total Assessments --}}
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('vuln-assessments.index') }}" style="text-decoration:none">
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lime-muted)">
                <i class="bi bi-clipboard2-pulse-fill" style="color:var(--lime-dark)"></i>
            </div>
            <div>
                <div class="stat-label">Assessments</div>
                <div class="stat-value">{{ $totalAssessments }}</div>
                <div class="stat-sub" style="color:var(--lime-dark)">Total conducted</div>
            </div>
        </div>
        </a>
    </div>

    {{-- Open Findings --}}
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2">
                <i class="bi bi-bug-fill" style="color:#dc2626"></i>
            </div>
            <div>
                <div class="stat-label">Open Findings</div>
                <div class="stat-value">{{ number_format($openFindings) }}</div>
                <div class="stat-sub" style="color:#dc2626">
                    <i class="bi bi-exclamation-octagon-fill me-1"></i>{{ number_format($criticalHighOpen) }} Critical / High
                </div>
            </div>
        </div>
    </div>

    {{-- Remediation Progress --}}
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5">
                <i class="bi bi-patch-check-fill" style="color:#059669"></i>
            </div>
            <div>
                <div class="stat-label">Remediated</div>
                <div class="stat-value">{{ number_format($resolvedCount) }}</div>
                <div class="stat-sub" style="color:#059669">
                    {{ $resolvedPct }}% of total remediations
                </div>
            </div>
        </div>
    </div>

    {{-- Users --}}
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('users.index') }}" style="text-decoration:none">
        <div class="stat-card">
            <div class="stat-icon" style="background:#ede9fe">
                <i class="bi bi-people-fill" style="color:#7c3aed"></i>
            </div>
            <div>
                <div class="stat-label">Users</div>
                <div class="stat-value">{{ $totalUsers }}</div>
                <div class="stat-sub" style="color:#7c3aed">System accounts</div>
            </div>
        </div>
        </a>
    </div>
</div>

{{-- ── Main grid ─────────────────────────────────────────────────────────── --}}
<div class="row g-3">

    {{-- Left col --}}
    <div class="col-lg-8">

        {{-- Severity breakdown --}}
        <div class="dash-card p-4 mb-3">
            <div class="section-title"><i class="bi bi-bar-chart-fill"></i>Open Findings by Severity</div>
            @php
                $sevMeta = [
                    'Critical' => ['sev-c','bi-exclamation-octagon-fill'],
                    'High'     => ['sev-h','bi-exclamation-triangle-fill'],
                    'Medium'   => ['sev-m','bi-dash-circle-fill'],
                    'Low'      => ['sev-l','bi-info-circle-fill'],
                ];
                $maxSev = max(1, $severityCounts->max() ?? 1);
            @endphp
            <div style="display:flex;flex-direction:column;gap:.65rem">
                @foreach($sevMeta as $sev => [$cls, $icon])
                @php $cnt = $severityCounts[$sev] ?? 0; $pct = round(($cnt/$maxSev)*100); @endphp
                <div class="d-flex align-items-center gap-3">
                    <span class="sev-badge {{ $cls }}" style="width:72px;text-align:center;flex-shrink:0">
                        <i class="bi {{ $icon }} me-1" style="font-size:.65rem"></i>{{ $sev }}
                    </span>
                    <div style="flex:1;height:10px;border-radius:99px;background:#f1f5f9;overflow:hidden">
                        <div style="height:100%;border-radius:99px;width:{{ $pct }}%;transition:width .4s;
                            background:{{ $sev==='Critical'?'#ef4444':($sev==='High'?'#f97316':($sev==='Medium'?'#eab308':'#94a3b8')) }}">
                        </div>
                    </div>
                    <span style="font-size:.82rem;font-weight:700;color:#0f172a;min-width:36px;text-align:right">{{ number_format($cnt) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Remediation status strip --}}
        <div class="dash-card p-4 mb-3">
            <div class="section-title"><i class="bi bi-clipboard2-check-fill"></i>Remediation Status</div>
            @php
                $remItems = [
                    ['Open',          $openRemCount,    '#fee2e2','#991b1b','#ef4444'],
                    ['In Progress',   $inProgressCount, '#fef9c3','#854d0e','#eab308'],
                    ['Resolved',      $resolvedCount,   '#d1fae5','#065f46','#22c55e'],
                    ['Accepted Risk', $acceptedCount,   '#f1f5f9','#475569','#94a3b8'],
                ];
            @endphp
            <div class="row g-2 mb-3">
                @foreach($remItems as [$label,$cnt,$bg,$col,$bar])
                <div class="col-6 col-md-3">
                    <div style="background:{{ $bg }};border-radius:10px;padding:.75rem .9rem;text-align:center">
                        <div style="font-size:1.3rem;font-weight:800;color:{{ $col }}">{{ number_format($cnt) }}</div>
                        <div style="font-size:.7rem;font-weight:700;color:{{ $col }};text-transform:uppercase;letter-spacing:.4px">{{ $label }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @if($totalRem > 0)
            <div class="rem-bar-wrap">
                @foreach($remItems as [$label,$cnt,$bg,$col,$bar])
                <div style="width:{{ round(($cnt/$totalRem)*100) }}%;background:{{ $bar }};min-width:{{ $cnt>0?'2px':'0' }}"></div>
                @endforeach
            </div>
            <div style="font-size:.72rem;color:#94a3b8;margin-top:.4rem">{{ $resolvedPct }}% remediated of {{ number_format($totalRem) }} total</div>
            @endif
        </div>

        {{-- Recent assessments --}}
        <div class="dash-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="section-title mb-0"><i class="bi bi-clipboard2-pulse-fill"></i>Recent Assessments</div>
                <a href="{{ route('vuln-assessments.index') }}"
                    style="font-size:.78rem;color:var(--lime-dark);font-weight:600;text-decoration:none">
                    View all <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            @forelse($recentAssessments as $a)
            @php
                $sevs = $sevByAssessment[$a->id] ?? collect();
                $envColors = ['Production'=>['#fee2e2','#991b1b'],'UAT'=>['#fef9c3','#854d0e'],'Internal'=>['#dbeafe','#1e40af'],'Development'=>['#f1f5f9','#475569']];
                [$envBg,$envCol] = $envColors[$a->environment ?? 'Production'] ?? ['#f1f5f9','#475569'];
            @endphp
            <div class="assess-row mb-2">
                <div style="width:34px;height:34px;border-radius:9px;background:var(--lime-muted);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-clipboard2-pulse-fill" style="color:var(--lime-dark);font-size:.85rem"></i>
                </div>
                <div style="flex:1;min-width:0">
                    <a href="{{ route('vuln-assessments.show', $a) }}"
                        style="font-weight:600;color:#0f172a;font-size:.85rem;text-decoration:none;
                               display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                        title="{{ $a->name }}">{{ $a->name }}</a>
                    <div style="font-size:.7rem;color:#94a3b8;margin-top:.1rem">
                        <i class="bi bi-person me-1"></i>{{ $a->creator?->name ?? '—' }}
                        &nbsp;·&nbsp;
                        <i class="bi bi-calendar3 me-1"></i>{{ $a->created_at->format('d M Y') }}
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1 flex-shrink-0 flex-wrap justify-content-end">
                    <span style="font-size:.65rem;font-weight:700;background:{{ $envBg }};color:{{ $envCol }};border-radius:20px;padding:.1rem .45rem">
                        {{ $a->environment ?? '—' }}
                    </span>
                    @foreach(['Critical'=>'sev-c','High'=>'sev-h','Medium'=>'sev-m','Low'=>'sev-l'] as $s=>$cls)
                    @php $sc = $sevs->firstWhere('severity',$s)?->cnt ?? 0; @endphp
                    @if($sc > 0)
                    <span class="sev-badge {{ $cls }}" style="font-size:.62rem;padding:.1rem .4rem">{{ $sc }}</span>
                    @endif
                    @endforeach
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:2rem;color:#94a3b8;font-size:.85rem">
                <i class="bi bi-clipboard2" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3;color:var(--lime)"></i>
                No assessments yet.
                <a href="{{ route('vuln-assessments.create') }}" style="color:var(--lime-dark);font-weight:600">Create one</a>
            </div>
            @endforelse
        </div>

    </div>

    {{-- Right col --}}
    <div class="col-lg-4">

        {{-- SLA card --}}
        @if($defaultSla)
        <div class="dash-card p-4 mb-3">
            <div class="section-title"><i class="bi bi-stopwatch-fill"></i>SLA Status</div>
            <div style="text-align:center;padding:.5rem 0 .75rem">
                <div style="font-size:2.4rem;font-weight:800;color:{{ $slaBreached>0?'#dc2626':'#22c55e' }};line-height:1">
                    {{ number_format($slaBreached) }}
                </div>
                <div style="font-size:.78rem;font-weight:600;color:{{ $slaBreached>0?'#dc2626':'#059669' }};margin-top:.25rem">
                    {{ $slaBreached > 0 ? 'Findings breached SLA' : 'No SLA breaches' }}
                </div>
            </div>
            <div style="background:#f8fafc;border-radius:8px;padding:.6rem .85rem;font-size:.74rem;color:#64748b">
                <div style="font-weight:700;margin-bottom:.3rem;color:#374151">
                    <i class="bi bi-stopwatch me-1" style="color:var(--lime-dark)"></i>{{ $defaultSla->name }}
                </div>
                <div class="d-flex flex-wrap gap-1">
                    @foreach(['Critical'=>$defaultSla->critical_days,'High'=>$defaultSla->high_days,'Medium'=>$defaultSla->medium_days,'Low'=>$defaultSla->low_days] as $s=>$d)
                    <span class="sev-badge {{ ['Critical'=>'sev-c','High'=>'sev-h','Medium'=>'sev-m','Low'=>'sev-l'][$s] }}"
                        style="font-size:.62rem">{{ $s[0] }}: {{ $d }}d</span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Top vulnerabilities --}}
        <div class="dash-card p-4 mb-3">
            <div class="section-title"><i class="bi bi-exclamation-octagon-fill"></i>Top Open Vulnerabilities</div>
            @forelse($topVulns as $v)
            @php $vc = $v->severity === 'Critical' ? 'sev-c' : 'sev-h'; @endphp
            <div style="display:flex;align-items:center;gap:.6rem;padding:.5rem 0;border-bottom:1px solid #f1f5f9">
                <span class="sev-badge {{ $vc }}" style="font-size:.62rem;flex-shrink:0">{{ $v->severity }}</span>
                <div style="flex:1;min-width:0;font-size:.78rem;color:#374151;font-weight:500;
                    overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $v->vuln_name }}">
                    {{ $v->vuln_name }}
                </div>
                <span style="font-size:.7rem;font-weight:700;color:#94a3b8;flex-shrink:0;white-space:nowrap">
                    {{ $v->host_count }} host{{ $v->host_count != 1 ? 's' : '' }}
                </span>
            </div>
            @empty
            <div style="text-align:center;padding:1.5rem;color:#94a3b8;font-size:.82rem">No open findings.</div>
            @endforelse
        </div>

        {{-- Quick actions --}}
        <div class="dash-card p-4">
            <div class="section-title"><i class="bi bi-lightning-fill"></i>Quick Actions</div>
            <div class="d-grid gap-2">
                <a href="{{ route('vuln-assessments.create') }}"
                    class="btn btn-sm text-start d-flex align-items-center gap-2 py-2 px-3"
                    style="background:#f8fafc;border:1.5px solid #e8f5c2;border-radius:9px;font-size:.84rem;font-weight:500;color:#374151;text-decoration:none">
                    <i class="bi bi-plus-circle-fill" style="color:var(--lime-dark)"></i>New Assessment
                </a>
                <a href="{{ route('assessment-scope.index') }}"
                    class="btn btn-sm text-start d-flex align-items-center gap-2 py-2 px-3"
                    style="background:#f8fafc;border:1.5px solid #e8f5c2;border-radius:9px;font-size:.84rem;font-weight:500;color:#374151;text-decoration:none">
                    <i class="bi bi-diagram-3-fill" style="color:var(--lime-dark)"></i>Manage Scope
                </a>
                <a href="{{ route('sla-policies.index') }}"
                    class="btn btn-sm text-start d-flex align-items-center gap-2 py-2 px-3"
                    style="background:#f8fafc;border:1.5px solid #e8f5c2;border-radius:9px;font-size:.84rem;font-weight:500;color:#374151;text-decoration:none">
                    <i class="bi bi-stopwatch-fill" style="color:var(--lime-dark)"></i>SLA Policies
                </a>
                @can('viewAny', App\Models\User::class)
                <a href="{{ route('users.index') }}"
                    class="btn btn-sm text-start d-flex align-items-center gap-2 py-2 px-3"
                    style="background:#f8fafc;border:1.5px solid #e8f5c2;border-radius:9px;font-size:.84rem;font-weight:500;color:#374151;text-decoration:none">
                    <i class="bi bi-people-fill" style="color:var(--lime-dark)"></i>Manage Users
                </a>
                @endcan
                <a href="{{ route('account.settings') }}"
                    class="btn btn-sm text-start d-flex align-items-center gap-2 py-2 px-3"
                    style="background:#f8fafc;border:1.5px solid #e8f5c2;border-radius:9px;font-size:.84rem;font-weight:500;color:#374151;text-decoration:none">
                    <i class="bi bi-gear-fill" style="color:var(--lime-dark)"></i>Settings
                </a>
            </div>
        </div>

    </div>
</div>

@endsection
