<?php

namespace App\Helpers;

class DeviceHelper
{
    /**
     * Check if the current request is from a mobile device
     */
    public static function isMobile(): bool
    {
        $userAgent = request()->header('User-Agent', '');
        
        // Check for mobile patterns
        return preg_match('/Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent) === 1
            && !self::isTablet();
    }

    /**
     * Check if the current request is from a tablet
     */
    public static function isTablet(): bool
    {
        $userAgent = request()->header('User-Agent', '');
        return preg_match('/iPad|Tablet|PlayBook/i', $userAgent) === 1;
    }

    /**
     * Check if the current request is from a desktop
     */
    public static function isDesktop(): bool
    {
        return !self::isMobile() && !self::isTablet();
    }

    /**
     * Get the device type as a string
     */
    public static function getDeviceType(): string
    {
        if (self::isMobile()) {
            return 'mobile';
        }
        if (self::isTablet()) {
            return 'tablet';
        }
        return 'desktop';
    }

    /**
     * Check if running in Android WebView
     */
    public static function isAndroidWebView(): bool
    {
        $userAgent = request()->header('User-Agent', '');
        return preg_match('/SmartAttendanceApp|wv.*Android/i', $userAgent) === 1;
    }

    /**
     * Check if running in iOS WebView
     */
    public static function isIOSWebView(): bool
    {
        $userAgent = request()->header('User-Agent', '');
        return preg_match('/(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i', $userAgent) === 1;
    }

    /**
     * Check if running in any WebView (native app wrapper)
     */
    public static function isWebView(): bool
    {
        return self::isAndroidWebView() || self::isIOSWebView();
    }
}
