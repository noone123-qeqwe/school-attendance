/**
 * Smart Classroom Attendance — Student Frontend Runtime
 * Handles Dark Oxblood / Light Porcelain theme, collapsible sidebar,
 * Linear Command Palette (Ctrl+K), global shortcuts (Q/P), notifications,
 * account modals, live countdowns, toasts, and celebratory confetti.
 */
(function () {
    'use strict';

    const THEME_KEY = 'student_portal_theme';
    const SIDEBAR_KEY = 'sidebarMini';

    /* ── 1. Theme System (Dark Oxblood / Light Porcelain) ─────────────────── */
    function getTheme() {
        try {
            const saved = localStorage.getItem(THEME_KEY);
            return saved === 'light' ? 'light' : 'dark';
        } catch (e) {
            return 'dark';
        }
    }

    function applyTheme(theme, announce) {
        const resolved = theme === 'light' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-student-theme', resolved);
        document.documentElement.classList.toggle('dark', resolved === 'dark');
        try {
            localStorage.setItem(THEME_KEY, resolved);
        } catch (e) {}

        const metaTheme = document.querySelector('meta[name="theme-color"]');
        if (metaTheme) {
            metaTheme.setAttribute('content', resolved === 'light' ? '#F7F3F4' : '#0C0709');
        }

        document.querySelectorAll('[data-theme-choice]').forEach((btn) => {
            const isMatch = btn.getAttribute('data-theme-choice') === resolved;
            btn.classList.toggle('active', isMatch);
            btn.setAttribute('aria-pressed', isMatch ? 'true' : 'false');
        });

        document.querySelectorAll('[data-theme-icon-sun]').forEach((el) => {
            el.style.display = resolved === 'dark' ? 'inline-block' : 'none';
        });
        document.querySelectorAll('[data-theme-icon-moon]').forEach((el) => {
            el.style.display = resolved === 'light' ? 'inline-block' : 'none';
        });

        if (announce && typeof window.showToast === 'function') {
            window.showToast(
                resolved === 'light' ? 'Switched to Light Porcelain' : 'Switched to Dark Oxblood',
                'info',
                2400
            );
        }
    }

    window.setStudentTheme = function (theme) {
        applyTheme(theme, true);
    };

    window.toggleStudentTheme = function () {
        applyTheme(getTheme() === 'dark' ? 'light' : 'dark', true);
    };

    /* ── 2. Linear Toast Engine ───────────────────────────────────────────── */
    window.showToast = function (message, type = 'success', duration = 4200) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const tone = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'success';
        const iconMap = {
            success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sf-emerald)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
            error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sf-danger)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>',
            warning: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sf-amber)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
            info: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sf-rose)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
        };

        const toast = document.createElement('div');
        toast.className = 'sf-toast is-' + tone;
        toast.style.setProperty('--sf-toast-dur', duration + 'ms');

        const iconSpan = document.createElement('span');
        iconSpan.style.display = 'inline-flex';
        iconSpan.innerHTML = iconMap[tone];

        const msgSpan = document.createElement('span');
        msgSpan.style.flex = '1';
        msgSpan.textContent = String(message ?? '');

        const bar = document.createElement('span');
        bar.className = 'sf-toast-bar';

        toast.append(iconSpan, msgSpan, bar);
        container.appendChild(toast);

        const remove = () => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 180ms ease';
            setTimeout(() => toast.remove(), 200);
        };
        toast.addEventListener('click', remove);
        setTimeout(remove, duration);
    };
    window.showPremiumToast = window.showToast;

    /* ── 3. Lightweight Zero-Dependency Confetti Burst ────────────────────── */
    window.sfConfettiBurst = function () {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const canvas = document.createElement('canvas');
        canvas.style.cssText = 'position:fixed;inset:0;width:100vw;height:100vh;pointer-events:none;z-index:2500;';
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        document.body.appendChild(canvas);
        const ctx = canvas.getContext('2d');
        if (!ctx) { canvas.remove(); return; }

        const colors = ['#9F1239', '#34D399', '#FDA4AF', '#FFFFFF', '#F59E0B'];
        const particles = Array.from({ length: 64 }, () => ({
            x: canvas.width * 0.5,
            y: canvas.height * 0.42,
            vx: (Math.random() - 0.5) * 14,
            vy: (Math.random() - 0.85) * 13,
            w: 5 + Math.random() * 5,
            h: 3 + Math.random() * 4,
            rot: Math.random() * Math.PI * 2,
            vr: (Math.random() - 0.5) * 0.25,
            color: colors[Math.floor(Math.random() * colors.length)],
            alpha: 1,
        }));

        let frame = 0;
        const step = () => {
            frame++;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            let alive = false;
            particles.forEach((p) => {
                p.x += p.vx;
                p.y += p.vy;
                p.vy += 0.28;
                p.vx *= 0.985;
                p.rot += p.vr;
                if (frame > 42) p.alpha -= 0.025;
                if (p.alpha > 0) {
                    alive = true;
                    ctx.save();
                    ctx.globalAlpha = Math.max(0, p.alpha);
                    ctx.translate(p.x, p.y);
                    ctx.rotate(p.rot);
                    ctx.fillStyle = p.color;
                    ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                    ctx.restore();
                }
            });
            if (alive && frame < 120) requestAnimationFrame(step);
            else canvas.remove();
        };
        requestAnimationFrame(step);
    };

    /* ── 4. Collapsible Desktop Sidebar ───────────────────────────────────── */
    window.applyMini = function (collapsed) {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;
        sidebar.classList.toggle('collapsed', Boolean(collapsed));
        document.body.classList.toggle('sf-sidebar-collapsed', Boolean(collapsed));
        const toggleBtn = document.getElementById('burgerBtn');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }
        const compactToggle = document.getElementById('compactToggle');
        if (compactToggle) compactToggle.checked = Boolean(collapsed);
    };

    window.toggleSidebar = function () {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;
        const next = !sidebar.classList.contains('collapsed');
        window.applyMini(next);
        try {
            localStorage.setItem(SIDEBAR_KEY, String(next));
        } catch (e) {}
    };

    /* ── 5. Modals, Popovers & Account Menu ───────────────────────────────── */
    function closeAllPopovers() {
        document.getElementById('sfAccountPopover')?.classList.remove('open');
        document.getElementById('sfNotifDropdown')?.classList.remove('open');
    }

    window.openSfModal = function (id) {
        closeAllPopovers();
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('open');
        el.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        const focusable = el.querySelector('input:not([type="hidden"]), select, textarea, button');
        if (focusable) setTimeout(() => focusable.focus({ preventScroll: true }), 40);
    };

    window.closeSfModal = function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('open');
        el.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.sf-modal-backdrop.open, .sf-drawer.open')) {
            document.body.style.overflow = '';
        }
    };

    /* ── 6. Notifications Bell Dropdown Polling ───────────────────────────── */
    let notifFetching = false;
    window.refreshNotificationBell = async function () {
        if (notifFetching) return;
        const pollUrl = document.body.dataset.notifPollUrl;
        if (!pollUrl) return;
        notifFetching = true;
        try {
            const res = await fetch(pollUrl, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            const count = Number(data.unread_count || 0);
            const dot = document.getElementById('topNavNotifDot');
            if (dot) dot.hidden = count <= 0;

            const markBtn = document.getElementById('sfNotifMarkAllBtn');
            if (markBtn) markBtn.hidden = count <= 0;

            const list = document.getElementById('topNavNotifList');
            if (!list) return;
            const items = Array.isArray(data.notifications) ? data.notifications : [];
            if (!items.length) {
                list.innerHTML = '<div class="sf-notif-dd-empty">No notifications yet.</div>';
                return;
            }
            list.replaceChildren();
            items.forEach((n) => {
                const row = document.createElement('div');
                row.className = 'sf-notif-dd-item' + (n.is_read ? '' : ' is-unread');
                const dotEl = document.createElement('span');
                dotEl.className = 'sf-notif-dd-dot';
                if (n.is_read) dotEl.style.opacity = '0.25';

                const body = document.createElement('div');
                body.className = 'sf-notif-dd-body';
                const msg = document.createElement('div');
                msg.className = 'sf-notif-dd-msg';
                msg.textContent = String(n.message || '');
                const time = document.createElement('div');
                time.className = 'sf-notif-dd-time';
                time.textContent = String(n.created_at_human || '');
                body.append(msg, time);
                row.append(dotEl, body);
                list.appendChild(row);
            });
        } catch (e) {
            // Silent retry on next open/poll
        } finally {
            notifFetching = false;
        }
    };

    window.markAllRead = async function () {
        const readUrl = document.body.dataset.notifReadUrl;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (!readUrl) return;
        try {
            const res = await fetch(readUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            if (res.ok) {
                window.refreshNotificationBell();
                window.showToast('All notifications marked as read.', 'success', 2600);
            }
        } catch (e) {}
    };

    /* ── 7. Linear Command Palette (Ctrl+K) ───────────────────────────────── */
    function initCommandPalette() {
        const overlay = document.getElementById('cmdPaletteOverlay');
        const input = document.getElementById('cmdPaletteInput');
        const listEl = document.getElementById('cmdPaletteItems');
        if (!overlay || !input || !listEl) return;

        const items = Array.isArray(window.__SF_COMMANDS__) ? window.__SF_COMMANDS__ : [];
        let filtered = items.slice();
        let activeIdx = 0;

        function render() {
            listEl.replaceChildren();
            if (!filtered.length) {
                listEl.innerHTML = '<div class="sf-notif-dd-empty">No matching commands or courses.</div>';
                return;
            }
            filtered.forEach((item, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'sf-cmd-item' + (idx === activeIdx ? ' active' : '');
                btn.dataset.cmdIdx = String(idx);
                btn.innerHTML = `
                    <span class="sf-cmd-item-left">
                        <span class="sf-cmd-item-icon">${item.icon || ''}</span>
                        <span class="sf-cmd-item-copy">
                            <strong></strong>
                            <span></span>
                        </span>
                    </span>
                    <span class="sf-cmd-enter">↵</span>
                `;
                btn.querySelector('strong').textContent = item.label || '';
                btn.querySelector('span.sf-cmd-item-copy span').textContent = item.hint || '';
                btn.addEventListener('mouseenter', () => {
                    activeIdx = idx;
                    syncActive();
                });
                btn.addEventListener('click', () => execute(item));
                listEl.appendChild(btn);
            });
        }

        function syncActive() {
            listEl.querySelectorAll('.sf-cmd-item').forEach((el, idx) => {
                el.classList.toggle('active', idx === activeIdx);
                if (idx === activeIdx) el.scrollIntoView({ block: 'nearest' });
            });
        }

        function execute(item) {
            closePalette();
            if (!item) return;
            if (item.action === 'toggleTheme') {
                window.toggleStudentTheme();
                return;
            }
            if (item.url) {
                window.location.assign(item.url);
            }
        }

        function openPalette() {
            closeAllPopovers();
            overlay.classList.add('open');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            input.value = '';
            filtered = items.slice();
            activeIdx = 0;
            render();
            setTimeout(() => input.focus({ preventScroll: true }), 20);
        }

        function closePalette() {
            overlay.classList.remove('open');
            overlay.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.sf-modal-backdrop.open, .sf-drawer.open')) {
                document.body.style.overflow = '';
            }
        }

        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            filtered = !q
                ? items.slice()
                : items.filter((item) => ((item.label || '') + ' ' + (item.hint || '') + ' ' + (item.keywords || '')).toLowerCase().includes(q));
            activeIdx = 0;
            render();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (filtered.length) {
                    activeIdx = (activeIdx + 1) % filtered.length;
                    syncActive();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (filtered.length) {
                    activeIdx = (activeIdx - 1 + filtered.length) % filtered.length;
                    syncActive();
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (filtered[activeIdx]) execute(filtered[activeIdx]);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                closePalette();
            }
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closePalette();
        });

        window.openCommandPalette = openPalette;
        window.closeCommandPalette = closePalette;
    }

    /* ── 8. Global Boot & Event Wiring ────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(getTheme(), false);

        try {
            if (!window.matchMedia('(max-width: 767.98px)').matches && localStorage.getItem(SIDEBAR_KEY) === 'true') {
                window.applyMini(true);
            }
        } catch (e) {}

        initCommandPalette();

        // Sidebar Account Popover
        const accBtn = document.getElementById('sfSidebarAccountBtn');
        const accPopover = document.getElementById('sfAccountPopover');
        if (accBtn && accPopover) {
            accBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const willOpen = !accPopover.classList.contains('open');
                closeAllPopovers();
                if (willOpen) accPopover.classList.add('open');
            });
        }

        // Notifications Bell Dropdown
        const notifBtn = document.getElementById('topNavNotifBtn');
        const notifDd = document.getElementById('sfNotifDropdown');
        if (notifBtn && notifDd) {
            notifBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const willOpen = !notifDd.classList.contains('open');
                closeAllPopovers();
                if (willOpen) {
                    notifDd.classList.add('open');
                    window.refreshNotificationBell();
                }
            });
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#sfAccountPopover, #sfSidebarAccountBtn, #sfNotifDropdown, #topNavNotifBtn')) {
                closeAllPopovers();
            }
        });

        // Theme toggle buttons
        document.querySelectorAll('[data-action="toggle-theme"]').forEach((btn) => {
            btn.addEventListener('click', () => window.toggleStudentTheme());
        });

        document.querySelectorAll('[data-theme-choice]').forEach((btn) => {
            btn.addEventListener('click', () => window.setStudentTheme(btn.getAttribute('data-theme-choice')));
        });

        // Close modal backdrops on click outside or close buttons
        document.querySelectorAll('.sf-modal-backdrop').forEach((backdrop) => {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) window.closeSfModal(backdrop.id);
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach((btn) => {
            btn.addEventListener('click', () => window.closeSfModal(btn.getAttribute('data-close-modal')));
        });

        // Global keyboard shortcuts (Ctrl+K, Q for QR, P for PIN, Escape)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                const overlay = document.getElementById('cmdPaletteOverlay');
                if (overlay?.classList.contains('open')) window.closeCommandPalette?.();
                else window.openCommandPalette?.();
                return;
            }

            if (e.key === 'Escape') {
                closeAllPopovers();
                window.closeCommandPalette?.();
                document.querySelectorAll('.sf-modal-backdrop.open').forEach((m) => window.closeSfModal(m.id));
                window.closeCourseDrawer?.();
                return;
            }

            const tag = document.activeElement?.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || document.activeElement?.isContentEditable) return;
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            if (document.querySelector('.sf-modal-backdrop.open')) return;

            const k = e.key.toLowerCase();
            if (k === 'q' && document.body.dataset.scanQrUrl) {
                window.location.assign(document.body.dataset.scanQrUrl);
            } else if (k === 'p' && document.body.dataset.scanPinUrl) {
                window.location.assign(document.body.dataset.scanPinUrl);
            }
        });

        // Haptic feedback helper
        window.triggerHaptic = function (type = 'light') {
            if (!('vibrate' in navigator) || !navigator.vibrate) return;
            try {
                if (type === 'light') navigator.vibrate(8);
                else if (type === 'medium') navigator.vibrate(16);
                else if (type === 'success') navigator.vibrate([12, 28, 18]);
                else if (type === 'error') navigator.vibrate([24, 36, 24]);
            } catch (e) {}
        };

        // Instant navigation feedback bar & pressed state for back buttons and portal links
        let navProgressSafetyTimer = null;
        window.sfStartNavProgress = function (triggerEl) {
            const bar = document.getElementById('sfNavProgress');
            if (bar) {
                bar.classList.remove('is-active');
                void bar.offsetWidth;
                bar.classList.add('is-active');
            }
            if (triggerEl && triggerEl.classList) {
                triggerEl.classList.add('is-navigating');
            }
            if (navProgressSafetyTimer) clearTimeout(navProgressSafetyTimer);
            navProgressSafetyTimer = setTimeout(() => window.sfStopNavProgress?.(), 15000);
        };

        window.sfStopNavProgress = function () {
            if (navProgressSafetyTimer) {
                clearTimeout(navProgressSafetyTimer);
                navProgressSafetyTimer = null;
            }
            document.getElementById('sfNavProgress')?.classList.remove('is-active');
            document.querySelectorAll('.is-navigating').forEach((el) => el.classList.remove('is-navigating'));
        };

        document.addEventListener('click', (e) => {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            const link = e.target.closest('a[href]');
            if (!link) return;
            if (link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
            const rawHref = (link.getAttribute('href') || '').trim();
            if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('javascript:') || rawHref.startsWith('mailto:') || rawHref.startsWith('tel:')) return;
            try {
                const dest = new URL(link.href, window.location.href);
                if (dest.origin !== window.location.origin) return;
                if (dest.pathname === window.location.pathname && dest.search === window.location.search && dest.hash) return;
            } catch (err) {
                return;
            }
            window.sfStartNavProgress(link);
        });

        window.addEventListener('pageshow', () => window.sfStopNavProgress());
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') window.sfStopNavProgress();
        });

        // Lazy notification bell refresh — unread dot is already server-rendered by SidebarComposer,
        // and clicking the bell button fetches immediately, so avoid hitting remote DB right on load.
        setTimeout(() => {
            if (document.visibilityState === 'visible') {
                window.refreshNotificationBell();
            }
        }, 8000);
    });
})();
