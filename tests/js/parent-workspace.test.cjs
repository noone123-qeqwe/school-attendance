const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const workspace = fs.readFileSync(path.join(__dirname, '../../public/js/parent-workspace.js'), 'utf8');
const calendar = fs.readFileSync(path.join(__dirname, '../../resources/views/parent/calendar.blade.php'), 'utf8');
const calendarText = fs.readFileSync(path.join(__dirname, '../../resources/views/partials/parent-calendar-text.blade.php'), 'utf8');

// DOM fixtures verify state transitions; visual browser checks remain a separate step.
function fixture({ admin = false, account = '7', saved = null, contextTemplate = null } = {}) {
    const ids = new Map(), requests = [], storage = new Map();
    if (saved !== null) storage.set(`parentSelectedChild:${account}`, saved);
    let refreshes = 0;
    const document = { activeElement: null };
    function element() {
        const events = {}, attributes = {}, classes = new Set();
        return {
            dataset: {}, style: {}, hidden: false, disabled: false, textContent: '', value: '', clicks: 0,
            classList: { contains: name => classes.has(name), toggle(name, on) { on ? classes.add(name) : classes.delete(name); } },
            addEventListener(name, fn) { (events[name] ??= []).push(fn); },
            fire(name, event = {}) { return Promise.all((events[name] ?? []).map(fn => fn({ target: this, preventDefault() {}, ...event }))); },
            click() { if (!this.disabled) { this.clicks++; return this.fire('click'); } },
            focus() { document.activeElement = this; },
            setAttribute: (name, value) => { attributes[name] = value; }, getAttribute: name => attributes[name],
            removeAttribute: name => { delete attributes[name]; }, hasAttribute: name => name in attributes,
            querySelectorAll: () => [], querySelector: () => null, matches: () => false,
        };
    }
    document.body = element(); document.body.classList.toggle('parent-portal', !admin);
    const panels = ['11', '12'].map(id => { const node = element(); node.dataset.parentChildPanel = id; return node; });
    const tabs = ['11', '12'].map(id => { const node = element(); node.dataset.parentChildTab = id; return node; });
    const picker = element(), contextPicker = element(), main = element(), button = element(), feedback = element();
    if (contextTemplate) contextPicker.dataset.parentChildContextTemplate = contextTemplate;
    button.dataset.readUrl = '/parent/notifications/read';
    const counts = [element(), element()], unread = element(), newBadge = element();
    const field = element(), verify = element();
    ids.set('mainContent', main); ids.set('markAllReadBtn', button); ids.set('parentNotificationFeedback', feedback);
    ids.set('instant_code', field); ids.set('btn-verify-code', verify);
    document.getElementById = id => ids.get(id) ?? null;
    document.querySelector = selector => selector.includes('auth-user-id') ? { content: account } : selector.includes('csrf-token') ? { content: 'fixture-token' } : selector.includes('child-context-switch') && contextTemplate ? contextPicker : selector.includes('child-picker') ? picker : null;
    document.querySelectorAll = selector => selector.includes('child-panel') ? panels : selector.includes('child-tab') ? tabs : selector.includes('unread-count') ? [...counts, newBadge] : selector.includes('alert-unread') ? [unread] : [];
    const context = { document, window: { location: { origin: 'https://attendance.test', pathname: '/parent/child/11', href: 'https://attendance.test/parent/child/11' }, history: { replaceState() {} }, refreshNotificationBell() { refreshes++; } }, localStorage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value) }, fetch: (...args) => new Promise(resolve => requests.push({ args, resolve })) };
    vm.runInNewContext(workspace, context);
    return { ...context, panels, tabs, picker, contextPicker, main, button, feedback, counts, unread, newBadge, field, verify, requests, storage, get refreshes() { return refreshes; } };
}

test('mobile child selection updates visible content and the matching desktop tab', async () => {
    const app = fixture();
    app.picker.value = '12'; await app.picker.fire('change');
    assert.deepEqual(app.panels.map(panel => panel.hidden), [true, false]);
    assert.deepEqual(app.panels.map(panel => panel.inert), [true, false]);
    assert.equal(app.tabs[1].getAttribute('aria-selected'), 'true');
    assert.equal(app.tabs[0].tabIndex, -1);
    assert.equal(app.storage.get('parentSelectedChild:7'), '12');
});

test('child tabs support arrow and endpoint keys without accepting an unlinked child', async () => {
    const app = fixture();
    await app.tabs[0].fire('keydown', { key: 'End' });
    assert.equal(app.document.activeElement, app.tabs[1]);
    assert.equal(app.picker.value, '12');
    assert.equal(app.window.switchDashboardChild('999'), false);
    assert.equal(app.panels[1].hidden, false);
    await app.tabs[1].fire('keydown', { key: 'Home' });
    assert.equal(app.panels[0].hidden, false);
});

test('a stale saved child falls back to a linked student and storage is separated by account', () => {
    const app = fixture({ account: '8', saved: '999' });
    assert.equal(app.picker.value, '11');
    assert.equal(app.storage.get('parentSelectedChild:8'), '11');
    assert.equal(app.storage.has('parentSelectedChild:7'), false);
});

test('a child-detail picker navigates to the selected child rather than staying on the old record page', async () => {
    const app = fixture({ contextTemplate: 'https://attendance.test/parent/child/__parent_child__' });
    app.contextPicker.value = '12';
    await app.contextPicker.fire('change');
    assert.equal(app.window.location.href, 'https://attendance.test/parent/child/12');
});

test('mark-read waits for success and prevents duplicate requests', async () => {
    const app = fixture();
    const pending = app.button.click();
    await app.button.click();
    assert.equal(app.requests.length, 1);
    assert.equal(app.button.disabled, true);
    assert.equal(app.newBadge.hidden, false);
    app.requests[0].resolve({ ok: true, json: async () => ({ success: true }) });
    await pending;
    assert.ok(app.counts.every(count => count.hidden));
    assert.equal(app.unread.dataset.parentAlertUnread, 'false');
    assert.equal(app.button.disabled, false);
    assert.equal(app.refreshes, 1);
    assert.equal(app.requests[0].args[1].headers['X-CSRF-TOKEN'], 'fixture-token');
});

test('failed mark-read retains unread indicators and allows retry', async () => {
    const app = fixture();
    const pending = app.button.click();
    app.requests[0].resolve({ ok: false, json: async () => ({ message: 'Failed' }) });
    await pending;
    assert.equal(app.newBadge.hidden, false);
    assert.equal(app.button.disabled, false);
    assert.match(app.feedback.textContent, /try again/);
    assert.equal(app.refreshes, 0);
});

test('link codes can be submitted with Enter using the existing action button', async () => {
    const app = fixture();
    await app.field.fire('keydown', { key: 'Enter' });
    assert.equal(app.verify.clicks, 1);
});

test('parent enhancements never attach to another role', () => {
    const app = fixture({ admin: true });
    assert.equal(app.window.switchDashboardChild, undefined);
    assert.equal(app.storage.size, 0);
});

test('calendar text is encoded and event colors cannot inject markup', () => {
    const context = {}; vm.createContext(context); vm.runInContext(calendarText, context);
    assert.equal(context.parentEscapeHtml(`<img src="x"> & O'Brien`), '&lt;img src=&quot;x&quot;&gt; &amp; O&#39;Brien');
    assert.equal(context.parentCalendarColor('#cfa46f'), '#cfa46f');
    assert.equal(context.parentCalendarColor('red;" onmouseover="attack()'), '#8b5cf6');
});

function calendarLoader() {
    const start = calendar.indexOf('function scalFetchEvents('), end = calendar.indexOf('/* ── Derive dominant status', start);
    assert.ok(start > 0 && end > start);
    const requests = [], timers = [], feedback = { textContent: '' }, retry = { hidden: true };
    const context = { scalChildId: 11, scalRequestId: 0, scalFetchController: null, scalEventMap: {},
        document: { getElementById: id => id === 'parentCalendarRetry' ? retry : feedback }, AbortController: class { constructor() { this.signal = {}; } abort() { this.signal.aborted = true; } },
        setTimeout: callback => { timers.push(callback); return timers.length; }, clearTimeout() {},
        fetch: (url, options) => new Promise((resolve, reject) => requests.push({ url, options, resolve, reject })),
    };
    vm.createContext(context); vm.runInContext(calendar.slice(start, end), context);
    return { context, requests, timers, feedback, retry };
}
const settle = () => new Promise(resolve => setImmediate(resolve));

test('a slower calendar response cannot replace the newly selected child', async () => {
    const app = calendarLoader(), calls = [];
    app.context.scalFetchEvents(2026, 11, () => calls.push('first child'));
    app.context.scalChildId = 12;
    app.context.scalFetchEvents(2026, 11, () => calls.push('second child'));
    assert.equal(app.requests[0].options.signal.aborted, true);
    app.requests[1].resolve({ ok: true, json: async () => [{ start: '2026-11-11', title: 'Second child record' }] });
    await settle();
    app.requests[0].resolve({ ok: true, json: async () => [{ start: '2026-11-11', title: 'Wrong older response' }] });
    await settle();
    assert.deepEqual(calls, ['second child']);
    assert.equal(app.context.scalEventMap['2026-11-11'][0].title, 'Second child record');
});

test('a calendar failure removes prior child data and gives a useful retry message', async () => {
    const app = calendarLoader();
    app.context.scalEventMap['2026-11-10'] = [{ title: 'Old record' }];
    let rendered = false;
    app.context.scalFetchEvents(2026, 11, () => { rendered = true; });
    app.requests[0].resolve({ ok: false });
    await settle();
    assert.deepEqual(Object.keys(app.context.scalEventMap), []);
    assert.equal(rendered, true);
    assert.match(app.feedback.textContent, /could not load/);
    assert.equal(app.retry.hidden, false);
});

test('a calendar timeout ends the loading state and exposes retry', async () => {
    const app = calendarLoader();
    let rendered = false;
    app.context.scalFetchEvents(2026, 11, () => { rendered = true; });
    app.timers[0]();
    app.requests[0].reject(Object.assign(new Error('Aborted'), { name: 'AbortError' }));
    await settle();
    assert.equal(app.requests[0].options.signal.aborted, true);
    assert.equal(rendered, true);
    assert.equal(app.retry.hidden, false);
});

test('school-calendar child changes keep the surrounding parent navigation on that same child', () => {
    const start = calendar.indexOf('function scalSelectChild('), end = calendar.indexOf('function scalSwitchChild(', start);
    assert.ok(start > 0 && end > start);
    const classList = { toggle() {} };
    const pickerItem = { getAttribute: name => ({ 'data-child-id': '12' })[name], classList, querySelector: () => null };
    const navLink = { href: 'https://attendance.test/parent/attendance-calendar' };
    const scheduleLink = { href: 'https://attendance.test/parent/schedule' };
    const fields = {
        activeStudentName: { textContent: '' }, activeStudentAvatar: { textContent: '' }, activeStudentSub: { textContent: '' },
        parentCalendarScheduleLink: scheduleLink,
    };
    const context = {
        scalChildId: 11,
        window: { location: { href: 'https://attendance.test/parent/calendar?child_id=11', origin: 'https://attendance.test' }, history: { replaceState() {} } },
        URL,
        document: {
            getElementById: id => fields[id] ?? null,
            querySelectorAll: selector => selector === '.scal-picker-item' ? [pickerItem] : selector === '[data-parent-child-context]' ? [navLink] : [],
        },
        scalLoadAndRender() {},
    };
    vm.createContext(context); vm.runInContext(calendar.slice(start, end), context);
    context.scalSelectChild({ getAttribute: name => ({ 'data-name': 'Second child', 'data-initials': 'SC', 'data-number': '5558872' })[name] }, 12);
    assert.match(scheduleLink.href, /child_id=12/);
    assert.match(navLink.href, /child_id=12/);
});

test('school calendar dialogs render special-character names and titles as text', () => {
    const nodes = new Map();
    const document = { getElementById(id) { if (id === 'daySummaryModal') return null; if (!nodes.has(id)) nodes.set(id, { style: {}, textContent: '', innerHTML: '', insertAdjacentHTML(_position, html) { this.innerHTML += html; } }); return nodes.get(id); } };
    const payload = '<img src=x onerror=attack()>';
    const context = { document, window: {}, scalChildCount: 2, scalEventMap: { '2026-11-11': [
        { type: 'attendance', status: 'Present', student_name: payload, subject_name: payload, time_string: payload, instructor_name: payload },
        { type: 'event', title: payload, color: 'red;" onmouseover="attack()' },
    ] } };
    const start = calendar.indexOf('function scalDayClick('), end = calendar.indexOf('function closeDaySummaryModal(', start);
    vm.createContext(context); vm.runInContext(calendarText + '\n' + calendar.slice(start, end), context);
    context.scalDayClick('2026-11-11');
    const html = nodes.get('daySummaryContent').innerHTML;
    assert.equal(html.includes(payload), false);
    assert.match(html, /&lt;img src=x onerror=attack\(\)&gt;/);
    assert.equal(html.includes('red;" onmouseover='), false);
});
