@php
    $prefs = $user->notification_preferences ?? ['in_app' => true, 'email' => true];
    $linkedParentsCount = isset($linkedParents) ? $linkedParents->count() : 0;
    $activePasskeysCount = isset($activePasskeys) ? $activePasskeys->count() : 0;
    $hasFace = !empty($hasCameraFaceEnrollment);
    $activeBioCount = ($activePasskeysCount > 0 ? 1 : 0) + ($hasFace ? 1 : 0);
    $installedVer = app(\App\Services\VersionService::class)->getInstalledVersion();
    $academicLine = collect([
        $user->course,
        $user->year_level ? 'Year ' . $user->year_level : null,
        $user->semester ? 'Sem ' . $user->semester : null,
    ])->filter()->implode(' · ');
@endphp

<div class="sf-settings-container sf-settings-root student-settings"
     id="studentSettingsContent"
     data-student-settings="{{ $user->id }}"
     data-student-view="profile"
     data-mobile-view="hub"
     data-settings-feedback="{{ $errors->any() || session('success') ? 'true' : 'false' }}"
     data-settings-phone-error="{{ $errors->has('phone') ? 'true' : 'false' }}"
     data-settings-phone-error-message="{{ $errors->first('phone') }}"
     data-settings-email-error="{{ $errors->has('new_email') || $errors->has('otp') ? 'true' : 'false' }}"
     data-settings-email-address-error="{{ $errors->has('new_email') ? 'true' : 'false' }}"
     data-settings-email-error-message="{{ $errors->first('new_email') ?: $errors->first('otp') }}"
     data-settings-password-error="{{ $errors->has('password') || $errors->has('password_confirmation') || $errors->has('otp') ? 'true' : 'false' }}"
     data-settings-password-error-message="{{ $errors->first('password') ?: $errors->first('password_confirmation') ?: $errors->first('otp') }}">

    @if(session('success'))
        <div class="sf-flash is-success" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="sf-flash is-error" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Mobile Back-to-Hub Trigger --}}
    <button type="button" class="sf-mobile-settings-back" id="sfMobileSettingsBackBtn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        <span>Back to Settings</span>
    </button>

    {{-- Mobile iOS-Style Grouped Settings Hub --}}
    <div class="sf-mobile-settings-hub" aria-label="Settings menu">
        <div>
            <div class="sf-mobile-settings-group-label">Account</div>
            <div class="sf-mobile-settings-group">
                <button type="button" class="sf-mobile-settings-row" data-settings-page="profile">
                    <span class="sf-mobile-settings-row-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                        <span>Profile</span>
                    </span>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sf-text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
                <button type="button" class="sf-mobile-settings-row" data-settings-page="family">
                    <span class="sf-mobile-settings-row-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Guardians</span>
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:6px;">
                        <span class="sf-mono" style="font-size:11.5px;color:var(--sf-text-muted);">{{ $linkedParentsCount }}</span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sf-text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </button>
            </div>
        </div>

        <div>
            <div class="sf-mobile-settings-group-label">Security &amp; Attendance</div>
            <div class="sf-mobile-settings-group">
                <button type="button" class="sf-mobile-settings-row" data-settings-page="credentials">
                    <span class="sf-mobile-settings-row-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <span>Password &amp; email</span>
                    </span>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sf-text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
                <button type="button" class="sf-mobile-settings-row" data-settings-page="biometrics">
                    <span class="sf-mobile-settings-row-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4"/><path d="M14 13.12c0 2.38 0 6.38-1 8.88"/><path d="M2 12a10 10 0 0 1 18-6"/><path d="M9 6.8a6 6 0 0 1 9 5.2v2"/></svg>
                        <span>Biometrics</span>
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:6px;">
                        <span class="sf-mono" style="font-size:11.5px;color:var(--sf-text-muted);">{{ $activeBioCount }}/2</span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sf-text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </button>
                <button type="button" class="sf-mobile-settings-row" data-settings-page="device">
                    <span class="sf-mobile-settings-row-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                        <span>Attendance device</span>
                    </span>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sf-text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
                <button type="button" class="sf-mobile-settings-row" data-settings-page="recovery">
                    <span class="sf-mobile-settings-row-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                        <span>Recovery codes</span>
                    </span>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sf-text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
        </div>

        <div>
            <div class="sf-mobile-settings-group-label">App</div>
            <div class="sf-mobile-settings-group">
                <button type="button" class="sf-mobile-settings-row" data-settings-page="preferences">
                    <span class="sf-mobile-settings-row-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/></svg>
                        <span>Notifications &amp; app</span>
                    </span>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sf-text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ── PANEL 1: PROFILE ── --}}
    <section class="sf-settings-panel spanel active" id="tab-profile" data-panel-key="profile">
        <div class="sf-settings-head">
            <div>
                <h2 id="studentSettingsPageTitle">Profile</h2>
                <p>Manage your photo, contact number, and review your student information</p>
            </div>
        </div>

        {{-- Profile Hero Card --}}
        <div class="sf-card sf-profile-hero">
            <div class="sf-profile-hero-left">
                <div class="sf-avatar-upload-wrap">
                    <x-profile-photo-manager :user="$user" :size="68" avatar-id="settingsAvatarDisplay" :show-overlay-trigger="false" />
                    <label for="ppmFileInput" class="sf-avatar-cam-btn" title="Change photo" aria-label="Change photo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                    </label>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:16px;font-weight:700;color:var(--sf-text);">{{ $user->name }}</div>
                    @if($academicLine !== '')
                        <div style="font-size:12.5px;color:var(--sf-text-secondary);">{{ $academicLine }}</div>
                    @endif
                    <div style="font-size:12px;color:var(--sf-text-muted);margin-top:1px;">{{ $user->email }}</div>
                </div>
            </div>
            @if($user->student_number)
                <button type="button"
                        class="sf-btn sf-btn-secondary sf-btn-sm sf-mono"
                        onclick="navigator.clipboard?.writeText('{{ $user->student_number }}'); window.showToast?.('Student ID copied!', 'info');"
                        title="Copy Student ID"
                        aria-label="Copy Student ID">
                    <span>ID {{ $user->student_number }}</span>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                </button>
            @endif
        </div>

        {{-- Personal Details (Phone Form) --}}
        <div class="sf-card">
            <div class="sf-card-head">
                <h3 class="sf-card-title">Personal details</h3>
                <span style="font-size:11.5px;color:var(--sf-text-muted);">School contact number</span>
            </div>
            <form action="{{ route('settings.update') }}" method="POST" id="personalInfoForm">
                @csrf
                <div class="sf-card-body">
                    <label class="sf-label" for="phoneInput">Mobile Phone Number</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <input type="tel"
                               id="phoneInput"
                               name="phone"
                               class="sf-input"
                               style="flex:1;min-width:200px;"
                               value="{{ old('phone', $user->phone) }}"
                               placeholder="+63 900 000 0000"
                               inputmode="tel"
                               autocomplete="tel"
                               maxlength="20"
                               pattern="[0-9+\s\-]{0,20}">
                        <button type="submit" id="savePhoneBtn" class="sf-btn sf-btn-primary">Save changes</button>
                    </div>
                    <div style="font-size:11.5px;color:var(--sf-text-muted);margin-top:6px;">
                        Include country code if needed. Leave blank to clear your saved number.
                    </div>
                    <div id="phoneFormStatus" role="status" aria-live="polite" style="margin-top:8px;font-size:12px;color:var(--sf-rose);" hidden></div>
                </div>
            </form>
        </div>

        {{-- Academic Record --}}
        <div class="sf-card">
            <div class="sf-card-head">
                <h3 class="sf-card-title">Academic record</h3>
                <span class="sf-pill">Managed by Registrar</span>
            </div>
            <div class="sf-academic-grid">
                <div class="sf-academic-cell">
                    <small>Student ID</small>
                    <strong class="sf-mono">{{ $user->student_number ?: 'Not Assigned' }}</strong>
                </div>
                <div class="sf-academic-cell">
                    <small>Program</small>
                    <strong>{{ $user->course ?: '—' }}</strong>
                </div>
                <div class="sf-academic-cell">
                    <small>Year Level</small>
                    <strong>{{ $user->year_level ? 'Year ' . $user->year_level : '—' }}</strong>
                </div>
                <div class="sf-academic-cell">
                    <small>Semester</small>
                    <strong>{{ $user->semester ? 'Semester ' . $user->semester : '—' }}</strong>
                </div>
            </div>
        </div>
    </section>

    {{-- ── PANEL 2: GUARDIANS ── --}}
    <section class="sf-settings-panel spanel" id="tab-family" data-panel-key="family">
        <div class="sf-settings-head">
            <div>
                <h2>Guardians</h2>
                <p>Manage who can view your attendance summaries and absence notices</p>
            </div>
            <span class="sf-pill {{ $linkedParentsCount > 0 ? 'is-emerald' : '' }}">
                {{ $linkedParentsCount > 0 ? $linkedParentsCount . ' linked' : 'None linked' }}
            </span>
        </div>

        {{-- Instant Parent Link Code Card --}}
        <div class="sf-card">
            <div class="sf-card-head">
                <h3 class="sf-card-title">Link a parent or guardian</h3>
                <span class="sf-pill is-rose">15-min code</span>
            </div>
            <div class="sf-card-body">
                <p style="margin:0 0 12px;font-size:12.5px;color:var(--sf-text-secondary);">
                    Generate a temporary 6-digit linking code and QR. Your guardian enters it inside the Parent Portal under <strong>Link Student</strong>.
                </p>

                <div id="parentCodeDisplayCard" style="display:none;padding:16px;border-radius:12px;background:var(--sf-surface-2);border:1px solid var(--sf-border-accent);text-align:center;margin-bottom:14px;">
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--sf-rose);margin-bottom:8px;">6-Digit Linking Code</div>
                    <div style="display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:8px;">
                        <div id="parentCodeValue" class="sf-mono" style="font-size:26px;font-weight:700;letter-spacing:6px;padding:6px 14px;border-radius:10px;background:var(--sf-surface);border:1px solid var(--sf-border-strong);color:var(--sf-text);">
                            ------
                        </div>
                        <button type="button" class="sf-btn sf-btn-secondary sf-btn-sm" onclick="copyParentLinkCode(this)">Copy</button>
                    </div>
                    <div id="parentCodeTimer" class="sf-mono" style="font-size:12px;color:var(--sf-emerald);margin-bottom:12px;">Valid for: 15:00</div>
                    <div style="display:inline-block;padding:10px;background:#ffffff;border-radius:10px;">
                        <div id="parentCodeQrBox" style="width:160px;height:160px;display:flex;align-items:center;justify-content:center;"></div>
                    </div>
                </div>

                <button type="button" id="generateParentCodeBtn" onclick="generateParentLinkCode()" class="sf-btn sf-btn-primary">
                    Generate Parent Link Code
                </button>
            </div>
        </div>

        {{-- Linked Guardians List --}}
        <div class="sf-card">
            <div class="sf-card-head">
                <h3 class="sf-card-title">Authorized guardians</h3>
                <span class="sf-pill">View-only access</span>
            </div>
            <div class="sf-card-body">
                @if(isset($linkedParents) && $linkedParents->isNotEmpty())
                    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px;">
                        @foreach($linkedParents as $guardian)
                            <div id="guardian-item-{{ $guardian->id }}" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 12px;border-radius:10px;background:var(--sf-surface-2);border:1px solid var(--sf-border);">
                                <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                                    <img src="{{ $guardian->profile_photo_url }}" alt="{{ $guardian->name }}" style="width:36px;height:36px;border-radius:9px;object-fit:cover;">
                                    <div style="min-width:0;">
                                        <div style="font-size:13px;font-weight:600;color:var(--sf-text);">{{ $guardian->name }}</div>
                                        <div style="font-size:11.5px;color:var(--sf-text-muted);">{{ $guardian->email }}{{ $guardian->phone ? ' · ' . $guardian->phone : '' }}</div>
                                    </div>
                                </div>
                                <span class="sf-pill is-emerald">Linked</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align:center;padding:22px 12px;border-radius:10px;border:1px dashed var(--sf-border-strong);margin-bottom:12px;">
                        <div style="font-size:13px;font-weight:600;color:var(--sf-text);margin-bottom:3px;">No guardians connected</div>
                        <div style="font-size:12px;color:var(--sf-text-muted);">Generate a link code above to connect your parent or guardian.</div>
                    </div>
                @endif

                <div style="padding:10px 12px;border-radius:9px;background:var(--sf-surface-2);border:1px solid var(--sf-border);font-size:11.5px;color:var(--sf-text-secondary);line-height:1.5;">
                    <strong style="color:var(--sf-text);">Privacy safeguard:</strong> Guardians can view attendance and subject summaries. Students cannot remove a guardian link; a guardian or school administrator can manage it.
                </div>
            </div>
        </div>
    </section>

    {{-- ── PANEL 3: PASSWORD & EMAIL ── --}}
    <section class="sf-settings-panel spanel" id="tab-credentials" data-panel-key="credentials">
        <div class="sf-settings-head">
            <div>
                <h2>Password &amp; email</h2>
                <p>Verify changes to your account email address or password with a one-time security code</p>
            </div>
        </div>

        {{-- Campus Email Card --}}
        <div class="sf-card">
            <div class="sf-card-head">
                <h3 class="sf-card-title">Account email</h3>
                <span class="sf-pill is-emerald">Verified</span>
            </div>
            <div class="sf-card-body">
                <div id="emailFormStatus" role="status" aria-live="polite" style="margin-bottom:10px;font-size:12px;color:var(--sf-rose);" hidden></div>

                <div id="emailStep1">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:9px;background:var(--sf-surface-2);border:1px solid var(--sf-border);margin-bottom:12px;">
                        <span class="sf-mono" id="displayUserEmail" style="font-size:12.5px;color:var(--sf-text);">{{ $user->email }}</span>
                        <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm" onclick="navigator.clipboard?.writeText('{{ $user->email }}'); window.showToast?.('Email copied!', 'info');">Copy</button>
                    </div>
                    <label class="sf-label" for="inputNewEmail">New Gmail address</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <input type="email" id="inputNewEmail" class="sf-input" style="flex:1;min-width:200px;" placeholder="name@gmail.com" value="{{ old('new_email') }}" autocomplete="email" maxlength="254" required>
                        <button type="button" onclick="requestEmailOtp()" id="sendEmailOtpBtn" class="sf-btn sf-btn-primary">Send code</button>
                    </div>
                    <div style="font-size:11.5px;color:var(--sf-text-muted);margin-top:6px;">
                        We'll send a 6-digit verification code to the new Gmail address before applying the change.
                    </div>
                </div>

                <div id="emailStep2" style="display:none;">
                    <div class="sf-flash is-success" style="margin-bottom:12px;">
                        Code sent to <strong id="emailSentDestination" style="margin-left:4px;">{{ old('new_email', $user->email) }}</strong>
                    </div>
                    <form action="{{ route('otp.email.change') }}" method="POST">
                        @csrf
                        <label class="sf-label" for="ed1">6-digit verification code</label>
                        <div class="sf-otp-row" role="group" aria-label="Email verification code">
                            @for($j = 1; $j <= 6; $j++)
                                <input type="text" class="email-otp-digit sf-otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ed{{ $j }}" aria-label="Digit {{ $j }} of 6" @if($j === 1) autocomplete="one-time-code" @endif required>
                            @endfor
                        </div>
                        <input type="hidden" name="otp" id="emailOtpHidden">
                        <label class="sf-label" for="confirmNewEmailInput">New email address</label>
                        <input type="email" name="new_email" id="confirmNewEmailInput" class="sf-input" value="{{ old('new_email') }}" readonly required style="margin-bottom:12px;">
                        <div style="display:flex;justify-content:flex-end;gap:8px;">
                            <button type="button" onclick="cancelEmailOtp()" class="sf-btn sf-btn-ghost">Back</button>
                            <button type="button" class="sf-btn sf-btn-primary" onclick="collectEmailOtp(this)">Verify &amp; update email</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Change Password Card --}}
        <div class="sf-card">
            <div class="sf-card-head">
                <h3 class="sf-card-title">Change password</h3>
                <span class="sf-pill">OTP Protected</span>
            </div>
            <div class="sf-card-body">
                <div id="passwordFormStatus" role="status" aria-live="polite" style="margin-bottom:10px;font-size:12px;color:var(--sf-rose);" hidden></div>

                <div id="otpStep1">
                    <p style="margin:0 0 12px;font-size:12.5px;color:var(--sf-text-secondary);">
                        We'll send a one-time security code to <strong style="color:var(--sf-text);">{{ $user->email }}</strong> so you can set a strong new password.
                    </p>
                    <button type="button" onclick="requestOtp()" id="sendOtpBtn" class="sf-btn sf-btn-primary">
                        Send verification code
                    </button>
                </div>

                <div id="otpStep2" style="display:none;">
                    <div class="sf-flash is-success" style="margin-bottom:12px;">
                        Code sent to <strong style="margin-left:4px;">{{ $user->email }}</strong>
                    </div>
                    <form action="{{ route('otp.change') }}" method="POST">
                        @csrf
                        <label class="sf-label" for="sd1">6-digit verification code</label>
                        <div class="sf-otp-row" role="group" aria-label="Password verification code">
                            @for($i = 1; $i <= 6; $i++)
                                <input type="text" class="otp-digit-s sf-otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" id="sd{{ $i }}" aria-label="Digit {{ $i }} of 6" @if($i === 1) autocomplete="one-time-code" @endif required>
                            @endfor
                        </div>
                        <input type="hidden" name="otp" id="settingsOtpHidden">

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                            <div>
                                <label class="sf-label" for="spw1">New password</label>
                                <input type="password" name="password" id="spw1" class="sf-input" minlength="12" placeholder="Min 12 characters" autocomplete="new-password" required>
                            </div>
                            <div>
                                <label class="sf-label" for="spw2">Confirm password</label>
                                <input type="password" name="password_confirmation" id="spw2" class="sf-input" minlength="12" placeholder="Repeat password" autocomplete="new-password" required>
                            </div>
                        </div>
                        <div style="font-size:11.5px;color:var(--sf-text-muted);margin-bottom:12px;">
                            Use at least 12 characters with uppercase, lowercase, a number, and a symbol.
                        </div>
                        <div style="display:flex;justify-content:flex-end;gap:8px;">
                            <button type="button" onclick="cancelOtp()" class="sf-btn sf-btn-ghost">Back</button>
                            <button type="button" class="sf-btn sf-btn-primary" onclick="collectOtp(this)">Update password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- ── PANEL 4: BIOMETRICS ── --}}
    <section class="sf-settings-panel spanel" id="tab-biometrics" data-panel-key="biometrics">
        <div class="sf-settings-head">
            <div>
                <h2>Biometrics</h2>
                <p>Configure WebAuthn device passkeys and facial verification for classroom check-ins</p>
            </div>
            <span class="sf-pill {{ $activeBioCount > 0 ? 'is-emerald' : '' }} sf-mono">{{ $activeBioCount }} of 2 active</span>
        </div>

        <div class="sf-bio-grid" id="studentBiometricChoices">
            <div class="sf-card sf-bio-card">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="sf-bio-icon {{ $activePasskeysCount > 0 ? 'is-emerald' : 'is-rose' }}">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4"/><path d="M14 13.12c0 2.38 0 6.38-1 8.88"/><path d="M2 12a10 10 0 0 1 18-6"/><path d="M9 6.8a6 6 0 0 1 9 5.2v2"/></svg>
                    </span>
                    <span class="sf-pill {{ $activePasskeysCount > 0 ? 'is-emerald' : '' }}">
                        {{ $activePasskeysCount > 0 ? $activePasskeysCount . ' registered' : 'Not registered' }}
                    </span>
                </div>
                <div>
                    <h3 style="margin:0 0 4px;font-size:14.5px;font-weight:700;color:var(--sf-text);">Fingerprint / Device Passkey</h3>
                    <p style="margin:0;font-size:12px;color:var(--sf-text-secondary);line-height:1.5;">
                        Use Touch ID, Face ID, or Android Biometrics via WebAuthn for fast passwordless sign-in and attendance checks.
                    </p>
                </div>
                @if(isset($activePasskeys) && $activePasskeys->isNotEmpty())
                    <div style="display:flex;flex-direction:column;gap:5px;">
                        @foreach($activePasskeys->take(3) as $pk)
                            <div style="font-size:11.5px;color:var(--sf-text-soft);padding:6px 9px;border-radius:7px;background:var(--sf-surface-2);border:1px solid var(--sf-border);">
                                {{ $pk->device_name ?: 'Registered Passkey' }}
                            </div>
                        @endforeach
                    </div>
                @endif
                <a id="studentFingerprintRegistrationLink" href="{{ route('settings.biometrics.fingerprint') }}" class="sf-btn sf-btn-primary sf-btn-block" style="margin-top:auto;">
                    {{ $activePasskeysCount > 0 ? 'Manage Passkeys' : 'Register Fingerprint' }}
                </a>
            </div>

            <div class="sf-card sf-bio-card">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="sf-bio-icon {{ $hasFace ? 'is-emerald' : 'is-rose' }}">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><circle cx="12" cy="11" r="3"/><path d="M7 17c1-2 3-3 5-3s4 1 5 3"/></svg>
                    </span>
                    <span class="sf-pill {{ $hasFace ? 'is-emerald' : '' }}">
                        {{ $hasFace ? 'Enrolled' : 'Not enrolled' }}
                    </span>
                </div>
                <div>
                    <h3 style="margin:0 0 4px;font-size:14.5px;font-weight:700;color:var(--sf-text);">Face Verification</h3>
                    <p style="margin:0;font-size:12px;color:var(--sf-text-secondary);line-height:1.5;">
                        Enroll an encrypted 3-pose facial template for high-assurance attendance and classmate Peer Snap backup verification.
                    </p>
                </div>
                <a id="studentFaceRegistrationLink" href="{{ route('settings.biometrics.face') }}" class="sf-btn sf-btn-secondary sf-btn-block" style="margin-top:auto;">
                    {{ $hasFace ? 'Manage Face Enrollment' : 'Register Face' }}
                </a>
            </div>
        </div>
    </section>

    {{-- ── PANEL 5: ATTENDANCE DEVICE ── --}}
    <section class="sf-settings-panel spanel" id="tab-device" data-panel-key="device">
        <div class="sf-settings-head">
            <div>
                <h2>Attendance device</h2>
                <p>Anti-proxy hardware binding restricts attendance check-ins to your personal device</p>
            </div>
            <span class="sf-pill {{ $deviceBinding ? ($deviceBinding->isLocked() ? 'is-danger' : 'is-emerald') : '' }}" id="deviceTabAuthorizedBadge">
                <span id="deviceTabAuthorizedBadgeText">
                    @if(!$deviceBinding)
                        Unregistered
                    @elseif($deviceBinding->isLocked())
                        Locked
                    @else
                        Authorized
                    @endif
                </span>
            </span>
        </div>

        <div id="deviceLockedAlert" class="sf-flash is-error" style="display:{{ ($deviceBinding && $deviceBinding->isLocked()) ? 'flex' : 'none' }};justify-content:space-between;flex-wrap:wrap;">
            <div>
                <strong>Attendance Device Locked</strong>
                <div id="deviceLockedReasonText" style="font-size:12px;opacity:0.9;">
                    {{ $deviceBinding?->locked_reason ? 'Reason: ' . $deviceBinding->locked_reason : 'Check-ins are paused until you unlock this device.' }}
                </div>
            </div>
            <button type="button" id="tabDeviceUnlockBtn" onclick="handleUnlockDevice()" class="sf-btn sf-btn-primary sf-btn-sm">Unlock Device</button>
        </div>

        <div class="sf-card">
            <div class="sf-card-head">
                <h3 class="sf-card-title">Registered hardware</h3>
                <span class="sf-pill is-emerald" id="currentDeviceMatchBadge" style="display:none;">Authorized</span>
            </div>
            <div class="sf-card-body">
                <div id="deviceTabBoundTile" style="display:{{ $deviceBinding ? 'block' : 'none' }};">
                    <div class="sf-drawer-info-grid" style="margin-bottom:14px;">
                        <div class="sf-info-tile">
                            <small>Device Model</small>
                            <strong id="deviceTabBoundName">{{ $deviceBinding?->device_name ?: 'Registered Device' }}</strong>
                            <span id="deviceTabBoundTime">{{ $deviceBinding?->last_seen_at ? $deviceBinding->last_seen_at->diffForHumans() : 'Recently active' }}</span>
                        </div>
                        <div class="sf-info-tile">
                            <small>Trust &amp; Network</small>
                            <strong id="deviceTabBoundTrust" style="color:var(--sf-emerald);">{{ $deviceBinding ? ($deviceBinding->getTrustLevel() . ' (' . ($deviceBinding->trust_score ?? 85) . '/100)') : '—' }}</strong>
                            <span class="sf-mono"><span id="deviceTabBoundIp">{{ $deviceBinding?->ip_address ?: '—' }}</span> <span id="deviceTabBoundChanges">({{ (int) ($deviceBinding?->change_count ?? 0) }} switches)</span></span>
                        </div>
                    </div>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" onclick="handleBindCurrentDevice()" id="tabDeviceBindBtn" class="sf-btn sf-btn-primary">
                            <span id="tabDeviceBindBtnText">Re-verify Current Device</span>
                        </button>
                        <button type="button" onclick="handleLockDevice()" id="tabDeviceLockBtn" class="sf-btn sf-btn-secondary">
                            Freeze / Lock
                        </button>
                        <button type="button" onclick="handleUnbindDevice()" id="tabDeviceUnbindBtn" class="sf-btn sf-btn-danger">
                            Unbind
                        </button>
                    </div>
                </div>

                <div id="deviceTabUnboundTile" style="display:{{ $deviceBinding ? 'none' : 'block' }};text-align:center;padding:24px 16px;border-radius:11px;border:1px dashed var(--sf-border-strong);">
                    <div style="font-size:14px;font-weight:700;color:var(--sf-text);margin-bottom:4px;">No Device Bound Yet</div>
                    <p style="margin:0 0 14px;font-size:12px;color:var(--sf-text-muted);">
                        Bind this phone or computer to authorize verified QR &amp; PIN attendance check-ins.
                    </p>
                    <button type="button" onclick="handleBindCurrentDevice()" class="sf-btn sf-btn-primary">
                        Bind to This Device
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- ── PANEL 6: RECOVERY CODES ── --}}
    <section class="sf-settings-panel spanel" id="tab-recovery" data-panel-key="recovery">
        <div class="sf-settings-head">
            <div>
                <h2>Recovery codes</h2>
                <p>One-time offline emergency keys to restore access if you lose your device</p>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <button type="button" onclick="downloadRecoveryCodes()" class="sf-btn sf-btn-ghost sf-btn-sm">Download</button>
                <button type="button" onclick="copyAllRecoveryCodes(this)" class="sf-btn sf-btn-secondary sf-btn-sm">Copy all</button>
                <button type="button" onclick="generateRecoveryCodes()" id="generateCodesBtn" class="sf-btn sf-btn-primary sf-btn-sm">Regenerate</button>
            </div>
        </div>

        <div class="sf-card">
            <div class="sf-card-body" style="display:flex;flex-direction:column;gap:12px;">
                <div style="padding:10px 12px;border-radius:9px;background:var(--sf-amber-bg);border:1px solid var(--sf-amber-border);color:var(--sf-amber-text);font-size:12px;">
                    Generating new recovery codes immediately invalidates all previous keys. Currently stored unused codes: <strong class="sf-mono">{{ $unusedRecoveryCodesCount ?? 0 }}</strong>.
                </div>

                <div id="recoveryCodesList" style="display:none;">
                    <div style="font-size:12px;font-weight:600;color:var(--sf-danger);margin-bottom:8px;">
                        Save these codes now — they are only displayed once.
                    </div>
                    <div id="codesContainer" class="sf-recovery-grid"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── PANEL 7: NOTIFICATIONS & APP ── --}}
    <section class="sf-settings-panel spanel" id="tab-preferences" data-panel-key="preferences">
        <div class="sf-settings-head">
            <div>
                <h2>Notifications &amp; app</h2>
                <p>Customize portal appearance, alert channels, and software updates</p>
            </div>
        </div>

        {{-- Appearance Theme Picker --}}
        <div class="sf-card">
            <div class="sf-card-head">
                <h3 class="sf-card-title">Appearance</h3>
                <span style="font-size:11.5px;color:var(--sf-text-muted);">Saved per browser</span>
            </div>
            <div class="sf-card-body">
                <div class="sf-theme-picker" role="group" aria-label="Portal theme">
                    <button type="button" class="sf-theme-option active" data-theme-choice="dark" aria-pressed="true">
                        <div class="sf-theme-preview is-dark" aria-hidden="true">
                            <div class="sf-theme-preview-row">
                                <span class="sf-theme-preview-dot"></span>
                                <span class="sf-theme-preview-bar"></span>
                            </div>
                            <span class="sf-theme-preview-accent"></span>
                        </div>
                        <div class="sf-theme-option-foot">
                            <span>Dark Oxblood</span>
                            <span class="sf-theme-active-tag">Active</span>
                        </div>
                    </button>

                    <button type="button" class="sf-theme-option" data-theme-choice="light" aria-pressed="false">
                        <div class="sf-theme-preview is-light" aria-hidden="true">
                            <div class="sf-theme-preview-row">
                                <span class="sf-theme-preview-dot"></span>
                                <span class="sf-theme-preview-bar"></span>
                            </div>
                            <span class="sf-theme-preview-accent"></span>
                        </div>
                        <div class="sf-theme-option-foot">
                            <span>Light Porcelain</span>
                            <span class="sf-theme-active-tag">Active</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        {{-- Alert Preferences Form --}}
        <div class="sf-card">
            <div class="sf-card-head">
                <h3 class="sf-card-title">Alert preferences</h3>
            </div>
            <form action="{{ route('settings.preferences.update') }}" method="POST">
                @csrf
                <div>
                    <div class="sf-setting-row">
                        <label for="preferenceInApp" style="cursor:pointer;flex:1;">
                            <strong>In-App Notifications</strong>
                            <span>Show badges and activity alerts inside the student portal</span>
                        </label>
                        <label class="sf-switch">
                            <input type="checkbox" id="preferenceInApp" name="prefs[in_app]" value="1" {{ !empty($prefs['in_app']) ? 'checked' : '' }}>
                            <span class="sf-switch-track"></span>
                        </label>
                    </div>

                    <div class="sf-setting-row">
                        <label for="preferenceEmail" style="cursor:pointer;flex:1;">
                            <strong>Email Notifications</strong>
                            <span>Receive attendance summaries, excuse decisions, and security codes via email</span>
                        </label>
                        <label class="sf-switch">
                            <input type="checkbox" id="preferenceEmail" name="prefs[email]" value="1" {{ !empty($prefs['email']) ? 'checked' : '' }}>
                            <span class="sf-switch-track"></span>
                        </label>
                    </div>

                    <div class="sf-setting-row">
                        <label for="preferenceSms" style="cursor:pointer;flex:1;">
                            <strong>SMS urgent alerts</strong>
                            <span>Emergency campus notices and attendance threshold warnings</span>
                        </label>
                        <label class="sf-switch">
                            <input type="checkbox" id="preferenceSms" name="prefs[sms]" value="1" {{ !empty($prefs['sms']) ? 'checked' : '' }}>
                            <span class="sf-switch-track"></span>
                        </label>
                    </div>

                    <div class="sf-setting-row sf-desktop-only">
                        <label for="compactToggle" style="cursor:pointer;flex:1;">
                            <strong>Compact sidebar</strong>
                            <span>Keep the desktop workspace sidebar collapsed for more screen width</span>
                        </label>
                        <label class="sf-switch">
                            <input type="checkbox" id="compactToggle">
                            <span class="sf-switch-track"></span>
                        </label>
                    </div>
                </div>
                <div style="padding:12px 16px;border-top:1px solid var(--sf-border);display:flex;justify-content:flex-end;">
                    <button type="submit" class="sf-btn sf-btn-primary">Save preferences</button>
                </div>
            </form>
        </div>

        {{-- App Updates Bar --}}
        <div class="sf-card">
            <div class="sf-card-body" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                        <strong style="font-size:13px;color:var(--sf-text);">App updates</strong>
                        <span class="sf-pill is-rose sf-mono">v{{ $installedVer }}</span>
                    </div>
                    <div id="updateStatusText" style="font-size:11.5px;color:var(--sf-text-muted);">
                        Check for the latest software features and offline assets.
                    </div>
                </div>
                <button type="button" id="checkUpdateBtn" onclick="checkForAppUpdates()" class="sf-btn sf-btn-secondary sf-btn-sm">
                    Check Updates
                </button>
            </div>
            <div id="updateFeedbackArea" style="display:none;margin:0 16px 14px;padding:11px 13px;border-radius:10px;font-size:12px;line-height:1.45;"></div>
        </div>
    </section>
</div>
