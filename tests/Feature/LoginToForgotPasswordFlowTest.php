<?php

namespace Tests\Feature;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginToForgotPasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
    }

    public function test_login_page_renders_forgot_password_link_with_id_input(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('id="idInput"', false);
        $response->assertSee('id="forgotPasswordLink"', false);
        $response->assertSee('updateForgotHref', false);
    }

    public function test_student_id_from_login_shows_account_found_and_masked_email(): void
    {
        $user = User::factory()->create([
            'name'           => 'Juan Dela Cruz',
            'student_number' => '20260001',
            'email'          => 'juandelacruz@gmail.com',
        ]);

        $response = $this->get('/forgot-password?identifier=20260001');

        $response->assertStatus(200);
        $response->assertSee('Account Found');
        $response->assertSee('We found your account.');
        $response->assertSee('Juan Dela Cruz');
        $response->assertSee('Student ID');
        $response->assertSee('20260001');
        $response->assertSee('j*****@gmail.com');
        $response->assertSee('Send OTP');
        $response->assertSee('<input type="hidden" name="identifier" value="20260001">', false);
    }

    public function test_email_from_login_shows_account_found_and_masked_email(): void
    {
        $user = User::factory()->create([
            'name'           => 'Maria Clara',
            'student_number' => '20260002',
            'email'          => 'mariaclara@gmail.com',
        ]);

        $response = $this->get('/forgot-password?identifier=mariaclara@gmail.com');

        $response->assertStatus(200);
        $response->assertSee('Account Found');
        $response->assertSee('Maria Clara');
        $response->assertSee('m*****@gmail.com');
        $response->assertSee('Send OTP');
    }

    public function test_submitting_student_id_sends_otp_to_registered_email_and_redirects_to_otp_screen(): void
    {
        $user = User::factory()->create([
            'student_number' => '20260001',
            'email'          => 'student1@gmail.com',
        ]);

        $response = $this->post('/forgot-password', [
            'identifier' => '20260001',
        ]);

        $response->assertRedirect(route('otp.verify.form', ['purpose' => 'forgot_password']));
        $response->assertSessionHas('account_verified', true);
        $response->assertSessionHas('otp_user_id', $user->id);
        $response->assertSessionHas('otp_email', 'student1@gmail.com');

        $otp = Otp::where('user_id', $user->id)->where('purpose', 'forgot_password')->latest()->first();
        $this->assertNotNull($otp);
        $this->assertEquals('student1@gmail.com', $otp->email);
        $this->assertEquals(6, strlen($otp->code));

        // Follow redirect to verify-otp and assert masked email is displayed
        $verifyRes = $this->get(route('otp.verify.form', ['purpose' => 'forgot_password']));
        $verifyRes->assertStatus(200);
        $verifyRes->assertSee('s*****@gmail.com');
        $verifyRes->assertSee('Enter the code sent to your registered email.');
    }

    public function test_submitting_email_sends_otp_to_that_email_and_redirects_to_otp_screen(): void
    {
        $user = User::factory()->create([
            'student_number' => '20260002',
            'email'          => 'student2@gmail.com',
        ]);

        $response = $this->post('/forgot-password', [
            'identifier' => 'student2@gmail.com',
        ]);

        $response->assertRedirect(route('otp.verify.form', ['purpose' => 'forgot_password']));
        $response->assertSessionHas('account_verified', true);
        $response->assertSessionHas('otp_user_id', $user->id);
        $response->assertSessionHas('otp_email', 'student2@gmail.com');

        $otp = Otp::where('user_id', $user->id)->where('purpose', 'forgot_password')->latest()->first();
        $this->assertNotNull($otp);
        $this->assertEquals('student2@gmail.com', $otp->email);
    }

    public function test_empty_login_field_shows_find_your_account_fallback(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Find Your Account');
        $response->assertSee('Enter your Student ID or registered email to continue.');
        $response->assertSee('name="identifier"', false);
        $response->assertSee('Continue');
    }

    public function test_empty_login_submission_fails_validation(): void
    {
        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'identifier' => '',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors('identifier');

        $this->assertEquals(0, Otp::where('purpose', 'forgot_password')->count());
    }

    public function test_unknown_student_id_or_email_shows_safe_error(): void
    {
        // GET with non-existent ID shows safe error message
        $getRes = $this->get('/forgot-password?identifier=99999999');
        $getRes->assertStatus(200);
        $getRes->assertSee('Unable to verify the account. Please check your Student ID or email address.');

        // POST with non-existent ID rejects with safe error
        $postRes = $this->from('/forgot-password')->post('/forgot-password', [
            'identifier' => '99999999',
        ]);

        $postRes->assertRedirect('/forgot-password');
        $postRes->assertSessionHasErrors('identifier');
        $this->assertEquals(0, Otp::where('purpose', 'forgot_password')->count());
    }

    public function test_multiple_students_recover_passwords_independently_with_correct_emails(): void
    {
        $studentA = User::factory()->create([
            'student_number' => '20260001',
            'email'          => 'student1@school.test',
        ]);

        $studentB = User::factory()->create([
            'student_number' => '20260002',
            'email'          => 'student2@school.test',
        ]);

        // Student A requests recovery using Student ID
        $this->post('/forgot-password', ['identifier' => '20260001']);
        $otpA = Otp::where('user_id', $studentA->id)->where('purpose', 'forgot_password')->latest()->first();
        $this->assertNotNull($otpA);
        $this->assertEquals('student1@school.test', $otpA->email);

        // Student B requests recovery using Email
        $this->post('/forgot-password', ['identifier' => 'student2@school.test']);
        $otpB = Otp::where('user_id', $studentB->id)->where('purpose', 'forgot_password')->latest()->first();
        $this->assertNotNull($otpB);
        $this->assertEquals('student2@school.test', $otpB->email);

        // Ensure distinct codes and correct destinations
        $this->assertNotEquals($otpA->id, $otpB->id);
        $this->assertNotEquals($otpA->email, $otpB->email);
    }

    public function test_complete_recovery_and_login_flow_with_student_id(): void
    {
        $user = User::factory()->create([
            'student_number' => '20260010',
            'email'          => 'student10@school.test',
            'password'       => Hash::make('OldPassword123!'),
            'is_active'      => true,
        ]);

        // Step 1: Start Forgot Password with Student ID
        $forgotRes = $this->post('/forgot-password', [
            'identifier' => '20260010',
        ]);
        $forgotRes->assertRedirect(route('otp.verify.form', ['purpose' => 'forgot_password']));

        $otp = Otp::where('user_id', $user->id)->where('purpose', 'forgot_password')->latest()->first();
        $this->assertNotNull($otp);

        // Step 2: Verify OTP
        $verifyRes = $this->post('/verify-otp', [
            'purpose'    => 'forgot_password',
            'identifier' => $user->email,
            'otp'        => $otp->code,
        ]);
        $verifyRes->assertRedirect(route('otp.reset.form'));

        // Step 3: Reset Password
        $resetToken = session('password_reset_token');
        $this->assertNotEmpty($resetToken);

        $resetRes = $this->post('/reset-password', [
            'token'                 => $resetToken,
            'password'              => 'NewSecurePass2026!',
            'password_confirmation' => 'NewSecurePass2026!',
        ]);
        $resetRes->assertRedirect(route('login'));

        // Step 4: Login with Student ID and the New Password
        $loginRes = $this->post('/login', [
            'identifier' => '20260010',
            'password'   => 'NewSecurePass2026!',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_complete_recovery_and_login_flow_with_email(): void
    {
        $user = User::factory()->create([
            'student_number' => '20260020',
            'email'          => 'student20@school.test',
            'password'       => Hash::make('OldPassword123!'),
            'is_active'      => true,
        ]);

        // Step 1: Start Forgot Password with Email
        $forgotRes = $this->post('/forgot-password', [
            'identifier' => 'student20@school.test',
        ]);
        $forgotRes->assertRedirect(route('otp.verify.form', ['purpose' => 'forgot_password']));

        $otp = Otp::where('user_id', $user->id)->where('purpose', 'forgot_password')->latest()->first();
        $this->assertNotNull($otp);

        // Step 2: Verify OTP
        $verifyRes = $this->post('/verify-otp', [
            'purpose'    => 'forgot_password',
            'identifier' => $user->email,
            'otp'        => $otp->code,
        ]);
        $verifyRes->assertRedirect(route('otp.reset.form'));

        // Step 3: Reset Password
        $resetToken = session('password_reset_token');
        $resetRes = $this->post('/reset-password', [
            'token'                 => $resetToken,
            'password'              => 'AnotherNewSecurePass2026!',
            'password_confirmation' => 'AnotherNewSecurePass2026!',
        ]);
        $resetRes->assertRedirect(route('login'));

        // Step 4: Login with Email and New Password
        $loginRes = $this->post('/login', [
            'identifier' => 'student20@school.test',
            'password'   => 'AnotherNewSecurePass2026!',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_teacher_with_employee_id_shows_account_found_and_employee_id_badge(): void
    {
        $teacher = User::factory()->teacher()->create([
            'name'        => 'Prof. John Smith',
            'employee_id' => 'EMP-2026',
            'email'       => 'teacher@school.test',
        ]);

        $response = $this->get('/forgot-password?identifier=EMP-2026');
        $response->assertStatus(200);
        $response->assertSee('Account Found');
        $response->assertSee('Prof. John Smith');
        $response->assertSee('Employee ID');
        $response->assertSee('EMP-2026');
        $response->assertSee('t*****@school.test');
    }

    public function test_deactivated_account_recovery_is_safely_blocked(): void
    {
        $user = User::factory()->create([
            'student_number' => '20269991',
            'email'          => 'deactivated@school.test',
            'is_active'      => false,
        ]);

        // GET shows safe error
        $getRes = $this->get('/forgot-password?identifier=20269991');
        $getRes->assertStatus(200);
        $getRes->assertSee('Unable to verify the account. Please check your Student ID or email address.');

        // POST rejects recovery
        $postRes = $this->from('/forgot-password')->post('/forgot-password', [
            'identifier' => '20269991',
        ]);
        $postRes->assertRedirect('/forgot-password');
        $postRes->assertSessionHasErrors('identifier');
        $this->assertEquals(0, Otp::where('user_id', $user->id)->count());
    }

    public function test_account_with_invalid_or_missing_email_safely_fails(): void
    {
        $user = User::factory()->create([
            'student_number' => '20269992',
            'email'          => '',
        ]);

        $getRes = $this->get('/forgot-password?identifier=20269992');
        $getRes->assertStatus(200);
        $getRes->assertSee('Unable to verify the account. Please check your Student ID or email address.');

        $postRes = $this->from('/forgot-password')->post('/forgot-password', [
            'identifier' => '20269992',
        ]);
        $postRes->assertRedirect('/forgot-password');
        $postRes->assertSessionHasErrors('identifier');
        $this->assertEquals(0, Otp::where('user_id', $user->id)->count());
    }

    public function test_resend_otp_invalidates_previous_otp_and_preserves_verified_email(): void
    {
        $user = User::factory()->create([
            'student_number' => '20260055',
            'email'          => 'student55@school.test',
        ]);

        // Initial request
        $this->post('/forgot-password', ['identifier' => '20260055']);
        $firstOtp = Otp::where('user_id', $user->id)->where('purpose', 'forgot_password')->first();
        $this->assertNotNull($firstOtp);
        $this->assertFalse((bool)$firstOtp->used);

        // Fast-forward cooldown to simulate waiting
        Cache::flush();

        // Trigger Resend OTP via AJAX
        $resendRes = $this->postJson('/forgot-password', [
            'is_resend'  => true,
            'purpose'    => 'forgot_password',
            'identifier' => 'student55@school.test',
        ]);

        $resendRes->assertStatus(200);
        $resendRes->assertJson(['success' => true]);

        // First OTP is invalidated (used = true)
        $firstOtp->refresh();
        $this->assertTrue((bool)$firstOtp->used);

        // New OTP exists and is sent to the same email
        $newOtp = Otp::where('user_id', $user->id)->where('purpose', 'forgot_password')->where('used', false)->latest()->first();
        $this->assertNotNull($newOtp);
        $this->assertEquals('student55@school.test', $newOtp->email);
        $this->assertNotEquals($firstOtp->code, $newOtp->code);
    }
}
