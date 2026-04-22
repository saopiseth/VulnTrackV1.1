@extends('layouts.app')
@section('title', 'Settings')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#0f172a,#1e293b);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;flex-shrink:0">
            <i class="bi bi-gear-fill"></i>
        </div>
        <div>
            <h4 class="mb-0">Settings</h4>
            <p class="mb-0">Configure your account preferences and security</p>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:12px;border:none;background:#f0fdf4;color:#166534;">
    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
</div>
@endif

@php
    $logoPath    = \App\Models\SiteSetting::get('logo_path');
    $companyName = \App\Models\SiteSetting::get('company_name', 'Security Assessment');
@endphp

<div class="row g-4">

    {{-- ── Company Name (admin only) ── --}}
    @if(Auth::user()->isAdministrator())
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="mb-1" style="color:#0f172a;font-weight:700">
                    <i class="bi bi-building me-2" style="color:rgb(152,194,10)"></i>Company Name
                </h6>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">
                    Displayed in the sidebar next to the logo.
                </p>
                <form method="POST" action="{{ route('account.company-name.update') }}" class="d-flex gap-2 align-items-start flex-wrap">
                    @csrf @method('PATCH')
                    <div style="flex:1;min-width:200px;max-width:360px">
                        <input type="text" name="company_name"
                            class="form-control @error('company_name') is-invalid @enderror"
                            value="{{ old('company_name', $companyName) }}"
                            placeholder="e.g. Acme Corp"
                            maxlength="80"
                            style="border-radius:9px;font-size:.875rem">
                        @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-sm"
                        style="background:rgb(152,194,10);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.5rem 1.1rem;font-size:.85rem;white-space:nowrap">
                        <i class="bi bi-check-lg me-1"></i>Save
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ── App Logo (admin only) ── --}}
    @if(Auth::user()->isAdministrator())
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="mb-1" style="color:#0f172a;font-weight:700">
                    <i class="bi bi-image-fill me-2" style="color:rgb(152,194,10)"></i>Application Logo
                </h6>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">
                    Displayed in the sidebar. PNG, JPG, SVG or WebP — max 2 MB.
                </p>

                <div class="d-flex align-items-center gap-4 flex-wrap">
                    {{-- Current logo preview --}}
                    <div style="width:72px;height:72px;border-radius:14px;border:2px solid #e8f5c2;
                        background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                        @if($logoPath)
                            <img src="{{ Storage::url($logoPath) }}" alt="Current logo"
                                 style="width:64px;height:64px;object-fit:contain;border-radius:10px">
                        @else
                            <img src="{{ asset('favicon.ico') }}" alt="Default logo"
                                 style="width:40px;height:40px;object-fit:contain;opacity:.5">
                        @endif
                    </div>

                    <div style="flex:1;min-width:200px">
                        <form method="POST" action="{{ route('account.logo.upload') }}"
                              enctype="multipart/form-data" id="logo-form">
                            @csrf
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <label style="cursor:pointer">
                                    <input type="file" name="logo" id="logo-input" accept="image/*"
                                           style="display:none" onchange="previewLogo(this)">
                                    <span class="btn btn-sm"
                                        style="border:1.5px solid rgb(152,194,10);border-radius:9px;color:rgb(118,151,7);
                                               background:#fff;font-weight:600;font-size:.82rem;padding:.4rem .9rem">
                                        <i class="bi bi-upload me-1"></i>Choose File
                                    </span>
                                </label>
                                <span id="logo-filename" style="font-size:.78rem;color:#94a3b8">No file chosen</span>
                                <button type="submit" id="logo-submit" class="btn btn-sm" disabled
                                    style="background:rgb(152,194,10);color:#fff;border-radius:9px;font-weight:600;
                                           border:none;padding:.4rem .9rem;font-size:.82rem">
                                    <i class="bi bi-check-lg me-1"></i>Upload
                                </button>
                            </div>
                            @error('logo')
                            <div style="font-size:.78rem;color:#dc2626;margin-top:.4rem">{{ $message }}</div>
                            @enderror
                        </form>

                        @if($logoPath)
                        <form method="POST" action="{{ route('account.logo.delete') }}" class="mt-2"
                              onsubmit="return confirm('Remove custom logo and restore default?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm"
                                style="border:1px solid #fca5a5;color:#dc2626;background:#fff8f8;
                                       border-radius:8px;font-size:.78rem;font-weight:600">
                                <i class="bi bi-trash me-1"></i>Remove Logo
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
    @endif

    {{-- ── Security Settings ── --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="mb-1" style="color:#0f172a;font-weight:700">Security</h6>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">Control authentication and access security options.</p>

                <form method="POST" action="{{ route('account.settings.update') }}">
                    @csrf @method('PATCH')

                    {{-- MFA Toggle --}}
                    <div class="d-flex align-items-start justify-content-between p-4 mb-3" style="background:#f8fafc;border-radius:14px;border:1.5px solid #e2e8f0">
                        <div class="d-flex gap-3 align-items-start">
                            <div style="width:44px;height:44px;border-radius:12px;{{ $user->mfa_enabled ? 'background:#f0fdf4' : 'background:#fef9c3' }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="bi bi-shield-lock-fill fs-5" style="color:{{ $user->mfa_enabled ? '#16a34a' : '#ca8a04' }}"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;color:#0f172a;font-size:.9rem">Two-Factor Authentication (MFA)</div>
                                <div style="font-size:.8rem;color:#64748b;margin-top:.2rem">Require a one-time code sent to your email each time you sign in.</div>
                                @if($user->mfa_enabled)
                                <span class="mt-2 d-inline-block" style="background:#f0fdf4;color:#166534;font-size:.7rem;font-weight:700;padding:.15rem .55rem;border-radius:20px;border:1px solid #bbf7d0">
                                    <i class="bi bi-check-circle-fill me-1"></i>ACTIVE
                                </span>
                                @else
                                <span class="mt-2 d-inline-block" style="background:#fef9c3;color:#854d0e;font-size:.7rem;font-weight:700;padding:.15rem .55rem;border-radius:20px;border:1px solid #fde68a">
                                    <i class="bi bi-exclamation-circle-fill me-1"></i>INACTIVE
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="form-check form-switch ms-3 mt-1">
                            <input class="form-check-input" type="checkbox" id="mfa_toggle"
                                   style="width:2.5rem;height:1.3rem;cursor:pointer"
                                   {{ $user->mfa_enabled ? 'checked' : '' }}>
                        </div>
                    </div>

                    <input type="hidden" name="mfa_enabled" id="mfa_enabled_input" value="{{ $user->mfa_enabled ? '1' : '0' }}">

                    <button type="submit" class="btn btn-primary px-4" style="background:rgb(152,194,10);border-color:rgb(152,194,10);border-radius:10px;font-size:.875rem;font-weight:600">
                        Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Danger Zone ── --}}
    <div class="col-lg-5">
        <div class="card" style="border-color:#fecaca">
            <div class="card-body p-4">
                <h6 class="mb-1" style="color:#991b1b;font-weight:700"><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone</h6>
                <p class="mb-4" style="color:#64748b;font-size:.85rem">Irreversible account actions. Proceed with caution.</p>

                <div class="p-3 mb-3" style="background:#fef2f2;border-radius:12px;border:1px solid #fecaca">
                    <div style="font-weight:600;color:#0f172a;font-size:.875rem">Sign Out All Sessions</div>
                    <div style="font-size:.8rem;color:#64748b;margin:.25rem 0 .75rem">Revoke all active sessions across all devices.</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#991b1b;border-radius:8px;font-size:.8rem;font-weight:600;border:1px solid #fecaca">
                            <i class="bi bi-box-arrow-right me-1"></i> Sign Out Everywhere
                        </button>
                    </form>
                </div>

                <div class="p-3" style="background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0">
                    <div style="font-weight:600;color:#0f172a;font-size:.875rem">Quick Links</div>
                    <div class="d-flex flex-column gap-2 mt-2">
                        <a href="{{ route('account.profile') }}" class="d-flex align-items-center gap-2" style="font-size:.85rem;color:rgb(118,151,7);text-decoration:none">
                            <i class="bi bi-person-fill"></i> Edit Profile
                        </a>
                        <a href="{{ route('account.profile') }}#password" class="d-flex align-items-center gap-2" style="font-size:.85rem;color:rgb(118,151,7);text-decoration:none">
                            <i class="bi bi-lock-fill"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    const toggle    = document.getElementById('mfa_toggle');
    const hiddenInput = document.getElementById('mfa_enabled_input');
    toggle.addEventListener('change', () => {
        hiddenInput.value = toggle.checked ? '1' : '0';
    });

    function previewLogo(input) {
        const name   = input.files[0]?.name ?? 'No file chosen';
        document.getElementById('logo-filename').textContent = name;
        document.getElementById('logo-submit').disabled = !input.files.length;
        if (input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                const preview = document.querySelector('#logo-form').closest('.card-body').querySelector('img');
                if (preview) { preview.src = e.target.result; preview.style.opacity = '1'; }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endpush
