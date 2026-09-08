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
         title="Click to select or capture a new profile photo">
        
        <div class="ppm-avatar-ring">
            <img id="{{ $avatarId }}"
                 src="{{ $avatarUrl }}"
                 alt="{{ $user->name ?? 'User' }}"
                 class="ppm-avatar-img user-avatar-img"
                 onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'User') }}&background=800000&color=fff&size=256'">
            
            <!-- Desktop Hover Overlay -->
            <div class="ppm-avatar-hover-overlay" onclick="ppmTriggerPicker(event)" title="Change photo">
                <i class="bi bi-camera-fill fs-3"></i>
                <span class="ppm-hover-text">Change</span>
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

        <!-- Functional Camera Action Badge (Clickable, accessible, mobile-optimized) -->
        <button type="button" 
                class="ppm-badge-btn" 
                id="ppmCameraBadge" 
                onclick="ppmTriggerPicker(event)" 
                aria-label="Upload new profile photo" 
                title="Change photo">
            <i class="bi bi-camera-fill"></i>
        </button>
    </div>

    <!-- Hidden Native File & Camera Input -->
    <input type="file" 
           id="ppmFileInput" 
           class="d-none" 
           accept="image/*,image/jpeg,image/png,image/jpg,image/webp,image/gif,image/heic,image/heif" 
           onchange="ppmHandleFileSelect(this)">

    <!-- Optional Remove Action (Displayed strictly when a custom photo exists) -->
    <div class="ppm-action-buttons mt-2 {{ $align === 'center' ? 'text-center' : '' }}" 
         id="ppmActionButtons" 
         style="{{ $hasCustom ? '' : 'display: none !important;' }}">
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

/* Quick Camera Action Badge */
.ppm-badge-btn {
    position: absolute;
    bottom: 0px;
    right: 0px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #dfb582 0%, #cfa46f 50%, #a87d46 100%);
    color: #120a0a;
    border: 2.5px solid #1a1010;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.55), inset 0 1px 1px rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.88rem;
    padding: 0;
    margin: 0;
    line-height: 1;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease, filter 0.2s ease;
    z-index: 6;
    outline: none;
    -webkit-tap-highlight-color: transparent;
}

/* Expanded clickable/tap area for comfortable mobile tapping */
.ppm-badge-btn::before {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    background: transparent;
    z-index: 1;
}

/* Visual indication that the camera icon can be clicked */
.ppm-badge-btn:hover {
    transform: scale(1.15);
    filter: brightness(1.08);
    box-shadow: 0 6px 18px rgba(207, 164, 111, 0.55), inset 0 1px 1px rgba(255, 255, 255, 0.7);
}

.ppm-badge-btn:active {
    transform: scale(0.92);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.7);
}

.ppm-badge-btn:focus-visible {
    box-shadow: 0 0 0 3px rgba(207, 164, 111, 0.65);
}

/* Remove Button styling */
.ppm-action-buttons {
    margin-bottom: 0;
}

.ppm-btn-remove {
    background: rgba(239, 68, 68, 0.12);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.28);
    font-size: 0.78rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 99px;
    transition: all 0.2s ease;
    line-height: 1.2;
}

.ppm-btn-remove:hover {
    background: rgba(239, 68, 68, 0.22);
    color: #ffffff;
    border-color: #ef4444;
    transform: translateY(-1px);
}

/* Remove Modal */
.ppm-modal .modal-content.ppm-modal-card {
    background: #1a1010;
    border: 1px solid rgba(207, 164, 111, 0.28);
    border-radius: 18px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.75);
    color: #f3e7cd;
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
    z-index: 1099;
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
 * Profile Photo Manager - Streamlined & Autonomous
 * Supports direct file/camera picker, live preview, square auto-crop, AJAX upload & rollback
 */
(function() {
    const state = {
        isUploading: false,
        originalSrc: null,
    };

    const routes = {
        upload: "{{ route('profile.image.update') }}",
        delete: "{{ route('profile.image.delete') }}",
        csrf: "{{ csrf_token() }}",
    };

    const targetAvatarId = "{{ $avatarId }}";

    // Trigger device picker
    window.ppmTriggerPicker = function(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (state.isUploading) return;

        const input = document.getElementById('ppmFileInput');
        if (input) {
            input.value = ''; // Reset to ensure re-selecting same photo triggers onchange
            input.click();
        }
    };

    // Dropzone click handler (clicking avatar also opens picker)
    window.ppmDropzoneClick = function(e) {
        if (e.target.closest('#ppmCameraBadge') || e.target.closest('.ppm-avatar-hover-overlay')) {
            return;
        }
        window.ppmTriggerPicker(e);
    };

    // Enter/Space key on dropzone or badge
    function initKeyboardSupport() {
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

    // Drag-and-drop on dropzone
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
                processAndUploadPhoto(e.dataTransfer.files[0]);
            }
        });
    }

    // Handle file input change
    window.ppmHandleFileSelect = function(input) {
        if (!input.files || !input.files[0]) {
            // User cancelled picker/camera - do nothing and keep current photo
            return;
        }
        const file = input.files[0];
        processAndUploadPhoto(file);
    };

    // Process, immediate preview, auto-crop 1:1, and upload
    function processAndUploadPhoto(file) {
        if (state.isUploading) return;

        // 1. Validate File Type
        const isImage = file.type ? file.type.startsWith('image/') : /\.(jpe?g|png|webp|gif|heic|heif)$/i.test(file.name);
        if (!isImage) {
            ppmShowToast('Please select a valid image file (JPG, PNG, WEBP, HEIC).', 'error');
            return;
        }

        // 2. Validate Raw File Size (max 15MB before optimization)
        if (file.size > 15 * 1024 * 1024) {
            ppmShowToast('Image is too large. Please select a photo under 15 MB.', 'error');
            return;
        }

        // Find primary avatar image element
        const avatarImg = document.getElementById(targetAvatarId) || document.querySelector('.ppm-avatar-img');
        if (avatarImg) {
            state.originalSrc = avatarImg.src;
        }

        // 3. Immediate local preview using FileReader
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewUrl = e.target.result;
            // Update preview immediately
            if (avatarImg) avatarImg.src = previewUrl;
            document.querySelectorAll('.ppm-avatar-img').forEach(img => {
                img.src = previewUrl;
            });

            // 4. Optimize into 1:1 square JPEG (up to 800x800) and start upload
            prepareSquareBlob(previewUrl, file, function(uploadBlob) {
                executeUpload(uploadBlob);
            });
        };
        reader.onerror = function() {
            ppmShowToast('Unable to read selected photo. Please try another file.', 'error');
        };

        // Show loading state immediately
        setLoadingState(true, 'Updating...');
        reader.readAsDataURL(file);
    }

    // Auto center-crop to 1:1 square canvas for high-quality, lightweight transfer
    function prepareSquareBlob(dataUrl, originalFile, callback) {
        const img = new Image();
        img.onload = function() {
            try {
                const srcW = img.naturalWidth || img.width;
                const srcH = img.naturalHeight || img.height;

                if (srcW < 50 || srcH < 50) {
                    throw new Error('Image dimensions too small.');
                }

                const minSide = Math.min(srcW, srcH);
                const cropX = (srcW - minSide) / 2;
                const cropY = (srcH - minSide) / 2;

                const maxDim = 800; // Crisp high-DPI square
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
        showSuccessState(data.message || 'Profile photo updated successfully!');

        const freshUrl = data.versioned_url || data.image_url;
        ppmApplyNewAvatar(freshUrl, true);
    }

    // Upload Error State
    function handleUploadError(errMsg) {
        // Rollback preview
        if (state.originalSrc) {
            document.querySelectorAll('.ppm-avatar-img, #' + targetAvatarId).forEach(img => {
                img.src = state.originalSrc;
            });
        }
        setLoadingState(false);
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
            if (isLoading) {
                badge.setAttribute('disabled', 'true');
                badge.style.opacity = '0.5';
                badge.style.pointerEvents = 'none';
            } else {
                badge.removeAttribute('disabled');
                badge.style.opacity = '';
                badge.style.pointerEvents = '';
            }
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

    // Modal Helpers (Bootstrap safe with fallback)
    function getBsModal(id) {
        const el = document.getElementById(id);
        if (!el) return null;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
        }
        return null;
    }

    // Remove Profile Picture Flow
    window.ppmPromptRemove = function() {
        const m = getBsModal('ppmRemoveConfirmModal');
        if (m) {
            m.show();
        } else {
            if (confirm('Remove profile photo and restore default initials avatar?')) {
                ppmExecuteRemove();
            }
        }
    };

    window.ppmCloseRemoveModal = function() {
        const m = getBsModal('ppmRemoveConfirmModal');
        if (m) m.hide();
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
            '#profilePreview'
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
                actionWrap.style.display = '';
            } else {
                actionWrap.style.setProperty('display', 'none', 'important');
            }
        }
        if (removeBtn) {
            if (hasCustom) {
                removeBtn.removeAttribute('style');
                removeBtn.style.display = '';
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

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initKeyboardSupport();
            initDragAndDrop();
        });
    } else {
        initKeyboardSupport();
        initDragAndDrop();
    }
})();
</script>
