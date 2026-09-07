<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiometricLoginViewTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_webauthn_login_options_returns_account_not_found_when_user_does_not_exist()
    {
        $response = $this->postJson(route('webauthn.login.options'), [
            'student_number' => 'non_existent_user_12345',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'code' => 'ACCOUNT_NOT_FOUND',
        ]);
    }
}
