<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService
{
    public const DEFAULTS = [
        'feature.face_verification' => true,
        // Keep external face verification hidden until privacy/provider review is complete.
        'feature.peer_biometric' => false,
        'feature.student_assistant_qr' => true,
        'feature.gps_verification' => true,
        'feature.device_binding' => true,
        'feature.sms_notifications' => false,
        'feature.offline_attendance' => true,
    ];

    private const CACHE_KEY = 'application_feature_flags';

    /** @return array<string, bool> */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function (): array {
            $flags = self::DEFAULTS;

            try {
                $stored = Setting::query()
                    ->whereIn('key', array_keys(self::DEFAULTS))
                    ->pluck('value', 'key');

                foreach ($stored as $key => $value) {
                    $flags[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                        ?? self::DEFAULTS[$key];
                }
            } catch (\Throwable) {
                // Settings may not exist during an early migration or local bootstrap.
            }

            return $flags;
        });
    }

    public function enabled(string $key): bool
    {
        return $this->all()[$key] ?? false;
    }

    public function set(string $key, bool $enabled, ?User $actor): void
    {
        if (!array_key_exists($key, self::DEFAULTS)) {
            throw new \InvalidArgumentException('Unknown feature flag.');
        }

        if (!$actor || !$actor->isAdmin()) {
            throw new AuthorizationException('Only administrators can change feature flags.');
        }

        $value = $enabled ? '1' : '0';
        $setting = Setting::firstOrNew(['key' => $key]);
        $changed = !$setting->exists || (string) $setting->value !== $value;
        $setting->value = $value;
        $setting->save();

        Cache::forget(self::CACHE_KEY);
        if ($changed) {
            activity()
                ->performedOn($setting)
                ->causedBy($actor)
                ->withProperties(['feature_flag' => $key, 'enabled' => $enabled])
                ->log('Feature flag changed');
        }
    }

    public function defaults(): array
    {
        return self::DEFAULTS;
    }
}
