@extends('layouts.app')
@section('page-title', $child->name . ' â€” Attendance Detail')

@section('content')
<style>
    .child-header-glow {
        background: linear-gradient(135deg, rgba(207, 164, 111, 0.15) 0%, rgba(128, 0, 0, 0.05) 100%);
        border: 1px solid rgba(207, 164, 111, 0.2);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2), inset 0 1px 1px rgba(255,255,255,0.05);
        border-radius: 16px;
        padding: 24px;
        position: relative;
        overflow: hidden;
    }
    .filter-group { flex: 1; min-width: 150px; }
    
    .mobile-timeline {
        display: flex; flex-direction: column; gap: 16px;
    }
    .timeline-item {
        display: flex; gap: 12px; background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 12px;
        transition: transform 0.2s;
    }
    .timeline-item:active { transform: scale(0.98); }
    .timeline-date {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        width: 48px; height: 52px; background: rgba(207,164,111,0.1); border-radius: 10px;
        color: var(--gold); border: 1px solid rgba(207,164,111,0.2); flex-shrink: 0;
    }
    .timeline-date .day { font-size: 1.1rem; font-weight: 700; line-height: 1; }
    .timeline-date .month { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8; }
    .timeline-content { flex-grow: 1; min-width: 0; }
    .timeline-content .subject-name { font-weight: 600; color: #f3e7cd; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; display: inline-block; }
    .timeline-content .time-in { font-size: 0.8rem; color: #8f826f; font-family: monospace; }
    
    @media (max-width: 768px) {
        .filter-group { flex-basis: 100%; min-width: 100%; }
        .ent-grid-5 { grid-template-columns: 1fr 1fr; }
        .ent-grid-5 > :last-child { grid-column: span 2; }
    }
</style>

<div class="p-4">
    {{-- Back Link --}}
    <a href="{{ route('parent.dashboard') }}" class="student-back-link">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    {{-- Child Header --}}
    <div class="child-header-glow d-flex align-items-center gap-3 mb-4">
        <img src="{{ $child->profile_image ? (str_starts_with($child->profile_image, 'http') ? $child->profile_image : asset('storage/'.$child->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($child->name).'&background=800000&color=fff' }}"
             alt="{{ $child->name }}" class="rounded-circle"
             style="width: 64px; height: 64px; object-fit: cover; border: 2px solid rgba(207,164,111,0.4);">
        <div>
            <h3 style="color: #f3e7cd; font-weight: 800; margin: 0;">{{ $child->name }}</h3>
            <small style="color: #b39b82;">{{ $child->student_number }} &bull; {{ $child->course }} â€” Year {{ $child->year_level }}, Semester {{ $child->semester }}</small>
        </div>
    </div>

    {{-- Stats --}}
    <div class="ent-grid ent-grid-5 ent-mb-lg">
        <x-card type="kpi" accent="gold" label="Attendance Rate" value="{{ $rate }}%" icon="bi bi-graph-up" />
        <x-card type="kpi" accent="info" label="Total Records" value="{{ $total }}" icon="bi bi-file-earmark-text" />
        <x-card type="kpi" accent="success" label="Present" value="{{ $present }}" icon="bi bi-check-circle" />
        <x-card type="kpi" accent="warning" label="Late" value="{{ $late }}" icon="bi bi-clock" />
        <x-card type="kpi" accent="danger" label="Absent" value="{{ $absent }}" icon="bi bi-x-circle" />
    </div>

    {{-- Filters --}}
    <x-card type="section" class="ent-mb-lg">
        <form method="GET" action="{{ route('parent.child', $child) }}" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
            <div class="filter-group">
                <label class="ent-label">Subject</label>
                <select name="subject" class="ent-input">
                    <option value="">All Subjects</option>
                    @foreach($subjects as $subj)
                        <option value="{{ $subj->code }}" {{ request('subject') == $subj->code ? 'selected' : '' }}>{{ $subj->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label class="ent-label">Status</label>
                <select name="status" class="ent-input">
                    <option value="">All</option>
                    <option value="Present" {{ request('status') == 'Present' ? 'selected' : '' }}>Present</option>
                    <option value="Late" {{ request('status') == 'Late' ? 'selected' : '' }}>Late</option>
                    <option value="Absent" {{ request('status') == 'Absent' ? 'selected' : '' }}>Absent</option>
                    <option value="Excused" {{ request('status') == 'Excused' ? 'selected' : '' }}>Excused</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="ent-label">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="ent-input">
            </div>
            <div class="filter-group">
                <label class="ent-label">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="ent-input">
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="ent-btn ent-btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                @if(request()->hasAny(['subject', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('parent.child', $child) }}" class="ent-btn ent-btn-ghost text-danger">Clear</a>
                @endif
            </div>
        </form>
    </x-card>

    {{-- Records Table --}}
    <x-card type="section" title="Attendance Records">
        <x-slot:headerActions>
            <span style="color: var(--ent-text-muted); font-size: 0.85rem;">{{ $records->total() }} records</span>
        </x-slot:headerActions>
        
        @if($records->count() > 0)
        <!-- Desktop Table -->
        <div class="ent-scroll-x d-none d-md-block" style="margin: -20px;">
            <table class="ent-table" style="min-width: 600px; margin-bottom: 0;">
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
                        <td data-label="Date">
                            <span style="font-weight: 500;">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</span>
                        </td>
                        <td data-label="Day">
                            <span class="ent-text-muted">{{ \Carbon\Carbon::parse($record->date)->format('l') }}</span>
                        </td>
                        <td data-label="Subject">
                            <span style="font-weight: 500; color: var(--ent-text);">{{ $record->subject->name ?? $record->subject_code }}</span>
                        </td>
                        <td data-label="Status">
                            @if($record->excused)
                                <span class="ent-badge ent-badge-info">Excused</span>
                            @elseif($record->status === 'Present')
                                <span class="ent-badge ent-badge-success">Present</span>
                            @elseif($record->status === 'Late')
                                <span class="ent-badge ent-badge-warning">Late</span>
                            @else
                                <span class="ent-badge ent-badge-danger">Absent</span>
                            @endif
                        </td>
                        <td data-label="Time In">
                            <span style="font-family: monospace;">{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '--' }}</span>
                        </td>
                        <td data-label="Action">
                            @if($record->status === 'Absent' && !$record->excused)
                                <a href="{{ route('parent.child.excuse', [$child, $record]) }}" class="ent-btn ent-btn-xs ent-btn-ghost text-primary">
                                    <i class="bi bi-pencil-square"></i> Excuse
                                </a>
                            @else
                                <span class="ent-text-muted">â€”</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="d-block d-md-none">
            <div class="mobile-timeline">
                @foreach($records as $record)
                <div class="timeline-item">
                    <div class="timeline-date">
                        <span class="day">{{ \Carbon\Carbon::parse($record->date)->format('d') }}</span>
                        <span class="month">{{ \Carbon\Carbon::parse($record->date)->format('M') }}</span>
                    </div>
                    <div class="timeline-content">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="subject-name">{{ $record->subject->name ?? $record->subject_code }}</span>
                            @if($record->excused)
                                <span class="ent-badge ent-badge-info">Excused</span>
                            @elseif($record->status === 'Present')
                                <span class="ent-badge ent-badge-success">Present</span>
                            @elseif($record->status === 'Late')
                                <span class="ent-badge ent-badge-warning">Late</span>
                            @else
                                <span class="ent-badge ent-badge-danger">Absent</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-end mt-2">
                            <div class="time-in">
                                <i class="bi bi-clock me-1"></i>{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '--' }}
                            </div>
                            @if($record->status === 'Absent' && !$record->excused)
                                <a href="{{ route('parent.child.excuse', [$child, $record]) }}" class="ent-btn ent-btn-xs" style="background: rgba(56,189,248,0.1); color: #38bdf8; border: 1px solid rgba(56,189,248,0.3); padding: 4px 10px;">
                                    <i class="bi bi-pencil-square"></i> Excuse
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Pagination --}}
        <div style="border-top:1px solid var(--ent-border); padding-top:16px; margin-top:16px;">
            {{ $records->links() }}
        </div>
        @else
        <div class="ent-empty" style="padding: 40px;">
            <div class="ent-empty-icon" style="width:64px;height:64px;font-size:2rem; margin-bottom:16px;">
                <i class="bi bi-calendar-x"></i>
            </div>
            <div class="ent-empty-text">No records match your filters.</div>
        </div>
        @endif
    </x-card>
</div>
@endsection
