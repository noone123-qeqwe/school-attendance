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

function createLogin({ native = true, account = identifier, setupReply = [401, { success: false, message: 'Incorrect password' }] } = {}) {
    const bridge = new EventTarget();
    const nativeRequests = [];
    const browserRequests = [];
    const browserCreateRequests = [];
    const requests = [];
    const redirects = [];
    const password = { value: '' };
    const modalIdentifier = { value: '' };
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
            getElementById(id) {
                if (id === 'bioModalPasswordInput') return password;
                if (id === 'bioModalIdentifierInput') return modalIdentifier;
                if (id === 'bioFaceCaptureCanvas') return { width: 0 };
                return null;
            },
            querySelector: () => null
        },
        idInput: { value: account },
        location: { hostname: 'attendance.example.test', replace: url => redirects.push(url) },
        getOrCreateDeviceKey: () => 'test-device',
        navigator: {
            mediaDevices: {
                async getUserMedia() {
                    throw new DOMException('Camera unavailable in unit stub', 'NotFoundError');
                }
            },
            credentials: {
                async get(options) {
                    browserRequests.push(options);
                    throw new DOMException('No passkey used', 'NotAllowedError');
                },
                async create(options) {
                    browserCreateRequests.push(options);
                    throw new DOMException('Not created', 'NotAllowedError');
                }
            }
        },
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
                    face_login_enabled: true,
                    has_account_credentials: Boolean(body.identifier),
                    allowCredentials: body.identifier ? [{ type: 'public-key', id: credentialId, transports: ['internal', 'hybrid'] }] : []
                }],
                '/webauthn.login': [200, { success: true, redirect: '/dashboard' }],
                '/webauthn.setup.options': setupReply,
                '/webauthn.setup.register': [200, { success: true, redirect: '/dashboard' }]
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
        context, nativeRequests, browserRequests, browserCreateRequests, requests, redirects, password, modalIdentifier,
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

test('Android local fingerprint sign-in restricts transports to internal and submits assertion', async () => {
    const login = createLogin();
    await login.context.handleBiometricLogin();
    assert.equal(login.requests[0].body.identifier, identifier);
    assert.deepEqual(
        Array.from(login.modal.methods, method => method.id),
        ['fingerprint', 'face', 'password']
    );
    assert.equal(login.modal.methods[0].name, 'Fingerprint verification');

    login.select('fingerprint');
    await settle();
    assert.equal(login.nativeRequests.length, 1);
    const request = login.nativeRequests[0];
    assert.equal(request.type, 'get');
    assert.equal(request.publicKey.challenge, 'DA0');
    assert.equal(request.publicKey.rpId, 'attendance.example.test');
    assert.equal(request.publicKey.userVerification, 'required');
    assert.equal(request.publicKey.allowCredentials[0].id, credentialId);
    assert.deepEqual(request.publicKey.allowCredentials[0].transports, ['internal']);
    assert.equal(login.browserRequests.length, 0);
    assert.deepEqual(login.redirects, []);

    login.reply({ id: request.id, assertion: signedAssertion });
    await settle();
    const submitted = login.requests.find(({ url }) => url === '/webauthn.login');
    assert.equal(submitted.body.identifier, identifier);
    assert.equal(submitted.body.biometric_method, 'fingerprint');
    assert.equal(submitted.body.credential_id, credentialId);
    assert.deepEqual(submitted.body.assertion, {
        id: signedAssertion.id,
        type: signedAssertion.type,
        response: signedAssertion.response
    });
    assert.deepEqual(login.redirects, ['/dashboard']);
});

test('a local Android miss falls back to face verification or password without cross-device or setup cards', async () => {
    const login = createLogin();
    await login.context.handleBiometricLogin();
    login.select('fingerprint');
    await settle();
    login.reply({ id: login.nativeRequests[0].id, error: 'NotFoundError' });
    await settle();

    assert.deepEqual(
        Array.from(login.modal.methods, method => method.id),
        ['face', 'password']
    );
});

test('Android setup sends create message with residentKey required and completes registration', async () => {
    const login = createLogin({
        setupReply: [200, {
            success: true,
            challenge: 'DA0',
            rp: { id: 'attendance.example.test', name: 'School Attendance' },
            user: { id: 'AQID', name: identifier, displayName: 'Student' },
            timeout: 45000,
            excludeCredentials: []
        }]
    });
    const regPromise = login.context.startBiometricRegistration(identifier, 'fixture-password');
    await settle();
    await settle();
    assert.equal(login.modal.title, 'Confirm on your device');
    login.modal.onPrimaryClick();
    await settle();

    const createReq = login.nativeRequests.find(req => req.type === 'create');
    assert.ok(createReq, 'Expected Android bridge create request');
    assert.equal(createReq.publicKey.authenticatorSelection.residentKey, 'required');
    assert.equal(createReq.publicKey.authenticatorSelection.authenticatorAttachment, 'platform');

    login.reply({
        id: createReq.id,
        credential: {
            id: credentialId,
            rawId: credentialId,
            type: 'public-key',
            response: {
                clientDataJSON: 'BAU',
                attestationObject: 'Bgc',
                transports: ['internal']
            }
        }
    });
    await regPromise;
    const saved = login.requests.find(({ url }) => url === '/webauthn.setup.register');
    assert.ok(saved, 'Expected setup registration submission');
    assert.equal(saved.body.biometric_type, 'fingerprint');
    assert.equal(saved.body.credential_id, credentialId);
});

test('selecting Face verification without an identifier prompts for account details inline', async () => {
    const login = createLogin({ account: '' });
    await login.context.handleBiometricLogin();
    login.select('face');
    await settle();

    assert.equal(login.modal.title, 'Face verification');
    assert.equal(login.modal.showIdentifier, true);
    assert.equal(login.nativeRequests.length, 0);
    assert.equal(login.browserRequests.length, 0);
});

test('a late Android assertion after cancellation cannot complete sign-in', async () => {
    const login = createLogin();
    await login.context.handleBiometricLogin();
    login.select('fingerprint');
    await settle();

    const request = login.nativeRequests[0];
    login.context.closeBiometricModal();
    assert.deepEqual(login.nativeRequests[1], { type: 'cancel', id: request.id });
    login.reply({ id: request.id, assertion: signedAssertion });
    await settle();

    assert.equal(login.requests.some(({ url }) => url === '/webauthn.login'), false);
    assert.deepEqual(login.redirects, []);
});

test('an unsupported Android provider falls back to browser WebAuthn with internal transport', async () => {
    const login = createLogin();
    await login.context.handleBiometricLogin();
    login.select('fingerprint');
    login.reply({ id: login.nativeRequests[0].id, error: 'NotSupportedError' });
    await settle();

    assert.equal(login.browserRequests.length, 1);
    assert.equal(login.browserRequests[0].publicKey.hints[0], 'client-device');
    assert.deepEqual(Array.from(login.browserRequests[0].publicKey.allowCredentials[0].id), [1, 2, 3]);
    assert.deepEqual(Array.from(login.browserRequests[0].publicKey.allowCredentials[0].transports), ['internal']);
});

test('without the Android bridge a local fingerprint request strips hybrid transport in browser WebAuthn', async () => {
    const login = createLogin({ native: false });
    await login.context.handleBiometricLogin();
    login.select('fingerprint');
    await settle();

    assert.equal(login.browserRequests.length, 1);
    assert.equal(login.browserRequests[0].publicKey.hints[0], 'client-device');
    assert.deepEqual(Array.from(login.browserRequests[0].publicKey.allowCredentials[0].id), [1, 2, 3]);
    assert.deepEqual(Array.from(login.browserRequests[0].publicKey.allowCredentials[0].transports), ['internal']);
});
