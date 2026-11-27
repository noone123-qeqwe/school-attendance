@extends('layouts.app')

@section('page-title', 'My Profile')
@section('content')
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

    @include('partials.student-page-header', ['title' => 'My Profile', 'description' => 'Your student information, account details, and attendance overview.'])
    <section class="student-profile-summary" aria-label="Student profile">
        <div class="student-avatar-container"><x-profile-photo-manager :user="Auth::user()" :size="88" avatar-id="studentAvatarDisplay" :show-overlay-trigger="false" /></div>
        <div class="profile-meta">
            <div>
                <div class="profile-name">{{ Auth::user()->name }}</div>
                <div class="profile-id" style="color:#ACA099;font-size:0.84rem;margin-top:2px;">{{ Auth::user()->course }} · Year {{ Auth::user()->year_level }}</div>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <label for="ppmFileInput" class="student-photo-change-btn" tabindex="0"><i class="bi bi-camera-fill" aria-hidden="true"></i><span>Change photo</span></label>
                <a href="{{ route('settings') }}" class="student-page-link"><i class="bi bi-sliders" aria-hidden="true"></i>Account Settings</a>
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
                        <div class="flex-grow-1">
                            <div class="info-row-label">Student ID Number</div>
                            <div class="info-row-value d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span style="font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 1.02rem; letter-spacing: 0.04em; color: #FAF8F5; font-weight: 700;">
                                    {{ Auth::user()->student_number ?: 'Not Assigned' }}
                                </span>
                                @if(Auth::user()->student_number)
                                    <button type="button" class="btn btn-sm py-1 px-3 copy-student-id-btn" onclick="if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText('{{ Auth::user()->student_number }}').then(() => { this.innerHTML='<i class=\'bi bi-check2 me-1\' aria-hidden=\'true\'></i>Copied!'; setTimeout(() => { this.innerHTML='<i class=\'bi bi-clipboard me-1\' aria-hidden=\'true\'></i>Copy'; }, 1500); }).catch(() => { if (typeof showToast === 'function') showToast('Unable to copy Student ID.', 'warning'); }); }" style="min-height: 44px; font-size:0.78rem; font-weight:600; background: #261B1B; border:1px solid rgba(255, 255, 255,0.18); color:#FAF8F5; border-radius: 8px;">
                                        <i class="bi bi-clipboard me-1" aria-hidden="true"></i>Copy
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div>
                            <div class="info-row-label">Course</div>
                            <div class="info-row-value">{{ Auth::user()->course }}</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div>
                            <div class="info-row-label">Year Level</div>
                            <div class="info-row-value">{{ Auth::user()->year_level }}{{ match((int)Auth::user()->year_level) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }} Year</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div>
                            <div class="info-row-label">Semester</div>
                            <div class="info-row-value">{{ Auth::user()->semester }}{{ match((int)Auth::user()->semester){1=>'st',2=>'nd',3=>'rd',default=>'th'} }} Semester</div>
                        </div>
                    </div>

                    <div class="info-row">
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
                    <div class="info-card-header-icon" style="color:#4ADE80;">
                        <i class="bi bi-bar-chart-fill" aria-hidden="true"></i>
                    </div>
                    <div class="info-card-title">Logged Attendance Summary</div>
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
                            <div class="stat-chip-value" style="color:#4ADE80;">{{ $totalPresent }}</div>
                            <div class="stat-chip-label">Present</div>
                        </div>
                        <div class="stat-chip">
                            <div class="stat-chip-value" style="color:#FBBF24;">{{ $totalLate }}</div>
                            <div class="stat-chip-label">Late</div>
                        </div>
                        <div class="stat-chip">
                            <div class="stat-chip-value" style="color:#F87171;">{{ $totalAbsent }}</div>
                            <div class="stat-chip-label">Absent</div>
                        </div>
                        <div class="stat-chip">
                            <div class="stat-chip-value" style="color:#7DD3FC;">{{ $totalExcused }}</div>
                            <div class="stat-chip-label">Excused</div>
                        </div>
                        <div class="stat-chip">
                            <div class="stat-chip-value" style="color:#FAF8F5;">{{ $totalAll }}</div>
                            <div class="stat-chip-label">Total</div>
                        </div>
                    </div>

                    @if($totalAll > 0)
                    @php $rate = round((($totalPresent + $totalLate) / $totalAll) * 100); @endphp
                    <div style="margin-top:4px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-size:0.78rem;font-weight:600;color:#ACA099;">Logged Attendance Rate</span>
                            <span style="font-size:0.84rem;font-weight:700;color:{{ $rate >= 75 ? '#4ADE80' : '#F87171' }};font-variant-numeric:tabular-nums;">{{ $rate }}%</span>
                        </div>
                        <div style="height:6px;background:rgba(255, 255, 255,0.08);border-radius:99px;overflow:hidden;">
                            <div style="height:100%;width:{{ $rate }}%;background:{{ $rate >= 75 ? '#4ADE80' : '#F87171' }};border-radius:99px;transition:width 0.35s ease;"></div>
                        </div>
                        <p style="margin:8px 0 0;font-size:0.74rem;color:#ACA099;line-height:1.4;">Based on {{ $totalAll }} recorded entries · see Dashboard for overall standing including missed classes.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Bound Attendance Device & Security Card -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-header-icon" style="color:#7DD3FC;">
                        <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                    </div>
                    <div class="info-card-title">Bound Attendance Device</div>
                </div>
                <div class="info-card-body">
                    @php
                        $binding = $user->deviceBinding;
                    @endphp
                    @if($binding)
                    <div style="background:#141010;border:1px solid rgba(255, 255, 255,0.09);border-radius:10px;padding:14px 16px;margin-bottom:14px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:10px;flex-wrap:wrap;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="bi {{ $binding->getDeviceIcon() }}" style="color:#4ADE80;font-size:1.25rem;flex-shrink:0;" aria-hidden="true"></i>
                                <div>
                                    <div style="font-weight:700;font-size:0.95rem;color:#FAF8F5;">{{ $binding->device_name ?: 'Registered Mobile Device' }}</div>
                                    <div style="font-size:0.75rem;color:#ACA099;">Bound on {{ $binding->created_at?->format('M d, Y') ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <x-badge variant="success" size="sm" :dot="true">Verified</x-badge>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;font-size:0.78rem;padding-top:10px;border-top:1px solid rgba(255, 255, 255,0.09);">
                            <div style="min-width:0;overflow-wrap:anywhere;">
                                <span style="color:#ACA099;">Last Active:</span>
                                <span style="color:#FAF8F5;font-weight:600;margin-left:4px;">{{ $binding->last_seen_at?->diffForHumans() ?? 'Just now' }}</span>
                            </div>
                            <div style="min-width:0;overflow-wrap:anywhere;">
                                <span style="color:#ACA099;">IP Address:</span>
                                <span style="color:#FAF8F5;font-weight:600;margin-left:4px;">{{ $binding->ip_address ?: 'Protected' }}</span>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:10px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.09);border-radius:10px;padding:10px 12px;font-size:0.78rem;color:#D8CEC8;line-height:1.45;">
                        <i class="bi bi-info-circle-fill" style="color:#D4AF37;margin-top:2px;flex-shrink:0;" aria-hidden="true"></i>
                        <div>
                            Anti-proxy protection active. Your student account is strictly bound to this personal device for attendance clock-ins.
                        </div>
                    </div>
                    @else
                    <div style="text-align:center;padding:18px 14px;background:#141010;border:1px dashed rgba(255, 255, 255,0.16);border-radius:12px;color:#ACA099;font-size:0.85rem;">
                        <i class="bi bi-phone" style="font-size:1.8rem;display:block;margin-bottom:8px;color:#D4AF37;" aria-hidden="true"></i>
                        <div style="font-weight:700;color:#FAF8F5;margin-bottom:4px;">No Device Bound Yet</div>
                        <div style="font-size:0.78rem;line-height:1.45;">Your personal device will be automatically and securely bound to your student account when you sign in or record attendance.</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Device passkeys -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-header-icon" style="color:#4ADE80;">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
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
                        <div style="font-size:.72rem;font-weight:700;color:#ACA099;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Registered devices</div>
                        <div id="deviceList">
                            <div style="text-align:center;padding:16px;color:#ACA099;font-size:.84rem;background:#141010;border-radius:10px;border:1px dashed rgba(255, 255, 255,0.14);" id="noDevices">
                                <i class="bi bi-shield-lock" style="font-size:1.6rem;display:block;margin-bottom:6px;color:#D4AF37;" aria-hidden="true"></i>
                                No passkeys registered yet.
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="registerDevicePasskey()" id="registerFpBtn" disabled class="btn w-100" style="background:#FAF8F5;color:#7A1A1A;font-weight:700;min-height:44px;padding:10px 18px;border-radius:10px;border:1px solid #FAF8F5;">
                        <i class="bi bi-shield-lock me-2" aria-hidden="true"></i>Set up a passkey
                    </button>
                    <div id="fpMessage" style="margin-top:12px;font-size:.82rem;display:none;"></div>
                    <div class="mt-3 pt-2 profile-quick-links d-flex align-items-center justify-content-between gap-2 flex-wrap" style="border-top: 1px solid rgba(255, 255, 255,0.08);">
                        <a href="{{ route('settings.biometrics.face') }}" style="min-height:44px;display:inline-flex;align-items:center;color:#D4AF37;font-size:0.82rem;font-weight:700;text-decoration:none;">
                            <i class="bi bi-camera-video me-1" aria-hidden="true"></i>Face Recognition Setup
                        </a>
                        <a href="{{ route('settings') }}#tab-biometrics" style="min-height:44px;display:inline-flex;align-items:center;color:#D8CEC8;font-size:0.82rem;font-weight:600;text-decoration:none;">
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
@endsection
