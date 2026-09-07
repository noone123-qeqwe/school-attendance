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
    <div class="ppm-avatar-dropzone position-relative mb-3"
         id="ppmDropzone"
         style="width: {{ $size }}px; height: {{ $size }}px;"
         title="Click or drag and drop a new profile photo">
        <div class="ppm-avatar-ring">
            <img id="{{ $avatarId }}"
                 src="{{ $avatarUrl }}"
                 alt="{{ $user->name ?? 'User' }}"
                 class="ppm-avatar-img user-avatar-img"
                 onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'User') }}&background=800000&color=fff&size=256'">
            
            <div class="ppm-avatar-hover-overlay" onclick="ppmOpenChoiceMenu()">
                <i class="bi bi-camera-fill fs-3"></i>
                <span class="ppm-hover-text">Change</span>
            </div>
        </div>

        <!-- Quick Camera Action Badge -->
        <button type="button" class="ppm-badge-btn" onclick="ppmOpenChoiceMenu()" aria-label="Change photo" title="Change Photo">
            <i class="bi bi-camera-fill"></i>
        </button>
    </div>

    <!-- Actions Row -->
    <div class="ppm-action-buttons d-flex flex-wrap gap-2 {{ $align === 'center' ? 'justify-content-center' : '' }}">
        <button type="button" class="btn ppm-btn-change" onclick="ppmOpenChoiceMenu()">
            <i class="bi bi-camera-fill me-1"></i> Change Photo
        </button>

        <button type="button" 
                class="btn ppm-btn-remove" 
                id="ppmRemoveBtn" 
                onclick="ppmPromptRemove()" 
                style="{{ $hasCustom ? '' : 'display: none !important;' }}">
            <i class="bi bi-trash3 me-1"></i> Remove Photo
        </button>
    </div>

    @if($showMeta && $user)
        <div class="ppm-meta mt-2">
            <div class="ppm-name fw-bold">{{ $user->name }}</div>
            <div class="ppm-sub text-muted small">{{ $user->student_number ?? $user->email }}</div>
        </div>
    @endif

    <!-- Hidden native file inputs -->
    <input type="file" 
           id="ppmFileInput" 
           class="d-none" 
           accept="image/jpeg,image/png,image/jpg,image/webp,image/heic,image/heif" 
           onchange="ppmHandleFileSelect(this)">

    <input type="file" 
           id="ppmCameraInput" 
           class="d-none" 
           accept="image/*" 
           capture="user" 
           onchange="ppmHandleFileSelect(this)">
</div>

<!-- ========================================== -->
<!-- MOBILE / DESKTOP CHOICE ACTION SHEET MODAL -->
<!-- ========================================== -->
<div class="modal fade ppm-modal" id="ppmChoiceModal" tabindex="-1" aria-labelledby="ppmChoiceModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content ppm-modal-card">
            <div class="modal-header border-0 pb-1">
                <h5 class="modal-title fs-6 fw-bold text-light" id="ppmChoiceModalTitle">
                    <i class="bi bi-person-bounding-box text-warning me-2"></i>Profile Picture
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2 pb-3">
                <p class="ppm-modal-hint mb-3">Choose an option to update your photo:</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn ppm-sheet-btn" onclick="ppmTriggerCamera()">
                        <i class="bi bi-camera-fill ppm-sheet-icon"></i>
                        <div class="text-start">
                            <div class="fw-semibold text-light">Take Photo</div>
                            <div class="small text-muted">Use device camera</div>
                        </div>
                    </button>

                    <button type="button" class="btn ppm-sheet-btn" onclick="ppmTriggerGallery()">
                        <i class="bi bi-images ppm-sheet-icon"></i>
                        <div class="text-start">
                            <div class="fw-semibold text-light">Upload / From Gallery</div>
                            <div class="small text-muted">Choose JPG, PNG, or WEBP</div>
                        </div>
                    </button>

                    <button type="button" 
                            class="btn ppm-sheet-btn ppm-sheet-btn-danger" 
                            id="ppmSheetRemoveBtn" 
                            onclick="ppmPromptRemoveFromSheet()" 
                            style="{{ $hasCustom ? '' : 'display: none !important;' }}">
                        <i class="bi bi-trash3-fill ppm-sheet-icon text-danger"></i>
                        <div class="text-start">
                            <div class="fw-semibold text-danger">Remove Photo</div>
                            <div class="small text-muted">Restore default avatar</div>
                        </div>
                    </button>

                    <button type="button" class="btn ppm-sheet-cancel mt-2" data-bs-dismiss="modal">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 1:1 SQUARE CROP & PREVIEW MODAL            -->
<!-- ========================================== -->
<div class="modal fade ppm-modal" id="ppmCropModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="ppmCropModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ppm-modal-card">
            <div class="modal-header border-0 pb-2">
                <div>
                    <h5 class="modal-title fs-6 fw-bold text-light" id="ppmCropModalTitle">
                        <i class="bi bi-crop text-warning me-2"></i>Adjust Profile Photo
                    </h5>
                    <div class="ppm-modal-hint">Drag to reposition &bull; Slider to zoom</div>
                </div>
                <button type="button" class="btn-close btn-close-white" onclick="ppmCloseCropper()" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3">
                <!-- Validation / Error Alert inside modal -->
                <div id="ppmCropError" class="alert alert-danger py-2 px-3 small d-none" role="alert"></div>

                <!-- Cropper Canvas Container -->
                <div class="ppm-cropper-viewport position-relative mx-auto" id="ppmCropViewport">
                    <canvas id="ppmCropCanvas" width="360" height="360"></canvas>
                    <!-- Circular guide overlay -->
                    <div class="ppm-circular-guide"></div>
                </div>

                <!-- Zoom Controls -->
                <div class="d-flex align-items-center gap-3 mt-3 px-2">
                    <button type="button" class="btn ppm-ctrl-btn" onclick="ppmZoomStep(-0.1)" title="Zoom Out">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                    <input type="range" class="form-range ppm-zoom-slider" id="ppmZoomSlider" min="1" max="3" step="0.01" value="1" oninput="ppmOnZoomChange(this.value)">
                    <button type="button" class="btn ppm-ctrl-btn" onclick="ppmZoomStep(0.1)" title="Zoom In">
                        <i class="bi bi-zoom-in"></i>
                    </button>
                    <button type="button" class="btn ppm-ctrl-btn" onclick="ppmResetCrop()" title="Reset Alignment">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>

                <!-- Upload Progress Feedback -->
                <div id="ppmUploadProgressContainer" class="mt-3 d-none">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span id="ppmProgressText">Uploading profile picture...</span>
                        <span id="ppmProgressPercent">0%</span>
                    </div>
                    <div class="progress ppm-progress-bar-bg" style="height: 6px;">
                        <div id="ppmProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 0%;"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn ppm-btn-secondary px-3" onclick="ppmCloseCropper()" id="ppmCancelCropBtn">
                    Cancel
                </button>
                <button type="button" class="btn ppm-btn-primary px-4" onclick="ppmSaveCroppedPhoto()" id="ppmSaveCropBtn">
                    <span class="ppm-btn-text"><i class="bi bi-check2 me-1"></i> Save Photo</span>
                    <span class="ppm-btn-spinner spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- REMOVE CONFIRMATION MODAL                  -->
<!-- ========================================== -->
<div class="modal fade ppm-modal" id="ppmRemoveConfirmModal" tabindex="-1" aria-labelledby="ppmRemoveModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content ppm-modal-card text-center p-3">
            <div class="modal-body p-2">
                <div class="ppm-delete-icon-circle mx-auto mb-3">
                    <i class="bi bi-trash3-fill text-danger fs-3"></i>
                </div>
                <h5 class="fs-6 fw-bold text-light mb-1" id="ppmRemoveModalTitle">Remove Profile Picture?</h5>
                <p class="small text-muted mb-4">Your picture will be removed and restored to your default initials avatar.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn ppm-btn-secondary px-3 w-50" data-bs-dismiss="modal" id="ppmCancelRemoveBtn">
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

<!-- Subtle Floating Toast Alert -->
<div id="ppmToast" class="ppm-toast d-none" role="status" aria-live="polite">
    <i class="bi bi-check-circle-fill ppm-toast-icon me-2 text-success"></i>
    <span id="ppmToastMessage">Profile picture updated</span>
</div>

<style>
/* ── PROFILE PHOTO MANAGER COMPONENT STYLES ── */
.ppm-avatar-dropzone {
    cursor: pointer;
    user-select: none;
}
.ppm-avatar-ring {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    overflow: hidden;
    position: relative;
    border: 3px solid rgba(207, 164, 111, 0.4);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    background: #150d0a;
    transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}
.ppm-avatar-dropzone:hover .ppm-avatar-ring,
.ppm-avatar-dropzone.ppm-dragover .ppm-avatar-ring {
    transform: scale(1.03);
    border-color: #cfa46f;
    box-shadow: 0 12px 32px rgba(207, 164, 111, 0.3);
}
.ppm-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
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
}
.ppm-hover-text {
    font-size: 0.75rem;
    font-weight: 700;
    margin-top: 3px;
    letter-spacing: 0.5px;
}
.ppm-avatar-dropzone:hover .ppm-avatar-hover-overlay,
.ppm-avatar-dropzone.ppm-dragover .ppm-avatar-hover-overlay {
    opacity: 1;
}
.ppm-badge-btn {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #cfa46f 0%, #a87d46 100%);
    color: #120a0a;
    border: 2px solid #1a1010;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.85rem;
    transition: transform 0.2s ease, background-color 0.2s;
    z-index: 2;
}
.ppm-badge-btn:hover {
    transform: scale(1.12);
}

/* Action Buttons */
.ppm-btn-change {
    background: rgba(207, 164, 111, 0.15);
    color: #f3e7cd;
    border: 1px solid rgba(207, 164, 111, 0.35);
    font-size: 0.82rem;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 99px;
    transition: all 0.2s ease;
}
.ppm-btn-change:hover {
    background: rgba(207, 164, 111, 0.25);
    color: #ffffff;
    border-color: #cfa46f;
    transform: translateY(-1px);
}
.ppm-btn-remove {
    background: rgba(239, 68, 68, 0.12);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.28);
    font-size: 0.82rem;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 99px;
    transition: all 0.2s ease;
}
.ppm-btn-remove:hover {
    background: rgba(239, 68, 68, 0.22);
    color: #ffffff;
    border-color: #ef4444;
    transform: translateY(-1px);
}

/* Modal Dialogs */
.ppm-modal .modal-content.ppm-modal-card {
    background: #1a1010;
    border: 1px solid rgba(207, 164, 111, 0.28);
    border-radius: 18px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
    color: #f3e7cd;
}
.ppm-modal-hint {
    font-size: 0.78rem;
    color: #b39b82;
    margin: 0;
}
.ppm-sheet-btn {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    color: #f3e7cd;
    transition: all 0.2s ease;
    text-align: left;
}
.ppm-sheet-btn:hover {
    background: rgba(207, 164, 111, 0.14);
    border-color: rgba(207, 164, 111, 0.35);
    color: #ffffff;
}
.ppm-sheet-icon {
    font-size: 1.4rem;
    color: #cfa46f;
    width: 28px;
    text-align: center;
}
.ppm-sheet-btn-danger:hover {
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.35);
}
.ppm-sheet-cancel {
    background: transparent;
    color: #b39b82;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 9px;
    font-weight: 600;
    font-size: 0.85rem;
}
.ppm-sheet-cancel:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #f3e7cd;
}

/* Cropper Viewport */
.ppm-cropper-viewport {
    width: 320px;
    height: 320px;
    max-width: 100%;
    border-radius: 14px;
    overflow: hidden;
    background: #0d0705;
    touch-action: none;
    cursor: grab;
    border: 1px solid rgba(207, 164, 111, 0.2);
}
.ppm-cropper-viewport:active {
    cursor: grabbing;
}
#ppmCropCanvas {
    width: 100%;
    height: 100%;
    display: block;
}
.ppm-circular-guide {
    position: absolute;
    inset: 0;
    pointer-events: none;
    border-radius: 50%;
    box-shadow: 0 0 0 9999px rgba(10, 5, 4, 0.65);
    border: 2px dashed rgba(207, 164, 111, 0.85);
}

/* Controls */
.ppm-ctrl-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #f3e7cd;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: all 0.2s;
}
.ppm-ctrl-btn:hover {
    background: rgba(207, 164, 111, 0.2);
    border-color: #cfa46f;
    color: #fff;
}
.ppm-zoom-slider {
    accent-color: #cfa46f;
}
.ppm-btn-primary {
    background: linear-gradient(135deg, #cfa46f 0%, #b88a52 100%);
    color: #1a0f0a;
    font-weight: 700;
    font-size: 0.88rem;
    border-radius: 10px;
    border: none;
    transition: all 0.2s;
}
.ppm-btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #dfb582 0%, #cfa46f 100%);
    color: #120a06;
    transform: translateY(-1px);
}
.ppm-btn-secondary {
    background: rgba(255, 255, 255, 0.06);
    color: #b39b82;
    border: 1px solid rgba(255, 255, 255, 0.12);
    font-weight: 600;
    font-size: 0.88rem;
    border-radius: 10px;
}
.ppm-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #f3e7cd;
}
.ppm-progress-bar-bg {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 99px;
    overflow: hidden;
}
.ppm-delete-icon-circle {
    width: 60px;
    height: 60px;
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
    background: rgba(26, 16, 16, 0.95);
    border: 1px solid rgba(207, 164, 111, 0.35);
    backdrop-filter: blur(8px);
    color: #f3e7cd;
    padding: 12px 20px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    font-size: 0.88rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    z-index: 1099;
    animation: ppmToastIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes ppmToastIn {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 576px) {
    .ppm-cropper-viewport {
        width: 280px;
        height: 280px;
    }
    .ppm-toast {
        bottom: 16px;
        left: 16px;
        right: 16px;
        justify-content: center;
    }
}
</style>

<script>
/**
 * Profile Photo Manager Javascript Engine
 * Pure Vanilla JS + HTML5 Canvas (No heavy dependencies)
 */
(function() {
    // State
    const state = {
        image: null,
        origWidth: 0,
        origHeight: 0,
        scale: 1,
        minScale: 1,
        maxScale: 3,
        offsetX: 0,
        offsetY: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        initialDistance: 0,
        isUploading: false,
        file: null,
    };

    const routes = {
        upload: "{{ route('profile.image.update') }}",
        delete: "{{ route('profile.image.delete') }}",
        csrf: "{{ csrf_token() }}",
    };

    // DOM Elements
    let canvas, ctx, viewport, zoomSlider, choiceModal, cropModal, removeModal;

    function initPPM() {
        canvas = document.getElementById('ppmCropCanvas');
        if (canvas) ctx = canvas.getContext('2d');
        viewport = document.getElementById('ppmCropViewport');
        zoomSlider = document.getElementById('ppmZoomSlider');

        // Drag and drop onto avatar
        const dropzone = document.getElementById('ppmDropzone');
        if (dropzone) {
            ['dragenter', 'dragover'].forEach(evt => {
                dropzone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('ppm-dragover');
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
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                    processSelectedFile(e.dataTransfer.files[0]);
                }
            });
        }

        // Canvas Interaction Listeners
        if (viewport) {
            // Mouse drag
            viewport.addEventListener('mousedown', onPointerDown);
            window.addEventListener('mousemove', onPointerMove);
            window.addEventListener('mouseup', onPointerUp);

            // Wheel zoom
            viewport.addEventListener('wheel', (e) => {
                e.preventDefault();
                const delta = e.deltaY < 0 ? 0.08 : -0.08;
                ppmZoomStep(delta);
            }, { passive: false });

            // Touch drag & pinch-to-zoom
            viewport.addEventListener('touchstart', onTouchStart, { passive: false });
            viewport.addEventListener('touchmove', onTouchMove, { passive: false });
            viewport.addEventListener('touchend', onTouchEnd);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPPM);
    } else {
        initPPM();
    }

    // Modal Helpers (Bootstrap 5 safe)
    function getBsModal(id) {
        const el = document.getElementById(id);
        if (!el || typeof bootstrap === 'undefined') return null;
        return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    }

    window.ppmOpenChoiceMenu = function() {
        const m = getBsModal('ppmChoiceModal');
        if (m) m.show();
        else ppmTriggerGallery();
    };

    window.ppmTriggerCamera = function() {
        const m = getBsModal('ppmChoiceModal');
        if (m) m.hide();
        const input = document.getElementById('ppmCameraInput');
        if (input) {
            input.value = '';
            input.click();
        }
    };

    window.ppmTriggerGallery = function() {
        const m = getBsModal('ppmChoiceModal');
        if (m) m.hide();
        const input = document.getElementById('ppmFileInput');
        if (input) {
            input.value = '';
            input.click();
        }
    };

    window.ppmHandleFileSelect = function(input) {
        if (!input.files || !input.files[0]) return;
        processSelectedFile(input.files[0]);
    };

    function showCropError(msg) {
        const box = document.getElementById('ppmCropError');
        if (box) {
            box.textContent = msg;
            box.classList.remove('d-none');
        }
    }

    function hideCropError() {
        const box = document.getElementById('ppmCropError');
        if (box) box.classList.add('d-none');
    }

    // File validation & Cropper Launch
    function processSelectedFile(file) {
        hideCropError();

        // 1. Validate File Type
        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif', 'image/heic', 'image/heif'];
        const isImage = file.type ? file.type.startsWith('image/') : /\.(jpe?g|png|webp|gif|heic|heif)$/i.test(file.name);
        if (!isImage) {
            ppmShowToast('Please select a valid image file (JPG, PNG, WEBP, HEIC).', 'error');
            return;
        }

        // 2. Validate File Size (5MB max)
        const maxBytes = 5 * 1024 * 1024;
        if (file.size > maxBytes) {
            ppmShowToast('Image is too large. Maximum size is 5 MB.', 'error');
            return;
        }

        state.file = file;

        // 3. Read image and validate dimensions
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                if (img.naturalWidth < 100 || img.naturalHeight < 100) {
                    ppmShowToast('Image dimensions are too small. Minimum is 100×100 pixels.', 'error');
                    return;
                }
                if (img.naturalWidth > 6000 || img.naturalHeight > 6000) {
                    ppmShowToast('Image dimensions exceed 6000×6000 pixels. Please choose a smaller image.', 'error');
                    return;
                }

                initCropper(img);
                const cm = getBsModal('ppmCropModal');
                if (cm) cm.show();
            };
            img.onerror = function() {
                ppmShowToast('Unable to read selected image. Please try another file.', 'error');
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    // Initialize Canvas Cropper
    function initCropper(img) {
        state.image = img;
        state.origWidth = img.naturalWidth;
        state.origHeight = img.naturalHeight;

        const cw = canvas.width;
        const ch = canvas.height;

        // Base scale: scale so image covers the canvas box
        const scaleX = cw / state.origWidth;
        const scaleY = ch / state.origHeight;
        state.minScale = Math.max(scaleX, scaleY);
        state.scale = state.minScale;
        state.maxScale = state.minScale * 3.5;

        // Center initially
        state.offsetX = (cw - state.origWidth * state.scale) / 2;
        state.offsetY = (ch - state.origHeight * state.scale) / 2;

        if (zoomSlider) {
            zoomSlider.min = state.minScale;
            zoomSlider.max = state.maxScale;
            zoomSlider.value = state.scale;
        }

        hideProgress();
        setSavingState(false);
        drawCanvas();
    }

    function drawCanvas() {
        if (!ctx || !state.image) return;
        const cw = canvas.width;
        const ch = canvas.height;

        ctx.clearRect(0, 0, cw, ch);

        // Keep inside bounds
        const renderedW = state.origWidth * state.scale;
        const renderedH = state.origHeight * state.scale;

        if (state.offsetX > 0) state.offsetX = 0;
        if (state.offsetY > 0) state.offsetY = 0;
        if (state.offsetX < cw - renderedW) state.offsetX = cw - renderedW;
        if (state.offsetY < ch - renderedH) state.offsetY = ch - renderedH;

        ctx.drawImage(state.image, state.offsetX, state.offsetY, renderedW, renderedH);
    }

    // Mouse drag
    function onPointerDown(e) {
        state.isDragging = true;
        state.dragStartX = e.clientX - state.offsetX;
        state.dragStartY = e.clientY - state.offsetY;
    }

    function onPointerMove(e) {
        if (!state.isDragging) return;
        state.offsetX = e.clientX - state.dragStartX;
        state.offsetY = e.clientY - state.dragStartY;
        drawCanvas();
    }

    function onPointerUp() {
        state.isDragging = false;
    }

    // Touch events (Pan & Pinch)
    function onTouchStart(e) {
        if (e.touches.length === 1) {
            state.isDragging = true;
            state.dragStartX = e.touches[0].clientX - state.offsetX;
            state.dragStartY = e.touches[0].clientY - state.offsetY;
        } else if (e.touches.length === 2) {
            state.isDragging = false;
            state.initialDistance = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
        }
    }

    function onTouchMove(e) {
        e.preventDefault();
        if (state.isDragging && e.touches.length === 1) {
            state.offsetX = e.touches[0].clientX - state.dragStartX;
            state.offsetY = e.touches[0].clientY - state.dragStartY;
            drawCanvas();
        } else if (e.touches.length === 2) {
            const currentDistance = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
            if (state.initialDistance > 0) {
                const ratio = currentDistance / state.initialDistance;
                const newScale = Math.min(Math.max(state.scale * ratio, state.minScale), state.maxScale);
                applyZoom(newScale);
                state.initialDistance = currentDistance;
            }
        }
    }

    function onTouchEnd() {
        state.isDragging = false;
        state.initialDistance = 0;
    }

    function applyZoom(newScale) {
        const cw = canvas.width;
        const ch = canvas.height;

        // Zoom relative to center
        const centerX = cw / 2;
        const centerY = ch / 2;

        const imgPointX = (centerX - state.offsetX) / state.scale;
        const imgPointY = (centerY - state.offsetY) / state.scale;

        state.scale = newScale;
        state.offsetX = centerX - imgPointX * state.scale;
        state.offsetY = centerY - imgPointY * state.scale;

        if (zoomSlider) zoomSlider.value = state.scale;
        drawCanvas();
    }

    window.ppmOnZoomChange = function(val) {
        applyZoom(parseFloat(val));
    };

    window.ppmZoomStep = function(delta) {
        const current = state.scale;
        const target = Math.min(Math.max(current + delta * (state.maxScale - state.minScale), state.minScale), state.maxScale);
        applyZoom(target);
    };

    window.ppmResetCrop = function() {
        if (state.image) initCropper(state.image);
    };

    window.ppmCloseCropper = function() {
        if (state.isUploading) return;
        const cm = getBsModal('ppmCropModal');
        if (cm) cm.hide();
    };

    function showProgress(percent) {
        const container = document.getElementById('ppmUploadProgressContainer');
        const bar = document.getElementById('ppmProgressBar');
        const text = document.getElementById('ppmProgressPercent');
        if (container) container.classList.remove('d-none');
        if (bar) bar.style.width = percent + '%';
        if (text) text.textContent = percent + '%';
    }

    function hideProgress() {
        const container = document.getElementById('ppmUploadProgressContainer');
        if (container) container.classList.add('d-none');
    }

    function setSavingState(isSaving) {
        state.isUploading = isSaving;
        const saveBtn = document.getElementById('ppmSaveCropBtn');
        const cancelBtn = document.getElementById('ppmCancelCropBtn');
        if (saveBtn) {
            saveBtn.disabled = isSaving;
            saveBtn.querySelector('.ppm-btn-text')?.classList.toggle('d-none', isSaving);
            saveBtn.querySelector('.ppm-btn-spinner')?.classList.toggle('d-none', !isSaving);
        }
        if (cancelBtn) cancelBtn.disabled = isSaving;
    }

    // Save & Upload
    window.ppmSaveCroppedPhoto = function() {
        if (state.isUploading) return;
        setSavingState(true);
        showProgress(15);

        // Generate clean 512x512 square exported blob
        const exportCanvas = document.createElement('canvas');
        const outSize = 512;
        exportCanvas.width = outSize;
        exportCanvas.height = outSize;
        const exportCtx = exportCanvas.getContext('2d');

        // Map crop viewport (360x360) directly to output (512x512)
        const ratio = outSize / canvas.width;
        exportCtx.drawImage(
            canvas,
            0, 0, canvas.width, canvas.height,
            0, 0, outSize, outSize
        );

        exportCanvas.toBlob(function(blob) {
            if (!blob) {
                ppmShowToast('Unable to process photo crop.', 'error');
                setSavingState(false);
                return;
            }

            uploadBlob(blob);
        }, 'image/jpeg', 0.92);
    };

    function uploadBlob(blob) {
        const formData = new FormData();
        formData.append('profile_image', blob, 'profile.jpg');
        formData.append('_token', routes.csrf);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', routes.upload, true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 85);
                showProgress(Math.max(20, percent));
            }
        };

        xhr.onload = function() {
            showProgress(100);
            setTimeout(() => {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300 && data.success) {
                        ppmApplyNewAvatar(data.versioned_url || data.image_url, true);
                        ppmShowToast(data.message || 'Profile picture updated successfully!', 'success');
                        const cm = getBsModal('ppmCropModal');
                        if (cm) cm.hide();
                    } else {
                        const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Unable to update your profile picture.');
                        showCropError(errMsg);
                        ppmShowToast(errMsg, 'error');
                    }
                } catch (e) {
                    showCropError('Upload completed with unexpected server response.');
                    ppmShowToast('Unable to update your profile picture. Please try again.', 'error');
                } finally {
                    setSavingState(false);
                }
            }, 300);
        };

        xhr.onerror = function() {
            setSavingState(false);
            showCropError('Network error during upload. Please check your connection and retry.');
            ppmShowToast('Network error. Please try again.', 'error');
        };

        xhr.send(formData);
    }

    // Remove Flow
    window.ppmPromptRemove = function() {
        const m = getBsModal('ppmRemoveConfirmModal');
        if (m) m.show();
    };

    window.ppmPromptRemoveFromSheet = function() {
        const sm = getBsModal('ppmChoiceModal');
        if (sm) sm.hide();
        ppmPromptRemove();
    };

    window.ppmExecuteRemove = function() {
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
                'X-CSRF-TOKEN': routes.csrf,
                'X-HTTP-Method-Override': 'DELETE',
            },
            body: JSON.stringify({ _token: routes.csrf, _method: 'DELETE' })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                ppmApplyNewAvatar(data.versioned_url || data.image_url, false);
                ppmShowToast('Profile picture removed successfully.', 'success');
                const rm = getBsModal('ppmRemoveConfirmModal');
                if (rm) rm.hide();
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

    // Update all avatars across the UI immediately
    function ppmApplyNewAvatar(url, hasCustom) {
        if (!url) return;
        const cacheBusted = url + (url.includes('?') ? '&' : '?') + 't=' + Date.now();

        // Update all avatar selectors everywhere across headers, sidebars, dashboard
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
            '#profilePreview'
        ];

        document.querySelectorAll(selectors.join(', ')).forEach(img => {
            img.src = cacheBusted;
        });

        // Toggle Remove buttons visibility
        const removeBtns = [
            document.getElementById('ppmRemoveBtn'),
            document.getElementById('ppmSheetRemoveBtn')
        ];
        removeBtns.forEach(btn => {
            if (btn) {
                if (hasCustom) {
                    btn.removeAttribute('style');
                    btn.style.display = '';
                } else {
                    btn.style.setProperty('display', 'none', 'important');
                }
            }
        });
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
})();
</script>
