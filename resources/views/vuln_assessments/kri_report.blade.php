@extends('layouts.app')
@section('title', 'KRI Report - ' . $assessment->name)

@section('content')
<style>
    .kri-page-head {
        background:#fff;
        border:1px solid #e2e8f0;
        border-radius:12px;
        padding:1.2rem 1.35rem;
        margin-bottom:1rem;
    }
    .kri-card { border:1px solid #e2e8f0; border-radius:10px; padding:1rem; height:100%; background:#fff; }
    .kri-label { font-size:.66rem; font-weight:800; text-transform:uppercase; letter-spacing:.55px; color:#64748b; margin-bottom:.35rem; }
    .kri-value { font-size:1.8rem; font-weight:900; line-height:1.05; color:#0f172a; }
    .kri-note { font-size:.73rem; color:#64748b; margin-top:.35rem; line-height:1.35; }
    .kri-meter { height:7px; border-radius:99px; background:#e2e8f0; overflow:hidden; margin-top:.65rem; }
    .kri-meter span { display:block; height:100%; border-radius:99px; }
    .kri-section-title {
        font-size:.72rem;
        font-weight:800;
        text-transform:uppercase;
        letter-spacing:.65px;
        color:var(--primary-dark);
        display:flex;
        align-items:center;
        gap:.4rem;
        margin:1rem 0 .75rem;
    }
    .kri-table th {
        font-size:.68rem;
        font-weight:800;
        text-transform:uppercase;
        letter-spacing:.45px;
        color:#64748b;
        white-space:nowrap;
    }
    .kri-table td { font-size:.82rem; vertical-align:middle; }
    .kri-chart { border:1px solid #e2e8f0; border-radius:10px; padding:1rem; background:#fff; height:100%; }
    .kri-doughnut-wrap { display:flex; align-items:center; gap:1rem; margin-top:.65rem; }
    .kri-doughnut {
        width:132px; height:132px; border-radius:50%; flex-shrink:0;
        display:grid; place-items:center; position:relative;
    }
    .kri-doughnut::after {
        content:""; width:76px; height:76px; border-radius:50%; background:#fff; position:absolute;
        box-shadow:inset 0 0 0 1px #e2e8f0;
    }
    .kri-doughnut-center { position:relative; z-index:1; text-align:center; line-height:1.05; }
    .kri-doughnut-total { display:block; color:#0f172a; font-size:1.25rem; font-weight:900; }
    .kri-doughnut-caption { display:block; color:#94a3b8; font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.35px; }
    .kri-legend { flex:1; min-width:0; }
    .kri-legend-row { display:grid; grid-template-columns:12px 1fr auto; align-items:center; gap:.5rem; margin:.42rem 0; }
    .kri-legend-dot { width:10px; height:10px; border-radius:50%; }
    .kri-legend-label { color:#475569; font-size:.75rem; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .kri-legend-value { color:#64748b; font-size:.75rem; font-weight:800; }
    @media (max-width: 575.98px) {
        .kri-doughnut-wrap { align-items:flex-start; }
        .kri-doughnut { width:112px; height:112px; }
        .kri-doughnut::after { width:64px; height:64px; }
    }
</style>

<div class="kri-page-head">
    <nav style="margin-bottom:.55rem">
        <ol class="breadcrumb mb-0" style="font-size:.73rem">
            <li class="breadcrumb-item"><a href="{{ route('vuln-assessments.index') }}" style="color:#94a3b8;text-decoration:none">VA Assessments</a></li>
            <li class="breadcrumb-item"><a href="{{ route('vuln-assessments.show', $assessment) }}" style="color:#94a3b8;text-decoration:none">{{ Str::limit($assessment->name, 42) }}</a></li>
            <li class="breadcrumb-item active" style="color:#374151">KRI Report</li>
        </ol>
    </nav>

    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
            <h4 style="color:#0f172a;margin:0;font-size:1.25rem;font-weight:800">
                <i class="bi bi-speedometer2 me-2" style="color:#1d4ed8"></i>Vulnerability KRI Report
            </h4>
            <div style="font-size:.8rem;color:#64748b;margin-top:.3rem">
                {{ $assessment->name }} · {{ $assessment->scans->count() }} scan{{ $assessment->scans->count() !== 1 ? 's' : '' }}
                @if($activeScan)
                    · Latest data {{ $activeScan->created_at?->format('d M Y, H:i') }}
                @endif
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('vuln-assessments.show', $assessment) }}" class="btn btn-sm"
               style="border:1px solid #cbd5e1;border-radius:8px;color:#475569;background:#fff;font-weight:600">
                <i class="bi bi-arrow-left me-1"></i>Assessment
            </a>
            @if($activeScan)
                <a href="{{ route('vuln-assessments.findings', $assessment) }}" class="btn btn-sm"
                   style="background:var(--primary);color:#fff;border-radius:8px;font-weight:600;border:none">
                    <i class="bi bi-table me-1"></i>Findings
                </a>
                <a href="{{ route('vuln-assessments.kri-report.powerpoint', $assessment) }}" class="btn btn-sm"
                   style="background:#1d4ed8;color:#fff;border-radius:8px;font-weight:600;border:none">
                    <i class="bi bi-file-earmark-ppt-fill me-1"></i>PowerPoint
                </a>
            @endif
        </div>
    </div>
</div>

@if(!$kri)
<div class="kri-card" style="text-align:center;padding:3rem;color:#64748b">
    <i class="bi bi-cloud-upload" style="font-size:2.25rem;display:block;margin-bottom:.75rem;color:#94a3b8"></i>
    <div style="font-weight:700;color:#475569;margin-bottom:.35rem">No KRI data available yet</div>
    <div style="font-size:.85rem">Upload scan data for this assessment to generate the vulnerability KRI report.</div>
</div>
@else
@php
    $severityChart = [
        ['Critical', (int) ($stats->critical ?? 0), '#780000'],
        ['High', (int) ($stats->high ?? 0), '#dc0000'],
        ['Medium', (int) ($stats->medium ?? 0), '#fd8c00'],
        ['Low', (int) ($stats->low ?? 0), '#16a34a'],
    ];
    $workflowChart = [
        ['Open', $kri['open_remediation'], '#dc2626'],
        ['In Progress', $kri['in_progress'], '#d97706'],
        ['Accepted', $kri['accepted_risk'], '#64748b'],
        ['Resolved', $kri['resolved_by_scan'], '#16a34a'],
    ];
    $slaChart = [
        ['Breached', $kri['sla_breached'], '#dc2626'],
        ['Approaching', $kri['sla_approaching'], '#d97706'],
        ['On Track', $kri['sla_on_track'], '#16a34a'],
        ['Met', $kri['sla_met'], '#0ea5e9'],
    ];
    $doughnutStyle = function (array $rows): string {
        $total = max(1, collect($rows)->sum(fn($row) => (int) $row[1]));
        $cursor = 0;
        $segments = [];

        foreach ($rows as [$label, $value, $color]) {
            $value = (int) $value;
            if ($value <= 0) {
                continue;
            }

            $start = round(($cursor / $total) * 360, 2);
            $cursor += $value;
            $end = round(($cursor / $total) * 360, 2);
            $segments[] = "{$color} {$start}deg {$end}deg";
        }

        return $segments ? 'conic-gradient(' . implode(', ', $segments) . ')' : 'conic-gradient(#e2e8f0 0deg 360deg)';
    };
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="kri-section-title mb-0"><i class="bi bi-activity"></i>Executive Indicators</div>
    <span style="font-size:.75rem;color:#64748b;font-weight:600">
        {{ $kri['sla_policy'] ? 'SLA: ' . $kri['sla_policy'] : 'No SLA policy configured' }}
    </span>
</div>

<div class="kri-section-title"><i class="bi bi-bar-chart-fill"></i>KRI Charts</div>
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="kri-chart">
            <div class="kri-label">Active Severity Distribution</div>
            <div class="kri-doughnut-wrap">
                <div class="kri-doughnut" style="background:{{ $doughnutStyle($severityChart) }}">
                    <div class="kri-doughnut-center">
                        <span class="kri-doughnut-total">{{ number_format(collect($severityChart)->sum(fn($row) => $row[1])) }}</span>
                        <span class="kri-doughnut-caption">Active</span>
                    </div>
                </div>
                <div class="kri-legend">
                    @foreach($severityChart as [$label, $value, $color])
                    <div class="kri-legend-row">
                        <span class="kri-legend-dot" style="background:{{ $color }}"></span>
                        <span class="kri-legend-label">{{ $label }}</span>
                        <span class="kri-legend-value">{{ number_format($value) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="kri-chart">
            <div class="kri-label">Remediation Workflow</div>
            <div class="kri-doughnut-wrap">
                <div class="kri-doughnut" style="background:{{ $doughnutStyle($workflowChart) }}">
                    <div class="kri-doughnut-center">
                        <span class="kri-doughnut-total">{{ number_format(collect($workflowChart)->sum(fn($row) => $row[1])) }}</span>
                        <span class="kri-doughnut-caption">Items</span>
                    </div>
                </div>
                <div class="kri-legend">
                    @foreach($workflowChart as [$label, $value, $color])
                    <div class="kri-legend-row">
                        <span class="kri-legend-dot" style="background:{{ $color }}"></span>
                        <span class="kri-legend-label">{{ $label }}</span>
                        <span class="kri-legend-value">{{ number_format($value) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="kri-chart">
            <div class="kri-label">SLA Health</div>
            <div class="kri-doughnut-wrap">
                <div class="kri-doughnut" style="background:{{ $doughnutStyle($slaChart) }}">
                    <div class="kri-doughnut-center">
                        <span class="kri-doughnut-total">{{ number_format(collect($slaChart)->sum(fn($row) => $row[1])) }}</span>
                        <span class="kri-doughnut-caption">SLA</span>
                    </div>
                </div>
                <div class="kri-legend">
                    @foreach($slaChart as [$label, $value, $color])
                    <div class="kri-legend-row">
                        <span class="kri-legend-dot" style="background:{{ $color }}"></span>
                        <span class="kri-legend-label">{{ $label }}</span>
                        <span class="kri-legend-value">{{ number_format($value) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="kri-card">
            <div class="kri-label">Asset Risk Concentration</div>
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <div class="kri-value">{{ number_format($kri['active_hosts']) }}</div>
                    <div class="kri-note">hosts with active vulnerabilities</div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:1.25rem;font-weight:900;color:#991b1b">{{ number_format($kri['mission_critical_hosts']) }}</div>
                    <div class="kri-note">mission-critical</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="kri-card">
            <div class="kri-label">Remediation Workflow</div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;text-align:center">
                <div><div style="font-weight:900;color:#dc2626;font-size:1.25rem">{{ number_format($kri['open_remediation']) }}</div><div class="kri-note">Open</div></div>
                <div><div style="font-weight:900;color:#d97706;font-size:1.25rem">{{ number_format($kri['in_progress']) }}</div><div class="kri-note">In Progress</div></div>
                <div><div style="font-weight:900;color:#64748b;font-size:1.25rem">{{ number_format($kri['accepted_risk']) }}</div><div class="kri-note">Accepted</div></div>
                <div><div style="font-weight:900;color:#16a34a;font-size:1.25rem">{{ number_format($kri['resolved_by_scan']) }}</div><div class="kri-note">Resolved</div></div>
            </div>
        </div>
    </div>
</div>


@if($topIps->count())
<div class="kri-section-title"><i class="bi bi-hdd-network"></i>Highest Risk Hosts</div>
<div class="kri-card p-0">
    <div class="table-responsive">
        <table class="table kri-table mb-0">
            <thead>
                <tr>
                    <th class="ps-3">IP Address</th>
                    <th>Hostname</th>
                    <th>Criticality</th>
                    <th class="text-center">Critical</th>
                    <th class="text-center">High</th>
                    <th class="text-center">Active</th>
                    <th>Vuln Age</th>
                    <th class="pe-3">Owner</th>
                </tr>
            </thead>
            <tbody>
            @foreach($topIps->take(10) as $ip)
                <tr>
                    <td class="ps-3" style="font-family:monospace;font-weight:700">{{ $ip->ip_address }}</td>
                    <td>{{ $ip->hostname ?: '-' }}</td>
                    <td>
                        @php $cm = \App\Models\AssessmentScope::criticalityLevels()[$ip->system_criticality] ?? null; @endphp
                        @if($cm)
                            <span style="display:inline-block;background:{{ $cm['bg'] }};color:{{ $cm['color'] }};border-radius:6px;padding:.1rem .45rem;font-size:.7rem;font-weight:700">{{ $cm['label'] }}</span>
                        @else
                            <span style="color:#94a3b8">-</span>
                        @endif
                    </td>
                    <td class="text-center" style="font-weight:800;color:#780000">{{ number_format($ip->critical) }}</td>
                    <td class="text-center" style="font-weight:800;color:#dc0000">{{ number_format($ip->high) }}</td>
                    <td class="text-center" style="font-weight:800;color:#059669">{{ number_format($ip->active_count) }}</td>
                    <td style="max-width:220px">
                        @if(!empty($ip->vuln_age_quarters))
                            <div style="display:flex;flex-wrap:wrap;gap:.25rem">
                                @foreach($ip->vuln_age_quarters as $q)
                                @php $isCurrent = $q['uuid'] === $assessment->uuid; @endphp
                                <a href="{{ route('vuln-assessments.show', $q['uuid']) }}"
                                   title="{{ $q['name'] }} — {{ $q['count'] }} vuln{{ $q['count'] !== 1 ? 's' : '' }}"
                                   style="background:{{ $isCurrent ? '#dbeafe' : '#f1f5f9' }};color:{{ $isCurrent ? '#1d4ed8' : '#475569' }};border:1px solid {{ $isCurrent ? '#bfdbfe' : '#e2e8f0' }};font-size:.68rem;font-weight:700;padding:.15rem .45rem;border-radius:5px;white-space:nowrap;line-height:1.5;max-width:130px;overflow:hidden;text-overflow:ellipsis;display:inline-block;vertical-align:middle;text-decoration:none">
                                    {{ Str::limit($q['name'], 22) }}&nbsp;<span style="opacity:.75">{{ $q['count'] }}</span>
                                </a>
                                @endforeach
                            </div>
                        @else
                            <span style="color:#94a3b8">—</span>
                        @endif
                    </td>
                    <td class="pe-3">{{ $ip->system_owner ?: '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endif
@endsection
