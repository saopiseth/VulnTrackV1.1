@extends('layouts.app')

@section('title', 'New Hardening Verification')

@section('content')
<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('hardening.dashboard') }}">Secure Configuration</a></li>
            <li class="breadcrumb-item"><a href="{{ route('hardening.verifications.index') }}">Verifications</a></li>
            <li class="breadcrumb-item active">New Verification</li>
        </ol>
    </nav>
    <h4><i class="bi bi-patch-check-fill me-2" style="color:var(--primary)"></i>New Hardening Verification</h4>
    <p>Upload a post-remediation Nessus scan to verify whether previous non-compliant findings are resolved.</p>
</div>

<style>
.form-card {
    background:#fff; border:1px solid var(--border); border-radius:14px;
    padding:1.5rem; margin-bottom:1.25rem; box-shadow:0 1px 4px rgba(0,0,0,.04);
}
.form-card h6 {
    font-size:.85rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
    color:var(--primary-dark); margin-bottom:1rem; padding-bottom:.6rem;
    border-bottom:2px solid color-mix(in srgb,var(--primary) 15%,white);
}
.form-label { font-size:.8rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
.form-control, .form-select { font-size:.875rem; border-radius:10px; border-color:var(--border); }
.form-control:focus, .form-select:focus { border-color:var(--primary); box-shadow:0 0 0 .2rem rgba(var(--primary-rgb),.15); }
</style>

@if($errors->any())
<div class="alert alert-danger mb-3">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('hardening.verifications.store') }}" enctype="multipart/form-data">
@csrf

{{-- Link to Assessment --}}
<div class="form-card">
    <h6><i class="bi bi-link-45deg me-2"></i>Select Hardening Assessment</h6>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Hardening Assessment <span class="text-danger">*</span></label>
            <select name="hardening_assessment_uuid" id="assessmentSelect"
                    class="form-select @error('hardening_assessment_uuid') is-invalid @enderror" required>
                <option value="">— Select an assessment —</option>
                @foreach($assessments as $a)
                <option value="{{ $a->uuid }}"
                    {{ (old('hardening_assessment_uuid') ?? $selectedAssessment?->uuid) === $a->uuid ? 'selected' : '' }}>
                    {{ $a->name }} ({{ $a->system_name }} / {{ $a->ip_address }})
                </option>
                @endforeach
            </select>
            @error('hardening_assessment_uuid')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Only completed assessments are listed.</div>
        </div>
    </div>
</div>

{{-- Verification Details --}}
<div class="form-card">
    <h6><i class="bi bi-calendar2-check me-2"></i>Verification Details</h6>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Verification Date <span class="text-danger">*</span></label>
            <input type="date" name="verification_date"
                   class="form-control @error('verification_date') is-invalid @enderror"
                   value="{{ old('verification_date', now()->format('Y-m-d')) }}" required>
            @error('verification_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Verified By</label>
            <input type="text" name="verified_by"
                   class="form-control @error('verified_by') is-invalid @enderror"
                   value="{{ old('verified_by', auth()->user()->name) }}"
                   placeholder="Name of person conducting verification">
            @error('verified_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- File Upload --}}
<div class="form-card">
    <h6><i class="bi bi-cloud-upload me-2"></i>Verification Nessus File Upload</h6>

    <div class="alert alert-info d-flex gap-2 mb-3" style="font-size:.82rem">
        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
        <div>
            Upload the verification scan file. The system will compare it against the initial assessment
            findings and generate a verification status for each finding.
            <strong>Initial assessment data will not be modified.</strong>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Verification Nessus File (.nessus / .xml / .csv) <span class="text-danger">*</span></label>
            <input type="file" name="nessus_file"
                   class="form-control @error('nessus_file') is-invalid @enderror"
                   accept=".nessus,.xml,.csv" required>
            @error('nessus_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Max 200 MB. Accepted: .nessus, .xml, .csv</div>
        </div>
        <div class="col-12">
            <label class="form-label">Verification Remarks</label>
            <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror"
                      rows="3" placeholder="Optional notes about this verification run…">{{ old('remarks') }}</textarea>
            @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end">
    <a href="{{ route('hardening.verifications.index') }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn px-4" style="background:var(--primary);color:#fff">
        <i class="bi bi-upload me-1"></i>Submit Verification
    </button>
</div>

</form>
@endsection
