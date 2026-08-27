<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Security-header regression tests.
 *
 * These tests verify that SecurityHeaders middleware always emits the
 * correct header values and that the CSP evolves safely — any weakening
 * (adding unsafe-eval, broadening connect-src, etc.) will cause a failure
 * and must be an explicit, reviewed change.
 */
class SecurityHeadersRegressionTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function getCSP(): string
    {
        return $this->get('/login')->headers->get('Content-Security-Policy', '');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Basic Security Headers
    // ─────────────────────────────────────────────────────────────────────────

    public function test_standard_security_headers_are_present()
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');
    }

    public function test_server_fingerprinting_headers_are_removed()
    {
        $response = $this->get('/login');

        $this->assertNull($response->headers->get('X-Powered-By'));
    }

    public function test_csp_header_is_present()
    {
        $response = $this->get('/login');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CSP — unsafe-eval must never be present
    // ─────────────────────────────────────────────────────────────────────────

    public function test_csp_does_not_contain_unsafe_eval()
    {
        $this->assertStringNotContainsString(
            "'unsafe-eval'",
            $this->getCSP(),
            "CSP must never include 'unsafe-eval'."
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CSP — nonce mechanics
    // ─────────────────────────────────────────────────────────────────────────

    public function test_csp_script_src_contains_nonce()
    {
        $csp = $this->getCSP();
        $this->assertMatchesRegularExpression(
            "/'nonce-[A-Za-z0-9+\/=]+'/",
            $csp,
            "script-src in CSP must contain a per-request nonce."
        );
    }

    public function test_nonce_differs_between_requests()
    {
        preg_match("/'nonce-([A-Za-z0-9+\/=]+)'/", $this->getCSP(), $m1);
        preg_match("/'nonce-([A-Za-z0-9+\/=]+)'/", $this->getCSP(), $m2);

        $this->assertNotEmpty($m1[1] ?? '', 'First request nonce is empty');
        $this->assertNotEmpty($m2[1] ?? '', 'Second request nonce is empty');
        $this->assertNotEquals($m1[1], $m2[1], 'CSP nonce must be unique per request.');
    }

    public function test_nonce_key_constant_is_stable()
    {
        // Changing NONCE_KEY breaks all Blade templates — guard it.
        $this->assertSame('csp_nonce', SecurityHeaders::NONCE_KEY);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CSP — connect-src must not be a wildcard
    // ─────────────────────────────────────────────────────────────────────────

    public function test_csp_connect_src_does_not_allow_arbitrary_http_https()
    {
        preg_match('/connect-src\s+([^;]+)/i', $this->getCSP(), $matches);
        // Tokenise on whitespace to get individual source expressions
        $tokens = preg_split('/\s+/', trim($matches[1] ?? ''));

        // A bare 'http:' or 'https:' scheme-source allows ALL origins of that scheme.
        // 'https://specific.host' is fine — only the bare scheme is dangerous.
        $this->assertNotContains('http:', $tokens, "connect-src must not allow bare http: scheme.");
        $this->assertNotContains('https:', $tokens, "connect-src must not allow bare https: scheme.");
        $this->assertNotContains('blob:', $tokens, "connect-src must not allow blob:.");
        $this->assertNotContains('data:', $tokens, "connect-src must not allow data:.");
    }

    public function test_csp_connect_src_allows_self_and_wss()
    {
        preg_match('/connect-src\s+([^;]+)/i', $this->getCSP(), $matches);
        $connectSrc = $matches[1] ?? '';

        $this->assertStringContainsString("'self'", $connectSrc);
        $this->assertStringContainsString('wss:', $connectSrc);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CSP — form-action must be self-only
    // ─────────────────────────────────────────────────────────────────────────

    public function test_csp_form_action_is_self_only()
    {
        preg_match("/form-action\s+([^;]+)/i", $this->getCSP(), $matches);
        $formAction = trim($matches[1] ?? '');

        $this->assertSame(
            "'self'",
            $formAction,
            "form-action must be restricted to 'self' only."
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CSP — object-src none and frame-ancestors self
    // ─────────────────────────────────────────────────────────────────────────

    public function test_csp_object_src_is_none()
    {
        preg_match("/object-src\s+([^;]+)/i", $this->getCSP(), $matches);
        $this->assertSame("'none'", trim($matches[1] ?? ''));
    }

    public function test_csp_frame_ancestors_is_self()
    {
        preg_match("/frame-ancestors\s+([^;]+)/i", $this->getCSP(), $matches);
        $this->assertSame("'self'", trim($matches[1] ?? ''));
    }

    public function test_csp_base_uri_is_self()
    {
        preg_match("/base-uri\s+([^;]+)/i", $this->getCSP(), $matches);
        $this->assertSame("'self'", trim($matches[1] ?? ''));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CSP — removed CDN allowlist items must stay removed
    // ─────────────────────────────────────────────────────────────────────────

    public function test_csp_script_src_does_not_allow_unpkg()
    {
        preg_match('/script-src\s+([^;]+)/i', $this->getCSP(), $matches);
        $this->assertStringNotContainsString(
            'unpkg.com',
            $matches[1] ?? '',
            "unpkg.com must not be in script-src."
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HSTS — not set on non-HTTPS local requests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_hsts_not_present_in_testing_environment()
    {
        // APP_ENV=testing (not production), plain HTTP → HSTS must not be set
        $hsts = $this->get('/login')->headers->get('Strict-Transport-Security');

        if ($hsts !== null) {
            // In non-production it may be set to max-age=0 for dev de-activation
            $this->assertStringStartsWith('max-age=0', $hsts);
        } else {
            $this->assertNull($hsts);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Proxy trust — X-Forwarded-Proto accepted from trusted proxy
    // ─────────────────────────────────────────────────────────────────────────

    public function test_app_accepts_x_forwarded_proto_from_trusted_proxy()
    {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-For'   => '127.0.0.1',
        ])->get('/login');

        $this->assertNotEquals(500, $response->status());
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API routes also receive security headers
    // ─────────────────────────────────────────────────────────────────────────

    public function test_api_responses_carry_security_headers()
    {
        $response = $this->getJson('/api/user');

        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
        $this->assertNotEmpty($response->headers->get('X-Content-Type-Options'));
        $this->assertStringNotContainsString("'unsafe-eval'", $response->headers->get('Content-Security-Policy', ''));
    }
}
