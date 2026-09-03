@extends('layouts.app')

@section('content')
<style>
    .records-page { max-width: 960px; margin: 0 auto; padding-bottom: 40px; }

    /* Page header */
    .page-header { margin-bottom: 24px; }
    .page-header-title { font-size: 1.6rem; font-weight: 800; color: #fde68a; letter-spacing: -0.3px; }
    .page-header-sub { font-size: 0.9rem; color: rgba(245,234,215,0.6); margin-top: 4px; }

    /* Summary chips */
    .summary-chips { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
    .summary-chip {
        flex: 1; min-width: 120px;
        background: rgba(35,21,27,0.7); 
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 18px; padding: 18px 20px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        backdrop-filter: blur(10px);
    }
    .summary-chip:hover { 
        transform: translateY(-3px); 
        box-shadow: 0 12px 32px rgba(0,0,0,0.3);
        border-color: rgba(253,230,138,0.2);
    }
    .summary-chip-value { font-size: 1.8rem; font-weight: 800; line-height: 1; margin-bottom: 4px; }
    .summary-chip-label { font-size: 0.72rem; font-weight: 700; color: rgba(245,234,215,0.5); text-transform: uppercase; letter-spacing: 0.5px; }

    /* Table card */
    .table-card {
        background: rgba(24,12,14,0.7); 
        border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.06);
        box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        overflow: hidden;
        backdrop-filter: blur(12px);
    }
    .table-card-header {
        padding: 20px 24px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 14px;
        background: rgba(255,255,255,0.02);
    }
    .table-card-title {
        font-size: 1rem; font-weight: 700; color: #f8e7d3;
        display: flex; align-items: center; gap: 12px;
    }
    .table-title-icon {
        width: 36px; height: 36px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        background: rgba(216,179,92,0.14); color: var(--gold);
    }

    /* Filter tabs */
    .filter-tabs { display: flex; gap: 8px; }
    .filter-tab {
        padding: 8px 16px; border-radius: 10px;
        font-size: 0.78rem; font-weight: 600;
        border: 1px solid rgba(255,255,255,0.08); 
        background: rgba(255,255,255,0.03);
        color: rgba(245,234,215,0.6); cursor: pointer; transition: all 0.2s;
    }
    .filter-tab:hover { background: rgba(255,255,255,0.08); color: #f8e7d3; }
    .filter-tab.active { background: rgba(216,179,92,0.15); border-color: rgba(216,179,92,0.3); color: #fde68a; }

    /* Empty state */
    .empty-state { text-align: center; padding: 60px 20px; color: rgba(245,234,215,0.5); }
    .empty-state i { font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 12px; }
    .empty-state p { font-size: 0.95rem; margin: 0; font-weight: 500; }

    /* Back btn */
    .back-btn {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 0.85rem; font-weight: 600; color: rgba(245,234,215,0.8);
        text-decoration: none; padding: 8px 16px;
        border: 1px solid rgba(255,255,255,0.1); border-radius: 12px;
        background: rgba(255,255,255,0.04); transition: all 0.2s;
        margin-bottom: 24px;
    }
    .back-btn:hover { color: #fde68a; border-color: rgba(253,230,138,0.3); background: rgba(253,230,138,0.05); transform: translateX(-4px); }

    /* Records Container - Mobile-first Card Layout */
    .records-container {
        display: flex;
        flex-direction: column;
        padding: 16px;
        gap: 12px;
    }
    
    .record-card {
        background: rgba(255,255,255,0.02);
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.05);
        overflow: hidden;
        transition: all 0.2s ease;
    }
    .record-card:hover {
        transform: translateY(-2px);
        background: rgba(255,255,255,0.04);
        border-color: rgba(255,255,255,0.1);
    }
    
    .record-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 18px 20px 14px;
        border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    
    .record-subject h4 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #f8e7d3;
        line-height: 1.3;
    }
    
    .record-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 6px;
    }
    
    .record-date {
        font-size: 0.85rem;
        font-weight: 600;
        color: rgba(245,234,215,0.55);
    }
    
    .record-day {
        font-size: 0.75rem;
        font-weight: 700;
        color: rgba(245,234,215,0.5);
        background: rgba(255,255,255,0.06);
        padding: 4px 10px;
        border-radius: 12px;
    }
    
    .record-status {
        flex-shrink: 0;
    }
    
    .record-body {
        padding: 16px 20px;
    }
    
    .record-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }
    
    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .detail-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: rgba(245,234,215,0.4);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .detail-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #f8e7d3;
    }
    
    .record-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding-top: 12px;
        border-top: 1px dashed rgba(255,255,255,0.06);
    }
    
    /* Update status badges for cards */
    .status-badge {
        display: inline-flex; 
        align-items: center; 
        gap: 6px;
        padding: 6px 14px; 
        border-radius: 12px;
        font-size: 0.75rem; 
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-badge::before { 
        content: ''; 
        width: 6px; 
        height: 6px; 
        border-radius: 50%; 
    }
    
    .badge-present { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25); }
    .badge-present::before { background: #4ade80; box-shadow: 0 0 6px #4ade80; }
    
    .badge-late { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.25); }
    .badge-late::before { background: #fbbf24; box-shadow: 0 0 6px #fbbf24; }
    
    .badge-absent { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.25); }
    .badge-absent::before { background: #f87171; box-shadow: 0 0 6px #f87171; }
    
    .badge-excused { background: rgba(216,179,92,0.15); color: #fde68a; border: 1px solid rgba(216,179,92,0.25); }
    .badge-excused::before { background: #fde68a; box-shadow: 0 0 6px #fde68a; }

    /* Excuse button and status */
    .excuse-btn {
        display: inline-flex; 
        align-items: center; 
        gap: 8px;
        padding: 8px 16px; 
        border-radius: 12px;
        font-size: 0.8rem; 
        font-weight: 700;
        background: linear-gradient(135deg, rgba(216,179,92,0.15), rgba(216,179,92,0.05)) !important;
        color: #fde68a !important;
        border: 1px solid rgba(216,179,92,0.3) !important;
        cursor: pointer; 
        transition: all 0.2s;
    }
    .excuse-btn:hover { 
        background: rgba(216,179,92,0.2) !important; 
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(216,179,92,0.15);
    }
    
    /* Update excuse status for cards */
    .excuse-status {
        display: inline-flex; 
        align-items: center; 
        gap: 6px;
        padding: 6px 14px; 
        border-radius: 12px;
        font-size: 0.75rem; 
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .excuse-pending { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.25); }
    .excuse-approved { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25); }
    .excuse-rejected { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.25); }

    /* Modal styles */
    .modal-overlay {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.7); z-index: 9999;
        display: none; align-items: center; justify-content: center;
        padding: 20px; backdrop-filter: blur(8px);
    }
    .modal-overlay.active { display: flex; }
    
    .modal-content {
        background: #180c0e; border-radius: 24px;
        border: 1px solid rgba(255,255,255,0.1);
        max-width: 500px; width: 100%;
        box-shadow: 0 24px 64px rgba(0,0,0,0.5);
        animation: modalIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        overflow: hidden;
    }
    
    @keyframes modalIn {
        from { transform: scale(0.92) translateY(20px); opacity: 0; }
        to   { transform: scale(1) translateY(0);    opacity: 1; }
    }
    
    .modal-header {
        padding: 24px 28px 20px; border-bottom: 1px solid rgba(255,255,255,0.06);
        display: flex; align-items: center; justify-content: space-between;
        background: rgba(255,255,255,0.02);
    }
    .modal-title {
        font-size: 1.1rem; font-weight: 800; color: #fde68a;
    }
    .modal-close {
        background: rgba(255,255,255,0.06); border: none; border-radius: 10px;
        width: 34px; height: 34px; cursor: pointer; font-size: 1.1rem;
        display: flex; align-items: center; justify-content: center;
        color: rgba(245,234,215,0.6); transition: all 0.2s;
    }
    .modal-close:hover { background: rgba(255,255,255,0.1); color: white; transform: rotate(90deg); }
    
    .modal-body {
        padding: 28px;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    .form-label {
        display: block; font-size: 0.8rem; font-weight: 700;
        color: rgba(245,234,215,0.7); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .form-input, .form-textarea, .form-select {
        width: 100%; padding: 14px 18px; 
        border: 1px solid rgba(255,255,255,0.1); border-radius: 14px;
        font-size: 0.9rem; font-family: 'Inter', sans-serif;
        background: rgba(0,0,0,0.3); color: white; outline: none;
        transition: all 0.2s;
    }
    .form-input:focus, .form-textarea:focus, .form-select:focus {
        border-color: rgba(216,179,92,0.4); background: rgba(0,0,0,0.5);
        box-shadow: 0 0 0 4px rgba(216,179,92,0.1);
    }
    .form-select {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23fde68a' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 14px center;
        background-repeat: no-repeat;
        background-size: 16px 12px;
        padding-right: 40px;
    }
    .form-textarea {
        resize: vertical; min-height: 120px;
    }
    .form-select option {
        background: #180c0e; color: white;
    }
    
    .excuse-details {
        padding: 20px; background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06); border-radius: 16px;
        font-size: 0.9rem; color: rgba(245,234,215,0.8);
        margin-bottom: 24px;
    }
    
    .modal-footer {
        padding: 20px 28px 28px; 
        display: flex; gap: 14px; justify-content: flex-end;
        border-top: 1px solid rgba(255,255,255,0.06);
        background: rgba(255,255,255,0.01);
    }
    .btn {
        padding: 12px 24px; border-radius: 12px; font-size: 0.85rem;
        font-weight: 700; cursor: pointer; transition: all 0.2s;
        border: none; display: inline-flex; align-items: center; gap: 8px;
        text-decoration: none; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .btn-primary {
        background: linear-gradient(135deg, #cfa46f, #b38855) !important; color: #1a0f0d !important;
        box-shadow: 0 8px 20px rgba(207,164,111,0.25);
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(207,164,111,0.35);
    }
    .btn-secondary {
        background: rgba(255,255,255,0.08) !important; color: white !important; 
        border: 1px solid rgba(255,255,255,0.1) !important;
    }
    .btn-secondary:hover {
        background: rgba(255,255,255,0.12) !important;
    }
    
    /* Override specific targeting for button colors */
    #excuseModal .btn-primary,
    #excuseModal button[type="submit"] {
        background: linear-gradient(135deg, #cfa46f, #b38855) !important;
        color: #1a0f0d !important;
    }
    #excuseModal .btn-primary:hover,
    #excuseModal button[type="submit"]:hover {
        background: linear-gradient(135deg, #dfb47f, #c39865) !important;
    }
    #excuseModal .btn-primary:focus,
    #excuseModal button[type="submit"]:focus {
        box-shadow: 0 0 0 4px rgba(207,164,111,0.2) !important;
    }

    /* ── MOBILE RESPONSIVENESS FOR CARDS ── */
    @media (max-width: 768px) {
        .records-container {
            gap: 12px;
        }
        
        .record-card {
            border-radius: 12px;
        }
        
        .record-header {
            padding: 16px 16px 12px;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        
        .record-subject {
            width: 100%;
        }
        
        .record-subject h4 {
            font-size: 0.95rem;
        }
        
        .record-meta {
            margin-top: 4px;
        }
        
        .record-date {
            font-size: 0.8rem;
        }
        
        .record-day {
            font-size: 0.7rem;
        }
        
        .record-status {
            align-self: flex-end;
        }
        
        .record-body {
            padding: 0 16px 16px;
        }
        
        .record-details {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .detail-item {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 8px;
        }
        
        .detail-label {
            font-size: 0.7rem;
        }
        
        .detail-value {
            font-size: 0.8rem;
        }
        
        .status-badge {
            font-size: 0.7rem;
            padding: 6px 12px;
        }
        
        .excuse-btn {
            font-size: 0.8rem;
            padding: 8px 12px;
        }
        
    /* ── ADDITIONAL MOBILE RESPONSIVENESS ── */
    @media (max-width: 768px) {
        .records-page {
            padding-left: 15px !important;
            padding-right: 15px !important;
        }

        .page-header-title { font-size: 1.2rem; }
        .page-header-sub { font-size: 0.8rem; }

        .summary-chips {
            gap: 8px;
        }
        .summary-chip {
            min-width: 100px;
            padding: 12px 16px;
        }
        .summary-chip-value { font-size: 1.5rem; }
        .summary-chip-label { font-size: 0.7rem; }

        .table-card-header {
            padding: 16px 20px;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        .table-card-title { font-size: 0.9rem; }
        .table-title-icon {
            width: 30px; height: 30px;
            font-size: 0.9rem;
        }

        .filter-tabs {
            width: 100%;
            justify-content: center;
        }
        .filter-tab {
            flex: 1;
            text-align: center;
            font-size: 0.7rem;
            padding: 8px 12px;
        }

        .back-btn {
            font-size: 0.8rem;
            padding: 6px 12px;
        }

        .modal-content {
            margin: 10px;
            max-width: calc(100vw - 20px);
            border-radius: 16px;
        }
        .modal-header {
            padding: 20px 24px 16px;
        }
        .modal-body {
            padding: 24px;
        }
        .modal-footer {
            padding: 16px 24px 24px;
            flex-direction: column;
            gap: 12px;
        }
        .btn {
            width: 100%;
            justify-content: center;
            min-height: 48px;
        }
        .modal-title {
            font-size: 0.95rem;
        }
        .excuse-details {
            padding: 16px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
    }
</style>

<div class="records-page">

    <a href="{{ route('home') }}" class="back-btn">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    <div class="page-header">
        <div class="page-header-title">Attendance Records</div>
        <div class="page-header-sub">Your complete attendance history — {{ $records->count() }} total records</div>
    </div>

    @php
        $totalPresent = $records->where('status', 'Present')->where('excused', false)->count();
        $totalLate    = $records->where('status', 'Late')->where('excused', false)->count();
        $totalAbsent  = $records->where('status', 'Absent')->where('excused', false)->count();
        $totalExcused = $records->where('excused', true)->count();
        $total        = $records->count();
        $rate         = $total > 0 ? round((($totalPresent + $totalLate) / $total) * 100) : 0;
    @endphp

    <!-- Summary chips -->
    <div class="summary-chips">
        <div class="summary-chip">
            <div class="summary-chip-value" style="color: #f8e7d3;">{{ $total }}</div>
            <div class="summary-chip-label">Total Records</div>
        </div>
        <div class="summary-chip">
            <div class="summary-chip-value" style="color: #4ade80;">{{ $totalPresent }}</div>
            <div class="summary-chip-label">Present</div>
        </div>
        <div class="summary-chip">
            <div class="summary-chip-value" style="color: #fbbf24;">{{ $totalLate }}</div>
            <div class="summary-chip-label">Late</div>
        </div>
        <div class="summary-chip">
            <div class="summary-chip-value" style="color: #f87171;">{{ $totalAbsent }}</div>
            <div class="summary-chip-label">Absent</div>
        </div>
    </div>

    <!-- Table card -->
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">
                <div class="table-title-icon">
                    <i class="bi bi-shield-check-fill"></i>
                </div>
                All Records
            </div>
            <!-- Filter tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterTable('all', this)">All</button>
                <button class="filter-tab" onclick="filterTable('Present', this)">Present</button>
                <button class="filter-tab" onclick="filterTable('Late', this)">Late</button>
                <button class="filter-tab" onclick="filterTable('Absent', this)">Absent</button>
            </div>
        </div>

        <!-- Mobile-first Card Layout -->
        <div class="records-container" id="recordsContainer">
            @forelse($records as $i => $record)
            <div class="record-card" data-status="{{ $record->status }}">
                <div class="record-header">
                    <div class="record-subject">
                        <h4>{{ $record->subject->name ?? $record->subject_code }}</h4>
                        <div class="record-meta">
                            <span class="record-date">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</span>
                            <span class="record-day">{{ \Carbon\Carbon::parse($record->date)->format('l') }}</span>
                        </div>
                    </div>
                    <div class="record-status">
                        @if($record->excused)
                            <span class="status-badge badge-excused" style="background:#f0fdf4;color:#16a34a;padding:4px 12px;border-radius:12px;font-size:.75rem;font-weight:600;">Excused</span>
                        @elseif($record->status === 'Present')
                            <span class="status-badge badge-present">Present</span>
                        @elseif($record->status === 'Late')
                            <span class="status-badge badge-late">Late</span>
                        @else
                            <span class="status-badge badge-absent">Absent</span>
                        @endif
                    </div>
                </div>
                
                <div class="record-body">
                    <div class="record-details">
                        <div class="detail-item">
                            <span class="detail-label">Time In</span>
                            <span class="detail-value">{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Record #</span>
                            <span class="detail-value">{{ $i + 1 }}</span>
                        </div>
                    </div>
                    
                    <div class="record-actions" style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                        @if($record->status === 'Absent' && !$record->excuseSubmission)
                            <button class="excuse-btn" onclick="openExcuseModal({{ $record->id }}, '{{ addslashes($record->subject->name ?? $record->subject_code) }}', '{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}')">
                                <i class="bi bi-file-text"></i> Submit Excuse
                            </button>
                        @elseif($record->excuseSubmission)
                            <span class="excuse-status excuse-{{ $record->excuseSubmission->status }}">
                                <i class="bi bi-{{ $record->excuseSubmission->status === 'approved' ? 'check-circle' : ($record->excuseSubmission->status === 'rejected' ? 'x-circle' : 'clock') }}"></i>
                                Excuse: {{ ucfirst($record->excuseSubmission->status) }}
                            </span>
                        @endif

                        @if($record->correction)
                            <span class="excuse-status excuse-{{ $record->correction->status }}" style="background:rgba(207,164,111,0.15); color:#fde68a; border:1px solid rgba(207,164,111,0.3);">
                                <i class="bi bi-patch-question"></i> Appeal: {{ ucfirst($record->correction->status) }}
                            </span>
                        @elseif(in_array($record->status, ['Late', 'Absent']))
                            <button type="button" class="excuse-btn" style="background:rgba(255,255,255,0.06) !important; color:#e2e8f0 !important; border-color:rgba(255,255,255,0.15) !important;" onclick="openCorrectionModal({{ $record->id }}, '{{ addslashes($record->subject->name ?? $record->subject_code) }}', '{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}', '{{ $record->status }}')">
                                <i class="bi bi-pencil-square"></i> Appeal Record
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>No attendance records found.</p>
            </div>
            @endforelse
        </div>
    </div>

</div>

<!-- Excuse Submission Modal -->
<div class="modal-overlay" id="excuseModal" role="dialog" aria-modal="true" aria-labelledby="excuseModalTitle">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title" id="excuseModalTitle">Submit Excuse</div>
            <button class="modal-close" onclick="closeExcuseModal()">✕</button>
        </div>
        <form id="excuseForm" method="POST" action="{{ route('excuses.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="attendance_id" id="attendanceId">
            <div class="modal-body">
                <div class="excuse-details" id="excuseDetails"></div>
                
                <div class="form-group">
                    <label class="form-label" for="reason">Reason for Absence <span style="color: #dc2626;">*</span></label>
                    <select name="reason" id="reason" class="form-select" required>
                        <option value="">Select a reason</option>
                        <option value="Medical/Health Issues">Medical/Health Issues</option>
                        <option value="Family Emergency">Family Emergency</option>
                        <option value="Personal Emergency">Personal Emergency</option>
                        <option value="Transportation Issues">Transportation Issues</option>
                        <option value="Weather Conditions">Weather Conditions</option>
                        <option value="Academic Activity">Academic Activity</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Detailed Description <span style="color: #dc2626;">*</span></label>
                    <textarea name="description" id="description" class="form-textarea" placeholder="Please provide a detailed explanation of your absence..." required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="attachments">Medical Certificate / Supporting File (Optional)</label>
                    <input type="file" name="attachments[]" id="attachments" class="form-input" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" style="padding: 10px 14px; font-size: 0.85rem;">
                    <small style="color: rgba(245,234,215,0.45); font-size: 0.75rem; margin-top: 4px; display: block;">Supports JPG, PNG, PDF, DOC up to 5MB</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeExcuseModal()">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="submit" class="btn excuse-submit-btn" style="background: #800000 !important; color: white !important; border: none !important;">
                    <i class="bi bi-send"></i> Submit Excuse
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Attendance Correction / Appeal Modal -->
<div class="modal-overlay" id="correctionModal" role="dialog" aria-modal="true" aria-labelledby="corrModalTitle">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title" id="corrModalTitle">Appeal Attendance Record</div>
            <button class="modal-close" onclick="closeCorrectionModal()">✕</button>
        </div>
        <form id="correctionForm" method="POST" action="{{ route('corrections.store') }}">
            @csrf
            <input type="hidden" name="attendance_id" id="corrAttendanceId">
            <div class="modal-body">
                <div class="excuse-details" id="corrDetails"></div>
                <div class="form-group">
                    <label class="form-label" for="corrReason">Reason for Appeal / Correction <span style="color: #dc2626;">*</span></label>
                    <textarea name="reason" id="corrReason" class="form-textarea" placeholder="Explain why your attendance record should be corrected (e.g., scanner error, verified late arrival, technical glitch)..." required rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCorrectionModal()">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="submit" class="btn" style="background: linear-gradient(135deg, #cfa46f, #8f6e4a); color: white; font-weight: 700; border: none; border-radius: 12px; padding: 10px 20px;">
                    <i class="bi bi-send-check"></i> Submit Appeal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openExcuseModal(attendanceId, subjectName, date) {
    document.getElementById('attendanceId').value = attendanceId;
    document.getElementById('excuseDetails').innerHTML = `
        <div style="font-weight: 700; color: #fde68a; margin-bottom: 6px; font-size: 1.05rem;">${subjectName}</div>
        <div style="font-size: 0.85rem; color: rgba(245,234,215,0.7); margin-bottom: 4px;"><strong style="color: rgba(245,234,215,0.9);">Date:</strong> ${date}</div>
        <div style="font-size: 0.85rem; color: rgba(245,234,215,0.7);">
            <strong style="color: rgba(245,234,215,0.9);">Status:</strong> 
            <span style="display:inline-block; padding: 2px 8px; border-radius: 6px; background: rgba(239,68,68,0.15); color: #f87171; font-weight: 700; margin-left: 4px; font-size: 0.75rem;">Absent</span>
        </div>
    `;
    document.getElementById('excuseModal').classList.add('active');
}

function closeExcuseModal() {
    document.getElementById('excuseModal').classList.remove('active');
    document.getElementById('excuseForm').reset();
}

function openCorrectionModal(attendanceId, subjectName, date, currentStatus) {
    document.getElementById('corrAttendanceId').value = attendanceId;
    document.getElementById('corrDetails').innerHTML = `
        <div style="font-weight: 700; color: #fde68a; margin-bottom: 6px; font-size: 1.05rem;">${subjectName}</div>
        <div style="font-size: 0.85rem; color: rgba(245,234,215,0.7); margin-bottom: 4px;"><strong style="color: rgba(245,234,215,0.9);">Date:</strong> ${date}</div>
        <div style="font-size: 0.85rem; color: rgba(245,234,215,0.7);">
            <strong style="color: rgba(245,234,215,0.9);">Recorded Status:</strong> 
            <span style="display:inline-block; padding: 2px 8px; border-radius: 6px; background: rgba(245,158,11,0.15); color: #fbbf24; font-weight: 700; margin-left: 4px; font-size: 0.75rem;">${currentStatus}</span>
        </div>
    `;
    document.getElementById('correctionModal').classList.add('active');
}

function closeCorrectionModal() {
    document.getElementById('correctionModal').classList.remove('active');
    document.getElementById('correctionForm').reset();
}

// Close modal when clicking outside
document.getElementById('excuseModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeExcuseModal();
    }
});
document.getElementById('correctionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCorrectionModal();
    }
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('excuseModal').classList.contains('active')) {
            closeExcuseModal();
        }
        if (document.getElementById('correctionModal').classList.contains('active')) {
            closeCorrectionModal();
        }
    }
});

function filterTable(status, btn) {
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');

    // Show/hide cards
    document.querySelectorAll('#recordsContainer .record-card[data-status]').forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<style>
/* FINAL OVERRIDE - Place at end to ensure it takes precedence */
.modal-overlay#excuseModal .modal-content .modal-footer button.btn.btn-primary,
.modal-overlay#excuseModal .modal-content .modal-footer button[type="submit"],
#excuseModal button.btn-primary,
#excuseModal button[type="submit"],
#excuseModal .btn-primary,
#excuseModal .excuse-submit-btn {
    background: #800000 !important;
    background-color: #800000 !important;
    border: none !important;
    border-color: #800000 !important;
    color: white !important;
}

.modal-overlay#excuseModal .modal-content .modal-footer button.btn.btn-primary:hover,
.modal-overlay#excuseModal .modal-content .modal-footer button[type="submit"]:hover,
#excuseModal button.btn-primary:hover,
#excuseModal button[type="submit"]:hover,
#excuseModal .btn-primary:hover,
#excuseModal .excuse-submit-btn:hover {
    background: #600000 !important;
    background-color: #600000 !important;
    border-color: #600000 !important;
    color: white !important;
    transform: translateY(-1px);
}

.modal-overlay#excuseModal .modal-content .modal-footer button.btn.btn-primary:focus,
.modal-overlay#excuseModal .modal-content .modal-footer button[type="submit"]:focus,
#excuseModal button.btn-primary:focus,
#excuseModal button[type="submit"]:focus,
#excuseModal .btn-primary:focus,
#excuseModal .excuse-submit-btn:focus {
    background: #800000 !important;
    background-color: #800000 !important;
    border-color: #800000 !important;
    color: white !important;
    box-shadow: 0 0 0 3px rgba(128, 0, 0, 0.2) !important;
}
</style>
@endsection
