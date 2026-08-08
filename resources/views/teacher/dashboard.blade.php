@extends('layouts.app')
@section('page-title', 'Instructor Dashboard')

@section('content')

@php
    $greetHour = now()->hour;
    $greeting = $greetHour < 12 ? 'Good Morning' : ($greetHour < 17 ? 'Good Afternoon' : 'Good Evening');
@endphp

@if(session('error'))
<div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <i class="bi bi-exclamation-circle-fill"></i><span>{{ session('error') }}</span>
</div>
@endif

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
</div>
@endif

<!-- Urgent Alerts -->
@if(isset($pendingExcuses) && $pendingExcuses > 0)
<div class="ent-alert ent-fade-up" style="background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.25); margin-bottom: 24px; padding: 24px; border-radius: 16px;">
    <div class="d-flex align-items-center gap-3 mb-3">
        <div class="ent-alert-icon" style="background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.1);">
            <i class="bi bi-file-earmark-text-fill"></i>
        </div>
        <div class="ent-alert-body" style="flex: 1;">
            <div class="ent-alert-title" style="color: #fcd34d; font-weight: 700; font-size: 1.1rem; letter-spacing: -0.01em;">{{ $pendingExcuses }} Pending Excuses</div>
            <div class="ent-alert-text" style="color: #b39b82; font-size: 0.875rem;">Students have submitted leave requests requiring your review.</div>
        </div>
    </div>
    <div class="d-flex gap-3 flex-wrap">
        <a href="{{ route('teacher.excuse.reviews') }}" class="ent-btn ent-btn-primary" style="background: linear-gradient(135deg, #fbbf24, #d97706); border: 1px solid rgba(245,158,11,0.4); border-radius: 10px; color: #1a1a2e;">
            Review Now
        </a>
    </div>
</div>
@endif

<!-- Hero Banner -->
<div class="mb-4" style="background: linear-gradient(135deg, rgba(32,20,15,0.9) 0%, rgba(20,10,5,0.95) 100%); border: 1px solid rgba(207,164,111,0.25); border-radius: 24px; padding: 30px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
    <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: linear-gradient(180deg, var(--gold) 0%, #8f6e4a 100%);"></div>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
        <div class="d-flex align-items-center gap-4">
            <div>
                <h1 style="color: #f3e7cd; font-weight: 800; margin: 0 0 6px 0; font-size: 2rem;">{{ Auth::user()->name }}</h1>
                <div style="color: #b39b82; font-size: 0.95rem; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span>Instructor</span>
                </div>
            </div>
        </div>
        <div style="display:flex; flex-direction:column; gap:12px; text-align:right;">
            <div style="text-align: right; background: rgba(0,0,0,0.3); padding: 12px 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05);">
                <div style="color: var(--gold); font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                    <i class="bi bi-clock"></i> <span id="teacherClock">{{ now()->format('h:i A') }}</span>
                </div>
                <div style="color: #b39b82; font-size: 0.85rem; margin-top: 2px;">{{ now()->format('l, F j, Y') }}</div>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('teacher.reports.pdf') }}" target="_blank" class="ent-btn" style="background: rgba(255,255,255,0.1); color: var(--gold); border: 1px solid rgba(207,164,111,0.3); justify-content:center; text-decoration:none;">
                    <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> Export
                </a>
                <a href="{{ route('teacher.excuses.create') }}" class="ent-btn" style="background: rgba(255,255,255,0.1); color: var(--gold); border: 1px solid rgba(207,164,111,0.3); justify-content:center; text-decoration:none;">
                    <i class="bi bi-calendar-x-fill me-1"></i> Leave
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Skeleton Stats -->
<div class="row g-3 mb-4" id="skelStats">
    <div class="col-md-3 col-6"><x-skeleton type="stat" /></div>
    <div class="col-md-3 col-6"><x-skeleton type="stat" /></div>
    <div class="col-md-3 col-6"><x-skeleton type="stat" /></div>
    <div class="col-md-3 col-6"><x-skeleton type="stat" /></div>
</div>

<!-- Quick Stats -->
<div class="ent-grid ent-grid-4 ent-mb-lg ent-fade-up ent-delay-2" id="realStats" style="display:none; gap:24px; margin-bottom:24px;">
    <x-card type="kpi" accent="gold" label="Today's Classes" value="{{ $todayClasses->count() }}" icon="bi bi-easel-fill" />
    <x-card type="kpi" accent="primary" label="Total Students" value="{{ $totalStudents ?? 0 }}" icon="bi bi-people-fill" />
    <x-card type="kpi" accent="success" label="Present Today" value="{{ $totalPresent ?? 0 }}" icon="bi bi-check-circle-fill" />
    <x-card type="kpi" accent="danger" label="Absent Today" value="{{ $totalAbsent ?? 0 }}" icon="bi bi-x-circle-fill" />
</div>

<div class="row g-4 mb-4">
    <!-- Left Column -->
    <div class="col-lg-8">
        
        <!-- School Calendar -->
        <div class="mb-4">
            <x-card title="School Calendar" icon="bi bi-calendar3">
                <x-slot name="headerActions">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" onclick="schoolCalendar.prev(); updateCalendarTitle();" class="btn btn-outline btn-sm" style="color: var(--ent-text); border-color: var(--ent-border);">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button type="button" onclick="schoolCalendar.next(); updateCalendarTitle();" class="btn btn-outline btn-sm" style="color: var(--ent-text); border-color: var(--ent-border);">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                        <button type="button" onclick="schoolCalendar.today(); updateCalendarTitle();" class="btn btn-outline btn-sm" style="color: var(--ent-text); border-color: var(--ent-border);">
                            Today
                        </button>
                    </div>
                </x-slot>

                <div id="calendarTitle" style="font-weight: 700; color: var(--ent-text); font-size: 1.1rem; margin-bottom: 16px;"></div>

                <div class="legend-container mb-3 d-flex gap-3 flex-wrap" style="font-size:0.8rem; font-weight:600; color:var(--ent-text-muted);">
                    <div class="d-flex align-items-center gap-1"><div style="width:10px;height:10px;border-radius:50%;background:#60a5fa;"></div> Class</div>
                    <div class="d-flex align-items-center gap-1"><div style="width:10px;height:10px;border-radius:50%;background:#f87171;"></div> Exam</div>
                    <div class="d-flex align-items-center gap-1"><div style="width:10px;height:10px;border-radius:50%;background:#fbbf24;"></div> Meeting</div>
                    <div class="d-flex align-items-center gap-1"><div style="width:10px;height:10px;border-radius:50%;background:#a78bfa;"></div> School Event</div>
                    <div class="d-flex align-items-center gap-1"><div style="width:10px;height:10px;border-radius:50%;background:#4ade80;"></div> Holiday</div>
                </div>

                <div id="schoolCalendar" style="min-height: 500px;"></div>
            </x-card>
        </div>
        
        <!-- Weekly Chart -->
        @if(count($weeklyLabels ?? []) > 0)
        <div class="mb-4">
            <x-card title="Weekly Attendance Overview" icon="bi bi-bar-chart-fill">
                <div class="ent-chart-container" style="height:250px;">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </x-card>
        </div>
        @endif

        <!-- Recent Logs -->
        <div class="mb-4">
            <x-card title="Recent Logs" icon="bi bi-clock-history">
                <div class="mb-3">
                    <input type="text" id="recentLogsSearch" placeholder="Search logs..." 
                        style="background:rgba(255,255,255,0.04);border:1px solid var(--ent-border);border-radius:8px;color:var(--ent-text);padding:8px 12px;font-size:0.85rem;width:100%;outline:none;font-family:'Inter',sans-serif;" 
                        onkeyup="filterRecentLogs()">
                </div>
                
                <div class="d-flex flex-column gap-3" style="max-height: 300px; overflow-y: auto;">
                    @forelse($recentAttendance->take(8) as $record)
                        <div class="attendance-row" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;color:#f3e7cd;">
                                    {{ substr($record->user->name, 0, 2) }}
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #f3e7cd;">{{ $record->user->name }}</div>
                                    <div style="color: #b39b82; font-size: 0.8rem; margin-top: 4px;">
                                        {{ $record->subject->name ?? $record->subject_code }}
                                    </div>
                                </div>
                            </div>
                            <div>
                                @php $recordStatus = strtolower($record->status ?? ''); @endphp
                                @if($recordStatus === 'present') <x-badge type="present">Present</x-badge>
                                @elseif($recordStatus === 'late') <x-badge type="late">Late</x-badge>
                                @else <x-badge type="absent">{{ $record->status }}</x-badge>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state text-center" style="padding: 40px 20px;">
                            <i class="bi bi-inbox-fill" style="font-size: 3rem; color: #b39b82; opacity: 0.5;"></i>
                            <p style="color: #b39b82; font-size: 1rem; margin-top: 16px; font-weight: 600;">No recent logs</p>
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>

    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        
        <!-- Quick Switch -->
        @if($todayClasses->count() > 0)
        <div class="mb-4">
            <x-card title="Today's Classes" icon="bi bi-easel-fill">
                <div class="d-flex flex-column gap-2">
                    @foreach($todayClasses as $class)
                        @php
                            $isCompleted = $class->has_attendance_today ?? false;
                            $btnBg = $isCompleted ? 'rgba(74,222,128,0.1)' : 'rgba(255,255,255,0.03)';
                            $btnBorder = $isCompleted ? 'rgba(74,222,128,0.2)' : 'rgba(255,255,255,0.06)';
                            $icon = $isCompleted ? 'bi-check-circle-fill' : 'bi-play-circle-fill';
                            $iconColor = $isCompleted ? '#4ade80' : '#cfa46f';
                        @endphp
                        <a href="{{ route('teacher.attendance', $class->id) }}" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: {{ $btnBg }}; border: 1px solid {{ $btnBorder }}; border-radius: 12px; color: var(--ent-text); text-decoration: none; transition: all 0.2s;" onmouseover="this.style.transform='translateX(4px)'" onmouseout="this.style.transform='none'">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi {{ $icon }}" style="color: {{ $iconColor }};"></i>
                                <span style="font-weight: 600;">{{ $class->name }}</span>
                            </div>
                            <i class="bi bi-chevron-right" style="font-size: 0.8rem; opacity: 0.5;"></i>
                        </a>
                    @endforeach
                </div>
            </x-card>
        </div>
        @else
        <div class="mb-4">
            <x-card title="Today's Classes" icon="bi bi-easel-fill">
                <div class="empty-state text-center" style="padding: 20px;">
                    <i class="bi bi-cup-hot" style="font-size: 2rem; color: #b39b82; opacity: 0.5;"></i>
                    <p style="color: #b39b82; font-size: 0.9rem; margin-top: 10px; font-weight: 600;">No classes scheduled today</p>
                </div>
            </x-card>
        </div>
        @endif

        <!-- At-Risk Students -->
        @if(isset($atRiskStudents) && $atRiskStudents->count() > 0)
        <div class="mb-4">
            <x-card title="Early Warning" icon="bi bi-exclamation-triangle-fill">
                <div class="d-flex flex-column gap-3">
                    @foreach($atRiskStudents->take(3) as $stat)
                    <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(248,113,113,0.15); padding: 14px; border-radius: 12px;">
                        <div style="font-size: 0.9rem; font-weight: 700; color: var(--ent-text); margin-bottom: 8px;">{{ $stat->user->name }}</div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--ent-text-muted); margin-bottom: 6px;">
                            <span>Attendance Rate</span>
                            <span style="color: var(--ent-danger); font-weight: 700;">{{ $stat->rate }}%</span>
                        </div>
                        <div style="height: 6px; background: rgba(255,255,255,0.06); border-radius: 99px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $stat->rate }}%; background: #f87171; border-radius: 99px;"></div>
                        </div>
                    </div>
                    @endforeach
                    @if($atRiskStudents->count() > 3)
                        <div style="text-align: center; margin-top: 8px;">
                            <a href="{{ route('teacher.classroom.index') }}" style="font-size: 0.85rem; color: #b39b82; text-decoration: none;">View all at-risk students <i class="bi bi-arrow-right"></i></a>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
        @endif
        
    </div>
</div>

@endsection

@section('scripts')
<style>
    .fc-theme-standard td, .fc-theme-standard th {
        border-color: rgba(255,255,255,0.06) !important;
    }
    .fc .fc-toolbar-title {
        font-size: 1.1rem !important;
        font-weight: 700;
        color: var(--ent-text);
    }
    .fc .fc-col-header-cell-cushion {
        color: #b39b82;
        font-weight: 600;
        padding: 8px 0 !important;
        text-transform: uppercase;
        font-size: 0.75rem;
    }
    .fc .fc-daygrid-day-number {
        color: var(--ent-text);
        font-size: 0.85rem;
        font-weight: 600;
        padding: 8px !important;
    }
    .fc .fc-day-today {
        background: rgba(251,191,36,0.05) !important;
    }
    .fc .fc-daygrid-event {
        border-radius: 6px !important;
        padding: 3px 6px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #1a1a2e !important;
        margin: 2px 6px !important;
        transition: all 0.2s;
        border: none !important;
    }
    .fc .fc-daygrid-event:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.4) !important;
        filter: brightness(1.1);
    }
    .fc .fc-daygrid-day-frame:hover {
        background: rgba(255,255,255,0.02);
    }
</style>

@if(count($weeklyLabels ?? []) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('weeklyChart').getContext('2d');
Chart.defaults.color = '#b39b82';
Chart.defaults.font.family = "'Inter', sans-serif";

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: @json($weeklyLabels),
        datasets: [{
            label: 'Present',
            data: @json($weeklyPresent),
            backgroundColor: 'rgba(74, 222, 128, 0.8)',
            borderColor: 'rgba(74, 222, 128, 1)',
            borderWidth: 1,
            borderRadius: 6,
        }, {
            label: 'Late',
            data: @json($weeklyLate),
            backgroundColor: 'rgba(245, 158, 11, 0.8)',
            borderColor: 'rgba(245, 158, 11, 1)',
            borderWidth: 1,
            borderRadius: 6,
        }, {
            label: 'Absent',
            data: @json($weeklyAbsent),
            backgroundColor: 'rgba(239, 68, 68, 0.8)',
            borderColor: 'rgba(239, 68, 68, 1)',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    padding: 20,
                    font: { weight: '600', size: 11 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 11, 8, 0.95)',
                titleColor: '#f3e7cd',
                bodyColor: '#e7dcc8',
                borderColor: 'rgba(207,164,111,0.2)',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 10
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255, 225, 150, 0.04)', drawBorder: false },
                ticks: { stepSize: 1, font: { weight: '500' } }
            },
            x: {
                grid: { display: false, drawBorder: false },
                ticks: { font: { weight: '600' } }
            }
        }
    }
});
</script>
@endif

<script>
// Skeleton -> Real Content
document.addEventListener('DOMContentLoaded', function() {
    var skelStats = document.getElementById('skelStats');
    var realStats = document.getElementById('realStats');
    if (skelStats && realStats) { skelStats.style.display = 'none'; realStats.style.display = ''; }
});

// Real-time clock
(function() {
    const clockEl = document.getElementById('teacherClock');
    function tick() {
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        const short = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
        if (clockEl) clockEl.textContent = short;
    }
    setInterval(tick, 1000);
    tick();
})();

function filterRecentLogs() {
    const input = document.getElementById('recentLogsSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.attendance-row');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? 'flex' : 'none';
    });
}

// School Calendar Initialization
let schoolCalendar;
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('schoolCalendar');
    if (!calendarEl) return;
    
    schoolCalendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: false,
        height: 'auto',
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch(`{{ route('teacher.calendar.data') }}?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
                .then(response => response.json())
                .then(data => {
                    const events = data.map(event => ({
                        id: event.id,
                        title: event.name || event.title,
                        start: event.date || event.start,
                        end: event.end,
                        backgroundColor: event.color || '#4ade80',
                        borderColor: event.color || '#4ade80',
                        textColor: '#ffffff',
                        extendedProps: {
                            type: event.type_label || event.type,
                            description: event.description,
                            location: event.location,
                            status: event.status
                        }
                    }));
                    successCallback(events);
                })
                .catch(error => {
                    console.error('Error fetching calendar data:', error);
                    failureCallback(error);
                });
        },
        dayCellClassNames: function(arg) {
            const today = new Date();
            if (arg.date.toDateString() === today.toDateString()) {
                return ['fc-day-today'];
            }
            return [];
        }
    });
    
    schoolCalendar.render();
    updateCalendarTitle();
});}

function updateCalendarTitle() {
    if(schoolCalendar) {
        document.getElementById('calendarTitle').textContent = schoolCalendar.view.title;
    }
}
</script>
@endsection
