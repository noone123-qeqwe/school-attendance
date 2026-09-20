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

        $enforce = (bool) \App\Models\Setting::get('enforce_device_binding', 1);
        if (!$enforce) {
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

            $boundDeviceName = $user->deviceBinding?->device_name ?: 'your registered device';
            $errorMsg = "Unrecognized device. Attendance is restricted to {$boundDeviceName}. Please sign in from your bound device or request a reset from your instructor.";

            // For AJAX/JSON requests (e.g., QR attendance confirmation)
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                    'error_type' => 'device_mismatch',
                    'bound_device' => $user->deviceBinding?->device_name,
                ], 403);
            }

            // For standard form submissions — redirect back with error, keep user logged in
            return redirect()->back()->with('error', $errorMsg);
        }

        return $next($request);
    }
}
