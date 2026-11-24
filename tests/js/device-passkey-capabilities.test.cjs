const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const script = fs.readFileSync(path.resolve(__dirname, '../../public/js/device-passkey-capabilities.js'), 'utf8');

function loadPasskeys(PublicKeyCredential, { secure = true, credentials = { get() {}, create() {} } } = {}) {
    const window = { isSecureContext: secure, PublicKeyCredential };
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

test('treats missing capability information as unknown', async () => {
    const passkeys = loadPasskeys({ getClientCapabilities: async () => { throw new Error(); } });
    for (const operation of ['get', 'create']) {
        assert.deepEqual({ ...await passkeys.check(operation) }, {
            available: false, platformAvailable: null, reason: 'unknown'
        });
    }
});
