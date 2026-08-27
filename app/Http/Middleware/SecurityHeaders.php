<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * The Request attribute / View share key used to propagate the nonce.
     * Blade templates read it via csp_nonce() or {{ request()->attributes->get('csp_nonce') }}.
     */
    public const NONCE_KEY = 'csp_nonce';

    /**
     * Handle an incoming request: generate a per-request CSP nonce, build a
     * restrictive Content-Security-Policy, and attach all HTTP security headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ── 1. Generate a cryptographically secure per-request nonce ──────────
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set(self::NONCE_KEY, $nonce);

        // Share with all Blade views so {{ csp_nonce() }} / @cspNonce works
        View::share(self::NONCE_KEY, $nonce);

        $response = $next($request);

        // ── 2. Standard security headers ─────────────────────────────────────
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');

        // ── 3. Restrictive Content-Security-Policy with per-request nonce ────
        //
        // unsafe-eval: REMOVED — no dynamic code evaluation permitted.
        // unsafe-inline scripts: replaced by 'nonce-…' so inline blocks pass only
        //   when they carry the matching nonce attribute.
        // connect-src: restricted to same-origin + the specific CDN / API hosts
        //   the app actually fetches at runtime; ws:/wss: for Reverb/Echo.
        // form-action: 'self' only — no third-party POST targets.
        //
        $appHost = parse_url(config('app.url', ''), PHP_URL_HOST) ?: '';

        $csp = implode(' ', [
            "default-src 'self';",

            // Scripts: nonce required; CDN bundles allowed by origin only.
            // 'unsafe-inline' is intentionally absent — nonce supersedes it in
            // modern browsers; legacy browsers fall back silently.
            "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net;",

            // Styles: nonce for inline styles; CDN origins for Bootstrap / Fonts.
            // 'unsafe-inline' kept here because moving every inline style to a
            // nonce attribute requires templating changes across all views — this
            // is the pragmatic intermediate step. Remove once all inline styles
            // are extracted to .css files or given nonce attributes.
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com;",

            "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net;",

            // Images: data: / blob: for canvas/QR; specific remote hosts only.
            "img-src 'self' data: blob: https://ui-avatars.com https://api.qrserver.com https://*.tile.openstreetmap.org https://res.cloudinary.com https://*.cloudinary.com;",

            // Fetch / XHR: same-origin + WebSocket for real-time + the specific
            // remote JSON APIs the front-end actually calls.
            "connect-src 'self' wss: https://res.cloudinary.com https://*.cloudinary.com;",

            "media-src 'self' data: blob: https://res.cloudinary.com https://*.cloudinary.com;",

            // Workers / frames / base / objects
            "worker-src 'self' blob:;",
            "frame-ancestors 'self';",
            "base-uri 'self';",
            "object-src 'none';",

            // Only allow form POSTs to our own origin — blocks phishing redirects.
            "form-action 'self';",

            // Upgrade insecure requests in production to avoid mixed-content.
            app()->isProduction() ? "upgrade-insecure-requests;" : '',
        ]);

        $response->headers->set('Content-Security-Policy', trim($csp));

        // ── 4. HSTS ──────────────────────────────────────────────────────────
        // Set unconditionally in production so it survives behind a TLS-terminating
        // proxy that forwards plain HTTP to the app — isSecure() would be false
        // there without proper proxy trust, so we key off APP_ENV instead.
        if (app()->isProduction()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        } elseif ($request->isSecure()) {
            // Dev / staging: only set if the connection is genuinely TLS
            $response->headers->set('Strict-Transport-Security', 'max-age=0');
        }

        // ── 5. Remove server-fingerprinting headers ───────────────────────────
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
