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
        
        // Accept both nested credential format and flattened credential payload
        $credentialId = $request->input('credential_id') ?? $request->input('id') ?? $request->input('rawId');
        $credential = $request->input('credential') ?? [
            'id' => $request->input('id') ?? $credentialId,
            'type' => $request->input('type') ?? 'public-key',
            'response' => $request->input('response') ?? [],
        ];
        $deviceName = $request->input('device_name') ?? 'My Device';

        if (!$credentialId || !is_string($credentialId)) {
            return response()->json(["success" => false, "message" => "Credential ID is required."], 422);
        }

        if (!is_array($credential) || empty($credential['response'])) {
            return response()->json(["success" => false, "message" => "Invalid credential data."], 422);
        }

        $normalizedCredentialId = rtrim(strtr($credentialId, '+/', '-_'), '=');

        $exists = DB::table("webauthn_credentials")
            ->where("user_id", $user->id)
            ->where(function ($query) use ($credentialId, $normalizedCredentialId) {
                $query->where('credential_id', $credentialId)
                      ->orWhere('credential_id', $normalizedCredentialId);
            })
            ->exists();

        if ($exists) {
            return response()->json(["success" => false, "message" => "This device is already registered."]);
        }

        try {
            $stored = $webauthn->storeCredential($user, $credential);
            $stored->forceFill(['device_name' => $deviceName])->save();

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
        $raw = $request->input('student_number') ?? $request->input('identifier') ?? $request->input('email');
        if (!$raw || !is_string($raw)) {
            return response()->json(["success" => false, "message" => "Please enter your Student ID or Email."], 422);
        }

        $identifier = trim($raw);
        $user = $this->findUserByIdentifier($identifier);

        if (!$user) {
            return response()->json(["success" => false, "message" => "Account not found for \"{$identifier}\"."], 404);
        }
        
        $credentials = $user->webauthnCredentials()->exists() || DB::table("webauthn_credentials")->where("user_id", $user->id)->exists();
        if (!$credentials) {
            return response()->json(["success" => false, "message" => "No biometric credentials registered for this account."], 404);
        }
        
        session(["webauthn_login_user_id" => $user->id]);
        $options = $webauthn->authenticationOptions($user);
        
        return response()->json(array_merge($options['publicKey'], ["success" => true]));
    }

    /**
     * Helper method to find user by various identifier formats
     */
    private function findUserByIdentifier(string $identifier): ?User
    {
        $user = User::where("student_number", $identifier)
            ->orWhere("email", $identifier)
            ->orWhere("employee_id", $identifier)
            ->orWhereRaw("LOWER(email) = ?", [strtolower($identifier)])
            ->orWhereRaw("LOWER(student_number) = ?", [strtolower($identifier)])
            ->orWhereRaw("LOWER(employee_id) = ?", [strtolower($identifier)])
            ->first();

        // Also check if hyphens/spaces were omitted or added
        if (!$user) {
            $clean = preg_replace('/[^a-zA-Z0-9]/', '', $identifier);
            if ($clean !== '') {
                $user = User::whereRaw("REPLACE(REPLACE(student_number, '-', ''), ' ', '') = ?", [$clean])
                    ->orWhereRaw("REPLACE(REPLACE(employee_id, '-', ''), ' ', '') = ?", [$clean])
                    ->first();
            }
        }

        return $user;
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
        
        Auth::login($user, true);
        $request->session()->regenerate();
        session()->forget(["webauthn_login_user_id"]);

        // Bind device on WebAuthn login too (same as password login)
        if ($user->isStudent()) {
            app(\App\Services\DeviceBindingService::class)->bind($user, $request);
        }
        
        $redirectUrl = route('home');
        if ($user->isAdmin()) {
            $redirectUrl = route('admin.dashboard');
        } elseif ($user->isTeacher()) {
            $redirectUrl = route('teacher.dashboard');
        } elseif ($user->isParent()) {
            $redirectUrl = route('parent.dashboard');
        }

        return response()->json(["success" => true, "redirect" => $redirectUrl]);
    }

    public function removeDevice(Request $request)
    {
        $request->validate(["credential_id" => "required|string"]);
        DB::table("webauthn_credentials")->where("user_id", Auth::id())->where("credential_id", $request->credential_id)->delete();
        return response()->json(["success" => true]);
    }

    public function devices()
    {
        $devices = DB::table("webauthn_credentials")
            ->where("user_id", Auth::id())
            ->select(["credential_id", "device_name", "created_at"])
            ->get()
            ->map(function ($d) {
                return [
                    'credential_id' => $d->credential_id,
                    'name' => $d->device_name ?? 'My Device',
                    'device_name' => $d->device_name ?? 'My Device',
                    'created_at' => $d->created_at,
                ];
            });

        return response()->json($devices);
    }
}

