<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use App\Models\AttendanceSession;
use App\Policies\AttendanceSessionPolicy;
use App\Events\AttendanceQrChanged;
use App\Events\AttendanceSessionChanged;
use App\Events\TeacherAttendanceUpdated;
use App\Listeners\QueueTeacherQrNotification;
use App\Listeners\AuditAttendanceSessionChange;
use App\Listeners\QueueTeacherAttendanceNotification;
use App\Http\Middleware\SecurityHeaders;
use App\Services\VersionService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VersionService::class, function () {
            return new VersionService();
        });
    }

    public function boot(): void
    {
        Gate::policy(AttendanceSession::class, AttendanceSessionPolicy::class);
        Event::listen(AttendanceQrChanged::class, QueueTeacherQrNotification::class);
        Event::listen(AttendanceSessionChanged::class, AuditAttendanceSessionChange::class);
        Event::listen(TeacherAttendanceUpdated::class, QueueTeacherAttendanceNotification::class);
        // ── Centralized Application Version Directives & Global View Sharing ───
        Blade::directive('appVersion', function () {
            return '<?php echo e(app(\App\Services\VersionService::class)->getVersion()); ?>';
        });
        Blade::directive('appVersionTag', function () {
            return '<?php echo e(app(\App\Services\VersionService::class)->getVersionTag()); ?>';
        });
        Blade::directive('appInstalledVersion', function () {
            return '<?php echo e(app(\App\Services\VersionService::class)->getInstalledVersion()); ?>';
        });
        Blade::directive('appInstalledVersionTag', function () {
            return '<?php echo e(app(\App\Services\VersionService::class)->getInstalledVersionTag()); ?>';
        });
        Blade::directive('appBuild', function () {
            return '<?php echo e(app(\App\Services\VersionService::class)->getBuild()); ?>';
        });
        Blade::directive('appReleaseDate', function () {
            return '<?php echo e(app(\App\Services\VersionService::class)->getFormattedReleaseDate()); ?>';
        });
        Blade::if('feature', fn (string $name): bool => app(\App\Services\FeatureFlagService::class)->enabled($name));

        View::composer('*', function ($view) {
            // A page renders many nested Blade views. Build this shared data once
            // per HTTP request instead of repeating file and database reads for
            // every component. Console jobs and tests can render views after
            // changing settings, so they continue to read fresh values.
            $request = request();
            $cacheKey = 'shared_version_view_data';
            $cacheForRequest = !app()->runningUnitTests() && $request->route() !== null;
            if ($cacheForRequest && $request->attributes->has($cacheKey)) {
                $view->with($request->attributes->get($cacheKey));
                return;
            }

            $versionService = app(VersionService::class);
            $latestVer = $versionService->getLatestVersion();
            $installedVer = $versionService->getInstalledVersion();

            $data = [
                'appVersion'             => $latestVer,
                'appVersionTag'          => $versionService->getVersionTag(),
                'appCurrentVersion'      => $installedVer,
                'appLatestVersion'       => $latestVer,
                'appBuild'               => $versionService->getBuild(),
                'appCommit'              => $versionService->getCommit(),
                'releaseDate'            => $versionService->getReleaseDate(),
                'appReleaseDate'         => $versionService->getFormattedReleaseDate(),
                'appInstalledVersion'    => $installedVer,
                'appInstalledVersionTag' => $versionService->getInstalledVersionTag(),
                'appIsUpToDate'          => $versionService->isUpToDate(),
                'appMetadata'            => $versionService->getFullMetadata(),
            ];
            if ($cacheForRequest) {
                $request->attributes->set($cacheKey, $data);
            }
            $view->with($data);
        });

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

        // Face-photo enrollment invokes the OpenCV models; limit expensive per-user uploads.
        RateLimiter::for('profile-photo', function (Request $request) {
            $identity = (string) ($request->user()?->getAuthIdentifier() ?? $request->ip() ?? 'unknown');

            return Limit::perMinute(5)->by($this->rateLimitKey('profile_photo', $identity));
        });
        
        // 1. Login: Multi-tier protection (5 attempts/min per account+IP, 10 attempts/min per IP)
        RateLimiter::for('login', function (Request $request) {
            $fields = $request->is('api/*')
                ? ['identifier', 'student_id', 'student_number', 'studentId', 'email', 'username', 'employee_id', 'employeeId', 'id', 'login', 'user', 'user_id', 'userId']
                : ['identifier', 'phone', 'phone_number', 'mobile', 'student_id', 'student_number', 'studentId', 'email', 'username', 'employee_id', 'employeeId', 'id', 'login', 'user', 'user_id', 'userId'];
            $identifier = $this->requestIdentifier($request, $fields);
            $ip = $request->ip() ?: 'unknown';
            $accountIpKey = $this->rateLimitKey('login_id', ($identifier ?: 'missing') . '|' . $ip);
            $ipKey = $this->rateLimitKey('login_ip', $ip);

            return [
                Limit::perMinute(5)->by($accountIpKey)->response(function (Request $request, array $headers) use ($identifier, $ip) {
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
                Limit::perMinute(10)->by($ipKey)->response(function (Request $request, array $headers) use ($identifier, $ip) {
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

        // 1b. WebAuthn Options: Pre-flight check limiter (30/min per IP) to avoid choking on credential lookups
        RateLimiter::for('webauthn.options', function (Request $request) {
            $ip = $request->ip() ?: 'unknown';
            return [
                Limit::perMinute(30)->by($this->rateLimitKey('webauthn_opt', $ip))->response(function (Request $request, array $headers) {
                    return response()->json([
                        'status' => 'error',
                        'success' => false,
                        'message' => 'Too many requests. Please wait a moment and try again.',
                        'retry_after' => (int) ($headers['Retry-After'] ?? 10),
                    ], 429, $headers);
                }),
            ];
        });
        // 2. OTP send: Enforce a 30s per-account pace, with a 10/min ceiling for shared IPs.
        RateLimiter::for('otp.send', function (Request $request) {
            $email = $this->requestIdentifier($request, ['email', 'identifier', 'username', 'phone', 'phone_number', 'mobile', 'account_id', 'gmail', 'student_id', 'student_number', 'employee_id']);
            $email = $email ?: strtolower(trim((string) ($request->user()?->email ?? '')));
            $ip = $request->ip() ?: 'unknown';
            $accountKey = $this->rateLimitKey('otp_send_id', $email ?: 'missing|' . $ip);
            $ipKey = $this->rateLimitKey('otp_send_ip', $ip);
            $hourlyIpKey = $this->rateLimitKey('otp_send_ip_hr', $ip);

            return [
                // Per-account limit: Max 2/min (permits initial send + 30s cooldown resend)
                Limit::perMinute(2)->by($accountKey)->response(function (Request $request, array $headers) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? 30);
                    return $this->format429Response($request, 'Please wait ' . $retryAfter . ' seconds before requesting another code.', $headers);
                }),
                // Per-IP limit: Max 10/min to safely support legitimate users on shared networks while blocking flood attacks
                Limit::perMinute(10)->by($ipKey)->response(function (Request $request, array $headers) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? 60);
                    return $this->format429Response($request, 'Too many OTP requests from this network. Please wait ' . $retryAfter . ' seconds.', $headers);
                }),
                // Hourly burst ceiling per IP: Max 100/hr
                Limit::perHour(100)->by($hourlyIpKey)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Hourly OTP request limit exceeded for this network. Please try again later.', $headers);
                }),
            ];
        });

        // 3. OTP verify: Max 5 attempts/min to prevent code brute-forcing
        RateLimiter::for('otp.verify', function (Request $request) {
            $email = $this->requestIdentifier($request, ['email', 'identifier', 'username', 'phone', 'phone_number', 'mobile', 'account_id', 'gmail', 'student_id', 'student_number', 'employee_id']);
            $email = $email ?: strtolower(trim((string) ($request->user()?->email ?? '')));
            $ip = $request->ip() ?: 'unknown';
            $accountKey = $this->rateLimitKey('otp_verify_id', $email ?: 'missing|' . $ip);
            $ipKey = $this->rateLimitKey('otp_verify_ip', $ip);

            return [
                Limit::perMinute(5)->by($accountKey)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many OTP verification attempts. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
                Limit::perMinute(20)->by($ipKey)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many verification attempts from this network. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
            ];
        });

        // 4. Password change & reset: 2 attempts/min per account and 10 per IP.
        RateLimiter::for('password.reset', function (Request $request) {
            $email = $this->requestIdentifier($request, ['email', 'identifier', 'username', 'student_id', 'student_number', 'studentId', 'employee_id', 'employeeId', 'id', 'login', 'user', 'user_id', 'userId']);
            $ip = $request->ip() ?: 'unknown';
            $accountKey = $this->rateLimitKey('pw_reset_id', $email ?: 'missing|' . $ip);
            $ipKey = $this->rateLimitKey('pw_reset_ip', $ip);

            return [
                Limit::perMinute(2)->by($accountKey)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many password reset requests. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
                Limit::perMinute(10)->by($ipKey)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many requests from this network.', $headers);
                }),
            ];
        });

        RateLimiter::for('password.change', function (Request $request) {
            $key = $request->user()?->id
                ? $this->rateLimitKey('pw_change_user', (string) $request->user()->id)
                : $this->rateLimitKey('pw_change_ip', $request->ip() ?: 'unknown');
            return Limit::perMinute(3)->by('pw_change:' . $key)->response(function (Request $request, array $headers) {
                return $this->format429Response($request, 'Too many password change attempts. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
            });
        });

        // 5. Email verification & resend protection
        RateLimiter::for('email.verify', function (Request $request) {
            $email = $this->requestIdentifier($request, ['email', 'identifier', 'gmail']);
            $email = $email ?: strtolower(trim((string) ($request->user()?->email ?? '')));
            $ip = $request->ip() ?: 'unknown';
            $accountKey = $this->rateLimitKey('email_verify_id', $email ?: 'missing|' . $ip);
            $ipKey = $this->rateLimitKey('email_verify_ip', $ip);
            $hourlyIpKey = $this->rateLimitKey('email_verify_ip_hr', $ip);

            return [
                Limit::perMinute(2)->by($accountKey)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Please wait ' . ($headers['Retry-After'] ?? 30) . ' seconds before requesting another verification email.', $headers);
                }),
                Limit::perMinute(20)->by($ipKey)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Too many email requests from this network. Please wait ' . ($headers['Retry-After'] ?? 60) . ' seconds.', $headers);
                }),
                Limit::perHour(100)->by($hourlyIpKey)->response(function (Request $request, array $headers) {
                    return $this->format429Response($request, 'Hourly email verification request limit exceeded.', $headers);
                }),
            ];
        });

        // 6. Global API Rate Limiter: 60 requests/min per IP / authenticated user
        RateLimiter::for('api', function (Request $request) {
            $ip = $request->ip() ?: 'unknown';
            $userKey = $request->user()?->id
                ? 'user:' . $request->user()->id
                : ($request->bearerToken() ? 'token:' . $request->bearerToken() : null);

            $limits = [
                Limit::perMinute(60)->by($this->rateLimitKey('api_ip', $ip))->response(function (Request $request, array $headers) {
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
                $limits[] = Limit::perMinute(60)->by($this->rateLimitKey('api_identity', $userKey))->response(function (Request $request, array $headers) {
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

    /** Return the first non-empty scalar using the configured field order. */
    private function requestIdentifier(Request $request, array $fields): string
    {
        foreach ($fields as $field) {
            $value = $request->input($field);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return strtolower(trim((string) $value));
            }
        }

        return '';
    }

    /** Hash rate-limit identities so cache keys do not expose account or IP data. */
    private function rateLimitKey(string $scope, string $identity): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            $key = 'attendance-rate-limit';
        }

        return $scope . ':' . hash_hmac('sha256', strtolower(trim($identity)), $key);
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
