<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Otp extends Model
{
    public const COOLDOWN_SECONDS = 60;
    public const MAX_VERIFY_ATTEMPTS = 5;

    protected $fillable = ['user_id', 'code', 'purpose', 'used', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime', 'used' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    /**
     * Check if a cooldown is currently active for this user or email + purpose.
     * Returns remaining seconds (0 if allowed).
     */
    public static function getCooldownRemaining(int|string $userOrEmail, string $purpose, int $cooldown = self::COOLDOWN_SECONDS): int
    {
        $cacheKey = 'otp_cooldown:' . sha1(strtolower(trim((string) $userOrEmail)) . ':' . $purpose);
        $expiresAt = (int) Cache::get($cacheKey, 0);

        $now = now()->timestamp;
        return max(0, $expiresAt - $now);
    }

    /**
     * Set the cooldown timer for this user or email + purpose.
     */
    public static function setCooldown(int|string $userOrEmail, string $purpose, int $cooldown = self::COOLDOWN_SECONDS): void
    {
        $cacheKey = 'otp_cooldown:' . sha1(strtolower(trim((string) $userOrEmail)) . ':' . $purpose);
        Cache::put($cacheKey, now()->addSeconds($cooldown)->timestamp, $cooldown);
    }

    /**
     * Record a failed OTP verification attempt.
     */
    public static function recordFailedVerify(int|string $userOrEmail, string $purpose): int
    {
        $key = 'otp_verify_fails:' . sha1(strtolower(trim((string) $userOrEmail)) . ':' . $purpose);
        $attempts = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, 600); // 10 mins

        return $attempts;
    }

    /**
     * Clear failed OTP verification attempts on success.
     */
    public static function clearFailedVerify(int|string $userOrEmail, string $purpose): void
    {
        $key = 'otp_verify_fails:' . sha1(strtolower(trim((string) $userOrEmail)) . ':' . $purpose);
        Cache::forget($key);
    }

    /**
     * Generate a fresh 6-digit OTP for a user, invalidating any previous ones.
     */
    public static function generate(int $userId, string $purpose): self
    {
        // Invalidate old OTPs for this user + purpose
        static::where('user_id', $userId)
              ->where('purpose', $purpose)
              ->update(['used' => true]);

        static::setCooldown($userId, $purpose);

        return static::create([
            'user_id'    => $userId,
            'code'       => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'purpose'    => $purpose,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);
    }
}
