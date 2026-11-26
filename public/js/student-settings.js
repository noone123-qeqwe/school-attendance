(function () {
    'use strict';
    const root = document.querySelector('[data-student-settings]');
    if (!root) return;

    const pages = {
        profile: ['Profile & school details', 'Manage your photo, contact number, and student information.', 'profile'],
        family: ['Guardians', 'Choose who can view your attendance and share a link code with your guardian.', 'family'],
        credentials: ['Password & email', 'Keep your sign-in details up to date. Changes require an email verification code.', 'security', 'credentials'],
        'change-email': ['Change email address', 'Verify the new address before it becomes your account email.', 'account-email'],
        'change-password': ['Change password', 'Confirm your email and choose a new password.', 'account-password'],
        'change-phone': ['Change phone number', 'Update the phone number listed on your school account.', 'account-phone'],
        biometrics: ['Biometrics', 'Manage face registration for attendance and fingerprint registration for sign-in.', 'security', 'biometrics'],
        device: ['Attendance device', 'Manage the phone or computer you use to record attendance.', 'security', 'device'],
        recovery: ['Recovery codes', 'Save backup codes somewhere safe in case you lose access to your account.', 'security', 'recovery'],
        preferences: ['Notifications & app', 'Choose how you receive alerts, adjust your display, and check for updates.', 'preferences'],
    };
    const aliases = {
        security: 'credentials', 'security-all': 'credentials', 'security-credentials': 'credentials',
        passkeys: 'fingerprint', 'security-biometrics': 'biometrics',
        'security-device': 'device', 'security-recovery': 'recovery', cameraFaceEnrollment: 'face',
    };
    const heading = document.getElementById('studentSettingsPageTitle');
    const description = document.getElementById('studentSettingsPageDescription');
    const backBtn = document.querySelector('[data-account-back]');
    const backLabel = document.querySelector('[data-account-back-label]');
    const returnKey = 'student_settings_return_' + root.dataset.studentSettings;
    let started = false;

    function placeAccountEditForms() {
        const sources = [
            ['email', 'accountEmailFormSlot'],
            ['password', 'accountPasswordFormSlot'],
            ['phone', 'accountPhoneFormSlot'],
        ];
        let moved = 0;
        sources.forEach(([name, slotId]) => {
            const source = document.querySelector('[data-account-edit-source="' + name + '"]');
            const slot = document.getElementById(slotId);
            if (source && slot) {
                slot.appendChild(source);
                moved++;
            }
        });

        if (moved === sources.length) {
            const formGrid = document.getElementById('studentCredentialFormGrid');
            const credentialShortcuts = document.getElementById('studentCredentialShortcuts');
            const phoneShortcut = document.getElementById('studentPhoneShortcut');
            if (formGrid) formGrid.hidden = true;
            if (credentialShortcuts) credentialShortcuts.hidden = false;
            if (phoneShortcut) phoneShortcut.hidden = false;
            root.dataset.accountFormsReady = 'true';
        }
    }

    placeAccountEditForms();

    function defaultPage() { return 'profile'; }

    function resolveActiveNavKey(page) {
        if (page === 'change-email' || page === 'change-password') return 'credentials';
        if (page === 'change-phone') return 'profile';
        return page;
    }

    window.studentSettingsRememberReturn = function() {
        try { sessionStorage.setItem(returnKey, root.dataset.studentView); } catch (e) {}
    };

    function navigate(requested, options = {}) {
        let page = aliases[requested] || requested;
        // Older bookmarks still open the corresponding standalone registration page.
        if (page === 'face' || page === 'fingerprint') {
            const link = document.getElementById(page === 'face' ? 'studentFaceRegistrationLink' : 'studentFingerprintRegistrationLink');
            if (link?.href) {
                location.replace(link.href);
                return page;
            }
            page = 'biometrics';
        }
        if (!pages[page]) page = defaultPage();
        if (options.fromHistory && started && root.dataset.studentView === page) return page;
        root.dataset.studentView = page;

        const activeNavKey = resolveActiveNavKey(page);
        document.querySelectorAll('[data-settings-page]').forEach(button => {
            const isMatch = button.dataset.settingsPage === activeNavKey;
            button.classList.toggle('active', isMatch);
            if (isMatch) {
                button.setAttribute('aria-current', 'page');
            } else {
                button.removeAttribute('aria-current');
            }
        });

        root.querySelectorAll('.spanel').forEach(panel => {
            panel.classList.toggle('active', panel.id === 'tab-' + pages[page][2]);
        });
        root.querySelectorAll('.sec-group-panel').forEach(panel => {
            panel.style.display = pages[page]?.[3] && panel.id === 'sec-section-' + pages[page][3] ? 'block' : 'none';
        });

        if (backBtn) {
            const isSubPage = page.startsWith('change-');
            backBtn.hidden = !isSubPage;
            if (backLabel && isSubPage) {
                backLabel.textContent = page === 'change-phone'
                    ? 'Back to Profile & school details'
                    : 'Back to Password & email';
            }
        }

        if (pages[page]) {
            if (heading) heading.textContent = pages[page][0];
            if (description) description.textContent = pages[page][1];
        }

        if (page === 'device' && typeof window.checkDeviceBindingStatus === 'function') {
            window.checkDeviceBindingStatus();
        }

        const activeMobilePill = document.querySelector('.settings-mobile-pill.active');
        if (activeMobilePill && typeof activeMobilePill.scrollIntoView === 'function') {
            try {
                activeMobilePill.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            } catch (e) {}
        }

        if ((options.push || (!options.fromHistory && started))) {
            history.replaceState(null, '', '#tab-' + page);
        }

        if (options.focus && heading) {
            heading.focus({ preventScroll: true });
            root.scrollIntoView({ block: 'start', behavior: 'instant' });
        }
        started = true;
        return page;
    }
    window.studentSettingsNavigate = navigate;

    document.addEventListener('click', event => {
        const accountLink = event.target.closest('[data-account-page]');
        if (accountLink && root.contains(accountLink)) {
            event.preventDefault();
            navigate(accountLink.dataset.accountPage, { push: true, focus: true });
            return;
        }
        const accountBack = event.target.closest('[data-account-back]');
        if (accountBack && root.contains(accountBack)) {
            event.preventDefault();
            const current = root.dataset.studentView || '';
            navigate(current === 'change-phone' ? 'profile' : 'credentials', { push: true, focus: true });
            return;
        }
        const navBtn = event.target.closest('[data-settings-page]');
        if (!navBtn) return;
        event.preventDefault();
        navigate(navBtn.dataset.settingsPage, { push: true, focus: true });
        if (typeof window.closeSidebar === 'function' && window.matchMedia('(max-width: 767.98px)').matches) {
            window.closeSidebar();
        }
    });

    root.addEventListener('submit', event => {
        if (event.target instanceof HTMLFormElement && event.target.checkValidity()) {
            window.studentSettingsRememberReturn();
            if (event.target.id === 'personalInfoForm') {
                const button = document.getElementById('savePhoneBtn');
                if (button && !button.disabled) {
                    if (typeof window.setAccountEditStatus === 'function') {
                        window.setAccountEditStatus('phoneFormStatus', 'Saving your phone number…', 'info');
                    }
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Saving…';
                }
            }
        }
    });

    function syncHistory() { navigate(location.hash.replace(/^#(?:tab-)?/, '') || defaultPage(), { fromHistory: true }); }
    window.addEventListener('popstate', syncHistory);
    window.addEventListener('hashchange', syncHistory);

    document.addEventListener('DOMContentLoaded', () => {
        let returned = null;
        try { returned = sessionStorage.getItem(returnKey); sessionStorage.removeItem(returnKey); } catch (e) {}
        // Preserve the section after a form redirects with validation or success feedback.
        if (root.dataset.settingsFeedback === 'true') {
            const destination = returned || (root.dataset.settingsPhoneError === 'true' ? 'profile' : 'credentials');
            navigate(destination);

            if (destination === 'change-email' && root.dataset.settingsEmailError === 'true') {
                const addressError = root.dataset.settingsEmailAddressError === 'true';
                const firstStep = document.getElementById('emailStep1');
                const secondStep = document.getElementById('emailStep2');
                if (firstStep && secondStep) {
                    firstStep.style.display = addressError ? 'block' : 'none';
                    secondStep.style.display = addressError ? 'none' : 'block';
                    setAccountEditProgress('emailEditProgress', addressError ? 1 : 2);
                    setAccountEditStatus('emailFormStatus', root.dataset.settingsEmailErrorMessage || 'Check the address or code and try again.', 'error');
                    document.getElementById(addressError ? 'inputNewEmail' : 'ed1')?.focus();
                }
            }
            if (destination === 'change-password' && root.dataset.settingsPasswordError === 'true') {
                document.getElementById('otpStep1').style.display = 'none';
                document.getElementById('otpStep2').style.display = 'block';
                setAccountEditProgress('passwordEditProgress', 2);
                setAccountEditStatus('passwordFormStatus', root.dataset.settingsPasswordErrorMessage || 'Check your code and password details, then try again.', 'error');
                document.getElementById('sd1')?.focus();
            }
            if (root.dataset.settingsPhoneError === 'true') {
                setAccountEditStatus('phoneFormStatus', root.dataset.settingsPhoneErrorMessage || 'Check the phone number and try again.', 'error');
                document.getElementById('phoneInput')?.focus();
            }
        }
    }, { once: true });
})();
