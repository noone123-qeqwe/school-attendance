const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/partials/pwa-tags.blade.php'), 'utf8');
const source = blade.slice(blade.indexOf('    let swRegistration = null;'), blade.indexOf('    // ── Universal Background Lifecycle'))
    .replace(/\{\{[^}]+\}\}/g, '9.1.6');

function mount({ storage = new Map(), session = new Map(), payload = {}, fail = false, gate } = {}) {
    const listeners = {};
    const elements = new Map();
    const timers = [];
    const videos = [];
    const reloads = [];
    const meta = { 'app-installed-version': '9.1.6', 'app-latest-version': '9.1.6', 'app-build-id': 'build-1', 'app-commit-hash': 'aaaaaaa', 'sw-build-version': 'v1', 'sw-build-mtime': '1' };
    const register = (name, listener) => (listeners[name] ??= []).push(listener);
    const document = {
        body: { appendChild(element) { element.parentElement = this; } }, readyState: 'complete',
        addEventListener: register, getElementById: id => elements.get(id) || null,
        querySelector: selector => ({ content: meta[selector.match(/name="([^"]+)"/)?.[1]] }),
        querySelectorAll: selector => selector === 'video' ? videos : [],
    };
    for (const id of ['pwaSystemUpdatePopup', 'pwaUpdateBackdrop', 'pwaUpdatePill', 'pwaPillVersionBadge', 'pwaUpdateTitle', 'pwaUpdateSubtitle', 'pwaUpdateVersionBadge', 'pwaApplyUpdateBtn', 'pwaApplyUpdateBtnText', 'pwaLaterUpdateBtn', 'pwaDismissUpdatePopupBtn']) {
        const classes = new Set();
        elements.set(id, {
            parentElement: document.body, dataset: {}, attributes: {},
            style: { display: 'none', setProperty(key, value) { this[key] = value; } },
            classList: { contains: key => classes.has(key), add: key => classes.add(key), remove: key => classes.delete(key), toggle(key, on) { on ? classes.add(key) : classes.delete(key); } },
            addEventListener() {}, querySelector() { return null; },
            setAttribute(key, value) { this.attributes[key] = value; }, removeAttribute(key) { delete this.attributes[key]; }, focus() { this.focused = true; },
        });
    }
    const local = { getItem: key => storage.get(key) || null, setItem: (key, value) => storage.set(key, String(value)), removeItem: key => storage.delete(key) };
    const window = { location: { href: 'https://attendance.test/home', replace: url => reloads.push(url), reload: () => reloads.push('reload') }, history: { replaceState() {} }, addEventListener: register };
    const server = { installed_version: '9.1.7', latest_version: '9.1.7', build: 'build-2', commit: 'bbbbbbb', sw_version: 'v2', update: { version: '9.1.7', mode: 'silent' }, changelog: { version: '9.1.7' }, ...payload };
    const fetch = async () => {
        if (gate) await gate;
        if (fail) throw new Error('Offline');
        return { ok: true, json: async () => server };
    };
    const context = vm.createContext({ window, document, navigator: {}, localStorage: local,
        sessionStorage: { getItem: key => session.get(key) || null, setItem: (key, value) => session.set(key, value), removeItem: key => session.delete(key) },
        fetch, URL, AbortController, console,
        setTimeout: (callback, delay) => { timers.push({ callback, delay }); return timers.length; }, clearTimeout() {},
    });
    vm.runInContext(source, context);
    return { window, document, server, storage, session, videos, reloads, timers,
        element: id => elements.get(id), visible: () => elements.get('pwaSystemUpdatePopup').classList.contains('show'),
        check: manual => window.checkServerVersion(true, Boolean(manual)),
        fire: (name, event) => (listeners[name] || []).forEach(listener => listener(event)),
    };
}

test('routine deployments stay quiet even when build, commit, worker and display version change', async () => {
    for (const version of ['9.1.6', '9.1.7']) {
        const app = mount({ payload: { installed_version: version, update: { version, mode: 'silent' } } });
        assert.equal((await app.check()).updateAvailable, true);
        assert.equal(app.visible(), false);
        assert.equal(app.element('pwaUpdatePill').style.display, 'none');
        assert.equal(app.reloads.length, 0);
        assert.equal(app.session.has('pwa_pending_release_key'), false);
    }
});

test('a published feature release uses a nonblocking banner once across revisits and deployments', async () => {
    const storage = new Map();
    const payload = { update: { version: '9.1.7', mode: 'announcement' } };
    const app = mount({ storage, payload });
    await app.check();
    assert.equal(app.visible(), true);
    assert.equal(app.element('pwaUpdateBackdrop').style.display, 'none');
    assert.equal(app.element('pwaSystemUpdatePopup').attributes.role, 'region');
    const revisit = mount({ storage, payload: { ...payload, commit: 'ccccccc' } });
    await revisit.check();
    assert.equal(revisit.visible(), false);
});

test('Later remains dismissed after another deployment of the same release and manual check still works', async () => {
    const app = mount({ payload: { update: { version: '9.1.7', mode: 'announcement' } } });
    await app.check();
    app.window.hideAppUpdatePopup('9.1.7');
    app.server.commit = 'ccccccc';
    await app.check();
    assert.equal(app.visible(), false);
    assert.equal(app.element('pwaUpdatePill').style.display, 'none');
    await app.check(true);
    assert.equal(app.visible(), true);
});

test('manual check offers a routine update without changing global release state', async () => {
    const app = mount();
    await app.check(true);
    assert.equal(app.visible(), true);
    assert.equal(app.element('pwaUpdateBackdrop').style.display, 'none');
    assert.equal(app.reloads.length, 0);
});

test('an unchanged document has no update, and an offline check never invents one', async () => {
    const current = mount({ payload: { installed_version: '9.1.6', build: 'build-1', commit: 'aaaaaaa', sw_version: 'v1' } });
    assert.equal((await current.check(true)).upToDate, true);
    assert.equal(current.visible(), false);
    const offline = mount({ fail: true });
    assert.equal((await offline.check(true)).checkFailed, true);
    assert.equal(offline.visible(), false);
    assert.equal(offline.reloads.length, 0);
});

test('critical update ignores an old dismissal, requires explicit apply and traps dialog keyboard focus', async () => {
    const app = mount({ storage: new Map([['pwa_update_dismissed_tag', 'release:9.1.7']]), payload: { update: { version: '9.1.7', mode: 'critical' } } });
    await app.check();
    assert.equal(app.visible(), true);
    assert.equal(app.element('pwaUpdateBackdrop').style.display, 'block');
    assert.equal(app.element('pwaLaterUpdateBtn').style.display, 'none');
    app.window.hideAppUpdatePopup('9.1.7');
    assert.equal(app.visible(), true);
    let prevented = false;
    app.fire('keydown', { key: 'Tab', preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(app.reloads.length, 0);
});

test('a later quiet release retains the minimum safe version requirement', async () => {
    const app = mount({ payload: { update: { version: '9.1.7', mode: 'silent', minimum_supported_version: '9.1.7' } } });
    await app.check();
    assert.equal(app.visible(), true);
    assert.equal(app.element('pwaSystemUpdatePopup').dataset.updateMode, 'critical');
});

test('a critical release replaces an already visible optional announcement', async () => {
    const app = mount({ payload: { update: { version: '9.1.7', mode: 'announcement' } } });
    await app.check();
    app.server.update.mode = 'critical';
    await app.check();
    assert.equal(app.element('pwaSystemUpdatePopup').dataset.updateMode, 'critical');
    assert.equal(app.element('pwaUpdateBackdrop').style.display, 'block');
});

test('rebuilding a critical release already loaded does not require it again', async () => {
    const app = mount({ payload: { installed_version: '9.1.6', update: { version: '9.1.6', mode: 'critical', minimum_supported_version: '9.1.6' } } });
    await app.check();
    assert.equal(app.visible(), false);
    await app.check(true);
    assert.equal(app.element('pwaSystemUpdatePopup').dataset.updateMode, 'announcement');
});

test('stale or missing announcement metadata defaults to quiet', async () => {
    for (const update of [undefined, { version: '9.1.8', mode: 'critical' }, { version: '9.1.7', mode: 'unknown' }]) {
        const app = mount({ payload: { update } });
        await app.check();
        assert.equal(app.visible(), false);
    }
});

test('active camera and changed forms defer announcements and block even explicit refresh', async () => {
    for (const work of ['camera', 'form']) {
        const app = mount({ payload: { update: { version: '9.1.7', mode: 'critical' } } });
        const form = { method: 'post', isConnected: true };
        if (work === 'camera') app.videos.push({ srcObject: { getVideoTracks: () => [{ readyState: 'live' }] } });
        else app.fire('input', { target: { form, type: 'text' } });
        await app.check();
        assert.equal(app.visible(), false);
        assert.equal(await app.window.applySystemUpdate(), false);
        assert.equal(app.reloads.length, 0);
        if (work === 'camera') app.videos.length = 0;
        else app.fire('reset', { target: form });
        await app.check();
        assert.equal(app.visible(), true);
    }
});

test('a manual critical check leaves unfinished forms accessible so the user can save them', async () => {
    const app = mount({ payload: { update: { version: '9.1.7', mode: 'critical' } } });
    app.fire('input', { target: { form: { method: 'post', isConnected: true }, type: 'text' } });
    await app.check(true);
    assert.equal(app.element('pwaUpdateBackdrop').style.display, 'none');
    assert.equal(app.element('pwaSystemUpdatePopup').attributes.role, 'region');
    assert.equal(await app.window.applySystemUpdate(), false);
    app.window.hideAppUpdatePopup('9.1.7');
    assert.equal(app.visible(), false);
});

test('queued offline attendance blocks apply, while a safe explicit apply schedules a reload', async () => {
    const app = mount();
    await app.check(true);
    app.window.OfflineAttendance = { getPendingCount: async () => 2 };
    assert.equal(await app.window.applySystemUpdate(), false);
    assert.match(app.element('pwaUpdateSubtitle').textContent, /2 attendance records/);
    assert.equal(app.session.has('pwa_pending_release_key'), false);
    app.window.OfflineAttendance.getPendingCount = async () => 0;
    await app.window.applySystemUpdate();
    assert.equal(app.session.get('pwa_pending_version'), '9.1.7');
    await app.timers.find(timer => timer.delay === 200).callback();
    assert.equal(app.reloads.length, 1);
});

test('pending offline records defer a required dialog so sync controls remain accessible', async () => {
    const app = mount({ payload: { update: { version: '9.1.7', mode: 'critical' } } });
    app.window.OfflineAttendance = { getPendingCount: async () => 2 };
    await app.check();
    assert.equal(app.visible(), false);
    await app.check(true);
    assert.equal(app.element('pwaSystemUpdatePopup').attributes.role, 'region');
    assert.equal(app.element('pwaUpdateBackdrop').style.display, 'none');
    app.window.hideAppUpdatePopup('9.1.7');
    app.window.OfflineAttendance.getPendingCount = async () => 0;
    await app.check();
    assert.equal(app.element('pwaSystemUpdatePopup').dataset.updateMode, 'critical');
});

test('camera work begun after clicking Update also prevents the delayed refresh', async () => {
    const app = mount();
    await app.check(true);
    await app.window.applySystemUpdate();
    app.videos.push({ srcObject: { getVideoTracks: () => [{ readyState: 'live' }] } });
    await app.timers.find(timer => timer.delay === 200).callback();
    assert.equal(app.reloads.length, 0);
    assert.equal(app.session.has('pwa_pending_release_key'), false);
    assert.equal(app.visible(), true);
    assert.equal(app.element('pwaApplyUpdateBtn').style.pointerEvents, '');
});

test('a manual check joining a background request still offers the update', async () => {
    let resolve;
    const app = mount({ gate: new Promise(done => { resolve = done; }) });
    const background = app.check();
    const manual = app.check(true);
    resolve();
    await Promise.all([background, manual]);
    assert.equal(app.visible(), true);
});

test('an interrupted update that reloads the old document never claims successful installation', () => {
    const app = mount({ session: new Map([
        ['pwa_pending_release_key', 'build-2|bbbbbbb|v2'],
        ['pwa_pending_version', '9.1.7'], ['pwa_updating', 'true'],
    ]) });
    assert.equal(app.storage.has('pwa_applied_release_key'), false);
    assert.equal(app.storage.get('pwa_installed_version'), '9.1.6');
    assert.equal(app.timers.some(timer => timer.delay === 400), false);
    assert.equal(app.session.has('pwa_updating'), false);
});
