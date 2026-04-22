@extends('layouts.app')
@section('title', 'User Groups')

@section('content')
<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-muted: rgb(232,244,195); }
    .group-card {
        background:#fff; border:1px solid #e8f5c2; border-radius:12px;
        padding:1.1rem 1.25rem; transition:box-shadow .15s, border-color .15s;
    }
    .group-card:hover { box-shadow:0 4px 18px rgba(118,151,7,.12); border-color:var(--lime); }
</style>

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size:.73rem">
                <li class="breadcrumb-item">
                    <a href="{{ route('users.index') }}" style="color:#94a3b8;text-decoration:none">Users</a>
                </li>
                <li class="breadcrumb-item active" style="color:#64748b">User Groups</li>
            </ol>
        </nav>
        <h5 style="margin:0;font-weight:700;color:#0f172a">
            <i class="bi bi-people-fill me-2" style="color:var(--lime)"></i>User Groups
        </h5>
        <p class="mb-0 mt-1" style="font-size:.82rem;color:#64748b">Organise users into groups for access control and assignment.</p>
    </div>
    <a href="{{ route('user-groups.create') }}" class="btn btn-sm"
        style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.1rem">
        <i class="bi bi-plus-lg me-1"></i>New Group
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" style="border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Search --}}
<form method="GET" class="mb-3 d-flex gap-2">
    <div class="input-group input-group-sm" style="max-width:320px">
        <span class="input-group-text" style="border-radius:8px 0 0 8px;background:#f8fafc"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control" placeholder="Search groups…"
            value="{{ request('search') }}" style="border-radius:0 8px 8px 0">
    </div>
    @if(request('search'))
    <a href="{{ route('user-groups.index') }}" class="btn btn-sm"
        style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff">
        <i class="bi bi-x"></i>
    </a>
    @endif
</form>

{{-- Stats strip --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div style="background:#fff;border:1px solid #e8f5c2;border-radius:10px;padding:.7rem 1rem;display:flex;align-items:center;gap:.75rem">
            <div style="width:36px;height:36px;border-radius:9px;background:#e8f5c2;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="bi bi-collection-fill" style="color:var(--lime-dark)"></i>
            </div>
            <div>
                <div style="font-size:.62rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.4px">Total Groups</div>
                <div style="font-size:1.3rem;font-weight:800;color:#0f172a;line-height:1.2">{{ $groups->total() }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Groups list --}}
@forelse($groups as $group)
<div class="group-card mb-2">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div style="min-width:0;flex:1">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span style="width:32px;height:32px;border-radius:8px;background:var(--lime-muted);
                              display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-people-fill" style="color:var(--lime-dark);font-size:.85rem"></i>
                </span>
                <a href="{{ route('user-groups.show', $group) }}"
                   style="font-weight:700;color:#0f172a;font-size:.92rem;text-decoration:none">
                    {{ $group->name }}
                </a>
                <span style="font-size:.7rem;font-weight:700;background:#e8f5c2;color:var(--lime-dark);
                              border-radius:20px;padding:.1rem .5rem">
                    {{ $group->members_count }} member{{ $group->members_count !== 1 ? 's' : '' }}
                </span>
            </div>
            @if($group->description)
            <div style="font-size:.78rem;color:#64748b;margin-left:2.75rem">{{ Str::limit($group->description, 100) }}</div>
            @endif
            <div style="font-size:.7rem;color:#94a3b8;margin-left:2.75rem;margin-top:.2rem">
                <i class="bi bi-person me-1"></i>Created by {{ $group->creator?->name ?? '—' }}
                &nbsp;·&nbsp;
                <i class="bi bi-calendar3 me-1"></i>{{ $group->created_at->format('d M Y') }}
            </div>
        </div>
        <div class="d-flex gap-1 flex-shrink-0">
            <a href="{{ route('user-groups.show', $group) }}"
               class="btn btn-sm" style="border-radius:8px;border:1px solid #e2e8f0;color:#64748b;padding:.3rem .65rem;font-size:.78rem">
                <i class="bi bi-eye"></i>
            </a>
            <a href="{{ route('user-groups.edit', $group) }}"
               class="btn btn-sm" style="border-radius:8px;border:1.5px solid var(--lime);color:var(--lime-dark);
                      background:var(--lime-muted);padding:.3rem .65rem;font-size:.78rem">
                <i class="bi bi-pencil"></i>
            </a>
            <form method="POST" action="{{ route('user-groups.destroy', $group) }}" class="d-inline"
                  onsubmit="return confirm('Delete group \'{{ $group->name }}\'?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm"
                    style="border-radius:8px;border:1px solid #fca5a5;color:#dc2626;background:#fff8f8;padding:.3rem .65rem;font-size:.78rem">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </div>
</div>
@empty
<div style="background:#fff;border:2px dashed #e8f5c2;border-radius:14px;padding:4rem 2rem;text-align:center;color:#94a3b8">
    <i class="bi bi-people" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.3;color:var(--lime)"></i>
    <div style="font-weight:600;font-size:1rem;color:#64748b;margin-bottom:.4rem">No groups yet</div>
    <p style="font-size:.83rem;margin-bottom:1.2rem">Create a group to organise users for assignment and access control.</p>
    <a href="{{ route('user-groups.create') }}" class="btn btn-sm"
       style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.4rem">
        <i class="bi bi-plus-lg me-1"></i>Create First Group
    </a>
</div>
@endforelse

@if($groups->hasPages())
<div class="d-flex justify-content-center mt-3">{{ $groups->links() }}</div>
@endif
@endsection
