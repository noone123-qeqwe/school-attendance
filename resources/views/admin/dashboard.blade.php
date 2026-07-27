@extends('layouts.admin_premium')

@section('title', 'Admin Dashboard')

@section('content')
<div class="anim-slide-up" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:24px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Command Center</h1>
        <p class="saas-text-muted" style="margin:0;">Overview of academic and attendance operations.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button class="saas-btn saas-btn-secondary">
            <i class="bi bi-calendar"></i> Today: {{ now()->format('M d, Y') }}
        </button>
        <button class="saas-btn saas-btn-primary">
            <i class="bi bi-cloud-arrow-down"></i> Generate Report
        </button>
    </div>
</div>

<!-- Primary KPIs -->
<div class="anim-slide-up delay-1" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:24px;">
    
    <div class="saas-card">
        <div class="saas-card-body" style="display:flex; align-items:center; gap:16px;">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(207,164,111,0.15);color:var(--saas-gold);display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <div class="saas-text-muted" style="font-size:0.75rem;text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">Total Students</div>
                <div class="saas-heading" style="font-size:1.75rem;line-height:1;">{{ number_format($totalStudents) }}</div>
            </div>
        </div>
    </div>
    
    <div class="saas-card">
        <div class="saas-card-body" style="display:flex; align-items:center; gap:16px;">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(207,164,111,0.15);color:var(--saas-gold);display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                <i class="bi bi-person-workspace"></i>
            </div>
            <div>
                <div class="saas-text-muted" style="font-size:0.75rem;text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">Instructors</div>
                <div class="saas-heading" style="font-size:1.75rem;line-height:1;">{{ number_format($totalTeachers) }}</div>
            </div>
        </div>
    </div>

    <div class="saas-card">
        <div class="saas-card-body" style="display:flex; align-items:center; gap:16px;">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(207,164,111,0.15);color:var(--saas-gold);display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                <i class="bi bi-building"></i>
            </div>
            <div>
                <div class="saas-text-muted" style="font-size:0.75rem;text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">Departments</div>
                <div class="saas-heading" style="font-size:1.75rem;line-height:1;">{{ number_format($totalDepartments) }}</div>
            </div>
        </div>
    </div>

    <div class="saas-card">
        <div class="saas-card-body" style="display:flex; align-items:center; gap:16px;">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(207,164,111,0.15);color:var(--saas-gold);display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                <i class="bi bi-diagram-3"></i>
            </div>
            <div>
                <div class="saas-text-muted" style="font-size:0.75rem;text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">Sections</div>
                <div class="saas-heading" style="font-size:1.75rem;line-height:1;">{{ number_format($totalSections) }}</div>
            </div>
        </div>
    </div>

</div>

<!-- Secondary Stats Row (Attendance Focus) -->
<div class="anim-slide-up delay-2" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:24px;">
    
    <div class="saas-card">
        <div class="saas-card-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <div class="saas-text-muted" style="font-weight:500;">Present Today</div>
                <span class="saas-badge saas-badge-success"><i class="bi bi-check2-circle" style="margin-right:4px;"></i> Good</span>
            </div>
            <div style="display:flex;align-items:baseline;gap:12px;">
                <div class="saas-heading" style="font-size:2rem;line-height:1;color:var(--saas-success);">{{ number_format($totalPresent) }}</div>
                @php $presentDiff = $totalPresent - $yesterdayPresent; @endphp
                <div style="font-size:0.8rem;color:{{ $presentDiff >= 0 ? 'var(--saas-success)' : 'var(--saas-danger)' }};">
                    {{ $presentDiff > 0 ? '↑' : ($presentDiff < 0 ? '↓' : '→') }} {{ abs($presentDiff) }} vs yesterday
                </div>
            </div>
        </div>
    </div>
    
    <div class="saas-card">
        <div class="saas-card-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <div class="saas-text-muted" style="font-weight:500;">Late Today</div>
                <span class="saas-badge saas-badge-warning"><i class="bi bi-exclamation-triangle" style="margin-right:4px;"></i> Notice</span>
            </div>
            <div style="display:flex;align-items:baseline;gap:12px;">
                <div class="saas-heading" style="font-size:2rem;line-height:1;color:var(--saas-warning);">{{ number_format($totalLate) }}</div>
                @php $lateDiff = $totalLate - $yesterdayLate; @endphp
                <div style="font-size:0.8rem;color:{{ $lateDiff <= 0 ? 'var(--saas-success)' : 'var(--saas-warning)' }};">
                    {{ $lateDiff > 0 ? '↑' : ($lateDiff < 0 ? '↓' : '→') }} {{ abs($lateDiff) }} vs yesterday
                </div>
            </div>
        </div>
    </div>
    
    <div class="saas-card">
        <div class="saas-card-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <div class="saas-text-muted" style="font-weight:500;">Absent Today</div>
                <span class="saas-badge saas-badge-danger"><i class="bi bi-x-circle" style="margin-right:4px;"></i> Critical</span>
            </div>
            <div style="display:flex;align-items:baseline;gap:12px;">
                <div class="saas-heading" style="font-size:2rem;line-height:1;color:var(--saas-danger);">{{ number_format($totalAbsent) }}</div>
                @php $absentDiff = $totalAbsent - $yesterdayAbsent; @endphp
                <div style="font-size:0.8rem;color:{{ $absentDiff <= 0 ? 'var(--saas-success)' : 'var(--saas-danger)' }};">
                    {{ $absentDiff > 0 ? '↑' : ($absentDiff < 0 ? '↓' : '→') }} {{ abs($absentDiff) }} vs yesterday
                </div>
            </div>
        </div>
    </div>
    
    <div class="saas-card" style="border-color:{{ $attendanceRate >= 80 ? 'rgba(74,222,128,0.3)' : 'rgba(248,113,113,0.3)' }};">
        <div class="saas-card-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <div class="saas-text-muted" style="font-weight:500;">Overall Attendance Rate</div>
            </div>
            <div style="display:flex;align-items:baseline;gap:12px;">
                <div class="saas-heading" style="font-size:2rem;line-height:1;color:{{ $attendanceRate >= 80 ? 'var(--saas-success)' : 'var(--saas-danger)' }};">{{ $attendanceRate }}%</div>
                @php $rateDiff = $attendanceRate - $yesterdayRate; @endphp
                <div style="font-size:0.8rem;color:{{ $rateDiff >= 0 ? 'var(--saas-success)' : 'var(--saas-danger)' }};">
                    {{ $rateDiff > 0 ? '↑' : ($rateDiff < 0 ? '↓' : '→') }} {{ abs($rateDiff) }}% vs yesterday
                </div>
            </div>
            <!-- mini progress bar -->
            <div style="height:4px;width:100%;background:rgba(255,255,255,0.1);border-radius:2px;margin-top:12px;overflow:hidden;">
                <div style="height:100%;width:{{ $attendanceRate }}%;background:{{ $attendanceRate >= 80 ? 'var(--saas-success)' : 'var(--saas-danger)' }};border-radius:2px;"></div>
            </div>
        </div>
    </div>

</div>

<!-- Charts & Tables Row -->
<div class="anim-slide-up delay-3" style="display:grid; grid-template-columns:1fr; gap:24px; margin-bottom:24px;">
    
    <!-- Weekly Attendance Chart -->
    <div class="saas-card">
        <div class="saas-card-header">
            <div class="saas-heading saas-heading-sm">Weekly Attendance Trend</div>
        </div>
        <div class="saas-card-body">
            <div id="weeklyChart" style="min-height:300px;"></div>
        </div>
    </div>
    
</div>

<div class="anim-slide-up delay-3" style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;">
    
    <!-- Live Sessions -->
    <div class="saas-card">
        <div class="saas-card-header">
            <div class="saas-heading saas-heading-sm">Live QR Sessions ({{ $activeSessionCount }})</div>
            <a href="#" class="saas-btn saas-btn-secondary" style="padding:4px 10px;font-size:0.75rem;">View All</a>
        </div>
        <div class="saas-table-container" style="border:none;border-radius:0;">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th>Subject & Teacher</th>
                        <th>Checked In</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activeSessions->take(5) as $session)
                    <tr>
                        <td>
                            <div style="font-weight:500;">{{ $session->subject?->name ?? $session->subject_code }}</div>
                            <div class="saas-text-muted" style="font-size:0.75rem;">{{ $session->creator?->name ?? 'Unknown' }}</div>
                        </td>
                        <td><span class="saas-badge saas-badge-default">{{ $session->checked_in_count }} students</span></td>
                        <td>
                            <span class="saas-badge {{ strtolower($session->qr_status) == 'active' ? 'saas-badge-success' : 'saas-badge-warning' }}">
                                {{ $session->qr_status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align:center;padding:30px;">
                            <div class="saas-text-muted">No active sessions at the moment.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- At Risk Students -->
    <div class="saas-card">
        <div class="saas-card-header">
            <div class="saas-heading saas-heading-sm">At-Risk Students</div>
            <a href="{{ route('admin.students') }}" class="saas-btn saas-btn-secondary" style="padding:4px 10px;font-size:0.75rem;">View All</a>
        </div>
        <div class="saas-table-container" style="border:none;border-radius:0;">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Rate</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($atRiskStudents->take(5) as $student)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <img src="{{ $student->profile_image ? (str_starts_with($student->profile_image, 'http') ? $student->profile_image : asset('storage/'.$student->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&background=800000&color=fff' }}"
                                     style="width:28px;height:28px;border-radius:50%;object-fit:cover;">
                                <div style="font-weight:500;font-size:0.8rem;">{{ $student->name }}</div>
                            </div>
                        </td>
                        <td><span style="font-size:0.8rem;">{{ $student->course }}</span></td>
                        <td>
                            <span class="saas-badge {{ $student->attendance_rate >= 70 ? 'saas-badge-warning' : 'saas-badge-danger' }}">
                                {{ $student->attendance_rate }}%
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.student', $student->id) }}" class="saas-btn saas-btn-secondary" style="padding:4px 8px;font-size:0.7rem;">Review</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:30px;">
                            <div class="saas-text-muted">No students currently at risk.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var options = {
        series: [
            { name: 'Present', data: {!! json_encode($weeklyPresent) !!} },
            { name: 'Late', data: {!! json_encode($weeklyLate) !!} },
            { name: 'Absent', data: {!! json_encode($weeklyAbsent) !!} }
        ],
        chart: {
            type: 'bar',
            height: 300,
            stacked: true,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#4ade80', '#fbbf24', '#f87171'],
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: false,
                columnWidth: '40%',
            },
        },
        xaxis: {
            categories: {!! json_encode($weeklyLabels) !!},
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: '#8f826f', fontSize: '12px' } }
        },
        yaxis: {
            labels: { style: { colors: '#8f826f', fontSize: '12px' } }
        },
        grid: {
            borderColor: 'rgba(255,255,255,0.05)',
            strokeDashArray: 4,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            labels: { colors: '#e7d4b8' }
        },
        fill: { opacity: 1 },
        theme: { mode: 'dark' },
        tooltip: {
            theme: 'dark',
            y: { formatter: function (val) { return val + " students" } }
        }
    };

    var chart = new ApexCharts(document.querySelector("#weeklyChart"), options);
    chart.render();
});
</script>
@endpush
