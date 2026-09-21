@extends('layouts.app')

@section('title', 'Class Schedules & Room Assignment')

@section('content')
<style>
    /* Scoped Schedules Dashboard Styles */
    .sch-header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .sch-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #D4AF37;
        background: rgba(212, 175, 55, 0.1);
        border: 1px solid rgba(212, 175, 55, 0.25);
        padding: 4px 12px;
        border-radius: 9999px;
        margin-bottom: 8px;
    }
    .sch-page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #FCF8F2;
        margin: 0 0 6px 0;
        letter-spacing: -0.02em;
    }
    .sch-page-subtitle {
        color: #A39683;
        font-size: 0.9rem;
        margin: 0;
    }
    .sch-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #7A1A1A 0%, #9C2727 100%);
        color: #FFFFFF !important;
        border: 1px solid rgba(212, 175, 55, 0.35);
        padding: 10px 20px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 4px 14px rgba(122, 26, 26, 0.4);
    }
    .sch-btn-primary:hover {
        background: linear-gradient(135deg, #8E1F1F 0%, #B22E2E 100%);
        box-shadow: 0 6px 20px rgba(122, 26, 26, 0.6), 0 0 12px rgba(212, 175, 55, 0.25);
        transform: translateY(-1px);
        color: #FFFFFF !important;
    }

    /* Sub-navigation tabs */
    .sch-nav-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }
    .sch-tab-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        border-radius: 12px;
        font-size: 0.86rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .sch-tab-active {
        background: linear-gradient(135deg, #7A1A1A, #9C2727);
        color: #FFF !important;
        border: 1px solid rgba(212, 175, 55, 0.35);
        box-shadow: 0 4px 12px rgba(122, 26, 26, 0.4);
    }
    .sch-tab-inactive {
        background: rgba(255, 255, 255, 0.05);
        color: #D1C5B4 !important;
        border: 1px solid rgba(212, 175, 55, 0.15);
    }
    .sch-tab-inactive:hover {
        background: rgba(212, 175, 55, 0.12);
        color: #FFF !important;
        border-color: rgba(212, 175, 55, 0.3);
    }
    
    /* Quick Stats Grid */
    .sch-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .sch-stat-card {
        background: rgba(26, 17, 17, 0.7);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1px solid rgba(212, 175, 55, 0.15);
        border-radius: 14px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.25s ease;
    }
    .sch-stat-card:hover {
        border-color: rgba(212, 175, 55, 0.35);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        transform: translateY(-2px);
    }
    .sch-stat-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .sch-stat-icon-gold {
        background: rgba(212, 175, 55, 0.12);
        color: #D4AF37;
        border: 1px solid rgba(212, 175, 55, 0.25);
    }
    .sch-stat-icon-emerald {
        background: rgba(52, 211, 153, 0.12);
        color: #34D399;
        border: 1px solid rgba(52, 211, 153, 0.25);
    }
    .sch-stat-icon-blue {
        background: rgba(96, 165, 250, 0.12);
        color: #60A5FA;
        border: 1px solid rgba(96, 165, 250, 0.25);
    }
    .sch-stat-icon-amber {
        background: rgba(251, 191, 36, 0.12);
        color: #FBBF24;
        border: 1px solid rgba(251, 191, 36, 0.25);
    }
    .sch-stat-num {
        font-size: 1.4rem;
        font-weight: 700;
        color: #FCF8F2;
        line-height: 1.2;
    }
    .sch-stat-label {
        font-size: 0.78rem;
        color: #A39683;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 500;
    }

    /* Main Container Card */
    .sch-card {
        background: rgba(26, 17, 17, 0.75);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(212, 175, 55, 0.18);
        border-radius: 18px;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45);
        overflow: hidden;
        margin-bottom: 24px;
    }
    .sch-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid rgba(212, 175, 55, 0.12);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        background: rgba(20, 12, 12, 0.5);
    }
    
    /* Search & Filter Form */
    .sch-filter-form {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        width: 100%;
    }
    .sch-search-box {
        position: relative;
        flex: 1;
        min-width: 250px;
    }
    .sch-search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #A39683;
        font-size: 0.9rem;
        pointer-events: none;
    }
    .sch-input {
        width: 100%;
        background: rgba(17, 10, 10, 0.85);
        border: 1px solid rgba(212, 175, 55, 0.18);
        color: #FCF8F2;
        padding: 9px 14px 9px 38px;
        border-radius: 10px;
        font-size: 0.88rem;
        outline: none;
        transition: all 0.2s ease;
    }
    .sch-input:focus {
        border-color: #D4AF37;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
        background: rgba(17, 10, 10, 0.95);
    }
    .sch-select {
        background: rgba(17, 10, 10, 0.85);
        border: 1px solid rgba(212, 175, 55, 0.18);
        color: #FCF8F2;
        padding: 9px 34px 9px 14px;
        border-radius: 10px;
        font-size: 0.88rem;
        outline: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23D4AF37' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        cursor: pointer;
        min-width: 150px;
    }
    .sch-select:focus {
        border-color: #D4AF37;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
    }
    .sch-select option {
        background-color: #1A1111;
        color: #FCF8F2;
    }
    .sch-btn-filter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(212, 175, 55, 0.12);
        color: #D4AF37;
        border: 1px solid rgba(212, 175, 55, 0.28);
        padding: 9px 16px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .sch-btn-filter:hover {
        background: rgba(212, 175, 55, 0.22);
        border-color: #D4AF37;
        color: #FFF;
    }
    .sch-btn-clear {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: transparent;
        color: #F87171;
        border: 1px solid rgba(248, 113, 113, 0.25);
        padding: 9px 14px;
        border-radius: 10px;
        font-size: 0.88rem;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .sch-btn-clear:hover {
        background: rgba(248, 113, 113, 0.12);
        border-color: #F87171;
        color: #FCA5A5;
    }

    /* Schedules Table */
    .sch-table-wrap {
        width: 100%;
        overflow-x: auto;
    }
    .sch-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    .sch-table th {
        background: rgba(17, 10, 10, 0.6);
        padding: 14px 20px;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #D4AF37;
        border-bottom: 1px solid rgba(212, 175, 55, 0.15);
        white-space: nowrap;
    }
    .sch-table td {
        padding: 16px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        color: #FCF8F2;
        vertical-align: middle;
        font-size: 0.88rem;
    }
    .sch-table tbody tr {
        transition: background 0.2s ease;
    }
    .sch-table tbody tr:hover {
        background: rgba(212, 175, 55, 0.035);
    }
    .sch-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Badges */
    .sch-badge-code {
        font-weight: 700;
        font-family: monospace;
        color: #D4AF37;
        font-size: 0.95rem;
    }
    .sch-badge-section {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 600;
        background: rgba(96, 165, 250, 0.12);
        color: #93C5FD;
        border: 1px solid rgba(96, 165, 250, 0.28);
    }
    .sch-badge-room {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 600;
        background: rgba(251, 191, 36, 0.12);
        color: #FCD34D;
        border: 1px solid rgba(251, 191, 36, 0.28);
    }
    .sch-badge-day {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        background: rgba(212, 175, 55, 0.12);
        color: #D4AF37;
        border: 1px solid rgba(212, 175, 55, 0.22);
    }

    /* Action Buttons */
    .sch-action-btn {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(212, 175, 55, 0.2);
        background: rgba(255, 255, 255, 0.04);
        color: #D1C5B4;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .sch-action-btn:hover {
        background: rgba(212, 175, 55, 0.15);
        color: #D4AF37;
        border-color: #D4AF37;
        transform: translateY(-1px);
    }
    .sch-action-btn-danger:hover {
        background: rgba(248, 113, 113, 0.15);
        color: #F87171;
        border-color: #F87171;
    }

    /* Modals */
    .sch-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 1050;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        transition: opacity 0.25s ease;
    }
    .sch-modal-backdrop.show {
        display: flex;
        opacity: 1;
    }
    .sch-modal-card {
        width: 100%;
        max-width: 620px;
        background: linear-gradient(145deg, #1F1515 0%, #150D0D 100%);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.8), 0 0 20px rgba(212, 175, 55, 0.1);
        transform: scale(0.95);
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
    }
    .sch-modal-backdrop.show .sch-modal-card {
        transform: scale(1);
    }
    .sch-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid rgba(212, 175, 55, 0.15);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(122, 26, 26, 0.12);
    }
    .sch-modal-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #FCF8F2;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .sch-modal-close {
        background: transparent;
        border: none;
        color: #A39683;
        font-size: 1.1rem;
        cursor: pointer;
        padding: 4px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    .sch-modal-close:hover {
        color: #FCF8F2;
        background: rgba(255, 255, 255, 0.08);
    }
    .sch-modal-body {
        padding: 24px;
    }
    .sch-modal-footer {
        padding: 16px 24px;
        border-top: 1px solid rgba(212, 175, 55, 0.15);
        background: rgba(15, 9, 9, 0.6);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
    .sch-form-label {
        display: block;
        font-size: 0.82rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #D4AF37;
        margin-bottom: 8px;
    }
    .sch-form-control {
        width: 100%;
        background: rgba(13, 8, 8, 0.8);
        border: 1px solid rgba(212, 175, 55, 0.2);
        color: #FCF8F2;
        padding: 10px 14px;
        border-radius: 10px;
        font-size: 0.9rem;
        outline: none;
        transition: all 0.2s ease;
    }
    .sch-form-control:focus {
        border-color: #D4AF37;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.18);
        background: rgba(13, 8, 8, 0.95);
    }
    .sch-form-control option {
        background-color: #1A1111;
        color: #FCF8F2;
    }
    .sch-btn-ghost {
        background: transparent;
        color: #A39683;
        border: 1px solid rgba(255, 255, 255, 0.15);
        padding: 9px 18px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .sch-btn-ghost:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #FCF8F2;
    }
</style>

<!-- Alert Notifications -->
@if(session('success'))
<div style="background: rgba(52, 211, 153, 0.12); border: 1px solid rgba(52, 211, 153, 0.3); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; color: #34D399;">
    <i class="bi bi-check-circle-fill" style="font-size: 1.15rem;"></i>
    <div style="font-size: 0.9rem; font-weight: 500;">{{ session('success') }}</div>
</div>
@endif

@if(session('error'))
<div style="background: rgba(248, 113, 113, 0.12); border: 1px solid rgba(248, 113, 113, 0.3); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; color: #F87171;">
    <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.15rem;"></i>
    <div style="font-size: 0.9rem; font-weight: 500;">{{ session('error') }}</div>
</div>
@endif

@if($errors->any())
<div style="background: rgba(248, 113, 113, 0.12); border: 1px solid rgba(248, 113, 113, 0.3); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #F87171;">
    <div style="font-weight: 600; margin-bottom: 6px;">Please check the following errors:</div>
    <ul style="margin: 0; padding-left: 20px; font-size: 0.88rem;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Header Section -->
<div class="sch-header-bar">
    <div>
        <div class="sch-badge-pill">
            <i class="bi bi-clock-fill"></i> Academic Timetable &amp; Schedules
        </div>
        <h1 class="sch-page-title">Class Schedules &amp; Room Assignment</h1>
        <p class="sch-page-subtitle">Assign subjects, student sections, lecture rooms, faculty instructors, and timeslots.</p>
    </div>
    
    <div>
        <button type="button" class="sch-btn-primary" onclick="openModal('addScheduleModal')">
            <i class="bi bi-calendar-plus-fill"></i> Assign Schedule
        </button>
    </div>
</div>

<!-- Nav Tabs -->
<div class="sch-nav-tabs">
    <a href="{{ route('admin.subjects') }}" class="sch-tab-link sch-tab-inactive">
        <i class="bi bi-journal-bookmark-fill"></i> Subjects &amp; Curriculum
    </a>
    <a href="{{ route('admin.class-schedules.index') }}" class="sch-tab-link sch-tab-active">
        <i class="bi bi-clock-fill"></i> Class Schedules
    </a>
</div>

<!-- Quick Statistics Bar -->
<div class="sch-stats-grid">
    <div class="sch-stat-card">
        <div class="sch-stat-icon-wrap sch-stat-icon-gold">
            <i class="bi bi-calendar2-week-fill"></i>
        </div>
        <div>
            <div class="sch-stat-num">{{ $stats['total'] ?? 0 }}</div>
            <div class="sch-stat-label">Total Schedules</div>
        </div>
    </div>
    <div class="sch-stat-card">
        <div class="sch-stat-icon-wrap sch-stat-icon-emerald">
            <i class="bi bi-book-half"></i>
        </div>
        <div>
            <div class="sch-stat-num">{{ $stats['subjects'] ?? 0 }}</div>
            <div class="sch-stat-label">Active Subjects</div>
        </div>
    </div>
    <div class="sch-stat-card">
        <div class="sch-stat-icon-wrap sch-stat-icon-blue">
            <i class="bi bi-diagram-3-fill"></i>
        </div>
        <div>
            <div class="sch-stat-num">{{ $stats['sections'] ?? 0 }}</div>
            <div class="sch-stat-label">Assigned Sections</div>
        </div>
    </div>
    <div class="sch-stat-card">
        <div class="sch-stat-icon-wrap sch-stat-icon-amber">
            <i class="bi bi-door-closed-fill"></i>
        </div>
        <div>
            <div class="sch-stat-num">{{ $stats['rooms'] ?? 0 }}</div>
            <div class="sch-stat-label">Lecture Rooms</div>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="sch-card">
    <div class="sch-card-header">
        <form method="GET" action="{{ route('admin.class-schedules.index') }}" class="sch-filter-form">
            <div class="sch-search-box">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="sch-input" placeholder="Search by subject code, title, room, or instructor..." value="{{ request('search') }}">
            </div>
            
            <select name="day" class="sch-select">
                <option value="">All Days</option>
                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $d)
                    <option value="{{ $d }}" {{ request('day') === $d ? 'selected' : '' }}>{{ $d }}</option>
                @endforeach
            </select>

            <select name="section_id" class="sch-select">
                <option value="">All Sections</option>
                @foreach($sections as $sec)
                    <option value="{{ $sec->id }}" {{ request('section_id') == $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
                @endforeach
            </select>
            
            <button type="submit" class="sch-btn-filter">
                <i class="bi bi-funnel"></i> Filter
            </button>
            
            @if(request()->hasAny(['search', 'day', 'section_id', 'subject_id']))
                <a href="{{ route('admin.class-schedules.index') }}" class="sch-btn-clear">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            @endif
        </form>
    </div>

    <div class="sch-table-wrap">
        <table class="sch-table">
            <thead>
                <tr>
                    <th style="min-width: 240px;">Subject &amp; Curriculum</th>
                    <th style="min-width: 140px;">Section</th>
                    <th style="min-width: 180px;">Instructor</th>
                    <th style="min-width: 200px;">Weekly Time Slot</th>
                    <th style="min-width: 130px;">Assigned Room</th>
                    <th style="min-width: 100px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $schedule)
                <tr>
                    <td>
                        <div class="sch-badge-code">{{ $schedule->subject->code ?? 'N/A' }}</div>
                        <div style="font-size: 0.88rem; font-weight: 600; color: #FCF8F2;">{{ $schedule->subject->name ?? 'Untitled Subject' }}</div>
                        <div style="font-size: 0.74rem; color: #A39683; margin-top: 2px;">
                            {{ $schedule->subject->course ?? 'General' }} &bull; {{ $schedule->subject->units ?? 3 }} Units
                        </div>
                    </td>
                    <td>
                        <span class="sch-badge-section">
                            <i class="bi bi-diagram-3-fill"></i>
                            {{ $schedule->section->name ?? 'N/A' }}
                        </span>
                        @if($schedule->section && $schedule->section->course)
                            <div style="font-size: 0.72rem; color: #A39683; margin-top: 3px;">{{ $schedule->section->course->code }}</div>
                        @endif
                    </td>
                    <td>
                        @if($schedule->teacher)
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: rgba(212,175,55,0.15); border: 1px solid rgba(212,175,55,0.3); display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 700; color: #D4AF37;">
                                    {{ strtoupper(substr($schedule->teacher->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-size: 0.86rem; font-weight: 600; color: #FCF8F2;">{{ $schedule->teacher->name }}</div>
                                    <div style="font-size: 0.72rem; color: #A39683;">{{ $schedule->teacher->employee_id ?? 'Faculty' }}</div>
                                </div>
                            </div>
                        @else
                            <span style="font-size: 0.8rem; color: #A39683; font-style: italic;">Unassigned</span>
                        @endif
                    </td>
                    <td>
                        <div style="margin-bottom: 3px;">
                            <span class="sch-badge-day">
                                <i class="bi bi-calendar-event"></i> {{ $schedule->day_of_week }}
                            </span>
                        </div>
                        <div style="font-weight: 600; font-size: 0.86rem; color: #FCF8F2;">
                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }} &ndash; {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                        </div>
                    </td>
                    <td>
                        <span class="sch-badge-room">
                            <i class="bi bi-door-closed-fill"></i> {{ $schedule->room }}
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: inline-flex; gap: 6px;">
                            <button type="button" 
                                    class="sch-action-btn" 
                                    title="Edit Schedule"
                                    onclick="openEditScheduleModal({{ $schedule->id }}, {{ $schedule->subject_id }}, {{ $schedule->section_id }}, {{ $schedule->teacher_id ?? 'null' }}, '{{ $schedule->day_of_week }}', '{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}', '{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}', '{{ addslashes($schedule->room) }}')">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <form action="{{ route('admin.class-schedules.destroy', $schedule) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this schedule slot?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sch-action-btn sch-action-btn-danger" title="Delete Schedule">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div style="text-align: center; padding: 56px 20px;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: radial-gradient(circle, rgba(122,26,26,0.4) 0%, rgba(30,21,21,0.2) 70%); border: 1px solid rgba(212,175,55,0.3); display: inline-flex; align-items: center; justify-content: center; font-size: 2.2rem; color: #D4AF37; margin-bottom: 20px;">
                                <i class="bi bi-calendar2-week"></i>
                            </div>
                            <h3 style="font-size: 1.25rem; font-weight: 700; color: #FCF8F2; margin-bottom: 8px;">No class schedules found</h3>
                            <p style="color: #A39683; font-size: 0.9rem; max-width: 440px; margin: 0 auto 24px auto;">
                                @if(request()->hasAny(['search', 'day', 'section_id', 'subject_id']))
                                    No scheduled classes match your current search or filters.
                                @else
                                    Assign subjects to lecture rooms, sections, instructors, and days of the week.
                                @endif
                            </p>
                            @if(request()->hasAny(['search', 'day', 'section_id', 'subject_id']))
                                <a href="{{ route('admin.class-schedules.index') }}" class="sch-btn-clear" style="display: inline-flex; margin-right: 8px;">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                                </a>
                            @endif
                            <button type="button" class="sch-btn-primary" onclick="openModal('addScheduleModal')">
                                <i class="bi bi-calendar-plus-fill"></i> Assign First Schedule
                            </button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($schedules->hasPages())
    <div style="padding: 16px 24px; border-top: 1px solid rgba(212, 175, 55, 0.15); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: rgba(20, 12, 12, 0.5);">
        <div style="font-size: 0.84rem; color: #A39683;">
            Showing <span style="color: #D4AF37; font-weight: 600;">{{ $schedules->firstItem() ?? 0 }}</span> to <span style="color: #D4AF37; font-weight: 600;">{{ $schedules->lastItem() ?? 0 }}</span> of <span style="color: #FCF8F2; font-weight: 600;">{{ $schedules->total() }}</span> schedule slots
        </div>
        <div>
            {{ $schedules->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Add Schedule Modal -->
<div id="addScheduleModal" class="sch-modal-backdrop" onclick="if(event.target === this) closeModal('addScheduleModal')">
    <div class="sch-modal-card">
        <div class="sch-modal-header">
            <div class="sch-modal-title">
                <i class="bi bi-calendar-plus-fill text-warning"></i>
                <span>Assign Class Schedule</span>
            </div>
            <button type="button" class="sch-modal-close" onclick="closeModal('addScheduleModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form action="{{ route('admin.class-schedules.store') }}" method="POST">
            @csrf
            <div class="sch-modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label class="sch-form-label">Subject</label>
                        <select name="subject_id" class="sch-form-control" required>
                            <option value="">-- Select Subject --</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->code }} - {{ $subject->name }} ({{ $subject->course }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="sch-form-label">Section</label>
                        <select name="section_id" class="sch-form-control" required>
                            <option value="">-- Select Section --</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}">
                                    {{ $section->name }} ({{ $section->course->code ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="sch-form-label">Faculty Instructor (Optional)</label>
                    <select name="teacher_id" class="sch-form-control">
                        <option value="">-- Unassigned / Assign Later --</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">
                                {{ $teacher->name }} ({{ $teacher->employee_id ?? 'Instructor' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label class="sch-form-label">Day of Week</label>
                        <select name="day_of_week" class="sch-form-control" required>
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                <option value="{{ $day }}">{{ $day }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="sch-form-label">Start Time</label>
                        <input type="time" name="start_time" class="sch-form-control" required>
                    </div>

                    <div>
                        <label class="sch-form-label">End Time</label>
                        <input type="time" name="end_time" class="sch-form-control" required>
                    </div>
                </div>

                <div style="margin-bottom: 0;">
                    <label class="sch-form-label">Room / Laboratory</label>
                    <input type="text" name="room" class="sch-form-control" placeholder="e.g. Rm 304, Computer Lab 2, Main Gym" required>
                </div>
            </div>
            <div class="sch-modal-footer">
                <button type="button" class="sch-btn-ghost" onclick="closeModal('addScheduleModal')">Cancel</button>
                <button type="submit" class="sch-btn-primary">
                    <i class="bi bi-check2-circle"></i> Save Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div id="editScheduleModal" class="sch-modal-backdrop" onclick="if(event.target === this) closeModal('editScheduleModal')">
    <div class="sch-modal-card">
        <div class="sch-modal-header">
            <div class="sch-modal-title">
                <i class="bi bi-pencil-square text-warning"></i>
                <span>Edit Class Schedule</span>
            </div>
            <button type="button" class="sch-modal-close" onclick="closeModal('editScheduleModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="editScheduleForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="sch-modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label class="sch-form-label">Subject</label>
                        <select name="subject_id" id="edit_subject_id" class="sch-form-control" required>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">
                                    {{ $subject->code }} - {{ $subject->name }} ({{ $subject->course }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="sch-form-label">Section</label>
                        <select name="section_id" id="edit_section_id" class="sch-form-control" required>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}">
                                    {{ $section->name }} ({{ $section->course->code ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="sch-form-label">Faculty Instructor</label>
                    <select name="teacher_id" id="edit_teacher_id" class="sch-form-control">
                        <option value="">-- Unassigned --</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">
                                {{ $teacher->name }} ({{ $teacher->employee_id ?? 'Instructor' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label class="sch-form-label">Day of Week</label>
                        <select name="day_of_week" id="edit_day_of_week" class="sch-form-control" required>
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                <option value="{{ $day }}">{{ $day }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="sch-form-label">Start Time</label>
                        <input type="time" name="start_time" id="edit_start_time" class="sch-form-control" required>
                    </div>

                    <div>
                        <label class="sch-form-label">End Time</label>
                        <input type="time" name="end_time" id="edit_end_time" class="sch-form-control" required>
                    </div>
                </div>

                <div style="margin-bottom: 0;">
                    <label class="sch-form-label">Room / Laboratory</label>
                    <input type="text" name="room" id="edit_room" class="sch-form-control" required>
                </div>
            </div>
            <div class="sch-modal-footer">
                <button type="button" class="sch-btn-ghost" onclick="closeModal('editScheduleModal')">Cancel</button>
                <button type="submit" class="sch-btn-primary">
                    <i class="bi bi-check2-circle"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script @cspNonce>
    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('show');
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('show');
    }

    function openEditScheduleModal(id, subjectId, sectionId, teacherId, day, startTime, endTime, room) {
        const form = document.getElementById('editScheduleForm');
        form.action = "{{ url('admin/class-schedules') }}/" + id;

        document.getElementById('edit_subject_id').value = subjectId;
        document.getElementById('edit_section_id').value = sectionId;
        document.getElementById('edit_teacher_id').value = teacherId !== null ? teacherId : '';
        document.getElementById('edit_day_of_week').value = day;
        document.getElementById('edit_start_time').value = startTime;
        document.getElementById('edit_end_time').value = endTime;
        document.getElementById('edit_room').value = room;

        openModal('editScheduleModal');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('addScheduleModal');
            closeModal('editScheduleModal');
        }
    });
</script>
@endpush
