<?php

namespace App\Http\Controllers;

use App\Mail\MfaOtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // ─── Login ───────────────────────────────────────────────

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        // Verify credentials without logging in
        if (!Auth::validate($credentials)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (!$user->mfa_enabled) {
            Auth::login($user, $remember);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        $request->session()->put([
            'mfa.user_id'  => $user->id,
            'mfa.remember' => $remember,
        ]);

        $this->issueOtp($request, $user);

        return redirect()->route('mfa.verify');
    }

    // ─── MFA Verify ──────────────────────────────────────────

    public function showMfa(Request $request)
    {
        if (!$request->session()->has('mfa.user_id')) {
            return redirect()->route('login');
        }
        return view('auth.mfa');
    }

    public function verifyMfa(Request $request)
    {
        if (!$request->session()->has('mfa.user_id')) {
            return redirect()->route('login');
        }

        $request->validate(['otp' => ['required', 'digits:6']]);

        $expiresAt = $request->session()->get('mfa.expires_at');
        if (now()->timestamp > $expiresAt) {
            $request->session()->forget(['mfa.user_id','mfa.otp','mfa.expires_at','mfa.remember']);
            return redirect()->route('login')
                ->withErrors(['email' => 'Verification code expired. Please sign in again.']);
        }

        if (!Hash::check($request->otp, $request->session()->get('mfa.otp'))) {
            return back()->withErrors(['otp' => 'Invalid verification code. Please try again.']);
        }

        $user     = User::findOrFail($request->session()->get('mfa.user_id'));
        $remember = $request->session()->get('mfa.remember', false);

        $request->session()->forget(['mfa.user_id','mfa.otp','mfa.expires_at','mfa.remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }

    public function resendMfa(Request $request)
    {
        if (!$request->session()->has('mfa.user_id')) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($request->session()->get('mfa.user_id'));

        $this->issueOtp($request, $user);

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    // ─── Register ────────────────────────────────────────────

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name'  => ['required', 'string', 'max:50'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', 'confirmed', Password::min(8)],
            'terms'      => ['accepted'],
        ], [
            'terms.accepted' => 'You must agree to the Terms of Service.',
            'email.unique'   => 'This email is already registered.',
        ]);

        $user = User::create([
            'name'     => $request->first_name . ' ' . $request->last_name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Account created! Welcome, ' . $user->name . '!');
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function issueOtp(Request $request, User $user): void
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $request->session()->put([
            'mfa.otp'        => Hash::make($otp),
            'mfa.expires_at' => now()->addMinutes(10)->timestamp,
        ]);
        Mail::to($user->email)->send(new MfaOtpMail($otp, $user->name));
    }

    // ─── Logout ──────────────────────────────────────────────

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been signed out successfully.');
    }
}
