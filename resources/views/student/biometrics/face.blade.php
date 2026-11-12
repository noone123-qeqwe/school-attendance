@extends('layouts.biometric-flow')

@section('title', 'Face registration')
@section('description', 'Register a front view and two slight turns for attendance. The camera captures each view when you are ready.')
@section('pageClass', 'face-registration-page')

@section('content')
<section class="biometric-flow-card" id="cameraFaceEnrollment" data-enrollment-url="{{ route('profile.face.enroll') }}" aria-label="Camera registration">
    @if($faceEnrollmentAvailable)
        <div class="bio-flow-kicker"><i class="bi bi-camera" aria-hidden="true"></i> Camera-based attendance profile</div>
        <div class="bio-flow-title-row">
            <span class="bio-flow-symbol" aria-hidden="true"><i class="bi bi-person-bounding-box"></i></span>
            <div>
                <h2>Scan three face views</h2>
                <p>Look forward, then turn slightly each way. The camera captures each position when you are centered and steady.</p>
            </div>
        </div>
        <ol class="face-pose-progress" aria-label="Face capture positions">
            <li data-face-pose="front"><span>1</span><span>Front</span></li>
            <li data-face-pose="left"><span>2</span><span>Left</span></li>
            <li data-face-pose="right"><span>3</span><span>Right</span></li>
        </ol>
        <p id="cameraFacePoseInstruction" tabindex="-1" aria-live="polite">Look straight ahead for your first capture. Keep both eyes visible.</p>
        <div class="face-camera-stage" id="cameraFaceStage">
            <div id="cameraFacePreviewWrap" class="biometric-camera-preview" style="display:none">
                <video id="cameraFaceVideo" autoplay muted playsinline aria-label="Live camera preview"></video>
                <div class="biometric-face-guide" id="cameraFaceGuide" aria-hidden="true">
                    <span>Center your face</span>
                    <span class="face-guide-stability"><span></span></span>
                </div>
                <canvas id="cameraFaceCanvas" style="display:none"></canvas>
            </div>
            <button type="button" id="cameraFaceStartBtn" class="flow-button flow-primary"><i class="bi bi-camera" aria-hidden="true"></i> Start face scan</button>
        </div>
        @include('partials.camera-light-controls', ['cameraLightId' => 'cameraFaceLight'])
        <p id="cameraFaceStatus" class="biometric-flow-status" role="status">{{ $hasCameraFaceEnrollment ? 'A face profile is enrolled for attendance matching. Saving new captures will replace it.' : '' }}</p>
        <p class="bio-privacy-note"><i class="bi bi-lock" aria-hidden="true"></i><span>The scan runs on this device. Captures are uploaded only when you tap Register face, then checked and encrypted for attendance matching. They are not used as your profile photo.</span></p>
        <div class="biometric-flow-actions">
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
