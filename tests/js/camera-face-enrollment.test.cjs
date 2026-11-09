const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/settings.blade.php'), 'utf8');
const startMarker = '// ── Camera-based face enrollment for attendance ──';
const endMarker = '// ── Device Passkey Registration ──';
const startIndex = blade.indexOf(startMarker);
const endIndex = blade.indexOf(endMarker, startIndex);
assert.notEqual(startIndex, -1, 'camera enrollment script marker must exist');
assert.notEqual(endIndex, -1, 'device passkey script marker must follow camera enrollment');
const script = blade.slice(startIndex, endIndex)
    .replace(/{{\s*route\("profile\.face\.enroll"\)\s*}}/g, '/profile/face-enrollment')
    .replace(/{{\s*csrf_token\(\)\s*}}/g, 'test-csrf-token');

function deferred() {
    let resolve;
    const promise = new Promise(done => { resolve = done; });
    return { promise, resolve };
}

function mount({ cameraGate = deferred(), response = { ok: true, json: async () => ({ success: true }) } } = {}) {
    const elements = new Map();
    const windowListeners = {};
    const fetchCalls = [];
    let trackStops = 0;
    const element = id => {
        if (!elements.has(id)) {
            elements.set(id, {
                id,
                style: { display: 'none', cssText: '' },
                disabled: false,
                textContent: '',
                listeners: {},
                addEventListener(event, callback) { this.listeners[event] = callback; },
                focus() {},
                play: async () => {},
                getContext: () => ({ drawImage() {} }),
                toBlob: callback => callback({ size: 1024, type: 'image/jpeg' }),
                getTracks: () => [{ stop: () => { trackStops++; } }],
                videoWidth: 640,
                videoHeight: 480,
                srcObject: null,
            });
        }
        return elements.get(id);
    };
    const document = {
        readyState: 'complete',
        getElementById: element,
    };
    const window = {
        addEventListener(event, callback) { windowListeners[event] = callback; },
    };
    const fetch = async (...args) => {
        fetchCalls.push(args);
        return response;
    };
    class FormDataMock {
        constructor() { this.items = []; }
        append(...items) { this.items.push(items); }
    }

    vm.runInNewContext(script, {
        document,
        window,
        navigator: { mediaDevices: { getUserMedia: () => cameraGate.promise } },
        fetch,
        FormData: FormDataMock,
    });

    return {
        cameraGate,
        element,
        fetchCalls,
        windowListeners,
        get trackStops() { return trackStops; },
        stream() { return { getTracks: () => [{ stop: () => { trackStops++; } }] }; },
    };
}

test('leaving settings while camera permission is pending stops the late stream', async () => {
    const app = mount();
    const start = app.element('cameraFaceStartBtn');
    const startPromise = start.listeners.click();

    assert.equal(start.disabled, true);
    app.windowListeners.pagehide();
    assert.equal(start.disabled, false);

    app.cameraGate.resolve(app.stream());
    await startPromise;

    assert.equal(app.trackStops, 1);
    assert.equal(app.element('cameraFaceVideo').srcObject, null);
    assert.equal(app.element('cameraFacePreviewWrap').style.display, 'none');
    assert.equal(app.element('cameraFaceCaptureBtn').style.display, 'none');
    assert.match(app.element('cameraFaceStatus').textContent, /camera was closed when you left Settings/i);
});

test('student can capture and submit a camera face image through the enrollment flow', async () => {
    const app = mount();
    const startPromise = app.element('cameraFaceStartBtn').listeners.click();
    app.cameraGate.resolve(app.stream());
    await startPromise;

    assert.equal(app.element('cameraFacePreviewWrap').style.display, 'block');
    assert.equal(app.element('cameraFaceCaptureBtn').style.display, '');

    app.element('cameraFaceCaptureBtn').listeners.click();
    assert.equal(app.trackStops, 1);
    assert.equal(app.element('cameraFaceSaveBtn').style.display, '');

    await app.element('cameraFaceSaveBtn').listeners.click();

    assert.equal(app.fetchCalls.length, 1);
    assert.equal(app.fetchCalls[0][0], '/profile/face-enrollment');
    assert.equal(app.fetchCalls[0][1].headers['X-CSRF-TOKEN'], 'test-csrf-token');
    assert.equal(app.element('cameraFacePreviewWrap').style.display, 'none');
    assert.match(app.element('cameraFaceStatus').textContent, /Face registered securely/);
});
