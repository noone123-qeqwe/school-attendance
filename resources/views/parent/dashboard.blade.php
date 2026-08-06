@extends('parent.layout')
@section('page-title', 'Parent Dashboard')

@section('content')

<!-- Desktop Header -->
<div class="mb-4" style="background: linear-gradient(135deg, rgba(32,20,15,0.9) 0%, rgba(20,10,5,0.95) 100%); border: 1px solid rgba(207,164,111,0.25); border-radius: 24px; padding: 30px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
    <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: linear-gradient(180deg, var(--gold) 0%, #8f6e4a 100%);"></div>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
        <div class="d-flex align-items-center gap-4">
            <div style="font-size: 3rem;">👨‍👩‍👧‍👦</div>
            <div>
                <h1 style="color: #f3e7cd; font-weight: 800; margin: 0 0 6px 0; font-size: 2rem;">My Children</h1>
                <div style="color: #b39b82; font-size: 0.95rem;">
                    Monitor attendance, performance & warnings
                </div>
            </div>
        </div>
        <div>
            <a href="{{ route('parent.link.form') }}" class="btn btn-primary" style="background: var(--gold); border: none; font-weight: 600;">
                <i class="bi bi-link-45deg"></i> Link Another Child
            </a>
        </div>
    </div>
</div>


@if(count($childrenData) > 0)
<!-- Child Tab Selector -->
<div class="d-flex gap-2 flex-nowrap overflow-auto mb-4 pb-2" style="scrollbar-width: none; -ms-overflow-style: none;" id="childTabsContainer">
    @foreach($childrenData as $index => $data)
        @php
            $rateColor = $data->rate >= 90 ? '#4ade80' : ($data->rate >= 75 ? '#fbbf24' : '#f87171');
        @endphp
        <button type="button" 
                class="btn child-tab-btn {{ $index === 0 ? 'active' : '' }}" 
                data-child-id="{{ $data->child->id }}"
                style="white-space: nowrap; border-radius: 99px; border: 1px solid rgba(255,255,255,{{ $index === 0 ? '0.15' : '0.06' }}); background: rgba(255,255,255,{{ $index === 0 ? '0.05' : '0.02' }}); color: {{ $index === 0 ? '#f3e7cd' : '#b39b82' }}; padding: 8px 20px; font-weight: 600; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(8px); transition: all 0.2s ease;">
            <div style="width: 24px; height: 24px; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #fff;">
                {{ substr($data->child->name, 0, 2) }}
            </div>
            {{ explode(' ', $data->child->name)[0] }}
            <span style="font-size: 0.7rem; background: rgba(0,0,0,0.3); padding: 2px 8px; border-radius: 99px; color: {{ $rateColor }}; border: 1px solid rgba(255,255,255,0.05);">{{ $data->rate }}%</span>
        </button>
    @endforeach
</div>
@endif

@forelse($childrenData as $index => $data)
<div class="child-view-content" id="childView_{{ $data->child->id }}" style="display: {{ $index === 0 ? 'block' : 'none' }}; animation: cmdFadeIn 0.3s ease;">
<x-card title="{{ $data->child->name }}" icon="bi bi-person-fill" class="mb-5">
    <x-slot name="headerActions">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('parent.child', $data->child) }}" class="btn btn-outline btn-sm">
                <i class="bi bi-list-ul"></i> Records
            </a>
            <a href="{{ route('parent.child.warnings', $data->child) }}" class="btn btn-outline btn-sm position-relative">
                <i class="bi bi-exclamation-triangle"></i> Warnings
                @if($data->warnings->count() > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ $data->warnings->count() }}
                    </span>
                @endif
            </a>
            <a href="{{ route('parent.child.report', $data->child) }}" class="btn btn-primary btn-sm" style="background: var(--gold); border: none;">
                <i class="bi bi-file-earmark-pdf"></i> Report
            </a>
        </div>
    </x-slot>

    <!-- Child Info -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <img src="{{ $data->child->profile_image ? (str_starts_with($data->child->profile_image, 'http') ? $data->child->profile_image : asset('storage/'.$data->child->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($data->child->name).'&background=800000&color=fff&size=52' }}"
             alt="{{ $data->child->name }}"
             style="width: 52px; height: 52px; border-radius: 50%; border: 2px solid var(--gold);">
        <div>
            <div style="font-weight: 700; color: #f3e7cd;">{{ $data->child->student_number }}</div>
            <div style="font-size: 0.85rem; color: #b39b82;">
                {{ $data->child->course }} — Year {{ $data->child->year_level }}
            </div>
        </div>
    </div>

    <!-- Skeleton Stats -->
    <div class="row g-3 mb-4" id="skelChildStats_{{ $data->child->id }}">
        <div class="col-md-2 col-4"><x-skeleton type="stat" /></div>
        <div class="col-md-2 col-4"><x-skeleton type="stat" /></div>
        <div class="col-md-2 col-4"><x-skeleton type="stat" /></div>
        <div class="col-md-2 col-4"><x-skeleton type="stat" /></div>
        <div class="col-md-2 col-4"><x-skeleton type="stat" /></div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4" id="realChildStats_{{ $data->child->id }}" style="display:none;">
        <div class="col-md-2 col-4">
            <div class="ent-kpi-card" style="padding: 16px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06);">
                <div class="ent-kpi-value" style="font-size: 1.5rem; color: {{ $data->rate >= 90 ? '#4ade80' : ($data->rate >= 75 ? '#fbbf24' : '#f87171') }}; text-align: center;">{{ $data->rate }}%</div>
                <div class="ent-kpi-label" style="text-align: center; margin-top: 4px;">Rate</div>
            </div>
        </div>
        <div class="col-md-2 col-4">
            <div class="ent-kpi-card" style="padding: 16px; background: rgba(34,197,94,0.05); border: 1px solid rgba(34,197,94,0.15);">
                <div class="ent-kpi-value" style="font-size: 1.5rem; color: #4ade80; text-align: center;">{{ $data->present }}</div>
                <div class="ent-kpi-label" style="text-align: center; margin-top: 4px; color: #4ade80;">Present</div>
            </div>
        </div>
        <div class="col-md-2 col-4">
            <div class="ent-kpi-card" style="padding: 16px; background: rgba(245,158,11,0.05); border: 1px solid rgba(245,158,11,0.15);">
                <div class="ent-kpi-value" style="font-size: 1.5rem; color: #fbbf24; text-align: center;">{{ $data->late }}</div>
                <div class="ent-kpi-label" style="text-align: center; margin-top: 4px; color: #fbbf24;">Late</div>
            </div>
        </div>
        <div class="col-md-2 col-4">
            <div class="ent-kpi-card" style="padding: 16px; background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.15);">
                <div class="ent-kpi-value" style="font-size: 1.5rem; color: #f87171; text-align: center;">{{ $data->absent }}</div>
                <div class="ent-kpi-label" style="text-align: center; margin-top: 4px; color: #f87171;">Absent</div>
            </div>
        </div>
        <div class="col-md-2 col-4">
            <div class="ent-kpi-card" style="padding: 16px; background: rgba(96,165,250,0.05); border: 1px solid rgba(96,165,250,0.15);">
                <div class="ent-kpi-value" style="font-size: 1.5rem; color: #60a5fa; text-align: center;">{{ $data->excused }}</div>
                <div class="ent-kpi-label" style="text-align: center; margin-top: 4px; color: #60a5fa;">Excused</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Chart -->
        <div class="col-lg-8">
            <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 20px; height: 100%;">
                <h6 style="color: var(--gold); font-size: 0.85rem; text-transform: uppercase; margin-bottom: 16px;"><i class="bi bi-graph-up me-2"></i> 30-Day Trend</h6>
                <div style="height: 180px;">
                    <canvas id="trendChart_{{ $data->child->id }}"></canvas>
                </div>
            </div>
        </div>
        <!-- Warnings -->
        <div class="col-lg-4">
            <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 20px; height: 100%; display: flex; flex-direction: column;">
                <h6 style="color: #f87171; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 16px;"><i class="bi bi-exclamation-triangle me-2"></i> Warnings</h6>
                <div style="flex: 1; overflow-y: auto;">
                    @if($data->warnings->count() > 0)
                        @foreach($data->warnings as $warning)
                        <div style="padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <div style="font-size: 0.82rem; font-weight: 700; color: #f87171; margin-bottom: 4px;">
                                {{ $warning->subject->name ?? $warning->subject_code }}
                            </div>
                            <div style="font-size: 0.78rem; color: #b39b82; line-height: 1.5;">
                                {{ Str::limit($warning->message, 80) }}
                            </div>
                            <div style="font-size: 0.68rem; color: rgba(179,155,130,0.6); margin-top: 4px;">
                                <i class="bi bi-clock me-1"></i>{{ $warning->created_at->diffForHumans() }}
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center" style="padding: 20px;">
                            <i class="bi bi-shield-check" style="font-size: 2rem; color: #4ade80; opacity: 0.5;"></i>
                            <div style="font-size: 0.85rem; color: #4ade80; margin-top: 8px;">All Clear!</div>
                            <div style="font-size: 0.78rem; color: #b39b82;">No warnings — great job!</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div>
        <h6 style="color: var(--gold); font-size: 0.85rem; text-transform: uppercase; margin-bottom: 16px;"><i class="bi bi-clock-history me-2"></i> Recent Attendance</h6>
        @if($data->child->attendances->count() > 0)
            <x-data-table :headers="['Date', 'Subject', 'Status', 'Time In', 'Action']">
                @foreach($data->child->attendances as $attendance)
                <tr>
                    <td data-label="Date" style="font-weight: 600;">{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</td>
                    <td data-label="Subject" style="color: #b39b82;">{{ $attendance->subject->name ?? $attendance->subject_code }}</td>
                    <td data-label="Status">
                        @if($attendance->excused)
                            <x-badge type="excused">Excused</x-badge>
                        @elseif($attendance->status === 'Present')
                            <x-badge type="present">Present</x-badge>
                        @elseif($attendance->status === 'Late')
                            <x-badge type="late">Late</x-badge>
                        @else
                            <x-badge type="absent">Absent</x-badge>
                        @endif
                    </td>
                    <td data-label="Time In" style="color: #b39b82;">
                        @if($attendance->time_in)
                            <i class="bi bi-stopwatch me-1" style="font-size: 0.8rem;"></i>{{ \Carbon\Carbon::parse($attendance->time_in)->format('h:i A') }}
                        @else
                            --
                        @endif
                    </td>
                    <td data-label="Action">
                        @if($attendance->status === 'Absent' && !$attendance->excused)
                            <a href="{{ route('parent.child.excuse', [$data->child, $attendance]) }}" class="btn btn-sm btn-outline" style="color: var(--gold); border-color: rgba(207,164,111,0.3); padding: 4px 10px; font-size: 0.75rem;">
                                <i class="bi bi-pencil-square"></i> Excuse
                            </a>
                        @else
                            <span style="color: rgba(179,155,130,0.5); font-size: 0.75rem;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </x-data-table>
        @else
            <div class="empty-state text-center" style="padding: 40px;">
                <i class="bi bi-calendar-x" style="font-size: 2.5rem; color: #b39b82; opacity: 0.5;"></i>
                <div style="font-size: 1.1rem; color: #f3e7cd; margin-top: 16px;">No Records</div>
                <div style="font-size: 0.85rem; color: #b39b82;">No attendance records yet for this student.</div>
            </div>
        @endif
    </div>
</x-card>
</div>
@empty
<!-- No Children Linked -->
<div class="empty-state text-center" style="padding: 80px 20px; background: rgba(0,0,0,0.2); border: 1px dashed rgba(207,164,111,0.3); border-radius: 24px;">
    <i class="bi bi-people" style="font-size: 3rem; color: var(--gold); opacity: 0.8;"></i>
    <h3 style="color: #f3e7cd; margin-top: 24px; font-weight: 700;">No Children Linked</h3>
    <p style="color: #b39b82; max-width: 400px; margin: 0 auto 24px;">You currently don't have any children linked to your account. Link your child's account by entering their Student ID.</p>
    <a href="{{ route('parent.link.form') }}" class="btn btn-primary" style="background: var(--gold); border: none; font-weight: 600; padding: 12px 24px;">
        <i class="bi bi-link-45deg"></i> Link a Child
    </a>
</div>
@endforelse

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Skeleton → Content Reveal ──
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[id^="skelChildStats_"]').forEach(function(skel) {
        var id = skel.id.replace('skelChildStats_', '');
        var real = document.getElementById('realChildStats_' + id);
        if (real) { skel.style.display = 'none'; real.style.display = ''; }
    });
</script>

<script>
// ── Child Tab Selector Logic ──
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.child-tab-btn');
    const views = document.querySelectorAll('.child-view-content');

    // Load saved tab from localStorage if exists
    const savedChildId = localStorage.getItem('selectedChildId');
    if (savedChildId) {
        const savedTab = document.querySelector('.child-tab-btn[data-child-id="' + savedChildId + '"]');
        if (savedTab) {
            activateTab(savedTab, savedChildId);
        }
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const childId = this.getAttribute('data-child-id');
            activateTab(this, childId);
            localStorage.setItem('selectedChildId', childId);
            
            // Scroll to center of tab container on mobile
            this.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        });
    });

    function activateTab(activeTab, childId) {
        // Reset all tabs
        tabs.forEach(t => {
            t.classList.remove('active');
            t.style.border = '1px solid rgba(207,164,111,0.15)';
            t.style.background = 'rgba(207,164,111,0.02)';
            t.style.color = '#b39b82';
        });

        // Set active tab
        activeTab.classList.add('active');
        activeTab.style.border = '1px solid rgba(207,164,111,0.4)';
        activeTab.style.background = 'rgba(207,164,111,0.1)';
        activeTab.style.color = '#f3e7cd';

        // Show/hide views
        views.forEach(v => {
            if (v.id === 'childView_' + childId) {
                v.style.display = 'block';
            } else {
                v.style.display = 'none';
            }
        });
    }
});
</script>
@foreach($childrenData as $data)
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('trendChart_{{ $data->child->id }}').getContext('2d');
    
    const presentGradient = ctx.createLinearGradient(0, 0, 0, 180);
    presentGradient.addColorStop(0, 'rgba(74,222,128,0.25)');
    presentGradient.addColorStop(1, 'rgba(74,222,128,0.01)');
    
    const absentGradient = ctx.createLinearGradient(0, 0, 0, 180);
    absentGradient.addColorStop(0, 'rgba(248,113,113,0.25)');
    absentGradient.addColorStop(1, 'rgba(248,113,113,0.01)');

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
