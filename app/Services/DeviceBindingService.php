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
     * An existing device can only be replaced after an explicit password check.
     */
    public function bind(User $user, Request $request, bool $allowRebind = false): ?DeviceBinding
    {
        // Allow binding for students, or any authenticated user requesting device binding
        if (!$user->isStudent() && !$request->routeIs('device.*') && !$request->is('device/*') && !$request->is('api/*')) {
            return null;
        }

        $oldBinding = $user->deviceBinding ?: DeviceBinding::where('user_id', $user->id)->first();

        // An explicit key must take priority over a stale cookie during rebind.
        // Reject conflicting header/body keys instead of choosing one silently.
        $presented = $this->presentedDeviceKey($request);
        if ($presented['conflict']) {
            return null;
        }
        $deviceKey = $presented['key'] ?? Str::random(64);
        $deviceHash = $this->hashDeviceKey((string) $deviceKey);

        if ($oldBinding) {
            if ($oldBinding->isLocked()) {
                return $oldBinding;
            }

            if (!$allowRebind && !hash_equals($oldBinding->device_hash, $deviceHash)) {
                return $oldBinding;
            }
        }

        // Hardware environment fingerprint
        $rawHwFp = $this->cleanKey($request->header('X-Device-Fingerprint'))
            ?? $this->cleanKey($request->input('device_fingerprint'));
        $hwFpHash = $rawHwFp ? $this->hashDeviceKey((string) $rawHwFp) :
            (($oldBinding && hash_equals($oldBinding->device_hash, $deviceHash)) ? $oldBinding->hardware_fingerprint : null);

        // Detect device model & friendly name
        $agent = new Agent();
        $agent->setUserAgent((string) $request->userAgent());

        $clientModel = $this->cleanKey($request->header('X-Device-Model'))
            ?? $this->cleanKey($request->input('device_model'));

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

        // Explicit rebinds to a different key are security events, even when
        // both devices report the same browser and operating system.
        $isDeviceChange = false;
        $changeCount = 0;
        if ($oldBinding) {
            $changeCount = (int) ($oldBinding->change_count ?? 0);
            if (!hash_equals($oldBinding->device_hash, $deviceHash)) {
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
            }
        }

        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        // Extract client telemetry metadata and compute device trust score
        $metadata = $this->extractClientMetadata($request);
        $trustScore = $this->calculateTrustScore($request, $metadata);

        // Update only the recognized device or an explicitly authorized rebind.
        $newBinding = DeviceBinding::updateOrCreate(
            ['user_id' => $user->id],
            [
                'device_hash'           => $deviceHash,
                'hardware_fingerprint'  => $hwFpHash,
                // The raw key is a bearer secret. Only its HMAC belongs in the DB.
                'device_uuid'           => null,
                'device_name'           => $friendlyDeviceName,
                'session_id'            => $sessionId,
                'user_agent'            => substr((string) $request->userAgent(), 0, 500),
                'client_metadata'       => !empty($metadata) ? $metadata : ($oldBinding->client_metadata ?? null),
                'ip_address'            => $request->ip(),
                'change_count'          => $changeCount,
                'trust_score'           => $trustScore,
                'is_locked'             => false,
                'locked_reason'         => null,
                'last_seen_at'          => now(),
                'last_verified_at'      => now(),
            ]
        );
        $user->setRelation('deviceBinding', $newBinding);

        // Store session verification flags so middleware & requests can verify it
        if ($request->hasSession()) {
            $request->session()->put('device_bound_session', true);
            $request->session()->put('bound_device_hash', $deviceHash);
        }

        $this->queueDeviceCookie($deviceKey, $request);

        // Alert admins only on real device changes
        if ($isDeviceChange) {
            $this->alertAdmins($user, $request, $friendlyDeviceName, $oldBinding->device_name ?? 'Unknown');
        }

        return $newBinding;
    }

    /**
     * Check if the current request is coming from the bound device.
     *
     * An explicit device key mismatch must never be rescued by a session or
     * browser fingerprint. Fingerprints are client-controlled telemetry.
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

        // A locked device cannot record attendance under any circumstances
        if ($binding && $binding->isLocked()) {
            Log::warning('Attendance check rejected: student device is locked', [
                'user_id' => $user->id,
                'student_number' => $user->student_number,
                'reason' => $binding->locked_reason,
            ]);
            return false;
        }

        // No binding exists yet — first-time user, allow through and bind on action
        if (!$binding) {
            return $this->bind($user, $request) !== null;
        }

        $presented = $this->presentedDeviceKey($request);
        if ($presented['conflict']) {
            return false;
        }

        if ($presented['key'] !== null) {
            if (!hash_equals($binding->device_hash, $this->hashDeviceKey($presented['key']))) {
                return false;
            }
            if ($this->cleanKey($request->cookie(self::COOKIE_NAME)) !== $presented['key']) {
                $this->queueDeviceCookie($presented['key'], $request);
            }
            $this->touchBinding($binding, $request);
            return true;
        }

        // A legacy authenticated web session may survive cleared browser storage.
        // It is only used when no device key is presented at all.
        if ($request->hasSession()) {
            $session = $request->session();
            $sessionHash = $session->get('bound_device_hash');

            if ($binding->session_id && $binding->session_id === $session->getId()) {
                $this->touchBinding($binding, $request);
                return true;
            }

            if ($sessionHash && hash_equals($binding->device_hash, (string) $sessionHash)) {
                $this->touchBinding($binding, $request);
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the active device hash from the current request payload, headers, or cookie.
     */
    public function getDeviceHashFromRequest(Request $request): ?string
    {
        $presented = $this->presentedDeviceKey($request);
        $deviceKey = $presented['conflict'] ? null : $presented['key'];

        return $deviceKey ? $this->hashDeviceKey((string) $deviceKey) : null;
    }

    private function cleanKey(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $key = trim($value);
        if ($key === '' || strlen($key) > 256 || preg_match('/[\x00-\x1f\x7f]/', $key) ||
            in_array(strtolower($key), ['undefined', 'null'], true)) {
            return null;
        }

        return $key;
    }

    private function hasSuppliedKey(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        if (!is_string($value)) {
            return true;
        }

        $key = strtolower(trim($value));

        return $key !== '' && $key !== 'undefined' && $key !== 'null';
    }

    /** @return array{key: ?string, conflict: bool} */
    private function presentedDeviceKey(Request $request): array
    {
        $headerRaw = $request->header('X-Device-Key');
        $bodyRaw = $request->input('device_key');
        $header = $this->cleanKey($headerRaw);
        $body = $this->cleanKey($bodyRaw);

        if (($this->hasSuppliedKey($headerRaw) && $header === null) ||
            ($this->hasSuppliedKey($bodyRaw) && $body === null) ||
            ($header !== null && $body !== null && !hash_equals($header, $body))) {
            return ['key' => null, 'conflict' => true];
        }

        $fallbackFp = $this->cleanKey($request->input('device_fingerprint'))
            ?? $this->cleanKey($request->header('X-Device-Fingerprint'));

        return [
            'key' => $header ?? $body ?? $this->cleanKey($request->cookie(self::COOKIE_NAME)) ?? $fallbackFp,
            'conflict' => false,
        ];
    }

    private function queueDeviceCookie(string $deviceKey, Request $request): void
    {
        // JavaScript currently synchronizes this cookie with local storage.
        Cookie::queue(cookie(
            self::COOKIE_NAME,
            $deviceKey,
            self::COOKIE_MINUTES,
            null,
            null,
            $request->isSecure(),
            false,
            false,
            'Lax'
        ));
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
            'session_id'        => $request->hasSession() ? $request->session()->getId() : $binding->session_id,
            'last_seen_at'      => now(),
            'last_verified_at'  => now(),
            'ip_address'        => $request->ip(),
        ])->save();
    }

    /**
     * Extract rich hardware and environment telemetry from request headers or payload.
     */
    public function extractClientMetadata(Request $request): array
    {
        $raw = $request->input('device_metadata')
            ?? $request->input('client_metadata')
            ?? $request->header('X-Device-Metadata');

        $metadata = [];
        if (is_array($raw)) {
            $metadata = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $trimmed = trim($raw);
            if (str_starts_with($trimmed, 'eyJ') || str_starts_with($trimmed, 'ey')) {
                $decoded = base64_decode($trimmed, true);
                if ($decoded) {
                    $json = json_decode($decoded, true);
                    if (is_array($json)) $metadata = $json;
                }
            }
            if (empty($metadata)) {
                $json = json_decode($trimmed, true);
                if (is_array($json)) $metadata = $json;
            }
        }

        // Also check individual standard inputs
        $fields = [
            'screen_res', 'pixel_ratio', 'color_depth', 'gpu_renderer', 'gpu_vendor',
            'cores', 'memory_gb', 'touch_points', 'canvas_hash', 'audio_hash',
            'platform', 'webdriver', 'timezone', 'language', 'connection_type'
        ];
        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $metadata[$field] = $request->input($field);
            }
        }

        // Sanitize string lengths and types to prevent DB bloat
        $sanitized = [];
        foreach ($metadata as $k => $v) {
            $cleanedKey = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$k), 0, 32);
            if (empty($cleanedKey)) continue;

            if (is_string($v)) {
                $sanitized[$cleanedKey] = substr(strip_tags($v), 0, 255);
            } elseif (is_numeric($v) || is_bool($v)) {
                $sanitized[$cleanedKey] = $v;
            }
        }

        return $sanitized;
    }

    /**
     * Calculate an objective hardware trust score (0 - 100).
     */
    public function calculateTrustScore(Request $request, array $metadata = []): int
    {
        $score = 80; // Baseline genuine request

        // WebGL GPU renderer inspection
        $gpu = strtolower((string) ($metadata['gpu_renderer'] ?? ''));
        if (!empty($gpu)) {
            if (str_contains($gpu, 'swiftshader') || str_contains($gpu, 'llvmpipe') || str_contains($gpu, 'software rasterizer')) {
                $score -= 35; // Headless / simulated GPU
            } elseif (
                str_contains($gpu, 'nvidia') || str_contains($gpu, 'amd') ||
                str_contains($gpu, 'intel') || str_contains($gpu, 'apple') ||
                str_contains($gpu, 'mali') || str_contains($gpu, 'adreno') ||
                str_contains($gpu, 'geforce') || str_contains($gpu, 'radeon')
            ) {
                $score += 10; // Genuine physical silicon
            }
        }

        // Display resolution sanity
        $res = (string) ($metadata['screen_res'] ?? '');
        if (!empty($res) && preg_match('/^(\d+)x(\d+)/', $res, $m)) {
            $w = (int) $m[1];
            $h = (int) $m[2];
            if ($w >= 320 && $h >= 480) {
                $score += 5;
            } else {
                $score -= 25; // Suspicious micro/zero screen
            }
        }

        // Automation / Headless browser detection
        $isAutomated = !empty($metadata['webdriver']) && filter_var($metadata['webdriver'], FILTER_VALIDATE_BOOLEAN);
        $ua = strtolower((string) $request->userAgent());
        if ($isAutomated || str_contains($ua, 'headless') || str_contains($ua, 'selenium') || str_contains($ua, 'puppeteer')) {
            $score -= 45;
        }

        // Hardware concurrency
        $cores = (int) ($metadata['cores'] ?? 0);
        if ($cores >= 2 && $cores <= 64) {
            $score += 5;
        }

        return (int) max(10, min(100, $score));
    }

    /**
     * Check for proxy attendance conflict (another student clocked in using this same hardware).
     */
    public function detectProxyConflict(User $user, Request $request, ?int $sessionId = null): ?array
    {
        $currentDeviceHash = $user->deviceBinding?->device_hash ?: $this->getDeviceHashFromRequest($request);
        $currentHwFp = $user->deviceBinding?->hardware_fingerprint ?: $this->getHardwareFingerprintFromRequest($request);

        if (!$currentDeviceHash && !$currentHwFp) {
            return null;
        }

        $otherUserIds = DeviceBinding::where(function ($q) use ($currentDeviceHash, $currentHwFp) {
                if ($currentDeviceHash) {
                    $q->where('device_hash', $currentDeviceHash);
                }
                if ($currentHwFp) {
                    $q->orWhere('hardware_fingerprint', $currentHwFp);
                }
            })
            ->where('user_id', '!=', $user->id)
            ->pluck('user_id');

        if ($otherUserIds->isEmpty()) {
            return null;
        }

        if ($sessionId) {
            $peerAttendance = \App\Models\Attendance::where('session_id', $sessionId)
                ->whereIn('user_id', $otherUserIds)
                ->whereIn('status', ['Present', 'Late'])
                ->with('user')
                ->first();

            if ($peerAttendance) {
                return [
                    'conflict'  => true,
                    'peer_id'   => $peerAttendance->user_id,
                    'peer_name' => $peerAttendance->user?->name ?? 'Another student',
                    'session_id'=> $sessionId,
                ];
            }
        }

        return null;
    }

    /**
     * Lock a student's device binding (anti-theft or policy enforcement).
     */
    public function lockBinding(User $user, string $reason = 'manual'): bool
    {
        $binding = $user->deviceBinding ?: DeviceBinding::where('user_id', $user->id)->first();
        if ($binding) {
            $binding->lock($reason);
            Log::info('Device binding locked', [
                'user_id' => $user->id,
                'student_number' => $user->student_number,
                'reason' => $reason,
            ]);
            return true;
        }
        return false;
    }

    /**
     * Unlock a student's device binding.
     */
    public function unlockBinding(User $user): bool
    {
        $binding = $user->deviceBinding ?: DeviceBinding::where('user_id', $user->id)->first();
        if ($binding) {
            $binding->unlock();
            Log::info('Device binding unlocked', [
                'user_id' => $user->id,
                'student_number' => $user->student_number,
            ]);
            return true;
        }
        return false;
    }

    /**
     * Verify step-up authorization password.
     */
    public function verifyStepUpPassword(User $user, string $password): bool
    {
        return \Illuminate\Support\Facades\Hash::check($password, $user->password);
    }

    /**
     * Hash a device key using HMAC-SHA256 with the app key.
     */
    public function hashDeviceKey(string $deviceKey): string
    {
        return hash_hmac('sha256', $deviceKey, config('app.key'));
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
