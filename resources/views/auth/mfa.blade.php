@extends('layouts.auth')
@section('title', 'Verify — Security Assessment')

@section('content')
<div class="container-fluid g-0">
    <div class="row g-0" style="min-height:100vh">

        {{-- Left decorative panel --}}
        <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between align-items-start p-5"
             style="background:linear-gradient(145deg,rgb(118,151,7),rgb(80,105,4));position:relative;overflow:hidden">

            {{-- Decorative circles --}}
            <div style="position:absolute;width:400px;height:400px;border-radius:50%;background:rgba(255,255,255,.06);top:-100px;right:-100px"></div>
            <div style="position:absolute;width:280px;height:280px;border-radius:50%;background:rgba(255,255,255,.05);bottom:60px;left:-80px"></div>

            {{-- Brand --}}
            <div class="d-flex align-items-center gap-3" style="position:relative;z-index:2">
                <img src="{{ asset('favicon.ico') }}" alt="Wing Bank" style="width:42px;height:42px;border-radius:10px;object-fit:contain;background:#fff;padding:4px">
                <span style="color:#fff;font-size:1.1rem;font-weight:700">Security Assessment</span>
            </div>

            {{-- Hero --}}
            <div style="position:relative;z-index:2">
                <div style="width:64px;height:64px;border-radius:18px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem">
                    <i class="bi bi-shield-lock-fill" style="font-size:2rem;color:#fff"></i>
                </div>
                <h1 style="color:#fff;font-size:2.2rem;font-weight:800;line-height:1.2;margin-bottom:1rem">
                    Two-Factor<br>Authentication
                </h1>
                <p style="color:rgba(255,255,255,.75);font-size:.95rem;line-height:1.7;max-width:340px">
                    An extra layer of security to keep your account protected. Enter the verification code sent to your email.
                </p>
            </div>

            <p style="position:relative;z-index:2;color:rgba(255,255,255,.35);font-size:.78rem;margin:0">
                &copy; {{ date('Y') }} Security Assessment. All rights reserved.
            </p>
        </div>

        {{-- Right: OTP form --}}
        <div class="col-lg-7 d-flex align-items-center justify-content-center p-4" style="background:#f8fafc">
            <div style="width:100%;max-width:440px">

                {{-- Mobile brand --}}
                <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
                    <img src="{{ asset('favicon.ico') }}" alt="Wing Bank" style="width:36px;height:36px;border-radius:8px;object-fit:contain;background:#fff;padding:3px;border:1px solid #e2e8f0">
                    <span style="font-size:1rem;font-weight:700;color:#1a2e05">Security Assessment</span>
                </div>

                {{-- Icon --}}
                <div style="width:60px;height:60px;border-radius:16px;background:rgb(240,248,210);display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem">
                    <i class="bi bi-envelope-check-fill" style="font-size:1.6rem;color:rgb(118,151,7)"></i>
                </div>

                <h2 style="font-size:1.6rem;font-weight:800;color:#0f172a;margin-bottom:.4rem">Check your email</h2>
                <p style="font-size:.9rem;color:#64748b;margin-bottom:2rem">
                    We sent a 6-digit verification code to your email address. The code expires in <strong>10 minutes</strong>.
                </p>

                {{-- Alerts --}}
                @if(session('success'))
                    <div class="alert d-flex align-items-center gap-2 mb-3" style="background:rgb(240,248,210);color:rgb(118,151,7);border:1px solid rgb(200,225,120);border-radius:10px;font-size:.875rem">
                        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                    </div>
                @endif

                @if($errors->has('otp'))
                    <div class="alert d-flex align-items-center gap-2 mb-3" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:10px;font-size:.875rem">
                        <i class="bi bi-exclamation-triangle-fill"></i> {{ $errors->first('otp') }}
                    </div>
                @endif

                @if($errors->has('email'))
                    <div class="alert d-flex align-items-center gap-2 mb-3" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:10px;font-size:.875rem">
                        <i class="bi bi-exclamation-triangle-fill"></i> {{ $errors->first('email') }}
                    </div>
                @endif

                {{-- OTP Form --}}
                <form method="POST" action="{{ route('mfa.verify.post') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151">Verification Code</label>
                        <input type="text" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                            class="form-control form-control-lg text-center @error('otp') is-invalid @enderror"
                            placeholder="• • • • • •"
                            autocomplete="one-time-code"
                            style="font-size:1.6rem;font-weight:700;letter-spacing:.5rem;border-radius:12px;border-color:#e2e8f0;padding:.8rem"
                            autofocus>
                    </div>

                    <button type="submit" class="btn w-100 mb-3"
                        style="background:rgb(152,194,10);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:.95rem;padding:.75rem;box-shadow:0 4px 12px rgba(152,194,10,.35)">
                        <i class="bi bi-shield-check me-2"></i>Verify & Sign In
                    </button>
                </form>

                {{-- Resend --}}
                <form method="POST" action="{{ route('mfa.resend') }}">
                    @csrf
                    <button type="submit" class="btn w-100"
                        style="border:1.5px solid rgb(152,194,10);border-radius:10px;color:rgb(118,151,7);font-weight:600;font-size:.875rem;padding:.65rem;background:#fff">
                        <i class="bi bi-arrow-clockwise me-1"></i>Resend Code
                    </button>
                </form>

                <p class="text-center mt-4" style="font-size:.82rem;color:#94a3b8">
                    <a href="{{ route('login') }}" style="color:rgb(118,151,7);font-weight:600;text-decoration:none">
                        <i class="bi bi-arrow-left me-1"></i>Back to sign in
                    </a>
                </p>

            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-submit when 6 digits entered
document.querySelector('input[name="otp"]').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '');
    if (this.value.length === 6) {
        this.closest('form').submit();
    }
});
</script>
@endpush
