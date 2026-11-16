/**
 * Shared CSP-safe UI helpers bundled via Vite.
 * Provides safe textContent DOM toast helpers and delegated attribute actions.
 */
export function copyToClipboardSafe(text) {
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        return navigator.clipboard.writeText(String(text ?? ''));
    }

    return new Promise((resolve, reject) => {
        try {
            const ta = document.createElement('textarea');
            ta.value = String(text ?? '');
            ta.setAttribute('readonly', '');
            ta.style.cssText = 'position:fixed;opacity:0;left:-9999px;';
            document.body.appendChild(ta);
            ta.select();
            const ok = document.execCommand('copy');
            document.body.removeChild(ta);
            ok ? resolve() : reject(new Error('Clipboard unavailable'));
        } catch (err) {
            reject(err);
        }
    });
}

export function initDelegatedActions() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-ui-action]');
        if (!trigger) return;

        const action = trigger.getAttribute('data-ui-action');
        if (action === 'copy-text') {
            const targetSelector = trigger.getAttribute('data-copy-target');
            const explicitText = trigger.getAttribute('data-copy-value');
            const text = explicitText ?? (targetSelector ? document.querySelector(targetSelector)?.textContent?.trim() : '');
            if (!text) return;

            copyToClipboardSafe(text)
                .then(() => window.showToast?.('Copied to clipboard.', 'success'))
                .catch(() => window.showToast?.('Unable to copy automatically.', 'warning'));
        } else if (action === 'dismiss-flash') {
            const flash = trigger.closest('.flash-ok, .flash-error, [role="alert"]');
            flash?.remove();
        }
    });
}

if (typeof window !== 'undefined') {
    window.SmartUi = Object.assign(window.SmartUi || {}, { copyToClipboardSafe });
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDelegatedActions, { once: true });
    } else {
        initDelegatedActions();
    }
}
