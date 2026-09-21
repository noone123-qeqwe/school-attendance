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
<meta name="theme-color" content="#110A0A">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Smart Attendance">
<meta name="application-name" content="Smart Attendance">
<meta name="msapplication-TileColor" content="#110A0A">
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

<style>
    /* ════════════════════════════════════════════════════════════════════
       GLOBAL SCROLL BEHAVIOR & PULL-TO-REFRESH PREVENTION
       Preserves smooth natural scrolling up/down without triggering
       unintended page reloads, state resets, or top jumps.
       ════════════════════════════════════════════════════════════════════ */
    html, body {
        overscroll-behavior: none !important;
        overscroll-behavior-y: none !important;
        overscroll-behavior-x: none !important;
    }

    /* PWA Install Banners & Overlays */
    .pwa-install-banner {
        position: fixed;
        bottom: calc(88px + env(safe-area-inset-bottom, 16px));
        left: 16px;
        right: 16px;
        max-width: 440px;
        margin: 0 auto;
        background: rgba(26, 17, 16, 0.96);
        border: 1px solid rgba(207, 164, 111, 0.35);
        border-radius: 20px;
        padding: 16px 20px;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.65), 0 0 25px rgba(207, 164, 111, 0.2);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        z-index: 99999;
        display: none;
        align-items: center;
        gap: 16px;
        animation: pwaSlideUp 0.45s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes pwaSlideUp {
        from { transform: translateY(100px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .pwa-banner-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: #110A0A;
        border: 1px solid rgba(207, 164, 111, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        overflow: hidden;
    }

    .pwa-banner-icon img {
        width: 36px;
        height: 36px;
        object-fit: contain;
    }

    .pwa-banner-content {
        flex: 1;
        min-width: 0;
    }

    .pwa-banner-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #F3E7CD;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pwa-banner-subtitle {
        font-size: 0.8rem;
        color: #B39B82;
        line-height: 1.35;
    }

    .pwa-banner-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pwa-btn-install {
        background: linear-gradient(135deg, #CFA46F 0%, #8F6E4A 100%);
        color: #110A0A;
        border: none;
        border-radius: 10px;
        padding: 9px 18px;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        box-shadow: 0 4px 14px rgba(207, 164, 111, 0.35);
    }

    .pwa-btn-install:hover {
        background: linear-gradient(135deg, #DFB783 0%, #9E7B54 100%);
        transform: translateY(-1px);
    }

    .pwa-btn-close {
        background: transparent;
        border: none;
        color: #B39B82;
        font-size: 1.25rem;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: color 0.2s;
    }

    .pwa-btn-close:hover {
        color: #F3E7CD;
    }

    /* ── Redesigned Mobile App Download & Installation Modal ── */
    .pwa-ios-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.78);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        z-index: 100000;
        display: none;
        align-items: flex-end;
        justify-content: center;
        padding: 0;
        animation: pwaFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @media (min-width: 769px) {
        .pwa-ios-modal {
            align-items: center;
            padding: 20px;
        }
    }

    .pwa-ios-sheet {
        background: linear-gradient(180deg, #1C1111 0%, #120A0A 100%);
        border: 1.5px solid rgba(207, 164, 111, 0.35);
        border-bottom: none;
        border-radius: 26px 26px 0 0;
        padding: 16px 18px 24px;
        max-width: 440px;
        width: 100%;
        color: #F3E7CD;
        text-align: center;
        box-shadow: 0 -12px 40px rgba(0, 0, 0, 0.75);
        position: relative;
        max-height: min(90vh, 640px);
        overflow-y: auto;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }

    .pwa-ios-sheet::-webkit-scrollbar {
        display: none;
    }

    @media (min-width: 769px) {
        .pwa-ios-sheet {
            border-radius: 24px;
            border-bottom: 1.5px solid rgba(207, 164, 111, 0.35);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.85);
            padding: 22px 22px 26px;
        }
    }

    .pwa-sheet-handle {
        width: 38px;
        height: 4px;
        background: rgba(255, 255, 255, 0.22);
        border-radius: 99px;
        margin: 0 auto 12px;
    }
    @media (min-width: 769px) {
        .pwa-sheet-handle {
            display: none;
        }
    }

    .pwa-sheet-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .pwa-sheet-title-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pwa-sheet-title-icon {
        font-size: 1rem;
        color: var(--gold, #CFA46F);
        display: flex;
        align-items: center;
    }

    .pwa-sheet-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: #F3E7CD;
        letter-spacing: 0.4px;
        margin: 0;
        text-transform: uppercase;
    }

    .pwa-sheet-close-btn {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #B39B82;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.15rem;
        line-height: 1;
        transition: all 0.2s;
        -webkit-tap-highlight-color: transparent;
    }
    .pwa-sheet-close-btn:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #F3E7CD;
    }

    /* ── Main App Hero Card ── */
    .pwa-hero-card {
        background: linear-gradient(145deg, rgba(207, 164, 111, 0.12) 0%, rgba(28, 16, 16, 0.9) 100%);
        border: 1.5px solid rgba(207, 164, 111, 0.32);
        border-radius: 18px;
        padding: 14px 14px 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(207, 164, 111, 0.2);
        margin-bottom: 12px;
        text-align: center;
    }

    .pwa-hero-header {
        display: flex;
        align-items: center;
        gap: 12px;
        text-align: left;
        margin-bottom: 10px;
    }

    .pwa-hero-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        border: 1.5px solid rgba(207, 164, 111, 0.45);
        object-fit: cover;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        flex-shrink: 0;
        background: #120A0A;
    }

    .pwa-hero-info {
        flex: 1;
        min-width: 0;
    }

    .pwa-hero-badge {
        font-size: 0.68rem;
        color: #E8C064;
        background: rgba(207, 164, 111, 0.15);
        border: 1px solid rgba(207, 164, 111, 0.3);
        border-radius: 6px;
        padding: 1.5px 7px;
        font-weight: 700;
        letter-spacing: 0.2px;
    }

    .pwa-hero-verified {
        font-size: 0.68rem;
        color: #4ADE80;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-weight: 600;
    }

    .pwa-hero-title {
        font-size: 0.86rem;
        font-weight: 800;
        color: #F3E7CD;
        letter-spacing: 0.2px;
        line-height: 1.25;
        margin: 4px 0 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pwa-hero-meta {
        font-size: 0.72rem;
        color: rgba(243, 231, 205, 0.65);
        font-variant-numeric: tabular-nums;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .pwa-meta-dot {
        opacity: 0.4;
    }

    .pwa-hero-tagline {
        font-size: 0.78rem;
        color: #B39B82;
        line-height: 1.35;
        margin: 0 0 12px;
        text-align: left;
    }

    .pwa-cta-container {
        position: relative;
        width: 100%;
    }

    .pwa-hero-dl-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        min-height: 48px;
        background: linear-gradient(135deg, #E8C064 0%, #CFA46F 100%);
        color: #110A0A;
        border: none;
        border-radius: 12px;
        font-size: 0.92rem;
        font-weight: 800;
        letter-spacing: 0.4px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        box-shadow: 0 6px 20px rgba(232, 192, 100, 0.35);
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        -webkit-tap-highlight-color: transparent;
    }

    .pwa-hero-dl-btn:active {
        transform: scale(0.98);
        filter: brightness(0.95);
    }

    .pwa-dl-progress-bar {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3.5px;
        background: rgba(17, 10, 10, 0.8);
        width: 0%;
        transition: width 0.3s ease;
    }

    .pwa-dl-status-banner {
        display: none;
        align-items: center;
        gap: 8px;
        background: rgba(34, 197, 94, 0.12);
        border: 1px solid rgba(34, 197, 94, 0.35);
        border-radius: 10px;
        padding: 8px 12px;
        color: #86EFAC;
        font-size: 0.78rem;
        font-weight: 600;
        text-align: left;
        margin-top: 8px;
        line-height: 1.35;
        animation: pwaFadeIn 0.3s ease;
    }

    .pwa-hero-trust {
        font-size: 0.72rem;
        color: rgba(207, 164, 111, 0.75);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        margin-top: 9px;
        font-weight: 600;
    }

    /* ── Simple 3-Step Visual Process ── */
    .pwa-mini-steps-section {
        margin-bottom: 10px;
        text-align: left;
    }

    .pwa-mini-steps-title {
        font-size: 0.68rem;
        font-weight: 800;
        color: rgba(207, 164, 111, 0.8);
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .pwa-mini-steps-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
    }

    .pwa-mini-step-box {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(207, 164, 111, 0.18);
        border-radius: 10px;
        padding: 8px 4px;
        text-align: center;
    }

    .pwa-mini-step-num {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #CFA46F;
        color: #110A0A;
        font-weight: 800;
        font-size: 0.72rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 4px;
    }

    .pwa-mini-step-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #F3E7CD;
        text-transform: uppercase;
    }

    .pwa-mini-step-hint {
        font-size: 0.64rem;
        color: #B39B82;
        line-height: 1.25;
        margin-top: 2px;
    }

    /* ── Collapsible Troubleshooting Section ── */
    .pwa-help-details {
        margin-top: 8px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.02);
        transition: all 0.2s ease;
    }

    .pwa-help-details[open] {
        border-color: rgba(207, 164, 111, 0.3);
        background: rgba(0, 0, 0, 0.35);
    }

    .pwa-help-summary {
        padding: 10px 14px;
        font-size: 0.76rem;
        font-weight: 600;
        color: #CFA46F;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        list-style: none;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }

    .pwa-help-summary::-webkit-details-marker {
        display: none;
    }

    .pwa-help-chevron {
        font-size: 0.75rem;
        transition: transform 0.2s ease;
    }

    .pwa-help-details[open] .pwa-help-chevron {
        transform: rotate(180deg);
    }

    .pwa-help-content {
        padding: 10px 14px 12px;
        font-size: 0.75rem;
        color: #F3E7CD;
        text-align: left;
        line-height: 1.45;
        border-top: 1px solid rgba(207, 164, 111, 0.15);
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .pwa-help-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .pwa-help-bullet {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #CFA46F;
        margin-top: 6px;
        flex-shrink: 0;
    }

    /* ── Bottom Action Buttons ── */
    .pwa-sheet-bottom-actions {
        display: flex;
        gap: 8px;
        width: 100%;
        margin-top: 14px;
    }

    .pwa-sheet-btn-secondary {
        flex: 1;
        min-height: 44px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 12px;
        color: #F3E7CD;
        font-size: 0.84rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        -webkit-tap-highlight-color: transparent;
    }

    .pwa-sheet-btn-secondary:hover {
        background: rgba(255, 255, 255, 0.14);
    }

    .pwa-sheet-btn-check {
        flex: 1;
        min-height: 44px;
        background: rgba(207, 164, 111, 0.12);
        border: 1px solid rgba(207, 164, 111, 0.3);
        border-radius: 12px;
        color: #E8C064;
        font-size: 0.84rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        -webkit-tap-highlight-color: transparent;
    }

    .pwa-sheet-btn-check:hover {
        background: rgba(207, 164, 111, 0.2);
    }

    @keyframes pwaSpin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .pwa-spin-fast {
        animation: pwaSpin 0.75s linear infinite !important;
        display: inline-block !important;
    }

    /* Connectivity Toast */
    .pwa-network-toast {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%) translateY(-60px);
        padding: 10px 22px;
        border-radius: 99px;
        font-size: 0.85rem;
        font-weight: 700;
        z-index: 100000;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
        opacity: 0;
        pointer-events: none;
    }

    .pwa-network-toast.show {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }

    .pwa-network-toast.offline {
        background: rgba(239, 68, 68, 0.95);
        color: #FFFFFF;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .pwa-network-toast.online {
        background: rgba(34, 197, 94, 0.95);
        color: #FFFFFF;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* ═══════════════════════════════════════════════════
       PWA UPDATE POPUP — Premium Redesign
    ═══════════════════════════════════════════════════ */

    .pwa-update-backdrop {
        position: fixed !important;
        inset: 0 !important;
        background: rgba(0, 0, 0, 0.75) !important;
        backdrop-filter: blur(12px) saturate(160%) !important;
        -webkit-backdrop-filter: blur(12px) saturate(160%) !important;
        z-index: 100000 !important;
        opacity: 0;
        transition: opacity 0.3s ease !important;
        pointer-events: auto;
        display: none;
    }
    .pwa-update-backdrop.show {
        display: block !important;
        opacity: 1 !important;
    }
    @keyframes pwaSlideUpEnterprise {
        0%  { opacity: 0; transform: translate(-50%, calc(-50% + 24px)) scale(0.95); }
        100%{ opacity: 1; transform: translate(-50%, -50%) scale(1); }
    }
    @keyframes pwaShimmer {
        0%   { background-position: -200% center; }
        100% { background-position:  200% center; }
    }
    @keyframes pwaPulseDot {
        0%,100% { transform: scale(1);   opacity: 1;   box-shadow: 0 0 8px  #22C55E; }
        50%     { transform: scale(1.3); opacity: 0.7; box-shadow: 0 0 18px #22C55E; }
    }
    @keyframes pwaIconFloat {
        0%,100% { transform: translateY(0); }
        50%     { transform: translateY(-4px); }
    }
    @keyframes pwaGlowRotate {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }
    @keyframes ptr-spin { to { transform: rotate(360deg); } }
    .pwa-update-banner {
        position: fixed !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        width: calc(100vw - 32px) !important;
        max-width: 420px !important;
        background: linear-gradient(160deg, rgba(22,10,14,0.98) 0%, rgba(12,6,9,0.99) 100%) !important;
        border-radius: 24px !important;
        padding: 0 !important;
        box-shadow: 0 30px 80px rgba(0,0,0,0.95), 0 0 0 1px rgba(255,255,255,0.07), 0 0 60px rgba(232,192,100,0.08) !important;
        z-index: 100005 !important;
        display: none;
        flex-direction: column !important;
        animation: pwaSlideUpEnterprise 0.38s cubic-bezier(0.16,1,0.3,1) !important;
        overflow: hidden !important;
        max-height: calc(100dvh - 48px) !important;
        box-sizing: border-box !important;
    }
    .pwa-update-banner.show {
        display: flex !important;
    }
    .pwa-update-accent-bar {
        height: 3px; width: 100%;
        background: linear-gradient(90deg, transparent 0%, rgba(232,192,100,0.4) 20%, #e8c064 40%, #fff8e1 50%, #e8c064 60%, rgba(232,192,100,0.4) 80%, transparent 100%);
        background-size: 200% auto;
        animation: pwaShimmer 2.5s linear infinite;
        flex-shrink: 0;
    }
    .pwa-update-inner {
        padding: 22px 22px 20px;
        display: flex; flex-direction: column; gap: 18px;
    }
    .pwa-update-glow {
        position: absolute; top: -80px; right: -80px;
        width: 200px; height: 200px;
        background: radial-gradient(circle, rgba(232,192,100,0.14) 0%, transparent 70%);
        pointer-events: none; z-index: 0; border-radius: 50%;
    }
    .pwa-update-banner-header {
        position: relative; z-index: 1;
        display: flex; align-items: center; gap: 14px;
    }
    .pwa-update-icon-container {
        position: relative; flex-shrink: 0; width: 58px; height: 58px;
    }
    .pwa-update-icon-glow-ring {
        position: absolute; inset: -5px; border-radius: 18px;
        background: conic-gradient(rgba(232,192,100,0.6) 0deg, rgba(232,192,100,0) 120deg, rgba(207,164,111,0.5) 240deg, rgba(232,192,100,0.6) 360deg);
        animation: pwaGlowRotate 3s linear infinite; opacity: 0.6; z-index: 0;
    }
    .pwa-update-icon-glow-ring::before {
        content: ''; position: absolute; inset: 2px;
        background: rgba(14,6,9,0.99); border-radius: 15px;
    }
    .pwa-update-app-icon {
        position: relative; z-index: 1;
        width: 58px; height: 58px; border-radius: 14px;
        border: 1.5px solid rgba(232,192,100,0.4);
        box-shadow: 0 6px 20px rgba(0,0,0,0.5);
        background: #18080c; display: block; object-fit: cover;
        animation: pwaIconFloat 3.5s ease-in-out infinite;
    }
    .pwa-update-pulse-indicator {
        position: absolute; top: -2px; right: -2px;
        width: 13px; height: 13px; background: #22C55E;
        border: 2.5px solid #0e0609; border-radius: 50%;
        box-shadow: 0 0 10px #22C55E;
        animation: pwaPulseDot 2s infinite ease-in-out; z-index: 2;
    }
    .pwa-update-text-area { flex: 1; min-width: 0; padding-right: 36px; }
    .pwa-update-meta { display: flex; align-items: center; gap: 7px; margin-bottom: 4px; }
    .pwa-update-tag {
        font-size: 0.6rem; font-weight: 900; letter-spacing: 1px; text-transform: uppercase;
        color: #22C55E; background: rgba(34,197,94,0.12);
        border: 1px solid rgba(34,197,94,0.3); padding: 2px 8px; border-radius: 6px;
    }
    .pwa-update-version-badge {
        font-size: 0.7rem; font-weight: 700; color: rgba(232,192,100,0.85);
        background: rgba(232,192,100,0.1); border: 1px solid rgba(232,192,100,0.22);
        padding: 1px 7px; border-radius: 5px; font-variant-numeric: tabular-nums;
    }
    .pwa-update-title {
        font-family: 'Outfit','Inter',sans-serif;
        font-size: 1.08rem; font-weight: 800; color: #fff;
        line-height: 1.2; letter-spacing: -0.3px; margin-bottom: 3px;
    }
    .pwa-update-subtitle { font-size: 0.8rem; color: rgba(255,255,255,0.5); line-height: 1.5; }
    .pwa-update-close-btn {
        position: absolute; top: 0; right: 0;
        background: rgba(255,255,255,0.07) !important;
        border: 1px solid rgba(255,255,255,0.13) !important;
        color: rgba(255,255,255,0.6) !important; font-size: 1.1rem !important;
        cursor: pointer !important; width: 30px !important; height: 30px !important;
        border-radius: 50% !important; display: flex !important;
        align-items: center !important; justify-content: center !important;
        line-height: 1 !important; padding: 0 !important;
        transition: all 0.2s ease !important; z-index: 2 !important; outline: none !important;
    }
    .pwa-update-close-btn:hover {
        background: rgba(255,255,255,0.18) !important; color: #fff !important;
        transform: rotate(90deg) !important;
    }
    .pwa-update-banner-actions {
        position: relative; z-index: 1;
        display: flex !important; align-items: center !important;
        gap: 10px !important; width: 100% !important;
    }
    .pwa-btn-update-later {
        background: rgba(255,255,255,0.06) !important; border: 1px solid rgba(255,255,255,0.13) !important;
        color: rgba(255,255,255,0.7) !important; font-weight: 600 !important;
        font-size: 0.85rem !important; font-family: inherit !important;
        border-radius: 12px !important; padding: 12px 16px !important;
        cursor: pointer !important; transition: all 0.2s ease !important;
        touch-action: manipulation !important; white-space: nowrap !important;
        outline: none !important; flex-shrink: 0 !important;
    }
    .pwa-btn-update-later:hover {
        background: rgba(255,255,255,0.13) !important; color: #fff !important;
        border-color: rgba(255,255,255,0.28) !important;
    }
    .pwa-btn-update-apply {
        flex: 1 !important;
        background: linear-gradient(135deg, #f0cc6e 0%, #d4a84b 100%) !important;
        color: #1a0a0a !important; font-weight: 800 !important;
        font-size: 0.9rem !important; font-family: inherit !important;
        border: none !important; border-radius: 12px !important;
        padding: 13px 20px !important; cursor: pointer !important;
        display: flex !important; align-items: center !important;
        justify-content: center !important; gap: 8px !important;
        box-shadow: 0 4px 20px rgba(232,192,100,0.4), inset 0 1px 0 rgba(255,255,255,0.3) !important;
        transition: all 0.25s cubic-bezier(0.16,1,0.3,1) !important;
        touch-action: manipulation !important; outline: none !important;
        letter-spacing: 0.2px !important;
    }
    .pwa-btn-update-apply:hover {
        filter: brightness(1.1) !important; transform: translateY(-2px) !important;
        box-shadow: 0 8px 28px rgba(232,192,100,0.55) !important;
    }
    .pwa-btn-update-apply:active { transform: scale(0.97) !important; }
    .pwa-btn-arrow-icon { transition: transform 0.2s ease; }
    .pwa-btn-update-apply:hover .pwa-btn-arrow-icon { transform: translateX(3px); }
    .pwa-update-pill {
        position: fixed !important; top: max(16px, env(safe-area-inset-top, 16px)) !important;
        left: 50% !important; transform: translateX(-50%) translateY(-70px) !important;
        background: rgba(18,10,12,0.97) !important; border: 1px solid rgba(232,192,100,0.45) !important;
        border-radius: 99px !important; padding: 8px 16px !important; display: none;
        align-items: center !important; gap: 10px !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.8), 0 0 20px rgba(232,192,100,0.25) !important;
        backdrop-filter: blur(20px) !important; -webkit-backdrop-filter: blur(20px) !important;
        z-index: 99999 !important; color: #F3E7CD !important;
        font-size: 0.82rem !important; font-weight: 700 !important; cursor: pointer !important;
        transition: transform 0.35s cubic-bezier(0.16,1,0.3,1), opacity 0.25s ease !important;
        opacity: 0; pointer-events: none; user-select: none; white-space: nowrap;
    }
    .pwa-update-pill.show {
        display: flex !important; transform: translateX(-50%) translateY(0) !important;
        opacity: 1 !important; pointer-events: auto !important;
    }
    .pwa-pill-dot {
        width: 8px; height: 8px; background: #22C55E; border-radius: 50%;
        box-shadow: 0 0 10px #22C55E; animation: pwaPulseDot 2s infinite ease-in-out; flex-shrink: 0;
    }
    .pwa-pill-text strong { color: #E8C064; }
    .pwa-pill-btn {
        background: linear-gradient(135deg, #E8C064 0%, #CFA46F 100%); color: #110A0A;
        font-size: 0.72rem; font-weight: 800; padding: 3px 10px;
        border-radius: 99px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    @keyframes pwaFadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<!-- Floating PWA Install Banner -->
<div class="pwa-install-banner" id="pwaInstallBanner" style="display: none;">
    <div class="pwa-banner-icon">
        <img src="/images/icons/icon-72x72.png" alt="Smart Attendance">
    </div>
    <div class="pwa-banner-content">
        <div class="pwa-banner-title">Install Smart Attendance</div>
        <div class="pwa-banner-subtitle">Fast attendance clock-in, offline mode &amp; alerts</div>
    </div>
    <div class="pwa-banner-actions">
        <button type="button" class="pwa-btn-install pwa-install-trigger" id="pwaBannerInstallBtn">Install</button>
        <a href="/download/apk" id="pwaBannerApkBtn" download="SmartAttendance.apk"
           style="display:none; background:linear-gradient(135deg,#22C55E 0%,#16A34A 100%); color:#fff; border:none; border-radius:10px; padding:9px 14px; font-size:0.82rem; font-weight:700; cursor:pointer; text-decoration:none; align-items:center; gap:5px; white-space:nowrap;">
            ⬇ APK
        </a>
        <button type="button" class="pwa-btn-close" id="pwaBannerCloseBtn" aria-label="Dismiss">&times;</button>
    </div>
</div>

<!-- Universal PWA Install & Download Modal (Android, iOS & Desktop) -->
<div class="pwa-ios-modal" id="pwaIosModal" role="dialog" aria-modal="true" aria-labelledby="pwaModalTitle">
    <div class="pwa-ios-sheet">
        <div class="pwa-sheet-handle"></div>
        <div class="pwa-sheet-top-row">
            <div class="pwa-sheet-title-wrap">
                <span class="pwa-sheet-title-icon"><i class="bi bi-phone"></i></span>
                <h3 id="pwaModalTitle" class="pwa-sheet-title">Get Mobile App</h3>
            </div>
            <button type="button" id="pwaModalCloseIcon" class="pwa-sheet-close-btn" aria-label="Close modal">&times;</button>
        </div>

        <!-- 1. Main App Hero Card -->
        <div class="pwa-hero-card" id="pwaHeroCard">
            <div class="pwa-hero-header">
                <img src="/images/icons/icon-192x192.png" alt="Smart Attendance" class="pwa-hero-icon" id="pwaHeroIcon">
                <div class="pwa-hero-info">
                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                        <span class="pwa-hero-badge" id="pwaHeroBadge">Official Android App</span>
                        <span class="pwa-hero-verified" id="pwaHeroVerified"><i class="bi bi-patch-check-fill"></i> Verified</span>
                    </div>
                    <h4 class="pwa-hero-title" id="pwaHeroTitle">Smart Classroom Attendance</h4>
                    <div class="pwa-hero-meta" id="pwaHeroMeta">
                        <span id="pwaHeroVersion">v{{ $latestVersion }}</span>
                        <span class="pwa-meta-dot">•</span>
                        <span id="pwaHeroSize">~4 MB</span>
                        <span class="pwa-meta-dot">•</span>
                        <span id="pwaHeroPlatform">Android</span>
                    </div>
                </div>
            </div>

            <p class="pwa-hero-tagline" id="pwaHeroTagline">Install for faster clock-in, biometric access &amp; instant alerts.</p>

            <!-- Main CTA Download / Action Button -->
            <div class="pwa-cta-container">
                <button type="button" id="pwaModalDownloadApkBtn" class="pwa-hero-dl-btn" data-apk-url="{{ route('pwa.download.apk') }}" data-action="download">
                    <span class="pwa-dl-btn-content" id="pwaDlBtnContent" style="display:flex; align-items:center; justify-content:center; gap:8px;">
                        <i class="bi bi-arrow-down-circle-fill pwa-dl-icon" id="pwaDlIcon"></i>
                        <span class="pwa-dl-label" id="pwaDlLabel">Download APK</span>
                    </span>
                    <span class="pwa-dl-progress-bar" id="pwaDlProgressBar"></span>
                </button>

                <div id="pwaDlStatusAlert" class="pwa-dl-status-banner">
                    <i class="bi bi-check-circle-fill" style="font-size:1.05rem; flex-shrink:0;"></i>
                    <div>
                        <div style="font-weight:700;">Download Started!</div>
                        <div style="font-size:0.72rem; opacity:0.9;">Open your Downloads folder and tap the APK to install.</div>
                    </div>
                </div>
            </div>

            <div class="pwa-hero-trust" id="pwaHeroTrust">
                <i class="bi bi-shield-check text-success"></i>
                <span id="pwaHeroTrustText">Official Smart Classroom Attendance App</span>
            </div>
        </div>

        <!-- 2. Simple 3-Step Visual Process -->
        <div class="pwa-mini-steps-section" id="pwaMiniStepsSection">
            <div class="pwa-mini-steps-title" id="pwaStepsHeaderTitle">HOW TO INSTALL</div>
            <div class="pwa-mini-steps-grid" id="pwaModalSteps">
                <div class="pwa-mini-step-box">
                    <div class="pwa-mini-step-num">1</div>
                    <div class="pwa-mini-step-label">DOWNLOAD</div>
                    <div class="pwa-mini-step-hint">Tap Download APK</div>
                </div>
                <div class="pwa-mini-step-box">
                    <div class="pwa-mini-step-num">2</div>
                    <div class="pwa-mini-step-label">INSTALL</div>
                    <div class="pwa-mini-step-hint">Open file &amp; tap Install</div>
                </div>
                <div class="pwa-mini-step-box">
                    <div class="pwa-mini-step-num">3</div>
                    <div class="pwa-mini-step-label">OPEN</div>
                    <div class="pwa-mini-step-hint">Launch from Drawer</div>
                </div>
            </div>
        </div>

        <!-- 3. Collapsible Troubleshooting Section -->
        <details class="pwa-help-details" id="pwaHelpDetails">
            <summary class="pwa-help-summary">
                <span style="display:flex; align-items:center; gap:6px;">
                    <i class="bi bi-question-circle"></i>
                    <span>Having trouble installing?</span>
                </span>
                <i class="bi bi-chevron-down pwa-help-chevron"></i>
            </summary>
            <div class="pwa-help-content" id="pwaHelpContent">
                <div class="pwa-help-item">
                    <span class="pwa-help-bullet"></span>
                    <div><strong>Samsung / Pixel:</strong> Open the downloaded APK and tap Install. The icon appears in your App Drawer (swipe up). Long-press to add to Home.</div>
                </div>
                <div class="pwa-help-item">
                    <span class="pwa-help-bullet"></span>
                    <div><strong>Xiaomi / Redmi / Poco:</strong> If home screen shortcuts are blocked: Settings &gt; Apps &gt; Permissions &gt; enable "Home screen shortcuts".</div>
                </div>
                <div class="pwa-help-item">
                    <span class="pwa-help-bullet"></span>
                    <div><strong>Browser Warning:</strong> Tap "Details" or "Download anyway" if your browser displays a standard prompt for direct APK files.</div>
                </div>
                <div class="pwa-help-item">
                    <span class="pwa-help-bullet"></span>
                    <div><strong>Can't Find Download:</strong> Open your phone's <strong>Files</strong> or <strong>Downloads</strong> app and tap <code>SmartAttendance.apk</code>.</div>
                </div>
            </div>
        </details>

        <!-- 4. Secondary Action Buttons -->
        <div class="pwa-sheet-bottom-actions">
            <button type="button" class="pwa-sheet-btn-secondary" id="pwaIosCloseBtn">Got It</button>
            <button type="button" class="pwa-sheet-btn-check" id="pwaResetStateBtn">
                <i class="bi bi-arrow-repeat me-1"></i> Check Again
            </button>
        </div>
    </div>
</div>

<!-- Automatic "System Updated ✓" Success Toast -->
<div class="pwa-toast-system-updated" id="pwaSystemUpdatedToast" style="display: none;" role="status" aria-live="polite">
    <div class="pwa-toast-icon-wrap">
        <svg class="pwa-toast-check-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
    </div>
    <div class="pwa-toast-content">
        <div class="pwa-toast-title" id="pwaSystemUpdatedTitle">System Updated <span class="pwa-toast-check">&#x2713;</span></div>
        <div class="pwa-toast-desc" id="pwaSystemUpdatedDesc">You are now using the latest version.</div>
    </div>
    <button type="button" class="pwa-toast-close" id="pwaDismissUpdatedToastBtn" aria-label="Dismiss">&times;</button>
</div>

<!-- System Update Notification -->
<div class="pwa-update-backdrop" id="pwaUpdateBackdrop" style="display: none;"></div>
<div class="pwa-update-banner" id="pwaSystemUpdatePopup" style="display: none;">
    <div class="pwa-update-accent-bar"></div>
    <div class="pwa-update-glow"></div>
    <div class="pwa-update-inner">
        <div class="pwa-update-banner-header">
            <div class="pwa-update-icon-container">
                <div class="pwa-update-icon-glow-ring"></div>
                <img src="/images/icons/icon-72x72.png" class="pwa-update-app-icon" alt="Smart Attendance">
                <span class="pwa-update-pulse-indicator" title="New build ready"></span>
            </div>
            <div class="pwa-update-text-area">
                <div class="pwa-update-meta">
                    <span class="pwa-update-tag">&#10003; Update Ready</span>
                    <span class="pwa-update-version-badge" id="pwaUpdateVersionBadge">{{ $initialChangelog['version_display'] ?? ('v' . $latestVersion) }}</span>
                </div>
                <div class="pwa-update-title" id="pwaUpdateTitle">New Version Available</div>
                <div class="pwa-update-subtitle" id="pwaUpdateSubtitle">Improvements and fixes are ready to install.</div>
                <span style="display:none;" aria-hidden="true">Update Ready Refresh Now</span>
            </div>
            <button type="button" class="pwa-update-close-btn" id="pwaDismissUpdatePopupBtn" aria-label="Dismiss">&times;</button>
        </div>
        <div class="pwa-update-banner-actions">
            <button type="button" class="pwa-btn-update-later" id="pwaLaterUpdateBtn">Later</button>
            <button type="button" class="pwa-btn-update-apply" id="pwaApplyUpdateBtn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="pwa-update-spin-icon" style="display:none; animation: ptr-spin 0.8s linear infinite;"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                <span id="pwaApplyUpdateBtnText">Update Now</span>
                <svg class="pwa-btn-arrow-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </button>
        </div>
    </div>
</div>

<!-- Floating Subtle Fallback Pill (Appears when update is available if modal is snoozed/dismissed) -->
<div class="pwa-update-pill" id="pwaUpdatePill" style="display: none;" role="button" aria-label="Update Available">
    <span class="pwa-pill-dot"></span>
    <span class="pwa-pill-text">Update Available <strong id="pwaPillVersionBadge">{{ $initialChangelog['version_display'] ?? ('v' . $latestVersion) }}</strong></span>
    <span class="pwa-pill-btn">Update</span>
</div>

<!-- Real-time Connectivity Toast -->
<div class="pwa-network-toast" id="pwaNetworkToast"></div>

<script @cspNonce src="{{ asset('js/password-toggle.js') }}?v={{ file_exists(public_path('js/password-toggle.js')) ? filemtime(public_path('js/password-toggle.js')) : time() }}"></script>
<script @cspNonce>
    // ── Prevent Pull-to-Refresh & Overscroll System Reload ──
    // When scrolling up or down, preserve user's position and page state.
    // Prevent mobile browsers (Chrome / Safari / WebView) from triggering
    // a page refresh or reload when reaching the top boundary.
    (function() {
        if (typeof window === 'undefined' || !window.document) return;
        
        let startY = 0;
        let isTouching = false;

        window.addEventListener('touchstart', function(e) {
            if (e.touches && e.touches.length === 1) {
                startY = e.touches[0].clientY;
                isTouching = true;
            }
        }, { passive: true });

        window.addEventListener('touchmove', function(e) {
            if (!isTouching || !e.touches || e.touches.length !== 1) return;
            const currentY = e.touches[0].clientY;
            const deltaY = currentY - startY;

            // When user pulls DOWN (deltaY > 0) while at the top of the page (scrollTop <= 0)
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            if (scrollTop <= 0 && deltaY > 0) {
                // Check if an ancestor scrollable element has scroll room upwards
                let target = e.target;
                let hasScrollableParent = false;
                while (target && target !== document.body && target !== document.documentElement) {
                    if (target.scrollHeight > target.clientHeight) {
                        const overflow = window.getComputedStyle(target).overflowY;
                        if ((overflow === 'auto' || overflow === 'scroll') && target.scrollTop > 0) {
                            hasScrollableParent = true;
                            break;
                        }
                    }
                    target = target.parentElement;
                }
                if (!hasScrollableParent && e.cancelable) {
                    e.preventDefault();
                }
            }
        }, { passive: false });

        window.addEventListener('touchend', function() {
            isTouching = false;
        }, { passive: true });

        window.addEventListener('touchcancel', function() {
            isTouching = false;
        }, { passive: true });
    })();

    // ── Universal Multi-Layer Persistent Device Key & Hardware Fingerprinting ──
    (function() {
        // Fast deterministic FNV-1a 32-bit hashing
        function fnv1a(str) {
            var hash = 2166136261;
            for (var i = 0; i < str.length; i++) {
                hash ^= str.charCodeAt(i);
                hash = Math.imul(hash, 16777619);
            }
            return (hash >>> 0).toString(16);
        }

        // Generate deterministic client hardware environment fingerprint
        window.getDeviceFingerprint = function() {
            try {
                var components = [
                    (window.screen ? window.screen.width + 'x' + window.screen.height + 'x' + window.screen.colorDepth : ''),
                    (window.devicePixelRatio || 1),
                    (navigator.hardwareConcurrency || 4),
                    (navigator.maxTouchPoints || 0),
                    (navigator.platform || ''),
                    (Intl && Intl.DateTimeFormat ? Intl.DateTimeFormat().resolvedOptions().timeZone : '')
                ];

                // Add lightweight canvas fingerprinting
                try {
                    var canvas = document.createElement('canvas');
                    canvas.width = 120;
                    canvas.height = 30;
                    var ctx = canvas.getContext('2d');
                    if (ctx) {
                        ctx.textBaseline = 'top';
                        ctx.font = '14px Arial';
                        ctx.fillStyle = '#f60';
                        ctx.fillRect(10, 1, 62, 20);
                        ctx.fillStyle = '#069';
                        ctx.fillText('SmartAtt_Fp', 2, 15);
                        ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
                        ctx.fillText('SmartAtt_Fp', 4, 17);
                        components.push(canvas.toDataURL());
                    }
                } catch(ce) {}

                var raw = components.join('###');
                return fnv1a(raw) + fnv1a(raw.split('').reverse().join(''));
            } catch(e) {
                return 'fp_fallback';
            }
        };

        // Determine user-friendly hardware device model
        window.getDeviceModel = function() {
            try {
                if (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.getNativeDeviceModel === 'function') {
                    return window.AndroidDeviceBridge.getNativeDeviceModel();
                }
                var ua = navigator.userAgent || '';
                if (/iPhone/i.test(ua)) return 'Apple iPhone';
                if (/iPad/i.test(ua)) return 'Apple iPad';
                if (/Android/i.test(ua)) {
                    var m = ua.match(/Android[^;]+; ([^)]+)\)/);
                    if (m && m[1]) return m[1].replace(/Build\/.+/, '').trim();
                    return 'Android Mobile';
                }
                if (/Macintosh/i.test(ua)) return 'Mac OS Computer';
                if (/Windows/i.test(ua)) return 'Windows PC';
                return 'Web Browser Device';
            } catch(e) {
                return 'Personal Device';
            }
        };

        // IndexedDB resilient storage layer
        function saveToIndexedDb(key) {
            if (!window.indexedDB) return;
            try {
                var req = indexedDB.open('smart_attendance_device_db', 1);
                req.onupgradeneeded = function(e) {
                    var db = e.target.result;
                    if (!db.objectStoreNames.contains('device_store')) {
                        db.createObjectStore('device_store');
                    }
                };
                req.onsuccess = function(e) {
                    try {
                        var db = e.target.result;
                        var tx = db.transaction('device_store', 'readwrite');
                        tx.objectStore('device_store').put(key, 'student_device_key');
                    } catch(err) {}
                };
            } catch(e) {}
        }

        function restoreFromIndexedDb() {
            if (!window.indexedDB) return;
            try {
                var req = indexedDB.open('smart_attendance_device_db', 1);
                req.onsuccess = function(e) {
                    try {
                        var db = e.target.result;
                        if (!db.objectStoreNames.contains('device_store')) return;
                        var tx = db.transaction('device_store', 'readonly');
                        var getReq = tx.objectStore('device_store').get('student_device_key');
                        getReq.onsuccess = function() {
                            var val = getReq.result;
                            if (val && typeof val === 'string' && val.length > 8) {
                                if (!localStorage.getItem('student_device_key')) {
                                    localStorage.setItem('student_device_key', val);
                                    localStorage.setItem('attendance_device_uuid', val);
                                    var sec = location.protocol === 'https:' ? '; Secure' : '';
                                    document.cookie = 'student_device_key=' + encodeURIComponent(val) + '; path=/; max-age=31536000; SameSite=Lax' + sec;
                                }
                            }
                        };
                    } catch(err) {}
                };
            } catch(e) {}
        }
        try { restoreFromIndexedDb(); } catch(e) {}

        // Multi-tier device key accessor & healing engine
        window.getOrCreateDeviceKey = function() {
            try {
                var key = null;

                // Tier 1: Native Android app bridge (100% persistent across app installs)
                if (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.getNativeDeviceId === 'function') {
                    key = window.AndroidDeviceBridge.getNativeDeviceId();
                }

                // Tier 2: localStorage
                if (!key) {
                    key = localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid');
                }

                // Tier 3: Cookies
                if (!key) {
                    var m = document.cookie.match(/(?:^|;\s*)student_device_key=([^;]+)/);
                    if (m && m[1]) key = decodeURIComponent(m[1]);
                }

                // Tier 4: sessionStorage
                if (!key) {
                    key = sessionStorage.getItem('student_device_key') || sessionStorage.getItem('attendance_device_uuid');
                }

                // Tier 5: Fresh generation if uninitialized
                if (!key || key === 'undefined' || key === 'null' || key.trim() === '') {
                    key = (typeof crypto !== 'undefined' && crypto.randomUUID)
                        ? crypto.randomUUID()
                        : ('dev_' + Date.now().toString(36) + '_' + Math.random().toString(36).substring(2, 14));
                }

                // Synchronize across all persistence layers
                try {
                    localStorage.setItem('student_device_key', key);
                    localStorage.setItem('attendance_device_uuid', key);
                    sessionStorage.setItem('student_device_key', key);
                    sessionStorage.setItem('attendance_device_uuid', key);
                    var sec = location.protocol === 'https:' ? '; Secure' : '';
                    document.cookie = 'student_device_key=' + encodeURIComponent(key) + '; path=/; max-age=31536000; SameSite=Lax' + sec;
                    saveToIndexedDb(key);
                } catch(syncErr) {}

                return key;
            } catch(e) {
                return '';
            }
        };

        try { window.getOrCreateDeviceKey(); } catch(e) {}

        // Attach device headers to fetch requests
        if (typeof window.fetch === 'function' && !window.__deviceFetchIntercepted) {
            window.__deviceFetchIntercepted = true;
            var _origFetch = window.fetch;
            window.fetch = function(input, init) {
                try {
                    init = init || {};
                    var devKey = window.getOrCreateDeviceKey ? window.getOrCreateDeviceKey() : '';
                    var devFp  = window.getDeviceFingerprint ? window.getDeviceFingerprint() : '';
                    var devMod = window.getDeviceModel ? window.getDeviceModel() : '';

                    if (devKey) {
                        if (!init.headers) init.headers = {};
                        if (init.headers instanceof Headers) {
                            if (!init.headers.has('X-Device-Key')) init.headers.set('X-Device-Key', devKey);
                            if (!init.headers.has('X-Device-Fingerprint')) init.headers.set('X-Device-Fingerprint', devFp || devKey);
                            if (!init.headers.has('X-Device-Model') && devMod) init.headers.set('X-Device-Model', devMod);
                        } else if (Array.isArray(init.headers)) {
                            init.headers.push(['X-Device-Key', devKey]);
                            init.headers.push(['X-Device-Fingerprint', devFp || devKey]);
                            if (devMod) init.headers.push(['X-Device-Model', devMod]);
                        } else {
                            if (!init.headers['X-Device-Key']) init.headers['X-Device-Key'] = devKey;
                            if (!init.headers['X-Device-Fingerprint']) init.headers['X-Device-Fingerprint'] = devFp || devKey;
                            if (!init.headers['X-Device-Model'] && devMod) init.headers['X-Device-Model'] = devMod;
                        }
                    }
                    if (!init.credentials) {
                        init.credentials = 'same-origin';
                    }
                } catch(err) {}
                return _origFetch.call(this, input, init);
            };
        }

        // Attach device headers to same-origin XMLHttpRequest
        if (typeof window.XMLHttpRequest !== 'undefined' && !window.__deviceXHRIntercepted) {
            window.__deviceXHRIntercepted = true;
            var _origOpen = XMLHttpRequest.prototype.open;
            var _origSend = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.open = function() {
                var url = arguments[1] || '';
                this.__isSameOrigin = (
                    typeof url === 'string' && (
                        url.startsWith('/') ||
                        url.startsWith(window.location.origin) ||
                        !url.startsWith('http')
                    )
                );
                return _origOpen.apply(this, arguments);
            };
            XMLHttpRequest.prototype.send = function() {
                try {
                    if (this.__isSameOrigin) {
                        var devKey = window.getOrCreateDeviceKey ? window.getOrCreateDeviceKey() : '';
                        var devFp  = window.getDeviceFingerprint ? window.getDeviceFingerprint() : '';
                        var devMod = window.getDeviceModel ? window.getDeviceModel() : '';
                        if (devKey) {
                            this.setRequestHeader('X-Device-Key', devKey);
                            this.setRequestHeader('X-Device-Fingerprint', devFp || devKey);
                            if (devMod) this.setRequestHeader('X-Device-Model', devMod);
                        }
                    }
                } catch(e) {}
                return _origSend.apply(this, arguments);
            };
        }

        // Automatically inject hidden device binding fields into HTML forms on submit
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (form && form.tagName === 'FORM' && form.method && form.method.toUpperCase() === 'POST') {
                try {
                    var devKey = window.getOrCreateDeviceKey ? window.getOrCreateDeviceKey() : '';
                    var devFp  = window.getDeviceFingerprint ? window.getDeviceFingerprint() : '';
                    var devMod = window.getDeviceModel ? window.getDeviceModel() : '';

                    if (devKey && !form.querySelector('input[name="device_key"]')) {
                        var inp1 = document.createElement('input');
                        inp1.type = 'hidden';
                        inp1.name = 'device_key';
                        inp1.value = devKey;
                        form.appendChild(inp1);
                    }
                    if (devFp && !form.querySelector('input[name="device_fingerprint"]')) {
                        var inp2 = document.createElement('input');
                        inp2.type = 'hidden';
                        inp2.name = 'device_fingerprint';
                        inp2.value = devFp;
                        form.appendChild(inp2);
                    }
                    if (devMod && !form.querySelector('input[name="device_model"]')) {
                        var inp3 = document.createElement('input');
                        inp3.type = 'hidden';
                        inp3.name = 'device_model';
                        inp3.value = devMod;
                        form.appendChild(inp3);
                    }
                } catch(formErr) {}
            }
        }, true);
    })();

    // ── 1. Register Service Worker & Handle Real-Time Update Notifications ──
    let swRegistration = null;
    let deferredPrompt = null;
    let latestDetectedVersion = null;
    let latestDetectedSwVersion = null;
    let currentChangelogData = null;
    let lastNotifiedVersion = null;
    let isCheckingVersion = false;
    let checkVersionPromise = null;
    let lastVersionCheckTime = 0;
    const VERSION_CHECK_COOLDOWN_MS = 1000;
    const DISMISS_COOLDOWN_MS = 15 * 60 * 1000; // 15 minutes snooze cooldown

    // ── Handle URL Version Query Sync & Parameter Cleanup ──
    try {
        const _url = new URL(window.location.href);
        const _vParam = _url.searchParams.get('_v');
        if (_vParam) {
            const _cleanV = String(_vParam).trim().replace(/^v/i, '');
            if (/^\d+(\.\d+)*$/.test(_cleanV)) {
                localStorage.setItem('app_installed_version', _cleanV);
                localStorage.setItem('pwa_installed_version', _cleanV);
                localStorage.setItem('pwa_app_version', _cleanV);
                localStorage.setItem('app_version', _cleanV);
                sessionStorage.setItem('pwa_updated_ver', _cleanV);
                sessionStorage.setItem('pwa_just_updated_at', String(Date.now()));
            }
            _url.searchParams.delete('_v');
            _url.searchParams.delete('_t');
            window.history.replaceState({}, document.title, _url.toString());
        }
    } catch(e) {}

    // Immutable constants capturing the document version as rendered by the server
    const DOC_INSTALLED_VER = document.querySelector('meta[name="app-installed-version"]')?.content || '{{ $installedVersion }}';
    const DOC_LATEST_VER = document.querySelector('meta[name="app-latest-version"]')?.content || '{{ $latestVersion }}';
    const DOC_BUILD_ID = document.querySelector('meta[name="app-build-id"]')?.content || '{{ $buildId }}';
    const DOC_COMMIT_HASH = document.querySelector('meta[name="app-commit-hash"]')?.content || '{{ $commitHash }}';
    const DOC_SW_VER = document.querySelector('meta[name="sw-build-version"]')?.content || '{{ $swCacheVer }}';
    const DOC_SW_MTIME = parseInt(document.querySelector('meta[name="sw-build-mtime"]')?.content || '{{ $swFileMtime }}', 10);
    let latestServerTimestamp = DOC_SW_MTIME;

    // Cross-tab synchronization via BroadcastChannel
    let pwaBroadcastChannel = null;
    try {
        if ('BroadcastChannel' in window) {
            pwaBroadcastChannel = new BroadcastChannel('pwa_update_channel');
            pwaBroadcastChannel.onmessage = function(e) {
                if (e.data && e.data.type === 'APP_UPDATED') {
                    checkServerVersion(true);
                }
            };
        }
    } catch(e) {}

    window.addEventListener('storage', function(e) {
        if (e.key === 'pwa_tab_updated_at' && e.newValue) {
            checkServerVersion(true);
        }
    });

    function updateChangelogUI(changelog) {
        if (!changelog) return;
        currentChangelogData = changelog;

        const badge = document.getElementById('pwaUpdateVersionBadge');
        if (badge) {
            badge.textContent = changelog.version_display || ('Version ' + (changelog.version || latestDetectedVersion || DOC_LATEST_VER));
        }

        const pillBadge = document.getElementById('pwaPillVersionBadge');
        if (pillBadge) {
            const clean = String(changelog.version || latestDetectedVersion || DOC_LATEST_VER).trim();
            pillBadge.textContent = clean.startsWith('v') ? clean : 'v' + clean;
        }

        const titleEl = document.getElementById('pwaUpdateTitle');
        if (titleEl) {
            titleEl.textContent = 'Update Available';
        }

        const subEl = document.getElementById('pwaUpdateSubtitle');
        if (subEl) {
            const verDisplay = changelog.version || latestDetectedVersion || DOC_LATEST_VER;
            subEl.textContent = changelog.description || ('A new version (Version ' + verDisplay + ') is available to install.');
        }
    }

    // ── 1.1 Semantic & Continuous Version Comparison (Latest Version > Installed Version) ──
    function parseSemver(v) {
        if (!v && v !== 0) return [0];
        let cleaned = String(v).trim().replace(/^Version\s*/i, '').replace(/^[vV]/, '');
        if (cleaned.includes('_')) {
            cleaned = cleaned.split('_')[0];
        }
        const parts = cleaned.split('.').map(function(p) {
            const num = parseInt(p.replace(/[^\d]/g, ''), 10);
            return isNaN(num) ? 0 : num;
        });
        return parts.length ? parts : [0];
    }

    function compareSemver(v1, v2) {
        const p1 = parseSemver(v1);
        const p2 = parseSemver(v2);
        const maxLen = Math.max(p1.length, p2.length);
        for (let i = 0; i < maxLen; i++) {
            const num1 = p1[i] !== undefined ? p1[i] : 0;
            const num2 = p2[i] !== undefined ? p2[i] : 0;
            if (num1 > num2) return 1;
            if (num1 < num2) return -1;
        }
        return 0;
    }

    function parseSwNum(v) {
        if (!v) return 0;
        const num = parseInt(String(v).replace(/[^\d]/g, ''), 10);
        return isNaN(num) ? 0 : num;
    }

    function getInstalledVersion() {
        const metaInstalled = document.querySelector('meta[name="app-installed-version"]')?.content || DOC_INSTALLED_VER;
        const stored = localStorage.getItem('app_installed_version') || localStorage.getItem('pwa_installed_version') || localStorage.getItem('pwa_app_version');

        if (metaInstalled) {
            if (!stored || compareSemver(metaInstalled, stored) >= 0) {
                try {
                    localStorage.setItem('app_installed_version', metaInstalled);
                    localStorage.setItem('pwa_installed_version', metaInstalled);
                    localStorage.setItem('pwa_app_version', metaInstalled);
                } catch(e) {}
                return metaInstalled;
            }
            return stored;
        }

        if (stored && !stored.includes('_') && /^\d/.test(stored)) {
            return stored;
        }

        return '1.0.0';
    }

    function getInstalledSwVersion() {
        const metaSw = document.querySelector('meta[name="sw-build-version"]')?.content || DOC_SW_VER || '';
        const storedSw = localStorage.getItem('pwa_installed_sw_version');
        if (metaSw && storedSw) {
            if (parseSwNum(metaSw) >= parseSwNum(storedSw)) {
                localStorage.setItem('pwa_installed_sw_version', metaSw);
                return metaSw;
            }
            return storedSw;
        }
        return metaSw || storedSw || '';
    }

    function getLatestVersion(serverData = null) {
        if (serverData && serverData.latest_version) {
            return serverData.latest_version;
        }
        if (serverData && serverData.changelog && serverData.changelog.version) {
            return serverData.changelog.version;
        }
        const metaLatest = document.querySelector('meta[name="app-latest-version"]')?.content;
        return metaLatest || DOC_LATEST_VER;
    }

    // ── 1.2 DOM Health: Ensure PWA Modals & Overlays live in document.body ──
    function ensurePwaModalsInBody() {
        if (!document.body) return false;
        ['pwaInstallBanner', 'pwaIosModal', 'pwaUpdateBackdrop', 'pwaSystemUpdatePopup', 'pwaUpdatePill', 'pwaSystemUpdatedToast', 'pwaNetworkToast'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        });

        // Ensure update popup buttons have direct listeners bound in addition to delegated listeners
        const applyBtn = document.getElementById('pwaApplyUpdateBtn');
        if (applyBtn && !applyBtn.__hasUpdateListener) {
            applyBtn.__hasUpdateListener = true;
            applyBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                applySystemUpdate();
            });
        }
        const laterBtn = document.getElementById('pwaLaterUpdateBtn');
        if (laterBtn && !laterBtn.__hasLaterListener) {
            laterBtn.__hasLaterListener = true;
            laterBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                hideAppUpdatePopup(latestDetectedVersion);
            });
        }
        const dismissBtn = document.getElementById('pwaDismissUpdatePopupBtn');
        if (dismissBtn && !dismissBtn.__hasDismissListener) {
            dismissBtn.__hasDismissListener = true;
            dismissBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                hideAppUpdatePopup(latestDetectedVersion);
            });
        }

        return true;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensurePwaModalsInBody);
    } else {
        ensurePwaModalsInBody();
    }

    // Initialize client stored version safely on initial visit without overwriting existing version records
    try {
        const _stored = localStorage.getItem('pwa_installed_version');
        if (!_stored) {
            localStorage.setItem('pwa_installed_version', DOC_INSTALLED_VER);
            localStorage.setItem('pwa_app_version', DOC_INSTALLED_VER);
            if (DOC_SW_VER) localStorage.setItem('pwa_installed_sw_version', DOC_SW_VER);
            if (DOC_SW_MTIME) localStorage.setItem('pwa_applied_sw_mtime', String(DOC_SW_MTIME));
        }
    } catch(e) {}

    function getAppliedSwMtime() {
        const stored = localStorage.getItem('pwa_applied_sw_mtime');
        if (stored) {
            return parseInt(stored, 10);
        }
        return DOC_SW_MTIME || 0;
    }

    function checkInstantUpdateAvailable() {
        const installedVer = getInstalledVersion();
        const latestVer = getLatestVersion();

        // If installed version is already equal to or greater than latest, no update needed
        if (compareSemver(installedVer, latestVer) >= 0) {
            return false;
        }

        // 1. Semantic Version update (e.g. 2.4.1 > 2.4.0)
        if (compareSemver(latestVer, installedVer) > 0) {
            return true;
        }

        // 2. Direct document meta comparison
        const metaInstalled = document.querySelector('meta[name="app-installed-version"]')?.content || DOC_INSTALLED_VER;
        const metaLatest = document.querySelector('meta[name="app-latest-version"]')?.content || DOC_LATEST_VER;
        if (metaLatest && metaInstalled && compareSemver(metaLatest, metaInstalled) > 0) {
            return true;
        }

        return false;
    }

    // ── Fallback Pill Helpers (Shows subtle pill when modal is dismissed or snoozed) ──
    function showUpdateFallbackPill(version = null) {
        // Do not show pill if already up to date
        const installedVer = getInstalledVersion();
        const targetVer = version || latestDetectedVersion || getLatestVersion();
        if (compareSemver(targetVer, installedVer) <= 0) {
            hideUpdateFallbackPill();
            return;
        }
        ensurePwaModalsInBody();
        const pill = document.getElementById('pwaUpdatePill');
        if (!pill) return;
        const badge = document.getElementById('pwaPillVersionBadge');
        if (badge) {
            const clean = String(targetVer).trim();
            badge.textContent = clean.startsWith('v') ? clean : 'v' + clean;
        }
        pill.style.display = 'flex';
        void pill.offsetHeight;
        pill.classList.add('show');
    }

    function hideUpdateFallbackPill() {
        const pill = document.getElementById('pwaUpdatePill');
        if (!pill) return;
        pill.classList.remove('show');
        pill.style.display = 'none';
    }

    function hideModalElementsIfUpToDate() {
        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (popup) {
            popup.classList.remove('show');
            popup.style.display = 'none';
        }
        const backdrop = document.getElementById('pwaUpdateBackdrop');
        if (backdrop) {
            backdrop.classList.remove('show');
            backdrop.style.display = 'none';
        }
        hideUpdateFallbackPill();
    }

    // ── Toast Helper: "System Updated ✓" (Auto-dismissing unobtrusive notification) ──
    let systemUpdatedToastTimer = null;
    function showSystemUpdatedToast(version = null) {
        ensurePwaModalsInBody();
        const toast = document.getElementById('pwaSystemUpdatedToast');
        if (!toast) return;

        if (version) {
            const descEl = document.getElementById('pwaSystemUpdatedDesc');
            if (descEl) {
                const cleanVer = String(version).trim();
                const verText = cleanVer.startsWith('v') ? cleanVer : 'v' + cleanVer;
                descEl.textContent = 'You are now using the latest version (' + verText + ').';
            }
        }

        toast.classList.remove('pwa-toast-hide');
        toast.style.display = 'flex';

        if (systemUpdatedToastTimer) clearTimeout(systemUpdatedToastTimer);
        systemUpdatedToastTimer = setTimeout(hideSystemUpdatedToast, 4000);
    }

    function hideSystemUpdatedToast() {
        const toast = document.getElementById('pwaSystemUpdatedToast');
        if (!toast) return;
        toast.classList.add('pwa-toast-hide');
        setTimeout(() => {
            toast.style.display = 'none';
            toast.classList.remove('pwa-toast-hide');
        }, 300);
    }

    // ── Toast/Prompt Helper: "Update Available" (When a newer version exists) ──
    function showUpdateReadyPrompt(version = null, force = false, changelog = null, isManualCheck = false) {
        if (version) latestDetectedVersion = version;

        const targetVersion = version || latestDetectedVersion || getLatestVersion();
        const installedVer = getInstalledVersion();

        // If not a manual check and already up to date according to semver, NEVER show
        if (!isManualCheck && compareSemver(targetVersion, installedVer) <= 0) {
            if (swRegistration && swRegistration.waiting) {
                try { swRegistration.waiting.postMessage({ action: 'skipWaiting', type: 'SKIP_WAITING' }); } catch(e) {}
            }
            hideModalElementsIfUpToDate();
            return;
        }

        // If elements are not yet parsed into DOM, wait for DOMContentLoaded
        if (!document.body || !document.getElementById('pwaSystemUpdatePopup')) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    showUpdateReadyPrompt(targetVersion, force, changelog, isManualCheck);
                }, { once: true });
                return;
            }
        }

        ensurePwaModalsInBody();

        // Prevent duplicate popup if already visible on screen unless forced or targetVersion changed
        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (popup && (popup.classList.contains('show') || popup.style.display === 'flex') && !force && !isManualCheck) {
            return;
        }

        // Suppress prompt within 60s of an applied update reload ONLY if target matches or is older than the just-updated version
        const justUpdatedVer = sessionStorage.getItem('pwa_updated_ver');
        const justUpdatedAt = parseInt(sessionStorage.getItem('pwa_just_updated_at') || '0', 10);
        const justUpdatedRecent = (sessionStorage.getItem('pwa_just_updated') === 'true') ||
                                  (justUpdatedVer && (compareSemver(targetVersion, justUpdatedVer) <= 0) && (Date.now() - justUpdatedAt < 60000));
        if (justUpdatedRecent && !isManualCheck && !force) {
            hideModalElementsIfUpToDate();
            return;
        }

        const currentUpdateKey = (targetVersion || '') + '_' + (latestServerTimestamp || '') + '_' + (latestDetectedSwVersion || '');
        // Check dismiss state in sessionStorage (within tab) OR localStorage (across navigations)
        const dismissedVer = sessionStorage.getItem('pwa_update_dismissed_ver') || localStorage.getItem('pwa_update_dismissed_ver') || '';
        const dismissedAtSession = parseInt(sessionStorage.getItem('pwa_update_dismissed_at') || '0', 10);
        const dismissedAtLocal   = parseInt(localStorage.getItem('pwa_update_dismissed_at')   || '0', 10);
        const dismissedAt = Math.max(dismissedAtSession, dismissedAtLocal);

        // If target version is strictly newer than the dismissed version, never suppress!
        const isNewerThanDismissed = dismissedVer && compareSemver(targetVersion, dismissedVer) > 0;
        const isSnoozed = !isNewerThanDismissed && (Date.now() - dismissedAt < DISMISS_COOLDOWN_MS);

        if (!isManualCheck && !force && isSnoozed) {
            // Within snooze cooldown: keep modal and pill closed
            hideModalElementsIfUpToDate();
            return;
        }

        // Active prompt: hide pill if showing
        hideUpdateFallbackPill();

        if (changelog) {
            updateChangelogUI(changelog);
        } else if (version) {
            const verParam = '?v=' + encodeURIComponent(version);
            fetch('/pwa/version' + verParam, { cache: 'no-store' })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d && d.changelog) {
                        updateChangelogUI(d.changelog);
                    }
                })
                .catch(function() {});
        }

        const titleEl = document.getElementById('pwaUpdateTitle');
        if (titleEl) titleEl.textContent = 'Update Available';

        const badge = document.getElementById('pwaUpdateVersionBadge');
        if (badge) {
            const cleanVer = String(targetVersion).trim();
            badge.textContent = cleanVer.toLowerCase().startsWith('v') ? ('Version ' + cleanVer.substring(1)) : ('Version ' + cleanVer);
        }

        const subEl = document.getElementById('pwaUpdateSubtitle');
        if (subEl) subEl.textContent = 'A new version (Version ' + targetVersion + ') is available to install.';

        const btnText = document.getElementById('pwaApplyUpdateBtnText');
        if (btnText) btnText.textContent = 'Update Now';

        const backdrop = document.getElementById('pwaUpdateBackdrop');
        if (backdrop) {
            backdrop.classList.add('show');
            backdrop.style.setProperty('display', 'block', 'important');
        }

        if (popup) {
            popup.classList.add('show');
            popup.style.setProperty('display', 'flex', 'important');
        }

        // Multi-channel alert once per new version
        const isNewVersion = !lastNotifiedVersion || lastNotifiedVersion !== currentUpdateKey;
        if (isNewVersion) {
            lastNotifiedVersion = currentUpdateKey;
            if (window.triggerHaptic) {
                window.triggerHaptic('success');
            } else if (navigator.vibrate) {
                navigator.vibrate([100, 50, 100]);
            }
        }
    }

    // Alias for backward compatibility with existing tests and scripts
    function showAppUpdatePopup(version, force = false, changelog = null) {
        showUpdateReadyPrompt(version, force, changelog, force);
    }

    function hideAppUpdatePopup(version) {
        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (popup) {
            popup.classList.remove('show');
            popup.style.display = 'none';
        }

        const backdrop = document.getElementById('pwaUpdateBackdrop');
        if (backdrop) {
            backdrop.classList.remove('show');
            backdrop.style.display = 'none';
        }
        hideUpdateFallbackPill();

        const targetVersion = version || latestDetectedVersion || getLatestVersion();
        const currentUpdateKey = (targetVersion || '') + '_' + (latestServerTimestamp || '') + '_' + (latestDetectedSwVersion || '');
        
        // Snooze cooldown — persist in BOTH sessionStorage (fast) and localStorage (survives navigation)
        const dismissNow = String(Date.now());
        sessionStorage.setItem('pwa_update_dismissed_ver', targetVersion);
        sessionStorage.setItem('pwa_update_dismissed_tag', currentUpdateKey);
        sessionStorage.setItem('pwa_update_dismissed_at', dismissNow);
        try {
            localStorage.setItem('pwa_update_dismissed_ver', targetVersion);
            localStorage.setItem('pwa_update_dismissed_at', dismissNow);
        } catch(e) {}
    }

    async function applySystemUpdate() {
        const btnText = document.getElementById('pwaApplyUpdateBtnText');
        if (btnText) btnText.textContent = 'Updating...';
        const applyBtn = document.getElementById('pwaApplyUpdateBtn');
        if (applyBtn) {
            const spinIcon = applyBtn.querySelector('.pwa-update-spin-icon');
            if (spinIcon) spinIcon.style.display = 'inline-block';
            applyBtn.style.opacity = '0.85';
            applyBtn.style.pointerEvents = 'none';
        }

        const targetVer = latestDetectedVersion || getLatestVersion();
        const targetTs = latestServerTimestamp || DOC_SW_MTIME;
        const targetSwVer = latestDetectedSwVersion || DOC_SW_VER;

        localStorage.setItem('app_installed_version', targetVer);
        localStorage.setItem('pwa_installed_version', targetVer);
        localStorage.setItem('pwa_app_version', targetVer);
        localStorage.setItem('app_version', targetVer);
        if (targetSwVer) {
            localStorage.setItem('pwa_installed_sw_version', targetSwVer);
        }
        localStorage.setItem('pwa_applied_sw_mtime', String(targetTs));
        sessionStorage.setItem('pwa_just_updated', 'true');
        sessionStorage.setItem('pwa_just_updated_at', String(Date.now()));
        sessionStorage.setItem('pwa_updated_ver', targetVer);
        sessionStorage.setItem('pwa_updating', 'true');

        // Cross-tab broadcast
        if (pwaBroadcastChannel) {
            try { 
                pwaBroadcastChannel.postMessage({ type: 'APP_UPDATED', version: targetVer }); 
                pwaBroadcastChannel.postMessage({ type: 'VERSION_CHANGED', version: targetVer });
            } catch(e) {}
        }
        try { localStorage.setItem('pwa_tab_updated_at', String(Date.now())); } catch(e) {}

        // Tell server to update installed version with timeout fail-safe
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3500);
            await fetch('/pwa/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                },
                body: JSON.stringify({ version: targetVer }),
                signal: controller.signal
            }).catch(() => {});
            clearTimeout(timeoutId);
        } catch (e) {}

        // Synchronize all visible version badges immediately on click
        const liveVerTag = 'v' + String(targetVer).replace(/^v/i, '');
        document.querySelectorAll('[data-app-version-tag], #loginAppVersionDesktop, #loginAppVersionMobile, #currentAppReleaseBadge, #pwaCurrentVersionBadge').forEach(el => {
            el.textContent = liveVerTag;
        });

        // Hide modal and pill
        hideModalElementsIfUpToDate();

        // Signal service worker to skip waiting and clear old caches
        if (swRegistration && swRegistration.waiting) {
            try { swRegistration.waiting.postMessage({ action: 'skipWaiting', type: 'SKIP_WAITING' }); } catch(e) {}
        } else if (navigator.serviceWorker && navigator.serviceWorker.controller) {
            try { navigator.serviceWorker.controller.postMessage({ action: 'skipWaiting', type: 'SKIP_WAITING' }); } catch(e) {}
        }

        if (navigator.serviceWorker && navigator.serviceWorker.controller) {
            try { navigator.serviceWorker.controller.postMessage({ action: 'clearCache', type: 'CLEAR_CACHE' }); } catch(e) {}
        }

        // Clear browser caches and reload with cache busting query while preserving existing query params
        let reloaded = false;
        const doReload = () => {
            if (!reloaded) {
                reloaded = true;
                try {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('_v', targetVer);
                    currentUrl.searchParams.set('_t', String(Date.now()));
                    window.location.replace(currentUrl.toString());
                } catch(e) {
                    window.location.reload(true);
                }
            }
        };

        if ('caches' in window) {
            caches.keys()
                .then(keys => Promise.all(keys.map(k => caches.delete(k))))
                .then(() => setTimeout(doReload, 300))
                .catch(() => setTimeout(doReload, 200));
        } else {
            setTimeout(doReload, 200);
        }

        // Fail-safe reload if caches.delete hangs
        setTimeout(doReload, 1200);
    }

    async function checkServerVersion(force = false, isManualCheck = false) {
        const now = Date.now();
        if (!force && !isManualCheck && (now - lastVersionCheckTime < VERSION_CHECK_COOLDOWN_MS)) {
            return { upToDate: true };
        }
        if (isCheckingVersion && checkVersionPromise) {
            return checkVersionPromise;
        }
        lastVersionCheckTime = now;
        isCheckingVersion = true;

        checkVersionPromise = (async () => {
            try {
                // Trigger Service Worker update check asynchronously in background WITHOUT blocking
                if (swRegistration) {
                    try { swRegistration.update().catch(() => {}); } catch(e) {}
                }

                // Suppress prompt within 30s of an applied update reload if on the same updated version
                const justUpdatedVer = sessionStorage.getItem('pwa_updated_ver');
                const justUpdatedAt = parseInt(sessionStorage.getItem('pwa_just_updated_at') || '0', 10);
                const justUpdatedRecent = (sessionStorage.getItem('pwa_just_updated') === 'true') ||
                                          (justUpdatedVer && (now - justUpdatedAt < 30000));

                const installedVer = getInstalledVersion();
                const installedSwVer = getInstalledSwVersion();
                let latestVer = getLatestVersion();

                let isUpdateAvailable = false;
                let updateChangelog = currentChangelogData;
                let serverData = null;

                // Fetch server version with AbortController timeout (6 seconds)
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 6000);

                try {
                    const res = await fetch('/pwa/version?_t=' + now, {
                        cache: 'no-store',
                        headers: { 'Accept': 'application/json' },
                        signal: controller.signal
                    });
                    clearTimeout(timeoutId);

                    if (res.ok) {
                        serverData = await res.json();
                        if (serverData) {
                            latestVer = serverData.latest_version || getLatestVersion(serverData);
                            latestDetectedVersion = latestVer;
                            if (serverData.sw_version) {
                                latestDetectedSwVersion = serverData.sw_version;
                            }
                            if (serverData.timestamp) {
                                latestServerTimestamp = serverData.timestamp;
                            }
                            if (serverData.changelog) {
                                updateChangelog = serverData.changelog;
                                updateChangelogUI(updateChangelog);
                            }

                            // Keep all visible app version tags synchronized in real time with actual installed app version
                            const currentActiveVer = serverData.installed_version || serverData.current_version || installedVer || latestVer;
                            if (currentActiveVer) {
                                const liveVerTag = 'v' + String(currentActiveVer).replace(/^v/i, '');
                                document.querySelectorAll('[data-app-version-tag], #loginAppVersionDesktop, #loginAppVersionMobile, #currentAppReleaseBadge, #pwaCurrentVersionBadge').forEach(el => {
                                    el.textContent = liveVerTag;
                                });
                                try {
                                    const cleanActive = String(currentActiveVer).replace(/^v/i, '');
                                    localStorage.setItem('app_installed_version', cleanActive);
                                    localStorage.setItem('pwa_installed_version', cleanActive);
                                    localStorage.setItem('pwa_app_version', cleanActive);
                                } catch(e) {}
                            }

                            // Check 1: Semantic version comparison (Latest > Installed)
                            if (compareSemver(latestVer, installedVer) > 0) {
                                isUpdateAvailable = true;
                            }

                            // Check 2: Server explicitly reports not up to date AND latest > installed
                            if (serverData.is_up_to_date === false && compareSemver(latestVer, installedVer) > 0) {
                                isUpdateAvailable = true;
                            }

                            // Check 3: Server's latest_version > server's installed_version AND latest > client installed
                            if (serverData.latest_version && serverData.installed_version && compareSemver(serverData.latest_version, serverData.installed_version) > 0 && compareSemver(serverData.latest_version, installedVer) > 0) {
                                isUpdateAvailable = true;
                            }

                            // Check 4: Service worker version bumped AND latest > installed
                            if (!isUpdateAvailable && serverData.sw_version && installedSwVer && parseSwNum(serverData.sw_version) > parseSwNum(installedSwVer) && compareSemver(latestVer, installedVer) > 0) {
                                isUpdateAvailable = true;
                            }

                            // Check 5: Build changed AND latest > installed
                            if (!isUpdateAvailable && serverData.build && DOC_BUILD_ID && serverData.build !== DOC_BUILD_ID && compareSemver(latestVer, installedVer) > 0) {
                                isUpdateAvailable = true;
                            }

                            // If update is available and server has an installed_version behind client, align client
                            if (isUpdateAvailable && serverData.installed_version && compareSemver(serverData.installed_version, installedVer) < 0) {
                                localStorage.setItem('app_installed_version', serverData.installed_version);
                                localStorage.setItem('pwa_installed_version', serverData.installed_version);
                            }
                        }
                    }
                } catch (fetchErr) {
                    clearTimeout(timeoutId);
                    // Network failure or abort — fail gracefully without breaking UI
                }

                // Check 6: Service worker has a waiting update (only if latest > installed)
                if (swRegistration && swRegistration.waiting) {
                    if (compareSemver(latestVer, installedVer) > 0) {
                        isUpdateAvailable = true;
                    } else {
                        // Up to date: silently activate waiting service worker
                        try { swRegistration.waiting.postMessage({ action: 'skipWaiting', type: 'SKIP_WAITING' }); } catch(e) {}
                    }
                }

                // Fallback check if offline or network error: compare local metadata
                if (!isUpdateAvailable && checkInstantUpdateAvailable()) {
                    isUpdateAvailable = true;
                }

                // If recently updated and the latest version is the version we just updated to, don't re-prompt
                if (justUpdatedRecent && justUpdatedVer && compareSemver(latestVer, justUpdatedVer) <= 0 && !isManualCheck && !force) {
                    isUpdateAvailable = false;
                }

                // If installed version is already >= latest version, it is NOT an update unless forced manual check
                if (compareSemver(installedVer, latestVer) >= 0 && !isManualCheck) {
                    isUpdateAvailable = false;
                }

                if (isUpdateAvailable) {
                    showUpdateReadyPrompt(latestVer, isManualCheck, updateChangelog, isManualCheck);
                    return { upToDate: false, updateAvailable: true, version: latestVer };
                } else {
                    // Up to date: close modal elements if currently showing, without writing false dismissal cooldown to storage
                    hideModalElementsIfUpToDate();
                    return { upToDate: true, version: installedVer };
                }
            } finally {
                isCheckingVersion = false;
            }
        })();

        return checkVersionPromise;
    }

    // ── Check if the page was just refreshed after an update ──
    try {
        const justUpdated = sessionStorage.getItem('pwa_just_updated') === 'true';
        if (justUpdated) {
            sessionStorage.removeItem('pwa_just_updated');
            const updatedVer = sessionStorage.getItem('pwa_updated_ver') || getLatestVersion();
            // Preserve pwa_updated_ver in sessionStorage so 60s suppression window remains active
            setTimeout(() => {
                showSystemUpdatedToast(updatedVer);
            }, 400);
        }
    } catch(e) {}

    // Expose helpers globally
    window.showSystemUpdatedToast = showSystemUpdatedToast;
    window.hideSystemUpdatedToast = hideSystemUpdatedToast;
    window.showUpdateReadyPrompt = showUpdateReadyPrompt;
    window.showAppUpdatePopup = showAppUpdatePopup;
    window.hideAppUpdatePopup = hideAppUpdatePopup;
    window.applySystemUpdate = applySystemUpdate;
    window.checkServerVersion = checkServerVersion;
    window.checkInstantUpdateAvailable = checkInstantUpdateAvailable;
    window.showUpdateFallbackPill = showUpdateFallbackPill;
    window.hideUpdateFallbackPill = hideUpdateFallbackPill;

    // ── Universal Background Lifecycle Triggers Across Desktop & Mobile ──
    // Sync stale localStorage installed version: if the server-rendered meta says we're at the latest,
    // update localStorage so checkInstantUpdateAvailable() doesn't produce a false positive.
    try {
        if (DOC_INSTALLED_VER && DOC_LATEST_VER && compareSemver(DOC_INSTALLED_VER, DOC_LATEST_VER) >= 0) {
            // Server confirms we are up to date — align localStorage with actual installed version
            localStorage.setItem('app_installed_version', DOC_INSTALLED_VER);
            localStorage.setItem('pwa_installed_version', DOC_INSTALLED_VER);
            localStorage.setItem('pwa_app_version', DOC_INSTALLED_VER);
        }
    } catch(e) {}

    // 0. Immediate local/meta check on launch: if metadata indicates update available, display immediately
    if (checkInstantUpdateAvailable()) {
        showUpdateReadyPrompt(DOC_LATEST_VER, false, currentChangelogData, false);
    }

    // 1. Immediate initial server check on script evaluation
    checkServerVersion(false);

    // 2. Initial check when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => checkServerVersion(false));
    } else {
        checkServerVersion(false);
    }

    // 3. Periodic background check every 15 seconds
    setInterval(() => {
        checkServerVersion(false);
    }, 15000);

    // 4. Tab visibility change (e.g. user returns to the app from another tab/app)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            checkServerVersion(false);
        }
    });

    // 5. Window focus
    window.addEventListener('focus', () => {
        checkServerVersion(false);
    });

    // 6. Page show (e.g. navigation / mobile bfcache)
    window.addEventListener('pageshow', () => {
        checkServerVersion(false);
    });

    // 7. Network reconnect
    window.addEventListener('online', () => {
        checkServerVersion(false);
    });

    // 8. Navigation state changes
    window.addEventListener('popstate', () => {
        checkServerVersion(false);
    });

    // ── 2. Service Worker Registration & Real-Time Sync ──
    if ('serviceWorker' in navigator) {
        let refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (refreshing) return;
            if (sessionStorage.getItem('pwa_updating') === 'true') {
                sessionStorage.removeItem('pwa_updating');
                refreshing = true;
                window.location.reload(true);
            } else {
                checkServerVersion(true);
            }
        });

        const initServiceWorker = async () => {
            try {
                const reg = await navigator.serviceWorker.register('/sw.js?v={{ $swQueryVer }}', { 
                    scope: '/',
                    updateViaCache: 'none'
                });
                swRegistration = reg;

                // Check version on registration
                try { reg.update().catch(() => {}); } catch(e) {}
                checkServerVersion(false);

                // If an update is waiting:
                if (reg.waiting) {
                    checkServerVersion(false);
                }

                // When new update finishes installing:
                reg.addEventListener('updatefound', () => {
                    const newWorker = reg.installing;
                    if (newWorker) {
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed') {
                                checkServerVersion(false);
                            }
                        });
                    }
                });

                // Listen for broadcast messages from service worker
                navigator.serviceWorker.addEventListener('message', (event) => {
                    if (event.data && (event.data.type === 'UPDATE_AVAILABLE' || event.data.type === 'SW_UPDATED' || event.data.type === 'UPDATE_WAITING')) {
                        console.log('[PWA] Automatic update signal received:', event.data.version);
                        checkServerVersion(false);
                    }
                });

            } catch (err) {
                console.warn('PWA service worker registration notice:', err);
            }
        };

        // Initialize immediately or on DOMContentLoaded — NEVER wait for window.load!
        if (document.readyState !== 'loading') {
            initServiceWorker();
        } else {
            document.addEventListener('DOMContentLoaded', initServiceWorker);
        }
    }

    // ── 3. Standalone State & Universal Install Display Logic ──
    function checkIsStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               document.referrer.includes('android-app://') ||
               window.matchMedia('(display-mode: fullscreen)').matches ||
               window.matchMedia('(display-mode: minimal-ui)').matches;
    }

    async function checkIsAppInstalled() {
        if (checkIsStandalone()) return true;
        if (deferredPrompt) {
            localStorage.removeItem('pwa_app_installed');
            return false;
        }
        if ('getInstalledRelatedApps' in navigator) {
            try {
                const related = await navigator.getInstalledRelatedApps();
                if (related && related.length > 0) {
                    return true;
                }
            } catch(e) {}
        }
        return false;
    }

    function scheduleInstallBanner() {
        @auth
        // Suppress install banner and prompts completely on authenticated dashboard/portal
        return;
        @endauth
        if (checkIsStandalone()) return;
        if (sessionStorage.getItem('pwa_banner_dismissed') === 'true') return;
        const dismissedUntil = localStorage.getItem('pwa_banner_dismissed_until');
        if (dismissedUntil && Date.now() < parseInt(dismissedUntil, 10)) return;

        setTimeout(() => {
            if (checkIsStandalone()) return;
            const banner = document.getElementById('pwaInstallBanner');
            if (banner && banner.style.display !== 'flex') {
                banner.style.display = 'flex';
            }
        }, 2200);
    }

    function hideInstallBanner(dismissDays = 0) {
        const banner = document.getElementById('pwaInstallBanner');
        if (banner) banner.style.display = 'none';
        sessionStorage.setItem('pwa_banner_dismissed', 'true');
        if (dismissDays > 0) {
            localStorage.setItem('pwa_banner_dismissed_until', String(Date.now() + dismissDays * 86400000));
        }
    }

    function syncPwaInstallVisibility() {
        const triggers = document.querySelectorAll('.pwa-install-trigger');
        const banner = document.getElementById('pwaInstallBanner');
        const downloadRow = document.getElementById('smartAppDownloadRow');
        @auth
        // On authenticated dashboard/portal: ensure no install triggers or banner are visible
        triggers.forEach(el => { el.style.setProperty('display', 'none', 'important'); el.style.visibility = 'hidden'; });
        if (banner) banner.style.display = 'none';
        if (downloadRow) downloadRow.style.display = 'none';
        @else
        const standalone = checkIsStandalone();
        const installedFlag = localStorage.getItem('pwa_app_installed') === 'true';
        if (standalone || installedFlag) {
            triggers.forEach(el => { el.style.setProperty('display', 'none', 'important'); el.style.visibility = 'hidden'; });
            if (banner) banner.style.display = 'none';
            if (downloadRow) downloadRow.style.display = 'none';
            return;
        }

        triggers.forEach(el => {
            if (el.id === 'pwaBannerInstallBtn') return;
            el.style.setProperty('display', el.getAttribute('data-display') || 'inline-flex', 'important');
            el.style.visibility = 'visible';
        });

        scheduleInstallBanner();
        @endauth
    }

    // Initialize display when DOM is ready and window loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncPwaInstallVisibility);
    } else {
        syncPwaInstallVisibility();
    }
    window.addEventListener('load', syncPwaInstallVisibility);

    // ── 4. Capture Native beforeinstallprompt Event ──
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        localStorage.removeItem('pwa_app_installed');
        console.log('[PWA] Native install prompt captured and ready.');
        syncPwaInstallVisibility();
    });

    // ── 5. Redesigned Guide Modal Builder & Download Handler ──
    let isDownloadingApk = false;

    function handleHeroCtaClick(btn) {
        if (!btn) return;
        const action = btn.getAttribute('data-action') || 'download';

        // 1. If already installed: open application
        if (action === 'open') {
            closePwaGuideModal();
            window.location.href = '/home';
            return;
        }

        // 2. If desktop / browser native prompt is available
        if (action === 'native_prompt') {
            if (deferredPrompt) {
                closePwaGuideModal();
                triggerPwaInstall();
            } else {
                showNetworkToast('Follow the installation steps below', 'online');
            }
            return;
        }

        // 3. If guide action (iOS, desktop browser menu)
        if (action === 'guide') {
            const stepsSec = document.getElementById('pwaMiniStepsSection');
            if (stepsSec) {
                stepsSec.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            return;
        }

        // 4. If direct APK download (Android / Default)
        if (action === 'download') {
            if (isDownloadingApk) return;
            isDownloadingApk = true;

            const apkUrl = btn.getAttribute('data-apk-url') || '/download/apk';
            const labelEl = document.getElementById('pwaDlLabel');
            const iconEl = document.getElementById('pwaDlIcon');
            const progressEl = document.getElementById('pwaDlProgressBar');
            const alertEl = document.getElementById('pwaDlStatusAlert');

            // 1. Set DOWNLOADING state
            if (labelEl) labelEl.textContent = 'DOWNLOADING...';
            if (iconEl) iconEl.className = 'bi bi-arrow-repeat pwa-dl-icon pwa-spin-fast';
            if (progressEl) {
                progressEl.style.transition = 'width 0.75s ease-out';
                progressEl.style.width = '70%';
            }

            if (window.triggerHaptic) {
                window.triggerHaptic('light');
            } else if (navigator.vibrate) {
                navigator.vibrate(50);
            }

            // 2. After simulated packaging delay, trigger file download & complete progress
            setTimeout(() => {
                const tempLink = document.createElement('a');
                tempLink.href = apkUrl;
                tempLink.download = 'SmartAttendance.apk';
                tempLink.style.display = 'none';
                document.body.appendChild(tempLink);
                tempLink.click();
                setTimeout(() => { tempLink.remove(); }, 300);

                if (progressEl) {
                    progressEl.style.width = '100%';
                }

                // DOWNLOAD STARTED state
                if (labelEl) labelEl.textContent = 'DOWNLOAD STARTED';
                if (iconEl) iconEl.className = 'bi bi-check-circle-fill pwa-dl-icon';

                // Reveal status banner
                if (alertEl) {
                    alertEl.style.display = 'flex';
                }

                if (window.triggerHaptic) {
                    window.triggerHaptic('success');
                } else if (navigator.vibrate) {
                    navigator.vibrate([60, 40, 60]);
                }

                // After 3.5s, ready to download again if needed
                setTimeout(() => {
                    if (progressEl) {
                        progressEl.style.transition = 'none';
                        progressEl.style.width = '0%';
                    }
                    if (labelEl) labelEl.textContent = 'Download Again';
                    if (iconEl) iconEl.className = 'bi bi-arrow-down-circle-fill pwa-dl-icon';
                    isDownloadingApk = false;
                }, 3500);
            }, 800);
        }
    }

    async function checkAppInstallStateAgain(btn) {
        if (btn) {
            btn.style.opacity = '0.7';
            btn.disabled = true;
        }

        if ('serviceWorker' in navigator && swRegistration) {
            try { await swRegistration.update(); } catch(e) {}
        }

        const isStandalone = checkIsStandalone();
        const isInstalled = isStandalone || await checkIsAppInstalled();

        if (btn) {
            btn.style.opacity = '1';
            btn.disabled = false;
        }

        if (isInstalled) {
            showNetworkToast('✓ Smart Attendance is installed!', 'online');
            showPwaGuideModal('already_installed');
            return;
        }

        if (deferredPrompt) {
            closePwaGuideModal();
            triggerPwaInstall();
            return;
        }

        showNetworkToast('Checking app... Open your Downloads folder and tap the APK to install.', 'online');
    }

    function showPwaGuideModal(forceMode = null) {
        ensurePwaModalsInBody();
        const modal = document.getElementById('pwaIosModal');
        const titleEl = document.getElementById('pwaModalTitle');
        const badgeEl = document.getElementById('pwaHeroBadge');
        const verifiedEl = document.getElementById('pwaHeroVerified');
        const heroTitleEl = document.getElementById('pwaHeroTitle');
        const versionEl = document.getElementById('pwaHeroVersion');
        const sizeEl = document.getElementById('pwaHeroSize');
        const platformEl = document.getElementById('pwaHeroPlatform');
        const taglineEl = document.getElementById('pwaHeroTagline');
        const heroDlBtn = document.getElementById('pwaModalDownloadApkBtn');
        const dlIcon = document.getElementById('pwaDlIcon');
        const dlLabel = document.getElementById('pwaDlLabel');
        const dlProgressBar = document.getElementById('pwaDlProgressBar');
        const dlStatusAlert = document.getElementById('pwaDlStatusAlert');
        const heroTrustText = document.getElementById('pwaHeroTrustText');
        const stepsHeader = document.getElementById('pwaStepsHeaderTitle');
        const stepsGrid = document.getElementById('pwaModalSteps');
        const helpDetails = document.getElementById('pwaHelpDetails');
        const helpContent = document.getElementById('pwaHelpContent');
        const closeBtn = document.getElementById('pwaIosCloseBtn');
        const checkBtn = document.getElementById('pwaResetStateBtn');

        if (!modal) return;

        // Reset previous download progress and banner state on each open
        if (dlProgressBar) {
            dlProgressBar.style.transition = 'none';
            dlProgressBar.style.width = '0%';
        }
        if (dlStatusAlert) {
            dlStatusAlert.style.display = 'none';
        }
        if (helpDetails) {
            helpDetails.removeAttribute('open');
        }

        const ua = (window.navigator.userAgent || '').toLowerCase();
        const isIos = /iphone|ipad|ipod/.test(ua);
        const isIosSafari = isIos && !ua.includes('crios') && !ua.includes('fxios') && !ua.includes('edgios');
        const isInApp = /fban|fbav|fb_iab|fbios|instagram|line\/|twitter|snapchat|micromessenger|tiktok|kakaotalk|threads/i.test(ua);
        const isSamsung = ua.includes('samsungbrowser');
        const isAndroid = /android/.test(ua);
        const isSecure = window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

        // Default close behavior
        if (closeBtn) {
            closeBtn.textContent = 'Got It';
            closeBtn.onclick = (e) => {
                e.preventDefault();
                closePwaGuideModal();
            };
        }

        if (forceMode === 'already_installed' || checkIsStandalone()) {
            if (titleEl) titleEl.textContent = 'App Installed';
            if (badgeEl) {
                badgeEl.textContent = '✓ Installed';
                badgeEl.style.color = '#4ADE80';
                badgeEl.style.borderColor = 'rgba(74, 222, 128, 0.35)';
                badgeEl.style.background = 'rgba(74, 222, 128, 0.12)';
            }
            if (taglineEl) taglineEl.textContent = 'Smart Classroom Attendance is installed and ready to use.';
            if (heroDlBtn) {
                heroDlBtn.setAttribute('data-action', 'open');
                heroDlBtn.style.background = 'linear-gradient(135deg, #22C55E 0%, #16A34A 100%)';
            }
            if (dlLabel) dlLabel.textContent = 'Open App';
            if (dlIcon) dlIcon.className = 'bi bi-box-arrow-up-right pwa-dl-icon';
            if (heroTrustText) heroTrustText.textContent = 'Official Application Ready';
            if (stepsHeader) stepsHeader.textContent = 'QUICK ACCESS';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num" style="background:#22C55E;color:#0E0609;">1</div>
                        <div class="pwa-mini-step-label">DRAWER</div>
                        <div class="pwa-mini-step-hint">Swipe up for icon</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num" style="background:#CFA46F;color:#0E0609;">2</div>
                        <div class="pwa-mini-step-label">SHORTCUT</div>
                        <div class="pwa-mini-step-hint">Long-press to Home</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num" style="background:#CFA46F;color:#0E0609;">3</div>
                        <div class="pwa-mini-step-label">INSTANT</div>
                        <div class="pwa-mini-step-hint">Biometrics &amp; QR</div>
                    </div>
                `;
            }
            if (helpContent) {
                helpContent.innerHTML = `
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Can't Find App:</strong> Swipe up into your App Drawer and search for "Smart Attendance".</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Add Shortcut:</strong> Long-press the icon and tap "Add to Home".</div>
                    </div>
                `;
            }
            if (closeBtn) closeBtn.textContent = 'Close';

        } else if (forceMode === 'insecure_context' || !isSecure) {
            if (titleEl) titleEl.textContent = 'HTTPS Required';
            if (badgeEl) {
                badgeEl.textContent = 'Security Note';
                badgeEl.style.color = '#F87171';
                badgeEl.style.borderColor = 'rgba(239, 68, 68, 0.35)';
                badgeEl.style.background = 'rgba(239, 68, 68, 0.12)';
            }
            if (taglineEl) taglineEl.textContent = 'Browsers require a secure HTTPS connection or localhost to install web apps.';
            if (heroDlBtn) heroDlBtn.setAttribute('data-action', 'guide');
            if (dlLabel) dlLabel.textContent = 'HTTPS Setup Needed';
            if (dlIcon) dlIcon.className = 'bi bi-shield-exclamation pwa-dl-icon';
            if (stepsHeader) stepsHeader.textContent = 'HOW TO ACCESS';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num" style="background:#EF4444;color:#FFFFFF;">!</div>
                        <div class="pwa-mini-step-label">HTTP</div>
                        <div class="pwa-mini-step-hint">Unencrypted</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">1</div>
                        <div class="pwa-mini-step-label">LOCAL</div>
                        <div class="pwa-mini-step-hint">Use localhost</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">2</div>
                        <div class="pwa-mini-step-label">TUNNEL</div>
                        <div class="pwa-mini-step-hint">HTTPS tunnel</div>
                    </div>
                `;
            }
            if (helpContent) {
                helpContent.innerHTML = `
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>On PC / Laptop:</strong> Access using <code>http://localhost:8002</code> instead of a local network IP.</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>On Mobile:</strong> Connect via an HTTPS tunnel (Cloudflare or ngrok).</div>
                    </div>
                `;
            }
            if (closeBtn) closeBtn.textContent = 'Close';

        } else if (isInApp) {
            if (titleEl) titleEl.textContent = 'Smart Attendance App';
            if (badgeEl) badgeEl.textContent = 'Application';
            if (taglineEl) taglineEl.textContent = 'Install or download the app for the best standalone experience.';
            if (heroDlBtn) heroDlBtn.setAttribute('data-action', 'download');
            if (dlLabel) dlLabel.textContent = 'Download APK';
            if (dlIcon) dlIcon.className = 'bi bi-arrow-down-circle-fill pwa-dl-icon';
            if (stepsHeader) stepsHeader.textContent = 'GET APP';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">1</div>
                        <div class="pwa-mini-step-label">DOWNLOAD</div>
                        <div class="pwa-mini-step-hint">Tap Download APK</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">2</div>
                        <div class="pwa-mini-step-label">INSTALL</div>
                        <div class="pwa-mini-step-hint">Open and install</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">3</div>
                        <div class="pwa-mini-step-label">LAUNCH</div>
                        <div class="pwa-mini-step-hint">Launch direct app</div>
                    </div>
                `;
            }

        } else if (isIosSafari || isIos) {
            if (titleEl) titleEl.textContent = 'Install on iOS';
            if (badgeEl) {
                badgeEl.textContent = 'iOS Web App';
                badgeEl.style.color = '#E8C064';
                badgeEl.style.borderColor = 'rgba(207, 164, 111, 0.3)';
                badgeEl.style.background = 'rgba(207, 164, 111, 0.15)';
            }
            if (platformEl) platformEl.textContent = 'iOS';
            if (sizeEl) sizeEl.textContent = 'PWA';
            if (taglineEl) taglineEl.textContent = 'Add to your Home Screen for faster clock-in & biometric access.';
            if (heroDlBtn) {
                heroDlBtn.setAttribute('data-action', 'guide');
                heroDlBtn.style.background = 'linear-gradient(135deg, #E8C064 0%, #CFA46F 100%)';
            }
            if (dlLabel) dlLabel.textContent = 'Follow Steps Below';
            if (dlIcon) dlIcon.className = 'bi bi-phone pwa-dl-icon';
            if (heroTrustText) heroTrustText.textContent = 'Official Web Application';
            if (stepsHeader) stepsHeader.textContent = 'HOW TO INSTALL';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">1</div>
                        <div class="pwa-mini-step-label">SHARE</div>
                        <div class="pwa-mini-step-hint">Tap Safari Share</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">2</div>
                        <div class="pwa-mini-step-label">ADD</div>
                        <div class="pwa-mini-step-hint">Add to Home Screen</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">3</div>
                        <div class="pwa-mini-step-label">OPEN</div>
                        <div class="pwa-mini-step-hint">Launch from Home</div>
                    </div>
                `;
            }
            if (helpContent) {
                helpContent.innerHTML = `
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Must Use Safari:</strong> On iOS, Chrome and third-party browsers cannot directly add PWAs to the Home Screen. Open in Safari to install.</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Can't Find "Add to Home Screen":</strong> In Safari, tap the Share icon and scroll down in the action sheet.</div>
                    </div>
                `;
            }

        } else if (!isAndroid) {
            // Desktop PC / Mac / Linux
            if (titleEl) titleEl.textContent = 'Install on Computer';
            if (badgeEl) {
                badgeEl.textContent = 'Desktop Web App';
                badgeEl.style.color = '#E8C064';
                badgeEl.style.borderColor = 'rgba(207, 164, 111, 0.3)';
                badgeEl.style.background = 'rgba(207, 164, 111, 0.15)';
            }
            if (platformEl) platformEl.textContent = 'Desktop';
            if (sizeEl) sizeEl.textContent = 'PWA';
            if (taglineEl) taglineEl.textContent = 'Install directly onto your computer for rapid desktop access.';
            if (heroDlBtn) {
                if (deferredPrompt) {
                    heroDlBtn.setAttribute('data-action', 'native_prompt');
                    if (dlLabel) dlLabel.textContent = 'Install App';
                    if (dlIcon) dlIcon.className = 'bi bi-download pwa-dl-icon';
                } else {
                    heroDlBtn.setAttribute('data-action', 'guide');
                    if (dlLabel) dlLabel.textContent = 'Install via Browser';
                    if (dlIcon) dlIcon.className = 'bi bi-laptop pwa-dl-icon';
                }
                heroDlBtn.style.background = 'linear-gradient(135deg, #E8C064 0%, #CFA46F 100%)';
            }
            if (heroTrustText) heroTrustText.textContent = 'Official Web Application';
            if (stepsHeader) stepsHeader.textContent = 'HOW TO INSTALL';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">1</div>
                        <div class="pwa-mini-step-label">BAR</div>
                        <div class="pwa-mini-step-hint">Click ⊕ / ⬇ in URL bar</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">2</div>
                        <div class="pwa-mini-step-label">CONFIRM</div>
                        <div class="pwa-mini-step-hint">Click Install in popup</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">3</div>
                        <div class="pwa-mini-step-label">LAUNCH</div>
                        <div class="pwa-mini-step-hint">Open from Start / Apps</div>
                    </div>
                `;
            }
            if (helpContent) {
                helpContent.innerHTML = `
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Windows Start Menu:</strong> Installed apps appear in your Start Menu. Search for "Smart Attendance".</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Desktop Shortcut:</strong> Open <code>chrome://apps</code> (or <code>brave://apps</code>), right-click Smart Attendance, and select <strong>Create shortcuts &gt; Desktop</strong>.</div>
                    </div>
                `;
            }

        } else {
            // Android Default
            if (titleEl) titleEl.textContent = 'Get Mobile App';
            if (badgeEl) {
                badgeEl.textContent = 'Official Android App';
                badgeEl.style.color = '#E8C064';
                badgeEl.style.borderColor = 'rgba(207, 164, 111, 0.3)';
                badgeEl.style.background = 'rgba(207, 164, 111, 0.15)';
            }
            if (platformEl) platformEl.textContent = 'Android';
            if (sizeEl) sizeEl.textContent = '~4 MB';
            if (taglineEl) taglineEl.textContent = 'Install for faster clock-in, biometric access &amp; instant alerts.';
            if (heroDlBtn) {
                heroDlBtn.setAttribute('data-action', 'download');
                heroDlBtn.style.background = 'linear-gradient(135deg, #E8C064 0%, #CFA46F 100%)';
            }
            if (dlLabel) dlLabel.textContent = 'Download APK';
            if (dlIcon) dlIcon.className = 'bi bi-arrow-down-circle-fill pwa-dl-icon';
            if (heroTrustText) heroTrustText.textContent = 'Official Smart Classroom Attendance App';
            if (stepsHeader) stepsHeader.textContent = 'HOW TO INSTALL';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">1</div>
                        <div class="pwa-mini-step-label">DOWNLOAD</div>
                        <div class="pwa-mini-step-hint">Tap Download APK</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">2</div>
                        <div class="pwa-mini-step-label">INSTALL</div>
                        <div class="pwa-mini-step-hint">Open file &amp; tap Install</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">3</div>
                        <div class="pwa-mini-step-label">OPEN</div>
                        <div class="pwa-mini-step-hint">Launch from Drawer</div>
                    </div>
                `;
            }
            if (helpContent) {
                helpContent.innerHTML = `
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Samsung / Pixel:</strong> Open the downloaded APK and tap Install. The icon appears in your App Drawer (swipe up). Long-press to add to Home.</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Xiaomi / Redmi / Poco:</strong> If home screen shortcuts are blocked: Settings &gt; Apps &gt; Permissions &gt; enable "Home screen shortcuts".</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Browser Warning:</strong> Tap "Details" or "Download anyway" if your browser displays a standard prompt for direct APK files.</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Can't Find Download:</strong> Open your phone's <strong>Files</strong> or <strong>Downloads</strong> app and tap <code>SmartAttendance.apk</code>.</div>
                    </div>
                `;
            }
        }

        const apkBannerBtn = document.getElementById('pwaBannerApkBtn');
        if (isAndroid && apkBannerBtn) {
            apkBannerBtn.style.display = 'inline-flex';
        }

        modal.style.display = 'flex';
    }

    function closePwaGuideModal() {
        const modal = document.getElementById('pwaIosModal');
        if (modal) modal.style.display = 'none';
        localStorage.setItem('pwa_ios_prompt_dismissed', 'true');
    }

    function resetPwaStateAndRetry() {
        checkAppInstallStateAgain(document.getElementById('pwaResetStateBtn'));
    }

    // ── 6. Universal Trigger Install Handler ──
    async function triggerPwaInstall(triggerEl) {
        if (window.__pwaActionInProgress) return;
        window.__pwaActionInProgress = true;
        setTimeout(() => { window.__pwaActionInProgress = false; }, 1500);

        // 1. Check if ALREADY open in standalone window
        if (checkIsStandalone()) {
            showPwaGuideModal('already_installed');
            return;
        }

        // 2. Security / HTTPS check
        const isSecure = window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        if (!isSecure) {
            showPwaGuideModal('insecure_context');
            return;
        }

        // 3. Native prompt immediately available: ALWAYS prioritize native prompt
        if (deferredPrompt) {
            try {
                if (triggerEl) triggerEl.classList.add('pwa-btn-loading');
                const promptEvent = deferredPrompt;
                deferredPrompt = null;
                promptEvent.prompt();
                const choice = await promptEvent.userChoice;
                if (triggerEl) triggerEl.classList.remove('pwa-btn-loading');
                
                if (choice && choice.outcome === 'accepted') {
                    localStorage.setItem('pwa_app_installed', 'true');
                    hideInstallBanner(30);
                    const isAndroidDevice = /android/.test(ua);
                    if (isAndroidDevice) {
                        showNetworkToast('✓ Installed! Swipe UP to open App Drawer, then long-press app & tap "Add to Home".', 'online');
                    } else if (!isIos) {
                        showNetworkToast('✓ Installed! Find it in Windows Start Menu or open chrome://apps for a Desktop shortcut.', 'online');
                    } else {
                        showNetworkToast('✓ Smart Attendance installed successfully!', 'online');
                    }
                    syncPwaInstallVisibility();
                    return;
                } else {
                    showNetworkToast('Installation postponed. You can install anytime from the menu.', 'offline');
                    return;
                }
            } catch(err) {
                console.warn('[PWA] Prompt error:', err);
                if (triggerEl) triggerEl.classList.remove('pwa-btn-loading');
            }
        }

        // 4. If deferredPrompt not yet available, wait briefly in case beforeinstallprompt is in flight
        const ua = (window.navigator.userAgent || '').toLowerCase();
        const isIos = /iphone|ipad|ipod/.test(ua);
        if (!deferredPrompt && !isIos && isSecure && !window.__pwaWaitedBeforePrompt) {
            window.__pwaWaitedBeforePrompt = true;
            if (triggerEl) {
                const originalHtml = triggerEl.innerHTML;
                triggerEl.innerHTML = '<span class="pwa-spinner"></span> Opening installer...';
                triggerEl.classList.add('pwa-btn-loading');

                const promptFired = await Promise.race([
                    new Promise(resolve => {
                        const handler = () => {
                            window.removeEventListener('beforeinstallprompt', handler);
                            resolve(true);
                        };
                        window.addEventListener('beforeinstallprompt', handler, { once: true });
                    }),
                    new Promise(resolve => setTimeout(() => resolve(false), 900))
                ]);

                triggerEl.innerHTML = originalHtml;
                triggerEl.classList.remove('pwa-btn-loading');

                if (promptFired && deferredPrompt) {
                    try {
                        const promptEvent = deferredPrompt;
                        deferredPrompt = null;
                        promptEvent.prompt();
                        const choice = await promptEvent.userChoice;
                        if (choice && choice.outcome === 'accepted') {
                            localStorage.setItem('pwa_app_installed', 'true');
                            hideInstallBanner(30);
                            showNetworkToast('✓ Smart Attendance installed! Check your App Drawer (swipe up) or Home screen.', 'online');
                            syncPwaInstallVisibility();
                            return;
                        } else {
                            showNetworkToast('Installation postponed.', 'offline');
                            return;
                        }
                    } catch(e) {}
                }
            }
        }

        // 5. Show browser-tailored guide modal (iOS Safari, desktop address bar, Android menu, etc.)
        showPwaGuideModal();
    }

    // Expose helpers globally
    window.triggerPwaInstall = triggerPwaInstall;
    window.showPwaGuideModal = showPwaGuideModal;
    window.closePwaGuideModal = closePwaGuideModal;
    window.checkIsStandalone = checkIsStandalone;

    // ── 7. Global Event Delegation (Guarantees ALL install buttons work anytime) ──
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closePwaGuideModal();
        }
    });

    document.addEventListener('click', (e) => {
        const target = e.target;
        if (!target) return;

        // Install button clicked
        const installTrigger = target.closest('.pwa-install-trigger') || target.closest('#pwaInstallBtn') || target.closest('#downloadAppBtn');
        if (installTrigger) {
            e.preventDefault();
            e.stopPropagation();
            triggerPwaInstall(installTrigger);
            return;
        }

        // Close floating banner
        const closeBannerTrigger = target.closest('#pwaBannerCloseBtn');
        if (closeBannerTrigger) {
            e.preventDefault();
            hideInstallBanner(3);
            return;
        }

        // Download APK / Hero CTA in redesigned modal
        const heroCtaTrigger = target.closest('#pwaModalDownloadApkBtn');
        if (heroCtaTrigger) {
            e.preventDefault();
            e.stopPropagation();
            handleHeroCtaClick(heroCtaTrigger);
            return;
        }

        // Check Again button in modal
        const resetTrigger = target.closest('#pwaResetStateBtn');
        if (resetTrigger) {
            e.preventDefault();
            e.stopPropagation();
            checkAppInstallStateAgain(resetTrigger);
            return;
        }

        // Close guide modal
        const closeTrigger = target.closest('#pwaIosCloseBtn') || target.closest('#pwaModalCloseIcon');
        if (closeTrigger) {
            e.preventDefault();
            closePwaGuideModal();
            return;
        }

        // Modal backdrop click
        const iosModal = document.getElementById('pwaIosModal');
        if (iosModal && target === iosModal) {
            closePwaGuideModal();
            return;
        }

        // Dismiss "System Updated ✓" Toast
        const dismissUpdatedToast = target.closest('#pwaDismissUpdatedToastBtn');
        if (dismissUpdatedToast) {
            e.preventDefault();
            hideSystemUpdatedToast();
            return;
        }

        // Floating update pill clicked
        const pillTrigger = target.closest('#pwaUpdatePill');
        if (pillTrigger) {
            e.preventDefault();
            e.stopPropagation();
            hideUpdateFallbackPill();
            showUpdateReadyPrompt(latestDetectedVersion, true, currentChangelogData, true);
            return;
        }

        // Apply Update button clicked ("Refresh Now")
        const applyUpdateBtn = target.closest('#pwaApplyUpdateBtn');
        if (applyUpdateBtn) {
            e.preventDefault();
            applySystemUpdate();
            return;
        }

        // Dismiss Update Ready prompt (Dismiss for current session)
        const dismissUpdateBtn = target.closest('#pwaDismissUpdatePopupBtn') || target.closest('#pwaLaterUpdateBtn') || (target.id === 'pwaUpdateBackdrop');
        if (dismissUpdateBtn) {
            e.preventDefault();
            hideAppUpdatePopup(latestDetectedVersion);
            return;
        }
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        const installBanner = document.getElementById('pwaInstallBanner');
        const iosModal = document.getElementById('pwaIosModal');
        const downloadRow = document.getElementById('smartAppDownloadRow');
        if (installBanner) installBanner.style.display = 'none';
        if (iosModal) iosModal.style.display = 'none';
        if (downloadRow) downloadRow.style.display = 'none';
        localStorage.setItem('pwa_prompt_dismissed', 'true');
        localStorage.setItem('pwa_app_installed', 'true');
        document.querySelectorAll('.pwa-install-trigger').forEach(el => {
            el.style.display = 'none';
        });
        const currentUa = (window.navigator.userAgent || '').toLowerCase();
        if (/android/.test(currentUa)) {
            showNetworkToast('✓ Smart Attendance installed! Check your App Drawer (swipe up) to add it to Home.', 'online');
        } else if (!/iphone|ipad|ipod/.test(currentUa)) {
            showNetworkToast('✓ Smart Attendance installed! Find it in Windows Start Menu or open chrome://apps for Desktop shortcut.', 'online');
        }
    });

    // ── 7. Real-time Network Connectivity Notifications ──
    const networkToast = document.getElementById('pwaNetworkToast');
    function showNetworkToast(message, type) {
        const toast = document.getElementById('pwaNetworkToast');
        if (!toast) return;
        toast.textContent = message;
        toast.className = `pwa-network-toast show ${type}`;
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3500);
    }

    window.addEventListener('offline', () => {
        showNetworkToast('⚡ You are currently offline. Offline mode active.', 'offline');
    });

    window.addEventListener('online', () => {
        showNetworkToast('✓ Connection restored! Back online.', 'online');
    });

    // ── 8. Cache User Session for Offline Mode ──
    @auth
        try {
            const userProfile = {
                name: "{{ addslashes(auth()->user()->name) }}",
                role: "{{ auth()->user()->role }}",
                email: "{{ auth()->user()->email }}",
                student_number: "{{ auth()->user()->student_number ?? '' }}",
                course: "{{ auth()->user()->course ?? '' }}",
                year_level: "{{ auth()->user()->year_level ?? '' }}",
                timestamp: new Date().toISOString()
            };
            localStorage.setItem('cached_user_profile', JSON.stringify(userProfile));
            localStorage.setItem('cached_student_profile', JSON.stringify(userProfile));
        } catch(e) {}
    @endauth
</script>
