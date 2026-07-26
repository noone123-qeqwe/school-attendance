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
            'student_number' => 'required|string',
            'recovery_code' => 'required|string',
        ]);

        $user = User::where('student_number', $request->student_number)->first();

        if (!$user) {
            return back()->withErrors(['student_number' => 'Student not found.'])->withInput();
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
            return back()->withErrors(['recovery_code' => 'Invalid or already used recovery code.'])->withInput();
        }

        // Mark code as used
        $validCode->update([
            'used' => true,
            'used_at' => now(),
        ]);

        // Log the user in
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to($user->isAdmin() ? route('admin.dashboard') : route('home'));
    }
}
