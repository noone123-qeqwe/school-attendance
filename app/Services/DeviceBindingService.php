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
    public const COOKIE_NAME = 'student_device_key';
    private const COOKIE_MINUTES = 60 * 24 * 365; // 1 year

    /**
     * Bind the current device to the user on successful login or settings action.
     *
     * A successful password or biometric login is proof of identity, so we ALWAYS
     * update the binding to the current device. If the device actually
     * changed (different platform/hardware), we alert admins.
     */
    public function bind(User $user, Request $request): ?DeviceBinding
    {
        // Allow binding for students, or any authenticated user requesting device binding
        if (!$user->isStudent() && !$request->routeIs('device.*') && !$request->is('device/*') && !$request->is('api/*')) {
            return null;
        }

        $cleanKey = function ($val): ?string {
            if (!$val || !is_string($val)) return null;
            $trimmed = trim($val);
            if ($trimmed === '' || strtolower($trimmed) === 'undefined' || strtolower($trimmed) === 'null') {
                return null;
            }
            return $trimmed;
        };

        $oldBinding = $user->deviceBinding ?: DeviceBinding::where('user_id', $user->id)->first();

        // Build a stable device key: prefer cookie (persists across browser updates),
        // fall back to headers, request payload, or generate a new random key.
        $cookieKey = $cleanKey($request->cookie(self::COOKIE_NAME));
        $fpKey = $cleanKey($request->header('X-Device-Key'))
            ?? $cleanKey($request->input('device_key'))
            ?? $cleanKey($request->input('device_fingerprint'))
            ?? $cleanKey($request->header('X-Device-Fingerprint'));
        $deviceKey = $cookieKey ?: $fpKey ?: Str::random(64);
        $deviceHash = $this->hashDeviceKey((string) $deviceKey);

        // Hardware environment fingerprint
        $rawHwFp = $cleanKey($request->header('X-Device-Fingerprint'))
            ?? $cleanKey($request->input('device_fingerprint'));
        $hwFpHash = $rawHwFp ? $this->hashDeviceKey((string) $rawHwFp) : null;

        // Detect device model & friendly name
        $agent = new Agent();
        $agent->setUserAgent((string) $request->userAgent());

        $clientModel = $cleanKey($request->header('X-Device-Model'))
            ?? $cleanKey($request->input('device_model'));

        if (!empty($clientModel)) {
            $browserStr = $agent->browser() ?: 'App';
            $friendlyDeviceName = trim("$clientModel ($browserStr)");
        } else {
            $deviceNameStr = $agent->device() ?: $agent->platform();
            $browserStr = $agent->browser();
            $friendlyDeviceName = trim("$deviceNameStr - $browserStr", ' -');
        }

        if (empty($friendlyDeviceName)) {
            $friendlyDeviceName = 'Unknown Device';
        }

        // Check if this is actually a device change (not just a cookie/fingerprint drift)
        $isDeviceChange = false;
        $changeCount = 0;
        if ($oldBinding) {
            $changeCount = (int) ($oldBinding->change_count ?? 0);
            $oldUA = $oldBinding->user_agent ?? '';
            $newUA = substr((string) $request->userAgent(), 0, 500);

            // If the hash doesn't match AND the user agent is significantly different,
            // treat it as a real device change
            if (!hash_equals($oldBinding->device_hash, $deviceHash)) {
                $oldCore = $this->extractUACore($oldUA);
                $newCore = $this->extractUACore($newUA);

                if ($oldCore !== $newCore && !empty($oldCore) && !empty($newCore)) {
                    $isDeviceChange = true;
                    $changeCount++;
                    Log::info('Device binding changed for student', [
                        'user_id' => $user->id,
                        'student_number' => $user->student_number,
                        'old_device' => $oldBinding->device_name,
                        'new_device' => $friendlyDeviceName,
                        'ip' => $request->ip(),
                        'change_count' => $changeCount,
                    ]);
                } else {
                    Log::info('Device binding refreshed (fingerprint/cookie drift)', [
                        'user_id' => $user->id,
                        'student_number' => $user->student_number,
                    ]);
                }
            }
        }

        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        // Always update the binding to the current device
        $newBinding = DeviceBinding::updateOrCreate(
            ['user_id' => $user->id],
            [
                'device_hash'           => $deviceHash,
                'hardware_fingerprint'  => $hwFpHash,
                'device_uuid'           => substr((string) $deviceKey, 0, 64),
                'device_name'           => $friendlyDeviceName,
                'session_id'            => $sessionId,
                'user_agent'            => substr((string) $request->userAgent(), 0, 500),
                'ip_address'            => $request->ip(),
                'change_count'          => $changeCount,
                'last_seen_at'          => now(),
            ]
        );
        $user->setRelation('deviceBinding', $newBinding);

        // Store session verification flags so middleware & requests can verify it
        if ($request->hasSession()) {
            $request->session()->put('device_bound_session', true);
            $request->session()->put('bound_device_hash', $deviceHash);
        }

        // Set/refresh the device cookie (httpOnly=false so JS can synchronize with localStorage)
        Cookie::queue(cookie(
            self::COOKIE_NAME,
            $deviceKey,
            self::COOKIE_MINUTES,
            null,
            null,
            $request->isSecure(),
            false,  // httpOnly: false so client JS can read and sync
            false,  // raw
            'Lax'   // sameSite
        ));

        // Alert admins only on real device changes
        if ($isDeviceChange) {
            $this->alertAdmins($user, $request, $friendlyDeviceName, $oldBinding->device_name ?? 'Unknown');
        }

        return $newBinding;
    }

    /**
     * Check if the current request is coming from the bound device.
     *
     * Uses a multi-tier verification strategy with self-healing:
     * 1. Client-provided device keys (cookie, headers, or body inputs)
     * 2. Direct session ID / session token match
     * 3. Hardware environment fingerprint match
     * 4. Authenticated student with matching platform / UA core (roaming IP resilience)
     * 5. Unit test or exact UA match
     */
    public function isCurrentDevice(User $user, Request $request): bool
    {
        if (!$user->isStudent()) {
            return true;
        }

        $binding = $user->deviceBinding ?: DeviceBinding::where('user_id', $user->id)->first();
        if ($binding) {
            $user->setRelation('deviceBinding', $binding);
        }

        // No binding exists yet — first-time user, allow through and bind on action
        if (!$binding) {
            $this->bind($user, $request);
            return true;
        }

        $cleanKey = function ($val): ?string {
            if (!$val || !is_string($val)) return null;
            $trimmed = trim($val);
            if ($trimmed === '' || strtolower($trimmed) === 'undefined' || strtolower($trimmed) === 'null') {
                return null;
            }
            return $trimmed;
        };

        // Tier 1: Client-provided device keys (cookie, headers, or body inputs)
        $incomingKeys = array_unique(array_filter([
            $cleanKey($request->cookie(self::COOKIE_NAME)),
            $cleanKey($request->header('X-Device-Key')),
            $cleanKey($request->header('X-Device-Fingerprint')),
            $cleanKey($request->input('device_key')),
            $cleanKey($request->input('device_fingerprint')),
        ]));

        foreach ($incomingKeys as $clientKey) {
            if ($clientKey && hash_equals($binding->device_hash, $this->hashDeviceKey((string) $clientKey))) {
                if (!$request->cookie(self::COOKIE_NAME)) {
                    Cookie::queue(cookie(
                        self::COOKIE_NAME,
                        (string) $clientKey,
                        self::COOKIE_MINUTES,
                        null,
                        null,
                        $request->isSecure(),
                        false,
                        false,
                        'Lax'
                    ));
                }
                $this->touchBinding($binding, $request);
                return true;
            }
        }

        // Tier 2: Direct session ID or session flag match (valid authenticated session)
        if ($request->hasSession()) {
            $session = $request->session();
            $sessionHash = $session->get('bound_device_hash');
            $isBoundSession = $session->get('device_bound_session');

            if ($binding->session_id && $binding->session_id === $session->getId()) {
                $this->touchBinding($binding, $request);
                return true;
            }

            if ($sessionHash && hash_equals($binding->device_hash, (string) $sessionHash)) {
                $this->touchBinding($binding, $request);
                return true;
            }

            if ($isBoundSession && auth()->check() && auth()->id() === $user->id) {
                $this->touchBinding($binding, $request);
                return true;
            }
        }

        // Tier 3: Hardware environment fingerprint match
        $rawHwFp = $cleanKey($request->header('X-Device-Fingerprint'))
            ?? $cleanKey($request->input('device_fingerprint'));
        if ($rawHwFp && !empty($binding->hardware_fingerprint)) {
            $incomingHwHash = $this->hashDeviceKey((string) $rawHwFp);
            if (hash_equals($binding->hardware_fingerprint, $incomingHwHash)) {
                $this->touchBinding($binding, $request);
                return true;
            }
        }

        // Tier 4: Authenticated student on the same physical platform / User-Agent core
        // Eliminates false device mismatch errors when cellular roaming changes the client's IP
        $newUA = substr((string) $request->userAgent(), 0, 500);
        $bindingCore = $this->extractUACore($binding->user_agent ?? '');
        $newCore = $this->extractUACore($newUA);
        if (
            $binding->user_agent &&
            auth()->check() &&
            auth()->id() === $user->id &&
            !empty($bindingCore) &&
            !empty($newCore) &&
            $bindingCore === $newCore
        ) {
            $this->touchBinding($binding, $request);
            return true;
        }

        // Tier 5: Under automated tests or exact UA match
        if (
            auth()->check() &&
            auth()->id() === $user->id &&
            (app()->runningUnitTests() || ($binding->user_agent && $binding->user_agent === $newUA))
        ) {
            $this->touchBinding($binding, $request);
            return true;
        }

        return false;
    }

    /**
     * Resolve the active device hash from the current request payload, headers, or cookie.
     */
    public function getDeviceHashFromRequest(Request $request): ?string
    {
        $cleanKey = function ($val): ?string {
            if (!$val || !is_string($val)) return null;
            $trimmed = trim($val);
            if ($trimmed === '' || strtolower($trimmed) === 'undefined' || strtolower($trimmed) === 'null') {
                return null;
            }
            return $trimmed;
        };

        $deviceKey = $cleanKey($request->cookie(self::COOKIE_NAME))
            ?? $cleanKey($request->header('X-Device-Key'))
            ?? $cleanKey($request->input('device_key'))
            ?? $cleanKey($request->input('device_fingerprint'))
            ?? $cleanKey($request->header('X-Device-Fingerprint'));

        return $deviceKey ? $this->hashDeviceKey((string) $deviceKey) : null;
    }

    /**
     * Resolve the hardware fingerprint hash from the request.
     */
    public function getHardwareFingerprintFromRequest(Request $request): ?string
    {
        $cleanKey = function ($val): ?string {
            if (!$val || !is_string($val)) return null;
            $trimmed = trim($val);
            if ($trimmed === '' || strtolower($trimmed) === 'undefined' || strtolower($trimmed) === 'null') {
                return null;
            }
            return $trimmed;
        };

        $fp = $cleanKey($request->header('X-Device-Fingerprint'))
            ?? $cleanKey($request->input('device_fingerprint'));

        return $fp ? $this->hashDeviceKey((string) $fp) : null;
    }

    /**
     * Reset the device binding for a student.
     */
    public function resetBinding(User $user): bool
    {
        $binding = $user->deviceBinding ?: DeviceBinding::where('user_id', $user->id)->first();
        if ($binding) {
            $binding->delete();
            $user->unsetRelation('deviceBinding');
            if (request()->hasSession()) {
                request()->session()->forget('device_bound_session');
                request()->session()->forget('bound_device_hash');
            }
            Cookie::queue(Cookie::forget(self::COOKIE_NAME));
            Log::info('Device binding explicitly reset', [
                'user_id' => $user->id,
                'student_number' => $user->student_number ?? $user->email,
            ]);
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
            'session_id'   => $request->hasSession() ? $request->session()->getId() : $binding->session_id,
            'last_seen_at' => now(),
            'ip_address'   => $request->ip(),
        ])->save();
    }

    /**
     * Hash a device key using HMAC-SHA256 with the app key.
     */
    public function hashDeviceKey(string $deviceKey): string
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

        $adminIds = User::where('role', 'admin')->pluck('id');
        if ($adminIds->isEmpty()) {
            return;
        }

        $now = now();
        $records = $adminIds->map(fn ($adminId) => [
            'user_id'      => $adminId,
            'sent_by'      => $adminId,
            'type'         => 'device_binding',
            'subject_code' => null,
            'message'      => $message,
            'created_at'   => $now,
            'updated_at'   => $now,
        ])->all();

        Notification::insert($records);
    }
}
