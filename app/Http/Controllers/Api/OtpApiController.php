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

        // 1. Enforce OTP Cooldown (60 seconds) by identifier and by IP
        $cooldown = max(
            $identifier !== '' ? Otp::getCooldownRemaining($identifier, $purpose) : 0,
            Otp::getCooldownRemaining($ip, $purpose)
        );

        if ($cooldown > 0) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => "Please wait {$cooldown} seconds before requesting another OTP.",
                'cooldown' => $cooldown,
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

        // 3. Set cooldown timer immediately
        Otp::setCooldown($identifier, $purpose);
        Otp::setCooldown($ip, $purpose);

        // 4. Look up user if exists, or generate guest/registration OTP
        $user = User::where('email', $identifier)
            ->orWhere('student_number', $identifier)
            ->orWhere('employee_id', $identifier)
            ->first();

        if ($user) {
            $otp = Otp::generate($user->id, $purpose);

            try {
                Mail::to($user->email)->send(new OtpMail($otp->code, $purpose, $user->name));
            } catch (\Exception $e) {
                Log::error("API OTP Mail failed: " . $e->getMessage());
            }
        } else {
            // Unregistered user / guest OTP
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            try {
                if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                    Mail::to($identifier)->send(new OtpMail($code, $purpose, 'User'));
                }
            } catch (\Exception $e) {
                Log::error("API Guest OTP Mail failed: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'OTP sent successfully to ' . $identifier,
            'cooldown_seconds' => Otp::COOLDOWN_SECONDS,
        ]);
    }

    /**
     * Verify an OTP (/api/otp/verify).
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'otp' => 'required|digits:6',
            'purpose' => 'nullable|string',
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $purpose = $request->input('purpose', 'verification');
        $code = trim((string) $request->input('otp'));

        $user = User::where('email', $email)->first();
        $userId = $user?->id ?? $email;

        $otpRecord = $user ? Otp::where('user_id', $user->id)
            ->where('code', $code)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first() : null;

        if (!$otpRecord) {
            $fails = Otp::recordFailedVerify($userId, $purpose);
            if ($fails >= Otp::MAX_VERIFY_ATTEMPTS) {
                if ($user) {
                    Otp::where('user_id', $user->id)
                        ->where('purpose', $purpose)
                        ->update(['used' => true]);
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
        $otpRecord->update(['used' => true]);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'OTP verified successfully.',
        ]);
    }

    /**
     * Password reset request with flood protection (/api/reset, /api/forgot-password).
     */
    public function requestPasswordReset(Request $request)
    {
        $identifier = strtolower(trim((string) $request->input('email', $request->input('identifier', $request->input('username', '')))));
        $ip = $request->ip() ?: 'unknown';

        // 1. Enforce 60s cooldown per email/identifier and per IP
        $cooldown = max(
            $identifier !== '' ? Otp::getCooldownRemaining($identifier, 'forgot_password') : 0,
            Otp::getCooldownRemaining($ip, 'forgot_password')
        );

        if ($cooldown > 0) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => "Please wait {$cooldown} seconds before requesting another password reset.",
                'cooldown' => $cooldown,
                'retry_after' => $cooldown,
            ], 429);
        }

        // 2. Validate identifier presence
        if (empty($identifier)) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'The email or identifier field is required.',
                'errors' => ['email' => ['The email/identifier field is required.']],
            ], 422);
        }

        Otp::setCooldown($identifier, 'forgot_password');
        Otp::setCooldown($ip, 'forgot_password');

        $user = User::where('email', $identifier)
            ->orWhere('student_number', $identifier)
            ->orWhere('employee_id', $identifier)
            ->first();

        if ($user) {
            $otp = Otp::generate($user->id, 'forgot_password');

            try {
                Mail::to($user->email)->send(new OtpMail($otp->code, 'forgot_password', $user->name));
            } catch (\Exception $e) {
                Log::error("API Password Reset Mail failed: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'If your account is registered, a password reset code has been sent.',
            'cooldown_seconds' => Otp::COOLDOWN_SECONDS,
        ]);
    }

    /**
     * Complete password reset (/api/reset-password).
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'otp' => 'required|digits:6',
            'password' => 'required|min:8|confirmed',
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or reset code.',
            ], 422);
        }

        $otpRecord = Otp::where('user_id', $user->id)
            ->where('code', $request->otp)
            ->where('purpose', 'forgot_password')
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpRecord) {
            $fails = Otp::recordFailedVerify($user->id, 'forgot_password');
            if ($fails >= Otp::MAX_VERIFY_ATTEMPTS) {
                Otp::where('user_id', $user->id)
                    ->where('purpose', 'forgot_password')
                    ->update(['used' => true]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many failed attempts. Reset code invalidated.',
                ], 422);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired reset code.',
            ], 422);
        }

        Otp::clearFailedVerify($user->id, 'forgot_password');
        $otpRecord->update(['used' => true]);
        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'status' => 'success',
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

        // Enforce 60-second cooldown
        $cooldown = max(
            $email !== '' ? Otp::getCooldownRemaining($email, 'email_verify') : 0,
            Otp::getCooldownRemaining($ip, 'email_verify')
        );

        if ($cooldown > 0) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => "Please wait {$cooldown} seconds before requesting another verification email.",
                'cooldown' => $cooldown,
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

        Otp::setCooldown($email, 'email_verify');
        Otp::setCooldown($ip, 'email_verify');

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        try {
            Mail::to($email)->send(new OtpMail($code, 'register', 'User'));
        } catch (\Exception $e) {
            Log::error("Email verify mail failed: " . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Verification email sent successfully.',
            'cooldown_seconds' => Otp::COOLDOWN_SECONDS,
        ]);
    }
}
