@extends('layouts.app')
@section('title', 'VA Assessments')

@section('content')
<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-muted: rgb(232,244,195); }

    .assess-card {
        background: #fff;
        border: 1px solid #e8f5c2;
        border-radius: 14px;
        overflow: hidden;
        transition: box-shadow .18s, border-color .18s, transform .15s;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .assess-card:hover {
        box-shadow: 0 6px 28px rgba(118,151,7,.15);
        border-color: var(--lime);
        transform: translateY(-2px);
    }
    .assess-card-body { padding: 1.15rem 1.25rem; flex: 1; }
    .assess-card-footer {
        padding: .65rem 1.25rem;
        background: #fafcf5;
        border-top: 1px solid #eef5d6;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .sev-pill {
        display: inline-flex; align-items: center; gap: .25rem;
        padding: .18rem .55rem; border-radius: 20px;
        font-size: .68rem; font-weight: 700;
    }
    .sev-c { background:#fee2e2; color:#991b1b; }
    .sev-h { background:#ffedd5; color:#9a3412; }
    .sev-m { background:#fef9c3; color:#854d0e; }
    .sev-l { background:#f1f5f9; color:#475569; }

    .badge-env { padding:.18rem .6rem; border-radius:20px; font-size:.66rem; font-weight:700; }
    .env-production  { background:#fee2e2; color:#991b1b; }
    .env-uat         { background:#fef9c3; color:#854d0e; }
    .env-internal    { background:#e0f2fe; color:#0c4a6e; }
    .env-development { background:#f1f5f9; color:#475569; }

    .glance-card {
        background: #fff; border: 1px solid #e8f5c2; border-radius: 12px;
        padding: .9rem 1.2rem;
        display: flex; align-items: center; gap: .85rem;
    }
    .glance-icon {
        width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
    }
    .sev-bar { display:flex; height:5px; border-radius:10px; overflow:hidden; margin-top:.35rem; }
</style>

{{-- Page header --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-clipboard2-pulse-fill me-2" style="color:var(--lime)"></i>VA Assessments
        </h4>
        <p class="mb-0 mt-1" style="font-size:.84rem;color:#64748b">
            Track and manage vulnerability assessments across your environments.
        </p>
    </div>
    <a href="{{ route('vuln-assessments.create') }}" class="btn btn-sm"
        style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.1rem">
        <i class="bi bi-plus-lg me-1"></i> New Assessment
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" style="border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Global glance stats (2 queries across all assessments) --}}
@php
    $allIds = $assessments->pluck('id');
    $gStats = \App\Models\VulnTracked::whereIn('assessment_id', $allIds)
        ->selectRaw("
            SUM(CASE WHEN tracking_status IN ('New','Pending') THEN 1 ELSE 0 END)  as active_total,
            SUM(CASE WHEN tracking_status = 'Resolved'         THEN 1 ELSE 0 END)  as resolved_total
        ")->first();
    $gHosts = \App\Models\VulnTracked::whereIn('assessment_id', $allIds)
        ->whereIn('tracking_status', ['New','Pending'])
        ->distinct('ip_address')->count('ip_address');
@endphp
<div class="row g-2 mb-4">
    <div class="col-6 col-md-3">
        <div class="glance-card">
            <div class="glance-icon" style="background:#e8f5c2;color:var(--lime-dark)">
                <i class="bi bi-clipboard2-pulse"></i>
            </div>
            <div>
                <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px">Assessments</div>
                <div style="font-size:1.45rem;font-weight:800;color:#0f172a;line-height:1.2">{{ $assessments->total() }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glance-card" style="border-color:#fca5a5">
            <div class="glance-icon" style="background:#fee2e2;color:#dc2626">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div>
                <div style="font-size:.68rem;color:#991b1b;font-weight:600;text-transform:uppercase;letter-spacing:.4px">Active Findings</div>
                <div style="font-size:1.45rem;font-weight:800;color:#dc2626;line-height:1.2">{{ number_format($gStats->active_total ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glance-card" style="border-color:#bfdbfe">
            <div class="glance-icon" style="background:#eff6ff;color:#1d4ed8">
                <i class="bi bi-hdd-network"></i>
            </div>
            <div>
                <div style="font-size:.68rem;color:#1e40af;font-weight:600;text-transform:uppercase;letter-spacing:.4px">Hosts at Risk</div>
                <div style="font-size:1.45rem;font-weight:800;color:#1d4ed8;line-height:1.2">{{ number_format($gHosts) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glance-card" style="border-color:#6ee7b7">
            <div class="glance-icon" style="background:#d1fae5;color:#059669">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <div style="font-size:.68rem;color:#065f46;font-weight:600;text-transform:uppercase;letter-spacing:.4px">Resolved</div>
                <div style="font-size:1.45rem;font-weight:800;color:#059669;line-height:1.2">{{ number_format($gStats->resolved_total ?? 0) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Assessment cards --}}
<div class="row g-3">
    @forelse($assessments as $a)
    @php
        $tracked = \App\Models\VulnTracked::where('assessment_id', $a->id)
            ->whereIn('severity', ['Critical','High','Medium','Low'])
            ->selectRaw("
                SUM(CASE WHEN tracking_status IN ('New','Pending') THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN tracking_status = 'Resolved'         THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN severity='Critical' AND tracking_status IN ('New','Pending') THEN 1 ELSE 0 END) as c,
                SUM(CASE WHEN severity='High'     AND tracking_status IN ('New','Pending') THEN 1 ELSE 0 END) as h,
                SUM(CASE WHEN severity='Medium'   AND tracking_status IN ('New','Pending') THEN 1 ELSE 0 END) as m,
                SUM(CASE WHEN severity='Low'      AND tracking_status IN ('New','Pending') THEN 1 ELSE 0 END) as l
            ")->first();

        $activeCount   = (int)($tracked->active   ?? 0);
        $resolvedCount = (int)($tracked->resolved ?? 0);
        $pctClosed     = ($activeCount + $resolvedCount) > 0
                            ? round($resolvedCount / ($activeCount + $resolvedCount) * 100) : 0;
        $uniqueHosts   = \App\Models\VulnTracked::where('assessment_id', $a->id)
            ->whereIn('tracking_status', ['New','Pending'])
            ->distinct('ip_address')->count('ip_address');

        $sevTotal = max(1, ($tracked->c + $tracked->h + $tracked->m + $tracked->l));
        $barC = $tracked->c > 0 ? round($tracked->c / $sevTotal * 100) : 0;
        $barH = $tracked->h > 0 ? round($tracked->h / $sevTotal * 100) : 0;
        $barM = $tracked->m > 0 ? round($tracked->m / $sevTotal * 100) : 0;
        $barL = max(0, 100 - $barC - $barH - $barM);

        $accentColor = $tracked->c > 0 ? '#dc2626'
            : ($tracked->h > 0 ? '#ea580c'
            : ($tracked->m > 0 ? '#d97706'
            : ($tracked->l > 0 ? '#64748b' : 'var(--lime)')));
    @endphp
    <div class="col-md-6 col-xl-4">
        <div class="assess-card" style="border-left: 4px solid {{ $accentColor }}">

            {{-- Card body --}}
            <div class="assess-card-body">

                {{-- Title + env badge --}}
                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                    <div style="min-width:0">
                        <a href="{{ route('vuln-assessments.show', $a) }}"
                           style="font-weight:700;color:#0f172a;font-size:.95rem;text-decoration:none;
                                  display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                           title="{{ $a->name }}">{{ $a->name }}</a>
                        @if($a->description)
                        <div style="font-size:.73rem;color:#94a3b8;margin-top:.1rem;
                                    overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            {{ Str::limit($a->description, 72) }}
                        </div>
                        @endif
                    </div>
                    @if($a->environment)
                    <span class="badge-env env-{{ strtolower($a->environment) }} flex-shrink-0">{{ $a->environment }}</span>
                    @endif
                </div>

                {{-- Meta row --}}
                <div class="d-flex flex-wrap gap-3 mb-3" style="font-size:.74rem;color:#64748b">
                    @if($a->period_start || $a->period_end)
                    <span>
                        <i class="bi bi-calendar3 me-1"></i>
                        {{ $a->period_start?->format('d M Y') ?? '—' }} – {{ $a->period_end?->format('d M Y') ?? '—' }}
                    </span>
                    @endif
                    <span><i class="bi bi-cloud-upload me-1"></i>{{ $a->scans->count() }} scan{{ $a->scans->count() !== 1 ? 's' : '' }}</span>
                    @if($a->creator)<span><i class="bi bi-person me-1"></i>{{ $a->creator->name }}</span>@endif
                </div>

                {{-- Findings block --}}
                @if($activeCount > 0)
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px">
                            Active findings
                        </span>
                        <span style="font-size:.8rem;font-weight:800;color:{{ $accentColor }}">
                            {{ number_format($activeCount) }}
                        </span>
                    </div>

                    <div class="d-flex gap-1 flex-wrap mb-2">
                        @if($tracked->c > 0)
                        <span class="sev-pill sev-c"><i class="bi bi-circle-fill" style="font-size:.4rem"></i>C:{{ $tracked->c }}</span>
                        @endif
                        @if($tracked->h > 0)
                        <span class="sev-pill sev-h"><i class="bi bi-circle-fill" style="font-size:.4rem"></i>H:{{ $tracked->h }}</span>
                        @endif
                        @if($tracked->m > 0)
                        <span class="sev-pill sev-m"><i class="bi bi-circle-fill" style="font-size:.4rem"></i>M:{{ $tracked->m }}</span>
                        @endif
                        @if($tracked->l > 0)
                        <span class="sev-pill sev-l"><i class="bi bi-circle-fill" style="font-size:.4rem"></i>L:{{ $tracked->l }}</span>
                        @endif
                        @if($uniqueHosts > 0)
                        <span style="font-size:.68rem;color:#1e40af;background:#eff6ff;border-radius:20px;
                                     padding:.18rem .5rem;font-weight:600">
                            <i class="bi bi-hdd-network" style="font-size:.6rem"></i> {{ $uniqueHosts }} host{{ $uniqueHosts !== 1 ? 's' : '' }}
                        </span>
                        @endif
                    </div>

                    {{-- Severity bar --}}
                    <div class="sev-bar">
                        @if($barC > 0)<div style="width:{{$barC}}%;background:#dc2626"></div>@endif
                        @if($barH > 0)<div style="width:{{$barH}}%;background:#ea580c"></div>@endif
                        @if($barM > 0)<div style="width:{{$barM}}%;background:#d97706"></div>@endif
                        @if($barL > 0)<div style="width:{{$barL}}%;background:#94a3b8"></div>@endif
                    </div>

                    {{-- Closure progress --}}
                    @if($resolvedCount > 0)
                    <div style="margin-top:.7rem">
                        <div class="d-flex justify-content-between" style="font-size:.68rem;color:#94a3b8;margin-bottom:.2rem">
                            <span>Remediation closure</span>
                            <span style="font-weight:700;color:var(--lime-dark)">{{ $pctClosed }}%</span>
                        </div>
                        <div style="height:4px;background:#e8f5c2;border-radius:10px;overflow:hidden">
                            <div style="height:100%;width:{{ $pctClosed }}%;background:var(--lime);border-radius:10px"></div>
                        </div>
                    </div>
                    @endif
                </div>

                @elseif($a->scans->count() > 0)
                <div style="font-size:.8rem;color:#059669;padding:.4rem 0">
                    <i class="bi bi-check-circle-fill me-1"></i>No active findings
                </div>

                @else
                <div style="font-size:.8rem;color:#94a3b8;padding:.4rem 0">
                    <i class="bi bi-cloud-upload me-1"></i>No scans uploaded yet
                </div>
                @endif
            </div>

            {{-- Card footer --}}
            <div class="assess-card-footer">
                <a href="{{ route('vuln-assessments.show', $a) }}"
                   class="btn btn-sm flex-grow-1"
                   style="background:var(--lime);color:#fff;border-radius:8px;font-weight:600;
                          border:none;font-size:.8rem;padding:.35rem .9rem;text-align:center">
                    <i class="bi bi-arrow-right-circle me-1"></i>Open
                </a>
                <form method="POST" action="{{ route('vuln-assessments.destroy', $a) }}"
                      class="d-inline"
                      onsubmit="return confirm('Delete this assessment and all its data?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm"
                        style="border-radius:8px;border:1px solid #fca5a5;color:#dc2626;
                               background:#fff8f8;padding:.35rem .65rem;font-size:.8rem"
                        title="Delete assessment">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div style="background:#fff;border:2px dashed #d1fae5;border-radius:16px;
                    padding:4rem 2rem;text-align:center;color:#94a3b8">
            <i class="bi bi-clipboard2-pulse"
               style="font-size:2.8rem;display:block;margin-bottom:1rem;opacity:.3;color:var(--lime)"></i>
            <div style="font-weight:600;font-size:1.05rem;margin-bottom:.5rem;color:#64748b">
                No assessments yet
            </div>
            <p style="font-size:.85rem;margin-bottom:1.4rem;max-width:340px;margin-inline:auto">
                Create your first assessment and upload a Nessus or CSV scan file to get started.
            </p>
            <a href="{{ route('vuln-assessments.create') }}" class="btn"
               style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;
                      border:none;padding:.55rem 1.6rem">
                <i class="bi bi-plus-lg me-1"></i> New Assessment
            </a>
        </div>
    </div>
    @endforelse
</div>

@if($assessments->hasPages())
<div class="d-flex justify-content-center mt-4">{{ $assessments->links() }}</div>
@endif
@endsection
