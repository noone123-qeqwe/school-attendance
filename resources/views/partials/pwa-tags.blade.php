@php
    $versionService = app(\App\Services\VersionService::class);
    $installedVersion = $appInstalledVersion ?? $versionService->getInstalledVersion();
    $latestVersion = $appVersion ?? $versionService->getVersion();
    $buildId = $appBuild ?? $versionService->getBuild();
    $commitHash = $appCommit ?? $versionService->getCommit();
    $swCacheVer = $versionService->getSwVersion();
    $swFileMtime = file_exists(public_path('sw.js')) ? filemtime(public_path('sw.js')) : time();
    $swQueryVer = 'v' . preg_replace('/[^0-9]/', '', (string)$swCacheVer) . '_' . $swFileMtime;
    $initialChangelog = app(\App\Services\ChangelogService::class)->getRelease((string)$latestVersion);
@endphp
<!-- PWA Head Meta Tags -->
<meta name="theme-color" content="#141010">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Smart Attendance">
<meta name="application-name" content="Smart Attendance">
<meta name="msapplication-TileColor" content="#141010">
<meta name="msapplication-TileImage" content="/images/icons/icon-144x144.png">
<meta name="app-installed-version" content="{{ $installedVersion }}">
<meta name="app-installed-version-tag" content="v{{ ltrim($installedVersion, 'vV ') }}">
<meta name="app-latest-version" content="{{ $latestVersion }}">
<meta name="app-latest-version-tag" content="v{{ ltrim($latestVersion, 'vV ') }}">
<meta name="app-build-id" content="{{ $buildId }}">
<meta name="app-commit-hash" content="{{ $commitHash }}">
<meta name="sw-build-version" content="{{ $swCacheVer }}">
<meta name="sw-build-mtime" content="{{ $swFileMtime }}">
@if(!str_contains(request()->route()?->getName() ?? '', 'api.'))
<meta name="csrf-token" content="{{ csrf_token() }}">
@endif

<!-- PWA Manifest & Icons -->
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">
<link rel="apple-touch-icon" sizes="180x180" href="/images/icons/icon-180x180.png">
<link rel="apple-touch-icon" sizes="152x152" href="/images/icons/icon-152x152.png">
<link rel="apple-touch-icon" sizes="128x128" href="/images/icons/icon-128x128.png">
<link rel="icon" type="image/png" sizes="192x192" href="/images/icons/icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="/images/icons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/images/icons/favicon-16x16.png">

<link rel="stylesheet" href="{{ asset('css/pwa-ui.css') }}?v={{ filemtime(public_path('css/pwa-ui.css')) }}">
