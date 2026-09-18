@extends('layouts.app')
@section('page-title', 'Parent Dashboard')

@section('content')
<style>
    /* ── CUSTOM SCROLLBAR & ANIMATIONS ── */
    .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(207,164,111,0.25); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(207,164,111,0.45); }

    @keyframes cardFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes badgePulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.85; }
    }

    /* ── PARENT HERO BANNER ── */
    .parent-hero-banner {
        background: linear-gradient(135deg, rgba(38, 22, 16, 0.95) 0%, rgba(20, 11, 7, 0.98) 100%);
        border: 1px solid rgba(207, 164, 111, 0.25);
        border-radius: 20px;
        padding: 24px 28px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 36px rgba(0, 0, 0, 0.35);
        margin-bottom: 24px;
        backdrop-filter: blur(16px);
    }
    .parent-hero-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 5px;
        height: 100%;
        background: linear-gradient(180deg, #cfa46f 0%, #8f6e4a 100%);
    }
    .parent-hero-banner::after {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(207, 164, 111, 0.12) 0%, transparent 70%);
        pointer-events: none;
    }
    .parent-hero-title {
        color: #ffffff;
        font-weight: 800;
        margin: 0 0 6px 0;
        font-size: clamp(1.4rem, 3.5vw, 2rem);
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .parent-hero-sub {
        color: #b39b82;
        font-size: 0.92rem;
        line-height: 1.5;
        margin: 0;
    }
    .parent-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(207, 164, 111, 0.12);
        color: #f3e7cd;
        border: 1px solid rgba(207, 164, 111, 0.3);
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }

    /* ── STUDENT SWITCHER TABS ── */
    .student-switcher-wrap {
        background: rgba(22, 13, 9, 0.75);
        border: 1px solid rgba(207, 164, 111, 0.18);
        border-radius: 16px;
        padding: 12px 16px;
        margin-bottom: 24px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        backdrop-filter: blur(12px);
    }
    .student-tabs-scroller {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 4px;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }
    .student-tabs-scroller::-webkit-scrollbar { display: none; }

    .student-tab-pill {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(207, 164, 111, 0.15);
        border-radius: 999px;
        padding: 8px 18px;
        color: #b39b82;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
        text-decoration: none;
        user-select: none;
    }
    .student-tab-pill:hover:not(.active) {
        background: rgba(207, 164, 111, 0.08);
        color: #f3e7cd;
        transform: translateY(-1px);
    }
    .student-tab-pill.active {
        background: linear-gradient(135deg, rgba(207, 164, 111, 0.25) 0%, rgba(143, 110, 74, 0.25) 100%);
        border-color: rgba(207, 164, 111, 0.55);
        color: #ffffff;
        box-shadow: 0 4px 16px rgba(207, 164, 111, 0.2);
    }
    .student-pill-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        object-fit: cover;
        border: 1.5px solid rgba(207, 164, 111, 0.4);
    }
    .student-pill-rate {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 999px;
    }
    .rate-pill-high { background: rgba(52, 211, 153, 0.18); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.35); }
    .rate-pill-med  { background: rgba(251, 191, 36, 0.18); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.35); }
    .rate-pill-low  { background: rgba(248, 113, 113, 0.18); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.35); }

    /* ── STUDENT PROFILE HERO CARD ── */
    .student-profile-showcase {
        background: linear-gradient(145deg, rgba(32, 18, 13, 0.85) 0%, rgba(18, 9, 6, 0.95) 100%);
        border: 1px solid rgba(207, 164, 111, 0.22);
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        margin-bottom: 24px;
        backdrop-filter: blur(14px);
    }
    .student-avatar-box {
        position: relative;
        width: 72px;
        height: 72px;
        flex-shrink: 0;
    }
    .student-avatar-img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 2.5px solid #cfa46f;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
    }
    .student-status-indicator {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid #140b07;
    }
    .student-name-title {
        color: #ffffff;
        font-weight: 800;
        font-size: clamp(1.2rem, 2.8vw, 1.6rem);
        letter-spacing: -0.3px;
        margin: 0 0 6px 0;
    }
    .student-id-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(207, 164, 111, 0.15);
        color: #f3e7cd;
        border: 1px solid rgba(207, 164, 111, 0.35);
        padding: 3px 10px;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 700;
        font-family: monospace;
        letter-spacing: 0.5px;
    }
    .student-meta-text {
        color: #b39b82;
        font-size: 0.88rem;
        margin-top: 6px;
    }

    /* ── QUICK ACTION BUTTONS ── */
    .parent-actions-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .parent-btn-action {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        border-radius: 12px;
        font-size: 0.84rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        white-space: nowrap;
        border: 1px solid transparent;
    }
    .parent-btn-action:hover {
        transform: translateY(-2px);
    }
    .btn-action-primary {
        background: linear-gradient(135deg, #cfa46f 0%, #a67f4c 100%);
        color: #140d07 !important;
        font-weight: 700;
        box-shadow: 0 4px 14px rgba(207, 164, 111, 0.25);
    }
    .btn-action-ghost {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(207, 164, 111, 0.2);
        color: #f3e7cd !important;
    }
    .btn-action-ghost:hover {
        background: rgba(207, 164, 111, 0.1);
        border-color: rgba(207, 164, 111, 0.4);
    }
    .btn-action-danger {
        background: rgba(239, 68, 68, 0.12);
        border-color: rgba(239, 68, 68, 0.3);
        color: #f87171 !important;
    }
    .btn-action-danger:hover {
        background: rgba(239, 68, 68, 0.2);
    }

    /* ── KPI METRICS CARDS ── */
    .parent-kpi-grid {
        display: grid;
        grid-template-columns: 1.4fr repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 24px;
    }
    .kpi-stat-card {
        background: rgba(25, 14, 10, 0.7);
        border: 1px solid rgba(207, 164, 111, 0.18);
        border-radius: 18px;
        padding: 18px 20px;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        backdrop-filter: blur(10px);
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    .kpi-stat-card:hover {
        transform: translateY(-2px);
        border-color: rgba(207, 164, 111, 0.35);
    }
    .kpi-stat-label {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #b39b82;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .kpi-stat-val {
        font-size: 1.85rem;
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.5px;
        color: #ffffff;
    }
    .kpi-stat-hint {
        font-size: 0.75rem;
        color: #8f826f;
        margin-top: 6px;
    }
    .kpi-icon-badge {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    .kpi-rate-card {
        background: linear-gradient(135deg, rgba(38, 22, 16, 0.9) 0%, rgba(20, 11, 7, 0.95) 100%);
        border-color: rgba(207, 164, 111, 0.35);
    }
    .streak-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.72rem;
        font-weight: 700;
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.3);
        padding: 3px 8px;
        border-radius: 999px;
        margin-top: 6px;
    }

    /* ── SECTIONS: WARNINGS & ATTENDANCE ── */
    .parent-section-card {
        background: rgba(22, 13, 9, 0.75);
        border: 1px solid rgba(207, 164, 111, 0.18);
        border-radius: 20px;
        padding: 22px 24px;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.28);
        backdrop-filter: blur(12px);
        margin-bottom: 24px;
    }
    .parent-section-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(207, 164, 111, 0.12);
    }
    .parent-section-title {
        color: #f3e7cd;
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* ── ALERT ITEMS ── */
    .alert-feed-item {
        background: rgba(239, 68, 68, 0.05);
        border: 1px solid rgba(239, 68, 68, 0.18);
        border-radius: 14px;
        padding: 16px 18px;
        margin-bottom: 12px;
        transition: all 0.2s ease;
    }
    .alert-feed-item:hover {
        background: rgba(239, 68, 68, 0.08);
        border-color: rgba(239, 68, 68, 0.3);
    }
    .alert-all-clear {
        background: rgba(52, 211, 153, 0.05);
        border: 1px solid rgba(52, 211, 153, 0.2);
        border-radius: 16px;
        padding: 32px 20px;
        text-align: center;
    }

    /* ── RECENT ATTENDANCE TABLE & MOBILE CARDS ── */
    .desktop-table-wrap {
        overflow-x: auto;
        margin: 0 -24px -22px;
    }
    .parent-data-table {
        width: 100%;
        border-collapse: collapse;
        color: #e6dbce;
        font-size: 0.88rem;
    }
    .parent-data-table th {
        background: rgba(14, 8, 5, 0.85);
        color: #cfa46f;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 14px 24px;
        border-bottom: 1px solid rgba(207, 164, 111, 0.18);
        text-align: left;
    }
    .parent-data-table td {
        padding: 16px 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        vertical-align: middle;
    }
    .parent-data-table tr:hover td {
        background: rgba(207, 164, 111, 0.03);
    }

    .mobile-att-card {
        background: rgba(26, 15, 11, 0.65);
        border: 1px solid rgba(207, 164, 111, 0.15);
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    }
    .mobile-att-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 10px;
    }
    .mobile-att-card-bot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        padding-top: 10px;
        margin-top: 6px;
    }

    /* ── STATUS BADGES ── */
    .att-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: capitalize;
    }
    .att-badge-present { background: rgba(52, 211, 153, 0.15); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.3); }
    .att-badge-late    { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
    .att-badge-absent  { background: rgba(248, 113, 113, 0.15); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); }
    .att-badge-excused { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }

    /* ── MOBILE RESPONSIVE OPTIMIZATIONS ── */
    @media (max-width: 768px) {
        .parent-hero-banner {
            padding: 18px 20px;
            border-radius: 16px;
        }
        .student-profile-showcase {
            padding: 18px;
            border-radius: 16px;
        }
        .student-avatar-box {
            width: 58px;
            height: 58px;
        }
        .parent-actions-row {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 14px;
        }
        .parent-actions-row .parent-btn-action {
            justify-content: center;
            padding: 8px 10px;
            font-size: 0.78rem;
        }
        .parent-kpi-grid {
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .parent-kpi-grid > .kpi-rate-card {
            grid-column: 1 / -1;
        }
        .parent-section-card {
            padding: 16px;
            border-radius: 16px;
        }
    }
</style>

<!-- ── PARENT HERO BANNER ── -->
<div class="parent-hero-banner">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="parent-hero-badge">
                <i class="bi bi-shield-check"></i> Parent &amp; Guardian Portal
            </span>
            <h1 class="parent-hero-title">
                <span>Welcome, {{ explode(' ', Auth::user()->name)[0] }}</span>
            </h1>
            <p class="parent-hero-sub">
                Live attendance monitoring, official excuses, and academic progress for your children
            </p>
        </div>
        <div>
            <a href="{{ route('parent.link.form') }}" class="btn-modern-gold" style="white-space: nowrap;">
                <i class="bi bi-person-plus-fill"></i> Link Student
            </a>
        </div>
    </div>
</div>

@if(count($childrenData) > 1)
<!-- ── MULTI-STUDENT SWITCHER TABS ── -->
<div class="student-switcher-wrap">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-people-fill text-gold" style="font-size: 1.1rem;"></i>
            <span style="font-size: 0.85rem; font-weight: 700; color: #f3e7cd; text-transform: uppercase; letter-spacing: 0.5px;">
                Your Linked Students ({{ count($childrenData) }})
            </span>
        </div>
        <span style="font-size: 0.75rem; color: #8f826f;">Tap student to view details</span>
    </div>
    <div class="student-tabs-scroller">
        @foreach($childrenData as $index => $data)
            @php
                $rRate = $data->rate;
                $rClass = $rRate >= 90 ? 'rate-pill-high' : ($rRate >= 75 ? 'rate-pill-med' : 'rate-pill-low');
                $cImg = $data->child->profile_image
                    ? (str_starts_with($data->child->profile_image, 'http') ? $data->child->profile_image : asset('storage/'.$data->child->profile_image))
                    : 'https://ui-avatars.com/api/?name='.urlencode($data->child->name).'&background=800000&color=fff&size=52';
            @endphp
            <button type="button" class="student-tab-pill {{ $index === 0 ? 'active' : '' }}" id="tabBtn_{{ $data->child->id }}" onclick="switchDashboardChild('{{ $data->child->id }}')">
                <img src="{{ $cImg }}" alt="{{ $data->child->name }}" class="student-pill-avatar">
                <span>{{ $data->child->name }}</span>
                <span class="student-pill-rate {{ $rClass }}">{{ $rRate }}%</span>
                @if($data->warnings->count() > 0)
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444; display: inline-block; animation: badgePulse 2s infinite;"></span>
                @endif
            </button>
        @endforeach
    </div>
</div>
@endif

@forelse($childrenData as $index => $data)
<div class="child-view-content" id="childView_{{ $data->child->id }}" style="display: {{ $index === 0 ? 'block' : 'none' }}; animation: cardFadeIn 0.3s ease;">
    @php
        $child = $data->child;
        $cImg = $child->profile_image
            ? (str_starts_with($child->profile_image, 'http') ? $child->profile_image : asset('storage/'.$child->profile_image))
            : 'https://ui-avatars.com/api/?name='.urlencode($child->name).'&background=800000&color=fff&size=96';
        $rateVal = $data->rate;
        $rateStatus = $rateVal >= 90 ? 'Excellent Standing' : ($rateVal >= 75 ? 'Satisfactory Standing' : 'Needs Attention');
        $rateColor = $rateVal >= 90 ? '#34d399' : ($rateVal >= 75 ? '#fbbf24' : '#f87171');
    @endphp

    <!-- ── STUDENT PROFILE HERO SHOWCASE ── -->
    <div class="student-profile-showcase">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="student-avatar-box">
                    <img src="{{ $cImg }}" alt="{{ $child->name }}" class="student-avatar-img">
                    <span class="student-status-indicator" style="background: {{ $rateColor }};" title="{{ $rateStatus }}"></span>
                </div>
                <div>
                    <h2 class="student-name-title">{{ $child->name }}</h2>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        @if($child->student_number)
                            <span class="student-id-chip" title="Official Student ID">
                                <i class="bi bi-person-badge"></i> {{ $child->student_number }}
                            </span>
                        @endif
                        <span style="font-size: 0.8rem; color: #cfa46f; font-weight: 600;">
                            {{ $child->course ?? 'General Academics' }}
                        </span>
                        <span style="color: rgba(179,155,130,0.4);">&bull;</span>
                        <span style="font-size: 0.8rem; color: #b39b82;">
                            Year {{ $child->year_level ?? '1' }} (Sem {{ $child->semester ?? '1' }})
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Action Shortcuts -->
            <div class="parent-actions-row">
                <a href="{{ route('parent.child', $child) }}" class="parent-btn-action btn-action-ghost" title="Detailed Attendance History">
                    <i class="bi bi-clock-history"></i>
                    <span>Records</span>
                </a>

                <a href="{{ route('parent.child.warnings', $child) }}" class="parent-btn-action {{ $data->warnings->count() > 0 ? 'btn-action-danger' : 'btn-action-ghost' }}" title="Academic Warnings">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Warnings</span>
                    @if($data->warnings->count() > 0)
                        <span class="badge rounded-pill bg-danger" style="font-size: 0.68rem; padding: 2px 6px;">{{ $data->warnings->count() }}</span>
                    @endif
                </a>

                <a href="{{ route('parent.excuses.create_general') }}" class="parent-btn-action btn-action-ghost" title="Submit Excuse Letter">
                    <i class="bi bi-file-earmark-plus"></i>
                    <span>Submit Excuse</span>
                </a>

                <a href="{{ route('parent.child.report', $child) }}" class="parent-btn-action btn-action-primary" title="Download Official PDF Report">
                    <i class="bi bi-file-earmark-pdf-fill"></i>
                    <span>PDF Report</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ── KPI STATS CARDS ── -->
    <div class="parent-kpi-grid">
        <!-- Rate KPI Card -->
        <div class="kpi-stat-card kpi-rate-card">
            <div>
                <div class="kpi-stat-label">
                    <span>Attendance Rate</span>
                    <div class="kpi-icon-badge" style="background: rgba(207, 164, 111, 0.15); color: var(--gold);">
                        <i class="bi bi-pie-chart-fill"></i>
                    </div>
                </div>
                <div class="kpi-stat-val" style="color: {{ $rateColor }};">
                    {{ $rateVal }}%
                </div>
                <div style="font-size: 0.78rem; font-weight: 700; color: {{ $rateColor }}; margin-top: 4px;">
                    {{ $rateStatus }}
                </div>
            </div>
            @if($data->streak > 0)
                <div class="streak-chip" title="Consecutive attended school days">
                    <span>🔥 {{ $data->streak }}-day attendance streak</span>
                </div>
            @else
                <div class="kpi-stat-hint">Calculated across {{ $data->total }} sessions</div>
            @endif
        </div>

        <!-- Present Card -->
        <div class="kpi-stat-card">
            <div class="kpi-stat-label">
                <span>Present</span>
                <div class="kpi-icon-badge" style="background: rgba(52, 211, 153, 0.15); color: #34d399;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
            <div class="kpi-stat-val" style="color: #34d399;">{{ $data->present }}</div>
            <div class="kpi-stat-hint">On-time attendances</div>
        </div>

        <!-- Late Card -->
        <div class="kpi-stat-card">
            <div class="kpi-stat-label">
                <span>Late</span>
                <div class="kpi-icon-badge" style="background: rgba(251, 191, 36, 0.15); color: #fbbf24;">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
            <div class="kpi-stat-val" style="color: #fbbf24;">{{ $data->late }}</div>
            <div class="kpi-stat-hint">Tardy check-ins</div>
        </div>

        <!-- Absent Card -->
        <div class="kpi-stat-card">
            <div class="kpi-stat-label">
                <span>Absent</span>
                <div class="kpi-icon-badge" style="background: rgba(248, 113, 113, 0.15); color: #f87171;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
            </div>
            <div class="kpi-stat-val" style="color: #f87171;">{{ $data->absent }}</div>
            <div class="kpi-stat-hint">Unexcused missed classes</div>
        </div>

        <!-- Excused Card -->
        <div class="kpi-stat-card">
            <div class="kpi-stat-label">
                <span>Excused</span>
                <div class="kpi-icon-badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                    <i class="bi bi-shield-fill-check"></i>
                </div>
            </div>
            <div class="kpi-stat-val" style="color: #38bdf8;">{{ $data->excused }}</div>
            <div class="kpi-stat-hint">
                @if($data->pendingExcuses > 0)
                    <span style="color: #fbbf24; font-weight: 600;">{{ $data->pendingExcuses }} pending review</span>
                @else
                    Official approved leaves
                @endif
            </div>
        </div>
    </div>

    <!-- ── DUAL COLUMN: ACADEMIC WARNINGS & RECENT ATTENDANCE ── -->
    <div class="row g-4 mb-4">
        <!-- Academic Warnings & Notices -->
        <div class="col-lg-5">
            <div class="parent-section-card h-100">
                <div class="parent-section-head">
                    <h3 class="parent-section-title">
                        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                        <span>Academic Notices</span>
                    </h3>
                    @if($data->warnings->count() > 0)
                        <a href="{{ route('parent.child.warnings', $child) }}" class="ent-btn ent-btn-sm ent-btn-ghost text-gold" style="font-size: 0.78rem;">
                            View All ({{ $data->warnings->count() }}) &rarr;
                        </a>
                    @endif
                </div>

                @if($data->warnings->count() > 0)
                    <div style="overflow-y: auto; max-height: 380px;" class="custom-scrollbar pe-1">
                        @foreach($data->warnings as $warning)
                            <div class="alert-feed-item">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                    <div style="font-size: 0.86rem; font-weight: 700; color: #f87171;">
                                        {{ $warning->subject->name ?? $warning->subject_code }}
                                    </div>
                                    <span style="font-size: 0.7rem; color: #b39b82;">
                                        {{ $warning->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <p style="font-size: 0.82rem; color: #e7dcc8; line-height: 1.5; margin: 0 0 8px 0;">
                                    {{ Str::limit($warning->message, 110) }}
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-size: 0.72rem; color: var(--gold); font-weight: 600;">
                                        <i class="bi bi-info-circle me-1"></i>Official Warning
                                    </span>
                                    <a href="{{ route('parent.excuses.create_general') }}" style="font-size: 0.75rem; color: #f3e7cd; font-weight: 600; text-decoration: underline;">
                                        Submit Excuse &rarr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert-all-clear">
                        <div style="width: 54px; height: 54px; border-radius: 50%; background: rgba(52, 211, 153, 0.12); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; border: 1px solid rgba(52, 211, 153, 0.25);">
                            <i class="bi bi-shield-check" style="font-size: 1.8rem; color: #34d399;"></i>
                        </div>
                        <h4 style="color: #34d399; font-weight: 700; font-size: 1.05rem; margin-bottom: 4px;">Good Standing</h4>
                        <p style="color: #b39b82; font-size: 0.85rem; margin: 0; max-width: 280px; margin: 0 auto;">
                            No attendance warnings or notices on record for {{ $child->name }}.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Attendance Log -->
        <div class="col-lg-7">
            <div class="parent-section-card h-100">
                <div class="parent-section-head">
                    <h3 class="parent-section-title">
                        <i class="bi bi-clock-history text-gold"></i>
                        <span>Recent Attendance</span>
                    </h3>
                    <a href="{{ route('parent.child', $child) }}" class="ent-btn ent-btn-sm ent-btn-ghost text-gold" style="font-size: 0.78rem;">
                        Full History &rarr;
                    </a>
                </div>

                @if($child->attendances->count() > 0)
                    <!-- Desktop Table View -->
                    <div class="d-none d-md-block desktop-table-wrap">
                        <table class="parent-data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Time In</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($child->attendances->take(6) as $att)
                                    @php
                                        $st = strtolower($att->status ?? 'absent');
                                    @endphp
                                    <tr>
                                        <td style="font-weight: 600; color: #ffffff;">
                                            {{ \Carbon\Carbon::parse($att->date)->format('M d, Y') }}
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #f3e7cd;">{{ $att->subject->name ?? $att->subject_code }}</div>
                                            <div style="font-size: 0.75rem; color: #8f826f;">{{ $att->subject->code ?? '' }}</div>
                                        </td>
                                        <td>
                                            @if($att->excused)
                                                <span class="att-badge att-badge-excused"><i class="bi bi-shield-check"></i> Excused</span>
                                            @elseif($st === 'present')
                                                <span class="att-badge att-badge-present"><i class="bi bi-check-circle"></i> Present</span>
                                            @elseif($st === 'late')
                                                <span class="att-badge att-badge-late"><i class="bi bi-clock"></i> Late</span>
                                            @else
                                                <span class="att-badge att-badge-absent"><i class="bi bi-x-circle"></i> Absent</span>
                                            @endif
                                        </td>
                                        <td style="color: #b39b82; font-family: monospace;">
                                            @if($att->time_in)
                                                <i class="bi bi-stopwatch text-gold me-1"></i>{{ \Carbon\Carbon::parse($att->time_in)->format('h:i A') }}
                                            @else
                                                <span style="color: rgba(179,155,130,0.4);">&mdash;</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right;">
                                            @if($st === 'absent' && !$att->excused)
                                                <a href="{{ route('parent.child.excuse', [$child, $att]) }}" class="parent-btn-action btn-action-ghost" style="padding: 4px 10px; font-size: 0.75rem;">
                                                    <i class="bi bi-pencil-square text-gold"></i> Excuse
                                                </a>
                                            @else
                                                <span style="color: rgba(179,155,130,0.3); font-size: 0.75rem;">&mdash;</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards View -->
                    <div class="d-block d-md-none">
                        @foreach($child->attendances->take(5) as $att)
                            @php
                                $st = strtolower($att->status ?? 'absent');
                            @endphp
                            <div class="mobile-att-card">
                                <div class="mobile-att-card-top">
                                    <div>
                                        <div style="font-size: 0.74rem; font-weight: 700; color: #cfa46f; margin-bottom: 2px;">
                                            {{ \Carbon\Carbon::parse($att->date)->format('M d, Y') }}
                                        </div>
                                        <div style="font-size: 0.92rem; font-weight: 700; color: #ffffff;">
                                            {{ $att->subject->name ?? $att->subject_code }}
                                        </div>
                                    </div>
                                    <div>
                                        @if($att->excused)
                                            <span class="att-badge att-badge-excused">Excused</span>
                                        @elseif($st === 'present')
                                            <span class="att-badge att-badge-present">Present</span>
                                        @elseif($st === 'late')
                                            <span class="att-badge att-badge-late">Late</span>
                                        @else
                                            <span class="att-badge att-badge-absent">Absent</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mobile-att-card-bot">
                                    <div style="font-size: 0.78rem; color: #b39b82; font-family: monospace;">
                                        @if($att->time_in)
                                            <i class="bi bi-stopwatch text-gold me-1"></i>{{ \Carbon\Carbon::parse($att->time_in)->format('h:i A') }}
                                        @else
                                            <span style="color: rgba(179,155,130,0.5);">No time logged</span>
                                        @endif
                                    </div>
                                    @if($st === 'absent' && !$att->excused)
                                        <a href="{{ route('parent.child.excuse', [$child, $att]) }}" style="font-size: 0.78rem; font-weight: 700; color: var(--gold); text-decoration: none;">
                                            <i class="bi bi-pencil-square me-1"></i>Submit Excuse &rarr;
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x" style="font-size: 2.2rem; color: rgba(207,164,111,0.35);"></i>
                        <h5 style="color: #f3e7cd; font-size: 0.95rem; margin-top: 10px;">No Attendance Records Yet</h5>
                        <p style="color: #8f826f; font-size: 0.82rem; margin: 0;">Attendance logs for {{ $child->name }} will show here once recorded by the instructor.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@empty
<!-- ── EMPTY STATE: NO CHILDREN LINKED ── -->
<div class="parent-section-card text-center py-5 my-4" style="max-width: 600px; margin-left: auto; margin-right: auto;">
    <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: rgba(207,164,111,0.12); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(207,164,111,0.3);">
        <i class="bi bi-link-45deg" style="font-size: 2.5rem; color: var(--gold);"></i>
    </div>
    <h3 style="color: #ffffff; font-weight: 800; font-size: 1.5rem; margin-bottom: 10px;">No Linked Students Yet</h3>
    <p style="color: #b39b82; font-size: 0.95rem; line-height: 1.6; margin-bottom: 24px;">
        Link your student's account using their official Student ID. Once linked, you will receive real-time attendance alerts, academic warning notices, and excuse filing capabilities.
    </p>
    <a href="{{ route('parent.link.form') }}" class="btn-modern-gold" style="display: inline-flex; padding: 12px 28px; font-size: 1rem;">
        <i class="bi bi-link-45deg"></i> Link Your Student Now
    </a>
</div>
@endforelse

<script>
// ── Child Switcher Logic ──
function switchDashboardChild(childId) {
    document.querySelectorAll('.child-view-content').forEach(function(el) {
        el.style.display = (el.id === 'childView_' + childId) ? 'block' : 'none';
    });

    // Update active tab styling
    document.querySelectorAll('.student-tab-pill').forEach(function(pill) {
        if (pill.id === 'tabBtn_' + childId) {
            pill.classList.add('active');
        } else {
            pill.classList.remove('active');
        }
    });

    try {
        localStorage.setItem('selectedParentChildId', childId);
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        var savedChildId = localStorage.getItem('selectedParentChildId');
        if (savedChildId && document.getElementById('childView_' + savedChildId)) {
            switchDashboardChild(savedChildId);
        }
    } catch(e) {}
});
</script>
@endsection
