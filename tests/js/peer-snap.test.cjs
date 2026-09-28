const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/mobile/peer-snap.blade.php'), 'utf8');
const script = blade.match(/<script @cspNonce>([\s\S]*?)<\/script>/)[1]
    .replace(/^    const (startUrl|confirmUrl|sessionsUrl) = .*;$/gm, '    const $1 = "/$1";');

function deferred() {
    let resolve;
    const promise = new Promise(done => { resolve = done; });
    return { promise, resolve };
}

function mount({ ticketGate, cameraGate, playGate }) {
    const elements = new Map();
    const listeners = {};
    let cameraCalls = 0;
    const element = id => {
        if (!elements.has(id)) elements.set(id, {
            value: '', hidden: false, disabled: false, textContent: '',
            addEventListener(event, callback) { this[event] = callback; },
            replaceChildren() {}, add() {}, append() {},
        });
        return elements.get(id);
    };
    element('peerSession').value = '1';
    element('peerStudent').value = '12345';
    element('peerVideo').play = () => playGate.promise;
    const document = {
        hidden: false,
        getElementById: element,
        querySelector: () => ({ content: 'csrf' }),
        createElement: () => element('created'),
        addEventListener: (event, callback) => { listeners[event] = callback; },
    };
    const window = { addEventListener: (event, callback) => { listeners[event] = callback; } };
    const fetch = url => url === '/sessionsUrl'
        ? Promise.resolve({ ok: true, json: async () => ({ available: true, sessions: [{ id: 1, subject_code: 'TEST', remaining_vouches: 1 }] }) })
        : ticketGate.promise;
    vm.runInNewContext(script, {
        document, window, fetch, navigator: { mediaDevices: { getUserMedia: () => { cameraCalls++; return cameraGate.promise; } } },
        Option: function () {}, setInterval: () => 1, clearInterval() {},
    });
    return { document, listeners, element, get cameraCalls() { return cameraCalls; } };
}

const ticket = { verification_id: 'id', nonce: 'nonce', challenge: 'turn_left', expires_at: new Date(Date.now() + 60000).toISOString() };
const flush = async () => { for (let i = 0; i < 20; i++) await Promise.resolve(); };

test('hiding the page while the start request is pending cannot open the camera later', async () => {
    const ticketGate = deferred(), cameraGate = deferred(), playGate = deferred();
    const app = mount({ ticketGate, cameraGate, playGate });
    await flush();
    const start = app.element('peerStart').click();
    app.document.hidden = true;
    app.listeners.visibilitychange();
    ticketGate.resolve({ ok: true, json: async () => ticket });
    await start;
    assert.equal(app.cameraCalls, 0);
    assert.equal(app.element('peerCameraCard').hidden, true);
    assert.equal(app.element('peerStart').disabled, false);
});

test('hiding the page while camera permission is pending releases a late stream', async () => {
    const ticketGate = deferred(), cameraGate = deferred(), playGate = deferred();
    const app = mount({ ticketGate, cameraGate, playGate });
    await flush();
    const start = app.element('peerStart').click();
    ticketGate.resolve({ ok: true, json: async () => ticket });
    await flush();
    assert.equal(app.cameraCalls, 1);
    app.document.hidden = true;
    app.listeners.visibilitychange();
    let stops = 0;
    cameraGate.resolve({ getTracks: () => [{ stop: () => { stops++; } }] });
    await start;
    assert.equal(stops, 1);
    assert.equal(app.element('peerCameraCard').hidden, true);
    assert.equal(app.element('peerStart').disabled, false);
});

test('hiding the page while video playback is pending leaves the flow cancelled', async () => {
    const ticketGate = deferred(), cameraGate = deferred(), playGate = deferred();
    const app = mount({ ticketGate, cameraGate, playGate });
    await flush();
    const start = app.element('peerStart').click();
    ticketGate.resolve({ ok: true, json: async () => ticket });
    let stops = 0;
    cameraGate.resolve({ getTracks: () => [{ stop: () => { stops++; } }] });
    await flush();
    assert.equal(app.cameraCalls, 1);
    app.document.hidden = true;
    app.listeners.visibilitychange();
    playGate.resolve();
    await start;
    assert.equal(stops, 1);
    assert.equal(app.element('peerCameraCard').hidden, true);
    assert.equal(app.element('peerStart').disabled, false);
});
