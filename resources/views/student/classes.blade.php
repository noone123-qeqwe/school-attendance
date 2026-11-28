@extends('layouts.app')
@section('portal-title', 'Classes & Schedule')
@section('page-title', 'Classes & Schedule')

@section('content')
@php
    $user = $user ?? auth()->user();
    $todayName = now()->format('l');
    $shortDayMap = [
        'Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed',
        'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun',
    ];
    $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    $coursesData = [];
    $hasSaturday = false;

    foreach ($subjects as $subject) {
        $slots = collect($subject->schedules ?? []);
        $rawDays = array_values(array_intersect($dayOrder, $slots->pluck('day')->filter()->unique()->values()->all()));
        if (in_array('Saturday', $rawDays, true)) {
            $hasSaturday = true;
        }
        $daysLabel = !empty($rawDays)
            ? implode('/', array_map(fn ($d) => $shortDayMap[$d] ?? substr($d, 0, 3), $rawDays))
            : 'TBA';

        $firstSlot = $slots->sortBy('start_time')->first();
        $timeRange = ($firstSlot && filled($firstSlot->start_time) && filled($firstSlot->end_time))
            ? \Carbon\Carbon::parse($firstSlot->start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($firstSlot->end_time)->format('g:i A')
            : 'Schedule TBA';

        $rawRoom = trim((string) ($firstSlot?->room ?? ''));
        $room = ($rawRoom !== '' && strtoupper($rawRoom) !== 'TBA') ? $rawRoom : 'Room TBA';
        $instructor = $subject->instructorUser?->name ?? $subject->instructor ?? 'Instructor TBA';
        $units = (float) ($subject->units ?: 3.0);
        $unitsText = fmod($units, 1.0) === 0.0 ? (string) (int) $units : number_format($units, 1);
        $attInfo = ($subjectAttendanceMap ?? [])[$subject->code] ?? ['count' => 0, 'total' => 0, 'recent' => []];

        $slotList = $slots->map(function ($s) use ($shortDayMap) {
            $r = trim((string) ($s->room ?? ''));
            return [
                'day' => (string) $s->day,
                'short_day' => $shortDayMap[$s->day] ?? substr((string) $s->day, 0, 3),
                'start_time' => $s->start_time ? \Carbon\Carbon::parse($s->start_time)->format('g:i A') : 'TBA',
                'end_time' => $s->end_time ? \Carbon\Carbon::parse($s->end_time)->format('g:i A') : '',
                'sort_time' => (string) ($s->start_time ?? '99:99:99'),
                'room' => ($r !== '' && strtoupper($r) !== 'TBA') ? $r : 'Room TBA',
            ];
        })->values()->all();

        $coursesData[] = [
            'id' => $subject->id,
            'code' => (string) $subject->code,
            'name' => (string) $subject->name,
            'instructor' => $instructor,
            'room' => $room,
            'section' => (string) ($subject->section ?: ($user->section ?: 'Unassigned')),
            'units' => $unitsText,
            'days_label' => $daysLabel,
            'raw_days' => $rawDays,
            'is_today' => in_array($todayName, $rawDays, true),
            'time_range' => $timeRange,
            'slots' => $slotList,
            'logged_count' => (int) ($attInfo['count'] ?? 0),
            'total_records' => (int) ($attInfo['total'] ?? 0),
            'recent_records' => $attInfo['recent'] ?? [],
        ];
    }

    $weekColumns = $hasSaturday
        ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
        : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    $subtitleMeta = collect([
        $user->course,
        $user->year_level ? 'Year ' . $user->year_level : null,
        $user->semester ? 'Semester ' . $user->semester : null,
    ])->filter()->implode(' · ');
@endphp

<div>
    @include('partials.missing-section-notice')

    <header class="sf-page-head">
        <div>
            <h1>Classes &amp; Schedule</h1>
            <p>{{ $subtitleMeta !== '' ? $subtitleMeta . ' · ' : '' }}Enrolled subjects and weekly timetable</p>
        </div>
        <div class="sf-page-actions">
            @if(!empty($hasActiveAssistantAssignment))
                <a href="{{ route('student-assistant.classes') }}" class="sf-btn sf-btn-secondary sf-mobile-only">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/></svg>
                    <span>Student Assistant</span>
                </a>
            @endif
            <div class="sf-segmented sf-desktop-only" role="group" aria-label="Classes layout mode">
                <button type="button" class="sf-segmented-btn active" id="sfClassesViewListBtn" data-classes-view="list" aria-pressed="true">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>
                    <span>List</span>
                </button>
                <button type="button" class="sf-segmented-btn" id="sfClassesViewGridBtn" data-classes-view="weekly" aria-pressed="false">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                    <span>Weekly Grid</span>
                </button>
            </div>
        </div>
    </header>

    {{-- Filter Toolbar --}}
    <div class="sf-toolbar">
        <div class="sf-filter-pills" role="group" aria-label="Filter by day">
            <button type="button" class="sf-filter-pill active" data-day-filter="all">All Days</button>
            @foreach($weekColumns as $dayName)
                <button type="button" class="sf-filter-pill" data-day-filter="{{ $dayName }}">{{ $shortDayMap[$dayName] }}</button>
            @endforeach
        </div>

        <div class="sf-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="search" id="scheduleSearchInput" class="sf-input" placeholder="Filter courses or rooms..." aria-label="Filter courses or rooms">
        </div>
    </div>

    {{-- Desktop Views (List + Weekly Grid) --}}
    <div class="sf-desktop-course-views">
        {{-- List View --}}
        <section class="sf-card" id="sfClassesListPanel" aria-label="Enrolled courses list">
            @forelse($coursesData as $course)
                <div class="sf-course-row"
                     role="button"
                     tabindex="0"
                     data-course-code="{{ $course['code'] }}"
                     data-days="{{ implode(',', $course['raw_days']) }}"
                     data-search="{{ strtolower($course['code'] . ' ' . $course['name'] . ' ' . $course['instructor'] . ' ' . $course['room']) }}">
                    <div class="sf-sched-left">
                        <span class="sf-code-chip is-rose">{{ $course['code'] }}</span>
                        <div class="sf-sched-copy">
                            <strong>{{ $course['name'] }}</strong>
                            <span>{{ $course['instructor'] }} · {{ $course['room'] }} · {{ $course['units'] }} units</span>
                        </div>
                    </div>
                    <div class="sf-course-meta-right">
                        <div class="sf-course-time-stack">
                            <strong>{{ $course['days_label'] }}</strong>
                            <span>{{ $course['time_range'] }}</span>
                        </div>
                        <span class="sf-pill sf-mono {{ $course['logged_count'] > 0 ? 'is-emerald' : '' }}">
                            {{ $course['logged_count'] }} logged
                        </span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sf-text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </div>
                </div>
            @empty
                <div class="sf-empty-state">
                    <span class="sf-empty-icon-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    </span>
                    <h3>No enrolled courses</h3>
                    <p>You are currently not registered for any subjects this semester.</p>
                </div>
            @endforelse
            <div class="sf-empty-state" id="sfListFilterEmpty" hidden>
                <h3>No matching courses</h3>
                <p>Try clearing your day filter or searching for another code, room, or instructor.</p>
            </div>
        </section>

        {{-- Weekly Grid View --}}
        <section class="sf-weekly-grid {{ $hasSaturday ? 'has-sat' : '' }}" id="sfClassesWeeklyPanel" aria-label="Weekly schedule grid" hidden>
            @foreach($weekColumns as $dayCol)
                @php
                    $dayItems = collect($coursesData)
                        ->flatMap(function ($c) use ($dayCol) {
                            return collect($c['slots'])
                                ->where('day', $dayCol)
                                ->map(fn ($slot) => array_merge($c, ['slot' => $slot]));
                        })
                        ->sortBy('slot.sort_time')
                        ->values();
                @endphp
                <div class="sf-week-col {{ $dayCol === $todayName ? 'is-today' : '' }}" data-week-col="{{ $dayCol }}">
                    <div class="sf-week-col-head">
                        <strong>{{ $dayCol }}</strong>
                        <span>{{ $dayItems->count() }}</span>
                    </div>
                    @forelse($dayItems as $item)
                        <button type="button"
                                class="sf-week-card"
                                data-course-code="{{ $item['code'] }}"
                                data-days="{{ $dayCol }}"
                                data-search="{{ strtolower($item['code'] . ' ' . $item['name'] . ' ' . $item['instructor'] . ' ' . $item['slot']['room']) }}">
                            <div class="sf-week-card-code">{{ $item['code'] }}</div>
                            <div class="sf-week-card-title">{{ $item['name'] }}</div>
                            <div class="sf-week-card-meta">
                                {{ $item['slot']['start_time'] }}{{ $item['slot']['end_time'] ? ' – ' . $item['slot']['end_time'] : '' }}<br>
                                {{ $item['slot']['room'] }}
                            </div>
                        </button>
                    @empty
                        <div style="font-size:11.5px;color:var(--sf-text-muted);text-align:center;padding:24px 6px;">
                            No classes
                        </div>
                    @endforelse
                </div>
            @endforeach
        </section>
    </div>

    {{-- Mobile Stacked Course Cards --}}
    <div class="sf-mobile-course-cards" id="sfMobileCoursesList">
        @forelse($coursesData as $course)
            <button type="button"
                    class="sf-mobile-course-card"
                    data-course-code="{{ $course['code'] }}"
                    data-days="{{ implode(',', $course['raw_days']) }}"
                    data-search="{{ strtolower($course['code'] . ' ' . $course['name'] . ' ' . $course['instructor'] . ' ' . $course['room']) }}">
                <div class="sf-mobile-course-card-top">
                    <span class="sf-code-chip is-rose">{{ $course['code'] }}</span>
                    <span class="sf-mono" style="font-size:11.5px;color:var(--sf-text-muted);">{{ $course['days_label'] }}</span>
                </div>
                <h2>{{ $course['name'] }}</h2>
                <p>{{ $course['room'] }} · {{ $course['instructor'] }}</p>
                <div class="sf-mono" style="font-size:11.5px;color:var(--sf-text-muted);">{{ $course['time_range'] }}</div>
            </button>
        @empty
            <div class="sf-card sf-empty-state">
                <h3>No enrolled courses</h3>
                <p>You are currently not registered for any subjects this semester.</p>
            </div>
        @endforelse
        <div class="sf-card sf-empty-state" id="sfMobileFilterEmpty" hidden>
            <h3>No matching courses</h3>
            <p>Try a different day filter or search query.</p>
        </div>
    </div>
</div>

{{-- Desktop Slide-Over Course Detail Drawer --}}
<div class="sf-drawer-backdrop" id="sfCourseDrawerBackdrop"></div>
<div class="sf-drawer" id="sfCourseDrawer" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="sfDrawerTitle">
    <div class="sf-drawer-head">
        <div style="display:flex;align-items:center;gap:8px;">
            <span class="sf-code-chip is-rose" id="sfDrawerCode">CODE</span>
            <span class="sf-pill" id="sfDrawerUnits">3 units</span>
        </div>
        <button type="button" class="sf-modal-close" id="sfDrawerCloseBtn" aria-label="Close course details">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>
    <div class="sf-drawer-body">
        <div>
            <h2 id="sfDrawerTitle" style="margin:0 0 4px;font-size:18px;font-weight:700;color:var(--sf-text);">Course Title</h2>
            <div id="sfDrawerInstructor" style="font-size:12.5px;color:var(--sf-text-secondary);">Instructor</div>
        </div>

        <div class="sf-drawer-info-grid">
            <div class="sf-info-tile">
                <small>Schedule</small>
                <strong id="sfDrawerDays">Mon/Wed</strong>
                <span class="sf-mono" id="sfDrawerTime">8:00 AM – 9:30 AM</span>
            </div>
            <div class="sf-info-tile">
                <small>Location</small>
                <strong id="sfDrawerRoom">Room</strong>
                <span id="sfDrawerSection">Section</span>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sf-btn sf-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/></svg>
                <span>Scan QR</span>
            </a>
            <a href="{{ route('mobile.scan', ['open_code' => 1]) }}" class="sf-btn sf-btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" x2="20" y1="9" y2="9"/><line x1="4" x2="20" y1="15" y2="15"/><line x1="10" x2="8" y1="3" y2="21"/><line x1="16" x2="14" y1="3" y2="21"/></svg>
                <span>Enter PIN</span>
            </a>
        </div>

        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <span style="font-size:11.5px;font-weight:600;color:var(--sf-text-secondary);" id="sfDrawerLogsHeading">Logged Check-ins (0)</span>
                <a href="{{ route('student.calendar') }}" id="sfDrawerAllLogsLink" class="sf-link">View in calendar →</a>
            </div>
            <div id="sfDrawerLogsList" style="display:flex;flex-direction:column;gap:7px;"></div>
        </div>
    </div>
</div>

{{-- Mobile Centered Course Detail Modal + Signature Circular Bottom Close Button --}}
<div class="sf-modal-backdrop" id="sfMobileCourseModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="sfMobCourseTitle">
    <div class="sf-modal-card">
        <div class="sf-modal-head">
            <div style="min-width:0;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px;">
                    <span class="sf-code-chip is-rose" id="sfMobCourseCode">CODE</span>
                    <span class="sf-pill" id="sfMobCourseUnits">3 units</span>
                </div>
                <h3 id="sfMobCourseTitle">Course Title</h3>
                <p id="sfMobCourseInstructor">Instructor</p>
            </div>
        </div>
        <div class="sf-modal-body" style="display:flex;flex-direction:column;gap:12px;">
            <div class="sf-drawer-info-grid">
                <div class="sf-info-tile">
                    <small>Schedule</small>
                    <strong id="sfMobCourseDays">Mon/Wed</strong>
                    <span class="sf-mono" id="sfMobCourseTime">Time</span>
                </div>
                <div class="sf-info-tile">
                    <small>Room</small>
                    <strong id="sfMobCourseRoom">Room</strong>
                    <span id="sfMobCourseLogged">0 logged</span>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sf-btn sf-btn-primary">Scan QR</a>
                <a href="{{ route('mobile.scan', ['open_code' => 1]) }}" class="sf-btn sf-btn-secondary">Enter PIN</a>
            </div>
            <div id="sfMobCourseLogs" style="display:flex;flex-direction:column;gap:6px;"></div>
        </div>
    </div>
    <button type="button" class="sf-modal-circle-close" data-close-modal="sfMobileCourseModal" aria-label="Close course details">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
</div>
@endsection

@section('scripts')
<script @cspNonce>
(function () {
    'use strict';
    const courses = @json($coursesData);
    const byCode = Object.fromEntries(courses.map((c) => [c.code, c]));
    const calendarBaseUrl = @json(route('student.calendar'));

    let activeDay = 'all';
    const searchInput = document.getElementById('scheduleSearchInput');
    const dayBtns = document.querySelectorAll('[data-day-filter]');
    const listRows = Array.from(document.querySelectorAll('.sf-course-row'));
    const weekCards = Array.from(document.querySelectorAll('.sf-week-card'));
    const mobileCards = Array.from(document.querySelectorAll('.sf-mobile-course-card'));

    // View mode switcher (List | Weekly Grid)
    const listPanel = document.getElementById('sfClassesListPanel');
    const weeklyPanel = document.getElementById('sfClassesWeeklyPanel');
    const listBtn = document.getElementById('sfClassesViewListBtn');
    const gridBtn = document.getElementById('sfClassesViewGridBtn');

    function setClassesView(mode) {
        const isWeekly = mode === 'weekly';
        if (listPanel) listPanel.hidden = isWeekly;
        if (weeklyPanel) weeklyPanel.hidden = !isWeekly;
        if (listBtn) {
            listBtn.classList.toggle('active', !isWeekly);
            listBtn.setAttribute('aria-pressed', !isWeekly ? 'true' : 'false');
        }
        if (gridBtn) {
            gridBtn.classList.toggle('active', isWeekly);
            gridBtn.setAttribute('aria-pressed', isWeekly ? 'true' : 'false');
        }
        try { localStorage.setItem('sfClassesView', isWeekly ? 'weekly' : 'list'); } catch (e) {}
    }

    listBtn?.addEventListener('click', () => setClassesView('list'));
    gridBtn?.addEventListener('click', () => setClassesView('weekly'));

    const urlParams = new URLSearchParams(window.location.search);
    const urlView = urlParams.get('view');
    try {
        const saved = localStorage.getItem('sfClassesView');
        if (urlView === 'weekly' || urlView === 'list') setClassesView(urlView);
        else if (saved === 'weekly') setClassesView('weekly');
    } catch (e) {}

    // Filter engine
    function applyFilter() {
        const q = (searchInput?.value || '').trim().toLowerCase();
        let listVisible = 0;
        let mobileVisible = 0;

        listRows.forEach((row) => {
            const days = (row.dataset.days || '').split(',');
            const matchDay = activeDay === 'all' || days.includes(activeDay);
            const matchQ = !q || (row.dataset.search || '').includes(q);
            const ok = matchDay && matchQ;
            row.hidden = !ok;
            if (ok) listVisible++;
        });

        mobileCards.forEach((card) => {
            const days = (card.dataset.days || '').split(',');
            const matchDay = activeDay === 'all' || days.includes(activeDay);
            const matchQ = !q || (card.dataset.search || '').includes(q);
            const ok = matchDay && matchQ;
            card.hidden = !ok;
            if (ok) mobileVisible++;
        });

        weekCards.forEach((card) => {
            const days = (card.dataset.days || '').split(',');
            const matchDay = activeDay === 'all' || days.includes(activeDay);
            const matchQ = !q || (card.dataset.search || '').includes(q);
            card.hidden = !(matchDay && matchQ);
        });

        const listEmpty = document.getElementById('sfListFilterEmpty');
        if (listEmpty) listEmpty.hidden = listRows.length === 0 || listVisible > 0;
        const mobEmpty = document.getElementById('sfMobileFilterEmpty');
        if (mobEmpty) mobEmpty.hidden = mobileCards.length === 0 || mobileVisible > 0;
    }

    dayBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            activeDay = btn.getAttribute('data-day-filter') || 'all';
            dayBtns.forEach((b) => b.classList.toggle('active', b === btn));
            applyFilter();
        });
    });
    searchInput?.addEventListener('input', applyFilter);

    // Course Detail Drawer / Mobile Modal
    const drawer = document.getElementById('sfCourseDrawer');
    const drawerBackdrop = document.getElementById('sfCourseDrawerBackdrop');

    function renderLogNodes(container, records) {
        container.replaceChildren();
        if (!records || !records.length) {
            const empty = document.createElement('div');
            empty.style.cssText = 'padding:14px;border-radius:10px;border:1px dashed var(--sf-border-strong);color:var(--sf-text-muted);font-size:12px;text-align:center;';
            empty.textContent = 'No check-ins recorded for this course yet.';
            container.appendChild(empty);
            return;
        }
        records.forEach((rec) => {
            const row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 11px;border-radius:9px;background:var(--sf-surface-2);border:1px solid var(--sf-border);font-size:12px;';
            const left = document.createElement('div');
            left.className = 'sf-mono';
            left.style.color = 'var(--sf-text-soft)';
            left.textContent = rec.date + (rec.time_in ? ' · ' + rec.time_in : '');
            const pill = document.createElement('span');
            const tone = rec.status === 'Present' ? 'is-emerald' : (rec.status === 'Late' ? 'is-amber' : (rec.status === 'Excused' ? 'is-sky' : 'is-danger'));
            pill.className = 'sf-pill ' + tone;
            pill.textContent = rec.status;
            row.append(left, pill);
            container.appendChild(row);
        });
    }

    function openCourseDetail(code) {
        const course = byCode[code];
        if (!course) return;
        const isMobile = window.matchMedia('(max-width: 767.98px)').matches;

        if (isMobile) {
            document.getElementById('sfMobCourseCode').textContent = course.code;
            document.getElementById('sfMobCourseUnits').textContent = course.units + ' units';
            document.getElementById('sfMobCourseTitle').textContent = course.name;
            document.getElementById('sfMobCourseInstructor').textContent = course.instructor;
            document.getElementById('sfMobCourseDays').textContent = course.days_label;
            document.getElementById('sfMobCourseTime').textContent = course.time_range;
            document.getElementById('sfMobCourseRoom').textContent = course.room;
            document.getElementById('sfMobCourseLogged').textContent = course.logged_count + ' logged';
            renderLogNodes(document.getElementById('sfMobCourseLogs'), course.recent_records);
            window.openSfModal?.('sfMobileCourseModal');
            return;
        }

        document.getElementById('sfDrawerCode').textContent = course.code;
        document.getElementById('sfDrawerUnits').textContent = course.units + ' units';
        document.getElementById('sfDrawerTitle').textContent = course.name;
        document.getElementById('sfDrawerInstructor').textContent = course.instructor;
        document.getElementById('sfDrawerDays').textContent = course.days_label;
        document.getElementById('sfDrawerTime').textContent = course.time_range;
        document.getElementById('sfDrawerRoom').textContent = course.room;
        document.getElementById('sfDrawerSection').textContent = course.section === 'Unassigned' ? 'Section TBA' : ('Section ' + course.section);
        document.getElementById('sfDrawerLogsHeading').textContent = 'Logged Check-ins (' + course.logged_count + ')';
        const logsLink = document.getElementById('sfDrawerAllLogsLink');
        if (logsLink) logsLink.href = calendarBaseUrl + '?q=' + encodeURIComponent(course.code) + '#attendance-records';
        renderLogNodes(document.getElementById('sfDrawerLogsList'), course.recent_records);

        drawer?.classList.add('open');
        drawer?.setAttribute('aria-hidden', 'false');
        drawerBackdrop?.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    window.closeCourseDrawer = function () {
        drawer?.classList.remove('open');
        drawer?.setAttribute('aria-hidden', 'true');
        drawerBackdrop?.classList.remove('open');
        if (!document.querySelector('.sf-modal-backdrop.open')) {
            document.body.style.overflow = '';
        }
    };

    document.getElementById('sfDrawerCloseBtn')?.addEventListener('click', window.closeCourseDrawer);
    drawerBackdrop?.addEventListener('click', window.closeCourseDrawer);

    document.querySelectorAll('[data-course-code]').forEach((el) => {
        el.addEventListener('click', () => openCourseDetail(el.getAttribute('data-course-code')));
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openCourseDetail(el.getAttribute('data-course-code'));
            }
        });
    });

    // Deep-link from Command Palette (?course=CODE)
    const deepCourse = urlParams.get('course');
    if (deepCourse && byCode[deepCourse]) {
        setTimeout(() => openCourseDetail(deepCourse), 120);
    }
})();
</script>
@endsection
