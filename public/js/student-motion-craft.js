/* ==========================================================================
   Student Portal Motion & Interaction Craft Runtime v5.0
   - Passive scroll elevation observer
   - GPU-composited gliding segmented indicators
   - Dashboard SVG ring & segmented meter reveal
   - Reusable Vaul-grade 1:1 pointer-capture drag-to-dismiss sheets
   - Sonner-grade toast pause-on-hover + swipe-to-dismiss
   - Blur-masked copy morph & directional calendar month transition
   ========================================================================== */
(function () {
    'use strict';

    if (typeof window === 'undefined' || typeof document === 'undefined') return;

    const prefersReducedMotion = () =>
        window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const EASE_OUT = 'cubic-bezier(0.23, 1, 0.32, 1)';
    const EASE_DRAWER = 'cubic-bezier(0.32, 0.72, 0, 1)';

    // ── 1. Passive Top-Header Scroll Elevation ─────────────────────────────
    function initScrollElevation() {
        const header = document.getElementById('topHeader');
        if (!header) return;
        let lastScrolled = null;
        const sync = () => {
            const scrolled = (window.scrollY || document.documentElement.scrollTop || 0) > 8;
            if (scrolled !== lastScrolled) {
                lastScrolled = scrolled;
                header.setAttribute('data-scrolled', scrolled ? 'true' : 'false');
            }
        };
        sync();
        window.addEventListener('scroll', sync, { passive: true });
    }

    // ── 2. GPU Gliding Segmented Indicator ─────────────────────────────────
    function initGlidingGroup(groupEl) {
        if (!groupEl || groupEl.dataset.glideBound === '1') return;
        groupEl.dataset.glideBound = '1';
        groupEl.setAttribute('data-glide-group', '');

        let pill = groupEl.querySelector(':scope > .sm-glide-indicator');
        if (!pill) {
            pill = document.createElement('span');
            pill.className = 'sm-glide-indicator';
            pill.setAttribute('aria-hidden', 'true');
            groupEl.prepend(pill);
        }

        const getActive = () =>
            groupEl.querySelector(
                ':scope > .active, :scope > .is-active, :scope > [aria-pressed="true"], :scope > [aria-selected="true"]'
            );

        const syncPosition = (instant) => {
            const active = getActive();
            if (!active || active.offsetParent === null) {
                groupEl.removeAttribute('data-glide-ready');
                return;
            }
            const groupRect = groupEl.getBoundingClientRect();
            const itemRect = active.getBoundingClientRect();
            if (groupRect.width <= 0 || itemRect.width <= 0) return;

            const x = Math.round((itemRect.left - groupRect.left - (groupEl.clientLeft || 0)) * 10) / 10;
            const w = Math.round(itemRect.width * 10) / 10;
            if (instant) groupEl.setAttribute('data-glide-instant', 'true');
            pill.style.setProperty('--glide-x', `${x}px`);
            pill.style.setProperty('--glide-w', `${w}px`);
            groupEl.setAttribute('data-glide-ready', 'true');
            if (instant) {
                requestAnimationFrame(() => groupEl.removeAttribute('data-glide-instant'));
            }
        };

        // Initial paint without transition so indicator never jumps on load
        requestAnimationFrame(() => syncPosition(true));
        if (document.fonts && typeof document.fonts.ready?.then === 'function') {
            document.fonts.ready.then(() => syncPosition(true));
        }

        // Update smoothly when any child tab is clicked or mutated
        groupEl.addEventListener('click', () => {
            requestAnimationFrame(() => syncPosition(false));
        });

        if (typeof MutationObserver === 'function') {
            const mo = new MutationObserver(() => requestAnimationFrame(() => syncPosition(false)));
            mo.observe(groupEl, {
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'aria-pressed', 'aria-selected'],
            });
        }

        if (typeof ResizeObserver === 'function') {
            const ro = new ResizeObserver(() => syncPosition(true));
            ro.observe(groupEl);
        } else {
            window.addEventListener('resize', () => syncPosition(true), { passive: true });
        }
    }

    function initAllGlidingControls() {
        const selectors = [
            '.sd-workspace-tabs',
            '.sd-segmented',
            '.sd-view-toggle',
            '.student-view-switcher',
            '.notif-status-tabs',
            '.scanner-mode-switcher',
        ];
        document.querySelectorAll(selectors.join(', ')).forEach(initGlidingGroup);
    }

    // ── 3. Dashboard Gauge & Segmented Bar Reveal ──────────────────────────
    function initDashboardMeterReveal() {
        if (prefersReducedMotion()) return;
        const gauge = document.querySelector('.sd-gauge-fill');
        if (gauge) {
            const finalOffset = gauge.getAttribute('stroke-dashoffset');
            const circ = gauge.getAttribute('stroke-dasharray');
            if (finalOffset !== null && circ !== null) {
                gauge.style.transition = 'none';
                gauge.setAttribute('stroke-dashoffset', circ);
                gauge.getBoundingClientRect();
                requestAnimationFrame(() => {
                    gauge.style.transition = '';
                    gauge.setAttribute('stroke-dashoffset', finalOffset);
                });
            }
        }
        const bar = document.querySelector('.sd-breakdown-bar');
        if (bar) {
            bar.classList.add('is-pre-reveal');
            requestAnimationFrame(() => {
                requestAnimationFrame(() => bar.classList.remove('is-pre-reveal'));
            });
        }
    }

    // ── 4. Reusable Vaul-Grade 1:1 Pointer Drag-to-Dismiss Sheet ───────────
    function attachSheetDrag(options) {
        const {
            sheet,
            handle,
            backdrop,
            scrollContainer,
            onDismiss,
            onlyBelowPx = 768,
            dismissThreshold = 96,
            velocityThreshold = 0.11,
        } = options || {};

        if (!sheet || sheet.dataset.smDragBound === '1') return;
        sheet.dataset.smDragBound = '1';

        const dragZone = handle || sheet;
        let activePointerId = null;
        let startY = 0;
        let startTime = 0;
        let lastY = 0;
        let dragging = false;

        const isEnabled = () => window.innerWidth < onlyBelowPx;

        const resetStyles = (animate) => {
            sheet.style.transition = animate ? `transform 300ms ${EASE_DRAWER}` : '';
            sheet.style.transform = '';
            if (backdrop) {
                backdrop.style.transition = animate ? 'opacity 260ms ease' : '';
                backdrop.style.opacity = '';
            }
            if (animate) {
                setTimeout(() => {
                    sheet.style.transition = '';
                    if (backdrop) backdrop.style.transition = '';
                }, 310);
            }
        };

        dragZone.addEventListener('pointerdown', (e) => {
            if (!isEnabled()) return;
            if (activePointerId !== null) return; // Multi-touch protection
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            if (e.target.closest('button, a, input, select, textarea, [data-no-drag]')) return;
            if (scrollContainer && scrollContainer.scrollTop > 0) return;

            activePointerId = e.pointerId;
            startY = e.clientY;
            lastY = startY;
            startTime = performance.now();
            dragging = true;
            sheet.style.transition = 'none';
            if (backdrop) backdrop.style.transition = 'none';
            try { dragZone.setPointerCapture(e.pointerId); } catch (_) {}
        });

        dragZone.addEventListener('pointermove', (e) => {
            if (!dragging || e.pointerId !== activePointerId) return;
            lastY = e.clientY;
            const rawDy = lastY - startY;
            // Rubber-band resistance when dragging upward past natural top edge
            const dy = rawDy < 0 ? rawDy * 0.16 : rawDy;
            sheet.style.transform = `translate3d(0, ${dy.toFixed(1)}px, 0)`;

            if (backdrop && rawDy > 0) {
                const sheetH = Math.max(240, sheet.getBoundingClientRect().height || 400);
                const progress = Math.min(1, rawDy / sheetH);
                backdrop.style.opacity = String(Math.max(0.15, 1 - progress * 0.85));
            }
        });

        const endDrag = (e) => {
            if (!dragging || e.pointerId !== activePointerId) return;
            dragging = false;
            const pointerToRelease = activePointerId;
            activePointerId = null;
            try { dragZone.releasePointerCapture(pointerToRelease); } catch (_) {}

            const dy = lastY - startY;
            const elapsed = Math.max(1, performance.now() - startTime);
            const velocity = dy / elapsed; // px per ms (Emil Kowalski threshold: ~0.11)

            if (dy > dismissThreshold || (dy > 28 && velocity > velocityThreshold)) {
                if (!prefersReducedMotion()) {
                    sheet.style.transition = `transform 220ms ${EASE_OUT}`;
                    sheet.style.transform = 'translate3d(0, 100%, 0)';
                    if (backdrop) {
                        backdrop.style.transition = 'opacity 200ms ease';
                        backdrop.style.opacity = '0';
                    }
                    setTimeout(() => {
                        if (typeof onDismiss === 'function') onDismiss();
                        resetStyles(false);
                    }, 190);
                } else {
                    if (typeof onDismiss === 'function') onDismiss();
                    resetStyles(false);
                }
            } else {
                resetStyles(!prefersReducedMotion());
            }
        };

        dragZone.addEventListener('pointerup', endDrag);
        dragZone.addEventListener('pointercancel', endDrag);
    }

    function initMobileSheets() {
        // 1. Command Palette mobile sheet
        const cmdOverlay = document.getElementById('cmdPaletteOverlay');
        const cmdSheet = document.getElementById('cmdPalette');
        const cmdGrabber = document.getElementById('cmdPaletteGrabber');
        const cmdBody = document.getElementById('cmdPaletteBody');
        if (cmdOverlay && cmdSheet) {
            attachSheetDrag({
                sheet: cmdSheet,
                handle: cmdGrabber || cmdSheet.querySelector('.cmd-palette-header'),
                backdrop: cmdOverlay,
                scrollContainer: cmdBody,
                onlyBelowPx: 768,
                onDismiss: () => window.closeCommandPalette?.(),
            });
        }

        // 2. Day Summary bottom sheet modal
        const dayModal = document.getElementById('daySummaryModal');
        const daySheet = dayModal?.querySelector('.bottom-sheet-content');
        const dayHandle = dayModal?.querySelector('.bottom-sheet-handle');
        if (dayModal && daySheet) {
            attachSheetDrag({
                sheet: daySheet,
                handle: dayHandle || daySheet,
                backdrop: dayModal,
                scrollContainer: dayModal.querySelector('.modal-dialog-scrollable'),
                onlyBelowPx: 576,
                onDismiss: () => {
                    if (typeof window.closeDaySummaryModal === 'function') {
                        window.closeDaySummaryModal();
                    } else if (window.bootstrap?.Modal) {
                        window.bootstrap.Modal.getInstance(dayModal)?.hide();
                    }
                },
            });
        }

        // 3. Scanner modal sheet (only when used as overlay, not full page)
        const scannerBackdrop = document.getElementById('studentScannerModal');
        if (scannerBackdrop && !scannerBackdrop.classList.contains('scanner-page-screen')) {
            const scannerCard = scannerBackdrop.querySelector('.scanner-modal-card');
            const scannerHandle = scannerBackdrop.querySelector('.scanner-drag-handle');
            if (scannerCard && scannerHandle) {
                attachSheetDrag({
                    sheet: scannerCard,
                    handle: scannerHandle,
                    backdrop: scannerBackdrop,
                    onlyBelowPx: 768,
                    onDismiss: () => window.closeStudentScanner?.(),
                });
            }
        }
    }

    // ── 5. Sonner-Grade Toast Swipe & Pause-on-Hover ───────────────────────
    function enhanceToast(toastEl, durationMs) {
        if (!toastEl) return;
        let remaining = Math.max(1200, Number(durationMs) || 4500);
        let startedAt = performance.now();
        let timerId = null;
        let dismissed = false;

        const dismiss = () => {
            if (dismissed) return;
            dismissed = true;
            clearTimeout(timerId);
            toastEl.classList.add('is-exiting');
            setTimeout(() => toastEl.remove(), prefersReducedMotion() ? 20 : 210);
        };

        const schedule = () => {
            clearTimeout(timerId);
            startedAt = performance.now();
            timerId = setTimeout(dismiss, remaining);
        };

        const pause = () => {
            clearTimeout(timerId);
            remaining = Math.max(600, remaining - (performance.now() - startedAt));
        };

        toastEl.addEventListener('pointerenter', pause);
        toastEl.addEventListener('pointerleave', () => {
            if (!dismissed) schedule();
        });
        document.addEventListener('visibilitychange', () => {
            if (dismissed) return;
            if (document.hidden) pause();
            else schedule();
        });

        // Horizontal swipe-to-dismiss on pointer devices & touch
        let pid = null;
        let sx = 0;
        let lx = 0;
        let st = 0;
        toastEl.addEventListener('pointerdown', (e) => {
            if (e.target.closest('button')) return;
            pid = e.pointerId;
            sx = e.clientX;
            lx = sx;
            st = performance.now();
            pause();
            toastEl.style.transition = 'none';
            try { toastEl.setPointerCapture(pid); } catch (_) {}
        });
        toastEl.addEventListener('pointermove', (e) => {
            if (pid === null || e.pointerId !== pid) return;
            lx = e.clientX;
            const dx = Math.max(-24, lx - sx);
            toastEl.style.transform = `translate3d(${dx}px, 0, 0)`;
            if (dx > 0) toastEl.style.opacity = String(Math.max(0.2, 1 - dx / 220));
        });
        const endSwipe = (e) => {
            if (pid === null || e.pointerId !== pid) return;
            try { toastEl.releasePointerCapture(pid); } catch (_) {}
            pid = null;
            const dx = lx - sx;
            const vel = dx / Math.max(1, performance.now() - st);
            toastEl.style.transition = '';
            if (dx > 84 || (dx > 28 && vel > 0.14)) {
                dismiss();
            } else {
                toastEl.style.transform = '';
                toastEl.style.opacity = '';
                schedule();
            }
        };
        toastEl.addEventListener('pointerup', endSwipe);
        toastEl.addEventListener('pointercancel', endSwipe);

        const closeBtn = toastEl.querySelector('.modern-toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dismiss();
            });
        }

        // Smooth CSS entry via transition (interruptible, no keyframes)
        requestAnimationFrame(() => {
            toastEl.classList.remove('is-entering');
        });
        schedule();
        return dismiss;
    }

    // ── 6. Blur-Masked Copy Morph Helper ───────────────────────────────────
    function copyWithMorph(btn, text, fallbackMessage) {
        if (!btn || !text) return;
        const applyText = (html, copied) => {
            let wrap = btn.querySelector('.sm-morph-content');
            if (!wrap) {
                btn.innerHTML = `<span class="sm-morph-content">${btn.innerHTML}</span>`;
                wrap = btn.querySelector('.sm-morph-content');
            }
            const reduced = prefersReducedMotion();
            if (!reduced) wrap.classList.add('is-morphing');
            setTimeout(() => {
                wrap.innerHTML = html;
                btn.classList.toggle('is-copied', copied);
                wrap.classList.remove('is-morphing');
            }, reduced ? 0 : 90);
        };

        const originalHtml = btn.dataset.origHtml || btn.innerHTML;
        btn.dataset.origHtml = originalHtml;

        if (!navigator.clipboard || !navigator.clipboard.writeText) {
            if (typeof window.showToast === 'function') {
                window.showToast(fallbackMessage || 'Copy is unavailable on this browser.', 'warning');
            }
            return;
        }

        navigator.clipboard
            .writeText(text)
            .then(() => {
                window.triggerHaptic?.('success');
                applyText('<i class="bi bi-check2" aria-hidden="true"></i><span>Copied</span>', true);
                clearTimeout(btn._copyResetTimer);
                btn._copyResetTimer = setTimeout(() => {
                    applyText(originalHtml, false);
                }, 1600);
            })
            .catch(() => {
                if (typeof window.showToast === 'function') {
                    window.showToast(fallbackMessage || 'Unable to copy Student ID.', 'warning');
                }
            });
    }

    // ── 7. Calendar Directional Month Shift & Inspector Crossfade ──────────
    function animateCalendarShift(gridEl, direction) {
        if (!gridEl || prefersReducedMotion() || typeof gridEl.animate !== 'function') return;
        const offset = direction === 'prev' ? -16 : direction === 'next' ? 16 : 0;
        if (!offset) return;
        gridEl.animate(
            [
                { opacity: 0.45, transform: `translate3d(${offset}px, 0, 0)` },
                { opacity: 1, transform: 'translate3d(0, 0, 0)' },
            ],
            { duration: 220, easing: EASE_OUT }
        );
    }

    function animateInspectorSwap(listEl) {
        if (!listEl || prefersReducedMotion()) return;
        listEl.classList.add('is-swapping');
        requestAnimationFrame(() => {
            requestAnimationFrame(() => listEl.classList.remove('is-swapping'));
        });
    }

    // ── 8. Excuse Dropzone Drag Highlight & Classes TBA Softening ──────────
    function initDropzoneHighlight() {
        const dz = document.getElementById('dropZone');
        if (!dz || dz.dataset.smDzBound === '1') return;
        dz.dataset.smDzBound = '1';
        let depth = 0;
        dz.addEventListener('dragenter', () => {
            depth++;
            dz.classList.add('is-dragover');
        });
        dz.addEventListener('dragleave', () => {
            depth = Math.max(0, depth - 1);
            if (depth === 0) dz.classList.remove('is-dragover');
        });
        dz.addEventListener('drop', () => {
            depth = 0;
            dz.classList.remove('is-dragover');
        });
    }

    // ── 9. Responsive <details> Disclosure & Overflow Action Menus ─────────
    function initResponsiveDisclosures() {
        const nodes = document.querySelectorAll('details[data-open-desktop="true"]');
        if (!nodes.length) return;
        const mq = window.matchMedia('(min-width: 768px)');
        const apply = () => {
            nodes.forEach((el) => {
                if (el.dataset.userToggled === '1') return;
                el.open = mq.matches;
            });
        };
        nodes.forEach((el) => {
            const summary = el.querySelector(':scope > summary');
            if (summary) {
                summary.addEventListener('click', () => {
                    el.dataset.userToggled = '1';
                });
            }
        });
        apply();
        if (typeof mq.addEventListener === 'function') {
            mq.addEventListener('change', apply);
        }
    }

    function initActionMenus() {
        const getMenus = () => Array.from(document.querySelectorAll('details.sd-action-menu'));
        document.addEventListener('click', (e) => {
            const clickedMenu = e.target.closest('details.sd-action-menu');
            getMenus().forEach((menu) => {
                if (menu !== clickedMenu && menu.open) {
                    menu.open = false;
                }
            });
        });
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            getMenus().forEach((menu) => {
                if (menu.open) {
                    menu.open = false;
                    menu.querySelector(':scope > summary')?.focus({ preventScroll: true });
                }
            });
        });
    }

    function initCalendarWorkspaceTabs() {
        const tabs = Array.from(document.querySelectorAll('[data-cal-workspace-tab]'));
        if (!tabs.length) return;
        const calPane = document.getElementById('scalWorkspaceCalendar');
        const recPane = document.getElementById('scalWorkspaceRecords');

        const activate = (target, shouldScroll) => {
            const mode = target === 'records' ? 'records' : 'calendar';
            tabs.forEach((btn) => {
                const active = btn.dataset.calWorkspaceTab === mode;
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
                btn.classList.toggle('is-active', active);
            });
            if (calPane) calPane.hidden = mode !== 'calendar';
            if (recPane) recPane.hidden = mode !== 'records';
            if (shouldScroll && mode === 'records') {
                requestAnimationFrame(() => window.scrollTo({ top: 0, behavior: prefersReducedMotion() ? 'auto' : 'smooth' }));
            }
        };

        tabs.forEach((btn) => {
            btn.addEventListener('click', () => {
                const mode = btn.dataset.calWorkspaceTab;
                activate(mode, false);
                if (mode === 'calendar' && window.location.hash === '#attendance-records') {
                    history.replaceState(null, '', window.location.pathname + window.location.search);
                }
            });
        });

        const params = new URLSearchParams(window.location.search);
        const hasRecordFilter = Boolean(params.get('q') || params.get('status') || params.get('record_date') || params.get('page'));
        const wantsRecords = window.location.hash === '#attendance-records' || hasRecordFilter;
        activate(wantsRecords ? 'records' : 'calendar', false);
        if (window.location.hash === '#attendance-records') {
            requestAnimationFrame(() => window.scrollTo({ top: 0, behavior: 'auto' }));
        }

        window.addEventListener('hashchange', () => {
            if (window.location.hash === '#attendance-records') {
                activate('records', true);
            }
        });

        // Any internal link targeting #attendance-records activates the Records tab seamlessly
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href*="#attendance-records"]');
            if (!link) return;
            try {
                const dest = new URL(link.href, window.location.origin);
                if (dest.pathname === window.location.pathname && dest.search === window.location.search) {
                    activate('records', true);
                }
            } catch (_) {}
        });

        window.StudentMotion = window.StudentMotion || {};
        window.StudentMotion.switchCalendarWorkspace = (mode) => activate(mode, false);
    }

    function boot() {
        initScrollElevation();
        initAllGlidingControls();
        initDashboardMeterReveal();
        initMobileSheets();
        initDropzoneHighlight();
        initResponsiveDisclosures();
        initActionMenus();
        initCalendarWorkspaceTabs();
    }

    window.StudentMotion = {
        attachSheetDrag,
        enhanceToast,
        copyWithMorph,
        animateCalendarShift,
        animateInspectorSwap,
        initGlidingGroup,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
