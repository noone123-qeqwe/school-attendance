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
    .resend-link button {
        color: #d8b35c; font-weight: 700; text-decoration: none;
        background: none; border: none; cursor: pointer; padding: 0;
    }
    .resend-link button:hover { text-decoration: underline; }
</style>

<div class="otp-wrapper">
    <div class="otp-card">
        <div class="otp-icon"><i class="bi bi-shield-lock-fill"></i></div>
        <div class="text-center mb-4">
            <h2 class="reset-title">Admin Authentication</h2>
            <p class="reset-subtitle">
                Please enter the 6-digit code sent to your email to verify your identity.
                <br><span style="font-size:0.8rem; color:rgba(248,231,211,0.7); display:inline-block; margin-top:6px;">
                    ⚠️ Check your <strong>Spam / Junk folder</strong> if you don't see it in your inbox.
                </span>
            </p>
        </div>

        @if(app()->environment('local', 'testing') && session('dev_otp'))
        <div id="adminDevOtpBanner" style="background: rgba(216, 179, 92, 0.15); border: 1px solid rgba(216, 179, 92, 0.4); border-radius: 14px; padding: 12px 16px; margin-bottom: 20px; text-align: center;">
            <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.8px; color: #d8b35c; font-weight: 700; margin-bottom: 4px;">
                <i class="bi bi-code-slash me-1"></i> Local Dev 2FA OTP
            </div>
            <div id="adminDevOtpCode" style="font-size: 1.5rem; font-weight: 800; letter-spacing: 5px; color: #fff; font-family: monospace;">
                {{ session('dev_otp') }}
            </div>
            <button type="button" onclick="autofillAdminOtp('{{ session('dev_otp') }}')" style="margin-top: 8px; font-size: 0.78rem; padding: 5px 14px; border-radius: 8px; background: rgba(216, 179, 92, 0.3); border: 1px solid rgba(216, 179, 92, 0.5); color: #f8e7d3; cursor: pointer; font-weight: 600;">
                <i class="bi bi-box-arrow-in-down me-1"></i> Click to Autofill
            </button>
        </div>
        @else
        <div id="adminDevOtpBanner" style="display: none; background: rgba(216, 179, 92, 0.15); border: 1px solid rgba(216, 179, 92, 0.4); border-radius: 14px; padding: 12px 16px; margin-bottom: 20px; text-align: center;">
            <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.8px; color: #d8b35c; font-weight: 700; margin-bottom: 4px;">
                <i class="bi bi-code-slash me-1"></i> Local Dev 2FA OTP
            </div>
            <div id="adminDevOtpCode" style="font-size: 1.5rem; font-weight: 800; letter-spacing: 5px; color: #fff; font-family: monospace;"></div>
            <button type="button" id="adminDevOtpBtn" style="margin-top: 8px; font-size: 0.78rem; padding: 5px 14px; border-radius: 8px; background: rgba(216, 179, 92, 0.3); border: 1px solid rgba(216, 179, 92, 0.5); color: #f8e7d3; cursor: pointer; font-weight: 600;">
                <i class="bi bi-box-arrow-in-down me-1"></i> Click to Autofill
            </button>
        </div>
        @endif

        @if(session('info'))
        <div class="alert-info"><i class="bi bi-info-circle me-2"></i>{{ session('info') }}</div>
        @endif
        @if($errors->any())
        <div class="alert-err"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}</div>
        @endif
        
        <div id="resendAlert" class="alert-info" style="display: none;"></div>

        <form method="POST" action="{{ route('admin.2fa.verify') }}" id="otpForm">
            @csrf
            <input type="hidden" name="otp" id="otpHidden">

            <div class="otp-inputs">
                @for($i = 1; $i <= 6; $i++)
                <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" id="d{{ $i }}" autocomplete="off">
                @endfor
            </div>

            <button type="submit" class="submit-btn">
                <i class="bi bi-check2-circle me-2"></i>Verify & Login
            </button>
        </form>

        <div class="resend-link">
            Didn't receive it? 
            <form action="{{ route('admin.2fa.resend') }}" method="POST" id="resendForm" style="display: inline;">
                @csrf
                <button type="button" id="resendBtn">Resend Code</button>
            </form>
        </div>
    </div>
</div>

<script>
function autofillAdminOtp(code) {
    if (!code) return;
    const digits = document.querySelectorAll('.otp-digit');
    code.split('').forEach((ch, idx) => {
        if (digits[idx]) digits[idx].value = ch;
    });
    updateHidden();
    if (digits[5]) digits[5].focus();
}
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
    });
});

function updateHidden() {
    document.getElementById('otpHidden').value = Array.from(digits).map(d => d.value).join('');
}

document.getElementById('otpForm').addEventListener('submit', (e) => {
    updateHidden();
    const otp = document.getElementById('otpHidden').value;
    if (otp.length !== 6) { e.preventDefault(); alert('Please enter all 6 digits.'); }
});

digits[0].focus();

// Resend Ajax logic
document.getElementById('resendBtn').addEventListener('click', async function(e) {
    e.preventDefault();
    this.disabled = true;
    this.innerText = 'Sending...';
    
    try {
        const response = await fetch('{{ route('admin.2fa.resend') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        
        const alertBox = document.getElementById('resendAlert');
        alertBox.style.display = 'block';
        alertBox.innerHTML = '<i class="bi bi-info-circle me-2"></i>' + data.message;
        
        if(data.success) {
            const devBanner = document.getElementById('adminDevOtpBanner');
            const devCode = document.getElementById('adminDevOtpCode');
            const devBtn = document.getElementById('adminDevOtpBtn');
            if (data.dev_otp && devBanner && devCode) {
                devCode.textContent = data.dev_otp;
                devBanner.style.display = 'block';
                const autofillAction = function() { autofillAdminOtp(data.dev_otp); };
                if (devBtn) devBtn.onclick = autofillAction;
                const existingBtn = devBanner.querySelector('button');
                if (existingBtn) existingBtn.onclick = autofillAction;
            }

            let countdown = 60;
            const btn = this;
            const timer = setInterval(() => {
                countdown--;
                btn.innerText = `Wait ${countdown}s`;
                if(countdown <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.innerText = 'Resend Code';
                }
            }, 1000);
        } else {
            this.disabled = false;
            this.innerText = 'Resend Code';
        }
    } catch (err) {
        this.disabled = false;
        this.innerText = 'Resend Code';
    }
});
</script>
@endsection
