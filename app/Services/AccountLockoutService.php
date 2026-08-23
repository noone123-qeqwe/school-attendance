<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AccountLockoutService
{
    public const MAX_ATTEMPTS = 5;
    public const LOCKOUT_SECONDS = 900; // 15 minutes
    public const IP_MAX_ATTEMPTS = 15;

    /**
     * Normalize the account identifier (email, student_number, employee_id).
     */
    private function normalizeIdentifier(?string $identifier): string
    {
        return strtolower(trim((string) $identifier));
    }

    /**
     * Check if the account or IP is currently locked out.
     */
    public function isLocked(?string $identifier, ?string $ip): bool
    {
        $accKey = $this->accountKey($identifier);
        $ipKey  = $this->ipKey($ip);

        if ($accKey && (int) Cache::get($accKey . ':attempts', 0) >= self::MAX_ATTEMPTS) {
            return true;
        }

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
     * Record a failed login attempt.
     */
    public function recordFailedAttempt(?string $identifier, ?string $ip): array
    {
        $accKey = $this->accountKey($identifier);
        $ipKey  = $this->ipKey($ip);
        $expiresAt = now()->addSeconds(self::LOCKOUT_SECONDS)->timestamp;

        $accAttempts = 0;
        if ($accKey) {
            $accAttempts = (int) Cache::get($accKey . ':attempts', 0) + 1;
            Cache::put($accKey . ':attempts', $accAttempts, self::LOCKOUT_SECONDS);
            Cache::put($accKey . ':expires_at', $expiresAt, self::LOCKOUT_SECONDS);
        }

        $ipAttempts = 0;
        if ($ipKey) {
            $ipAttempts = (int) Cache::get($ipKey . ':attempts', 0) + 1;
            Cache::put($ipKey . ':attempts', $ipAttempts, self::LOCKOUT_SECONDS);
            Cache::put($ipKey . ':expires_at', $expiresAt, self::LOCKOUT_SECONDS);
        }

        $isLocked = ($accAttempts >= self::MAX_ATTEMPTS) || ($ipAttempts >= self::IP_MAX_ATTEMPTS);

        if ($isLocked) {
            Log::warning('Account/IP locked out due to excessive failed attempts', [
                'identifier' => $identifier,
                'ip' => $ip,
                'acc_attempts' => $accAttempts,
                'ip_attempts' => $ipAttempts,
            ]);
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
        return $clean !== '' ? 'lockout:acc:' . sha1($clean) : null;
    }

    private function ipKey(?string $ip): ?string
    {
        $clean = trim((string) $ip);
        return $clean !== '' ? 'lockout:ip:' . sha1($clean) : null;
    }
}
