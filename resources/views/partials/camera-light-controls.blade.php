@once
<link rel="stylesheet" href="{{ asset('css/camera-lighting.css') }}?v={{ filemtime(public_path('css/camera-lighting.css')) }}">
<script src="{{ asset('js/camera-lighting.js') }}?v={{ filemtime(public_path('js/camera-lighting.js')) }}" @cspNonce></script>
@endonce
<section id="{{ $cameraLightId }}" class="camera-light-controls" aria-label="Camera lighting" hidden>
    <button type="button" data-light-toggle aria-pressed="false" aria-describedby="{{ $cameraLightId }}-help"><i class="bi bi-lightning-charge" aria-hidden="true"></i> Face light</button>
    <label for="{{ $cameraLightId }}-level">Screen light <output data-light-level-output for="{{ $cameraLightId }}-level">60%</output></label>
    <input id="{{ $cameraLightId }}-level" data-light-level type="range" min="10" max="100" step="5" value="60" disabled>
    <div data-light-exposure-group hidden>
        <label for="{{ $cameraLightId }}-exposure">Camera exposure <output data-light-exposure-output for="{{ $cameraLightId }}-exposure"></output></label>
        <input id="{{ $cameraLightId }}-exposure" data-light-exposure type="range" min="-2" max="2" step="0.1" value="0" disabled>
    </div>
    <p id="{{ $cameraLightId }}-help" data-light-help role="status" aria-live="polite">Turn on the light in dim conditions.</p>
    <div class="camera-light-fill" data-light-fill aria-hidden="true" hidden></div>
</section>
