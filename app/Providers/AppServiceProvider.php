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
            ['127.0.0.1', '::1'],
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        // Auto-detect ngrok URL from request headers and force HTTPS scheme
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            URL::forceScheme('https');
        }

        if (isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'ngrok')) {
            $scheme = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : 'https';
            URL::forceRootUrl($scheme . '://' . $_SERVER['HTTP_HOST']);
        }

        // ── Anti-Spam & Security Rate Limiters ──────────────────────────────────
        
        // 1. Login: Multi-tier protection (5 attempts/min per account+IP, 10 attempts/min per IP)
        RateLimiter::for('login', function (Request $request) {
            $identifier = strtolower(trim((string) $request->input('email', $request->input('identifier', $request->input('username', '')))));
            $ip = $request->ip();

            return [
                Limit::perMinute(5)->by($identifier . '|' . $ip)->response(function (Request $request, array $headers) use ($identifier, $ip) {
                    $lockout = app(\App\Services\AccountLockoutService::class);
                    $isLocked = $lockout->isLocked($identifier, $ip);
                    return response()->json([
                        'status' => 'error',
                        'success' => false,
                        'message' => 'Too many login attempts for this account. Please try again in ' . ($headers['Retry-After'] ?? 60) . ' seconds.',
                        'locked' => $isLocked ?: true,
                        'retry_after' => (int) ($headers['Retry-After'] ?? 60),
                    ], 429, $headers);
                }),
                Limit::perMinute(10)->by($ip)->response(function (Request $request, array $headers) use ($identifier, $ip) {
                    $lockout = app(\App\Services\AccountLockoutService::class);
                    $isLocked = $lockout->isLocked($identifier, $ip);
                    return response()->json([
                        'status' => 'error',
                        'success' => false,
                        'message' => 'Too many login attempts from this IP address. Please try again in ' . ($headers['Retry-After'] ?? 60) . ' seconds.',
                        'locked' => $isLocked ?: true,
                        'retry_after' => (int) ($headers['Retry-After'] ?? 60),
                    ], 429, $headers);
                }),
            ];
        });

        // 2. OTP send: Max 3/min per IP, max 2/min per email to prevent spam/bombing
        RateLimiter::for('otp.send', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', $request->user()?->email ?? '')));
            $ip = $request->ip();

            return [
                Limit::perMinute(3)->by($ip)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many OTP requests from this IP. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
                Limit::perMinute(2)->by('otp_email:' . ($email ?: $ip))->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many OTP requests for this email. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
            ];
        });

        // 3. OTP verify: Max 5 attempts/min to prevent code brute-forcing
        RateLimiter::for('otp.verify', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', $request->user()?->email ?? '')));
            $ip = $request->ip();

            return [
                Limit::perMinute(5)->by('otp_verify:' . ($email ?: $ip))->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many OTP verification attempts. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
                Limit::perMinute(10)->by($ip)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many attempts from this IP address.', $headers);
                }),
            ];
        });

        // 4. Password change & Reset: 3 attempts/min per account and per IP
        RateLimiter::for('password.reset', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', $request->input('identifier', ''))));
            $ip = $request->ip();

            return [
                Limit::perMinute(3)->by('pw_reset:' . ($email ?: $ip))->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many password reset requests. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
                Limit::perMinute(5)->by($ip)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many requests from this IP address.', $headers);
                }),
            ];
        });

        RateLimiter::for('password.change', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();
            return Limit::perMinute(3)->by($key)->response(function (Request $request, array $headers) {
                return $this->format429Response($request, 'Too many password change attempts. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
            });
        });

        // 5. Email verification & resend protection
        RateLimiter::for('email.verify', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', $request->user()?->email ?? '')));
            $ip = $request->ip();

            return [
                Limit::perMinute(3)->by($ip)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many email requests from this IP. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
                Limit::perMinute(2)->by('email_verify:' . ($email ?: $ip))->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many email requests for this account.', $headers);
                }),
            ];
        });

        // 6. Global API Rate Limiter: 60 requests/min per IP / authenticated user
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->id ? 'user:' . $request->user()->id : 'ip:' . $request->ip();
            return Limit::perMinute(60)->by($key)->response(function (Request $request, array $headers) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'API rate limit exceeded. Too many requests. Please retry in ' . ($headers['Retry-After'] ?? 60) . ' seconds.',
                    'retry_after' => (int) ($headers['Retry-After'] ?? 60),
                ], 429, $headers);
            });
        });
    }

    /**
     * Helper to return a JSON 429 response or redirect with error for Web requests.
     */
    private function format429Response(Request $request, string $message, array $headers)
    {
        if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => $message,
                'retry_after' => (int) ($headers['Retry-After'] ?? 60),
            ], 429, $headers);
        }

        return back()->withErrors(['error' => $message])->withHeaders($headers);
    }
}
