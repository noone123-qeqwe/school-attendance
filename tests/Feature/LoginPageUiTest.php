<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginPageUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_form_labels_fields_and_presents_password_sign_in_before_biometrics(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertSee('css/auth-login.css', false);

        $page = new \DOMDocument();
        $page->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($page);
        $form = $xpath->query('//form[@id="loginForm"]')->item(0);
        $this->assertNotNull($form);
        $this->assertSame('Welcome back', $xpath->evaluate('normalize-space(//h1)'));
        $this->assertSame('Student ID, Email, or Mobile', $xpath->evaluate('normalize-space(.//label[@for="idInput"])', $form));
        $this->assertSame('Password', $xpath->evaluate('normalize-space(.//label[@for="loginPassword"])', $form));
        $this->assertSame('Keep me signed in on this device', $xpath->evaluate('normalize-space(.//label[@for="rememberMe" or .//input[@id="rememberMe"]])', $form));
        $this->assertSame('Sign in with Biometrics', $xpath->evaluate('normalize-space(.//*[@id="fpLabel"])', $form));

        $controls = $xpath->query('.//*[
            self::input[@id="idInput" or @id="loginPassword" or @id="rememberMe"]
            or self::a[@id="forgotPasswordLink"]
            or self::button[@id="loginSubmitBtn" and @type="submit"]
            or self::button[@id="fpRowBtn" and @type="button"]
            or text()[translate(normalize-space(.), "or", "OR") = "OR"]
        ]', $form);

        $order = [];
        foreach ($controls as $control) {
            $order[] = strcasecmp(trim($control->textContent), 'OR') === 0 ? 'OR' : $control->getAttribute('id');
        }
        $this->assertSame(['idInput', 'forgotPasswordLink', 'loginPassword', 'rememberMe', 'loginSubmitBtn', 'OR', 'fpRowBtn'], $order);
    }

    public function test_login_page_offers_fingerprint_face_and_password_methods(): void
    {
        $response = $this->get(route('login'))->assertOk();
        $response->assertSee('id="bioModalMethodsWrap"', false);
        $response->assertSee('id="bioModalMethodsList"', false);
        $response->assertSee('id="bioModalFaceWrap"', false);
        $response->assertSee('id="bioFaceCameraVideo"', false);
        $response->assertSee("name: 'Fingerprint verification'", false);
        $response->assertSee("name: 'Face verification'", false);
        $response->assertSee('data-face-login-enabled=', false);
        $response->assertDontSee('Checking biometric options', false);
        $response->assertDontSee('Use passkey from another device', false);
        $response->assertDontSee('Set up fingerprint on this device', false);
        $response->assertSee("name: 'Enter password'", false);

        $csp = (string) $response->headers->get('Content-Security-Policy', '');
        $this->assertStringContainsString("'wasm-unsafe-eval'", $csp);
    }

    public function test_login_styles_include_a_static_seal_glass_surface_and_biometric_methods(): void
    {
        $css = file_get_contents(public_path('css/auth-login.css'));
        $this->assertNotFalse($css);

        $this->assertStringNotContainsString('floatLogo', $css);
        $this->assertStringContainsString('.glass-logo', $css);
        $this->assertStringContainsString('backdrop-filter:', $css);
        $this->assertStringContainsString('.bio-method-icon-box--fingerprint', $css);
        $this->assertStringContainsString('.bio-method-icon-box--face', $css);
        $this->assertStringContainsString('.bio-method-icon-box--password', $css);

        $this->assertSame(substr_count($css, '{'), substr_count($css, '}'), 'auth-login.css must keep balanced braces.');
    }
}
