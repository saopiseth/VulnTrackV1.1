<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    // Nonce is generated before the view renders and read back when building CSP.
    private static string $nonce = '';

    public static function nonce(): string
    {
        return static::$nonce;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Generate a per-request nonce BEFORE the view renders so that
        // csp_nonce() is available inside every Blade template.
        $nonce = base64_encode(random_bytes(16));
        static::$nonce = $nonce;
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Legacy XSS filter
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer info
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser features not needed by this app
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');

        // Content Security Policy
        // script-src uses per-request nonce — unsafe-inline removed.
        // style-src keeps unsafe-inline because inline <style> blocks are pervasive
        // in view files; nonce-ing styles is a separate hardening pass.
        $csp = implode(' ', [
            "default-src 'self';",
            "script-src 'self' 'nonce-{$nonce}' cdn.jsdelivr.net;",
            "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com;",
            "font-src 'self' data: fonts.gstatic.com cdn.jsdelivr.net;",
            "img-src 'self' data: blob:;",
            "connect-src 'self';",
            "frame-ancestors 'none';",
            "form-action 'self';",
            "base-uri 'self';",
            "object-src 'none';",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // HSTS — always set; ForceHttps ensures we are on HTTPS in production.
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains; preload'
        );

        // Remove headers that fingerprint the server stack
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
