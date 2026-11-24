@extends('layouts.mobile-app')

@section('title', 'Home - Smart Attendance')

@section('content')
@php
    $studentUser = auth()->user();
    $academicBits = array_filter([
        $studentUser?->course,
        $studentUser?->year_level ? 'Year ' . $studentUser->year_level : null,
        $studentUser?->section ? 'Sec. ' . $studentUser->section : null,
    ]);
@endphp
<div class="mobile-home">
    {{-- Welcome Card --}}
    <div class="welcome-card">
        <div class="welcome-top-row">
            <span class="welcome-kicker">
                <i class="bi bi-sun-fill" aria-hidden="true"></i>
                Good {{ $greeting }}
            </span>
            <span class="welcome-date">{{ now()->format('D, M j') }}</span>
        </div>
        <div class="welcome-greeting">
            <h2>{{ $studentUser?->name }}</h2>
            @if(!empty($academicBits))
                <div class="welcome-chips">
                    @foreach($academicBits as $bit)
                        <span class="welcome-chip">{{ $bit }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if(blank($studentUser?->section))
    <div role="status" class="section-warning-banner">
        <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
        <div>
            <strong>Class section not assigned</strong>
            <span>Contact the registrar or school administrator to add your section and timetable.</span>
        </div>
    </div>
    @endif

    {{-- Today's Attendance Status --}}
    <div class="status-card status-{{ $todayStatus }}">
        <div class="status-header">
            <h3>TODAY'S ATTENDANCE</h3>
            <span class="status-date">{{ now()->format('M j, Y') }}</span>
        </div>

        <div class="status-body">
            <div class="status-icon">
                @if($todayStatus === 'present')
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                @elseif($todayStatus === 'absent')
                    <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                @else
                    <i class="bi bi-clock-fill" aria-hidden="true"></i>
                @endif
            </div>
            <div class="status-text">{{ strtoupper($todayStatusText) }}</div>
            @if($checkInTime)
                <div class="status-time">{{ $checkInTime }} Check-in</div>
            @else
                <div class="status-time">{{ $todayStatusSubtext }}</div>
            @endif
        </div>

        <div class="status-footer">
            <div class="status-progress">
                <div class="progress-bar" style="width: {{ $attendanceRate }}%"></div>
            </div>
            <p class="status-summary">
                @if($weekTotal > 0)
                    This week: {{ $weekPresent }}/{{ $weekTotal }} marked present ({{ $attendanceRate }}%)
                @else
                    No attendance records yet this week
                @endif
            </p>
        </div>
    </div>

    {{-- Quick Actions --}}
    <section class="quick-actions-section">
        <h3 class="section-title">QUICK ACTIONS</h3>
        <div class="quick-actions-grid">
            <button type="button" id="quickActionScanBtn" data-action="open-scanner" class="quick-action-btn quick-action-primary" aria-label="Scan QR Code">
                <div class="quick-action-icon">
                    <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                </div>
                <span class="quick-action-label">Scan QR</span>
            </button>

            <a href="{{ route('student.schedule') }}" class="quick-action-btn">
                <div class="quick-action-icon">
                    <i class="bi bi-calendar2-week" aria-hidden="true"></i>
                </div>
                <span class="quick-action-label">Schedule</span>
            </a>

            <a href="{{ route('mobile.history') }}" class="quick-action-btn">
                <div class="quick-action-icon">
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                </div>
                <span class="quick-action-label">History</span>
            </a>

            <a href="{{ route('excuses.create_general') }}" class="quick-action-btn">
                <div class="quick-action-icon">
                    <i class="bi bi-file-earmark-medical" aria-hidden="true"></i>
                </div>
                <span class="quick-action-label">Submit Excuse</span>
            </a>

            @if(!empty($hasActiveAssistantAssignment))
                <a href="{{ route('student-assistant.classes') }}" class="quick-action-btn">
                    <div class="quick-action-icon"><i class="bi bi-qr-code" aria-hidden="true"></i></div>
                    <span class="quick-action-label">Student Assistant</span>
                </a>
            @endif

            @if(app(\App\Services\PeerBiometricAttendanceService::class)->isAvailable())
            <a href="{{ route('peer-snap.index') }}" class="quick-action-btn quick-action-peer" aria-label="Check in for a Classmate">
                <div class="quick-action-icon"><i class="bi bi-person-bounding-box" aria-hidden="true"></i></div>
                <span class="quick-action-label">Check in for a Classmate</span>
            </a>
            @endif
        </div>
    </section>

    {{-- Upcoming Classes --}}
    @if(count($upcomingClasses) > 0)
    <section class="upcoming-section">
        <h3 class="section-title">UPCOMING CLASSES</h3>
        <div class="upcoming-list">
            @foreach($upcomingClasses as $class)
            <a href="{{ route('student.schedule') }}" class="class-card" aria-label="View schedule for {{ $class['name'] }} at {{ $class['time'] }}">
                <div class="class-time">
                    <i class="bi bi-clock"></i>
                    <span>{{ $class['time'] }}</span>
                </div>
                <div class="class-details">
                    <h4>{{ $class['name'] }}</h4>
                    <p>{{ $class['room'] }} • {{ $class['teacher'] }}</p>
                </div>
                <span class="class-action" aria-hidden="true">
                    <i class="bi bi-chevron-right"></i>
                </span>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Recent Activity --}}
    <section class="activity-section">
        <h3 class="section-title">RECENT ACTIVITY</h3>
        <div class="activity-list">
            @forelse($recentActivity as $date => $activities)
                <div class="activity-date-group">
                    <p class="activity-date">{{ $date }}</p>
                    @foreach($activities as $activity)
                    <div class="activity-item">
                        <div class="activity-icon activity-{{ $activity['status'] }}">
                            <i class="bi bi-{{ $activity['icon'] }}"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-title">{{ $activity['title'] }}</p>
                            @if(isset($activity['subtitle']))
                                <p class="activity-subtitle">{{ $activity['subtitle'] }}</p>
                            @endif
                        </div>
                        <div class="activity-time">{{ $activity['time'] }}</div>
                    </div>
                    @endforeach
                </div>
            @empty
                <p class="empty-state">No recent activity</p>
            @endforelse
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    .mobile-home {
        padding-bottom: 24px;
    }

    /* Welcome Card */
    .welcome-card {
        margin-bottom: 16px;
        padding: 18px 16px;
        border-radius: 18px;
        background: linear-gradient(145deg, rgba(122, 26, 26, 0.22), rgba(24, 16, 14, 0.92));
        border: 1px solid rgba(207, 164, 111, 0.22);
    }

    .welcome-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
    }

    .welcome-kicker {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #f3d18e;
        background: rgba(212, 175, 55, 0.14);
        border: 1px solid rgba(212, 175, 55, 0.24);
        padding: 3px 9px;
        border-radius: 99px;
    }

    .welcome-greeting h2 {
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 8px;
        letter-spacing: -0.02em;
        line-height: 1.25;
    }

    .welcome-date {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--text-secondary);
        margin: 0;
    }

    .welcome-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .welcome-chip {
        font-size: 0.73rem;
        font-weight: 600;
        color: var(--text-secondary);
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 3px 9px;
        border-radius: 8px;
    }

    .section-warning-banner {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 12px 14px;
        margin-bottom: 14px;
        border-radius: 14px;
        border: 1px solid rgba(207, 164, 111, 0.35);
        background: rgba(207, 164, 111, 0.1);
        color: #f3e7cd;
        font-size: 0.84rem;
    }

    .section-warning-banner i {
        color: #dfb784;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .section-warning-banner strong {
        display: block;
        margin-bottom: 2px;
    }

    .section-warning-banner span {
        display: block;
        color: var(--text-secondary);
        font-size: 0.78rem;
        line-height: 1.45;
    }

    /* Status Card */
    .status-card {
        background: var(--bg-card);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid rgba(207, 164, 111, 0.2);
    }

    .status-card.status-present {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(34, 197, 94, 0.05));
        border-color: rgba(34, 197, 94, 0.3);
    }

    .status-card.status-absent {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.05));
        border-color: rgba(239, 68, 68, 0.3);
    }

    .status-card.status-pending {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(245, 158, 11, 0.05));
        border-color: rgba(245, 158, 11, 0.3);
    }

    .status-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .status-header h3 {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
    }

    .status-date {
        font-size: 11px;
        color: var(--text-muted);
    }

    .status-body {
        text-align: center;
        margin-bottom: 20px;
    }

    .status-icon {
        font-size: 48px;
        margin-bottom: 12px;
    }

    .status-present .status-icon {
        color: var(--success);
    }

    .status-absent .status-icon {
        color: var(--error);
    }

    .status-pending .status-icon {
        color: var(--warning);
    }

    .status-text {
        font-size: 24px;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .status-time {
        font-size: 14px;
        color: var(--text-secondary);
    }

    .status-footer {
        padding-top: 16px;
        border-top: 1px solid rgba(207, 164, 111, 0.1);
    }

    .status-progress {
        width: 100%;
        height: 4px;
        background: rgba(207, 164, 111, 0.1);
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 8px;
    }

    .progress-bar {
        height: 100%;
        background: var(--gold-primary);
        transition: width 0.3s ease;
    }

    .status-summary {
        font-size: 12px;
        color: var(--text-secondary);
        text-align: center;
    }

    /* Section Titles */
    .section-title {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
        margin-bottom: 16px;
    }

    /* Quick Actions */
    .quick-actions-section {
        margin-bottom: 32px;
    }

    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .quick-action-peer {
        grid-column: 1 / -1;
        flex-direction: row !important;
        justify-content: center;
        padding: 18px 24px !important;
    }

    .quick-action-btn {
        background: var(--bg-card);
        border: 1px solid rgba(207, 164, 111, 0.2);
        border-radius: 16px;
        padding: 24px;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        transition: all 0.2s ease;
        /* button element reset */
        cursor: pointer;
        font-family: inherit;
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
    }

    .quick-action-btn:active {
        transform: scale(0.95);
        background: var(--bg-card-hover);
    }

    .quick-action-primary {
        background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
        border: none;
    }

    .quick-action-icon {
        font-size: 32px;
        color: var(--gold-primary);
    }

    .quick-action-primary .quick-action-icon {
        color: var(--bg-dark);
    }

    .quick-action-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .quick-action-primary .quick-action-label {
        color: var(--bg-dark);
    }

    /* Upcoming Classes */
    .upcoming-section {
        margin-bottom: 32px;
    }

    .upcoming-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .class-card {
        background: var(--bg-card);
        border: 1px solid rgba(207, 164, 111, 0.2);
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: inherit;
        text-decoration: none;
    }

    .class-card:hover { border-color: rgba(207, 164, 111, 0.55); color: inherit; }
    .class-card:focus-visible { outline: 2px solid var(--gold-primary); outline-offset: 3px; }

    .class-time {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        min-width: 60px;
        color: var(--gold-primary);
    }

    .class-time i {
        font-size: 20px;
    }

    .class-time span {
        font-size: 12px;
        font-weight: 600;
    }

    .class-details {
        flex: 1;
        min-width: 0;
    }

    .class-details h4 {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 4px;
        overflow-wrap: anywhere;
    }

    .class-details p {
        font-size: 12px;
        color: var(--text-secondary);
        margin: 0;
        overflow-wrap: anywhere;
    }

    .class-action {
        width: 32px;
        height: 32px;
        border: none;
        background: transparent;
        color: var(--text-muted);
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    /* Recent Activity */
    .activity-section {
        margin-bottom: 32px;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .activity-date-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .activity-date {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 4px;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: var(--bg-card);
        border-radius: 12px;
        border: 1px solid rgba(207, 164, 111, 0.1);
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .activity-success {
        background: rgba(34, 197, 94, 0.15);
        color: var(--success);
    }

    .activity-error {
        background: rgba(239, 68, 68, 0.15);
        color: var(--error);
    }

    .activity-warning {
        background: rgba(245, 158, 11, 0.15);
        color: var(--warning);
    }

    .activity-content {
        flex: 1;
    }

    .activity-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 2px;
    }

    .activity-subtitle {
        font-size: 12px;
        color: var(--text-secondary);
    }

    .activity-time {
        font-size: 12px;
        color: var(--text-muted);
        white-space: nowrap;
    }

    .empty-state {
        text-align: center;
        color: var(--text-muted);
        padding: 40px 20px;
        font-size: 14px;
    }

    @media (min-width: 900px) {
        .mobile-home { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 24px; }
        .welcome-card { grid-column: 1 / -1; margin-bottom: 0; }
        .status-card, .quick-actions-section, .upcoming-section, .activity-section { min-width: 0; margin-bottom: 0; }
        .quick-actions-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .quick-action-btn { min-height: 112px; }
    }

    @media (max-width: 380px) {
        .status-card { padding: 18px; }
        .quick-action-btn { padding: 18px 10px; }
        .activity-item { align-items: flex-start; }
        .activity-time { white-space: normal; text-align: right; }
    }
</style>
@endpush
