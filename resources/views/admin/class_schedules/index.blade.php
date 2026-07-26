@extends('layouts.admin_premium')

@section('title', 'Class Schedules')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Class Schedules</h1>
        <p class="saas-text-muted" style="margin:0;">Manage timetables, assigned rooms, and instructor loads.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button class="saas-btn saas-btn-primary" onclick="openModal('addScheduleModal')">
            <i class="bi bi-calendar-plus"></i> Add Schedule
        </button>
    </div>
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <div class="saas-card-header" style="gap:16px; flex-wrap:wrap;">
        <div class="saas-search" style="width:250px;">
            <i class="bi bi-search"></i>
            <input type="text" class="saas-search-input" placeholder="Search by subject or teacher...">
        </div>
        
        <div style="display:flex; gap:12px; align-items:center;">
            <select class="saas-input saas-select" style="width:140px; padding:6px 30px 6px 12px;">
                <option value="">Day (All)</option>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
                <option value="Saturday">Saturday</option>
            </select>
            
            <button class="saas-btn saas-btn-secondary" style="padding:6px 12px;">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </div>
    
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" style="accent-color:var(--saas-primary);"></th>
                    <th>Subject</th>
                    <th>Section</th>
                    <th>Instructor</th>
                    <th>Schedule (Day & Time)</th>
                    <th>Room</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $schedule)
                <tr>
                    <td><input type="checkbox" style="accent-color:var(--saas-primary);"></td>
                    <td>
                        <span style="font-weight:600; font-family:monospace; color:var(--saas-gold);">{{ $schedule->subject->code ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <span class="saas-badge saas-badge-info">{{ $schedule->section->name ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <i class="bi bi-person saas-text-muted"></i>
                            <span style="font-size:0.85rem;">{{ $schedule->teacher->name ?? 'Unassigned' }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:500;">{{ $schedule->day_of_week }}</div>
                        <div class="saas-text-muted" style="font-size:0.75rem;">
                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }} - 
                            {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                        </div>
                    </td>
                    <td>
                        <span class="saas-badge saas-badge-default"><i class="bi bi-door-open" style="margin-right:4px;"></i> {{ $schedule->room }}</span>
                    </td>
                    <td style="text-align:right;">
                        <button class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="saas-btn saas-btn-secondary" style="padding:4px 8px; color:var(--saas-danger);" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-calendar2-week saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No schedules found</div>
                        <p class="saas-text-muted" style="margin-bottom:20px; max-width:400px; margin-inline:auto;">Assign subjects to teachers, timeslots, and rooms.</p>
                        <button class="saas-btn saas-btn-primary" onclick="openModal('addScheduleModal')">
                            <i class="bi bi-calendar-plus"></i> Add Schedule
                        </button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($schedules->hasPages())
    <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:space-between; align-items:center;">
        <div class="saas-text-muted">
            Showing {{ $schedules->firstItem() ?? 0 }} to {{ $schedules->lastItem() ?? 0 }} of {{ $schedules->total() }} results
        </div>
        <div>
            {{ $schedules->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Add Schedule Modal -->
<div id="addScheduleModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:100; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s;">
    <div class="saas-card" style="width:100%; max-width:550px; transform:scale(0.95); transition:transform 0.2s;" id="addScheduleCard">
        <div class="saas-card-header">
            <div class="saas-heading saas-heading-sm">Add Class Schedule</div>
            <button onclick="closeModal('addScheduleModal')" style="background:none; border:none; color:var(--saas-text-muted); cursor:pointer;"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.class-schedules.store') }}" method="POST">
            @csrf
            <div class="saas-card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div class="saas-form-group" style="margin:0;">
                        <label class="saas-label">Subject</label>
                        <select name="subject_id" class="saas-input saas-select" required>
                            <option value="">Select Subject...</option>
                            <!-- Subject options would go here -->
                        </select>
                    </div>
                    <div class="saas-form-group" style="margin:0;">
                        <label class="saas-label">Section</label>
                        <select name="section_id" class="saas-input saas-select" required>
                            <option value="">Select Section...</option>
                            <!-- Section options would go here -->
                        </select>
                    </div>
                </div>
                
                <div class="saas-form-group">
                    <label class="saas-label">Instructor</label>
                    <select name="teacher_id" class="saas-input saas-select" required>
                        <option value="">Select Instructor...</option>
                        <!-- Teacher options would go here -->
                    </select>
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div class="saas-form-group" style="margin:0;">
                        <label class="saas-label">Day</label>
                        <select name="day_of_week" class="saas-input saas-select" required>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>
                    <div class="saas-form-group" style="margin:0;">
                        <label class="saas-label">Start Time</label>
                        <input type="time" name="start_time" class="saas-input" required>
                    </div>
                    <div class="saas-form-group" style="margin:0;">
                        <label class="saas-label">End Time</label>
                        <input type="time" name="end_time" class="saas-input" required>
                    </div>
                </div>
                
                <div class="saas-form-group" style="margin-bottom:0;">
                    <label class="saas-label">Room</label>
                    <input type="text" name="room" class="saas-input" placeholder="e.g. Rm 301, ComLab 2" required>
                </div>
            </div>
            <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:flex-end; gap:12px; background:rgba(0,0,0,0.2);">
                <button type="button" class="saas-btn saas-btn-secondary" onclick="closeModal('addScheduleModal')">Cancel</button>
                <button type="submit" class="saas-btn saas-btn-primary">Save Schedule</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        const card = modal.querySelector('.saas-card');
        modal.style.display = 'flex';
        void modal.offsetWidth;
        modal.style.opacity = '1';
        card.style.transform = 'scale(1)';
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        const card = modal.querySelector('.saas-card');
        modal.style.opacity = '0';
        card.style.transform = 'scale(0.95)';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 200);
    }
</script>
@endpush
