@extends('parent.layout')
@section('page-title', $child->name . ' — Attendance Detail')

@section('content')
<style>
    /* Premium Header Glow */
    .child-header-glow {
        background: linear-gradient(135deg, rgba(207, 164, 111, 0.15) 0%, rgba(128, 0, 0, 0.05) 100%);
        border: 1px solid rgba(207, 164, 111, 0.2);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2), inset 0 1px 1px rgba(255,255,255,0.05);
        border-radius: 16px;
        padding: 24px;
        position: relative;
        overflow: hidden;
    }
    
    /* Stat Box Hover */
    .adm-stat {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        background: rgba(255,235,190,0.03) !important;
        border: 1px solid rgba(255,215,145,0.05);
        border-radius: 16px;
        padding: 16px;
        position: relative;
        overflow: hidden;
    }
    .adm-stat:hover {
        transform: translateY(-3px) scale(1.02) !important;
        background: rgba(255,235,190,0.06) !important;
        border-color: rgba(207, 164, 111, 0.25) !important;
        box-shadow: 0 10px 20px rgba(0,0,0,0.2) !important;
    }

    /* Filters / Form Card Glassmorphism */
    .adm-card {
        background: rgba(26, 17, 13, 0.6) !important;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(207, 164, 111, 0.15) !important;
        transition: box-shadow 0.3s ease !important;
        border-radius: 20px !important;
    }
    
    /* Action Buttons */
    .adm-btn {
        transition: all 0.2s ease !important;
        border-radius: 8px !important;
    }
    .adm-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(207, 164, 111, 0.2); }
    
    /* Table Rows Hover */
    .adm-table tbody tr { transition: all 0.2s ease; border-bottom: 1px solid rgba(255,215,145,0.04); }
    .adm-table tbody tr:hover {
        background: rgba(207, 164, 111, 0.04) !important;
        transform: scale(1.002);
    }
</style>

<div class="p-4">
    {{-- Back Link --}}
    <a href="{{ route('parent.dashboard') }}" class="student-back-link">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    {{-- Child Header --}}
    <div class="child-header-glow d-flex align-items-center gap-3 mb-4">
        <img src="{{ $child->profile_image ? asset('storage/' . $child->profile_image) : 'https://ui-avatars.com/api/?name='.urlencode($child->name).'&background=800000&color=fff' }}"
             alt="{{ $child->name }}" class="rounded-circle"
             style="width: 64px; height: 64px; object-fit: cover; border: 2px solid rgba(207,164,111,0.4);">
        <div>
            <h3 style="color: #f3e7cd; font-weight: 800; margin: 0;">{{ $child->name }}</h3>
            <small style="color: #b39b82;">{{ $child->student_number }} &bull; {{ $child->course }} — Year {{ $child->year_level }}, Semester {{ $child->semester }}</small>
        </div>
    </div>

    {{-- Stats --}}
    <div class="adm-stats" style="grid-template-columns: repeat(5, 1fr); margin-bottom: 24px;">
        <div class="adm-stat" style="text-align: center;">
            <div class="adm-stat-val" style="color: #cfa46f;">{{ $rate }}%</div>
            <div class="adm-stat-lbl">Attendance Rate</div>
        </div>
        <div class="adm-stat" style="text-align: center;">
            <div class="adm-stat-val">{{ $total }}</div>
            <div class="adm-stat-lbl">Total Records</div>
        </div>
        <div class="adm-stat" style="text-align: center;">
            <div class="adm-stat-val" style="color: #66bb6a;">{{ $present }}</div>
            <div class="adm-stat-lbl">Present</div>
        </div>
        <div class="adm-stat" style="text-align: center;">
            <div class="adm-stat-val" style="color: #ffa726;">{{ $late }}</div>
            <div class="adm-stat-lbl">Late</div>
        </div>
        <div class="adm-stat" style="text-align: center;">
            <div class="adm-stat-val" style="color: #ef5350;">{{ $absent }}</div>
            <div class="adm-stat-lbl">Absent</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="adm-card" style="margin-bottom: 24px;">
        <div class="adm-card-body" style="padding: 16px 20px;">
            <form method="GET" action="{{ route('parent.child', $child) }}" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.72rem; font-weight: 600; color: #b39b82; text-transform: uppercase;">Subject</label>
                    <select name="subject" class="tch-input" style="min-width: 160px;">
                        <option value="">All Subjects</option>
                        @foreach($subjects as $subj)
                            <option value="{{ $subj->code }}" {{ request('subject') == $subj->code ? 'selected' : '' }}>{{ $subj->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.72rem; font-weight: 600; color: #b39b82; text-transform: uppercase;">Status</label>
                    <select name="status" class="tch-input" style="min-width: 120px;">
                        <option value="">All</option>
                        <option value="Present" {{ request('status') == 'Present' ? 'selected' : '' }}>Present</option>
                        <option value="Late" {{ request('status') == 'Late' ? 'selected' : '' }}>Late</option>
                        <option value="Absent" {{ request('status') == 'Absent' ? 'selected' : '' }}>Absent</option>
                        <option value="Excused" {{ request('status') == 'Excused' ? 'selected' : '' }}>Excused</option>
                    </select>
                </div>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.72rem; font-weight: 600; color: #b39b82; text-transform: uppercase;">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="tch-input">
                </div>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.72rem; font-weight: 600; color: #b39b82; text-transform: uppercase;">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="tch-input">
                </div>
                <button type="submit" class="adm-btn adm-btn-primary" style="font-size: 0.82rem;"><i class="bi bi-funnel"></i> Filter</button>
                <a href="{{ route('parent.child', $child) }}" class="adm-btn adm-btn-ghost" style="font-size: 0.82rem;">Clear</a>
            </form>
        </div>
    </div>

    {{-- Records Table --}}
    <div class="adm-card">
        <div class="adm-card-head">
            <div class="adm-card-title">
                <div class="adm-card-icon"><i class="bi bi-journal-text"></i></div>
                Attendance Records
            </div>
            <span style="color: #b39b82; font-size: 0.82rem;">{{ $records->total() }} records</span>
        </div>
        <div class="adm-card-body" style="padding: 0;">
            @if($records->count() > 0)
            <div class="table-responsive">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Time In</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $record)
                        <tr>
                            <td>
                                <span class="attendance-date">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</span>
                            </td>
                            <td>
                                <span class="attendance-day">{{ \Carbon\Carbon::parse($record->date)->format('l') }}</span>
                            </td>
                            <td>
                                <span class="attendance-subject">{{ $record->subject->name ?? $record->subject_code }}</span>
                            </td>
                            <td>
                                @if($record->excused)
                                    <span class="badge-present" style="background: rgba(66,165,245,0.15); color: #42a5f5;">Excused</span>
                                @elseif($record->status === 'Present')
                                    <span class="badge-present">Present</span>
                                @elseif($record->status === 'Late')
                                    <span class="badge-late">Late</span>
                                @else
                                    <span class="badge-absent">Absent</span>
                                @endif
                            </td>
                            <td>
                                <span class="attendance-time">{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '--' }}</span>
                            </td>
                            <td>
                                @if($record->status === 'Absent' && !$record->excused)
                                    <a href="{{ route('parent.child.excuse', [$child, $record]) }}" class="adm-btn adm-btn-ghost" style="font-size: 0.75rem; padding: 5px 10px;">
                                        <i class="bi bi-pencil-square"></i> Excuse
                                    </a>
                                @else
                                    <span style="color: #8f826f;">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div style="padding: 16px 20px; border-top: 1px solid rgba(255,215,145,0.08);">
                {{ $records->links() }}
            </div>
            @else
            <div class="empty-state" style="padding: 40px;">
                <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                <span>No records match your filters.</span>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .adm-stats { grid-template-columns: repeat(3, 1fr) !important; }
}
@media (max-width: 480px) {
    .adm-stats { grid-template-columns: repeat(2, 1fr) !important; }
}
</style>
@endsection
