@extends('layouts.app')
@section('page-title', 'My Profile')

@section('content')
<style>
    .tch-profile-header {
        display: flex; align-items: center; gap: 18px;
        padding: 18px 22px;
        flex-wrap: wrap;
        background: rgba(255,235,190,0.04);
        border: 1px solid rgba(255,215,145,0.1);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.18);
        margin-bottom: 22px;
    }
    .tch-profile-avatar {
        width: 100px; height: 100px;
        border-radius: 50%;
        border: 4px solid #f1f5f9;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        background: white;
        overflow: hidden;
        cursor: pointer;
        transition: transform 0.3s, box-shadow 0.3s;
        flex-shrink: 0;
    }
    .tch-profile-avatar:hover { transform: scale(1.05); box-shadow: 0 8px 28px rgba(0,0,0,0.25); }
    .tch-profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .tch-profile-header-info { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    .tch-profile-name { font-size: 1.3rem; font-weight: 800; color: #f3e7cd; letter-spacing: -0.3px; }
    .tch-profile-email { font-size: 0.9rem; color: #b39b82; }
    .tch-role-badge {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,235,190,0.08); color: #f3e7cd;
        font-size: 0.75rem; font-weight: 700;
        padding: 5px 14px; border-radius: 99px;
        border: 1px solid rgba(255,215,145,0.15);
    }
    .tch-info-card {
        background: rgba(255,235,190,0.04); border-radius: 14px;
        border: 1px solid rgba(255,215,145,0.1);
        box-shadow: 0 12px 36px rgba(0,0,0,0.2);
        overflow: hidden; margin-bottom: 16px;
        transition: box-shadow 0.25s;
    }
    .tch-info-card:hover { box-shadow: 0 18px 42px rgba(0,0,0,0.28); }
    .tch-info-card-head {
        padding: 16px 22px; border-bottom: 1px solid rgba(255,215,145,0.12);
        display: flex; align-items: center; gap: 10px;
    }
    .tch-info-card-icon {
        width: 34px; height: 34px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center; font-size: 0.95rem;
    }
    .tch-info-card-title { font-size: 0.9rem; font-weight: 700; color: var(--portal-text); }
    .tch-info-card-sub { font-size: 0.75rem; color: var(--portal-muted); margin-top: 1px; }
    .tch-info-card-body { padding: 6px 22px 18px; }
    .tch-info-row {
        display: flex; align-items: center; gap: 14px;
        padding: 13px 0; border-bottom: 1px solid rgba(255,215,145,0.08);
    }
    .tch-info-row:last-child { border-bottom: none; }
    .tch-info-icon {
        width: 34px; height: 34px; border-radius: 9px;
        background: rgba(255,235,190,0.06); border: 1px solid rgba(255,215,145,0.12);
        display: flex; align-items: center; justify-content: center;
        color: #d4ab62; font-size: 0.9rem; flex-shrink: 0;
    }
    .tch-info-lbl { font-size: 0.7rem; font-weight: 600; color: #b39b82; text-transform: uppercase; letter-spacing: 0.5px; }
    .tch-info-val { font-size: 0.95rem; font-weight: 600; color: #f3e7cd; margin-top: 2px; }
    .tch-form-input {
        width: 100%; padding: 11px 14px;
        border-radius: 10px; border: 1.5px solid rgba(255,215,145,0.12);
        font-size: 0.875rem; font-family: 'Inter', sans-serif;
        background: rgba(255,235,190,0.04); color: #f3e7cd;
        transition: all 0.2s; outline: none;
    }
    .tch-form-input:hover { border-color: rgba(255,215,145,0.22); background: rgba(255,235,190,0.06); }
    .tch-form-input:focus {
        border-color: #cfa46f; background: rgba(255,235,190,0.06);
        box-shadow: 0 0 0 3px rgba(207,164,111,0.12);
    }
    .pw-wrap { position: relative; }
    .pw-wrap .tch-form-input { padding-right: 44px; }
    .eye-btn {
        position: absolute; right: 13px; top: 50%;
        transform: translateY(-50%);
        color: #94a3b8; font-size: 1rem;
        cursor: pointer; background: none; border: none; padding: 0;
        transition: color 0.2s; line-height: 1;
    }
    .eye-btn:hover { color: #7c2d12; }
    .tch-save-btn {
        padding: 11px 28px;
        background: #7f432e;
        color: #f3e7cd; font-weight: 700; font-size: 0.875rem;
        border: none; border-radius: 10px; cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,0,0,0.25);
        transition: all 0.25s;
    }
    .tch-save-btn:hover {
        background: #8f4f37;
        transform: translateY(-2px);
        box-shadow: 0 8px 22px rgba(0,0,0,0.32);
    }
    .tch-save-btn:active { transform: translateY(0); }
    .flash-ok {
        background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d;
        border-radius: 12px; padding: 12px 16px; font-size: 0.875rem;
        margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
    }
    .flash-err {
        background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
        border-radius: 12px; padding: 12px 16px; font-size: 0.875rem;
        margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
    }
    @media (max-width: 768px) {
        .tch-profile-header { flex-direction: column; align-items: flex-start; }
        .tch-profile-avatar { width: 84px; height: 84px; }
    }
</style>

@if(session('success'))
<div class="flash-ok"><i class="bi bi-check-circle-fill fs-5"></i><span>{{ session('success') }}</span></div>
@endif
@if($errors->any())
<div class="flash-err"><i class="bi bi-exclamation-circle-fill fs-5"></i><span>{{ $errors->first() }}</span></div>
@endif

<div class="tch-profile-header">
    <div class="tch-avatar-wrapper">
        <x-profile-photo-manager :user="$teacher" :size="96" avatar-id="teacherAvatarDisplay" />
    </div>

        <div class="tch-profile-header-info">
            <div class="tch-profile-name">{{ $teacher->name }}</div>
            <div class="tch-profile-email">{{ $teacher->email }}</div>
            <div class="tch-role-badge"><i class="bi bi-person-workspace"></i> Teacher</div>
        </div>
</div>

<!-- Grid -->
<div class="row g-3">

    <!-- LEFT: Account Info -->
    <div class="col-lg-7">
        <div class="tch-info-card">
            <div class="tch-info-card-head">
                <div class="tch-info-card-icon" style="background:rgba(255,235,190,0.08);color:#cfa46f;"><i class="bi bi-person-fill"></i></div>
                <div>
                    <div class="tch-info-card-title">Account Information</div>
                    <div class="tch-info-card-sub">Your teacher account details</div>
                </div>
            </div>
            <div class="tch-info-card-body">
                <div class="tch-info-row">
                    <div class="tch-info-icon"><i class="bi bi-person-fill"></i></div>
                    <div><div class="tch-info-lbl">Full Name</div><div class="tch-info-val">{{ $teacher->name }}</div></div>
                </div>
                <div class="tch-info-row">
                    <div class="tch-info-icon"><i class="bi bi-envelope-fill"></i></div>
                    <div><div class="tch-info-lbl">Email Address</div><div class="tch-info-val">{{ $teacher->email }}</div></div>
                </div>
                <div class="tch-info-row">
                    <div class="tch-info-icon"><i class="bi bi-card-text"></i></div>
                    <div><div class="tch-info-lbl">Employee ID</div><div class="tch-info-val">{{ $teacher->employee_id ?? 'Not set' }}</div></div>
                </div>
                @if($teacher->department)
                <div class="tch-info-row">
                    <div class="tch-info-icon"><i class="bi bi-building"></i></div>
                    <div><div class="tch-info-lbl">Department</div><div class="tch-info-val">{{ $teacher->department }}</div></div>
                </div>
                @endif
                @if($teacher->position)
                <div class="tch-info-row">
                    <div class="tch-info-icon"><i class="bi bi-briefcase"></i></div>
                    <div><div class="tch-info-lbl">Position</div><div class="tch-info-val">{{ $teacher->position }}</div></div>
                </div>
                @endif
                <div class="tch-info-row">
                    <div class="tch-info-icon"><i class="bi bi-calendar3"></i></div>
                    <div><div class="tch-info-lbl">Member Since</div><div class="tch-info-val">{{ $teacher->created_at->format('F j, Y') }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Update Security -->
    <div class="col-lg-5">
        <div class="tch-info-card">
            <div class="tch-info-card-head">
                <div class="tch-info-card-icon" style="background:rgba(255,235,190,0.08);color:#f3e7cd;"><i class="bi bi-shield-lock-fill"></i></div>
                <div><div class="tch-info-card-title">Security & Contact</div><div class="tch-info-card-sub">Update email and password</div></div>
            </div>
            <div class="tch-info-card-body" style="padding-top:16px;">

                <!-- Change Email via OTP -->
                <div style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,0.08);">
                    <div style="font-size:.72rem;font-weight:700;color:var(--portal-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Email Address</div>
                    
                    <div id="tchEmailStep1">
                        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--portal-canvas);border-radius:10px;border:1px solid rgba(255,255,255,0.1);margin-bottom:12px;">
                            <i class="bi bi-envelope-fill" style="color:var(--portal-gold);"></i>
                            <span style="font-size:.875rem;font-weight:600;color:var(--portal-text);">{{ $teacher->email }}</span>
                        </div>
                        <p style="font-size:.85rem;color:var(--portal-muted);margin-bottom:12px;">
                            An OTP will be sent to your <strong style="color:var(--portal-text);">current email</strong> to verify it's you before changing.
                        </p>
                        <button type="button" onclick="tchRequestEmailOtp()" id="tchSendEmailOtpBtn" class="tch-save-btn">
                            <i class="bi bi-envelope-fill me-2"></i>Send OTP to Current Email
                        </button>
                    </div>

                    <div id="tchEmailStep2" style="display:none;">
                        <div style="background:rgba(52,211,153,0.12);border:1px solid rgba(52,211,153,0.3);color:var(--portal-emerald);border-radius:10px;padding:10px 14px;font-size:.82rem;margin-bottom:14px;">
                            <i class="bi bi-envelope-check me-2"></i>OTP sent to <strong>{{ $teacher->email }}</strong>
                        </div>
                        <form action="{{ route('otp.email.change') }}" method="POST">
                            @csrf
                            <label style="font-size:.72rem;font-weight:700;color:var(--portal-muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">Enter OTP</label>
                            <div style="display:flex;gap:8px;margin-bottom:14px;">
                                @for($j=1;$j<=6;$j++)
                                <input type="text" class="tch-email-otp-digit" maxlength="1" inputmode="numeric" id="ted{{$j}}" style="width:42px;height:48px;border-radius:9px;border:1px solid rgba(212,175,55,0.28);font-size:1.2rem;font-weight:800;text-align:center;color:var(--portal-text);background:var(--portal-canvas);outline:none;transition:all .2s;">
                                @endfor
                            </div>
                            <input type="hidden" name="otp" id="tchEmailOtpHidden">
                            <div class="mb-3">
                                <label style="font-size:.72rem;font-weight:700;color:var(--portal-muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">New Email Address</label>
                                <input type="email" name="new_email" class="tch-form-input" placeholder="newemail@example.com" required>
                            </div>
                            <div style="display:flex;gap:10px;">
                                <button type="button" onclick="tchCancelEmailOtp()" class="tch-btn tch-btn-ghost" style="padding:11px 20px;border-radius:10px;font-weight:600;font-size:.875rem;cursor:pointer;">Cancel</button>
                                <button type="submit" class="tch-save-btn" style="flex:1;" onclick="tchCollectEmailOtp()"><i class="bi bi-check2 me-2"></i>Change Email</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- OTP Password Change -->
                <div style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,0.08);">
                    <div style="font-size:.72rem;font-weight:700;color:var(--portal-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Change Password</div>
                    <div id="tchOtpStep1">
                        <p style="font-size:.85rem;color:var(--portal-muted);margin-bottom:12px;">
                            We'll send a 6-digit OTP to <strong style="color:var(--portal-text);">{{ $teacher->email }}</strong> to verify it's you.
                        </p>
                        <button type="button" onclick="tchRequestOtp()" id="tchSendOtpBtn" class="tch-save-btn">
                            <i class="bi bi-send-fill me-2"></i>Send OTP to Email
                        </button>
                    </div>
                    <div id="tchOtpStep2" style="display:none;">
                        <div style="background:rgba(52,211,153,0.12);border:1px solid rgba(52,211,153,0.3);color:var(--portal-emerald);border-radius:10px;padding:10px 14px;font-size:.82rem;margin-bottom:14px;">
                            <i class="bi bi-check-circle me-2"></i>OTP sent to {{ $teacher->email }}
                        </div>
                        <form action="{{ route('otp.change') }}" method="POST">
                            @csrf
                            <label style="font-size:.72rem;font-weight:700;color:var(--portal-muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">Enter OTP</label>
                            <div style="display:flex;gap:8px;margin-bottom:14px;">
                                @for($i=1;$i<=6;$i++)
                                <input type="text" class="tch-otp-digit" maxlength="1" inputmode="numeric" id="tc{{$i}}" style="width:42px;height:48px;border-radius:9px;border:1px solid rgba(212,175,55,0.28);font-size:1.2rem;font-weight:800;text-align:center;color:var(--portal-text);background:var(--portal-canvas);outline:none;transition:all .2s;">
                                @endfor
                            </div>
                            <input type="hidden" name="otp" id="tchOtpHidden">
                            <div class="mb-3">
                                <label style="font-size:.72rem;font-weight:700;color:var(--portal-muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">New Password</label>
                                <div class="pw-wrap">
                                    <input type="password" name="password" id="tpw1" class="tch-form-input" placeholder="12+ chars, mixed case, number, symbol" minlength="12" required>
                                    <button type="button" class="eye-btn" onclick="togglePw('tpw1',this,event)" data-toggle-password="tpw1" aria-controls="tpw1" aria-label="Show password" title="Show password" aria-pressed="false"><i class="bi bi-eye-slash"></i></button>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label style="font-size:.72rem;font-weight:700;color:var(--portal-muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Confirm Password</label>
                                <div class="pw-wrap">
                                    <input type="password" name="password_confirmation" id="tpw2" class="tch-form-input" placeholder="Repeat new password" minlength="12" required>
                                    <button type="button" class="eye-btn" onclick="togglePw('tpw2',this,event)" data-toggle-password="tpw2" aria-controls="tpw2" aria-label="Show password confirmation" title="Show password confirmation" aria-pressed="false"><i class="bi bi-eye-slash"></i></button>
                                </div>
                            </div>
                            <div style="display:flex;gap:10px;">
                                <button type="button" onclick="tchCancelOtp()" class="tch-btn tch-btn-ghost" style="padding:11px 20px;border-radius:10px;font-weight:600;font-size:.875rem;cursor:pointer;">Cancel</button>
                                <button type="submit" class="tch-save-btn" style="flex:1;" onclick="tchCollectOtp()"><i class="bi bi-check2 me-2"></i>Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Device passkey -->
                <div style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid rgba(255,215,145,0.08);">
                    <div style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Device passkeys</div>
                    
                    <div id="webauthnUnsupported" role="status" style="display:none;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:#f87171;border-radius:12px;padding:14px 18px;font-size:.85rem;margin-bottom:14px;">
                        <div style="display:flex;align-items:flex-start;gap:10px;">
                            <i class="bi bi-exclamation-triangle" style="font-size:1.1rem;flex-shrink:0;margin-top:2px;"></i>
                            <div id="webauthnUnsupportedMsg" style="font-size:.8rem;line-height:1.4;">
                                Passkey setup is unavailable on this device.
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom:16px;">
                        <div id="deviceList">
                            <div style="text-align:center;padding:16px;color:#b39b82;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:10px;border:1px dashed rgba(207,164,111,0.2);" id="noDevices">
                                <i class="bi bi-shield-lock" style="font-size:1.8rem;display:block;margin-bottom:6px;opacity:.4;color:var(--gold,#CFA46F);"></i>
                                No passkeys registered yet.
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="registerDevicePasskey()" id="registerFpBtn" disabled class="tch-save-btn" style="background:linear-gradient(135deg,#16a34a,#22c55e);box-shadow:0 4px 14px rgba(22,163,74,.25);color:white;">
                        <i class="bi bi-shield-lock me-2"></i>Set up a passkey
                    </button>
                    <div id="fpMessage" style="margin-top:12px;font-size:.82rem;display:none;"></div>
                </div>

                <!-- Recovery Codes -->
                <div>
                    <div style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Recovery Codes</div>
                    <p style="font-size:.85rem;color:#64748b;margin-bottom:12px;">
                        Recovery codes can be used to log in if you lose access to your email or passkey.
                    </p>
                    <button type="button" onclick="generateRecoveryCodes()" id="generateCodesBtn" class="tch-save-btn" style="background:linear-gradient(135deg,#eab308,#ca8a04);border-color:#ca8a04;color:white;">
                        <i class="bi bi-key-fill me-2"></i>Generate Recovery Codes
                    </button>
                    
                    <div id="recoveryCodesList" style="display:none;margin-top:16px;background:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.28);border-radius:10px;padding:16px;">
                        <div style="font-size:.875rem;font-weight:600;color:var(--portal-amber);margin-bottom:12px;">
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

    <!-- Notification Preferences -->
    <div class="col-lg-5 mt-4">
        <div class="tch-info-card">
            <div class="tch-info-card-head">
                <div class="tch-info-card-icon" style="background:rgba(255,235,190,0.08);color:#f3e7cd;"><i class="bi bi-bell-fill"></i></div>
                <div><div class="tch-info-card-title">Notification Preferences</div><div class="tch-info-card-sub">Manage your alerts</div></div>
            </div>
            <div class="tch-info-card-body" style="padding-top:16px;">
                @php 
                    $prefs = is_string($teacher->notification_preferences) ? json_decode($teacher->notification_preferences, true) : (is_array($teacher->notification_preferences) ? $teacher->notification_preferences : []);
                    $emailNotif = $prefs['email'] ?? true;
                    $pushNotif = $prefs['push'] ?? true;
                @endphp
                <form action="{{ route('teacher.profile.update') }}" method="POST">
                    @csrf
                    
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <div>
                            <div style="font-weight: 700; color: #f3e7cd; font-size: 0.9rem;">Email Notifications</div>
                            <div style="font-size: 0.8rem; color: #b39b82;">Receive updates via email</div>
                        </div>
                        <div class="form-check form-switch" style="font-size: 1.25rem;">
                            <input class="form-check-input" type="checkbox" name="notif_email" value="1" {{ $emailNotif ? 'checked' : '' }} style="cursor: pointer;">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <div style="font-weight: 700; color: #f3e7cd; font-size: 0.9rem;">Push Notifications</div>
                            <div style="font-size: 0.8rem; color: #b39b82;">Receive system alerts</div>
                        </div>
                        <div class="form-check form-switch" style="font-size: 1.25rem;">
                            <input class="form-check-input" type="checkbox" name="notif_push" value="1" {{ $pushNotif ? 'checked' : '' }} style="cursor: pointer;">
                        </div>
                    </div>

                    <button type="submit" class="tch-save-btn w-100">
                        <i class="bi bi-save-fill me-2"></i>Save Preferences
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

@include('partials.profile-device-passkey-capability')
<script @cspNonce>
function togglePw(id, btn, e) {
    if (e) {
        if (e._pwToggled) return;
        e._pwToggled = true;
        if (e.preventDefault) e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
    }
    if (window.togglePassword) {
        window.togglePassword(id, btn, e);
        return;
    }
    const i = typeof id === 'string' ? document.getElementById(id) : id;
    if (!i) return;

    let start = null;
    let end = null;
    try {
        start = i.selectionStart;
        end = i.selectionEnd;
    } catch (err) {}

    const isPw = i.type === 'password';
    i.type = isPw ? 'text' : 'password';
    const ic = btn ? btn.querySelector('i') : null;
    if (ic) ic.className = isPw ? 'bi bi-eye' : 'bi bi-eye-slash';
    if (btn) {
        btn.style.color = isPw ? '#7c2d12' : '';
        const isConf = i.name === 'password_confirmation' || i.id === 'tpw2';
        const label = isPw 
            ? (isConf ? 'Hide password confirmation' : 'Hide password')
            : (isConf ? 'Show password confirmation' : 'Show password');
        btn.setAttribute('aria-label', label);
        btn.setAttribute('title', label);
        btn.setAttribute('aria-pressed', isPw ? 'true' : 'false');
    }

    try {
        i.focus();
        if (start !== null && end !== null) {
            i.setSelectionRange(start, end);
        }
    } catch (err) {}
}

// Teacher OTP digit handling
const tchDigits = document.querySelectorAll('.tch-otp-digit');
tchDigits.forEach((input, idx) => {
    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
        if (e.target.value && idx < tchDigits.length - 1) tchDigits[idx + 1].focus();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) tchDigits[idx - 1].focus();
    });
});

function tchCollectOtp() {
    document.getElementById('tchOtpHidden').value = Array.from(tchDigits).map(d => d.value).join('');
}

function tchRequestOtp() {
    const btn = document.getElementById('tchSendOtpBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Sending...';
    fetch('{{ route("otp.change.send") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            document.getElementById('tchOtpStep1').style.display = 'none';
            document.getElementById('tchOtpStep2').style.display = 'block';
            if (data.dev_otp && tchDigits) {
                const str = String(data.dev_otp).trim();
                tchDigits.forEach((d, i) => { if (str[i]) d.value = str[i]; });
                tchCollectOtp();
            }
            if (tchDigits[0]) tchDigits[0].focus();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Send OTP to Email';
            alert(data.message || 'Failed to send OTP.');
        }
    });
}

function tchCancelOtp() {
    document.getElementById('tchOtpStep1').style.display = 'block';
    document.getElementById('tchOtpStep2').style.display = 'none';
    tchDigits.forEach(d => d.value = '');
}

// Teacher Email OTP
const tchEmailDigits = document.querySelectorAll('.tch-email-otp-digit');
tchEmailDigits.forEach((input, idx) => {
    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
        if (e.target.value && idx < tchEmailDigits.length - 1) tchEmailDigits[idx + 1].focus();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) tchEmailDigits[idx - 1].focus();
    });
});

function tchCollectEmailOtp() {
    document.getElementById('tchEmailOtpHidden').value = Array.from(tchEmailDigits).map(d => d.value).join('');
}

function tchRequestEmailOtp() {
    const btn = document.getElementById('tchSendEmailOtpBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Sending...';
    fetch('{{ route("otp.email.send") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            document.getElementById('tchEmailStep1').style.display = 'none';
            document.getElementById('tchEmailStep2').style.display = 'block';
            if (data.dev_otp && tchEmailDigits) {
                const str = String(data.dev_otp).trim();
                tchEmailDigits.forEach((d, i) => { if (str[i]) d.value = str[i]; });
                tchCollectEmailOtp();
            }
            if (tchEmailDigits[0]) tchEmailDigits[0].focus();
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

function tchCancelEmailOtp() {
    document.getElementById('tchEmailStep1').style.display = 'block';
    document.getElementById('tchEmailStep2').style.display = 'none';
    tchEmailDigits.forEach(d => d.value = '');
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

// Passkey device loading & revocation handled centrally by partials.profile-device-passkey-capability.
</script>
@endsection
