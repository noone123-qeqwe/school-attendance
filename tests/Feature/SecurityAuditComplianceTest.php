<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Otp;
use App\Services\AccountLockoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SecurityAuditComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
    }

    /**
     * Finding-001: Global Rate Limiting on /api/*
     */
    public function test_global_api_rate_limiting_enforces_threshold()
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');
            $this->assertNotEquals(429, $response->getStatusCode(), "Request $i should not be rate limited yet");
        }

        // The 61st request should be throttled
        $throttled = $this->actingAs($user, 'sanctum')->getJson('/api/user');
        $throttled->assertStatus(429);
        $throttled->assertJsonFragment(['status' => 'error']);
    }

    /**
     * Finding-004: Account Lockout on /api/login and /login
     */
    public function test_api_login_locks_account_after_5_failed_attempts()
    {
        $user = User::factory()->create([
            'email' => 'victim@school.edu',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        // Attempt 1 to 4: returns 401
        for ($i = 1; $i <= 4; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'victim@school.edu',
                'password' => 'WrongPassword',
            ]);
            $response->assertStatus(401);
            $response->assertJsonFragment(['remaining_attempts' => 5 - $i]);
        }

        // Attempt 5: triggers lockout (HTTP 429)
        $response5 = $this->postJson('/api/login', [
            'email' => 'victim@school.edu',
            'password' => 'WrongPassword',
        ]);
        $response5->assertStatus(429);
        $response5->assertJsonFragment(['locked' => true]);

        // Attempt 6 (even with correct password): remains locked
        $response6 = $this->postJson('/api/login', [
            'email' => 'victim@school.edu',
            'password' => 'CorrectPassword123!',
        ]);
        $response6->assertStatus(429);
        $response6->assertJsonFragment(['locked' => true]);
    }

    public function test_web_login_locks_account_after_5_failed_attempts()
    {
        $user = User::factory()->create([
            'email' => 'webuser@school.edu',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        for ($i = 1; $i <= 4; $i++) {
            $response = $this->post('/login', [
                'identifier' => 'webuser@school.edu',
                'password' => 'WrongPassword',
            ]);
            $response->assertSessionHasErrors('identifier');
        }

        // 5th attempt locks out
        $response5 = $this->post('/login', [
            'identifier' => 'webuser@school.edu',
            'password' => 'WrongPassword',
        ]);
        $response5->assertSessionHasErrors(['identifier' => 'Account is temporarily locked due to repeated failed login attempts. Please try again in 15 minutes.']);
    }

    public function test_successful_login_clears_failed_attempts()
    {
        $user = User::factory()->create([
            'email' => 'clearuser@school.edu',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        // 2 failed attempts
        $this->postJson('/api/login', ['email' => 'clearuser@school.edu', 'password' => 'Wrong']);
        $this->postJson('/api/login', ['email' => 'clearuser@school.edu', 'password' => 'Wrong']);

        // Successful login
        $success = $this->postJson('/api/login', ['email' => 'clearuser@school.edu', 'password' => 'CorrectPassword123!']);
        $success->assertStatus(200);

        // Lockout service should be cleared
        $lockout = app(AccountLockoutService::class);
        $this->assertEquals(5, $lockout->getRemainingAttempts('clearuser@school.edu', '127.0.0.1'));
    }

    /**
     * Finding-005: OTP Cooldown Enforcement
     */
    public function test_api_otp_enforces_60_second_cooldown()
    {
        $response1 = $this->postJson('/api/otp', [
            'email' => 'student@school.edu',
            'purpose' => 'verification',
        ]);
        $response1->assertStatus(200);
        $response1->assertJsonFragment(['status' => 'success']);

        // Immediate second attempt should return 429
        $response2 = $this->postJson('/api/otp', [
            'email' => 'student@school.edu',
            'purpose' => 'verification',
        ]);
        $response2->assertStatus(429);
        $response2->assertJsonFragment(['status' => 'error']);
    }

    public function test_web_register_otp_enforces_cooldown()
    {
        $response1 = $this->postJson('/otp/send-register', [
            'email' => 'newstudent@school.edu',
        ]);
        $response1->assertStatus(200);

        // Immediate duplicate request is blocked with 429
        $response2 = $this->postJson('/otp/send-register', [
            'email' => 'newstudent@school.edu',
        ]);
        $response2->assertStatus(429);
        $response2->assertJsonFragment(['success' => false]);
    }

    /**
     * OTP Brute-Force Code Invalidation (5 failed attempts)
     */
    public function test_otp_is_invalidated_after_5_failed_verification_attempts()
    {
        $user = User::factory()->create(['email' => 'verifytest@school.edu']);
        $otp = Otp::generate($user->id, 'verification');

        for ($i = 1; $i <= 4; $i++) {
            $res = $this->postJson('/api/otp/verify', [
                'email' => 'verifytest@school.edu',
                'otp' => '000000',
                'purpose' => 'verification',
            ]);
            $res->assertStatus(422);
            $res->assertJsonFragment(['remaining_attempts' => 5 - $i]);
        }

        // 5th failed attempt invalidates OTP
        $res5 = $this->postJson('/api/otp/verify', [
            'email' => 'verifytest@school.edu',
            'otp' => '000000',
            'purpose' => 'verification',
        ]);
        $res5->assertStatus(422);
        $res5->assertJsonFragment(['message' => 'Too many failed verification attempts. This OTP has been invalidated. Please request a new one.']);

        $otp->refresh();
        $this->assertTrue((bool) $otp->used);
    }

    /**
     * Finding-006: Password Reset Flood Protection
     */
    public function test_password_reset_flood_protection_enforces_cooldown()
    {
        $user = User::factory()->create(['email' => 'resetme@school.edu']);

        $res1 = $this->postJson('/api/reset', ['email' => 'resetme@school.edu']);
        $res1->assertStatus(200);

        // Second request immediately after triggers cooldown rejection (429)
        $res2 = $this->postJson('/api/reset', ['email' => 'resetme@school.edu']);
        $res2->assertStatus(429);
        $res2->assertJsonFragment(['status' => 'error']);
    }

    /**
     * Finding-007: Email Verification Endpoint Abuse Protection
     */
    public function test_email_verify_endpoint_enforces_cooldown_and_rate_limit()
    {
        $res1 = $this->postJson('/api/email/verify', ['email' => 'verifyemail@school.edu']);
        $res1->assertStatus(200);

        $res2 = $this->postJson('/api/email/verify', ['email' => 'verifyemail@school.edu']);
        $res2->assertStatus(429);
        $res2->assertJsonFragment(['status' => 'error']);
    }

    /**
     * Finding-001: Global Rate Limiting on unmapped /api/* routes across all HTTP methods
     */
    public function test_global_rate_limiting_catches_unmapped_post_put_delete_routes()
    {
        // Unmapped POST should return 404, not 405 MethodNotAllowed
        $response = $this->postJson('/api/unmapped-endpoint-test', ['data' => 123]);
        $response->assertStatus(404);
        $response->assertJsonFragment(['status' => 'error', 'message' => 'API endpoint not found.']);

        // Rapid requests to unmapped route trigger 429 global rate limit
        for ($i = 0; $i < 59; $i++) {
            $this->postJson('/api/unmapped-endpoint-test', ['data' => $i]);
        }

        $throttled = $this->postJson('/api/unmapped-endpoint-test', ['data' => 999]);
        $throttled->assertStatus(429);
        $throttled->assertJsonFragment(['status' => 'error']);
    }

    /**
     * Finding-004: Account lockout works with alternative field names (username, identifier)
     */
    public function test_api_login_locks_out_with_username_and_identifier_fields()
    {
        for ($i = 1; $i <= 4; $i++) {
            $res = $this->postJson('/api/login', [
                'username' => 'testuser@school.edu',
                'password' => 'WrongPass123',
            ]);
            $this->assertTrue(in_array($res->getStatusCode(), [401, 422]));
        }

        // 5th attempt locks out
        $res5 = $this->postJson('/api/login', [
            'username' => 'testuser@school.edu',
            'password' => 'WrongPass123',
        ]);
        $res5->assertStatus(429);
        $res5->assertJsonFragment(['locked' => true]);
    }

    /**
     * Finding-002: Fast response times for throttled requests
     */
    public function test_throttled_burst_requests_return_immediately()
    {
        $user = User::factory()->create();

        // Saturate rate limiter
        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($user, 'sanctum')->getJson('/api/user');
        }

        $start = microtime(true);
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');
        $elapsed = (microtime(true) - $start) * 1000; // in ms

        $response->assertStatus(429);
        // Throttled request should be processed in < 100ms (far below the 2000ms threshold)
        $this->assertLessThan(100, $elapsed, "Throttled request took {$elapsed}ms, should be <100ms");
    }

    /**
     * Anti-Spam: Web Registration OTP IP cooldown & flood prevention
     */
    public function test_web_registration_otp_ip_flood_blocked()
    {
        $res1 = $this->postJson('/otp/send-register', ['email' => 'newstudent1@school.edu']);
        $res1->assertStatus(200);

        // Immediate second attempt with different email from same IP should be blocked by IP cooldown
        $res2 = $this->postJson('/otp/send-register', ['email' => 'newstudent2@school.edu']);
        $res2->assertStatus(429);
        $res2->assertJsonFragment(['status' => 'error']);
    }

    /**
     * Anti-Spam: Admin 2FA Resend Cooldown & Abuse Protection
     */
    public function test_admin_2fa_resend_cooldown_blocked()
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin_2fa_test@school.edu']);

        $res1 = $this->actingAs($admin)->postJson('/admin/2fa/resend');
        $res1->assertStatus(200);

        // Second immediate resend triggers 429
        $res2 = $this->actingAs($admin)->postJson('/admin/2fa/resend');
        $res2->assertStatus(429);
        $res2->assertJsonFragment(['status' => 'error']);
    }

    /**
     * Anti-Spam: Multi-email cycling from single IP is blocked on /api/otp
     */
    public function test_api_otp_cycling_emails_from_single_ip_is_blocked()
    {
        $res1 = $this->postJson('/api/otp', ['email' => 'bot_user_1@domain.com']);
        $res1->assertStatus(200);

        // Immediate subsequent request with different email should be blocked by IP cooldown
        $res2 = $this->postJson('/api/otp', ['email' => 'bot_user_2@domain.com']);
        $res2->assertStatus(429);
        $res2->assertJsonFragment(['status' => 'error']);
    }
}
