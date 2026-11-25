const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const loginView = fs.readFileSync(path.resolve(__dirname, '../../resources/views/auth/login.blade.php'), 'utf8');
const loginScript = loginView.slice(
    loginView.indexOf('// ── BIOMETRIC / WEBAUTHN AUTHENTICATION'),
    loginView.indexOf('// Bind DOM event listeners for biometric UI elements')
).replace(/\{\{\s*route\("([^"]+)"\)\s*\}\}/g, '/$1');

const identifier = 'student@example.test';
const credentialId = 'AQID';
const signedAssertion = {
    id: credentialId,
    rawId: credentialId,
    type: 'public-key',
    response: {
        clientDataJSON: 'BAU',
        authenticatorData: 'Bgc',
        signature: 'CAk',
        userHandle: 'Cgs'
    }
};

function createLogin({ native = true, account = identifier } = {}) {
    const bridge = new EventTarget();
    const nativeRequests = [];
    const browserRequests = [];
    const requests = [];
    const redirects = [];
    const password = { value: '' };
    let modal;

    bridge.postMessage = message => nativeRequests.push(JSON.parse(message));

    const context = vm.createContext({
        AbortController,
        atob,
        btoa,
        performance,
        setTimeout: (callback, delay) => setTimeout(callback, delay).unref(),
        clearTimeout,
        console: { error() {} },
        isSecureContext: true,
        PublicKeyCredential: function PublicKeyCredential() {},
        AttendancePasskey: native ? bridge : undefined,
        DevicePasskeys: { check: async () => ({ available: true, platformAvailable: true }) },
        document: {
            addEventListener() {},
            getElementById: id => id === 'bioModalPasswordInput' ? password : null,
            querySelector: () => null
        },
        idInput: { value: account },
        location: { hostname: 'attendance.example.test', replace: url => redirects.push(url) },
        getOrCreateDeviceKey: () => 'test-device',
        navigator: { credentials: {
            async get(options) {
                browserRequests.push(options);
                throw new DOMException('No passkey used', 'NotAllowedError');
            }
        } },
        async fetch(url, options) {
            const body = JSON.parse(options.body);
            requests.push({ url, body });

            const replies = {
                '/webauthn.login.options': [200, {
                    success: true,
                    challenge: 'DA0',
                    rpId: 'attendance.example.test',
                    hints: ['client-device'],
                    timeout: 45000,
                    has_account_credentials: Boolean(body.identifier),
                    allowCredentials: body.identifier ? [{ type: 'public-key', id: credentialId, transports: ['internal', 'hybrid'] }] : []
                }],
                '/webauthn.login': [200, { success: true, redirect: '/dashboard' }],
                '/webauthn.setup.options': [401, { success: false, message: 'Incorrect password' }]
            };
            assert.ok(replies[url], `Unexpected request to ${url}`);
            const [status, data] = replies[url];
            return { ok: status < 400, status, text: async () => JSON.stringify(data) };
        }
    });
    context.window = context;
    vm.runInContext(loginScript, context);
    context.openBiometricModal = config => { modal = config; };

    return {
        context, nativeRequests, browserRequests, requests, redirects, password,
        get modal() { return modal; },
        select(id) {
            const method = modal.methods?.find(candidate => candidate.id === id);
            assert.ok(method, `Missing ${id} in ${modal.title}`);
            return modal.onMethodSelect(method);
        },
        reply(data) {
            bridge.dispatchEvent(new MessageEvent('message', { data: JSON.stringify(data) }));
        }
    };
}

const settle = () => new Promise(resolve => setImmediate(resolve));

test('Android local sign-in submits the bridge assertion to the existing server login route', async () => {
    const login = createLogin();
    await login.context.handleBiometricLogin();
    assert.equal(login.requests[0].body.identifier, identifier);
    assert.ok(login.modal.methods.some(method => method.id === 'try_hybrid'));
    assert.ok(login.modal.methods.some(method => method.id === 'password'));

    login.select('device_lock');
    await settle();
    assert.equal(login.nativeRequests.length, 1);
    const request = login.nativeRequests[0];
    assert.equal(request.type, 'get');
    assert.equal(request.publicKey.challenge, 'DA0');
    assert.equal(request.publicKey.rpId, 'attendance.example.test');
    assert.equal(request.publicKey.userVerification, 'required');
    assert.equal(request.publicKey.allowCredentials[0].id, credentialId);
    assert.equal(login.browserRequests.length, 0);
    assert.deepEqual(login.redirects, []);

    login.reply({ id: request.id, assertion: signedAssertion });
    await settle();
    const submitted = login.requests.find(({ url }) => url === '/webauthn.login');
    assert.equal(submitted.body.identifier, identifier);
    assert.equal(submitted.body.credential_id, credentialId);
    assert.deepEqual(submitted.body.assertion, {
        id: signedAssertion.id,
        type: signedAssertion.type,
        response: signedAssertion.response
    });
    assert.deepEqual(login.redirects, ['/dashboard']);
});

test('cross-device sign-in uses browser WebAuthn even when the Android bridge exists', async () => {
    const login = createLogin();
    await login.context.handleBiometricLogin();
    login.select('try_hybrid');
    await settle();

    assert.deepEqual(login.nativeRequests, []);
    assert.equal(login.browserRequests.length, 1);
    const { publicKey } = login.browserRequests[0];
    assert.equal(publicKey.hints[0], 'hybrid');
    assert.equal(publicKey.userVerification, 'required');
    assert.deepEqual(Array.from(publicKey.allowCredentials[0].id), [1, 2, 3]);
    assert.deepEqual(Array.from(publicKey.allowCredentials[0].transports), ['internal', 'hybrid']);
});

test('a local Android miss can start password-confirmed setup for an account with other passkeys', async () => {
    const login = createLogin();
    await login.context.handleBiometricLogin();
    login.select('device_lock');
    await settle();
    login.reply({ id: login.nativeRequests[0].id, error: 'NotFoundError' });
    await settle();

    assert.ok(login.modal.methods.some(method => method.id === 'try_hybrid'));
    assert.ok(login.modal.methods.some(method => method.id === 'password'));
    login.select('setup');
    await settle();
    await login.modal.onPrimaryClick();
    assert.equal(login.modal.showPassword, true);
    assert.equal(login.requests.some(({ url }) => url === '/webauthn.setup.options'), false);

    login.password.value = 'fixture-password';
    await login.modal.onPrimaryClick();
    assert.deepEqual(login.requests.at(-1), {
        url: '/webauthn.setup.options',
        body: { identifier, password: 'fixture-password' }
    });
    assert.equal(login.modal.message, 'Incorrect password');
});

test('a late Android assertion after cancellation cannot complete sign-in', async () => {
    const login = createLogin();
    await login.context.handleBiometricLogin();
    login.select('device_lock');
    await settle();

    const request = login.nativeRequests[0];
    login.context.closeBiometricModal();
    assert.deepEqual(login.nativeRequests[1], { type: 'cancel', id: request.id });
    login.reply({ id: request.id, assertion: signedAssertion });
    await settle();

    assert.equal(login.requests.some(({ url }) => url === '/webauthn.login'), false);
    assert.deepEqual(login.redirects, []);
});

test('an unsupported Android provider falls back to browser WebAuthn', async () => {
    const login = createLogin();
    await login.context.handleBiometricLogin();
    login.select('device_lock');
    login.reply({ id: login.nativeRequests[0].id, error: 'NotSupportedError' });
    await settle();

    assert.equal(login.browserRequests.length, 1);
    assert.equal(login.browserRequests[0].publicKey.hints[0], 'client-device');
    assert.deepEqual(Array.from(login.browserRequests[0].publicKey.allowCredentials[0].id), [1, 2, 3]);
});

test('without the Android bridge a local request still uses browser WebAuthn', async () => {
    const login = createLogin({ native: false });
    await login.context.handleBiometricLogin();
    login.select('device_lock');
    await settle();

    assert.equal(login.browserRequests.length, 1);
    assert.equal(login.browserRequests[0].publicKey.hints[0], 'client-device');
    assert.deepEqual(Array.from(login.browserRequests[0].publicKey.allowCredentials[0].id), [1, 2, 3]);
});
