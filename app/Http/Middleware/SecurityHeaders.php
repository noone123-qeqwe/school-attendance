<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and attach standard HTTP security headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Security headers applied to all responses
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');

        // Content Security Policy (CSP)
        $csp = "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com; "
            . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com https://fonts.googleapis.com; "
            . "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net; "
            . "img-src 'self' data: blob: https: https://ui-avatars.com https://api.qrserver.com https://*.tile.openstreetmap.org https://res.cloudinary.com https://*.cloudinary.com; "
            . "connect-src 'self' ws: wss: http: https: data: blob:; "
            . "media-src 'self' data: blob: https://res.cloudinary.com https://*.cloudinary.com; "
            . "frame-ancestors 'self'; "
            . "base-uri 'self'; "
            . "object-src 'none'; "
            . "form-action 'self' http: https:;";

        $response->headers->set('Content-Security-Policy', $csp);

        // Enforce HSTS if request is on HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Hide server identity header if present
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
