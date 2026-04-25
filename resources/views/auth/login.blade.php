@extends('layouts.auth')
@section('title', 'Sign In — Vuln Management')

@section('content')
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc">
    <div style="width:100%;max-width:420px;padding:2rem">

            {{-- Heading --}}
            <div style="margin-bottom:2rem">
                <div style="width:48px;height:48px;border-radius:13px;background:rgb(240,248,210);display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem">
                    <i class="bi bi-box-arrow-in-right" style="font-size:1.4rem;color:var(--primary-dark)"></i>
                </div>
                <h2 style="font-size:1.65rem;font-weight:800;color:#0f172a;margin:0 0 .35rem">Sign in</h2>
                <p style="font-size:.875rem;color:#64748b;margin:0">
                    Enter your credentials to access your account
                </p>
            </div>

            {{-- Alerts --}}
            @if(session('success'))
                <div class="alert d-flex align-items-center gap-2 mb-3"
                     style="background:rgb(240,248,210);color:var(--primary-dark);border:1px solid var(--primary-light);border-radius:10px;font-size:.875rem">
                    <i class="bi bi-check-circle-fill flex-shrink-0"></i> {{ session('success') }}
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="alert d-flex align-items-center gap-2 mb-3"
                     style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:10px;font-size:.875rem">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    {{ session('error') ?? $errors->first() }}
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                {{-- Email --}}
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="you@example.com"
                            value="{{ old('email') }}"
                            autocomplete="email" autofocus required>
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label mb-0">Password</label>
                        <a href="{{ route('password.request') }}" class="link-primary-custom" style="font-size:.8rem">
                            Forgot password?
                        </a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="••••••••"
                            autocomplete="current-password" required>
                        <button class="btn toggle-password border" type="button" id="togglePass" tabindex="-1">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                {{-- Remember --}}
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember"
                        {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember" style="font-size:.85rem;color:#374151">
                        Remember me for 30 days
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn btn-primary-custom">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

            {{-- Divider --}}
            <div class="auth-divider">secure login</div>

            {{-- Security note --}}
            <div style="display:flex;align-items:flex-start;gap:.6rem;background:rgb(240,248,210);border:1px solid var(--primary-light);border-radius:10px;padding:.85rem 1rem">
                <i class="bi bi-shield-check-fill flex-shrink-0" style="color:var(--primary-dark);margin-top:2px"></i>
                <span style="font-size:.78rem;color:var(--primary-dark);line-height:1.6">
                    Your sign-in is protected by <strong>email-based two-factor authentication</strong>. A verification code will be sent to your inbox.
                </span>
            </div>

            <p class="text-center mt-4 mb-0" style="font-size:.8rem;color:#94a3b8">
                By signing in, you agree to our
                <a href="#" class="link-primary-custom">Terms of Service</a> &amp;
                <a href="#" class="link-primary-custom">Privacy Policy</a>.
            </p>

    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
    document.getElementById('togglePass').addEventListener('click', function () {
        const pwd  = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            pwd.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
</script>
@endpush
