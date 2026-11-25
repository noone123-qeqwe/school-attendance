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

function createLogin({
    native = true,
    account = identifier,
    androidBridgeExtra = null,
    setupReply = [401, { success: false, message: 'Incorrect password' }],
    availableMethodsReply = [200, { success: true, discoverable: true, available_methods: ['fingerprint', 'face'], face_login_enabled: true }],
    loginOptionsReply = null,
    faceOptionsReply = [404, { success: false, code: 'FACE_LOGIN_UNAVAILABLE', message: 'No registered face profile was found for those sign-in details.' }]
} = {}) {
    const bridge = new EventTarget();
    const nativeRequests = [];
    const browserRequests = [];
    const browserCreateRequests = [];
    const cameraRequests = [];
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
        AndroidDeviceBridge: androidBridgeExtra || undefined,
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
                async getUserMedia(constraints) {
                    cameraRequests.push(constraints);
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

            const defaultLoginOptions = [200, {
                success: true,
                challenge: 'DA0',
                rpId: 'attendance.example.test',
                hints: ['client-device'],
                timeout: 45000,
                face_login_enabled: true,
                has_account_credentials: Boolean(body.identifier),
                allowCredentials: body.identifier ? [{ type: 'public-key', id: credentialId, transports: ['internal', 'hybrid'] }] : []
            }];

            const replies = {
                '/webauthn.available.methods': () => availableMethodsReply,
                '/webauthn.login.options': () => (typeof loginOptionsReply === 'function'
                    ? loginOptionsReply(body)
                    : (loginOptionsReply || defaultLoginOptions)),
                '/webauthn.login': () => [200, { success: true, redirect: '/dashboard' }],
                '/webauthn.face.options': () => faceOptionsReply,
                '/webauthn.setup.options': () => setupReply,
                '/webauthn.setup.register': () => [200, { success: true, redirect: '/dashboard' }]
            };
            assert.ok(replies[url], `Unexpected request to ${url}`);
            const [status, data] = replies[url]();
            return { ok: status < 400, status, text: async () => JSON.stringify(data) };
        }
    });
    context.window = context;
    vm.runInContext(fs.readFileSync(path.resolve(__dirname, '../../public/js/device-passkey-capabilities.js'), 'utf8'), context);
    context.DevicePasskeys.check = async () => ({ available: true, platformAvailable: true });
    vm.runInContext(loginScript, context);
    context.openBiometricModal = config => { modal = config; };

    return {
        context, nativeRequests, browserRequests, browserCreateRequests, cameraRequests, requests, redirects, password, modalIdentifier,
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

test('browser verification waits for a fresh tap when account lookup consumes activation', async () => {
    const login = createLogin({ native: false });
    login.context.navigator.userActivation = { isActive: false };
    await login.context.startFingerprintWithIdentifier(identifier);
    assert.equal(login.browserRequests.length, 0);
    assert.equal(login.modal.primaryBtnText, 'Scan fingerprint');
    login.context.navigator.userActivation.isActive = true;
    login.modal.onPrimaryClick();
    await settle();
    assert.equal(login.browserRequests.length, 1);
});

test('Android fingerprint sign-in checks DB first, targets internal platform authenticator, and submits assertion', async () => {
    const login = createLogin();
    await login.context.handleBiometricLogin();
    // Chooser loads independent method availability without issuing a WebAuthn challenge yet.
    assert.equal(login.requests.length, 1);
    assert.equal(login.requests[0].url, '/webauthn.available.methods');
    assert.deepEqual(
        Array.from(login.modal.methods, method => method.id),
        ['fingerprint', 'face', 'password']
    );
    assert.equal(login.modal.methods[0].name, 'Fingerprint verification');

    login.select('fingerprint');
    await settle();
    await settle();
    const optsReq = login.requests.find(({ url }) => url === '/webauthn.login.options');
    assert.ok(optsReq, 'Expected database-first login-options lookup');
    assert.equal(optsReq.body.identifier, identifier);
    assert.equal(login.nativeRequests.length, 1);
    const request = login.nativeRequests[0];
    assert.equal(request.type, 'get');
    assert.equal(request.publicKey.challenge, 'DA0');
    assert.equal(request.publicKey.rpId, 'attendance.example.test');
    assert.equal(request.publicKey.userVerification, 'required');
    assert.deepEqual(request.publicKey.hints, ['client-device']);
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

test('a local Android miss offers in-modal device registration, face verification, and password', async () => {
    const login = createLogin();
    await login.context.handleBiometricLogin();
    login.select('fingerprint');
    await settle();
    await settle();
    login.reply({ id: login.nativeRequests[0].id, error: 'NotFoundError' });
    await settle();

    assert.deepEqual(
        Array.from(login.modal.methods, method => method.id),
        ['setup_device', 'face', 'password']
    );
    assert.equal(login.modal.message.includes('registered on another browser or device'), false);
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
    assert.equal(login.browserCreateRequests.length, 0);

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
    await settle();

    const request = login.nativeRequests[0];
    login.context.closeBiometricModal();
    assert.deepEqual(login.nativeRequests[1], { type: 'cancel', id: request.id });
    login.reply({ id: request.id, assertion: signedAssertion });
    await settle();

    assert.equal(login.requests.some(({ url }) => url === '/webauthn.login'), false);
    assert.deepEqual(login.redirects, []);
});

test('an unsupported Android provider falls back to browser WebAuthn targeting internal authenticator', async () => {
    const login = createLogin();
    await login.context.handleBiometricLogin();
    login.select('fingerprint');
    await settle();
    await settle();
    login.reply({ id: login.nativeRequests[0].id, error: 'NotSupportedError' });
    await settle();

    assert.equal(login.browserRequests.length, 1);
    assert.equal(login.browserRequests[0].publicKey.hints[0], 'client-device');
    assert.deepEqual(Array.from(login.browserRequests[0].publicKey.allowCredentials[0].id), [1, 2, 3]);
    assert.deepEqual(Array.from(login.browserRequests[0].publicKey.allowCredentials[0].transports), ['internal']);
});

test('without the Android bridge browser WebAuthn targets internal platform authenticator to avoid NFC/USB chooser', async () => {
    const login = createLogin({ native: false });
    await login.context.handleBiometricLogin();
    login.select('fingerprint');
    await settle();
    await settle();

    assert.equal(login.browserRequests.length, 1);
    assert.equal(login.browserRequests[0].publicKey.hints[0], 'client-device');
    assert.deepEqual(Array.from(login.browserRequests[0].publicKey.allowCredentials[0].id), [1, 2, 3]);
    assert.deepEqual(Array.from(login.browserRequests[0].publicKey.allowCredentials[0].transports), ['internal']);
});

test('fingerprint sign-in without an identifier requests a discoverable local key', async () => {
    const login = createLogin({
        native: true,
        account: '',
        androidBridgeExtra: { isNativeApp: () => true, hasLocalPasskey: () => true }
    });
    await login.context.handleBiometricLogin();
    login.select('fingerprint');
    await settle();

    assert.equal(login.requests.find(({url}) => url === '/webauthn.login.options').body.identifier, '');
    assert.deepEqual(login.nativeRequests[0].publicKey.allowCredentials, []);
    login.reply({ id: login.nativeRequests[0].id, assertion: signedAssertion });
    await settle();
    assert.equal(login.requests.find(({url}) => url === '/webauthn.login').body.identifier, '');
    assert.deepEqual(login.redirects, ['/dashboard']);
});

test('a discoverable miss offers targeted verification for older non-resident credentials', async () => {
    const login = createLogin({ account: '', native: false });
    await login.context.handleBiometricLogin();
    login.select('fingerprint');
    await settle();
    assert.equal(login.browserRequests[0].publicKey.allowCredentials.length, 0);
    login.select('account');
    assert.equal(login.modal.showIdentifier, true);
    login.modalIdentifier.value = identifier;
    login.modal.onPrimaryClick();
    await settle();
    assert.equal(login.browserRequests[1].publicKey.allowCredentials.length, 1);
});

test('native Android APK does not fall back to WebView WebAuthn on NotSupportedError', async () => {
    const login = createLogin({
        androidBridgeExtra: { isNativeApp: () => true, hasLocalPasskey: () => false }
    });
    await login.context.handleBiometricLogin();
    login.select('fingerprint');
    await settle();
    await settle();
    login.reply({ id: login.nativeRequests[0].id, error: 'NotSupportedError' });
    await settle();

    assert.equal(login.browserRequests.length, 0);
    assert.equal(login.modal.title, 'Fingerprint verification unavailable');
});

test('fingerprint lookup with no DB credentials never invokes device prompt and offers Face and Password fallbacks', async () => {
    const login = createLogin({
        loginOptionsReply: [404, {
            success: false,
            code: 'BIOMETRIC_UNAVAILABLE',
            message: 'No passkey was found for those sign-in details.',
            face_login_enabled: true
        }]
    });
    await login.context.handleBiometricLogin();
    login.select('fingerprint');
    await settle();
    await settle();

    assert.equal(login.nativeRequests.length, 0);
    assert.equal(login.browserRequests.length, 0);
    assert.equal(login.modal.title, 'No fingerprint passkey registered');
    assert.deepEqual(
        Array.from(login.modal.methods, method => method.id),
        ['setup_device', 'face', 'password']
    );
});

test('the APK selects the provider that owns the matching fingerprint credential', async () => {
    for (const matches of [true, false]) {
        const login = createLogin({
            androidBridgeExtra: {
                isNativeApp: () => true,
                hasLocalPasskey: () => true,
                isBiometricAvailable: () => true,
                matchesLocalPasskey: json => {
                    assert.equal(JSON.parse(json)[0].id, credentialId);
                    return matches;
                }
            }
        });
        await login.context.handleBiometricLogin();
        login.select('fingerprint');
        await settle();
        if (matches) {
            assert.equal(login.nativeRequests[0].publicKey.allowCredentials[0].id, credentialId);
            assert.equal(login.browserRequests.length, 0);
        } else {
            assert.deepEqual(Array.from(login.browserRequests[0].publicKey.allowCredentials[0].id), [1, 2, 3]);
            assert.equal(login.nativeRequests.length, 0);
        }
    }
});

test('retrying fingerprint after cancellation fetches a fresh database challenge instead of reusing stale options', async () => {
    let optionsCount = 0;
    const login = createLogin({
        loginOptionsReply: () => {
            optionsCount += 1;
            return [200, {
                success: true,
                challenge: 'CHALLENGE_' + optionsCount,
                rpId: 'attendance.example.test',
                hints: ['client-device'],
                timeout: 45000,
                face_login_enabled: true,
                has_account_credentials: true,
                allowCredentials: [{ type: 'public-key', id: credentialId, transports: ['internal'] }]
            }];
        }
    });
    await login.context.handleBiometricLogin();
    login.select('fingerprint');
    await settle();
    await settle();

    assert.equal(login.nativeRequests[0].publicKey.challenge, 'CHALLENGE_1');
    login.reply({ id: login.nativeRequests[0].id, error: 'NotAllowedError' });
    await settle();

    login.select('retry_local');
    await settle();
    await settle();

    assert.equal(optionsCount, 2);
    assert.equal(login.nativeRequests[1].publicKey.challenge, 'CHALLENGE_2');
});

test('Face verification checks database registration FIRST and never opens camera when unregistered', async () => {
    const login = createLogin({
        faceOptionsReply: [404, {
            success: false,
            code: 'FACE_LOGIN_UNAVAILABLE',
            message: 'No registered face profile was found for those sign-in details.'
        }]
    });
    await login.context.handleBiometricLogin();
    login.select('face');
    await settle();
    await settle();

    assert.equal(login.requests.some(({ url }) => url === '/webauthn.face.options'), true);
    assert.equal(login.requests.some(({ url }) => url === '/webauthn.login.options'), false);
    assert.equal(login.cameraRequests.length, 0);
    assert.equal(login.modal.title, 'Face verification unavailable');
    assert.equal(login.modal.primaryBtnText, 'Use fingerprint');
});

test('Face verification opens camera capture only AFTER database confirms registered face profile', async () => {
    const login = createLogin({
        faceOptionsReply: [200, {
            success: true,
            face_challenge: {
                id: 'a'.repeat(64),
                poses: ['front', 'left', 'right', 'up', 'down'],
                expires_in: 70
            }
        }]
    });
    // Provide faceGuidanceUrl dataset on modal element for this test
    login.context.document.getElementById = id => {
        if (id === 'biometricModal') return { toggleAttribute() {}, dataset: { faceGuidanceUrl: '/js/guidance.js' } };
        if (id === 'bioFaceCaptureCanvas') return { width: 0 };
        return null;
    };

    await login.context.handleBiometricLogin();
    login.select('face');
    await settle();
    await settle();

    assert.equal(login.requests.some(({ url }) => url === '/webauthn.face.options'), true);
    assert.equal(login.cameraRequests.length, 1);
});
