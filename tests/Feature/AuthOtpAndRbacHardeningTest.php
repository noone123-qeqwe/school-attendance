<?php

namespace Tests\Feature;

use App\Models\Otp;
use App\Models\RecoveryCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AuthOtpAndRbacHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'student_number' => 'ID-' . uniqid(),
            'course' => 'BSIT',
            'year_level' => '1',
            'email' => 'user' . uniqid() . '@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'student',
            'is_active' => true,
        ], $overrides));
    }

    public function test_device_lock_options_allow_a_students_registered_passkey_with_required_verification(): void
    {
        $student = $this->createUser();
        $credential = $student->webauthnCredentials()->create([
            'credential_id' => 'dGVzdC1zdHVkZW50LXBhc3NrZXk',
            'public_key' => <<<'PEM'
                -----BEGIN PUBLIC KEY-----
                MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEJQ6V8r1g93isH5pxPxeW/P2bfUiX
                V4vPDB6MtEg4yFSZO+yifg44lONIPY5R85iDgt51BAo03UddM6kZz3jmFw==
                -----END PUBLIC KEY-----
                PEM,
        ]);
        $this->assertSame('fingerprint', $credential->refresh()->biometric_type);

        $this->postJson(route('webauthn.login.options'), [
            'identifier' => $student->student_number,
            'biometric_method' => 'device_lock',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('userVerification', 'required')
            ->assertJsonCount(1, 'allowCredentials')
            ->assertJsonPath('allowCredentials.0.id', $credential->credential_id)
            ->assertJsonPath('allowCredentials.0.type', 'public-key');
    }

    public function test_device_lock_options_do_not_distinguish_camera_only_and_unknown_accounts(): void
    {
        $student = $this->createUser();
        $student->webauthnCredentials()->create([
            'credential_id' => 'face_legacy_camera',
            'public_key' => '[0.1, 0.2, 0.3]',
            'biometric_type' => 'face',
        ]);

        $unknown = $this->postJson(route('webauthn.login.options'), [
            'identifier' => 'missing.account@gmail.com',
            'biometric_method' => 'device_lock',
        ])->assertNotFound()->assertJsonPath('code', 'BIOMETRIC_UNAVAILABLE');

        $this->postJson(route('webauthn.login.options'), [
            'identifier' => $student->student_number,
            'biometric_method' => 'device_lock',
        ])->assertNotFound()->assertExactJson($unknown->json());
    }

    #[TestWith([[]], 'without a section')]
    #[TestWith([['section_id' => 1]], 'with an earlier form')]
    public function test_student_registration_leaves_section_assignment_to_the_school(array $formFields): void
    {
        $email = 'new.student@gmail.com';
        $otp = Otp::generateForEmail($email, 'register');

        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee('name="section_id"', false);

        $this->postJson(route('otp.register.verify'), ['email' => $email, 'otp' => $otp->code])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->post(route('register.submit'), $formFields + [
            'first_name' => 'New',
            'surname' => 'Student',
            'role' => 'student',
            'email' => $email,
            'password' => 'StrongPass#2026',
            'password_confirmation' => 'StrongPass#2026',
            'student_number' => '2600251',
            'year_level' => '1',
            'semester' => '1',
            'terms' => '1',
        ])->assertRedirect(route('student.pending-approval'));

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'student_number' => '2600251',
            'section' => null,
            'student_registration_pending' => true,
        ]);
    }

    public function test_admin_web_2fa_does_not_penalize_valid_otp_before_matching_and_invalidates_after_limit(): void
    {
        Cache::flush();
        $admin = $this->createUser([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'email' => 'super.2fa@gmail.com',
        ]);

        $otp = Otp::generate($admin->id, 'admin_login');

        // Put user at MAX_VERIFY_ATTEMPTS - 1 prior failures; the next valid submission must succeed
        for ($i = 0; $i < Otp::MAX_VERIFY_ATTEMPTS - 1; $i++) {
            Otp::recordFailedVerify($admin->id, 'admin_login');
        }

        $response = $this->actingAs($admin)
            ->withSession(['admin_2fa_verified' => false])
            ->post(route('admin.2fa.verify'), ['otp' => $otp->code]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue((bool) $otp->fresh()->used);

        // Fresh OTP with MAX_VERIFY_ATTEMPTS wrong attempts gets invalidated immediately on threshold
        $second = Otp::generate($admin->id, 'admin_login');
        for ($i = 0; $i < Otp::MAX_VERIFY_ATTEMPTS; $i++) {
            $failResp = $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class)
                ->actingAs($admin)
                ->withSession(['admin_2fa_verified' => false])
                ->post(route('admin.2fa.verify'), ['otp' => '999999' === $second->code ? '000000' : '999999']);
            $failResp->assertSessionHasErrors('otp');
        }

        $this->assertTrue((bool) $second->fresh()->used);
        $this->assertTrue(Otp::hasExceededVerifyAttempts($admin->id, 'admin_login'));
    }

    public function test_api_otp_verify_for_forgot_password_leaves_code_unconsumed_for_reset_password(): void
    {
        Cache::flush();
        $student = $this->createUser([
            'email' => 'reset.student@gmail.com',
            'student_number' => '2026-9901',
        ]);

        $otp = Otp::generateForEmail($student->email, 'forgot_password', $student->id);

        $verifyResponse = $this->postJson('/api/otp/verify', [
            'email' => $student->email,
            'otp' => $otp->code,
            'purpose' => 'forgot_password',
        ]);

        $verifyResponse->assertOk()->assertJsonPath('success', true);
        $this->assertFalse((bool) $otp->fresh()->used);

        $resetResponse = $this->postJson('/api/reset-password', [
            'identifier' => $student->email,
            'otp' => $otp->code,
            'password' => 'NewStrongPass#2026',
            'password_confirmation' => 'NewStrongPass#2026',
        ]);

        $resetResponse->assertOk()->assertJsonPath('status', 'success');
        $this->assertTrue((bool) $otp->fresh()->used);
        $this->assertTrue(Hash::check('NewStrongPass#2026', (string) $student->fresh()->password));
    }

    public function test_recovery_code_login_resolves_identifier_safely_enforces_lockout_and_logs_in(): void
    {
        Cache::flush();
        $student = $this->createUser([
            'student_number' => '2026-3001',
            'email' => 'rec.student@gmail.com',
        ]);

        RecoveryCode::create([
            'user_id' => $student->id,
            'code' => Hash::make('ABCD-1234'),
            'used' => false,
        ]);

        // Wrong recovery codes increment lockout counter cleanly
        for ($i = 0; $i < 5; $i++) {
            $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class)
                ->from('/login')->post(route('recovery.login'), [
                    'identifier' => '2026-3001',
                    'recovery_code' => 'WRONG-CODE',
                ])->assertSessionHasErrors('identifier');
        }

        // Valid code while locked out is rejected by AccountLockoutService
        $lockedResp = $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class)
            ->from('/login')->post(route('recovery.login'), [
                'identifier' => '2026-3001',
                'recovery_code' => 'ABCD-1234',
            ]);
        $lockedResp->assertSessionHasErrors('identifier');
        $this->assertGuest();

        Cache::flush();

        // Valid identifier + valid recovery code works and binds device cleanly
        $okResp = $this->post(route('recovery.login'), [
            'identifier' => '2026-3001',
            'recovery_code' => 'ABCD-1234',
        ]);
        $okResp->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($student);
    }

    public function test_data_entry_admin_cannot_access_super_admin_or_audit_routes_and_mobile_admin_requires_2fa(): void
    {
        $dataEntry = $this->createUser([
            'role' => 'admin',
            'admin_sub_role' => 'data_entry',
            'email' => 'entry@gmail.com',
        ]);

        $superAdmin = $this->createUser([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'email' => 'super@gmail.com',
        ]);

        $this->actingAs($dataEntry)->get(route('admin.admins'))->assertForbidden();
        $this->actingAs($dataEntry)->get(route('admin.admin.create'))->assertForbidden();
        $this->actingAs($dataEntry)->get(route('admin.activity.log'))->assertForbidden();
        $this->actingAs($dataEntry)->get(route('admin.activity.log.export'))->assertForbidden();
        $this->actingAs($dataEntry)->get(route('admin.settings'))->assertForbidden();

        // Clear session password hash before switching authenticated users under AuthenticateSession
        $this->flushSession();

        // Unverified 2FA session on mobile admin routes redirects to 2FA prompt
        $this->actingAs($superAdmin)
            ->withSession(['admin_2fa_verified' => false])
            ->get(route('mobile.dashboard'))
            ->assertRedirect(route('admin.2fa.form'));

        $this->actingAs($superAdmin)
            ->withSession(['admin_2fa_verified' => false])
            ->get(route('mobile.settings'))
            ->assertRedirect(route('admin.2fa.form'));

        // Verified super admin succeeds
        $this->actingAs($superAdmin)->get(route('admin.admins'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.activity.log'))->assertOk();
    }

    public function test_device_management_routes_enforce_rate_limiting(): void
    {
        $student = $this->createUser([
            'role' => 'student',
            'student_number' => '2026-4401',
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($student)->postJson(route('device.bind'), [
                'device_name' => 'Phone',
                'password' => 'StrongPass#12345',
            ])->assertStatus(200);
        }

        $this->actingAs($student)->postJson(route('device.bind'), [
            'device_name' => 'Phone',
            'password' => 'StrongPass#12345',
        ])->assertStatus(429);
    }

    public function test_api_admin_login_otp_does_not_penalize_before_match(): void
    {
        Cache::flush();
        $admin = $this->createUser([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'email' => 'api.admin@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
        ]);

        $otp = Otp::generate($admin->id, 'admin_api_login');
        for ($i = 0; $i < Otp::MAX_VERIFY_ATTEMPTS - 1; $i++) {
            Otp::recordFailedVerify($admin->id, 'admin_api_login');
        }

        $this->postJson('/api/login', [
            'identifier' => $admin->email,
            'password' => 'StrongPass#12345',
            'otp' => $otp->code,
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->assertTrue((bool) $otp->fresh()->used);
        $this->assertFalse(Otp::hasExceededVerifyAttempts($admin->id, 'admin_api_login'));
    }
}
