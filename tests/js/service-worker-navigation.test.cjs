const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../public/sw.js'), 'utf8');

function mount({ preloadResponse = Promise.resolve(undefined), fetch: networkFetch = async () => new Response('online page', { status: 200 }) } = {}) {
    const listeners = new Map();
    const offlineResponse = new Response('cached offline page', { status: 200 });
    const self = {
        registration: {},
        clients: { matchAll: async () => [], claim: async () => {} },
        addEventListener(name, callback) { listeners.set(name, callback); },
        skipWaiting: async () => {},
    };
    const caches = {
        open: async () => ({ match: async () => offlineResponse }),
        keys: async () => [],
        match: async () => null,
    };
    const context = vm.createContext({
        self,
        caches,
        URL,
        Response,
        AbortController,
        Promise,
        console: { info() {}, warn() {}, log() {}, error() {} },
        fetch: networkFetch,
        // Run service-worker retry delays without adding wall time to tests.
        setTimeout(callback) { callback(); return 1; },
        clearTimeout() {},
    });
    vm.runInContext(source, context);

    return {
        offlineResponse,
        async navigate() {
            let responsePromise;
            const event = {
                request: { method: 'GET', mode: 'navigate', url: 'https://school.example/student' },
                preloadResponse,
                respondWith(promise) { responsePromise = promise; },
            };
            listeners.get('fetch')(event);
            return responsePromise;
        },
    };
}

test('a rejected navigation preload falls through to the normal network request', async () => {
    let requests = 0;
    const app = mount({
        preloadResponse: Promise.reject(new TypeError('temporary network failure')),
        fetch: async () => { requests++; return new Response('latest page', { status: 200 }); },
    });

    const response = await app.navigate();
    assert.equal(await response.text(), 'latest page');
    assert.equal(requests, 1);
});

test('a failed first network request is retried before using the offline screen', async () => {
    let requests = 0;
    const app = mount({
        preloadResponse: Promise.reject(new TypeError('preload failed')),
        fetch: async () => {
            requests++;
            if (requests === 1) throw new TypeError('brief network handoff');
            return new Response('recovered page', { status: 200 });
        },
    });

    const response = await app.navigate();
    assert.equal(await response.text(), 'recovered page');
    assert.equal(requests, 2);
});

test('the cached offline page is returned only after both network attempts fail', async () => {
    let requests = 0;
    const app = mount({
        preloadResponse: Promise.reject(new TypeError('preload failed')),
        fetch: async () => { requests++; throw new TypeError('network unavailable'); },
    });

    const response = await app.navigate();
    assert.equal(await response.text(), 'cached offline page');
    assert.equal(requests, 2);
});
