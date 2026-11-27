@extends('layouts.biometric-flow')

@section('title', 'Face registration')
@section('description', 'Register a front view and two slight turns for attendance. The camera captures each view automatically when you are centered and steady.')
@section('pageClass', 'face-registration-page')

@section('content')
<section class="biometric-flow-card" id="cameraFaceEnrollment" data-enrollment-url="{{ route('profile.face.enroll') }}"
    @if($faceEnrollmentAvailable) data-guidance-url="{{ Vite::asset('resources/js/attendance-face-guidance.js') }}" @endif
    aria-label="Camera registration">
    @if($faceEnrollmentAvailable)
        <div class="bio-flow-title-row">
            <span class="bio-flow-symbol" aria-hidden="true"><i class="bi bi-person-bounding-box"></i></span>
            <div>
                <h2>Scan three face views</h2>
                <p>Look straight ahead, then turn slightly left and right. Each angle captures automatically once your face is steady.</p>
            </div>
        </div>

        <ol class="face-pose-progress" aria-label="Face capture positions">
            <li data-face-pose="front"><span>1</span><span>Front</span></li>
            <li data-face-pose="left"><span>2</span><span>Left</span></li>
            <li data-face-pose="right"><span>3</span><span>Right</span></li>
        </ol>

        <div class="bio-face-studio-grid">
            <div class="bio-face-studio-left">
                <p id="cameraFacePoseInstruction" tabindex="-1" aria-live="polite">Look straight ahead for your first capture. Keep both eyes visible.</p>
                <div class="face-camera-stage" id="cameraFaceStage">
                    <div class="face-camera-placeholder" aria-hidden="true">
                        <span class="face-camera-placeholder-icon"><i class="bi bi-camera-video"></i></span>
                        <strong>Ready for your face scan</strong>
                        <span>Grant camera access and keep your face evenly lit inside the guide oval.</span>
                    </div>
                    <div id="cameraFacePreviewWrap" class="biometric-camera-preview" style="display:none">
                        <video id="cameraFaceVideo" autoplay muted playsinline aria-label="Live camera preview"></video>
                        <div class="bio-viewfinder-corner tl" aria-hidden="true"></div>
                        <div class="bio-viewfinder-corner tr" aria-hidden="true"></div>
                        <div class="bio-viewfinder-corner bl" aria-hidden="true"></div>
                        <div class="bio-viewfinder-corner br" aria-hidden="true"></div>
                        <div class="biometric-face-guide" id="cameraFaceGuide" aria-hidden="true">
                            <span>Center your face</span>
                            <span class="face-guide-stability"><span></span></span>
                        </div>
                        <canvas id="cameraFaceCanvas" style="display:none"></canvas>
                    </div>
                    <button type="button" id="cameraFaceStartBtn" class="flow-button flow-primary">
                        <i class="bi bi-camera" aria-hidden="true"></i> Start face scan
                    </button>
                </div>
            </div>

            <div class="bio-face-studio-right">
                @include('partials.camera-light-controls', ['cameraLightId' => 'cameraFaceLight'])

                <p id="cameraFaceStatus" class="biometric-flow-status" role="status">{{ $hasCameraFaceEnrollment ? 'A face profile is enrolled for attendance matching. Saving new captures will replace it.' : '' }}</p>

                <p class="bio-privacy-note">
                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                    <span>
                        <strong>Private attendance profile</strong>
                        <span>Captures stay on this device until you register. They are encrypted and used only for classroom attendance matching, never as your public profile photo.</span>
                    </span>
                </p>

                <label for="cameraFaceConsentCheckbox" class="bio-consent-card">
                    <input type="checkbox" id="cameraFaceConsentCheckbox" value="1" {{ !empty($hasFaceConsent) ? 'checked' : '' }}>
                    <span>I voluntarily consent to the encrypted storage and liveness matching of my camera face captures for classroom attendance verification under the <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Notice</a>.</span>
                </label>

                <div class="biometric-flow-actions">
                    <button type="button" id="cameraFaceRetakeBtn" hidden class="flow-button">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Retake
                    </button>
                    <button type="button" id="cameraFaceSaveBtn" hidden class="flow-button flow-primary">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i> Register face
                    </button>
                    <button type="button" id="cameraFaceCancelBtn" hidden class="flow-button">
                        <i class="bi bi-x-lg" aria-hidden="true"></i> Cancel capture
                    </button>
                    <a id="cameraFaceDone" class="flow-button flow-primary" href="{{ route('settings') }}#tab-biometrics" hidden>
                        <i class="bi bi-check-lg" aria-hidden="true"></i> Done
                    </a>
                </div>

                @if(!empty($hasCameraFaceEnrollment) || !empty($hasFaceConsent))
                    <form method="POST" action="{{ route('student.face-attendance-consent.withdraw') }}" class="bio-withdraw-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flow-button flow-danger">
                            <i class="bi bi-trash3" aria-hidden="true"></i> Withdraw consent &amp; delete face profile
                        </button>
                    </form>
                @endif
            </div>
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
