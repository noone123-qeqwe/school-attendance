<!-- Student QR & 6-Digit PIN Attendance Scanner -->
@php($scannerPage = request()->routeIs('mobile.scan'))
<div id="studentScannerModal"
     class="sf-scanner-screen {{ $scannerPage ? 'scanner-page-screen' : '' }}"
     style="display:none;{{ $scannerPage ? '' : 'position:fixed;inset:0;z-index:1300;' }}"
     @if($scannerPage) role="region" aria-labelledby="scannerPageTitle" @else role="dialog" aria-modal="true" aria-label="Student Attendance Scanner" @endif>

    {{-- Top Floating Bar --}}
    <div class="sf-scanner-topbar">
        <span class="sf-live-scanner-pill" id="sfScannerTopPill">
            <span style="width:6px;height:6px;border-radius:50%;background:var(--sf-rose-bright);display:inline-block;"></span>
            <span id="sfScannerPillLabel">LIVE QR SCANNER</span>
        </span>

        <div style="display:flex;align-items:center;gap:8px;">
            <button type="button" id="torchCameraBtn" data-action="toggle-torch" class="sf-icon-btn" title="Flashlight" aria-label="Flashlight" aria-pressed="false" style="display:none;border-radius:50%;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            </button>
            <button type="button" id="flipCameraBtn" data-action="flip-camera" class="sf-icon-btn" title="Flip Camera" aria-label="Flip Camera" style="display:none !important;border-radius:50%;">
                <i class="bi bi-camera-reverse" aria-hidden="true"></i>
            </button>
            <button type="button" data-action="close-scanner" class="sf-icon-btn" style="border-radius:50%;" title="{{ $scannerPage ? 'Back to dashboard' : 'Close scanner' }}" aria-label="{{ $scannerPage ? 'Back to dashboard' : 'Close scanner' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Mode Switcher (Compact Segmented Pill) --}}
    <div class="sf-segmented" id="mobileModeSwitcher" role="group" aria-label="Check-in method" style="margin-top:14px;">
        <button type="button" id="tabScanMode" class="sf-segmented-btn active" data-action="switch-scan" aria-pressed="true" aria-controls="scannerActiveView">
            Scan QR
        </button>
        <button type="button" id="tabCodeMode" class="sf-segmented-btn" data-action="switch-code" aria-pressed="false" aria-controls="scannerCodeView">
            Enter PIN
        </button>
    </div>

    <div class="sf-scanner-center">
        @if($scannerPage)
            <h1 id="scannerPageTitle" class="sf-sr-only">Attendance check-in</h1>
        @endif

        {{-- Mode 1: Camera QR Scanner --}}
        <div id="scannerActiveView" style="width:100%;display:flex;flex-direction:column;align-items:center;">
            <h2 style="margin:0 0 4px;font-size:20px;font-weight:700;color:var(--sf-text);">Scan Classroom QR</h2>
            <p class="sf-scanner-sub">Align the instructor's rotating QR code inside the frame</p>

            <div id="scannerVideoContainer" class="sf-viewfinder">
                <div id="reader"></div>
                <div class="sf-vf-corner tl" aria-hidden="true"></div>
                <div class="sf-vf-corner tr" aria-hidden="true"></div>
                <div class="sf-vf-corner bl" aria-hidden="true"></div>
                <div class="sf-vf-corner br" aria-hidden="true"></div>
                <div id="scannerLaser" class="sf-vf-laser" style="display:none;" aria-hidden="true"></div>

                <div id="scannerLoadingOverlay" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;background:rgba(10,5,7,0.84);z-index:6;color:var(--sf-text-soft);font-size:12.5px;" role="status">
                    <div class="spinner-border spinner-border-sm" style="color:var(--sf-rose);width:26px;height:26px;" aria-hidden="true"></div>
                    <span>Starting camera...</span>
                </div>

                <div id="scannerProcessingOverlay" style="position:absolute;inset:0;display:none;flex-direction:column;align-items:center;justify-content:center;gap:8px;background:rgba(10,5,7,0.9);z-index:7;color:var(--sf-text);font-size:13px;font-weight:600;" role="status">
                    <div class="spinner-border spinner-border-sm" style="color:var(--sf-emerald);width:28px;height:28px;" aria-hidden="true"></div>
                    <span>Verifying attendance...</span>
                    <div class="processing-sub" style="font-size:11.5px;color:var(--sf-text-muted);font-weight:500;"></div>
                </div>

                <div id="scannerFallbackNotice" style="position:absolute;inset:0;display:none;flex-direction:column;align-items:center;justify-content:center;padding:22px;background:var(--sf-surface);z-index:8;text-align:center;">
                    <div id="fallbackIconWrap" style="font-size:26px;color:var(--sf-rose);margin-bottom:8px;"></div>
                    <h3 id="fallbackTitle" style="margin:0 0 6px;font-size:15px;font-weight:700;color:var(--sf-text);">Camera Access Required</h3>
                    <p id="scannerFallbackText" style="margin:0 0 14px;font-size:12px;color:var(--sf-text-secondary);line-height:1.45;">Camera access is required to scan the attendance QR code.</p>
                    <div style="display:flex;flex-direction:column;gap:8px;width:100%;">
                        <button type="button" class="sf-btn sf-btn-primary sf-btn-block" id="retryCameraBtn" data-action="allow-camera">
                            <span id="retryCameraBtnText">Allow Camera</span>
                        </button>
                        <button type="button" class="sf-btn sf-btn-secondary sf-btn-block" data-action="switch-code">
                            Enter 6-Digit PIN Instead
                        </button>
                    </div>
                </div>
            </div>

            <div id="scannerGpsBadge" class="sf-pill" data-gps-state="searching" role="status" aria-live="polite" style="margin-top:16px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <span class="scanner-gps-text">Acquiring GPS location...</span>
            </div>

            <div style="display:flex;flex-direction:column;gap:8px;width:100%;max-width:310px;margin-top:18px;">
                <button type="button" class="sf-btn sf-btn-primary sf-btn-lg sf-btn-block" data-action="switch-code">
                    Enter 6-Digit PIN Instead
                </button>
                <button type="button" class="sf-btn sf-btn-secondary sf-btn-block" data-action="paste-copied-code" id="cameraPasteBtn">
                    Paste Copied Code
                </button>
            </div>
        </div>

        {{-- Mode 2: 6-Digit Instructor PIN View --}}
        <div id="scannerCodeView" style="display:none;width:100%;">
            <div class="sf-card" style="padding:22px 20px;text-align:left;">
                <h2 style="margin:0 0 3px;font-size:17px;font-weight:700;color:var(--sf-text);">Enter Instructor PIN</h2>
                <p style="margin:0 0 14px;font-size:12px;color:var(--sf-text-muted);">Type the 6-digit session code shown by your instructor</p>

                <label class="sf-label" for="sfPinBox0">6-Digit Session PIN</label>
                <div class="sf-otp-row" role="group" aria-label="6-digit attendance PIN">
                    @for($p = 0; $p < 6; $p++)
                        <input type="text"
                               id="sfPinBox{{ $p }}"
                               class="sf-otp-box"
                               maxlength="1"
                               inputmode="numeric"
                               autocomplete="{{ $p === 0 ? 'one-time-code' : 'off' }}"
                               aria-label="Digit {{ $p + 1 }} of 6"
                               data-pin-idx="{{ $p }}">
                    @endfor
                </div>

                {{-- Backing input kept synchronized for full token paste & existing logic --}}
                <input type="text" id="directSessionCodeInput" class="sf-sr-only" maxlength="500" autocomplete="off" aria-label="Attendance code">

                <div id="codeEntryHint" style="font-size:11.5px;color:var(--sf-text-muted);margin-bottom:10px;" aria-live="polite">
                    6-digit session PIN or copied QR token
                </div>

                <div id="scannerCodeGpsBadge" class="sf-pill" data-gps-state="searching" role="status" aria-live="polite" style="margin-bottom:16px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span class="scanner-gps-text">Acquiring GPS location...</span>
                </div>

                <div style="display:flex;flex-direction:column;gap:8px;">
                    <button type="button" id="codeSubmitBtn" class="sf-btn sf-btn-primary sf-btn-lg sf-btn-block" data-action="submit-code">
                        Verify &amp; Record Attendance
                    </button>
                    <button type="button" id="pasteClipboardBtn" class="sf-btn sf-btn-secondary sf-btn-block" data-action="paste-copied-code">
                        Paste Copied Code
                    </button>
                    <button type="button" id="switchToCameraBtn" class="sf-btn sf-btn-ghost sf-btn-block" data-action="switch-scan">
                        Switch to Camera Scan
                    </button>
                </div>
            </div>
        </div>

        {{-- Mode 3: Verification Result View --}}
        <div id="scannerResultView" style="display:none;width:100%;">
            <div class="sf-card" style="padding:24px 20px;text-align:center;">
                <div id="resultStatusIcon" style="width:60px;height:60px;border-radius:16px;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;font-size:28px;"></div>
                <h2 id="resultTitle" style="margin:0 0 6px;font-size:18px;font-weight:700;color:var(--sf-text);"></h2>
                <p id="resultSubtitle" style="margin:0 0 16px;font-size:12.5px;color:var(--sf-text-secondary);"></p>

                <div id="resultDetailsBox" style="text-align:left;padding:12px 14px;border-radius:11px;background:var(--sf-surface-2);border:1px solid var(--sf-border);margin-bottom:14px;display:flex;flex-direction:column;gap:8px;font-size:12.5px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="color:var(--sf-text-muted);">Status</span>
                        <span id="resultBadge" class="sf-pill"></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                        <span style="color:var(--sf-text-muted);">Subject</span>
                        <strong id="resultSubject" style="color:var(--sf-text);text-align:right;"></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                        <span style="color:var(--sf-text-muted);">Instructor</span>
                        <span id="resultInstructor" style="color:var(--sf-text-soft);text-align:right;"></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                        <span style="color:var(--sf-text-muted);">Section</span>
                        <span id="resultSection" style="color:var(--sf-text-soft);"></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                        <span style="color:var(--sf-text-muted);">Recorded At</span>
                        <span id="resultTimestamp" class="sf-mono" style="color:var(--sf-rose);"></span>
                    </div>
                </div>

                <div id="resultAutoCloseNotice" style="display:none;margin-bottom:14px;">
                    <div style="font-size:12px;color:var(--sf-emerald);margin-bottom:6px;">
                        Redirecting in <strong id="autoCloseCountdown">3</strong>s...
                    </div>
                    <div class="sf-progress-track">
                        <div id="autoCloseProgressFill" class="sf-progress-fill" style="width:100%;"></div>
                    </div>
                </div>

                <div style="display:flex;gap:8px;">
                    <button type="button" id="resultRetryBtn" data-action="result-retry" class="sf-btn sf-btn-secondary" style="flex:1;display:none;">Try Again</button>
                    <button type="button" id="resultDoneBtn" data-action="result-done" class="sf-btn sf-btn-primary" style="flex:1;">Done</button>
                </div>
            </div>
        </div>
    </div>

    <div style="font-size:11px;color:var(--sf-text-muted);text-align:center;">
        Protected by campus geofencing &amp; bound device verification
    </div>
</div>

{{-- Outside Classroom Range Popup Modal --}}
<div id="outsideRangePopupModal" class="sf-modal-backdrop" style="display:none;z-index:1400;" role="dialog" aria-modal="true" aria-labelledby="outsideRangeTitle">
    <div class="sf-modal-card">
        <div class="sf-modal-head">
            <div>
                <span class="sf-pill is-danger" style="margin-bottom:6px;">Geofence Boundary Exceeded</span>
                <h3 id="outsideRangeTitle">Outside Classroom Range</h3>
            </div>
            <button type="button" class="sf-modal-close" data-action="close-outside-popup" aria-label="Close dialog">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="sf-modal-body" style="display:flex;flex-direction:column;gap:12px;">
            <p id="outsideRangeMessage" style="margin:0;font-size:12.5px;color:var(--sf-text-secondary);line-height:1.5;">
                You are too far from the classroom to record attendance. You must be physically inside the room during class.
            </p>
            <div class="sf-drawer-info-grid">
                <div class="sf-info-tile">
                    <small>Your Distance</small>
                    <strong id="outsideRangeDetectedDist" class="sf-mono" style="color:var(--sf-danger);">--m</strong>
                    <span>Outside boundary</span>
                </div>
                <div class="sf-info-tile">
                    <small>Allowed Radius</small>
                    <strong id="outsideRangeAllowedRadius" class="sf-mono" style="color:var(--sf-amber-text);">50m</strong>
                    <span>Maximum limit</span>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:4px;">
                <button type="button" class="sf-btn sf-btn-primary sf-btn-block" data-action="outside-retry-scan">Scan QR Code Again</button>
                <button type="button" class="sf-btn sf-btn-secondary sf-btn-block" data-action="outside-enter-code">Enter 6-Digit PIN</button>
                <button type="button" class="sf-btn sf-btn-ghost sf-btn-block" data-action="close-outside-popup">Dismiss</button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}" src="{{ asset('js/html5-qrcode.min.js') }}?v={{ filemtime(public_path('js/html5-qrcode.min.js')) }}"></script>
<script nonce="{{ csp_nonce() }}">
let html5QrScanner = null;
let isScannerStarting = false;
let isScannerRunning = false;
let isScanInFlight = false;
let currentFacingMode = "environment";
let torchEnabled = false;
let studentGeoCoords = null;
let studentGeoTimestamp = 0;
let studentGeoSamples = [];
let studentGeoPromise = null;
let studentGeoRequestSerial = 0;
let studentLocationWatchId = null;
let currentScannerMode = 'scan';
let autoCloseTimer = null;
let autoCloseCountdownInterval = null;
const studentScannerPageUrl = @json(route('mobile.scan'));
const studentDashboardUrl = @json(route('home'));
const isDedicatedStudentScannerPage = @json(request()->routeIs('mobile.scan'));

function syncPinBoxesFromBacking() {
    const backing = document.getElementById('directSessionCodeInput');
    const clean = (backing?.value || '').replace(/[^0-9A-Za-z]/g, '').toUpperCase().slice(0, 6);
    for (let i = 0; i < 6; i++) {
        const box = document.getElementById('sfPinBox' + i);
        if (box) box.value = clean[i] || '';
    }
}

function syncBackingFromPinBoxes() {
    const backing = document.getElementById('directSessionCodeInput');
    if (!backing) return;
    let combined = '';
    for (let i = 0; i < 6; i++) {
        const box = document.getElementById('sfPinBox' + i);
        combined += (box?.value || '').replace(/[^0-9A-Za-z]/g, '').toUpperCase();
    }
    backing.value = combined.length > 3 ? (combined.slice(0, 3) + ' ' + combined.slice(3, 6)) : combined;
}

function updateScannerGpsStatus(state, message) {
    document.querySelectorAll('#scannerGpsBadge, #scannerCodeGpsBadge').forEach(badge => {
        badge.setAttribute('data-gps-state', state);
        badge.className = 'sf-pill ' + (state === 'ready' ? 'is-emerald' : (state === 'error' ? 'is-danger' : 'is-amber'));
        const textEl = badge.querySelector('.scanner-gps-text');
        if (textEl) textEl.textContent = message;
    });
}

function startContinuousLocationWatch() {
    if (!navigator.geolocation) {
        updateScannerGpsStatus('error', 'Location unsupported on this browser');
        return;
    }
    if (studentLocationWatchId !== null) return;
    updateScannerGpsStatus('searching', 'Acquiring GPS location...');
    studentLocationWatchId = navigator.geolocation.watchPosition(
        pos => {
            if (!pos || !pos.coords) return;
            const acc = pos.coords.accuracy || 0;
            const fixTs = Number(pos.timestamp);
            if (!Number.isFinite(acc) || acc <= 0 || !Number.isFinite(fixTs)) return;

            if (window.GpsSampler) {
                const merged = window.GpsSampler.mergeContinuousSamples(studentGeoSamples, pos, {
                    windowMs: 15000, maxAcceptableAccuracy: 50, maxBufferSize: 5,
                });
                studentGeoSamples = merged.buffer;
                const candidate = merged.estimate;
                if (candidate && window.GpsSampler.shouldReplaceContinuousFix(studentGeoCoords, candidate, {
                    staleAfterMs: 15000, maxAcceptableAccuracy: 50,
                })) {
                    studentGeoCoords = { lat: candidate.lat, lng: candidate.lng, acc: candidate.acc, timestamp: candidate.timestamp };
                    studentGeoTimestamp = candidate.timestamp;
                }
            } else if (!studentGeoCoords || acc <= (studentGeoCoords.acc || Infinity) || (Date.now() - studentGeoTimestamp > 15000 && acc <= 50)) {
                studentGeoCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude, acc: acc, timestamp: fixTs };
                studentGeoTimestamp = fixTs;
            }

            if (studentGeoCoords && studentGeoCoords.acc <= 50) {
                updateScannerGpsStatus('ready', `Location ready (±${Math.round(studentGeoCoords.acc)}m)`);
            } else {
                updateScannerGpsStatus('searching', `Refining GPS (±${Math.round(acc)}m)...`);
            }
        },
        err => {
            if (err && err.code === 1) updateScannerGpsStatus('error', 'Enable location permission to check in');
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

function stopContinuousLocationWatch() {
    if (studentLocationWatchId !== null && navigator.geolocation) {
        navigator.geolocation.clearWatch(studentLocationWatchId);
        studentLocationWatchId = null;
    }
}

function refreshStudentLocation(force = false) {
    if (!navigator.geolocation || !window.GpsSampler) return Promise.resolve(null);
    const now = Date.now();
    if (!force && studentGeoCoords && (now - studentGeoTimestamp < 5000) && (studentGeoCoords.acc <= 50)) {
        return Promise.resolve(studentGeoCoords);
    }
    if (studentGeoPromise && !force) return studentGeoPromise;

    const requestSerial = ++studentGeoRequestSerial;
    studentGeoPromise = window.GpsSampler.capturePosition({
        timeoutMs: 14000, settleMs: 3200, targetAccuracy: 15, maxAcceptableAccuracy: 50,
        minClusterSamples: 2, allowSingleFallback: false, requireClusterIfDisagreeing: true, maxSamples: 5,
        onSample: ({ estimate, bestRawAccuracy, sampleCount }) => {
            if (requestSerial !== studentGeoRequestSerial) return;
            if (estimate) updateScannerGpsStatus('searching', `Locking GPS (±${Math.round(estimate.acc)}m${sampleCount > 1 ? `, ${sampleCount} fixes` : ''})...`);
            else if (Number.isFinite(bestRawAccuracy)) updateScannerGpsStatus('searching', `Refining GPS (±${Math.round(bestRawAccuracy)}m)...`);
        },
    }).then((result) => {
        if (requestSerial !== studentGeoRequestSerial) return null;
        studentGeoPromise = null;
        if (result.ok && result.fix) {
            const coords = { lat: result.fix.lat, lng: result.fix.lng, acc: result.fix.acc, timestamp: result.fix.timestamp };
            studentGeoCoords = coords;
            studentGeoTimestamp = coords.timestamp;
            studentGeoSamples = [coords];
            updateScannerGpsStatus('ready', `Location ready (±${Math.round(coords.acc)}m)`);
            return coords;
        }
        studentGeoCoords = null;
        studentGeoTimestamp = 0;
        const accMsg = Number.isFinite(result.bestRawAccuracy)
            ? `GPS signal weak (±${Math.round(result.bestRawAccuracy)}m) — move near window`
            : 'GPS unavailable — move near window';
        updateScannerGpsStatus('error', accMsg);
        return null;
    });
    return studentGeoPromise;
}

if (navigator.geolocation) refreshStudentLocation(true);

function switchScannerMode(mode) {
    currentScannerMode = mode;
    const tabScan = document.getElementById('tabScanMode');
    const tabCode = document.getElementById('tabCodeMode');
    const scanView = document.getElementById('scannerActiveView');
    const codeView = document.getElementById('scannerCodeView');
    const resultView = document.getElementById('scannerResultView');
    const torchBtn = document.getElementById('torchCameraBtn');
    const pillLabel = document.getElementById('sfScannerPillLabel');
    const codeBtn = document.getElementById('codeSubmitBtn');
    const hint = document.getElementById('codeEntryHint');

    tabScan?.setAttribute('aria-pressed', String(mode === 'scan'));
    tabCode?.setAttribute('aria-pressed', String(mode === 'code'));
    tabScan?.classList.toggle('active', mode === 'scan');
    tabCode?.classList.toggle('active', mode === 'code');
    if (resultView) resultView.style.display = 'none';

    if (mode === 'scan') {
        if (pillLabel) pillLabel.textContent = 'LIVE QR SCANNER';
        if (scanView) scanView.style.display = 'flex';
        if (codeView) codeView.style.display = 'none';
        startHtml5Scanner();
    } else {
        if (pillLabel) pillLabel.textContent = 'SESSION PIN ENTRY';
        if (scanView) scanView.style.display = 'none';
        if (codeView) codeView.style.display = 'block';
        if (torchBtn) torchBtn.style.display = 'none';
        if (codeBtn) { codeBtn.disabled = false; codeBtn.textContent = 'Verify & Record Attendance'; }
        if (hint) { hint.style.color = 'var(--sf-text-muted)'; hint.textContent = '6-digit session PIN or copied QR token'; }
        safeStopScanner();
        setTimeout(() => document.getElementById('sfPinBox0')?.focus(), 60);
    }
    window.triggerHaptic?.('light');
}

function handleCopiedCodeInput(rawText) {
    if (!rawText) return;
    const text = String(rawText).trim();
    if (!text) return;
    if (currentScannerMode !== 'code') switchScannerMode('code');

    const input = document.getElementById('directSessionCodeInput');
    const hint = document.getElementById('codeEntryHint');
    const parsed = extractQrToken(text);

    if (parsed.code && parsed.code.length === 6) {
        const c = parsed.code.toUpperCase();
        if (input) input.value = c.substring(0, 3) + ' ' + c.substring(3);
        syncPinBoxesFromBacking();
        if (hint) { hint.style.color = 'var(--sf-emerald)'; hint.textContent = 'Copied code accepted: ' + c; }
        window.triggerHaptic?.('success');
        setTimeout(() => submitDirectCode(), 250);
        return;
    }

    if (parsed.token || (parsed.code && parsed.code.length >= 4)) {
        if (input) input.value = parsed.token || parsed.code;
        if (hint) { hint.style.color = 'var(--sf-emerald)'; hint.textContent = 'Copied attendance token detected!'; }
        window.triggerHaptic?.('success');
        setTimeout(() => onQrScanSuccess(parsed.token || parsed.code, 'code'), 250);
        return;
    }

    if (hint) { hint.style.color = 'var(--sf-amber-text)'; hint.textContent = 'Check that your copied text contains a valid 6-digit PIN.'; }
}

async function pasteCopiedCodeFromClipboard() {
    let text = '';
    try {
        if (navigator.clipboard && navigator.clipboard.readText) {
            text = await navigator.clipboard.readText();
        }
    } catch (err) {}
    if (text) {
        handleCopiedCodeInput(text);
    } else {
        switchScannerMode('code');
        document.getElementById('sfPinBox0')?.focus();
        const hint = document.getElementById('codeEntryHint');
        if (hint) { hint.style.color = 'var(--sf-amber-text)'; hint.textContent = 'Paste your copied code directly (Ctrl+V or long-press).'; }
    }
}

function submitDirectCode() {
    if (isScanInFlight) return;
    const input = document.getElementById('directSessionCodeInput');
    const rawVal = (input?.value || '').trim();
    const hint = document.getElementById('codeEntryHint');
    const parsed = extractQrToken(rawVal);
    const codeVal = parsed.code || (parsed.token ? '' : rawVal.replace(/[^0-9A-Za-z]/g, ''));
    const tokenVal = parsed.token;

    if (!codeVal && !tokenVal) {
        if (hint) { hint.style.color = 'var(--sf-danger)'; hint.textContent = 'Please enter the 6-digit attendance PIN.'; }
        document.getElementById('sfPinBox0')?.focus();
        window.triggerHaptic?.('error');
        return;
    }

    if (hint) { hint.style.color = 'var(--sf-rose)'; hint.textContent = 'Verifying code...'; }
    const codeBtn = document.getElementById('codeSubmitBtn');
    if (codeBtn) { codeBtn.disabled = true; codeBtn.textContent = 'Verifying PIN...'; }
    onQrScanSuccess(tokenVal || codeVal, 'code');
}

function openStudentScanner(initialMode = 'scan', autoPaste = false) {
    if (!isDedicatedStudentScannerPage) {
        const scanUrl = new URL(studentScannerPageUrl, window.location.origin);
        scanUrl.searchParams.set(initialMode === 'code' ? 'open_code' : 'open_scanner', '1');
        if (autoPaste) scanUrl.searchParams.set('paste', '1');
        window.location.assign(scanUrl.toString());
        return;
    }
    const modal = document.getElementById('studentScannerModal');
    if (!modal) return;
    startContinuousLocationWatch();
    refreshStudentLocation(true);
    currentScannerMode = initialMode;
    modal.style.display = 'flex';
    resetScannerView();
    switchScannerMode(initialMode);
    if (autoPaste) setTimeout(() => pasteCopiedCodeFromClipboard(), 120);
}

let isNavigatingAwayFromScanner = false;

function stopCameraStreamsNow() {
    isScannerRunning = false;
    isScannerStarting = false;
    const reader = document.getElementById('reader');
    if (reader) {
        reader.querySelectorAll('video').forEach(v => {
            if (v.srcObject) {
                try { v.srcObject.getTracks().forEach(t => t.stop()); } catch (e) {}
                v.srcObject = null;
            }
        });
    }
}

async function safeStopScanner() {
    if (html5QrScanner) {
        try {
            const state = typeof html5QrScanner.getState === 'function' ? html5QrScanner.getState() : null;
            if (state === 2 || state === 3 || isScannerRunning) await html5QrScanner.stop();
        } catch (e) {}
    }
    stopCameraStreamsNow();
    const loadingOverlay = document.getElementById('scannerLoadingOverlay');
    if (loadingOverlay) loadingOverlay.style.display = 'none';
    const laser = document.getElementById('scannerLaser');
    if (laser) laser.style.display = 'none';
    const torchBtn = document.getElementById('torchCameraBtn');
    torchEnabled = false;
    if (torchBtn) { torchBtn.style.display = 'none'; torchBtn.setAttribute('aria-pressed', 'false'); }
}

async function safeClearScanner() {
    await safeStopScanner();
    if (html5QrScanner) {
        try { await html5QrScanner.clear(); } catch (e) {}
        html5QrScanner = null;
    }
    const reader = document.getElementById('reader');
    if (reader) reader.innerHTML = '';
}

async function waitForHtml5Qrcode(maxWaitMs = 3000) {
    const startTime = Date.now();
    while (typeof Html5Qrcode === 'undefined') {
        if (Date.now() - startTime > maxWaitMs) return false;
        await new Promise(r => setTimeout(r, 100));
    }
    return true;
}

async function startHtml5Scanner() {
    if (currentScannerMode !== 'scan') return;
    if (isScannerStarting || isScannerRunning) {
        const activeVideo = document.querySelector('#reader video');
        if (isScannerRunning && activeVideo && !activeVideo.paused && !activeVideo.ended && activeVideo.readyState >= 2) return;
        isScannerStarting = false;
        isScannerRunning = false;
    }

    hideCameraError();
    const loadingOverlay = document.getElementById('scannerLoadingOverlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
        showCameraError("unsupported", "Camera access requires HTTPS or localhost. Switch to 6-digit PIN entry.");
        return;
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
        showCameraError("unsupported", "Camera scanning is unavailable on this browser. Use the 6-digit PIN instead.");
        return;
    }
    const libLoaded = await waitForHtml5Qrcode();
    if (!libLoaded) {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
        showCameraError("init_failed", "Unable to initialize the QR scanner. Please retry.");
        return;
    }
    if (currentScannerMode !== 'scan') {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
        return;
    }

    isScannerStarting = true;
    try {
        await safeClearScanner();
        if (currentScannerMode !== 'scan') { isScannerStarting = false; return; }
        if (loadingOverlay) loadingOverlay.style.display = 'flex';
        const readerContainer = document.getElementById('reader');
        if (readerContainer) readerContainer.innerHTML = '';
        html5QrScanner = new Html5Qrcode("reader");

        const qrConfig = {
            fps: 15,
            qrbox: (w, h) => {
                const s = Math.max(Math.floor(Math.min(w, h) * 0.78), 180);
                return { width: s, height: s };
            },
            experimentalFeatures: { useBarCodeDetectorIfSupported: true }
        };

        let started = false;
        let lastError = null;
        try {
            await html5QrScanner.start({ facingMode: currentFacingMode }, qrConfig, onQrScanSuccess);
            started = true;
        } catch (err1) {
            lastError = err1;
            if (err1.name === 'NotAllowedError' || err1.name === 'PermissionDeniedError') throw err1;
            try { await html5QrScanner.clear(); } catch (e) {}
            html5QrScanner = new Html5Qrcode("reader");
        }

        if (!started && currentScannerMode === 'scan') {
            try {
                const cameras = await Html5Qrcode.getCameras();
                if (cameras && cameras.length > 0) {
                    let sel = cameras[0];
                    if (currentFacingMode === 'environment') {
                        const backCam = cameras.find(c => /back|rear|environment|main|world/i.test(c.label));
                        if (backCam) sel = backCam;
                    }
                    await html5QrScanner.start(sel.id, qrConfig, onQrScanSuccess);
                    started = true;
                }
            } catch (err2) {
                lastError = err2;
                if (err2.name === 'NotAllowedError' || err2.name === 'PermissionDeniedError') throw err2;
                try { await html5QrScanner.clear(); } catch (e) {}
                html5QrScanner = new Html5Qrcode("reader");
            }
        }

        if (!started && currentScannerMode === 'scan') {
            await html5QrScanner.start({ facingMode: { ideal: currentFacingMode } }, qrConfig, onQrScanSuccess);
            started = true;
        }

        if (!started) throw lastError || new Error("Unable to start scanner");
        if (currentScannerMode !== 'scan') { safeStopScanner(); return; }
        onScannerSuccessfullyStarted();
    } catch (err) {
        isScannerStarting = false;
        isScannerRunning = false;
        if (loadingOverlay) loadingOverlay.style.display = 'none';
        if (currentScannerMode === 'scan') {
            const errName = err ? (err.name || '') : '';
            if (errName === 'NotAllowedError' || errName === 'PermissionDeniedError') showCameraError('permission_denied');
            else if (errName === 'NotFoundError' || errName === 'DevicesNotFoundError') showCameraError('camera_unavailable', 'No camera detected. Switch to 6-digit PIN entry.');
            else showCameraError('camera_unavailable', 'Unable to access your camera. You can enter the 6-digit PIN instead.');
        }
    }
}

function onScannerSuccessfullyStarted() {
    if (currentScannerMode !== 'scan') { safeStopScanner(); return; }
    isScannerRunning = true;
    isScannerStarting = false;
    const loadingOverlay = document.getElementById('scannerLoadingOverlay');
    if (loadingOverlay) loadingOverlay.style.display = 'none';
    hideCameraError();
    const laser = document.getElementById('scannerLaser');
    if (laser) laser.style.display = 'block';
    const torchBtn = document.getElementById('torchCameraBtn');
    if (torchBtn && currentFacingMode === 'environment') torchBtn.style.display = 'inline-flex';
}

function showCameraError(type = 'init_failed', customMsg = null) {
    if (currentScannerMode !== 'scan') return;
    const notice = document.getElementById('scannerFallbackNotice');
    const text = document.getElementById('scannerFallbackText');
    const title = document.getElementById('fallbackTitle');
    const retryBtn = document.getElementById('retryCameraBtn');
    const laser = document.getElementById('scannerLaser');
    const loadingOverlay = document.getElementById('scannerLoadingOverlay');
    if (loadingOverlay) loadingOverlay.style.display = 'none';
    if (laser) laser.style.display = 'none';
    if (title) title.textContent = type === 'permission_denied' ? 'Camera Permission Denied' : (type === 'unsupported' ? 'Camera Not Supported' : 'Camera Unavailable');
    if (text) text.textContent = customMsg || 'Please allow camera access in your browser settings or enter the 6-digit PIN instead.';
    if (retryBtn) retryBtn.style.display = type === 'unsupported' ? 'none' : 'inline-flex';
    if (notice) notice.style.display = 'flex';
}

function hideCameraError() {
    const notice = document.getElementById('scannerFallbackNotice');
    if (notice) notice.style.display = 'none';
}

function requestCameraAgain() {
    isScannerStarting = false;
    isScannerRunning = false;
    hideCameraError();
    startHtml5Scanner();
}

async function toggleTorch() {
    if (!html5QrScanner || !isScannerRunning) return;
    const torchBtn = document.getElementById('torchCameraBtn');
    const nextEnabled = !torchEnabled;
    try {
        await html5QrScanner.applyVideoConstraints({ advanced: [{ torch: nextEnabled }] });
        torchEnabled = nextEnabled;
        torchBtn?.setAttribute('aria-pressed', String(torchEnabled));
    } catch (e) {
        window.showToast?.('Flashlight is not supported on this device.', 'info', 2500);
    }
}

function closeStudentScanner(returnToDashboard = true) {
    clearAutoCloseTimer();
    stopContinuousLocationWatch();
    isScanInFlight = false;
    if (isDedicatedStudentScannerPage && returnToDashboard) {
        isNavigatingAwayFromScanner = true;
        stopCameraStreamsNow();
        window.sfStartNavProgress?.();
        window.location.replace(studentDashboardUrl);
        return;
    }
    const modal = document.getElementById('studentScannerModal');
    if (modal) modal.style.display = 'none';
    safeClearScanner();
}

function clearAutoCloseTimer() {
    if (autoCloseTimer) { clearTimeout(autoCloseTimer); autoCloseTimer = null; }
    if (autoCloseCountdownInterval) { clearInterval(autoCloseCountdownInterval); autoCloseCountdownInterval = null; }
}

function resetScannerView() {
    clearAutoCloseTimer();
    isScanInFlight = false;
    const resultView = document.getElementById('scannerResultView');
    const overlay = document.getElementById('scannerProcessingOverlay');
    const codeInput = document.getElementById('directSessionCodeInput');
    const autoCloseNotice = document.getElementById('resultAutoCloseNotice');
    if (resultView) resultView.style.display = 'none';
    if (overlay) overlay.style.display = 'none';
    if (autoCloseNotice) autoCloseNotice.style.display = 'none';
    if (codeInput) codeInput.value = '';
    syncPinBoxesFromBacking();
}

function extractQrToken(raw) {
    if (!raw) return { token: '', code: '' };
    let str = typeof raw === 'string' ? raw.trim() : String(raw).trim();
    if ((str.startsWith('{') && str.endsWith('}')) || (str.startsWith('[') && str.endsWith(']'))) {
        try {
            const parsed = JSON.parse(str);
            if (parsed && typeof parsed === 'object') {
                let token = parsed.token || parsed.session_token || '';
                let code = parsed.code || parsed.session_code || '';
                let url = parsed.scan_url || parsed.url || '';
                if (!token && url) {
                    const extracted = extractQrToken(url);
                    token = extracted.token;
                    code = code || extracted.code;
                }
                return { token: String(token).trim(), code: String(code).trim() };
            }
        } catch (e) {}
    }

    let token = '';
    let code = '';
    const urlMatch = str.match(/https?:\/\/[^\s'"<>]+/);
    const candidateUrl = urlMatch ? urlMatch[0] : (str.startsWith('http://') || str.startsWith('https://') ? str : null);

    if (str.includes('/qr/scan/')) {
        const parts = str.split('/qr/scan/');
        let tail = parts[1] ? parts[1].split('?')[0].split('#')[0] : '';
        token = decodeURIComponent(tail).replace(/['".,;:)\/]+$/, '').trim();
    } else if (candidateUrl) {
        try {
            const urlObj = new URL(candidateUrl);
            if (urlObj.searchParams.has('token')) token = urlObj.searchParams.get('token').trim();
            if (urlObj.searchParams.has('code') || urlObj.searchParams.has('session_code')) {
                code = (urlObj.searchParams.get('code') || urlObj.searchParams.get('session_code')).trim();
            }
            if (!token) {
                const pathParts = urlObj.pathname.split('/').filter(Boolean);
                if (pathParts.length > 0) token = decodeURIComponent(pathParts[pathParts.length - 1]).replace(/['".,;:)\/]+$/, '').trim();
            }
        } catch (e) {}
    }

    if (!token && !code) {
        const pinMatch = str.match(/(?:code|pin|session|attendance)[:\s#\-_]*([0-9]{3})[\s\-_]*([0-9]{3})\b/i) ||
                         str.match(/(?:code|pin|session|attendance)[:\s#\-_]*([0-9]{6})\b/i) ||
                         str.match(/(?<![0-9])([0-9]{3})[\s\-_]+([0-9]{3})(?![0-9])/) ||
                         str.match(/(?<![0-9])([0-9]{6})(?![0-9])/);
        if (pinMatch) {
            code = pinMatch[1] ? (pinMatch[2] ? pinMatch[1] + pinMatch[2] : pinMatch[1]) : pinMatch[0].replace(/\D/g, '');
        } else {
            const cleanAlphaNum = str.replace(/[^0-9A-Za-z]/g, '');
            if (cleanAlphaNum.length >= 4 && cleanAlphaNum.length <= 10 && !str.includes('/')) {
                const endDigits = cleanAlphaNum.match(/[0-9]{6}$/);
                code = endDigits ? endDigits[0] : cleanAlphaNum;
            } else {
                token = str;
            }
        }
    }
    return { token: token.trim(), code: code.trim() };
}

async function onQrScanSuccess(decodedText, method = 'qr') {
    if (isScanInFlight) return;
    isScanInFlight = true;
    safeStopScanner();

    const overlay = document.getElementById('scannerProcessingOverlay');
    const codeBtn = document.getElementById('codeSubmitBtn');
    const hint = document.getElementById('codeEntryHint');

    if (method === 'code' || currentScannerMode === 'code') {
        if (codeBtn) { codeBtn.disabled = true; codeBtn.textContent = 'Verifying PIN...'; }
        if (hint) { hint.style.color = 'var(--sf-rose)'; hint.textContent = 'Verifying code...'; }
    } else {
        if (overlay) overlay.style.display = 'flex';
    }
    window.triggerHaptic?.('medium');

    const parsedData = extractQrToken(decodedText);
    const resolvedMethod = (method === 'code' || currentScannerMode === 'code' || (parsedData.code && !parsedData.token)) ? 'code' : 'qr';
    const processingText = document.querySelector('#scannerProcessingOverlay .processing-sub');
    if (processingText) processingText.textContent = 'Checking location...';
    if (hint) hint.textContent = 'Checking location...';

    let scanLocation = null;
    if (@json(app(\App\Services\FeatureFlagService::class)->enabled('feature.gps_verification'))) {
        try { scanLocation = await refreshStudentLocation(false); } catch (e) {}
        if (!scanLocation) {
            if (overlay) overlay.style.display = 'none';
            renderScanError({ error_type: 'unreliable_gps', message: 'Getting accurate location failed. Enable high-accuracy GPS, move near a window, and retry.' });
            return;
        }
    }

    const devKey = (typeof window.getOrCreateDeviceKey === 'function')
        ? window.getOrCreateDeviceKey()
        : (localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || '');

    const payload = {
        token: parsedData.token || (resolvedMethod === 'code' ? parsedData.code : ''),
        code: parsedData.code || parsedData.token || '',
        method: resolvedMethod,
        device_key: devKey,
        device_fingerprint: devKey,
        latitude: scanLocation?.lat ?? null,
        longitude: scanLocation?.lng ?? null,
        accuracy: scanLocation?.acc ?? null,
        location_timestamp_ms: scanLocation?.timestamp ?? null
    };

    try {
        const response = await fetch('{{ route("qr.scan.process") }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Device-Key': devKey,
                'X-Device-Fingerprint': devKey
            },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (overlay) overlay.style.display = 'none';

        if (response.ok && data.verification_url) {
            const destination = new URL(data.verification_url, window.location.origin);
            if (destination.origin === window.location.origin && destination.pathname.startsWith('/qr/scan/')) {
                window.location.assign(destination.href);
                return;
            }
            renderScanError({ message: 'Could not open identity verification. Please scan again.' });
            return;
        }
        if (response.ok && data.success) renderScanSuccess(data);
        else renderScanError(data);
    } catch (error) {
        if (overlay) overlay.style.display = 'none';
        renderScanError({ message: 'Network connection failed. Please check your connection and try again.' });
    }
}

function renderScanSuccess(data) {
    clearAutoCloseTimer();
    const codeBtn = document.getElementById('codeSubmitBtn');
    if (codeBtn) { codeBtn.disabled = false; codeBtn.textContent = 'Verify & Record Attendance'; }

    document.getElementById('scannerActiveView').style.display = 'none';
    document.getElementById('scannerCodeView').style.display = 'none';
    document.getElementById('scannerResultView').style.display = 'block';

    const iconBox = document.getElementById('resultStatusIcon');
    const badge = document.getElementById('resultBadge');
    const title = document.getElementById('resultTitle');
    const subtitle = document.getElementById('resultSubtitle');
    const retryBtn = document.getElementById('resultRetryBtn');
    const autoCloseNotice = document.getElementById('resultAutoCloseNotice');
    const detailsBox = document.getElementById('resultDetailsBox');
    if (detailsBox) detailsBox.style.display = 'flex';
    if (retryBtn) retryBtn.style.display = 'none';

    if (data.already_clocked_in) {
        iconBox.style.background = 'var(--sf-sky-bg)';
        iconBox.style.border = '1px solid var(--sf-sky-border)';
        iconBox.innerHTML = '<i class="bi bi-info-circle-fill" style="color:var(--sf-sky);"></i>';
        title.textContent = 'Already Recorded';
        subtitle.textContent = data.message || 'Attendance was already recorded for this session.';
        badge.className = 'sf-pill is-sky';
        badge.textContent = data.status || 'Present';
        if (autoCloseNotice) autoCloseNotice.style.display = 'none';
    } else {
        iconBox.style.background = 'var(--sf-emerald-bg)';
        iconBox.style.border = '1px solid var(--sf-emerald-border)';
        iconBox.innerHTML = '<i class="bi bi-check2-circle" style="color:var(--sf-emerald);"></i>';
        title.textContent = 'Attendance Recorded';
        subtitle.textContent = data.message || `Confirmed for ${data.subject || 'your class'}.`;
        badge.className = 'sf-pill ' + ((data.status || 'Present') === 'Present' ? 'is-emerald' : 'is-amber');
        badge.textContent = data.status || 'Present';
        window.sfConfettiBurst?.();
        window.triggerHaptic?.('success');

        if (autoCloseNotice) {
            autoCloseNotice.style.display = 'block';
            let secondsLeft = 3;
            const countEl = document.getElementById('autoCloseCountdown');
            const progressFill = document.getElementById('autoCloseProgressFill');
            if (countEl) countEl.textContent = secondsLeft;
            if (progressFill) progressFill.style.width = '100%';
            autoCloseCountdownInterval = setInterval(() => {
                secondsLeft--;
                if (countEl) countEl.textContent = Math.max(secondsLeft, 0);
                if (progressFill) progressFill.style.width = (Math.max(secondsLeft, 0) / 3 * 100) + '%';
                if (secondsLeft <= 0) clearInterval(autoCloseCountdownInterval);
            }, 1000);
            autoCloseTimer = setTimeout(() => finishScanAndRefresh(), 3200);
        }
    }

    window.studentPresenceGuardian?.syncActiveSession(true);
    document.getElementById('resultSubject').textContent = (data.subject || 'Subject') + (data.subject_code ? ' (' + data.subject_code + ')' : '');
    document.getElementById('resultInstructor').textContent = data.instructor || 'Instructor';
    document.getElementById('resultSection').textContent = data.section || 'Regular';
    document.getElementById('resultTimestamp').textContent = (data.date || '') + (data.time ? ' · ' + data.time : '');
}

function renderScanError(data) {
    clearAutoCloseTimer();
    isScanInFlight = false;
    const codeBtn = document.getElementById('codeSubmitBtn');
    if (codeBtn) { codeBtn.disabled = false; codeBtn.textContent = 'Verify & Record Attendance'; }

    document.getElementById('scannerActiveView').style.display = 'none';
    document.getElementById('scannerCodeView').style.display = 'none';
    document.getElementById('scannerResultView').style.display = 'block';

    const iconBox = document.getElementById('resultStatusIcon');
    const title = document.getElementById('resultTitle');
    const subtitle = document.getElementById('resultSubtitle');
    const retryBtn = document.getElementById('resultRetryBtn');
    const autoCloseNotice = document.getElementById('resultAutoCloseNotice');
    if (autoCloseNotice) autoCloseNotice.style.display = 'none';

    iconBox.style.background = 'var(--sf-danger-bg)';
    iconBox.style.border = '1px solid var(--sf-danger-border)';
    iconBox.innerHTML = '<i class="bi bi-x-circle-fill" style="color:var(--sf-danger);"></i>';

    const errType = data.error_type || '';
    const errDetail = data.error_detail || '';
    const titleMap = {
        schedule_mismatch: 'Schedule Mismatch',
        device_mismatch: 'Device Not Recognized',
        proxy_device_detected: 'Proxy Attendance Detected',
        session_expired: 'Session Expired',
        session_inactive: 'Session Ended',
        session_closed: 'Session Ended',
        invalid_code: 'Invalid PIN Code',
        invalid_token: 'Invalid QR Code',
        location_required: 'Location Required',
        unreliable_gps: 'Weak GPS Signal',
        outside_classroom: 'Outside Classroom Range',
    };
    title.textContent = titleMap[errDetail] || titleMap[errType] || 'Unable to Record Attendance';
    subtitle.textContent = data.message || 'Please check with your instructor and try again.';
    document.getElementById('resultDetailsBox').style.display = 'none';
    if (retryBtn) retryBtn.style.display = 'inline-flex';
    if (errType === 'outside_classroom') showOutsideRangePopup(data);
    window.triggerHaptic?.('error');
}

function showOutsideRangePopup(data) {
    const modal = document.getElementById('outsideRangePopupModal');
    if (!modal) return;
    const rawDist = data.distance ?? data.dist ?? null;
    const rawRadius = data.radius ?? data.limit ?? 50;
    const dist = rawDist !== null && !isNaN(Number(rawDist)) ? Math.round(Number(rawDist)) : null;
    const radius = !isNaN(Number(rawRadius)) ? Math.round(Number(rawRadius)) : 50;
    document.getElementById('outsideRangeDetectedDist').textContent = dist !== null ? (dist.toLocaleString() + 'm') : 'Out of range';
    document.getElementById('outsideRangeAllowedRadius').textContent = radius + 'm';
    if (data.message) document.getElementById('outsideRangeMessage').textContent = data.message;
    modal.style.display = 'flex';
}

function closeOutsideRangePopup() {
    const modal = document.getElementById('outsideRangePopupModal');
    if (modal) modal.style.display = 'none';
}

function retryCurrentScanMode() {
    refreshStudentLocation(true);
    resetScannerView();
    switchScannerMode(currentScannerMode === 'code' ? 'code' : 'scan');
}

function finishScanAndRefresh() {
    clearAutoCloseTimer();
    if (window.location.pathname.includes('/mobile/scan')) {
        closeStudentScanner(false);
        window.location.href = '{{ route("student.calendar") }}#attendance-records';
    } else {
        closeStudentScanner();
        window.location.reload();
    }
}

window.openStudentScanner = openStudentScanner;
window.closeStudentScanner = closeStudentScanner;
window.switchScannerMode = switchScannerMode;
window.submitDirectCode = submitDirectCode;
window.resetScannerView = resetScannerView;
window.retryCurrentScanMode = retryCurrentScanMode;
window.finishScanAndRefresh = finishScanAndRefresh;

document.addEventListener('DOMContentLoaded', () => {
    // Wire 6-box OTP PIN inputs
    const pinBoxes = Array.from(document.querySelectorAll('.sf-otp-box'));
    pinBoxes.forEach((box, idx) => {
        box.addEventListener('input', (e) => {
            const val = box.value.replace(/[^0-9A-Za-z]/g, '').toUpperCase();
            box.value = val.slice(-1);
            syncBackingFromPinBoxes();
            if (box.value && idx < pinBoxes.length - 1) pinBoxes[idx + 1].focus();
        });
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !box.value && idx > 0) {
                pinBoxes[idx - 1].focus();
                pinBoxes[idx - 1].value = '';
                syncBackingFromPinBoxes();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                submitDirectCode();
            }
        });
        box.addEventListener('paste', (e) => {
            const pasted = (e.clipboardData || window.clipboardData)?.getData('text') || '';
            if (pasted) {
                e.preventDefault();
                handleCopiedCodeInput(pasted);
            }
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (isDedicatedStudentScannerPage) {
        const mode = urlParams.get('open_code') === '1' ? 'code' : 'scan';
        openStudentScanner(mode, urlParams.get('paste') === '1');
    }

    function handleModalAction(e) {
        const trigger = e.target.closest('[data-action]');
        if (!trigger) return;
        const action = trigger.getAttribute('data-action');
        if (!action) return;
        e.stopPropagation();
        switch (action) {
            case 'switch-scan': switchScannerMode('scan'); break;
            case 'switch-code': switchScannerMode('code'); break;
            case 'paste-copied-code': pasteCopiedCodeFromClipboard(); break;
            case 'allow-camera': requestCameraAgain(); break;
            case 'submit-code': submitDirectCode(); break;
            case 'close-scanner': closeStudentScanner(); break;
            case 'toggle-torch': toggleTorch(); break;
            case 'result-done': finishScanAndRefresh(); break;
            case 'result-retry': retryCurrentScanMode(); break;
            case 'outside-retry-scan': closeOutsideRangePopup(); retryCurrentScanMode(); switchScannerMode('scan'); break;
            case 'outside-enter-code': closeOutsideRangePopup(); resetScannerView(); switchScannerMode('code'); break;
            case 'close-outside-popup': closeOutsideRangePopup(); break;
        }
    }

    document.getElementById('studentScannerModal')?.addEventListener('click', handleModalAction, true);
    document.getElementById('outsideRangePopupModal')?.addEventListener('click', handleModalAction, true);
    window.addEventListener('pagehide', () => stopCameraStreamsNow());
    window.addEventListener('beforeunload', () => {
        if (!isNavigatingAwayFromScanner) stopCameraStreamsNow();
    });
});
</script>
