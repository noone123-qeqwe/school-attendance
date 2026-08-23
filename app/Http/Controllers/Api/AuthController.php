<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccountLockoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected AccountLockoutService $lockoutService
    ) {}

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $identifier = strtolower(trim((string) $request->input('email', $request->input('identifier', $request->input('username', '')))));
        $ip = $request->ip();

        // 1. Check account / IP lockout
        if ($this->lockoutService->isLocked($identifier, $ip)) {
            $remaining = $this->lockoutService->getRemainingSeconds($identifier, $ip);
            $minutes = max(1, ceil($remaining / 60));

            return response()->json([
                'status' => 'error',
                'message' => "Account is temporarily locked due to repeated failed login attempts. Please try again in {$minutes} minutes.",
                'locked' => true,
                'retry_after' => $remaining,
            ], 429);
        }

        // 2. Attempt authentication
        $credentials = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? ['email' => $identifier, 'password' => $request->password]
            : ['student_number' => $identifier, 'password' => $request->password];

        $authenticated = Auth::attempt($credentials);

        if (!$authenticated && !filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            // Also try employee_id fallback
            $authenticated = Auth::attempt(['employee_id' => $identifier, 'password' => $request->password])
                || Auth::attempt(['email' => $identifier, 'password' => $request->password]);
        }

        if ($authenticated) {
            $this->lockoutService->clear($identifier, $ip);

            $user = Auth::user();
            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'user' => $user,
                'token' => $token,
                'role' => $user->role,
            ]);
        }

        // 3. Record failed attempt & check if newly locked
        $result = $this->lockoutService->recordFailedAttempt($identifier, $ip);

        if ($result['locked']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account is temporarily locked due to repeated failed login attempts. Please try again in 15 minutes.',
                'locked' => true,
                'retry_after' => $result['lockout_seconds'],
            ], 429);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid credentials. (' . $result['remaining_attempts'] . ' attempts remaining before account lockout)',
            'remaining_attempts' => $result['remaining_attempts'],
        ], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }
}
