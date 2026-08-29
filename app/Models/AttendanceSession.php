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
        'subject_code', 'created_by', 'token', 'session_code', 'expires_at', 'session_ends_at', 'active',
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
     * Generate an easy-to-read 6-digit numeric attendance session code.
     */
    public static function generateSessionCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * Get formatted code (e.g. "849 201").
     */
    public function getFormattedCode(): string
    {
        if (!$this->session_code) return '';
        $clean = preg_replace('/[^0-9A-Za-z]/', '', $this->session_code);
        if (strlen($clean) === 6) {
            return substr($clean, 0, 3) . ' ' . substr($clean, 3, 3);
        }
        return $this->session_code;
    }
}
