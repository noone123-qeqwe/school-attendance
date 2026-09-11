@extends('layouts.mobile-app')

@section('title', 'Attendance History - Smart Attendance')

@section('content')
<div class="mobile-history">
    {{-- Page Header --}}
    <div class="history-top-bar">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('mobile.home') }}" class="back-link" aria-label="Back to Home">
                <i class="bi bi-chevron-left"></i>
            </a>
            <div>
                <h1 class="page-title">Attendance History</h1>
                <p class="page-subtitle">{{ $stats['total'] }} total recorded classes</p>
            </div>
        </div>
        <button type="button" class="scan-shortcut-btn" onclick="if(typeof openStudentScanner==='function'){openStudentScanner('scan');}" aria-label="Scan QR Code">
            <i class="bi bi-qr-code-scan"></i>
        </button>
    </div>

    {{-- Stats Grid --}}
    <div class="stats-pills-grid">
        <div class="stat-pill stat-total">
            <span class="stat-num">{{ $stats['total'] }}</span>
            <span class="stat-lbl">Total</span>
        </div>
        <div class="stat-pill stat-present">
            <span class="stat-num">{{ $stats['present'] }}</span>
            <span class="stat-lbl">Present</span>
        </div>
        <div class="stat-pill stat-late">
            <span class="stat-num">{{ $stats['late'] }}</span>
            <span class="stat-lbl">Late</span>
        </div>
        <div class="stat-pill stat-absent">
            <span class="stat-num">{{ $stats['absent'] }}</span>
            <span class="stat-lbl">Absent</span>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="filter-scroll-wrapper">
        <div class="filter-tabs">
            <button type="button" class="filter-btn active" data-filter="all" onclick="filterHistory('all', this)">
                All ({{ $stats['total'] }})
            </button>
            <button type="button" class="filter-btn" data-filter="Present" onclick="filterHistory('Present', this)">
                <i class="bi bi-check-circle-fill text-success me-1"></i> Present ({{ $stats['present'] }})
            </button>
            <button type="button" class="filter-btn" data-filter="Late" onclick="filterHistory('Late', this)">
                <i class="bi bi-clock-fill text-warning me-1"></i> Late ({{ $stats['late'] }})
            </button>
            <button type="button" class="filter-btn" data-filter="Absent" onclick="filterHistory('Absent', this)">
                <i class="bi bi-x-circle-fill text-danger me-1"></i> Absent ({{ $stats['absent'] }})
            </button>
        </div>
    </div>

    {{-- Attendance Records List --}}
    @php
        $groupedRecords = [];
        foreach ($records as $rec) {
            $parsedDate = \Carbon\Carbon::parse($rec->date);
            $dateGroup = $parsedDate->isToday() 
                ? 'Today (' . $parsedDate->format('M j') . ')' 
                : ($parsedDate->isYesterday() 
                    ? 'Yesterday (' . $parsedDate->format('M j') . ')' 
                    : $parsedDate->format('F j, Y (l)'));
            $groupedRecords[$dateGroup][] = $rec;
        }
    @endphp

    <div class="history-list" id="historyList">
        @forelse($groupedRecords as $dateGroup => $dayRecords)
            <div class="history-date-group" data-group="{{ $dateGroup }}">
                <div class="group-header">
                    <span class="group-title">{{ $dateGroup }}</span>
                    <span class="group-count">{{ count($dayRecords) }} {{ count($dayRecords) === 1 ? 'class' : 'classes' }}</span>
                </div>

                @foreach($dayRecords as $record)
                    @php
                        $statusClass = match($record->status) {
                            'Present' => 'success',
                            'Late'    => 'warning',
                            'Absent'  => 'danger',
                            default   => 'secondary'
                        };
                        $statusIcon = match($record->status) {
                            'Present' => 'check-circle-fill',
                            'Late'    => 'clock-fill',
                            'Absent'  => 'x-circle-fill',
                            default   => 'circle'
                        };
                        $methodLabel = match($record->method) {
                            'qr'         => 'QR Scan',
                            'code'       => 'PIN Code',
                            'manual_gps' => 'GPS Check-in',
                            'manual'     => 'Teacher Verified',
                            default      => 'Verified'
                        };
                    @endphp

                    <div class="history-card" data-status="{{ $record->status }}">
                        <div class="card-status-indicator indicator-{{ $statusClass }}"></div>
                        
                        <div class="card-inner">
                            <div class="card-main">
                                <div class="subject-info">
                                    <h2 class="subject-name">{{ $record->subject->name ?? $record->subject_code }}</h2>
                                    <div class="subject-meta">
                                        <span class="subject-code-badge">{{ $record->subject_code }}</span>
                                        @if($record->subject && $record->subject->instructor)
                                            <span class="meta-dot">•</span>
                                            <span class="instructor-name">{{ is_string($record->subject->instructor) ? $record->subject->instructor : ($record->subject->instructor->name ?? 'Instructor') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="status-badge-wrap">
                                    @if($record->excused)
                                        <span class="status-badge badge-excused">
                                            <i class="bi bi-shield-check"></i> Excused
                                        </span>
                                    @else
                                        <span class="status-badge badge-{{ $statusClass }}">
                                            <i class="bi bi-{{ $statusIcon }}"></i> {{ $record->status }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="card-details">
                                <div class="detail-col">
                                    <span class="detail-lbl">Time In</span>
                                    <span class="detail-val">
                                        @if($record->time_in)
                                            <i class="bi bi-clock text-gold me-1"></i>{{ \Carbon\Carbon::parse($record->time_in)->format('g:i A') }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </span>
                                </div>

                                <div class="detail-col">
                                    <span class="detail-lbl">Method</span>
                                    <span class="detail-val">
                                        <i class="bi bi-shield-check text-muted me-1"></i>{{ $methodLabel }}
                                    </span>
                                </div>

                                @if($record->class)
                                <div class="detail-col">
                                    <span class="detail-lbl">Section</span>
                                    <span class="detail-val">{{ $record->class }}</span>
                                </div>
                                @endif
                            </div>

                            {{-- Actions for Absent or Late records --}}
                            @if($record->status === 'Absent' || $record->status === 'Late' || $record->excuseSubmission || $record->correction)
                            <div class="card-footer-actions">
                                @if($record->excuseSubmission)
                                    <span class="action-tag tag-excuse tag-{{ $record->excuseSubmission->status }}">
                                        <i class="bi bi-{{ $record->excuseSubmission->status === 'approved' ? 'check-circle' : ($record->excuseSubmission->status === 'rejected' ? 'x-circle' : 'hourglass-split') }}"></i>
                                        Excuse: {{ ucfirst($record->excuseSubmission->status) }}
                                    </span>
                                @elseif($record->status === 'Absent')
                                    <a href="{{ route('excuses.create', $record->id) }}" class="action-btn btn-excuse">
                                        <i class="bi bi-file-earmark-medical me-1"></i> Submit Excuse
                                    </a>
                                @endif

                                @if($record->correction)
                                    <span class="action-tag tag-appeal">
                                        <i class="bi bi-question-circle"></i> Appeal: {{ ucfirst($record->correction->status) }}
                                    </span>
                                @elseif(in_array($record->status, ['Late', 'Absent']))
                                    <a href="{{ route('attendance.records') }}?appeal={{ $record->id }}" class="action-btn btn-appeal">
                                        <i class="bi bi-pencil-square me-1"></i> Appeal
                                    </a>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="history-empty-state">
                <div class="empty-icon-wrap">
                    <i class="bi bi-calendar-x"></i>
                </div>
                <h3>No Attendance Records Found</h3>
                <p>You haven't recorded attendance for any classes yet. Scan a teacher's QR code or enter your 6-digit class code to get started.</p>
                <button type="button" class="empty-scan-btn" onclick="if(typeof openStudentScanner==='function'){openStudentScanner('scan');}">
                    <i class="bi bi-qr-code-scan me-2"></i> Scan Attendance Now
                </button>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('styles')
<style>
    .mobile-history {
        padding-bottom: 24px;
    }

    /* Top Bar */
    .history-top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .back-link {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: var(--bg-card);
        border: 1px solid rgba(207, 164, 111, 0.15);
        color: var(--gold-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .back-link:hover, .back-link:active {
        background: var(--bg-card-hover);
        color: #fff;
    }

    .page-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
        line-height: 1.2;
    }

    .page-subtitle {
        font-size: 13px;
        color: var(--text-muted);
        margin: 2px 0 0 0;
    }

    .scan-shortcut-btn {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
        color: var(--bg-dark);
        border: none;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(207, 164, 111, 0.35);
        cursor: pointer;
        transition: transform 0.15s ease;
    }

    .scan-shortcut-btn:active {
        transform: scale(0.92);
    }

    /* Stats Grid */
    .stats-pills-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-bottom: 18px;
    }

    .stat-pill {
        background: var(--bg-card);
        border: 1px solid rgba(207, 164, 111, 0.12);
        border-radius: 14px;
        padding: 12px 6px;
        text-align: center;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .stat-num {
        font-size: 20px;
        font-weight: 800;
        line-height: 1;
        color: var(--text-primary);
    }

    .stat-lbl {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.5px;
    }

    .stat-present .stat-num { color: #34d399; }
    .stat-late .stat-num    { color: #fbbf24; }
    .stat-absent .stat-num  { color: #f87171; }

    /* Filter Tabs */
    .filter-scroll-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 20px;
        padding-bottom: 4px;
    }

    .filter-scroll-wrapper::-webkit-scrollbar {
        display: none;
    }

    .filter-tabs {
        display: flex;
        gap: 8px;
        width: max-content;
    }

    .filter-btn {
        padding: 8px 16px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: var(--bg-card);
        color: var(--text-secondary);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .filter-btn.active {
        background: rgba(207, 164, 111, 0.2);
        border-color: rgba(207, 164, 111, 0.4);
        color: var(--gold-light);
    }

    /* History Groups */
    .history-date-group {
        margin-bottom: 24px;
    }

    .group-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding: 0 4px;
    }

    .group-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--gold-primary);
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .group-count {
        font-size: 12px;
        color: var(--text-muted);
        font-weight: 500;
    }

    /* History Card */
    .history-card {
        position: relative;
        background: var(--bg-card);
        border: 1px solid rgba(207, 164, 111, 0.1);
        border-radius: 18px;
        overflow: hidden;
        margin-bottom: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        transition: transform 0.15s ease, border-color 0.2s ease;
    }

    .card-status-indicator {
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        width: 5px;
    }

    .indicator-success   { background: #22c55e; }
    .indicator-warning   { background: #f59e0b; }
    .indicator-danger    { background: #ef4444; }
    .indicator-secondary { background: #64748b; }

    .card-inner {
        padding: 16px 16px 16px 20px;
    }

    .card-main {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 14px;
    }

    .subject-info {
        flex: 1;
    }

    .subject-name {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 6px 0;
        line-height: 1.3;
    }

    .subject-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        font-size: 12px;
        color: var(--text-muted);
    }

    .subject-code-badge {
        background: rgba(207, 164, 111, 0.15);
        color: var(--gold-light);
        padding: 2px 8px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 11px;
    }

    .meta-dot {
        opacity: 0.5;
    }

    .instructor-name {
        color: var(--text-secondary);
    }

    /* Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .badge-success {
        background: rgba(34, 197, 94, 0.15);
        color: #4ade80;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .badge-warning {
        background: rgba(245, 158, 11, 0.15);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .badge-danger {
        background: rgba(239, 68, 68, 0.15);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .badge-excused {
        background: rgba(14, 165, 233, 0.15);
        color: #38bdf8;
        border: 1px solid rgba(14, 165, 233, 0.3);
    }

    /* Details Grid */
    .card-details {
        display: flex;
        gap: 20px;
        padding-top: 12px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .detail-col {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .detail-lbl {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.4px;
    }

    .detail-val {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        align-items: center;
    }

    .text-gold {
        color: var(--gold-primary);
    }

    /* Actions */
    .card-footer-actions {
        display: flex;
        gap: 8px;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px dashed rgba(255, 255, 255, 0.05);
        flex-wrap: wrap;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-excuse {
        background: rgba(128, 0, 0, 0.3);
        color: #fca5a5;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .btn-appeal {
        background: rgba(207, 164, 111, 0.15);
        color: var(--gold-light);
        border: 1px solid rgba(207, 164, 111, 0.25);
    }

    .action-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
    }

    .tag-excuse.tag-approved { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
    .tag-excuse.tag-pending  { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
    .tag-excuse.tag-rejected { background: rgba(239, 68, 68, 0.2); color: #f87171; }
    .tag-appeal { background: rgba(207, 164, 111, 0.15); color: var(--gold-light); }

    /* Empty State */
    .history-empty-state {
        text-align: center;
        padding: 48px 20px;
    }

    .empty-icon-wrap {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: rgba(207, 164, 111, 0.1);
        border: 1px solid rgba(207, 164, 111, 0.2);
        color: var(--gold-primary);
        font-size: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
    }

    .history-empty-state h3 {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .history-empty-state p {
        font-size: 14px;
        color: var(--text-muted);
        line-height: 1.5;
        max-width: 320px;
        margin: 0 auto 20px;
    }

    .empty-scan-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
        color: var(--bg-dark);
        border: none;
        padding: 12px 24px;
        border-radius: 14px;
        font-weight: 700;
        font-size: 14px;
        box-shadow: 0 4px 16px rgba(207, 164, 111, 0.3);
        cursor: pointer;
    }
</style>
@endpush

@push('scripts')
<script>
    function filterHistory(status, btn) {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const cards = document.querySelectorAll('.history-card');
        const groups = document.querySelectorAll('.history-date-group');

        cards.forEach(card => {
            if (status === 'all' || card.dataset.status === status) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });

        // Hide empty groups
        groups.forEach(group => {
            const visibleCards = group.querySelectorAll('.history-card:not([style*="display: none"])');
            if (visibleCards.length === 0) {
                group.style.display = 'none';
            } else {
                group.style.display = 'block';
            }
        });
    }
</script>
@endpush
