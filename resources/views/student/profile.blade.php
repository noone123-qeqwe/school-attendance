@extends('layouts.app')

@section('page-title', 'My Profile')
@section('content')
<style>
    /* ── PAGE ── */
    .profile-page { max-width: 960px; margin: 0 auto; }

    /* ── COVER + AVATAR HERO ── */
    .profile-hero {
        background: linear-gradient(135deg, rgba(32,20,15,0.9) 0%, rgba(20,10,5,0.95) 100%);
        border: 1px solid rgba(207,164,111,0.25);
        border-radius: 20px;
        height: 160px;
        position: relative;
        overflow: hidden;
        margin-bottom: 70px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .profile-hero::before {
        content: '';
        position: absolute; inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23cfa46f' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .profile-hero-icon {
        position: absolute; right: 32px; bottom: -10px;
        font-size: 8rem; opacity: 0.1; color: var(--gold);
        pointer-events: none;
    }

    /* Avatar */
    .avatar-wrap {
        position: absolute;
        bottom: -56px; left: 36px;
        width: 112px; height: 112px;
        border-radius: 50%;
        border: 4px solid #1a1a2e;
        box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        background: #1a1a2e;
        overflow: hidden;
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .avatar-wrap:hover { transform: scale(1.05); box-shadow: 0 8px 28px rgba(0,0,0,0.5); }
    .avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-edit-overlay {
        position: absolute; inset: 0;
        background: rgba(0,0,0,0.6);
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.2s;
        border-radius: 50%;
        color: white; font-size: 0.65rem; font-weight: 600;
        gap: 3px;
    }
    .avatar-wrap:hover .avatar-edit-overlay { opacity: 1; }

    /* Name + meta beside avatar */
    .profile-meta {
        padding-left: 164px;
        padding-top: 8px;
        min-height: 56px;
        display: flex; align-items: flex-end; justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
    }
    .profile-name { font-size: 1.4rem; font-weight: 800; color: #f3e7cd; letter-spacing: -0.3px; }
    .profile-id {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 0.8rem; color: #b39b82; font-weight: 600;
        background: rgba(255,255,255,0.05); padding: 4px 12px; border-radius: 99px;
        border: 1px solid rgba(255,255,255,0.08); margin-top: 4px;
    }

    /* ── CARDS ── */
    .info-card {
        background: rgba(255,255,255,0.02);
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.06);
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        overflow: hidden;
        transition: box-shadow 0.25s;
    }
    .info-card:hover { box-shadow: 0 12px 40px rgba(0,0,0,0.3); }

    .info-card-header {
        padding: 18px 24px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        background: rgba(0,0,0,0.2);
        display: flex; align-items: center; gap: 10px;
    }
    .info-card-header-icon {
        width: 34px; height: 34px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.95rem;
        background: rgba(207,164,111,0.15); color: var(--gold);
    }
    .info-card-title { font-size: 0.9rem; font-weight: 700; color: #f3e7cd; }
    .info-card-body { padding: 20px 24px; }

    /* Info rows */
    .info-row {
        display: flex; align-items: flex-start;
        padding: 14px 0;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        gap: 16px;
    }
    .info-row:last-child { border-bottom: none; padding-bottom: 0; }
    .info-row-icon {
        width: 36px; height: 36px; border-radius: 10px;
        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
        display: flex; align-items: center; justify-content: center;
        color: #b39b82; font-size: 0.9rem; flex-shrink: 0;
        margin-top: 2px;
    }
    .info-row-label { font-size: 0.72rem; font-weight: 600; color: #b39b82; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
    .info-row-value { font-size: 0.95rem; font-weight: 600; color: #f3e7cd; }

    /* Stat chips */
    .stat-chips { display: flex; gap: 10px; flex-wrap: wrap; }
    .stat-chip {
        flex: 1; min-width: 80px;
        background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
        border-radius: 12px; padding: 14px 16px;
        text-align: center;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-chip:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.2); }
    .stat-chip-value { font-size: 1.5rem; font-weight: 800; color: var(--gold); line-height: 1; }
    .stat-chip-label { font-size: 0.7rem; font-weight: 600; color: #b39b82; text-transform: uppercase; letter-spacing: 0.4px; margin-top: 4px; }

    /* Flash */
    .flash-success {
        background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80;
        border-radius: 12px; padding: 12px 16px; font-size: 0.875rem; font-weight: 600;
        margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
    }
    .flash-err {
        background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171;
        border-radius: 12px; padding: 12px 16px; font-size: 0.875rem; font-weight: 600;
        margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
    }

    /* Course badge */
    .course-badge {
        display: inline-block;
        background: rgba(207,164,111,0.15); border: 1px solid rgba(207,164,111,0.3);
        color: var(--gold); font-size: 0.75rem; font-weight: 700;
        padding: 4px 14px; border-radius: 99px;
        letter-spacing: 0.5px;
    }

    /* Year badge */
    .year-badge {
        display: inline-block;
        background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
        color: #e5e5e5; font-size: 0.75rem; font-weight: 700;
        padding: 4px 14px; border-radius: 99px;
    }
    
    .student-avatar-container {
        position: absolute;
        bottom: -56px; left: 36px;
        z-index: 10;
    }
    
    @media (max-width: 768px) {
        .student-avatar-container { left: 50%; transform: translateX(-50%); }
        .profile-meta { padding-left: 0; padding-top: 100px; justify-content: center; text-align: center; }
        .profile-id { justify-content: center; margin: 4px auto 0; }
        .info-row { flex-direction: column; gap: 8px; align-items: flex-start; }
    }
</style>

<div class="profile-page">

    @if(session('success'))
    <div class="flash-success">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="flash-err">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <span>{{ $errors->first() }}</span>
    </div>
    @endif

    @include('partials.student-page-header', ['title' => 'My profile', 'description' => 'Your student information, account details, and attendance overview.'])
    <section class="student-profile-summary" aria-label="Student profile">
        <div class="student-avatar-container"><x-profile-photo-manager :user="Auth::user()" :size="88" avatar-id="studentAvatarDisplay" /></div>
        <div class="profile-meta">
            <div>
                <div class="profile-name">{{ Auth::user()->name }}</div>
                <div class="profile-id">Student ID: {{ Auth::user()->student_number ?: 'Not Assigned' }}</div>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <span class="course-badge">{{ Auth::user()->course }}</span>
                <span class="year-badge">Year {{ Auth::user()->year_level }}</span>
                <a href="{{ route('settings') }}" class="student-page-link"><i class="bi bi-pencil" aria-hidden="true"></i>Edit Profile</a>
            </div>
        </div>
    </section>

    <!-- CONTENT GRID -->
    <div class="row g-3 mt-1">

        <!-- LEFT: Academic Info & Quick Actions -->
        <div class="col-lg-7 d-flex flex-column gap-3">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-header-icon">
                        <i class="bi bi-mortarboard-fill" aria-hidden="true"></i>
                    </div>
                    <div class="info-card-title">Academic Information</div>
                </div>
                <div class="info-card-body">

                    <div class="info-row">
                        <div class="info-row-icon"><i class="bi bi-person-fill" aria-hidden="true"></i></div>
                        <div>
                            <div class="info-row-label">Full Name</div>
                            <div class="info-row-value">{{ Auth::user()->name }}</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-row-icon"><i class="bi bi-card-text" aria-hidden="true"></i></div>
                        <div class="flex-grow-1">
                            <div class="info-row-label">Student ID Number</div>
                            <div class="info-row-value d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span style="font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 1.02rem; letter-spacing: 0.04em; color: #fcf9fa; font-weight: 700;">
                                    {{ Auth::user()->student_number ?: 'Not Assigned' }}
                                </span>
                                @if(Auth::user()->student_number)
                                    <button type="button" class="btn btn-sm py-1 px-2 copy-student-id-btn" onclick="navigator.clipboard.writeText('{{ Auth::user()->student_number }}'); this.innerHTML='<i class=\'bi bi-check2 me-1\' aria-hidden=\'true\'></i>Copied!'; setTimeout(() => { this.innerHTML='<i class=\'bi bi-clipboard me-1\' aria-hidden=\'true\'></i>Copy'; }, 1500);" style="font-size:0.75rem; background: rgba(255,255,255,0.06); border:1px solid rgba(236,224,228,0.2); color:#fcf9fa; border-radius: 8px;">
                                        <i class="bi bi-clipboard me-1" aria-hidden="true"></i>Copy
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-row-icon"><i class="bi bi-book-fill" aria-hidden="true"></i></div>
                        <div>
                            <div class="info-row-label">Course</div>
                            <div class="info-row-value">{{ Auth::user()->course }}</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-row-icon"><i class="bi bi-layers-fill" aria-hidden="true"></i></div>
                        <div>
                            <div class="info-row-label">Year Level</div>
                            <div class="info-row-value">{{ Auth::user()->year_level }}{{ match((int)Auth::user()->year_level) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }} Year</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-row-icon"><i class="bi bi-calendar3" aria-hidden="true"></i></div>
                        <div>
                            <div class="info-row-label">Semester</div>
                            <div class="info-row-value">{{ Auth::user()->semester }}{{ match((int)Auth::user()->semester){1=>'st',2=>'nd',3=>'rd',default=>'th'} }} Semester</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-row-icon"><i class="bi bi-envelope-fill" aria-hidden="true"></i></div>
                        <div>
                            <div class="info-row-label">Email Address</div>
                            <div class="info-row-value">{{ Auth::user()->email }}</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Quick Actions -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-header-icon">
                        <i class="bi bi-lightning-fill" aria-hidden="true"></i>
                    </div>
                    <div class="info-card-title">Quick Actions</div>
                </div>
                <div class="info-card-body" style="padding-top:16px;padding-bottom:16px;">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <a href="{{ route('home') }}" class="sd-profile-quick-link">
                                <span class="sd-profile-quick-icon"><i class="bi bi-grid-fill" aria-hidden="true"></i></span>
                                <span class="fw-semibold" style="font-size:0.875rem;">Go to Dashboard</span>
                                <i class="bi bi-chevron-right ms-auto" style="color:#baa3ab;font-size:0.75rem;" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('student.schedule') }}" class="sd-profile-quick-link">
                                <span class="sd-profile-quick-icon is-sky"><i class="bi bi-calendar2-week-fill" aria-hidden="true"></i></span>
                                <span class="fw-semibold" style="font-size:0.875rem;">My Schedule</span>
                                <i class="bi bi-chevron-right ms-auto" style="color:#baa3ab;font-size:0.75rem;" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('student.calendar') }}#attendance-records" class="sd-profile-quick-link">
                                <span class="sd-profile-quick-icon is-emerald"><i class="bi bi-journal-check" aria-hidden="true"></i></span>
                                <span class="fw-semibold" style="font-size:0.875rem;">Attendance Log</span>
                                <i class="bi bi-chevron-right ms-auto" style="color:#baa3ab;font-size:0.75rem;" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('settings') }}" class="sd-profile-quick-link">
                                <span class="sd-profile-quick-icon"><i class="bi bi-gear-fill" aria-hidden="true"></i></span>
                                <span class="fw-semibold" style="font-size:0.875rem;">Account Settings</span>
                                <i class="bi bi-chevron-right ms-auto" style="color:#baa3ab;font-size:0.75rem;" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Stats + Quick Actions -->
        <div class="col-lg-5 d-flex flex-column gap-3">

            <!-- Attendance Stats -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-header-icon" style="background:rgba(74,222,128,0.14);color:#4ade80;border:1px solid rgba(74,222,128,0.28);">
                        <i class="bi bi-bar-chart-fill"></i>
                    </div>
                    <div class="info-card-title">Attendance Summary</div>
                </div>
                <div class="info-card-body">
                    @php
                        $allRecords   = Auth::user()->attendances ?? collect();
                        $totalPresent = $allRecords->where('status', 'Present')->where('excused', false)->count();
                        $totalLate    = $allRecords->where('status', 'Late')->where('excused', false)->count();
                        $totalAbsent  = $allRecords->where('status', 'Absent')->where('excused', false)->count();
                        $totalExcused = $allRecords->where('excused', true)->count();
                        $totalAll     = $allRecords->count();
                    @endphp
                    <div class="stat-chips mb-3">
                        <div class="stat-chip">
                            <div class="stat-chip-value" style="color:#16a34a;">{{ $totalPresent }}</div>
                            <div class="stat-chip-label">Present</div>
                        </div>
                        <div class="stat-chip">
                            <div class="stat-chip-value" style="color:#d97706;">{{ $totalLate }}</div>
                            <div class="stat-chip-label">Late</div>
                        </div>
                        <div class="stat-chip">
                            <div class="stat-chip-value" style="color:#dc2626;">{{ $totalAbsent }}</div>
                            <div class="stat-chip-label">Absent</div>
                        </div>
                        <div class="stat-chip">
                            <div class="stat-chip-value" style="color:#a78bfa;">{{ $totalExcused }}</div>
                            <div class="stat-chip-label">Excused</div>
                        </div>
                        <div class="stat-chip">
                            <div class="stat-chip-value">{{ $totalAll }}</div>
                            <div class="stat-chip-label">Total</div>
                        </div>
                    </div>

                    @if($totalAll > 0)
                    @php $rate = round((($totalPresent + $totalLate) / $totalAll) * 100); @endphp
                    <div style="margin-top:4px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-size:0.78rem;font-weight:600;color:#9E8990;">Attendance Rate</span>
                            <span style="font-size:0.84rem;font-weight:700;color:{{ $rate >= 75 ? '#34D399' : '#FB7185' }};">{{ $rate }}%</span>
                        </div>
                        <div style="height:6px;background:rgba(250,247,248,0.08);border-radius:99px;overflow:hidden;">
                            <div style="height:100%;width:{{ $rate }}%;background:{{ $rate >= 75 ? '#34D399' : '#FB7185' }};border-radius:99px;transition:width 0.6s ease;"></div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Bound Attendance Device & Security Card -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-header-icon" style="background:rgba(59,130,246,0.15);color:#60a5fa;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div class="info-card-title">Bound Attendance Device</div>
                </div>
                <div class="info-card-body">
                    @php
                        $binding = $user->deviceBinding;
                    @endphp
                    @if($binding)
                    <div style="background:rgba(15,4,7,0.52);border:1px solid rgba(236,224,228,0.16);border-radius:14px;padding:16px;margin-bottom:14px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:10px;flex-wrap:wrap;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:40px;height:40px;border-radius:10px;background:rgba(52,211,153,0.14);color:#34d399;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">
                                    <i class="bi {{ $binding->getDeviceIcon() }}" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <div style="font-weight:700;font-size:0.95rem;color:#fcf9fa;">{{ $binding->device_name ?: 'Registered Mobile Device' }}</div>
                                    <div style="font-size:0.75rem;color:#baa3ab;">Bound on {{ $binding->created_at?->format('M d, Y') ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <x-badge variant="success" size="sm" :dot="true">Verified</x-badge>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;font-size:0.78rem;padding-top:10px;border-top:1px solid rgba(236,224,228,0.09);">
                            <div style="min-width:0;overflow-wrap:anywhere;">
                                <span style="color:#baa3ab;">Last Active:</span>
                                <span style="color:#fcf9fa;font-weight:600;margin-left:4px;">{{ $binding->last_seen_at?->diffForHumans() ?? 'Just now' }}</span>
                            </div>
                            <div style="min-width:0;overflow-wrap:anywhere;">
                                <span style="color:#baa3ab;">IP Address:</span>
                                <span style="color:#fcf9fa;font-weight:600;margin-left:4px;">{{ $binding->ip_address ?: 'Protected' }}</span>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:8px;background:rgba(138,36,57,0.22);border:1px solid rgba(228,143,160,0.26);border-radius:12px;padding:10px 14px;font-size:0.78rem;color:#e3d5d9;line-height:1.45;">
                        <i class="bi bi-info-circle-fill" style="color:#e48fa0;margin-top:2px;flex-shrink:0;" aria-hidden="true"></i>
                        <div>
                            Anti-proxy protection active. Your student account is strictly bound to this personal device for attendance clock-ins.
                        </div>
                    </div>
                    @else
                    <div style="text-align:center;padding:18px 14px;background:rgba(15,4,7,0.45);border:1px dashed rgba(236,224,228,0.2);border-radius:14px;color:#baa3ab;font-size:0.85rem;">
                        <i class="bi bi-phone" style="font-size:2rem;opacity:0.5;display:block;margin-bottom:8px;color:#e48fa0;" aria-hidden="true"></i>
                        <div style="font-weight:700;color:#fcf9fa;margin-bottom:4px;">No Device Bound Yet</div>
                        <div style="font-size:0.78rem;line-height:1.45;">Your personal device will be automatically and securely bound to your student account when you sign in or record attendance.</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Device passkeys -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-header-icon" style="background:rgba(22,163,74,0.15);color:#4ade80;">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div class="info-card-title">Device passkeys</div>
                </div>
                <div class="info-card-body">
                    <div id="webauthnUnsupported" role="status" style="display:none;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:#f87171;border-radius:12px;padding:14px 18px;font-size:.85rem;margin-bottom:14px;">
                        <div style="display:flex;align-items:flex-start;gap:10px;">
                            <i class="bi bi-exclamation-triangle" style="font-size:1.1rem;flex-shrink:0;margin-top:2px;"></i>
                            <div id="webauthnUnsupportedMsg" style="font-size:.8rem;line-height:1.4;">
                                Passkey setup is unavailable on this device.
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom:16px;">
                        <div style="font-size:.72rem;font-weight:700;color:#9E8990;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Registered devices</div>
                        <div id="deviceList">
                            <div style="text-align:center;padding:16px;color:#9E8990;font-size:.84rem;background:#0B0406;border-radius:10px;border:1px dashed rgba(250,247,248,0.14);" id="noDevices">
                                <i class="bi bi-shield-lock" style="font-size:1.6rem;display:block;margin-bottom:6px;opacity:.6;color:#E898A8;" aria-hidden="true"></i>
                                No passkeys registered yet.
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="registerDevicePasskey()" id="registerFpBtn" disabled class="btn w-100" style="background:#FAF7F8;color:#14080C;font-weight:700;min-height:44px;padding:10px 18px;border-radius:10px;border:1px solid #FAF7F8;">
                        <i class="bi bi-shield-lock me-2" aria-hidden="true"></i>Set up a passkey
                    </button>
                    <div id="fpMessage" style="margin-top:12px;font-size:.82rem;display:none;"></div>
                    <div class="mt-3 pt-3 d-flex align-items-center justify-content-between gap-2 flex-wrap" style="border-top: 1px solid rgba(250,247,248,0.08);">
                        <a href="{{ route('settings.biometrics.face') }}" style="color:#E898A8;font-size:0.8rem;font-weight:700;text-decoration:none;">
                            <i class="bi bi-camera-video me-1" aria-hidden="true"></i>Face Recognition Setup
                        </a>
                        <a href="{{ route('settings') }}#tab-biometrics" style="color:#D4C6CA;font-size:0.8rem;font-weight:600;text-decoration:none;">
                            Security Hub <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- APK Download Card removed - users can install via PWA prompt -->

        </div>
    </div>
</div>

@include('partials.profile-device-passkey-capability')
<script @cspNonce>
async function loadDevices() {
    try {
        const res = await fetch('{{ route("webauthn.devices") }}');
        const devices = await res.json();
        const list = document.getElementById('deviceList');
        const noDevices = document.getElementById('noDevices');
        if (!list) return;

        if (devices.length > 0) {
            if (noDevices) noDevices.style.display = 'none';
            list.innerHTML = '';
            
            const successNote = document.createElement('div');
            successNote.style.cssText = 'text-align:center;padding:12px;color:#4ade80;font-size:.85rem;background:rgba(22,163,74,0.1);border-radius:10px;border:1px solid rgba(22,163,74,0.2);margin-bottom:12px;font-weight:600;';
            successNote.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Credentials linked to this account.';
            list.appendChild(successNote);

            devices.forEach(d => {
                const div = document.createElement('div');
                div.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;margin-bottom:8px;';
                div.innerHTML = `
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:8px;background:rgba(74,222,128,0.1);display:flex;align-items:center;justify-content:center;color:#4ade80;">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <div>
                            <div style="font-size:.85rem;font-weight:600;color:#FAF7F8;">${d.name || d.device_name || "My Device"}</div>
                            <div style="font-size:.72rem;color:#9E8990;">Registered ${new Date(d.created_at).toLocaleDateString()}</div>
                        </div>
                    </div>
                    <button type="button" onclick="removeDevice('${d.credential_id}', this)"
                        style="min-height:36px;padding:4px 12px;border-radius:7px;background:rgba(251,113,133,0.12);color:#FB7185;border:1px solid rgba(251,113,133,0.28);font-size:.75rem;font-weight:600;cursor:pointer;">
                        Remove
                    </button>`;
                list.appendChild(div);
            });
        } else {
            if (noDevices) noDevices.style.display = 'block';
        }
    } catch(e) {
        console.error('Error loading devices:', e);
    }
}

async function removeDevice(id, btn) {
    if (!confirm('Remove this device credential?')) return;
    try {
        await fetch('{{ route("webauthn.remove") }}', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ credential_id: id })
        });
        loadDevices();
    } catch(e) {}
}



document.addEventListener('DOMContentLoaded', () => {
    loadDevices();

    // Hide the APK download card if the app is already installed (running as PWA/standalone)
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true
        || document.referrer.includes('android-app://');

    if (isStandalone) {
        const apkCard = document.getElementById('apkDownloadCard');
        if (apkCard) apkCard.style.display = 'none';
    }
});
</script>
@endsection
