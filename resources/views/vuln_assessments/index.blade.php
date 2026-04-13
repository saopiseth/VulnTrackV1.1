@extends('layouts.app')
@section('title', 'VA Assessments')

@section('content')
<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-light: rgb(240,248,210); --lime-muted: rgb(232,244,195); }
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .badge-env { padding:.2rem .65rem; border-radius:20px; font-size:.7rem; font-weight:700; }
    .env-production { background:#fee2e2; color:#991b1b; }
    .env-uat        { background:#fef9c3; color:#854d0e; }
    .env-internal   { background:#e0f2fe; color:#0c4a6e; }
    .env-development{ background:#f1f5f9; color:#475569; }
    thead.lime-head th { background:var(--lime-muted) !important; color:var(--lime-dark) !important; }
</style>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4><i class="bi bi-clipboard2-pulse-fill me-2" style="color:var(--lime)"></i>VA Assessments</h4>
        <p>Create and manage vulnerability assessments from scan results.</p>
    </div>
    <a href="{{ route('vuln-assessments.create') }}" class="btn btn-sm"
        style="background:rgb(152,194,10);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1rem">
        <i class="bi bi-plus-lg me-1"></i> New Assessment
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="va-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table" style="margin:0;font-size:.84rem">
            <thead class="lime-head">
                <tr>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0">Assessment Name</th>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0">Assessment Period</th>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;text-align:center">Scans</th>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;text-align:center">Findings</th>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0">Created By</th>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assessments as $a)
                @php
                    // Active scan = latest non-baseline with findings, or baseline if only one
                    $activeScan = $a->scans->where('is_baseline', false)->where('finding_count', '>', 0)->sortByDesc('id')->first()
                                ?? $a->scans->where('is_baseline', true)->first();

                    // Unique findings per host (plugin_id + ip_address) — excludes Info
                    $sevStats    = null;
                    $uniqueHosts = 0;
                    if ($activeScan) {
                        $sevStats = \App\Models\VulnFinding::where('scan_id', $activeScan->id)
                            ->whereIn('severity', ['Critical','High','Medium','Low'])
                            ->selectRaw("COUNT(*) as total,
                                         SUM(CASE WHEN severity='Critical' THEN 1 ELSE 0 END) as c,
                                         SUM(CASE WHEN severity='High'     THEN 1 ELSE 0 END) as h,
                                         SUM(CASE WHEN severity='Medium'   THEN 1 ELSE 0 END) as m,
                                         SUM(CASE WHEN severity='Low'      THEN 1 ELSE 0 END) as l")
                            ->first();
                        $uniqueHosts = \App\Models\VulnFinding::where('scan_id', $activeScan->id)
                            ->distinct('ip_address')
                            ->count('ip_address');
                    }
                    $activeFindingCount = $sevStats->total ?? 0;
                @endphp
                <tr style="border-color:#f1f5f9">
                    <td style="padding:.65rem 1rem;vertical-align:middle;border-color:#f1f5f9">
                        <div style="font-weight:600;color:#0f172a">{{ $a->name }}</div>
                        @if($a->description)
                        <div style="font-size:.75rem;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:280px">{{ Str::limit($a->description, 80) }}</div>
                        @endif
                        @if($a->environment)
                        <span class="badge-env env-{{ strtolower($a->environment) }}" style="font-size:.65rem;padding:.1rem .4rem;margin-top:.2rem;display:inline-block">{{ $a->environment }}</span>
                        @endif
                    </td>
                    <td style="padding:.65rem 1rem;vertical-align:middle;border-color:#f1f5f9;color:#374151;font-size:.82rem">
                        @if($a->period_start || $a->period_end)
                            <i class="bi bi-calendar3 me-1" style="color:#94a3b8"></i>
                            {{ $a->period_start?->format('d M Y') ?? '—' }}
                            &ndash;
                            {{ $a->period_end?->format('d M Y') ?? '—' }}
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                    <td style="padding:.65rem 1rem;vertical-align:middle;border-color:#f1f5f9;text-align:center">
                        <span style="font-weight:700;color:#0f172a">{{ $a->scans->count() }}</span>
                        @if($activeScan)
                        @php $latestCount = $a->scans->where('is_baseline', false)->count(); @endphp
                        <div style="font-size:.67rem;color:#94a3b8;margin-top:.1rem">
                            {{ $latestCount > 0 ? $latestCount . ' latest' : 'Baseline only' }}
                        </div>
                        @endif
                    </td>
                    <td style="padding:.65rem 1rem;vertical-align:middle;border-color:#f1f5f9;text-align:center">
                        @if($activeScan && $activeFindingCount > 0)
                        <span style="font-weight:700;color:rgb(118,151,7);font-size:.95rem">{{ number_format($activeFindingCount) }}</span>
                        <div style="font-size:.67rem;color:#94a3b8;margin-top:.1rem">
                            <i class="bi bi-hdd-network me-1"></i>{{ $uniqueHosts }} {{ Str::plural('host', $uniqueHosts) }}
                        </div>
                        @if($sevStats && ($sevStats->c + $sevStats->h + $sevStats->m + $sevStats->l) > 0)
                        <div class="d-flex justify-content-center gap-1 mt-1 flex-wrap">
                            @if($sevStats->c > 0)<span style="font-size:.63rem;background:#fee2e2;color:#991b1b;border-radius:10px;padding:.05rem .35rem;font-weight:700">C:{{ $sevStats->c }}</span>@endif
                            @if($sevStats->h > 0)<span style="font-size:.63rem;background:#ffedd5;color:#9a3412;border-radius:10px;padding:.05rem .35rem;font-weight:700">H:{{ $sevStats->h }}</span>@endif
                            @if($sevStats->m > 0)<span style="font-size:.63rem;background:#fef9c3;color:#854d0e;border-radius:10px;padding:.05rem .35rem;font-weight:700">M:{{ $sevStats->m }}</span>@endif
                            @if($sevStats->l > 0)<span style="font-size:.63rem;background:#f1f5f9;color:#475569;border-radius:10px;padding:.05rem .35rem;font-weight:700">L:{{ $sevStats->l }}</span>@endif
                        </div>
                        @endif
                        @elseif($activeScan)
                        <span style="color:#94a3b8;font-size:.82rem">No findings</span>
                        @else
                        <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                    <td style="padding:.65rem 1rem;vertical-align:middle;border-color:#f1f5f9;color:#64748b;font-size:.8rem">
                        {{ $a->creator?->name ?? '—' }}
                    </td>
                    <td style="padding:.65rem 1rem;vertical-align:middle;border-color:#f1f5f9;text-align:right">
                        <a href="{{ route('vuln-assessments.show', $a) }}" class="btn btn-sm"
                            style="border-radius:8px;background:rgb(232,244,195);color:rgb(118,151,7);border:none;font-weight:600;padding:.28rem .75rem;font-size:.78rem">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                        <form method="POST" action="{{ route('vuln-assessments.destroy', $a) }}" class="d-inline"
                            onsubmit="return confirm('Delete this assessment and all its data?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm"
                                style="border-radius:8px;border:1px solid #fca5a5;color:#dc2626;background:#fff8f8;padding:.28rem .6rem;font-size:.78rem">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:3rem;color:#94a3b8">
                        <i class="bi bi-clipboard2-pulse" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
                        No assessments yet. <a href="{{ route('vuln-assessments.create') }}" style="color:rgb(118,151,7)">Create one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($assessments->hasPages())
    <div style="padding:.75rem 1.5rem;border-top:1px solid #f1f5f9">{{ $assessments->links() }}</div>
    @endif
</div>
@endsection
