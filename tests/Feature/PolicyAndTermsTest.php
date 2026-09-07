<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PolicyAndTermsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
    }

    public function test_public_privacy_policy_page_loads_with_all_sections_and_metadata()
    {
        $response = $this->get('/privacy');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check titles and metadata
        $this->assertStringContainsString('Smart Classroom Attendance System — Privacy Notice', $content);
        $this->assertStringContainsString('Version 1.0', $content);
        $this->assertStringContainsString('Effective Date:', $content);
        $this->assertStringContainsString('Last Updated:', $content);
        $this->assertStringContainsString('Osmeña Colleges', $content);

        // Check required specific sections & system-accurate features
        $this->assertStringContainsString('1. Introduction', $content);
        $this->assertStringContainsString('2. Purpose of this Privacy Notice', $content);
        $this->assertStringContainsString('3. Information We Collect', $content);
        $this->assertStringContainsString('Student Information', $content);
        $this->assertStringContainsString('Teacher & Instructor Information', $content);
        $this->assertStringContainsString('Administrator Information', $content);
        $this->assertStringContainsString('Biometric / WebAuthn Credentials', $content);
        $this->assertStringContainsString('Email Verification and One-Time Passcode', $content);
        $this->assertStringContainsString('Dynamic QR Code & Anti-Fraud Session Tokens', $content);
        $this->assertStringContainsString('GPS Coordinates', $content);
        $this->assertStringContainsString('Device Binding Data', $content);
        $this->assertStringContainsString('Excuse Submissions, Letters & Attachments', $content);
        $this->assertStringContainsString('User Privacy Rights (Data Privacy Act of 2012)', $content);
        $this->assertStringContainsString('Republic Act No. 10173', $content);
        $this->assertStringContainsString('admin@osmena.edu', $content);
    }

    public function test_public_terms_and_conditions_page_loads_with_anti_fraud_rules()
    {
        $response = $this->get('/terms');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check titles and metadata
        $this->assertStringContainsString('Smart Classroom Attendance System — Terms &amp; Conditions', $content);
        $this->assertStringContainsString('Version 1.0', $content);
        $this->assertStringContainsString('Effective Date:', $content);
        $this->assertStringContainsString('Last Updated:', $content);

        // Check required sections and strict anti-fraud policy
        $this->assertStringContainsString('Acceptance of Terms', $content);
        $this->assertStringContainsString('Eligibility and Authorized Users', $content);
        $this->assertStringContainsString('Account Security and User Responsibilities', $content);
        $this->assertStringContainsString('Attendance Rules & Strict Anti-Fraud Policy', $content);
        $this->assertStringContainsString('Proxy Attendance', $content);
        $this->assertStringContainsString('Remote Sharing of QR Codes', $content);
        $this->assertStringContainsString('GPS Spoofing & Location Emulation', $content);
        $this->assertStringContainsString('Attendance Scanning Protocols', $content);
        $this->assertStringContainsString('Official Records & Dispute Resolution', $content);
        $this->assertStringContainsString('Republic of the Philippines', $content);
        $this->assertStringContainsString('admin@osmena.edu', $content);
    }

    public function test_registration_requires_explicit_acceptance_of_terms_and_privacy()
    {
        session(['reg_email_verified' => 'newstudent@example.com']);

        // Attempt registration without accepting terms
        $response = $this->from('/register')->post('/register', [
            'name'                  => 'John Doe',
            'first_name'            => 'John',
            'surname'               => 'Doe',
            'email'                 => 'newstudent@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'student',
            'student_number'        => '0703999',
            'course'                => 'BSCS',
            'year_level'            => 1,
            'semester'              => '1',
            // 'terms' omitted!
        ]);

        $response->assertSessionHasErrors(['terms' => 'You must read and agree to the Privacy Notice and Terms & Conditions to create an account.']);
        $this->assertDatabaseMissing('users', ['email' => 'newstudent@example.com']);
    }

    public function test_registration_succeeds_when_terms_are_accepted()
    {
        session(['reg_email_verified' => 'approvedstudent@example.com']);

        $response = $this->from('/register')->post('/register', [
            'name'                  => 'Jane Smith',
            'first_name'            => 'Jane',
            'surname'               => 'Smith',
            'email'                 => 'approvedstudent@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'student',
            'student_number'        => '0703888',
            'course'                => 'BSCS',
            'year_level'            => 2,
            'semester'              => '1',
            'terms'                 => '1', // Accepted!
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('users', [
            'email'          => 'approvedstudent@example.com',
            'student_number' => '0703888',
            'role'           => 'student',
        ]);
    }

    public function test_admin_can_access_policy_editor_and_update_privacy_notice()
    {
        $admin = User::factory()->create([
            'email'    => 'admin@osmena.edu',
            'role'     => 'admin',
            'password' => Hash::make('AdminPass123!'),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_2fa_pending' => false])
            ->get('/admin/policies');

        $response->assertStatus(200);
        $response->assertSee('Policy & Legal Management');
        $response->assertSee('Privacy Notice');
        $response->assertSee('Terms & Conditions');

        // Update Privacy Policy
        $updateRes = $this->actingAs($admin)
            ->post('/admin/policies', [
                'policy_type'            => 'privacy',
                'privacy_version'        => '2.1',
                'privacy_effective_date' => '2026-09-01',
                'privacy_content'        => '<div class="policy-section"><h3>Updated Institutional Privacy Policy</h3><p>Custom policy content verified for Osmeña Colleges.</p></div>',
            ]);

        $updateRes->assertRedirect(route('admin.policies.edit', ['tab' => 'privacy']));
        $updateRes->assertSessionHas('success');

        // Verify public page displays updated content
        $publicRes = $this->get('/privacy');
        $publicRes->assertStatus(200);
        $publicRes->assertSee('Version 2.1');
        $publicRes->assertSee('September 1, 2026');
        $publicRes->assertSee('Updated Institutional Privacy Policy');
    }

    public function test_admin_can_update_terms_and_conditions_and_reset_to_default()
    {
        $admin = User::factory()->create([
            'email'    => 'admin@osmena.edu',
            'role'     => 'admin',
            'password' => Hash::make('AdminPass123!'),
        ]);

        // Update Terms
        $updateRes = $this->actingAs($admin)
            ->post('/admin/policies', [
                'policy_type'          => 'terms',
                'terms_version'        => '3.0',
                'terms_effective_date' => '2026-09-08',
                'terms_content'        => '<div class="policy-section"><h3>Updated Institutional Terms of Service</h3><p>Custom terms content strictly enforced.</p></div>',
            ]);

        $updateRes->assertRedirect(route('admin.policies.edit', ['tab' => 'terms']));
        $updateRes->assertSessionHas('success');

        // Verify public page displays updated terms
        $publicRes = $this->get('/terms');
        $publicRes->assertStatus(200);
        $publicRes->assertSee('Version 3.0');
        $publicRes->assertSee('September 8, 2026');
        $publicRes->assertSee('Updated Institutional Terms of Service');

        // Reset Terms to Default
        $resetRes = $this->actingAs($admin)
            ->post('/admin/policies/reset', [
                'type' => 'terms',
            ]);

        $resetRes->assertRedirect(route('admin.policies.edit', ['tab' => 'terms']));
        $resetRes->assertSessionHas('success');

        // Public page should return to default
        $resetPublicRes = $this->get('/terms');
        $resetPublicRes->assertStatus(200);
        $resetPublicRes->assertSee('Version 1.0');
        $this->assertStringContainsString('Attendance Rules & Strict Anti-Fraud Policy', $resetPublicRes->getContent());
    }
}
