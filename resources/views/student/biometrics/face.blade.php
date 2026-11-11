@extends('layouts.biometric-flow')

@section('title', 'Face registration')
@section('description', 'Capture a front view and two slight turns for attendance. Your face captures are stored encrypted.')

@section('content')
<section class="biometric-flow-card" id="cameraFaceEnrollment" data-enrollment-url="{{ route('profile.face.enroll') }}" aria-label="Camera registration">
    @if($faceEnrollmentAvailable)
        <ol class="face-pose-progress" aria-label="Face capture positions">
            <li data-face-pose="front">1. Front</li>
            <li data-face-pose="left">2. Left</li>
            <li data-face-pose="right">3. Right</li>
        </ol>
        <p id="cameraFacePoseInstruction" aria-live="polite">Look straight ahead for your first capture. Keep both eyes visible.</p>
        <div id="cameraFacePreviewWrap" class="biometric-camera-preview" style="display:none">
            <video id="cameraFaceVideo" autoplay muted playsinline aria-label="Live camera preview"></video>
            <div class="biometric-face-guide" aria-hidden="true"></div>
            <canvas id="cameraFaceCanvas" style="display:none"></canvas>
        </div>
        @include('partials.camera-light-controls', ['cameraLightId' => 'cameraFaceLight'])
        <p id="cameraFaceStatus" class="biometric-flow-status" role="status">{{ $hasCameraFaceEnrollment ? 'A face profile is enrolled for attendance matching. Saving new captures will replace it.' : '' }}</p>
        <div class="biometric-flow-actions">
            <button type="button" id="cameraFaceStartBtn" class="flow-button flow-primary">Open camera</button>
            <button type="button" id="cameraFaceCaptureBtn" hidden class="flow-button flow-primary">Capture front view</button>
            <button type="button" id="cameraFaceRetakeBtn" hidden class="flow-button">Retake</button>
            <button type="button" id="cameraFaceSaveBtn" hidden class="flow-button flow-primary">Register face</button>
            <button type="button" id="cameraFaceCancelBtn" hidden class="flow-button">Cancel capture</button>
            <a id="cameraFaceDone" class="flow-button" href="{{ route('settings') }}#tab-biometrics" hidden>Done</a>
        </div>
    @else
        <p id="cameraFaceUnavailableNotice" role="status">Camera-based face registration is currently unavailable. Please contact your administrator.</p>
    @endif
</section>
@endsection

@push('scripts')
@if($faceEnrollmentAvailable)
<script src="{{ asset('js/camera-face-enrollment.js') }}?v={{ filemtime(public_path('js/camera-face-enrollment.js')) }}" @cspNonce defer></script>
@endif
@endpush
