<?php

namespace Tests\Feature;

use App\Models\Otp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForgotPasswordAccountVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Cache::flush();
    }

    /**
     * Edge Case 1: Correct Student ID + correct registered Gmail → Continue to OTP
     */
    public function test_correct_student_id_and_correct_registered_email_succeeds()
    {
        $user = User::factory()->create([
            'student_number' => '20260001',
            'email'          => 'student@gmail.com',
        ]);

        $response = $this->post('/forgot-password', [
            'account_id' => '20260001',
            'email'      => 'student@gmail.com',
        ]);

        $response->assertRedirect(route('otp.verify.form', ['purpose' => 'forgot_password']));
        $response->assertSessionHas('account_verified', true);
        $response->assertSessionHas('otp_user_id', $user->id);

        $otp = Otp::where('user_id', $user->id)->where('purpose', 'forgot_password')->latest()->first();
        $this->assertNotNull($otp);
        $this->assertEquals('student@gmail.com', $otp->email);
    }

    /**
     * Edge Case 2: Correct Student ID + wrong Gmail → Reject with clear error
     */
    public function test_correct_student_id_and_wrong_email_rejects_without_sending_otp()
    {
        $user = User::factory()->create([
            'student_number' => '20260001',
            'email'          => 'student@gmail.com',
        ]);

        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'account_id' => '20260001',
            'email'      => 'wrongemail@gmail.com',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors('email');
        $response->assertSessionHas('account_verification_failed', true);

        // Verify NO OTP was created
        $otp = Otp::where('purpose', 'forgot_password')->first();
        $this->assertNull($otp);
    }

    /**
     * Edge Case 3: Wrong Student ID + correct Gmail → Reject (safe failure)
     */
    public function test_wrong_student_id_and_correct_email_rejects_safely()
    {
        $user = User::factory()->create([
            'student_number' => '20260001',
            'email'          => 'student@gmail.com',
        ]);

        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'account_id' => '20269999', // wrong student ID
            'email'      => 'student@gmail.com',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors('account_id');

        // Verify NO OTP was created
        $otp = Otp::where('purpose', 'forgot_password')->first();
        $this->assertNull($otp);
    }

    /**
     * Edge Case 4: Wrong Student ID + wrong Gmail → Reject
     */
    public function test_wrong_student_id_and_wrong_email_rejects()
    {
        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'account_id' => '99999999',
            'email'      => 'nobody@gmail.com',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors('account_id');

        $otp = Otp::where('purpose', 'forgot_password')->first();
        $this->assertNull($otp);
    }

    /**
     * Edge Case 5: Correct account + email with different capitalization → Handle correctly
     */
    public function test_correct_account_with_different_email_capitalization_succeeds()
    {
        $user = User::factory()->create([
            'student_number' => '20260002',
            'email'          => 'Student@Gmail.com',
        ]);

        $response = $this->post('/forgot-password', [
            'account_id' => '20260002',
            'email'      => 'student@gmail.com',
        ]);

        $response->assertRedirect(route('otp.verify.form', ['purpose' => 'forgot_password']));
        $this->assertNotNull(Otp::where('user_id', $user->id)->first());
    }

    /**
     * Edge Case 6: Correct account + accidental spaces → Handle correctly
     */
    public function test_correct_account_with_accidental_spaces_succeeds()
    {
        $user = User::factory()->create([
            'student_number' => '20260003',
            'email'          => 'spacestudent@gmail.com',
        ]);

        $response = $this->post('/forgot-password', [
            'account_id' => '  20260003  ',
            'email'      => '  spacestudent@gmail.com  ',
        ]);

        $response->assertRedirect(route('otp.verify.form', ['purpose' => 'forgot_password']));
        $this->assertNotNull(Otp::where('user_id', $user->id)->first());
    }

    /**
     * Edge Case 7: Non-existent Student ID → Safe failure
     */
    public function test_non_existent_student_id_returns_safe_message()
    {
        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'account_id' => '99998888',
            'email'      => 'nonexistent@gmail.com',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors(['account_id' => 'Unable to verify the account. Please check your Student ID and email address.']);
    }

    /**
     * Edge Case 8: OTP requested / accessed before account verification → Not allowed
     */
    public function test_otp_page_direct_access_without_account_verification_redirects_to_forgot_form()
    {
        // Visiting verify-otp without session('otp_verified_account')
        $response = $this->get('/verify-otp?purpose=forgot_password');
        $response->assertRedirect(route('otp.forgot.form'));
        $response->assertSessionHasErrors('account_id');
    }

    /**
     * Edge Case 9: Reset token modified to another user → Reject
     */
    public function test_reset_session_tampering_or_mismatch_is_rejected()
    {
        $userA = User::factory()->create(['email' => 'usera@gmail.com', 'password' => Hash::make('PasswordA123!')]);
        $userB = User::factory()->create(['email' => 'userb@gmail.com', 'password' => Hash::make('PasswordB123!')]);

        $tokenA = bin2hex(random_bytes(32));

        // Attempt to submit reset request for User B using User A's token
        $response = $this->withSession([
            'password_reset_user_id' => $userA->id,
            'password_reset_token'   => $tokenA,
            'password_reset_expires' => now()->addMinutes(15)->timestamp,
        ])->post('/reset-password', [
            'reset_token'           => 'tampered_wrong_token',
            'password'              => 'HackedPassword123!',
            'password_confirmation' => 'HackedPassword123!',
        ]);

        $response->assertRedirect(route('otp.forgot.form'));

        // Verify neither password changed
        $userA->refresh();
        $userB->refresh();
        $this->assertTrue(Hash::check('PasswordA123!', $userA->password));
        $this->assertTrue(Hash::check('PasswordB123!', $userB->password));
    }

    /**
     * Edge Case 10: Expired OTP → Reject
     */
    public function test_expired_otp_is_rejected()
    {
        $user = User::factory()->create([
            'student_number' => '20260010',
            'email'          => 'expiredotp@gmail.com',
        ]);

        $otp = Otp::create([
            'email'      => $user->email,
            'user_id'    => $user->id,
            'code'       => '123456',
            'purpose'    => 'forgot_password',
            'used'       => false,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->withSession([
            'otp_verified_account' => true,
            'otp_user_id'          => $user->id,
            'otp_email'            => $user->email,
            'otp_purpose'          => 'forgot_password',
        ])->from('/verify-otp')->post('/verify-otp', [
            'otp'     => '123456',
            'purpose' => 'forgot_password',
        ]);

        $response->assertRedirect('/verify-otp');
        $response->assertSessionHasErrors('otp');
    }

    /**
     * Edge Case 11: Expired reset session → Reject
     */
    public function test_expired_reset_session_is_rejected()
    {
        $user = User::factory()->create([
            'email'    => 'expiredsession@gmail.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = bin2hex(random_bytes(32));

        $response = $this->withSession([
            'password_reset_user_id' => $user->id,
            'password_reset_token'   => $token,
            'password_reset_expires' => now()->subMinutes(5)->timestamp, // expired
        ])->post('/reset-password', [
            'reset_token'           => $token,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect(route('otp.forgot.form'));
        $user->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $user->password));
    }

    /**
     * Edge Case 12: Teacher / Employee account dual-verification works
     */
    public function test_teacher_employee_id_dual_verification_works()
    {
        $teacher = User::factory()->teacher()->create([
            'employee_id' => 'EMP-5001',
            'email'       => 'teacher@gmail.com',
        ]);

        $response = $this->post('/forgot-password', [
            'account_id' => 'EMP-5001',
            'email'      => 'teacher@gmail.com',
        ]);

        $response->assertRedirect(route('otp.verify.form', ['purpose' => 'forgot_password']));
        $this->assertNotNull(Otp::where('user_id', $teacher->id)->first());
    }

    /**
     * Edge Case 13: API rejects password reset when only email is provided without Student ID
     */
    public function test_api_rejects_password_reset_without_student_id()
    {
        $user = User::factory()->create([
            'student_number' => '20260099',
            'email'          => 'student99@gmail.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'student99@gmail.com',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status'  => 'error',
            'success' => false,
        ]);
        $this->assertNull(Otp::where('user_id', $user->id)->first());

        // Now provide both correctly -> should succeed
        $validResponse = $this->postJson('/api/forgot-password', [
            'account_id' => '20260099',
            'email'      => 'student99@gmail.com',
        ]);

        $validResponse->assertStatus(200);
        $validResponse->assertJson([
            'status'  => 'success',
            'success' => true,
        ]);
        $this->assertNotNull(Otp::where('user_id', $user->id)->first());
    }
}