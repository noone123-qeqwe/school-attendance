<?php

namespace Tests\Unit;

use App\Services\AccountLockoutService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountLockoutServiceTest extends TestCase
{
    use RefreshDatabase;

    private AccountLockoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountLockoutService();
        Cache::flush();
    }

    // --- isPrivateOrLoopbackIp ---

    public function test_localhost_is_private(): void
    {
        $this->assertTrue($this->service->isPrivateOrLoopbackIp('127.0.0.1'));
        $this->assertTrue($this->service->isPrivateOrLoopbackIp('::1'));
        $this->assertTrue($this->service->isPrivateOrLoopbackIp('localhost'));
    }

    public function test_private_ip_is_detected(): void
    {
        $this->assertTrue($this->service->isPrivateOrLoopbackIp('192.168.1.1'));
        $this->assertTrue($this->service->isPrivateOrLoopbackIp('10.0.0.1'));
        $this->assertTrue($this->service->isPrivateOrLoopbackIp('172.16.0.1'));
    }

    public function test_public_ip_is_not_private(): void
    {
        $this->assertFalse($this->service->isPrivateOrLoopbackIp('8.8.8.8'));
        $this->assertFalse($this->service->isPrivateOrLoopbackIp('203.0.113.1'));
    }

    public function test_null_ip_is_not_private(): void
    {
        $this->assertFalse($this->service->isPrivateOrLoopbackIp(null));
    }

    // --- Account lockout flow ---

    public function test_account_is_not_locked_initially(): void
    {
        $this->assertFalse($this->service->isLocked('user@test.com', '1.2.3.4'));
        $this->assertFalse($this->service->isAccountLocked('user@test.com'));
    }

    public function test_account_locks_after_max_attempts(): void
    {
        for ($i = 0; $i < AccountLockoutService::MAX_ATTEMPTS; $i++) {
            $result = $this->service->recordFailedAttempt('user@test.com', '8.8.8.8');
        }

        $this->assertTrue($result['locked']);
        $this->assertTrue($this->service->isAccountLocked('user@test.com'));
        $this->assertTrue($this->service->isLocked('user@test.com', '8.8.8.8'));
    }

    public function test_remaining_attempts_decreases(): void
    {
        $this->assertEquals(AccountLockoutService::MAX_ATTEMPTS, $this->service->getRemainingAttempts('user@test.com', '8.8.8.8'));

        $this->service->recordFailedAttempt('user@test.com', '8.8.8.8');
        $this->assertEquals(AccountLockoutService::MAX_ATTEMPTS - 1, $this->service->getRemainingAttempts('user@test.com', '8.8.8.8'));
    }

    public function test_clear_resets_attempts(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->service->recordFailedAttempt('user@test.com', '8.8.8.8');
        }

        $this->service->clear('user@test.com', '8.8.8.8');

        $this->assertFalse($this->service->isAccountLocked('user@test.com'));
        $this->assertEquals(AccountLockoutService::MAX_ATTEMPTS, $this->service->getRemainingAttempts('user@test.com', '8.8.8.8'));
    }

    public function test_private_ip_immune_to_ip_lockout(): void
    {
        // Even with many failures from private IP, IP-level lockout doesn't apply
        for ($i = 0; $i < AccountLockoutService::IP_MAX_ATTEMPTS + 5; $i++) {
            $this->service->recordFailedAttempt('different-user-' . $i . '@test.com', '192.168.1.1');
        }

        // Private IP is immune to IP lockout, but individual accounts can still be locked
        $this->assertFalse($this->service->isLocked('new-user@test.com', '192.168.1.1'));
    }

    public function test_lockout_has_remaining_seconds(): void
    {
        for ($i = 0; $i < AccountLockoutService::MAX_ATTEMPTS; $i++) {
            $this->service->recordFailedAttempt('user@test.com', '8.8.8.8');
        }

        $remaining = $this->service->getRemainingSeconds('user@test.com', '8.8.8.8');
        $this->assertGreaterThan(0, $remaining);
        $this->assertLessThanOrEqual(AccountLockoutService::LOCKOUT_SECONDS, $remaining);
    }
}
