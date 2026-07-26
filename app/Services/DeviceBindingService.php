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

class DeviceBindingService
{
    private const COOKIE_NAME = 'student_device_key';
    private const COOKIE_MINUTES = 60 * 24 * 365;

    public function bind(User $user, Request $request): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $deviceKey = $request->cookie(self::COOKIE_NAME) ?: Str::random(64);
        $deviceHash = $this->hashDeviceKey($deviceKey);
        $sessionId = $request->session()->getId();
        $binding = $user->deviceBinding;
        $replaced = $binding && $binding->device_hash !== $deviceHash;

        if ($replaced && $binding->session_id && Schema::hasTable('sessions')) {
            DB::table('sessions')->where('id', $binding->session_id)->delete();
        }

        DeviceBinding::updateOrCreate(
            ['user_id' => $user->id],
            [
                'device_hash' => $deviceHash,
                'session_id' => $sessionId,
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'ip_address' => $request->ip(),
                'last_seen_at' => now(),
            ]
        );

        Cookie::queue(cookie(self::COOKIE_NAME, $deviceKey, self::COOKIE_MINUTES, null, null, $request->isSecure(), true, false, 'Lax'));

        if ($replaced) {
            $this->alertAdmins($user, $request);
        }
    }

    public function isCurrentDevice(User $user, Request $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $deviceKey = $request->cookie(self::COOKIE_NAME);
        $binding = $user->deviceBinding;

        if (!$deviceKey || !$binding) {
            return false;
        }

        if (!hash_equals($binding->device_hash, $this->hashDeviceKey($deviceKey))) {
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
