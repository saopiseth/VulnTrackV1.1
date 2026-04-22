@extends('layouts.app')
@section('title', $assessment->name . ' — Progress')

@section('content')
<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-light: rgb(240,248,210); --lime-muted: rgb(232,244,195); }
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .stat-card {
        background:#fff; border:1px solid #e2e8f0; border-radius:14px;
        padding:1.25rem 1.5rem; display:flex; align-items:center; gap:1rem;
    }
    .stat-icon {
        width:46px; height:46px; border-radius:12px;
        display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0;
    }
    .stat-label { font-size:.72rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px; }
    .stat-value { font-size:1.65rem; font-weight:800; color:#0f172a; line-height:1.2; }
    .chart-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .chart-title { font-size:.82rem; font-weight:700; color:#0f172a; text-transform:uppercase; letter-spacing:.5px; margin-bottom:1.25rem; display:flex; align-items:center; gap:.5rem; }
    .chart-title i { color:var(--lime-dark); }
    .scan-row { display:flex; align-items:center; gap:.85rem; padding:.6rem .85rem; border-radius:10px; border:1px solid #f1f5f9; margin-bottom:.5rem; font-size:.8rem; }
    .scan-row:last-child { margin-bottom:0; }
    .scan-badge { font-size:.68rem; font-weight:700; padding:.15rem .5rem; border-radius:20px; white-space:nowrap; }
</style>

{{-- Breadcrumb + header --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1" style="font-size:.73rem">
            <li class="breadcrumb-item"><a href="{{ route('vuln-assessments.index') }}" style="color:#94a3b8;text-decoration:none">VA Assessments</a></li>
            <li class="breadcrumb-item"><a href="{{ route('vuln-assessments.show', $assessment) }}" style="color:#94a3b8;text-decoration:none">{{ Str::limit($assessment->name, 40) }}</a></li>
            <li class="breadcrumb-item active" style="color:#64748b">Progress</li>
        </ol></nav>
        <h5 style="margin:0;font-weight:700;color:#0f172a">{{ $assessment->name }} — Progress</h5>
        <div style="font-size:.78rem;color:#94a3b8;margin-top:.2rem">
            {{ $scans->count() }} scan{{ $scans->count() !== 1 ? 's' : '' }} uploaded &nbsp;·&nbsp;
            {{ $assessment->environment ?? '—' }} &nbsp;·&nbsp;
            {{ $assessment->scopeGroup?->name ?? 'No scope group' }}
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('vuln-assessments.findings', $assessment) }}" class="btn btn-sm"
            style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.38rem .9rem;font-size:.81rem">
            <i class="bi bi-table me-1"></i>Findings
        </a>
        <a href="{{ route('vuln-assessments.show', $assessment) }}" class="btn btn-sm"
            style="border:1.5px solid var(--lime);border-radius:9px;color:var(--lime-dark);background:#fff;font-weight:600;font-size:.81rem;padding:.38rem .9rem">
            <i class="bi bi-arrow-left me-1"></i>Overview
        </a>
    </div>
</div>

{{-- ── Summary stat cards ──────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f1f5f9">
                <i class="bi bi-shield-exclamation" style="color:#475569"></i>
            </div>
            <div>
                <div class="stat-label">Total Tracked</div>
                <div class="stat-value">{{ $totalTracked }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2">
                <i class="bi bi-exclamation-circle-fill" style="color:#dc2626"></i>
            </div>
            <div>
                <div class="stat-label">Still Open</div>
                <div class="stat-value" style="color:#dc2626">{{ $totalOpen }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5">
                <i class="bi bi-check-circle-fill" style="color:#059669"></i>
            </div>
            <div>
                <div class="stat-label">Resolved</div>
                <div class="stat-value" style="color:#059669">{{ $totalResolved }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lime-light)">
                <i class="bi bi-percent" style="color:var(--lime-dark)"></i>
            </div>
            <div>
                <div class="stat-label">Resolution Rate</div>
                <div class="stat-value" style="color:var(--lime-dark)">
                    {{ $totalTracked > 0 ? round($totalResolved / $totalTracked * 100) : 0 }}%
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- ── Severity Trend Line Chart ────────────────────────────────── --}}
    <div class="col-12">
        <div class="chart-card">
            <div class="chart-title"><i class="bi bi-graph-up-arrow"></i>Severity Trend per Scan</div>
            <canvas id="severityTrendChart" style="max-height:320px"></canvas>
        </div>
    </div>

    {{-- ── Remediation Status Pie Chart ────────────────────────────── --}}
    <div class="col-12 col-md-5">
        <div class="chart-card">
            <div class="chart-title"><i class="bi bi-pie-chart-fill"></i>Remediation Status</div>
            <canvas id="remChart" style="max-height:260px"></canvas>
        </div>
    </div>

    {{-- ── Current Severity Breakdown Bar ─────────────────────────── --}}
    <div class="col-12 col-md-7">
        <div class="chart-card">
            <div class="chart-title"><i class="bi bi-bar-chart-steps"></i>Current Severity Breakdown</div>
            <canvas id="sevBreakChart" style="max-height:260px"></canvas>
        </div>
    </div>

    {{-- ── Scan History ─────────────────────────────────────────────── --}}
    <div class="col-12">
        <div class="va-card" style="padding:1.25rem">
            <div style="font-size:.82rem;font-weight:700;color:#0f172a;text-transform:uppercase;letter-spacing:.5px;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem">
                <i class="bi bi-clock-history" style="color:var(--lime-dark)"></i> Scan History
            </div>
            @foreach($scans as $scan)
            <div class="scan-row">
                @if($scan->is_baseline)
                <span class="scan-badge" style="background:#dbeafe;color:#1e40af">Baseline</span>
                @else
                <span class="scan-badge" style="background:var(--lime-muted);color:var(--lime-dark)">Scan #{{ $loop->iteration }}</span>
                @endif
                <span style="color:#94a3b8;font-size:.73rem">{{ $scan->created_at->format('d M Y, H:i') }}</span>
                <span style="font-size:.75rem;color:#475569">{{ $scan->filename }}</span>
                <div class="ms-auto d-flex gap-2">
                    <span style="font-size:.72rem;color:#64748b"><i class="bi bi-bug-fill" style="color:#dc2626"></i> {{ $scan->finding_count }} findings</span>
                    <span style="font-size:.72rem;color:#64748b"><i class="bi bi-hdd-network" style="color:#3b82f6"></i> {{ $scan->host_count }} hosts</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.plugins.legend.labels.boxWidth = 12;
    Chart.defaults.plugins.legend.labels.padding  = 16;

    var scanLabels = {!! json_encode(array_values($scanLabels)) !!};

    // ── 1. Severity Trend Line Chart ────────────────────────────────
    new Chart(document.getElementById('severityTrendChart'), {
        type: 'line',
        data: {
            labels: scanLabels,
            datasets: [
                {
                    label: 'Critical',
                    data: {!! json_encode(array_values($severityTrend['Critical'])) !!},
                    borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,.1)',
                    borderWidth: 2.5, pointRadius: 4, tension: .35, fill: false
                },
                {
                    label: 'High',
                    data: {!! json_encode(array_values($severityTrend['High'])) !!},
                    borderColor: '#ea580c', backgroundColor: 'rgba(234,88,12,.1)',
                    borderWidth: 2.5, pointRadius: 4, tension: .35, fill: false
                },
                {
                    label: 'Medium',
                    data: {!! json_encode(array_values($severityTrend['Medium'])) !!},
                    borderColor: '#ca8a04', backgroundColor: 'rgba(202,138,4,.1)',
                    borderWidth: 2.5, pointRadius: 4, tension: .35, fill: false
                },
                {
                    label: 'Low',
                    data: {!! json_encode(array_values($severityTrend['Low'])) !!},
                    borderColor: '#64748b', backgroundColor: 'rgba(100,116,139,.08)',
                    borderWidth: 2, pointRadius: 4, tension: .35, fill: false
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    // ── 2. Remediation Status Pie ────────────────────────────────────
    var remLabels = {!! json_encode(array_keys($remCounts->toArray())) !!};
    var remData   = {!! json_encode(array_values($remCounts->toArray())) !!};
    var remColors = {
        'Open':'#dc2626','In Progress':'#ca8a04','Resolved':'#059669','Accepted Risk':'#64748b'
    };
    new Chart(document.getElementById('remChart'), {
        type: 'doughnut',
        data: {
            labels: remLabels,
            datasets: [{
                data: remData,
                backgroundColor: remLabels.map(function(l){ return remColors[l] || '#94a3b8'; }),
                borderWidth: 2, borderColor: '#fff', hoverOffset: 8
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: true, cutout: '60%',
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // ── 4. Current Severity Breakdown Bar ───────────────────────────
    var sevLabels = ['Critical','High','Medium','Low'];
    var sevData   = {!! json_encode(collect(['Critical','High','Medium','Low'])->map(fn($s) => (int)($currentSevCounts[$s] ?? 0))->values()) !!};
    var sevColors = ['#dc2626','#ea580c','#ca8a04','#64748b'];
    new Chart(document.getElementById('sevBreakChart'), {
        type: 'bar',
        data: {
            labels: sevLabels,
            datasets: [{
                label: 'Findings',
                data: sevData,
                backgroundColor: sevColors,
                borderRadius: 7, borderSkipped: false
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
@endpush
