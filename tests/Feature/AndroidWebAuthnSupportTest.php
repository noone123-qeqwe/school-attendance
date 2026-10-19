<?php

namespace Tests\Feature;

use Tests\TestCase;

class AndroidWebAuthnSupportTest extends TestCase
{
    public function test_android_webview_enables_the_platform_webauthn_bridge(): void
    {
        $root = dirname(base_path());
        $activity = file_get_contents($root . '/android_app/app/src/main/java/com/school/attendance/MainActivity.java');
        $buildFile = file_get_contents($root . '/android_app/app/build.gradle');
        $rootBuildFile = file_get_contents($root . '/android_app/build.gradle');

        $this->assertStringContainsString('WebViewFeature.WEB_AUTHENTICATION', $activity);
        $this->assertStringContainsString('setWebAuthenticationSupport', $activity);
        $this->assertStringContainsString('WEB_AUTHENTICATION_SUPPORT_FOR_APP', $activity);
        $this->assertStringContainsString("androidx.webkit:webkit:1.14.0", $buildFile);
        $this->assertStringContainsString("androidx.credentials:credentials:1.6.0-beta02", $buildFile);
        $this->assertStringContainsString("androidx.credentials:credentials-play-services-auth:1.6.0-beta02", $buildFile);
        $this->assertStringContainsString("com.android.application' version '8.6.1'", $rootBuildFile);
    }
}
