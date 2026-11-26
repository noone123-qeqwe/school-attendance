const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const script = fs.readFileSync(path.resolve(__dirname, '../../public/js/device-passkey-capabilities.js'), 'utf8');

function loadPasskeys(PublicKeyCredential, {
    secure = true,
    credentials = { get() {}, create() {} },
    native,
    userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/128.0.0.0 Safari/537.36',
    storage = null
} = {}) {
    const store = storage || new Map();
    const localStorage = {
        getItem: key => (store.has(key) ? store.get(key) : null),
        setItem: (key, val) => store.set(key, String(val)),
        removeItem: key => store.delete(key)
    };
    const window = { isSecureContext: secure, PublicKeyCredential, AndroidDeviceBridge: native, localStorage };
    if (native) window.AttendancePasskey = { postMessage() {}, addEventListener() {} };
    vm.runInNewContext(script, {
        window,
        navigator: { credentials, userAgent, onLine: true },
        DOMException,
        atob,
        btoa,
        setTimeout,
        clearTimeout
    });
    return window.DevicePasskeys;
}

const platform = value => ({ isUserVerifyingPlatformAuthenticatorAvailable: async () => value });
const browserCapabilities = values => ({ getClientCapabilities: async () => values });

test('requires a secure context and WebAuthn API for the requested operation', async () => {
    assert.equal((await loadPasskeys(platform(true), { secure: false }).check('get')).reason, 'insecure');
    assert.equal((await loadPasskeys(null).check('get')).reason, 'unsupported');
    const signInOnly = loadPasskeys(platform(true), { credentials: { get() {} } });
    assert.equal((await signInOnly.check('get')).available, true);
    assert.equal((await signInOnly.check('create')).reason, 'unsupported');
});

test('supports confirmed platform authenticator on desktop browsers without misleadingly claiming fingerprint-only UI', async () => {
    const passkeys = loadPasskeys(platform(true), {
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'
    });
    for (const operation of ['get', 'create']) {
        const result = await passkeys.check(operation);
        assert.equal(result.available, true);
        assert.equal(result.platformAvailable, true);
        assert.equal(result.reason, null);
        assert.equal(result.supportsFingerprintSpecificUi, false);
        assert.equal(result.biometricType, 'device_lock');
        assert.equal(result.platform.os, 'windows');
        assert.equal(result.platform.browser, 'chrome');
        assert.equal(result.defaultDeviceName, 'Chrome on Windows PC');
        assert.match(result.disclaimer, /operating system/i);
    }
});

test('supports iOS Safari and macOS Safari with accurate platform metadata and non-fingerprint-only labeling', async () => {
    const ios = loadPasskeys(platform(true), {
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1'
    });
    const iosCap = await ios.check('create');
    assert.equal(iosCap.available, true);
    assert.equal(iosCap.supportsFingerprintSpecificUi, false);
    assert.equal(iosCap.platform.os, 'ios');
    assert.equal(iosCap.defaultDeviceName, 'Safari on iPhone');

    const mac = loadPasskeys(platform(true), {
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 Version/17.5 Safari/605.1.15'
    });
    const macCap = await mac.check('create');
    assert.equal(macCap.available, true);
    assert.equal(macCap.supportsFingerprintSpecificUi, false);
    assert.equal(macCap.platform.os, 'macos');
    assert.equal(macCap.defaultDeviceName, 'Safari on Mac');
});

test('does not offer a passkey when the browser reports no platform authenticator', async () => {
    const passkeys = loadPasskeys(platform(false));
    for (const operation of ['get', 'create']) {
        const result = await passkeys.check(operation);
        assert.equal(result.available, false);
        assert.equal(result.reason, 'unavailable');
    }
    assert.match(passkeys.message(await passkeys.check('get'), 'get'), /sign in with your password/i);
});

for (const key of ['passkeyPlatformAuthenticator', 'hybridTransport']) {
    test(`does not offer passkey when browser reports ${key} without a local authenticator`, async () => {
        const passkeys = loadPasskeys({ ...platform(false), ...browserCapabilities({ [key]: true }) });
        const signIn = await passkeys.check('get');
        assert.equal(signIn.available, false);
        assert.equal(signIn.platformAvailable, false);
        assert.equal((await passkeys.check('create')).available, false);
    });
}

test('checks client capabilities when platform probe is missing or rejects', async () => {
    const capabilities = browserCapabilities({ userVerifyingPlatformAuthenticator: true });
    const failingProbe = { isUserVerifyingPlatformAuthenticatorAvailable: async () => { throw new Error(); } };
    for (const credential of [capabilities, { ...failingProbe, ...capabilities }]) {
        const passkeys = loadPasskeys(credential);
        assert.equal((await passkeys.check('get')).platformAvailable, true);
        assert.equal((await passkeys.check('create')).available, true);
    }
});

test('an inconclusive browser probe permits registration through platform creation request', async () => {
    const passkeys = loadPasskeys({ getClientCapabilities: async () => { throw new Error(); } });
    const res = await passkeys.check('get');
    assert.equal(res.available, true);
    assert.equal(res.platformAvailable, null);
    assert.equal(res.reason, null);
    assert.equal((await passkeys.check('create')).available, true);
});

test('Android native bridge exposes fingerprint-specific UI only when sensor is genuinely available', async () => {
    const native = { isNativeApp: () => true, isBiometricAvailable: () => true };
    const passkeys = loadPasskeys(null, { native, credentials: {} });
    assert.equal((await passkeys.check('create')).reason, 'update-app');

    native.hasLocalPasskey = () => false;
    const ready = await passkeys.check('create');
    assert.equal(ready.available, true);
    assert.equal(ready.androidBridge, true);
    assert.equal(ready.supportsFingerprintSpecificUi, true);
    assert.equal(ready.biometricType, 'fingerprint');

    native.isBiometricAvailable = () => false;
    const noSensor = await passkeys.check('create');
    assert.equal(noSensor.available, false);
    assert.equal(noSensor.reason, 'sensor-unavailable');
    assert.equal(noSensor.supportsFingerprintSpecificUi, false);
});

test('enrollment sends platform-only WebAuthn request, preserves signed response, and remembers/forgets credential', async () => {
    const calls = [];
    const store = new Map();
    const rawId = Uint8Array.of(1, 2);
    const passkeys = loadPasskeys(platform(true), {
        storage: store,
        credentials: {
            async create(options) {
                calls.push(options);
                return {
                    rawId,
                    authenticatorAttachment: 'platform',
                    response: { clientDataJSON: rawId, attestationObject: rawId, getTransports: () => ['internal'] }
                };
            }
        }
    });
    const result = await passkeys.registerFingerprint({
        challenge: 'AQI', rp: { id: 'example.test', name: 'Attendance' }, user: { id: 'AQI' }
    });
    assert.equal(calls[0].publicKey.authenticatorSelection.authenticatorAttachment, 'platform');
    assert.equal(calls[0].publicKey.authenticatorSelection.userVerification, 'required');
    assert.equal(calls[0].publicKey.authenticatorSelection.residentKey, 'preferred');
    assert.equal(result.credential_id, 'AQI');
    assert.equal(result.credential.rawId, 'AQI');
    assert.equal(result.credential.response.attestationObject, 'AQI');
    assert.match(store.get('attendance_browser_passkeys'), /AQI/);

    passkeys.forgetBrowserPasskey('AQI');
    assert.equal(store.get('attendance_browser_passkeys'), '[]');
});

test('an empty account credential list never opens cross-device discovery', async () => {
    const passkeys = loadPasskeys(platform(true), { credentials: { get() { assert.fail('unexpected prompt'); } } });
    await assert.rejects(passkeys.verifyFingerprint({ allowCredentials: [] }), { name: 'NotFoundError' });
});

test('classifyError maps all biometric states consistently (cancelled, duplicate, expired, revoked, unsupported, network, consent)', () => {
    const passkeys = loadPasskeys(platform(true));

    assert.equal(passkeys.classifyError(new DOMException('cancelled', 'AbortError'), 'verify').state, passkeys.STATES.CANCELLED);
    assert.equal(passkeys.classifyError(new DOMException('closed', 'NotAllowedError'), 'register').state, passkeys.STATES.CANCELLED);
    assert.equal(passkeys.classifyError(new DOMException('dup', 'InvalidStateError'), 'register').state, passkeys.STATES.DUPLICATE);
    assert.equal(passkeys.classifyError({ status: 409, code: 'CREDENTIAL_ALREADY_REGISTERED' }, 'register').state, passkeys.STATES.DUPLICATE);
    assert.equal(passkeys.classifyError({ code: 'PASSKEY_CHALLENGE_EXPIRED' }, 'verify').state, passkeys.STATES.EXPIRED);
    assert.equal(passkeys.classifyError({ code: 'CREDENTIAL_REVOKED' }, 'verify').state, passkeys.STATES.NO_CREDENTIAL);
    assert.equal(passkeys.classifyError(new DOMException('no hw', 'NotSupportedError'), 'register').state, passkeys.STATES.UNSUPPORTED);
    assert.equal(passkeys.classifyError(new TypeError('Failed to fetch'), 'verify').state, passkeys.STATES.NETWORK_ERROR);
    assert.equal(passkeys.classifyError({ status: 500 }, 'verify').state, passkeys.STATES.NETWORK_ERROR);
    assert.equal(passkeys.classifyError({ status: 422, code: 'CONSENT_REQUIRED' }, 'register').state, passkeys.STATES.FAILED);
});

test('QR verification requires explicit user click (never auto-invokes biometric prompt) and supports Android bridge', async () => {
    const view = fs.readFileSync(path.resolve(__dirname, '../../resources/views/qr/verify.blade.php'), 'utf8');
    assert.match(view, /DevicePasskeys\.verifyFingerprint\(opts, signal, allowSavedPasskey\)/);
    assert.match(view, /showFingerprintReady\(\)/);
    assert.doesNotMatch(view, /else if \(HAS_FINGERPRINT\) \{\s*doFingerprint\(\);/);

    const bridge = new EventTarget();
    const window = {
        isSecureContext: true, AttendancePasskey: bridge,
        AndroidDeviceBridge: { matchesLocalPasskey: list => JSON.parse(list)[0].id === 'AQI' }
    };
    bridge.postMessage = message => {
        const { id, type, publicKey } = JSON.parse(message);
        assert.equal(type, 'get');
        assert.equal(publicKey.challenge, 'Bwg');
        assert.deepEqual(publicKey.allowCredentials[0].transports, ['internal']);
        assert.equal(publicKey.userVerification, 'required');
        bridge.dispatchEvent(new MessageEvent('message', { data: JSON.stringify({
            id, assertion: { rawId: 'AQI', response: { clientDataJSON: 'AwQ', authenticatorData: 'BQY', signature: 'CQo' } }
        }) }));
    };
    vm.runInNewContext(script, { window, navigator: {}, setTimeout, clearTimeout, DOMException, atob, btoa });
    const signed = await window.DevicePasskeys.verifyFingerprint({
        rpId: 'school.example', challenge: 'Bwg', allowCredentials: [{ id: 'AQI' }]
    });
    assert.equal(signed.id, 'AQI');
    assert.equal(signed.rawId, 'AQI');
    assert.equal(signed.response.signature, 'CQo');
});
