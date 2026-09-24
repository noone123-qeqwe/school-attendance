<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceBinding extends Model
{
    protected $fillable = [
        'user_id',
        'device_hash',
        'hardware_fingerprint',
        'device_uuid',
        'device_name',
        'session_id',
        'user_agent',
        'client_metadata',
        'ip_address',
        'change_count',
        'trust_score',
        'is_locked',
        'locked_reason',
        'last_seen_at',
        'last_verified_at',
    ];

    protected $casts = [
        'last_seen_at'     => 'datetime',
        'last_verified_at' => 'datetime',
        'is_locked'        => 'boolean',
        'change_count'     => 'integer',
        'trust_score'      => 'integer',
        'client_metadata'  => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }

    public function lock(?string $reason = 'manual'): self
    {
        $this->update([
            'is_locked'     => true,
            'locked_reason' => $reason,
        ]);
        return $this;
    }

    public function unlock(): self
    {
        $this->update([
            'is_locked'     => false,
            'locked_reason' => null,
        ]);
        return $this;
    }

    /**
     * Get computed trust level based on score.
     */
    public function getTrustLevel(): string
    {
        $score = $this->trust_score ?? 85;
        if ($this->isLocked()) {
            return 'LOCKED';
        }
        if ($score >= 85) {
            return 'VERIFIED';
        }
        if ($score >= 60) {
            return 'STANDARD';
        }
        return 'SUSPICIOUS';
    }

    /**
     * Get GPU renderer or graphics chip information if available.
     */
    public function getGpuInfo(): ?string
    {
        $meta = $this->client_metadata;
        if (!is_array($meta)) return null;

        $gpu = $meta['gpu_renderer'] ?? $meta['gpu'] ?? null;
        if ($gpu && is_string($gpu)) {
            // Clean common verbose ANGLE prefixes for cleaner UI display
            $cleaned = preg_replace('/^ANGLE \([^,]+,\s*/i', '', $gpu);
            $cleaned = preg_replace('/\s+Direct3D.+$/i', '', $cleaned);
            return trim($cleaned, ' ()');
        }
        return null;
    }

    /**
     * Get display resolution / pixel ratio if available.
     */
    public function getDisplayInfo(): ?string
    {
        $meta = $this->client_metadata;
        if (!is_array($meta)) return null;

        $res = $meta['screen_res'] ?? $meta['resolution'] ?? null;
        $dpr = $meta['pixel_ratio'] ?? null;
        if ($res) {
            return $dpr ? "{$res} @ {$dpr}x" : (string) $res;
        }
        return null;
    }

    /**
     * Get an appropriate Bootstrap icon for the bound device.
     */
    public function getDeviceIcon(): string
    {
        $ua = strtolower((string) ($this->user_agent ?? ''));
        $name = strtolower((string) ($this->device_name ?? ''));

        if (str_contains($ua, 'ipad') || str_contains($name, 'tablet')) {
            return 'bi-tablet';
        }
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone') || str_contains($name, 'phone')) {
            return 'bi-phone';
        }
        if (str_contains($ua, 'macintosh') || str_contains($ua, 'windows') || str_contains($name, 'desktop') || str_contains($name, 'laptop')) {
            return 'bi-laptop';
        }

        return 'bi-phone';
    }
}

