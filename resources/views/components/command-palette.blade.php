{{-- ═══════════════════════════════════════════════════
     COMMAND PALETTE — Cmd+K Quick Navigation
     Role-aware spotlight search for all layouts
     ═══════════════════════════════════════════════════ --}}

<div class="cmd-palette-overlay" id="cmdPaletteOverlay">
    <div class="cmd-palette" id="cmdPalette">
        <div class="cmd-palette-header">
            <i class="bi bi-search cmd-palette-search-icon"></i>
            <input type="text" class="cmd-palette-input" id="cmdPaletteInput"
                   placeholder="Search pages, actions..."
                   autocomplete="off" spellcheck="false">
            <kbd class="cmd-palette-kbd">ESC</kbd>
        </div>

        <div class="cmd-palette-body" id="cmdPaletteBody">
            <div class="cmd-palette-group" id="cmdPaletteRecent" style="display:none;">
                <div class="cmd-palette-group-label">
                    <i class="bi bi-clock-history"></i> Recent
                </div>
                <div id="cmdPaletteRecentItems"></div>
            </div>
            <div class="cmd-palette-group">
                <div class="cmd-palette-group-label">
                    <i class="bi bi-compass"></i> Navigation
                </div>
                <div id="cmdPaletteItems"></div>
            </div>
            <div class="cmd-palette-empty" id="cmdPaletteEmpty" style="display:none;">
                <i class="bi bi-search" style="font-size:1.5rem;opacity:0.3;"></i>
                <div style="margin-top:8px;">No results found</div>
            </div>
        </div>

        <div class="cmd-palette-footer">
            <span><kbd>↑↓</kbd> Navigate</span>
            <span><kbd>↵</kbd> Open</span>
            <span><kbd>ESC</kbd> Close</span>
        </div>
    </div>
</div>

<style>
/* ── COMMAND PALETTE STYLES ── */
.cmd-palette-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    align-items: flex-start;
    justify-content: center;
    padding-top: min(20vh, 120px);
}

.cmd-palette-overlay.active {
    display: flex;
    animation: cmdFadeIn 0.15s ease;
}

@keyframes cmdFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.cmd-palette {
    width: 560px;
    max-width: calc(100vw - 32px);
    max-height: 480px;
    background: rgba(22, 14, 10, 0.98);
    border: 1px solid rgba(207, 164, 111, 0.2);
    border-radius: 16px;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(207, 164, 111, 0.08);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: cmdSlideUp 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes cmdSlideUp {
    from { transform: translateY(16px) scale(0.97); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}

.cmd-palette-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(207, 164, 111, 0.12);
}

.cmd-palette-search-icon {
    font-size: 1.1rem;
    color: #cfa46f;
    flex-shrink: 0;
}

.cmd-palette-input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    color: #f3e7cd;
    font-size: 1rem;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
}

.cmd-palette-input::placeholder {
    color: #8f826f;
}

.cmd-palette-kbd {
    background: rgba(207, 164, 111, 0.1);
    color: #b39b82;
    border: 1px solid rgba(207, 164, 111, 0.15);
    border-radius: 6px;
    padding: 2px 8px;
    font-size: 0.68rem;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    flex-shrink: 0;
}

.cmd-palette-body {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.cmd-palette-group-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #8f826f;
    padding: 8px 12px 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.cmd-palette-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    color: #f3e7cd;
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.12s ease;
}

.cmd-palette-item:hover,
.cmd-palette-item.active {
    background: rgba(207, 164, 111, 0.1);
    color: #f3e7cd;
}

.cmd-palette-item.active {
    outline: 1px solid rgba(207, 164, 111, 0.2);
}

.cmd-palette-item-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(207, 164, 111, 0.08);
    color: #cfa46f;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.cmd-palette-item-text {
    flex: 1;
    min-width: 0;
}

.cmd-palette-item-label {
    font-weight: 600;
    font-size: 0.85rem;
}

.cmd-palette-item-hint {
    font-size: 0.72rem;
    color: #8f826f;
    margin-top: 1px;
}

.cmd-palette-empty {
    text-align: center;
    padding: 32px 16px;
    color: #8f826f;
    font-size: 0.85rem;
}

.cmd-palette-footer {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 10px 20px;
    border-top: 1px solid rgba(207, 164, 111, 0.08);
    font-size: 0.7rem;
    color: #8f826f;
}

.cmd-palette-footer kbd {
    background: rgba(207, 164, 111, 0.08);
    border: 1px solid rgba(207, 164, 111, 0.12);
    border-radius: 4px;
    padding: 1px 5px;
    font-size: 0.65rem;
    font-family: 'Inter', sans-serif;
    color: #b39b82;
}

@media (max-width: 768px) {
    .cmd-palette-overlay { padding-top: 16px; }
    .cmd-palette { max-height: 70vh; border-radius: 16px 16px 0 0; }
    .cmd-palette-footer { display: none; }
}
</style>

<script nonce="{{ csp_nonce() }}">
(function() {
    'use strict';

    // ── Role-aware navigation items ──
    var navItems = [];

    @auth
        @if(Auth::user()->isAdmin())
            navItems = [
                { label: 'Dashboard', hint: 'Admin overview', icon: 'bi-grid-1x2', url: '{{ route("admin.dashboard") }}' },
                { label: 'Students', hint: 'Manage students', icon: 'bi-people', url: '{{ route("admin.students") }}' },
                { label: 'Teachers', hint: 'Manage instructors', icon: 'bi-person-workspace', url: '{{ route("admin.teachers") }}' },
                { label: 'Subjects', hint: 'Course subjects', icon: 'bi-book', url: '{{ route("admin.subjects") }}' },
                { label: 'Attendance', hint: 'Attendance logs', icon: 'bi-clipboard-check', url: '{{ route("admin.attendance") }}' },
                { label: 'QR Management', hint: 'QR sessions', icon: 'bi-qr-code', url: '{{ route("admin.qr") }}' },
                { label: 'Departments', hint: 'Academic departments', icon: 'bi-building', url: '{{ route("admin.departments.index") }}' },
                { label: 'Courses', hint: 'Course programs', icon: 'bi-mortarboard', url: '{{ route("admin.courses.index") }}' },
                { label: 'Sections', hint: 'Class sections', icon: 'bi-diagram-3', url: '{{ route("admin.sections.index") }}' },
                { label: 'Announcements', hint: 'Post announcements', icon: 'bi-megaphone', url: '{{ route("admin.announcements.index") }}' },
                { label: 'Holiday Calendar', hint: 'Holidays & events', icon: 'bi-calendar-event', url: '{{ route("admin.calendar") }}' },
                { label: 'Notifications', hint: 'System alerts', icon: 'bi-bell', url: '{{ route("admin.notifications") }}' },
                { label: 'Excuse Reviews', hint: 'Student excuses', icon: 'bi-file-earmark-check', url: '{{ route("admin.excuses") }}' },
                { label: 'Settings', hint: 'System settings', icon: 'bi-sliders', url: '{{ route("admin.settings") }}' },
                { label: 'Audit Logs', hint: 'Activity history', icon: 'bi-journal-code', url: '{{ route("admin.activity.log") }}' },
                { label: 'System Health', hint: 'Server status', icon: 'bi-heart-pulse', url: '{{ route("admin.system-health.index") }}' },
                { label: 'Backups', hint: 'Database backups', icon: 'bi-database-down', url: '{{ route("admin.backups.index") }}' },
                { label: 'Profile', hint: 'Admin profile', icon: 'bi-person-circle', url: '{{ route("admin.profile") }}' },
                { label: 'Generate Report', hint: 'Export PDF', icon: 'bi-file-earmark-pdf', url: '{{ route("admin.attendance.pdf") }}' },
            ];
        @elseif(Auth::user()->isTeacher())
            navItems = [
                { label: 'Dashboard', hint: 'Teacher overview', icon: 'bi-grid-fill', url: '{{ route("teacher.dashboard") }}' },
                { label: 'My Classes', hint: 'Classroom view', icon: 'bi-journal-album', url: '{{ route("teacher.classroom.index") }}' },
                { label: 'My Subjects', hint: 'Subject management', icon: 'bi-book', url: '{{ route("teacher.subjects") }}' },
                { label: 'Attendance', hint: 'Attendance records', icon: 'bi-clipboard-check', url: '{{ route("teacher.attendance") }}' },
                { label: 'Absent Report', hint: 'Absent students', icon: 'bi-person-x', url: '{{ route("teacher.absent") }}' },
                { label: 'Excuse Reviews', hint: 'Review excuses', icon: 'bi-file-text', url: '{{ route("teacher.excuse.reviews") }}' },
                { label: 'Students', hint: 'Student list', icon: 'bi-people', url: '{{ route("teacher.students") }}' },
                { label: 'Reports', hint: 'Generate reports', icon: 'bi-bar-chart', url: '{{ route("teacher.reports") }}' },
                { label: 'Holiday Calendar', hint: 'Holidays & events', icon: 'bi-calendar-event', url: '{{ route("teacher.calendar") }}' },
                { label: 'Notifications', hint: 'Alerts', icon: 'bi-bell', url: '{{ route("teacher.notifications") }}' },

                { label: 'Profile', hint: 'My profile', icon: 'bi-person-circle', url: '{{ route("teacher.profile") }}' },
            ];
        @elseif(Auth::user()->role === 'parent')
            navItems = [
                { label: 'Dashboard', hint: 'Children overview', icon: 'bi-grid-fill', url: '{{ route("parent.dashboard") }}' },
                { label: 'Calendar', hint: 'School calendar', icon: 'bi-calendar-event', url: '{{ route("parent.calendar") }}' },
                { label: 'Excuse Letters', hint: 'Submit excuses', icon: 'bi-file-earmark-text', url: '{{ route("parent.excuses") }}' },

                { label: 'Notifications', hint: 'Alerts', icon: 'bi-bell', url: '{{ route("parent.notifications") }}' },
                { label: 'Link a Child', hint: 'Connect student', icon: 'bi-link-45deg', url: '{{ route("parent.link.form") }}' },
            ];
        @else
            navItems = [
                { label: 'Dashboard', hint: 'Student home', icon: 'bi-grid-fill', url: '{{ route("home") }}' },
                { label: 'My Classes', hint: 'Enrolled subjects', icon: 'bi-folder', url: '{{ route("student.classes") }}' },
                { label: 'My Schedule', hint: 'Weekly timetable', icon: 'bi-calendar-week', url: '{{ route("student.schedule") }}' },
                { label: 'Notifications', hint: 'Alerts & warnings', icon: 'bi-bell', url: '{{ route("notifications") }}' },
                { label: 'Excuse Submissions', hint: 'Submit excuses', icon: 'bi-file-text', url: '{{ route("excuses") }}' },
                { label: 'Attendance Records', hint: 'View records', icon: 'bi-clock-history', url: '{{ route("attendance.records") }}' },
                { label: 'Settings', hint: 'Account settings', icon: 'bi-gear', url: '{{ route("settings") }}' },
            ];
        @endif
    @endauth

    var overlay = document.getElementById('cmdPaletteOverlay');
    var input = document.getElementById('cmdPaletteInput');
    var itemsContainer = document.getElementById('cmdPaletteItems');
    var recentContainer = document.getElementById('cmdPaletteRecentItems');
    var recentGroup = document.getElementById('cmdPaletteRecent');
    var emptyState = document.getElementById('cmdPaletteEmpty');
    var activeIndex = -1;

    // Render items
    function renderItems(items, container) {
        container.innerHTML = '';
        items.forEach(function(item, i) {
            var a = document.createElement('a');
            a.href = item.url;
            a.className = 'cmd-palette-item';
            a.setAttribute('data-index', i);
            a.innerHTML = '<div class="cmd-palette-item-icon"><i class="bi ' + item.icon + '"></i></div>' +
                '<div class="cmd-palette-item-text"><div class="cmd-palette-item-label">' + item.label + '</div>' +
                '<div class="cmd-palette-item-hint">' + item.hint + '</div></div>';
            a.addEventListener('click', function() {
                saveRecent(item);
            });
            container.appendChild(a);
        });
    }

    // Filter
    function filterItems(query) {
        var q = query.toLowerCase().trim();
        var filtered = navItems.filter(function(item) {
            return item.label.toLowerCase().includes(q) || item.hint.toLowerCase().includes(q);
        });

        renderItems(filtered, itemsContainer);
        emptyState.style.display = filtered.length === 0 ? 'block' : 'none';
        recentGroup.style.display = q.length > 0 ? 'none' : (getRecent().length > 0 ? '' : 'none');
        activeIndex = -1;
        updateActive();
    }

    // Recent pages
    function getRecent() {
        try {
            return JSON.parse(localStorage.getItem('cmdPaletteRecent') || '[]').slice(0, 3);
        } catch(e) { return []; }
    }

    function saveRecent(item) {
        var recent = getRecent().filter(function(r) { return r.url !== item.url; });
        recent.unshift(item);
        localStorage.setItem('cmdPaletteRecent', JSON.stringify(recent.slice(0, 5)));
    }

    function showRecent() {
        var recent = getRecent();
        if (recent.length > 0) {
            renderItems(recent, recentContainer);
            recentGroup.style.display = '';
        } else {
            recentGroup.style.display = 'none';
        }
    }

    // Keyboard nav
    function getAllItems() {
        return overlay.querySelectorAll('.cmd-palette-item');
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

    // Open/Close
    function openPalette() {
        overlay.classList.add('active');
        input.value = '';
        renderItems(navItems, itemsContainer);
        showRecent();
        emptyState.style.display = 'none';
        activeIndex = -1;
        setTimeout(function() { input.focus(); }, 50);
    }

    function closePalette() {
        overlay.classList.remove('active');
        activeIndex = -1;
    }

    // Event Listeners
    document.addEventListener('keydown', function(e) {
        // Cmd+K or Ctrl+K
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
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
            var items = getAllItems();
            if (items[activeIndex]) {
                items[activeIndex].click();
            }
        }
    });

    input.addEventListener('input', function() {
        filterItems(this.value);
    });

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closePalette();
    });

    // Wire existing search bars to open palette
    var saasSearch = document.querySelector('.saas-search-input:not(#studentSearchInput)');
    if (saasSearch) {
        // Only open palette if it's NOT inside a form
        saasSearch.addEventListener('focus', function(e) {
            var isInForm = e.target.closest('form');
            if (!isInForm) {
                e.target.blur();
                openPalette();
            }
        });
    }

    // Expose globally for external triggers
    window.openCommandPalette = openPalette;
    window.closeCommandPalette = closePalette;
})();
</script>
