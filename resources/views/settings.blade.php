@php
    $layout = 'layouts.app';
    if(Auth::check()) {
        if(Auth::user()->isAdmin()) $layout = 'admin.layout';
        elseif(Auth::user()->isTeacher()) $layout = 'teacher.layout';
        elseif(Auth::user()->isParent()) $layout = 'parent.layout';
    }
@endphp
@extends($layout)

@section('content')
@php
    $user = Auth::user();
    $allRecords   = $user->attendances ?? collect();
    $totalRecords = $allRecords->count();
    $totalPresent = $allRecords->where('status','Present')->count();
    $totalLate    = $allRecords->where('status','Late')->count();
    $totalAbsent  = $allRecords->where('status','Absent')->count();
    $rate = $totalRecords > 0 ? round((($totalPresent+$totalLate)/$totalRecords)*100) : 0;
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
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.stat-box{background:rgba(255,235,190,0.03);border:1px solid rgba(255,215,145,0.08);border-radius:12px;padding:14px 16px;text-align:center;transition:transform .2s,box-shadow .2s;}
.stat-box:hover{transform:translateY(-3px);box-shadow:0 6px 16px rgba(0,0,0,.15);border-color:rgba(255,215,145,0.15);}
.stat-val{font-size:1.6rem;font-weight:800;line-height:1;}
.stat-lbl{font-size:.68rem;font-weight:600;color:#b39b82;text-transform:uppercase;letter-spacing:.4px;margin-top:4px;}
.prog-bar{height:8px;background:rgba(255,215,145,0.1);border-radius:99px;overflow:hidden;margin-top:6px;}
.prog-fill{height:100%;border-radius:99px;transition:width 1s ease;}
.info-row{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid rgba(255,215,145,0.06);}
.info-row:last-child{border-bottom:none;}
.info-icon{width:34px;height:34px;border-radius:9px;background:rgba(207,164,111,0.12);border:1px solid rgba(255,215,145,0.1);display:flex;align-items:center;justify-content:center;color:#cfa46f;font-size:.9rem;flex-shrink:0;}
.info-lbl{font-size:.7rem;font-weight:600;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;}
.info-val{font-size:.9rem;font-weight:600;color:#f3e7cd;}
.act-row{display:flex;align-items:center;gap:14px;padding:14px 24px;border-bottom:1px solid rgba(255,215,145,0.06);transition:background .15s;}
.act-row:hover{background:rgba(255,235,190,0.04);}
.act-row:last-child{border-bottom:none;}

/* Form overrides specific for Dark Theme */
.email-otp-digit, .otp-digit-s {
    color: #f3e7cd !important;
    background: rgba(255,235,190,0.05) !important;
    border-color: rgba(255,215,145,0.12) !important;
}
.email-otp-digit:focus, .otp-digit-s:focus {
    border-color: #cfa46f !important;
    box-shadow: 0 0 0 3px rgba(207,164,111,.15) !important;
}

    /* â”€â”€ MOBILE RESPONSIVENESS â”€â”€ */
    @media (max-width: 768px) {
        .sp {
            padding-left: 15px !important;
            padding-right: 15px !important;
        }

        .pg-title { font-size: 1.2rem; }
        .pg-sub { font-size: 0.8rem; }

        .stabs-wrapper {
            margin-bottom: 20px;
        }
        .stabs {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            gap: 8px;
            padding: 4px 44px 4px 4px;
            margin-bottom: 0;
        }
        .stabs::-webkit-scrollbar {
            display: none;
        }
        .stab {
            white-space: nowrap;
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .sc-head {
            padding: 16px 20px;
        }
        .sc-icon {
            width: 32px; height: 32px;
            font-size: 0.9rem;
        }
        .sc-title { font-size: 0.9rem; }
        .sc-sub { font-size: 0.75rem; }
        .sc-body { padding: 20px; }

        .sl { font-size: 0.7rem; }
        .si { font-size: 0.85rem; padding: 10px 12px; }

        .sbtn { padding: 10px 20px; font-size: 0.85rem; }

        .trow {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
            padding: 12px 0;
        }
        .tlabel { font-size: 0.85rem; }
        .tsub { font-size: 0.75rem; }

        .stat-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        .stat-box {
            padding: 12px 14px;
        }
        .stat-val { font-size: 1.4rem; }
        .stat-lbl { font-size: 0.65rem; }

        .info-row {
            gap: 10px;
            padding: 10px 0;
        }
        .info-icon {
            width: 30px; height: 30px;
            font-size: 0.8rem;
        }
        .info-lbl { font-size: 0.68rem; }
        .info-val { font-size: 0.85rem; }

        .act-row {
            padding: 12px 20px;
            gap: 10px;
        }
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
            <button class="stab" onclick="switchTab('attendance',this)"><i class="bi bi-bar-chart-fill me-1"></i> Attendance</button>
            <button class="stab" onclick="switchTab('preferences',this)"><i class="bi bi-sliders me-1"></i> Preferences</button>
        </div>
        <button type="button" class="stabs-arrow stabs-arrow-right" id="stabsArrowRight" onclick="scrollStabs('right')" aria-label="Scroll right">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>

    <!-- ── TAB: PROFILE ── -->
    <div id="tab-profile" class="spanel active">

        <!-- Avatar -->
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:#fff5f5;color:#800000;"><i class="bi bi-person-circle"></i></div>
                <div><div class="sc-title">Profile Photo</div><div class="sc-sub">Click the photo to change it</div></div>
            </div>
            <div class="sc-body">
                <div style="display:flex;align-items:center;gap:20px;">
                    <form action="{{ route('profile.image.update') }}" method="POST" enctype="multipart/form-data" id="settingsProfileImageForm">
                        @csrf
                        <input type="file" name="profile_image" id="imgInput" class="d-none" accept="image/*" onchange="handleSettingsAvatarUpload(this)">
                        <div onclick="document.getElementById('imgInput').click()"
                             style="width:80px;height:80px;border-radius:50%;overflow:hidden;border:3px solid #fef3c7;box-shadow:0 4px 16px rgba(128,0,0,.12);cursor:pointer;position:relative;flex-shrink:0;transition:transform .3s;"
                             onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform=''" title="Click to change photo">
                            @if(Auth::user()->profile_image)
                                <img id="settingsAvatarDisplay" src="{{ str_starts_with(Auth::user()->profile_image, 'http') ? Auth::user()->profile_image : asset('storage/'.Auth::user()->profile_image) }}" style="width:100%;height:100%;object-fit:cover;"
                                     onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=800000&color=fff&size=200'">
                            @else
                                <img id="settingsAvatarDisplay" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=800000&color=fff&size=200" style="width:100%;height:100%;object-fit:cover;">
                            @endif
                            <div id="settingsAvatarOverlay" style="position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;flex-direction:column;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;border-radius:50%;color:white;"
                                 onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0">
                                <i class="bi bi-camera-fill" style="font-size:1.2rem;"></i>
                            </div>
                        </div>
                    </form>
                    <div>
                        <div style="font-size:1rem;font-weight:700;color:#f3e7cd;">{{ Auth::user()->name }}</div>
                        <div style="font-size:.8rem;color:#b39b82;margin-top:2px;">{{ Auth::user()->student_number }}</div>
                        <div style="margin-top:8px;display:flex;gap:6px;">
                            <span style="background:#800000;color:white;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:99px;">{{ Auth::user()->course }}</span>
                            <span style="background:#eff6ff;color:#2563eb;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:99px;border:1px solid #bfdbfe;">Year {{ Auth::user()->year_level }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic Info -->
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-mortarboard-fill"></i></div>
                <div><div class="sc-title">Academic Information</div><div class="sc-sub">Contact admin to update enrollment details</div></div>
            </div>
            <div class="sc-body">
                <div class="info-row"><div class="info-icon"><i class="bi bi-person-fill"></i></div><div><div class="info-lbl">Full Name</div><div class="info-val">{{ Auth::user()->name }}</div></div></div>
                <div class="info-row"><div class="info-icon"><i class="bi bi-card-text"></i></div><div><div class="info-lbl">Student ID</div><div class="info-val">{{ Auth::user()->student_number }}</div></div></div>
                <div class="info-row"><div class="info-icon"><i class="bi bi-book-fill"></i></div><div><div class="info-lbl">Course</div><div class="info-val">{{ Auth::user()->course }}</div></div></div>
                <div class="info-row"><div class="info-icon"><i class="bi bi-layers-fill"></i></div><div><div class="info-lbl">Year Level</div><div class="info-val">{{ Auth::user()->year_level }}{{ match((int)Auth::user()->year_level){1=>'st',2=>'nd',3=>'rd',default=>'th'} }} Year</div></div></div>
                <div class="info-row"><div class="info-icon"><i class="bi bi-calendar3"></i></div><div><div class="info-lbl">Semester</div><div class="info-val">{{ Auth::user()->semester }}{{ match((int)Auth::user()->semester){1=>'st',2=>'nd',3=>'rd',default=>'th'} }} Semester</div></div></div>
                <div class="info-row"><div class="info-icon"><i class="bi bi-envelope-fill"></i></div><div><div class="info-lbl">Email</div><div class="info-val">{{ Auth::user()->email }}</div></div></div>
            </div>
        </div>
    </div>

    <!-- ── TAB: SECURITY ── -->
    <div id="tab-security" class="spanel">
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:#fff5f5;color:#800000;"><i class="bi bi-shield-lock-fill"></i></div>
                <div><div class="sc-title">Security & Authentication</div><div class="sc-sub">Manage your email, password, biometrics, and recovery keys</div></div>
            </div>
            <div class="sc-body">

                <!-- ── Change Email via OTP to current email ── -->
                <div style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid rgba(255,215,145,0.06);">
                    <div style="font-size:.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Email Address</div>

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
                                <input type="text" class="email-otp-digit" maxlength="1" inputmode="numeric" id="ed{{$j}}" style="width:44px;height:50px;border-radius:10px;border:1.5px solid #e2e8f0;font-size:1.3rem;font-weight:800;text-align:center;color:#1e293b;background:#f8fafc;outline:none;transition:all .2s;">
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
                <div style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid rgba(255,215,145,0.06);">
                    <div style="font-size:.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Password</div>

                    <div id="otpStep1">
                        <p style="font-size:.85rem;color:#b39b82;margin-bottom:12px;">
                            An OTP will be sent to <strong>{{ Auth::user()->email }}</strong> before you can change your password.
                        </p>
                        <button type="button" onclick="requestOtp()" id="sendOtpBtn" class="sbtn" style="background:linear-gradient(135deg,#1e293b,#334155);box-shadow:0 4px 14px rgba(30,41,59,.25);">
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
                                <input type="text" class="otp-digit-s" maxlength="1" inputmode="numeric" id="sd{{$i}}" style="width:44px;height:50px;border-radius:10px;border:1.5px solid #e2e8f0;font-size:1.3rem;font-weight:800;text-align:center;color:#1e293b;background:#f8fafc;outline:none;transition:all .2s;">
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
                <div style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid rgba(255,215,145,0.06);">
                    <div style="font-size:.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Biometric Authentication</div>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:rgba(255,235,190,0.03);padding:16px 20px;border-radius:12px;border:1px solid rgba(255,215,145,0.08);">
                        <div style="display:flex;align-items:center;gap:14px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:rgba(34,197,94,0.12);color:#4ade80;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
                                <i class="bi bi-fingerprint"></i>
                            </div>
                            <div>
                                <div style="font-size:.9rem;font-weight:700;color:#f3e7cd;">Fingerprint / Biometric Login</div>
                                <div style="font-size:.78rem;color:#b39b82;">Use device biometrics (fingerprint / Face ID) for passwordless login and fast QR attendance</div>
                            </div>
                        </div>
                        <button type="button" onclick="switchTab('fingerprint', document.querySelectorAll('.stab')[2])" class="sbtn" style="padding:9px 18px;font-size:.82rem;background:linear-gradient(135deg,#16a34a,#22c55e);box-shadow:0 4px 14px rgba(22,163,74,.25);">
                            <i class="bi bi-fingerprint me-1"></i> Manage Fingerprints
                        </button>
                    </div>
                </div>

                <!-- ── Recovery Codes ── -->
                <div>
                    <div style="font-size:.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Recovery Codes</div>
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
                <div>
                    <div class="sc-title">Fingerprint / Biometric Login</div>
                    <div class="sc-sub">Register device biometrics (Touch ID, Windows Hello, Android Biometrics) for fast login and attendance</div>
                </div>
            </div>
            <div class="sc-body">

                <!-- In-app browser alert -->
                <div id="webauthnUnsupported" style="display:none;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:#f87171;border-radius:14px;padding:16px 20px;font-size:.85rem;margin-bottom:20px;">
                    <div style="display:flex;align-items:flex-start;gap:12px;">
                        <i class="bi bi-exclamation-triangle" style="font-size:1.2rem;flex-shrink:0;margin-top:2px;"></i>
                        <div>
                            <div style="font-weight:700;margin-bottom:4px;">Biometric login not available on this browser</div>
                            <div id="webauthnUnsupportedMsg" style="font-size:.8rem;opacity:.85;line-height:1.5;">
                                You're using an in-app browser that doesn't support fingerprint/biometric login. Please open this page in <strong>Chrome</strong> or <strong>Safari</strong> to register your fingerprint.
                            </div>
                            <a id="openInBrowserBtn" href="#" onclick="openInSystemBrowser()" style="display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:8px 16px;background:rgba(248,113,113,0.15);border:1px solid rgba(248,113,113,0.3);border-radius:8px;color:#fca5a5;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .2s;">
                                <i class="bi bi-box-arrow-up-right"></i> Open in Browser
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Info Cards / How it works -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:12px;margin-bottom:24px;">
                    <div style="background:rgba(255,235,190,0.03);border:1px solid rgba(255,215,145,0.08);border-radius:12px;padding:14px 16px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                            <div style="width:28px;height:28px;border-radius:8px;background:rgba(207,164,111,0.15);color:var(--gold,#cfa46f);display:flex;align-items:center;justify-content:center;font-size:.85rem;"><i class="bi bi-shield-lock"></i></div>
                            <span style="font-size:.85rem;font-weight:700;color:#f3e7cd;">Passwordless Login</span>
                        </div>
                        <p style="font-size:.76rem;color:#b39b82;margin:0;line-height:1.4;">Sign in instantly on this device using your fingerprint or Face ID without remembering passwords.</p>
                    </div>
                    <div style="background:rgba(255,235,190,0.03);border:1px solid rgba(255,215,145,0.08);border-radius:12px;padding:14px 16px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                            <div style="width:28px;height:28px;border-radius:8px;background:rgba(34,197,94,0.15);color:#4ade80;display:flex;align-items:center;justify-content:center;font-size:.85rem;"><i class="bi bi-qr-code-scan"></i></div>
                            <span style="font-size:.85rem;font-weight:700;color:#f3e7cd;">QR Attendance Clock-In</span>
                        </div>
                        <p style="font-size:.76rem;color:#b39b82;margin:0;line-height:1.4;">Verify your identity in seconds when scanning teacher classroom QR codes to record attendance.</p>
                    </div>
                </div>

                <!-- Registered devices -->
                <div style="margin-bottom:24px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <div style="font-size:.75rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;">Registered Biometric Devices</div>
                        <span id="deviceCountBadge" style="font-size:.72rem;background:rgba(207,164,111,0.12);color:var(--gold,#cfa46f);padding:2px 8px;border-radius:99px;border:1px solid rgba(207,164,111,0.2);">Loading...</span>
                    </div>
                    <div id="deviceList">
                        <div style="text-align:center;padding:28px 20px;color:#b39b82;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:12px;border:1px dashed rgba(207,164,111,0.2);" id="noDevices">
                            <i class="bi bi-fingerprint" style="font-size:2.4rem;display:block;margin-bottom:8px;opacity:.35;color:var(--gold,#CFA46F);"></i>
                            <div style="font-weight:600;color:#f3e7cd;margin-bottom:4px;">No fingerprint registered yet</div>
                            <div style="font-size:.78rem;color:#b39b82;">Register this device to enable fast fingerprint sign-in and QR clock-in.</div>
                        </div>
                    </div>
                </div>

                <!-- Register button & Status -->
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <button type="button" onclick="registerFingerprint()" id="registerFpBtn" class="sbtn" style="background:linear-gradient(135deg,#16a34a,#22c55e);box-shadow:0 4px 14px rgba(22,163,74,.25);">
                        <i class="bi bi-fingerprint me-2"></i>Register This Device
                    </button>
                    <span style="font-size:.78rem;color:#b39b82;"><i class="bi bi-shield-check me-1 text-success"></i>FIDO2 / WebAuthn standard security</span>
                </div>
                <div id="fpMessage" style="margin-top:14px;font-size:.82rem;display:none;"></div>
            </div>
        </div>
    </div>

    <!-- ── TAB: ATTENDANCE ── -->
    <div id="tab-attendance" class="spanel">
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-bar-chart-fill"></i></div>
                <div><div class="sc-title">Attendance Overview</div><div class="sc-sub">Your all-time attendance summary</div></div>
            </div>
            <div class="sc-body">
                <div class="stat-grid">
                    <div class="stat-box"><div class="stat-val" style="color:#f3e7cd;">{{ $totalRecords }}</div><div class="stat-lbl">Total</div></div>
                    <div class="stat-box"><div class="stat-val" style="color:#16a34a;">{{ $totalPresent }}</div><div class="stat-lbl">Present</div></div>
                    <div class="stat-box"><div class="stat-val" style="color:#d97706;">{{ $totalLate }}</div><div class="stat-lbl">Late</div></div>
                    <div class="stat-box"><div class="stat-val" style="color:#dc2626;">{{ $totalAbsent }}</div><div class="stat-lbl">Absent</div></div>
                </div>
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:.78rem;font-weight:600;color:#b39b82;">Overall Attendance Rate</span>
                        <span style="font-size:.85rem;font-weight:800;color:{{ $rate>=75?'#16a34a':'#dc2626' }};">{{ $rate }}%</span>
                    </div>
                    <div class="prog-bar">
                        <div class="prog-fill" style="width:{{ $rate }}%;background:{{ $rate>=75?'linear-gradient(90deg,#16a34a,#22c55e)':'linear-gradient(90deg,#dc2626,#ef4444)' }};"></div>
                    </div>
                    <div style="font-size:.72rem;color:#b39b82;margin-top:6px;">
                        {{ $rate>=75 ? 'Great job! Keep it up.' : 'Your attendance is below 75%. Try to attend more classes.' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:#fffbeb;color:#d97706;"><i class="bi bi-clock-history"></i></div>
                <div><div class="sc-title">Recent Activity</div><div class="sc-sub">Your last 5 attendance records</div></div>
            </div>
            <div class="sc-body" style="padding:0;">
                @php $recent = Auth::user()->attendances()->with('subject')->latest('date')->take(5)->get(); @endphp
                @forelse($recent as $r)
                <div class="act-row">
                    <div style="width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                        {{ $r->status=='Present'?'background:#f0fdf4;':($r->status=='Late'?'background:#fffbeb;':'background:#fef2f2;') }}">
                        <i class="bi {{ $r->status=='Present'?'bi-check2-circle text-success':($r->status=='Late'?'bi-clock text-warning':'bi-x-circle text-danger') }}"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:.875rem;font-weight:600;color:#f3e7cd;">{{ $r->subject->name ?? $r->subject_code }}</div>
                        <div style="font-size:.75rem;color:#b39b82;">{{ \Carbon\Carbon::parse($r->date)->format('M d, Y') }}</div>
                    </div>
                    <span style="font-size:.75rem;font-weight:700;padding:4px 12px;border-radius:99px;
                        {{ $r->status=='Present'?'background:#f0fdf4;color:#16a34a;':($r->status=='Late'?'background:#fffbeb;color:#d97706;':'background:#fef2f2;color:#dc2626;') }}">
                        {{ $r->status }}
                    </span>
                </div>
                @empty
                <div style="text-align:center;padding:40px;color:#b39b82;font-size:.875rem;">No records yet.</div>
                @endforelse
                @if($recent->count()>0)
                <div style="padding:14px 24px;">
                    <a href="{{ route('attendance.records') }}" style="font-size:.82rem;font-weight:600;color:#cfa46f;text-decoration:none;">
                        View all records <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ── TAB: PREFERENCES ── -->
    <div id="tab-preferences" class="spanel">
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-sliders"></i></div>
                <div><div class="sc-title">Preferences</div><div class="sc-sub">Customize your portal experience</div></div>
            </div>
            <div class="sc-body">
                <div class="trow">
                    <div><div class="tlabel">System Language</div><div class="tsub">Choose your preferred display language</div></div>
                    <select class="si" style="width:auto;padding:8px 12px;">
                        <option>English</option><option>Filipino</option><option>Bikolano</option>
                    </select>
                </div>
                <form action="{{ route('settings.preferences.update') }}" method="POST">
                    @csrf
                    <div style="margin:16px 0 10px;font-size:.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;">Notifications</div>
                    
                    @php
                        $prefs = Auth::user()->notification_preferences ?? ['in_app' => true, 'email' => true];
                    @endphp

                    <div class="trow">
                        <div><div class="tlabel">In-App Notifications</div><div class="tsub">Receive alerts within the portal</div></div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="prefs[in_app]" value="1" {{ !empty($prefs['in_app']) ? 'checked' : '' }}>
                        </div>
                    </div>
                    
                    <div class="trow">
                        <div><div class="tlabel">Email Notifications</div><div class="tsub">Receive important alerts via email</div></div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="prefs[email]" value="1" {{ !empty($prefs['email']) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="trow">
                        <div>
                            <div class="tlabel d-flex align-items-center gap-2">
                                <span>Web Push Notifications</span>
                                <span class="push-status-badge badge-inactive" style="font-size: 0.72rem; padding: 2px 8px; border-radius: 999px; background: rgba(207,164,111,0.15); color: #cfa46f; border: 1px solid rgba(207,164,111,0.3); font-weight: 700;">Checking...</span>
                            </div>
                            <div class="tsub">Receive instant background alerts on this device for attendance, excuse updates, and announcements.</div>
                            <div class="mt-2">
                                <button type="button" onclick="WebPushManager.sendTest()" class="push-test-btn sbtn" style="display:none; padding: 5px 12px; font-size: 0.75rem; background: rgba(74,222,128,0.12)!important; border-color: rgba(74,222,128,0.3)!important; color: #4ade80!important;">
                                    <i class="bi bi-bell-fill me-1"></i> Send Test Push Alert
                                </button>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input push-toggle-input" type="checkbox" onchange="toggleWebPush(this)">
                        </div>
                    </div>

                    <div class="trow">
                        <div><div class="tlabel">SMS Notifications</div><div class="tsub">Receive important alerts via SMS (charges may apply)</div></div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="prefs[sms]" value="1" {{ !empty($prefs['sms']) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div style="margin:16px 0 10px;font-size:.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;">Display</div>
                    <div class="trow">
                        <div><div class="tlabel">Compact Sidebar</div><div class="tsub">Start with the sidebar collapsed</div></div>
                        <div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" id="compactToggle"></div>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="sbtn"><i class="bi bi-save me-2"></i>Save Preferences</button>
                    </div>
                </form>

                <hr style="border:0; border-top:1px solid rgba(255,255,255,0.08); margin: 28px 0 20px;">

                <!-- App & System Updates -->
                <div style="margin-bottom:10px;font-size:.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;">App & System Updates</div>
                <div class="trow" style="align-items: flex-start; gap: 16px;">
                    <div>
                        <div class="tlabel" style="display:flex;align-items:center;gap:8px;">
                            <i class="bi bi-arrow-repeat" style="color:var(--gold);"></i> Software Updates
                            <span class="badge" style="background:rgba(207,164,111,0.15);color:var(--gold);border:1px solid rgba(207,164,111,0.3);font-size:0.75rem;">v2.1.0</span>
                        </div>
                        <div class="tsub" id="updateStatusText">Check for latest software features, security patches, and offline assets.</div>
                    </div>
                    <div>
                        <button type="button" id="checkUpdateBtn" onclick="checkForAppUpdates()" class="sbtn" style="padding:9px 18px;font-size:0.85rem;white-space:nowrap;">
                            <i class="bi bi-cloud-arrow-down me-1"></i> Check for Updates
                        </button>
                    </div>
                </div>
                <div id="updateFeedbackArea" style="display:none;margin-top:12px;padding:14px 18px;border-radius:12px;font-size:0.85rem;line-height:1.5;"></div>
            </div>
        </div>
    </div>

</div>

<script>
async function checkForAppUpdates() {
    const btn = document.getElementById('checkUpdateBtn');
    const feedback = document.getElementById('updateFeedbackArea');
    const statusText = document.getElementById('updateStatusText');

    if (!btn || !feedback) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width:14px;height:14px;"></span> Checking...';
    feedback.style.display = 'block';
    feedback.style.background = 'rgba(59, 130, 246, 0.1)';
    feedback.style.border = '1px solid rgba(59, 130, 246, 0.3)';
    feedback.style.color = '#93c5fd';
    feedback.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>Connecting to server and checking for updates...';

    if (!navigator.onLine) {
        feedback.style.background = 'rgba(239, 68, 68, 0.1)';
        feedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
        feedback.style.color = '#fca5a5';
        feedback.innerHTML = '<i class="bi bi-wifi-off me-2"></i>You are currently offline. Please connect to the internet to check for updates.';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-arrow-down me-1"></i> Check for Updates';
        return;
    }

    try {
        if ('serviceWorker' in navigator) {
            const reg = await navigator.serviceWorker.getRegistration();
            if (reg) {
                await reg.update();

                if (reg.waiting) {
                    feedback.style.background = 'rgba(34, 197, 94, 0.15)';
                    feedback.style.border = '1px solid rgba(34, 197, 94, 0.3)';
                    feedback.style.color = '#86efac';
                    feedback.innerHTML = `
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div><i class="bi bi-stars me-2"></i><strong>New update is ready to install!</strong></div>
                            <button type="button" class="btn btn-sm btn-success fw-bold" onclick="applySwUpdate()" style="border-radius:8px; padding:6px 14px;">Update & Refresh</button>
                        </div>
                    `;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Ready';
                    return;
                }
            }
        }

        await new Promise(r => setTimeout(r, 800));

        feedback.style.background = 'rgba(16, 185, 129, 0.1)';
        feedback.style.border = '1px solid rgba(16, 185, 129, 0.3)';
        feedback.style.color = '#6ee7b7';
        feedback.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Your application is up to date (v2.1.0). You have the latest version installed.';
        if (statusText) statusText.textContent = 'Last checked: Just now';
    } catch (e) {
        feedback.style.background = 'rgba(239, 68, 68, 0.1)';
        feedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
        feedback.style.color = '#fca5a5';
        feedback.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Unable to complete update check: ' + (e.message || 'Network error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-arrow-down me-1"></i> Check for Updates';
    }
}

function applySwUpdate() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistration().then(reg => {
            if (reg && reg.waiting) {
                reg.waiting.postMessage({ action: 'skipWaiting' });
            } else {
                window.location.reload();
            }
        });
    } else {
        window.location.reload();
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

async function toggleWebPush(input) {
    if (input.checked) {
        const ok = await WebPushManager.subscribe();
        if (!ok) input.checked = false;
    } else {
        await WebPushManager.unsubscribe();
    }
}
function togglePw(id, btn) {
    const i = document.getElementById(id);
    const ic = btn.querySelector('i');
    if (i.type === 'password') { i.type = 'text'; ic.className = 'bi bi-eye'; btn.style.color = '#800000'; }
    else { i.type = 'password'; ic.className = 'bi bi-eye-slash'; btn.style.color = ''; }
}
// Compact sidebar toggle
const ct = document.getElementById('compactToggle');
if (ct) {
    ct.checked = localStorage.getItem('sidebarMini') === 'true';
    ct.addEventListener('change', function() {
        localStorage.setItem('sidebarMini', this.checked);
        location.reload();
    });
}
// Auto-open security tab on validation errors
@if($errors->any()) switchTab('security', document.querySelectorAll('.stab')[1]); @endif

// ── In-app browser detection ──
function isInAppBrowser() {
    var ua = navigator.userAgent || '';
    // Detect Facebook, Messenger, Instagram, LINE, Twitter, Snapchat, etc.
    return /FBAN|FBAV|FB_IAB|FBIOS|Instagram|Line\/|Twitter|Snapchat|MicroMessenger|KAKAOTALK/i.test(ua);
}

function openInSystemBrowser() {
    var url = window.location.href;
    // Android: use intent to open in Chrome
    if (/android/i.test(navigator.userAgent)) {
        window.location.href = 'intent://' + url.replace(/^https?:\/\//, '') + '#Intent;scheme=https;package=com.android.chrome;end';
        // Fallback after a short delay (if intent doesn't work)
        setTimeout(function() { window.open(url, '_system'); }, 500);
    } else {
     // ── WebAuthn Fingerprint Registration ──
async function loadDevices() {
    var inApp = isInAppBrowser();
    const list = document.getElementById('deviceList');
    const badge = document.getElementById('deviceCountBadge');
    const regBtn = document.getElementById('registerFpBtn');
    const unsupported = document.getElementById('webauthnUnsupported');

    if (!window.PublicKeyCredential) {
        if (regBtn) regBtn.style.display = 'none';
        if (badge) { badge.textContent = 'Unsupported'; badge.style.color = '#f87171'; }
        if (unsupported) {
            unsupported.style.display = 'block';
            var msgEl = document.getElementById('webauthnUnsupportedMsg');
            var openBtn = document.getElementById('openInBrowserBtn');
            if (inApp) {
                msgEl.innerHTML = 'You\'re using an in-app browser (like Messenger or Facebook) that doesn\'t support fingerprint login. Tap the button below to open this page in <strong>Chrome</strong> or <strong>Safari</strong>.';
                if (openBtn) openBtn.style.display = 'inline-flex';
            } else {
                msgEl.innerHTML = 'Your browser or device doesn\'t support biometric login. Please try using <strong>Chrome</strong> or <strong>Safari</strong> on a device with a fingerprint sensor or Face ID.';
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
            if (badge) {
                badge.textContent = `${devices.length} Registered`;
                badge.style.color = '#4ade80';
                badge.style.borderColor = 'rgba(74,222,128,0.3)';
                badge.style.background = 'rgba(74,222,128,0.1)';
            }

            const registeredMsg = document.createElement('div');
            registeredMsg.style.cssText = 'padding:14px 18px;color:#4ade80;font-size:.875rem;background:rgba(22,163,74,0.12);border-radius:12px;border:1px solid rgba(22,163,74,0.25);margin-bottom:16px;font-weight:600;display:flex;align-items:center;gap:10px;';
            registeredMsg.innerHTML = '<i class="bi bi-check-circle-fill" style="font-size:1.2rem;color:#22c55e;"></i> <span>Biometric authentication is <strong>active</strong> on your account.</span>';
            list.appendChild(registeredMsg);

            devices.forEach(d => {
                const div = document.createElement('div');
                div.style.cssText = 'display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:12px;background:rgba(255,235,190,0.03);border:1px solid rgba(255,215,145,0.08);margin-bottom:10px;transition:all .2s;';
                div.onmouseover = function() { this.style.borderColor = 'rgba(255,215,145,0.2)'; this.style.background = 'rgba(255,235,190,0.06)'; };
                div.onmouseout = function() { this.style.borderColor = 'rgba(255,215,145,0.08)'; this.style.background = 'rgba(255,235,190,0.03)'; };

                div.innerHTML = `
                    <div style="width:42px;height:42px;border-radius:10px;background:rgba(22,163,74,0.15);border:1px solid rgba(22,163,74,0.3);display:flex;align-items:center;justify-content:center;color:#4ade80;font-size:1.2rem;flex-shrink:0;">
                        <i class="bi bi-fingerprint"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.9rem;font-weight:700;color:#f3e7cd;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${d.name || d.device_name || "Registered Device"}</div>
                        <div style="font-size:.75rem;color:#b39b82;display:flex;align-items:center;gap:8px;margin-top:2px;">
                            <span><i class="bi bi-shield-check text-success me-1"></i>Verified</span>
                            <span>•</span>
                            <span>Added ${new Date(d.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                        </div>
                    </div>
                    <button onclick="removeDevice('${d.credential_id}', this)"
                        style="padding:6px 14px;border-radius:8px;background:rgba(248,113,113,0.1);color:#f87171;border:1px solid rgba(248,113,113,0.25);font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s;"
                        onmouseover="this.style.background='rgba(248,113,113,0.2)'" onmouseout="this.style.background='rgba(248,113,113,0.1)'">
                        <i class="bi bi-trash3 me-1"></i>Remove
                    </button>`;
                list.appendChild(div);
            });
        } else {
            if (badge) {
                badge.textContent = '0 Registered';
                badge.style.color = '#b39b82';
                badge.style.borderColor = 'rgba(207,164,111,0.2)';
                badge.style.background = 'rgba(207,164,111,0.12)';
            }
            const emptyDiv = document.createElement('div');
            emptyDiv.id = 'noDevices';
            emptyDiv.style.cssText = 'text-align:center;padding:28px 20px;color:#b39b82;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:12px;border:1px dashed rgba(207,164,111,0.2);margin-bottom:16px;';
            emptyDiv.innerHTML = `
                <i class="bi bi-fingerprint" style="font-size:2.4rem;display:block;margin-bottom:8px;opacity:.35;color:var(--gold,#CFA46F);"></i>
                <div style="font-weight:600;color:#f3e7cd;margin-bottom:4px;">No fingerprint registered yet</div>
                <div style="font-size:.78rem;color:#b39b82;">Register this device to enable fast fingerprint sign-in and QR clock-in.</div>
            `;
            list.appendChild(emptyDiv);
        }
    } catch(e) {
        console.error('Failed to load devices', e);
    }
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
    } catch(e) {
        console.error(e);
    }
    isFetchingOptions = false;
}

async function registerFingerprint() {
    const btn = document.getElementById('registerFpBtn');
    const msg = document.getElementById('fpMessage');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Waiting for biometric prompt...';
    msg.style.display = 'none';

    try {
        // Step 1: Always fetch fresh registration challenge directly from server
        const optRes = await fetch('{{ route("webauthn.register.options") }}', {
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            }
        });
        const opts = await optRes.json();

        // Decode challenge and userId from base64
        const challenge = base64ToUint8Array(opts.challenge);
        const userId    = base64ToUint8Array(opts.user.id);

        // Sanitize RP configuration (IP addresses must not be sent as rp.id per WebAuthn spec)
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

        // Step 3: encode credential id and attestation object
        var credentialId = bufferToBase64Url(credential.rawId);
        var attestationObject = bufferToBase64Url(credential.response.attestationObject);
        var clientDataJSON = bufferToBase64Url(credential.response.clientDataJSON);

        // Detect device name
        var ua = navigator.userAgent;
        var deviceName = ua.indexOf('iPhone') !== -1 ? 'iPhone' :
                         ua.indexOf('iPad') !== -1 ? 'iPad' :
                         ua.indexOf('Android') !== -1 ? 'Android Mobile' :
                         ua.indexOf('Windows') !== -1 ? 'Windows PC' :
                         ua.indexOf('Mac') !== -1 ? 'Mac Device' : 'Mobile Device';

        // Step 4: save to server
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
                    response: {
                        attestationObject: attestationObject,
                        clientDataJSON: clientDataJSON
                    }
                },
                device_name: deviceName
            })
        });
        const result = await saveRes.json();

        msg.style.display = 'block';
        if (result.success) {
            msg.style.cssText = 'margin-top:12px;font-size:.82rem;display:block;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:10px 14px;border-radius:10px;';
            msg.innerHTML = '<i class="bi bi-check-circle me-2"></i>' + (result.message || 'Fingerprint registered successfully!');
            btn.innerHTML = '<i class="bi bi-check2 me-2"></i>Registered';
            loadDevices();
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            msg.style.cssText = 'margin-top:12px;font-size:.82rem;display:block;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:10px;';
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>' + (result.message || 'Registration failed.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-fingerprint me-2"></i>Register This Device';
        }
    } catch(err) {
        msg.style.display = 'block';
        msg.style.cssText = 'margin-top:12px;font-size:.82rem;display:block;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:10px;';
        if (err.name === 'NotAllowedError') {
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Biometric prompt was cancelled.';
        } else if (err.name === 'InvalidStateError') {
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>This device is already registered.';
        } else if (err.name === 'NotReadableError') {
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Device biometric sensor is unavailable or locked.';
        } else {
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>' + (err.message || 'Registration failed.');
        }
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
    loadDevices();
}

// OTP digit handling in settings
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
    }).then(r => {
        if (!r.ok) {
            return r.text().then(text => { throw new Error('HTTP ' + r.status + ': ' + text.substring(0, 300)); });
        }
        return r.json();
    }).then(data => {
        if (data.success) {
            document.getElementById('otpStep1').style.display = 'none';
            document.getElementById('otpStep2').style.display = 'block';
            if (sDigits[0]) sDigits[0].focus();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Send OTP to Email';
            alert(data.message || 'Failed to send OTP.');
        }
    }).catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Send OTP to Email';
        console.error('OTP fetch error:', err.message);
        alert('Error: ' + err.message);
    });
}

function cancelOtp() {
    document.getElementById('otpStep1').style.display = 'block';
    document.getElementById('otpStep2').style.display = 'none';
    sDigits.forEach(d => d.value = '');
}

// ── Email change via SMS/Email OTP ──
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

function handleSettingsAvatarUpload(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 10 * 1024 * 1024) {
        alert('The selected image is too large (' + (file.size / (1024 * 1024)).toFixed(1) + 'MB). Please choose an image under 10MB.');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        const avatarImg = document.getElementById('settingsAvatarDisplay');
        if (avatarImg) avatarImg.src = e.target.result;
    };
    reader.readAsDataURL(file);
    const overlay = document.getElementById('settingsAvatarOverlay');
    if (overlay) {
        overlay.innerHTML = '<div class="spinner-border spinner-border-sm text-light" role="status" style="width:1.1rem;height:1.1rem;"></div>';
        overlay.style.opacity = '1';
    }
    document.getElementById('settingsProfileImageForm').submit();
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

    // Check if hash or localStorage requested a specific tab (e.g., #tab-fingerprint or #fingerprint)
    const rawHash = window.location.hash.replace('#tab-', '').replace('#', '');
    const storedTab = localStorage.getItem('active_settings_tab');
    const targetTab = rawHash || storedTab;
    if (targetTab) {
        localStorage.removeItem('active_settings_tab');
        const tabBtn = Array.from(document.querySelectorAll('.stab')).find(b => b.getAttribute('onclick')?.includes(`'${targetTab}'`));
        if (tabBtn) {
            switchTab(targetTab, tabBtn);
        }
    }
});
</script>
@endsection