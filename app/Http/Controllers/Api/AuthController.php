<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountLockoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        protected AccountLockoutService $lockoutService
    ) {}

    public function login(Request $request)
    {
        $candidates = [
            $request->input('identifier'),
            $request->input('student_id'),
            $request->input('student_number'),
            $request->input('studentId'),
            $request->input('email'),
            $request->input('username'),
            $request->input('employee_id'),
            $request->input('employeeId'),
            $request->input('id'),
            $request->input('login'),
            $request->input('user'),
            $request->input('user_id'),
            $request->input('userId'),
        ];
        $identifier = '';
        foreach ($candidates as $c) {
            if (is_scalar($c) && trim((string)$c) !== '') {
                $identifier = trim((string)$c);
                break;
            }
        }

        $passCandidates = [
            $request->input('password'),
            $request->input('pass'),
            $request->input('pwd'),
            $request->input('user_password'),
        ];
        $password = '';
        foreach ($passCandidates as $p) {
            if (is_scalar($p) && (string)$p !== '') {
                $password = (string)$p;
                break;
            }
        }

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
        if ($identifier === '' || $password === '') {
            $result = $this->lockoutService->recordFailedAttempt($identifier ?: 'unknown', $ip);
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
                    'identifier' => ['The identifier field is required.'],
                    'password' => ['The password field is required.'],
                ],
                'remaining_attempts' => $result['remaining_attempts'],
            ], 422);
        }

        // 3. Attempt authentication via User lookup
        $user = User::findByIdentifier($identifier);
        $authenticated = false;

        if ($user && (Hash::check($password, $user->password) || Hash::check(trim($password), $user->password))) {
            $authenticated = true;
        } else {
            // Fallback standard attempts
            $authenticated = Auth::attempt(['student_number' => $identifier, 'password' => $password])
                || Auth::attempt(['email' => $identifier, 'password' => $password])
                || Auth::attempt(['employee_id' => $identifier, 'password' => $password])
                || Auth::attempt(['student_number' => $identifier, 'password' => trim($password)])
                || Auth::attempt(['email' => $identifier, 'password' => trim($password)]);
            if ($authenticated) {
                $user = Auth::user();
            }
        }

        if ($authenticated && $user) {
            $this->lockoutService->clear($identifier, $ip);
            if ($user->email) $this->lockoutService->clear($user->email, $ip);
            if ($user->student_number) $this->lockoutService->clear($user->student_number, $ip);
            if ($user->employee_id) $this->lockoutService->clear($user->employee_id, $ip);

            if (!$user->isActive()) {
                Auth::logout();
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact the school administrator.',
                    'account_disabled' => true,
                ], 403);
            }

            $token = $user->createToken('mobile-app')->plainTextToken;

            // Determine dashboard URL
            $dashboardUrl = url('/home');
            if ($user->isAdmin()) {
                $dashboardUrl = route('admin.dashboard');
            } elseif ($user->isTeacher() || $user->isDepartmentHead()) {
                $dashboardUrl = route('teacher.dashboard');
            } elseif ($user->isParent()) {
                $dashboardUrl = route('parent.dashboard');
            }

            return response()->json([
                'status' => 'success',
                'success' => true,
                'user' => $user,
                'token' => $token,
                'role' => $user->role,
                'dashboard_url' => $dashboardUrl,
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
            'message' => 'Incorrect ID/email or password. (' . $result['remaining_attempts'] . ' attempts remaining before account lockout)',
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
