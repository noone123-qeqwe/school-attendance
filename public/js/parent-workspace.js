(() => {
    'use strict';
    if (!document.body.classList.contains('parent-portal')) return;
    const panels = [...document.querySelectorAll('[data-parent-child-panel]')];
    const tabs = [...document.querySelectorAll('[data-parent-child-tab]')];
    const picker = document.querySelector('[data-parent-child-picker]');
    const account = document.querySelector('meta[name="auth-user-id"]')?.content || '';
    const storageKey = `parentSelectedChild:${account}`;
    const serverSelectedChild = document.querySelector('[data-parent-selected-child]')?.dataset.parentSelectedChild || '';
    function updateChildAwareLinks(id) {
        document.querySelectorAll('[data-parent-child-context]').forEach(link => {
            try {
                const url = new URL(link.href, window.location.origin);
                url.searchParams.set('child_id', id);
                link.href = url.toString();
            } catch (_) { /* A missing URL API must not block the parent workspace. */ }
        });
        if (!window.location || !window.history?.replaceState || !/\/parent\/dashboard\/?$/.test(window.location.pathname)) return;
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('child_id', id);
            window.history.replaceState({}, '', url);
        } catch (_) { /* Keep the selected child in the visible page when URL updates are unavailable. */ }
    }
    function selectChild(value, focus = false) {
        const id = String(value);
        if (!panels.some(panel => panel.dataset.parentChildPanel === id)) return false;
        panels.forEach(panel => {
            const selected = panel.dataset.parentChildPanel === id;
            panel.hidden = !selected;
            panel.inert = !selected;
        });
        tabs.forEach(tab => {
            const selected = tab.dataset.parentChildTab === id;
            tab.classList.toggle('active', selected);
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
            if (selected && focus) tab.focus();
        });
        if (picker) picker.value = id;
        try { localStorage.setItem(storageKey, id); } catch (_) { /* Selection works when storage is unavailable. */ }
        updateChildAwareLinks(id);
        return true;
    }
    if (panels.length) {
        let saved;
        try { saved = localStorage.getItem(storageKey); } catch (_) { /* Use the first linked child. */ }
        if (!(serverSelectedChild && selectChild(serverSelectedChild)) && (!saved || !selectChild(saved))) {
            selectChild(panels[0].dataset.parentChildPanel);
        }
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => selectChild(tab.dataset.parentChildTab));
            tab.addEventListener('keydown', event => {
                let next;
                if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
                if (event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
                if (event.key === 'Home') next = 0;
                if (event.key === 'End') next = tabs.length - 1;
                if (next === undefined) return;
                event.preventDefault();
                selectChild(tabs[next].dataset.parentChildTab, true);
            });
        });
        picker?.addEventListener('change', () => selectChild(picker.value));
        window.switchDashboardChild = selectChild;
    }

    const childContextPicker = document.querySelector('[data-parent-child-context-switch]');
    childContextPicker?.addEventListener('change', () => {
        const template = childContextPicker.dataset.parentChildContextTemplate;
        if (!template) return;
        window.location.href = template.replace('__parent_child__', encodeURIComponent(childContextPicker.value));
    });

    const main = document.getElementById('mainContent');
    const accountTabs = [...document.querySelectorAll('[data-parent-profile-tab]')];
    accountTabs.forEach((tab, index) => tab.addEventListener('keydown', event => {
        let next;
        if (event.key === 'ArrowRight') next = (index + 1) % accountTabs.length;
        if (event.key === 'ArrowLeft') next = (index - 1 + accountTabs.length) % accountTabs.length;
        if (event.key === 'Home') next = 0;
        if (event.key === 'End') next = accountTabs.length - 1;
        if (next === undefined) return;
        event.preventDefault();
        accountTabs[next].click();
        accountTabs[next].focus();
    }));
    main?.querySelectorAll('label:not([for])').forEach((label, index) => {
        if (label.querySelector('input, select, textarea')) return;
        const sibling = label.nextElementSibling;
        const input = sibling?.matches('input:not([type="hidden"]), select, textarea') ? sibling : sibling?.matches('.pw-wrap') ? sibling.querySelector('input') : null;
        if (!input) return;
        if (!input.id) input.id = `parentField${index}`;
        label.htmlFor = input.id;
    });
    main?.querySelectorAll('button[title], a[title]').forEach(control => {
        if (!control.textContent.trim() && !control.hasAttribute('aria-label')) control.setAttribute('aria-label', control.title);
    });
    main?.addEventListener('keydown', event => {
        if (!['Enter', ' '].includes(event.key) || !event.target.matches('[role="button"][tabindex="0"]')) return;
        event.preventDefault();
        event.target.click();
    });
    for (const [inputId, buttonId] of [['instant_student_number', 'btn-verify-code'], ['instant_code', 'btn-verify-code'], ['student_number', 'btn-send-otp'], ['otp_code', 'btn-verify-otp']]) {
        document.getElementById(inputId)?.addEventListener('keydown', event => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            document.getElementById(buttonId)?.click();
        });
    }

    const readButton = document.getElementById('markAllReadBtn');
    const feedback = document.getElementById('parentNotificationFeedback');
    readButton?.addEventListener('click', async () => {
        if (readButton.disabled) return;
        readButton.disabled = true;
        readButton.setAttribute('aria-busy', 'true');
        if (feedback) feedback.textContent = 'Marking alerts as read…';
        try {
            const response = await fetch(readButton.dataset.readUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error('Unable to mark alerts as read. Please try again.');
            document.querySelectorAll('[data-parent-unread-count], [data-parent-alert-new]').forEach(node => { node.hidden = true; });
            document.querySelectorAll('[data-parent-alert-unread]').forEach(node => {
                node.dataset.parentAlertUnread = 'false'; node.style.background = 'transparent';
            });
            if (feedback) feedback.textContent = 'All alerts are marked as read.';
            window.refreshNotificationBell?.();
        } catch (_) {
            if (feedback) feedback.textContent = 'Unable to mark alerts as read. Check your connection and try again.';
        } finally {
            readButton.disabled = false;
            readButton.removeAttribute('aria-busy');
        }
    });
})();
