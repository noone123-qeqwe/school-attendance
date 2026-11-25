async function checkForAppUpdates() {
    const btn = document.getElementById('checkUpdateBtn');
    const feedback = document.getElementById('updateFeedbackArea');
    const statusText = document.getElementById('updateStatusText');

    if (!btn || !feedback) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width:14px;height:14px;"></span> Checking...';
    feedback.style.display = 'block';
    feedback.style.background = 'rgba(59, 130, 246, 0.1)';
    feedback.style.border = '1px solid rgba(59, 130, 246, 0.3)';
    feedback.style.color = '#93c5fd';
    feedback.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>Connecting to server and checking for updates...';

    if (!navigator.onLine) {
        feedback.style.background = 'rgba(239, 68, 68, 0.1)';
        feedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
        feedback.style.color = '#fca5a5';
        feedback.innerHTML = '<i class="bi bi-wifi-off me-2"></i>You are currently offline. Please connect to the internet to check for updates.';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-arrow-down me-1"></i> Check for Updates';
        return;
    }

    try {
        let result = null;
        if (typeof checkServerVersion === 'function') {
            result = await checkServerVersion(true, true);
        } else if ('serviceWorker' in navigator) {
            const reg = await navigator.serviceWorker.getRegistration();
            if (reg) {
                await reg.update();
                if (reg.waiting && typeof showAppUpdatePopup === 'function') {
                    showAppUpdatePopup(null, true);
                }
            }
        }

        await new Promise(r => setTimeout(r, 600));

        const popup = document.getElementById('pwaSystemUpdatePopup');
        const popupVisible = popup && window.getComputedStyle(popup).display !== 'none';

        if (result && result.checkFailed) {
            feedback.style.background = 'rgba(239, 68, 68, 0.1)';
            feedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
            feedback.style.color = '#fca5a5';
            feedback.textContent = 'Unable to check for updates right now. Please try again when connected.';
        } else if (popupVisible || (result && result.updateAvailable)) {
            feedback.style.background = 'rgba(207, 164, 111, 0.12)';
            feedback.style.border = '1px solid rgba(207, 164, 111, 0.35)';
            feedback.style.color = '#f3e7cd';
            feedback.innerHTML = '<i class="bi bi-stars me-2"></i>A new software update is ready! Tap "Update Now" on the notification to use it.';
        } else {
            feedback.style.background = 'rgba(16, 185, 129, 0.1)';
            feedback.style.border = '1px solid rgba(16, 185, 129, 0.3)';
            feedback.style.color = '#6ee7b7';
            feedback.innerHTML = '<div style="display:flex; align-items:flex-start; gap:8px;"><i class="bi bi-check-circle-fill me-1" style="font-size:1.1rem; color:#22c55e;"></i><div><strong>You’re up to date ✓</strong><div style="font-size:0.85em; opacity:0.9; margin-top:2px;">Your system is running the deployed version (v' + String((window.__SETTINGS_CONFIG__ || {}).installedVersion || '') + ').</div></div></div>';
        }
        if (statusText) statusText.textContent = 'Last checked: Just now';
    } catch (e) {
        feedback.style.background = 'rgba(239, 68, 68, 0.1)';
        feedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
        feedback.style.color = '#fca5a5';
        feedback.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Unable to complete update check: ' + (e.message || 'Network error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-arrow-down me-1"></i> Check for Updates';
    }
}

function applySwUpdate() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistration().then(reg => {
            if (reg && reg.waiting) {
                reg.waiting.postMessage({ action: 'skipWaiting' });
            } else {
                window.location.reload();
            }
        });
    } else {
        window.location.reload();
    }
}

function updateStabsScrollArrows() {
    const nav = document.getElementById('stabsNav');
    const leftBtn = document.getElementById('stabsArrowLeft');
    const rightBtn = document.getElementById('stabsArrowRight');
    if (!nav || !leftBtn || !rightBtn) return;
    if (nav.clientWidth === 0) return;

    const wrapper = nav.closest('.stabs-wrapper');
    const maxScrollLeft = Math.max(0, nav.scrollWidth - nav.clientWidth);
    const reservedArrowSpace = Number.parseFloat(window.getComputedStyle(nav).paddingRight) || 0;
    // Ignore overflow caused only by the space reserved for the arrow itself.
    const hasScrollableContent = nav.scrollWidth - reservedArrowSpace > nav.clientWidth + 3;

    const canScrollLeft = hasScrollableContent && nav.scrollLeft > 3;
    leftBtn.style.display = canScrollLeft ? 'flex' : 'none';
    leftBtn.setAttribute('aria-hidden', canScrollLeft ? 'false' : 'true');
    leftBtn.tabIndex = canScrollLeft ? 0 : -1;

    const canScrollRight = hasScrollableContent && nav.scrollLeft < maxScrollLeft - 3;
    rightBtn.style.display = canScrollRight ? 'flex' : 'none';
    rightBtn.setAttribute('aria-hidden', canScrollRight ? 'false' : 'true');
    rightBtn.tabIndex = canScrollRight ? 0 : -1;
    rightBtn.setAttribute('title', canScrollRight ? 'More settings categories. Scroll right.' : '');
    wrapper?.classList.toggle('has-scroll-left', canScrollLeft);
    wrapper?.classList.toggle('has-scroll-right', canScrollRight);
}

window.scrollStabs = function(direction) {
    const nav = document.getElementById('stabsNav');
    if (!nav) return;
    const distance = 160;
    nav.scrollBy({
        left: direction === 'right' ? distance : -distance,
        behavior: 'smooth'
    });
    setTimeout(updateStabsScrollArrows, 200);
};

function syncUnifiedSettingsSidebar(activeKey) {
    const map = {
        security: 'credentials',
        fingerprint: 'biometrics',
    };
    const key = map[activeKey] || activeKey || 'profile';
    document.querySelectorAll('[data-settings-page]').forEach(btn => {
        const isMatch = btn.dataset.settingsPage === key;
        btn.classList.toggle('active', isMatch);
        if (isMatch) btn.setAttribute('aria-current', 'page');
        else btn.removeAttribute('aria-current');
    });
}

window.switchSecuritySub = function(subKey, btn) {
    if (window.studentSettingsNavigate) return window.studentSettingsNavigate('security-' + (subKey === 'fingerprint' ? 'biometrics' : (subKey || 'all')));
    if (!subKey) subKey = 'all';
    if (subKey === 'fingerprint') subKey = 'biometrics';

    // 1. Ensure parent tab-security panel is active
    const secPanel = document.getElementById('tab-security');
    if (secPanel && !secPanel.classList.contains('active')) {
        document.querySelectorAll('.spanel').forEach(p => p.classList.remove('active'));
        secPanel.classList.add('active');
        document.querySelectorAll('.stab').forEach(b => b.classList.remove('active'));
        const secStab = document.querySelector('.stab[data-tab="security"]');
        if (secStab) secStab.classList.add('active');
    }

    // 2. Update subnav filter pills
    document.querySelectorAll('.sec-subnav-pill').forEach(pill => {
        if (pill.getAttribute('data-sec-sub') === subKey) {
            pill.classList.add('active');
        } else {
            pill.classList.remove('active');
        }
    });

    // 3. Update sidebar active status
    syncUnifiedSettingsSidebar(subKey === 'all' ? 'credentials' : subKey);

    // 4. Show/hide security group panels
    const sections = {
        credentials: document.getElementById('sec-section-credentials'),
        biometrics: document.getElementById('sec-section-biometrics'),
        device: document.getElementById('sec-section-device'),
        recovery: document.getElementById('sec-section-recovery')
    };

    if (subKey === 'all') {
        Object.values(sections).forEach(s => { if (s) s.style.display = 'block'; });
    } else {
        Object.entries(sections).forEach(([key, section]) => {
            if (!section) return;
            section.style.display = key === subKey ? 'block' : 'none';
        });
    }

    // 5. Special handlers for biometrics or device sub-sections
    if (subKey === 'biometrics' || subKey === 'all') {
        if (typeof loadDevices === 'function') loadDevices();
    }
    if (subKey === 'device' || subKey === 'all') {
        if (typeof checkDeviceBindingStatus === 'function') checkDeviceBindingStatus();
    }

    // 6. Update breadcrumbs
    if (typeof window.updateSettingsBreadcrumbs === 'function') {
        if (subKey === 'biometrics') window.updateSettingsBreadcrumbs('fingerprint');
        else if (subKey === 'device') window.updateSettingsBreadcrumbs('device');
        else window.updateSettingsBreadcrumbs('security', subKey);
    }

    // 7. Update URL hash
    if (window.history && window.history.replaceState) {
        let hashTarget = 'security';
        if (subKey === 'biometrics') hashTarget = 'fingerprint';
        else if (subKey === 'device') hashTarget = 'device';
        else if (subKey !== 'all') hashTarget = 'security-' + subKey;
        window.history.replaceState(null, null, '#tab-' + hashTarget);
    }

    if (window.triggerHaptic) window.triggerHaptic('light');

    // 8. If switching to a specific sub-section, scroll smoothly to it
    if (subKey !== 'all' && sections[subKey]) {
        try {
            sections[subKey].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (e) {}
    }
};

window.switchTab = function(id, btn) {
    if (window.studentSettingsNavigate) return window.studentSettingsNavigate(id);
    if (!id) return;
    if (id === 'biometrics') id = 'fingerprint';

    // If target is biometric sensors, device binding, credentials, or recovery, route into unified Security section
    if (id === 'fingerprint' || id === 'device' || id === 'credentials' || id === 'recovery') {
        document.querySelectorAll('.spanel').forEach(p => p.classList.remove('active'));
        const secPanel = document.getElementById('tab-security');
        if (secPanel) secPanel.classList.add('active');

        document.querySelectorAll('.stab').forEach(b => b.classList.remove('active'));
        const secStab = document.querySelector('.stab[data-tab="security"]');
        if (secStab) {
            secStab.classList.add('active');
            if (typeof secStab.scrollIntoView === 'function') {
                secStab.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            }
        }

        const subKey = (id === 'fingerprint') ? 'biometrics' : id;
        window.switchSecuritySub(subKey, btn);
        setTimeout(updateStabsScrollArrows, 300);
        return;
    }

    // Standard primary tab switching (profile, security, family, preferences)
    document.querySelectorAll('.spanel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.stab').forEach(b => b.classList.remove('active'));

    const targetPanel = document.getElementById('tab-' + id);
    if (targetPanel) {
        targetPanel.classList.add('active');
    }

    let targetBtn = btn;
    if (!targetBtn || !targetBtn.classList || !targetBtn.classList.contains('stab')) {
        targetBtn = document.querySelector(`.stab[data-tab="${id}"]`) || Array.from(document.querySelectorAll('.stab')).find(b => b.textContent.toLowerCase().includes(id));
    }
    if (targetBtn) {
        targetBtn.classList.add('active');
        if (typeof targetBtn.scrollIntoView === 'function') {
            targetBtn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }
    }

    syncUnifiedSettingsSidebar(id);

    if (id === 'security') {
        window.switchSecuritySub('all');
    }

    if (typeof window.updateSettingsBreadcrumbs === 'function') {
        window.updateSettingsBreadcrumbs(id);
    }

    if (window.triggerHaptic) window.triggerHaptic('light');
    if (window.history && window.history.replaceState) {
        window.history.replaceState(null, null, '#tab-' + id);
    }
    setTimeout(updateStabsScrollArrows, 300);
};

window.updateSettingsBreadcrumbs = function(tabId, secSub) {
    const meta = {
        'profile': { cat: 'Account', name: 'Profile & Identity' },
        'family': { cat: 'Account', name: 'Family & Guardian' },
        'security': { cat: 'Security & Access', name: 'Security & Authentication Controls' },
        'fingerprint': { cat: 'Security & Access', name: 'Biometrics Verification (FIDO2)' },
        'device': { cat: 'Security & Access', name: 'Device Binding & Telemetry' },
        'preferences': { cat: 'System & Academics', name: 'System Preferences' }
    };
    let info = meta[tabId] || { cat: 'Settings', name: tabId.charAt(0).toUpperCase() + tabId.slice(1) };
    if (tabId === 'security' && secSub && secSub !== 'all') {
        if (secSub === 'credentials') info = { cat: 'Security & Access', name: 'Password & Email Credentials' };
        else if (secSub === 'biometrics') info = { cat: 'Security & Access', name: 'Biometrics Verification (FIDO2)' };
        else if (secSub === 'device') info = { cat: 'Security & Access', name: 'Device Binding & Telemetry' };
        else if (secSub === 'recovery') info = { cat: 'Security & Access', name: 'Recovery codes' };
    }
    const catEl = document.getElementById('settingsBreadcrumbCategory');
    const tabEl = document.getElementById('settingsBreadcrumbTab');
    if (catEl) catEl.textContent = info.cat;
    if (tabEl) tabEl.textContent = info.name;
};

// ── Attendance Device Binding API Handlers ──
window.deviceBindingExists = Boolean((window.__SETTINGS_CONFIG__ || {}).deviceBindingExists);
window.handleBindCurrentDevice = async function(stepUpPassword = null) {
    if (window.deviceBindingExists && !stepUpPassword) {
        stepUpPassword = prompt('Enter your account password to switch or re-verify this device:');
        if (!stepUpPassword) return;
    }

    const bindBtns = document.querySelectorAll('#tabDeviceBindBtn, #secQuickBindBtn');
    bindBtns.forEach(b => {
        b.disabled = true;
        b._origHtml = b.innerHTML;
        b.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Authorizing...';
    });

    try {
        let devKey = (typeof window.getOrCreateDeviceKey === 'function')
            ? window.getOrCreateDeviceKey()
            : (localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || '');
        let devFp = localStorage.getItem('saas_device_fp') || '';
        let devModel = '';
        let devMeta = (typeof window.getDeviceTelemetry === 'function') ? window.getDeviceTelemetry() : {};

        if (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.getNativeDeviceId === 'function') {
            devKey = window.AndroidDeviceBridge.getNativeDeviceId();
        } else if (window.AndroidBridge && typeof window.AndroidBridge.getNativeDeviceId === 'function') {
            devKey = window.AndroidBridge.getNativeDeviceId();
        }
        if (window.AndroidBridge && typeof window.AndroidBridge.getNativeDeviceModel === 'function') {
            devModel = window.AndroidBridge.getNativeDeviceModel();
        }
        if (!devFp && typeof window.getDeviceFingerprint === 'function') {
            devFp = window.getDeviceFingerprint();
        }
        if (!devModel && typeof window.getDeviceModel === 'function') {
            devModel = window.getDeviceModel();
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ((window.__SETTINGS_CONFIG__ || {}).csrfToken || '');

        const resp = await fetch(((window.__SETTINGS_CONFIG__ || {}).routes?.deviceBind || ''), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Device-Key': devKey,
                'X-Device-Fingerprint': devFp,
                'X-Device-Model': devModel,
                'X-Device-Metadata': JSON.stringify(devMeta),
            },
            body: JSON.stringify({
                device_key: devKey,
                device_fingerprint: devFp,
                device_model: devModel,
                device_metadata: devMeta,
                password: stepUpPassword,
                _token: token,
            }),
        });

        const data = await resp.json();
        if (resp.ok && data.success) {
            updateDeviceBindingUI(data);
            if (typeof showToast === 'function') {
                showToast(data.message || 'Device bound successfully!', 'success');
            } else {
                alert(data.message || 'Device bound successfully!');
            }
            if (window.triggerHaptic) window.triggerHaptic('success');
        } else {
            const err = data.message || 'Failed to bind device. Please try again.';
            if (typeof showToast === 'function') showToast(err, 'error');
            else alert(err);
        }
    } catch (err) {
        console.error('Device bind error:', err);
        if (typeof showToast === 'function') showToast('Network or server error while binding device.', 'error');
        else alert('Network error while binding device.');
    } finally {
        bindBtns.forEach(b => {
            b.disabled = false;
            if (b._origHtml) b.innerHTML = b._origHtml;
        });
    }
};

window.handleLockDevice = async function() {
    if (!confirm('Freeze attendance for this device? Attendance clock-ins will be locked until you enter your password to unlock it.')) {
        return;
    }

    const lockBtn = document.getElementById('tabDeviceLockBtn');
    if (lockBtn) {
        lockBtn.disabled = true;
        lockBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Locking...';
    }

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ((window.__SETTINGS_CONFIG__ || {}).csrfToken || '');
        const resp = await fetch(((window.__SETTINGS_CONFIG__ || {}).routes?.deviceLock || ''), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                reason: 'Student emergency anti-theft freeze',
                _token: token,
            }),
        });

        const data = await resp.json();
        if (resp.ok && data.success) {
            if (typeof showToast === 'function') showToast(data.message, 'success');
            else alert(data.message);
            window.checkDeviceBindingStatus();
        } else {
            const err = data.message || 'Failed to lock device.';
            if (typeof showToast === 'function') showToast(err, 'error');
            else alert(err);
        }
    } catch (err) {
        console.error('Lock error:', err);
        if (typeof showToast === 'function') showToast('Network error while locking device.', 'error');
    } finally {
        if (lockBtn) {
            lockBtn.disabled = false;
            lockBtn.innerHTML = '<i class="bi bi-lock-fill me-1"></i> Freeze / Lock';
        }
    }
};

window.handleUnlockDevice = async function() {
    const pw = prompt('Enter your account password to unlock attendance for this device:');
    if (!pw) return;

    const unlockBtn = document.getElementById('tabDeviceUnlockBtn');
    if (unlockBtn) {
        unlockBtn.disabled = true;
        unlockBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Unlocking...';
    }

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ((window.__SETTINGS_CONFIG__ || {}).csrfToken || '');
        const resp = await fetch(((window.__SETTINGS_CONFIG__ || {}).routes?.deviceUnlock || ''), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                password: pw,
                _token: token,
            }),
        });

        const data = await resp.json();
        if (resp.ok && data.success) {
            if (typeof showToast === 'function') showToast(data.message, 'success');
            else alert(data.message);
            window.checkDeviceBindingStatus();
        } else {
            const err = data.message || 'Incorrect password or failed to unlock.';
            if (typeof showToast === 'function') showToast(err, 'error');
            else alert(err);
        }
    } catch (err) {
        console.error('Unlock error:', err);
        if (typeof showToast === 'function') showToast('Network error while unlocking device.', 'error');
    } finally {
        if (unlockBtn) {
            unlockBtn.disabled = false;
            unlockBtn.innerHTML = '<i class="bi bi-unlock-fill me-1"></i> Unlock Device';
        }
    }
};

window.handleUnbindDevice = async function() {
    if (!confirm('Are you sure you want to unbind this device? You will need to re-bind a device to record classroom attendance.')) {
        return;
    }

    const password = prompt('Enter your account password to unbind the device:');
    if (!password) return;

    const unbindBtns = document.querySelectorAll('#tabDeviceUnbindBtn, #secQuickUnbindBtn');
    unbindBtns.forEach(b => {
        b.disabled = true;
        b._origHtml = b.innerHTML;
        b.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Unbinding...';
    });

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ((window.__SETTINGS_CONFIG__ || {}).csrfToken || '');

        const resp = await fetch(((window.__SETTINGS_CONFIG__ || {}).routes?.deviceUnbind || ''), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                password: password,
                _token: token,
            }),
        });

        const data = await resp.json();
        if (resp.ok && data.success) {
            updateDeviceBindingUI(data);
            if (typeof showToast === 'function') {
                showToast(data.message || 'Device unbound successfully.', 'success');
            } else {
                alert(data.message || 'Device unbound successfully.');
            }
            if (window.triggerHaptic) window.triggerHaptic('medium');
        } else {
            const err = data.message || 'Failed to unbind device. Please try again.';
            if (typeof showToast === 'function') showToast(err, 'error');
            else alert(err);
        }
    } catch (err) {
        console.error('Device unbind error:', err);
        if (typeof showToast === 'function') showToast('Network or server error while unbinding device.', 'error');
        else alert('Network error while unbinding device.');
    } finally {
        unbindBtns.forEach(b => {
            b.disabled = false;
            if (b._origHtml) b.innerHTML = b._origHtml;
        });
    }
};

function updateDeviceBindingUI(data) {
    const isBound = !!data.is_bound;
    window.deviceBindingExists = isBound;
    const isCurrent = !!data.is_current_device;
    const binding = data.binding;

    // 1. Update Security Tab Quick Card
    const secBadge = document.getElementById('secCardDeviceBadge');
    const secBadgeText = document.getElementById('secCardDeviceBadgeText');
    const secName = document.getElementById('secCardDeviceName');
    const secIcon = document.getElementById('secCardDeviceIcon');
    const secHint = document.getElementById('secCardDeviceHint');
    const secBindBtnText = document.getElementById('secQuickBindBtnText');
    const secUnbindBtn = document.getElementById('secQuickUnbindBtn');

    if (isBound && binding) {
        if (secBadge) {
            secBadge.className = 'sec-badge ' + (binding.is_locked ? 'sec-badge-danger' : (isCurrent ? 'sec-badge-emerald' : 'sec-badge-gold'));
        }
        if (secBadgeText) {
            secBadgeText.textContent = binding.is_locked ? 'Device Locked' : (isCurrent ? 'Bound to This Device' : 'Bound to Other Device');
        }
        if (secName) secName.textContent = binding.device_name || 'Registered Device';
        if (secIcon) secIcon.className = 'bi ' + (binding.device_icon || 'bi-phone') + (binding.is_locked ? ' text-danger me-2' : ' text-success me-2');
        if (secHint) {
            secHint.innerHTML = `Bound device IP: <strong style="color:#f3e7cd;">${binding.ip_address || 'Unknown'}</strong> • Active ${binding.last_seen_human || 'Recently'}.`;
        }
        if (secBindBtnText) secBindBtnText.textContent = isCurrent ? 'Re-verify This Device' : 'Switch / Re-bind to This Device';
        if (secUnbindBtn) secUnbindBtn.style.display = 'inline-flex';
    } else {
        if (secBadge) secBadge.className = 'sec-badge';
        if (secBadgeText) secBadgeText.textContent = 'No Device Bound';
        if (secName) secName.textContent = 'No device currently registered';
        if (secIcon) secIcon.className = 'bi bi-phone text-muted me-2';
        if (secHint) secHint.textContent = 'Bind your smartphone, tablet, or laptop to unlock anti-proxy verified QR attendance clock-ins.';
        if (secBindBtnText) secBindBtnText.textContent = 'Bind to This Device';
        if (secUnbindBtn) secUnbindBtn.style.display = 'none';
    }

    // 2. Update Device Tab Full Page
    const heroPill = document.getElementById('deviceHeroPill');
    const heroPillText = document.getElementById('deviceHeroPillText');
    const trustScorePill = document.getElementById('deviceTrustScorePill');
    const trustScoreText = document.getElementById('deviceTrustScoreText');
    const lockedAlert = document.getElementById('deviceLockedAlert');
    const lockedReasonText = document.getElementById('deviceLockedReasonText');

    const boundStatusTile = document.getElementById('deviceTabBoundTile');
    const unboundStatusTile = document.getElementById('deviceTabUnboundTile');
    const boundDeviceName = document.getElementById('deviceTabBoundName');
    const boundDeviceIp = document.getElementById('deviceTabBoundIp');
    const boundDeviceChanges = document.getElementById('deviceTabBoundChanges');
    const boundDeviceTime = document.getElementById('deviceTabBoundTime');
    const boundDeviceIcon = document.getElementById('deviceTabBoundIcon');
    const boundDeviceTrust = document.getElementById('deviceTabBoundTrust');
    const tabBindBtnText = document.getElementById('tabDeviceBindBtnText');
    const tabUnbindBtn = document.getElementById('tabDeviceUnbindBtn');
    const tabLockBtn = document.getElementById('tabDeviceLockBtn');
    const currentDeviceMatchBadge = document.getElementById('currentDeviceMatchBadge');
    const deviceTabAuthorizedBadge = document.getElementById('deviceTabAuthorizedBadge');
    const deviceTabAuthorizedBadgeText = document.getElementById('deviceTabAuthorizedBadgeText');

    if (isBound && binding) {
        const isLocked = !!binding.is_locked;
        if (heroPill) {
            heroPill.className = 'sec-health-pill ' + (isLocked ? 'gold' : (isCurrent ? 'emerald' : 'gold'));
        }
        if (heroPillText) {
            heroPillText.textContent = isLocked ? 'Device Locked' : (isCurrent ? 'Bound to This Device' : 'Bound to Other Hardware');
        }

        if (trustScorePill) {
            trustScorePill.style.display = 'inline-flex';
            if (trustScoreText) {
                trustScoreText.textContent = `Trust Score: ${binding.trust_score || 85}/100 • ${binding.trust_level || 'VERIFIED'}`;
            }
        }

        if (lockedAlert) {
            lockedAlert.style.display = isLocked ? 'flex' : 'none';
            if (lockedReasonText && binding.locked_reason) {
                lockedReasonText.textContent = `Reason: ${binding.locked_reason}`;
            }
        }

        if (boundStatusTile) boundStatusTile.style.display = 'block';
        if (unboundStatusTile) unboundStatusTile.style.display = 'none';
        if (boundDeviceName) boundDeviceName.textContent = binding.device_name || 'Registered Attendance Device';
        if (boundDeviceIp) boundDeviceIp.textContent = binding.ip_address || 'Unknown IP';
        if (boundDeviceChanges) boundDeviceChanges.textContent = `(${binding.change_count || 0} switches)`;
        if (boundDeviceTime) boundDeviceTime.textContent = binding.last_seen_human || 'Just now';
        if (boundDeviceIcon) boundDeviceIcon.className = 'bi ' + (binding.device_icon || 'bi-phone');
        if (boundDeviceTrust) {
            boundDeviceTrust.textContent = `${binding.trust_level || 'VERIFIED'} (${binding.trust_score || 85}/100)`;
            boundDeviceTrust.style.color = isLocked ? '#f87171' : ((binding.trust_score || 85) >= 80 ? '#34d399' : '#fbbf24');
        }

        if (deviceTabAuthorizedBadge) {
            deviceTabAuthorizedBadge.className = 'sec-badge ' + (isLocked ? 'sec-badge-danger' : (isCurrent ? 'sec-badge-emerald' : 'sec-badge-gold'));
        }
        if (deviceTabAuthorizedBadgeText) {
            deviceTabAuthorizedBadgeText.textContent = isLocked ? 'Locked' : (isCurrent ? 'Authorized (This Device)' : 'Authorized (Other Device)');
        }

        if (tabBindBtnText) tabBindBtnText.textContent = isCurrent ? 'Re-verify Current Device' : 'Switch & Bind This Device';
        if (tabUnbindBtn) tabUnbindBtn.style.display = 'inline-flex';
        if (tabLockBtn) tabLockBtn.style.display = isLocked ? 'none' : 'inline-flex';

        if (currentDeviceMatchBadge) {
            currentDeviceMatchBadge.style.display = 'inline-flex';
            currentDeviceMatchBadge.className = 'sec-badge ' + (isCurrent ? 'sec-badge-emerald' : 'sec-badge-gold');
            currentDeviceMatchBadge.innerHTML = isCurrent 
                ? '<i class="bi bi-check-circle-fill me-1"></i> Authorized Device' 
                : '<i class="bi bi-exclamation-triangle-fill me-1"></i> Unmatched Device';
        }
    } else {
        if (heroPill) heroPill.className = 'sec-health-pill';
        if (heroPillText) heroPillText.textContent = 'Device Not Bound';
        if (trustScorePill) trustScorePill.style.display = 'none';
        if (lockedAlert) lockedAlert.style.display = 'none';
        if (boundStatusTile) boundStatusTile.style.display = 'none';
        if (unboundStatusTile) unboundStatusTile.style.display = 'block';
        if (deviceTabAuthorizedBadge) deviceTabAuthorizedBadge.className = 'sec-badge';
        if (deviceTabAuthorizedBadgeText) deviceTabAuthorizedBadgeText.textContent = 'Unregistered';
        if (tabBindBtnText) tabBindBtnText.textContent = 'Bind to This Device';
        if (tabUnbindBtn) tabUnbindBtn.style.display = 'none';
        if (currentDeviceMatchBadge) {
            currentDeviceMatchBadge.style.display = 'none';
        }
    }
}

window.checkDeviceBindingStatus = async function() {
    try {
        if (typeof window.getDeviceTelemetry === 'function') {
            const telemetry = window.getDeviceTelemetry();
            const gpuEl = document.getElementById('thisDeviceGpuText');
            if (gpuEl && telemetry.gpu_renderer) {
                let cleaned = telemetry.gpu_renderer.replace(/^ANGLE \([^,]+,\s*/i, '').replace(/\s+Direct3D.+$/i, '');
                gpuEl.textContent = cleaned || telemetry.gpu_renderer;
            } else if (gpuEl) {
                gpuEl.textContent = 'Standard Web Graphics Engine';
            }

            const dispEl = document.getElementById('thisDeviceDisplayCores');
            if (dispEl) {
                dispEl.textContent = `${telemetry.screen_res || '1080p'} @ ${telemetry.pixel_ratio || 1}x • ${telemetry.cores || 4} Logical Cores`;
            }

            const netEl = document.getElementById('thisDeviceNetworkText');
            if (netEl && telemetry.connection_type && telemetry.connection_type !== 'unknown') {
                netEl.textContent += ` (${telemetry.connection_type.toUpperCase()})`;
            }
        }

        if (typeof window.getDeviceModel === 'function') {
            const dm = window.getDeviceModel();
            const el = document.getElementById('thisDeviceModelText');
            if (el && dm) el.textContent = dm;
        }

        const resp = await fetch(((window.__SETTINGS_CONFIG__ || {}).routes?.deviceStatus || ''), {
            headers: { 'Accept': 'application/json' }
        });
        if (resp.ok) {
            const data = await resp.json();
            if (data.success) {
                updateDeviceBindingUI(data);
            }
        }
    } catch (e) {
        console.warn('Device status check error:', e);
    }
};


async function toggleWebPush(input) {
    if (input.checked) {
        const ok = await WebPushManager.subscribe();
        if (!ok) input.checked = false;
    } else {
        await WebPushManager.unsubscribe();
    }
}
function togglePw(id, btn, e) {
    if (e) {
        if (e._pwToggled) return;
        e._pwToggled = true;
        if (e.preventDefault) e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
    }
    if (window.togglePassword) {
        window.togglePassword(id, btn, e);
        return;
    }
    const i = typeof id === 'string' ? document.getElementById(id) : id;
    if (!i) return;

    let start = null;
    let end = null;
    try {
        start = i.selectionStart;
        end = i.selectionEnd;
    } catch (err) {}

    const isPw = i.type === 'password';
    i.type = isPw ? 'text' : 'password';
    const ic = btn ? btn.querySelector('i') : null;
    if (ic) ic.className = isPw ? 'bi bi-eye' : 'bi bi-eye-slash';
    if (btn) {
        btn.style.color = isPw ? '#800000' : '';
        const isConf = i.name === 'password_confirmation' || i.id === 'spw2';
        const label = isPw 
            ? (isConf ? 'Hide password confirmation' : 'Hide password')
            : (isConf ? 'Show password confirmation' : 'Show password');
        btn.setAttribute('aria-label', label);
        btn.setAttribute('title', label);
        btn.setAttribute('aria-pressed', isPw ? 'true' : 'false');
    }

    try {
        i.focus();
        if (start !== null && end !== null) {
            i.setSelectionRange(start, end);
        }
    } catch (err) {}
}
// Compact sidebar toggle
const ct = document.getElementById('compactToggle');
if (ct) {
    ct.checked = localStorage.getItem('sidebarMini') === 'true';
    ct.addEventListener('change', function() {
        localStorage.setItem('sidebarMini', String(this.checked));
        if (typeof window.applyMini === 'function' && !window.matchMedia('(max-width: 767.98px)').matches) {
            window.applyMini(this.checked, true);
        } else {
            location.reload();
        }
    });
    const burgerBtn = document.getElementById('burgerBtn');
    const sidebarEl = document.getElementById('sidebar');
    if (burgerBtn && sidebarEl) {
        burgerBtn.addEventListener('click', function() {
            setTimeout(() => {
                ct.checked = sidebarEl.classList.contains('collapsed');
            }, 0);
        });
    }
}
// Auto-open security tab on validation errors
if ((window.__SETTINGS_CONFIG__ || {}).hasErrors && typeof switchTab === 'function') switchTab('security');

// ── In-app browser detection ──
function isInAppBrowser() {
    var ua = navigator.userAgent || '';
    // Detect Facebook, Messenger, Instagram, LINE, Twitter, Snapchat, etc.
    return /FBAN|FBAV|FB_IAB|FBIOS|Instagram|Line\/|Twitter|Snapchat|MicroMessenger|KAKAOTALK/i.test(ua);
}



// Small, inline feedback keeps account changes clear without interrupting the form.
function setAccountEditStatus(id, message, tone = 'info') {
    const region = document.getElementById(id);
    if (!region) return false;
    region.textContent = message || '';
    region.dataset.tone = tone;
    region.hidden = !message;
    return true;
}

function announceAccountEditStatus(id, message, tone = 'info') {
    if (!setAccountEditStatus(id, message, tone) && message) window.alert(message);
}

function setAccountEditProgress(id, currentStep) {
    const progress = document.getElementById(id);
    if (!progress) return;
    progress.querySelectorAll('[data-account-step]').forEach((item) => {
        const step = Number(item.dataset.accountStep);
        if (step === currentStep) item.setAttribute('aria-current', 'step');
        else item.removeAttribute('aria-current');
        item.classList.toggle('is-complete', step < currentStep);
    });
}

function setAccountButtonBusy(button, busy, busyLabel) {
    if (!button) return;
    if (!button.dataset.idleHtml) button.dataset.idleHtml = button.innerHTML;
    button.disabled = busy;
    button.setAttribute('aria-busy', busy ? 'true' : 'false');
    button.innerHTML = busy
        ? '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + busyLabel
        : button.dataset.idleHtml;
}

function setupAccountOtpInputs(inputs) {
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
            const next = Array.from(inputs).slice(index + 1).find((field) => !field.value)
                || Array.from(inputs).find((field) => !field.value);
            (next || inputs[inputs.length - 1]).focus();
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !input.value && index > 0) inputs[index - 1].focus();
            if (event.key === 'ArrowLeft' && index > 0) inputs[index - 1].focus();
            if (event.key === 'ArrowRight' && index < inputs.length - 1) inputs[index + 1].focus();
        });
        input.addEventListener('paste', (event) => {
            const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, inputs.length);
            if (!pasted) return;
            pasted.split('').forEach((digit, offset) => { if (inputs[offset]) inputs[offset].value = digit; });
            event.preventDefault();
            (inputs[Math.min(pasted.length, inputs.length) - 1] || inputs[0]).focus();
        });
    });
}

async function parseAccountOtpResponse(response) {
    let data = {};
    try { data = await response.json(); } catch (error) {}
    if (!response.ok || data.success === false) {
        throw new Error(data.message || 'We could not send the code. Please try again in a moment.');
    }
    return data;
}

const sDigits = document.querySelectorAll('.otp-digit-s');
const eDigits = document.querySelectorAll('.email-otp-digit');
setupAccountOtpInputs(sDigits);
setupAccountOtpInputs(eDigits);

function collectOtp(btn) {
    const otp = Array.from(sDigits).map((digit) => digit.value).join('');
    const firstMissing = Array.from(sDigits).find((digit) => !digit.value);
    if (!/^\d{6}$/.test(otp)) {
        announceAccountEditStatus('passwordFormStatus', 'Enter all 6 digits from the email code.', 'error');
        (firstMissing || sDigits[0])?.focus();
        return;
    }
    const password = document.getElementById('spw1');
    const confirmation = document.getElementById('spw2');
    const value = password?.value || '';
    if (value.length < 12 || !/[a-z]/.test(value) || !/[A-Z]/.test(value) || !/\d/.test(value) || !/[^A-Za-z0-9]/.test(value)) {
        announceAccountEditStatus('passwordFormStatus', 'Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol.', 'error');
        password?.focus();
        return;
    }
    if (value !== (confirmation?.value || '')) {
        announceAccountEditStatus('passwordFormStatus', 'The passwords do not match yet. Check the confirmation field.', 'error');
        confirmation?.focus();
        return;
    }
    const form = btn?.closest('form');
    if (!form || !form.reportValidity()) return;
    document.getElementById('settingsOtpHidden').value = otp;
    setAccountEditStatus('passwordFormStatus', '', 'info');
    if (typeof window.studentSettingsRememberReturn === 'function') window.studentSettingsRememberReturn();
    setAccountButtonBusy(btn, true, 'Updating password…');
    if (form.requestSubmit) form.requestSubmit(); else form.submit();
}

async function requestOtp() {
    const btn = document.getElementById('sendOtpBtn');
    if (!btn || btn.disabled) return;
    setAccountEditStatus('passwordFormStatus', '', 'info');
    setAccountButtonBusy(btn, true, 'Sending code…');
    try {
        const response = await fetch(((window.__SETTINGS_CONFIG__ || {}).routes?.otpChangeSend || ''), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': ((window.__SETTINGS_CONFIG__ || {}).csrfToken || ''), 'Content-Type': 'application/json', 'Accept': 'application/json' }
        });
        const data = await parseAccountOtpResponse(response);
        document.getElementById('otpStep1').style.display = 'none';
        document.getElementById('otpStep2').style.display = 'block';
        setAccountEditProgress('passwordEditProgress', 2);
        setAccountButtonBusy(btn, false);
        announceAccountEditStatus('passwordFormStatus', 'Code sent. Enter it below to continue.', 'success');
        if (data.dev_otp) {
            const value = String(data.dev_otp).trim();
            sDigits.forEach((digit, index) => { digit.value = value[index] || ''; });
            const hiddenInput = document.getElementById('settingsOtpHidden');
            if (hiddenInput) hiddenInput.value = value;
        }
        sDigits[0]?.focus();
    } catch (error) {
        setAccountButtonBusy(btn, false);
        announceAccountEditStatus('passwordFormStatus', error.message || 'We could not send the code. Please try again.', 'error');
    }
}

function cancelOtp() {
    document.getElementById('otpStep1').style.display = 'block';
    document.getElementById('otpStep2').style.display = 'none';
    sDigits.forEach((digit) => { digit.value = ''; });
    document.getElementById('settingsOtpHidden').value = '';
    document.getElementById('spw1').value = '';
    document.getElementById('spw2').value = '';
    setAccountEditProgress('passwordEditProgress', 1);
    setAccountEditStatus('passwordFormStatus', '', 'info');
    document.getElementById('sendOtpBtn')?.focus();
}

function collectEmailOtp(btn) {
    const otp = Array.from(eDigits).map((digit) => digit.value).join('');
    const firstMissing = Array.from(eDigits).find((digit) => !digit.value);
    if (!/^\d{6}$/.test(otp)) {
        announceAccountEditStatus('emailFormStatus', 'Enter all 6 digits from the email code.', 'error');
        (firstMissing || eDigits[0])?.focus();
        return;
    }
    const form = btn?.closest('form');
    if (!form || !form.reportValidity()) return;
    document.getElementById('emailOtpHidden').value = otp;
    setAccountEditStatus('emailFormStatus', '', 'info');
    if (typeof window.studentSettingsRememberReturn === 'function') window.studentSettingsRememberReturn();
    setAccountButtonBusy(btn, true, 'Updating email…');
    if (form.requestSubmit) form.requestSubmit(); else form.submit();
}

async function requestEmailOtp() {
    const newEmailInput = document.getElementById('inputNewEmail');
    const newEmail = newEmailInput ? newEmailInput.value.trim() : '';
    if (!newEmailInput || !newEmailInput.reportValidity()) return;
    const btn = document.getElementById('sendEmailOtpBtn');
    if (!btn || btn.disabled) return;
    setAccountEditStatus('emailFormStatus', '', 'info');
    setAccountButtonBusy(btn, true, 'Sending code…');
    try {
        const response = await fetch(((window.__SETTINGS_CONFIG__ || {}).routes?.otpEmailSend || ''), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': ((window.__SETTINGS_CONFIG__ || {}).csrfToken || ''), 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ new_email: newEmail })
        });
        const data = await parseAccountOtpResponse(response);
        document.getElementById('emailStep1').style.display = 'none';
        document.getElementById('emailStep2').style.display = 'block';
        setAccountEditProgress('emailEditProgress', 2);
        setAccountButtonBusy(btn, false);
        announceAccountEditStatus('emailFormStatus', 'Code sent. Enter it below to verify the new address.', 'success');
        const destination = document.getElementById('emailSentDestination');
        if (destination) destination.textContent = data.target_email || newEmail;
        const confirmInput = document.getElementById('confirmNewEmailInput');
        if (confirmInput) confirmInput.value = newEmail;
        if (data.dev_otp) {
            const value = String(data.dev_otp).trim();
            eDigits.forEach((digit, index) => { digit.value = value[index] || ''; });
            const hiddenInput = document.getElementById('emailOtpHidden');
            if (hiddenInput) hiddenInput.value = value;
        }
        eDigits[0]?.focus();
    } catch (error) {
        setAccountButtonBusy(btn, false);
        announceAccountEditStatus('emailFormStatus', error.message || 'We could not send the code. Please check the address and try again.', 'error');
    }
}

function cancelEmailOtp() {
    document.getElementById('emailStep1').style.display = 'block';
    document.getElementById('emailStep2').style.display = 'none';
    eDigits.forEach((digit) => { digit.value = ''; });
    document.getElementById('emailOtpHidden').value = '';
    setAccountEditProgress('emailEditProgress', 1);
    setAccountEditStatus('emailFormStatus', '', 'info');
    document.getElementById('inputNewEmail')?.focus();
}

function generateRecoveryCodes() {
    if(!confirm("Are you sure? Generating new recovery codes will invalidate all your old codes.")) return;
    
    const btn = document.getElementById('generateCodesBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
    
    fetch(((window.__SETTINGS_CONFIG__ || {}).routes?.recoveryGenerate || ''), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': ((window.__SETTINGS_CONFIG__ || {}).csrfToken || ''),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if(data.success) {
            const container = document.getElementById('codesContainer');
            container.innerHTML = '';
            data.codes.forEach(code => {
                const codeEl = document.createElement('div');
                codeEl.className = 'recovery-code-chip';
                codeEl.style.cssText = 'background:rgba(255,235,190,0.06);border:1px solid rgba(207,164,111,0.2);padding:8px 10px;border-radius:8px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:0.95rem;color:#f3e7cd;letter-spacing:1px;font-weight:700;text-align:center;user-select:all;box-shadow:inset 0 1px 2px rgba(0,0,0,0.3);';
                codeEl.textContent = code;
                container.appendChild(codeEl);
            });
            document.getElementById('recoveryCodesList').style.display = 'block';
            if (typeof showToast === 'function') {
                showToast('New recovery codes generated! Please save them now.', 'success');
            } else {
                alert('New recovery codes generated successfully. Please save them now.');
            }
        } else {
            alert(data.message || 'Failed to generate recovery codes.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Network error. Please try again.');
    });
}

function copyAllRecoveryCodes(btn) {
    const chips = document.querySelectorAll('#codesContainer .recovery-code-chip');
    if (!chips.length) return;
    const codes = Array.from(chips).map(el => el.textContent.trim()).join('\n');
    navigator.clipboard.writeText(codes).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied!';
        if (typeof showToast === 'function') showToast('Recovery codes copied to clipboard!', 'success');
        if (window.triggerHaptic) window.triggerHaptic('success');
        setTimeout(() => { btn.innerHTML = orig; }, 2000);
    }).catch(() => {
        alert('Failed to copy to clipboard.');
    });
}

function downloadRecoveryCodes() {
    const chips = document.querySelectorAll('#codesContainer .recovery-code-chip');
    if (!chips.length) return;
    const codes = Array.from(chips).map(el => el.textContent.trim()).join('\n');
    const content = "SMART ATTENDANCE - EMERGENCY RECOVERY CODES\nGenerated: " + new Date().toLocaleString() + "\nAccount: " + String((window.__SETTINGS_CONFIG__ || {}).userEmail || '') + "\n\n" + codes + "\n\nStore these keys in a safe, offline location.";
    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'attendance-recovery-codes-' + Date.now() + '.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    if (typeof showToast === 'function') showToast('Recovery codes downloaded!', 'info');
}



const SETTINGS_SEARCH_INDEX = [
    { title: 'Profile Photo & Avatar', desc: 'Change avatar, upload photo, preview appearance', cat: 'Account', tab: 'profile', icon: 'bi-person-bounding-box', elId: 'tab-profile' },
    { title: 'Personal Information', desc: 'Name, email address, and role identity', cat: 'Account', tab: 'profile', icon: 'bi-person-vcard', elId: 'name' },
    { title: 'Student ID Number', desc: '7-character institutional student ID', cat: 'Account', tab: 'profile', icon: 'bi-card-text', elId: 'studentId' },
    { title: 'Change Password', desc: 'Update account password and security credentials', cat: 'Security & Access', tab: 'security', icon: 'bi-key-fill', elId: 'current_password' },
    { title: 'Active Sessions & Audit Trail', desc: 'Review login history and active device sessions', cat: 'Security & Access', tab: 'security', icon: 'bi-shield-check', elId: 'tab-security' },
    { title: 'Device Passkey', desc: 'Register a passkey using the secure prompt on your device', cat: 'Security & Access', tab: 'fingerprint', icon: 'bi-key', elId: 'startBioBtn' },
    { title: 'Registered Devices', desc: 'View or remove registered device passkeys', cat: 'Security & Access', tab: 'fingerprint', icon: 'bi-shield-lock', elId: 'deviceList' },
    { title: 'Device Binding & Trust', desc: 'Hardware device binding and attendance authorization', cat: 'Security & Access', tab: 'device', icon: 'bi-phone', elId: 'tab-device' },
    { title: 'Bind Current Device', desc: 'Cryptographically bind this phone or computer to your attendance', cat: 'Security & Access', tab: 'device', icon: 'bi-phone-fill', elId: 'tabDeviceBindBtn' },
    { title: 'Emergency Device Lock', desc: 'Lock attendance check-ins exclusively to your authorized device', cat: 'Security & Access', tab: 'device', icon: 'bi-shield-lock-fill', elId: 'tab-device' },
    { title: 'System Display Language', desc: 'Select English (US), Filipino, or Bikolano', cat: 'System & Academics', tab: 'preferences', icon: 'bi-translate', elId: 'tab-preferences' },
    { title: 'Notification Alerts', desc: 'In-app notifications and email alert preferences', cat: 'System & Academics', tab: 'preferences', icon: 'bi-bell-fill', elId: 'tab-preferences' },
    { title: 'App updates', desc: 'Check latest system updates, security patches and offline assets', cat: 'System & Academics', tab: 'preferences', icon: 'bi-cloud-arrow-down-fill', elId: 'checkUpdateBtn' },
    { title: 'Family & Guardian Link', desc: 'Connect parent accounts with QR code or link code', cat: 'Account', tab: 'family', icon: 'bi-people-fill', elId: 'tab-family' },
    { title: 'Linked Guardians List', desc: 'View authorized guardians with view-only attendance access', cat: 'Account', tab: 'family', icon: 'bi-person-lines-fill', elId: 'tab-family' }
];

function initSettingsSearch() {
    const input = document.getElementById('settingsSearchInput');
    const dropdown = document.getElementById('settingsSearchResults');
    const clearBtn = document.getElementById('settingsSearchClear');
    const backdrop = document.getElementById('settingsSearchBackdrop');
    const header = document.querySelector('.settings-command-header') || document.querySelector('.settings-top-navbar');
    const kbd = document.querySelector('.settings-search-kbd');
    if (!input || !dropdown) return;

    let selectedIndex = -1;
    let currentResults = [];

    function updateClearButton() {
        if (!clearBtn) return;
        if (input.value.trim().length > 0) {
            clearBtn.style.display = 'flex';
            if (kbd) kbd.style.display = 'none';
        } else {
            clearBtn.style.display = 'none';
            if (kbd && window.innerWidth > 768) kbd.style.display = '';
        }
    }

    function showSearchUI() {
        if (header) header.classList.add('search-active');
        if (backdrop) backdrop.style.display = 'block';
    }

    function hideSearchUI() {
        dropdown.style.display = 'none';
        if (header) header.classList.remove('search-active');
        if (backdrop) backdrop.style.display = 'none';
        selectedIndex = -1;
    }

    function renderResults(query) {
        updateClearButton();
        const q = (query || '').trim().toLowerCase();
        if (!q) {
            hideSearchUI();
            dropdown.innerHTML = '';
            currentResults = [];
            selectedIndex = -1;
            return;
        }

        currentResults = SETTINGS_SEARCH_INDEX.filter(item => {
            return item.title.toLowerCase().includes(q) ||
                   item.desc.toLowerCase().includes(q) ||
                   item.cat.toLowerCase().includes(q) ||
                   item.tab.toLowerCase().includes(q);
        });

        if (currentResults.length === 0) {
            dropdown.innerHTML = `<div class="search-empty-state"><i class="bi bi-search me-2"></i>No settings found matching "${query}".</div>`;
            dropdown.style.display = 'block';
            showSearchUI();
            selectedIndex = -1;
            return;
        }

        selectedIndex = 0;
        dropdown.innerHTML = currentResults.map((item, idx) => `
            <div class="search-item-row ${idx === 0 ? 'selected' : ''}" data-idx="${idx}">
                <div class="search-item-icon"><i class="bi ${item.icon}"></i></div>
                <div class="search-item-body">
                    <span class="search-item-title">${item.title}</span>
                    <span class="search-item-sub">${item.desc}</span>
                </div>
                <span class="search-item-cat">${item.cat}</span>
            </div>
        `).join('');

        dropdown.querySelectorAll('.search-item-row').forEach(row => {
            row.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-idx'));
                executeSearchJump(currentResults[idx]);
            });
        });

        dropdown.style.display = 'block';
        showSearchUI();
    }

    function executeSearchJump(item) {
        if (!item) return;
        hideSearchUI();
        input.value = '';
        updateClearButton();
        input.blur();

        if (window.switchTab) {
            window.switchTab(item.tab);
        }

        if (item.elId) {
            setTimeout(() => {
                const el = document.getElementById(item.elId);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('search-highlight-pulse');
                    setTimeout(() => el.classList.remove('search-highlight-pulse'), 2500);
                }
            }, 150);
        }
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            input.value = '';
            updateClearButton();
            hideSearchUI();
            input.focus();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function() {
            hideSearchUI();
            input.blur();
        });
    }

    input.addEventListener('input', function() {
        renderResults(this.value);
    });

    input.addEventListener('focus', function() {
        updateClearButton();
        if (this.value.trim()) {
            renderResults(this.value);
        }
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (currentResults.length > 0) {
                selectedIndex = (selectedIndex + 1) % currentResults.length;
                updateSelection();
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (currentResults.length > 0) {
                selectedIndex = (selectedIndex - 1 + currentResults.length) % currentResults.length;
                updateSelection();
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0 && selectedIndex < currentResults.length) {
                executeSearchJump(currentResults[selectedIndex]);
            }
        } else if (e.key === 'Escape') {
            hideSearchUI();
            input.blur();
        }
    });

    function updateSelection() {
        const rows = dropdown.querySelectorAll('.search-item-row');
        rows.forEach((r, idx) => {
            r.classList.toggle('selected', idx === selectedIndex);
            if (idx === selectedIndex) {
                r.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }

    // Global keyboard shortcut '/' or 'Ctrl+K'
    document.addEventListener('keydown', function(e) {
        const activeTag = document.activeElement ? document.activeElement.tagName : '';
        if (activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT') {
            return;
        }
        if (e.key === '/' || ((e.ctrlKey || e.metaKey) && e.key === 'k')) {
            e.preventDefault();
            input.focus();
            input.select();
        }
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target) && (!clearBtn || !clearBtn.contains(e.target))) {
            hideSearchUI();
        }
    });
}

function initMobileSearchToggle() {
    if (window.innerWidth > 768) return;
    var box = document.querySelector('.sp .settings-search-box');
    var icon = document.querySelector('.sp .settings-search-icon');
    var input = document.getElementById('settingsSearchInput');
    if (!box || !icon || !input) return;

    function expand() {
        box.classList.add('search-expanded');
        setTimeout(function() { input.focus(); }, 60);
    }
    function collapse() {
        if (!input.value.trim()) {
            box.classList.remove('search-expanded');
        }
    }

    icon.addEventListener('click', function(e) {
        if (!box.classList.contains('search-expanded')) {
            e.preventDefault();
            e.stopPropagation();
            expand();
        }
    });
    input.addEventListener('blur', function() {
        setTimeout(collapse, 200);
    });
    // Keyboard shortcut still works
    document.addEventListener('keydown', function(e) {
        if (window.innerWidth > 768) return;
        var activeTag = document.activeElement ? document.activeElement.tagName : '';
        if (activeTag === 'INPUT' || activeTag === 'TEXTAREA') return;
        if (e.key === '/' || ((e.ctrlKey || e.metaKey) && e.key === 'k')) {
            e.preventDefault();
            expand();
        }
    });
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            box.classList.remove('search-expanded');
        }
    }, { passive: true });
}

function initSettingsPage() {
    const studentSettings = document.querySelector('[data-student-settings]');
    if (!studentSettings && typeof initializeDevicePasskeyRegistration === 'function') {
        initializeDevicePasskeyRegistration();
    }
    if (typeof updateStabsScrollArrows === 'function') updateStabsScrollArrows();
    if (typeof initSettingsSearch === 'function') initSettingsSearch();
    initMobileSearchToggle();

    // Attach direct click event listeners to all mobile stab buttons
    document.querySelectorAll('.stab').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const tab = this.getAttribute('data-tab') || this.dataset.tab;
            if (tab && window.switchTab) {
                window.switchTab(tab, this);
            }
        });
    });

    // Attach direct click event listeners to unified Settings sidebar items for non-student roles
    if (!studentSettings) {
        document.querySelectorAll('[data-settings-page]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-settings-page') || this.dataset.settingsPage;
                if (page && window.switchTab) {
                    window.switchTab(page, this);
                }
                if (typeof window.closeSidebar === 'function' && window.matchMedia('(max-width: 767.98px)').matches) {
                    window.closeSidebar();
                }
            });
        });
    }

    const nav = document.getElementById('stabsNav');
    if (nav) {
        nav.addEventListener('scroll', updateStabsScrollArrows, { passive: true });
        // Recheck after responsive layout, font loading, or tab sizing changes.
        if (!nav.dataset.scrollCueObserver && 'ResizeObserver' in window) {
            nav.dataset.scrollCueObserver = 'true';
            nav.settingsScrollCueObserver = new ResizeObserver(() => updateStabsScrollArrows());
            nav.settingsScrollCueObserver.observe(nav);
            Array.from(nav.children).forEach(tab => nav.settingsScrollCueObserver.observe(tab));
        }
        requestAnimationFrame(updateStabsScrollArrows);
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(updateStabsScrollArrows).catch(() => {});
        }
    }
    window.addEventListener('resize', updateStabsScrollArrows, { passive: true });

    // Check if hash or localStorage requested a specific tab (e.g., #tab-fingerprint or #fingerprint)
    const rawHash = window.location.hash.replace('#tab-', '').replace('#', '');
    const storedTab = localStorage.getItem('active_settings_tab');
    const targetTab = rawHash || storedTab || 'profile';
    if (targetTab && window.switchTab) {
        localStorage.removeItem('active_settings_tab');
        if (targetTab.startsWith('security-')) {
            const sub = targetTab.replace('security-', '');
            window.switchSecuritySub(sub);
        } else {
            window.switchTab(targetTab);
        }
    }

    // Initialize device binding status check
    if (!studentSettings && typeof window.checkDeviceBindingStatus === 'function') {
        setTimeout(window.checkDeviceBindingStatus, 300);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSettingsPage);
} else {
    initSettingsPage();
}

// ── Student Family & Guardian Binding Handlers ──
let studentLinkCountdownTimer = null;

async function generateParentLinkCode() {
    const btn = document.getElementById('generateParentCodeBtn');
    const displayCard = document.getElementById('parentCodeDisplayCard');
    const codeEl = document.getElementById('parentCodeValue');
    const qrContainer = document.getElementById('parentCodeQrBox');
    const timerEl = document.getElementById('parentCodeTimer');

    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating Code...';

    try {
        const resp = await fetch(((window.__SETTINGS_CONFIG__ || {}).routes?.parentLinkGenerateCode || ''), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': ((window.__SETTINGS_CONFIG__ || {}).csrfToken || ''),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        });

        const data = await resp.json();
        if (data.success) {
            if (codeEl) codeEl.textContent = data.code;
            if (displayCard) displayCard.style.display = 'block';

            // Generate QR Code if library is loaded
            if (qrContainer && typeof QRCode !== 'undefined') {
                qrContainer.innerHTML = '';
                const linkUrl = ((window.__SETTINGS_CONFIG__ || {}).routes?.parentLinkForm || '') + '?code=' + encodeURIComponent(data.code)
                    + '&student_number=' + encodeURIComponent(data.student_number || '');
                new QRCode(qrContainer, {
                    text: linkUrl,
                    width: 170,
                    height: 170,
                    colorDark: '#140d07',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            }

            // Start countdown timer
            if (studentLinkCountdownTimer) clearInterval(studentLinkCountdownTimer);
            let remaining = data.expires_in_seconds || 900;
            const updateTimerDisplay = () => {
                if (remaining <= 0) {
                    clearInterval(studentLinkCountdownTimer);
                    if (timerEl) timerEl.textContent = 'Expired. Click Generate to refresh.';
                    if (codeEl) codeEl.style.opacity = '0.5';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Generate New Code';
                    return;
                }
                const mins = Math.floor(remaining / 60);
                const secs = remaining % 60;
                if (timerEl) {
                    timerEl.textContent = `Valid for: ${mins}:${secs < 10 ? '0' : ''}${secs}`;
                }
                remaining--;
            };
            updateTimerDisplay();
            studentLinkCountdownTimer = setInterval(updateTimerDisplay, 1000);

            if (typeof showToast === 'function') {
                showToast('Link Code generated! Share it with your parent.', 'success');
            }
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Regenerate Code';
        } else {
            alert(data.message || 'Failed to generate link code.');
            btn.innerHTML = '<i class="bi bi-key-fill me-1"></i> Generate Parent Link Code';
        }
    } catch (err) {
        alert('Network error while generating code.');
        btn.innerHTML = '<i class="bi bi-key-fill me-1"></i> Generate Parent Link Code';
    } finally {
        btn.disabled = false;
    }
}

function copyParentLinkCode(btn) {
    const codeEl = document.getElementById('parentCodeValue');
    if (!codeEl) return;
    const code = codeEl.textContent.trim();
    navigator.clipboard.writeText(code).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied!';
        if (typeof showToast === 'function') showToast('Code copied to clipboard!', 'success');
        setTimeout(() => { btn.innerHTML = orig; }, 2000);
    });
}

window.generateParentLinkCode = generateParentLinkCode;
window.copyParentLinkCode = copyParentLinkCode;
