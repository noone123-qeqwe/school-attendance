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
            id, textContent, hidden: false, style: {}, dataset: {}, value: '', children: [],
            classList: {
                toggle(name, on) { if (on) classes.add(name); else classes.delete(name); },
                contains: name => classes.has(name),
            },
            setAttribute: (name, value) => attributes.set(name, value),
            removeAttribute: name => attributes.delete(name),
            getAttribute: name => attributes.get(name),
            addEventListener() {}, appendChild(child) { this.children.push(child); return child; }, focus() {}, scrollIntoView() {},
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
    const panels = ['profile', 'security', 'account-email', 'account-password', 'account-phone'].map(key => element('tab-' + key));
    const groups = ['biometrics', 'credentials', 'device', 'recovery'].map(key => element('sec-section-' + key));
    const menuGroup = { querySelectorAll: () => buttons };
    for (const id of [
        'studentSettingsPageTitle', 'studentSettingsPageDescription', 'studentSettingsFilter', 'studentSettingsNoResults',
        'emailStep1', 'emailStep2', 'otpStep1', 'otpStep2', 'emailEditProgress', 'passwordEditProgress',
        'emailFormStatus', 'passwordFormStatus', 'phoneFormStatus', 'inputNewEmail', 'ed1', 'sd1', 'phoneInput',
    ]) element(id);
    element('studentFaceRegistrationLink').href = '/settings/biometrics/face';
    element('studentFingerprintRegistrationLink').href = '/settings/biometrics/fingerprint';
    const root = element('root');
    root.dataset = { studentSettings: '7', studentView: 'overview' };
    const sources = new Map(['email', 'password', 'phone'].map(name => {
        const source = element('source-' + name);
        source.dataset.accountEditSource = name;
        return [name, source];
    }));
    for (const id of ['accountEmailFormSlot', 'accountPasswordFormSlot', 'accountPhoneFormSlot', 'studentCredentialFormGrid', 'studentCredentialShortcuts', 'studentPhoneShortcut']) element(id);
    ids.get('studentCredentialShortcuts').hidden = true;
    ids.get('studentPhoneShortcut').hidden = true;
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
        document: {
            querySelector: selector => selector === '[data-student-settings]' ? root : selector.match(/data-account-edit-source="(email|password|phone)"/) ? sources.get(selector.match(/data-account-edit-source="(email|password|phone)"/)[1]) : null,
            getElementById: id => ids.get(id),
            addEventListener: (event, fn) => { documentListeners[event] = fn; },
        },
        sessionStorage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value), removeItem: key => storage.delete(key) },
        HTMLFormElement: class {},
        setAccountEditProgress: (id, step) => { ids.get(id).dataset.currentStep = String(step); },
        setAccountEditStatus: (id, message, tone) => {
            const status = ids.get(id);
            status.textContent = message;
            status.dataset.tone = tone;
            status.hidden = !message;
        },
    });
    return { window, root, ids, buttons, location, listeners, rootListeners, documentListeners, storage, redirects };
}

test('student account edits move to focused, separate screens', () => {
    const app = mount(true);
    for (const [name, slot] of [['email', 'accountEmailFormSlot'], ['password', 'accountPasswordFormSlot'], ['phone', 'accountPhoneFormSlot']]) {
        assert.equal(app.ids.get(slot).children[0], app.ids.get('source-' + name));
    }
    assert.equal(app.ids.get('studentCredentialFormGrid').hidden, true);
    assert.equal(app.ids.get('studentCredentialShortcuts').hidden, false);
    assert.equal(app.ids.get('studentPhoneShortcut').hidden, false);

    const emailAction = {
        dataset: { accountPage: 'change-email' },
        closest: selector => selector.includes('[data-account-page]') ? emailAction : null,
    };
    app.rootListeners.click({ target: emailAction, preventDefault() {} });
    assert.equal(app.root.dataset.studentView, 'change-email');
    assert.equal(app.ids.get('studentSettingsPageTitle').textContent, 'Change email address');
    assert.equal(app.ids.get('tab-account-email').classList.contains('active'), true);
    assert.equal(app.location.hash, '#tab-change-email');
    app.window.studentSettingsRememberReturn();
    assert.equal(app.storage.get('student_settings_return_7'), 'change-email');
});

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

test('email verification errors return to the code step with a clear inline message', () => {
    const app = mount();
    app.storage.set('student_settings_return_7', 'change-email');
    Object.assign(app.root.dataset, {
        settingsFeedback: 'true',
        settingsEmailError: 'true',
        settingsEmailAddressError: 'false',
        settingsEmailErrorMessage: 'The verification code has expired.',
    });

    app.documentListeners.DOMContentLoaded();

    assert.equal(app.root.dataset.studentView, 'change-email');
    assert.equal(app.ids.get('emailStep1').style.display, 'none');
    assert.equal(app.ids.get('emailStep2').style.display, 'block');
    assert.equal(app.ids.get('emailEditProgress').dataset.currentStep, '2');
    assert.equal(app.ids.get('emailFormStatus').textContent, 'The verification code has expired.');
    assert.equal(app.ids.get('emailFormStatus').dataset.tone, 'error');
});
