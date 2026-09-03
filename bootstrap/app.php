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
        // TRUSTED_PROXIES: comma-separated IPs/CIDRs, or '*' to trust all proxies.
        // In production (Render, Railway, Heroku, nginx reverse-proxy) set this to
        // the actual proxy IP ranges so isSecure(), ip(), and URL generation all
        // resolve correctly from X-Forwarded-* headers.
        // Example .env values:
        //   TRUSTED_PROXIES=*                         (trust all — safe behind managed PaaS)
        //   TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12 (specific CIDR ranges)
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin'         => \App\Http\Middleware\AdminMiddleware::class,
            'admin.2fa'     => \App\Http\Middleware\AdminTwoFactor::class,
            'teacher'       => \App\Http\Middleware\TeacherMiddleware::class,
            'student'       => \App\Http\Middleware\StudentMiddleware::class,
            'parent'        => \App\Http\Middleware\ParentMiddleware::class,
            'device.bound'  => \App\Http\Middleware\EnsureStudentDeviceIsBound::class,
            'admin.ip'      => \App\Http\Middleware\EnsureAdminIpWhitelisted::class,
            'admin.super'   => \App\Http\Middleware\SuperAdminMiddleware::class,
            'dept_head'     => \App\Http\Middleware\DepartmentHeadMiddleware::class,
            'admin.auditor' => \App\Http\Middleware\RestrictAuditor::class,
        ]);

        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\CheckPasswordChange::class,
            \App\Http\Middleware\CheckAccountStatus::class,
        ]);

        $middleware->api(
            prepend: ['throttle:api'],
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
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