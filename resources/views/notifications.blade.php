@extends('layouts.app')

@section('page-title', 'Notifications')

@section('content')
@php
    $getVisual = function (string $type): array {
        if ($type === 'system_update') {
            return ['pill' => 'is-emerald', 'label' => 'System Update'];
        }
        if ($type === 'warning_3' || $type === 'warning_consecutive_3') {
            return ['pill' => 'is-danger', 'label' => 'Critical Warning'];
        }
        if ($type === 'warning_2' || str_contains($type, 'warning')) {
            return ['pill' => 'is-amber', 'label' => 'Attendance Warning'];
        }
        if ($type === 'absence') {
            return ['pill' => 'is-danger', 'label' => 'Absence Recorded'];
        }
        if (str_contains($type, 'excuse')) {
            return ['pill' => 'is-sky', 'label' => 'Excuse Update'];
        }
        return ['pill' => 'is-rose', 'label' => 'Notice'];
    };
@endphp

<div>
    <header class="sf-page-head">
        <div>
            <h1>Notifications</h1>
            <p>Class attendance records, warnings, and school announcements</p>
        </div>
        <div class="sf-page-actions">
            <button type="button"
                    class="sf-btn sf-btn-secondary"
                    id="btnMarkAllRead"
                    onclick="markAllNotificationsRead()"
                    {{ ($counts['unread'] ?? 0) === 0 ? 'disabled' : '' }}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 7 17l-5-5"/><path d="m22 10-7.5 7.5L13 16"/></svg>
                <span>Mark all read</span>
            </button>
        </div>
    </header>

    {{-- Filter Bar --}}
    <div class="sf-toolbar">
        <div class="sf-filter-pills" role="navigation" aria-label="Notification status">
            <a href="{{ route('notifications') }}"
               class="sf-filter-pill {{ !request('status') || request('status') === 'active' ? 'active' : '' }}">
                Active <span class="sf-mono" id="tabCountActive">{{ $counts['active'] ?? 0 }}</span>
            </a>
            <a href="{{ route('notifications', ['status' => 'unread']) }}"
               class="sf-filter-pill {{ request('status') === 'unread' ? 'active' : '' }}">
                Unread <span class="sf-mono" id="tabCountUnread">{{ $counts['unread'] ?? 0 }}</span>
            </a>
            <a href="{{ route('notifications', ['status' => 'archived']) }}"
               class="sf-filter-pill {{ request('status') === 'archived' ? 'active' : '' }}">
                Archived <span class="sf-mono" id="tabCountArchived">{{ $counts['archived'] ?? 0 }}</span>
            </a>
        </div>

        <form method="GET" action="{{ route('notifications') }}" class="sf-search-wrap" id="notifSearchForm">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="search" name="search" id="notifSearchInput" class="sf-input" placeholder="Search alerts..." aria-label="Search notifications or subjects" value="{{ request('search') }}" autocomplete="off">
        </form>
    </div>

    {{-- Category Chips --}}
    <div class="sf-filter-pills" style="margin-bottom:14px;">
        <a href="{{ route('notifications', array_filter(['status' => request('status')])) }}"
           class="sf-filter-pill {{ !request('type') ? 'active' : '' }}">All Types</a>
        <a href="{{ route('notifications', array_filter(['status' => request('status'), 'type' => 'absence'])) }}"
           class="sf-filter-pill {{ request('type') === 'absence' ? 'active' : '' }}">Absences ({{ $counts['absences'] ?? 0 }})</a>
        <a href="{{ route('notifications', array_filter(['status' => request('status'), 'type' => 'warning'])) }}"
           class="sf-filter-pill {{ request('type') === 'warning' ? 'active' : '' }}">Warnings ({{ $counts['warnings'] ?? 0 }})</a>
        <a href="{{ route('notifications', array_filter(['status' => request('status'), 'type' => 'system'])) }}"
           class="sf-filter-pill {{ request('type') === 'system' ? 'active' : '' }}">System Updates</a>
    </div>

    {{-- Notification Cards --}}
    <section class="sf-card" id="notifListContainer" aria-label="Notifications list">
        @forelse($notifications as $notification)
            @php
                $vis = $getVisual((string) $notification->type);
                $isArchived = $notification->isArchived();
                $isUnread = !$notification->is_read;
            @endphp
            <article class="sf-record-item notif-item-card {{ $isUnread ? 'unread' : '' }}"
                     id="notifCard_{{ $notification->id }}"
                     data-notif-id="{{ $notification->id }}"
                     data-is-unread="{{ $isUnread ? 'true' : 'false' }}"
                     style="{{ $isUnread ? 'background:var(--sf-brand-soft);' : '' }}">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-bottom:6px;">
                            <span class="sf-pill {{ $vis['pill'] }}">{{ $vis['label'] }}</span>
                            @if($isUnread)
                                <span class="sf-pill is-rose" id="itemUnreadBadge_{{ $notification->id }}">Unread</span>
                            @endif
                            @if($notification->subject_code)
                                <span class="sf-code-chip">{{ $notification->subject_code }}</span>
                            @endif
                            <span class="sf-mono" style="font-size:11px;color:var(--sf-text-muted);">
                                {{ $notification->created_at->diffForHumans() }} · {{ $notification->created_at->format('M j, g:i A') }}
                            </span>
                        </div>
                        <div style="font-size:13px;color:var(--sf-text);line-height:1.45;">
                            {{ $notification->message }}
                        </div>
                        @if($notification->subject || $notification->sender)
                            <div style="font-size:11.5px;color:var(--sf-text-muted);margin-top:5px;">
                                {{ collect([$notification->subject?->name, $notification->sender ? 'From ' . $notification->sender->name : null])->filter()->implode(' · ') }}
                            </div>
                        @endif
                    </div>

                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;flex-shrink:0;">
                        @if($notification->type === 'absence' || str_contains((string) $notification->type, 'warning'))
                            <a href="{{ route('excuses') }}" class="sf-btn sf-btn-secondary sf-btn-sm">Submit Excuse</a>
                        @endif
                        @if($isUnread)
                            <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm" id="btnMarkRead_{{ $notification->id }}" onclick="markSingleNotificationRead({{ $notification->id }})">
                                Mark read
                            </button>
                        @endif
                        @if($isArchived)
                            <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm" onclick="unarchiveNotification({{ $notification->id }})">Restore</button>
                        @else
                            <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm" onclick="archiveNotification({{ $notification->id }})">Archive</button>
                        @endif
                        <button type="button" class="sf-btn sf-btn-danger sf-btn-sm btn-notif-delete" onclick="deleteNotification({{ $notification->id }})" aria-label="Delete notification">
                            Delete
                        </button>
                    </div>
                </div>
            </article>
        @empty
            <div class="sf-empty-state">
                <span class="sf-empty-icon-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                </span>
                <h3>{{ request('status') === 'unread' ? 'All caught up!' : (request('status') === 'archived' ? 'No archived notifications' : 'No notifications found') }}</h3>
                <p>Attendance alerts, excuse decisions, and school announcements will appear here.</p>
                @if(request()->hasAny(['status', 'type', 'search']))
                    <a href="{{ route('notifications') }}" class="sf-btn sf-btn-secondary sf-btn-sm" style="margin-top:6px;">View Active Notifications</a>
                @endif
            </div>
        @endforelse
    </section>

    @if($notifications->hasPages())
        <div class="sf-pagination-wrap" style="margin-top:16px;display:flex;justify-content:center;">
            {{ $notifications->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

<script @cspNonce>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    function parseJsonResponse(res) {
        return res.json().catch(() => null).then(data => {
            if (!res.ok || !data || !data.success) {
                throw new Error((data && data.message) || ('Request failed (' + res.status + ')'));
            }
            return data;
        });
    }

    function adjustTabCount(id, delta) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = String(Math.max(0, (parseInt(el.textContent, 10) || 0) + delta));
    }

    function updateUnreadUi(count) {
        const c = Math.max(0, Number(count) || 0);
        const tab = document.getElementById('tabCountUnread');
        if (tab) tab.textContent = String(c);
        const btn = document.getElementById('btnMarkAllRead');
        if (btn) btn.disabled = c <= 0;
        window.refreshNotificationBell?.();
    }

    window.markSingleNotificationRead = function (id) {
        fetch('/notifications/' + id + '/read', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', Accept: 'application/json' }
        })
        .then(parseJsonResponse)
        .then(data => {
            const card = document.getElementById('notifCard_' + id);
            if (card) { card.classList.remove('unread'); card.style.background = ''; card.dataset.isUnread = 'false'; }
            document.getElementById('btnMarkRead_' + id)?.remove();
            document.getElementById('itemUnreadBadge_' + id)?.remove();
            if (typeof data.unread_count !== 'undefined') updateUnreadUi(data.unread_count);
            else adjustTabCount('tabCountUnread', -1);
            window.showToast?.('Marked as read', 'success');
        })
        .catch(err => window.showToast?.(err.message, 'error'));
    };

    window.markAllNotificationsRead = function () {
        const btn = document.getElementById('btnMarkAllRead');
        if (btn) btn.disabled = true;
        fetch('{{ route("notifications.read") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', Accept: 'application/json' }
        })
        .then(parseJsonResponse)
        .then(() => {
            document.querySelectorAll('.notif-item-card.unread').forEach(c => { c.classList.remove('unread'); c.style.background = ''; });
            document.querySelectorAll('[id^="btnMarkRead_"], [id^="itemUnreadBadge_"]').forEach(el => el.remove());
            updateUnreadUi(0);
            window.showToast?.('All notifications marked as read', 'success');
        })
        .catch(err => {
            if (btn) btn.disabled = false;
            window.showToast?.(err.message, 'error');
        });
    };

    window.archiveNotification = function (id) {
        const card = document.getElementById('notifCard_' + id);
        fetch('/notifications/' + id + '/archive', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', Accept: 'application/json' }
        })
        .then(parseJsonResponse)
        .then(data => {
            card?.remove();
            if (typeof data.unread_count !== 'undefined') updateUnreadUi(data.unread_count);
            adjustTabCount('tabCountActive', -1);
            adjustTabCount('tabCountArchived', 1);
            window.showToast?.('Notification archived', 'success');
        })
        .catch(err => window.showToast?.(err.message, 'error'));
    };

    window.unarchiveNotification = function (id) {
        const card = document.getElementById('notifCard_' + id);
        fetch('/notifications/' + id + '/unarchive', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', Accept: 'application/json' }
        })
        .then(parseJsonResponse)
        .then(data => {
            card?.remove();
            if (typeof data.unread_count !== 'undefined') updateUnreadUi(data.unread_count);
            adjustTabCount('tabCountActive', 1);
            adjustTabCount('tabCountArchived', -1);
            window.showToast?.('Notification restored', 'success');
        })
        .catch(err => window.showToast?.(err.message, 'error'));
    };

    window.deleteNotification = function (id) {
        const card = document.getElementById('notifCard_' + id);
        const btn = card?.querySelector('.btn-notif-delete');
        if (btn && btn.dataset.confirmPending !== '1') {
            btn.dataset.confirmPending = '1';
            btn.textContent = 'Confirm?';
            setTimeout(() => { if (btn) { delete btn.dataset.confirmPending; btn.textContent = 'Delete'; } }, 3500);
            return;
        }
        const isArchivedView = new URLSearchParams(window.location.search).get('status') === 'archived';
        fetch('/notifications/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', Accept: 'application/json' }
        })
        .then(parseJsonResponse)
        .then(data => {
            card?.remove();
            if (typeof data.unread_count !== 'undefined') updateUnreadUi(data.unread_count);
            adjustTabCount(isArchivedView ? 'tabCountArchived' : 'tabCountActive', -1);
            window.showToast?.('Notification deleted', 'success');
        })
        .catch(err => window.showToast?.(err.message, 'error'));
    };

    const searchInput = document.getElementById('notifSearchInput');
    searchInput?.addEventListener('input', () => {
        const q = searchInput.value.toLowerCase().trim();
        document.querySelectorAll('.notif-item-card').forEach(card => {
            card.style.display = (!q || card.textContent.toLowerCase().includes(q)) ? '' : 'none';
        });
    });
})();
</script>
@endsection
