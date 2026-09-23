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
use Illuminate\Support\Facades\DB;

use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\LoginRequest;

class PTController extends Controller
{
    
    public function register(RegisterUserRequest $request)
    {
        // Validation is handled by RegisterUserRequest

        $cleanEmail = strtolower(trim((string) $request->email));

        // 1. Double check Gmail format
        if (!\App\Services\OtpService::isValidGmailFormat($cleanEmail)) {
            if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a valid Gmail address (e.g., username@gmail.com).',
                    'errors'  => [
                        'email' => ['Please enter a valid Gmail address (e.g., username@gmail.com).']
                    ]
                ], 422);
            }
            return back()->withInput()->withErrors([
                'email' => 'Please enter a valid Gmail address (e.g., username@gmail.com).'
            ]);
        }

        // 2. Double check verified OTP email session
        $verifiedEmail = strtolower(trim((string) session('reg_email_verified', '')));
        if (!$verifiedEmail || $verifiedEmail !== $cleanEmail) {
            if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email address is unverified. Please verify your email with the verification code before completing registration.',
                    'errors'  => [
                        'email' => ['This email address is unverified. Please verify your email with the verification code before completing registration.']
                    ]
                ], 422);
            }
            return back()->withInput()->withErrors([
                'email' => 'This email address is unverified. Please verify your email with the verification code before completing registration.'
            ]);
        }
        
        // Clear the session so it cannot be reused
        session()->forget('reg_email_verified');

        // 3. Create user based on role
        $userData = [
            'name'              => $request->name,
            'email'             => $request->email,
            'email_verified_at' => now(),
            'password'          => Hash::make($request->password),
            'role'              => $request->role,
        ];
        if ($request->filled('phone')) {
            $userData['phone'] = trim($request->phone);
        }

        if ($request->role === 'student') {
            $hasCustomStudentNumber = $request->filled('student_number') && trim($request->student_number) !== '';
            $userData['student_number'] = $hasCustomStudentNumber
                ? trim($request->student_number)
                : User::generateStudentNumber();
            $userData['course'] = $request->course ?: 'BSCS';
            $userData['year_level'] = $request->year_level;
            $userData['semester'] = $request->semester;
            $userData['section'] = $request->section;
        }

        /** @var \App\Models\User $user */
        $maxAttempts = 5;
        $user = null;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                if ($request->role === 'student' && $attempt > 1 && empty($hasCustomStudentNumber)) {
                    $userData['student_number'] = User::generateStudentNumber();
                }
                $user = DB::transaction(function () use ($userData) {
                    return User::create($userData);
                });
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempt < $maxAttempts && str_contains($e->getMessage(), 'student_number') && empty($hasCustomStudentNumber)) {
                    continue;
                }
                throw $e;
            }
        }

        // Link parent to student if student identifier was provided
        if ($user && $request->role === 'parent') {
            $studentIdentifier = trim((string) ($request->student_number ?? $request->student_id ?? ''));
            if ($studentIdentifier !== '') {
                $rawNumbers = preg_split('/[,\s]+/', $studentIdentifier, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($rawNumbers as $num) {
                    $st = User::where('role', 'student')->where(function($q) use ($num) {
                        $q->where('student_number', $num)
                          ->orWhere('student_number', ltrim($num, '0'))
                          ->orWhere('id', $num);
                    })->first();
                    if ($st) {
                        DB::table('parent_student')->insertOrIgnore([
                            'parent_id' => $user->id,
                            'student_id' => $st->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        if (empty($st->guardian_email)) {
                            $st->update(['guardian_email' => $user->email]);
                        }
                        try {
                            \App\Models\Notification::create([
                                'user_id' => $st->id,
                                'sent_by' => $user->id,
                                'type'    => 'parent_linked',
                                'message' => "Your parent/guardian {$user->name} ({$user->email}) connected to your student profile during registration.",
                                'is_read' => false,
                            ]);
                        } catch (\Throwable $e) {}
                    }
                }
            }
        }

        // 4. Log them in and redirect based on role
        Auth::login($user, true);
        $request->session()->regenerate();
        
        if ($user->isStudent()) {
            app(DeviceBindingService::class)->bind($user, $request);
            $request->session()->save();
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
            ?: $request->input('phone')
            ?: $request->input('phone_number')
            ?: $request->input('mobile')
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

        // 1. Check account lockout (account-specific brute force protection)
        if ($lockoutService->isAccountLocked($identifier)) {
            $remaining = $lockoutService->getRemainingSeconds($identifier, $request->ip());
            $minutes = max(1, ceil($remaining / 60));
            $errorMessage = "This account is temporarily locked due to repeated failed login attempts. Please try again in {$minutes} minutes.";

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

        // If IP is locked from previous failed attempts on a shared campus network,
        // we defer blocking so that legitimate users with correct passwords can still log in.
        $isIpLocked = $lockoutService->isLocked($identifier, $request->ip());

        // Look up user by student_number, email, employee_id, phone, id, or normalized format
        $user = User::findByIdentifier($identifier);

        $authenticated = false;

        if ($user) {
            $trimmedPassword = trim($password);
            $passwordMatches = Hash::check($password, $user->password)
                || ($password !== $trimmedPassword && Hash::check($trimmedPassword, $user->password));

            // Demo/seed fallback for student accounts: support standard passwords (student123 and password)
            if (!$passwordMatches && $user->isStudent()) {
                if (($password === 'password' || $password === 'student123') &&
                    (Hash::check('password', $user->password) || Hash::check('student123', $user->password))) {
                    $passwordMatches = true;
                }
            }

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

        // Fallback: If identifier matches multiple accounts, or user was not resolved directly
        if (!$authenticated) {
            $normalizedIdentifier = preg_replace('/^(?:student[\s_-]*(?:id|number|no|#)?|id[\s:#_-]*|sn[\s:#_-]*|lrn[\s:#_-]*)\s*/i', '', $identifier);
            $digitsOnly = preg_replace('/\D/', '', $normalizedIdentifier ?: $identifier);
            $num = $digitsOnly !== '' ? (int)$digitsOnly : null;

            $candidateVariants = array_filter(array_unique([
                $identifier,
                $normalizedIdentifier,
                ltrim($identifier, '0'),
                ltrim($normalizedIdentifier, '0'),
                preg_replace('/[^a-zA-Z0-9]/', '', $identifier),
                preg_replace('/[^a-zA-Z0-9]/', '', $normalizedIdentifier),
                $num !== null ? sprintf('%06d', $num) : null,
                $num !== null ? sprintf('%07d', $num) : null,
                $num !== null ? sprintf('%08d', $num) : null,
            ]));

            $phoneVariants = User::getPhoneVariants($identifier);

            $candidateUsers = User::whereNull('deleted_at')
                ->where(function ($q) use ($candidateVariants, $phoneVariants, $num) {
                    $q->whereIn('student_number', $candidateVariants)
                      ->orWhereIn('employee_id', $candidateVariants)
                      ->orWhereIn('email', $candidateVariants);
                    if ($num !== null) {
                        $q->orWhere('id', $num);
                    }
                    if (!empty($phoneVariants)) {
                        $q->orWhereIn('phone', $phoneVariants);
                    }
                })
                ->when($user, fn($q) => $q->where('id', '!=', $user->id))
                ->get();

            foreach ($candidateUsers as $cand) {
                $trimmedPassword = trim($password);
                $candMatches = Hash::check($password, $cand->password)
                    || ($password !== $trimmedPassword && Hash::check($trimmedPassword, $cand->password));

                if (!$candMatches && $cand->isStudent() && ($password === 'password' || $password === 'student123')) {
                    $candMatches = Hash::check('password', $cand->password) || Hash::check('student123', $cand->password);
                }

                if ($candMatches) {
                    if (!$cand->isActive()) {
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
                    $user = $cand;
                    Auth::login($user, $remember);
                    $authenticated = true;
                    break;
                }
            }
        }

        // Fallback standard attempts only if user was not resolved
        if (!$authenticated && !$user) {
            $authenticated = Auth::attempt(['student_number' => $identifier, 'password' => $password], $remember)
                || Auth::attempt(['email' => $identifier, 'password' => $password], $remember)
                || Auth::attempt(['employee_id' => $identifier, 'password' => $password], $remember)
                || Auth::attempt(['phone' => $identifier, 'password' => $password], $remember);

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
            if ($user->phone) $lockoutService->clear($user->phone, $request->ip());

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

                app(DeviceBindingService::class)->bind($user, $request);
                $request->session()->save();

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
                // 2FA disabled — go straight to dashboard
                $request->session()->put('admin_2fa_verified', true);
                $request->session()->save();
                $targetUrl = route('admin.dashboard');
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

        if ($isIpLocked || $lockoutResult['locked']) {
            $remaining = $lockoutResult['lockout_seconds'] ?? ($isIpLocked ? $lockoutService->getRemainingSeconds($identifier, $request->ip()) : 900);
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
        $user->load('deviceBinding');
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
        $subjects->load(['schedules', 'instructorUser']);

        return view('student.classes', compact('subjects', 'user'));
    }

    public function myClassesPdf()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $subjects = $user->getAllSubjects();
        $subjects->load(['schedules', 'instructorUser']);
        $subjects = $subjects->sortBy('code')->values();

        $pdf = Pdf::loadView('student.classes-pdf', compact('user', 'subjects'))
            ->setPaper('a4', 'landscape');

        $filename = 'Certificate_of_Registration_' . ($user->student_number ?: $user->id) . '.pdf';
        return $pdf->download($filename);
    }
}
