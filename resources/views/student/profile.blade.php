@extends('layouts.app')

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
    
    @media (max-width: 768px) {
        .profile-meta { padding-left: 0; padding-top: 60px; justify-content: center; text-align: center; }
        .avatar-wrap { left: 50%; transform: translateX(-50%); }
        .avatar-wrap:hover { transform: translateX(-50%) scale(1.05); }
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

    <!-- HERO COVER -->
    <div class="position-relative mb-4">
        <div class="profile-hero">
            <i class="bi bi-mortarboard-fill profile-hero-icon"></i>
        </div>

        <!-- Avatar -->
        <form action="{{ route('profile.image.update') }}" method="POST" enctype="multipart/form-data" id="profileImageForm">
            @csrf
            <input type="file" name="profile_image" id="profile_image_input" class="d-none" accept="image/*" onchange="this.form.submit()">
            <div class="avatar-wrap" onclick="document.getElementById('profile_image_input').click()">
                @if(Auth::user()->profile_image)
                    <img src="{{ str_starts_with(Auth::user()->profile_image, 'http') ? Auth::user()->profile_image : asset('storage/'.Auth::user()->profile_image) }}"
                         onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=800000&color=fff&size=200'">
                @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=800000&color=fff&size=200">
                @endif
                <div class="avatar-edit-overlay">
                    <i class="bi bi-camera-fill" style="font-size:1.2rem;"></i>
                    <span>Change</span>
                </div>
            </div>
        </form>

        <!-- Name + meta -->
        <div class="profile-meta">
            <div>
                <div class="profile-name">{{ Auth::user()->name }}</div>
                <div class="profile-id">
                    <i class="bi bi-person-badge"></i>
                    {{ Auth::user()->student_number }}
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center pb-1">
                <span class="course-badge">{{ Auth::user()->course }}</span>
                <span class="year-badge">Year {{ Auth::user()->year_level }}</span>
            </div>
        </div>
    </div>

    <!-- CONTENT GRID -->
    <div class="row g-3 mt-1">

        <!-- LEFT: Academic Info -->
        <div class="col-lg-7">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-header-icon" style="background:#fff5f5;color:#800000;">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div class="info-card-title">Academic Information</div>
                </div>
                <div class="info-card-body">

                    <div class="info-row">
                        <div class="info-row-icon"><i class="bi bi-person-fill"></i></div>
                        <div>
                            <div class="info-row-label">Full Name</div>
                            <div class="info-row-value">{{ Auth::user()->name }}</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-row-icon"><i class="bi bi-card-text"></i></div>
                        <div>
                            <div class="info-row-label">Student ID</div>
                            <div class="info-row-value">{{ Auth::user()->student_number }}</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-row-icon"><i class="bi bi-book-fill"></i></div>
                        <div>
                            <div class="info-row-label">Course</div>
                            <div class="info-row-value">{{ Auth::user()->course }}</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-row-icon"><i class="bi bi-layers-fill"></i></div>
                        <div>
                            <div class="info-row-label">Year Level</div>
                            <div class="info-row-value">{{ Auth::user()->year_level }}{{ match((int)Auth::user()->year_level) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }} Year</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-row-icon"><i class="bi bi-calendar3"></i></div>
                        <div>
                            <div class="info-row-label">Semester</div>
                            <div class="info-row-value">{{ Auth::user()->semester }}{{ match((int)Auth::user()->semester){1=>'st',2=>'nd',3=>'rd',default=>'th'} }} Semester</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-row-icon"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <div class="info-row-label">Email Address</div>
                            <div class="info-row-value">{{ Auth::user()->email }}</div>
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
                    <div class="info-card-header-icon" style="background:#f0fdf4;color:#16a34a;">
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
                            <div class="stat-chip-value">{{ $totalAll }}</div>
                            <div class="stat-chip-label">Total</div>
                        </div>
                    </div>

                    @if($totalAll > 0)
                    @php $rate = round((($totalPresent + $totalLate) / $totalAll) * 100); @endphp
                    <div style="margin-top:4px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-size:0.75rem;font-weight:600;color:#64748b;">Attendance Rate</span>
                            <span style="font-size:0.8rem;font-weight:700;color:{{ $rate >= 75 ? '#16a34a' : '#dc2626' }};">{{ $rate }}%</span>
                        </div>
                        <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                            <div style="height:100%;width:{{ $rate }}%;background:{{ $rate >= 75 ? 'linear-gradient(90deg,#16a34a,#22c55e)' : 'linear-gradient(90deg,#dc2626,#ef4444)' }};border-radius:99px;transition:width 1s ease;"></div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-header-icon" style="background:#eff6ff;color:#2563eb;">
                        <i class="bi bi-lightning-fill"></i>
                    </div>
                    <div class="info-card-title">Quick Actions</div>
                </div>
                <div class="info-card-body" style="padding-top:14px;padding-bottom:14px;">
                    <a href="{{ route('home') }}" class="quick-action-btn" style="text-decoration:none;">
                        <div style="display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;border:1px solid #f1f5f9;background:#fafafa;transition:all 0.2s;margin-bottom:8px;" onmouseover="this.style.background='#fff5f5';this.style.borderColor='#fecaca';" onmouseout="this.style.background='#fafafa';this.style.borderColor='#f1f5f9';">
                            <div style="width:32px;height:32px;border-radius:8px;background:#fff5f5;display:flex;align-items:center;justify-content:center;color:#800000;font-size:0.9rem;">
                                <i class="bi bi-grid-fill"></i>
                            </div>
                            <span style="font-size:0.875rem;font-weight:600;color:#1e293b;">Go to Dashboard</span>
                            <i class="bi bi-chevron-right ms-auto" style="color:#cbd5e1;font-size:0.75rem;"></i>
                        </div>
                    </a>
                    <a href="{{ route('settings') }}" style="text-decoration:none;">
                        <div style="display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;border:1px solid #f1f5f9;background:#fafafa;transition:all 0.2s;" onmouseover="this.style.background='#fff5f5';this.style.borderColor='#fecaca';" onmouseout="this.style.background='#fafafa';this.style.borderColor='#f1f5f9';">
                            <div style="width:32px;height:32px;border-radius:8px;background:#f8fafc;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:0.9rem;">
                                <i class="bi bi-gear-fill"></i>
                            </div>
                            <span style="font-size:0.875rem;font-weight:600;color:#1e293b;">Account Settings</span>
                            <i class="bi bi-chevron-right ms-auto" style="color:#cbd5e1;font-size:0.75rem;"></i>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
