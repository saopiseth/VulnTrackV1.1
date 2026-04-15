<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking — allow only same origin to embed in frames
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Legacy XSS filter (belt-and-suspenders)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control how much referrer info is sent
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser features not needed by this app
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');

        // Content Security Policy
        // unsafe-inline required for Bootstrap's inline styles and the app's inline <style>/<script> blocks.
        // Harden further by adopting nonces in a future pass.
        $csp = implode(' ', [
            "default-src 'self';",
            "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net;",
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

        // HSTS — only set over HTTPS; tells browsers never to fall back to HTTP
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Remove headers that fingerprint the server stack
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
