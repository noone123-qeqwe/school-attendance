<?php

namespace Tests\Feature;

use Tests\TestCase;

class AssetLinksTest extends TestCase
{
    public function test_well_known_assetlinks_returns_valid_json_for_android_webauthn(): void
    {
        $response = $this->get('/.well-known/assetlinks.json');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals('android_app', $data[0]['target']['namespace']);
        $this->assertEquals('com.school.attendance', $data[0]['target']['package_name']);
        $this->assertContains('delegate_permission/common.get_login_creds', $data[0]['relation']);
    }
}
