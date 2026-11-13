const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/qr/verify.blade.php'), 'utf8');
const start = blade.indexOf('function showFaceIntro()');
const end = blade.indexOf('function doFingerprint()', start);
const script = blade.slice(start, end).replace('await import(FACE_GUIDANCE_URL)', 'await loadGuidance()');

function deferred() {
    let resolve;
    const promise = new Promise(done => { resolve = done; });
    return { promise, resolve };
}

function mount({ camera = deferred(), guidance = {}, play = Promise.resolve() } = {}) {
    const elements = new Map();
    let stops = 0, closed = 0, started = 0, submitted = null;
    const element = id => {
        if (!elements.has(id)) {
            elements.set(id, { style: {}, textContent: '', play: () => play });
        }
        return elements.get(id);
    };
    const track = { stop: () => { stops++; }, addEventListener() {} };
    const context = {
        document: { getElementById: element, querySelectorAll: () => [], addEventListener() {} },
        window: { addEventListener() {} },
        navigator: { mediaDevices: { getUserMedia: () => camera.promise } },
        faceLighting: { start: () => { started++; }, stop() {} },
        faceAttempt: null, faceSubmitting: false, activeStream: null,
        AbortController, FACE_ADDITIONAL_FACTOR: 'none', QR_TOKEN: 'qr', CSRF: '',
        showMsg() {}, hideMsg() {},
        loadGuidance: async () => ({
            createDetector: async () => ({ close: () => { closed++; } }),
            captureSequence: async ({ onProgress }) => {
                for (let n = 0; n < 5; n++) onProgress(n + 1, ['front', 'left', 'up', 'right', 'down'][n]);
                return Array.from({ length: 14 }, (_, i) => 'frame' + i);
            },
            ...guidance,
        }),
        fetch: async () => ({ ok: true, json: async () => ({
            face_challenge: { id: 'issued', poses: ['front', 'left', 'up', 'right', 'down'], expires_in: 65 },
        }) }),
        submitAttendance: async data => { submitted = data; },
    };
    vm.runInNewContext(script, context);
    return {
        context, element,
        resolve: () => camera.resolve({ getTracks: () => [track], getVideoTracks: () => [track] }),
        get stops() { return stops; },
        get closed() { return closed; },
        get started() { return started; },
        get submitted() { return submitted; },
    };
}

test('late camera permission is released after closing the camera', async () => {
    const app = mount();
    const scan = app.context.startFaceScan();
    app.context.stopCamera();
    app.resolve();
    await scan;
    assert.equal(app.stops, 1);
    assert.equal(app.started, 0);
    assert.equal(app.submitted, null);
});

test('camera submits the ordered evidence automatically after five captures', async () => {
    const app = mount();
    const scan = app.context.startFaceScan();
    app.resolve();
    await scan;
    assert.equal(app.stops, 1);
    assert.equal(app.closed, 1);
    assert.equal(app.element('faceCaptureProgress').value, 5);
    assert.equal(app.element('faceLivenessStatus').textContent, 'Checking liveness and identity…');
    assert.equal(app.submitted.face_challenge, 'issued');
    assert.equal(app.submitted.live_frames.length, 14);
});

test('interrupted video releases the camera and permits retry', async () => {
    const app = mount({ guidance: { captureSequence: async () => { throw new Error('Camera interrupted'); } } });
    const scan = app.context.startFaceScan();
    app.resolve();
    await scan;
    assert.equal(app.stops, 1);
    assert.equal(app.closed, 1);
    assert.equal(app.submitted, null);
    assert.equal(app.element('btnRetryFace').hidden, false);
});

test('capture guidance recognizes all five directions and requires centered transitions', async () => {
    const { matchesPose, captureSteps } = await import('../../resources/js/attendance-face-guidance.js');
    const front = { yaw: 0.01, pitch: 0.54 };
    assert.equal(matchesPose(front, 'front', null), true);
    for (const [pose, yaw, pitch] of [
        ['left', 0.23, 0.54], ['right', -0.20, 0.54], ['up', 0.01, 0.39], ['down', 0.01, 0.70],
    ]) {
        assert.equal(matchesPose({ yaw, pitch }, pose, front), true);
        assert.equal(matchesPose(front, pose, front), false);
    }
    const steps = captureSteps(['front', 'down', 'right', 'up', 'left']);
    assert.equal(steps.filter(step => step.transition).length, 4);
    assert.equal(steps.reduce((sum, step) => sum + step.count, 0), 14);
});
