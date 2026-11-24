@extends('layouts.biometric-flow')

@section('title', 'Face registration')
@section('description', 'Register a front view and two slight turns for attendance. The camera captures each view when you are ready.')
@section('pageClass', 'face-registration-page')

@section('content')
<section class="biometric-flow-card" id="cameraFaceEnrollment" data-enrollment-url="{{ route('profile.face.enroll') }}"
    @if($faceEnrollmentAvailable) data-guidance-url="{{ Vite::asset('resources/js/attendance-face-guidance.js') }}" @endif
    aria-label="Camera registration">
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
            <div class="face-camera-placeholder" aria-hidden="true">
                <span class="face-camera-placeholder-icon"><i class="bi bi-camera-video"></i></span>
                <strong>Ready for your face scan</strong>
                <span>Your camera preview appears here.</span>
            </div>
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
        <p class="bio-privacy-note"><i class="bi bi-lock" aria-hidden="true"></i><span><strong>Private attendance profile</strong><span>Captures stay on this device until you register. They are then encrypted and used for attendance matching, not as your profile photo.</span></span></p>
        <label for="cameraFaceConsentCheckbox" class="bio-consent-check" style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:12px;background:rgba(207,164,111,0.08);border:1px solid rgba(207,164,111,0.22);margin-bottom:14px;cursor:pointer;font-size:0.86rem;line-height:1.45;color:#f3e7cd;">
            <input type="checkbox" id="cameraFaceConsentCheckbox" value="1" {{ !empty($hasFaceConsent) ? 'checked' : '' }} style="margin-top:3px;width:16px;height:16px;accent-color:#cfa46f;flex-shrink:0;">
            <span>I voluntarily consent to the encrypted storage and liveness matching of my camera face captures for classroom attendance verification under the <a href="{{ route('privacy') }}" target="_blank" rel="noopener" style="color:#ffd166;text-decoration:underline;">Privacy Notice</a>.</span>
        </label>
        <div class="biometric-flow-actions">
            <button type="button" id="cameraFaceRetakeBtn" hidden class="flow-button">Retake</button>
            <button type="button" id="cameraFaceSaveBtn" hidden class="flow-button flow-primary">Register face</button>
            <button type="button" id="cameraFaceCancelBtn" hidden class="flow-button">Cancel capture</button>
            <a id="cameraFaceDone" class="flow-button" href="{{ route('settings') }}#tab-biometrics" hidden>Done</a>
        </div>
        @if(!empty($hasCameraFaceEnrollment) || !empty($hasFaceConsent))
            <form method="POST" action="{{ route('student.face-attendance-consent.withdraw') }}" style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.08);">
                @csrf
                @method('DELETE')
                <button type="submit" class="flow-button" style="width:100%;justify-content:center;color:#fca5a5;border-color:rgba(248,113,113,0.32);">
                    <i class="bi bi-trash3 me-1" aria-hidden="true"></i> Withdraw consent &amp; delete face profile
                </button>
            </form>
        @endif
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
