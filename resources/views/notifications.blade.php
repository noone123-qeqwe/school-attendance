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
    @php
        $notifFilterCount = (filled(request('search')) ? 1 : 0) + (filled(request('type')) ? 1 : 0);
    @endphp
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

            {{-- Inline Search & Filter Trigger (Desktop/Tablet same row, Mobile wraps cleanly) --}}
            <button type="button"
                    class="sd-notif-filter-toggle"
                    id="notifFilterToggleBtn"
                    aria-expanded="{{ $notifFilterCount > 0 ? 'true' : 'false' }}"
                    aria-controls="notifFilterPanel">
                <i class="bi bi-sliders" aria-hidden="true"></i>
                <span>Search &amp; Filter</span>
                @if($notifFilterCount > 0)
                    <span class="sd-disclosure-pill is-accent">{{ $notifFilterCount }} active</span>
                @endif
                <i class="bi bi-chevron-down sd-disclosure-chevron" aria-hidden="true"></i>
            </button>
        </div>

        {{-- Progressive Search & Category Filter Drawer --}}
        <div class="sd-filter-disclosure-body notif-filter-panel notif-filter-disclosure" id="notifFilterPanel" @if($notifFilterCount === 0) hidden @endif>
            <form method="GET" action="{{ route('notifications') }}" class="notif-search-box mb-3" id="notifSearchForm">
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

                        {{-- Action Buttons (Primary upfront + overflow menu for secondary) --}}
                        <div class="notif-actions-row">
                            {{-- Primary contextual CTA for Absences or Warnings --}}
                            @if($notification->type === 'absence' || str_contains($notification->type, 'warning'))
                                <a href="{{ route('excuses') }}" class="btn-notif-action btn-notif-excuse">
                                    <i class="bi bi-file-earmark-medical" aria-hidden="true"></i> Submit Excuse
                                </a>
                            @endif

                            {{-- Primary Mark as Read Button (if unread) --}}
                            @if($isUnread)
                                <button type="button" 
                                        class="btn-notif-action btn-notif-read" 
                                        id="btnMarkRead_{{ $notification->id }}"
                                        onclick="markSingleNotificationRead({{ $notification->id }})">
                                    <i class="bi bi-check2" aria-hidden="true"></i> Mark as Read
                                </button>
                            @endif

                            {{-- Secondary Actions Popover --}}
                            <details class="sd-action-menu">
                                <summary aria-label="More notification actions" title="More actions">
                                    <i class="bi bi-three-dots" aria-hidden="true"></i>
                                </summary>
                                <div class="sd-action-menu-popover">
                                    @if($notification->type === 'absence' || str_contains($notification->type, 'warning'))
                                        <a href="{{ route('attendance.records') }}" class="sd-action-menu-item">
                                            <i class="bi bi-clock-history" aria-hidden="true"></i> View Records
                                        </a>
                                    @endif

                                    @if($isArchived)
                                        <button type="button" 
                                                class="sd-action-menu-item" 
                                                onclick="unarchiveNotification({{ $notification->id }})">
                                            <i class="bi bi-arrow-up-circle" aria-hidden="true"></i> Restore
                                        </button>
                                    @else
                                        <button type="button" 
                                                class="sd-action-menu-item" 
                                                onclick="archiveNotification({{ $notification->id }})">
                                            <i class="bi bi-archive" aria-hidden="true"></i> Archive
                                        </button>
                                    @endif

                                    <button type="button" 
                                            class="sd-action-menu-item is-danger btn-notif-delete" 
                                            aria-label="Delete notification"
                                            onclick="deleteNotification({{ $notification->id }})">
                                        <i class="bi bi-trash3-fill" aria-hidden="true"></i> Delete
                                    </button>
                                </div>
                            </details>
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
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduced) {
            card.remove();
            if (typeof callback === 'function') callback();
            return;
        }
        const rect = card.getBoundingClientRect();
        card.style.overflow = 'hidden';
        card.style.maxHeight = rect.height + 'px';
        card.getBoundingClientRect();
        card.style.transition = 'opacity 180ms cubic-bezier(0.23, 1, 0.32, 1), transform 200ms cubic-bezier(0.23, 1, 0.32, 1), max-height 240ms cubic-bezier(0.23, 1, 0.32, 1), margin-bottom 240ms cubic-bezier(0.23, 1, 0.32, 1), padding-top 240ms cubic-bezier(0.23, 1, 0.32, 1), padding-bottom 240ms cubic-bezier(0.23, 1, 0.32, 1)';
        card.style.maxHeight = '0px';
        card.style.marginBottom = '0px';
        card.style.paddingTop = '0px';
        card.style.paddingBottom = '0px';
        setTimeout(() => {
            card.remove();
            if (typeof callback === 'function') callback();
        }, 250);
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
            delBtn.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i><span>Confirm delete</span>';
            delBtn.style.background = 'rgba(239, 68, 68, 0.24)';
            delBtn.style.color = '#fee2e2';
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

    // Progressive Search & Filter drawer toggle
    const filterToggleBtn = document.getElementById('notifFilterToggleBtn');
    const filterPanel = document.getElementById('notifFilterPanel');
    if (filterToggleBtn && filterPanel) {
        filterToggleBtn.addEventListener('click', function () {
            const willOpen = filterPanel.hidden;
            filterPanel.hidden = !willOpen;
            filterToggleBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            filterToggleBtn.classList.toggle('is-open', willOpen);
            if (willOpen) {
                document.getElementById('notifSearchInput')?.focus({ preventScroll: true });
            }
        });
    }

    // Smart overflow popover placement (flips upward above fixed mobile bottom nav or viewport floor)
    function positionActionMenu(menu) {
        if (!menu.open) {
            menu.classList.remove('is-drop-up');
            return;
        }
        const summary = menu.querySelector('summary');
        const popover = menu.querySelector('.sd-action-menu-popover');
        if (!summary || !popover) return;
        const summaryRect = summary.getBoundingClientRect();
        const popoverHeight = popover.offsetHeight || 156;
        const bottomNav = document.querySelector('#mobileBottomNav, .mobile-bottom-nav');
        const navStyle = bottomNav ? getComputedStyle(bottomNav) : null;
        const navClearance = (bottomNav && navStyle && navStyle.display !== 'none' && navStyle.visibility !== 'hidden')
            ? (bottomNav.getBoundingClientRect().height + 16)
            : 16;
        const spaceBelow = window.innerHeight - navClearance - summaryRect.bottom;
        const spaceAbove = summaryRect.top - 72;
        menu.classList.toggle('is-drop-up', spaceBelow < popoverHeight + 8 && spaceAbove > spaceBelow);
    }

    document.querySelectorAll('details.sd-action-menu').forEach(menu => {
        menu.addEventListener('toggle', function () {
            if (menu.open) {
                document.querySelectorAll('details.sd-action-menu[open]').forEach(other => {
                    if (other !== menu) other.open = false;
                });
            }
            positionActionMenu(menu);
        });
    });

    window.addEventListener('resize', function () {
        document.querySelectorAll('details.sd-action-menu[open]').forEach(positionActionMenu);
    }, { passive: true });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('details.sd-action-menu')) {
            document.querySelectorAll('details.sd-action-menu[open]').forEach(menu => { menu.open = false; });
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('details.sd-action-menu[open]').forEach(menu => {
                menu.open = false;
                menu.querySelector('summary')?.focus({ preventScroll: true });
            });
        }
    });

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