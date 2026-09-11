<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemUpdateVersionBumpTest extends TestCase
{
    use RefreshDatabase;

    private string $initialSwContent = '';
    private string $initialManifestContent = '';
    private string $initialVersionContent = '';

    protected function setUp(): void
    {
        parent::setUp();
        $swPath = public_path('sw.js');
        if (File::exists($swPath)) {
            $this->initialSwContent = File::get($swPath);
        }
        $manifestPath = public_path('manifest.json');
        if (File::exists($manifestPath)) {
            $this->initialManifestContent = File::get($manifestPath);
        }
        $versionPath = base_path('version.json');
        if (File::exists($versionPath)) {
            $this->initialVersionContent = File::get($versionPath);
        }
    }

    protected function tearDown(): void
    {
        $swPath = public_path('sw.js');
        if (!empty($this->initialSwContent) && File::exists($swPath)) {
            File::put($swPath, $this->initialSwContent);
        }
        $manifestPath = public_path('manifest.json');
        if (!empty($this->initialManifestContent) && File::exists($manifestPath)) {
            File::put($manifestPath, $this->initialManifestContent);
        }
        $versionPath = base_path('version.json');
        if (!empty($this->initialVersionContent) && File::exists($versionPath)) {
            File::put($versionPath, $this->initialVersionContent);
        }
        parent::tearDown();
    }

    public function test_multiple_consecutive_pwa_version_bumps_increment_every_time(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'is_active' => true,
        ]);

        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);
        $initialContent = File::get($swPath);

        try {
            // Bump 1
            $res1 = $this->actingAs($superAdmin)->postJson(route('admin.system-update.pwa-bump'));
            $res1->assertStatus(200);
            $v1 = $res1->json('version');
            $this->assertNotEmpty($v1);
            $num1 = (int)str_replace('v', '', $v1);

            // Bump 2 - must move again
            $res2 = $this->actingAs($superAdmin)->postJson(route('admin.system-update.pwa-bump'));
            $res2->assertStatus(200);
            $v2 = $res2->json('version');
            $num2 = (int)str_replace('v', '', $v2);
            $this->assertEquals($num1 + 1, $num2, "Version must advance on second bump: $v1 -> $v2");

            // Bump 3 - must move again
            $res3 = $this->actingAs($superAdmin)->postJson(route('admin.system-update.pwa-bump'));
            $res3->assertStatus(200);
            $v3 = $res3->json('version');
            $num3 = (int)str_replace('v', '', $v3);
            $this->assertEquals($num2 + 1, $num3, "Version must advance on third bump: $v2 -> $v3");

            // Verify /pwa/version returns the latest bumped version
            $verRes = $this->get('/pwa/version');
            $verRes->assertStatus(200);
            $verData = $verRes->json();
            $this->assertEquals($v3, $verData['sw_version']);
            $this->assertEquals('2.4.0', $verData['latest_version']);

        } finally {
            // Restore original sw.js content so test doesn't dirty git working tree
            File::put($swPath, $initialContent);
        }
    }

    public function test_full_system_update_returns_bumped_version_in_response(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'is_active' => true,
        ]);

        $swPath = public_path('sw.js');
        $initialContent = File::get($swPath);

        try {
            $res = $this->actingAs($superAdmin)->postJson(route('admin.system-update.run'));
            $res->assertStatus(200);
            $data = $res->json();

            $this->assertTrue($data['success']);
            $this->assertArrayHasKey('version', $data);
            $this->assertArrayHasKey('sw_version', $data);
            $this->assertArrayHasKey('app_version', $data);
            $this->assertNotEmpty($data['version']);
            $this->assertEquals('v2.4.0', $data['app_version']);

            // Subsequent update bump advances patch version and does NOT stay stuck on 2.3.5
            $res2 = $this->actingAs($superAdmin)->postJson(route('admin.system-update.run'));
            $res2->assertStatus(200);
            $data2 = $res2->json();
            $this->assertEquals('v2.4.1', $data2['app_version']);
        } finally {
            File::put($swPath, $initialContent);
        }
    }

    public function test_application_semver_bumps_and_does_not_get_stuck(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'is_active' => true,
        ]);

        // Bump 1: from base 2.4.0 to 2.4.1
        $res1 = $this->actingAs($superAdmin)->postJson(route('admin.system-update.app-bump'));
        $res1->assertStatus(200);
        $this->assertEquals('v2.4.1', $res1->json('app_version'));

        // Bump 2: to 2.4.2
        $res2 = $this->actingAs($superAdmin)->postJson(route('admin.system-update.app-bump'));
        $res2->assertStatus(200);
        $this->assertEquals('v2.4.2', $res2->json('app_version'));

        // Verify /pwa/version endpoint reflects the new dynamic version
        $pwaRes = $this->get('/pwa/version');
        $pwaRes->assertStatus(200);
        $this->assertEquals('2.4.2', $pwaRes->json('latest_version'));
    }
}
