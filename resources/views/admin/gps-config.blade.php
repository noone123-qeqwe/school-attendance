@extends('layouts.app')

@section('content')
<!-- Include Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
    #map { height: 400px; width: 100%; border-radius: 8px; z-index: 1; }
</style>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="bi bi-geo-alt-fill"></i> GPS Configuration</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    <div class="alert alert-info">
                        <strong>Current School Location:</strong> <span id="display-lat">{{ $currentLat }}</span>°N, <span id="display-lng">{{ $currentLng }}</span>°E (<span id="display-radius">{{ $currentRadius }}</span>m radius)
                    </div>
                    
                    <div class="mb-4">
                        <div id="map"></div>
                        <div class="text-muted mt-2 small"><i class="bi bi-info-circle"></i> Drag the marker or click on the map to set the school location.</div>
                    </div>

                    <form method="POST" action="{{ route('admin.gps.update') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="number" step="any" class="form-control @error('latitude') is-invalid @enderror" 
                                           id="latitude" name="latitude" value="{{ old('latitude', $currentLat) }}" required>
                                    @error('latitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="number" step="any" class="form-control @error('longitude') is-invalid @enderror" 
                                           id="longitude" name="longitude" value="{{ old('longitude', $currentLng) }}" required>
                                    @error('longitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="radius" class="form-label">Allowed Radius (meters)</label>
                            <input type="number" class="form-control @error('radius') is-invalid @enderror" 
                                   id="radius" name="radius" value="{{ old('radius', $currentRadius) }}" 
                                   min="10" max="500" required>
                            <div class="form-text">Students must be within this distance to clock in (10-500 meters)</div>
                            @error('radius')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <button type="button" id="getCurrentLocation" class="btn btn-outline-secondary">
                                <i class="bi bi-crosshair"></i> Use My Current Location
                            </button>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update GPS Settings
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="document.getElementById('getCurrentLocation').click()">
                                <i class="bi bi-speedometer2"></i> Test GPS Signal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentLat = parseFloat(document.getElementById('latitude').value);
    let currentLng = parseFloat(document.getElementById('longitude').value);
    let currentRadius = parseFloat(document.getElementById('radius').value);
    
    const map = L.map('map').setView([currentLat, currentLng], 17);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);
    
    let marker = L.marker([currentLat, currentLng], {draggable: true}).addTo(map);
    let circle = L.circle([currentLat, currentLng], {
        color: 'blue',
        fillColor: '#3085d6',
        fillOpacity: 0.2,
        radius: currentRadius
    }).addTo(map);
    
    function updateInputs(lat, lng) {
        document.getElementById('latitude').value = lat.toFixed(6);
        document.getElementById('longitude').value = lng.toFixed(6);
        document.getElementById('display-lat').innerText = lat.toFixed(6);
        document.getElementById('display-lng').innerText = lng.toFixed(6);
    }
    
    marker.on('dragend', function(e) {
        let position = marker.getLatLng();
        updateInputs(position.lat, position.lng);
        circle.setLatLng(position);
    });
    
    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        circle.setLatLng(e.latlng);
        updateInputs(e.latlng.lat, e.latlng.lng);
    });
    
    document.getElementById('radius').addEventListener('input', function(e) {
        let r = parseFloat(e.target.value) || 10;
        circle.setRadius(r);
        document.getElementById('display-radius').innerText = r;
    });

    document.getElementById('latitude').addEventListener('input', function(e) {
        let lat = parseFloat(e.target.value) || 0;
        let lng = parseFloat(document.getElementById('longitude').value) || 0;
        let newLatLng = new L.LatLng(lat, lng);
        marker.setLatLng(newLatLng);
        circle.setLatLng(newLatLng);
        map.setView(newLatLng);
        document.getElementById('display-lat').innerText = lat.toFixed(6);
    });

    document.getElementById('longitude').addEventListener('input', function(e) {
        let lat = parseFloat(document.getElementById('latitude').value) || 0;
        let lng = parseFloat(e.target.value) || 0;
        let newLatLng = new L.LatLng(lat, lng);
        marker.setLatLng(newLatLng);
        circle.setLatLng(newLatLng);
        map.setView(newLatLng);
        document.getElementById('display-lng').innerText = lng.toFixed(6);
    });
    
    document.getElementById('getCurrentLocation').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Getting location...';
        
        if (!navigator.geolocation) {
            alert('GPS not supported on this device');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-crosshair"></i> Use My Current Location';
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                let lat = position.coords.latitude;
                let lng = position.coords.longitude;
                
                updateInputs(lat, lng);
                let newLatLng = new L.LatLng(lat, lng);
                marker.setLatLng(newLatLng);
                circle.setLatLng(newLatLng);
                map.setView(newLatLng, 17);
                
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Location Updated';
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-success');
                
                setTimeout(() => {
                    btn.innerHTML = '<i class="bi bi-crosshair"></i> Use My Current Location';
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-secondary');
                }, 2000);
            },
            function(error) {
                let message = 'GPS Error: ';
                switch(error.code) {
                    case 1: message += 'Permission denied'; break;
                    case 2: message += 'Position unavailable'; break;
                    case 3: message += 'Timeout'; break;
                    default: message += 'Unknown error';
                }
                alert(message);
                
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-crosshair"></i> Use My Current Location';
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    });
});
</script>
@endsection
