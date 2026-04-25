<?php

namespace App\Providers;

use App\Http\Middleware\SecurityHeaders;
use App\Models\User;
use App\Models\Vulnerability;
use App\Models\VulnAssessment;
use App\Policies\UserPolicy;
use App\Policies\VulnerabilityPolicy;
use App\Policies\VulnAssessmentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Global helper — returns the per-request CSP nonce for inline <script> tags.
        if (!function_exists('csp_nonce')) {
            function csp_nonce(): string
            {
                return SecurityHeaders::nonce();
            }
        }

        // Blade directive shorthand: nonce="{{ csp_nonce() }}" in templates.
        Blade::directive('cspnonce', fn() => '<?php echo e(csp_nonce()); ?>');

        // ── Authorization policies ────────────────────────────
        Gate::policy(User::class,             UserPolicy::class);
        Gate::policy(VulnAssessment::class,   VulnAssessmentPolicy::class);
        Gate::policy(Vulnerability::class,    VulnerabilityPolicy::class);

        // ── Named rate limiters ───────────────────────────────

        // Login: 5 attempts per minute keyed to the submitted email + IP.
        // Combining both prevents an attacker from cycling IPs to beat a
        // per-email limit, and prevents enumeration via per-IP-only limits.
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower($request->input('email', '')) . '|' . $request->ip();
            return [
                Limit::perMinute(5)->by($key)
                     ->response(fn () => back()
                         ->withInput($request->only('email', 'remember'))
                         ->withErrors(['email' => 'Too many login attempts. Please wait a minute and try again.'])),
                Limit::perMinute(15)->by($request->ip()),
            ];
        });

        // Registration: 3 new accounts per 10 minutes from the same IP.
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinutes(10, 3)->by($request->ip())
                ->response(fn () => back()
                    ->withErrors(['email' => 'Too many registration attempts. Please try again later.']));
        });

        // MFA: 5 code attempts per minute keyed to the user being verified.
        // Using the session-stored user ID so an attacker who rotates IPs
        // still hits the same bucket for the same target account.
        RateLimiter::for('mfa', function (Request $request) {
            $key = $request->session()->get('mfa.user_id', $request->ip());
            return Limit::perMinute(5)->by('mfa|' . $key)
                ->response(fn () => back()
                    ->withErrors(['otp' => 'Too many verification attempts. Please sign in again.']));
        });

        // File upload: 10 scan uploads per 5 minutes per user.
        RateLimiter::for('upload', function (Request $request) {
            return Limit::perMinutes(5, 10)->by('upload|' . ($request->user()?->id ?? $request->ip()));
        });

        // ── Agent API rate limiters ───────────────────────────────

        // General API: 60 requests per minute keyed to the Bearer token (or IP
        // as fallback). Keying by token means the limit applies per-agent, not
        // per-IP, which is important for agents behind shared NAT.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->bearerToken() ?? $request->ip());
        });

        // Registration: 5 new registrations per 10 minutes per IP.
        // Prevents an attacker from flooding the agents table with fake agents.
        RateLimiter::for('agent.register', function (Request $request) {
            return Limit::perMinutes(10, 5)->by('agent.register|' . $request->ip());
        });

        // Route model binding
        $this->app['router']->bind('user', function ($value) {
            return User::findOrFail($value);
        });
    }
}
