let bioAbortController = null;
let passkeySupportCheck = 0;
let lastCapabilityResult = null;

function isConsentSatisfied() {
    const consentBox = document.getElementById('bioConsentCheckbox');
    return !consentBox || consentBox.checked === true;
}

function syncRegistrationButtonState() {
    const button = document.getElementById('startBioBtn');
    const status = document.getElementById('devicePasskeyStatus');
    if (!button) return;

    const available = Boolean(lastCapabilityResult && lastCapabilityResult.available === true);
    const consentOk = isConsentSatisfied();
    button.disabled = !available || !consentOk || Boolean(bioAbortController);

    if (!status) return;
    if (!lastCapabilityResult) {
        status.hidden = false;
        status.textContent = 'Checking device compatibility…';
        return;
    }

    if (!available) {
        status.hidden = false;
        status.textContent = window.DevicePasskeys?.message(lastCapabilityResult, 'create')
            || 'Device passkey registration is unavailable in this browser.';
        if (lastCapabilityResult.reason === 'update-app') {
            const update = document.createElement('a');
            update.href = '{{ route("pwa.download.apk") }}';
            update.textContent = 'Download the Android update';
            status.append(' ', update);
        }
        return;
    }

    if (!consentOk) {
        status.hidden = false;
        status.textContent = 'Confirm your consent above to enable registration.';
        return;
    }

    status.hidden = false;
    status.textContent = 'Ready for registration. Your operating system will open its native verification prompt.';
}

function updateCapabilityPresentation(result) {
    const stage = document.getElementById('bioCapabilityStage');
    const pill = document.getElementById('bioStatePill');
    const pillText = document.getElementById('bioStatePillText');
    const spinner = document.getElementById('bioCapabilitySpinner');
    const pad = document.getElementById('bioSensorPad');
    const padIcon = document.getElementById('bioSensorPadIcon');
    const heroIcon = document.getElementById('bioMethodHeroIcon');
    const capTitle = document.getElementById('bioCapabilityTitle');
    const capDesc = document.getElementById('bioCapabilityDesc');
    const unsupportedPanel = document.getElementById('bioUnsupportedPanel');
    const unsupportedDetail = document.getElementById('bioUnsupportedDetail');
    const controlsWrap = document.getElementById('bioRegistrationControls');
    const btnText = document.getElementById('startBioBtnText');
    const btnIcon = document.getElementById('startBioBtnIcon');
    const nameInput = document.getElementById('bioDeviceNameInput');

    if (spinner) spinner.hidden = true;

    const available = Boolean(result && result.available === true);
    const fpUi = Boolean(result && result.supportsFingerprintSpecificUi === true);

    if (nameInput && !nameInput.value && result?.defaultDeviceName) {
        nameInput.value = result.defaultDeviceName;
    }

    if (available) {
        if (stage) stage.dataset.state = 'ready';
        if (pill) pill.dataset.tone = 'ready';
        if (pillText) pillText.textContent = 'Compatible device detected';
        if (pad) {
            pad.classList.remove('is-unsupported', 'is-awaiting');
            pad.classList.add('is-ready');
        }
        const iconClass = fpUi ? 'bi bi-fingerprint' : 'bi bi-shield-check';
        if (padIcon) padIcon.className = iconClass;
        if (heroIcon) heroIcon.className = fpUi ? 'bi bi-fingerprint' : 'bi bi-shield-lock';
        if (capTitle) capTitle.textContent = result.authenticatorLabel || 'Platform authenticator ready';
        if (capDesc) capDesc.textContent = result.disclaimer || 'Your operating system will verify you locally and share only a cryptographic public key.';
        if (btnText) btnText.textContent = fpUi ? 'Register fingerprint / biometrics' : 'Register this device';
        if (btnIcon) btnIcon.className = fpUi ? 'bi bi-fingerprint' : 'bi bi-shield-check';
        if (unsupportedPanel) unsupportedPanel.hidden = true;
        if (controlsWrap) controlsWrap.hidden = false;
    } else {
        if (stage) stage.dataset.state = 'unsupported';
        if (pill) pill.dataset.tone = 'warning';
        if (pillText) pillText.textContent = 'Unsupported device or browser';
        if (pad) {
            pad.classList.remove('is-ready', 'is-awaiting');
            pad.classList.add('is-unsupported');
        }
        if (padIcon) padIcon.className = 'bi bi-exclamation-triangle';
        if (capTitle) capTitle.textContent = 'Platform authentication unavailable';
        const reasonMsg = window.DevicePasskeys?.message(result || { reason: 'unsupported' }, 'create')
            || 'This browser cannot create a platform passkey.';
        if (capDesc) capDesc.textContent = reasonMsg;
        if (unsupportedDetail) unsupportedDetail.textContent = reasonMsg;
        if (unsupportedPanel) unsupportedPanel.hidden = false;
        if (controlsWrap) controlsWrap.hidden = false;
    }
}

async function refreshDevicePasskeySupport() {
    const check = ++passkeySupportCheck;
    const result = await (window.DevicePasskeys?.check('create') || { available: false, reason: 'unsupported' });
    if (check === passkeySupportCheck) {
        lastCapabilityResult = result;
        updateCapabilityPresentation(result);
        syncRegistrationButtonState();
    }
    return Boolean(result && result.available === true);
}

function initializeDevicePasskeyRegistration() {
    const start = document.getElementById('startBioBtn');
    if (!start) return;

    start.addEventListener('click', beginDevicePasskeyRegistration);
    document.getElementById('retryBtn')?.addEventListener('click', beginDevicePasskeyRegistration);
    document.getElementById('cancelBioBtn')?.addEventListener('click', cancelBiometricRegistration);
    document.getElementById('doneBioBtn')?.addEventListener('click', cancelBiometricRegistration);
    document.getElementById('backToIdleBtn')?.addEventListener('click', cancelBiometricRegistration);
    document.getElementById('bioConsentCheckbox')?.addEventListener('change', syncRegistrationButtonState);

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

function showDeviceActionStatus(message, isError = false) {
    const el = document.getElementById('deviceActionStatus');
    if (!el) return;
    if (!message) {
        el.hidden = true;
        el.textContent = '';
        return;
    }
    el.hidden = false;
    el.textContent = message;
    el.style.color = isError ? '#ffb3bc' : '#6ee7b7';
}

function cancelBiometricRegistration() {
    bioAbortController?.abort();
    bioAbortController = null;
    const confirmBtn = document.getElementById('confirmBioBtn');
    if (confirmBtn) confirmBtn.hidden = true;
    showRegistrationView('bioIdleView');
    setBioRegistrationStage(1);
    syncRegistrationButtonState();
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
    loadingState.className = 'device-list-loading bio-device-loading';
    loadingState.setAttribute('role', 'status');
    const loadingIcon = document.createElement('span');
    loadingIcon.className = 'biometric-spinner';
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
                badge.textContent = `${devices.length} Active`;
                badge.classList.remove('is-error');
                badge.classList.add('is-active');
            }

            const activeSummary = document.createElement('div');
            activeSummary.className = 'bio-device-banner is-active';
            activeSummary.innerHTML = `
                <i class="bi bi-check-circle-fill" aria-hidden="true" style="font-size:1.15rem;"></i>
                <div>WebAuthn device verification is <strong>active</strong> on your account.</div>`;
            list.appendChild(activeSummary);

            devices.forEach(d => {
                const div = document.createElement('div');
                div.className = 'device-item-card';
                const createdStr = d.created_at
                    ? new Date(d.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
                    : 'Registered';
                const lastUsedStr = d.last_used_at
                    ? 'Last used ' + new Date(d.last_used_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
                    : 'Not used yet';

                div.innerHTML = `
                    <div class="device-item-left">
                        <div class="device-item-icon">
                            <i class="bi ${d.biometric_type === 'fingerprint' ? 'bi-fingerprint' : 'bi-shield-lock'}" aria-hidden="true"></i>
                        </div>
                        <div class="device-item-info">
                            <div class="device-item-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span data-device-name></span>
                            </div>
                            <div class="device-item-meta">
                                <span class="device-meta-verified"><i class="bi bi-shield-check me-1" aria-hidden="true"></i><span data-device-meta></span></span>
                                <span class="device-meta-dot" aria-hidden="true">•</span>
                                <span class="device-meta-date">Added ${createdStr} · ${lastUsedStr}</span>
                            </div>
                        </div>
                    </div>
                    <button class="device-remove-btn" type="button" title="Revoke credential">
                        <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Revoke
                    </button>`;
                const displayName = d.name || d.device_name || 'Device passkey';
                div.querySelector('[data-device-name]').textContent = displayName;
                const attachmentLabel = d.authenticator_attachment === 'platform' ? 'Platform passkey' : 'Security key';
                div.querySelector('[data-device-meta]').textContent = d.user_agent_summary
                    ? `${attachmentLabel} · ${d.user_agent_summary}`
                    : attachmentLabel;
                const removeButton = div.querySelector('.device-remove-btn');
                removeButton.setAttribute('aria-label', 'Revoke ' + displayName);
                removeButton.addEventListener('click', () => removeDevice(d.credential_id, removeButton));
                list.appendChild(div);
            });
        } else {
            if (badge) {
                badge.textContent = '0 Registered';
                badge.classList.remove('is-active', 'is-error');
            }
            const emptyDiv = document.createElement('div');
            emptyDiv.id = 'noDevices';
            emptyDiv.className = 'bio-device-empty';
            emptyDiv.innerHTML = `
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <strong>No credentials registered on this account yet</strong>
                <div style="font-size:.82rem;">Confirm your consent above and register this device to enable passkey sign-in and attendance verification.</div>`;
            list.appendChild(emptyDiv);
        }
    } catch (e) {
        console.error('Failed to load biometric devices', e);
        list.innerHTML = '';
        list.removeAttribute('aria-busy');
        if (badge) {
            badge.textContent = 'Could not load devices';
            badge.classList.remove('is-active');
            badge.classList.add('is-error');
        }
        const errorState = document.createElement('div');
        errorState.className = 'device-list-error bio-device-error';
        errorState.setAttribute('role', 'alert');
        const errorText = document.createElement('span');
        errorText.textContent = 'We couldn’t load your registered devices. Check your connection and try again.';
        const retryButton = document.createElement('button');
        retryButton.type = 'button';
        retryButton.className = 'device-load-retry flow-button';
        retryButton.innerHTML = '<i class="bi bi-arrow-repeat" aria-hidden="true"></i> Try again';
        retryButton.addEventListener('click', function () {
            retryButton.disabled = true;
            retryButton.textContent = 'Checking again…';
            return loadDevices();
        });
        errorState.appendChild(errorText);
        errorState.appendChild(retryButton);
        list.appendChild(errorState);
    }
}

async function removeDevice(credentialId, btn) {
    if (!confirm('Revoke this device credential? It will immediately stop working for sign-in and attendance until re-registered.')) return;
    showDeviceActionStatus('');
    try {
        if (btn) {
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.textContent = 'Revoking…';
        }
        const res = await fetch('{{ route("webauthn.remove") }}', {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ credential_id: credentialId, reason: 'user_revoked' })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            throw new Error(data.message || 'Could not revoke this credential. Please try again.');
        }
        window.DevicePasskeys?.forgetBrowserPasskey?.(credentialId);
        await loadDevices();
        showDeviceActionStatus(data.message || 'Device credential revoked.');
        if (typeof showToast === 'function') {
            showToast(data.message || 'Device credential revoked.', 'info');
        }
    } catch (err) {
        console.error('Failed to revoke biometric device', err);
        if (btn) {
            btn.disabled = false;
            btn.removeAttribute('aria-busy');
            btn.innerHTML = '<i class="bi bi-trash3 me-1"></i>Revoke';
        }
        showDeviceActionStatus(err.message || 'Could not revoke this credential. Please try again.', true);
        if (typeof showToast === 'function') {
            showToast(err.message || 'Could not revoke this credential. Please try again.', 'error');
        }
    }
}

async function beginDevicePasskeyRegistration() {
    if (bioAbortController) return;
    if (!isConsentSatisfied()) {
        syncRegistrationButtonState();
        document.getElementById('bioConsentCheckbox')?.focus({ preventScroll: true });
        return;
    }

    const controller = new AbortController();
    bioAbortController = controller;
    syncRegistrationButtonState();

    const statusTitle = document.getElementById('fpScanningTitle');
    const statusText = document.getElementById('fpStatusSub');
    const activeLabel = document.getElementById('bioActiveStateLabel');
    const confirmButton = document.getElementById('confirmBioBtn');
    const deviceNameInput = document.getElementById('bioDeviceNameInput');

    function showClassifiedError(classified) {
        const errorTitle = document.getElementById('bioErrorTitle');
        const errorDesc = document.getElementById('bioErrorDesc');
        const retryBtn = document.getElementById('retryBtn');
        if (errorTitle) errorTitle.textContent = classified.title || 'Registration could not finish';
        if (errorDesc) errorDesc.textContent = classified.message || 'Please try again.';
        if (retryBtn) retryBtn.hidden = classified.retryable === false;
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

        if (activeLabel) activeLabel.textContent = 'Preparing registration challenge…';
        if (statusTitle) statusTitle.textContent = 'Preparing secure challenge';
        if (statusText) statusText.textContent = 'Requesting a one-time cryptographic challenge from the server.';
        showRegistrationView('bioScanningView');
        setBioRegistrationStage(2);
        focusBiometricHeading(statusTitle);

        const optRes = await fetch('{{ route("webauthn.register.options") }}', {
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            },
            signal: controller.signal
        });
        const options = await optRes.json().catch(() => ({}));
        if (controller.signal.aborted) return;
        if (!optRes.ok || !options.challenge || !options.user) {
            const err = new Error(options.message || 'Could not start registration. Please try again.');
            err.status = optRes.status;
            err.code = options.code;
            throw err;
        }

        const scan = () => {
            if (confirmButton) confirmButton.hidden = true;
            if (activeLabel) activeLabel.textContent = 'Awaiting device authentication';
            if (statusTitle) statusTitle.textContent = 'Complete your device prompt';
            if (statusText) {
                statusText.textContent = lastCapabilityResult?.supportsFingerprintSpecificUi
                    ? 'Touch your fingerprint sensor or complete Android’s biometric prompt.'
                    : 'Your operating system is handling verification. Follow the native prompt on this device.';
            }
            return window.DevicePasskeys.registerCredential(options, controller.signal);
        };

        let registration;
        if (window.DevicePasskeys.needsTap('create')) {
            if (activeLabel) activeLabel.textContent = 'Ready for device prompt';
            if (statusTitle) statusTitle.textContent = 'Open device prompt';
            if (statusText) statusText.textContent = 'Tap below to open your operating system’s authentication prompt.';
            if (confirmButton) confirmButton.hidden = false;
            registration = await window.DevicePasskeys.confirmOnClick(confirmButton, scan, controller.signal);
        } else {
            registration = await scan();
        }
        if (controller.signal.aborted || !registration) return;

        setBioRegistrationStage(3);
        if (activeLabel) activeLabel.textContent = 'Verifying cryptographic response…';
        if (statusTitle) statusTitle.textContent = 'Validating on server';
        if (statusText) statusText.textContent = 'Confirming your public key and linking it securely to your account.';

        const chosenName = (deviceNameInput && deviceNameInput.value.trim())
            || lastCapabilityResult?.defaultDeviceName
            || undefined;

        const saveRes = await fetch('{{ route("webauthn.register") }}', {
            method: 'POST',
            credentials: 'same-origin',
            signal: controller.signal,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ...registration,
                device_name: chosenName,
                biometric_type: lastCapabilityResult?.biometricType || 'device_lock',
                consent_accepted: true
            })
        });
        const result = await saveRes.json().catch(() => ({}));
        if (controller.signal.aborted) return;

        if (!saveRes.ok || !result.success) {
            const saveErr = new Error(result.message || 'Server validation failed. Please try again.');
            saveErr.status = saveRes.status;
            saveErr.code = result.code;
            throw saveErr;
        }

        // Only show registration success AFTER server-side cryptographic validation returns 200 OK.
        setBioRegistrationStage(4);
        const successDesc = document.getElementById('bioSuccessDesc');
        if (successDesc && result.device?.device_name) {
            successDesc.textContent = `"${result.device.device_name}" passed cryptographic verification and is now linked to your account.`;
        }
        showRegistrationView('bioSuccessView');
        focusBiometricHeading(document.getElementById('bioSuccessTitle'));
        window.triggerHaptic?.('success');
        await loadDevices();
    } catch (error) {
        if (controller.signal.aborted) return;
        const classified = window.DevicePasskeys?.classifyError
            ? window.DevicePasskeys.classifyError(error, 'register')
            : { title: 'Registration failed', message: error.message || 'Please try again.', retryable: true };
        showClassifiedError(classified);
    } finally {
        if (bioAbortController === controller) {
            if (confirmButton) confirmButton.hidden = true;
            bioAbortController = null;
            syncRegistrationButtonState();
        }
    }
}
