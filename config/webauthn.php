<?php

$appUrl = trim((string) env('APP_URL', ''));
if ($appUrl === '') {
    $appUrl = (string) env('RENDER_EXTERNAL_URL', 'http://localhost');
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

return [
    // WebAuthn is bound to deployment configuration, never to request headers.
    'rp_id' => strtolower(trim($configuredRpId !== '' ? $configuredRpId : $defaultRpId, ". \t\n\r\0\x0B")),

    // Exact browser origins, including scheme and non-default port when used.
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', $configuredOrigins !== '' ? $configuredOrigins : $defaultOrigin)
    ))),

    // Add exact Android WebAuthn origins only when native passkey support is configured.
    'allowed_android_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', $configuredAndroidOrigins)
    ))),
];
