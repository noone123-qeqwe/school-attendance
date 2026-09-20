<?php

namespace App\Http\Controllers;

use App\Models\DeviceBinding;
use App\Services\DeviceBindingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
                'id'              => $binding->id,
                'device_name'     => $binding->device_name ?: 'Registered Device',
                'ip_address'      => $binding->ip_address ?: 'Unknown IP',
                'last_seen_at'    => $binding->last_seen_at?->toIso8601String(),
                'last_seen_human' => $binding->last_seen_at ? $binding->last_seen_at->diffForHumans() : 'Recently',
                'device_icon'     => $binding->getDeviceIcon(),
                'is_locked'       => $binding->isLocked(),
                'change_count'    => (int) $binding->change_count,
            ] : null,
        ]);
    }

    /**
     * Bind the current physical device to the authenticated user.
     */
    public function bind(Request $request, DeviceBindingService $service): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $binding = $service->bind($user, $request);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'           => true,
                'status'            => 'success',
                'message'           => 'This device has been successfully bound to your account for attendance.',
                'is_bound'          => true,
                'is_current_device' => true,
                'binding'           => $binding ? [
                    'id'              => $binding->id,
                    'device_name'     => $binding->device_name ?: 'Registered Device',
                    'ip_address'      => $binding->ip_address,
                    'last_seen_human' => 'Just now',
                    'device_icon'     => $binding->getDeviceIcon(),
                    'is_locked'       => $binding->isLocked(),
                    'change_count'    => (int) $binding->change_count,
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
}
