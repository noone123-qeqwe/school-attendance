(() => {
    'use strict';

    const flashSelectors = '.flash-ok, .flash-err, .flash-error, .flash-success, .ts-flash, .hub-alert, .sd-error-banner, .sd-success-banner';
    const bannerSelectors = [
        '[data-alert-banner]', 'div.alert', 'section.alert', 'p.alert', '.alert-info', '.alert-err', '.alert-modern', '.glass-alert',
        '.portal-banner', '.ent-alert', '.ds-inline-alert', '.assistant-notice', flashSelectors,
    ].join(', ');
    const resolvedSelector = '[data-alert-resolved]';
    const scope = document.currentScript?.dataset.alertScope || 'guest';
    const storageKey = `attendance:alert-dismissals:v1:${scope}`;
    const records = new WeakMap();
    const queue = new Set();
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let storage;
    let dismissals = new Map();
    let scheduled = false;

    for (const name of ['localStorage', 'sessionStorage']) {
        try {
            storage = window[name];
            dismissals = new Map(JSON.parse(storage.getItem(storageKey) || '[]'));
            break;
        } catch {
            storage = undefined;
        }
    }

    // Keep the revision small and avoid storing message text or identifiers from it.
    function hash(value) {
        let result = 2166136261;
        for (let index = 0; index < value.length; index++) {
            result = Math.imul(result ^ value.charCodeAt(index), 16777619);
        }
        return (result >>> 0).toString(36);
    }

    function save() {
        try {
            storage?.setItem(storageKey, JSON.stringify([...dismissals]));
        } catch {
            // In-memory dismissal still works when browser storage is unavailable.
        }
    }

    function forget(key) {
        if (dismissals.delete(key)) save();
    }

    function enhance(banner) {
        if (!banner.isConnected) return;
        if (banner.matches(resolvedSelector)) {
            forget(hash(banner.dataset.alertResolved));
            return;
        }

        const previous = records.get(banner);
        const eligible = !banner.closest('[data-alert-required]');
        const message = banner.querySelector('[data-banner-message]') || banner;
        const text = Array.from(message.childNodes)
            .filter(node => node.nodeType !== Node.ELEMENT_NODE || !node.matches('[data-alert-close]'))
            .map(node => node.textContent).join(' ').replace(/\s+/g, ' ').trim();
        const key = hash(banner.dataset.alertKey || `${location.pathname}:${banner.id || text}`);
        const revision = hash(banner.dataset.alertVersion ?? text);
        const transient = banner.matches(`[data-alert-transient], ${flashSelectors}`);

        if (!eligible || !text) {
            if (eligible && previous && !transient) forget(previous.key);
            previous?.animation?.cancel();
            records.delete(banner);
            banner.removeAttribute('data-alert-enhanced');
            banner.removeAttribute('data-alert-dismissed');
            banner.removeAttribute('data-alert-closing');
            return;
        }

        const sameAlert = previous?.key === key && previous.revision === revision;
        const state = sameAlert ? previous : { key, revision, transient, dismissed: false };
        if (!sameAlert) {
            previous?.animation?.cancel();
            banner.removeAttribute('data-alert-closing');
        }
        records.set(banner, state);

        if (!transient && dismissals.has(key) && dismissals.get(key) !== revision) forget(key);
        const dismissed = transient ? state.dismissed : dismissals.get(key) === revision || state.dismissed;
        if (!state.animation) banner.toggleAttribute('data-alert-dismissed', dismissed);

        if (!banner.hasAttribute('data-alert-enhanced')) {
            banner.classList.toggle('alert-with-relative-position', getComputedStyle(banner).position === 'static');
            banner.setAttribute('data-alert-enhanced', '');
        }

        if (!banner.querySelector(':scope > [data-alert-close]')) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'alert-dismiss-button';
            button.setAttribute('data-alert-close', '');
            button.setAttribute('aria-label', banner.dataset.alertLabel || 'Dismiss alert');
            const icon = document.createElement('span');
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = '×';
            button.append(icon);
            banner.append(button);
        }
    }

    function restoreFocus(banner) {
        if (!banner.contains(document.activeElement)) return;

        const available = Array.from(document.querySelectorAll('[data-alert-close]')).filter(button =>
            !banner.contains(button) && button.getClientRects().length
        );
        const next = available.find(button => banner.compareDocumentPosition(button) & Node.DOCUMENT_POSITION_FOLLOWING)
            || available.at(-1)
            || banner.closest('main, [role="main"]') || document.querySelector('main, h1');
        if (!next) return;

        if (!next.matches('button, a[href], [tabindex]')) {
            next.tabIndex = -1;
            next.addEventListener('blur', () => next.removeAttribute('tabindex'), { once: true });
        }
        next.focus({ preventScroll: true });
    }

    function dismiss(banner) {
        const state = records.get(banner);
        if (!state || state.dismissed) return;

        state.dismissed = true;
        if (!state.transient) {
            dismissals.set(state.key, state.revision);
            if (dismissals.size > 160) dismissals.delete(dismissals.keys().next().value);
            save();
        }
        restoreFocus(banner);

        const finish = () => {
            banner.setAttribute('data-alert-dismissed', '');
            banner.removeAttribute('data-alert-closing');
            state.animation?.cancel();
            state.animation = undefined;
        };

        if (reducedMotion.matches || !banner.animate) {
            finish();
            return;
        }
        const style = getComputedStyle(banner);
        const sizing = {
            blockSize: style.blockSize,
            minBlockSize: '0px',
            marginBlockStart: style.marginBlockStart,
            marginBlockEnd: style.marginBlockEnd,
            paddingBlockStart: style.paddingBlockStart,
            paddingBlockEnd: style.paddingBlockEnd,
            borderBlockStartWidth: style.borderBlockStartWidth,
            borderBlockEndWidth: style.borderBlockEndWidth,
        };
        const collapsed = Object.fromEntries(Object.keys(sizing).map(property => [property, '0px']));
        banner.setAttribute('data-alert-closing', '');
        state.animation = banner.animate(
            [{ ...sizing, opacity: style.opacity }, { ...collapsed, opacity: 0 }],
            { duration: 220, easing: 'ease-in-out', fill: 'forwards' }
        );
        state.animation.onfinish = finish;
    }

    document.addEventListener('click', event => {
        const button = event.target.closest?.('[data-alert-close]');
        if (button) dismiss(button.parentElement);
    });

    function scan(root) {
        if (root.matches?.(`${bannerSelectors}, ${resolvedSelector}`)) enhance(root);
        root.querySelectorAll?.(`${bannerSelectors}, ${resolvedSelector}`).forEach(enhance);
    }

    function schedule(element) {
        if (!element) return;
        queue.add(element);
        if (scheduled) return;
        scheduled = true;
        queueMicrotask(() => {
            scheduled = false;
            queue.forEach(scan);
            queue.clear();
        });
    }

    scan(document);
    new MutationObserver(mutations => {
        for (const mutation of mutations) {
            const target = mutation.target.nodeType === Node.ELEMENT_NODE
                ? mutation.target : mutation.target.parentElement;
            if (target?.closest('[data-alert-close]')) continue;
            schedule(target?.closest(bannerSelectors));
            if (mutation.type === 'attributes') schedule(target);
            for (const node of mutation.addedNodes) {
                if (node.nodeType === Node.ELEMENT_NODE && !node.matches('[data-alert-close]')) schedule(node);
            }
        }
    }).observe(document.body, {
        subtree: true,
        childList: true,
        characterData: true,
        attributes: true,
        attributeFilter: ['data-alert-version', 'data-alert-required', 'data-alert-resolved'],
    });

    window.addEventListener('pageshow', event => {
        if (event.persisted) scan(document);
    });
})();
