<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAdminIpWhitelisted;
use App\Http\Middleware\ResolveClientIpFromTrustedHeader;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminIpWhitelistTest extends TestCase
{
    use RefreshDatabase;

    public function test_ip_whitelist_uses_configured_edge_client_ip_header(): void
    {
        config([
            'app.env' => 'production',
            'app.client_ip_header' => 'CF-Connecting-IP',
        ]);
        Setting::set('admin_ip_whitelist', '203.0.113.25', false);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '10.0.0.10']);
        $request->headers->set('X-Forwarded-For', '198.51.100.99');
        $request->headers->set('CF-Connecting-IP', '203.0.113.25');

        $response = app(EnsureAdminIpWhitelisted::class)->handle($request, fn () => response('allowed'));

        $this->assertSame('allowed', $response->getContent());
    }

    public function test_ip_whitelist_fails_closed_when_configured_edge_ip_is_missing(): void
    {
        config([
            'app.env' => 'production',
            'app.client_ip_header' => 'CF-Connecting-IP',
        ]);
        Setting::set('admin_ip_whitelist', '203.0.113.25', false);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '10.0.0.10']);
        $request->headers->set('X-Forwarded-For', '203.0.113.25');

        try {
            app(EnsureAdminIpWhitelisted::class)->handle($request, fn () => response('allowed'));
            $this->fail('A missing configured edge IP must not fall back to the shared proxy address.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_trusted_edge_ip_is_used_for_request_ip_instead_of_forwarded_for(): void
    {
        config(['app.client_ip_header' => 'CF-Connecting-IP']);

        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '10.0.0.10']);
        $request->headers->set('X-Forwarded-For', '198.51.100.99');
        $request->headers->set('CF-Connecting-IP', '203.0.113.25');
        $request->headers->set('X-Forwarded-Proto', 'https');

        $response = app(ResolveClientIpFromTrustedHeader::class)->handle(
            $request,
            fn (Request $request) => response($request->ip())
        );

        $this->assertSame('203.0.113.25', $response->getContent());
        $this->assertTrue($request->isSecure());
    }
}
