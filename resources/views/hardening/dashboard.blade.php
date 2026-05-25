@extends('layouts.app')

@section('title', 'Secure Configuration — Dashboard')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h4><i class="bi bi-shield-check me-2" style="color:var(--primary)"></i>Secure Configuration</h4>
        <p>Hardening assessment and verification overview</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('hardening.assessments.create') }}" class="btn btn-sm" style="background:var(--primary);color:#fff">
            <i class="bi bi-plus-lg me-1"></i>New Assessment
        </a>
        <a href="{{ route('hardening.verifications.create') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-patch-check me-1"></i>New Verification
        </a>
    </div>
</div>

{{-- Stat row --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-widget">
            <div class="sw-icon" style="background:rgba(var(--primary-rgb),.12)">
                <i class="bi bi-clipboard2-pulse-fill" style="color:var(--primary)"></i>
            </div>
            <div>
                <div class="sw-label">Total Assessments</div>
                <div class="sw-value">{{ $totalAssessments }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-widget">
            <div class="sw-icon" style="background:rgba(239,68,68,.1)">
                <i class="bi bi-x-circle-fill" style="color:#ef4444"></i>
            </div>
            <div>
                <div class="sw-label">Non-Compliant Findings</div>
                <div class="sw-value">{{ $totalNonCompliant }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-widget">
            <div class="sw-icon" style="background:rgba(16,185,129,.1)">
                <i class="bi bi-check-circle-fill" style="color:#10b981"></i>
            </div>
            <div>
                <div class="sw-label">Compliant Findings</div>
                <div class="sw-value">{{ $totalCompliant }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-widget">
            <div class="sw-icon" style="background:rgba(245,158,11,.1)">
                <i class="bi bi-patch-check-fill" style="color:#f59e0b"></i>
            </div>
            <div>
                <div class="sw-label">Verifications Run</div>
                <div class="sw-value">{{ $totalVerifications }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Recent Assessments --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-600">Recent Assessments</h6>
                    <a href="{{ route('hardening.assessments.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                @forelse($recentAssessments as $a)
                <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border)">
                    <div class="flex-shrink-0">
                        @php
                            $rate = $a->complianceRate();
                            $rateColor = $rate >= 80 ? '#10b981' : ($rate >= 50 ? '#f59e0b' : '#ef4444');
                        @endphp
                        <div style="width:44px;height:44px;border-radius:50%;background:{{ $rateColor }}22;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:{{ $rateColor }}">
                            {{ $rate }}%
                        </div>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-600 text-truncate" style="font-size:.875rem">
                            <a href="{{ route('hardening.assessments.show', $a) }}" class="text-decoration-none text-dark">{{ $a->name }}</a>
                        </div>
                        <div style="font-size:.78rem;color:var(--text-muted)">
                            {{ $a->system_name }} &bull; {{ $a->ip_address }} &bull; {{ $a->assessment_date->format('d M Y') }}
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <span class="badge" style="font-size:.7rem;background:{{ $a->non_compliant_count > 0 ? '#fef2f2' : '#f0fdf4' }};color:{{ $a->non_compliant_count > 0 ? '#ef4444' : '#10b981' }}">
                            {{ $a->non_compliant_count }} Non-Compliant
                        </span>
                        <div style="font-size:.72rem;color:var(--text-muted);margin-top:3px">
                            @if($a->upload_status === 'completed')
                                {{ $a->total_findings }} findings
                            @elseif($a->upload_status === 'processing')
                                <span class="text-warning">Processing…</span>
                            @elseif($a->upload_status === 'failed')
                                <span class="text-danger">Failed</span>
                            @else
                                <span class="text-muted">Pending</span>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-muted mb-0" style="font-size:.875rem">No assessments yet. <a href="{{ route('hardening.assessments.create') }}">Create one</a>.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Compliance Breakdown --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-600 mb-3">Compliance Breakdown</h6>
                @php
                    $grand = $totalCompliant + $totalNonCompliant + $totalPartial + $totalNotApplicable;
                @endphp
                @foreach([
                    ['Non-Compliant', $totalNonCompliant, '#ef4444'],
                    ['Compliant', $totalCompliant, '#10b981'],
                    ['Partially Compliant', $totalPartial, '#f59e0b'],
                    ['Not Applicable', $totalNotApplicable, '#94a3b8'],
                ] as [$label, $count, $color])
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:.8rem">
                        <span style="color:#374151">{{ $label }}</span>
                        <span class="fw-600">{{ $count }}</span>
                    </div>
                    <div style="height:6px;background:#f1f5f9;border-radius:4px;overflow:hidden">
                        <div style="height:100%;width:{{ $grand > 0 ? round($count / $grand * 100) : 0 }}%;background:{{ $color }};border-radius:4px;transition:width .4s"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
