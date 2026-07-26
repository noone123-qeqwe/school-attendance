@extends('layouts.admin_premium')
@section('page-title', 'My Profile')

@section('content')
<style>
    /* Profile header */
    .adm-profile-header {
        display: flex;
        align-items: center;
        gap: 20px;
        background: rgba(255,235,190,0.04);
        border: 1px solid rgba(255,215,145,0.1);
        border-radius: 16px;
        padding: 18px 22px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.18);
    }

    /* Avatar */
    .adm-profile-avatar {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 4px solid #f1f5f9;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        background: white;
        overflow: hidden;
        cursor: pointer;
        transition: transform 0.3s, box-shadow 0.3s;
        flex-shrink: 0;
    }
    .adm-profile-avatar:hover { transform: scale(1.05); box-shadow: 0 8px 28px rgba(0,0,0,0.25); }
    .adm-profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .adm-avatar-overlay {
        position: absolute; inset: 0;
        background: rgba(0,0,0,0.45);
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.2s;
        border-radius: 50%; color: white;
        font-size: 0.65rem; font-weight: 600; gap: 3px;
    }
    .adm-profile-avatar:hover .adm-avatar-overlay { opacity: 1; }

    /* Meta row */
   .adm-profile-meta {
        padding-left: 0;
        padding-top: 0;
        padding-bottom: 0;
        display: flex; align-items: center;
        justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
        min-height: auto;
    }
    .adm-profile-name { font-size: 1.3rem; font-weight: 800; color: #f3e7cd; letter-spacing: -0.3px; }
    .adm-profile-email { font-size: 0.8rem; color: #b39b82; margin-top: 2px; }
    .adm-role-badge {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,235,190,0.08); color: #f3e7cd;
        font-size: 0.75rem; font-weight: 700;
        padding: 5px 14px; border-radius: 99px;
        border: 1px solid rgba(255,215,145,0.15);
    }

    /* Info card */
    .adm-info-card {
        background: rgba(255,235,190,0.04); border-radius: 14px;
        border: 1px solid rgba(255,215,145,0.1);
        box-shadow: 0 12px 36px rgba(0,0,0,0.2);
        overflow: hidden; margin-bottom: 16px;
        transition: box-shadow 0.25s;
    }
    .adm-info-card:hover { box-shadow: 0 18px 42px rgba(0,0,0,0.28); }
    .adm-info-card-head {
        padding: 16px 22px; border-bottom: 1px solid rgba(255,215,145,0.12);
        display: flex; align-items: center; gap: 10px;
    }
    .adm-info-card-icon {
        width: 34px; height: 34px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center; font-size: 0.95rem;
        background: rgba(255,235,190,0.08); color: #cfa46f;
    }
    .adm-info-card-title { font-size: 0.9rem; font-weight: 700; color: #f3e7cd; }
    .adm-info-card-sub { font-size: 0.75rem; color: #b39b82; margin-top: 1px; }
    .adm-info-card-body { padding: 6px 22px 18px; }

    .adm-info-row {
        display: flex; align-items: center; gap: 14px;
        padding: 13px 0; border-bottom: 1px solid rgba(255,215,145,0.08);
    }
    .adm-info-row:last-child { border-bottom: none; }
    .adm-info-icon {
        width: 34px; height: 34px; border-radius: 9px;
        background: rgba(255,235,190,0.06); border: 1px solid rgba(255,215,145,0.12);
        display: flex; align-items: center; justify-content: center;
        color: #d4ab62; font-size: 0.9rem; flex-shrink: 0;
    }
    .adm-info-lbl { font-size: 0.7rem; font-weight: 600; color: #b39b82; text-transform: uppercase; letter-spacing: 0.5px; }
    .adm-info-val { font-size: 0.9rem; font-weight: 600; color: #f3e7cd; margin-top: 2px; }

    /* Form inputs */
    .adm-form-label {
        font-size: 0.72rem; font-weight: 700; color: #b39b82;
        text-transform: uppercase; letter-spacing: 0.5px;
        display: block; margin-bottom: 6px;
    }
    .adm-form-input {
        width: 100%; padding: 11px 14px;
        border-radius: 10px; border: 1.5px solid rgba(255,215,145,0.12);
        font-size: 0.875rem; font-family: 'Inter', sans-serif;
        background: rgba(255,235,190,0.04); color: #f3e7cd;
        transition: all 0.2s; outline: none;
    }
    .adm-form-input:hover { border-color: rgba(255,215,145,0.22); background: rgba(255,235,190,0.08); }
    .adm-form-input:focus {
        border-color: #cfa46f; background: rgba(255,235,190,0.08);
        box-shadow: 0 0 0 3px rgba(207,164,111,0.12);
    }
    .pw-wrap { position: relative; }
    .pw-wrap .adm-form-input { padding-right: 44px; }
    .eye-btn {
        position: absolute; right: 13px; top: 50%;
        transform: translateY(-50%);
        color: #b39b82; font-size: 1rem;
        cursor: pointer; background: none; border: none; padding: 0;
        transition: color 0.2s; line-height: 1;
    }
    .eye-btn:hover { color: #f3e7cd; }

    .adm-save-btn {
        padding: 11px 28px;
        background: #7f432e;
        color: #f3e7cd; font-weight: 700; font-size: 0.875rem;
        border: none; border-radius: 10px; cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,0,0,0.25);
        transition: all 0.25s;
    }
    .adm-save-btn:hover {
        background: #8f4f37;
        transform: translateY(-2px);
        box-shadow: 0 8px 22px rgba(0,0,0,0.32);
    }
    .adm-save-btn:active { transform: translateY(0); }

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
</style>

@if(session('success'))
<div class="flash-ok"><i class="bi bi-check-circle-fill fs-5"></i><span>{{ session('success') }}</span></div>
@endif
@if($errors->any())
<div class="flash-err"><i class="bi bi-exclamation-circle-fill fs-5"></i><span>{{ $errors->first() }}</span></div>
@endif

<!-- Profile Header -->
<div class="adm-profile-header">
    <form action="{{ route('admin.profile.image') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="profile_image" id="adminProfileImg" class="d-none" accept="image/*" onchange="this.form.submit()">
        <div class="adm-profile-avatar" onclick="document.getElementById('adminProfileImg').click()">
            @if($user->profile_image)
                <img src="/storage/{{ $user->profile_image }}"
                     onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=800000&color=fff&size=200'">
            @else
                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=800000&color=fff&size=200">
            @endif
            <div class="adm-avatar-overlay">
                <i class="bi bi-camera-fill" style="font-size:1.2rem;"></i>
                <span>Change</span>
            </div>
        </div>
    </form>

    <!-- Name + role -->
    <div class="adm-profile-meta">
        <div>
            <div class="adm-profile-name">{{ $user->name }}</div>
            <div class="adm-profile-email">{{ $user->email }}</div>
        </div>
        <div class="adm-role-badge">
            <i class="bi bi-shield-fill"></i> Administrator
        </div>
    </div>
</div>

<!-- Grid -->
<div class="row g-3">

    <!-- LEFT: Account Info -->
    <div class="col-lg-7">
        <div class="adm-info-card">
            <div class="adm-info-card-head">
                <div class="adm-info-card-icon" style="background:rgba(255,235,190,0.08);color:#cfa46f;"><i class="bi bi-person-fill"></i></div>
                <div>
                    <div class="adm-info-card-title">Account Information</div>
                    <div class="adm-info-card-sub">Your admin account details</div>
                </div>
            </div>
            <div class="adm-info-card-body">
                <div class="adm-info-row">
                    <div class="adm-info-icon"><i class="bi bi-person-fill"></i></div>
                    <div><div class="adm-info-lbl">Full Name</div><div class="adm-info-val">{{ $user->name }}</div></div>
                </div>
                <div class="adm-info-row">
                    <div class="adm-info-icon"><i class="bi bi-envelope-fill"></i></div>
                    <div><div class="adm-info-lbl">Email Address</div><div class="adm-info-val">{{ $user->email }}</div></div>
                </div>
                <div class="adm-info-row">
                    <div class="adm-info-icon"><i class="bi bi-shield-fill"></i></div>
                    <div><div class="adm-info-lbl">Role</div><div class="adm-info-val">Administrator</div></div>
                </div>
                <div class="adm-info-row">
                    <div class="adm-info-icon"><i class="bi bi-calendar3"></i></div>
                    <div><div class="adm-info-lbl">Member Since</div><div class="adm-info-val">{{ $user->created_at->format('F j, Y') }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Update Security -->
    <div class="col-lg-5">
        <div class="adm-info-card">
            <div class="adm-info-card-head">
                <div class="adm-info-card-icon" style="background:rgba(255,235,190,0.08);color:#f3e7cd;"><i class="bi bi-shield-lock-fill"></i></div>
                <div><div class="adm-info-card-title">Security & Contact</div><div class="adm-info-card-sub">Update email and password</div></div>
            </div>
            <div class="adm-info-card-body" style="padding-top:16px;">

                <!-- OTP Password Change -->
                <div>
                    <div style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Change Password</div>
                    <div id="admOtpStep1">
                        <p style="font-size:.85rem;color:#64748b;margin-bottom:12px;">
                            We'll send a 6-digit OTP to <strong>{{ $user->email }}</strong> to verify it's you.
                        </p>
                        <button type="button" onclick="admRequestOtp()" id="admSendOtpBtn" class="adm-save-btn">
                            <i class="bi bi-send-fill me-2"></i>Send OTP to Email
                        </button>
                    </div>
                    <div id="admOtpStep2" style="display:none;">
                        <div style="background:rgba(255,235,190,0.06);border:1px solid rgba(255,215,145,0.12);color:#f3e7cd;border-radius:10px;padding:10px 14px;font-size:.82rem;margin-bottom:14px;">
                            <i class="bi bi-check-circle me-2"></i>OTP sent to {{ $user->email }}
                        </div>
                        <form action="{{ route('otp.change') }}" method="POST">
                            @csrf
                            <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">Enter OTP</label>
                            <div style="display:flex;gap:8px;margin-bottom:14px;">
                                @for($i=1;$i<=6;$i++)
                                <input type="text" class="adm-otp-digit" maxlength="1" inputmode="numeric" id="ad{{$i}}" style="width:42px;height:48px;border-radius:9px;border:1.5px solid rgba(255,215,145,0.12);font-size:1.2rem;font-weight:800;text-align:center;color:#f3e7cd;background:rgba(255,235,190,0.04);outline:none;transition:all .2s;">
                                @endfor
                            </div>
                            <input type="hidden" name="otp" id="admOtpHidden">
                            <div class="mb-3">
                                <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">New Password</label>
                                <div class="pw-wrap">
                                    <input type="password" name="password" id="apw1" class="adm-form-input" placeholder="At least 8 characters" required>
                                    <button type="button" class="eye-btn" onclick="togglePw('apw1',this)" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Confirm Password</label>
                                <div class="pw-wrap">
                                    <input type="password" name="password_confirmation" id="apw2" class="adm-form-input" placeholder="Repeat new password" required>
                                    <button type="button" class="eye-btn" onclick="togglePw('apw2',this)" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                                </div>
                            </div>
                            <div style="display:flex;gap:10px;">
                                <button type="button" onclick="admCancelOtp()" style="padding:11px 20px;background:rgba(255,235,190,0.06);color:#f3e7cd;border:1.5px solid rgba(255,215,145,0.12);border-radius:10px;font-weight:600;font-size:.875rem;cursor:pointer;">Cancel</button>
                                <button type="submit" class="adm-save-btn" style="flex:1;" onclick="admCollectOtp()"><i class="bi bi-check2 me-2"></i>Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function togglePw(id, btn) {
    const i = document.getElementById(id);
    const ic = btn.querySelector('i');
    if (i.type === 'password') { i.type = 'text'; ic.className = 'bi bi-eye'; btn.style.color = '#5c001d'; }
    else { i.type = 'password'; ic.className = 'bi bi-eye-slash'; btn.style.color = ''; }
}

// Admin OTP digit handling
const admDigits = document.querySelectorAll('.adm-otp-digit');
admDigits.forEach((input, idx) => {
    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
        if (e.target.value && idx < admDigits.length - 1) admDigits[idx + 1].focus();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) admDigits[idx - 1].focus();
    });
});

function admCollectOtp() {
    document.getElementById('admOtpHidden').value = Array.from(admDigits).map(d => d.value).join('');
}

function admRequestOtp() {
    const btn = document.getElementById('admSendOtpBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Sending...';
    fetch('{{ route("otp.change.send") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            document.getElementById('admOtpStep1').style.display = 'none';
            document.getElementById('admOtpStep2').style.display = 'block';
            if (admDigits[0]) admDigits[0].focus();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Send OTP to Email';
            alert(data.message || 'Failed to send OTP.');
        }
    });
}

function admCancelOtp() {
    document.getElementById('admOtpStep1').style.display = 'block';
    document.getElementById('admOtpStep2').style.display = 'none';
    admDigits.forEach(d => d.value = '');
}
</script>
@endsection
