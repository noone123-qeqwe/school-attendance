<?php

namespace App\Http\Middleware;

use App\Services\DeviceBindingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentDeviceIsBound
{
    /**
     * Verify the student is using their bound device.
     *
     * Instead of immediately logging the user out (which causes a frustrating
     * loop), we return an error response that keeps them authenticated but
     * prevents the attendance action from completing.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isStudent()) {
            return $next($request);
        }

        $service = app(DeviceBindingService::class);

        if (!$service->isCurrentDevice($user, $request)) {
            Log::warning('Device binding mismatch for student', [
                'user_id' => $user->id,
                'student_number' => $user->student_number,
                'ip' => $request->ip(),
                'route' => $request->route()?->getName(),
            ]);

            // For AJAX/JSON requests (e.g., QR attendance confirmation)
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This device is not recognized. Please log out and log back in to re-bind your device.',
                    'error_type' => 'device_mismatch',
                ], 403);
            }

            // For standard form submissions — redirect back with error, keep user logged in
            return redirect()->back()->with('error',
                'This device is not recognized. Please log out and log back in to re-bind your device.'
            );
        }

        return $next($request);
    }
}
