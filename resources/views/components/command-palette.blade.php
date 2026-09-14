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
                <i class="bi bi-search cmd-palette-search-icon"></i>
                <input type="text" class="cmd-palette-input" id="cmdPaletteInput"
                       placeholder="Search pages, actions..."
                       autocomplete="off" spellcheck="false"
                       aria-label="Search pages and actions">
                <button type="button" class="cmd-palette-clear-btn" id="cmdPaletteClearBtn" style="display:none;" title="Clear search" aria-label="Clear search">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
            <kbd class="cmd-palette-kbd d-none d-md-inline-flex" title="Press Escape to close">ESC</kbd>
            <button type="button" class="cmd-palette-close-btn" id="cmdPaletteCloseBtn" aria-label="Close command palette" title="Close (ESC)">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- Filter Category Pills --}}
        <div class="cmd-palette-categories" id="cmdPaletteCategories">
            <button type="button" class="cmd-cat-pill active" data-cat="all">
                <i class="bi bi-stars"></i> All
            </button>
            <button type="button" class="cmd-cat-pill" data-cat="action">
                <i class="bi bi-lightning-charge-fill"></i> Actions
            </button>
            <button type="button" class="cmd-cat-pill" data-cat="navigation">
                <i class="bi bi-compass-fill"></i> Navigation
            </button>
            <button type="button" class="cmd-cat-pill" data-cat="academic">
                <i class="bi bi-mortarboard-fill"></i> Academic
            </button>
            <button type="button" class="cmd-cat-pill" data-cat="system">
                <i class="bi bi-gear-fill"></i> System
            </button>
        </div>

        {{-- Palette Body --}}
        <div class="cmd-palette-body" id="cmdPaletteBody">
            {{-- Quick Access Chips (shown when no search query) --}}
            <div class="cmd-quick-chips-wrap" id="cmdQuickChipsWrap">
                <div class="cmd-quick-chips-label">
                    <i class="bi bi-lightning-fill"></i> Quick Access
                </div>
                <div class="cmd-quick-chips-row" id="cmdQuickChipsRow">
                    <!-- populated dynamically -->
                </div>
            </div>

            {{-- Recent Searches --}}
            <div class="cmd-palette-group" id="cmdPaletteRecent" style="display:none;">
                <div class="cmd-palette-group-header">
                    <div class="cmd-palette-group-label">
                        <i class="bi bi-clock-history"></i> Recent
                    </div>
                    <button type="button" class="cmd-palette-clear-recent" id="cmdClearRecentBtn">Clear</button>
                </div>
                <div id="cmdPaletteRecentItems" class="cmd-palette-items-list"></div>
            </div>

            {{-- Main Navigation Group --}}
            <div class="cmd-palette-group" id="cmdPaletteMainGroup">
                <div class="cmd-palette-group-header">
                    <div class="cmd-palette-group-label" id="cmdPaletteGroupLabel">
                        <i class="bi bi-compass"></i> Navigation
                    </div>
                    <span class="cmd-palette-count-badge" id="cmdPaletteCountBadge"></span>
                </div>
                <div id="cmdPaletteItems" class="cmd-palette-items-list"></div>
            </div>

            {{-- Empty State --}}
            <div class="cmd-palette-empty" id="cmdPaletteEmpty" style="display:none;">
                <div class="cmd-empty-icon">
                    <i class="bi bi-search"></i>
                </div>
                <div class="cmd-empty-title">No matching pages or actions found</div>
                <div class="cmd-empty-desc">Try keywords like "classes", "schedule", "code", or "records"</div>
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
                <i class="bi bi-shield-check"></i> Smart Attendance Command
            </div>
        </div>
    </div>
</div>

<style>
/* ── COMMAND PALETTE MODERN LUXURY DARK & GOLD THEME ── */
.cmd-palette-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(0, 0, 0, 0.78);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    align-items: flex-start;
    justify-content: center;
    padding: min(10vh, 70px) 16px 24px;
    opacity: 0;
    transition: opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.cmd-palette-overlay.active {
    display: flex;
    opacity: 1;
}

.cmd-palette {
    width: 620px;
    max-width: 100%;
    max-height: 560px;
    background: linear-gradient(180deg, rgba(24, 15, 13, 0.97) 0%, rgba(13, 8, 7, 0.99) 100%);
    border: 1.5px solid rgba(255, 209, 102, 0.25);
    border-radius: 24px;
    box-shadow: 0 28px 90px rgba(0, 0, 0, 0.88),
                0 0 0 1px rgba(207, 164, 111, 0.14),
                0 0 40px rgba(255, 209, 102, 0.08);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: translateY(14px) scale(0.97);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.cmd-palette-overlay.active .cmd-palette {
    transform: translateY(0) scale(1);
}

/* Mobile Grab Handle */
.cmd-palette-grabber {
    width: 38px;
    height: 4px;
    background: rgba(255, 255, 255, 0.22);
    border-radius: 99px;
    margin: 12px auto 2px;
    flex-shrink: 0;
}

/* Header Area */
.cmd-palette-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    background: rgba(255, 255, 255, 0.015);
}

.cmd-palette-search-box {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(0, 0, 0, 0.35);
    border: 1px solid rgba(255, 209, 102, 0.2);
    border-radius: 14px;
    padding: 10px 14px;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.cmd-palette-search-box:focus-within {
    border-color: #ffd166;
    background: rgba(255, 209, 102, 0.04);
    box-shadow: 0 0 18px rgba(255, 209, 102, 0.2), inset 0 0 8px rgba(255, 209, 102, 0.03);
}

.cmd-palette-search-icon {
    font-size: 1.1rem;
    color: #ffd166;
    flex-shrink: 0;
    opacity: 0.95;
}

.cmd-palette-input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    color: #f3ede4;
    font-size: 0.98rem;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    letter-spacing: -0.01em;
}

.cmd-palette-input::placeholder {
    color: rgba(179, 155, 130, 0.55);
}

.cmd-palette-clear-btn {
    background: transparent;
    border: none;
    color: #8f826f;
    cursor: pointer;
    font-size: 0.95rem;
    padding: 2px 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: color 0.15s ease;
}

.cmd-palette-clear-btn:hover {
    color: #ffd166;
}

.cmd-palette-kbd {
    background: rgba(255, 209, 102, 0.08);
    color: #b39b82;
    border: 1px solid rgba(255, 209, 102, 0.18);
    border-radius: 8px;
    padding: 4px 10px;
    font-size: 0.7rem;
    font-family: 'Inter', sans-serif;
    font-weight: 700;
    letter-spacing: 0.03em;
    flex-shrink: 0;
}

.cmd-palette-close-btn {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #b39b82;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    flex-shrink: 0;
}

.cmd-palette-close-btn:hover {
    background: rgba(248, 113, 113, 0.15);
    border-color: rgba(248, 113, 113, 0.35);
    color: #f87171;
    transform: scale(1.05);
}

/* Category Filter Pills */
.cmd-palette-categories {
    display: flex;
    gap: 8px;
    padding: 10px 20px;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    background: rgba(0, 0, 0, 0.15);
}

.cmd-palette-categories::-webkit-scrollbar {
    display: none;
}

.cmd-cat-pill {
    padding: 6px 13px;
    border-radius: 99px;
    font-size: 0.74rem;
    font-weight: 700;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.03);
    color: #b39b82;
    cursor: pointer;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}

.cmd-cat-pill:hover {
    background: rgba(255, 209, 102, 0.08);
    border-color: rgba(255, 209, 102, 0.25);
    color: #ffd166;
    transform: translateY(-1px);
}

.cmd-cat-pill.active {
    background: linear-gradient(135deg, rgba(255, 209, 102, 0.2) 0%, rgba(207, 164, 111, 0.12) 100%);
    border-color: rgba(255, 209, 102, 0.45);
    color: #ffd166;
    box-shadow: 0 2px 10px rgba(255, 209, 102, 0.15);
}

/* Quick Action Chips */
.cmd-quick-chips-wrap {
    padding: 6px 4px 14px;
    border-bottom: 1px dashed rgba(255, 255, 255, 0.06);
    margin-bottom: 12px;
}

.cmd-quick-chips-label {
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #cfa46f;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.cmd-quick-chips-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.cmd-quick-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.07);
    color: #f3ede4;
    text-decoration: none;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}

.cmd-quick-chip:hover {
    background: rgba(255, 209, 102, 0.1);
    border-color: rgba(255, 209, 102, 0.3);
    color: #ffd166;
    transform: translateY(-1px);
}

/* Body & Items */
.cmd-palette-body {
    flex: 1;
    overflow-y: auto;
    padding: 14px 16px;
    scrollbar-width: thin;
    scrollbar-color: rgba(207, 164, 111, 0.25) transparent;
}

.cmd-palette-body::-webkit-scrollbar {
    width: 6px;
}

.cmd-palette-body::-webkit-scrollbar-track {
    background: transparent;
}

.cmd-palette-body::-webkit-scrollbar-thumb {
    background: rgba(207, 164, 111, 0.22);
    border-radius: 4px;
}

.cmd-palette-body::-webkit-scrollbar-thumb:hover {
    background: rgba(207, 164, 111, 0.35);
}

.cmd-palette-group {
    margin-bottom: 12px;
}

.cmd-palette-group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
    padding: 0 4px;
}

.cmd-palette-group-label {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #a89a8a;
    display: flex;
    align-items: center;
    gap: 6px;
}

.cmd-palette-count-badge {
    font-size: 0.65rem;
    font-weight: 700;
    color: rgba(255, 209, 102, 0.7);
    background: rgba(255, 209, 102, 0.08);
    padding: 2px 6px;
    border-radius: 6px;
}

.cmd-palette-clear-recent {
    background: transparent;
    border: none;
    font-size: 0.7rem;
    font-weight: 600;
    color: #8f826f;
    cursor: pointer;
    transition: color 0.15s ease;
}

.cmd-palette-clear-recent:hover {
    color: #f87171;
}

.cmd-palette-items-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

/* Individual Item Card */
.cmd-palette-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 11px 14px;
    border-radius: 16px;
    color: #f3ede4;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    border: 1px solid transparent;
    user-select: none;
    background: rgba(255, 255, 255, 0.015);
}

.cmd-palette-item:hover {
    background: rgba(255, 255, 255, 0.045);
    border-color: rgba(255, 209, 102, 0.2);
    transform: translateX(3px);
}

.cmd-palette-item.active {
    background: linear-gradient(135deg, rgba(255, 209, 102, 0.14) 0%, rgba(207, 164, 111, 0.06) 100%);
    border: 1px solid rgba(255, 209, 102, 0.35);
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.35), inset 0 0 10px rgba(255, 209, 102, 0.04);
}

.cmd-palette-item-icon {
    width: 42px;
    height: 42px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
    transition: all 0.18s ease;
    border: 1px solid rgba(255, 255, 255, 0.06);
    background: rgba(255, 209, 102, 0.1);
    color: #ffd166;
}

.cmd-palette-item:hover .cmd-palette-item-icon,
.cmd-palette-item.active .cmd-palette-item-icon {
    transform: scale(1.06);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
}

.cmd-palette-item-text {
    flex: 1;
    min-width: 0;
}

.cmd-palette-item-label-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 2px;
}

.cmd-palette-item-label {
    font-weight: 700;
    font-size: 0.92rem;
    color: #fdfbf7;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cmd-palette-item-badge {
    font-size: 0.65rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.06);
    color: #cfa46f;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    border: 1px solid rgba(255, 255, 255, 0.06);
    flex-shrink: 0;
}

.cmd-palette-item-hint {
    font-size: 0.76rem;
    color: #9a8a7a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cmd-palette-item:hover .cmd-palette-item-hint,
.cmd-palette-item.active .cmd-palette-item-hint {
    color: #cfa46f;
}

.cmd-palette-item-arrow {
    color: #8f826f;
    font-size: 1.15rem;
    opacity: 0;
    transform: translateX(-4px);
    transition: all 0.18s ease;
    flex-shrink: 0;
}

.cmd-palette-item:hover .cmd-palette-item-arrow,
.cmd-palette-item.active .cmd-palette-item-arrow {
    opacity: 1;
    transform: translateX(0);
    color: #ffd166;
}

/* Empty State */
.cmd-palette-empty {
    text-align: center;
    padding: 40px 16px;
    color: #8f826f;
}

.cmd-empty-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: rgba(255, 209, 102, 0.08);
    border: 1px solid rgba(255, 209, 102, 0.18);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffd166;
    font-size: 1.4rem;
    margin: 0 auto 12px;
}

.cmd-empty-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #f3ede4;
    margin-bottom: 4px;
}

.cmd-empty-desc {
    font-size: 0.78rem;
    color: #8f826f;
    max-width: 280px;
    margin: 0 auto;
}

/* Footer (Desktop) */
.cmd-palette-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    background: rgba(0, 0, 0, 0.2);
    font-size: 0.72rem;
    color: #8f826f;
}

.cmd-palette-footer-hints {
    display: flex;
    align-items: center;
    gap: 14px;
}

.cmd-palette-footer kbd {
    background: rgba(255, 209, 102, 0.08);
    border: 1px solid rgba(255, 209, 102, 0.18);
    border-radius: 5px;
    padding: 2px 6px;
    font-size: 0.65rem;
    font-family: 'Inter', sans-serif;
    color: #ffd166;
    margin-right: 4px;
}

.cmd-palette-footer-brand {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #a89a8a;
    font-weight: 600;
}

/* ── MOBILE RESPONSIVENESS: NATIVE LUXURY BOTTOM SHEET ── */
@media (max-width: 768px) {
    .cmd-palette-overlay {
        padding: 0;
        align-items: flex-end;
    }

    .cmd-palette {
        width: 100%;
        max-width: 100%;
        max-height: 84vh;
        border-radius: 28px 28px 0 0;
        border-bottom: none;
        border-left: none;
        border-right: none;
        box-shadow: 0 -15px 50px rgba(0, 0, 0, 0.9);
        padding-bottom: env(safe-area-inset-bottom, 16px);
        transform: translateY(100%);
    }

    .cmd-palette-overlay.active .cmd-palette {
        transform: translateY(0);
    }

    .cmd-palette-header {
        padding: 12px 16px;
    }

    .cmd-palette-categories {
        padding: 8px 16px;
    }

    .cmd-palette-body {
        padding: 12px 14px;
    }

    .cmd-palette-item {
        padding: 10px 12px;
    }

    .cmd-palette-item-icon {
        width: 38px;
        height: 38px;
        font-size: 1.05rem;
        border-radius: 11px;
    }

    .cmd-palette-item-label {
        font-size: 0.88rem;
    }

    .cmd-palette-item-hint {
        font-size: 0.72rem;
    }

    .cmd-palette-footer {
        display: none !important;
    }
}
</style>

<script nonce="{{ csp_nonce() }}">
(function() {
    'use strict';

    // ── Role-aware Navigation & Actions ──
    var navItems = [];
    var quickActionChips = [];

    @auth
        @if(Auth::user()->isAdmin())
            navItems = [
                { id: 'dashboard', label: 'Dashboard', hint: 'Admin portal & university overview', icon: 'bi-grid-fill', color: '#ffd166', bg: 'rgba(255, 209, 102, 0.12)', category: 'navigation', badge: 'Admin', url: '{{ route("admin.dashboard") }}' },
                { id: 'students', label: 'Students', hint: 'Manage enrolled student records', icon: 'bi-people-fill', color: '#38bdf8', bg: 'rgba(56, 189, 248, 0.12)', category: 'academic', badge: 'Directory', url: '{{ route("admin.students") }}' },
                { id: 'teachers', label: 'Teachers', hint: 'Manage instructor accounts & faculty', icon: 'bi-person-workspace', color: '#a78bfa', bg: 'rgba(167, 139, 250, 0.12)', category: 'academic', badge: 'Faculty', url: '{{ route("admin.teachers") }}' },
                { id: 'subjects', label: 'Subjects & Schedules', hint: 'Course curriculum and timetables', icon: 'bi-book-fill', color: '#2dd4bf', bg: 'rgba(45, 212, 191, 0.12)', category: 'academic', badge: 'Curriculum', url: '{{ route("admin.subjects") }}' },
                { id: 'attendance', label: 'Attendance Logs', hint: 'University-wide attendance records', icon: 'bi-clipboard-check-fill', color: '#fde68a', bg: 'rgba(253, 230, 138, 0.12)', category: 'academic', badge: 'Logs', url: '{{ route("admin.attendance") }}' },
                { id: 'qr', label: 'QR Management', hint: 'Active QR attendance sessions', icon: 'bi-qr-code-scan', color: '#34d399', bg: 'rgba(52, 211, 153, 0.12)', category: 'action', badge: 'Session', url: '{{ route("admin.qr") }}' },
                { id: 'departments', label: 'Departments', hint: 'Academic departments & deans', icon: 'bi-building-fill', color: '#fbbf24', bg: 'rgba(251, 191, 36, 0.12)', category: 'system', badge: 'Org', url: '{{ route("admin.departments.index") }}' },
                { id: 'courses', label: 'Courses & Degrees', hint: 'Degree programs & majors', icon: 'bi-mortarboard-fill', color: '#60a5fa', bg: 'rgba(96, 165, 250, 0.12)', category: 'academic', badge: 'Programs', url: '{{ route("admin.courses.index") }}' },
                { id: 'sections', label: 'Sections', hint: 'Class sections and student cohorts', icon: 'bi-diagram-3-fill', color: '#c084fc', bg: 'rgba(192, 132, 252, 0.12)', category: 'academic', badge: 'Sections', url: '{{ route("admin.sections.index") }}' },
                { id: 'announcements', label: 'Announcements', hint: 'Broadcast campus announcements', icon: 'bi-megaphone-fill', color: '#f97316', bg: 'rgba(249, 115, 22, 0.12)', category: 'action', badge: 'Broadcast', url: '{{ route("admin.announcements.index") }}' },
                { id: 'calendar', label: 'Holiday Calendar', hint: 'Official holidays & university events', icon: 'bi-calendar-event-fill', color: '#4ade80', bg: 'rgba(74, 222, 128, 0.12)', category: 'navigation', badge: 'Calendar', url: '{{ route("admin.calendar") }}' },
                { id: 'excuses', label: 'Excuse Reviews', hint: 'Review & approve student absences', icon: 'bi-file-earmark-check-fill', color: '#f59e0b', bg: 'rgba(245, 158, 11, 0.12)', category: 'action', badge: 'Reviews', url: '{{ route("admin.excuses") }}' },
                { id: 'audit', label: 'Audit Logs', hint: 'Security activity & change logs', icon: 'bi-journal-code', color: '#e2e8f0', bg: 'rgba(226, 232, 240, 0.12)', category: 'system', badge: 'Security', url: '{{ route("admin.activity.log") }}' },
                { id: 'health', label: 'System Health', hint: 'Server, database and cache metrics', icon: 'bi-heart-pulse-fill', color: '#ef4444', bg: 'rgba(239, 68, 68, 0.12)', category: 'system', badge: 'Health', url: '{{ route("admin.system-health.index") }}' },
                { id: 'backups', label: 'Backups', hint: 'Manage database snapshots', icon: 'bi-database-down', color: '#06b6d4', bg: 'rgba(6, 182, 212, 0.12)', category: 'system', badge: 'Backup', url: '{{ route("admin.backups.index") }}' },
                { id: 'settings', label: 'Settings', hint: 'System-wide preferences & GPS', icon: 'bi-sliders', color: '#94a3b8', bg: 'rgba(148, 163, 184, 0.12)', category: 'system', badge: 'Config', url: '{{ route("admin.settings") }}' },
                { id: 'reports', label: 'Generate PDF Report', hint: 'Export official attendance summary', icon: 'bi-file-earmark-pdf-fill', color: '#f87171', bg: 'rgba(248, 113, 113, 0.12)', category: 'action', badge: 'Export', url: '{{ route("admin.attendance.pdf") }}' }
            ];
            quickActionChips = [
                { label: 'Students', icon: 'bi-people-fill', url: '{{ route("admin.students") }}' },
                { label: 'QR Sessions', icon: 'bi-qr-code-scan', url: '{{ route("admin.qr") }}' },
                { label: 'Excuse Reviews', icon: 'bi-file-earmark-check-fill', url: '{{ route("admin.excuses") }}' },
                { label: 'Audit Logs', icon: 'bi-journal-code', url: '{{ route("admin.activity.log") }}' }
            ];
        @elseif(Auth::user()->isTeacher())
            navItems = [
                { id: 'dashboard', label: 'Dashboard', hint: 'Teacher dashboard & active sessions', icon: 'bi-grid-fill', color: '#ffd166', bg: 'rgba(255, 209, 102, 0.12)', category: 'navigation', badge: 'Home', url: '{{ route("teacher.dashboard") }}' },
                { id: 'qr', label: 'Live QR Attendance', hint: 'Generate QR & monitor check-ins', icon: 'bi-qr-code-scan', color: '#34d399', bg: 'rgba(52, 211, 153, 0.12)', category: 'action', badge: 'Live', url: '{{ route("teacher.attendance") }}' },
                { id: 'classroom', label: 'My Classroom', hint: 'Active classroom student monitor', icon: 'bi-journal-album', color: '#38bdf8', bg: 'rgba(56, 189, 248, 0.12)', category: 'academic', badge: 'Classroom', url: '{{ route("teacher.classroom.index") }}' },
                { id: 'subjects', label: 'My Subjects', hint: 'Subject schedules & enrolled rosters', icon: 'bi-book-fill', color: '#a78bfa', bg: 'rgba(167, 139, 250, 0.12)', category: 'academic', badge: 'Subjects', url: '{{ route("teacher.subjects") }}' },
                { id: 'attendance', label: 'Attendance Records', hint: 'Class attendance history', icon: 'bi-clipboard-check-fill', color: '#fde68a', bg: 'rgba(253, 230, 138, 0.12)', category: 'academic', badge: 'Records', url: '{{ route("teacher.attendance") }}' },
                { id: 'absent', label: 'Absent Students Report', hint: 'Review chronic & missed classes', icon: 'bi-person-x-fill', color: '#f87171', bg: 'rgba(248, 113, 113, 0.12)', category: 'academic', badge: 'Report', url: '{{ route("teacher.absent") }}' },
                { id: 'excuses', label: 'Excuse Reviews', hint: 'Review submitted student excuses', icon: 'bi-file-text-fill', color: '#f59e0b', bg: 'rgba(245, 158, 11, 0.12)', category: 'action', badge: 'Reviews', url: '{{ route("teacher.excuse.reviews") }}' },
                { id: 'students', label: 'Student Directory', hint: 'Browse enrolled student profiles', icon: 'bi-people-fill', color: '#60a5fa', bg: 'rgba(96, 165, 250, 0.12)', category: 'academic', badge: 'Directory', url: '{{ route("teacher.students") }}' },
                { id: 'reports', label: 'Attendance Reports', hint: 'Generate attendance export & reports', icon: 'bi-bar-chart-fill', color: '#2dd4bf', bg: 'rgba(45, 212, 191, 0.12)', category: 'academic', badge: 'Analytics', url: '{{ route("teacher.reports") }}' },
                { id: 'calendar', label: 'Holiday Calendar', hint: 'School events & academic calendar', icon: 'bi-calendar-event-fill', color: '#4ade80', bg: 'rgba(74, 222, 128, 0.12)', category: 'navigation', badge: 'Calendar', url: '{{ route("teacher.calendar") }}' },
                { id: 'notifications', label: 'Notifications', hint: 'Alerts and system updates', icon: 'bi-bell-fill', color: '#fb7185', bg: 'rgba(251, 113, 133, 0.12)', category: 'navigation', badge: 'Alerts', url: '{{ route("teacher.notifications") }}' },
                { id: 'profile', label: 'My Profile', hint: 'Instructor credentials & settings', icon: 'bi-person-circle', color: '#cbd5e1', bg: 'rgba(203, 213, 225, 0.12)', category: 'system', badge: 'Account', url: '{{ route("teacher.profile") }}' }
            ];
            quickActionChips = [
                { label: 'Live QR', icon: 'bi-qr-code-scan', url: '{{ route("teacher.attendance") }}' },
                { label: 'Classroom', icon: 'bi-journal-album', url: '{{ route("teacher.classroom.index") }}' },
                { label: 'Excuse Reviews', icon: 'bi-file-text-fill', url: '{{ route("teacher.excuse.reviews") }}' },
                { label: 'Absent Report', icon: 'bi-person-x-fill', url: '{{ route("teacher.absent") }}' }
            ];
        @elseif(Auth::user()->role === 'parent')
            navItems = [
                { id: 'dashboard', label: 'Dashboard', hint: 'Children overview & daily attendance', icon: 'bi-grid-fill', color: '#ffd166', bg: 'rgba(255, 209, 102, 0.12)', category: 'navigation', badge: 'Home', url: '{{ route("parent.dashboard") }}' },
                { id: 'calendar', label: 'School Calendar', hint: 'Academic schedule, exams & holidays', icon: 'bi-calendar-event-fill', color: '#38bdf8', bg: 'rgba(56, 189, 248, 0.12)', category: 'academic', badge: 'Calendar', url: '{{ route("parent.calendar") }}' },
                { id: 'excuses', label: 'Excuse Letters', hint: 'Submit excuse for student absence', icon: 'bi-file-earmark-text-fill', color: '#f59e0b', bg: 'rgba(245, 158, 11, 0.12)', category: 'action', badge: 'Excuses', url: '{{ route("parent.excuses") }}' },
                { id: 'link', label: 'Link a Child', hint: 'Connect student account with code', icon: 'bi-link-45deg', color: '#34d399', bg: 'rgba(52, 211, 153, 0.12)', category: 'action', badge: 'Connect', url: '{{ route("parent.link.form") }}' },
                { id: 'notifications', label: 'Notifications', hint: 'Attendance alerts & warnings', icon: 'bi-bell-fill', color: '#fb7185', bg: 'rgba(251, 113, 133, 0.12)', category: 'navigation', badge: 'Alerts', url: '{{ route("parent.notifications") }}' }
            ];
            quickActionChips = [
                { label: 'Calendar', icon: 'bi-calendar-event-fill', url: '{{ route("parent.calendar") }}' },
                { label: 'Submit Excuse', icon: 'bi-file-earmark-text-fill', url: '{{ route("parent.excuses") }}' },
                { label: 'Link Child', icon: 'bi-link-45deg', url: '{{ route("parent.link.form") }}' }
            ];
        @else
            navItems = [
                { id: 'dashboard', label: 'Dashboard', hint: 'Student home & daily overview', icon: 'bi-grid-fill', color: '#ffd166', bg: 'rgba(255, 209, 102, 0.12)', category: 'navigation', badge: 'Home', url: '{{ route("home") }}' },
                { id: 'code', label: 'Enter Attendance Code', hint: 'Enter 6-digit session PIN or scan QR', icon: 'bi-qr-code-scan', color: '#34d399', bg: 'rgba(52, 211, 153, 0.12)', category: 'action', badge: 'Check-in', action: 'openScanner', url: '{{ route("home") }}?open_code=1' },
                { id: 'classes', label: 'My Classes', hint: 'Enrolled subjects, instructors & rooms', icon: 'bi-folder-fill', color: '#38bdf8', bg: 'rgba(56, 189, 248, 0.12)', category: 'academic', badge: 'Classes', url: '{{ route("student.classes") }}' },
                { id: 'schedule', label: 'My Schedule', hint: 'Weekly timetable & scheduled classes', icon: 'bi-calendar-week-fill', color: '#c084fc', bg: 'rgba(192, 132, 252, 0.12)', category: 'academic', badge: 'Schedule', url: '{{ route("student.schedule") }}' },
                { id: 'records', label: 'Attendance Records', hint: 'Subject breakdown & clock-in history', icon: 'bi-clock-history', color: '#fde68a', bg: 'rgba(253, 230, 138, 0.12)', category: 'academic', badge: 'History', url: '{{ route("attendance.records") }}' },
                { id: 'calendar', label: 'Attendance Calendar', hint: 'Interactive monthly attendance calendar', icon: 'bi-calendar-check-fill', color: '#fbbf24', bg: 'rgba(251, 191, 36, 0.12)', category: 'academic', badge: 'Calendar', url: '{{ route("student.attendance.calendar") }}' },
                { id: 'excuses', label: 'Excuse Submissions', hint: 'Submit absence excuse & check status', icon: 'bi-file-earmark-medical-fill', color: '#f59e0b', bg: 'rgba(245, 158, 11, 0.12)', category: 'action', badge: 'Excuses', url: '{{ route("excuses") }}' },
                { id: 'notifications', label: 'Notifications', hint: 'Class announcements & attendance warnings', icon: 'bi-bell-fill', color: '#fb7185', bg: 'rgba(251, 113, 133, 0.12)', category: 'navigation', badge: 'Alerts', url: '{{ route("notifications") }}' },
                { id: 'settings', label: 'Settings', hint: 'Account preferences, security & profile', icon: 'bi-gear-fill', color: '#94a3b8', bg: 'rgba(148, 163, 184, 0.12)', category: 'system', badge: 'Account', url: '{{ route("settings") }}' }
            ];
            quickActionChips = [
                { label: 'Check-in Code', icon: 'bi-qr-code-scan', action: 'openScanner', url: '{{ route("home") }}?open_code=1' },
                { label: 'My Schedule', icon: 'bi-calendar-week-fill', url: '{{ route("student.schedule") }}' },
                { label: 'View Records', icon: 'bi-clock-history', url: '{{ route("attendance.records") }}' },
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
    var itemsContainer = document.getElementById('cmdPaletteItems');
    var recentContainer = document.getElementById('cmdPaletteRecentItems');
    var recentGroup = document.getElementById('cmdPaletteRecent');
    var clearRecentBtn = document.getElementById('cmdClearRecentBtn');
    var quickChipsWrap = document.getElementById('cmdQuickChipsWrap');
    var quickChipsRow = document.getElementById('cmdQuickChipsRow');
    var categoriesWrap = document.getElementById('cmdPaletteCategories');
    var countBadge = document.getElementById('cmdPaletteCountBadge');
    var emptyState = document.getElementById('cmdPaletteEmpty');
    var activeIndex = -1;
    var currentCategory = 'all';

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
        if (countBadge) {
            countBadge.textContent = items.length + (items.length === 1 ? ' result' : ' results');
        }

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
            emptyState.style.display = 'none';
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
        emptyState.style.display = filtered.length === 0 ? 'block' : 'none';
        activeIndex = -1;
        updateActive();
    }

    // Category pill selection
    if (categoriesWrap) {
        categoriesWrap.addEventListener('click', function(e) {
            var pill = e.target.closest('.cmd-cat-pill');
            if (!pill) return;
            categoriesWrap.querySelectorAll('.cmd-cat-pill').forEach(function(el) { el.classList.remove('active'); });
            pill.classList.add('active');
            currentCategory = pill.getAttribute('data-cat') || 'all';
            if (window.triggerHaptic) window.triggerHaptic('light');
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
        document.body.style.overflow = 'hidden';
        input.value = '';
        currentCategory = 'all';
        if (categoriesWrap) {
            categoriesWrap.querySelectorAll('.cmd-cat-pill').forEach(function(el) { el.classList.remove('active'); });
            categoriesWrap.querySelector('[data-cat="all"]')?.classList.add('active');
        }
        renderQuickChips();
        renderItems(navItems, itemsContainer);
        showRecent();
        emptyState.style.display = 'none';
        if (clearBtn) clearBtn.style.display = 'none';
        activeIndex = -1;
        if (window.triggerHaptic) window.triggerHaptic('medium');
        setTimeout(function() { input.focus(); }, 60);
    }

    function closePalette() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        activeIndex = -1;
    }

    // Event listeners
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            input.value = '';
            filterItems('');
            input.focus();
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
