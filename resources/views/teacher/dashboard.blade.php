@extends('layouts.app')
@section('page-title', 'Instructor Dashboard')

@push('styles')
<style>
    /* FullCalendar Overrides - Dark Squircle Theme */
    .fc {
        color: #f3ede4;
        font-family: inherit;
    }
    .fc-theme-standard td, .fc-theme-standard th, .fc-theme-standard .fc-scrollgrid {
        border-color: rgba(255,255,255,0.06) !important;
    }
    .fc .fc-col-header-cell {
        background: rgba(255,255,255,0.025) !important;
        padding: 12px 0;
        border: none !important;
    }
    .fc .fc-col-header-cell-cushion {
        color: #cfa46f;
        text-transform: uppercase;
        font-size: 0.8rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-decoration: none !important;
    }
    .fc .fc-daygrid-day-frame {
        border-radius: 12px !important;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.04) !important;
        margin: 3px !important;
        min-height: 85px !important;
        transition: all 0.2s ease;
    }
    .fc .fc-daygrid-day-frame:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(207, 164, 111, 0.3) !important;
        transform: translateY(-1px);
        z-index: 2;
    }
    .fc .fc-daygrid-day-number {
        color: #f3ede4;
        font-weight: 700;
        font-size: 0.95rem;
        padding: 6px 10px !important;
        text-decoration: none !important;
    }
    .fc .fc-day-sun .fc-daygrid-day-number {
        color: #f87171 !important;
    }
    .fc .fc-day-other .fc-daygrid-day-number {
        color: #5c4e40;
        opacity: 0.5;
    }
    .fc a {
        text-decoration: none !important;
    }
    .fc .fc-day-today .fc-daygrid-day-frame {
        background: rgba(207, 164, 111, 0.1) !important;
        border: 2px solid #cfa46f !important;
        box-shadow: 0 0 16px rgba(207, 164, 111, 0.2) !important;
    }
    .fc .fc-day-today .fc-daygrid-day-number {
        color: #ffffff !important;
        font-weight: 800 !important;
    }
    .fc .fc-daygrid-event {
        border: none !important;
        border-radius: 6px !important;
        padding: 3px 6px;
        font-size: 0.74rem;
        font-weight: 700;
        color: #ffffff !important;
        margin: 2px 4px !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3) !important;
        cursor: pointer;
    }
    .fc .fc-scrollgrid-sync-table { width: 100% !important; }
    .fc-col-header { width: 100% !important; }
    .fc-daygrid-body { width: 100% !important; }
    .fc-scrollgrid { width: 100% !important; }

    /* Modal styling */
    .event-modal-content {
        background: linear-gradient(145deg, #241414 0%, #150a0a 100%);
        border: 1px solid rgba(207, 164, 111, 0.3);
        border-radius: 20px;
        color: #f3e7cd;
    }
    .event-modal-header {
        border-bottom: 1px solid rgba(207, 164, 111, 0.15);
        padding: 18px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .event-modal-body {
        padding: 20px 24px;
    }
</style>
@endpush

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
<div class="premium-hero-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
        <div class="d-flex align-items-center gap-3">
            <div>
                <h1 style="color: #ffffff; font-weight: 800; margin: 0 0 6px 0; font-size: clamp(1.4rem, 4vw, 2.1rem); letter-spacing: -0.5px;">{{ Auth::user()->name }}</h1>
                <div style="color: #b39b82; font-size: 0.88rem; font-weight: 500;">
                    Manage your active classes, attendance, and student requests
                </div>
            </div>
        </div>
        <div class="d-flex flex-column align-items-md-end gap-2">
            <div class="hero-clock-pill">
                <div class="hero-clock-time">
                    <i class="bi bi-clock"></i> <span id="teacherClock">{{ now()->format('h:i A') }}</span>
                </div>
                <div class="hero-clock-date">{{ now()->format('l, F j, Y') }}</div>
            </div>
            <div class="d-flex gap-2 justify-content-end mt-1">
                <a href="{{ route('teacher.reports.pdf') }}" target="_blank" class="btn-modern-glass" style="padding: 8px 16px; font-size: 0.84rem;">
                    <i class="bi bi-file-earmark-arrow-down-fill" style="color: var(--gold);"></i> Export
                </a>
                <a href="{{ route('teacher.excuse.reviews') }}" class="btn-modern-gold" style="padding: 8px 16px; font-size: 0.84rem;">
                    <i class="bi bi-file-earmark-text"></i> Reviews
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
<div class="ent-grid ent-grid-4 ent-mb-lg ent-fade-up ent-delay-2" id="realStats" style="display:none; gap:20px; margin-bottom:24px;">
    <x-card type="kpi" accent="gold" label="Today's Classes" value="{{ $todayClasses->count() }}" icon="bi bi-easel-fill" />
    <x-card type="kpi" accent="info" label="Total Students" value="{{ $totalStudents ?? 0 }}" icon="bi bi-people-fill" />
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
                    <div class="d-flex align-items-center gap-1"><div style="width:10px;height:10px;border-radius:50%;background:#ec4899;"></div> Exam</div>
                    <div class="d-flex align-items-center gap-1"><div style="width:10px;height:10px;border-radius:50%;background:#fbbf24;"></div> Meeting</div>
                    <div class="d-flex align-items-center gap-1"><div style="width:10px;height:10px;border-radius:50%;background:#a78bfa;"></div> School Event</div>
                    <div class="d-flex align-items-center gap-1"><div style="width:10px;height:10px;border-radius:50%;background:#4ade80;"></div> Holiday</div>
                </div>

                <div style="overflow-x: auto;">
                    <div id="schoolCalendar" style="min-height: 500px; min-width: 700px;"></div>
                </div>
            </x-card>
        </div>

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
        
        <!-- Post Announcement -->
        <div class="mb-4">
            <x-card title="Post Announcement" icon="bi bi-megaphone-fill">
                <form action="{{ route('teacher.announcements.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #b39b82;">Target Class *</label>
                        <select name="target_id" class="form-select" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                            <option value="">Select a class...</option>
                            @foreach($teacherSubjects as $sub)
                                <option value="{{ $sub->id }}" style="color: #000;">{{ $sub->name }} ({{ $sub->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #b39b82;">Title *</label>
                        <input type="text" name="title" class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #b39b82;">Message *</label>
                        <textarea name="content" class="form-control" rows="3" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-weight: 600; color: #b39b82;">Schedule For (Optional)</label>
                        <input type="datetime-local" name="scheduled_for" class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                    </div>
                    <button type="submit" class="btn btn-primary w-100" style="background: var(--gold); border: none; font-weight: 600; color: #fff;">
                        <i class="bi bi-send-fill"></i> Post Announcement
                    </button>
                </form>
            </x-card>
        </div>
        
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<style>
    .fc {
        color: #f3ede4;
        font-family: inherit;
    }
    .fc-theme-standard td, .fc-theme-standard th {
        border-color: rgba(255,255,255,0.04) !important;
    }
    .fc .fc-toolbar-title {
        font-size: 1.25rem !important;
        font-weight: 800;
        color: #fdfbf7;
    }
    .fc .fc-col-header-cell {
        background: rgba(255,255,255,0.025) !important;
        padding: 12px 0 !important;
        border: none !important;
    }
    .fc .fc-col-header-cell-cushion {
        color: #cfa46f;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.08em;
    }
    .fc .fc-daygrid-day-frame {
        border-radius: 12px !important;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.03) !important;
        margin: 3px !important;
        min-height: 90px !important;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .fc .fc-daygrid-day-frame:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(207, 164, 111, 0.3) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4);
    }
    .fc .fc-daygrid-day-number {
        color: #f3ede4;
        font-size: 1.05rem;
        font-weight: 700;
        padding: 8px 12px !important;
        text-decoration: none !important;
    }
    .fc .fc-day-sun .fc-daygrid-day-number {
        color: #f87171 !important;
    }
    .fc .fc-day-other .fc-daygrid-day-number {
        color: #5c4e40;
        opacity: 0.5;
    }
    .fc .fc-day-today .fc-daygrid-day-frame {
        background: rgba(255, 209, 102, 0.08) !important;
        border: 2px solid #ffd166 !important;
        box-shadow: 0 0 20px rgba(255, 209, 102, 0.25), inset 0 0 12px rgba(255, 209, 102, 0.08) !important;
    }
    .fc .fc-day-today .fc-daygrid-day-number {
        color: #ffffff !important;
        font-weight: 800 !important;
    }
    .fc .fc-daygrid-event {
        border-radius: 8px !important;
        padding: 4px 8px;
        font-size: 0.78rem;
        font-weight: 700;
        color: #ffffff !important;
        margin: 2px 4px !important;
        transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: none !important;
    }
    .fc .fc-daygrid-event:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 6px 16px rgba(0,0,0,0.5) !important;
        filter: brightness(1.15);
    }
</style>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content event-modal-content w-100">
            <div class="event-modal-header">
                <h5 class="modal-title" style="margin: 0; font-weight: 700;" id="eventTitle">Event Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="event-modal-body">
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: #b39b82; font-weight: 700; margin-bottom: 4px;">Event Type</div>
                    <span id="eventTypeBadge" class="badge" style="background: rgba(207,164,111,0.2); color: #f3e7cd; font-size: 0.82rem; padding: 5px 10px;">Type</span>
                </div>
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: #b39b82; font-weight: 700; margin-bottom: 4px;">Date</div>
                    <div id="eventDateText" style="color: #ffffff; font-weight: 600;">Date</div>
                </div>
                <div id="eventDescriptionContainer" style="margin-bottom: 12px; display: none;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: #b39b82; font-weight: 700; margin-bottom: 4px;">Description</div>
                    <div id="eventDescription" style="color: #d4c8b8; font-size: 0.9rem;"></div>
                </div>
                <div id="eventLocationContainer" style="display: none;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: #b39b82; font-weight: 700; margin-bottom: 4px;">Location</div>
                    <div id="eventLocation" style="color: #d4c8b8; font-size: 0.9rem;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script nonce="{{ csp_nonce() }}">
// Skeleton -> Real Content
document.addEventListener('DOMContentLoaded', function() {
    var skelStats = document.getElementById('skelStats');
    var realStats = document.getElementById('realStats');
    if (skelStats && realStats) { skelStats.style.display = 'none'; realStats.style.display = 'grid'; }
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
    if (!calendarEl || typeof FullCalendar === 'undefined') return;
    
    schoolCalendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: false,
        height: 'auto',
        firstDay: 0,
        weekends: true,
        fixedWeekCount: false,
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
        eventClick: function(info) {
            const event = info.event;
            const props = event.extendedProps || {};
            
            document.getElementById('eventTitle').textContent = event.title;
            document.getElementById('eventTypeBadge').textContent = props.type || 'Event';
            document.getElementById('eventDateText').textContent = event.start ? event.start.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : '';
            
            const descEl = document.getElementById('eventDescription');
            const descContainer = document.getElementById('eventDescriptionContainer');
            if (props.description) {
                descEl.textContent = props.description;
                descContainer.style.display = 'block';
            } else {
                descContainer.style.display = 'none';
            }
            
            const locEl = document.getElementById('eventLocation');
            const locContainer = document.getElementById('eventLocationContainer');
            if (props.location) {
                locEl.textContent = props.location;
                locContainer.style.display = 'block';
            } else {
                locContainer.style.display = 'none';
            }
            
            const modalEl = document.getElementById('eventDetailModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
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
});

function updateCalendarTitle() {
    if(schoolCalendar && schoolCalendar.view) {
        const titleEl = document.getElementById('calendarTitle');
        if (titleEl) {
            titleEl.textContent = schoolCalendar.view.title;
        }
    }
}
</script>
@endsection
