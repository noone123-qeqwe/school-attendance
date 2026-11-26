@extends('layouts.app')
@section('focused-attendance-verification', 'true')
@section('content')
<style>
    body.attendance-verification-page :is(#sidebar, #sidebarOverlay, #topHeader, #mobileBottomNav) { display: none !important; }
    body.attendance-verification-page .main-wrapper { margin: 0 !important; width: 100% !important; }
    body.attendance-verification-page #mainContent.main-content { margin: 0 !important; padding: 0 !important; }
    body.attendance-verification-page #mainContent.main-content > .page-enter { padding: 0 !important; }

    .verify-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: 100dvh;
        padding: max(16px, env(safe-area-inset-top)) max(16px, env(safe-area-inset-right)) max(28px, env(safe-area-inset-bottom)) max(16px, env(safe-area-inset-left));
        background:
            radial-gradient(ellipse 65% 45% at 88% -6%, rgba(122, 26, 26, 0.22) 0%, transparent 66%),
            radial-gradient(ellipse 48% 42% at 10% 12%, rgba(212, 175, 55, 0.08) 0%, transparent 64%),
            #141010;
        color: #FAF8F5;
        font-family: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif;
    }

    .verify-navigation {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px 16px;
        width: min(100%, 560px);
        margin-bottom: 14px;
    }

    .verify-navigation h1 {
        margin: 0;
        font-size: clamp(1.15rem, 3.6vw, 1.45rem);
        color: #FAF8F5;
        font-weight: 800;
        letter-spacing: -0.025em;
    }

    .verify-navigation a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 44px;
        padding: 8px 14px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(255, 255, 255, 0.05);
        color: #FAF8F5;
        font-size: 0.86rem;
        font-weight: 700;
        text-decoration: none;
        transition: background 0.16s ease, border-color 0.16s ease;
    }

    .verify-navigation a:hover {
        background: rgba(122, 26, 26, 0.24);
        border-color: rgba(212, 175, 55, 0.34);
        color: #ffffff;
    }

    .verify-navigation a:focus-visible {
        outline: 2px solid #D4AF37;
        outline-offset: 2px;
    }

    .verify-card {
        max-width: 560px;
        width: 100%;
        border-radius: 24px;
        padding: clamp(18px, 4vw, 28px);
        background:
            radial-gradient(ellipse 68% 75% at 100% 0%, rgba(122, 26, 26, 0.16) 0%, transparent 66%),
            linear-gradient(180deg, rgba(30, 21, 21, 0.94) 0%, rgba(22, 15, 15, 0.98) 100%);
        border: 1px solid rgba(212, 175, 55, 0.2);
        box-shadow: 0 18px 44px rgba(0, 0, 0, 0.44), inset 0 1px 0 rgba(255, 255, 255, 0.08);
        text-align: center;
    }

    .subject-pill {
        background: linear-gradient(135deg, rgba(46, 28, 28, 0.65), rgba(24, 17, 17, 0.9));
        border: 1px solid rgba(212, 175, 55, 0.18);
        border-radius: 16px;
        padding: 14px 16px;
        margin-bottom: 20px;
        text-align: left;
    }

    .subject-pill-label {
        font-size: 0.68rem;
        font-weight: 800;
        color: #D4AF37;
        text-transform: uppercase;
        letter-spacing: 0.09em;
    }

    .subject-pill-name {
        font-size: 1.02rem;
        font-weight: 800;
        color: #FAF8F5;
        margin-top: 3px;
    }

    .subject-pill-meta {
        font-size: 0.8rem;
        color: #D6CCC6;
        margin-top: 4px;
        font-variant-numeric: tabular-nums;
    }

    /* Step progress tracker */
    .step-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 24px;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
    }

    .step-caption {
        font-size: 0.68rem;
        font-weight: 700;
        color: #baa3ab;
        letter-spacing: 0.03em;
    }

    .sdot {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        transition: all 0.3s ease;
    }

    .sdot.done {
        background: rgba(52, 211, 153, 0.2);
        border: 1.5px solid #34d399;
        color: #6ee7b7;
    }

    .sdot.active {
        background: linear-gradient(135deg, #ffffff 0%, #eae0e3 60%, #d7c7cc 100%);
        color: #800000;
        box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.26);
    }

    .sdot.pending {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.16);
        color: #baa3ab;
    }

    .sline {
        flex: 1;
        height: 2px;
        background: rgba(255, 255, 255, 0.14);
        max-width: 52px;
        margin-bottom: 18px;
        transition: background 0.35s ease;
    }

    .sline.done {
        background: linear-gradient(90deg, #34d399, #6ee7b7);
    }

    /* Setup Required Card */
    .verify-setup-card {
        background: linear-gradient(155deg, rgba(122, 26, 26, 0.24), rgba(28, 18, 18, 0.94));
        border: 1px solid rgba(212, 175, 55, 0.32);
        border-radius: 18px;
        padding: 22px 18px;
        margin-bottom: 18px;
        text-align: center;
    }

    .verify-setup-icon {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        background: rgba(122, 26, 26, 0.28);
        border: 1px solid rgba(255, 255, 255, 0.26);
        color: #ffffff;
        display: grid;
        place-items: center;
        font-size: 1.7rem;
        margin: 0 auto 12px;
    }

    /* Biometric Method Selector */
    .method-selector {
        display: flex;
        background: rgba(28, 18, 18, 0.72);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 14px;
        padding: 4px;
        margin-bottom: 20px;
        gap: 6px;
    }

    .method-btn {
        flex: 1;
        min-height: 44px;
        padding: 10px 14px;
        border: 1px solid transparent;
        border-radius: 11px;
        font-size: 0.84rem;
        font-weight: 700;
        color: #baa3ab;
        background: transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
    }

    .method-btn:hover {
        color: #ffffff;
        background: rgba(122, 26, 26, 0.22);
    }

    .method-btn.active {
        background: linear-gradient(135deg, rgba(122, 26, 26, 0.28), rgba(42, 27, 27, 0.88));
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.32);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35);
    }

    /* Face Verification Disclosure */
    .face-match-disclosure { margin-bottom: 14px; text-align: left; }

    .face-match-note {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin: 0;
        padding: 12px 14px;
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 14px;
        background: rgba(122, 26, 26, 0.22);
        color: #FAF8F5;
        font-size: 0.84rem;
        line-height: 1.48;
    }

    .face-match-note > i { flex: none; margin-top: 2px; color: #D4AF37; }

    .face-match-details {
        margin-top: 8px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(28, 18, 18, 0.5);
        border-radius: 12px;
        text-align: left;
    }

    .face-match-details summary {
        display: flex;
        align-items: center;
        min-height: 44px;
        padding: 8px 13px;
        color: #D6CCC6;
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
    }

    .face-match-details p {
        margin: 0;
        padding: 0 13px 13px;
        color: #baa3ab;
        font-size: 0.78rem;
        line-height: 1.5;
    }

    .face-match-details a { color: #ffffff; font-weight: 700; }

    /* Camera Viewport & Optical HUD */
    .camera-viewport-card {
        position: relative;
        width: 100%;
        height: clamp(270px, 43svh, 440px);
        border-radius: 20px;
        overflow: hidden;
        background: #070305;
        border: 1px solid rgba(255, 255, 255, 0.22);
        box-shadow: 0 16px 42px rgba(0, 0, 0, 0.52);
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .camera-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
    }

    .face-reticle-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .face-oval-guide {
        position: relative;
        width: clamp(154px, 38vw, 196px);
        height: clamp(196px, 31svh, 266px);
        border: 2.5px dashed rgba(255, 255, 255, 0.82);
        border-radius: 50% / 55%;
        box-shadow: 0 0 0 9999px rgba(10, 3, 6, 0.54);
        transition: all 0.28s ease;
    }

    .face-oval-guide.matched {
        border-color: #34d399 !important;
        border-style: solid !important;
        box-shadow: 0 0 0 9999px rgba(10, 3, 6, 0.44), 0 0 28px rgba(52, 211, 153, 0.72) !important;
    }

    .face-oval-guide.mismatch {
        border-color: #ff6b7a !important;
        border-style: solid !important;
        box-shadow: 0 0 0 9999px rgba(10, 3, 6, 0.52), 0 0 28px rgba(255, 107, 122, 0.72) !important;
    }

    .face-laser-line {
        position: absolute;
        left: 10%;
        right: 10%;
        height: 2.5px;
        background: linear-gradient(90deg, transparent, #D4AF37, #ffffff, #D4AF37, transparent);
        box-shadow: 0 0 14px rgba(212, 175, 55, 0.85);
        border-radius: 999px;
        animation: laserScan 2.1s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    }

    @keyframes laserScan {
        0% { top: 8%; opacity: 0; }
        15% { opacity: 1; }
        85% { opacity: 1; }
        100% { top: 92%; opacity: 0; }
    }

    .face-corner {
        position: absolute;
        width: 16px;
        height: 16px;
        border-color: #FAF8F5;
        border-style: solid;
    }

    .face-corner.tl { top: 10px; left: 16px; border-width: 2.5px 0 0 2.5px; border-top-left-radius: 8px; }
    .face-corner.tr { top: 10px; right: 16px; border-width: 2.5px 2.5px 0 0; border-top-right-radius: 8px; }
    .face-corner.bl { bottom: 10px; left: 16px; border-width: 0 0 2.5px 2.5px; border-bottom-left-radius: 8px; }
    .face-corner.br { bottom: 10px; right: 16px; border-width: 0 2.5px 2.5px 0; border-bottom-right-radius: 8px; }

    .camera-status-pill {
        position: absolute;
        bottom: 12px;
        left: 50%;
        transform: translateX(-50%);
        width: max-content;
        max-width: calc(100% - 24px);
        justify-content: center;
        text-align: center;
        background: rgba(28, 18, 18, 0.84);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.24);
        color: #FAF8F5;
        border-radius: 999px;
        padding: 6px 14px;
        font-size: 0.76rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 7px;
        z-index: 5;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #34d399;
        flex: 0 0 8px;
    }

    .status-dot.searching { background: #D4AF37; }
    .status-dot.pulsing {
        box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.7);
        animation: pulseGreen 1.5s infinite;
    }
    .status-dot.err {
        background: #ff6b7a;
        box-shadow: 0 0 10px #ff6b7a;
    }

    @keyframes pulseGreen {
        0% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(52, 211, 153, 0); }
        100% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0); }
    }

    /* Buttons */
    .btn-verify-face {
        width: 100%;
        min-height: 50px;
        padding: 13px 18px;
        background: linear-gradient(135deg, #ffffff 0%, #eae0e3 55%, #d7c7cc 100%);
        color: #800000;
        font-weight: 800;
        font-size: 0.94rem;
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 10px 26px rgba(0, 0, 0, 0.42), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        transition: transform 0.16s ease, filter 0.16s ease;
    }

    .btn-verify-face:hover { transform: translateY(-1px); filter: brightness(1.04); color: #24050b; }
    .btn-verify-face:disabled { opacity: 0.58; cursor: not-allowed; transform: none; }

    .btn-retry-face {
        width: 100%;
        min-height: 44px;
        padding: 11px 16px;
        background: rgba(255, 255, 255, 0.05);
        color: #FAF8F5;
        font-weight: 700;
        font-size: 0.86rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: background 0.16s ease, border-color 0.16s ease;
    }

    .btn-retry-face:hover {
        background: rgba(122, 26, 26, 0.24);
        border-color: rgba(255, 255, 255, 0.36);
        color: #ffffff;
    }

    .face-capture-summary {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 8px;
        font-size: 0.82rem;
        font-weight: 700;
        color: #FAF8F5;
        font-variant-numeric: tabular-nums;
    }

    #faceCaptureProgress {
        width: 100%;
        height: 8px;
        accent-color: #D4AF37;
        border-radius: 999px;
    }

    #facePoseInstruction {
        margin: 10px 0 12px;
        font-weight: 700;
        color: #FAF8F5;
    }

    #faceCaptureConfirmation {
        min-height: 1.4em;
        font-size: 0.82rem;
        color: #D6CCC6;
    }

    #faceScanArea [hidden] { display: none !important; }

    /* Fingerprint & Status Medallion */
    .v-icon {
        position: relative;
        width: 88px;
        height: 88px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 18px;
        font-size: 2.15rem;
        transition: all 0.35s ease;
        background: rgba(122, 26, 26, 0.32);
        border: 1.5px solid rgba(255, 255, 255, 0.28);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
    }

    .v-title {
        font-size: 1.2rem;
        font-weight: 800;
        color: #FAF8F5;
        margin-bottom: 6px;
        letter-spacing: -0.02em;
    }

    .v-sub {
        font-size: 0.88rem;
        color: #D6CCC6;
        line-height: 1.5;
        margin-bottom: 0;
    }

    .v-msg {
        border-radius: 12px;
        padding: 12px 15px;
        font-size: 0.84rem;
        margin-top: 16px;
        display: none;
        text-align: left;
    }

    .v-ok   { background: rgba(52, 211, 153, 0.14); border: 1px solid rgba(52, 211, 153, 0.34); color: #6ee7b7; }
    .v-err  { background: rgba(255, 107, 122, 0.14); border: 1px solid rgba(255, 107, 122, 0.34); color: #ffb3bc; }
    .v-info { background: rgba(125, 211, 252, 0.14); border: 1px solid rgba(125, 211, 252, 0.34); color: #bae6fd; }

    .retry-btn {
        width: 100%;
        min-height: 50px;
        padding: 13px 18px;
        margin-top: 16px;
        background: linear-gradient(135deg, #ffffff 0%, #eae0e3 55%, #d7c7cc 100%);
        color: #800000;
        font-weight: 800;
        font-size: 0.92rem;
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 14px;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: transform 0.16s ease, filter 0.16s ease;
    }

    .retry-btn:hover { transform: translateY(-1px); filter: brightness(1.04); }

    .spin {
        display: inline-block;
        width: 22px;
        height: 22px;
        border: 3px solid rgba(255, 255, 255, 0.25);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── OUTSIDE RANGE POPUP DIALOG ── */
    .outside-range-popup-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(10, 3, 6, 0.88);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        z-index: 100002;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        animation: fadeIn 0.25s ease-out forwards;
    }

    .outside-range-popup-card {
        background:
            radial-gradient(ellipse 65% 75% at 100% 0%, rgba(122, 26, 26, 0.24) 0%, transparent 65%),
            linear-gradient(180deg, #210a12 0%, #13050a 100%);
        border: 1.5px solid rgba(255, 107, 122, 0.44);
        border-radius: 24px;
        max-width: 440px;
        width: 100%;
        padding: 28px 24px 24px;
        color: #FAF8F5;
        text-align: center;
        position: relative;
        box-shadow: 0 28px 68px rgba(0, 0, 0, 0.72);
        animation: popIn 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes popIn {
        0% { opacity: 0; transform: scale(0.92) translateY(16px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
    }
    @keyframes fadeIn {
        0% { opacity: 0; }
        100% { opacity: 1; }
    }

    .outside-range-close-btn {
        position: absolute;
        top: 14px;
        right: 14px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.16);
        color: #FAF8F5;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.18s ease, color 0.18s ease;
    }

    .outside-range-close-btn:hover { background: rgba(255, 107, 122, 0.24); color: #ffffff; }

    .outside-range-icon-pulse {
        width: 76px;
        height: 76px;
        border-radius: 50%;
        background: rgba(255, 107, 122, 0.14);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        animation: pulseGps 2s infinite;
    }

    @keyframes pulseGps {
        0% { box-shadow: 0 0 0 0 rgba(255, 107, 122, 0.55); }
        70% { box-shadow: 0 0 0 16px rgba(255, 107, 122, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 107, 122, 0); }
    }

    .outside-range-icon-inner {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ff6b7a, #800000);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 1.65rem;
    }

    .outside-range-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 999px;
        background: rgba(255, 107, 122, 0.16);
        border: 1px solid rgba(255, 107, 122, 0.36);
        color: #ffb3bc;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .outside-range-headline { font-size: 1.35rem; font-weight: 800; color: #ffffff; margin-bottom: 8px; }
    .outside-range-desc { font-size: 0.88rem; color: #D6CCC6; line-height: 1.5; margin-bottom: 18px; }

    .outside-range-metrics-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(28, 18, 18, 0.62);
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 16px;
        padding: 14px 18px;
        margin-bottom: 16px;
    }

    .outside-range-metric-col { flex: 1; text-align: center; }
    .outside-range-metric-col .metric-label { font-size: 0.72rem; font-weight: 700; color: #baa3ab; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; }
    .outside-range-metric-col .metric-val { font-size: 1.45rem; font-weight: 800; font-family: 'JetBrains Mono', ui-monospace, monospace; font-variant-numeric: tabular-nums; }
    .outside-range-metric-col.detected .metric-val { color: #ff6b7a; }
    .outside-range-metric-col.allowed .metric-val { color: #fbbf24; }
    .outside-range-metric-col .metric-sub { font-size: 0.72rem; font-weight: 600; }
    .outside-range-divider { width: 1px; height: 48px; background: rgba(255, 255, 255, 0.14); margin: 0 12px; }

    .outside-range-tip-box {
        display: flex;
        align-items: flex-start;
        background: rgba(122, 26, 26, 0.24);
        border: 1px solid rgba(212, 175, 55, 0.28);
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 0.8rem;
        color: #FAF8F5;
        text-align: left;
        margin-bottom: 20px;
        line-height: 1.48;
    }

    .outside-range-actions { display: flex; flex-direction: column; gap: 10px; }

    .outside-range-btn-primary {
        min-height: 48px;
        background: linear-gradient(135deg, #ffffff 0%, #eae0e3 55%, #d7c7cc 100%);
        color: #800000;
        font-weight: 800;
        font-size: 0.92rem;
        padding: 12px 20px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.6);
        cursor: pointer;
        transition: transform 0.16s ease, filter 0.16s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .outside-range-btn-primary:hover { transform: translateY(-1px); filter: brightness(1.04); }

    .outside-range-btn-secondary {
        min-height: 46px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.22);
        color: #FAF8F5;
        font-weight: 700;
        font-size: 0.88rem;
        padding: 10px 18px;
        border-radius: 14px;
        cursor: pointer;
        transition: background 0.16s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .outside-range-btn-secondary:hover { background: rgba(122, 26, 26, 0.24); color: #ffffff; }

    .outside-range-btn-text {
        background: transparent;
        border: none;
        color: #baa3ab;
        font-size: 0.84rem;
        font-weight: 700;
        min-height: 40px;
        padding: 6px;
        cursor: pointer;
    }

    .outside-range-btn-text:hover { color: #ffffff; }

    @media (max-width: 600px) {
        .verify-wrapper { padding: max(12px, env(safe-area-inset-top)) max(12px, env(safe-area-inset-right)) max(18px, env(safe-area-inset-bottom)) max(12px, env(safe-area-inset-left)); }
        .verify-card { border-radius: 20px; }
        .camera-viewport-card { height: clamp(250px, 41svh, 380px); border-radius: 16px; }
        .face-oval-guide { width: clamp(145px, 44vw, 180px); height: clamp(185px, 30svh, 235px); }
        .camera-status-pill { font-size: 0.74rem; line-height: 1.25; padding: 7px 11px; }
        .face-match-note { padding: 10px 11px; font-size: 0.79rem; }
    }

    @media (orientation: landscape) and (max-height: 600px) {
        .camera-viewport-card { height: clamp(210px, 58svh, 330px); }
        .face-oval-guide { height: min(72%, 225px); }
    }

    @media (prefers-reduced-motion: reduce) {
        .face-laser-line, .status-dot.pulsing, .spin, .outside-range-icon-pulse { animation: none !important; }
        .btn-verify-face, .btn-retry-face, .outside-range-btn-primary { transition: none !important; }
    }
</style>

<div class="verify-wrapper">
    <header class="verify-navigation">
        <a href="{{ route('mobile.scan') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to scanner</a>
        <h1>Verify attendance</h1>
    </header>
    <div class="verify-card">

        {{-- Subject info --}}
        <div class="subject-pill">
            <div class="subject-pill-label">Clocking in for</div>
            <div class="subject-pill-name">{{ $subject->name }}</div>
            <div class="subject-pill-meta">
                <i class="bi bi-clock me-1"></i>
                @if($subject->start_time)
                    {{ \Carbon\Carbon::parse($subject->start_time)->format('h:i A') }}
                @endif
                &nbsp;&middot;&nbsp; {{ $subject->code }}
            </div>
        </div>

        {{-- Step indicators --}}
        <div class="step-row" aria-label="Verification progress">
            <div class="step-item">
                <div class="sdot done" id="dot1"><i class="bi bi-check2" aria-hidden="true"></i></div>
                <span class="step-caption">QR / PIN</span>
            </div>
            <div class="sline done" id="line1"></div>
            <div class="step-item">
                <div class="sdot active" id="dot2">2</div>
                <span class="step-caption">Location</span>
            </div>
            <div class="sline" id="line2"></div>
            <div class="step-item">
                <div class="sdot pending" id="dot3">3</div>
                <span class="step-caption">Biometric</span>
            </div>
        </div>

        @if(isset($status) && $status === 'setup')
        <div class="verify-setup-card">
            <div class="verify-setup-icon" aria-hidden="true">
                <i class="bi bi-person-bounding-box"></i>
            </div>
            <div style="font-size:1.12rem;font-weight:800;color:#FAF8F5;margin-bottom:6px;">Biometric Verification Required</div>
            <p style="font-size:0.86rem;color:#D6CCC6;line-height:1.5;margin-bottom:18px;">
                {{ $message ?? 'You must have a registered face profile or device passkey on this account before clocking in with QR attendance.' }}
            </p>
            <div class="d-flex flex-column gap-2">
                @if(app(\App\Services\FeatureFlagService::class)->enabled('feature.face_verification'))
                <a href="{{ route('settings.biometrics.face') }}" class="btn-verify-face" style="text-decoration:none;">
                    <i class="bi bi-camera" aria-hidden="true"></i> Register Your Face
                </a>
                @endif
                <a href="{{ route('settings.biometrics.fingerprint') }}" class="btn-retry-face" style="text-decoration:none;">
                    <i class="bi bi-fingerprint" aria-hidden="true"></i> Or Register Fingerprint
                </a>
            </div>
            <p class="small mt-3 mb-0" style="color:#baa3ab;">Ask your teacher for help if you can’t use either method.</p>
        </div>
        @endif

        {{-- Method Selector (when both face and fingerprint available) --}}
        @if(!empty($hasFaceMethod) && !empty($hasFingerprint))
        <div class="method-selector" id="methodSelector" style="display: none;">
            <button type="button" class="method-btn @if($defaultMethod === 'face') active @endif" id="btnMethodFace" onclick="switchMethod('face')">
                <i class="bi bi-person-bounding-box"></i> Live Face Match
            </button>
            <button type="button" class="method-btn @if($defaultMethod === 'fingerprint') active @endif" id="btnMethodFp" onclick="switchMethod('fingerprint')">
                <i class="bi bi-fingerprint"></i> Fingerprint
            </button>
        </div>
        @endif

        @if(!empty($hasFaceMethod))
        <div id="faceScanArea" style="display: none;">
            <div id="faceAttendanceNotice" class="face-match-disclosure">
                <p class="face-match-note" role="note">
                    <i class="bi bi-camera" aria-hidden="true"></i>
                    <span>The camera captures five views automatically. Follow the directions to verify liveness and match your registered face.</span>
                </p>
                <details class="face-match-details">
                    <summary>How we use your camera</summary>
                    <p>The school server temporarily processes still images to check liveness and compare them with your encrypted face profile. It does not save a video or keep the check-in images in your attendance record. You can use a registered device or ask your teacher for help instead. <a href="{{ route('privacy') }}">Privacy notice</a></p>
                    <form method="POST" action="{{ route('student.face-attendance-consent.withdraw') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-retry-face">Withdraw camera attendance consent</button>
                    </form>
                </details>
                <button type="button" class="btn-verify-face mt-2" id="btnStartFace" onclick="startFaceScan()">
                    <i class="bi bi-camera" aria-hidden="true"></i> Start camera
                </button>
            </div>
            <div id="faceCapturePanel" hidden>
            <div class="face-capture-summary">
                <label for="faceCaptureProgress" id="faceProgressLabel">0/5 angles captured</label>
                <span id="faceLivenessStatus" role="status">Liveness check pending</span>
            </div>
            <progress id="faceCaptureProgress" value="0" max="5"></progress>
            <p id="facePoseInstruction" aria-live="polite"></p>
            <div class="camera-viewport-card">
                <video id="faceCameraVideo" class="camera-video" autoplay playsinline muted></video>
                <canvas id="faceCaptureCanvas" style="display: none;"></canvas>

                <div class="face-reticle-overlay">
                    <div class="face-oval-guide" id="faceOvalGuide">
                        <div class="face-laser-line" id="faceLaserLine"></div>
                        <div class="face-corner tl"></div>
                        <div class="face-corner tr"></div>
                        <div class="face-corner bl"></div>
                        <div class="face-corner br"></div>
                        <div id="faceSuccessCheck" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;font-size:3.5rem;color:#10b981;animation:popIn 0.3s ease;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                </div>

                <div class="camera-status-pill" id="cameraStatusPill">
                    <span class="status-dot pulsing" id="cameraStatusDot" aria-hidden="true"></span>
                    <span id="cameraStatusText" aria-live="polite" aria-atomic="true">Align face within oval</span>
                </div>
            </div>

            <p id="faceCaptureConfirmation" role="status"></p>
            @include('partials.camera-light-controls', ['cameraLightId' => 'faceMatchLight'])
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <button type="button" class="btn-retry-face" id="btnCancelFace" onclick="showFaceIntro()" hidden>Close camera</button>
                <button type="button" class="btn-retry-face" id="btnRetryFace" onclick="startFaceScan()" hidden>Try camera again</button>
            </div>
            <p class="text-muted small mb-0 mt-2">Ask your teacher for help if you can’t use the camera.</p>
        </div>
        @endif

        {{-- Dynamic content area --}}
        <div id="vIcon"  class="v-icon" style="background:rgba(96, 165, 250, 0.16); @if(isset($status) && $status === 'setup') display:none; @endif">
            <i class="bi bi-geo-alt-fill" style="color:#60a5fa;"></i>
        </div>
        <div class="v-title" id="vTitle" style="@if(isset($status) && $status === 'setup') display:none; @endif">Checking Location</div>
        <div class="v-sub"   id="vSub" style="@if(isset($status) && $status === 'setup') display:none; @endif">Verifying you are inside the classroom...</div>
        <div class="v-msg"   id="vMsg"></div>

        {{-- Retry fingerprint button (shown only on fp error) --}}
        <button class="retry-btn" id="retryFpBtn" onclick="doFingerprint()">
            <i class="bi bi-fingerprint"></i> Try Fingerprint Again
        </button>

        {{-- Hidden form --}}
        <form id="attendanceForm" action="{{ route('qr.confirm') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="latitude" id="latInput">
            <input type="hidden" name="longitude" id="lngInput">
            <input type="hidden" name="accuracy" id="accuracyInput">
            <input type="hidden" name="credential" id="credentialInput">
            <input type="hidden" name="device_key" id="deviceKeyInput">
            <input type="hidden" name="device_fingerprint" id="deviceFingerprintInput">
        </form>
    </div>
</div>

<!-- OUTSIDE RANGE ALERT POPUP MODAL -->
<div id="outsideRangePopupModal" class="outside-range-popup-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="outsideRangeTitle">
    <div class="outside-range-popup-card">
        <!-- Close icon button -->
        <button type="button" class="outside-range-close-btn" onclick="closeOutsideRangePopup()" aria-label="Close dialog">
            <i class="bi bi-x-lg"></i>
        </button>

        <!-- Animated Warning Icon -->
        <div class="outside-range-icon-pulse">
            <div class="outside-range-icon-inner">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
        </div>

        <!-- Headline & Subtitle -->
        <div class="outside-range-badge">
            <i class="bi bi-shield-exclamation me-1"></i> GEOFENCE BOUNDARY EXCEEDED
        </div>
        <h3 id="outsideRangeTitle" class="outside-range-headline">Outside Classroom Range</h3>
        <p id="outsideRangeMessage" class="outside-range-desc">
            You are too far from the classroom to record attendance. You must be physically inside the room during class.
        </p>

        <!-- Range Metrics Display -->
        <div class="outside-range-metrics-box">
            <div class="outside-range-metric-col detected">
                <div class="metric-label"><i class="bi bi-person-walking me-1"></i> Your Distance</div>
                <div class="metric-val" id="outsideRangeDetectedDist">--m</div>
                <div class="metric-sub text-danger">Outside Boundary</div>
            </div>
            <div class="outside-range-divider"></div>
            <div class="outside-range-metric-col allowed">
                <div class="metric-label"><i class="bi bi-broadcast me-1"></i> Allowed Radius</div>
                <div class="metric-val" id="outsideRangeAllowedRadius">50m</div>
                <div class="metric-sub text-warning">Maximum Limit</div>
            </div>
        </div>

        <!-- Proximity Guidance -->
        <div class="outside-range-tip-box">
            <i class="bi bi-info-circle-fill text-warning me-2" style="font-size: 1.1rem; flex-shrink: 0;"></i>
            <span>Please step into the classroom or move closer to the instructor's display and try verifying again.</span>
        </div>

        <!-- Action Buttons -->
        <div class="outside-range-actions">
            <button type="button" class="outside-range-btn-primary" onclick="retryScanFromOutsidePopup()">
                <i class="bi bi-arrow-repeat me-1"></i> Retry Location Check
            </button>
            <a href="{{ route('home') }}?open_code=1" class="outside-range-btn-secondary" style="text-decoration:none;">
                <i class="bi bi-key-fill me-1"></i> Enter 6-Digit Code Instead
            </a>
            <button type="button" class="outside-range-btn-text" onclick="closeOutsideRangePopup()">
                Dismiss
            </button>
        </div>
    </div>
</div>

<script src="{{ asset('js/device-passkey-capabilities.js') }}?v={{ filemtime(public_path('js/device-passkey-capabilities.js')) }}" @cspNonce></script>
<script @cspNonce>
var STUDENT_NUMBER = '{{ addslashes(Auth::user()->student_number) }}';
var QR_TOKEN = '{{ addslashes($token) }}';
var CSRF = '{{ csrf_token() }}';
var CLASSROOM_LAT = {{ isset($classroomLat) && $classroomLat !== null ? (float) $classroomLat : 'null' }};
var CLASSROOM_LNG = {{ isset($classroomLng) && $classroomLng !== null ? (float) $classroomLng : 'null' }};
var RADIUS_METERS = {{ isset($radiusMeters) && $radiusMeters !== null ? (int) $radiusMeters : (int) \App\Models\Setting::get('gps_radius', 50) }};
var GPS_VERIFICATION_ENABLED = {{ app(\App\Services\FeatureFlagService::class)->enabled('feature.gps_verification') ? 'true' : 'false' }};
var FACE_ADDITIONAL_FACTOR = @json($faceAdditionalFactor ?? 'none');

var fingerprintController = null;
window.addEventListener('pagehide', () => fingerprintController?.abort());
var fingerprintInProgress = false; // Add guard against multiple simultaneous calls
var HAS_FACE_METHOD = {{ !empty($hasFaceMethod) ? 'true' : 'false' }};
var HAS_FINGERPRINT = {{ !empty($hasFingerprint) ? 'true' : 'false' }};
var ACTIVE_METHOD = '{{ $defaultMethod ?? (!empty($hasFaceMethod) ? "face" : "fingerprint") }}';
var FACE_GUIDANCE_URL = @json(!empty($hasFaceMethod) ? Vite::asset('resources/js/attendance-face-guidance.js') : null);
var activeStream = null;
var faceLighting = window.CameraLighting?.mount(document.getElementById('faceMatchLight'));
var faceAttempt = null;
var faceSubmitting = false;

function normalizeCoordinates(lat, lng) {
    if (window.GpsSampler) return window.GpsSampler.normalizeCoordinates(lat, lng);
    let latitude = Number(lat);
    let longitude = Number(lng);
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;

    if (Math.abs(latitude) > 90 && Math.abs(latitude) <= 180 && Math.abs(longitude) <= 90) {
        const t = latitude;
        latitude = longitude;
        longitude = t;
    }
    return Math.abs(latitude) <= 90 && Math.abs(longitude) <= 180 ? [latitude, longitude] : null;
}

function calculateDistance(lat1, lon1, lat2, lon2) {
    if (window.GpsSampler) return window.GpsSampler.distanceMeters(lat1, lon1, lat2, lon2);
    var c1 = normalizeCoordinates(lat1, lon1);
    var c2 = normalizeCoordinates(lat2, lon2);
    if (!c1 || !c2) return Infinity;
    lat1 = c1[0]; lon1 = c1[1];
    lat2 = c2[0]; lon2 = c2[1];

    var R = 6371000; // meters
    var dLat = (lat2 - lat1) * Math.PI / 180;
    var dLon = (lon2 - lon1) * Math.PI / 180;
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
    a = Math.min(1.0, Math.max(0.0, a));
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function setIcon(bg, iconClass, iconColor) {
    var darkSurfaceMap = {
        '#ecfdf5': { bg: 'rgba(34, 197, 94, 0.16)', color: '#4ade80' },
        '#f0fdf4': { bg: 'rgba(34, 197, 94, 0.16)', color: '#4ade80' },
        '#fef2f2': { bg: 'rgba(239, 68, 68, 0.16)', color: '#f87171' },
        '#fff5f5': { bg: 'rgba(239, 68, 68, 0.16)', color: '#f87171' },
        '#fef3c7': { bg: 'rgba(245, 158, 11, 0.16)', color: '#fbbf24' },
        '#eff6ff': { bg: 'rgba(59, 130, 246, 0.16)', color: '#60a5fa' }
    };
    var mapped = darkSurfaceMap[(bg || '').toLowerCase()];
    var el = document.getElementById('vIcon');
    el.style.background = mapped ? mapped.bg : bg;
    el.replaceChildren();
    var iconEl = document.createElement('i');
    iconEl.className = iconClass;
    iconEl.style.color = mapped ? mapped.color : iconColor;
    iconEl.style.fontSize = '2rem';
    el.appendChild(iconEl);
}
function setSpinner() {
    var el = document.getElementById('vIcon');
    el.style.background = 'rgba(122, 26, 26, 0.24)';
    el.replaceChildren();
    var spin = document.createElement('div');
    spin.style.cssText = 'width:36px;height:36px;border:3.5px solid rgba(255, 255, 255,0.22);border-top-color:#ffffff;border-radius:50%;animation:spin .7s linear infinite;';
    el.appendChild(spin);
}
function showMsg(type, text) {
    var el = document.getElementById('vMsg');
    el.className = 'v-msg ' + (type === 'ok' ? 'v-ok' : type === 'err' ? 'v-err' : 'v-info');
    el.innerHTML = text;
    el.style.display = 'block';
}
function hideMsg() { document.getElementById('vMsg').style.display = 'none'; }
function setStep(step) {
    if (step >= 3) {
        document.getElementById('dot2').className = 'sdot done';
        document.getElementById('dot2').innerHTML = '<i class="bi bi-check2"></i>';
        document.getElementById('line2').className = 'sline done';
        document.getElementById('dot3').className = step >= 4 ? 'sdot done' : 'sdot active';
        if (step >= 4) document.getElementById('dot3').innerHTML = '<i class="bi bi-check2"></i>';
        else document.getElementById('dot3').textContent = '3';
    }
}

let geoRetryDowngraded = false;
let locationWatcher = null;
let locationTimeoutId = null;
let fastFallbackTimer = null;
let bestLocation = null;
let bestAccuracy = Infinity;
let firstLocationReceived = false;

const GPS_TIMEOUT_MS = 15000;            // Time to obtain a fresh, reliable device fix
const GPS_FAST_FALLBACK_MS = 3000;       // Wait briefly for a confirming multi-fix lock
const GPS_QUICK_ACCEPT = 12;
const GPS_MAX_FAST_ACCEPT = 50;
const GPS_MAX_ACCEPTABLE_ACCURACY = 50;

function isNearBoundary(distance, accuracy) {
    return distance + accuracy > RADIUS_METERS && distance - accuracy <= RADIUS_METERS;
}

function validFreshPosition(pos) {
    if (window.GpsSampler) {
        return window.GpsSampler.isValidSample(pos, { maxAgeMs: 10000, maxFutureMs: 5000 });
    }
    if (!pos || !pos.coords) return false;
    var c = pos.coords;
    var age = Date.now() - pos.timestamp;
    return age >= -5000 && age <= 10000 && Number.isFinite(c.latitude) &&
        Math.abs(c.latitude) <= 90 && Number.isFinite(c.longitude) &&
        Math.abs(c.longitude) <= 180 && Number.isFinite(c.accuracy) && c.accuracy > 0;
}

// Wait briefly for confirming independent fixes and fuse via inverse-variance centroid.
async function getFreshStablePosition() {
    if (!navigator.geolocation || !window.GpsSampler) return null;
    var result = await window.GpsSampler.capturePosition({
        timeoutMs: 12000,
        settleMs: 2800,
        targetAccuracy: GPS_QUICK_ACCEPT,
        maxAcceptableAccuracy: GPS_MAX_ACCEPTABLE_ACCURACY,
        minClusterSamples: 2,
        allowSingleFallback: false,
        maxSamples: 5,
        requireClusterIfDisagreeing: true,
        maxSpreadMeters: 60,
    });
    return result.ok && result.fix ? result.fix.toGeolocationPosition() : null;
}

var PAGE_STATUS = '{{ $status ?? "ready" }}';
window.addEventListener('load', function() { 
    if (PAGE_STATUS !== 'setup') {
        startGPS(); 
    }
});

function startGPS() {
    if (!GPS_VERIFICATION_ENABLED) {
        setIcon('#ecfdf5', 'bi bi-check-circle-fill', '#059669');
        document.getElementById('vTitle').textContent = 'GPS verification not required';
        document.getElementById('vSub').textContent = 'Continue with the configured identity verification method.';
        startBiometric();
        return;
    }

    geoRetryDowngraded = false;
    bestLocation = null;
    bestAccuracy = Infinity;
    if (locationWatcher !== null) {
        navigator.geolocation.clearWatch(locationWatcher);
        locationWatcher = null;
    }
    if (locationTimeoutId) {
        clearTimeout(locationTimeoutId);
        locationTimeoutId = null;
    }

    setSpinner();
    document.getElementById('vTitle').textContent = 'Getting accurate location...';
    document.getElementById('vSub').textContent = 'Waiting for a fresh GPS fix from this device...';
    hideMsg();
    document.getElementById('retryFpBtn').style.display = 'none';

    if (fastFallbackTimer) {
        clearTimeout(fastFallbackTimer);
        fastFallbackTimer = null;
    }

    if (!navigator.geolocation) {
        setIcon('#fef2f2', 'bi bi-geo-alt-fill', '#dc2626');
        document.getElementById('vTitle').textContent = 'GPS Not Available';
        document.getElementById('vSub').textContent = 'Your device does not support location services.';
        showMsg('err', 'Location access is required to clock in.');
        return;
    }

    // Request a fresh high-accuracy location reading immediately before geofence verification
    requestLocation({ timeout: GPS_TIMEOUT_MS, enableHighAccuracy: true, maximumAge: 0 });
}

function requestLocation(options) {
    var clearGpsTimers = function() {
        if (locationWatcher !== null) {
            navigator.geolocation.clearWatch(locationWatcher);
            locationWatcher = null;
        }
        if (locationTimeoutId) {
            clearTimeout(locationTimeoutId);
            locationTimeoutId = null;
        }
        if (fastFallbackTimer) {
            clearTimeout(fastFallbackTimer);
            fastFallbackTimer = null;
        }
    };

    clearGpsTimers();
    bestLocation = null;
    bestAccuracy = Infinity;
    var locationReadings = [];
    var bestEstimate = null;
    var locationAccepted = false;

    var acceptBestLocation = function(pos, accuracyLabel) {
        if (locationAccepted) return;
        locationAccepted = true;
        clearGpsTimers();

        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;
        var accuracy = pos.coords.accuracy || 0;

        console.log('GPS Accepted:', lat, lng, 'Accuracy:', accuracy + 'm');
        document.getElementById('latInput').value = lat;
        document.getElementById('lngInput').value = lng;
        document.getElementById('accuracyInput').value = accuracy;

        // Check if student is within teacher laptop geofence
        if (RADIUS_METERS > 0) {
            if (CLASSROOM_LAT === null || CLASSROOM_LNG === null) {
                showTeacherLocationMissingError();
                return;
            }

            // Handle weak or inaccurate GPS signals gracefully instead of incorrectly reporting too far away
            if (!Number.isFinite(accuracy) || accuracy <= 0 || accuracy > 50) {
                showWeakGpsError(accuracy);
                return;
            }

            var dist = calculateDistance(lat, lng, CLASSROOM_LAT, CLASSROOM_LNG);
            console.debug('Teacher laptop proximity check:', { rawDistanceMeters: dist, finalDistanceMeters: dist, allowedRadiusMeters: RADIUS_METERS });

            if (dist + accuracy > RADIUS_METERS) {
                if (isNearBoundary(dist, accuracy) || dist <= RADIUS_METERS) showLocationUncertainError(dist, accuracy);
                else showOutsideClassroomError(dist, RADIUS_METERS);
                return;
            }
        }

        setIcon('#f0fdf4', 'bi bi-geo-alt-fill', '#16a34a');
        setStep(3);
        document.getElementById('vTitle').textContent = 'Inside Classroom';
        document.getElementById('vSub').textContent = 'Location verified. Verifying identity...';
        showMsg('ok', '<i class="bi bi-check-circle me-1"></i> You are inside the classroom (' + accuracyLabel + ').');
        setTimeout(function() { startBiometric(); }, 800);
    };

    var onSuccess = function(pos) {
        if (locationAccepted) return;
        console.log('GPS Success:', pos.coords.latitude, pos.coords.longitude, 'Accuracy:', pos.coords.accuracy + 'm');

        if (!validFreshPosition(pos)) return;
        if (pos.coords.accuracy < bestAccuracy) {
            bestAccuracy = pos.coords.accuracy;
            bestLocation = pos;
        }

        var candidatePos = pos;
        var evalAcc = pos.coords.accuracy;
        var clustered = false;
        var sampleCount = 1;

        if (pos.coords.accuracy <= GPS_MAX_ACCEPTABLE_ACCURACY) {
            var normalized = window.GpsSampler
                ? window.GpsSampler.normalizeSample(pos)
                : { lat: pos.coords.latitude, lng: pos.coords.longitude, acc: pos.coords.accuracy, timestamp: pos.timestamp };
            if (normalized && !locationReadings.some(function(r) {
                return r.timestamp === normalized.timestamp && r.lat === normalized.lat && r.lng === normalized.lng;
            })) {
                locationReadings.push(normalized);
                locationReadings.sort(function(a, b) { return a.acc - b.acc; });
                if (locationReadings.length > 5) locationReadings.pop();
            }
            if (window.GpsSampler && locationReadings.length) {
                bestEstimate = window.GpsSampler.fuseCluster(locationReadings, { maxSpreadMeters: 60 });
                candidatePos = bestEstimate.toGeolocationPosition();
                evalAcc = bestEstimate.acc;
                clustered = bestEstimate.clustered;
                sampleCount = bestEstimate.sampleCount;
            }
        }

        var isWithinBounds = false;
        if (RADIUS_METERS > 0 && CLASSROOM_LAT !== null && CLASSROOM_LNG !== null) {
            var rawDist = calculateDistance(candidatePos.coords.latitude, candidatePos.coords.longitude, CLASSROOM_LAT, CLASSROOM_LNG);
            isWithinBounds = (rawDist + evalAcc <= RADIUS_METERS);
        } else {
            isWithinBounds = true;
        }

        if (isWithinBounds && clustered && sampleCount >= 2 && evalAcc <= GPS_MAX_ACCEPTABLE_ACCURACY) {
            var label = clustered ? 'Location confirmed (' + sampleCount + '-fix average)' : 'Location confirmed';
            acceptBestLocation(candidatePos, label);
            return;
        }

        if (pos.coords.accuracy <= GPS_MAX_FAST_ACCEPT) {
            showMsg('info', '<i class="bi bi-info-circle"></i> Refining GPS fix (±' + Math.round(evalAcc) + 'm)...');
            return;
        }

        showMsg('info', '<i class="bi bi-info-circle"></i> Weak GPS signal (±' + Math.round(pos.coords.accuracy) + 'm). Refining location...');
    };

    var onError = function(err) {
        if (locationAccepted) return;
        clearGpsTimers();

        if (!geoRetryDowngraded && (err.code === 3 || err.code === 2)) {
            geoRetryDowngraded = true;
            console.warn('Low-accuracy geolocation failed; retrying with higher accuracy.');
            showMsg('info', '<i class="bi bi-info-circle"></i> Location fix was not fast enough. Retrying with higher accuracy...');
            requestLocation({ timeout: GPS_TIMEOUT_MS, enableHighAccuracy: true, maximumAge: 0 });
            return;
        }

        setIcon('#fef2f2', 'bi bi-geo-alt-fill', '#dc2626');
        document.getElementById('vTitle').textContent = 'Location Failed';
        var reason = '';
        switch(err.code) {
            case 1:
                reason = 'Location permission denied. Please allow location access and try again.';
                break;
            case 2:
                reason = 'Could not determine location. Please check your GPS/location settings.';
                break;
            case 3:
                reason = 'Location request timed out. Please try again.';
                break;
            default:
                reason = 'Location error occurred. Please try again.';
        }
        document.getElementById('vSub').textContent = reason;
        showMsg('err', '<i class="bi bi-exclamation-circle me-1"></i> ' + reason + (geoRetryDowngraded ? ' (retry failed)' : ''));
        var btn = document.getElementById('retryFpBtn');
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry Location Check';
        btn.onclick = function() { startGPS(); };
        btn.style.display = 'flex';
    };

    locationWatcher = navigator.geolocation.watchPosition(onSuccess, onError, options);
    fastFallbackTimer = setTimeout(function() {
        if (locationAccepted) return;
        var fastCandidate = (bestEstimate && bestEstimate.clustered && bestEstimate.sampleCount >= 2 && bestEstimate.acc <= GPS_MAX_FAST_ACCEPT)
            ? bestEstimate.toGeolocationPosition()
            : null;
        if (fastCandidate) {
            if (RADIUS_METERS > 0 && CLASSROOM_LAT !== null && CLASSROOM_LNG !== null) {
                var earlyDist = calculateDistance(fastCandidate.coords.latitude, fastCandidate.coords.longitude, CLASSROOM_LAT, CLASSROOM_LNG);
                if (earlyDist + fastCandidate.coords.accuracy <= RADIUS_METERS) {
                    var fastLabel = (bestEstimate && bestEstimate.clustered)
                        ? 'Location confirmed (' + bestEstimate.sampleCount + '-fix average)'
                        : 'Location confirmed';
                    acceptBestLocation(fastCandidate, fastLabel);
                    return;
                }
                console.log('Fast fallback: location appears outside at 3s (' + Math.round(earlyDist) + 'm); continuing to watch for refined fix...');
            } else {
                acceptBestLocation(fastCandidate, 'Using best available location');
            }
        }
    }, GPS_FAST_FALLBACK_MS);

    locationTimeoutId = setTimeout(function() {
        if (locationAccepted) return;
        clearGpsTimers();

        if (bestEstimate && bestEstimate.clustered && bestEstimate.sampleCount >= 2 && bestEstimate.acc <= GPS_MAX_ACCEPTABLE_ACCURACY) {
            var timeoutLabel = bestEstimate.clustered
                ? 'Using ' + bestEstimate.sampleCount + '-fix average'
                : 'Using best available location';
            acceptBestLocation(bestEstimate.toGeolocationPosition(), timeoutLabel);
            return;
        }

        if (bestLocation || bestEstimate) {
            setIcon('#fef3c7', 'bi bi-geo-alt-fill', '#d97706');
            document.getElementById('vTitle').textContent = 'Location Not Stable Yet';
            document.getElementById('vSub').textContent = 'GPS readings did not agree closely enough to confirm your position.';
            showMsg('err', 'Move near a window, keep location enabled, and retry. You can also use the teacher’s approved alternative attendance method.');
            var retryBtn = document.getElementById('retryFpBtn');
            retryBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry Location Check';
            retryBtn.onclick = function() { startGPS(); };
            retryBtn.style.display = 'flex';
            return;
        }

        onError({ code: 3 });
    }, options.timeout || GPS_TIMEOUT_MS);
}

async function submitAttendance(payload, signal) {
    var token = QR_TOKEN;
    var isFace = (payload && payload.biometric_method === 'face');

    if (!isFace) {
        document.getElementById('vTitle').textContent = 'Confirming current location...';
        document.getElementById('vSub').textContent = 'Checking a fresh GPS fix after device verification.';
    } else {
        setCameraStatus('Confirming location & face...', 'pulsing');
    }

    var freshPosition = GPS_VERIFICATION_ENABLED ? await getFreshStablePosition() : null;
    if (signal?.aborted) return;

    if (GPS_VERIFICATION_ENABLED && (!freshPosition || !freshPosition.coords ||
        !validFreshPosition(freshPosition) ||
        !Number.isFinite(freshPosition.coords.latitude) ||
        !Number.isFinite(freshPosition.coords.longitude) ||
        Math.abs(freshPosition.coords.latitude) > 90 ||
        Math.abs(freshPosition.coords.longitude) > 180 ||
        !Number.isFinite(freshPosition.coords.accuracy) ||
        freshPosition.coords.accuracy <= 0 || freshPosition.coords.accuracy > 50)) {
        if (isFace) resetFaceSubmission();
        showWeakGpsError(freshPosition?.coords?.accuracy);
        return;
    }

    var latitude = freshPosition ? freshPosition.coords.latitude : null;
    var longitude = freshPosition ? freshPosition.coords.longitude : null;
    var accuracy = freshPosition ? freshPosition.coords.accuracy : null;
    if (freshPosition) {
        document.getElementById('latInput').value = latitude;
        document.getElementById('lngInput').value = longitude;
        document.getElementById('accuracyInput').value = accuracy;
    }

    if (GPS_VERIFICATION_ENABLED && RADIUS_METERS > 0) {
        if (CLASSROOM_LAT === null || CLASSROOM_LNG === null) {
            if (isFace) resetFaceSubmission();
            showTeacherLocationMissingError();
            return;
        }
        var currentDistance = calculateDistance(latitude, longitude, CLASSROOM_LAT, CLASSROOM_LNG);
        if (currentDistance + accuracy > RADIUS_METERS) {
            if (isFace) resetFaceSubmission();
            if (isNearBoundary(currentDistance, accuracy) || currentDistance <= RADIUS_METERS) showLocationUncertainError(currentDistance, accuracy);
            else showOutsideClassroomError(currentDistance, RADIUS_METERS);
            return;
        }
    }

    var devKey = (typeof window.getOrCreateDeviceKey === 'function')
        ? window.getOrCreateDeviceKey()
        : (localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || '');

    var dKeyEl = document.getElementById('deviceKeyInput');
    var dFpEl = document.getElementById('deviceFingerprintInput');
    if (dKeyEl && devKey) dKeyEl.value = devKey;
    if (dFpEl && devKey) dFpEl.value = devKey;

    var reqData = {
        token: token,
        latitude: latitude,
        longitude: longitude,
        accuracy: accuracy,
        location_timestamp_ms: freshPosition ? freshPosition.timestamp : null,
        device_key: devKey,
        device_fingerprint: devKey
    };

    if (isFace) {
        reqData.biometric_method = 'face';
        reqData.face_challenge = payload.face_challenge;
        reqData.live_frames = payload.live_frames;
        if (payload.credential) reqData.credential = payload.credential;
    } else {
        reqData.credential = payload;
    }

    var xhr2 = new XMLHttpRequest();
    xhr2.open('POST', '{{ route("qr.verify.complete") }}', true);
    xhr2.withCredentials = true;
    xhr2.setRequestHeader('X-CSRF-TOKEN', CSRF);
    xhr2.setRequestHeader('Content-Type', 'application/json');
    xhr2.setRequestHeader('Accept', 'application/json');
    xhr2.onload = function() {
        var response;
        try {
            response = JSON.parse(xhr2.responseText);
        } catch (e) {
            if (isFace) handleFaceError('Unable to read server response. Please try again.');
            else showFpError('Unable to read server response. Please try again.');
            return;
        }

        if (xhr2.status === 200 && response.success) {
            fingerprintInProgress = false;

            setStep(4);

            if (isFace) {
                var oval = document.getElementById('faceOvalGuide');
                if (oval) oval.className = 'face-oval-guide matched';
                var checkEl = document.getElementById('faceSuccessCheck');
                if (checkEl) checkEl.style.display = 'flex';
                var laser = document.getElementById('faceLaserLine');
                if (laser) laser.style.display = 'none';

                document.getElementById('faceLivenessStatus').textContent = response.face_verified ? 'Liveness passed' : 'Attendance already recorded';
                setCameraStatus(response.face_verified ? 'Liveness and identity verified' : 'Attendance already recorded', '');
                showMsg('ok', response.face_verified ? 'Attendance recorded.' : 'Attendance was already recorded.');

                setTimeout(function() {
                    stopCamera();
                    window.location.href = response.redirect || '/home';
                }, 800);
            } else {
                setIcon('#f0fdf4', 'bi bi-fingerprint', '#16a34a');
                document.getElementById('vTitle').textContent = 'Identity Verified';
                document.getElementById('vSub').textContent = 'Clocking you in now...';
                showMsg('ok', '<i class="bi bi-check-circle me-1"></i> Fingerprint confirmed.');
                window.location.href = response.redirect || '/home';
            }
            return;
        }

        if (response.error_type === 'teacher_location_unavailable') {
            if (isFace) resetFaceSubmission();
            showTeacherLocationMissingError();
            return;
        }

        if (response.error_type === 'unreliable_gps') {
            if (isFace) resetFaceSubmission();
            showWeakGpsError(response.accuracy || accuracy);
            return;
        }

        if (response.error_type === 'location_uncertain' || response.error_type === 'stale_location') {
            if (isFace) resetFaceSubmission();
            showLocationUncertainError(response.distance, accuracy);
            return;
        }

        if (response.error_type === 'outside_classroom') {
            if (isFace) resetFaceSubmission();
            showOutsideClassroomError(response.distance || 0, response.radius || RADIUS_METERS);
            return;
        }

        if (isFace) {
            handleFaceError(response.message || 'Face verification failed. Please try again.', response.code);
        } else {
            showFpError(response.message || 'Clock-in failed. Please try again.');
        }
    };
    xhr2.onerror = function() {
        if (isFace) handleFaceError('Network error while clocking in. Please try again.');
        else showFpError('Network error while clocking in. Please try again.');
    };
    xhr2.timeout = 35000;
    xhr2.ontimeout = xhr2.onerror;
    signal?.addEventListener('abort', () => xhr2.abort(), { once: true });
    xhr2.send(JSON.stringify(reqData));
}

function startBiometric() {
    var selector = document.getElementById('methodSelector');
    if (selector && HAS_FACE_METHOD && HAS_FINGERPRINT) {
        selector.style.display = 'flex';
    }

    // Never automatically invoke a biometric prompt without explicit user interaction.
    if (ACTIVE_METHOD === 'face' && HAS_FACE_METHOD) {
        showFaceIntro();
    } else if (HAS_FINGERPRINT) {
        showFingerprintReady();
    } else if (HAS_FACE_METHOD) {
        showFaceIntro();
    } else {
        showMsg('err', 'No biometric method is registered on your account. Register a device in Settings or ask your instructor for help.');
    }
}

function switchMethod(method) {
    if (faceSubmitting || fingerprintInProgress) return;
    ACTIVE_METHOD = method;

    var btnFace = document.getElementById('btnMethodFace');
    var btnFp = document.getElementById('btnMethodFp');
    if (btnFace) btnFace.classList.toggle('active', method === 'face');
    if (btnFp) btnFp.classList.toggle('active', method === 'fingerprint');

    hideMsg();
    if (method === 'face') {
        fingerprintController?.abort();
        document.getElementById('retryFpBtn').style.display = 'none';
        document.getElementById('vIcon').style.display = 'none';
        document.getElementById('vTitle').style.display = 'none';
        document.getElementById('vSub').style.display = 'none';
        showFaceIntro();
    } else {
        stopCamera();
        document.getElementById('faceScanArea').style.display = 'none';
        showFingerprintReady();
    }
}

async function showFingerprintReady() {
    fingerprintController?.abort();
    fingerprintInProgress = false;
    stopCamera();
    var scanArea = document.getElementById('faceScanArea');
    if (scanArea) scanArea.style.display = 'none';
    document.getElementById('vIcon').style.display = 'flex';
    document.getElementById('vTitle').style.display = 'block';
    document.getElementById('vSub').style.display = 'block';
    hideMsg();

    var btn = document.getElementById('retryFpBtn');
    setSpinner();
    document.getElementById('vTitle').textContent = 'Checking device compatibility';
    document.getElementById('vSub').textContent = 'Verifying whether this device supports platform authentication…';
    btn.style.display = 'none';

    var capability = await (window.DevicePasskeys?.check('get') || { available: false, reason: 'unsupported' });
    if (ACTIVE_METHOD !== 'fingerprint') return;

    if (!capability.available) {
        setIcon('#fef3c7', 'bi bi-exclamation-triangle', '#d97706');
        document.getElementById('vTitle').textContent = 'Device Authentication Unavailable';
        document.getElementById('vSub').textContent = window.DevicePasskeys?.message(capability, 'get')
            || 'This device or browser does not support built-in biometric/passkey verification.';
        showMsg('err', '<i class="bi bi-info-circle me-1"></i> ' + (HAS_FACE_METHOD
            ? 'Switch to Live Face Match above or ask your instructor for assistance.'
            : 'Ask your instructor for an approved check-in fallback.'));
        btn.style.display = 'none';
        return;
    }

    var isNativeFp = Boolean(capability.supportsFingerprintSpecificUi);
    setIcon('rgba(52, 211, 153, 0.14)', isNativeFp ? 'bi bi-fingerprint' : 'bi bi-shield-check', '#34d399');
    document.getElementById('vTitle').textContent = isNativeFp ? 'Ready for Fingerprint Verification' : 'Ready for Device Verification';
    document.getElementById('vSub').textContent = capability.disclaimer
        || 'Tap the button below to trigger your operating system’s biometric or screen-lock verification prompt.';
    btn.innerHTML = '<i class="bi ' + (isNativeFp ? 'bi-fingerprint' : 'bi-shield-check') + '"></i> '
        + (isNativeFp ? 'Verify Fingerprint' : 'Verify with This Device');
    btn.onclick = function() { doFingerprint(false); };
    btn.style.display = 'flex';
}

function showFaceIntro() {
    fingerprintController?.abort();
    stopCamera();
    document.getElementById('faceScanArea').style.display = 'block';
    document.getElementById('faceAttendanceNotice').hidden = false;
    document.getElementById('faceCapturePanel').hidden = true;
    document.getElementById('btnCancelFace').hidden = true;
    document.getElementById('btnRetryFace').hidden = true;
    document.getElementById('retryFpBtn').style.display = 'none';
    ['vIcon', 'vTitle', 'vSub'].forEach(id => document.getElementById(id).style.display = 'none');
    hideMsg();
}

async function startFaceScan() {
    if (faceSubmitting) return;
    showFaceIntro();
    const attempt = new AbortController();
    faceAttempt = attempt;
    const { signal } = attempt;
    const video = document.getElementById('faceCameraVideo');
    const progress = document.getElementById('faceCaptureProgress');
    const progressLabel = document.getElementById('faceProgressLabel');
    const instruction = document.getElementById('facePoseInstruction');
    const confirmation = document.getElementById('faceCaptureConfirmation');
    const status = document.getElementById('faceLivenessStatus');
    document.getElementById('faceAttendanceNotice').hidden = true;
    document.getElementById('faceCapturePanel').hidden = false;
    document.getElementById('btnCancelFace').hidden = false;
    document.getElementById('faceOvalGuide').className = 'face-oval-guide';
    document.getElementById('faceSuccessCheck').style.display = 'none';
    progress.value = 0;
    progressLabel.textContent = '0/5 angles captured';
    confirmation.textContent = '';
    instruction.textContent = 'Position your face inside the oval.';
    status.textContent = 'Liveness check pending';
    setCameraStatus('Starting camera…', 'searching');

    let detector;
    try {
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error('This browser cannot open the camera. Use device verification or ask your teacher for help.');
        }

        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 640 } },
            audio: false,
        });
        if (signal.aborted) {
            stream.getTracks().forEach(track => track.stop());
            return;
        }
        activeStream = stream;
        video.srcObject = stream;
        stream.getVideoTracks().forEach(track => track.addEventListener('ended', () => {
            if (!signal.aborted) handleFaceError('The camera was disconnected. Try again or use device verification.');
        }, { once: true }));
        await video.play();
        signal.throwIfAborted();
        faceLighting?.start(stream);
        setCameraStatus('Preparing face detection…', 'searching');
        const guidance = await import(FACE_GUIDANCE_URL);
        signal.throwIfAborted();
        detector = await guidance.createDetector();
        signal.throwIfAborted();

        const devKey = typeof window.getOrCreateDeviceKey === 'function' ? window.getOrCreateDeviceKey() : '';
        const response = await fetch('{{ route("qr.verify.options") }}', {
            method: 'POST',
            credentials: 'same-origin',
            signal,
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(devKey ? { 'X-Device-Key': devKey, 'X-Device-Fingerprint': devKey } : {}),
            },
            body: JSON.stringify({ token: QR_TOKEN, biometric_method: 'face' }),
        });
        const options = await response.json().catch(() => ({}));
        signal.throwIfAborted();
        const challenge = options.face_challenge;
        if (!response.ok || !challenge?.id || challenge.poses?.length !== 5 || !challenge.expires_in) {
            throw new Error(options.message || 'Unable to start the camera check. Please scan the QR again.');
        }
        const frames = await guidance.captureSequence({
            video,
            canvas: document.getElementById('faceCaptureCanvas'),
            detector,
            poses: challenge.poses,
            signal,
            expiresIn: challenge.expires_in,
            onGuide(message, state, direction) {
                if (instruction.textContent !== direction) instruction.textContent = direction;
                setCameraStatus(message, state);
            },
            onProgress(count, pose) {
                progress.value = count;
                progressLabel.textContent = count + '/5 angles captured';
                confirmation.textContent = { front: 'Front', left: 'Left', right: 'Right', up: 'Upward', down: 'Downward' }[pose] + ' view captured.';
                window.triggerHaptic?.('light');
            },
        });
        signal.throwIfAborted();
        closeFaceStream();
        let credential = null;
        if (['fingerprint', 'fingerprint_and_device'].includes(FACE_ADDITIONAL_FACTOR)) {
            setCameraStatus('Confirm with your registered device.', 'pulsing');
            credential = await window.DevicePasskeys.verifyFingerprint({ ...options, timeout: 20000 }, signal, true);
        }
        signal.throwIfAborted();
        faceSubmitting = true;
        document.getElementById('btnCancelFace').hidden = true;
        document.querySelectorAll('#methodSelector button').forEach(button => button.disabled = true);
        status.textContent = 'Checking liveness and identity…';
        await submitAttendance({
            biometric_method: 'face',
            face_challenge: challenge.id,
            live_frames: frames,
            credential,
        }, signal);
    } catch (error) {
        if (!signal.aborted) {
            const message = error.name === 'NotAllowedError'
                ? 'Camera or device permission was declined. Try again or ask your teacher for help.'
                : (error instanceof TypeError ? 'The camera check could not connect. Try again or use device verification.' : error.message);
            handleFaceError(message || 'The camera check could not start. Try again or use device verification.');
        }
    } finally {
        detector?.close();
    }
}

function closeFaceStream() {
    faceLighting?.stop();
    activeStream?.getTracks().forEach(track => track.stop());
    activeStream = null;
    const video = document.getElementById('faceCameraVideo');
    if (video) video.srcObject = null;
    const canvas = document.getElementById('faceCaptureCanvas');
    if (canvas) canvas.width = 0;
}

function stopCamera() {
    faceAttempt?.abort();
    faceAttempt = null;
    closeFaceStream();
}

function setCameraStatus(text, state) {
    const message = document.getElementById('cameraStatusText');
    if (message && message.textContent !== text) message.textContent = text;
    const dot = document.getElementById('cameraStatusDot');
    if (dot) dot.className = 'status-dot ' + (state || '');
}

function resetFaceSubmission() {
    faceSubmitting = false;
    stopCamera();
    document.getElementById('btnCancelFace').hidden = true;
    document.getElementById('btnRetryFace').hidden = false;
    document.querySelectorAll('#methodSelector button').forEach(button => button.disabled = false);
}

function handleFaceError(message, code) {
    resetFaceSubmission();
    document.getElementById('faceOvalGuide').className = 'face-oval-guide mismatch';
    document.getElementById('faceLivenessStatus').textContent = ({
        LIVENESS_FAILED: 'Liveness check failed',
        LIVENESS_UNCERTAIN: 'Liveness inconclusive',
        LIVE_POSE_MISMATCH: 'Movement check failed',
        BIOMETRIC_MISMATCH: 'Liveness passed. Face did not match.',
        IDENTITY_UNCERTAIN: 'Liveness passed. Face match inconclusive.',
    })[code] || 'Camera check incomplete';
    setCameraStatus('Camera check unsuccessful', 'err');
    showMsg('err', '');
    document.getElementById('vMsg').textContent = message;
}

window.addEventListener('pagehide', stopCamera);
document.addEventListener('visibilitychange', function () {
    if (document.hidden && faceAttempt && activeStream && !faceSubmitting) {
        handleFaceError('The camera paused when you left this page. Start a new check to continue.');
    }
});
window.addEventListener('pageshow', function (event) {
    if (event.persisted && ACTIVE_METHOD === 'face' && HAS_FACE_METHOD) {
        if (faceSubmitting) resetFaceSubmission();
        showFaceIntro();
    }
});
async function doFingerprint(allowSavedPasskey = false) {
    if (fingerprintInProgress) return;
    fingerprintController?.abort();
    const controller = new AbortController();
    fingerprintController = controller;
    const signal = controller.signal;
    fingerprintInProgress = true;
    stopCamera();
    const scanArea = document.getElementById('faceScanArea');
    if (scanArea) scanArea.style.display = 'none';
    document.getElementById('vIcon').style.display = 'flex';
    document.getElementById('vTitle').style.display = 'block';
    document.getElementById('vSub').style.display = 'block';
    document.getElementById('retryFpBtn').style.display = 'none';
    setSpinner();
    document.getElementById('vTitle').textContent = 'Verify your fingerprint';
    document.getElementById('vSub').textContent = 'Preparing your device prompt…';
    hideMsg();

    let lookupTimer;
    try {
        const capability = await window.DevicePasskeys.check('get');
        if (signal.aborted) return;
        if (!capability.available) {
            showFpError(window.DevicePasskeys.message(capability, 'get') || 'Device verification is unavailable on this browser.');
            return;
        }
        lookupTimer = setTimeout(() => {
            controller.abort();
            showFpError('The verification request timed out. Check your connection and try again.');
        }, 15000);
        const devKey = window.getOrCreateDeviceKey?.() || localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || '';
        const response = await fetch('{{ route("qr.verify.options") }}', {
            method: 'POST', credentials: 'same-origin', signal,
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ token: QR_TOKEN, device_key: devKey, device_fingerprint: devKey })
        });
        const opts = await response.json().catch(() => ({}));
        clearTimeout(lookupTimer);
        if (signal.aborted) return;
        if (!response.ok || !opts.success || !opts.challenge) {
            showFpError(opts.message || 'Could not start verification for this attendance session. Try again or scan the current QR code.');
            return;
        }
        var scanButton = document.getElementById('retryFpBtn');
        var requestScan = async function() {
            if (signal.aborted) return;
            fingerprintInProgress = true;
            scanButton.style.display = 'none';
            document.getElementById('vTitle').textContent = 'Awaiting device verification';
            document.getElementById('vSub').textContent = 'Follow the native biometric or screen-lock prompt shown by your device.';
            try {
                var credentialData = await window.DevicePasskeys.verifyFingerprint(opts, signal, allowSavedPasskey);
                if (signal.aborted) return;
                setStep(4);
                setIcon('#f0fdf4', 'bi bi-shield-check', '#16a34a');
                document.getElementById('vTitle').textContent = 'Validating on server';
                document.getElementById('vSub').textContent = 'Confirming your cryptographic verification and recording attendance…';
                document.getElementById('credentialInput').value = JSON.stringify(credentialData);
                await submitAttendance(credentialData, signal);
            } catch (err) {
                if (signal.aborted) return;
                if (err.canCheckSavedPasskey) {
                    showFpError('No matching credential was recognized on this device. <a href="{{ route("settings.biometrics.fingerprint") }}">Register this device</a> or check your saved passkeys.');
                    scanButton.textContent = 'Use a previously saved passkey';
                    scanButton.onclick = () => doFingerprint(true);
                } else {
                    var classified = window.DevicePasskeys?.classifyError
                        ? window.DevicePasskeys.classifyError(err, 'verify')
                        : { message: 'Device verification could not finish. Try again.' };
                    var extraLink = ['NotAllowedError', 'InvalidStateError', 'NotFoundError'].includes(err.name)
                        ? ' <a href="{{ route("settings.biometrics.fingerprint") }}">Manage registered devices</a>'
                        : '';
                    showFpError(classified.message + extraLink);
                }
            }
        };
        if (window.DevicePasskeys.needsTap('get', opts, allowSavedPasskey)) {
            var readyAt = performance.now();
            fingerprintInProgress = false;
            document.getElementById('vTitle').textContent = 'Ready to verify';
            document.getElementById('vSub').textContent = 'Tap below to open your device authentication prompt.';
            scanButton.textContent = 'Open device prompt';
            scanButton.style.display = 'flex';
            scanButton.onclick = function() {
                if (performance.now() - readyAt >= 45000) doFingerprint(allowSavedPasskey);
                else requestScan();
            };
        } else {
            requestScan();
        }
    } catch (error) {
        if (!signal.aborted) showFpError('Could not prepare the fingerprint prompt. Check your connection and try again.');
    } finally {
        clearTimeout(lookupTimer);
    }
}

window.addEventListener('pageshow', event => {
    if (event.persisted && fingerprintController?.signal.aborted && ACTIVE_METHOD === 'fingerprint') {
        showFpError('The previous fingerprint check ended. Start a new check to continue.');
    }
});

function submitForm(msg) {
    setIcon('#fef2f2', 'bi bi-exclamation-circle', '#dc2626');
    document.getElementById('vTitle').textContent = 'Unable to Clock In';
    document.getElementById('vSub').textContent = msg;
    showMsg('err', '<i class="bi bi-exclamation-circle me-1"></i> ' + msg);
}

function showTeacherLocationMissingError() {
    fingerprintInProgress = false;
    setIcon('#fef3c7', 'bi bi-laptop', '#d97706');
    document.getElementById('vTitle').textContent = 'Teacher Location Unavailable';
    document.getElementById('vSub').textContent = 'Reference location for this session has not been set.';
    showMsg('err', '<i class="bi bi-exclamation-triangle-fill me-1"></i> The teacher\'s laptop location is not available for this session. Please ask your instructor to enable location on their laptop.');
    var btn = document.getElementById('retryFpBtn');
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh Page';
    btn.onclick = function() { window.location.reload(); };
    btn.style.display = 'flex';
}

function showWeakGpsError(acc) {
    fingerprintInProgress = false;
    setIcon('#fef2f2', 'bi bi-geo', '#dc2626');
    document.getElementById('vTitle').textContent = 'Location Signal Unreliable';
    var accMsg = (acc && acc > 0) ? ' (±' + Math.round(acc) + 'm)' : '';
    document.getElementById('vSub').textContent = 'Location readings are weak or unstable' + accMsg + '.';
    showMsg('err', '<i class="bi bi-exclamation-triangle-fill me-1"></i> Keep Wi-Fi and precise location enabled, wait for a better fix, then retry. If it remains unreliable indoors, ask your instructor for help.');
    var btn = document.getElementById('retryFpBtn');
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry Location Check';
    btn.onclick = function() { startGPS(); };
    btn.style.display = 'flex';
}

function showLocationUncertainError(dist, accuracy) {
    fingerprintInProgress = false;
    setIcon('#fef3c7', 'bi bi-geo-alt-fill', '#d97706');
    document.getElementById('vTitle').textContent = 'Location Needs Another Check';
    document.getElementById('vSub').textContent = 'The indoor location reading is too close to the boundary to decide.';
    showMsg('err', '<i class="bi bi-exclamation-triangle-fill me-1"></i> Try again with location services on and Wi-Fi enabled. If GPS stays uncertain while you are in class, ask your instructor for an approved attendance fallback.');
    var btn = document.getElementById('retryFpBtn');
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry Location Check';
    btn.onclick = function() { startGPS(); };
    btn.style.display = 'flex';
}

function showOutsideClassroomError(dist, limit) {
    fingerprintInProgress = false;
    setIcon('#fef2f2', 'bi bi-geo-alt-fill', '#dc2626');
    document.getElementById('vTitle').textContent = 'Failed to Scan';
    const roundedDist = Math.round(dist);
    document.getElementById('vSub').textContent = 'You are outside the classroom (' + roundedDist.toLocaleString() + 'm away).';
    showMsg('err', '<i class="bi bi-x-circle-fill me-1"></i> <strong>Outside Classroom:</strong> Your phone is ' + roundedDist.toLocaleString() + 'm from the session center (limit ' + limit + 'm). If you are beside the instructor, ask them to check the saved center or use the verified campus anchor.');
    var btn = document.getElementById('retryFpBtn');
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry Location Check';
    btn.onclick = function() { startGPS(); };
    btn.style.display = 'flex';

    showOutsideRangePopup({
        distance: dist,
        radius: limit,
        message: 'You are ' + roundedDist.toLocaleString() + 'm from the session center (limit ' + limit + 'm). If you are beside the instructor, ask them to check the saved center or use the verified campus anchor.'
    });
}

function showOutsideRangePopup(data) {
    const modal = document.getElementById('outsideRangePopupModal');
    if (!modal) return;

    const rawDist = (data.distance !== undefined && data.distance !== null) ? Number(data.distance) : 
                    ((data.dist !== undefined && data.dist !== null) ? Number(data.dist) : null);
    const rawRadius = (data.radius !== undefined && data.radius !== null) ? Number(data.radius) : 
                      ((data.limit !== undefined && data.limit !== null) ? Number(data.limit) : 50);

    const dist = (rawDist !== null && !isNaN(rawDist)) ? Math.round(rawDist) : null;
    const radius = !isNaN(rawRadius) ? Math.round(rawRadius) : 50;

    const distEl = document.getElementById('outsideRangeDetectedDist');
    const radEl = document.getElementById('outsideRangeAllowedRadius');
    const msgEl = document.getElementById('outsideRangeMessage');

    if (distEl) distEl.textContent = dist !== null ? (dist.toLocaleString() + 'm away') : 'Out of range';
    if (radEl) radEl.textContent = radius + 'm radius';
    if (msgEl && data.message) {
        msgEl.textContent = data.message;
    }

    modal.style.display = 'flex';
    if (window.triggerHaptic) window.triggerHaptic('error');
}

function closeOutsideRangePopup() {
    const modal = document.getElementById('outsideRangePopupModal');
    if (modal) modal.style.display = 'none';
}

function retryScanFromOutsidePopup() {
    closeOutsideRangePopup();
    startGPS();
}

function showFpError(msg) {
    fingerprintInProgress = false; // Reset the flag on error
    setIcon('#fef2f2', 'bi bi-fingerprint', '#dc2626');
    document.getElementById('vTitle').textContent = 'Fingerprint Required';
    document.getElementById('vSub').textContent = 'You must verify your fingerprint to clock in.';
    showMsg('err', '<i class="bi bi-exclamation-circle me-1"></i> ' + msg);
    var btn = document.getElementById('retryFpBtn');
    btn.innerHTML = '<i class="bi bi-fingerprint"></i> Try Fingerprint Again';
    btn.onclick = function() { doFingerprint(); };
    btn.style.display = 'flex';
}
</script>
@endsection
