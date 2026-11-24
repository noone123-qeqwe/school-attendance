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

function checkProfilePasskeyWhenVisible() {
    if (!document.hidden && !profilePasskeyRegistrationPending) refreshProfilePasskeyCapability();
}

window.addEventListener('pageshow', checkProfilePasskeyWhenVisible);
document.addEventListener('visibilitychange', checkProfilePasskeyWhenVisible);
</script>
