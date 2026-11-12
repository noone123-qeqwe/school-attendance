const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const script = fs.readFileSync(path.join(__dirname, '../../public/js/camera-face-enrollment.js'), 'utf8');

function deferred() {
    let resolve;
    const promise = new Promise(done => { resolve = done; });
    return { promise, resolve };
}

function mount({ cameraGate = deferred(), response = { ok: true, json: async () => ({ success: true }) }, FaceDetector = null } = {}) {
    const elements = new Map();
    const windowListeners = {};
    const fetchCalls = [];
    const timers = [];
    let trackStops = 0;
    let focusedElement = null;
    const element = id => {
        if (!elements.has(id)) {
            elements.set(id, {
                id,
                style: { display: 'none', cssText: '' },
                disabled: false,
                textContent: '',
                listeners: {},
                dataset: { enrollmentUrl: '/profile/face-enrollment' },
                querySelectorAll: () => ['front', 'left', 'right'].map(pose => element('pose-' + pose)),
                querySelector: () => ({ textContent: '' }),
                setAttribute() {}, removeAttribute() {},
                click() { return this.listeners.click?.(); },
                addEventListener(event, callback) { this.listeners[event] = callback; },
                focus() { focusedElement = this; },
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
        hidden: false,
        getElementById: element,
        querySelector: () => ({ content: 'test-csrf-token' }),
        addEventListener() {},
    };
    const window = {
        FaceDetector,
        setTimeout(callback) { const timer = { callback, cleared: false }; timers.push(timer); return timer; },
        clearTimeout(timer) { if (timer) timer.cleared = true; },
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
        timers,
        navigator: { mediaDevices: { getUserMedia: () => cameraGate.promise } },
        fetch,
        FormData: FormDataMock,
    });

    return {
        cameraGate,
        element,
        fetchCalls,
        windowListeners,
        window,
        focusedElement: () => focusedElement,
        get trackStops() { return trackStops; },
        stream() { return { getTracks: () => [{ stop: () => { trackStops++; } }] }; },
        async advanceScan() {
            const timer = timers.find(item => !item.cleared && !item.fired);
            assert.ok(timer, 'an automatic scan should be scheduled');
            timer.fired = true;
            timer.callback();
            for (let i = 0; i < 30; i++) await Promise.resolve();
        },
    };
}

test('leaving registration while camera permission is pending stops the late stream', async () => {
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
    assert.match(app.element('cameraFaceStatus').textContent, /Capture closed/);
});

test('switching screens clears captures and ignores a late save response', async () => {
    const gate = deferred();
    const app = mount({ response: { ok: true, json: () => gate.promise } });
    const opening = app.element('cameraFaceStartBtn').listeners.click();
    app.cameraGate.resolve(app.stream());
    await opening;
    for (let i = 0; i < 3; i++) await app.element('cameraFaceCaptureBtn').listeners.click();
    const saving = app.element('cameraFaceSaveBtn').listeners.click();
    assert.equal(app.element('cameraFaceCancelBtn').disabled, true);
    app.windowListeners.pagehide();
    gate.resolve({ success: true, message: 'Late success' });
    await saving;
    assert.match(app.element('cameraFaceStatus').textContent, /Capture closed/);
    assert.equal(app.element('cameraFacePreviewWrap').style.display, 'none');
    await app.element('cameraFaceSaveBtn').listeners.click();
    assert.equal(app.fetchCalls.length, 1);
});

test('student must capture all three positions before submitting the camera enrollment', async () => {
    const app = mount();
    const startPromise = app.element('cameraFaceStartBtn').listeners.click();
    app.cameraGate.resolve(app.stream());
    await startPromise;

    assert.equal(app.element('cameraFacePreviewWrap').style.display, 'block');
    assert.equal(app.element('cameraFaceCaptureBtn').style.display, '');
    assert.equal(app.focusedElement(), app.element('cameraFaceCaptureBtn'));

    await app.element('cameraFaceCaptureBtn').listeners.click();
    await app.element('cameraFaceSaveBtn').listeners.click();
    assert.equal(app.fetchCalls.length, 0, 'One view must not complete registration');
    assert.equal(app.trackStops, 0, 'Camera stays open for the next position');
    assert.match(app.element('cameraFacePoseInstruction').textContent, /left/);
    await app.element('cameraFaceCaptureBtn').listeners.click();
    assert.match(app.element('cameraFacePoseInstruction').textContent, /right/);
    await app.element('cameraFaceCaptureBtn').listeners.click();
    assert.equal(app.trackStops, 1);
    assert.equal(app.element('cameraFaceSaveBtn').style.display, '');
    assert.equal(app.focusedElement(), app.element('cameraFaceSaveBtn'));

    await app.element('cameraFaceSaveBtn').listeners.click();

    assert.equal(app.fetchCalls.length, 1);
    assert.equal(app.fetchCalls[0][0], '/profile/face-enrollment');
    assert.equal(app.fetchCalls[0][1].headers['X-CSRF-TOKEN'], 'test-csrf-token');
    assert.deepEqual(app.fetchCalls[0][1].body.items.map(item => item[0]), ['face_images[front]', 'face_images[left]', 'face_images[right]']);
    assert.equal(app.element('cameraFacePreviewWrap').style.display, 'none');
    assert.match(app.element('cameraFaceStatus').textContent, /three face views were registered securely/);
    assert.equal(app.element('cameraFaceDone').hidden, false);
    assert.equal(app.element('cameraFaceStartBtn').hidden, true);
    assert.equal(app.focusedElement(), app.element('cameraFaceDone'));
});

test('pose validation errors keep the captures available for retry or retake', async () => {
    const app = mount({ response: { ok: false, json: async () => ({ message: 'Turn in opposite directions.' }) } });
    const opening = app.element('cameraFaceStartBtn').listeners.click();
    app.cameraGate.resolve(app.stream());
    await opening;
    for (let i = 0; i < 3; i++) await app.element('cameraFaceCaptureBtn').listeners.click();
    await app.element('cameraFaceSaveBtn').listeners.click();
    assert.match(app.element('cameraFaceStatus').textContent, /opposite directions/);
    assert.equal(app.element('cameraFaceRetakeBtn').disabled, false);
    assert.equal(app.element('cameraFaceSaveBtn').disabled, false);
    assert.equal(app.focusedElement(), app.element('cameraFaceSaveBtn'));
    app.element('cameraFaceCancelBtn').listeners.click();
    assert.equal(app.focusedElement(), app.element('cameraFaceStartBtn'));
    await app.element('cameraFaceSaveBtn').listeners.click();
    assert.equal(app.fetchCalls.length, 1, 'Canceled captures cannot be submitted');
});

test('unsupported on-device pose detection leaves manual capture available', async () => {
    const app = mount();
    const opening = app.element('cameraFaceStartBtn').listeners.click();
    app.cameraGate.resolve(app.stream());
    await opening;

    assert.equal(app.element('cameraFaceCaptureBtn').hidden, false);
    assert.match(app.element('cameraFaceStatus').textContent, /not available in this browser/i);
});

test('supported face detection automatically captures a centered front view and opposite turns', async () => {
    const face = offset => ({
        boundingBox: { x: 192, y: 120, width: 256, height: 300 },
        landmarks: [{ type: 'nose', locations: [{ x: 320 + (offset * 128), y: 260 }] }],
    });
    const offsets = [.01, .02, .01, .015, .24, .25, .23, .24, -.24, -.25, -.23, -.24];
    class Detector {
        constructor() { this.index = 0; }
        async detect() { return [face(offsets[Math.min(this.index++, offsets.length - 1)])]; }
    }
    const app = mount({ FaceDetector: Detector });
    const opening = app.element('cameraFaceStartBtn').listeners.click();
    app.cameraGate.resolve(app.stream());
    await opening;

    assert.equal(app.element('cameraFaceCaptureBtn').hidden, true, 'automatic mode should not ask for a tap per pose');
    for (let i = 0; i < offsets.length; i++) await app.advanceScan();

    assert.equal(app.element('cameraFaceSaveBtn').hidden, false);
    assert.equal(app.element('cameraFaceRetakeBtn').hidden, false);
    assert.equal(app.trackStops, 1, 'camera shuts off after the third view');
    assert.match(app.element('cameraFaceStatus').textContent, /Three views captured/);
    await app.element('cameraFaceSaveBtn').listeners.click();
    assert.deepEqual(app.fetchCalls[0][1].body.items.map(item => item[0]), ['face_images[front]', 'face_images[left]', 'face_images[right]']);
});

test('automatic scanning falls back to manual capture if the browser cannot report a nose landmark', async () => {
    class Detector {
        async detect() { return [{ boundingBox: { x: 192, y: 120, width: 256, height: 300 }, landmarks: [] }]; }
    }
    const app = mount({ FaceDetector: Detector });
    const opening = app.element('cameraFaceStartBtn').listeners.click();
    app.cameraGate.resolve(app.stream());
    await opening;
    await app.advanceScan();

    assert.equal(app.element('cameraFaceCaptureBtn').hidden, false);
    assert.match(app.element('cameraFaceStatus').textContent, /not supported by this camera/i);
});
