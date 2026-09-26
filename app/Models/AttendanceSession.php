<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceSession extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'subject_code', 'created_by', 'token', 'previous_token', 'session_code', 'previous_session_code', 'expires_at', 'session_ends_at', 'active',
        'classroom_lat', 'classroom_lng', 'radius_meters', 'grace_period_minutes', 'webauthn_challenge',
    ];

    protected $casts = [
        'expires_at'           => 'datetime',
        'session_ends_at'      => 'datetime',
        'active'               => 'boolean',
        'radius_meters'        => 'integer',
        'grace_period_minutes' => 'integer',
    ];

    public function getAllowedRadius(): int
    {
        // An explicit session radius must not change when an administrator edits defaults.
        if ($this->radius_meters !== null) {
            return max(0, (int) $this->radius_meters);
        }
        return max(0, (int) \App\Models\Setting::get('gps_radius', 50));
    }

    public function getGracePeriodMinutes(): int
    {
        return (int) ($this->grace_period_minutes ?: \App\Models\Setting::get('presence_grace_minutes', 5));
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_code', 'code');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['subject_code', 'active', 'session_code', 'session_ends_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'session_id');
    }

    public function qrTokens()
    {
        return $this->hasMany(AttendanceQrToken::class);
    }

    public function activeQrToken()
    {
        return $this->hasOne(AttendanceQrToken::class, 'active_session_id');
    }

    public function isTokenValid(): bool
    {
        return $this->active && $this->expires_at && $this->expires_at->isFuture();
    }

    /**
     * Check if a given token or attendance code is valid for this session under the 15-second rotation window.
     * Accommodates network latency and clock sync via grace period.
     *
     * @param string|null $token
     * @param string|null $code
     * @param int $ttlSeconds (Default 15s)
     * @param int $graceSeconds (Default 5s)
     * @return array ['valid' => bool, 'is_expired' => bool, 'reason' => string]
     */
    public function validateCodeOrToken(?string $token, ?string $code, int $ttlSeconds = 15, int $graceSeconds = 5): array
    {
        if (!$this->isSessionActive()) {
            return [
                'valid' => false,
                'is_expired' => true,
                'reason' => 'session_ended'
            ];
        }

        $cleanCode = !empty($code) ? strtoupper(preg_replace('/[^0-9A-Za-z]/', '', (string) $code)) : null;
        $cleanToken = !empty($token) ? trim((string) $token) : null;

        $matchesCurrentToken = $cleanToken && ($this->token === $cleanToken);
        $matchesCurrentCode = $cleanCode && ($this->session_code === $cleanCode);
        $matchesPrevToken = $cleanToken && ($this->previous_token === $cleanToken);
        $matchesPrevCode = $cleanCode && ($this->previous_session_code === $cleanCode);

        if (!$matchesCurrentToken && !$matchesCurrentCode && !$matchesPrevToken && !$matchesPrevCode) {
            return [
                'valid' => false,
                'is_expired' => false,
                'reason' => 'not_found'
            ];
        }

        $now = now('Asia/Manila');
        $expiresAt = $this->expires_at ? Carbon::parse($this->expires_at)->setTimezone('Asia/Manila') : $now;

        // Current token or current code:
        if ($matchesCurrentToken || $matchesCurrentCode) {
            // Valid if current time is before or at expires_at + graceSeconds
            if ($now->lte($expiresAt->copy()->addSeconds($graceSeconds))) {
                return ['valid' => true, 'is_expired' => false, 'reason' => 'current_valid'];
            }
            return [
                'valid' => false,
                'is_expired' => true,
                'reason' => 'current_expired'
            ];
        }

        // Previous token or previous code:
        if ($matchesPrevToken || $matchesPrevCode) {
            // The previous code was rotated at ($expiresAt - $ttlSeconds).
            // It expired at that rotation moment. It is accepted within graceSeconds after rotation.
            $rotationTime = $expiresAt->copy()->subSeconds($ttlSeconds);
            $elapsedSeconds = $now->timestamp - $rotationTime->timestamp;

            if ($elapsedSeconds <= $graceSeconds) {
                return ['valid' => true, 'is_expired' => false, 'reason' => 'previous_valid_in_grace'];
            }
            return [
                'valid' => false,
                'is_expired' => true,
                'reason' => 'previous_expired'
            ];
        }

        return ['valid' => false, 'is_expired' => true, 'reason' => 'expired'];
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
     * Generate an unpredictable QR nonce.
     */
    public static function generateToken(string $subjectCode): string
    {
        return hash_hmac('sha256', $subjectCode . '|' . now()->timestamp . '|' . bin2hex(random_bytes(32)), config('app.key'));
    }

    /**
     * Generate an easy-to-read 6-digit numeric attendance session code (padded with leading zeros if needed).
     */
    public static function generateSessionCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get formatted code (e.g. "849 201" or "012 345").
     */
    public function getFormattedCode(): string
    {
        if ($this->session_code === null || $this->session_code === '') return '';
        $clean = preg_replace('/[^0-9A-Za-z]/', '', (string) $this->session_code);
        if (strlen($clean) === 6) {
            return substr($clean, 0, 3) . ' ' . substr($clean, 3, 3);
        }
        return (string) $this->session_code;
    }
}
