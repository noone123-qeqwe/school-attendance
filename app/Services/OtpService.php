<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
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
     * Generate, store, and deliver an OTP to the given email address.
     *
     * Logging sequence (without logging sensitive credentials or OTP values):
     * 1. OTP request received
     * 2. OTP generated
     * 3. OTP stored
     * 4. Email send initiated
     * 5. Email provider accepted request
     *
     * @throws Exception if sending fails or cooldown is active.
     */
    public function sendOtp(
        string $email,
        string $purpose,
        ?int $userId = null,
        ?string $recipientName = null
    ): array {
        $cleanEmail = strtolower(trim($email));
        $maskedEmail = self::maskEmail($cleanEmail);
        $ip = request()->ip() ?: 'unknown';

        // Enforce cooldown
        $cooldown = max(
            Otp::getCooldownRemaining($cleanEmail, $purpose),
            Otp::getCooldownRemaining($ip, $purpose),
            $userId ? Otp::getCooldownRemaining($userId, $purpose) : 0
        );

        if ($cooldown > 0) {
            throw new Exception("Please wait {$cooldown} seconds before requesting another verification code.", 429);
        }

        // Set cooldown on both email and IP immediately to prevent flood
        Otp::setCooldown($cleanEmail, $purpose);
        Otp::setCooldown($ip, $purpose);
        if ($userId) {
            Otp::setCooldown($userId, $purpose);
        }

        // 1. Log request received
        Log::info("OTP request received [purpose: {$purpose}, recipient: {$maskedEmail}]");

        // 2. Invalidate previous OTPs & generate new 6-digit code
        $otp = Otp::generateForEmail($cleanEmail, $purpose, $userId);
        Log::info("OTP generated [purpose: {$purpose}]");

        // 3. Log OTP stored
        Log::info("OTP stored [purpose: {$purpose}]");

        // Resolve name if not explicitly passed
        if (!$recipientName) {
            if ($userId) {
                $recipientName = User::find($userId)?->name ?? 'User';
            } else {
                $user = User::where('email', $cleanEmail)->first();
                $recipientName = $user?->name ?? 'User';
            }
        }

        // 4. Send email
        Log::info("Email send initiated [recipient: {$maskedEmail}]");

        try {
            Mail::to($cleanEmail)->send(new OtpMail($otp->code, $purpose, $recipientName));
            // 5. Email provider accepted request
            Log::info("Email provider accepted request [recipient: {$maskedEmail}]");

            return [
                'success'  => true,
                'message'  => 'Verification code sent to ' . $cleanEmail,
                'cooldown' => Otp::COOLDOWN_SECONDS,
            ];
        } catch (\Throwable $e) {
            Log::error("Email delivery failed [recipient: {$maskedEmail}]: " . $e->getMessage());
            // Invalidate the OTP so unreceived code cannot be exploited
            $otp->update(['used' => true]);

            throw new Exception('Unable to send verification code. Please try again.', 500);
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
