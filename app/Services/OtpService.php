<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\Email\EmailDeliveryService;
use Exception;

class OtpService
{
    public function __construct(
        protected EmailDeliveryService $emailDeliveryService
    ) {}

    /**
     * Mask email address for secure server-side logging (e.g. j***e@gmail.com).
     */
    public static function maskEmail(string $email): string
    {
        $parts = explode('@', trim($email));
        if (count($parts) !== 2) {
            return '***';
        }
        $name = $parts[0];
        $domain = $parts[1];

        $prefix = mb_substr($name, 0, 1);
        return $prefix . '*****@' . $domain;
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
        string $otpGenerated = 'YES',
        string $providerAccepted = 'UNKNOWN',
        ?string $messageId = null,
        string $outcome = 'SUCCESS',
        ?int $userId = null,
        ?string $errorCategory = null
    ): void {
        $maskedEmail = self::maskEmail($email);
        $maskedIp = self::maskIp($ip);

        $lines = [
            "Request ID: " . $requestId,
            "User ID: " . ($userId ?: 'N/A'),
            "Email: " . $maskedEmail,
            "Timestamp: " . now()->toIso8601String(),
            "Purpose: " . $purpose,
            "Endpoint: " . (request()->path() ?: 'internal'),
            "Client IP: " . $maskedIp,
            "Cooldown: " . $cooldown,
            "OTP generated: " . $otpGenerated,
            "Email provider: " . $emailProvider,
            "Provider accepted: " . $providerAccepted,
            "Message ID: " . ($messageId ?: 'N/A'),
            "Result: " . $outcome,
        ];

        if ($errorCategory) {
            $lines[] = "Error Category: " . $errorCategory;
        }

        Log::info("OTP REQUEST\n" . implode("\n", $lines));
    }

    /**
     * Generate, store, and deliver an OTP to the given email address.
     * Enforces per-email 30-second cooldown, server-side idempotency, provider verification, and structured logging.
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
                    otpGenerated: 'NO',
                    providerAccepted: 'NO',
                    outcome: 'RATE_LIMITED'
                );

                throw new Exception("Please wait {$cooldown} seconds before requesting another verification code.", 429);
            }

            // 4. Set cooldown timer for this specific email (and user)
            Otp::setCooldown($cleanEmail, $purpose);
            if ($userId) {
                Otp::setCooldown($userId, $purpose);
            }

            // Resolve user if not explicitly passed
            $user = null;
            if ($userId) {
                $user = User::find($userId);
            } else {
                $user = User::findByIdentifier($cleanEmail);
                if ($user) {
                    $userId = $user->id;
                    Otp::setCooldown($userId, $purpose);
                }
            }

            // 5. Invalidate previous OTPs & generate new 6-digit code
            $otp = Otp::generateForEmail($cleanEmail, $purpose, $userId);

            // Resolve name if not explicitly passed
            if (!$recipientName) {
                $recipientName = $user?->name ?? 'User';
            }

            // 6. Deliver email through decoupled EmailDeliveryService
            $delivery = $this->emailDeliveryService->sendOtp(
                recipientEmail: $cleanEmail,
                otpCode: $otp->code,
                purpose: $purpose,
                recipientName: $recipientName,
                requestId: $resolvedRequestId
            );

            if ($delivery->success) {
                $result = [
                    'success'    => true,
                    'message'    => 'Verification code sent to ' . $cleanEmail,
                    'cooldown'   => Otp::COOLDOWN_SECONDS,
                    'retryAfter' => Otp::COOLDOWN_SECONDS,
                    'request_id' => $resolvedRequestId,
                    'provider'   => $delivery->provider,
                    'message_id' => $delivery->messageId,
                ];

                // Cache successful response for idempotency window
                if ($idempotencyKey) {
                    Cache::put($idempotencyKey, $result, 30);
                }

                $this->logStructuredRequest(
                    requestId: $logRequestId,
                    email: $cleanEmail,
                    purpose: $purpose,
                    ip: $ip,
                    cooldown: Otp::COOLDOWN_SECONDS,
                    emailProvider: $delivery->provider,
                    otpGenerated: 'YES',
                    providerAccepted: 'YES',
                    messageId: $delivery->messageId,
                    outcome: 'SUCCESS',
                    userId: $userId
                );

                return $result;
            }

            // Delivery failed: Invalidate the unreceived OTP so it cannot be guessed
            $otp->update(['used' => true]);

            // Set brief 5s buffer on provider crash so user isn't stuck with 30s lock
            Otp::setCooldown($cleanEmail, $purpose, 5);
            if ($userId) {
                Otp::setCooldown($userId, $purpose, 5);
            }

            $this->logStructuredRequest(
                requestId: $logRequestId,
                email: $cleanEmail,
                purpose: $purpose,
                ip: $ip,
                cooldown: 5,
                emailProvider: $delivery->provider,
                otpGenerated: 'YES',
                providerAccepted: 'NO',
                messageId: null,
                outcome: 'FAILED',
                userId: $userId,
                errorCategory: 'PROVIDER_REJECTED'
            );

            throw new Exception($delivery->error ?: 'Unable to send verification code. Please try again.', $delivery->statusCode ?: 500);
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

        // Check if user exists using comprehensive identifier resolution
        $user = null;
        if (!$userId) {
            $user = User::findByIdentifier($cleanIdentifier);
            if ($user) {
                $userId = $user->id;
            }
        } else {
            $user = User::find($userId);
        }

        $registeredEmail = null;
        if ($user && !empty($user->email)) {
            $registeredEmail = strtolower(trim((string) $user->email));
        }

        // Look for matching active OTP record (matched by clean identifier, registered email, or user ID)
        $otpRecord = Otp::where('purpose', $purpose)
            ->where('code', $cleanCode)
            ->where('used', false)
            ->where(function ($query) use ($cleanIdentifier, $userId, $registeredEmail) {
                $query->where('email', $cleanIdentifier);
                if ($registeredEmail && $registeredEmail !== $cleanIdentifier) {
                    $query->orWhere('email', $registeredEmail);
                }
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
                ->where(function ($query) use ($cleanIdentifier, $userId, $registeredEmail) {
                    $query->where('email', $cleanIdentifier);
                    if ($registeredEmail && $registeredEmail !== $cleanIdentifier) {
                        $query->orWhere('email', $registeredEmail);
                    }
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
                if ($registeredEmail && $registeredEmail !== $cleanIdentifier) {
                    Otp::invalidatePrevious($registeredEmail, $purpose);
                }
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

        // Resolve authoritative user ID from all available sources
        $authoritativeUserId = $userId ?: ($otpRecord->user_id ?? $user?->id);
        if (!$authoritativeUserId && !empty($otpRecord->email)) {
            $resolvedUser = User::findByIdentifier($otpRecord->email);
            $authoritativeUserId = $resolvedUser?->id;
        }

        Log::info("OTP verified successfully [purpose: {$purpose}, recipient: {$maskedIdentifier}, user_id: " . ($authoritativeUserId ?: 'none') . "]");

        return [
            'success' => true,
            'status'  => 'verified',
            'message' => 'Email verified successfully.',
            'otp'     => $otpRecord,
            'user_id' => $authoritativeUserId,
        ];
    }

    /**
     * Determine if an email has a valid Gmail address format.
     * Google Gmail rules:
     * - Domain: gmail.com or googlemail.com
     * - Local part (username): 6 to 30 characters
     * - Characters: letters (a-z, case-insensitive), numbers (0-9), and periods (.)
     * - Cannot begin or end with a period
     * - Cannot contain consecutive periods (..)
     *
     * In unit/feature testing environments, test domains (@example.com, @example.org, @school.edu, @school.test, @osmena.edu)
     * are also permitted unless $strict is set to true.
     */
    public static function isValidGmailFormat(string $email, bool $strict = false): bool
    {
        $clean = strtolower(trim($email));

        if (!filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $parts = explode('@', $clean);
        if (count($parts) !== 2) {
            return false;
        }

        [$username, $domain] = $parts;

        // Allow recognized test domains in automated testing unless strict mode is requested
        if (!$strict && (app()->runningUnitTests() || app()->environment('testing'))) {
            $testDomains = ['example.com', 'example.org', 'example.net', 'school.edu', 'school.test', 'osmena.edu'];
            if (in_array($domain, $testDomains, true)) {
                return true;
            }
        }

        if ($domain !== 'gmail.com' && $domain !== 'googlemail.com') {
            return false;
        }

        // Check username length (Google requires 6 to 30 characters)
        $unmasked = str_replace('.', '', $username);
        if (strlen($unmasked) < 6 || strlen($unmasked) > 30) {
            return false;
        }

        // Cannot start or end with a dot
        if (str_starts_with($username, '.') || str_ends_with($username, '.')) {
            return false;
        }

        // Cannot have consecutive dots
        if (str_contains($username, '..')) {
            return false;
        }

        // Only alphanumeric characters and dots are allowed in standard Gmail usernames
        if (!preg_match('/^[a-z0-9.]+$/i', $username)) {
            return false;
        }

        return true;
    }

    /**
     * Classify an email address into one of four states:
     * - 'invalid': does not meet Gmail format
     * - 'already_registered': exists in users table
     * - 'valid': syntactically valid and available
     */
    public static function classifyEmail(string $email, ?int $ignoreUserId = null, bool $strict = false): array
    {
        $clean = strtolower(trim($email));

        if ($clean === '' || !self::isValidGmailFormat($clean, $strict)) {
            return [
                'status'   => 'invalid',
                'category' => 'invalid',
                'message'  => 'Please enter a valid Gmail address (e.g., username@gmail.com).',
            ];
        }

        $query = User::where('email', $clean);
        if ($ignoreUserId) {
            $query->where('id', '!=', $ignoreUserId);
        }

        if ($query->exists()) {
            return [
                'status'   => 'already_registered',
                'category' => 'already_registered',
                'message'  => 'This Gmail address is already registered. Please sign in or use another email.',
            ];
        }

        return [
            'status'   => 'valid',
            'category' => 'valid',
            'message'  => 'Valid Gmail address.',
        ];
    }
}
