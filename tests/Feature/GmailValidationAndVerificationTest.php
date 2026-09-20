<?php

namespace Tests\Feature;

use App\Models\Otp;
use App\Models\User;
use App\Services\Email\EmailDeliveryResult;
use App\Services\Email\EmailDeliveryService;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class GmailValidationAndVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Test invalid Gmail formats are rejected when requesting OTP.
     */
    public function test_invalid_gmail_format_rejected_when_requesting_otp(): void
    {
        // Non-Gmail domain
        $res1 = $this->postJson('/otp/send-register', ['email' => 'user@yahoo.com']);
        $res1->assertStatus(422)
            ->assertJson([
                'success'  => false,
                'category' => 'invalid',
                'error'    => 'EMAIL_INVALID',
            ]);
        $this->assertStringContainsString('valid Gmail address', $res1->json('message'));

        // Gmail username too short (< 6 characters)
        $res2 = $this->postJson('/otp/send-register', ['email' => 'abc@gmail.com']);
        $res2->assertStatus(422)
            ->assertJson([
                'success'  => false,
                'category' => 'invalid',
                'error'    => 'EMAIL_INVALID',
            ]);

        // Consecutive dots
        $res3 = $this->postJson('/otp/send-register', ['email' => 'user..name@gmail.com']);
        $res3->assertStatus(422)
            ->assertJson([
                'success'  => false,
                'category' => 'invalid',
            ]);

        // Leading dot
        $res4 = $this->postJson('/otp/send-register', ['email' => '.username@gmail.com']);
        $res4->assertStatus(422)
            ->assertJson([
                'success'  => false,
                'category' => 'invalid',
            ]);

        // Trailing dot
        $res5 = $this->postJson('/otp/send-register', ['email' => 'username.@gmail.com']);
        $res5->assertStatus(422)
            ->assertJson([
                'category' => 'invalid',
            ]);

        // Invalid symbols
        $res6 = $this->postJson('/otp/send-register', ['email' => 'user$name@gmail.com']);
        $res6->assertStatus(422)
            ->assertJson([
                'category' => 'invalid',
            ]);
    }

    /**
     * 2. Test already-registered Gmail address is rejected with distinct category and message.
     */
    public function test_already_registered_gmail_rejected_when_requesting_otp(): void
    {
        User::factory()->create([
            'email' => 'registered.user@gmail.com',
        ]);

        $res = $this->postJson('/otp/send-register', ['email' => 'registered.user@gmail.com']);
        $res->assertStatus(422)
            ->assertJson([
                'success'  => false,
                'status'   => 'error',
                'category' => 'already_registered',
                'error'    => 'EMAIL_ALREADY_REGISTERED',
                'message'  => 'This Gmail address is already registered. Please sign in or use another email.',
            ]);
    }

    /**
     * 3. Test unavailable email address / delivery failure returns distinct category and message.
     */
    public function test_unavailable_email_fails_when_mail_delivery_fails(): void
    {
        $mockDelivery = Mockery::mock(EmailDeliveryService::class);
        $mockDelivery->shouldReceive('sendOtp')
            ->once()
            ->andReturn(EmailDeliveryResult::rejected('smtp', '550 5.1.1 The email account that you tried to reach does not exist', 500));

        $this->app->instance(EmailDeliveryService::class, $mockDelivery);

        $res = $this->postJson('/otp/send-register', ['email' => 'nonexistent.mailbox.999@gmail.com']);
        $res->assertStatus(422)
            ->assertJson([
                'success'  => false,
                'status'   => 'error',
                'category' => 'unavailable',
                'error'    => 'EMAIL_UNAVAILABLE',
            ]);
        $this->assertStringContainsString('Unable to verify this email address', $res->json('message'));
    }

    /**
     * 4. Test unverified email address is blocked from completing registration.
     */
    public function test_unverified_email_cannot_complete_registration(): void
    {
        // No verification session
        $res = $this->post(route('register.submit'), [
            'name'                  => 'John Unverified',
            'first_name'            => 'John',
            'surname'               => 'Unverified',
            'role'                  => 'student',
            'course'                => 'BSCS',
            'year_level'            => 1,
            'semester'              => '1',
            'email'                 => 'unverified.student@gmail.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms'                 => 1,
        ]);

        $res->assertSessionHasErrors('email');
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('This email address is unverified', $errors[0]);

        $this->assertDatabaseMissing('users', [
            'email' => 'unverified.student@gmail.com',
        ]);
    }

    /**
     * 5. Test mismatched verified email in session is blocked from completing registration.
     */
    public function test_mismatched_verified_email_cannot_complete_registration(): void
    {
        session(['reg_email_verified' => 'different.verified@gmail.com']);

        $res = $this->post(route('register.submit'), [
            'name'                  => 'Jane Mismatch',
            'first_name'            => 'Jane',
            'surname'               => 'Mismatch',
            'role'                  => 'student',
            'course'                => 'BSCS',
            'year_level'            => 1,
            'semester'              => '1',
            'email'                 => 'submitting.email@gmail.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms'                 => 1,
        ]);

        $res->assertSessionHasErrors('email');
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('This email address is unverified', $errors[0]);

        $this->assertDatabaseMissing('users', [
            'email' => 'submitting.email@gmail.com',
        ]);
    }

    /**
     * 6. Test successful full registration flow with verified Gmail.
     */
    public function test_successful_full_registration_flow_with_verified_gmail(): void
    {
        $email = 'verified.student99@gmail.com';

        // 1. Send OTP
        $sendRes = $this->postJson('/otp/send-register', ['email' => $email]);
        $sendRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status'  => 'success',
            ]);

        // Find the generated OTP in DB
        $otp = Otp::where('email', $email)->latest()->first();
        $this->assertNotNull($otp);

        // 2. Verify OTP
        $verifyRes = $this->postJson('/otp/verify-register', [
            'email' => $email,
            'otp'   => $otp->code,
        ]);
        $verifyRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status'  => 'success',
            ]);

        // Confirm session was set
        $this->assertEquals($email, session('reg_email_verified'));

        // 3. Complete registration
        $regRes = $this->post(route('register.submit'), [
            'name'                  => 'Verified Student',
            'first_name'            => 'Verified',
            'surname'               => 'Student',
            'role'                  => 'student',
            'course'                => 'BSCS',
            'year_level'            => 2,
            'semester'              => '1',
            'email'                 => $email,
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms'                 => 1,
        ]);

        $regRes->assertRedirect(route('home'));
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role'  => 'student',
        ]);

        // Confirm session was cleared so it cannot be reused
        $this->assertNull(session('reg_email_verified'));
    }

    /**
     * 7. Test OtpService helper classification.
     */
    public function test_otp_service_classify_email(): void
    {
        // Invalid
        $res1 = OtpService::classifyEmail('invalid-format');
        $this->assertEquals('invalid', $res1['category']);

        // Already registered
        User::factory()->create(['email' => 'preexisting@gmail.com']);
        $res2 = OtpService::classifyEmail('preexisting@gmail.com');
        $this->assertEquals('already_registered', $res2['category']);

        // Valid
        $res3 = OtpService::classifyEmail('fresh.account.valid@gmail.com');
        $this->assertEquals('valid', $res3['category']);
    }

    /**
     * 8. Test change email endpoint validates Gmail format and duplicate registration.
     */
    public function test_change_email_validates_gmail_format_and_duplicates(): void
    {
        $user = User::factory()->create([
            'email' => 'current.user@gmail.com',
            'role'  => 'student',
        ]);

        User::factory()->create([
            'email' => 'another.user@gmail.com',
            'role'  => 'student',
        ]);

        $this->actingAs($user);

        // Send OTP with invalid Gmail format
        $resInvalid = $this->postJson('/otp/send-email-change', [
            'new_email' => 'notgmail@yahoo.com',
        ]);
        $resInvalid->assertStatus(422)
            ->assertJson([
                'success'  => false,
                'category' => 'invalid',
            ]);

        // Send OTP with already-registered email
        $resDup = $this->postJson('/otp/send-email-change', [
            'new_email' => 'another.user@gmail.com',
        ]);
        $resDup->assertStatus(422)
            ->assertJson([
                'success'  => false,
                'category' => 'already_registered',
            ]);
    }

    /**
     * 9. Test forgot password rejects invalid Gmail format and shows clear error message.
     */
    public function test_forgot_password_rejects_invalid_gmail_format(): void
    {
        // GET /forgot-password with invalid Gmail in query string
        $getRes = $this->get('/forgot-password?identifier=user@yahoo.com');
        $getRes->assertStatus(200);
        $getRes->assertSee('Please enter a valid Gmail address (e.g., username@gmail.com).');

        // POST /forgot-password with invalid Gmail in single identifier
        $postRes1 = $this->postJson(route('otp.forgot.send'), [
            'identifier' => 'user..dots@gmail.com',
        ]);
        $postRes1->assertStatus(422)
            ->assertJson([
                'success'  => false,
                'category' => 'invalid',
                'error'    => 'EMAIL_INVALID',
            ]);
        $this->assertStringContainsString('valid Gmail address', $postRes1->json('message'));

        // POST /forgot-password in dual verification mode with invalid Gmail
        $postRes2 = $this->postJson(route('otp.forgot.send'), [
            'account_id' => '20260001',
            'email'      => 'short@gmail.com',
        ]);
        $postRes2->assertStatus(422)
            ->assertJson([
                'success'  => false,
                'category' => 'invalid',
                'error'    => 'EMAIL_INVALID',
            ]);
        $this->assertStringContainsString('valid Gmail address', $postRes2->json('message'));
    }

    /**
     * 10. Test forgot password does not send OTP when email does not exist.
     */
    public function test_forgot_password_does_not_send_otp_when_email_does_not_exist(): void
    {
        $nonExistent = 'nonexistent.user.12345@gmail.com';

        $res = $this->postJson(route('otp.forgot.send'), [
            'identifier' => $nonExistent,
        ]);

        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error'   => 'ACCOUNT_NOT_FOUND',
            ]);

        // Assert NO OTP record was created in database
        $this->assertDatabaseMissing('otps', [
            'email'   => $nonExistent,
            'purpose' => 'forgot_password',
        ]);
    }

    /**
     * 11. Test direct API POST to /register cannot bypass email verification.
     */
    public function test_direct_api_register_request_cannot_bypass_email_verification(): void
    {
        // Direct JSON POST to /register without prior email verification session
        $res = $this->postJson(route('register.submit'), [
            'name'                  => 'Hacker Bypasser',
            'first_name'            => 'Hacker',
            'surname'               => 'Bypasser',
            'role'                  => 'student',
            'course'                => 'BSCS',
            'year_level'            => 1,
            'semester'              => '1',
            'email'                 => 'bypasser.target@gmail.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms'                 => 1,
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $errors = $res->json('errors.email');
        $this->assertStringContainsString('This email address is unverified', $errors[0]);

        $this->assertDatabaseMissing('users', [
            'email' => 'bypasser.target@gmail.com',
        ]);
    }

    /**
     * 12. Test API OTP endpoints (/api/otp, /api/email/verify, /api/reset) validate Gmail and prevent bypass.
     */
    public function test_api_otp_endpoints_validate_gmail_format_and_prevent_bypass(): void
    {
        // 1. /api/otp with invalid Gmail format
        $res1 = $this->postJson('/api/otp', [
            'email'   => 'invalid..gmail@gmail.com',
            'purpose' => 'register',
        ]);
        $res1->assertStatus(422)
            ->assertJson([
                'status'   => 'error',
                'category' => 'invalid',
                'error'    => 'EMAIL_INVALID',
            ]);

        // 2. /api/otp with purpose register and already registered email
        User::factory()->create(['email' => 'existing.api.user@gmail.com']);
        $res2 = $this->postJson('/api/otp', [
            'email'   => 'existing.api.user@gmail.com',
            'purpose' => 'register',
        ]);
        $res2->assertStatus(422)
            ->assertJson([
                'status'   => 'error',
                'category' => 'already_registered',
                'error'    => 'EMAIL_ALREADY_REGISTERED',
            ]);

        // 3. /api/otp with purpose forgot_password and non-existent email -> does NOT send mail or create OTP
        $res3 = $this->postJson('/api/otp', [
            'email'   => 'unknown.account999@gmail.com',
            'purpose' => 'forgot_password',
        ]);
        $res3->assertStatus(200)
            ->assertJson([
                'status'  => 'success',
                'success' => true,
            ]);
        $this->assertDatabaseMissing('otps', [
            'email'   => 'unknown.account999@gmail.com',
            'purpose' => 'forgot_password',
        ]);

        // 4. /api/email/verify with invalid format
        $res4 = $this->postJson('/api/email/verify', [
            'email' => 'notgmail@hotmail.com',
        ]);
        $res4->assertStatus(422)
            ->assertJson([
                'status'   => 'error',
                'category' => 'invalid',
                'error'    => 'EMAIL_INVALID',
            ]);

        // 5. /api/reset with invalid format in email
        $res5 = $this->postJson('/api/reset', [
            'email' => 'bad.format..dots@gmail.com',
        ]);
        $res5->assertStatus(422)
            ->assertJson([
                'status'   => 'error',
                'category' => 'invalid',
                'error'    => 'EMAIL_INVALID',
            ]);
    }

    /**
     * 13. Test student update rejects non-Gmail addresses.
     */
    public function test_student_update_rejects_non_gmail_address(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin.test.acc@gmail.com']);
        $student = User::factory()->create(['role' => 'student', 'email' => 'student.original@gmail.com']);

        $this->actingAs($admin);

        $res = $this->put(route('admin.student.update', $student), [
            'name'       => $student->name,
            'email'      => 'invalid.student@yahoo.com',
            'year_level' => 1,
            'semester'   => 1,
        ]);

        $res->assertSessionHasErrors('email');
        $this->assertDatabaseHas('users', [
            'id'    => $student->id,
            'email' => 'student.original@gmail.com',
        ]);
    }
}
