/**
 * Student Settings Navigation — Desktop Single-Column & Mobile iOS-Style Grouped Hub Drill-Down
 */
(function () {
    'use strict';

    const root = document.querySelector('[data-student-settings]');
    if (!root) return;

    const validPages = ['profile', 'family', 'credentials', 'biometrics', 'device', 'recovery', 'preferences'];
    const aliases = {
        security: 'credentials',
        'security-all': 'credentials',
        'security-credentials': 'credentials',
        'change-email': 'credentials',
        'change-password': 'credentials',
        'change-phone': 'profile',
        passkeys: 'biometrics',
        fingerprint: 'biometrics',
        face: 'biometrics',
        'security-biometrics': 'biometrics',
        'security-device': 'device',
        'security-recovery': 'recovery',
    };

    const returnKey = 'student_settings_return_' + root.dataset.studentSettings;
    const isMobile = () => window.matchMedia('(max-width: 767.98px)').matches;
    let started = false;

    window.studentSettingsRememberReturn = function () {
        try {
            sessionStorage.setItem(returnKey, root.dataset.studentView || 'profile');
        } catch (e) {}
    };

    function navigate(requested, options = {}) {
        let page = aliases[requested] || requested;
        if (!validPages.includes(page)) page = 'profile';

        root.dataset.studentView = page;

        // Update desktop sidebar & mobile triggers active state
        document.querySelectorAll('[data-settings-page]').forEach((btn) => {
            const match = btn.dataset.settingsPage === page;
            btn.classList.toggle('active', match);
            if (match) btn.setAttribute('aria-current', 'page');
            else btn.removeAttribute('aria-current');
        });

        // Show active panel
        root.querySelectorAll('.sf-settings-panel').forEach((panel) => {
            const match = panel.dataset.panelKey === page || panel.id === 'tab-' + page;
            panel.classList.toggle('active', match);
        });

        // Mobile drill-down into detail view when explicitly navigated or deep-linked
        if (options.openDetail !== false && (options.fromClick || options.fromHash || options.fromFeedback)) {
            root.dataset.mobileView = 'detail';
        }

        if (page === 'device' && typeof window.checkDeviceBindingStatus === 'function') {
            window.checkDeviceBindingStatus();
        }

        if (options.push || (started && !options.fromHistory)) {
            try {
                history.replaceState(null, '', '#tab-' + page);
            } catch (e) {}
        }

        if (options.scrollTop) {
            window.scrollTo({ top: 0, behavior: 'instant' });
        }

        started = true;
        return page;
    }

    window.studentSettingsNavigate = navigate;

    // Sidebar and Mobile Hub item clicks
    document.addEventListener('click', (event) => {
        const navBtn = event.target.closest('[data-settings-page]');
        if (!navBtn) return;
        event.preventDefault();
        navigate(navBtn.dataset.settingsPage, { push: true, fromClick: true, scrollTop: true });
    });

    // Mobile "Back to Settings" button
    const mobileBackBtn = document.getElementById('sfMobileSettingsBackBtn');
    if (mobileBackBtn) {
        mobileBackBtn.addEventListener('click', () => {
            root.dataset.mobileView = 'hub';
            try {
                history.replaceState(null, '', window.location.pathname);
            } catch (e) {}
            window.scrollTo({ top: 0, behavior: 'instant' });
        });
    }

    // Remember active section across form POST redirects
    root.addEventListener('submit', (event) => {
        if (event.target instanceof HTMLFormElement && event.target.checkValidity()) {
            window.studentSettingsRememberReturn();
        }
    });

    function syncFromHash() {
        const raw = location.hash.replace(/^#(?:tab-)?/, '');
        if (raw) {
            navigate(raw, { fromHistory: true, fromHash: true });
        } else if (isMobile()) {
            root.dataset.mobileView = 'hub';
        }
    }

    window.addEventListener('hashchange', syncFromHash);
    window.addEventListener('popstate', syncFromHash);

    document.addEventListener('DOMContentLoaded', () => {
        let returned = null;
        try {
            returned = sessionStorage.getItem(returnKey);
            sessionStorage.removeItem(returnKey);
        } catch (e) {}

        if (root.dataset.settingsFeedback === 'true') {
            const dest = root.dataset.settingsPhoneError === 'true'
                ? 'profile'
                : ((root.dataset.settingsEmailError === 'true' || root.dataset.settingsPasswordError === 'true')
                    ? 'credentials'
                    : (returned || 'profile'));
            navigate(dest, { fromFeedback: true });

            if (root.dataset.settingsEmailError === 'true' && root.dataset.settingsEmailAddressError !== 'true') {
                const s1 = document.getElementById('emailStep1');
                const s2 = document.getElementById('emailStep2');
                if (s1 && s2) { s1.style.display = 'none'; s2.style.display = 'block'; }
            }
            if (root.dataset.settingsPasswordError === 'true') {
                const p1 = document.getElementById('otpStep1');
                const p2 = document.getElementById('otpStep2');
                if (p1 && p2) { p1.style.display = 'none'; p2.style.display = 'block'; }
            }
        } else if (location.hash) {
            syncFromHash();
        } else {
            navigate('profile', { openDetail: false });
            if (isMobile()) {
                root.dataset.mobileView = 'hub';
            }
        }
    }, { once: true });
})();
