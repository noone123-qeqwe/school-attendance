@props(['parentUnreadCount' => null])
{{-- ═════════════════════════════════════════════════════════════════
     COMMAND PALETTE — Redesigned Quick Navigation & Spotlight
     Ultra-sleek, responsive mobile sheet + desktop command center
     ═════════════════════════════════════════════════════════════════ --}}

<div class="cmd-palette-overlay" id="cmdPaletteOverlay" role="dialog" aria-modal="true" aria-label="Command Palette">
    <div class="cmd-palette" id="cmdPalette">
        {{-- Mobile drag handle / grabber --}}
        <div class="cmd-palette-grabber d-md-none" id="cmdPaletteGrabber"></div>

        {{-- Top Search Bar --}}
        <div class="cmd-palette-header">
            <div class="cmd-palette-search-box">
                <i class="bi bi-search cmd-palette-search-icon" aria-hidden="true"></i>
                <input type="text" class="cmd-palette-input" id="cmdPaletteInput"
                       placeholder="Search pages, actions..."
                       autocomplete="off" spellcheck="false"
                       aria-label="Search pages and actions">
                <button type="button" class="cmd-palette-clear-btn" id="cmdPaletteClearBtn" style="display:none;" title="Clear search" aria-label="Clear search">
                    <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                </button>
            </div>
            <kbd class="cmd-palette-kbd d-none d-md-inline-flex" title="Press Escape to close">ESC</kbd>
            <button type="button" class="cmd-palette-close-btn" id="cmdPaletteCloseBtn" aria-label="Close command palette" title="Close (ESC)">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        {{-- Filter Category Pills --}}
        <div class="cmd-palette-categories" id="cmdPaletteCategories" role="group" aria-label="Filter command palette">
            <button type="button" class="cmd-cat-pill active" data-cat="all" aria-pressed="true">
                <i class="bi bi-stars" aria-hidden="true"></i> All
            </button>
            <button type="button" class="cmd-cat-pill" data-cat="action" aria-pressed="false">
                <i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> Actions
            </button>
            <button type="button" class="cmd-cat-pill" data-cat="navigation" aria-pressed="false">
                <i class="bi bi-compass-fill" aria-hidden="true"></i> Navigation
            </button>
            <button type="button" class="cmd-cat-pill" data-cat="academic" aria-pressed="false">
                <i class="bi bi-mortarboard-fill" aria-hidden="true"></i> Academic
            </button>
            <button type="button" class="cmd-cat-pill" data-cat="system" aria-pressed="false">
                <i class="bi bi-gear-fill" aria-hidden="true"></i> System
            </button>
        </div>

        {{-- Palette Body --}}
        <div class="cmd-palette-body" id="cmdPaletteBody">
            <span id="cmdPaletteLiveStatus" class="cmd-sr-only" role="status" aria-live="polite" aria-atomic="true"></span>

            {{-- Quick Access Chips (shown when no search query) --}}
            <div class="cmd-quick-chips-wrap" id="cmdQuickChipsWrap">
                <div class="cmd-quick-chips-label">
                    <i class="bi bi-lightning-fill" aria-hidden="true"></i> Quick Access
                </div>
                <div class="cmd-quick-chips-row" id="cmdQuickChipsRow">
                    <!-- populated dynamically -->
                </div>
            </div>

            {{-- Recent Searches --}}
            <div class="cmd-palette-group" id="cmdPaletteRecent" style="display:none;">
                <div class="cmd-palette-group-header">
                    <div class="cmd-palette-group-label">
                        <i class="bi bi-clock-history" aria-hidden="true"></i> Recent
                    </div>
                    <button type="button" class="cmd-palette-clear-recent" id="cmdClearRecentBtn">Clear</button>
                </div>
                <div id="cmdPaletteRecentItems" class="cmd-palette-items-list"></div>
            </div>

            {{-- Main Navigation Group --}}
            <div class="cmd-palette-group" id="cmdPaletteMainGroup">
                <div class="cmd-palette-group-header">
                    <div class="cmd-palette-group-label" id="cmdPaletteGroupLabel">
                        <i class="bi bi-grid-1x2" aria-hidden="true"></i> All Pages &amp; Actions
                    </div>
                    <span class="cmd-palette-count-badge" id="cmdPaletteCountBadge"></span>
                </div>
                <div id="cmdPaletteItems" class="cmd-palette-items-list"></div>
            </div>

            {{-- Empty State --}}
            <div class="cmd-palette-empty" id="cmdPaletteEmpty" style="display:none;">
                <div class="cmd-empty-icon" aria-hidden="true">
                    <i class="bi bi-search"></i>
                </div>
                <div class="cmd-empty-title" id="cmdEmptyTitle">No matching pages or actions found</div>
                <div class="cmd-empty-desc" id="cmdEmptyDesc">Try keywords like "classes", "schedule", "code", or "records"</div>
                <div class="cmd-empty-actions" id="cmdEmptyActions" style="display:none;">
                    <button type="button" class="cmd-empty-reset-btn" id="cmdEmptyClearBtn" style="display:none;">Clear search</button>
                    <button type="button" class="cmd-empty-reset-btn" id="cmdEmptyAllCatBtn" style="display:none;">Show All Categories</button>
                </div>
            </div>
        </div>

        {{-- Desktop Footer --}}
        <div class="cmd-palette-footer d-none d-md-flex">
            <div class="cmd-palette-footer-hints">
                <span><kbd>↑</kbd><kbd>↓</kbd> Navigate</span>
                <span><kbd>↵</kbd> Select</span>
                <span><kbd>ESC</kbd> Close</span>
            </div>
            <div class="cmd-palette-footer-brand">
                <i class="bi bi-shield-check" aria-hidden="true"></i> Smart Attendance Command
            </div>
        </div>
    </div>
</div>

@once
<link rel="stylesheet" href="{{ asset('css/command-palette.css') }}?v={{ filemtime(public_path('css/command-palette.css')) }}">
@endonce

<script nonce="{{ csp_nonce() }}">
(function() {
    'use strict';

    // ── Role-aware Navigation & Actions ──
    var navItems = [];
    var quickActionChips = [];

    @auth
        @php
            if (Auth::user()->isParent()) {
                $unreadNotifsCount = $parentUnreadCount ?? \App\Support\ParentNavigation::unreadCount(Auth::user());
            } else {
                $unreadNotifsCount = \App\Models\Notification::where('user_id', Auth::id())->where('is_read', false)->count();
            }
            $notifBadgeText = $unreadNotifsCount > 0 ? ($unreadNotifsCount > 9 ? '9+ New' : $unreadNotifsCount . ' New') : 'Alerts';
        @endphp

        @if(Auth::user()->isAdmin())
            navItems = @json(\App\Support\AdminNavigation::searchItems(Auth::user()));
            quickActionChips = [
                { label: 'Students', icon: 'bi-people', url: '{{ route("admin.students") }}' },
                { label: 'Class schedules', icon: 'bi-calendar3', url: '{{ route("admin.class-schedules.index") }}' },
                { label: 'Attendance', icon: 'bi-clipboard-check', url: '{{ route("admin.attendance") }}' },
                { label: 'Reports', icon: 'bi-bar-chart', url: '{{ route("admin.reports") }}' }
            ];
        @elseif(Auth::user()->isTeacher())
            navItems = [
                { id: 'dashboard', label: 'Dashboard', hint: 'Teacher command canvas & live classes', icon: 'bi-grid-fill', color: '#ffd166', bg: 'rgba(255, 209, 102, 0.12)', category: 'navigation', badge: 'Home', url: '{{ route("teacher.dashboard") }}' },
                { id: 'qr', label: 'Live QR Attendance', hint: 'Choose a class & open attendance QR', icon: 'bi-qr-code-scan', color: '#34d399', bg: 'rgba(52, 211, 153, 0.12)', category: 'action', badge: 'Live', url: '{{ route("teacher.classroom.index", ["task" => "attendance"]) }}' },
                { id: 'classroom', label: 'My Classes', hint: 'Classes, rosters, attendance records & sessions', icon: 'bi-journal-bookmark-fill', color: '#38bdf8', bg: 'rgba(56, 189, 248, 0.12)', category: 'academic', badge: 'Classes', url: '{{ route("teacher.classroom.index") }}' },
                { id: 'attendance', label: 'Class Attendance Records', hint: 'Choose a class to manage attendance records', icon: 'bi-clipboard-check-fill', color: '#fde68a', bg: 'rgba(253, 230, 138, 0.12)', category: 'academic', badge: 'Records', url: '{{ route("teacher.classroom.index", ["task" => "records"]) }}' },
                { id: 'excuses', label: 'Excuse Reviews', hint: 'Review submitted student excuse letters', icon: 'bi-file-earmark-text-fill', color: '#f59e0b', bg: 'rgba(245, 158, 11, 0.12)', category: 'action', badge: 'Reviews', url: '{{ route("teacher.excuse.reviews") }}' },
                { id: 'corrections', label: 'Correction Requests', hint: 'Review student attendance adjustments', icon: 'bi-pencil-square', color: '#fb923c', bg: 'rgba(251, 146, 60, 0.12)', category: 'action', badge: 'Adjustments', url: '{{ route("teacher.corrections") }}' },
                { id: 'absent', label: 'Absent Report', hint: 'Review missed classes and send warnings', icon: 'bi-person-x-fill', color: '#f87171', bg: 'rgba(248, 113, 113, 0.12)', category: 'academic', badge: 'Report', url: '{{ route("teacher.absent") }}' },
                { id: 'students', label: 'Student Record', hint: 'Browse enrolled student profiles & notes', icon: 'bi-people-fill', color: '#60a5fa', bg: 'rgba(96, 165, 250, 0.12)', category: 'academic', badge: 'Directory', url: '{{ route("teacher.students") }}' },
                { id: 'reports', label: 'Attendance Reports', hint: 'Generate PDF, CSV & Excel exports', icon: 'bi-graph-up-arrow', color: '#2dd4bf', bg: 'rgba(45, 212, 191, 0.12)', category: 'academic', badge: 'Analytics', url: '{{ route("teacher.reports") }}' },
                { id: 'calendar', label: 'School Calendar', hint: 'Academic holidays, exams & events', icon: 'bi-calendar-event-fill', color: '#4ade80', bg: 'rgba(74, 222, 128, 0.12)', category: 'navigation', badge: 'Calendar', url: '{{ route("teacher.calendar") }}' },
                { id: 'my-excuses', label: 'My Excuse Requests', hint: 'Submit or track faculty excuse requests', icon: 'bi-file-earmark-person-fill', color: '#a78bfa', bg: 'rgba(167, 139, 250, 0.12)', category: 'navigation', badge: 'Excuses', url: '{{ route("teacher.excuses") }}' },
                { id: 'notifications', label: 'Notifications', hint: 'Alerts and system updates', icon: 'bi-bell-fill', color: '#fb7185', bg: 'rgba(251, 113, 133, 0.12)', category: 'navigation', badge: '{{ $notifBadgeText }}', url: '{{ route("teacher.notifications") }}' },
                { id: 'profile', label: 'My Profile', hint: 'Account credentials, passkeys & settings', icon: 'bi-person-circle', color: '#cbd5e1', bg: 'rgba(203, 213, 225, 0.12)', category: 'system', badge: 'Account', url: '{{ route("teacher.profile") }}' }
            ];
            quickActionChips = [
                { label: 'Live QR', icon: 'bi-qr-code-scan', url: '{{ route("teacher.classroom.index", ["task" => "attendance"]) }}' },
                { label: 'My Classes', icon: 'bi-journal-bookmark-fill', url: '{{ route("teacher.classroom.index") }}' },
                { label: 'Excuse Reviews', icon: 'bi-file-earmark-text-fill', url: '{{ route("teacher.excuse.reviews") }}' },
                { label: 'Corrections', icon: 'bi-pencil-square', url: '{{ route("teacher.corrections") }}' }
            ];
        @elseif(Auth::user()->role === 'parent')
            @php
                $parentPaletteChildId = request('child_id') ?: session(\App\Support\ParentNavigation::selectionKey(Auth::user()));
            @endphp
            navItems = @json(\App\Support\ParentNavigation::searchItems($parentPaletteChildId ? (int) $parentPaletteChildId : null));
            quickActionChips = [
                { label: 'Attendance & calendar', icon: 'bi-calendar-check', url: '{{ \App\Support\ParentNavigation::url("parent.calendar", $parentPaletteChildId ? (int) $parentPaletteChildId : null) }}' },
                { label: 'Schedule', icon: 'bi-clock', url: '{{ \App\Support\ParentNavigation::url("parent.schedule", $parentPaletteChildId ? (int) $parentPaletteChildId : null) }}' },
                { label: 'Submit Excuse', icon: 'bi-file-earmark-text-fill', url: '{{ route("parent.excuses.create_general", $parentPaletteChildId ? ["child_id" => $parentPaletteChildId] : []) }}' },
                { label: 'My children', icon: 'bi-people', url: '{{ route("parent.link.form") }}' }
            ];
        @else
            navItems = [
                { id: 'dashboard', label: 'Dashboard', hint: 'Student home & daily overview', icon: 'bi-grid-fill', color: '#D4AF37', bg: 'transparent', category: 'navigation', badge: 'Home', url: '{{ route("home") }}' },
                { id: 'code', label: 'Enter Attendance Code', hint: 'Enter 6-digit session PIN or scan QR', icon: 'bi-qr-code-scan', color: '#4ADE80', bg: 'transparent', category: 'action', badge: 'Check-in', action: 'openScanner', url: '{{ route("mobile.scan", ["open_code" => 1]) }}' },
                { id: 'classes', label: 'My Classes', hint: 'Enrolled subjects, instructors & rooms', icon: 'bi-folder-fill', color: '#7DD3FC', bg: 'transparent', category: 'academic', badge: 'Classes', url: '{{ route("student.classes") }}' },
                { id: 'schedule', label: 'My Schedule', hint: 'Weekly timetable & scheduled classes', icon: 'bi-calendar-week-fill', color: '#D4AF37', bg: 'transparent', category: 'academic', badge: 'Schedule', url: '{{ route("student.schedule") }}' },
                { id: 'calendar', label: 'Attendance & School Calendar', hint: 'Detailed attendance records, clock-in history, school events, and holidays', icon: 'bi-calendar-check-fill', color: '#FBBF24', bg: 'transparent', category: 'academic', badge: 'Calendar', url: '{{ route("student.calendar") }}' },
                { id: 'excuses', label: 'Excuse Submissions', hint: 'Submit absence excuse & check status', icon: 'bi-file-earmark-medical-fill', color: '#FBBF24', bg: 'transparent', category: 'action', badge: 'Excuses', url: '{{ route("excuses") }}' },
                { id: 'notifications', label: 'Notifications', hint: 'Class announcements & attendance warnings', icon: 'bi-bell-fill', color: '#F87171', bg: 'transparent', category: 'navigation', badge: '{{ $notifBadgeText }}', url: '{{ route("notifications") }}' },
                { id: 'settings', label: 'Settings', hint: 'Account preferences, security & profile', icon: 'bi-gear-fill', color: '#ACA099', bg: 'transparent', category: 'system', badge: 'Account', url: '{{ route("settings") }}' }
            ];
            quickActionChips = [
                { label: 'Check-in Code', icon: 'bi-qr-code-scan', action: 'openScanner', url: '{{ route("mobile.scan", ["open_code" => 1]) }}' },
                { label: 'My Schedule', icon: 'bi-calendar-week-fill', url: '{{ route("student.schedule") }}' },
                { label: 'View Records', icon: 'bi-clock-history', url: '{{ route("student.calendar") }}#attendance-records' },
                { label: 'Submit Excuse', icon: 'bi-file-earmark-medical-fill', url: '{{ route("excuses") }}' }
            ];
        @endif
    @endauth

    var overlay = document.getElementById('cmdPaletteOverlay');
    var palette = document.getElementById('cmdPalette');
    var input = document.getElementById('cmdPaletteInput');
    var clearBtn = document.getElementById('cmdPaletteClearBtn');
    var closeBtn = document.getElementById('cmdPaletteCloseBtn');
    var grabber = document.getElementById('cmdPaletteGrabber');
    var bodyEl = document.getElementById('cmdPaletteBody');
    var itemsContainer = document.getElementById('cmdPaletteItems');
    var mainGroup = document.getElementById('cmdPaletteMainGroup');
    var groupLabel = document.getElementById('cmdPaletteGroupLabel');
    var recentContainer = document.getElementById('cmdPaletteRecentItems');
    var recentGroup = document.getElementById('cmdPaletteRecent');
    var clearRecentBtn = document.getElementById('cmdClearRecentBtn');
    var quickChipsWrap = document.getElementById('cmdQuickChipsWrap');
    var quickChipsRow = document.getElementById('cmdQuickChipsRow');
    var categoriesWrap = document.getElementById('cmdPaletteCategories');
    var countBadge = document.getElementById('cmdPaletteCountBadge');
    var liveStatus = document.getElementById('cmdPaletteLiveStatus');
    var emptyState = document.getElementById('cmdPaletteEmpty');
    var emptyTitle = document.getElementById('cmdEmptyTitle');
    var emptyDesc = document.getElementById('cmdEmptyDesc');
    var emptyActions = document.getElementById('cmdEmptyActions');
    var emptyClearBtn = document.getElementById('cmdEmptyClearBtn');
    var emptyAllCatBtn = document.getElementById('cmdEmptyAllCatBtn');
    var activeIndex = -1;
    var currentCategory = 'all';
    var viewportRaf = null;
    var viewportListenersAttached = false;

    var categoryMeta = {
        all:        { icon: 'bi-grid-1x2',              label: 'All Pages & Actions', searchLabel: 'Search Results' },
        action:     { icon: 'bi-lightning-charge-fill', label: 'Actions',             searchLabel: 'Matching Actions' },
        navigation: { icon: 'bi-compass',               label: 'Navigation',          searchLabel: 'Matching Navigation' },
        academic:   { icon: 'bi-mortarboard-fill',      label: 'Academic',            searchLabel: 'Matching Academic' },
        system:     { icon: 'bi-gear-fill',             label: 'System',              searchLabel: 'Matching System' }
    };

    function setActiveCategoryPill(cat) {
        currentCategory = cat || 'all';
        if (!categoriesWrap) return;
        categoriesWrap.querySelectorAll('.cmd-cat-pill').forEach(function(el) {
            var isMatch = (el.getAttribute('data-cat') || 'all') === currentCategory;
            el.classList.toggle('active', isMatch);
            el.setAttribute('aria-pressed', isMatch ? 'true' : 'false');
            if (isMatch && typeof el.scrollIntoView === 'function') {
                try {
                    el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
                } catch (err) {
                    el.scrollIntoView(true);
                }
            }
        });
    }

    function syncCategoryPillsAvailability() {
        if (!categoriesWrap) return;
        var counts = { all: navItems.length };
        navItems.forEach(function(item) {
            var c = item.category || 'navigation';
            counts[c] = (counts[c] || 0) + 1;
        });
        categoriesWrap.querySelectorAll('.cmd-cat-pill').forEach(function(el) {
            var cat = el.getAttribute('data-cat') || 'all';
            el.style.display = (cat === 'all' || (counts[cat] || 0) > 0) ? '' : 'none';
        });
    }

    function updateGroupHeader(query, count) {
        var meta = categoryMeta[currentCategory] || categoryMeta.navigation;
        var heading = query ? meta.searchLabel : meta.label;
        if (groupLabel) {
            groupLabel.innerHTML = '<i class="bi ' + meta.icon + '" aria-hidden="true"></i> ' + escapeHtml(heading);
        }
        if (countBadge) {
            countBadge.textContent = count + (count === 1 ? ' result' : ' results');
        }
        if (liveStatus) {
            liveStatus.textContent = count === 0
                ? 'No matching pages or actions found in ' + meta.label
                : heading + ': ' + count + (count === 1 ? ' result' : ' results');
        }
    }

    function syncEmptyState(itemsCount, query) {
        if (!emptyState) return;
        var isEmpty = itemsCount === 0;
        if (mainGroup) {
            mainGroup.style.display = isEmpty ? 'none' : '';
        }
        emptyState.style.display = isEmpty ? 'flex' : 'none';
        if (!isEmpty) return;

        var meta = categoryMeta[currentCategory] || categoryMeta.all;
        if (emptyTitle) {
            emptyTitle.textContent = query
                ? 'No matches for "' + query + '"'
                : 'No items available in ' + meta.label;
        }
        if (emptyDesc) {
            emptyDesc.textContent = currentCategory !== 'all'
                ? 'Try clearing your search filter or switching back to All categories.'
                : 'Try keywords like "classes", "schedule", "code", or "records".';
        }
        var showClear = !!query;
        var showAllCat = currentCategory !== 'all';
        if (emptyClearBtn) emptyClearBtn.style.display = showClear ? '' : 'none';
        if (emptyAllCatBtn) emptyAllCatBtn.style.display = showAllCat ? '' : 'none';
        if (emptyActions) emptyActions.style.display = (showClear || showAllCat) ? 'flex' : 'none';
    }

    function syncViewportMetrics() {
        if (!overlay.classList.contains('active')) return;
        var vv = window.visualViewport;
        if (!vv) {
            overlay.style.removeProperty('--cmd-vv-height');
            overlay.style.removeProperty('--cmd-vv-top');
            overlay.style.removeProperty('--cmd-bottom-inset');
            return;
        }
        var vvHeight = Math.max(240, Math.round(vv.height));
        var vvTop = Math.max(0, Math.round(vv.offsetTop));
        var layoutHeight = window.innerHeight || document.documentElement.clientHeight || vvHeight;
        var keyboardOpen = (layoutHeight - vvHeight) > 120;
        overlay.style.setProperty('--cmd-vv-height', vvHeight + 'px');
        overlay.style.setProperty('--cmd-vv-top', vvTop + 'px');
        overlay.style.setProperty('--cmd-bottom-inset', keyboardOpen ? '8px' : 'max(12px, env(safe-area-inset-bottom, 12px))');
        document.body.classList.toggle('keyboard-open', keyboardOpen);
    }

    function scheduleViewportSync() {
        if (viewportRaf) return;
        viewportRaf = window.requestAnimationFrame(function() {
            viewportRaf = null;
            syncViewportMetrics();
        });
    }

    function attachViewportListeners() {
        syncViewportMetrics();
        if (viewportListenersAttached) return;
        viewportListenersAttached = true;
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', scheduleViewportSync);
            window.visualViewport.addEventListener('scroll', scheduleViewportSync);
        }
        window.addEventListener('resize', scheduleViewportSync);
    }

    function detachViewportListeners() {
        if (viewportRaf) {
            window.cancelAnimationFrame(viewportRaf);
            viewportRaf = null;
        }
        if (viewportListenersAttached) {
            viewportListenersAttached = false;
            if (window.visualViewport) {
                window.visualViewport.removeEventListener('resize', scheduleViewportSync);
                window.visualViewport.removeEventListener('scroll', scheduleViewportSync);
            }
            window.removeEventListener('resize', scheduleViewportSync);
        }
        overlay.style.removeProperty('--cmd-vv-height');
        overlay.style.removeProperty('--cmd-vv-top');
        overlay.style.removeProperty('--cmd-bottom-inset');
        document.body.classList.remove('keyboard-open');
    }

    // Populate Quick Action Chips
    function renderQuickChips() {
        if (!quickChipsRow) return;
        quickChipsRow.innerHTML = '';
        quickActionChips.forEach(function(chip) {
            var el = document.createElement('a');
            el.href = chip.url;
            el.className = 'cmd-quick-chip';
            el.innerHTML = '<i class="bi ' + chip.icon + '" style="color:#ffd166;"></i><span>' + chip.label + '</span>';
            el.addEventListener('click', function(e) {
                if (chip.action === 'openScanner' && typeof window.openStudentScanner === 'function') {
                    e.preventDefault();
                    closePalette();
                    window.openStudentScanner('code');
                    return;
                }
                saveRecent({ label: chip.label, hint: 'Quick action', icon: chip.icon, color: '#ffd166', bg: 'rgba(255,209,102,0.12)', url: chip.url });
            });
            quickChipsRow.appendChild(el);
        });
    }

    // Render items with highlight and rich badge
    function renderItems(items, container, query) {
        container.innerHTML = '';

        items.forEach(function(item, i) {
            var a = document.createElement('a');
            a.href = item.url;
            a.className = 'cmd-palette-item';
            a.setAttribute('data-index', i);

            var labelHtml = query ? highlightMatch(item.label, query) : escapeHtml(item.label);
            var hintHtml = query ? highlightMatch(item.hint, query) : escapeHtml(item.hint);
            var itemColor = item.color || '#ffd166';
            var itemBg = item.bg || 'rgba(255, 209, 102, 0.12)';
            var itemBadge = item.badge ? '<span class="cmd-palette-item-badge">' + escapeHtml(item.badge) + '</span>' : '';

            a.innerHTML = `
                <div class="cmd-palette-item-icon" style="background:${itemBg}; color:${itemColor}; border-color:${itemColor}33;">
                    <i class="bi ${item.icon}"></i>
                </div>
                <div class="cmd-palette-item-text">
                    <div class="cmd-palette-item-label-row">
                        <span class="cmd-palette-item-label">${labelHtml}</span>
                        ${itemBadge}
                    </div>
                    <div class="cmd-palette-item-hint">${hintHtml}</div>
                </div>
                <i class="bi bi-arrow-right-short cmd-palette-item-arrow"></i>
            `;

            a.addEventListener('click', function(e) {
                if (item.action === 'openScanner' && typeof window.openStudentScanner === 'function') {
                    e.preventDefault();
                    closePalette();
                    window.openStudentScanner('code');
                    return;
                }
                saveRecent(item);
                if (window.triggerHaptic) window.triggerHaptic('light');
            });

            container.appendChild(a);
        });
    }

    // Highlighting matching characters with gold pill mark
    function highlightMatch(text, query) {
        if (!query) return escapeHtml(text);
        var escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var regex = new RegExp('(' + escaped + ')', 'gi');
        return escapeHtml(text).replace(regex, '<mark style="background:rgba(255,209,102,0.25);color:#ffd166;font-weight:800;border-radius:4px;padding:0 3px;">$1</mark>');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Fuzzy search scoring
    function fuzzyScore(text, query) {
        text = text.toLowerCase();
        query = query.toLowerCase();
        if (text.includes(query)) return 100 + (text.startsWith(query) ? 50 : 0);

        var score = 0;
        var index = 0;
        for (var i = 0; i < query.length; i++) {
            var charIndex = text.indexOf(query[i], index);
            if (charIndex === -1) return 0;
            score += 1 / (charIndex - index + 1);
            index = charIndex + 1;
        }
        return score;
    }

    // Filter with category & search query
    function filterItems(query) {
        var q = (query || '').trim();

        // Update clear button visibility
        if (clearBtn) {
            clearBtn.style.display = q.length > 0 ? 'flex' : 'none';
        }

        var list = navItems;

        // Apply category filter
        if (currentCategory !== 'all') {
            list = list.filter(function(item) {
                return item.category === currentCategory;
            });
        }

        if (!q) {
            renderItems(list, itemsContainer);
            updateGroupHeader('', list.length);
            syncEmptyState(list.length, '');
            if (quickChipsWrap) quickChipsWrap.style.display = currentCategory === 'all' ? '' : 'none';
            showRecent();
            activeIndex = -1;
            updateActive();
            return;
        }

        // Hide quick chips and recent during search query
        if (quickChipsWrap) quickChipsWrap.style.display = 'none';
        if (recentGroup) recentGroup.style.display = 'none';

        // Score and sort items
        var scored = list.map(function(item) {
            var labelScore = fuzzyScore(item.label, q);
            var hintScore = fuzzyScore(item.hint, q);
            var badgeScore = item.badge ? fuzzyScore(item.badge, q) : 0;
            var maxScore = Math.max(labelScore, hintScore, badgeScore);
            return { item: item, score: maxScore };
        }).filter(function(obj) {
            return obj.score > 0;
        }).sort(function(a, b) {
            return b.score - a.score;
        });

        var filtered = scored.map(function(obj) { return obj.item; });

        renderItems(filtered, itemsContainer, q);
        updateGroupHeader(q, filtered.length);
        syncEmptyState(filtered.length, q);
        activeIndex = -1;
        updateActive();
    }

    // Category pill selection
    if (categoriesWrap) {
        categoriesWrap.addEventListener('click', function(e) {
            var pill = e.target.closest('.cmd-cat-pill');
            if (!pill) return;
            setActiveCategoryPill(pill.getAttribute('data-cat') || 'all');
            if (bodyEl) bodyEl.scrollTop = 0;
            if (window.triggerHaptic) window.triggerHaptic('light');
            filterItems(input.value);
        });
    }

    if (emptyClearBtn) {
        emptyClearBtn.addEventListener('click', function() {
            input.value = '';
            if (bodyEl) bodyEl.scrollTop = 0;
            filterItems('');
            input.focus({ preventScroll: true });
        });
    }

    if (emptyAllCatBtn) {
        emptyAllCatBtn.addEventListener('click', function() {
            setActiveCategoryPill('all');
            if (bodyEl) bodyEl.scrollTop = 0;
            filterItems(input.value);
        });
    }

    // Recent items
    function getRecent() {
        try {
            return JSON.parse(localStorage.getItem('cmdPaletteRecent_v2') || '[]').slice(0, 4);
        } catch(e) { return []; }
    }

    function saveRecent(item) {
        var recent = getRecent().filter(function(r) { return r.url !== item.url; });
        recent.unshift(item);
        try {
            localStorage.setItem('cmdPaletteRecent_v2', JSON.stringify(recent.slice(0, 6)));
        } catch(e) {}
    }

    function showRecent() {
        var recent = getRecent();
        if (recent.length > 0 && (!input.value.trim()) && currentCategory === 'all') {
            renderItems(recent, recentContainer);
            recentGroup.style.display = '';
        } else {
            recentGroup.style.display = 'none';
        }
    }

    if (clearRecentBtn) {
        clearRecentBtn.addEventListener('click', function() {
            try { localStorage.removeItem('cmdPaletteRecent_v2'); } catch(e) {}
            recentGroup.style.display = 'none';
        });
    }

    // Keyboard navigation
    function getAllItems() {
        return overlay.querySelectorAll('.cmd-palette-items-list .cmd-palette-item');
    }

    function updateActive() {
        var items = getAllItems();
        items.forEach(function(el, i) {
            el.classList.toggle('active', i === activeIndex);
        });
        if (items[activeIndex]) {
            items[activeIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    // Open / Close actions
    function openPalette() {
        overlay.classList.add('active');
        document.body.classList.add('cmd-palette-open');
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        input.value = '';
        syncCategoryPillsAvailability();
        setActiveCategoryPill('all');
        renderQuickChips();
        if (bodyEl) bodyEl.scrollTop = 0;
        filterItems('');
        activeIndex = -1;
        attachViewportListeners();
        if (window.triggerHaptic) window.triggerHaptic('medium');
        setTimeout(function() { input.focus({ preventScroll: true }); }, 60);
    }

    function closePalette() {
        overlay.classList.remove('active');
        document.body.classList.remove('cmd-palette-open');
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
        detachViewportListeners();
        activeIndex = -1;
    }

    // Event listeners
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            input.value = '';
            if (bodyEl) bodyEl.scrollTop = 0;
            filterItems('');
            input.focus({ preventScroll: true });
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            closePalette();
        });
    }

    if (grabber) {
        grabber.addEventListener('click', function() {
            closePalette();
        });
    }

    input.addEventListener('input', function() {
        if (bodyEl) bodyEl.scrollTop = 0;
        filterItems(this.value);
    });

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closePalette();
    });

    document.addEventListener('keydown', function(e) {
        // Cmd+K or Ctrl+K shortcut
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (overlay.classList.contains('active')) {
                closePalette();
            } else {
                openPalette();
            }
            return;
        }

        if (!overlay.classList.contains('active')) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            closePalette();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            var items = getAllItems();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            updateActive();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, -1);
            updateActive();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            var all = getAllItems();
            if (all[activeIndex]) {
                all[activeIndex].click();
            } else if (all[0]) {
                all[0].click();
            }
        }
    });

    // Wire universal search bars to trigger command palette
    var saasSearch = document.querySelector('.saas-search-input:not(#studentSearchInput)');
    if (saasSearch) {
        saasSearch.addEventListener('focus', function(e) {
            var isInForm = e.target.closest('form');
            if (!isInForm) {
                e.target.blur();
                openPalette();
            }
        });
    }

    // Expose globally
    window.openCommandPalette = openPalette;
    window.closeCommandPalette = closePalette;
})();
</script>
