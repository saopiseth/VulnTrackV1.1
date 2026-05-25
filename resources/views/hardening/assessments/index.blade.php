@extends('layouts.app')

@section('title', 'Hardening Assessments')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h4><i class="bi bi-clipboard2-pulse-fill me-2" style="color:var(--primary)"></i>Hardening Assessments</h4>
        <p>Initial secure configuration assessments</p>
    </div>
    <a href="{{ route('hardening.assessments.create') }}" class="btn btn-sm" style="background:var(--primary);color:#fff">
        <i class="bi bi-plus-lg me-1"></i>New Assessment
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:.875rem">
            <thead style="background:#f8fafc;border-bottom:1px solid var(--border)">
                <tr>
                    <th class="px-3 py-3">Assessment Name</th>
                    <th class="py-3">System / IP</th>
                    <th class="py-3">OS / Environment</th>
                    <th class="py-3">Date</th>
                    <th class="py-3">Compliance</th>
                    <th class="py-3">Status</th>
                    <th class="py-3">Verifications</th>
                    <th class="py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($assessments as $a)
                <tr>
                    <td class="px-3 py-3">
                        <a href="{{ route('hardening.assessments.show', $a) }}" class="fw-600 text-decoration-none" style="color:#0f172a">
                            {{ $a->name }}
                        </a>
                        <div style="font-size:.75rem;color:var(--text-muted)">{{ $a->system_name }}</div>
                    </td>
                    <td class="py-3">
                        <div>{{ $a->ip_address }}</div>
                        @if($a->hostname)
                        <div style="font-size:.75rem;color:var(--text-muted)">{{ $a->hostname }}</div>
                        @endif
                    </td>
                    <td class="py-3">
                        <div style="font-size:.8rem">{{ $a->operating_system ?? '—' }}</div>
                        <span class="badge bg-light text-secondary" style="font-size:.7rem">{{ $a->environment }}</span>
                    </td>
                    <td class="py-3">{{ $a->assessment_date->format('d M Y') }}</td>
                    <td class="py-3">
                        @if($a->upload_status === 'completed' && $a->total_findings > 0)
                        @php $rate = $a->complianceRate(); $color = $rate >= 80 ? '#10b981' : ($rate >= 50 ? '#f59e0b' : '#ef4444'); @endphp
                        <div style="font-weight:700;color:{{ $color }}">{{ $rate }}%</div>
                        <div style="font-size:.73rem;color:var(--text-muted)">
                            <span class="text-danger">{{ $a->non_compliant_count }} NC</span> /
                            <span class="text-success">{{ $a->compliant_count }} C</span>
                        </div>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="py-3">
                        @if($a->upload_status === 'completed')
                            <span class="badge text-bg-success">Completed</span>
                        @elseif($a->upload_status === 'processing')
                            <span class="badge text-bg-warning">Processing</span>
                        @elseif($a->upload_status === 'failed')
                            <span class="badge text-bg-danger">Failed</span>
                        @else
                            <span class="badge text-bg-secondary">Pending</span>
                        @endif
                    </td>
                    <td class="py-3">
                        <span class="badge bg-light text-dark">{{ $a->verifications_count }}</span>
                    </td>
                    <td class="py-3 pe-3 text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('hardening.assessments.show', $a) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($a->upload_status === 'completed')
                            <a href="{{ route('hardening.verifications.create', ['assessment' => $a->uuid]) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Add Verification">
                                <i class="bi bi-patch-check"></i>
                            </a>
                            @endif
                            <form method="POST" action="{{ route('hardening.assessments.destroy', $a) }}" onsubmit="return confirm('Delete this assessment and all its findings?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        No hardening assessments found.
                        <a href="{{ route('hardening.assessments.create') }}">Create your first one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($assessments->hasPages())
    <div class="card-footer bg-white border-top-0 d-flex justify-content-end">
        {{ $assessments->links() }}
    </div>
    @endif
</div>
@endsection
