@extends('layouts.app')

@section('page-title', 'My Profile')
@section('content')
@php
    $authUser = Auth::user();
    $academicSubtitle = implode(' · ', array_filter([
        $authUser->course,
        filled($authUser->year_level) ? 'Year ' . $authUser->year_level : null,
        filled($authUser->semester) ? 'Sem ' . $authUser->semester : null,
        filled($authUser->section) ? 'Sec ' . $authUser->section : null,
    ]));
    $allRecords   = $authUser->attendances ?? collect();
    $totalPresent = $allRecords->where('status', 'Present')->where('excused', false)->count();
    $totalLate    = $allRecords->where('status', 'Late')->where('excused', false)->count();
    $totalAbsent  = $allRecords->where('status', 'Absent')->where('excused', false)->count();
    $totalExcused = $allRecords->where('excused', true)->count();
    $totalAll     = $allRecords->count();
    $onTimeShare  = $totalAll > 0 ? round(($totalPresent / $totalAll) * 100) : 0;
    $binding      = $user->deviceBinding;
@endphp

<div style="max-width:980px;margin:0 auto;display:flex;flex-direction:column;gap:14px;">
    <header class="sf-page-head" style="margin-bottom:4px;">
        <div>
            <h1>My Profile</h1>
            <p>Your student identity, enrollment record, and attendance standing</p>
        </div>
        <div class="sf-page-actions">
            <a href="{{ route('settings') }}" class="sf-btn sf-btn-secondary">Account Settings</a>
        </div>
    </header>

    {{-- Identity Hero Card --}}
    <section class="sf-card sf-profile-hero" aria-label="Student profile summary">
        <div class="sf-profile-hero-left">
            <x-profile-photo-manager :user="$authUser" :size="72" avatar-id="studentAvatarDisplay" :show-overlay-trigger="false" />
            <div style="min-width:0;">
                <h2 style="margin:0 0 3px;font-size:18px;font-weight:700;color:var(--sf-text);">{{ $authUser->name }}</h2>
                <div style="font-size:12.5px;color:var(--sf-text-secondary);">{{ $academicSubtitle }}</div>
                <div style="font-size:12px;color:var(--sf-text-muted);margin-top:2px;">{{ $authUser->email }}</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <label for="ppmFileInput" class="sf-btn sf-btn-secondary" tabindex="0" style="cursor:pointer;">Change photo</label>
            @if($authUser->student_number)
                <button type="button" class="sf-btn sf-btn-ghost sf-mono" onclick="navigator.clipboard?.writeText('{{ $authUser->student_number }}'); window.showToast?.('Student ID copied!', 'info');">
                    ID {{ $authUser->student_number }}
                </button>
            @endif
        </div>
    </section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:14px;">
        {{-- Academic Record --}}
        <section class="sf-card">
            <div class="sf-card-head">
                <h2 class="sf-card-title">Academic Record</h2>
                <span class="sf-pill">Managed by Registrar</span>
            </div>
            <div class="sf-academic-grid" style="grid-template-columns:1fr 1fr;">
                <div class="sf-academic-cell">
                    <small>Student ID</small>
                    <strong class="sf-mono">{{ $authUser->student_number ?: 'Not Assigned' }}</strong>
                </div>
                <div class="sf-academic-cell">
                    <small>Program</small>
                    <strong>{{ $authUser->course ?: '—' }}</strong>
                </div>
                <div class="sf-academic-cell">
                    <small>Year Level</small>
                    <strong>{{ $authUser->year_level ? 'Year ' . $authUser->year_level : '—' }}</strong>
                </div>
                <div class="sf-academic-cell">
                    <small>Semester</small>
                    <strong>{{ $authUser->semester ? 'Semester ' . $authUser->semester : '—' }}</strong>
                </div>
            </div>
        </section>

        {{-- Attendance Summary --}}
        <section class="sf-card">
            <div class="sf-card-head">
                <h2 class="sf-card-title">Attendance Summary</h2>
                <span class="sf-pill sf-mono is-emerald">{{ $totalAll > 0 ? $onTimeShare . '% on-time' : '0 logged' }}</span>
            </div>
            <div class="sf-card-body" style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;text-align:center;">
                    <div class="sf-info-tile"><small>Present</small><strong style="color:var(--sf-emerald);">{{ $totalPresent }}</strong></div>
                    <div class="sf-info-tile"><small>Late</small><strong style="color:var(--sf-amber-text);">{{ $totalLate }}</strong></div>
                    <div class="sf-info-tile"><small>Absent</small><strong style="color:var(--sf-danger);">{{ $totalAbsent }}</strong></div>
                    <div class="sf-info-tile"><small>Excused</small><strong style="color:var(--sf-sky);">{{ $totalExcused }}</strong></div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:12px;color:var(--sf-text-muted);">{{ $binding ? 'Device bound: ' . ($binding->device_name ?: 'Authorized') : 'No attendance device bound yet' }}</span>
                    <a href="{{ route('student.calendar') }}#attendance-records" class="sf-link">View records →</a>
                </div>
            </div>
        </section>
    </div>

    {{-- Device Passkeys Card (preserves partials.profile-device-passkey-capability) --}}
    <section class="sf-card">
        <div class="sf-card-head">
            <h2 class="sf-card-title">Device Passkeys &amp; Biometrics</h2>
            <a href="{{ route('settings') }}#tab-biometrics" class="sf-link">Manage in Settings →</a>
        </div>
        <div class="sf-card-body">
            <div id="webauthnUnsupported" role="status" class="sf-flash is-warning" style="display:none;">
                <span id="webauthnUnsupportedMsg">Passkey setup is unavailable on this device.</span>
            </div>
            <div id="deviceList" style="margin-bottom:12px;">
                <div id="noDevices" style="font-size:12.5px;color:var(--sf-text-muted);">No passkeys registered yet.</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" onclick="registerDevicePasskey()" id="registerFpBtn" disabled class="sf-btn sf-btn-primary">
                    Set up a passkey
                </button>
                <a href="{{ route('settings.biometrics.face') }}" class="sf-btn sf-btn-secondary">Face Recognition Setup</a>
            </div>
            <div id="fpMessage" style="display:none;margin-top:10px;font-size:12.5px;"></div>
        </div>
    </section>
</div>

@include('partials.profile-device-passkey-capability')
@endsection
