<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

class FaceVerificationAttemptLimiter
{
    public const MAX_ATTEMPTS = 5;

    public function isLocked(User $user, int $sessionId): bool
    {
        return RateLimiter::tooManyAttempts($this->key($user, $sessionId), self::MAX_ATTEMPTS);
    }

    public function recordFailure(User $user, int $sessionId): int
    {
        $key = $this->key($user, $sessionId);
        RateLimiter::hit($key, $this->decaySeconds());

        return RateLimiter::attempts($key);
    }

    public function secondsUntilRetry(User $user, int $sessionId): int
    {
        return RateLimiter::availableIn($this->key($user, $sessionId));
    }

    public function clear(User $user, int $sessionId): void
    {
        RateLimiter::clear($this->key($user, $sessionId));
    }

    private function key(User $user, int $sessionId): string
    {
        $appKey = (string) config('app.key');
        if ($appKey === '') {
            $appKey = 'attendance-face-verification';
        }

        $identity = $user->getKey() . ':' . $sessionId;

        return 'face_verification:' . hash_hmac('sha256', $identity, $appKey);
    }

    private function decaySeconds(): int
    {
        $minutes = (int) Setting::get('face_lockout_minutes', 15);

        return max(1, min(60, $minutes)) * 60;
    }
}
