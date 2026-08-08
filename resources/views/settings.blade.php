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
.stabs{display:flex;gap:0;margin-bottom:24px;border-bottom:2px solid rgba(255,215,145,0.08);}
.stab{padding:10px 20px;font-size:.875rem;font-weight:600;color:#8f826f;cursor:pointer;border:none;background:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s;}
.stab.active{color:#cfa46f;border-bottom-color:#cfa46f;}
.stab:hover{color:#f3e7cd;}
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

        .stabs {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            gap: 8px;
            padding-bottom: 2px;
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
    <div class="stabs">
        <button class="stab active" onclick="switchTab('profile',this)">Profile</button>
        <button class="stab" onclick="switchTab('security',this)">Security</button>
        <button class="stab" onclick="switchTab('attendance',this)">Attendance</button>
        <button class="stab" onclick="switchTab('fingerprint',this)">Fingerprint</button>
        <button class="stab" onclick="switchTab('preferences',this)">Preferences</button>
    </div>

    <!-- â”€â”€ TAB: PROFILE â”€â”€ -->
    <div id="tab-profile" class="spanel active">

        <!-- Avatar -->
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:#fff5f5;color:#800000;"><i class="bi bi-person-circle"></i></div>
                <div><div class="sc-title">Profile Photo</div><div class="sc-sub">Click the photo to change it</div></div>
            </div>
            <div class="sc-body">
                <div style="display:flex;align-items:center;gap:20px;">
                    <form action="{{ route('profile.image.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="profile_image" id="imgInput" class="d-none" accept="image/*" onchange="this.form.submit()">
                        <div onclick="document.getElementById('imgInput').click()"
                             style="width:80px;height:80px;border-radius:50%;overflow:hidden;border:3px solid #fef3c7;box-shadow:0 4px 16px rgba(128,0,0,.12);cursor:pointer;position:relative;flex-shrink:0;transition:transform .3s;"
                             onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform=''">
                            @if(Auth::user()->profile_image)
                                <img src="{{ str_starts_with(Auth::user()->profile_image, 'http') ? Auth::user()->profile_image : asset('storage/'.Auth::user()->profile_image) }}" style="width:100%;height:100%;object-fit:cover;"
                                     onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=800000&color=fff&size=200'">
                            @else
                                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=800000&color=fff&size=200" style="width:100%;height:100%;object-fit:cover;">
                            @endif
                            <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;border-radius:50%;"
                                 onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0">
                                <i class="bi bi-camera-fill" style="color:white;font-size:1.2rem;"></i>
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

    <!-- â”€â”€ TAB: SECURITY â”€â”€ -->
    <div id="tab-security" class="spanel">
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:#fff5f5;color:#800000;"><i class="bi bi-shield-lock-fill"></i></div>
                <div><div class="sc-title">Security</div><div class="sc-sub">Change your email address or password</div></div>
            </div>
            <div class="sc-body">

                <!-- â”€â”€ Change Email via OTP to current email â”€â”€ -->
                <div style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid #f1f5f9;">
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

                <!-- â”€â”€ Change Password via OTP â”€â”€ -->
                <div>
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

            </div>
        </div>

    </div>

    <!-- â”€â”€ TAB: ATTENDANCE â”€â”€ -->
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

    <!-- â”€â”€ TAB: FINGERPRINT â”€â”€ -->
    <div id="tab-fingerprint" class="spanel">
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-fingerprint"></i></div>
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
                        <div style="text-align:center;padding:20px;color:#b39b82;font-size:.85rem;" id="noDevices">
                            <i class="bi bi-fingerprint" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3;"></i>
                            No fingerprint registered yet.
                        </div>
                    </div>
                </div>

                <!-- Register button -->
                <button type="button" onclick="registerFingerprint()" id="registerFpBtn" class="sbtn" style="background:linear-gradient(135deg,#16a34a,#22c55e);box-shadow:0 4px 14px rgba(22,163,74,.25);">
                    <i class="bi bi-fingerprint me-2"></i>Register This Device
                </button>
                <div id="fpMessage" style="margin-top:12px;font-size:.82rem;display:none;"></div>
            </div>
        </div>
    </div>

    <!-- â”€â”€ TAB: PREFERENCES â”€â”€ -->
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
            </div>
        </div>
    </div>

</div>

<script>
function switchTab(id, btn) {
    document.querySelectorAll('.spanel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.stab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
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
        // iOS and others: window.open usually opens Safari
        window.open(url, '_blank');
    }
}

// ── WebAuthn Fingerprint Registration ──
async function loadDevices() {
    var inApp = isInAppBrowser();

    if (!window.PublicKeyCredential) {
        document.getElementById('registerFpBtn').style.display = 'none';
        var unsupported = document.getElementById('webauthnUnsupported');
        if (unsupported) {
            unsupported.style.display = 'block';
            // Customize message based on whether it's an in-app browser or truly unsupported
            var msgEl = document.getElementById('webauthnUnsupportedMsg');
            var openBtn = document.getElementById('openInBrowserBtn');
            if (inApp) {
                msgEl.innerHTML = 'You\'re using an in-app browser (like Messenger or Facebook) that doesn\'t support fingerprint login. Tap the button below to open this page in <strong>Chrome</strong> or <strong>Safari</strong>.';
                openBtn.style.display = 'inline-flex';
            } else {
                msgEl.innerHTML = 'Your browser or device doesn\'t support biometric login. Please try using <strong>Chrome</strong> or <strong>Safari</strong> on a device with a fingerprint sensor or Face ID.';
                openBtn.style.display = 'none';
            }
        }
        return;
    }
    try {
        const res = await fetch('{{ route("webauthn.devices") }}');
        const devices = await res.json();
        const list = document.getElementById('deviceList');
        const noDevices = document.getElementById('noDevices');
        if (devices.length > 0) {
            noDevices.style.display = 'none';
            devices.forEach(d => {
                const div = document.createElement('div');
                div.style.cssText = 'display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid rgba(255,215,145,0.06);';
                div.innerHTML = `
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(74,222,128,0.1);display:flex;align-items:center;justify-content:center;color:#4ade80;flex-shrink:0;">
                        <i class="bi bi-fingerprint"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:.875rem;font-weight:600;color:#f3e7cd;">${d.name || d.device_name || "My Device"}</div>
                        <div style="font-size:.72rem;color:#b39b82;">Registered ${new Date(d.created_at).toLocaleDateString()}</div>
                    </div>
                    <button onclick="removeDevice('${d.credential_id}', this)"
                        style="padding:5px 12px;border-radius:7px;background:rgba(248,113,113,0.1);color:#f87171;border:1px solid rgba(248,113,113,0.2);font-size:.75rem;font-weight:600;cursor:pointer;">
                        Remove
                    </button>`;
                list.appendChild(div);
            });
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
    } catch(e) {
        console.error(e);
    }
    isFetchingOptions = false;
}

async function registerFingerprint() {
    const btn = document.getElementById('registerFpBtn');
    const msg = document.getElementById('fpMessage');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Waiting for fingerprint...';
    msg.style.display = 'none';

    try {
        let opts = prefetchOptions;
        if (!opts) {
            // Step 1: get challenge from server (fallback)
            const optRes = await fetch('{{ route("webauthn.register.options") }}', {
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                    'Accept': 'application/json',
                    'ngrok-skip-browser-warning': 'true'
                }
            });
            opts = await optRes.json();
        }
        
        // Reset prefetch for next time
        prefetchOptions = null;

        // Decode challenge and userId from base64
        const challenge = base64ToUint8Array(opts.challenge);
        const userId    = base64ToUint8Array(opts.user.id);

        // Step 2: prompt device biometric
        const rpId = opts.rp?.id || window.location.hostname;
        
        const timeoutPromise = new Promise((_, reject) => {
            const err = new Error('Biometric prompt timed out. Please try again.');
            err.name = 'TimeoutError';
            setTimeout(() => reject(err), 60000);
        });

        const createPromise = navigator.credentials.create({
            publicKey: {
                challenge: challenge,
                rp: { ...opts.rp, id: rpId },
                user: { id: userId, name: opts.user.name, displayName: opts.user.displayName },
                pubKeyCredParams: opts.pubKeyCredParams,
                authenticatorSelection: opts.authenticatorSelection,
                timeout: opts.timeout,
                attestation: opts.attestation
            }
        });

        const credential = await Promise.race([createPromise, timeoutPromise]);

        // Step 3: encode credential id and attestation object
        var rawId = new Uint8Array(credential.rawId);
        var credentialId = bufferToBase64Url(credential.rawId);

        var attestationObject = bufferToBase64Url(credential.response.attestationObject);
        var clientDataJSON = bufferToBase64Url(credential.response.clientDataJSON);

        // Detect device name
        var ua = navigator.userAgent;
        var deviceName = ua.indexOf('iPhone') !== -1 ? 'iPhone' :
                         ua.indexOf('iPad') !== -1 ? 'iPad' :
                         ua.indexOf('Android') !== -1 ? 'Android Device' :
                         ua.indexOf('Windows') !== -1 ? 'Windows Device' : 'My Device';

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
            msg.innerHTML = '<i class="bi bi-check-circle me-2"></i>' + result.message;
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            msg.style.cssText = 'margin-top:12px;font-size:.82rem;display:block;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:10px;';
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>' + result.message;
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-fingerprint me-2"></i>Register This Device';
            prefetchWebAuthn();
        }
    } catch(err) {
        msg.style.display = 'block';
        msg.style.cssText = 'margin-top:12px;font-size:.82rem;display:block;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:10px;';
        if (err.name === 'NotAllowedError') {
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Fingerprint cancelled or not allowed.';
        } else if (err.name === 'InvalidStateError') {
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>This device is already registered.';
        } else if (err.name === 'NotReadableError') {
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Fingerprint cancelled or device is not configured for biometric login.';
        } else {
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>' + err.name + ': ' + err.message;
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-fingerprint me-2"></i>Register This Device';
        
        // Prefetch a new challenge for next attempt
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

// Load devices when fingerprint tab is opened
document.querySelectorAll('.stab').forEach(btn => {
    btn.addEventListener('click', () => {
        if (btn.textContent.trim() === 'Fingerprint') {
            loadDevices();
            prefetchWebAuthn();
        }
    });
});

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

// â”€â”€ Email change via SMS OTP â”€â”€
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
</script>
@endsection