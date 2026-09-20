<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Resolve the role-based profile redirect for the authenticated user.
     */
    private function roleRedirect(): string
    {
        $user = Auth::user();
        if ($user->isAdmin())   return 'admin.profile';
        if ($user->isTeacher()) return 'teacher.profile';
        if ($user->isParent())  return 'parent.profile';
        return 'settings'; // student
    }

    // ─────────────────────────────────────────
    // REGISTRATION — Send OTP to verify email
    // ─────────────────────────────────────────
    public function sendRegisterOtp(Request $request)
    {
        $rawEmail = trim((string) $request->input('email', ''));
        $emailClean = strtolower($rawEmail);
        $scope = $request->input('scope', 'register');
        $sessionPrefix = $scope === 'admin_student' ? 'admin_reg' : 'reg';
        $requestId = $request->header('X-Request-Id') ?: $request->input('request_id');

        if ($emailClean === '') {
            return response()->json([
                'success'  => false,
                'status'   => 'error',
                'category' => 'invalid',
                'error'    => 'EMAIL_INVALID',
                'message'  => 'Please enter your Gmail address.',
            ], 422);
        }

        // 1. Check valid Gmail format (invalid category)
        if (!OtpService::isValidGmailFormat($emailClean)) {
            return response()->json([
                'success'  => false,
                'status'   => 'error',
                'category' => 'invalid',
                'error'    => 'EMAIL_INVALID',
                'message'  => 'Please enter a valid Gmail address (e.g., username@gmail.com).',
            ], 422);
        }

        // 2. Check already-registered (already_registered category)
        if (User::where('email', $emailClean)->exists()) {
            return response()->json([
                'success'  => false,
                'status'   => 'error',
                'category' => 'already_registered',
                'error'    => 'EMAIL_ALREADY_REGISTERED',
                'message'  => 'This Gmail address is already registered. Please sign in or use another email.',
            ], 422);
        }

        // 3. Rate-limit & Send OTP (unavailable category if sending fails)
        try {
            $result = $this->otpService->sendOtp($emailClean, 'register', null, null, $requestId);
            session([
                "{$sessionPrefix}_otp_email" => $emailClean,
            ]);

            return response()->json([
                'success'    => true,
                'status'     => 'success',
                'category'   => 'sent',
                'message'    => 'Verification code sent to ' . $emailClean,
                'cooldown'   => $result['cooldown'] ?? Otp::COOLDOWN_SECONDS,
                'retryAfter' => $result['cooldown'] ?? Otp::COOLDOWN_SECONDS,
                'retry_after'=> $result['cooldown'] ?? Otp::COOLDOWN_SECONDS,
                'request_id' => $result['request_id'] ?? $requestId,
            ]);
        } catch (\Exception $e) {
            $status = $e->getCode() === 429 ? 429 : 422;
            $cooldown = $status === 429 ? Otp::getCooldownRemaining($emailClean, 'register') : 0;
            if ($cooldown <= 0 && $status === 429) {
                $cooldown = Otp::COOLDOWN_SECONDS;
            }

            $isRateLimited = ($status === 429);
            $category = $isRateLimited ? 'rate_limited' : 'unavailable';
            $errorType = $isRateLimited ? 'OTP_RATE_LIMITED' : 'EMAIL_UNAVAILABLE';
            $message = $isRateLimited
                ? "Please wait {$cooldown} seconds before requesting another code."
                : 'Unable to verify this email address. The verification code could not be delivered to this Gmail address. Please check that the mailbox exists and can receive email.';

            return response()->json([
                'success'    => false,
                'status'     => 'error',
                'category'   => $category,
                'error'      => $errorType,
                'message'    => $message,
                'cooldown'   => $cooldown,
                'retryAfter' => $cooldown,
                'retry_after'=> $cooldown,
            ], $status);
        }
    }

    public function verifyRegisterOtp(Request $request)
    {
        $rawEmail = trim((string) $request->input('email', ''));
        $emailClean = strtolower($rawEmail);
        $otpClean   = trim((string) $request->input('otp', ''));

        if ($emailClean === '' || !OtpService::isValidGmailFormat($emailClean)) {
            return response()->json([
                'success'  => false,
                'status'   => 'error',
                'category' => 'invalid',
                'message'  => 'Please enter a valid Gmail address (e.g., username@gmail.com).',
            ], 422);
        }

        if (strlen($otpClean) !== 6 || !ctype_digit($otpClean)) {
            return response()->json([
                'success'  => false,
                'status'   => 'error',
                'category' => 'unverified',
                'message'  => 'The verification code must be exactly 6 digits.',
            ], 422);
        }

        $scope = $request->input('scope', 'register');
        $sessionPrefix = $scope === 'admin_student' ? 'admin_reg' : 'reg';

        $result = $this->otpService->verifyOtp($emailClean, $otpClean, 'register');

        if (!$result['success']) {
            return response()->json([
                'success'  => false,
                'status'   => $result['status'],
                'category' => 'unverified',
                'message'  => $result['message'],
            ], 422);
        }

        // Store confirmed verified email in session for registration submission
        session(["{$sessionPrefix}_email_verified" => $emailClean]);

        return response()->json([
            'success'  => true,
            'status'   => 'success',
            'category' => 'verified',
            'message'  => 'Email verified successfully.',
        ]);
    }

    // ─────────────────────────────────────────
    // FORGOT PASSWORD — Step 1: Verify Account (Student/Account ID + Email)
    // ─────────────────────────────────────────
    // ─────────────────────────────────────────
    // FORGOT PASSWORD — Step 1: Verify Account (Student ID / Email)
    // ─────────────────────────────────────────
    public function forgotForm(Request $request)
    {
        $accountUser = null;
        $maskedEmail = null;
        $accountIdentifierType = null;
        $accountIdentifierValue = null;
        $errorMessage = null;

        $rawIdentifier = trim((string) $request->input('identifier', $request->input('account_id', '')));

        if ($rawIdentifier !== '') {
            $user = User::findByRecoveryIdentifier($rawIdentifier);
            $registeredEmail = strtolower(trim((string) ($user ? $user->email : '')));
            $hasValidEmail = ($registeredEmail !== '' && filter_var($registeredEmail, FILTER_VALIDATE_EMAIL));

            if ($user && $user->isActive() && $hasValidEmail) {
                $accountUser = $user;
                $maskedEmail = OtpService::maskEmail($registeredEmail);
                if (str_contains($rawIdentifier, '@')) {
                    $accountIdentifierType = 'Email';
                } elseif (!empty($user->employee_id) && ($rawIdentifier === $user->employee_id || $user->isTeacher() || $user->isAdmin() || empty($user->student_number))) {
                    $accountIdentifierType = 'Employee ID';
                } else {
                    $accountIdentifierType = 'Student ID';
                }
                $accountIdentifierValue = $rawIdentifier;
            } else {
                $errorMessage = 'Unable to verify the account. Please check your Student ID or email address.';
            }
        }

        return view('auth.forgot-password', compact(
            'accountUser',
            'maskedEmail',
            'accountIdentifierType',
            'accountIdentifierValue',
            'errorMessage'
        ));
    }

    public function sendForgotOtp(Request $request)
    {
        $requestId = $request->header('X-Request-Id') ?: $request->input('request_id');
        $ip        = $request->ip() ?: 'unknown';

        // 1. Handle in-session OTP resend from the verification screen
        $isResend = $request->boolean('is_resend') || 
            ($request->input('purpose') === 'forgot_password' && 
             empty($request->input('account_id')) && 
             empty($request->input('student_id')) && 
             empty($request->input('identifier')) &&
             session('otp_verified_account'));

        if ($isResend && session('otp_verified_account') && session('otp_user_id')) {
            $user = User::find(session('otp_user_id'));
            if ($user) {
                $registeredEmail = strtolower(trim((string) $user->email));
                $cooldown = Otp::getCooldownRemaining($registeredEmail, 'forgot_password');
                if ($cooldown > 0) {
                    return response()->json([
                        'success'    => false,
                        'status'     => 'error',
                        'error'      => 'OTP_RATE_LIMITED',
                        'message'    => "Please wait {$cooldown} seconds before requesting another code.",
                        'cooldown'   => $cooldown,
                        'retryAfter' => $cooldown,
                        'retry_after'=> $cooldown,
                    ], 429);
                }

                try {
                    $this->otpService->sendOtp($registeredEmail, 'forgot_password', $user->id, $user->name, $requestId);
                } catch (\Exception $e) {
                    Log::error("Failed to resend forgot password OTP for user [ID: {$user->id}]: " . $e->getMessage());
                    $status = $e->getCode() === 429 ? 429 : 500;
                    $cooldownRem = $status === 429 ? Otp::getCooldownRemaining($registeredEmail, 'forgot_password') : 0;
                    return response()->json([
                        'success'    => false,
                        'status'     => 'error',
                        'error'      => $status === 429 ? 'OTP_RATE_LIMITED' : 'OTP_SEND_FAILED',
                        'message'    => $status === 429 
                            ? "Please wait {$cooldownRem} seconds before requesting another code." 
                            : 'Unable to send the verification code right now. Please try again later.',
                        'cooldown'   => $cooldownRem,
                        'retryAfter' => $cooldownRem,
                    ], $status);
                }

                return response()->json([
                    'success'      => true,
                    'status'       => 'success',
                    'message'      => 'A new verification code has been sent.',
                    'masked_email' => session('otp_masked_email'),
                    'cooldown'     => Otp::COOLDOWN_SECONDS,
                    'retryAfter'   => Otp::COOLDOWN_SECONDS,
                ]);
            }
        }

        $rawAccountId  = trim((string) $request->input('account_id', $request->input('student_id', '')));
        $rawEmail      = trim((string) $request->input('email', $request->input('gmail', '')));
        $rawIdentifier = trim((string) $request->input('identifier', ''));

        // Determine if dual-verification mode (both Account ID and Email submitted, and no single identifier)
        $isDualVerification = ($rawAccountId !== '' && $rawEmail !== '' && $rawIdentifier === '');

        if ($isDualVerification) {
            // ─────────────────────────────────────────
            // DUAL VERIFICATION MODE (Account ID + Email Match)
            // ─────────────────────────────────────────
            $cleanEnteredEmail = strtolower($rawEmail);

            $cooldown = max(
                Otp::getCooldownRemaining($cleanEnteredEmail, 'forgot_password'),
                Otp::getCooldownRemaining($rawAccountId, 'forgot_password')
            );

            if ($cooldown > 0) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success'    => false,
                        'status'     => 'error',
                        'error'      => 'OTP_RATE_LIMITED',
                        'message'    => "Please wait {$cooldown} seconds before requesting another code.",
                        'cooldown'   => $cooldown,
                        'retryAfter' => $cooldown,
                        'retry_after'=> $cooldown,
                    ], 429);
                }
                return back()->withInput()->withErrors([
                    'email' => "Please wait {$cooldown} seconds before requesting another code."
                ]);
            }

            $user = User::findByAccountId($rawAccountId);

            if (!$user) {
                Otp::setCooldown($cleanEnteredEmail, 'forgot_password');
                Otp::setCooldown($rawAccountId, 'forgot_password');
                Otp::setCooldown($ip, 'forgot_password');
                Log::warning("Forgot password verification failed: Account ID [{$rawAccountId}] not found.");

                $safeError = 'Unable to verify the account. Please check your Student ID and email address.';

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'status'  => 'error',
                        'error'   => 'ACCOUNT_NOT_FOUND',
                        'message' => $safeError,
                    ], 422);
                }

                return back()->withInput()->withErrors([
                    'account_id' => $safeError,
                ]);
            }

            $registeredEmail = strtolower(trim((string) $user->email));

            if ($cleanEnteredEmail !== $registeredEmail) {
                Otp::setCooldown($cleanEnteredEmail, 'forgot_password');
                Otp::setCooldown($ip, 'forgot_password');
                Log::warning("Forgot password verification failed: Email mismatch for account [ID: {$user->id}, AccountID: {$rawAccountId}].");

                $mismatchError = 'The email address you entered is not the email registered to this account.';

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'status'  => 'error',
                        'error'   => 'EMAIL_MISMATCH',
                        'message' => $mismatchError,
                    ], 422);
                }

                return back()->withInput()
                             ->with('account_verification_failed', true)
                             ->withErrors(['email' => $mismatchError]);
            }
        } else {
            // ─────────────────────────────────────────
            // INTELLIGENT RECOVERY MODE (Reusing Login Identifier)
            // ─────────────────────────────────────────
            $effectiveIdentifier = $rawIdentifier !== '' ? $rawIdentifier : ($rawAccountId !== '' ? $rawAccountId : $rawEmail);

            if ($effectiveIdentifier === '') {
                $request->validate([
                    'identifier' => 'required|string|max:255',
                ], [
                    'identifier.required' => 'Please enter your Student ID or registered email address.',
                ]);
            }

            $cooldown = Otp::getCooldownRemaining($effectiveIdentifier, 'forgot_password');
            if ($cooldown > 0) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success'    => false,
                        'status'     => 'error',
                        'error'      => 'OTP_RATE_LIMITED',
                        'message'    => "Please wait {$cooldown} seconds before requesting another code.",
                        'cooldown'   => $cooldown,
                        'retryAfter' => $cooldown,
                        'retry_after'=> $cooldown,
                    ], 429);
                }
                return back()->withInput()->withErrors([
                    'identifier' => "Please wait {$cooldown} seconds before requesting another code."
                ]);
            }

            $user = User::findByRecoveryIdentifier($effectiveIdentifier);

            if (!$user) {
                Otp::setCooldown($effectiveIdentifier, 'forgot_password');
                Otp::setCooldown($ip, 'forgot_password');
                Log::warning("Forgot password lookup failed: Identifier [{$effectiveIdentifier}] not found.");

                $safeError = 'Unable to verify the account. Please check your Student ID or email address.';

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'status'  => 'error',
                        'error'   => 'ACCOUNT_NOT_FOUND',
                        'message' => $safeError,
                    ], 422);
                }

                return back()->withInput()->withErrors([
                    'identifier' => $safeError,
                ]);
            }

            if (!$user->isActive()) {
                $inactiveError = 'This account is inactive or disabled. Please contact the administrator.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'status'  => 'error',
                        'error'   => 'ACCOUNT_INACTIVE',
                        'message' => $inactiveError,
                    ], 422);
                }
                return back()->withInput()->withErrors(['identifier' => $inactiveError]);
            }

            $registeredEmail = strtolower(trim((string) $user->email));

            if ($registeredEmail === '' || !filter_var($registeredEmail, FILTER_VALIDATE_EMAIL)) {
                $noEmailError = 'No valid registered email address is found for this account. Please contact an administrator.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'status'  => 'error',
                        'error'   => 'NO_REGISTERED_EMAIL',
                        'message' => $noEmailError,
                    ], 422);
                }
                return back()->withInput()->withErrors(['identifier' => $noEmailError]);
            }

            $emailCooldown = Otp::getCooldownRemaining($registeredEmail, 'forgot_password');
            if ($emailCooldown > 0) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success'    => false,
                        'status'     => 'error',
                        'error'      => 'OTP_RATE_LIMITED',
                        'message'    => "Please wait {$emailCooldown} seconds before requesting another code.",
                        'cooldown'   => $emailCooldown,
                        'retryAfter' => $emailCooldown,
                        'retry_after'=> $emailCooldown,
                    ], 429);
                }
                return back()->withInput()->withErrors([
                    'identifier' => "Please wait {$emailCooldown} seconds before requesting another code."
                ]);
            }
        }

        // ─────────────────────────────────────────
        // Step 6: Verification Succeeded! Create secure recovery session
        // ─────────────────────────────────────────
        $maskedEmail = OtpService::maskEmail($registeredEmail);
        $flowToken   = bin2hex(random_bytes(16));

        session([
            'otp_verified_account' => true,
            'account_verified'     => true,
            'otp_user_id'          => $user->id,
            'otp_account_id'       => $user->student_number ?? $user->employee_id ?? (string) $user->id,
            'otp_email'            => $registeredEmail,
            'otp_masked_email'     => $maskedEmail,
            'otp_flow_token'       => $flowToken,
            'otp_attempts'         => 0,
            'otp_verified_at'      => now()->timestamp,
            'otp_purpose'          => 'forgot_password',
            'otp_identifier'       => $registeredEmail,
        ]);

        // ─────────────────────────────────────────
        // Step 7: Generate and send OTP strictly to the verified registered email
        // ─────────────────────────────────────────
        try {
            $this->otpService->sendOtp($registeredEmail, 'forgot_password', $user->id, $user->name, $requestId);
        } catch (\Exception $e) {
            session()->forget([
                'otp_verified_account',
                'account_verified',
                'otp_user_id',
                'otp_account_id',
                'otp_email',
                'otp_masked_email',
                'otp_flow_token',
                'otp_attempts',
                'otp_verified_at',
                'otp_purpose',
                'otp_identifier',
            ]);

            Log::error("Failed to send forgot password OTP for user [ID: {$user->id}]: " . $e->getMessage(), [
                'exception' => get_class($e),
                'code'      => $e->getCode(),
            ]);

            $errorField = $isDualVerification ? 'email' : 'identifier';

            if ($request->expectsJson() || $request->ajax()) {
                $status = $e->getCode() === 429 ? 429 : 500;
                $cooldownRem = $status === 429 ? Otp::getCooldownRemaining($registeredEmail, 'forgot_password') : 0;
                return response()->json([
                    'success'    => false,
                    'status'     => 'error',
                    'error'      => $status === 429 ? 'OTP_RATE_LIMITED' : 'OTP_SEND_FAILED',
                    'message'    => $status === 429 
                        ? "Please wait {$cooldownRem} seconds before requesting another code." 
                        : 'Unable to send the verification code right now. Please try again later.',
                    'cooldown'   => $cooldownRem,
                    'retryAfter' => $cooldownRem,
                ], $status);
            }
            return back()->withInput()->withErrors([
                $errorField => 'Unable to send the verification code right now. Please try again later.'
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'status'       => 'success',
                'message'      => "We verified your account successfully. Verification code sent to {$maskedEmail}",
                'masked_email' => $maskedEmail,
                'cooldown'     => Otp::COOLDOWN_SECONDS,
                'retryAfter'   => Otp::COOLDOWN_SECONDS,
                'retry_after'  => Otp::COOLDOWN_SECONDS,
            ]);
        }

        return redirect()->route('otp.verify.form', ['purpose' => 'forgot_password'])
                         ->with('account_verified', true)
                         ->with('success', "We verified your account successfully. Verification code sent to {$maskedEmail}");
    }

    // ─────────────────────────────────────────
    // FORGOT PASSWORD — Step 2 & 3: Enter OTP
    // ─────────────────────────────────────────
    public function verifyForm(Request $request)
    {
        $purpose = $request->get('purpose', 'forgot_password');

        // Enforce account verification before accessing OTP form for forgot_password
        if ($purpose === 'forgot_password') {
            if (!session('otp_verified_account') || !session('otp_user_id')) {
                return redirect()->route('otp.forgot.form')
                                 ->withErrors(['account_id' => 'Please verify your account before entering a verification code.']);
            }
        }

        $identifier  = session('otp_identifier') ?? $request->get('identifier', '');
        $maskedEmail = session('otp_masked_email') ?? '';
        return view('auth.otp-verify', compact('purpose', 'identifier', 'maskedEmail'));
    }

    public function verifyOtp(Request $request)
    {
        // Support fallback if otp_digits array was submitted
        if (empty($request->otp) && $request->has('otp_digits')) {
            $digitsInput = $request->input('otp_digits');
            if (is_array($digitsInput)) {
                $request->merge(['otp' => implode('', $digitsInput)]);
            } elseif (is_string($digitsInput)) {
                $request->merge(['otp' => $digitsInput]);
            }
        }

        $purpose  = $request->input('purpose', session('otp_purpose', 'forgot_password'));
        $otpClean = trim((string) $request->otp);

        $request->validate([
            'otp'     => 'required|digits:6',
            'purpose' => 'required|in:forgot_password,change_password,register',
        ], [
            'otp.required' => 'The verification code is required.',
            'otp.digits'   => 'The verification code must be exactly 6 digits.',
        ]);

        if ($purpose === 'forgot_password') {
            $userId        = session('otp_user_id');
            $verifiedEmail = session('otp_email');

            if (!session('otp_verified_account') || !$userId || !$verifiedEmail) {
                return redirect()->route('otp.forgot.form')
                                 ->withErrors(['account_id' => 'Your session has expired. Please verify your account again.']);
            }

            // Track verify attempts on session to prevent brute force
            $attempts = (int) session('otp_attempts', 0) + 1;
            session(['otp_attempts' => $attempts]);

            if ($attempts > Otp::MAX_VERIFY_ATTEMPTS) {
                Otp::invalidatePrevious($verifiedEmail, 'forgot_password');
                Otp::invalidatePrevious($userId, 'forgot_password');
                session()->forget([
                    'otp_verified_account', 'otp_user_id', 'otp_account_id',
                    'otp_email', 'otp_masked_email', 'otp_flow_token', 'otp_attempts'
                ]);

                return redirect()->route('otp.forgot.form')->withErrors([
                    'account_id' => 'Too many failed verification attempts. Please verify your account again and request a new code.'
                ]);
            }

            $result = $this->otpService->verifyOtp($verifiedEmail, $otpClean, 'forgot_password', $userId);

            if (!$result['success']) {
                $remaining = max(0, Otp::MAX_VERIFY_ATTEMPTS - $attempts);
                $msg = $result['message'] . ($remaining > 0 ? " ({$remaining} attempts remaining)" : '');

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success'            => false,
                        'status'             => $result['status'] ?? 'INVALID_OTP',
                        'message'            => $msg,
                        'remaining_attempts' => $remaining,
                    ], 422);
                }

                return back()->with('maskedEmail', session('otp_masked_email'))
                             ->withErrors(['otp' => $msg])
                             ->withInput();
            }

            // Verification successful: create secure, single-use, 15-minute reset session bound to verified user
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt  = now()->addMinutes(15)->timestamp;

            session([
                'password_reset_token'   => $resetToken,
                'password_reset_user_id' => $userId,
                'password_reset_expires' => $expiresAt,
                'otp_verified_user'      => $userId,
                'otp_purpose'            => 'forgot_password',
            ]);

            // Clean up OTP flow variables so OTP cannot be reused
            session()->forget([
                'otp_verified_account',
                'otp_flow_token',
                'otp_attempts',
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'     => true,
                    'status'      => 'success',
                    'message'     => 'OTP verified successfully.',
                    'reset_token' => $resetToken,
                ]);
            }

            return redirect()->route('otp.reset.form');
        }

        // Other purposes fallback (e.g. change_password)
        $identifierInput = $request->input('identifier', $request->input('email', session('otp_identifier', '')));
        $identifier = strtolower(trim((string) $identifierInput));
        $knownUserId = session('otp_user_id');
        $result = $this->otpService->verifyOtp($identifier, $otpClean, $purpose, $knownUserId);

        if (!$result['success']) {
            return back()->with('otp_identifier', $identifier)
                         ->withErrors(['otp' => $result['message']])
                         ->withInput();
        }

        $verifiedUserId = $result['user_id'] ?: $knownUserId;
        session([
            'otp_verified_user' => $verifiedUserId,
            'otp_purpose'       => $purpose,
        ]);

        return redirect()->route('otp.reset.form');
    }

    // ─────────────────────────────────────────
    // FORGOT PASSWORD — Step 4: Set New Password
    // ─────────────────────────────────────────
    public function resetForm()
    {
        $userId     = session('password_reset_user_id', session('otp_verified_user'));
        $resetToken = session('password_reset_token');
        $expires    = session('password_reset_expires', 0);

        if (!$userId || ($expires > 0 && now()->timestamp > $expires)) {
            session()->forget(['password_reset_token', 'password_reset_user_id', 'password_reset_expires', 'otp_verified_user']);
            return redirect()->route('otp.forgot.form')
                             ->withErrors(['account_id' => 'Password reset session has expired or is invalid. Please verify your account again.']);
        }

        return view('auth.reset-password', compact('resetToken'));
    }

    public function resetPassword(Request $request)
    {
        $userId       = session('password_reset_user_id', session('otp_verified_user'));
        $sessionToken = session('password_reset_token');
        $expires      = session('password_reset_expires', 0);

        if (!$userId || ($expires > 0 && now()->timestamp > $expires)) {
            session()->forget(['password_reset_token', 'password_reset_user_id', 'password_reset_expires', 'otp_verified_user']);
            return redirect()->route('otp.forgot.form')
                             ->withErrors(['account_id' => 'Password reset session has expired. Please verify your account again.']);
        }

        // Validate token binding if passed
        if ($request->filled('reset_token') && $sessionToken) {
            if (!hash_equals($sessionToken, (string) $request->input('reset_token'))) {
                session()->forget(['password_reset_token', 'password_reset_user_id', 'password_reset_expires', 'otp_verified_user']);
                return redirect()->route('otp.forgot.form')
                                 ->withErrors(['account_id' => 'Invalid password reset token. Please verify your account again.']);
            }
        }

        $request->validate([
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = User::find($userId);
        if ($user) {
            $user->update(['password' => Hash::make($request->password)]);

            // Invalidate any remaining unused forgot_password OTPs for this user
            Otp::invalidatePrevious($user->email, 'forgot_password');
            Otp::invalidatePrevious($user->id, 'forgot_password');
        }

        session()->forget([
            'password_reset_token',
            'password_reset_user_id',
            'password_reset_expires',
            'otp_verified_user',
            'otp_purpose',
            'otp_identifier',
            'otp_user_id',
            'otp_email',
            'otp_masked_email',
            'otp_account_id',
        ]);

        $request->session()->regenerate();

        return redirect()->route('login')->with('success', 'Password reset successfully! You can now log in.');
    }

    // ─────────────────────────────────────────
    // CHANGE EMAIL — Send OTP to ANY specified email or current email
    // ─────────────────────────────────────────
    public function sendEmailChangeOtp(Request $request)
    {
        $user = Auth::user();

        // Allow sending OTP directly to any new email address specified by the user, or current email
        $targetEmail = $user->email;
        if ($request->filled('new_email')) {
            $rawNew = strtolower(trim((string) $request->new_email));
            if (!OtpService::isValidGmailFormat($rawNew)) {
                return response()->json([
                    'success'  => false,
                    'status'   => 'error',
                    'category' => 'invalid',
                    'message'  => 'Please enter a valid Gmail address (e.g., username@gmail.com).',
                ], 422);
            }

            if (User::where('email', $rawNew)->where('id', '!=', $user->id)->exists()) {
                return response()->json([
                    'success'  => false,
                    'status'   => 'error',
                    'category' => 'already_registered',
                    'message'  => 'This Gmail address is already registered. Please sign in or use another email.',
                ], 422);
            }

            $targetEmail = $rawNew;
            session(['pending_new_email' => $targetEmail]);
        }

        try {
            $res = $this->otpService->sendOtp($targetEmail, 'change_email', $user->id, $user->name);
            return response()->json([
                'success'      => true,
                'status'       => 'success',
                'category'     => 'sent',
                'message'      => 'Verification code sent to ' . $targetEmail . '.',
                'target_email' => $targetEmail,
                'cooldown'     => $res['cooldown'],
            ]);
        } catch (\Exception $e) {
            $status = $e->getCode() === 429 ? 429 : 422;
            $cooldown = $status === 429 ? Otp::getCooldownRemaining($targetEmail, 'change_email') : 0;
            $category = $status === 429 ? 'rate_limited' : 'unavailable';
            return response()->json([
                'success'  => false,
                'status'   => 'error',
                'category' => $category,
                'message'  => $status === 429
                    ? "Please wait {$cooldown} seconds before requesting another code."
                    : 'Unable to verify this email address. The verification code could not be delivered. Please check that the mailbox exists and can receive email.',
                'cooldown' => $cooldown,
            ], $status);
        }
    }

    // ─────────────────────────────────────────
    // CHANGE EMAIL — Verify OTP + save new email
    // ─────────────────────────────────────────
    public function changeEmail(Request $request)
    {
        $newEmail = strtolower(trim((string) $request->new_email));
        $otpClean = trim((string) $request->otp);

        if (!OtpService::isValidGmailFormat($newEmail)) {
            return back()->withErrors(['new_email' => 'Please enter a valid Gmail address (e.g., username@gmail.com).'])->withInput();
        }

        if (User::where('email', $newEmail)->where('id', '!=', Auth::id())->exists()) {
            return back()->withErrors(['new_email' => 'This Gmail address is already registered. Please sign in or use another email.'])->withInput();
        }

        if (strlen($otpClean) !== 6 || !ctype_digit($otpClean)) {
            return back()->withErrors(['otp' => 'The verification code must be exactly 6 digits.'])->withInput();
        }

        $user = Auth::user();

        // Check if OTP was verified against new_email or current user email
        $result = $this->otpService->verifyOtp($newEmail, $otpClean, 'change_email', $user->id);
        if (!$result['success']) {
            $result = $this->otpService->verifyOtp($user->email, $otpClean, 'change_email', $user->id);
        }

        if (!$result['success']) {
            return back()->withErrors(['otp' => 'This email address is unverified. ' . $result['message']])->withInput();
        }

        $user->update(['email' => $newEmail]);
        session()->forget('pending_new_email');

        return redirect()->route($this->roleRedirect())->with('success', 'Email address updated successfully!');
    }

    // ─────────────────────────────────────────
    // CHANGE PASSWORD — Send OTP to email
    // ─────────────────────────────────────────
    public function sendChangeOtp(Request $request)
    {
        $user = Auth::user();

        try {
            $res = $this->otpService->sendOtp($user->email, 'change_password', $user->id, $user->name);
            return response()->json([
                'success'  => true,
                'message'  => 'Verification code sent to your registered email.',
                'cooldown' => $res['cooldown'],
            ]);
        } catch (\Exception $e) {
            $status = $e->getCode() === 429 ? 429 : 500;
            return response()->json([
                'success'  => false,
                'message'  => $e->getMessage() ?: 'Unable to send verification code. Please try again.',
                'cooldown' => $status === 429 ? Otp::getCooldownRemaining($user->id, 'change_password') : 0,
            ], $status);
        }
    }

    // ─────────────────────────────────────────
    // CHANGE PASSWORD — Verify OTP + save
    // ─────────────────────────────────────────
    public function changePassword(Request $request)
    {
        $request->validate([
            'otp'                   => 'required|digits:6',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = Auth::user();
        $otpClean = trim((string) $request->otp);

        $result = $this->otpService->verifyOtp($user->email, $otpClean, 'change_password', $user->id);

        if (!$result['success']) {
            return back()->withErrors(['otp' => $result['message']])->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route($this->roleRedirect())->with('success', 'Password changed successfully!');
    }

    // ─────────────────────────────────────────
    // TEACHER CHANGE PASSWORD — Send OTP to email
    // (delegates to sendChangeOtp after role check)
    // ─────────────────────────────────────────
    public function sendTeacherChangeOtp(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'teacher') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return $this->sendChangeOtp($request);
    }

    // ─────────────────────────────────────────
    // TEACHER CHANGE PASSWORD — Verify OTP + save
    // ─────────────────────────────────────────
    public function changeTeacherPassword(Request $request)
    {
        $request->validate([
            'otp'                   => 'required|digits:6',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = Auth::user();

        if ($user->role !== 'teacher') {
            return back()->withErrors(['error' => 'Unauthorized.'])->withInput();
        }

        $result = $this->otpService->verifyOtp($user->email, $request->otp, 'change_password', $user->id);

        if (!$result['success']) {
            return back()->withErrors(['otp' => $result['message']])->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('teacher.profile')->with('success', 'Password changed successfully!');
    }
}
