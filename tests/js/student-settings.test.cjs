const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const script = fs.readFileSync(path.join(__dirname, '../../public/js/student-settings.js'), 'utf8');

function mount(mobile = true) {
    const listeners = {}, rootListeners = {}, documentListeners = {};
    const ids = new Map(), storage = new Map();
    let deviceLoads = 0, optionLoads = 0, cameraCancels = 0;
    const element = (id, textContent = '') => {
        const classes = new Set(), attributes = new Map();
        const el = {
            id, textContent, hidden: false, style: {}, dataset: {}, value: '',
            classList: {
                toggle(name, on) { if (on) classes.add(name); else classes.delete(name); },
                contains: name => classes.has(name),
            },
            setAttribute: (name, value) => attributes.set(name, value),
            removeAttribute: name => attributes.delete(name),
            getAttribute: name => attributes.get(name),
            addEventListener() {}, focus() {}, scrollIntoView() {},
        };
        ids.set(id, el);
        return el;
    };
    const buttons = [
        ['profile', 'Profile & school details'],
        ['biometrics', 'Biometrics face registration camera passkeys fingerprint credentials'],
        ['credentials', 'Password & email'],
    ].map(([page, text]) => {
        const button = element('button-' + page, text);
        button.dataset.settingsPage = page;
        button.dataset.settingsKeywords = '';
        return button;
    });
    const panels = ['profile', 'security'].map(key => element('tab-' + key));
    const groups = ['biometrics', 'credentials', 'device', 'recovery'].map(key => element('sec-section-' + key));
    const menuGroup = { querySelectorAll: () => buttons };
    for (const id of ['studentSettingsPageTitle', 'studentSettingsPageDescription', 'studentSettingsFilter', 'studentSettingsNoResults', 'cameraFaceEnrollment', 'studentPasskeySettings']) element(id);
    element('cameraFaceCancelBtn').click = () => { cameraCancels++; };
    const root = element('root');
    root.dataset = { studentSettings: '7', studentView: 'overview' };
    root.addEventListener = (event, fn) => { rootListeners[event] = fn; };
    root.querySelectorAll = selector => ({
        '[data-settings-page]': buttons,
        '.student-settings-menu-group': [menuGroup],
        '.spanel': panels,
        '.sec-group-panel': groups,
    }[selector] || []);
    const location = { pathname: '/settings', search: '', hash: '' };
    const history = {
        pushState(a, b, url) { location.hash = url.startsWith('#') ? url : ''; },
        replaceState(a, b, url) { location.hash = url.startsWith('#') ? url : ''; },
    };
    const window = {
        matchMedia: () => ({ matches: mobile, addEventListener() {} }),
        addEventListener: (event, fn) => { listeners[event] = fn; },
        loadDevices: () => { deviceLoads++; },
        prefetchWebAuthn: () => { optionLoads++; },
    };
    vm.runInNewContext(script, {
        window, location, history,
        document: { querySelector: () => root, getElementById: id => ids.get(id), addEventListener: (event, fn) => { documentListeners[event] = fn; } },
        sessionStorage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value), removeItem: key => storage.delete(key) },
        HTMLFormElement: class {},
    });
    return { window, root, ids, buttons, location, listeners, rootListeners, documentListeners, storage,
        counts: () => ({ deviceLoads, optionLoads, cameraCancels }) };
}

for (const [screen, mobile] of [['mobile', true], ['desktop', false]]) {
    test(`${screen}: the combined settings page shows both camera enrollment and device sign-in`, () => {
        const app = mount(mobile);
        app.window.studentSettingsNavigate('biometrics', { push: true, focus: true });
        assert.equal(app.root.dataset.studentView, 'biometrics');
        assert.equal(app.ids.get('cameraFaceEnrollment').hidden, false);
        assert.equal(app.ids.get('studentPasskeySettings').hidden, false);
        assert.equal(app.ids.get('tab-security').classList.contains('active'), true);
        assert.equal(app.ids.get('sec-section-credentials').style.display, 'none');
        assert.equal(app.ids.get('studentSettingsPageTitle').textContent, 'Biometrics');
        assert.equal(app.buttons[1].getAttribute('aria-current'), 'page');
        assert.equal(app.location.hash, '#tab-biometrics');
    });
}

test('old face and passkey links reach the combined page and load credentials only once', () => {
    const app = mount();
    for (const alias of ['face', 'passkeys', 'fingerprint', 'security-biometrics', 'cameraFaceEnrollment']) {
        app.window.studentSettingsNavigate(alias);
        assert.equal(app.root.dataset.studentView, 'biometrics');
        assert.equal(app.ids.get('cameraFaceEnrollment').hidden, false);
        assert.equal(app.ids.get('studentPasskeySettings').hidden, false);
    }
    assert.deepEqual(app.counts(), { deviceLoads: 1, optionLoads: 1, cameraCancels: 0 });
});

test('leaving combined settings stops camera capture and hides both sections', () => {
    const app = mount();
    app.window.studentSettingsNavigate('biometrics');
    app.window.studentSettingsNavigate('profile');
    assert.equal(app.counts().cameraCancels, 1);
    assert.equal(app.ids.get('cameraFaceEnrollment').hidden, true);
    assert.equal(app.ids.get('studentPasskeySettings').hidden, true);
});

test('browser history and saved form returns support the old face settings link', () => {
    const app = mount();
    app.location.hash = '#tab-face';
    app.listeners.hashchange();
    assert.equal(app.root.dataset.studentView, 'biometrics');
    app.window.studentSettingsNavigate('profile');
    app.storage.set('student_settings_return_7', 'passkeys');
    app.root.dataset.settingsFeedback = 'true';
    app.documentListeners.DOMContentLoaded();
    assert.equal(app.root.dataset.studentView, 'biometrics');
});
