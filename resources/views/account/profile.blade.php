@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,rgb(152,194,10),rgb(100,140,5));display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:800;color:#fff;flex-shrink:0">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h4 class="mb-0">My Profile</h4>
            <p class="mb-0">Manage your personal information and password</p>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:12px;border:none;background:#f0fdf4;color:#166534;">
    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
</div>
@endif

<div class="row g-4">

    {{-- ── Personal Information ── --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="fw-700 mb-1" style="color:#0f172a;font-weight:700">Personal Information</h6>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">Update your display name and email address.</p>

                <form method="POST" action="{{ route('account.profile.update') }}">
                    @csrf @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.85rem">Full Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}"
                               style="border-radius:10px;border-color:#e2e8f0;font-size:.9rem">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:.85rem">Email Address</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}"
                               style="border-radius:10px;border-color:#e2e8f0;font-size:.9rem">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-primary px-4" style="background:rgb(152,194,10);border-color:rgb(152,194,10);border-radius:10px;font-size:.875rem;font-weight:600">
                            Save Changes
                        </button>
                        <span style="font-size:.8rem;color:#94a3b8">Role: <span style="background:rgb(232,244,195);color:rgb(118,151,7);font-size:.7rem;font-weight:700;padding:.1rem .5rem;border-radius:5px;text-transform:uppercase">{{ $user->role }}</span></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Account Summary ── --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body p-4">
                <h6 class="fw-700 mb-4" style="color:#0f172a;font-weight:700">Account Summary</h6>

                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center gap-3 p-3" style="background:#f8fafc;border-radius:12px">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgb(232,244,195);display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-person-fill" style="color:rgb(118,151,7)"></i>
                        </div>
                        <div>
                            <div style="font-size:.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px">Name</div>
                            <div style="font-size:.9rem;font-weight:600;color:#0f172a">{{ $user->name }}</div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 p-3" style="background:#f8fafc;border-radius:12px">
                        <div style="width:38px;height:38px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-envelope-fill" style="color:#3b82f6"></i>
                        </div>
                        <div>
                            <div style="font-size:.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px">Email</div>
                            <div style="font-size:.9rem;font-weight:600;color:#0f172a">{{ $user->email }}</div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 p-3" style="background:#f8fafc;border-radius:12px">
                        <div style="width:38px;height:38px;border-radius:10px;{{ $user->mfa_enabled ? 'background:#f0fdf4' : 'background:#fef9c3' }};display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-shield-{{ $user->mfa_enabled ? 'fill-check' : 'exclamation' }}" style="color:{{ $user->mfa_enabled ? '#16a34a' : '#ca8a04' }}"></i>
                        </div>
                        <div>
                            <div style="font-size:.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px">MFA</div>
                            <div style="font-size:.9rem;font-weight:600;color:#0f172a">{{ $user->mfa_enabled ? 'Enabled' : 'Disabled' }}</div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 p-3" style="background:#f8fafc;border-radius:12px">
                        <div style="width:38px;height:38px;border-radius:10px;background:#f5f3ff;display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-calendar3" style="color:#7c3aed"></i>
                        </div>
                        <div>
                            <div style="font-size:.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px">Member Since</div>
                            <div style="font-size:.9rem;font-weight:600;color:#0f172a">{{ $user->created_at->format('d M Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Change Password ── --}}
    <div class="col-lg-7" id="password">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="fw-700 mb-1" style="color:#0f172a;font-weight:700">Change Password</h6>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">Choose a strong password — minimum 8 characters with mixed case and numbers.</p>

                @if($errors->has('current_password'))
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="border-radius:10px;border:none;font-size:.875rem;background:#fef2f2;color:#991b1b">
                    <i class="bi bi-exclamation-circle-fill"></i> {{ $errors->first('current_password') }}
                </div>
                @endif

                <form method="POST" action="{{ route('account.password.update') }}">
                    @csrf @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.85rem">Current Password</label>
                        <input type="password" name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               style="border-radius:10px;border-color:#e2e8f0;font-size:.9rem">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.85rem">New Password</label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               style="border-radius:10px;border-color:#e2e8f0;font-size:.9rem">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:.85rem">Confirm New Password</label>
                        <input type="password" name="password_confirmation"
                               class="form-control"
                               style="border-radius:10px;border-color:#e2e8f0;font-size:.9rem">
                    </div>

                    <button type="submit" class="btn px-4" style="background:#0f172a;color:#fff;border-radius:10px;font-size:.875rem;font-weight:600">
                        <i class="bi bi-lock-fill me-1"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
