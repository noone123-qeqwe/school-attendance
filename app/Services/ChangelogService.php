<?php

namespace App\Services;

class ChangelogService
{
    /**
     * Retrieve the changelog metadata for a specific or latest version.
     *
     * @param string|null $rawVersion
     * @return array
     */
    public function getRelease(?string $rawVersion = null): array
    {
        $releases = config('changelog.releases', []);
        $defaultVersion = config('changelog.default_version', '2.3.0');

        if (empty($rawVersion)) {
            $rawVersion = $defaultVersion;
        }

        // 1. Clean version tag (strip timestamp suffix, e.g. v143_1725546549 -> v143)
        $cleanTag = trim($rawVersion);
        if (str_contains($cleanTag, '_')) {
            $cleanTag = explode('_', $cleanTag)[0];
        }

        // 2. Direct key match (e.g. '1.4.3' or 'v1.4.3')
        if (isset($releases[$cleanTag])) {
            return $this->formatRelease($releases[$cleanTag]);
        }

        // 3. Match without leading 'v' / 'V'
        $stripped = ltrim($cleanTag, 'vV');
        if (isset($releases[$stripped])) {
            return $this->formatRelease($releases[$stripped]);
        }

        // 4. Match integer strings like '143' -> '1.4.3'
        $normalizedSemver = $this->normalizeToSemver($stripped);
        if ($normalizedSemver && isset($releases[$normalizedSemver])) {
            return $this->formatRelease($releases[$normalizedSemver]);
        }

        // 5. Search by version or version_tag property inside release entries
        foreach ($releases as $key => $rel) {
            $ver = $rel['version'] ?? $key;
            $vTag = $rel['version_tag'] ?? ('v' . $ver);
            if (
                $ver === $stripped ||
                $ver === $normalizedSemver ||
                $vTag === $cleanTag ||
                $vTag === ('v' . $stripped) ||
                $vTag === ('v' . $normalizedSemver)
            ) {
                return $this->formatRelease($rel);
            }
        }

        // 6. If no exact release entry was found, build a safe dynamic fallback release
        $fallbackVersion = $normalizedSemver ?: ($stripped ?: $defaultVersion);
        return $this->buildFallbackRelease($fallbackVersion);
    }

    /**
     * Get the latest release from the configured changelog.
     *
     * @return array
     */
    public function getLatestRelease(): array
    {
        $releases = config('changelog.releases', []);
        $first = reset($releases);

        if (!empty($first)) {
            return $this->formatRelease($first);
        }

        return $this->buildFallbackRelease(config('changelog.default_version', '2.3.0'));
    }

    /**
     * Retrieve all releases.
     *
     * @return array
     */
    public function getAllReleases(): array
    {
        $releases = config('changelog.releases', []);
        $formatted = [];
        foreach ($releases as $rel) {
            $formatted[] = $this->formatRelease($rel);
        }
        return $formatted;
    }

    /**
     * Normalize strings like '143' -> '1.4.3', '140' -> '1.4.0', '14' -> '1.4.0'
     *
     * @param string $version
     * @return string
     */
    public function normalizeToSemver(string $version): string
    {
        $cleaned = preg_replace('/[^0-9.]/', '', $version);
        if (empty($cleaned)) {
            return '';
        }

        // If it already has dots, return as-is
        if (str_contains($cleaned, '.')) {
            return $cleaned;
        }

        $len = strlen($cleaned);
        if ($len === 3) {
            // e.g. 143 -> 1.4.3
            return $cleaned[0] . '.' . $cleaned[1] . '.' . $cleaned[2];
        }

        if ($len === 2) {
            // e.g. 14 -> 1.4.0
            return $cleaned[0] . '.' . $cleaned[1] . '.0';
        }

        return $cleaned;
    }

    /**
     * Format a release data array with standardized keys and types.
     *
     * @param array $release
     * @return array
     */
    protected function formatRelease(array $release): array
    {
        $version = $release['version'] ?? config('changelog.default_version', '2.3.0');
        $versionTag = $release['version_tag'] ?? ('v' . ltrim($version, 'v'));

        $features = array_values((array)($release['features'] ?? []));
        $improvements = array_values((array)($release['improvements'] ?? []));
        $bugFixes = array_values((array)($release['bugFixes'] ?? $release['bug_fixes'] ?? []));
        $security = array_values((array)($release['security'] ?? []));

        return [
            'version' => $version,
            'version_tag' => $versionTag,
            'version_display' => 'VERSION ' . ltrim($version, 'v'),
            'title' => $release['title'] ?? 'Software Update Available',
            'description' => $release['description'] ?? 'A new version of Smart Attendance is ready.',
            'features' => $features,
            'improvements' => $improvements,
            'bugFixes' => $bugFixes,
            'bug_fixes' => $bugFixes, // Alias for backwards-compatibility
            'security' => $security,
            'has_security_fixes' => !empty($security),
            'released_at' => $release['released_at'] ?? null,
        ];
    }

    /**
     * Generate dynamic fallback release metadata when an unlisted version is requested.
     *
     * @param string $version
     * @return array
     */
    protected function buildFallbackRelease(string $version): array
    {
        $cleanVer = ltrim($version, 'v');
        return [
            'version' => $cleanVer,
            'version_tag' => 'v' . $cleanVer,
            'version_display' => 'VERSION ' . $cleanVer,
            'title' => 'Software Update Available',
            'description' => 'A new version of Smart Attendance is ready with latest performance optimizations and system improvements.',
            'features' => [
                'System performance and stability enhancements',
                'Service worker caching and real-time synchronization updates',
            ],
            'improvements' => [
                'Faster page navigation and responsive layout improvements',
                'Optimized background asset loading',
            ],
            'bugFixes' => [
                'General reliability fixes and stability improvements',
            ],
            'bug_fixes' => [
                'General reliability fixes and stability improvements',
            ],
            'security' => [],
            'has_security_fixes' => false,
            'released_at' => date('Y-m-d'),
        ];
    }
}
