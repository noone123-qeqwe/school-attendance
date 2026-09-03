<!-- Student QR & Code Scanner Modal Partial -->
<div id="studentScannerModal" class="scanner-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-label="Student Attendance Scanner">
    <div class="scanner-modal-card">
        
        <!-- Header & Top Floating Bar -->
        <div class="scanner-top-bar">
            <!-- Mode Switcher Tabs: QR vs Code -->
            <div class="scanner-mode-switcher">
                <button type="button" id="tabScanMode" class="scanner-mode-tab active" onclick="switchScannerMode('scan')">
                    <i class="bi bi-qr-code-scan me-1"></i> Scan QR
                </button>
                <button type="button" id="tabCodeMode" class="scanner-mode-tab" onclick="switchScannerMode('code')">
                    <i class="bi bi-key-fill me-1"></i> Enter Code
                </button>
            </div>
            
            <div class="scanner-top-actions">
                <button type="button" id="torchCameraBtn" onclick="toggleTorch()" class="scanner-icon-btn" title="Toggle Flashlight">
                    <i class="bi bi-lightning-charge"></i>
                </button>
                <button type="button" id="flipCameraBtn" onclick="toggleCameraFacing()" class="scanner-icon-btn" title="Flip Camera">
                    <i class="bi bi-camera-reverse"></i>
                </button>
                <button type="button" onclick="closeStudentScanner()" class="scanner-icon-btn close-btn" title="Close">
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
                <div class="scanner-guide-badge">
                    <i class="bi bi-viewfinder me-1"></i> Point at screen
                </div>

                <!-- Processing Overlay -->
                <div id="scannerProcessingOverlay" class="scanner-processing-overlay" style="display: none;">
                    <div class="spinner-border text-warning mb-3" style="width: 3.2rem; height: 3.2rem; border-width: 3px;" role="status"></div>
                    <div class="processing-title">Recording Attendance...</div>
                    <div class="processing-sub">Verifying session & GPS proximity</div>
                </div>

                <!-- Fallback Notice (Permission Blocked / Unsupported) -->
                <div id="scannerFallbackNotice" class="scanner-fallback-box" style="display: none;">
                    <div class="fallback-icon-wrap">
                        <i class="bi bi-camera-video-off"></i>
                    </div>
                    <h5 class="fallback-title">Camera Inactive</h5>
                    <p id="scannerFallbackText" class="fallback-text">Please allow camera permissions or switch to Code entry.</p>
                    <div class="d-flex justify-content-center gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="requestCameraAgain()" style="border-radius: 12px; font-weight: 700;">
                            <i class="bi bi-arrow-repeat me-1"></i> Retry Camera
                        </button>
                        <button type="button" class="btn btn-sm btn-warning text-dark" onclick="switchScannerMode('code')" style="border-radius: 12px; font-weight: 700;">
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
                <button type="button" class="btn scanner-secondary-action-btn w-100" onclick="switchScannerMode('scan')">
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

            <!-- Action Buttons -->
            <div class="d-flex gap-2 w-100">
                <button type="button" id="resultDoneBtn" onclick="finishScanAndRefresh()" class="btn scanner-primary-action-btn">
                    <i class="bi bi-check-lg me-1"></i> Back to Dashboard
                </button>
                <button type="button" id="resultRetryBtn" onclick="resetScannerView()" class="btn scanner-secondary-action-btn" style="display: none;">
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
    padding: 28px 24px 24px;
    color: #f3e7cd;
    text-align: center;
    position: relative;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.7), 0 0 30px rgba(239, 68, 68, 0.15);
    animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

@keyframes popIn {
    0% { opacity: 0; transform: scale(0.88) translateY(20px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}

@keyframes fadeIn {
    0% { opacity: 0; }
    100% { opacity: 1; }
}

.outside-range-close-btn {
    position: absolute;
    top: 16px;
    right: 16px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #f3e7cd;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.outside-range-close-btn:hover {
    background: rgba(239, 68, 68, 0.25);
    color: #f87171;
    transform: rotate(90deg);
}

.outside-range-icon-pulse {
    width: 76px;
    height: 76px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    position: relative;
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6);
    animation: pulseGps 2s infinite;
}

@keyframes pulseGps {
    0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6); }
    70% { box-shadow: 0 0 0 16px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

.outside-range-icon-inner {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ef4444, #991b1b);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 1.7rem;
}

.outside-range-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 999px;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.35);
    color: #f87171;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 10px;
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
    min-height: 270px;
    max-height: 320px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1.5px solid rgba(207, 164, 111, 0.35);
    box-shadow: inset 0 0 35px rgba(0, 0, 0, 0.85);
}

.scanner-reader-feed {
    width: 100% !important;
    min-height: 250px !important;
}

.scanner-reader-feed video {
    border-radius: 20px !important;
    object-fit: cover !important;
    width: 100% !important;
    max-height: 310px !important;
}

.scanner-reader-feed __scan_region__ {
    border: none !important;
}

/* Reticle Corner Markers */
.reticle-corner {
    position: absolute;
    width: 26px;
    height: 26px;
    border-color: #cfa46f;
    border-style: solid;
    border-width: 0;
    z-index: 12;
    pointer-events: none;
    filter: drop-shadow(0 0 6px rgba(207, 164, 111, 0.8));
}

.reticle-corner.top-left {
    top: 24px;
    left: 24px;
    border-top-width: 3.5px;
    border-left-width: 3.5px;
    border-top-left-radius: 12px;
}

.reticle-corner.top-right {
    top: 24px;
    right: 24px;
    border-top-width: 3.5px;
    border-right-width: 3.5px;
    border-top-right-radius: 12px;
}

.reticle-corner.bottom-left {
    bottom: 24px;
    left: 24px;
    border-bottom-width: 3.5px;
    border-left-width: 3.5px;
    border-bottom-left-radius: 12px;
}

.reticle-corner.bottom-right {
    bottom: 24px;
    right: 24px;
    border-bottom-width: 3.5px;
    border-right-width: 3.5px;
    border-bottom-right-radius: 12px;
}

/* Laser Scan Line */
.scanner-laser-line {
    position: absolute;
    left: 12%;
    right: 12%;
    height: 3px;
    background: linear-gradient(90deg, transparent, #cfa46f 30%, #ffd700 50%, #cfa46f 70%, transparent);
    box-shadow: 0 0 16px #ffd700, 0 0 30px rgba(207, 164, 111, 0.6);
    z-index: 15;
    animation: modernLaserScan 2s ease-in-out infinite;
    pointer-events: none;
}

@keyframes modernLaserScan {
    0% { top: 18%; opacity: 0.3; }
    50% { top: 78%; opacity: 1; }
    100% { top: 18%; opacity: 0.3; }
}

/* Guidance Badge */
.scanner-guide-badge {
    position: absolute;
    bottom: 12px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(18, 16, 14, 0.75);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #f3e7cd;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 99px;
    z-index: 16;
    pointer-events: none;
}

/* Processing Overlay */
.scanner-processing-overlay {
    position: absolute;
    inset: 0;
    background: rgba(14, 13, 12, 0.9);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    z-index: 25;
    flex-direction: column;
    align-items: center;
    justify-content: center;
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
    z-index: 10;
}

.fallback-icon-wrap {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
}

.fallback-title {
    font-weight: 700;
    color: #ffffff;
    font-size: 1rem;
    margin-bottom: 2px;
}

.fallback-text {
    font-size: 0.8rem;
    color: #b39b82;
    max-width: 250px;
    margin: 0 auto;
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
    margin-bottom: 20px;
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
        min-height: 90dvh;
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
        min-height: 290px;
        max-height: 350px;
        border-radius: 26px;
    }

    .scanner-reader-feed video {
        max-height: 340px !important;
        border-radius: 24px !important;
    }

    .scanner-title {
        font-size: 1.35rem;
    }

    .code-entry-input {
        font-size: 2.4rem !important;
        letter-spacing: 6px !important;
        padding: 14px 10px !important;
    }

    .reticle-corner {
        width: 32px;
        height: 32px;
    }

    .reticle-corner.top-left { top: 20px; left: 20px; }
    .reticle-corner.top-right { top: 20px; right: 20px; }
    .reticle-corner.bottom-left { bottom: 20px; left: 20px; }
    .reticle-corner.bottom-right { bottom: 20px; right: 20px; }
}
</style>

<script nonce="{{ csp_nonce() }}" src="{{ asset('js/html5-qrcode.min.js') }}?v={{ filemtime(public_path('js/html5-qrcode.min.js')) }}"></script>
<script nonce="{{ csp_nonce() }}">
let html5QrScanner = null;
let currentFacingMode = "environment";
let torchEnabled = false;
let studentGeoCoords = null;
let currentScannerMode = 'scan'; // 'scan' | 'code'

// Auto-capture GPS coords quietly for faster validation
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        pos => { studentGeoCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude, acc: pos.coords.accuracy }; },
        () => {},
        { enableHighAccuracy: true, timeout: 5000 }
    );
}

function switchScannerMode(mode) {
    currentScannerMode = mode;
    const tabScan = document.getElementById('tabScanMode');
    const tabCode = document.getElementById('tabCodeMode');
    const scanView = document.getElementById('scannerActiveView');
    const codeView = document.getElementById('scannerCodeView');
    const torchBtn = document.getElementById('torchCameraBtn');
    const flipBtn = document.getElementById('flipCameraBtn');

    if (!tabScan || !tabCode || !scanView || !codeView) return;

    if (mode === 'scan') {
        tabScan.classList.add('active');
        tabCode.classList.remove('active');
        scanView.style.display = 'block';
        codeView.style.display = 'none';
        if (torchBtn) torchBtn.style.display = 'flex';
        if (flipBtn) flipBtn.style.display = 'flex';
        startHtml5Scanner();
    } else {
        tabCode.classList.add('active');
        tabScan.classList.remove('active');
        scanView.style.display = 'none';
        codeView.style.display = 'block';
        if (torchBtn) torchBtn.style.display = 'none';
        if (flipBtn) flipBtn.style.display = 'none';

        // Stop camera while typing to save battery
        if (html5QrScanner) {
            html5QrScanner.stop().then(() => {
                html5QrScanner.clear();
            }).catch(() => {});
            html5QrScanner = null;
        }

        setTimeout(() => {
            const input = document.getElementById('directSessionCodeInput');
            if (input) input.focus();
        }, 100);
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

function openStudentScanner(initialMode = 'scan') {
    const modal = document.getElementById('studentScannerModal');
    if (!modal) return;
    modal.style.display = 'flex';
    resetScannerView();

    switchScannerMode(initialMode);
}

function startHtml5Scanner() {
    const fallbackNotice = document.getElementById('scannerFallbackNotice');
    if (fallbackNotice) fallbackNotice.style.display = 'none';

    try {
        if (typeof Html5Qrcode !== 'undefined') {
            if (html5QrScanner) {
                html5QrScanner.stop().catch(() => {}).finally(() => initScannerInstance());
            } else {
                initScannerInstance();
            }
        } else {
            showCameraError("Scanner engine is loading. You can also enter the 6-digit code.");
        }
    } catch (e) {
        showCameraError("Camera initialization failed: " + e.message);
    }
}

async function initScannerInstance() {
    try {
        if (!html5QrScanner) {
            html5QrScanner = new Html5Qrcode("reader");
        }
        const isMobile = window.innerWidth <= 640;
        const config = {
            fps: 15,
            qrbox: function(viewfinderWidth, viewfinderHeight) {
                const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                const qrboxSize = Math.floor(minEdge * 0.72);
                return { width: Math.max(qrboxSize, 180), height: Math.max(qrboxSize, 180) };
            },
            aspectRatio: 1.0,
            showTorchButtonIfSupported: true
        };

        // Try standard facingMode
        try {
            await html5QrScanner.start(
                { facingMode: currentFacingMode },
                config,
                onQrScanSuccess
            );
            const laser = document.getElementById('scannerLaser');
            if (laser) laser.style.display = 'block';
            return;
        } catch (firstErr) {
            console.warn("FacingMode start failed, trying camera enumeration fallback:", firstErr);
        }

        // Camera enumeration fallback for multi-camera phones
        const cameras = await Html5Qrcode.getCameras();
        if (cameras && cameras.length > 0) {
            let selectedCamera = cameras[0];
            const backCam = cameras.find(c => /back|rear|environment/i.test(c.label));
            if (backCam && currentFacingMode === 'environment') {
                selectedCamera = backCam;
            } else if (currentFacingMode === 'user') {
                const frontCam = cameras.find(c => /front|user|selfie/i.test(c.label));
                if (frontCam) selectedCamera = frontCam;
            }

            await html5QrScanner.start(
                selectedCamera.id,
                config,
                onQrScanSuccess
            );
            const laser = document.getElementById('scannerLaser');
            if (laser) laser.style.display = 'block';
        } else {
            throw new Error("No cameras detected on this device.");
        }
    } catch (err) {
        console.warn("Camera start failed completely:", err);
        showCameraError("Camera access unavailable or permission denied. Tap 'Use Code' to enter the 6-digit code.");
    }
}

function showCameraError(msg) {
    const notice = document.getElementById('scannerFallbackNotice');
    const text = document.getElementById('scannerFallbackText');
    const laser = document.getElementById('scannerLaser');
    if (notice) notice.style.display = 'block';
    if (text) text.textContent = msg;
    if (laser) laser.style.display = 'none';
}

function requestCameraAgain() {
    startHtml5Scanner();
}

function toggleCameraFacing() {
    currentFacingMode = currentFacingMode === "environment" ? "user" : "environment";
    startHtml5Scanner();
    if (window.triggerHaptic) window.triggerHaptic('light');
}

function toggleTorch() {
    if (!html5QrScanner) return;
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
    const modal = document.getElementById('studentScannerModal');
    if (modal) modal.style.display = 'none';
    if (html5QrScanner) {
        html5QrScanner.stop().then(() => {
            html5QrScanner.clear();
        }).catch(() => {});
        html5QrScanner = null;
    }
}

function resetScannerView() {
    const activeView = document.getElementById('scannerActiveView');
    const codeView = document.getElementById('scannerCodeView');
    const resultView = document.getElementById('scannerResultView');
    const overlay = document.getElementById('scannerProcessingOverlay');
    const codeInput = document.getElementById('directSessionCodeInput');

    if (activeView) activeView.style.display = 'block';
    if (codeView) codeView.style.display = 'none';
    if (resultView) resultView.style.display = 'none';
    if (overlay) overlay.style.display = 'none';
    if (codeInput) codeInput.value = '';
    
    const modal = document.getElementById('studentScannerModal');
    if (currentScannerMode === 'scan' && !html5QrScanner && modal && modal.style.display === 'flex') {
        startHtml5Scanner();
    }
}

async function onQrScanSuccess(decodedText) {
    if (html5QrScanner) {
        html5QrScanner.stop().catch(() => {});
    }

    const overlay = document.getElementById('scannerProcessingOverlay');
    const laser = document.getElementById('scannerLaser');
    if (overlay) overlay.style.display = 'flex';
    if (laser) laser.style.display = 'none';

    // Play quick scan beep & haptic
    playScanBeep();
    if (window.triggerHaptic) window.triggerHaptic('medium');

    // Fetch fresh GPS if not present
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
        token: decodedText.trim(),
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
    document.getElementById('scannerActiveView').style.display = 'none';
    document.getElementById('scannerCodeView').style.display = 'none';
    document.getElementById('scannerResultView').style.display = 'block';

    const iconBox = document.getElementById('resultStatusIcon');
    const badge = document.getElementById('resultBadge');
    const title = document.getElementById('resultTitle');
    const subtitle = document.getElementById('resultSubtitle');
    const retryBtn = document.getElementById('resultRetryBtn');
    const doneBtn = document.getElementById('resultDoneBtn');

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
    } else {
        iconBox.style.background = 'rgba(16, 185, 129, 0.15)';
        iconBox.style.border = '2px solid rgba(16, 185, 129, 0.4)';
        iconBox.innerHTML = '<i class="bi bi-check2-circle" style="color: #34d399;"></i>';

        title.textContent = 'Attendance Recorded Successfully!';
        subtitle.textContent = `Your attendance has been recorded for ${data.subject || 'this class'}.`;

        const isPresent = (data.status || 'Present') === 'Present';
        badge.className = isPresent ? 'badge bg-success' : 'badge bg-warning text-dark';
        badge.textContent = data.status || 'Present';

        playSuccessChime();
        if (window.triggerHaptic) window.triggerHaptic('success');
    }

    document.getElementById('resultSubject').textContent = (data.subject || 'Subject') + (data.subject_code ? ' (' + data.subject_code + ')' : '');
    document.getElementById('resultInstructor').textContent = data.instructor || 'Instructor';
    document.getElementById('resultSection').textContent = data.section || 'Regular';
    document.getElementById('resultTimestamp').textContent = (data.date || '') + (data.time ? ' at ' + data.time : '');
}

function renderScanError(data) {
    document.getElementById('scannerActiveView').style.display = 'none';
    document.getElementById('scannerCodeView').style.display = 'none';
    document.getElementById('scannerResultView').style.display = 'block';

    const iconBox = document.getElementById('resultStatusIcon');
    const title = document.getElementById('resultTitle');
    const subtitle = document.getElementById('resultSubtitle');
    const retryBtn = document.getElementById('resultRetryBtn');
    const doneBtn = document.getElementById('resultDoneBtn');

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
    playScanBeep();
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
        osc.frequency.setValueAtTime(800, ctx.currentTime);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
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
        osc.frequency.setValueAtTime(523.25, ctx.currentTime);
        osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.1);
        osc.frequency.setValueAtTime(783.99, ctx.currentTime + 0.2);
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
        osc.start();
        osc.stop(ctx.currentTime + 0.5);
    } catch(e) {}
}

// Auto open scanner if directed with URL query
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open_scanner') === '1') {
        setTimeout(() => { openStudentScanner(); }, 150);
    } else if (urlParams.get('open_code') === '1') {
        setTimeout(() => { openStudentScanner('code'); }, 150);
    }
});
</script>
