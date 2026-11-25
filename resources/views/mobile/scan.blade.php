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
        padding: 24px 18px;
        margin-bottom: 16px;
        border-radius: 14px;
        background: #14080C;
        border: 1px solid rgba(250, 247, 248, 0.11);
        border-top: 2px solid #6B192B;
    }

    .scan-hero-icon {
        width: 64px;
        height: 64px;
        background: #1C0C11;
        border: 1px solid rgba(232, 152, 168, 0.3);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        color: #E898A8;
        margin: 0 auto 14px;
    }

    .scan-hero-title {
        font-size: 1.24rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #FAF7F8;
        margin-bottom: 6px;
    }

    .scan-hero-subtitle {
        font-size: 0.84rem;
        color: #D4C6CA;
        line-height: 1.5;
        margin: 0;
    }

    .scan-action-area {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 0;
        margin-bottom: 16px;
    }

    .scan-open-btn {
        width: 100%;
        min-height: 52px;
        padding: 14px 20px;
        background: #FAF7F8;
        color: #14080C;
        border: 1px solid #FAF7F8;
        border-radius: 12px;
        font-size: 0.96rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        transition: background 0.15s ease, transform 0.12s ease;
    }

    .scan-open-btn:hover {
        background: #FFFFFF;
        color: #0B0406;
    }

    .scan-open-btn i {
        font-size: 1.2rem;
    }

    .scan-open-btn:active {
        transform: scale(0.985);
    }

    .scan-divider-text {
        font-size: 0.71rem;
        font-weight: 700;
        color: #9E8990;
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
        background: #14080C;
        color: #FAF7F8;
        border: 1px solid rgba(250, 247, 248, 0.14);
        border-radius: 10px;
        font-size: 0.84rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease, transform 0.12s ease;
        margin-top: 0;
    }

    .scan-paste-btn {
        background: #1C0C11;
        color: #E898A8;
        border-color: rgba(232, 152, 168, 0.28);
    }

    .scan-code-btn:hover,
    .scan-paste-btn:hover {
        background: #261118;
        border-color: rgba(250, 247, 248, 0.28);
        color: #FFFFFF;
    }

    .scan-code-btn:active,
    .scan-paste-btn:active {
        transform: scale(0.985);
    }

    .scan-info-card {
        width: 100%;
        background: #14080C;
        border: 1px solid rgba(250, 247, 248, 0.09);
        border-radius: 14px;
        padding: 16px 18px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .scan-unavailable {
        width: 100%;
        padding: 13px 16px;
        margin-bottom: 16px;
        border: 1px solid rgba(251, 113, 133, 0.38);
        border-left: 3px solid #FB7185;
        border-radius: 10px;
        background: rgba(153, 27, 45, 0.18);
        color: #FFE4E8;
        line-height: 1.5;
    }

    .scan-info-row {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 0.82rem;
        color: #D4C6CA;
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
