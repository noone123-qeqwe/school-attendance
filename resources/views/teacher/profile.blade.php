@extends('layouts.app')

@section('page-title', 'Settings')
@section('role-sidebar')
    @include('layouts.sidebars.settings', ['settingsUser' => $teacher])
@endsection

@section('content')
@php
    $prefs = is_string($teacher->notification_preferences)
        ? json_decode($teacher->notification_preferences, true)
        : (is_array($teacher->notification_preferences) ? $teacher->notification_preferences : []);
    $emailNotif = $prefs['email'] ?? true;
    $pushNotif = $prefs['push'] ?? true;
    $mobileNavGroups = \App\Support\SettingsNavigation::groups($teacher);
    $assignedCount = $assignedClassesCount ?? 0;
@endphp

<div class="sp teacher-settings"
     data-teacher-settings="{{ $teacher->id }}"
     data-settings-view="profile"
     data-settings-feedback="{{ $errors->any() || session('success') ? 'true' : 'false' }}"
     data-settings-email-error="{{ $errors->has('new_email') || $errors->has('otp') ? 'true' : 'false' }}"
     data-settings-email-address-error="{{ $errors->has('new_email') ? 'true' : 'false' }}"
     data-settings-email-error-message="{{ $errors->first('new_email') ?: $errors->first('otp') }}"
     data-settings-password-error="{{ $errors->has('password') || $errors->has('password_confirmation') || $errors->has('otp') ? 'true' : 'false' }}"
     data-settings-password-error-message="{{ $errors->first('password') ?: $errors->first('password_confirmation') ?: $errors->first('otp') }}">

    <header class="teacher-settings-heading">
        <h1>Your settings</h1>
        <p>Manage your faculty profile, account security, and notification preferences.</p>
    </header>

    @if(session('success'))
        <div class="ts-flash ts-flash-ok" role="status">
            <i class="bi bi-check-circle-fill fs-5" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="ts-flash ts-flash-err" role="alert">
            <i class="bi bi-exclamation-circle-fill fs-5" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="settings-main-content" id="teacherSettingsContent" aria-labelledby="teacherSettingsPageTitle">
        <nav class="settings-mobile-nav d-md-none" aria-label="Settings sections">
            @foreach($mobileNavGroups as $groupItems)
                @foreach($groupItems as $item)
                    <button type="button"
                            class="settings-mobile-pill {{ $item['destination'] === 'profile' ? 'active' : '' }}"
                            data-settings-page="{{ $item['destination'] }}"
                            aria-controls="teacherSettingsContent"
                            @if($item['destination'] === 'profile') aria-current="page" @endif>
                        <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </button>
                @endforeach
            @endforeach
        </nav>

        <header class="teacher-settings-detail-heading">
            <button type="button" class="teacher-settings-back" data-account-back hidden>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span data-account-back-label>Back to Password &amp; email</span>
            </button>
            <h2 id="teacherSettingsPageTitle" tabindex="-1">Profile &amp; faculty details</h2>
            <p id="teacherSettingsPageDescription">Manage your profile photo and review your faculty account details.</p>
        </header>

        <!-- ── PANEL: PROFILE & FACULTY DETAILS ── -->
        <div id="tab-profile" class="spanel active">
            <div class="ts-card ts-profile-summary">
                <div class="ts-card-head">
                    <div class="ts-card-icon"><i class="bi bi-person-bounding-box" aria-hidden="true"></i></div>
                    <div>
                        <div class="ts-card-title">Profile Photo</div>
                        <div class="ts-card-sub">Manage your avatar and account appearance</div>
                    </div>
                </div>
                <div class="ts-card-body">
                    <div class="ts-profile-inner">
                        <x-profile-photo-manager :user="$teacher" :size="96" avatar-id="teacherAvatarDisplay" />

                        <div class="ts-profile-details">
                            <div class="ts-user-name">{{ $teacher->name }}</div>
                            <div class="ts-user-id">
                                <i class="bi bi-person-badge" aria-hidden="true"></i>
                                <span>{{ $teacher->employee_id ?: $teacher->email }}</span>
                            </div>
                            <div class="ts-badges-row">
                                <span class="ts-role-pill"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i> Faculty</span>
                                @if($teacher->department)
                                    <span class="ts-role-pill">{{ $teacher->department }}</span>
                                @endif
                                @if($teacher->position)
                                    <span class="ts-role-pill">{{ $teacher->position }}</span>
                                @endif
                            </div>
                            <div>
                                <label for="ppmFileInput" class="ts-photo-change-btn">
                                    <i class="bi bi-camera-fill" aria-hidden="true"></i>
                                    <span>Change photo</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ts-card ts-faculty-details">
                <div class="ts-card-head">
                    <div class="ts-card-icon"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i></div>
                    <div>
                        <div class="ts-card-title">Faculty Information</div>
                        <div class="ts-card-sub">Verified institutional faculty record</div>
                    </div>
                </div>
                <div class="ts-card-body">
                    <div class="ts-spec-grid">
                        <div class="ts-spec-tile">
                            <div class="ts-spec-label">Full Name</div>
                            <div class="ts-spec-value"><span>{{ $teacher->name }}</span></div>
                        </div>

                        <div class="ts-spec-tile">
                            <div class="ts-spec-label">Employee ID</div>
                            <div class="ts-spec-value">
                                <span>{{ $teacher->employee_id ?: 'Not Assigned' }}</span>
                                @if($teacher->employee_id)
                                    <button type="button"
                                            class="ts-copy-btn"
                                            data-copy-text="{{ $teacher->employee_id }}"
                                            data-copy-label="Employee ID"
                                            title="Copy Employee ID"
                                            aria-label="Copy Employee ID">
                                        <i class="bi bi-clipboard" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="ts-spec-tile">
                            <div class="ts-spec-label">Primary Email</div>
                            <div class="ts-spec-value">
                                <span>{{ $teacher->email }}</span>
                                <button type="button"
                                        class="ts-copy-btn"
                                        data-copy-text="{{ $teacher->email }}"
                                        data-copy-label="Email address"
                                        title="Copy Email Address"
                                        aria-label="Copy Email Address">
                                    <i class="bi bi-clipboard" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="ts-spec-tile">
                            <div class="ts-spec-label">Department</div>
                            <div class="ts-spec-value"><span>{{ $teacher->department ?: 'Not Assigned' }}</span></div>
                        </div>

                        <div class="ts-spec-tile">
                            <div class="ts-spec-label">Position</div>
                            <div class="ts-spec-value"><span>{{ $teacher->position ?: 'Faculty Member' }}</span></div>
                        </div>

                        <div class="ts-spec-tile">
                            <div class="ts-spec-label">Assigned Classes</div>
                            <div class="ts-spec-value"><span>{{ $assignedCount }} {{ \Illuminate\Support\Str::plural('class', $assignedCount) }}</span></div>
                        </div>

                        <div class="ts-spec-tile">
                            <div class="ts-spec-label">Member Since</div>
                            <div class="ts-spec-value"><span>{{ $teacher->created_at?->format('F j, Y') ?: '—' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── PANEL: PASSWORD & EMAIL ── -->
        <div id="tab-credentials" class="spanel">
            <div class="ts-account-shortcuts" id="teacherCredentialShortcuts" hidden>
                <article class="ts-account-shortcut">
                    <span class="ts-account-shortcut-icon"><i class="bi bi-envelope-at" aria-hidden="true"></i></span>
                    <span class="ts-account-shortcut-copy">
                        <strong>Email address</strong>
                        <span>{{ $teacher->email }}<br>Used for sign-in, security codes, and faculty alerts.</span>
                    </span>
                    <button type="button" data-account-page="change-email">Change</button>
                </article>
                <article class="ts-account-shortcut">
                    <span class="ts-account-shortcut-icon"><i class="bi bi-key" aria-hidden="true"></i></span>
                    <span class="ts-account-shortcut-copy">
                        <strong>Password</strong>
                        <span>Change your password after verifying a one-time code sent to your email.</span>
                    </span>
                    <button type="button" data-account-page="change-password">Change</button>
                </article>
            </div>

            <div id="teacherCredentialFormGrid">
                <!-- Change Email Card -->
                <div class="ts-card" data-account-edit-source="email">
                    <div class="ts-card-head">
                        <div class="ts-card-icon"><i class="bi bi-envelope-check-fill" aria-hidden="true"></i></div>
                        <div>
                            <div class="ts-card-title">Email address</div>
                            <div class="ts-card-sub">Used for account recovery, security codes, and notifications.</div>
                        </div>
                    </div>
                    <div class="ts-card-body">
                        <ol class="ts-edit-progress" id="emailEditProgress" aria-label="Email change steps">
                            <li aria-current="step" data-account-step="1"><span class="ts-step-number">1</span><span>New email</span></li>
                            <li data-account-step="2"><span class="ts-step-number">2</span><span>Verify code</span></li>
                        </ol>
                        <div id="emailFormStatus" class="ts-edit-status" role="status" aria-live="polite" aria-atomic="true" hidden></div>

                        <div id="emailStep1">
                            <div class="ts-input-display">
                                <i class="bi bi-envelope-at-fill text-warning" aria-hidden="true"></i>
                                <span class="ts-input-val">{{ $teacher->email }}</span>
                                <button type="button"
                                        class="ts-copy-btn"
                                        data-copy-text="{{ $teacher->email }}"
                                        data-copy-label="Email address"
                                        title="Copy email"
                                        aria-label="Copy current email address">
                                    <i class="bi bi-clipboard" aria-hidden="true"></i>
                                </button>
                            </div>
                            <label class="ts-label" for="inputNewEmail">New email address</label>
                            <input type="email"
                                   id="inputNewEmail"
                                   class="ts-input"
                                   placeholder="name@gmail.com"
                                   value="{{ old('new_email') }}"
                                   autocomplete="email"
                                   inputmode="email"
                                   maxlength="254"
                                   required>
                            <p class="ts-help">Enter a Gmail address you can open. We&rsquo;ll send a one-time code there to verify the change.</p>
                            <button type="button" id="sendEmailOtpBtn" class="ts-btn ts-btn-gold">
                                <i class="bi bi-send-fill" aria-hidden="true"></i>
                                <span>Send verification code</span>
                            </button>
                        </div>

                        <div id="emailStep2" style="display:none;">
                            <div class="ts-status-tile" role="status">
                                <i class="bi bi-envelope-check-fill" aria-hidden="true"></i>
                                <span>Code sent to <strong id="emailSentDestination">{{ old('new_email', $teacher->email) }}</strong></span>
                            </div>
                            <form action="{{ route('otp.email.change') }}" method="POST">
                                @csrf
                                <label class="ts-label" for="ed1">6-digit verification code</label>
                                <div class="ts-otp-grid" role="group" aria-label="Email verification code">
                                    @for($j = 1; $j <= 6; $j++)
                                        <input type="text"
                                               class="email-otp-digit ts-otp-input"
                                               maxlength="1"
                                               inputmode="numeric"
                                               pattern="[0-9]"
                                               id="ed{{ $j }}"
                                               aria-label="Digit {{ $j }} of 6"
                                               @if($j === 1) autocomplete="one-time-code" @endif
                                               required>
                                    @endfor
                                </div>
                                <p class="ts-help">Enter the 6-digit code sent to your new email. You can paste all six digits into the first box.</p>
                                <input type="hidden" name="otp" id="emailOtpHidden">

                                <label class="ts-label" for="confirmNewEmailInput">New email address</label>
                                <input type="email"
                                       name="new_email"
                                       id="confirmNewEmailInput"
                                       class="ts-input"
                                       placeholder="name@gmail.com"
                                       value="{{ old('new_email') }}"
                                       readonly
                                       required>

                                <div class="ts-form-actions">
                                    <button type="button" id="cancelEmailOtpBtn" class="ts-btn ts-btn-ghost">Back</button>
                                    <button type="button" id="verifyEmailOtpBtn" class="ts-btn ts-btn-gold">
                                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                        <span>Verify and update email</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password Card -->
                <div class="ts-card" data-account-edit-source="password">
                    <div class="ts-card-head">
                        <div class="ts-card-icon"><i class="bi bi-key-fill" aria-hidden="true"></i></div>
                        <div>
                            <div class="ts-card-title">Password</div>
                            <div class="ts-card-sub">Use a strong, unique password to protect your faculty account.</div>
                        </div>
                    </div>
                    <div class="ts-card-body">
                        <ol class="ts-edit-progress" id="passwordEditProgress" aria-label="Password change steps">
                            <li aria-current="step" data-account-step="1"><span class="ts-step-number">1</span><span>Request code</span></li>
                            <li data-account-step="2"><span class="ts-step-number">2</span><span>Verify &amp; update</span></li>
                        </ol>
                        <div id="passwordFormStatus" class="ts-edit-status" role="status" aria-live="polite" aria-atomic="true" hidden></div>

                        <div id="otpStep1">
                            <div class="ts-status-tile">
                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                <span>Your current password stays private</span>
                            </div>
                            <p class="ts-help">We&rsquo;ll send a 6-digit verification code to <strong>{{ $teacher->email }}</strong> before saving your new password.</p>
                            <button type="button" id="sendPasswordOtpBtn" class="ts-btn ts-btn-gold">
                                <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                                <span>Send verification code</span>
                            </button>
                        </div>

                        <div id="otpStep2" style="display:none;">
                            <div class="ts-status-tile" role="status">
                                <i class="bi bi-envelope-check-fill" aria-hidden="true"></i>
                                <span>Code sent to <strong>{{ $teacher->email }}</strong></span>
                            </div>
                            <form action="{{ route('otp.change') }}" method="POST">
                                @csrf
                                <label class="ts-label" for="sd1">6-digit verification code</label>
                                <div class="ts-otp-grid" role="group" aria-label="Password verification code">
                                    @for($i = 1; $i <= 6; $i++)
                                        <input type="text"
                                               class="otp-digit-s ts-otp-input"
                                               maxlength="1"
                                               inputmode="numeric"
                                               pattern="[0-9]"
                                               id="sd{{ $i }}"
                                               aria-label="Digit {{ $i }} of 6"
                                               @if($i === 1) autocomplete="one-time-code" @endif
                                               required>
                                    @endfor
                                </div>
                                <input type="hidden" name="otp" id="settingsOtpHidden">

                                <div class="mb-3 mt-3">
                                    <label class="ts-label" for="tpw1">New password</label>
                                    <div class="ts-pw-wrap">
                                        <input type="password"
                                               name="password"
                                               id="tpw1"
                                               class="ts-input"
                                               placeholder="12+ characters with mixed case, number, and symbol"
                                               minlength="12"
                                               autocomplete="new-password"
                                               required>
                                        <button type="button"
                                                class="ts-eye-btn"
                                                data-toggle-password="tpw1"
                                                aria-controls="tpw1"
                                                aria-label="Show password"
                                                title="Show password"
                                                aria-pressed="false">
                                            <i class="bi bi-eye-slash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="ts-label" for="tpw2">Confirm new password</label>
                                    <div class="ts-pw-wrap">
                                        <input type="password"
                                               name="password_confirmation"
                                               id="tpw2"
                                               class="ts-input"
                                               placeholder="Repeat new password"
                                               minlength="12"
                                               autocomplete="new-password"
                                               required>
                                        <button type="button"
                                                class="ts-eye-btn"
                                                data-toggle-password="tpw2"
                                                aria-controls="tpw2"
                                                aria-label="Show password confirmation"
                                                title="Show password confirmation"
                                                aria-pressed="false">
                                            <i class="bi bi-eye-slash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="ts-form-actions">
                                    <button type="button" id="cancelPasswordOtpBtn" class="ts-btn ts-btn-ghost">Back</button>
                                    <button type="button" id="verifyPasswordOtpBtn" class="ts-btn ts-btn-gold">
                                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                        <span>Verify and update password</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── SUB-PANELS FOR PROGRESSIVE CREDENTIAL FLOWS ── -->
        <div id="tab-account-email" class="spanel ts-account-edit-panel" aria-label="Change email address">
            <div class="ts-account-edit-intro">Enter a Gmail address you can open. We&rsquo;ll send a verification code there before changing your account email.</div>
            <div id="teacherAccountEmailSlot"></div>
        </div>

        <div id="tab-account-password" class="spanel ts-account-edit-panel" aria-label="Change password">
            <div class="ts-account-edit-intro">We&rsquo;ll send a security code to your current email. Enter it and choose a strong new password.</div>
            <div id="teacherAccountPasswordSlot"></div>
        </div>

        <!-- ── PANEL: DEVICE PASSKEYS ── -->
        <div id="tab-passkeys" class="spanel">
            <div class="ts-card">
                <div class="ts-card-head">
                    <div class="ts-card-icon"><i class="bi bi-fingerprint" aria-hidden="true"></i></div>
                    <div>
                        <div class="ts-card-title">Registered Device Passkeys</div>
                        <div class="ts-card-sub">Use your device&rsquo;s built-in biometrics or screen lock to sign in securely</div>
                    </div>
                </div>
                <div class="ts-card-body">
                    <div id="webauthnUnsupported" role="status" class="ts-flash ts-flash-err" style="display:none;" data-alert-required>
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                        <div id="webauthnUnsupportedMsg">Passkey setup is unavailable on this device.</div>
                    </div>

                    <div id="deviceList" class="mb-3">
                        <div id="noDevices" class="ts-passkey-empty">
                            <i class="bi bi-shield-lock" aria-hidden="true"></i>
                            <div>No active passkeys registered on this account yet.</div>
                        </div>
                    </div>

                    <button type="button" id="registerFpBtn" disabled class="ts-btn ts-btn-emerald">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                        <span>Set up a passkey</span>
                    </button>
                    <div id="fpMessage" style="margin-top:12px;font-size:.84rem;display:none;" role="status" aria-live="polite"></div>
                </div>
            </div>
        </div>

        <!-- ── PANEL: RECOVERY CODES ── -->
        <div id="tab-recovery" class="spanel">
            <div class="ts-card">
                <div class="ts-card-head">
                    <div class="ts-card-icon"><i class="bi bi-safe-fill" aria-hidden="true"></i></div>
                    <div>
                        <div class="ts-card-title">Backup Recovery Codes</div>
                        <div class="ts-card-sub">One-time offline keys to restore access if credentials or devices are lost</div>
                    </div>
                </div>
                <div class="ts-card-body">
                    <div class="ts-status-tile">
                        <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                        <span>Generating new keys immediately invalidates all previous recovery codes</span>
                    </div>
                    <p class="ts-help">Store generated codes safely in a secure password manager or offline notes.</p>
                    <button type="button" id="generateCodesBtn" class="ts-btn ts-btn-gold">
                        <i class="bi bi-key-fill" aria-hidden="true"></i>
                        <span>Generate Recovery Codes</span>
                    </button>

                    <div id="recoveryCodesList" class="ts-recovery-vault" style="display:none;">
                        <div style="font-size:.82rem;font-weight:700;color:#fda4af;display:flex;align-items:center;gap:8px;">
                            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                            <span>Save these codes in a safe place. They will only be shown once.</span>
                        </div>
                        <div id="codesContainer" class="ts-recovery-grid"></div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" id="copyAllRecoveryBtn" class="ts-btn ts-btn-gold">
                                <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                                <span>Copy All</span>
                            </button>
                            <button type="button" id="downloadRecoveryBtn" class="ts-btn ts-btn-ghost">
                                <i class="bi bi-download" aria-hidden="true"></i>
                                <span>Download TXT</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── PANEL: NOTIFICATION PREFERENCES ── -->
        <div id="tab-preferences" class="spanel">
            <div class="ts-card">
                <div class="ts-card-head">
                    <div class="ts-card-icon"><i class="bi bi-sliders" aria-hidden="true"></i></div>
                    <div>
                        <div class="ts-card-title">Notification Preferences</div>
                        <div class="ts-card-sub">Choose how you receive faculty alerts and adjust your sidebar layout</div>
                    </div>
                </div>
                <div class="ts-card-body">
                    <form action="{{ route('teacher.profile.update') }}" method="POST" id="teacherPreferencesForm">
                        @csrf
                        <div class="ts-pref-section-lbl">Notification Alerts</div>

                        <div class="ts-pref-tile">
                            <div class="ts-pref-left">
                                <div class="ts-pref-icon"><i class="bi bi-envelope" aria-hidden="true"></i></div>
                                <div>
                                    <label class="ts-pref-title" for="notifEmailToggle">Email Notifications</label>
                                    <div class="ts-pref-sub">Receive excuse submission notices, attendance summaries, and security updates via email</div>
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notifEmailToggle" name="notif_email" value="1" {{ $emailNotif ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="ts-pref-tile">
                            <div class="ts-pref-left">
                                <div class="ts-pref-icon"><i class="bi bi-bell" aria-hidden="true"></i></div>
                                <div>
                                    <label class="ts-pref-title" for="notifPushToggle">Push Notifications</label>
                                    <div class="ts-pref-sub">Receive real-time portal alerts for student requests and live sessions</div>
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notifPushToggle" name="notif_push" value="1" {{ $pushNotif ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="ts-pref-section-lbl mt-4">Display &amp; Layout</div>

                        <div class="ts-pref-tile">
                            <div class="ts-pref-left">
                                <div class="ts-pref-icon"><i class="bi bi-layout-sidebar" aria-hidden="true"></i></div>
                                <div>
                                    <label class="ts-pref-title" for="compactToggle">Compact Sidebar</label>
                                    <div class="ts-pref-sub">Keep sidebar collapsed on desktop for extra workspace width</div>
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="compactToggle">
                            </div>
                        </div>

                        <div class="ts-form-actions justify-content-end">
                            <button type="submit" class="ts-btn ts-btn-gold">
                                <i class="bi bi-save" aria-hidden="true"></i>
                                <span>Save Preferences</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

@php
    $teacherSettingsConfig = [
        'csrfToken' => csrf_token(),
        'userEmail' => $teacher->email,
        'routes' => [
            'otpChangeSend' => route('otp.change.send'),
            'otpEmailSend' => route('otp.email.send'),
            'recoveryGenerate' => route('recovery.generate'),
        ],
    ];
@endphp
<script @cspNonce>
window.__TEACHER_SETTINGS_CONFIG__ = @json($teacherSettingsConfig);
</script>
@include('partials.profile-device-passkey-capability')
<script src="{{ asset('js/teacher-settings.js') }}?v={{ filemtime(public_path('js/teacher-settings.js')) }}" @cspNonce></script>
@endsection
