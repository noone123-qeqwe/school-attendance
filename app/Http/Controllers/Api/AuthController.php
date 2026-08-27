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
        $identifier = strtolower(trim((string) $request->input('email', $request->input('identifier', $request->input('username', $request->input('student_number', $request->input('employee_id', $request->input('user', ''))))))));
        $password = (string) $request->input('password', $request->input('pass', ''));
        $ip = $request->ip() ?: 'unknown';

        // 1. Check account / IP lockout FIRST before any database or hashing operations
        if ($this->lockoutService->isLocked($identifier, $ip)) {
            $remaining = $this->lockoutService->getRemainingSeconds($identifier, $ip);
            $minutes = max(1, ceil($remaining / 60));

            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => "Account is temporarily locked due to repeated failed login attempts. Please try again in {$minutes} minutes.",
                'locked' => true,
                'retry_after' => $remaining,
            ], 429);
        }

        // 2. Validate presence of credentials
        if (empty($identifier) || empty($password)) {
            $result = $this->lockoutService->recordFailedAttempt($identifier, $ip);
            if ($result['locked']) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Account is temporarily locked due to repeated failed login attempts. Please try again in 15 minutes.',
                    'locked' => true,
                    'retry_after' => $result['lockout_seconds'],
                ], 429);
            }

            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'The identifier and password fields are required.',
                'errors' => [
                    'email' => ['The email/identifier field is required.'],
                    'password' => ['The password field is required.'],
                ],
                'remaining_attempts' => $result['remaining_attempts'],
            ], 422);
        }

        // 3. Attempt authentication
        $credentials = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? ['email' => $identifier, 'password' => $password]
            : ['student_number' => $identifier, 'password' => $password];

        $authenticated = Auth::attempt($credentials);

        if (!$authenticated) {
            $authenticated = Auth::attempt(['employee_id' => $identifier, 'password' => $password])
                || Auth::attempt(['email' => $identifier, 'password' => $password]);
        }

        if ($authenticated) {
            $this->lockoutService->clear($identifier, $ip);

            $user = Auth::user();
            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'success' => true,
                'user' => $user,
                'token' => $token,
                'role' => $user->role,
            ]);
        }

        // 4. Record failed attempt & check if newly locked
        $result = $this->lockoutService->recordFailedAttempt($identifier, $ip);

        if ($result['locked']) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Account is temporarily locked due to repeated failed login attempts. Please try again in 15 minutes.',
                'locked' => true,
                'retry_after' => $result['lockout_seconds'],
            ], 429);
        }

        return response()->json([
            'status' => 'error',
            'success' => false,
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
