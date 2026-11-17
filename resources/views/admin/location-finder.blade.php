@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="bi bi-crosshair"></i> Find Your School's GPS Coordinates</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <strong>Current Issue:</strong> Your GPS coordinates are wrong (381km away). Let's find the correct ones!
                    </div>
                    
                    <button id="getLocationBtn" class="btn btn-primary btn-lg">
                        <i class="bi bi-geo-alt"></i> Get My Current Location
                    </button>
                    
                    <div id="locationResult" class="mt-4" style="display: none;">
                        <div class="card">
                            <div class="card-body">
                                <h5>Your Current Location:</h5>
                                <div id="coordinates"></div>
                                <div class="mt-3">
                                    <button id="updateBtn" class="btn btn-success" onclick="updateCoordinates()">
                                        <i class="bi bi-check-circle"></i> Use These Coordinates
                                    </button>
                                    <a id="mapsLink" href="#" target="_blank" class="btn btn-outline-primary">
                                        <i class="bi bi-map"></i> View on Google Maps
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="error" class="alert alert-danger mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script @cspNonce>
let currentLat, currentLng;

document.getElementById('getLocationBtn').addEventListener('click', async function() {
    const btn = this;
    const errEl = document.getElementById('error');
    if (errEl) errEl.style.display = 'none';
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Stabilizing GPS location...';

    if (!navigator.geolocation || !window.GpsSampler) {
        showError('GPS not supported on this device');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-geo-alt"></i> Get My Current Location';
        return;
    }

    const result = await window.GpsSampler.capturePosition({
        timeoutMs: 15000,
        settleMs: 4000,
        targetAccuracy: 12,
        maxAcceptableAccuracy: 50,
        minClusterSamples: 3,
        maxSamples: 6,
        allowSingleFallback: true,
        onSample: ({ estimate, bestRawAccuracy, sampleCount }) => {
            if (estimate) {
                btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Averaging fixes (±${Math.round(estimate.acc)}m, ${sampleCount})...`;
            } else if (Number.isFinite(bestRawAccuracy)) {
                btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Refining signal (±${Math.round(bestRawAccuracy)}m)...`;
            }
        },
    });

    if (!result.ok || !result.fix || result.fix.acc > 50) {
        const message = result.reason === 'permission_denied'
            ? 'GPS Error: Permission denied'
            : (Number.isFinite(result.bestRawAccuracy)
                ? `GPS accuracy is too low (±${Math.round(result.bestRawAccuracy)}m). Move near a window or use a device with GPS.`
                : 'GPS Error: Unable to acquire a reliable position');
        showError(message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-geo-alt"></i> Get My Current Location';
        return;
    }

    currentLat = result.fix.lat;
    currentLng = result.fix.lng;

    document.getElementById('coordinates').innerHTML = `
        <strong>Latitude:</strong> ${currentLat.toFixed(6)}<br>
        <strong>Longitude:</strong> ${currentLng.toFixed(6)}<br>
        <strong>Accuracy:</strong> ±${Math.round(result.fix.acc)}m (${result.fix.sampleCount} ${result.fix.sampleCount === 1 ? 'sample' : 'samples averaged'})
    `;

    document.getElementById('mapsLink').href = `https://www.google.com/maps?q=${currentLat},${currentLng}`;
    document.getElementById('locationResult').style.display = 'block';

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-geo-alt"></i> Get My Current Location';
});

function updateCoordinates() {
    if (!currentLat || !currentLng) {
        alert('Please get your location first');
        return;
    }
    
    fetch('/admin/update-coordinates', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            latitude: currentLat,
            longitude: currentLng
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Coordinates updated successfully! The system will now use your current location.');
            window.location.reload();
        } else {
            alert('Error updating coordinates: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function showError(message) {
    document.getElementById('error').innerHTML = message;
    document.getElementById('error').style.display = 'block';
}
</script>
@endsection
