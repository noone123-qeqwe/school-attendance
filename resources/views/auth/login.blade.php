<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
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

        html, body {
            background-color: #110A0A;
            color: #F3E7CD;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            overflow-y: auto;
        }

        /* â”€â”€ FULL-SCREEN BACKGROUND â”€â”€ */
        .bg-scene {
            position: fixed; inset: 0;
            background: url('/images/background.jpg') center center / cover no-repeat;
            background-color: #1a0a0a;
            z-index: 0;
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

        /* background video removed from login — intro handled separately */

        /* â”€â”€ TOP BAR â”€â”€ */
        .top-bar {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 28px;
        }
        .top-bar-brand {
            font-size: 0.78rem; font-weight: 800;
            color: white; letter-spacing: 1.5px;
            text-transform: uppercase; opacity: 0.9;
            display: flex; align-items: center; gap: 10px;
        }
        .top-bar-brand i { font-size: 1.1rem; }

        /* â”€â”€ BOTTOM BAR â”€â”€ */
        .bottom-bar {
            position: fixed; bottom: 0; left: 0; right: 0;
            z-index: 5;
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 28px;
            font-size: 0.72rem; color: rgba(255,255,255,0.45);
            pointer-events: none;
        }
        .bottom-bar a {
            color: rgba(255,255,255,0.45); text-decoration: none;
            transition: color 0.2s; pointer-events: all;
        }
        .bottom-bar a:hover { color: rgba(255,255,255,0.8); }
        .bottom-bar span { pointer-events: none; }
        .bottom-links { display: flex; gap: 20px; }

        /* â”€â”€ CENTERED LAYOUT â”€â”€ */
        .auth-scene {
            position: relative; z-index: 10;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 56px 20px 48px;
        }

        /* ── GLASS CARD ── */
        .glass-card {
            width: 100%; 
            max-width: 500px;
            min-width: 320px; /* Minimum width for mobile */
            background: rgba(30, 21, 21, 0.75);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border-radius: 24px;
            border: 1px solid rgba(212, 175, 55, 0.25);
            box-shadow: 0 16px 40px rgba(0,0,0,0.5), inset 0 1px 0 rgba(212, 175, 55, 0.1);
            padding: 28px 28px 24px;
            color: white;
            position: relative;
            z-index: 20;
            margin: 0 auto; /* Center the card */
            transition: all 0.3s ease; /* Smooth transitions */
        }

        /* Logo  -  smaller */
        .glass-logo {
            width: 58px; height: 58px;
            border-radius: 50%;
            background: white;
            border: 3px solid rgba(255,220,100,0.6);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            margin: 0 auto 10px;
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
            padding: 3px 12px; border-radius: 99px;
            margin-bottom: 6px;
        }
        .glass-title {
            font-size: 1.5rem; font-weight: 800;
            color: white; letter-spacing: -0.5px;
            margin-bottom: 2px;
        }
        .glass-sub {
            font-size: 0.8rem; color: rgba(255,255,255,0.65);
            margin-bottom: 16px;
        }

        /* Role toggle */
        .role-toggle {
            display: flex;
            background: rgba(0,0,0,0.25);
            border-radius: 12px;
            padding: 4px; gap: 4px;
            margin-bottom: 14px;
            border: 1px solid rgba(255,255,255,0.12);
        }
        .role-btn {
            flex: 1; padding: 8px 10px;
            border: none; border-radius: 9px;
            font-size: 0.82rem; font-weight: 600;
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
        #loginForm .glass-input-wrap { position: relative; margin-bottom: 8px; }
        #loginForm .glass-input-wrap .g-icon {
            position: absolute; left: 18px !important; top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.5); font-size: 1.1rem !important;
            pointer-events: none; transition: color 0.2s;
        }
        #loginForm .glass-input {
            width: 100%;
            padding: 11px 13px 11px 50px !important;
            border-radius: 11px;
            border: 1.5px solid rgba(212, 175, 55, 0.25);
            background: rgba(0,0,0,0.3);
            color: white;
            font-size: 0.875rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: all 0.2s;
        }
        #loginForm .glass-input::placeholder { color: rgba(255,255,255,0.35); }
        #loginForm .glass-input:hover { border-color: rgba(212, 175, 55, 0.4); background: rgba(0,0,0,0.4); }
        #loginForm .glass-input:focus {
            border-color: #d4af37;
            background: rgba(0,0,0,0.5);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
        }
        #loginForm .glass-input-wrap:focus-within .g-icon { color: rgba(255,255,255,0.85); }
        #loginForm .glass-input.has-eye { padding-right: 42px; }
        .eye-toggle {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.5); font-size: 0.95rem;
            cursor: pointer; background: none; border: none; padding: 4px;
            transition: color 0.2s; line-height: 1;
            z-index: 5;
        }
        .eye-toggle:hover { color: white; }

        /* Fingerprint/Biometric row */
        .fp-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 13px;
            border-radius: 11px;
            border: 1.5px solid rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.08);
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 8px;
        }
        .fp-row:hover { background: rgba(255,255,255,0.14); border-color: rgba(255,255,255,0.35); }
        .fp-row:active { transform: scale(0.98); }
        .fp-row-left { display: flex; align-items: center; gap: 10px; }
        .fp-row-left i { font-size: 1.2rem; color: rgba(255,255,255,0.8); transition: all 0.3s; }
        .fp-row-label { font-size: 0.85rem; font-weight: 600; color: white; transition: all 0.2s; }
        .fp-row-hint { font-size: 0.7rem; color: rgba(255,255,255,0.45); margin-top: 1px; transition: all 0.2s; }
        .fp-row-arrow { color: rgba(255,255,255,0.35); font-size: 0.82rem; transition: all 0.2s; }
        
        /* Biometric button states */
        .fp-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 13px;
            border-radius: 11px;
            border: 1.5px solid rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.08);
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 8px;
        }
        .fp-row:hover { background: rgba(255,255,255,0.14); border-color: rgba(255,255,255,0.35); }
        .fp-row:active { transform: scale(0.98); }
        .fp-row-left { display: flex; align-items: center; gap: 10px; }
        .fp-row-left i { font-size: 1.2rem; color: rgba(255,255,255,0.8); transition: all 0.3s; }
        .fp-row-label { font-size: 0.85rem; font-weight: 600; color: white; transition: all 0.2s; }
        .fp-row-hint { font-size: 0.7rem; color: rgba(255,255,255,0.45); margin-top: 1px; transition: all 0.2s; }
        .fp-row-arrow { color: rgba(255,255,255,0.35); font-size: 0.82rem; transition: all 0.2s; }
        
        .fp-row[data-state="checking"] {
            opacity: 0.6;
            pointer-events: none;
        }
        .fp-row[data-state="ready"] {
            cursor: pointer;
        }
        .fp-row[data-state="ready"]:hover {
            background: rgba(212, 175, 55, 0.15);
            border-color: rgba(212, 175, 55, 0.4);
        }
        .fp-row[data-state="not-registered"] {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.12);
            cursor: help;
        }
        .fp-row[data-state="not-registered"]:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.2);
        }
        .fp-row[data-state="not-registered"] .fp-row-left i {
            color: rgba(255,215,0,0.6);
        }

        /* Divider */
        .glass-divider {
            display: flex; align-items: center; gap: 10px;
            margin: 10px 0; color: rgba(255,255,255,0.35); font-size: 0.72rem;
        }
        .glass-divider::before, .glass-divider::after {
            content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.18);
        }

        /* Submit button */
        .glass-btn {
            width: 100%; padding: 12px;
            background: rgba(255,255,255,0.95);
            color: #800000;
            font-weight: 800; font-size: 0.875rem;
            letter-spacing: 0.5px;
            border: none; border-radius: 11px;
            cursor: pointer; transition: all 0.25s ease;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            margin-top: 4px;
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
            font-size: 0.78rem; color: rgba(255,255,255,0.65);
            text-decoration: none; transition: all 0.2s ease;
            padding: 10px 0;
        }
        .glass-note-link:hover {
            color: rgba(255,255,255,0.95);
            text-decoration: underline;
        }

        /* Links */
        .glass-link-row { text-align: center; font-size: 0.78rem; color: rgba(255,255,255,0.5); margin-top: 12px; }
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

        /* Mobile and Responsive */
        @media (max-width: 768px) {
            .glass-card { 
                max-width: 90vw; /* Use viewport width on tablets */
                padding: 24px 24px 20px; 
                border-radius: 20px; 
            }
            .top-bar { padding: 12px 20px; }
            .bottom-bar { padding: 10px 20px; }
        }

        @media (max-width: 480px) {
            .glass-card { 
                max-width: 95vw; /* Use more viewport width on phones */
                min-width: 280px; /* Smaller minimum for very small screens */
                padding: 22px 20px 20px; 
                border-radius: 18px; 
                margin: 0 10px; /* Small side margins */
            }
            .glass-title { font-size: 1.3rem; }
            .bottom-bar { display: none; }
            .auth-scene { padding: 52px 16px 16px; }
            .top-bar { padding: 10px 16px; }
            .top-bar-brand { font-size: 0.7rem; }
        }

        @media (max-width: 360px) {
            .glass-card { 
                max-width: 98vw; /* Almost full width on very small screens */
                min-width: 260px;
                padding: 20px 18px 18px; 
                border-radius: 16px;
                margin: 0 5px;
            }
            .glass-title { font-size: 1.2rem; }
            .glass-sub { font-size: 0.75rem; }
            .glass-input { font-size: 0.8rem; padding: 10px 12px 10px 36px; }
            .glass-btn { font-size: 0.8rem; padding: 11px; }
        }

        /* Landscape orientation adjustments */
        @media (max-height: 600px) and (orientation: landscape) {
            .auth-scene { padding: 20px 16px; }
            .glass-card { padding: 20px 24px 18px; }
            .glass-logo { width: 48px; height: 48px; margin-bottom: 8px; }
            .glass-title { font-size: 1.3rem; }
            .top-bar, .bottom-bar { display: none; }
        }

        /* Desktop Install Button Responsiveness */
        @media (max-width: 768px) {
            #webInstallAppBtn {
                bottom: 15px !important;
                right: 15px !important;
            }
            #pwaInstallBtn {
                padding: 12px 20px !important;
                font-size: 0.85rem !important;
            }
        }

        @media (max-width: 480px) {
            #webInstallAppBtn {
                bottom: 10px !important;
                right: 10px !important;
            }
            #pwaInstallBtn {
                padding: 10px 16px !important;
                font-size: 0.8rem !important;
                gap: 8px !important;
            }
            #pwaInstallBtn i {
                font-size: 0.95rem !important;
            }
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
    <span>© {{ date('Y') }} Smart Classroom Attendance System. All rights reserved.</span>
    <div class="bottom-links">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Service</a>
        <a href="#">Contact Us</a>
        <span style="color: rgba(207,164,111,0.6); font-weight: 600; margin-left: 12px; pointer-events: all;">
            v{{ config('changelog.default_version', '2.2.0') }}
        </span>
    </div>
</div>

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
            <div class="glass-sub anim-fade-up anim-d3">Sign in with your Student ID or Email</div>
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
            @php
                $qrToken = old('qr_token', session('qr_token') ?? request('qr_token'));
            @endphp
            @if($qrToken)
                <input type="hidden" name="qr_token" value="{{ $qrToken }}">
            @endif

            <!-- ID or Email  -  system detects role automatically -->
            <div class="glass-input-wrap anim-fade-up anim-d4">
                <i class="bi bi-person-fill g-icon"></i>
                <input type="text" name="identifier" id="idInput"
                       class="glass-input @error('identifier') is-invalid @enderror"
                       placeholder="Student ID or Email"
                       required autocomplete="username"
                       value="{{ old('identifier') }}">
            </div>
            @error('identifier')
                <div class="invalid-feedback-custom anim-fade-up anim-d4">{{ $message }}</div>
            @enderror

            <!-- Biometric Authentication (WebAuthn supported) -->
            <div id="fingerprintSection" style="display: none;">
                <div class="fp-row anim-fade-up anim-d5" onclick="handleBiometricLogin()" id="fpRowBtn">
                    <div class="fp-row-left">
                        <i class="bi bi-fingerprint" id="fpIcon"></i>
                        <div>
                            <div class="fp-row-label" id="fpLabel">Sign in with Biometrics</div>
                            <div class="fp-row-hint" id="fpHint">Touch sensor, Face ID, or device security</div>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right fp-row-arrow" id="fpArrow"></i>
                </div>
                <div class="glass-divider anim-fade-up anim-d5">or use password</div>
            </div>

            <!-- Password -->
            <div class="glass-input-wrap mb-3 anim-fade-up anim-d5">
                <i class="bi bi-lock-fill g-icon"></i>
                <input type="password" name="password" id="loginPassword"
                       class="glass-input has-eye @error('password') is-invalid @enderror"
                       placeholder="Password" required autocomplete="current-password">
                <button type="button" class="eye-toggle" onclick="toggleEye('loginPassword',this)" tabindex="-1">
                    <i class="bi bi-eye-slash"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback-custom anim-fade-up anim-d5" style="margin-top:-10px;margin-bottom:12px;">{{ $message }}</div>
            @enderror
            <!-- Remember Me & Forgot Password Row -->
            <div class="d-flex align-items-center justify-content-between mb-3 anim-fade-up anim-d5" style="font-size: 0.82rem; padding: 0 2px;">
                <label style="display: inline-flex; align-items: center; gap: 7px; cursor: pointer; user-select: none; margin: 0; color: rgba(255,255,255,0.85); font-weight: 500;">
                    <input type="checkbox" name="remember" id="rememberMe" value="1" checked style="width: 16px; height: 16px; accent-color: #d4af37; cursor: pointer; border-radius: 4px;">
                    <span>Remember me</span>
                </label>
                <a href="{{ route('otp.forgot.form') }}" style="color: rgba(212,175,55,0.9); text-decoration: none; font-weight: 500; transition: color 0.2s;">
                    Forgot password?
                </a>
            </div>

            <button type="submit" class="glass-btn glass-btn-primary anim-fade-up anim-d6" id="loginSubmitBtn">
                <i class="bi bi-box-arrow-in-right me-2"></i>SIGN IN
            </button>
        </form>

        <form method="POST" action="{{ route('recovery.login') }}" id="recoveryForm" style="display:none;">
            @csrf
            <div class="glass-input-wrap anim-fade-up anim-d4">
                <i class="bi bi-person-fill g-icon"></i>
                <input type="text" name="identifier" class="glass-input" placeholder="Student ID or Email" required autocomplete="username">
            </div>
            <div class="glass-input-wrap mb-3 anim-fade-up anim-d5">
                <i class="bi bi-key-fill g-icon"></i>
                <input type="text" name="recovery_code" class="glass-input" placeholder="Recovery Code (e.g. A1B2-C3D4)" required>
            </div>
            <button type="button" class="glass-btn glass-btn-secondary anim-fade-up anim-d6 mb-2" onclick="document.getElementById('recoveryForm').style.display='none'; document.getElementById('loginForm').style.display='block';">
                <i class="bi bi-arrow-left me-2"></i>BACK TO LOGIN
            </button>
            <button type="submit" class="glass-btn glass-btn-primary anim-fade-up anim-d6" id="recoverySubmitBtn">
                <i class="bi bi-box-arrow-in-right me-2"></i>SIGN IN WITH CODE
            </button>
        </form>

        <div style="text-align:center;margin-top:14px;" class="anim-fade-up anim-d7">
            <a href="#" onclick="document.getElementById('loginForm').style.display='none'; document.getElementById('recoveryForm').style.display='block'; return false;" class="glass-note-link">
                <i class="bi bi-key me-1"></i>Use Recovery Code
            </a>
        </div>
        <div class="glass-link-row anim-fade-up anim-d7">
            Don't have an account? <a href="{{ route('register') }}">Register here</a>
        </div>
        
        {{-- Version Badge - Visible on Mobile (when bottom bar is hidden) --}}
        <div class="d-block d-md-none text-center anim-fade-up anim-d7" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.08);">
            <span style="font-size: 0.7rem; color: rgba(207,164,111,0.5); font-weight: 600; letter-spacing: 0.5px;">
                VERSION {{ config('changelog.default_version', '2.2.0') }}
            </span>
        </div>

        {{-- Smart Download App Button - Mobile Only, Detects Installation State --}}
        <div id="smartAppDownloadRow" style="text-align: center; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.1); display: none;">
            <div style="font-size: 0.75rem; color: rgba(255,255,255,0.45); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Using a phone?</div>
            <button id="downloadAppBtn" type="button"
                style="display: inline-flex; align-items: center; justify-content: center; gap: 8px;
                       background: rgba(207,164,111,0.12); color: #CFA46F;
                       border: 1px solid rgba(207,164,111,0.35); border-radius: 12px;
                       padding: 10px 24px; font-size: 0.85rem; font-weight: 700;
                       cursor: pointer; transition: all 0.2s cubic-bezier(0.16,1,0.3,1);
                       box-shadow: 0 4px 14px rgba(0,0,0,0.25);">
                <i class="bi bi-download"></i> <span id="downloadAppBtnText">Download App</span>
            </button>
        </div>

    </div>
</div>

<!-- Desktop/Web Install App Button - Fixed Bottom Right -->
<div id="webInstallAppBtn" style="position: fixed; bottom: 20px; right: 20px; z-index: 999; display: none;">
    <button type="button" id="pwaInstallBtn"
        style="display: inline-flex; align-items: center; justify-content: center; gap: 10px;
               background: rgba(212, 175, 55, 0.95); color: #1a0a0a;
               border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 14px;
               padding: 14px 24px; font-size: 0.9rem; font-weight: 700;
               cursor: pointer; transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
               box-shadow: 0 8px 24px rgba(0,0,0,0.4);
               letter-spacing: 0.5px; text-transform: uppercase;
               backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);">
        <i class="bi bi-download" style="font-size: 1.1rem;"></i>
        <span id="pwaInstallBtnText">Install App</span>
    </button>
</div>

<script>
// ── PWA INSTALL BUTTON FOR WEB/DESKTOP - Bottom Right ──────────────────────────
(function() {
    var webInstallContainer = document.getElementById('webInstallAppBtn');
    var pwaInstallBtn = document.getElementById('pwaInstallBtn');
    var pwaInstallBtnText = document.getElementById('pwaInstallBtnText');
    var deferredPrompt;
    
    // Detect if user is on mobile device
    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    // Detect if running in standalone mode (app is already installed)
    function isAppInstalled() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               document.referrer.includes('android-app://');
    }
    
    // Show install button on desktop (not mobile) if app isn't already installed
    console.log('[PWA Install] Checking display conditions...');
    console.log('[PWA Install] Is mobile:', isMobileDevice());
    console.log('[PWA Install] Is installed:', isAppInstalled());
    console.log('[PWA Install] Container exists:', !!webInstallContainer);
    
    if (!isMobileDevice() && !isAppInstalled() && webInstallContainer) {
        console.log('[PWA Install] Showing install button');
        webInstallContainer.style.display = 'block';
        
        // Add hover effects via JavaScript
        pwaInstallBtn.addEventListener('mouseenter', function() {
            this.style.background = 'rgba(212, 175, 55, 1)';
            this.style.transform = 'translateY(-3px) scale(1.05)';
            this.style.boxShadow = '0 12px 32px rgba(0,0,0,0.5)';
        });
        
        pwaInstallBtn.addEventListener('mouseleave', function() {
            this.style.background = 'rgba(212, 175, 55, 0.95)';
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '0 8px 24px rgba(0,0,0,0.4)';
        });
    } else {
        console.log('[PWA Install] Install button hidden - conditions not met');
    }
    
    // Listen for the beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', function(e) {
        // Prevent the mini-infobar from appearing on mobile
        e.preventDefault();
        
        // Stash the event so it can be triggered later
        deferredPrompt = e;
        
        console.log('[PWA Install] Install prompt available');
    });
    
    // Handle install button click
    if (pwaInstallBtn) {
        pwaInstallBtn.addEventListener('click', async function() {
            if (!deferredPrompt) {
                console.log('[PWA Install] No install prompt available');
                
                // Show instructions for manual installation
                var isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
                var isEdge = /Edg/.test(navigator.userAgent);
                var isSafari = /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent);
                
                var instructions = 'To install this app:\n\n';
                
                if (isChrome || isEdge) {
                    instructions += 'Chrome/Edge:\n';
                    instructions += '1. Click the ⋮ menu (top right)\n';
                    instructions += '2. Select "Install app" or "Install ' + (document.title || 'Attendance') + '"\n';
                    instructions += '3. Click "Install" in the popup';
                } else if (isSafari) {
                    instructions += 'Safari:\n';
                    instructions += '1. Click the Share button\n';
                    instructions += '2. Scroll down and tap "Add to Dock"\n';
                    instructions += '3. Click "Add"';
                } else {
                    instructions += '1. Look for an install icon in your browser\'s address bar\n';
                    instructions += '2. Or check your browser menu for "Install app" option';
                }
                
                alert(instructions);
                return;
            }
            
            // Show the install prompt
            deferredPrompt.prompt();
            
            // Update button text
            if (pwaInstallBtnText) {
                pwaInstallBtnText.textContent = 'Installing...';
            }
            pwaInstallBtn.disabled = true;
            
            // Wait for the user to respond to the prompt
            const { outcome } = await deferredPrompt.userChoice;
            
            console.log('[PWA Install] User choice:', outcome);
            
            if (outcome === 'accepted') {
                console.log('[PWA Install] User accepted the install prompt');
                
                // Hide the button after successful install
                if (webInstallContainer) {
                    webInstallContainer.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(function() {
                        webInstallContainer.style.display = 'none';
                    }, 300);
                }
            } else {
                console.log('[PWA Install] User dismissed the install prompt');
                
                // Reset button
                if (pwaInstallBtnText) {
                    pwaInstallBtnText.textContent = 'Install App';
                }
                pwaInstallBtn.disabled = false;
            }
            
            // Clear the deferred prompt
            deferredPrompt = null;
        });
    }
    
    // Listen for successful app installation
    window.addEventListener('appinstalled', function() {
        console.log('[PWA Install] App was installed successfully');
        
        // Hide the install button
        if (webInstallContainer) {
            webInstallContainer.style.display = 'none';
        }
    });
    
    // Hide button if app is already installed
    if (isAppInstalled() && webInstallContainer) {
        webInstallContainer.style.display = 'none';
    }
})();

// ── SMART APP DOWNLOAD BUTTON - Detects Actual Installation State ──────────────
(function() {
    var downloadRow = document.getElementById('smartAppDownloadRow');
    var downloadBtn = document.getElementById('downloadAppBtn');
    var btnText = document.getElementById('downloadAppBtnText');
    
    // Configuration - update this URL to your official app download location
    var ANDROID_APP_URL = '/download/apk'; // or Google Play URL
    var IOS_APP_URL = '#'; // App Store URL if available
    
    // Detect if user is on mobile device
    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    // Detect if running in standalone mode (app is installed and opened)
    function isAppInstalled() {
        // PWA standalone mode detection
        var isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                          window.navigator.standalone === true ||
                          document.referrer.includes('android-app://');
        
        return isStandalone;
    }
    
    // Detect platform
    function getPlatform() {
        var ua = navigator.userAgent;
        if (/android/i.test(ua)) return 'android';
        if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) return 'ios';
        return 'desktop';
    }
    
    // Check if app is available for installation
    function checkAppAvailability() {
        var platform = getPlatform();
        var installed = isAppInstalled();
        var mobile = isMobileDevice();
        
        console.log('[Smart Download] Platform:', platform, 'Mobile:', mobile, 'Installed:', installed);
        
        // Hide on desktop
        if (!mobile || platform === 'desktop') {
            if (downloadRow) downloadRow.style.display = 'none';
            return;
        }
        
        // Hide if app is already installed
        if (installed) {
            if (downloadRow) downloadRow.style.display = 'none';
            return;
        }
        
        // Show download button for mobile users without app
        if (downloadRow) {
            downloadRow.style.display = 'block';
            
            // Set appropriate download URL based on platform
            if (downloadBtn) {
                downloadBtn.onclick = function() {
                    var url = platform === 'android' ? ANDROID_APP_URL : IOS_APP_URL;
                    
                    if (platform === 'android') {
                        // For Android, download APK or open Play Store
                        window.location.href = url;
                    } else if (platform === 'ios') {
                        // For iOS, show install instructions
                        alert('To install:\n\n1. Tap the Share button (□↑)\n2. Scroll down and tap "Add to Home Screen"\n3. Tap "Add" to install');
                    }
                };
            }
        }
    }
    
    // Initial check
    checkAppAvailability();
    
    // Re-check when page becomes visible (user returns from installer)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            console.log('[Smart Download] Page visible, rechecking installation state...');
            setTimeout(checkAppAvailability, 500);
        }
    });
    
    // Re-check when page gains focus
    window.addEventListener('focus', function() {
        console.log('[Smart Download] Page focused, rechecking installation state...');
        setTimeout(checkAppAvailability, 500);
    });
    
    // Listen for app installed event
    window.addEventListener('appinstalled', function() {
        console.log('[Smart Download] App installed event fired');
        if (downloadRow) downloadRow.style.display = 'none';
    });
    
    // Check for beforeinstallprompt (PWA available)
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        console.log('[Smart Download] PWA installation available');
        
        // If PWA is available, use native prompt
        if (downloadBtn) {
            downloadBtn.onclick = function() {
                e.prompt();
                e.userChoice.then(function(choice) {
                    if (choice.outcome === 'accepted') {
                        console.log('[Smart Download] User accepted PWA install');
                        if (downloadRow) downloadRow.style.display = 'none';
                    }
                });
            };
        }
    });
})();
</script>

<script>
// FingerprintJS initialization
const fpPromise = import('https://openfpcdn.io/fingerprintjs/v4')
    .then(FingerprintJS => FingerprintJS.load());

fpPromise
    .then(fp => fp.get())
    .then(result => {
        const visitorId = result.visitorId;
        var hiddenInput = document.getElementById('deviceFingerprint');
        if (hiddenInput) {
            hiddenInput.value = visitorId;
        }
    })
    .catch(error => console.error('FingerprintJS error:', error));

function toggleEye(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text'; icon.className = 'bi bi-eye'; btn.style.color = 'white';
    } else {
        input.type = 'password'; icon.className = 'bi bi-eye-slash'; btn.style.color = '';
    }
}

// Remember identifier in localStorage
var idInput = document.getElementById('idInput');
var rememberCheckbox = document.getElementById('rememberMe');

try {
    var savedId = localStorage.getItem('attendance_saved_identifier');
    if (savedId && idInput && !idInput.value) {
        idInput.value = savedId;
        if (rememberCheckbox) rememberCheckbox.checked = true;
    }
} catch (e) {}

// Loading state and remember credentials on submit
var loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function() {
        try {
            if (rememberCheckbox && rememberCheckbox.checked && idInput && idInput.value) {
                localStorage.setItem('attendance_saved_identifier', idInput.value.trim());
            } else if (rememberCheckbox && !rememberCheckbox.checked) {
                localStorage.removeItem('attendance_saved_identifier');
            }
        } catch (e) {}

        var btn = document.getElementById('loginSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="btn-spinner"></span>SIGNING IN...';
        }
    });
}

// ── INTELLIGENT BIOMETRIC BUTTON MANAGEMENT ──────────────────────────────
var idInput = document.getElementById('idInput');
var fpSec = document.getElementById('fingerprintSection');
var fpRowBtn = document.getElementById('fpRowBtn');
var fpLabel = document.getElementById('fpLabel');
var fpHint = document.getElementById('fpHint');
var fpIcon = document.getElementById('fpIcon');
var fpArrow = document.getElementById('fpArrow');

// Track button state
var biometricState = {
    available: false,
    hasCredentials: false,
    checking: false,
    identifier: null
};

// Check if WebAuthn is supported
function isBiometricSupported() {
    return window.isSecureContext && window.PublicKeyCredential !== undefined;
}

// Debounce function to avoid excessive API calls
function debounce(func, wait) {
    var timeout;
    return function executedFunction() {
        var context = this;
        var args = arguments;
        var later = function() {
            timeout = null;
            func.apply(context, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Check if user has registered biometric credentials
async function checkBiometricAvailability(identifier) {
    if (!identifier || identifier.length < 3) {
        hideBiometricButton();
        return;
    }

    if (!isBiometricSupported()) {
        hideBiometricButton();
        return;
    }

    // Avoid redundant checks
    if (biometricState.identifier === identifier && !biometricState.checking) {
        return;
    }

    biometricState.checking = true;
    biometricState.identifier = identifier;

    try {
        // Show checking state
        showBiometricButton('checking');

        var response = await fetch('{{ route("webauthn.login.options") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ identifier: identifier, student_number: identifier })
        });

        var data = await response.json();

        if (data.success && data.allowCredentials && data.allowCredentials.length > 0) {
            // User has registered biometric credentials
            biometricState.hasCredentials = true;
            biometricState.available = true;
            showBiometricButton('ready');
        } else {
            // User has no biometric credentials
            biometricState.hasCredentials = false;
            biometricState.available = true;
            showBiometricButton('not-registered');
        }
    } catch (error) {
        console.log('Biometric check failed:', error);
        hideBiometricButton();
    } finally {
        biometricState.checking = false;
    }
}

// Debounced version for input event
var debouncedCheck = debounce(function() {
    var identifier = idInput.value.trim();
    checkBiometricAvailability(identifier);
}, 600);

// Show biometric button in different states
function showBiometricButton(state) {
    if (!fpSec) return;

    fpSec.style.display = 'block';

    if (state === 'checking') {
        fpRowBtn.setAttribute('data-state', 'checking');
        fpRowBtn.style.opacity = '0.6';
        fpRowBtn.style.pointerEvents = 'none';
        fpLabel.textContent = 'Checking biometric availability...';
        fpHint.textContent = 'Please wait';
        fpIcon.className = 'bi bi-hourglass-split';
        fpArrow.className = 'bi bi-hourglass-split fp-row-arrow';
    } else if (state === 'ready') {
        fpRowBtn.setAttribute('data-state', 'ready');
        fpRowBtn.style.opacity = '1';
        fpRowBtn.style.pointerEvents = '';
        fpLabel.textContent = 'Sign in with Biometrics';
        fpHint.textContent = 'Touch sensor, Face ID, or device security';
        fpIcon.className = 'bi bi-fingerprint';
        fpArrow.className = 'bi bi-chevron-right fp-row-arrow';
        fpRowBtn.style.cursor = 'pointer';
    } else if (state === 'not-registered') {
        fpRowBtn.setAttribute('data-state', 'not-registered');
        fpRowBtn.style.opacity = '0.7';
        fpRowBtn.style.pointerEvents = '';
        fpLabel.textContent = 'Set up biometric login';
        fpHint.textContent = 'Sign in first, then enable in your profile';
        fpIcon.className = 'bi bi-info-circle';
        fpArrow.className = 'bi bi-arrow-right fp-row-arrow';
        fpRowBtn.style.cursor = 'help';
    }
}

// Hide biometric button
function hideBiometricButton() {
    if (fpSec) {
        fpSec.style.display = 'none';
    }
    biometricState.available = false;
    biometricState.hasCredentials = false;
}

// Reset button to default state
function resetBiometricButton() {
    if (!fpRowBtn) return;
    fpRowBtn.setAttribute('data-state', 'ready');
    fpRowBtn.style.opacity = '1';
    fpRowBtn.style.pointerEvents = '';
    fpLabel.textContent = 'Sign in with Biometrics';
    fpHint.textContent = 'Touch sensor, Face ID, or device security';
    fpIcon.className = 'bi bi-fingerprint';
    fpArrow.className = 'bi bi-chevron-right fp-row-arrow';
}

// Handle biometric login button click
async function handleBiometricLogin() {
    if (!biometricState.hasCredentials) {
        // User hasn't registered biometrics yet
        showBiometricInfo();
        return;
    }

    // Proceed with actual biometric authentication
    await performBiometricLogin();
}

// Show info modal for unregistered users
function showBiometricInfo() {
    var msg = 'Biometric login is not set up yet.\n\n' +
              'To enable it:\n' +
              '1. Sign in with your password below\n' +
              '2. Go to your Profile page\n' +
              '3. Find "Fingerprint / Biometric Login" section\n' +
              '4. Register your device\n' +
              '5. Next time you can use biometric login!';
    
    alert(msg);
    
    var passInput = document.getElementById('loginPassword');
    if (passInput) {
        passInput.focus();
    }
}

// Listen to identifier input
if (idInput) {
    idInput.addEventListener('input', debouncedCheck);
    
    // Also check on blur for better UX
    idInput.addEventListener('blur', function() {
        var identifier = idInput.value.trim();
        if (identifier) {
            checkBiometricAvailability(identifier);
        }
    });
}

// Restore identifier on validation error and check biometric availability
@if(old('identifier'))
    if (idInput) {
        idInput.value = '{{ old('identifier') }}';
        checkBiometricAvailability('{{ old('identifier') }}');
    }
@endif

// Initial check if identifier is prefilled
window.addEventListener('DOMContentLoaded', function() {
    console.log('[Biometric] DOMContentLoaded - checking for prefilled identifier');
    if (idInput && idInput.value.trim()) {
        console.log('[Biometric] Prefilled identifier found:', idInput.value.trim());
        checkBiometricAvailability(idInput.value.trim());
    } else {
        console.log('[Biometric] No prefilled identifier');
    }
});

// ── BIOMETRIC AUTHENTICATION IMPLEMENTATION ──────────────────────────────
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

async function performBiometricLogin() {
    if (!window.isSecureContext) {
        showBiometricError('Biometric authentication requires a secure connection (HTTPS or localhost).');
        return;
    }

    if (!window.PublicKeyCredential) {
        showBiometricError('Biometric authentication is not supported by your browser. Please use Chrome, Safari, or Edge.');
        return;
    }

    var studentNumber = idInput.value.trim();
    if (!studentNumber) {
        idInput.focus();
        idInput.style.borderColor = 'rgba(252,165,165,0.8)';
        setTimeout(function() { idInput.style.borderColor = ''; }, 2000);
        showBiometricError('Please enter your Student ID or Email first.');
        return;
    }

    // Update button to show progress
    if (fpRowBtn) {
        fpRowBtn.style.opacity = '0.6';
        fpRowBtn.style.pointerEvents = 'none';
    }
    if (fpLabel) fpLabel.textContent = 'Preparing authentication...';
    if (fpArrow) fpArrow.className = 'bi bi-hourglass-split fp-row-arrow';

    try {
        // Step 1: Get authentication options from server
        var optRes = await fetch('{{ route("webauthn.login.options") }}', {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Content-Type': 'application/json', 
                'Accept': 'application/json' 
            },
            body: JSON.stringify({ student_number: studentNumber, identifier: studentNumber })
        });
        
        var opts = await optRes.json();
        
        console.log('WebAuthn options received:', opts);

        if (!opts.success) {
            showBiometricError(opts.message || 'Biometric login is not set up for this account. Please sign in with your password.');
            focusPasswordField();
            resetBiometricButton();
            return;
        }

        // Step 2: Prepare WebAuthn request
        var challenge = base64ToUint8Array(opts.challenge);
        var allowCredentials = (opts.allowCredentials || []).map(function(c) {
            return { type: c.type, id: base64ToUint8Array(c.id) };
        });
        
        if (allowCredentials.length === 0) {
            showBiometricError('No biometric credentials found. Please sign in with your password and register in your profile.');
            focusPasswordField();
            resetBiometricButton();
            return;
        }

        var rpId = opts.rpId || window.location.hostname;
        var hostname = window.location.hostname;
        var isIp = /^(\d{1,3}\.){3}\d{1,3}$/.test(hostname) || hostname.includes(':');
        
        var getPublicKey = {
            challenge: challenge,
            allowCredentials: allowCredentials,
            userVerification: 'preferred',
            timeout: 60000
        };
        
        // Only set rpId for non-IP hostnames
        if (rpId && !isIp) {
            getPublicKey.rpId = rpId;
        }

        // Update button for user action
        if (fpLabel) fpLabel.textContent = 'Waiting for biometric...';
        if (fpIcon) fpIcon.className = 'bi bi-hand-index';

        // Step 3: Request biometric authentication from browser/device
        console.log('Requesting biometric authentication...');
        var assertion = await navigator.credentials.get({
            publicKey: getPublicKey
        });

        if (!assertion) {
            throw new Error('Authentication was cancelled or failed.');
        }

        console.log('Biometric authentication successful, verifying...');

        // Update button
        if (fpLabel) fpLabel.textContent = 'Verifying...';
        if (fpIcon) fpIcon.className = 'bi bi-shield-check';

        // Step 4: Send assertion to server for verification
        var credentialId = bufferToBase64Url(assertion.rawId);
        var assertionData = {
            id: credentialId,
            response: {
                clientDataJSON: bufferToBase64Url(new Uint8Array(assertion.response.clientDataJSON)),
                authenticatorData: bufferToBase64Url(new Uint8Array(assertion.response.authenticatorData)),
                signature: bufferToBase64Url(new Uint8Array(assertion.response.signature))
            }
        };
        
        console.log('Sending assertion to server for verification...');

        var loginRes = await fetch('{{ route("webauthn.login") }}', {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Content-Type': 'application/json', 
                'Accept': 'application/json' 
            },
            body: JSON.stringify({ credential_id: credentialId, assertion: assertionData })
        });
        
        var result = await loginRes.json();

        if (result.success) {
            console.log('Login successful! Redirecting...');
            
            // Update button to show success
            if (fpLabel) fpLabel.textContent = '✓ Authenticated! Redirecting...';
            if (fpIcon) fpIcon.className = 'bi bi-check-circle-fill';
            if (fpArrow) fpArrow.className = 'bi bi-check2 fp-row-arrow';
            if (fpRowBtn) fpRowBtn.style.opacity = '1';
            
            // Redirect to dashboard
            window.location.href = result.redirect;
        } else {
            console.error('Server verification failed:', result);
            showBiometricError(result.message || 'Authentication verification failed. Please try again or use your password.');
            focusPasswordField();
            resetBiometricButton();
        }
    } catch (err) {
        console.error('Biometric authentication error:', err);
        
        var errorMessage = 'Authentication failed. ';
        
        if (err.name === 'NotAllowedError') {
            errorMessage = 'Authentication was cancelled or timed out. Please try again or sign in with your password.';
        } else if (err.name === 'InvalidStateError') {
            errorMessage = 'No matching biometric credential found on this browser. Please sign in with your password and register this browser in your profile.';
        } else if (err.name === 'SecurityError') {
            errorMessage = 'Security error: Please ensure you\'re using HTTPS or localhost.';
        } else if (err.name === 'NotSupportedError') {
            errorMessage = 'Biometric authentication is not supported on this browser or device.';
        } else {
            errorMessage += (err.message || 'Please try again or use your password.');
        }
        
        showBiometricError(errorMessage);
        focusPasswordField();
        resetBiometricButton();
    }
}

// Helper function to focus password field
function focusPasswordField() {
    var passInput = document.getElementById('loginPassword');
    if (passInput) {
        passInput.focus();
        passInput.style.borderColor = '#d4af37';
        setTimeout(function() { 
            if (passInput) passInput.style.borderColor = ''; 
        }, 3000);
    }
}

// Show biometric error message
function showBiometricError(msg) {
    var err = document.getElementById('biometricError');
    if (!err) {
        err = document.createElement('div');
        err.id = 'biometricError';
        err.className = 'glass-alert';
        err.style.marginBottom = '10px';
        if (fpSec) {
            fpSec.insertBefore(err, fpSec.firstChild);
        }
    }
    err.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>' + msg;
    err.style.display = 'block';
    
    // Auto-hide after 8 seconds
    setTimeout(function() { 
        if (err && err.parentNode) {
            err.style.display = 'none';
        }
    }, 8000);
}
</script>
</body>
</html>
