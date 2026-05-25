@extends('layouts.app')

@section('title', $assessment->name)

@section('content')
<div class="page-header d-flex align-items-start justify-content-between">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hardening.dashboard') }}">Secure Configuration</a></li>
                <li class="breadcrumb-item"><a href="{{ route('hardening.assessments.index') }}">Assessments</a></li>
                <li class="breadcrumb-item active">{{ Str::limit($assessment->name, 50) }}</li>
            </ol>
        </nav>
        <h4><i class="bi bi-clipboard2-pulse-fill me-2" style="color:var(--primary)"></i>{{ $assessment->name }}</h4>
        <p>{{ $assessment->system_name }} &bull; {{ $assessment->ip_address }} &bull; Assessed {{ $assessment->assessment_date->format('d M Y') }}</p>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        @if($assessment->upload_status === 'completed')
        <a href="{{ route('hardening.verifications.create', ['assessment' => $assessment->uuid]) }}"
           class="btn btn-sm" style="background:var(--primary);color:#fff">
            <i class="bi bi-patch-check me-1"></i>Add Verification
        </a>
        @endif
        <form method="POST" action="{{ route('hardening.assessments.destroy', $assessment) }}"
              onsubmit="return confirm('Delete this assessment and all findings?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash3 me-1"></i>Delete
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Upload status polling --}}
@if(in_array($assessment->upload_status, ['pending','processing']))
<div class="alert alert-info d-flex align-items-center gap-2 mb-3" id="processingAlert">
    <div class="spinner-border spinner-border-sm text-info"></div>
    <span>Processing Nessus file… This page will refresh automatically.</span>
</div>
<script nonce="{{ csp_nonce() }}">
(function(){
    var poll = setInterval(function(){
        fetch('{{ route("hardening.assessments.status", $assessment) }}')
            .then(r=>r.json())
            .then(function(d){
                if(d.status === 'completed' || d.status === 'failed'){
                    clearInterval(poll);
                    location.reload();
                }
            });
    }, 3000);
})();
</script>
@endif

@if($assessment->upload_status === 'failed')
<div class="alert alert-danger mb-3">
    <strong>Upload failed:</strong> {{ $assessment->upload_error ?? 'Unknown error.' }}
</div>
@endif

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 text-center">
            <div style="font-size:1.8rem;font-weight:800;color:#0f172a">{{ $assessment->total_findings }}</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Total Findings</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 text-center">
            <div style="font-size:1.8rem;font-weight:800;color:#ef4444">{{ $assessment->non_compliant_count }}</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Non-Compliant</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 text-center">
            <div style="font-size:1.8rem;font-weight:800;color:#f59e0b">{{ $assessment->partially_compliant_count }}</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Partially Compliant</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 text-center">
            <div style="font-size:1.8rem;font-weight:800;color:#10b981">{{ $assessment->compliant_count }}</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Compliant</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Details --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-700 mb-3" style="font-size:.85rem">Assessment Details</h6>
                @foreach([
                    ['System Name', $assessment->system_name],
                    ['Hostname', $assessment->hostname ?? '—'],
                    ['IP Address', $assessment->ip_address],
                    ['Operating System', $assessment->operating_system ?? '—'],
                    ['Environment', $assessment->environment],
                    ['Scope Type', $assessment->scope_type ?? '—'],
                    ['Asset Owner', $assessment->asset_owner ?? '—'],
                    ['System Owner', $assessment->system_owner ?? '—'],
                    ['Criticality', $assessment->criticality_level],
                    ['Created By', $assessment->creator->name ?? '—'],
                ] as [$label, $value])
                <div class="d-flex justify-content-between py-1" style="border-bottom:1px solid var(--border);font-size:.82rem">
                    <span class="text-muted">{{ $label }}</span>
                    <span class="fw-500 text-end ms-2">{{ $value }}</span>
                </div>
                @endforeach
                @if($assessment->remarks)
                <div class="mt-2" style="font-size:.82rem">
                    <div class="text-muted mb-1">Remarks</div>
                    <div>{{ $assessment->remarks }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Verifications --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-700 mb-0" style="font-size:.85rem">Verification History</h6>
                    @if($assessment->upload_status === 'completed')
                    <a href="{{ route('hardening.verifications.create', ['assessment' => $assessment->uuid]) }}"
                       class="btn btn-sm btn-outline-secondary py-1 px-2">
                        <i class="bi bi-plus-lg me-1"></i>Add Verification
                    </a>
                    @endif
                </div>
                @forelse($assessment->verifications->sortByDesc('verification_date') as $v)
                <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border)">
                    <div>
                        @if($v->upload_status === 'completed')
                            @php $rrate = $v->resolutionRate(); @endphp
                            <div style="width:44px;height:44px;border-radius:50%;background:{{ $rrate >= 80 ? '#f0fdf4' : ($rrate >= 50 ? '#fffbeb' : '#fef2f2') }};display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:{{ $rrate >= 80 ? '#10b981' : ($rrate >= 50 ? '#f59e0b' : '#ef4444') }}">
                                {{ $rrate }}%
                            </div>
                        @else
                            <div style="width:44px;height:44px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:.8rem;color:#94a3b8">
                                <i class="bi bi-hourglass"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-600" style="font-size:.85rem">
                            <a href="{{ route('hardening.verifications.show', $v) }}" class="text-decoration-none text-dark">
                                Verification — {{ $v->verification_date->format('d M Y') }}
                            </a>
                        </div>
                        <div style="font-size:.78rem;color:var(--text-muted)">
                            Verified by: {{ $v->verified_by ?? '—' }}
                            @if($v->upload_status === 'completed')
                             &bull; <span class="text-success">{{ $v->resolved_count }} Resolved</span>
                             &bull; <span class="text-danger">{{ $v->still_open_count }} Still Open</span>
                             &bull; <span class="text-warning">{{ $v->new_finding_count }} New</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        @if($v->upload_status === 'completed')
                            <span class="badge text-bg-success">Done</span>
                        @elseif($v->upload_status === 'processing')
                            <span class="badge text-bg-warning">Processing</span>
                        @elseif($v->upload_status === 'failed')
                            <span class="badge text-bg-danger">Failed</span>
                        @else
                            <span class="badge text-bg-secondary">Pending</span>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-muted mb-0" style="font-size:.875rem">No verifications yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Findings table --}}
<div class="card">
    <div class="card-body p-0">
        <div class="d-flex align-items-center justify-content-between px-3 py-3" style="border-bottom:1px solid var(--border)">
            <h6 class="mb-0 fw-700" style="font-size:.85rem">Assessment Findings</h6>
            <span class="badge bg-light text-dark">{{ $assessment->total_findings }} total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.82rem">
                <thead style="background:#f8fafc">
                    <tr>
                        <th class="px-3 py-2">Plugin / Check</th>
                        <th class="py-2">Family</th>
                        <th class="py-2">Severity</th>
                        <th class="py-2">Port</th>
                        <th class="py-2">Compliance Status</th>
                        <th class="py-2">Result</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($findings as $f)
                    <tr>
                        <td class="px-3 py-2">
                            <div class="fw-600">{{ Str::limit($f->plugin_name, 60) }}</div>
                            <div style="font-size:.72rem;color:var(--text-muted)">ID: {{ $f->plugin_id }}</div>
                        </td>
                        <td class="py-2" style="color:var(--text-muted)">{{ $f->plugin_family ?? '—' }}</td>
                        <td class="py-2">
                            @if($f->severity)
                            @php $sc = match($f->severity){ 'Critical'=>'danger','High'=>'warning','Medium'=>'info','Low'=>'success',default=>'secondary' }; @endphp
                            <span class="badge text-bg-{{ $sc }}">{{ $f->severity }}</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="py-2">{{ $f->port ?: '—' }}</td>
                        <td class="py-2">
                            @php $bc = $f->statusBadgeClass(); @endphp
                            <span class="badge text-bg-{{ $bc }}">{{ $f->compliance_status }}</span>
                        </td>
                        <td class="py-2">
                            <span class="text-muted" style="font-size:.75rem">{{ $f->compliance_result ?? '—' }}</span>
                        </td>
                    </tr>
                    @if($f->plugin_output)
                    <tr style="background:#fafbfc">
                        <td colspan="6" class="px-3 py-2">
                            <pre style="font-size:.72rem;white-space:pre-wrap;margin:0;color:#374151;max-height:120px;overflow:auto">{{ $f->plugin_output }}</pre>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            @if($assessment->upload_status === 'completed')
                                No findings parsed from this file.
                            @else
                                Waiting for file to finish processing…
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($findings->hasPages())
        <div class="card-footer bg-white d-flex justify-content-end">
            {{ $findings->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
