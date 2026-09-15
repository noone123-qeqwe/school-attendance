<?php

/**
 * Automated Application Versioning Script
 *
 * Increments semantic version (MAJOR.MINOR.PATCH) automatically on every Git push / CI build.
 * Single source of truth: version.json
 * Synchronizes:
 *   - version.json
 *   - package.json
 *   - public/manifest.json
 *   - public/sw.js (Service Worker cache version)
 *
 * Usage:
 *   php scripts/version-bump.php [--type=patch|minor|major] [--set=2.4.7] [--ci] [--dry-run]
 */

$rootDir = dirname(__DIR__);
$versionJsonPath = $rootDir . DIRECTORY_SEPARATOR . 'version.json';
$packageJsonPath = $rootDir . DIRECTORY_SEPARATOR . 'package.json';
$manifestJsonPath = $rootDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'manifest.json';
$swJsPath = $rootDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'sw.js';

// Parse CLI arguments
$options = getopt('', ['type:', 'set:', 'ci', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo "Smart Attendance - Automated Application Versioning\n";
    echo "Usage:\n";
    echo "  php scripts/version-bump.php [options]\n\n";
    echo "Options:\n";
    echo "  --type=patch|minor|major  Increment type (default: patch)\n";
    echo "  --set=<version>           Explicitly set semantic version (e.g. 2.4.7)\n";
    echo "  --ci                      Run in CI mode and populate GitHub Actions \$GITHUB_OUTPUT\n";
    echo "  --dry-run                 Compute version changes without writing to disk\n";
    echo "  --help                    Show this help message\n";
    exit(0);
}

$type = isset($options['type']) ? strtolower(trim((string)$options['type'])) : 'patch';
$explicitVersion = isset($options['set']) ? trim((string)$options['set']) : null;
$isCi = isset($options['ci']);
$isDryRun = isset($options['dry-run']);

// 1. Read current version from version.json (Single Source of Truth)
$versionData = [];
$currentVersion = '2.4.6'; // Default baseline
if (file_exists($versionJsonPath)) {
    $raw = @file_get_contents($versionJsonPath);
    if (!empty($raw)) {
        $decoded = @json_decode($raw, true);
        if (is_array($decoded)) {
            $versionData = $decoded;
            if (!empty($decoded['version'])) {
                $currentVersion = ltrim(trim((string)$decoded['version']), 'vV ');
            }
        }
    }
}

// 2. Compute New Version using Semantic Versioning (MAJOR.MINOR.PATCH)
if (!empty($explicitVersion)) {
    $newVersion = ltrim(trim($explicitVersion), 'vV ');
} else {
    // Support plain integers (1 -> 2 -> 3)
    if (is_numeric($currentVersion) && !str_contains($currentVersion, '.')) {
        $newVersion = (string)((int)$currentVersion + 1);
    } else {
        $parts = explode('.', $currentVersion);
        while (count($parts) < 3) {
            $parts[] = '0';
        }

        $major = (int)$parts[0];
        $minor = (int)$parts[1];
        $patch = (int)$parts[2];

        switch ($type) {
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
                // Strict integer increment: 2.4.9 -> 2.4.10 (never 2.4.91)
                $patch++;
                break;
        }

        $newVersion = "{$major}.{$minor}.{$patch}";
    }
}

// Guarantee strictly ascending progression (never reset to an older value)
if (version_compare($newVersion, $currentVersion, '<=')) {
    $parts = explode('.', $currentVersion);
    while (count($parts) < 3) {
        $parts[] = '0';
    }
    $parts[2] = ((int)$parts[2]) + 1;
    $newVersion = implode('.', $parts);
}

// 3. Compute Build Identifier and Git Commit
$todayPrefix = date('Ymd');
$currentBuild = $versionData['build'] ?? '';
$buildSeq = 1;
if (str_starts_with($currentBuild, $todayPrefix . '.')) {
    $buildSeq = ((int)substr($currentBuild, strlen($todayPrefix) + 1)) + 1;
}
$newBuild = sprintf('%s.%03d', $todayPrefix, $buildSeq);
$releaseDate = date('Y-m-d');

// Attempt to read short git commit
$gitCommit = $versionData['commit'] ?? 'prod';
$headFile = $rootDir . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR . 'HEAD';
if (file_exists($headFile)) {
    $headContent = trim((string)@file_get_contents($headFile));
    if (str_starts_with($headContent, 'ref:')) {
        $refPath = $rootDir . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR . trim(substr($headContent, 4));
        if (file_exists($refPath)) {
            $gitCommit = substr(trim((string)@file_get_contents($refPath)), 0, 7);
        }
    } elseif (strlen($headContent) >= 7) {
        $gitCommit = substr($headContent, 0, 7);
    }
}

// 4. Service Worker Cache Version Bump
$newSwVersion = 'v351';
$swBumped = false;
if (file_exists($swJsPath)) {
    $swContent = (string)@file_get_contents($swJsPath);
    $currentSwNum = 350;
    if (preg_match('/CACHE_VERSION\s*=\s*[\'"]v?(\d+)[\'"]/', $swContent, $matches)) {
        $currentSwNum = (int)$matches[1];
    }
    $newSwVersion = 'v' . ($currentSwNum + 1);

    if (!$isDryRun) {
        $swContent = preg_replace(
            '/const\s+CACHE_VERSION\s*=\s*[\'"][^\'"]+[\'"];/',
            "const CACHE_VERSION = '{$newSwVersion}';",
            $swContent
        );
        $swContent = preg_replace(
            '/const\s+CACHE_NAME\s*=\s*[`\'"][^`\'"]+[`\'"];/',
            "const CACHE_NAME = `attendance-{$newSwVersion}`;",
            $swContent
        );
        $swContent = preg_replace(
            '/const\s+RUNTIME_CACHE_NAME\s*=\s*[`\'"][^`\'"]+[`\'"];/',
            "const RUNTIME_CACHE_NAME = `attendance-runtime-{$newSwVersion}`;",
            $swContent
        );

        $timestampIso = date('c');
        if (preg_match('/\/\* BUMP_TIMESTAMP:.*?\*\//s', $swContent)) {
            $swContent = preg_replace(
                '/\/\* BUMP_TIMESTAMP:.*?\*\//s',
                "/* BUMP_TIMESTAMP: {$timestampIso} */",
                $swContent
            );
        } else {
            $swContent = "/* BUMP_TIMESTAMP: {$timestampIso} */\n" . $swContent;
        }

        @file_put_contents($swJsPath, $swContent);
        $swBumped = true;
    }
}

// 5. Update version.json
$newVersionData = array_merge($versionData, [
    'version'      => $newVersion,
    'build'        => $newBuild,
    'commit'       => $gitCommit,
    'release_date' => $releaseDate,
    'channel'      => $versionData['channel'] ?? 'stable',
    'name'         => $versionData['name'] ?? 'Smart Classroom Attendance System',
]);

if (!$isDryRun) {
    file_put_contents(
        $versionJsonPath,
        json_encode($newVersionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
    );
}

// 6. Update package.json
if (file_exists($packageJsonPath) && !$isDryRun) {
    $pkg = (string)file_get_contents($packageJsonPath);
    $pkg = preg_replace('/"version"\s*:\s*"[^"]+"/', "\"version\": \"{$newVersion}\"", $pkg);
    file_put_contents($packageJsonPath, $pkg);
}

// 7. Update manifest.json
if (file_exists($manifestJsonPath) && !$isDryRun) {
    $manifest = (string)file_get_contents($manifestJsonPath);
    $manifest = preg_replace('/"version"\s*:\s*"[^"]+"/', "\"version\": \"{$newVersion}\"", $manifest);
    file_put_contents($manifestJsonPath, $manifest);
}

// 8. Output Summary & Results
echo "\n==================================================\n";
echo "   AUTOMATIC APPLICATION VERSION BUMP APPLIED    \n";
echo "==================================================\n";
echo "  Previous Version : v{$currentVersion}\n";
echo "  New Version      : v{$newVersion}\n";
echo "  Build ID         : {$newBuild}\n";
echo "  Git Commit       : {$gitCommit}\n";
echo "  SW Cache Version : {$newSwVersion}\n";
echo "  Release Date     : {$releaseDate}\n";
echo "  Dry Run          : " . ($isDryRun ? "YES (files untouched)" : "NO (files updated)") . "\n";
echo "==================================================\n";
echo "Synchronized files:\n";
echo "  [✓] " . basename($versionJsonPath) . "\n";
echo "  [✓] " . basename($packageJsonPath) . "\n";
echo "  [✓] public/" . basename($manifestJsonPath) . "\n";
echo "  [✓] public/" . basename($swJsPath) . "\n\n";

// 9. If in GitHub Actions CI mode, output to GITHUB_OUTPUT environment file
if ($isCi || getenv('GITHUB_ACTIONS') === 'true') {
    $githubOutput = getenv('GITHUB_OUTPUT');
    if ($githubOutput && file_exists($githubOutput)) {
        $lines = [
            "new_version={$newVersion}",
            "previous_version={$currentVersion}",
            "new_version_tag=v{$newVersion}",
            "new_build={$newBuild}",
            "new_sw_version={$newSwVersion}",
            "bumped=true",
        ];
        @file_put_contents($githubOutput, implode("\n", $lines) . "\n", FILE_APPEND);
        echo "GitHub Actions \$GITHUB_OUTPUT populated successfully.\n";
    }
}

exit(0);
