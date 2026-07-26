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
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        top: -120px; left: -100px;
        border-radius: 50%; pointer-events: none;
    }
    .otp-card {
        max-width: 420px; width: 100%; border-radius: 28px;
        padding: 42px 36px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.12);
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
    .reset-title {
        font-size: 1.5rem; font-weight: 800;
        color: #f8e7d3; letter-spacing: -0.4px;
        margin-bottom: 4px;
    }
    .reset-subtitle {
        font-size: 0.9rem; color: rgba(248,231,211,0.75);
        margin-top: 4px;
    }
    .field-label {
        font-size: .72rem; font-weight: 700;
        color: rgba(248,231,211,0.75);
        text-transform: uppercase; letter-spacing: .5px;
        display: block; margin-bottom: 8px;
    }
    .pw-wrap { position: relative; margin-bottom: 16px; }
    .field-input {
        width: 100%; padding: 14px 44px 14px 14px;
        border-radius: 14px; border: 1.5px solid rgba(255,255,255,0.14);
        font-size: .95rem; font-family: 'Inter', sans-serif;
        background: rgba(255,255,255,0.06); color: #f8e7d3;
        transition: all .2s; outline: none;
    }
    .field-input:focus {
        border-color: rgba(216,179,92,0.6);
        background: rgba(255,255,255,0.12);
        box-shadow: 0 0 0 3px rgba(216,179,92,0.12);
    }
    .eye-btn {
        position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
        color: rgba(248,231,211,0.65); font-size: 1rem;
        cursor: pointer; background: none; border: none;
        padding: 0; transition: color .2s; line-height: 1;
    }
    .eye-btn:hover { color: #d8b35c; }
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
        box-shadow: 0 12px 28px rgba(216,179,92,.28);
    }
    .submit-btn:active { transform: translateY(0); }
    .alert-err {
        background: rgba(220,38,38,0.18);
        border: 1px solid rgba(220,38,38,0.35);
        color: #f8c6c6; border-radius: 14px;
        padding: 12px 14px; font-size: .88rem;
        margin-bottom: 18px;
    }
</style>

<div class="otp-wrapper">
    <div class="otp-card">
            <div class="otp-icon"><i class="bi bi-key-fill"></i></div>
            <div class="text-center mb-4">
                <h2 class="reset-title">Set New Password</h2>
                <p class="reset-subtitle">OTP verified. Choose a strong new password.</p>
            </div>
        @if($errors->any())
        <div class="alert-err"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('otp.reset') }}">
            @csrf
            <div>
                <label class="field-label">New Password</label>
                <div class="pw-wrap">
                    <input type="password" name="password" id="pw1" class="field-input" placeholder="At least 8 characters" required>
                    <button type="button" class="eye-btn" onclick="togglePw('pw1',this)" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                </div>
            </div>
            <div>
                <label class="field-label">Confirm Password</label>
                <div class="pw-wrap">
                    <input type="password" name="password_confirmation" id="pw2" class="field-input" placeholder="Repeat new password" required>
                    <button type="button" class="eye-btn" onclick="togglePw('pw2',this)" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                </div>
            </div>
            <button type="submit" class="submit-btn">
                <i class="bi bi-check2-circle me-2"></i>Reset Password
            </button>
        </form>
    </div>
</div>

<script>
function togglePw(id, btn) {
    const i = document.getElementById(id);
    const ic = btn.querySelector('i');
    if (i.type === 'password') { i.type = 'text'; ic.className = 'bi bi-eye'; btn.style.color = '#800000'; }
    else { i.type = 'password'; ic.className = 'bi bi-eye-slash'; btn.style.color = ''; }
}
</script>
@endsection
