<?php

namespace App\Http\Controllers;

use App\Models\DeviceBinding;
use App\Services\DeviceBindingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DeviceBindingController extends Controller
{
    /**
     * Get the device binding status for the authenticated user and current device.
     */
    public function status(Request $request, DeviceBindingService $service): JsonResponse
    {
        $user = $request->user();
        $binding = $user->deviceBinding ?: DeviceBinding::where('user_id', $user->id)->first();
        $isBound = !is_null($binding);
        $isCurrent = $isBound && $service->isCurrentDevice($user, $request);

        return response()->json([
            'success'           => true,
            'is_bound'          => $isBound,
            'is_current_device' => $isCurrent,
            'binding'           => $binding ? [
                'id'                  => $binding->id,
                'device_name'         => $binding->device_name ?: 'Registered Device',
                'ip_address'          => $binding->ip_address ?: 'Unknown IP',
                'last_seen_at'        => $binding->last_seen_at?->toIso8601String(),
                'last_seen_human'     => $binding->last_seen_at ? $binding->last_seen_at->diffForHumans() : 'Recently',
                'last_verified_at'    => $binding->last_verified_at?->toIso8601String(),
                'last_verified_human' => $binding->last_verified_at ? $binding->last_verified_at->diffForHumans() : 'Recently',
                'device_icon'         => $binding->getDeviceIcon(),
                'is_locked'           => $binding->isLocked(),
                'locked_reason'       => $binding->locked_reason,
                'trust_score'         => (int) ($binding->trust_score ?? 85),
                'trust_level'         => $binding->getTrustLevel(),
                'gpu_info'            => $binding->getGpuInfo(),
                'display_info'        => $binding->getDisplayInfo(),
                'change_count'        => (int) $binding->change_count,
                'metadata'            => $binding->client_metadata,
            ] : null,
        ]);
    }

    /**
     * Bind the current physical device to the authenticated user.
     */
    public function bind(Request $request, DeviceBindingService $service): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $oldBinding = $user->deviceBinding ?: DeviceBinding::where('user_id', $user->id)->first();

        // Optional step-up password verification if re-binding to a different physical device
        if ($oldBinding && $request->filled('password')) {
            if (!Hash::check($request->input('password'), $user->password)) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Incorrect password confirmation for device binding.',
                    ], 422);
                }
                return back()->with('error', 'Incorrect password confirmation for device binding.');
            }
        }

        $binding = $service->bind($user, $request);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'           => true,
                'status'            => 'success',
                'message'           => 'This device has been successfully bound to your account for attendance.',
                'is_bound'          => true,
                'is_current_device' => true,
                'binding'           => $binding ? [
                    'id'                  => $binding->id,
                    'device_name'         => $binding->device_name ?: 'Registered Device',
                    'ip_address'          => $binding->ip_address,
                    'last_seen_human'     => 'Just now',
                    'last_verified_human' => 'Just now',
                    'device_icon'         => $binding->getDeviceIcon(),
                    'is_locked'           => $binding->isLocked(),
                    'locked_reason'       => $binding->locked_reason,
                    'trust_score'         => (int) ($binding->trust_score ?? 85),
                    'trust_level'         => $binding->getTrustLevel(),
                    'gpu_info'            => $binding->getGpuInfo(),
                    'display_info'        => $binding->getDisplayInfo(),
                    'change_count'        => (int) $binding->change_count,
                    'metadata'            => $binding->client_metadata,
                ] : null,
            ]);
        }

        return back()->with('success', 'This device has been successfully bound to your account for attendance.');
    }

    /**
     * Unbind the device for the authenticated user.
     */
    public function unbind(Request $request, DeviceBindingService $service): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $service->resetBinding($user);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'           => true,
                'status'            => 'success',
                'message'           => 'Device successfully unbound. You may now bind this or another device.',
                'is_bound'          => false,
                'is_current_device' => false,
                'binding'           => null,
            ]);
        }

        return back()->with('success', 'Device successfully unbound from your account.');
    }

    /**
     * Emergency lock the user's bound device (e.g., lost or stolen hardware).
     */
    public function lock(Request $request, DeviceBindingService $service): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $reason = $request->input('reason', 'Student anti-theft freeze');
        $success = $service->lockBinding($user, (string) $reason);

        if (!$success) {
            $msg = 'No bound device found to lock.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 404);
            }
            return back()->with('error', $msg);
        }

        $msg = 'Device has been locked successfully. Attendance clock-ins are frozen until unlocked.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'   => true,
                'message'   => $msg,
                'is_locked' => true,
            ]);
        }
        return back()->with('success', $msg);
    }

    /**
     * Unlock the user's bound device with password confirmation.
     */
    public function unlock(Request $request, DeviceBindingService $service): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        // Require password confirmation to unlock
        if ($request->filled('password')) {
            if (!Hash::check($request->input('password'), $user->password)) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Incorrect password.',
                    ], 422);
                }
                return back()->with('error', 'Incorrect password.');
            }
        }

        $success = $service->unlockBinding($user);

        if (!$success) {
            $msg = 'No bound device found to unlock.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 404);
            }
            return back()->with('error', $msg);
        }

        $msg = 'Device has been unlocked successfully. You can now record attendance.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'   => true,
                'message'   => $msg,
                'is_locked' => false,
            ]);
        }
        return back()->with('success', $msg);
    }
}
