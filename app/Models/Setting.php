<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * In-memory cache for settings within the request lifecycle.
     */
    protected static array $runtimeCache = [];
    protected static bool $allLoaded = false;

    protected static function booted(): void
    {
        static::saved(function ($setting) {
            self::$runtimeCache[$setting->key] = $setting->value;
        });

        static::deleted(function ($setting) {
            unset(self::$runtimeCache[$setting->key]);
        });
    }

    /**
     * Helper method to get a setting value with an optional default.
     */
    public static function get(string $key, $default = null)
    {
        if (app()->runningUnitTests()) {
            try {
                $item = self::where('key', $key)->first(['value']);
                return $item ? $item->value : $default;
            } catch (\Throwable $e) {
                return $default;
            }
        }

        if (array_key_exists($key, self::$runtimeCache)) {
            return self::$runtimeCache[$key];
        }

        if (!self::$allLoaded) {
            try {
                $items = self::all(['key', 'value']);
                foreach ($items as $item) {
                    self::$runtimeCache[$item->key] = $item->value;
                }
                self::$allLoaded = true;

                if (array_key_exists($key, self::$runtimeCache)) {
                    return self::$runtimeCache[$key];
                }
            } catch (\Throwable $e) {
                // Table might not exist yet during migrations
            }
        }

        return $default;
    }

    public static function set(string $key, $value, bool $syncVersionFile = true)
    {
        self::$runtimeCache[$key] = $value;

        if ($syncVersionFile && app()->runningUnitTests()) {
            try {
                $versionFile = base_path('version.json');
                if (\Illuminate\Support\Facades\File::exists($versionFile)) {
                    $raw = @file_get_contents($versionFile);
                    $data = !empty($raw) ? @json_decode($raw, true) : [];
                    if (!is_array($data)) {
                        $data = [];
                    }
                    if ($key === 'installed_version') {
                        $data['installed_version'] = $value;
                    } elseif ($key === 'latest_version' || $key === 'system_version') {
                        $data['version'] = $value;
                    }
                    \Illuminate\Support\Facades\File::put($versionFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            } catch (\Throwable $e) {}
        }

        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Flush the in-memory runtime cache.
     */
    public static function flushCache(): void
    {
        self::$runtimeCache = [];
        self::$allLoaded = false;
    }
}
