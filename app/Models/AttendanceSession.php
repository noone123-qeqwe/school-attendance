<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceSession extends Model
{
    protected $fillable = [
        'subject_code', 'created_by', 'token', 'expires_at', 'session_ends_at', 'active',
        'classroom_lat', 'classroom_lng', 'webauthn_challenge',
    ];

    protected $casts = [
        'expires_at'      => 'datetime',
        'session_ends_at' => 'datetime',
        'active'          => 'boolean',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_code', 'code');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isTokenValid(): bool
    {
        return $this->active && $this->expires_at && $this->expires_at->isFuture();
    }

    public function isSessionActive(): bool
    {
        return $this->active && $this->session_ends_at && $this->session_ends_at->isFuture();
    }

    public function markInactiveIfExpired(): void
    {
        if ($this->active && $this->session_ends_at && $this->session_ends_at->isPast()) {
            $this->forceFill(['active' => false, 'webauthn_challenge' => null])->save();
        }
    }

    /**
     * Clean up expired WebAuthn challenges to prevent confusion
     */
    public function cleanupExpiredChallenge(): void
    {
        // Clean up challenges that are older than 5 minutes (much longer than the 60-second WebAuthn timeout)
        // This prevents premature cleanup while still preventing stale challenges
        $challengeExpiryTime = now()->subMinutes(5);
        
        if ($this->webauthn_challenge && $this->updated_at && $this->updated_at->lt($challengeExpiryTime)) {
            Log::debug('Cleaning up expired WebAuthn challenge', [
                'session_id' => $this->id,
                'challenge_updated_at' => $this->updated_at,
                'expiry_time' => $challengeExpiryTime,
                'challenge_exists' => !empty($this->webauthn_challenge),
            ]);
            $this->forceFill(['webauthn_challenge' => null])->save();
        }
    }

    /**
     * Clear the WebAuthn challenge (called after successful verification)
     */
    public function clearWebauthnChallenge(): void
    {
        if ($this->webauthn_challenge) {
            $this->forceFill(['webauthn_challenge' => null])->save();
        }
    }

    /**
     * Generate an unpredictable QR nonce. The signed URL protects the token from tampering.
     */
    public static function generateToken(string $subjectCode): string
    {
        return hash_hmac('sha256', $subjectCode . '|' . now()->timestamp . '|' . bin2hex(random_bytes(32)), config('app.key'));
    }
}
