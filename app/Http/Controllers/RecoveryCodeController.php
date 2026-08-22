<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RecoveryCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RecoveryCodeController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'recovery_code' => 'required|string',
        ]);

        $identifier = trim($request->identifier);

        // Look up by student_number, email, or employee_id
        $user = User::where('student_number', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('employee_id', $identifier)
            ->orWhereRaw('LOWER(email) = ?', [strtolower($identifier)])
            ->orWhereRaw('LOWER(student_number) = ?', [strtolower($identifier)])
            ->first();

        if (!$user) {
            $clean = preg_replace('/[^a-zA-Z0-9]/', '', $identifier);
            if ($clean !== '') {
                $user = User::whereRaw("REPLACE(REPLACE(student_number, '-', ''), ' ', '') = ?", [$clean])
                    ->orWhereRaw("REPLACE(REPLACE(employee_id, '-', ''), ' ', '') = ?", [$clean])
                    ->first();
            }
        }

        if (!$user) {
            return back()->withErrors(['identifier' => 'Invalid credentials or recovery code.'])->withInput();
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
            return back()->withErrors(['identifier' => 'Invalid credentials or recovery code.'])->withInput();
        }

        // Mark code as used
        $validCode->update([
            'used' => true,
            'used_at' => now(),
        ]);

        // Log the user in
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('user_role', $user->role);
        $request->session()->put('login_timestamp', now()->toString());

        // Bind device on recovery login (same as password login)
        if ($user->isStudent()) {
            app(\App\Services\DeviceBindingService::class)->bind($user, $request);
        }

        // Redirect based on role
        if ($user->isAdmin()) return redirect()->route('admin.dashboard');
        if ($user->isTeacher() || $user->isDepartmentHead()) return redirect()->route('teacher.dashboard');
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
