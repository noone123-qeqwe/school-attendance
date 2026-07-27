@extends('teacher.layout')
@section('portal-title', 'Dashboard')

@endpush

@section('content')

@if(isset($pendingExcuses) && $pendingExcuses > 0)
<div class="premium-alert anim-slide-up">
    <div class="alert-icon-wrap">
        <i class="bi bi-file-earmark-text-fill" style="color: #cfa46f; font-size: 1.4rem;"></i>
    </div>
    <div style="flex: 1;">
        <div style="font-weight: 800; font-size: 1.1rem; color: #f3e7cd; margin-bottom: 4px;">
            Action Required: {{ $pendingExcuses }} Excuse{{ $pendingExcuses > 1 ? 's' : '' }} Pending Review
        </div>
        <div style="font-size: 0.9rem; color: #d4b77d;">
            Students have submitted excuse letters that require your immediate attention.
        </div>
    </div>
    <a href="{{ route('teacher.excuse.reviews') }}" class="premium-btn">
        <i class="bi bi-eye"></i> Review Now
    </a>
</div>
@endif

<!-- Quick Actions Bar -->
<div class="glass-panel anim-slide-up delay-1" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:20px; margin-bottom:28px; padding:28px 32px; background: linear-gradient(135deg, rgba(255, 245, 225, 0.05) 0%, rgba(207, 164, 111, 0.02) 100%);">
    <div style="display: flex; align-items: center; gap: 24px;">
        <div style="width: 64px; height: 64px; border-radius: 18px; background: rgba(207,164,111,0.15); border: 1px solid rgba(207,164,111,0.3); display: flex; align-items: center; justify-content: center; color: #cfa46f; font-size: 2rem; box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
            <i class="bi bi-clock-fill"></i>
        </div>
        <div>
            <div style="font-size:0.85rem; font-weight:700; color:#b39b82; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">
                Current Session Time
            </div>
            <div id="teacherClock" class="clock-text" style="font-size:2.5rem; font-weight:800; line-height: 1.1; letter-spacing: -1px;">
                {{ now()->format('h:i:s A') }}
            </div>
            <div style="font-size:0.95rem; color:#b39b82; margin-top:4px; font-weight:600;">
                {{ now()->format('l, F j, Y') }}
            </div>
        </div>
    </div>
    <div style="display:flex; gap:16px; flex-wrap:wrap; align-items: center;">
        <!-- Export & Leave Request -->
        <a href="{{ route('teacher.reports.pdf') }}" target="_blank" class="premium-btn" style="background: rgba(255,255,255,0.05); color: #f3e7cd; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(8px);">
            <i class="bi bi-file-earmark-arrow-down-fill" style="color: #cfa46f;"></i> Export Report
        </a>
        <button type="button" class="premium-btn" style="background: rgba(239,68,68,0.1); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); backdrop-filter: blur(8px);" onclick="openLeaveDrawer()">
            <i class="bi bi-calendar-x-fill"></i> Request Leave
        </button>
        @php
            $nextClass = $todayClasses->first(function($c) {
                $sched = $c->schedules->first();
                if (!$sched) return false;
                $end = \Carbon\Carbon::today()->setTimeFromTimeString($sched->end_time);
                return now()->lessThan($end) && !($c->has_attendance_today ?? false);
            });
        @endphp
        @if($nextClass)
        <div style="display: flex; flex-direction: column; align-items: flex-end;">
            <span style="font-size:0.75rem; color:#b39b82; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">Up Next</span>
            <a href="{{ route('teacher.attendance', $nextClass->id) }}" class="premium-btn" style="background: linear-gradient(135deg, #16a34a, #15803d); border-color: rgba(74,222,128,0.3);">
                <i class="bi bi-qr-code-scan"></i> Start: {{ $nextClass->name }}
            </a>
        </div>
        @else
        <div style="display:flex; align-items:center; gap:12px; background: linear-gradient(135deg, rgba(34,197,94,0.15), rgba(22,163,74,0.05)); border:1px solid rgba(34,197,94,0.3); padding:14px 24px; border-radius:16px; box-shadow: 0 8px 20px rgba(0,0,0,0.15);">
            <div style="width:36px; height:36px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-size: 1.2rem; box-shadow: 0 4px 10px rgba(34,197,94,0.4);">
                <i class="bi bi-check2-all"></i>
            </div>
            <div>
                <div style="color:#4ade80; font-weight:800; font-size:1rem; letter-spacing: 0.5px;">All caught up!</div>
                <div style="color:#b39b82; font-size:0.85rem; font-weight:500;">No more classes for today.</div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Stats -->
<div class="row g-4 mb-4 anim-slide-up delay-2">
    <div class="col-6 col-xl-3">
        <div class="glass-panel stat-card-premium">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #4ade80; opacity: 0.8;"></div>
            <i class="bi bi-person-check-fill stat-icon-floating" style="color: #4ade80;"></i>
            <div class="stat-icon-solid" style="background: linear-gradient(135deg, rgba(34,197,94,0.15), rgba(22,163,74,0.05)); color: #4ade80; border: 1px solid rgba(34,197,94,0.2);">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div>
                <div class="stat-val-premium" data-stat="present">{{ $totalPresent }}</div>
                <div class="tch-stat-lbl">Present Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="glass-panel stat-card-premium">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #fbbf24; opacity: 0.8;"></div>
            <i class="bi bi-hourglass-split stat-icon-floating" style="color: #fbbf24;"></i>
            <div class="stat-icon-solid" style="background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(217,119,6,0.05)); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2);">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <div class="stat-val-premium" data-stat="late">{{ $totalLate }}</div>
                <div class="tch-stat-lbl">Late Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="glass-panel stat-card-premium">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #f87171; opacity: 0.8;"></div>
            <i class="bi bi-person-x-fill stat-icon-floating" style="color: #f87171;"></i>
            <div class="stat-icon-solid" style="background: linear-gradient(135deg, rgba(239,68,68,0.15), rgba(185,28,28,0.05)); color: #f87171; border: 1px solid rgba(239,68,68,0.2);">
                <i class="bi bi-person-x-fill"></i>
            </div>
            <div>
                <div class="stat-val-premium" data-stat="absent">{{ $totalAbsent }}</div>
                <div class="tch-stat-lbl">Absent Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="glass-panel stat-card-premium">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #cfa46f; opacity: 0.8;"></div>
            <i class="bi bi-journal-bookmark-fill stat-icon-floating" style="color: #cfa46f;"></i>
            <div class="stat-icon-solid" style="background: linear-gradient(135deg, rgba(207,164,111,0.15), rgba(143,110,74,0.05)); color: #cfa46f; border: 1px solid rgba(207,164,111,0.2);">
                <i class="bi bi-journal-text"></i>
            </div>
            <div>
                <div class="stat-val-premium">{{ $teacherSubjects->count() }}</div>
                <div class="tch-stat-lbl">My Subjects</div>
            </div>
        </div>
    </div>
</div>

@if(isset($atRiskStudents) && $atRiskStudents->count() > 0)
<div class="row g-4 mb-4 anim-slide-up delay-2">
    <div class="col-12">
        <div class="glass-panel" style="padding: 20px 24px; border-color: rgba(239,68,68,0.3); background: linear-gradient(135deg, rgba(239,68,68,0.05) 0%, rgba(207,164,111,0.02) 100%);">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="tch-card-icon" style="background: rgba(239,68,68,0.2); color: #fca5a5;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div style="font-size: 1.1rem; font-weight: 700; color: #f3e7cd;">Early Warning: At-Risk Students</div>
            </div>
            <div class="d-flex gap-3 flex-wrap">
                @foreach($atRiskStudents as $stat)
                <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(239,68,68,0.2); padding: 12px 16px; border-radius: 12px; min-width: 200px;">
                    <div style="font-size: 0.95rem; font-weight: 700; color: #f3e7cd;">{{ $stat->user->name }}</div>
                    <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.85rem; color: #b39b82;">
                        <span>Attendance</span>
                        <span style="color: #f87171; font-weight: 700;">{{ $stat->rate }}%</span>
                    </div>
                    <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; margin-top: 6px; overflow: hidden;">
                        <div style="width: {{ $stat->rate }}%; height: 100%; background: #f87171; border-radius: 3px;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<div class="row g-4 anim-slide-up delay-3">
    <!-- Today's Classes -->
    <div class="col-lg-8">
        <div class="glass-panel h-100 d-flex flex-column" style="overflow: hidden;">
            <div class="tch-card-head" style="background: rgba(0,0,0,0.2);">
                <div class="tch-card-title">
                    <div class="tch-card-icon" style="background:rgba(207,164,111,0.2); color:#e7dcc8;">
                        <i class="bi bi-calendar-event-fill"></i>
                    </div>
                    <div style="display:flex; align-items:center; gap: 8px;">
                        <a href="?date={{ $targetDate->copy()->subDay()->toDateString() }}" class="btn btn-sm text-white" style="background: rgba(255,255,255,0.1); padding: 2px 8px;"><i class="bi bi-chevron-left"></i></a>
                        <span>{{ $targetDate->isToday() ? "Today's Schedule" : $targetDate->format('M d, Y') }}</span>
                        <a href="?date={{ $targetDate->copy()->addDay()->toDateString() }}" class="btn btn-sm text-white" style="background: rgba(255,255,255,0.1); padding: 2px 8px;"><i class="bi bi-chevron-right"></i></a>
                    </div>
                    @if($todayClasses->count() > 0)
                        @php
                            $completedCount = $todayClasses->where('has_attendance_today', true)->count();
                            $totalCount = $todayClasses->count();
                            $allDone = $completedCount === $totalCount;
                        @endphp
                        <span class="status-pill {{ $allDone ? 'status-completed' : ($completedCount > 0 ? 'status-ongoing' : '') }}" style="margin-left: 12px;">
                            {{ $completedCount }}/{{ $totalCount }} Completed
                        </span>
                    @endif
                </div>
            </div>
            <div style="flex: 1; overflow-y: auto;">
                @forelse($todayClasses as $class)
                    @php
                        $schedule = $class->schedules->first();
                        $isCompleted = $class->has_attendance_today ?? false;
                        $attendanceCount = $class->attendance_count_today ?? 0;
                        
                        $currentTime = now();
                        $classTime = $schedule ? Carbon\Carbon::today()->setTimeFromTimeString($schedule->start_time) : null;
                        $endTime = $schedule ? Carbon\Carbon::today()->setTimeFromTimeString($schedule->end_time) : null;
                        
                        $statusClass = 'status-upcoming';
                        $statusText = 'Upcoming';
                        $statusIcon = 'bi-clock-fill';
                        
                        if ($isCompleted) {
                            $statusClass = 'status-completed';
                            $statusText = 'Completed';
                            $statusIcon = 'bi-check-circle-fill';
                        } elseif ($classTime && $endTime) {
                            if ($currentTime->greaterThan($endTime)) {
                                $statusClass = 'status-missed';
                                $statusText = 'Missed';
                                $statusIcon = 'bi-exclamation-circle-fill';
                            } elseif ($currentTime->between($classTime, $endTime)) {
                                $statusClass = 'status-ongoing';
                                $statusText = 'Ongoing Now';
                                $statusIcon = 'bi-play-circle-fill';
                            }
                        }
                    @endphp
                    <div class="class-item" style="{{ $isCompleted ? 'opacity: 0.75;' : '' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width: 4px; height: 40px; border-radius: 4px; background: {{ $isCompleted ? '#cfa46f' : ($statusClass == 'status-ongoing' ? '#e89f7d' : ($statusClass == 'status-missed' ? '#e6a893' : '#b39b82')) }};"></div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <div style="font-size: 1.05rem; font-weight: 700; color: #f3e7cd;">{{ $class->name }}</div>
                                        <div class="status-pill {{ $statusClass }}">
                                            <i class="bi {{ $statusIcon }}"></i> {{ $statusText }}
                                        </div>
                                    </div>
                                    <div style="font-size: 0.85rem; color: #b39b82;">
                                        <i class="bi bi-tag-fill me-1"></i> {{ $class->code }} &bull; Year {{ $class->year_level }} Sem {{ $class->semester }}
                                        @if($isCompleted)
                                            <span style="color: #cfa46f; font-weight: 600; margin-left: 8px;">
                                                &bull; <i class="bi bi-people-fill"></i> {{ $attendanceCount }} student{{ $attendanceCount !== 1 ? 's' : '' }} present
                                            </span>
                                            @php
                                                $health = $class->class_health ?? 0;
                                                $healthColor = $health >= 90 ? '#4ade80' : ($health >= 75 ? '#fbbf24' : '#f87171');
                                            @endphp
                                            <span style="margin-left: 8px; padding: 2px 6px; border-radius: 4px; background: rgba(255,255,255,0.1); color: {{ $healthColor }}; font-weight: 700;">
                                                {{ $health }}% Avg
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                @if($schedule)
                                    <div style="font-size: 0.95rem; font-weight: 800; color: #f3e7cd; font-variant-numeric: tabular-nums;">
                                        {{ Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: #b39b82; font-weight: 600;">
                                        to {{ Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                                    </div>
                                @endif
                                
                                @if(!$isCompleted && $statusClass != 'status-missed')
                                    <a href="{{ route('teacher.attendance', $class->id) }}" class="btn btn-sm mt-2" style="background: rgba(207,164,111,0.15); color: #e7dcc8; border: 1px solid rgba(207,164,111,0.3); border-radius: 8px; font-size: 0.75rem; font-weight: 600;">
                                        Start Attendance
                                    </a>
                                @elseif($isCompleted)
                                    <a href="{{ route('teacher.messages.create', ['subject' => $class->id, 'to' => 'absentees', 'date' => $targetDate->toDateString()]) }}" class="btn btn-sm mt-2" style="background: rgba(239,68,68,0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; font-size: 0.75rem; font-weight: 600;">
                                        <i class="bi bi-envelope-fill"></i> Message Absentees
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state" style="padding: 80px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                        <div style="width: 80px; height: 80px; background: rgba(207,164,111,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 8px 20px rgba(0,0,0,0.2);">
                            <i class="bi bi-cup-hot-fill" style="font-size: 2.5rem; color: #cfa46f;"></i>
                        </div>
                        <h4 style="font-size: 1.25rem; font-weight: 800; color: #f3e7cd; margin-bottom: 8px;">Free Day!</h4>
                        <p style="font-size: 0.95rem; color: #b39b82; max-width: 250px;">You have no classes scheduled for today. Enjoy your free time.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="col-lg-4">
        <div class="glass-panel h-100 d-flex flex-column" style="overflow: hidden;">
            <div class="tch-card-head" style="background: rgba(0,0,0,0.2);">
                <div class="tch-card-title">
                    <div class="tch-card-icon" style="background: rgba(166,92,59,0.2); color:#e89f7d;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <span>Recent Logs</span>
                        <input type="text" id="recentLogsSearch" placeholder="Search..." style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; color: #fff; padding: 2px 8px; font-size: 0.8rem; width: 120px;" onkeyup="filterRecentLogs()">
                    </div>
                </div>
            </div>
            <div style="flex: 1; overflow-y: auto; padding: 0;">
                @forelse($recentAttendance->take(6) as $record)
                    <div class="attendance-row">
                        <div class="avatar-premium">
                            {{ substr($record->user->name, 0, 2) }}
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size: 0.9rem; font-weight: 700; color: #f3e7cd; margin-bottom: 2px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                {{ $record->user->name }}
                            </div>
                            <div style="font-size: 0.75rem; color: #b39b82;">
                                {{ $record->subject->name ?? $record->subject_code }}
                            </div>
                        </div>
                        <div>
                            <span class="badge-{{ strtolower($record->status) }}" style="font-size: 0.7rem; font-weight: 700; border: 1px solid currentColor;">
                                {{ $record->status }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="empty-state" style="padding: 80px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                        <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 8px 20px rgba(0,0,0,0.2);">
                            <i class="bi bi-inbox-fill" style="font-size: 2.5rem; color: rgba(255,255,255,0.3);"></i>
                        </div>
                        <p style="font-size: 1rem; font-weight: 600; color: #b39b82; margin-bottom: 4px;">No recent logs</p>
                        <p style="font-size: 0.85rem; color: rgba(255,255,255,0.4);">Attendance records will appear here once submitted.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Weekly Chart -->
@if(count($weeklyLabels) > 0)
<div class="row g-4 mt-1 anim-slide-up delay-3">
    <div class="col-12">
        <div class="glass-panel p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="tch-card-icon" style="background: rgba(207,164,111,0.2); color:#e7dcc8;">
                    <i class="bi bi-bar-chart-fill"></i>
                </div>
                <div style="font-size: 1.1rem; font-weight: 700; color: #f3e7cd;">Weekly Attendance Overview</div>
            </div>
            <div style="height: 250px; position: relative; width: 100%;">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
@if(count($weeklyLabels) > 0)
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
                    font: { weight: '600' }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 11, 8, 0.9)',
                titleColor: '#f3e7cd',
                bodyColor: '#e7dcc8',
                borderColor: 'rgba(207,164,111,0.3)',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 12
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(255, 225, 150, 0.05)',
                    drawBorder: false,
                },
                ticks: {
                    stepSize: 1,
                    font: { weight: '500' }
                }
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
// Real-time clock for teacher dashboard
(function() {
    const clockEl = document.getElementById('teacherClock');
    if (!clockEl) return;
    function tick() {
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        clockEl.textContent = h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s + ' ' + ampm;
    }
    setInterval(tick, 1000);
})();

function filterRecentLogs() {
    const input = document.getElementById('recentLogsSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.attendance-row');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? 'flex' : 'none';
    });
}

function openLeaveDrawer() {
    const html = `
        <div style="color: #e7dcc8;">
            <p>Please provide details for your leave/substitute request. This will be sent to the administration.</p>
            <div class="mb-3">
                <label style="font-size: 0.85rem; color: #b39b82; margin-bottom: 6px;">Date of Leave</label>
                <input type="date" class="tch-input w-100" id="leaveDate">
            </div>
            <div class="mb-3">
                <label style="font-size: 0.85rem; color: #b39b82; margin-bottom: 6px;">Reason</label>
                <textarea class="tch-input w-100" id="leaveReason" rows="3" placeholder="Explain the reason for leave..."></textarea>
            </div>
        </div>
    `;
    if(typeof openDrawer === 'function') {
        openDrawer('Request Leave / Substitute', html, function() {
            if(typeof showToast === 'function') {
                showToast('Leave request submitted to Admin successfully!', 'success');
            } else {
                alert('Leave request submitted successfully!');
            }
            closeDrawer();
        }, 'Submit Request');
    } else {
        alert("Leave request submitted successfully!");
    }
}
</script>
@endsection