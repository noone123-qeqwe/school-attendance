<style>
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
        #attendance-records .ent-grid-5 { grid-template-columns: 1fr 1fr; gap: 12px; }
        #attendance-records .ent-grid-5 > :last-child { grid-column: span 2; }
    }
</style>

<section class="parent-page" id="attendance-records" aria-labelledby="attendanceRecordsTitle" tabindex="-1">
    <div class="parent-page-heading">
        <div>
            <h2 id="attendanceRecordsTitle">Detailed attendance records</h2>
            <p class="ent-text-muted m-0">{{ $child->name }}</p>
        </div>
        <a class="ent-btn ent-btn-ghost" href="{{ route('parent.child.report', $child) }}">
            <i class="bi bi-download" aria-hidden="true"></i> Download PDF
        </a>
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
        <form method="GET" action="{{ route('parent.calendar') }}#attendance-records" class="calendar-record-filters">
            <input type="hidden" name="child_id" value="{{ $child->id }}">
            <input type="hidden" name="year" value="{{ $year }}" data-calendar-month="year">
            <input type="hidden" name="month" value="{{ $month }}" data-calendar-month="month">
            <div class="filter-group">
                <label class="ent-label" for="parentRecordSubject">Subject</label>
                <select name="subject" id="parentRecordSubject" class="ent-input">
                    <option value="">All Subjects</option>
                    @foreach($subjects as $subj)
                        <option value="{{ $subj->code }}" @selected(($filters['subject'] ?? '') === $subj->code)>{{ $subj->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label class="ent-label" for="parentRecordStatus">Status</label>
                <select name="status" id="parentRecordStatus" class="ent-input">
                    <option value="">All statuses</option>
                    @foreach(['Present', 'Late', 'Absent', 'Escaped', 'Excused'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label class="ent-label" for="parentRecordFrom">From</label>
                <input type="date" id="parentRecordFrom" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="ent-input">
            </div>
            <div class="filter-group">
                <label class="ent-label" for="parentRecordTo">To</label>
                <input type="date" id="parentRecordTo" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="ent-input">
            </div>
            <div class="calendar-record-filter-actions">
                <button type="submit" class="ent-btn ent-btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                @if(array_filter($filters))
                    <a href="{{ route('parent.calendar', ['child_id' => $child->id, 'year' => $year, 'month' => $month]) }}#attendance-records" class="ent-btn ent-btn-ghost" data-calendar-month-link>Clear</a>
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
                            @if($record->excused || $record->status === 'Excused')
                                <span class="ent-badge ent-badge-info">Excused</span>
                            @elseif($record->status === 'Present')
                                <span class="ent-badge ent-badge-success">Present</span>
                            @elseif($record->status === 'Late')
                                <span class="ent-badge ent-badge-warning">Late</span>
                            @else
                                <span class="ent-badge ent-badge-danger">{{ $record->status }}</span>
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
                                <span class="ent-text-muted">&mdash;</span>
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
                            @if($record->excused || $record->status === 'Excused')
                                <span class="ent-badge ent-badge-info">Excused</span>
                            @elseif($record->status === 'Present')
                                <span class="ent-badge ent-badge-success">Present</span>
                            @elseif($record->status === 'Late')
                                <span class="ent-badge ent-badge-warning">Late</span>
                            @else
                                <span class="ent-badge ent-badge-danger">{{ $record->status }}</span>
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

        @else
        <div class="ent-empty" style="padding: 40px;">
            <div class="ent-empty-icon" style="width:64px;height:64px;font-size:2rem; margin-bottom:16px;">
                <i class="bi bi-calendar-x"></i>
            </div>
            <div class="ent-empty-text">{{ $records->total() ? 'No records on this page.' : 'No records match your filters.' }}</div>
        </div>
        @endif
        <div class="mt-3" data-calendar-month-pagination>{{ $records->links() }}</div>
    </x-card>
</section>
