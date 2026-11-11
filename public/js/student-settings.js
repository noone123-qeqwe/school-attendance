(function () {
    'use strict';
    const root = document.querySelector('[data-student-settings]');
    if (!root) return;

    const pages = {
        profile: ['Profile & school details', 'Manage your photo, contact number, and student information.', 'profile'],
        family: ['Guardians', 'Choose who can view your attendance and share a link code with your guardian.', 'family'],
        credentials: ['Password & email', 'Keep your sign-in details up to date. Changes require an email verification code.', 'security', 'credentials'],
        biometrics: ['Face & device sign-in', 'Register your face for attendance and manage the secure ways you sign in to your account.', 'security', 'biometrics'],
        device: ['Attendance device', 'Manage the phone or computer you use to record attendance.', 'security', 'device'],
        recovery: ['Recovery codes', 'Save backup codes somewhere safe in case you lose access to your account.', 'security', 'recovery'],
        preferences: ['Notifications & app', 'Choose how you receive alerts, adjust your display, and check for updates.', 'preferences'],
    };
    const aliases = {
        security: 'credentials', 'security-all': 'credentials', 'security-credentials': 'credentials',
        face: 'biometrics', passkeys: 'biometrics', fingerprint: 'biometrics', 'security-biometrics': 'biometrics',
        'security-device': 'device', 'security-recovery': 'recovery', cameraFaceEnrollment: 'biometrics',
    };
    const mobile = window.matchMedia('(max-width: 991.98px)');
    const heading = document.getElementById('studentSettingsPageTitle');
    const description = document.getElementById('studentSettingsPageDescription');
    const filter = document.getElementById('studentSettingsFilter');
    const returnKey = 'student_settings_return_' + root.dataset.studentSettings;
    let passkeysLoaded = false;
    let started = false;

    function defaultPage() { return mobile.matches ? 'overview' : 'profile'; }

    function filterMenu() {
        const query = filter.value.trim().toLocaleLowerCase();
        let count = 0;
        root.querySelectorAll('[data-settings-page]').forEach(button => {
            button.hidden = !(button.textContent + ' ' + button.dataset.settingsKeywords).toLocaleLowerCase().includes(query);
            if (!button.hidden) count++;
        });
        root.querySelectorAll('.student-settings-menu-group').forEach(group => {
            group.hidden = !Array.from(group.querySelectorAll('[data-settings-page]')).some(button => !button.hidden);
        });
        document.getElementById('studentSettingsNoResults').hidden = count > 0;
    }

    function navigate(requested, options = {}) {
        let page = aliases[requested] || requested;
        if (!pages[page] && page !== 'overview') page = defaultPage();
        if (page === 'overview' && !mobile.matches) page = 'profile';
        if (options.fromHistory && started && root.dataset.studentView === page) return page;
        if (root.dataset.studentView === 'biometrics' && page !== 'biometrics') {
            // Cancel pending camera requests as well as stopping an active stream.
            document.getElementById('cameraFaceCancelBtn')?.click();
        }
        root.dataset.studentView = page;
        root.querySelectorAll('[data-settings-page]').forEach(button => {
            if (button.dataset.settingsPage === page) button.setAttribute('aria-current', 'page');
            else button.removeAttribute('aria-current');
        });
        root.querySelectorAll('.spanel').forEach(panel => {
            panel.classList.toggle('active', page !== 'overview' && panel.id === 'tab-' + pages[page][2]);
        });
        root.querySelectorAll('.sec-group-panel').forEach(panel => {
            panel.style.display = pages[page]?.[3] && panel.id === 'sec-section-' + pages[page][3] ? 'block' : 'none';
        });
        const camera = document.getElementById('cameraFaceEnrollment');
        const passkeys = document.getElementById('studentPasskeySettings');
        if (camera) camera.hidden = page !== 'biometrics';
        if (passkeys) passkeys.hidden = page !== 'biometrics';
        if (pages[page]) {
            heading.textContent = pages[page][0];
            description.textContent = pages[page][1];
        }
        if (page === 'biometrics' && !passkeysLoaded) {
            passkeysLoaded = true;
            if (typeof window.loadDevices === 'function') window.loadDevices();
            if (typeof window.prefetchWebAuthn === 'function') window.prefetchWebAuthn();
        }
        if (page === 'device' && typeof window.checkDeviceBindingStatus === 'function') window.checkDeviceBindingStatus();
        if (options.push) history.pushState(null, '', page === 'overview' ? location.pathname + location.search : '#tab-' + page);
        else if (!options.fromHistory && started) history.replaceState(null, '', page === 'overview' ? location.pathname + location.search : '#tab-' + page);
        if (options.focus) {
            const target = page === 'overview' ? filter : heading;
            target.focus({ preventScroll: true });
            root.scrollIntoView({ block: 'start', behavior: 'instant' });
        }
        started = true;
        return page;
    }
    window.studentSettingsNavigate = navigate;

    root.addEventListener('click', event => {
        const link = event.target.closest('[data-settings-page], [data-settings-back]');
        if (!link) return;
        event.preventDefault();
        if (link.hasAttribute('data-settings-back')) {
            filter.value = '';
            filterMenu();
            navigate('overview', { push: true, focus: true });
        } else navigate(link.dataset.settingsPage, { push: true, focus: true });
    });
    filter.addEventListener('input', filterMenu);
    root.addEventListener('submit', event => {
        if (event.target instanceof HTMLFormElement && event.target.checkValidity()) {
            try { sessionStorage.setItem(returnKey, root.dataset.studentView); } catch (e) {}
        }
    });
    function syncHistory() { navigate(location.hash.replace(/^#(?:tab-)?/, '') || defaultPage(), { fromHistory: true }); }
    window.addEventListener('popstate', syncHistory);
    window.addEventListener('hashchange', syncHistory);
    mobile.addEventListener('change', () => {
        if (!mobile.matches && root.dataset.studentView === 'overview') navigate('profile');
    });
    document.addEventListener('DOMContentLoaded', () => {
        let returned = null;
        try { returned = sessionStorage.getItem(returnKey); sessionStorage.removeItem(returnKey); } catch (e) {}
        // Preserve the section after a form redirects with validation or success feedback.
        if (root.dataset.settingsFeedback === 'true') navigate(returned || (root.dataset.settingsPhoneError === 'true' ? 'profile' : 'credentials'));
    }, { once: true });
})();
