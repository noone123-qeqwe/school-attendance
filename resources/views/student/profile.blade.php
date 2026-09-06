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

    @if($errors->any())
    <div class="flash-err">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <span>{{ $errors->first() }}</span>
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
            <input type="file" name="profile_image" id="profile_image_input" class="d-none" accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/heic,image/heif,image/*" onchange="handleStudentAvatarUpload(this)">
            <label for="profile_image_input" class="avatar-wrap" title="Click to change profile picture">
                @if(Auth::user()->profile_image)
                    <img id="studentAvatarDisplay" src="{{ str_starts_with(Auth::user()->profile_image, 'http') ? Auth::user()->profile_image : asset('storage/'.Auth::user()->profile_image) }}"
                         onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=800000&color=fff&size=200'">
                @else
                    <img id="studentAvatarDisplay" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=800000&color=fff&size=200">
                @endif
                <div class="avatar-edit-overlay" id="avatarEditOverlay">
                    <i class="bi bi-camera-fill" style="font-size:1.2rem;"></i>
                    <span>Change</span>
                </div>
            </label>
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

            <!-- Fingerprint & Biometrics Card -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-header-icon" style="background:rgba(22,163,74,0.15);color:#4ade80;">
                        <i class="bi bi-fingerprint"></i>
                    </div>
                    <div class="info-card-title">Fingerprint / Biometric Login</div>
                </div>
                <div class="info-card-body">
                    <div id="webauthnUnsupported" style="display:none;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);color:#f87171;border-radius:12px;padding:14px 18px;font-size:.85rem;margin-bottom:14px;">
                        <div style="display:flex;align-items:flex-start;gap:10px;">
                            <i class="bi bi-exclamation-triangle" style="font-size:1.1rem;flex-shrink:0;margin-top:2px;"></i>
                            <div id="webauthnUnsupportedMsg" style="font-size:.8rem;line-height:1.4;">
                                Biometric login is not supported on this browser or device.
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom:16px;">
                        <div style="font-size:.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Registered Biometric Devices</div>
                        <div id="deviceList">
                            <div style="text-align:center;padding:16px;color:#b39b82;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:10px;border:1px dashed rgba(207,164,111,0.2);" id="noDevices">
                                <i class="bi bi-fingerprint" style="font-size:1.8rem;display:block;margin-bottom:6px;opacity:.4;color:var(--gold,#CFA46F);"></i>
                                No fingerprint registered yet.
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="registerFingerprint()" id="registerFpBtn" class="btn w-100" style="background:linear-gradient(135deg,#16a34a,#22c55e);box-shadow:0 4px 14px rgba(22,163,74,.25);color:white;font-weight:700;padding:11px 18px;border-radius:12px;border:none;">
                        <i class="bi bi-fingerprint me-2"></i>Register This Device
                    </button>
                    <div id="fpMessage" style="margin-top:12px;font-size:.82rem;display:none;"></div>
                </div>
            </div>

            <!-- APK Download Card removed - users can install via PWA prompt -->

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
                        <div style="display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.03);transition:all 0.2s;margin-bottom:8px;" onmouseover="this.style.background='rgba(255,255,255,0.06)';" onmouseout="this.style.background='rgba(255,255,255,0.03)';">
                            <div style="width:32px;height:32px;border-radius:8px;background:rgba(207,164,111,0.15);display:flex;align-items:center;justify-content:center;color:var(--gold,#CFA46F);font-size:0.9rem;">
                                <i class="bi bi-grid-fill"></i>
                            </div>
                            <span style="font-size:0.875rem;font-weight:600;color:#f3e7cd;">Go to Dashboard</span>
                            <i class="bi bi-chevron-right ms-auto" style="color:#b39b82;font-size:0.75rem;"></i>
                        </div>
                    </a>
                    <a href="{{ route('student.schedule') }}" style="text-decoration:none;">
                        <div style="display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.03);transition:all 0.2s;margin-bottom:8px;" onmouseover="this.style.background='rgba(255,255,255,0.06)';" onmouseout="this.style.background='rgba(255,255,255,0.03)';">
                            <div style="width:32px;height:32px;border-radius:8px;background:rgba(207,164,111,0.15);display:flex;align-items:center;justify-content:center;color:var(--gold,#CFA46F);font-size:0.9rem;">
                                <i class="bi bi-calendar2-week-fill"></i>
                            </div>
                            <span style="font-size:0.875rem;font-weight:600;color:#f3e7cd;">My Schedule</span>
                            <i class="bi bi-chevron-right ms-auto" style="color:#b39b82;font-size:0.75rem;"></i>
                        </div>
                    </a>
                    <a href="{{ route('settings') }}" style="text-decoration:none;">
                        <div style="display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.03);transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.06)';" onmouseout="this.style.background='rgba(255,255,255,0.03)';">
                            <div style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;color:#b39b82;font-size:0.9rem;">
                                <i class="bi bi-gear-fill"></i>
                            </div>
                            <span style="font-size:0.875rem;font-weight:600;color:#f3e7cd;">Account Settings</span>
                            <i class="bi bi-chevron-right ms-auto" style="color:#b39b82;font-size:0.75rem;"></i>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
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
async function loadDevices() {
    if (!window.PublicKeyCredential) {
        const btn = document.getElementById('registerFpBtn');
        const unsupported = document.getElementById('webauthnUnsupported');
        if (unsupported) {
            unsupported.style.display = 'block';
            const msgEl = document.getElementById('webauthnUnsupportedMsg');
            if (msgEl) {
                if (!window.isSecureContext) {
                    msgEl.innerHTML = 'Biometric WebAuthn requires a <strong>secure connection (HTTPS or localhost)</strong>. If accessing from mobile, please open via HTTPS.';
                } else {
                    msgEl.innerHTML = 'Biometric login is not supported by your current browser or device.';
                }
            }
        }
        if (btn) {
            btn.style.display = 'block';
            btn.onclick = function() {
                if (!window.isSecureContext) {
                    alert('Biometric registration requires HTTPS (secure context). Please open via HTTPS or localhost.');
                } else {
                    alert('Biometric authentication is not supported by your browser or device.');
                }
            };
        }
    }
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
            successNote.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Biometric key active for this account.';
            list.appendChild(successNote);

            devices.forEach(d => {
                const div = document.createElement('div');
                div.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;margin-bottom:8px;';
                div.innerHTML = `
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:8px;background:rgba(74,222,128,0.1);display:flex;align-items:center;justify-content:center;color:#4ade80;">
                            <i class="bi bi-fingerprint"></i>
                        </div>
                        <div>
                            <div style="font-size:.85rem;font-weight:600;color:#f3e7cd;">${d.name || d.device_name || "My Device"}</div>
                            <div style="font-size:.7rem;color:#b39b82;">Registered ${new Date(d.created_at).toLocaleDateString()}</div>
                        </div>
                    </div>
                    <button type="button" onclick="removeDevice('${d.credential_id}', this)"
                        style="padding:4px 10px;border-radius:6px;background:rgba(248,113,113,0.1);color:#f87171;border:1px solid rgba(248,113,113,0.2);font-size:.75rem;font-weight:600;cursor:pointer;">
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

async function registerFingerprint() {
    const btn = document.getElementById('registerFpBtn');
    const msg = document.getElementById('fpMessage');
    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Waiting for biometric prompt...';
    if (msg) msg.style.display = 'none';

    try {
        const optRes = await fetch('{{ route("webauthn.register.options") }}', {
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        });
        const opts = await optRes.json();

        const challenge = base64ToUint8Array(opts.challenge);
        const userId = base64ToUint8Array(opts.user.id);
        const hostname = window.location.hostname;
        const isIp = /^(\d{1,3}\.){3}\d{1,3}$/.test(hostname) || hostname.includes(':');
        const rp = { name: opts.rp?.name || 'School Attendance' };
        if (opts.rp?.id && !isIp) {
            rp.id = opts.rp.id;
        }

        const excludeCredentials = (opts.excludeCredentials || []).map(c => ({
            type: c.type || 'public-key',
            id: base64ToUint8Array(c.id)
        }));

        const credential = await navigator.credentials.create({
            publicKey: {
                challenge,
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
                excludeCredentials
            }
        });

        const rawId = bufferToBase64Url(credential.rawId);
        const clientDataJSON = bufferToBase64Url(credential.response.clientDataJSON);
        const attestationObject = bufferToBase64Url(credential.response.attestationObject);

        const saveRes = await fetch('{{ route("webauthn.register") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({
                credential_id: rawId,
                credential: {
                    id: credential.id,
                    type: credential.type,
                    response: {
                        attestationObject: attestationObject,
                        clientDataJSON: clientDataJSON
                    }
                },
                device_name: navigator.userAgent.includes('Mobile') ? 'Mobile Device' : 'Desktop Browser'
            })
        });
        const result = await saveRes.json();

        if (result.success) {
            if (msg) {
                msg.style.display = 'block';
                msg.style.color = '#4ade80';
                msg.innerHTML = '<i class="bi bi-check-circle me-2"></i>Fingerprint registered successfully!';
            }
            btn.innerHTML = '<i class="bi bi-fingerprint me-2"></i>Register This Device';
            btn.disabled = false;
            loadDevices();
        } else {
            throw new Error(result.message || 'Registration failed');
        }
    } catch(err) {
        if (msg) {
            msg.style.display = 'block';
            msg.style.color = '#f87171';
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>' + (err.message || 'Fingerprint registration cancelled.');
        }
        btn.innerHTML = '<i class="bi bi-fingerprint me-2"></i>Register This Device';
        btn.disabled = false;
    }
}

async function removeDevice(id, btn) {
    if (!confirm('Remove this fingerprint device?')) return;
    try {
        await fetch('{{ route("webauthn.remove") }}', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ credential_id: id })
        });
        loadDevices();
    } catch(e) {}
}

async function handleStudentAvatarUpload(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    
    // Check file size (10 MB max)
    if (file.size > 10 * 1024 * 1024) {
        alert('The selected image is too large (' + (file.size / (1024 * 1024)).toFixed(1) + 'MB). Please choose an image under 10MB.');
        input.value = '';
        return;
    }
    
    // Instant preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const avatarImg = document.getElementById('studentAvatarDisplay');
        if (avatarImg) avatarImg.src = e.target.result;
    };
    reader.readAsDataURL(file);
    
    // Loading indicator
    const overlay = document.getElementById('avatarEditOverlay');
    if (overlay) {
        overlay.innerHTML = '<div class="spinner-border spinner-border-sm text-light" role="status" style="width:1.2rem;height:1.2rem;"></div><span style="font-size:0.7rem;margin-top:4px;">Saving...</span>';
        overlay.style.opacity = '1';
    }
    
    const form = document.getElementById('profileImageForm');
    const formData = new FormData(form);
    formData.set('profile_image', file);
    formData.set('_token', '{{ csrf_token() }}');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            if (overlay) {
                overlay.innerHTML = '<i class="bi bi-check-circle-fill text-success" style="font-size:1.3rem;"></i><span style="font-size:0.65rem;margin-top:2px;">Saved</span>';
                setTimeout(() => {
                    overlay.style.opacity = '0';
                    overlay.innerHTML = '<i class="bi bi-camera-fill" style="font-size:1.2rem;"></i><span>Change</span>';
                }, 1800);
            }
            if (data.image_url) {
                const freshUrl = data.image_url + (data.image_url.includes('?') ? '&' : '?') + 't=' + Date.now();
                document.querySelectorAll('.top-nav-avatar, .user-avatar-img, .header-user-avatar, .header-profile-img, .mobile-user-avatar, #studentAvatarDisplay, #settingsAvatarDisplay').forEach(img => {
                    img.src = freshUrl;
                });
            }
            if (window.triggerHaptic) window.triggerHaptic('success');
        } else {
            throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Upload failed'));
        }
    } catch (err) {
        console.warn('AJAX upload fallback to form submit:', err);
        form.submit();
    }
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
