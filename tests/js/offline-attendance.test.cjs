const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const scriptPath = path.resolve(__dirname, '../../public/js/offline-attendance.js');
const scriptSource = fs.readFileSync(scriptPath, 'utf8');

class FakeClassList {
    constructor() {
        this.set = new Set();
    }
    toggle(name, force) {
        if (force === undefined) {
            if (this.set.has(name)) this.set.delete(name);
            else this.set.add(name);
            return;
        }
        if (force) this.set.add(name);
        else this.set.delete(name);
    }
    contains(name) {
        return this.set.has(name);
    }
}

class FakeElement {
    constructor(tagName = 'DIV') {
        this.tagName = tagName.toUpperCase();
        this.id = '';
        this.className = '';
        this.classList = new FakeClassList();
        this.textContent = '';
        this.style = {};
        this.dataset = {};
        this.children = [];
        this.listeners = {};
    }
    addEventListener(type, fn) {
        this.listeners[type] = this.listeners[type] || [];
        this.listeners[type].push(fn);
    }
    appendChild(child) {
        this.children.push(child);
        return child;
    }
    querySelectorAll() {
        return [];
    }
}

function createHarness(options = {}) {
    const elementsById = new Map();
    const registerEl = (el) => {
        if (el && el.id) elementsById.set(el.id, el);
        if (el && el.children) el.children.forEach(registerEl);
    };

    const body = new FakeElement('BODY');
    const head = new FakeElement('HEAD');

    // Provide stub nodes created by injectStatusUI
    const makeStatusNodes = () => {
        const wrap = new FakeElement('DIV');
        wrap.id = 'offlineAttendanceStatus';
        wrap.className = 'oa-status-wrap';
        wrap.style = { display: 'none' };

        const pill = new FakeElement('DIV');
        pill.id = 'oaStatusPill';
        pill.className = 'oa-status-pill';

        const dot = new FakeElement('SPAN');
        dot.id = 'oaStatusDot';
        dot.className = 'oa-status-dot';

        const label = new FakeElement('SPAN');
        label.id = 'oaStatusLabel';
        label.className = 'oa-status-label';
        label.textContent = 'Online';

        const badge = new FakeElement('SPAN');
        badge.id = 'oaPendingBadge';
        badge.className = 'oa-pending-badge';
        badge.style = { display: 'none' };
        badge.textContent = '0';

        const syncBtn = new FakeElement('BUTTON');
        syncBtn.id = 'oaSyncBtn';
        syncBtn.className = 'oa-sync-btn';
        syncBtn.style = { display: 'none' };

        pill.appendChild(dot);
        pill.appendChild(label);
        pill.appendChild(badge);
        wrap.appendChild(pill);
        wrap.appendChild(syncBtn);
        registerEl(wrap);
        return wrap;
    };

    const listeners = { window: {}, document: {} };
    let nextTimerId = 1;
    const timeouts = new Map();
    const intervals = new Map();
    const fetchCalls = [];
    let fetchImpl = options.fetchImpl || (async () => ({ ok: true, status: 200, json: async () => ({ success: true, subjects: [] }) }));

    const document = {
        hidden: Boolean(options.hidden),
        body,
        head,
        dataset: {},
        getElementById(id) {
            return elementsById.get(id) || null;
        },
        querySelector(sel) {
            if (sel === 'meta[name="csrf-token"]') return { content: 'test-csrf' };
            return null;
        },
        createElement(tag) {
            const el = new FakeElement(tag);
            if (tag.toLowerCase() === 'div') {
                let rawHtml = '';
                Object.defineProperty(el, 'innerHTML', {
                    get() { return rawHtml; },
                    set(val) {
                        rawHtml = String(val);
                        if (rawHtml.includes('id="offlineAttendanceStatus"')) {
                            el.firstElementChild = makeStatusNodes();
                        }
                    },
                });
            }
            return el;
        },
        addEventListener(type, fn) {
            listeners.document[type] = listeners.document[type] || [];
            listeners.document[type].push(fn);
        },
    };
    body.appendChild = function(child) {
        this.children.push(child);
        registerEl(child);
        return child;
    };
    head.appendChild = function(child) {
        this.children.push(child);
        registerEl(child);
        return child;
    };

    const indexedDbData = [];
    const makeRequest = (result) => {
        const req = { result, error: null, onsuccess: null, onerror: null };
        queueMicrotask(() => { if (req.onsuccess) req.onsuccess({ target: req }); });
        return req;
    };
    const fakeDb = {
        objectStoreNames: { contains: () => true },
        transaction() {
            const store = {
                index: () => ({
                    getAll: () => makeRequest([]),
                    openCursor: () => {
                        const req = { result: null, error: null, onsuccess: null, onerror: null };
                        queueMicrotask(() => { if (req.onsuccess) req.onsuccess({ target: req }); });
                        return req;
                    },
                }),
                getAll: () => makeRequest(indexedDbData.slice()),
                put: () => {},
            };
            return { objectStore: () => store, oncomplete: null, onerror: null };
        },
    };

    const navigatorObj = {
        onLine: options.onLine !== undefined ? options.onLine : true,
    };

    const windowObj = {
        navigator: navigatorObj,
        document,
        addEventListener(type, fn) {
            listeners.window[type] = listeners.window[type] || [];
            listeners.window[type].push(fn);
        },
        dispatchEvent() {},
        CustomEvent: class CustomEvent {
            constructor(type, init = {}) {
                this.type = type;
                this.detail = init.detail;
            }
        },
        setTimeout(fn, delay) {
            const id = nextTimerId++;
            timeouts.set(id, { id, fn, delay });
            return id;
        },
        clearTimeout(id) {
            timeouts.delete(id);
        },
        setInterval(fn, delay) {
            const id = nextTimerId++;
            intervals.set(id, { id, fn, delay });
            return id;
        },
        clearInterval(id) {
            intervals.delete(id);
        },
    };

    const context = vm.createContext({
        window: windowObj,
        document,
        navigator: navigatorObj,
        AbortSignal: undefined,
        AbortController: class AbortController {
            constructor() { this.signal = { aborted: false }; }
            abort() { this.signal.aborted = true; }
        },
        indexedDB: {
            open() {
                const req = { result: fakeDb, error: null, onupgradeneeded: null, onsuccess: null, onerror: null };
                queueMicrotask(() => {
                    if (req.onsuccess) req.onsuccess({ target: req });
                });
                return req;
            },
        },
        localStorage: { getItem: () => null, setItem() {} },
        fetch: (url, init) => {
            fetchCalls.push({ url, init });
            return Promise.resolve(fetchImpl(url, init));
        },
        setTimeout: windowObj.setTimeout,
        clearTimeout: windowObj.clearTimeout,
        setInterval: windowObj.setInterval,
        clearInterval: windowObj.clearInterval,
        requestAnimationFrame: (fn) => fn(),
        console: { log() {}, warn() {}, error() {} },
    });

    vm.runInContext(scriptSource, context);

    return {
        api: windowObj.OfflineAttendance,
        elementsById,
        timeouts,
        intervals,
        fetchCalls,
        listeners,
        navigatorObj,
        document,
        setFetchImpl(fn) { fetchImpl = fn; },
        async flush() {
            for (let i = 0; i < 6; i++) {
                await new Promise((resolve) => setImmediate(resolve));
            }
        },
    };
}

test('starts online and keeps status pill hidden when navigator.onLine is true and queue is empty', async () => {
    const h = createHarness({ onLine: true });
    h.api.init({ csrfToken: 'abc', teacherId: 42 });
    await h.flush();

    assert.equal(h.api.getConnectionState(), 'online');
    assert.equal(h.api.isOnline(), true);
    assert.equal(h.elementsById.get('offlineAttendanceStatus').style.display, 'none');
});

test('survives a single transient /api/ping failure and transitions offline only after 2 consecutive failures', async () => {
    let pingResult = () => Promise.reject(new Error('timeout'));
    const h = createHarness({
        onLine: true,
        fetchImpl: (url) => {
            if (String(url).includes('/api/ping')) return pingResult();
            return Promise.resolve({ ok: true, status: 200, json: async () => ({ success: true, subjects: [] }) });
        },
    });
    h.api.init({ csrfToken: 'abc', teacherId: 42 });
    await h.flush();

    // Deferred initial probe runs timeout callback -> 1st failure stays online
    const initialPing = Array.from(h.timeouts.values()).find((t) => t.delay === 2500);
    assert.ok(initialPing, 'expected deferred initial ping timer');
    initialPing.fn();
    await h.flush();

    assert.equal(h.api.getConnectionState(), 'online');
    assert.equal(h.elementsById.get('offlineAttendanceStatus').style.display, 'none');

    // Periodic probe fails a 2nd consecutive time -> transitions to offline
    const periodicPing = Array.from(h.intervals.values())[0];
    assert.ok(periodicPing, 'expected periodic ping interval');
    periodicPing.fn();
    await h.flush();

    assert.equal(h.api.getConnectionState(), 'offline');
    assert.equal(h.elementsById.get('oaStatusLabel').textContent, 'Offline');
    assert.equal(h.elementsById.get('offlineAttendanceStatus').style.display, 'flex');

    // Recovers smoothly to online once ping responds
    pingResult = () => Promise.resolve({ ok: true, status: 200 });
    periodicPing.fn();
    await h.flush();

    assert.equal(h.api.getConnectionState(), 'online');
    assert.equal(h.elementsById.get('offlineAttendanceStatus').style.display, 'none');
});

test('treats HTTP 429 rate-limit response from server probe as reachable rather than offline', async () => {
    const h = createHarness({
        onLine: true,
        fetchImpl: (url) => {
            if (String(url).includes('/api/ping')) {
                return Promise.resolve({ ok: false, status: 429 });
            }
            return Promise.resolve({ ok: true, status: 200, json: async () => ({ success: true, subjects: [] }) });
        },
    });

    h.api.init({ csrfToken: 'abc', teacherId: 42 });
    await h.flush();

    const initialPing = Array.from(h.timeouts.values()).find((t) => t.delay === 2500);
    assert.ok(initialPing);
    initialPing.fn();
    await h.flush();

    const periodicPing = Array.from(h.intervals.values())[0];
    periodicPing.fn();
    await h.flush();

    assert.equal(h.api.getConnectionState(), 'online');
    assert.equal(h.elementsById.get('offlineAttendanceStatus').style.display, 'none');
});

test('does not flash syncing when queue is empty and skips periodic pings while tab is hidden', async () => {
    const h = createHarness({ onLine: true, hidden: true });
    h.api.init({ csrfToken: 'abc', teacherId: 42 });
    await h.flush();

    const pingCountBefore = h.fetchCalls.filter((c) => String(c.url).includes('/api/ping')).length;
    const periodicPing = Array.from(h.intervals.values())[0];
    periodicPing.fn();
    await h.flush();

    const pingCountAfter = h.fetchCalls.filter((c) => String(c.url).includes('/api/ping')).length;
    assert.equal(pingCountAfter, pingCountBefore, 'should skip periodic ping while tab is hidden');

    h.api.syncNow();
    await h.flush();
    assert.equal(h.api.getConnectionState(), 'online');
    assert.equal(h.elementsById.get('offlineAttendanceStatus').style.display, 'none');
});
