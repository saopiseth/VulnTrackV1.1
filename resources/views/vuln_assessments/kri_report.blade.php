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
    $riskLevel = $kri['risk_score'] >= 100 ? ['Critical Risk', '#fee2e2', '#991b1b']
        : ($kri['risk_score'] >= 50 ? ['Elevated Risk', '#ffedd5', '#c2410c']
        : ($kri['risk_score'] >= 15 ? ['Moderate Risk', '#fef9c3', '#854d0e'] : ['Low Risk', '#dcfce7', '#166534']));
    $slaColor = $kri['sla_breached'] > 0 ? '#dc2626' : ($kri['sla_approaching'] > 0 ? '#d97706' : '#16a34a');
    $remColor = $kri['remediation_pct'] >= 80 ? '#16a34a' : ($kri['remediation_pct'] >= 50 ? '#d97706' : '#dc2626');
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="kri-section-title mb-0"><i class="bi bi-activity"></i>Executive Indicators</div>
    <span style="font-size:.75rem;color:#64748b;font-weight:600">
        {{ $kri['sla_policy'] ? 'SLA: ' . $kri['sla_policy'] : 'No SLA policy configured' }}
    </span>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="kri-card" style="background:{{ $riskLevel[1] }};border-color:{{ $riskLevel[2] }}33">
            <div class="kri-label" style="color:{{ $riskLevel[2] }}">Overall Risk Score</div>
            <div class="kri-value" style="color:{{ $riskLevel[2] }}">{{ number_format($kri['risk_score']) }}</div>
            <div class="kri-note" style="color:{{ $riskLevel[2] }}">{{ $riskLevel[0] }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="kri-card">
            <div class="kri-label">Critical / High Exposure</div>
            <div class="kri-value">{{ number_format($kri['critical_high']) }}</div>
            <div class="kri-note">{{ $kri['critical_high_pct'] }}% of active findings</div>
            <div class="kri-meter"><span style="width:{{ min(100, $kri['critical_high_pct']) }}%;background:#dc2626"></span></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="kri-card">
            <div class="kri-label">SLA Breached</div>
            <div class="kri-value" style="color:{{ $slaColor }}">{{ number_format($kri['sla_breached']) }}</div>
            <div class="kri-note">{{ number_format($kri['sla_approaching']) }} approaching deadline</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="kri-card">
            <div class="kri-label">Remediation Completion</div>
            <div class="kri-value" style="color:{{ $remColor }}">{{ $kri['remediation_pct'] }}%</div>
            <div class="kri-note">{{ number_format($kri['resolved_by_scan']) }} scan-confirmed resolved</div>
            <div class="kri-meter"><span style="width:{{ min(100, $kri['remediation_pct']) }}%;background:{{ $remColor }}"></span></div>
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
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;text-align:center">
                <div><div style="font-weight:900;color:#dc2626;font-size:1.25rem">{{ number_format($kri['open_remediation']) }}</div><div class="kri-note">Open</div></div>
                <div><div style="font-weight:900;color:#d97706;font-size:1.25rem">{{ number_format($kri['in_progress']) }}</div><div class="kri-note">In Progress</div></div>
                <div><div style="font-weight:900;color:#64748b;font-size:1.25rem">{{ number_format($kri['accepted_risk']) }}</div><div class="kri-note">Accepted</div></div>
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
                    <td class="text-center" style="font-weight:800;color:#991b1b">{{ number_format($ip->critical) }}</td>
                    <td class="text-center" style="font-weight:800;color:#c2410c">{{ number_format($ip->high) }}</td>
                    <td class="text-center" style="font-weight:800;color:#059669">{{ number_format($ip->active_count) }}</td>
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
