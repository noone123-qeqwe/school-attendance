@extends('layouts.app')
@section('page-title', 'Parent Dashboard')

@section('content')
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(207,164,111,0.2); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(207,164,111,0.4); }

    .parent-header-card {
        background: linear-gradient(135deg, rgba(32,20,15,0.9) 0%, rgba(20,10,5,0.95) 100%);
        border: 1px solid rgba(207,164,111,0.25);
        border-radius: 24px; padding: 20px 24px; position: relative; overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .parent-header-card::before {
        content: ''; position: absolute; top: 0; left: 0; width: 6px; height: 100%;
        background: linear-gradient(180deg, var(--gold) 0%, #8f6e4a 100%);
    }
    .parent-header-title { color: #f3e7cd; font-weight: 800; margin: 0 0 6px 0; font-size: clamp(1.5rem, 3vw, 2rem); }
    .parent-header-sub { color: #b39b82; font-size: 0.95rem; }
    
    .child-tab-container {
        display: flex; gap: 8px; flex-wrap: nowrap; overflow-x: auto;
        scrollbar-width: none; -ms-overflow-style: none; padding-bottom: 8px;
        -webkit-overflow-scrolling: touch; scroll-snap-type: x mandatory;
    }
    .child-tab-container::-webkit-scrollbar { display: none; }
    
    .child-tab-btn {
        scroll-snap-align: start; white-space: nowrap; border-radius: 99px;
        border: 1px solid rgba(207,164,111,0.15); background: rgba(207,164,111,0.02);
        color: #b39b82; padding: 8px 20px; font-weight: 600;
        display: flex; align-items: center; gap: 8px; backdrop-filter: blur(8px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;
    }
    .child-tab-btn:hover:not(.active) { background: rgba(207,164,111,0.06); transform: translateY(-1px); }
    .child-tab-btn.active {
        border-color: rgba(207,164,111,0.4); background: rgba(207,164,111,0.1);
        color: #f3e7cd; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transform: scale(1.02);
    }
    .child-avatar-mini {
        width: 24px; height: 24px; border-radius: 50%; background: rgba(207,164,111,0.2);
        display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #fff;
    }
    .child-rate-badge {
        font-size: 0.7rem; background: rgba(0,0,0,0.4); padding: 2px 8px; border-radius: 99px; border: 1px solid rgba(255,255,255,0.05);
    }

    .parent-empty-state {
        padding: 60px 20px; background: linear-gradient(145deg, rgba(32,20,15,0.8) 0%, rgba(20,10,5,0.9) 100%);
        border: 1px solid rgba(207,164,111,0.2); border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        backdrop-filter: blur(10px); text-align: center;
    }
    .parent-empty-icon {
        width: 80px; height: 80px; margin: 0 auto 20px; background: rgba(207,164,111,0.1); border-radius: 50%;
        display: flex; align-items: center; justify-content: center; border: 1px solid rgba(207,164,111,0.2);
    }
    
    @media (max-width: 768px) {
        .parent-header-card {
            background: rgba(26, 17, 16, 0.6);
            border: 1px solid rgba(207, 164, 111, 0.1);
            box-shadow: none;
            padding: 16px;
            border-radius: 16px;
        }
        .parent-header-card::before { width: 4px; }
        .parent-header-title { font-size: 1.25rem; }
        
        .ent-grid-5 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .ent-grid-5 > div:first-child {
            grid-column: 1 / -1;
        }
        
        .mobile-attendance-card {
            background: rgba(20,10,5,0.4);
            border: 1px solid rgba(207,164,111,0.15);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
    }
</style>

<!-- Desktop Header -->
<div class="mb-4 parent-header-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
        <div class="d-flex align-items-center gap-3 gap-md-4">
            <div class="d-none d-md-block" style="font-size: 3rem;">👨‍👩‍👧‍👦</div>
            <div>
                <h1 class="parent-header-title">My Children</h1>
                <div class="parent-header-sub">
                    Monitor attendance, performance & warnings
                </div>
            </div>
        </div>
        <div class="w-100 d-md-block" style="max-width: 280px; flex-grow: 1;">
            <a href="{{ route('parent.link.form') }}" class="ent-btn w-100" style="background: rgba(207,164,111,0.1); border: 1px solid rgba(207,164,111,0.3); color: var(--gold); border-radius: 12px;">
                <i class="bi bi-link-45deg"></i> Link Another Child
            </a>
        </div>
    </div>
</div>


@if(count($childrenData) > 1)
<!-- Child Dropdown Selector -->
<div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3" style="background: linear-gradient(135deg, rgba(32,20,15,0.7) 0%, rgba(20,10,5,0.85) 100%); border: 1px solid rgba(207,164,111,0.25); border-radius: 18px; padding: 14px 22px; box-shadow: 0 10px 25px rgba(0,0,0,0.3);">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-people-fill" style="color: var(--gold); font-size: 1.15rem;"></i>
        <label for="dashboardChildSelect" style="font-weight: 700; color: #f3e7cd; margin: 0; font-size: 0.95rem; white-space: nowrap;">
            Select Student:
        </label>
    </div>
    <div style="min-width: 260px; flex: 1; max-width: 480px;">
        <select id="dashboardChildSelect" class="form-select" onchange="switchDashboardChild(this.value)"
            style="background-color: #140d07; border: 1px solid rgba(207,164,111,0.35); color: #f3e7cd; font-weight: 600; font-size: 0.9rem; border-radius: 12px; padding: 8px 36px 8px 14px; cursor: pointer; outline: none; box-shadow: 0 4px 12px rgba(0,0,0,0.2); width: 100%;">
            @foreach($childrenData as $index => $data)
                <option value="{{ $data->child->id }}" {{ $index === 0 ? 'selected' : '' }} style="background: #140d07; color: #f3e7cd;">
                    {{ $data->child->name }} — {{ $data->child->student_number ?? 'Student' }}
                </option>
            @endforeach
        </select>
    </div>
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
    <div class="ent-grid ent-grid-5 mb-4" id="realChildStats_{{ $data->child->id }}" style="display:none;">
        <x-card type="kpi" accent="{{ $data->rate >= 90 ? 'success' : ($data->rate >= 75 ? 'warning' : 'danger') }}" label="Rate" value="{{ $data->rate }}%" />
        <x-card type="kpi" accent="success" label="Present" value="{{ $data->present }}" />
        <x-card type="kpi" accent="warning" label="Late" value="{{ $data->late }}" />
        <x-card type="kpi" accent="danger" label="Absent" value="{{ $data->absent }}" />
        <x-card type="kpi" accent="info" label="Excused" value="{{ $data->excused }}" />
    </div>

    <!-- Warnings -->
    <x-card type="section" class="mb-4" icon="bi bi-exclamation-triangle" title="Warnings">
        <div style="overflow-y: auto; max-height: 250px;" class="custom-scrollbar pe-2">
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
    </x-card>

    <!-- Recent Attendance -->
    <x-card type="section" icon="bi bi-clock-history" title="Recent Attendance">
        @if($data->child->attendances->count() > 0)
            <!-- Desktop Table -->
            <div class="d-none d-md-block">
                <x-data-table :headers="['Date', 'Subject', 'Status', 'Time In', 'Action']">
                    @foreach($data->child->attendances as $attendance)
                    <tr>
                        <td style="font-weight: 600;">{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</td>
                        <td style="color: #b39b82;">{{ $attendance->subject->name ?? $attendance->subject_code }}</td>
                        <td>
                            @php
                                $st = strtolower($attendance->status ?? 'absent');
                            @endphp
                            @if($attendance->excused)
                                <x-badge type="excused">Excused</x-badge>
                            @elseif($st === 'present')
                                <x-badge type="present">Present</x-badge>
                            @elseif($st === 'late')
                                <x-badge type="late">Late</x-badge>
                            @else
                                <x-badge type="absent">Absent</x-badge>
                            @endif
                        </td>
                        <td style="color: #b39b82;">
                            @if($attendance->time_in)
                                <i class="bi bi-stopwatch me-1" style="font-size: 0.8rem;"></i>{{ \Carbon\Carbon::parse($attendance->time_in)->format('h:i A') }}
                            @else
                                --
                            @endif
                        </td>
                        <td>
                            @if(strtolower($attendance->status ?? '') === 'absent' && !$attendance->excused)
                                <a href="{{ route('parent.child.excuse', [$data->child, $attendance]) }}" class="ent-btn ent-btn-sm ent-btn-ghost">
                                    <i class="bi bi-pencil-square"></i> Excuse
                                </a>
                            @else
                                <span style="color: rgba(179,155,130,0.5); font-size: 0.75rem;">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>
            </div>
            
            <!-- Mobile Cards -->
            <div class="d-block d-md-none">
                @foreach($data->child->attendances as $attendance)
                <div class="mobile-attendance-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div style="color: #b39b82; font-size: 0.75rem; font-weight: 600; margin-bottom: 2px;">
                                {{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}
                            </div>
                            <div style="font-weight: 700; color: #f3e7cd; font-size: 0.95rem;">
                                {{ $attendance->subject->name ?? $attendance->subject_code }}
                            </div>
                        </div>
                        <div>
                            @php
                                $st = strtolower($attendance->status ?? 'absent');
                            @endphp
                            @if($attendance->excused)
                                <x-badge type="excused">Excused</x-badge>
                            @elseif($st === 'present')
                                <x-badge type="present">Present</x-badge>
                            @elseif($st === 'late')
                                <x-badge type="late">Late</x-badge>
                            @else
                                <x-badge type="absent">Absent</x-badge>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center" style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px;">
                        <div style="color: rgba(179,155,130,0.8); font-size: 0.8rem;">
                            @if($attendance->time_in)
                                <i class="bi bi-stopwatch me-1"></i>{{ \Carbon\Carbon::parse($attendance->time_in)->format('h:i A') }}
                            @else
                                <i class="bi bi-dash"></i> No time
                            @endif
                        </div>
                        <div>
                            @if(strtolower($attendance->status ?? '') === 'absent' && !$attendance->excused)
                                <a href="{{ route('parent.child.excuse', [$data->child, $attendance]) }}" style="color: var(--gold); font-size: 0.8rem; font-weight: 600; text-decoration: none;">
                                    <i class="bi bi-pencil-square"></i> Excuse
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <x-empty-state icon="bi bi-calendar-x" title="No Records" description="No attendance records yet for this student." />
        @endif
    </x-card>
</x-card>
</div>
@empty
<!-- No Children Linked -->
<div class="parent-empty-state">
    <div class="parent-empty-icon">
        <i class="bi bi-link-45deg" style="font-size: 2.5rem; color: var(--gold); opacity: 0.9;"></i>
    </div>
    <h3 style="color: #f3e7cd; margin-top: 10px; font-weight: 700; font-size: 1.5rem;">No Children Linked</h3>
    <p style="color: #b39b82; max-width: 400px; margin: 0 auto 24px; font-size: 0.95rem; line-height: 1.5;">You currently don't have any children linked to your account. Link your child's account to start monitoring their attendance and performance.</p>
    <a href="{{ route('parent.link.form') }}" class="ent-btn" style="background: var(--gold); border: none; font-weight: 600; padding: 12px 30px; border-radius: 12px; color: #1a1d24; box-shadow: 0 4px 15px rgba(207,164,111,0.3); transition: all 0.3s ease;">
        <i class="bi bi-link-45deg"></i> Link a Child
    </a>
</div>
@endforelse

<script>
// ── Skeleton → Content Reveal ──
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[id^="skelChildStats_"]').forEach(function(skel) {
        var id = skel.id.replace('skelChildStats_', '');
        var real = document.getElementById('realChildStats_' + id);
        if (real) { skel.style.display = 'none'; real.style.display = ''; }
    });
});
</script>

<script>
// ── Child Dropdown Selector Logic ──
function switchDashboardChild(childId) {
    document.querySelectorAll('.child-view-content').forEach(function(v) {
        v.style.display = (v.id === 'childView_' + childId) ? 'block' : 'none';
    });
    localStorage.setItem('selectedChildId', childId);
    var select = document.getElementById('dashboardChildSelect');
    if (select && select.value !== childId.toString()) {
        select.value = childId;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var savedChildId = localStorage.getItem('selectedChildId');
    if (savedChildId && document.getElementById('childView_' + savedChildId)) {
        switchDashboardChild(savedChildId);
    }
});
</script>

@endsection
