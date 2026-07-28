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
        } elseif ($request->role === 'teacher') {
            $userData['employee_id'] = $request->employee_id;
            // Use custom department if "Other" was selected, otherwise use the selected department
            $userData['department'] = $request->department === 'Other' ? $request->custom_department : $request->department;
            $userData['position'] = $request->position;
            $userData['specialization'] = $request->specialization;
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

    // Auto-detect: if no '@' symbol, treat as student number
    if (!str_contains($identifier, '@')) {
        // Student login via student_number
        $credentials = ['student_number' => $identifier, 'password' => $password];
        if (Auth::attempt($credentials)) {
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
        if (Auth::attempt($credentials)) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Set role-specific session
            $request->session()->regenerate();
            $request->session()->put('user_role', $user->role);
            $request->session()->put('login_timestamp', now());
            $request->session()->save(); // Ensure session is saved
            
            // Redirect based on role
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->isTeacher()) {
                return redirect()->route('teacher.dashboard');
            } elseif ($user->isParent()) {
                return redirect()->route('parent.dashboard');
            } else {
                // Student trying to login with email
                Auth::logout();
                $request->session()->flush();
                return back()->withInput($request->only('identifier'))
                    ->withErrors(['identifier' => 'Students should use their Student ID number to sign in.']);
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

            $uploadedFileUrl = cloudinary()->upload($request->file('profile_image')->getRealPath())->getSecurePath();

            /** @var \App\Models\User $user */
            $user = auth()->user();
            $user->profile_image = $uploadedFileUrl;
            $user->save();
        }

    return back()->with('success', 'Image updated!');
}
    
    public function myClasses()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

    $year = (int) $user->year_level;
    $semester = (int) $user->semester;

    $subjects = Subject::where('year_level', $year)
        ->where('semester', $semester)
        ->with('schedules')
        ->get();

    return view('student.classes', compact('subjects'));
}

    public function myClassesPdf()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

    $subjects = Subject::where('year_level', (int) $user->year_level)
        ->where('semester', (int) $user->semester)
        ->with('schedules')
        ->orderBy('code')
        ->get();

    $pdf = Pdf::loadView('student.classes-pdf', compact('user', 'subjects'))
        ->setPaper('a4', 'landscape');

    return $pdf->download('my-class-schedule.pdf');
}
}
