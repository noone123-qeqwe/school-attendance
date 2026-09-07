@extends('layouts.app')

@section('content')
<style>
    body { background: #100608; color: #f8e7d3; }
    .otp-wrapper {
        display: flex; align-items: center; justify-content: center;
        min-height: calc(100vh - 64px);
        padding: 40px 16px;
        background: radial-gradient(circle at top left, rgba(216,179,92,0.08), transparent 28%),
                    linear-gradient(135deg, #150a07 0%, #2a1112 40%, #17080a 100%);
        position: relative; overflow: hidden;
        box-sizing: border-box;
    }
    .otp-wrapper::before {
        content: '';
        position: absolute;
        width: 420px; height: 420px;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 72%);
        top: -120px; left: -100px;
        border-radius: 50%; pointer-events: none;
    }
    .otp-card {
        max-width: 440px; width: 100%; border-radius: 28px;
        padding: 40px 28px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.14);
        box-shadow: 0 28px 80px rgba(0,0,0,0.45);
        position: relative; z-index: 1;
        box-sizing: border-box;
        transition: transform .3s, box-shadow .3s;
    }
    .otp-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 36px 90px rgba(0,0,0,0.5);
    }
    .otp-icon {
        width: 68px; height: 68px; border-radius: 50%;
        background: rgba(216,179,92,0.18);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 18px;
        box-shadow: 0 12px 32px rgba(216,179,92,0.22);
    }
    .otp-icon i { color: #f8e7d3; font-size: 1.8rem; }
    .field-label {
        font-size: .72rem; font-weight: 700;
        color: rgba(248,231,211,0.75);
        text-transform: uppercase; letter-spacing: .5px;
        display: block; margin-bottom: 8px;
    }
    .field-input {
        width: 100%; padding: 13px 14px;
        border-radius: 14px; border: 1.5px solid rgba(255,255,255,0.14);
        font-size: .95rem; font-family: 'Inter', sans-serif;
        background: rgba(255,255,255,0.06); color: #f8e7d3;
        transition: all .2s; outline: none; box-sizing: border-box;
    }
    .field-input:hover { border-color: rgba(255,255,255,0.22); }
    .field-input:focus {
        border-color: rgba(216,179,92,0.6);
        background: rgba(255,255,255,0.12);
        box-shadow: 0 0 0 3px rgba(216,179,92,0.12);
    }
    
    /* ── Responsive 6-Digit OTP Container ── */
    .otp-inputs {
        display: flex;
        gap: 8px;
        justify-content: center;
        align-items: center;
        width: 100%;
        max-width: 360px;
        margin: 22px auto 18px auto;
        box-sizing: border-box;
        cursor: text;
        user-select: none;
    }
    .otp-digit {
        flex: 1 1 0;
        min-width: 0;
        max-width: 48px;
        height: 58px;
        border-radius: 14px;
        border: 1.5px solid rgba(255,255,255,0.16);
        font-size: 1.5rem;
        font-weight: 800;
        text-align: center;
        color: #ffffff;
        background: rgba(255,255,255,0.06);
        outline: none;
        transition: border-color 0.2s, background-color 0.2s, box-shadow 0.2s, transform 0.2s;
        box-sizing: border-box;
        caret-color: #d8b35c;
        -webkit-user-select: text;
        user-select: text;
    }
    .otp-digit:hover {
        border-color: rgba(216,179,92,0.4);
        background: rgba(255,255,255,0.09);
    }
    .otp-digit:focus,
    .otp-digit.active {
        border-color: #d8b35c;
        background: rgba(216,179,92,0.14);
        box-shadow: 0 0 0 3px rgba(216,179,92,0.22), 0 0 16px rgba(216,179,92,0.12);
        transform: translateY(-2px);
    }
    .otp-digit.filled {
        border-color: rgba(216,179,92,0.65);
        background: rgba(216,179,92,0.09);
    }
    .otp-digit.is-invalid {
        border-color: rgba(239, 68, 68, 0.8) !important;
        background: rgba(239, 68, 68, 0.1) !important;
    }

    @media (max-width: 480px) {
        .otp-wrapper { padding: 24px 12px; }
        .otp-card { padding: 32px 18px; border-radius: 22px; }
        .otp-inputs { gap: 6px; }
        .otp-digit { height: 50px; font-size: 1.3rem; border-radius: 12px; }
    }
    @media (max-width: 350px) {
        .otp-card { padding: 26px 12px; border-radius: 18px; }
        .otp-inputs { gap: 4px; }
        .otp-digit { height: 44px; font-size: 1.15rem; border-radius: 10px; }
    }

    .submit-btn {
        width: 100%; padding: 14px;
        background: linear-gradient(135deg, #d8b35c, #b8974d);
        color: #2b0507; font-weight: 800; font-size: .95rem;
        border: none; border-radius: 14px; cursor: pointer;
        transition: all .25s; box-shadow: 0 8px 24px rgba(216,179,92,.25);
        margin-top: 8px; letter-spacing: 0.5px;
    }
    .submit-btn:hover:not([disabled]) {
        background: linear-gradient(135deg, #c9a551, #a7843f);
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(216,179,92,.3);
    }
    .submit-btn:active:not([disabled]) { transform: translateY(0); }
    .submit-btn[disabled] {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none !important;
    }

    .alert-err {
        background: rgba(220,38,38,0.18);
        border: 1px solid rgba(220,38,38,0.35);
        color: #f8c6c6; border-radius: 14px;
        padding: 12px 14px; font-size: .88rem;
        margin-bottom: 16px; text-align: left; line-height: 1.4;
    }
    .alert-info {
        background: rgba(59,130,246,0.12);
        border: 1px solid rgba(59,130,246,0.28);
        color: #bfdbfe; border-radius: 14px;
        padding: 12px 14px; font-size: .88rem;
        margin-bottom: 16px; text-align: left; line-height: 1.4;
    }
    .resend-link {
        text-align: center; margin-top: 16px;
        font-size: .85rem; color: rgba(248,231,211,0.7);
    }
    .resend-link a {
        color: #d8b35c; font-weight: 700; text-decoration: none;
    }
    .resend-link a:hover { text-decoration: underline; }
</style>

<div class="otp-wrapper">
    <div class="otp-card">
        <div class="otp-icon"><i class="bi bi-shield-check"></i></div>
        <div class="text-center mb-3">
            <h2 class="reset-title" style="font-size:1.4rem; font-weight:800; letter-spacing:0.5px; margin-bottom:8px;">VERIFY YOUR EMAIL</h2>
            @php
                $effectiveIdentifier = $identifier ?? session('otp_identifier') ?? old('identifier', old('email', ''));
            @endphp
            <p class="reset-subtitle" style="font-size:0.92rem; color:rgba(248,231,211,0.8); line-height:1.5; margin:0;">
                We sent a verification code to:
                @if(!empty($effectiveIdentifier))
                <br><strong style="color:#ffffff; font-size:1.05rem; word-break:break-all;">{{ $effectiveIdentifier }}</strong>
                @endif
            </p>
            <div style="margin-top:10px; display:inline-flex; align-items:center; gap:6px; background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.3); border-radius:10px; padding:6px 12px; font-size:0.8rem; color:#fde68a;">
                <span>⚠️ Check your <strong>Spam / Junk folder</strong> if the email does not appear in your inbox.</span>
            </div>
        </div>

        <div id="verify-alert" class="alert-err" style="display:none;"></div>
        <div id="verify-success" class="alert-info" style="display:none; background:rgba(34,197,94,0.18); border-color:rgba(34,197,94,0.4); color:#86efac;"></div>

        @if(session('info'))
        <div class="alert-info"><i class="bi bi-info-circle me-2"></i>{{ session('info') }}</div>
        @endif
        @if($errors->any())
        <div class="alert-err" id="server-alert-err"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('otp.verify') }}" id="otpForm" novalidate>
            @csrf
            <input type="hidden" name="purpose" value="{{ $purpose }}">
            <input type="hidden" name="identifier" id="identifierInput" value="{{ $effectiveIdentifier }}">
            <input type="hidden" name="otp" id="otpHidden" value="{{ old('otp', '') }}">

            @if(empty($effectiveIdentifier))
            <div style="margin-bottom:16px;">
                <label class="field-label">Email / Student Number / Employee ID</label>
                <input type="text" class="field-input" id="identifierVisible" placeholder="Email, student no., or employee ID" value="{{ old('identifier', old('email')) }}" autocomplete="username" required>
            </div>
            @endif

            <!-- 6 digit boxes (Auto-centered & Responsive) -->
            <div class="otp-inputs" id="otpInputsContainer" title="Click anywhere to enter code">
                @php
                    $oldOtpDigits = str_split(substr((string)old('otp', ''), 0, 6));
                @endphp
                @for($i = 0; $i < 6; $i++)
                <input type="text"
                       name="otp_digits[]"
                       class="otp-digit {{ !empty($oldOtpDigits[$i]) ? 'filled' : '' }}"
                       maxlength="1"
                       inputmode="numeric"
                       pattern="[0-9]*"
                       id="d{{ $i + 1 }}"
                       data-index="{{ $i }}"
                       value="{{ $oldOtpDigits[$i] ?? '' }}"
                       autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                       aria-label="OTP Digit {{ $i + 1 }}">
                @endfor
            </div>

            <div class="text-center mb-3" style="font-size:0.85rem; color:rgba(248,231,211,0.75);">
                Code expires in <span id="expiry-timer" style="color:#d8b35c; font-weight:700; font-variant-numeric: tabular-nums;">10:00</span>
            </div>

            <button type="submit" class="submit-btn" id="verifyBtn">
                <i class="bi bi-check2-circle me-2"></i>VERIFY
            </button>
        </form>

        <div class="text-center mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,0.08);">
            <p style="font-size: 0.85rem; color: rgba(248,231,211,0.7); margin-bottom: 8px;">Didn't receive the code?</p>
            <button type="button" class="submit-btn d-inline-flex align-items-center justify-content-center" id="btn-resend-forgot" style="background:rgba(255,255,255,0.08); color:#f8e7d3; border:1px solid rgba(255,255,255,0.18); width:auto; padding:10px 24px; font-size:0.88rem; box-shadow:none; margin:0 auto; cursor:pointer;">
                <i class="bi bi-arrow-clockwise me-1"></i> RESEND OTP
            </button>
            <div id="resend-cooldown-text" style="font-size:0.82rem; color:#d8b35c; margin-top:8px; display:none; font-weight:500;">
                Resend available in <span id="resend-seconds" style="font-weight:700;">30</span> seconds
            </div>
        </div>

        <div class="resend-link mt-3">
            <a href="{{ route('login') }}" style="color:rgba(248,231,211,0.6); font-size:0.85rem;"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a>
        </div>

        <div class="text-center mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,0.06); font-size:0.8rem; color:rgba(248,231,211,0.6);">
            <a href="{{ route('privacy') }}" target="_blank" style="color:rgba(248,231,211,0.6); text-decoration:none;" onmouseover="this.style.color='#d8b35c'" onmouseout="this.style.color='rgba(248,231,211,0.6)'">Privacy Policy</a>
            <span class="mx-2" style="opacity:0.3;">|</span>
            <a href="{{ route('terms') }}" target="_blank" style="color:rgba(248,231,211,0.6); text-decoration:none;" onmouseover="this.style.color='#d8b35c'" onmouseout="this.style.color='rgba(248,231,211,0.6)'">Terms & Conditions</a>
        </div>
    </div>
</div>

<script @cspNonce>
(function() {
    const digits = Array.from(document.querySelectorAll('.otp-digit'));
    const otpHidden = document.getElementById('otpHidden');
    const otpContainer = document.getElementById('otpInputsContainer');
    const otpForm = document.getElementById('otpForm');
    const verifyBtn = document.getElementById('verifyBtn');
    const resendBtn = document.getElementById('btn-resend-forgot');
    const identifierVisible = document.getElementById('identifierVisible');
    const identifierInput = document.getElementById('identifierInput');

    function getCombinedOtp() {
        return digits.map(d => d.value.trim()).join('');
    }

    function syncOtp() {
        const combined = getCombinedOtp();
        if (otpHidden) {
            otpHidden.value = combined;
        }
        digits.forEach(d => {
            if (d.value.trim().length > 0) {
                d.classList.add('filled');
            } else {
                d.classList.remove('filled');
            }
        });
        return combined;
    }

    // Container click -> focus first empty box or the last box
    if (otpContainer) {
        otpContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('otp-digit')) return;
            const firstEmpty = digits.find(d => !d.value.trim());
            if (firstEmpty) {
                firstEmpty.focus();
                firstEmpty.select();
            } else if (digits.length > 0) {
                digits[digits.length - 1].focus();
                digits[digits.length - 1].select();
            }
        });
    }

    function distributeCode(code, startIdx = 0) {
        const chars = code.replace(/\D/g, '').slice(0, 6).split('');
        chars.forEach((ch, idx) => {
            const targetIdx = startIdx + idx;
            if (targetIdx < digits.length) {
                digits[targetIdx].value = ch;
            }
        });
        syncOtp();
        hideVerifyAlerts();

        const nextEmpty = digits.find(d => !d.value.trim());
        if (nextEmpty) {
            nextEmpty.focus();
            nextEmpty.select();
        } else if (digits.length > 0) {
            digits[digits.length - 1].focus();
            digits[digits.length - 1].select();
        }
    }

    digits.forEach((input, idx) => {
        input.addEventListener('focus', function() {
            this.select();
        });

        input.addEventListener('input', function(e) {
            const clean = this.value.replace(/\D/g, '');
            if (clean.length > 1) {
                distributeCode(clean, idx);
                return;
            }
            this.value = clean;
            syncOtp();
            hideVerifyAlerts();
            if (clean && idx < digits.length - 1) {
                digits[idx + 1].focus();
                digits[idx + 1].select();
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace') {
                if (!this.value && idx > 0) {
                    e.preventDefault();
                    digits[idx - 1].value = '';
                    digits[idx - 1].focus();
                    syncOtp();
                } else {
                    setTimeout(syncOtp, 0);
                }
            } else if (e.key === 'ArrowLeft' && idx > 0) {
                e.preventDefault();
                digits[idx - 1].focus();
                digits[idx - 1].select();
            } else if (e.key === 'ArrowRight' && idx < digits.length - 1) {
                e.preventDefault();
                digits[idx + 1].focus();
                digits[idx + 1].select();
            } else if (e.key === 'Delete') {
                this.value = '';
                syncOtp();
            }
        });

        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasteData = (e.clipboardData || window.clipboardData)?.getData('text') || '';
            const cleanPaste = pasteData.replace(/\D/g, '').slice(0, 6);
            if (cleanPaste) {
                distributeCode(cleanPaste, 0);
            }
        });
    });

    if (identifierVisible && identifierInput) {
        identifierVisible.addEventListener('input', function() {
            identifierInput.value = this.value.trim();
        });
    }

    let isSubmitting = false;

    if (otpForm) {
        otpForm.addEventListener('submit', function(e) {
            const otp = syncOtp();

            if (identifierVisible && !identifierInput.value.trim()) {
                e.preventDefault();
                showVerifyError('Please enter your email, student number, or employee ID.');
                identifierVisible.focus();
                return false;
            }

            if (otp.length === 0) {
                e.preventDefault();
                showVerifyError('Please enter your 6-digit verification code.');
                digits[0].focus();
                return false;
            }

            if (otp.length < 6) {
                e.preventDefault();
                showVerifyError('Please enter all 6 digits of your verification code.');
                const firstEmpty = digits.find(d => !d.value.trim()) || digits[0];
                firstEmpty.focus();
                return false;
            }

            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            isSubmitting = true;
            if (verifyBtn) {
                verifyBtn.disabled = true;
                verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>VERIFYING...';
            }
        });
    }

    function hideVerifyAlerts() {
        const errEl = document.getElementById('verify-alert');
        if (errEl) errEl.style.display = 'none';
        const srvErr = document.getElementById('server-alert-err');
        if (srvErr) srvErr.style.display = 'none';
    }

    function showVerifyError(msg) {
        const errEl = document.getElementById('verify-alert');
        const succEl = document.getElementById('verify-success');
        if (succEl) succEl.style.display = 'none';
        const srvErr = document.getElementById('server-alert-err');
        if (srvErr) srvErr.style.display = 'none';
        if (!errEl) return;
        errEl.innerHTML = `<i class="bi bi-exclamation-circle me-2"></i>${msg}`;
        errEl.style.display = 'block';
    }

    function showVerifySuccess(msg) {
        const errEl = document.getElementById('verify-alert');
        const succEl = document.getElementById('verify-success');
        if (errEl) errEl.style.display = 'none';
        const srvErr = document.getElementById('server-alert-err');
        if (srvErr) srvErr.style.display = 'none';
        if (!succEl) return;
        succEl.innerHTML = `<i class="bi bi-check-circle me-2"></i>${msg}`;
        succEl.style.display = 'block';
    }

    // ── Resend OTP Handling ──
    let isResending = false;
    let cooldownInterval;

    function startResendCooldown(seconds = 30) {
        clearInterval(cooldownInterval);
        const cooldownText = document.getElementById('resend-cooldown-text');
        const secondsSpan = document.getElementById('resend-seconds');
        if (!resendBtn || !cooldownText || !secondsSpan) return;

        resendBtn.disabled = true;
        cooldownText.style.display = 'block';
        let remaining = seconds;
        secondsSpan.textContent = remaining;

        cooldownInterval = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                clearInterval(cooldownInterval);
                resendBtn.disabled = false;
                cooldownText.style.display = 'none';
            } else {
                secondsSpan.textContent = remaining;
            }
        }, 1000);
    }

    if (resendBtn) {
        resendBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (isResending) return;

            const identifier = (identifierInput ? identifierInput.value : '') ||
                               (identifierVisible ? identifierVisible.value : '');
            if (!identifier.trim()) {
                showVerifyError('Please specify your registered email or identifier.');
                if (identifierVisible) identifierVisible.focus();
                return;
            }

            const requestId = (typeof crypto !== 'undefined' && crypto.randomUUID)
                ? crypto.randomUUID()
                : 'req_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);

            isResending = true;
            const originalHtml = resendBtn.innerHTML;
            resendBtn.disabled = true;
            resendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sending...';

            fetch('{{ route("otp.forgot.send") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Request-Id': requestId,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ identifier: identifier.trim(), request_id: requestId })
            }).then(async r => {
                const isJson = r.headers.get('content-type')?.includes('application/json');
                const data = isJson ? await r.json() : null;
                if (!r.ok) {
                    const retrySecs = data ? (data.retryAfter || data.retry_after || data.cooldown) : null;
                    let errorMsg = data && data.message ? data.message : 'Unable to send verification code. Please try again.';
                    if (r.status === 429 && retrySecs) {
                        errorMsg = `Please wait ${retrySecs} seconds before requesting another code.`;
                    }
                    const err = new Error(errorMsg);
                    err.status = r.status;
                    err.cooldown = retrySecs;
                    throw err;
                }
                return data;
            }).then(data => {
                isResending = false;
                resendBtn.innerHTML = originalHtml;
                if (data.success) {
                    showVerifySuccess('A new verification code has been sent.');
                    startExpiryTimer(600);
                    startResendCooldown(data.cooldown || data.retryAfter || 30);
                    digits.forEach(d => { d.value = ''; d.classList.remove('filled'); });
                    syncOtp();
                    digits[0].focus();
                } else {
                    resendBtn.disabled = false;
                    showVerifyError(data.message || 'Unable to send verification code. Please try again.');
                }
            }).catch(err => {
                isResending = false;
                resendBtn.innerHTML = originalHtml;
                showVerifyError(err.message || 'Unable to send verification code. Please try again.');
                if (err.cooldown) {
                    startResendCooldown(err.cooldown);
                } else {
                    resendBtn.disabled = false;
                }
            });
        });
    }

    // ── 10-Minute Expiry Timer ──
    let expiryInterval;
    function startExpiryTimer(duration = 600) {
        clearInterval(expiryInterval);
        let time = duration;
        const display = document.getElementById('expiry-timer');
        if (!display) return;
        display.style.color = '#d8b35c';
        const update = () => {
            const m = Math.floor(time / 60);
            const s = time % 60;
            display.textContent = m + ':' + (s < 10 ? '0' : '') + s;
        };
        update();
        expiryInterval = setInterval(() => {
            time--;
            if (time < 0) {
                clearInterval(expiryInterval);
                display.textContent = 'Expired';
                display.style.color = '#ef4444';
            } else {
                update();
            }
        }, 1000);
    }

    // Initial setup
    syncOtp();
    const firstEmpty = digits.find(d => !d.value.trim()) || digits[0];
    firstEmpty.focus();
    startExpiryTimer(600);
    startResendCooldown(30);
})();
</script>
@endsection
