<?php

namespace Tests\Feature;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpVerifyUiAndFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
    }

    public function test_otp_verify_view_renders_with_csp_nonce_and_6_digit_inputs()
    {
        $response = $this->withSession(['otp_identifier' => 'student@example.com'])
            ->get('/verify-otp?purpose=forgot_password');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for CSP nonce on the script
        $this->assertMatchesRegularExpression('/<script[^>]*nonce="[^"]+"/', $content);

        // Check for 6 individual otp_digits[] inputs
        $this->assertEquals(6, substr_count($content, 'name="otp_digits[]"'));

        // Check for hidden otp input
        $this->assertStringContainsString('name="otp"', $content);
        $this->assertStringContainsString('id="otpHidden"', $content);

        // Check for responsive styles
        $this->assertStringContainsString('.otp-inputs', $content);
        $this->assertStringContainsString('flex: 1 1 0', $content);
        $this->assertStringContainsString('max-width: 48px', $content);

        // Check for identifier display
        $this->assertStringContainsString('student@example.com', $content);
    }

    public function test_empty_otp_fails_validation_with_proper_message()
    {
        $response = $this->withSession(['otp_identifier' => 'user@example.com'])
            ->post('/verify-otp', [
                'purpose'    => 'forgot_password',
                'identifier' => 'user@example.com',
                'otp'        => '',
            ]);

        $response->assertSessionHasErrors(['otp' => 'The verification code is required.']);
    }

    public function test_incomplete_otp_fails_validation_with_proper_message()
    {
        $response = $this->withSession(['otp_identifier' => 'user@example.com'])
            ->post('/verify-otp', [
                'purpose'    => 'forgot_password',
                'identifier' => 'user@example.com',
                'otp'        => '123',
            ]);

        $response->assertSessionHasErrors(['otp' => 'The verification code must be exactly 6 digits.']);
    }

    public function test_otp_digits_array_fallback_concatenation_verifies_successfully()
    {
        $user = User::factory()->create([
            'email'    => 'verifytest@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        Otp::create([
            'email'      => $user->email,
            'code'       => '654321',
            'purpose'    => 'forgot_password',
            'expires_at' => Carbon::now()->addMinutes(10),
            'used'       => false,
            'attempts'   => 0,
        ]);

        // Submit form simulating browser where otpHidden was empty, but otp_digits[] was submitted
        $response = $this->withSession(['otp_identifier' => $user->email])
            ->post('/verify-otp', [
                'purpose'    => 'forgot_password',
                'identifier' => $user->email,
                'otp'        => '', // empty hidden input
                'otp_digits' => ['6', '5', '4', '3', '2', '1'],
            ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('otp.reset.form'));
        $this->assertEquals($user->id, session('otp_verified_user'));
    }

    public function test_invalid_otp_keeps_old_input_for_digits()
    {
        $user = User::factory()->create([
            'email'    => 'invalidtest@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        Otp::create([
            'email'      => $user->email,
            'code'       => '111222',
            'purpose'    => 'forgot_password',
            'expires_at' => Carbon::now()->addMinutes(10),
            'used'       => false,
            'attempts'   => 0,
        ]);

        $response = $this->withSession(['otp_identifier' => $user->email])
            ->from('/verify-otp?purpose=forgot_password')
            ->post('/verify-otp', [
                'purpose'    => 'forgot_password',
                'identifier' => $user->email,
                'otp'        => '999888',
            ]);

        $response->assertSessionHasErrors('otp');
        // Assert old input contains the submitted OTP
        $response->assertSessionHas('_old_input');
        $oldInput = session('_old_input');
        $this->assertEquals('999888', $oldInput['otp']);
    }

    public function test_admin_2fa_view_has_csp_nonce_and_responsive_inputs()
    {
        $admin = User::factory()->create([
            'email'    => 'admin@example.com',
            'role'     => 'admin',
            'password' => Hash::make('AdminPass123!'),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_2fa_pending' => true, 'admin_2fa_user_id' => $admin->id])
            ->get('/admin/2fa');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertMatchesRegularExpression('/<script[^>]*nonce="[^"]+"/', $content);
        $this->assertEquals(6, substr_count($content, 'name="otp_digits[]"'));
        $this->assertStringContainsString('.otp-inputs', $content);
        $this->assertStringContainsString('max-width: 48px', $content);
    }
}
