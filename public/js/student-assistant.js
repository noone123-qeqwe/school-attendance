(() => {
    const panel = document.querySelector('[data-assistant-qr]');
    if (!panel) return;

    const find = (name) => panel.querySelector(`[data-qr-${name}]`);
    const canvas = find('canvas');
    const placeholder = find('placeholder');
    const status = find('status');
    const message = find('message');
    const countdownRow = find('countdown-row');
    const countdown = find('countdown');
    const generatorRow = find('generator-row');
    const generator = find('generator');
    const error = find('error');
    const retryButton = find('retry');
    const replaceButton = find('replace');
    const confirmation = find('confirm');
    const cancelButton = find('cancel');
    const confirmButton = find('confirm-replace');
    const sessionEnd = document.querySelector('[data-assistant-session-end]');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const timeFormat = new Intl.DateTimeFormat(document.documentElement.lang || 'en', {
        timeZone: 'Asia/Manila', hour: 'numeric', minute: '2-digit'
    });
    let endsAt = Number(panel.dataset.sessionEndsAt) * 1000;
    let expiresAt = 0;
    let tokenId = null;
    let pending = null;
    let stopped = false;
    let state = 'connecting';
    let pollTimer;
    let tickTimer;

    const states = {
        connecting: ['Connecting', 'pending', 'Getting the current attendance code…'],
        updating: ['Refreshing', 'pending', 'Preparing a new code…'],
        ready: ['Ready to scan', 'approved', 'Ask classmates to scan this QR with their own device.'],
        offline: ['Offline', 'pending', 'Reconnect to the internet to display an attendance code.'],
        unavailable: ['Connection issue', 'pending', 'The attendance service is unavailable. Try again.'],
        ended: ['Session ended', 'dismissed', 'Return to Assigned classes to find another session.'],
        denied: ['Access ended', 'dismissed', 'Your access to this session has ended. Return to Assigned classes.'],
        signedOut: ['Sign-in required', 'dismissed', 'Sign in again to reopen the attendance session.'],
    };

    function updateControls() {
        const canReplace = state === 'ready' && Boolean(tokenId);
        replaceButton.hidden = !canReplace;
        replaceButton.disabled = Boolean(pending);
        confirmButton.disabled = Boolean(pending) || !canReplace;
        retryButton.hidden = state !== 'unavailable';
        retryButton.disabled = Boolean(pending);
    }

    function showState(next) {
        state = next;
        const [label, style, detail] = states[next];
        status.textContent = label;
        status.className = `assistant-status assistant-status--${style}`;
        if (message.textContent !== detail) message.textContent = detail;
        updateControls();
    }

    function closeConfirmation(restoreFocus = false) {
        confirmation.hidden = true;
        replaceButton.setAttribute('aria-expanded', 'false');
        updateControls();
        if (restoreFocus && !replaceButton.hidden) replaceButton.focus();
    }

    function showError(text = '') {
        error.hidden = !text;
        error.textContent = text;
    }

    function clearQr(next) {
        canvas.replaceChildren();
        canvas.hidden = true;
        placeholder.hidden = false;
        countdownRow.hidden = true;
        generatorRow.hidden = true;
        expiresAt = 0;
        tokenId = null;
        closeConfirmation();
        showState(next);
    }

    function stop(next) {
        stopped = true;
        pending?.abort();
        window.clearInterval(pollTimer);
        window.clearInterval(tickTimer);
        showError();
        clearQr(next);
    }

    function updateEnd(value) {
        const timestamp = Number(value) * 1000;
        if (!timestamp || timestamp === endsAt) return;
        endsAt = timestamp;
        if (sessionEnd) {
            sessionEnd.dateTime = new Date(endsAt).toISOString();
            sessionEnd.textContent = timeFormat.format(endsAt);
        }
    }

    function updateCountdown() {
        if (!expiresAt) return;
        const seconds = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
        countdown.textContent = `${seconds}s`;
        if (seconds === 0) {
            clearQr('updating');
            refresh('rotate');
        }
    }

    function renderQr(data) {
        updateEnd(data.session_ends_at);
        expiresAt = Number(data.expires_at) * 1000;
        if (!expiresAt || !data.token_id || typeof data.scan_url !== 'string') {
            throw new Error('Missing QR data');
        }
        if (Date.now() >= expiresAt || Date.now() >= endsAt) {
            clearQr('updating');
            return;
        }
        if (tokenId !== data.token_id) {
            canvas.replaceChildren();
            new window.QRCode(canvas, { text: data.scan_url, width: 320, height: 320, correctLevel: window.QRCode.CorrectLevel.M });
            canvas.removeAttribute('title');
            tokenId = data.token_id;
        }
        canvas.hidden = false;
        placeholder.hidden = true;
        countdownRow.hidden = false;
        generator.textContent = data.generated_by || '';
        generatorRow.hidden = !data.generated_by;
        showState('ready');
        updateCountdown();
    }

    async function getQr(operation, signal) {
        const headers = { Accept: 'application/json' };
        const method = operation === 'status' ? 'GET' : 'POST';
        if (method === 'POST') headers['X-CSRF-TOKEN'] = csrf;
        const response = await fetch(panel.dataset[`${operation}Url`], {
            method, headers, signal, credentials: 'same-origin', cache: 'no-store',
        });
        const data = await response.json().catch(() => null);
        return { response, data };
    }

    async function refresh(operation = 'status') {
        if (stopped || pending || !navigator.onLine) return;
        const controller = new AbortController();
        pending = controller;
        const timeout = window.setTimeout(() => controller.abort(), 10000);
        if (operation !== 'status') showError();
        if (!tokenId) showState(operation === 'status' ? 'connecting' : 'updating');
        updateControls();

        try {
            let { response, data } = await getQr(operation, controller.signal);
            if (operation === 'status' && response.ok && data?.success && !data.active) {
                updateEnd(data.session_ends_at);
                clearQr('updating');
                ({ response, data } = await getQr('generate', controller.signal));
            }
            if (controller.signal.aborted || stopped) return;
            if (response.status === 410 || data?.errors?.session) return stop('ended');
            if (response.status === 401 || response.status === 419 || response.redirected) return stop('signedOut');
            if (response.status === 403) return stop(Date.now() >= endsAt ? 'ended' : 'denied');
            if (!response.ok || !data?.success) {
                if (operation === 'replace' && tokenId && expiresAt > Date.now()) {
                    showError(data?.errors?.qr?.[0] || 'Could not replace the code. The current code remains active.');
                    return;
                }
                throw new Error('Code unavailable');
            }
            renderQr(data);
        } catch {
            if (!stopped && navigator.onLine) {
                showError();
                clearQr('unavailable');
            }
        } finally {
            window.clearTimeout(timeout);
            pending = null;
            updateControls();
        }
    }

    replaceButton.addEventListener('click', () => {
        if (!confirmation.hidden) {
            closeConfirmation(true);
            return;
        }
        confirmation.hidden = false;
        replaceButton.setAttribute('aria-expanded', 'true');
        updateControls();
        cancelButton.focus();
    });
    cancelButton.addEventListener('click', () => closeConfirmation(true));
    confirmButton.addEventListener('click', () => {
        closeConfirmation(true);
        refresh('replace');
    });
    confirmation.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeConfirmation(true);
    });
    retryButton.addEventListener('click', () => refresh());

    window.attendanceUpdateGuard = () => !stopped && Date.now() < endsAt
        ? 'Finish displaying the attendance QR before updating.'
        : null;
    window.addEventListener('offline', () => {
        if (stopped) return;
        pending?.abort();
        showError();
        clearQr('offline');
    });
    window.addEventListener('online', () => refresh());
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            updateCountdown();
            refresh();
        }
    });

    function subscribe() {
        window.Echo?.private(`assistant-session.${panel.dataset.sessionId}`)
            .listen('.attendance.qr.changed', () => refresh())
            .listen('.attendance.session.changed', (event) => {
                if (event.change_type === 'closed') stop('ended');
                if (event.change_type === 'extended') updateEnd(event.session_ends_at);
            });
    }
    const submitted = document.getElementById('assistant-submission-feedback');
    if (submitted) {
        const url = new URL(window.location.href);
        url.searchParams.delete('submitted');
        url.hash = 'attendance-reports';
        history.replaceState(history.state, '', url);
    }
    const formFeedback = submitted || document.querySelector('#attendance-reports [aria-invalid="true"], #attendance-reports [role="alert"][tabindex]');
    function focusFeedback() {
        if (!formFeedback || ![document.body, formFeedback].includes(document.activeElement)) return;
        formFeedback.focus({ preventScroll: true });
        formFeedback.scrollIntoView({ block: 'center' });
    }
    focusFeedback();
    if (formFeedback && document.readyState !== 'complete') {
        window.addEventListener('load', () => window.requestAnimationFrame(focusFeedback), { once: true });
    }

    tickTimer = window.setInterval(updateCountdown, 1000);
    pollTimer = window.setInterval(() => refresh(), 5000);
    if (navigator.onLine) refresh();
    else clearQr('offline');
    if (window.Echo) subscribe();
    else window.addEventListener('load', subscribe, { once: true });
})();
