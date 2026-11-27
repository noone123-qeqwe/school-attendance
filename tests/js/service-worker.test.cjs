const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const worker = fs.readFileSync(path.join(__dirname, '../../public/sw.js'), 'utf8');

function loadWorker({ caches, registration, clients } = {}) {
    const listeners = new Map();
    vm.runInNewContext(worker, {
        self: {
            addEventListener: (event, listener) => listeners.set(event, listener),
            registration,
            clients,
        },
        caches,
        URL,
    });
    return listeners;
}

test('page navigations go directly through the browser', () => {
    const handleFetch = loadWorker().get('fetch');

    for (const page of ['/', '/home', '/teacher/classroom', '/offline', '/up']) {
        handleFetch({
            request: { method: 'GET', mode: 'navigate', url: 'https://attendance.test' + page },
            respondWith: () => assert.fail('Navigation must not get a service worker response'),
        });
    }
});

test('upgrading removes the legacy offline page and disables navigation preload', async () => {
    const version = worker.match(/const CACHE_VERSION = '([^']+)'/)[1];
    const staticCache = 'attendance-' + version;
    const runtimeCache = 'attendance-runtime-' + version;
    const oldCache = 'attendance-v00000000000';
    const removed = [];
    const actions = [];
    const listeners = loadWorker({
        caches: {
            keys: async () => [staticCache, runtimeCache, oldCache, 'other-app'],
            delete: async name => removed.push(name),
            open: async name => {
                assert.equal(name, staticCache);
                return { delete: async request => removed.push(request) };
            },
        },
        registration: {
            navigationPreload: { disable: async () => actions.push('disabled preload') },
        },
        clients: { claim: async () => actions.push('claimed clients') },
    });
    let completion;
    listeners.get('activate')({ waitUntil: promise => { completion = promise; } });

    await completion;
    assert.deepEqual(removed, [oldCache, '/offline']);
    assert.deepEqual(actions, ['disabled preload', 'claimed clients']);
});
