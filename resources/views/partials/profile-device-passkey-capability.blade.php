<script src="{{ asset('js/device-passkey-capabilities.js') }}?v={{ filemtime(public_path('js/device-passkey-capabilities.js')) }}" @cspNonce></script>
<script @cspNonce>
let profileRegistration = null;
let profilePasskeyCapabilityCheck = 0;
let profileLastCapability = null;

function ensureProfileConsentControl() {
    const btn = document.getElementById('registerFpBtn');
    if (!btn) return null;
    let checkbox = document.getElementById('profilePasskeyConsent');
    if (checkbox) return checkbox;

    const wrapper = document.createElement('label');
    wrapper.id = 'profilePasskeyConsentWrap';
    wrapper.htmlFor = 'profilePasskeyConsent';
    wrapper.style.cssText = 'display:flex;align-items:flex-start;gap:10px;padding:12px 14px;margin-bottom:12px;border-radius:10px;border:1px solid rgba(255,255,255,0.1);background:#141010;font-size:0.78rem;line-height:1.45;color:#D6CCC6;cursor:pointer;';

    checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.id = 'profilePasskeyConsent';
    checkbox.style.cssText = 'margin-top:2px;flex-shrink:0;cursor:pointer;';

    const text = document.createElement('span');
    text.textContent = 'I consent to linking this device’s built-in biometric or screen-lock passkey to my account. Only a cryptographic public key is stored — never raw fingerprint or biometric data.';

    wrapper.appendChild(checkbox);
    wrapper.appendChild(text);
    btn.parentNode.insertBefore(wrapper, btn);

    checkbox.addEventListener('change', syncProfileRegisterButton);
    return checkbox;
}

function syncProfileRegisterButton() {
    const btn = document.getElementById('registerFpBtn');
    if (!btn) return;
    const consent = ensureProfileConsentControl();
    const available = Boolean(profileLastCapability && profileLastCapability.available === true);
    const consentOk = !consent || consent.checked === true;
    btn.disabled = Boolean(profileRegistration) || !available || !consentOk;
}

async function refreshProfilePasskeyCapability() {
    const btn = document.getElementById('registerFpBtn');
    const status = document.getElementById('webauthnUnsupported');
    const message = document.getElementById('webauthnUnsupportedMsg');
    if (!btn) return false;
    ensureProfileConsentControl();
    const check = ++profilePasskeyCapabilityCheck;
    btn.disabled = true;

    const passkeys = window.DevicePasskeys;
    const result = await passkeys?.check('create') ?? { available: false, reason: 'unknown' };
    if (check === profilePasskeyCapabilityCheck) {
        profileLastCapability = result;
        if (status) status.style.display = result.available ? 'none' : 'block';
        if (message) {
            message.textContent = result.available
                ? ''
                : (passkeys?.message(result, 'create') ?? 'Passkey setup is unavailable in this browser.');
        }
        const consentWrap = document.getElementById('profilePasskeyConsentWrap');
        if (consentWrap) consentWrap.style.display = result.available ? 'flex' : 'none';
        syncProfileRegisterButton();
    }
    return Boolean(result.available);
}

async function registerDevicePasskey() {
    if (window.location.hostname === '127.0.0.1') {
        const local = new URL(location.href);
        local.hostname = 'localhost';
        location.replace(local.href);
        return;
    }
    const button = document.getElementById('registerFpBtn');
    const consent = ensureProfileConsentControl();
    if (!button || button.disabled || profileRegistration) return;
    if (consent && !consent.checked) {
        consent.focus({ preventScroll: true });
        return;
    }

    const message = document.getElementById('fpMessage');
    const controller = new AbortController();
    const signal = controller.signal;
    profileRegistration = controller;
    const originalLabel = button.innerHTML;
    const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' };
    button.disabled = true;
    if (message) message.style.display = 'none';

    try {
        button.textContent = 'Preparing secure challenge…';
        const start = await fetch('{{ route("webauthn.register.options") }}', {
            credentials: 'same-origin',
            headers,
            signal
        });
        const options = await start.json().catch(() => ({}));
        if (!start.ok || !options.challenge) {
            const optErr = new Error(options.message || 'Could not start registration.');
            optErr.status = start.status;
            optErr.code = options.code;
            throw optErr;
        }

        const scan = () => {
            button.textContent = 'Awaiting device prompt…';
            return window.DevicePasskeys.registerCredential(options, signal);
        };
        button.textContent = 'Continue to device prompt';
        const registration = window.DevicePasskeys.needsTap('create')
            ? await window.DevicePasskeys.confirmOnClick(button, scan, signal)
            : await scan();
        if (signal.aborted || !registration) return;

        button.textContent = 'Validating on server…';
        const save = await fetch('{{ route("webauthn.register") }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { ...headers, 'Content-Type': 'application/json' },
            signal,
            body: JSON.stringify({
                ...registration,
                device_name: profileLastCapability?.defaultDeviceName || undefined,
                biometric_type: profileLastCapability?.biometricType || 'device_lock',
                consent_accepted: true
            })
        });
        const result = await save.json().catch(() => ({}));
        if (!save.ok || !result.success) {
            const saveErr = new Error(result.message || 'Could not register this device.');
            saveErr.status = save.status;
            saveErr.code = result.code;
            throw saveErr;
        }

        if (message) {
            message.style.display = 'block';
            message.style.color = '#4ade80';
            message.textContent = 'Device verified and registered. You can now use it to sign in.';
        }
        @if($reloadAfterRegistration ?? false)
        setTimeout(() => location.reload(), 1500);
        @else
        if (typeof loadDevices === 'function') await loadDevices();
        @endif
    } catch (error) {
        if (signal.aborted) return;
        const classified = window.DevicePasskeys?.classifyError
            ? window.DevicePasskeys.classifyError(error, 'register')
            : { message: error.message || 'Device registration could not finish.' };
        if (message) {
            message.style.display = 'block';
            message.style.color = '#f87171';
            message.textContent = classified.message;
        }
    } finally {
        profileRegistration = null;
        button.innerHTML = originalLabel;
        refreshProfilePasskeyCapability();
    }
}

async function loadDevices() {
    const list = document.getElementById('deviceList');
    if (!list) return;
    list.setAttribute('aria-busy', 'true');
    try {
        const res = await fetch('{{ route("webauthn.devices") }}', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('Unable to load registered devices.');
        const devices = await res.json();
        list.innerHTML = '';
        list.removeAttribute('aria-busy');

        if (Array.isArray(devices) && devices.length > 0) {
            const summary = document.createElement('div');
            summary.style.cssText = 'padding:12px 14px;color:#4ade80;font-size:.84rem;background:rgba(22,163,74,0.1);border-radius:10px;border:1px solid rgba(22,163,74,0.22);margin-bottom:12px;font-weight:600;display:flex;align-items:center;gap:8px;';
            summary.innerHTML = '<i class="bi bi-check-circle-fill" aria-hidden="true"></i><span></span>';
            summary.querySelector('span').textContent = `${devices.length} active device credential${devices.length === 1 ? '' : 's'} linked to this account.`;
            list.appendChild(summary);

            devices.forEach(d => {
                const row = document.createElement('div');
                row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:10px;margin-bottom:8px;';

                const left = document.createElement('div');
                left.style.cssText = 'display:flex;align-items:center;gap:10px;min-width:0;flex:1;';

                const iconWrap = document.createElement('div');
                iconWrap.style.cssText = 'width:36px;height:36px;border-radius:9px;background:rgba(74,222,128,0.12);display:flex;align-items:center;justify-content:center;color:#4ade80;flex-shrink:0;';
                iconWrap.innerHTML = `<i class="bi ${d.biometric_type === 'fingerprint' ? 'bi-fingerprint' : 'bi-shield-lock'}" aria-hidden="true"></i>`;

                const metaWrap = document.createElement('div');
                metaWrap.style.cssText = 'min-width:0;';

                const nameEl = document.createElement('div');
                nameEl.style.cssText = 'font-size:.86rem;font-weight:600;color:#f3e7cd;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
                const displayName = d.name || d.device_name || 'Device Passkey';
                nameEl.textContent = displayName;

                const subEl = document.createElement('div');
                subEl.style.cssText = 'font-size:.73rem;color:#b39b82;';
                const createdStr = d.created_at ? new Date(d.created_at).toLocaleDateString() : 'Registered';
                subEl.textContent = d.user_agent_summary
                    ? `${d.user_agent_summary} · Added ${createdStr}`
                    : `Platform passkey · Added ${createdStr}`;

                metaWrap.appendChild(nameEl);
                metaWrap.appendChild(subEl);
                left.appendChild(iconWrap);
                left.appendChild(metaWrap);

                const revokeBtn = document.createElement('button');
                revokeBtn.type = 'button';
                revokeBtn.textContent = 'Revoke';
                revokeBtn.setAttribute('aria-label', 'Revoke ' + displayName);
                revokeBtn.style.cssText = 'min-height:44px;padding:6px 14px;border-radius:8px;background:rgba(248,113,113,0.12);color:#f87171;border:1px solid rgba(248,113,113,0.28);font-size:.76rem;font-weight:600;cursor:pointer;flex-shrink:0;';
                revokeBtn.addEventListener('click', () => removeDevice(d.credential_id, revokeBtn));

                row.appendChild(left);
                row.appendChild(revokeBtn);
                list.appendChild(row);
            });
        } else {
            const emptyDiv = document.createElement('div');
            emptyDiv.id = 'noDevices';
            emptyDiv.style.cssText = 'text-align:center;padding:18px 16px;color:#b39b82;font-size:.84rem;background:rgba(255,255,255,0.02);border-radius:10px;border:1px dashed rgba(207,164,111,0.22);';
            emptyDiv.innerHTML = '<i class="bi bi-shield-lock" style="font-size:1.7rem;display:block;margin-bottom:6px;opacity:.5;" aria-hidden="true"></i><span>No active passkeys registered on this account yet.</span>';
            list.appendChild(emptyDiv);
        }
    } catch (err) {
        console.error('Error loading passkeys:', err);
        list.removeAttribute('aria-busy');
        list.innerHTML = '';
        const errDiv = document.createElement('div');
        errDiv.setAttribute('role', 'alert');
        errDiv.style.cssText = 'padding:12px 14px;border-radius:10px;background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.25);color:#fca5a5;font-size:.82rem;';
        errDiv.textContent = 'Could not load your registered devices. Check your connection and refresh.';
        list.appendChild(errDiv);
    }
}

async function removeDevice(credentialId, btn) {
    if (!confirm('Revoke this device credential? It will immediately stop working until re-registered.')) return;
    const msgEl = document.getElementById('fpMessage');
    try {
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Revoking…';
        }
        const res = await fetch('{{ route("webauthn.remove") }}', {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ credential_id: credentialId, reason: 'user_revoked' })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            throw new Error(data.message || 'Could not revoke credential.');
        }
        window.DevicePasskeys?.forgetBrowserPasskey?.(credentialId);
        if (msgEl) {
            msgEl.style.display = 'block';
            msgEl.style.color = '#4ade80';
            msgEl.textContent = data.message || 'Device credential revoked.';
        }
        await loadDevices();
    } catch (err) {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Revoke';
        }
        if (msgEl) {
            msgEl.style.display = 'block';
            msgEl.style.color = '#f87171';
            msgEl.textContent = err.message || 'Could not revoke this credential.';
        }
    }
}

window.addEventListener('pagehide', () => profileRegistration?.abort());

function checkProfilePasskeyWhenVisible() {
    if (!document.hidden && !profileRegistration) refreshProfilePasskeyCapability();
}

checkProfilePasskeyWhenVisible();
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => loadDevices());
} else {
    loadDevices();
}
window.addEventListener('pageshow', event => {
    if (event.persisted) checkProfilePasskeyWhenVisible();
});
document.addEventListener('visibilitychange', checkProfilePasskeyWhenVisible);
</script>
