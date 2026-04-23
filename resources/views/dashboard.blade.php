@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<style>
    :root { --lime: var(--primary); --lime-dark: var(--primary-dark); --lime-muted: color-mix(in srgb,var(--primary) 14%,white); }

    .dash-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; }
    .stat-card  { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.25rem 1.4rem;
                  display:flex; align-items:center; gap:1rem; transition:box-shadow .15s; height:100%; }
    .stat-card:hover { box-shadow:0 4px 18px rgba(var(--primary-rgb),.12); }
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

    /* ── Customize mode ── */
    .widget-wrap { position:relative; }
    .edit-mode .widget-wrap { cursor:grab; }
    .edit-mode .widget-wrap:active { cursor:grabbing; }
    .edit-mode .dash-card,
    .edit-mode .stat-card {
        outline:2px dashed rgba(var(--primary-rgb),.35);
        outline-offset:2px;
    }
    .widget-toolbar {
        display:none;
        position:absolute; top:-10px; right:-6px; z-index:10;
        gap:4px;
    }
    .edit-mode .widget-toolbar { display:flex; }
    .widget-btn {
        width:26px; height:26px; border-radius:6px; border:none; cursor:pointer;
        display:flex; align-items:center; justify-content:center; font-size:.72rem;
        transition:all .15s;
    }
    .widget-btn-hide  { background:#fee2e2; color:#dc2626; }
    .widget-btn-drag  { background:var(--lime-muted); color:var(--lime-dark); cursor:grab; }
    .widget-btn-drag:active { cursor:grabbing; }
    .drag-ghost { opacity:.4; }

    /* panel pill that shows hidden widget names */
    .hidden-pill {
        display:inline-flex; align-items:center; gap:.4rem;
        background:#f8fafc; border:1.5px dashed #e2e8f0; border-radius:8px;
        padding:.3rem .7rem; font-size:.78rem; color:#64748b; cursor:pointer;
        transition:all .15s;
    }
    .hidden-pill:hover { border-color:var(--lime); color:var(--lime-dark); background:var(--lime-muted); }

    .sortable-chosen { box-shadow:0 8px 30px rgba(0,0,0,.12); }
</style>

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4>Dashboard</h4>
        <p>Welcome back, <strong>{{ auth()->user()->name }}</strong> — {{ now()->format('l, d M Y') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <button id="editBtn" onclick="toggleEdit()" class="btn btn-sm"
            style="border:1.5px solid var(--lime);color:var(--lime-dark);background:#fff;border-radius:9px;font-weight:600;padding:.42rem 1rem">
            <i class="bi bi-grid-1x2 me-1"></i>Customize
        </button>
        <button id="saveBtn" onclick="saveLayout()" class="btn btn-sm d-none"
            style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.42rem 1rem">
            <i class="bi bi-check-lg me-1"></i>Save Layout
        </button>
        <button id="resetBtn" onclick="resetLayout()" class="btn btn-sm d-none"
            style="border:1.5px solid #e2e8f0;color:#64748b;background:#fff;border-radius:9px;font-weight:600;padding:.42rem .9rem">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
        </button>
        <a href="{{ route('vuln-assessments.create') }}" class="btn btn-sm"
            style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.1rem">
            <i class="bi bi-plus-lg me-1"></i>New Assessment
        </a>
    </div>
</div>

{{-- Hidden widgets panel (shown in edit mode) --}}
<div id="hiddenPanel" class="d-none mb-3">
    <div style="background:#f8fafc;border:1.5px dashed #e2e8f0;border-radius:12px;padding:.85rem 1rem">
        <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.6rem">
            <i class="bi bi-eye-slash me-1"></i>Hidden Widgets — click to restore
        </div>
        <div id="hiddenList" class="d-flex flex-wrap gap-2"></div>
    </div>
</div>

{{-- ── Stat row ────────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-3" id="statsRow">
@php
    $statWidgets = collect($widgets)->filter(fn($w) => $w['size'] === 'stat');
@endphp
@foreach($statWidgets as $w)
<div class="col-sm-6 col-xl-3 widget-wrap" data-id="{{ $w['id'] }}" @if(!$w['visible']) style="display:none" @endif>
    <div class="widget-toolbar">
        <button class="widget-btn widget-btn-drag" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></button>
        <button class="widget-btn widget-btn-hide" title="Hide" onclick="hideWidget('{{ $w['id'] }}')"><i class="bi bi-x"></i></button>
    </div>
    @if($w['id'] === 'stat_assessments')
    <a href="{{ route('vuln-assessments.index') }}" style="text-decoration:none;display:block;height:100%">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--lime-muted)">
            <i class="bi bi-clipboard2-pulse-fill" style="color:var(--lime-dark)"></i>
        </div>
        <div>
            <div class="stat-label">Assessments</div>
            <div class="stat-value">{{ $totalAssessments }}</div>
            <div class="stat-sub" style="color:var(--lime-dark)">Total conducted</div>
        </div>
    </div></a>
    @elseif($w['id'] === 'stat_findings')
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2">
            <i class="bi bi-bug-fill" style="color:#dc2626"></i>
        </div>
        <div>
            <div class="stat-label">Open Findings</div>
            <div class="stat-value">{{ number_format($openFindings) }}</div>
            <div class="stat-sub" style="color:#dc2626">
                <i class="bi bi-exclamation-octagon-fill me-1"></i>{{ number_format($criticalHighOpen) }} Crit / High
            </div>
        </div>
    </div>
    @elseif($w['id'] === 'stat_remediated')
    <div class="stat-card">
        <div class="stat-icon" style="background:#d1fae5">
            <i class="bi bi-patch-check-fill" style="color:#059669"></i>
        </div>
        <div>
            <div class="stat-label">Remediated</div>
            <div class="stat-value">{{ number_format($resolvedCount) }}</div>
            <div class="stat-sub" style="color:#059669">{{ $resolvedPct }}% of total</div>
        </div>
    </div>
    @elseif($w['id'] === 'stat_users')
    <a href="{{ route('users.index') }}" style="text-decoration:none;display:block;height:100%">
    <div class="stat-card">
        <div class="stat-icon" style="background:#ede9fe">
            <i class="bi bi-people-fill" style="color:#7c3aed"></i>
        </div>
        <div>
            <div class="stat-label">Users</div>
            <div class="stat-value">{{ $totalUsers }}</div>
            <div class="stat-sub" style="color:#7c3aed">System accounts</div>
        </div>
    </div></a>
    @endif
</div>
@endforeach
</div>

{{-- ── Main grid ────────────────────────────────────────────────────────────── --}}
<div class="row g-3" id="mainGrid">

@php
    $mainWidgets = collect($widgets)->filter(fn($w) => $w['size'] !== 'stat');
@endphp

@foreach($mainWidgets as $w)

@if($w['id'] === 'severity_breakdown')
<div class="col-lg-8 widget-wrap" data-id="severity_breakdown" @if(!$w['visible']) style="display:none" @endif>
    <div class="widget-toolbar">
        <button class="widget-btn widget-btn-drag" title="Drag"><i class="bi bi-grip-vertical"></i></button>
        <button class="widget-btn widget-btn-hide" onclick="hideWidget('severity_breakdown')"><i class="bi bi-x"></i></button>
    </div>
    <div class="dash-card p-4 h-100">
        <div class="section-title"><i class="bi bi-bar-chart-fill"></i>Open Findings by Severity</div>
        @php
            $sevMeta = ['Critical'=>['sev-c','bi-exclamation-octagon-fill'],'High'=>['sev-h','bi-exclamation-triangle-fill'],'Medium'=>['sev-m','bi-dash-circle-fill'],'Low'=>['sev-l','bi-info-circle-fill']];
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
</div>

@elseif($w['id'] === 'remediation_status')
<div class="col-lg-8 widget-wrap" data-id="remediation_status" @if(!$w['visible']) style="display:none" @endif>
    <div class="widget-toolbar">
        <button class="widget-btn widget-btn-drag" title="Drag"><i class="bi bi-grip-vertical"></i></button>
        <button class="widget-btn widget-btn-hide" onclick="hideWidget('remediation_status')"><i class="bi bi-x"></i></button>
    </div>
    <div class="dash-card p-4 h-100">
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
</div>

@elseif($w['id'] === 'recent_assessments')
<div class="col-lg-8 widget-wrap" data-id="recent_assessments" @if(!$w['visible']) style="display:none" @endif>
    <div class="widget-toolbar">
        <button class="widget-btn widget-btn-drag" title="Drag"><i class="bi bi-grip-vertical"></i></button>
        <button class="widget-btn widget-btn-hide" onclick="hideWidget('recent_assessments')"><i class="bi bi-x"></i></button>
    </div>
    <div class="dash-card p-4 h-100">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="section-title mb-0"><i class="bi bi-clock-history"></i>Recent Assessments</div>
            <a href="{{ route('vuln-assessments.index') }}" style="font-size:.78rem;color:var(--lime-dark);font-weight:600;text-decoration:none">
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
                           display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $a->name }}</a>
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

@elseif($w['id'] === 'sla_status')
<div class="col-lg-4 widget-wrap" data-id="sla_status" @if(!$w['visible']) style="display:none" @endif>
    <div class="widget-toolbar">
        <button class="widget-btn widget-btn-drag" title="Drag"><i class="bi bi-grip-vertical"></i></button>
        <button class="widget-btn widget-btn-hide" onclick="hideWidget('sla_status')"><i class="bi bi-x"></i></button>
    </div>
    @if($defaultSla)
    <div class="dash-card p-4 h-100">
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
    @else
    <div class="dash-card p-4 h-100" style="display:flex;align-items:center;justify-content:center;text-align:center;color:#94a3b8;font-size:.85rem">
        <div><i class="bi bi-stopwatch" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3"></i>No default SLA policy set.</div>
    </div>
    @endif
</div>

@elseif($w['id'] === 'top_vulns')
<div class="col-lg-4 widget-wrap" data-id="top_vulns" @if(!$w['visible']) style="display:none" @endif>
    <div class="widget-toolbar">
        <button class="widget-btn widget-btn-drag" title="Drag"><i class="bi bi-grip-vertical"></i></button>
        <button class="widget-btn widget-btn-hide" onclick="hideWidget('top_vulns')"><i class="bi bi-x"></i></button>
    </div>
    <div class="dash-card p-4 h-100">
        <div class="section-title"><i class="bi bi-exclamation-octagon-fill"></i>Top Open Vulnerabilities</div>
        @forelse($topVulns as $v)
        @php $vc = $v->severity === 'Critical' ? 'sev-c' : 'sev-h'; @endphp
        <div style="display:flex;align-items:center;gap:.6rem;padding:.5rem 0;border-bottom:1px solid #f1f5f9">
            <span class="sev-badge {{ $vc }}" style="font-size:.62rem;flex-shrink:0">{{ $v->severity }}</span>
            <div style="flex:1;min-width:0;font-size:.78rem;color:#374151;font-weight:500;
                overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $v->vuln_name }}">
                {{ $v->vuln_name }}
            </div>
            <span style="font-size:.7rem;font-weight:700;color:#94a3b8;flex-shrink:0">{{ $v->host_count }}h</span>
        </div>
        @empty
        <div style="text-align:center;padding:1.5rem;color:#94a3b8;font-size:.82rem">No open findings.</div>
        @endforelse
    </div>
</div>

@elseif($w['id'] === 'quick_actions')
<div class="col-lg-4 widget-wrap" data-id="quick_actions" @if(!$w['visible']) style="display:none" @endif>
    <div class="widget-toolbar">
        <button class="widget-btn widget-btn-drag" title="Drag"><i class="bi bi-grip-vertical"></i></button>
        <button class="widget-btn widget-btn-hide" onclick="hideWidget('quick_actions')"><i class="bi bi-x"></i></button>
    </div>
    <div class="dash-card p-4 h-100">
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
@endif

@endforeach
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const WIDGETS = @json($widgets);
const DEFAULT_IDS = ['stat_assessments','stat_findings','stat_remediated','stat_users',
    'severity_breakdown','remediation_status','recent_assessments','sla_status','top_vulns','quick_actions'];

let editMode = false;
let statSort, mainSort;

// Build state from DOM
function getLayout() {
    const layout = [];
    // Stats row
    document.querySelectorAll('#statsRow .widget-wrap').forEach(el => {
        layout.push({ id: el.dataset.id, visible: el.style.display !== 'none' });
    });
    // Main grid
    document.querySelectorAll('#mainGrid .widget-wrap').forEach(el => {
        layout.push({ id: el.dataset.id, visible: el.style.display !== 'none' });
    });
    return layout;
}

function toggleEdit() {
    editMode = !editMode;
    document.body.classList.toggle('edit-mode', editMode);
    document.getElementById('editBtn').classList.toggle('d-none', editMode);
    document.getElementById('saveBtn').classList.toggle('d-none', !editMode);
    document.getElementById('resetBtn').classList.toggle('d-none', !editMode);
    document.getElementById('hiddenPanel').classList.toggle('d-none', !editMode);

    if (editMode) {
        renderHiddenList();
        initSortable();
    } else {
        if (statSort) { statSort.destroy(); statSort = null; }
        if (mainSort) { mainSort.destroy(); mainSort = null; }
    }
}

function initSortable() {
    statSort = Sortable.create(document.getElementById('statsRow'), {
        animation: 150, ghostClass: 'drag-ghost', chosenClass: 'sortable-chosen',
        handle: '.widget-btn-drag',
        onEnd: renderHiddenList,
    });
    mainSort = Sortable.create(document.getElementById('mainGrid'), {
        animation: 150, ghostClass: 'drag-ghost', chosenClass: 'sortable-chosen',
        handle: '.widget-btn-drag',
        onEnd: renderHiddenList,
    });
}

function hideWidget(id) {
    const el = document.querySelector(`.widget-wrap[data-id="${id}"]`);
    if (el) el.style.display = 'none';
    renderHiddenList();
}

function showWidget(id) {
    const el = document.querySelector(`.widget-wrap[data-id="${id}"]`);
    if (el) el.style.display = '';
    renderHiddenList();
}

function renderHiddenList() {
    const list = document.getElementById('hiddenList');
    list.innerHTML = '';
    document.querySelectorAll('.widget-wrap').forEach(el => {
        if (el.style.display === 'none') {
            const def = WIDGETS.find(w => w.id === el.dataset.id);
            if (!def) return;
            const pill = document.createElement('div');
            pill.className = 'hidden-pill';
            pill.innerHTML = `<i class="bi ${def.icon}"></i>${def.label}`;
            pill.onclick = () => showWidget(el.dataset.id);
            list.appendChild(pill);
        }
    });
    if (!list.children.length) {
        list.innerHTML = '<span style="font-size:.78rem;color:#94a3b8">All widgets are visible.</span>';
    }
}

async function saveLayout() {
    const layout = getLayout();
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Saving…';

    try {
        const res = await fetch('{{ route("dashboard.layout") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ layout }),
        });
        if (res.ok) {
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Saved!';
            btn.style.background = '#22c55e';
            setTimeout(() => {
                toggleEdit();
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Layout';
                btn.style.background = '';
            }, 900);
        }
    } catch(e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Layout';
    }
}

async function resetLayout() {
    if (!confirm('Reset dashboard to default layout?')) return;
    const layout = DEFAULT_IDS.map(id => ({ id, visible: true }));
    await fetch('{{ route("dashboard.layout") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ layout }),
    });
    location.reload();
}
</script>
@endpush
