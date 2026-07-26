<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin'         => \App\Http\Middleware\AdminMiddleware::class,
            'admin.2fa'     => \App\Http\Middleware\AdminTwoFactor::class,
            'teacher'       => \App\Http\Middleware\TeacherMiddleware::class,
            'student'       => \App\Http\Middleware\StudentMiddleware::class,
            'parent'        => \App\Http\Middleware\ParentMiddleware::class,
            'device.bound'  => \App\Http\Middleware\EnsureStudentDeviceIsBound::class,
        ]);
        $middleware->prepend(\App\Http\Middleware\NgrokBypass::class);

        $middleware->validateCsrfTokens(except: [
            'login',
            'register',
            'logout',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();