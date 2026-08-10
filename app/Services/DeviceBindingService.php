<?php

namespace App\Services;

use App\Models\DeviceBinding;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class DeviceBindingService
{
    private const COOKIE_NAME = 'student_device_key';
    private const COOKIE_MINUTES = 60 * 24 * 365;

    public function bind(User $user, Request $request): void
    {
        if (!$user->isStudent()) {
            return;
        }

        $fpKey = $request->input('device_fingerprint');
        $cookieKey = $request->cookie(self::COOKIE_NAME);
        
        $binding = $user->deviceBinding;
        $deviceKey = null;
        $deviceHash = null;

        // Strict Binding Check: If they have a binding, check if either cookie or fingerprint matches
        if ($binding) {
            $fpHash = $fpKey ? $this->hashDeviceKey($fpKey) : null;
            $cookieHash = $cookieKey ? $this->hashDeviceKey($cookieKey) : null;
            
            if ($cookieHash && hash_equals($binding->device_hash, $cookieHash)) {
                $deviceKey = $cookieKey;
                $deviceHash = $cookieHash;
            } elseif ($fpHash && hash_equals($binding->device_hash, $fpHash)) {
                $deviceKey = $fpKey;
                $deviceHash = $fpHash;
            } else {
                \Illuminate\Support\Facades\Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'identifier' => 'Your account is already bound to another device. Please contact an admin to reset it.',
                ]);
            }
        } else {
            // First time binding
            $deviceKey = $fpKey ?: $cookieKey ?: Str::random(64);
            $deviceHash = $this->hashDeviceKey($deviceKey);
        }

        $sessionId = $request->session()->getId();

        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());
        $deviceNameStr = $agent->device() ?: $agent->platform();
        $browserStr = $agent->browser();
        $friendlyDeviceName = trim("$deviceNameStr - $browserStr", ' -');
        if (empty($friendlyDeviceName)) {
            $friendlyDeviceName = 'Unknown Device';
        }

        DeviceBinding::updateOrCreate(
            ['user_id' => $user->id],
            [
                'device_hash' => $deviceHash,
                'device_name' => $friendlyDeviceName,
                'session_id' => $sessionId,
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'ip_address' => $request->ip(),
                'last_seen_at' => now(),
            ]
        );

        Cookie::queue(cookie(self::COOKIE_NAME, $deviceKey, self::COOKIE_MINUTES, null, null, $request->isSecure(), true, false, 'Lax'));
    }

    public function isCurrentDevice(User $user, Request $request): bool
    {
        if (!$user->isStudent()) {
            return true;
        }

        $binding = $user->deviceBinding;
        if (!$binding) {
            return false;
        }

        $cookieKey = $request->cookie(self::COOKIE_NAME);
        $fpKey = $request->input('device_fingerprint');

        $isValid = false;
        if ($cookieKey && hash_equals($binding->device_hash, $this->hashDeviceKey($cookieKey))) {
            $isValid = true;
        } elseif ($fpKey && hash_equals($binding->device_hash, $this->hashDeviceKey($fpKey))) {
            $isValid = true;
        }

        if (!$isValid) {
            return false;
        }

        // Update session_id on each valid request to keep it current
        $binding->forceFill([
            'session_id' => $request->session()->getId(),
            'last_seen_at' => now(),
            'ip_address' => $request->ip(),
        ])->save();

        return true;
    }

    private function hashDeviceKey(string $deviceKey): string
    {
        return hash_hmac('sha256', $deviceKey, config('app.key'));
    }

    private function alertAdmins(User $student, Request $request): void
    {
        $message = "Device binding alert: {$student->name} ({$student->student_number}) logged in from a new phone. The old session was removed.";
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'sent_by' => $admin->id,
                'type' => 'device_binding',
                'subject_code' => null,
                'message' => $message . ' IP: ' . $request->ip(),
            ]);
        }
    }
}
