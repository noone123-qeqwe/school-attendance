@php
    $noticeUser = auth()->user();
@endphp

@if($noticeUser && blank($noticeUser->section))
<div role="status" class="sd-notice-banner" id="missingSectionNotice" data-notice-key="sd_dismiss_missing_section_{{ $noticeUser->id }}">
    <span class="sd-notice-icon" aria-hidden="true"><i class="bi bi-info-circle-fill"></i></span>
    <div class="sd-notice-copy">
        <strong>Class section not assigned</strong>
        <span>{{ $message ?? 'Your section timetable will appear after the school assigns your section. Contact the registrar or school administrator to update your profile.' }}</span>
    </div>
    <button type="button"
            class="sd-notice-close"
            id="closeMissingSectionNoticeBtn"
            aria-label="Close notice"
            title="Dismiss notice">
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
</div>
<script nonce="{{ csp_nonce() }}">
(function() {
    const banner = document.getElementById('missingSectionNotice');
    if (!banner) return;
    const storageKey = banner.getAttribute('data-notice-key');
    try {
        if (storageKey && window.sessionStorage && sessionStorage.getItem(storageKey) === '1') {
            banner.hidden = true;
            return;
        }
    } catch (e) {}

    const closeBtn = document.getElementById('closeMissingSectionNoticeBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            banner.hidden = true;
            try {
                if (storageKey && window.sessionStorage) {
                    sessionStorage.setItem(storageKey, '1');
                }
            } catch (e) {}
        });
    }
})();
</script>
@endif
