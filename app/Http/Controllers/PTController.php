<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\DeviceBindingService;
use Illuminate\Support\Facades\Log;

use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\LoginRequest;

class PTController extends Controller
{
    
    public function register(RegisterUserRequest $request)
    {
        // Validation is now handled by RegisterUserRequest

        // Verify OTP email
        $verifiedEmail = session('reg_email_verified');
        if (!$verifiedEmail || strtolower($verifiedEmail) !== strtolower($request->email)) {
            return back()->withInput()->withErrors(['email' => 'Please verify your email address using the OTP sent to your email.']);
        }
        
        // Clear the session so it cannot be reused
        session()->forget('reg_email_verified');

        // 3. Create user based on role
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ];

        if ($request->role === 'student') {
            // Auto-generate student number if not provided
            $userData['student_number'] = $request->student_number ?: User::generateStudentNumber();
            $userData['course'] = $request->course;
            $userData['year_level'] = $request->year_level;
            $userData['semester'] = $request->semester;
            $userData['section'] = $request->section;
        }

        /** @var \App\Models\User $user */
        $user = User::create($userData);

        // 4. Log them in and redirect based on role
        Auth::login($user, true);
        $request->session()->regenerate();
        
        if ($request->role === 'student') {
            app(DeviceBindingService::class)->bind($user, $request);
            return redirect()->route('home');
        } elseif ($request->role === 'parent') {
            return redirect()->route('parent.dashboard');
        } else {
            return redirect()->route('teacher.dashboard');
        }
    }

    public function login(LoginRequest $request)
    {
        // Resolve identifier and password (supports all aliases and direct request creation)
        $identifier = trim((string)(
            $request->identifier
            ?: $request->input('identifier')
            ?: $request->input('student_id')
            ?: $request->input('student_number')
            ?: $request->input('studentId')
            ?: $request->input('email')
            ?: $request->input('username')
            ?: $request->input('employee_id')
            ?: $request->input('employeeId')
            ?: $request->input('id')
            ?: $request->input('login')
            ?: $request->input('user')
            ?: ''
        ));
        $password = (string) (
            $request->password
            ?: $request->input('pass')
            ?: $request->input('pwd')
            ?: $request->input('user_password')
            ?: ''
        );
        $remember = $request->has('remember') ? $request->boolean('remember') : true;
        $lockoutService = app(\App\Services\AccountLockoutService::class);

        Log::info('Login attempt', ['identifier' => $identifier, 'ip' => $request->ip()]);

        // 1. Check account / IP lockout
        if ($lockoutService->isLocked($identifier, $request->ip())) {
            $remaining = $lockoutService->getRemainingSeconds($identifier, $request->ip());
            $minutes = max(1, ceil($remaining / 60));
            $errorMessage = "Account is temporarily locked due to repeated failed login attempts. Please try again in {$minutes} minutes.";

            if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => $errorMessage,
                    'locked' => true,
                    'retry_after' => $remaining,
                ], 429, ['Retry-After' => $remaining]);
            }

            return back()->withInput($request->only('identifier'))
                ->withErrors(['identifier' => $errorMessage]);
        }

        // Look up user by student_number, email, employee_id, id, or normalized format
        $user = User::findByIdentifier($identifier);

        $authenticated = false;

        if ($user) {
            $trimmedPassword = trim($password);
            $passwordMatches = Hash::check($password, $user->password)
                || ($password !== $trimmedPassword && Hash::check($trimmedPassword, $user->password));

            if ($passwordMatches) {
                // Check if account is deactivated
                if (!$user->isActive()) {
                    $errorMessage = 'Your account has been deactivated. Please contact the school administrator.';
                    if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                        return response()->json([
                            'status' => 'error',
                            'success' => false,
                            'message' => $errorMessage,
                            'account_disabled' => true,
                        ], 403);
                    }
                    return back()->withInput($request->only('identifier'))
                        ->withErrors(['identifier' => $errorMessage]);
                }

                Auth::login($user, $remember);
                $authenticated = true;
            }
        }

        // Fallback standard attempts only if user was not resolved by findByIdentifier
        if (!$authenticated && !$user) {
            $authenticated = Auth::attempt(['student_number' => $identifier, 'password' => $password], $remember)
                || Auth::attempt(['email' => $identifier, 'password' => $password], $remember)
                || Auth::attempt(['employee_id' => $identifier, 'password' => $password], $remember);

            if ($authenticated) {
                $user = Auth::user();
                if (!$user->isActive()) {
                    Auth::logout();
                    $authenticated = false;
                    $errorMessage = 'Your account has been deactivated. Please contact the school administrator.';
                    if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                        return response()->json([
                            'status' => 'error',
                            'success' => false,
                            'message' => $errorMessage,
                            'account_disabled' => true,
                        ], 403);
                    }
                    return back()->withInput($request->only('identifier'))
                        ->withErrors(['identifier' => $errorMessage]);
                }
            }
        }

        if ($authenticated && $user) {
            $lockoutService->clear($identifier, $request->ip());
            if ($user->email) $lockoutService->clear($user->email, $request->ip());
            if ($user->student_number) $lockoutService->clear($user->student_number, $request->ip());
            if ($user->employee_id) $lockoutService->clear($user->employee_id, $request->ip());

            Log::info('Login successful', [
                'user_id' => $user->id,
                'role' => $user->role,
                'identifier' => $identifier,
                'session_id' => $request->session()->getId()
            ]);

            $request->session()->regenerate();
            $request->session()->put('user_role', $user->role);
            $request->session()->put('login_timestamp', now()->toString());

            // Determine target dashboard URL based on role
            $targetUrl = route('home');

            if ($user->isStudent()) {
                $request->session()->put('user_role', 'student');
                $request->session()->put('login_timestamp', now());
                $request->session()->save();

                app(DeviceBindingService::class)->bind($user, $request);

                if ($request->filled('qr_token')) {
                    $targetUrl = route('qr.scan', ['token' => $request->qr_token]);
                } else {
                    $intended = $request->session()->pull('url.intended');
                    if ($intended && !str_contains($intended, '/admin') && !str_contains($intended, '/teacher') && !str_contains($intended, '/parent') && !str_contains($intended, '/login')) {
                        $targetUrl = $intended;
                    } else {
                        $targetUrl = route('home');
                    }
                }
            } elseif ($user->isAdmin()) {
                Log::info('Admin login successful', ['user_id' => $user->id, 'session_id' => $request->session()->getId()]);

                if (app()->environment('local', 'testing')) {
                    $request->session()->put('admin_2fa_verified', true);
                    $request->session()->save();
                    $targetUrl = route('admin.dashboard');
                } else {
                    $otp = \App\Models\Otp::generate($user->id, 'admin_login');
                    try {
                        app(\App\Services\Email\EmailDeliveryService::class)->sendOtp($user->email, $otp->code, 'admin_login', $user->name);
                    } catch (\Exception $e) {
                        Log::error('Failed to send admin 2FA OTP: ' . $e->getMessage());
                    }

                    if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                        return response()->json([
                            'status' => '2fa_required',
                            'requires_2fa' => true,
                            'redirect_url' => route('admin.2fa.form'),
                            'message' => 'Please check your email for the verification code.',
                        ]);
                    }
                    return redirect()->route('admin.2fa.form')->with('info', 'Please check your email for the verification code.');
                }
            } elseif ($user->isTeacher() || $user->isDepartmentHead()) {
                $targetUrl = route('teacher.dashboard');
            } elseif ($user->isParent()) {
                $targetUrl = route('parent.dashboard');
            } else {
                $targetUrl = route('home');
            }

            if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => $user,
                    'role' => $user->role,
                    'redirect_url' => $targetUrl,
                    'dashboard_url' => $targetUrl,
                ]);
            }

            return redirect()->to($targetUrl);
        }

        // Record failed attempt
        $lockoutResult = $lockoutService->recordFailedAttempt($identifier, $request->ip());

        if ($lockoutResult['locked']) {
            $remaining = $lockoutResult['lockout_seconds'] ?? 900;
            $errorMessage = 'Account is temporarily locked due to repeated failed login attempts. Please try again in 15 minutes.';

            if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => $errorMessage,
                    'locked' => true,
                    'retry_after' => $remaining,
                ], 429, ['Retry-After' => $remaining]);
            }

            return back()->withInput($request->only('identifier'))
                ->withErrors(['identifier' => $errorMessage]);
        }

        $errorMessage = 'Incorrect ID/email or password.';

        if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => $errorMessage,
                'errors' => [
                    'identifier' => [$errorMessage],
                ],
            ], 401);
        }

        $response = back()->withInput($request->only('identifier'))
            ->withErrors(['identifier' => $errorMessage]);

        if ($request->filled('qr_token')) {
            $response->with('qr_token', $request->qr_token);
        }

        return $response;
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function profile()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        return view('student.profile', compact('user'));
    }
    public function updateImage(Request $request)
    {
        return app(\App\Http\Controllers\ProfilePhotoController::class)->update($request);
    }
    
    public function myClasses()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

    $subjects = $user->getAllSubjects();
    $subjects->load('schedules');

    return view('student.classes', compact('subjects'));
}

    public function myClassesPdf()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

    $subjects = $user->getAllSubjects();
    $subjects->load('schedules');
    $subjects = $subjects->sortBy('code')->values();

    $pdf = Pdf::loadView('student.classes-pdf', compact('user', 'subjects'))
        ->setPaper('a4', 'landscape');

    return $pdf->download('my-class-schedule.pdf');
}
}
