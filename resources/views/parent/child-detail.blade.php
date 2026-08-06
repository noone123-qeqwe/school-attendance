@extends('layouts.app')
@section('page-title', $child->name . ' â€” Attendance Detail')

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
            <div style="flex:1; min-width: 150px;">
                <label class="ent-label">Subject</label>
                <select name="subject" class="ent-input">
                    <option value="">All Subjects</option>
                    @foreach($subjects as $subj)
                        <option value="{{ $subj->code }}" {{ request('subject') == $subj->code ? 'selected' : '' }}>{{ $subj->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1; min-width: 120px;">
                <label class="ent-label">Status</label>
                <select name="status" class="ent-input">
                    <option value="">All</option>
                    <option value="Present" {{ request('status') == 'Present' ? 'selected' : '' }}>Present</option>
                    <option value="Late" {{ request('status') == 'Late' ? 'selected' : '' }}>Late</option>
                    <option value="Absent" {{ request('status') == 'Absent' ? 'selected' : '' }}>Absent</option>
                    <option value="Excused" {{ request('status') == 'Excused' ? 'selected' : '' }}>Excused</option>
                </select>
            </div>
            <div style="flex:1; min-width: 130px;">
                <label class="ent-label">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="ent-input">
            </div>
            <div style="flex:1; min-width: 130px;">
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
        <div class="ent-scroll-x" style="margin: -20px;">
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
