@extends('layouts.app')
@section('page-title', 'My Schedule')

@section('content')
<div class="mb-3 mb-md-4 student-weekly-heading student-page-header">
    <div>
        <h1 style="font-size: clamp(1.35rem, 3.5vw, 1.9rem);">Weekly Schedule</h1>
        <p>
            Your classes for Year {{ Auth::user()->year_level }}, Semester {{ Auth::user()->semester }}
        </p>
    </div>
    <div class="d-none d-lg-flex align-items-center gap-2 flex-wrap">
        <div class="sd-view-toggle" role="group" aria-label="Schedule layout">
            <button type="button" class="sd-view-toggle-btn is-active" id="schedGridBtn" data-sched-view="grid">
                <i class="bi bi-columns-gap" aria-hidden="true"></i> Grid
            </button>
            <button type="button" class="sd-view-toggle-btn" id="schedListBtn" data-sched-view="list">
                <i class="bi bi-list-ul" aria-hidden="true"></i> Detailed List
            </button>
        </div>
        <a href="{{ route('student.classes') }}" class="student-page-link">
            <i class="bi bi-journal-bookmark" aria-hidden="true"></i> Classes &amp; PDF
        </a>
    </div>
</div>

@php
    $currentDay = now()->format('l');
    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $defaultDay = in_array($currentDay, $validDays) ? $currentDay : 'Monday';
@endphp

<style>
    @media (max-width: 767px) {
        .mobile-sched-hidden {
            display: none !important;
        }
        .schedule-day-rail {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding: 4px 2px 8px 2px;
            margin-bottom: 12px;
            scrollbar-width: none;
        }
        .schedule-day-rail::-webkit-scrollbar {
            display: none;
        }
        .schedule-day-pill {
            flex-shrink: 0;
            min-height: 44px;
            padding: 8px 15px;
            border-radius: 10px;
            border: 1px solid rgba(250, 247, 248, 0.1);
            background: #14080C;
            color: #9E8990;
            font-size: 0.84rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            touch-action: manipulation;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
            cursor: pointer;
        }
        .schedule-day-pill:active {
            transform: scale(0.96);
        }
        .schedule-day-pill.active {
            background: #6B192B !important;
            border-color: rgba(232, 152, 168, 0.42) !important;
            color: #FAF7F8 !important;
        }
        .schedule-day-pill-badge {
            font-size: 0.7rem;
            padding: 2px 7px;
            border-radius: 6px;
            background: rgba(232, 152, 168, 0.14);
            color: #E898A8;
            font-weight: 700;
        }
        .schedule-day-pill.active .schedule-day-pill-badge {
            background: #FAF7F8;
            color: #14080C;
        }
        .schedule-day-today-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #34D399;
            flex-shrink: 0;
        }
        .sched-mobile-card {
            border-left: 3px solid #E898A8;
            background: #1C0C11;
            border-top: 1px solid rgba(250, 247, 248, 0.08);
            border-right: 1px solid rgba(250, 247, 248, 0.08);
            border-bottom: 1px solid rgba(250, 247, 248, 0.08);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 10px;
        }
    }
</style>

<!-- Mobile Day Selector Pill Rail -->
<div class="d-md-none">
    <div class="schedule-day-rail" role="tablist" aria-label="Select weekday">
        @foreach($validDays as $day)
            @php $count = $weeklySchedule[$day]->count(); @endphp
            <button type="button" 
                    class="schedule-day-pill {{ $day === $defaultDay ? 'active' : '' }}" 
                    onclick="selectScheduleDay('{{ $day }}')"
                    id="sched-pill-{{ $day }}"
                    aria-pressed="{{ $day === $defaultDay ? 'true' : 'false' }}">
                @if($day === $currentDay)
                    <span class="schedule-day-today-dot" aria-label="Today"></span>
                @endif
                <span>{{ substr($day, 0, 3) }}</span>
                @if($count > 0)
                    <span class="schedule-day-pill-badge">{{ $count }}</span>
                @endif
            </button>
        @endforeach
    </div>
</div>

<div class="row g-4 student-weekly-grid sd-week-columns" id="studentWeeklyGrid">
    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
        <div class="col-lg-12 schedule-day-col {{ $day === $defaultDay ? '' : 'mobile-sched-hidden' }} {{ $day === $currentDay ? 'is-today-day' : '' }}" id="sched-day-card-{{ $day }}">
            <x-card title="{{ $day }}" icon="bi bi-calendar-event">
                @if($day === $currentDay)
                    <x-slot name="headerActions">
                        <span class="badge" style="background: rgba(74, 222, 128, 0.14); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.34); font-size: 0.72rem; font-weight: 800; padding: 4px 10px; border-radius: 999px;">Today</span>
                    </x-slot>
                @endif
                @if($weeklySchedule[$day]->count() > 0)
                    <!-- Compact Grid View Cards (Desktop Grid mode) -->
                    <div class="sd-sched-grid-items d-none d-lg-flex flex-column gap-2">
                        @foreach($weeklySchedule[$day] as $sched)
                            <div class="sd-sched-grid-card">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                    <span class="sd-sched-time">
                                        {{ \Carbon\Carbon::parse($sched->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($sched->end_time)->format('g:i A') }}
                                    </span>
                                    <span class="sd-sched-room-pill">{{ $sched->room ?? 'TBA' }}</span>
                                </div>
                                <div class="sd-sched-subject">{{ $sched->subject->name }}</div>
                                <div class="d-flex align-items-center justify-content-between gap-2 mt-1 sd-sched-meta">
                                    <span class="sd-sched-code">{{ $sched->subject->code }}</span>
                                    <span class="text-truncate" title="{{ $sched->subject->instructorUser->name ?? $sched->subject->instructor ?? 'TBA' }}">{{ $sched->subject->instructorUser->name ?? $sched->subject->instructor ?? 'TBA' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Desktop Table (Detailed List mode & tablet) -->
                    <div class="ent-scroll-x d-none d-md-block sd-sched-list-table" style="margin: -20px;">
                        <table class="ent-table" style="min-width: 600px; margin-bottom: 0; table-layout: fixed;">
                            <colgroup>
                                <col style="width: 25%;">
                                <col style="width: 40%;">
                                <col style="width: 15%;">
                                <col style="width: 20%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Subject</th>
                                    <th>Room</th>
                                    <th>Instructor</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($weeklySchedule[$day] as $sched)
                                <tr>
                                    <td data-label="Time">
                                        <div class="sd-sched-time">
                                             {{ \Carbon\Carbon::parse($sched->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('g:i A') }}
                                        </div>
                                    </td>
                                    <td data-label="Subject">
                                        <div class="sd-sched-subject">{{ $sched->subject->name }}</div>
                                        <div class="sd-sched-code mt-1">{{ $sched->subject->code }}</div>
                                    </td>
                                    <td data-label="Room">
                                        <span class="sd-sched-room-pill">{{ $sched->room ?? 'TBA' }}</span>
                                    </td>
                                    <td data-label="Instructor">
                                        <div style="font-size: 0.9rem;">{{ $sched->subject->instructorUser->name ?? $sched->subject->instructor ?? 'TBA' }}</div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="d-block d-md-none">
                        <div class="d-flex flex-column pt-1">
                            @foreach($weeklySchedule[$day] as $sched)
                                <div class="sched-mobile-card">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div style="min-width: 0;">
                                            <div class="sd-sched-subject">{{ $sched->subject->name }}</div>
                                            <div class="sd-sched-code mt-1">{{ $sched->subject->code }}</div>
                                        </div>
                                        <span class="sd-sched-room-pill">{{ $sched->room ?? 'TBA' }}</span>
                                    </div>
                                    <div class="d-flex flex-column gap-1 mt-2">
                                        <div class="sd-sched-time d-flex align-items-center gap-2">
                                            <i class="bi bi-clock" aria-hidden="true"></i> {{ \Carbon\Carbon::parse($sched->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('g:i A') }}
                                        </div>
                                        <div class="sd-sched-meta d-flex align-items-center gap-2" style="overflow-wrap: anywhere;">
                                            <i class="bi bi-person" aria-hidden="true"></i> {{ $sched->subject->instructorUser->name ?? $sched->subject->instructor ?? 'TBA' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-cup-hot" aria-hidden="true" style="font-size: 2.2rem; color: rgba(250,247,248,0.16); margin-bottom: 12px; display: block;"></i>
                        <div style="color: #9E8990; font-size: 0.88rem; margin-top: 6px;">No classes scheduled for this day.</div>
                    </div>
                @endif
            </x-card>
        </div>
    @endforeach
</div>

<script nonce="{{ csp_nonce() }}">
    function selectScheduleDay(day) {
        if (window.triggerHaptic) window.triggerHaptic('light');
        document.querySelectorAll('.schedule-day-pill').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-pressed', 'false');
        });
        const activeBtn = document.getElementById('sched-pill-' + day);
        if (activeBtn) {
            activeBtn.classList.add('active');
            activeBtn.setAttribute('aria-pressed', 'true');
            activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        document.querySelectorAll('.schedule-day-col').forEach(col => col.classList.add('mobile-sched-hidden'));
        const targetCol = document.getElementById('sched-day-card-' + day);
        if (targetCol) {
            targetCol.classList.remove('mobile-sched-hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const gridEl = document.getElementById('studentWeeklyGrid');
        const gridBtn = document.getElementById('schedGridBtn');
        const listBtn = document.getElementById('schedListBtn');
        if (!gridEl || !gridBtn || !listBtn) return;

        function setScheduleView(mode) {
            const isGrid = mode !== 'list';
            gridEl.classList.toggle('sd-week-columns', isGrid);
            gridBtn.classList.toggle('is-active', isGrid);
            listBtn.classList.toggle('is-active', !isGrid);
            try { localStorage.setItem('sdScheduleView', isGrid ? 'grid' : 'list'); } catch (e) {}
        }

        gridBtn.addEventListener('click', function() { setScheduleView('grid'); });
        listBtn.addEventListener('click', function() { setScheduleView('list'); });
        try {
            const saved = localStorage.getItem('sdScheduleView');
            if (saved === 'list' || saved === 'grid') setScheduleView(saved);
        } catch (e) {}
    });
</script>
@endsection
