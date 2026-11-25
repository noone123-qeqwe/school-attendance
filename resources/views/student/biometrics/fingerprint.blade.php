@extends('layouts.biometric-flow')

@section('title', 'Device passkey registration')
@section('description', 'Register a device passkey for secure passwordless sign-in.')
@section('pageClass', 'fingerprint-registration-page')

@section('content')
<section class="biometric-flow-card" aria-label="Register this device">
    <ol class="bio-registration-steps" aria-label="Registration steps">
        <li data-bio-step="1" aria-current="step"><span>1</span><span>Start</span></li>
        <li data-bio-step="2"><span>2</span><span>Confirm</span></li>
        <li data-bio-step="3"><span>3</span><span>Finish</span></li>
    </ol>
    <div id="bioIdleView">
        <div class="bio-flow-kicker"><i class="bi bi-shield-lock" aria-hidden="true"></i> Biometric sign-in</div>
        <div class="bio-flow-title-row">
            <span class="bio-flow-symbol" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
            <div>
                <h2>Register a device passkey</h2>
                <p>Use your device’s supported verification to link a passkey to your account.</p>
            </div>
        </div>
        <p class="bio-privacy-note"><i class="bi bi-lock" aria-hidden="true"></i><span>Your device verifies you locally. The app saves a public key for sign-in.</span></p>
        <button type="button" id="startBioBtn" class="flow-button flow-primary" aria-describedby="devicePasskeyStatus" disabled><i class="bi bi-shield-lock" aria-hidden="true"></i> Register a passkey</button>
        <p id="devicePasskeyStatus" class="biometric-flow-status" role="status">Checking biometric support…</p>
    </div>
    <div id="bioScanningView" style="display:none" aria-live="polite" aria-atomic="false">
        <div class="bio-flow-waiting" aria-hidden="true"><span class="biometric-spinner"></span></div>
        <h2 id="fpScanningTitle">Confirm on your device</h2>
        <p id="fpStatusSub">Follow your device’s prompt to finish passkey registration.</p>
        <button type="button" id="confirmBioBtn" class="flow-button flow-primary" hidden>Continue to device prompt</button>
        <button type="button" id="cancelBioBtn" class="flow-button"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancel</button>
    </div>
    <div id="bioSuccessView" style="display:none" role="status" aria-live="polite">
        <div class="bio-flow-result-icon is-success" aria-hidden="true"><i class="bi bi-check-lg"></i></div>
        <h2 id="bioSuccessTitle">Passkey registered</h2>
        <p>Your device passkey is linked to your account.</p>
        <a href="{{ route('settings') }}#tab-biometrics" class="flow-button flow-primary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Done</a>
    </div>
    <div id="bioErrorView" style="display:none" role="alert">
        <div class="bio-error-heading">
            <div class="bio-flow-result-icon is-error" aria-hidden="true"><i class="bi bi-exclamation-lg"></i></div>
            <h2 id="bioErrorTitle">Registration could not finish</h2>
        </div>
        <p id="bioErrorDesc"></p>
        <div class="bio-error-actions">
            <button type="button" id="retryBtn" class="flow-button flow-primary"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Try again</button>
        </div>
    </div>
</section>
<section class="biometric-flow-card" aria-labelledby="registeredDevicesTitle">
    <div class="bio-section-heading"><div><h2 id="registeredDevicesTitle">Registered devices</h2><p>Sign-in methods linked to your account.</p></div><i class="bi bi-laptop" aria-hidden="true"></i></div>
    <p id="deviceCountBadge" class="device-count" role="status">Loading devices…</p>
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
