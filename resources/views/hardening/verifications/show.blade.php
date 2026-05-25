@extends('layouts.app')

@section('title', 'Verification — ' . $verification->assessment->name)

@section('content')
<div class="page-header d-flex align-items-start justify-content-between">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hardening.dashboard') }}">Secure Configuration</a></li>
                <li class="breadcrumb-item"><a href="{{ route('hardening.verifications.index') }}">Verifications</a></li>
                <li class="breadcrumb-item active">{{ $verification->verification_date->format('d M Y') }}</li>
            </ol>
        </nav>
        <h4><i class="bi bi-patch-check-fill me-2" style="color:var(--primary)"></i>
            Hardening Verification — {{ $verification->assessment->name }}
        </h4>
        <p>
            <a href="{{ route('hardening.assessments.show', $verification->assessment) }}" class="text-decoration-none">
                {{ $verification->assessment->system_name }}
            </a>
            &bull; {{ $verification->assessment->ip_address }}
            &bull; Verified {{ $verification->verification_date->format('d M Y') }}
        </p>
    </div>
    <form method="POST" action="{{ route('hardening.verifications.destroy', $verification) }}"
          onsubmit="return confirm('Delete this verification and all results?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger flex-shrink-0">
            <i class="bi bi-trash3 me-1"></i>Delete
        </button>
    </form>
</div>

@if(in_array($verification->upload_status, ['pending','processing']))
<div class="alert alert-info d-flex gap-2 mb-3" id="processingAlert">
    <div class="spinner-border spinner-border-sm text-info flex-shrink-0 mt-1"></div>
    <span>Comparing verification scan against initial assessment… This page will refresh automatically.</span>
</div>
<script nonce="{{ csp_nonce() }}">
(function(){
    var poll = setInterval(function(){
        fetch('{{ route("hardening.verifications.status", $verification) }}')
            .then(r=>r.json())
            .then(function(d){
                if(d.status==='completed'||d.status==='failed'){ clearInterval(poll); location.reload(); }
            });
    }, 3000);
})();
</script>
@endif

@if($verification->upload_status === 'failed')
<div class="alert alert-danger mb-3">
    <strong>Processing failed:</strong> {{ $verification->upload_error ?? 'Unknown error.' }}
</div>
@endif

{{-- Summary stats --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Resolved',                $verification->resolved_count,   '#10b981', 'check-circle-fill'],
        ['Still Open',              $verification->still_open_count,  '#ef4444', 'x-circle-fill'],
        ['New Findings',            $verification->new_finding_count, '#f59e0b', 'exclamation-triangle-fill'],
        ['Not Found in Verification',$verification->not_found_count,  '#94a3b8', 'question-circle-fill'],
    ] as [$label, $count, $color, $icon])
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 text-center">
            <i class="bi bi-{{ $icon }} mb-1" style="font-size:1.5rem;color:{{ $color }}"></i>
            <div style="font-size:1.8rem;font-weight:800;color:#0f172a">{{ $count }}</div>
            <div style="font-size:.78rem;color:var(--text-muted)">{{ $label }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Meta --}}
<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-4" style="font-size:.82rem">
        <div><span class="text-muted">Verified By:</span> <strong>{{ $verification->verified_by ?? '—' }}</strong></div>
        <div><span class="text-muted">File:</span> <strong>{{ $verification->nessus_file_name ?? '—' }}</strong></div>
        <div><span class="text-muted">Created By:</span> <strong>{{ $verification->creator->name ?? '—' }}</strong></div>
        @if($verification->remarks)
        <div><span class="text-muted">Remarks:</span> {{ $verification->remarks }}</div>
        @endif
    </div>
</div>

{{-- Results table --}}
<div class="card">
    <div class="card-body p-0">
        <div class="d-flex align-items-center justify-content-between px-3 py-3" style="border-bottom:1px solid var(--border)">
            <h6 class="mb-0 fw-700" style="font-size:.85rem">Verification Results</h6>
            <div class="d-flex gap-2">
                @foreach(['Still Open'=>'danger','Not Found in Verification'=>'secondary','New Finding'=>'warning','Resolved'=>'success','Accepted Risk'=>'info'] as $s=>$cls)
                <span class="badge text-bg-{{ $cls }}" style="cursor:pointer" onclick="filterTable('{{ $s }}')" title="Filter by {{ $s }}">{{ $s }}</span>
                @endforeach
                <span class="badge bg-light text-dark" style="cursor:pointer" onclick="filterTable('')">All</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="resultsTable" style="font-size:.82rem">
                <thead style="background:#f8fafc">
                    <tr>
                        <th class="px-3 py-2">Plugin / Check</th>
                        <th class="py-2">Family</th>
                        <th class="py-2">Severity</th>
                        <th class="py-2">Initial Status</th>
                        <th class="py-2">Verification Status</th>
                        <th class="py-2">Compliance Result</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $r)
                    <tr data-vstatus="{{ $r->verification_status }}">
                        <td class="px-3 py-2">
                            <div class="fw-600">{{ Str::limit($r->plugin_name, 60) }}</div>
                            <div style="font-size:.72rem;color:var(--text-muted)">ID: {{ $r->plugin_id }}</div>
                        </td>
                        <td class="py-2" style="color:var(--text-muted)">{{ $r->plugin_family ?? '—' }}</td>
                        <td class="py-2">
                            @if($r->severity)
                            @php $sc = match($r->severity){ 'Critical'=>'danger','High'=>'warning','Medium'=>'info','Low'=>'success',default=>'secondary' }; @endphp
                            <span class="badge text-bg-{{ $sc }}">{{ $r->severity }}</span>
                            @else —
                            @endif
                        </td>
                        <td class="py-2">
                            @if($r->originalFinding)
                            @php $ob = $r->originalFinding->statusBadgeClass(); @endphp
                            <span class="badge text-bg-{{ $ob }}">{{ $r->originalFinding->compliance_status }}</span>
                            @else
                            <span class="badge text-bg-warning">New</span>
                            @endif
                        </td>
                        <td class="py-2">
                            @php $vb = $r->statusBadgeClass(); @endphp
                            <span class="badge text-bg-{{ $vb }}">{{ $r->verification_status }}</span>
                        </td>
                        <td class="py-2">
                            <span class="text-muted" style="font-size:.75rem">{{ $r->compliance_result ?? '—' }}</span>
                        </td>
                    </tr>
                    @if($r->plugin_output)
                    <tr style="background:#fafbfc" data-vstatus="{{ $r->verification_status }}">
                        <td colspan="6" class="px-3 py-2">
                            <pre style="font-size:.72rem;white-space:pre-wrap;margin:0;color:#374151;max-height:100px;overflow:auto">{{ $r->plugin_output }}</pre>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            @if($verification->upload_status === 'completed')
                                No results found.
                            @else
                                Processing verification scan…
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($results->hasPages())
        <div class="card-footer bg-white d-flex justify-content-end">
            {{ $results->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script nonce="{{ csp_nonce() }}">
function filterTable(status) {
    document.querySelectorAll('#resultsTable tbody tr').forEach(function(row) {
        if (!status || row.getAttribute('data-vstatus') === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
@endpush
@endsection
