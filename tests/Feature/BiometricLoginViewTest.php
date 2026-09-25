<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiometricLoginViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_fingerprint_enrollment_uses_only_the_local_authenticator_on_every_page()
    {
        $views = [
            'auth/login.blade.php',
            'settings.blade.php',
            'student/profile.blade.php',
            'teacher/profile.blade.php',
            'admin/profile.blade.php',
            'parent/profile.blade.php',
        ];

        foreach ($views as $view) {
            $source = file_get_contents(resource_path('views/' . $view));
            $this->assertStringContainsString("authenticatorAttachment: 'platform'", $source, $view);
            $this->assertStringContainsString("hints: ['client-device']", $source, $view);
            $this->assertStringContainsString("userVerification: 'required'", $source, $view);
            $this->assertStringNotContainsString('retry without authenticatorAttachment', $source, $view);
            $this->assertStringNotContainsString('retry without platform restriction', $source, $view);
            $this->assertStringNotContainsString('retrying with flexible authenticator selection', $source, $view);
        }
    }

    public function test_fingerprint_sign_in_does_not_retry_with_another_device_or_old_challenge()
    {
        $source = file_get_contents(resource_path('views/auth/login.blade.php'));
        $start = strpos($source, 'async function performBiometricLogin(');
        $end = strpos($source, 'function setupBiometricListeners(', $start);
        $signIn = substr($source, $start, $end - $start);

        $this->assertStringNotContainsString('cachedOpts', $signIn);
        $this->assertStringContainsString("selectedMethod.id === 'fingerprint' ? ['internal']", $signIn);
        $this->assertStringContainsString("selectedMethod.id !== 'fingerprint' && getPublicKey.allowCredentials", $signIn);
        $this->assertStringContainsString("firstErr.name === 'NotAllowedError'", $signIn);
        $this->assertStringContainsString("getPublicKey.hints = ['client-device']", $signIn);
    }

    public function test_qr_attendance_fingerprint_stays_on_student_device()
    {
        $source = file_get_contents(resource_path('views/qr/verify.blade.php'));

        $this->assertStringContainsString("transports: ['internal']", $source);
        $this->assertStringContainsString("userVerification: 'required'", $source);
        $this->assertStringContainsString("hints: ['client-device']", $source);
    }

    public function test_webauthn_options_require_device_verification()
    {
        $user = User::factory()->create();
        $service = app(\App\Services\WebauthnService::class);

        $this->assertSame('required', $service->registrationOptions($user)['publicKey']['authenticatorSelection']['userVerification']);
        $this->assertSame('required', $service->authenticationOptions($user)['publicKey']['userVerification']);
    }

    public function test_webauthn_rejects_assertion_without_verified_user_flag()
    {
        $user = User::factory()->create();
        $credential = \App\Models\WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'unverified-credential',
            'public_key' => 'not-needed-before-uv-check',
            'sign_count' => 0,
            'device_name' => 'Test device',
        ]);
        $service = app(\App\Services\WebauthnService::class);
        $challenge = $service->authenticationOptions($user)['publicKey']['challenge'];
        $clientData = json_encode([
            'type' => 'webauthn.get',
            'challenge' => $challenge,
            'origin' => 'http://localhost',
        ]);
        $authenticatorData = hash('sha256', 'localhost', true) . chr(0x01) . pack('N', 0);
        $encode = static fn (string $value) => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Device verification was not completed');
        $service->verifyAssertion($user, [
            'id' => $credential->credential_id,
            'response' => [
                'clientDataJSON' => $encode($clientData),
                'authenticatorData' => $encode($authenticatorData),
                'signature' => $encode('invalid'),
            ],
        ], $credential);
    }

    public function test_login_page_renders_biometric_button_and_elements_without_inline_onclick()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('id="fpRowBtn"', false);
        $response->assertSee('Sign in with Biometrics', false);
        $response->assertSee('id="biometricModal"', false);

        // Crucial CSP check: Ensure inline onclick handlers are not used on biometric buttons
        $content = $response->getContent();
        $this->assertStringNotContainsString('id="fpRowBtn" onclick=', $content);
        $this->assertStringNotContainsString('onclick="handleBiometricLogin()', $content);
        $this->assertStringNotContainsString('id="bioModalPrimaryBtn" onclick=', $content);
        $this->assertStringNotContainsString('id="bioModalSecondaryBtn" onclick=', $content);
        $this->assertStringNotContainsString('id="bioModalCloseBtn" onclick=', $content);
        $this->assertStringNotContainsString('id="bioModalBackdrop" onclick=', $content);

        // Ensure proper unobtrusive event listeners exist
        $response->assertSee('setupBiometricListeners', false);
        $response->assertSee('addEventListener(\'click\'', false);
    }

    public function test_login_page_contains_required_biometric_not_registered_message()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Biometric login is not registered for this account. Please use your password or register your biometrics first.', false);
    }

    public function test_login_page_contains_biometric_cancellation_and_error_handling()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Biometric authentication was cancelled or timed out. Please try again or use your password.', false);
        $response->assertSee('AUTHENTICATION CANCELLED', false);
        $response->assertSee('STUDENT ID OR EMAIL REQUIRED', false);
    }

    public function test_biometric_options_error_handler_is_valid_javascript()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Keep the error branches inside the failed-options condition. An extra
        // closing brace before this `else if` makes the whole login script fail
        // to parse, so the biometric button cannot respond to taps at all.
        $this->assertStringContainsString(
            "if (opts.code === 'NOT_REGISTERED' || (optRes.status === 404 && opts.user_exists)) {",
            $content
        );
        $this->assertStringContainsString(
            "} else if (opts.code === 'ACCOUNT_NOT_FOUND' || optRes.status === 404) {",
            $content
        );
        $this->assertStringNotContainsString(
            "            }\n            } else if (opts.code === 'ACCOUNT_NOT_FOUND'",
            $content
        );
    }

    public function test_webauthn_login_options_returns_not_registered_for_user_without_biometrics()
    {
        $user = User::factory()->create([
            'student_number' => '2024-99999',
            'email' => 'student99999@test.com',
            'role' => 'student',
            'is_active' => true,
        ]);

        $response = $this->postJson(route('webauthn.login.options'), [
            'student_number' => '2024-99999',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'code' => 'NOT_REGISTERED',
            'user_exists' => true,
        ]);
    }

    public function test_login_page_has_no_premature_script_close_and_all_biometric_scripts_are_inside_script_tags()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Count opening and closing script tags
        $openCount = substr_count($content, '<script');
        $closeCount = substr_count($content, '</script>');

        $this->assertEquals($openCount, $closeCount, "Script tags must be balanced. Opening: {$openCount}, Closing: {$closeCount}");

        // Ensure biometric functions are inside a script block before the closing </script>
        $lastScriptClose = strrpos($content, '</script>');
        $handlePos = strpos($content, 'function handleBiometricLogin(');
        $performPos = strpos($content, 'function performBiometricLogin(');
        $setupPos = strpos($content, 'function setupBiometricListeners(');

        $this->assertNotFalse($handlePos, 'handleBiometricLogin must be present in response');
        $this->assertNotFalse($performPos, 'performBiometricLogin must be present in response');
        $this->assertNotFalse($setupPos, 'setupBiometricListeners must be present in response');

        $this->assertLessThan($lastScriptClose, $handlePos, 'handleBiometricLogin must be inside a <script> block');
        $this->assertLessThan($lastScriptClose, $performPos, 'performBiometricLogin must be inside a <script> block');
        $this->assertLessThan($lastScriptClose, $setupPos, 'setupBiometricListeners must be inside a <script> block');
    }

    public function test_webauthn_login_options_supports_discoverable_passkey_when_no_identifier_provided()
    {
        $response = $this->postJson(route('webauthn.login.options'), []);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'discoverable' => true,
        ]);
        $this->assertNotEmpty($response->json('challenge'));
    }

    public function test_webauthn_login_options_returns_credentials_when_user_has_biometrics_registered()
    {
        $user = User::factory()->create([
            'student_number' => '2024-88888',
            'email' => 'student88888@test.com',
            'role' => 'student',
            'is_active' => true,
        ]);

        \App\Models\WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'test_cred_id_12345',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAz\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
            'device_name' => 'Pixel Fingerprint',
            'biometric_type' => 'fingerprint',
            'last_used_at' => now(),
        ]);

        $response = $this->postJson(route('webauthn.login.options'), [
            'student_number' => '2024-88888',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'user_id' => $user->id,
            'identifier' => '2024-88888',
        ]);
        $this->assertNotEmpty($response->json('challenge'));
        $this->assertNotEmpty($response->json('allowCredentials'));
    }

    public function test_login_page_scripts_have_valid_javascript_syntax()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $content = $response->getContent();

        preg_match_all('/<script[\s\S]*?<\/script>/i', $content, $matches);
        $this->assertNotEmpty($matches[0], 'Login page must contain script tags.');

        foreach ($matches[0] as $index => $scriptTag) {
            $code = preg_replace('/^<script[^>]*>/i', '', $scriptTag);
            $code = preg_replace('/<\/script>$/i', '', $code);

            // Clean up any remaining blade expressions for parser check
            $lines = explode("\n", $code);
            foreach ($lines as $i => $line) {
                if (str_starts_with(trim($line), '@')) {
                    $lines[$i] = '// ' . $line;
                }
            }
            $cleanCode = implode("\n", $lines);

            // Check syntax with node -e if node is available
            $tmpFile = tempnam(sys_get_temp_dir(), 'login_script_') . '.js';
            file_put_contents($tmpFile, $cleanCode);

            $command = 'node --check ' . escapeshellarg($tmpFile) . ' 2>&1';
            exec($command, $output, $returnCode);
            @unlink($tmpFile);

            $this->assertSame(0, $returnCode, "Script index {$index} contains syntax error: " . implode("\n", $output));
        }
    }

    public function test_biometric_row_has_responsive_styles_and_attributes()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('aria-label="Sign in with Biometrics"', $content);
        $this->assertStringContainsString('.fp-row * { pointer-events: none; }', $content);
        $this->assertStringContainsString('touch-action: manipulation;', $content);
        $this->assertStringContainsString('@media (hover: hover) and (pointer: fine)', $content);
    }

    public function test_login_page_renders_biometric_selection_prompt_elements()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $content = $response->getContent();

        // Biometric method selection modal DOM elements
        $this->assertStringContainsString('id="bioModalMethodsWrap"', $content);
        $this->assertStringContainsString('id="bioModalMethodsList"', $content);
        $this->assertStringContainsString('id="bioModalChooseMethodBtn"', $content);
        $this->assertStringContainsString('class="bio-method-list"', $content);

        // Crucial CSP check: Choose method button must not use inline onclick
        $this->assertStringNotContainsString('id="bioModalChooseMethodBtn" onclick=', $content);

        // Core JS functions for dynamic biometric detection and prompt selection
        $this->assertStringContainsString('function getDeviceBiometricCapabilities(', $content);
        $this->assertStringContainsString('function filterAvailableBiometricMethods(', $content);
        $this->assertStringContainsString('function openBiometricSelectionPrompt(', $content);
        $this->assertStringContainsString('function handleSelectBiometricMethod(', $content);
    }

    public function test_webauthn_login_options_returns_available_methods_for_targeted_user()
    {
        $user = User::factory()->create([
            'student_number' => '2024-77777',
            'email' => 'student77777@test.com',
            'role' => 'student',
            'is_active' => true,
        ]);

        \App\Models\WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'test_cred_id_77777',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAz\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
            'device_name' => 'Fingerprint Sensor',
            'biometric_type' => 'fingerprint',
            'last_used_at' => now(),
        ]);

        $response = $this->postJson(route('webauthn.login.options'), [
            'student_number' => '2024-77777',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertIsArray($response->json('available_methods'));
        $this->assertContains('fingerprint', $response->json('available_methods'));
        $this->assertContains('device_lock', $response->json('available_methods'));
    }

    public function test_webauthn_login_options_returns_available_methods_in_discoverable_mode()
    {
        $response = $this->postJson(route('webauthn.login.options'), []);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'discoverable' => true,
        ]);
        $this->assertIsArray($response->json('available_methods'));
        $this->assertNotEmpty($response->json('available_methods'));
    }

    public function test_webauthn_available_methods_endpoint_returns_enrolled_methods()
    {
        $user = User::factory()->create([
            'student_number' => '2024-66666',
            'email' => 'student66666@test.com',
            'role' => 'student',
            'is_active' => true,
        ]);

        \App\Models\WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'test_cred_id_66666_fp',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAz\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
            'device_name' => 'Fingerprint Reader',
            'biometric_type' => 'fingerprint',
            'last_used_at' => now(),
        ]);

        \App\Models\WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'test_cred_id_66666_face',
            'public_key' => 'face_desc_95_mockdata',
            'sign_count' => 0,
            'device_name' => 'Front Camera Face',
            'biometric_type' => 'face',
            'last_used_at' => now(),
        ]);

        // Query by student number
        $response = $this->postJson(route('webauthn.available.methods'), [
            'identifier' => '2024-66666',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'identifier' => '2024-66666',
        ]);
        $methods = $response->json('available_methods');
        $this->assertContains('fingerprint', $methods);
        $this->assertContains('face', $methods);
        $this->assertContains('device_lock', $methods);
    }
}
