@extends('layouts.app')
@section('title', 'New Assessment')

@section('content')

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>New Project Assessment</h4>
        <p>Fill in the details below to create a new assessment record.</p>
    </div>
    <a href="{{ route('assessments.index') }}" class="btn btn-sm" style="border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-weight:500;color:#374151;background:#fff">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

@include('assessments._form', ['assessment' => null, 'action' => route('assessments.store'), 'method' => 'POST'])

@endsection
