<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <title>Sign In - {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/premium.css') }}">
    <link rel="preload" as="image" href="/images/815973132_28114561508245819_4078090034639789685_n.jpg">
    @include('partials.pwa-tags')
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── ENTRANCE ANIMATIONS ── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(22px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        @keyframes shakeError {
            0%, 100% { transform: translateX(0); }
            15%, 45%, 75% { transform: translateX(-6px); }
            30%, 60%, 90% { transform: translateX(6px); }
        }
        @keyframes spinLoader {
            to { transform: rotate(360deg); }
        }
        .anim-fade-up { animation: fadeInUp 0.55s ease both; }
        .anim-d1 { animation-delay: 0.05s; }
        .anim-d2 { animation-delay: 0.12s; }
        .anim-d3 { animation-delay: 0.19s; }
        .anim-d4 { animation-delay: 0.26s; }
        .anim-d5 { animation-delay: 0.33s; }
        .anim-d6 { animation-delay: 0.40s; }
        .anim-d7 { animation-delay: 0.47s; }
        
        @keyframes floatLogo {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        @keyframes glowPulse {
            0% { box-shadow: 0 4px 20px rgba(0,0,0,0.3), 0 0 0 0 rgba(212, 175, 55, 0.4); }
            70% { box-shadow: 0 4px 20px rgba(0,0,0,0.3), 0 0 0 10px rgba(212, 175, 55, 0); }
            100% { box-shadow: 0 4px 20px rgba(0,0,0,0.3), 0 0 0 0 rgba(212, 175, 55, 0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.9); }
        }

        .glass-alert { animation: shakeError 0.5s ease; }
        .btn-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(128,0,0,0.2); border-top-color: #800000; border-radius: 50%; animation: spinLoader 0.6s linear infinite; margin-right: 8px; vertical-align: middle; }

        :root {
            --sat: env(safe-area-inset-top, 0px);
            --sab: env(safe-area-inset-bottom, 0px);
            --sal: env(safe-area-inset-left, 0px);
            --sar: env(safe-area-inset-right, 0px);
            --app-height: 100dvh;
        }

        html {
            zoom: 1 !important;
        }

        html, body {
            height: 100%;
            height: 100dvh;
            height: var(--app-height, 100dvh);
            max-height: var(--app-height, 100dvh);
            width: 100%;
            overflow: hidden;
            overscroll-behavior: none;
            overscroll-behavior-y: none;
            overscroll-behavior-x: none;
            -webkit-overscroll-behavior: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: 0;
            padding: 0;
            touch-action: pan-y;
            background-color: #110A0A;
            color: #F3E7CD;
            font-family: 'Inter', sans-serif;
        }

        /* ── FULL-SCREEN BACKGROUND ── */
        .bg-scene {
            position: fixed; inset: 0;
            background: url('/images/815973132_28114561508245819_4078090034639789685_n.jpg') center center / cover no-repeat;
            background-color: #1a0a0a;
            z-index: 0;
            pointer-events: none;
        }
        /* On narrow/tall portrait phones the landscape photo appeared very zoomed-in
           when anchored to center. Anchor to top-center so more of the scene is visible. */
        @media (max-width: 768px) and (orientation: portrait) {
            .bg-scene {
                background-position: top center;
                background-size: cover;
            }
        }
        @media (max-width: 768px) and (orientation: landscape) {
            .bg-scene {
                background-position: center center;
                background-size: cover;
            }
        }
        .bg-scene::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(
                135deg,
                rgba(0,0,0,0.55) 0%,
                rgba(80,0,20,0.35) 50%,
                rgba(0,0,0,0.50) 100%
            );
        }

        /* background video removed from login - intro handled separately */

        /* ── TOP BAR ── */
        .top-bar {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding-top: calc(env(safe-area-inset-top, 0px) + 12px);
            padding-bottom: 10px;
            padding-left: calc(env(safe-area-inset-left, 0px) + 24px);
            padding-right: calc(env(safe-area-inset-right, 0px) + 24px);
            background: linear-gradient(180deg, rgba(17, 10, 10, 0.88) 0%, rgba(17, 10, 10, 0.35) 75%, transparent 100%);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            pointer-events: auto;
            /* locked against status bar jitter */
        }
        .top-bar-brand {
            font-size: 0.78rem; font-weight: 800;
            color: white; letter-spacing: 1.5px;
            text-transform: uppercase; opacity: 0.9;
            display: flex; align-items: center; gap: 10px;
        }
        .top-bar-brand i { font-size: 1.1rem; }

        /* ── BOTTOM BAR ── */
        .bottom-bar {
            position: fixed; bottom: 0; left: 0; right: 0;
            z-index: 25;
            display: flex; align-items: center; justify-content: space-between;
            padding-top: 10px;
            padding-bottom: calc(var(--sab, env(safe-area-inset-bottom, 0px)) + 12px);
            padding-left: calc(var(--sal, env(safe-area-inset-left, 0px)) + 24px);
            padding-right: calc(var(--sar, env(safe-area-inset-right, 0px)) + 24px);
            font-size: 0.74rem; color: rgba(255,255,255,0.45);
            pointer-events: none;
            background: linear-gradient(0deg, rgba(17, 10, 10, 0.92) 0%, rgba(17, 10, 10, 0.5) 70%, transparent 100%);
            transition: opacity 0.2s ease;
        }
        .bottom-bar a {
            color: rgba(255,255,255,0.55); text-decoration: none;
            transition: color 0.2s; pointer-events: all;
        }
        .bottom-bar a:hover { color: #d4af37; }
        .bottom-bar span { pointer-events: none; }
        .bottom-links { display: flex; align-items: center; gap: 20px; }

        body.keyboard-open .bottom-bar {
            display: none !important;
        }

        /* ── CENTERED & IMMOVABLE RESPONSIVE LAYOUT ── */
        .auth-scene {
            position: absolute;
            inset: 0;
            z-index: 10;
            width: 100%;
            height: 100%;
            height: var(--app-height, 100%);
            max-height: var(--app-height, 100%);
            display: flex; 
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding-top: calc(var(--sat, env(safe-area-inset-top, 0px)) + 52px);
            padding-bottom: calc(var(--sab, env(safe-area-inset-bottom, 0px)) + 50px);
            padding-left: calc(var(--sal, env(safe-area-inset-left, 0px)) + 16px);
            padding-right: calc(var(--sar, env(safe-area-inset-right, 0px)) + 16px);
            box-sizing: border-box;
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: none !important;
            overscroll-behavior-y: none !important;
            -webkit-overflow-scrolling: touch;
            /* locked against status bar jitter */
        }

        /* ── GLASS CARD ── */
        .glass-card {
            width: 100%; 
            max-width: 440px;
            min-width: 280px;
            background: rgba(28, 18, 18, 0.88);
            backdrop-filter: blur(28px) saturate(190%);
            -webkit-backdrop-filter: blur(28px) saturate(190%);
            border-radius: 20px;
            border: 1px solid rgba(212, 175, 55, 0.26);
            box-shadow: 0 16px 44px rgba(0,0,0,0.55), 0 0 0 1px rgba(255,255,255,0.05), inset 0 1px 0 rgba(212, 175, 55, 0.16);
            padding: 22px 24px 18px;
            color: white;
            position: relative;
            z-index: 20;
            margin: auto 0;
            transition: transform 0.2s ease, padding 0.2s ease;
            overflow-y: visible;
        }

        /* Logo */
        .glass-logo {
            width: 52px; height: 52px;
            border-radius: 50%;
            background: #4A121C;
            border: 2px solid rgba(212, 175, 55, 0.75);
            box-shadow: 0 4px 16px rgba(0,0,0,0.4), 0 0 12px rgba(212, 175, 55, 0.25);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            margin: 0 auto 8px;
            transition: transform 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
            animation: floatLogo 4s ease-in-out infinite, glowPulse 2s infinite;
        }
        .glass-logo:hover { transform: scale(1.08); animation-play-state: paused; }
        .glass-logo img { width: 100%; height: 100%; object-fit: cover; }

        /* Badge */
        .glass-badge {
            display: inline-block;
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.25);
            color: rgba(255,255,255,0.9);
            font-size: 0.62rem; font-weight: 800;
            letter-spacing: 1.5px; text-transform: uppercase;
            padding: 2px 10px; border-radius: 99px;
            margin-bottom: 3px;
        }
        .glass-title {
            font-size: 1.42rem; font-weight: 800;
            color: white; letter-spacing: -0.4px;
            margin-bottom: 2px;
        }
        .glass-sub {
            font-size: 0.8rem; color: rgba(255,255,255,0.65);
            margin-bottom: 12px;
        }

        /* Role toggle */
        .role-toggle {
            display: flex;
            background: rgba(0,0,0,0.25);
            border-radius: 12px;
            padding: 5px; gap: 5px; /* Increased padding */
            margin-bottom: 18px; /* Increased margin */
            border: 1px solid rgba(255,255,255,0.12);
        }
        .role-btn {
            flex: 1; padding: 10px 12px; /* Increased padding */
            border: none; border-radius: 9px;
            font-size: 0.88rem; font-weight: 600; /* Increased font size */
            cursor: pointer;
            transition: all 0.25s ease;
            background: transparent;
            color: rgba(255,255,255,0.55);
            display: flex; align-items: center; justify-content: center; gap: 7px;
        }
        .role-btn.active {
            background: rgba(255,255,255,0.95);
            color: #800000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .role-btn:hover:not(.active) { color: white; background: rgba(255,255,255,0.1); }

        /* Inputs */
        .glass-input-wrap { position: relative; margin-bottom: 10px; width: 100%; }
        .glass-input-wrap .g-icon {
            position: absolute; left: 14px !important; top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.5); font-size: 0.95rem !important;
            pointer-events: none; transition: color 0.2s;
            z-index: 2;
        }
        .glass-input {
            width: 100%;
            padding: 11px 14px 11px 42px;
            border-radius: 11px;
            border: 1.5px solid rgba(212, 175, 55, 0.25);
            background: rgba(0,0,0,0.3);
            color: white;
            font-size: 0.88rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: all 0.2s;
        }
        .glass-input::placeholder { color: rgba(255,255,255,0.35); }
        .glass-input:hover { border-color: rgba(212, 175, 55, 0.4); background: rgba(0,0,0,0.4); }
        .glass-input:focus {
            border-color: #d4af37;
            background: rgba(0,0,0,0.5);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
        }
        .glass-input-wrap:focus-within .g-icon { color: rgba(255,255,255,0.85); }
        .glass-input.has-eye { padding-right: 46px !important; }
        .eye-toggle {
            position: absolute; right: 6px; top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.65); font-size: 1rem;
            cursor: pointer; background: none; border: none; padding: 0;
            transition: color 0.2s; line-height: 1;
            z-index: 10 !important;
            min-width: 38px !important;
            min-height: 38px !important;
            width: 38px !important;
            height: 38px !important;
            display: inline-flex; align-items: center; justify-content: center;
            user-select: none !important;
            -webkit-user-select: none !important;
            -webkit-touch-callout: none !important;
            -webkit-tap-highlight-color: transparent !important;
            touch-action: manipulation !important;
            border-radius: 9px;
        }
        .eye-toggle:hover { color: white; }
        .eye-toggle:focus-visible {
            outline: 2px solid rgba(212, 175, 55, 0.75) !important;
            outline-offset: 2px !important;
            color: #ffffff !important;
        }
        .eye-toggle i { pointer-events: none !important; font-size: 1.05rem !important; }


        /* Fingerprint/Biometric row */
        .fp-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 8px 12px;
            border-radius: 12px;
            border: 1.5px solid rgba(255, 255, 255, 0.16);
            border-top: 1.5px solid rgba(255, 255, 255, 0.22);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.03) 100%);
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            margin-bottom: 5px;
            width: 100%;
            font-family: inherit;
            color: inherit;
            outline: none;
            text-align: left;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            touch-action: manipulation;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
        }
        .fp-row * { pointer-events: none; }
        @media (hover: hover) and (pointer: fine) {
            .fp-row:hover {
                background: linear-gradient(135deg, rgba(212, 175, 55, 0.18) 0%, rgba(212, 175, 55, 0.06) 100%);
                border-color: rgba(212, 175, 55, 0.55);
                transform: translateY(-1px);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35), 0 0 16px rgba(212, 175, 55, 0.12);
            }
        }
        .fp-row:active {
            transform: scale(0.98);
            background: rgba(212, 175, 55, 0.24);
            border-color: rgba(212, 175, 55, 0.7);
        }
        .fp-row:focus { outline: none; }
        .fp-row:focus-visible { outline: 2px solid rgba(212, 175, 55, 0.7); outline-offset: 2px; }
        .fp-row-left { display: flex; align-items: center; gap: 10px; text-align: left; }
        .fp-row-left .fp-icon-wrap {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(14, 165, 233, 0.15) 100%);
            border: 1px solid rgba(52, 211, 153, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #34d399;
            font-size: 1.05rem;
            flex-shrink: 0;
            transition: all 0.25s ease;
        }
        .fp-row:hover .fp-icon-wrap {
            border-color: #34d399;
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);
            transform: scale(1.05);
        }
        .fp-row-left i { font-size: 1.05rem; color: #34d399; transition: all 0.3s; }
        .fp-row-label { font-size: 0.82rem; font-weight: 700; color: #ffffff; letter-spacing: -0.1px; transition: all 0.2s; }
        .fp-row-hint { font-size: 0.67rem; color: rgba(255, 255, 255, 0.65); margin-top: 1px; transition: all 0.2s; }
        .fp-row-arrow { color: rgba(255, 255, 255, 0.45); font-size: 0.8rem; transition: all 0.2s; }
        .fp-row:hover .fp-row-arrow { color: #f5dfa8; transform: translateX(2px); }

        /* Divider */
                #reEnrollBioLink, [id*='reEnrollBio'] { display: none !important; pointer-events: none !important; opacity: 0 !important; visibility: hidden !important; }

.glass-divider {
            display: flex; align-items: center; gap: 8px;
            margin: 6px 0; color: rgba(255,255,255,0.35); font-size: 0.68rem;
        }
        .glass-divider::before, .glass-divider::after {
            content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.18);
        }

        /* Submit button */
        .glass-btn {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, #ffffff 0%, #f7f7f7 100%);
            color: #800000;
            font-weight: 800; font-size: 0.92rem;
            letter-spacing: 0.5px;
            border: none; border-radius: 11px;
            cursor: pointer; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 16px rgba(0,0,0,0.22);
            margin-top: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .glass-btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #f7f7f7 100%);
            color: #800000;
        }
        .glass-btn-secondary {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.9);
            border: 1.5px solid rgba(255,255,255,0.2);
        }
        .glass-btn:hover {
            background: #ffffff;
            transform: translateY(-1.5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.3), 0 0 16px rgba(255,255,255,0.15);
        }
        .glass-btn:active { transform: translateY(0); }
        .glass-btn.admin-variant { background: rgba(107,0,32,0.9); color: white; border: 1px solid rgba(255,255,255,0.2); }
        .glass-btn.admin-variant:hover { background: rgba(107,0,32,1); }

        .glass-note-link {
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.74rem; color: rgba(255,255,255,0.65);
            text-decoration: none; transition: all 0.2s ease;
            padding: 2px 0;
        }
        .glass-note-link:hover {
            color: #d4af37;
            text-decoration: underline;
        }

        /* Links */
        .glass-link-row { text-align: center; font-size: 0.76rem; color: rgba(255,255,255,0.5); margin-top: 5px; }
        .glass-link-row a { color: rgba(212,175,55,0.95); font-weight: 700; text-decoration: none; transition: color 0.2s; }
        .glass-link-row a:hover { color: #f9e596; text-decoration: underline; }

        /* Error alert */
        .glass-alert {
            background: rgba(220,38,38,0.2); border: 1px solid rgba(220,38,38,0.4);
            color: #fca5a5; border-radius: 10px; padding: 9px 13px;
            font-size: 0.8rem; margin-bottom: 12px;
        }
        
        /* Inline Validation */
        .is-invalid {
            border: 1px solid rgba(220,38,38,0.5) !important;
            background: rgba(220,38,38,0.05) !important;
        }
        .invalid-feedback-custom {
            color: #fca5a5;
            font-size: 0.75rem;
            margin-top: 4px;
            padding-left: 12px;
            animation: fadeIn 0.3s ease;
        }

        /* Fingerprint section */
        #fingerprintSection { display: block; }

        /* ── Biometric Popup Modal (Enterprise Design System) ── */
        .bio-modal-overlay {
            position: fixed;
            inset: 0;
            min-height: 100vh;
            min-height: 100dvh;
            min-height: var(--app-height, 100dvh);
            height: 100%;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: calc(env(safe-area-inset-top, 0px) + 16px);
            padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 16px);
            padding-left: calc(env(safe-area-inset-left, 0px) + 16px);
            padding-right: calc(env(safe-area-inset-right, 0px) + 16px);
            opacity: 0;
            visibility: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            transition: opacity 0.28s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.28s;
        }
        .bio-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .bio-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(10, 15, 29, 0.82);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
        }
        .bio-modal-dialog {
            position: relative;
            width: 100%;
            max-width: 430px;
            z-index: 2;
            transform: scale(0.92) translateY(14px);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .bio-modal-overlay.active .bio-modal-dialog {
            transform: scale(1) translateY(0);
        }
        .bio-modal-card {
            background: linear-gradient(165deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.98) 100%);
            border: 1.5px solid rgba(148, 163, 184, 0.2);
            border-top: 1.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 26px;
            box-shadow: 0 25px 65px -10px rgba(0, 0, 0, 0.85), 0 0 45px rgba(212, 175, 55, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.2);
            padding: 28px 24px 22px;
            color: #f8fafc;
            text-align: center;
            position: relative;
            max-height: calc(var(--app-height, 100dvh) - env(safe-area-inset-top, 0px) - env(safe-area-inset-bottom, 0px) - 32px);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        @media (max-width: 480px) {
            .bio-modal-card {
                padding: 24px 18px 20px;
                border-radius: 22px;
            }
        }
        .bio-modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.85rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 10;
        }
        .bio-modal-close:hover {
            background: rgba(212, 175, 55, 0.2);
            border-color: rgba(212, 175, 55, 0.45);
            color: #ffffff;
            transform: rotate(90deg) scale(1.05);
        }
        .bio-modal-icon-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }
        .bio-modal-icon-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, rgba(16, 185, 129, 0.25) 0%, rgba(14, 165, 233, 0.2) 50%, rgba(15, 23, 42, 0.95) 100%);
            border: 2px solid rgba(52, 211, 153, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.15rem;
            color: #34d399;
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.3), inset 0 2px 4px rgba(255, 255, 255, 0.2);
            animation: glowPulse 2.8s infinite;
        }
        .bio-modal-badge {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #1a0808;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.6);
            border: 2.5px solid #0f172a;
        }
        .bio-modal-title {
            font-size: 1.22rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            background: linear-gradient(135deg, #ffffff 40%, #e2e8f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .bio-modal-user-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(148, 163, 184, 0.12);
            border: 1px solid rgba(148, 163, 184, 0.25);
            color: #e2e8f0;
            border-radius: 99px;
            padding: 4px 14px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-bottom: 12px;
            max-width: 90%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        .bio-modal-desc {
            font-size: 0.86rem;
            line-height: 1.55;
            color: #cbd5e1;
            margin-bottom: 16px;
            word-wrap: break-word;
        }
        .bio-modal-desc strong {
            color: #ffffff;
            font-weight: 700;
        }

        /* Modal Password Section */
        .bio-modal-card #bioModalPasswordWrap {
            margin: 14px 0 8px 0;
            text-align: left;
        }
        .bio-modal-input-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: #cbd5e1;
            margin-bottom: 7px;
        }
        .bio-modal-card .glass-input-wrap {
            position: relative !important;
            width: 100% !important;
            margin-bottom: 0 !important;
        }
        .bio-modal-card .glass-input {
            width: 100% !important;
            height: 48px !important;
            padding: 0 46px 0 44px !important;
            border-radius: 13px !important;
            border: 1.5px solid rgba(148, 163, 184, 0.28) !important;
            background: rgba(15, 23, 42, 0.7) !important;
            color: #ffffff !important;
            font-size: 0.92rem !important;
            font-family: inherit !important;
            outline: none !important;
            transition: all 0.25s ease !important;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.5) !important;
        }
        .bio-modal-card .glass-input::placeholder {
            color: rgba(255, 255, 255, 0.4) !important;
            font-size: 0.86rem !important;
        }
        .bio-modal-card .glass-input:hover {
            border-color: rgba(212, 175, 55, 0.5) !important;
            background: rgba(15, 23, 42, 0.8) !important;
        }
        .bio-modal-card .glass-input:focus {
            border-color: #d4af37 !important;
            background: rgba(15, 23, 42, 0.95) !important;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.25), inset 0 1px 3px rgba(0, 0, 0, 0.6) !important;
        }
        .bio-modal-card .glass-input-wrap .g-icon {
            position: absolute !important;
            left: 15px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: rgba(212, 175, 55, 0.85) !important;
            font-size: 1.05rem !important;
            pointer-events: none !important;
            transition: color 0.2s !important;
            z-index: 2 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .bio-modal-card .glass-input-wrap:focus-within .g-icon {
            color: #d4af37 !important;
        }
        .bio-modal-card .eye-toggle {
            position: absolute !important;
            right: 6px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: 38px !important;
            height: 38px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: transparent !important;
            border: none !important;
            color: rgba(255, 255, 255, 0.6) !important;
            cursor: pointer !important;
            border-radius: 9px !important;
            transition: all 0.2s ease !important;
            z-index: 5 !important;
            padding: 0 !important;
        }
        .bio-modal-card .eye-toggle:hover {
            color: #f5dfa8 !important;
            background: rgba(255, 255, 255, 0.1) !important;
        }
        .bio-modal-card .eye-toggle:active {
            transform: translateY(-50%) scale(0.92) !important;
        }
        .bio-modal-card .eye-toggle i {
            font-size: 1.15rem !important;
            pointer-events: none !important;
        }

        .bio-modal-steps {
            background: rgba(15, 23, 42, 0.65);
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 14px;
            padding: 14px 16px;
            text-align: left;
            margin-bottom: 20px;
        }
        .bio-modal-steps-header {
            font-size: 0.74rem;
            font-weight: 700;
            color: #d4af37;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        .bio-modal-steps-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .bio-modal-steps-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.78rem;
            color: #cbd5e1;
            line-height: 1.4;
        }
        .bio-modal-steps-list .step-num {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.2);
            color: #d4af37;
            border: 1px solid rgba(212, 175, 55, 0.4);
            font-size: 0.65rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        /* Modal Action Buttons */
        .bio-modal-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }
        .bio-modal-actions #bioModalPrimaryBtn {
            width: 100% !important;
            padding: 14px 20px !important;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 0.92rem !important;
            letter-spacing: 0.6px !important;
            text-transform: uppercase !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 14px !important;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35), 0 2px 6px rgba(0, 0, 0, 0.4) !important;
            cursor: pointer !important;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.3) !important;
        }
        .bio-modal-actions #bioModalPrimaryBtn:hover:not(:disabled) {
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5), 0 3px 8px rgba(0, 0, 0, 0.5) !important;
        }
        .bio-modal-actions #bioModalPrimaryBtn:active:not(:disabled) {
            transform: translateY(0) !important;
            box-shadow: 0 3px 12px rgba(16, 185, 129, 0.25) !important;
        }
        .bio-modal-actions #bioModalPrimaryBtn:disabled {
            opacity: 0.65 !important;
            cursor: not-allowed !important;
            transform: none !important;
        }
        .bio-modal-secondary-btn {
            width: 100%;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 12px;
            color: #cbd5e1;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            cursor: pointer;
            padding: 12px 16px;
            transition: all 0.22s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .bio-modal-secondary-btn:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-color: rgba(212, 175, 55, 0.4);
            transform: translateY(-1px);
        }
        .bio-modal-secondary-btn:active {
            transform: translateY(0);
        }

        /* Biometric Method Selection Cards */
        .bio-method-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 14px 0 12px 0;
            text-align: left;
        }
        .bio-method-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 13px 16px;
            background: linear-gradient(165deg, rgba(30, 41, 59, 0.5) 0%, rgba(15, 23, 42, 0.7) 100%);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 14px;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
            width: 100%;
            box-sizing: border-box;
            text-align: left;
        }
        .bio-method-card:hover {
            background: linear-gradient(165deg, rgba(30, 41, 59, 0.75) 0%, rgba(15, 23, 42, 0.9) 100%);
            border-color: rgba(212, 175, 55, 0.6);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }
        .bio-method-card:active {
            transform: translateY(0);
            background: rgba(212, 175, 55, 0.18);
        }
        .bio-method-left {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 0;
        }
        .bio-method-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(148, 163, 184, 0.12);
            border: 1px solid rgba(148, 163, 184, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #cbd5e1;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .bio-method-card:hover .bio-method-icon-box {
            background: rgba(212, 175, 55, 0.22);
            border-color: #d4af37;
            color: #f8fafc;
            transform: scale(1.05);
        }
        .bio-method-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        .bio-method-name {
            font-size: 0.92rem;
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: -0.2px;
        }
        .bio-method-desc {
            font-size: 0.74rem;
            color: #94a3b8;
            line-height: 1.35;
        }
        .bio-method-arrow {
            font-size: 0.95rem;
            color: rgba(212, 175, 55, 0.6);
            transition: all 0.2s;
            margin-left: 8px;
            flex-shrink: 0;
        }
        .bio-method-card:hover .bio-method-arrow {
            color: #d4af37;
            transform: translateX(3px);
        }

        /* Modal Face Recognition Camera Scanner */
        .bio-login-camera-box {
            position: relative;
            width: 100%;
            max-width: 360px;
            height: 360px;
            aspect-ratio: 1 / 1;
            margin: 0 auto;
            border-radius: 26px;
            overflow: hidden;
            background: #020617;
            border: 2px solid rgba(14, 165, 233, 0.55);
            box-shadow: 0 0 35px rgba(14, 165, 233, 0.28), inset 0 0 25px rgba(14, 165, 233, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: box-shadow 0.25s ease, border-color 0.25s ease;
        }
        @media (max-width: 480px) {
            .bio-login-camera-box {
                max-width: min(330px, 84vw);
                height: min(330px, 84vw);
                border-radius: 22px;
            }
        }
        /* Face Flash / Fill Light Controls */
        .face-hud-flash-btn {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 25;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: rgba(15, 23, 42, 0.85);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            color: #f1f5f9;
            font-size: 0.78rem;
            font-weight: 600;
            border-radius: 20px;
            cursor: pointer;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.5);
        }
        .face-hud-flash-btn:hover {
            background: rgba(30, 41, 59, 0.95);
            border-color: #facc15;
            color: #fef08a;
            transform: scale(1.05);
            box-shadow: 0 0 18px rgba(250, 204, 21, 0.6);
        }
        .face-hud-flash-btn.active-flash {
            background: linear-gradient(135deg, #facc15, #f59e0b) !important;
            border-color: #ffffff !important;
            color: #0f172a !important;
            box-shadow: 0 0 24px rgba(250, 204, 21, 0.95), 0 0 10px #ffffff !important;
            font-weight: 700;
            transform: scale(1.04);
        }
        .face-hud-flash-btn.active-flash i {
            color: #0f172a !important;
            text-shadow: 0 0 4px rgba(255, 255, 255, 0.8);
        }
        .face-hud-flash-btn.flash-suggest-pulse {
            animation: flashBtnSuggestPulse 1.4s ease-in-out infinite;
        }
        @keyframes flashBtnSuggestPulse {
            0%, 100% { border-color: rgba(250, 204, 21, 0.4); box-shadow: 0 0 0 rgba(250, 204, 21, 0); }
            50% { border-color: #facc15; box-shadow: 0 0 18px rgba(250, 204, 21, 0.75); color: #facc15; }
        }

        /* Face Screen Flash Overlay (Selfie Fill Light) */
        .face-screen-flash-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 12;
            opacity: 0;
            transition: opacity 0.25s ease, box-shadow 0.25s ease;
            border-radius: inherit;
            box-shadow: inset 0 0 70px 25px rgba(255, 255, 255, 0.95), 0 0 90px 30px rgba(255, 255, 255, 0.9);
            border: 4px solid #ffffff;
        }
        .face-screen-flash-overlay.active {
            opacity: 1;
        }

        .face-scan-frame.flash-on,
        .bio-login-camera-box.flash-on {
            border-color: #ffffff !important;
            box-shadow: 0 0 0 12px #ffffff, 0 0 90px 35px rgba(255, 255, 255, 0.95), 0 0 150px 70px rgba(255, 255, 255, 0.85) !important;
        }

        /* Modal-wide screen fill light when flash is active */
        .bio-modal-card.face-screen-fill-active {
            background: #ffffff !important;
            box-shadow: 0 0 150px 70px rgba(255, 255, 255, 0.95), 0 0 0 9999px rgba(255, 255, 255, 0.5) !important;
            color: #0f172a !important;
            border-color: #ffffff !important;
        }
        .bio-modal-card.face-screen-fill-active .bio-modal-title,
        .bio-modal-card.face-screen-fill-active #bioModalTitle {
            color: #0f172a !important;
            text-shadow: none !important;
            -webkit-text-fill-color: #0f172a !important;
        }
        .bio-modal-card.face-screen-fill-active .bio-modal-desc,
        .bio-modal-card.face-screen-fill-active #bioModalDesc {
            color: #334155 !important;
        }
        .bio-modal-card.face-screen-fill-active .bio-modal-close {
            background: rgba(15, 23, 42, 0.12) !important;
            color: #0f172a !important;
            border-color: rgba(15, 23, 42, 0.25) !important;
        }
        .bio-modal-card.face-screen-fill-active .bio-login-progress-meta {
            color: #1e293b !important;
        }

        @keyframes faceCaptureBurst {
            0% { opacity: 0; }
            20% { opacity: 1; background: #ffffff; }
            100% { opacity: 0; }
        }
        .face-scan-frame.face-flash-burst::after,
        .bio-login-camera-box.face-flash-burst::after {
            content: '';
            position: absolute;
            inset: 0;
            background: #ffffff;
            z-index: 30;
            pointer-events: none;
            border-radius: inherit;
            animation: faceCaptureBurst 0.38s ease-out forwards;
        }
        .bio-login-face-feed {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
            display: block;
        }
        .bio-login-face-holo {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.15) 0%, rgba(2, 6, 23, 0.95) 80%);
        }
        .bio-login-reticle-corner {
            position: absolute;
            width: 24px;
            height: 24px;
            border-color: #38bdf8;
            border-style: solid;
            pointer-events: none;
            z-index: 10;
        }
        .bio-login-reticle-corner.tl { top: 14px; left: 14px; border-width: 3px 0 0 3px; border-top-left-radius: 10px; }
        .bio-login-reticle-corner.tr { top: 14px; right: 14px; border-width: 3px 3px 0 0; border-top-right-radius: 10px; }
        .bio-login-reticle-corner.bl { bottom: 14px; left: 14px; border-width: 0 0 3px 3px; border-bottom-left-radius: 10px; }
        .bio-login-reticle-corner.br { bottom: 14px; right: 14px; border-width: 0 3px 3px 0; border-bottom-right-radius: 10px; }
        
        .bio-login-laser-bar {
            position: absolute;
            left: 4%;
            width: 92%;
            height: 3px;
            z-index: 4;
            background: linear-gradient(90deg, transparent 0%, #0ea5e9 25%, #38bdf8 50%, #0ea5e9 75%, transparent 100%);
            box-shadow: 0 0 16px #38bdf8;
            pointer-events: none;
            animation: bioLaserSweep 2.2s ease-in-out infinite;
        }
        @keyframes bioLaserSweep {
            0%   { top: 8%; opacity: 0.8; }
            50%  { top: 88%; opacity: 1; }
            100% { top: 8%; opacity: 0.8; }
        }
        .bio-login-hud-badge {
            position: absolute;
            bottom: 14px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 99px;
            padding: 5px 16px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.6px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 7px;
            z-index: 10;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .bio-login-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #38bdf8;
            box-shadow: 0 0 8px #38bdf8;
            animation: bioPulseDot 1.4s infinite ease-in-out;
        }
        @keyframes bioPulseDot {
            0%, 100% { opacity: 0.5; transform: scale(0.85); }
            50% { opacity: 1; transform: scale(1.25); }
        }
        .bio-login-progress-wrap {
            width: 100%;
            max-width: 360px;
            margin: 14px auto 0 auto;
        }
        .bio-login-progress-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 99px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .bio-login-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0284c7, #38bdf8);
            border-radius: 99px;
            transition: width 0.22s ease;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.5);
        }
        .bio-login-progress-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.74rem;
            color: #cbd5e1;
            margin-top: 6px;
            line-height: 1.35;
        }

        /* Desktop elevation for optical vertical centering */
        @media (min-width: 769px) {
            .auth-scene {
                padding-top: calc(env(safe-area-inset-top, 0px) + 52px);
                padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 50px);
            }
            .glass-card {
                max-width: 440px;
                padding: 24px 26px 18px;
            }
        }

        /* Mobile and Responsive */
        @media (max-width: 768px) {
            .auth-scene {
                padding-top: calc(env(safe-area-inset-top, 0px) + 52px);
                padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 32px);
                padding-left: calc(env(safe-area-inset-left, 0px) + 16px);
                padding-right: calc(env(safe-area-inset-right, 0px) + 16px);
            }
            .glass-card { 
                max-width: 100%;
                padding: 20px 20px 16px; 
                border-radius: 20px; 
                transform: none;
            }
            .top-bar {
                padding-top: calc(env(safe-area-inset-top, 0px) + 10px);
                padding-bottom: 10px;
                padding-left: calc(env(safe-area-inset-left, 0px) + 18px);
                padding-right: calc(env(safe-area-inset-right, 0px) + 18px);
            }
            .bottom-bar {
                padding-top: 8px;
                padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 8px);
                padding-left: calc(env(safe-area-inset-left, 0px) + 18px);
                padding-right: calc(env(safe-area-inset-right, 0px) + 18px);
            }
        }

        @media (max-width: 480px) {
            .auth-scene {
                padding-top: calc(env(safe-area-inset-top, 0px) + 48px);
                padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 20px);
                padding-left: calc(env(safe-area-inset-left, 0px) + 12px);
                padding-right: calc(env(safe-area-inset-right, 0px) + 12px);
            }
            .glass-card { 
                max-width: 100%;
                min-width: 260px;
                padding: 20px 16px 16px; 
                border-radius: 18px; 
                margin: auto 0;
            }
            .glass-title { font-size: 1.35rem; }
            .glass-sub { font-size: 0.8rem; margin-bottom: 14px; }
            .bottom-bar { display: none; }
            .top-bar {
                padding-top: calc(env(safe-area-inset-top, 0px) + 8px);
                padding-bottom: 8px;
                padding-left: calc(env(safe-area-inset-left, 0px) + 14px);
                padding-right: calc(env(safe-area-inset-right, 0px) + 14px);
            }
            .top-bar-brand { font-size: 0.72rem; }
        }

        @media (max-width: 360px) {
            .glass-card { 
                padding: 16px 12px 14px; 
                border-radius: 16px;
            }
            .glass-title { font-size: 1.18rem; }
            .glass-sub { font-size: 0.74rem; }
            .glass-input { font-size: 0.82rem; padding: 9px 10px 9px 36px; }
            .glass-btn { font-size: 0.84rem; padding: 10px; }
        }

        /* Medium/short desktop heights (e.g. 768p laptops or non-maximized windows) */
        @media (max-height: 760px) {
            .auth-scene {
                padding-top: calc(env(safe-area-inset-top, 0px) + 44px);
                padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 44px);
            }
            .glass-card {
                max-width: 420px;
                padding: 18px 22px 14px;
            }
            .glass-logo { width: 44px; height: 44px; margin-bottom: 6px; }
            .glass-title { font-size: 1.28rem; margin-bottom: 2px; }
            .glass-sub { font-size: 0.76rem; margin-bottom: 8px; }
            .glass-input-wrap { margin-bottom: 7px; }
            .glass-input { padding: 9px 12px 9px 38px; font-size: 0.84rem; }
            .fp-row { padding: 6px 10px; margin-bottom: 4px; }
            .glass-divider { margin: 4px 0; }
            .glass-btn { padding: 10px; font-size: 0.86rem; }
        }

        /* Landscape orientation adjustments & short screen heights */
        @media (max-height: 620px) {
            .auth-scene {
                padding-top: calc(env(safe-area-inset-top, 0px) + 36px);
                padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 16px);
            }
            .glass-card { padding: 14px 18px 12px; }
            .glass-logo { width: 40px; height: 40px; margin-bottom: 4px; }
            .glass-title { font-size: 1.2rem; margin-bottom: 2px; }
            .glass-sub { font-size: 0.72rem; margin-bottom: 6px; }
            .role-toggle { margin-bottom: 6px; padding: 2px; }
            .role-btn { padding: 5px 8px; font-size: 0.76rem; }
            .top-bar { display: none; }
            .bottom-bar { display: none; }
        }

    </style>
    <link rel="stylesheet" href="{{ asset('css/biometric-flow.css') }}?v={{ filemtime(public_path('css/biometric-flow.css')) }}">
</head>
<body>

<!-- Background -->
<div class="bg-scene"></div>

<!-- Top bar -->
<div class="top-bar">
    <div class="top-bar-brand">
        <i class="bi bi-laptop"></i>
        {{ config('app.name') }}
    </div>
</div>

<!-- Bottom bar -->
<div class="bottom-bar">
    <span>&copy; {{ date('Y') }} Osmeña Colleges. All rights reserved.</span>
    <div class="bottom-links">
        <a href="{{ route('privacy') }}">Privacy Policy</a>
        <a href="{{ route('terms') }}">Terms & Conditions</a>
        <a href="javascript:void(0)" data-footer-modal="contact">Contact Us</a>
        <span style="color: rgba(207,164,111,0.6); font-weight: 600; margin-left: 12px; pointer-events: all;" id="loginAppVersionDesktop" data-app-version-tag>
            {{ $appInstalledVersionTag ?? $appVersionTag }}
        </span>
    </div>
</div>

<!-- Footer Info Modals -->
<div id="footerModalOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:99999;backdrop-filter:blur(6px);opacity:0;transition:opacity 0.25s;">
    <div id="footerModalContent" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) scale(0.95);background:linear-gradient(145deg,#1a1218 0%,#0d0a0f 100%);border:1px solid rgba(207,164,111,0.25);border-radius:20px;padding:32px;max-width:520px;width:92%;max-height:80vh;overflow-y:auto;color:#f3e7cd;box-shadow:0 25px 60px rgba(0,0,0,0.5);transition:transform 0.25s;">
        <button type="button" id="footerModalCloseBtn" aria-label="Close" style="position:absolute;top:14px;right:14px;background:rgba(255,255,255,0.08);border:none;color:#b39b82;width:32px;height:32px;border-radius:50%;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
        <div id="footerModalBody"></div>
    </div>
</div>
<script @cspNonce>
const footerModals = {
    privacy: `<h3 style="margin:0 0 16px;color:#cfa46f;font-size:1.2rem;"><i class="bi bi-shield-lock-fill me-2"></i>Privacy Policy</h3>
        <p style="color:#b39b82;line-height:1.7;font-size:0.9rem;">The Smart Classroom Attendance System collects only the data necessary for attendance tracking. This includes:</p>
        <ul style="color:#b39b82;line-height:1.9;font-size:0.85rem;padding-left:20px;">
            <li>Student information (name, ID, course, section)</li>
            <li>Attendance records (timestamps, location data during scan)</li>
            <li>Device identifiers for binding verification</li>
            <li>Biometric credential IDs (WebAuthn — no biometric data is stored)</li>
        </ul>
        <p style="color:#b39b82;line-height:1.7;font-size:0.9rem;">Your data is stored securely and is only accessible to authorized school personnel. We do not share personal data with third parties.</p>`,
    terms: `<h3 style="margin:0 0 16px;color:#cfa46f;font-size:1.2rem;"><i class="bi bi-file-earmark-text-fill me-2"></i>Terms of Service</h3>
        <p style="color:#b39b82;line-height:1.7;font-size:0.9rem;">By using the Smart Classroom Attendance System, you agree to:</p>
        <ul style="color:#b39b82;line-height:1.9;font-size:0.85rem;padding-left:20px;">
            <li>Use the system only for legitimate attendance purposes</li>
            <li>Not share your login credentials with others</li>
            <li>Not attempt to manipulate or falsify attendance records</li>
            <li>Report any system issues to the school administration</li>
            <li>Comply with the institution's academic policies</li>
        </ul>
        <p style="color:#b39b82;line-height:1.7;font-size:0.9rem;">Violations may result in disciplinary action as determined by the institution.</p>`,
    contact: `<h3 style="margin:0 0 16px;color:#cfa46f;font-size:1.2rem;"><i class="bi bi-envelope-fill me-2"></i>Contact Us</h3>
        <p style="color:#b39b82;line-height:1.7;font-size:0.9rem;">For support or inquiries, reach out through the following channels:</p>
        <div style="margin:16px 0;padding:16px;background:rgba(255,255,255,0.04);border-radius:12px;border:1px solid rgba(255,255,255,0.08);">
            <p style="color:#f3e7cd;font-size:0.9rem;margin:0 0 8px;"><i class="bi bi-building me-2" style="color:#cfa46f;"></i><strong>School Administration Office</strong></p>
            <p style="color:#b39b82;font-size:0.85rem;margin:0 0 6px;"><i class="bi bi-envelope me-2"></i>admin@school.edu.ph</p>
            <p style="color:#b39b82;font-size:0.85rem;margin:0 0 6px;"><i class="bi bi-telephone me-2"></i>(02) 8123-4567</p>
            <p style="color:#b39b82;font-size:0.85rem;margin:0;"><i class="bi bi-clock me-2"></i>Mon–Fri, 8:00 AM – 5:00 PM</p>
        </div>
        <p style="color:#b39b82;line-height:1.7;font-size:0.9rem;">For technical issues with the attendance system, please contact your class adviser or the IT department.</p>`
};
function showFooterModal(type) {
    var body = document.getElementById('footerModalBody');
    if (body) body.innerHTML = footerModals[type] || '';
    const overlay = document.getElementById('footerModalOverlay');
    if (overlay) {
        overlay.style.display = 'block';
        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
            var content = document.getElementById('footerModalContent');
            if (content) content.style.transform = 'translate(-50%,-50%) scale(1)';
        });
    }
}
function closeFooterModal() {
    const overlay = document.getElementById('footerModalOverlay');
    if (!overlay) return;
    overlay.style.opacity = '0';
    var content = document.getElementById('footerModalContent');
    if (content) content.style.transform = 'translate(-50%,-50%) scale(0.95)';
    setTimeout(() => { overlay.style.display = 'none'; }, 250);
}
window.showFooterModal = showFooterModal;
window.closeFooterModal = closeFooterModal;

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-footer-modal]').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var type = link.getAttribute('data-footer-modal');
            showFooterModal(type);
        });
    });
    var overlay = document.getElementById('footerModalOverlay');
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeFooterModal();
            }
        });
    }
    var closeBtn = document.getElementById('footerModalCloseBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeFooterModal();
        });
    }
});

function toggleEye(inputId, btn, e) {
    if (e) {
        if (e._pwToggled) return;
        e._pwToggled = true;
        if (e.preventDefault) e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
    }

    let button = btn;
    if (!button && e && e.target) {
        button = e.target.closest('.eye-toggle, .eye-btn, [data-toggle-password], [id^="btn-toggle-password"]');
    }

    // Debounce protection per button (300ms) to prevent duplicate triggers (pointerdown + click)
    const now = Date.now();
    if (button) {
        if (button._lastToggleTime && (now - button._lastToggleTime < 300)) {
            return;
        }
        button._lastToggleTime = now;
    }

    if (!inputId && button) {
        inputId = button.getAttribute('data-toggle-password') || button.getAttribute('aria-controls');
    }

    let input = null;
    if (typeof inputId === 'string') {
        input = document.getElementById(inputId);
    } else if (inputId && inputId.nodeType === 1) {
        input = inputId;
    }

    if (!input && button && button.parentElement) {
        input = button.parentElement.querySelector('input[type="password"], input[type="text"]');
    }
    if (!input) return;

    if (!button) {
        const id = input.id;
        if (id) {
            button = document.querySelector(`button[data-toggle-password="${id}"], button[aria-controls="${id}"], button[onclick*="${id}"]`);
        }
        if (!button && input.parentElement) {
            button = input.parentElement.querySelector('.eye-toggle, .eye-btn, [data-toggle-password], [id^="btn-toggle-password"]');
        }
    }

    // Capture focus state and selection range
    const isCurrentlyFocused = (document.activeElement === input);
    let start = null;
    let end = null;
    try {
        start = input.selectionStart;
        end = input.selectionEnd;
    } catch (err) {}

    // 4. Dedicated visibility state toggle
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    // 8. Synchronize icon and accessibility state
    if (button) {
        const icon = button.querySelector('i');
        if (icon) {
            // Password hidden: Eye-off icon (bi bi-eye-slash)
            // Password visible: Eye icon (bi bi-eye)
            icon.className = isPassword ? 'bi bi-eye' : 'bi bi-eye-slash';
        }
        const isConf = input.name === 'password_confirmation' || (input.id && (input.id.includes('2') || input.id.includes('conf') || input.id.endsWith('_confirmation')));
        const label = isPassword 
            ? (isConf ? 'Hide password confirmation' : 'Hide password')
            : (isConf ? 'Show password confirmation' : 'Show password');
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
    }

    // 6. Keep input focus and restore cursor without page jump
    if (isCurrentlyFocused) {
        try {
            input.focus({ preventScroll: true });
            if (start !== null && end !== null) {
                input.setSelectionRange(start, end);
            }
        } catch (err) {}
    }
}
window.toggleEye = toggleEye;
window.togglePw = toggleEye;
window.togglePassword = toggleEye;

function setupPasswordToggleListeners() {
    // 1. Pointerdown (touch and mouse down):
    // Prevents active input blur and virtual keyboard dismissal on mobile, triggers toggle instantly
    document.addEventListener('pointerdown', function(e) {
        if (e.button !== undefined && e.button !== 0) return;
        const btn = e.target.closest('.eye-toggle, .eye-btn, [data-toggle-password], [id^="btn-toggle-password"]');
        if (!btn) return;

        e.preventDefault(); // Prevents input from losing focus / virtual keyboard from closing
        const targetId = btn.getAttribute('data-toggle-password') || btn.getAttribute('aria-controls');
        let input = targetId ? document.getElementById(targetId) : null;
        if (!input && btn.parentElement) {
            input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
        }
        toggleEye(input, btn, e);
    });

    // 2. Touchstart fallback for environments without PointerEvent
    if (!window.PointerEvent) {
        document.addEventListener('touchstart', function(e) {
            const btn = e.target.closest('.eye-toggle, .eye-btn, [data-toggle-password], [id^="btn-toggle-password"]');
            if (!btn) return;

            e.preventDefault();
            const targetId = btn.getAttribute('data-toggle-password') || btn.getAttribute('aria-controls');
            let input = targetId ? document.getElementById(targetId) : null;
            if (!input && btn.parentElement) {
                input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
            }
            toggleEye(input, btn, e);
        }, { passive: false });
    }

    // 3. Click handler (standard desktop click / fallback)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.eye-toggle, .eye-btn, [data-toggle-password], [id^="btn-toggle-password"]');
        if (!btn) return;

        e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();

        const targetId = btn.getAttribute('data-toggle-password') || btn.getAttribute('aria-controls');
        let input = targetId ? document.getElementById(targetId) : null;
        if (!input && btn.parentElement) {
            input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
        }
        toggleEye(input, btn, e);
    });

    // 4. Keyboard accessibility (Space and Enter)
    document.addEventListener('keydown', function(e) {
        if (e.key === ' ' || e.key === 'Enter' || e.keyCode === 32 || e.keyCode === 13) {
            const btn = e.target.closest('.eye-toggle, .eye-btn, [data-toggle-password], [id^="btn-toggle-password"]');
            if (!btn) return;

            e.preventDefault();
            if (e.stopPropagation) e.stopPropagation();

            const targetId = btn.getAttribute('data-toggle-password') || btn.getAttribute('aria-controls');
            let input = targetId ? document.getElementById(targetId) : null;
            if (!input && btn.parentElement) {
                input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
            }
            toggleEye(input, btn, e);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupPasswordToggleListeners);
} else {
    setupPasswordToggleListeners();
}
</script>


<!-- Auth scene -->
<div class="auth-scene">
    <div class="glass-card">

        <!-- Logo + heading -->
        <div class="text-center">
            <div class="anim-fade-up anim-d1" style="display:flex; justify-content:center;">
                <div class="glass-logo">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo">
                </div>
            </div>
            <div class="glass-badge anim-fade-up anim-d2">Attendance Checker</div>
            <div class="glass-title anim-fade-up anim-d2">Welcome back</div>
            <div class="glass-sub anim-fade-up anim-d3">Sign in with your Student ID, Email, or Mobile</div>
        </div>

        <!-- Errors -->
        @if(session('error'))
        <div class="glass-alert"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="glass-alert"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}</div>
        @endif

        <!-- Single unified form -->
        <form method="POST" action="{{ route('login.submit') }}" id="loginForm">
            @csrf
            <input type="hidden" name="device_fingerprint" id="deviceFingerprint">
            <input type="hidden" name="device_key" id="deviceKey">
            @php
                $qrToken = old('qr_token', session('qr_token') ?? request('qr_token'));
            @endphp
            @if($qrToken)
                <input type="hidden" name="qr_token" value="{{ $qrToken }}">
            @endif


            <!-- ID, Email, or Mobile  -  system detects role automatically -->
            <div class="glass-input-wrap anim-fade-up anim-d4" style="position: relative;">
                <i class="bi bi-person-fill g-icon"></i>
                <input type="text" name="identifier" id="idInput"
                       class="glass-input @error('identifier') is-invalid @enderror"
                       placeholder="Student ID, Email, or Mobile Number"
                       required autocomplete="username"
                       value="{{ old('identifier') }}"
                       style="padding-right: 42px;">
                <button type="button" id="clearIdBtn" title="Switch account / Clear" aria-label="Switch account or clear input"
                        style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: rgba(255,255,255,0.6); font-size: 0.85rem; cursor: pointer; padding: 4px 7px; border-radius: 6px; display: none; transition: all 0.2s;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            @error('identifier')
                <div class="invalid-feedback-custom anim-fade-up anim-d4">{{ $message }}</div>
            @enderror

            <!-- Biometric Authentication (WebAuthn supported) -->
            <div id="fingerprintSection" style="display: block;">
                <!-- Inline message area for biometric feedback -->
                <div id="fpMessage" role="status" style="display:none;"></div>
                <button type="button" class="fp-row anim-fade-up anim-d5" id="fpRowBtn" aria-label="Sign in with Biometrics" aria-haspopup="dialog" aria-controls="biometricModal">
                    <div class="fp-row-left">
                        <div class="fp-icon-wrap">
                            <i class="bi bi-fingerprint" id="fpIcon"></i>
                        </div>
                        <div>
                            <div class="fp-row-label" id="fpLabel">Sign in with Biometrics</div>
                            <div class="fp-row-hint" id="fpHint">Fingerprint, Face ID, or device security</div>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right fp-row-arrow" id="fpArrow"></i>
                </button>
                <div class="glass-divider anim-fade-up anim-d5">or use password</div>
            </div>

            <!-- Password -->
            <div class="glass-input-wrap anim-fade-up anim-d5" style="margin-bottom: 7px;">
                <i class="bi bi-lock-fill g-icon"></i>
                <input type="password" name="password" id="loginPassword"
                       class="glass-input has-eye @error('password') is-invalid @enderror"
                       placeholder="Password" required autocomplete="current-password">
                <button type="button" class="eye-toggle" data-toggle-password="loginPassword" aria-controls="loginPassword" aria-label="Show password" title="Show password" aria-pressed="false">
                    <i class="bi bi-eye-slash"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback-custom anim-fade-up anim-d5" style="margin-top:-10px;margin-bottom:12px;">{{ $message }}</div>
            @enderror
            <!-- Remember Me & Forgot Password Row -->
            <div class="d-flex align-items-center justify-content-between anim-fade-up anim-d5" style="font-size: 0.82rem; padding: 0 2px; margin-bottom: 9px;">
                <label style="display: inline-flex; align-items: center; gap: 7px; cursor: pointer; user-select: none; margin: 0; color: rgba(255,255,255,0.85); font-weight: 500;">
                    <input type="checkbox" name="remember" id="rememberMe" value="1" checked style="width: 16px; height: 16px; accent-color: #d4af37; cursor: pointer; border-radius: 4px;">
                    <span>Remember me</span>
                </label>
                <a href="{{ route('otp.forgot.form') }}" id="forgotPasswordLink" style="color: rgba(212,175,55,0.9); text-decoration: none; font-weight: 500; transition: color 0.2s;">
                    Forgot password?
                </a>
            </div>

            <button type="submit" class="glass-btn glass-btn-primary anim-fade-up anim-d6" id="loginSubmitBtn">
                <i class="bi bi-box-arrow-in-right me-2"></i>SIGN IN
            </button>
        </form>

        <form method="POST" action="{{ route('recovery.login') }}" id="recoveryForm" style="display:none;">
            @csrf
            <input type="hidden" name="device_fingerprint" id="recoveryDeviceFingerprint">
            <input type="hidden" name="device_key" id="recoveryDeviceKey">
            <div class="glass-input-wrap anim-fade-up anim-d4">
                <i class="bi bi-person-fill g-icon"></i>
                <input type="text" name="identifier" class="glass-input" placeholder="Student ID, Email, or Mobile Number" required autocomplete="username">
            </div>
            <div class="glass-input-wrap mb-3 anim-fade-up anim-d5">
                <i class="bi bi-key-fill g-icon"></i>
                <input type="text" name="recovery_code" class="glass-input" placeholder="Recovery Code (e.g. A1B2-C3D4)" required>
            </div>
            <button type="button" class="glass-btn glass-btn-secondary anim-fade-up anim-d6 mb-2" id="backToLoginBtn">
                <i class="bi bi-arrow-left me-2"></i>BACK TO LOGIN
            </button>
            <button type="submit" class="glass-btn glass-btn-primary anim-fade-up anim-d6" id="recoverySubmitBtn">
                <i class="bi bi-box-arrow-in-right me-2"></i>SIGN IN WITH CODE
            </button>
        </form>

        <div style="text-align:center;margin-top:6px;" class="anim-fade-up anim-d7">
            <a href="#" id="useRecoveryCodeLink" class="glass-note-link">
                <i class="bi bi-key me-1"></i>Use Recovery Code
            </a>
        </div>
        <div class="glass-link-row anim-fade-up anim-d7" style="margin-top:4px;">
            Don't have an account? <a href="{{ route('register') }}">Register here</a>
        </div>
        
        {{-- Version Badge - Visible on Mobile (when bottom bar is hidden) --}}
        <div class="d-block d-md-none text-center anim-fade-up anim-d7" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.08);">
            <span style="font-size: 0.7rem; color: rgba(207,164,111,0.5); font-weight: 600; letter-spacing: 0.5px;" id="loginAppVersionMobile" data-app-version-tag>
                {{ $appInstalledVersionTag ?? $appVersionTag }}
            </span>
        </div>

        {{-- Install App Button — Mobile only (hidden on desktop via d-md-none), always visible unless already installed --}}
        <div id="smartAppDownloadRow" class="d-md-none" style="text-align: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.1);">
            <div style="font-size: 0.72rem; color: rgba(207,164,111,0.75); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                <i class="bi bi-phone" style="font-size: 0.85rem; color: var(--gold, #CFA46F);"></i>
                <span>Install for Quick Access</span>
            </div>
            <div>
                <button id="downloadAppBtn" type="button" class="pwa-install-trigger"
                    style="display: inline-flex; align-items: center; justify-content: center; gap: 10px;
                           background: linear-gradient(135deg, rgba(212,175,55,0.22), rgba(207,164,111,0.15));
                           color: #CFA46F;
                           border: 1.5px solid rgba(207,164,111,0.55); border-radius: 14px;
                           padding: 13px 30px; font-size: 0.9rem; font-weight: 700;
                           cursor: pointer; transition: all 0.2s cubic-bezier(0.16,1,0.3,1);
                           box-shadow: 0 4px 16px rgba(0,0,0,0.3); letter-spacing: 0.3px;
                           -webkit-tap-highlight-color: transparent;">
                    <i class="bi bi-arrow-bar-down" style="font-size: 1.1rem;"></i>
                    <span id="downloadAppBtnText">Install App</span>
                </button>
            </div>
            <div style="margin-top: 8px;">
                <a href="{{ \Illuminate\Support\Facades\Route::has('pwa.download.apk') ? route('pwa.download.apk') : url('/download/apk') }}" download="SmartAttendance.apk"
                   style="font-size: 0.76rem; color: rgba(207,164,111,0.65); text-decoration: underline; font-weight: 500; transition: color 0.2s;">
                    <i class="bi bi-download me-1"></i>Or download direct Android APK (.apk)
                </a>
            </div>
        </div>

    </div>
</div>



<!-- Biometric Not Registered / Setup & Status Modal -->
<div id="biometricModal" class="bio-modal-overlay" style="display:none;" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="bioModalTitle" aria-describedby="bioModalDesc">
    <div class="bio-modal-backdrop" id="bioModalBackdrop"></div>
    <div class="bio-modal-dialog">
        <div class="bio-modal-card">
            <!-- Close button -->
            <button type="button" class="bio-modal-close" id="bioModalCloseBtn" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>

            <header class="bio-dialog-heading">
                <div class="bio-dialog-icon" aria-hidden="true">
                    <i class="bi bi-fingerprint" id="bioModalIcon"></i>
                </div>
                <h2 class="bio-modal-title" id="bioModalTitle" tabindex="-1">Biometric sign-in</h2>
                <div class="bio-modal-user-pill" id="bioModalUserPill" style="display:none;">
                    <i class="bi bi-person-badge" aria-hidden="true"></i><span id="bioModalUserText"></span>
                </div>
            </header>
            <p class="bio-modal-desc" id="bioModalDesc">
                Biometric login is not registered for this account. Please use your password or register your biometrics first.
            </p>

            <!-- Biometric Method Selection List -->
            <div id="bioModalMethodsWrap" style="display:none;">
                <div class="bio-method-list" id="bioModalMethodsList"></div>
            </div>

            <div id="bioModalProgress" class="bio-dialog-progress" role="status" hidden>
                <span id="bioModalProgressIcon" class="biometric-spinner" aria-hidden="true"></span>
                <span id="bioModalProgressText"></span>
            </div>
            <!-- Inline Password Verification Field (for setup flow) -->
            <div id="bioModalPasswordWrap" style="display:none;">
                <label class="bio-modal-input-label" for="bioModalPasswordInput">Account password</label>
                <div class="glass-input-wrap">
                    <i class="bi bi-lock-fill g-icon"></i>
                    <input type="password" id="bioModalPasswordInput" class="glass-input has-eye" placeholder="Enter your password" autocomplete="current-password">
                    <button type="button" class="eye-toggle" data-toggle-password="bioModalPasswordInput" aria-controls="bioModalPasswordInput" aria-label="Show password" title="Show password" aria-pressed="false">
                        <i class="bi bi-eye-slash"></i>
                    </button>
                </div>
            </div>

            <!-- Inline Alert Message -->
            <!-- Action Buttons -->
            <div class="bio-modal-actions">
                <button type="button" class="glass-btn glass-btn-primary" id="bioModalPrimaryBtn">
                    <i class="bi bi-shield-lock-fill me-2"></i>Set Up Biometrics
                </button>
                <button type="button" class="bio-modal-secondary-btn" id="bioModalChooseMethodBtn" style="display:none;">
                    <i class="bi bi-grid-fill me-1"></i>Choose Another Method
                </button>
                <button type="button" class="bio-modal-secondary-btn" id="bioModalSecondaryBtn">
                    Use Password
                </button>
            </div>
        </div>
    </div>
</div>

<script @cspNonce>
// ── PWA INSTALL BUTTON CONTROLLER (LOGIN PAGE) ──────────────────────────────
(function() {
    function checkStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               document.referrer.includes('android-app://') ||
               window.matchMedia('(display-mode: fullscreen)').matches;
    }

    function checkInstalledFlag() {
        return localStorage.getItem('pwa_app_installed') === 'true';
    }

    async function checkRelatedApps() {
        if ('getInstalledRelatedApps' in navigator) {
            try {
                var related = await navigator.getInstalledRelatedApps();
                if (related && related.length > 0) {
                    localStorage.setItem('pwa_app_installed', 'true');
                    return true;
                }
            } catch(e) {}
        }
        return false;
    }

    function hideInstallRow() {
        var mobileRow = document.getElementById('smartAppDownloadRow');
        if (mobileRow) {
            mobileRow.style.display = 'none';
        }
    }

    async function updateInstallVisibility() {
        // 1. Immediate checks (synchronous)
        if (checkStandalone() || checkInstalledFlag()) {
            hideInstallRow();
            return;
        }

        // 2. Async check via getInstalledRelatedApps API
        var relatedInstalled = await checkRelatedApps();
        if (relatedInstalled) {
            hideInstallRow();
        }
    }

    // Direct click handlers forwarding to window.triggerPwaInstall if available
    var downloadBtn = document.getElementById('downloadAppBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof window.triggerPwaInstall === 'function') {
                window.triggerPwaInstall(downloadBtn);
            }
        });
    }

    // Hide immediately when PWA is installed during this session
    window.addEventListener('appinstalled', function() {
        localStorage.setItem('pwa_app_installed', 'true');
        hideInstallRow();
    });

    // Check visibility on load, DOM ready, and focus
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { updateInstallVisibility(); });
    } else {
        updateInstallVisibility();
    }
    window.addEventListener('load', function() { updateInstallVisibility(); });
    window.addEventListener('focus', function() { updateInstallVisibility(); });
})();

// Reliable Device Fingerprint & Key initialization
(function() {
    function initDeviceTokens() {
        var devKey = (typeof window.getOrCreateDeviceKey === 'function')
            ? window.getOrCreateDeviceKey()
            : (localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || '');
        var fpInput = document.getElementById('deviceFingerprint');
        var keyInput = document.getElementById('deviceKey');
        var rfpInput = document.getElementById('recoveryDeviceFingerprint');
        var rkeyInput = document.getElementById('recoveryDeviceKey');

        if (fpInput && !fpInput.value && devKey) fpInput.value = devKey;
        if (keyInput && !keyInput.value && devKey) keyInput.value = devKey;
        if (rfpInput && !rfpInput.value && devKey) rfpInput.value = devKey;
        if (rkeyInput && !rkeyInput.value && devKey) rkeyInput.value = devKey;
    }
    initDeviceTokens();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDeviceTokens);
    }
    window.addEventListener('load', initDeviceTokens);

    // Optional FingerprintJS refinement if available
    try {
        const fpPromise = import('https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@4/+esm')
            .then(FingerprintJS => FingerprintJS.load())
            .catch(() => null);

        if (fpPromise) {
            fpPromise.then(fp => {
                if (!fp) return;
                return fp.get();
            }).then(result => {
                if (!result || !result.visitorId) return;
                var fpInput = document.getElementById('deviceFingerprint');
                if (fpInput && !fpInput.value) {
                    fpInput.value = result.visitorId;
                }
            }).catch(() => {});
        }
    } catch (e) {}
})();

// Multi-Account Storage & Account Switcher on Device
var idInput = document.getElementById('idInput');
var rememberCheckbox = document.getElementById('rememberMe');
var clearIdBtn = document.getElementById('clearIdBtn');

function getSavedAccounts() {
    var accounts = [];
    try {
        var raw = localStorage.getItem('attendance_saved_accounts');
        if (raw) {
            var parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                accounts = parsed.filter(function(a) { 
                    return a && a.identifier && typeof a.identifier === 'string' && a.identifier.trim(); 
                });
            }
        }
    } catch (e) {}

    // Legacy migration: If single saved identifier exists and not yet in accounts list
    try {
        var legacyId = localStorage.getItem('attendance_saved_identifier');
        if (legacyId && legacyId.trim()) {
            var trimmed = legacyId.trim();
            var exists = accounts.some(function(a) { return a.identifier.toLowerCase() === trimmed.toLowerCase(); });
            if (!exists) {
                accounts.unshift({
                    identifier: trimmed,
                    name: trimmed,
                    role: '',
                    lastLogin: Date.now()
                });
                localStorage.setItem('attendance_saved_accounts', JSON.stringify(accounts));
            }
        }
    } catch (e) {}

    return accounts;
}
window.getSavedAccounts = getSavedAccounts;

function saveAccount(acc) {
    if (!acc || !acc.identifier || typeof acc.identifier !== 'string') return;
    var id = acc.identifier.trim();
    if (!id) return;

    try {
        var accounts = getSavedAccounts();
        // Remove existing entry for this identifier (case-insensitive)
        accounts = accounts.filter(function(a) {
            return a.identifier.toLowerCase() !== id.toLowerCase();
        });
        accounts.unshift({
            identifier: id,
            name: (acc.name && acc.name.trim()) ? acc.name.trim() : id,
            role: acc.role || '',
            lastLogin: Date.now()
        });
        if (accounts.length > 8) {
            accounts = accounts.slice(0, 8);
        }
        localStorage.setItem('attendance_saved_accounts', JSON.stringify(accounts));
        localStorage.setItem('attendance_saved_identifier', id);
        if (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.setLastIdentifier === 'function') {
            window.AndroidDeviceBridge.setLastIdentifier(id);
        }
    } catch (e) {}
    updateAccountBanner();
}
window.saveAccount = saveAccount;

function removeSavedAccount(identifier) {
    if (!identifier) return;
    var id = identifier.trim().toLowerCase();
    try {
        var accounts = getSavedAccounts().filter(function(a) {
            return a.identifier.toLowerCase() !== id;
        });
        localStorage.setItem('attendance_saved_accounts', JSON.stringify(accounts));
        var activeId = (localStorage.getItem('attendance_saved_identifier') || '').toLowerCase();
        if (activeId === id) {
            if (accounts.length > 0) {
                localStorage.setItem('attendance_saved_identifier', accounts[0].identifier);
                if (idInput && idInput.value.toLowerCase() === id) {
                    idInput.value = accounts[0].identifier;
                }
            } else {
                localStorage.removeItem('attendance_saved_identifier');
                if (idInput && idInput.value.toLowerCase() === id) {
                    idInput.value = '';
                }
            }
        }
    } catch (e) {}
    updateAccountBanner();
}
window.removeSavedAccount = removeSavedAccount;

function clearAllSavedAccounts() {
    try {
        localStorage.removeItem('attendance_saved_accounts');
        localStorage.removeItem('attendance_saved_identifier');
        if (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.setLastIdentifier === 'function') {
            window.AndroidDeviceBridge.setLastIdentifier('');
        }
    } catch (e) {}
    if (idInput) idInput.value = '';
    var pass = document.getElementById('loginPassword');
    if (pass) pass.value = '';
    updateAccountBanner();
    hideFpMessage();
}
window.clearAllSavedAccounts = clearAllSavedAccounts;

function renderSavedAccounts() {
    // Multi-account switcher on device removed per design
}

function updateBiometricHint() {
    if (typeof isBioPending !== 'undefined' && isBioPending) return;
    var curVal = idInput ? idInput.value.trim() : '';
    if (typeof fpHint !== 'undefined' && fpHint) {
        if (curVal) {
            var accounts = getSavedAccounts();
            var match = accounts.find(function(a) { return a.identifier.toLowerCase() === curVal.toLowerCase(); });
            var displayName = match ? (match.name || match.identifier) : curVal;
            fpHint.textContent = 'Touch sensor for ' + displayName;
        } else {
            fpHint.textContent = 'Fingerprint, Face ID, or device security (Any user)';
        }
    }
}

function updateAccountBanner() {
    updateClearBtnVisibility();
    renderSavedAccounts();
    updateBiometricHint();
}

function updateClearBtnVisibility() {
    if (!clearIdBtn || !idInput) return;
    if (idInput.value && idInput.value.trim().length > 0) {
        clearIdBtn.style.display = 'block';
    } else {
        clearIdBtn.style.display = 'none';
    }
}

function clearSavedAccount() {
    if (idInput) {
        idInput.value = '';
        idInput.focus();
    }
    var pass = document.getElementById('loginPassword');
    if (pass) {
        pass.value = '';
    }
    updateAccountBanner();
    hideFpMessage();
    if (typeof updateForgotHref === 'function') {
        updateForgotHref();
    }
}

try {
    var savedAccounts = getSavedAccounts();
    var savedId = localStorage.getItem('attendance_saved_identifier');
    if ((!savedId || !savedId.trim()) && savedAccounts.length > 0) {
        savedId = savedAccounts[0].identifier;
    }
    if ((!savedId || !savedId.trim()) && window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.getLastIdentifier === 'function') {
        savedId = window.AndroidDeviceBridge.getLastIdentifier();
    }
    if (savedId && idInput && !idInput.value) {
        idInput.value = savedId;
        if (rememberCheckbox) rememberCheckbox.checked = true;
    }
} catch (e) {}
updateAccountBanner();

if (clearIdBtn) {
    clearIdBtn.addEventListener('click', function(e) {
        e.preventDefault();
        clearSavedAccount();
    });
}


function clearErrorStates() {
    var alerts = document.querySelectorAll('.glass-alert, .invalid-feedback-custom');
    alerts.forEach(function(el) { el.style.display = 'none'; });
    if (idInput) idInput.classList.remove('is-invalid');
    var pass = document.getElementById('loginPassword');
    if (pass) pass.classList.remove('is-invalid');
    updateAccountBanner();
}

if (idInput) {
    idInput.addEventListener('input', function() {
        clearErrorStates();
        updateAccountBanner();
    });
    idInput.addEventListener('change', updateAccountBanner);
}
var passInput = document.getElementById('loginPassword');
if (passInput) {
    passInput.addEventListener('input', clearErrorStates);
}

// Loading state and remember credentials on submit
var loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function() {
        clearErrorStates();

        if (idInput && idInput.value) {
            idInput.value = idInput.value.trim();
            if (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.setLastIdentifier === 'function') {
                window.AndroidDeviceBridge.setLastIdentifier(idInput.value);
            }
        }

        try {
            var devKey = (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.getNativeDeviceId === 'function')
                ? window.AndroidDeviceBridge.getNativeDeviceId()
                : ((typeof window.getOrCreateDeviceKey === 'function')
                    ? window.getOrCreateDeviceKey()
                    : (localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || ''));
            var fpInput = document.getElementById('deviceFingerprint');
            var keyInput = document.getElementById('deviceKey');
            if (fpInput && devKey) fpInput.value = devKey;
            if (keyInput && devKey) keyInput.value = devKey;
        } catch (e) {}

        try {
            if (rememberCheckbox && rememberCheckbox.checked && idInput && idInput.value) {
                saveAccount({ identifier: idInput.value.trim(), name: idInput.value.trim() });
            } else if (rememberCheckbox && !rememberCheckbox.checked && idInput && idInput.value) {
                removeSavedAccount(idInput.value.trim());
            }
        } catch (e) {}

        var btn = document.getElementById('loginSubmitBtn');
        if (btn) {
            setTimeout(function() {
                btn.disabled = true;
                btn.innerHTML = '<span class="btn-spinner"></span>SIGNING IN...';
            }, 10);
        }
    });
}

// Switch between Login Form and Recovery Code Form
var useRecoveryCodeLink = document.getElementById('useRecoveryCodeLink');
if (useRecoveryCodeLink) {
    useRecoveryCodeLink.addEventListener('click', function(e) {
        e.preventDefault();
        var lf = document.getElementById('loginForm');
        var rf = document.getElementById('recoveryForm');
        if (lf) lf.style.display = 'none';
        if (rf) rf.style.display = 'block';
    });
}

var recoveryForm = document.getElementById('recoveryForm');
if (recoveryForm) {
    recoveryForm.addEventListener('submit', function() {
        try {
            var devKey = (typeof window.getOrCreateDeviceKey === 'function')
                ? window.getOrCreateDeviceKey()
                : (localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || '');
            var rfpInput = document.getElementById('recoveryDeviceFingerprint');
            var rkeyInput = document.getElementById('recoveryDeviceKey');
            if (rfpInput && devKey) rfpInput.value = devKey;
            if (rkeyInput && devKey) rkeyInput.value = devKey;
        } catch (e) {}

        var rbtn = document.getElementById('recoverySubmitBtn');
        if (rbtn) {
            setTimeout(function() {
                rbtn.disabled = true;
                rbtn.innerHTML = '<span class="btn-spinner"></span>SIGNING IN...';
            }, 10);
        }
    });
}

var backToLoginBtn = document.getElementById('backToLoginBtn');
if (backToLoginBtn) {
    backToLoginBtn.addEventListener('click', function(e) {
        e.preventDefault();
        var lf = document.getElementById('loginForm');
        var rf = document.getElementById('recoveryForm');
        if (rf) rf.style.display = 'none';
        if (lf) lf.style.display = 'block';
    });
}

// ── BIOMETRIC / WEBAUTHN AUTHENTICATION ─────────────────────────────────────
var fpSec = document.getElementById('fingerprintSection');
var fpRowBtn = document.getElementById('fpRowBtn');
var fpLabel = document.getElementById('fpLabel');
var fpHint = document.getElementById('fpHint');
var fpIcon = document.getElementById('fpIcon');
var fpArrow = document.getElementById('fpArrow');

var bioAbortController = null;
var biometricReturnFocus = null;
var biometricPreviousOverflow = '';
var isBioPending = false;
var biometricRedirectStarted = false;

function completeBiometricLogin(result) {
    if (biometricRedirectStarted) return;
    biometricRedirectStarted = true;
    isBioPending = true;
    if (fpLabel) fpLabel.textContent = '✓ Authenticated! Opening dashboard...';
    if (fpHint) fpHint.textContent = 'Loading your dashboard';
    if (fpIcon) fpIcon.className = 'bi bi-check-circle-fill text-success';
    if (fpArrow) fpArrow.className = 'bi bi-check2 fp-row-arrow text-success';
    if (fpRowBtn) {
        fpRowBtn.disabled = true;
        fpRowBtn.style.opacity = '1';
        fpRowBtn.removeAttribute('title');
    }
    if (typeof showBiometricProgress === 'function') {
        showBiometricProgress('Signed in', 'Opening your dashboard.', '', 'Opening dashboard…', { complete: true });
    }

    // Begin the navigation before optional account storage or native bridge
    // calls. A slow device bridge must not hold up the dashboard request.
    window.location.replace(result.redirect || '{{ route("home") }}');

    if (result.user && result.user.identifier) {
        try {
            saveAccount({
                identifier: result.user.identifier,
                name: result.user.name,
                role: result.user.role
            });
        } catch (e) {}
    }
}

function getEffectiveRpId(serverRpId) {
    var hostname = window.location.hostname;
    if (!hostname) return undefined;
    var isIp = /^(\d{1,3}\.){3}\d{1,3}$/.test(hostname) || hostname.includes(':');
    if (isIp) {
        return undefined; // IP cannot be rpId in WebAuthn
    }
    if (serverRpId && typeof serverRpId === 'string') {
        var s = serverRpId.toLowerCase().trim();
        var h = hostname.toLowerCase().trim();
        if (h === s || h.endsWith('.' + s)) {
            return s;
        }
    }
    return hostname;
}

function normalizeBase64(base64) {
    base64 = (base64 || '').replace(/-/g, '+').replace(/_/g, '/');
    var padding = base64.length % 4;
    if (padding) base64 += '===='.slice(padding);
    return base64;
}

function base64ToUint8Array(base64) {
    base64 = normalizeBase64(base64);
    var binary = atob(base64);
    var bytes = new Uint8Array(binary.length);
    for (var i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
}

function bufferToBase64Url(buffer) {
    var bytes = new Uint8Array(buffer);
    var binary = '';
    for (var i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function resetBiometricButton() {
    isBioPending = false;
    bioAbortController = null;
    if (typeof stopFaceRecognitionLoginCamera === 'function') stopFaceRecognitionLoginCamera();
    if (!fpRowBtn) return;
    fpRowBtn.disabled = false;
    fpRowBtn.style.opacity = '1';
    fpRowBtn.style.cursor = 'pointer';
    fpRowBtn.removeAttribute('title');
    if (fpLabel) fpLabel.textContent = 'Sign in with Biometrics';
    if (fpHint) fpHint.textContent = 'Fingerprint, Face ID, or device security';
    if (fpIcon) fpIcon.className = 'bi bi-fingerprint';
    if (fpArrow) fpArrow.className = 'bi bi-chevron-right fp-row-arrow';
}

function biometricText(copy) {
    return String(copy || '')
        .replace(/^<i class="bi [^"]+"><\/i>/, '')
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<\/?strong>/gi, '')
        .trim();
}

function showFpMessage(type, html) {
    var el = document.getElementById('fpMessage');
    if (!el) return;
    var styles = {
        info:    'background:rgba(212,175,55,0.15);border:1px solid rgba(212,175,55,0.4);color:#f3e7cd;',
        warning: 'background:rgba(248,113,113,0.15);border:1px solid rgba(248,113,113,0.4);color:#fca5a5;',
        error:   'background:rgba(220,38,38,0.2);border:1px solid rgba(220,38,38,0.4);color:#fca5a5;',
        success: 'background:rgba(74,222,128,0.15);border:1px solid rgba(74,222,128,0.4);color:#86efac;'
    };
    el.style.cssText = (styles[type] || styles.info) + 'display:block;border-radius:10px;padding:10px 14px;font-size:0.8rem;margin-bottom:8px;line-height:1.5;';
    el.textContent = biometricText(html);
    clearTimeout(el._timer);
    el._timer = setTimeout(function() { el.style.display = 'none'; }, 10000);
}

function hideFpMessage() {
    var el = document.getElementById('fpMessage');
    if (el) { el.style.display = 'none'; clearTimeout(el._timer); }
}

function focusPasswordField() {
    var passInput = document.getElementById('loginPassword');
    if (passInput) {
        try {
            passInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch(e) {}
        passInput.focus();
        passInput.style.borderColor = '#d4af37';
        passInput.style.boxShadow = '0 0 0 4px rgba(212, 175, 55, 0.35)';
        setTimeout(function() {
            passInput.style.borderColor = '';
            passInput.style.boxShadow = '';
        }, 1500);
    }
}

// ── BIOMETRIC MODAL CONTROLLER & SETUP FLOW ─────────────────────────────────
let activeSetupIdentifier = '';
let currentPrimaryModalCallback = null;
let currentSecondaryModalCallback = null;
let currentChooseMethodCallback = null;
let lastAvailableMethods = null;
let lastBiometricOptions = null;

function openBiometricModal(config) {
    config = config || {};
    var modal = document.getElementById('biometricModal');
    if (!modal) return;
    
    var titleEl = document.getElementById('bioModalTitle');
    var descEl = document.getElementById('bioModalDesc');
    var pillEl = document.getElementById('bioModalUserPill');
    var pillTextEl = document.getElementById('bioModalUserText');
    var primaryBtn = document.getElementById('bioModalPrimaryBtn');
    var secondaryBtn = document.getElementById('bioModalSecondaryBtn');
    var passWrap = document.getElementById('bioModalPasswordWrap');
    var progress = document.getElementById('bioModalProgress');
    var progressText = document.getElementById('bioModalProgressText');
    var progressIcon = document.getElementById('bioModalProgressIcon');
    var wasOpen = modal.classList.contains('active');
    if (!wasOpen) {
        biometricReturnFocus = document.activeElement === document.body ? fpRowBtn : document.activeElement;
        biometricPreviousOverflow = document.body.style.overflow;
    }
    hideFpMessage();
    modal.dataset.tone = config.badgeType || 'info';
    modal.dataset.dismissible = config.complete ? 'false' : 'true';
    if (titleEl) titleEl.textContent = config.title || 'Set up biometric sign-in';
    if (descEl) descEl.textContent = biometricText(config.message || 'Use your password to sign in or set up biometrics on this device.');
    if (progress) progress.hidden = !config.progress;
    if (progressText) progressText.textContent = config.progress || '';
    if (progressIcon) {
        progressIcon.className = config.complete ? 'bi bi-check-circle-fill bio-progress-complete' : 'biometric-spinner';
    }
    var closeBtn = document.getElementById('bioModalCloseBtn');
    if (closeBtn) closeBtn.hidden = !!config.complete;
    
    if (config.identifier) {
        activeSetupIdentifier = config.identifier;
        if (pillEl) pillEl.style.display = 'inline-flex';
        if (pillTextEl) pillTextEl.textContent = config.identifier;
    } else {
        if (pillEl) pillEl.style.display = 'none';
    }
    
    if (passWrap) {
        passWrap.style.display = config.showPassword ? 'block' : 'none';
    }

    currentPrimaryModalCallback = typeof config.onPrimaryClick === 'function' ? config.onPrimaryClick : handleSetupBiometricsClick;
    currentSecondaryModalCallback = typeof config.onSecondaryClick === 'function' ? config.onSecondaryClick : closeBiometricModalAndFocusPassword;

    // Render dynamic biometric method selection list if provided
    var methodsWrap = document.getElementById('bioModalMethodsWrap');
    var methodsList = document.getElementById('bioModalMethodsList');
    if (methodsWrap && methodsList) {
        if (config.methods && Array.isArray(config.methods) && config.methods.length > 0) {
            methodsWrap.style.display = 'block';
            methodsList.innerHTML = '';
            config.methods.forEach(function(method) {
                var card = document.createElement('button');
                card.type = 'button';
                card.className = 'bio-method-card';
                card.setAttribute('data-bio-method', method.id);
                card.setAttribute('aria-label', method.name + ' - ' + method.desc);

                var leftDiv = document.createElement('div');
                leftDiv.className = 'bio-method-left';

                var iconBox = document.createElement('div');
                iconBox.className = 'bio-method-icon-box';
                var iTag = document.createElement('i');
                iTag.className = 'bi ' + method.icon;
                iconBox.appendChild(iTag);
                leftDiv.appendChild(iconBox);

                var infoDiv = document.createElement('div');
                infoDiv.className = 'bio-method-info';
                var nameDiv = document.createElement('div');
                nameDiv.className = 'bio-method-name';
                nameDiv.textContent = method.name;
                var descDiv = document.createElement('div');
                descDiv.className = 'bio-method-desc';
                descDiv.textContent = method.desc;
                infoDiv.appendChild(nameDiv);
                infoDiv.appendChild(descDiv);
                leftDiv.appendChild(infoDiv);

                var arrowIcon = document.createElement('i');
                arrowIcon.className = 'bi bi-chevron-right bio-method-arrow';

                card.appendChild(leftDiv);
                card.appendChild(arrowIcon);

                card.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (typeof config.onMethodSelect === 'function') {
                        config.onMethodSelect(method);
                    }
                });

                methodsList.appendChild(card);
            });
        } else {
            methodsWrap.style.display = 'none';
            methodsList.innerHTML = '';
        }
    }

    if (primaryBtn) {
        primaryBtn.hidden = !!(config.hidePrimaryBtn || config.primaryBtnText === false);
        primaryBtn.textContent = biometricText(config.primaryBtnText || 'Set up biometrics');
        primaryBtn.disabled = !!config.primaryDisabled;
    }

    var chooseMethodBtn = document.getElementById('bioModalChooseMethodBtn');
    if (chooseMethodBtn) {
        if (config.showChooseMethodBtn) {
            chooseMethodBtn.hidden = false;
            if (config.chooseMethodBtnText) {
                chooseMethodBtn.textContent = biometricText(config.chooseMethodBtnText);
            } else {
                chooseMethodBtn.textContent = 'Choose another method';
            }
            currentChooseMethodCallback = typeof config.onChooseMethodClick === 'function' 
                ? config.onChooseMethodClick 
                : function() {
                    if (lastAvailableMethods && lastAvailableMethods.length > 0) {
                        openBiometricSelectionPrompt(lastAvailableMethods, activeSetupIdentifier, lastBiometricOptions);
                    } else {
                        handleBiometricLogin();
                    }
                };
        } else {
            chooseMethodBtn.hidden = true;
        }
    }

    if (secondaryBtn) {
        if (config.secondaryBtnText === '' || config.secondaryBtnText === false) {
            secondaryBtn.hidden = true;
        } else {
            secondaryBtn.hidden = false;
            secondaryBtn.textContent = biometricText(config.secondaryBtnText || 'Use password');
        }
    }
    
    var modalIcon = document.getElementById('bioModalIcon');
    if (modalIcon) {
        if (config.headerIcon) {
            modalIcon.className = 'bi ' + config.headerIcon;
        } else if (config.methods && config.methods.length > 0) {
            modalIcon.className = 'bi bi-shield-lock';
        } else {
            modalIcon.className = 'bi bi-fingerprint';
        }
    }

    modal.style.display = 'flex';
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    if (!config.complete) {
        var focusTarget = config.showPassword ? document.getElementById('bioModalPasswordInput') : titleEl;
        focusTarget?.focus({ preventScroll: true });
    }
}

function showBiometricProgress(title, message, identifier, progress, options = {}) {
    openBiometricModal({
        title, message, identifier,
        hidePrimaryBtn: true,
        headerIcon: options.complete ? 'bi-check-circle' : (options.icon || 'bi-shield-lock'),
        badgeType: options.complete ? 'success' : 'info',
        complete: options.complete,
        progress: options.complete ? 'Your account is ready.' : progress,
        secondaryBtnText: options.complete ? false : 'Cancel',
        onSecondaryClick: closeBiometricModal
    });
}

function closeBiometricModal() {
    if (typeof stopFaceRecognitionLoginCamera === 'function') stopFaceRecognitionLoginCamera();
    if (bioAbortController) {
        try { bioAbortController.abort(); } catch(e) {}
        bioAbortController = null;
    }
    resetBiometricButton();
    var modal = document.getElementById('biometricModal');
    if (!modal) return;
    if (!modal.classList.contains('active')) return;
    modal.classList.remove('active');
    var passwordInput = document.getElementById('bioModalPasswordInput');
    if (passwordInput) passwordInput.value = '';
    (biometricReturnFocus || fpRowBtn)?.focus({ preventScroll: true });
    biometricReturnFocus = null;
    modal.setAttribute('aria-hidden', 'true');
    modal.style.display = 'none';
    document.body.style.overflow = biometricPreviousOverflow;
}

function closeBiometricModalAndFocusPassword() {
    closeBiometricModal();
    focusPasswordField();
}

function closeBiometricModalAndFocusIdentifier() {
    closeBiometricModal();
    setTimeout(function() {
        if (idInput) {
            try {
                idInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } catch(e) {}
            idInput.focus();
            idInput.style.borderColor = '#d4af37';
            idInput.style.boxShadow = '0 0 0 4px rgba(212, 175, 55, 0.35)';
            setTimeout(function() { 
                if (idInput) {
                    idInput.style.borderColor = ''; 
                    idInput.style.boxShadow = '';
                }
            }, 2500);
        }
    }, 320);
}

document.addEventListener('keydown', function(e) {
    var modal = document.getElementById('biometricModal');
    if (!modal?.classList.contains('active')) return;
    if (e.key === 'Escape' && modal.dataset.dismissible !== 'false') {
        e.preventDefault();
        closeBiometricModal();
    }
    if (e.key !== 'Tab') return;
    var controls = Array.from(modal.querySelectorAll('button:not(:disabled), input:not(:disabled), a[href]'))
        .filter(control => control.getClientRects().length > 0);
    var first = controls[0];
    var last = controls.at(-1);
    if (!first || (!modal.contains(document.activeElement) || (e.shiftKey && document.activeElement === first) || (!e.shiftKey && document.activeElement === last))) {
        e.preventDefault();
        (e.shiftKey ? last : first)?.focus();
    }
});

function openBiometricSetupModal(identifier) {
    activeSetupIdentifier = identifier || (idInput ? idInput.value.trim() : '');
    showFpMessage('warning', '<i class="bi bi-exclamation-triangle-fill me-2"></i>Biometric login is not registered for this account. Please use your password or register your biometrics first.');
    openBiometricModal({
        title: 'BIOMETRIC NOT REGISTERED',
        identifier: activeSetupIdentifier,
        message: 'Biometric login is not registered for this account.<br><br>Please use your password or register your biometrics first.',
        badgeType: 'warning',
        primaryBtnText: '<i class="bi bi-shield-lock-fill me-2"></i>SET UP BIOMETRICS',
        secondaryBtnText: 'USE PASSWORD',
        onPrimaryClick: handleSetupBiometricsClick,
        onSecondaryClick: closeBiometricModalAndFocusPassword
    });
}

async function handleSetupBiometricsClick() {
    if (isBioPending) return;
    var passInputInForm = document.getElementById('loginPassword');
    var passInputInModal = document.getElementById('bioModalPasswordInput');
    
    var password = '';
    if (passInputInForm && passInputInForm.value) {
        password = passInputInForm.value;
    } else if (passInputInModal && passInputInModal.value) {
        password = passInputInModal.value;
    }

    if (!password) {
        openBiometricModal({
            title: 'VERIFY YOUR ACCOUNT',
            identifier: activeSetupIdentifier,
            message: 'Confirm your account password before registering this device.',
            showPassword: true,
            badgeType: 'warning',
            primaryBtnText: '<i class="bi bi-check-circle-fill me-2"></i>VERIFY & ENABLE BIOMETRICS',
            secondaryBtnText: 'USE PASSWORD',
            onPrimaryClick: handleSetupBiometricsClick,
            onSecondaryClick: closeBiometricModalAndFocusPassword
        });
        return;
    }

    await startBiometricRegistration(activeSetupIdentifier, password);
}

async function startBiometricRegistration(identifier, password) {
    if (window.location.hostname === '127.0.0.1') {
        const targetUrl = window.location.href.replace('//127.0.0.1', '//localhost');
        window.location.replace(targetUrl);
        return;
    }

    var attempt = new AbortController();
    bioAbortController?.abort();
    bioAbortController = attempt;
    isBioPending = true;
    showBiometricProgress('Checking your password', 'After this check, your device will ask you to confirm setup.', identifier, 'Step 1 of 2 · Checking account');

    try {
        var optRes = await fetch('{{ route("webauthn.setup.options") }}', {
            method: 'POST',
            signal: attempt.signal,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ identifier: identifier, password: password })
        });

        var opts = await optRes.json();
        if (attempt.signal.aborted) return;

        if (!optRes.ok || !opts.success) {
            resetBiometricButton();
            var errMsg = opts.message || 'Verification failed. Please check your password.';
            openBiometricModal({
                title: 'ACCOUNT VERIFICATION FAILED',
                identifier: identifier,
                message: errMsg,
                showPassword: true,
                badgeType: 'danger',
                primaryBtnText: '<i class="bi bi-arrow-repeat me-2"></i>TRY AGAIN',
                secondaryBtnText: 'USE PASSWORD',
                onPrimaryClick: handleSetupBiometricsClick,
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        }

        showBiometricProgress('Confirm on your device', 'Follow the secure prompt. Your device may ask for a fingerprint, Face ID, or a screen lock.', identifier, 'Step 2 of 2 · Waiting for your device', { icon: 'bi-phone' });

        var challenge = base64ToUint8Array(opts.challenge);
        var userId = base64ToUint8Array(opts.user.id);
        var effectiveRpId = getEffectiveRpId(opts.rp?.id);
        var rp = { name: opts.rp?.name || 'School Attendance' };
        if (effectiveRpId) {
            rp.id = effectiveRpId;
        }

        var excludeCredentials = (opts.excludeCredentials || []).map(function(c) {
            return { type: c.type || 'public-key', id: base64ToUint8Array(c.id) };
        });

        var hasPlatformAuth = false;
        if (typeof PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable === 'function') {
            try {
                hasPlatformAuth = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            } catch(e) {
                hasPlatformAuth = false;
            }
        }
        if (attempt.signal.aborted) return;
        if (typeof PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable === 'function' && !hasPlatformAuth) {
            throw new Error('No on-device biometric sign-in is available. Set up fingerprint, Face ID, or Windows Hello in your device settings, then try again.');
        }

        var basePublicKey = {
            challenge: challenge,
            rp: rp,
            user: { id: userId, name: opts.user.name, displayName: opts.user.displayName },
            pubKeyCredParams: opts.pubKeyCredParams || [
                { type: 'public-key', alg: -7 },
                { type: 'public-key', alg: -257 }
            ],
            timeout: opts.timeout || 120000,
            attestation: opts.attestation || 'none',
            excludeCredentials: excludeCredentials,
            hints: ['client-device']
        };

        // ── Direct Platform Biometric Registration (Login Setup Flow) ─────────
        // Enforce on-device platform biometric attachment so Android Chrome,
        // iOS Touch ID/Face ID, and Windows Hello invoke the native sensor directly
        // without prompting with the external security key chooser (NFC/USB).
        const loginPrimarySelection = {
            authenticatorAttachment: 'platform',
            userVerification: 'required',
            residentKey: 'preferred',
            requireResidentKey: false
        };

        let credential = null;
        try {
            credential = await navigator.credentials.create({
                signal: attempt.signal,
                publicKey: Object.assign({}, basePublicKey, {
                    authenticatorSelection: loginPrimarySelection
                })
            });
        } catch (firstErr) {
            if (attempt.signal.aborted || firstErr.name === 'AbortError' || firstErr.name === 'NotAllowedError') {
                throw firstErr;
            }
            console.warn('Biometric setup (primary attempt) failed, retrying with platform discouraged fallback:', firstErr);
            try {
                credential = await navigator.credentials.create({
                    signal: attempt.signal,
                    publicKey: Object.assign({}, basePublicKey, {
                        authenticatorSelection: {
                            authenticatorAttachment: 'platform',
                            userVerification: 'required',
                            residentKey: 'discouraged',
                            requireResidentKey: false
                        }
                    })
                });
            } catch (retryErr) {
                throw retryErr;
            }
        }

        if (attempt.signal.aborted) return;
        if (!credential) {
            throw new Error('Biometric setup was cancelled.');
        }

        showBiometricProgress('Saving biometric sign-in', 'Your device confirmed setup. We’re linking it to your account.', identifier, 'Saving device credential…');
        var rawId = bufferToBase64Url(credential.rawId);
        var clientDataJSON = bufferToBase64Url(credential.response.clientDataJSON);
        var attestationObject = bufferToBase64Url(credential.response.attestationObject);

        var saveRes = await fetch('{{ route("webauthn.setup.register") }}', {
            method: 'POST',
            signal: attempt.signal,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                credential_id: rawId,
                credential: {
                    id: credential.id,
                    type: credential.type,
                    response: {
                        attestationObject: attestationObject,
                        clientDataJSON: clientDataJSON
                    }
                },
                identifier: identifier,
                password: password,
                device_name: navigator.userAgent.includes('Mobile') ? 'Mobile Device' : 'Desktop Browser'
            })
        });

        var regResult = await saveRes.json();
        if (attempt.signal.aborted) return;

        if (regResult.success) {
            resetBiometricButton();
            try {
                saveAccount({ identifier: identifier, name: identifier });
                if (idInput) {
                    idInput.value = identifier;
                    if (typeof updateAccountBanner === 'function') updateAccountBanner();
                }
            } catch (e) {}

            openBiometricModal({
                title: '✓ BIOMETRIC SIGN-IN ENABLED',
                identifier: identifier,
                message: 'Biometric sign-in is now enabled for this account on this device.',
                badgeType: 'success',
                primaryBtnText: 'CONTINUE',
                secondaryBtnText: false,
                onPrimaryClick: function() {
                    closeBiometricModal();
                    window.location.href = regResult.redirect || '{{ route("home") }}';
                }
            });
        } else {
            throw new Error(regResult.message || 'Failed to save biometric credential.');
        }
    } catch (err) {
        if (attempt.signal.aborted) return;
        console.error('Biometric setup error:', err);
        resetBiometricButton();
        if (err.name === 'NotAllowedError') {
            showFpMessage('warning', '<i class="bi bi-x-circle me-2"></i>Biometric registration was cancelled or timed out.');
            openBiometricModal({
                title: 'REGISTRATION CANCELLED',
                identifier: identifier,
                message: 'Biometric registration was cancelled or timed out.<br><br>You can try registering again, or sign in using your password.',
                badgeType: 'warning',
                primaryBtnText: '<i class="bi bi-arrow-repeat me-2"></i>TRY AGAIN',
                secondaryBtnText: 'USE PASSWORD',
                onPrimaryClick: handleSetupBiometricsClick,
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        }
        var msg = 'Biometric setup failed. ';
        if (err.message) {
            msg += err.message;
        }
        showFpMessage('error', '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + msg);
        openBiometricModal({
            title: 'BIOMETRIC SETUP FAILED',
            identifier: identifier,
            message: msg + '<br><br>You can sign in using your password or try setting up biometrics again.',
            badgeType: 'warning',
            primaryBtnText: '<i class="bi bi-arrow-repeat me-2"></i>TRY SETUP AGAIN',
            secondaryBtnText: 'USE PASSWORD',
            onPrimaryClick: handleSetupBiometricsClick,
            onSecondaryClick: closeBiometricModalAndFocusPassword
        });
    }
}

// Direct action to re-enroll or register biometrics on this device
function handleDirectReEnrollClick(e) {
    if (e) e.preventDefault();
    hideFpMessage();
    var idVal = idInput ? idInput.value.trim() : '';
    if (!idVal) {
        showFpMessage('warning', '<i class="bi bi-person-fill me-2"></i>Please enter your Student ID, Email, or Mobile Number first.');
        openBiometricModal({
            title: 'ENTER ACCOUNT IDENTIFIER',
            message: 'Please enter your <strong>Student ID, Email, or Mobile Number</strong> first so we can re-enroll your biometric sign-in on this device.',
            badgeType: 'warning',
            primaryBtnText: '<i class="bi bi-person-fill me-2"></i>ENTER IDENTIFIER',
            secondaryBtnText: 'CANCEL',
            onPrimaryClick: closeBiometricModalAndFocusIdentifier,
            onSecondaryClick: closeBiometricModal
        });
        if (idInput) {
            idInput.focus();
            idInput.style.borderColor = '#d4af37';
            setTimeout(function() { if (idInput) idInput.style.borderColor = ''; }, 2500);
        }
        return;
    }
    openBiometricSetupModal(idVal);
}

// Dynamic Device Biometric Capabilities & Selection
let _deviceBioCapabilitiesCache = null;

async function getDeviceBiometricCapabilities() {
    if (_deviceBioCapabilitiesCache) {
        return _deviceBioCapabilitiesCache;
    }

    var isSecure = window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    var isWebAuthnSupported = !!(isSecure && window.PublicKeyCredential && navigator.credentials && typeof navigator.credentials.get === 'function');
    var isPlatformAvailable = false;
    var platformAvailabilityKnown = false;
    if (isWebAuthnSupported && typeof PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable === 'function') {
        try {
            isPlatformAvailable = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            platformAvailabilityKnown = true;
        } catch(e) {
            isPlatformAvailable = false;
        }
    }

    var ua = navigator.userAgent || '';
    var isIOS = /iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var isAndroid = /Android/i.test(ua);
    var isWindows = /Windows/i.test(ua);
    var isMac = /Macintosh/i.test(ua) && !isIOS;

    var isIosFaceId = isIOS && (window.screen.height / window.screen.width > 2 || (window.visualViewport && window.visualViewport.height / window.visualViewport.width > 2) || (typeof window.CSS !== 'undefined' && CSS.supports('padding-top: env(safe-area-inset-top)')));



    _deviceBioCapabilitiesCache = {
        isWebAuthnSupported: isWebAuthnSupported,
        isPlatformAvailable: isPlatformAvailable,
        platformAvailabilityKnown: platformAvailabilityKnown,
        isIOS: isIOS,
        isIosFaceId: isIosFaceId,
        isAndroid: isAndroid,
        isWindows: isWindows,
        isMac: isMac,
    };

    return _deviceBioCapabilitiesCache;
}

function filterAvailableBiometricMethods(serverMethods, deviceCaps) {
    deviceCaps = deviceCaps || { isPlatformAvailable: true, isIOS: false, isIosFaceId: false, isAndroid: false, isWindows: false };
    serverMethods = Array.isArray(serverMethods) ? serverMethods : ['fingerprint', 'face', 'device_lock'];

    var methods = [];

    // Method 1: Fingerprint (Touch ID, Fingerprint sensor)
    var canTryLocalAuthenticator = deviceCaps.isPlatformAvailable
        || (deviceCaps.isWebAuthnSupported && !deviceCaps.platformAvailabilityKnown);
    var fpSupported = canTryLocalAuthenticator;
    if (serverMethods.includes('fingerprint') && fpSupported) {
        methods.push({
            id: 'fingerprint',
            name: deviceCaps.isMac ? 'Touch ID / Fingerprint' : 'Fingerprint',
            desc: deviceCaps.isMac ? 'Touch sensor to sign in with Touch ID' : 'Touch sensor or scan fingerprint to sign in',
            icon: 'bi-fingerprint',
            uv: 'preferred'
        });
    }

    // Method 2: Device face sign-in (Face ID / Windows Hello passkey)
    // A webcam is not an authenticator. Only offer this method when the device
    // reports a user-verifying platform authenticator (Face ID/Windows Hello).
    var faceSupported = canTryLocalAuthenticator;
    if (serverMethods.includes('face') && faceSupported) {
        methods.push({
            id: 'face',
            name: deviceCaps.isIOS ? 'Face ID' : 'Face ID / Windows Hello',
            desc: 'Use this device’s protected sign-in prompt; a PIN may be offered by the device',
            icon: 'bi-person-bounding-box',
            uv: 'required'
        });
    }

    // Method 3: Device PIN/password/pattern as a fallback when supported
    if (serverMethods.includes('device_lock') && canTryLocalAuthenticator) {
        methods.push({
            id: 'device_lock',
            name: deviceCaps.isWindows ? 'Windows Hello PIN / Screen Lock' : (deviceCaps.isIOS ? 'Device Passcode / Screen Lock' : 'Device PIN / Pattern / Screen Lock'),
            desc: deviceCaps.isIOS ? 'Use device passcode or security key' : 'Use device PIN, pattern, or system password',
            icon: 'bi-shield-lock-fill',
            uv: 'required'
        });
    }

    return methods;
}

function stopFaceRecognitionLoginCamera() {
    // Kept for callers that close the biometric dialog; device verification has no camera stream.
}
async function startFaceRecognitionLogin(identifier, opts) {
    // Compatibility entry point: face sign-in uses the device's signed WebAuthn assertion.
    return performBiometricLogin(identifier || (idInput ? idInput.value.trim() : ''), {
        id: 'face',
        name: 'Face ID / Windows Hello',
        icon: 'bi-person-bounding-box',
        uv: 'required'
    }, opts);
}
function openBiometricSelectionPrompt(methods, identifier, opts) {
    lastAvailableMethods = methods;
    lastBiometricOptions = opts;
    activeSetupIdentifier = identifier || (idInput ? idInput.value.trim() : '');

    openBiometricModal({
        title: 'SIGN IN WITH BIOMETRICS',
        identifier: activeSetupIdentifier,
        message: 'Choose an enrolled authentication method on your device to sign in:',
        badgeType: 'info',
        methods: methods,
        hidePrimaryBtn: true,
        secondaryBtnText: 'SIGN IN WITH PASSWORD',
        showChooseMethodBtn: false,
        onMethodSelect: function(selectedMethod) {
            handleSelectBiometricMethod(selectedMethod, activeSetupIdentifier, opts);
        },
        onSecondaryClick: closeBiometricModalAndFocusPassword
    });
}

function handleSelectBiometricMethod(selectedMethod, identifier, opts) {
    closeBiometricModal();
    if (fpLabel) fpLabel.textContent = selectedMethod.name + '...';
    if (fpHint) fpHint.textContent = 'Tap here to cancel (or verify)';
    if (fpIcon) fpIcon.className = 'bi ' + selectedMethod.icon;
    if (fpArrow) fpArrow.className = 'bi bi-x-circle fp-row-arrow';

    performBiometricLogin(identifier, selectedMethod, opts);
}

// Handle biometric login button click
async function handleBiometricLogin() {
    if (biometricRedirectStarted) return;
    hideFpMessage();

    if (isBioPending) return;

    // Guard: ensure environment supports WebAuthn / secure context
    if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        resetBiometricButton();
        showFpMessage('warning', '<i class="bi bi-shield-exclamation me-2"></i>Biometric authentication requires HTTPS or localhost. Please sign in with your password.');
        focusPasswordField();
        return;
    }

    var hasWebAuthn = window.PublicKeyCredential && navigator.credentials && typeof navigator.credentials.get === 'function';


    if (!hasWebAuthn) {
        resetBiometricButton();
        showFpMessage('warning', '<i class="bi bi-shield-exclamation me-2"></i>Device passkey authentication is not supported on this browser. Please sign in with your password.');
        focusPasswordField();
        return;
    }

    var identifier = idInput ? idInput.value.trim() : '';
    if (!identifier && typeof getSavedAccounts === 'function') {
        var savedAccs = getSavedAccounts();
        if (savedAccs && savedAccs.length === 1 && savedAccs[0].identifier) {
            identifier = savedAccs[0].identifier;
            if (idInput) idInput.value = identifier;
        }
    }
    if (!identifier && window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.getLastIdentifier === 'function') {
        var lastNativeId = window.AndroidDeviceBridge.getLastIdentifier();
        if (lastNativeId && lastNativeId.trim()) {
            identifier = lastNativeId.trim();
            if (idInput) idInput.value = identifier;
        }
    }

    // Modal helper for when identifier is required during targeted setup
    window.showStudentIdRequiredModal = function() {
        openBiometricModal({
            title: 'STUDENT ID OR EMAIL REQUIRED',
            message: 'Enter your Student ID, email, or mobile number. If this device has a passkey, you can try it directly.',
            badgeType: 'warning',
            primaryBtnText: '<i class="bi bi-person-fill me-2"></i>ENTER STUDENT ID / EMAIL',
            secondaryBtnText: '<i class="bi bi-passkey me-2"></i>USE DEVICE PASSKEY',
            onPrimaryClick: closeBiometricModalAndFocusIdentifier,
            onSecondaryClick: function() {
                closeBiometricModal();
                performBiometricLogin('');
            }
        });
    };

    var attempt = new AbortController();
    bioAbortController = attempt;
    isBioPending = true;
    showBiometricProgress('Biometric sign-in', 'We’re checking which sign-in methods are available for this device.', identifier, 'Finding available methods…');

    var savedAccounts = (typeof getSavedAccounts === 'function') ? getSavedAccounts() : [];
    var savedIds = savedAccounts.map(function(a) { return a.identifier; }).filter(Boolean);

    try {
        // Only discover methods here. The selected method obtains its fresh,
        // single-use challenge in performBiometricLogin().
        var optRes = await fetch('{{ route("webauthn.available.methods") }}', {
            method: 'POST',
            signal: attempt.signal,
            credentials: 'same-origin',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Content-Type': 'application/json', 
                'Accept': 'application/json' 
            },
            body: JSON.stringify({ 
                student_number: identifier, 
                identifier: identifier,
                saved_identifiers: savedIds
            })
        });

        var opts = await optRes.json();
        if (attempt.signal.aborted) return;

        if (opts.requires_device_enrollment) {
            resetBiometricButton();
            showFpMessage('info', '<i class="bi bi-shield-check me-2"></i>' + (opts.message || 'Verify your password to enroll secure device sign-in.'));
            openBiometricModal({
                title: 'ACTIVATE DEVICE BIOMETRICS',
                identifier: identifier,
                message: (opts.message || 'This account needs a secure device credential.') + '<br><br>Verify your password to enroll a protected device sign-in method.',
                badgeType: 'info',
                showPassword: true,
                primaryBtnText: '<i class="bi bi-check-circle-fill me-2"></i>ACTIVATE BIOMETRICS',
                secondaryBtnText: 'USE PASSWORD',
                onPrimaryClick: handleSetupBiometricsClick,
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        }

        if (!optRes.ok || !opts.success) {
            resetBiometricButton();
            // Account has not registered biometrics
            if (opts.code === 'NOT_REGISTERED' || (optRes.status === 404 && opts.user_exists)) {
                showFpMessage('warning', '<i class="bi bi-shield-lock-fill me-2"></i>Biometric login is not registered for this account. Please use your password or set up biometrics.');
                openBiometricModal({
                    title: 'BIOMETRIC NOT REGISTERED',
                    identifier: identifier,
                    message: 'Biometric sign-in is not registered for this account.<br><br>Would you like to verify your password and set up biometrics on this device now?',
                    badgeType: 'warning',
                    primaryBtnText: '<i class="bi bi-shield-plus me-2"></i>SET UP BIOMETRICS',
                    secondaryBtnText: 'SIGN IN WITH PASSWORD',
                    onPrimaryClick: function() { openBiometricSetupModal(identifier); },
                    onSecondaryClick: closeBiometricModalAndFocusPassword
                });
                return;
            } else if (opts.code === 'ACCOUNT_NOT_FOUND' || optRes.status === 404) {
                showFpMessage('error', '<i class="bi bi-x-circle me-2"></i>No account found matching "' + identifier + '".');
                openBiometricModal({
                    title: 'ACCOUNT NOT FOUND',
                    identifier: identifier,
                    message: 'No account was found matching "<strong>' + identifier + '</strong>". Please double check your Student ID, Email, or Mobile Number.',
                    badgeType: 'danger',
                    primaryBtnText: '<i class="bi bi-pencil-fill me-2"></i>CHECK IDENTIFIER',
                    secondaryBtnText: 'USE PASSWORD',
                    onPrimaryClick: closeBiometricModalAndFocusIdentifier,
                    onSecondaryClick: closeBiometricModalAndFocusPassword
                });
                return;
            } else if (opts.code === 'ACCOUNT_DEACTIVATED' || optRes.status === 403) {
                var deactMsg = opts.message || 'Your account has been deactivated. Please contact the school administrator.';
                showFpMessage('error', '<i class="bi bi-slash-circle me-2"></i>' + deactMsg);
                openBiometricModal({
                    title: 'ACCOUNT DEACTIVATED',
                    identifier: identifier,
                    message: deactMsg,
                    badgeType: 'danger',
                    primaryBtnText: 'USE PASSWORD',
                    secondaryBtnText: false,
                    onPrimaryClick: closeBiometricModalAndFocusPassword
                });
                return;
            } else {
                var genMsg = opts.message || 'Biometric login is not registered for this account. Please use your password or register your biometrics first.';
                showFpMessage('warning', '<i class="bi bi-shield-lock-fill me-2"></i>' + genMsg);
                openBiometricModal({
                    title: 'BIOMETRIC LOGIN',
                    identifier: identifier,
                    message: genMsg,
                    badgeType: 'warning',
                    primaryBtnText: '<i class="bi bi-shield-lock-fill me-2"></i>SET UP BIOMETRICS',
                    secondaryBtnText: 'USE PASSWORD',
                    onPrimaryClick: function() { openBiometricSetupModal(identifier); },
                    onSecondaryClick: closeBiometricModalAndFocusPassword
                });
                return;
            }
        }

        // Detect device biometric capabilities dynamically and filter available methods
        var deviceCaps = await getDeviceBiometricCapabilities();
        if (attempt.signal.aborted) return;
        resetBiometricButton();
        var availableMethods = filterAvailableBiometricMethods(opts.available_methods, deviceCaps);

        if (availableMethods.length === 0) {
            showFpMessage('warning', '<i class="bi bi-shield-exclamation me-2"></i>No compatible biometric authentication method was detected on this device. Please sign in with your password.');
            openBiometricModal({
                title: 'BIOMETRICS UNAVAILABLE',
                message: 'No supported biometric authentication hardware was detected on this device.<br><br>Please sign in using your account password.',
                badgeType: 'warning',
                primaryBtnText: 'SIGN IN WITH PASSWORD',
                secondaryBtnText: 'CANCEL',
                onPrimaryClick: closeBiometricModalAndFocusPassword,
                onSecondaryClick: closeBiometricModal
            });
            return;
        }

        // If face is the only available method, launch its WebAuthn device prompt directly
        if (availableMethods.length === 1 && availableMethods[0].id === 'face') {
            startFaceRecognitionLogin(identifier || opts.identifier, opts);
            return;
        }

        // Present the user with the biometric methods supported and enrolled on their device
        openBiometricSelectionPrompt(availableMethods, identifier || opts.identifier, opts);

    } catch(err) {
        if (attempt.signal.aborted) return;
        console.error('Error fetching biometric options:', err);
        closeBiometricModal();
        showFpMessage('error', '<i class="bi bi-exclamation-triangle-fill me-2"></i>Could not check biometric availability. Please sign in with your password.');
        focusPasswordField();
    }
}

function performNativeAndroidBiometricLogin(studentNumber) {
    studentNumber = (studentNumber || (idInput ? idInput.value : '')).trim();
    if (!studentNumber && window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.getLastIdentifier === 'function') {
        studentNumber = (window.AndroidDeviceBridge.getLastIdentifier() || '').trim();
    }
    if (!studentNumber) {
        if (typeof window.showStudentIdRequiredModal === 'function') {
            window.showStudentIdRequiredModal();
        } else if (idInput) {
            idInput.focus();
            showFpMessage('warning', '<i class="bi bi-person-fill me-2"></i>Please enter your Student ID or Email first.');
        }
        return;
    }
    var nativeDeviceId = (window.AndroidDeviceBridge && window.AndroidDeviceBridge.getNativeDeviceId) ? window.AndroidDeviceBridge.getNativeDeviceId() : '';
    var nativeModel = (window.AndroidDeviceBridge && window.AndroidDeviceBridge.getNativeDeviceModel) ? window.AndroidDeviceBridge.getNativeDeviceModel() : '';

    if (fpLabel) fpLabel.textContent = 'Touch fingerprint sensor...';
    if (fpHint) fpHint.textContent = studentNumber ? ('Verifying for ' + studentNumber) : 'Scan your fingerprint to sign in';
    if (fpIcon) fpIcon.className = 'bi bi-fingerprint';
    if (fpArrow) fpArrow.className = 'bi bi-hourglass-split fp-row-arrow';

    window._onNativeBioSuccess = async function() {
        if (fpLabel) fpLabel.textContent = 'Verifying with server...';
        if (fpHint) fpHint.textContent = 'Please wait...';
        if (fpIcon) fpIcon.className = 'bi bi-shield-check';

        try {
            var res = await fetch('{{ route("webauthn.native.login") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Device-Id': nativeDeviceId,
                    'X-Device-Key': nativeDeviceId,
                    'X-Device-Fingerprint': nativeDeviceId
                },
                body: JSON.stringify({
                    student_number: studentNumber,
                    identifier: studentNumber,
                    device_id: nativeDeviceId,
                    device_key: nativeDeviceId,
                    device_model: nativeModel,
                    device_fingerprint: nativeDeviceId
                })
            });

            var result = await res.json();
            if (result.success) {
                if (fpLabel) fpLabel.textContent = 'Welcome back!';
                if (fpHint) fpHint.textContent = 'Redirecting...';
                if (fpIcon) fpIcon.className = 'bi bi-check-circle-fill text-success';
                if (result.user && result.user.identifier) {
                    try {
                        saveAccount({
                            identifier: result.user.identifier,
                            name: result.user.name,
                            role: result.user.role
                        });
                        if (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.setLastIdentifier === 'function') {
                            window.AndroidDeviceBridge.setLastIdentifier(result.user.identifier);
                        }
                    } catch(e) {}
                }
                window.location.replace(result.redirect || '{{ route("home") }}');
            } else {
                resetBiometricButton();
                if (result.code === 'SETUP_REQUIRED') {
                    showFpMessage('warning', '<i class="bi bi-shield-exclamation me-2"></i>' + result.message);
                    openBiometricSetupModal(studentNumber);
                } else if (result.code === 'USER_NOT_FOUND') {
                    showFpMessage('warning', '<i class="bi bi-person-fill me-2"></i>' + result.message);
                    if (typeof window.showStudentIdRequiredModal === 'function') {
                        window.showStudentIdRequiredModal();
                    } else if (idInput) {
                        idInput.focus();
                    }
                } else {
                    showFpMessage('error', '<i class="bi bi-x-circle me-2"></i>' + (result.message || 'Login failed.'));
                    openBiometricModal({
                        title: 'SIGN IN FAILED',
                        identifier: studentNumber,
                        message: (result.message || 'Authentication failed.') + '<br><br>Please try again or use your password.',
                        badgeType: 'danger',
                        primaryBtnText: '<i class="bi bi-arrow-repeat me-2"></i>TRY AGAIN',
                        secondaryBtnText: 'SIGN IN WITH PASSWORD',
                        onPrimaryClick: function() { performNativeAndroidBiometricLogin(studentNumber); },
                        onSecondaryClick: closeBiometricModalAndFocusPassword
                    });
                }
            }
        } catch(err) {
            resetBiometricButton();
            showFpMessage('error', '<i class="bi bi-wifi-off me-2"></i>Network error. Please try again.');
        }
    };

    window._onNativeBioError = function(errMsg, errCode, isCancelled) {
        resetBiometricButton();
        if (isCancelled) {
            closeBiometricModal();
            return;
        }
        showFpMessage('warning', '<i class="bi bi-exclamation-triangle me-2"></i>' + (errMsg || 'Biometric authentication was cancelled or paused.'));
        openBiometricModal({
            title: 'AUTHENTICATION PAUSED',
            identifier: studentNumber,
            message: (errMsg || 'Biometric authentication was paused.') + '<br><br>Would you like to try again or sign in with your password?',
            badgeType: 'warning',
            primaryBtnText: '<i class="bi bi-arrow-repeat me-2"></i>TRY AGAIN',
            secondaryBtnText: 'SIGN IN WITH PASSWORD',
            onPrimaryClick: function() { performNativeAndroidBiometricLogin(studentNumber); },
            onSecondaryClick: closeBiometricModalAndFocusPassword
        });
    };

    window.AndroidDeviceBridge.authenticateBiometric(
        'Sign In with Fingerprint',
        studentNumber ? ('Confirm fingerprint for ' + studentNumber) : 'Touch fingerprint sensor to sign in',
        '_onNativeBioSuccess',
        '_onNativeBioError'
    );
}

async function performBiometricLogin(studentNumber, selectedMethod) {
    if (biometricRedirectStarted) return;
    studentNumber = (studentNumber || '').trim();
    selectedMethod = selectedMethod || { id: 'fingerprint', name: 'Biometric', icon: 'bi-fingerprint', uv: 'required' };
    var onDeviceMethod = selectedMethod.id === 'fingerprint' || selectedMethod.id === 'face';

    // Abort previous prompt if any
    if (bioAbortController) {
        try { bioAbortController.abort(); } catch(e) {}
    }
    var attempt = new AbortController();
    bioAbortController = attempt;
    isBioPending = true;
    showBiometricProgress('Preparing sign-in', 'Your device will ask you to confirm.', studentNumber, 'Connecting securely…', { icon: selectedMethod.icon });

    var savedAccounts = (typeof getSavedAccounts === 'function') ? getSavedAccounts() : [];
    var savedIds = savedAccounts.map(function(a) { return a.identifier; }).filter(Boolean);

    try {
        if (onDeviceMethod && typeof PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable === 'function') {
            var platformAvailable = null;
            try {
                platformAvailable = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            } catch (availabilityError) {
                // Some browsers cannot answer the capability query. The local-only
                // WebAuthn request below remains the definitive check on those devices.
            }
            if (attempt.signal.aborted) return;
            if (platformAvailable === false) {
                throw new Error('No on-device verification is set up. Enable fingerprint, Face ID, or Windows Hello in device settings, or sign in with your password.');
            }
        }
        // Every attempt needs a fresh, single-use challenge, including retries from the error dialog.
        var optRes = await fetch('{{ route("webauthn.login.options") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json' 
                },
                body: JSON.stringify({ 
                    student_number: studentNumber, 
                    identifier: studentNumber,
                    biometric_method: selectedMethod.id,
                    saved_identifiers: savedIds
                }),
                signal: attempt.signal
        });
        var opts = await optRes.json();
        if (attempt.signal.aborted) return;

        if (!opts || !opts.success) {
            throw new Error(opts?.message || 'Biometric login failed to initialize.');
        }

        var allowCredentials = (opts.allowCredentials || []).map(function(c) {
            var cred = { type: c.type || 'public-key', id: base64ToUint8Array(c.id) };
            if (c.transports && Array.isArray(c.transports) && c.transports.length > 0) {
                cred.transports = onDeviceMethod ? ['internal'] : c.transports;
            } else {
                cred.transports = onDeviceMethod ? ['internal'] : ['internal', 'hybrid'];
            }
            return cred;
        });

        if (studentNumber && allowCredentials.length === 0) {
            resetBiometricButton();
            openBiometricSetupModal(studentNumber);
            return;
        }

        // Trigger WebAuthn credential retrieval
        var challenge = base64ToUint8Array(opts.challenge);
        var effectiveRpId = getEffectiveRpId(opts.rpId);
        
        var getPublicKey = {
            challenge: challenge,
            userVerification: 'required',
            timeout: 45000
        };
        
        if (allowCredentials.length > 0) {
            getPublicKey.allowCredentials = allowCredentials;
        }
        if (onDeviceMethod) {
            getPublicKey.hints = ['client-device'];
        }
        
        if (effectiveRpId) {
            getPublicKey.rpId = effectiveRpId;
        }

        var promptHelp = selectedMethod.id === 'face'
            ? 'Confirm with Face ID or Windows Hello when prompted. A device PIN may be offered.'
            : (selectedMethod.id === 'device_lock' ? 'Use your PIN or screen lock in the device prompt.' : 'Follow your device’s fingerprint or screen-lock prompt.');
        showBiometricProgress('Confirm on your device', promptHelp, studentNumber, 'Waiting for your device…', { icon: selectedMethod.icon });

        var assertion = null;
        try {
            assertion = await navigator.credentials.get({
                publicKey: getPublicKey,
                signal: attempt.signal
            });
        } catch (firstErr) {
            if (attempt.signal.aborted || firstErr.name === 'AbortError' || firstErr.name === 'NotAllowedError') {
                throw firstErr;
            }

            // If allowCredentials failed on this device (e.g. user presented another enrolled credential),
            // attempt discoverable passkey before giving up
            if (!onDeviceMethod && getPublicKey.allowCredentials && getPublicKey.allowCredentials.length > 0) {
                console.warn('Biometric query with allowCredentials failed, trying discoverable passkey fallback...', firstErr);
                var fallbackPublicKey = Object.assign({}, getPublicKey);
                delete fallbackPublicKey.allowCredentials;
                try {
                    assertion = await navigator.credentials.get({
                        publicKey: fallbackPublicKey,
                        signal: attempt.signal
                    });
                } catch (fallbackErr) {
                    if (fallbackErr.name === 'AbortError') throw fallbackErr;
                    throw firstErr;
                }
            } else {
                throw firstErr;
            }
        }

        if (attempt.signal.aborted) return;
        if (!assertion) {
            throw new Error('Authentication was cancelled or failed.');
        }

        showBiometricProgress('Finishing sign-in', 'Your device confirmed the prompt.', studentNumber, 'Signing you in…');

        var credentialId = bufferToBase64Url(assertion.rawId);
        var assertionResponse = {
            clientDataJSON: bufferToBase64Url(assertion.response.clientDataJSON instanceof ArrayBuffer ? new Uint8Array(assertion.response.clientDataJSON) : assertion.response.clientDataJSON),
            authenticatorData: bufferToBase64Url(assertion.response.authenticatorData instanceof ArrayBuffer ? new Uint8Array(assertion.response.authenticatorData) : assertion.response.authenticatorData),
            signature: bufferToBase64Url(assertion.response.signature instanceof ArrayBuffer ? new Uint8Array(assertion.response.signature) : assertion.response.signature)
        };
        if (assertion.response.userHandle) {
            assertionResponse.userHandle = bufferToBase64Url(assertion.response.userHandle instanceof ArrayBuffer ? new Uint8Array(assertion.response.userHandle) : assertion.response.userHandle);
        }
        var assertionData = {
            id: credentialId,
            type: assertion.type || 'public-key',
            response: assertionResponse
        };

        var devKey = (typeof window.getOrCreateDeviceKey === 'function')
            ? window.getOrCreateDeviceKey()
            : (localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || '');

        var loginRes = await fetch('{{ route("webauthn.login") }}', {
            method: 'POST',
            signal: attempt.signal,
            credentials: 'same-origin',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Content-Type': 'application/json', 
                'Accept': 'application/json',
                'X-Device-Key': devKey,
                'X-Device-Fingerprint': devKey
            },
            body: JSON.stringify({ 
                credential_id: credentialId, 
                assertion: assertionData,
                student_number: studentNumber,
                identifier: studentNumber,
                biometric_method: selectedMethod.id,
                device_key: devKey,
                device_fingerprint: devKey
            })
        });
        
        var result = await loginRes.json();
        if (attempt.signal.aborted) return;

        if (loginRes.ok && result.success) {
            completeBiometricLogin(result);
        } else {
            resetBiometricButton();

            // 1-Tap Account Switch when scanned biometric belongs to another user on this device
            if (result.can_switch_user && result.detected_user) {
                var detUser = result.detected_user;
                var detName = detUser.name || detUser.identifier;
                
                openBiometricModal({
                    title: 'BIOMETRIC RECOGNIZED',
                    identifier: detUser.identifier,
                    message: 'This biometric matches <strong>' + detName + '</strong> (' + detUser.identifier + ').<br><br>Would you like to sign in as <strong>' + detName + '</strong>?',
                    badgeType: 'success',
                    primaryBtnText: '<i class="bi bi-box-arrow-in-right me-2"></i>SIGN IN AS ' + detName.toUpperCase(),
                    secondaryBtnText: 'CANCEL',
                    onPrimaryClick: async function() {
                        openBiometricModal({
                            title: 'SIGNING IN...',
                            identifier: detUser.identifier,
                            message: 'Authenticating as <strong>' + detName + '</strong>...',
                            badgeType: 'info',
                            primaryBtnText: '<i class="bi bi-hourglass-split me-2"></i>Signing in...',
                            primaryDisabled: true,
                            secondaryBtnText: false
                        });
                        
                        try {
                            var swRes = await fetch('{{ route("webauthn.login") }}', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: { 
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                                    'Content-Type': 'application/json', 
                                    'Accept': 'application/json',
                                    'X-Device-Key': devKey,
                                    'X-Device-Fingerprint': devKey
                                },
                                body: JSON.stringify({ 
                                    credential_id: credentialId, 
                                    assertion: assertionData,
                                    student_number: detUser.identifier,
                                    identifier: detUser.identifier,
                                    switch_user: true,
                                    device_key: devKey,
                                    device_fingerprint: devKey
                                })
                            });
                            var swData = await swRes.json();
                            if (swData.success) {
                                try {
                                    saveAccount({
                                        identifier: detUser.identifier,
                                        name: detUser.name,
                                        role: detUser.role
                                    });
                                    if (idInput) {
                                        idInput.value = detUser.identifier;
                                        if (typeof updateAccountBanner === 'function') updateAccountBanner();
                                    }
                                } catch(e) {}
                                window.location.href = swData.redirect || '{{ route("home") }}';
                            } else {
                                throw new Error(swData.message || 'Login failed.');
                            }
                        } catch(swErr) {
                            openBiometricModal({
                                title: 'SIGN IN FAILED',
                                identifier: detUser.identifier,
                                message: swErr.message || 'Failed to switch user.',
                                badgeType: 'danger',
                                primaryBtnText: '<i class="bi bi-shield-plus me-2"></i>RE-ENROLL ON THIS DEVICE',
                                secondaryBtnText: 'USE PASSWORD',
                                onPrimaryClick: function() { openBiometricSetupModal(detUser.identifier); },
                                onSecondaryClick: closeBiometricModalAndFocusPassword
                            });
                        }
                    },
                    onSecondaryClick: closeBiometricModal
                });
                return;
            }

            var failMsg = result.message || 'Biometric authentication was not recognized.';
            showFpMessage('error', '<i class="bi bi-x-circle me-2"></i>' + failMsg);
            
            var modalTitle = result.code === 'CREDENTIAL_MISMATCH' ? 'ACCOUNT MISMATCH' : 'AUTHENTICATION FAILED';
            
            openBiometricModal({
                title: modalTitle,
                message: failMsg + '<br><br>Please try again, choose another method, or sign in using your password.',
                badgeType: 'danger',
                primaryBtnText: '<i class="bi bi-arrow-repeat me-2"></i>TRY AGAIN',
                secondaryBtnText: 'SIGN IN WITH PASSWORD',
                showChooseMethodBtn: true,
                onPrimaryClick: function() {
                    closeBiometricModal();
                    performBiometricLogin(studentNumber, selectedMethod);
                },
                onChooseMethodClick: function() {
                    closeBiometricModal();
                    if (lastAvailableMethods && lastAvailableMethods.length > 0) {
                        openBiometricSelectionPrompt(lastAvailableMethods, studentNumber, lastBiometricOptions);
                    } else {
                        handleBiometricLogin();
                    }
                },
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
        }
    } catch (err) {
        if (attempt.signal.aborted) return;
        console.error('Biometric authentication error:', err);
        resetBiometricButton();

        if (err.name === 'AbortError') {
            closeBiometricModal();
            return;
        }

        var isLockout = /lockout|locked|too\s*many/i.test(err.message || '');
        if (isLockout) {
            showFpMessage('error', '<i class="bi bi-shield-exclamation me-2"></i>Biometric sensor locked out. Please use another method or sign in with your password.');
            openBiometricModal({
                title: 'SENSOR LOCKED OUT',
                message: 'Biometric sensor locked out due to multiple failed attempts.<br><br>Please use your device PIN, pattern, or sign in with your account password.',
                badgeType: 'danger',
                primaryBtnText: '<i class="bi bi-grid-fill me-2"></i>CHOOSE ANOTHER METHOD',
                secondaryBtnText: 'SIGN IN WITH PASSWORD',
                showChooseMethodBtn: true,
                onPrimaryClick: function() {
                    closeBiometricModal();
                    if (lastAvailableMethods && lastAvailableMethods.length > 0) {
                        openBiometricSelectionPrompt(lastAvailableMethods, studentNumber, lastBiometricOptions);
                    } else {
                        handleBiometricLogin();
                    }
                },
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        }

        if (err.name === 'NotAllowedError') {
            openBiometricModal({
                title: 'Device prompt didn’t finish',
                identifier: studentNumber,
                message: 'Try the device prompt again or sign in with your password.',
                badgeType: 'warning',
                primaryBtnText: '<i class="bi bi-arrow-repeat me-2"></i>TRY AGAIN',
                secondaryBtnText: 'SIGN IN WITH PASSWORD',
                showChooseMethodBtn: lastAvailableMethods.length > 1,
                onPrimaryClick: function() {
                    closeBiometricModal();
                    performBiometricLogin(studentNumber, selectedMethod);
                },
                onChooseMethodClick: function() {
                    openBiometricSelectionPrompt(lastAvailableMethods, studentNumber, lastBiometricOptions);
                },
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        } else if (err.name === 'InvalidStateError') {
            showFpMessage('warning', '<i class="bi bi-shield-exclamation me-2"></i>Your biometric sign-in is not set up on this device.');
            openBiometricModal({
                title: 'BIOMETRICS NOT SET UP',
                message: 'Your biometric credential is not registered on this device.<br><br>Please sign in with your password.',
                badgeType: 'warning',
                primaryBtnText: 'SIGN IN WITH PASSWORD',
                secondaryBtnText: 'CANCEL',
                onPrimaryClick: closeBiometricModalAndFocusPassword,
                onSecondaryClick: closeBiometricModal
            });
            return;
        } else {
            var errMsg = err.message || 'Your biometric authentication could not be completed.';
            showFpMessage('error', '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + errMsg);
            openBiometricModal({
                title: 'AUTHENTICATION FAILED',
                message: errMsg + '<br><br>Please try again, choose another method, or sign in with your password.',
                badgeType: 'danger',
                primaryBtnText: '<i class="bi bi-arrow-repeat me-2"></i>TRY AGAIN',
                secondaryBtnText: 'SIGN IN WITH PASSWORD',
                showChooseMethodBtn: true,
                onPrimaryClick: function() {
                    closeBiometricModal();
                    performBiometricLogin(studentNumber, selectedMethod);
                },
                onChooseMethodClick: function() {
                    closeBiometricModal();
                    if (lastAvailableMethods && lastAvailableMethods.length > 0) {
                        openBiometricSelectionPrompt(lastAvailableMethods, studentNumber, lastBiometricOptions);
                    } else {
                        handleBiometricLogin();
                    }
                },
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
        }
    }
}

// Bind DOM event listeners for biometric UI elements
function setupBiometricListeners() {
    // Re-query the controls here instead of relying only on the references
    // captured when this script first ran. Login can be restored from the
    // browser back/forward cache or replaced by a partial navigation.
    fpSec = document.getElementById('fingerprintSection');
    fpRowBtn = document.getElementById('fpRowBtn');
    fpLabel = document.getElementById('fpLabel');
    fpHint = document.getElementById('fpHint');
    fpIcon = document.getElementById('fpIcon');
    fpArrow = document.getElementById('fpArrow');

    if (fpRowBtn && fpRowBtn.dataset.biometricBound !== 'true') {
        fpRowBtn.dataset.biometricBound = 'true';
        fpRowBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            handleBiometricLogin();
        });
    }

    var primaryBtn = document.getElementById('bioModalPrimaryBtn');
    if (primaryBtn && primaryBtn.dataset.biometricBound !== 'true') {
        primaryBtn.dataset.biometricBound = 'true';
        primaryBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof currentPrimaryModalCallback === 'function') {
                currentPrimaryModalCallback(e);
            } else {
                handleSetupBiometricsClick();
            }
        });
    }

    var secondaryBtn = document.getElementById('bioModalSecondaryBtn');
    if (secondaryBtn && secondaryBtn.dataset.biometricBound !== 'true') {
        secondaryBtn.dataset.biometricBound = 'true';
        secondaryBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof currentSecondaryModalCallback === 'function') {
                currentSecondaryModalCallback(e);
            } else {
                closeBiometricModalAndFocusPassword();
            }
        });
    }

    var chooseMethodBtn = document.getElementById('bioModalChooseMethodBtn');
    if (chooseMethodBtn && chooseMethodBtn.dataset.biometricBound !== 'true') {
        chooseMethodBtn.dataset.biometricBound = 'true';
        chooseMethodBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof currentChooseMethodCallback === 'function') {
                currentChooseMethodCallback(e);
            } else if (lastAvailableMethods && lastAvailableMethods.length > 0) {
                openBiometricSelectionPrompt(lastAvailableMethods, activeSetupIdentifier, lastBiometricOptions);
            } else {
                handleBiometricLogin();
            }
        });
    }

    var bioModalBackdrop = document.getElementById('bioModalBackdrop');
    if (bioModalBackdrop && bioModalBackdrop.dataset.biometricBound !== 'true') {
        bioModalBackdrop.dataset.biometricBound = 'true';
        bioModalBackdrop.addEventListener('click', function(e) {
            e.preventDefault();
            if (document.getElementById('biometricModal')?.dataset.dismissible !== 'false') closeBiometricModal();
        });
    }

    var bioModalCloseBtn = document.getElementById('bioModalCloseBtn');
    if (bioModalCloseBtn && bioModalCloseBtn.dataset.biometricBound !== 'true') {
        bioModalCloseBtn.dataset.biometricBound = 'true';
        bioModalCloseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeBiometricModal();
        });
    }

    var bioModalPass = document.getElementById('bioModalPasswordInput');
    if (bioModalPass && bioModalPass.dataset.biometricBound !== 'true') {
        bioModalPass.dataset.biometricBound = 'true';
        bioModalPass.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleSetupBiometricsClick();
            }
        });
    }

}

// Delegated click handler on document ensures clicks on #fpRowBtn or its child elements
// are captured reliably across all mobile browsers and dynamic DOM states
document.addEventListener('click', function(e) {
    var btn = e.target.closest('#fpRowBtn');
    if (btn && !e.defaultPrevented) {
        e.preventDefault();
        e.stopPropagation();
        handleBiometricLogin();
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupBiometricListeners);
} else {
    setupBiometricListeners();
}
window.addEventListener('pageshow', setupBiometricListeners);
window.addEventListener('pagehide', function() {
    if (!biometricRedirectStarted) closeBiometricModal();
});
// Expose functions globally on window
window.handleBiometricLogin = handleBiometricLogin;
window.performBiometricLogin = performBiometricLogin;
window.openBiometricModal = openBiometricModal;
window.closeBiometricModal = closeBiometricModal;
window.openBiometricSetupModal = openBiometricSetupModal;
window.handleDirectReEnrollClick = handleDirectReEnrollClick;
window.handleSetupBiometricsClick = handleSetupBiometricsClick;
window.closeBiometricModalAndFocusPassword = closeBiometricModalAndFocusPassword;
window.closeBiometricModalAndFocusIdentifier = closeBiometricModalAndFocusIdentifier;

// Restore identifier on validation error if present
@if(old('identifier'))
    if (idInput) {
        idInput.value = '{{ old('identifier') }}';
        if (typeof updateAccountBanner === 'function') updateAccountBanner();
    }
@endif

// Intelligently forward login identifier to Forgot Password flow
function updateForgotHref() {
    var link = document.getElementById('forgotPasswordLink');
    var input = document.getElementById('idInput');
    if (!link || !input) return;
    var base = '{{ route('otp.forgot.form') }}';
    var val = input.value ? input.value.trim() : '';
    if (val) {
        link.href = base + '?identifier=' + encodeURIComponent(val);
    } else {
        link.href = base;
    }
}

var idInputElem = document.getElementById('idInput');
var forgotLinkElem = document.getElementById('forgotPasswordLink');
if (idInputElem) {
    idInputElem.addEventListener('input', updateForgotHref);
    idInputElem.addEventListener('change', updateForgotHref);
    updateForgotHref();
}
if (forgotLinkElem) {
    forgotLinkElem.addEventListener('click', updateForgotHref);
    forgotLinkElem.addEventListener('mouseenter', updateForgotHref);
    forgotLinkElem.addEventListener('focus', updateForgotHref);
    forgotLinkElem.addEventListener('touchstart', updateForgotHref);
}

// ── Viewport Height Lock – prevents status bar / notification shade from shifting layout ──
(function() {
    var docEl = document.documentElement;
    var lockedHeight = window.innerHeight || docEl.clientHeight;
    var lockedWidth  = window.innerWidth  || docEl.clientWidth;
    var lastOrientation = (window.screen && window.screen.orientation && window.screen.orientation.type) ?
        (window.screen.orientation.type.includes('landscape') ? 'landscape' : 'portrait') :
        (window.innerWidth > window.innerHeight ? 'landscape' : 'portrait');

    function lockSafeAreas() {
        try {
            var probe = document.createElement('div');
            probe.style.cssText = 'position:fixed;top:0;left:0;width:1px;height:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px);padding-left:env(safe-area-inset-left,0px);padding-right:env(safe-area-inset-right,0px);visibility:hidden;pointer-events:none;z-index:-1;';
            docEl.appendChild(probe);
            var cs = window.getComputedStyle(probe);
            var top = parseFloat(cs.height) || 0;
            var bottom = parseFloat(cs.paddingBottom) || 0;
            var left = parseFloat(cs.paddingLeft) || 0;
            var right = parseFloat(cs.paddingRight) || 0;
            docEl.removeChild(probe);
            if (top > 0) docEl.style.setProperty('--sat', top + 'px');
            if (bottom > 0) docEl.style.setProperty('--sab', bottom + 'px');
            if (left > 0) docEl.style.setProperty('--sal', left + 'px');
            if (right > 0) docEl.style.setProperty('--sar', right + 'px');
        } catch (e) {}
    }

    function applyLock() {
        docEl.style.setProperty('--app-height', lockedHeight + 'px');
        docEl.style.setProperty('--app-width',  lockedWidth  + 'px');
        lockSafeAreas();
    }

    // Re-lock only on real physical orientation changes
    function onOrientationChange() {
        setTimeout(function() {
            var newOrientation = (window.screen && window.screen.orientation && window.screen.orientation.type) ?
                (window.screen.orientation.type.includes('landscape') ? 'landscape' : 'portrait') :
                (window.innerWidth > window.innerHeight ? 'landscape' : 'portrait');
            if (newOrientation !== lastOrientation) {
                lastOrientation  = newOrientation;
                lockedHeight = window.innerHeight || docEl.clientHeight;
                lockedWidth  = window.innerWidth  || docEl.clientWidth;
                applyLock();
            }
        }, 250);
    }

    // A system overlay can shrink the visual viewport just like a keyboard.
    // Only enter keyboard mode when an editable control owns focus so opening
    // the status bar, notification shade, or system controls leaves the UI intact.
    function isTextEntryFocused() {
        var active = document.activeElement;
        if (!active) return false;
        return active.matches('textarea, select, [contenteditable="true"], input:not([type="button"]):not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="reset"])');
    }

    // Keyboard detection only – never update layout height from resize
    function onResize() {
        var vv = window.visualViewport;
        var rawHeight = vv ? vv.height : window.innerHeight;
        var isKeyboard = isTextEntryFocused() && rawHeight < (lockedHeight - 150);
        document.body.classList.toggle('keyboard-open', isKeyboard);

        // When keyboard opens, nudge the active input into the visible viewport
        if (isKeyboard && document.activeElement) {
            setTimeout(function() {
                try { document.activeElement.scrollIntoView({ block: 'center', behavior: 'smooth' }); } catch(e) {}
            }, 80);
        }
    }

    // Preserve scroll position when notification panel / overlays are opened or dismissed
    var authScene = document.querySelector('.auth-scene');
    var savedScrollTop = 0;
    function trackScroll() {
        if (authScene) savedScrollTop = authScene.scrollTop;
    }
    if (authScene) {
        authScene.addEventListener('scroll', trackScroll, { passive: true });
    }

    // Scroll focused input into view when keyboard pops up (works in WebView)
    function onInputFocus(e) {
        setTimeout(function() {
            try { e.target.scrollIntoView({ block: 'center', behavior: 'smooth' }); } catch(ex) {}
        }, 320);
    }
    document.querySelectorAll('input, textarea, select').forEach(function(el) {
        el.addEventListener('focus', onInputFocus, { passive: true });
    });
    // Re-apply to any inputs added after DOM ready (e.g. inside modals)
    document.addEventListener('focusin', function(e) {
        if (e.target && e.target.matches && e.target.matches('input, textarea, select')) {
            onInputFocus(e);
        }
    }, { passive: true });

    window.addEventListener('orientationchange', onOrientationChange, { passive: true });
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', onResize, { passive: true });
    }
    window.addEventListener('resize', onResize, { passive: true });

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            if (authScene && savedScrollTop > 0 && Math.abs(authScene.scrollTop - savedScrollTop) > 2) {
                authScene.scrollTop = savedScrollTop;
            }
            onResize();
        } else {
            trackScroll();
        }
    }, { passive: true });

    window.addEventListener('focus', function() {
        if (authScene && savedScrollTop > 0 && Math.abs(authScene.scrollTop - savedScrollTop) > 2) {
            authScene.scrollTop = savedScrollTop;
        }
    }, { passive: true });

    applyLock();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyLock);
    }
})();

// ── Real-time Login Version Badge Synchronization ──
(function() {
    function syncLoginVersionBadge() {
        // The badge describes this loaded page, not a newer build reported by
        // /pwa/version. It advances only after the update reload succeeds.
        const ver = document.querySelector('meta[name="app-installed-version"]')?.content;
        if (!ver) return;
        const tag = 'v' + String(ver).replace(/^v/i, '');
        document.querySelectorAll('[data-app-version-tag], #loginAppVersionDesktop, #loginAppVersionMobile').forEach(function(el) {
            el.textContent = tag;
        });
    }

    syncLoginVersionBadge();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncLoginVersionBadge);
    }
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) syncLoginVersionBadge();
    });
    window.addEventListener('focus', syncLoginVersionBadge);
    window.addEventListener('pageshow', syncLoginVersionBadge);

    // BroadcastChannel sync across tabs
    if ('BroadcastChannel' in window) {
        try {
            const ch = new BroadcastChannel('smart_attendance_pwa');
            ch.addEventListener('message', function(e) {
                if (e.data && (e.data.type === 'APP_UPDATED' || e.data.type === 'VERSION_CHANGED')) {
                    syncLoginVersionBadge();
                }
            });
        } catch(e) {}
    }
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('controllerchange', syncLoginVersionBadge);
    }
})();
</script>
</body>
</html>
