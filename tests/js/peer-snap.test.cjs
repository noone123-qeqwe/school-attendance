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

function mount({ ticketGate, cameraGate, playGate, confirmGate, savedPending = false }) {
    const elements = new Map();
    const listeners = {};
    const storage = new Map(savedPending ? [['peer-snap-pending-confirmation', '1']] : []);
    let cameraCalls = 0;
    const element = id => {
        if (!elements.has(id)) elements.set(id, {
            value: '', hidden: false, disabled: false, textContent: '', children: [],
            addEventListener(event, callback) { this[event] = callback; },
            replaceChildren(...children) { this.children = children; if (id === 'peerSession') this.value = children[0]?.value || ''; },
            add(option) { this.children.push(option); },
            append(...children) { this.children.push(...children); },
            focus() { this.focused = true; },
        });
        return elements.get(id);
    };
    element('peerSession').value = '1';
    element('peerStudent').value = '12345';
    element('peerVideo').videoWidth = 640;
    element('peerVideo').videoHeight = 480;
    element('peerVideo').play = () => playGate.promise;
    const document = {
        hidden: false,
        getElementById: element,
        querySelector: () => ({ content: 'csrf' }),
        createElement: tag => tag === 'canvas'
            ? { width: 0, height: 0, getContext: () => ({ drawImage() {} }), toBlob: callback => callback({ size: 1024 }) }
            : { textContent: '' },
        addEventListener: (event, callback) => { listeners[event] = callback; },
    };
    const window = { isSecureContext: true, addEventListener: (event, callback) => { listeners[event] = callback; } };
    const fetch = url => {
        if (url === '/sessionsUrl') return Promise.resolve({ ok: true, json: async () => ({ available: true, sessions: [{ id: 1, subject_code: 'TEST', remaining_vouches: 1 }] }) });
        if (url === '/confirmUrl') return confirmGate?.promise;
        return ticketGate.promise;
    };
    vm.runInNewContext(script, {
        document, window, fetch, navigator: { mediaDevices: { getUserMedia: () => { cameraCalls++; return cameraGate.promise; } } },
        Option: function (text, value) { this.text = text; this.value = String(value); },
        FormData: class { constructor() { this.items = []; } append(...items) { this.items.push(items); } },
        sessionStorage: { getItem: key => storage.get(key) || null, setItem: (key, value) => storage.set(key, value), removeItem: key => storage.delete(key) },
        setInterval: () => 1, clearInterval() {}, setTimeout: callback => { Promise.resolve().then(callback); return 1; },
    });
    return { document, listeners, element, storage, get cameraCalls() { return cameraCalls; } };
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

test('denied camera access releases the flow and explains the manual attendance option', async () => {
    const ticketGate = deferred(), cameraGate = deferred(), playGate = deferred();
    const app = mount({ ticketGate, cameraGate, playGate });
    await flush();
    const start = app.element('peerStart').click();
    ticketGate.resolve({ ok: true, json: async () => ticket });
    await flush();
    cameraGate.resolve(Promise.reject(Object.assign(new Error('blocked'), { name: 'NotAllowedError' })));
    await start;
    assert.equal(app.element('peerCameraCard').hidden, true);
    assert.equal(app.element('peerStart').disabled, false);
    assert.match(app.element('peerResult').children[1].textContent, /Allow it in browser settings/);
    assert.match(app.element('peerResult').children[1].textContent, /manual attendance check/);
});

test('camera track ending during preview closes the stream and permits retry', async () => {
    const ticketGate = deferred(), cameraGate = deferred(), playGate = deferred();
    const app = mount({ ticketGate, cameraGate, playGate });
    await flush();
    const start = app.element('peerStart').click();
    ticketGate.resolve({ ok: true, json: async () => ticket });
    let ended, stops = 0;
    cameraGate.resolve({ getTracks: () => [{ stop: () => { stops++; }, addEventListener: (_, handler) => { ended = handler; } }] });
    playGate.resolve();
    await start;
    assert.equal(app.element('peerCameraCard').hidden, false);
    ended();
    assert.equal(stops, 1);
    assert.equal(app.element('peerCameraCard').hidden, true);
    assert.equal(app.element('peerStart').disabled, false);
    assert.match(app.element('peerResult').children[1].textContent, /camera stopped/);
});

test('successful verification releases camera and clears only the student number', async () => {
    const ticketGate = deferred(), cameraGate = deferred(), playGate = deferred(), confirmGate = deferred();
    const app = mount({ ticketGate, cameraGate, playGate, confirmGate });
    await flush();
    const start = app.element('peerStart').click();
    ticketGate.resolve({ ok: true, json: async () => ticket });
    let stops = 0;
    cameraGate.resolve({ getTracks: () => [{ stop: () => { stops++; } }] });
    playGate.resolve();
    await start;
    const capture = app.element('peerCapture').click();
    await flush();
    assert.equal(stops, 1, 'camera is released before the server responds');
    assert.equal(app.storage.get('peer-snap-pending-confirmation'), '1');
    confirmGate.resolve({ ok: true, status: 200, json: async () => ({ status: 'Present', attendance_id: 7, student_name: 'Classmate' }) });
    await capture;
    assert.equal(app.element('peerStudent').value, '');
    assert.equal(app.element('peerSession').value, '1');
    assert.equal(app.element('peerCameraCard').hidden, true);
    assert.equal(app.element('peerStart').disabled, true, 'another classmate must enter their own number');
    assert.equal(app.storage.has('peer-snap-pending-confirmation'), false);
    assert.match(app.element('peerResult').children[1].textContent, /ready for the next classmate/);
});

test('an interrupted confirmation gives a check-result warning after refresh', async () => {
    const app = mount({ ticketGate: deferred(), cameraGate: deferred(), playGate: deferred(), savedPending: true });
    await flush();
    assert.equal(app.storage.has('peer-snap-pending-confirmation'), false);
    assert.match(app.element('peerResult').children[1].textContent, /Ask your instructor to check attendance/);
});
