@extends('parent.layout')
@section('page-title', 'Parent Dashboard')

@section('content')
<style>
    /* Premium Header Glow */
    .dashboard-header-glow {
        background: linear-gradient(135deg, rgba(207, 164, 111, 0.15) 0%, rgba(128, 0, 0, 0.05) 100%);
        border: 1px solid rgba(207, 164, 111, 0.2);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2), inset 0 1px 1px rgba(255,255,255,0.05);
        border-radius: 16px;
        padding: 24px;
        position: relative;
        overflow: hidden;
    }
    .dashboard-header-glow::before {
        content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
        background: radial-gradient(circle, rgba(207, 164, 111, 0.08) 0%, transparent 60%);
        opacity: 0; transition: opacity 0.5s; pointer-events: none;
    }
    .dashboard-header-glow:hover::before { opacity: 1; }

    /* Child Card Glassmorphism & Hover */
    .child-card {
        background: rgba(26, 17, 13, 0.6) !important;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(207, 164, 111, 0.15) !important;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease !important;
        border-radius: 20px !important;
        overflow: hidden;
    }
    .child-card:hover {
        transform: translateY(-4px) !important;
        box-shadow: 0 20px 40px rgba(0,0,0,0.4), 0 0 20px rgba(207, 164, 111, 0.05) !important;
        border-color: rgba(207, 164, 111, 0.3) !important;
    }

    /* Child Avatar Image */
    .profile-img-container {
        position: relative;
    }

    /* Stat Box Hover */
    .stat-box {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        background: rgba(255,235,190,0.03) !important;
        position: relative;
        overflow: hidden;
    }
    .stat-box::after {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(255,255,255,0.03) 0%, transparent 100%);
        opacity: 0; transition: opacity 0.3s;
    }
    .stat-box:hover {
        transform: translateY(-3px) scale(1.02) !important;
        background: rgba(255,235,190,0.06) !important;
        border-color: rgba(207, 164, 111, 0.25) !important;
        box-shadow: 0 10px 20px rgba(0,0,0,0.2) !important;
    }
    .stat-box:hover::after { opacity: 1; }

    /* Action Buttons */
    .action-btn {
        transition: all 0.2s ease !important;
        border-radius: 10px !important;
        overflow: hidden;
        position: relative;
    }
    .action-btn i { transition: transform 0.2s ease; display: inline-block; }
    .action-btn:hover i { transform: translateX(2px) scale(1.1); }
    
    /* Table Rows Hover */
    .custom-table tbody tr { transition: all 0.2s ease; border-bottom: 1px solid rgba(255,215,145,0.04); }
    .custom-table tbody tr:hover {
        background: rgba(207, 164, 111, 0.04) !important;
        transform: scale(1.002);
    }
    
    /* Chart Container Gradient */
    .panel-section {
        background: linear-gradient(180deg, rgba(30,20,15,0.6) 0%, rgba(20,12,8,0.4) 100%);
        border: 1px solid rgba(255,215,145,0.08);
        border-radius: 16px;
        padding: 20px;
        transition: border-color 0.3s;
    }
    .panel-section:hover { border-color: rgba(255,215,145,0.15); }

    /* Mobile Attendance Card */
    .mobile-att-card {
        background: rgba(26, 17, 13, 0.4) !important;
        border: 1px solid rgba(255,215,145,0.08) !important;
        border-radius: 12px;
        padding: 16px;
        transition: all 0.2s ease;
    }
    .mobile-att-card:active {
        transform: scale(0.98);
        background: rgba(26, 17, 13, 0.6) !important;
    }

    /* ── PARENT DASHBOARD MOBILE EXTRAS ── */
    @media (max-width: 768px) {
        /* Header strip */
        .dashboard-header-glow { padding: 14px 16px; }
        .dashboard-header-glow h2 { font-size: 1.1rem !important; }
        /* Child card header — stack on mobile */
        .child-card-header { flex-direction: column !important; align-items: flex-start !important; gap: 12px !important; }
        .action-buttons { width: 100%; display: flex !important; gap: 8px !important; flex-wrap: wrap !important; }
        .action-buttons .adm-btn { flex: 1; justify-content: center !important; font-size: 0.78rem; padding: 8px 10px; }
        /* Stats grid 2 cols */
        .stat-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 10px !important; }
        .stat-grid-5 { grid-template-columns: repeat(2, 1fr) !important; }
        /* Make circular progress smaller */
        .circular-progress svg { width: 60px !important; height: 60px !important; }
        /* Recent attendance table card view */
        .custom-table { font-size: 0.78rem !important; }
        .adm-card-body.p-4 { padding: 14px !important; }
        /* Child avatar */
        .child-avatar { width: 44px !important; height: 44px !important; }
    }
</style>

{{-- Mobile header for parent dashboard --}}
<div class="mobile-dash-header d-md-none anim-slide-up" style="margin:0 0 16px;">
    <div>
        <div class="mobile-dash-title">Parent Portal</div>
        <div class="mobile-dash-subtitle">Monitor your children's attendance</div>
    </div>
    <div class="mobile-dash-date">
        <div style="font-size:1rem;font-weight:800;color:#cfa46f;">{{ now()->format('d') }}</div>
        <div>{{ now()->format('M Y') }}</div>
    </div>
</div>

    <div class="dashboard-header-glow d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <h2 style="color: #f3e7cd; font-weight: 800; margin: 0; letter-spacing: -0.5px; font-size: clamp(1.5rem, 4vw, 2rem);">
            <i class="bi bi-house-door-fill" style="color: #cfa46f; margin-right: 12px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"></i>My Children
        </h2>
        <a href="{{ route('parent.link.form') }}" class="adm-btn adm-btn-primary action-btn w-100 w-md-auto justify-content-center">
            <i class="bi bi-link-45deg me-1"></i> Link Another Child
        </a>
    </div>

    @forelse($childrenData as $data)
    <div class="adm-card child-card mb-5">
        {{-- Child Header --}}
        <div class="adm-card-head child-card-header">
            <div class="d-flex align-items-center gap-3">
                <div class="profile-img-container">
                    <img src="{{ $data->child->profile_image ? (str_starts_with($data->child->profile_image, 'http') ? $data->child->profile_image : asset('storage/'.$data->child->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($data->child->name).'&background=800000&color=fff' }}"
                         alt="{{ $data->child->name }}" class="rounded-circle child-avatar">
                </div>
                <div>
                    <h4 class="child-name">{{ $data->child->name }}</h4>
                    <span class="child-meta">
                        <i class="bi bi-person-badge me-1"></i> {{ $data->child->student_number }} &bull; {{ $data->child->course }} â€” Year {{ $data->child->year_level }}
                    </span>
                </div>
            </div>
            <div class="d-flex gap-2 action-buttons">
                <a href="{{ route('parent.child', $data->child) }}" class="adm-btn action-btn">
                    <i class="bi bi-list-ul"></i> Full Records
                </a>
                <a href="{{ route('parent.child.warnings', $data->child) }}" class="adm-btn adm-btn-ghost action-btn position-relative">
                    <i class="bi bi-exclamation-triangle"></i> Warnings
                    @if($data->warnings->count() > 0)
                        <span class="warning-badge">{{ $data->warnings->count() }}</span>
                    @endif
                </a>
                <a href="{{ route('parent.child.report', $data->child) }}" class="adm-btn adm-btn-primary action-btn">
                    <i class="bi bi-file-earmark-pdf"></i> Report
                </a>
            </div>
        </div>

        <div class="adm-card-body p-4">
            {{-- Stats Cards --}}
            <div class="stat-grid stat-grid-5 mb-4">
                <div class="adm-stat stat-box stat-rate">
                    @php
                        $rateColor = $data->rate >= 90 ? '#4ade80' : ($data->rate >= 75 ? '#fbbf24' : '#f87171');
                        $circumference = 2 * pi() * 32; // radius 32
                        $dashoffset = $circumference - ($data->rate / 100) * $circumference;
                        $rateGlow = $data->rate >= 90 ? 'rgba(74, 222, 128, 0.4)' : ($data->rate >= 75 ? 'rgba(251, 191, 36, 0.4)' : 'rgba(248, 113, 113, 0.4)');
                    @endphp
                    <div class="circular-progress">
                        <svg width="80" height="80" viewBox="0 0 80 80">
                            <circle cx="40" cy="40" r="32" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="8" />
                            <circle cx="40" cy="40" r="32" fill="none" stroke="{{ $rateColor }}" stroke-width="8" 
                                    stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashoffset }}" 
                                    stroke-linecap="round" style="transition: stroke-dashoffset 1.5s ease-out; filter: drop-shadow(0 0 6px {{ $rateGlow }});" />
                        </svg>
                        <div class="progress-text" style="color: {{ $rateColor }};">{{ $data->rate }}<span>%</span></div>
                    </div>
                    <div class="adm-stat-lbl">Attendance Rate</div>
                </div>
                <div class="adm-stat stat-box">
                    <div class="stat-icon" style="background: rgba(102, 187, 106, 0.1); color: #66bb6a;"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="adm-stat-val" style="color: #66bb6a;">{{ $data->present }}</div>
                    <div class="adm-stat-lbl">Present</div>
                </div>
                <div class="adm-stat stat-box">
                    <div class="stat-icon" style="background: rgba(255, 167, 38, 0.1); color: #ffa726;"><i class="bi bi-clock-fill"></i></div>
                    <div class="adm-stat-val" style="color: #ffa726;">{{ $data->late }}</div>
                    <div class="adm-stat-lbl">Late</div>
                </div>
                <div class="adm-stat stat-box">
                    <div class="stat-icon" style="background: rgba(239, 83, 80, 0.1); color: #ef5350;"><i class="bi bi-x-circle-fill"></i></div>
                    <div class="adm-stat-val" style="color: #ef5350;">{{ $data->absent }}</div>
                    <div class="adm-stat-lbl">Absent</div>
                </div>
                <div class="adm-stat stat-box">
                    <div class="stat-icon" style="background: rgba(66, 165, 245, 0.1); color: #42a5f5;"><i class="bi bi-fire"></i></div>
                    <div class="adm-stat-val" style="color: #42a5f5;">{{ $data->streak }}</div>
                    <div class="adm-stat-lbl">Day Streak</div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                {{-- 30-Day Attendance Trend Chart --}}
                <div class="col-lg-8">
                    <div class="panel-section h-100">
                        <h6 class="panel-title">
                            <i class="bi bi-graph-up"></i> 30-Day Attendance Trend
                        </h6>
                        <div class="chart-container" style="position: relative; height: 180px; width: 100%;">
                            <canvas id="trendChart_{{ $data->child->id }}"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Warnings Panel --}}
                <div class="col-lg-4">
                    <div class="panel-section h-100 d-flex flex-column">
                        <h6 class="panel-title">
                            <i class="bi bi-exclamation-triangle"></i> Recent Warnings
                        </h6>
                        <div class="warnings-container flex-grow-1">
                            @if($data->warnings->count() > 0)
                                @foreach($data->warnings as $warning)
                                <div class="warning-item">
                                    <div class="warning-subject">
                                        {{ $warning->subject->name ?? $warning->subject_code }}
                                    </div>
                                    <div class="warning-message">
                                        {{ Str::limit($warning->message, 80) }}
                                    </div>
                                    <div class="warning-time">
                                        <i class="bi bi-clock me-1"></i>{{ $warning->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="empty-state-sm h-100 d-flex flex-column align-items-center justify-content-center py-4">
                                    <i class="bi bi-shield-check" style="font-size: 2.5rem; color: #66bb6a; margin-bottom: 12px; filter: drop-shadow(0 0 10px rgba(102, 187, 106, 0.3));"></i>
                                    <span style="font-size: 0.9rem; color: #b39b82; font-weight: 500;">No warnings â€” great job!</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Attendance Table --}}
            <div class="panel-section">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="panel-title mb-0">
                        <i class="bi bi-clock-history"></i> Recent Attendance
                    </h6>
                    @if($data->pendingExcuses > 0)
                        <span class="badge-pending">
                            {{ $data->pendingExcuses }} pending excuse(s)
                        </span>
                    @endif
                </div>

                @if($data->child->attendances->count() > 0)
                <div class="custom-table-wrapper">
                    <div class="table-responsive d-none d-md-block">
                        <table class="adm-table custom-table mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Time In</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data->child->attendances as $attendance)
                                <tr>
                                    <td class="fw-medium text-light">{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</td>
                                    <td style="color: #b39b82;">{{ $attendance->subject->name ?? $attendance->subject_code }}</td>
                                    <td>
                                        @if($attendance->excused)
                                            <span class="status-badge badge-excused"><i class="bi bi-check2-circle me-1"></i>Excused</span>
                                        @elseif($attendance->status === 'Present')
                                            <span class="status-badge badge-present"><i class="bi bi-check-circle me-1"></i>Present</span>
                                        @elseif($attendance->status === 'Late')
                                            <span class="status-badge badge-late"><i class="bi bi-clock-history me-1"></i>Late</span>
                                        @else
                                            <span class="status-badge badge-absent"><i class="bi bi-x-circle me-1"></i>Absent</span>
                                        @endif
                                    </td>
                                    <td style="color: #b39b82;">
                                        @if($attendance->time_in)
                                            <i class="bi bi-stopwatch me-1" style="color: #8f826f; font-size: 0.8rem;"></i>{{ \Carbon\Carbon::parse($attendance->time_in)->format('h:i A') }}
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->status === 'Absent' && !$attendance->excused)
                                            <a href="{{ route('parent.child.excuse', [$data->child, $attendance]) }}" class="adm-btn adm-btn-ghost submit-excuse-btn">
                                                <i class="bi bi-pencil-square"></i> Submit Excuse
                                            </a>
                                        @else
                                            <span style="color: #5a5246; font-size: 0.85rem;">â€”</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile Cards View --}}
                    <div class="d-md-none p-3 mobile-att-cards">
                        @foreach($data->child->attendances as $attendance)
                        <div class="mobile-att-card mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold" style="color: #f3e7cd; font-size: 0.9rem;">{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</span>
                                <div>
                                    @if($attendance->excused)
                                        <span class="status-badge badge-excused"><i class="bi bi-check2-circle me-1"></i>Excused</span>
                                    @elseif($attendance->status === 'Present')
                                        <span class="status-badge badge-present"><i class="bi bi-check-circle me-1"></i>Present</span>
                                    @elseif($attendance->status === 'Late')
                                        <span class="status-badge badge-late"><i class="bi bi-clock-history me-1"></i>Late</span>
                                    @else
                                        <span class="status-badge badge-absent"><i class="bi bi-x-circle me-1"></i>Absent</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-2" style="color: #b39b82; font-size: 0.85rem;">
                                <i class="bi bi-book me-1"></i> {{ $attendance->subject->name ?? $attendance->subject_code }}
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3" style="border-top: 1px solid rgba(255,215,145,0.06);">
                                <div style="color: #e7dcc8; font-size: 0.85rem; display: flex; align-items: center; gap: 6px;">
                                    <i class="bi bi-stopwatch" style="color: #8f826f;"></i>
                                    @if($attendance->time_in)
                                        <span>{{ \Carbon\Carbon::parse($attendance->time_in)->format('h:i A') }}</span>
                                    @else
                                        <span style="color: #8f826f;">--</span>
                                    @endif
                                </div>
                                @if($attendance->status === 'Absent' && !$attendance->excused)
                                    <a href="{{ route('parent.child.excuse', [$data->child, $attendance]) }}" class="adm-btn adm-btn-ghost px-2 py-1" style="font-size: 0.75rem;">
                                        <i class="bi bi-pencil-square"></i> Excuse
                                    </a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="empty-state py-4">
                    <div class="empty-icon-wrapper mb-3" style="font-size: 2rem; color: #8f826f;">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <span style="color: #b39b82; font-weight: 500;">No attendance records yet.</span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="adm-card empty-card">
        <div class="adm-card-body d-flex flex-column align-items-center justify-content-center" style="min-height: 400px;">
            <div class="empty-state-large text-center">
                <div class="icon-circle mb-4">
                    <i class="bi bi-people"></i>
                </div>
                <h4 style="color: #f3e7cd; font-weight: 700; margin-bottom: 8px;">No Children Linked</h4>
                <p style="color: #8f826f; margin-bottom: 24px; max-width: 300px; line-height: 1.5; margin-left: auto; margin-right: auto;">You currently don't have any children linked to your account. You can link your child's account by entering their Student ID.</p>
                <a href="{{ route('parent.link.form') }}" class="adm-btn adm-btn-primary action-btn-lg">
                    <i class="bi bi-link-45deg me-2"></i>Link a Child
                </a>
            </div>
        </div>
    </div>
    @endforelse
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@foreach($childrenData as $data)
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('trendChart_{{ $data->child->id }}').getContext('2d');
    
    // Create gradient for Present/Late
    const presentGradient = ctx.createLinearGradient(0, 0, 0, 180);
    presentGradient.addColorStop(0, 'rgba(102,187,106,0.25)');
    presentGradient.addColorStop(1, 'rgba(102,187,106,0.01)');
    
    // Create gradient for Absent
    const absentGradient = ctx.createLinearGradient(0, 0, 0, 180);
    absentGradient.addColorStop(0, 'rgba(239,83,80,0.25)');
    absentGradient.addColorStop(1, 'rgba(239,83,80,0.01)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($data->trendLabels),
            datasets: [
                {
                    label: 'Present/Late',
                    data: @json($data->trendPresent),
                    borderColor: '#4ade80',
                    backgroundColor: presentGradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointBackgroundColor: '#1a1d24',
                    pointBorderColor: '#4ade80',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#4ade80',
                    pointHoverBorderColor: '#fff',
                },
                {
                    label: 'Absent',
                    data: @json($data->trendAbsent),
                    borderColor: '#f87171',
                    backgroundColor: absentGradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointBackgroundColor: '#1a1d24',
                    pointBorderColor: '#f87171',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#f87171',
                    pointHoverBorderColor: '#fff',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: { 
                        color: '#b39b82', 
                        font: { size: 11, family: 'Inter', weight: '500' }, 
                        boxWidth: 10,
                        boxHeight: 10,
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(26, 29, 36, 0.95)',
                    titleColor: '#cfa46f',
                    bodyColor: '#f3e7cd',
                    borderColor: 'rgba(207,164,111,0.2)',
                    borderWidth: 1,
                    padding: 12,
                    boxPadding: 6,
                    usePointStyle: true,
                    titleFont: { family: 'Inter', size: 12, weight: '600' },
                    bodyFont: { family: 'Inter', size: 12 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#8f826f', font: { size: 10, family: 'Inter' }, stepSize: 1, padding: 8 },
                    grid: { color: 'rgba(255,215,145,0.04)', drawBorder: false }
                },
                x: {
                    ticks: { color: '#8f826f', font: { size: 9, family: 'Inter' }, maxRotation: 45, padding: 8 },
                    grid: { display: false, drawBorder: false }
                }
            }
        }
    });
});
@endforeach
</script>

@endsection
