@extends('layouts.app')
@section('title', 'Edit User — ' . $user->name)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>Edit User</h4>
        <p>Update details for <strong>{{ $user->name }}</strong>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('users.show', $user) }}" class="btn btn-sm" style="border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-weight:500;color:#374151;background:#fff">
            <i class="bi bi-eye me-1"></i> View
        </a>
        <a href="{{ route('users.index') }}" class="btn btn-sm" style="border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-weight:500;color:#374151;background:#fff">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

@include('users._form', ['user' => $user, 'action' => route('users.update', $user), 'method' => 'PUT'])
@endsection
