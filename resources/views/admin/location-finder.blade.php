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

<script>
let currentLat, currentLng;

document.getElementById('getLocationBtn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Getting location...';
    
    if (!navigator.geolocation) {
        showError('GPS not supported on this device');
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            currentLat = position.coords.latitude;
            currentLng = position.coords.longitude;
            
            document.getElementById('coordinates').innerHTML = `
                <strong>Latitude:</strong> ${currentLat}<br>
                <strong>Longitude:</strong> ${currentLng}<br>
                <strong>Accuracy:</strong> ±${Math.round(position.coords.accuracy)}m
            `;
            
            document.getElementById('mapsLink').href = `https://www.google.com/maps?q=${currentLat},${currentLng}`;
            document.getElementById('locationResult').style.display = 'block';
            
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-geo-alt"></i> Get My Current Location';
        },
        function(error) {
            let message = 'GPS Error: ';
            switch(error.code) {
                case 1: message += 'Permission denied'; break;
                case 2: message += 'Position unavailable'; break;
                case 3: message += 'Timeout'; break;
                default: message += 'Unknown error';
            }
            showError(message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-geo-alt"></i> Get My Current Location';
        },
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }
    );
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
