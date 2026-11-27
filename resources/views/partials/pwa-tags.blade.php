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
        color: #F3E7CD;
        border: 1px solid rgba(207, 164, 111, 0.35);
        border-radius: 20px;
        padding: 16px 20px;
        padding-inline-end: 3.5rem;
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
        background: #141010;
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
        color: #141010;
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
        color: #141010;
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
        background: rgba(20, 14, 14, 0.85);
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
        color: #141010;
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

    /* Feature announcements stay compact and leave the page usable. */
    .pwa-update-banner[data-update-mode] { overflow-y: auto !important; overscroll-behavior: contain; }
    .pwa-update-banner[data-update-mode="announcement"] {
        top: auto !important;
        bottom: calc(100px + env(safe-area-inset-bottom, 0px)) !important;
        transform: translateX(-50%) !important;
        animation: none !important;
        border-radius: 16px !important;
        max-height: calc(100dvh - 180px) !important;
    }
    .pwa-update-banner[data-update-mode="announcement"] .pwa-update-inner { padding: 16px; gap: 14px; }
    .pwa-update-banner[data-update-mode="announcement"] .pwa-update-icon-container,
    .pwa-update-banner[data-update-mode="announcement"] .pwa-update-app-icon { width: 36px; height: 36px; animation: none; }
    .pwa-update-banner[data-update-mode="announcement"] .pwa-update-accent-bar { animation: none; }
    .pwa-update-banner[data-update-mode="announcement"] .pwa-update-title { font-size: 1rem; }
    @media (min-width: 769px) {
        .pwa-update-banner[data-update-mode="announcement"] { bottom: 24px !important; }
    }
    .pwa-update-banner[data-update-mode="announcement"] .pwa-update-glow,
    .pwa-update-banner[data-update-mode="announcement"] .pwa-update-icon-glow-ring {
        display: none;
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
        color: #F3E7CD;
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
    .pwa-update-text-area { flex: 1; min-width: 0; }
    .pwa-update-banner:not([data-update-mode="critical"]) .pwa-update-text-area { padding-inline-end: 3.5rem; }
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
        background: linear-gradient(135deg, #E8C064 0%, #CFA46F 100%); color: #141010;
        font-size: 0.72rem; font-weight: 800; padding: 3px 10px;
        border-radius: 99px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    @keyframes pwaFadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>
