@extends('layouts.app')
@section('portal-title', request('task') === 'attendance' ? 'Take Attendance' : 'My Classes')
@section('page-title', request('task') === 'attendance' ? 'Take Attendance' : 'My Classes')

@push('styles')
<style>
    .teacher-classes-wrapper {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        padding-bottom: 90px;
    }
    
    .class-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
        margin-top: 20px;
        margin-bottom: 40px;
    }
    
    .class-card {
        background: linear-gradient(145deg, rgba(32,20,15,0.8) 0%, rgba(20,10,5,0.9) 100%);
        border: 1px solid rgba(207,164,111,0.2);
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        display: flex;
        flex-direction: column;
    }
    
    .class-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.4);
        border-color: rgba(207,164,111,0.5);
    }
    
    .class-card-header {
        background: rgba(207,164,111,0.1);
        padding: 20px 24px;
        border-bottom: 1px solid rgba(207,164,111,0.15);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    
    .class-code {
        font-family: monospace;
        font-size: 0.85rem;
        color: #d6b67b;
        background: rgba(207,164,111,0.15);
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    
    .class-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: #f3e7cd;
        margin-top: 12px;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .class-meta-grid {
        padding: 20px 24px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        flex: 1;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .meta-icon {
        width: 32px;
        height: 32px;
        background: rgba(207,164,111,0.1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #cfa46f;
        font-size: 1rem;
    }
    
    .meta-text {
        font-size: 0.85rem;
        color: #b39b82;
        font-weight: 500;
    }
    
    .meta-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        color: rgba(179,155,130,0.6);
        font-weight: 700;
        letter-spacing: 1px;
    }
    
    .class-card-footer {
        padding: 16px 24px;
        border-top: 1px solid rgba(255,255,255,0.05);
        background: rgba(0,0,0,0.2);
    }
    
    .btn-manage-class {
        width: 100%;
        background: linear-gradient(135deg, #cfa46f 0%, #8f6e4a 100%);
        color: #fff;
        border: none;
        padding: 12px;
        border-radius: 12px;
        font-weight: 700;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .btn-manage-class:hover {
        background: linear-gradient(135deg, #dfb987 0%, #a8845c 100%);
        color: #fff;
        transform: translateY(-2px);
    }
</style>
@endpush

@section('content')
@php $attendanceMode = request('task') === 'attendance'; @endphp
<div class="teacher-classes-wrapper">
<div class="d-flex justify-content-between align-items-center mb-4 teacher-page-heading">
    <div>
        <h1 style="color: #f3e7cd; font-weight: 800; font-size: 1.8rem; margin: 0;">{{ $attendanceMode ? 'Take attendance' : 'My Classes' }}</h1>
        <p style="color: #b39b82; font-size: 0.95rem; margin-top: 4px;">{{ $attendanceMode ? 'Choose a class, then open its QR attendance screen.' : 'Open a class to manage its students, schedule, and materials.' }}</p>
    </div>
    <div>
        <a href="{{ route('teacher.subjects.create') }}" class="btn-action btn-primary-action" style="background: rgba(207,164,111,0.15); border: 1px solid rgba(207,164,111,0.3); color: #f3e7cd; padding: 10px 20px; border-radius: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <i class="bi bi-plus-lg"></i> Add Subject
        </a>
    </div>
</div>

<form method="GET" action="{{ route('teacher.classroom.index') }}" class="teacher-class-search" role="search" aria-label="Find one of my classes">
    @if($attendanceMode)<input type="hidden" name="task" value="attendance">@endif
    <div>
        <label for="teacherClassSearch">Find a class</label>
        <input type="search" id="teacherClassSearch" name="search" value="{{ request('search') }}" placeholder="Class name or subject code" class="tch-input" autocomplete="off">
    </div>
    <button type="submit" class="ent-btn ent-btn-primary"><i class="bi bi-search" aria-hidden="true"></i>Search</button>
    @if(request()->filled('search'))<a href="{{ route('teacher.classroom.index', $attendanceMode ? ['task' => 'attendance'] : []) }}" class="ent-btn ent-btn-secondary">Clear search</a>@endif
</form>

@if($subjects->isEmpty())
    <div class="empty-state" style="text-align: center; padding: 60px 20px; background: rgba(30,20,15,0.4); border-radius: 24px; border: 1px dashed rgba(207,164,111,0.2);">
        <i class="bi bi-journal-x" style="font-size: 3rem; color: #8f6e4a; margin-bottom: 16px;"></i>
        <h3 style="color: #f3e7cd; font-weight: 700;">{{ request()->filled('search') ? 'No classes match your search' : 'No Classes Assigned' }}</h3>
        <p style="color: #b39b82;">{{ request()->filled('search') ? 'Try another class name or subject code, or clear the search to see all your classes.' : 'Your assigned classes will appear here. Contact your school administrator if a class is missing.' }}</p>
    </div>
@else
    <div class="class-grid">
        @foreach($subjects as $subject)
            <div class="class-card">
                <div class="class-card-header">
                    <div>
                        <span class="class-code">{{ $subject->code }}</span>
                        <div class="class-title">{{ $subject->name }}</div>
                    </div>
                </div>
                
                <div class="class-meta-grid">
                    @php
                        $activeDays = $subject->schedules->pluck('day')->toArray();
                        $firstSched = $subject->schedules->first();
                        $dayShort = ['Monday'=>'Mon', 'Tuesday'=>'Tue', 'Wednesday'=>'Wed', 'Thursday'=>'Thu', 'Friday'=>'Fri', 'Saturday'=>'Sat'];
                        $dayLabels = array_map(fn($d) => $dayShort[$d] ?? $d, $activeDays);
                    @endphp
                    <div class="meta-item">
                        <div class="meta-icon"><i class="bi bi-calendar-range"></i></div>
                        <div>
                            <div class="meta-label">Schedule</div>
                            <div class="meta-text">{{ !empty($dayLabels) ? implode(',', $dayLabels) : 'TBA' }}</div>
                        </div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon"><i class="bi bi-clock"></i></div>
                        <div>
                            <div class="meta-label">Time</div>
                            <div class="meta-text">
                                @if($firstSched)
                                    {{ \Carbon\Carbon::parse($firstSched->start_time)->format('h:i A') }}
                                @else
                                    TBA
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon"><i class="bi bi-mortarboard"></i></div>
                        <div>
                            <div class="meta-label">Level / Sem</div>
                            <div class="meta-text">Y{{ $subject->year_level }} - S{{ $subject->semester }}</div>
                        </div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="meta-label">Section</div>
                            <div class="meta-text">{{ $subject->section ?: 'TBA' }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="class-card-footer">
                    <a href="{{ $attendanceMode ? route('teacher.qr', $subject->code) : route('teacher.classroom.show', $subject->code) }}" class="btn-manage-class" aria-label="{{ $attendanceMode ? 'Take attendance for ' : 'Open class ' }}{{ $subject->name }}">
                        {{ $attendanceMode ? 'Open QR attendance' : 'Open class' }} <i class="bi bi-arrow-right-short fs-5" aria-hidden="true"></i>
                    </a>
                    <a href="{{ $attendanceMode ? route('teacher.classroom.show', $subject->code) : route('teacher.qr', $subject->code) }}" class="teacher-class-secondary" aria-label="{{ $attendanceMode ? 'Open class ' : 'Take attendance for ' }}{{ $subject->name }}">{{ $attendanceMode ? 'View class details' : 'Take attendance' }}</a>
                </div>
            </div>
        @endforeach
    </div>
@endif
</div>
@endsection
