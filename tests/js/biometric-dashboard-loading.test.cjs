const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const login = fs.readFileSync(path.join(__dirname, '../../resources/views/auth/login.blade.php'), 'utf8');
const dashboard = fs.readFileSync(path.join(__dirname, '../../resources/views/home.blade.php'), 'utf8');
const render = source => source.replace(/\{\{[\s\S]*?\}\}/g, 'rendered');

function loginFixture(storageFails = false) {
    const events = [];
    const context = {
        biometricRedirectStarted: false, isBioPending: false,
        fpLabel: {}, fpHint: {}, fpIcon: {}, fpArrow: {},
        fpRowBtn: { style: {}, removeAttribute() {} },
        window: { location: { replace(url) { events.push(['navigate', url]); } } },
        saveAccount(account) {
            events.push(['remember', account.identifier]);
            if (storageFails) throw new Error('Storage unavailable');
        }
    };
    vm.createContext(context);
    vm.runInContext(render(login.slice(login.indexOf('function completeBiometricLogin('), login.indexOf('function getEffectiveRpId('))), context);
    return { context, events };
}

test('successful biometrics start the dashboard request before optional account storage', () => {
    const { context, events } = loginFixture();
    context.completeBiometricLogin({ redirect: '/home', user: { identifier: '1234569' } });
    assert.deepEqual(events, [['navigate', '/home'], ['remember', '1234569']]);
    assert.equal(context.fpRowBtn.disabled, true);
    context.completeBiometricLogin({ redirect: '/home' });
    assert.equal(events.length, 2, 'Repeated success cannot start another navigation');
});

test('unavailable saved-account storage does not prevent dashboard navigation', () => {
    const { context, events } = loginFixture(true);
    assert.doesNotThrow(() => context.completeBiometricLogin({ redirect: '/teacher/dashboard', user: { identifier: 'teacher@example.com' } }));
    assert.equal(events[0][1], '/teacher/dashboard');
});

function dashboardFixture(fetch) {
    const elements = new Map();
    let shown = 0;
    const body = { classList: { remove() {}, add() {} }, appendChild() {} };
    for (const id of ['daySummaryTitle', 'daySummarySubtitle', 'daySummaryContent', 'daySummaryModal']) {
        elements.set(id, {
            innerHTML: '', textContent: '', style: {}, parentNode: body,
            classList: { add() {}, remove() {} }, setAttribute() {}, removeAttribute() {}
        });
    }
    const context = {
        console, fetch, Promise, Date,
        window: { bootstrap: { Modal: { getOrCreateInstance() { return { show() { shown++; }, hide() {} }; } } } },
        document: { body, getElementById: id => elements.get(id), querySelector: () => null },
    };
    context.bootstrap = context.window.bootstrap;
    vm.createContext(context);
    vm.runInContext(render(dashboard.slice(dashboard.indexOf('var attCalendarData'), dashboard.indexOf('async function loadAttendanceRecordsForModal('))), context);
    return { context, elements, shown: () => shown };
}

const flush = () => new Promise(resolve => setImmediate(resolve));

test('calendar loading is on demand and shares one request between rapid day selections', async () => {
    let requests = 0, resolve;
    const pending = new Promise(done => { resolve = done; });
    const f = dashboardFixture(() => { requests++; return pending; });
    assert.equal(requests, 0, 'Initial dashboard must not fetch optional calendar details');
    f.context.showAttDetail('2026-10-05', 5);
    f.context.showAttDetail('2026-10-06', 6);
    assert.equal(requests, 1);
    assert.equal(f.shown(), 2, 'Loading feedback opens immediately');
    resolve({ ok: true, json: async () => ({ attendance: {}, events: {} }) });
    await flush();
    assert.match(f.elements.get('daySummaryTitle').textContent, /October 6/);
    assert.match(f.elements.get('daySummaryContent').innerHTML, /No records or events/);
});

test('calendar network errors show recovery information and allow retry', async () => {
    let requests = 0;
    const f = dashboardFixture(async () => {
        requests++;
        if (requests === 1) throw new Error('Network offline');
        return { ok: true, json: async () => ({ attendance: {}, events: {} }) };
    });
    f.context.showAttDetail('2026-10-05', 5);
    await flush();
    assert.match(f.elements.get('daySummaryContent').innerHTML, /Could not load calendar details/);
    f.context.closeDaySummaryModal();
    f.context.showAttDetail('2026-10-05', 5);
    await flush();
    assert.equal(requests, 2);
    assert.match(f.elements.get('daySummaryContent').innerHTML, /No records or events/);
});

test('closing a loading calendar does not reopen it when its request completes', async () => {
    let resolve;
    const f = dashboardFixture(() => new Promise(done => { resolve = done; }));
    f.context.showAttDetail('2026-10-05', 5);
    f.context.closeDaySummaryModal();
    resolve({ ok: true, json: async () => ({ attendance: {}, events: {} }) });
    await flush();
    assert.equal(f.shown(), 1);
});
