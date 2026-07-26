@extends('teacher.layout')
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
    .tch-info-card-title { font-size: 0.9rem; font-weight: 700; color: #7c2d12; }
    .tch-info-card-sub { font-size: 0.75rem; color: #94a3b8; margin-top: 1px; }
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
    <form action="{{ route('teacher.profile.image') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="profile_image" id="teacherProfileImg" class="d-none" accept="image/*" onchange="this.form.submit()">
        <div class="tch-profile-avatar" onclick="document.getElementById('teacherProfileImg').click()">
                @if($teacher->profile_image)
                    <img src="/storage/{{ $teacher->profile_image }}"
                         onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($teacher->name) }}&background=7c2d12&color=fff&size=200'">
                @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($teacher->name) }}&background=7c2d12&color=fff&size=200">
                @endif
            </div>
        </form>

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

                <!-- OTP Password Change -->
                <div>
                    <div style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Change Password</div>
                    <div id="tchOtpStep1">
                        <p style="font-size:.85rem;color:#64748b;margin-bottom:12px;">
                            We'll send a 6-digit OTP to <strong>{{ $teacher->email }}</strong> to verify it's you.
                        </p>
                        <button type="button" onclick="tchRequestOtp()" id="tchSendOtpBtn" class="tch-save-btn">
                            <i class="bi bi-send-fill me-2"></i>Send OTP to Email
                        </button>
                    </div>
                    <div id="tchOtpStep2" style="display:none;">
                        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:10px;padding:10px 14px;font-size:.82rem;margin-bottom:14px;">
                            <i class="bi bi-check-circle me-2"></i>OTP sent to {{ $teacher->email }}
                        </div>
                        <form action="{{ route('teacher.otp.change') }}" method="POST">
                            @csrf
                            <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">Enter OTP</label>
                            <div style="display:flex;gap:8px;margin-bottom:14px;">
                                @for($i=1;$i<=6;$i++)
                                <input type="text" class="tch-otp-digit" maxlength="1" inputmode="numeric" id="tc{{$i}}" style="width:42px;height:48px;border-radius:9px;border:1.5px solid #e2e8f0;font-size:1.2rem;font-weight:800;text-align:center;color:#7c2d12;background:#f8fafc;outline:none;transition:all .2s;">
                                @endfor
                            </div>
                            <input type="hidden" name="otp" id="tchOtpHidden">
                            <div class="mb-3">
                                <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">New Password</label>
                                <div class="pw-wrap">
                                    <input type="password" name="password" id="tpw1" class="tch-form-input" placeholder="At least 8 characters" required>
                                    <button type="button" class="eye-btn" onclick="togglePw('tpw1',this)" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Confirm Password</label>
                                <div class="pw-wrap">
                                    <input type="password" name="password_confirmation" id="tpw2" class="tch-form-input" placeholder="Repeat new password" required>
                                    <button type="button" class="eye-btn" onclick="togglePw('tpw2',this)" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                                </div>
                            </div>
                            <div style="display:flex;gap:10px;">
                                <button type="button" onclick="tchCancelOtp()" style="padding:11px 20px;background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;border-radius:10px;font-weight:600;font-size:.875rem;cursor:pointer;">Cancel</button>
                                <button type="submit" class="tch-save-btn" style="flex:1;" onclick="tchCollectOtp()"><i class="bi bi-check2 me-2"></i>Change Password</button>
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
    if (i.type === 'password') { i.type = 'text'; ic.className = 'bi bi-eye'; btn.style.color = '#7c2d12'; }
    else { i.type = 'password'; ic.className = 'bi bi-eye-slash'; btn.style.color = ''; }
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
    fetch('{{ route("teacher.otp.change.send") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            document.getElementById('tchOtpStep1').style.display = 'none';
            document.getElementById('tchOtpStep2').style.display = 'block';
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
</script>
@endsection