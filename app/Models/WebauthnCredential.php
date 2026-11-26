<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebauthnCredential extends Model
{
    protected $fillable = [
        'user_id',
        'credential_id',
        'public_key',
        'sign_count',
        'device_name',
        'biometric_type',
        'transports',
        'aaguid',
        'authenticator_attachment',
        'backed_up',
        'user_agent_summary',
        'consent_given_at',
        'last_used_at',
        'revoked_at',
        'revoked_reason',
    ];

    protected $hidden = [
        'public_key',
    ];

    protected $casts = [
        'transports' => 'array',
        'backed_up' => 'boolean',
        'consent_given_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to active (non-revoked) credentials with a valid PEM public key.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where('public_key', 'like', '%BEGIN PUBLIC KEY%');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null
            && str_contains((string) $this->public_key, 'BEGIN PUBLIC KEY');
    }

    public function revoke(string $reason = 'user_revoked'): bool
    {
        return $this->forceFill([
            'revoked_at' => now(),
            'revoked_reason' => mb_substr(trim($reason), 0, 120) ?: 'user_revoked',
        ])->save();
    }

    public function normalizedCredentialId(): string
    {
        return rtrim(strtr(trim((string) $this->credential_id), '+/', '-_'), '=');
    }
}
