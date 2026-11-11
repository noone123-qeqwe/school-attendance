const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const blade = fs.readFileSync(path.join(__dirname, '../../resources/views/intro.blade.php'), 'utf8');
const code = blade.slice(blade.indexOf('const video '), blade.lastIndexOf('</script>'))
    .replace(/@json\(\$destUrl\)/g, '"/login"')
    .replace(/\{\{ \$mobileVideoUrl \}\}/g, '/videos/Mobile.mp4')
    .replace(/\{\{ \$desktopVideoUrl \}\}/g, '/videos/Desktop.mp4');

function mount({ mobile = true, ready = true, cleanupFails = false } = {}) {
    const ids = new Map(), documentListeners = {}, windowListeners = {}, timers = new Map(), navigations = [];
    let nextTimer = 0, pauses = 0, plays = 0;
    function element(id, interactive = false) {
        const classes = new Set();
        const el = {
            id, hidden: true, textContent: '', style: {}, listeners: {}, attributes: {},
            classList: { add: key => classes.add(key), contains: key => classes.has(key) },
            closest: selector => interactive ? (selector.includes('a,') ? el : null) : (selector === '[data-skip-intro]' ? el : null),
            addEventListener: (event, callback) => { el.listeners[event] = callback; },
            setAttribute: (key, value) => { el.attributes[key] = value; },
            removeAttribute: key => { delete el.attributes[key]; },
        };
        ids.set(id, el);
        return el;
    }
    const video = element('introVideo');
    video.hidden = false;
    video.currentSrc = mobile ? '/videos/Mobile.mp4' : '/videos/Desktop.mp4';
    video.src = video.currentSrc;
    video.currentTime = 0;
    video.duration = 8;
    video.readyState = ready ? 1 : 0;
    video.play = () => { plays++; return Promise.resolve(); };
    video.pause = () => { pauses++; if (cleanupFails) throw new Error('Media cleanup failed'); };
    const sources = [element('introVideoSrcMobile'), element('introVideoSrcDesktop'), element('introVideoSrc')];
    sources.forEach(source => { source.attributes.src = 'video.mp4'; });
    video.querySelectorAll = () => sources;
    video.load = () => {};
    const skip = element('skipBtn', true);
    for (const id of ['progressBar', 'introLogo', 'introStatus', 'introTagline', 'introRecovery', 'skipBtnLabel']) element(id);
    ids.get('introTagline').hidden = false;
    const body = element('body');
    const location = { replace: url => { navigations.push(['replace', url]); } };
    Object.defineProperty(location, 'href', { set: url => navigations.push(['href', url]) });
    vm.runInNewContext(code, {
        document: { body, getElementById: id => ids.get(id), addEventListener: (event, fn) => { documentListeners[event] = fn; } },
        window: {
            innerWidth: mobile ? 390 : 1440, screen: { width: mobile ? 390 : 1440 },
            matchMedia: () => ({ matches: mobile }), location,
            addEventListener: (event, fn) => { windowListeners[event] = fn; },
        },
        navigator: { userAgent: mobile ? 'Android' : 'Desktop' },
        setTimeout: (fn, delay) => { const id = ++nextTimer; timers.set(id, { fn, delay }); return id; },
        clearTimeout: id => timers.delete(id),
    });
    function click(target = skip) {
        const event = { target, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
        target.listeners.click?.(event);
        documentListeners.click(event);
        return event;
    }
    function runDelay(delay) {
        for (const [id, timer] of [...timers]) {
            if (timer.delay === delay) { timers.delete(id); timer.fn(); }
        }
    }
    return { ids, body, video, skip, sources, navigations, click, runDelay, documentListeners, windowListeners, timers,
        media: () => ({ pauses, plays }) };
}

for (const [screen, mobile] of [['mobile', true], ['desktop', false]]) {
    test(`${screen}: skipping starts one immediate navigation and leaves visible loading feedback`, () => {
        const app = mount({ mobile });
        app.click();
        assert.deepEqual(app.navigations, [['replace', '/login']]);
        assert.equal(app.body.classList.contains('intro-leaving'), true);
        assert.equal(app.ids.get('introStatus').hidden, false);
        assert.equal(app.ids.get('introLogo').hidden, false);
        assert.equal(app.ids.get('skipBtnLabel').textContent, 'Continue now');
        assert.equal(app.video.hidden, true);
        assert.equal(app.media().pauses, 1);
        assert.equal(app.sources.every(source => !source.attributes.src), true);
        assert.equal([...app.timers.values()].some(timer => timer.delay === 50), false);
    });
}

test('media events and timeout after skipping do not restart a pending navigation', () => {
    const app = mount();
    app.click();
    app.video.listeners.error();
    app.video.listeners.ended();
    app.runDelay(600);
    app.runDelay(12000);
    assert.equal(app.navigations.length, 1);
    app.runDelay(8000);
    assert.equal(app.ids.get('introRecovery').hidden, false);
    assert.equal(app.click().defaultPrevented, false, 'The real link remains usable for a manual retry');
});

test('skip still navigates if the video cannot be paused or unloaded', () => {
    const app = mount({ cleanupFails: true });
    app.click();
    assert.deepEqual(app.navigations, [['replace', '/login']]);
});

test('late video readiness cannot restart playback after skipping', () => {
    const app = mount({ ready: false });
    app.click();
    app.video.listeners.loadedmetadata();
    app.video.listeners.canplay();
    assert.equal(app.media().plays, 0);
});

test('PWA controls keep their click and keyboard behavior during the intro', () => {
    const app = mount();
    const target = { listeners: {}, closest: () => target };
    const event = app.click(target);
    app.documentListeners.keydown({ target, code: 'Enter', key: 'Enter' });
    assert.equal(event.defaultPrevented, false);
    assert.equal(app.navigations.length, 0);
});

test('intro-surface taps, keyboard skipping, and playback failure can each continue', () => {
    const tap = mount();
    tap.click(tap.video);
    assert.equal(tap.navigations.length, 1);
    const keyboard = mount();
    keyboard.documentListeners.keydown({ target: keyboard.body, code: 'Space', key: ' ', preventDefault() {} });
    assert.equal(keyboard.navigations.length, 1);
    const failure = mount();
    failure.video.listeners.error();
    assert.equal(failure.navigations.length, 1);
});
