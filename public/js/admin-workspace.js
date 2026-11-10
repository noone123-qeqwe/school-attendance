/* Progressive enhancements only: existing forms, routes and permissions stay authoritative. */
(() => {
    'use strict';
    if (!document.body.classList.contains('admin-portal')) return;

    document.querySelectorAll('[data-admin-navigation]').forEach(nav => {
        const search = nav.querySelector('[data-admin-nav-search]');
        const groups = [...nav.querySelectorAll('[data-admin-nav-group]')];
        let previousOpen = null;
        search?.addEventListener('input', () => {
            const query = search.value.trim().toLocaleLowerCase();
            if (query && !previousOpen) previousOpen = groups.map(group => group.open);
            let total = 0;
            groups.forEach((group, index) => {
                let count = 0;
                group.querySelectorAll('[data-admin-nav-link]').forEach(link => {
                    const matches = !query || link.textContent.toLocaleLowerCase().includes(query);
                    link.hidden = !matches;
                    if (matches) count++;
                });
                group.hidden = count === 0;
                if (query) group.open = count > 0;
                else if (previousOpen) group.open = previousOpen[index];
                total += count;
            });
            nav.querySelector('[data-admin-nav-empty]').hidden = total > 0;
            if (!query) previousOpen = null;
        });
        if (nav.closest('#moreSheet')) {
            nav.addEventListener('click', event => {
                if (event.target.closest('a[href]')) window.closeMoreSheet?.();
            });
        }
    });

    const actionMenus = [...document.querySelectorAll('.admin-action-menu')];
    document.addEventListener('click', event => {
        actionMenus.forEach(menu => {
            if (!menu.contains(event.target) || event.target.closest('.admin-action-menu-items a, .admin-action-menu-items button')) menu.open = false;
        });
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        actionMenus.forEach(menu => {
            if (menu.open) { menu.open = false; menu.querySelector('summary')?.focus(); }
        });
    });

    const main = document.getElementById('mainContent');
    if (!main) return;
    main.addEventListener('click', event => {
        const button = event.target.closest('[data-admin-edit-record]');
        if (button) {
            const dialog = document.getElementById(button.dataset.editTarget);
            const form = dialog?.querySelector('[data-admin-record-form]');
            if (!form || !button.dataset.editUrl) return;
            const values = JSON.parse(button.dataset.adminEditRecord);
            form.action = button.dataset.editUrl;
            Object.entries(values).forEach(([name, value]) => {
                const field = form.elements.namedItem(name);
                if (field) field.value = value ?? '';
            });
            dialog.style.display = 'flex';
        }
        const close = event.target.closest('[data-admin-close-dialog]');
        if (close) close.closest('.saas-modal-backdrop').style.display = 'none';
    });
    const tableSelector = 'table.saas-table, table.adm-table, table.ent-table, table.sch-table, table.par-table, table.ann-table, table.audit-table';
    function enhanceTables() {
        main.querySelectorAll(tableSelector).forEach(table => {
            const headings = table.tHead?.rows;
            // Complex report matrices retain native horizontal scrolling and their header relationships.
            if (headings?.length !== 1 || [...headings[0].cells].some(cell => cell.colSpan > 1 || cell.rowSpan > 1)) return;
            const labels = [...headings[0].cells].map(cell => cell.textContent.trim().replace(/\s+/g, ' '));
            table.classList.add('admin-record-table');
            table.setAttribute('role', 'table');
            table.tHead.setAttribute('role', 'rowgroup');
            headings[0].setAttribute('role', 'row');
            [...headings[0].cells].forEach(cell => { cell.scope = 'col'; cell.setAttribute('role', 'columnheader'); });
            table.closest('.table-responsive, .table-scroll, .ent-table-wrap, .ent-scroll-x, .saas-table-container')?.classList.add('admin-record-wrap');
            [...table.tBodies].forEach(body => {
                body.setAttribute('role', 'rowgroup');
                [...body.rows].forEach(row => {
                    row.setAttribute('role', 'row');
                    [...row.cells].forEach((cell, index) => {
                        cell.setAttribute('role', 'cell');
                        if (cell.colSpan === 1 && !cell.dataset.label) cell.dataset.label = labels[index] || (index === 0 ? 'Record' : 'Details');
                    });
                });
            });
        });
    }
    let fieldId = 0;
    function enhanceLabels() {
        main.querySelectorAll('label:not([for])').forEach(label => {
            if (label.querySelector('input, select, textarea')) return;
            // Only pair adjacent labels, never guess across unrelated form sections.
            const sibling = label.nextElementSibling;
            const input = sibling?.matches('input:not([type="hidden"]), select, textarea') ? sibling : null;
            if (!input) return;
            if (!input.id) {
                do { input.id = `adminField${++fieldId}`; } while (document.querySelectorAll(`#${input.id}`).length > 1);
            }
            label.htmlFor = input.id;
        });
        main.querySelectorAll('button[title], a[title]').forEach(control => {
            if (!control.textContent.trim() && !control.hasAttribute('aria-label')) control.setAttribute('aria-label', control.title);
        });
    }
    enhanceTables();
    enhanceLabels();
    let scheduled = false;
    new MutationObserver(records => {
        if (scheduled || !records.some(record => record.addedNodes.length)) return;
        scheduled = true;
        requestAnimationFrame(() => { scheduled = false; enhanceTables(); enhanceLabels(); });
    }).observe(main, { childList: true, subtree: true });

    // Supply keyboard behavior for the app's custom dialogs, using their own close buttons.
    const dialogs = main.querySelectorAll('.saas-modal-backdrop, .sch-modal-backdrop, .par-modal-backdrop, .ann-modal-backdrop, .hcal-modal-overlay, .audit-modal-backdrop');
    dialogs.forEach(dialog => {
        const close = dialog.querySelector('button[onclick*="close"], [class$="modal-close"], [data-admin-close-dialog]');
        if (!close) return;
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        const heading = dialog.querySelector('h2, h3, h4, [class$="modal-title"], .saas-heading');
        if (heading) {
            if (!heading.id) heading.id = `${dialog.id}Heading`;
            dialog.setAttribute('aria-labelledby', heading.id);
        }
        if (!close.hasAttribute('aria-label')) close.setAttribute('aria-label', 'Close dialog');
        let opened = false;
        let trigger = null;
        const visible = () => getComputedStyle(dialog).display !== 'none' && getComputedStyle(dialog).visibility !== 'hidden';
        const focusables = () => [...dialog.querySelectorAll('a[href], button, input:not([type="hidden"]), select, textarea, [tabindex="0"]')].filter(el => !el.disabled && el.getClientRects().length);
        const sync = () => {
            const isOpen = visible();
            dialog.setAttribute('aria-hidden', String(!isOpen));
            dialog.inert = !isOpen;
            if (isOpen && !opened) {
                trigger = document.activeElement?.closest('.admin-action-menu')?.querySelector('summary') ?? document.activeElement;
                const first = focusables()[0];
                first?.focus({ preventScroll: true });
            } else if (!isOpen && opened && trigger?.isConnected) trigger.focus({ preventScroll: true });
            opened = isOpen;
            document.body.classList.toggle('admin-dialog-open', [...dialogs].some(overlay => overlay.getAttribute('aria-hidden') === 'false'));
        };
        new MutationObserver(sync).observe(dialog, { attributes: true, attributeFilter: ['class', 'style'] });
        dialog.addEventListener('keydown', event => {
            if (event.key === 'Escape') { event.preventDefault(); close.click(); }
            if (event.key !== 'Tab') return;
            const controls = focusables();
            const first = controls[0], last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        });
        sync();
    });
})();
