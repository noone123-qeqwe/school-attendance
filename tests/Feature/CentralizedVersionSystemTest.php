<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CentralizedVersionSystemTest extends TestCase
{
    use RefreshDatabase;

    private string $originalVersionJson = '';
    private string $originalManifestJson = '';
    private string $originalPackageJson = '';
    private string $originalSwJs = '';

    protected function setUp(): void
    {
        parent::setUp();

        if (File::exists(base_path('version.json'))) {
            $this->originalVersionJson = File::get(base_path('version.json'));
        }
        if (File::exists(public_path('manifest.json'))) {
            $this->originalManifestJson = File::get(public_path('manifest.json'));
        }
        if (File::exists(base_path('package.json'))) {
            $this->originalPackageJson = File::get(base_path('package.json'));
        }
        if (File::exists(public_path('sw.js'))) {
            $this->originalSwJs = File::get(public_path('sw.js'));
        }
    }

    protected function tearDown(): void
    {
        if (!empty($this->originalVersionJson) && File::exists(base_path('version.json'))) {
            File::put(base_path('version.json'), $this->originalVersionJson);
        }
        if (!empty($this->originalManifestJson) && File::exists(public_path('manifest.json'))) {
            File::put(public_path('manifest.json'), $this->originalManifestJson);
        }
        if (!empty($this->originalPackageJson) && File::exists(base_path('package.json'))) {
            File::put(base_path('package.json'), $this->originalPackageJson);
        }
        if (!empty($this->originalSwJs) && File::exists(public_path('sw.js'))) {
            File::put(public_path('sw.js'), $this->originalSwJs);
        }

        parent::tearDown();
    }

    public function test_api_version_endpoint_returns_comprehensive_metadata(): void
    {
        $response = $this->getJson('/api/version');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'name',
                'version',
                'version_tag',
                'version_display',
                'build',
                'commit',
                'release_date',
                'releaseDate',
                'environment',
                'database_version',
                'installed_version',
                'latest_version',
                'is_up_to_date',
            ]);

        $this->assertStringContainsString('no-cache', (string)$response->headers->get('Cache-Control'));

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['version']);
        $this->assertStringStartsWith('v', $data['version_tag']);
        $this->assertNotEmpty($data['build']);
        $this->assertNotEmpty($data['release_date']);
        $this->assertEquals($data['release_date'], $data['releaseDate']);
        $this->assertIsInt($data['database_version']);
    }

    public function test_semver_increment_logic_for_patch_minor_major(): void
    {
        /** @var VersionService $service */
        $service = app(VersionService::class);

        // Patch: 2.4.0 -> 2.4.1
        $this->assertEquals('2.4.1', $service->incrementSemver('2.4.0', 'patch'));
        $this->assertEquals('2.4.1', $service->incrementSemver('v2.4.0', 'patch'));

        // Minor: 2.4.0 -> 2.5.0, reset patch
        $this->assertEquals('2.5.0', $service->incrementSemver('2.4.0', 'minor'));
        $this->assertEquals('2.5.0', $service->incrementSemver('2.4.5', 'minor'));

        // Major: 2.4.0 -> 3.0.0, reset minor and patch
        $this->assertEquals('3.0.0', $service->incrementSemver('2.4.0', 'major'));
        $this->assertEquals('3.0.0', $service->incrementSemver('2.9.12', 'major'));
    }

    public function test_create_release_updates_all_manifests_build_and_caches(): void
    {
        /** @var VersionService $service */
        $service = app(VersionService::class);

        $initialBuild = $service->getBuild();

        $release = $service->createRelease('minor');

        $this->assertEquals('2.5.0', $release['version']);
        $this->assertEquals('v2.5.0', $release['version_tag']);
        $this->assertNotEmpty($release['build']);
        $this->assertStringStartsWith(date('Ymd'), $release['build']);
        $this->assertNotEquals($initialBuild, $release['build']);

        // Check version.json was updated
        $versionJson = json_decode(File::get(base_path('version.json')), true);
        $this->assertEquals('2.5.0', $versionJson['version']);
        $this->assertEquals($release['build'], $versionJson['build']);

        // Check package.json was updated
        $pkg = json_decode(File::get(base_path('package.json')), true);
        $this->assertEquals('2.5.0', $pkg['version']);

        // Check manifest.json was updated
        $manifest = json_decode(File::get(public_path('manifest.json')), true);
        $this->assertEquals('2.5.0', $manifest['version']);

        // Check database Setting was updated
        $this->assertEquals('2.5.0', Setting::get('system_version'));
        $this->assertEquals('2.5.0', Setting::get('installed_version'));

        // Check sw.js was updated with new cache version
        $sw = File::get(public_path('sw.js'));
        $this->assertStringContainsString($release['sw_version'], $sw);
    }

    public function test_blade_directives_render_version_and_build(): void
    {
        $renderedVersion = Blade::render('@appVersion');
        $this->assertNotEmpty($renderedVersion);

        $renderedTag = Blade::render('@appVersionTag');
        $this->assertStringStartsWith('v', $renderedTag);

        $renderedBuild = Blade::render('@appBuild');
        $this->assertNotEmpty($renderedBuild);

        $renderedDate = Blade::render('@appReleaseDate');
        $this->assertNotEmpty($renderedDate);
    }

    public function test_view_composer_shares_version_variables_with_all_views(): void
    {
        $view = view('auth.login', ['errors' => new \Illuminate\Support\ViewErrorBag]);
        $html = $view->render();
        $data = $view->getData();

        $this->assertArrayHasKey('appVersion', $data);
        $this->assertArrayHasKey('appVersionTag', $data);
        $this->assertArrayHasKey('appBuild', $data);
        $this->assertArrayHasKey('appCommit', $data);
        $this->assertArrayHasKey('appReleaseDate', $data);
        $this->assertArrayHasKey('appInstalledVersion', $data);
        $this->assertArrayHasKey('appIsUpToDate', $data);
        $this->assertArrayHasKey('appMetadata', $data);
    }

    public function test_artisan_app_version_command(): void
    {
        $this->artisan('app:version')
            ->expectsOutputToContain('Smart Classroom Attendance System')
            ->expectsOutputToContain('Release Version')
            ->expectsOutputToContain('Build ID')
            ->assertExitCode(0);

        $this->artisan('app:version', ['--json' => true])
            ->assertExitCode(0);
    }

    public function test_artisan_app_release_command(): void
    {
        $this->artisan('app:release', ['type' => 'patch'])
            ->expectsOutputToContain('Application release successfully created!')
            ->assertExitCode(0);
    }

    public function test_update_detection_evaluates_up_to_date_accurately(): void
    {
        /** @var VersionService $service */
        $service = app(VersionService::class);

        Setting::set('system_version', '2.4.0');
        Setting::set('installed_version', '2.4.0');
        $this->assertTrue($service->isUpToDate());

        Setting::set('installed_version', '2.3.9');
        $this->assertFalse($service->isUpToDate());

        Setting::set('installed_version', '2.4.1');
        $this->assertTrue($service->isUpToDate());
    }
}
