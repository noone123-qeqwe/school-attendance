<?php

namespace App\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AccountLockoutService
{
    public const MAX_ATTEMPTS = 5;
    public const LOCKOUT_SECONDS = 900; // 15 minutes
    public const IP_MAX_ATTEMPTS = 50; // Higher threshold for shared networks/Wi-Fi

    /**
     * Check if an IP address is a private, local, or loopback network address.
     */
    public function isPrivateOrLoopbackIp(?string $ip): bool
    {
        $clean = trim((string) $ip);
        if ($clean === '') {
            return false;
        }

        if (strtolower($clean) === 'localhost') {
            return true;
        }

        $packedIp = @inet_pton($clean);
        if ($packedIp === false) {
            // Invalid or missing client IPs must not get the private-network exemption.
            return false;
        }

        // Treat IPv4-mapped IPv6 values the same as their native IPv4 address.
        if (strlen($packedIp) === 16 && substr($packedIp, 0, 12) === str_repeat("\0", 10) . "\xff\xff") {
            $packedIp = substr($packedIp, 12);
        }

        $ranges = strlen($packedIp) === 4
            ? [
                ['10.0.0.0', 8],
                ['172.16.0.0', 12],
                ['192.168.0.0', 16],
                ['127.0.0.0', 8],
                ['169.254.0.0', 16],
                ['0.0.0.0', 8],
            ]
            : [
                ['::1', 128],
                ['::', 128],
                ['fc00::', 7],
                ['fe80::', 10],
            ];

        foreach ($ranges as [$network, $prefix]) {
            $packedNetwork = inet_pton($network);
            if ($packedNetwork !== false && $this->ipMatchesNetwork($packedIp, $packedNetwork, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize the account identifier (email, student_number, employee_id).
     */
    private function normalizeIdentifier(?string $identifier): string
    {
        return strtolower(trim((string) $identifier));
    }

    /**
     * Check if the specific account identifier is locked out.
     */
    public function isAccountLocked(?string $identifier): bool
    {
        $accKey = $this->accountKey($identifier);
        return $accKey && (int) Cache::get($accKey . ':attempts', 0) >= self::MAX_ATTEMPTS;
    }

    /**
     * Check if the account or IP is currently locked out.
     */
    public function isLocked(?string $identifier, ?string $ip): bool
    {
        if ($this->isAccountLocked($identifier)) {
            return true;
        }

        // Loopback and private campus subnets are immune to blanket IP lockouts
        if ($this->isPrivateOrLoopbackIp($ip)) {
            return false;
        }

        $ipKey = $this->ipKey($ip);
        if ($ipKey && (int) Cache::get($ipKey . ':attempts', 0) >= self::IP_MAX_ATTEMPTS) {
            return true;
        }

        return false;
    }

    /**
     * Get remaining lockout seconds for an account or IP.
     */
    public function getRemainingSeconds(?string $identifier, ?string $ip): int
    {
        $accKey = $this->accountKey($identifier);
        $ipKey  = $this->ipKey($ip);

        $accExpiry = $accKey ? (int) Cache::get($accKey . ':expires_at', 0) : 0;
        $ipExpiry  = $ipKey ? (int) Cache::get($ipKey . ':expires_at', 0) : 0;

        $now = now()->timestamp;
        $accRemaining = max(0, $accExpiry - $now);
        $ipRemaining  = max(0, $ipExpiry - $now);

        return max($accRemaining, $ipRemaining, 0);
    }

    /**
     * Get remaining login attempts before lockout.
     */
    public function getRemainingAttempts(?string $identifier, ?string $ip): int
    {
        $accKey = $this->accountKey($identifier);
        $currentAcc = $accKey ? (int) Cache::get($accKey . ':attempts', 0) : 0;
        
        return max(0, self::MAX_ATTEMPTS - $currentAcc);
    }

    /**
     * Record a failed login attempt with progressive delay.
     */
    public function recordFailedAttempt(?string $identifier, ?string $ip): array
    {
        $accKey = $this->accountKey($identifier);
        $ipKey  = $this->ipKey($ip);

        $accAttempts = 0;
        if ($accKey) {
            $accAttempts = $this->incrementCounter($accKey, self::MAX_ATTEMPTS);
        }

        $ipAttempts = 0;
        if ($ipKey) {
            $ipAttempts = $this->incrementCounter($ipKey, self::IP_MAX_ATTEMPTS);
        }

        $ipLocked = $ipAttempts >= self::IP_MAX_ATTEMPTS && !$this->isPrivateOrLoopbackIp($ip);
        $isLocked = ($accAttempts >= self::MAX_ATTEMPTS) || $ipLocked;

        if ($isLocked) {
            Log::warning('Account/IP locked out due to excessive failed attempts', [
                'acc_attempts' => $accAttempts,
                'ip_attempts' => $ipAttempts,
            ]);
        } else {
            // Progressive delay on repeated failures (after 2 failed attempts)
            $effectiveAttempts = max($accAttempts, $ipAttempts);
            if ($effectiveAttempts >= 3 && !app()->environment('testing')) {
                $delayMicroseconds = min(1000000, ($effectiveAttempts - 2) * 300000);
                usleep($delayMicroseconds);
            }
        }

        return [
            'locked' => $isLocked,
            'attempts' => $accAttempts,
            'remaining_attempts' => max(0, self::MAX_ATTEMPTS - $accAttempts),
            'lockout_seconds' => self::LOCKOUT_SECONDS,
        ];
    }

    /**
     * Clear failed login attempts after successful authentication.
     */
    public function clear(?string $identifier, ?string $ip): void
    {
        $accKey = $this->accountKey($identifier);
        $ipKey  = $this->ipKey($ip);

        if ($accKey) {
            Cache::forget($accKey . ':attempts');
            Cache::forget($accKey . ':expires_at');
        }

        if ($ipKey) {
            Cache::forget($ipKey . ':attempts');
            Cache::forget($ipKey . ':expires_at');
        }
    }

    private function accountKey(?string $identifier): ?string
    {
        $clean = $this->normalizeIdentifier($identifier);
        return $clean !== '' ? 'lockout:acc:' . $this->fingerprint($clean) : null;
    }

    private function ipKey(?string $ip): ?string
    {
        $clean = trim((string) $ip);
        return $clean !== '' ? 'lockout:ip:' . $this->fingerprint($clean) : null;
    }

    private function fingerprint(string $value): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            $key = 'attendance-lockout';
        }

        return hash_hmac('sha256', $value, $key);
    }

    /**
     * Increment a lockout window under a cache lock so concurrent failures are
     * counted instead of overwriting one another on file and database stores.
     */
    private function incrementCounter(string $key, int $lockThreshold): int
    {
        try {
            return Cache::lock($key . ':mutex', 10)->block(2, function () use ($key) {
                $attemptKey = $key . ':attempts';
                $attempts = (int) Cache::get($attemptKey, 0) + 1;
                $expiresAt = now()->addSeconds(self::LOCKOUT_SECONDS)->timestamp;

                Cache::put($attemptKey, $attempts, self::LOCKOUT_SECONDS);
                Cache::put($key . ':expires_at', $expiresAt, self::LOCKOUT_SECONDS);

                return $attempts;
            });
        } catch (LockTimeoutException) {
            // Under lock-store contention, fail closed rather than drop a failed
            // attempt and leave the account/IP unprotected.
            $attemptKey = $key . ':attempts';
            $attempts = max((int) Cache::get($attemptKey, 0) + 1, $lockThreshold);
            Cache::put($attemptKey, $attempts, self::LOCKOUT_SECONDS);
            Cache::put($key . ':expires_at', now()->addSeconds(self::LOCKOUT_SECONDS)->timestamp, self::LOCKOUT_SECONDS);

            Log::warning('Rate limit counter lock timed out; applying a temporary fail-closed lockout', [
                'counter' => hash('sha256', $key),
            ]);

            return $attempts;
        }
    }

    private function ipMatchesNetwork(string $ip, string $network, int $prefix): bool
    {
        $wholeBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        if ($wholeBytes > 0 && substr($ip, 0, $wholeBytes) !== substr($network, 0, $wholeBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainingBits)) & 0xff;

        return (ord($ip[$wholeBytes]) & $mask) === (ord($network[$wholeBytes]) & $mask);
    }
}
