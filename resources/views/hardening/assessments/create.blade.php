@extends('layouts.app')

@section('title', 'New Hardening Assessment')

@section('content')
<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('hardening.dashboard') }}">Secure Configuration</a></li>
            <li class="breadcrumb-item"><a href="{{ route('hardening.assessments.index') }}">Hardening Assessments</a></li>
            <li class="breadcrumb-item active">New Assessment</li>
        </ol>
    </nav>
    <h4><i class="bi bi-clipboard2-plus-fill me-2" style="color:var(--primary)"></i>New Hardening Assessment</h4>
    <p>Upload the initial Nessus scan for secure configuration baseline assessment.</p>
</div>

<style>
.form-card {
    background:#fff;
    border:1px solid var(--border);
    border-radius:14px;
    padding:1.5rem;
    margin-bottom:1.25rem;
    box-shadow:0 1px 4px rgba(0,0,0,.04);
}
.form-card h6 {
    font-size:.85rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.5px;
    color:var(--primary-dark);
    margin-bottom:1rem;
    padding-bottom:.6rem;
    border-bottom:2px solid color-mix(in srgb,var(--primary) 15%,white);
}
.form-label { font-size:.8rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
.form-control, .form-select { font-size:.875rem; border-radius:10px; border-color:var(--border); }
.form-control:focus, .form-select:focus { border-color:var(--primary); box-shadow:0 0 0 .2rem rgba(var(--primary-rgb),.15); }
</style>

<form method="POST" action="{{ route('hardening.assessments.store') }}" enctype="multipart/form-data">
@csrf

{{-- Assessment Information --}}
<div class="form-card">
    <h6><i class="bi bi-info-circle me-2"></i>Assessment Information</h6>
    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label">Assessment Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" placeholder="e.g. Windows Server 2019 Baseline Assessment" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Assessment Date <span class="text-danger">*</span></label>
            <input type="date" name="assessment_date" class="form-control @error('assessment_date') is-invalid @enderror"
                   value="{{ old('assessment_date', now()->format('Y-m-d')) }}" required>
            @error('assessment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Criticality Level <span class="text-danger">*</span></label>
            <select name="criticality_level" class="form-select @error('criticality_level') is-invalid @enderror" required>
                @foreach(['Critical','High','Medium','Low'] as $level)
                <option value="{{ $level }}" {{ old('criticality_level','Medium') === $level ? 'selected' : '' }}>{{ $level }}</option>
                @endforeach
            </select>
            @error('criticality_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- System Details --}}
<div class="form-card">
    <h6><i class="bi bi-pc-display me-2"></i>System Details</h6>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">System Name <span class="text-danger">*</span></label>
            <input type="text" name="system_name" class="form-control @error('system_name') is-invalid @enderror"
                   value="{{ old('system_name') }}" placeholder="e.g. PROD-DC-01" required>
            @error('system_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Hostname</label>
            <input type="text" name="hostname" class="form-control @error('hostname') is-invalid @enderror"
                   value="{{ old('hostname') }}" placeholder="e.g. dc01.corp.local">
            @error('hostname')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">IP Address <span class="text-danger">*</span></label>
            <input type="text" name="ip_address" class="form-control @error('ip_address') is-invalid @enderror"
                   value="{{ old('ip_address') }}" placeholder="e.g. 192.168.1.10" required>
            @error('ip_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Operating System</label>
            <input type="text" name="operating_system" class="form-control @error('operating_system') is-invalid @enderror"
                   value="{{ old('operating_system') }}" placeholder="e.g. Windows Server 2019">
            @error('operating_system')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Environment <span class="text-danger">*</span></label>
            <select name="environment" class="form-select @error('environment') is-invalid @enderror" required>
                @foreach(['Production','UAT','Development','Internal','DMZ','Cloud'] as $env)
                <option value="{{ $env }}" {{ old('environment','Production') === $env ? 'selected' : '' }}>{{ $env }}</option>
                @endforeach
            </select>
            @error('environment')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Scope Type</label>
            <input type="text" name="scope_type" class="form-control @error('scope_type') is-invalid @enderror"
                   value="{{ old('scope_type') }}" placeholder="e.g. Domain Controller, Web Server, Database">
            @error('scope_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- Ownership --}}
<div class="form-card">
    <h6><i class="bi bi-person-badge me-2"></i>Ownership</h6>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Asset Owner</label>
            <input type="text" name="asset_owner" class="form-control @error('asset_owner') is-invalid @enderror"
                   value="{{ old('asset_owner') }}" placeholder="Department or team that owns the asset">
            @error('asset_owner')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">System Owner</label>
            <input type="text" name="system_owner" class="form-control @error('system_owner') is-invalid @enderror"
                   value="{{ old('system_owner') }}" placeholder="Person responsible for the system">
            @error('system_owner')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- File Upload --}}
<div class="form-card">
    <h6><i class="bi bi-cloud-upload me-2"></i>Initial Nessus File Upload</h6>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Nessus Scan File (.nessus / .xml / .csv) <span class="text-danger">*</span></label>
            <input type="file" name="nessus_file" id="nessus_file"
                   class="form-control @error('nessus_file') is-invalid @enderror"
                   accept=".nessus,.xml,.csv" required>
            @error('nessus_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Max 200 MB. Accepted: .nessus, .xml, .csv</div>
        </div>
        <div class="col-12">
            <label class="form-label">Assessment Remarks</label>
            <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror"
                      rows="3" placeholder="Optional notes about this assessment…">{{ old('remarks') }}</textarea>
            @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- Submit --}}
<div class="d-flex gap-2 justify-content-end">
    <a href="{{ route('hardening.assessments.index') }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn px-4" style="background:var(--primary);color:#fff">
        <i class="bi bi-upload me-1"></i>Create Assessment
    </button>
</div>

</form>
@endsection
