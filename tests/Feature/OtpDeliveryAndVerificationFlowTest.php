<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpDeliveryAndVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
    }

    /**
     * 1. Register a new account / Request OTP & Confirm OTP Generation (6-digits, 10-min expiry).
     */
    public function test_register_otp_generates_secure_6_digit_code_and_sends_email()
    {
        $email = 'newuser@gmail.com';

        $response = $this->postJson('/otp/send-register', [
            'email' => $email,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'  => true,
            'cooldown' => 30,
        ]);

        // Assert OTP stored in database
        $otp = Otp::where('email', $email)->where('purpose', 'register')->first();
        $this->assertNotNull($otp);
        $this->assertEquals(6, strlen($otp->code));
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $otp->code);
        $this->assertFalse($otp->used);
        $this->assertTrue($otp->expires_at->isFuture());

        // Assert Mail sent
        Mail::assertSent(OtpMail::class, function ($mail) use ($email, $otp) {
            return $mail->hasTo($email) && $mail->otp === $otp->code;
        });
    }

    /**
     * 2. Confirm Email Content matches requirements (Subject, 10 min expiry, plain text).
     */
    public function test_otp_email_content_and_headers()
    {
        $mailable = new OtpMail('482915', 'register', 'Test User');
        $envelope = $mailable->envelope();

        $this->assertEquals('Your Verification Code', $envelope->subject);

        $renderedHtml = $mailable->render();
        $this->assertStringContainsString('482915', $renderedHtml);
        $this->assertStringContainsString('10 minutes', $renderedHtml);
        $this->assertStringContainsString('If you did not request this code, you can safely ignore this email.', $renderedHtml);
    }

    /**
     * 3. Resend OTP invalidates previous OTP and restarts timer.
     */
    public function test_resend_otp_invalidates_previous_otp()
    {
        $email = 'resendtest@gmail.com';

        // 1st request
        $this->postJson('/otp/send-register', ['email' => $email])->assertStatus(200);
        $firstOtp = Otp::where('email', $email)->where('purpose', 'register')->latest()->first();
        $this->assertFalse($firstOtp->used);

        // Clear cooldown to simulate waiting 30 seconds
        Cache::flush();

        // 2nd request (Resend)
        $this->postJson('/otp/send-register', ['email' => $email])->assertStatus(200);

        // First OTP must now be marked as used (invalidated)
        $firstOtp->refresh();
        $this->assertTrue($firstOtp->used);

        // A new OTP should be active
        $secondOtp = Otp::where('email', $email)->where('purpose', 'register')->where('used', false)->first();
        $this->assertNotNull($secondOtp);
        $this->assertNotEquals($firstOtp->id, $secondOtp->id);

        // Attempting to verify the old (invalidated) OTP must fail
        $verifyOld = $this->postJson('/otp/verify-register', [
            'email' => $email,
            'otp'   => $firstOtp->code,
        ]);
        $verifyOld->assertStatus(422);
    }

    /**
     * 4. Resend cooldown enforcement (30 seconds).
     */
    public function test_resend_cooldown_enforced_at_30_seconds()
    {
        $email = 'cooldowntest@gmail.com';

        $res1 = $this->postJson('/otp/send-register', ['email' => $email]);
        $res1->assertStatus(200);
        $this->assertEquals(30, $res1->json('cooldown'));

        // Immediate subsequent request must return HTTP 429
        $res2 = $this->postJson('/otp/send-register', ['email' => $email]);
        $res2->assertStatus(429);
        $this->assertFalse($res2->json('success'));
        $this->assertGreaterThan(0, $res2->json('cooldown'));
    }

    /**
     * 5. Enter correct OTP -> Email verified successfully.
     */
    public function test_verify_correct_otp_succeeds_and_invalidates_code()
    {
        $email = 'verifytest@gmail.com';

        $this->postJson('/otp/send-register', ['email' => $email])->assertStatus(200);
        $otp = Otp::where('email', $email)->where('purpose', 'register')->first();

        $verify = $this->postJson('/otp/verify-register', [
            'email' => $email,
            'otp'   => $otp->code,
        ]);

        $verify->assertStatus(200);
        $verify->assertJson([
            'success' => true,
            'message' => 'Email verified successfully.',
        ]);

        // Code should now be used
        $otp->refresh();
        $this->assertTrue($otp->used);

        // Cannot be reused
        $reverify = $this->postJson('/otp/verify-register', [
            'email' => $email,
            'otp'   => $otp->code,
        ]);
        $reverify->assertStatus(422);
    }

    /**
     * 6. Expired OTP is rejected with clear message.
     */
    public function test_expired_otp_is_rejected()
    {
        $email = 'expiredtest@gmail.com';

        $this->postJson('/otp/send-register', ['email' => $email])->assertStatus(200);
        $otp = Otp::where('email', $email)->where('purpose', 'register')->first();

        // Expire the OTP
        $otp->update(['expires_at' => Carbon::now()->subMinutes(1)]);

        $verify = $this->postJson('/otp/verify-register', [
            'email' => $email,
            'otp'   => $otp->code,
        ]);

        $verify->assertStatus(422);
        $verify->assertJson([
            'success' => false,
            'message' => 'This code has expired. Please request a new one.',
        ]);
    }

    /**
     * 7. Incorrect OTP is rejected with attempts count.
     */
    public function test_incorrect_otp_is_rejected()
    {
        $email = 'wrongotp@gmail.com';

        $this->postJson('/otp/send-register', ['email' => $email])->assertStatus(200);

        $verify = $this->postJson('/otp/verify-register', [
            'email' => $email,
            'otp'   => '000000',
        ]);

        $verify->assertStatus(422);
        $this->assertStringContainsString('Invalid verification code', $verify->json('message'));
    }

    /**
     * 8. Too many failed attempts (5 fails) invalidates OTP.
     */
    public function test_max_failed_attempts_locks_otp()
    {
        $email = 'locktest@gmail.com';

        $this->postJson('/otp/send-register', ['email' => $email])->assertStatus(200);
        $otp = Otp::where('email', $email)->where('purpose', 'register')->first();

        for ($i = 1; $i <= 4; $i++) {
            $res = $this->postJson('/otp/verify-register', [
                'email' => $email,
                'otp'   => '999999',
            ]);
            $res->assertStatus(422);
        }

        // 5th attempt invalidates
        $res5 = $this->postJson('/otp/verify-register', [
            'email' => $email,
            'otp'   => '999999',
        ]);
        $res5->assertStatus(422);
        $this->assertStringContainsString('Too many failed verification attempts', $res5->json('message'));

        // Even with the right code now, it should fail because it was invalidated (or throttled)
        $res6 = $this->postJson('/otp/verify-register', [
            'email' => $email,
            'otp'   => $otp->code,
        ]);
        $this->assertTrue(in_array($res6->status(), [422, 429]));
    }

    /**
     * 9. Email sending failure returns 500 error instead of silently succeeding.
     */
    public function test_email_sending_failure_returns_useful_error()
    {
        $email = 'failtest@gmail.com';

        // Force Mail::to()->send() to throw an exception
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \Exception('Connection refused by SMTP server'));

        $res = $this->postJson('/otp/send-register', [
            'email' => $email,
        ]);

        $res->assertStatus(500);
        $res->assertJson([
            'success' => false,
            'message' => 'Unable to send verification code. Please try again.',
        ]);

        // Code should not be active
        $otp = Otp::where('email', $email)->where('purpose', 'register')->first();
        if ($otp) {
            $this->assertTrue($otp->used);
        }
    }

    /**
     * 10. Forgot Password Flow with OTP.
     */
    public function test_forgot_password_otp_flow()
    {
        $user = User::factory()->create([
            'email'    => 'forgotstudent@gmail.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        // 1. Request forgot password OTP
        $sendRes = $this->post('/forgot-password', [
            'identifier' => 'forgotstudent@gmail.com',
        ]);
        $sendRes->assertRedirect(route('otp.verify.form', ['purpose' => 'forgot_password']));

        $otp = Otp::where('user_id', $user->id)->where('purpose', 'forgot_password')->first();
        $this->assertNotNull($otp);

        // 2. Verify OTP
        $verifyRes = $this->post('/verify-otp', [
            'identifier' => 'forgotstudent@gmail.com',
            'otp'        => $otp->code,
            'purpose'    => 'forgot_password',
        ]);
        $verifyRes->assertRedirect(route('otp.reset.form'));

        // 3. Reset Password
        $resetRes = $this->withSession([
            'otp_verified_user' => $user->id,
            'otp_purpose'       => 'forgot_password',
        ])->post('/reset-password', [
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $resetRes->assertRedirect(route('login'));

        // Verify password changed
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
    }
}
