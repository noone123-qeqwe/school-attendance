<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VersionService
{
    protected ?array $fileData = null;

    /**
     * Get the root version.json file contents if available.
     */
    protected function getFileData(): array
    {
        if ($this->fileData !== null) {
            return $this->fileData;
        }

        $versionFile = base_path('version.json');
        if (File::exists($versionFile)) {
            $json = @json_decode(File::get($versionFile), true);
            if (is_array($json)) {
                return $this->fileData = $json;
            }
        }

        return $this->fileData = [];
    }

    /**
     * Primary source of truth: Get current application semantic version.
     */
    public function getVersion(): string
    {
        // 1. Check if explicitly overridden via config (e.g. tests or environment overrides)
        $changelogDef = config('changelog.default_version');
        if (!empty($changelogDef) && $changelogDef !== '2.4.0') {
            return (string)$changelogDef;
        }
        $configVer = config('version.version');
        if (!empty($configVer) && $configVer !== '2.4.0') {
            return (string)$configVer;
        }

        // 2. Check database setting override if present
        try {
            $dbSetting = Setting::get('system_version');
            if (!empty($dbSetting)) {
                return (string)$dbSetting;
            }
        } catch (\Throwable $e) {}

        // 3. Check version.json (primary centralized file source of truth)
        $file = $this->getFileData();
        if (!empty($file['version'])) {
            return (string)$file['version'];
        }

        // 4. Default fallback
        return (string)($configVer ?: ($changelogDef ?: '2.4.0'));
    }

    /**
     * Get the version formatted with a 'v' prefix (e.g. 'v2.4.0').
     */
    public function getVersionTag(): string
    {
        return 'v' . ltrim($this->getVersion(), 'v');
    }

    /**
     * Get the display label (e.g. 'Version 2.4.0').
     */
    public function getVersionDisplay(): string
    {
        return 'Version ' . ltrim($this->getVersion(), 'v');
    }

    /**
     * Get the build identifier (e.g. '20260912.001').
     */
    public function getBuild(): string
    {
        $configBuild = config('version.build');
        if (!empty($configBuild)) {
            return (string)$configBuild;
        }

        $file = $this->getFileData();
        if (!empty($file['build'])) {
            return (string)$file['build'];
        }

        return date('Ymd') . '.001';
    }

    /**
     * Get short Git commit hash if in a Git repository or from version.json.
     */
    public function getCommit(): string
    {
        // 1. Check version.json / config
        $file = $this->getFileData();
        $configCommit = config('version.commit') ?: ($file['commit'] ?? null);

        // 2. Query git directly if git is available
        try {
            $gitHead = base_path('.git/HEAD');
            if (File::exists($gitHead)) {
                $headContent = trim(File::get($gitHead));
                if (str_starts_with($headContent, 'ref:')) {
                    $refPath = base_path('.git/' . trim(substr($headContent, 4)));
                    if (File::exists($refPath)) {
                        return substr(trim(File::get($refPath)), 0, 7);
                    }
                } elseif (strlen($headContent) >= 7) {
                    return substr($headContent, 0, 7);
                }
            }
        } catch (\Throwable $e) {}

        return $configCommit ?: 'prod';
    }

    /**
     * Get the application release date (Y-m-d).
     */
    public function getReleaseDate(): string
    {
        $file = $this->getFileData();
        return config('version.release_date') ?: ($file['release_date'] ?? date('Y-m-d'));
    }

    /**
     * Get human-readable release date (e.g. 'September 12, 2026').
     */
    public function getFormattedReleaseDate(): string
    {
        try {
            return \Carbon\Carbon::parse($this->getReleaseDate())->format('F d, Y');
        } catch (\Throwable $e) {
            return $this->getReleaseDate();
        }
    }

    /**
     * Get the environment string (Production / Local / Staging).
     */
    public function getEnvironment(): string
    {
        return ucfirst(app()->environment());
    }

    /**
     * Get the installed version on client or local system.
     */
    public function getInstalledVersion(): string
    {
        // 1. Check if explicitly overridden via config (e.g. tests)
        $configInstalled = config('version.installed_version') ?: config('changelog.installed_version');
        if (!empty($configInstalled) && $configInstalled !== '2.4.0') {
            return (string)$configInstalled;
        }

        // 2. Check database setting
        try {
            $installed = Setting::get('installed_version');
            if (!empty($installed)) {
                return (string)$installed;
            }
        } catch (\Throwable $e) {}

        return $this->getVersion();
    }

    /**
     * Determine if installed version matches or is newer than current release.
     */
    public function isUpToDate(): bool
    {
        $installed = $this->getInstalledVersion();
        $current = $this->getVersion();
        return version_compare($installed, $current, '>=');
    }

    /**
     * Get total count of applied database migrations.
     */
    public function getMigrationsCount(): int
    {
        try {
            return DB::table('migrations')->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Return comprehensive system version and build telemetry.
     */
    public function getFullMetadata(): array
    {
        return [
            'name'                     => config('version.name', 'Smart Classroom Attendance System'),
            'version'                  => $this->getVersion(),
            'version_tag'              => $this->getVersionTag(),
            'version_display'          => $this->getVersionDisplay(),
            'build'                    => $this->getBuild(),
            'commit'                   => $this->getCommit(),
            'release_date'             => $this->getReleaseDate(),
            'release_date_formatted'   => $this->getFormattedReleaseDate(),
            'environment'              => $this->getEnvironment(),
            'channel'                  => config('version.channel', 'stable'),
            'installed_version'        => $this->getInstalledVersion(),
            'latest_version'           => $this->getVersion(),
            'is_up_to_date'            => $this->isUpToDate(),
            'database_migrations_count'=> $this->getMigrationsCount(),
        ];
    }

    /**
     * Increment semantic version (patch, minor, major).
     *
     * @param string $version Current version string (e.g. '2.4.0')
     * @param string $type 'patch' | 'minor' | 'major'
     * @return string
     */
    public function incrementSemver(string $version, string $type = 'patch'): string
    {
        $clean = ltrim(trim($version), 'v');
        $parts = explode('.', $clean);
        while (count($parts) < 3) {
            $parts[] = '0';
        }

        $major = (int)$parts[0];
        $minor = (int)$parts[1];
        $patch = (int)$parts[2];

        switch (strtolower(trim($type))) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case 'minor':
                $minor++;
                $patch = 0;
                break;
            case 'patch':
            default:
                $patch++;
                break;
        }

        return "{$major}.{$minor}.{$patch}";
    }

    /**
     * Create a new application release end-to-end:
     * - Bumps semantic version in version.json
     * - Increments build identifier
     * - Synchronizes package.json and manifest.json
     * - Bumps Service Worker cache version in sw.js
     * - Stores in Setting database table
     * - Clears application view/config/pwa caches
     *
     * @param string $type 'patch' | 'minor' | 'major' | 'custom'
     * @param string|null $explicitVersion Optional custom semver (e.g. '3.0.0')
     * @return array Resulting release telemetry
     */
    public function createRelease(string $type = 'patch', ?string $explicitVersion = null): array
    {
        $currentVer = $this->getVersion();
        
        if (!empty($explicitVersion)) {
            $targetVer = ltrim(trim($explicitVersion), 'v');
        } else {
            $targetVer = $this->incrementSemver($currentVer, $type);
        }

        // Generate next build number (e.g. 20260912.002)
        $todayPrefix = date('Ymd');
        $currentBuild = $this->getBuild();
        $buildSeq = 1;
        if (str_starts_with($currentBuild, $todayPrefix . '.')) {
            $buildSeq = ((int)substr($currentBuild, strlen($todayPrefix) + 1)) + 1;
        }
        $targetBuild = sprintf('%s.%03d', $todayPrefix, $buildSeq);
        $targetDate = date('Y-m-d');
        $commit = $this->getCommit();

        // 1. Update version.json
        $versionPayload = [
            'version'      => $targetVer,
            'build'        => $targetBuild,
            'commit'       => $commit,
            'release_date' => $targetDate,
            'channel'      => 'stable',
            'name'         => config('version.name', 'Smart Classroom Attendance System'),
        ];
        $versionFile = base_path('version.json');
        File::put($versionFile, json_encode($versionPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->fileData = $versionPayload;

        // 2. Synchronize package.json
        $packagePath = base_path('package.json');
        if (File::exists($packagePath)) {
            $pkg = File::get($packagePath);
            $pkg = preg_replace('/"version"\s*:\s*"[^"]+"/', "\"version\": \"{$targetVer}\"", $pkg);
            File::put($packagePath, $pkg);
        }

        // 3. Synchronize manifest.json
        $manifestPath = public_path('manifest.json');
        if (File::exists($manifestPath)) {
            $mf = File::get($manifestPath);
            $mf = preg_replace('/"version"\s*:\s*"[^"]+"/', "\"version\": \"{$targetVer}\"", $mf);
            File::put($manifestPath, $mf);
        }

        // 4. Bump sw.js Service Worker Cache Version
        $swPath = public_path('sw.js');
        $newSwVersion = 'v344';
        if (File::exists($swPath)) {
            $swContent = File::get($swPath);
            $currentNum = 344;
            if (preg_match('/CACHE_VERSION\s*=\s*[\'"]v?(\d+)[\'"]/', $swContent, $matches)) {
                $currentNum = (int)$matches[1];
            }
            $newSwVersion = 'v' . ($currentNum + 1);

            $swContent = preg_replace('/const\s+CACHE_VERSION\s*=\s*[\'"][^\'"]+[\'"];/', "const CACHE_VERSION = '{$newSwVersion}';", $swContent);
            $swContent = preg_replace('/const\s+CACHE_NAME\s*=\s*[`\'"][^`\'"]+[`\'"];/', "const CACHE_NAME = `attendance-{$newSwVersion}`;", $swContent);
            $swContent = preg_replace('/const\s+RUNTIME_CACHE_NAME\s*=\s*[`\'"][^`\'"]+[`\'"];/', "const RUNTIME_CACHE_NAME = `attendance-runtime-{$newSwVersion}`;", $swContent);

            $timestampIso = now()->toIso8601String();
            if (preg_match('/\/\* BUMP_TIMESTAMP:.*?\*\//s', $swContent)) {
                $swContent = preg_replace('/\/\* BUMP_TIMESTAMP:.*?\*\//s', "/* BUMP_TIMESTAMP: {$timestampIso} */", $swContent);
            } else {
                $swContent = "/* BUMP_TIMESTAMP: {$timestampIso} */\n" . $swContent;
            }

            File::put($swPath, $swContent);
        }

        // 5. Update Database Setting
        try {
            Setting::set('system_version', $targetVer);
            Setting::set('installed_version', $targetVer);
        } catch (\Throwable $e) {}

        // 6. Invalidate Cache
        try {
            Cache::flush();
        } catch (\Throwable $e) {}
        Cache::forever('pwa_sw_version', $newSwVersion);

        return [
            'previous_version' => $currentVer,
            'version'          => $targetVer,
            'version_tag'      => 'v' . $targetVer,
            'build'            => $targetBuild,
            'commit'           => $commit,
            'sw_version'       => $newSwVersion,
            'release_date'     => $targetDate,
        ];
    }

    /**
     * Get the service worker cache version (e.g. 'v345') dynamically from sw.js or cache.
     */
    public function getSwVersion(): string
    {
        $cached = Cache::get('pwa_sw_version');
        if (!empty($cached)) {
            return (string)$cached;
        }

        $swPath = public_path('sw.js');
        if (File::exists($swPath)) {
            $content = @file_get_contents($swPath);
            if ($content && preg_match('/CACHE_VERSION\s*=\s*[\'"](v?\d+)[\'"]/', $content, $matches)) {
                $ver = str_starts_with($matches[1], 'v') ? $matches[1] : 'v' . $matches[1];
                Cache::forever('pwa_sw_version', $ver);
                return $ver;
            }
        }

        return 'v345';
    }
}
