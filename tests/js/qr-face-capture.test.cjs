const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/qr/verify.blade.php'), 'utf8');
const startIndex = blade.indexOf('function startFaceScan()');
const endIndex = blade.indexOf('async function captureAndVerifyFace()', startIndex);
assert.notEqual(startIndex, -1, 'QR face camera flow must exist');
assert.notEqual(endIndex, -1, 'face verification must follow camera setup');
const script = blade.slice(startIndex, endIndex);

function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((done, fail) => { resolve = done; reject = fail; });
    return { promise, resolve, reject };
}

function mount(cameraGate, playGate = { promise: Promise.resolve() }) {
    const elements = new Map();
    const statuses = [];
    const messages = [];
    let trackStops = 0;
    let playCalls = 0;
    const element = id => {
        if (!elements.has(id)) {
            elements.set(id, {
                id,
                style: { display: '' },
                className: '',
                innerHTML: '',
                disabled: false,
                srcObject: null,
                onloadedmetadata: null,
                play() { playCalls++; return playGate.promise; },
                getTracks: () => [{ stop: () => { trackStops++; } }],
            });
        }
        return elements.get(id);
    };
    const context = {
        activeStream: null,
        currentFacingMode: 'user',
        faceCameraRequestId: 0,
        faceVerificationInProgress: false,
        faceLighting: null,
        document: { getElementById: element },
        navigator: { mediaDevices: { getUserMedia: () => cameraGate.promise } },
        setCameraStatus: (message, state) => statuses.push({ message, state }),
        showMsg: (type, message) => messages.push({ type, message }),
        hideMsg() {},
        HAS_FINGERPRINT: false,
        console: { error() {} },
        Promise,
        setTimeout() { throw new Error('The face camera must not capture automatically.'); },
        clearTimeout() {},
    };
    vm.runInNewContext(script, context);
    return {
        context,
        statuses,
        messages,
        element,
        get trackStops() { return trackStops; },
        get playCalls() { return playCalls; },
        stream() { return { getTracks: () => [{ stop: () => { trackStops++; } }] }; },
    };
}

const flush = async () => { for (let i = 0; i < 12; i++) await Promise.resolve(); };

test('a late camera permission response is stopped after the scan is cancelled', async () => {
    const cameraGate = deferred();
    const app = mount(cameraGate);

    app.context.startFaceScan();
    app.context.stopCamera();
    cameraGate.resolve(app.stream());
    await flush();

    assert.equal(app.trackStops, 1);
    assert.equal(app.context.activeStream, null);
    assert.equal(app.element('faceCameraVideo').srcObject, null);
    assert.equal(app.playCalls, 0);
});

test('capture stays disabled until video playback is ready and waits for the student to tap', async () => {
    const cameraGate = deferred();
    const playGate = deferred();
    const app = mount(cameraGate, playGate);
    const capture = app.element('btnCaptureFace');

    app.context.startFaceScan();
    cameraGate.resolve(app.stream());
    await flush();

    const video = app.element('faceCameraVideo');
    assert.equal(typeof video.onloadedmetadata, 'function');
    video.onloadedmetadata();
    assert.equal(app.playCalls, 1);
    assert.equal(capture.disabled, true);

    playGate.resolve();
    await flush();

    assert.equal(capture.disabled, false);
    assert.match(app.statuses.at(-1).message, /tap Verify My Face/i);
});

test('video playback failure closes the stream and exposes a retry control', async () => {
    const cameraGate = deferred();
    const playGate = deferred();
    const app = mount(cameraGate, playGate);

    app.context.startFaceScan();
    cameraGate.resolve(app.stream());
    await flush();
    app.element('faceCameraVideo').onloadedmetadata();
    playGate.reject(new Error('playback failed'));
    await flush();

    assert.equal(app.trackStops, 1);
    assert.equal(app.element('faceCameraVideo').srcObject, null);
    assert.equal(app.element('btnRetryFace').style.display, 'block');
    assert.equal(app.messages.at(-1).type, 'err');
});
