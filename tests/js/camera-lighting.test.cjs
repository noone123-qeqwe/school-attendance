const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const script = fs.readFileSync(path.join(__dirname, '../../public/js/camera-lighting.js'), 'utf8');

function deferred() {
    let resolve;
    const promise = new Promise(done => { resolve = done; });
    return { promise, resolve };
}
function mount(capabilities = {}, apply = async () => {}) {
    const nodes = new Map(), windowEvents = {}, documentEvents = {};
    const element = name => {
        if (!nodes.has(name)) nodes.set(name, {
            hidden: false, disabled: false, value: name === 'level' ? '60' : '0', attributes: {}, events: {},
            style: { setProperty(name, value) { this[name] = value; } },
            setAttribute(name, value) { this.attributes[name] = value; },
            addEventListener(name, callback) { this.events[name] = callback; },
        });
        return nodes.get(name);
    };
    const root = { hidden: true, querySelector: selector => element(selector.slice('[data-light-'.length, -1)) };
    const document = {
        hidden: false, body: { appendChild() {} },
        addEventListener(name, callback) { documentEvents[name] = callback; },
    };
    const window = {
        addEventListener(name, callback) { windowEvents[name] = callback; },
        requestAnimationFrame(callback) { callback(); },
        setTimeout, clearTimeout,
    };
    const calls = [], trackEvents = {};
    const track = {
        kind: 'video', readyState: 'live',
        getCapabilities: () => capabilities,
        getSettings: () => ({ exposureCompensation: 0 }),
        applyConstraints: async value => { calls.push(value.advanced[0]); await apply(value.advanced[0]); },
        addEventListener(name, callback) { trackEvents[name] = callback; },
    };
    const stream = { getVideoTracks: () => [track] };
    vm.runInNewContext(script, { window, document });
    const api = window.CameraLighting.mount(root);
    return { api, root, element, document, documentEvents, windowEvents, track, trackEvents, stream, calls,
        toggle: () => element('toggle').events.click(),
        exposure(value) { element('exposure').value = value; element('exposure').events.input(); },
        level(value) { element('level').value = value; element('level').events.input(); },
    };
}

test('front cameras without a torch have an adjustable screen fill', async () => {
    const app = mount();
    assert.equal(app.root.hidden, true);
    app.api.start(app.stream);
    assert.equal(app.root.hidden, false);
    assert.equal(app.element('fill').hidden, true);
    assert.equal(app.element('exposure-group').hidden, true);
    app.toggle();
    app.level('85');
    await app.api.settle();
    assert.equal(app.element('fill').hidden, false);
    assert.equal(app.element('fill').style['--camera-fill-opacity'], '0.85');
    assert.equal(app.element('toggle').attributes['aria-pressed'], 'true');
    assert.equal(app.calls.length, 0, 'Screen fill must work without unsupported hardware calls');
    app.toggle();
    assert.equal(app.element('fill').hidden, true);
});

test('supported torch switches on and off and exposure uses the camera range', async () => {
    const app = mount({ torch: true, exposureCompensation: { min: -2, max: 2, step: 0.5 } });
    app.api.start(app.stream);
    assert.equal(app.element('exposure-group').hidden, false);
    app.toggle();
    await app.api.settle();
    assert.equal(app.calls[0].torch, true);
    app.exposure('1.24');
    await app.api.settle();
    assert.equal(app.calls.at(-1).exposureCompensation, 1);
    assert.equal(app.calls.at(-1).torch, true, 'Exposure updates preserve the enabled torch');
    app.exposure('99');
    await app.api.settle();
    assert.equal(app.calls.at(-1).exposureCompensation, 2);
    app.toggle();
    await app.api.settle();
    assert.equal(app.calls.at(-1).torch, false);
    assert.equal(app.calls.at(-1).exposureCompensation, 2);
});

test('rejected hardware controls fall back to screen light without blocking capture', async () => {
    const app = mount({ torch: true, exposureCompensation: { min: -1, max: 1, step: 0.1 } }, async () => { throw new Error('Unsupported'); });
    app.api.start(app.stream); app.toggle();
    await app.api.settle();
    assert.equal(app.element('fill').hidden, false);
    assert.equal(app.element('exposure-group').hidden, true);
    assert.match(app.element('help').textContent, /Screen light is on/);
});

test('a silently ignored torch setting also falls back to screen light', async () => {
    const app = mount({ torch: true });
    app.track.getSettings = () => ({ torch: false });
    app.api.start(app.stream); app.toggle();
    await app.api.settle();
    assert.equal(app.element('fill').hidden, false);
    assert.match(app.element('help').textContent, /Screen light is on/);
    assert.equal(app.calls.at(-1).torch, false);
});

test('late torch enable is followed by off when the camera is closed', async () => {
    const gate = deferred();
    const app = mount({ torch: true }, change => change.torch ? gate.promise : Promise.resolve());
    app.api.start(app.stream); app.toggle();
    await Promise.resolve(); await Promise.resolve();
    app.api.stop();
    assert.equal(app.element('fill').hidden, true);
    gate.resolve();
    await app.api.settle();
    assert.equal(app.calls.at(-1).torch, false);
    assert.equal(app.root.hidden, true);
});

test('capture locks controls and waits for pending camera adjustments', async () => {
    const gate = deferred();
    const app = mount({ torch: true }, change => change.torch ? gate.promise : Promise.resolve());
    app.api.start(app.stream); app.toggle(); app.api.lock(true);
    let settled = false;
    const waiting = app.api.settle().then(() => { settled = true; });
    await Promise.resolve(); await Promise.resolve();
    assert.equal(settled, false);
    app.toggle(); app.level('10');
    assert.equal(app.element('toggle').attributes['aria-pressed'], 'true');
    assert.equal(app.element('toggle').disabled, true);
    gate.resolve(); await waiting;
    app.api.lock(false);
    assert.equal(app.element('toggle').disabled, false);
});

test('rapid slider changes coalesce to the latest exposure', async () => {
    const app = mount({ exposureCompensation: { min: -2, max: 2, step: 0.1 } });
    app.api.start(app.stream);
    for (const value of ['0.1', '0.2', '0.3', '0.4']) app.exposure(value);
    await app.api.settle();
    assert.equal(app.calls.length, 1);
    assert.ok(Math.abs(app.calls[0].exposureCompensation - 0.4) < 0.0001);
});

test('backgrounding turns light off and returning keeps controls usable', async () => {
    const app = mount({ torch: true });
    app.api.start(app.stream); app.toggle(); await app.api.settle();
    app.document.hidden = true; app.documentEvents.visibilitychange();
    await app.api.settle();
    assert.equal(app.element('fill').hidden, true);
    assert.equal(app.calls.at(-1).torch, false);
    app.document.hidden = false; app.documentEvents.visibilitychange();
    app.toggle(); await app.api.settle();
    assert.equal(app.element('fill').hidden, false);
    app.windowEvents.pagehide(); await app.api.settle();
    assert.equal(app.root.hidden, true);
    assert.equal(app.element('fill').hidden, true);
});

test('switching cameras resets capabilities and an ended track hides the light', async () => {
    const app = mount({ torch: true });
    app.api.start(app.stream); app.toggle(); await app.api.settle();
    const front = { kind: 'video', getCapabilities: () => ({}), addEventListener() {} };
    app.api.start({ getVideoTracks: () => [front] }); await app.api.settle();
    assert.equal(app.element('fill').hidden, true);
    app.toggle(); await app.api.settle();
    assert.equal(app.element('fill').hidden, false);
    app.api.start(app.stream);
    app.trackEvents.ended(); await app.api.settle();
    assert.equal(app.root.hidden, true);
});
