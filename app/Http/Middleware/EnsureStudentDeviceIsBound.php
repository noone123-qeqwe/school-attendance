<?php

namespace App\Http\Middleware;

use App\Services\DeviceBindingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentDeviceIsBound
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->isAdmin() && !app(DeviceBindingService::class)->isCurrentDevice($user, $request)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'student_number' => 'Your account was opened on another phone. Please log in again on this device.',
            ]);
        }

        return $next($request);
    }
}
