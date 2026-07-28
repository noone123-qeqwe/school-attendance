<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    // ─────────────────────────────────────────
    // REGISTRATION — Send OTP to verify email
    // ─────────────────────────────────────────
    public function sendRegisterOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
        ]);

        $scope = $request->input('scope', 'register');
        $sessionPrefix = $scope === 'admin_student' ? 'admin_reg' : 'reg';

        // Store a temporary OTP keyed by email (no user yet)
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        session([
            "{$sessionPrefix}_otp_code"    => $code,
            "{$sessionPrefix}_otp_email"   => $request->email,
            "{$sessionPrefix}_otp_expires" => now()->addMinutes(10)->timestamp,
        ]);

        \Illuminate\Support\Facades\Log::info("Registration OTP for {$request->email}: {$code}");

        try {
            Mail::to($request->email)->send(new OtpMail($code, 'register', 'New User'));
            return response()->json(['success' => true, 'message' => 'OTP sent to ' . $request->email]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send OTP: ' . $e->getMessage()], 500);
        }
    }

    public function verifyRegisterOtp(Request $request)
    {
        $request->validate(['email' => 'required|email', 'otp' => 'required|digits:6']);

        $scope = $request->input('scope', 'register');
        $sessionPrefix = $scope === 'admin_student' ? 'admin_reg' : 'reg';

        $sessionCode    = session("{$sessionPrefix}_otp_code");
        $sessionEmail   = session("{$sessionPrefix}_otp_email");
        $sessionExpires = session("{$sessionPrefix}_otp_expires");

        if (
            !$sessionCode ||
            $request->email !== $sessionEmail ||
            $request->otp   !== $sessionCode  ||
            now()->timestamp > $sessionExpires
        ) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.']);
        }

        // Mark email as verified in session
        session(["{$sessionPrefix}_email_verified" => $request->email]);
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────
    // FORGOT PASSWORD — Step 1: Enter email
    // ─────────────────────────────────────────
    public function forgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendForgotOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        $otp  = Otp::generate($user->id, 'forgot_password');

        \Illuminate\Support\Facades\Log::info("Forgot Password OTP for {$user->email}: {$otp->code}");

        try {
            Mail::to($user->email)->send(new OtpMail($otp->code, 'forgot_password', $user->name));
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Failed to send OTP. Please check your mail configuration.']);
        }

        return redirect()->route('otp.verify.form', ['purpose' => 'forgot_password'])
                         ->with('otp_email', $user->email)
                         ->with('info', 'OTP sent to ' . $user->email);
    }

    // ─────────────────────────────────────────
    // FORGOT PASSWORD — Step 2: Enter OTP
    // ─────────────────────────────────────────
    public function verifyForm(Request $request)
    {
        $purpose = $request->get('purpose', 'forgot_password');
        return view('auth.otp-verify', compact('purpose'));
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email'   => 'required|email|exists:users,email',
            'otp'     => 'required|digits:6',
            'purpose' => 'required|in:forgot_password,change_password',
        ]);

        $user = User::where('email', $request->email)->first();

        $otpRecord = Otp::where('user_id', $user->id)
                        ->where('code', $request->otp)
                        ->where('purpose', $request->purpose)
                        ->where('used', false)
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->first();

        if (!$otpRecord) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP. Please try again.'])->withInput();
        }

        $otpRecord->update(['used' => true]);
        session(['otp_verified_user' => $user->id, 'otp_purpose' => $request->purpose]);

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
        $user->update(['password' => Hash::make($request->password)]);
        session()->forget(['otp_verified_user', 'otp_purpose']);

        return redirect()->route('login')->with('success', 'Password reset successfully! You can now log in.');
    }

    // ─────────────────────────────────────────
    // CHANGE EMAIL — Send OTP to CURRENT email
    // ─────────────────────────────────────────
    public function sendEmailChangeOtp(Request $request)
    {
        $user = Auth::user();
        $otp  = Otp::generate($user->id, 'change_email');

        \Illuminate\Support\Facades\Log::info("Change Email OTP for {$user->email}: {$otp->code}");

        try {
            Mail::to($user->email)->send(new OtpMail($otp->code, 'change_email', $user->name));
            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your current email: ' . $user->email,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send OTP: ' . $e->getMessage()], 500);
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

        $otpRecord = Otp::where('user_id', $user->id)
                        ->where('code', $request->otp)
                        ->where('purpose', 'change_email')
                        ->where('used', false)
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->first();

        if (!$otpRecord) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        $otpRecord->update(['used' => true]);
        $user->update(['email' => $request->new_email]);

        return redirect()->route('settings')->with('success', 'Email address updated successfully!');
    }

    // ─────────────────────────────────────────
    // CHANGE PASSWORD — Send OTP to email
    // ─────────────────────────────────────────
    public function sendChangeOtp(Request $request)
    {
        $user = Auth::user();
        $otp  = Otp::generate($user->id, 'change_password');

        \Illuminate\Support\Facades\Log::info("Change Password OTP for {$user->email}: {$otp->code}");

        try {
            Mail::to($user->email)->send(new OtpMail($otp->code, 'change_password', $user->name));
            return response()->json(['success' => true, 'message' => 'OTP sent to ' . $user->email]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send OTP: ' . $e->getMessage()], 500);
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

        $otpRecord = Otp::where('user_id', $user->id)
                        ->where('code', $request->otp)
                        ->where('purpose', 'change_password')
                        ->where('used', false)
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->first();

        if (!$otpRecord) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        $otpRecord->update(['used' => true]);
        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('settings')->with('success', 'Password changed successfully!');
    }

    // ─────────────────────────────────────────
    // TEACHER CHANGE PASSWORD — Send OTP to email
    // ─────────────────────────────────────────
    public function sendTeacherChangeOtp(Request $request)
    {
        $user = Auth::user();
        
        // Verify user is a teacher
        if ($user->role !== 'teacher') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $otp = Otp::generate($user->id, 'change_password');

        \Illuminate\Support\Facades\Log::info("Teacher Change Password OTP for {$user->email}: {$otp->code}");

        try {
            Mail::to($user->email)->send(new OtpMail($otp->code, 'change_password', $user->name));
            return response()->json(['success' => true, 'message' => 'OTP sent to ' . $user->email]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send OTP: ' . $e->getMessage()], 500);
        }
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

        // Verify user is a teacher
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
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        $otpRecord->update(['used' => true]);
        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('teacher.profile')->with('success', 'Password changed successfully!');
    }
}
