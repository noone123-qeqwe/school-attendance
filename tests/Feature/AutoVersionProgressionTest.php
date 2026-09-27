<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AutoVersionProgressionTest extends TestCase
{
    use RefreshDatabase;

    private ?string $originalVersionJson = null;
    private ?string $originalPackageJson = null;
    private ?string $originalPackageLockJson = null;
    private ?string $originalManifestJson = null;
    private ?string $originalSwJs = null;
    private ?string $originalAndroidGradle = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (file_exists(base_path('version.json'))) {
            $this->originalVersionJson = file_get_contents(base_path('version.json'));
        }
        if (file_exists(base_path('package.json'))) {
            $this->originalPackageJson = file_get_contents(base_path('package.json'));
        }
        if (file_exists(base_path('package-lock.json'))) {
            $this->originalPackageLockJson = file_get_contents(base_path('package-lock.json'));
        }
        if (file_exists(public_path('manifest.json'))) {
            $this->originalManifestJson = file_get_contents(public_path('manifest.json'));
        }
        if (file_exists(public_path('sw.js'))) {
            $this->originalSwJs = file_get_contents(public_path('sw.js'));
        }
        if (file_exists(base_path('apps/android_app/app/build.gradle'))) {
            $this->originalAndroidGradle = file_get_contents(base_path('apps/android_app/app/build.gradle'));
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
        if ($this->originalPackageLockJson !== null) {
            file_put_contents(base_path('package-lock.json'), $this->originalPackageLockJson);
        }
        if ($this->originalManifestJson !== null) {
            file_put_contents(public_path('manifest.json'), $this->originalManifestJson);
        }
        if ($this->originalSwJs !== null) {
            file_put_contents(public_path('sw.js'), $this->originalSwJs);
        }
        if ($this->originalAndroidGradle !== null) {
            file_put_contents(base_path('apps/android_app/app/build.gradle'), $this->originalAndroidGradle);
        }
        parent::tearDown();
    }

    /**
     * Test full version progression: 2.4.6 -> 2.4.7 -> 2.4.8 -> 2.4.9 -> 2.4.10 -> 2.4.11
     * Verifies:
     *  - Starting at 2.4.6
     *  - Increments patch correctly
     *  - 2.4.9 -> 2.4.10 is semver (NOT decimal 2.4.91)
     *  - All disk files stay synchronized
     *  - The deployed web release is immediately the installed release
     *  - Never resets to older value on container/service restart
     */
    public function test_full_automated_version_progression_from_2_4_6_to_2_4_10(): void
    {
        /** @var VersionService $service */
        $service = app(VersionService::class);

        // Step 0: Ensure base starting version is 2.4.6
        $service->setLatestVersion('2.4.6');
        $service->setInstalledVersion('2.4.6');
        $service->refresh();

        $this->assertEquals('2.4.6', $service->getInstalledVersion());
        $this->assertEquals('2.4.6', $service->getLatestVersion());
        $this->assertTrue($service->isUpToDate());

        $expectedProgression = [
            ['from' => '2.4.6', 'to' => '2.4.7'],
            ['from' => '2.4.7', 'to' => '2.4.8'],
            ['from' => '2.4.8', 'to' => '2.4.9'],
            ['from' => '2.4.9', 'to' => '2.4.10'], // Critical: semver 2.4.10, not decimal 2.4.91
            ['from' => '2.4.10', 'to' => '2.4.11'],
        ];

        foreach ($expectedProgression as $step) {
            $prevVer = $step['from'];
            $targetVer = $step['to'];

            // 1. Simulate push / release trigger via version bump script
            $output = [];
            $code = 0;
            exec(PHP_BINARY . ' ' . escapeshellarg(base_path('scripts/version-bump.php')), $output, $code);
            $this->assertEquals(0, $code, "version-bump.php should exit with 0");

            // Refresh service to read newly updated disk/DB artifacts
            $service->refresh();

            // 2. Verify single source of truth updated to targetVer
            $this->assertEquals($targetVer, $service->getLatestVersion());
            $this->assertEquals($targetVer, $service->getInstalledVersion());
            $this->assertTrue($service->isUpToDate());

            // 3. Verify disk files are synchronized
            $vJson = json_decode(file_get_contents(base_path('version.json')), true);
            $this->assertEquals($targetVer, $vJson['version']);

            $pkgJson = json_decode(file_get_contents(base_path('package.json')), true);
            $this->assertEquals($targetVer, $pkgJson['version']);

            $lockJson = json_decode(file_get_contents(base_path('package-lock.json')), true);
            $this->assertEquals($targetVer, $lockJson['version']);
            $this->assertEquals($targetVer, $lockJson['packages']['']['version']);

            $mfJson = json_decode(file_get_contents(public_path('manifest.json')), true);
            $this->assertEquals($targetVer, $mfJson['version']);

            // 4. Server metadata describes the deployable release; each browser
            // independently detects whether its loaded HTML/service worker is older.
            $pwaCheck = $this->getJson('/pwa/version');
            $pwaCheck->assertStatus(200)
                ->assertJson([
                    'installed_version' => $targetVer,
                    'latest_version'    => $targetVer,
                    'is_up_to_date'     => true,
                ]);

            $apiCheck = $this->getJson('/api/version');
            $apiCheck->assertStatus(200)
                ->assertJson([
                    'installed_version' => $targetVer,
                    'latest_version'    => $targetVer,
                    'is_up_to_date'     => true,
                ]);

            // 5. User applies update (clicks "Update Now")
            $updateRes = $this->actingAs(\App\Models\User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin']))->postJson('/pwa/update', ['version' => $targetVer]);
            $updateRes->assertStatus(200)
                ->assertJson([
                    'success'           => true,
                    'installed_version' => $targetVer,
                    'is_up_to_date'     => true,
                ]);

            // 6. Application reports up-to-date and popup is dismissed
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

        // 7. Verify container / application restart does NOT revert version
        app()->forgetInstance(VersionService::class);
        $freshService = app(VersionService::class);
        $this->assertEquals('2.4.11', $freshService->getLatestVersion());
        $this->assertEquals('2.4.11', $freshService->getInstalledVersion());
        $this->assertTrue($freshService->isUpToDate());
    }

    /**
     * Verify dry run does not modify files.
     */
    public function test_dry_run_leaves_files_unchanged(): void
    {
        $vJsonBefore = file_get_contents(base_path('version.json'));
        $pkgBefore = file_get_contents(base_path('package.json'));

        $output = [];
        $code = 0;
        exec(PHP_BINARY . ' ' . escapeshellarg(base_path('scripts/version-bump.php')) . ' --dry-run', $output, $code);
        $this->assertEquals(0, $code);

        $this->assertEquals($vJsonBefore, file_get_contents(base_path('version.json')));
        $this->assertEquals($pkgBefore, file_get_contents(base_path('package.json')));
    }

    public function test_legacy_push_workflow_cannot_bump_without_release_flag(): void
    {
        $before = file_get_contents(base_path('version.json'));
        $output = tempnam(sys_get_temp_dir(), 'web-release-output-');

        try {
            $process = new Process(
                [PHP_BINARY, base_path('scripts/version-bump.php'), '--ci'],
                base_path(),
                ['GITHUB_ACTIONS' => 'true', 'GITHUB_OUTPUT' => $output]
            );
            $process->run();

            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $this->assertStringContainsString('explicit --release flag is required', $process->getOutput());
            $this->assertStringContainsString('bumped=false', file_get_contents($output));
            $this->assertSame($before, file_get_contents(base_path('version.json')));
        } finally {
            unlink($output);
        }
    }

    /**
     * Verify artisan command app:version:bump works end-to-end.
     */
    public function test_artisan_app_version_bump_command(): void
    {
        $service = app(VersionService::class);
        $service->setLatestVersion('2.4.6');
        $service->setInstalledVersion('2.4.6');
        $service->refresh();

        $this->artisan('app:version:bump')
            ->assertExitCode(0);

        $service->refresh();
        $this->assertEquals('2.4.7', $service->getLatestVersion());
    }
}
