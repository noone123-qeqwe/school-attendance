const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const script = fs.readFileSync(require('node:path').join(__dirname, '../../public/js/admin-workspace.js'), 'utf8');

// A small DOM fixture for progressive-enhancement behavior; this does not replace visual browser QA.
function fixture({ admin = true, mobile = false } = {}) {
    const observers = [];
    const ids = new Map();
    let queries = 0, mobileCloses = 0;
    function element(textContent = '') {
        const attributes = new Map(), events = new Map(), classes = new Set();
        return {
            textContent, dataset: {}, style: {}, hidden: false, open: false, disabled: false, isConnected: true,
            classList: {
                contains: value => classes.has(value), add: value => classes.add(value),
                toggle(value, on) { if (on) classes.add(value); else classes.delete(value); },
            },
            addEventListener(name, listener) { (events.get(name) ?? events.set(name, []).get(name)).push(listener); },
            fire(name, event = {}) { for (const listener of events.get(name) ?? []) listener({ target: this, preventDefault() {}, ...event }); },
            setAttribute: (key, value) => attributes.set(key, value), getAttribute: key => attributes.get(key) ?? null,
            hasAttribute: key => attributes.has(key), querySelector: () => null, querySelectorAll: () => [], closest: () => null,
            getClientRects: () => [{}], focus() { document.activeElement = this; },
        };
    }
    const document = element();
    document.body = element();
    if (admin) document.body.classList.add('admin-portal');
    document.getElementById = id => ids.get(id) ?? null;
    const search = element(); search.value = '';
    const empty = element(); empty.hidden = true;
    const groups = [['Dashboard', 'School calendar'], ['Students', 'Instructors'], ['Settings']].map((labels, index) => {
        const group = element(); group.open = index === 0;
        group.links = labels.map(label => element(label));
        group.querySelectorAll = () => group.links;
        return group;
    });
    const nav = element();
    nav.querySelector = selector => selector.includes('search') ? search : empty;
    nav.querySelectorAll = () => groups;
    nav.closest = () => mobile ? element() : null;
    const main = element(); ids.set('mainContent', main);
    const wrapper = element();
    const head = element(); head.cells = ['Student', 'Course', 'Actions'].map(element);
    head.cells.forEach(cell => { cell.colSpan = 1; cell.rowSpan = 1; });
    const row = () => {
        const result = element();
        result.cells = ["O'Brien <Student>", 'BSCS', 'Edit Delete'].map(element);
        result.cells.forEach(cell => { cell.colSpan = 1; });
        return result;
    };
    const body = element(); body.rows = [row()];
    const table = element(); table.tHead = element(); table.tHead.rows = [head]; table.tBodies = [body]; table.closest = () => wrapper;
    const tables = [table];
    const fields = Object.fromEntries(['name', 'code', 'description', 'department_id'].map(name => [name, { value: 'old' }]));
    const form = element(); form.elements = { namedItem: name => fields[name] };
    const dialog = element(); dialog.id = 'recordEditor'; dialog.style.display = 'none'; ids.set(dialog.id, dialog);
    const heading = element('Edit record');
    const close = element();
    close.click = () => { dialog.style.display = 'none'; notify(dialog); };
    const last = element();
    dialog.querySelector = selector => selector.includes('record-form') ? form : selector.startsWith('h2') ? heading : close;
    dialog.querySelectorAll = () => [close, last];
    const trigger = element(); document.activeElement = trigger;
    const editorButton = element();
    editorButton.dataset = { editTarget: 'recordEditor', editUrl: '/admin/courses/42', adminEditRecord: JSON.stringify({ code: 'RD', name: "O'Brien <Research>", description: null, department_id: 7 }) };
    editorButton.closest = selector => selector.includes('edit-record') ? editorButton : null;
    main.querySelectorAll = selector => selector.startsWith('table.') ? tables : selector.includes('modal-backdrop') ? [dialog] : [];
    document.querySelectorAll = selector => { queries++; return selector.includes('data-admin-navigation') ? [nav] : []; };
    const window = { closeMoreSheet() { mobileCloses++; } };
    function notify(target, records = [{ addedNodes: [{}] }]) { observers.filter(o => o.target === target).forEach(o => o.callback(records)); }
    class MutationObserver {
        constructor(callback) { this.callback = callback; observers.push(this); }
        observe(target) { this.target = target; }
    }
    vm.runInNewContext(script, { document, window, MutationObserver, requestAnimationFrame: fn => fn(), getComputedStyle: node => ({ display: node.style.display || 'block', visibility: 'visible' }) });
    return { document, main, nav, groups, search, empty, table, body, head, tables, wrapper, row, dialog, close, last, trigger, editorButton, form, fields, notify, get queries() { return queries; }, get mobileCloses() { return mobileCloses; } };
}

test('navigation search finds pages across closed groups and restores the original groups on clear', () => {
    const app = fixture();
    app.search.value = 'student'; app.search.fire('input');
    assert.equal(app.groups[0].hidden, true);
    assert.equal(app.groups[1].open, true);
    assert.equal(app.groups[1].links[0].hidden, false);
    assert.equal(app.groups[1].links[1].hidden, true);
    app.search.value = 'missing page'; app.search.fire('input');
    assert.equal(app.empty.hidden, false);
    app.search.value = ''; app.search.fire('input');
    assert.deepEqual(app.groups.map(group => group.open), [true, false, false]);
    assert.ok(app.groups.every(group => !group.hidden && group.links.every(link => !link.hidden)));
    assert.equal(app.empty.hidden, true);
});

test('selecting a mobile destination dismisses its dialog without affecting desktop navigation', () => {
    for (const mobile of [true, false]) {
        const app = fixture({ mobile });
        app.nav.fire('click', { target: { closest: () => ({ href: '/admin/students' }) } });
        assert.equal(app.mobileCloses, mobile ? 1 : 0);
    }
});

test('phone record labels preserve content and are reapplied after filtered rows arrive', () => {
    const app = fixture();
    assert.ok(app.wrapper.classList.contains('admin-record-wrap'));
    assert.deepEqual(app.body.rows[0].cells.map(cell => cell.dataset.label), ['Student', 'Course', 'Actions']);
    assert.equal(app.body.rows[0].cells[0].textContent, "O'Brien <Student>");
    assert.equal(app.head.cells[0].getAttribute('role'), 'columnheader');
    const fresh = app.row(); fresh.cells[0].dataset.label = 'Student name';
    app.body.rows = [fresh]; app.notify(app.main);
    assert.deepEqual(fresh.cells.map(cell => cell.dataset.label), ['Student name', 'Course', 'Actions']);
    assert.equal(fresh.cells[0].getAttribute('role'), 'cell');
});

test('complex report header relationships are left in their native table layout', () => {
    const app = fixture();
    const complex = { tHead: { rows: [{ cells: [{ colSpan: 2, rowSpan: 1 }] }] }, classList: { add() { throw Error('Complex report changed'); } } };
    app.tables.push(complex);
    assert.doesNotThrow(() => app.notify(app.main));
});

test('record editor populates the right endpoint and safely preserves special-character text', () => {
    const app = fixture();
    app.main.fire('click', { target: app.editorButton });
    assert.equal(app.form.action, '/admin/courses/42');
    assert.equal(app.fields.name.value, "O'Brien <Research>");
    assert.equal(app.fields.description.value, '');
    assert.equal(app.fields.department_id.value, 7);
    assert.equal(app.dialog.style.display, 'flex');
});

test('custom dialogs contain keyboard focus, close with Escape and return focus to the opener', () => {
    const app = fixture();
    assert.equal(app.dialog.inert, true);
    app.dialog.style.display = 'flex'; app.notify(app.dialog);
    assert.equal(app.dialog.inert, false);
    assert.equal(app.document.activeElement, app.close);
    assert.equal(app.document.body.classList.contains('admin-dialog-open'), true);
    app.last.focus();
    let prevented = false;
    app.dialog.fire('keydown', { key: 'Tab', preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(app.document.activeElement, app.close);
    app.dialog.fire('keydown', { key: 'Escape' });
    assert.equal(app.dialog.inert, true);
    assert.equal(app.document.activeElement, app.trigger);
    assert.equal(app.document.body.classList.contains('admin-dialog-open'), false);
});

test('admin behavior never attaches to another role', () => {
    const app = fixture({ admin: false });
    assert.equal(app.queries, 0);
    assert.equal(app.table.getAttribute('role'), null);
});
