@extends('layouts.app')
@section('content')
<style>
    .verify-wrapper {
        display: flex; align-items: center; justify-content: center;
        min-height: calc(100vh - 64px); padding: 20px;
        background: linear-gradient(135deg, #f8f0f0 0%, #f1f5f9 50%, #f0f4ff 100%);
    }
    .verify-card {
        max-width: 380px; width: 100%; border-radius: 24px;
        padding: 32px 28px; background: white;
        box-shadow: 0 20px 60px rgba(0,0,0,0.1); text-align: center;
    }
    .subject-pill {
        background: #fff5f5; border: 1.5px solid #fecaca;
        border-radius: 12px; padding: 12px 16px; margin-bottom: 24px; text-align: left;
    }
    .subject-pill-label { font-size: .65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
    .subject-pill-name { font-size: .95rem; font-weight: 700; color: #1e293b; margin-top: 3px; }

    /* Step dots */
    .step-row { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 28px; }
    .sdot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; transition: all .4s; }
    .sdot.done    { background: #16a34a; color: white; }
    .sdot.active  { background: #800000; color: white; box-shadow: 0 0 0 4px rgba(128,0,0,.15); }
    .sdot.pending { background: #f1f5f9; color: #94a3b8; }
    .sline { flex: 1; height: 2px; background: #f1f5f9; max-width: 40px; transition: background .4s; }
    .sline.done { background: #16a34a; }

    /* Status icon */
    .v-icon {
        width: 80px; height: 80px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 16px; font-size: 2rem; transition: all .4s;
    }
    .v-title { font-size: 1.15rem; font-weight: 800; color: #1e293b; margin-bottom: 6px; }
    .v-sub   { font-size: .85rem; color: #64748b; line-height: 1.5; margin-bottom: 0; }

    /* Status messages */
    .v-msg { border-radius: 10px; padding: 10px 14px; font-size: .82rem; margin-top: 16px; display: none; }
    .v-ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
    .v-err { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
    .v-info{ background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }

    /* Retry button â€” only shown on fingerprint error */
    .retry-btn {
        width: 100%; padding: 13px; margin-top: 14px;
        background: linear-gradient(135deg, #16a34a, #22c55e);
        color: white; font-weight: 700; font-size: .9rem;
        border: none; border-radius: 12px; cursor: pointer;
        display: none; align-items: center; justify-content: center; gap: 8px;
        transition: all .25s;
    }
    .retry-btn:hover { transform: translateY(-2px); }

    /* Spinner */
    .spin { display: inline-block; width: 20px; height: 20px; border: 3px solid rgba(255,255,255,.3); border-top-color: white; border-radius: 50%; animation: spin .7s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<div class="verify-wrapper">
    <div class="verify-card">

        {{-- Subject info --}}
        <div class="subject-pill">
            <div class="subject-pill-label">Clocking in for</div>
            <div class="subject-pill-name">{{ $subject->name }}</div>
            <div style="font-size:.75rem;color:#64748b;margin-top:2px;">
                <i class="bi bi-clock me-1"></i>
                @if($subject->start_time)
                    {{ \Carbon\Carbon::parse($subject->start_time)->format('h:i A') }}
                @endif
                &nbsp;Â·&nbsp; {{ $subject->code }}
            </div>
        </div>

        {{-- Step indicators --}}
        <div class="step-row">
            <div class="sdot done"    id="dot1"><i class="bi bi-check2"></i></div>
            <div class="sline done"   id="line1"></div>
            <div class="sdot active"  id="dot2">2</div>
            <div class="sline"        id="line2"></div>
            <div class="sdot pending" id="dot3">3</div>
        </div>

        {{-- Dynamic content area --}}
        <div id="vIcon"  class="v-icon" style="background:#eff6ff;">
            <i class="bi bi-geo-alt-fill" style="color:#2563eb;"></i>
        </div>
        <div class="v-title" id="vTitle">Checking Location</div>
        <div class="v-sub"   id="vSub">Verifying you are inside the classroom...</div>
        <div class="v-msg"   id="vMsg"></div>

        {{-- Retry fingerprint button (shown only on fp error) --}}
        <button class="retry-btn" id="retryFpBtn" onclick="doFingerprint()">
            <i class="bi bi-fingerprint"></i> Try Fingerprint Again
        </button>

        {{-- Hidden form --}}
        <form id="attendanceForm" action="{{ route('qr.confirm') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="latitude" id="latInput">
            <input type="hidden" name="longitude" id="lngInput">
            <input type="hidden" name="accuracy" id="accuracyInput">
            <input type="hidden" name="credential" id="credentialInput">
        </form>
    </div>
</div>

<script>
var STUDENT_NUMBER = '{{ addslashes(Auth::user()->student_number) }}';
var QR_TOKEN = '{{ addslashes($token) }}';
var CSRF = '{{ csrf_token() }}';
var CLASSROOM_LAT = {{ isset($classroomLat) && $classroomLat !== null ? (float) $classroomLat : 'null' }};
var CLASSROOM_LNG = {{ isset($classroomLng) && $classroomLng !== null ? (float) $classroomLng : 'null' }};
var RADIUS_METERS = {{ isset($radiusMeters) && $radiusMeters !== null ? (int) $radiusMeters : 50 }};

var fingerprintInProgress = false; // Add guard against multiple simultaneous calls

function calculateDistance(lat1, lon1, lat2, lon2) {
    var R = 6371000; // meters
    var dLat = (lat2 - lat1) * Math.PI / 180;
    var dLon = (lon2 - lon1) * Math.PI / 180;
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function setIcon(bg, iconClass, iconColor) {
    var el = document.getElementById('vIcon');
    el.style.background = bg;
    el.innerHTML = '<i class="' + iconClass + '" style="color:' + iconColor + ';font-size:2rem;"></i>';
}
function setSpinner() {
    var el = document.getElementById('vIcon');
    el.style.background = '#fff5f5';
    el.innerHTML = '<div style="width:36px;height:36px;border:4px solid rgba(128,0,0,.15);border-top-color:#800000;border-radius:50%;animation:spin .7s linear infinite;"></div>';
}
function showMsg(type, text) {
    var el = document.getElementById('vMsg');
    el.className = 'v-msg ' + (type === 'ok' ? 'v-ok' : type === 'err' ? 'v-err' : 'v-info');
    el.innerHTML = text;
    el.style.display = 'block';
}
function hideMsg() { document.getElementById('vMsg').style.display = 'none'; }
function setStep(step) {
    if (step >= 3) {
        document.getElementById('dot2').className = 'sdot done';
        document.getElementById('dot2').innerHTML = '<i class="bi bi-check2"></i>';
        document.getElementById('line2').className = 'sline done';
        document.getElementById('dot3').className = step >= 4 ? 'sdot done' : 'sdot active';
        if (step >= 4) document.getElementById('dot3').innerHTML = '<i class="bi bi-check2"></i>';
        else document.getElementById('dot3').textContent = '3';
    }
}

let geoRetryDowngraded = false;
let locationWatcher = null;
let locationTimeoutId = null;
let fastFallbackTimer = null;
let bestLocation = null;
let bestAccuracy = Infinity;
let firstLocationReceived = false;

const GPS_TIMEOUT_MS = 12000;            // Total timeout for the higher-accuracy retry
const GPS_FAST_FALLBACK_MS = 3000;       // Accept a good enough location quickly
const GPS_QUICK_ACCEPT = 45;             // Immediately accept strong location fixes
const GPS_MAX_FAST_ACCEPT = 85;          // Accept a decent location after a short wait
const GPS_MAX_ACCEPTABLE_ACCURACY = 2500; // Accept weaker signal after a full timeout
const GPS_HARD_ACCURACY_LIMIT = 5000;     // Reject only if accuracy is worse than this

window.addEventListener('load', function() { startGPS(); });

function startGPS() {
    geoRetryDowngraded = false;
    bestLocation = null;
    bestAccuracy = Infinity;
    if (locationWatcher !== null) {
        navigator.geolocation.clearWatch(locationWatcher);
        locationWatcher = null;
    }
    if (locationTimeoutId) {
        clearTimeout(locationTimeoutId);
        locationTimeoutId = null;
    }

    setSpinner();
    document.getElementById('vTitle').textContent = 'Checking Location';
    document.getElementById('vSub').textContent = 'Verifying you are inside the classroom...';
    hideMsg();
    document.getElementById('retryFpBtn').style.display = 'none';

    if (fastFallbackTimer) {
        clearTimeout(fastFallbackTimer);
        fastFallbackTimer = null;
    }

    if (!navigator.geolocation) {
        setIcon('#fef2f2', 'bi bi-geo-alt-fill', '#dc2626');
        document.getElementById('vTitle').textContent = 'GPS Not Available';
        document.getElementById('vSub').textContent = 'Your device does not support location services.';
        showMsg('err', 'Location access is required to clock in.');
        return;
    }

    // Start with a higher-accuracy request to avoid IP-based or network-only location errors.
    requestLocation({ timeout: 10000, enableHighAccuracy: true, maximumAge: 5000 });
}

function requestLocation(options) {
    if (locationWatcher !== null) {
        navigator.geolocation.clearWatch(locationWatcher);
        locationWatcher = null;
    }
    if (locationTimeoutId) {
        clearTimeout(locationTimeoutId);
        locationTimeoutId = null;
    }
    if (fastFallbackTimer) {
        clearTimeout(fastFallbackTimer);
        fastFallbackTimer = null;
    }

    bestLocation = null;
    bestAccuracy = Infinity;

    var acceptBestLocation = function(pos, accuracyLabel) {
        if (locationWatcher !== null) {
            navigator.geolocation.clearWatch(locationWatcher);
            locationWatcher = null;
        }
        if (locationTimeoutId) {
            clearTimeout(locationTimeoutId);
            locationTimeoutId = null;
        }

        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;
        var accuracy = pos.coords.accuracy || 0;

        console.log('GPS Accepted:', lat, lng, 'Accuracy:', accuracy + 'm');
        document.getElementById('latInput').value = lat;
        document.getElementById('lngInput').value = lng;
        document.getElementById('accuracyInput').value = accuracy;

        // Check if student is within classroom geofence
        if (CLASSROOM_LAT !== null && CLASSROOM_LNG !== null) {
            var dist = calculateDistance(lat, lng, CLASSROOM_LAT, CLASSROOM_LNG);
            console.log('Classroom distance check:', dist + 'm, limit:', RADIUS_METERS + 'm');
            
            if (dist > RADIUS_METERS) {
                showOutsideClassroomError(dist, RADIUS_METERS);
                return;
            }
        }

        setIcon('#f0fdf4', 'bi bi-geo-alt-fill', '#16a34a');
        setStep(3);
        document.getElementById('vTitle').textContent = 'Inside Classroom';
        document.getElementById('vSub').textContent = 'Location verified. Verifying fingerprint...';
        showMsg('ok', '<i class="bi bi-check-circle me-1"></i> You are inside the classroom (' + accuracyLabel + ').');
        setTimeout(function() { doFingerprint(); }, 800);
    };

    var onSuccess = function(pos) {
        console.log('GPS Success:', pos.coords.latitude, pos.coords.longitude, 'Accuracy:', pos.coords.accuracy + 'm');

        if (pos.coords.accuracy < bestAccuracy) {
            bestAccuracy = pos.coords.accuracy;
            bestLocation = pos;
        }

        if (pos.coords.accuracy <= GPS_QUICK_ACCEPT) {
            acceptBestLocation(pos, 'Location confirmed');
            return;
        }

        if (pos.coords.accuracy <= GPS_MAX_FAST_ACCEPT) {
            showMsg('info', '<i class="bi bi-info-circle"></i> Good location detected. Finalizing shortly...');
            return;
        }

        if (pos.coords.accuracy <= GPS_MAX_ACCEPTABLE_ACCURACY) {
            showMsg('info', '<i class="bi bi-info-circle"></i> Weak location detected (' + Math.round(pos.coords.accuracy) + 'm). Finalizing after a short wait...');
            return;
        }

        showMsg('info', '<i class="bi bi-info-circle"></i> Very weak location (' + Math.round(pos.coords.accuracy) + 'm). We will still keep trying, but if this persists try moving near a window or enabling GPS.');
    };

    var onError = function(err) {
        if (locationWatcher !== null) {
            navigator.geolocation.clearWatch(locationWatcher);
            locationWatcher = null;
        }
        if (locationTimeoutId) {
            clearTimeout(locationTimeoutId);
            locationTimeoutId = null;
        }

        if (!geoRetryDowngraded && (err.code === 3 || err.code === 2)) {
            geoRetryDowngraded = true;
            console.warn('Low-accuracy geolocation failed; retrying with higher accuracy.');
            showMsg('info', '<i class="bi bi-info-circle"></i> Location fix was not fast enough. Retrying with higher accuracy...');
            requestLocation({ timeout: GPS_TIMEOUT_MS, enableHighAccuracy: true, maximumAge: 5000 });
            return;
        }

        setIcon('#fef2f2', 'bi bi-geo-alt-fill', '#dc2626');
        document.getElementById('vTitle').textContent = 'Location Failed';
        var reason = '';
        switch(err.code) {
            case 1:
                reason = 'Location permission denied. Please allow location access and try again.';
                break;
            case 2:
                reason = 'Could not determine location. Please check your GPS/location settings.';
                break;
            case 3:
                reason = 'Location request timed out. Please try again.';
                break;
            default:
                reason = 'Location error occurred. Please try again.';
        }
        document.getElementById('vSub').textContent = reason;
        showMsg('err', '<i class="bi bi-exclamation-circle me-1"></i> ' + reason + (geoRetryDowngraded ? ' (retry failed)' : ''));
        var btn = document.getElementById('retryFpBtn');
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry Location Check';
        btn.onclick = function() { startGPS(); };
        btn.style.display = 'flex';
    };

    locationWatcher = navigator.geolocation.watchPosition(onSuccess, onError, options);
    fastFallbackTimer = setTimeout(function() {
        if (bestLocation && bestAccuracy <= GPS_MAX_FAST_ACCEPT) {
            console.log('Fast fallback: good enough accuracy after 3s, accepting');
            acceptBestLocation(bestLocation, 'Using best available location');
        }
    }, GPS_FAST_FALLBACK_MS);

    locationTimeoutId = setTimeout(function() {
        if (locationWatcher !== null) {
            navigator.geolocation.clearWatch(locationWatcher);
            locationWatcher = null;
        }

        if (bestLocation && bestAccuracy <= GPS_HARD_ACCURACY_LIMIT) {
            console.log('GPS timeout: accepting best available location');
            acceptBestLocation(bestLocation, 'Using best available location');
            return;
        }

        if (bestLocation) {
            console.warn('GPS timeout: best available location still too weak', bestAccuracy);
            onError({ code: 3 });
            return;
        }

        onError({ code: 3 });
    }, options.timeout || GPS_TIMEOUT_MS);
}

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

function submitAttendance(credentialData) {
    var latitude = document.getElementById('latInput').value;
    var longitude = document.getElementById('lngInput').value;
    var token = QR_TOKEN;

    if (!latitude || !longitude) {
        showFpError('Location data is missing. Please retry the clock-in process.');
        return;
    }

    var xhr2 = new XMLHttpRequest();
    xhr2.open('POST', '{{ route("qr.verify.complete") }}', true);
    xhr2.withCredentials = true;
    xhr2.setRequestHeader('X-CSRF-TOKEN', CSRF);
    xhr2.setRequestHeader('Content-Type', 'application/json');
    xhr2.setRequestHeader('Accept', 'application/json');
    xhr2.onload = function() {
        var response;
        try {
            response = JSON.parse(xhr2.responseText);
        } catch (e) {
            showFpError('Unable to read server response. Please try again.');
            return;
        }

        if (xhr2.status === 200 && response.success) {
            fingerprintInProgress = false; // Reset flag on success
            window.location.href = response.redirect || '/home';
            return;
        }

        if (response.error_type === 'outside_classroom') {
            showOutsideClassroomError(response.distance || 0, response.radius || RADIUS_METERS);
            return;
        }

        showFpError(response.message || 'Clock-in failed. Please try again.');
    };
    xhr2.onerror = function() {
        showFpError('Network error while clocking in. Please try again.');
    };
    var accuracy = document.getElementById('accuracyInput').value;
    xhr2.send(JSON.stringify({
        token: token,
        latitude: latitude,
        longitude: longitude,
        accuracy: accuracy,
        credential: credentialData
    }));
}

function doFingerprint() {
    // Prevent multiple simultaneous fingerprint attempts
    if (fingerprintInProgress) {
        console.log('Fingerprint verification already in progress');
        return;
    }
    
    fingerprintInProgress = true;
    document.getElementById('retryFpBtn').style.display = 'none';
    setSpinner();
    document.getElementById('vTitle').textContent = 'Verifying Identity';
    document.getElementById('vSub').textContent = 'Touch your fingerprint sensor or use Face ID...';
    hideMsg();

    if (!window.PublicKeyCredential) {
        fingerprintInProgress = false;
        showFpError('Biometrics not supported on this device. Please use a compatible phone or browser.');
        return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '{{ route("qr.verify.options") }}', true);
    xhr.setRequestHeader('X-CSRF-TOKEN', CSRF);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.withCredentials = true;
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onload = function() {
        if (xhr.status !== 200) {
            fingerprintInProgress = false;
            showFpError('Server error (' + xhr.status + '). Please try again.');
            return;
        }
        var opts;
        try { 
            opts = JSON.parse(xhr.responseText); 
        } catch(e) { 
            fingerprintInProgress = false;
            showFpError('Server error (HTTP ' + xhr.status + '): ' + xhr.responseText.substring(0, 200)); 
            return; 
        }

        if (!opts.success) {
            fingerprintInProgress = false;
            showFpError(opts.message || 'No fingerprint registered. Please set up biometric login before clocking in.');
            return;
        }

        var challenge;
        var allowCredentials;
        try {
            challenge = base64ToUint8Array(opts.challenge);
            allowCredentials = [];
            for (var i = 0; i < opts.allowCredentials.length; i++) {
                var cred = opts.allowCredentials[i];
                allowCredentials.push({ type: cred.type, id: base64ToUint8Array(cred.id) });
            }
        } catch(e) { 
            fingerprintInProgress = false;
            showFpError('Failed to decode credentials: ' + e.message); 
            return; 
        }

        var rpId = opts.rpId || window.location.hostname;

        navigator.credentials.get({
            publicKey: {
                challenge: challenge,
                rpId: rpId,
                allowCredentials: allowCredentials,
                userVerification: 'required',
                timeout: 60000
            }
        }).then(function(assertion) {
            setStep(4);
            setIcon('#f0fdf4', 'bi bi-fingerprint', '#16a34a');
            document.getElementById('vTitle').textContent = 'Identity Verified';
            document.getElementById('vSub').textContent = 'Clocking you in now...';
            showMsg('ok', '<i class="bi bi-check-circle me-1"></i> Fingerprint confirmed.');

            var credentialId = bufferToBase64Url(assertion.rawId);

            var credentialData = {
                id: credentialId,
                type: assertion.type,
                rawId: bufferToBase64Url(assertion.rawId),
                response: {
                    clientDataJSON: bufferToBase64Url(assertion.response.clientDataJSON),
                    authenticatorData: bufferToBase64Url(assertion.response.authenticatorData),
                    signature: bufferToBase64Url(assertion.response.signature),
                    userHandle: assertion.response.userHandle ? bufferToBase64Url(assertion.response.userHandle) : null
                }
            };

            document.getElementById('credentialInput').value = JSON.stringify(credentialData);
            submitAttendance(credentialData);
        }).catch(function(err) {
            fingerprintInProgress = false;
            if (err.name === 'NotAllowedError' || err.name === 'InvalidStateError') {
                showFpError('Fingerprint was cancelled or not found on this browser. If you switched browsers, <a href="{{ route("settings") }}#tab-fingerprint" style="color:#2563eb;font-weight:700;text-decoration:underline;">register this browser in Settings</a>.');
            } else {
                showFpError((err.name || 'Error') + ': ' + (err.message || 'Verification failed.'));
            }
        });
    };
    xhr.onerror = function() { 
        fingerprintInProgress = false;
        showFpError('Network error. Please try again.'); 
    };
    xhr.send(JSON.stringify({ token: QR_TOKEN }));
}

function submitForm(msg) {
    setIcon('#fef2f2', 'bi bi-exclamation-circle', '#dc2626');
    document.getElementById('vTitle').textContent = 'Unable to Clock In';
    document.getElementById('vSub').textContent = msg;
    showMsg('err', '<i class="bi bi-exclamation-circle me-1"></i> ' + msg);
}

function showOutsideClassroomError(dist, limit) {
    fingerprintInProgress = false;
    setIcon('#fef2f2', 'bi bi-geo-alt-fill', '#dc2626');
    document.getElementById('vTitle').textContent = 'Failed to Scan';
    document.getElementById('vSub').textContent = 'You are outside the classroom (' + Math.round(dist) + 'm away).';
    showMsg('err', '<i class="bi bi-x-circle-fill me-1"></i> <strong>Outside Classroom:</strong> Attendance can only be recorded while physically inside the classroom (within ' + limit + 'm).');
    var btn = document.getElementById('retryFpBtn');
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry Location Check';
    btn.onclick = function() { startGPS(); };
    btn.style.display = 'flex';
}

function showFpError(msg) {
    fingerprintInProgress = false; // Reset the flag on error
    setIcon('#fef2f2', 'bi bi-fingerprint', '#dc2626');
    document.getElementById('vTitle').textContent = 'Fingerprint Required';
    document.getElementById('vSub').textContent = 'You must verify your fingerprint to clock in.';
    showMsg('err', '<i class="bi bi-exclamation-circle me-1"></i> ' + msg);
    var btn = document.getElementById('retryFpBtn');
    btn.innerHTML = '<i class="bi bi-fingerprint"></i> Try Fingerprint Again';
    btn.onclick = function() { doFingerprint(); };
    btn.style.display = 'flex';
}
</script>
@endsection
