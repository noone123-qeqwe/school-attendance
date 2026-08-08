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
    .cls-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
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

    /* Day pills */
    .day-pills { display: flex; gap: 4px; flex-wrap: wrap; }
    .day-pill {
        width: 28px; height: 28px;
        border-radius: 7px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.68rem; font-weight: 800;
        text-transform: uppercase;
    }
    .day-pill.active { background: #800000; color: white; }
    .day-pill.inactive { background: rgba(255,255,255,0.08); color: rgba(248,231,211,0.75); }

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

    /* ── SUBJECT CARDS FOR MOBILE ── */
    .subject-cards {
        display: none;
    }
    .subject-card {
        width: 100%;
        max-width: 100%;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 16px;
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .subject-card:hover {
        box-shadow: 0 16px 38px rgba(0,0,0,0.24);
        transform: translateY(-2px);
    }
    .subject-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .subject-code-mobile {
        background: #800000;
        color: white;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 6px;
        font-family: monospace;
        display: inline-block;
    }
    .subject-units-mobile {
        background: #eff6ff;
        color: #2563eb;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 6px;
        border: 1px solid #bfdbfe;
        display: inline-block;
    }
    .subject-name-mobile {
        font-size: 1.1rem;
        font-weight: 700;
        color: #f8e7d3;
        margin-bottom: 8px;
        line-height: 1.3;
    }
    .subject-details {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .detail-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
    }
    .detail-icon {
        width: 16px;
        color: rgba(248,231,211,0.65);
        flex-shrink: 0;
    }
    .detail-label {
        font-weight: 600;
        color: rgba(248,231,211,0.8);
        min-width: 60px;
    }
    .detail-value {
        color: #f8e7d3;
        font-weight: 500;
    }
    .day-badges-mobile {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        margin-top: 4px;
    }
    .day-badge-mobile {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .day-badge-mobile.active {
        background: #800000;
        color: white;
    }
    .day-badge-mobile.inactive {
        background: rgba(255,255,255,0.08);
        color: rgba(248,231,211,0.75);
    }

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
            display: none; /* Hide old pills, show summary grid instead */
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

        .table-card {
            display: none; /* Hide table on mobile */
        }
        .subject-cards {
            display: block; /* Show cards on mobile */
        }

        .subject-card {
            padding: 16px;
            margin-bottom: 12px;
        }
        .subject-name-mobile { font-size: 1rem; }
        .detail-row { font-size: 0.8rem; }
        .detail-label { min-width: 50px; }
    }
</style>

<div class="container-fluid p-4" style="max-width: 1200px;">

    <div style="margin-bottom:20px;">
        <div style="font-size:1.4rem;font-weight:800;color:#f8e7d3;letter-spacing:-.3px;">My Class Schedule</div>
        <div style="font-size:.875rem;color:rgba(248,231,211,0.72);margin-top:2px;">Your enrolled subjects for this semester</div>
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

    <!-- Table card -->
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">
                <div class="table-title-icon" style="background:#fff5f5;color:#800000;">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
                Enrolled Subjects
            </div>
            <span class="subject-count">{{ $subjects->count() }} total</span>
        </div>

        <div style="overflow-x:auto;">
            <table class="cls-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Subject</th>
                        <th>Days</th>
                        <th>Time</th>
                        <th>Units</th>
                        <th>Instructor</th>
                        <th>Section</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $subject)
                    @php
                        $allDays    = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                        $dayShort   = ['Monday'=>'M','Tuesday'=>'T','Wednesday'=>'W','Thursday'=>'Th','Friday'=>'F','Saturday'=>'Sa'];
                        $activeDays = $subject->schedules->pluck('day')->toArray();
                        $firstSched = $subject->schedules->first();
                    @endphp
                    <tr>
                        <td>
                            <span class="subject-code-badge">{{ $subject->code }}</span>
                        </td>
                        <td class="subject-name-cell">{{ $subject->name }}</td>
                        <td>
                            @if($subject->schedules->isNotEmpty())
                            <div class="day-pills">
                                @foreach($allDays as $day)
                                    <div class="day-pill {{ in_array($day, $activeDays) ? 'active' : 'inactive' }}">
                                        {{ $dayShort[$day] }}
                                    </div>
                                @endforeach
                            </div>
                            @else
                                <span style="color:#cbd5e1;font-size:0.8rem;">—</span>
                            @endif
                        </td>
                        <td class="time-cell">
                            @if($firstSched)
                                <span class="time-range">
                                    {{ \Carbon\Carbon::parse($firstSched->start_time)->format('h:i A') }}
                                    <span class="time-sep">–</span>
                                    {{ \Carbon\Carbon::parse($firstSched->end_time)->format('h:i A') }}
                                </span>
                            @else
                                <span style="color:#cbd5e1;">TBA</span>
                            @endif
                        </td>
                        <td>
                            <div class="units-badge">{{ $subject->units ?? '—' }}</div>
                        </td>
                        <td style="color:#f8e7d3;">{{ $subject->instructorUser->name ?? 'TBA' }}</td>
                        <td style="color:#f8e7d3;font-weight:600;">{{ $subject->section ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-journal-x"></i>
                                <p>No subjects found for your year level and semester.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Subject Cards — outside table-card so they show on mobile -->
        </div><!-- end table-card -->

        <div class="subject-cards">
            @forelse($subjects as $subject)
            @php
                $allDays    = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                $dayShort   = ['Monday'=>'M','Tuesday'=>'T','Wednesday'=>'W','Thursday'=>'Th','Friday'=>'F','Saturday'=>'Sa'];
                $activeDays = $subject->schedules->pluck('day')->toArray();
                $firstSched = $subject->schedules->first();
            @endphp
            <div class="subject-card">
                <div class="subject-card-header">
                    <span class="subject-code-mobile">{{ $subject->code }}</span>
                    <span class="subject-units-mobile">{{ $subject->units ?? '—' }} units</span>
                </div>
                <div class="subject-name-mobile">{{ $subject->name }}</div>
                <div class="subject-details">
                    <div class="detail-row">
                        <i class="bi bi-clock detail-icon"></i>
                        <span class="detail-label">Time:</span>
                        <span class="detail-value">
                            @if($firstSched)
                                {{ \Carbon\Carbon::parse($firstSched->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($firstSched->end_time)->format('h:i A') }}
                            @else TBA @endif
                        </span>
                    </div>
                    <div class="detail-row">
                        <i class="bi bi-person detail-icon"></i>
                        <span class="detail-label">Instructor:</span>
                        <span class="detail-value">{{ $subject->instructorUser->name ?? 'TBA' }}</span>
                    </div>
                    <div class="detail-row">
                        <i class="bi bi-tag detail-icon"></i>
                        <span class="detail-label">Section:</span>
                        <span class="detail-value">{{ $subject->section ?? '—' }}</span>
                    </div>
                    <div class="detail-row">
                        <i class="bi bi-calendar-week detail-icon"></i>
                        <span class="detail-label">Days:</span>
                        <div class="day-badges-mobile">
                            @if($subject->schedules->isNotEmpty())
                                @foreach($allDays as $day)
                                    <div class="day-badge-mobile {{ in_array($day, $activeDays) ? 'active' : 'inactive' }}">
                                        {{ $dayShort[$day] }}
                                    </div>
                                @endforeach
                            @else
                                <span class="detail-value">—</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-journal-x"></i>
                <p>No subjects found for your year level and semester.</p>
            </div>
            @endforelse
        </div>
</div>
@endsection