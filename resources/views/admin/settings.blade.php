@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="admin-settings-page">
<div class="admin-settings-header">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Settings</h1>
        <p class="saas-text-muted" style="margin:0;">Open a category to change its settings. Save changes applies all categories.</p>
    </div>

    <div class="admin-page-actions">
        <button type="submit" form="settingsForm" class="saas-btn saas-btn-primary">
            <i class="bi bi-save"></i> Save Changes
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert"><p>Check these settings before saving:</p><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="admin-settings-layout">

    <!-- Main Settings Form -->
    <div class="admin-settings-form">
        <form id="settingsForm" action="{{ route('admin.settings.update') }}" method="POST">
            @csrf

            <details class="admin-settings-section" open>
                <summary id="settings-institution"><i class="bi bi-building" aria-hidden="true"></i><span><strong>School details</strong><small>Name, short name and tagline</small></span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                <div class="admin-settings-section-body">

            <div class="admin-settings-fields" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div class="saas-form-group">
                    <label class="saas-label">School / Institution Name</label>
                    <input type="text" name="school_name" class="saas-input" value="{{ env('APP_NAME', 'Smart Classroom System') }}">
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">Abbreviation / Short Name</label>
                    <input type="text" name="school_short_name" class="saas-input" value="SCS">
                </div>
            </div>

            <div class="saas-form-group" style="margin-bottom:32px;">
                <label class="saas-label">System Subtitle / Tagline</label>
                <input type="text" name="school_subtitle" class="saas-input" value="{{ env('APP_SUBTITLE', 'QR, GPS & Biometric Attendance') }}">
            </div>

                </div>
            </details>
            <details class="admin-settings-section" @if($errors->any()) open @endif>
                <summary id="settings-attendance"><i class="bi bi-clock-history" aria-hidden="true"></i><span><strong>Academic term & attendance</strong><small>Semester, late arrivals and absence thresholds</small></span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                <div class="admin-settings-section-body">

            <div class="admin-settings-fields" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div class="saas-form-group">
                    <label class="saas-label">Current Academic Year</label>
                    <select name="academic_year" class="saas-input saas-select">
                        <option value="2025-2026" {{ \App\Models\Setting::get('academic_year', '2025-2026') == '2025-2026' ? 'selected' : '' }}>2025-2026</option>
                        <option value="2026-2027" {{ \App\Models\Setting::get('academic_year') == '2026-2027' ? 'selected' : '' }}>2026-2027</option>
                    </select>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">Current Semester</label>
                    <select name="current_semester" class="saas-input saas-select">
                        <option value="1" {{ \App\Models\Setting::get('current_semester') == '1' ? 'selected' : '' }}>1st Semester</option>
                        <option value="2" {{ \App\Models\Setting::get('current_semester', '2') == '2' ? 'selected' : '' }}>2nd Semester</option>
                        <option value="summer" {{ \App\Models\Setting::get('current_semester') == 'summer' ? 'selected' : '' }}>Summer</option>
                    </select>
                </div>
            </div>

            <div class="admin-settings-fields admin-settings-fields-three" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:16px;">
                <div class="saas-form-group">
                    <label class="saas-label">Late Threshold (Minutes)</label>
                    <input type="number" name="late_threshold" class="saas-input" value="{{ \App\Models\Setting::get('late_threshold', 15) }}">
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">Students arriving after this time are marked Late.</p>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">Absent Threshold (Minutes)</label>
                    <input type="number" name="absent_threshold" class="saas-input" value="{{ \App\Models\Setting::get('absent_threshold', 45) }}">
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">Students arriving after this time are marked Absent.</p>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">Warning Threshold (Absences)</label>
                    <input type="number" name="warning_threshold" class="saas-input" value="{{ \App\Models\Setting::get('warning_threshold', 3) }}">
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">Number of absences before OSAS warning.</p>
                </div>
            </div>

                </div>
            </details>
            <details class="admin-settings-section" @if($errors->any()) open @endif>
                <summary id="settings-verification"><i class="bi bi-qr-code-scan" aria-hidden="true"></i><span><strong>QR codes, location & verification</strong><small>Attendance checks, device rules and campus location</small></span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                <div class="admin-settings-section-body">

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 class="saas-heading saas-heading-sm" style="margin:0; display:flex; align-items:center; gap:8px;">
                    Attendance checks
                </h3>
                <button type="button" class="saas-btn saas-btn-secondary" style="padding:4px 12px; font-size:0.8rem;" onclick="resetAttendanceRules()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset to Default
                </button>
            </div>

            <div class="admin-settings-fields" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:20px;">
                <div class="saas-form-group">
                    <label class="saas-label">QR Token Expiry (Seconds)</label>
                    <input type="number" name="qr_expiry" id="setting_qr_expiry" class="saas-input" value="{{ \App\Models\Setting::get('qr_expiry', 20) }}">
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">How long each QR code is valid before refreshing.</p>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">GPS Radius (Meters)</label>
                    <input type="number" name="gps_radius" id="setting_gps_radius" class="saas-input" value="{{ \App\Models\Setting::get('gps_radius', 50) }}">
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">Maximum allowed distance from the classroom coordinates.</p>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label" style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="require_biometric" id="setting_require_biometric" value="1" {{ \App\Models\Setting::get('require_biometric', 1) ? 'checked' : '' }} style="accent-color:var(--saas-primary); width:16px; height:16px;">
                        Require Biometrics
                    </label>
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">Enforce fingerprint or face scan verification for attendance clock-in.</p>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label" style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="auto_holiday" id="setting_auto_holiday" value="1" {{ \App\Models\Setting::get('auto_holiday', 1) ? 'checked' : '' }} style="accent-color:var(--saas-primary); width:16px; height:16px;">
                        Auto Holiday
                    </label>
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">Automatically exempt attendance on official & declared holidays.</p>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label" style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="enforce_device_binding" id="setting_enforce_device_binding" value="1" {{ \App\Models\Setting::get('enforce_device_binding', 1) ? 'checked' : '' }} style="accent-color:var(--saas-primary); width:16px; height:16px;">
                        Enforce Device Binding
                    </label>
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">Require students to record attendance exclusively from their registered personal device.</p>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label" style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="anti_proxy_device_check" id="setting_anti_proxy_device_check" value="1" {{ \App\Models\Setting::get('anti_proxy_device_check', 1) ? 'checked' : '' }} style="accent-color:var(--saas-primary); width:16px; height:16px;">
                        Anti-Proxy Hardware Protection
                    </label>
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">Block multiple students from clocking in from the same physical device in the same session.</p>
                </div>
            </div>

            <div style="background:rgba(207,164,111,0.05);border:1px solid rgba(207,164,111,0.15);border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:flex-start;gap:12px;">
                <i class="bi bi-shield-lock" style="color:var(--saas-gold,#cfa46f);font-size:1.3rem;margin-top:2px;"></i>
                <div>
                    <div style="font-weight:600;font-size:0.85rem;color:#f3e7cd;">Hardware Security & Anti-Proxy Architecture</div>
                    <div class="saas-text-muted" style="font-size:0.75rem;line-height:1.4;">
                        Device binding pairs each student account to their physical phone or browser. Anti-proxy hardware fingerprinting prevents students from passing their phones around to clock in for peers in the same classroom session.
                    </div>
                </div>
            </div>

            <!-- Campus GPS Coordinates -->
            <div style="background:rgba(255,255,255,0.02);border:1px solid var(--saas-border);border-radius:12px;padding:16px;margin-bottom:20px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
                    <div>
                        <div style="font-weight:600;font-size:0.9rem;display:flex;align-items:center;gap:6px;">
                            <i class="bi bi-geo-alt-fill" style="color:var(--saas-primary);"></i> Campus Anchor GPS Coordinates
                        </div>
                        <p class="saas-text-muted" style="font-size:0.75rem;margin:2px 0 0 0;">
                            Verified campus pin that teachers can choose when a device reports an inaccurate location. Capture it on campus with a device that has precise location enabled.
                        </p>
                    </div>
                    <button type="button" class="saas-btn saas-btn-secondary" style="padding:5px 12px;font-size:0.8rem;" onclick="detectCampusGps()">
                        <i class="bi bi-crosshair"></i> Detect My Current Location
                    </button>
                </div>
                <div class="admin-settings-fields" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="saas-form-group">
                        <label class="saas-label">Campus Latitude</label>
                        <input type="number" step="any" name="gps_lat" id="setting_gps_lat" class="saas-input" placeholder="e.g. 14.509600" value="{{ \App\Models\Setting::get('gps_lat', '') }}">
                    </div>
                    <div class="saas-form-group">
                        <label class="saas-label">Campus Longitude</label>
                        <input type="number" step="any" name="gps_lng" id="setting_gps_lng" class="saas-input" placeholder="e.g. 121.009000" value="{{ \App\Models\Setting::get('gps_lng', '') }}">
                    </div>
                </div>
                <div id="campusGpsStatus" style="font-size:0.75rem;margin-top:8px;display:none;"></div>
            </div>

            <script @cspNonce>
                function resetAttendanceRules() {
                    document.getElementById('setting_qr_expiry').value = 20;
                    document.getElementById('setting_gps_radius').value = 50;
                    document.getElementById('setting_require_biometric').checked = true;
                    document.getElementById('setting_auto_holiday').checked = true;
                    document.getElementById('setting_enforce_device_binding').checked = true;
                    document.getElementById('setting_anti_proxy_device_check').checked = true;
                }

                function detectCampusGps() {
                    const statusEl = document.getElementById('campusGpsStatus');
                    if (!navigator.geolocation) {
                        if (statusEl) {
                            statusEl.style.display = 'block';
                            statusEl.style.color = '#ef4444';
                            statusEl.textContent = 'Geolocation is not supported by your browser.';
                        }
                        return;
                    }
                    if (statusEl) {
                        statusEl.style.display = 'block';
                        statusEl.style.color = 'var(--saas-gold, #cfa46f)';
                        statusEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width:12px;height:12px;"></span> Detecting current location...';
                    }
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            if (!Number.isFinite(pos.coords.accuracy) || pos.coords.accuracy <= 0 || pos.coords.accuracy > 50) {
                                if (statusEl) {
                                    statusEl.style.color = '#ef4444';
                                    statusEl.textContent = 'Location accuracy is too low (±' + Math.round(pos.coords.accuracy || 0) + 'm). Enable precise location or use a phone with GPS, then try again.';
                                }
                                return;
                            }
                            document.getElementById('setting_gps_lat').value = pos.coords.latitude.toFixed(6);
                            document.getElementById('setting_gps_lng').value = pos.coords.longitude.toFixed(6);
                            if (statusEl) {
                                statusEl.style.color = '#10b981';
                                statusEl.innerHTML = '<i class="bi bi-check-circle me-1"></i> Location captured: ' + pos.coords.latitude.toFixed(6) + ', ' + pos.coords.longitude.toFixed(6) + ' (Accuracy: ±' + Math.round(pos.coords.accuracy) + 'm). Click Save Changes to apply.';
                            }
                        },
                        (err) => {
                            if (statusEl) {
                                statusEl.style.color = '#ef4444';
                                statusEl.textContent = 'Unable to detect location (' + err.message + '). Please enter coordinates manually.';
                            }
                        },
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                    );
                }
            </script>

                </div>
            </details>
            <details class="admin-settings-section" @if($errors->any()) open @endif>
                <summary id="settings-notifications"><i class="bi bi-bell" aria-hidden="true"></i><span><strong>Features & notifications</strong><small>Available features, email and SMS alerts</small></span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                <div class="admin-settings-section-body">

            <label style="display:flex; align-items:center; gap:12px; margin-bottom:12px; cursor:pointer;">
                <input type="checkbox" name="email_notifications" checked style="accent-color:var(--saas-primary); width:16px; height:16px;">
                <div>
                    <div style="font-weight:500; font-size:0.9rem;">Enable Email Notifications</div>
                    <div class="saas-text-muted" style="font-size:0.75rem;">Send automated emails to parents for absences.</div>
                </div>
            </label>

            @php
                $featureControls = [
                    'face_verification' => ['Face verification', 'Supplementary identity check during attendance.'],
                    'student_assistant_qr' => ['Student assistant QR', 'Allow assigned student assistants to manage session QR codes.'],
                    'gps_verification' => ['GPS verification', 'Use location checks for attendance sessions.'],
                    'device_binding' => ['Device binding', 'Require the student’s registered device when the workflow requests it.'],
                    'sms_notifications' => ['SMS notifications', 'Send supported absence alerts by text message.'],
                    'offline_attendance' => ['Offline attendance sync', 'Allow queued attendance records to sync after connectivity returns.'],
                ];
            @endphp
            <p class="saas-text-muted" role="note" style="font-size:0.8rem; margin:12px 0 0;">
                Peer biometric follows the deployment setting automatically; check-ins require a configured verifier. No admin toggle is needed.
            </p>
            <input type="hidden" name="feature_flags_present" value="1">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px; margin-top:18px;">
                @foreach($featureControls as $name => [$label, $description])
                    <label style="display:flex; align-items:flex-start; gap:10px; padding:12px; border:1px solid var(--saas-border); border-radius:10px; cursor:pointer;">
                        <input type="checkbox" name="features[{{ $name }}]" value="1" @checked($featureFlags['feature.' . $name] ?? false) style="accent-color:var(--saas-primary); width:16px; height:16px; margin-top:2px;">
                        <span>
                            <span style="display:block; font-weight:500; font-size:0.9rem;">{{ $label }}</span>
                            <span class="saas-text-muted" style="display:block; font-size:0.75rem;">{{ $description }}</span>
                            @if($name === 'sms_notifications')
                                <span class="saas-text-muted" style="display:block; font-size:0.72rem; margin-top:4px;">
                                    @if(app(\App\Services\SemaphoreService::class)->isConfigured())
                                        SMS gateway configured.
                                    @else
                                        SMS unavailable until SMS_ENABLED and a valid Semaphore key are configured in the deployment environment.
                                    @endif
                                </span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>

                </div>
            </details>
            <details class="admin-settings-section" @if($errors->any()) open @endif>
                <summary id="settings-security"><i class="bi bi-shield-lock" aria-hidden="true"></i><span><strong>Security & access</strong><small>Face verification limits and admin access</small></span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                <div class="admin-settings-section-body">

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px; margin-bottom:24px;">
                <div class="saas-form-group">
                    <label class="saas-label" for="face_lockout_minutes">Face verification lockout</label>
                    <select id="face_lockout_minutes" name="face_lockout_minutes" class="saas-input">
                        @foreach([5, 10, 15, 30, 60] as $minutes)
                            <option value="{{ $minutes }}" @selected((int) \App\Models\Setting::get('face_lockout_minutes', 15) === $minutes)>{{ $minutes }} minutes after five failed attempts</option>
                        @endforeach
                    </select>
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">The limit is five unsuccessful face checks per student per attendance session.</p>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label" for="face_verification_additional_factor">Additional check after face match</label>
                    <select id="face_verification_additional_factor" name="face_verification_additional_factor" class="saas-input">
                        @php($faceFactor = \App\Models\Setting::get('face_verification_additional_factor', 'none'))
                        <option value="none" @selected($faceFactor === 'none')>None</option>
                        <option value="fingerprint" @selected($faceFactor === 'fingerprint')>Registered fingerprint or device biometric</option>
                        <option value="device" @selected($faceFactor === 'device')>Registered device</option>
                        <option value="fingerprint_and_device" @selected($faceFactor === 'fingerprint_and_device')>Fingerprint and registered device</option>
                    </select>
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">Fingerprint uses the existing WebAuthn assertion. Device checks require an existing device binding.</p>
                </div>
            </div>

            <div class="saas-form-group" style="margin-bottom:32px;">
                <label class="saas-label">Admin IP Whitelist</label>
                <input type="text" name="admin_ip_whitelist" class="saas-input" value="{{ \App\Models\Setting::get('admin_ip_whitelist', '') }}" placeholder="e.g. 192.168.1.1, 10.0.0.5">
                <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">Comma-separated list of IP addresses allowed to access the Admin panel. Leave blank to disable whitelisting.</p>
            </div>

                </div>
            </details>
            <div class="admin-settings-footer">
                <button type="submit" class="saas-btn saas-btn-primary"><i class="bi bi-save"></i> Save Changes</button>
            </div>
        </form>
    </div>

    <!-- Sidebar Settings/Info -->
    <div class="admin-settings-aside">
        <div class="saas-card" style="padding:20px; margin-bottom:20px;">
            <h4 class="saas-heading saas-heading-sm" style="margin-bottom:12px;">System Information</h4>
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span class="saas-text-muted" style="font-size:0.85rem;">Version</span>
                <span style="font-size:0.85rem; font-weight:600; color:var(--saas-gold);">v{{ app(\App\Services\ChangelogService::class)->getLatestVersion() }}</span>
            </div>
            <details class="admin-technical-details"><summary>Technical details</summary>
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span class="saas-text-muted" style="font-size:0.85rem;">Laravel</span>
                <span style="font-size:0.85rem; font-weight:600;">v{{ app()->version() }}</span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span class="saas-text-muted" style="font-size:0.85rem;">PHP</span>
                <span style="font-size:0.85rem; font-weight:600;">v{{ phpversion() }}</span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:16px;">
                <span class="saas-text-muted" style="font-size:0.85rem;">Timezone</span>
                <span style="font-size:0.85rem; font-weight:600;">{{ config('app.timezone') }}</span>
            </div>
            </details>

            <button type="button" id="adminCheckUpdateBtn" onclick="checkAdminUpdates()" class="saas-btn saas-btn-secondary" style="width:100%; justify-content:center; font-size:0.82rem; padding:8px 12px;">
                <i class="bi bi-cloud-arrow-down me-1"></i> Check for Updates
            </button>
            <div id="adminUpdateFeedback" style="display:none; margin-top:10px; padding:10px 12px; border-radius:8px; font-size:0.8rem; line-height:1.4;"></div>
        </div>

        <div class="saas-card" style="padding:20px;">
            <h4 class="saas-heading saas-heading-sm" style="margin-bottom:12px;">Maintenance</h4>
            <p class="saas-text-muted" style="font-size:0.85rem;">Review deployment information and maintenance actions.</p>
            <a href="{{ route('admin.system-update.index') }}" class="saas-btn saas-btn-secondary w-100"><i class="bi bi-tools" aria-hidden="true"></i> Open maintenance tools</a>
        </div>
    </div>
</div>
</div>

<script @cspNonce>
async function checkAdminUpdates() {
    const btn = document.getElementById('adminCheckUpdateBtn');
    const feedback = document.getElementById('adminUpdateFeedback');
    if (!btn || !feedback) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width:12px;height:12px;"></span> Checking...';
    feedback.style.display = 'block';
    feedback.style.background = 'rgba(59, 130, 246, 0.1)';
    feedback.style.border = '1px solid rgba(59, 130, 246, 0.3)';
    feedback.style.color = '#93c5fd';
    feedback.innerHTML = '<i class="bi bi-arrow-repeat spin me-1"></i> Checking service worker & server version...';

    if (!navigator.onLine) {
        feedback.style.background = 'rgba(239, 68, 68, 0.1)';
        feedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
        feedback.style.color = '#fca5a5';
        feedback.innerHTML = '<i class="bi bi-wifi-off me-1"></i> Offline. Connect to the internet to check.';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-arrow-down me-1"></i> Check for Updates';
        return;
    }

    try {
        let result = null;
        if (typeof checkServerVersion === 'function') {
            result = await checkServerVersion(true, true);
        } else if ('serviceWorker' in navigator) {
            const reg = await navigator.serviceWorker.getRegistration();
            if (reg) {
                await reg.update();
                if (reg.waiting && typeof showAppUpdatePopup === 'function') {
                    showAppUpdatePopup(null, true);
                    feedback.style.display = 'none';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Update Ready';
                    return;
                }
            }
        }

        await new Promise(r => setTimeout(r, 400));

        const popup = document.getElementById('pwaSystemUpdatePopup');
        const popupVisible = popup && window.getComputedStyle(popup).display !== 'none';

        if (result && result.checkFailed) {
            feedback.style.background = 'rgba(239, 68, 68, 0.1)';
            feedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
            feedback.style.color = '#fca5a5';
            feedback.textContent = 'Unable to check for updates right now. Please try again when connected.';
        } else if (popupVisible || (result && result.updateAvailable)) {
            feedback.style.background = 'rgba(207, 164, 111, 0.12)';
            feedback.style.border = '1px solid rgba(207, 164, 111, 0.35)';
            feedback.style.color = '#f3e7cd';
            feedback.innerHTML = '<i class="bi bi-stars me-2"></i>A new software update is ready! Tap "Update Now" on the notification to activate.';
            btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Update Ready';
        } else {
            feedback.style.background = 'rgba(16, 185, 129, 0.1)';
            feedback.style.border = '1px solid rgba(16, 185, 129, 0.3)';
            feedback.style.color = '#6ee7b7';
            feedback.innerHTML = '<div style="display:flex; align-items:flex-start; gap:8px;"><i class="bi bi-check-circle-fill me-1" style="font-size:1.1rem; color:#22c55e;"></i><div><strong>You’re up to date ✓</strong><div style="font-size:0.85em; opacity:0.9; margin-top:2px;">Your system is running the deployed version (v{{ app(\App\Services\VersionService::class)->getInstalledVersion() }}).</div></div></div>';
        }
    } catch (e) {
        feedback.style.background = 'rgba(239, 68, 68, 0.1)';
        feedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
        feedback.style.color = '#fca5a5';
        feedback.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Check failed: ' + (e.message || 'Error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-arrow-down me-1"></i> Check for Updates';
    }
}
</script>
@endsection
