let bioAbortController = null;
let passkeySupportCheck = 0;

async function refreshDevicePasskeySupport() {
    const check = ++passkeySupportCheck;
    const result = await (window.DevicePasskeys?.check('create') || { available: false, reason: 'unsupported' });
    const available = result.available === true;
    if (check === passkeySupportCheck) {
        const button = document.getElementById('startBioBtn');
        const status = document.getElementById('devicePasskeyStatus');
        button.disabled = !available;
        status.hidden = available;
        status.textContent = available
            ? ''
            : (window.DevicePasskeys?.message(result, 'create') || 'Device passkeys are unavailable in this browser.');
    }
    return available;
}

function initializeDevicePasskeyRegistration() {
    const start = document.getElementById('startBioBtn');
    if (!start) return;

    start.addEventListener('click', beginDevicePasskeyRegistration);
    document.getElementById('retryBtn')?.addEventListener('click', beginDevicePasskeyRegistration);
    document.getElementById('cancelBioBtn')?.addEventListener('click', cancelBiometricRegistration);
    document.getElementById('doneBioBtn')?.addEventListener('click', cancelBiometricRegistration);
    window.addEventListener('pagehide', cancelBiometricRegistration);
    window.addEventListener('focus', () => {
        if (!bioAbortController) refreshDevicePasskeySupport();
    });
    refreshDevicePasskeySupport();
    loadDevices();
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

function showRegistrationView(id) {
    ['bioIdleView', 'bioScanningView', 'bioSuccessView', 'bioErrorView'].forEach(view => {
        const element = document.getElementById(view);
        if (element) element.style.display = view === id ? 'block' : 'none';
    });
}

function cancelBiometricRegistration() {
    bioAbortController?.abort();
    bioAbortController = null;
    document.getElementById('devicePasskeyFrame')?.classList.remove('bio-detected');
    showRegistrationView('bioIdleView');
    setBioRegistrationStage(1);
    if (document.visibilityState !== 'hidden') {
        document.getElementById('startBioBtn')?.focus({ preventScroll: true });
    }
}

async function loadDevices() {
    const list = document.getElementById('deviceList');
    const badge = document.getElementById('deviceCountBadge');
    if (!list) return;
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

        if (devices.length > 0) {
            if (badge) {
                badge.textContent = `${devices.length} Registered`;
                badge.style.color = '#4ade80';
                badge.style.borderColor = 'rgba(74,222,128,0.3)';
                badge.style.background = 'rgba(74,222,128,0.1)';
            }

            const activeSummary = document.createElement('div');
            const hasUsableCredential = devices.some(device => device.biometric_type !== 'face' || device.is_secure_face_credential);
            activeSummary.style.cssText = hasUsableCredential
                ? 'padding:14px 18px;color:#4ade80;font-size:.875rem;background:rgba(22,163,74,0.12);border-radius:12px;border:1px solid rgba(22,163,74,0.25);margin-bottom:16px;font-weight:600;display:flex;align-items:center;gap:10px;'
                : 'padding:14px 18px;color:#fbbf24;font-size:.875rem;background:rgba(245,158,11,0.12);border-radius:12px;border:1px solid rgba(245,158,11,0.25);margin-bottom:16px;font-weight:600;display:flex;align-items:center;gap:10px;';
            activeSummary.innerHTML = `
                <i class="bi ${hasUsableCredential ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}" style="font-size:1.2rem;color:${hasUsableCredential ? '#22c55e' : '#f59e0b'};"></i>
                <div>
                    <div>${hasUsableCredential ? 'Device sign-in is <strong>active</strong> on your account.' : 'Register a device passkey to enable sign-in.'}</div>
                </div>`;
            list.appendChild(activeSummary);

            devices.forEach(d => {
                const isLegacyFace = d.biometric_type === 'face' && !d.is_secure_face_credential;
                const div = document.createElement('div');
                div.className = 'device-item-card';
                div.innerHTML = `
                    <div class="device-item-left">
                        <div class="device-item-icon">
                            <i class="bi bi-shield-lock" aria-hidden="true"></i>
                        </div>
                        <div class="device-item-info">
                            <div class="device-item-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span data-device-name></span>
                            </div>
                            <div class="device-item-meta">
                                <span class="device-meta-verified"><i class="bi ${isLegacyFace ? 'bi-exclamation-triangle' : 'bi-shield-check'} me-1"></i>${isLegacyFace ? 'Camera-only legacy profile · re-enroll' : 'Device-protected credential'}</span>
                                <span class="device-meta-dot">•</span>
                                <span class="device-meta-date">${d.created_at ? new Date(d.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Registered'}</span>
                            </div>
                        </div>
                    </div>
                    <button class="device-remove-btn" type="button" title="Remove sign-in method">
                        <i class="bi bi-trash3 me-1"></i>Remove
                    </button>`;
                div.querySelector('[data-device-name]').textContent = d.name || d.device_name || 'Device passkey';
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
                <div style="font-weight:700;color:#f3e7cd;margin-bottom:4px;">No passkeys registered on this account yet</div>
                <div style="font-size:.78rem;color:#b39b82;">Use the registration button above to add a passkey for this device to your account.</div>
            `;
            list.appendChild(emptyDiv);
        }
    } catch(e) {
        console.error('Failed to load biometric devices', e);
        list.innerHTML = '';
        list.removeAttribute('aria-busy');
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
    if (!confirm('Remove this sign-in method from your account?')) return;
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
            showToast(data.message || 'Device passkey removed.', 'info');
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

async function beginDevicePasskeyRegistration() {
    if (bioAbortController) return;
    const controller = new AbortController();
    bioAbortController = controller;

    const statusTitle = document.getElementById('fpScanningTitle');
    const statusText = document.getElementById('fpStatusSub');
    const frame = document.getElementById('devicePasskeyFrame');

    function showError(title, message) {
        frame?.classList.remove('bio-detected');
        const errorTitle = document.getElementById('bioErrorTitle');
        errorTitle.textContent = title;
        document.getElementById('bioErrorDesc').textContent = message;
        showRegistrationView('bioErrorView');
        focusBiometricHeading(errorTitle);
    }

    try {
        const available = await refreshDevicePasskeySupport();
        if (controller.signal.aborted) return;
        if (!available) {
            showRegistrationView('bioIdleView');
            setBioRegistrationStage(1);
            return;
        }

        statusTitle.textContent = 'Preparing device prompt';
        statusText.textContent = 'Setting up the passkey request.';
        showRegistrationView('bioScanningView');
        setBioRegistrationStage(2);
        focusBiometricHeading(statusTitle);

        const optRes = await fetch('{{ route("webauthn.register.options") }}', {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            },
            signal: controller.signal
        });
        const options = await optRes.json();
        if (controller.signal.aborted) return;
        if (!optRes.ok || !options.challenge || !options.user) {
            throw new Error(options.message || 'Could not start passkey registration. Try again.');
        }

        const hostname = window.location.hostname.toLowerCase().trim();
        const rp = { name: options.rp?.name || 'School Attendance' };
        const serverRp = (options.rp?.id || '').toLowerCase().trim();
        if (!/^(\d{1,3}\.){3}\d{1,3}$/.test(hostname) && !hostname.includes(':')) {
            rp.id = serverRp && (hostname === serverRp || hostname.endsWith('.' + serverRp)) ? serverRp : hostname;
        }

        const publicKey = {
            challenge: base64ToUint8Array(options.challenge),
            rp,
            user: {
                id: base64ToUint8Array(options.user.id),
                name: options.user.name,
                displayName: options.user.displayName
            },
            pubKeyCredParams: options.pubKeyCredParams || [
                { type: 'public-key', alg: -7 },
                { type: 'public-key', alg: -257 }
            ],
            timeout: options.timeout || 120000,
            attestation: options.attestation || 'none',
            excludeCredentials: (options.excludeCredentials || []).map(credential => ({
                type: credential.type || 'public-key',
                id: base64ToUint8Array(credential.id)
            })),
            hints: ['client-device'],
            authenticatorSelection: {
                authenticatorAttachment: 'platform',
                userVerification: 'required',
                residentKey: 'preferred',
                requireResidentKey: false
            }
        };

        statusTitle.textContent = 'Confirm on your device';
        statusText.textContent = 'Follow your device’s biometric or screen lock prompt.';
        const strategies = [
            { authenticatorAttachment: 'platform', userVerification: 'required', residentKey: 'required', requireResidentKey: true },
            { authenticatorAttachment: 'platform', userVerification: 'required', residentKey: 'preferred', requireResidentKey: false },
            { authenticatorAttachment: 'platform', userVerification: 'required', residentKey: 'discouraged', requireResidentKey: false }
        ];
        let credential = null;
        let lastCreateError = null;
        for (const selection of strategies) {
            if (controller.signal.aborted) return;
            try {
                credential = await navigator.credentials.create({
                    publicKey: { ...publicKey, authenticatorSelection: selection },
                    signal: controller.signal
                });
                break;
            } catch (error) {
                lastCreateError = error;
                if (controller.signal.aborted
                    || error.name === 'AbortError'
                    || error.name === 'NotAllowedError'
                    || error.name === 'InvalidStateError'
                    || error.name === 'SecurityError') {
                    throw error;
                }
            }
        }
        if (controller.signal.aborted) return;
        if (!credential) throw (lastCreateError || new Error('The passkey request ended before registration finished.'));

        setBioRegistrationStage(3);
        frame?.classList.add('bio-detected');
        statusTitle.textContent = 'Saving your passkey';
        statusText.textContent = 'Linking the passkey to your account.';

        const credentialId = bufferToBase64Url(credential.rawId);
        const transports = (credential.response && typeof credential.response.getTransports === 'function')
            ? credential.response.getTransports()
            : ['internal'];
        const normalizedTransports = Array.isArray(transports) && transports.length ? transports : ['internal'];

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
                transports: normalizedTransports,
                credential: {
                    id: credential.id,
                    type: credential.type,
                    transports: normalizedTransports,
                    response: {
                        attestationObject: bufferToBase64Url(credential.response.attestationObject),
                        clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON)
                    }
                }
            })
        });

        const result = await saveRes.json();
        if (controller.signal.aborted) return;
        if (saveRes.status === 409) {
            showError('Passkey already registered', 'This passkey is already registered.');
            return;
        }
        if (!saveRes.ok || !result.success) {
            throw new Error(result.message || 'Could not save the passkey. Try again.');
        }

        setBioRegistrationStage(4);
        showRegistrationView('bioSuccessView');
        focusBiometricHeading(document.getElementById('bioSuccessTitle'));
        window.triggerHaptic?.('success');
        await loadDevices();
    } catch (error) {
        if (controller.signal.aborted) return;
        if (error.name === 'AbortError') {
            cancelBiometricRegistration();
        } else if (error.name === 'NotAllowedError') {
            showError('Device prompt ended', 'The device prompt was closed or expired before registration finished. Try again when you are ready.');
        } else if (error.name === 'InvalidStateError') {
            showError('Passkey already registered', 'This passkey is already registered.');
        } else if (error.name === 'NotSupportedError' || error.name === 'NotReadableError') {
            showError('Device verification unavailable', 'Check the sign-in settings on this device and try again.');
        } else {
            showError('Registration could not finish', error.message || 'Try registering the device again.');
        }
    } finally {
        if (bioAbortController === controller) bioAbortController = null;
    }
}
