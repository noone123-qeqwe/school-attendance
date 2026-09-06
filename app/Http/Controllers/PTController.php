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
            $userData['student_number'] = $request->student_number;
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
        // Validation is handled by LoginRequest
        $identifier = trim($request->identifier);
        $password   = $request->password;
        $remember   = $request->has('remember') ? $request->boolean('remember') : true;
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

        // Look up the user by student_number, email, or employee_id (including deactivated)
        $user = User::withTrashed()->where('student_number', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('employee_id', $identifier)
            ->orWhereRaw('LOWER(email) = ?', [strtolower($identifier)])
            ->orWhereRaw('LOWER(student_number) = ?', [strtolower($identifier)])
            ->orWhereRaw('LOWER(employee_id) = ?', [strtolower($identifier)])
            ->first();

        // Also check with non-alphanumeric stripped (in case of dashes/spaces)
        if (!$user) {
            $clean = preg_replace('/[^a-zA-Z0-9]/', '', $identifier);
            if ($clean !== '') {
                $user = User::withTrashed()->whereRaw("REPLACE(REPLACE(student_number, '-', ''), ' ', '') = ?", [$clean])
                    ->orWhereRaw("REPLACE(REPLACE(employee_id, '-', ''), ' ', '') = ?", [$clean])
                    ->first();
            }
        }

        // Check if account is deactivated
        if ($user && Hash::check($password, $user->password)) {
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
        }

        $authenticated = false;
        if ($user && $user->isActive()) {
            // Attempt login with the found user's email
            $authenticated = Auth::attempt(['email' => $user->email, 'password' => $password], $remember);
        } else {
            // Fallback standard attempts
            $authenticated = Auth::attempt(['student_number' => $identifier, 'password' => $password], $remember)
                || Auth::attempt(['email' => $identifier, 'password' => $password], $remember);
            if ($authenticated) {
                $user = Auth::user();
                if (!$user->isActive()) {
                    Auth::logout();
                    $authenticated = false;
                    return back()->withInput($request->only('identifier'))
                        ->withErrors(['identifier' => 'Your account has been deactivated. Please contact the school administrator.']);
                }
            }
        }

        if ($authenticated && $user) {
            $lockoutService->clear($identifier, $request->ip());

            Log::info('Login successful', [
                'user_id' => $user->id,
                'role' => $user->role,
                'identifier' => $identifier,
                'session_id' => $request->session()->getId()
            ]);

            $request->session()->regenerate();
            $request->session()->put('user_role', $user->role);
            $request->session()->put('login_timestamp', now()->toString());

            // Handle student role
            if ($user->isStudent()) {
                $request->session()->put('user_role', 'student');
                $request->session()->put('login_timestamp', now());
                $request->session()->save();

                app(DeviceBindingService::class)->bind($user, $request);

                if ($request->filled('qr_token')) {
                    return redirect()->route('qr.scan', ['token' => $request->qr_token]);
                }
                return redirect()->intended('/home');
            }

            // Handle admin role
            if ($user->isAdmin()) {
                Log::info('Admin login successful', ['user_id' => $user->id, 'session_id' => $request->session()->getId()]);

                if (app()->environment('local', 'testing')) {
                    $request->session()->put('admin_2fa_verified', true);
                    $request->session()->save();
                    return redirect()->route('admin.dashboard');
                }

                $otp = \App\Models\Otp::generate($user->id, 'admin_login');
                try {
                    app(\App\Services\Email\EmailDeliveryService::class)->sendOtp($user->email, $otp->code, 'admin_login', $user->name);
                } catch (\Exception $e) {
                    Log::error('Failed to send admin 2FA OTP: ' . $e->getMessage());
                }
                return redirect()->route('admin.2fa.form')->with('info', 'Please check your email for the verification code.');
            }

            // Handle teacher / dept head role
            if ($user->isTeacher() || $user->isDepartmentHead()) {
                return redirect()->route('teacher.dashboard');
            }

            // Handle parent role
            if ($user->isParent()) {
                return redirect()->route('parent.dashboard');
            }

            return redirect()->intended('/home');
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
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,webp,gif,heic,heif|max:10240',
        ], [
            'profile_image.required' => 'Please select an image file to upload.',
            'profile_image.image' => 'The selected file must be a valid image.',
            'profile_image.mimes' => 'Allowed formats: JPG, JPEG, PNG, WEBP, GIF, HEIC.',
            'profile_image.max' => 'The profile image size must not exceed 10 MB.',
        ]);

        $imageUrl = null;
        if ($request->hasFile('profile_image')) {
            /** @var \App\Models\User $user */
            $user = auth()->user();

            // Delete old image if it exists in local storage
            if ($user->profile_image && !str_starts_with($user->profile_image, 'http')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_image);
            }

            $path = $request->file('profile_image')->store('profile_images', 'public');
            $user->profile_image = $path;
            $user->save();

            $imageUrl = '/storage/' . $path;
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile photo updated successfully!',
                'image_url' => $imageUrl,
                'path' => $path ?? null,
            ]);
        }

        return back()->with('success', 'Profile photo updated successfully!');
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
