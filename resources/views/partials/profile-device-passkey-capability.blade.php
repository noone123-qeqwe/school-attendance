<script src="{{ asset('js/device-passkey-capabilities.js') }}?v={{ filemtime(public_path('js/device-passkey-capabilities.js')) }}" @cspNonce></script>
<script @cspNonce>
let profilePasskeyRegistrationPending = false;
let profilePasskeyCapabilityCheck = 0;

async function refreshProfilePasskeyCapability() {
    const btn = document.getElementById('registerFpBtn');
    const status = document.getElementById('webauthnUnsupported');
    const message = document.getElementById('webauthnUnsupportedMsg');
    const check = ++profilePasskeyCapabilityCheck;
    btn.disabled = true;

    const passkeys = window.DevicePasskeys;
    const result = await passkeys?.check('create') ?? { available: false, reason: 'unknown' };
    if (check === profilePasskeyCapabilityCheck) {
        status.style.display = result.available ? 'none' : 'block';
        message.textContent = result.available ? '' : (passkeys?.message(result, 'create') ?? 'Passkey setup is unavailable in this browser.');
        btn.disabled = profilePasskeyRegistrationPending || !result.available;
    }
    return result.available;
}

async function requestProfileFingerprint(button, options) {
    const passkeys = window.DevicePasskeys;
    let credential;
    if (passkeys.useAndroidFingerprint('create')) {
        button.textContent = 'Touch your fingerprint sensor…';
        credential = await passkeys.requestAndroidPasskey('create', {
            ...options,
            authenticatorSelection: {
                authenticatorAttachment: 'platform', userVerification: 'required',
                residentKey: 'required', requireResidentKey: true
            }
        });
    } else {
        const publicKey = {
            ...options,
            challenge: base64ToUint8Array(options.challenge),
            user: { ...options.user, id: base64ToUint8Array(options.user.id) },
            excludeCredentials: (options.excludeCredentials || []).map(item => ({
                ...item, id: base64ToUint8Array(item.id)
            })),
            hints: ['client-device']
        };
        const scan = () => {
            button.textContent = 'Waiting for your device…';
            return passkeys.createBrowserFingerprint(publicKey);
        };
        button.textContent = 'Continue to device prompt';
        credential = navigator.userActivation?.isActive === false
            ? await passkeys.confirmOnClick(button, scan)
            : await scan();
    }
    if (!credential) throw new Error('Passkey setup was canceled.');
    const response = credential.response;
    const encode = value => typeof value === 'string' ? value : bufferToBase64Url(value);
    const transports = response.transports || response.getTransports?.() || [];
    return {
        credential_id: encode(credential.rawId),
        transports,
        credential: {
            id: credential.id,
            type: credential.type,
            transports,
            response: {
                attestationObject: encode(response.attestationObject),
                clientDataJSON: encode(response.clientDataJSON)
            }
        }
    };
}

function checkProfilePasskeyWhenVisible() {
    if (!document.hidden && !profilePasskeyRegistrationPending) refreshProfilePasskeyCapability();
}

checkProfilePasskeyWhenVisible();
window.addEventListener('pageshow', event => {
    if (event.persisted) checkProfilePasskeyWhenVisible();
});
document.addEventListener('visibilitychange', checkProfilePasskeyWhenVisible);
</script>
