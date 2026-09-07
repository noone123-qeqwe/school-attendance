@php
    $installedVersion = (string)config('changelog.installed_version', '2.3.0');
    $latestVersion = (string)config('changelog.default_version', '2.3.0');
    $swCacheVer = \Illuminate\Support\Facades\Cache::get('pwa_sw_version', $latestVersion);
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
<meta name="app-latest-version" content="{{ $latestVersion }}">

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

    /* ── Big Tech / Enterprise System Update Card (Apple / Linear / Slack Style) ── */
    .pwa-update-banner {
        position: fixed;
        bottom: 24px;
        right: 24px;
        left: auto;
        width: 410px;
        max-width: calc(100vw - 32px);
        background: rgba(14, 6, 9, 0.94);
        border: 1px solid rgba(232, 192, 100, 0.28);
        border-radius: 22px;
        padding: 18px 20px;
        box-shadow: 0 24px 70px rgba(0, 0, 0, 0.85), 
                    0 4px 20px rgba(0, 0, 0, 0.5), 
                    inset 0 1px 0 rgba(255, 255, 255, 0.15),
                    0 0 35px rgba(232, 192, 100, 0.08);
        backdrop-filter: blur(28px) saturate(180%);
        -webkit-backdrop-filter: blur(28px) saturate(180%);
        z-index: 100005 !important;
        display: none;
        flex-direction: column;
        gap: 13px;
        animation: pwaSlideUpEnterprise 0.45s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
        box-sizing: border-box;
    }

    /* Ambient Subtle Shimmer Glow */
    .pwa-update-glow {
        position: absolute;
        top: -60px;
        right: -60px;
        width: 140px;
        height: 140px;
        background: radial-gradient(circle, rgba(232, 192, 100, 0.18) 0%, transparent 70%);
        pointer-events: none;
        z-index: 0;
    }

    @keyframes pwaSlideUpEnterprise {
        0% {
            opacity: 0;
            transform: translateY(30px) scale(0.96);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* On mobile / tablets: Gracefully float above bottom navigation capsule with safe area */
    @media (max-width: 768px) {
        .pwa-update-banner {
            bottom: calc(84px + env(safe-area-inset-bottom, 12px)) !important;
            left: 12px !important;
            right: 12px !important;
            width: auto !important;
            max-width: calc(100vw - 24px) !important;
            margin: 0 auto !important;
            padding: 16px 18px !important;
            border-radius: 20px !important;
            max-height: min(520px, calc(100dvh - 96px - env(safe-area-inset-bottom, 12px))) !important;
            box-sizing: border-box !important;
            z-index: 100005 !important;
        }

        .pwa-update-banner-actions {
            flex-direction: row !important;
            gap: 10px !important;
        }

        .pwa-btn-update-later,
        .pwa-btn-update-apply {
            min-height: 44px !important;
            touch-action: manipulation !important;
        }
    }

    /* Small screens / landscape mobile phones: keep content accessible within viewport */
    @media (max-width: 768px) and (max-height: 540px) {
        .pwa-update-banner {
            top: calc(8px + env(safe-area-inset-top, 0px)) !important;
            bottom: auto !important;
            max-height: calc(100dvh - 16px - env(safe-area-inset-top, 0px)) !important;
        }
    }

    .pwa-update-banner-header {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        gap: 13px;
    }

    .pwa-update-icon-container {
        position: relative;
        flex-shrink: 0;
    }

    .pwa-update-app-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        border: 1px solid rgba(232, 192, 100, 0.35);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4);
        background: #18080c;
        display: block;
        object-fit: cover;
    }

    .pwa-update-pulse-indicator {
        position: absolute;
        top: -3px;
        right: -3px;
        width: 11px;
        height: 11px;
        background: #22C55E;
        border: 2px solid #0e0609;
        border-radius: 50%;
        box-shadow: 0 0 10px #22C55E;
        animation: pwaPulseDot 2s infinite ease-in-out;
    }

    @keyframes pwaPulseDot {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.75; }
    }

    .pwa-update-text-area {
        flex: 1;
        min-width: 0;
        padding-right: 18px;
    }

    .pwa-update-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 3px;
    }

    .pwa-update-tag {
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: #e8c064;
        background: rgba(232, 192, 100, 0.12);
        border: 1px solid rgba(232, 192, 100, 0.25);
        padding: 2px 7px;
        border-radius: 6px;
    }

    .pwa-update-version-badge {
        font-size: 0.72rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.6);
        font-variant-numeric: tabular-nums;
    }

    .pwa-update-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.02rem;
        font-weight: 700;
        color: #FFFFFF;
        line-height: 1.25;
        letter-spacing: -0.2px;
        margin-bottom: 3px;
    }

    .pwa-update-subtitle {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.68);
        line-height: 1.4;
    }

    .pwa-update-close-btn {
        position: absolute;
        top: -6px;
        right: -6px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.55);
        font-size: 1.25rem;
        cursor: pointer;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        padding: 0;
        transition: all 0.2s ease;
    }

    .pwa-update-close-btn:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #FFFFFF;
        border-color: rgba(255, 255, 255, 0.25);
    }

    /* Actions */
    .pwa-update-banner-actions {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
    }

    .pwa-btn-update-later {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.7);
        font-weight: 600;
        font-size: 0.86rem;
        border-radius: 12px;
        padding: 11px 18px;
        cursor: pointer;
        transition: all 0.2s ease;
        touch-action: manipulation;
        white-space: nowrap;
    }

    .pwa-btn-update-later:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #FFFFFF;
        border-color: rgba(255, 255, 255, 0.25);
    }

    .pwa-btn-update-apply {
        flex: 1;
        background: linear-gradient(135deg, #e8c064 0%, #cfa46f 100%);
        color: #0a0305;
        font-weight: 700;
        font-size: 0.88rem;
        border: none;
        border-radius: 12px;
        padding: 11px 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 18px rgba(232, 192, 100, 0.35);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        touch-action: manipulation;
        white-space: nowrap;
    }

    .pwa-btn-update-apply:hover {
        filter: brightness(1.08);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(232, 192, 100, 0.45);
    }

    .pwa-btn-update-apply:active {
        transform: scale(0.97);
    }

    .pwa-btn-arrow-icon {
        transition: transform 0.2s ease;
    }

    .pwa-btn-update-apply:hover .pwa-btn-arrow-icon {
        transform: translateX(3px);
    }

    @keyframes pwaFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
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
                        <span id="pwaHeroVersion">v{{ config('changelog.default_version', '2.3.0') }}</span>
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

<!-- Enterprise-Grade System Update Notification (Dynamic Version-Based Changelog) -->
<div class="pwa-update-banner" id="pwaSystemUpdatePopup" style="display: none;">
    <div class="pwa-update-glow"></div>
    <div class="pwa-update-banner-header">
        <div class="pwa-update-icon-container">
            <img src="/images/icons/icon-72x72.png" class="pwa-update-app-icon" alt="Smart Attendance">
            <span class="pwa-update-pulse-indicator" title="New build ready"></span>
        </div>
        <div class="pwa-update-text-area">
            <div class="pwa-update-meta">
                <span class="pwa-update-tag">SYSTEM UPDATE</span>
                <span class="pwa-update-version-badge" id="pwaUpdateVersionBadge">{{ $initialChangelog['version_display'] ?? ('VERSION ' . ltrim((string)$swCacheVer, 'v')) }}</span>
            </div>
            <div class="pwa-update-title" id="pwaUpdateTitle">{{ $initialChangelog['title'] ?? 'Software Update Available' }}</div>
        </div>
        <button type="button" class="pwa-update-close-btn" id="pwaDismissUpdatePopupBtn" aria-label="Dismiss">&times;</button>
    </div>

    <div class="pwa-update-banner-actions">
        <button type="button" class="pwa-btn-update-later" id="pwaLaterUpdateBtn">
            Later
        </button>
        <button type="button" class="pwa-btn-update-apply" id="pwaApplyUpdateBtn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="pwa-update-spin-icon" style="display:none; animation: ptr-spin 0.8s linear infinite;">
                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
            </svg>
            <span id="pwaApplyUpdateBtnText">Restart &amp; Update</span>
            <svg class="pwa-btn-arrow-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </button>
    </div>
</div>

<!-- Real-time Connectivity Toast -->
<div class="pwa-network-toast" id="pwaNetworkToast"></div>

<script @cspNonce>
    // ── 1. Register Service Worker & Handle Real-Time Update Notifications ──
    let swRegistration = null;
    let deferredPrompt = null;
    let latestDetectedVersion = null;
    let currentChangelogData = null;

    // Track which specific version we already notified about (not just a boolean)
    // so that a NEW version from the server always triggers a fresh popup
    let lastNotifiedVersion = null;

    function updateChangelogUI(changelog) {
        if (!changelog) return;
        currentChangelogData = changelog;

        const badge = document.getElementById('pwaUpdateVersionBadge');
        if (badge) {
            badge.textContent = changelog.version_display || ('VERSION ' + (changelog.version || '2.3.0'));
        }

        const titleEl = document.getElementById('pwaUpdateTitle');
        if (titleEl && changelog.title) {
            titleEl.textContent = changelog.title;
        }

        const subEl = document.getElementById('pwaUpdateSubtitle');
        if (subEl && changelog.description) {
            subEl.textContent = changelog.description;
        }
    }

    // ── 1.1 Semantic Version Comparison (Latest Version > Installed Version) ──
    function parseSemver(v) {
        if (!v) return [0, 0, 0];
        let cleaned = String(v).trim().replace(/^[vV]/, '');
        if (cleaned.includes('_')) {
            cleaned = cleaned.split('_')[0];
        }
        if (/^\d{2,3}$/.test(cleaned)) {
            cleaned = cleaned.split('').join('.');
        }
        const parts = cleaned.split('.').map(function(p) {
            const num = parseInt(p.replace(/[^\d]/g, ''), 10);
            return isNaN(num) ? 0 : num;
        });
        while (parts.length < 3) parts.push(0);
        return parts.slice(0, 3);
    }

    function compareSemver(v1, v2) {
        const p1 = parseSemver(v1);
        const p2 = parseSemver(v2);
        for (let i = 0; i < 3; i++) {
            if (p1[i] > p2[i]) return 1;
            if (p1[i] < p2[i]) return -1;
        }
        return 0;
    }

    function getInstalledVersion() {
        const metaInstalled = document.querySelector('meta[name="app-installed-version"]')?.content || '2.3.0';
        const storedInstalled = localStorage.getItem('pwa_installed_version');
        
        // If metaInstalled is newer than storedInstalled, auto-sync localStorage
        if (storedInstalled) {
            if (compareSemver(storedInstalled, metaInstalled) < 0) {
                localStorage.setItem('pwa_installed_version', metaInstalled);
                localStorage.setItem('pwa_app_version', metaInstalled);
                return metaInstalled;
            }
            return storedInstalled;
        }

        const legacyVer = localStorage.getItem('pwa_app_version');
        if (legacyVer && !legacyVer.includes('_') && /^\d/.test(legacyVer)) {
            if (compareSemver(legacyVer, metaInstalled) < 0) {
                localStorage.setItem('pwa_installed_version', metaInstalled);
                localStorage.setItem('pwa_app_version', metaInstalled);
                return metaInstalled;
            }
            return legacyVer;
        }

        if (metaInstalled) {
            localStorage.setItem('pwa_installed_version', metaInstalled);
            return metaInstalled;
        }

        return '2.3.0';
    }

    function getLatestVersion(serverData = null) {
        if (serverData && serverData.latest_version) {
            return serverData.latest_version;
        }
        if (serverData && serverData.changelog && serverData.changelog.version) {
            return serverData.changelog.version;
        }
        const metaLatest = document.querySelector('meta[name="app-latest-version"]')?.content;
        return metaLatest || '2.3.0';
    }

    // ── 1.2 DOM Health: Ensure PWA Modals & Overlays live in document.body ──
    function ensurePwaModalsInBody() {
        if (!document.body) return false;
        ['pwaInstallBanner', 'pwaIosModal', 'pwaSystemUpdatePopup', 'pwaNetworkToast'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        });
        return true;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensurePwaModalsInBody);
    } else {
        ensurePwaModalsInBody();
    }

    function showAppUpdatePopup(version, force = false, changelog = null) {
        if (version) latestDetectedVersion = version;

        // Ensure modal is attached to document.body so mobile WebKit/Blink renders it
        if (!ensurePwaModalsInBody() && document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                ensurePwaModalsInBody();
                showAppUpdatePopup(version, force, changelog);
            }, { once: true });
            return;
        }

        const currentInstalled = getInstalledVersion();
        const targetVersion = version || latestDetectedVersion || getLatestVersion();

        // Under NO circumstances should an update popup show if installed >= target!
        if (compareSemver(targetVersion, currentInstalled) <= 0) {
            return;
        }

        const sessionDismissed = sessionStorage.getItem('pwa_update_dismissed_ver');
        const localDismissed = localStorage.getItem('pwa_update_dismissed_ver');
        const isDismissed = (sessionDismissed === targetVersion) || (localDismissed === targetVersion);

        // Only block if this EXACT version was dismissed AND not forced
        if (!force && isDismissed) {
            return;
        }

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

        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (popup) {
            popup.style.display = 'none';
            void popup.offsetHeight; // force reflow
            popup.style.display = 'flex';
        }

        // Trigger multi-channel update alerts ONCE PER NEW VERSION
        const isNewVersion = !lastNotifiedVersion || lastNotifiedVersion !== latestDetectedVersion;
        if (isNewVersion) {
            lastNotifiedVersion = latestDetectedVersion;

            if (window.triggerHaptic) {
                window.triggerHaptic('success');
            } else if (navigator.vibrate) {
                navigator.vibrate([100, 50, 100]);
            }

            if ('Notification' in window && Notification.permission === 'granted') {
                try {
                    const notifTitle = '🚀 Smart Attendance Update Available';
                    const notifOptions = {
                        body: 'A new version of the attendance portal is ready. Tap to load the latest features and optimizations.',
                        icon: '/images/icons/icon-192x192.png',
                        badge: '/images/icons/icon-72x72.png',
                        vibrate: [100, 50, 100],
                        tag: 'app-update-' + (latestDetectedVersion || Date.now()),
                        renotify: true,
                        data: { url: window.location.href, action: 'update' }
                    };

                    if (swRegistration && swRegistration.showNotification) {
                        swRegistration.showNotification(notifTitle, notifOptions);
                    } else {
                        new Notification(notifTitle, notifOptions);
                    }
                } catch(e) {}
            }

            if (typeof window.refreshNotificationBell === 'function') {
                try { window.refreshNotificationBell(); } catch(e) {}
            }
        }
    }

    function hideAppUpdatePopup(version) {
        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (popup) popup.style.display = 'none';

        const targetVersion = version || latestDetectedVersion || getLatestVersion();
        if (targetVersion) {
            sessionStorage.setItem('pwa_update_dismissed_ver', targetVersion);
            localStorage.setItem('pwa_update_dismissed_ver', targetVersion);
        }
    }

    let lastVersionCheckTime = 0;
    const VERSION_CHECK_COOLDOWN_MS = 2000;

    async function checkServerVersion(force = false) {
        const now = Date.now();
        if (!force && (now - lastVersionCheckTime < VERSION_CHECK_COOLDOWN_MS)) {
            return;
        }
        lastVersionCheckTime = now;

        const installedVer = getInstalledVersion();
        let latestVer = getLatestVersion();

        if (swRegistration) {
            try { swRegistration.update(); } catch(e) {}
        }

        let isUpdateAvailable = false;
        let updateChangelog = currentChangelogData;

        try {
            const res = await fetch('/pwa/version?_t=' + now, { cache: 'no-store' });
            if (res.ok) {
                const data = await res.json();
                if (data) {
                    latestVer = getLatestVersion(data);
                    latestDetectedVersion = latestVer;
                    if (data.changelog) {
                        updateChangelog = data.changelog;
                        updateChangelogUI(updateChangelog);
                    }

                    // Semantic comparison: Latest Version > Installed Version
                    if (compareSemver(latestVer, installedVer) > 0) {
                        isUpdateAvailable = true;
                    }
                }
            }
        } catch (e) {}

        // Fallback check if offline or network error: compare local metadata
        if (!isUpdateAvailable && compareSemver(latestVer, installedVer) > 0) {
            isUpdateAvailable = true;
        }

        if (isUpdateAvailable) {
            showAppUpdatePopup(latestVer, force, updateChangelog);
        }
    }

    // Run check immediately on evaluation
    checkServerVersion();

    if ('serviceWorker' in navigator) {
        let refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (refreshing) return;
            if (sessionStorage.getItem('pwa_updating') === 'true') {
                sessionStorage.removeItem('pwa_updating');
                refreshing = true;
                window.location.reload();
            }
        });

        window.addEventListener('load', async () => {
            try {
                const reg = await navigator.serviceWorker.register('/sw.js?v={{ $swQueryVer }}', { 
                    scope: '/',
                    updateViaCache: 'none'
                });
                swRegistration = reg;

                // 1. Check version on load (do NOT force-override user dismissal)
                try { reg.update(); } catch(e) {}
                checkServerVersion(false);

                // 2. Periodic check every 30s for real-time background detection
                setInterval(() => {
                    if (swRegistration) try { swRegistration.update(); } catch(e) {}
                    checkServerVersion(false);
                }, 30000);

                // 3. Check on page visibility change (when user returns to tab)
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        if (swRegistration) try { swRegistration.update(); } catch(e) {}
                        checkServerVersion(false);
                    }
                });

                // 4. If an update is already downloaded and waiting in background:
                if (reg.waiting) {
                    const currentInstalled = getInstalledVersion();
                    const latest = latestDetectedVersion || getLatestVersion();
                    if (compareSemver(latest, currentInstalled) > 0) {
                        showAppUpdatePopup(latest, false);
                    } else {
                        // Current installed version is already up to date; silently activate worker
                        reg.waiting.postMessage({ action: 'skipWaiting', type: 'SKIP_WAITING' });
                    }
                }

                // 5. When a new update is found and finishes installing in the background
                reg.addEventListener('updatefound', () => {
                    const newWorker = reg.installing;
                    if (newWorker) {
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed') {
                                if (navigator.serviceWorker.controller) {
                                    const currentInstalled = getInstalledVersion();
                                    const latest = latestDetectedVersion || getLatestVersion();
                                    if (compareSemver(latest, currentInstalled) > 0) {
                                        showAppUpdatePopup(latest, false);
                                    }
                                } else {
                                    checkServerVersion(false);
                                }
                            }
                        });
                    }
                });

                // 6. Listen for broadcast message from push or system broadcast
                navigator.serviceWorker.addEventListener('message', (event) => {
                    if (event.data && (event.data.type === 'UPDATE_AVAILABLE' || event.data.type === 'SW_UPDATED')) {
                        console.log('[PWA] Update available:', event.data.version);
                        try {
                            sessionStorage.setItem('pwa_update_available', event.data.version || 'latest');
                        } catch(e) {}
                    }
                });

                // 7. Mobile & desktop lifecycle: focus, pageshow (bfcache restore), and online
                window.addEventListener('focus', () => {
                    if (swRegistration) try { swRegistration.update(); } catch(e) {}
                    checkServerVersion(false);
                });
                window.addEventListener('pageshow', () => {
                    if (swRegistration) try { swRegistration.update(); } catch(e) {}
                    checkServerVersion(false);
                });
                window.addEventListener('online', () => {
                    checkServerVersion(false);
                });

            } catch (err) {
                console.warn('PWA service worker registration failed:', err);
            }
        });
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
        const standalone = checkIsStandalone();
        const triggers = document.querySelectorAll('.pwa-install-trigger');
        if (standalone) {
            triggers.forEach(el => { el.style.display = 'none'; });
            const banner = document.getElementById('pwaInstallBanner');
            if (banner) banner.style.display = 'none';
            return;
        }

        triggers.forEach(el => {
            if (el.id === 'pwaBannerInstallBtn') return;
            el.style.display = el.getAttribute('data-display') || 'inline-flex';
        });

        scheduleInstallBanner();
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
            if (titleEl) titleEl.textContent = 'Open in Browser';
            if (badgeEl) badgeEl.textContent = 'In-App Browser';
            if (taglineEl) taglineEl.textContent = 'Please open in Chrome or Safari to install the app or download the APK.';
            if (heroDlBtn) heroDlBtn.setAttribute('data-action', 'download');
            if (dlLabel) dlLabel.textContent = 'Download APK';
            if (dlIcon) dlIcon.className = 'bi bi-arrow-down-circle-fill pwa-dl-icon';
            if (stepsHeader) stepsHeader.textContent = 'SWITCH BROWSER';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">1</div>
                        <div class="pwa-mini-step-label">MENU</div>
                        <div class="pwa-mini-step-hint">Tap ⋯ or ⋮ icon</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">2</div>
                        <div class="pwa-mini-step-label">OPEN</div>
                        <div class="pwa-mini-step-hint">In Chrome / Safari</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">3</div>
                        <div class="pwa-mini-step-label">INSTALL</div>
                        <div class="pwa-mini-step-hint">Tap Install / APK</div>
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

        // Apply Update button clicked
        const applyUpdateBtn = target.closest('#pwaApplyUpdateBtn');
        if (applyUpdateBtn) {
            e.preventDefault();
            const btnText = document.getElementById('pwaApplyUpdateBtnText');
            if (btnText) btnText.textContent = 'Updating...';
            const spinIcon = applyUpdateBtn.querySelector('.pwa-update-spin-icon');
            if (spinIcon) spinIcon.style.display = 'inline-block';
            applyUpdateBtn.style.opacity = '0.85';
            applyUpdateBtn.style.pointerEvents = 'none';

            const targetVer = latestDetectedVersion || getLatestVersion();
            localStorage.setItem('pwa_installed_version', targetVer);
            localStorage.setItem('pwa_app_version', targetVer);
            localStorage.removeItem('pwa_update_dismissed_ver');
            localStorage.removeItem('pwa_update_prompted_ver');
            sessionStorage.removeItem('pwa_update_dismissed_ver');
            sessionStorage.setItem('pwa_updating', 'true');

            // Hide popup immediately
            const popup = document.getElementById('pwaSystemUpdatePopup');
            if (popup) popup.style.display = 'none';

            if (swRegistration && swRegistration.waiting) {
                swRegistration.waiting.postMessage({ action: 'skipWaiting', type: 'SKIP_WAITING' });
            }
            if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({ action: 'clearCache', type: 'CLEAR_CACHE' });
            }

            if ('caches' in window) {
                caches.keys().then(keys => Promise.all(keys.map(k => caches.delete(k)))).then(() => {
                    setTimeout(() => window.location.reload(true), 400);
                }).catch(() => {
                    window.location.reload(true);
                });
            } else {
                setTimeout(() => window.location.reload(true), 300);
            }
            return;
        }

        // Dismiss Update popup (Dismiss for current session)
        const dismissUpdateBtn = target.closest('#pwaDismissUpdatePopupBtn') || target.closest('#pwaLaterUpdateBtn');
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
        if (installBanner) installBanner.style.display = 'none';
        if (iosModal) iosModal.style.display = 'none';
        localStorage.setItem('pwa_prompt_dismissed', 'true');
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
