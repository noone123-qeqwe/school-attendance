@extends('layouts.app')
@section('portal-title', 'Classes & Schedule')
@section('page-title', 'Classes & Schedule')

@section('content')
@php
    /** @var \App\Models\User $user */
    $user = $user ?? auth()->user();

    // ── SCHEDULE DAY HELPER ──
    $todayName = now()->format('l');

    $formatDaysAbbr = function (array $days): string {
        $order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $presentDays = array_values(array_intersect($order, $days));
        if (empty($presentDays)) return 'TBA';

        if ($presentDays === ['Tuesday', 'Thursday']) return 'TTH';
        if ($presentDays === ['Monday', 'Wednesday', 'Friday']) return 'MWF';
        if ($presentDays === ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']) return 'M-F';
        if ($presentDays === ['Monday', 'Wednesday']) return 'MW';
        if ($presentDays === ['Tuesday', 'Wednesday', 'Thursday']) return 'TWTH';
        if ($presentDays === ['Saturday']) return 'SAT';
        if ($presentDays === ['Sunday']) return 'SUN';
        if ($presentDays === ['Monday']) return 'MON';
        if ($presentDays === ['Tuesday']) return 'TUE';
        if ($presentDays === ['Wednesday']) return 'WED';
        if ($presentDays === ['Thursday']) return 'THU';
        if ($presentDays === ['Friday']) return 'FRI';

        $short = [
            'Monday'=>'M', 'Tuesday'=>'T', 'Wednesday'=>'W',
            'Thursday'=>'TH', 'Friday'=>'F', 'Saturday'=>'SAT', 'Sunday'=>'SUN'
        ];
        return implode('', array_map(fn($d) => $short[$d] ?? $d, $presentDays));
    };

    // ── FLATTEN / GROUP SUBJECTS & SCHEDULES ──
    $groupedSchedules = [];
    $rowNumber = 1;

    foreach ($subjects as $subject) {
        $subjectSchedules = $subject->schedules ?? collect();
        $instructorName = $subject->instructorUser?->name ?? $subject->instructor ?? 'TBA';
        $units = (float) ($subject->units ?: 3.0);
        $rawSection = $subject->section ?: ($user->section ?: 'Not assigned');

        if ($subjectSchedules->count() > 0) {
            // Group schedule slots by time and room
            $slots = [];
            foreach ($subjectSchedules as $sched) {
                $key = ($sched->start_time ?? '') . '|' . ($sched->end_time ?? '') . '|' . ($sched->room ?? '');
                if (!isset($slots[$key])) {
                    $slots[$key] = [
                        'days'       => [],
                        'start_time' => $sched->start_time,
                        'end_time'   => $sched->end_time,
                        'room'       => $sched->room ?? null,
                    ];
                }
                $slots[$key]['days'][] = $sched->day;
            }

            $slotIndex = 0;
            foreach ($slots as $slot) {
                $daysAbbr = $formatDaysAbbr($slot['days']);
                $isToday = in_array($todayName, $slot['days']);
                $classNum = str_pad((string) (20 + (($subject->id * 3 + $slotIndex) % 70)), 2, '0', STR_PAD_LEFT);
                $currentRow = $rowNumber++;

                // If rawSection already contains leading row numbers, keep it; else prefix row number
                $sectionLabel = preg_match('/^\d+\s+/', $rawSection) ? $rawSection : ($currentRow . ' ' . $rawSection);

                $groupedSchedules[] = (object) [
                    'row_num'         => $currentRow,
                    'subject_id'      => $subject->id,
                    'section'         => $sectionLabel,
                    'raw_section'     => $rawSection,
                    'code'            => $subject->code,
                    'name'            => $subject->name,
                    'class_number'    => $classNum,
                    'units'           => $slotIndex === 0 ? number_format($units, 1) : '—',
                    'raw_units'       => $slotIndex === 0 ? $units : 0,
                    'start_time'      => $slot['start_time'],
                    'end_time'        => $slot['end_time'],
                    'days'            => $daysAbbr,
                    'raw_days'        => $slot['days'],
                    'is_today'        => $isToday,
                    'room'            => !empty($slot['room']) ? $slot['room'] : 'TBA',
                    'teacher'         => $instructorName,
                ];
                $slotIndex++;
            }
        } else {
            // Subject without specified schedule
            $currentRow = $rowNumber++;
            $sectionLabel = preg_match('/^\d+\s+/', $rawSection) ? $rawSection : ($currentRow . ' ' . $rawSection);

            $groupedSchedules[] = (object) [
                'row_num'         => $currentRow,
                'subject_id'      => $subject->id,
                'section'         => $sectionLabel,
                'raw_section'     => $rawSection,
                'code'            => $subject->code,
                'name'            => $subject->name,
                'class_number'    => str_pad((string) (20 + (($subject->id * 3) % 70)), 2, '0', STR_PAD_LEFT),
                'units'           => number_format($units, 1),
                'raw_units'       => $units,
                'start_time'      => null,
                'end_time'        => null,
                'days'            => 'TBA',
                'raw_days'        => [],
                'is_today'        => false,
                'room'            => 'TBA',
                'teacher'         => $instructorName,
            ];
        }
    }

@endphp

@if(blank($user->section))
<x-alert-banner role="status" class="mb-3" alert-key="missing-class-section" version="unassigned"
                dismiss-label="Dismiss class section notice"
                style="display:flex;gap:12px;align-items:flex-start;padding:13px 16px;border-radius:10px;border:1px solid rgba(212, 175, 55,0.34);background:#1E1515;color:#FAF8F5;">
    <i class="bi bi-info-circle-fill" style="color:#D4AF37;margin-top:2px;" aria-hidden="true"></i>
    <div><strong>Your section is not assigned yet.</strong><br><span style="color:#D6CCC6;">Section-based classes appear after the school assigns your section. Contact the registrar or school administrator to update your student profile.</span></div>
</x-alert-banner>
@else
<span hidden data-alert-resolved="missing-class-section"></span>
@endif

<div class="container-fluid p-3 p-md-4 cor-page-wrapper">

    <!-- Screen Action Header -->
    <div class="cor-actions-bar no-print">
        <div class="cor-page-title-group">
            <h1>Classes &amp; Schedule</h1>
            <p>Your enrolled subjects and weekly class timetable</p>
        </div>
        @if(!empty($hasActiveAssistantAssignment))
            <a href="{{ route('student-assistant.classes') }}" class="btn btn-outline-light d-inline-flex d-md-none align-items-center gap-2 px-3 py-2">
                <i class="bi bi-person-check" aria-hidden="true"></i>
                Student Assistant
            </a>
        @endif
    </div>

    <!-- ── THE SCHEDULE SHEET ── -->
    <div class="cor-sheet" id="corCertificateSheet">
        <div class="cor-content-relative">

            <!-- 3. Screen Controls Toolbar (Open on Desktop, Progressive Disclosure on Mobile) -->
            <details class="sd-filter-disclosure cor-mobile-filter-disclosure no-print mb-2" data-open-desktop="true" id="corFilterDisclosure">
                <summary>
                    <i class="bi bi-sliders" aria-hidden="true"></i>
                    <span>Search &amp; Filter</span>
                    <span class="sd-disclosure-pill is-accent" id="corActiveFilterPill" hidden></span>
                    <i class="bi bi-chevron-down sd-disclosure-chevron" aria-hidden="true"></i>
                </summary>
                <div class="cor-toolbar no-print mt-2 mt-md-0">
                    <label class="student-filter-field" for="scheduleSearchInput">Find a class
                        <span class="position-relative d-block w-100">
                            <i class="bi bi-search sd-search-icon" aria-hidden="true"></i>
                            <input type="search" id="scheduleSearchInput" class="cor-search-input" placeholder="Subject, teacher, or room" aria-label="Filter schedule records">
                        </span>
                    </label>
                    <label class="student-filter-field" for="scheduleDayFilter">Day
                        <select id="scheduleDayFilter">
                            <option value="all">All days</option>
                            <option value="today">Today</option>
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="sd-view-toggle d-none d-lg-inline-flex" role="group" aria-label="Classes layout mode">
                        <button type="button" class="sd-view-toggle-btn is-active" id="corViewCardsBtn" data-cor-view="cards" aria-pressed="true">
                            <i class="bi bi-grid-fill" aria-hidden="true"></i> Cards
                        </button>
                        <button type="button" class="sd-view-toggle-btn" id="corViewTableBtn" data-cor-view="table" aria-pressed="false">
                            <i class="bi bi-table" aria-hidden="true"></i> COR Table
                        </button>
                        <button type="button" class="sd-view-toggle-btn" id="corViewWeeklyBtn" data-cor-view="weekly" aria-pressed="false">
                            <i class="bi bi-calendar-week" aria-hidden="true"></i> Weekly
                        </button>
                    </div>
                </div>
            </details>
            <p class="student-results-count no-print" id="scheduleResultsStatus" role="status">{{ count($groupedSchedules) }} classes</p>

            <!-- 4. Class Schedule Cards View (Primary Mobile Layout) -->
            <div class="cor-mobile-cards-view" id="corMobileCardsView">
                @forelse($groupedSchedules as $sched)
                    <div class="cor-card-item {{ $sched->is_today ? 'is-today' : '' }}" 
                         data-days="{{ implode(',', $sched->raw_days) }}" 
                         data-is-today="{{ $sched->is_today ? '1' : '0' }}">
                        
                        <!-- Top Meta Bar -->
                        <div class="cor-card-top-bar">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="cor-code-badge">{{ $sched->code }}</span>
                                @if($sched->is_today)
                                    <span class="cor-today-indicator"><i class="bi bi-dot"></i> Today</span>
                                @endif
                            </div>
                            <span class="cor-card-units-tag">
                                <span class="cor-card-section-tag">Sec {{ $sched->section }}</span>
                                · {{ $sched->units !== '—' ? $sched->units . ' Units' : 'Lab/Lec' }}
                            </span>
                        </div>

                        <!-- Subject Title Block -->
                        <div class="cor-card-subj-block">
                            <h4 class="cor-card-subj-title">
                                {{ $sched->name }}
                            </h4>
                        </div>

                        <!-- 2x2 Mini Schedule Matrix Grid with Borders -->
                        <div class="cor-card-sched-matrix">
                            <div class="cor-card-matrix-cell">
                                <div class="cor-card-matrix-label">Time</div>
                                <div class="cor-card-matrix-value cor-time-text">
                                    @if($sched->start_time && $sched->end_time)
                                        {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                                    @else
                                        TBA
                                    @endif
                                </div>
                            </div>

                            <div class="cor-card-matrix-cell">
                                <div class="cor-card-matrix-label">Day</div>
                                <div class="cor-card-matrix-value">
                                    <span class="cor-day-badge">{{ $sched->days }}</span>
                                </div>
                            </div>

                            @php
                                $hasRoom = !empty($sched->room) && strtoupper(trim((string) $sched->room)) !== 'TBA';
                            @endphp
                            @if($hasRoom)
                                <div class="cor-card-matrix-cell">
                                    <div class="cor-card-matrix-label">Room</div>
                                    <div class="cor-card-matrix-value">
                                        <span class="cor-room-badge">{{ $sched->room }}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="cor-card-matrix-cell {{ !$hasRoom ? 'cor-card-matrix-cell--full' : '' }}">
                                <div class="cor-card-matrix-label">Teacher</div>
                                <div class="cor-card-matrix-value" title="{{ $sched->teacher }}">
                                    {{ $sched->teacher }}
                                </div>
                            </div>
                        </div>

                    </div>
                @empty
                    <div class="cor-empty-state">
                        <i class="bi bi-journal-x cor-empty-icon"></i>
                        <h5>No Enrolled Subjects</h5>
                        <p class="mb-0">You are currently not registered for any classes this semester.</p>
                    </div>
                @endforelse
                <div id="scheduleFilterEmpty" class="student-filter-empty" hidden><strong>No matching classes</strong>Try a different day, subject, teacher, or room.</div>
            </div>

            <!-- 5. Desktop COR Table View -->
            <div class="cor-table-wrap d-none no-print" id="corDesktopTableView">
                <table class="cor-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Subject Description</th>
                            <th>Units</th>
                            <th>Day(s)</th>
                            <th>Time</th>
                            <th>Room</th>
                            <th>Instructor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupedSchedules as $sched)
                            <tr class="cor-table-row" data-days="{{ implode(',', $sched->raw_days) }}" data-is-today="{{ $sched->is_today ? '1' : '0' }}">
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span class="cor-code-badge">{{ $sched->code }}</span>
                                        <span style="font-size:0.72rem; color:#ACA099;">Sec {{ $sched->section }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:700; color:#FAF8F5;">{{ $sched->name }}</div>
                                    @if($sched->is_today)
                                        <span class="cor-today-indicator mt-1 d-inline-flex"><i class="bi bi-dot"></i> Today</span>
                                    @endif
                                </td>
                                <td style="font-variant-numeric: tabular-nums; font-weight:700;">{{ $sched->units !== '—' ? $sched->units : '—' }}</td>
                                <td><span class="cor-day-badge">{{ $sched->days }}</span></td>
                                <td class="cor-time-text" style="white-space:nowrap; font-variant-numeric: tabular-nums;">
                                    @if($sched->start_time && $sched->end_time)
                                        {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                                    @else
                                        TBA
                                    @endif
                                </td>
                                <td>
                                    @if($sched->room && $sched->room !== 'TBA')
                                        <span class="cor-room-badge">{{ $sched->room }}</span>
                                    @else
                                        <span class="cor-tba-label">TBA</span>
                                    @endif
                                </td>
                                <td style="color:#D8CEC8;">{{ $sched->teacher }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- 6. Desktop Weekly Timetable View -->
            @php
                $todayNameCor = now()->format('l');
                $weekDaysList = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            @endphp
            <div class="sd-cor-weekly-grid d-none no-print" id="corWeeklyGridView">
                @foreach($weekDaysList as $wDay)
                    @php
                        $dayClasses = collect($groupedSchedules)
                            ->filter(fn ($s) => in_array($wDay, $s->raw_days ?? [], true))
                            ->sortBy('start_time')
                            ->values();
                    @endphp
                    <div class="sd-cor-week-day {{ $wDay === $todayNameCor ? 'is-today-day' : '' }}" data-week-day="{{ $wDay }}">
                        <div class="sd-cor-week-head">
                            <span>{{ $wDay }}</span>
                            @if($wDay === $todayNameCor)
                                <span class="cor-today-indicator"><i class="bi bi-dot"></i> Today</span>
                            @else
                                <span style="font-size:0.74rem; color:#ACA099; font-weight:600;">{{ $dayClasses->count() }} {{ Str::plural('class', $dayClasses->count()) }}</span>
                            @endif
                        </div>
                        @forelse($dayClasses as $dc)
                            <div class="sd-sched-grid-card cor-week-item" data-days="{{ implode(',', $dc->raw_days) }}" data-is-today="{{ $dc->is_today ? '1' : '0' }}">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                    <span style="font-size: 0.77rem; font-weight: 700; color: #D4AF37; font-variant-numeric: tabular-nums;">
                                        @if($dc->start_time && $dc->end_time)
                                            {{ \Carbon\Carbon::parse($dc->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($dc->end_time)->format('g:i A') }}
                                        @else
                                            TBA
                                        @endif
                                    </span>
                                    @if($dc->room && $dc->room !== 'TBA')
                                        <span class="cor-room-badge" style="padding:2px 8px; font-size:0.7rem;">{{ $dc->room }}</span>
                                    @endif
                                </div>
                                <div style="font-weight: 700; color: #FAF8F5; font-size: 0.9rem; line-height: 1.3;">{{ $dc->name }}</div>
                                <div class="d-flex align-items-center justify-content-between gap-2 mt-1" style="font-size: 0.76rem; color: #ACA099;">
                                    <span style="color: #D4AF37; font-weight: 700;">{{ $dc->code }}</span>
                                    <span class="text-truncate" title="{{ $dc->teacher }}">{{ $dc->teacher }}</span>
                                </div>
                            </div>
                        @empty
                            <div style="color:#ACA099; font-size:0.82rem; padding:14px 4px; text-align:center;">No scheduled classes</div>
                        @endforelse
                    </div>
                @endforeach
            </div>

        </div>
    </div>

</div>

<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    const search = document.getElementById('scheduleSearchInput');
    const day = document.getElementById('scheduleDayFilter');
    const cards = Array.from(document.querySelectorAll('.cor-card-item'));
    const tableRows = Array.from(document.querySelectorAll('.cor-table-row'));
    const weekItems = Array.from(document.querySelectorAll('.cor-week-item'));
    const cardsWrap = document.getElementById('corMobileCardsView');
    const tableWrap = document.getElementById('corDesktopTableView');
    const weeklyWrap = document.getElementById('corWeeklyGridView');
    const cardsBtn = document.getElementById('corViewCardsBtn');
    const tableBtn = document.getElementById('corViewTableBtn');
    const weeklyBtn = document.getElementById('corViewWeeklyBtn');

    function setCorView(mode) {
        const isDesktop = window.innerWidth >= 992;
        const resolved = !isDesktop ? 'cards' : ((mode === 'table' || mode === 'weekly') ? mode : 'cards');
        if (cardsWrap) cardsWrap.classList.toggle('d-none', resolved !== 'cards');
        if (tableWrap) tableWrap.classList.toggle('d-none', resolved !== 'table');
        if (weeklyWrap) weeklyWrap.classList.toggle('d-none', resolved !== 'weekly');
        if (cardsBtn) {
            cardsBtn.classList.toggle('is-active', resolved === 'cards');
            cardsBtn.setAttribute('aria-pressed', resolved === 'cards' ? 'true' : 'false');
        }
        if (tableBtn) {
            tableBtn.classList.toggle('is-active', resolved === 'table');
            tableBtn.setAttribute('aria-pressed', resolved === 'table' ? 'true' : 'false');
        }
        if (weeklyBtn) {
            weeklyBtn.classList.toggle('is-active', resolved === 'weekly');
            weeklyBtn.setAttribute('aria-pressed', resolved === 'weekly' ? 'true' : 'false');
        }
        try { if (isDesktop) localStorage.setItem('sdClassesView', resolved); } catch (e) {}
    }

    if (cardsBtn) cardsBtn.addEventListener('click', function() { setCorView('cards'); });
    if (tableBtn) tableBtn.addEventListener('click', function() { setCorView('table'); });
    if (weeklyBtn) weeklyBtn.addEventListener('click', function() { setCorView('weekly'); });
    const urlView = new URLSearchParams(window.location.search).get('view');
    try {
        const saved = localStorage.getItem('sdClassesView');
        if (urlView === 'weekly' || urlView === 'table' || urlView === 'cards') {
            setCorView(urlView);
        } else if (saved === 'table' || saved === 'cards' || saved === 'weekly') {
            setCorView(saved);
        }
    } catch (e) {}
    window.addEventListener('resize', function() {
        if (window.innerWidth < 992) {
            setCorView('cards');
        }
    });

    function applyScheduleFilter() {
        const query = search.value.trim().toLowerCase();
        let visible = 0;
        cards.forEach((card, idx) => {
            const matchesDay = day.value === 'all' || (day.value === 'today' ? card.dataset.isToday === '1' : (card.dataset.days || '').split(',').includes(day.value));
            const isMatch = matchesDay && card.textContent.toLowerCase().includes(query);
            card.hidden = !isMatch;
            if (tableRows[idx]) tableRows[idx].hidden = !isMatch;
            if (isMatch) visible++;
        });
        weekItems.forEach((item) => {
            const matchesDay = day.value === 'all' || (day.value === 'today' ? item.dataset.isToday === '1' : (item.dataset.days || '').split(',').includes(day.value));
            item.hidden = !(matchesDay && item.textContent.toLowerCase().includes(query));
        });
        document.getElementById('scheduleResultsStatus').textContent = visible + (visible === 1 ? ' class' : ' classes');
        document.getElementById('scheduleFilterEmpty').hidden = cards.length === 0 || visible > 0;
        const activeFilterPill = document.getElementById('corActiveFilterPill');
        if (activeFilterPill) {
            const activeCount = (query ? 1 : 0) + (day.value !== 'all' ? 1 : 0);
            activeFilterPill.hidden = activeCount === 0;
            activeFilterPill.textContent = activeCount > 0 ? (activeCount + ' active') : '';
        }
    }
    search.addEventListener('input', applyScheduleFilter);
    day.addEventListener('change', applyScheduleFilter);
    applyScheduleFilter();
});
</script>
@endsection
