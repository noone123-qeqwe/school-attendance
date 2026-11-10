<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ── Trusted Proxy Configuration ─────────────────────────────────────
        // Trust only X-Forwarded-Proto, never forwarded client IP or host values.
        // Render's client IP is resolved from the edge-overwritten CF header below.
        $trustedProxies = trim((string) env('TRUSTED_PROXIES', ''));
        if ($trustedProxies !== '') {
            $middleware->trustProxies(
                at: $trustedProxies === '*'
                    ? '*'
                    : array_values(array_filter(array_map('trim', explode(',', $trustedProxies)))),
                headers: Request::HEADER_X_FORWARDED_PROTO
            );
        }

        $middleware->encryptCookies(except: [
            'student_device_key',
        ]);

        $middleware->prepend(\App\Http\Middleware\ResolveClientIpFromTrustedHeader::class);

        $middleware->alias([
            'admin'         => \App\Http\Middleware\AdminMiddleware::class,
            'admin.2fa'     => \App\Http\Middleware\AdminTwoFactor::class,
            'admin.api.2fa' => \App\Http\Middleware\AdminApiTwoFactor::class,
            'teacher'       => \App\Http\Middleware\TeacherMiddleware::class,
            'student'       => \App\Http\Middleware\StudentMiddleware::class,
            'parent'        => \App\Http\Middleware\ParentMiddleware::class,
            'device.bound'  => \App\Http\Middleware\EnsureStudentDeviceIsBound::class,
            'audit.attendance.qr' => \App\Http\Middleware\AuditAttendanceQrScan::class,
            'admin.ip'      => \App\Http\Middleware\EnsureAdminIpWhitelisted::class,
            'admin.super'   => \App\Http\Middleware\SuperAdminMiddleware::class,
            'admin.auditor' => \App\Http\Middleware\RestrictAuditor::class,
            'password.rotate.api' => \App\Http\Middleware\RequirePasswordRotation::class,
            'feature' => \App\Http\Middleware\EnsureFeatureEnabled::class,
        ]);

        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->web(append: [
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \App\Http\Middleware\CheckPasswordChange::class,
            \App\Http\Middleware\CheckAccountStatus::class,
            \App\Http\Middleware\EnsureStudentRegistrationApproved::class,
        ]);

        $middleware->api(
            prepend: ['throttle:api'],
            append: [
                \App\Http\Middleware\CheckAccountStatus::class,
                \App\Http\Middleware\EnsureStudentRegistrationApproved::class,
            ],
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\AppException $e, Request $request) {
            \Illuminate\Support\Facades\Log::error('Application domain exception.', [
                'error_code' => $e->errorCode(),
                'exception_type' => $e::class,
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'context' => $e->safeContext(),
            ]);

            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error_code' => $e->errorCode(),
                ], $e->httpStatus());
            }

            return response()->view('errors.domain', [
                'message' => $e->getMessage(),
                'errorCode' => $e->errorCode(),
            ], $e->httpStatus());
        });

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Your session expired. Please refresh the page and try again.',
                ], 419);
            }

            return redirect()->route('login')
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('error', 'Your session expired. Please sign in again.');
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => 'Your session expired. Please refresh the page and try again.',
                    ], 419);
                }

                return redirect()->route('login')
                    ->withInput($request->except('_token', 'password', 'password_confirmation'))
                    ->with('error', 'Your session expired. Please sign in again.');
            }
        });
    })->create();
