<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
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
        $ip = $request->ip() ?: 'unknown';

        $cooldown = max(
            Otp::getCooldownRemaining($emailClean, 'register'),
            Otp::getCooldownRemaining($ip, 'register')
        );
        if ($cooldown > 0) {
            return response()->json([
                'success'  => false,
                'status'   => 'error',
                'message'  => "Please wait {$cooldown} seconds before requesting another verification code.",
                'cooldown' => $cooldown,
            ], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        session([
            "{$sessionPrefix}_otp_code"    => $code,
            "{$sessionPrefix}_otp_email"   => $emailClean,
            "{$sessionPrefix}_otp_expires" => now()->addMinutes(10)->timestamp,
        ]);
        Otp::setCooldown($emailClean, 'register');
        Otp::setCooldown($ip, 'register');

        Log::info("Register OTP generated for [{$emailClean}]: {$code} (scope: {$scope})");

        try {
            Mail::to($emailClean)->send(new OtpMail($code, 'register', 'New User'));
            $resp = ['success' => true, 'message' => 'OTP sent to ' . $emailClean];
            if (app()->environment('local', 'testing')) {
                $resp['dev_otp'] = $code;
            }
            return response()->json($resp);
        } catch (\Exception $e) {
            Log::error('Register OTP mail failed: ' . $e->getMessage());
            // In local/testing mode, preserve session OTP and provide code so testing is never blocked by SMTP issues
            if (app()->environment('local', 'testing')) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP generated (Email delivery failed: ' . $e->getMessage() . ')',
                    'dev_otp' => $code,
                ]);
            }
            session()->forget(["{$sessionPrefix}_otp_code", "{$sessionPrefix}_otp_email", "{$sessionPrefix}_otp_expires"]);
            return response()->json(['success' => false, 'message' => 'Failed to send OTP. Please try again.'], 500);
        }
    }

    public function verifyRegisterOtp(Request $request)
    {
        $request->validate(['email' => 'required|email', 'otp' => 'required|digits:6']);

        $emailClean = strtolower(trim((string) $request->email));
        $otpClean   = trim((string) $request->otp);

        $scope = $request->input('scope', 'register');
        $sessionPrefix = $scope === 'admin_student' ? 'admin_reg' : 'reg';

        $sessionCode    = session("{$sessionPrefix}_otp_code");
        $sessionEmail   = session("{$sessionPrefix}_otp_email");
        $sessionExpires = session("{$sessionPrefix}_otp_expires");

        $sessionEmailClean = strtolower(trim((string) $sessionEmail));
        $sessionCodeClean  = trim((string) $sessionCode);

        if (
            !$sessionCodeClean ||
            $emailClean !== $sessionEmailClean ||
            $otpClean   !== $sessionCodeClean  ||
            now()->timestamp > $sessionExpires
        ) {
            $fails = Otp::recordFailedVerify($emailClean, 'register');
            if ($fails >= Otp::MAX_VERIFY_ATTEMPTS) {
                session()->forget(["{$sessionPrefix}_otp_code", "{$sessionPrefix}_otp_email", "{$sessionPrefix}_otp_expires"]);
                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed attempts. This OTP has been invalidated. Please request a new one.'
                ], 422);
            }
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP. (' . (Otp::MAX_VERIFY_ATTEMPTS - $fails) . ' attempts remaining)'
            ], 422);
        }

        Otp::clearFailedVerify($emailClean, 'register');
        // Consume the OTP — single-use
        session()->forget(["{$sessionPrefix}_otp_code", "{$sessionPrefix}_otp_expires"]);
        session(["{$sessionPrefix}_email_verified" => $emailClean]);
        Log::info("Register OTP successfully verified for [{$emailClean}] (scope: {$scope})");
        return response()->json(['success' => true]);
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
        $ip = $request->ip() ?: 'unknown';

        $cooldown = max(
            Otp::getCooldownRemaining($identifier, 'forgot_password'),
            Otp::getCooldownRemaining($ip, 'forgot_password')
        );
        if ($cooldown > 0) {
            return back()->withInput()->withErrors([
                'identifier' => "Please wait {$cooldown} seconds before requesting a new password reset code."
            ]);
        }

        // Always set cooldown BEFORE user lookup — prevents timing-based enumeration
        Otp::setCooldown($identifier, 'forgot_password');
        Otp::setCooldown($ip, 'forgot_password');

        $user = User::where('email', $identifier)
            ->orWhere('student_number', $identifier)
            ->orWhere('employee_id', $identifier)
            ->first();

        if ($user) {
            $otp = Otp::generate($user->id, 'forgot_password');
            Log::info("Forgot password OTP generated for user #{$user->id} ({$user->email}, identifier: {$identifier}): {$otp->code}");
            
            if (app()->environment('local', 'testing')) {
                session()->flash('dev_otp', $otp->code);
            }

            try {
                Mail::to($user->email)->send(new OtpMail($otp->code, 'forgot_password', $user->name));
            } catch (\Exception $e) {
                Log::error("Forgot password mail failed: " . $e->getMessage());
                if (app()->environment('local', 'testing')) {
                    session()->flash('dev_otp', $otp->code);
                }
            }
        } else {
            Log::warning("Forgot password OTP requested for non-existent identifier: {$identifier}");
        }

        // Store identifier stably in session so page refreshes or retries don't lose it
        session(['otp_identifier' => $identifier]);

        // Always return the same neutral message regardless of whether the user exists
        return redirect()->route('otp.verify.form', ['purpose' => 'forgot_password'])
                         ->with('info', 'If your account is registered, an OTP has been sent.');
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

        $user = User::where('email', $identifier)
            ->orWhere('student_number', $identifier)
            ->orWhere('employee_id', $identifier)
            ->first();

        // Treat "user not found" the same as "wrong OTP" — no enumeration
        if (!$user) {
            $fails = Otp::recordFailedVerify($identifier, $request->purpose);
            if ($fails >= Otp::MAX_VERIFY_ATTEMPTS) {
                return back()->with('otp_identifier', $identifier)
                             ->withErrors(['otp' => 'Too many failed verification attempts. Please request a new code.'])
                             ->withInput();
            }
            return back()->with('otp_identifier', $identifier)
                         ->withErrors([
                             'otp' => 'Invalid or expired OTP. (' . (Otp::MAX_VERIFY_ATTEMPTS - $fails) . ' attempts remaining)'
                         ])->withInput();
        }

        $otpRecord = Otp::where('user_id', $user->id)
                        ->where('code', $otpClean)
                        ->where('purpose', $request->purpose)
                        ->where('used', false)
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->first();

        if (!$otpRecord) {
            // In local/testing mode, re-flash the valid OTP code to make testing easy
            if (app()->environment('local', 'testing')) {
                $latestUnused = Otp::where('user_id', $user->id)
                    ->where('purpose', $request->purpose)
                    ->where('used', false)
                    ->where('expires_at', '>', now())
                    ->latest()
                    ->first();
                if ($latestUnused) {
                    session()->flash('dev_otp', $latestUnused->code);
                }
            }

            $fails = Otp::recordFailedVerify($user->id, $request->purpose);
            if ($fails >= Otp::MAX_VERIFY_ATTEMPTS) {
                Otp::where('user_id', $user->id)
                   ->where('purpose', $request->purpose)
                   ->update(['used' => true]);
                return back()->with('otp_identifier', $identifier)
                             ->withErrors([
                                 'otp' => 'Too many failed verification attempts. This code has been invalidated. Please request a new one.'
                             ])->withInput();
            }
            return back()->with('otp_identifier', $identifier)
                         ->withErrors([
                             'otp' => 'Invalid or expired OTP. (' . (Otp::MAX_VERIFY_ATTEMPTS - $fails) . ' attempts remaining)'
                         ])->withInput();
        }

        Otp::clearFailedVerify($user->id, $request->purpose);
        $otpRecord->update(['used' => true]);
        session(['otp_verified_user' => $user->id, 'otp_purpose' => $request->purpose]);

        Log::info("OTP successfully verified for user #{$user->id} ({$user->email}, purpose: {$request->purpose})");

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
    // CHANGE EMAIL — Send OTP to CURRENT email
    // ─────────────────────────────────────────
    public function sendEmailChangeOtp(Request $request)
    {
        $user = Auth::user();
        $ip   = $request->ip() ?: 'unknown';

        $cooldown = max(
            Otp::getCooldownRemaining($user->id, 'change_email'),
            Otp::getCooldownRemaining($ip, 'change_email')
        );
        if ($cooldown > 0) {
            return response()->json([
                'success'  => false,
                'status'   => 'error',
                'message'  => "Please wait {$cooldown} seconds before requesting a new code.",
                'cooldown' => $cooldown,
            ], 429);
        }

        $otp = Otp::generate($user->id, 'change_email');
        Otp::setCooldown($user->id, 'change_email');
        Otp::setCooldown($ip, 'change_email');

        Log::info("Change email OTP generated for user #{$user->id} ({$user->email}): {$otp->code}");

        try {
            Mail::to($user->email)->send(new OtpMail($otp->code, 'change_email', $user->name));
            $resp = ['success' => true, 'message' => 'OTP sent to your current email.'];
            if (app()->environment('local', 'testing')) {
                $resp['dev_otp'] = $otp->code;
            }
            return response()->json($resp);
        } catch (\Exception $e) {
            Log::error('Change email OTP mail failed: ' . $e->getMessage());
            if (app()->environment('local', 'testing')) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP generated (Email delivery failed: ' . $e->getMessage() . ')',
                    'dev_otp' => $otp->code,
                ]);
            }
            $otp->update(['used' => true]);
            return response()->json(['success' => false, 'message' => 'Failed to send OTP. Please try again.'], 500);
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

        $otpRecord = Otp::where('user_id', $user->id)
                        ->where('code', $otpClean)
                        ->where('purpose', 'change_email')
                        ->where('used', false)
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->first();

        if (!$otpRecord) {
            $fails = Otp::recordFailedVerify($user->id, 'change_email');
            if ($fails >= Otp::MAX_VERIFY_ATTEMPTS) {
                Otp::where('user_id', $user->id)->where('purpose', 'change_email')->update(['used' => true]);
                return back()->withErrors(['otp' => 'Too many failed attempts. Code invalidated. Please request a new one.'])->withInput();
            }
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        Otp::clearFailedVerify($user->id, 'change_email');
        $otpRecord->update(['used' => true]);
        $user->update(['email' => strtolower(trim($request->new_email))]);

        return redirect()->route($this->roleRedirect())->with('success', 'Email address updated successfully!');
    }

    // ─────────────────────────────────────────
    // CHANGE PASSWORD — Send OTP to email
    // ─────────────────────────────────────────
    public function sendChangeOtp(Request $request)
    {
        $user = Auth::user();
        $ip   = $request->ip() ?: 'unknown';

        $cooldown = max(
            Otp::getCooldownRemaining($user->id, 'change_password'),
            Otp::getCooldownRemaining($ip, 'change_password')
        );
        if ($cooldown > 0) {
            return response()->json([
                'success'  => false,
                'status'   => 'error',
                'message'  => "Please wait {$cooldown} seconds before requesting a new code.",
                'cooldown' => $cooldown,
            ], 429);
        }

        $otp = Otp::generate($user->id, 'change_password');
        Otp::setCooldown($user->id, 'change_password');
        Otp::setCooldown($ip, 'change_password');

        Log::info("Change password OTP generated for user #{$user->id} ({$user->email}): {$otp->code}");

        try {
            Mail::to($user->email)->send(new OtpMail($otp->code, 'change_password', $user->name));
            $resp = ['success' => true, 'message' => 'OTP sent to your registered email.'];
            if (app()->environment('local', 'testing')) {
                $resp['dev_otp'] = $otp->code;
            }
            return response()->json($resp);
        } catch (\Exception $e) {
            Log::error('Change password OTP mail failed: ' . $e->getMessage());
            if (app()->environment('local', 'testing')) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP generated (Email delivery failed: ' . $e->getMessage() . ')',
                    'dev_otp' => $otp->code,
                ]);
            }
            $otp->update(['used' => true]);
            return response()->json(['success' => false, 'message' => 'Failed to send OTP. Please try again.'], 500);
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

        $otpRecord = Otp::where('user_id', $user->id)
                        ->where('code', $otpClean)
                        ->where('purpose', 'change_password')
                        ->where('used', false)
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->first();

        if (!$otpRecord) {
            $fails = Otp::recordFailedVerify($user->id, 'change_password');
            if ($fails >= Otp::MAX_VERIFY_ATTEMPTS) {
                Otp::where('user_id', $user->id)->where('purpose', 'change_password')->update(['used' => true]);
                return back()->withErrors(['otp' => 'Too many failed attempts. Code invalidated. Please request a new one.'])->withInput();
            }
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        Otp::clearFailedVerify($user->id, 'change_password');
        $otpRecord->update(['used' => true]);
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

        $otpRecord = Otp::where('user_id', $user->id)
                        ->where('code', $request->otp)
                        ->where('purpose', 'change_password')
                        ->where('used', false)
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->first();

        if (!$otpRecord) {
            $fails = Otp::recordFailedVerify($user->id, 'change_password');
            if ($fails >= Otp::MAX_VERIFY_ATTEMPTS) {
                Otp::where('user_id', $user->id)->where('purpose', 'change_password')->update(['used' => true]);
                return back()->withErrors(['otp' => 'Too many failed attempts. Code invalidated. Please request a new one.'])->withInput();
            }
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        Otp::clearFailedVerify($user->id, 'change_password');
        $otpRecord->update(['used' => true]);
        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('teacher.profile')->with('success', 'Password changed successfully!');
    }
}
