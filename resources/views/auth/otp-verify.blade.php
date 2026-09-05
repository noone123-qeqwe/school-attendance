@extends('layouts.app')

@section('content')
<style>
    body { background: #100608; color: #f8e7d3; }
    .otp-wrapper {
        display: flex; align-items: center; justify-content: center;
        min-height: calc(100vh - 64px);
        padding: 40px 20px;
        background: radial-gradient(circle at top left, rgba(216,179,92,0.08), transparent 28%),
                    linear-gradient(135deg, #150a07 0%, #2a1112 40%, #17080a 100%);
        position: relative; overflow: hidden;
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
        max-width: 420px; width: 100%; border-radius: 28px;
        padding: 44px 36px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.14);
        box-shadow: 0 28px 80px rgba(0,0,0,0.35);
        position: relative; z-index: 1;
        transition: transform .3s, box-shadow .3s;
    }
    .otp-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 36px 90px rgba(0,0,0,0.42);
    }
    .otp-icon {
        width: 72px; height: 72px; border-radius: 50%;
        background: rgba(216,179,92,0.18);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 20px;
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
        width: 100%; padding: 14px 14px;
        border-radius: 14px; border: 1.5px solid rgba(255,255,255,0.14);
        font-size: .95rem; font-family: 'Inter', sans-serif;
        background: rgba(255,255,255,0.06); color: #f8e7d3;
        transition: all .2s; outline: none;
    }
    .field-input:hover { border-color: rgba(255,255,255,0.22); }
    .field-input:focus {
        border-color: rgba(216,179,92,0.6);
        background: rgba(255,255,255,0.12);
        box-shadow: 0 0 0 3px rgba(216,179,92,0.12);
    }
    .otp-inputs { display: flex; gap: 12px; justify-content: center; margin: 20px 0; }
    .otp-digit {
        width: 54px; height: 62px; border-radius: 16px;
        border: 1.5px solid rgba(255,255,255,0.14);
        font-size: 1.5rem; font-weight: 800; text-align: center;
        color: #f8e7d3;
        background: rgba(255,255,255,0.06);
        outline: none; transition: all .2s;
    }
    .otp-digit:focus {
        border-color: rgba(216,179,92,0.7);
        background: rgba(255,255,255,0.14);
        box-shadow: 0 0 0 3px rgba(216,179,92,0.12);
    }
    .submit-btn {
        width: 100%; padding: 14px;
        background: linear-gradient(135deg, #d8b35c, #b8974d);
        color: #2b0507; font-weight: 700; font-size: .95rem;
        border: none; border-radius: 14px; cursor: pointer;
        transition: all .25s; box-shadow: 0 8px 24px rgba(216,179,92,.25);
        margin-top: 8px;
    }
    .submit-btn:hover {
        background: linear-gradient(135deg, #c9a551, #a7843f);
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(216,179,92,.3);
    }
    .submit-btn:active { transform: translateY(0); }
    .alert-err {
        background: rgba(220,38,38,0.18);
        border: 1px solid rgba(220,38,38,0.35);
        color: #f8c6c6; border-radius: 14px;
        padding: 12px 14px; font-size: .88rem;
        margin-bottom: 16px;
    }
    .alert-info {
        background: rgba(59,130,246,0.12);
        border: 1px solid rgba(59,130,246,0.28);
        color: #bfdbfe; border-radius: 14px;
        padding: 12px 14px; font-size: .88rem;
        margin-bottom: 16px;
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
        <div class="text-center mb-4">
            <h2 class="reset-title" style="font-size:1.4rem; font-weight:800; letter-spacing:0.5px;">VERIFY YOUR EMAIL</h2>
            @php
                $effectiveIdentifier = $identifier ?? session('otp_identifier') ?? old('identifier', old('email', ''));
            @endphp
            <p class="reset-subtitle" style="font-size:0.92rem; color:rgba(248,231,211,0.8); line-height:1.5; margin-top:8px;">
                We sent a verification code to:
                @if(!empty($effectiveIdentifier))
                <br><strong style="color:#ffffff; font-size:1.05rem;">{{ $effectiveIdentifier }}</strong>
                @endif
            </p>
            <p style="font-size:0.8rem; color:rgba(248,231,211,0.65); margin-top:6px;">
                ⚠️ Check your <strong>Spam / Junk folder</strong> if the email does not appear in your inbox.
            </p>
        </div>

        <div id="verify-alert" class="alert-err" style="display:none;"></div>
        <div id="verify-success" class="alert-info" style="display:none; background:rgba(34,197,94,0.18); border-color:rgba(34,197,94,0.4); color:#86efac;"></div>

        @if(session('info'))
        <div class="alert-info"><i class="bi bi-info-circle me-2"></i>{{ session('info') }}</div>
        @endif
        @if($errors->any())
        <div class="alert-err"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('otp.verify') }}" id="otpForm">
            @csrf
            <input type="hidden" name="purpose" value="{{ $purpose }}">
            <input type="hidden" name="identifier" id="identifierInput" value="{{ $effectiveIdentifier }}">
            <input type="hidden" name="otp" id="otpHidden">

            @if(empty($effectiveIdentifier))
            <div style="margin-bottom:16px;">
                <label class="field-label">Email / Student Number / Employee ID</label>
                <input type="text" class="field-input" id="identifierVisible" placeholder="Email, student no., or employee ID" value="{{ old('identifier', old('email')) }}" oninput="document.getElementById('identifierInput').value=this.value" autocomplete="username" required>
            </div>
            @endif

            <!-- 6 digit boxes -->
            <div class="otp-inputs">
                @for($i = 1; $i <= 6; $i++)
                <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" id="d{{ $i }}" autocomplete="off">
                @endfor
            </div>

            <div class="text-center mb-3" style="font-size:0.85rem; color:rgba(248,231,211,0.7);">
                Code expires in <span id="expiry-timer" style="color:#d8b35c; font-weight:700; font-variant-numeric: tabular-nums;">10:00</span>
            </div>

            <button type="submit" class="submit-btn" id="verifyBtn">
                <i class="bi bi-check2-circle me-2"></i>VERIFY
            </button>
        </form>

        <div class="text-center mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,0.08);">
            <p style="font-size: 0.85rem; color: rgba(248,231,211,0.7); margin-bottom: 8px;">Didn't receive the code?</p>
            <button type="button" class="submit-btn d-inline-flex align-items-center justify-content-center" id="btn-resend-forgot" onclick="resendForgotOtp()" style="background:rgba(255,255,255,0.08); color:#f8e7d3; border:1px solid rgba(255,255,255,0.18); width:auto; padding:10px 24px; font-size:0.88rem; box-shadow:none; margin:0 auto;">
                <i class="bi bi-arrow-clockwise me-1"></i> RESEND OTP
            </button>
            <div id="resend-cooldown-text" style="font-size:0.82rem; color:#d8b35c; margin-top:8px; display:none; font-weight:500;">
                Resend available in <span id="resend-seconds" style="font-weight:700;">30</span> seconds
            </div>
        </div>

        <div class="resend-link mt-3">
            <a href="{{ route('login') }}" style="color:rgba(248,231,211,0.6); font-size:0.85rem;"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a>
        </div>
    </div>
</div>

<script>
const digits = document.querySelectorAll('.otp-digit');
digits.forEach((input, idx) => {
    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
        if (e.target.value && idx < digits.length - 1) digits[idx + 1].focus();
        updateHidden();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) digits[idx - 1].focus();
    });
    input.addEventListener('paste', (e) => {
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        paste.split('').forEach((ch, i) => { if (digits[i]) digits[i].value = ch; });
        updateHidden();
        e.preventDefault();
        if (digits[5]) digits[5].focus();
    });
});

function updateHidden() {
    document.getElementById('otpHidden').value = Array.from(digits).map(d => d.value).join('');
}

document.getElementById('otpForm').addEventListener('submit', (e) => {
    updateHidden();
    const otp = document.getElementById('otpHidden').value;
    if (otp.length !== 6) { 
        e.preventDefault(); 
        showVerifyError('Please enter all 6 digits of your verification code.'); 
        return;
    }
    const btn = document.getElementById('verifyBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying...';
});

function showVerifyError(msg) {
    const errEl = document.getElementById('verify-alert');
    const succEl = document.getElementById('verify-success');
    if (succEl) succEl.style.display = 'none';
    if (!errEl) return;
    errEl.innerHTML = `<i class="bi bi-exclamation-circle me-2"></i>${msg}`;
    errEl.style.display = 'block';
}

function showVerifySuccess(msg) {
    const errEl = document.getElementById('verify-alert');
    const succEl = document.getElementById('verify-success');
    if (errEl) errEl.style.display = 'none';
    if (!succEl) return;
    succEl.innerHTML = `<i class="bi bi-check-circle me-2"></i>${msg}`;
    succEl.style.display = 'block';
}

// 10-minute expiry timer
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

// 30-second resend cooldown timer
let cooldownInterval;
function startResendCooldown(seconds = 30) {
    clearInterval(cooldownInterval);
    const resendBtn = document.getElementById('btn-resend-forgot');
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

let isResendingForgot = false;

function resendForgotOtp() {
    if (isResendingForgot) {
        console.warn('OTP resend already in progress. Ignoring duplicate click.');
        return;
    }

    const identifier = document.getElementById('identifierInput')?.value?.trim();
    if (!identifier) {
        showVerifyError('Please specify your registered email or identifier.');
        return;
    }

    const requestId = (typeof crypto !== 'undefined' && crypto.randomUUID)
        ? crypto.randomUUID()
        : 'req_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);

    console.log("OTP REQUEST START\nRequest ID: " + requestId);

    isResendingForgot = true;
    const resendBtn = document.getElementById('btn-resend-forgot');
    const originalHtml = resendBtn.innerHTML;
    resendBtn.disabled = true;
    resendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';

    fetch('{{ route("otp.forgot.send") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Request-Id': requestId,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ identifier: identifier, request_id: requestId })
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
        isResendingForgot = false;
        resendBtn.innerHTML = originalHtml;
        if (data.success) {
            showVerifySuccess('A new verification code has been sent.');
            startExpiryTimer(600);
            startResendCooldown(data.cooldown || data.retryAfter || 30);
            digits.forEach(d => d.value = '');
            digits[0].focus();
        } else {
            resendBtn.disabled = false;
            showVerifyError(data.message || 'Unable to send verification code. Please try again.');
        }
    }).catch(err => {
        isResendingForgot = false;
        resendBtn.innerHTML = originalHtml;
        showVerifyError(err.message || 'Unable to send verification code. Please try again.');
        if (err.cooldown) {
            startResendCooldown(err.cooldown);
        } else {
            resendBtn.disabled = false;
        }
    });
}

// Auto-focus and start timer
digits[0].focus();
startExpiryTimer(600);
startResendCooldown(30);
</script>
@endsection
