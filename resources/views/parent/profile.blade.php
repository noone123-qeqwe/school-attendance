@extends('layouts.app')
@section('page-title', 'My Profile')

@section('content')
@php
    $user = Auth::user();
@endphp

<style>
.sp{max-width:1100px;margin:0 auto;}
.pg-title{font-size:1.8rem;font-weight:800;color:#f3e7cd;letter-spacing:-.3px;}
.pg-sub{font-size:.875rem;color:#b39b82;margin-top:2px;}
.stabs-wrapper {
    position: relative;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    width: 100%;
}
.stabs {
    display: flex;
    gap: 0;
    width: 100%;
    border-bottom: 2px solid rgba(255,215,145,0.08);
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    padding-right: 48px;
    padding-left: 2px;
}
.stabs::-webkit-scrollbar {
    display: none;
}
.stab {
    white-space: nowrap;
    padding: 10px 20px;
    font-size: .875rem;
    font-weight: 600;
    color: #8f826f;
    cursor: pointer;
    border: none;
    background: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all .2s;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
}
.stab.active { color: #cfa46f; border-bottom-color: #cfa46f; }
.stab:hover { color: #f3e7cd; }

.stabs-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(30, 24, 20, 0.95);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1.5px solid rgba(207, 164, 111, 0.5);
    color: #cfa46f;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.95rem;
    box-shadow: 0 4px 14px rgba(0,0,0,0.6), 0 0 10px rgba(207,164,111,0.3);
    transition: all 0.25s ease;
    padding: 0;
}
.stabs-arrow:hover {
    background: rgba(45, 36, 30, 0.98);
    border-color: #cfa46f;
    color: #f3e7cd;
    box-shadow: 0 6px 16px rgba(0,0,0,0.7), 0 0 14px rgba(207,164,111,0.45);
    transform: translateY(-50%) scale(1.08);
}
.stabs-arrow-left {
    left: 0;
    display: none;
}
.stabs-arrow-left.visible {
    display: flex;
}
.stabs-arrow-right {
    right: 0;
    display: flex;
    animation: stabsArrowPulse 2.5s infinite ease-in-out;
}
@keyframes stabsArrowPulse {
    0%, 100% {
        transform: translateY(-50%) scale(1);
        box-shadow: 0 4px 12px rgba(0,0,0,0.6), 0 0 8px rgba(207,164,111,0.25);
    }
    50% {
        transform: translateY(-50%) scale(1.12);
        box-shadow: 0 4px 16px rgba(0,0,0,0.7), 0 0 16px rgba(207,164,111,0.5);
    }
}
.stabs-wrapper.has-scroll-right::after {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 52px;
    background: linear-gradient(to right, transparent, rgba(17, 14, 12, 0.9));
    pointer-events: none;
    z-index: 5;
}
.stabs-wrapper.has-scroll-left::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 52px;
    background: linear-gradient(to left, transparent, rgba(17, 14, 12, 0.9));
    pointer-events: none;
    z-index: 5;
}
.spanel{display:none;}.spanel.active{display:block;}
.sc{background:rgba(255,235,190,0.02);border-radius:16px;border:1px solid rgba(255,215,145,0.08);box-shadow:0 4px 15px rgba(0,0,0,.2);overflow:hidden;margin-bottom:20px;transition:all .25s;}
.sc:hover{box-shadow:0 8px 25px rgba(0,0,0,.3);border-color:rgba(255,215,145,0.15);}
.sc-head{padding:28px 40px;border-bottom:1px solid rgba(255,215,145,0.06);display:flex;align-items:center;gap:20px;}
.sc-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;background:rgba(207,164,111,0.12)!important;color:#cfa46f!important;}
.sc-title{font-size:1.15rem;font-weight:700;color:#f3e7cd;}
.sc-sub{font-size:.9rem;color:#b39b82;margin-top:4px;}
.sc-body{padding:40px;}
.sl{font-size:.75rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;display:block;}
.si{width:100%;padding:11px 14px;border-radius:10px;border:1.5px solid rgba(255,215,145,0.12);font-size:.875rem;font-family:'Inter',sans-serif;background:rgba(255,235,190,0.05);color:#f3e7cd;transition:all .2s;outline:none;}
.si:hover{border-color:rgba(255,215,145,0.25);background:rgba(255,235,190,0.08);}
.si:focus{border-color:#cfa46f;background:rgba(255,235,190,0.08);box-shadow:0 0 0 3px rgba(207,164,111,.15);}
.si option {background:#1a1d24;color:#f3e7cd;}
.pw-wrap{position:relative;}
.pw-wrap .si{padding-right:44px;}
.eye-btn{position:absolute;right:13px;top:50%;transform:translateY(-50%);color:#b39b82;font-size:1rem;cursor:pointer;background:none;border:none;padding:0;transition:color .2s;line-height:1;}
.eye-btn:hover{color:#cfa46f;}
.sbtn{padding:11px 28px;background:rgba(117,69,53,0.9)!important;color:#f3e7cd;font-weight:700;font-size:.875rem;border:1px solid rgba(255,215,145,0.16);border-radius:10px;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.25)!important;transition:all .25s;}
.sbtn:hover{background:rgba(135,87,58,0.95)!important;transform:translateY(-2px);box-shadow:0 8px 22px rgba(0,0,0,.35)!important;}
.sbtn:active{transform:translateY(0);}
.cancel-btn {
    padding:11px 20px; background:rgba(255,235,190,0.05)!important; color:#b39b82!important; border:1px solid rgba(255,215,145,0.15)!important; border-radius:10px; font-weight:600; font-size:.875rem; cursor:pointer; transition:all 0.2s;
}
.cancel-btn:hover { background:rgba(255,235,190,0.1)!important; color:#f3e7cd!important; }
.trow{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid rgba(255,215,145,0.06);}
.trow:last-child{border-bottom:none;padding-bottom:0;}
.tlabel{font-size:.875rem;font-weight:600;color:#f3e7cd;}
.tsub{font-size:.78rem;color:#b39b82;margin-top:2px;}
.form-check-input{background-color:rgba(255,235,190,0.1);border-color:rgba(255,215,145,0.2);}
.form-check-input:checked{background-color:#cfa46f!important;border-color:#cfa46f!important;}
.form-check-input{width:2.4em!important;height:1.3em!important;cursor:pointer;}
.flash-ok{background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.2);color:#4ade80;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.flash-err{background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.2);color:#f87171;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;}

.email-otp-digit, .otp-digit-s {
    color: #f3e7cd !important;
    background: rgba(255,235,190,0.05) !important;
    border-color: rgba(255,215,145,0.12) !important;
}
.email-otp-digit:focus, .otp-digit-s:focus {
    border-color: #cfa46f !important;
    box-shadow: 0 0 0 3px rgba(207,164,111,.15) !important;
}

@media (max-width: 768px) {
    .sp { padding-left: 15px !important; padding-right: 15px !important; }
    .pg-title { font-size: 1.2rem; }
    .pg-sub { font-size: 0.8rem; }
    .stabs-wrapper { margin-bottom: 20px; }
    .stabs { display: flex; flex-wrap: nowrap; overflow-x: auto; gap: 8px; padding: 4px 44px 4px 4px; margin-bottom: 0; }
    .stabs::-webkit-scrollbar { display: none; }
    .stab { white-space: nowrap; padding: 8px 16px; font-size: 0.85rem; }
    .sc-head { padding: 16px 20px; }
    .sc-icon { width: 32px; height: 32px; font-size: 0.9rem; }
    .sc-title { font-size: 0.9rem; }
    .sc-sub { font-size: 0.75rem; }
    .sc-body { padding: 20px; }
    .sl { font-size: 0.7rem; }
    .si { font-size: 0.85rem; padding: 10px 12px; }
    .sbtn { padding: 10px 20px; font-size: 0.85rem; }
    .trow { flex-direction: column; align-items: flex-start; gap: 4px; padding: 12px 0; }
    .tlabel { font-size: 0.85rem; }
    .tsub { font-size: 0.75rem; }
}
</style>

<div class="sp">

    <div style="margin-bottom:24px;">
        <div class="pg-title">Settings</div>
        <div class="pg-sub">Manage your account, security, and preferences</div>
    </div>

    @if(session('success'))
    <div class="flash-ok"><i class="bi bi-check-circle-fill fs-5"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
    <div class="flash-err"><i class="bi bi-exclamation-circle-fill fs-5"></i><span>{{ $errors->first() }}</span></div>
    @endif

    <!-- TABS -->
    <div class="stabs-wrapper">
        <button type="button" class="stabs-arrow stabs-arrow-left" id="stabsArrowLeft" onclick="scrollStabs('left')" aria-label="Scroll left">
            <i class="bi bi-chevron-left"></i>
        </button>
        <div class="stabs" id="stabsNav">
            <button class="stab active" onclick="switchTab('profile',this)"><i class="bi bi-person-circle me-1"></i> Profile</button>
            <button class="stab" onclick="switchTab('security',this)"><i class="bi bi-shield-lock-fill me-1"></i> Security</button>
            <button class="stab" onclick="switchTab('fingerprint',this)"><i class="bi bi-fingerprint me-1"></i> Fingerprint</button>
            <button class="stab" onclick="switchTab('preferences',this)"><i class="bi bi-sliders me-1"></i> Preferences</button>
        </div>
        <button type="button" class="stabs-arrow stabs-arrow-right" id="stabsArrowRight" onclick="scrollStabs('right')" aria-label="Scroll right">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>

    <!-- ── TAB: PROFILE ── -->
    <div id="tab-profile" class="spanel active">
        <form action="{{ route('parent.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="sc">
                <div class="sc-head">
                    <div class="sc-icon" style="background:#fff5f5;color:#800000;"><i class="bi bi-person-circle"></i></div>
                    <div><div class="sc-title">Personal Information</div><div class="sc-sub">Update your basic profile details</div></div>
                </div>
                <div class="sc-body">
                    <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;">
                        <input type="file" name="profile_image" id="imgInput" class="d-none" accept="image/*" onchange="updateProfilePreview(this)">
                        <div onclick="document.getElementById('imgInput').click()"
                             style="width:80px;height:80px;border-radius:50%;overflow:hidden;border:3px solid #fef3c7;box-shadow:0 4px 16px rgba(128,0,0,.12);cursor:pointer;position:relative;flex-shrink:0;transition:transform .3s;"
                             onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform=''">
                            @if($user->profile_image)
                                <img id="profilePreview" src="{{ str_starts_with($user->profile_image, 'http') ? $user->profile_image : asset('storage/'.$user->profile_image) }}" style="width:100%;height:100%;object-fit:cover;"
                                     onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=800000&color=fff&size=200'">
                            @else
                                <img id="profilePreview" src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=800000&color=fff&size=200" style="width:100%;height:100%;object-fit:cover;">
                            @endif
                            <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;border-radius:50%;"
                                 onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0">
                                <i class="bi bi-camera-fill" style="color:white;font-size:1.2rem;"></i>
                            </div>
                        </div>
                        <div>
                            <div style="font-size:1rem;font-weight:700;color:#f3e7cd;">Profile Photo</div>
                            <div style="font-size:.8rem;color:#b39b82;margin-top:2px;">Click the photo to change it</div>
                        </div>
                    </div>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="sl">Full Name</label>
                            <input type="text" name="name" class="si" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="sl">Phone Number</label>
                            <input type="text" name="phone" class="si" value="{{ old('phone', $user->phone) }}" placeholder="+63 900 000 0000">
                        </div>
                    </div>
                </div>
            </div>

            <div style="text-align: right;">
                <button type="submit" class="sbtn"><i class="bi bi-save me-2"></i>Save Profile</button>
            </div>
        </form>
    </div>

    <!-- ── TAB: SECURITY ── -->
    <div id="tab-security" class="spanel">
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:#fff5f5;color:#800000;"><i class="bi bi-shield-lock-fill"></i></div>
                <div><div class="sc-title">Security</div><div class="sc-sub">Change your email address or password</div></div>
            </div>
            <div class="sc-body">

                <!-- ── Change Email via OTP to current email ── -->
                <div style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="font-size:.78rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Email Address</div>

                    <div id="emailStep1">
                        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:rgba(255,235,190,0.05);border-radius:10px;border:1px solid rgba(255,215,145,0.12);margin-bottom:12px;">
                            <i class="bi bi-envelope-fill" style="color:#cfa46f;"></i>
                            <span style="font-size:.875rem;font-weight:600;color:#f3e7cd;">{{ Auth::user()->email }}</span>
                        </div>
                        <p style="font-size:.85rem;color:#b39b82;margin-bottom:12px;">
                            An OTP will be sent to your <strong>current email</strong> to verify it's you before changing.
                        </p>
                        <button type="button" onclick="requestEmailOtp()" id="sendEmailOtpBtn" class="sbtn" style="background:linear-gradient(135deg,#2563eb,#3b82f6);box-shadow:0 4px 14px rgba(37,99,235,.25);">
                            <i class="bi bi-envelope-fill me-2"></i>Send OTP to Current Email
                        </button>
                    </div>

                    <div id="emailStep2" style="display:none;">
                        <div style="background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.2);color:#4ade80;border-radius:10px;padding:10px 14px;font-size:.82rem;margin-bottom:14px;">
                            <i class="bi bi-envelope-check me-2"></i>OTP sent to <strong>{{ Auth::user()->email }}</strong>. Check your inbox.
                        </div>
                        <form action="{{ route('otp.email.change') }}" method="POST">
                            @csrf
                            <label class="sl">Enter OTP</label>
                            <div style="display:flex;gap:8px;margin-bottom:14px;">
                                @for($j=1;$j<=6;$j++)
                                <input type="text" class="email-otp-digit" maxlength="1" inputmode="numeric" id="ed{{$j}}" style="width:44px;height:50px;border-radius:10px;border:1.5px solid #e2e8f0;font-size:1.3rem;font-weight:800;text-align:center;outline:none;transition:all .2s;">
                                @endfor
                            </div>
                            <input type="hidden" name="otp" id="emailOtpHidden">
                            <label class="sl">New Email Address</label>
                            <input type="email" name="new_email" class="si" placeholder="newemail@example.com" style="margin-bottom:14px;" required>
                            <div style="display:flex;gap:10px;">
                                <button type="button" onclick="cancelEmailOtp()" class="cancel-btn">Cancel</button>
                                <button type="button" class="sbtn" style="flex:1;" onclick="collectEmailOtp(this)"><i class="bi bi-check2 me-2"></i>Change Email</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ── Change Password via OTP ── -->
                <div style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="font-size:.78rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Password</div>

                    <div id="otpStep1">
                        <p style="font-size:.85rem;color:#b39b82;margin-bottom:12px;">
                            An OTP will be sent to <strong>{{ Auth::user()->email }}</strong> before you can change your password.
                        </p>
                        <button type="button" onclick="requestOtp()" id="sendOtpBtn" class="sbtn" style="background:linear-gradient(135deg,#1e293b,#334155);box-shadow:0 4px 14px rgba(0,0,0,.25);">
                            <i class="bi bi-send-fill me-2"></i>Send OTP to Email
                        </button>
                    </div>

                    <div id="otpStep2" style="display:none;">
                        <div style="background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.2);color:#4ade80;border-radius:10px;padding:10px 14px;font-size:.82rem;margin-bottom:16px;">
                            <i class="bi bi-check-circle me-2"></i>OTP sent to {{ Auth::user()->email }}. Check your inbox.
                        </div>
                        <form action="{{ route('otp.change') }}" method="POST">
                            @csrf
                            <label class="sl">Enter OTP</label>
                            <div style="display:flex;gap:8px;margin-bottom:16px;">
                                @for($i=1;$i<=6;$i++)
                                <input type="text" class="otp-digit-s" maxlength="1" inputmode="numeric" id="sd{{$i}}" style="width:44px;height:50px;border-radius:10px;border:1.5px solid #e2e8f0;font-size:1.3rem;font-weight:800;text-align:center;outline:none;transition:all .2s;">
                                @endfor
                            </div>
                            <input type="hidden" name="otp" id="settingsOtpHidden">
                            <label class="sl">New Password</label>
                            <div class="pw-wrap" style="margin-bottom:12px;">
                                <input type="password" name="password" id="spw1" class="si" placeholder="At least 8 characters" required>
                                <button type="button" class="eye-btn" onclick="togglePw('spw1',this)" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                            </div>
                            <label class="sl">Confirm Password</label>
                            <div class="pw-wrap" style="margin-bottom:16px;">
                                <input type="password" name="password_confirmation" id="spw2" class="si" placeholder="Repeat new password" required>
                                <button type="button" class="eye-btn" onclick="togglePw('spw2',this)" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                            </div>
                            <div style="display:flex;gap:10px;">
                                <button type="button" onclick="cancelOtp()" class="cancel-btn">Cancel</button>
                                <button type="button" class="sbtn" style="flex:1;" onclick="collectOtp(this)"><i class="bi bi-check2 me-2"></i>Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ── Biometric & Fingerprint Authentication ── -->
                <div style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="font-size:.78rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Biometric Authentication</div>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:rgba(255,235,190,0.03);padding:16px 20px;border-radius:12px;border:1px solid rgba(255,215,145,0.08);">
                        <div style="display:flex;align-items:center;gap:14px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:rgba(34,197,94,0.12);color:#4ade80;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
                                <i class="bi bi-fingerprint"></i>
                            </div>
                            <div>
                                <div style="font-size:.9rem;font-weight:700;color:#f3e7cd;">Fingerprint / Biometric Login</div>
                                <div style="font-size:.78rem;color:#b39b82;">Use device biometrics for instant passwordless sign-in</div>
                            </div>
                        </div>
                        <button type="button" onclick="switchTab('fingerprint', document.querySelectorAll('.stab')[2])" class="sbtn" style="padding:9px 18px;font-size:.82rem;background:linear-gradient(135deg,#16a34a,#22c55e);box-shadow:0 4px 14px rgba(22,163,74,.25);">
                            <i class="bi bi-fingerprint me-1"></i> Manage Fingerprints
                        </button>
                    </div>
                </div>

                <!-- ── Recovery Codes ── -->
                <div>
                    <div style="font-size:.78rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Recovery Codes</div>
                    <p style="font-size:.85rem;color:#b39b82;margin-bottom:12px;">
                        Recovery codes can be used to log in if you lose access to your email or fingerprint. Generate new codes to invalidate old ones.
                    </p>
                    <button type="button" onclick="generateRecoveryCodes()" id="generateCodesBtn" class="sbtn" style="background:linear-gradient(135deg,#eab308,#ca8a04);box-shadow:0 4px 14px rgba(234,179,8,.25);">
                        <i class="bi bi-key-fill me-2"></i>Generate Recovery Codes
                    </button>
                    
                    <div id="recoveryCodesList" style="display:none;margin-top:16px;background:rgba(255,235,190,0.05);border:1px solid rgba(255,215,145,0.12);border-radius:10px;padding:16px;">
                        <div style="font-size:.875rem;font-weight:600;color:#f87171;margin-bottom:12px;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Save these codes in a safe place. They will not be shown again.
                        </div>
                        <div id="codesContainer" style="display:flex;flex-wrap:wrap;gap:10px;">
                            <!-- Codes will be injected here -->
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ── TAB: FINGERPRINT ── -->
    <div id="tab-fingerprint" class="spanel">
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:rgba(34,197,94,0.12);color:#4ade80;"><i class="bi bi-fingerprint"></i></div>
                <div><div class="sc-title">Fingerprint / Biometric Login</div><div class="sc-sub">Register your device fingerprint to log in without a password</div></div>
            </div>
            <div class="sc-body">

                <div id="webauthnUnsupported" style="display:none;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:#f87171;border-radius:14px;padding:16px 20px;font-size:.85rem;margin-bottom:16px;">
                    <div style="display:flex;align-items:flex-start;gap:12px;">
                        <i class="bi bi-exclamation-triangle" style="font-size:1.2rem;flex-shrink:0;margin-top:2px;"></i>
                        <div>
                            <div style="font-weight:700;margin-bottom:4px;">Biometric login not available</div>
                            <div id="webauthnUnsupportedMsg" style="font-size:.8rem;opacity:.85;line-height:1.5;">
                                You're using an in-app browser that doesn't support fingerprint/biometric login. Please open this page in <strong>Chrome</strong> or <strong>Safari</strong> to register your fingerprint.
                            </div>
                            <a id="openInBrowserBtn" href="#" onclick="openInSystemBrowser()" style="display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:8px 16px;background:rgba(248,113,113,0.15);border:1px solid rgba(248,113,113,0.3);border-radius:8px;color:#fca5a5;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .2s;">
                                <i class="bi bi-box-arrow-up-right"></i> Open in Browser
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Registered devices -->
                <div style="margin-bottom:20px;">
                    <div style="font-size:.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Registered Devices</div>
                    <div id="deviceList">
                        <div style="text-align:center;padding:24px 20px;color:#b39b82;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:12px;border:1px dashed rgba(207,164,111,0.2);" id="noDevices">
                            <i class="bi bi-fingerprint" style="font-size:2.2rem;display:block;margin-bottom:8px;opacity:.35;color:var(--gold,#CFA46F);"></i>
                            <div style="font-weight:600;color:#f3e7cd;margin-bottom:4px;">No fingerprint registered yet</div>
                            <div style="font-size:.78rem;color:#b39b82;">Register this device to enable fast fingerprint sign-in.</div>
                        </div>
                    </div>
                </div>

                <!-- Register button -->
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <button type="button" onclick="registerFingerprint()" id="registerFpBtn" class="sbtn" style="background:linear-gradient(135deg,#16a34a,#22c55e);box-shadow:0 4px 14px rgba(22,163,74,.25);">
                        <i class="bi bi-fingerprint me-2"></i>Register This Device
                    </button>
                    <span style="font-size:.78rem;color:#b39b82;"><i class="bi bi-shield-check me-1 text-success"></i>FIDO2 / WebAuthn standard security</span>
                </div>
                <div id="fpMessage" style="margin-top:12px;font-size:.82rem;display:none;"></div>
            </div>
        </div>
    </div>

    <!-- ── TAB: PREFERENCES ── -->
    <div id="tab-preferences" class="spanel">
        <form action="{{ route('parent.profile.update') }}" method="POST">
            @csrf
            <div class="sc">
                <div class="sc-head">
                    <div class="sc-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-sliders"></i></div>
                    <div><div class="sc-title">Preferences</div><div class="sc-sub">Customize your portal experience</div></div>
                </div>
                <div class="sc-body">
                    
                    @php
                        $prefs = $user->notification_preferences ?? [];
                        $emailNotifs = $prefs['email_notifications'] ?? true;
                        $pushNotifs = $prefs['push_notifications'] ?? true;
                    @endphp

                    <div style="margin:16px 0 10px;font-size:.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;">Notifications</div>

                    <div class="trow">
                        <div><div class="tlabel">Email Notifications</div><div class="tsub">Receive important alerts via email</div></div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="email_notifications" {{ $emailNotifs ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="trow">
                        <div><div class="tlabel">Push Notifications</div><div class="tsub">Receive alerts within the portal</div></div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="push_notifications" {{ $pushNotifs ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <div style="text-align: right;">
                <button type="submit" class="sbtn"><i class="bi bi-save me-2"></i>Save Preferences</button>
            </div>
        </form>
    </div>

</div>

<script>
function updateProfilePreview(input) {
    if (input.files && input.files[0]) {
        if (input.files[0].size > 10 * 1024 * 1024) {
            alert('The selected image is too large (' + (input.files[0].size / (1024 * 1024)).toFixed(1) + 'MB). Please choose an image under 10MB.');
            input.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function updateStabsScrollArrows() {
    const nav = document.getElementById('stabsNav');
    const leftBtn = document.getElementById('stabsArrowLeft');
    const rightBtn = document.getElementById('stabsArrowRight');
    const wrapper = nav?.closest('.stabs-wrapper');
    if (!nav || !leftBtn || !rightBtn) return;

    const maxScrollLeft = nav.scrollWidth - nav.clientWidth;

    if (maxScrollLeft <= 8) {
        leftBtn.classList.remove('visible');
        rightBtn.style.display = 'none';
        wrapper?.classList.remove('has-scroll-left', 'has-scroll-right');
        return;
    }

    rightBtn.style.display = '';

    // Toggle left button visibility when scrolled right
    if (nav.scrollLeft > 10) {
        leftBtn.classList.add('visible');
        wrapper?.classList.add('has-scroll-left');
    } else {
        leftBtn.classList.remove('visible');
        wrapper?.classList.remove('has-scroll-left');
    }

    // Always keep right arrow clearly visible on mobile & desktop
    if (nav.scrollLeft >= maxScrollLeft - 8) {
        rightBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i>';
        rightBtn.setAttribute('title', 'Scroll to start');
        wrapper?.classList.remove('has-scroll-right');
    } else {
        rightBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
        rightBtn.setAttribute('title', 'Scroll tabs');
        wrapper?.classList.add('has-scroll-right');
    }
}

function scrollStabs(direction) {
    const nav = document.getElementById('stabsNav');
    if (!nav) return;
    const maxScrollLeft = nav.scrollWidth - nav.clientWidth;

    if (direction === 'right' && maxScrollLeft > 10 && nav.scrollLeft >= maxScrollLeft - 5) {
        nav.scrollTo({ left: 0, behavior: 'smooth' });
    } else {
        const distance = 180;
        nav.scrollBy({
            left: direction === 'right' ? distance : -distance,
            behavior: 'smooth'
        });
    }
    setTimeout(updateStabsScrollArrows, 250);
}

function switchTab(id, btn) {
    document.querySelectorAll('.spanel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.stab').forEach(b => b.classList.remove('active'));
    const targetPanel = document.getElementById('tab-' + id);
    if (targetPanel) targetPanel.classList.add('active');
    if (btn) {
        btn.classList.add('active');
        btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }
    if (id === 'fingerprint') {
        loadDevices();
        prefetchWebAuthn();
    }
    setTimeout(updateStabsScrollArrows, 300);
}

function togglePw(id, btn) {
    const i = document.getElementById(id);
    const ic = btn.querySelector('i');
    if (i.type === 'password') { i.type = 'text'; ic.className = 'bi bi-eye'; btn.style.color = '#cfa46f'; }
    else { i.type = 'password'; ic.className = 'bi bi-eye-slash'; btn.style.color = ''; }
}

@if($errors->any()) switchTab('security', document.querySelectorAll('.stab')[1]); @endif

// ── In-app browser detection ──
function isInAppBrowser() {
    var ua = navigator.userAgent || '';
    return /FBAN|FBAV|FB_IAB|FBIOS|Instagram|Line\/|Twitter|Snapchat|MicroMessenger|KAKAOTALK/i.test(ua);
}

function openInSystemBrowser() {
    var url = window.location.href;
    if (/android/i.test(navigator.userAgent)) {
        window.location.href = 'intent://' + url.replace(/^https?:\/\//, '') + '#Intent;scheme=https;package=com.android.chrome;end';
        setTimeout(function() { window.open(url, '_system'); }, 500);
    } else {
        window.open(url, '_blank');
    }
}

// ── WebAuthn Fingerprint Registration ──
async function loadDevices() {
    var inApp = isInAppBrowser();
    const list = document.getElementById('deviceList');
    const regBtn = document.getElementById('registerFpBtn');
    const unsupported = document.getElementById('webauthnUnsupported');

    if (!window.PublicKeyCredential) {
        if (regBtn) regBtn.style.display = 'none';
        if (unsupported) {
            unsupported.style.display = 'block';
            var msgEl = document.getElementById('webauthnUnsupportedMsg');
            var openBtn = document.getElementById('openInBrowserBtn');
            if (inApp) {
                msgEl.innerHTML = 'You\'re using an in-app browser that doesn\'t support fingerprint login. Tap the button below to open this page in <strong>Chrome</strong> or <strong>Safari</strong>.';
                if (openBtn) openBtn.style.display = 'inline-flex';
            } else {
                msgEl.innerHTML = 'Your browser or device doesn\'t support biometric login. Please try using <strong>Chrome</strong> or <strong>Safari</strong> on a device with a fingerprint sensor.';
                if (openBtn) openBtn.style.display = 'none';
            }
        }
        return;
    }
    try {
        const res = await fetch('{{ route("webauthn.devices") }}');
        const devices = await res.json();
        if (!list) return;

        list.innerHTML = '';

        if (devices && devices.length > 0) {
            const registeredMsg = document.createElement('div');
            registeredMsg.style.cssText = 'padding:14px 18px;color:#4ade80;font-size:.875rem;background:rgba(22,163,74,0.12);border-radius:12px;border:1px solid rgba(22,163,74,0.25);margin-bottom:16px;font-weight:600;display:flex;align-items:center;gap:10px;';
            registeredMsg.innerHTML = '<i class="bi bi-check-circle-fill" style="font-size:1.2rem;color:#22c55e;"></i> <span>Biometric authentication is <strong>active</strong> on your account.</span>';
            list.appendChild(registeredMsg);

            devices.forEach(d => {
                const div = document.createElement('div');
                div.style.cssText = 'display:flex;align-items:center;gap:14px;padding:12px 16px;border-radius:12px;background:rgba(255,235,190,0.03);border:1px solid rgba(255,215,145,0.08);margin-bottom:10px;';
                div.innerHTML = `
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(74,222,128,0.15);border:1px solid rgba(74,222,128,0.3);display:flex;align-items:center;justify-content:center;color:#4ade80;flex-shrink:0;">
                        <i class="bi bi-fingerprint"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.875rem;font-weight:600;color:#f3e7cd;">${d.name || d.device_name || "My Device"}</div>
                        <div style="font-size:.72rem;color:#b39b82;">Registered ${new Date(d.created_at).toLocaleDateString()}</div>
                    </div>
                    <button onclick="removeDevice('${d.credential_id}', this)"
                        style="padding:5px 12px;border-radius:7px;background:rgba(248,113,113,0.1);color:#f87171;border:1px solid rgba(248,113,113,0.2);font-size:.75rem;font-weight:600;cursor:pointer;">
                        Remove
                    </button>`;
                list.appendChild(div);
            });
        } else {
            const emptyDiv = document.createElement('div');
            emptyDiv.id = 'noDevices';
            emptyDiv.style.cssText = 'text-align:center;padding:24px 20px;color:#b39b82;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:12px;border:1px dashed rgba(207,164,111,0.2);';
            emptyDiv.innerHTML = `
                <i class="bi bi-fingerprint" style="font-size:2.2rem;display:block;margin-bottom:8px;opacity:.35;color:var(--gold,#CFA46F);"></i>
                <div style="font-weight:600;color:#f3e7cd;margin-bottom:4px;">No fingerprint registered yet</div>
                <div style="font-size:.78rem;color:#b39b82;">Register this device to enable fast fingerprint sign-in.</div>
            `;
            list.appendChild(emptyDiv);
        }
    } catch(e) {}
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

let prefetchOptions = null;
let isFetchingOptions = false;

async function prefetchWebAuthn() {
    if (isFetchingOptions || prefetchOptions) return;
    isFetchingOptions = true;
    try {
        const optRes = await fetch('{{ route("webauthn.register.options") }}', {
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            }
        });
        prefetchOptions = await optRes.json();
    } catch(e) {}
    isFetchingOptions = false;
}

async function registerFingerprint() {
    const btn = document.getElementById('registerFpBtn');
    const msg = document.getElementById('fpMessage');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Waiting for biometric prompt...';
    msg.style.display = 'none';

    try {
        const optRes = await fetch('{{ route("webauthn.register.options") }}', {
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            }
        });
        const opts = await optRes.json();

        const challenge = base64ToUint8Array(opts.challenge);
        const userId    = base64ToUint8Array(opts.user.id);
        const hostname = window.location.hostname;
        const isIp = /^(\d{1,3}\.){3}\d{1,3}$/.test(hostname) || hostname.includes(':');
        const rp = { name: opts.rp?.name || 'School Attendance' };
        if (opts.rp?.id && !isIp) {
            rp.id = opts.rp.id;
        }

        const excludeCredentials = (opts.excludeCredentials || []).map(function(c) {
            return { type: c.type || 'public-key', id: base64ToUint8Array(c.id) };
        });

        const timeoutPromise = new Promise((_, reject) => {
            const err = new Error('Biometric prompt timed out. Please try again.');
            err.name = 'TimeoutError';
            setTimeout(() => reject(err), 60000);
        });

        const createPromise = navigator.credentials.create({
            publicKey: {
                challenge: challenge,
                rp: rp,
                user: { id: userId, name: opts.user.name, displayName: opts.user.displayName },
                pubKeyCredParams: opts.pubKeyCredParams || [
                    { type: 'public-key', alg: -7 },
                    { type: 'public-key', alg: -257 }
                ],
                authenticatorSelection: opts.authenticatorSelection || {
                    authenticatorAttachment: 'platform',
                    userVerification: 'preferred',
                    requireResidentKey: false
                },
                timeout: opts.timeout || 60000,
                attestation: opts.attestation || 'none',
                excludeCredentials: excludeCredentials
            }
        });

        const credential = await Promise.race([createPromise, timeoutPromise]);

        var credentialId = bufferToBase64Url(credential.rawId);
        var attestationObject = bufferToBase64Url(credential.response.attestationObject);
        var clientDataJSON = bufferToBase64Url(credential.response.clientDataJSON);

        var ua = navigator.userAgent;
        var deviceName = ua.indexOf('iPhone') !== -1 ? 'iPhone' :
                         ua.indexOf('iPad') !== -1 ? 'iPad' :
                         ua.indexOf('Android') !== -1 ? 'Android Device' :
                         ua.indexOf('Windows') !== -1 ? 'Windows Device' : 'My Device';

        const saveRes = await fetch('{{ route("webauthn.register") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            },
            body: JSON.stringify({
                credential_id: credentialId,
                credential: {
                    id: credential.id,
                    type: credential.type,
                    response: { attestationObject: attestationObject, clientDataJSON: clientDataJSON }
                },
                device_name: deviceName
            })
        });
        const result = await saveRes.json();

        msg.style.display = 'block';
        if (result.success) {
            msg.style.cssText = 'margin-top:12px;font-size:.82rem;display:block;background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.2);color:#4ade80;padding:10px 14px;border-radius:10px;';
            msg.innerHTML = '<i class="bi bi-check-circle me-2"></i>' + result.message;
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            msg.style.cssText = 'margin-top:12px;font-size:.82rem;display:block;background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.2);color:#f87171;padding:10px 14px;border-radius:10px;';
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>' + result.message;
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-fingerprint me-2"></i>Register This Device';
            prefetchWebAuthn();
        }
    } catch(err) {
        msg.style.display = 'block';
        msg.style.cssText = 'margin-top:12px;font-size:.82rem;display:block;background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.2);color:#f87171;padding:10px 14px;border-radius:10px;';
        if (err.name === 'NotAllowedError') msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Fingerprint cancelled or not allowed.';
        else if (err.name === 'InvalidStateError') msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>This device is already registered.';
        else if (err.name === 'NotReadableError') msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Fingerprint cancelled or device is not configured for biometric login.';
        else msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>' + err.name + ': ' + err.message;
        
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-fingerprint me-2"></i>Register This Device';
        prefetchWebAuthn();
    }
}

async function removeDevice(credentialId, btn) {
    if (!confirm('Remove this fingerprint device?')) return;
    await fetch('{{ route("webauthn.remove") }}', {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
        body: JSON.stringify({ credential_id: credentialId })
    });
    location.reload();
}

document.querySelectorAll('.stab').forEach(btn => {
    btn.addEventListener('click', () => {
        if (btn.textContent.trim() === 'Fingerprint') {
            loadDevices();
            prefetchWebAuthn();
        }
    });
});

// OTP Input Handling
const sDigits = document.querySelectorAll('.otp-digit-s');
sDigits.forEach((input, idx) => {
    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
        if (e.target.value && idx < sDigits.length - 1) sDigits[idx + 1].focus();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) sDigits[idx - 1].focus();
    });
    input.addEventListener('paste', (e) => {
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        paste.split('').forEach((ch, i) => { if (sDigits[i]) sDigits[i].value = ch; });
        e.preventDefault();
    });
});

function collectOtp(btn) {
    document.getElementById('settingsOtpHidden').value = Array.from(sDigits).map(d => d.value).join('');
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        btn.closest('form').submit();
    }
}

function requestOtp() {
    const btn = document.getElementById('sendOtpBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Sending...';
    fetch('{{ route("otp.change.send") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            document.getElementById('otpStep1').style.display = 'none';
            document.getElementById('otpStep2').style.display = 'block';
            if (data.dev_otp && sDigits) {
                const str = String(data.dev_otp).trim();
                sDigits.forEach((d, i) => { if (str[i]) d.value = str[i]; });
                document.getElementById('otpHidden').value = str;
            }
            if (sDigits[0]) sDigits[0].focus();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Send OTP to Email';
            alert(data.message || 'Failed to send OTP.');
        }
    }).catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Send OTP to Email';
        alert('Network error. Please try again.');
    });
}

function cancelOtp() {
    document.getElementById('otpStep1').style.display = 'block';
    document.getElementById('otpStep2').style.display = 'none';
    sDigits.forEach(d => d.value = '');
}

const eDigits = document.querySelectorAll('.email-otp-digit');
eDigits.forEach((input, idx) => {
    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
        if (e.target.value && idx < eDigits.length - 1) eDigits[idx + 1].focus();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) eDigits[idx - 1].focus();
    });
    input.addEventListener('paste', (e) => {
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        paste.split('').forEach((ch, i) => { if (eDigits[i]) eDigits[i].value = ch; });
        e.preventDefault();
    });
});

function collectEmailOtp(btn) {
    document.getElementById('emailOtpHidden').value = Array.from(eDigits).map(d => d.value).join('');
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        btn.closest('form').submit();
    }
}

function requestEmailOtp() {
    const btn = document.getElementById('sendEmailOtpBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Sending...';
    fetch('{{ route("otp.email.send") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            document.getElementById('emailStep1').style.display = 'none';
            document.getElementById('emailStep2').style.display = 'block';
            if (data.dev_otp && eDigits) {
                const str = String(data.dev_otp).trim();
                eDigits.forEach((d, i) => { if (str[i]) d.value = str[i]; });
                document.getElementById('emailOtpHidden').value = str;
            }
            if (eDigits[0]) eDigits[0].focus();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-envelope-fill me-2"></i>Send OTP to Current Email';
            alert(data.message || 'Failed to send OTP.');
        }
    }).catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-envelope-fill me-2"></i>Send OTP to Current Email';
        alert('Network error. Please try again.');
    });
}

function cancelEmailOtp() {
    document.getElementById('emailStep1').style.display = 'block';
    document.getElementById('emailStep2').style.display = 'none';
    eDigits.forEach(d => d.value = '');
}

function generateRecoveryCodes() {
    if(!confirm("Are you sure? Generating new recovery codes will invalidate all your old codes.")) return;
    
    const btn = document.getElementById('generateCodesBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
    
    fetch('{{ route("recovery.generate") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if(data.success) {
            const container = document.getElementById('codesContainer');
            container.innerHTML = '';
            data.codes.forEach(code => {
                const codeEl = document.createElement('div');
                codeEl.style.cssText = 'background:rgba(0,0,0,0.2);padding:8px 12px;border-radius:6px;font-family:monospace;font-size:1.1rem;color:#f3e7cd;letter-spacing:1px;font-weight:700;';
                codeEl.textContent = code;
                container.appendChild(codeEl);
            });
            document.getElementById('recoveryCodesList').style.display = 'block';
            alert('New recovery codes generated successfully. Please save them now.');
        } else {
            alert(data.message || 'Failed to generate recovery codes.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Network error. Please try again.');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadDevices();
    prefetchWebAuthn();
    updateStabsScrollArrows();

    const nav = document.getElementById('stabsNav');
    if (nav) {
        nav.addEventListener('scroll', updateStabsScrollArrows, { passive: true });
    }
    window.addEventListener('resize', updateStabsScrollArrows, { passive: true });

    const rawHash = window.location.hash.replace('#tab-', '').replace('#', '');
    if (rawHash) {
        const tabBtn = Array.from(document.querySelectorAll('.stab')).find(b => b.getAttribute('onclick')?.includes(`'${rawHash}'`));
        if (tabBtn) {
            switchTab(rawHash, tabBtn);
        }
    }
});
</script>
@endsection
