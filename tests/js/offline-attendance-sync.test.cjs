const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../public/js/offline-attendance.js'), 'utf8');

function makeDatabase(seedRecords, seedRosters) {
    const records = new Map(seedRecords.map(record => [record.local_id, { ...record }]));
    const roster = new Map((seedRosters || []).map(subject => [subject.cache_key, { ...subject }]));
    let nextId = Math.max(0, ...records.keys()) + 1;

    function transaction() {
        const tx = { pending: 0, completionQueued: false, completed: false, oncomplete: null, onerror: null };
        tx.enqueue = callback => {
            tx.pending++;
            queueMicrotask(() => {
                try {
                    callback();
                } catch (error) {
                    tx.error = error;
                    tx.onerror?.({ target: tx });
                } finally {
                    tx.pending--;
                    if (tx.pending === 0 && !tx.completionQueued) {
                        tx.completionQueued = true;
                        queueMicrotask(() => {
                            tx.completionQueued = false;
                            if (tx.pending === 0 && !tx.completed) {
                                tx.completed = true;
                                tx.oncomplete?.();
                            }
                        });
                    }
                }
            });
        };
        return tx;
    }

    function createRequest(tx, callback) {
        const request = { result: undefined, error: null, onsuccess: null, onerror: null };
        tx.enqueue(() => {
            try {
                request.result = callback();
                request.onsuccess?.({ target: request });
            } catch (error) {
                request.error = error;
                request.onerror?.({ target: request });
            }
        });
        return request;
    }

    function makeStore(tx, storeName) {
        const data = storeName === 'pending_records' ? records : roster;
        const keyField = storeName === 'pending_records' ? 'local_id' : 'cache_key';
        return {
            get(key) {
                return createRequest(tx, () => {
                    const value = data.get(key);
                    return value ? { ...value } : undefined;
                });
            },
            getAll() {
                return createRequest(tx, () => [...data.values()].map(value => ({ ...value })));
            },
            add(value) {
                return createRequest(tx, () => {
                    const key = nextId++;
                    data.set(key, { ...value, [keyField]: key });
                    return key;
                });
            },
            put(value) {
                return createRequest(tx, () => {
                    const key = value[keyField];
                    data.set(key, { ...value });
                    return key;
                });
            },
            index(field) {
                return {
                    getAll(value) {
                        return createRequest(tx, () => [...data.values()]
                            .filter(record => record[field] === value)
                            .map(record => ({ ...record })));
                    },
                    openCursor(value) {
                        const request = { result: null, onsuccess: null, onerror: null };
                        let cursorIndex = 0;
                        const rows = () => [...data.values()].filter(record => record[field] === value);
                        const next = () => tx.enqueue(() => {
                            const matchingRows = rows();
                            if (cursorIndex >= matchingRows.length) {
                                request.result = null;
                            } else {
                                const row = matchingRows[cursorIndex];
                                request.result = {
                                    value: { ...row },
                                    delete: () => data.delete(row[keyField]),
                                    continue: () => { cursorIndex++; next(); },
                                };
                            }
                            request.onsuccess?.({ target: request });
                        });
                        next();
                        return request;
                    },
                };
            },
        };
    }

    const database = {
        objectStoreNames: { contains: name => ['pending_records', 'roster_cache'].includes(name) },
        transaction() {
            const tx = transaction();
            tx.objectStore = name => makeStore(tx, name);
            return tx;
        },
    };

    return {
        records,
        indexedDB: {
            open() {
                const request = { result: null, onsuccess: null, onerror: null };
                queueMicrotask(() => {
                    request.result = database;
                    request.onsuccess?.({ target: request });
                });
                return request;
            },
        },
    };
}

function mount({ records, rosters = [], fetchSync, serverReachable = true }) {
    const db = makeDatabase(records, rosters);
    const listeners = new Map();
    const toasts = [];
    let postCount = 0;
    const body = {
        dataset: { isOfflinePage: 'true' },
        appendChild(element) { toasts.push(element); },
    };
    const document = {
        body,
        head: { appendChild() {} },
        querySelector() { return null; },
        getElementById() { return null; },
        createElement() {
            return {
                classList: { add() {} },
                remove() {},
            };
        },
    };
    const window = {
        addEventListener(name, callback) {
            if (!listeners.has(name)) listeners.set(name, []);
            listeners.get(name).push(callback);
        },
        dispatchEvent() {},
    };
    const navigator = {
        onLine: false,
        storage: { persist: () => Promise.resolve(true) },
    };
    const fetch = async (url, options) => {
        if (url === '/api/ping') return { ok: serverReachable };
        if (url === '/teacher/offline-attendance/sync') {
            postCount++;
            return fetchSync(JSON.parse(options.body), postCount);
        }
        throw new Error('Unexpected request: ' + url);
    };

    const context = {
        window,
        document,
        navigator,
        indexedDB: db.indexedDB,
        localStorage: { getItem: () => null },
        fetch,
        AbortSignal: { timeout: () => undefined },
        CustomEvent: class { constructor(type, init) { this.type = type; this.detail = init.detail; } },
        requestAnimationFrame: callback => callback(),
        setInterval: () => 1,
        clearInterval() {},
        setTimeout: () => 1,
        clearTimeout() {},
        console: { log() {}, warn() {}, error() {} },
    };
    vm.runInNewContext(source, context);

    window.OfflineAttendance.init({ teacherId: 7, csrfToken: 'csrf' });

    return {
        records: db.records,
        toasts,
        api: window.OfflineAttendance,
        setServerReachable(value) { serverReachable = value; },
        dispatch(name) { (listeners.get(name) || []).forEach(callback => callback()); },
        get postCount() { return postCount; },
    };
}

function pendingRecord(localId) {
    return {
        local_id: localId,
        teacher_id: 7,
        user_id: 100 + localId,
        subject_code: 'CS101',
        status: 'Present',
        date: '2026-11-07',
        sync_status: 'pending',
        sync_attempts: 0,
    };
}

async function waitFor(assertion, timeoutMs = 1500) {
    const start = Date.now();
    while (Date.now() - start < timeoutMs) {
        if (assertion()) return;
        await new Promise(resolve => setTimeout(resolve, 5));
    }
    assert.ok(assertion(), 'condition did not become true before timeout');
}

test('reopening while online resumes pending attendance automatically', async () => {
    const app = mount({
        records: [pendingRecord(1)],
        fetchSync: async ({ records }) => ({
            ok: true,
            json: async () => ({ success: true, synced: records.map(record => ({ local_id: record.local_id })), failed: [], skipped: [] }),
        }),
    });

    await waitFor(() => app.records.get(1)?.sync_status === 'synced');
    assert.equal(app.postCount, 1);
});

test('offline queue waits safely and resumes when the application server returns', async () => {
    const app = mount({
        records: [pendingRecord(1)],
        serverReachable: false,
        fetchSync: async ({ records }) => ({
            ok: true,
            json: async () => ({ success: true, synced: records.map(record => ({ local_id: record.local_id })), failed: [], skipped: [] }),
        }),
    });

    await new Promise(resolve => setTimeout(resolve, 25));
    assert.equal(app.postCount, 0);
    assert.equal(app.records.get(1).sync_status, 'pending');

    app.setServerReachable(true);
    app.dispatch('online');
    await waitFor(() => app.records.get(1)?.sync_status === 'synced');
    assert.equal(app.postCount, 1);
});

test('transient sync failure keeps data and retries after connectivity returns', async () => {
    const app = mount({
        records: [pendingRecord(1)],
        fetchSync: async ({ records }, attempt) => {
            if (attempt === 1) throw new TypeError('network disconnected');
            return {
                ok: true,
                json: async () => ({ success: true, synced: records.map(record => ({ local_id: record.local_id })), failed: [], skipped: [] }),
            };
        },
    });

    await waitFor(() => app.records.get(1)?.sync_status === 'failed');
    assert.equal(app.records.get(1).sync_retryable, true);
    app.dispatch('online');
    await waitFor(() => app.records.get(1)?.sync_status === 'synced');
    assert.equal(app.postCount, 2);
});

test('partial sync keeps permanent failures on device and reports them', async () => {
    const app = mount({
        records: [pendingRecord(1), pendingRecord(2)],
        fetchSync: async ({ records }) => ({
            ok: true,
            json: async () => ({
                success: true,
                synced: [{ local_id: records[0].local_id }],
                skipped: [],
                failed: [{ local_id: records[1].local_id, reason: 'Student is not enrolled.', retryable: false }],
                summary: { total: 2, synced: 1, skipped: 0, failed: 1 },
            }),
        }),
    });

    await waitFor(() => app.records.get(1)?.sync_status === 'synced' && app.records.get(2)?.sync_status === 'failed');
    assert.equal(app.records.get(2).sync_retryable, false);
    assert.equal(app.records.get(2).sync_error, 'Student is not enrolled.');
    assert.match(app.toasts[0].textContent, /still saved on this device/);
    assert.equal(app.postCount, 1);
});

test('records blocked by an expired session resume after the teacher signs in again', async () => {
    const record = {
        ...pendingRecord(1),
        sync_status: 'failed',
        sync_retryable: false,
        sync_requires_auth: true,
        sync_error: 'Server returned 419',
        sync_attempts: 1,
    };
    const app = mount({
        records: [record],
        fetchSync: async ({ records }) => ({
            ok: true,
            json: async () => ({ success: true, synced: records.map(item => ({ local_id: item.local_id })), failed: [], skipped: [] }),
        }),
    });

    await waitFor(() => app.records.get(1)?.sync_status === 'synced');
    assert.equal(app.postCount, 1);
});

test('offline roster lookup never falls back to another teacher cache', async () => {
    const app = mount({
        records: [],
        rosters: [{ cache_key: '7:CS101', teacher_id: 7, code: 'CS101', students: [{ id: 100 }] }],
        serverReachable: false,
        fetchSync: async () => ({ ok: true, json: async () => ({ success: true, synced: [], failed: [], skipped: [] }) }),
    });

    const otherTeacherRoster = await app.api.getCachedRoster(8);
    const otherTeacherSubject = await app.api.getCachedSubject('CS101', 8);
    const ownerRoster = await app.api.getCachedRoster(7);

    assert.equal(otherTeacherRoster.length, 0);
    assert.equal(otherTeacherSubject, null);
    assert.equal(ownerRoster.length, 1);
});

test('pending de-duplication keeps records scoped to their teacher account', async () => {
    const app = mount({
        records: [],
        serverReachable: false,
        fetchSync: async () => ({ ok: true, json: async () => ({ success: true, synced: [], failed: [], skipped: [] }) }),
    });

    const attendance = { user_id: 100, subject_code: 'CS101', status: 'Present', date: '2026-11-07' };
    await app.api.saveRecord({ ...attendance, teacher_id: 7 });
    await app.api.saveRecord({ ...attendance, teacher_id: 8 });

    assert.equal(app.records.size, 2);
});
