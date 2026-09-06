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
        $request->validate(['email' => 'required|email|unique:users,email']);

        $emailClean = strtolower(trim((string) $request->email));
        $scope = $request->input('scope', 'register');
        $sessionPrefix = $scope === 'admin_student' ? 'admin_reg' : 'reg';
        $requestId = $request->header('X-Request-Id') ?: $request->input('request_id');

        try {
            $result = $this->otpService->sendOtp($emailClean, 'register', null, null, $requestId);
            session([
                "{$sessionPrefix}_otp_email" => $emailClean,
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Verification code sent to ' . $emailClean,
                'cooldown'   => $result['cooldown'] ?? Otp::COOLDOWN_SECONDS,
                'retryAfter' => $result['cooldown'] ?? Otp::COOLDOWN_SECONDS,
                'retry_after'=> $result['cooldown'] ?? Otp::COOLDOWN_SECONDS,
                'request_id' => $result['request_id'] ?? $requestId,
            ]);
        } catch (\Exception $e) {
            $status = $e->getCode() === 429 ? 429 : 500;
            $cooldown = $status === 429 ? Otp::getCooldownRemaining($emailClean, 'register') : 0;
            if ($cooldown <= 0 && $status === 429) {
                $cooldown = Otp::COOLDOWN_SECONDS;
            }

            return response()->json([
                'success'    => false,
                'status'     => 'error',
                'error'      => $status === 429 ? 'OTP_RATE_LIMITED' : 'OTP_SEND_FAILED',
                'message'    => $status === 429
                    ? "Please wait {$cooldown} seconds before requesting another code."
                    : ($e->getMessage() ?: 'Unable to send verification code. Please try again.'),
                'cooldown'   => $cooldown,
                'retryAfter' => $cooldown,
                'retry_after'=> $cooldown,
            ], $status);
        }
    }

    public function verifyRegisterOtp(Request $request)
    {
        $request->validate(['email' => 'required|email', 'otp' => 'required|digits:6']);

        $emailClean = strtolower(trim((string) $request->email));
        $otpClean   = trim((string) $request->otp);

        $scope = $request->input('scope', 'register');
        $sessionPrefix = $scope === 'admin_student' ? 'admin_reg' : 'reg';

        $result = $this->otpService->verifyOtp($emailClean, $otpClean, 'register');

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'status'  => $result['status'],
                'message' => $result['message'],
            ], 422);
        }

        // Store confirmed verified email in session for registration submission
        session(["{$sessionPrefix}_email_verified" => $emailClean]);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
        ]);
    }

    // ─────────────────────────────────────────
    // FORGOT PASSWORD — Step 1: Enter identifier
    // ─────────────────────────────────────────
    public function forgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendForgotOtp(Request $request)
    {
        // Accept email, student_number, or employee_id via a unified "identifier" field.
        // Legacy "email" field is also accepted for backward-compatibility.
        $request->validate([
            'identifier' => 'required_without:email|nullable|string|max:255',
            'email'      => 'required_without:identifier|nullable|string|max:255',
        ]);

        $identifier = strtolower(trim((string) $request->input('identifier', $request->input('email', ''))));
        $requestId  = $request->header('X-Request-Id') ?: $request->input('request_id');

        $cooldown = Otp::getCooldownRemaining($identifier, 'forgot_password');
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

        $user = User::where('email', $identifier)
            ->orWhere('student_number', $identifier)
            ->orWhere('employee_id', $identifier)
            ->first();

        // Store identifier stably in session
        session(['otp_identifier' => $identifier]);

        if ($user) {
            try {
                $this->otpService->sendOtp($user->email, 'forgot_password', $user->id, $user->name, $requestId);
            } catch (\Exception $e) {
                if ($request->expectsJson() || $request->ajax()) {
                    $status = $e->getCode() === 429 ? 429 : 500;
                    $cooldownRem = $status === 429 ? Otp::getCooldownRemaining($user->email, 'forgot_password') : 0;
                    return response()->json([
                        'success'    => false,
                        'status'     => 'error',
                        'error'      => $status === 429 ? 'OTP_RATE_LIMITED' : 'OTP_SEND_FAILED',
                        'message'    => $status === 429 
                            ? "Please wait {$cooldownRem} seconds before requesting another code." 
                            : 'Unable to send verification code. Please try again.',
                        'cooldown'   => $cooldownRem,
                        'retryAfter' => $cooldownRem,
                    ], $status);
                }
                return back()->withInput()->withErrors([
                    'identifier' => 'Unable to send verification code. Please try again.'
                ]);
            }
        } else {
            // Mitigate user enumeration
            Otp::setCooldown($identifier, 'forgot_password');
            Log::warning("Forgot password OTP requested for non-existent identifier: {$identifier}");
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'    => true,
                'message'    => 'If your account is registered, a verification code has been sent.',
                'cooldown'   => Otp::COOLDOWN_SECONDS,
                'retryAfter' => Otp::COOLDOWN_SECONDS,
            ]);
        }

        // Always return the same neutral message regardless of whether user exists
        return redirect()->route('otp.verify.form', ['purpose' => 'forgot_password'])
                         ->with('info', 'If your account is registered, a verification code has been sent.');
    }

    // ─────────────────────────────────────────
    // FORGOT PASSWORD — Step 2: Enter OTP
    // ─────────────────────────────────────────
    public function verifyForm(Request $request)
    {
        $purpose = $request->get('purpose', 'forgot_password');
        $identifier = session('otp_identifier') ?? $request->get('identifier', '');
        return view('auth.otp-verify', compact('purpose', 'identifier'));
    }

    public function verifyOtp(Request $request)
    {
        // Accept "identifier" (new unified field), legacy "email", or fallback to session
        $identifierInput = $request->input('identifier', $request->input('email', session('otp_identifier', '')));
        $identifier = strtolower(trim((string) $identifierInput));
        $otpClean   = trim((string) $request->otp);

        if (empty($identifier)) {
            return back()->withErrors(['identifier' => 'Please enter your email, student number, or employee ID.'])->withInput();
        }

        session(['otp_identifier' => $identifier]);

        $request->validate([
            'otp'     => 'required|digits:6',
            'purpose' => 'required|in:forgot_password,change_password',
        ]);

        $result = $this->otpService->verifyOtp($identifier, $otpClean, $request->purpose);

        if (!$result['success']) {
            return back()->with('otp_identifier', $identifier)
                         ->withErrors(['otp' => $result['message']])
                         ->withInput();
        }

        session(['otp_verified_user' => $result['user_id'], 'otp_purpose' => $request->purpose]);

        return redirect()->route('otp.reset.form');
    }

    // ─────────────────────────────────────────
    // FORGOT PASSWORD — Step 3: Set new password
    // ─────────────────────────────────────────
    public function resetForm()
    {
        if (!session('otp_verified_user')) {
            return redirect()->route('otp.forgot.form');
        }
        return view('auth.reset-password');
    }

    public function resetPassword(Request $request)
    {
        if (!session('otp_verified_user')) {
            return redirect()->route('otp.forgot.form');
        }

        $request->validate([
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = User::find(session('otp_verified_user'));
        if ($user) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        session()->forget(['otp_verified_user', 'otp_purpose', 'otp_identifier']);
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
            $request->validate([
                'new_email' => 'required|email|unique:users,email,' . $user->id,
            ]);
            $targetEmail = strtolower(trim((string) $request->new_email));
            session(['pending_new_email' => $targetEmail]);
        }

        try {
            $res = $this->otpService->sendOtp($targetEmail, 'change_email', $user->id, $user->name);
            return response()->json([
                'success'      => true,
                'message'      => 'Verification code sent to ' . $targetEmail . '.',
                'target_email' => $targetEmail,
                'cooldown'     => $res['cooldown'],
            ]);
        } catch (\Exception $e) {
            $status = $e->getCode() === 429 ? 429 : 500;
            return response()->json([
                'success'  => false,
                'message'  => $e->getMessage() ?: 'Unable to send verification code. Please try again.',
                'cooldown' => $status === 429 ? Otp::getCooldownRemaining($targetEmail, 'change_email') : 0,
            ], $status);
        }
    }

    // ─────────────────────────────────────────
    // CHANGE EMAIL — Verify OTP + save new email
    // ─────────────────────────────────────────
    public function changeEmail(Request $request)
    {
        $request->validate([
            'otp'       => 'required|digits:6',
            'new_email' => 'required|email|unique:users,email,' . Auth::id(),
        ]);

        $user = Auth::user();
        $otpClean = trim((string) $request->otp);
        $newEmail = strtolower(trim((string) $request->new_email));

        // Check if OTP was verified against new_email or current user email
        $result = $this->otpService->verifyOtp($newEmail, $otpClean, 'change_email', $user->id);
        if (!$result['success']) {
            $result = $this->otpService->verifyOtp($user->email, $otpClean, 'change_email', $user->id);
        }

        if (!$result['success']) {
            return back()->withErrors(['otp' => $result['message']])->withInput();
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
