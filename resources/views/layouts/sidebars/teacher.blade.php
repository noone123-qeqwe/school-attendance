<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="{{ config('app.name') }} Logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
    </div>
</div>

<div class="sidebar-divider"></div>

@php
    $teacher = Auth::user();
    $pendingExcusesCount = 0;
    $pendingCorrectionsCount = 0;
    if ($teacher) {
        try {
            $subjectCodes = \App\Models\Subject::where('instructor_id', $teacher->id)
                ->orWhere('instructor', $teacher->name)
                ->pluck('code');

            if ($subjectCodes->isNotEmpty()) {
                $pendingExcusesCount = \App\Models\ExcuseSubmission::where('status', 'pending')
                    ->whereHas('attendance', function($q) use ($subjectCodes) {
                        $q->whereIn('subject_code', $subjectCodes);
                    })->count();

                $pendingCorrectionsCount = \App\Models\AttendanceCorrection::where('status', 'pending')
                    ->whereHas('attendance', function ($q) use ($subjectCodes) {
                        $q->whereIn('subject_code', $subjectCodes);
                    })->count();
            }
        } catch (\Throwable $e) {
            $pendingExcusesCount = 0;
            $pendingCorrectionsCount = 0;
        }
    }
    $totalRequestsPending = $pendingExcusesCount + $pendingCorrectionsCount;

    // Active state detection
    $isTeachingActive = request()->routeIs('teacher.classroom*') || request()->routeIs('teacher.subjects*') || request()->routeIs('teacher.students*') || request()->routeIs('teacher.student');
    $isAttendanceActive = request()->routeIs('teacher.attendance*') || request()->routeIs('teacher.reports*') || request()->routeIs('teacher.absent*');
    $isRequestsActive = request()->routeIs('teacher.excuse*') || request()->routeIs('teacher.corrections*');
@endphp

<nav class="sidebar-nav teacher-sidebar-nav" aria-label="Teacher Navigation">

    <!-- 1. MAIN -->
    <div class="teacher-nav-section">
        <div class="teacher-section-label">
            <i class="bi bi-house-door me-1"></i> Main
        </div>
        <a href="{{ route('teacher.dashboard') }}" 
           class="nav-link {{ request()->routeIs('teacher.dashboard*') ? 'active' : '' }}"
           data-title="Dashboard">
            <i class="bi bi-grid-1x2-fill"></i>
            <span class="nav-link-text">Dashboard</span>
        </a>
    </div>

    <!-- 2. TEACHING (Collapsible) -->
    <div class="teacher-nav-section teacher-collapsible-group {{ $isTeachingActive ? 'open is-active-parent' : 'open' }}" 
         id="teacherGroupTeaching" 
         data-group="teaching">
        <button type="button" 
                class="teacher-group-toggle" 
                onclick="toggleTeacherGroup('teaching')" 
                aria-expanded="true" 
                aria-controls="teacherMenuTeaching"
                id="toggleTeaching">
            <span class="teacher-group-title">
                <i class="bi bi-book-half teacher-group-icon"></i>
                <span class="teacher-group-label-text">Teaching</span>
            </span>
            <i class="bi bi-chevron-down teacher-chevron"></i>
        </button>
        <div class="teacher-submenu" id="teacherMenuTeaching" role="region" aria-labelledby="toggleTeaching">
            <a href="{{ route('teacher.classroom.index') }}" 
               class="nav-link {{ (request()->routeIs('teacher.classroom*') || request()->routeIs('teacher.subjects*')) ? 'active' : '' }}"
               data-title="My Classes">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span class="nav-link-text">My Classes</span>
            </a>
            <a href="{{ route('teacher.students') }}" 
               class="nav-link {{ (request()->routeIs('teacher.students*') || request()->routeIs('teacher.student')) ? 'active' : '' }}"
               data-title="Student Roster">
                <i class="bi bi-people-fill"></i>
                <span class="nav-link-text">Student Roster</span>
            </a>
        </div>
    </div>

    <!-- 3. ATTENDANCE (Collapsible) -->
    <div class="teacher-nav-section teacher-collapsible-group {{ $isAttendanceActive ? 'open is-active-parent' : 'open' }}" 
         id="teacherGroupAttendance" 
         data-group="attendance">
        <button type="button" 
                class="teacher-group-toggle" 
                onclick="toggleTeacherGroup('attendance')" 
                aria-expanded="true" 
                aria-controls="teacherMenuAttendance"
                id="toggleAttendance">
            <span class="teacher-group-title">
                <i class="bi bi-card-checklist teacher-group-icon"></i>
                <span class="teacher-group-label-text">Attendance</span>
            </span>
            <i class="bi bi-chevron-down teacher-chevron"></i>
        </button>
        <div class="teacher-submenu" id="teacherMenuAttendance" role="region" aria-labelledby="toggleAttendance">
            <a href="{{ route('teacher.attendance') }}" 
               class="nav-link {{ request()->routeIs('teacher.attendance*') ? 'active' : '' }}"
               data-title="Attendance Records">
                <i class="bi bi-clipboard-check-fill"></i>
                <span class="nav-link-text">Attendance Records</span>
            </a>
            <a href="{{ route('teacher.reports') }}" 
               class="nav-link {{ request()->routeIs('teacher.reports*') ? 'active' : '' }}"
               data-title="Attendance Reports">
                <i class="bi bi-graph-up-arrow"></i>
                <span class="nav-link-text">Attendance Reports</span>
            </a>
        </div>
    </div>

    <!-- 4. REQUESTS (Collapsible) -->
    <div class="teacher-nav-section teacher-collapsible-group {{ $isRequestsActive ? 'open is-active-parent' : 'open' }}" 
         id="teacherGroupRequests" 
         data-group="requests">
        <button type="button" 
                class="teacher-group-toggle" 
                onclick="toggleTeacherGroup('requests')" 
                aria-expanded="true" 
                aria-controls="teacherMenuRequests"
                id="toggleRequests">
            <span class="teacher-group-title">
                <i class="bi bi-inboxes-fill teacher-group-icon"></i>
                <span class="teacher-group-label-text">Requests</span>
            </span>
            <div class="teacher-toggle-meta">
                @if($totalRequestsPending > 0)
                    <span class="teacher-collapsed-badge" title="{{ $totalRequestsPending }} pending request(s)">{{ $totalRequestsPending }}</span>
                @endif
                <i class="bi bi-chevron-down teacher-chevron"></i>
            </div>
        </button>
        <div class="teacher-submenu" id="teacherMenuRequests" role="region" aria-labelledby="toggleRequests">
            <a href="{{ route('teacher.excuse.reviews') }}" 
               class="nav-link {{ request()->routeIs('teacher.excuse*') ? 'active' : '' }}"
               data-title="Excuse Submissions">
                <i class="bi bi-file-earmark-text-fill"></i>
                <span class="nav-link-text">Excuse Submissions</span>
                @if($pendingExcusesCount > 0)
                    <span class="teacher-badge badge-excuses ms-auto" title="{{ $pendingExcusesCount }} pending excuse(s)">
                        {{ $pendingExcusesCount }}
                    </span>
                @endif
            </a>
            <a href="{{ route('teacher.corrections') }}" 
               class="nav-link {{ request()->routeIs('teacher.corrections*') ? 'active' : '' }}"
               data-title="Correction Requests">
                <i class="bi bi-pencil-square"></i>
                <span class="nav-link-text">Correction Requests</span>
                @if($pendingCorrectionsCount > 0)
                    <span class="teacher-badge badge-corrections ms-auto" title="{{ $pendingCorrectionsCount }} pending correction(s)">
                        {{ $pendingCorrectionsCount }}
                    </span>
                @endif
            </a>
        </div>
    </div>

    <!-- 5. SCHOOL -->
    <div class="teacher-nav-section">
        <div class="teacher-section-label">
            <i class="bi bi-buildings me-1"></i> School
        </div>
        <a href="{{ route('teacher.calendar') }}" 
           class="nav-link {{ request()->routeIs('teacher.calendar*') ? 'active' : '' }}"
           data-title="School Calendar">
            <i class="bi bi-calendar2-week-fill"></i>
            <span class="nav-link-text">School Calendar</span>
        </a>
    </div>

</nav>

<style>
/* Teacher Sidebar Navigation Styling */
.teacher-sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 10px 12px 60px !important;
}

.teacher-nav-section {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

/* Group Headers & Section Labels */
.teacher-section-label {
    padding: 6px 12px 4px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(207, 164, 111, 0.65);
    display: flex;
    align-items: center;
    user-select: none;
}

.teacher-group-toggle {
    width: 100%;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 12px;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: rgba(207, 164, 111, 0.75);
    transition: all 0.2s ease;
    user-select: none;
}

.teacher-group-toggle:hover {
    background: rgba(207, 164, 111, 0.08);
    color: #f3e7cd;
}

.teacher-group-toggle:focus-visible {
    outline: 2px solid rgba(207, 164, 111, 0.45);
    outline-offset: 1px;
}

.teacher-group-title {
    display: flex;
    align-items: center;
    gap: 7px;
}

.teacher-group-icon {
    font-size: 0.85rem;
    opacity: 0.85;
}

.teacher-toggle-meta {
    display: flex;
    align-items: center;
    gap: 6px;
}

.teacher-chevron {
    font-size: 0.72rem;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 0.6;
}

.teacher-collapsible-group.open .teacher-chevron {
    transform: rotate(0deg);
}

.teacher-collapsible-group:not(.open) .teacher-chevron {
    transform: rotate(-90deg);
}

/* Submenu Container with indentation tree */
.teacher-submenu {
    display: flex;
    flex-direction: column;
    gap: 3px;
    max-height: 280px;
    opacity: 1;
    overflow: hidden;
    margin-left: 8px;
    padding-left: 6px;
    border-left: 1.5px solid rgba(207, 164, 111, 0.18);
    transition: max-height 0.28s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease, margin 0.2s ease;
}

.teacher-collapsible-group:not(.open) .teacher-submenu {
    max-height: 0 !important;
    opacity: 0 !important;
    pointer-events: none !important;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    border-left-color: transparent !important;
}

/* Links */
.teacher-sidebar-nav .nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    min-height: 42px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 500;
    color: rgba(243, 231, 205, 0.82);
    margin-bottom: 2px;
    position: relative;
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
}

.teacher-sidebar-nav .nav-link i {
    font-size: 1.15rem;
    width: 22px;
    text-align: center;
    flex-shrink: 0;
    transition: transform 0.2s ease, color 0.2s ease;
    color: rgba(243, 231, 205, 0.7);
}

.teacher-sidebar-nav .nav-link:hover {
    background: rgba(255, 255, 255, 0.07);
    color: #ffffff;
    transform: translateX(2px);
}

.teacher-sidebar-nav .nav-link:hover i {
    transform: scale(1.1);
    color: var(--gold, #cfa46f);
}

/* Active State */
.teacher-sidebar-nav .nav-link.active {
    background: linear-gradient(90deg, rgba(207, 164, 111, 0.22) 0%, rgba(207, 164, 111, 0.04) 100%);
    color: #ffffff !important;
    border-left: 3.5px solid var(--gold, #cfa46f);
    font-weight: 700;
    box-shadow: inset 0 0 10px rgba(207, 164, 111, 0.08);
}

.teacher-sidebar-nav .nav-link.active i {
    color: var(--gold, #cfa46f);
}

/* Highlight parent group toggle when child is active */
.teacher-collapsible-group.is-active-parent .teacher-group-toggle {
    color: #f3e7cd;
}

.teacher-collapsible-group.is-active-parent .teacher-group-icon {
    color: var(--gold, #cfa46f);
}

/* Badges */
.teacher-badge {
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 999px;
    line-height: 1.2;
    letter-spacing: 0;
}

.badge-excuses {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.35);
}

.badge-corrections {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.35);
}

.teacher-collapsed-badge {
    background: #ef4444;
    color: #ffffff;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 999px;
    line-height: 1.2;
}

.teacher-collapsible-group.open .teacher-collapsed-badge {
    display: none;
}

/* Collapsed Mini-Sidebar Overrides */
.sidebar.collapsed .teacher-section-label,
.sidebar.collapsed .teacher-group-label-text,
.sidebar.collapsed .teacher-chevron,
.sidebar.collapsed .teacher-collapsed-badge {
    display: none !important;
}

.sidebar.collapsed .teacher-submenu {
    margin-left: 0 !important;
    padding-left: 0 !important;
    border-left: none !important;
}

.sidebar.collapsed .teacher-group-toggle {
    justify-content: center !important;
    padding: 8px 0 !important;
}

/* Mobile adjustments */
@media (max-width: 767.98px) {
    .teacher-sidebar-nav .nav-link {
        min-height: 46px;
        font-size: 0.92rem;
    }
    
    .teacher-group-toggle {
        padding: 9px 12px;
    }
}
</style>

<script>
(function() {
    const STORAGE_KEY = 'teacher_sidebar_groups_state';

    // Get current stored state
    function getStoredState() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    // Save state
    function saveGroupState(groupName, isOpen) {
        try {
            const state = getStoredState() || {};
            state[groupName] = isOpen;
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {}
    }

    // Toggle a group
    window.toggleTeacherGroup = function(groupName) {
        const sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('collapsed')) {
            return;
        }

        const idMap = {
            'teaching': 'teacherGroupTeaching',
            'attendance': 'teacherGroupAttendance',
            'requests': 'teacherGroupRequests'
        };

        const elId = idMap[groupName];
        if (!elId) return;

        const groupEl = document.getElementById(elId);
        if (!groupEl) return;

        const willOpen = !groupEl.classList.contains('open');
        groupEl.classList.toggle('open', willOpen);

        const btn = groupEl.querySelector('.teacher-group-toggle');
        if (btn) {
            btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        }

        saveGroupState(groupName, willOpen);
    };

    // Restore state on DOM load
    document.addEventListener('DOMContentLoaded', function() {
        const state = getStoredState();
        if (!state) return;

        ['teaching', 'attendance', 'requests'].forEach(function(group) {
            const idMap = {
                'teaching': 'teacherGroupTeaching',
                'attendance': 'teacherGroupAttendance',
                'requests': 'teacherGroupRequests'
            };
            const groupEl = document.getElementById(idMap[group]);
            if (!groupEl) return;

            // If this group contains the active page, ALWAYS KEEP IT OPEN
            if (groupEl.classList.contains('is-active-parent')) {
                groupEl.classList.add('open');
                const btn = groupEl.querySelector('.teacher-group-toggle');
                if (btn) btn.setAttribute('aria-expanded', 'true');
                return;
            }

            // Otherwise restore stored state if specified
            if (typeof state[group] === 'boolean') {
                groupEl.classList.toggle('open', state[group]);
                const btn = groupEl.querySelector('.teacher-group-toggle');
                if (btn) btn.setAttribute('aria-expanded', state[group] ? 'true' : 'false');
            }
        });
    });
})();
</script>
