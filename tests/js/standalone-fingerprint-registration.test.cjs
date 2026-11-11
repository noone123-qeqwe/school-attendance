const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const script = fs.readFileSync(path.join(__dirname, '../../resources/views/partials/device-biometric-registration-script.blade.php'), 'utf8')
    .replace(/\{\{ route\("([^"]+)"\) \}\}/g, '/$1')
    .replaceAll('{{ csrf_token() }}', 'test-token');

function fakeElement() {
    const element = {
        textContent: '', style: { display: 'none' }, dataset: {}, attributes: {}, listeners: {}, children: [],
        appendChild(child) { this.children.push(child); return child; },
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
        addEventListener(name, callback) { this.listeners[name] = callback; },
        focus() {},
        querySelector() { return null; },
    };
    Object.defineProperty(element, 'innerHTML', {
        get() { return this._innerHTML || ''; },
        set(value) { this._innerHTML = value; this.children = []; },
    });
    return element;
}

function mount({ platformCheck = async () => true, createCredential, deviceFetch } = {}) {
    const ids = ['startBioBtn', 'bioIdleView', 'bioScanningView', 'fpScanningTitle', 'bioErrorView', 'bioErrorTitle', 'bioSuccessView', 'bioSuccessTitle', 'deviceList', 'deviceCountBadge', 'statusBadgeFingerprint', 'statusBadgeFace'];
    const elements = new Map(ids.map(id => [id, fakeElement()]));
    elements.get('startBioBtn').textContent = 'Register fingerprint';
    const stages = [1, 2, 3].map(step => ({
        dataset: { bioStep: String(step) }, attributes: {},
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
    }));
    const prompts = [];
    const requests = [];
    const publicKeyCredential = { isUserVerifyingPlatformAuthenticatorAvailable: platformCheck };
    const context = vm.createContext({
        AbortController, clearInterval, atob, btoa, console: { error() {}, warn() {}, log() {} },
        window: { isSecureContext: true, PublicKeyCredential: publicKeyCredential, location: { hostname: 'localhost' } },
        PublicKeyCredential: publicKeyCredential,
        navigator: {
            userAgent: 'Test Browser',
            credentials: { create: async options => {
                prompts.push(options);
                return createCredential?.(options) || {
                    rawId: Uint8Array.of(1), id: 'AQ', type: 'public-key',
                    response: { attestationObject: Uint8Array.of(2), clientDataJSON: Uint8Array.of(3) },
                };
            } },
        },
        document: {
            getElementById: id => elements.get(id),
            createElement: () => fakeElement(),
            querySelector: () => null,
            querySelectorAll: selector => selector === '[data-bio-step]' ? stages : [],
        },
        fetch: async (url, options) => {
            requests.push({ url, options });
            if (url.startsWith('/webauthn.register.options')) {
                return { ok: true, json: async () => ({ challenge: 'AQ', user: { id: 'Ag', name: 'test', displayName: 'Test' } }) };
            }
            if (url === '/webauthn.register') return { ok: true, json: async () => ({ success: true }) };
            if (url === '/webauthn.devices' && deviceFetch) return deviceFetch(url, options);
            return { ok: true, json: async () => [] };
        },
    });
    vm.runInContext(script, context);
    return { context, elements, prompts, requests, stages };
}

test('the dedicated fingerprint screen registers through the on-device prompt', async () => {
    const app = mount();
    await app.context.beginSelectedBiometricRegistration();
    const prompt = app.prompts[0].publicKey;
    assert.equal(prompt.authenticatorSelection.authenticatorAttachment, 'platform');
    assert.equal(prompt.authenticatorSelection.userVerification, 'required');
    const save = app.requests.find(request => request.url === '/webauthn.register');
    assert.equal(save.options.headers['X-CSRF-TOKEN'], 'test-token');
    assert.equal(JSON.parse(save.options.body).biometric_type, 'fingerprint');
    assert.equal(app.elements.get('bioSuccessView').style.display, 'block');
    assert.equal(app.elements.get('bioSuccessTitle').attributes.tabindex, '-1');
    assert.equal(app.elements.get('fpScanningTitle').textContent, 'Device Verified ✓');
    assert.equal(app.stages.every(step => step.dataset.complete === 'true'), true);
    assert.equal(app.stages.every(step => step.attributes['aria-current'] === undefined), true);
});

test('cancelling while device support is being checked prevents a late native prompt', async () => {
    let deviceReady, checkStarted;
    const checking = new Promise(resolve => { checkStarted = resolve; });
    const app = mount({ platformCheck: () => {
        checkStarted();
        return new Promise(resolve => { deviceReady = resolve; });
    } });
    const registration = app.context.beginSelectedBiometricRegistration();
    await checking;
    app.context.cancelBiometricRegistration();
    deviceReady(true);
    await registration;
    assert.equal(app.prompts.length, 0);
    assert.equal(app.requests.some(request => request.url === '/webauthn.register'), false);
    assert.equal(app.elements.get('bioIdleView').style.display, 'block');
    assert.equal(app.stages[0].attributes['aria-current'], 'step');
    assert.equal(app.stages[1].dataset.complete, undefined);
});

test('registered device loading offers an in-page retry and clears the error after recovery', async () => {
    let shouldFail = true;
    const app = mount({ deviceFetch: async () => {
        if (shouldFail) throw new Error('offline');
        return { ok: true, json: async () => [] };
    } });

    await app.context.loadDevices();
    const list = app.elements.get('deviceList');
    assert.equal(list.attributes['aria-busy'], undefined);
    assert.equal(app.elements.get('statusBadgeFingerprint').textContent, 'Unable to check');
    assert.equal(app.elements.get('statusBadgeFingerprint').className, 'bio-status-pill failed');
    assert.equal(app.elements.get('statusBadgeFace').textContent, 'Unable to check');
    assert.equal(list.children[0].getAttribute ? list.children[0].getAttribute('role') : list.children[0].attributes.role, 'alert');
    assert.equal(list.children[0].children[0].textContent.includes('try again'), true);
    const retryButton = list.children[0].children[1];

    shouldFail = false;
    await retryButton.listeners.click();
    assert.equal(app.elements.get('deviceCountBadge').textContent, '0 Registered');
    assert.equal(list.children[0].id, 'noDevices');
});
