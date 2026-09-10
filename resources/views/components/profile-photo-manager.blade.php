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
            
            <!-- Desktop Hover Overlay -->
            <div class="ppm-avatar-hover-overlay" onclick="ppmTriggerPicker(event)" title="Change photo">
                <i class="bi bi-camera-fill fs-3" style="pointer-events: none;"></i>
                <span class="ppm-hover-text" style="pointer-events: none;">Change</span>
            </div>

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

        <!-- Functional Camera Action Badge (The designated trigger to change profile photo) -->
        <button type="button" 
                class="ppm-badge-btn" 
                id="ppmCameraBadge" 
                onclick="ppmTriggerPicker(event)" 
                aria-label="Change profile picture" 
                title="Change profile picture">
            <i class="bi bi-camera-fill" style="pointer-events: none;"></i>
        </button>
    </div>

    <!-- Hidden Native File Input (Accessible, zero-size, non-display-none for maximum browser compatibility) -->
    <input type="file" 
           id="ppmFileInput" 
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
                <p class="small text-muted mb-3">Review your new profile picture before saving to your account.</p>
                
                <!-- Circular preview ring matching avatar styling -->
                <div class="ppm-preview-avatar-wrap mx-auto mb-3">
                    <img id="ppmPreviewImg" src="" alt="Selected Photo Preview" class="ppm-preview-img">
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

.ppm-preview-avatar-wrap {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    overflow: hidden;
    position: relative;
    border: 3.5px solid #cfa46f;
    box-shadow: 0 10px 32px rgba(0, 0, 0, 0.65), 0 0 20px rgba(207, 164, 111, 0.3);
    background: #110a0a;
}

.ppm-preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
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

<script>
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
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (state.isUploading) return;

        const input = document.getElementById('ppmFileInput');
        if (input) {
            input.value = '';
            input.click();
        }
    };

    // Clicking avatar ring forwards to camera picker
    window.ppmDropzoneClick = function(e) {
        if (e.target.closest('#ppmCameraBadge')) {
            return;
        }
        window.ppmTriggerPicker(e);
    };

    // Handle file selection from camera or file dialog
    window.ppmHandleFileSelect = function(input) {
        if (!input.files || !input.files[0]) {
            return;
        }
        const file = input.files[0];

        // 1. Validate File Type
        const isImage = file.type ? file.type.startsWith('image/') : /\.(jpe?g|png|webp|gif|heic|heif)$/i.test(file.name);
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

        // 3. Read image and show PREVIEW & CONFIRMATION MODAL
        const reader = new FileReader();
        reader.onload = function(e) {
            state.pendingDataUrl = e.target.result;

            const previewImg = document.getElementById('ppmPreviewImg');
            if (previewImg) previewImg.src = state.pendingDataUrl;

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

        // Optimize into 1:1 square JPEG canvas (max 800x800)
        prepareSquareBlob(state.pendingDataUrl, state.pendingFile, function(uploadBlob) {
            executeUpload(uploadBlob);
        });
    };

    // Auto center-crop to 1:1 square canvas (max 800x800) for crisp, lightweight transfer
    function prepareSquareBlob(dataUrl, originalFile, callback) {
        const img = new Image();
        img.onload = function() {
            try {
                const srcW = img.naturalWidth || img.width;
                const srcH = img.naturalHeight || img.height;

                if (srcW < 20 || srcH < 20) {
                    throw new Error('Image dimensions too small.');
                }

                const minSide = Math.min(srcW, srcH);
                const cropX = (srcW - minSide) / 2;
                const cropY = (srcH - minSide) / 2;

                const maxDim = 800;
                const targetDim = Math.min(minSide, maxDim);

                const canvas = document.createElement('canvas');
                canvas.width = targetDim;
                canvas.height = targetDim;
                const ctx = canvas.getContext('2d');
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, cropX, cropY, minSide, minSide, 0, 0, targetDim, targetDim);

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
        const formData = new FormData();
        formData.append('profile_image', blob, 'profile.jpg');
        formData.append('_token', routes.csrf);

        fetch(routes.upload, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': routes.csrf,
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

    // Keyboard & Drag-and-drop
    function initKeyboardSupport() {
        const badge = document.getElementById('ppmCameraBadge');
        if (badge) {
            badge.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    window.ppmTriggerPicker(e);
                }
            });
        }
        const dropzone = document.getElementById('ppmDropzone');
        if (dropzone) {
            dropzone.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    window.ppmTriggerPicker(e);
                }
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

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            teleportModals();
            initKeyboardSupport();
            initDragAndDrop();
        });
    } else {
        teleportModals();
        initKeyboardSupport();
        initDragAndDrop();
    }
})();
</script>
