@extends('layouts.app')
@section('content')
<style>
    .verify-wrapper {
        display: flex; align-items: center; justify-content: center;
        min-height: calc(100vh - 64px); padding: 20px;
        background: linear-gradient(135deg, #f8f0f0 0%, #f1f5f9 50%, #f0f4ff 100%);
    }
    .verify-card {
        max-width: 410px; width: 100%; border-radius: 24px;
        padding: 28px 24px; background: white;
        box-shadow: 0 20px 60px rgba(0,0,0,0.1); text-align: center;
    }

    /* Biometric Method Selector */
    .method-selector {
        display: flex; background: #f1f5f9; border-radius: 14px;
        padding: 4px; margin-bottom: 20px; gap: 4px;
    }
    .method-btn {
        flex: 1; padding: 10px 14px; border: none; border-radius: 10px;
        font-size: 0.82rem; font-weight: 700; color: #64748b;
        background: transparent; cursor: pointer; transition: all 0.25s ease;
        display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .method-btn.active {
        background: white; color: #800000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    /* Profile Photo Reference Card */
    .profile-match-header {
        background: linear-gradient(135deg, #fffbf0 0%, #fef3c7 100%);
        border: 1.5px solid #fde68a; border-radius: 16px;
        padding: 12px 14px; margin-bottom: 18px;
        display: flex; align-items: center; gap: 12px; text-align: left;
    }
    .profile-match-avatar-wrap {
        position: relative; width: 48px; height: 48px; flex-shrink: 0;
    }
    .profile-match-avatar {
        width: 48px; height: 48px; border-radius: 50%; object-fit: cover;
        border: 2px solid #b45309; box-shadow: 0 3px 10px rgba(180,83,9,0.2);
    }
    .profile-match-badge {
        position: absolute; bottom: -2px; right: -2px;
        width: 18px; height: 18px; border-radius: 50%;
        background: #16a34a; color: white;
        font-size: 0.65rem; display: flex; align-items: center; justify-content: center;
        border: 2px solid white;
    }
    .profile-match-info { flex: 1; min-width: 0; }
    .profile-match-label {
        font-size: 0.68rem; font-weight: 700; color: #b45309;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .profile-match-name {
        font-size: 0.92rem; font-weight: 800; color: #78350f;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .profile-match-sub {
        font-size: 0.72rem; color: #92400e; font-weight: 500;
    }

    /* Camera Viewport & HUD */
    .camera-viewport-card {
        position: relative; width: 100%; height: 280px;
        border-radius: 20px; overflow: hidden;
        background: #090d16; box-shadow: 0 12px 36px rgba(0,0,0,0.25);
        margin-bottom: 14px; display: flex; align-items: center; justify-content: center;
    }
    .camera-video {
        width: 100%; height: 100%; object-fit: cover;
        transform: scaleX(-1);
    }
    .face-reticle-overlay {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        pointer-events: none;
    }
    .face-oval-guide {
        position: relative; width: 180px; height: 230px;
        border: 2.5px dashed rgba(255, 255, 255, 0.7);
        border-radius: 50% / 55%;
        box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.45);
        transition: all 0.3s ease;
    }
    .face-oval-guide.matched {
        border-color: #10b981 !important;
        border-style: solid !important;
        box-shadow: 0 0 0 9999px rgba(16, 185, 129, 0.2), 0 0 25px rgba(16, 185, 129, 0.8) !important;
    }
    .face-oval-guide.mismatch {
        border-color: #ef4444 !important;
        border-style: solid !important;
        box-shadow: 0 0 0 9999px rgba(239, 68, 68, 0.25), 0 0 25px rgba(239, 68, 68, 0.8) !important;
    }
    .face-laser-line {
        position: absolute; left: 10%; right: 10%; height: 2.5px;
        background: linear-gradient(90deg, transparent, #38bdf8, #818cf8, transparent);
        box-shadow: 0 0 12px #38bdf8;
        border-radius: 50%;
        animation: laserScan 2.2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    }
    @keyframes laserScan {
        0% { top: 8%; opacity: 0; }
        15% { opacity: 1; }
        85% { opacity: 1; }
        100% { top: 92%; opacity: 0; }
    }
    .face-corner {
        position: absolute; width: 14px; height: 14px;
        border-color: #cfa46f; border-style: solid;
    }
    .face-corner.tl { top: 12px; left: 18px; border-width: 3px 0 0 3px; border-top-left-radius: 8px; }
    .face-corner.tr { top: 12px; right: 18px; border-width: 3px 3px 0 0; border-top-right-radius: 8px; }
    .face-corner.bl { bottom: 12px; left: 18px; border-width: 0 0 3px 3px; border-bottom-left-radius: 8px; }
    .face-corner.br { bottom: 12px; right: 18px; border-width: 0 3px 3px 0; border-bottom-right-radius: 8px; }

    /* Camera Floating Tools */
    .camera-floating-controls {
        position: absolute; top: 12px; right: 12px;
        display: flex; gap: 8px; z-index: 5;
    }
    .cam-tool-btn {
        width: 34px; height: 34px; border-radius: 50%;
        background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(6px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white; font-size: 0.85rem;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.2s;
    }
    .cam-tool-btn:hover { background: rgba(0, 0, 0, 0.8); transform: scale(1.05); }

    /* Live status badge on camera */
    .camera-status-pill {
        position: absolute; bottom: 12px;
        background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white; border-radius: 999px;
        padding: 5px 14px; font-size: 0.76rem; font-weight: 700;
        display: flex; align-items: center; gap: 6px; z-index: 5;
    }
    .status-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: #22c55e;
    }
    .status-dot.pulsing {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
        animation: pulseGreen 1.5s infinite;
    }
    .status-dot.err {
        background: #ef4444;
        box-shadow: 0 0 8px #ef4444;
    }

    /* Buttons */
    .btn-verify-face {
        width: 100%; padding: 13px;
        background: linear-gradient(135deg, #800000, #a31d1d);
        color: white; font-weight: 800; font-size: 0.92rem;
        border: none; border-radius: 14px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        box-shadow: 0 8px 24px rgba(128, 0, 0, 0.25);
        transition: all 0.25s ease;
    }
    .btn-verify-face:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(128, 0, 0, 0.35); }
    .btn-verify-face:disabled { opacity: 0.65; cursor: not-allowed; transform: none; }
    .btn-retry-face {
        width: 100%; padding: 11px;
        background: #f1f5f9; color: #475569;
        font-weight: 700; font-size: 0.88rem;
        border: 1px solid #cbd5e1; border-radius: 12px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 6px;
        transition: all 0.2s;
    }
    .btn-retry-face:hover { background: #e2e8f0; }
    .subject-pill {
        background: #fff5f5; border: 1.5px solid #fecaca;
        border-radius: 12px; padding: 12px 16px; margin-bottom: 24px; text-align: left;
    }
    .subject-pill-label { font-size: .65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
    .subject-pill-name { font-size: .95rem; font-weight: 700; color: #1e293b; margin-top: 3px; }

    /* Step dots */
    .step-row { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 28px; }
    .sdot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; transition: all .4s; }
    .sdot.done    { background: #16a34a; color: white; }
    .sdot.active  { background: #800000; color: white; box-shadow: 0 0 0 4px rgba(128,0,0,.15); }
    .sdot.pending { background: #f1f5f9; color: #94a3b8; }
    .sline { flex: 1; height: 2px; background: #f1f5f9; max-width: 40px; transition: background .4s; }
    .sline.done { background: #16a34a; }

    /* Status icon */
    .v-icon {
        width: 80px; height: 80px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 16px; font-size: 2rem; transition: all .4s;
    }
    .v-title { font-size: 1.15rem; font-weight: 800; color: #1e293b; margin-bottom: 6px; }
    .v-sub   { font-size: .85rem; color: #64748b; line-height: 1.5; margin-bottom: 0; }

    /* Status messages */
    .v-msg { border-radius: 10px; padding: 10px 14px; font-size: .82rem; margin-top: 16px; display: none; }
    .v-ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
    .v-err { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
    .v-info{ background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }

    /* Retry button â€” only shown on fingerprint error */
    .retry-btn {
        width: 100%; padding: 13px; margin-top: 14px;
        background: linear-gradient(135deg, #16a34a, #22c55e);
        color: white; font-weight: 700; font-size: .9rem;
        border: none; border-radius: 12px; cursor: pointer;
        display: none; align-items: center; justify-content: center; gap: 8px;
        transition: all .25s;
    }
    .retry-btn:hover { transform: translateY(-2px); }

    /* Spinner */
    .spin { display: inline-block; width: 20px; height: 20px; border: 3px solid rgba(255,255,255,.3); border-top-color: white; border-radius: 50%; animation: spin .7s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── OUTSIDE RANGE POPUP DIALOG ── */
    .outside-range-popup-backdrop {
        position: fixed; inset: 0;
        background: rgba(10, 5, 5, 0.88);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        z-index: 100002;
        display: flex; align-items: center; justify-content: center;
        padding: 16px;
        animation: fadeIn 0.25s ease-out forwards;
    }
    .outside-range-popup-card {
        background: linear-gradient(180deg, #241616 0%, #150d0d 100%);
        border: 1.5px solid rgba(239, 68, 68, 0.45);
        border-radius: 24px;
        max-width: 440px; width: 100%;
        padding: 28px 24px 24px;
        color: #f3e7cd; text-align: center;
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
        position: absolute; top: 16px; right: 16px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #f3e7cd; width: 34px; height: 34px;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.2s;
    }
    .outside-range-close-btn:hover { background: rgba(239, 68, 68, 0.25); color: #f87171; transform: rotate(90deg); }
    .outside-range-icon-pulse {
        width: 76px; height: 76px; border-radius: 50%;
        background: rgba(239, 68, 68, 0.12);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 16px; position: relative;
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6);
        animation: pulseGps 2s infinite;
    }
    @keyframes pulseGps {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6); }
        70% { box-shadow: 0 0 0 16px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    .outside-range-icon-inner {
        width: 54px; height: 54px; border-radius: 50%;
        background: linear-gradient(135deg, #ef4444, #991b1b);
        display: flex; align-items: center; justify-content: center;
        color: #ffffff; font-size: 1.7rem;
    }
    .outside-range-badge {
        display: inline-flex; align-items: center;
        padding: 4px 12px; border-radius: 999px;
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.35);
        color: #f87171; font-size: 0.72rem; font-weight: 800;
        letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 10px;
    }
    .outside-range-headline { font-size: 1.35rem; font-weight: 800; color: #ffffff; margin-bottom: 8px; }
    .outside-range-desc { font-size: 0.88rem; color: #d1c4b2; line-height: 1.5; margin-bottom: 20px; }
    .outside-range-metrics-box {
        display: flex; align-items: center; justify-content: space-between;
        background: rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(207, 164, 111, 0.2);
        border-radius: 16px; padding: 14px 18px; margin-bottom: 18px;
    }
    .outside-range-metric-col { flex: 1; text-align: center; }
    .outside-range-metric-col .metric-label { font-size: 0.72rem; font-weight: 700; color: #b39b82; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; }
    .outside-range-metric-col .metric-val { font-size: 1.45rem; font-weight: 800; font-family: monospace; }
    .outside-range-metric-col.detected .metric-val { color: #f87171; }
    .outside-range-metric-col.allowed .metric-val { color: #fbbf24; }
    .outside-range-metric-col .metric-sub { font-size: 0.72rem; font-weight: 600; }
    .outside-range-divider { width: 1px; height: 48px; background: rgba(255, 255, 255, 0.1); margin: 0 12px; }
    .outside-range-tip-box {
        display: flex; align-items: flex-start;
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.25);
        border-radius: 12px; padding: 12px 14px; font-size: 0.8rem;
        color: #fde68a; text-align: left; margin-bottom: 22px; line-height: 1.45;
    }
    .outside-range-actions { display: flex; flex-direction: column; gap: 10px; }
    .outside-range-btn-primary {
        background: linear-gradient(135deg, #cfa46f, #a07a4a);
        color: #110a0a; font-weight: 800; font-size: 0.92rem;
        padding: 12px 20px; border-radius: 14px; border: none; cursor: pointer;
        transition: all 0.2s; box-shadow: 0 4px 16px rgba(207, 164, 111, 0.3);
        display: flex; align-items: center; justify-content: center;
    }
    .outside-range-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 22px rgba(207, 164, 111, 0.4); }
    .outside-range-btn-secondary {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(207, 164, 111, 0.3);
        color: #f3e7cd; font-weight: 700; font-size: 0.88rem;
        padding: 10px 18px; border-radius: 14px; cursor: pointer;
        transition: all 0.2s; display: flex; align-items: center; justify-content: center;
    }
    .outside-range-btn-secondary:hover { background: rgba(207, 164, 111, 0.15); color: #ffffff; }
    .outside-range-btn-text {
        background: transparent; border: none; color: #b39b82;
        font-size: 0.82rem; font-weight: 600; padding: 6px; cursor: pointer; transition: color 0.2s;
    }
    .outside-range-btn-text:hover { color: #f3e7cd; }
</style>

<div class="verify-wrapper">
    <div class="verify-card">

        {{-- Subject info --}}
        <div class="subject-pill">
            <div class="subject-pill-label">Clocking in for</div>
            <div class="subject-pill-name">{{ $subject->name }}</div>
            <div style="font-size:.75rem;color:#64748b;margin-top:2px;">
                <i class="bi bi-clock me-1"></i>
                @if($subject->start_time)
                    {{ \Carbon\Carbon::parse($subject->start_time)->format('h:i A') }}
                @endif
                &nbsp;Â·&nbsp; {{ $subject->code }}
            </div>
        </div>

        {{-- Step indicators --}}
        <div class="step-row">
            <div class="sdot done"    id="dot1"><i class="bi bi-check2"></i></div>
            <div class="sline done"   id="line1"></div>
            <div class="sdot active"  id="dot2">2</div>
            <div class="sline"        id="line2"></div>
            <div class="sdot pending" id="dot3">3</div>
        </div>

        @if(isset($status) && $status === 'setup')
        <div style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:18px;padding:22px 18px;margin-bottom:18px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:50%;background:rgba(245,158,11,0.15);color:#d97706;display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 12px;">
                <i class="bi bi-person-bounding-box"></i>
            </div>
            <div style="font-size:1.1rem;font-weight:800;color:#92400e;margin-bottom:6px;">Biometric Verification Required</div>
            <p style="font-size:0.85rem;color:#b45309;line-height:1.45;margin-bottom:16px;">
                {{ $message ?? 'You must have a registered profile photo or fingerprint on this device before clocking in with QR attendance.' }}
            </p>
            <div class="d-flex flex-column gap-2">
                <a href="{{ route('settings') }}#tab-profile" class="btn w-100" style="background:linear-gradient(135deg,#cfa46f,#a07a4a);color:white;font-weight:700;padding:12px;border-radius:12px;text-decoration:none;display:block;">
                    <i class="bi bi-camera me-1"></i> Upload Profile Photo
                </a>
                <a href="{{ route('settings') }}#tab-fingerprint" onclick="localStorage.setItem('active_settings_tab', 'fingerprint');" class="btn w-100" style="background:#f1f5f9;color:#334155;font-weight:700;padding:10px;border-radius:12px;text-decoration:none;display:block;">
                    <i class="bi bi-fingerprint me-1"></i> Or Register Fingerprint
                </a>
            </div>
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

        {{-- Registered Profile Photo Banner --}}
        @if(!empty($hasFaceMethod))
        <div class="profile-match-header" id="profileMatchHeader" style="display: none;">
            <div class="profile-match-avatar-wrap">
                <img src="{{ $profilePhotoUrl ?? asset('images/default-avatar.png') }}" alt="{{ optional($user)->name ?? 'Student' }}" class="profile-match-avatar" id="refAvatarImg" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(optional($user)->name ?? 'Student') }}&background=800000&color=fff';">
                <div class="profile-match-badge" title="Registered Reference Photo"><i class="bi bi-check-lg"></i></div>
            </div>
            <div class="profile-match-info">
                <div class="profile-match-label">Comparing against registered photo</div>
                <div class="profile-match-name">{{ optional($user)->name ?? 'Student' }}</div>
                <div class="profile-match-sub">Live camera biometric match</div>
            </div>
        </div>
        @endif

        {{-- Live Face Camera HUD --}}
        <div id="faceScanArea" style="display: none;">
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

                <div class="camera-floating-controls">
                    <button type="button" class="cam-tool-btn" id="flipCamBtn" onclick="toggleCameraFacing()" title="Flip Camera">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                </div>

                <div class="camera-status-pill" id="cameraStatusPill">
                    <span class="status-dot pulsing" id="cameraStatusDot"></span>
                    <span id="cameraStatusText">Align face within oval</span>
                </div>
            </div>

            <button type="button" class="btn-verify-face" id="btnCaptureFace" onclick="captureAndVerifyFace()">
                <i class="bi bi-person-check-fill"></i> Verify My Face
            </button>
            <button type="button" class="btn-retry-face mt-2" id="btnRetryFace" onclick="startFaceScan()" style="display: none;">
                <i class="bi bi-arrow-clockwise"></i> Scan Face Again
            </button>
        </div>

        {{-- Dynamic content area --}}
        <div id="vIcon"  class="v-icon" style="background:#eff6ff; @if(isset($status) && $status === 'setup') display:none; @endif">
            <i class="bi bi-geo-alt-fill" style="color:#2563eb;"></i>
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

<script>
var STUDENT_NUMBER = '{{ addslashes(Auth::user()->student_number) }}';
var QR_TOKEN = '{{ addslashes($token) }}';
var CSRF = '{{ csrf_token() }}';
var CLASSROOM_LAT = {{ isset($classroomLat) && $classroomLat !== null ? (float) $classroomLat : 'null' }};
var CLASSROOM_LNG = {{ isset($classroomLng) && $classroomLng !== null ? (float) $classroomLng : 'null' }};
var RADIUS_METERS = {{ isset($radiusMeters) && $radiusMeters !== null ? (int) $radiusMeters : (int) \App\Models\Setting::get('gps_radius', 50) }};

var fingerprintInProgress = false; // Add guard against multiple simultaneous calls
var HAS_FACE_METHOD = {{ !empty($hasFaceMethod) ? 'true' : 'false' }};
var HAS_FINGERPRINT = {{ !empty($hasFingerprint) ? 'true' : 'false' }};
var ACTIVE_METHOD = '{{ $defaultMethod ?? (!empty($hasFaceMethod) ? "face" : "fingerprint") }}';
var currentFacingMode = 'user';
var activeStream = null;
var autoCaptureTimer = null;
var faceVerificationInProgress = false;

function normalizeCoordinates(lat, lng) {
    let latitude = Number(lat);
    let longitude = Number(lng);
    if (isNaN(latitude) || isNaN(longitude)) return [0, 0];

    // Definite swap: lat cannot exceed 90 or be less than -90
    if (Math.abs(latitude) > 90 && Math.abs(longitude) <= 90) {
        const t = latitude;
        latitude = longitude;
        longitude = t;
    }
    // Regional swap detection (e.g. Philippines lat ~12, lng ~123)
    if (latitude > 50 && latitude <= 180 && longitude >= -90 && longitude <= 50) {
        const t = latitude;
        latitude = longitude;
        longitude = t;
    }
    return [latitude, longitude];
}

function calculateDistance(lat1, lon1, lat2, lon2) {
    var c1 = normalizeCoordinates(lat1, lon1);
    var c2 = normalizeCoordinates(lat2, lon2);
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
    var el = document.getElementById('vIcon');
    el.style.background = bg;
    el.innerHTML = '<i class="' + iconClass + '" style="color:' + iconColor + ';font-size:2rem;"></i>';
}
function setSpinner() {
    var el = document.getElementById('vIcon');
    el.style.background = '#fff5f5';
    el.innerHTML = '<div style="width:36px;height:36px;border:4px solid rgba(128,0,0,.15);border-top-color:#800000;border-radius:50%;animation:spin .7s linear infinite;"></div>';
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
const GPS_FAST_FALLBACK_MS = 3000;       // Accept a good enough location quickly
const GPS_QUICK_ACCEPT = 30;
const GPS_MAX_FAST_ACCEPT = 50;
const GPS_MAX_ACCEPTABLE_ACCURACY = 50;

function isNearBoundary(distance, accuracy) {
    return distance > RADIUS_METERS && distance <= RADIUS_METERS + Math.min(10, accuracy / 2);
}

function validFreshPosition(pos) {
    if (!pos || !pos.coords) return false;
    var c = pos.coords;
    var age = Date.now() - pos.timestamp;
    return age >= -5000 && age <= 10000 && Number.isFinite(c.latitude) &&
        Math.abs(c.latitude) <= 90 && Number.isFinite(c.longitude) &&
        Math.abs(c.longitude) <= 180 && Number.isFinite(c.accuracy) && c.accuracy > 0;
}

// Wait briefly for a second independent fix. Widely separated fixes indicate indoor drift.
function getFreshStablePosition() {
    return new Promise(function(resolve) {
        if (!navigator.geolocation) { resolve(null); return; }
        var readings = [];
        var watchId = null;
        var finished = false;
        var timer = null;
        var finish = function(result) {
            if (finished) return;
            finished = true;
            clearTimeout(timer);
            if (watchId !== null) navigator.geolocation.clearWatch(watchId);
            resolve(result);
        };
        timer = setTimeout(function() {
            if (!readings.length) { finish(null); return; }
            if (readings.length > 1) {
                // Do not send a single favorable outlier when fresh fixes disagree.
                var stable = readings.some(function(a, i) {
                    return readings.some(function(b, j) {
                        return i !== j && calculateDistance(a.coords.latitude, a.coords.longitude,
                            b.coords.latitude, b.coords.longitude) <= Math.max(12, a.coords.accuracy, b.coords.accuracy);
                    });
                });
                if (!stable) { finish(null); return; }
            }
            readings.sort(function(a, b) { return a.coords.accuracy - b.coords.accuracy; });
            finish(readings[0]);
        }, 12000);
        try {
            watchId = navigator.geolocation.watchPosition(function(pos) {
                if (!validFreshPosition(pos) || pos.coords.accuracy > GPS_MAX_ACCEPTABLE_ACCURACY) return;
                if (readings.some(function(previous) { return previous.timestamp === pos.timestamp; })) return;
                readings.push(pos);
                if (readings.length > 5) readings.shift();
                for (var i = 0; i < readings.length; i++) {
                    for (var j = i + 1; j < readings.length; j++) {
                        var a = readings[i], b = readings[j];
                        var separation = calculateDistance(a.coords.latitude, a.coords.longitude,
                            b.coords.latitude, b.coords.longitude);
                        if (separation <= Math.max(12, a.coords.accuracy, b.coords.accuracy)) {
                            finish(a.coords.accuracy <= b.coords.accuracy ? a : b);
                            return;
                        }
                    }
                }
            }, function(err) {
                if (err.code === 1) finish(null);
            }, { enableHighAccuracy: true, maximumAge: 0, timeout: 12000 });
        } catch (error) { finish(null); }
    });
}

var PAGE_STATUS = '{{ $status ?? "ready" }}';
window.addEventListener('load', function() { 
    if (PAGE_STATUS !== 'setup') {
        startGPS(); 
    }
});

function startGPS() {
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

    bestLocation = null;
    bestAccuracy = Infinity;

    var acceptBestLocation = function(pos, accuracyLabel) {
        if (locationWatcher !== null) {
            navigator.geolocation.clearWatch(locationWatcher);
            locationWatcher = null;
        }
        if (locationTimeoutId) {
            clearTimeout(locationTimeoutId);
            locationTimeoutId = null;
        }

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

            if (dist > RADIUS_METERS) {
                if (isNearBoundary(dist, accuracy)) showLocationUncertainError(dist, accuracy);
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
        console.log('GPS Success:', pos.coords.latitude, pos.coords.longitude, 'Accuracy:', pos.coords.accuracy + 'm');

        if (!validFreshPosition(pos)) return;
        if (pos.coords.accuracy < bestAccuracy) {
            bestAccuracy = pos.coords.accuracy;
            bestLocation = pos;
        }

        var isWithinBounds = false;
        if (RADIUS_METERS > 0 && CLASSROOM_LAT !== null && CLASSROOM_LNG !== null) {
            var rawDist = calculateDistance(pos.coords.latitude, pos.coords.longitude, CLASSROOM_LAT, CLASSROOM_LNG);
            isWithinBounds = (rawDist <= RADIUS_METERS);
        } else {
            isWithinBounds = true;
        }

        if (pos.coords.accuracy <= GPS_QUICK_ACCEPT && isWithinBounds) {
            acceptBestLocation(pos, 'Location confirmed');
            return;
        }

        if (pos.coords.accuracy <= 20 && isWithinBounds) {
            acceptBestLocation(pos, 'Location confirmed');
            return;
        }

        if (pos.coords.accuracy <= GPS_MAX_FAST_ACCEPT) {
            showMsg('info', '<i class="bi bi-info-circle"></i> Good location detected. Refining coordinates...');
            return;
        }

        if (pos.coords.accuracy <= GPS_MAX_ACCEPTABLE_ACCURACY) {
            showMsg('info', '<i class="bi bi-info-circle"></i> Acquiring satellite fix (±' + Math.round(pos.coords.accuracy) + 'm)...');
            return;
        }

        showMsg('info', '<i class="bi bi-info-circle"></i> Weak GPS signal (±' + Math.round(pos.coords.accuracy) + 'm). Refining location...');
    };

    var onError = function(err) {
        if (locationWatcher !== null) {
            navigator.geolocation.clearWatch(locationWatcher);
            locationWatcher = null;
        }
        if (locationTimeoutId) {
            clearTimeout(locationTimeoutId);
            locationTimeoutId = null;
        }

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
        if (bestLocation && bestAccuracy <= GPS_MAX_FAST_ACCEPT) {
            if (RADIUS_METERS > 0 && CLASSROOM_LAT !== null && CLASSROOM_LNG !== null) {
                var earlyDist = calculateDistance(bestLocation.coords.latitude, bestLocation.coords.longitude, CLASSROOM_LAT, CLASSROOM_LNG);
                if (earlyDist <= RADIUS_METERS) {
                    console.log('Fast fallback: inside boundary with accuracy ' + bestAccuracy + 'm, accepting');
                    acceptBestLocation(bestLocation, 'Location confirmed');
                    return;
                }
                console.log('Fast fallback: location appears outside at 3s (' + Math.round(earlyDist) + 'm, ±' + Math.round(bestAccuracy) + 'm); continuing to watch for refined fix...');
            } else {
                acceptBestLocation(bestLocation, 'Using best available location');
            }
        }
    }, GPS_FAST_FALLBACK_MS);

    locationTimeoutId = setTimeout(function() {
        if (locationWatcher !== null) {
            navigator.geolocation.clearWatch(locationWatcher);
            locationWatcher = null;
        }

        if (bestLocation) {
            if (bestAccuracy > 50) {
                console.warn('GPS timeout: best accuracy is too weak (±' + bestAccuracy + 'm)');
                showWeakGpsError(bestAccuracy);
                return;
            }
            console.log('GPS timeout: accepting best available location (±' + bestAccuracy + 'm)');
            acceptBestLocation(bestLocation, 'Using best available location');
            return;
        }

        onError({ code: 3 });
    }, options.timeout || GPS_TIMEOUT_MS);
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

async function submitAttendance(payload) {
    var token = QR_TOKEN;
    var isFace = (payload && payload.biometric_method === 'face');

    if (!isFace) {
        document.getElementById('vTitle').textContent = 'Confirming current location...';
        document.getElementById('vSub').textContent = 'Checking a fresh GPS fix after device verification.';
    } else {
        setCameraStatus('Confirming location & face...', 'pulsing');
    }

    var freshPosition = await getFreshStablePosition();

    if (!freshPosition || !freshPosition.coords ||
        !validFreshPosition(freshPosition) ||
        !Number.isFinite(freshPosition.coords.latitude) ||
        !Number.isFinite(freshPosition.coords.longitude) ||
        Math.abs(freshPosition.coords.latitude) > 90 ||
        Math.abs(freshPosition.coords.longitude) > 180 ||
        !Number.isFinite(freshPosition.coords.accuracy) ||
        freshPosition.coords.accuracy <= 0 || freshPosition.coords.accuracy > 50) {
        if (isFace) {
            faceVerificationInProgress = false;
            var cBtn = document.getElementById('btnCaptureFace');
            if (cBtn) { cBtn.disabled = false; cBtn.innerHTML = '<i class="bi bi-person-check-fill"></i> Verify My Face'; }
        }
        showWeakGpsError(freshPosition?.coords?.accuracy);
        return;
    }

    var latitude = freshPosition.coords.latitude;
    var longitude = freshPosition.coords.longitude;
    var accuracy = freshPosition.coords.accuracy;
    document.getElementById('latInput').value = latitude;
    document.getElementById('lngInput').value = longitude;
    document.getElementById('accuracyInput').value = accuracy;

    if (RADIUS_METERS > 0) {
        if (CLASSROOM_LAT === null || CLASSROOM_LNG === null) {
            if (isFace) {
                faceVerificationInProgress = false;
                var cBtn = document.getElementById('btnCaptureFace');
                if (cBtn) { cBtn.disabled = false; cBtn.innerHTML = '<i class="bi bi-person-check-fill"></i> Verify My Face'; }
            }
            showTeacherLocationMissingError();
            return;
        }
        var currentDistance = calculateDistance(latitude, longitude, CLASSROOM_LAT, CLASSROOM_LNG);
        if (currentDistance > RADIUS_METERS) {
            if (isFace) {
                faceVerificationInProgress = false;
                var cBtn = document.getElementById('btnCaptureFace');
                if (cBtn) { cBtn.disabled = false; cBtn.innerHTML = '<i class="bi bi-person-check-fill"></i> Verify My Face'; }
            }
            if (isNearBoundary(currentDistance, accuracy)) showLocationUncertainError(currentDistance, accuracy);
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
        location_timestamp_ms: freshPosition.timestamp,
        device_key: devKey,
        device_fingerprint: devKey
    };

    if (isFace) {
        reqData.biometric_method = 'face';
        reqData.live_frame = payload.live_frame;
    } else {
        reqData.credential = payload;
    }

    var xhr2 = new XMLHttpRequest();
    xhr2.open('POST', '{{ route("qr.verify.complete") }}', true);
    xhr2.withCredentials = true;
    xhr2.setRequestHeader('X-CSRF-TOKEN', CSRF);
    xhr2.setRequestHeader('Content-Type', 'application/json');
    xhr2.setRequestHeader('Accept', 'application/json');
    if (devKey) {
        xhr2.setRequestHeader('X-Device-Key', devKey);
        xhr2.setRequestHeader('X-Device-Fingerprint', devKey);
    }
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
            faceVerificationInProgress = false;
            setStep(4);

            if (isFace) {
                var oval = document.getElementById('faceOvalGuide');
                if (oval) oval.className = 'face-oval-guide matched';
                var checkEl = document.getElementById('faceSuccessCheck');
                if (checkEl) checkEl.style.display = 'flex';
                var laser = document.getElementById('faceLaserLine');
                if (laser) laser.style.display = 'none';

                setCameraStatus('Identity Confirmed! Clocking in...', 'pulsing');
                showMsg('ok', '<i class="bi bi-check-circle-fill me-1"></i> Live face verified against registered profile photo!');

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
            if (isFace) {
                faceVerificationInProgress = false;
                var cBtn = document.getElementById('btnCaptureFace');
                if (cBtn) { cBtn.disabled = false; cBtn.innerHTML = '<i class="bi bi-person-check-fill"></i> Verify My Face'; }
            }
            showTeacherLocationMissingError();
            return;
        }

        if (response.error_type === 'unreliable_gps') {
            if (isFace) {
                faceVerificationInProgress = false;
                var cBtn = document.getElementById('btnCaptureFace');
                if (cBtn) { cBtn.disabled = false; cBtn.innerHTML = '<i class="bi bi-person-check-fill"></i> Verify My Face'; }
            }
            showWeakGpsError(response.accuracy || accuracy);
            return;
        }

        if (response.error_type === 'location_uncertain' || response.error_type === 'stale_location') {
            if (isFace) {
                faceVerificationInProgress = false;
                var cBtn = document.getElementById('btnCaptureFace');
                if (cBtn) { cBtn.disabled = false; cBtn.innerHTML = '<i class="bi bi-person-check-fill"></i> Verify My Face'; }
            }
            showLocationUncertainError(response.distance, accuracy);
            return;
        }

        if (response.error_type === 'outside_classroom') {
            if (isFace) {
                faceVerificationInProgress = false;
                var cBtn = document.getElementById('btnCaptureFace');
                if (cBtn) { cBtn.disabled = false; cBtn.innerHTML = '<i class="bi bi-person-check-fill"></i> Verify My Face'; }
            }
            showOutsideClassroomError(response.distance || 0, response.radius || RADIUS_METERS);
            return;
        }

        if (isFace) {
            handleFaceError(response.message || 'Face matching failed. Please try again.', response.similarity);
        } else {
            showFpError(response.message || 'Clock-in failed. Please try again.');
        }
    };
    xhr2.onerror = function() {
        if (isFace) handleFaceError('Network error while clocking in. Please try again.');
        else showFpError('Network error while clocking in. Please try again.');
    };
    xhr2.send(JSON.stringify(reqData));
}

function startBiometric() {
    var selector = document.getElementById('methodSelector');
    if (selector && HAS_FACE_METHOD && HAS_FINGERPRINT) {
        selector.style.display = 'flex';
    }

    if (ACTIVE_METHOD === 'face' && HAS_FACE_METHOD) {
        startFaceScan();
    } else if (HAS_FINGERPRINT) {
        doFingerprint();
    } else if (HAS_FACE_METHOD) {
        startFaceScan();
    } else {
        showMsg('err', 'No biometric method available.');
    }
}

function switchMethod(method) {
    ACTIVE_METHOD = method;

    var btnFace = document.getElementById('btnMethodFace');
    var btnFp = document.getElementById('btnMethodFp');
    if (btnFace) btnFace.classList.toggle('active', method === 'face');
    if (btnFp) btnFp.classList.toggle('active', method === 'fingerprint');

    hideMsg();
    if (method === 'face') {
        document.getElementById('retryFpBtn').style.display = 'none';
        document.getElementById('vIcon').style.display = 'none';
        document.getElementById('vTitle').style.display = 'none';
        document.getElementById('vSub').style.display = 'none';
        startFaceScan();
    } else {
        stopCamera();
        document.getElementById('faceScanArea').style.display = 'none';
        var pHeader = document.getElementById('profileMatchHeader');
        if (pHeader) pHeader.style.display = 'none';

        document.getElementById('vIcon').style.display = 'flex';
        document.getElementById('vTitle').style.display = 'block';
        document.getElementById('vSub').style.display = 'block';
        doFingerprint();
    }
}

function startFaceScan() {
    stopCamera();
    faceVerificationInProgress = false;

    var scanArea = document.getElementById('faceScanArea');
    if (scanArea) scanArea.style.display = 'block';

    var pHeader = document.getElementById('profileMatchHeader');
    if (pHeader) pHeader.style.display = 'flex';

    document.getElementById('vIcon').style.display = 'none';
    document.getElementById('vTitle').style.display = 'none';
    document.getElementById('vSub').style.display = 'none';
    document.getElementById('retryFpBtn').style.display = 'none';
    hideMsg();

    var oval = document.getElementById('faceOvalGuide');
    if (oval) oval.className = 'face-oval-guide';
    var checkEl = document.getElementById('faceSuccessCheck');
    if (checkEl) checkEl.style.display = 'none';
    var laser = document.getElementById('faceLaserLine');
    if (laser) laser.style.display = 'block';
    var retryBtn = document.getElementById('btnRetryFace');
    if (retryBtn) retryBtn.style.display = 'none';
    var captureBtn = document.getElementById('btnCaptureFace');
    if (captureBtn) {
        captureBtn.disabled = false;
        captureBtn.innerHTML = '<i class="bi bi-person-check-fill"></i> Verify My Face';
    }

    setCameraStatus('Starting camera...', 'pulsing');

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setCameraStatus('Camera not supported', 'err');
        showMsg('err', 'Camera access is not supported by your browser. Please switch to Fingerprint or use a supported browser.');
        return;
    }

    navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: currentFacingMode,
            width: { ideal: 640 },
            height: { ideal: 640 }
        },
        audio: false
    }).then(function(stream) {
        activeStream = stream;
        var video = document.getElementById('faceCameraVideo');
        video.srcObject = stream;
        video.onloadedmetadata = function() {
            video.play();
            setCameraStatus('Align face within oval', 'pulsing');

            if (autoCaptureTimer) clearTimeout(autoCaptureTimer);
            autoCaptureTimer = setTimeout(function() {
                if (!faceVerificationInProgress && activeStream) {
                    captureAndVerifyFace();
                }
            }, 1800);
        };
    }).catch(function(err) {
        console.error('Camera error:', err);
        setCameraStatus('Camera access denied', 'err');
        showMsg('err', 'Unable to access device camera. Please allow camera permissions to verify your face.');
        if (HAS_FINGERPRINT) {
            var btn = document.getElementById('retryFpBtn');
            btn.innerHTML = '<i class="bi bi-fingerprint"></i> Use Fingerprint Instead';
            btn.onclick = function() { switchMethod('fingerprint'); };
            btn.style.display = 'flex';
        }
    });
}

function toggleCameraFacing() {
    currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
    var video = document.getElementById('faceCameraVideo');
    if (video) {
        video.style.transform = (currentFacingMode === 'user') ? 'scaleX(-1)' : 'none';
    }
    startFaceScan();
}

function stopCamera() {
    if (autoCaptureTimer) {
        clearTimeout(autoCaptureTimer);
        autoCaptureTimer = null;
    }
    if (activeStream) {
        activeStream.getTracks().forEach(function(track) {
            track.stop();
        });
        activeStream = null;
    }
    var video = document.getElementById('faceCameraVideo');
    if (video) {
        video.srcObject = null;
    }
}

function captureAndVerifyFace() {
    if (faceVerificationInProgress) return;
    if (autoCaptureTimer) {
        clearTimeout(autoCaptureTimer);
        autoCaptureTimer = null;
    }

    var video = document.getElementById('faceCameraVideo');
    if (!video || !activeStream || video.readyState < 2) {
        setCameraStatus('Waiting for camera feed...', 'pulsing');
        return;
    }

    faceVerificationInProgress = true;
    var captureBtn = document.getElementById('btnCaptureFace');
    if (captureBtn) {
        captureBtn.disabled = true;
        captureBtn.innerHTML = '<span class="spin me-1"></span> Analyzing Face...';
    }
    setCameraStatus('Comparing with profile photo...', 'pulsing');

    var canvas = document.getElementById('faceCaptureCanvas');
    var vw = video.videoWidth || 320;
    var vh = video.videoHeight || 320;
    var size = Math.min(vw, vh, 480);
    canvas.width = size;
    canvas.height = size;
    var ctx = canvas.getContext('2d');
    var sx = (vw - size) / 2;
    var sy = (vh - size) / 2;
    ctx.drawImage(video, sx, sy, size, size, 0, 0, size, size);

    var liveFrameBase64 = canvas.toDataURL('image/jpeg', 0.88);

    submitAttendance({
        biometric_method: 'face',
        live_frame: liveFrameBase64
    });
}

function setCameraStatus(text, dotClass) {
    var txtEl = document.getElementById('cameraStatusText');
    if (txtEl) txtEl.textContent = text;
    var dotEl = document.getElementById('cameraStatusDot');
    if (dotEl) dotEl.className = 'status-dot ' + (dotClass || '');
}

function handleFaceError(msg, similarity) {
    faceVerificationInProgress = false;
    var oval = document.getElementById('faceOvalGuide');
    if (oval) oval.className = 'face-oval-guide mismatch';

    var simText = similarity ? ' (' + Math.round(similarity) + '% match)' : '';
    setCameraStatus('Match failed' + simText, 'err');
    showMsg('err', '<i class="bi bi-exclamation-triangle-fill me-1"></i> ' + msg);

    var captureBtn = document.getElementById('btnCaptureFace');
    if (captureBtn) {
        captureBtn.disabled = false;
        captureBtn.innerHTML = '<i class="bi bi-person-check-fill"></i> Retry Face Verification';
    }

    var retryBtn = document.getElementById('btnRetryFace');
    if (retryBtn) retryBtn.style.display = 'block';

    if (HAS_FINGERPRINT) {
        var fpBtn = document.getElementById('retryFpBtn');
        if (fpBtn) {
            fpBtn.innerHTML = '<i class="bi bi-fingerprint"></i> Switch to Fingerprint';
            fpBtn.onclick = function() { switchMethod('fingerprint'); };
            fpBtn.style.display = 'flex';
        }
    }
}

window.addEventListener('beforeunload', function() {
    stopCamera();
});

function doFingerprint() {
    stopCamera();
    var scanArea = document.getElementById('faceScanArea');
    if (scanArea) scanArea.style.display = 'none';
    var pHeader = document.getElementById('profileMatchHeader');
    if (pHeader) pHeader.style.display = 'none';

    document.getElementById('vIcon').style.display = 'flex';
    document.getElementById('vTitle').style.display = 'block';
    document.getElementById('vSub').style.display = 'block';

    // Prevent multiple simultaneous fingerprint attempts
    if (fingerprintInProgress) {
        console.log('Fingerprint verification already in progress');
        return;
    }
    
    fingerprintInProgress = true;
    document.getElementById('retryFpBtn').style.display = 'none';
    setSpinner();
    document.getElementById('vTitle').textContent = 'Verifying Identity';
    document.getElementById('vSub').textContent = 'Touch your fingerprint sensor or use Face ID...';
    hideMsg();

    if (!window.PublicKeyCredential) {
        fingerprintInProgress = false;
        showFpError('Biometrics not supported on this device. Please use a compatible phone or browser.');
        return;
    }

    var devKey = (typeof window.getOrCreateDeviceKey === 'function')
        ? window.getOrCreateDeviceKey()
        : (localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || '');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '{{ route("qr.verify.options") }}', true);
    xhr.setRequestHeader('X-CSRF-TOKEN', CSRF);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.withCredentials = true;
    xhr.setRequestHeader('Accept', 'application/json');
    if (devKey) {
        xhr.setRequestHeader('X-Device-Key', devKey);
        xhr.setRequestHeader('X-Device-Fingerprint', devKey);
    }
    xhr.onload = function() {
        if (xhr.status !== 200) {
            fingerprintInProgress = false;
            showFpError('Server error (' + xhr.status + '). Please try again.');
            return;
        }
        var opts;
        try { 
            opts = JSON.parse(xhr.responseText); 
        } catch(e) { 
            fingerprintInProgress = false;
            showFpError('Server error (HTTP ' + xhr.status + '): ' + xhr.responseText.substring(0, 200)); 
            return; 
        }

        if (!opts.success) {
            fingerprintInProgress = false;
            showFpError(opts.message || 'No fingerprint registered. Please set up biometric login before clocking in.');
            return;
        }

        var challenge;
        var allowCredentials;
        try {
            challenge = base64ToUint8Array(opts.challenge);
            allowCredentials = [];
            for (var i = 0; i < opts.allowCredentials.length; i++) {
                var cred = opts.allowCredentials[i];
                allowCredentials.push({ type: cred.type, id: base64ToUint8Array(cred.id), transports: ['internal'] });
            }
        } catch(e) { 
            fingerprintInProgress = false;
            showFpError('Failed to decode credentials: ' + e.message); 
            return; 
        }

        var hostname = window.location.hostname;
        var isIp = /^(\d{1,3}\.){3}\d{1,3}$/.test(hostname) || hostname.includes(':');
        var sRp = (opts.rpId || '').toLowerCase().trim();
        var h = hostname.toLowerCase().trim();
        var effectiveRpId = isIp ? undefined : ((sRp && (h === sRp || h.endsWith('.' + sRp))) ? sRp : hostname);

        var pubKey = {
            challenge: challenge,
            allowCredentials: allowCredentials,
            userVerification: 'required',
            hints: ['client-device'],
            timeout: 60000
        };
        if (effectiveRpId) {
            pubKey.rpId = effectiveRpId;
        }

        navigator.credentials.get({
            publicKey: pubKey
        }).then(function(assertion) {
            setStep(4);
            setIcon('#f0fdf4', 'bi bi-fingerprint', '#16a34a');
            document.getElementById('vTitle').textContent = 'Identity Verified';
            document.getElementById('vSub').textContent = 'Clocking you in now...';
            showMsg('ok', '<i class="bi bi-check-circle me-1"></i> Fingerprint confirmed.');

            var credentialId = bufferToBase64Url(assertion.rawId);

            var credentialData = {
                id: credentialId,
                type: assertion.type,
                rawId: bufferToBase64Url(assertion.rawId),
                response: {
                    clientDataJSON: bufferToBase64Url(assertion.response.clientDataJSON),
                    authenticatorData: bufferToBase64Url(assertion.response.authenticatorData),
                    signature: bufferToBase64Url(assertion.response.signature),
                    userHandle: assertion.response.userHandle ? bufferToBase64Url(assertion.response.userHandle) : null
                }
            };

            document.getElementById('credentialInput').value = JSON.stringify(credentialData);
            submitAttendance(credentialData);
        }).catch(function(err) {
            fingerprintInProgress = false;
            if (err.name === 'NotAllowedError' || err.name === 'InvalidStateError') {
                showFpError('Fingerprint was cancelled or not found on this browser. If you switched browsers, <a href="{{ route("settings") }}#tab-fingerprint" style="color:#2563eb;font-weight:700;text-decoration:underline;">register this browser in Settings</a>.');
            } else {
                showFpError((err.name || 'Error') + ': ' + (err.message || 'Verification failed.'));
            }
        });
    };
    xhr.onerror = function() { 
        fingerprintInProgress = false;
        showFpError('Network error. Please try again.'); 
    };
    xhr.send(JSON.stringify({
        token: QR_TOKEN,
        device_key: devKey,
        device_fingerprint: devKey
    }));
}

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
    document.getElementById('vTitle').textContent = 'Weak GPS Signal';
    var accMsg = (acc && acc > 0) ? ' (±' + Math.round(acc) + 'm)' : '';
    document.getElementById('vSub').textContent = 'GPS accuracy is too low' + accMsg + ' to verify your location.';
    showMsg('err', '<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Weak Signal:</strong> Please move near a window, enable High Accuracy GPS, and try again.');
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
    showMsg('err', '<i class="bi bi-x-circle-fill me-1"></i> <strong>Outside Classroom:</strong> Attendance can only be recorded while physically inside the classroom (within ' + limit + 'm).');
    var btn = document.getElementById('retryFpBtn');
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry Location Check';
    btn.onclick = function() { startGPS(); };
    btn.style.display = 'flex';

    showOutsideRangePopup({
        distance: dist,
        radius: limit,
        message: 'You are outside the classroom boundary (' + roundedDist.toLocaleString() + 'm away, allowed within ' + limit + 'm). Attendance can only be recorded while physically inside the classroom.'
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
