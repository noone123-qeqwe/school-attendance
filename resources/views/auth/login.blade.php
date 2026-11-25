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
            <div id="fingerprintSection">
                <!-- Inline message area for biometric feedback -->
                <div id="fpMessage" role="status" style="display:none;"></div>
                <p class="fp-row-hint" id="passkeyAvailability" role="status"></p>
                <div id="passkeyControls" hidden>
                    <button type="button" class="fp-row anim-fade-up anim-d5" id="fpRowBtn" aria-label="Sign in with a passkey" aria-haspopup="dialog" aria-controls="biometricModal" disabled>
                        <div class="fp-row-left">
                            <div class="fp-icon-wrap">
                                <i class="bi bi-shield-lock" id="fpIcon"></i>
                            </div>
                            <div>
                                <div class="fp-row-label" id="fpLabel">Sign in with a passkey</div>
                                <div class="fp-row-hint" id="fpHint">Follow your browser’s passkey prompt</div>
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
<div id="biometricModal" class="bio-modal-overlay" style="display:none;" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="bioModalTitle" aria-describedby="bioModalDesc">
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
                <h2 class="bio-modal-title" id="bioModalTitle" tabindex="-1">Passkey sign-in</h2>
                <div class="bio-modal-user-pill" id="bioModalUserPill" style="display:none;">
                    <i class="bi bi-person-badge" aria-hidden="true"></i><span id="bioModalUserText"></span>
                </div>
            </header>
            <p class="bio-modal-desc" id="bioModalDesc">Use your device’s secure prompt to sign in.</p>

            <div id="bioModalProgress" class="bio-dialog-progress" role="status" hidden>
                <span id="bioModalProgressIcon" class="biometric-spinner" aria-hidden="true"></span>
                <span id="bioModalProgressText"></span>
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

            <!-- Inline Alert Message -->
            <!-- Action Buttons -->
            <div class="bio-modal-actions">
                <button type="button" class="glass-btn glass-btn-primary" id="bioModalPrimaryBtn">
                    Set up a passkey
                </button>
                <button type="button" class="bio-modal-secondary-btn" id="bioModalSecondaryBtn">
                    Use Password
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
            fpHint.textContent = 'Use a passkey for ' + displayName;
        } else {
            fpHint.textContent = 'Follow your browser’s passkey prompt';
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

var bioAbortController = null;
var biometricReturnFocus = null;
var biometricPreviousOverflow = '';
var isBioPending = false;
var biometricRedirectStarted = false;
var passkeySignInAvailability = null;
var passkeySignInCheck = null;

async function checkPasskeyAvailability(operation) {
    return await window.DevicePasskeys?.check(operation) || { available: false, reason: 'unknown' };
}

function passkeyAvailabilityMessage(result, operation) {
    return window.DevicePasskeys?.message(result, operation)
        || 'Passkey availability couldn’t be checked. Sign in with your password.';
}

function refreshPasskeySignIn() {
    if (passkeySignInCheck) return passkeySignInCheck;

    var status = document.getElementById('passkeyAvailability');
    var controls = document.getElementById('passkeyControls');
    if (status && !passkeySignInAvailability) status.textContent = 'Checking passkey availability…';
    passkeySignInCheck = checkPasskeyAvailability('get').then(function(result) {
        passkeySignInAvailability = result;
        if (status) {
            status.hidden = result.available;
            status.textContent = result.available ? '' : passkeyAvailabilityMessage(result, 'get');
        }
        if (controls) controls.hidden = !result.available;
        if (fpRowBtn) fpRowBtn.disabled = !result.available || isBioPending;
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
    if (!fpRowBtn) return;
    fpRowBtn.disabled = !passkeySignInAvailability?.available;
    fpRowBtn.style.opacity = '1';
    fpRowBtn.style.cursor = 'pointer';
    fpRowBtn.removeAttribute('title');
    if (fpLabel) fpLabel.textContent = 'Sign in with a passkey';
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
    var primaryBtn = document.getElementById('bioModalPrimaryBtn');
    var secondaryBtn = document.getElementById('bioModalSecondaryBtn');
    var cancelBtn = document.getElementById('bioModalCancelBtn');
    var passWrap = document.getElementById('bioModalPasswordWrap');
    var progress = document.getElementById('bioModalProgress');
    var progressText = document.getElementById('bioModalProgressText');
    var progressIcon = document.getElementById('bioModalProgressIcon');
    var wasOpen = modal.classList.contains('active');
    if (!wasOpen) {
        biometricReturnFocus = document.activeElement === document.body ? fpRowBtn : document.activeElement;
        biometricPreviousOverflow = document.body.style.overflow;
    }
    hideFpMessage();
    modal.dataset.tone = config.badgeType || 'info';
    modal.dataset.dismissible = config.complete ? 'false' : 'true';
    if (titleEl) titleEl.textContent = config.title || 'Passkey sign-in';
    if (descEl) descEl.textContent = biometricText(config.message || 'Use your password to sign in or set up a passkey on this device.');
    if (progress) progress.hidden = !config.progress;
    if (progressText) progressText.textContent = config.progress || '';
    if (progressIcon) {
        progressIcon.className = config.complete ? 'bi bi-check-circle-fill bio-progress-complete' : 'biometric-spinner';
    }
    var closeBtn = document.getElementById('bioModalCloseBtn');
    if (closeBtn) closeBtn.hidden = !!config.complete;
    
    if (config.identifier) {
        activeSetupIdentifier = config.identifier;
        if (pillEl) pillEl.style.display = 'inline-flex';
        if (pillTextEl) pillTextEl.textContent = config.identifier;
    } else {
        if (pillEl) pillEl.style.display = 'none';
    }
    
    if (passWrap) {
        passWrap.style.display = config.showPassword ? 'block' : 'none';
    }

    currentPrimaryModalCallback = typeof config.onPrimaryClick === 'function' ? config.onPrimaryClick : handleSetupBiometricsClick;
    currentSecondaryModalCallback = typeof config.onSecondaryClick === 'function' ? config.onSecondaryClick : closeBiometricModalAndFocusPassword;

    if (primaryBtn) {
        primaryBtn.hidden = !!(config.hidePrimaryBtn || config.primaryBtnText === false);
        primaryBtn.style.display = primaryBtn.hidden ? 'none' : '';
        primaryBtn.textContent = biometricText(config.primaryBtnText || 'Set up a passkey');
        primaryBtn.disabled = !!config.primaryDisabled;
    }

    if (secondaryBtn) {
        if (config.secondaryBtnText === '' || config.secondaryBtnText === false) {
            secondaryBtn.hidden = true;
        } else {
            secondaryBtn.hidden = false;
            secondaryBtn.textContent = biometricText(config.secondaryBtnText || 'Use password');
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
        var focusTarget = config.showPassword ? document.getElementById('bioModalPasswordInput') : titleEl;
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
        progress: options.complete ? 'Your account is ready.' : progress,
        secondaryBtnText: options.complete ? false : 'Cancel',
        onSecondaryClick: closeBiometricModal
    });
}

function closeBiometricModal() {
    if (bioAbortController) {
        try { bioAbortController.abort(); } catch(e) {}
        bioAbortController = null;
    }
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
    openBiometricModal({
        title: 'Passkey setup unavailable',
        identifier: identifier,
        message: passkeyAvailabilityMessage(capability, 'create'),
        badgeType: 'warning',
        primaryBtnText: 'Sign in with password',
        secondaryBtnText: false,
        onPrimaryClick: closeBiometricModalAndFocusPassword
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
        title: 'Set up a passkey',
        identifier: activeSetupIdentifier,
        message: 'Check your account details. To set up a passkey on this device, confirm your account password.',
        badgeType: 'warning',
        primaryBtnText: 'Set up a passkey',
        secondaryBtnText: 'Sign in with password',
        onPrimaryClick: handleSetupBiometricsClick,
        onSecondaryClick: closeBiometricModalAndFocusPassword
    });
}

async function handleSetupBiometricsClick() {
    if (isBioPending) return;
    var passInputInForm = document.getElementById('loginPassword');
    var passInputInModal = document.getElementById('bioModalPasswordInput');
    
    var password = passInputInModal?.value || passInputInForm?.value || '';

    if (!password) {
        openBiometricModal({
            title: 'VERIFY YOUR ACCOUNT',
            identifier: activeSetupIdentifier,
            message: 'Confirm your account password before setting up a passkey on this device.',
            showPassword: true,
            badgeType: 'warning',
            primaryBtnText: 'Verify and set up passkey',
            secondaryBtnText: 'USE PASSWORD',
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

    var attempt = new AbortController();
    bioAbortController?.abort();
    bioAbortController = attempt;
    isBioPending = true;
    showBiometricProgress('Preparing passkey setup', 'Checking passkey availability on this device.', identifier, 'Checking this browser…');

    try {
        var capability = await checkPasskeyAvailability('create');
        if (attempt.signal.aborted) return;
        if (!capability.available) {
            resetBiometricButton();
            showPasskeySetupUnavailable(identifier, capability);
            return;
        }

        showBiometricProgress('Checking your password', 'After this check, your device will ask you to confirm setup.', identifier, 'Step 1 of 2 · Checking account');
        var optRes = await fetch('{{ route("webauthn.setup.options") }}', {
            method: 'POST',
            signal: attempt.signal,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ identifier: identifier, password: password })
        });

        var opts = await optRes.json();
        if (attempt.signal.aborted) return;

        if (!optRes.ok || !opts.success) {
            resetBiometricButton();
            var errMsg = opts.message || 'Verification failed. Please check your password.';
            openBiometricModal({
                title: 'ACCOUNT VERIFICATION FAILED',
                identifier: identifier,
                message: errMsg,
                showPassword: true,
                badgeType: 'danger',
                primaryBtnText: '<i class="bi bi-arrow-repeat me-2"></i>TRY AGAIN',
                secondaryBtnText: 'USE PASSWORD',
                onPrimaryClick: handleSetupBiometricsClick,
                onSecondaryClick: closeBiometricModalAndFocusPassword
            });
            return;
        }

        showBiometricProgress('Confirm on your device', 'Follow the device prompt to create your passkey.', identifier, 'Step 2 of 2 · Waiting for your device', { icon: 'bi-phone' });

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

        // Register a platform passkey with local user verification.
        const loginPrimarySelection = {
            authenticatorAttachment: 'platform',
            userVerification: 'required',
            residentKey: 'preferred',
            requireResidentKey: false
        };

        let credential = null;
        try {
            credential = await navigator.credentials.create({
                signal: attempt.signal,
                publicKey: Object.assign({}, basePublicKey, {
                    authenticatorSelection: loginPrimarySelection
                })
            });
        } catch (firstErr) {
            if (attempt.signal.aborted || firstErr.name === 'AbortError' || firstErr.name === 'NotAllowedError') {
                throw firstErr;
            }
            console.warn('Biometric setup (primary attempt) failed, retrying with platform discouraged fallback:', firstErr);
            try {
                credential = await navigator.credentials.create({
                    signal: attempt.signal,
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

        if (attempt.signal.aborted) return;
        if (!credential) {
            throw new Error('Biometric setup was cancelled.');
        }

        showBiometricProgress('Saving biometric sign-in', 'Your device confirmed setup. We’re linking it to your account.', identifier, 'Saving device credential…');
        var rawId = bufferToBase64Url(credential.rawId);
        var clientDataJSON = bufferToBase64Url(credential.response.clientDataJSON);
        var attestationObject = bufferToBase64Url(credential.response.attestationObject);

        var saveRes = await fetch('{{ route("webauthn.setup.register") }}', {
            method: 'POST',
            signal: attempt.signal,
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
                device_name: navigator.userAgent.includes('Mobile') ? 'Mobile Device' : 'Desktop Browser'
            })
        });

        var regResult = await saveRes.json();
        if (attempt.signal.aborted) return;

        if (regResult.success) {
            resetBiometricButton();
            try {
                saveAccount({ identifier: identifier, name: identifier });
                if (idInput) {
                    idInput.value = identifier;
                    if (typeof updateAccountBanner === 'function') updateAccountBanner();
                }
            } catch (e) {}

            openBiometricModal({
                title: 'Passkey ready',
                identifier: identifier,
                message: 'You can now use this passkey to sign in.',
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
        if (attempt.signal.aborted) return;
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

// Handle biometric login button click
async function handleBiometricLogin() {
    if (biometricRedirectStarted) return;
    hideFpMessage();

    if (isBioPending) return;

    var identifier = idInput ? idInput.value.trim() : '';
    if (!identifier && typeof getSavedAccounts === 'function') {
        var savedAccs = getSavedAccounts();
        if (savedAccs && savedAccs.length === 1 && savedAccs[0].identifier) {
            identifier = savedAccs[0].identifier;
            if (idInput) idInput.value = identifier;
        }
    }
    if (!identifier && window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.getLastIdentifier === 'function') {
        var lastNativeId = window.AndroidDeviceBridge.getLastIdentifier();
        if (lastNativeId && lastNativeId.trim()) {
            identifier = lastNativeId.trim();
            if (idInput) idInput.value = identifier;
        }
    }

    return openBiometricSignInChoices(identifier);
}

async function openBiometricSignInChoices(identifier) {
    bioAbortController?.abort();
    var attempt = new AbortController();
    bioAbortController = attempt;
    isBioPending = true;
    showBiometricProgress('Checking sign-in options', 'Checking passkey availability in this browser.', identifier, 'Checking this browser…');

    var capability = await refreshPasskeySignIn();
    if (attempt.signal.aborted) return;
    resetBiometricButton();
    openBiometricModal({
        title: 'Choose how to sign in',
        identifier: identifier,
        message: capability.available
            ? 'Use a passkey to open your browser’s sign-in options, or use your account password. Your device controls the available verification methods.'
            : passkeyAvailabilityMessage(capability, 'get'),
        badgeType: 'info',
        primaryBtnText: capability.available ? 'Use a passkey' : false,
        secondaryBtnText: 'Sign in with password',
        showCancelBtn: true,
        onPrimaryClick: function() { performBiometricLogin(identifier); },
        onSecondaryClick: closeBiometricModalAndFocusPassword
    });
}

async function performBiometricLogin(studentNumber) {
    if (biometricRedirectStarted) return;
    studentNumber = (studentNumber || '').trim();

    // Abort previous prompt if any
    if (bioAbortController) {
        try { bioAbortController.abort(); } catch(e) {}
    }
    var attempt = new AbortController();
    bioAbortController = attempt;
    isBioPending = true;
    showBiometricProgress('Preparing passkey sign-in', 'Checking whether this browser can use a passkey.', studentNumber, 'Checking this browser…');

    var savedAccounts = !studentNumber ? getSavedAccounts() : [];
    var savedIds = savedAccounts.map(function(a) { return a.identifier; }).filter(Boolean);

    try {
        var capability = await refreshPasskeySignIn();
        if (attempt.signal.aborted) return;
        if (!capability.available) {
            closeBiometricModalAndFocusPassword();
            return;
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
                    biometric_method: 'device_lock',
                    saved_identifiers: savedIds
                }),
                signal: attempt.signal
        });
        var opts = await optRes.json();
        if (attempt.signal.aborted) return;

        if (opts?.code === 'BIOMETRIC_UNAVAILABLE' && studentNumber) {
            await openBiometricSetupModal(studentNumber, attempt.signal);
            return;
        }

        if (!optRes.ok || !opts?.success) {
            throw new Error(optRes.status === 429
                ? 'Too many sign-in attempts. Wait a moment and try again.'
                : 'Passkey sign-in could not be started.');
        }

        var allowCredentials = (opts.allowCredentials || []).map(function(credential) {
            return {
                type: credential.type || 'public-key',
                id: base64ToUint8Array(credential.id),
                transports: credential.transports || ['internal', 'hybrid']
            };
        });

        if (studentNumber && allowCredentials.length === 0) {
            await openBiometricSetupModal(studentNumber, attempt.signal);
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
        
        if (effectiveRpId) {
            getPublicKey.rpId = effectiveRpId;
        }

        showBiometricProgress('Confirm your passkey', 'Follow your browser’s passkey prompt.', studentNumber, 'Waiting for confirmation…', { icon: 'bi-phone' });

        var assertion = null;
        try {
            assertion = await navigator.credentials.get({
                publicKey: getPublicKey,
                signal: attempt.signal
            });
        } catch (firstErr) {
            if (attempt.signal.aborted || firstErr.name === 'AbortError' || firstErr.name === 'NotAllowedError') {
                throw firstErr;
            }

            // If allowCredentials failed on this device (e.g. user presented another enrolled credential),
            // attempt discoverable passkey before giving up
            if (!studentNumber && getPublicKey.allowCredentials) {
                console.warn('Biometric query with allowCredentials failed, trying discoverable passkey fallback...', firstErr);
                var fallbackPublicKey = Object.assign({}, getPublicKey);
                delete fallbackPublicKey.allowCredentials;
                try {
                    assertion = await navigator.credentials.get({
                        publicKey: fallbackPublicKey,
                        signal: attempt.signal
                    });
                } catch (fallbackErr) {
                    if (fallbackErr.name === 'AbortError') throw fallbackErr;
                    throw firstErr;
                }
            } else {
                throw firstErr;
            }
        }

        if (attempt.signal.aborted) return;
        if (!assertion) {
            throw new Error('Authentication was cancelled or failed.');
        }

        showBiometricProgress('Finishing sign-in', 'Your device confirmed the prompt.', studentNumber, 'Signing you in…');

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
            signal: attempt.signal,
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
                biometric_method: 'device_lock',
                device_key: devKey,
                device_fingerprint: devKey
            })
        });
        
        var result = await loginRes.json();
        if (attempt.signal.aborted) return;

        if (!loginRes.ok || !result.success) {
            throw new Error('Passkey sign-in could not be completed.');
        }
        completeBiometricLogin(result);
    } catch (err) {
        if (attempt.signal.aborted) return;
        console.error('Passkey sign-in error:', err);
        resetBiometricButton();

        if (err.name === 'AbortError') {
            closeBiometricModal();
            return;
        }

        var promptIncomplete = err.name === 'NotAllowedError';
        openBiometricModal({
            title: promptIncomplete ? 'Device prompt didn’t finish' : 'Passkey sign-in unavailable',
            identifier: studentNumber,
            message: promptIncomplete
                ? 'Try again on a device that has your passkey, or sign in with your password.'
                : (err.message || 'Passkey sign-in could not be completed.') + ' Try again or sign in with your password.',
            badgeType: 'warning',
            primaryBtnText: 'Try again',
            secondaryBtnText: 'Sign in with password',
            onPrimaryClick: function() {
                closeBiometricModal();
                performBiometricLogin(studentNumber);
            },
            onSecondaryClick: closeBiometricModalAndFocusPassword
        });
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
window.addEventListener('pageshow', setupBiometricListeners);
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && !isBioPending && !biometricRedirectStarted) refreshPasskeySignIn();
});
window.addEventListener('pagehide', function() {
    if (!biometricRedirectStarted) closeBiometricModal();
});
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
