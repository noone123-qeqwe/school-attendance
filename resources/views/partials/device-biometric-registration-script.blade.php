// ── WebAuthn Device Biometrics (Fingerprint & Face ID / Windows Hello) ──
let selectedBioMethod = 'fingerprint'; // 'fingerprint' | 'face'
let bioAbortController = null;
let bioScanProgressTimer = null;
let bioRegistrationRun = null;

function selectBiometricMethod(method) {
    if (method !== 'fingerprint' && method !== 'face') return;
    selectedBioMethod = method;

    // 1. Toggle option card active styling & radio state
    const cardFp = document.getElementById('methodCardFp') || document.getElementById('methodCardFingerprint');
    const cardFace = document.getElementById('methodCardFace');
    if (cardFp && cardFace) {
        if (method === 'fingerprint') {
            cardFp.classList.add('selected');
            cardFp.setAttribute('aria-pressed', 'true');
            cardFace.classList.remove('selected');
            cardFace.setAttribute('aria-pressed', 'false');
        } else {
            cardFace.classList.add('selected');
            cardFace.setAttribute('aria-pressed', 'true');
            cardFp.classList.remove('selected');
            cardFp.setAttribute('aria-pressed', 'false');
        }
    }

    // 2. Smoothly switch idle preview graphic
    const fpPrev = document.getElementById('fpIdlePreview');
    const facePrev = document.getElementById('faceIdlePreview');
    if (fpPrev && facePrev) {
        if (method === 'fingerprint') {
            fpPrev.classList.add('active');
            facePrev.classList.remove('active');
        } else {
            facePrev.classList.add('active');
            fpPrev.classList.remove('active');
        }
    }

    // 3. Switch active scanner views
    const fpScan = document.getElementById('fpActiveScanner');
    const faceScan = document.getElementById('faceActiveScanner');
    if (fpScan && faceScan) {
        if (method === 'fingerprint') {
            fpScan.classList.add('active');
            faceScan.classList.remove('active');
        } else {
            faceScan.classList.add('active');
            fpScan.classList.remove('active');
        }
    }

    // 4. Update Step 2 title & CTA button text
    const displaySpan = document.getElementById('selectedMethodDisplay');
    const methodHelper = document.getElementById('selectedMethodHelper');
    const stepTitle = document.getElementById('bioStep2Title');
    const startBtn = document.getElementById('startBioBtn');
    if (displaySpan) {
        if (method === 'fingerprint') {
            displaySpan.textContent = 'Fingerprint';
            displaySpan.style.color = '#4ade80';
        } else {
            displaySpan.textContent = 'Face ID / Windows Hello';
            displaySpan.style.color = '#38bdf8';
        }
    }
    if (methodHelper) {
        methodHelper.textContent = method === 'face'
            ? '— Confirm with your device. A PIN may be offered.'
            : '— Follow the device prompt; a screen lock may be offered.';
    }
    if (stepTitle) stepTitle.textContent = method === 'face' ? 'Confirm face unlock on this device' : 'Confirm fingerprint on this device';
    if (startBtn) {
        if (method === 'fingerprint') {
            startBtn.innerHTML = '<i class="bi bi-fingerprint me-2"></i>Continue to Register Fingerprint';
            startBtn.className = 'sbtn btn-emerald bio-primary-cta';
            startBtn.style.background = '';
            startBtn.style.color = '';
        } else {
            startBtn.innerHTML = '<i class="bi bi-person-bounding-box me-2"></i>Continue with Face Sign-In';
            startBtn.className = 'sbtn bio-primary-cta';
            startBtn.style.background = 'linear-gradient(135deg, #0284c7, #0ea5e9)';
            startBtn.style.color = '#ffffff';
        }
    }

    // 5. Reset scanner stage back to idle view if error or success was shown
    const idleView = document.getElementById('bioIdleView');
    const scanningView = document.getElementById('bioScanningView');
    const successView = document.getElementById('bioSuccessView');
    const errorView = document.getElementById('bioErrorView');
    if (idleView && (successView?.style.display !== 'none' || errorView?.style.display !== 'none')) {
        idleView.style.display = 'block';
        if (scanningView) scanningView.style.display = 'none';
        if (successView) successView.style.display = 'none';
        if (errorView) errorView.style.display = 'none';
    }

    if (window.triggerHaptic) window.triggerHaptic('light');
}

function updateProgressiveFeedback(pct, stateText) {
    if (selectedBioMethod === 'fingerprint') {
        const fill = document.getElementById('fpProgressFill');
        const pctEl = document.getElementById('fpPctLabel');
        const stateEl = document.getElementById('fpStateLabel');
        if (fill) fill.style.width = pct + '%';
        if (pctEl) pctEl.textContent = pct + '%';
        if (stateEl && stateText) stateEl.textContent = stateText;
    } else {
        const fill = document.getElementById('faceProgressFill');
        const pctEl = document.getElementById('facePctLabel');
        const stateEl = document.getElementById('faceStateLabel');
        if (fill) fill.style.width = pct + '%';
        if (pctEl) pctEl.textContent = pct + '%';
        if (stateEl && stateText) stateEl.textContent = stateText;
    }
}

function setBioRegistrationStage(stage) {
    document.querySelectorAll('[data-bio-step]').forEach(step => {
        const stepNumber = Number(step.dataset.bioStep);
        if (stepNumber < stage) step.dataset.complete = 'true';
        else delete step.dataset.complete;
        if (stepNumber === stage) step.setAttribute('aria-current', 'step');
        else step.removeAttribute('aria-current');
    });
}

function focusBiometricHeading(element) {
    if (!element) return;
    element.setAttribute('tabindex', '-1');
    element.focus({ preventScroll: true });
}

function startScanProgressAnimation() {
    clearInterval(bioScanProgressTimer);
    bioScanProgressTimer = null;
    if (selectedBioMethod === 'fingerprint') {
        updateProgressiveFeedback(25, 'Waiting for secure device prompt…');
        const pctEl = document.getElementById('fpPctLabel');
        if (pctEl) pctEl.textContent = 'Waiting';
        const titleEl = document.getElementById('fpScanningTitle');
        const subEl = document.getElementById('fpStatusSub');
        if (titleEl) titleEl.textContent = 'Confirm on your device';
        if (subEl) subEl.textContent = 'Follow the secure prompt. Your device may ask for a fingerprint or screen lock.';
    } else {
        updateProgressiveFeedback(25, 'Waiting for Face ID or Windows Hello…');
        const pctEl = document.getElementById('facePctLabel');
        const titleEl = document.getElementById('faceScanningTitle');
        const subEl = document.getElementById('faceStatusSub');
        if (pctEl) pctEl.textContent = 'Waiting';
        if (titleEl) titleEl.textContent = 'Confirm face unlock on your device';
        if (subEl) subEl.textContent = 'Complete the secure device prompt. A PIN may be offered.';
    }
}

function triggerBiometricDetected() {
    clearInterval(bioScanProgressTimer);
    bioScanProgressTimer = null;
    updateProgressiveFeedback(100, 'Device sign-in verified! Finalizing…');

    const frame = selectedBioMethod === 'fingerprint'
        ? document.querySelector('.fp-scan-frame')
        : document.getElementById('faceScanFrame');
    if (frame) frame.classList.add('bio-detected');

    if (window.triggerHaptic) window.triggerHaptic('success');
}

function cancelBiometricRegistration() {
    bioRegistrationRun = null;

    if (bioAbortController) {
        try { bioAbortController.abort(); } catch(e){}
        bioAbortController = null;
    }

    clearInterval(bioScanProgressTimer);
    bioScanProgressTimer = null;
    isBioRegistrationRunning = false;

    const idleView = document.getElementById('bioIdleView');
    const scanningView = document.getElementById('bioScanningView');
    const successView = document.getElementById('bioSuccessView');
    const errorView = document.getElementById('bioErrorView');
    if (idleView) idleView.style.display = 'block';
    if (scanningView) scanningView.style.display = 'none';
    if (successView) successView.style.display = 'none';
    if (errorView) errorView.style.display = 'none';
    setBioRegistrationStage(1);
    if (document.visibilityState !== 'hidden') {
        focusBiometricHeading(document.getElementById('startBioBtn'));
    }

    // Reset styles
    const faceScanFrame = document.getElementById('faceScanFrame');
    if (faceScanFrame) {
        faceScanFrame.classList.remove('bio-detected');
        faceScanFrame.style.borderColor = '';
    }
    const faceLaserBar = document.getElementById('faceLaserBar');
    if (faceLaserBar) faceLaserBar.style.background = '';
    document.querySelectorAll('.fp-scan-frame, .face-scan-frame').forEach(el => el.classList.remove('bio-detected'));
}

function resetToSelectionStage() {
    cancelBiometricRegistration();
}

function switchOrRegisterOtherMethod() {
    const other = selectedBioMethod === 'fingerprint' ? 'face' : 'fingerprint';
    selectBiometricMethod(other);
    resetToSelectionStage();
}

function retryBiometricRegistration() {
    const errorView = document.getElementById('bioErrorView');
    if (errorView) errorView.style.display = 'none';
    beginSelectedBiometricRegistration();
}

function switchBiometricMethodFallback() {
    const other = selectedBioMethod === 'fingerprint' ? 'face' : 'fingerprint';
    selectBiometricMethod(other);
    resetToSelectionStage();
}

async function loadDevices() {
    const list = document.getElementById('deviceList');
    const badge = document.getElementById('deviceCountBadge');
    const badgeFp = document.getElementById('statusBadgeFingerprint');
    const badgeFace = document.getElementById('statusBadgeFace');
    if (!list) return;

    const webAuthnAvailable = Boolean(window.isSecureContext && window.PublicKeyCredential);
    if (!webAuthnAvailable) {
        if (badge) { badge.textContent = !window.isSecureContext ? 'Requires HTTPS' : 'Unsupported'; badge.style.color = '#f87171'; }
        if (badgeFp) { badgeFp.textContent = 'Unavailable'; badgeFp.className = 'bio-status-pill not-reg'; }
        if (badgeFace) { badgeFace.textContent = 'Unavailable'; badgeFace.className = 'bio-status-pill not-reg'; }
    } else if (badge) {
        badge.textContent = 'Checking registered devices…';
        badge.style.color = '#b39b82';
    }

    list.setAttribute('aria-busy', 'true');
    list.innerHTML = '';
    const loadingState = document.createElement('div');
    loadingState.className = 'device-list-loading';
    loadingState.setAttribute('role', 'status');
    loadingState.style.cssText = 'display:flex;align-items:center;justify-content:center;gap:10px;min-height:72px;padding:16px;border:1px solid rgba(207,164,111,.18);border-radius:12px;background:rgba(207,164,111,.04);color:#b39b82;font-size:.86rem;';
    const loadingIcon = document.createElement('span');
    loadingIcon.className = 'biometric-spinner';
    loadingIcon.style.cssText = 'flex:none;width:16px;height:16px;border:2px solid rgba(207,164,111,.3);border-top-color:#cfa46f;border-radius:50%;';
    loadingIcon.setAttribute('aria-hidden', 'true');
    const loadingText = document.createElement('span');
    loadingText.textContent = 'Checking your registered devices…';
    loadingState.appendChild(loadingIcon);
    loadingState.appendChild(loadingText);
    list.appendChild(loadingState);

    try {
        const res = await fetch('{{ route("webauthn.devices") }}', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('Device list request failed.');
        const devices = await res.json();
        if (!Array.isArray(devices)) throw new Error('Device list response was invalid.');

        list.innerHTML = '';
        list.removeAttribute('aria-busy');

        const hasFp = devices.some(d => (d.biometric_type || 'fingerprint') === 'fingerprint');
        const hasFace = devices.some(d => d.biometric_type === 'face' && d.is_secure_face_credential);
        const hasLegacyFace = devices.some(d => d.biometric_type === 'face' && !d.is_secure_face_credential);

        if (badgeFp && webAuthnAvailable) {
            badgeFp.textContent = hasFp ? 'Active' : 'Not Registered';
            badgeFp.className = 'bio-status-pill ' + (hasFp ? 'reg-active' : 'not-reg');
        }
        if (badgeFace && webAuthnAvailable) {
            badgeFace.textContent = hasFace ? 'Active' : (hasLegacyFace ? 'Re-enroll' : 'Not Registered');
            badgeFace.className = 'bio-status-pill ' + (hasFace ? 'reg-active' : 'not-reg');
        }

        if (devices && devices.length > 0) {
            if (badge) {
                badge.textContent = `${devices.length} Registered`;
                badge.style.color = '#4ade80';
                badge.style.borderColor = 'rgba(74,222,128,0.3)';
                badge.style.background = 'rgba(74,222,128,0.1)';
            }

            const activeSummary = document.createElement('div');
            const hasUsableCredential = hasFp || hasFace;
            const enrolledMethods = [
                ...(hasFp ? ['Fingerprint'] : []),
                ...(hasFace ? ['Face ID / Windows Hello'] : [])
            ];
            activeSummary.style.cssText = hasUsableCredential
                ? 'padding:14px 18px;color:#4ade80;font-size:.875rem;background:rgba(22,163,74,0.12);border-radius:12px;border:1px solid rgba(22,163,74,0.25);margin-bottom:16px;font-weight:600;display:flex;align-items:center;gap:10px;'
                : 'padding:14px 18px;color:#fbbf24;font-size:.875rem;background:rgba(245,158,11,0.12);border-radius:12px;border:1px solid rgba(245,158,11,0.25);margin-bottom:16px;font-weight:600;display:flex;align-items:center;gap:10px;';
            activeSummary.innerHTML = `
                <i class="bi ${hasUsableCredential ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}" style="font-size:1.2rem;color:${hasUsableCredential ? '#22c55e' : '#f59e0b'};"></i>
                <div>
                    <div>${hasUsableCredential ? 'Device sign-in is <strong>active</strong> on your account.' : 'A camera-only face profile <strong>cannot sign in</strong>. Register a device passkey below.'}</div>
                    <div style="font-size:.76rem;color:${hasUsableCredential ? '#86efac' : '#fcd34d'};font-weight:400;margin-top:2px;">
                        ${hasUsableCredential ? `Enrolled: ${enrolledMethods.join(' and ')}` : 'Remove the old profile after registering a passkey.'}
                    </div>
                </div>`;
            list.appendChild(activeSummary);

            devices.forEach(d => {
                const isFace = d.biometric_type === 'face';
                const isLegacyFace = isFace && !d.is_secure_face_credential;
                const div = document.createElement('div');
                div.className = 'device-item-card';
                div.innerHTML = `
                    <div class="device-item-left">
                        <div class="device-item-icon" style="background:${isFace ? 'rgba(56,189,248,0.12)' : 'rgba(74,222,128,0.12)'};color:${isFace ? '#38bdf8' : '#4ade80'};">
                            <i class="bi ${isFace ? 'bi-person-bounding-box' : 'bi-fingerprint'}"></i>
                        </div>
                        <div class="device-item-info">
                            <div class="device-item-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span data-device-name></span>
                                <span class="bio-device-type-pill ${isFace ? 'pill-face' : 'pill-fp'}">
                                    <i class="bi ${isFace ? 'bi-person-bounding-box' : 'bi-fingerprint'} me-1"></i>${isFace ? 'Face' : 'Fingerprint'}
                                </span>
                            </div>
                            <div class="device-item-meta">
                                <span class="device-meta-verified"><i class="bi ${isLegacyFace ? 'bi-exclamation-triangle' : 'bi-shield-check'} me-1"></i>${isLegacyFace ? 'Camera-only legacy profile · re-enroll' : 'Device-protected credential'}</span>
                                <span class="device-meta-dot">•</span>
                                <span class="device-meta-date">${d.created_at ? new Date(d.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Registered'}</span>
                            </div>
                        </div>
                    </div>
                    <button class="device-remove-btn" type="button" title="Remove biometric credential">
                        <i class="bi bi-trash3 me-1"></i>Remove
                    </button>`;
                div.querySelector('[data-device-name]').textContent = d.name || d.device_name || (isFace ? 'Face Recognition Credential' : 'Fingerprint Credential');
                const removeButton = div.querySelector('.device-remove-btn');
                removeButton.setAttribute('aria-label', 'Remove ' + (d.name || d.device_name || 'registered device'));
                removeButton.addEventListener('click', () => removeDevice(d.credential_id, removeButton));
                list.appendChild(div);
            });
        } else {
            if (badge) {
                badge.textContent = '0 Registered';
                badge.style.color = '#b39b82';
                badge.style.borderColor = 'rgba(207,164,111,0.2)';
                badge.style.background = 'rgba(207,164,111,0.12)';
            }
            const emptyDiv = document.createElement('div');
            emptyDiv.id = 'noDevices';
            emptyDiv.style.cssText = 'text-align:center;padding:32px 20px;color:#b39b82;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:14px;border:1px dashed rgba(207,164,111,0.2);';
            emptyDiv.innerHTML = `
                <i class="bi bi-shield-lock" style="font-size:2.6rem;display:block;margin-bottom:10px;opacity:.35;color:var(--gold,#CFA46F);"></i>
                <div style="font-weight:700;color:#f3e7cd;margin-bottom:4px;">No biometric credentials registered yet</div>
                <div style="font-size:.78rem;color:#b39b82;">Use the registration button above to set up this device.</div>
            `;
            list.appendChild(emptyDiv);
        }
    } catch(e) {
        console.error('Failed to load biometric devices', e);
        list.innerHTML = '';
        list.removeAttribute('aria-busy');
        if (webAuthnAvailable) {
            if (badgeFp) {
                badgeFp.textContent = 'Unable to check';
                badgeFp.className = 'bio-status-pill failed';
            }
            if (badgeFace) {
                badgeFace.textContent = 'Unable to check';
                badgeFace.className = 'bio-status-pill failed';
            }
        }
        if (badge) {
            badge.textContent = 'Could not load devices';
            badge.style.color = '#fca5a5';
            badge.style.borderColor = 'rgba(248,113,113,.3)';
            badge.style.background = 'rgba(248,113,113,.08)';
        }
        const errorState = document.createElement('div');
        errorState.className = 'device-list-error';
        errorState.setAttribute('role', 'alert');
        errorState.style.cssText = 'display:flex;flex-direction:column;align-items:center;gap:8px;padding:18px 14px;text-align:center;border:1px solid rgba(248,113,113,.25);border-radius:12px;background:rgba(248,113,113,.06);color:#f3e7cd;';
        const errorText = document.createElement('span');
        errorText.textContent = 'We couldn’t load your registered devices. Check your connection and try again.';
        const retryButton = document.createElement('button');
        retryButton.type = 'button';
        retryButton.className = 'device-load-retry';
        retryButton.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:44px;padding:8px 14px;border:1px solid rgba(207,164,111,.38);border-radius:9px;background:rgba(207,164,111,.12);color:#f3e7cd;font:inherit;font-weight:650;cursor:pointer;';
        retryButton.innerHTML = '<i class="bi bi-arrow-repeat" aria-hidden="true"></i> Try again';
        retryButton.addEventListener('click', function() {
            retryButton.disabled = true;
            retryButton.textContent = 'Checking again…';
            if (badge) {
                badge.setAttribute('tabindex', '-1');
                badge.focus({ preventScroll: true });
            }
            return loadDevices();
        });
        errorState.appendChild(errorText);
        errorState.appendChild(retryButton);
        list.appendChild(errorState);
    }
}

async function removeDevice(credentialId, btn) {
    if (!confirm('Remove this biometric credential from your account?')) return;
    try {
        if (btn) {
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.textContent = 'Removing…';
        }
        const res = await fetch('{{ route("webauthn.remove") }}', {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ credential_id: credentialId })
        });
        const data = await res.json();
        if (!res.ok || data.success === false) {
            throw new Error(data.message || 'Could not remove this sign-in method. Please try again.');
        }
        await loadDevices();
        if (typeof showToast === 'function') {
            showToast(data.message || 'Biometric credential removed.', 'info');
        }
    } catch(err) {
        console.error('Failed to remove biometric device', err);
        if (btn) {
            btn.disabled = false;
            btn.removeAttribute('aria-busy');
            btn.innerHTML = '<i class="bi bi-trash3 me-1"></i>Remove';
        }
        const badge = document.getElementById('deviceCountBadge');
        if (typeof showToast === 'function') showToast(err.message || 'Could not remove this sign-in method. Please try again.', 'error');
        else if (badge) badge.textContent = 'Could not remove device. Try again.';
    }
}

function normalizeBase64(base64) {
    base64 = (base64 || '').replace(/-/g, '+').replace(/_/g, '/');
    var padding = base64.length % 4;
    if (padding) base64 += '===='.slice(padding);
    return base64;
}

function base64ToUint8Array(base64) {
    base64 = normalizeBase64(base64);
    var binary = atob(base64);
    var bytes = new Uint8Array(binary.length);
    for (var i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
}

function bufferToBase64Url(buffer) {
    var bytes = new Uint8Array(buffer);
    var binary = '';
    for (var i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

let prefetchOptions = null;
let isFetchingOptions = false;

async function prefetchWebAuthn() {
    if (isFetchingOptions || prefetchOptions) return;
    isFetchingOptions = true;
    try {
        const optRes = await fetch('{{ route("webauthn.register.options") }}', {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            }
        });
        prefetchOptions = await optRes.json();
    } catch(e) {
        console.error(e);
    }
    isFetchingOptions = false;
}

// ── Device Passkey Registration ──
let isBioRegistrationRunning = false;

async function beginFaceRegistration() {
    // Face verification stays on the device; the server stores only a verified passkey public key.
    selectedBioMethod = 'face';
    return beginFingerprintRegistration();
}
// ── WebAuthn Device Credential Registration ──
async function beginFingerprintRegistration() {
    const registrationType = selectedBioMethod === 'face' ? 'face' : 'fingerprint';
    const idleView = document.getElementById('bioIdleView');
    const scanningView = document.getElementById('bioScanningView');
    const successView = document.getElementById('bioSuccessView');
    const errorView = document.getElementById('bioErrorView');
    const errorTitle = document.getElementById('bioErrorTitle');
    const errorDesc = document.getElementById('bioErrorDesc');
    const successTitle = document.getElementById('bioSuccessTitle');
    const successDesc = document.getElementById('bioSuccessDesc');

    // 1. WebAuthn requires a named domain like localhost or HTTPS (not raw 127.0.0.1 IP)
    if (window.location.hostname === '127.0.0.1') {
        const targetUrl = window.location.href.replace('//127.0.0.1', '//localhost');
        window.location.replace(targetUrl);
        return;
    }

    if (!window.isSecureContext) {
        if (idleView) idleView.style.display = 'none';
        if (errorView) {
            errorView.style.display = 'block';
            if (errorTitle) errorTitle.textContent = 'HTTPS Connection Required';
            if (errorDesc) errorDesc.textContent = 'Device biometric sign-in requires HTTPS or localhost. On mobile Wi-Fi, open the app through its secure HTTPS address.';
        }
        focusBiometricHeading(errorTitle);
        return;
    }

    if (!window.PublicKeyCredential) {
        if (idleView) idleView.style.display = 'none';
        if (errorView) {
            errorView.style.display = 'block';
            if (errorTitle) errorTitle.textContent = 'Browser Unsupported';
            if (errorDesc) errorDesc.innerHTML = 'Your current browser does not support WebAuthn biometric credentials. Please use <strong>Google Chrome</strong>, <strong>Microsoft Edge</strong>, or <strong>Apple Safari</strong>.';
        }
        focusBiometricHeading(errorTitle);
        return;
    }

    if (idleView) idleView.style.display = 'none';
    if (successView) successView.style.display = 'none';
    if (errorView) errorView.style.display = 'none';
    if (scanningView) scanningView.style.display = 'block';
    setBioRegistrationStage(2);

    document.querySelectorAll('.fp-scan-frame, .face-scan-frame').forEach(el => el.classList.remove('bio-detected'));

    const fpScan = document.getElementById('fpActiveScanner');
    const faceScan = document.getElementById('faceActiveScanner');
    if (faceScan) faceScan.classList.toggle('active', registrationType === 'face');
    if (fpScan) fpScan.classList.toggle('active', registrationType !== 'face');

    const fpScanFrame = document.querySelector(registrationType === 'face' ? '.face-scan-frame' : '.fp-scan-frame');
    if (fpScanFrame) {
        fpScanFrame.classList.remove('bio-detected');
    }
    const fpScanningTitle = document.getElementById(registrationType === 'face' ? 'faceScanningTitle' : 'fpScanningTitle');
    const fpStatusSub = document.getElementById(registrationType === 'face' ? 'faceStatusSub' : 'fpStatusSub');
    if (fpScanningTitle) fpScanningTitle.textContent = registrationType === 'face' ? 'Confirm face unlock on your device' : 'Confirm on your device';
    if (fpStatusSub) fpStatusSub.textContent = registrationType === 'face'
        ? 'Complete the secure prompt. A PIN may be offered by your device.'
        : 'Follow the secure prompt. Your device may ask for a fingerprint or screen lock.';
    updateProgressiveFeedback(25, registrationType === 'face' ? 'Waiting for device confirmation…' : 'Waiting for secure device prompt…');
    const fpPctLabel = document.getElementById(registrationType === 'face' ? 'facePctLabel' : 'fpPctLabel');
    if (fpPctLabel) fpPctLabel.textContent = 'Waiting';
    focusBiometricHeading(fpScanningTitle);

    const controller = new AbortController();
    bioAbortController = controller;

    try {
        const optRes = await fetch('{{ route("webauthn.register.options") }}?biometric_type=' + registrationType, {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            },
            signal: controller.signal
        });
        const opts = await optRes.json();
        if (controller.signal.aborted) return;

        if (!optRes.ok || !opts.challenge || !opts.user) {
            throw new Error(opts.message || 'Failed to initialize biometric registration options from server.');
        }

        const challenge = base64ToUint8Array(opts.challenge);
        const userId    = base64ToUint8Array(opts.user.id);

        const hostname = window.location.hostname;
        const isIp = /^(\d{1,3}\.){3}\d{1,3}$/.test(hostname) || hostname.includes(':');
        const rp = { name: opts.rp?.name || 'School Attendance' };
        if (!isIp) {
            const sRp = (opts.rp?.id || '').toLowerCase().trim();
            const h = hostname.toLowerCase().trim();
            rp.id = (sRp && (h === sRp || h.endsWith('.' + sRp))) ? sRp : hostname;
        }

        const excludeCredentials = (opts.excludeCredentials || []).map(function(c) {
            return { type: c.type || 'public-key', id: base64ToUint8Array(c.id) };
        });

        // Detect if platform authenticator (Windows Hello, Touch ID, Android fingerprint) is available
        let hasPlatformAuth = false;
        let platformAvailabilityKnown = false;
        if (typeof PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable === 'function') {
            try {
                hasPlatformAuth = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
                platformAvailabilityKnown = true;
            } catch(e) {
                hasPlatformAuth = false;
            }
        }
        if (controller.signal.aborted) return;
        if (platformAvailabilityKnown && !hasPlatformAuth) {
            throw new Error('No on-device biometric sign-in is available. Set up fingerprint, Face ID, or Windows Hello in your device settings, then try again.');
        }

        const basePublicKey = {
            challenge: challenge,
            rp: rp,
            user: { id: userId, name: opts.user.name, displayName: opts.user.displayName },
            pubKeyCredParams: opts.pubKeyCredParams || [
                { type: 'public-key', alg: -7 },
                { type: 'public-key', alg: -257 }
            ],
            timeout: opts.timeout || 120000,
            attestation: opts.attestation || 'none',
            excludeCredentials: excludeCredentials,
            hints: ['client-device']
        };

        // ── Direct Platform Biometric Registration ──────────────────────────
        // Enforce on-device platform biometric attachment so Android Chrome,
        // iOS Touch ID/Face ID, and Windows Hello invoke the native sensor directly
        // without prompting with the external security key chooser (NFC/USB).
        const primarySelection = {
            authenticatorAttachment: 'platform',
            userVerification: 'required',
            residentKey: 'preferred',
            requireResidentKey: false
        };

        let credential = null;
        try {
            credential = await navigator.credentials.create({
                publicKey: Object.assign({}, basePublicKey, {
                    authenticatorSelection: primarySelection
                }),
                signal: controller.signal
            });
        } catch (firstErr) {
            if (controller.signal.aborted) return;
            // If the first attempt was aborted/cancelled by the user, propagate immediately.
            if (firstErr.name === 'AbortError' || firstErr.name === 'NotAllowedError') {
                throw firstErr;
            }
            // Fallback attempt: still strictly platform-bound, but with residentKey: 'discouraged'
            console.warn('Primary platform registration failed, retrying with platform discouraged fallback:', firstErr);
            if (fpStatusSub) fpStatusSub.textContent = 'Retrying with device sensor...';
            try {
                credential = await navigator.credentials.create({
                    publicKey: Object.assign({}, basePublicKey, {
                        authenticatorSelection: {
                            authenticatorAttachment: 'platform',
                            userVerification: 'required',
                            residentKey: 'discouraged',
                            requireResidentKey: false
                        }
                    }),
                    signal: controller.signal
                });
            } catch (retryErr) {
                throw retryErr;
            }
        }

        if (controller.signal.aborted) return;
        if (!credential) {
            throw new Error('Biometric registration was cancelled.');
        }

        setBioRegistrationStage(3);

        if (fpScanFrame) {
            fpScanFrame.classList.add('bio-detected');
        }
        if (fpScanningTitle) fpScanningTitle.textContent = 'Device Verified ✓';
        if (fpStatusSub) fpStatusSub.textContent = 'Device verification complete. Registering credential...';
        triggerBiometricDetected();

        const credentialId = bufferToBase64Url(credential.rawId);
        const attestationObject = bufferToBase64Url(credential.response.attestationObject);
        const clientDataJSON = bufferToBase64Url(credential.response.clientDataJSON);

        const ua = navigator.userAgent;
        let deviceType = ua.indexOf('iPhone') !== -1 ? 'iPhone' :
                         ua.indexOf('iPad') !== -1 ? 'iPad' :
                         ua.indexOf('Android') !== -1 ? 'Android Device' :
                         ua.indexOf('Windows') !== -1 ? 'Windows PC' :
                         ua.indexOf('Mac') !== -1 ? 'Mac' : 'Biometric Device';
        const deviceName = `${deviceType} (${registrationType === 'face' ? 'Secure Face ID / Passkey' : 'Fingerprint'})`;

        const saveRes = await fetch('{{ route("webauthn.register") }}', {
            method: 'POST',
            signal: controller.signal,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            },
            body: JSON.stringify({
                credential_id: credentialId,
                credential: {
                    id: credential.id,
                    type: credential.type,
                    response: {
                        attestationObject: attestationObject,
                        clientDataJSON: clientDataJSON
                    }
                },
                biometric_type: registrationType,
                device_name: deviceName
            })
        });

        const result = await saveRes.json();
        if (controller.signal.aborted) return;

        if (saveRes.status === 409 || (result && result.error === 'duplicate')) {
            if (scanningView) scanningView.style.display = 'none';
            if (errorView) {
                errorView.style.display = 'block';
                if (errorTitle) errorTitle.textContent = 'Biometric Already Registered';
                if (errorDesc) errorDesc.textContent = result.message || 'This device credential is already registered on your account.';
            }
            focusBiometricHeading(errorTitle);
            return;
        }

        if (result.success) {
            setBioRegistrationStage(4);
            if (scanningView) scanningView.style.display = 'none';
            if (successView) {
                successView.style.display = 'block';
                if (successTitle) successTitle.textContent = registrationType === 'face' ? 'Face Sign-in Registered!' : 'Fingerprint Registered Successfully!';
                if (successDesc) successDesc.textContent = result.message || 'Your device credential is linked to your account.';
            }
            focusBiometricHeading(successTitle);
            await loadDevices();
        } else {
            if (scanningView) scanningView.style.display = 'none';
            if (errorView) {
                errorView.style.display = 'block';
                if (errorTitle) errorTitle.textContent = 'Registration Incomplete';
                if (errorDesc) errorDesc.textContent = result.message || 'The server could not verify and save the biometric enrollment.';
            }
            focusBiometricHeading(errorTitle);
        }
    } catch(err) {
        if (controller.signal.aborted) return;
        clearInterval(bioScanProgressTimer);
        bioScanProgressTimer = null;
        if (fpScanFrame) fpScanFrame.classList.remove('bio-detected');

        if (err.name === 'AbortError') {
            if (scanningView) scanningView.style.display = 'none';
            if (idleView) idleView.style.display = 'block';
            focusBiometricHeading(document.getElementById('startBioBtn'));
            return;
        }

        if (scanningView) scanningView.style.display = 'none';
        if (errorView) {
            errorView.style.display = 'block';
            if (err.name === 'NotAllowedError') {
                if (errorTitle) errorTitle.textContent = 'Biometric Prompt Dismissed';
                if (errorDesc) errorDesc.textContent = 'The device verification prompt was cancelled or timed out. Try again or use another sign-in method.';
            } else if (err.name === 'InvalidStateError') {
                if (errorTitle) errorTitle.textContent = 'Already Registered';
                if (errorDesc) errorDesc.textContent = 'This device credential is already registered for your account.';
            } else if (err.name === 'NotSupportedError') {
                if (errorTitle) errorTitle.textContent = 'Device Authentication Unsupported';
                if (errorDesc) errorDesc.textContent = 'Set up Face ID, Windows Hello, or another device sign-in method and try again.';
            } else {
                if (errorTitle) errorTitle.textContent = 'Registration Failed';
                if (errorDesc) errorDesc.textContent = err.message || 'Device verification could not be completed. Check your device sign-in settings and try again.';
            }
        }
        focusBiometricHeading(errorTitle);
    } finally {
        if (bioAbortController === controller) bioAbortController = null;
    }
}

// ── Unified Biometric Registration Dispatcher ──
async function beginSelectedBiometricRegistration() {
    if (isBioRegistrationRunning) return;
    isBioRegistrationRunning = true;
    const run = Symbol();
    bioRegistrationRun = run;

    // Determine the active method from both the JS state and the DOM aria-pressed
    // attribute, so we are never dependent on a single source of truth.
    // This prevents device verification from triggering when face is selected.
    let method = selectedBioMethod;
    const faceCard = document.getElementById('methodCardFace');
    const fpCard   = document.getElementById('methodCardFp') || document.getElementById('methodCardFingerprint');
    if (faceCard && faceCard.getAttribute('aria-pressed') === 'true') {
        method = 'face';
    } else if (fpCard && fpCard.getAttribute('aria-pressed') === 'true') {
        method = 'fingerprint';
    }
    // Also read from the visible button label as a final fallback
    const startBtn = document.getElementById('startBioBtn');
    if (startBtn && startBtn.textContent && startBtn.textContent.toLowerCase().includes('face')) {
        method = 'face';
    }

    // Sync the JS variable to what we determined from the DOM
    selectedBioMethod = method;

    try {
        if (method === 'face') {
            await beginFaceRegistration();
        } else {
            await beginFingerprintRegistration();
        }
    } finally {
        if (bioRegistrationRun === run) {
            bioRegistrationRun = null;
            isBioRegistrationRunning = false;
        }
    }
}

// Backward compatibility alias
window.registerFingerprint = beginSelectedBiometricRegistration;
