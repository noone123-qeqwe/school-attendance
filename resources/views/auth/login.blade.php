<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.alert-banner-assets')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <title>Sign In - {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/premium.css') }}">
    <link rel="preload" as="image" href="/images/815973132_28114561508245819_4078090034639789685_n.jpg">
    @include('partials.pwa-tags')
    <link rel="stylesheet" href="{{ asset('css/auth-login.css') }}?v={{ filemtime(public_path('css/auth-login.css')) }}">
    <style>
        :root {
            --sat: env(safe-area-inset-top, 0px);
            --sab: env(safe-area-inset-bottom, 0px);
            --sal: env(safe-area-inset-left, 0px);
            --sar: env(safe-area-inset-right, 0px);
            --app-height: 100dvh;
        }
        html, body { overscroll-behavior: none; }
        .fp-row { touch-action: manipulation; }
        .fp-row * { pointer-events: none; }
        @media (hover: hover) and (pointer: fine) {
            .fp-row:hover { border-color: rgba(212, 175, 55, 0.55); }
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
        <p style="color:#b39b82;line-height:1.7;font-size:0.9rem;">Violations may result in disciplinary action as determined by the institution.</p>`
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
            <div class="glass-logo-wrap">
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
        <div class="glass-alert" role="alert" data-alert-transient><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="glass-alert" role="alert" data-alert-transient><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}</div>
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
            <div id="fingerprintSection">
                <!-- Inline message area for biometric feedback -->
                <div id="fpMessage" role="status" style="display:none;"></div>
                <p class="fp-row-hint" id="passkeyAvailability" role="status" hidden></p>
                <div id="passkeyControls">
                    <button type="button" class="fp-row anim-fade-up anim-d5" id="fpRowBtn" aria-label="Sign in with biometrics" aria-haspopup="dialog" aria-controls="biometricModal">
                        <div class="fp-row-left">
                            <div class="fp-icon-wrap">
                                <i class="bi bi-shield-lock" id="fpIcon"></i>
                            </div>
                            <div>
                                <div class="fp-row-label" id="fpLabel">Sign in with biometrics</div>
                                <div class="fp-row-hint" id="fpHint">Use fingerprint, face verification, or your password</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right fp-row-arrow" id="fpArrow"></i>
                    </button>
                    <div class="glass-divider anim-fade-up anim-d5">or use password</div>
                </div>
            </div>

            <!-- Password -->
            <div class="glass-input-wrap glass-input-wrap--password anim-fade-up anim-d5">
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
            <div class="d-flex align-items-center justify-content-between glass-remember-row anim-fade-up anim-d5">
                <label class="glass-remember-label">
                    <input type="checkbox" name="remember" id="rememberMe" value="1" checked class="glass-remember-check">
                    <span>Remember me</span>
                </label>
                <a href="{{ route('otp.forgot.form') }}" id="forgotPasswordLink" class="glass-forgot-link">
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

        <div class="glass-recovery-row anim-fade-up anim-d7">
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
<div id="biometricModal" class="bio-modal-overlay" style="display:none;" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="bioModalTitle" aria-describedby="bioModalDesc"
     data-face-guidance-url="{{ Vite::asset('resources/js/attendance-face-guidance.js') }}"
     data-face-options-url="{{ route('webauthn.face.options') }}"
     data-face-login-url="{{ route('webauthn.face.login') }}"
     data-apk-download-url="{{ \Illuminate\Support\Facades\Route::has('pwa.download.apk') ? route('pwa.download.apk') : url('/download/apk') }}">
    <div class="bio-modal-backdrop" id="bioModalBackdrop"></div>
    <div class="bio-modal-dialog">
        <div class="bio-modal-card">
            <!-- Close button -->
            <button type="button" class="bio-modal-close" id="bioModalCloseBtn" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>

            <header class="bio-dialog-heading">
                <div class="bio-dialog-icon" aria-hidden="true">
                    <i class="bi bi-shield-lock" id="bioModalIcon"></i>
                </div>
                <h2 class="bio-modal-title" id="bioModalTitle" tabindex="-1">Sign in with biometrics</h2>
                <div class="bio-modal-user-pill" id="bioModalUserPill" style="display:none;">
                    <i class="bi bi-person-badge" aria-hidden="true"></i><span id="bioModalUserText"></span>
                </div>
            </header>
            <p class="bio-modal-desc" id="bioModalDesc">Choose a verification option below to sign in.</p>

            <!-- Biometric / Sign-In Option Selection List -->
            <div id="bioModalMethodsWrap" style="display:none;">
                <div class="bio-method-list" id="bioModalMethodsList" role="group" aria-label="Sign-in options"></div>
            </div>

            <div id="bioModalProgress" class="bio-dialog-progress" role="status" hidden>
                <span id="bioModalProgressIcon" class="biometric-spinner" aria-hidden="true"></span>
                <span id="bioModalProgressText"></span>
            </div>

            <!-- Inline Identifier Input (when Face Verification starts without a typed ID) -->
            <div id="bioModalIdentifierWrap" style="display:none;">
                <label class="bio-modal-input-label" for="bioModalIdentifierInput">Student ID, Email, or Mobile</label>
                <div class="glass-input-wrap">
                    <i class="bi bi-person-fill g-icon"></i>
                    <input type="text" id="bioModalIdentifierInput" class="glass-input" placeholder="Enter your Student ID or Email" autocomplete="username">
                </div>
            </div>

            <!-- Guided Live Camera Face Verification Panel -->
            <div id="bioModalFaceWrap" hidden>
                <div class="bio-login-progress-wrap" style="margin-top:0;margin-bottom:10px;">
                    <div class="bio-login-progress-meta">
                        <span id="bioFaceProgressLabel">0/5 angles captured</span>
                        <span id="bioFaceLivenessStatus" role="status">Starting camera…</span>
                    </div>
                    <div class="bio-login-progress-bar">
                        <div class="bio-login-progress-fill" id="bioFaceProgressFill" style="width:0%"></div>
                    </div>
                    <progress id="bioFaceProgress" value="0" max="5" hidden></progress>
                </div>
                <p id="bioFacePoseInstruction" class="bio-face-pose-text" aria-live="polite">Position your face inside the oval.</p>
                <div class="bio-login-camera-box" id="bioFaceCameraBox">
                    <video id="bioFaceCameraVideo" class="bio-login-face-feed" autoplay playsinline muted></video>
                    <canvas id="bioFaceCaptureCanvas" style="display:none;"></canvas>
                    <div class="bio-login-reticle-corner tl"></div>
                    <div class="bio-login-reticle-corner tr"></div>
                    <div class="bio-login-reticle-corner bl"></div>
                    <div class="bio-login-reticle-corner br"></div>
                    <div class="bio-login-laser-bar" id="bioFaceLaserBar"></div>
                    <div class="bio-face-oval-reticle" id="bioFaceOvalGuide"></div>
                    <div class="bio-login-hud-badge" id="bioFaceStatusPill">
                        <span class="bio-login-pulse-dot" id="bioFaceStatusDot" aria-hidden="true"></span>
                        <span id="bioFaceStatusText" aria-live="polite" aria-atomic="true">Starting camera…</span>
                    </div>
                </div>
                @include('partials.camera-light-controls', ['cameraLightId' => 'bioFaceLight'])
                <p id="bioFaceCaptureConfirmation" class="bio-face-confirm-text" role="status"></p>
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

            <!-- Explicit Consent for Device Credential Registration -->
            <div id="bioModalConsentWrap" style="display:none;margin-bottom:14px;">
                <label for="bioModalConsentCheckbox" style="display:flex;align-items:flex-start;gap:10px;padding:11px 13px;border-radius:12px;border:1px solid rgba(255,255,255,0.16);background:rgba(28, 18, 18,0.52);font-size:0.78rem;line-height:1.45;color:#D6CCC6;cursor:pointer;text-align:left;">
                    <input type="checkbox" id="bioModalConsentCheckbox" style="margin-top:3px;width:16px;height:16px;flex-shrink:0;cursor:pointer;">
                    <span>I consent to registering this device’s built-in biometric or screen-lock passkey on my account. Only a cryptographic public key is stored — never raw fingerprint or biometric data.</span>
                </label>
            </div>

            <!-- Inline Alert Message -->
            <!-- Action Buttons -->
            <div class="bio-modal-actions">
                <button type="button" class="glass-btn glass-btn-primary" id="bioModalPrimaryBtn">
                    Set up biometrics
                </button>
                <button type="button" class="bio-modal-secondary-btn" id="bioModalSecondaryBtn">
                    Enter password
                </button>
                <button type="button" class="bio-modal-secondary-btn" id="bioModalCancelBtn" hidden>
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/device-passkey-capabilities.js') }}?v={{ filemtime(public_path('js/device-passkey-capabilities.js')) }}" @cspNonce></script>
<script @cspNonce>
// ── PWA INSTALL BUTTON CONTROLLER (LOGIN PAGE) ──────────────────────────────
(function() {
    function checkStandalone() {
        return Boolean(
            (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.isNativeApp === 'function' && window.AndroidDeviceBridge.isNativeApp()) ||
            window.AttendancePasskey ||
            /SmartAttendanceApp/i.test(navigator.userAgent || '')
        ) ||
               window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               document.referrer.includes('android-app://') ||
               window.matchMedia('(display-mode: fullscreen)').matches;
    }

    function checkInstalledFlag() {
        return localStorage.getItem('pwa_app_installed') === 'true' ||
               localStorage.getItem('pwa_apk_downloaded') === 'true';
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
            fpHint.textContent = 'Sign in with biometrics for ' + displayName;
        } else {
            fpHint.textContent = 'Use a device passkey or your password';
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
var biometricQrToken = document.querySelector('#loginForm input[name="qr_token"]')?.value;

var bioAbortController = null;
var bioAbortReason = null; // 'user' | 'timeout' | 'superseded' | null
var activeBioAttemptId = 0;
var bioWatchdogTimer = null;
var biometricReturnFocus = null;
var biometricPreviousOverflow = '';
var isBioPending = false;
var biometricRedirectStarted = false;
var passkeySignInCheck = null;
var bioFaceStream = null;
var bioFaceDetector = null;
var bioFaceLighting = null;

function getBioFaceLightControl() {
    if (!bioFaceLighting && typeof window.CameraLighting?.mount === 'function') {
        bioFaceLighting = window.CameraLighting.mount(document.getElementById('bioFaceLight'));
    }
    return bioFaceLighting;
}

function stopFaceLoginCapture() {
    try { bioFaceLighting?.stop?.(); } catch (e) {}
    if (bioFaceStream && typeof bioFaceStream.getTracks === 'function') {
        bioFaceStream.getTracks().forEach(function(track) {
            try { track.stop(); } catch (e) {}
        });
    }
    bioFaceStream = null;
    if (bioFaceDetector && typeof bioFaceDetector.close === 'function') {
        try { bioFaceDetector.close(); } catch (e) {}
    }
    bioFaceDetector = null;
    var video = document.getElementById('bioFaceCameraVideo');
    if (video) video.srcObject = null;
    var canvas = document.getElementById('bioFaceCaptureCanvas');
    if (canvas) canvas.width = 0;
}

function createWebAuthnError(name, message, extra) {
    var err = new Error(message);
    err.name = name || 'Error';
    if (extra && typeof extra === 'object') {
        Object.keys(extra).forEach(function(key) { err[key] = extra[key]; });
    }
    return err;
}

function clearBioWatchdog() {
    if (bioWatchdogTimer) {
        clearTimeout(bioWatchdogTimer);
        bioWatchdogTimer = null;
    }
}

function abortActiveBiometricAttempt(reason) {
    clearBioWatchdog();
    stopFaceLoginCapture();
    bioAbortReason = reason || 'user';
    if (bioAbortController) {
        try { bioAbortController.abort(); } catch (e) {}
        bioAbortController = null;
    }
}

function startNewBiometricAttempt() {
    if (bioAbortController || bioWatchdogTimer) {
        abortActiveBiometricAttempt('superseded');
    }
    activeBioAttemptId += 1;
    bioAbortReason = null;
    bioAbortController = new AbortController();
    return {
        id: activeBioAttemptId,
        controller: bioAbortController,
        signal: bioAbortController.signal
    };
}

function isStaleBiometricAttempt(attemptObj) {
    return !attemptObj
        || attemptObj.id !== activeBioAttemptId
        || attemptObj.controller.signal.aborted;
}

async function fetchJsonWithTimeout(url, options, timeoutMs, attemptObj) {
    var reqTimeout = timeoutMs || 15000;
    var reqController = new AbortController();
    var timedOut = false;

    var onAttemptAbort = function() {
        try { reqController.abort(); } catch (e) {}
    };
    if (attemptObj && attemptObj.signal) {
        if (attemptObj.signal.aborted) {
            throw createWebAuthnError('AbortError', 'Operation cancelled.');
        }
        attemptObj.signal.addEventListener('abort', onAttemptAbort, { once: true });
    }

    var timer = setTimeout(function() {
        timedOut = true;
        try { reqController.abort(); } catch (e) {}
    }, reqTimeout);

    try {
        var fetchOptions = Object.assign({}, options || {}, { signal: reqController.signal });
        var response = await fetch(url, fetchOptions);
        var rawText = await response.text();
        var data = null;
        if (rawText) {
            try {
                data = JSON.parse(rawText);
            } catch (parseErr) {
                data = null;
            }
        }
        return { response: response, data: data };
    } catch (err) {
        if (timedOut) {
            throw createWebAuthnError('TimeoutError', 'The server took too long to respond. Please check your connection and try again.');
        }
        if (attemptObj && attemptObj.signal && attemptObj.signal.aborted) {
            throw createWebAuthnError(
                bioAbortReason === 'timeout' ? 'TimeoutError' : 'AbortError',
                bioAbortReason === 'timeout'
                    ? 'The verification request timed out.'
                    : 'Verification was cancelled.'
            );
        }
        throw err;
    } finally {
        clearTimeout(timer);
        if (attemptObj && attemptObj.signal) {
            attemptObj.signal.removeEventListener('abort', onAttemptAbort);
        }
    }
}

async function runWithCredentialWatchdog(credentialPromiseFactory, timeoutMs, attemptObj) {
    var watchdogMs = Math.min(Math.max(Number(timeoutMs) || 45000, 15000), 65000) + 2500;
    clearBioWatchdog();

    return new Promise(function(resolve, reject) {
        var settled = false;
        var finish = function(fn, val) {
            if (settled) return;
            settled = true;
            clearBioWatchdog();
            if (attemptObj && attemptObj.signal) {
                attemptObj.signal.removeEventListener('abort', onAbort);
            }
            fn(val);
        };

        var onAbort = function() {
            if (bioAbortReason === 'timeout') {
                finish(reject, createWebAuthnError('TimeoutError', 'Biometric verification timed out before your device completed the prompt.'));
            } else {
                finish(reject, createWebAuthnError('AbortError', 'Verification was cancelled.'));
            }
        };

        if (attemptObj && attemptObj.signal) {
            if (attemptObj.signal.aborted) {
                onAbort();
                return;
            }
            attemptObj.signal.addEventListener('abort', onAbort, { once: true });
        }

        bioWatchdogTimer = setTimeout(function() {
            if (!settled && attemptObj && attemptObj.id === activeBioAttemptId) {
                abortActiveBiometricAttempt('timeout');
            }
        }, watchdogMs);

        var credPromise;
        try {
            credPromise = credentialPromiseFactory();
        } catch (syncErr) {
            finish(reject, syncErr);
            return;
        }

        Promise.resolve(credPromise).then(
            function(cred) { finish(resolve, cred); },
            function(err) { finish(reject, err); }
        );
    });
}

async function checkPasskeyAvailability(operation) {
    return await window.DevicePasskeys?.check(operation) || { available: false, reason: 'unknown' };
}

function passkeyAvailabilityMessage(result, operation) {
    if (result && result.reason === 'insecure') {
        return operation === 'create'
            ? 'Biometric setup requires a secure (HTTPS) connection.'
            : 'Biometric sign-in requires a secure (HTTPS) connection. Sign in with your password.';
    }
    if (result && result.reason === 'unavailable') {
        return operation === 'create'
            ? 'This browser couldn’t find an active biometric or device screen lock method. Check your device settings.'
            : 'Set up fingerprint or screen lock on this device, then try again.';
    }
    if (result && result.reason === 'unsupported') {
        return 'This browser does not support device biometric sign-in. Sign in with your password.';
    }
    return window.DevicePasskeys?.message(result, operation)
        || 'Biometric availability couldn’t be checked. Sign in with your password.';
}

function refreshPasskeySignIn() {
    if (passkeySignInCheck) return passkeySignInCheck;

    var status = document.getElementById('passkeyAvailability');
    var controls = document.getElementById('passkeyControls');
    if (controls) controls.hidden = false;
    if (fpRowBtn) fpRowBtn.disabled = isBioPending;
    passkeySignInCheck = checkPasskeyAvailability('get').then(function(result) {
        if (status) {
            status.hidden = true;
            status.textContent = '';
        }
        if (controls) controls.hidden = false;
        if (fpRowBtn) fpRowBtn.disabled = isBioPending;
        return result;
    }).finally(function() {
        passkeySignInCheck = null;
    });
    return passkeySignInCheck;
}

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

function resetBiometricButton() {
    clearBioWatchdog();
    isBioPending = false;
    if (!fpRowBtn) return;
    fpRowBtn.disabled = false;
    fpRowBtn.style.opacity = '1';
    fpRowBtn.style.cursor = 'pointer';
    fpRowBtn.removeAttribute('title');
    if (fpLabel) fpLabel.textContent = 'Sign in with biometrics';
    updateBiometricHint();
    if (fpIcon) fpIcon.className = 'bi bi-shield-lock';
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
function openBiometricModal(config) {
    config = config || {};
    var modal = document.getElementById('biometricModal');
    if (!modal) return;
    
    var titleEl = document.getElementById('bioModalTitle');
    var descEl = document.getElementById('bioModalDesc');
    var pillEl = document.getElementById('bioModalUserPill');
    var pillTextEl = document.getElementById('bioModalUserText');
    var methodsWrap = document.getElementById('bioModalMethodsWrap');
    var methodsList = document.getElementById('bioModalMethodsList');
    var primaryBtn = document.getElementById('bioModalPrimaryBtn');
    var secondaryBtn = document.getElementById('bioModalSecondaryBtn');
    var cancelBtn = document.getElementById('bioModalCancelBtn');
    var passWrap = document.getElementById('bioModalPasswordWrap');
    var consentWrap = document.getElementById('bioModalConsentWrap');
    var consentBox = document.getElementById('bioModalConsentCheckbox');
    var idWrap = document.getElementById('bioModalIdentifierWrap');
    var faceWrap = document.getElementById('bioModalFaceWrap');
    var progress = document.getElementById('bioModalProgress');
    var progressText = document.getElementById('bioModalProgressText');
    var progressIcon = document.getElementById('bioModalProgressIcon');
    var wasOpen = modal.classList.contains('active');
    if (!wasOpen) {
        biometricReturnFocus = document.activeElement === document.body ? fpRowBtn : document.activeElement;
        biometricPreviousOverflow = document.body.style.overflow;
    }
    if (!config.showFaceCapture) {
        stopFaceLoginCapture();
    }
    hideFpMessage();
    modal.dataset.tone = config.badgeType || 'info';
    var dismissible = !config.complete && config.dismissible !== false;
    modal.dataset.dismissible = String(dismissible);
    if (titleEl) titleEl.textContent = config.title || 'Sign in with biometrics';
    if (descEl) descEl.textContent = biometricText(config.message || 'Choose a verification option below to sign in.');
    if (progress) progress.hidden = !config.progress;
    if (progressText) progressText.textContent = config.progress || '';
    if (progressIcon) {
        progressIcon.className = config.complete ? 'bi bi-check-circle-fill bio-progress-complete' : 'biometric-spinner';
    }
    var closeBtn = document.getElementById('bioModalCloseBtn');
    if (closeBtn) closeBtn.hidden = !dismissible;
    
    if (config.identifier) {
        activeSetupIdentifier = config.identifier;
        if (pillEl) pillEl.style.display = 'inline-flex';
        if (pillTextEl) pillTextEl.textContent = config.identifier;
    } else {
        if (pillEl) pillEl.style.display = 'none';
    }
    
    if (idWrap) {
        idWrap.style.display = config.showIdentifier ? 'block' : 'none';
        var modalIdInput = document.getElementById('bioModalIdentifierInput');
        if (modalIdInput && typeof config.prefillIdentifier === 'string') {
            modalIdInput.value = config.prefillIdentifier;
        }
    }
    if (faceWrap) {
        faceWrap.hidden = !config.showFaceCapture;
    }
    if (passWrap) {
        passWrap.style.display = config.showPassword ? 'block' : 'none';
    }
    if (consentWrap) {
        consentWrap.style.display = config.showConsent ? 'block' : 'none';
        if (consentBox && config.resetConsent) {
            consentBox.checked = false;
        }
    }

    if (methodsWrap && methodsList) {
        if (Array.isArray(config.methods) && config.methods.length > 0) {
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
                iconBox.className = 'bio-method-icon-box' + (method.accentClass ? ' ' + method.accentClass : '');
                var iconEl = document.createElement('i');
                iconEl.className = 'bi ' + (method.icon || 'bi-shield-lock');
                iconEl.setAttribute('aria-hidden', 'true');
                iconBox.appendChild(iconEl);
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
                arrowIcon.setAttribute('aria-hidden', 'true');

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

    currentPrimaryModalCallback = typeof config.onPrimaryClick === 'function' ? config.onPrimaryClick : handleSetupBiometricsClick;
    currentSecondaryModalCallback = typeof config.onSecondaryClick === 'function' ? config.onSecondaryClick : closeBiometricModalAndFocusPassword;

    if (primaryBtn) {
        primaryBtn.hidden = !!(config.hidePrimaryBtn || config.primaryBtnText === false);
        primaryBtn.style.display = primaryBtn.hidden ? 'none' : '';
        primaryBtn.textContent = biometricText(config.primaryBtnText || 'Set up biometrics');
        primaryBtn.disabled = !!config.primaryDisabled;
    }

    if (secondaryBtn) {
        if (config.secondaryBtnText === '' || config.secondaryBtnText === false) {
            secondaryBtn.hidden = true;
        } else {
            secondaryBtn.hidden = false;
            secondaryBtn.textContent = biometricText(config.secondaryBtnText || 'Enter password');
        }
        secondaryBtn.style.display = secondaryBtn.hidden ? 'none' : '';
    }
    if (cancelBtn) {
        cancelBtn.hidden = !config.showCancelBtn;
        cancelBtn.style.display = cancelBtn.hidden ? 'none' : '';
    }
    
    var modalIcon = document.getElementById('bioModalIcon');
    if (modalIcon) {
        modalIcon.className = 'bi ' + (config.headerIcon || 'bi-shield-lock');
    }

    modal.style.display = 'flex';
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    if (!config.complete) {
        var firstMethodBtn = methodsList ? methodsList.querySelector('button.bio-method-card') : null;
        var focusTarget = config.showIdentifier
            ? document.getElementById('bioModalIdentifierInput')
            : (config.showPassword
                ? document.getElementById('bioModalPasswordInput')
                : (firstMethodBtn || (primaryBtn && !primaryBtn.hidden && !primaryBtn.disabled ? primaryBtn : titleEl)));
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
        dismissible: options.dismissible,
        progress: options.complete ? 'Your account is ready.' : progress,
        secondaryBtnText: options.complete || options.dismissible === false ? false : 'Cancel',
        onSecondaryClick: closeBiometricModal
    });
}

function closeBiometricModal() {
    abortActiveBiometricAttempt('user');
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

function showPasskeySetupUnavailable(identifier, capability) {
    var updateUrl = capability?.reason === 'update-app' && document.getElementById('biometricModal')?.dataset?.apkDownloadUrl;
    openBiometricModal({
        title: 'Biometric setup unavailable',
        identifier: identifier,
        message: passkeyAvailabilityMessage(capability, 'create'),
        badgeType: 'warning',
        primaryBtnText: updateUrl ? 'Download app update' : 'Enter password',
        secondaryBtnText: 'Back to options',
        onPrimaryClick: updateUrl ? function() { window.location.assign(updateUrl); } : closeBiometricModalAndFocusPassword,
        onSecondaryClick: function() { openBiometricSignInChoices(identifier); }
    });
}

async function openBiometricSetupModal(identifier, signal) {
    var capability = await checkPasskeyAvailability('create');
    if (signal?.aborted) return;
    resetBiometricButton();
    activeSetupIdentifier = identifier || (idInput ? idInput.value.trim() : '');
    if (!capability.available) {
        showPasskeySetupUnavailable(activeSetupIdentifier, capability);
        return;
    }
    openBiometricModal({
        title: 'Register this device',
        headerIcon: capability.supportsFingerprintSpecificUi ? 'bi-fingerprint' : 'bi-shield-lock',
        identifier: activeSetupIdentifier,
        message: 'Confirm your password and consent below. ' + (capability.disclaimer || 'Your operating system will verify you locally and share only a cryptographic public key.'),
        showPassword: true,
        showConsent: true,
        resetConsent: true,
        badgeType: 'info',
        primaryBtnText: capability.supportsFingerprintSpecificUi ? 'Verify & register fingerprint' : 'Verify & register device',
        secondaryBtnText: 'Enter password',
        showCancelBtn: true,
        onPrimaryClick: handleSetupBiometricsClick,
        onSecondaryClick: closeBiometricModalAndFocusPassword
    });
}

async function handleSetupBiometricsClick() {
    if (isBioPending) return;
    var passInputInForm = document.getElementById('loginPassword');
    var passInputInModal = document.getElementById('bioModalPasswordInput');
    var consentBox = document.getElementById('bioModalConsentCheckbox');
    
    var password = passInputInModal?.value || passInputInForm?.value || '';

    if (consentBox && !consentBox.checked) {
        openBiometricModal({
            title: 'Consent required',
            identifier: activeSetupIdentifier,
            message: 'Please check the consent box below to confirm you want to link this device’s passkey to your account.',
            showPassword: true,
            showConsent: true,
            badgeType: 'warning',
            primaryBtnText: 'Verify and set up biometrics',
            secondaryBtnText: 'Enter password',
            showCancelBtn: true,
            onPrimaryClick: handleSetupBiometricsClick,
            onSecondaryClick: closeBiometricModalAndFocusPassword
        });
        return;
    }

    if (!password) {
        openBiometricModal({
            title: 'VERIFY YOUR ACCOUNT',
            identifier: activeSetupIdentifier,
            message: 'Confirm your account password to register a passkey on this account for this device.',
            showPassword: true,
            showConsent: true,
            badgeType: 'warning',
            primaryBtnText: 'Verify and set up biometrics',
            secondaryBtnText: 'Enter password',
            showCancelBtn: true,
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

    var attemptObj = startNewBiometricAttempt();
    isBioPending = true;
    showBiometricProgress('Preparing passkey setup', 'Checking biometric and screen lock support on this device.', identifier, 'Checking this browser…');

    try {
        var capability = await checkPasskeyAvailability('create');
        if (isStaleBiometricAttempt(attemptObj)) return;
        if (!capability.available) {
            resetBiometricButton();
            showPasskeySetupUnavailable(identifier, capability);
            return;
        }

        showBiometricProgress('Checking your password', 'After this check, your device will ask you to confirm setup.', identifier, 'Step 1 of 2 · Checking account');
        var optFetch = await fetchJsonWithTimeout('{{ route("webauthn.setup.options") }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ identifier: identifier, password: password, consent_accepted: true })
        }, 15000, attemptObj);
        if (isStaleBiometricAttempt(attemptObj)) return;

        var optRes = optFetch.response;
        var opts = optFetch.data;

        if (!optRes.ok || !opts || !opts.success) {
            resetBiometricButton();
            if (optRes.status === 419) {
                openBiometricModal({
                    title: 'Session expired',
                    identifier: identifier,
                    message: 'Your security token expired. Refresh the page and try again, or sign in with your password.',
                    badgeType: 'warning',
                    primaryBtnText: 'Refresh page',
                    secondaryBtnText: 'Enter password',
                    showCancelBtn: true,
                    onPrimaryClick: function() { window.location.reload(); },
                    onSecondaryClick: closeBiometricModalAndFocusPassword
                });
                return;
            }
            var errMsg = (opts && opts.message) || 'Verification failed. Please check your password.';
            openBiometricModal({
                title: 'ACCOUNT VERIFICATION FAILED',
                identifier: identifier,
                message: errMsg,
                showPassword: true,
                badgeType: 'danger',
                primaryBtnText: 'Try again',
                secondaryBtnText: 'Enter password',
                showCancelBtn: true,
                onPrimaryClick: handleSetupBiometricsClick,
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        }

        const scan = () => {
            showBiometricProgress('Confirm on your device', 'Use your fingerprint sensor or follow the device unlock prompt.', identifier, 'Waiting for your device…', { icon: 'bi-fingerprint' });
            return runWithCredentialWatchdog(
                () => window.DevicePasskeys.registerFingerprint(opts, attemptObj.signal), opts.timeout, attemptObj
            );
        };
        let registration;
        if (window.DevicePasskeys.needsTap('create')) {
            registration = await new Promise((resolve, reject) => {
                const cancel = () => resolve(null);
                attemptObj.signal.addEventListener('abort', cancel, { once: true });
                openBiometricModal({
                    title: 'Ready to register',
                    identifier,
                    message: 'Open the fingerprint prompt on this device.',
                    primaryBtnText: 'Register fingerprint',
                    secondaryBtnText: 'Enter password',
                    showCancelBtn: true,
                    onSecondaryClick: closeBiometricModalAndFocusPassword,
                    onPrimaryClick: () => {
                        attemptObj.signal.removeEventListener('abort', cancel);
                        scan().then(resolve, reject);
                    }
                });
            });
        } else {
            registration = await scan();
        }
        if (isStaleBiometricAttempt(attemptObj) || !registration) return;
        showBiometricProgress('Saving biometric sign-in', 'Linking this device to your account.', identifier, 'Saving credential…');
        var saveFetch = await fetchJsonWithTimeout('{{ route("webauthn.setup.register") }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ...registration,
                device_name: capability?.defaultDeviceName || undefined,
                biometric_type: capability?.biometricType || 'fingerprint',
                consent_accepted: true,
                qr_token: biometricQrToken
            })
        }, 15000, attemptObj);
        if (isStaleBiometricAttempt(attemptObj)) return;

        var saveRes = saveFetch.response;
        var regResult = saveFetch.data;

        if (saveRes.ok && regResult && regResult.success) {
            resetBiometricButton();
            try {
                saveAccount({ identifier: identifier, name: identifier });
                if (idInput) {
                    idInput.value = identifier;
                    if (typeof updateAccountBanner === 'function') updateAccountBanner();
                }
            } catch (e) {}

            openBiometricModal({
                title: 'Biometrics ready',
                headerIcon: 'bi-shield-lock',
                identifier: identifier,
                message: 'Passkey registered on this account. You can now use biometrics on this device to sign in.',
                badgeType: 'success',
                primaryBtnText: 'Continue',
                secondaryBtnText: false,
                onPrimaryClick: function() {
                    closeBiometricModal();
                    window.location.href = regResult.redirect || '{{ route("home") }}';
                }
            });
        } else {
            throw new Error((regResult && regResult.message) || 'Failed to save biometric credential.');
        }
    } catch (err) {
        if (attemptObj.id !== activeBioAttemptId || (attemptObj.signal.aborted && bioAbortReason !== 'timeout')) return;
        console.error('Biometric setup error:', err);
        resetBiometricButton();

        if (err.name === 'TimeoutError') {
            openBiometricModal({
                title: 'Setup timed out',
                identifier: identifier,
                message: 'The passkey setup prompt timed out before finishing. Make sure your device screen lock or biometrics are active, then try again or sign in with your password.',
                badgeType: 'warning',
                primaryBtnText: 'Try again',
                secondaryBtnText: 'Enter password',
                showCancelBtn: true,
                onPrimaryClick: handleSetupBiometricsClick,
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        }
        if (err.name === 'NotAllowedError') {
            showFpMessage('warning', 'Biometric registration was cancelled or timed out.');
            openBiometricModal({
                title: 'Registration cancelled',
                identifier: identifier,
                message: 'Biometric setup was cancelled or didn’t finish. You can try registering again, or sign in using your password.',
                badgeType: 'warning',
                primaryBtnText: 'Try again',
                secondaryBtnText: 'Enter password',
                showCancelBtn: true,
                onPrimaryClick: handleSetupBiometricsClick,
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        }
        var msg = 'Biometric setup could not be completed.';
        if (navigator.onLine === false) {
            msg = 'You appear to be offline. Reconnect to the internet and try again.';
        } else if (err.name === 'InvalidStateError') {
            msg = 'A passkey from this device is already registered on this account.';
        } else if (err.name === 'SecurityError') {
            msg = 'This page domain or HTTPS configuration does not allow passkey setup here.';
        } else if (err.message) {
            msg = err.message;
        }
        showFpMessage('error', msg);
        openBiometricModal({
            title: 'Biometric setup failed',
            identifier: identifier,
            message: msg + ' You can sign in using your password or try setup again.',
            badgeType: 'warning',
            primaryBtnText: 'Try again',
            secondaryBtnText: 'Enter password',
            showCancelBtn: true,
            onPrimaryClick: handleSetupBiometricsClick,
            onSecondaryClick: closeBiometricModalAndFocusPassword
        });
    }
}

// Direct action to register biometrics on this account
function handleDirectReEnrollClick(e) {
    if (e) e.preventDefault();
    hideFpMessage();
    var idVal = idInput ? idInput.value.trim() : '';
    if (!idVal) {
        showFpMessage('warning', 'Please enter your Student ID, Email, or Mobile Number first.');
        openBiometricModal({
            title: 'Enter account identifier',
            message: 'Please enter your Student ID, Email, or Mobile Number first so we can register biometric sign-in on this account.',
            badgeType: 'warning',
            primaryBtnText: 'Enter identifier',
            secondaryBtnText: 'Cancel',
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

// Handle biometric login button click
async function handleBiometricLogin() {
    if (biometricRedirectStarted) return;
    hideFpMessage();

    if (isBioPending) return;

    return openBiometricSignInChoices(idInput ? idInput.value.trim() : '');
}

function canRequestFaceSignIn(serverOpts) {
    if (serverOpts && serverOpts.face_login_enabled === false) return false;
    return Boolean(
        window.isSecureContext
        && typeof navigator !== 'undefined'
        && navigator.mediaDevices
        && typeof navigator.mediaDevices.getUserMedia === 'function'
    );
}

var faceSignInOption = {
    id: 'face',
    name: 'Face verification',
    desc: 'Capture a live camera face scan to verify liveness and sign in.',
    icon: 'bi-person-bounding-box',
    accentClass: 'bio-method-icon-box--face'
};

function updateFaceCaptureStatus(message, statusText) {
    var pillText = document.getElementById('bioFaceStatusText');
    var liveness = document.getElementById('bioFaceLivenessStatus');
    if (pillText && message) pillText.textContent = message;
    if (liveness && (statusText || message)) liveness.textContent = statusText || message;
}

function promptModalIdentifier(config, prefill, errorMessage) {
    openBiometricModal({
        title: config.title,
        headerIcon: config.headerIcon,
        badgeType: errorMessage ? 'warning' : 'info',
        message: errorMessage || config.message,
        showIdentifier: true,
        prefillIdentifier: prefill || '',
        primaryBtnText: config.primaryBtnText,
        secondaryBtnText: 'Enter password',
        showCancelBtn: true,
        onPrimaryClick: function() {
            var modalIdInput = document.getElementById('bioModalIdentifierInput');
            var entered = modalIdInput ? modalIdInput.value.trim() : '';
            if (!entered) {
                promptModalIdentifier(config, '', 'Enter your Student ID, Email, or Mobile Number to continue.');
                return;
            }
            if (idInput) {
                idInput.value = entered;
                if (typeof updateAccountBanner === 'function') updateAccountBanner();
            }
            config.onSubmit(entered);
        },
        onSecondaryClick: closeBiometricModalAndFocusPassword
    });
}

function promptFaceIdentifier(prefill, errorMessage) {
    promptModalIdentifier({
        title: 'Face verification',
        headerIcon: 'bi-person-bounding-box',
        message: 'Enter your Student ID, Email, or Mobile Number so we can check your registered face profile.',
        primaryBtnText: 'Start camera',
        onSubmit: startFaceVerificationLogin
    }, prefill, errorMessage);
}

function promptFingerprintIdentifier(prefill, errorMessage) {
    promptModalIdentifier({
        title: 'Fingerprint verification',
        headerIcon: 'bi-fingerprint',
        message: 'Enter your Student ID, Email, or Mobile Number to check your registered fingerprint passkey on this device.',
        primaryBtnText: 'Verify fingerprint',
        onSubmit: startFingerprintWithIdentifier
    }, prefill, errorMessage);
}

async function fetchAvailableBiometricMethods(attemptObj) {
    try {
        var resFetch = await fetchJsonWithTimeout('{{ route("webauthn.available.methods") }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({})
        }, 10000, attemptObj);
        if (resFetch.response.ok && resFetch.data && resFetch.data.success) {
            return resFetch.data;
        }
    } catch (err) {
        if (attemptObj && attemptObj.signal && attemptObj.signal.aborted) {
            throw err;
        }
    }
    return null;
}

async function fetchFingerprintLoginOptions(identifier, attemptObj) {
    return fetchJsonWithTimeout('{{ route("webauthn.login.options") }}', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ identifier: identifier, biometric_method: 'fingerprint' })
    }, 15000, attemptObj);
}

async function startFingerprintWithIdentifier(identifier, allowSavedPasskey = false) {
    if (biometricRedirectStarted || isBioPending) return;
    var resolvedIdentifier = (identifier || (idInput ? idInput.value : '') || '').trim();
    if (!resolvedIdentifier) {
        promptFingerprintIdentifier('');
        return;
    }

    var attemptObj = startNewBiometricAttempt();
    showBiometricProgress('Checking account passkeys', 'Checking registered fingerprint credentials for your account.', resolvedIdentifier, 'Checking account…', { icon: 'bi-fingerprint' });
    try {
        var optFetch = await fetchFingerprintLoginOptions(resolvedIdentifier, attemptObj);
        if (isStaleBiometricAttempt(attemptObj)) return;
        var optRes = optFetch.response;
        var opts = optFetch.data;
        if (opts?.code === 'BIOMETRIC_UNAVAILABLE'
            || (optRes.ok && opts?.success && (!Array.isArray(opts.allowCredentials) || opts.allowCredentials.length === 0))) {
            var fallbackMethods = [{
                id: 'setup_device',
                name: 'Register this device',
                desc: 'Verify your password once and scan your fingerprint to enable sign-in on this device.',
                icon: 'bi-fingerprint',
                accentClass: 'bio-method-icon-box--fingerprint'
            }];
            if (canRequestFaceSignIn(opts)) fallbackMethods.push(faceSignInOption);
            fallbackMethods.push({
                id: 'password',
                name: 'Enter password',
                desc: 'Type your account password to sign in.',
                icon: 'bi-lock-fill',
                accentClass: 'bio-method-icon-box--password'
            });
            openBiometricModal({
                title: 'No fingerprint passkey registered',
                headerIcon: 'bi-fingerprint',
                identifier: resolvedIdentifier,
                badgeType: 'warning',
                message: (opts && opts.message) || 'No passkey was found for those sign-in details. Register this device, use Face verification, or sign in with your password.',
                methods: fallbackMethods,
                hidePrimaryBtn: true,
                secondaryBtnText: false,
                showCancelBtn: true,
                onMethodSelect: function(selected) {
                    if (selected.id === 'setup_device') openBiometricSetupModal(resolvedIdentifier);
                    else if (selected.id === 'face') startFaceVerificationLogin(resolvedIdentifier);
                    else closeBiometricModalAndFocusPassword();
                }
            });
            return;
        }
        if (!optRes.ok || !opts || !opts.success) {
            if (optRes.status === 419) {
                throw createWebAuthnError('SessionExpiredError', 'Your session security token expired. Refresh this page and try again.');
            }
            if (optRes.status === 429) {
                throw createWebAuthnError('RateLimitError', 'Too many sign-in attempts. Wait a moment and try again.');
            }
            throw new Error((opts && opts.message) || 'Device sign-in could not be started.');
        }
        if (window.DevicePasskeys.needsTap('get', opts, allowSavedPasskey)) {
            var readyAt = performance.now();
            openBiometricModal({
                title: 'Verify with fingerprint',
                identifier: resolvedIdentifier,
                message: 'Open the device prompt to finish signing in.',
                primaryBtnText: 'Scan fingerprint',
                secondaryBtnText: 'Enter password',
                showCancelBtn: true,
                onPrimaryClick: function() {
                    if (performance.now() - readyAt > 45000) startFingerprintWithIdentifier(resolvedIdentifier, allowSavedPasskey);
                    else performBiometricLogin(resolvedIdentifier, opts, allowSavedPasskey);
                },
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        }
        performBiometricLogin(resolvedIdentifier, opts, allowSavedPasskey);
    } catch (err) {
        if (!isStaleBiometricAttempt(attemptObj)) handleBiometricSignInError(err, resolvedIdentifier, null);
    }
}

async function startFaceVerificationLogin(identifier) {
    if (biometricRedirectStarted || isBioPending) return;
    var resolvedIdentifier = (identifier || (idInput ? idInput.value : '') || '').trim();
    if (!resolvedIdentifier) {
        promptFaceIdentifier('');
        return;
    }

    if (!window.isSecureContext || !navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
        openBiometricModal({
            title: 'Camera unavailable',
            headerIcon: 'bi-camera-video-off',
            identifier: resolvedIdentifier,
            badgeType: 'warning',
            message: 'This browser cannot access the camera here. Sign in with fingerprint or your password.',
            primaryBtnText: 'Use fingerprint',
            secondaryBtnText: 'Enter password',
            showCancelBtn: true,
            onPrimaryClick: function() { startFingerprintWithIdentifier(resolvedIdentifier); },
            onSecondaryClick: closeBiometricModalAndFocusPassword
        });
        return;
    }

    var modalEl = document.getElementById('biometricModal');
    var faceGuidanceUrl = modalEl?.dataset?.faceGuidanceUrl;
    var faceOptionsUrl = modalEl?.dataset?.faceOptionsUrl || '{{ route("webauthn.face.options") }}';
    var faceLoginUrl = modalEl?.dataset?.faceLoginUrl || '{{ route("webauthn.face.login") }}';

    var attemptObj = startNewBiometricAttempt();
    isBioPending = true;

    // Database-first check: verify registered facial verification data exists before opening camera.
    showBiometricProgress(
        'Checking face profile',
        'Checking whether facial verification is registered for your account.',
        resolvedIdentifier,
        'Checking account face profile…',
        { icon: 'bi-person-bounding-box' }
    );

    try {
        var optFetch = await fetchJsonWithTimeout(faceOptionsUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                identifier: resolvedIdentifier,
                student_number: resolvedIdentifier
            })
        }, 15000, attemptObj);
        if (isStaleBiometricAttempt(attemptObj)) return;

        var optRes = optFetch.response;
        var optData = optFetch.data;
        if (!optRes.ok || !optData?.face_challenge?.id || !Array.isArray(optData.face_challenge.poses) || optData.face_challenge.poses.length !== 5) {
            var optMsg = (optData && optData.message) || 'Face verification could not be started.';
            throw createWebAuthnError('FaceLoginError', optMsg, {
                status: optRes.status,
                code: optData?.code || null
            });
        }

        if (!faceGuidanceUrl) {
            throw createWebAuthnError('NotSupportedError', 'Face detection assets are not available.');
        }

        // Registered facial template confirmed in database — open Capture & Liveness Detection UI.
        openBiometricModal({
            title: 'Face verification',
            headerIcon: 'bi-person-bounding-box',
            identifier: resolvedIdentifier,
            message: 'Center your face inside the frame and follow each head-turn direction.',
            showFaceCapture: true,
            hidePrimaryBtn: true,
            secondaryBtnText: 'Cancel',
            onSecondaryClick: closeBiometricModal
        });

        var progressEl = document.getElementById('bioFaceProgress');
        var progressFill = document.getElementById('bioFaceProgressFill');
        var progressLabel = document.getElementById('bioFaceProgressLabel');
        var instructionEl = document.getElementById('bioFacePoseInstruction');
        var confirmEl = document.getElementById('bioFaceCaptureConfirmation');
        var video = document.getElementById('bioFaceCameraVideo');
        var canvas = document.getElementById('bioFaceCaptureCanvas');

        if (progressEl) progressEl.value = 0;
        if (progressFill) progressFill.style.width = '0%';
        if (progressLabel) progressLabel.textContent = '0/5 angles captured';
        if (instructionEl) instructionEl.textContent = 'Position your face inside the oval.';
        if (confirmEl) confirmEl.textContent = '';
        updateFaceCaptureStatus('Starting camera…', 'Liveness check pending');

        var stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 640 } },
            audio: false
        });
        if (isStaleBiometricAttempt(attemptObj)) {
            stream.getTracks().forEach(function(t) { t.stop(); });
            return;
        }
        bioFaceStream = stream;
        if (video) {
            video.srcObject = stream;
            await video.play();
        }
        if (isStaleBiometricAttempt(attemptObj)) return;

        getBioFaceLightControl()?.start?.(stream);
        updateFaceCaptureStatus('Preparing face detection…', 'Loading camera guidance');

        var guidance = await import(faceGuidanceUrl);
        if (isStaleBiometricAttempt(attemptObj)) return;
        bioFaceDetector = await guidance.createDetector();
        if (isStaleBiometricAttempt(attemptObj)) return;

        var frames = await guidance.captureSequence({
            video: video,
            canvas: canvas,
            detector: bioFaceDetector,
            poses: optData.face_challenge.poses,
            signal: attemptObj.signal,
            expiresIn: optData.face_challenge.expires_in,
            onGuide: function(message, state, direction) {
                if (instructionEl && direction && instructionEl.textContent !== direction) {
                    instructionEl.textContent = direction;
                }
                updateFaceCaptureStatus(message, state === 'matched' ? 'Hold pose steady' : message);
            },
            onProgress: function(count, pose) {
                if (progressEl) progressEl.value = count;
                if (progressFill) progressFill.style.width = Math.round((count / 5) * 100) + '%';
                if (progressLabel) progressLabel.textContent = count + '/5 angles captured';
                if (confirmEl) {
                    var labels = { front: 'Front', left: 'Left', right: 'Right', up: 'Upward', down: 'Downward' };
                    confirmEl.textContent = (labels[pose] || 'View') + ' angle captured.';
                }
                window.triggerHaptic?.('light');
            }
        });
        if (isStaleBiometricAttempt(attemptObj)) return;

        stopFaceLoginCapture();
        showBiometricProgress(
            'Checking face verification',
            'Confirming live capture and matching your encrypted face profile.',
            resolvedIdentifier,
            'Verifying liveness & identity…',
            { icon: 'bi-person-bounding-box', dismissible: false }
        );

        var devKey = (typeof window.getOrCreateDeviceKey === 'function')
            ? window.getOrCreateDeviceKey()
            : (localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || '');

        var loginFetch = await fetchJsonWithTimeout(faceLoginUrl, {
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
                identifier: resolvedIdentifier,
                student_number: resolvedIdentifier,
                face_challenge: optData.face_challenge.id,
                live_frames: frames,
                qr_token: biometricQrToken
            })
        }, 25000, attemptObj);
        if (isStaleBiometricAttempt(attemptObj)) return;

        var loginRes = loginFetch.response;
        var loginData = loginFetch.data;
        if (!loginRes.ok || !loginData || !loginData.success) {
            var failMsg = (loginData && loginData.message) || 'Face verification did not match. Try again in even lighting.';
            throw createWebAuthnError('FaceLoginError', failMsg, {
                status: loginRes.status,
                code: loginData?.code || null
            });
        }

        completeBiometricLogin(loginData);
    } catch (err) {
        stopFaceLoginCapture();
        resetBiometricButton();
        if (attemptObj.id !== activeBioAttemptId || (attemptObj.signal.aborted && bioAbortReason === 'user')) {
            return;
        }

        var message = err && err.name === 'NotAllowedError'
            ? 'Camera access was denied. Allow camera permission in your browser settings to verify with your face, or use your fingerprint or password.'
            : (err?.message || 'Face verification could not be completed. Please try again.');
        var cannotRetry = err?.code === 'FACE_LOGIN_UNAVAILABLE'
            || err?.code === 'FACE_VERIFICATION_UNAVAILABLE'
            || err?.code === 'FACE_ATTEMPTS_EXHAUSTED'
            || err?.code === 'ACCOUNT_LOCKED'
            || err?.status === 429
            || err?.status === 503;

        openBiometricModal({
            title: cannotRetry ? 'Face verification unavailable' : 'Face verification incomplete',
            headerIcon: 'bi-person-bounding-box',
            identifier: resolvedIdentifier,
            badgeType: 'warning',
            message: message,
            primaryBtnText: cannotRetry ? 'Use fingerprint' : 'Try face again',
            secondaryBtnText: 'Enter password',
            showCancelBtn: true,
            onPrimaryClick: function() {
                if (cannotRetry) startFingerprintWithIdentifier(resolvedIdentifier);
                else startFaceVerificationLogin(resolvedIdentifier);
            },
            onSecondaryClick: closeBiometricModalAndFocusPassword
        });
    }
}

async function openBiometricSignInChoices(identifier) {
    var attemptObj = startNewBiometricAttempt();
    resetBiometricButton();

    var password = {
        id: 'password',
        name: 'Enter password',
        desc: 'Type your account password on the login form',
        icon: 'bi-lock-fill',
        accentClass: 'bio-method-icon-box--password'
    };
    function showChoices(message, methods, onSelect, progress) {
        openBiometricModal({
            title: 'Sign in with biometrics',
            headerIcon: 'bi-shield-lock',
            identifier: identifier,
            message: message,
            progress: progress,
            methods: methods.concat(password),
            hidePrimaryBtn: true,
            secondaryBtnText: false,
            showCancelBtn: true,
            onMethodSelect: function(method) {
                if (method.id === 'password') closeBiometricModalAndFocusPassword();
                else if (method.id === 'face') startFaceVerificationLogin(identifier);
                else onSelect(method);
            }
        });
    }

    showChoices('Choose how to verify your identity.', [], null, 'Checking biometric options…');
    try {
        var results = await Promise.all([
            refreshPasskeySignIn(),
            fetchAvailableBiometricMethods(attemptObj)
        ]);
        if (isStaleBiometricAttempt(attemptObj)) return;

        var capability = results[0];
        var serverMethods = results[1];
        var hasDevicePasskeys = capability.available;
        var cameraFaceSupported = canRequestFaceSignIn(serverMethods);

        if (!hasDevicePasskeys && !cameraFaceSupported) {
            showChoices(passkeyAvailabilityMessage(capability, 'get'), []);
            return;
        }

        var fingerprintOption = {
            id: 'fingerprint',
            name: 'Fingerprint verification',
            desc: capability.supportsFingerprintSpecificUi
                ? 'Use your device’s native fingerprint or biometric prompt to verify and sign in.'
                : 'Use your device’s built-in biometric sensor, facial recognition, or screen lock/PIN (WebAuthn passkey).',
            icon: capability.supportsFingerprintSpecificUi ? 'bi-fingerprint' : 'bi-shield-lock',
            accentClass: 'bio-method-icon-box--fingerprint'
        };
        var methods = [];
        if (hasDevicePasskeys) {
            methods.push(fingerprintOption);
        }
        if (cameraFaceSupported) {
            methods.push(faceSignInOption);
        }

        const choiceMessage = hasDevicePasskeys ? 'Choose how to verify your identity.' : passkeyAvailabilityMessage(capability, 'get');
        showChoices(choiceMessage, methods, function(method) {
            if (method.id === 'fingerprint') {
                startFingerprintWithIdentifier(identifier);
            }
        });
    } catch (err) {
        if (!isStaleBiometricAttempt(attemptObj)) handleBiometricSignInError(err, identifier, null);
    }
}

async function performBiometricLogin(studentNumber, opts, allowSavedPasskey) {
    if (biometricRedirectStarted || isBioPending) return;
    var attemptObj = startNewBiometricAttempt();
    isBioPending = true;

    try {
        showBiometricProgress('Verify with fingerprint', 'Use your fingerprint sensor or follow the device unlock prompt.', studentNumber, 'Waiting for your device…', { icon: 'bi-fingerprint' });
        const assertionData = await runWithCredentialWatchdog(
            () => window.DevicePasskeys.verifyFingerprint(opts, attemptObj.signal, allowSavedPasskey), opts.timeout, attemptObj
        );
        if (isStaleBiometricAttempt(attemptObj)) return;
        const credentialId = assertionData.id;
        showBiometricProgress('Finishing sign-in', 'Checking your device verification.', studentNumber, 'Completing sign-in…', { dismissible: false });

        var devKey = (typeof window.getOrCreateDeviceKey === 'function')
            ? window.getOrCreateDeviceKey()
            : (localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || '');

        var loginFetch = await fetchJsonWithTimeout('{{ route("webauthn.login") }}', {
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
                biometric_method: 'fingerprint',
                qr_token: biometricQrToken,
                device_key: devKey,
                device_fingerprint: devKey
            })
        }, 20000, attemptObj);
        if (isStaleBiometricAttempt(attemptObj)) return;

        var loginRes = loginFetch.response;
        var result = loginFetch.data;

        if (!loginRes.ok || !result || !result.success) {
            if (loginRes.status === 419) {
                throw createWebAuthnError('SessionExpiredError', 'Your session security token expired. Refresh the page and try again.');
            }
            if (loginRes.status === 429) {
                throw createWebAuthnError('RateLimitError', (result && result.message) || 'Too many sign-in attempts. Wait a moment and try again.');
            }
            if (result && result.code === 'CREDENTIAL_REVOKED') {
                window.DevicePasskeys?.forgetBrowserPasskey?.(credentialId);
                throw createWebAuthnError('NotFoundError', result.message || 'This device credential was revoked. Register this device again or sign in with your password.', { code: 'CREDENTIAL_REVOKED' });
            }
            if (result && result.code === 'PASSKEY_CHALLENGE_EXPIRED') {
                throw createWebAuthnError('TimeoutError', result.message || 'The verification challenge expired. Start sign-in again.', { code: 'PASSKEY_CHALLENGE_EXPIRED' });
            }
            throw createWebAuthnError('VerificationFailedError', (result && result.message) || 'Device sign-in could not be completed.', {
                status: loginRes.status,
                code: result && result.code
            });
        }
        completeBiometricLogin(result);
    } catch (err) {
        if (attemptObj.id !== activeBioAttemptId || (attemptObj.signal.aborted && bioAbortReason !== 'timeout')) return;
        handleBiometricSignInError(err, studentNumber, opts);
    }
}

function handleBiometricSignInError(err, studentNumber, opts) {
        console.error('Biometric sign-in error:', err);
        resetBiometricButton();

        var hasAccountCredentials = Boolean(
            (opts && opts.has_account_credentials)
            || (opts && Array.isArray(opts.allowCredentials) && opts.allowCredentials.length > 0)
        );
        var isOutdatedNativeApk = Boolean(
            window.AndroidDeviceBridge?.isNativeApp?.()
            && typeof window.AndroidDeviceBridge?.hasLocalPasskey !== 'function'
        );

        if (isOutdatedNativeApk && (err.name === 'NotSupportedError' || err.name === 'NotFoundError' || err.name === 'TimeoutError')) {
            var apkUrl = document.getElementById('biometricModal')?.dataset?.apkDownloadUrl;
            openBiometricModal({
                title: 'Update app for fingerprint verification',
                headerIcon: 'bi-phone',
                identifier: studentNumber,
                badgeType: 'warning',
                message: 'Install the latest Smart Attendance app update to use the native fingerprint sensor directly, or sign in with Face verification or your password.',
                primaryBtnText: apkUrl ? 'Download app update' : 'Enter password',
                secondaryBtnText: canRequestFaceSignIn(opts) ? 'Face verification' : 'Enter password',
                showCancelBtn: true,
                onPrimaryClick: function() {
                    if (apkUrl) window.location.href = apkUrl;
                    else closeBiometricModalAndFocusPassword();
                },
                onSecondaryClick: function() {
                    if (canRequestFaceSignIn(opts)) startFaceVerificationLogin(studentNumber);
                    else closeBiometricModalAndFocusPassword();
                }
            });
            return;
        }

        if (err.name === 'SessionExpiredError') {
            openBiometricModal({
                title: 'Session expired',
                headerIcon: 'bi-arrow-clockwise',
                identifier: studentNumber,
                message: err.message,
                badgeType: 'warning',
                primaryBtnText: 'Refresh page',
                secondaryBtnText: 'Enter password',
                showCancelBtn: true,
                onPrimaryClick: function() { window.location.reload(); },
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        }

        if (bioAbortReason === 'timeout' || err.name === 'TimeoutError') {
            openBiometricModal({
                title: 'Fingerprint prompt timed out',
                headerIcon: 'bi-clock-history',
                identifier: studentNumber,
                message: 'Your device fingerprint prompt did not open or finish in time. Ensure fingerprint sign-in is registered on this device in Settings, use Face verification, or sign in with your password.',
                badgeType: 'warning',
                primaryBtnText: 'Try again',
                secondaryBtnText: 'Enter password',
                showCancelBtn: true,
                onPrimaryClick: function() { openBiometricSignInChoices(studentNumber); },
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        }

        if (err.name === 'NotAllowedError' || err.name === 'NotFoundError') {
            var fallbackMethods = [];
            if (hasAccountCredentials && err.name !== 'NotFoundError') {
                fallbackMethods.push({
                    id: 'retry_local',
                    name: 'Try fingerprint again',
                    desc: 'Open the fingerprint verification prompt again.',
                    icon: 'bi-fingerprint',
                    accentClass: 'bio-method-icon-box--fingerprint'
                });
            }
            if (studentNumber) {
                fallbackMethods.push({
                    id: 'setup_device',
                    name: 'Register this device',
                    desc: 'Verify your password once and scan your fingerprint to link this device.',
                    icon: 'bi-fingerprint',
                    accentClass: 'bio-method-icon-box--fingerprint'
                });
            }
            if (err.canCheckSavedPasskey) {
                fallbackMethods.push({
                    id: 'saved_passkey', name: 'Use a previously saved passkey',
                    desc: 'Open the device prompt for an earlier registration.',
                    icon: 'bi-key', accentClass: 'bio-method-icon-box--fingerprint'
                });
            }
            if (canRequestFaceSignIn(opts)) {
                fallbackMethods.push(faceSignInOption);
            }
            fallbackMethods.push({
                id: 'password',
                name: 'Enter password',
                desc: 'Type your account password to sign in.',
                icon: 'bi-lock-fill',
                accentClass: 'bio-method-icon-box--password'
            });

            var noLocalTitle = err.name === 'NotFoundError'
                ? 'Link this device’s fingerprint'
                : 'Fingerprint verification incomplete';
            var noLocalMsg = err.name === 'NotFoundError'
                ? 'We couldn’t find a fingerprint registration for this account on this device. Register this device or choose another sign-in option.'
                : 'Fingerprint verification was cancelled or timed out. Try again, register this device, use Face verification, or sign in with your password.';
            openBiometricModal({
                title: noLocalTitle,
                headerIcon: 'bi-fingerprint',
                identifier: studentNumber,
                message: noLocalMsg,
                badgeType: 'warning',
                methods: fallbackMethods,
                hidePrimaryBtn: true,
                secondaryBtnText: false,
                showCancelBtn: true,
                onMethodSelect: function(selected) {
                    if (selected.id === 'retry_local') {
                        startFingerprintWithIdentifier(studentNumber);
                    } else if (selected.id === 'saved_passkey') {
                        startFingerprintWithIdentifier(studentNumber, true);
                    } else if (selected.id === 'setup_device') {
                        openBiometricSetupModal(studentNumber);
                    } else if (selected.id === 'face') {
                        startFaceVerificationLogin(studentNumber);
                    } else {
                        closeBiometricModalAndFocusPassword();
                    }
                }
            });
            return;
        }

        var friendlyMsg;
        if (navigator.onLine === false) {
            friendlyMsg = 'You appear to be offline. Reconnect to the internet and try again, or sign in later.';
        } else if (err.name === 'SecurityError') {
            friendlyMsg = 'This domain or HTTPS configuration blocked biometric sign-in (' + window.location.hostname + ').';
        } else if (err.name === 'NotSupportedError' || err.name === 'InvalidStateError') {
            friendlyMsg = 'This browser or device could not find or use a local fingerprint passkey. Sign in with your password and register this device in Settings.';
        } else {
            friendlyMsg = err.message || 'Device sign-in could not be completed.';
        }

        openBiometricModal({
            title: 'Fingerprint verification unavailable',
            headerIcon: 'bi-fingerprint',
            identifier: studentNumber,
            message: friendlyMsg,
            badgeType: 'warning',
            primaryBtnText: canRequestFaceSignIn(opts) ? 'Use face verification' : 'Try again',
            secondaryBtnText: 'Enter password',
            showCancelBtn: true,
            onPrimaryClick: function() {
                if (canRequestFaceSignIn(opts)) startFaceVerificationLogin(studentNumber);
                else openBiometricSignInChoices(studentNumber);
            },
            onSecondaryClick: closeBiometricModalAndFocusPassword
        });
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
    if (!isBioPending && !biometricRedirectStarted) refreshPasskeySignIn();

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

    var cancelBtn = document.getElementById('bioModalCancelBtn');
    if (cancelBtn && cancelBtn.dataset.biometricBound !== 'true') {
        cancelBtn.dataset.biometricBound = 'true';
        cancelBtn.addEventListener('click', closeBiometricModal);
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

    var bioModalId = document.getElementById('bioModalIdentifierInput');
    if (bioModalId && bioModalId.dataset.biometricBound !== 'true') {
        bioModalId.dataset.biometricBound = 'true';
        bioModalId.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (typeof currentPrimaryModalCallback === 'function') {
                    currentPrimaryModalCallback(e);
                }
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
window.addEventListener('pageshow', function(e) {
    if (e && e.persisted) {
        biometricRedirectStarted = false;
        resetBiometricButton();
        closeBiometricModal();
    }
    setupBiometricListeners();
});
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && !isBioPending && !biometricRedirectStarted) refreshPasskeySignIn();
});
window.addEventListener('pagehide', function() {
    if (!biometricRedirectStarted) closeBiometricModal();
});
// Expose functions globally on window
window.handleBiometricLogin = handleBiometricLogin;
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
@include('partials.pwa-body')
</body>
</html>
