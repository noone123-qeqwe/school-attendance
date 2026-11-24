<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RecoveryCode;
use App\Services\AccountLockoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RecoveryCodeController extends Controller
{
    public function login(Request $request, AccountLockoutService $lockoutService)
    {
        $request->validate([
            'identifier' => 'required|string',
            'recovery_code' => 'required|string',
        ]);

        $identifier = trim($request->identifier);
        $ip = $request->ip();

        if ($lockoutService->isAccountLocked($identifier) || $lockoutService->isLocked($identifier, $ip)) {
            $seconds = max(60, $lockoutService->getRemainingSeconds($identifier, $ip));
            $minutes = (int) ceil($seconds / 60);

            return back()->withErrors([
                'identifier' => "Too many failed sign-in attempts. Please wait {$minutes} minute(s) before trying again.",
            ])->withInput($request->only('identifier'));
        }

        $user = User::findByIdentifier($identifier, false);

        if (!$user) {
            $lockoutService->recordFailedAttempt($identifier, $ip);
            return back()->withErrors(['identifier' => 'Invalid credentials or recovery code.'])->withInput($request->only('identifier'));
        }

        // Ensure user account is active before verifying recovery secrets
        if (!$user->isActive()) {
            return back()->withErrors(['identifier' => 'Your account has been deactivated. Please contact the school administrator.'])->withInput($request->only('identifier'));
        }

        // Fetch unused recovery codes for this user
        $recoveryCodes = RecoveryCode::where('user_id', $user->id)
                                    ->where('used', false)
                                    ->get();

        $validCode = null;
        foreach ($recoveryCodes as $rc) {
            if (Hash::check($request->recovery_code, $rc->code)) {
                $validCode = $rc;
                break;
            }
        }

        if (!$validCode) {
            $lockoutService->recordFailedAttempt($identifier, $ip);
            return back()->withErrors(['identifier' => 'Invalid credentials or recovery code.'])->withInput($request->only('identifier'));
        }

        // Block shared starter-password accounts from bypassing credential rotation via recovery code
        if ($user->usesSharedStarterPassword()) {
            $user->forceFill(['must_change_password' => true])->saveQuietly();
            return back()->withErrors([
                'identifier' => 'This account uses a shared starter password. Use Forgot Password to verify your email and set a new password.',
            ])->withInput($request->only('identifier'));
        }

        $lockoutService->clear($identifier, $ip);

        // Mark code as used
        $validCode->update([
            'used' => true,
            'used_at' => now(),
        ]);

        // Log the user in
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget('admin_2fa_verified');
        $request->session()->put('user_role', $user->role);
        $request->session()->put('login_timestamp', now()->toString());

        // Bind device on recovery login (same as password login)
        if ($user->isStudent()) {
            app(\App\Services\DeviceBindingService::class)->bind($user, $request);
            $request->session()->save();
        }

        if ($user->ensurePasswordChangeRequired()) {
            return redirect()->route('password.change.form');
        }

        // Redirect based on role (admin middleware enforces 2FA for admins)
        if ($user->isAdmin()) return redirect()->route('admin.dashboard');
        if ($user->isTeacher()) return redirect()->route('teacher.dashboard');
        if ($user->isParent()) return redirect()->route('parent.dashboard');
        return redirect()->route('home');
    }

    public function generate(Request $request)
    {
        $user = Auth::user();
        
        // Delete any existing unused codes
        RecoveryCode::where('user_id', $user->id)->where('used', false)->delete();

        $recoveryCodes = [];
        for ($i = 0; $i < 5; $i++) {
            $rawCode = strtoupper(\Illuminate\Support\Str::random(4) . '-' . \Illuminate\Support\Str::random(4));
            $recoveryCodes[] = $rawCode;
            
            RecoveryCode::create([
                'user_id' => $user->id,
                'code' => Hash::make($rawCode),
            ]);
        }

        return response()->json([
            'success' => true,
            'codes' => $recoveryCodes
        ]);
    }
}
