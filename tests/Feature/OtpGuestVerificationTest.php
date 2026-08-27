<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Tests\TestCase;

class OtpGuestVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_otp_is_cached_and_verifiable()
    {
        Mail::fake();
        Cache::flush();

        $email = 'guest@example.com';

        // 1. Send OTP
        $response = $this->postJson('/api/otp/send', [
            'email' => $email,
            'purpose' => 'verification'
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Extract the code from the cache to simulate a user verifying
        $cacheKey = 'guest_otp:' . sha1($email . ':verification');
        $hashedCode = Cache::get($cacheKey);
        $this->assertNotNull($hashedCode);

        // Find what was sent
        Mail::assertQueued(OtpMail::class, function ($mail) use ($email, &$sentCode) {
            $sentCode = $mail->otp;
            return $mail->hasTo($email);
        });

        // 2. Verify with wrong code
        $wrongResponse = $this->postJson('/api/otp/verify', [
            'email' => $email,
            'otp' => '000000',
            'purpose' => 'verification'
        ]);
        
        $wrongResponse->assertStatus(422);

        // 3. Verify with correct code
        $correctResponse = $this->postJson('/api/otp/verify', [
            'email' => $email,
            'otp' => $sentCode,
            'purpose' => 'verification'
        ]);

        $correctResponse->assertStatus(200);
        $this->assertNull(Cache::get($cacheKey), 'Cache should be cleared after success');
    }

    public function test_email_verification_for_guest()
    {
        Mail::fake();
        Cache::flush();

        $email = 'verify@example.com';

        $response = $this->postJson('/api/email/verify', [
            'email' => $email,
        ]);

        $response->assertStatus(200);
        
        $cacheKey = 'guest_otp:' . sha1($email . ':email_verify');
        $this->assertNotNull(Cache::get($cacheKey));

        Mail::assertQueued(OtpMail::class, function ($mail) use ($email, &$sentCode) {
            $sentCode = $mail->otp;
            return $mail->hasTo($email) && $mail->purpose === 'email_verify';
        });

        $verifyResponse = $this->postJson('/api/otp/verify', [
            'email' => $email,
            'otp' => $sentCode,
            'purpose' => 'email_verify'
        ]);

        $verifyResponse->assertStatus(200);
    }
}
