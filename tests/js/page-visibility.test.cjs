const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const layout = fs.readFileSync(path.join(__dirname, '../../resources/views/layouts/app.blade.php'), 'utf8');
const start = layout.indexOf('// ── Seamless Page Transitions ──');
const end = layout.indexOf("document.addEventListener('DOMContentLoaded'", start);
assert.ok(start !== -1 && end > start, 'The shared page visibility script must be present');

function page() {
    const classes = new Set(['page-enter']);
    const listeners = {};
    const pageElement = { classList: { add: value => classes.add(value), remove: value => classes.delete(value) } };
    const register = (name, listener) => (listeners[name] ??= []).push(listener);
    const anchor = { getAttribute: () => '/my-classes', hasAttribute: () => false };
    const window = { innerWidth: 390, location: { origin: 'https://attendance.test', pathname: '/home' }, addEventListener: register };
    const document = { addEventListener: register, querySelector: () => pageElement };
    vm.runInNewContext(layout.slice(start, end), { window, document, URL });
    return {
        classes,
        click: extra => (listeners.click ?? []).forEach(listener => listener({ target: { closest: () => anchor }, ...extra })),
        show: () => (listeners.pageshow ?? []).forEach(listener => listener({ persisted: true })),
    };
}

test('mobile dashboard remains visible while the next document is pending', () => {
    const current = page();
    current.click({});
    assert.equal(current.classes.has('page-exit'), false);
});

test('cancelled or background navigation never blanks the current page', () => {
    for (const event of [{ defaultPrevented: true }, { ctrlKey: true }, { metaKey: true }]) {
        const current = page();
        current.click(event);
        assert.equal(current.classes.has('page-exit'), false);
    }
});

test('returning from browser history clears a legacy exit state', () => {
    const current = page();
    current.classes.add('page-exit');
    current.show();
    assert.equal(current.classes.has('page-exit'), false);
});
