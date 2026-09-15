<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContinuousVersionProgressionTest extends TestCase
{
    use RefreshDatabase;

    private ?string $originalVersionJson = null;
    private ?string $originalPackageJson = null;
    private ?string $originalManifestJson = null;
    private ?string $originalSwJs = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (file_exists(base_path('version.json'))) {
            $this->originalVersionJson = file_get_contents(base_path('version.json'));
        }
        if (file_exists(base_path('package.json'))) {
            $this->originalPackageJson = file_get_contents(base_path('package.json'));
        }
        if (file_exists(public_path('manifest.json'))) {
            $this->originalManifestJson = file_get_contents(public_path('manifest.json'));
        }
        if (file_exists(public_path('sw.js'))) {
            $this->originalSwJs = file_get_contents(public_path('sw.js'));
        }
    }

    protected function tearDown(): void
    {
        if ($this->originalVersionJson !== null) {
            file_put_contents(base_path('version.json'), $this->originalVersionJson);
        }
        if ($this->originalPackageJson !== null) {
            file_put_contents(base_path('package.json'), $this->originalPackageJson);
        }
        if ($this->originalManifestJson !== null) {
            file_put_contents(public_path('manifest.json'), $this->originalManifestJson);
        }
        if ($this->originalSwJs !== null) {
            file_put_contents(public_path('sw.js'), $this->originalSwJs);
        }
        parent::tearDown();
    }

    public function test_continuous_version_progression_from_version_1_to_version_5(): void
    {
        /** @var VersionService $service */
        $service = app(VersionService::class);

        // Step 0: Initial clean state -> System is on Version 1
        Setting::set('installed_version', '1');
        Setting::set('system_version', '1');
        Setting::set('latest_version', '1');
        $service->refresh();

        $this->assertEquals('1', $service->getInstalledVersion());
        $this->assertEquals('1', $service->getLatestVersion());
        $this->assertTrue($service->isUpToDate());

        $res0 = $this->getJson('/pwa/version');
        $res0->assertStatus(200)
            ->assertJson([
                'installed_version' => '1',
                'latest_version'    => '1',
                'is_up_to_date'     => true,
            ]);

        // Progression Loop: 1 -> 2 -> 3 -> 4 -> 5
        for ($nextVer = 2; $nextVer <= 5; $nextVer++) {
            $prevVer = (string)($nextVer - 1);
            $targetVer = (string)$nextVer;

            // 1. Release published: Update N -> Version N
            $service->publishRelease($targetVer);

            $this->assertEquals($prevVer, $service->getInstalledVersion());
            $this->assertEquals($targetVer, $service->getLatestVersion());
            $this->assertFalse($service->isUpToDate());

            // API / PWA endpoints detect update available
            $pwaCheck = $this->getJson('/pwa/version');
            $pwaCheck->assertStatus(200)
                ->assertJson([
                    'installed_version' => $prevVer,
                    'latest_version'    => $targetVer,
                    'is_up_to_date'     => false,
                ]);

            $apiCheck = $this->getJson('/api/version');
            $apiCheck->assertStatus(200)
                ->assertJson([
                    'installed_version' => $prevVer,
                    'latest_version'    => $targetVer,
                    'is_up_to_date'     => false,
                ]);

            // 2. User installs Version N
            $updateRes = $this->postJson('/pwa/update', ['version' => $targetVer]);
            $updateRes->assertStatus(200)
                ->assertJson([
                    'success'           => true,
                    'installed_version' => $targetVer,
                    'is_up_to_date'     => true,
                ]);

            // 3. Application now recognizes Current Version = Version N, Up to date = true
            $service->refresh();
            $this->assertEquals($targetVer, $service->getInstalledVersion());
            $this->assertEquals($targetVer, $service->getLatestVersion());
            $this->assertTrue($service->isUpToDate());

            $postUpdateCheck = $this->getJson('/pwa/version');
            $postUpdateCheck->assertStatus(200)
                ->assertJson([
                    'installed_version' => $targetVer,
                    'latest_version'    => $targetVer,
                    'is_up_to_date'     => true,
                ]);
        }

        // Final verification: Refreshing or resolving new service instance does not reset back to 1
        app()->forgetInstance(VersionService::class);
        $freshService = app(VersionService::class);
        $this->assertEquals('5', $freshService->getInstalledVersion());
        $this->assertEquals('5', $freshService->getLatestVersion());
        $this->assertTrue($freshService->isUpToDate());
    }
}
