const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const script = fs.readFileSync(path.resolve(__dirname, '../../public/js/device-passkey-capabilities.js'), 'utf8');

function loadPasskeys(PublicKeyCredential, { secure = true, credentials = { get() {}, create() {} }, native } = {}) {
    const window = { isSecureContext: secure, PublicKeyCredential, AndroidDeviceBridge: native };
    if (native) window.AttendancePasskey = { postMessage() {}, addEventListener() {} };
    vm.runInNewContext(script, { window, navigator: { credentials } });
    return window.DevicePasskeys;
}

const platform = value => ({ isUserVerifyingPlatformAuthenticatorAvailable: async () => value });
const browserCapabilities = values => ({ getClientCapabilities: async () => values });

test('requires a secure context and the API for the requested operation', async () => {
    assert.equal((await loadPasskeys(platform(true), { secure: false }).check('get')).reason, 'insecure');
    assert.equal((await loadPasskeys(null).check('get')).reason, 'unsupported');
    const signInOnly = loadPasskeys(platform(true), { credentials: { get() {} } });
    assert.equal((await signInOnly.check('get')).available, true);
    assert.equal((await signInOnly.check('create')).reason, 'unsupported');
});

test('supports a confirmed platform authenticator for sign-in and setup', async () => {
    const passkeys = loadPasskeys(platform(true));
    for (const operation of ['get', 'create']) {
        assert.deepEqual({ ...await passkeys.check(operation) }, {
            available: true, platformAvailable: true, reason: null
        });
    }
});

test('does not offer a passkey when the browser finds no eligible option', async () => {
    const passkeys = loadPasskeys(platform(false));
    for (const operation of ['get', 'create']) {
        const result = await passkeys.check(operation);
        assert.equal(result.available, false);
        assert.equal(result.reason, 'unavailable');
    }
    assert.match(passkeys.message(await passkeys.check('get'), 'get'), /sign in with your password/i);
});

for (const key of ['passkeyPlatformAuthenticator', 'hybridTransport']) {
    test(`allows sign-in when the browser reports ${key} without a local authenticator`, async () => {
        const passkeys = loadPasskeys({ ...platform(false), ...browserCapabilities({ [key]: true }) });
        const signIn = await passkeys.check('get');
        assert.equal(signIn.available, true);
        assert.equal(signIn.platformAvailable, false);
        assert.equal((await passkeys.check('create')).available, false);
    });
}

test('checks client capabilities when the platform probe is missing or rejects', async () => {
    const capabilities = browserCapabilities({ userVerifyingPlatformAuthenticator: true });
    const failingProbe = { isUserVerifyingPlatformAuthenticatorAvailable: async () => { throw new Error(); } };
    for (const credential of [capabilities, { ...failingProbe, ...capabilities }]) {
        const passkeys = loadPasskeys(credential);
        assert.equal((await passkeys.check('get')).platformAvailable, true);
        assert.equal((await passkeys.check('create')).available, true);
    }
});

test('an inconclusive browser probe permits registration through the platform creation request', async () => {
    const passkeys = loadPasskeys({ getClientCapabilities: async () => { throw new Error(); } });
    assert.deepEqual({ ...await passkeys.check('get') }, {
        available: false, platformAvailable: null, reason: 'unknown'
    });
    assert.equal((await passkeys.check('create')).available, true);
});

test('Android registration uses the native sensor independently of browser WebAuthn', async () => {
    const native = { isNativeApp: () => true, isBiometricAvailable: () => true };
    const passkeys = loadPasskeys(null, { native, credentials: {} });
    assert.equal((await passkeys.check('create')).reason, 'update-app');
    native.hasLocalPasskey = () => false;
    assert.equal((await passkeys.check('create')).available, true);
    native.isBiometricAvailable = () => false;
    assert.equal((await passkeys.check('create')).reason, 'sensor-unavailable');
});

test('older APKs use their browser provider when it supports fingerprint enrollment', async () => {
    const native = { isNativeApp: () => true, isBiometricAvailable: () => true };
    const passkeys = loadPasskeys(platform(true), { native });
    assert.equal((await passkeys.check('create')).available, true);
    assert.equal(passkeys.useAndroidFingerprint('create'), false);
    assert.equal(passkeys.useAndroidFingerprint('get', [{ id: 'AQID' }]), false);
});

test('browser enrollment supports platform credentials with optional residency and respects cancellation', async () => {
    for (const firstError of ['ConstraintError', 'NotAllowedError']) {
        const calls = [];
        const credentials = {
            async create(options) {
                calls.push(options);
                if (calls.length === 1) throw new DOMException('First request ended', firstError);
                return 'registered';
            }
        };
        const passkeys = loadPasskeys(platform(true), { credentials });
        const signal = new AbortController().signal;
        const registration = passkeys.createBrowserFingerprint({ challenge: 'fixture' }, signal);
        if (firstError === 'NotAllowedError') {
            await assert.rejects(registration, { name: firstError });
            assert.equal(calls.length, 1);
        } else {
            assert.equal(await registration, 'registered');
            assert.equal(calls[1].publicKey.authenticatorSelection.residentKey, 'discouraged');
        }
        assert.equal(calls[0].publicKey.authenticatorSelection.residentKey, 'preferred');
        for (const call of calls) {
            assert.equal(call.publicKey.authenticatorSelection.authenticatorAttachment, 'platform');
            assert.equal(call.publicKey.authenticatorSelection.userVerification, 'required');
            assert.equal(call.signal, signal);
        }
    }
});

test('profile enrollment submits the signed Android registration with no browser credential API', async () => {
    const view = fs.readFileSync(path.resolve(__dirname, '../../resources/views/partials/profile-device-passkey-capability.blade.php'), 'utf8');
    const handler = view.slice(view.indexOf('async function requestProfileFingerprint'), view.indexOf('function checkProfilePasskeyWhenVisible'));
    const response = { clientDataJSON: 'AQI', attestationObject: 'AwQ', transports: ['internal'] };
    const passkeys = {
        useAndroidFingerprint: operation => operation === 'create',
        async requestAndroidPasskey(operation, options) {
            assert.equal(operation, 'create');
            assert.equal(options.challenge, 'BQY');
            assert.equal(options.authenticatorSelection.userVerification, 'required');
            return { id: 'Bwg', rawId: 'Bwg', type: 'public-key', response };
        }
    };
    const register = vm.runInNewContext(handler + '\nrequestProfileFingerprint', { window: { DevicePasskeys: passkeys } });
    const result = await register({}, { challenge: 'BQY' });
    assert.equal(result.credential_id, 'Bwg');
    assert.equal(result.credential.response.attestationObject, response.attestationObject);
    assert.equal(result.credential.response.clientDataJSON, response.clientDataJSON);
});

test('QR attendance requests a native signature when the WebView has no browser WebAuthn API', async () => {
    const view = fs.readFileSync(path.resolve(__dirname, '../../resources/views/qr/verify.blade.php'), 'utf8');
    const handler = view.slice(view.indexOf('async function requestFingerprintAssertion'), view.indexOf('async function submitAttendance'));
    const assertion = { rawId: 'AQID', type: 'public-key', response: { signature: 'fixture' } };
    const calls = [];
    const devicePasskeys = {
        useAndroidFingerprint: () => true,
        requestAndroidPasskey(...args) { calls.push(args); return Promise.resolve(assertion); }
    };
    const request = vm.runInNewContext(handler + '\nrequestFingerprintAssertion', { window: { DevicePasskeys: devicePasskeys } });
    const options = { challenge: 'AQAB', rpId: 'attendance.example', allowCredentials: [{ id: 'AQID' }] };
    const signal = new AbortController().signal;
    assert.equal(await request(options, signal), assertion);
    const [type, nativeOptions, nativeSignal] = calls[0];
    assert.equal(type, 'get');
    assert.equal(nativeSignal, signal);
    assert.equal(nativeOptions.rpId, options.rpId);
    assert.equal(nativeOptions.challenge, options.challenge);
    assert.equal(nativeOptions.allowCredentials[0].id, options.allowCredentials[0].id);
    assert.equal(nativeOptions.userVerification, 'required');
});
