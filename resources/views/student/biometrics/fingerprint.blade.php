@extends('layouts.biometric-flow')

@section('title', 'Fingerprint registration')
@section('description', 'Set up secure sign-in on this device. Your device may offer a PIN or another verification method.')
@section('pageClass', 'fingerprint-registration-page')

@section('content')
<section class="biometric-flow-card" aria-label="Register this device">
    <ol class="bio-registration-steps" aria-label="Registration steps">
        <li data-bio-step="1" aria-current="step"><span>1</span><span>Start</span></li>
        <li data-bio-step="2"><span>2</span><span>Confirm</span></li>
        <li data-bio-step="3"><span>3</span><span>Finish</span></li>
    </ol>
    <div id="bioIdleView">
        <div class="bio-flow-kicker"><i class="bi bi-shield-lock" aria-hidden="true"></i> Device-protected sign-in</div>
        <div class="bio-flow-title-row">
            <span class="bio-flow-symbol" aria-hidden="true"><i class="bi bi-fingerprint"></i></span>
            <div>
                <h2>Fingerprint sign-in</h2>
                <p>Use your device’s secure prompt to add a quick sign-in method.</p>
            </div>
        </div>
        <p class="bio-privacy-note"><i class="bi bi-lock" aria-hidden="true"></i><span>Your fingerprint or face stays on your device. The app saves a secure sign-in key.</span></p>
        <button type="button" id="startBioBtn" class="flow-button flow-primary"><i class="bi bi-fingerprint" aria-hidden="true"></i> Register fingerprint</button>
    </div>
    <div id="bioScanningView" style="display:none" aria-live="polite" aria-atomic="false">
        <div class="bio-flow-waiting" aria-hidden="true"><span class="biometric-spinner"></span></div>
        <h2 id="fpScanningTitle">Confirm on your device</h2>
        <p id="fpStatusSub">Follow the secure prompt to register your device. Your device may ask for a PIN.</p>
        <button type="button" id="cancelBioBtn" class="flow-button"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancel</button>
    </div>
    <div id="bioSuccessView" style="display:none" role="status" aria-live="polite">
        <div class="bio-flow-result-icon is-success" aria-hidden="true"><i class="bi bi-check-lg"></i></div>
        <h2 id="bioSuccessTitle">Device registered</h2>
        <p id="bioSuccessDesc"></p>
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
<script @cspNonce>
@include('partials.device-biometric-registration-script')

document.getElementById('startBioBtn').addEventListener('click', beginSelectedBiometricRegistration);
document.getElementById('retryBtn').addEventListener('click', retryBiometricRegistration);
document.getElementById('cancelBioBtn').addEventListener('click', cancelBiometricRegistration);
window.addEventListener('pagehide', cancelBiometricRegistration);
loadDevices();
</script>
@endpush
