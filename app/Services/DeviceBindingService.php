<?php

namespace App\Services;

use App\Models\DeviceBinding;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class DeviceBindingService
{
    private const COOKIE_NAME = 'student_device_key';
    private const COOKIE_MINUTES = 60 * 24 * 365; // 1 year

    /**
     * Bind the current device to the user on successful login.
     *
     * A successful password login is proof of identity, so we ALWAYS
     * update the binding to the current device. If the device actually
     * changed (different user agent), we alert admins.
     */
    public function bind(User $user, Request $request): void
    {
        if (!$user->isStudent()) {
            return;
        }

        $oldBinding = $user->deviceBinding;

        // Build a stable device key: prefer cookie (persists across browser updates),
        // fall back to fingerprint, or generate a new random key.
        $cookieKey = $request->cookie(self::COOKIE_NAME);
        $fpKey = $request->input('device_fingerprint');
        $deviceKey = $cookieKey ?: $fpKey ?: Str::random(64);
        $deviceHash = $this->hashDeviceKey($deviceKey);

        // Detect device info for logging
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());
        $deviceNameStr = $agent->device() ?: $agent->platform();
        $browserStr = $agent->browser();
        $friendlyDeviceName = trim("$deviceNameStr - $browserStr", ' -');
        if (empty($friendlyDeviceName)) {
            $friendlyDeviceName = 'Unknown Device';
        }

        // Check if this is actually a device change (not just a cookie/fingerprint drift)
        $isDeviceChange = false;
        if ($oldBinding) {
            $oldUA = $oldBinding->user_agent ?? '';
            $newUA = substr((string) $request->userAgent(), 0, 500);

            // If the hash doesn't match AND the user agent is significantly different,
            // treat it as a real device change
            if (!hash_equals($oldBinding->device_hash, $deviceHash)) {
                // Compare the core of the user agent (platform + device) rather than the full string
                // to avoid false positives from minor browser version bumps
                $oldCore = $this->extractUACore($oldUA);
                $newCore = $this->extractUACore($newUA);

                if ($oldCore !== $newCore) {
                    $isDeviceChange = true;
                    Log::info('Device binding changed for student', [
                        'user_id' => $user->id,
                        'student_number' => $user->student_number,
                        'old_device' => $oldBinding->device_name,
                        'new_device' => $friendlyDeviceName,
                        'ip' => $request->ip(),
                    ]);
                } else {
                    Log::info('Device binding refreshed (fingerprint/cookie drift)', [
                        'user_id' => $user->id,
                        'student_number' => $user->student_number,
                    ]);
                }
            }
        }

        $sessionId = $request->session()->getId();

        // Always update the binding to the current device
        DeviceBinding::updateOrCreate(
            ['user_id' => $user->id],
            [
                'device_hash'  => $deviceHash,
                'device_name'  => $friendlyDeviceName,
                'session_id'   => $sessionId,
                'user_agent'   => substr((string) $request->userAgent(), 0, 500),
                'ip_address'   => $request->ip(),
                'last_seen_at' => now(),
            ]
        );

        // Store the session ID so middleware can verify it
        $request->session()->put('device_bound_session', true);

        // Set/refresh the device cookie
        Cookie::queue(cookie(
            self::COOKIE_NAME,
            $deviceKey,
            self::COOKIE_MINUTES,
            null,
            null,
            $request->isSecure(),
            true,   // httpOnly
            false,  // raw
            'Lax'   // sameSite
        ));

        // Alert admins only on real device changes
        if ($isDeviceChange) {
            $this->alertAdmins($user, $request, $friendlyDeviceName, $oldBinding->device_name ?? 'Unknown');
        }
    }

    /**
     * Check if the current request is coming from the bound device.
     *
     * Uses a 3-tier verification strategy:
     * 1. Cookie match (most reliable, persists across browser updates)
     * 2. Fingerprint match (fallback, can drift)
     * 3. Session flag (set during login — proves user just authenticated)
     */
    public function isCurrentDevice(User $user, Request $request): bool
    {
        if (!$user->isStudent()) {
            return true;
        }

        $binding = $user->deviceBinding;

        // No binding exists yet — first-time user, allow through and bind on action
        if (!$binding) {
            $this->bind($user, $request);
            return true;
        }

        // Tier 1: Cookie check (most stable)
        $cookieKey = $request->cookie(self::COOKIE_NAME);
        if ($cookieKey && hash_equals($binding->device_hash, $this->hashDeviceKey($cookieKey))) {
            $this->touchBinding($binding, $request);
            return true;
        }

        // Tier 2: Fingerprint check (from POST body, query, or headers)
        $fpKey = $request->input('device_fingerprint') ?? $request->header('X-Device-Fingerprint');
        if ($fpKey && hash_equals($binding->device_hash, $this->hashDeviceKey($fpKey))) {
            $this->touchBinding($binding, $request);
            return true;
        }

        // Tier 3: Session flag — set during the login `bind()` call
        if ($request->session()->has('device_bound_session') && $request->session()->get('device_bound_session')) {
            $this->touchBinding($binding, $request);
            return true;
        }

        // Tier 4: Session ID direct match
        if ($binding->session_id && $binding->session_id === $request->session()->getId()) {
            $this->touchBinding($binding, $request);
            return true;
        }

        // Tier 5: Same authenticated user session (prevents cookie loss / PWA partition lockouts)
        if (auth()->check() && auth()->id() === $user->id) {
            $this->touchBinding($binding, $request);
            return true;
        }

        return false;
    }

    /**
     * Update the last_seen timestamp and session on the binding.
     */
    private function touchBinding(DeviceBinding $binding, Request $request): void
    {
        $binding->forceFill([
            'session_id'   => $request->session()->getId(),
            'last_seen_at' => now(),
            'ip_address'   => $request->ip(),
        ])->save();
    }

    /**
     * Hash a device key using HMAC-SHA256 with the app key.
     */
    private function hashDeviceKey(string $deviceKey): string
    {
        return hash_hmac('sha256', $deviceKey, config('app.key'));
    }

    /**
     * Extract the core platform/device part of a user agent string
     * to compare devices without being affected by minor version bumps.
     *
     * e.g. "Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X)" → "iPhone"
     *      "Mozilla/5.0 (Linux; Android 14; Pixel 8)" → "Android Pixel 8"
     */
    private function extractUACore(string $ua): string
    {
        $agent = new Agent();
        $agent->setUserAgent($ua);
        $device = $agent->device() ?: '';
        $platform = $agent->platform() ?: '';
        return strtolower(trim("$platform $device"));
    }

    /**
     * Notify admins when a student's device actually changes.
     */
    private function alertAdmins(User $student, Request $request, string $newDevice, string $oldDevice): void
    {
        $message = "📱 Device change: {$student->name} ({$student->student_number}) switched from \"{$oldDevice}\" to \"{$newDevice}\". IP: {$request->ip()}";

        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id'      => $admin->id,
                'sent_by'      => $admin->id,
                'type'         => 'device_binding',
                'subject_code' => null,
                'message'      => $message,
            ]);
        }
    }
}
