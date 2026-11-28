@extends('layouts.app')
@section('page-title', 'Weekly Schedule')

@section('content')
@php
    $currentDay = now()->format('l');
    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $defaultDay = in_array($currentDay, $validDays, true) ? $currentDay : 'Monday';
@endphp

<div>
    <header class="sf-page-head">
        <div>
            <h1>Weekly Schedule</h1>
            <p>Your classes for Year {{ Auth::user()->year_level }}, Semester {{ Auth::user()->semester }}</p>
        </div>
        <div class="sf-page-actions">
            <div class="sf-segmented sf-desktop-only" role="group" aria-label="Schedule layout">
                <button type="button" class="sf-segmented-btn active" id="schedGridBtn" data-sched-view="grid" aria-pressed="true">Grid</button>
                <button type="button" class="sf-segmented-btn" id="schedListBtn" data-sched-view="list" aria-pressed="false">List</button>
            </div>
            <a href="{{ route('student.classes') }}" class="sf-btn sf-btn-secondary">All Classes</a>
        </div>
    </header>

    {{-- Mobile Day Selector Tablist --}}
    <div class="sf-mobile-only" style="margin-bottom:14px;">
        <div class="sf-filter-pills" role="tablist" aria-label="Select weekday" style="flex-wrap:nowrap;overflow-x:auto;padding-bottom:4px;">
            @foreach($validDays as $day)
                @php($count = $weeklySchedule[$day]->count())
                @php($isSelected = $day === $defaultDay)
                <button type="button"
                        role="tab"
                        class="sf-filter-pill schedule-day-pill {{ $isSelected ? 'active' : '' }}"
                        id="sched-pill-{{ $day }}"
                        data-sched-day="{{ $day }}"
                        aria-controls="sched-day-card-{{ $day }}"
                        aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                        tabindex="{{ $isSelected ? '0' : '-1' }}">
                    <span>{{ substr($day, 0, 3) }}</span>
                    @if($count > 0)
                        <span class="sf-mono" style="opacity:0.75;">{{ $count }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <div class="sf-weekly-grid has-sat" id="studentWeeklyGrid">
        @foreach($validDays as $day)
            @php($dayItems = $weeklySchedule[$day])
            <section class="sf-week-col schedule-day-col {{ $day === $currentDay ? 'is-today' : '' }}"
                     data-mobile-hidden="{{ $day === $defaultDay ? '0' : '1' }}"
                     id="sched-day-card-{{ $day }}"
                     role="tabpanel"
                     aria-labelledby="sched-pill-{{ $day }}">
                <div class="sf-week-col-head">
                    <strong>{{ $day }}</strong>
                    <span>{{ $day === $currentDay ? 'Today · ' : '' }}{{ $dayItems->count() }}</span>
                </div>

                @if($dayItems->isNotEmpty())
                    @foreach($dayItems as $sched)
                        @php
                            $teacherName = $sched->subject->instructorUser->name ?? $sched->subject->instructor ?? 'TBA';
                            $roomLabel = trim((string) ($sched->room ?? ''));
                            $hasRoom = $roomLabel !== '' && strtoupper($roomLabel) !== 'TBA';
                        @endphp
                        <div class="sf-week-card">
                            <div class="sf-week-card-code">{{ $sched->subject->code }}</div>
                            <div class="sf-week-card-title">{{ $sched->subject->name }}</div>
                            <div class="sf-week-card-meta">
                                {{ \Carbon\Carbon::parse($sched->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($sched->end_time)->format('g:i A') }}<br>
                                {{ $hasRoom ? $roomLabel : 'Room TBA' }} · {{ $teacherName }}
                            </div>
                        </div>
                    @endforeach
                @else
                    <div style="font-size:12px;color:var(--sf-text-muted);text-align:center;padding:24px 6px;">
                        No classes scheduled for {{ $day }}.
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>

<script @cspNonce>
(function () {
    const pills = Array.from(document.querySelectorAll('.schedule-day-pill[data-sched-day]'));
    const cols = Array.from(document.querySelectorAll('.schedule-day-col'));

    function syncMobileCols() {
        const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
        cols.forEach((col) => {
            col.hidden = isMobile && col.dataset.mobileHidden === '1';
        });
    }

    function selectScheduleDay(day, moveFocus = false) {
        pills.forEach((btn) => {
            const active = btn.dataset.schedDay === day;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
            btn.setAttribute('tabindex', active ? '0' : '-1');
            if (active && moveFocus) btn.focus();
        });
        cols.forEach((col) => {
            col.dataset.mobileHidden = col.id === 'sched-day-card-' + day ? '0' : '1';
        });
        syncMobileCols();
    }

    pills.forEach((btn, idx) => {
        btn.addEventListener('click', () => selectScheduleDay(btn.dataset.schedDay));
        btn.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                e.preventDefault();
                const next = pills[(idx + (e.key === 'ArrowRight' ? 1 : -1) + pills.length) % pills.length];
                selectScheduleDay(next.dataset.schedDay, true);
            }
        });
    });

    const gridEl = document.getElementById('studentWeeklyGrid');
    const gridBtn = document.getElementById('schedGridBtn');
    const listBtn = document.getElementById('schedListBtn');
    function setScheduleView(mode) {
        const isGrid = mode !== 'list';
        if (gridEl) gridEl.style.gridTemplateColumns = isGrid ? '' : '1fr';
        gridBtn?.classList.toggle('active', isGrid);
        listBtn?.classList.toggle('active', !isGrid);
        gridBtn?.setAttribute('aria-pressed', isGrid ? 'true' : 'false');
        listBtn?.setAttribute('aria-pressed', !isGrid ? 'true' : 'false');
    }
    gridBtn?.addEventListener('click', () => setScheduleView('grid'));
    listBtn?.addEventListener('click', () => setScheduleView('list'));

    window.addEventListener('resize', syncMobileCols);
    syncMobileCols();
})();
</script>
@endsection
