(function () {
    'use strict';

    const root = document.querySelector('[data-teacher-settings]');
    if (!root) return;

    const config = window.__TEACHER_SETTINGS_CONFIG__ || {};
    const pages = {
        profile: ['Profile & faculty details', 'Manage your profile photo and review your faculty account details.', 'profile'],
        credentials: ['Password & email', 'Keep your sign-in details up to date. Changes require an email verification code.', 'credentials'],
        'change-email': ['Change email address', 'Verify the new address before it becomes your account email.', 'account-email'],
        'change-password': ['Change password', 'Confirm your email and choose a new password.', 'account-password'],
        passkeys: ['Device passkeys', 'Register this device\u2019s built-in biometrics or screen lock for faster sign-in.', 'passkeys'],
        recovery: ['Recovery codes', 'Save one-time offline backup codes in case you lose access to your account.', 'recovery'],
        preferences: ['Notification preferences', 'Choose how you receive alerts and customize your sidebar layout.', 'preferences'],
    };
    const aliases = {
        security: 'credentials',
        biometrics: 'passkeys',
        fingerprint: 'passkeys',
        notifications: 'preferences',
    };

    const heading = document.getElementById('teacherSettingsPageTitle');
    const description = document.getElementById('teacherSettingsPageDescription');
    const backBtn = root.querySelector('[data-account-back]');
    const returnKey = 'teacher_settings_return_' + (root.dataset.teacherSettings || 'current');
    let started = false;

    function placeAccountEditForms() {
        const sources = [
            ['email', 'teacherAccountEmailSlot'],
            ['password', 'teacherAccountPasswordSlot'],
        ];
        let moved = 0;
        sources.forEach(([name, slotId]) => {
            const source = root.querySelector('[data-account-edit-source="' + name + '"]');
            const slot = document.getElementById(slotId);
            if (source && slot) {
                slot.appendChild(source);
                moved++;
            }
        });

        if (moved === sources.length) {
            const formGrid = document.getElementById('teacherCredentialFormGrid');
            const shortcuts = document.getElementById('teacherCredentialShortcuts');
            if (formGrid) formGrid.hidden = true;
            if (shortcuts) shortcuts.hidden = false;
            root.dataset.accountFormsReady = 'true';
        }
    }

    function resolveActiveNavKey(page) {
        if (page === 'change-email' || page === 'change-password') return 'credentials';
        return page;
    }

    function rememberReturnView() {
        try {
            sessionStorage.setItem(returnKey, root.dataset.settingsView || 'profile');
        } catch (e) {}
    }

    function setEditStatus(id, message, tone) {
        const region = document.getElementById(id);
        if (!region) return false;
        region.textContent = message || '';
        region.dataset.tone = tone || 'info';
        region.hidden = !message;
        return true;
    }

    function announceEditStatus(id, message, tone) {
        if (!setEditStatus(id, message, tone) && message) {
            if (typeof window.showToast === 'function') {
                window.showToast(message, tone === 'error' ? 'error' : 'info');
            } else {
                window.alert(message);
            }
        }
    }

    function setEditProgress(id, step) {
        const progress = document.getElementById(id);
        if (!progress) return;
        progress.querySelectorAll('[data-account-step]').forEach((item) => {
            const num = Number(item.dataset.accountStep);
            if (num === step) item.setAttribute('aria-current', 'step');
            else item.removeAttribute('aria-current');
            item.classList.toggle('is-complete', num < step);
        });
    }

    function setButtonBusy(button, busy, busyLabel) {
        if (!button) return;
        if (!button.dataset.idleHtml) button.dataset.idleHtml = button.innerHTML;
        button.disabled = busy;
        button.setAttribute('aria-busy', busy ? 'true' : 'false');
        button.innerHTML = busy
            ? '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + busyLabel
            : button.dataset.idleHtml;
    }

    function navigate(requested, options) {
        const opts = options || {};
        let page = aliases[requested] || requested;
        if (!pages[page]) page = 'profile';
        if (opts.fromHistory && started && root.dataset.settingsView === page) return page;

        root.dataset.settingsView = page;

        const activeNavKey = resolveActiveNavKey(page);
        document.querySelectorAll('[data-settings-page]').forEach((btn) => {
            const isMatch = btn.dataset.settingsPage === activeNavKey;
            btn.classList.toggle('active', isMatch);
            if (isMatch) btn.setAttribute('aria-current', 'page');
            else btn.removeAttribute('aria-current');
        });

        root.querySelectorAll('.spanel').forEach((panel) => {
            panel.classList.toggle('active', panel.id === 'tab-' + pages[page][2]);
        });

        if (backBtn) {
            backBtn.hidden = !page.startsWith('change-');
        }

        if (heading) heading.textContent = pages[page][0];
        if (description) description.textContent = pages[page][1];

        if (page === 'passkeys' && typeof window.loadDevices === 'function') {
            window.loadDevices();
        }

        const activePill = root.querySelector('.settings-mobile-pill.active');
        if (activePill && typeof activePill.scrollIntoView === 'function') {
            try {
                activePill.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            } catch (e) {}
        }

        const targetHash = '#tab-' + page;
        if (opts.push && window.location.hash !== targetHash) {
            history.pushState(null, '', targetHash);
        } else if (!opts.fromHistory && started && window.location.hash !== targetHash) {
            history.replaceState(null, '', targetHash);
        }

        if (opts.focus && heading) {
            heading.focus({ preventScroll: true });
            root.scrollIntoView({ block: 'start', behavior: 'instant' });
        }

        started = true;
        return page;
    }

    function wireOtpDigits(inputs) {
        inputs.forEach((input, index) => {
            input.addEventListener('input', (event) => {
                const digits = event.target.value.replace(/\D/g, '').slice(0, inputs.length);
                if (digits.length > 1) {
                    digits.split('').forEach((digit, offset) => {
                        if (inputs[index + offset]) inputs[index + offset].value = digit;
                    });
                } else {
                    event.target.value = digits;
                }
                const next = Array.from(inputs).slice(index + 1).find((f) => !f.value)
                    || Array.from(inputs).find((f) => !f.value);
                (next || inputs[inputs.length - 1])?.focus();
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && !input.value && index > 0) inputs[index - 1].focus();
                if (event.key === 'ArrowLeft' && index > 0) inputs[index - 1].focus();
                if (event.key === 'ArrowRight' && index < inputs.length - 1) inputs[index + 1].focus();
            });

            input.addEventListener('paste', (event) => {
                const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, inputs.length);
                if (!pasted) return;
                pasted.split('').forEach((digit, offset) => {
                    if (inputs[offset]) inputs[offset].value = digit;
                });
                event.preventDefault();
                (inputs[Math.min(pasted.length, inputs.length) - 1] || inputs[0])?.focus();
            });
        });
    }

    async function parseJsonResponse(response) {
        let data = {};
        try {
            data = await response.json();
        } catch (e) {}
        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'We could not send the code. Please try again in a moment.');
        }
        return data;
    }

    function togglePasswordField(btn) {
        const targetId = btn.getAttribute('data-toggle-password');
        const input = targetId ? document.getElementById(targetId) : null;
        if (!input) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        const icon = btn.querySelector('i');
        if (icon) icon.className = isPassword ? 'bi bi-eye' : 'bi bi-eye-slash';

        const isConf = input.name === 'password_confirmation' || targetId === 'tpw2';
        const label = isPassword
            ? (isConf ? 'Hide password confirmation' : 'Hide password')
            : (isConf ? 'Show password confirmation' : 'Show password');
        btn.setAttribute('aria-label', label);
        btn.setAttribute('title', label);
        btn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
    }

    function initOtpAndCredentials() {
        const emailDigits = document.querySelectorAll('.email-otp-digit');
        const passwordDigits = document.querySelectorAll('.otp-digit-s');
        wireOtpDigits(emailDigits);
        wireOtpDigits(passwordDigits);

        const sendEmailBtn = document.getElementById('sendEmailOtpBtn');
        const verifyEmailBtn = document.getElementById('verifyEmailOtpBtn');
        const cancelEmailBtn = document.getElementById('cancelEmailOtpBtn');
        const sendPasswordBtn = document.getElementById('sendPasswordOtpBtn');
        const verifyPasswordBtn = document.getElementById('verifyPasswordOtpBtn');
        const cancelPasswordBtn = document.getElementById('cancelPasswordOtpBtn');

        if (sendEmailBtn) {
            sendEmailBtn.addEventListener('click', async () => {
                const newEmailInput = document.getElementById('inputNewEmail');
                const newEmail = newEmailInput ? newEmailInput.value.trim() : '';
                if (!newEmailInput || !newEmailInput.reportValidity()) return;
                if (sendEmailBtn.disabled) return;

                setEditStatus('emailFormStatus', '', 'info');
                setButtonBusy(sendEmailBtn, true, 'Sending code\u2026');
                try {
                    const response = await fetch(config.routes?.otpEmailSend || '', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': config.csrfToken || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ new_email: newEmail }),
                    });
                    const data = await parseJsonResponse(response);
                    document.getElementById('emailStep1').style.display = 'none';
                    document.getElementById('emailStep2').style.display = 'block';
                    setEditProgress('emailEditProgress', 2);
                    setButtonBusy(sendEmailBtn, false);
                    announceEditStatus('emailFormStatus', 'Code sent. Enter it below to verify the new address.', 'success');

                    const dest = document.getElementById('emailSentDestination');
                    if (dest) dest.textContent = data.target_email || newEmail;
                    const confirmInput = document.getElementById('confirmNewEmailInput');
                    if (confirmInput) confirmInput.value = newEmail;

                    if (data.dev_otp) {
                        const codeStr = String(data.dev_otp).trim();
                        emailDigits.forEach((d, idx) => { d.value = codeStr[idx] || ''; });
                        const hidden = document.getElementById('emailOtpHidden');
                        if (hidden) hidden.value = codeStr;
                    }
                    emailDigits[0]?.focus();
                } catch (err) {
                    setButtonBusy(sendEmailBtn, false);
                    announceEditStatus('emailFormStatus', err.message || 'We could not send the code.', 'error');
                }
            });
        }

        if (verifyEmailBtn) {
            verifyEmailBtn.addEventListener('click', () => {
                const otp = Array.from(emailDigits).map((d) => d.value).join('');
                const missing = Array.from(emailDigits).find((d) => !d.value);
                if (!/^\d{6}$/.test(otp)) {
                    announceEditStatus('emailFormStatus', 'Enter all 6 digits from the email code.', 'error');
                    (missing || emailDigits[0])?.focus();
                    return;
                }
                const form = verifyEmailBtn.closest('form');
                if (!form || !form.reportValidity()) return;
                document.getElementById('emailOtpHidden').value = otp;
                setEditStatus('emailFormStatus', '', 'info');
                rememberReturnView();
                setButtonBusy(verifyEmailBtn, true, 'Updating email\u2026');
                if (form.requestSubmit) form.requestSubmit();
                else form.submit();
            });
        }

        if (cancelEmailBtn) {
            cancelEmailBtn.addEventListener('click', () => {
                document.getElementById('emailStep1').style.display = 'block';
                document.getElementById('emailStep2').style.display = 'none';
                emailDigits.forEach((d) => { d.value = ''; });
                const hidden = document.getElementById('emailOtpHidden');
                if (hidden) hidden.value = '';
                setEditProgress('emailEditProgress', 1);
                setEditStatus('emailFormStatus', '', 'info');
                document.getElementById('inputNewEmail')?.focus();
            });
        }

        if (sendPasswordBtn) {
            sendPasswordBtn.addEventListener('click', async () => {
                if (sendPasswordBtn.disabled) return;
                setEditStatus('passwordFormStatus', '', 'info');
                setButtonBusy(sendPasswordBtn, true, 'Sending code\u2026');
                try {
                    const response = await fetch(config.routes?.otpChangeSend || '', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': config.csrfToken || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                    });
                    const data = await parseJsonResponse(response);
                    document.getElementById('otpStep1').style.display = 'none';
                    document.getElementById('otpStep2').style.display = 'block';
                    setEditProgress('passwordEditProgress', 2);
                    setButtonBusy(sendPasswordBtn, false);
                    announceEditStatus('passwordFormStatus', 'Code sent. Enter it below to continue.', 'success');

                    if (data.dev_otp) {
                        const codeStr = String(data.dev_otp).trim();
                        passwordDigits.forEach((d, idx) => { d.value = codeStr[idx] || ''; });
                        const hidden = document.getElementById('settingsOtpHidden');
                        if (hidden) hidden.value = codeStr;
                    }
                    passwordDigits[0]?.focus();
                } catch (err) {
                    setButtonBusy(sendPasswordBtn, false);
                    announceEditStatus('passwordFormStatus', err.message || 'We could not send the code.', 'error');
                }
            });
        }

        if (verifyPasswordBtn) {
            verifyPasswordBtn.addEventListener('click', () => {
                const otp = Array.from(passwordDigits).map((d) => d.value).join('');
                const missing = Array.from(passwordDigits).find((d) => !d.value);
                if (!/^\d{6}$/.test(otp)) {
                    announceEditStatus('passwordFormStatus', 'Enter all 6 digits from the email code.', 'error');
                    (missing || passwordDigits[0])?.focus();
                    return;
                }
                const pw = document.getElementById('tpw1');
                const conf = document.getElementById('tpw2');
                const val = pw?.value || '';
                if (val.length < 12 || !/[a-z]/.test(val) || !/[A-Z]/.test(val) || !/\d/.test(val) || !/[^A-Za-z0-9]/.test(val)) {
                    announceEditStatus('passwordFormStatus', 'Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol.', 'error');
                    pw?.focus();
                    return;
                }
                if (val !== (conf?.value || '')) {
                    announceEditStatus('passwordFormStatus', 'The passwords do not match yet. Check the confirmation field.', 'error');
                    conf?.focus();
                    return;
                }
                const form = verifyPasswordBtn.closest('form');
                if (!form || !form.reportValidity()) return;
                document.getElementById('settingsOtpHidden').value = otp;
                setEditStatus('passwordFormStatus', '', 'info');
                rememberReturnView();
                setButtonBusy(verifyPasswordBtn, true, 'Updating password\u2026');
                if (form.requestSubmit) form.requestSubmit();
                else form.submit();
            });
        }

        if (cancelPasswordBtn) {
            cancelPasswordBtn.addEventListener('click', () => {
                document.getElementById('otpStep1').style.display = 'block';
                document.getElementById('otpStep2').style.display = 'none';
                passwordDigits.forEach((d) => { d.value = ''; });
                const hidden = document.getElementById('settingsOtpHidden');
                if (hidden) hidden.value = '';
                const pw1 = document.getElementById('tpw1');
                const pw2 = document.getElementById('tpw2');
                if (pw1) pw1.value = '';
                if (pw2) pw2.value = '';
                setEditProgress('passwordEditProgress', 1);
                setEditStatus('passwordFormStatus', '', 'info');
                document.getElementById('sendPasswordOtpBtn')?.focus();
            });
        }

        root.querySelectorAll('[data-toggle-password]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                togglePasswordField(btn);
            });
        });
    }

    function initRecoveryAndUtilities() {
        const generateBtn = document.getElementById('generateCodesBtn');
        const copyAllBtn = document.getElementById('copyAllRecoveryBtn');
        const downloadBtn = document.getElementById('downloadRecoveryBtn');
        const registerFpBtn = document.getElementById('registerFpBtn');
        const compactToggle = document.getElementById('compactToggle');

        if (generateBtn) {
            generateBtn.addEventListener('click', async () => {
                if (!window.confirm('Are you sure? Generating new recovery codes will invalidate all your old codes.')) return;
                const orig = generateBtn.innerHTML;
                generateBtn.disabled = true;
                generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Generating\u2026';

                try {
                    const response = await fetch(config.routes?.recoveryGenerate || '', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': config.csrfToken || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                    });
                    const data = await response.json();
                    if (data.success && Array.isArray(data.codes)) {
                        const container = document.getElementById('codesContainer');
                        container.innerHTML = '';
                        data.codes.forEach((code) => {
                            const chip = document.createElement('div');
                            chip.className = 'recovery-code-chip';
                            chip.textContent = code;
                            container.appendChild(chip);
                        });
                        const listWrap = document.getElementById('recoveryCodesList');
                        if (listWrap) listWrap.style.display = 'block';
                        if (typeof window.showToast === 'function') {
                            window.showToast('New recovery codes generated! Please save them now.', 'success');
                        }
                    } else {
                        window.alert(data.message || 'Failed to generate recovery codes.');
                    }
                } catch (err) {
                    window.alert('Network error. Please try again.');
                } finally {
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = orig;
                }
            });
        }

        if (copyAllBtn) {
            copyAllBtn.addEventListener('click', () => {
                const chips = document.querySelectorAll('#codesContainer .recovery-code-chip');
                if (!chips.length) return;
                const codes = Array.from(chips).map((el) => el.textContent.trim()).join('\n');
                navigator.clipboard.writeText(codes).then(() => {
                    const orig = copyAllBtn.innerHTML;
                    copyAllBtn.innerHTML = '<i class="bi bi-check2 me-1" aria-hidden="true"></i>Copied';
                    if (typeof window.showToast === 'function') window.showToast('Recovery codes copied!', 'success');
                    setTimeout(() => { copyAllBtn.innerHTML = orig; }, 2000);
                });
            });
        }

        if (downloadBtn) {
            downloadBtn.addEventListener('click', () => {
                const chips = document.querySelectorAll('#codesContainer .recovery-code-chip');
                if (!chips.length) return;
                const codes = Array.from(chips).map((el) => el.textContent.trim()).join('\n');
                const content = 'SMART ATTENDANCE - EMERGENCY RECOVERY CODES\n'
                    + 'Generated: ' + new Date().toLocaleString() + '\n'
                    + 'Account: ' + String(config.userEmail || '') + '\n\n'
                    + codes + '\n\nStore these keys in a safe, offline location.';
                const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'teacher-recovery-codes-' + Date.now() + '.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                if (typeof window.showToast === 'function') window.showToast('Recovery codes downloaded!', 'info');
            });
        }

        root.querySelectorAll('[data-copy-text]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const text = btn.getAttribute('data-copy-text') || '';
                const label = btn.getAttribute('data-copy-label') || 'Value';
                if (!text || !navigator.clipboard) return;
                navigator.clipboard.writeText(text).then(() => {
                    if (typeof window.showToast === 'function') {
                        window.showToast(label + ' copied!', 'info');
                    }
                });
            });
        });

        if (registerFpBtn) {
            registerFpBtn.addEventListener('click', () => {
                if (typeof window.registerDevicePasskey === 'function') {
                    window.registerDevicePasskey();
                }
            });
        }

        if (compactToggle) {
            compactToggle.checked = localStorage.getItem('sidebarMini') === 'true';
            compactToggle.addEventListener('change', function () {
                localStorage.setItem('sidebarMini', String(this.checked));
                if (typeof window.applyMini === 'function' && !window.matchMedia('(max-width: 767.98px)').matches) {
                    window.applyMini(this.checked, true);
                } else {
                    window.location.reload();
                }
            });
            const burgerBtn = document.getElementById('burgerBtn');
            const sidebarEl = document.getElementById('sidebar');
            if (burgerBtn && sidebarEl) {
                burgerBtn.addEventListener('click', () => {
                    setTimeout(() => {
                        compactToggle.checked = sidebarEl.classList.contains('collapsed');
                    }, 0);
                });
            }
        }
    }

    placeAccountEditForms();
    initOtpAndCredentials();
    initRecoveryAndUtilities();

    document.addEventListener('click', (event) => {
        const accountLink = event.target.closest('[data-account-page]');
        if (accountLink && root.contains(accountLink)) {
            event.preventDefault();
            navigate(accountLink.dataset.accountPage, { push: true, focus: true });
            return;
        }

        const accountBack = event.target.closest('[data-account-back]');
        if (accountBack && root.contains(accountBack)) {
            event.preventDefault();
            navigate('credentials', { push: true, focus: true });
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

    root.addEventListener('submit', (event) => {
        if (event.target instanceof HTMLFormElement && event.target.checkValidity()) {
            rememberReturnView();
        }
    });

    function syncFromHash() {
        const raw = window.location.hash.replace(/^#(?:tab-)?/, '') || 'profile';
        navigate(raw, { fromHistory: true });
    }

    window.addEventListener('popstate', syncFromHash);
    window.addEventListener('hashchange', syncFromHash);

    function bootstrapInitialView() {
        let returned = null;
        try {
            returned = sessionStorage.getItem(returnKey);
            sessionStorage.removeItem(returnKey);
        } catch (e) {}

        if (root.dataset.settingsFeedback === 'true') {
            const emailErr = root.dataset.settingsEmailError === 'true';
            const pwErr = root.dataset.settingsPasswordError === 'true';
            const destination = returned || (emailErr ? 'change-email' : (pwErr ? 'change-password' : 'preferences'));
            navigate(destination, { fromHistory: true });

            if (destination === 'change-email' && emailErr) {
                const addressErr = root.dataset.settingsEmailAddressError === 'true';
                const step1 = document.getElementById('emailStep1');
                const step2 = document.getElementById('emailStep2');
                if (step1 && step2) {
                    step1.style.display = addressErr ? 'block' : 'none';
                    step2.style.display = addressErr ? 'none' : 'block';
                    setEditProgress('emailEditProgress', addressErr ? 1 : 2);
                    setEditStatus('emailFormStatus', root.dataset.settingsEmailErrorMessage || 'Check the address or code and try again.', 'error');
                    document.getElementById(addressErr ? 'inputNewEmail' : 'ed1')?.focus();
                }
            }

            if (destination === 'change-password' && pwErr) {
                document.getElementById('otpStep1').style.display = 'none';
                document.getElementById('otpStep2').style.display = 'block';
                setEditProgress('passwordEditProgress', 2);
                setEditStatus('passwordFormStatus', root.dataset.settingsPasswordErrorMessage || 'Check your code and password details, then try again.', 'error');
                document.getElementById('sd1')?.focus();
            }
            return;
        }

        const hashSection = window.location.hash.replace(/^#(?:tab-)?/, '');
        const querySection = new URLSearchParams(window.location.search).get('section') || '';
        navigate(hashSection || querySection || 'profile', { fromHistory: false });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrapInitialView, { once: true });
    } else {
        bootstrapInitialView();
    }
})();
