/**
 * Pull-to-Refresh for Mobile
 * Detects touch pull at top of page, shows indicator, reloads on release.
 * Only activates on mobile viewports (≤768px) and when scrolled to top.
 */
(function () {
    'use strict';

    // Only on mobile
    if (window.innerWidth > 768) return;

    // Create indicator element
    var indicator = document.createElement('div');
    indicator.className = 'pull-refresh-indicator';
    indicator.innerHTML = '<i class="bi bi-arrow-down pull-refresh-arrow"></i><span class="ptr-text">Pull to refresh</span>';
    document.body.appendChild(indicator);

    var startY = 0;
    var currentY = 0;
    var pulling = false;
    var threshold = 80;
    var maxPull = 120;

    function getScrollTop() {
        return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
    }

    document.addEventListener('touchstart', function (e) {
        if (getScrollTop() <= 5) {
            startY = e.touches[0].pageY;
            pulling = true;
        }
    }, { passive: true });

    document.addEventListener('touchmove', function (e) {
        if (!pulling) return;

        currentY = e.touches[0].pageY;
        var delta = currentY - startY;

        if (delta < 0) {
            pulling = false;
            indicator.classList.remove('visible', 'ready');
            return;
        }

        if (delta > 10 && getScrollTop() <= 5) {
            var progress = Math.min(delta / maxPull, 1);

            indicator.classList.add('visible');
            indicator.style.transform = 'translateX(-50%) translateY(' + (progress * 24 - 4) + 'px)';
            indicator.style.opacity = Math.min(progress * 1.5, 1);

            var textEl = indicator.querySelector('.ptr-text');
            if (delta >= threshold) {
                indicator.classList.add('ready');
                if (textEl) textEl.textContent = 'Release to refresh';
            } else {
                indicator.classList.remove('ready');
                if (textEl) textEl.textContent = 'Pull to refresh';
            }
        }
    }, { passive: true });

    document.addEventListener('touchend', function () {
        if (!pulling) return;

        var delta = currentY - startY;

        if (delta >= threshold) {
            // Show loading state
            indicator.classList.add('loading');
            indicator.classList.remove('ready');
            indicator.innerHTML = '<div class="pull-refresh-spinner"></div><span>Refreshing...</span>';
            indicator.style.transform = 'translateX(-50%) translateY(16px)';
            indicator.style.opacity = '1';

            // Haptic feedback (if available)
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }

            setTimeout(function () {
                window.location.reload();
            }, 400);
        } else {
            indicator.classList.remove('visible', 'ready');
            indicator.style.transform = '';
            indicator.style.opacity = '';
        }

        pulling = false;
        startY = 0;
        currentY = 0;
    }, { passive: true });
})();
