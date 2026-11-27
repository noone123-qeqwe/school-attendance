@php
    $noticeUser = auth()->user();
@endphp

@if($noticeUser && blank($noticeUser->section))
<x-alert-banner role="status" class="sd-notice-banner" id="missingSectionNotice"
                alert-key="missing-class-section" version="unassigned" dismiss-label="Dismiss class section notice"
                close-id="closeMissingSectionNoticeBtn">
    <span class="sd-notice-icon" aria-hidden="true"><i class="bi bi-info-circle-fill"></i></span>
    <div class="sd-notice-copy">
        <strong>Class section not assigned</strong>
        <span>{{ $message ?? 'Your section timetable will appear after the school assigns your section. Contact the registrar or school administrator to update your profile.' }}</span>
    </div>
</x-alert-banner>
@elseif($noticeUser)
    <span hidden data-alert-resolved="missing-class-section"></span>
@endif
