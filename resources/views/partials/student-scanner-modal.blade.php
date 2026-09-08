<!-- Student QR & Code Scanner Modal Partial -->
<div id="studentScannerModal" class="scanner-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-label="Student Attendance Scanner">
    <div class="scanner-modal-card">
        
        <!-- Header & Top Floating Bar -->
        <div class="scanner-top-bar">
            <!-- Mobile: Mode Switcher Tabs (QR vs Code) -->
            <div class="scanner-mode-switcher" id="mobileModeSwitcher">
                <button type="button" id="tabScanMode" class="scanner-mode-tab active" onclick="switchScannerMode('scan')">
                    <i class="bi bi-qr-code-scan me-1"></i> Scan QR
                </button>
                <button type="button" id="tabCodeMode" class="scanner-mode-tab" onclick="switchScannerMode('code')">
                    <i class="bi bi-key-fill me-1"></i> Enter Code
                </button>
            </div>

            <!-- Desktop: Dedicated Header (Code Only) -->
            <div id="desktopScannerHeader" class="desktop-scanner-header" style="display: none; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 12px; background: rgba(207,164,111,0.16); color: #cfa46f; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; border: 1px solid rgba(207,164,111,0.3);">
                    <i class="bi bi-key-fill"></i>
                </div>
                <div>
                    <div style="font-size: 1.05rem; font-weight: 800; color: #f3e7cd; letter-spacing: -0.2px; line-height: 1.2;">Enter Attendance Code</div>
                    <div style="font-size: 0.72rem; color: #b39b82;">Manual attendance check-in</div>
                </div>
            </div>
            
            <div class="scanner-top-actions">
                <button type="button" id="torchCameraBtn" onclick="toggleTorch()" class="scanner-icon-btn" title="Toggle Flashlight" aria-label="Toggle Flashlight">
                    <i class="bi bi-lightning-charge"></i>
                </button>
                <button type="button" id="flipCameraBtn" onclick="toggleCameraFacing()" class="scanner-icon-btn" title="Flip Camera" aria-label="Flip Camera">
                    <i class="bi bi-camera-reverse"></i>
                </button>
                <button type="button" onclick="closeStudentScanner()" class="scanner-icon-btn close-btn" title="Close" aria-label="Close Scanner">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <!-- Mode 1: Camera Scanner View -->
        <div id="scannerActiveView" class="scanner-active-content">
            <div class="scanner-hero-heading">
                <h4 class="scanner-title">Scan Attendance QR</h4>
                <p class="scanner-sub">Align the teacher's classroom QR code within the frame</p>
            </div>

            <!-- Viewfinder Area with Glowing Corner Reticles -->
            <div id="scannerVideoContainer" class="scanner-viewfinder-wrapper">
                <div id="reader" class="scanner-reader-feed"></div>
                
                <!-- 4 Corner Reticle Accents -->
                <div class="reticle-corner top-left"></div>
                <div class="reticle-corner top-right"></div>
                <div class="reticle-corner bottom-left"></div>
                <div class="reticle-corner bottom-right"></div>

                <!-- Laser scanning beam -->
                <div id="scannerLaser" class="scanner-laser-line"></div>

                <!-- Guidance Pill -->
                <div id="scannerGuideBadge" class="scanner-guide-badge">
                    <i class="bi bi-viewfinder me-1"></i> Point at screen QR code
                </div>

                <!-- Processing Overlay -->
                <div id="scannerProcessingOverlay" class="scanner-processing-overlay" style="display: none;">
                    <div class="spinner-border text-warning mb-3" style="width: 3.2rem; height: 3.2rem; border-width: 3px;" role="status"></div>
                    <div class="processing-title">Recording Attendance...</div>
                    <div class="processing-sub">Verifying session & GPS proximity</div>
                </div>

                <!-- Fallback Notice (Permission Blocked / Unsupported) -->
                <div id="scannerFallbackNotice" class="scanner-fallback-box" style="display: none;">
                    <div class="fallback-icon-wrap" id="fallbackIconWrap">
                        <i class="bi bi-camera-video-off"></i>
                    </div>
                    <h5 class="fallback-title" id="fallbackTitle">Camera Inactive</h5>
                    <p id="scannerFallbackText" class="fallback-text">Please allow camera permissions or switch to Code entry.</p>
                    <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-warning" id="retryCameraBtn" onclick="requestCameraAgain()" style="border-radius: 12px; font-weight: 700; padding: 7px 16px;">
                            <i class="bi bi-arrow-repeat me-1"></i> Retry Camera
                        </button>
                        <button type="button" class="btn btn-sm btn-warning text-dark" onclick="switchScannerMode('code')" style="border-radius: 12px; font-weight: 700; padding: 7px 16px;">
                            <i class="bi bi-key-fill me-1"></i> Use Code
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick switch link to code -->
            <div class="mt-3 text-center">
                <button type="button" class="scanner-manual-toggle-btn" onclick="switchScannerMode('code')">
                    <i class="bi bi-key-fill text-warning me-1"></i> Or enter 6-digit Code instead
                </button>
            </div>
        </div>

        <!-- Mode 2: Direct Code / PIN Input View -->
        <div id="scannerCodeView" class="scanner-active-content" style="display: none; padding: 10px 0;">
            <div style="width: 60px; height: 60px; border-radius: 20px; background: linear-gradient(135deg, #cfa46f, #8c6d46); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: #181614; margin: 0 auto 14px; box-shadow: 0 8px 24px rgba(207,164,111,0.3);">
                <i class="bi bi-key-fill"></i>
            </div>
            
            <h4 class="scanner-title">Enter Attendance Code</h4>
            <p class="scanner-sub">Type the 6-digit attendance code displayed on the teacher's screen</p>

            <div class="code-entry-container my-4">
                <input type="text" id="directSessionCodeInput" class="code-entry-input" placeholder="849 201" maxlength="9" autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false" oninput="formatSessionCodeInput(this)" onkeydown="handleCodeKeydown(event)">
                <div class="code-entry-hint mt-2">
                    <i class="bi bi-shield-check text-warning me-1"></i> 6-digit session PIN or QR token
                </div>
            </div>

            <div class="d-flex flex-column gap-2">
                <button type="button" id="codeSubmitBtn" class="btn scanner-primary-action-btn w-100" onclick="submitDirectCode()">
                    <i class="bi bi-check2-circle me-1"></i> Record Attendance
                </button>
                <button type="button" id="switchToCameraBtn" class="btn scanner-secondary-action-btn w-100" onclick="switchScannerMode('scan')">
                    <i class="bi bi-camera-fill me-1"></i> Switch to Camera Scan
                </button>
            </div>
        </div>

        <!-- Result View State -->
        <div id="scannerResultView" class="scanner-result-content" style="display: none;">
            <div id="resultStatusIcon" class="result-status-icon-wrap"></div>
            
            <h4 id="resultTitle" class="result-headline"></h4>
            <p id="resultSubtitle" class="result-caption"></p>

            <!-- Result Details Box -->
            <div id="resultDetailsBox" class="result-summary-card">
                <div class="result-row">
                    <span class="result-label">Status</span>
                    <span id="resultBadge" class="badge"></span>
                </div>
                <div class="result-row">
                    <span class="result-label">Subject</span>
                    <span id="resultSubject" class="result-val highlight"></span>
                </div>
                <div class="result-row">
                    <span class="result-label">Instructor</span>
                    <span id="resultInstructor" class="result-val"></span>
                </div>
                <div class="result-row">
                    <span class="result-label">Section</span>
                    <span id="resultSection" class="result-val"></span>
                </div>
                <div class="result-row no-border">
                    <span class="result-label">Recorded At</span>
                    <span id="resultTimestamp" class="result-val text-gold"></span>
                </div>
            </div>

            <!-- Auto Close Countdown Notice (active on successful recording) -->
            <div id="resultAutoCloseNotice" class="result-autoclose-box" style="display: none;">
                <div class="autoclose-text">
                    <i class="bi bi-check2-circle text-success me-1"></i> Auto-closing and updating in <strong id="autoCloseCountdown">3</strong>s
                </div>
                <div class="autoclose-progress-bar">
                    <div id="autoCloseProgressFill" class="autoclose-progress-fill"></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 w-100 mt-2">
                <button type="button" id="resultDoneBtn" onclick="finishScanAndRefresh()" class="btn scanner-primary-action-btn flex-fill">
                    <i class="bi bi-check-lg me-1"></i> Done (Back to Dashboard)
                </button>
                <button type="button" id="resultRetryBtn" onclick="resetScannerView()" class="btn scanner-secondary-action-btn flex-fill" style="display: none;">
                    <i class="bi bi-arrow-repeat me-1"></i> Try Again
                </button>
            </div>
        </div>

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
            <span>Please step into the classroom or move closer to the instructor's display and scan the QR code again.</span>
        </div>

        <!-- Action Buttons -->
        <div class="outside-range-actions">
            <button type="button" class="outside-range-btn-primary" onclick="retryScanFromOutsidePopup()">
                <i class="bi bi-qr-code-scan me-1"></i> Scan QR Code Again
            </button>
            <button type="button" class="outside-range-btn-secondary" onclick="useCodeFromOutsidePopup()">
                <i class="bi bi-key-fill me-1"></i> Enter 6-Digit Code
            </button>
            <button type="button" class="outside-range-btn-text" onclick="closeOutsideRangePopup()">
                Dismiss
            </button>
        </div>
    </div>
</div>

<style>
/* ── OUTSIDE RANGE POPUP DIALOG ── */
.outside-range-popup-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(10, 5, 5, 0.88);
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
    background: linear-gradient(180deg, #241616 0%, #150d0d 100%);
    border: 1.5px solid rgba(239, 68, 68, 0.45);
    border-radius: 24px;
    max-width: 440px;
    width: 100%;
    padding: 24px 20px;
    text-align: center;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8), 0 0 30px rgba(239, 68, 68, 0.2);
    animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes scaleUp {
    0% { transform: scale(0.92); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.outside-range-close-btn {
    position: absolute;
    top: 14px;
    right: 14px;
    background: rgba(255, 255, 255, 0.08);
    border: none;
    color: #f3e7cd;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
}

.outside-range-close-btn:hover {
    background: rgba(239, 68, 68, 0.3);
    color: #ffffff;
}

.outside-range-icon-pulse {
    width: 68px;
    height: 68px;
    margin: 0 auto 14px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.outside-range-icon-pulse::after {
    content: '';
    position: absolute;
    inset: -6px;
    border-radius: 50%;
    border: 2px solid rgba(239, 68, 68, 0.35);
    animation: pulseRing 1.8s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}

@keyframes pulseRing {
    0% { transform: scale(0.85); opacity: 1; }
    100% { transform: scale(1.35); opacity: 0; }
}

.outside-range-icon-inner {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ef4444, #b91c1c);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.5);
}

.outside-range-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(239, 68, 68, 0.18);
    border: 1px solid rgba(239, 68, 68, 0.35);
    color: #fca5a5;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    padding: 3px 10px;
    border-radius: 99px;
    margin-bottom: 8px;
}

.outside-range-headline {
    font-size: 1.35rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 8px;
}

.outside-range-desc {
    font-size: 0.88rem;
    color: #d1c4b2;
    line-height: 1.5;
    margin-bottom: 20px;
}

.outside-range-metrics-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(0, 0, 0, 0.35);
    border: 1px solid rgba(207, 164, 111, 0.2);
    border-radius: 16px;
    padding: 14px 18px;
    margin-bottom: 18px;
}

.outside-range-metric-col {
    flex: 1;
    text-align: center;
}

.outside-range-metric-col .metric-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: #b39b82;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 4px;
}

.outside-range-metric-col .metric-val {
    font-size: 1.45rem;
    font-weight: 800;
    font-family: monospace;
}

.outside-range-metric-col.detected .metric-val {
    color: #f87171;
}

.outside-range-metric-col.allowed .metric-val {
    color: #fbbf24;
}

.outside-range-metric-col .metric-sub {
    font-size: 0.72rem;
    font-weight: 600;
}

.outside-range-divider {
    width: 1px;
    height: 48px;
    background: rgba(255, 255, 255, 0.1);
    margin: 0 12px;
}

.outside-range-tip-box {
    display: flex;
    align-items: flex-start;
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.25);
    border-radius: 12px;
    padding: 12px 14px;
    font-size: 0.8rem;
    color: #fde68a;
    text-align: left;
    margin-bottom: 22px;
    line-height: 1.45;
}

.outside-range-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.outside-range-btn-primary {
    background: linear-gradient(135deg, #cfa46f, #a07a4a);
    color: #110a0a;
    font-weight: 800;
    font-size: 0.92rem;
    padding: 12px 20px;
    border-radius: 14px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 16px rgba(207, 164, 111, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}

.outside-range-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 22px rgba(207, 164, 111, 0.4);
}

.outside-range-btn-secondary {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(207, 164, 111, 0.3);
    color: #f3e7cd;
    font-weight: 700;
    font-size: 0.88rem;
    padding: 10px 18px;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.outside-range-btn-secondary:hover {
    background: rgba(207, 164, 111, 0.15);
    color: #ffffff;
}

.outside-range-btn-text {
    background: transparent;
    border: none;
    color: #b39b82;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 6px;
    cursor: pointer;
    transition: color 0.2s;
}

.outside-range-btn-text:hover {
    color: #f3e7cd;
}

/* ── STUDENT SCANNER & CODE MODAL STYLES ── */
.scanner-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(8, 8, 10, 0.88);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.scanner-modal-card {
    background: linear-gradient(180deg, #1f1b17 0%, #131211 100%);
    border: 1px solid rgba(207, 164, 111, 0.28);
    border-radius: 28px;
    max-width: 470px;
    width: 100%;
    padding: 22px 20px;
    color: #ffffff;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(207, 164, 111, 0.12);
    text-align: center;
    position: relative;
    overflow: hidden;
    animation: scannerCardEnter 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes scannerCardEnter {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

/* Mode Switcher (QR vs Code) */
.scanner-mode-switcher {
    display: inline-flex;
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(207, 164, 111, 0.2);
    border-radius: 99px;
    padding: 3px;
    gap: 3px;
}

.scanner-mode-tab {
    background: transparent;
    border: none;
    color: #b39b82;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 99px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.scanner-mode-tab.active {
    background: linear-gradient(135deg, #cfa46f, #8c6d46);
    color: #181614;
    box-shadow: 0 4px 12px rgba(207, 164, 111, 0.35);
}

/* Top Floating Bar */
.scanner-top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.scanner-top-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.scanner-icon-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #f3e7cd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.scanner-icon-btn:hover, .scanner-icon-btn:active {
    background: rgba(207, 164, 111, 0.2);
    color: #ffffff;
    border-color: rgba(207, 164, 111, 0.4);
    transform: scale(1.06);
}

.scanner-icon-btn.active-torch {
    background: #cfa46f;
    color: #181614;
    border-color: #ffd700;
    box-shadow: 0 0 16px rgba(255, 215, 0, 0.6);
}

.scanner-icon-btn.close-btn:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border-color: rgba(239, 68, 68, 0.4);
}

/* Heading */
.scanner-hero-heading {
    margin-bottom: 14px;
}

.scanner-title {
    font-weight: 800;
    font-size: 1.25rem;
    color: #ffffff;
    margin-bottom: 2px;
    letter-spacing: -0.02em;
}

.scanner-sub {
    color: #b39b82;
    font-size: 0.82rem;
    margin-bottom: 0;
}

/* Code Entry Panel */
.code-entry-container {
    background: rgba(0, 0, 0, 0.4);
    border: 1.5px solid rgba(207, 164, 111, 0.35);
    border-radius: 22px;
    padding: 20px 16px;
    box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.6);
}

.code-entry-input {
    background: rgba(0, 0, 0, 0.6) !important;
    border: 2px solid rgba(207, 164, 111, 0.4) !important;
    color: #ffd700 !important;
    font-family: 'Consolas', 'Courier New', monospace !important;
    font-size: 2.2rem !important;
    font-weight: 900 !important;
    letter-spacing: 8px !important;
    text-align: center !important;
    border-radius: 16px !important;
    padding: 12px 10px !important;
    width: 100% !important;
    text-transform: uppercase !important;
    box-shadow: 0 0 16px rgba(207, 164, 111, 0.15) !important;
    transition: all 0.2s !important;
}

.code-entry-input:focus {
    border-color: #ffd700 !important;
    box-shadow: 0 0 24px rgba(255, 215, 0, 0.35) !important;
    outline: none !important;
}

.code-entry-hint {
    font-size: 0.78rem;
    color: #b39b82;
}

/* Viewfinder Area */
.scanner-viewfinder-wrapper {
    position: relative;
    border-radius: 24px;
    overflow: hidden;
    background: #000000;
    width: 100%;
    max-width: 320px;
    aspect-ratio: 1 / 1;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1.5px solid rgba(207, 164, 111, 0.4);
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.7), inset 0 0 40px rgba(0, 0, 0, 0.9);
}

.scanner-reader-feed {
    position: absolute !important;
    inset: 0 !important;
    width: 100% !important;
    height: 100% !important;
    overflow: hidden !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.scanner-reader-feed video {
    border-radius: 22px !important;
    object-fit: cover !important;
    width: 100% !important;
    height: 100% !important;
}

.scanner-reader-feed canvas {
    display: none !important;
}

.scanner-reader-feed #reader__scan_region {
    border: none !important;
    width: 100% !important;
    height: 100% !important;
}

.scanner-reader-feed #reader__dashboard {
    display: none !important;
}

/* Reticle Corner Markers */
.reticle-corner {
    position: absolute;
    width: 28px;
    height: 28px;
    border-color: #ffd700;
    border-style: solid;
    border-width: 0;
    z-index: 14;
    pointer-events: none;
    filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.75));
}

.reticle-corner.top-left {
    top: 18px;
    left: 18px;
    border-top-width: 3.5px;
    border-left-width: 3.5px;
    border-top-left-radius: 12px;
}

.reticle-corner.top-right {
    top: 18px;
    right: 18px;
    border-top-width: 3.5px;
    border-right-width: 3.5px;
    border-top-right-radius: 12px;
}

.reticle-corner.bottom-left {
    bottom: 18px;
    left: 18px;
    border-bottom-width: 3.5px;
    border-left-width: 3.5px;
    border-bottom-left-radius: 12px;
}

.reticle-corner.bottom-right {
    bottom: 18px;
    right: 18px;
    border-bottom-width: 3.5px;
    border-right-width: 3.5px;
    border-bottom-right-radius: 12px;
}

/* Laser Scan Line */
.scanner-laser-line {
    position: absolute;
    left: 8%;
    right: 8%;
    height: 3px;
    background: linear-gradient(90deg, transparent, #cfa46f 25%, #ffd700 50%, #cfa46f 75%, transparent);
    box-shadow: 0 0 18px #ffd700, 0 0 32px rgba(207, 164, 111, 0.7);
    z-index: 15;
    animation: modernLaserScan 2s ease-in-out infinite;
    pointer-events: none;
}

@keyframes modernLaserScan {
    0% { top: 12%; opacity: 0.25; }
    50% { top: 86%; opacity: 1; }
    100% { top: 12%; opacity: 0.25; }
}

/* Guidance Badge */
.scanner-guide-badge {
    position: absolute;
    bottom: 12px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(18, 16, 14, 0.85);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.16);
    color: #f3e7cd;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 5px 14px;
    border-radius: 99px;
    z-index: 16;
    pointer-events: none;
    white-space: nowrap;
}

/* Processing Overlay */
.scanner-processing-overlay {
    position: absolute;
    inset: 0;
    background: rgba(14, 13, 12, 0.92);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    z-index: 25;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 22px;
}

.processing-title {
    font-weight: 800;
    color: #ffffff;
    font-size: 1rem;
    letter-spacing: -0.01em;
}

.processing-sub {
    font-size: 0.78rem;
    color: #b39b82;
    margin-top: 2px;
}

/* Fallback Notice */
.scanner-fallback-box {
    padding: 24px 16px;
    color: #b39b82;
    text-align: center;
    z-index: 20;
    position: relative;
}

.fallback-icon-wrap {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
}

.fallback-title {
    font-weight: 700;
    color: #ffffff;
    font-size: 1rem;
    margin-bottom: 4px;
}

.fallback-text {
    font-size: 0.8rem;
    color: #d1c4b2;
    max-width: 260px;
    margin: 0 auto;
    line-height: 1.45;
}

.scanner-manual-toggle-btn {
    background: none;
    border: none;
    color: #cfa46f;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    padding: 6px 12px;
    border-radius: 8px;
    transition: all 0.2s;
}

.scanner-manual-toggle-btn:hover {
    color: #ffd700;
    background: rgba(207, 164, 111, 0.1);
}

/* Result View */
.scanner-result-content {
    padding: 8px 0 4px;
    text-align: center;
}

.result-status-icon-wrap {
    width: 74px;
    height: 74px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.3rem;
    margin: 0 auto 14px;
    animation: resultPop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes resultPop {
    0% { transform: scale(0.4); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.result-headline {
    font-weight: 800;
    font-size: 1.3rem;
    letter-spacing: -0.02em;
    margin-bottom: 4px;
}

.result-caption {
    color: #b39b82;
    font-size: 0.86rem;
    margin-bottom: 18px;
    line-height: 1.45;
}

.result-summary-card {
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    padding: 14px 18px;
    margin-bottom: 16px;
    text-align: left;
}

.result-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.result-row.no-border {
    border-bottom: none;
    padding-bottom: 2px;
}

.result-label {
    font-size: 0.75rem;
    color: #b39b82;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.result-val {
    font-size: 0.86rem;
    color: #f3e7cd;
    font-weight: 600;
    text-align: right;
}

.result-val.highlight {
    color: #ffffff;
    font-weight: 700;
}

.result-val.text-gold {
    color: #cfa46f;
    font-weight: 700;
}

/* Auto Close Countdown Box */
.result-autoclose-box {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 14px;
    padding: 10px 14px;
    margin-bottom: 14px;
    text-align: center;
}

.autoclose-text {
    font-size: 0.82rem;
    font-weight: 700;
    color: #34d399;
    margin-bottom: 6px;
}

.autoclose-progress-bar {
    width: 100%;
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 99px;
    overflow: hidden;
}

.autoclose-progress-fill {
    height: 100%;
    width: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    transition: width 0.1s linear;
}

.scanner-primary-action-btn {
    background: linear-gradient(135deg, #cfa46f, #8c6d46) !important;
    color: #181614 !important;
    font-weight: 800 !important;
    padding: 13px !important;
    border-radius: 16px !important;
    font-size: 0.95rem !important;
    border: none !important;
    box-shadow: 0 8px 24px rgba(207, 164, 111, 0.3) !important;
    transition: all 0.2s !important;
}

.scanner-secondary-action-btn {
    background: rgba(255, 255, 255, 0.08) !important;
    border: 1px solid rgba(255, 255, 255, 0.15) !important;
    color: #f3e7cd !important;
    font-weight: 700 !important;
    padding: 13px !important;
    border-radius: 16px !important;
    font-size: 0.95rem !important;
}

/* ── MOBILE ADAPTIVE VIEWPORT OPTIMIZATIONS ── */
@media (max-width: 640px) {
    .scanner-modal-backdrop {
        padding: 0;
        align-items: flex-end;
    }

    .scanner-modal-card {
        max-width: 100vw;
        width: 100vw;
        min-height: 88dvh;
        max-height: 96dvh;
        border-radius: 32px 32px 0 0;
        border-bottom: none;
        border-left: none;
        border-right: none;
        padding: 20px 18px max(24px, env(safe-area-inset-bottom)) 18px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .scanner-viewfinder-wrapper {
        max-width: 300px;
        aspect-ratio: 1 / 1;
        border-radius: 26px;
    }

    .scanner-reader-feed video {
        border-radius: 24px !important;
    }

    .scanner-title {
        font-size: 1.3rem;
    }

    .code-entry-input {
        font-size: 2.2rem !important;
        letter-spacing: 6px !important;
        padding: 14px 10px !important;
    }

    .reticle-corner {
        width: 30px;
        height: 30px;
    }

    .reticle-corner.top-left { top: 16px; left: 16px; }
    .reticle-corner.top-right { top: 16px; right: 16px; }
    .reticle-corner.bottom-left { bottom: 16px; left: 16px; }
    .reticle-corner.bottom-right { bottom: 16px; right: 16px; }
}

/* ── DESKTOP ONLY / MOBILE ONLY RESPONSIVE RULES ── */
@media (min-width: 768px) {
    /* Desktop layout: lock to code-only manual entry */
    .scanner-mode-switcher {
        display: none !important;
    }
    #desktopScannerHeader {
        display: flex !important;
    }
    #torchCameraBtn,
    #flipCameraBtn,
    #switchToCameraBtn {
        display: none !important;
    }
    #scannerActiveView {
        display: none !important;
    }
    .scanner-modal-card {
        max-width: 480px;
        width: 100%;
        padding: 28px 28px 24px;
        border-radius: 28px;
    }
}

@media (max-width: 767px) {
    /* Mobile layout: show switcher and camera elements */
    #desktopScannerHeader {
        display: none !important;
    }
}
</style>

<script nonce="{{ csp_nonce() }}" src="{{ asset('js/html5-qrcode.min.js') }}?v={{ filemtime(public_path('js/html5-qrcode.min.js')) }}"></script>
<script nonce="{{ csp_nonce() }}">
let html5QrScanner = null;
let isScannerStarting = false;
let isScannerRunning = false;
let isScanInFlight = false;
let currentFacingMode = "environment";
let torchEnabled = false;
let studentGeoCoords = null;
let currentScannerMode = 'scan'; // 'scan' | 'code'
let autoCloseTimer = null;
let autoCloseCountdownInterval = null;

// Auto-capture GPS coords quietly for faster validation
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        pos => { studentGeoCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude, acc: pos.coords.accuracy }; },
        () => {},
        { enableHighAccuracy: true, timeout: 5000 }
    );
}

function isDesktopDevice() {
    // Desktop / tablet layout threshold: screen width >= 768px
    return window.innerWidth >= 768;
}

function switchScannerMode(mode) {
    if (isDesktopDevice()) {
        // Enforce code-only mode on desktop layouts
        mode = 'code';
    }
    currentScannerMode = mode;
    const tabScan = document.getElementById('tabScanMode');
    const tabCode = document.getElementById('tabCodeMode');
    const scanView = document.getElementById('scannerActiveView');
    const codeView = document.getElementById('scannerCodeView');
    const torchBtn = document.getElementById('torchCameraBtn');
    const flipBtn = document.getElementById('flipCameraBtn');
    const switchCamBtn = document.getElementById('switchToCameraBtn');

    if (mode === 'scan' && !isDesktopDevice()) {
        if (tabScan) tabScan.classList.add('active');
        if (tabCode) tabCode.classList.remove('active');
        if (scanView) scanView.style.display = 'block';
        if (codeView) codeView.style.display = 'none';
        if (torchBtn) torchBtn.style.display = 'flex';
        if (flipBtn) flipBtn.style.display = 'flex';
        startHtml5Scanner();
    } else {
        if (tabCode) tabCode.classList.add('active');
        if (tabScan) tabScan.classList.remove('active');
        if (scanView) scanView.style.display = 'none';
        if (codeView) codeView.style.display = 'block';
        if (torchBtn) torchBtn.style.display = 'none';
        if (flipBtn) flipBtn.style.display = 'none';
        if (switchCamBtn) {
            switchCamBtn.style.display = isDesktopDevice() ? 'none' : 'block';
        }

        // Stop camera while typing to save battery
        safeStopScanner();

        setTimeout(() => {
            const input = document.getElementById('directSessionCodeInput');
            if (input) input.focus();
        }, 80);
    }

    if (window.triggerHaptic) window.triggerHaptic('light');
}

function formatSessionCodeInput(el) {
    let val = el.value.replace(/[^0-9A-Za-z]/g, '').toUpperCase();
    if (val.length > 6) {
        val = val.substring(0, 6);
    }
    if (val.length > 3) {
        el.value = val.substring(0, 3) + ' ' + val.substring(3);
    } else {
        el.value = val;
    }

    if (val.length === 6 && window.triggerHaptic) {
        window.triggerHaptic('light');
    }
}

function handleCodeKeydown(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        submitDirectCode();
    }
}

function submitDirectCode() {
    const rawVal = (document.getElementById('directSessionCodeInput')?.value || '').trim();
    const cleanVal = rawVal.replace(/[^0-9A-Za-z]/g, '');
    
    if (!cleanVal) {
        alert('Please enter the 6-digit attendance code shown on the screen.');
        document.getElementById('directSessionCodeInput')?.focus();
        return;
    }

    onQrScanSuccess(cleanVal);
}

function applyResponsiveScannerLayout() {
    const isDesktop = isDesktopDevice();
    const modeSwitcher = document.getElementById('mobileModeSwitcher') || document.querySelector('.scanner-mode-switcher');
    const desktopHeader = document.getElementById('desktopScannerHeader');
    const torchBtn = document.getElementById('torchCameraBtn');
    const flipBtn = document.getElementById('flipCameraBtn');
    const switchCamBtn = document.getElementById('switchToCameraBtn');
    const scanTab = document.getElementById('tabScanMode');

    if (isDesktop) {
        if (modeSwitcher) modeSwitcher.style.display = 'none';
        if (desktopHeader) desktopHeader.style.display = 'flex';
        if (torchBtn) torchBtn.style.display = 'none';
        if (flipBtn) flipBtn.style.display = 'none';
        if (switchCamBtn) switchCamBtn.style.display = 'none';
        if (scanTab) scanTab.style.display = 'none';
    } else {
        if (modeSwitcher) modeSwitcher.style.display = 'inline-flex';
        if (desktopHeader) desktopHeader.style.display = 'none';
        if (switchCamBtn) switchCamBtn.style.display = 'block';
        if (scanTab) scanTab.style.display = 'inline-block';
    }
}

function openStudentScanner(initialMode = 'scan') {
    const modal = document.getElementById('studentScannerModal');
    if (!modal) return;
    
    const isDesktop = isDesktopDevice();
    // On desktop, strictly enforce code entry mode
    if (isDesktop) {
        initialMode = 'code';
    }
    modal.style.display = 'flex';
    
    applyResponsiveScannerLayout();
    resetScannerView();
    switchScannerMode(initialMode);
}

async function safeStopScanner() {
    if (html5QrScanner && isScannerRunning) {
        try {
            await html5QrScanner.stop();
        } catch (e) {
            console.warn("[Scanner] Safe stop warning:", e);
        }
    }
    isScannerRunning = false;
    isScannerStarting = false;
    const laser = document.getElementById('scannerLaser');
    if (laser) laser.style.display = 'none';
}

async function safeClearScanner() {
    await safeStopScanner();
    if (html5QrScanner) {
        try {
            await html5QrScanner.clear();
        } catch (e) {}
        html5QrScanner = null;
    }
}

async function waitForHtml5Qrcode(maxWaitMs = 3000) {
    const startTime = Date.now();
    while (typeof Html5Qrcode === 'undefined') {
        if (Date.now() - startTime > maxWaitMs) {
            return false;
        }
        await new Promise(r => setTimeout(r, 100));
    }
    return true;
}

async function startHtml5Scanner() {
    if (isDesktopDevice()) {
        console.log('[Scanner] QR camera scanner is disabled on desktop layouts.');
        return;
    }

    if (isScannerStarting || isScannerRunning) {
        return;
    }

    hideCameraError();

    // 1. Check secure context (HTTPS / localhost required by browsers for camera)
    if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        showCameraError("Camera access requires a secure connection (HTTPS) or localhost. Please switch to 6-digit Code entry.", false, true);
        return;
    }

    // 2. Check browser mediaDevices support
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showCameraError("Your browser or device does not support live camera scanning. Please enter the 6-digit Code.", false, true);
        return;
    }

    // 3. Wait for Html5Qrcode library to be loaded
    const libLoaded = await waitForHtml5Qrcode();
    if (!libLoaded) {
        showCameraError("Scanner engine is loading. Please enter the 6-digit Code or tap Retry.", false);
        return;
    }

    isScannerStarting = true;

    try {
        // 4. Request camera permissions explicitly
        try {
            const probeStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: currentFacingMode } }
            });
            probeStream.getTracks().forEach(track => track.stop());
        } catch (permErr) {
            isScannerStarting = false;
            if (permErr.name === 'NotAllowedError' || permErr.name === 'PermissionDeniedError') {
                showCameraError("Camera access was denied. Please allow camera permissions in your browser address bar/settings, then tap Retry Camera.", true);
                return;
            } else if (permErr.name === 'NotFoundError' || permErr.name === 'DevicesNotFoundError') {
                showCameraError("No camera detected on this device. Please use 'Enter Code'.", false, true);
                return;
            } else if (permErr.name === 'NotReadableError' || permErr.name === 'TrackStartError') {
                showCameraError("Camera is currently in use by another app. Please close other camera apps and tap Retry.", false);
                return;
            } else {
                showCameraError("Camera permission failed: " + (permErr.message || "Unknown error"), false);
                return;
            }
        }

        // 5. Initialize Html5Qrcode instance
        await safeClearScanner();

        const readerContainer = document.getElementById('reader');
        if (readerContainer) {
            readerContainer.innerHTML = '';
        }

        html5QrScanner = new Html5Qrcode("reader");

        const qrConfig = {
            fps: 20,
            qrbox: function(viewfinderWidth, viewfinderHeight) {
                const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                const qrboxSize = Math.floor(minEdge * 0.76);
                return { width: Math.max(qrboxSize, 180), height: Math.max(qrboxSize, 180) };
            },
            experimentalFeatures: {
                useBarCodeDetectorIfSupported: true
            }
        };

        // Try primary facingMode
        try {
            await html5QrScanner.start(
                { facingMode: { ideal: currentFacingMode } },
                qrConfig,
                onQrScanSuccess
            );
            isScannerRunning = true;
            isScannerStarting = false;
            const laser = document.getElementById('scannerLaser');
            if (laser) laser.style.display = 'block';
            return;
        } catch (firstErr) {
            console.warn("[Scanner] FacingMode start failed, trying camera enumeration fallback:", firstErr);
        }

        // Camera enumeration fallback for multi-lens mobile devices
        const cameras = await Html5Qrcode.getCameras();
        if (cameras && cameras.length > 0) {
            let selectedCamera = cameras[0];
            if (currentFacingMode === 'environment') {
                const backCam = cameras.find(c => /back|rear|environment|main/i.test(c.label));
                if (backCam) selectedCamera = backCam;
            } else if (currentFacingMode === 'user') {
                const frontCam = cameras.find(c => /front|user|selfie/i.test(c.label));
                if (frontCam) selectedCamera = frontCam;
            }

            await html5QrScanner.start(
                selectedCamera.id,
                qrConfig,
                onQrScanSuccess
            );
            isScannerRunning = true;
            isScannerStarting = false;
            const laser = document.getElementById('scannerLaser');
            if (laser) laser.style.display = 'block';
        } else {
            throw new Error("No cameras detected on this device.");
        }
    } catch (err) {
        console.warn("[Scanner] Camera start failed completely:", err);
        isScannerStarting = false;
        isScannerRunning = false;
        showCameraError("Camera unavailable or permission denied. Tap 'Retry Camera' or enter the 6-digit Code.", true);
    }
}

function showCameraError(msg, isPermission = false, isUnsupported = false) {
    const notice = document.getElementById('scannerFallbackNotice');
    const text = document.getElementById('scannerFallbackText');
    const title = document.getElementById('fallbackTitle');
    const iconWrap = document.getElementById('fallbackIconWrap');
    const retryBtn = document.getElementById('retryCameraBtn');
    const laser = document.getElementById('scannerLaser');

    if (notice) notice.style.display = 'block';
    if (text) text.textContent = msg;
    if (laser) laser.style.display = 'none';

    if (title) {
        if (isPermission) {
            title.textContent = 'Camera Permission Required';
        } else if (isUnsupported) {
            title.textContent = 'Camera Unsupported';
        } else {
            title.textContent = 'Camera Inactive';
        }
    }

    if (iconWrap) {
        if (isPermission) {
            iconWrap.innerHTML = '<i class="bi bi-shield-lock-fill text-warning"></i>';
            iconWrap.style.background = 'rgba(245, 158, 11, 0.15)';
            iconWrap.style.borderColor = 'rgba(245, 158, 11, 0.35)';
        } else {
            iconWrap.innerHTML = '<i class="bi bi-camera-video-off text-danger"></i>';
            iconWrap.style.background = 'rgba(239, 68, 68, 0.15)';
            iconWrap.style.borderColor = 'rgba(239, 68, 68, 0.35)';
        }
    }

    if (retryBtn) {
        retryBtn.style.display = isUnsupported ? 'none' : 'inline-flex';
    }
}

function hideCameraError() {
    const notice = document.getElementById('scannerFallbackNotice');
    if (notice) notice.style.display = 'none';
}

function requestCameraAgain() {
    hideCameraError();
    startHtml5Scanner();
}

async function toggleCameraFacing() {
    currentFacingMode = currentFacingMode === "environment" ? "user" : "environment";
    await safeStopScanner();
    startHtml5Scanner();
    if (window.triggerHaptic) window.triggerHaptic('light');
}

function toggleTorch() {
    if (!html5QrScanner || !isScannerRunning) return;
    try {
        torchEnabled = !torchEnabled;
        const torchBtn = document.getElementById('torchCameraBtn');
        html5QrScanner.applyVideoConstraints({
            advanced: [{ torch: torchEnabled }]
        }).then(() => {
            if (torchEnabled) {
                if (torchBtn) torchBtn.classList.add('active-torch');
            } else {
                if (torchBtn) torchBtn.classList.remove('active-torch');
            }
        }).catch(() => {
            if (torchBtn) torchBtn.classList.remove('active-torch');
            if (typeof showToast === 'function') {
                showToast('Flashlight is not supported on this camera/browser.', 'info', 2500);
            }
        });
    } catch (e) {}
}

function closeStudentScanner() {
    clearAutoCloseTimer();
    const modal = document.getElementById('studentScannerModal');
    if (modal) modal.style.display = 'none';
    safeClearScanner();
    isScanInFlight = false;
}

function clearAutoCloseTimer() {
    if (autoCloseTimer) {
        clearTimeout(autoCloseTimer);
        autoCloseTimer = null;
    }
    if (autoCloseCountdownInterval) {
        clearInterval(autoCloseCountdownInterval);
        autoCloseCountdownInterval = null;
    }
}

function resetScannerView() {
    clearAutoCloseTimer();
    isScanInFlight = false;

    const isDesktop = isDesktopDevice();
    const activeView = document.getElementById('scannerActiveView');
    const codeView = document.getElementById('scannerCodeView');
    const resultView = document.getElementById('scannerResultView');
    const overlay = document.getElementById('scannerProcessingOverlay');
    const codeInput = document.getElementById('directSessionCodeInput');
    const autoCloseNotice = document.getElementById('resultAutoCloseNotice');

    if (resultView) resultView.style.display = 'none';
    if (overlay) overlay.style.display = 'none';
    if (autoCloseNotice) autoCloseNotice.style.display = 'none';
    if (codeInput) codeInput.value = '';

    if (isDesktop) {
        if (activeView) activeView.style.display = 'none';
        if (codeView) codeView.style.display = 'block';
    } else {
        if (currentScannerMode === 'scan') {
            if (activeView) activeView.style.display = 'block';
            if (codeView) codeView.style.display = 'none';
            const modal = document.getElementById('studentScannerModal');
            if (modal && modal.style.display === 'flex') {
                startHtml5Scanner();
            }
        } else {
            if (activeView) activeView.style.display = 'none';
            if (codeView) codeView.style.display = 'block';
        }
    }
}

function extractQrToken(raw) {
    if (!raw) return '';
    let str = raw.trim();
    if (str.includes('/qr/scan/')) {
        const parts = str.split('/qr/scan/');
        str = parts[1] ? parts[1].split('?')[0].split('#')[0] : str;
    } else if (str.startsWith('http://') || str.startsWith('https://')) {
        try {
            const url = new URL(str);
            const pathParts = url.pathname.split('/').filter(Boolean);
            if (pathParts.length > 0) {
                str = pathParts[pathParts.length - 1];
            }
        } catch(e) {}
    }
    return str.trim();
}

async function onQrScanSuccess(decodedText) {
    // Prevent multiple accidental scan triggers (in-flight guard)
    if (isScanInFlight) return;
    isScanInFlight = true;

    // Immediately stop camera feed and hide laser
    safeStopScanner();

    const overlay = document.getElementById('scannerProcessingOverlay');
    const laser = document.getElementById('scannerLaser');
    if (overlay) overlay.style.display = 'flex';
    if (laser) laser.style.display = 'none';

    // Play quick scan beep & haptic
    playScanBeep();
    if (window.triggerHaptic) window.triggerHaptic('medium');

    // Clean scanned token / URL
    const cleanedToken = extractQrToken(decodedText);

    // Fetch fresh GPS if not already captured
    if (!studentGeoCoords && navigator.geolocation) {
        try {
            await new Promise((resolve) => {
                navigator.geolocation.getCurrentPosition(
                    pos => {
                        studentGeoCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude, acc: pos.coords.accuracy };
                        resolve();
                    },
                    () => resolve(),
                    { enableHighAccuracy: true, timeout: 3500 }
                );
            });
        } catch(e) {}
    }

    const payload = {
        token: cleanedToken,
        latitude: studentGeoCoords ? studentGeoCoords.lat : null,
        longitude: studentGeoCoords ? studentGeoCoords.lng : null,
        accuracy: studentGeoCoords ? studentGeoCoords.acc : null
    };

    try {
        const response = await fetch('{{ route("qr.scan.process") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (overlay) overlay.style.display = 'none';

        if (response.ok && data.success) {
            renderScanSuccess(data);
        } else {
            renderScanError(data);
        }
    } catch (error) {
        if (overlay) overlay.style.display = 'none';
        renderScanError({
            message: 'Network connection failed. Please verify your internet connection and try again.'
        });
    }
}

function renderScanSuccess(data) {
    clearAutoCloseTimer();

    document.getElementById('scannerActiveView').style.display = 'none';
    document.getElementById('scannerCodeView').style.display = 'none';
    document.getElementById('scannerResultView').style.display = 'block';

    const iconBox = document.getElementById('resultStatusIcon');
    const badge = document.getElementById('resultBadge');
    const title = document.getElementById('resultTitle');
    const subtitle = document.getElementById('resultSubtitle');
    const retryBtn = document.getElementById('resultRetryBtn');
    const doneBtn = document.getElementById('resultDoneBtn');
    const autoCloseNotice = document.getElementById('resultAutoCloseNotice');
    const detailsBox = document.getElementById('resultDetailsBox');

    if (detailsBox) detailsBox.style.display = 'block';
    if (retryBtn) retryBtn.style.display = 'none';
    if (doneBtn) doneBtn.style.display = 'block';

    if (data.already_clocked_in) {
        iconBox.style.background = 'rgba(59, 130, 246, 0.15)';
        iconBox.style.border = '2px solid rgba(59, 130, 246, 0.4)';
        iconBox.innerHTML = '<i class="bi bi-info-circle-fill" style="color: #60a5fa;"></i>';
        
        title.textContent = 'Already Clocked In';
        subtitle.textContent = data.message || 'You have already recorded your attendance for this class today.';
        
        badge.className = 'badge bg-info text-dark';
        badge.textContent = data.status || 'Present';

        if (autoCloseNotice) autoCloseNotice.style.display = 'none';
        if (window.triggerHaptic) window.triggerHaptic('light');
    } else {
        iconBox.style.background = 'rgba(16, 185, 129, 0.15)';
        iconBox.style.border = '2px solid rgba(16, 185, 129, 0.4)';
        iconBox.innerHTML = '<i class="bi bi-check2-circle" style="color: #34d399;"></i>';

        title.textContent = 'Attendance Recorded Successfully!';
        subtitle.textContent = `Your attendance has been confirmed for ${data.subject || 'this class'}.`;

        const isPresent = (data.status || 'Present') === 'Present';
        badge.className = isPresent ? 'badge bg-success' : 'badge bg-warning text-dark';
        badge.textContent = data.status || 'Present';

        playSuccessChime();
        if (window.triggerHaptic) window.triggerHaptic('success');

        // Automatically close and submit flow
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
                if (progressFill) {
                    progressFill.style.width = (secondsLeft / 3 * 100) + '%';
                }
                if (secondsLeft <= 0) {
                    clearInterval(autoCloseCountdownInterval);
                }
            }, 1000);

            autoCloseTimer = setTimeout(() => {
                finishScanAndRefresh();
            }, 3200);
        }
    }

    document.getElementById('resultSubject').textContent = (data.subject || 'Subject') + (data.subject_code ? ' (' + data.subject_code + ')' : '');
    document.getElementById('resultInstructor').textContent = data.instructor || 'Instructor';
    document.getElementById('resultSection').textContent = data.section || 'Regular';
    document.getElementById('resultTimestamp').textContent = (data.date || '') + (data.time ? ' at ' + data.time : '');
}

function renderScanError(data) {
    clearAutoCloseTimer();
    isScanInFlight = false;

    document.getElementById('scannerActiveView').style.display = 'none';
    document.getElementById('scannerCodeView').style.display = 'none';
    document.getElementById('scannerResultView').style.display = 'block';

    const iconBox = document.getElementById('resultStatusIcon');
    const title = document.getElementById('resultTitle');
    const subtitle = document.getElementById('resultSubtitle');
    const retryBtn = document.getElementById('resultRetryBtn');
    const doneBtn = document.getElementById('resultDoneBtn');
    const autoCloseNotice = document.getElementById('resultAutoCloseNotice');

    if (autoCloseNotice) autoCloseNotice.style.display = 'none';

    iconBox.style.background = 'rgba(239, 68, 68, 0.15)';
    iconBox.style.border = '2px solid rgba(239, 68, 68, 0.4)';
    iconBox.innerHTML = '<i class="bi bi-x-circle-fill" style="color: #f87171;"></i>';

    if (data.error_type === 'schedule_mismatch') {
        title.textContent = 'Schedule Mismatch';
    } else if (data.error_type === 'session_closed' || data.error_type === 'invalid_or_expired') {
        title.textContent = 'Code / QR Expired';
    } else if (data.error_type === 'outside_classroom' || (data.message && data.message.toLowerCase().includes('outside'))) {
        title.textContent = 'Outside Classroom Range';
        showOutsideRangePopup(data);
    } else {
        title.textContent = 'Unable to Record Attendance';
    }

    subtitle.textContent = data.message || 'The entered code or QR could not be processed. Please check with your instructor.';

    document.getElementById('resultDetailsBox').style.display = 'none';
    if (retryBtn) retryBtn.style.display = 'block';
    if (doneBtn) doneBtn.style.display = 'none';

    playErrorTone();
    if (window.triggerHaptic) window.triggerHaptic('error');
}

function showOutsideRangePopup(data) {
    const modal = document.getElementById('outsideRangePopupModal');
    if (!modal) return;

    const dist = data.distance ? Math.round(data.distance) : (data.dist ? Math.round(data.dist) : null);
    const radius = data.radius ? Math.round(data.radius) : (data.limit ? Math.round(data.limit) : 50);

    const distEl = document.getElementById('outsideRangeDetectedDist');
    const radEl = document.getElementById('outsideRangeAllowedRadius');
    const msgEl = document.getElementById('outsideRangeMessage');

    if (distEl) distEl.textContent = dist !== null ? (dist + 'm away') : 'Out of range';
    if (radEl) radEl.textContent = radius + 'm radius';
    if (msgEl && data.message) {
        msgEl.textContent = data.message;
    }

    modal.style.display = 'flex';
    if (window.triggerHaptic) window.triggerHaptic('error');
    playErrorTone();
}

function closeOutsideRangePopup() {
    const modal = document.getElementById('outsideRangePopupModal');
    if (modal) modal.style.display = 'none';
}

function retryScanFromOutsidePopup() {
    closeOutsideRangePopup();
    resetScannerView();
    switchScannerMode('scan');
}

function useCodeFromOutsidePopup() {
    closeOutsideRangePopup();
    resetScannerView();
    switchScannerMode('code');
}

window.showOutsideRangePopup = showOutsideRangePopup;
window.closeOutsideRangePopup = closeOutsideRangePopup;

function finishScanAndRefresh() {
    clearAutoCloseTimer();
    closeStudentScanner();
    window.location.reload();
}

function playScanBeep() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        gain.gain.setValueAtTime(0.12, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
        osc.start();
        osc.stop(ctx.currentTime + 0.12);
    } catch(e) {}
}

function playSuccessChime() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(523.25, ctx.currentTime); // C5
        osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.1); // E5
        osc.frequency.setValueAtTime(783.99, ctx.currentTime + 0.2); // G5
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
        osc.start();
        osc.stop(ctx.currentTime + 0.5);
    } catch(e) {}
}

function playErrorTone() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(220, ctx.currentTime);
        osc.frequency.setValueAtTime(160, ctx.currentTime + 0.15);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
        osc.start();
        osc.stop(ctx.currentTime + 0.35);
    } catch(e) {}
}

// Auto open scanner if directed with URL query
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open_scanner') === '1') {
        setTimeout(() => { openStudentScanner('scan'); }, 150);
    } else if (urlParams.get('open_code') === '1') {
        setTimeout(() => { openStudentScanner('code'); }, 150);
    }

    // Modal backdrop click to close
    const modal = document.getElementById('studentScannerModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeStudentScanner();
            }
        });
    }
});

// Window resize adaptive listener
window.addEventListener('resize', () => {
    const modal = document.getElementById('studentScannerModal');
    if (modal && modal.style.display !== 'none') {
        applyResponsiveScannerLayout();
        if (isDesktopDevice() && currentScannerMode !== 'code') {
            switchScannerMode('code');
        }
    }
});

// Keyboard support: Escape key closes modal on desktop/mobile
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modal = document.getElementById('studentScannerModal');
        if (modal && modal.style.display !== 'none') {
            closeStudentScanner();
        }
    }
});
</script>
