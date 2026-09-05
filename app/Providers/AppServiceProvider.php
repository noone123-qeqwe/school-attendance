<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\SecurityHeaders;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // ── CSP nonce Blade directive ─────────────────────────────────────────
        // @cspNonce  → renders: nonce="<per-request-value>"
        Blade::directive('cspNonce', function () {
            return '<?php echo \'nonce="\' . e(request()->attributes->get(\App\Http\Middleware\SecurityHeaders::NONCE_KEY, \'\')) . \'"\'; ?>';
        });

        // ── Proxy / URL auto-detection for ngrok & local dev ──────────────────
        // Proxy trust configuration is handled in bootstrap/app.php via
        // Middleware::trustProxies() so X-Forwarded-* headers are applied early.
        // The ngrok URL forcing below is an additional dev convenience.
        if ($this->app->environment('production') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            URL::forceScheme('https');
        }

        if (isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'ngrok')) {
            $scheme = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : 'https';
            URL::forceRootUrl($scheme . '://' . $_SERVER['HTTP_HOST']);
        }

        // ── High-Concurrency SQLite Database Optimization ─────────────────────
        if (config('database.default') === 'sqlite') {
            try {
                \Illuminate\Support\Facades\DB::statement('PRAGMA journal_mode=WAL;');
                \Illuminate\Support\Facades\DB::statement('PRAGMA synchronous=NORMAL;');
                \Illuminate\Support\Facades\DB::statement('PRAGMA busy_timeout=10000;');
                \Illuminate\Support\Facades\DB::statement('PRAGMA mmap_size=268435456;');
                \Illuminate\Support\Facades\DB::statement('PRAGMA cache_size=-20000;');
            } catch (\Throwable $e) {
                // Ignore pragma setup failures during early bootstrapping/migrations
            }
        }

        // ── Anti-Spam & Security Rate Limiters ──────────────────────────────────
        
        // 1. Login: Multi-tier protection (5 attempts/min per account+IP, 10 attempts/min per IP)
        RateLimiter::for('login', function (Request $request) {
            $identifier = strtolower(trim((string) $request->input('email', $request->input('identifier', $request->input('username', $request->input('student_number', $request->input('employee_id', $request->input('user', ''))))))));
            $ip = $request->ip() ?: 'unknown';

            return [
                Limit::perMinute(5)->by('login_id:' . ($identifier ?: 'empty') . '|' . $ip)->response(function (Request $request, array $headers) use ($identifier, $ip) {
                    $lockout = app(\App\Services\AccountLockoutService::class);
                    $isLocked = $lockout->isLocked($identifier, $ip);
                    return response()->json([
                        'status' => 'error',
                        'success' => false,
                        'message' => 'Too many login attempts. Please try again in ' . ($headers['Retry-After'] ?? 60) . ' seconds.',
                        'locked' => $isLocked ?: true,
                        'retry_after' => (int) ($headers['Retry-After'] ?? 60),
                    ], 429, $headers);
                }),
                Limit::perMinute(10)->by('login_ip:' . $ip)->response(function (Request $request, array $headers) use ($identifier, $ip) {
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

        // 2. OTP send: Enforce per-email 30s cooldown pace (2/min), while providing a realistic 20/min ceiling for shared IPs (mobile carrier CGNAT, campus WiFi)
        RateLimiter::for('otp.send', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', $request->input('identifier', $request->input('username', $request->input('phone', $request->user()?->email ?? ''))))));
            $ip = $request->ip() ?: 'unknown';

            return [
                // Per-account limit: Max 2/min (permits initial send + 30s cooldown resend)
                Limit::perMinute(2)->by('otp_send_id:' . ($email ?: $ip))->response(function (Request $request, array $headers) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? 30);
                    return $this->format429Response($request, 'Please wait ' . $retryAfter . ' seconds before requesting another code.', $headers);
                }),
                // Per-IP limit: Max 10/min to safely support legitimate users on shared networks while blocking flood attacks
                Limit::perMinute(10)->by('otp_send_ip:' . $ip)->response(function (Request $request, array $headers) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? 60);
                    return $this->format429Response($request, 'Too many OTP requests from this network. Please wait ' . $retryAfter . ' seconds.', $headers);
                }),
                // Hourly burst ceiling per IP: Max 100/hr
                Limit::perHour(100)->by('otp_send_ip_hr:' . $ip)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Hourly OTP request limit exceeded for this network. Please try again later.', $headers);
                }),
            ];
        });

        // 3. OTP verify: Max 5 attempts/min to prevent code brute-forcing
        RateLimiter::for('otp.verify', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', $request->input('identifier', $request->input('username', $request->user()?->email ?? '')))));
            $ip = $request->ip() ?: 'unknown';

            return [
                Limit::perMinute(5)->by('otp_verify_id:' . ($email ?: $ip))->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many OTP verification attempts. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
                Limit::perMinute(20)->by('otp_verify_ip:' . $ip)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many verification attempts from this network. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
            ];
        });

        // 4. Password change & Reset: 2 attempts/min per account, 5 per IP
        RateLimiter::for('password.reset', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', $request->input('identifier', $request->input('username', '')))));
            $ip = $request->ip() ?: 'unknown';

            return [
                Limit::perMinute(2)->by('pw_reset_id:' . ($email ?: $ip))->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many password reset requests. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
                Limit::perMinute(10)->by('pw_reset_ip:' . $ip)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many requests from this network.', $headers);
                }),
            ];
        });

        RateLimiter::for('password.change', function (Request $request) {
            $key = $request->user()?->id ? 'user:' . $request->user()->id : 'ip:' . ($request->ip() ?: 'unknown');
            return Limit::perMinute(3)->by('pw_change:' . $key)->response(function (Request $request, array $headers) {
                return $this->format429Response($request, 'Too many password change attempts. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
            });
        });

        // 5. Email verification & resend protection
        RateLimiter::for('email.verify', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', $request->user()?->email ?? '')));
            $ip = $request->ip() ?: 'unknown';

            return [
                Limit::perMinute(2)->by('email_verify_id:' . ($email ?: $ip))->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Please wait ' . ($headers['Retry-After'] ?? 30) . ' seconds before requesting another verification email.', $headers);
                }),
                Limit::perMinute(20)->by('email_verify_ip:' . $ip)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many email requests from this network. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
                Limit::perHour(100)->by('email_verify_ip_hr:' . $ip)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Hourly email verification request limit exceeded.', $headers);
                }),
            ];
        });

        // 6. Global API Rate Limiter: 60 requests/min per IP / authenticated user
        RateLimiter::for('api', function (Request $request) {
            $ip = $request->ip() ?: 'unknown';
            $userKey = $request->user()?->id ? 'user:' . $request->user()->id : null;

            $limits = [
                Limit::perMinute(60)->by('api_ip:' . $ip)->response(function (Request $request, array $headers) {
                    return response()->json([
                        'status' => 'error',
                        'error' => 'API_RATE_LIMITED',
                        'message' => 'API rate limit exceeded. Too many requests. Please retry in ' . ($headers['Retry-After'] ?? 60) . ' seconds.',
                        'retryAfter' => (int) ($headers['Retry-After'] ?? 60),
                        'retry_after' => (int) ($headers['Retry-After'] ?? 60),
                        'cooldown' => (int) ($headers['Retry-After'] ?? 60),
                    ], 429, $headers);
                }),
            ];

            if ($userKey) {
                $limits[] = Limit::perMinute(60)->by('api_' . $userKey)->response(function (Request $request, array $headers) {
                    return response()->json([
                        'status' => 'error',
                        'error' => 'API_RATE_LIMITED',
                        'message' => 'API rate limit exceeded for this account. Please retry in ' . ($headers['Retry-After'] ?? 60) . ' seconds.',
                        'retryAfter' => (int) ($headers['Retry-After'] ?? 60),
                        'retry_after' => (int) ($headers['Retry-After'] ?? 60),
                        'cooldown' => (int) ($headers['Retry-After'] ?? 60),
                    ], 429, $headers);
                });
            }

            return $limits;
        });
    }

    /**
     * Helper to return a structured JSON 429 response or redirect with error for Web requests.
     */
    private function format429Response(Request $request, string $message, array $headers)
    {
        $retryAfter = (int) ($headers['Retry-After'] ?? 60);

        if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'error' => 'OTP_RATE_LIMITED',
                'message' => $message,
                'retryAfter' => $retryAfter,
                'retry_after' => $retryAfter,
                'cooldown' => $retryAfter,
            ], 429, $headers);
        }

        return back()->withErrors(['error' => $message])->withHeaders($headers);
    }
}
