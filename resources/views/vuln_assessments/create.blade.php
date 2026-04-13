@extends('layouts.app')
@section('title', 'New VA Assessment')

@section('content')
<style>
    .form-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.75rem; margin-bottom:1.25rem; }
    .form-card h6 { font-size:.8rem; font-weight:700; color:rgb(118,151,7); text-transform:uppercase; letter-spacing:.8px; margin-bottom:1.25rem; padding-bottom:.6rem; border-bottom:2px solid rgb(152,194,10); }
    .form-label { font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
    .form-control, .form-select { border-radius:9px; border:1.5px solid #e2e8f0; font-size:.875rem; }
    .form-control:focus, .form-select:focus { border-color:rgb(152,194,10); box-shadow:0 0 0 3px rgba(152,194,10,.15); }
</style>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>New Assessment</h4>
        <p>Fill in the assessment details and upload scans after creation.</p>
    </div>
    <a href="{{ route('vuln-assessments.index') }}" class="btn btn-sm"
        style="border:1.5px solid rgb(152,194,10);border-radius:9px;color:rgb(118,151,7);background:#fff;font-weight:500">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form method="POST" action="{{ route('vuln-assessments.store') }}">
@csrf
<div class="form-card">
    <h6><i class="bi bi-info-circle me-2"></i>Assessment Details</h6>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Assessment Name <span style="color:#dc2626">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name') }}" placeholder="e.g. Q2 2026 Infrastructure Vulnerability Assessment" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4"
                placeholder="Brief description of scope, objectives, and systems in scope…">{{ old('description') }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Assessment Period — From</label>
            <input type="date" name="period_start" class="form-control @error('period_start') is-invalid @enderror"
                value="{{ old('period_start') }}">
            @error('period_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Assessment Period — To</label>
            <input type="date" name="period_end" class="form-control @error('period_end') is-invalid @enderror"
                value="{{ old('period_end') }}">
            @error('period_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('vuln-assessments.index') }}" class="btn btn-sm"
        style="border:1.5px solid #cbd5e1;border-radius:9px;color:#64748b;background:#fff;font-weight:500;padding:.45rem 1.2rem">
        Cancel
    </a>
    <button type="submit" class="btn btn-sm"
        style="background:rgb(152,194,10);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.4rem">
        <i class="bi bi-plus-lg me-1"></i> Create Assessment
    </button>
</div>
</form>
@endsection
