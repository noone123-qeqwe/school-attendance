@extends('layouts.mobile-app')

@section('title', 'Scan QR - Smart Attendance')

@section('content')
<div class="mobile-scan-page">
    <div class="scan-hero">
        <div class="scan-hero-icon">
            <i class="bi bi-qr-code-scan"></i>
        </div>
        <h2 class="scan-hero-title">Scan Attendance QR</h2>
        <p class="scan-hero-subtitle">Tap the button below to open the camera<br>and scan your instructor's QR code.</p>
    </div>

    <div class="scan-action-area">
        <button type="button" class="scan-open-btn touchable" data-action="open-scanner" id="openScannerBtn" onclick="if(typeof mobileScanButtonTapped==='function'){mobileScanButtonTapped(event)}">
            <i class="bi bi-camera-fill"></i>
            <span>Open QR Scanner</span>
        </button>

        <p class="scan-divider-text">or</p>

        <button type="button" class="scan-code-btn touchable" id="openCodeBtn" data-action="open-code" onclick="if(typeof openStudentScanner==='function'){openStudentScanner('code')}else if(typeof mobileScanButtonTapped==='function'){mobileScanButtonTapped(event)}">
            <i class="bi bi-key-fill"></i>
            <span>Enter 6-Digit Code Instead</span>
        </button>
    </div>

    <div class="scan-info-card">
        <div class="scan-info-row">
            <i class="bi bi-shield-check text-success"></i>
            <span>Your GPS location is used to verify you're inside the classroom</span>
        </div>
        <div class="scan-info-row">
            <i class="bi bi-camera text-info"></i>
            <span>Camera access is required to scan the QR code</span>
        </div>
        <div class="scan-info-row">
            <i class="bi bi-clock text-warning"></i>
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
        padding: 20px 0 32px;
        min-height: calc(100vh - var(--header-height) - var(--bottom-nav-height) - 40px);
    }

    .scan-hero {
        text-align: center;
        margin-bottom: 40px;
    }

    .scan-hero-icon {
        width: 96px;
        height: 96px;
        background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 44px;
        color: var(--bg-dark);
        margin: 0 auto 20px;
        box-shadow: 0 8px 32px rgba(207, 164, 111, 0.4);
        animation: scanPulse 2.5s ease-in-out infinite;
    }

    @keyframes scanPulse {
        0%, 100% { box-shadow: 0 8px 32px rgba(207, 164, 111, 0.4); }
        50% { box-shadow: 0 8px 48px rgba(207, 164, 111, 0.65), 0 0 0 16px rgba(207, 164, 111, 0.08); }
    }

    .scan-hero-title {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 10px;
    }

    .scan-hero-subtitle {
        font-size: 14px;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    .scan-action-area {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
        margin-bottom: 32px;
    }

    .scan-open-btn {
        width: 100%;
        padding: 18px 24px;
        background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
        color: var(--bg-dark);
        border: none;
        border-radius: 18px;
        font-size: 17px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        cursor: pointer;
        box-shadow: 0 6px 24px rgba(207, 164, 111, 0.35);
        transition: all 0.2s ease;
    }

    .scan-open-btn i {
        font-size: 22px;
    }

    .scan-open-btn:active {
        transform: scale(0.96);
        box-shadow: 0 3px 12px rgba(207, 164, 111, 0.25);
    }

    .scan-divider-text {
        font-size: 13px;
        color: var(--text-muted);
        margin: 14px 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .scan-code-btn {
        width: 100%;
        padding: 14px 24px;
        background: var(--bg-card);
        color: var(--gold-primary);
        border: 1px solid rgba(207, 164, 111, 0.3);
        border-radius: 16px;
        font-size: 15px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .scan-code-btn:active {
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
    document.addEventListener('DOMContentLoaded', function() {
        const openScanBtn = document.getElementById('openScannerBtn');
        const openCodeBtn = document.getElementById('openCodeBtn');

        if (openScanBtn) {
            openScanBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (typeof openStudentScanner === 'function') {
                    openStudentScanner('scan');
                } else if (typeof mobileScanButtonTapped === 'function') {
                    mobileScanButtonTapped(e);
                }
            });
        }

        if (openCodeBtn) {
            openCodeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (typeof openStudentScanner === 'function') {
                    openStudentScanner('code');
                }
            });
        }

        // Auto-open the scanner immediately when this dedicated scan page loads
        setTimeout(function() {
            if (typeof openStudentScanner === 'function') {
                openStudentScanner('scan');
            } else if (typeof mobileScanButtonTapped === 'function') {
                mobileScanButtonTapped(null);
            }
        }, 300);
    });
</script>
@endpush
