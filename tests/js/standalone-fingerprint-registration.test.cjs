const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const script = fs.readFileSync(path.join(__dirname, '../../resources/views/partials/device-biometric-registration-script.blade.php'), 'utf8')
    .replace(/\{\{ route\("([^"]+)"\) \}\}/g, '/$1')
    .replaceAll('{{ csrf_token() }}', 'test-token');

function mount({ platformCheck = async () => true, createCredential } = {}) {
    const elements = new Map(['startBioBtn', 'bioIdleView', 'bioScanningView', 'bioErrorView', 'bioSuccessView']
        .map(id => [id, { textContent: id === 'startBioBtn' ? 'Register fingerprint' : '', style: { display: 'none' } }]));
    const stages = [1, 2, 3].map(step => ({
        dataset: { bioStep: String(step) }, attributes: {},
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
    }));
    const prompts = [];
    const requests = [];
    const publicKeyCredential = { isUserVerifyingPlatformAuthenticatorAvailable: platformCheck };
    const context = vm.createContext({
        AbortController, clearInterval, atob, btoa, console,
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
        document: { getElementById: id => elements.get(id), querySelector: () => null, querySelectorAll: selector => selector === '[data-bio-step]' ? stages : [] },
        fetch: async (url, options) => {
            requests.push({ url, options });
            if (url.startsWith('/webauthn.register.options')) {
                return { ok: true, json: async () => ({ challenge: 'AQ', user: { id: 'Ag', name: 'test', displayName: 'Test' } }) };
            }
            if (url === '/webauthn.register') return { ok: true, json: async () => ({ success: true }) };
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
    assert.equal(app.stages[0].dataset.complete, 'true');
    assert.equal(app.stages[1].dataset.complete, 'true');
    assert.equal(app.stages[2].attributes['aria-current'], 'step');
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
