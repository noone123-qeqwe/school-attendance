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

    /**
     * Helper method to get a setting value with an optional default.
     */
    public static function get(string $key, $default = null)
    {
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

    /**
     * Helper method to set a setting value.
     */
    public static function set(string $key, $value)
    {
        self::$runtimeCache[$key] = $value;
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
