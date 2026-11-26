const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const bladePath = path.resolve(__dirname, '../../resources/views/components/command-palette.blade.php');
const blade = fs.readFileSync(bladePath, 'utf8');

function extractScript() {
    const startToken = '<script nonce="{{ csp_nonce() }}">';
    const start = blade.indexOf(startToken);
    const end = blade.lastIndexOf('</script>');
    assert.ok(start !== -1 && end !== -1, 'Could not find command palette script block');
    let code = blade.slice(start + startToken.length, end);

    const mockItems = JSON.stringify([
        { id: 'dashboard', label: 'Dashboard', hint: 'Student home & daily overview', icon: 'bi-grid-fill', category: 'navigation', badge: 'Home', url: '/dashboard' },
        { id: 'code', label: 'Enter Attendance Code', hint: 'Enter 6-digit session PIN or scan QR', icon: 'bi-qr-code-scan', category: 'action', badge: 'Check-in', action: 'openScanner', url: '/dashboard?open_code=1' },
        { id: 'excuses', label: 'Excuse Submissions', hint: 'Submit absence excuse & check status', icon: 'bi-file-earmark-medical-fill', category: 'action', badge: 'Excuses', url: '/excuses' },
        { id: 'classes', label: 'My Classes', hint: 'Enrolled subjects, instructors & rooms', icon: 'bi-folder-fill', category: 'academic', badge: 'Classes', url: '/classes' },
        { id: 'schedule', label: 'My Schedule', hint: 'Weekly timetable & scheduled classes', icon: 'bi-calendar-week-fill', category: 'academic', badge: 'Schedule', url: '/schedule' },
        { id: 'calendar', label: 'Attendance & School Calendar', hint: 'Detailed attendance records, clock-in history, school events, and holidays', icon: 'bi-calendar-check-fill', category: 'academic', badge: 'Calendar', url: '/calendar' },
        { id: 'notifications', label: 'Notifications', hint: 'Class announcements & attendance warnings', icon: 'bi-bell-fill', category: 'navigation', badge: 'Alerts', url: '/notifications' },
        { id: 'settings', label: 'Settings', hint: 'Account preferences, security & profile', icon: 'bi-gear-fill', category: 'system', badge: 'Account', url: '/settings' }
    ]);

    const mockChips = JSON.stringify([
        { label: 'Check-in Code', icon: 'bi-qr-code-scan', action: 'openScanner', url: '/dashboard?open_code=1' },
        { label: 'My Schedule', icon: 'bi-calendar-week-fill', url: '/schedule' }
    ]);

    const authStart = code.indexOf('@auth');
    const authEnd = code.indexOf('@endauth');
    assert.ok(authStart !== -1 && authEnd !== -1, 'Expected @auth block in command palette script');
    code = code.slice(0, authStart) + `navItems = ${mockItems};\nquickActionChips = ${mockChips};` + code.slice(authEnd + '@endauth'.length);
    code = code.replace(/\{\{--[\s\S]*?--\}\}/g, '');
    return code;
}

class FakeClassList {
    constructor() {
        this.set = new Set();
    }
    add(...names) { names.forEach((n) => this.set.add(n)); }
    remove(...names) { names.forEach((n) => this.set.delete(n)); }
    toggle(name, force) {
        if (force === undefined) {
            if (this.set.has(name)) this.set.delete(name);
            else this.set.add(name);
            return;
        }
        if (force) this.set.add(name);
        else this.set.delete(name);
    }
    contains(name) { return this.set.has(name); }
}

class FakeElement {
    constructor(tagName, id = null) {
        this.tagName = tagName.toUpperCase();
        this.id = id;
        this.classList = new FakeClassList();
        this._className = '';
        this.attrs = {};
        this.children = [];
        this.parentNode = null;
        this.styleMap = {};
        this.style = {
            setProperty: (k, v) => { this.styleMap[k] = String(v); },
            removeProperty: (k) => { delete this.styleMap[k]; },
            getPropertyValue: (k) => this.styleMap[k] || ''
        };
        this.listeners = {};
        this._innerHTML = '';
        this.textContent = '';
        this.value = '';
        this.scrollTop = 0;
        this.scrolledIntoViewCalls = 0;
    }

    set className(val) {
        this._className = val;
        this.classList = new FakeClassList();
        val.split(/\s+/).filter(Boolean).forEach((c) => this.classList.add(c));
    }

    get className() {
        return Array.from(this.classList.set).join(' ');
    }

    set innerHTML(val) {
        this._innerHTML = String(val);
        if (val === '') this.children = [];
    }

    get innerHTML() {
        return this._innerHTML;
    }

    setAttribute(k, v) { this.attrs[k] = String(v); }
    getAttribute(k) { return this.attrs[k] ?? null; }
    removeAttribute(k) { delete this.attrs[k]; }
    appendChild(child) {
        child.parentNode = this;
        this.children.push(child);
        return child;
    }
    addEventListener(type, fn) {
        (this.listeners[type] ||= []).push(fn);
    }
    dispatchEvent(evt) {
        evt.target ||= this;
        evt.stopPropagation ||= () => { evt._stopped = true; };
        let node = this;
        while (node && !evt._stopped) {
            evt.currentTarget = node;
            (node.listeners[evt.type] || []).forEach((fn) => fn.call(node, evt));
            node = evt.bubbles === false ? null : node.parentNode;
        }
    }
    click() {
        this.dispatchEvent({ type: 'click', target: this, preventDefault() {} });
    }
    focus() {}
    scrollIntoView() { this.scrolledIntoViewCalls++; }
    closest(selector) {
        if (selector === '.cmd-cat-pill' && this.classList.contains('cmd-cat-pill')) return this;
        return this.parentNode ? this.parentNode.closest(selector) : null;
    }
    querySelector(selector) {
        return this.querySelectorAll(selector)[0] || null;
    }
    querySelectorAll(selector) {
        const matches = [];
        const visit = (node) => {
            for (const child of node.children) {
                if (selector.startsWith('[data-cat="')) {
                    const wanted = selector.slice('[data-cat="'.length, -2);
                    if (child.getAttribute('data-cat') === wanted) matches.push(child);
                } else if (selector === '.cmd-cat-pill' && child.classList.contains('cmd-cat-pill')) {
                    matches.push(child);
                } else if (selector === '.cmd-palette-items-list .cmd-palette-item' && child.classList.contains('cmd-palette-item')) {
                    matches.push(child);
                }
                visit(child);
            }
        };
        visit(this);
        return matches;
    }
}

function createHarness() {
    const byId = new Map();
    const register = (tag, id, parent) => {
        const el = new FakeElement(tag, id);
        if (id) byId.set(id, el);
        if (parent) parent.appendChild(el);
        return el;
    };

    const overlay = register('div', 'cmdPaletteOverlay');
    const palette = register('div', 'cmdPalette', overlay);
    const grabber = register('div', 'cmdPaletteGrabber', palette);
    const input = register('input', 'cmdPaletteInput', palette);
    const clearBtn = register('button', 'cmdPaletteClearBtn', palette);
    const closeBtn = register('button', 'cmdPaletteCloseBtn', palette);
    const categories = register('div', 'cmdPaletteCategories', palette);
    for (const cat of ['all', 'action', 'navigation', 'academic', 'system']) {
        const pill = register('button', null, categories);
        pill.className = 'cmd-cat-pill';
        pill.setAttribute('data-cat', cat);
    }

    const bodyEl = register('div', 'cmdPaletteBody', palette);
    const liveStatus = register('span', 'cmdPaletteLiveStatus', bodyEl);
    const quickChipsWrap = register('div', 'cmdQuickChipsWrap', bodyEl);
    register('div', 'cmdQuickChipsRow', quickChipsWrap);
    const recentGroup = register('div', 'cmdPaletteRecent', bodyEl);
    register('button', 'cmdClearRecentBtn', recentGroup);
    register('div', 'cmdPaletteRecentItems', recentGroup);
    const mainGroup = register('div', 'cmdPaletteMainGroup', bodyEl);
    const groupLabel = register('div', 'cmdPaletteGroupLabel', mainGroup);
    const countBadge = register('span', 'cmdPaletteCountBadge', mainGroup);
    const itemsContainer = register('div', 'cmdPaletteItems', mainGroup);
    const emptyState = register('div', 'cmdPaletteEmpty', bodyEl);
    const emptyTitle = register('div', 'cmdEmptyTitle', emptyState);
    const emptyDesc = register('div', 'cmdEmptyDesc', emptyState);
    const emptyActions = register('div', 'cmdEmptyActions', emptyState);
    const emptyClearBtn = register('button', 'cmdEmptyClearBtn', emptyActions);
    const emptyAllCatBtn = register('button', 'cmdEmptyAllCatBtn', emptyActions);

    const bodyNode = new FakeElement('body');
    const docEl = new FakeElement('html');
    docEl.clientHeight = 844;

    const vvListeners = {};
    const visualViewport = {
        height: 844,
        offsetTop: 0,
        addEventListener(type, fn) { (vvListeners[type] ||= []).push(fn); },
        removeEventListener(type, fn) {
            vvListeners[type] = (vvListeners[type] || []).filter((f) => f !== fn);
        },
        emitResize(height, offsetTop = 0) {
            this.height = height;
            this.offsetTop = offsetTop;
            (vvListeners.resize || []).forEach((fn) => fn());
        }
    };

    const windowListeners = {};
    const docListeners = {};
    const localStorageMap = new Map();
    const windowObj = {
        visualViewport,
        innerHeight: 844,
        addEventListener(t, fn) { (windowListeners[t] ||= []).push(fn); },
        removeEventListener(t, fn) { windowListeners[t] = (windowListeners[t] || []).filter((f) => f !== fn); },
        requestAnimationFrame(cb) { cb(); return 1; },
        cancelAnimationFrame() {},
    };
    const documentObj = {
        body: bodyNode,
        documentElement: docEl,
        getElementById: (id) => byId.get(id) || null,
        createElement: (tag) => new FakeElement(tag),
        querySelector: () => null,
        addEventListener(t, fn) { (docListeners[t] ||= []).push(fn); },
    };

    vm.runInNewContext(extractScript(), {
        window: windowObj,
        document: documentObj,
        localStorage: {
            getItem: (k) => (localStorageMap.has(k) ? localStorageMap.get(k) : null),
            setItem: (k, v) => localStorageMap.set(k, String(v)),
            removeItem: (k) => localStorageMap.delete(k),
        },
        setTimeout: (fn) => { fn(); return 1; },
        clearTimeout() {},
    });

    return {
        windowObj,
        documentObj,
        overlay,
        palette,
        grabber,
        input,
        clearBtn,
        closeBtn,
        categories,
        bodyEl,
        mainGroup,
        groupLabel,
        countBadge,
        itemsContainer,
        quickChipsWrap,
        liveStatus,
        emptyState,
        emptyTitle,
        emptyDesc,
        emptyActions,
        emptyClearBtn,
        emptyAllCatBtn,
        visualViewport,
    };
}

test('switching category tabs updates group label accurately and resets body scroll without losing items', () => {
    const h = createHarness();
    h.windowObj.openCommandPalette();

    assert.equal(h.overlay.classList.contains('active'), true);
    assert.match(h.groupLabel.innerHTML, /All Pages/);
    assert.equal(h.countBadge.textContent, '8 results');
    assert.equal(h.itemsContainer.children.length, 8);
    assert.notEqual(h.quickChipsWrap.style.display, 'none');

    // Simulate user scrolling in "All", then switch to "Actions"
    h.bodyEl.scrollTop = 240;
    const actionsPill = h.categories.querySelector('[data-cat="action"]');
    actionsPill.click();

    assert.equal(h.bodyEl.scrollTop, 0);
    assert.match(h.groupLabel.innerHTML, /> Actions$/);
    assert.equal(h.countBadge.textContent, '2 results');
    assert.equal(h.itemsContainer.children.length, 2);
    assert.equal(h.quickChipsWrap.style.display, 'none');
    assert.ok(actionsPill.scrolledIntoViewCalls >= 1);

    // Switch to "Academic" and "System"
    h.categories.querySelector('[data-cat="academic"]').click();
    assert.match(h.groupLabel.innerHTML, /> Academic$/);
    assert.equal(h.countBadge.textContent, '3 results');

    h.categories.querySelector('[data-cat="system"]').click();
    assert.match(h.groupLabel.innerHTML, /> System$/);
    assert.equal(h.countBadge.textContent, '1 result');
});

test('empty state hides 0-results header, presents recovery buttons, and restores cleanly', () => {
    const h = createHarness();
    h.windowObj.openCommandPalette();

    h.categories.querySelector('[data-cat="action"]').click();
    h.input.value = 'zzznomatch';
    h.input.dispatchEvent({ type: 'input' });

    assert.equal(h.mainGroup.style.display, 'none');
    assert.equal(h.emptyState.style.display, 'flex');
    assert.equal(h.emptyTitle.textContent, 'No matches for "zzznomatch"');
    assert.equal(h.emptyClearBtn.style.display, '');
    assert.equal(h.emptyAllCatBtn.style.display, '');
    assert.match(h.liveStatus.textContent, /No matching pages or actions found/);

    // Clear search keeps Actions category and shows the 2 Actions items
    h.emptyClearBtn.click();
    assert.equal(h.input.value, '');
    assert.equal(h.emptyState.style.display, 'none');
    assert.equal(h.mainGroup.style.display, '');
    assert.equal(h.itemsContainer.children.length, 2);

    // Search miss again -> Show All Categories resets to All
    h.input.value = 'calendar';
    h.input.dispatchEvent({ type: 'input' });
    assert.equal(h.emptyState.style.display, 'flex');
    h.emptyAllCatBtn.click();
    assert.equal(h.emptyState.style.display, 'none');
    assert.equal(h.itemsContainer.children.length, 1);
    assert.match(h.groupLabel.innerHTML, /Search Results/);
});

test('mobile visualViewport adjusts overlay height when software keyboard opens and cleans up on close', () => {
    const h = createHarness();
    h.windowObj.openCommandPalette();

    assert.equal(h.overlay.style.getPropertyValue('--cmd-vv-height'), '844px');
    assert.equal(h.documentObj.body.classList.contains('keyboard-open'), false);

    // Simulate mobile keyboard shrinking visual viewport from 844px -> 480px
    h.visualViewport.emitResize(480, 0);
    assert.equal(h.overlay.style.getPropertyValue('--cmd-vv-height'), '480px');
    assert.equal(h.overlay.style.getPropertyValue('--cmd-bottom-inset'), '8px');
    assert.equal(h.documentObj.body.classList.contains('keyboard-open'), true);

    h.windowObj.closeCommandPalette();
    assert.equal(h.overlay.classList.contains('active'), false);
    assert.equal(h.overlay.style.getPropertyValue('--cmd-vv-height'), '');
    assert.equal(h.documentObj.body.classList.contains('keyboard-open'), false);
    assert.equal(h.documentObj.body.style.overflow, '');
    assert.equal(h.documentObj.documentElement.style.overflow, '');
});
