@extends('layouts.auth')
@section('title', 'Create Account — ' . config('app.name'))

@section('content')
<div class="container-fluid g-0">
    <div class="row g-0" style="min-height:100vh">

        {{-- ══════════════ LEFT PANEL ══════════════ --}}
        <div class="col-lg-5 auth-left d-none d-lg-flex">

            <div class="blob-mid"></div>

            <div class="dot-grid">
                @for($i = 0; $i < 36; $i++)
                    <span></span>
                @endfor
            </div>

            <div class="auth-brand">
                <div class="brand-icon">
                    <i class="bi bi-lightning-charge-fill"></i>
                </div>
                <span class="brand-name">{{ config('app.name', 'MyApp') }}</span>
            </div>

            <div class="auth-hero">
                <h1>Start your<br>journey<br><span>for free today</span></h1>
                <p>Join thousands of teams who trust us to manage their workflows, projects, and data — securely and efficiently.</p>

                <div class="stat-cards">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:rgba(79,70,229,.25)">
                            <i class="bi bi-rocket-takeoff-fill" style="color:#818cf8"></i>
                        </div>
                        <div>
                            <div class="stat-label">Setup Time</div>
                            <div class="stat-value">Under 2 Minutes</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:rgba(6,182,212,.2)">
                            <i class="bi bi-credit-card-fill" style="color:#22d3ee"></i>
                        </div>
                        <div>
                            <div class="stat-label">Free Plan</div>
                            <div class="stat-value">No Credit Card Needed</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:rgba(16,185,129,.2)">
                            <i class="bi bi-award-fill" style="color:#34d399"></i>
                        </div>
                        <div>
                            <div class="stat-label">Support</div>
                            <div class="stat-value">24/7 Live Chat</div>
                        </div>
                    </div>
                </div>
            </div>

            <p style="position:relative;z-index:2;color:rgba(255,255,255,.35);font-size:.78rem;margin:0">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>

        {{-- ══════════════ RIGHT PANEL ══════════════ --}}
        <div class="col-lg-7 auth-right" style="align-items:flex-start;padding-top:3rem;padding-bottom:3rem">
            <div class="auth-form-wrap">

                <div class="auth-brand d-flex d-lg-none mb-4">
                    <div class="brand-icon" style="background:linear-gradient(135deg,#4f46e5,#06b6d4)">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <span class="brand-name" style="color:#1e1b4b">{{ config('app.name', 'MyApp') }}</span>
                </div>

                <p class="auth-subtitle mb-1" style="font-size:.8rem;font-weight:600;color:#6366f1;text-transform:uppercase;letter-spacing:.8px">Get started</p>
                <h2 class="auth-title">Create your account</h2>
                <p class="auth-subtitle">Already have an account?
                    <a href="{{ route('login') }}" class="link-primary-custom">Sign in here</a>
                </p>

                @if($errors->any())
                    <div class="alert alert-danger mt-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                {{-- Social Sign Up --}}
                <div class="row g-2 mt-3">
                    <div class="col-6">
                        <button class="btn btn-social w-100">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            Google
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-social w-100">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#1877F2">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            Facebook
                        </button>
                    </div>
                </div>

                <div class="auth-divider">or register with email</div>

                <form method="POST" action="{{ route('register') }}" novalidate>
                    @csrf

                    {{-- Name row --}}
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" id="first_name" name="first_name"
                                    class="form-control @error('first_name') is-invalid @enderror"
                                    placeholder="John" value="{{ old('first_name') }}" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" id="last_name" name="last_name"
                                    class="form-control @error('last_name') is-invalid @enderror"
                                    placeholder="Doe" value="{{ old('last_name') }}" required>
                            </div>
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="you@example.com" value="{{ old('email') }}" required>
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" id="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Min 8 characters" required>
                            <button class="btn toggle-password border" type="button" id="togglePass" tabindex="-1">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <div class="mt-2">
                            <div class="d-flex gap-1" id="strengthBar">
                                <div class="flex-fill rounded" style="height:4px;background:#e2e8f0" id="bar1"></div>
                                <div class="flex-fill rounded" style="height:4px;background:#e2e8f0" id="bar2"></div>
                                <div class="flex-fill rounded" style="height:4px;background:#e2e8f0" id="bar3"></div>
                                <div class="flex-fill rounded" style="height:4px;background:#e2e8f0" id="bar4"></div>
                            </div>
                            <p class="mt-1 mb-0" style="font-size:.75rem;color:var(--text-muted)" id="strengthText"></p>
                        </div>
                    </div>

                    {{-- Confirm Password --}}
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                class="form-control" placeholder="Repeat your password" required>
                        </div>
                    </div>

                    {{-- Terms --}}
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms" style="font-size:.85rem;color:#374151">
                            I agree to the <a href="#" class="link-primary-custom">Terms of Service</a>
                            and <a href="#" class="link-primary-custom">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-person-plus-fill me-2"></i>Create Account
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Password toggle
    document.getElementById('togglePass').addEventListener('click', function () {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            pwd.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });

    // Password strength meter
    document.getElementById('password').addEventListener('input', function () {
        const val = this.value;
        const bars = [document.getElementById('bar1'), document.getElementById('bar2'),
                      document.getElementById('bar3'), document.getElementById('bar4')];
        const text = document.getElementById('strengthText');
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const colors = ['#ef4444','#f97316','#eab308','#10b981'];
        const labels = ['Weak','Fair','Good','Strong'];
        bars.forEach((b, i) => b.style.background = i < score ? colors[score - 1] : '#e2e8f0');
        text.textContent = val.length ? labels[score - 1] || '' : '';
        text.style.color = score > 0 ? colors[score - 1] : 'var(--text-muted)';
    });
</script>
@endpush
