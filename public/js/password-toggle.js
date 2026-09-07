/**
 * Smart Attendance - Universal Password Visibility Toggle
 * Handles mobile touch (pointerdown/touchstart), desktop mouse (click), and keyboard (Space/Enter).
 * Prevents touch cancellation, prevents virtual keyboard dismissal, preserves selection range.
 */
(function(window, document) {
    'use strict';

    function togglePasswordVisibility(inputTarget, btnElement, event) {
        if (event) {
            if (event._pwToggled) return;
            event._pwToggled = true;
            if (event.preventDefault) event.preventDefault();
            if (event.stopPropagation) event.stopPropagation();
        }

        // Resolve button element
        let button = btnElement;
        if (!button && event && event.target) {
            button = event.target.closest('.eye-toggle, .eye-btn, [data-toggle-password], [id^="btn-toggle-password"]');
        }

        // Debounce protection per button (300ms) to prevent duplicate triggers (pointerdown + click)
        const now = Date.now();
        if (button) {
            if (button._lastToggleTime && (now - button._lastToggleTime < 300)) {
                return;
            }
            button._lastToggleTime = now;
        }

        // Resolve input element
        let input = null;
        if (typeof inputTarget === 'string') {
            input = document.getElementById(inputTarget);
        } else if (inputTarget && inputTarget.nodeType === 1) {
            input = inputTarget;
        }

        if (!input && button) {
            const targetId = button.getAttribute('data-toggle-password') || button.getAttribute('aria-controls');
            if (targetId) input = document.getElementById(targetId);
            if (!input && button.parentElement) {
                input = button.parentElement.querySelector('input[type="password"], input[type="text"]');
            }
        }
        if (!input) return;

        if (!button) {
            const id = input.id;
            if (id) {
                button = document.querySelector(`button[data-toggle-password="${id}"], button[aria-controls="${id}"], button[onclick*="${id}"]`);
            }
            if (!button && input.parentElement) {
                button = input.parentElement.querySelector('.eye-toggle, .eye-btn, [data-toggle-password], [id^="btn-toggle-password"]');
            }
        }

        // Capture current focus state and selection range
        const isCurrentlyFocused = (document.activeElement === input);
        let start = null;
        let end = null;
        try {
            start = input.selectionStart;
            end = input.selectionEnd;
        } catch (err) {}

        // Toggle visibility state
        const isPassword = (input.type === 'password');
        input.type = isPassword ? 'text' : 'password';

        // Synchronize button icon and accessibility attributes
        if (button) {
            const icon = button.querySelector('i');
            if (icon) {
                // Password hidden: Eye-off icon (bi bi-eye-slash)
                // Password visible: Eye icon (bi bi-eye)
                icon.className = isPassword ? 'bi bi-eye' : 'bi bi-eye-slash';
            }
            const isConf = input.name === 'password_confirmation' || 
                           (input.id && (input.id.includes('2') || input.id.includes('conf') || input.id.endsWith('_confirmation')));
            const label = isPassword 
                ? (isConf ? 'Hide password confirmation' : 'Hide password')
                : (isConf ? 'Show password confirmation' : 'Show password');
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
        }

        // Keep input focus and restore selection position if it was active
        if (isCurrentlyFocused) {
            try {
                input.focus({ preventScroll: true });
                if (start !== null && end !== null) {
                    input.setSelectionRange(start, end);
                }
            } catch (err) {}
        }
    }

    // Expose global functions for inline onclick handlers and backwards compatibility
    window.togglePassword = togglePasswordVisibility;
    window.toggleEye = togglePasswordVisibility;
    window.togglePw = togglePasswordVisibility;

    // Delegated event listener setup
    function setupDelegatedListeners() {
        // 1. Pointerdown (touch and mouse down):
        // Prevents input blur and virtual keyboard dismissal on mobile, executes toggle instantly
        document.addEventListener('pointerdown', function(e) {
            if (e.button !== undefined && e.button !== 0) return;
            const btn = e.target.closest('.eye-toggle, .eye-btn, [data-toggle-password], [id^="btn-toggle-password"]');
            if (!btn) return;

            e.preventDefault(); // Prevents input from losing focus / keyboard closing
            const targetId = btn.getAttribute('data-toggle-password') || btn.getAttribute('aria-controls');
            let input = targetId ? document.getElementById(targetId) : null;
            if (!input && btn.parentElement) {
                input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
            }
            togglePasswordVisibility(input, btn, e);
        });

        // 2. Touchstart fallback for environments without PointerEvent
        if (!window.PointerEvent) {
            document.addEventListener('touchstart', function(e) {
                const btn = e.target.closest('.eye-toggle, .eye-btn, [data-toggle-password], [id^="btn-toggle-password"]');
                if (!btn) return;
                e.preventDefault();
                const targetId = btn.getAttribute('data-toggle-password') || btn.getAttribute('aria-controls');
                let input = targetId ? document.getElementById(targetId) : null;
                if (!input && btn.parentElement) {
                    input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
                }
                togglePasswordVisibility(input, btn, e);
            }, { passive: false });
        }

        // 3. Click handler (fallback & desktop click)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.eye-toggle, .eye-btn, [data-toggle-password], [id^="btn-toggle-password"]');
            if (!btn) return;

            e.preventDefault();
            if (e.stopPropagation) e.stopPropagation();

            const targetId = btn.getAttribute('data-toggle-password') || btn.getAttribute('aria-controls');
            let input = targetId ? document.getElementById(targetId) : null;
            if (!input && btn.parentElement) {
                input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
            }
            togglePasswordVisibility(input, btn, e);
        });

        // 4. Keyboard accessibility (Space and Enter)
        document.addEventListener('keydown', function(e) {
            if (e.key === ' ' || e.key === 'Enter' || e.keyCode === 32 || e.keyCode === 13) {
                const btn = e.target.closest('.eye-toggle, .eye-btn, [data-toggle-password], [id^="btn-toggle-password"]');
                if (!btn) return;

                e.preventDefault();
                if (e.stopPropagation) e.stopPropagation();

                const targetId = btn.getAttribute('data-toggle-password') || btn.getAttribute('aria-controls');
                let input = targetId ? document.getElementById(targetId) : null;
                if (!input && btn.parentElement) {
                    input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
                }
                togglePasswordVisibility(input, btn, e);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupDelegatedListeners);
    } else {
        setupDelegatedListeners();
    }
})(window, document);
