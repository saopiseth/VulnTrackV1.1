@extends('layouts.app')
@section('title', 'User Management')

@section('content')

<style>
    .btn-primary-act {
        background:linear-gradient(135deg,var(--primary),var(--primary-dark)); color:#fff; border:none;
        border-radius:9px; font-weight:600; font-size:.875rem; padding:.5rem 1.1rem;
        text-decoration:none; display:inline-flex; align-items:center;
        box-shadow:0 4px 12px rgba(var(--primary-rgb),.3); transition:all .2s;
    }
    .btn-primary-act:hover { color:#fff; transform:translateY(-1px); box-shadow:0 6px 18px rgba(var(--primary-rgb),.4); }
    .stat-mini { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem 1.25rem; }
    .stat-mini .sm-val { font-size:1.5rem; font-weight:800; color:#0f172a; }
    .stat-mini .sm-lbl { font-size:.75rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.5px; }
    .user-table { border-radius:14px; overflow:hidden; border:1px solid #e2e8f0; background:#fff; }
    .user-table table { margin:0; font-size:.875rem; }
    .user-table thead th { background:#f8fafc; color:#64748b; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid #e2e8f0; padding:.75rem 1rem; white-space:nowrap; }
    .user-table tbody td { padding:.75rem 1rem; vertical-align:middle; border-color:#f1f5f9; color:#374151; }
    .user-table tbody tr:hover { background:#f8fdf0; }
    .filter-bar { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1.25rem; }
    .filter-bar .form-control, .filter-bar .form-select { font-size:.85rem; border-color:#e2e8f0; border-radius:8px; }
    .filter-bar .form-control:focus, .filter-bar .form-select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(var(--primary-rgb),.1); }
    .btn-act { padding:.25rem .55rem; border-radius:7px; font-size:.78rem; border:1px solid #e2e8f0; background:#fff; }
    .btn-act:hover { background:#f1f5f9; }
    .role-badge { padding:.22rem .7rem; border-radius:20px; font-size:.72rem; font-weight:700; display:inline-block; }
    .role-admin          { background:rgb(240,248,210); color:var(--primary-dark); }
    .role-assessor       { background:#f0fdf4; color:#15803d; }
    .role-patch-admin    { background:#e0f2fe; color:#0369a1; }
    .avatar-sm { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:700; color:#fff; flex-shrink:0; background:linear-gradient(135deg,var(--primary),var(--primary-dark)); }
</style>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4>User Management</h4>
        <p>Manage system users and their roles.</p>
    </div>
    @can('create', App\Models\User::class)
    <a href="{{ route('users.create') }}" class="btn-primary-act">
        <i class="bi bi-person-plus-fill me-1"></i> New User
    </a>
    @endcan
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-mini">
            <div class="sm-val">{{ $stats['total'] }}</div>
            <div class="sm-lbl">Total Users</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini" style="border-left:3px solid var(--primary-dark)">
            <div class="sm-val" style="color:var(--primary-dark)">{{ $stats['administrators'] }}</div>
            <div class="sm-lbl">Administrators</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini" style="border-left:3px solid #15803d">
            <div class="sm-val" style="color:#15803d">{{ $stats['assessors'] }}</div>
            <div class="sm-lbl">Assessors</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini" style="border-left:3px solid #0369a1">
            <div class="sm-val" style="color:#0369a1">{{ $stats['patch_administrators'] }}</div>
            <div class="sm-lbl">Patch Admins</div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert d-flex align-items-center gap-2 mb-3" style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
</div>
@endif

{{-- Filter --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-end">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0"><i class="bi bi-search" style="color:#94a3b8"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search name or email..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select">
                <option value="">All Roles</option>
                <option value="administrator"      {{ request('role') === 'administrator'      ? 'selected' : '' }}>Administrator</option>
                <option value="assessor"           {{ request('role') === 'assessor'           ? 'selected' : '' }}>Assessor</option>
                <option value="patch_administrator" {{ request('role') === 'patch_administrator' ? 'selected' : '' }}>Patch Administrator</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary-act flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="{{ route('users.index') }}" class="btn btn-act flex-fill text-center">Clear</a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="user-table">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td style="color:#94a3b8;font-size:.75rem">{{ $u->id }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm">{{ strtoupper(substr($u->name, 0, 1)) }}</div>
                            <div>
                                <div style="font-weight:600;color:#0f172a">{{ $u->name }}</div>
                                @if($u->id === auth()->id())
                                    <span style="font-size:.7rem;background:rgb(240,248,210);color:var(--primary-dark);padding:.05rem .4rem;border-radius:5px;font-weight:600">You</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="color:#64748b">{{ $u->email }}</td>
                    <td>
                        @php
                            $roleBadgeClass = match($u->role) {
                                'administrator'       => 'role-admin',
                                'assessor'            => 'role-assessor',
                                'patch_administrator' => 'role-patch-admin',
                                default               => 'role-assessor',
                            };
                            $roleIcon = match($u->role) {
                                'administrator'       => 'shield-fill-check',
                                'assessor'            => 'person-badge-fill',
                                'patch_administrator' => 'eye-fill',
                                default               => 'person-badge-fill',
                            };
                            $roleLabel = match($u->role) {
                                'administrator'       => 'Administrator',
                                'assessor'            => 'Assessor',
                                'patch_administrator' => 'Patch Admin',
                                default               => ucfirst($u->role),
                            };
                        @endphp
                        <span class="role-badge {{ $roleBadgeClass }}">
                            <i class="bi bi-{{ $roleIcon }} me-1"></i>{{ $roleLabel }}
                        </span>
                    </td>
                    <td style="color:#94a3b8;font-size:.82rem">{{ $u->created_at->format('d M Y') }}</td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('users.show', $u) }}" class="btn btn-act" title="View">
                            <i class="bi bi-eye" style="color:var(--primary-dark)"></i>
                        </a>
                        @can('update', $u)
                        <a href="{{ route('users.edit', $u) }}" class="btn btn-act ms-1" title="Edit">
                            <i class="bi bi-pencil" style="color:#d97706"></i>
                        </a>
                        @endcan
                        @can('delete', $u)
                        <form method="POST" action="{{ route('users.destroy', $u) }}" class="d-inline ms-1"
                              onsubmit="return confirm('Delete user {{ $u->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-act" title="Delete">
                                <i class="bi bi-trash" style="color:#dc2626"></i>
                            </button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5" style="color:#94a3b8">
                        <i class="bi bi-people" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
                        No users found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-top:1px solid #f1f5f9">
        <p class="mb-0" style="font-size:.8rem;color:#64748b">
            Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }} users
        </p>
        {{ $users->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

@endsection
