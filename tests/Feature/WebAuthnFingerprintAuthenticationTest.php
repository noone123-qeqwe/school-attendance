<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\WebauthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WebAuthnFingerprintAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_with_registered_fingerprint_gets_login_options_by_student_number()
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2024-0001',
            'email' => 'student@school.edu',
        ]);

        WebauthnCredential::create([
            'user_id' => $student->id,
            'credential_id' => 'mock_credential_id_123',
            'public_key' => 'mock_public_key',
            'sign_count' => 0,
            'device_name' => 'Pixel 8',
        ]);

        $response = $this->postJson(route('webauthn.login.options'), [
            'student_number' => '2024-0001',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['challenge', 'allowCredentials', 'success']);
        $this->assertTrue($response->json('success'));
    }

    public function test_student_gets_login_options_by_email_case_insensitively_with_whitespace()
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2024-0002',
            'email' => 'student.two@school.edu',
        ]);

        WebauthnCredential::create([
            'user_id' => $student->id,
            'credential_id' => 'mock_credential_id_456',
            'public_key' => 'mock_public_key',
            'sign_count' => 0,
            'device_name' => 'iPhone 15',
        ]);

        $response = $this->postJson(route('webauthn.login.options'), [
            'identifier' => '  STUDENT.TWO@SCHOOL.EDU  ',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }

    public function test_teacher_gets_login_options_by_employee_id()
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'employee_id' => 'TCH-999',
            'email' => 'teacher@school.edu',
        ]);

        WebauthnCredential::create([
            'user_id' => $teacher->id,
            'credential_id' => 'mock_teacher_cred',
            'public_key' => 'mock_public_key',
            'sign_count' => 0,
            'device_name' => 'MacBook Pro',
        ]);

        $response = $this->postJson(route('webauthn.login.options'), [
            'student_number' => 'TCH-999',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }

    public function test_unregistered_account_returns_no_fingerprint_registered_message()
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2024-0003',
            'email' => 'nofingerprint@school.edu',
        ]);

        $response = $this->postJson(route('webauthn.login.options'), [
            'student_number' => '2024-0003',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'No biometric credentials registered for this account.',
        ]);
    }

    public function test_non_existent_account_returns_404_account_not_found()
    {
        $response = $this->postJson(route('webauthn.login.options'), [
            'student_number' => 'NONEXISTENT-99999',
        ]);

        $response->assertStatus(404);
        $this->assertFalse($response->json('success'));
    }

    public function test_authenticated_user_can_list_and_remove_devices()
    {
        $student = User::factory()->create(['role' => 'student']);

        $cred = WebauthnCredential::create([
            'user_id' => $student->id,
            'credential_id' => 'device_to_remove_123',
            'public_key' => 'mock_public_key',
            'sign_count' => 0,
            'device_name' => 'My Phone',
        ]);

        $this->actingAs($student);

        $devicesRes = $this->getJson(route('webauthn.devices'));
        $devicesRes->assertOk();
        $devicesRes->assertJsonFragment(['credential_id' => 'device_to_remove_123', 'name' => 'My Phone']);

        $deleteRes = $this->deleteJson(route('webauthn.remove'), [
            'credential_id' => 'device_to_remove_123',
        ]);
        $deleteRes->assertOk();
        $this->assertTrue($deleteRes->json('success'));

        $this->assertDatabaseMissing('webauthn_credentials', [
            'credential_id' => 'device_to_remove_123',
        ]);
    }

    public function test_successful_fingerprint_login_redirects_to_correct_role_dashboard()
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'employee_id' => 'TCH-100',
            'email' => 'prof@school.edu',
        ]);

        $cred = WebauthnCredential::create([
            'user_id' => $teacher->id,
            'credential_id' => 'teacher_login_cred',
            'public_key' => 'mock_public_key',
            'sign_count' => 0,
            'device_name' => 'Teacher Laptop',
        ]);

        // Mock WebauthnService assertion verification
        $mockService = Mockery::mock(WebauthnService::class);
        $mockService->shouldReceive('verifyAssertion')->once()->andReturn($cred);
        $this->app->instance(WebauthnService::class, $mockService);

        // Put user id in session as loginOptions would do
        session(['webauthn_login_user_id' => $teacher->id]);

        $response = $this->postJson(route('webauthn.login'), [
            'credential_id' => 'teacher_login_cred',
            'assertion' => [
                'id' => 'teacher_login_cred',
                'response' => [
                    'clientDataJSON' => 'mock_json',
                    'authenticatorData' => 'mock_auth',
                    'signature' => 'mock_sig',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'redirect' => route('teacher.dashboard'),
        ]);

        $this->assertAuthenticatedAs($teacher);
    }

    public function test_student_with_leading_zero_student_number_gets_login_options()
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => '0703250',
            'email' => 'ibn@school.edu',
        ]);

        WebauthnCredential::create([
            'user_id' => $student->id,
            'credential_id' => 'mock_student_0703250_cred',
            'public_key' => 'mock_public_key',
            'sign_count' => 0,
            'device_name' => 'Redmi Note 12',
        ]);

        $response = $this->postJson(route('webauthn.login.options'), [
            'student_number' => '0703250',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }

    public function test_student_can_login_with_student_number_and_bind_device()
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => '0703250',
            'email' => 'ibnkervijamatos44@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login.submit'), [
            'identifier' => '0703250',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($student);

        // Verify device binding record exists
        $this->assertDatabaseHas('device_bindings', [
            'user_id' => $student->id,
        ]);
    }

    public function test_student_can_login_with_email_and_bind_device()
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => '0703251',
            'email' => 'student_browser_switch@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login.submit'), [
            'identifier' => 'student_browser_switch@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($student);

        // Verify device binding record exists
        $this->assertDatabaseHas('device_bindings', [
            'user_id' => $student->id,
        ]);
    }

    public function test_student_switching_browsers_can_login_and_rebind_device()
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => '0703252',
            'email' => 'switch_test@gmail.com',
            'password' => bcrypt('password123'),
        ]);

        // Login from Browser 1 (Chrome)
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 Chrome/120.0.0.0'])
            ->post(route('login.submit'), [
                'identifier' => '0703252',
                'password' => 'password123',
            ])->assertRedirect('/home');

        $this->assertDatabaseHas('device_bindings', [
            'user_id' => $student->id,
        ]);

        // Login from Browser 2 (Safari/Edge/Firefox)
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 Version/17.0 Safari/605.1.15'])
            ->post(route('login.submit'), [
                'identifier' => 'switch_test@gmail.com',
                'password' => 'password123',
            ])->assertRedirect('/home');

        $this->assertAuthenticatedAs($student);
        $this->assertDatabaseHas('device_bindings', [
            'user_id' => $student->id,
        ]);
    }
}

