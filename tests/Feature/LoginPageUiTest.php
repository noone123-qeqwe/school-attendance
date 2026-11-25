<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginPageUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_static_logo_biometric_button_and_method_options(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('css/auth-login.css', false);
        $response->assertSee('class="glass-logo-wrap"', false);
        $response->assertSee('class="glass-logo"', false);
        $response->assertDontSee('anim-fade-up anim-d1', false);

        $response->assertSee('id="loginForm"', false);
        $response->assertSee('id="idInput"', false);
        $response->assertSee('id="loginPassword"', false);
        $response->assertSee('id="fpRowBtn"', false);
        $response->assertSee('id="loginSubmitBtn"', false);
        $response->assertSee('class="glass-input-wrap glass-input-wrap--password anim-fade-up anim-d5"', false);
        $response->assertSee('class="d-flex align-items-center justify-content-between glass-remember-row anim-fade-up anim-d5"', false);

        // Biometrics button is visible without waiting on passkey gating and renamed from passkey
        $response->assertSee('<div id="passkeyControls">', false);
        $response->assertSee('Sign in with biometrics', false);
        $response->assertSee('Fingerprint, face verification, or password', false);
        $response->assertDontSee('Sign in with a passkey', false);

        // Clicking opens the biometric options list containing Fingerprint, Face verification, and Enter password
        $response->assertSee('id="bioModalMethodsWrap"', false);
        $response->assertSee('id="bioModalMethodsList"', false);
        $response->assertSee("name: 'Fingerprint'", false);
        $response->assertSee("name: 'Face verification'", false);
        $response->assertSee("name: 'Enter password'", false);
    }

    public function test_login_css_removes_moving_logo_animation_and_enlarges_desktop_form(): void
    {
        $css = file_get_contents(public_path('css/auth-login.css'));
        $this->assertNotFalse($css);

        $this->assertStringNotContainsString('@keyframes floatLogo', $css);
        $this->assertStringNotContainsString('floatLogo', $css);

        $this->assertMatchesRegularExpression('/\.glass-logo\s*\{[^}]*\}/s', $css);
        preg_match('/\.glass-logo\s*\{([^}]*)\}/s', $css, $logoMatch);
        $this->assertNotEmpty($logoMatch[1] ?? '');
        $this->assertStringNotContainsString('animation:', $logoMatch[1]);
        $this->assertDoesNotMatchRegularExpression('/\.glass-logo:hover\s*\{/', $css);

        $this->assertStringContainsString('@media (min-width: 769px)', $css);
        $this->assertStringContainsString('max-width: 520px;', $css);
        $this->assertStringContainsString('@media (min-width: 1200px)', $css);
        $this->assertStringContainsString('max-width: 540px;', $css);
        $this->assertStringNotContainsString('max-width: 420px;', $css);
        $this->assertStringContainsString('.bio-method-icon-box--fingerprint', $css);
        $this->assertStringContainsString('.bio-method-icon-box--face', $css);
        $this->assertStringContainsString('.bio-method-icon-box--password', $css);
    }
}
