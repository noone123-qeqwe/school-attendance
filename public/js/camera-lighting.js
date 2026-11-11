(function () {
    'use strict';
    const instances = new WeakMap();

    function mount(root) {
        if (!root) return null;
        if (instances.has(root)) return instances.get(root);
        const toggle = root.querySelector('[data-light-toggle]');
        const level = root.querySelector('[data-light-level]');
        const levelOutput = root.querySelector('[data-light-level-output]');
        const exposureGroup = root.querySelector('[data-light-exposure-group]');
        const exposure = root.querySelector('[data-light-exposure]');
        const exposureOutput = root.querySelector('[data-light-exposure-output]');
        const help = root.querySelector('[data-light-help]');
        const fill = root.querySelector('[data-light-fill]');
        document.body.appendChild(fill);
        let track = null, enabled = false, locked = false, torch = false, range = null;
        let epoch = 0, revision = 0, pending = Promise.resolve(), originalConstraints = {};

        function settings(target) {
            try { return target?.getSettings?.() || {}; } catch (_) { return {}; }
        }
        function render() {
            toggle.disabled = !track || locked;
            toggle.setAttribute('aria-pressed', String(enabled));
            level.disabled = !track || !enabled || locked;
            levelOutput.textContent = level.value + '%';
            fill.style.setProperty('--camera-fill-opacity', String(Number(level.value) / 100));
            fill.hidden = !track || !enabled;
            exposureGroup.hidden = !range;
            exposure.disabled = !track || !range || locked;
            exposureOutput.textContent = range ? Number(exposure.value).toFixed(2) : '';
            const message = enabled
                ? (torch ? 'Screen light is adjustable. The camera flash has fixed brightness.' : 'Screen light is on. Adjust its strength for a clearer face capture.')
                : 'Turn on the light in dim conditions.';
            if (help.textContent !== message) help.textContent = message;
        }
        function queueAdjustment() {
            const id = ++revision, session = epoch, target = track;
            // Coalesce slider movements while keeping hardware changes in order.
            pending = pending.catch(() => {}).then(async () => {
                if (!target || target !== track || session !== epoch || id !== revision || (!torch && !range)) return;
                const wanted = enabled;
                const adjustment = {};
                if (torch) adjustment.torch = wanted;
                if (range) adjustment.exposureCompensation = Number(exposure.value);
                // applyConstraints replaces the previous constraints, so retain both lighting settings and the original camera options.
                const constraints = { ...originalConstraints, advanced: [...(originalConstraints.advanced || []), adjustment] };
                try {
                    await target.applyConstraints(constraints);
                    if (target !== track || session !== epoch) return;
                    if (torch && settings(target).torch === !wanted) throw new Error('Camera flash did not respond');
                } catch (_) {
                    if (target !== track || session !== epoch) return;
                    if (torch) {
                        try {
                            await target.applyConstraints({ ...originalConstraints, advanced: [...(originalConstraints.advanced || []), { torch: false }] });
                        } catch (_) {}
                    }
                    if (target !== track || session !== epoch) return;
                    torch = false;
                    range = null;
                }
                if (target === track && session === epoch) render();
            });
        }
        function turnOff() {
            if (!track || !enabled) return;
            enabled = false;
            render();
            queueAdjustment();
        }
        function stop() {
            const previous = track, hadTorch = torch, base = originalConstraints;
            epoch++; revision++;
            track = null; enabled = false; locked = false; torch = false; range = null;
            root.hidden = true;
            render();
            // A late hardware response must be followed by off, never relight a closed camera.
            if (previous && hadTorch) {
                const cleanup = pending.catch(() => {}).then(async () => {
                    try { await previous.applyConstraints({ ...base, advanced: [...(base.advanced || []), { torch: false }] }); } catch (_) {}
                });
                pending = cleanup;
            }
        }
        const api = {
            start(stream) {
                stop();
                track = stream?.getVideoTracks?.()[0] || stream?.getTracks?.().find(item => item.kind === 'video') || null;
                if (!track || track.readyState === 'ended') { track = null; return; }
                let capabilities = {};
                try { capabilities = track.getCapabilities?.() || {}; } catch (_) {}
                originalConstraints = track.getConstraints?.() || {};
                const canAdjust = typeof track.applyConstraints === 'function';
                torch = canAdjust && (capabilities.torch === true || capabilities.torch?.includes?.(true) === true);
                const candidate = capabilities.exposureCompensation;
                if (canAdjust && candidate && Number.isFinite(candidate.min) && Number.isFinite(candidate.max) && candidate.max > candidate.min) {
                    range = candidate;
                    exposure.min = String(range.min); exposure.max = String(range.max);
                    exposure.step = String(Number.isFinite(range.step) && range.step > 0 ? range.step : (range.max - range.min) / 100);
                    const current = settings(track).exposureCompensation;
                    exposure.value = String(Math.max(range.min, Math.min(range.max, Number.isFinite(current) ? current : 0)));
                }
                root.hidden = false;
                const current = track;
                current.addEventListener?.('ended', () => { if (track === current) stop(); }, { once: true });
                render();
            },
            stop,
            turnOff,
            lock(value) { locked = value; render(); },
            async settle() {
                // Include any last slider event queued before capture was locked.
                let current;
                do { current = pending; await current.catch(() => {}); } while (current !== pending);
                // Let the screen fill paint before reading a live camera frame.
                if (enabled && !document.hidden && window.requestAnimationFrame) {
                    await new Promise(resolve => {
                        const timeout = window.setTimeout(resolve, 100);
                        window.requestAnimationFrame(() => window.requestAnimationFrame(() => {
                            window.clearTimeout(timeout);
                            resolve();
                        }));
                    });
                }
            },
        };
        toggle.addEventListener('click', () => {
            if (!track || locked) return;
            enabled = !enabled; render(); queueAdjustment();
        });
        level.addEventListener('input', () => {
            if (!track || locked) return;
            level.value = String(Math.max(10, Math.min(100, Number(level.value) || 60)));
            render();
        });
        exposure.addEventListener('input', () => {
            if (!track || locked || !range) return;
            const requested = Number(exposure.value);
            const step = Number(exposure.step);
            const value = Number.isFinite(requested) ? requested : 0;
            exposure.value = String(Math.max(range.min, Math.min(range.max, range.min + Math.round((value - range.min) / step) * step)));
            render(); queueAdjustment();
        });
        window.addEventListener('pagehide', stop);
        // Release the fill and hardware torch when the app goes into the background.
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) turnOff();
        });
        stop();
        instances.set(root, api);
        return api;
    }
    window.CameraLighting = { mount };
})();
