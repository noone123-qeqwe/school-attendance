@extends('layouts.mobile-app')

@section('title', 'Scan QR - Smart Attendance')

@section('content')
<nav class="sd-scan-desktop-bar d-none d-lg-flex" aria-label="Desktop check-in navigation">
    <div class="sd-scan-desktop-nav">
        <a href="{{ route('home') }}" class="sd-scan-nav-link is-primary">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>Back to Dashboard</span>
        </a>
        <a href="{{ route('student.classes') }}" class="sd-scan-nav-link">
            <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
            <span>Classes</span>
        </a>
        <a href="{{ route('student.calendar') }}" class="sd-scan-nav-link">
            <i class="bi bi-calendar-event" aria-hidden="true"></i>
            <span>Attendance &amp; Calendar</span>
        </a>
    </div>
    <div class="sd-scan-desktop-modes" role="group" aria-label="Check-in method">
        <button type="button" class="sd-scan-mode-pill is-active" id="sdModeScanBtn" data-action="switch-scan">
            <i class="bi bi-camera-video" aria-hidden="true"></i>
            <span>QR Camera</span>
        </button>
        <button type="button" class="sd-scan-mode-pill" id="sdModeCodeBtn" data-action="switch-code">
            <i class="bi bi-123" aria-hidden="true"></i>
            <span>6-Digit Code</span>
        </button>
    </div>
</nav>
<div class="mobile-scan-page">
    <div class="scan-hero">
        <div class="scan-hero-icon" aria-hidden="true">
            <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
        </div>
        <h2 class="scan-hero-title">Scan Attendance QR</h2>
        <p class="scan-hero-subtitle">Tap the button below to open the camera<br>and scan your instructor's QR code.</p>
    </div>

    <div class="scan-action-area">
        <button type="button" class="scan-open-btn touchable" data-action="open-scanner" id="openScannerBtn">
            <i class="bi bi-camera-fill" aria-hidden="true"></i>
            <span>Open QR Scanner</span>
        </button>

        <p class="scan-divider-text">or verify manually</p>

        <div class="scan-alt-grid">
            <button type="button" class="scan-code-btn touchable" id="openCodeBtn" data-action="open-code">
                <i class="bi bi-key-fill" aria-hidden="true"></i>
                <span>6-Digit Code</span>
            </button>

            <button type="button" class="scan-paste-btn touchable" id="openPasteBtn" data-action="paste-copied-code">
                <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                <span>Paste Code</span>
            </button>
        </div>
    </div>

    <p id="scanUnavailable" class="scan-unavailable" role="alert" hidden>
        The scanner is unavailable right now. Refresh this page or ask your instructor for help.
    </p>

    <div class="scan-info-card">
        <div class="scan-info-row">
            <i class="bi bi-shield-check text-success" aria-hidden="true"></i>
            <span>Your GPS location is used to verify you're inside the classroom</span>
        </div>
        <div class="scan-info-row">
            <i class="bi bi-camera text-info" aria-hidden="true"></i>
            <span>Camera access is required to scan the QR code</span>
        </div>
        <div class="scan-info-row">
            <i class="bi bi-clock text-warning" aria-hidden="true"></i>
            <span>QR codes expire quickly — scan within the session window</span>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/student-desktop-ux.css') }}?v={{ filemtime(public_path('css/student-desktop-ux.css')) }}">
<style>
    .mobile-scan-page {
        display: flex;
        flex-direction: column;
        align-items: center;
        max-width: 440px;
        margin: 0 auto;
        padding: 12px 0 24px;
        min-height: calc(100dvh - var(--header-height) - var(--bottom-nav-height) - 48px);
    }

    .scan-hero {
        width: 100%;
        text-align: center;
        padding: 22px 16px;
        margin-bottom: 18px;
        border-radius: 18px;
        background: linear-gradient(145deg, rgba(122, 26, 26, 0.2), rgba(24, 16, 14, 0.92));
        border: 1px solid rgba(207, 164, 111, 0.2);
    }

    .scan-hero-icon {
        width: 72px;
        height: 72px;
        background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
        border-radius: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        color: var(--bg-dark);
        margin: 0 auto 14px;
        box-shadow: 0 8px 26px rgba(207, 164, 111, 0.35);
        animation: scanPulse 2.5s ease-in-out infinite;
    }

    @keyframes scanPulse {
        0%, 100% { box-shadow: 0 8px 26px rgba(207, 164, 111, 0.35); }
        50% { box-shadow: 0 8px 36px rgba(207, 164, 111, 0.58), 0 0 0 10px rgba(207, 164, 111, 0.08); }
    }

    .scan-hero-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 6px;
    }

    .scan-hero-subtitle {
        font-size: 0.84rem;
        color: var(--text-secondary);
        line-height: 1.5;
        margin: 0;
    }

    .scan-action-area {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 0;
        margin-bottom: 18px;
    }

    .scan-open-btn {
        width: 100%;
        min-height: 54px;
        padding: 15px 22px;
        background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
        color: var(--bg-dark);
        border: none;
        border-radius: 16px;
        font-size: 1rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        box-shadow: 0 6px 22px rgba(207, 164, 111, 0.32);
        transition: all 0.2s ease;
    }

    .scan-open-btn i {
        font-size: 1.25rem;
    }

    .scan-open-btn:active {
        transform: scale(0.96);
        box-shadow: 0 3px 12px rgba(207, 164, 111, 0.25);
    }

    .scan-divider-text {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--text-muted);
        margin: 12px 0;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .scan-alt-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    @media (max-width: 340px) {
        .scan-alt-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    .scan-code-btn,
    .scan-paste-btn {
        width: 100%;
        min-height: 48px;
        padding: 12px 14px;
        background: rgba(255, 255, 255, 0.04);
        color: var(--gold-light, #F3E7CD);
        border: 1px solid rgba(207, 164, 111, 0.28);
        border-radius: 14px;
        font-size: 0.84rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-top: 0;
    }

    .scan-paste-btn {
        background: rgba(207, 164, 111, 0.14);
        color: #f5dfa8;
        border-color: rgba(207, 164, 111, 0.4);
    }

    .scan-code-btn:active,
    .scan-paste-btn:active {
        transform: scale(0.96);
        background: var(--bg-card-hover);
    }

    .scan-info-card {
        width: 100%;
        background: var(--bg-card);
        border: 1px solid rgba(207, 164, 111, 0.15);
        border-radius: 16px;
        padding: 18px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .scan-unavailable {
        width: 100%;
        padding: 14px 16px;
        margin-bottom: 20px;
        border: 1px solid rgba(239, 68, 68, 0.45);
        border-radius: 12px;
        background: rgba(239, 68, 68, 0.12);
        color: #fecaca;
        line-height: 1.5;
    }

    .scan-info-row {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 13px;
        color: var(--text-secondary);
        line-height: 1.5;
    }

    .scan-info-row i {
        font-size: 16px;
        flex-shrink: 0;
        margin-top: 1px;
    }
</style>
@endpush

@push('scripts')
<script @cspNonce>
(function() {
    function syncDesktopModePills(mode) {
        var scanBtn = document.getElementById('sdModeScanBtn');
        var codeBtn = document.getElementById('sdModeCodeBtn');
        if (!scanBtn || !codeBtn) return;
        var isCode = mode === 'code';
        scanBtn.classList.toggle('is-active', !isCode);
        codeBtn.classList.toggle('is-active', isCode);
    }

    document.addEventListener('DOMContentLoaded', function() {
        var params = new URLSearchParams(window.location.search);
        syncDesktopModePills(params.get('open_code') === '1' ? 'code' : 'scan');

        document.addEventListener('click', function(e) {
            var trigger = e.target.closest('[data-action="switch-scan"], [data-action="switch-code"], [data-action="open-code"], [data-action="paste-copied-code"]');
            if (!trigger) return;
            var action = trigger.getAttribute('data-action');
            syncDesktopModePills(action === 'switch-scan' ? 'scan' : 'code');
        }, true);

        var codeContainer = document.getElementById('codeModeContainer');
        if (codeContainer && typeof MutationObserver !== 'undefined') {
            new MutationObserver(function() {
                var visible = window.getComputedStyle(codeContainer).display !== 'none';
                syncDesktopModePills(visible ? 'code' : 'scan');
            }).observe(codeContainer, { attributes: true, attributeFilter: ['style', 'class'] });
        }
    });
})();
</script>
@endpush
