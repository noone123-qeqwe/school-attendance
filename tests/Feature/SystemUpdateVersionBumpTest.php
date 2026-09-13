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
    private string $initialPackageContent = '';

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
        $packagePath = base_path('package.json');
        if (File::exists($packagePath)) {
            $this->initialPackageContent = File::get($packagePath);
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
        $packagePath = base_path('package.json');
        if (!empty($this->initialPackageContent) && File::exists($packagePath)) {
            File::put($packagePath, $this->initialPackageContent);
        }
        parent::tearDown();
    }

    private function getBaselineVersion(): string
    {
        $versionService = app(\App\Services\VersionService::class);
        $versionService->refresh();
        return $versionService->getVersion();
    }

    private function bumpSemver(string $version, int $increment = 1): string
    {
        $parts = explode('.', $version);
        $parts[2] = (int)($parts[2] ?? 0) + $increment;
        return implode('.', $parts);
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
        $baseVersion = $this->getBaselineVersion();

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
            $this->assertEquals($baseVersion, $verData['latest_version']);

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
        $base = $this->getBaselineVersion();
        $expectedNext1 = 'v' . $this->bumpSemver($base, 1);
        $expectedNext2 = 'v' . $this->bumpSemver($base, 2);

        try {
            $res = $this->actingAs($superAdmin)->postJson(route('admin.system-update.run'));
            $res->assertStatus(200);
            $data = $res->json();

            $this->assertTrue($data['success']);
            $this->assertArrayHasKey('version', $data);
            $this->assertArrayHasKey('sw_version', $data);
            $this->assertArrayHasKey('app_version', $data);
            $this->assertNotEmpty($data['version']);
            $this->assertEquals($expectedNext1, $data['app_version']);

            // Subsequent update bump advances patch version and does NOT stay stuck on 2.3.5
            $res2 = $this->actingAs($superAdmin)->postJson(route('admin.system-update.run'));
            $res2->assertStatus(200);
            $data2 = $res2->json();
            $this->assertEquals($expectedNext2, $data2['app_version']);
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

        $base = $this->getBaselineVersion();
        $expectedBump1 = 'v' . $this->bumpSemver($base, 1);
        $expectedBump2 = 'v' . $this->bumpSemver($base, 2);

        // Bump 1
        $res1 = $this->actingAs($superAdmin)->postJson(route('admin.system-update.app-bump'));
        $res1->assertStatus(200);
        $this->assertEquals($expectedBump1, $res1->json('app_version'));

        // Bump 2
        $res2 = $this->actingAs($superAdmin)->postJson(route('admin.system-update.app-bump'));
        $res2->assertStatus(200);
        $this->assertEquals($expectedBump2, $res2->json('app_version'));

        // Verify /pwa/version endpoint reflects the new dynamic version
        $pwaRes = $this->get('/pwa/version');
        $pwaRes->assertStatus(200);
        $this->assertEquals($this->bumpSemver($base, 2), $pwaRes->json('latest_version'));
    }

    public function test_consecutive_system_updates_move_version_multiple_times_and_never_get_stuck(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'is_active' => true,
        ]);

        $base = $this->getBaselineVersion();

        // Perform 4 consecutive full system updates
        for ($i = 1; $i <= 4; $i++) {
            $expectedVer = 'v' . $this->bumpSemver($base, $i);

            $res = $this->actingAs($superAdmin)->postJson(route('admin.system-update.run'));
            $res->assertStatus(200);
            $data = $res->json();

            $this->assertTrue($data['success'], "Update #{$i} must succeed");
            $this->assertEquals($expectedVer, $data['app_version'], "Update #{$i} must advance to {$expectedVer}, got {$data['app_version']}");

            // Verify database setting matches
            $this->assertEquals($this->bumpSemver($base, $i), \App\Models\Setting::get('system_version'));

            // Verify VersionService matches
            $this->assertEquals($this->bumpSemver($base, $i), app(\App\Services\VersionService::class)->getVersion());
        }

        // Verify final /pwa/version endpoint matches base+4
        $pwaRes = $this->get('/pwa/version');
        $pwaRes->assertStatus(200);
        $this->assertEquals($this->bumpSemver($base, 4), $pwaRes->json('latest_version'));
    }
}
