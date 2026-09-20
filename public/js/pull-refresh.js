/**
 * Native App Touch Interactions & Bottom Sheet Dismissal
 * - Pull-to-refresh is intentionally disabled to preserve user scroll position and page state
 * - Native swipe-down bottom sheet dismissal
 */
(function () {
    'use strict';

    if (window.innerWidth > 768) return;

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
