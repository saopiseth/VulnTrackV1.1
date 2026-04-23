@extends('layouts.app')
@section('title', $group->name . ' — User Group')

@section('content')
<style>
    :root { --lime: var(--primary); --lime-dark: var(--primary-dark); --lime-muted: rgb(232,244,195); }
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .section-label {
        font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.7px;
        color:var(--lime-dark); margin-bottom:.85rem; padding-bottom:.4rem;
        border-bottom:2px solid var(--lime); display:flex; align-items:center; gap:.4rem;
    }
    .member-row {
        display:flex; align-items:center; gap:.75rem; padding:.6rem .85rem;
        border:1px solid #e8f5c2; border-radius:10px; background:#fff;
    }
    .member-avatar {
        width:34px; height:34px; border-radius:50%; display:flex; align-items:center;
        justify-content:center; font-size:.8rem; font-weight:700; color:#fff;
        background:linear-gradient(135deg, var(--lime-dark), rgb(80,120,5)); flex-shrink:0;
    }
</style>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size:.73rem">
                <li class="breadcrumb-item"><a href="{{ route('users.index') }}" style="color:#94a3b8;text-decoration:none">Users</a></li>
                <li class="breadcrumb-item"><a href="{{ route('user-groups.index') }}" style="color:#94a3b8;text-decoration:none">Groups</a></li>
                <li class="breadcrumb-item active" style="color:#64748b">{{ $group->name }}</li>
            </ol>
        </nav>
        <h5 style="margin:0;font-weight:700;color:#0f172a">
            <i class="bi bi-people-fill me-2" style="color:var(--lime)"></i>{{ $group->name }}
        </h5>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user-groups.edit', $group) }}" class="btn btn-sm"
            style="background:var(--lime-muted);border:1.5px solid var(--lime);color:var(--lime-dark);border-radius:9px;font-weight:600;font-size:.81rem">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <form method="POST" action="{{ route('user-groups.destroy', $group) }}" class="d-inline"
              onsubmit="return confirm('Delete group \'{{ $group->name }}\'?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm"
                style="border:1.5px solid #fca5a5;color:#dc2626;background:#fff8f8;border-radius:9px;font-weight:600;font-size:.81rem">
                <i class="bi bi-trash me-1"></i>Delete
            </button>
        </form>
        <a href="{{ route('user-groups.index') }}" class="btn btn-sm"
            style="border:1.5px solid #cbd5e1;border-radius:9px;color:#64748b;background:#fff;font-weight:500;font-size:.81rem">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" style="border-radius:10px;font-size:.85rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">

    {{-- Left: details --}}
    <div class="col-lg-4">
        <div class="va-card">
            <div class="section-label"><i class="bi bi-info-circle-fill"></i>Group Details</div>
            <dl class="mb-0" style="font-size:.85rem">
                <dt style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:.2rem">Name</dt>
                <dd style="color:#0f172a;font-weight:600;margin-bottom:.9rem">{{ $group->name }}</dd>

                <dt style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:.2rem">Description</dt>
                <dd style="color:#374151;margin-bottom:.9rem">{{ $group->description ?: '—' }}</dd>

                <dt style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:.2rem">Created by</dt>
                <dd style="color:#374151;margin-bottom:.9rem">{{ $group->creator?->name ?? '—' }}</dd>

                <dt style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:.2rem">Created at</dt>
                <dd style="color:#374151;margin-bottom:0">{{ $group->created_at->format('d M Y, H:i') }}</dd>
            </dl>
        </div>

        {{-- Stats --}}
        <div style="background:#fff;border:1px solid #e8f5c2;border-radius:12px;padding:1rem 1.25rem;display:flex;align-items:center;gap:.9rem">
            <div style="width:44px;height:44px;border-radius:11px;background:var(--lime-muted);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="bi bi-person-lines-fill" style="color:var(--lime-dark);font-size:1.15rem"></i>
            </div>
            <div>
                <div style="font-size:1.5rem;font-weight:800;color:#0f172a;line-height:1.1">{{ $group->members->count() }}</div>
                <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">Member{{ $group->members->count() !== 1 ? 's' : '' }}</div>
            </div>
        </div>
    </div>

    {{-- Right: members --}}
    <div class="col-lg-8">
        <div class="va-card">
            <div class="section-label"><i class="bi bi-person-check-fill"></i>Members</div>

            @if($group->members->isNotEmpty())
            <div style="display:flex;flex-direction:column;gap:.45rem">
                @foreach($group->members->sortBy('name') as $member)
                <div class="member-row">
                    <div class="member-avatar">{{ strtoupper(substr($member->name, 0, 1)) }}</div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;color:#0f172a;font-size:.86rem;line-height:1.2">{{ $member->name }}</div>
                        <div style="font-size:.73rem;color:#94a3b8">{{ $member->email }}</div>
                    </div>
                    <span style="font-size:.65rem;font-weight:700;border-radius:20px;padding:.1rem .5rem;flex-shrink:0;
                        {{ $member->role === 'administrator' ? 'background:#fee2e2;color:#991b1b' : 'background:#e8f5c2;color:var(--lime-dark)' }}">
                        {{ ucfirst($member->role) }}
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <div style="text-align:center;padding:2.5rem;color:#94a3b8;font-size:.84rem">
                <i class="bi bi-people" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3;color:var(--lime)"></i>
                No members in this group.
                <a href="{{ route('user-groups.edit', $group) }}" style="display:block;margin-top:.75rem;color:var(--lime-dark);font-weight:600;text-decoration:none">
                    <i class="bi bi-plus-circle me-1"></i>Add members
                </a>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
