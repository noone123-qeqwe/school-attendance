<?php

$appUrl = trim((string) env('APP_URL', ''));
$renderUrl = trim((string) env('RENDER_EXTERNAL_URL', ''));
$configuredAppHost = strtolower((string) (parse_url($appUrl, PHP_URL_HOST) ?: ''));
if ($appUrl === '' || ($renderUrl !== '' && in_array($configuredAppHost, ['localhost', '127.0.0.1', '::1'], true))) {
    $appUrl = $renderUrl !== '' ? $renderUrl : 'http://localhost';
}
$appParts = parse_url($appUrl);
$defaultOrigin = is_array($appParts) && isset($appParts['scheme'], $appParts['host'])
    ? strtolower($appParts['scheme']) . '://' . strtolower($appParts['host'])
        . (isset($appParts['port']) ? ':' . $appParts['port'] : '')
    : 'http://localhost';
$defaultRpId = is_array($appParts) ? strtolower((string) ($appParts['host'] ?? 'localhost')) : 'localhost';
$configuredRpId = trim((string) env('WEBAUTHN_RP_ID', ''));
$configuredOrigins = trim((string) env('WEBAUTHN_ALLOWED_ORIGINS', ''));
$configuredAndroidOrigins = trim((string) env('WEBAUTHN_ALLOWED_ANDROID_ORIGINS', ''));
$androidOrigins = [];

// Use the same signing certificates as the Digital Asset Links served by this app.
// Keep an explicit environment allowlist authoritative for custom deployments.
if ($configuredAndroidOrigins === '') {
    $assetLinksPath = dirname(__DIR__) . '/public/.well-known/assetlinks.json';
    $assetLinks = is_file($assetLinksPath) ? json_decode(file_get_contents($assetLinksPath), true) : [];
    foreach ((array) $assetLinks as $statement) {
        $target = $statement['target'] ?? [];
        if (($target['namespace'] ?? null) !== 'android_app'
            || ($target['package_name'] ?? null) !== 'com.school.attendance'
            || !in_array('delegate_permission/common.get_login_creds', (array) ($statement['relation'] ?? []), true)) {
            continue;
        }
        foreach ((array) ($target['sha256_cert_fingerprints'] ?? []) as $fingerprint) {
            if (is_string($fingerprint) && preg_match('/\A(?:[a-f0-9]{2}:){31}[a-f0-9]{2}\z/i', $fingerprint)) {
                $hash = hex2bin(str_replace(':', '', $fingerprint));
                $androidOrigins[] = 'android:apk-key-hash:' . rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
            }
        }
    }
} else {
    $androidOrigins = array_filter(array_map('trim', explode(',', $configuredAndroidOrigins)));
}

return [
    // WebAuthn is bound to deployment configuration, never to request headers.
    'rp_id' => strtolower(trim($configuredRpId !== '' ? $configuredRpId : $defaultRpId, ". \t\n\r\0\x0B")),

    // Exact browser origins, including scheme and non-default port when used.
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', $configuredOrigins !== '' ? $configuredOrigins : $defaultOrigin)
    ))),

    'allowed_android_origins' => array_values(array_unique($androidOrigins)),
];
