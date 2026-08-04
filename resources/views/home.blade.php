@extends('layouts.app')

@section('content')

@php
    $calYear   = request('cal_year',  now()->year);
    $calMonth  = request('cal_month', now()->month);
    $calStart  = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $calEnd    = $calStart->copy()->endOfMonth();
    $prevMonth = $calStart->copy()->subMonth();
    $nextMonth = $calStart->copy()->addMonth();

    $monthRecords = $records->filter(fn($r) =>
        \Carbon\Carbon::parse($r->date)->year  == $calYear &&
        \Carbon\Carbon::parse($r->date)->month == $calMonth
    );

    $dayRecordsMap = [];
    foreach ($monthRecords as $r) {
        $day = (int) \Carbon\Carbon::parse($r->date)->format('j');
        $dayRecordsMap[$day][] = $r;
    }

    $dayDotsMap = [];
    foreach ($dayRecordsMap as $day => $recs) {
        $dots = [];
        $statuses = collect($recs)->pluck('status')->unique();
        if ($statuses->contains('Present')) $dots[] = 'present';
        if ($statuses->contains('Late'))    $dots[] = 'late';
        if ($statuses->contains('Absent'))  $dots[] = 'absent';
        $dayDotsMap[$day] = $dots;
    }

    $calendarJson = [];
    foreach ($dayRecordsMap as $day => $recs) {
        $dateKey = \Carbon\Carbon::create($calYear, $calMonth, $day)->format('Y-m-d');
        $calendarJson[$dateKey] = collect($recs)->map(fn($r) => [
            'subject' => $r->subject->name ?? $r->subject_code,
            'code'    => $r->subject_code,
            'status'  => $r->status,
            'time_in' => $r->time_in ? \Carbon\Carbon::parse($r->time_in)->format('h:i A') : null,
        ])->values()->toArray();
    }

    $today     = now()->day;
    $todayFull = now()->toDateString();
    $isCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $startDow  = $calStart->dayOfWeek;

    $noClassDays = [];
    for ($d = 1; $d <= $calEnd->day; $d++) {
        if (\Carbon\Carbon::create($calYear, $calMonth, $d)->dayOfWeek === 0) {
            $noClassDays[] = $d;
        }
    }
    $isLatestMonth = $isCurrentMonth;

    $totalRecords = $records->count();
    $greetHour = now()->hour;
    $greeting = $greetHour < 12 ? 'Good Morning' : ($greetHour < 17 ? 'Good Afternoon' : 'Good Evening');
@endphp

<!-- ATTENDANCE DATE MODAL -->
<div class="att-modal-overlay" id="attModal">
    <div class="att-modal">
        <div class="att-modal-header">
            <div class="att-modal-title" id="modalDate">—</div>
            <button class="att-modal-close" onclick="closeAttModal()">✕</button>
        </div>
        <div class="att-modal-summary" id="modalSummary"></div>
        <div class="att-modal-list" id="modalList"></div>
    </div>
</div>

<div class="container-fluid" style="max-width: 1200px;">

    @if(session('error'))
    <div class="ent-alert danger ent-fade-up">
        <div class="ent-alert-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
        <div class="ent-alert-body">
            <div class="ent-alert-text" style="color:#fee2e2;font-weight:600;">{{ session('error') }}</div>
        </div>
    </div>
    @endif

    <!-- ACTIVE WARNINGS BANNER -->
    @if(isset($activeWarnings) && $activeWarnings->count() > 0)
    <div class="ent-section ent-fade-up ent-mb-lg" style="border-color:rgba(239,68,68,0.25);background:linear-gradient(135deg,rgba(220,38,38,0.08) 0%,rgba(153,27,27,0.03) 100%);">
        <div class="ent-section-body" style="position:relative;">
            <div style="position:absolute;right:20px;top:50%;transform:translateY(-50%);font-size:4rem;opacity:0.04;pointer-events:none;">⚠️</div>
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
                <div class="ent-kpi-icon" style="background:rgba(239,68,68,0.15);color:var(--ent-danger);width:44px;height:44px;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:1.2rem;"></i>
                </div>
                <div>
                    <div style="font-size:1.05rem;font-weight:800;color:#fca5a5;">Action Required: Attendance Warning</div>
                    <div style="font-size:0.82rem;color:var(--ent-text-secondary);">You have {{ $activeWarnings->count() }} active warning(s). Please review your attendance immediately.</div>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($activeWarnings as $warning)
                <div style="background:rgba(0,0,0,0.2);padding:12px 16px;border-radius:var(--ent-radius-md);border:1px solid rgba(239,68,68,0.1);">
                    <div class="ent-flex-between" style="margin-bottom:4px;">
                        <span style="color:#fca5a5;font-weight:700;font-size:0.85rem;"><i class="bi bi-exclamation-triangle-fill" style="margin-right:4px;"></i>{{ $warning->subject_code }}</span>
                        <span class="ent-text-muted" style="font-size:0.7rem;">{{ $warning->created_at->diffForHumans() }}</span>
                    </div>
                    <div style="font-size:0.82rem;color:var(--ent-text-secondary);line-height:1.5;">{{ $warning->message }}</div>
                </div>
                @endforeach
            </div>
            <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
                <a href="{{ route('excuses') }}" class="ent-btn ent-btn-primary" style="background:linear-gradient(135deg,#dc2626,#991b1b);border-color:rgba(220,38,38,0.3);">
                    <i class="bi bi-file-text-fill"></i> Submit Excuse
                </a>
                <a href="{{ route('attendance.records') }}" class="ent-btn ent-btn-secondary">
                    View Records
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- MOBILE COMPACT HEADER (Only on mobile) -->
    <div class="mobile-dash-header d-md-none" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <div>
            <div class="mobile-dash-title" style="font-size:1.45rem; font-weight:800; line-height:1.2; letter-spacing:-0.5px;">
                {{ $greeting }},<br>{{ explode(' ', Auth::user()->name)[0] }}!
            </div>
            <div class="mobile-dash-subtitle" style="font-size:0.85rem; color:var(--text-secondary); margin-top:4px;">
                {{ now()->format('l, M j') }}
            </div>
        </div>
        <div style="background: rgba(207,164,111,0.15); color: var(--gold); padding: 10px 16px; border-radius: 14px; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.2);">
            <div style="font-size:0.65rem; text-transform:uppercase; font-weight:700; letter-spacing:0.5px; opacity:0.9;">Present</div>
            <div style="font-size:1.3rem; font-weight:800; line-height:1.1; margin-top:2px;">{{ $totalPresent }}</div>
        </div>
    </div>

    <!-- HERO BANNER (Desktop only) -->
    <div class="hero-banner fade-up fade-up-1 d-none d-md-flex" style="margin-bottom: 24px;">
        <div class="hero-icon">🎓</div>
        <div>
            <div class="hero-greeting">{{ $greeting }}</div>
            <div class="hero-title">{{ Auth::user()->name }}</div>
            <div class="hero-sub">
                {{ Auth::user()->course }} — Year {{ Auth::user()->year_level }}, Semester {{ Auth::user()->semester }}
            </div>
        </div>
        <div class="hero-time" style="margin-left: auto;">
            <i class="bi bi-clock"></i>
            <span id="liveTime">{{ now()->format('h:i A') }}</span>
            &nbsp;·&nbsp; {{ now()->format('l, F j, Y') }}
        </div>
    </div>

    <!-- HOLIDAY NOTIFICATION -->
    @if($todayHoliday)
    <div class="ent-section ent-fade-up ent-mb-lg" style="border-color:rgba(220,38,38,0.15);background:linear-gradient(135deg,rgba(220,38,38,0.08),rgba(190,18,60,0.03));">
        <div class="ent-section-body" style="position:relative;">
            <div style="position:absolute;right:20px;top:50%;transform:translateY(-50%);font-size:3rem;opacity:0.08;">🎉</div>
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:8px;">
                <div class="ent-kpi-icon" style="background:rgba(239,68,68,0.12);color:#fca5a5;width:40px;height:40px;">
                    <span style="font-size:1.2rem;">🏖️</span>
                </div>
                <div>
                    <div style="font-size:1.05rem;font-weight:800;color:#fca5a5;">{{ $todayHoliday->name }}</div>
                    <div style="font-size:0.78rem;color:var(--ent-text-secondary);">{{ $todayHoliday->type_label }} &bull; {{ $todayHoliday->date->format('F j, Y') }}</div>
                </div>
            </div>
            @if($todayHoliday->description)
            <div style="font-size:0.82rem;color:var(--ent-text-secondary);margin-top:6px;">{{ $todayHoliday->description }}</div>
            @endif
            <div style="background:rgba(0,0,0,0.15);padding:10px 14px;border-radius:var(--ent-radius-sm);margin-top:12px;font-size:0.82rem;font-weight:600;color:#f3e7cd;">
                <i class="bi bi-info-circle" style="color:#4ade80;margin-right:6px;"></i>No classes today — Enjoy your holiday! 🎊
            </div>
        </div>
    </div>
    @endif

    <!-- HOLIDAY & EVENTS CALENDAR (Read-Only) -->
    @php
        $stuCalYear   = request('hcal_year', now()->year);
        $stuCalMonth  = request('hcal_month', now()->month);
        $stuCalStart  = \Carbon\Carbon::create($stuCalYear, $stuCalMonth, 1);
        $stuCalEnd    = $stuCalStart->copy()->endOfMonth();
        $stuCalPrev   = $stuCalStart->copy()->subMonth();
        $stuCalNext   = $stuCalStart->copy()->addMonth();
        $stuStartDow  = $stuCalStart->dayOfWeek;
        $stuIsCurrent = (now()->year == $stuCalYear && now()->month == $stuCalMonth);
        $stuToday     = now()->day;

        // Build holiday map for this month
        $stuHolidayMap = [];
        $allMonthHolidays = \App\Models\Holiday::active()
            ->forMonth($stuCalYear, $stuCalMonth)
            ->orderBy('date')
            ->get();
        foreach ($allMonthHolidays as $hol) {
            $dk = $hol->date->format('Y-m-d');
            $stuHolidayMap[$dk][] = [
                'type' => $hol->type, 'name' => $hol->name,
                'description' => $hol->description,
                'type_label' => $hol->type_label, 'source' => 'holiday',
                'date_formatted' => $hol->date->format('M j, Y'),
            ];
        }
        // Merge announcements
        if (isset($calendarEvents)) {
            foreach ($calendarEvents as $evt) {
                if ($evt->type === 'announcement') {
                    $stuHolidayMap[$evt->date][] = [
                        'type' => 'announcement', 'name' => $evt->title,
                        'description' => $evt->content,
                        'type_label' => 'Announcement', 'source' => 'announcement',
                        'date_formatted' => \Carbon\Carbon::parse($evt->date)->format('M j, Y'),
                        'author' => $evt->author ?? null,
                    ];
                }
            }
        }

        // Build upcoming list
        $stuUpcoming = collect();
        foreach ($allMonthHolidays->where('date', '>=', now()->toDateString())->take(5) as $hol) {
            $stuUpcoming->push((object)[
                'type' => $hol->type, 'name' => $hol->name,
                'description' => $hol->description, 'date' => $hol->date,
                'date_formatted' => $hol->date->format('M j, Y'),
                'type_label' => $hol->type_label, 'source' => 'holiday',
            ]);
        }
        if (isset($upcomingHolidays)) {
            foreach ($upcomingHolidays as $hol) {
                if (!$stuUpcoming->contains('name', $hol->name)) {
                    $stuUpcoming->push((object)[
                        'type' => $hol->type, 'name' => $hol->name,
                        'description' => $hol->description, 'date' => $hol->date,
                        'date_formatted' => $hol->date->format('M j, Y'),
                        'type_label' => $hol->type_label, 'source' => 'holiday',
                    ]);
                }
            }
        }
        if (isset($announcements)) {
            foreach ($announcements->take(3) as $ann) {
                $annDate = $ann->scheduled_for ?? $ann->created_at;
                $stuUpcoming->push((object)[
                    'type' => 'announcement', 'name' => $ann->title,
                    'description' => \Illuminate\Support\Str::limit($ann->content, 100),
                    'date' => $annDate, 'date_formatted' => $annDate->format('M j, Y'),
                    'type_label' => 'Announcement', 'source' => 'announcement',
                    'author' => $ann->author->name ?? 'Admin',
                ]);
            }
        }
        $stuUpcoming = $stuUpcoming->sortBy('date')->values()->take(8);
    @endphp

    <div class="ent-section ent-fade-up ent-mb-lg">
        <div class="ent-section-header">
            <div class="ent-section-title">
                <div class="ent-section-title-icon" style="background:rgba(248,113,113,0.12);color:#f87171;">
                    <i class="bi bi-calendar-heart-fill"></i>
                </div>
                Holiday & Events Calendar
            </div>
        </div>
        <div class="ent-section-body" style="padding:16px 20px;">
            <div class="hcal-container">
                {{-- Calendar Pane --}}
                <div class="hcal-calendar-pane">
                    <div class="hcal-nav">
                        <a href="?hcal_year={{ $stuCalPrev->year }}&hcal_month={{ $stuCalPrev->month }}" class="hcal-nav-btn">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <div class="hcal-month-label">{{ $stuCalStart->format('F Y') }}</div>
                        <a href="?hcal_year={{ $stuCalNext->year }}&hcal_month={{ $stuCalNext->month }}" class="hcal-nav-btn">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>

                    <div class="hcal-day-labels">
                        @foreach(['S','M','T','W','T','F','S'] as $lbl)
                            <div class="hcal-day-label">{{ $lbl }}</div>
                        @endforeach
                    </div>

                    <div class="hcal-grid">
                        @for($i = 0; $i < $stuStartDow; $i++)
                            <div class="hcal-day empty"></div>
                        @endfor

                        @for($d = 1; $d <= $stuCalEnd->day; $d++)
                            @php
                                $dateKey = \Carbon\Carbon::create($stuCalYear, $stuCalMonth, $d)->format('Y-m-d');
                                $isToday = $stuIsCurrent && $d === $stuToday;
                                $isSunday = \Carbon\Carbon::create($stuCalYear, $stuCalMonth, $d)->dayOfWeek === 0;
                                $dayEvents = $stuHolidayMap[$dateKey] ?? [];
                                $hasEvents = count($dayEvents) > 0;
                                $isHoliday = collect($dayEvents)->where('source', 'holiday')->isNotEmpty();
                                $cls = '';
                                if ($isToday) $cls .= ' today';
                                if ($isSunday) $cls .= ' sunday';
                                if ($hasEvents) $cls .= ' has-event';
                                if ($isHoliday) $cls .= ' holiday-day';
                            @endphp
                            <div class="hcal-day{{ $cls }}" @if($hasEvents) onclick="scrollToStudentEvent('{{ $dateKey }}')" title="{{ collect($dayEvents)->pluck('name')->join(', ') }}" @endif>
                                <div class="hcal-day-num">{{ $d }}</div>
                                @if($hasEvents)
                                    <div class="hcal-dots">
                                        @foreach(collect($dayEvents)->unique('type')->take(3) as $evt)
                                            <div class="hcal-dot {{ $evt['type'] }}"></div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endfor
                    </div>

                    <div class="hcal-legend">
                        <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#dc2626;"></div> National</div>
                        <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#d97706;"></div> Local</div>
                        <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#7c2d12;"></div> School</div>
                        <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#6366f1;"></div> No Class</div>
                        <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#60a5fa;"></div> Announcement</div>
                    </div>
                </div>

                {{-- Events Sidebar (Read Only) --}}
                <div class="hcal-events-pane">
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--ent-text-muted);margin-bottom:12px;">
                        <i class="bi bi-calendar-event"></i> Upcoming Events
                    </div>
                    @forelse($stuUpcoming as $evt)
                        <div class="hcal-event-card" data-type="{{ $evt->type }}" id="stu-evt-{{ is_object($evt->date) ? $evt->date->format('Y-m-d') : $evt->date }}">
                            <div class="hcal-event-type {{ $evt->type }}">
                                <i class="bi {{ $evt->source === 'holiday' ? 'bi-calendar-heart' : 'bi-megaphone' }}"></i>
                                {{ $evt->type_label }}
                            </div>
                            <div class="hcal-event-name">{{ $evt->name }}</div>
                            <div class="hcal-event-date">
                                <i class="bi bi-calendar3"></i> {{ $evt->date_formatted }}
                                @if(isset($evt->author))
                                    · <i class="bi bi-person"></i> {{ $evt->author }}
                                @endif
                            </div>
                            @if($evt->description)
                                <div class="hcal-event-desc">{{ $evt->description }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="hcal-empty">
                            <div class="hcal-empty-icon"><i class="bi bi-calendar-x"></i></div>
                            <div class="hcal-empty-text">No upcoming events</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- STATS ROW -->
    <div class="ent-grid ent-grid-4 ent-mb-lg ent-fade-up ent-delay-1">
        <div class="ent-kpi-card" data-accent="success">
            <div class="ent-kpi-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="ent-kpi-body">
                <div class="ent-kpi-label">Present (All-time)</div>
                <div class="ent-kpi-value" data-count="{{ $totalPresent }}">0</div>
            </div>
        </div>
        <div class="ent-kpi-card" data-accent="warning">
            <div class="ent-kpi-icon"><i class="bi bi-clock-fill"></i></div>
            <div class="ent-kpi-body">
                <div class="ent-kpi-label">Late (All-time)</div>
                <div class="ent-kpi-value" data-count="{{ $totalLate }}">0</div>
            </div>
        </div>
        <div class="ent-kpi-card" data-accent="danger">
            <div class="ent-kpi-icon"><i class="bi bi-x-circle-fill"></i></div>
            <div class="ent-kpi-body">
                <div class="ent-kpi-label">Absent (All-time)</div>
                <div class="ent-kpi-value" data-count="{{ $totalAbsent }}">0</div>
            </div>
        </div>
        <div class="ent-kpi-card" data-accent="gold">
            <div class="ent-kpi-icon"><i class="bi bi-book-fill"></i></div>
            <div class="ent-kpi-body">
                <div class="ent-kpi-label">Subjects Enrolled</div>
                <div class="ent-kpi-value" data-count="{{ isset($subjects) ? count($subjects) : 0 }}">0</div>
            </div>
        </div>
    </div>


    <!-- MAIN BENTO GRID -->
    <div class="bento-grid">

        <!-- TODAY'S SCHEDULE -->
        <div class="bento-item bento-item--schedule glass-panel ent-fade-up ent-delay-2">
            <div class="section-header">
                <div class="section-icon" style="background: rgba(216,179,92,0.14); color: var(--gold);">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="section-title">Today's Schedule</div>
                    <div class="section-subtitle">{{ now()->format('l, M j') }}</div>
                </div>
            </div>
            @if($todaySchedule->count() > 0)
                @foreach($todaySchedule as $item)
                <div class="schedule-item">
                    <div class="schedule-timeline">
                        <div class="schedule-dot {{ $item->status }}"></div>
                    </div>
                    <div class="schedule-info">
                        <div class="schedule-subject">{{ $item->subject->name }}</div>
                        <div class="schedule-meta">
                            {{ $item->start_time->format('g:i A') }} – {{ $item->end_time->format('g:i A') }}
                            &nbsp;·&nbsp; {{ $item->subject->code }}
                        </div>
                    </div>
                    <div class="schedule-status-badge {{ $item->status }}">
                        @if($item->status === 'completed') ✓ Done
                        @elseif($item->status === 'ongoing') ⏱ Now
                        @elseif($item->status === 'missed') ✕ Missed
                        @else ○ Later
                        @endif
                    </div>
                </div>
                @endforeach
            @else
                <div class="ent-empty" style="padding:32px 16px;">
                    <div class="ent-empty-icon" style="width:48px;height:48px;font-size:1.25rem;">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <div class="ent-empty-text">No classes scheduled today</div>
                </div>
            @endif
        </div>

        <!-- ATTENDANCE CALENDAR -->
        <div class="bento-item bento-item--calendar glass-panel ent-fade-up ent-delay-3">
            <div class="section-header">
                <div class="section-icon" style="background: rgba(37,99,235,0.14); color: #60a5fa;">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div>
                    <div class="section-title">Calendar</div>
                    <div class="section-subtitle">Attendance history</div>
                </div>
            </div>

            <div class="cal-header">
                <a href="?cal_year={{ $prevMonth->year }}&cal_month={{ $prevMonth->month }}" class="cal-nav">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <div class="cal-month">{{ $calStart->format('F Y') }}</div>
                @if(!$isLatestMonth)
                    <a href="?cal_year={{ $nextMonth->year }}&cal_month={{ $nextMonth->month }}" class="cal-nav">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @else
                    <div style="width:30px;"></div>
                @endif
            </div>

            <div class="cal-grid">
                @foreach(['S','M','T','W','T','F','S'] as $label)
                    <div class="cal-day-label">{{ $label }}</div>
                @endforeach

                @for($i = 0; $i < $startDow; $i++)
                    <div class="cal-day empty"></div>
                @endfor

                @for($d = 1; $d <= $calEnd->day; $d++)
                    @php
                        $isToday   = $isCurrentMonth && $d === $today;
                        $isFuture  = $isCurrentMonth && $d > $today;
                        $isNoClass = in_array($d, $noClassDays);
                        $dots      = $dayDotsMap[$d] ?? [];
                        $hasRecs   = !empty($dots);
                        $dateKey   = \Carbon\Carbon::create($calYear, $calMonth, $d)->format('Y-m-d');
                        $dateLabel = \Carbon\Carbon::create($calYear, $calMonth, $d)->format('F j, Y');
                        $hasEvents = isset($eventsMap[$dateKey]);

                        $isHoliday = false;
                        if ($isToday) {
                            $todayHolidayCheck = \App\Models\Holiday::where('date', $dateKey)->where('is_active', true)->first();
                            $isHoliday = $todayHolidayCheck !== null;
                        }

                        $cls = '';
                        if ($isNoClass || $isHoliday) $cls = 'no-class';
                        elseif ($isFuture) $cls = 'future';
                        elseif ($isToday) $cls = 'today';
                        if ($hasRecs && !$isNoClass && !$isFuture && !$isHoliday) $cls .= ' has-records';
                        if ($hasEvents) $cls .= ' has-events';

                        $isClickable = ($hasRecs && !$isNoClass && !$isFuture && !$isHoliday) ||
                                      ($isToday && !$isNoClass && !$isHoliday) ||
                                      $hasEvents;
                    @endphp
                    <div class="cal-day {{ $cls }}"
                         @if($isClickable)
                             onclick="openAttModal('{{ $dateKey }}', '{{ $dateLabel }}')"
                             onkeypress="if(event.key === 'Enter') openAttModal('{{ $dateKey }}', '{{ $dateLabel }}')"
                             title="Click to view details"
                             tabindex="0"
                             role="button"
                             aria-label="View attendance for {{ $dateLabel }}"
                         @endif>
                        <div class="cal-day-num">{{ $d }}</div>
                        @if($hasRecs && !$isNoClass && !$isFuture && !$isHoliday)
                            <div class="cal-dots">
                                @foreach($dots as $dot)
                                    <div class="cal-dot {{ $dot }}"></div>
                                @endforeach
                                @if($hasEvents)
                                    <div class="cal-dot" style="background:#a78bfa;"></div>
                                @endif
                            </div>
                        @elseif($hasEvents)
                            <div class="cal-dots">
                                <div class="cal-dot" style="background:#a78bfa;"></div>
                            </div>
                        @elseif($isToday && !$isNoClass && !$isHoliday)
                            <div class="cal-dots">
                                <div class="cal-dot" style="background:#94a3b8;width:4px;height:4px;opacity:0.4;"></div>
                            </div>
                        @endif
                    </div>
                @endfor
            </div>

            <div class="cal-legend">
                <div class="cal-legend-item">
                    <div class="cal-legend-dot" style="background:#16a34a;"></div> Present
                </div>
                <div class="cal-legend-item">
                    <div class="cal-legend-dot" style="background:#d97706;"></div> Late
                </div>
                <div class="cal-legend-item">
                    <div class="cal-legend-dot" style="background:#dc2626;"></div> Absent
                </div>
                <div class="cal-legend-item">
                    <div class="cal-legend-dot" style="background:#a78bfa;"></div> Event
                </div>
            </div>
        </div>



    </div>
</div>

<script>
// ── LIVE CLOCK ──
function updateClock() {
    const el = document.getElementById('liveTime');
    if (el) {
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes(), ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        el.textContent = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
    }
}
setInterval(updateClock, 30000);

// ── ANIMATED COUNTERS ──
function animateCounters() {
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count) || 0;
        if (target === 0) { el.textContent = '0'; return; }
        const duration = 1200;
        const start = performance.now();
        function tick(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(eased * target);
            if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });
}

// Observe when stats are visible
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            observer.disconnect();
        }
    });
}, { threshold: 0.2 });
const statsRow = document.querySelector('.ent-grid-4');
if (statsRow) observer.observe(statsRow);

// ── CALENDAR MODAL ──
const calendarData = @json($calendarJson);
const eventsData = @json($eventsMap);

function openAttModal(dateKey, dateLabel) {
    const records = calendarData[dateKey] || [];
    const events = eventsData[dateKey] || [];

    document.getElementById('modalDate').textContent = dateLabel;

    if (records.length === 0 && events.length === 0) {
        document.getElementById('modalSummary').innerHTML =
            '<span class="att-summary-pill" style="background:rgba(255,255,255,0.06);color:rgba(245,234,215,0.6);border:1px solid rgba(255,255,255,0.1);"><i class="bi bi-info-circle"></i> No records yet</span>';

        document.getElementById('modalList').innerHTML =
            '<div class="ent-empty" style="padding:24px;">' +
            '<div class="ent-empty-icon" style="width:48px;height:48px;font-size:1.25rem;"><i class="bi bi-calendar-x"></i></div>' +
            '<div class="ent-empty-text">No attendance or events for this date.</div>' +
            '</div>';

        document.getElementById('attModal').classList.add('active');
        return;
    }

    let summaryHtml = '';

    // Attendance summary
    if (records.length > 0) {
        const present = records.filter(r => r.status === 'Present').length;
        const late    = records.filter(r => r.status === 'Late').length;
        const absent  = records.filter(r => r.status === 'Absent').length;

        if (present > 0) summaryHtml += `<span class="att-summary-pill present"><i class="bi bi-check-circle-fill"></i> Present: ${present}</span>`;
        if (late > 0)    summaryHtml += `<span class="att-summary-pill late"><i class="bi bi-clock-fill"></i> Late: ${late}</span>`;
        if (absent > 0)  summaryHtml += `<span class="att-summary-pill absent"><i class="bi bi-x-circle-fill"></i> Absent: ${absent}</span>`;
    }

    // Events summary
    if (events.length > 0) {
        summaryHtml += `<span class="att-summary-pill" style="background:rgba(139,92,246,0.15);color:#c4b5fd;border:1px solid rgba(139,92,246,0.2);"><i class="bi bi-megaphone-fill"></i> ${events.length} Event${events.length > 1 ? 's' : ''}</span>`;
    }

    document.getElementById('modalSummary').innerHTML = summaryHtml;

    let listHtml = '';

    // Render events first
    events.forEach(evt => {
        const icon = evt.type === 'holiday' ? 'bi-calendar-heart' : 'bi-megaphone';
        const bgColor = evt.type === 'holiday' ? 'rgba(139,92,246,0.12)' : 'rgba(59,130,246,0.1)';
        const iconColor = evt.type === 'holiday' ? '#a78bfa' : '#60a5fa';
        const label = evt.type === 'holiday' ? 'Holiday' : 'Announcement';
        listHtml += `
            <div style="background:${bgColor};border:1px solid rgba(139,92,246,0.1);border-radius:10px;padding:10px 12px;margin-bottom:8px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <i class="bi ${icon}" style="color:${iconColor};font-size:0.85rem;"></i>
                    <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:${iconColor};letter-spacing:0.5px;">${label}</span>
                </div>
                <div style="font-size:0.88rem;font-weight:700;color:#f8e7d3;margin-bottom:2px;">${evt.title}</div>
                <div style="font-size:0.75rem;color:rgba(245,234,215,0.55);line-height:1.4;">${evt.content}</div>
                ${evt.author ? `<div style="font-size:0.68rem;color:rgba(245,234,215,0.35);margin-top:4px;"><i class="bi bi-person" style="margin-right:3px;"></i>${evt.author}</div>` : ''}
            </div>`;
    });

    // Render attendance records
    records.forEach(r => {
        const badgeCls = r.status === 'Present' ? 'present' : r.status === 'Late' ? 'late' : 'absent';
        const timeHtml = r.time_in
            ? `<span class="att-modal-time"><i class="bi bi-clock me-1"></i>${r.time_in}</span>`
            : `<span class="att-modal-time">—</span>`;
        listHtml += `
            <div class="att-modal-row">
                <span class="att-modal-badge ${badgeCls}">${r.status}</span>
                <span class="att-modal-subject">${r.subject}</span>
                ${timeHtml}
            </div>`;
    });
    document.getElementById('modalList').innerHTML = listHtml;
    document.getElementById('attModal').classList.add('active');
}

function closeAttModal() {
    document.getElementById('attModal').classList.remove('active');
}

document.getElementById('attModal').addEventListener('click', function(e) {
    if (e.target === this) closeAttModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAttModal();
});

// ── HOLIDAY CALENDAR SCROLL ──
function scrollToStudentEvent(dateKey) {
    const el = document.getElementById('stu-evt-' + dateKey);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.style.boxShadow = '0 0 0 2px rgba(207,164,111,0.5)';
        setTimeout(() => { el.style.boxShadow = ''; }, 2000);
    }
}
</script>
@endsection