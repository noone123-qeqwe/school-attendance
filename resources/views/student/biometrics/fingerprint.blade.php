@extends('layouts.biometric-flow')

@section('title', 'Device Biometric & Passkey Registration')
@section('description', 'Link this device’s built-in platform authenticator (WebAuthn/FIDO2) for secure sign-in and classroom attendance verification.')
@section('pageClass', 'fingerprint-registration-page')

@section('content')
<section class="biometric-flow-card" aria-label="Register this device">
    <ol class="bio-registration-steps" aria-label="Registration steps">
        <li data-bio-step="1" aria-current="step"><span>1</span><span>Consent &amp; Check</span></li>
        <li data-bio-step="2"><span>2</span><span>Device Prompt</span></li>
        <li data-bio-step="3"><span>3</span><span>Verified</span></li>
    </ol>

    <div id="bioIdleView">
        <div class="bio-flow-kicker"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i> WebAuthn / FIDO2 Passkey</div>
        <div class="bio-flow-title-row">
            <span class="bio-flow-symbol" aria-hidden="true"><i class="bi bi-shield-lock" id="bioMethodHeroIcon"></i></span>
            <div>
                <h2 id="bioRegistrationHeading">Register this device</h2>
                <p id="bioRegistrationLead">Verify your identity using your operating system’s built-in biometric sensor or screen lock to create a secure passkey for this account.</p>
            </div>
        </div>

        <div class="bio-sensor-stage" id="bioCapabilityStage" data-state="checking">
            <span class="bio-state-pill" id="bioStatePill" data-tone="info" role="status">
                <span class="biometric-spinner" id="bioCapabilitySpinner" aria-hidden="true"></span>
                <span id="bioStatePillText">Checking device compatibility…</span>
            </span>
            <div class="bio-sensor-pad" id="bioSensorPad" aria-hidden="true">
                <i class="bi bi-shield-lock" id="bioSensorPadIcon"></i>
            </div>
            <p class="bio-sensor-caption" id="bioCapabilityTitle">Checking platform authenticator support</p>
            <p class="bio-sensor-sub" id="bioCapabilityDesc">Detecting whether this browser and operating system support built-in biometric or passkey verification.</p>
        </div>

        <div id="bioUnsupportedPanel" class="bio-alt-auth-box" hidden role="status">
            <strong><i class="bi bi-info-circle-fill me-1" aria-hidden="true"></i> Alternative authentication options</strong>
            <span id="bioUnsupportedDetail">This browser or device cannot create a platform passkey right now. You can continue using your account password or enroll in live camera face verification for attendance.</span>
            <div class="bio-alt-auth-links">
                <a href="{{ route('settings.biometrics.face') }}" class="flow-button">
                    <i class="bi bi-camera-video" aria-hidden="true"></i> Set up Face Verification
                </a>
            </div>
        </div>

        <div id="bioRegistrationControls">
            <div class="bio-field-group">
                <label class="bio-field-label" for="bioDeviceNameInput">Device name (optional)</label>
                <input type="text" id="bioDeviceNameInput" class="bio-text-input" maxlength="80" autocomplete="off" placeholder="e.g., My Phone or Study Laptop">
            </div>

            <label class="bio-consent-card" for="bioConsentCheckbox">
                <input type="checkbox" id="bioConsentCheckbox">
                <span>
                    <strong>I consent to registering this device</strong><br>
                    Your operating system verifies your identity locally (using fingerprint, facial recognition, or screen lock/PIN depending on your hardware). Smart Attendance receives and stores <strong>only a cryptographic public key</strong> — never raw fingerprint images or biometric templates. You can revoke this credential at any time.
                </span>
            </label>

            <button type="button" id="startBioBtn" class="flow-button flow-primary" aria-describedby="devicePasskeyStatus" disabled>
                <i class="bi bi-shield-check" id="startBioBtnIcon" aria-hidden="true"></i>
                <span id="startBioBtnText">Register this device</span>
            </button>
            <p id="devicePasskeyStatus" class="biometric-flow-status" role="status">Checking device compatibility…</p>
        </div>
    </div>

    <div id="bioScanningView" style="display:none" aria-live="polite" aria-atomic="false">
        <div class="bio-sensor-stage" data-state="awaiting">
            <span class="bio-state-pill" id="bioActiveStatePill" data-tone="awaiting">
                <span class="biometric-spinner" aria-hidden="true"></span>
                <span id="bioActiveStateLabel">Awaiting device authentication</span>
            </span>
            <div class="bio-sensor-pad is-awaiting" aria-hidden="true">
                <i class="bi bi-shield-lock" id="bioScanningIcon"></i>
            </div>
            <h2 id="fpScanningTitle" style="margin-bottom:6px;">Complete your device prompt</h2>
            <p id="fpStatusSub" style="margin:0;">Your operating system is handling verification. Follow the native prompt shown on your screen.</p>
        </div>
        <button type="button" id="confirmBioBtn" class="flow-button flow-primary" hidden>Open device authentication prompt</button>
        <button type="button" id="cancelBioBtn" class="flow-button">
            <i class="bi bi-x-lg" aria-hidden="true"></i> Cancel registration
        </button>
    </div>

    <div id="bioSuccessView" style="display:none" role="status" aria-live="polite">
        <div class="bio-flow-result-icon is-success" aria-hidden="true"><i class="bi bi-check-lg"></i></div>
        <h2 id="bioSuccessTitle">Device credential registered</h2>
        <p id="bioSuccessDesc">Server-side cryptographic verification succeeded. You can now use this device to sign in and verify classroom attendance.</p>
        <div class="biometric-flow-actions">
            <a href="{{ route('settings') }}#tab-biometrics" class="flow-button flow-primary">
                <i class="bi bi-check2" aria-hidden="true"></i> Done
            </a>
            <button type="button" id="doneBioBtn" class="flow-button">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Register another device
            </button>
        </div>
    </div>

    <div id="bioErrorView" style="display:none" role="alert">
        <div class="bio-error-heading">
            <div class="bio-flow-result-icon is-error" id="bioErrorIconWrap" aria-hidden="true"><i class="bi bi-exclamation-lg" id="bioErrorIcon"></i></div>
            <h2 id="bioErrorTitle">Registration could not finish</h2>
        </div>
        <p id="bioErrorDesc"></p>
        <div class="bio-error-actions biometric-flow-actions">
            <button type="button" id="retryBtn" class="flow-button flow-primary">
                <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Try again
            </button>
            <button type="button" id="backToIdleBtn" class="flow-button">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Back
            </button>
        </div>
    </div>
</section>

<section class="biometric-flow-card" aria-labelledby="registeredDevicesTitle">
    <div class="bio-section-heading">
        <div>
            <h2 id="registeredDevicesTitle">Registered devices</h2>
            <p>Active WebAuthn public-key credentials linked to your account. Revoking a device immediately prevents it from signing in.</p>
        </div>
        <i class="bi bi-laptop" aria-hidden="true"></i>
    </div>
    <p id="deviceCountBadge" class="device-count" role="status">Loading devices…</p>
    <p id="deviceActionStatus" class="biometric-flow-status" role="status" hidden></p>
    <div id="deviceList"></div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('js/device-passkey-capabilities.js') }}?v={{ filemtime(public_path('js/device-passkey-capabilities.js')) }}" @cspNonce></script>
<script @cspNonce>
@include('partials.device-biometric-registration-script')

initializeDevicePasskeyRegistration();
</script>
@endpush
