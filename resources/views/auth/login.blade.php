@extends('layouts.auth')
@section('title', 'Sign In — Security Assessment')

@section('content')
<div style="min-height:100vh;display:flex;align-items:stretch">

    {{-- ══ LEFT: Branding panel ══ --}}
    <div class="d-none d-lg-flex flex-column justify-content-between"
         style="width:42%;background:linear-gradient(160deg,rgb(55,72,2) 0%,rgb(90,118,5) 55%,rgb(118,151,7) 100%);padding:3rem;position:relative;overflow:hidden">

        {{-- Background circles --}}
        <div style="position:absolute;width:500px;height:500px;border-radius:50%;background:rgba(255,255,255,.04);top:-150px;right:-150px;pointer-events:none"></div>
        <div style="position:absolute;width:320px;height:320px;border-radius:50%;background:rgba(255,255,255,.05);bottom:-80px;left:-80px;pointer-events:none"></div>
        <div style="position:absolute;width:180px;height:180px;border-radius:50%;background:rgba(200,235,100,.08);top:45%;left:55%;pointer-events:none"></div>

        {{-- Logo --}}
        <div style="position:relative;z-index:2">
            <img src="{{ asset('wb-logo.svg') }}" alt="Wing Bank"
                 style="height:70px;width:auto;filter:brightness(0) invert(1)">
        </div>

        {{-- Hero --}}
        <div style="position:relative;z-index:2">
            <h1 style="color:#fff;font-size:2.4rem;font-weight:800;line-height:1.18;margin-bottom:1rem;letter-spacing:-.5px">
                Security<br>Assessment<br>
                <span style="background:linear-gradient(90deg,rgb(220,240,130),rgb(180,220,50));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">
                    Platform
                </span>
            </h1>
            <p style="color:rgba(255,255,255,.68);font-size:.95rem;line-height:1.75;max-width:320px;margin-bottom:2.5rem">
                Manage, track and report security assessments across all your projects in one place.
            </p>

            {{-- Feature pills --}}
            <div style="display:flex;flex-direction:column;gap:.75rem">
                @foreach([
                    ['bi-shield-check','Criteria tracking with evidence upload'],
                    ['bi-lock','Role-based access control'],
                    ['bi-envelope-check','Two-factor authentication via email'],
                ] as [$icon,$text])
                <div style="display:flex;align-items:center;gap:.75rem">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi {{ $icon }}" style="color:rgb(220,240,130);font-size:.9rem"></i>
                    </div>
                    <span style="color:rgba(255,255,255,.8);font-size:.85rem;font-weight:500">{{ $text }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Footer --}}
        <p style="position:relative;z-index:2;color:rgba(255,255,255,.3);font-size:.75rem;margin:0">
            &copy; {{ date('Y') }} Wing Bank. All rights reserved.
        </p>
    </div>

    {{-- ══ RIGHT: Form panel ══ --}}
    <div style="flex:1;display:flex;align-items:center;justify-content:center;background:#f8fafc;padding:2rem">
        <div style="width:100%;max-width:420px">

            {{-- Mobile logo --}}
            <div class="d-flex d-lg-none justify-content-center mb-5">
                <img src="{{ asset('wb-logo.svg') }}" alt="Wing Bank" style="height:36px;width:auto">
            </div>

            {{-- Heading --}}
            <div style="margin-bottom:2rem">
                <div style="width:48px;height:48px;border-radius:13px;background:rgb(240,248,210);display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem">
                    <i class="bi bi-box-arrow-in-right" style="font-size:1.4rem;color:rgb(118,151,7)"></i>
                </div>
                <h2 style="font-size:1.65rem;font-weight:800;color:#0f172a;margin:0 0 .35rem">Sign in</h2>
                <p style="font-size:.875rem;color:#64748b;margin:0">
                    Enter your credentials to access your account
                </p>
            </div>

            {{-- Alerts --}}
            @if(session('success'))
                <div class="alert d-flex align-items-center gap-2 mb-3"
                     style="background:rgb(240,248,210);color:rgb(118,151,7);border:1px solid rgb(200,225,120);border-radius:10px;font-size:.875rem">
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
            <div style="display:flex;align-items:flex-start;gap:.6rem;background:rgb(240,248,210);border:1px solid rgb(200,225,120);border-radius:10px;padding:.85rem 1rem">
                <i class="bi bi-shield-check-fill flex-shrink-0" style="color:rgb(118,151,7);margin-top:2px"></i>
                <span style="font-size:.78rem;color:rgb(80,105,4);line-height:1.6">
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

</div>
@endsection

@push('scripts')
<script>
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
