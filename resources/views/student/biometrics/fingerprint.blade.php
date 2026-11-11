@extends('layouts.biometric-flow')

@section('title', 'Fingerprint registration')
@section('description', 'Set up secure sign-in on this device. Your device may offer a PIN or another verification method.')

@section('content')
<section class="biometric-flow-card" aria-label="Register this device">
    <div id="bioIdleView">
        <button type="button" id="startBioBtn" class="flow-button flow-primary">Register fingerprint</button>
    </div>
    <div id="bioScanningView" style="display:none" role="status">
        <h2 id="fpScanningTitle">Confirm on your device</h2>
        <p id="fpStatusSub">Follow the secure prompt to register your device.</p>
        <button type="button" id="cancelBioBtn" class="flow-button">Cancel registration</button>
    </div>
    <div id="bioSuccessView" style="display:none" role="status">
        <h2 id="bioSuccessTitle">Device registered</h2>
        <p id="bioSuccessDesc"></p>
        <a href="{{ route('settings') }}#tab-biometrics" class="flow-button">Done</a>
    </div>
    <div id="bioErrorView" style="display:none" role="status">
        <h2 id="bioErrorTitle">Registration could not finish</h2>
        <p id="bioErrorDesc"></p>
        <button type="button" id="retryBtn" class="flow-button">Try again</button>
    </div>
</section>
<section class="biometric-flow-card" aria-labelledby="registeredDevicesTitle">
    <h2 id="registeredDevicesTitle">Registered devices</h2>
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
