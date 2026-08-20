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
        Auth::login($user);
        $request->session()->regenerate();
        
        if ($request->role === 'student') {
            app(DeviceBindingService::class)->bind($user, $request);
            return redirect()->route('home');
        } else {
            return redirect()->route('teacher.dashboard');
        }
    }

    public function login(LoginRequest $request)
    {
        // Validation is handled by LoginRequest

    $identifier = trim($request->identifier);
    $password   = $request->password;
    
    Log::info('Login attempt', ['identifier' => $identifier, 'ip' => $request->ip()]);

    // Auto-detect: if no '@' symbol, treat as student number
    if (!str_contains($identifier, '@')) {
        // Student login via student_number
        $credentials = ['student_number' => $identifier, 'password' => $password];
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if (!$user->isStudent()) {
                Auth::logout();
                $request->session()->flush();
                return back()->withInput($request->only('identifier'))
                    ->withErrors(['identifier' => 'Use your email address to sign in.']);
            }
            
            Log::info('Student login successful', ['user_id' => Auth::id(), 'student_number' => $identifier]);
            
            // Set role-specific session
            $request->session()->regenerate();
            $request->session()->put('user_role', 'student');
            $request->session()->put('login_timestamp', now());
            $request->session()->save(); // Ensure session is saved
            
            Log::info('Session set for student', ['session_role' => $request->session()->get('user_role')]);
            
            app(DeviceBindingService::class)->bind($user, $request);
            if ($request->filled('qr_token')) {
                return redirect()->route('qr.scan', ['token' => $request->qr_token]);
            }
            return redirect()->intended('/home');
        }
    } else {
        // Email login for admin/teacher
        $credentials = ['email' => $identifier, 'password' => $password];
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            Log::info('Email login successful', [
                'user_id' => $user->id,
                'role' => $user->role,
                'email' => $user->email,
                'session_id' => $request->session()->getId()
            ]);
            
            // Set role-specific session
            $request->session()->regenerate();
            $request->session()->put('user_role', $user->role);
            $request->session()->put('login_timestamp', now()->toString());
            
            // Redirect based on role
            if ($user->isAdmin()) {
                Log::info('Admin login successful', ['user_id' => $user->id, 'session_id' => $request->session()->getId()]);
                
                // Bypass 2FA in local and testing environments
                if (app()->environment('local', 'testing')) {
                    $request->session()->put('admin_2fa_verified', true);
                    $request->session()->save(); // Force session save
                    
                    Log::info('Admin 2FA bypassed (local env)', [
                        'user_id' => $user->id,
                        'session_id' => $request->session()->getId(),
                        'session_verified' => $request->session()->get('admin_2fa_verified'),
                        'all_session_data' => $request->session()->all()
                    ]);
                    
                    return redirect()->route('admin.dashboard');
                }
                
                // Generate 2FA OTP for admin in production
                $otp = \App\Models\Otp::generate($user->id, 'admin_login');
                try {
                    \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OtpMail($otp->code, 'admin_login', $user->name));
                } catch (\Exception $e) {
                    Log::error('Failed to send admin 2FA OTP: ' . $e->getMessage());
                }
                return redirect()->route('admin.2fa.form')->with('info', 'Please check your email for the verification code.');
            } elseif ($user->isTeacher() || $user->isDepartmentHead()) {
                return redirect()->route('teacher.dashboard');
            } elseif ($user->isParent()) {
                return redirect()->route('parent.dashboard');
            } else {
                // Student logging in with email
                Auth::logout();
                $request->session()->flush();
                return back()->withInput($request->only('identifier'))
                    ->withErrors(['identifier' => 'Students must log in using their student number.']);
            }
        }
    }

        $response = back()->withInput($request->only('identifier'))
            ->withErrors(['identifier' => 'Incorrect ID/email or password.']);

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
        'profile_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    ]);

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
        }

    return back()->with('success', 'Image updated!');
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
