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
        max-width: 460px; width: 100%; border-radius: 28px;
        padding: 40px 34px;
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
    .step-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 12px; border-radius: 20px;
        background: rgba(216,179,92,0.14);
        border: 1px solid rgba(216,179,92,0.3);
        color: #d8b35c; font-size: 0.75rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.5px;
        margin-bottom: 14px;
    }
    .otp-icon {
        width: 68px; height: 68px; border-radius: 50%;
        background: rgba(216,179,92,0.18);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 16px;
        box-shadow: 0 12px 32px rgba(216,179,92,0.22);
    }
    .otp-icon i { color: #f8e7d3; font-size: 1.8rem; }
    .field-label {
        font-size: .74rem; font-weight: 700;
        color: rgba(248,231,211,0.85);
        text-transform: uppercase; letter-spacing: .5px;
        display: block; margin-bottom: 8px;
    }
    .field-input-group {
        position: relative; margin-bottom: 20px;
    }
    .field-icon {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        color: rgba(248,231,211,0.5); font-size: 1.05rem; pointer-events: none;
    }
    .field-input {
        width: 100%; padding: 13px 14px 13px 42px;
        border-radius: 14px; border: 1.5px solid rgba(255,255,255,0.14);
        font-size: .95rem; font-family: 'Inter', sans-serif;
        background: rgba(255,255,255,0.06); color: #f8e7d3;
        transition: all .2s; outline: none; box-sizing: border-box;
    }
    .field-input:hover { border-color: rgba(255,255,255,0.24); }
    .field-input:focus {
        border-color: rgba(216,179,92,0.7);
        background: rgba(255,255,255,0.12);
        box-shadow: 0 0 0 3px rgba(216,179,92,0.14);
    }
    .submit-btn {
        width: 100%; padding: 14px;
        background: linear-gradient(135deg, #d8b35c, #b8974d);
        color: #2b0507; font-weight: 800; font-size: .95rem;
        border: none; border-radius: 14px; cursor: pointer;
        transition: all .25s; box-shadow: 0 8px 24px rgba(216,179,92,.25);
        margin-top: 8px; letter-spacing: 0.4px;
    }
    .submit-btn:hover {
        background: linear-gradient(135deg, #c9a551, #a7843f);
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(216,179,92,.3);
    }
    .submit-btn:active { transform: translateY(0); }
    .back-link {
        display: block; text-align: center; margin-top: 20px;
        font-size: .875rem; color: rgba(248,231,211,0.65);
    }
    .back-link a {
        color: #d8b35c; font-weight: 700; text-decoration: none;
    }
    .back-link a:hover { text-decoration: underline; }
    .alert-err {
        background: rgba(220,38,38,0.18);
        border: 1px solid rgba(220,38,38,0.38);
        color: #f8c6c6; border-radius: 14px;
        padding: 14px 16px; font-size: .88rem;
        margin-bottom: 20px; text-align: left;
    }
    .alert-err-title {
        font-weight: 800; font-size: 0.95rem; margin-bottom: 4px;
        display: flex; align-items: center; gap: 8px; color: #fca5a5;
    }
    .alert-err-sub {
        font-size: 0.82rem; color: rgba(248,198,198,0.85); margin-top: 4px;
    }
    .alert-info {
        background: rgba(59,130,246,0.12);
        border: 1px solid rgba(59,130,246,0.28);
        color: #bfdbfe; border-radius: 14px;
        padding: 12px 14px; font-size: .88rem;
        margin-bottom: 16px;
    }
    .reset-title {
        font-size: 1.65rem; font-weight: 800; letter-spacing: -0.4px;
        color: #f8e7d3; margin-bottom: 6px;
    }
    .reset-subtitle {
        font-size: .9rem; color: rgba(248,231,211,0.76); line-height: 1.5;
    }
</style>

<div class="otp-wrapper">
    <div class="otp-card">
        <div class="text-center">
            <div class="step-badge">
                <i class="bi bi-shield-lock-fill"></i> Step 1: Verify Account
            </div>
            <div class="otp-icon"><i class="bi bi-person-check-fill"></i></div>
            <h2 class="reset-title">Verify Your Account</h2>
            <p class="reset-subtitle">Enter your Student ID / Account ID and registered Gmail to confirm ownership before resetting your password.</p>
        </div>

        @if(session('info'))
        <div class="alert-info mt-3"><i class="bi bi-info-circle me-2"></i>{{ session('info') }}</div>
        @endif

        @if(session('account_verification_failed') || ($errors->has('email') && str_contains($errors->first('email'), 'not the email registered')))
        <div class="alert-err mt-3">
            <div class="alert-err-title">
                <i class="bi bi-x-circle-fill" style="color:#ef4444; font-size:1.15rem;"></i> Account Verification Failed
            </div>
            <div style="font-weight:600; color:#fee2e2;">
                The email address does not match the email registered to this account.
            </div>
            <div class="alert-err-sub">
                Please enter the email address associated with your account.
            </div>
        </div>
        @elseif($errors->any())
        <div class="alert-err mt-3">
            <div class="alert-err-title">
                <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b; font-size:1.1rem;"></i> Unable to Verify Account
            </div>
            <div>{{ $errors->first() }}</div>
        </div>
        @endif

        <form method="POST" action="{{ route('otp.forgot.send') }}" class="mt-4">
            @csrf

            <!-- Student ID / Account ID -->
            <div class="field-input-group">
                <label class="field-label" for="account_id">Student ID / Account ID</label>
                <div style="position:relative;">
                    <i class="bi bi-person-badge field-icon"></i>
                    <input type="text"
                           name="account_id"
                           id="account_id"
                           class="field-input"
                           placeholder="e.g. 20260001 or Account ID"
                           value="{{ old('account_id', old('identifier')) }}"
                           required
                           autocomplete="username"
                           autofocus>
                </div>
                @if($errors->has('account_id'))
                <p style="color:#f8c6c6;font-size:.82rem;margin-top:6px;">{{ $errors->first('account_id') }}</p>
                @endif
            </div>

            <!-- Registered Gmail / Email Address -->
            <div class="field-input-group">
                <label class="field-label" for="email">Registered Gmail / Email</label>
                <div style="position:relative;">
                    <i class="bi bi-envelope-fill field-icon"></i>
                    <input type="email"
                           name="email"
                           id="email"
                           class="field-input"
                           placeholder="e.g. student@gmail.com"
                           value="{{ old('email') }}"
                           required
                           autocomplete="email">
                </div>
                @if($errors->has('email'))
                <p style="color:#f8c6c6;font-size:.82rem;margin-top:6px;">{{ $errors->first('email') }}</p>
                @endif
            </div>

            <button type="submit" class="submit-btn" id="continue-btn">
                <i class="bi bi-arrow-right-circle-fill me-2"></i>Continue
            </button>
        </form>

        <div class="back-link">
            Remember your password? <a href="{{ route('login') }}">Sign in</a>
        </div>

        <div class="text-center mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,0.06); font-size:0.8rem; color:rgba(248,231,211,0.6);">
            <a href="{{ route('privacy') }}" target="_blank" style="color:rgba(248,231,211,0.6); text-decoration:none;" onmouseover="this.style.color='#d8b35c'" onmouseout="this.style.color='rgba(248,231,211,0.6)'">Privacy Policy</a>
            <span class="mx-2" style="opacity:0.3;">|</span>
            <a href="{{ route('terms') }}" target="_blank" style="color:rgba(248,231,211,0.6); text-decoration:none;" onmouseover="this.style.color='#d8b35c'" onmouseout="this.style.color='rgba(248,231,211,0.6)'">Terms & Conditions</a>
        </div>
    </div>
</div>
@endsection
