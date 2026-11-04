<?php

namespace Tests\Unit;

use App\Services\AccountLockoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_login_aliases_share_a_hashed_account_and_ip_limit_key(): void
    {
        $limiter = RateLimiter::limiter('login');

        $studentIdRequest = Request::create('/api/login', 'POST', [
            'student_id' => ' User@School.edu ',
        ], [], [], ['REMOTE_ADDR' => '203.0.113.10']);
        $emailRequest = Request::create('/api/login', 'POST', [
            'email' => 'user@school.edu',
        ], [], [], ['REMOTE_ADDR' => '203.0.113.10']);

        $studentIdLimits = $limiter($studentIdRequest);
        $emailLimits = $limiter($emailRequest);

        $this->assertSame($studentIdLimits[0]->key, $emailLimits[0]->key);
        $this->assertSame($studentIdLimits[1]->key, $emailLimits[1]->key);
        $this->assertStringNotContainsString('user@school.edu', $studentIdLimits[0]->key);
        $this->assertStringNotContainsString('203.0.113.10', $studentIdLimits[1]->key);
    }

    public function test_web_login_aliases_share_a_limit_key(): void
    {
        $limiter = RateLimiter::limiter('login');

        $phoneRequest = Request::create('/login', 'POST', [
            'phone' => '5551234',
        ], [], [], ['REMOTE_ADDR' => '203.0.113.10']);
        $emailRequest = Request::create('/login', 'POST', [
            'email' => '5551234',
        ], [], [], ['REMOTE_ADDR' => '203.0.113.10']);

        $this->assertSame($limiter($phoneRequest)[0]->key, $limiter($emailRequest)[0]->key);
    }

    public function test_private_ip_bursts_do_not_trigger_ip_lockout(): void
    {
        $service = new AccountLockoutService();

        for ($i = 0; $i < AccountLockoutService::IP_MAX_ATTEMPTS + 5; $i++) {
            $result = $service->recordFailedAttempt('student-' . $i, '192.168.1.25');
        }

        $this->assertFalse($result['locked']);
        $this->assertFalse($service->isLocked('another-student', '192.168.1.25'));
    }

    public function test_only_valid_private_or_loopback_addresses_get_ip_lockout_exemption(): void
    {
        $service = new AccountLockoutService();

        $this->assertTrue($service->isPrivateOrLoopbackIp('10.5.4.3'));
        $this->assertTrue($service->isPrivateOrLoopbackIp('::ffff:127.0.0.1'));
        $this->assertFalse($service->isPrivateOrLoopbackIp('203.0.113.1'));
        $this->assertFalse($service->isPrivateOrLoopbackIp('not-an-ip'));
    }
}
