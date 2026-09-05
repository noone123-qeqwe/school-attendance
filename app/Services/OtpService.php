<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class OtpService
{
    /**
     * Mask email address for secure server-side logging (e.g. j***e@gmail.com).
     */
    public static function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }
        $name = $parts[0];
        $domain = $parts[1];

        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '*';
        } else {
            $maskedName = substr($name, 0, 1) . str_repeat('*', min(5, $len - 2)) . substr($name, -1);
        }

        return $maskedName . '@' . $domain;
    }

    /**
     * Mask IP address for privacy while maintaining diagnostic utility.
     */
    public static function maskIp(?string $ip): string
    {
        if (!$ip || $ip === 'unknown') {
            return 'unknown';
        }
        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);
            return ($parts[0] ?? '') . ':' . ($parts[1] ?? '') . ':****:****';
        }
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.***.***';
        }
        return '***.***';
    }

    /**
     * Log structured server-side OTP telemetry without exposing raw codes or credentials.
     */
    public function logStructuredRequest(
        string $requestId,
        string $email,
        string $purpose,
        string $ip,
        int $cooldown,
        string $emailProvider,
        string $outcome
    ): void {
        $maskedEmail = self::maskEmail($email);
        $maskedIp = self::maskIp($ip);

        Log::info("OTP REQUEST\n" . implode("\n", [
            "Request ID: " . $requestId,
            "Email: " . $maskedEmail,
            "Timestamp: " . now()->toIso8601String(),
            "Purpose: " . $purpose,
            "Endpoint: " . (request()->path() ?: 'internal'),
            "Client IP: " . $maskedIp,
            "Cooldown: " . $cooldown,
            "Email provider: " . $emailProvider,
            "Result: " . $outcome,
        ]));
    }

    /**
     * Generate, store, and deliver an OTP to the given email address.
     * Enforces per-email 30-second cooldown, server-side idempotency, and structured logging.
     *
     * @throws Exception if sending fails or cooldown is active.
     */
    public function sendOtp(
        string $email,
        string $purpose,
        ?int $userId = null,
        ?string $recipientName = null,
        ?string $requestId = null
    ): array {
        $cleanEmail = strtolower(trim($email));
        $maskedEmail = self::maskEmail($cleanEmail);
        $ip = request()->ip() ?: 'unknown';

        // Resolve client-supplied unique request ID for idempotency (if provided)
        $resolvedRequestId = $requestId 
            ?: request()->header('X-Request-Id') 
            ?: request()->input('request_id');
        $logRequestId = $resolvedRequestId ?: ('srv_' . substr(sha1(uniqid('', true)), 0, 8));

        // 1. Idempotency Check: return existing result if exact client request ID was already processed
        $idempotencyKey = $resolvedRequestId ? ('otp_idemp:' . sha1($resolvedRequestId)) : null;
        if ($idempotencyKey && ($cached = Cache::get($idempotencyKey))) {
            Log::info("OTP REQUEST (DUPLICATE IDEMPOTENT)\nRequest ID: {$resolvedRequestId}\nEmail: {$maskedEmail}\nResult: RETURNING_CACHED_RESULT");
            return $cached;
        }

        // 2. Concurrency Lock per email+purpose to serialize parallel double-clicks
        $lockKey = 'otp_lock:' . sha1($cleanEmail . ':' . $purpose);
        $lock = Cache::lock($lockKey, 10);

        try {
            // Wait up to 2 seconds if a concurrent thread is already processing this email
            $lock->block(2);
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            if ($idempotencyKey && ($cached = Cache::get($idempotencyKey))) {
                return $cached;
            }
            throw new Exception("Please wait a moment before requesting another verification code.", 429);
        }

        try {
            // Re-check idempotency cache after acquiring lock
            if ($idempotencyKey && ($cached = Cache::get($idempotencyKey))) {
                return $cached;
            }

            // 3. Enforce cooldown strictly per-email and per-user (NOT on the entire IP)
            // Shared networks (cellular CGNAT, university WiFi) must not lock out different users.
            $cooldown = max(
                Otp::getCooldownRemaining($cleanEmail, $purpose),
                $userId ? Otp::getCooldownRemaining($userId, $purpose) : 0
            );

            if ($cooldown > 0) {
                $this->logStructuredRequest(
                    requestId: $logRequestId,
                    email: $cleanEmail,
                    purpose: $purpose,
                    ip: $ip,
                    cooldown: $cooldown,
                    emailProvider: 'SKIPPED',
                    outcome: 'RATE_LIMITED'
                );

                throw new Exception("Please wait {$cooldown} seconds before requesting another verification code.", 429);
            }

            // 4. Set cooldown timer for this specific email (and user)
            Otp::setCooldown($cleanEmail, $purpose);
            if ($userId) {
                Otp::setCooldown($userId, $purpose);
            }

            // 5. Invalidate previous OTPs & generate new 6-digit code
            $otp = Otp::generateForEmail($cleanEmail, $purpose, $userId);

            // Resolve name if not explicitly passed
            if (!$recipientName) {
                if ($userId) {
                    $recipientName = User::find($userId)?->name ?? 'User';
                } else {
                    $user = User::where('email', $cleanEmail)->first();
                    $recipientName = $user?->name ?? 'User';
                }
            }

            // 6. Send email via configured provider
            try {
                Mail::to($cleanEmail)->send(new OtpMail($otp->code, $purpose, $recipientName));

                $result = [
                    'success'    => true,
                    'message'    => 'Verification code sent to ' . $cleanEmail,
                    'cooldown'   => Otp::COOLDOWN_SECONDS,
                    'retryAfter' => Otp::COOLDOWN_SECONDS,
                    'request_id' => $resolvedRequestId,
                ];

                // Cache successful response for idempotency window if request ID was supplied
                if ($idempotencyKey) {
                    Cache::put($idempotencyKey, $result, 30);
                }

                $this->logStructuredRequest(
                    requestId: $logRequestId,
                    email: $cleanEmail,
                    purpose: $purpose,
                    ip: $ip,
                    cooldown: Otp::COOLDOWN_SECONDS,
                    emailProvider: 'ACCEPTED',
                    outcome: 'SUCCESS'
                );

                return $result;
            } catch (\Throwable $e) {
                Log::error("Email delivery failed [recipient: {$maskedEmail}]: " . $e->getMessage());

                // Invalidate the unreceived OTP so it cannot be guessed
                $otp->update(['used' => true]);

                // Set brief 5s buffer on provider crash so user isn't stuck with 30s lock
                Otp::setCooldown($cleanEmail, $purpose, 5);

                $this->logStructuredRequest(
                    requestId: $logRequestId,
                    email: $cleanEmail,
                    purpose: $purpose,
                    ip: $ip,
                    cooldown: 5,
                    emailProvider: 'REJECTED_OR_FAILED',
                    outcome: 'FAILED'
                );

                throw new Exception('Unable to send verification code. Please try again.', 500);
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Verify an OTP against the database records.
     */
    public function verifyOtp(
        string $emailOrIdentifier,
        string $code,
        string $purpose,
        ?int $userId = null
    ): array {
        $cleanIdentifier = strtolower(trim($emailOrIdentifier));
        $cleanCode = trim($code);
        $maskedIdentifier = str_contains($cleanIdentifier, '@')
            ? self::maskEmail($cleanIdentifier)
            : substr($cleanIdentifier, 0, 2) . '***';

        // Check if user exists by email, student_number, or employee_id
        if (!$userId) {
            $user = User::where('email', $cleanIdentifier)
                ->orWhere('student_number', $cleanIdentifier)
                ->orWhere('employee_id', $cleanIdentifier)
                ->first();
            if ($user) {
                $userId = $user->id;
            }
        }

        // Look for matching active OTP record
        $otpRecord = Otp::where('purpose', $purpose)
            ->where('code', $cleanCode)
            ->where('used', false)
            ->where(function ($query) use ($cleanIdentifier, $userId) {
                $query->where('email', $cleanIdentifier);
                if ($userId) {
                    $query->orWhere('user_id', $userId);
                }
            })
            ->latest()
            ->first();

        // If not found or already used/expired
        if (!$otpRecord) {
            // Check if there was an expired code with this value
            $expired = Otp::where('purpose', $purpose)
                ->where('code', $cleanCode)
                ->where('expires_at', '<=', now())
                ->where(function ($query) use ($cleanIdentifier, $userId) {
                    $query->where('email', $cleanIdentifier);
                    if ($userId) {
                        $query->orWhere('user_id', $userId);
                    }
                })
                ->latest()
                ->first();

            if ($expired) {
                return [
                    'success' => false,
                    'status'  => 'expired',
                    'message' => 'This code has expired. Please request a new one.',
                ];
            }

            $fails = Otp::recordFailedVerify($userId ?: $cleanIdentifier, $purpose);
            if ($fails >= Otp::MAX_VERIFY_ATTEMPTS) {
                Otp::invalidatePrevious($cleanIdentifier, $purpose);
                if ($userId) {
                    Otp::invalidatePrevious($userId, $purpose);
                }
                return [
                    'success' => false,
                    'status'  => 'locked',
                    'message' => 'Too many failed verification attempts. This code has been invalidated. Please request a new one.',
                ];
            }

            return [
                'success'            => false,
                'status'             => 'invalid',
                'message'            => 'Invalid verification code. (' . (Otp::MAX_VERIFY_ATTEMPTS - $fails) . ' attempts remaining)',
                'remaining_attempts' => max(0, Otp::MAX_VERIFY_ATTEMPTS - $fails),
            ];
        }

        // Check expiration
        if ($otpRecord->expires_at->isPast()) {
            return [
                'success' => false,
                'status'  => 'expired',
                'message' => 'This code has expired. Please request a new one.',
            ];
        }

        // Successfully verified
        Otp::clearFailedVerify($userId ?: $cleanIdentifier, $purpose);
        $otpRecord->update(['used' => true]);

        Log::info("OTP verified successfully [purpose: {$purpose}, recipient: {$maskedIdentifier}]");

        return [
            'success' => true,
            'status'  => 'verified',
            'message' => 'Email verified successfully.',
            'otp'     => $otpRecord,
            'user_id' => $userId,
        ];
    }
}
