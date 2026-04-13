<style>
    .form-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .form-card h6 { font-size:.8rem; font-weight:700; color:#4f46e5; text-transform:uppercase; letter-spacing:.8px; margin-bottom:1.25rem; padding-bottom:.6rem; border-bottom:1px solid #f1f5f9; }
    .form-label { font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
    .form-control, .form-select { font-size:.875rem; border-color:#e2e8f0; border-radius:9px; padding:.55rem .85rem; color:#0f172a; transition:border-color .2s, box-shadow .2s; }
    .form-control:focus, .form-select:focus { border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,.1); }
    .form-control.is-invalid { border-color:#dc2626; }
    .invalid-feedback { font-size:.78rem; }
    .btn-submit { background:linear-gradient(135deg,#4f46e5,#3730a3); color:#fff; border:none; border-radius:10px; font-weight:600; font-size:.9rem; padding:.65rem 2rem; box-shadow:0 4px 12px rgba(79,70,229,.3); transition:all .2s; }
    .form-check-input:checked { background-color:rgb(152,194,10); border-color:rgb(152,194,10); }
    .btn-submit:hover { color:#fff; transform:translateY(-1px); }

    /* Role cards */
    .role-card { border:2px solid #e2e8f0; border-radius:12px; padding:1rem 1.25rem; cursor:pointer; transition:all .2s; }
    .role-card:hover { border-color:#a5b4fc; background:#fafbff; }
    .role-card.selected-admin { border-color:#6d28d9; background:#faf5ff; }
    .role-card.selected-assessor { border-color:#15803d; background:#f0fdf4; }
    .role-card input[type=radio] { display:none; }
    .role-card .rc-title { font-weight:700; font-size:.9rem; color:#0f172a; }
    .role-card .rc-desc { font-size:.8rem; color:#64748b; margin-top:.25rem; }
    .role-card .rc-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
</style>

<form method="POST" action="{{ $action }}">
    @csrf
    @if(isset($method) && $method === 'PUT') @method('PUT') @endif

    @if($errors->any())
    <div class="alert d-flex align-items-center gap-2 mb-3" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:10px;font-size:.875rem">
        <i class="bi bi-exclamation-triangle-fill"></i> Please fix the errors below.
    </div>
    @endif

    {{-- Basic Info --}}
    <div class="form-card">
        <h6><i class="bi bi-person me-2"></i>User Information</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full Name <span style="color:#dc2626">*</span></label>
                <div class="input-group">
                    <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0"><i class="bi bi-person" style="color:#94a3b8"></i></span>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        placeholder="John Doe" value="{{ old('name', $user?->name) }}" required>
                </div>
                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Email Address <span style="color:#dc2626">*</span></label>
                <div class="input-group">
                    <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0"><i class="bi bi-envelope" style="color:#94a3b8"></i></span>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        placeholder="user@example.com" value="{{ old('email', $user?->email) }}" required>
                </div>
                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- Role --}}
    <div class="form-card">
        <h6><i class="bi bi-shield-half me-2"></i>Role & Permissions</h6>
        <div class="row g-3" id="roleCards">
            {{-- Administrator --}}
            @php $currentRole = old('role', $user?->role ?? 'assessor'); @endphp
            <div class="col-md-6">
                <label class="role-card {{ $currentRole === 'administrator' ? 'selected-admin' : '' }}" id="card-admin" for="role-admin">
                    <input type="radio" name="role" id="role-admin" value="administrator" {{ $currentRole === 'administrator' ? 'checked' : '' }}>
                    <div class="d-flex align-items-start gap-3">
                        <div class="rc-icon" style="background:#ede9fe;color:#6d28d9"><i class="bi bi-shield-fill-check"></i></div>
                        <div>
                            <div class="rc-title">Administrator</div>
                            <div class="rc-desc">Full access — can manage users, create, edit, and delete assessments.</div>
                            <div class="mt-2 d-flex flex-wrap gap-1">
                                @foreach(['View','Create','Edit','Delete'] as $p)
                                <span style="background:#ede9fe;color:#6d28d9;font-size:.68rem;font-weight:700;padding:.1rem .45rem;border-radius:5px">{{ $p }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </label>
            </div>
            {{-- Assessor --}}
            <div class="col-md-6">
                <label class="role-card {{ $currentRole === 'assessor' ? 'selected-assessor' : '' }}" id="card-assessor" for="role-assessor">
                    <input type="radio" name="role" id="role-assessor" value="assessor" {{ $currentRole === 'assessor' ? 'checked' : '' }}>
                    <div class="d-flex align-items-start gap-3">
                        <div class="rc-icon" style="background:#f0fdf4;color:#15803d"><i class="bi bi-person-badge-fill"></i></div>
                        <div>
                            <div class="rc-title">Assessor</div>
                            <div class="rc-desc">Limited access — can view, create, and edit assessments. Cannot delete.</div>
                            <div class="mt-2 d-flex flex-wrap gap-1">
                                @foreach(['View','Create','Edit'] as $p)
                                <span style="background:#f0fdf4;color:#15803d;font-size:.68rem;font-weight:700;padding:.1rem .45rem;border-radius:5px">{{ $p }}</span>
                                @endforeach
                                <span style="background:#fee2e2;color:#dc2626;font-size:.68rem;font-weight:700;padding:.1rem .45rem;border-radius:5px">No Delete</span>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
        </div>
        @error('role')<div class="invalid-feedback d-block mt-1">{{ $message }}</div>@enderror
    </div>

    {{-- MFA --}}
    @if(isset($user))
    <div class="form-card">
        <h6><i class="bi bi-shield-lock me-2"></i>Two-Factor Authentication</h6>
        @php $mfaEnabled = old('mfa_enabled', $user?->mfa_enabled ?? true); @endphp
        <div class="d-flex align-items-center justify-content-between p-3"
             style="border:2px solid {{ $mfaEnabled ? 'rgb(152,194,10)' : '#e2e8f0' }};border-radius:12px;background:{{ $mfaEnabled ? 'rgb(240,248,210)' : '#f8fafc' }};transition:all .2s"
             id="mfa-toggle-box">
            <div class="d-flex align-items-center gap-3">
                <div style="width:42px;height:42px;border-radius:10px;background:{{ $mfaEnabled ? 'rgb(152,194,10)' : '#e2e8f0' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .2s" id="mfa-icon-wrap">
                    <i class="bi bi-{{ $mfaEnabled ? 'shield-check-fill' : 'shield-slash' }}" style="font-size:1.1rem;color:{{ $mfaEnabled ? '#fff' : '#94a3b8' }}" id="mfa-icon"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:.9rem;color:#0f172a" id="mfa-label">
                        {{ $mfaEnabled ? 'MFA Enabled' : 'MFA Disabled' }}
                    </div>
                    <div style="font-size:.78rem;color:#64748b;margin-top:2px" id="mfa-desc">
                        {{ $mfaEnabled ? 'User must verify via email OTP on every login.' : 'User logs in with password only — no email OTP required.' }}
                    </div>
                </div>
            </div>
            <div class="form-check form-switch mb-0 ms-3">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="mfa_enabled" name="mfa_enabled" value="1"
                       style="width:3rem;height:1.5rem;cursor:pointer"
                       {{ $mfaEnabled ? 'checked' : '' }}>
            </div>
        </div>
        <p class="mt-2 mb-0" style="font-size:.75rem;color:#94a3b8">
            <i class="bi bi-info-circle me-1"></i>
            When disabled, the user bypasses the email verification step and signs in with credentials only.
        </p>
    </div>
    @endif

    {{-- Password --}}
    <div class="form-card">
        <h6><i class="bi bi-lock me-2"></i>Password{{ isset($user) ? ' (leave blank to keep current)' : '' }}</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Password {!! !isset($user) ? '<span style="color:#dc2626">*</span>' : '' !!}</label>
                <div class="input-group">
                    <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0"><i class="bi bi-lock" style="color:#94a3b8"></i></span>
                    <input type="password" name="password" id="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="Min 8 characters" {{ !isset($user) ? 'required' : '' }}>
                    <button class="btn border" type="button" id="togglePass" tabindex="-1"
                        style="background:#f8fafc;border-color:#e2e8f0;color:#94a3b8">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirm Password {!! !isset($user) ? '<span style="color:#dc2626">*</span>' : '' !!}</label>
                <div class="input-group">
                    <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0"><i class="bi bi-lock-fill" style="color:#94a3b8"></i></span>
                    <input type="password" name="password_confirmation"
                        class="form-control" placeholder="Repeat password" {{ !isset($user) ? 'required' : '' }}>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-submit">
            <i class="bi bi-{{ isset($user) ? 'check-lg' : 'person-plus-fill' }} me-2"></i>
            {{ isset($user) ? 'Update User' : 'Create User' }}
        </button>
        <a href="{{ route('users.index') }}" class="btn" style="border:1.5px solid #e2e8f0;border-radius:10px;font-weight:500;color:#374151;padding:.65rem 1.5rem">
            Cancel
        </a>
    </div>
</form>

<script>
    // Password toggle
    document.getElementById('togglePass')?.addEventListener('click', function () {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
        if (pwd.type === 'password') { pwd.type = 'text'; icon.classList.replace('bi-eye','bi-eye-slash'); }
        else { pwd.type = 'password'; icon.classList.replace('bi-eye-slash','bi-eye'); }
    });

    // MFA toggle visual feedback
    const mfaToggle = document.getElementById('mfa_enabled');
    if (mfaToggle) {
        mfaToggle.addEventListener('change', function () {
            const on = this.checked;
            const box = document.getElementById('mfa-toggle-box');
            const iconWrap = document.getElementById('mfa-icon-wrap');
            const icon = document.getElementById('mfa-icon');
            const label = document.getElementById('mfa-label');
            const desc = document.getElementById('mfa-desc');

            box.style.borderColor = on ? 'rgb(152,194,10)' : '#e2e8f0';
            box.style.background  = on ? 'rgb(240,248,210)' : '#f8fafc';
            iconWrap.style.background = on ? 'rgb(152,194,10)' : '#e2e8f0';
            icon.className = 'bi bi-' + (on ? 'shield-check-fill' : 'shield-slash');
            icon.style.color = on ? '#fff' : '#94a3b8';
            label.textContent = on ? 'MFA Enabled' : 'MFA Disabled';
            desc.textContent  = on
                ? 'User must verify via email OTP on every login.'
                : 'User logs in with password only — no email OTP required.';
        });
    }

    // Role card highlight
    document.querySelectorAll('input[name="role"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.getElementById('card-admin').classList.remove('selected-admin');
            document.getElementById('card-assessor').classList.remove('selected-assessor');
            if (this.value === 'administrator') document.getElementById('card-admin').classList.add('selected-admin');
            else document.getElementById('card-assessor').classList.add('selected-assessor');
        });
    });
</script>
