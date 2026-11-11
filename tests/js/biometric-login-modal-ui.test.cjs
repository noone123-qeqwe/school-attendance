const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const loginView = fs.readFileSync(path.join(__dirname, '../../resources/views/auth/login.blade.php'), 'utf8');
const functionStart = loginView.indexOf('function openBiometricModal(config) {');
const functionEnd = loginView.indexOf('function showBiometricProgress(', functionStart);
assert.notEqual(functionStart, -1, 'biometric modal controller is present');
assert.notEqual(functionEnd, -1, 'biometric modal controller has a clear boundary');

function createElement() {
    return {
        dataset: {}, style: {}, className: '', textContent: '', hidden: false, disabled: false,
        classList: {
            active: false,
            contains(name) { return name === 'active' && this.active; },
            add(name) { if (name === 'active') this.active = true; },
        },
        focus() {}, setAttribute() {}, innerHTML: '',
    };
}

function mountModal() {
    const ids = [
        'biometricModal', 'bioModalTitle', 'bioModalDesc', 'bioModalUserPill', 'bioModalUserText',
        'bioModalPrimaryBtn', 'bioModalSecondaryBtn', 'bioModalPasswordWrap', 'bioModalProgress',
        'bioModalProgressText', 'bioModalProgressIcon', 'bioModalCloseBtn', 'bioModalMethodsWrap',
        'bioModalMethodsList', 'bioModalChooseMethodBtn', 'bioModalIcon',
    ];
    const elements = new Map(ids.map(id => [id, createElement()]));
    const body = { style: { overflow: 'auto' } };
    const document = {
        body,
        activeElement: body,
        getElementById: id => elements.get(id) || null,
    };
    const context = vm.createContext({
        document,
        biometricText: copy => String(copy || '').replace(/<[^>]*>/g, '').trim(),
        hideFpMessage() {},
        fpRowBtn: createElement(),
        handleSetupBiometricsClick() {},
        closeBiometricModalAndFocusPassword() {},
        closeBiometricModal() {},
        openBiometricSelectionPrompt() {},
        handleBiometricLogin() {},
        lastAvailableMethods: [],
        lastBiometricOptions: null,
        activeSetupIdentifier: '',
        currentPrimaryModalCallback: null,
        currentSecondaryModalCallback: null,
        currentChooseMethodCallback: null,
        biometricReturnFocus: null,
        biometricPreviousOverflow: '',
    });
    vm.runInContext(loginView.slice(functionStart, functionEnd), context);
    return { context, elements, body };
}

test('biometric login modal refreshes visible actions between progress, retry, and completion states', () => {
    const app = mountModal();
    const open = app.context.openBiometricModal;
    const primary = app.elements.get('bioModalPrimaryBtn');
    const secondary = app.elements.get('bioModalSecondaryBtn');
    const chooser = app.elements.get('bioModalChooseMethodBtn');
    const progress = app.elements.get('bioModalProgress');

    open({ title: 'Confirm on your device', progress: 'Waiting for your device…', hidePrimaryBtn: true, secondaryBtnText: 'Cancel', showChooseMethodBtn: true });
    assert.equal(primary.hidden, true);
    assert.equal(secondary.hidden, false);
    assert.equal(secondary.textContent, 'Cancel');
    assert.equal(chooser.hidden, false);
    assert.equal(progress.hidden, false);

    open({ title: 'Device prompt did not finish', primaryBtnText: 'Try again', secondaryBtnText: 'Use password', showChooseMethodBtn: false });
    assert.equal(primary.hidden, false);
    assert.equal(primary.textContent, 'Try again');
    assert.equal(secondary.textContent, 'Use password');
    assert.equal(chooser.hidden, true);
    assert.equal(progress.hidden, true);

    open({ title: 'Signed in', progress: 'Your account is ready.', complete: true, hidePrimaryBtn: true, secondaryBtnText: false });
    assert.equal(primary.hidden, true);
    assert.equal(secondary.hidden, true);
    assert.equal(app.elements.get('bioModalCloseBtn').hidden, true);
    assert.equal(app.elements.get('bioModalProgressIcon').className, 'bi bi-check-circle-fill bio-progress-complete');
    assert.equal(progress.hidden, false);
    assert.equal(app.elements.get('bioModalProgressText').textContent, 'Your account is ready.');
});
