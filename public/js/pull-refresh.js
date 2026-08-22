/**
 * Native App Touch Interactions & Pull-to-Refresh
 * - Rubber-band physics with tactile micro-haptics
 * - Native swipe-down bottom sheet dismissal
 */
(function () {
    'use strict';

    if (window.innerWidth > 768) return;

    // ── Pull to Refresh Indicator ──
    var indicator = document.createElement('div');
    indicator.className = 'pull-refresh-indicator';
    indicator.innerHTML = '<i class="bi bi-arrow-down pull-refresh-arrow"></i><span class="ptr-text">Pull to refresh</span>';
    document.body.appendChild(indicator);

    var startY = 0;
    var currentY = 0;
    var pulling = false;
    var threshold = 75;
    var maxPull = 120;
    var hapticTriggered = false;

    function getScrollTop() {
        return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
    }

    document.addEventListener('touchstart', function (e) {
        if (getScrollTop() <= 3 && !document.querySelector('.modal.show')) {
            startY = e.touches[0].pageY;
            pulling = true;
            hapticTriggered = false;
        }
    }, { passive: true });

    document.addEventListener('touchmove', function (e) {
        if (!pulling) return;

        currentY = e.touches[0].pageY;
        var rawDelta = currentY - startY;

        if (rawDelta < 0) {
            pulling = false;
            indicator.classList.remove('visible', 'ready');
            return;
        }

        if (rawDelta > 10 && getScrollTop() <= 3) {
            // Apple-style non-linear rubber-band resistance curve
            var delta = rawDelta < threshold 
                ? rawDelta * 0.75 
                : threshold * 0.75 + Math.pow(rawDelta - threshold, 0.72) * 1.5;
            delta = Math.min(delta, maxPull);

            var progress = Math.min(delta / threshold, 1.2);

            indicator.classList.add('visible');
            indicator.style.transform = 'translateX(-50%) translateY(' + (delta * 0.6) + 'px)';
            indicator.style.opacity = Math.min(progress, 1);

            var textEl = indicator.querySelector('.ptr-text');
            if (rawDelta >= threshold) {
                indicator.classList.add('ready');
                if (textEl) textEl.textContent = 'Release to refresh';
                if (!hapticTriggered) {
                    if (window.triggerHaptic) window.triggerHaptic('medium');
                    hapticTriggered = true;
                }
            } else {
                indicator.classList.remove('ready');
                if (textEl) textEl.textContent = 'Pull to refresh';
                hapticTriggered = false;
            }
        }
    }, { passive: true });

    document.addEventListener('touchend', function () {
        if (!pulling) return;

        var rawDelta = currentY - startY;

        if (rawDelta >= threshold) {
            indicator.classList.add('loading');
            indicator.classList.remove('ready');
            indicator.innerHTML = '<div class="pull-refresh-spinner"></div><span>Refreshing...</span>';
            indicator.style.transform = 'translateX(-50%) translateY(16px)';
            indicator.style.opacity = '1';

            if (window.triggerHaptic) window.triggerHaptic('success');

            setTimeout(function () {
                window.location.reload();
            }, 300);
        } else {
            indicator.classList.remove('visible', 'ready');
            indicator.style.transform = '';
            indicator.style.opacity = '';
        }

        pulling = false;
        startY = 0;
        currentY = 0;
        hapticTriggered = false;
    }, { passive: true });

    // ── Swipe Down to Dismiss Bottom Sheets ──
    var sheetStartY = 0;
    var activeModalDialog = null;

    document.addEventListener('touchstart', function (e) {
        var modal = document.querySelector('.modal.show');
        if (!modal) return;
        var dialog = modal.querySelector('.modal-dialog');
        var touch = e.touches[0];
        
        // If touch begins near the top 60px of the modal dialog (handle area)
        if (dialog && touch.clientY <= dialog.getBoundingClientRect().top + 70) {
            sheetStartY = touch.clientY;
            activeModalDialog = dialog;
            dialog.style.transition = 'none';
        }
    }, { passive: true });

    document.addEventListener('touchmove', function (e) {
        if (!activeModalDialog) return;
        var currentTouchY = e.touches[0].clientY;
        var diff = currentTouchY - sheetStartY;

        if (diff > 0) {
            activeModalDialog.style.transform = 'translate3d(0, ' + diff + 'px, 0)';
        }
    }, { passive: true });

    document.addEventListener('touchend', function (e) {
        if (!activeModalDialog) return;
        var modal = activeModalDialog.closest('.modal');
        var matrix = window.getComputedStyle(activeModalDialog).transform;
        var currentTranslateY = 0;
        
        if (matrix && matrix !== 'none') {
            var values = matrix.split('(')[1].split(')')[0].split(',');
            currentTranslateY = parseFloat(values[5] || values[13] || 0);
        }

        activeModalDialog.style.transition = 'transform 0.32s cubic-bezier(0.32, 0.72, 0, 1)';

        if (currentTranslateY > 100) {
            // Dismiss modal
            activeModalDialog.style.transform = 'translate3d(0, 100%, 0)';
            if (window.triggerHaptic) window.triggerHaptic('light');
            setTimeout(function () {
                if (modal && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                    else modal.classList.remove('show');
                }
                if (activeModalDialog) {
                    activeModalDialog.style.transform = '';
                    activeModalDialog = null;
                }
            }, 250);
        } else {
            // Snap back
            activeModalDialog.style.transform = 'translate3d(0, 0, 0)';
            setTimeout(function () {
                if (activeModalDialog) {
                    activeModalDialog.style.transform = '';
                    activeModalDialog = null;
                }
            }, 320);
        }
    }, { passive: true });
})();
