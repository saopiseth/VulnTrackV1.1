@extends('layouts.app')
@section('title', 'User — ' . $user->name)

@section('content')

<style>
    .detail-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .detail-card h6 { font-size:.8rem; font-weight:700; color:#4f46e5; text-transform:uppercase; letter-spacing:.8px; margin-bottom:1.25rem; padding-bottom:.6rem; border-bottom:1px solid #f1f5f9; }
    .dl { font-size:.75rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px; }
    .dv { font-size:.9rem; color:#0f172a; font-weight:500; margin-top:.2rem; }
    .perm-row { display:flex; align-items:center; justify-content:space-between; padding:.55rem 0; border-bottom:1px solid #f8fafc; }
    .perm-row:last-child { border-bottom:none; }
    .perm-label { font-size:.85rem; font-weight:500; color:#374151; }
    .perm-yes { background:#d1fae5; color:#065f46; padding:.18rem .6rem; border-radius:20px; font-size:.72rem; font-weight:700; }
    .perm-no  { background:#fee2e2; color:#991b1b; padding:.18rem .6rem; border-radius:20px; font-size:.72rem; font-weight:700; }
</style>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4>{{ $user->name }}</h4>
        <p>User ID #{{ $user->id }} &middot; Joined {{ $user->created_at->format('d M Y') }}</p>
    </div>
    <div class="d-flex gap-2">
        @can('update', $user)
        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1rem">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @endcan
        <a href="{{ route('users.index') }}" class="btn btn-sm" style="border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-weight:500;color:#374151;background:#fff">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">

        {{-- Profile --}}
        <div class="detail-card">
            <h6><i class="bi bi-person me-2"></i>Profile</h6>
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:800;color:#fff;flex-shrink:0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <div style="font-size:1.1rem;font-weight:700;color:#0f172a">{{ $user->name }}</div>
                    <div style="color:#64748b;font-size:.875rem">{{ $user->email }}</div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="dl">Role</div>
                    <div class="dv mt-1">
                        <span style="background:{{ $user->isAdministrator() ? '#ede9fe' : '#f0fdf4' }};color:{{ $user->isAdministrator() ? '#6d28d9' : '#15803d' }};padding:.3rem .85rem;border-radius:20px;font-size:.8rem;font-weight:700">
                            <i class="bi bi-{{ $user->isAdministrator() ? 'shield-fill-check' : 'person-badge-fill' }} me-1"></i>
                            {{ ucfirst($user->role) }}
                        </span>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="dl">Member Since</div>
                    <div class="dv">{{ $user->created_at->format('d M Y, h:i A') }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="dl">Two-Factor Auth</div>
                    <div class="dv mt-1">
                        @if($user->mfa_enabled)
                            <span style="background:rgb(240,248,210);color:var(--primary-dark);padding:.3rem .85rem;border-radius:20px;font-size:.8rem;font-weight:700">
                                <i class="bi bi-shield-check-fill me-1"></i>Enabled
                            </span>
                        @else
                            <span style="background:#f1f5f9;color:#64748b;padding:.3rem .85rem;border-radius:20px;font-size:.8rem;font-weight:700">
                                <i class="bi bi-shield-slash me-1"></i>Disabled
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Permissions --}}
        <div class="detail-card">
            <h6><i class="bi bi-key me-2"></i>Role Permissions</h6>
            @php
                $isAdmin = $user->isAdministrator();
                $perms = [
                    ['action' => 'View Assessments',   'scope' => 'All records',             'allowed' => true],
                    ['action' => 'Create Assessments', 'scope' => 'All records',             'allowed' => true],
                    ['action' => 'Edit Assessments',   'scope' => 'All records',             'allowed' => true],
                    ['action' => 'Delete Assessments', 'scope' => 'Administrators only',     'allowed' => $isAdmin],
                    ['action' => 'Manage Users',       'scope' => 'Administrators only',     'allowed' => $isAdmin],
                    ['action' => 'Create Users',       'scope' => 'Administrators only',     'allowed' => $isAdmin],
                    ['action' => 'Edit Users',         'scope' => 'Administrators only',     'allowed' => $isAdmin],
                    ['action' => 'Delete Users',       'scope' => 'Administrators only',     'allowed' => $isAdmin],
                ];
            @endphp
            @foreach($perms as $perm)
            <div class="perm-row">
                <div>
                    <div class="perm-label">{{ $perm['action'] }}</div>
                    <div style="font-size:.75rem;color:#94a3b8">{{ $perm['scope'] }}</div>
                </div>
                <span class="{{ $perm['allowed'] ? 'perm-yes' : 'perm-no' }}">
                    <i class="bi bi-{{ $perm['allowed'] ? 'check-lg' : 'x-lg' }}"></i>
                    {{ $perm['allowed'] ? 'Allowed' : 'Denied' }}
                </span>
            </div>
            @endforeach
        </div>

    </div>

    <div class="col-lg-4">

        {{-- Meta --}}
        <div class="detail-card">
            <h6><i class="bi bi-info-circle me-2"></i>Record Info</h6>
            <div class="d-flex flex-column gap-3">
                <div><div class="dl">User ID</div><div class="dv">#{{ $user->id }}</div></div>
                <div><div class="dl">Created</div><div class="dv">{{ $user->created_at->format('d M Y, h:i A') }}</div></div>
                <div><div class="dl">Last Updated</div><div class="dv">{{ $user->updated_at->format('d M Y, h:i A') }}</div></div>
            </div>
        </div>

        {{-- Delete --}}
        @can('delete', $user)
        <div class="detail-card" style="border-color:#fee2e2">
            <h6 style="color:#dc2626"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h6>
            <p style="font-size:.82rem;color:#64748b;margin-bottom:1rem">Permanently delete this user account.</p>
            <form method="POST" action="{{ route('users.destroy', $user) }}"
                  onsubmit="return confirm('Delete user {{ $user->name }}? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn w-100" style="background:#fee2e2;color:#dc2626;border:1.5px solid #fca5a5;border-radius:9px;font-weight:600;font-size:.85rem">
                    <i class="bi bi-trash me-1"></i> Delete User
                </button>
            </form>
        </div>
        @endcan

    </div>
</div>

@endsection
