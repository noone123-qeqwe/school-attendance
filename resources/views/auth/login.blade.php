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
    <link rel="preload" as="image" href="/images/background.jpg" media="(min-width: 769px)">
    <link rel="preload" as="image" href="/images/background_mobile.jpg" media="(max-width: 768px)">
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
            background: url('/images/background.jpg') center center / cover no-repeat;
            background-color: #1a0a0a;
            z-index: 0;
            pointer-events: none;
        }
        @media (max-width: 768px) {
            .bg-scene {
                background-image: url('/images/background_mobile.jpg');
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
            z-index: 5;
            display: flex; align-items: center; justify-content: space-between;
            padding-top: 10px;
            padding-bottom: calc(var(--sab, env(safe-area-inset-bottom, 0px)) + 10px);
            padding-left: calc(var(--sal, env(safe-area-inset-left, 0px)) + 24px);
            padding-right: calc(var(--sar, env(safe-area-inset-right, 0px)) + 24px);
            font-size: 0.72rem; color: rgba(255,255,255,0.45);
            pointer-events: none;
            background: linear-gradient(0deg, rgba(17, 10, 10, 0.85) 0%, transparent 100%);
            transition: opacity 0.2s ease;
        }
        .bottom-bar a {
            color: rgba(255,255,255,0.45); text-decoration: none;
            transition: color 0.2s; pointer-events: all;
        }
        .bottom-bar a:hover { color: rgba(255,255,255,0.8); }
        .bottom-bar span { pointer-events: none; }
        .bottom-links { display: flex; gap: 20px; }

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
            align-items: center;
            justify-content: center;
            padding-top: calc(var(--sat, env(safe-area-inset-top, 0px)) + 64px);
            padding-bottom: calc(var(--sab, env(safe-area-inset-bottom, 0px)) + 48px);
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
            max-width: 500px;
            min-width: 280px;
            background: rgba(30, 21, 21, 0.82);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border-radius: 22px;
            border: 1px solid rgba(212, 175, 55, 0.25);
            box-shadow: 0 16px 40px rgba(0,0,0,0.5), inset 0 1px 0 rgba(212, 175, 55, 0.12);
            padding: 30px 28px 24px;
            color: white;
            position: relative;
            z-index: 20;
            margin: auto 0;
            transition: transform 0.2s ease, padding 0.2s ease;
            overflow-y: visible;
        }

        /* Logo  -  larger */
        .glass-logo {
            width: 64px; height: 64px; /* Increased from 56px */
            border-radius: 50%;
            background: white;
            border: 2.5px solid rgba(255,220,100,0.6);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            margin: 0 auto 14px; /* Increased margin */
            transition: transform 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
            animation: floatLogo 4s ease-in-out infinite, glowPulse 2s infinite;
        }
        .glass-logo:hover { transform: scale(1.08); animation-play-state: paused; }
        .glass-logo img { width: 85%; height: 85%; object-fit: contain; }

        /* Badge */
        .glass-badge {
            display: inline-block;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            font-size: 0.62rem; font-weight: 800;
            letter-spacing: 2px; text-transform: uppercase;
            padding: 2.5px 12px; border-radius: 99px;
            margin-bottom: 4px;
        }
        .glass-title {
            font-size: 1.58rem; font-weight: 800; /* Increased from 1.48rem */
            color: white; letter-spacing: -0.5px;
            margin-bottom: 6px; /* Increased spacing */
        }
        .glass-sub {
            font-size: 0.88rem; color: rgba(255,255,255,0.65); /* Increased from 0.82rem */
            margin-bottom: 20px; /* Increased spacing */
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
        .glass-input-wrap { position: relative; margin-bottom: 14px; width: 100%; }
        .glass-input-wrap .g-icon {
            position: absolute; left: 16px !important; top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.5); font-size: 1.05rem !important;
            pointer-events: none; transition: color 0.2s;
            z-index: 2;
        }
        .glass-input {
            width: 100%;
            padding: 14px 16px 14px 50px;
            border-radius: 11px;
            border: 1.5px solid rgba(212, 175, 55, 0.25);
            background: rgba(0,0,0,0.3);
            color: white;
            font-size: 0.92rem;
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
        .glass-input.has-eye { padding-right: 50px !important; }
        .eye-toggle {
            position: absolute; right: 8px; top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.65); font-size: 1.05rem;
            cursor: pointer; background: none; border: none; padding: 0;
            transition: color 0.2s; line-height: 1;
            z-index: 10 !important;
            min-width: 44px !important;
            min-height: 44px !important;
            width: 44px !important;
            height: 44px !important;
            display: inline-flex; align-items: center; justify-content: center;
            user-select: none !important;
            -webkit-user-select: none !important;
            -webkit-touch-callout: none !important;
            -webkit-tap-highlight-color: transparent !important;
            touch-action: manipulation !important;
            border-radius: 10px;
        }
        .eye-toggle:hover { color: white; }
        .eye-toggle:focus-visible {
            outline: 2px solid rgba(212, 175, 55, 0.75) !important;
            outline-offset: 2px !important;
            color: #ffffff !important;
        }
        .eye-toggle i { pointer-events: none !important; font-size: 1.15rem !important; }


        /* Fingerprint/Biometric row */
        .fp-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 14px; /* Increased padding */
            border-radius: 10px;
            border: 1.5px solid rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.08);
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 8px; /* Increased margin */
            width: 100%;
            font-family: inherit;
            color: inherit;
            outline: none;
            text-align: left;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            touch-action: manipulation;
        }
        .fp-row * { pointer-events: none; }
        @media (hover: hover) and (pointer: fine) {
            .fp-row:hover { background: rgba(212, 175, 55, 0.15); border-color: rgba(212, 175, 55, 0.4); }
        }
        .fp-row:active {
            transform: scale(0.98);
            background: rgba(212, 175, 55, 0.22);
            border-color: rgba(212, 175, 55, 0.55);
        }
        .fp-row:focus { outline: none; }
        .fp-row:focus-visible { outline: 2px solid rgba(212, 175, 55, 0.6); outline-offset: 2px; }
        .fp-row-left { display: flex; align-items: center; gap: 10px; text-align: left; }
        .fp-row-left i { font-size: 1.15rem; color: rgba(255,255,255,0.85); transition: all 0.3s; }
        .fp-row-label { font-size: 0.82rem; font-weight: 600; color: white; transition: all 0.2s; }
        .fp-row-hint { font-size: 0.68rem; color: rgba(255,255,255,0.5); margin-top: 1px; transition: all 0.2s; }
        .fp-row-arrow { color: rgba(255,255,255,0.4); font-size: 0.8rem; transition: all 0.2s; }

        /* Divider */
                #reEnrollBioLink, [id*='reEnrollBio'] { display: none !important; pointer-events: none !important; opacity: 0 !important; visibility: hidden !important; }

.glass-divider {
            display: flex; align-items: center; gap: 10px;
            margin: 10px 0; color: rgba(255,255,255,0.35); font-size: 0.72rem; /* Increased margin */
        }
        .glass-divider::before, .glass-divider::after {
            content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.18);
        }

        /* Submit button */
        .glass-btn {
            width: 100%; padding: 15px; /* Increased padding */
            background: rgba(255,255,255,0.95);
            color: #800000;
            font-weight: 800; font-size: 0.95rem; /* Increased font size */
            letter-spacing: 0.5px;
            border: none; border-radius: 11px;
            cursor: pointer; transition: all 0.25s ease;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            margin-top: 6px; /* Increased spacing */
        }
        .glass-btn-primary {
            background: rgba(255,255,255,0.95);
            color: #800000;
        }
        .glass-btn-secondary {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.9);
            border: 1.5px solid rgba(255,255,255,0.2);
        }
        .glass-btn:hover { background: white; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
        .glass-btn:active { transform: translateY(0); }
        .glass-btn.admin-variant { background: rgba(107,0,32,0.9); color: white; border: 1px solid rgba(255,255,255,0.2); }
        .glass-btn.admin-variant:hover { background: rgba(107,0,32,1); }

        .glass-note-link {
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.76rem; color: rgba(255,255,255,0.65);
            text-decoration: none; transition: all 0.2s ease;
            padding: 4px 0;
        }
        .glass-note-link:hover {
            color: rgba(255,255,255,0.95);
            text-decoration: underline;
        }

        /* Links */
        .glass-link-row { text-align: center; font-size: 0.78rem; color: rgba(255,255,255,0.5); margin-top: 8px; }
        .glass-link-row a { color: rgba(255,255,255,0.85); font-weight: 700; text-decoration: none; transition: color 0.2s; }
        .glass-link-row a:hover { color: white; text-decoration: underline; }

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

        /* ── Biometric Popup Modal ── */
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
            background: rgba(10, 4, 4, 0.78);
            backdrop-filter: blur(14px) saturate(160%);
            -webkit-backdrop-filter: blur(14px) saturate(160%);
        }
        .bio-modal-dialog {
            position: relative;
            width: 100%;
            max-width: 420px;
            z-index: 2;
            transform: scale(0.92) translateY(14px);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .bio-modal-overlay.active .bio-modal-dialog {
            transform: scale(1) translateY(0);
        }
        .bio-modal-card {
            background: linear-gradient(155deg, rgba(38, 18, 20, 0.96) 0%, rgba(20, 9, 11, 0.98) 100%);
            border: 1px solid rgba(212, 175, 55, 0.38);
            border-top: 1.5px solid rgba(245, 218, 138, 0.6);
            border-radius: 26px;
            box-shadow: 0 25px 65px -10px rgba(0, 0, 0, 0.85), 0 0 45px rgba(212, 175, 55, 0.14), inset 0 1px 1px rgba(255, 255, 255, 0.25);
            padding: 28px 24px 22px;
            color: #f5eedb;
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
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
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
            background: radial-gradient(circle at 35% 35%, rgba(212, 175, 55, 0.3) 0%, rgba(139, 0, 0, 0.45) 60%, rgba(26, 9, 11, 0.9) 100%);
            border: 2px solid rgba(212, 175, 55, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.15rem;
            color: #f5d77f;
            box-shadow: 0 0 28px rgba(212, 175, 55, 0.28), inset 0 2px 4px rgba(255, 255, 255, 0.2);
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
            border: 2.5px solid #1c0a0c;
        }
        .bio-modal-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            background: linear-gradient(135deg, #ffffff 40%, #f6e6bd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .bio-modal-user-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.16) 0%, rgba(128, 0, 0, 0.22) 100%);
            border: 1px solid rgba(212, 175, 55, 0.42);
            color: #f5dfa8;
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }
        .bio-modal-desc {
            font-size: 0.86rem;
            line-height: 1.52;
            color: rgba(255, 255, 255, 0.82);
            margin-bottom: 16px;
            word-wrap: break-word;
        }
        .bio-modal-desc strong {
            color: #f7e4b5;
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
            color: rgba(245, 223, 168, 0.9);
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
            border: 1.5px solid rgba(212, 175, 55, 0.32) !important;
            background: rgba(8, 3, 4, 0.55) !important;
            color: #ffffff !important;
            font-size: 0.92rem !important;
            font-family: 'Inter', sans-serif !important;
            outline: none !important;
            transition: all 0.25s ease !important;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.5) !important;
        }
        .bio-modal-card .glass-input::placeholder {
            color: rgba(255, 255, 255, 0.38) !important;
            font-size: 0.86rem !important;
        }
        .bio-modal-card .glass-input:hover {
            border-color: rgba(212, 175, 55, 0.5) !important;
            background: rgba(8, 3, 4, 0.65) !important;
        }
        .bio-modal-card .glass-input:focus {
            border-color: #d4af37 !important;
            background: rgba(8, 3, 4, 0.8) !important;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.22), inset 0 1px 3px rgba(0, 0, 0, 0.6) !important;
        }
        .bio-modal-card .glass-input-wrap .g-icon {
            position: absolute !important;
            left: 15px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: rgba(212, 175, 55, 0.75) !important;
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
            color: rgba(255, 255, 255, 0.55) !important;
            cursor: pointer !important;
            border-radius: 9px !important;
            transition: all 0.2s ease !important;
            z-index: 5 !important;
            padding: 0 !important;
        }
        .bio-modal-card .eye-toggle:hover {
            color: #f5dfa8 !important;
            background: rgba(255, 255, 255, 0.08) !important;
        }
        .bio-modal-card .eye-toggle:active {
            transform: translateY(-50%) scale(0.92) !important;
        }
        .bio-modal-card .eye-toggle i {
            font-size: 1.15rem !important;
            pointer-events: none !important;
        }

        .bio-modal-steps {
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 12px 16px;
            text-align: left;
            margin-bottom: 20px;
        }
        .bio-modal-steps-header {
            font-size: 0.74rem;
            font-weight: 700;
            color: #d4af37;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.35;
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
            background: linear-gradient(135deg, #e8c872 0%, #d4af37 50%, #b89122 100%) !important;
            color: #240a0c !important;
            font-weight: 800 !important;
            font-size: 0.92rem !important;
            letter-spacing: 0.6px !important;
            text-transform: uppercase !important;
            border: 1px solid rgba(255, 255, 255, 0.45) !important;
            border-radius: 14px !important;
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.32), 0 2px 6px rgba(0, 0, 0, 0.4) !important;
            cursor: pointer !important;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-shadow: 0 1px 0 rgba(255, 255, 255, 0.25) !important;
        }
        .bio-modal-actions #bioModalPrimaryBtn:hover:not(:disabled) {
            background: linear-gradient(135deg, #f0d588 0%, #dfba42 50%, #c49d2a 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.45), 0 3px 8px rgba(0, 0, 0, 0.5) !important;
        }
        .bio-modal-actions #bioModalPrimaryBtn:active:not(:disabled) {
            transform: translateY(0) !important;
            box-shadow: 0 3px 12px rgba(212, 175, 55, 0.25) !important;
        }
        .bio-modal-actions #bioModalPrimaryBtn:disabled {
            opacity: 0.65 !important;
            cursor: not-allowed !important;
            transform: none !important;
        }
        .bio-modal-secondary-btn {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.72);
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
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border-color: rgba(212, 175, 55, 0.35);
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
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(212, 175, 55, 0.25);
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
            background: rgba(212, 175, 55, 0.12);
            border-color: rgba(212, 175, 55, 0.6);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
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
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(212, 175, 55, 0.14);
            border: 1px solid rgba(212, 175, 55, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.22rem;
            color: #d4af37;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .bio-method-card:hover .bio-method-icon-box {
            background: rgba(212, 175, 55, 0.25);
            border-color: #d4af37;
            color: #f3e7cd;
            transform: scale(1.05);
        }
        .bio-method-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        .bio-method-name {
            font-size: 0.88rem;
            font-weight: 700;
            color: #f3e7cd;
            letter-spacing: -0.2px;
        }
        .bio-method-desc {
            font-size: 0.73rem;
            color: rgba(255, 255, 255, 0.65);
            line-height: 1.3;
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
            border-radius: 28px;
            overflow: hidden;
            background: #000000;
            border: 2.5px solid rgba(6, 182, 212, 0.6);
            box-shadow: 0 0 35px rgba(6, 182, 212, 0.3), inset 0 0 25px rgba(6, 182, 212, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: box-shadow 0.25s ease, border-color 0.25s ease;
        }
        @media (max-width: 480px) {
            .bio-login-camera-box {
                max-width: min(340px, 84vw);
                height: min(340px, 84vw);
                border-radius: 22px;
            }
        }
        /* Face Flash / Fill Light Controls */
        .face-hud-flash-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 25;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            background: rgba(15, 23, 42, 0.85);
            border: 1.5px solid rgba(255, 255, 255, 0.35);
            color: #f1f5f9;
            font-size: 0.78rem;
            font-weight: 600;
            border-radius: 20px;
            cursor: pointer;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.5);
        }
        .face-hud-flash-btn:hover {
            background: rgba(30, 41, 59, 0.95);
            border-color: #facc15;
            color: #fef08a;
            transform: scale(1.05);
            box-shadow: 0 0 16px rgba(250, 204, 21, 0.55);
        }
        .face-hud-flash-btn.active-flash {
            background: linear-gradient(135deg, #facc15, #f59e0b) !important;
            border-color: #ffffff !important;
            color: #0f172a !important;
            box-shadow: 0 0 20px rgba(250, 204, 21, 0.9), 0 0 8px #ffffff !important;
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
            50% { border-color: #facc15; box-shadow: 0 0 16px rgba(250, 204, 21, 0.75); color: #facc15; }
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
            box-shadow: inset 0 0 60px 20px rgba(255, 255, 255, 0.95), 0 0 80px 25px rgba(255, 255, 255, 0.9);
            border: 4px solid #ffffff;
        }
        .face-screen-flash-overlay.active {
            opacity: 1;
        }

        .face-scan-frame.flash-on,
        .bio-login-camera-box.flash-on {
            border-color: #ffffff !important;
            box-shadow: 0 0 0 12px #ffffff, 0 0 80px 30px rgba(255, 255, 255, 0.95), 0 0 140px 60px rgba(255, 255, 255, 0.8) !important;
        }

        /* Modal-wide screen fill light when flash is active */
        .bio-modal-card.face-screen-fill-active {
            background: #ffffff !important;
            box-shadow: 0 0 140px 60px rgba(255, 255, 255, 0.95), 0 0 0 9999px rgba(255, 255, 255, 0.45) !important;
            color: #0f172a !important;
            border-color: #ffffff !important;
        }
        .bio-modal-card.face-screen-fill-active .bio-modal-title,
        .bio-modal-card.face-screen-fill-active #bioModalTitle {
            color: #0f172a !important;
            text-shadow: none !important;
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
            background: radial-gradient(circle, rgba(6, 182, 212, 0.15) 0%, rgba(0, 0, 0, 0.9) 80%);
        }
        .bio-login-reticle-corner {
            position: absolute;
            width: 24px;
            height: 24px;
            border-color: #06b6d4;
            border-style: solid;
            pointer-events: none;
            z-index: 10;
        }
        .bio-login-reticle-corner.tl { top: 12px; left: 12px; border-width: 3px 0 0 3px; border-top-left-radius: 10px; }
        .bio-login-reticle-corner.tr { top: 12px; right: 12px; border-width: 3px 3px 0 0; border-top-right-radius: 10px; }
        .bio-login-reticle-corner.bl { bottom: 12px; left: 12px; border-width: 0 0 3px 3px; border-bottom-left-radius: 10px; }
        .bio-login-reticle-corner.br { bottom: 12px; right: 12px; border-width: 0 3px 3px 0; border-bottom-right-radius: 10px; }
        
        .bio-login-laser-bar {
            position: absolute;
            left: 5%;
            width: 90%;
            height: 3px;
            z-index: 4;
            background: linear-gradient(90deg, transparent 0%, #06b6d4 35%, #38bdf8 50%, #06b6d4 65%, transparent 100%);
            box-shadow: 0 0 14px #38bdf8;
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
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.78);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 99px;
            padding: 4px 14px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 6px;
            z-index: 10;
            white-space: nowrap;
        }
        .bio-login-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #06b6d4;
            box-shadow: 0 0 8px #06b6d4;
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
            background: rgba(255, 255, 255, 0.1);
            border-radius: 99px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .bio-login-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #06b6d4, #38bdf8);
            border-radius: 99px;
            transition: width 0.22s ease;
        }
        .bio-login-progress-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.72rem;
            color: rgba(255, 255, 255, 0.75);
            margin-top: 6px;
            line-height: 1.3;
        }


        /* Desktop elevation for optical vertical centering */
        @media (min-width: 769px) {
            .auth-scene {
                padding-top: calc(env(safe-area-inset-top, 0px) + 68px);
                padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 48px);
            }
            .glass-card {
                max-width: 520px;
                padding: 32px 30px 26px;
            }
        }

        /* Mobile and Responsive */
        @media (max-width: 768px) {
            .auth-scene {
                padding-top: calc(env(safe-area-inset-top, 0px) + 56px);
                padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 32px);
                padding-left: calc(env(safe-area-inset-left, 0px) + 16px);
                padding-right: calc(env(safe-area-inset-right, 0px) + 16px);
            }
            .glass-card { 
                max-width: 100%;
                padding: 24px 22px 20px; 
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
                padding-top: calc(env(safe-area-inset-top, 0px) + 50px);
                padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 20px);
                padding-left: calc(env(safe-area-inset-left, 0px) + 12px);
                padding-right: calc(env(safe-area-inset-right, 0px) + 12px);
            }
            .glass-card { 
                max-width: 100%;
                min-width: 260px;
                padding: 22px 18px 18px; 
                border-radius: 18px; 
                margin: auto 0;
            }
            .glass-title { font-size: 1.35rem; }
            .glass-sub { font-size: 0.82rem; margin-bottom: 16px; }
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
                padding: 18px 14px 16px; 
                border-radius: 16px;
            }
            .glass-title { font-size: 1.2rem; }
            .glass-sub { font-size: 0.75rem; }
            .glass-input { font-size: 0.82rem; padding: 11px 12px 11px 40px; }
            .glass-btn { font-size: 0.85rem; padding: 12px; }
        }

        /* Landscape orientation adjustments & short screen heights */
        @media (max-height: 620px) {
            .auth-scene {
                padding-top: calc(env(safe-area-inset-top, 0px) + 40px);
                padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 16px);
            }
            .glass-card { padding: 18px 20px 16px; }
            .glass-logo { width: 46px; height: 46px; margin-bottom: 8px; }
            .glass-title { font-size: 1.25rem; margin-bottom: 3px; }
            .glass-sub { font-size: 0.76rem; margin-bottom: 12px; }
            .role-toggle { margin-bottom: 12px; padding: 3px; }
            .role-btn { padding: 7px 10px; font-size: 0.8rem; }
            .top-bar { display: none; }
            .bottom-bar { display: none; }
        }

    </style>
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
                <div id="fpMessage" style="display:none; border-radius:10px; padding:10px 14px; font-size:0.8rem; margin-bottom:8px; line-height:1.4;"></div>
                <button type="button" class="fp-row anim-fade-up anim-d5" id="fpRowBtn" aria-label="Sign in with Biometrics">
                    <div class="fp-row-left">
                        <i class="bi bi-fingerprint" id="fpIcon"></i>
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

        <div style="text-align:center;margin-top:8px;" class="anim-fade-up anim-d7">
            <a href="#" id="useRecoveryCodeLink" class="glass-note-link">
                <i class="bi bi-key me-1"></i>Use Recovery Code
            </a>
        </div>
        <div class="glass-link-row anim-fade-up anim-d7">
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
<div id="biometricModal" class="bio-modal-overlay" style="display:none;" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="bio-modal-backdrop" id="bioModalBackdrop"></div>
    <div class="bio-modal-dialog">
        <div class="bio-modal-card">
            <!-- Close button -->
            <button type="button" class="bio-modal-close" id="bioModalCloseBtn" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>

            <!-- Header Icon -->
            <div class="bio-modal-icon-wrap" id="bioModalIconWrap">
                <div class="bio-modal-icon-circle">
                    <i class="bi bi-fingerprint" id="bioModalIcon"></i>
                </div>
                <span class="bio-modal-badge" id="bioModalBadge"><i class="bi bi-exclamation-triangle-fill"></i></span>
            </div>

            <!-- Title & User Identifier Badge -->
            <h4 class="bio-modal-title" id="bioModalTitle">BIOMETRIC NOT REGISTERED</h4>
            <div class="bio-modal-user-pill" id="bioModalUserPill" style="display:none;">
                <i class="bi bi-person-badge me-1"></i><span id="bioModalUserText"></span>
            </div>
            <p class="bio-modal-desc" id="bioModalDesc">
                Biometric login is not registered for this account. Please use your password or register your biometrics first.
            </p>

            <!-- Biometric Method Selection List -->
            <div id="bioModalMethodsWrap" style="display:none;">
                <div class="bio-method-list" id="bioModalMethodsList"></div>
            </div>

            <!-- Face Recognition Camera Scanner Wrap (Inside Modal) -->
            <div id="bioModalFaceScannerWrap" style="display:none; margin: 14px 0;">
                <div class="bio-login-camera-box" id="bioLoginCameraBox">
                    <button type="button" id="bioLoginFlashToggleBtn" class="face-hud-flash-btn" onclick="toggleBioLoginFlash()" title="Toggle Flash / Fill Light" aria-label="Toggle Flash">
                        <i class="bi bi-lightning-fill"></i>
                        <span class="flash-text">Flash</span>
                    </button>
                    <div id="bioLoginScreenFlashOverlay" class="face-screen-flash-overlay"></div>
                    <div class="bio-login-reticle-corner tl"></div>
                    <div class="bio-login-reticle-corner tr"></div>
                    <div class="bio-login-reticle-corner bl"></div>
                    <div class="bio-login-reticle-corner br"></div>
                    <div class="bio-login-laser-bar" id="bioLoginLaserBar"></div>
                    <video id="bioLoginFaceVideo" class="bio-login-face-feed" autoplay playsinline muted></video>
                    <div class="bio-login-face-holo" id="bioLoginFaceHolo" style="display:none;">
                        <i class="bi bi-person-bounding-box" style="font-size:3.5rem; color:#06b6d4; opacity:0.6;"></i>
                    </div>
                    <div class="bio-login-hud-badge" id="bioLoginHudBadge">
                        <span class="bio-login-pulse-dot"></span>
                        <span id="bioLoginHudStatus">POSITION FACE</span>
                    </div>
                </div>
                <div class="bio-login-progress-wrap">
                    <div class="bio-login-progress-bar">
                        <div class="bio-login-progress-fill" id="bioLoginFaceProgressFill" style="width: 15%;"></div>
                    </div>
                    <div class="bio-login-progress-meta">
                        <span id="bioLoginFaceStateLabel">Searching for face in camera frame...</span>
                        <span id="bioLoginFacePctLabel">15%</span>
                    </div>
                </div>
            </div>

            <!-- Inline Password Verification Field (for setup flow) -->
            <div id="bioModalPasswordWrap" style="display:none;">
                <div class="bio-modal-input-label">
                    <span><i class="bi bi-shield-lock-fill me-1" style="color:#d4af37;"></i> Account Password Verification</span>
                </div>
                <div class="glass-input-wrap">
                    <i class="bi bi-lock-fill g-icon"></i>
                    <input type="password" id="bioModalPasswordInput" class="glass-input has-eye" placeholder="Enter your password" autocomplete="current-password">
                    <button type="button" class="eye-toggle" data-toggle-password="bioModalPasswordInput" aria-controls="bioModalPasswordInput" aria-label="Show password" title="Show password" aria-pressed="false">
                        <i class="bi bi-eye-slash"></i>
                    </button>
                </div>
            </div>

            <!-- Inline Alert Message -->
            <div id="bioModalAlert" style="display:none; border-radius:12px; padding:10px 14px; font-size:0.82rem; margin:12px 0 4px 0; text-align:left; line-height:1.4;"></div>

            <!-- How to Setup Steps (Optional info) -->
            <div class="bio-modal-steps" id="bioModalSteps" style="display:none;">
                <div class="bio-modal-steps-header">
                    <i class="bi bi-info-circle me-1"></i> How to set up biometric login:
                </div>
                <ol class="bio-modal-steps-list">
                    <li>
                        <span class="step-num">1</span>
                        <span>Tap <strong>Set Up Biometrics</strong> to register your device sensor.</span>
                    </li>
                    <li>
                        <span class="step-num">2</span>
                        <span>Verify your account password when prompted.</span>
                    </li>
                    <li>
                        <span class="step-num">3</span>
                        <span>Touch your fingerprint sensor or scan Face ID when prompted by your browser.</span>
                    </li>
                    <li>
                        <span class="step-num">4</span>
                        <span>Future sign-ins will require only your biometric authentication.</span>
                    </li>
                </ol>
            </div>

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
        }

        try {
            var devKey = (typeof window.getOrCreateDeviceKey === 'function')
                ? window.getOrCreateDeviceKey()
                : (localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || '');
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
var isBioPending = false;

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
    if (typeof fpRowBtn.blur === 'function') fpRowBtn.blur();
    if (fpLabel) fpLabel.textContent = 'Sign in with Biometrics';
    if (fpHint) fpHint.textContent = 'Fingerprint, Face ID, or device security';
    if (fpIcon) fpIcon.className = 'bi bi-fingerprint';
    if (fpArrow) fpArrow.className = 'bi bi-chevron-right fp-row-arrow';
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
    el.innerHTML = html;
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
    var stepsEl = document.getElementById('bioModalSteps');
    var primaryBtn = document.getElementById('bioModalPrimaryBtn');
    var secondaryBtn = document.getElementById('bioModalSecondaryBtn');
    var badgeEl = document.getElementById('bioModalBadge');
    var passWrap = document.getElementById('bioModalPasswordWrap');
    var alertEl = document.getElementById('bioModalAlert');
    
    if (titleEl) titleEl.textContent = config.title || 'BIOMETRIC NOT REGISTERED';
    if (descEl) descEl.innerHTML = config.message || 'Biometric login is not registered for this account.<br><br>Please use your password or register your biometrics first.';
    
    if (config.identifier) {
        activeSetupIdentifier = config.identifier;
        if (pillEl) pillEl.style.display = 'inline-flex';
        if (pillTextEl) pillTextEl.textContent = config.identifier;
    } else {
        if (pillEl) pillEl.style.display = 'none';
    }
    
    if (stepsEl) {
        stepsEl.style.display = 'none';
    }

    var faceWrap = document.getElementById('bioModalFaceScannerWrap');
    if (faceWrap) {
        faceWrap.style.display = 'none';
    }

    if (passWrap) {
        passWrap.style.display = config.showPassword ? 'block' : 'none';
    }

    if (alertEl) {
        if (config.alertMessage) {
            alertEl.style.display = 'block';
            alertEl.style.background = config.alertType === 'error' ? 'rgba(220,38,38,0.2)' : 'rgba(212,175,55,0.15)';
            alertEl.style.color = config.alertType === 'error' ? '#fca5a5' : '#f3e7cd';
            alertEl.style.border = config.alertType === 'error' ? '1px solid rgba(220,38,38,0.4)' : '1px solid rgba(212,175,55,0.4)';
            alertEl.innerHTML = config.alertMessage;
        } else {
            alertEl.style.display = 'none';
        }
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

    if (primaryBtn && primaryBtn.dataset.biometricBound !== 'true') {
        primaryBtn.dataset.biometricBound = 'true';
        if (config.hidePrimaryBtn || config.primaryBtnText === false) {
            primaryBtn.style.display = 'none';
        } else {
            primaryBtn.style.display = 'block';
            if (config.primaryBtnText) {
                primaryBtn.innerHTML = config.primaryBtnText;
            } else {
                primaryBtn.innerHTML = '<i class="bi bi-shield-lock-fill me-2"></i>SET UP BIOMETRICS';
            }
            primaryBtn.disabled = !!config.primaryDisabled;
        }
    }

    var chooseMethodBtn = document.getElementById('bioModalChooseMethodBtn');
    if (chooseMethodBtn) {
        if (config.showChooseMethodBtn) {
            chooseMethodBtn.style.display = 'block';
            if (config.chooseMethodBtnText) {
                chooseMethodBtn.innerHTML = config.chooseMethodBtnText;
            } else {
                chooseMethodBtn.innerHTML = '<i class="bi bi-grid-fill me-1"></i>Choose Another Method';
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
            chooseMethodBtn.style.display = 'none';
        }
    }

    if (secondaryBtn && secondaryBtn.dataset.biometricBound !== 'true') {
        secondaryBtn.dataset.biometricBound = 'true';
        if (config.secondaryBtnText === '' || config.secondaryBtnText === false) {
            secondaryBtn.style.display = 'none';
        } else {
            secondaryBtn.style.display = 'block';
            secondaryBtn.textContent = config.secondaryBtnText || 'USE PASSWORD';
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

    if (badgeEl) {
        if (config.badgeType === 'danger') {
            badgeEl.style.background = '#ef4444';
            badgeEl.innerHTML = '<i class="bi bi-x-lg"></i>';
        } else if (config.badgeType === 'success') {
            badgeEl.style.background = '#22c55e';
            badgeEl.innerHTML = '<i class="bi bi-check-lg"></i>';
        } else if (config.badgeType === 'info') {
            badgeEl.style.background = '#3b82f6';
            badgeEl.innerHTML = '<i class="bi bi-shield-check"></i>';
        } else if (config.badgeType === 'warning') {
            badgeEl.style.background = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
            badgeEl.innerHTML = '<i class="bi bi-shield-lock-fill"></i>';
        } else {
            badgeEl.style.background = '#d4af37';
            badgeEl.innerHTML = '<i class="bi bi-fingerprint"></i>';
        }
    }
    
    modal.style.display = 'flex';
    void modal.offsetHeight;
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
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
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    setTimeout(function() {
        if (!modal.classList.contains('active')) {
            modal.style.display = 'none';
        }
    }, 300);
}

function closeBiometricModalAndFocusPassword() {
    closeBiometricModal();
    setTimeout(function() {
        focusPasswordField();
    }, 320);
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

// Close biometric modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var modal = document.getElementById('biometricModal');
        if (modal && modal.classList.contains('active')) {
            closeBiometricModal();
        }
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
            message: 'Please enter your password for <strong>' + activeSetupIdentifier + '</strong> to verify your identity and enable biometric sign-in.',
            showPassword: true,
            badgeType: 'warning',
            primaryBtnText: '<i class="bi bi-check-circle-fill me-2"></i>VERIFY & ENABLE BIOMETRICS',
            secondaryBtnText: 'USE PASSWORD',
            onPrimaryClick: handleSetupBiometricsClick,
            onSecondaryClick: closeBiometricModalAndFocusPassword
        });
        setTimeout(function() {
            var pInput = document.getElementById('bioModalPasswordInput');
            if (pInput) pInput.focus();
        }, 100);
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

    openBiometricModal({
        title: 'SETTING UP BIOMETRICS',
        identifier: identifier,
        message: 'Verifying account credentials...',
        badgeType: 'info',
        showPassword: false,
        primaryBtnText: '<i class="bi bi-hourglass-split me-2"></i>Verifying...',
        primaryDisabled: true,
        secondaryBtnText: 'Cancel',
        onSecondaryClick: closeBiometricModal
    });

    try {
        var optRes = await fetch('{{ route("webauthn.setup.options") }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ identifier: identifier, password: password })
        });

        var opts = await optRes.json();

        if (!optRes.ok || !opts.success) {
            var errMsg = opts.message || 'Verification failed. Please check your password.';
            openBiometricModal({
                title: 'ACCOUNT VERIFICATION FAILED',
                identifier: identifier,
                message: errMsg,
                showPassword: true,
                badgeType: 'danger',
                alertMessage: '<i class="bi bi-exclamation-circle-fill me-2"></i>' + errMsg,
                alertType: 'error',
                primaryBtnText: '<i class="bi bi-arrow-repeat me-2"></i>TRY AGAIN',
                secondaryBtnText: 'USE PASSWORD',
                onPrimaryClick: handleSetupBiometricsClick,
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        }

        openBiometricModal({
            title: 'TOUCH SENSOR OR SCAN FACE ID',
            identifier: identifier,
            message: 'Please authenticate using your device\'s fingerprint, Face ID, or screen lock when prompted by your device.',
            badgeType: 'info',
            primaryBtnText: '<i class="bi bi-hand-index-thumb me-2"></i>Waiting for Biometric Prompt...',
            primaryDisabled: true,
            secondaryBtnText: 'Cancel',
            onSecondaryClick: closeBiometricModal
        });

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
                publicKey: Object.assign({}, basePublicKey, {
                    authenticatorSelection: loginPrimarySelection
                })
            });
        } catch (firstErr) {
            if (firstErr.name === 'AbortError' || firstErr.name === 'NotAllowedError') {
                throw firstErr;
            }
            console.warn('Biometric setup (primary attempt) failed, retrying with platform discouraged fallback:', firstErr);
            try {
                credential = await navigator.credentials.create({
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

        if (!credential) {
            throw new Error('Biometric setup was cancelled.');
        }

        var rawId = bufferToBase64Url(credential.rawId);
        var clientDataJSON = bufferToBase64Url(credential.response.clientDataJSON);
        var attestationObject = bufferToBase64Url(credential.response.attestationObject);

        var saveRes = await fetch('{{ route("webauthn.setup.register") }}', {
            method: 'POST',
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

        if (regResult.success) {
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

    var hasCamera = !!(navigator.mediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function');

    _deviceBioCapabilitiesCache = {
        isWebAuthnSupported: isWebAuthnSupported,
        isPlatformAvailable: isPlatformAvailable,
        platformAvailabilityKnown: platformAvailabilityKnown,
        isIOS: isIOS,
        isIosFaceId: isIosFaceId,
        isAndroid: isAndroid,
        isWindows: isWindows,
        isMac: isMac,
        hasCamera: hasCamera
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

    // Method 2: Face Recognition / Face ID
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

// Reusable offscreen canvas & native detector cache for real-time face frame processing
var _faceCanvas = null;
var _nativeFaceDetector = null;
var bioFaceLoginActive = false;
var bioFaceLoginStream = null;

if ('FaceDetector' in window) {
    try {
        _nativeFaceDetector = new window.FaceDetector({ fastMode: true, maxDetectedFaces: 5 });
    } catch(e) {
        _nativeFaceDetector = null;
    }
}

var isBioLoginFlashActive = false;

async function toggleBioLoginFlash() {
    isBioLoginFlashActive = !isBioLoginFlashActive;
    var overlay = document.getElementById('bioLoginScreenFlashOverlay');
    var btn = document.getElementById('bioLoginFlashToggleBtn');
    var box = document.getElementById('bioLoginCameraBox');
    var modalCard = document.querySelector('.bio-modal-card');

    if (overlay) {
        if (isBioLoginFlashActive) {
            overlay.classList.add('active');
        } else {
            overlay.classList.remove('active');
        }
    }

    if (box) {
        if (isBioLoginFlashActive) {
            box.classList.add('flash-on');
        } else {
            box.classList.remove('flash-on');
        }
    }

    if (modalCard) {
        if (isBioLoginFlashActive) {
            modalCard.classList.add('face-screen-fill-active');
        } else {
            modalCard.classList.remove('face-screen-fill-active');
        }
    }

    if (btn) {
        btn.classList.remove('flash-suggest-pulse');
        var textSpan = btn.querySelector('.flash-text');
        if (isBioLoginFlashActive) {
            btn.classList.add('active-flash');
            if (textSpan) textSpan.textContent = 'Flash ON';
            btn.setAttribute('title', 'Turn Flash / Fill Light OFF');
            btn.setAttribute('aria-pressed', 'true');
        } else {
            btn.classList.remove('active-flash');
            if (textSpan) textSpan.textContent = 'Flash';
            btn.setAttribute('title', 'Turn Flash / Fill Light ON');
            btn.setAttribute('aria-pressed', 'false');
        }
    }

    if (bioFaceLoginStream) {
        try {
            var track = bioFaceLoginStream.getVideoTracks()[0];
            if (track) {
                var capabilities = track.getCapabilities ? track.getCapabilities() : {};
                if (capabilities.torch || ('torch' in capabilities)) {
                    await track.applyConstraints({
                        advanced: [{ torch: isBioLoginFlashActive }]
                    });
                }
            }
        } catch(e) {
            console.warn('Hardware torch toggle not supported on this device/track:', e);
        }
    }
    if (window.triggerHaptic) window.triggerHaptic('light');
    return isBioLoginFlashActive;
}

function resetBioLoginFlash() {
    isBioLoginFlashActive = false;
    var overlay = document.getElementById('bioLoginScreenFlashOverlay');
    var btn = document.getElementById('bioLoginFlashToggleBtn');
    var box = document.getElementById('bioLoginCameraBox');
    var modalCard = document.querySelector('.bio-modal-card');
    if (overlay) overlay.classList.remove('active');
    if (box) box.classList.remove('flash-on');
    if (modalCard) modalCard.classList.remove('face-screen-fill-active');
    if (btn) {
        btn.classList.remove('active-flash');
        btn.classList.remove('flash-suggest-pulse');
        var textSpan = btn.querySelector('.flash-text');
        if (textSpan) textSpan.textContent = 'Flash';
        btn.setAttribute('title', 'Toggle Flash / Fill Light');
        btn.setAttribute('aria-pressed', 'false');
    }
}

function triggerCaptureFlash(containerId) {
    var container = document.getElementById(containerId);
    if (!container) return;
    container.classList.remove('face-flash-burst');
    void container.offsetWidth;
    container.classList.add('face-flash-burst');
    setTimeout(function() {
        container.classList.remove('face-flash-burst');
    }, 400);
}

async function detectAndAnalyzeFaceFrame(video) {
    // 1. Validate video readyState and dimensions
    if (!video || !video.videoWidth || !video.videoHeight || video.readyState < 2) {
        return {
            status: 'NO_FACE',
            passed: false,
            facesCount: 0,
            score: 0,
            message: 'No face detected. Please position your face in front of the camera.'
        };
    }
    if (video.paused && video.srcObject) {
        try { await video.play(); } catch(e) {}
    }

    var vw = 160;
    var vh = 160;
    if (!_faceCanvas) {
        _faceCanvas = document.createElement('canvas');
        _faceCanvas.width = vw;
        _faceCanvas.height = vh;
    }
    var ctx = _faceCanvas.getContext('2d', { willReadFrequently: true });
    if (!ctx) {
        return {
            status: 'NO_FACE',
            passed: false,
            facesCount: 0,
            score: 0,
            message: 'No face detected. Please position your face in front of the camera.'
        };
    }

    // Undistorted center-crop: crop square region from center of video to preserve true facial aspect ratio
    var srcW = video.videoWidth;
    var srcH = video.videoHeight;
    var minDim = Math.min(srcW, srcH);
    var sx = Math.max(0, (srcW - minDim) / 2);
    var sy = Math.max(0, (srcH - minDim) / 2);

    ctx.drawImage(video, sx, sy, minDim, minDim, 0, 0, vw, vh);
    var imgData = ctx.getImageData(0, 0, vw, vh);
    var pixels = imgData.data;

    // 2. Global Frame Quality Checks (Illumination, Glare, Blurriness)
    var totalLuma = 0;
    var minLuma = 255;
    var maxLuma = 0;
    var sampledCount = 0;
    var lumaSumSq = 0;

    var totalEdgeEnergy = 0;
    var edgeSamples = 0;

    for (var y = 0; y < vh; y += 2) {
        for (var x = 0; x < vw; x += 2) {
            var idx = (y * vw + x) * 4;
            var r = pixels[idx];
            var g = pixels[idx + 1];
            var b = pixels[idx + 2];
            var luma = 0.299 * r + 0.587 * g + 0.114 * b;

            totalLuma += luma;
            lumaSumSq += luma * luma;
            if (luma < minLuma) minLuma = luma;
            if (luma > maxLuma) maxLuma = luma;
            sampledCount++;

            // Sample edge gradients in central facial zone
            if (x >= 24 && x <= 136 && y >= 24 && y <= 136 && x + 2 < vw && y + 2 < vh) {
                var rightIdx = (y * vw + (x + 2)) * 4;
                var downIdx = ((y + 2) * vw + x) * 4;
                var rightLuma = 0.299 * pixels[rightIdx] + 0.587 * pixels[rightIdx + 1] + 0.114 * pixels[rightIdx + 2];
                var downLuma = 0.299 * pixels[downIdx] + 0.587 * pixels[downIdx + 1] + 0.114 * pixels[downIdx + 2];
                totalEdgeEnergy += Math.abs(luma - rightLuma) + Math.abs(luma - downLuma);
                edgeSamples++;
            }
        }
    }

    var avgLuma = sampledCount > 0 ? (totalLuma / sampledCount) : 0;
    var lumaVariance = sampledCount > 0 ? (lumaSumSq / sampledCount - avgLuma * avgLuma) : 0;
    var lumaStdDev = Math.sqrt(Math.max(0, lumaVariance));
    var lumaContrast = maxLuma - minLuma;
    var avgEdgeGradient = edgeSamples > 0 ? (totalEdgeEnergy / edgeSamples) : 0;

    // Reject dark / covered frame
    if (avgLuma < 10 || (avgLuma < 14 && lumaContrast < 12 && lumaStdDev < 4.0)) {
        return {
            status: 'UNUSABLE',
            passed: false,
            facesCount: 0,
            score: 0,
            message: 'Lighting is too dark. Please improve lighting.'
        };
    }

    // Reject glare
    if (avgLuma > 248 && lumaContrast < 18) {
        return {
            status: 'UNUSABLE',
            passed: false,
            facesCount: 0,
            score: 0,
            message: 'Too much glare. Please improve lighting and face the camera.'
        };
    }

    // Sharpness check: threshold relaxed to 0.28 to accommodate smooth webcams / indoor lighting
    if (avgEdgeGradient < 0.28) {
        return {
            status: 'BLURRY',
            passed: false,
            facesCount: 0,
            score: 0,
            message: 'Camera image is blurry. Please hold steady in front of the camera.'
        };
    }

    // 3. Multi-Method Face Detection
    var nativeFace = null;
    if (_nativeFaceDetector) {
        try {
            var detected = await _nativeFaceDetector.detect(_faceCanvas);
            if (Array.isArray(detected)) {
                if (detected.length > 1) {
                    return {
                        status: 'MULTIPLE_FACES',
                        passed: false,
                        facesCount: detected.length,
                        score: 0,
                        message: 'Multiple faces detected. Please ensure only one person is visible. Multiple faces detected. Please ensure only the intended person is visible.'
                    };
                }
                if (detected.length === 1 && detected[0].boundingBox) {
                    nativeFace = detected[0].boundingBox;
                }
            }
        } catch(err) {}
    }

    var gridCols = 10;
    var gridRows = 10;
    var cellW = vw / gridCols;
    var cellH = vh / gridRows;
    var cellSkinCounts = new Array(gridCols * gridRows).fill(0);
    var cellTotalCounts = new Array(gridCols * gridRows).fill(0);

    for (var y = 0; y < vh; y += 2) {
        var row = Math.min(gridRows - 1, Math.floor(y / cellH));
        for (var x = 0; x < vw; x += 2) {
            var col = Math.min(gridCols - 1, Math.floor(x / cellW));
            var cellIdx = row * gridCols + col;
            cellTotalCounts[cellIdx]++;

            var idx = (y * vw + x) * 4;
            var r = pixels[idx];
            var g = pixels[idx + 1];
            var b = pixels[idx + 2];
            var sumRgb = r + g + b || 1;

            var normR = r / sumRgb;
            var normG = g / sumRgb;

            var yVal  = 0.299 * r + 0.587 * g + 0.114 * b;
            var cbVal = 128 - 0.168736 * r - 0.331264 * g + 0.5 * b;
            var crVal = 128 + 0.5 * r - 0.418688 * g - 0.081312 * b;

            var maxC = Math.max(r, g, b);
            var minC = Math.min(r, g, b);
            var delta = maxC - minC;
            var hue = 0;
            if (delta > 0) {
                if (maxC === r) hue = ((g - b) / delta) % 6;
                else if (maxC === g) hue = (b - r) / delta + 2;
                else hue = (r - g) / delta + 4;
                hue = Math.round(hue * 60);
                if (hue < 0) hue += 360;
            }
            var sat = maxC > 0 ? (delta / maxC) : 0;
            var val = maxC / 255;

            var isYcbcrSkin = (yVal >= 8 && yVal <= 255) &&
                              (cbVal >= 55 && cbVal <= 170) &&
                              (crVal >= 110 && crVal <= 200);

            var isNormRgbSkin = (normR >= 0.24 && normR <= 0.74) &&
                                (normG >= 0.18 && normG <= 0.46);

            var isHsvSkin = ((hue >= 0 && hue <= 68) || (hue >= 305 && hue <= 360)) &&
                            (sat >= 0.04 && sat <= 0.90) &&
                            (val >= 0.06);

            var isSkin = (isYcbcrSkin && (isNormRgbSkin || isHsvSkin)) ||
                         (isNormRgbSkin && isHsvSkin) ||
                         (isYcbcrSkin && (r > b - 15)) ||
                         (r > 55 && g > 30 && b > 15 && r >= g && (r - b) >= 6) ||
                         (Math.abs(r - g) < 22 && r > b && (r - b) >= 8);

            if (isSkin) {
                cellSkinCounts[cellIdx]++;
            }
        }
    }

    var activeGrid = new Array(gridCols * gridRows).fill(false);
    var activeCount = 0;
    for (var i = 0; i < gridCols * gridRows; i++) {
        var density = cellTotalCounts[i] > 0 ? (cellSkinCounts[i] / cellTotalCounts[i]) : 0;
        if (density >= 0.20) {
            activeGrid[i] = true;
            activeCount++;
        }
    }

    // Adaptive fallback if lighting makes skin counts softer
    if (activeCount < 4) {
        for (var i = 0; i < gridCols * gridRows; i++) {
            var density = cellTotalCounts[i] > 0 ? (cellSkinCounts[i] / cellTotalCounts[i]) : 0;
            if (density >= 0.12) {
                activeGrid[i] = true;
            }
        }
    }

    var visited = new Array(gridCols * gridRows).fill(false);
    var clusters = [];

    for (var r = 0; r < gridRows; r++) {
        for (var c = 0; c < gridCols; c++) {
            var cIdx = r * gridCols + c;
            if (activeGrid[cIdx] && !visited[cIdx]) {
                var queue = [[r, c]];
                visited[cIdx] = true;
                var clusterCells = [];

                while (queue.length > 0) {
                    var curr = queue.shift();
                    var currR = curr[0];
                    var currC = curr[1];
                    clusterCells.push([currR, currC]);

                    var neighbors = [
                        [currR - 1, currC], [currR + 1, currC],
                        [currR, currC - 1], [currR, currC + 1]
                    ];
                    for (var n = 0; n < neighbors.length; n++) {
                        var nr = neighbors[n][0];
                        var nc = neighbors[n][1];
                        if (nr >= 0 && nr < gridRows && nc >= 0 && nc < gridCols) {
                            var nIdx = nr * gridCols + nc;
                            if (activeGrid[nIdx] && !visited[nIdx]) {
                                visited[nIdx] = true;
                                queue.push([nr, nc]);
                            }
                        }
                    }
                }

                if (clusterCells.length >= 2) {
                    var minR = gridRows, maxR = 0, minC = gridCols, maxC = 0;
                    for (var k = 0; k < clusterCells.length; k++) {
                        var cr = clusterCells[k][0];
                        var cc = clusterCells[k][1];
                        if (cr < minR) minR = cr;
                        if (cr > maxR) maxR = cr;
                        if (cc < minC) minC = cc;
                        if (cc > maxC) maxC = cc;
                    }

                    // Trim excessive bottom rows (neck / clothing) if cluster spans almost all vertical rows
                    if (minR <= 2 && maxR >= 8 && (maxR - minR >= 7)) {
                        maxR = Math.min(maxR, 7);
                    }

                    var centerCol = (minC + maxC) / 2;
                    var centerRw = (minR + maxR) / 2;
                    var distFromCenter = Math.hypot(centerCol - 4.5, centerRw - 4.5);
                    clusters.push({
                        cells: clusterCells.length,
                        minC: minC, maxC: maxC,
                        minR: minR, maxR: maxR,
                        centerC: centerCol,
                        centerR: centerRw,
                        distFromCenter: distFromCenter,
                        wPx: (maxC - minC + 1) * cellW,
                        hPx: (maxR - minR + 1) * cellH
                    });
                }
            }
        }
    }

    if (clusters.length > 1) {
        clusters.sort(function(a, b) { return b.cells - a.cells; });
        var primary = clusters[0];
        var secondary = clusters[1];
        var secondaryAspect = secondary.hPx / Math.max(1, secondary.wPx);
        if (secondary.cells >= 12 && secondary.cells >= primary.cells * 0.70 &&
            Math.abs(primary.centerC - secondary.centerC) >= 3.8 &&
            secondaryAspect >= 0.75 && secondaryAspect <= 2.2) {
            return {
                status: 'MULTIPLE_FACES',
                passed: false,
                facesCount: clusters.length,
                score: 0,
                message: 'Multiple faces detected. Please ensure only one person is visible. Multiple faces detected. Please ensure only the intended person is visible.'
            };
        }
        clusters.sort(function(a, b) {
            return (a.distFromCenter * 0.7 - a.cells * 0.3) - (b.distFromCenter * 0.7 - b.cells * 0.3);
        });
    }

    var faceX = 0, faceY = 0, faceW = 0, faceH = 0;
    var isNativeDetected = false;
    if (nativeFace && nativeFace.width >= 20 && nativeFace.height >= 24) {
        faceX = Math.max(0, Math.floor(nativeFace.x));
        faceY = Math.max(0, Math.floor(nativeFace.y));
        faceW = Math.min(vw - faceX, Math.floor(nativeFace.width));
        faceH = Math.min(vh - faceY, Math.floor(nativeFace.height));
        isNativeDetected = true;
    } else if (clusters.length > 0) {
        var primaryCluster = clusters[0];
        faceX = Math.max(0, Math.floor(primaryCluster.minC * cellW));
        faceY = Math.max(0, Math.floor(primaryCluster.minR * cellH));
        faceW = Math.min(vw - faceX, Math.floor(primaryCluster.wPx));
        faceH = Math.min(vh - faceY, Math.floor(primaryCluster.hPx));
    } else {
        return {
            status: 'NO_FACE',
            passed: false,
            facesCount: 0,
            score: 0,
            message: 'No face detected. Please position your face in front of the camera.'
        };
    }

    // 4. Proximity
    var wRatio = faceW / vw;
    if (wRatio < 0.14 || faceW < 22 || faceH < 26) {
        return {
            status: 'TOO_FAR',
            passed: false,
            facesCount: 1,
            score: 30,
            message: 'Move closer to the camera.'
        };
    }
    if (wRatio > 0.96 || (faceW > 154 && faceH > 154)) {
        return {
            status: 'TOO_CLOSE',
            passed: false,
            facesCount: 1,
            score: 32,
            message: 'Move farther away from the camera.'
        };
    }

    // 5. Centering
    var centerX = faceX + faceW / 2;
    var centerY = faceY + faceH / 2;
    var offX = Math.abs(centerX - (vw / 2)) / vw;
    var offY = Math.abs(centerY - (vh / 2)) / vh;

    if (offX > 0.34 || offY > 0.36) {
        return {
            status: 'OFF_CENTER',
            passed: false,
            facesCount: 1,
            score: 40,
            message: 'Center your face inside the target frame.'
        };
    }

    // Partial face check: only flag if significantly cut off at edges
    var isClippedLeft = faceX <= 0 && (faceX + faceW) < 80;
    var isClippedRight = (faceX + faceW) >= 159 && faceX > 80;
    var isClippedBottom = (faceY + faceH) >= 159 && faceY > 90;
    if (isClippedLeft || isClippedRight || isClippedBottom) {
        return {
            status: 'PARTIAL_FACE',
            passed: false,
            facesCount: 1,
            score: 42,
            message: 'Center your face. Face is partially outside the frame.'
        };
    }

    // 6. Aspect ratio
    var aspect = faceH / Math.max(1, faceW);
    if (aspect < 0.60 || aspect > 2.8) {
        return {
            status: 'NO_FACE',
            passed: false,
            facesCount: 0,
            score: 20,
            message: 'No face detected. Please position your face in front of the camera.'
        };
    }

    // 7. Topological Feature Analysis & Landmarks
    var leftEyeLumaSum = 0, leftEyeCount = 0;
    var rightEyeLumaSum = 0, rightEyeCount = 0;
    var noseBridgeLumaSum = 0, noseBridgeCount = 0;
    var cheekLumaSum = 0, cheekCount = 0;
    var mouthLumaSum = 0, mouthCount = 0;

    for (var dy = 0; dy < faceH; dy += 2) {
        var curY = faceY + dy;
        var normY = dy / faceH;

        for (var dx = 0; dx < faceW; dx += 2) {
            var curX = faceX + dx;
            var normX = dx / faceW;

            var pIdx = (curY * vw + curX) * 4;
            var pLuma = 0.299 * pixels[pIdx] + 0.587 * pixels[pIdx + 1] + 0.114 * pixels[pIdx + 2];

            if (normY >= 0.26 && normY <= 0.50) {
                if (normX >= 0.14 && normX <= 0.44) {
                    leftEyeLumaSum += pLuma;
                    leftEyeCount++;
                } else if (normX >= 0.56 && normX <= 0.86) {
                    rightEyeLumaSum += pLuma;
                    rightEyeCount++;
                } else if (normX > 0.44 && normX < 0.56) {
                    noseBridgeLumaSum += pLuma;
                    noseBridgeCount++;
                }
            }

            if (normY >= 0.50 && normY <= 0.70) {
                if ((normX >= 0.14 && normX <= 0.42) || (normX >= 0.58 && normX <= 0.86)) {
                    cheekLumaSum += pLuma;
                    cheekCount++;
                }
            }

            if (normY >= 0.70 && normY <= 0.88) {
                if (normX >= 0.26 && normX <= 0.74) {
                    mouthLumaSum += pLuma;
                    mouthCount++;
                }
            }
        }
    }

    var avgLeftEye    = leftEyeCount > 0 ? (leftEyeLumaSum / leftEyeCount) : 128;
    var avgRightEye   = rightEyeCount > 0 ? (rightEyeLumaSum / rightEyeCount) : 128;
    var avgNoseBridge = noseBridgeCount > 0 ? (noseBridgeLumaSum / noseBridgeCount) : 128;
    var avgCheek      = cheekCount > 0 ? (cheekLumaSum / cheekCount) : avgLuma;
    var avgMouth      = mouthCount > 0 ? (mouthLumaSum / mouthCount) : avgLuma;

    var score = isNativeDetected ? 65 : 54; // Base confidence for confirmed face

    // A. Aspect ratio fit (0 - 15 pts)
    if (aspect >= 0.85 && aspect <= 1.95) {
        score += 15;
    } else if (aspect >= 0.70 && aspect <= 2.30) {
        score += 10;
    } else {
        score += 5;
    }

    // B. Centering and scale optimality (0 - 15 pts)
    if (offX <= 0.20 && offY <= 0.22 && wRatio >= 0.20 && wRatio <= 0.85) {
        score += 15;
    } else if (offX <= 0.30 && offY <= 0.32) {
        score += 10;
    } else {
        score += 5;
    }

    // C. Eye socket bilateral depression & symmetry (0 - 12 pts)
    var eyeCheekRatioL = avgCheek > 0 ? (avgLeftEye / avgCheek) : 1;
    var eyeCheekRatioR = avgCheek > 0 ? (avgRightEye / avgCheek) : 1;
    var eyeSymmetryDiff = Math.abs(avgLeftEye - avgRightEye) / (avgLeftEye + avgRightEye + 1);

    if (eyeCheekRatioL <= 1.25 && eyeCheekRatioR <= 1.25) {
        score += 6;
    } else {
        score += 3;
    }

    if (eyeSymmetryDiff < 0.45) {
        score += 6;
    } else if (eyeSymmetryDiff < 0.65) {
        score += 4;
    } else {
        score += 2;
    }

    // D. Nose bridge highlight vs eye contrast (0 - 8 pts)
    var noseEyeDiff = avgNoseBridge - (avgLeftEye + avgRightEye) / 2;
    if (noseEyeDiff > -10) {
        score += 8;
    } else if (noseEyeDiff > -20) {
        score += 5;
    } else {
        score += 3;
    }

    // E. Mouth cavity depression / contrast (0 - 6 pts)
    var mouthCheekRatio = avgCheek > 0 ? (avgMouth / avgCheek) : 1;
    if (mouthCheekRatio < 1.25) {
        score += 6;
    } else {
        score += 3;
    }

    // F. Edge definition & sharpness bonus (0 - 6 pts)
    if (avgEdgeGradient >= 0.9) {
        score += 6;
    } else if (avgEdgeGradient >= 0.35) {
        score += 5;
    } else {
        score += 3;
    }

    score = Math.min(96, Math.max(0, Math.round(score)));

    var MATCHING_THRESHOLD = 70;
    if (score < 35) {
        return {
            status: 'NO_FACE',
            passed: false,
            facesCount: 0,
            score: score,
            message: 'No face detected. Please position your face in front of the camera.'
        };
    }

    if (score >= 35 && score < MATCHING_THRESHOLD) {
        return {
            status: 'ALIGNING',
            passed: false,
            facesCount: 1,
            score: score,
            message: 'Face detected. Please hold still and center your face in the frame.'
        };
    }

    var descriptor = 'face_desc_' + Math.round(score) + '_' + Math.round(avgLeftEye) + '_' + Math.round(avgRightEye) + '_' + Math.round(avgNoseBridge) + '_' + Math.round(avgMouth) + '_' + Date.now().toString(36);
    return {
        status: 'VALID_FACE',
        passed: true,
        facesCount: 1,
        score: score,
        descriptor: descriptor,
        message: 'Face verified ✓'
    };
}

function stopFaceRecognitionLoginCamera() {
    bioFaceLoginActive = false;
    resetBioLoginFlash();
    if (bioFaceLoginStream) {
        try {
            bioFaceLoginStream.getTracks().forEach(function(track) {
                try {
                    var capabilities = track.getCapabilities ? track.getCapabilities() : {};
                    if (capabilities.torch) {
                        track.applyConstraints({ advanced: [{ torch: false }] });
                    }
                } catch(e) {}
                track.stop();
            });
        } catch(e) {}
        bioFaceLoginStream = null;
    }
    var video = document.getElementById('bioLoginFaceVideo');
    if (video) {
        video.srcObject = null;
        video.style.display = 'none';
    }
    var faceWrap = document.getElementById('bioModalFaceScannerWrap');
    if (faceWrap) {
        faceWrap.style.display = 'none';
    }
    var holo = document.getElementById('bioLoginFaceHolo');
    if (holo) {
        holo.style.display = 'none';
    }
}

async function startFaceRecognitionLogin(identifier, opts) {
    identifier = (identifier || (idInput ? idInput.value.trim() : '')).trim();
    opts = opts || lastBiometricOptions || {};

    // Face authentication must be performed by a platform WebAuthn
    // authenticator (Face ID, Windows Hello, Android screen lock). A browser
    // camera image alone is not an identity credential.
    return performBiometricLogin(identifier, {
        id: 'face',
        name: 'Face ID / Windows Hello',
        icon: 'bi-person-bounding-box',
        uv: 'required'
    }, opts);

    stopFaceRecognitionLoginCamera();
    if (bioAbortController) {
        try { bioAbortController.abort(); } catch(e) {}
    }
    bioAbortController = new AbortController();
    isBioPending = true;
    bioFaceLoginActive = true;

    if (fpRowBtn) {
        fpRowBtn.disabled = false;
        fpRowBtn.style.opacity = '0.95';
        fpRowBtn.style.cursor = 'pointer';
        fpRowBtn.setAttribute('title', 'Tap here to cancel face scan');
    }
    if (fpLabel) fpLabel.textContent = 'Scanning Face...';
    if (fpHint) fpHint.textContent = identifier ? ('Verifying face for ' + identifier + '...') : 'Looking at camera (tap to cancel)';
    if (fpIcon) fpIcon.className = 'bi bi-person-bounding-box';
    if (fpArrow) fpArrow.className = 'bi bi-x-circle fp-row-arrow';

    var modal = document.getElementById('biometricModal');
    var titleEl = document.getElementById('bioModalTitle');
    var descEl = document.getElementById('bioModalDesc');
    var pillEl = document.getElementById('bioModalUserPill');
    var pillTextEl = document.getElementById('bioModalUserText');
    var modalIcon = document.getElementById('bioModalIcon');
    var badgeEl = document.getElementById('bioModalBadge');
    var methodsWrap = document.getElementById('bioModalMethodsWrap');
    var passWrap = document.getElementById('bioModalPasswordWrap');
    var alertEl = document.getElementById('bioModalAlert');
    var faceWrap = document.getElementById('bioModalFaceScannerWrap');
    var primaryBtn = document.getElementById('bioModalPrimaryBtn');
    var chooseBtn = document.getElementById('bioModalChooseMethodBtn');
    var secondaryBtn = document.getElementById('bioModalSecondaryBtn');

    if (titleEl) titleEl.textContent = 'FACE RECOGNITION';
    if (descEl) descEl.innerHTML = 'Look directly at the camera to verify your face.';
    if (modalIcon) modalIcon.className = 'bi bi-person-bounding-box';
    if (badgeEl) {
        badgeEl.style.background = '#06b6d4';
        badgeEl.innerHTML = '<i class="bi bi-camera-video-fill"></i>';
    }
    if (identifier) {
        if (pillEl) pillEl.style.display = 'inline-flex';
        if (pillTextEl) pillTextEl.textContent = identifier;
    } else {
        if (pillEl) pillEl.style.display = 'none';
    }

    if (methodsWrap) methodsWrap.style.display = 'none';
    if (passWrap) passWrap.style.display = 'none';
    if (alertEl) alertEl.style.display = 'none';
    if (faceWrap) faceWrap.style.display = 'block';

    if (primaryBtn) primaryBtn.style.display = 'none';
    if (chooseBtn) {
        chooseBtn.style.display = 'block';
        chooseBtn.innerHTML = '<i class="bi bi-grid-fill me-1"></i>Choose Another Method';
        currentChooseMethodCallback = function() {
            stopFaceRecognitionLoginCamera();
            if (lastAvailableMethods && lastAvailableMethods.length > 0) {
                openBiometricSelectionPrompt(lastAvailableMethods, identifier, opts);
            } else {
                handleBiometricLogin();
            }
        };
    }
    if (secondaryBtn) {
        secondaryBtn.style.display = 'block';
        secondaryBtn.textContent = 'SIGN IN WITH PASSWORD';
        currentSecondaryModalCallback = function() {
            stopFaceRecognitionLoginCamera();
            closeBiometricModalAndFocusPassword();
        };
    }

    if (modal && !modal.classList.contains('active')) {
        modal.style.display = 'flex';
        void modal.offsetHeight;
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    var video = document.getElementById('bioLoginFaceVideo');
    var holo = document.getElementById('bioLoginFaceHolo');
    var laserBar = document.getElementById('bioLoginLaserBar');
    var progressFill = document.getElementById('bioLoginFaceProgressFill');
    var pctLabel = document.getElementById('bioLoginFacePctLabel');
    var stateLabel = document.getElementById('bioLoginFaceStateLabel');
    var hudStatus = document.getElementById('bioLoginHudStatus');

    var currentProgress = 15;
    if (progressFill) progressFill.style.width = '15%';
    if (pctLabel) pctLabel.textContent = '15%';
    if (stateLabel) stateLabel.textContent = 'Accessing camera...';
    if (hudStatus) {
        hudStatus.textContent = 'INITIALIZING';
        hudStatus.style.color = '#06b6d4';
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        resetBiometricButton();
        if (video) video.style.display = 'none';
        if (holo) holo.style.display = 'flex';
        if (descEl) descEl.innerHTML = '<span style="color:#fca5a5;">Camera access is not supported on this browser or requires HTTPS. Please sign in with your password.</span>';
        if (stateLabel) stateLabel.textContent = 'Camera unavailable';
        return;
    }

    try {
        bioFaceLoginStream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'user',
                width: { ideal: 640 },
                height: { ideal: 640 }
            }
        });
        if (video && bioFaceLoginStream) {
            video.srcObject = bioFaceLoginStream;
            video.style.display = 'block';
            if (holo) holo.style.display = 'none';
            try { await video.play(); } catch(e) {}
        }
    } catch(camErr) {
        console.warn('Camera error for face login:', camErr);
        resetBiometricButton();
        if (video) video.style.display = 'none';
        if (holo) holo.style.display = 'flex';
        if (descEl) descEl.innerHTML = '<span style="color:#fca5a5;"><i class="bi bi-camera-video-off me-1"></i>Camera access was denied. Please allow camera permission in browser settings, or use another method.</span>';
        if (stateLabel) stateLabel.textContent = 'Camera permission denied';
        if (hudStatus) {
            hudStatus.textContent = 'CAMERA BLOCKED';
            hudStatus.style.color = '#ef4444';
        }
        return;
    }

    resetBioLoginFlash();
    var consecutiveValidFrames = 0;
    var REQUIRED_FRAMES = 4;
    var SCAN_TIMEOUT_MS = 40000;
    var startTime = Date.now();
    var lastVerifiedAnalysis = null;
    var flashBtn = document.getElementById('bioLoginFlashToggleBtn');

    var sleep = function(ms) {
        return new Promise(function(resolve) {
            var timer = setTimeout(resolve, ms);
            if (bioAbortController && bioAbortController.signal) {
                bioAbortController.signal.addEventListener('abort', function() {
                    clearTimeout(timer);
                    resolve();
                }, { once: true });
            }
        });
    };

    while (bioFaceLoginActive && !bioAbortController?.signal?.aborted) {
        if (Date.now() - startTime > SCAN_TIMEOUT_MS) {
            bioFaceLoginActive = false;
            stopFaceRecognitionLoginCamera();
            resetBiometricButton();
            if (descEl) descEl.innerHTML = '<span style="color:#fca5a5;"><i class="bi bi-clock-history me-1"></i>Face recognition timed out. Please position your face clearly in good lighting and try again.</span>';
            if (stateLabel) stateLabel.textContent = 'Face detection timed out';
            return;
        }

        var analysis = await detectAndAnalyzeFaceFrame(video);
        if (!bioFaceLoginActive || bioAbortController?.signal?.aborted) {
            break;
        }

        if (analysis.status === 'NO_FACE') {
            consecutiveValidFrames = Math.max(0, consecutiveValidFrames - 1);
            currentProgress = Math.max(15, currentProgress - 2);
            if (progressFill) progressFill.style.width = currentProgress + '%';
            if (pctLabel) pctLabel.textContent = currentProgress + '%';
            if (stateLabel) stateLabel.textContent = 'No face detected. Please position your face in front of the camera.';
            if (hudStatus) { hudStatus.textContent = 'POSITION FACE'; hudStatus.style.color = '#ef4444'; }
            if (laserBar) laserBar.style.background = 'linear-gradient(90deg, transparent 0%, #ef4444 35%, #f87171 50%, #ef4444 65%, transparent 100%)';
        } else if (analysis.status === 'MULTIPLE_FACES') {
            consecutiveValidFrames = 0;
            currentProgress = 15;
            if (progressFill) progressFill.style.width = '15%';
            if (pctLabel) pctLabel.textContent = '15%';
            if (stateLabel) stateLabel.textContent = 'Multiple faces detected. Please ensure only one person is visible.';
            if (hudStatus) { hudStatus.textContent = 'MULTIPLE FACES'; hudStatus.style.color = '#ef4444'; }
            if (laserBar) laserBar.style.background = 'linear-gradient(90deg, transparent 0%, #ef4444 35%, #f87171 50%, #ef4444 65%, transparent 100%)';
        } else if (analysis.status === 'TOO_FAR') {
            consecutiveValidFrames = Math.max(0, consecutiveValidFrames - 1);
            if (stateLabel) stateLabel.textContent = 'Move closer to the camera...';
            if (hudStatus) { hudStatus.textContent = 'MOVE CLOSER'; hudStatus.style.color = '#f59e0b'; }
        } else if (analysis.status === 'TOO_CLOSE') {
            consecutiveValidFrames = Math.max(0, consecutiveValidFrames - 1);
            if (stateLabel) stateLabel.textContent = 'Move farther away from the camera...';
            if (hudStatus) { hudStatus.textContent = 'MOVE BACK'; hudStatus.style.color = '#f59e0b'; }
        } else if (analysis.status === 'OFF_CENTER' || analysis.status === 'PARTIAL_FACE') {
            consecutiveValidFrames = Math.max(0, consecutiveValidFrames - 1);
            if (stateLabel) stateLabel.textContent = analysis.message || 'Center your face inside the frame.';
            if (hudStatus) { hudStatus.textContent = 'CENTER FACE'; hudStatus.style.color = '#f59e0b'; }
        } else if (analysis.status === 'BLURRY') {
            consecutiveValidFrames = Math.max(0, consecutiveValidFrames - 1);
            if (stateLabel) stateLabel.textContent = 'Camera image blurry. Please hold steady.';
            if (hudStatus) { hudStatus.textContent = 'HOLD STEADY'; hudStatus.style.color = '#f59e0b'; }
        } else if (analysis.status === 'UNUSABLE') {
            consecutiveValidFrames = Math.max(0, consecutiveValidFrames - 1);
            if (stateLabel) stateLabel.textContent = 'Lighting is dark. Tap Flash to illuminate your face, or improve lighting.';
            if (hudStatus) { hudStatus.textContent = 'LIGHTING LOW'; hudStatus.style.color = '#f59e0b'; }
            if (flashBtn && !isBioLoginFlashActive) {
                flashBtn.classList.add('flash-suggest-pulse');
            }
        } else if (analysis.status === 'ALIGNING') {
            consecutiveValidFrames = Math.max(0, consecutiveValidFrames - 1);
            var alPct = Math.max(30, Math.min(65, Math.round(analysis.score || 45)));
            currentProgress = Math.max(currentProgress, alPct);
            if (progressFill) progressFill.style.width = currentProgress + '%';
            if (pctLabel) pctLabel.textContent = currentProgress + '%';
            if (stateLabel) stateLabel.textContent = 'Face detected (' + Math.round(analysis.score) + '%). Hold still...';
            if (hudStatus) { hudStatus.textContent = 'ALIGNING'; hudStatus.style.color = '#06b6d4'; }
            if (laserBar) laserBar.style.background = 'linear-gradient(90deg, transparent 0%, #06b6d4 35%, #38bdf8 50%, #06b6d4 65%, transparent 100%)';
        } else if (analysis.status === 'VALID_FACE' && analysis.passed) {
            consecutiveValidFrames++;
            lastVerifiedAnalysis = analysis;
            if (flashBtn) {
                flashBtn.classList.remove('flash-suggest-pulse');
            }
            var pct = Math.min(98, Math.round(55 + (consecutiveValidFrames / REQUIRED_FRAMES) * 43));
            currentProgress = Math.max(currentProgress, pct);
            if (progressFill) progressFill.style.width = currentProgress + '%';
            if (pctLabel) pctLabel.textContent = currentProgress + '%';
            if (stateLabel) stateLabel.textContent = 'Verifying facial geometry (' + Math.round(analysis.score) + '% match)...';
            if (hudStatus) { hudStatus.textContent = 'VERIFYING'; hudStatus.style.color = '#22c55e'; }
            if (laserBar) laserBar.style.background = 'linear-gradient(90deg, transparent 0%, #22c55e 35%, #4ade80 50%, #22c55e 65%, transparent 100%)';

            if (consecutiveValidFrames >= REQUIRED_FRAMES) {
                currentProgress = 100;
                if (progressFill) progressFill.style.width = '100%';
                if (pctLabel) pctLabel.textContent = '100%';
                if (stateLabel) stateLabel.textContent = 'Face verified ✓ Authenticating...';
                if (hudStatus) { hudStatus.textContent = 'MATCHED ✓'; hudStatus.style.color = '#22c55e'; }
                triggerCaptureFlash('bioLoginCameraBox');
                bioFaceLoginActive = false;
                break;
            }
        }

        await sleep(90);
    }

    if (bioAbortController?.signal?.aborted || !lastVerifiedAnalysis || !lastVerifiedAnalysis.passed) {
        stopFaceRecognitionLoginCamera();
        resetBiometricButton();
        return;
    }

    if (bioFaceLoginStream) {
        try { bioFaceLoginStream.getTracks().forEach(function(t) { t.stop(); }); } catch(e) {}
        bioFaceLoginStream = null;
    }
    if (video) { video.srcObject = null; }

    var savedAccounts = (typeof getSavedAccounts === 'function') ? getSavedAccounts() : [];
    var savedIds = savedAccounts.map(function(a) { return a.identifier; }).filter(Boolean);

    if (descEl) descEl.innerHTML = '<span style="color:#22c55e;"><i class="bi bi-shield-check me-1"></i>Face verified. Authenticating with server...</span>';
    if (stateLabel) stateLabel.textContent = 'Authenticating session...';

    try {
        var loginRes = await fetch('{{ route("webauthn.login") }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                biometric_method: 'face',
                credential_id: opts.face_credential_id || ('face_login_' + Date.now().toString(36)),
                face_descriptor: lastVerifiedAnalysis.descriptor,
                student_number: identifier,
                identifier: identifier,
                saved_identifiers: savedIds
            })
        });

        var loginData = await loginRes.json();
        console.log('Face login response:', loginData);

        if (loginData.success) {
            if (badgeEl) {
                badgeEl.style.background = '#22c55e';
                badgeEl.innerHTML = '<i class="bi bi-check-lg"></i>';
            }
            if (titleEl) titleEl.textContent = 'FACE RECOGNIZED ✓';
            if (descEl) descEl.innerHTML = '<span style="color:#22c55e; font-weight:600;">Welcome back! Redirecting to your dashboard...</span>';
            if (stateLabel) stateLabel.textContent = 'Sign-in complete. Redirecting...';
            showFpMessage('success', '<i class="bi bi-check-circle-fill me-2"></i>Face recognized successfully! Redirecting...');
            
            setTimeout(function() {
                window.location.href = loginData.redirect || '{{ route("home") }}';
            }, 600);
        } else {
            resetBiometricButton();
            if (badgeEl) {
                badgeEl.style.background = '#ef4444';
                badgeEl.innerHTML = '<i class="bi bi-x-lg"></i>';
            }
            if (titleEl) titleEl.textContent = 'VERIFICATION FAILED';
            var errMsg = loginData.message || 'Face recognition could not be verified.';
            if (descEl) descEl.innerHTML = '<span style="color:#fca5a5;">' + errMsg + '</span>';
            if (faceWrap) faceWrap.style.display = 'none';
            if (primaryBtn) {
                primaryBtn.style.display = 'block';
                primaryBtn.innerHTML = '<i class="bi bi-camera-video me-2"></i>TRY FACE AGAIN';
                currentPrimaryModalCallback = function() {
                    startFaceRecognitionLogin(identifier, opts);
                };
            }
            showFpMessage('error', '<i class="bi bi-x-circle me-2"></i>' + errMsg);
        }
    } catch(err) {
        console.error('Face login request error:', err);
        resetBiometricButton();
        if (descEl) descEl.innerHTML = '<span style="color:#fca5a5;">Could not connect to authentication server. Please check your network and try again.</span>';
        if (faceWrap) faceWrap.style.display = 'none';
        if (primaryBtn) {
            primaryBtn.style.display = 'block';
            primaryBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>RETRY';
            currentPrimaryModalCallback = function() {
                startFaceRecognitionLogin(identifier, opts);
            };
        }
    }
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
    if (selectedMethod.id === 'face') {
        closeBiometricModal();
        startFaceRecognitionLogin(identifier, opts);
    } else {
        closeBiometricModal();
        if (fpLabel) fpLabel.textContent = selectedMethod.name + '...';
        if (fpHint) fpHint.textContent = 'Tap here to cancel (or verify)';
        if (fpIcon) fpIcon.className = 'bi ' + selectedMethod.icon;
        if (fpArrow) fpArrow.className = 'bi bi-x-circle fp-row-arrow';

        performBiometricLogin(identifier, selectedMethod, opts);
    }
}

// Handle biometric login button click
async function handleBiometricLogin() {
    hideFpMessage();

    if (fpRowBtn && typeof fpRowBtn.blur === 'function') {
        fpRowBtn.blur();
    }

    // If an active biometric scan is already in progress and modal is visible, tapping acts as a clean cancel!
    var bioModalEl = document.getElementById('biometricModal');
    var isModalActive = bioModalEl && (bioModalEl.classList.contains('active') || bioModalEl.style.display === 'flex');
    if (isBioPending && isModalActive) {
        if (bioAbortController) {
            try { bioAbortController.abort(); } catch(e) {}
            bioAbortController = null;
        }
        resetBiometricButton();
        closeBiometricModal();
        return;
    }
    isBioPending = false;

    // Guard: ensure environment supports WebAuthn / secure context
    if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        resetBiometricButton();
        showFpMessage('warning', '<i class="bi bi-shield-exclamation me-2"></i>Biometric authentication requires HTTPS or localhost. Please sign in with your password.');
        focusPasswordField();
        return;
    }

    var hasWebAuthn = window.PublicKeyCredential && navigator.credentials && typeof navigator.credentials.get === 'function';
    var hasCamera = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);

    if (!hasWebAuthn && !hasCamera) {
        resetBiometricButton();
        showFpMessage('warning', '<i class="bi bi-shield-exclamation me-2"></i>Biometric authentication is not supported on this browser. Please sign in with your password.');
        focusPasswordField();
        return;
    }

    var identifier = idInput ? idInput.value.trim() : '';

    // Modal helper for when identifier is required during targeted setup
    window.showStudentIdRequiredModal = function() {
        openBiometricModal({
            title: 'STUDENT ID OR EMAIL REQUIRED',
            message: 'Please enter your <strong>Student ID, Email, or Mobile Number</strong> first so the system can verify your registered biometric credentials.<br><br><span style="font-size:0.85rem;color:rgba(212,175,55,0.9);">If you have registered a passkey on this device, you can also proceed directly.</span>',
            badgeType: 'warning',
            primaryBtnText: '<i class="bi bi-person-fill me-2"></i>ENTER STUDENT ID / EMAIL',
            secondaryBtnText: '<i class="bi bi-passkey me-2"></i>USE DEVICE PASSKEY',
            onPrimaryClick: closeBiometricModalAndFocusIdentifier,
            onSecondaryClick: function() {
                closeBiometricModal();
                performBiometricLogin('');
            }
        });
        showFpMessage('warning', '<i class="bi bi-person-fill me-2"></i>Please enter your Student ID, Email, or Mobile Number first.');
        if (idInput) {
            idInput.focus();
            idInput.style.borderColor = '#d4af37';
            setTimeout(function() { if (idInput) idInput.style.borderColor = ''; }, 2500);
        }
    };

    // Query available methods and display selection prompt before triggering hardware authentication
    if (fpLabel) fpLabel.textContent = 'Checking biometrics...';
    if (fpHint) fpHint.textContent = identifier ? ('Checking methods for ' + identifier + '...') : 'Detecting device capabilities...';
    if (fpIcon) fpIcon.className = 'bi bi-hourglass-split';
    if (fpArrow) fpArrow.className = 'bi bi-hourglass-split fp-row-arrow';

    var savedAccounts = (typeof getSavedAccounts === 'function') ? getSavedAccounts() : [];
    var savedIds = savedAccounts.map(function(a) { return a.identifier; }).filter(Boolean);

    try {
        var optRes = await fetch('{{ route("webauthn.login.options") }}', {
            method: 'POST',
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
        console.log('WebAuthn options response:', opts);

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
            setTimeout(function() {
                var pInput = document.getElementById('bioModalPasswordInput');
                if (pInput) pInput.focus();
            }, 100);
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

        resetBiometricButton();

        // Detect device biometric capabilities dynamically and filter available methods
        var deviceCaps = await getDeviceBiometricCapabilities();
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

        // If only Face Recognition is available for this user/device, launch face login directly
        if (availableMethods.length === 1 && availableMethods[0].id === 'face') {
            startFaceRecognitionLogin(identifier || opts.identifier, opts);
            return;
        }

        // Present the user with the biometric methods supported and enrolled on their device
        openBiometricSelectionPrompt(availableMethods, identifier || opts.identifier, opts);

    } catch(err) {
        console.error('Error fetching biometric options:', err);
        resetBiometricButton();
        showFpMessage('error', '<i class="bi bi-exclamation-triangle-fill me-2"></i>Could not check biometric availability. Please sign in with your password.');
        focusPasswordField();
    }
}

async function performBiometricLogin(studentNumber, selectedMethod) {
    studentNumber = (studentNumber || '').trim();
    selectedMethod = selectedMethod || { id: 'fingerprint', name: 'Biometric', icon: 'bi-fingerprint', uv: 'required' };
    var onDeviceMethod = selectedMethod.id === 'fingerprint' || selectedMethod.id === 'face';

    // Abort previous prompt if any
    if (bioAbortController) {
        try { bioAbortController.abort(); } catch(e) {}
    }
    bioAbortController = new AbortController();
    isBioPending = true;

    if (fpRowBtn) {
        fpRowBtn.disabled = false; // keep clickable so user can tap anytime to cancel
        fpRowBtn.style.opacity = '0.9';
        fpRowBtn.style.cursor = 'pointer';
        fpRowBtn.setAttribute('title', 'Tap here to cancel biometric scan');
    }
    if (fpLabel) fpLabel.textContent = selectedMethod.name + '...';
    if (fpHint) fpHint.textContent = studentNumber ? ('Verifying for ' + studentNumber + '...') : 'Tap to cancel (or touch sensor / verify)';
    if (fpIcon) fpIcon.className = 'bi ' + selectedMethod.icon;
    if (fpArrow) fpArrow.className = 'bi bi-x-circle fp-row-arrow';

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
                signal: bioAbortController.signal
        });
        var opts = await optRes.json();

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

        if (selectedMethod.id === 'face') {
            if (fpLabel) fpLabel.textContent = 'Confirm with Face ID or Windows Hello...';
        } else if (selectedMethod.id === 'device_lock') {
            if (fpLabel) fpLabel.textContent = 'Enter device PIN or screen lock...';
        } else {
            if (fpLabel) fpLabel.textContent = 'Touch sensor or scan fingerprint...';
        }
        if (fpHint) fpHint.textContent = 'Tap here to cancel (or verify)';

        var assertion = null;
        try {
            assertion = await navigator.credentials.get({
                publicKey: getPublicKey,
                signal: bioAbortController ? bioAbortController.signal : undefined
            });
        } catch (firstErr) {
            if (firstErr.name === 'AbortError' || firstErr.name === 'NotAllowedError') {
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
                        signal: bioAbortController ? bioAbortController.signal : undefined
                    });
                } catch (fallbackErr) {
                    if (fallbackErr.name === 'AbortError') throw fallbackErr;
                    throw firstErr;
                }
            } else {
                throw firstErr;
            }
        }

        if (!assertion) {
            throw new Error('Authentication was cancelled or failed.');
        }

        if (fpLabel) fpLabel.textContent = 'Verifying biometric...';
        if (fpHint) fpHint.textContent = 'Please wait a moment...';
        if (fpIcon) fpIcon.className = 'bi bi-shield-check';
        if (fpArrow) fpArrow.className = 'bi bi-hourglass-split fp-row-arrow';

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

        if (result.success) {
            if (fpLabel) fpLabel.textContent = '✓ Authenticated! Redirecting...';
            if (fpHint) fpHint.textContent = 'Welcome back!';
            if (fpIcon) fpIcon.className = 'bi bi-check-circle-fill text-success';
            if (fpArrow) fpArrow.className = 'bi bi-check2 fp-row-arrow text-success';
            if (fpRowBtn) fpRowBtn.style.opacity = '1';

            if (result.user && result.user.identifier) {
                try {
                    saveAccount({
                        identifier: result.user.identifier,
                        name: result.user.name,
                        role: result.user.role
                    });
                    if (idInput) {
                        idInput.value = result.user.identifier;
                        if (typeof updateAccountBanner === 'function') updateAccountBanner();
                    }
                } catch(e) {}
            }
            
            window.location.replace(result.redirect || '{{ route("home") }}');
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
            var isTimeout = err.message && /timed?\s*out/i.test(err.message);
            showFpMessage('warning', '<i class="bi bi-x-circle me-2"></i>Biometric authentication was cancelled or timed out. Please try again or use your password.');
            openBiometricModal({
                title: 'AUTHENTICATION CANCELLED',
                message: 'Biometric authentication was cancelled or timed out.<br><br>Please try again, choose another method, or sign in with your password.',
                badgeType: 'warning',
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
            if (typeof fpRowBtn.blur === 'function') fpRowBtn.blur();
            handleBiometricLogin();
        });
    }

    var primaryBtn = document.getElementById('bioModalPrimaryBtn');
    if (primaryBtn) {
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
    if (secondaryBtn) {
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
            closeBiometricModal();
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

    var bioFlashBtn = document.getElementById('bioLoginFlashToggleBtn');
    if (bioFlashBtn && bioFlashBtn.dataset.flashBound !== 'true') {
        bioFlashBtn.dataset.flashBound = 'true';
        bioFlashBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleBioLoginFlash();
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
        if (typeof btn.blur === 'function') btn.blur();
        handleBiometricLogin();
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupBiometricListeners);
} else {
    setupBiometricListeners();
}
window.addEventListener('pageshow', setupBiometricListeners);
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
        fetch('/pwa/version?_t=' + Date.now(), { 
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data) {
                    const ver = data.installed_version || data.current_version || data.version || data.latest_version;
                    if (ver) {
                        const tag = 'v' + String(ver).replace(/^v/i, '');
                        document.querySelectorAll('[data-app-version-tag], #loginAppVersionDesktop, #loginAppVersionMobile').forEach(function(el) {
                            el.textContent = tag;
                        });
                        try {
                            const clean = String(ver).replace(/^v/i, '');
                            localStorage.setItem('app_installed_version', clean);
                            localStorage.setItem('pwa_installed_version', clean);
                            localStorage.setItem('pwa_app_version', clean);
                        } catch(e) {}
                    }
                }
            })
            .catch(function() {});
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
