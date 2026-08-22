<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Trust all proxies (ngrok, local reverse proxies)
        \Illuminate\Http\Request::setTrustedProxies(
            ['127.0.0.1', '::1', '*'],
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        // Auto-detect ngrok URL from request headers and force HTTPS scheme
        // This means you NEVER need to update APP_URL when ngrok restarts
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            URL::forceScheme('https');
        }

        if (isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'ngrok')) {
            $scheme = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : 'https';
            URL::forceRootUrl($scheme . '://' . $_SERVER['HTTP_HOST']);
        }

        // ── Anti-Spam Rate Limiters ──────────────────────────────────
        // Login: 5 attempts/min keyed by email+IP to block credential stuffing
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email', $request->input('username', '')));
            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        // OTP send: 3 emails/min per IP to prevent email/SMS bombing
        RateLimiter::for('otp.send', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // OTP verify: 5 attempts/min per IP to prevent brute-forcing codes
        RateLimiter::for('otp.verify', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Password change/reset: 3 attempts/min keyed by user or IP
        RateLimiter::for('password.change', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();
            return Limit::perMinute(3)->by($key);
        });

        // Global API: 60 requests/min per authenticated user or IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}

