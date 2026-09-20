@props([
    'user' => Auth::user(),
    'size' => 120,
    'align' => 'left',
    'showMeta' => false,
    'avatarId' => 'mainProfileAvatar',
])

@php
    $user = $user ?: Auth::user();
    $avatarUrl = $user ? $user->profile_photo_url_with_version : 'https://ui-avatars.com/api/?name=User&background=800000&color=fff&size=256';
    $hasCustom = $user ? $user->has_custom_profile_image : false;
@endphp

<div class="ppm-container d-flex flex-column {{ $align === 'center' ? 'align-items-center text-center' : 'align-items-start' }}" data-user-id="{{ $user->id ?? '' }}">
    <!-- Avatar Display & Drag-and-Drop Area -->
    <div class="ppm-avatar-dropzone position-relative"
         id="ppmDropzone"
         style="width: {{ $size }}px; height: {{ $size }}px;"
         onclick="ppmDropzoneClick(event)"
         role="button"
         tabindex="0"
         aria-label="Change profile photo"
         title="Click camera icon to change profile picture">
        
        <div class="ppm-avatar-ring">
            <img id="{{ $avatarId }}"
                 src="{{ $avatarUrl }}"
                 alt="{{ $user->name ?? 'User' }}"
                 class="ppm-avatar-img user-avatar-img"
                 onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'User') }}&background=800000&color=fff&size=256'">
            
            <!-- Desktop Hover Overlay (Native label trigger for file input) -->
            <label for="ppmFileInput" class="ppm-avatar-hover-overlay" id="ppmHoverOverlay" role="button" tabindex="0" title="Change photo" aria-label="Change photo">
                <i class="bi bi-camera-fill fs-3" style="pointer-events: none;"></i>
                <span class="ppm-hover-text" style="pointer-events: none;">Change</span>
            </label>

            <!-- Live Upload / Status Overlay -->
            <div class="ppm-status-overlay d-none" id="ppmStatusOverlay" aria-live="polite">
                <div class="ppm-status-spinner spinner-border text-warning" role="status" id="ppmStatusSpinner">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <i class="bi bi-check-circle-fill text-success fs-2 d-none" id="ppmStatusSuccess"></i>
                <i class="bi bi-exclamation-circle-fill text-danger fs-2 d-none" id="ppmStatusError"></i>
                <span class="ppm-status-text mt-1" id="ppmStatusText">Updating...</span>
            </div>
        </div>

        <!-- Functional Camera Action Badge (Native label trigger for file input: guaranteed to open picker on mobile/desktop without CSP or JS blockage) -->
        <label for="ppmFileInput" 
               class="ppm-badge-btn" 
               id="ppmCameraBadge" 
               role="button" 
               tabindex="0" 
               aria-label="Change profile picture" 
               title="Change profile picture">
            <i class="bi bi-camera-fill" style="pointer-events: none;"></i>
        </label>
    </div>

    <!-- Hidden Native File Input (Accessible, zero-size, non-display-none for maximum browser compatibility) -->
    <input type="file" 
           id="ppmFileInput" 
           name="profile_image"
           style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0; opacity: 0;" 
           accept="image/*,image/jpeg,image/png,image/jpg,image/webp,image/gif,image/heic,image/heif" 
           onchange="ppmHandleFileSelect(this)">

    <!-- Optional Remove Action (Strictly displayed when a custom photo exists, no empty gap when absent) -->
    <div class="ppm-action-buttons {{ $align === 'center' ? 'text-center' : '' }}" 
         id="ppmActionButtons" 
         style="{{ $hasCustom ? 'margin-top: 8px;' : 'display: none !important;' }}">
        <button type="button" 
                class="btn ppm-btn-remove" 
                id="ppmRemoveBtn" 
                onclick="ppmPromptRemove()" 
                title="Remove photo and restore default initials avatar">
            <i class="bi bi-trash3 me-1"></i> Remove Photo
        </button>
    </div>

    @if($showMeta && $user)
        <div class="ppm-meta mt-2">
            <div class="ppm-name fw-bold">{{ $user->name }}</div>
            <div class="ppm-sub text-muted small">{{ $user->student_number ?? $user->email }}</div>
        </div>
    @endif
</div>

<!-- ========================================== -->
<!-- PREVIEW & CONFIRMATION MODAL               -->
<!-- ========================================== -->
<div class="modal fade ppm-modal" id="ppmPreviewModal" tabindex="-1" aria-labelledby="ppmPreviewModalTitle" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ppm-modal-card p-3">
            <div class="modal-header border-0 pb-1 justify-content-between">
                <h5 class="fs-6 fw-bold text-light mb-0 d-flex align-items-center gap-2" id="ppmPreviewModalTitle">
                    <i class="bi bi-person-bounding-box text-warning"></i> Preview Profile Picture
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="ppmCancelPreview()" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 text-center">
                <p class="small text-muted mb-2">Adjust and position your profile picture before saving.</p>
                
                <!-- Interactive Crop Viewport (Circular ring matching avatar styling) -->
                <div class="ppm-crop-container mx-auto mb-2" id="ppmCropContainer">
                    <div class="ppm-crop-viewport" id="ppmCropViewport" tabindex="0" title="Drag to position photo" role="region" aria-label="Profile picture crop area">
                        <img id="ppmPreviewImg" src="" alt="Selected Photo Preview" class="ppm-crop-img" draggable="false">
                        <div class="ppm-crop-overlay-ring"></div>
                        <div class="ppm-crop-guideline ppm-crop-guide-h"></div>
                        <div class="ppm-crop-guideline ppm-crop-guide-v"></div>
                    </div>
                </div>

                <!-- Position & Zoom Controls Bar -->
                <div class="ppm-crop-controls d-flex flex-column align-items-center mb-2">
                    <div class="ppm-crop-hint mb-2 d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-arrows-move text-warning"></i>
                        <span>Drag photo to position &bull; Pinch or scroll to zoom</span>
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-center gap-2 w-100" style="max-width: 280px;">
                        <button type="button" class="ppm-zoom-btn" id="ppmZoomOutBtn" title="Zoom out" aria-label="Zoom out">
                            <i class="bi bi-dash"></i>
                        </button>
                        <div class="flex-grow-1 d-flex align-items-center px-1">
                            <input type="range" class="form-range ppm-zoom-range w-100" id="ppmZoomSlider" min="1" max="4" step="0.01" value="1" aria-label="Zoom level">
                        </div>
                        <button type="button" class="ppm-zoom-btn" id="ppmZoomInBtn" title="Zoom in" aria-label="Zoom in">
                            <i class="bi bi-plus"></i>
                        </button>
                        <button type="button" class="ppm-zoom-btn ppm-reset-btn ms-1" id="ppmResetCropBtn" title="Reset position & zoom" aria-label="Reset crop">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>

                <!-- File details tag -->
                <div class="ppm-preview-file-info badge border text-muted mb-2 px-3 py-2" id="ppmPreviewDetails" style="background: rgba(255,255,255,0.05); border-color: rgba(207,164,111,0.2) !important;">
                    photo.jpg • 1.2 MB
                </div>

                <div class="small text-muted mt-1" style="font-size: 0.76rem;">
                    <i class="bi bi-info-circle me-1"></i>Your photo will be cropped to a clean square avatar.
                </div>
                
                <div class="alert alert-danger d-none mt-3 py-2 small" id="ppmPreviewError" role="alert"></div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex gap-2 justify-content-center">
                <button type="button" class="btn ppm-btn-secondary px-3 flex-grow-1" data-bs-dismiss="modal" id="ppmCancelPreviewBtn" onclick="ppmCancelPreview()">
                    Cancel
                </button>
                <button type="button" class="btn ppm-btn-confirm-save px-3 flex-grow-1" id="ppmConfirmSaveBtn" onclick="ppmExecuteSave()">
                    <span class="ppm-save-btn-content d-inline-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-check2-circle"></i> Confirm &amp; Save
                    </span>
                    <span class="ppm-save-spinner spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- REMOVE CONFIRMATION MODAL                  -->
<!-- ========================================== -->
<div class="modal fade ppm-modal" id="ppmRemoveConfirmModal" tabindex="-1" aria-labelledby="ppmRemoveModalTitle" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content ppm-modal-card text-center p-3">
            <div class="modal-body p-2">
                <div class="ppm-delete-icon-circle mx-auto mb-3">
                    <i class="bi bi-trash3-fill text-danger fs-3"></i>
                </div>
                <h5 class="fs-6 fw-bold text-light mb-1" id="ppmRemoveModalTitle">Remove Profile Picture?</h5>
                <p class="small text-muted mb-4">Your picture will be removed and restored to your default initials avatar.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn ppm-btn-secondary px-3 w-50" data-bs-dismiss="modal" id="ppmCancelRemoveBtn" onclick="ppmCloseRemoveModal()">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger px-3 w-50" onclick="ppmExecuteRemove()" id="ppmConfirmRemoveBtn">
                        <span class="ppm-remove-btn-text">Remove</span>
                        <span class="ppm-remove-spinner spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Toast Notification -->
<div id="ppmToast" class="ppm-toast d-none" role="status" aria-live="polite">
    <i class="bi bi-check-circle-fill ppm-toast-icon me-2 text-success"></i>
    <span id="ppmToastMessage">Profile picture updated</span>
</div>

<style>
/* ── PROFILE PHOTO MANAGER COMPONENT STYLES ── */
.ppm-container {
    position: relative;
    display: inline-flex;
}

.ppm-avatar-dropzone {
    cursor: pointer;
    user-select: none;
    display: inline-block;
    position: relative;
    margin-bottom: 0 !important;
    line-height: 0;
    -webkit-tap-highlight-color: transparent;
    outline: none;
}

.ppm-avatar-ring {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    overflow: hidden;
    position: relative;
    border: 3px solid rgba(207, 164, 111, 0.45);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
    background: #150d0a;
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.25s ease, box-shadow 0.25s ease;
}

.ppm-avatar-dropzone:hover .ppm-avatar-ring,
.ppm-avatar-dropzone.ppm-dragover .ppm-avatar-ring {
    transform: scale(1.02);
    border-color: #cfa46f;
    box-shadow: 0 10px 28px rgba(207, 164, 111, 0.35);
}

.ppm-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: filter 0.2s ease, opacity 0.2s ease;
}

/* Hover overlay on desktop */
.ppm-avatar-hover-overlay {
    position: absolute;
    inset: 0;
    background: rgba(18, 10, 8, 0.65);
    backdrop-filter: blur(2px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #f3e7cd;
    opacity: 0;
    transition: opacity 0.2s ease;
    border-radius: 50%;
    cursor: pointer;
}

.ppm-hover-text {
    font-size: 0.75rem;
    font-weight: 700;
    margin-top: 3px;
    letter-spacing: 0.5px;
    line-height: 1.2;
}

.ppm-avatar-dropzone:hover .ppm-avatar-hover-overlay,
.ppm-avatar-dropzone.ppm-dragover .ppm-avatar-hover-overlay {
    opacity: 1;
}

/* Status Overlay (Loading, Success, Error) */
.ppm-status-overlay {
    position: absolute;
    inset: 0;
    background: rgba(18, 10, 8, 0.82);
    backdrop-filter: blur(3px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #f3e7cd;
    border-radius: 50%;
    z-index: 5;
    transition: opacity 0.2s ease;
    animation: ppmFadeIn 0.2s ease;
}

@keyframes ppmFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.ppm-status-text {
    font-size: 0.72rem;
    font-weight: 700;
    color: #f3e7cd;
    letter-spacing: 0.3px;
    line-height: 1.2;
}

.ppm-status-spinner {
    width: 1.6rem;
    height: 1.6rem;
    border-width: 2.5px;
}

/* Quick Camera Action Badge (The designated camera icon button) */
.ppm-badge-btn {
    position: absolute;
    bottom: 0px;
    right: 0px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #dfb582 0%, #cfa46f 50%, #a87d46 100%);
    color: #120a0a;
    border: 2.5px solid #1a1010;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.55), inset 0 1px 1px rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.95rem;
    padding: 0;
    margin: 0;
    line-height: 1;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease, filter 0.2s ease;
    z-index: 10;
    outline: none;
    -webkit-tap-highlight-color: transparent;
    pointer-events: auto !important;
}

.ppm-badge-btn::before {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    background: transparent;
    z-index: 1;
}

.ppm-badge-btn:hover {
    transform: scale(1.15);
    filter: brightness(1.1);
    box-shadow: 0 6px 18px rgba(207, 164, 111, 0.55), inset 0 1px 1px rgba(255, 255, 255, 0.7);
}

.ppm-badge-btn:active {
    transform: scale(0.92);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.7);
}

.ppm-badge-btn:focus-visible {
    box-shadow: 0 0 0 3px rgba(207, 164, 111, 0.65);
}

/* Action Buttons below avatar */
.ppm-action-buttons {
    margin-bottom: 0;
}

.ppm-btn-remove {
    background: rgba(239, 68, 68, 0.12);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.28);
    font-size: 0.78rem;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 99px;
    transition: all 0.2s ease;
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.ppm-btn-remove:hover {
    background: rgba(239, 68, 68, 0.22);
    color: #ffffff;
    border-color: #ef4444;
    transform: translateY(-1px);
}

/* Modal Styling - Standalone centering & dark theme */
.ppm-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 105000;
    overflow-x: hidden;
    overflow-y: auto;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.ppm-modal.show {
    display: flex !important;
}

.ppm-modal .modal-dialog {
    margin: auto;
    max-width: 440px;
    width: 100%;
    position: relative;
    pointer-events: auto;
}

.ppm-modal .modal-content.ppm-modal-card {
    background: #1a1010;
    border: 1.5px solid rgba(207, 164, 111, 0.35);
    border-radius: 20px;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.85);
    color: #f3e7cd;
}

/* Interactive Profile Photo Crop & Positioning */
.ppm-crop-container {
    width: 220px;
    height: 220px;
    position: relative;
    user-select: none;
    -webkit-user-select: none;
    touch-action: none;
}

.ppm-crop-viewport {
    width: 220px;
    height: 220px;
    border-radius: 50%;
    position: relative;
    overflow: hidden;
    cursor: grab;
    border: 3.5px solid #cfa46f;
    box-shadow: 0 10px 32px rgba(0, 0, 0, 0.65), 0 0 24px rgba(207, 164, 111, 0.25);
    background: #0f0a0a;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
    outline: none;
}

.ppm-crop-viewport:focus-visible {
    box-shadow: 0 0 0 3px rgba(207, 164, 111, 0.6), 0 10px 32px rgba(0, 0, 0, 0.65);
}

.ppm-crop-viewport:active,
.ppm-crop-viewport.is-dragging {
    cursor: grabbing;
}

.ppm-crop-img {
    position: absolute;
    top: 50%;
    left: 50%;
    transform-origin: center center;
    pointer-events: none;
    user-select: none;
    -webkit-user-drag: none;
    -webkit-user-select: none;
    display: block;
    max-width: none !important;
    max-height: none !important;
    will-change: transform, width, height;
}

.ppm-crop-overlay-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 1.5px solid rgba(255, 255, 255, 0.18);
    pointer-events: none;
    box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.45);
}

.ppm-crop-guideline {
    position: absolute;
    pointer-events: none;
    opacity: 0.22;
    transition: opacity 0.2s ease;
}

.ppm-crop-viewport:hover .ppm-crop-guideline,
.ppm-crop-viewport.is-dragging .ppm-crop-guideline,
.ppm-crop-viewport:focus .ppm-crop-guideline {
    opacity: 0.45;
}

.ppm-crop-guide-h {
    left: 0;
    right: 0;
    top: 50%;
    height: 1px;
    background: rgba(255, 255, 255, 0.4);
    border-top: 1px dashed rgba(207, 164, 111, 0.65);
}

.ppm-crop-guide-v {
    top: 0;
    bottom: 0;
    left: 50%;
    width: 1px;
    background: rgba(255, 255, 255, 0.4);
    border-left: 1px dashed rgba(207, 164, 111, 0.65);
}

.ppm-crop-hint {
    font-size: 0.76rem;
    color: #a89a8c;
    letter-spacing: 0.2px;
}

.ppm-zoom-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(207, 164, 111, 0.3);
    color: #f3e7cd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
    line-height: 1;
}

.ppm-zoom-btn:hover {
    background: rgba(207, 164, 111, 0.2);
    border-color: #cfa46f;
    color: #ffffff;
    transform: scale(1.05);
}

.ppm-zoom-btn:active {
    transform: scale(0.95);
}

.ppm-zoom-range {
    accent-color: #cfa46f;
    cursor: pointer;
    height: 6px;
}

.ppm-zoom-range::-webkit-slider-runnable-track {
    background: rgba(255, 255, 255, 0.15);
    height: 6px;
    border-radius: 3px;
}

.ppm-zoom-range::-webkit-slider-thumb {
    background: #cfa46f;
    border: 2px solid #1a1010;
    box-shadow: 0 0 6px rgba(0, 0, 0, 0.6);
}

.ppm-btn-confirm-save {
    background: linear-gradient(135deg, #dfb582 0%, #cfa46f 50%, #a87d46 100%);
    color: #120a0a;
    font-weight: 700;
    font-size: 0.88rem;
    border: none;
    border-radius: 10px;
    padding: 9px 18px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 16px rgba(207, 164, 111, 0.35);
}

.ppm-btn-confirm-save:hover {
    background: linear-gradient(135deg, #ebd0a8 0%, #dfb582 50%, #b88d56 100%);
    color: #000;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(207, 164, 111, 0.45);
}

.ppm-btn-confirm-save:disabled {
    opacity: 0.65;
    pointer-events: none;
}

.ppm-btn-secondary {
    background: rgba(255, 255, 255, 0.06);
    color: #b39b82;
    border: 1px solid rgba(255, 255, 255, 0.12);
    font-weight: 600;
    font-size: 0.88rem;
    border-radius: 10px;
    padding: 9px 18px;
    transition: all 0.2s ease;
}

.ppm-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #f3e7cd;
}

.ppm-delete-icon-circle {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Toast */
.ppm-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: rgba(26, 16, 16, 0.96);
    border: 1px solid rgba(207, 164, 111, 0.35);
    backdrop-filter: blur(8px);
    color: #f3e7cd;
    padding: 12px 20px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
    font-size: 0.88rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    z-index: 106000;
    animation: ppmToastIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes ppmToastIn {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 576px) {
    .ppm-toast {
        bottom: 16px;
        left: 16px;
        right: 16px;
        justify-content: center;
    }
}
</style>

<script @cspNonce>
/**
 * Profile Photo Manager
 * Camera badge triggers device image picker, opens circular preview modal, allows confirm/cancel, and uploads with instant cache-busting
 */
(function() {
    const state = {
        isUploading: false,
        pendingFile: null,
        pendingDataUrl: null,
        originalSrc: null,
    };

    const routes = {
        upload: "{{ route('profile.image.update') }}",
        delete: "{{ route('profile.image.delete') }}",
        csrf: "{{ csrf_token() }}",
    };

    const targetAvatarId = "{{ $avatarId }}";

    // Cropper State & Geometry
    const cropper = {
        VIEWPORT_SIZE: 220,
        naturalWidth: 0,
        naturalHeight: 0,
        baseScale: 1,
        zoom: 1.0,
        minZoom: 1.0,
        maxZoom: 4.0,
        posX: 0,
        posY: 0,
        activePointers: new Map(),
        initialPinchDistance: null,
        initialPinchZoom: 1.0,
    };

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || routes.csrf;
    }

    function cropperSetZoom(newZoom) {
        const clampedZoom = Math.max(cropper.minZoom, Math.min(cropper.maxZoom, newZoom));
        cropper.zoom = clampedZoom;

        const slider = document.getElementById('ppmZoomSlider');
        if (slider && Math.abs(parseFloat(slider.value) - clampedZoom) > 0.005) {
            slider.value = clampedZoom.toFixed(2);
        }

        clampAndApplyTransform();
    }

    function cropperReset() {
        cropper.zoom = 1.0;
        cropper.posX = 0;
        cropper.posY = 0;

        const slider = document.getElementById('ppmZoomSlider');
        if (slider) slider.value = '1';

        clampAndApplyTransform();
    }

    function clampAndApplyTransform() {
        const previewImg = document.getElementById('ppmPreviewImg');
        if (!previewImg || !cropper.naturalWidth || !cropper.naturalHeight) return;

        const curW = cropper.naturalWidth * cropper.baseScale * cropper.zoom;
        const curH = cropper.naturalHeight * cropper.baseScale * cropper.zoom;

        // Ensure image fully covers the viewport without transparent edges:
        // leftEdge <= 0 => (V - curW)/2 + posX <= 0 => posX <= (curW - V)/2
        // rightEdge >= V => (V + curW)/2 + posX >= V => posX >= -(curW - V)/2
        const maxOffsetX = Math.max(0, (curW - cropper.VIEWPORT_SIZE) / 2);
        const maxOffsetY = Math.max(0, (curH - cropper.VIEWPORT_SIZE) / 2);

        cropper.posX = Math.max(-maxOffsetX, Math.min(maxOffsetX, cropper.posX));
        cropper.posY = Math.max(-maxOffsetY, Math.min(maxOffsetY, cropper.posY));

        previewImg.style.width = Math.round(curW) + 'px';
        previewImg.style.height = Math.round(curH) + 'px';
        previewImg.style.transform = 'translate(-50%, -50%) translate(' + Math.round(cropper.posX) + 'px, ' + Math.round(cropper.posY) + 'px)';
    }

    function initCropper(dataUrl, callback) {
        const img = new Image();
        img.onload = function() {
            cropper.naturalWidth = img.naturalWidth || img.width;
            cropper.naturalHeight = img.naturalHeight || img.height;

            if (cropper.naturalWidth < 20 || cropper.naturalHeight < 20) {
                ppmShowToast('Selected image is too small.', 'error');
                return;
            }

            cropper.baseScale = Math.max(
                cropper.VIEWPORT_SIZE / cropper.naturalWidth,
                cropper.VIEWPORT_SIZE / cropper.naturalHeight
            );
            cropper.zoom = 1.0;
            cropper.posX = 0;
            cropper.posY = 0;
            cropper.activePointers.clear();
            cropper.initialPinchDistance = null;

            const slider = document.getElementById('ppmZoomSlider');
            if (slider) slider.value = '1';

            const previewImg = document.getElementById('ppmPreviewImg');
            if (previewImg) {
                previewImg.src = dataUrl;
            }

            clampAndApplyTransform();

            if (typeof callback === 'function') callback();
        };
        img.onerror = function() {
            ppmShowToast('Unable to load photo preview. Please choose another image.', 'error');
        };
        img.src = dataUrl;
    }

    // Teleport modals to <body> to avoid clipping or stacking context traps
    function teleportModals() {
        ['ppmPreviewModal', 'ppmRemoveConfirmModal'].forEach(id => {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        });
    }

    // Modal Display Helpers
    function showModal(id) {
        teleportModals();
        const el = document.getElementById(id);
        if (!el) return;
        el.style.display = 'flex';
        el.classList.add('show');
        el.removeAttribute('aria-hidden');
        el.setAttribute('aria-modal', 'true');
        document.body.classList.add('modal-open');
    }

    function hideModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.style.display = 'none';
        el.classList.remove('show');
        el.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    function formatBytes(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    // Trigger device picker synchronously from camera badge or avatar
    window.ppmTriggerPicker = function(e) {
        if (state.isUploading) return;
        const input = document.getElementById('ppmFileInput');
        if (!input) return;

        // If triggered directly by label click, browser natively opens file picker; just reset value
        if (e && e.target && (e.target.id === 'ppmCameraBadge' || e.target.closest('#ppmCameraBadge') || e.target.id === 'ppmHoverOverlay' || e.target.closest('#ppmHoverOverlay'))) {
            input.value = '';
            return;
        }

        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        input.value = '';
        input.click();
    };

    // Clicking avatar ring forwards to camera picker
    window.ppmDropzoneClick = function(e) {
        if (e.target.closest('#ppmCameraBadge') || e.target.closest('#ppmHoverOverlay')) {
            return;
        }
        window.ppmTriggerPicker(e);
    };

    // Handle file selection from camera or file dialog
    window.ppmHandleFileSelect = function(input) {
        if (!input || !input.files || !input.files[0]) {
            return;
        }
        const file = input.files[0];

        // 1. Validate File Type (flexible for mobile camera captures)
        const isImage = (file.type && file.type.startsWith('image/')) || 
                        /\.(jpe?g|png|webp|gif|heic|heif)$/i.test(file.name || '') || 
                        (file.size > 0 && !file.type);
        if (!isImage) {
            ppmShowToast('Please select a valid image file (JPG, PNG, WEBP, HEIC).', 'error');
            return;
        }

        // 2. Validate Raw File Size (max 15MB before client-side optimization)
        if (file.size > 15 * 1024 * 1024) {
            ppmShowToast('Image is too large. Please select a photo under 15 MB.', 'error');
            return;
        }

        state.pendingFile = file;

        // 3. Read image, initialize cropper geometry, and show PREVIEW & CONFIRMATION MODAL
        const reader = new FileReader();
        reader.onload = function(e) {
            state.pendingDataUrl = e.target.result;

            initCropper(state.pendingDataUrl, function() {
                const detailsEl = document.getElementById('ppmPreviewDetails');
                if (detailsEl) {
                    detailsEl.textContent = (file.name || 'Photo') + ' • ' + formatBytes(file.size);
                }

                const errEl = document.getElementById('ppmPreviewError');
                if (errEl) errEl.classList.add('d-none');

                // Reset confirm button state
                const saveBtn = document.getElementById('ppmConfirmSaveBtn');
                const cancelBtn = document.getElementById('ppmCancelPreviewBtn');
                if (saveBtn) {
                    saveBtn.removeAttribute('disabled');
                    saveBtn.querySelector('.ppm-save-btn-content')?.classList.remove('d-none');
                    saveBtn.querySelector('.ppm-save-spinner')?.classList.add('d-none');
                }
                if (cancelBtn) cancelBtn.removeAttribute('disabled');

                // Display preview modal
                showModal('ppmPreviewModal');
            });
        };
        reader.onerror = function() {
            ppmShowToast('Unable to read the selected photo. Please try another.', 'error');
        };
        reader.readAsDataURL(file);
    };

    // Cancel preview and discard selection
    window.ppmCancelPreview = function() {
        hideModal('ppmPreviewModal');
        state.pendingFile = null;
        state.pendingDataUrl = null;
        cropper.activePointers.clear();
        cropper.initialPinchDistance = null;
        const fi = document.getElementById('ppmFileInput');
        if (fi) fi.value = '';
    };

    // User confirmed photo in preview modal: execute optimization & upload
    window.ppmExecuteSave = function() {
        if (!state.pendingFile || !state.pendingDataUrl || state.isUploading) return;

        const saveBtn = document.getElementById('ppmConfirmSaveBtn');
        const cancelBtn = document.getElementById('ppmCancelPreviewBtn');
        if (saveBtn) {
            saveBtn.setAttribute('disabled', 'true');
            saveBtn.querySelector('.ppm-save-btn-content')?.classList.add('d-none');
            saveBtn.querySelector('.ppm-save-spinner')?.classList.remove('d-none');
        }
        if (cancelBtn) cancelBtn.setAttribute('disabled', 'true');

        setLoadingState(true, 'Saving photo...');

        // Optimize into 1:1 square JPEG canvas matching user's selected position & zoom
        prepareSquareBlob(state.pendingDataUrl, state.pendingFile, function(uploadBlob) {
            executeUpload(uploadBlob);
        });
    };

    // Precise canvas extraction matching the user's interactive position and zoom
    function prepareSquareBlob(dataUrl, originalFile, callback) {
        const img = new Image();
        img.onload = function() {
            try {
                const natW = img.naturalWidth || img.width;
                const natH = img.naturalHeight || img.height;

                if (natW < 20 || natH < 20) {
                    throw new Error('Image dimensions too small.');
                }

                const baseScale = Math.max(cropper.VIEWPORT_SIZE / natW, cropper.VIEWPORT_SIZE / natH);
                const curW = natW * baseScale * cropper.zoom;
                const curH = natH * baseScale * cropper.zoom;

                const dispImgLeft = (cropper.VIEWPORT_SIZE - curW) / 2 + cropper.posX;
                const dispImgTop = (cropper.VIEWPORT_SIZE - curH) / 2 + cropper.posY;

                const scaleOnScreen = curW / natW;

                let srcCropX = (0 - dispImgLeft) / scaleOnScreen;
                let srcCropY = (0 - dispImgTop) / scaleOnScreen;
                let srcCropW = cropper.VIEWPORT_SIZE / scaleOnScreen;
                let srcCropH = cropper.VIEWPORT_SIZE / scaleOnScreen;

                // Clamp to natural image bounds
                srcCropX = Math.max(0, Math.min(natW - srcCropW, srcCropX));
                srcCropY = Math.max(0, Math.min(natH - srcCropH, srcCropY));
                srcCropW = Math.min(natW, srcCropW);
                srcCropH = Math.min(natH, srcCropH);

                const maxDim = 800;
                const targetDim = Math.max(100, Math.min(Math.round(srcCropW), maxDim));

                const canvas = document.createElement('canvas');
                canvas.width = targetDim;
                canvas.height = targetDim;
                const ctx = canvas.getContext('2d');
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, srcCropX, srcCropY, srcCropW, srcCropH, 0, 0, targetDim, targetDim);

                canvas.toBlob(function(blob) {
                    if (blob && blob.size > 0) {
                        callback(blob);
                    } else {
                        callback(originalFile);
                    }
                }, 'image/jpeg', 0.92);
            } catch (err) {
                console.warn('Square optimization fallback:', err);
                callback(originalFile);
            }
        };
        img.onerror = function() {
            callback(originalFile);
        };
        img.src = dataUrl;
    }

    // Execute AJAX upload
    function executeUpload(blob) {
        const csrfToken = getCsrfToken();
        const formData = new FormData();
        formData.append('profile_image', blob, 'profile.jpg');
        formData.append('_token', csrfToken);

        fetch(routes.upload, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: formData
        })
        .then(async response => {
            const data = await response.json().catch(() => null);
            if (response.ok && data && data.success) {
                handleUploadSuccess(data);
            } else {
                const msg = (data && (data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : null))) || 'Unable to update profile photo.';
                handleUploadError(msg);
            }
        })
        .catch(err => {
            handleUploadError('Network error while saving profile photo. Please try again.');
        });
    }

    // Upload Success State
    function handleUploadSuccess(data) {
        setLoadingState(false);
        hideModal('ppmPreviewModal');

        const saveBtn = document.getElementById('ppmConfirmSaveBtn');
        const cancelBtn = document.getElementById('ppmCancelPreviewBtn');
        if (saveBtn) {
            saveBtn.removeAttribute('disabled');
            saveBtn.querySelector('.ppm-save-btn-content')?.classList.remove('d-none');
            saveBtn.querySelector('.ppm-save-spinner')?.classList.add('d-none');
        }
        if (cancelBtn) cancelBtn.removeAttribute('disabled');

        state.pendingFile = null;
        state.pendingDataUrl = null;

        showSuccessState(data.message || 'Profile picture updated successfully!');

        const freshUrl = data.versioned_url || data.image_url;
        ppmApplyNewAvatar(freshUrl, true);
    }

    // Upload Error State
    function handleUploadError(errMsg) {
        setLoadingState(false);

        const saveBtn = document.getElementById('ppmConfirmSaveBtn');
        const cancelBtn = document.getElementById('ppmCancelPreviewBtn');
        if (saveBtn) {
            saveBtn.removeAttribute('disabled');
            saveBtn.querySelector('.ppm-save-btn-content')?.classList.remove('d-none');
            saveBtn.querySelector('.ppm-save-spinner')?.classList.add('d-none');
        }
        if (cancelBtn) cancelBtn.removeAttribute('disabled');

        const errEl = document.getElementById('ppmPreviewError');
        if (errEl) {
            errEl.textContent = errMsg;
            errEl.classList.remove('d-none');
        }

        showErrorState(errMsg);
    }

    // Loading overlay toggle
    function setLoadingState(isLoading, text) {
        state.isUploading = isLoading;
        const overlay = document.getElementById('ppmStatusOverlay');
        const spinner = document.getElementById('ppmStatusSpinner');
        const successIcon = document.getElementById('ppmStatusSuccess');
        const errorIcon = document.getElementById('ppmStatusError');
        const statusText = document.getElementById('ppmStatusText');
        const badge = document.getElementById('ppmCameraBadge');

        if (badge) {
            badge.style.pointerEvents = isLoading ? 'none' : 'auto';
            badge.style.opacity = isLoading ? '0.5' : '';
        }

        if (!overlay) return;

        if (isLoading) {
            overlay.classList.remove('d-none');
            if (spinner) spinner.classList.remove('d-none');
            if (successIcon) successIcon.classList.add('d-none');
            if (errorIcon) errorIcon.classList.add('d-none');
            if (statusText) statusText.textContent = text || 'Updating...';
        } else {
            if (spinner) spinner.classList.add('d-none');
        }
    }

    function showSuccessState(msg) {
        const overlay = document.getElementById('ppmStatusOverlay');
        const spinner = document.getElementById('ppmStatusSpinner');
        const successIcon = document.getElementById('ppmStatusSuccess');
        const statusText = document.getElementById('ppmStatusText');

        if (overlay) {
            overlay.classList.remove('d-none');
            if (spinner) spinner.classList.add('d-none');
            if (successIcon) successIcon.classList.remove('d-none');
            if (statusText) statusText.textContent = 'Saved!';
        }

        ppmShowToast(msg, 'success');

        setTimeout(() => {
            if (overlay) overlay.classList.add('d-none');
            if (successIcon) successIcon.classList.add('d-none');
        }, 1200);
    }

    function showErrorState(msg) {
        const overlay = document.getElementById('ppmStatusOverlay');
        const spinner = document.getElementById('ppmStatusSpinner');
        const errorIcon = document.getElementById('ppmStatusError');
        const statusText = document.getElementById('ppmStatusText');

        if (overlay) {
            overlay.classList.remove('d-none');
            if (spinner) spinner.classList.add('d-none');
            if (errorIcon) errorIcon.classList.remove('d-none');
            if (statusText) statusText.textContent = 'Failed';
        }

        ppmShowToast(msg, 'error');

        setTimeout(() => {
            if (overlay) overlay.classList.add('d-none');
            if (errorIcon) errorIcon.classList.add('d-none');
        }, 2200);
    }

    // Remove Profile Picture Flow
    window.ppmPromptRemove = function() {
        showModal('ppmRemoveConfirmModal');
    };

    window.ppmCloseRemoveModal = function() {
        hideModal('ppmRemoveConfirmModal');
    };

    window.ppmExecuteRemove = function() {
        const csrfToken = getCsrfToken();
        const btn = document.getElementById('ppmConfirmRemoveBtn');
        const cancelBtn = document.getElementById('ppmCancelRemoveBtn');
        if (btn) {
            btn.disabled = true;
            btn.querySelector('.ppm-remove-btn-text')?.classList.add('d-none');
            btn.querySelector('.ppm-remove-spinner')?.classList.remove('d-none');
        }
        if (cancelBtn) cancelBtn.disabled = true;

        fetch(routes.delete, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                'X-HTTP-Method-Override': 'DELETE',
            },
            body: JSON.stringify({ _token: csrfToken, _method: 'DELETE' })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                ppmApplyNewAvatar(data.versioned_url || data.image_url, false);
                ppmShowToast('Profile picture removed successfully.', 'success');
                ppmCloseRemoveModal();
            } else {
                ppmShowToast(data.message || 'Unable to remove profile picture.', 'error');
            }
        })
        .catch(() => {
            ppmShowToast('Unable to remove profile picture. Please try again.', 'error');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.querySelector('.ppm-remove-btn-text')?.classList.remove('d-none');
                btn.querySelector('.ppm-remove-spinner')?.classList.add('d-none');
            }
            if (cancelBtn) cancelBtn.disabled = false;
        });
    };

    // Update all avatar images across the application UI immediately
    function ppmApplyNewAvatar(url, hasCustom) {
        if (!url) return;
        const cacheBusted = url + (url.includes('?') ? '&' : '?') + 't=' + Date.now();

        const selectors = [
            '.ppm-avatar-img',
            '.user-avatar-img',
            '.header-profile-img',
            '.header-user-avatar',
            '.top-nav-avatar',
            '.mobile-user-avatar',
            '#studentAvatarDisplay',
            '#teacherAvatarDisplay',
            '#adminAvatarDisplay',
            '#settingsAvatarDisplay',
            '#profilePreview',
            '#' + targetAvatarId
        ];

        document.querySelectorAll(selectors.join(', ')).forEach(img => {
            img.src = cacheBusted;
        });

        // Toggle Remove button visibility
        const actionWrap = document.getElementById('ppmActionButtons');
        const removeBtn = document.getElementById('ppmRemoveBtn');
        if (actionWrap) {
            if (hasCustom) {
                actionWrap.removeAttribute('style');
                actionWrap.style.marginTop = '8px';
            } else {
                actionWrap.style.setProperty('display', 'none', 'important');
            }
        }
        if (removeBtn) {
            if (hasCustom) {
                removeBtn.removeAttribute('style');
                removeBtn.style.display = 'inline-flex';
            } else {
                removeBtn.style.setProperty('display', 'none', 'important');
            }
        }
    }

    // Toast Notification helper
    let toastTimeout;
    window.ppmShowToast = function(message, type) {
        const toast = document.getElementById('ppmToast');
        const text = document.getElementById('ppmToastMessage');
        const icon = toast ? toast.querySelector('.ppm-toast-icon') : null;
        if (!toast || !text) return;

        text.textContent = message;
        if (icon) {
            icon.className = 'ppm-toast-icon me-2 ' + (type === 'error' ? 'bi bi-exclamation-circle-fill text-danger' : 'bi bi-check-circle-fill text-success');
        }

        toast.classList.remove('d-none');
        clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => {
            toast.classList.add('d-none');
        }, 3500);
    };

    // Bind explicit event listeners to bypass any CSP inline handler restrictions
    function bindEventListeners() {
        const fileInput = document.getElementById('ppmFileInput');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                ppmHandleFileSelect(this);
            });
        }

        const cameraBadge = document.getElementById('ppmCameraBadge');
        if (cameraBadge) {
            cameraBadge.addEventListener('click', function(e) {
                if (fileInput) fileInput.value = '';
            });
            cameraBadge.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (fileInput) {
                        fileInput.value = '';
                        fileInput.click();
                    }
                }
            });
        }

        const hoverOverlay = document.getElementById('ppmHoverOverlay');
        if (hoverOverlay) {
            hoverOverlay.addEventListener('click', function(e) {
                if (fileInput) fileInput.value = '';
            });
            hoverOverlay.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (fileInput) {
                        fileInput.value = '';
                        fileInput.click();
                    }
                }
            });
        }

        const dropzone = document.getElementById('ppmDropzone');
        if (dropzone) {
            dropzone.addEventListener('click', function(e) {
                if (e.target.closest('#ppmCameraBadge') || e.target.closest('#ppmHoverOverlay')) {
                    return;
                }
                if (fileInput) {
                    fileInput.value = '';
                    fileInput.click();
                }
            });
            dropzone.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (fileInput) {
                        fileInput.value = '';
                        fileInput.click();
                    }
                }
            });
        }

        const confirmSaveBtn = document.getElementById('ppmConfirmSaveBtn');
        if (confirmSaveBtn) {
            confirmSaveBtn.addEventListener('click', ppmExecuteSave);
        }

        const cancelPreviewBtn = document.getElementById('ppmCancelPreviewBtn');
        if (cancelPreviewBtn) {
            cancelPreviewBtn.addEventListener('click', ppmCancelPreview);
        }

        const modalCloseBtn = document.querySelector('#ppmPreviewModal .btn-close');
        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', ppmCancelPreview);
        }

        const removeBtn = document.getElementById('ppmRemoveBtn');
        if (removeBtn) {
            removeBtn.addEventListener('click', ppmPromptRemove);
        }

        const confirmRemoveBtn = document.getElementById('ppmConfirmRemoveBtn');
        if (confirmRemoveBtn) {
            confirmRemoveBtn.addEventListener('click', ppmExecuteRemove);
        }

        const cancelRemoveBtn = document.getElementById('ppmCancelRemoveBtn');
        if (cancelRemoveBtn) {
            cancelRemoveBtn.addEventListener('click', ppmCloseRemoveModal);
        }
    }

    // Attach all interactive cropper handlers (drag, pinch, wheel, keyboard, buttons, slider)
    function attachCropperEventListeners() {
        const viewport = document.getElementById('ppmCropViewport');
        if (!viewport) return;

        function onPointerDown(e) {
            e.preventDefault();
            try {
                viewport.setPointerCapture(e.pointerId);
            } catch (err) {}

            cropper.activePointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            viewport.classList.add('is-dragging');

            if (cropper.activePointers.size === 2) {
                const pts = Array.from(cropper.activePointers.values());
                cropper.initialPinchDistance = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
                cropper.initialPinchZoom = cropper.zoom;
            }
        }

        function onPointerMove(e) {
            if (!cropper.activePointers.has(e.pointerId)) return;
            e.preventDefault();

            if (cropper.activePointers.size === 1) {
                const prev = cropper.activePointers.get(e.pointerId);
                const dx = e.clientX - prev.x;
                const dy = e.clientY - prev.y;
                cropper.activePointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

                cropper.posX += dx;
                cropper.posY += dy;
                clampAndApplyTransform();
            } else if (cropper.activePointers.size === 2) {
                cropper.activePointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
                const pts = Array.from(cropper.activePointers.values());
                const curDist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);

                if (cropper.initialPinchDistance && cropper.initialPinchDistance > 0) {
                    const ratio = curDist / cropper.initialPinchDistance;
                    cropperSetZoom(cropper.initialPinchZoom * ratio);
                }
            }
        }

        function onPointerUp(e) {
            try {
                if (viewport.hasPointerCapture(e.pointerId)) {
                    viewport.releasePointerCapture(e.pointerId);
                }
            } catch (err) {}

            cropper.activePointers.delete(e.pointerId);

            if (cropper.activePointers.size < 2) {
                cropper.initialPinchDistance = null;
            }
            if (cropper.activePointers.size === 0) {
                viewport.classList.remove('is-dragging');
            }
        }

        viewport.addEventListener('pointerdown', onPointerDown);
        viewport.addEventListener('pointermove', onPointerMove);
        viewport.addEventListener('pointerup', onPointerUp);
        viewport.addEventListener('pointercancel', onPointerUp);

        // Native touch event cancellation to prevent page scrolling/gestures
        viewport.addEventListener('touchstart', function(e) { e.preventDefault(); }, { passive: false });
        viewport.addEventListener('touchmove', function(e) { e.preventDefault(); }, { passive: false });

        // Wheel zoom (mouse wheel and touchpad pinch/scroll)
        viewport.addEventListener('wheel', function(e) {
            e.preventDefault();
            const delta = -Math.sign(e.deltaY) * 0.15;
            cropperSetZoom(cropper.zoom + delta);
        }, { passive: false });

        // Keyboard arrow nudging and +/- zooming for accessibility
        viewport.addEventListener('keydown', function(e) {
            let handled = true;
            const step = 10;
            if (e.key === 'ArrowLeft') {
                cropper.posX += step;
            } else if (e.key === 'ArrowRight') {
                cropper.posX -= step;
            } else if (e.key === 'ArrowUp') {
                cropper.posY += step;
            } else if (e.key === 'ArrowDown') {
                cropper.posY -= step;
            } else if (e.key === '+' || e.key === '=') {
                cropperSetZoom(cropper.zoom + 0.15);
            } else if (e.key === '-' || e.key === '_') {
                cropperSetZoom(cropper.zoom - 0.15);
            } else {
                handled = false;
            }
            if (handled) {
                e.preventDefault();
                clampAndApplyTransform();
            }
        });

        // Zoom range slider
        const slider = document.getElementById('ppmZoomSlider');
        if (slider) {
            slider.addEventListener('input', function() {
                cropperSetZoom(parseFloat(this.value));
            });
        }

        // Zoom Out Button
        const zoomOutBtn = document.getElementById('ppmZoomOutBtn');
        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                cropperSetZoom(cropper.zoom - 0.25);
            });
        }

        // Zoom In Button
        const zoomInBtn = document.getElementById('ppmZoomInBtn');
        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', function(e) {
                e.preventDefault();
                cropperSetZoom(cropper.zoom + 0.25);
            });
        }

        // Reset Crop Button
        const resetBtn = document.getElementById('ppmResetCropBtn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                cropperReset();
            });
        }
    }

    function initDragAndDrop() {
        const dropzone = document.getElementById('ppmDropzone');
        if (!dropzone) return;

        ['dragenter', 'dragover'].forEach(evt => {
            dropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!state.isUploading) dropzone.classList.add('ppm-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(evt => {
            dropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('ppm-dragover');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            if (state.isUploading) return;
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                const fakeInput = { files: e.dataTransfer.files };
                ppmHandleFileSelect(fakeInput);
            }
        });
    }

    function init() {
        teleportModals();
        bindEventListeners();
        attachCropperEventListeners();
        initDragAndDrop();
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
