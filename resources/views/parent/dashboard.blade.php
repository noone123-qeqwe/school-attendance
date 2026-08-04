@extends('parent.layout')
@section('page-title', 'Parent Dashboard')

@section('content')
<style>
    /* Child Card Enhanced Hover */
    .child-card-ent {
        background: var(--ent-surface) !important;
        border: 1px solid var(--ent-border) !important;
        border-radius: var(--ent-radius-xl) !important;
        overflow: hidden;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease, border-color 0.3s ease !important;
    }
    .child-card-ent:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 16px 40px rgba(0,0,0,0.35), 0 0 16px rgba(207,164,111,0.04) !important;
        border-color: var(--ent-border-hover) !important;
    }

    /* Stat Box Enhanced */
    .stat-box-ent {
        background: rgba(255,255,255,0.02);
        border: 1px solid var(--ent-border);
        border-radius: var(--ent-radius-md);
        padding: 16px;
        text-align: center;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    .stat-box-ent:hover {
        transform: translateY(-2px);
        background: rgba(255,255,255,0.04);
        border-color: var(--ent-border-hover);
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    /* Mobile cards */
    .mobile-att-card-ent {
        background: rgba(26,17,13,0.4);
        border: 1px solid var(--ent-border);
        border-radius: var(--ent-radius-md);
        padding: 14px 16px;
        transition: background 0.15s;
    }
    .mobile-att-card-ent:active {
        background: rgba(26,17,13,0.6);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .child-card-header-ent { flex-direction: column !important; align-items: flex-start !important; gap: 12px !important; }
        .action-buttons-ent { width: 100%; display: flex !important; gap: 8px !important; flex-wrap: wrap !important; }
        .action-buttons-ent .ent-btn { flex: 1; justify-content: center !important; font-size: 0.75rem; padding: 8px 10px; }
        .stat-grid-ent { grid-template-columns: repeat(2, 1fr) !important; gap: 10px !important; }
        .child-avatar-ent { width: 44px !important; height: 44px !important; }
    }
</style>

{{-- Mobile header --}}
<div class="ent-mobile-header d-md-none ent-fade-up" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
    <div>
        <div class="ent-mobile-header-title" style="font-size:1.45rem; font-weight:800; line-height:1.2;">Parent Portal</div>
        <div class="ent-mobile-header-sub" style="font-size:0.85rem; color:var(--text-secondary); margin-top:4px;">Monitor your children's attendance</div>
    </div>
    <div class="ent-mobile-header-date" style="text-align:right;">
        <div class="ent-mobile-header-day" style="font-size:1.1rem;font-weight:800;color:var(--gold);">{{ now()->format('d') }}</div>
        <div style="font-size:0.75rem; color:var(--text-muted);">{{ now()->format('M Y') }}</div>
    </div>
</div>

{{-- Desktop Header --}}
<div class="ent-dash-header ent-fade-up d-none d-md-flex" style="margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:14px;">
        <div class="ent-kpi-icon" style="width:52px;height:52px;font-size:1.4rem;background:rgba(207,164,111,0.12);color:var(--ent-gold);">
            <i class="bi bi-house-door-fill"></i>
        </div>
        <div>
            <h2 class="ent-dash-title" style="margin:0;">My Children</h2>
            <p class="ent-dash-subtitle" style="margin:0;">Monitor attendance, performance & warnings</p>
        </div>
    </div>
    <a href="{{ route('parent.link.form') }}" class="ent-btn ent-btn-primary">
        <i class="bi bi-link-45deg"></i> Link Another Child
    </a>
</div>

{{-- Mobile Link Button --}}
<div class="ent-mobile-only d-md-none ent-mb-md">
    <a href="{{ route('parent.link.form') }}" class="ent-btn ent-btn-primary" style="width:100%;justify-content:center;">
        <i class="bi bi-link-45deg"></i> Link Another Child
    </a>
</div>

@forelse($childrenData as $data)
<div class="child-card-ent ent-fade-up ent-delay-{{ $loop->index + 1 }}" style="margin-bottom:32px;">
    {{-- Child Header --}}
    <div class="ent-section-header child-card-header-ent" style="padding:20px 24px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="ent-avatar ent-avatar-round child-avatar-ent" style="width:52px;height:52px;font-size:1rem;">
                <img src="{{ $data->child->profile_image ? (str_starts_with($data->child->profile_image, 'http') ? $data->child->profile_image : asset('storage/'.$data->child->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($data->child->name).'&background=800000&color=fff&size=52' }}"
                     alt="{{ $data->child->name }}">
            </div>
            <div>
                <h4 style="font-size:1.1rem;font-weight:700;color:var(--ent-text);margin:0 0 2px;">{{ $data->child->name }}</h4>
                <span style="font-size:0.78rem;color:var(--ent-text-muted);">
                    <i class="bi bi-person-badge" style="margin-right:2px;"></i> {{ $data->child->student_number }} · {{ $data->child->course }} — Year {{ $data->child->year_level }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2 action-buttons-ent">
            <a href="{{ route('parent.child', $data->child) }}" class="ent-btn ent-btn-sm ent-btn-secondary">
                <i class="bi bi-list-ul"></i> Records
            </a>
            <a href="{{ route('parent.child.warnings', $data->child) }}" class="ent-btn ent-btn-sm ent-btn-secondary" style="position:relative;">
                <i class="bi bi-exclamation-triangle"></i> Warnings
                @if($data->warnings->count() > 0)
                    <span style="position:absolute;top:-4px;right:-4px;background:var(--ent-danger);color:#fff;font-size:0.6rem;font-weight:700;padding:1px 5px;border-radius:99px;">{{ $data->warnings->count() }}</span>
                @endif
            </a>
            <a href="{{ route('parent.child.report', $data->child) }}" class="ent-btn ent-btn-sm ent-btn-primary">
                <i class="bi bi-file-earmark-pdf"></i> Report
            </a>
        </div>
    </div>

    <div style="padding:20px 24px;">
        {{-- Stats Grid --}}
        <div class="stat-grid-ent" style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px;">
            {{-- Attendance Rate Circle --}}
            <div class="stat-box-ent" style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
                @php
                    $rateColor = $data->rate >= 90 ? '#4ade80' : ($data->rate >= 75 ? '#fbbf24' : '#f87171');
                    $circumference = 2 * pi() * 28;
                    $dashoffset = $circumference - ($data->rate / 100) * $circumference;
                @endphp
                <div class="ent-circular-progress">
                    <svg width="68" height="68" viewBox="0 0 68 68">
                        <circle cx="34" cy="34" r="28" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="6" />
                        <circle cx="34" cy="34" r="28" fill="none" stroke="{{ $rateColor }}" stroke-width="6"
                                stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashoffset }}"
                                stroke-linecap="round" style="transition:stroke-dashoffset 1.5s ease-out;filter:drop-shadow(0 0 4px {{ $rateColor }}40);" />
                    </svg>
                    <div class="ent-circular-text">
                        <div class="ent-circular-value" style="color:{{ $rateColor }};font-size:1.1rem;">{{ $data->rate }}<span style="font-size:0.65rem;">%</span></div>
                    </div>
                </div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--ent-text-muted);margin-top:6px;">Rate</div>
            </div>
            <div class="stat-box-ent">
                <div style="width:32px;height:32px;border-radius:var(--ent-radius-sm);background:rgba(102,187,106,0.1);color:#66bb6a;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:0.9rem;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#66bb6a;">{{ $data->present }}</div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--ent-text-muted);margin-top:4px;">Present</div>
            </div>
            <div class="stat-box-ent">
                <div style="width:32px;height:32px;border-radius:var(--ent-radius-sm);background:rgba(255,167,38,0.1);color:#ffa726;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:0.9rem;">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#ffa726;">{{ $data->late }}</div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--ent-text-muted);margin-top:4px;">Late</div>
            </div>
            <div class="stat-box-ent">
                <div style="width:32px;height:32px;border-radius:var(--ent-radius-sm);background:rgba(239,83,80,0.1);color:#ef5350;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:0.9rem;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#ef5350;">{{ $data->absent }}</div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--ent-text-muted);margin-top:4px;">Absent</div>
            </div>
            <div class="stat-box-ent">
                <div style="width:32px;height:32px;border-radius:var(--ent-radius-sm);background:rgba(66,165,245,0.1);color:#42a5f5;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:0.9rem;">
                    <i class="bi bi-fire"></i>
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#42a5f5;">{{ $data->streak }}</div>
                <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--ent-text-muted);margin-top:4px;">Streak</div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- 30-Day Attendance Trend --}}
            <div class="col-lg-8">
                <div class="ent-section h-100">
                    <div class="ent-section-header">
                        <div class="ent-section-title">
                            <div class="ent-section-title-icon" style="background:rgba(74,222,128,0.12);color:var(--ent-success);">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            30-Day Trend
                        </div>
                    </div>
                    <div class="ent-section-body">
                        <div class="ent-chart-container" style="height:180px;">
                            <canvas id="trendChart_{{ $data->child->id }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Warnings Panel --}}
            <div class="col-lg-4">
                <div class="ent-section h-100 d-flex flex-column">
                    <div class="ent-section-header">
                        <div class="ent-section-title">
                            <div class="ent-section-title-icon" style="background:rgba(248,113,113,0.12);color:var(--ent-danger);">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            Warnings
                        </div>
                    </div>
                    <div class="ent-section-body" style="flex:1;">
                        @if($data->warnings->count() > 0)
                            @foreach($data->warnings as $warning)
                            <div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.03);">
                                <div style="font-size:0.82rem;font-weight:700;color:var(--ent-danger);margin-bottom:4px;">
                                    {{ $warning->subject->name ?? $warning->subject_code }}
                                </div>
                                <div style="font-size:0.78rem;color:var(--ent-text-secondary);line-height:1.5;">
                                    {{ Str::limit($warning->message, 80) }}
                                </div>
                                <div style="font-size:0.68rem;color:var(--ent-text-muted);margin-top:4px;">
                                    <i class="bi bi-clock" style="margin-right:2px;"></i>{{ $warning->created_at->diffForHumans() }}
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="ent-empty" style="padding:32px 16px;">
                                <div class="ent-empty-icon" style="width:48px;height:48px;font-size:1.25rem;background:rgba(74,222,128,0.08);color:var(--ent-success);">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div class="ent-empty-title" style="font-size:0.85rem;color:var(--ent-success);">All Clear!</div>
                                <div class="ent-empty-text" style="font-size:0.78rem;">No warnings — great job!</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Attendance Table --}}
        <div class="ent-section">
            <div class="ent-section-header">
                <div class="ent-section-title">
                    <div class="ent-section-title-icon" style="background:rgba(207,164,111,0.12);color:var(--ent-gold);">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    Recent Attendance
                </div>
                @if($data->pendingExcuses > 0)
                    <span class="ent-badge ent-badge-warning">{{ $data->pendingExcuses }} pending excuse(s)</span>
                @endif
            </div>
            <div class="ent-section-body no-pad">
                @if($data->child->attendances->count() > 0)
                {{-- Desktop Table --}}
                <div class="d-none d-md-block">
                    <div class="ent-scroll-x">
                        <table class="ent-table">
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
                                    <td style="font-weight:600;">{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</td>
                                    <td class="ent-text-muted">{{ $attendance->subject->name ?? $attendance->subject_code }}</td>
                                    <td>
                                        @if($attendance->excused)
                                            <span class="ent-badge ent-badge-success"><i class="bi bi-check2-circle" style="font-size:0.6rem;"></i> Excused</span>
                                        @elseif($attendance->status === 'Present')
                                            <span class="ent-badge ent-badge-success"><i class="bi bi-check-circle" style="font-size:0.6rem;"></i> Present</span>
                                        @elseif($attendance->status === 'Late')
                                            <span class="ent-badge ent-badge-warning"><i class="bi bi-clock-history" style="font-size:0.6rem;"></i> Late</span>
                                        @else
                                            <span class="ent-badge ent-badge-danger"><i class="bi bi-x-circle" style="font-size:0.6rem;"></i> Absent</span>
                                        @endif
                                    </td>
                                    <td class="ent-text-muted">
                                        @if($attendance->time_in)
                                            <i class="bi bi-stopwatch" style="font-size:0.7rem;margin-right:2px;"></i>{{ \Carbon\Carbon::parse($attendance->time_in)->format('h:i A') }}
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->status === 'Absent' && !$attendance->excused)
                                            <a href="{{ route('parent.child.excuse', [$data->child, $attendance]) }}" class="ent-btn ent-btn-xs ent-btn-secondary" style="color:var(--ent-gold);">
                                                <i class="bi bi-pencil-square"></i> Excuse
                                            </a>
                                        @else
                                            <span class="ent-text-muted" style="font-size:0.78rem;">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Cards --}}
                <div class="d-md-none" style="padding:12px;">
                    @foreach($data->child->attendances as $attendance)
                    <div class="mobile-att-card-ent" style="margin-bottom:10px;">
                        <div class="ent-flex-between" style="margin-bottom:8px;">
                            <span style="font-weight:700;color:var(--ent-text);font-size:0.85rem;">{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</span>
                            @if($attendance->excused)
                                <span class="ent-badge ent-badge-success">Excused</span>
                            @elseif($attendance->status === 'Present')
                                <span class="ent-badge ent-badge-success">Present</span>
                            @elseif($attendance->status === 'Late')
                                <span class="ent-badge ent-badge-warning">Late</span>
                            @else
                                <span class="ent-badge ent-badge-danger">Absent</span>
                            @endif
                        </div>
                        <div style="color:var(--ent-text-muted);font-size:0.8rem;margin-bottom:8px;">
                            <i class="bi bi-book" style="margin-right:4px;"></i> {{ $attendance->subject->name ?? $attendance->subject_code }}
                        </div>
                        <div class="ent-flex-between" style="padding-top:8px;border-top:1px solid rgba(255,255,255,0.04);">
                            <div style="color:var(--ent-text-secondary);font-size:0.8rem;display:flex;align-items:center;gap:4px;">
                                <i class="bi bi-stopwatch" style="color:var(--ent-text-muted);"></i>
                                @if($attendance->time_in)
                                    {{ \Carbon\Carbon::parse($attendance->time_in)->format('h:i A') }}
                                @else
                                    <span class="ent-text-muted">--</span>
                                @endif
                            </div>
                            @if($attendance->status === 'Absent' && !$attendance->excused)
                                <a href="{{ route('parent.child.excuse', [$data->child, $attendance]) }}" class="ent-btn ent-btn-xs ent-btn-ghost" style="color:var(--ent-gold);">
                                    <i class="bi bi-pencil-square"></i> Excuse
                                </a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="ent-empty" style="padding:40px 20px;">
                    <div class="ent-empty-icon" style="width:56px;height:56px;font-size:1.5rem;">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <div class="ent-empty-title">No Records</div>
                    <div class="ent-empty-text">No attendance records yet for this student.</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@empty
{{-- No Children Linked --}}
<div class="ent-section ent-fade-up" style="border-radius:var(--ent-radius-xl);">
    <div class="ent-empty" style="min-height:400px;padding:60px 24px;">
        <div class="ent-empty-icon" style="width:80px;height:80px;font-size:2.5rem;">
            <i class="bi bi-people"></i>
        </div>
        <div class="ent-empty-title" style="font-size:1.2rem;">No Children Linked</div>
        <div class="ent-empty-text" style="max-width:300px;margin-bottom:24px;">You currently don't have any children linked to your account. Link your child's account by entering their Student ID.</div>
        <a href="{{ route('parent.link.form') }}" class="ent-btn ent-btn-primary">
            <i class="bi bi-link-45deg"></i> Link a Child
        </a>
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
    
    const presentGradient = ctx.createLinearGradient(0, 0, 0, 180);
    presentGradient.addColorStop(0, 'rgba(102,187,106,0.25)');
    presentGradient.addColorStop(1, 'rgba(102,187,106,0.01)');
    
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
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: { 
                        color: '#b39b82', 
                        font: { size: 11, family: 'Inter', weight: '500' }, 
                        boxWidth: 10, boxHeight: 10,
                        usePointStyle: true, padding: 15
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(26, 29, 36, 0.95)',
                    titleColor: '#cfa46f',
                    bodyColor: '#f3e7cd',
                    borderColor: 'rgba(207,164,111,0.2)',
                    borderWidth: 1,
                    padding: 12, boxPadding: 6,
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
