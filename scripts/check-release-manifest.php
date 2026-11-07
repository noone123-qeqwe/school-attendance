<?php

/** Verify that every web artifact describes the same deployable release. */
$root = dirname(__DIR__);
$readJson = static function (string $relative) use ($root): array {
    $data = json_decode((string) @file_get_contents($root . '/' . $relative), true);
    if (!is_array($data)) {
        fwrite(STDERR, "Invalid or missing {$relative}\n");
        exit(1);
    }
    return $data;
};

$release = $readJson('version.json');
$package = $readJson('package.json');
$lock = $readJson('package-lock.json');
$manifest = $readJson('public/manifest.json');
$version = $release['version'] ?? null;
$errors = [];

if (!is_string($version) || !preg_match('/^\d+\.\d+\.\d+$/', $version)) {
    $errors[] = 'version.json must contain a semantic web version';
}
foreach ([
    'version.json installed_version' => $release['installed_version'] ?? null,
    'package.json version' => $package['version'] ?? null,
    'package-lock.json version' => $lock['version'] ?? null,
    'package-lock.json root package version' => $lock['packages']['']['version'] ?? null,
    'public/manifest.json version' => $manifest['version'] ?? null,
] as $label => $actual) {
    if ($actual !== $version) {
        $errors[] = "{$label} does not match web version {$version}";
    }
}

if (empty($release['build'])) {
    $errors[] = 'version.json build is missing';
}

$worker = (string) @file_get_contents($root . '/public/sw.js');
if (!preg_match('/const CACHE_VERSION = [\x27\x22](v\d+)[\x27\x22];?/', $worker, $matches)) {
    $errors[] = 'Service worker cache version is missing';
} else {
    $cacheVersion = $matches[1];
    $expectedCacheVersion = 'v' . preg_replace('/\D/', '', (string) ($release['build'] ?? ''));
    if (!preg_match('/const APP_VERSION = [\x27\x22]' . preg_quote((string) $version, '/') . '[\x27\x22];/', $worker)) {
        $errors[] = 'Service worker APP_VERSION does not match version.json';
    }
    if ($cacheVersion !== $expectedCacheVersion) {
        $errors[] = 'Service worker cache version does not match version.json build';
    }
    $backtick = chr(96);
    if (!str_contains($worker, 'const CACHE_NAME = ' . $backtick . 'attendance-' . '$' . '{CACHE_VERSION}' . $backtick . ';')
        || !str_contains($worker, 'const RUNTIME_CACHE_NAME = ' . $backtick . 'attendance-runtime-' . '$' . '{CACHE_VERSION}' . $backtick . ';')) {
        $errors[] = 'Service worker cache names do not derive from its cache version';
    }
}

if ($errors) {
    foreach ($errors as $error) {
        fwrite(STDERR, "Release manifest error: {$error}\n");
    }
    exit(1);
}

echo "Web release {$version} ({$release['build']}) is consistent.\n";
