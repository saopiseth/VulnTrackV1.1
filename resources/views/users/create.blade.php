@extends('layouts.app')
@section('title', 'New User')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>New User</h4>
        <p>Create a new system user and assign a role.</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-sm" style="border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-weight:500;color:#374151;background:#fff">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

@include('users._form', ['user' => null, 'action' => route('users.store'), 'method' => 'POST'])
@endsection
