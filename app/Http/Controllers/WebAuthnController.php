<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WebauthnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebAuthnController extends Controller
{
    public function debug()
    {
        $auth_challenge = session('webauthn.auth_challenge');
        $register_challenge = session('webauthn.register_challenge');
        
        return response()->json([
            'auth_challenge' => $auth_challenge ? 'SET' : 'NULL',
            'register_challenge' => $register_challenge ? 'SET' : 'NULL',
            'session_id' => session()->getId(),
            'driver' => config('session.driver'),
        ]);
    }

    private function getRpId(Request $request): string
    {
        $host = trim((string) $request->header('x-forwarded-host', $request->getHost()));
        $host = preg_replace('/:\d+$/', '', $host);

        if ($host === '') {
            $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        }

        return strtolower($host);
    }

    public function registerOptions(Request $request, WebauthnService $webauthn)
    {
        $user = Auth::user();
        $options = $webauthn->registrationOptions($user);

        return response()->json($options['publicKey']);
    }

    public function register(Request $request, WebauthnService $webauthn)
    {
        $user = Auth::user();
        $request->validate([
            "credential_id" => "required|string",
            "credential" => "required|array",
            "device_name" => "nullable|string|max:100",
        ]);

        $normalizedCredentialId = rtrim(strtr($request->credential_id, '+/', '-_'), '=');

        $exists = DB::table("webauthn_credentials")
            ->where("user_id", $user->id)
            ->where(function ($query) use ($request, $normalizedCredentialId) {
                $query->where('credential_id', $request->credential_id)
                      ->orWhere('credential_id', $normalizedCredentialId);
            })
            ->exists();

        if ($exists) {
            return response()->json(["success" => false, "message" => "This device is already registered."]);
        }

        $credential = $request->input('credential');
        if (!is_array($credential)) {
            return response()->json(["success" => false, "message" => "Invalid credential data."], 422);
        }

        try {
            $stored = $webauthn->storeCredential($user, $credential);
            $stored->forceFill(['device_name' => $request->device_name ?? 'My Device'])->save();

            // Check if user already has recovery codes
            $hasRecoveryCodes = \App\Models\RecoveryCode::where('user_id', $user->id)->exists();
            $recoveryCodes = [];

            if (!$hasRecoveryCodes) {
                for ($i = 0; $i < 5; $i++) {
                    $rawCode = strtoupper(Str::random(4) . '-' . Str::random(4));
                    $recoveryCodes[] = $rawCode;
                    
                    \App\Models\RecoveryCode::create([
                        'user_id' => $user->id,
                        'code' => \Illuminate\Support\Facades\Hash::make($rawCode),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "message" => "Failed to store fingerprint: " . $e->getMessage()], 422);
        }

        return response()->json([
            "success" => true, 
            "message" => "Fingerprint registered successfully!",
            "recovery_codes" => $recoveryCodes
        ]);
    }

    public function loginOptions(Request $request, WebauthnService $webauthn)
    {
        $request->validate(["student_number" => "required|string"]);
        
        $identifier = $request->student_number;
        $user = User::where("student_number", $identifier)
            ->orWhere("email", $identifier)
            ->orWhere("employee_id", $identifier)
            ->first();

        if (!$user) return response()->json(["success" => false, "message" => "Account not found."], 404);
        
        $credentials = DB::table("webauthn_credentials")->where("user_id", $user->id)->exists();
        if (!$credentials) return response()->json(["success" => false, "message" => "No fingerprint registered for this account."], 404);
        
        session(["webauthn_login_user_id" => $user->id]);
        $options = $webauthn->authenticationOptions($user);
        
        return response()->json(array_merge($options['publicKey'], ["success" => true]));
    }

    public function login(Request $request, WebauthnService $webauthn)
    {
        $request->validate(["credential_id" => "required|string", "assertion" => "required|array"]);
        $userId = session("webauthn_login_user_id");
        
        \Illuminate\Support\Facades\Log::info('WebAuthn login attempt', [
            'user_id' => $userId,
            'session_id' => session()->getId(),
            'assertion_received' => !empty($request->assertion),
        ]);
        
        if (!$userId) {
            \Illuminate\Support\Facades\Log::error('WebAuthn login - no user_id in session', [
                'session_keys' => array_keys(session()->all()),
            ]);
            return response()->json(["success" => false, "message" => "Session expired."], 401);
        }
        
        $user = User::find($userId);
        if (!$user) return response()->json(["success" => false, "message" => "User not found."], 401);
        
        try {
            $webauthn->verifyAssertion($user, $request->assertion);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('WebAuthn verification failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(["success" => false, "message" => "WebAuthn verification failed: " . $e->getMessage()], 401);
        }
        
        Auth::login($user);
        $request->session()->regenerate();
        session()->forget(["webauthn_login_user_id"]);

        // Bind device on WebAuthn login too (same as password login)
        if ($user->isStudent()) {
            app(\App\Services\DeviceBindingService::class)->bind($user, $request);
        }
        
        return response()->json(["success" => true, "redirect" => $user->isAdmin() ? route("admin.dashboard") : route("home")]);
    }

    public function removeDevice(Request $request)
    {
        $request->validate(["credential_id" => "required|string"]);
        DB::table("webauthn_credentials")->where("user_id", Auth::id())->where("credential_id", $request->credential_id)->delete();
        return response()->json(["success" => true]);
    }

    public function devices()
    {
        return response()->json(DB::table("webauthn_credentials")->where("user_id", Auth::id())->select("credential_id", "name", "created_at")->get());
    }
}

