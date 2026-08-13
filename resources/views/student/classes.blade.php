@extends('layouts.app')

@section('content')
<style>
    /* ── MOBILE RESPONSIVENESS ── */
    @media (max-width: 768px) {
        .container-fluid { 
            padding-left: 15px !important; 
            padding-right: 15px !important; 
        }
    }   
    /* Info pills */
    .info-pills { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
    .info-pill {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 7px 14px; border-radius: 99px;
        font-size: 0.8rem; font-weight: 600;
        border: 1.5px solid rgba(255,255,255,0.14); background: rgba(255,255,255,0.06);
        color: #f8e7d3;
    }
    .info-pill i { font-size: 0.85rem; }
    .info-pill.maroon { background: #800000; border-color: #800000; color: white; }

    /* Table card */
    .table-card {
        width: 100%;
        max-width: 100%;
        background: rgba(255,255,255,0.05); border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: 0 16px 44px rgba(0,0,0,0.22);
        overflow: hidden;
    }
    .table-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        display: flex; align-items: center; gap: 10px;
    }   
    .table-card-title {
        font-size: 0.95rem; font-weight: 700; color: #f8e7d3;
        display: flex; align-items: center; gap: 10px;
    }
    .table-title-icon {
        width: 34px; height: 34px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center; font-size: 0.95rem;
        background: rgba(255,255,255,0.08); color: #d8b35c;
    }
    .subject-count {
        margin-left: auto;
        font-size: 0.75rem; font-weight: 700; color: #f5e7d3;
        background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);
        padding: 4px 12px; border-radius: 99px;
    }

    /* Table */
    .cls-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: auto; }
    .cls-table thead th {
        font-size: 0.7rem; font-weight: 700; color: rgba(248,231,211,0.8);
        text-transform: uppercase; letter-spacing: 0.5px;
        padding: 12px 20px; background: rgba(255,255,255,0.06);
        border-bottom: 1px solid rgba(255,255,255,0.08);
        white-space: nowrap;
    }
    .cls-table tbody tr { transition: background 0.15s; }
    .cls-table tbody tr:hover td { background: rgba(255,255,255,0.08); }
    .cls-table tbody td {
        padding: 14px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        font-size: 0.875rem; color: rgba(248,231,211,0.88);
        vertical-align: middle;
    }
    .cls-table tbody tr:last-child td { border-bottom: none; }

    /* Subject name */
    .subject-name-cell { font-weight: 600; color: #f8e7d3; }
    .subject-code-badge {
        display: inline-block;
        background: rgba(255,255,255,0.08); color: #f8e7d3;
        font-size: 0.75rem; font-weight: 700;
        padding: 3px 10px; border-radius: 6px;
        border: 1px solid rgba(255,255,255,0.12);
        font-family: monospace;
    }

    /* Time */
    .time-cell { white-space: nowrap; font-size: 0.82rem; }
    .time-cell .time-range { font-weight: 600; color: #f8e7d3; }
    .time-cell .time-sep { color: rgba(248,231,211,0.7); margin: 0 3px; }

    /* Units badge */
    .units-badge {
        display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; border-radius: 8px;
        background: rgba(255,255,255,0.08); color: #f8e7d3;
        font-size: 0.8rem; font-weight: 700;
        border: 1px solid rgba(255,255,255,0.12);
    }

    /* Empty */
    .empty-state { text-align: center; padding: 60px 20px; color: rgba(248,231,211,0.7); }
    .empty-state i { font-size: 3rem; opacity: 0.25; display: block; margin-bottom: 12px; }

    /* ── ENHANCED INFO SUMMARY ── */
    .summary-grid {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .summary-card {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: 0 16px 36px rgba(0,0,0,0.24);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 44px rgba(0,0,0,0.28);
    }
    .summary-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        margin-bottom: 12px;
    }
    .summary-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #f8e7d3;
        margin-bottom: 4px;
    }
    .summary-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: rgba(248,231,211,0.75);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* ── MOBILE RESPONSIVENESS ── */
    @media (max-width: 768px) {
        .container-fluid { 
            padding-left: 15px !important; 
            padding-right: 15px !important; 
        }

        .page-header-title { font-size: 1.2rem; }
        .page-header-sub { font-size: 0.8rem; }

        .info-pills {
            display: none;
        }

        .summary-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .summary-card {
            padding: 14px 16px;
        }
        .summary-icon {
            width: 32px;
            height: 32px;
            font-size: 1rem;
        }
        .summary-value { font-size: 1.3rem; }
        .summary-label { font-size: 0.75rem; }
    }
</style>

<div class="container-fluid p-4" style="max-width: 1200px;">

    <div style="margin-bottom:20px;">
        <div style="font-size:1.4rem;font-weight:800;color:#f8e7d3;letter-spacing:-.3px;">My Class Schedule</div>
        <div style="font-size:.875rem;color:rgba(248,231,211,0.72);margin-top:2px;">Your enrolled subjects and unified schedule for this semester</div>
    </div>

    <!-- Info pills -->
    <div class="info-pills">
        <div class="info-pill maroon">
            <i class="bi bi-person-fill"></i>
            {{ auth()->user()->name }}
        </div>
        <div class="info-pill">
            <i class="bi bi-layers-fill" style="color:#800000;"></i>
            Year {{ auth()->user()->year_level }}
        </div>
        <div class="info-pill">
            <i class="bi bi-calendar3" style="color:#800000;"></i>
            Semester {{ auth()->user()->semester }}
        </div>
        <div class="info-pill">
            <i class="bi bi-book-fill" style="color:#800000;"></i>
            {{ $subjects->count() }} {{ $subjects->count() === 1 ? 'Subject' : 'Subjects' }}
        </div>
    </div>

    <!-- Summary Grid -->
    <div class="summary-grid" style="margin-bottom:24px;">
        <div class="summary-card">
            <div class="summary-icon" style="background:#fff5f5;color:#800000;">
                <i class="bi bi-book-fill"></i>
            </div>
            <div class="summary-value">{{ $subjects->count() }}</div>
            <div class="summary-label">{{ $subjects->count() === 1 ? 'Subject' : 'Subjects' }} Enrolled</div>
        </div>
        <div class="summary-card">
            <div class="summary-icon" style="background:#f0f9ff;color:#0369a1;">
                <i class="bi bi-layers-fill"></i>
            </div>
            <div class="summary-value">Year {{ auth()->user()->year_level }}</div>
            <div class="summary-label">Year Level</div>
        </div>
        <div class="summary-card">
            <div class="summary-icon" style="background:#fefce8;color:#ca8a04;">
                <i class="bi bi-calendar3"></i>
            </div>
            <div class="summary-value">Semester {{ auth()->user()->semester }}</div>
            <div class="summary-label">Current Semester</div>
        </div>
        <div class="summary-card">
            <div class="summary-icon" style="background:#f0fdf4;color:#16a34a;">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div class="summary-value">{{ auth()->user()->course }}</div>
            <div class="summary-label">Course</div>
        </div>
    </div>

    @php
        $groupedSchedules = [];
        $dayMap = ['Monday'=>'M', 'Tuesday'=>'T', 'Wednesday'=>'W', 'Thursday'=>'TH', 'Friday'=>'F', 'Saturday'=>'S', 'Sunday'=>'U'];

        foreach($subjects as $subject) {
            $groups = [];
            foreach($subject->schedules as $sched) {
                $key = $sched->start_time . '-' . $sched->end_time . '-' . $sched->room;
                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'days' => [],
                        'start_time' => $sched->start_time,
                        'end_time' => $sched->end_time,
                        'room' => $sched->room
                    ];
                }
                $groups[$key]['days'][] = $sched->day;
            }
            
            if (count($groups) > 0) {
                foreach($groups as $group) {
                    $daysStr = '';
                    foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $d) {
                        if (in_array($d, $group['days'])) {
                            $daysStr .= $dayMap[$d];
                        }
                    }
                    $groupedSchedules[] = (object) [
                        'section' => $subject->section ?? '—',
                        'code' => $subject->code,
                        'name' => $subject->name,
                        'class_number' => $subject->id,
                        'units' => $subject->units ?? '—',
                        'start_time' => $group['start_time'],
                        'end_time' => $group['end_time'],
                        'days' => $daysStr,
                        'room' => $group['room'] ?? 'TBA',
                        'teacher' => $subject->instructorUser->name ?? $subject->instructor ?? 'TBA',
                    ];
                }
            } else {
                $groupedSchedules[] = (object) [
                    'section' => $subject->section ?? '—',
                    'code' => $subject->code,
                    'name' => $subject->name,
                    'class_number' => $subject->id,
                    'units' => $subject->units ?? '—',
                    'start_time' => null,
                    'end_time' => null,
                    'days' => 'TBA',
                    'room' => 'TBA',
                    'teacher' => $subject->instructorUser->name ?? $subject->instructor ?? 'TBA',
                ];
            }
        }
    @endphp

    <!-- Controls (Search & Filter) -->
    <div class="d-flex flex-column flex-md-row gap-3 mb-4 justify-content-between">
        <div class="input-group" style="max-width: 400px;">
            <span class="input-group-text bg-dark border-secondary text-light">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" id="scheduleSearch" class="form-control bg-dark border-secondary text-light" placeholder="Search by name, subject, section, instructor...">
        </div>
        
        <div class="d-flex gap-2">
            <select id="dayFilter" class="form-select bg-dark border-secondary text-light" style="width: auto;">
                <option value="">All Days</option>
                <option value="M">Monday</option>
                <option value="T">Tuesday</option>
                <option value="W">Wednesday</option>
                <option value="TH">Thursday</option>
                <option value="F">Friday</option>
                <option value="S">Saturday</option>
            </select>
        </div>
    </div>

    <!-- Table card -->
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">
                <div class="table-title-icon" style="background:#fff5f5;color:#800000;">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
                My Schedule & Classes
            </div>
            <span class="subject-count" id="itemCount">{{ count($groupedSchedules) }} items</span>
        </div>

        <div style="overflow-x:auto;">
            <table class="cls-table" id="scheduleTable">
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Subject Code</th>
                        <th>Class Number</th>
                        <th>Units</th>
                        <th>Time</th>
                        <th>Day</th>
                        <th>Room</th>
                        <th>Teacher's Name</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groupedSchedules as $sched)
                    <tr class="schedule-row" 
                        data-days="{{ $sched->days }}">
                        <td><strong>{{ $sched->section }}</strong></td>
                        <td>
                            <span class="subject-code-badge">{{ $sched->code }}</span>
                            <div style="font-size: 0.75rem; color: rgba(248,231,211,0.6); margin-top: 4px;">{{ $sched->name }}</div>
                        </td>
                        <td style="color:#f8e7d3; font-weight:600;">{{ $sched->class_number }}</td>
                        <td>
                            <div class="units-badge">{{ $sched->units }}</div>
                        </td>
                        <td class="time-cell">
                            @if($sched->start_time)
                                <span class="time-range">
                                    {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i a') }}
                                    <span class="time-sep">–</span>
                                    {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i a') }}
                                </span>
                            @else
                                <span style="color:#cbd5e1;">TBA</span>
                            @endif
                        </td>
                        <td><strong style="color: #d8b35c;">{{ $sched->days }}</strong></td>
                        <td style="font-family: monospace; color:#f8e7d3;">{{ $sched->room }}</td>
                        <td style="color:#f8e7d3;">{{ $sched->teacher }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="bi bi-journal-x"></i>
                                <p>No classes found.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('scheduleSearch');
    const dayFilter = document.getElementById('dayFilter');
    const rows = document.querySelectorAll('.schedule-row');
    const countDisplay = document.getElementById('itemCount');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedDay = dayFilter.value;
        let visibleCount = 0;

        rows.forEach(row => {
            const searchData = row.textContent.toLowerCase();
            const daysData = row.getAttribute('data-days');
            
            const matchesSearch = searchData.includes(searchTerm);
            const matchesDay = selectedDay === '' || daysData.includes(selectedDay);

            if (matchesSearch && matchesDay) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        countDisplay.textContent = visibleCount + (visibleCount === 1 ? ' item' : ' items');
    }

    searchInput.addEventListener('input', filterTable);
    dayFilter.addEventListener('change', filterTable);
});
</script>
@endsection