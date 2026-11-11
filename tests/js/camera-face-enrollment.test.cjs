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

function mount({ cameraGate = deferred(), response = { ok: true, json: async () => ({ success: true }) } } = {}) {
    const elements = new Map();
    const windowListeners = {};
    const fetchCalls = [];
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
        getElementById: element,
        querySelector: () => ({ content: 'test-csrf-token' }),
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
        window,
        focusedElement: () => focusedElement,
        get trackStops() { return trackStops; },
        stream() { return { getTracks: () => [{ stop: () => { trackStops++; } }] }; },
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
    for (let i = 0; i < 3; i++) app.element('cameraFaceCaptureBtn').listeners.click();
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

    app.element('cameraFaceCaptureBtn').listeners.click();
    await app.element('cameraFaceSaveBtn').listeners.click();
    assert.equal(app.fetchCalls.length, 0, 'One view must not complete registration');
    assert.equal(app.trackStops, 0, 'Camera stays open for the next position');
    assert.match(app.element('cameraFacePoseInstruction').textContent, /left/);
    app.element('cameraFaceCaptureBtn').listeners.click();
    assert.match(app.element('cameraFacePoseInstruction').textContent, /right/);
    app.element('cameraFaceCaptureBtn').listeners.click();
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
    for (let i = 0; i < 3; i++) app.element('cameraFaceCaptureBtn').listeners.click();
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
