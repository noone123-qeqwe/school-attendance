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
        'ip_address',
        'change_count',
        'is_locked',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'is_locked'    => 'boolean',
        'change_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }

    public function lock(): self
    {
        $this->update(['is_locked' => true]);
        return $this;
    }

    public function unlock(): self
    {
        $this->update(['is_locked' => false]);
        return $this;
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
