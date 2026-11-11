const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const script = fs.readFileSync(path.join(__dirname, '../../public/js/student-settings.js'), 'utf8');

function mount(mobile = true) {
    const listeners = {}, rootListeners = {}, documentListeners = {};
    const ids = new Map(), storage = new Map();
    const redirects = [];
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
    for (const id of ['studentSettingsPageTitle', 'studentSettingsPageDescription', 'studentSettingsFilter', 'studentSettingsNoResults']) element(id);
    element('studentFaceRegistrationLink').href = '/settings/biometrics/face';
    element('studentFingerprintRegistrationLink').href = '/settings/biometrics/fingerprint';
    const root = element('root');
    root.dataset = { studentSettings: '7', studentView: 'overview' };
    root.addEventListener = (event, fn) => { rootListeners[event] = fn; };
    root.querySelectorAll = selector => ({
        '[data-settings-page]': buttons,
        '.student-settings-menu-group': [menuGroup],
        '.spanel': panels,
        '.sec-group-panel': groups,
    }[selector] || []);
    const location = { pathname: '/settings', search: '', hash: '', replace(url) { redirects.push(url); } };
    const history = {
        pushState(a, b, url) { location.hash = url.startsWith('#') ? url : ''; },
        replaceState(a, b, url) { location.hash = url.startsWith('#') ? url : ''; },
    };
    const window = {
        matchMedia: () => ({ matches: mobile, addEventListener() {} }),
        addEventListener: (event, fn) => { listeners[event] = fn; },
    };
    vm.runInNewContext(script, {
        window, location, history,
        document: { querySelector: () => root, getElementById: id => ids.get(id), addEventListener: (event, fn) => { documentListeners[event] = fn; } },
        sessionStorage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value), removeItem: key => storage.delete(key) },
        HTMLFormElement: class {},
    });
    return { window, root, ids, buttons, location, listeners, rootListeners, documentListeners, storage, redirects };
}

for (const [screen, mobile] of [['mobile', true], ['desktop', false]]) {
    test(`${screen}: Biometrics opens its menu and leaves registration links to the browser`, () => {
        const app = mount(mobile);
        app.window.studentSettingsNavigate('biometrics', { push: true, focus: true });
        assert.equal(app.ids.get('sec-section-biometrics').style.display, 'block');
        assert.equal(app.ids.get('studentSettingsPageTitle').textContent, 'Biometrics');
        assert.equal(app.buttons[1].getAttribute('aria-current'), 'page');
        assert.equal(app.location.hash, '#tab-biometrics');
        const link = app.ids.get('studentFaceRegistrationLink');
        app.rootListeners.click({
            target: { closest: selector => selector.includes('[data-biometric-page]') ? link : null },
            preventDefault() { assert.fail('A real link should be handled by the browser'); },
        });
    });
}

test('old biometric bookmarks replace the Settings URL with the correct registration page', () => {
    const app = mount();
    for (const [hash, method] of [['#tab-face', 'face'], ['#tab-fingerprint', 'fingerprint'], ['#passkeys', 'fingerprint'], ['#cameraFaceEnrollment', 'face']]) {
        app.location.hash = hash;
        app.listeners.hashchange();
        assert.equal(app.redirects.at(-1), '/settings/biometrics/' + method);
    }
    assert.equal(app.root.dataset.studentView, 'overview');
});

test('form feedback still reopens the originating Settings panel', () => {
    const app = mount();
    app.storage.set('student_settings_return_7', 'profile');
    app.root.dataset.settingsFeedback = 'true';
    app.documentListeners.DOMContentLoaded();
    assert.equal(app.root.dataset.studentView, 'profile');
    assert.deepEqual(app.redirects, []);
});
