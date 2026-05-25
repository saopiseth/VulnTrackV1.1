@extends('layouts.app')

@section('title', 'Hardening Verifications')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h4><i class="bi bi-patch-check-fill me-2" style="color:var(--primary)"></i>Hardening Verifications</h4>
        <p>Post-remediation verification scan results</p>
    </div>
    <a href="{{ route('hardening.verifications.create') }}" class="btn btn-sm" style="background:var(--primary);color:#fff">
        <i class="bi bi-plus-lg me-1"></i>New Verification
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:.875rem">
            <thead style="background:#f8fafc;border-bottom:1px solid var(--border)">
                <tr>
                    <th class="px-3 py-3">Assessment</th>
                    <th class="py-3">Verification Date</th>
                    <th class="py-3">Verified By</th>
                    <th class="py-3">Resolution</th>
                    <th class="py-3">Status</th>
                    <th class="py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($verifications as $v)
                <tr>
                    <td class="px-3 py-3">
                        <a href="{{ route('hardening.assessments.show', $v->assessment) }}" class="fw-600 text-decoration-none" style="color:#0f172a">
                            {{ $v->assessment->name }}
                        </a>
                        <div style="font-size:.75rem;color:var(--text-muted)">
                            {{ $v->assessment->system_name }} &bull; {{ $v->assessment->ip_address }}
                        </div>
                    </td>
                    <td class="py-3">{{ $v->verification_date->format('d M Y') }}</td>
                    <td class="py-3">{{ $v->verified_by ?? '—' }}</td>
                    <td class="py-3">
                        @if($v->upload_status === 'completed')
                        @php $rrate = $v->resolutionRate(); $color = $rrate >= 80 ? '#10b981' : ($rrate >= 50 ? '#f59e0b' : '#ef4444'); @endphp
                        <div style="font-weight:700;color:{{ $color }}">{{ $rrate }}% resolved</div>
                        <div style="font-size:.73rem;color:var(--text-muted)">
                            <span class="text-success">{{ $v->resolved_count }} R</span> /
                            <span class="text-danger">{{ $v->still_open_count }} O</span> /
                            <span class="text-warning">{{ $v->new_finding_count }} New</span>
                        </div>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="py-3">
                        @if($v->upload_status === 'completed')
                            <span class="badge text-bg-success">Completed</span>
                        @elseif($v->upload_status === 'processing')
                            <span class="badge text-bg-warning">Processing</span>
                        @elseif($v->upload_status === 'failed')
                            <span class="badge text-bg-danger">Failed</span>
                        @else
                            <span class="badge text-bg-secondary">Pending</span>
                        @endif
                    </td>
                    <td class="py-3 pe-3 text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('hardening.verifications.show', $v) }}" class="btn btn-sm btn-outline-secondary py-1 px-2">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form method="POST" action="{{ route('hardening.verifications.destroy', $v) }}"
                                  onsubmit="return confirm('Delete this verification and all results?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        No verifications yet.
                        <a href="{{ route('hardening.verifications.create') }}">Create one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($verifications->hasPages())
    <div class="card-footer bg-white border-top-0 d-flex justify-content-end">
        {{ $verifications->links() }}
    </div>
    @endif
</div>
@endsection
