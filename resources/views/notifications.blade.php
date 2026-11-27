@extends('layouts.app')

@section('page-title', 'Notifications')

@section('content')
<div class="notif-wrapper">
    {{-- Hero Banner --}}
    {{-- Open Header --}}
    <div class="notif-hero">
        <div class="notif-hero-content">
            <div class="notif-hero-left">
                <div class="notif-hero-text">
                    <h1>Notifications</h1>
                    <p>Class attendance records, warnings, and announcements</p>
                </div>
            </div>
            <div class="notif-hero-actions">
                <button type="button" 
                        class="notif-btn-mark-all" 
                        id="btnMarkAllRead" 
                        onclick="markAllNotificationsRead()"
                        {{ ($counts['unread'] ?? 0) === 0 ? 'disabled' : '' }}>
                    <i class="bi bi-check2-all"></i> Mark all read
                </button>
            </div>
        </div>
    </div>

    {{-- Filter & Search Controls Card --}}
    <div class="notif-controls-card">
        <div class="notif-tabs-row">
            {{-- Status Tabs --}}
            <div class="notif-status-tabs">
                <a href="{{ route('notifications') }}" 
                   class="notif-tab {{ !request('status') || request('status') === 'active' ? 'active' : '' }}">
                    <i class="bi bi-inbox-fill"></i> Active
                    <span class="notif-tab-badge" id="tabCountActive">{{ $counts['active'] ?? 0 }}</span>
                </a>
                <a href="{{ route('notifications') }}?status=unread" 
                   class="notif-tab {{ request('status') === 'unread' ? 'active' : '' }}">
                    <i class="bi bi-envelope-fill"></i> Unread
                    <span class="notif-tab-badge" id="tabCountUnread">{{ $counts['unread'] ?? 0 }}</span>
                </a>
                <a href="{{ route('notifications') }}?status=archived" 
                   class="notif-tab {{ request('status') === 'archived' ? 'active' : '' }}">
                    <i class="bi bi-archive-fill"></i> Archived
                    <span class="notif-tab-badge" id="tabCountArchived">{{ $counts['archived'] ?? 0 }}</span>
                </a>
            </div>

            {{-- Quick Client & Server Search --}}
            <form method="GET" action="{{ route('notifications') }}" class="notif-search-box" id="notifSearchForm">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                <i class="bi bi-search notif-search-icon" aria-hidden="true"></i>
                <input type="text" 
                       name="search" 
                       id="notifSearchInput" 
                       class="notif-search-input" 
                       placeholder="Search alerts..." 
                       aria-label="Search notifications or subjects"
                       value="{{ request('search') }}"
                       autocomplete="off">
                @if(request('search'))
                    <a href="{{ route('notifications', array_filter(['status' => request('status'), 'type' => request('type')])) }}" class="notif-search-clear" title="Clear search">
                        <i class="bi bi-x-circle-fill"></i>
                    </a>
                @endif
            </form>
        </div>

        {{-- Category Pills --}}
        <div class="notif-cat-pills">
            <a href="{{ route('notifications', array_filter(['status' => request('status')])) }}" 
               class="notif-pill {{ !request('type') ? 'active' : '' }}">All Types</a>
            <a href="{{ route('notifications', array_filter(['status' => request('status'), 'type' => 'absence'])) }}" 
               class="notif-pill {{ request('type') === 'absence' ? 'active' : '' }}">Absences ({{ $counts['absences'] ?? 0 }})</a>
            <a href="{{ route('notifications', array_filter(['status' => request('status'), 'type' => 'warning'])) }}" 
               class="notif-pill {{ request('type') === 'warning' ? 'active' : '' }}">Warnings ({{ $counts['warnings'] ?? 0 }})</a>
            <a href="{{ route('notifications', array_filter(['status' => request('status'), 'type' => 'system'])) }}" 
               class="notif-pill {{ request('type') === 'system' ? 'active' : '' }}">System Updates</a>
        </div>
    </div>

    {{-- Notifications List --}}
    @php
        $getVisualDetails = function($type) {
            if ($type === 'system_update') {
                return [
                    'avatar_class' => 'type-avatar-system_update',
                    'icon' => 'bi-rocket-takeoff-fill',
                    'badge_class' => 'kind-badge-system',
                    'badge_label' => 'System Update'
                ];
            } elseif ($type === 'warning_3' || $type === 'warning_consecutive_3') {
                return [
                    'avatar_class' => 'type-avatar-warning_3',
                    'icon' => 'bi-exclamation-octagon-fill',
                    'badge_class' => 'kind-badge-critical',
                    'badge_label' => 'Critical Warning'
                ];
            } elseif ($type === 'warning_2' || str_contains($type, 'warning')) {
                return [
                    'avatar_class' => 'type-avatar-warning_2',
                    'icon' => 'bi-exclamation-triangle-fill',
                    'badge_class' => 'kind-badge-warning',
                    'badge_label' => 'Attendance Warning'
                ];
            } elseif ($type === 'absence') {
                return [
                    'avatar_class' => 'type-avatar-absence',
                    'icon' => 'bi-clipboard-x-fill',
                    'badge_class' => 'kind-badge-absence',
                    'badge_label' => 'Absence Recorded'
                ];
            } elseif (str_contains($type, 'excuse')) {
                return [
                    'avatar_class' => 'type-avatar-excuse',
                    'icon' => 'bi-file-earmark-medical-fill',
                    'badge_class' => 'kind-badge-warning',
                    'badge_label' => 'Excuse Update'
                ];
            } else {
                return [
                    'avatar_class' => 'type-avatar-custom',
                    'icon' => 'bi-info-circle-fill',
                    'badge_class' => 'kind-badge-notice',
                    'badge_label' => 'Notice'
                ];
            }
        };
    @endphp

    <div class="notif-list" id="notifListContainer">
        @forelse($notifications as $notification)
            @php 
                $vis = $getVisualDetails($notification->type); 
                $isArchived = $notification->isArchived();
                $isUnread = !$notification->is_read;
            @endphp
            <div class="notif-item-card {{ $isUnread ? 'unread' : '' }} {{ $isArchived ? 'archived-card' : '' }}" 
                 id="notifCard_{{ $notification->id }}" 
                 data-notif-id="{{ $notification->id }}"
                 data-is-unread="{{ $isUnread ? 'true' : 'false' }}">
                <div class="notif-card-inner">
                    {{-- Avatar Icon --}}
                    <div class="notif-type-avatar {{ $vis['avatar_class'] }}" aria-hidden="true">
                        <i class="bi {{ $vis['icon'] }}" aria-hidden="true"></i>
                    </div>

                    {{-- Main Content --}}
                    <div class="notif-card-main">
                        <div class="notif-top-row">
                            <div class="notif-badges-left">
                                <span class="notif-kind-badge {{ $vis['badge_class'] }}">{{ $vis['badge_label'] }}</span>
                                @if($isUnread)
                                    <span class="notif-unread-dot" id="itemUnreadBadge_{{ $notification->id }}"><span aria-hidden="true">●</span> Unread</span>
                                @endif
                                @if($notification->subject_code)
                                    <span class="notif-meta-item">
                                        <i class="bi bi-book-fill" style="color:#D4AF37;" aria-hidden="true"></i>
                                        {{ $notification->subject_code }}
                                    </span>
                                @endif
                            </div>
                            <span class="notif-timestamp" title="{{ $notification->created_at->format('M d, Y h:i A') }}">
                                {{ $notification->created_at->diffForHumans() }} · {{ $notification->created_at->format('M j, g:i A') }}
                            </span>
                        </div>

                        <div class="notif-message-text">
                            {{ $notification->message }}
                        </div>

                        @if($notification->subject || $notification->sender)
                        <div class="notif-meta-tags">
                            @if($notification->subject)
                                <span class="notif-meta-item">
                                    <i class="bi bi-mortarboard" aria-hidden="true"></i>
                                    {{ $notification->subject->name }}
                                </span>
                            @endif
                            @if($notification->sender)
                                <span class="notif-meta-item">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                    From {{ $notification->sender->name }}
                                </span>
                            @endif
                        </div>
                        @endif

                        {{-- Action Buttons --}}
                        <div class="notif-actions-row">
                            {{-- For Absences or Warnings: Provide Direct Excuse / Record links --}}
                            @if($notification->type === 'absence' || str_contains($notification->type, 'warning'))
                                <a href="{{ route('excuses') }}" class="btn-notif-action btn-notif-excuse">
                                    <i class="bi bi-file-earmark-medical" aria-hidden="true"></i> Submit Excuse
                                </a>
                                <a href="{{ route('attendance.records') }}" class="btn-notif-action btn-notif-records">
                                    <i class="bi bi-clock-history" aria-hidden="true"></i> View Records
                                </a>
                            @endif

                            {{-- Mark as Read Button (if unread) --}}
                            @if($isUnread)
                                <button type="button" 
                                        class="btn-notif-action btn-notif-read" 
                                        id="btnMarkRead_{{ $notification->id }}"
                                        onclick="markSingleNotificationRead({{ $notification->id }})">
                                    <i class="bi bi-check2" aria-hidden="true"></i> Mark as Read
                                </button>
                            @endif

                            {{-- Archive / Unarchive Button --}}
                            @if($isArchived)
                                <button type="button" 
                                        class="btn-notif-action btn-notif-unarchive" 
                                        onclick="unarchiveNotification({{ $notification->id }})">
                                    <i class="bi bi-arrow-up-circle" aria-hidden="true"></i> Restore
                                </button>
                            @else
                                <button type="button" 
                                        class="btn-notif-action btn-notif-archive" 
                                        onclick="archiveNotification({{ $notification->id }})">
                                    <i class="bi bi-archive" aria-hidden="true"></i> Archive
                                </button>
                            @endif

                            {{-- Delete Button --}}
                            <button type="button" 
                                    class="btn-notif-action btn-notif-delete" 
                                    title="Delete notification"
                                    aria-label="Delete notification"
                                    onclick="deleteNotification({{ $notification->id }})">
                                <i class="bi bi-trash3-fill" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="notif-empty-state">
                <div class="notif-empty-icon">
                    <i class="bi bi-bell-slash"></i>
                </div>
                <div class="notif-empty-title">
                    @if(request('status') === 'unread')
                        All caught up!
                    @elseif(request('status') === 'archived')
                        No archived notifications
                    @elseif(request('search'))
                        No notifications match "{{ request('search') }}"
                    @else
                        No notifications found
                    @endif
                </div>
                <div class="notif-empty-desc">
                    @if(request('status') === 'unread')
                        You have read all your alerts and notifications. New updates will appear here in real-time.
                    @elseif(request('status') === 'archived')
                        Notifications you archive will be saved here for your reference.
                    @else
                        Attendance warnings, absence notices, and system alerts will automatically be posted here.
                    @endif
                </div>
                @if(request()->hasAny(['status', 'type', 'search']))
                    <a href="{{ route('notifications') }}" class="btn-notif-action btn-notif-records" style="display:inline-flex;">
                        <i class="bi bi-arrow-left"></i> View All Active Notifications
                    </a>
                @else
                    <a href="{{ route('home') }}" class="btn-notif-action btn-notif-records" style="display:inline-flex;">
                        <i class="bi bi-grid-fill"></i> Return to Dashboard
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($notifications->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════
     INTERACTIVE JAVASCRIPT — CSP NONCE PROTECTED
     ══════════════════════════════════════════════════════════════ --}}
<script nonce="{{ csp_nonce() }}">
(function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    function triggerHapticFeedback(type = 'light') {
        if (typeof window.triggerHaptic === 'function') {
            window.triggerHaptic(type);
        }
    }

    function parseJsonResponse(res) {
        return res.json().catch(() => null).then(data => {
            if (!res.ok || !data || !data.success) {
                throw new Error((data && data.message) || ('Request failed (' + res.status + ')'));
            }
            return data;
        });
    }

    function notifyActionError(message) {
        if (typeof showPremiumToast === 'function') {
            showPremiumToast(message || 'Unable to update notification. Please try again.', 'error');
        }
    }

    function adjustTabCount(elementId, delta) {
        const el = document.getElementById(elementId);
        if (!el) return;
        const cur = parseInt(el.textContent, 10) || 0;
        el.textContent = String(Math.max(0, cur + delta));
    }

    function removeAfterTransition(card, callback) {
        const delay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 250;
        setTimeout(() => {
            card.remove();
            if (typeof callback === 'function') callback();
        }, delay);
    }

    function updateBadgeElements(unreadCount) {
        const count = Math.max(0, Number(unreadCount) || 0);
        const heroPill = document.getElementById('heroUnreadPill');
        const markAllBtn = document.getElementById('btnMarkAllRead');
        const statUnread = document.getElementById('statUnreadCount');
        const tabUnread = document.getElementById('tabCountUnread');

        if (tabUnread) tabUnread.textContent = String(count);

        if (count <= 0) {
            if (heroPill) heroPill.style.display = 'none';
            if (markAllBtn) markAllBtn.disabled = true;
            if (statUnread) {
                statUnread.textContent = '0';
                statUnread.style.color = '#ffffff';
            }
        } else {
            if (heroPill) {
                heroPill.style.display = 'inline-flex';
                heroPill.textContent = count + ' NEW';
            }
            if (markAllBtn) markAllBtn.disabled = false;
            if (statUnread) {
                statUnread.textContent = String(count);
                statUnread.style.color = '#f87171';
            }
        }

        // Also sync top navbar bell
        if (typeof window.refreshNotificationBell === 'function') {
            window.refreshNotificationBell();
        } else {
            const topNavBadge = document.getElementById('topNavNotifBadge');
            if (topNavBadge) {
                if (count <= 0) topNavBadge.remove();
                else topNavBadge.textContent = count > 9 ? '9+' : String(count);
            }
        }
    }

    // Mark single notification as read
    window.markSingleNotificationRead = function(id) {
        triggerHapticFeedback('light');
        const card = document.getElementById('notifCard_' + id);
        const readBtn = document.getElementById('btnMarkRead_' + id);
        const itemBadge = document.getElementById('itemUnreadBadge_' + id);

        fetch('/notifications/' + id + '/read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(parseJsonResponse)
        .then(data => {
            if (card) {
                card.classList.remove('unread');
                card.setAttribute('data-is-unread', 'false');
            }
            if (readBtn) readBtn.remove();
            if (itemBadge) itemBadge.remove();
            if (typeof data.unread_count !== 'undefined') {
                updateBadgeElements(data.unread_count);
            } else {
                adjustTabCount('tabCountUnread', -1);
            }
            if (typeof showPremiumToast === 'function') {
                showPremiumToast('Marked as read', 'success');
            }
        })
        .catch(err => {
            console.error('Error marking read:', err);
            notifyActionError(err.message);
        });
    };

    // Mark all notifications as read
    window.markAllNotificationsRead = function() {
        triggerHapticFeedback('medium');
        const markAllBtn = document.getElementById('btnMarkAllRead');
        const origBtnHtml = markAllBtn ? markAllBtn.innerHTML : '<i class="bi bi-check2-all"></i> Mark all read';
        if (markAllBtn) {
            markAllBtn.disabled = true;
            markAllBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Marking...';
        }

        fetch('{{ route("notifications.read") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(parseJsonResponse)
        .then(() => {
            document.querySelectorAll('.notif-item-card.unread').forEach(card => {
                card.classList.remove('unread');
                card.setAttribute('data-is-unread', 'false');
            });
            document.querySelectorAll('[id^="btnMarkRead_"]').forEach(btn => btn.remove());
            document.querySelectorAll('[id^="itemUnreadBadge_"]').forEach(badge => badge.remove());

            updateBadgeElements(0);

            if (markAllBtn) {
                markAllBtn.innerHTML = '<i class="bi bi-check2-all"></i> All Read';
            }
            if (typeof showPremiumToast === 'function') {
                showPremiumToast('All notifications marked as read', 'success');
            }
        })
        .catch(err => {
            console.error('Error marking all read:', err);
            if (markAllBtn) {
                markAllBtn.disabled = false;
                markAllBtn.innerHTML = origBtnHtml;
            }
            notifyActionError(err.message);
        });
    };

    // Archive notification
    window.archiveNotification = function(id) {
        triggerHapticFeedback('light');
        const card = document.getElementById('notifCard_' + id);
        if (!card) return;

        card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        card.style.opacity = '0';
        card.style.transform = 'translateX(20px)';

        fetch('/notifications/' + id + '/archive', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(parseJsonResponse)
        .then(data => {
            removeAfterTransition(card, () => {
                if (typeof data.unread_count !== 'undefined') {
                    updateBadgeElements(data.unread_count);
                }
                adjustTabCount('tabCountActive', -1);
                adjustTabCount('tabCountArchived', 1);
                adjustTabCount('statActiveCount', -1);
                adjustTabCount('statArchivedCount', 1);
            });
            if (typeof showPremiumToast === 'function') {
                showPremiumToast('Notification archived', 'success');
            }
        })
        .catch(err => {
            console.error('Error archiving:', err);
            card.style.opacity = '1';
            card.style.transform = 'none';
            notifyActionError(err.message);
        });
    };

    // Unarchive notification
    window.unarchiveNotification = function(id) {
        triggerHapticFeedback('light');
        const card = document.getElementById('notifCard_' + id);
        if (!card) return;

        card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        card.style.opacity = '0';
        card.style.transform = 'translateX(-20px)';

        fetch('/notifications/' + id + '/unarchive', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(parseJsonResponse)
        .then(data => {
            removeAfterTransition(card, () => {
                if (typeof data.unread_count !== 'undefined') {
                    updateBadgeElements(data.unread_count);
                }
                adjustTabCount('tabCountActive', 1);
                adjustTabCount('tabCountArchived', -1);
                adjustTabCount('statActiveCount', 1);
                adjustTabCount('statArchivedCount', -1);
            });
            if (typeof showPremiumToast === 'function') {
                showPremiumToast('Notification restored to active', 'success');
            }
        })
        .catch(err => {
            console.error('Error unarchiving:', err);
            card.style.opacity = '1';
            card.style.transform = 'none';
            notifyActionError(err.message);
        });
    };

    // Delete notification (2-click inline confirmation replacing browser alert/confirm)
    window.deleteNotification = function(id) {
        const card = document.getElementById('notifCard_' + id);
        if (!card) return;
        const delBtn = card.querySelector('.btn-notif-delete');
        const resetDeleteBtn = () => {
            if (!delBtn) return;
            delete delBtn.dataset.confirmPending;
            if (delBtn.dataset.origHtml) {
                delBtn.innerHTML = delBtn.dataset.origHtml;
            }
            delBtn.style.width = '';
            delBtn.style.padding = '';
            delBtn.style.background = '';
            delBtn.style.color = '';
            delBtn.style.borderColor = '';
        };
        if (delBtn && delBtn.dataset.confirmPending !== '1') {
            delBtn.dataset.confirmPending = '1';
            delBtn.dataset.origHtml = delBtn.innerHTML;
            delBtn.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i><span style="font-size:0.74rem;font-weight:700;margin-left:4px;">Confirm</span>';
            delBtn.style.width = 'auto';
            delBtn.style.padding = '0 10px';
            delBtn.style.background = 'rgba(239, 68, 68, 0.25)';
            delBtn.style.color = '#fca5a5';
            delBtn.style.borderColor = 'rgba(239, 68, 68, 0.5)';
            clearTimeout(delBtn._confirmTimer);
            delBtn._confirmTimer = setTimeout(resetDeleteBtn, 3500);
            return;
        }
        if (delBtn) {
            clearTimeout(delBtn._confirmTimer);
            delete delBtn.dataset.confirmPending;
        }
        triggerHapticFeedback('heavy');

        const isArchivedView = new URLSearchParams(window.location.search).get('status') === 'archived';
        card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.95)';

        fetch('/notifications/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(parseJsonResponse)
        .then(data => {
            removeAfterTransition(card, () => {
                if (typeof data.unread_count !== 'undefined') {
                    updateBadgeElements(data.unread_count);
                }
                if (isArchivedView) {
                    adjustTabCount('tabCountArchived', -1);
                    adjustTabCount('statArchivedCount', -1);
                } else {
                    adjustTabCount('tabCountActive', -1);
                    adjustTabCount('statActiveCount', -1);
                }
            });
            if (typeof showPremiumToast === 'function') {
                showPremiumToast('Notification deleted', 'success');
            }
        })
        .catch(err => {
            console.error('Error deleting:', err);
            card.style.opacity = '1';
            card.style.transform = 'none';
            resetDeleteBtn();
            notifyActionError(err.message);
        });
    };

    // Client-side instant filter on search input keyup
    const searchInput = document.getElementById('notifSearchInput');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.toLowerCase().trim();
            debounceTimer = setTimeout(() => {
                const cards = document.querySelectorAll('.notif-item-card');
                let visibleCount = 0;
                cards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    if (!query || text.includes(query)) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
            }, 150);
        });
    }
})();
</script>
@endsection