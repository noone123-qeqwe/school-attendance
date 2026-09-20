<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Email\EmailDeliveryService;
class OtpApiController extends Controller
{
    /**
     * Send or request an OTP (/api/otp, /api/otp/send).
     */
    public function sendOtp(Request $request)
    {
        $identifier = strtolower(trim((string) $request->input('email', $request->input('identifier', $request->input('username', $request->input('phone', ''))))));
        $purpose = $request->input('purpose', 'verification');
        $ip = $request->ip() ?: 'unknown';

        // 1. Enforce OTP Cooldown by identifier
        $cooldown = $identifier !== '' ? Otp::getCooldownRemaining($identifier, $purpose) : 0;

        if ($cooldown > 0) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'error' => 'OTP_RATE_LIMITED',
                'message' => "Please wait {$cooldown} seconds before requesting another code.",
                'cooldown' => $cooldown,
                'retryAfter' => $cooldown,
                'retry_after' => $cooldown,
            ], 429);
        }

        // 2. Validate presence of identifier
        if (empty($identifier)) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'The email or identifier field is required.',
                'errors' => ['email' => ['The email/identifier field is required.']],
            ], 422);
        }

        // 3. If identifier is an email, enforce Gmail format validation
        if (str_contains($identifier, '@') && !\App\Services\OtpService::isValidGmailFormat($identifier)) {
            return response()->json([
                'status'   => 'error',
                'success'  => false,
                'category' => 'invalid',
                'error'    => 'EMAIL_INVALID',
                'message'  => 'Please enter a valid Gmail address (e.g., username@gmail.com).',
                'errors'   => ['email' => ['Please enter a valid Gmail address (e.g., username@gmail.com).']],
            ], 422);
        }

        // 4. For registration purpose, prevent sending OTP to already-registered email
        if ($purpose === 'register' && User::where('email', $identifier)->exists()) {
            return response()->json([
                'status'   => 'error',
                'success'  => false,
                'category' => 'already_registered',
                'error'    => 'EMAIL_ALREADY_REGISTERED',
                'message'  => 'This Gmail address is already registered. Please sign in or use another email.',
                'errors'   => ['email' => ['This Gmail address is already registered. Please sign in or use another email.']],
            ], 422);
        }

        // 5. Set cooldown timer immediately for this account/identifier
        Otp::setCooldown($identifier, $purpose);

        // 6. Look up user if exists
        $user = User::findByIdentifier($identifier);

        // 7. For forgot password / reset, only send when account exists; avoid disclosing existence
        if (in_array($purpose, ['forgot_password', 'reset'], true) && !$user) {
            Otp::setCooldown($ip, $purpose);
            return response()->json([
                'status'           => 'success',
                'success'          => true,
                'message'          => 'If the account is registered, an OTP has been sent.',
                'cooldown_seconds' => Otp::COOLDOWN_SECONDS,
            ]);
        }

        if ($user) {
            $otp = Otp::generateForEmail($user->email, $purpose, $user->id);

            try {
                $delivery = app(EmailDeliveryService::class)->sendOtp($user->email, $otp->code, $purpose, $user->name);
                if (!$delivery->success) {
                    $otp->update(['used' => true]);
                    Log::error("API OTP Mail rejected for [{$user->email}]: " . $delivery->error);
                    if (!in_array($purpose, ['forgot_password', 'reset'], true)) {
                        return response()->json([
                            'status'   => 'error',
                            'success'  => false,
                            'category' => 'unavailable',
                            'error'    => 'EMAIL_UNAVAILABLE',
                            'message'  => 'Unable to verify this email address. The verification code could not be delivered. Please check that the mailbox exists and can receive email.',
                        ], 422);
                    }
                }
            } catch (\Exception $e) {
                Log::error("API OTP Mail failed: " . $e->getMessage());
                if (!in_array($purpose, ['forgot_password', 'reset'], true)) {
                    return response()->json([
                        'status'   => 'error',
                        'success'  => false,
                        'category' => 'unavailable',
                        'error'    => 'EMAIL_UNAVAILABLE',
                        'message'  => 'Unable to verify this email address. The verification code could not be delivered. Please check that the mailbox exists and can receive email.',
                    ], 422);
                }
            }
        } else {
            // Unregistered user / guest OTP
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $hash = hash('sha256', $code);
            $cacheKey = 'guest_otp:' . sha1($identifier . ':' . $purpose);
            Cache::put($cacheKey, $hash, now()->addMinutes(10));

            try {
                if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                    $delivery = app(EmailDeliveryService::class)->sendOtp($identifier, $code, $purpose, 'User');
                    if (!$delivery->success) {
                        Cache::forget($cacheKey);
                        return response()->json([
                            'status'   => 'error',
                            'success'  => false,
                            'category' => 'unavailable',
                            'error'    => 'EMAIL_UNAVAILABLE',
                            'message'  => 'Unable to verify this email address. The verification code could not be delivered. Please check that the mailbox exists and can receive email.',
                        ], 422);
                    }
                }
            } catch (\Exception $e) {
                Cache::forget($cacheKey);
                Log::error("API Guest OTP Mail failed: " . $e->getMessage());
                return response()->json([
                    'status'   => 'error',
                    'success'  => false,
                    'category' => 'unavailable',
                    'error'    => 'EMAIL_UNAVAILABLE',
                    'message'  => 'Unable to verify this email address. The verification code could not be delivered. Please check that the mailbox exists and can receive email.',
                ], 422);
            }
        }

        return response()->json([
            'status'           => 'success',
            'success'          => true,
            'message'          => 'If the account is registered, an OTP has been sent.',
            'cooldown_seconds' => Otp::COOLDOWN_SECONDS,
        ]);
    }

    /**
     * Verify an OTP (/api/otp/verify).
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email'   => 'required',
            'otp'     => 'required|digits:6',
            'purpose' => 'nullable|string',
        ]);

        $identifier = strtolower(trim((string) $request->input('email')));
        $purpose    = $request->input('purpose', 'verification');
        $code       = trim((string) $request->input('otp'));

        // Resolve user by email, student_number, or employee_id
        $user   = User::findByIdentifier($identifier);
        $userId = $user?->id ?? $identifier;

        $otpRecord = null;
        if ($user) {
            $otpRecord = Otp::where('purpose', $purpose)
                ->where('code', $code)
                ->where('used', false)
                ->where(function ($query) use ($user, $identifier) {
                    $query->where('user_id', $user->id)
                          ->orWhere('email', $identifier)
                          ->orWhere('email', $user->email);
                })
                ->where('expires_at', '>', now())
                ->latest()
                ->first();
        } else {
            $cacheKey   = 'guest_otp:' . sha1($identifier . ':' . $purpose);
            $hashedCode = Cache::get($cacheKey);
            if ($hashedCode && hash_equals($hashedCode, hash('sha256', $code))) {
                $otpRecord = (object) ['is_guest' => true, 'cacheKey' => $cacheKey];
            }
        }

        if (!$otpRecord) {
            $fails = Otp::recordFailedVerify($userId, $purpose);
            if ($fails >= Otp::MAX_VERIFY_ATTEMPTS) {
                if ($user) {
                    Otp::where('user_id', $user->id)
                        ->where('purpose', $purpose)
                        ->update(['used' => true]);
                } else {
                    Cache::forget('guest_otp:' . sha1($identifier . ':' . $purpose));
                }

                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Too many failed verification attempts. This OTP has been invalidated. Please request a new one.',
                ], 422);
            }

            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Invalid or expired OTP. (' . (Otp::MAX_VERIFY_ATTEMPTS - $fails) . ' attempts remaining)',
                'remaining_attempts' => max(0, Otp::MAX_VERIFY_ATTEMPTS - $fails),
            ], 422);
        }

        Otp::clearFailedVerify($userId, $purpose);
        if (isset($otpRecord->is_guest)) {
            Cache::forget($otpRecord->cacheKey);
        } else {
            $otpRecord->update(['used' => true]);
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'OTP verified successfully.',
        ]);
    }

    /**
     * Password reset request with strict Account ID + Email verification (/api/reset, /api/forgot-password).
     */
    public function requestPasswordReset(Request $request)
    {
        $rawAccountId = trim((string) $request->input('account_id', $request->input('student_id', '')));
        $rawEmail     = trim((string) $request->input('email', $request->input('gmail', $request->input('identifier', ''))));
        $ip           = $request->ip() ?: 'unknown';

        // For forgot-password routes, both account_id and email are strictly required
        $isForgotPasswordEndpoint = $request->is('api/forgot-password') || $request->is('forgot-password');

        if ($isForgotPasswordEndpoint && (empty($rawAccountId) || empty($rawEmail))) {
            return response()->json([
                'status'  => 'error',
                'success' => false,
                'message' => 'Both your Account ID / Student ID and registered email address are required.',
                'errors'  => [
                    'account_id' => empty($rawAccountId) ? ['The Account ID / Student ID field is required.'] : [],
                    'email'      => empty($rawEmail) ? ['The email field is required.'] : [],
                ],
            ], 422);
        }

        if (empty($rawAccountId) && empty($rawEmail)) {
            return response()->json([
                'status'  => 'error',
                'success' => false,
                'message' => 'The email or account identifier field is required.',
            ], 422);
        }

        $cleanEmail = strtolower($rawEmail);

        if ($cleanEmail !== '' && str_contains($cleanEmail, '@') && !\App\Services\OtpService::isValidGmailFormat($cleanEmail)) {
            return response()->json([
                'status'   => 'error',
                'success'  => false,
                'category' => 'invalid',
                'error'    => 'EMAIL_INVALID',
                'message'  => 'Please enter a valid Gmail address (e.g., username@gmail.com).',
                'errors'   => ['email' => ['Please enter a valid Gmail address (e.g., username@gmail.com).']],
            ], 422);
        }

        // 2. Enforce cooldown per email/identifier/IP
        $cooldown = max(
            $cleanEmail !== '' ? Otp::getCooldownRemaining($cleanEmail, 'forgot_password') : 0,
            $rawAccountId !== '' ? Otp::getCooldownRemaining($rawAccountId, 'forgot_password') : 0
        );

        if ($cooldown > 0) {
            return response()->json([
                'status'      => 'error',
                'success'     => false,
                'error'       => 'OTP_RATE_LIMITED',
                'message'     => "Please wait {$cooldown} seconds before requesting another password reset.",
                'cooldown'    => $cooldown,
                'retryAfter'  => $cooldown,
                'retry_after' => $cooldown,
            ], 429);
        }

        // 3. Look up account
        if (!empty($rawAccountId)) {
            $user = User::findByAccountId($rawAccountId);
            if (!$user) {
                if ($cleanEmail !== '') Otp::setCooldown($cleanEmail, 'forgot_password');
                Otp::setCooldown($rawAccountId, 'forgot_password');
                Otp::setCooldown($ip, 'forgot_password');

                return response()->json([
                    'status'  => 'error',
                    'success' => false,
                    'error'   => 'ACCOUNT_NOT_FOUND',
                    'message' => 'Unable to verify the account. Please check your Student ID and email address.',
                ], 422);
            }

            // 4. Retrieve registered email and compare against entered email
            $registeredEmail = strtolower(trim((string) $user->email));

            if ($cleanEmail !== '' && $cleanEmail !== $registeredEmail) {
                Otp::setCooldown($cleanEmail, 'forgot_password');
                Otp::setCooldown($ip, 'forgot_password');

                return response()->json([
                    'status'  => 'error',
                    'success' => false,
                    'error'   => 'EMAIL_MISMATCH',
                    'message' => 'The email address you entered is not the email registered to this account.',
                ], 422);
            }
        } else {
            // Legacy /api/reset lookup by email/identifier
            $user = User::findByIdentifier($cleanEmail);
            $registeredEmail = $user ? strtolower(trim((string) $user->email)) : $cleanEmail;
        }

        // Set cooldown timers
        if (!empty($cleanEmail)) Otp::setCooldown($cleanEmail, 'forgot_password');
        if (!empty($rawAccountId)) Otp::setCooldown($rawAccountId, 'forgot_password');
        Otp::setCooldown($ip, 'forgot_password');

        if ($user) {
            $otp = Otp::generateForEmail($registeredEmail, 'forgot_password', $user->id);
            $maskedEmail = \App\Services\OtpService::maskEmail($registeredEmail);

            try {
                $delivery = app(EmailDeliveryService::class)->sendOtp($registeredEmail, $otp->code, 'forgot_password', $user->name);
                if (!$delivery->success) {
                    $otp->update(['used' => true]);
                    Log::error("API Password Reset Mail rejected by provider for [{$registeredEmail}]: " . $delivery->error);
                }
            } catch (\Exception $e) {
                Log::error("API Password Reset Mail failed: " . $e->getMessage());
            }
        } else {
            $maskedEmail = \App\Services\OtpService::maskEmail($cleanEmail);
        }

        return response()->json([
            'status'           => 'success',
            'success'          => true,
            'message'          => "If your account is registered, a password reset code has been sent.",
            'masked_email'     => $maskedEmail ?? null,
            'cooldown_seconds' => Otp::COOLDOWN_SECONDS,
        ]);
    }

    /**
     * Complete password reset (/api/reset-password).
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required_without:identifier|nullable',
            'identifier'            => 'required_without:email|nullable',
            'account_id'            => 'nullable|string',
            'otp'                   => 'required|digits:6',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $rawIdentifier = trim((string) $request->input('identifier', $request->input('email', $request->input('account_id', ''))));
        $cleanEmail    = strtolower(trim((string) $request->input('email', '')));
        $rawAccountId  = trim((string) $request->input('account_id', $request->input('student_id', '')));

        if (!empty($rawAccountId) && !empty($cleanEmail)) {
            $user = User::findByAccountId($rawAccountId);
            if (!$user || strtolower(trim((string) $user->email)) !== $cleanEmail) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Account verification failed. Account ID and email do not match.',
                ], 422);
            }
        } elseif (!empty($rawAccountId)) {
            $user = User::findByAccountId($rawAccountId);
        } elseif (!empty($cleanEmail)) {
            $user = User::where('email', $cleanEmail)->first();
        } else {
            $user = User::findByIdentifier($rawIdentifier);
        }

        if (!$user) {
            Otp::recordFailedVerify($rawIdentifier ?: 'unknown', 'forgot_password');
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid or expired reset code.',
            ], 422);
        }

        $otpRecord = Otp::where('purpose', 'forgot_password')
            ->where('code', $request->otp)
            ->where('used', false)
            ->where(function ($query) use ($user, $cleanEmail, $rawIdentifier) {
                $query->where('user_id', $user->id)
                      ->orWhere('email', $user->email);
                if (!empty($cleanEmail)) {
                    $query->orWhere('email', $cleanEmail);
                }
                if (!empty($rawIdentifier) && str_contains($rawIdentifier, '@')) {
                    $query->orWhere('email', strtolower($rawIdentifier));
                }
            })
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpRecord) {
            $fails = Otp::recordFailedVerify($user->id, 'forgot_password');
            if ($fails >= Otp::MAX_VERIFY_ATTEMPTS) {
                Otp::invalidatePrevious($user->email, 'forgot_password');
                Otp::invalidatePrevious($user->id, 'forgot_password');

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Too many failed attempts. Reset code invalidated.',
                ], 422);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid or expired reset code.',
            ], 422);
        }

        Otp::clearFailedVerify($user->id, 'forgot_password');
        $otpRecord->update(['used' => true]);
        $user->update(['password' => Hash::make($request->password)]);

        // Invalidate any remaining forgot_password OTPs for this user
        Otp::invalidatePrevious($user->email, 'forgot_password');
        Otp::invalidatePrevious($user->id, 'forgot_password');

        return response()->json([
            'status'  => 'success',
            'message' => 'Password reset successfully.',
        ]);
    }

    /**
     * Email verification request (/api/email/verify, /api/email/resend).
     */
    public function sendEmailVerification(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', $request->user()?->email ?? '')));
        $ip = $request->ip() ?: 'unknown';

        // Enforce cooldown per email
        $cooldown = $email !== '' ? Otp::getCooldownRemaining($email, 'email_verify') : 0;

        if ($cooldown > 0) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'error' => 'OTP_RATE_LIMITED',
                'message' => "Please wait {$cooldown} seconds before requesting another verification email.",
                'cooldown' => $cooldown,
                'retryAfter' => $cooldown,
                'retry_after' => $cooldown,
            ], 429);
        }

        if (empty($email)) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'The email field is required.',
                'errors' => ['email' => ['The email field is required.']],
            ], 422);
        }

        // Validate valid Gmail format
        if (!\App\Services\OtpService::isValidGmailFormat($email)) {
            return response()->json([
                'status'   => 'error',
                'success'  => false,
                'category' => 'invalid',
                'error'    => 'EMAIL_INVALID',
                'message'  => 'Please enter a valid Gmail address (e.g., username@gmail.com).',
                'errors'   => ['email' => ['Please enter a valid Gmail address (e.g., username@gmail.com).']],
            ], 422);
        }

        // Check if already registered by someone else
        $authUser = $request->user();
        $existing = User::where('email', $email)->first();
        if ($existing && (!$authUser || $existing->id !== $authUser->id)) {
            return response()->json([
                'status'   => 'error',
                'success'  => false,
                'category' => 'already_registered',
                'error'    => 'EMAIL_ALREADY_REGISTERED',
                'message'  => 'This Gmail address is already registered. Please sign in or use another email.',
                'errors'   => ['email' => ['This Gmail address is already registered. Please sign in or use another email.']],
            ], 422);
        }

        // Set cooldown timer for this email
        Otp::setCooldown($email, 'email_verify');

        $user = User::where('email', $email)->first();

        if ($user) {
            $otp = Otp::generate($user->id, 'email_verify');
            $code = $otp->code;
        } else {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $hash = hash('sha256', $code);
            Cache::put('guest_otp:' . sha1($email . ':email_verify'), $hash, now()->addMinutes(10));
        }

        try {
            $delivery = app(EmailDeliveryService::class)->sendOtp($email, $code, 'email_verify', $user ? $user->name : 'User');
            if (!$delivery->success) {
                if (!$user) {
                    Cache::forget('guest_otp:' . sha1($email . ':email_verify'));
                }
                return response()->json([
                    'status'   => 'error',
                    'success'  => false,
                    'category' => 'unavailable',
                    'error'    => 'EMAIL_UNAVAILABLE',
                    'message'  => 'Unable to verify this email address. The verification code could not be delivered. Please check that the mailbox exists and can receive email.',
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error("Email verify mail failed: " . $e->getMessage());
            if (!$user) {
                Cache::forget('guest_otp:' . sha1($email . ':email_verify'));
            }
            return response()->json([
                'status'   => 'error',
                'success'  => false,
                'category' => 'unavailable',
                'error'    => 'EMAIL_UNAVAILABLE',
                'message'  => 'Unable to verify this email address. The verification code could not be delivered. Please check that the mailbox exists and can receive email.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Verification email sent successfully.',
            'cooldown_seconds' => Otp::COOLDOWN_SECONDS,
        ]);
    }
}
