@extends('layouts.app')

@section('title', 'Announcements & Broadcasts')

@section('content')
<style>
    /* Scoped Announcements Dashboard Styles */
    .ann-header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .ann-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #D4AF37;
        background: rgba(212, 175, 55, 0.1);
        border: 1px solid rgba(212, 175, 55, 0.25);
        padding: 4px 12px;
        border-radius: 9999px;
        margin-bottom: 8px;
    }
    .ann-page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #FCF8F2;
        margin: 0 0 6px 0;
        letter-spacing: -0.02em;
    }
    .ann-page-subtitle {
        color: #A39683;
        font-size: 0.9rem;
        margin: 0;
    }
    .ann-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #7A1A1A 0%, #9C2727 100%);
        color: #FFFFFF !important;
        border: 1px solid rgba(212, 175, 55, 0.35);
        padding: 10px 20px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 4px 14px rgba(122, 26, 26, 0.4);
    }
    .ann-btn-primary:hover {
        background: linear-gradient(135deg, #8E1F1F 0%, #B22E2E 100%);
        box-shadow: 0 6px 20px rgba(122, 26, 26, 0.6), 0 0 12px rgba(212, 175, 55, 0.25);
        transform: translateY(-1px);
        color: #FFFFFF !important;
    }
    
    /* Quick Stats Grid */
    .ann-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .ann-stat-card {
        background: rgba(26, 17, 17, 0.7);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1px solid rgba(212, 175, 55, 0.15);
        border-radius: 14px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.25s ease;
    }
    .ann-stat-card:hover {
        border-color: rgba(212, 175, 55, 0.35);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        transform: translateY(-2px);
    }
    .ann-stat-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .ann-stat-icon-gold {
        background: rgba(212, 175, 55, 0.12);
        color: #D4AF37;
        border: 1px solid rgba(212, 175, 55, 0.25);
    }
    .ann-stat-icon-emerald {
        background: rgba(52, 211, 153, 0.12);
        color: #34D399;
        border: 1px solid rgba(52, 211, 153, 0.25);
    }
    .ann-stat-icon-blue {
        background: rgba(96, 165, 250, 0.12);
        color: #60A5FA;
        border: 1px solid rgba(96, 165, 250, 0.25);
    }
    .ann-stat-icon-amber {
        background: rgba(251, 191, 36, 0.12);
        color: #FBBF24;
        border: 1px solid rgba(251, 191, 36, 0.25);
    }
    .ann-stat-num {
        font-size: 1.4rem;
        font-weight: 700;
        color: #FCF8F2;
        line-height: 1.2;
    }
    .ann-stat-label {
        font-size: 0.78rem;
        color: #A39683;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 500;
    }

    /* Main Container Card */
    .ann-card {
        background: rgba(26, 17, 17, 0.75);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(212, 175, 55, 0.18);
        border-radius: 18px;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45);
        overflow: hidden;
        margin-bottom: 24px;
    }
    .ann-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid rgba(212, 175, 55, 0.12);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        background: rgba(20, 12, 12, 0.5);
    }
    
    /* Search & Filter Form */
    .ann-filter-form {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        width: 100%;
    }
    .ann-search-box {
        position: relative;
        flex: 1;
        min-width: 260px;
    }
    .ann-search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #A39683;
        font-size: 0.9rem;
        pointer-events: none;
    }
    .ann-input {
        width: 100%;
        background: rgba(17, 10, 10, 0.85);
        border: 1px solid rgba(212, 175, 55, 0.18);
        color: #FCF8F2;
        padding: 9px 14px 9px 38px;
        border-radius: 10px;
        font-size: 0.88rem;
        outline: none;
        transition: all 0.2s ease;
    }
    .ann-input:focus {
        border-color: #D4AF37;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
        background: rgba(17, 10, 10, 0.95);
    }
    .ann-select {
        background: rgba(17, 10, 10, 0.85);
        border: 1px solid rgba(212, 175, 55, 0.18);
        color: #FCF8F2;
        padding: 9px 34px 9px 14px;
        border-radius: 10px;
        font-size: 0.88rem;
        outline: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23D4AF37' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        cursor: pointer;
        min-width: 170px;
    }
    .ann-select:focus {
        border-color: #D4AF37;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
    }
    .ann-select option {
        background-color: #1A1111;
        color: #FCF8F2;
    }
    .ann-btn-filter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(212, 175, 55, 0.12);
        color: #D4AF37;
        border: 1px solid rgba(212, 175, 55, 0.28);
        padding: 9px 16px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .ann-btn-filter:hover {
        background: rgba(212, 175, 55, 0.22);
        border-color: #D4AF37;
        color: #FFF;
    }
    .ann-btn-clear {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: transparent;
        color: #F87171;
        border: 1px solid rgba(248, 113, 113, 0.25);
        padding: 9px 14px;
        border-radius: 10px;
        font-size: 0.88rem;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .ann-btn-clear:hover {
        background: rgba(248, 113, 113, 0.12);
        border-color: #F87171;
        color: #FCA5A5;
    }

    /* Announcements Table */
    .ann-table-wrap {
        width: 100%;
        overflow-x: auto;
    }
    .ann-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    .ann-table th {
        background: rgba(17, 10, 10, 0.6);
        padding: 14px 20px;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #D4AF37;
        border-bottom: 1px solid rgba(212, 175, 55, 0.15);
        white-space: nowrap;
    }
    .ann-table td {
        padding: 16px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        color: #FCF8F2;
        vertical-align: middle;
        font-size: 0.88rem;
    }
    .ann-table tbody tr {
        transition: background 0.2s ease;
    }
    .ann-table tbody tr:hover {
        background: rgba(212, 175, 55, 0.035);
    }
    .ann-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Content Cell Styling */
    .ann-title-text {
        font-size: 0.95rem;
        font-weight: 600;
        color: #FCF8F2;
        margin-bottom: 4px;
        line-height: 1.3;
    }
    .ann-snippet-text {
        color: #A39683;
        font-size: 0.82rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 420px;
    }

    /* Audience Badges */
    .ann-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.3;
        white-space: nowrap;
    }
    .ann-badge-all {
        background: rgba(52, 211, 153, 0.12);
        color: #34D399;
        border: 1px solid rgba(52, 211, 153, 0.28);
    }
    .ann-badge-student {
        background: rgba(96, 165, 250, 0.12);
        color: #60A5FA;
        border: 1px solid rgba(96, 165, 250, 0.28);
    }
    .ann-badge-teacher {
        background: rgba(251, 191, 36, 0.12);
        color: #FBBF24;
        border: 1px solid rgba(251, 191, 36, 0.28);
    }
    .ann-badge-parent {
        background: rgba(244, 114, 182, 0.12);
        color: #F472B6;
        border: 1px solid rgba(244, 114, 182, 0.28);
    }
    .ann-badge-default {
        background: rgba(255, 255, 255, 0.08);
        color: #D1C5B4;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    /* Status Pill */
    .ann-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 8px;
        font-size: 0.74rem;
        font-weight: 600;
        background: rgba(52, 211, 153, 0.12);
        color: #34D399;
        border: 1px solid rgba(52, 211, 153, 0.25);
    }
    .ann-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #34D399;
        box-shadow: 0 0 6px #34D399;
    }

    /* Action Buttons */
    .ann-action-btn {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(212, 175, 55, 0.2);
        background: rgba(255, 255, 255, 0.04);
        color: #D1C5B4;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .ann-action-btn:hover {
        background: rgba(212, 175, 55, 0.15);
        color: #D4AF37;
        border-color: #D4AF37;
        transform: translateY(-1px);
    }
    .ann-action-btn-danger:hover {
        background: rgba(248, 113, 113, 0.15);
        color: #F87171;
        border-color: #F87171;
    }

    /* Empty State Styling */
    .ann-empty-container {
        text-align: center;
        padding: 56px 24px;
    }
    .ann-empty-icon-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(122, 26, 26, 0.4) 0%, rgba(30, 21, 21, 0.2) 70%);
        border: 1px solid rgba(212, 175, 55, 0.3);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        color: #D4AF37;
        margin-bottom: 20px;
        box-shadow: 0 0 24px rgba(212, 175, 55, 0.15);
    }
    .ann-empty-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #FCF8F2;
        margin-bottom: 8px;
    }
    .ann-empty-desc {
        color: #A39683;
        font-size: 0.9rem;
        max-width: 440px;
        margin: 0 auto 24px auto;
        line-height: 1.5;
    }

    /* Modals */
    .ann-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 1050;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        transition: opacity 0.25s ease;
    }
    .ann-modal-backdrop.show {
        display: flex;
        opacity: 1;
    }
    .ann-modal-card {
        width: 100%;
        max-width: 620px;
        background: linear-gradient(145deg, #1F1515 0%, #150D0D 100%);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.8), 0 0 20px rgba(212, 175, 55, 0.1);
        transform: scale(0.95);
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
    }
    .ann-modal-backdrop.show .ann-modal-card {
        transform: scale(1);
    }
    .ann-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid rgba(212, 175, 55, 0.15);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(122, 26, 26, 0.12);
    }
    .ann-modal-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #FCF8F2;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .ann-modal-close {
        background: transparent;
        border: none;
        color: #A39683;
        font-size: 1.1rem;
        cursor: pointer;
        padding: 4px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    .ann-modal-close:hover {
        color: #FCF8F2;
        background: rgba(255, 255, 255, 0.08);
    }
    .ann-modal-body {
        padding: 24px;
    }
    .ann-modal-footer {
        padding: 16px 24px;
        border-top: 1px solid rgba(212, 175, 55, 0.15);
        background: rgba(15, 9, 9, 0.6);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
    .ann-form-label {
        display: block;
        font-size: 0.82rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #D4AF37;
        margin-bottom: 8px;
    }
    .ann-form-control {
        width: 100%;
        background: rgba(13, 8, 8, 0.8);
        border: 1px solid rgba(212, 175, 55, 0.2);
        color: #FCF8F2;
        padding: 11px 14px;
        border-radius: 10px;
        font-size: 0.9rem;
        outline: none;
        transition: all 0.2s ease;
    }
    .ann-form-control:focus {
        border-color: #D4AF37;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.18);
        background: rgba(13, 8, 8, 0.95);
    }
    .ann-btn-ghost {
        background: transparent;
        color: #A39683;
        border: 1px solid rgba(255, 255, 255, 0.15);
        padding: 9px 18px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .ann-btn-ghost:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #FCF8F2;
    }
</style>

<!-- Alert Notifications -->
@if(session('success'))
<div style="background: rgba(52, 211, 153, 0.12); border: 1px solid rgba(52, 211, 153, 0.3); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; color: #34D399;">
    <i class="bi bi-check-circle-fill" style="font-size: 1.15rem;"></i>
    <div style="font-size: 0.9rem; font-weight: 500;">{{ session('success') }}</div>
</div>
@endif

@if(session('error'))
<div style="background: rgba(248, 113, 113, 0.12); border: 1px solid rgba(248, 113, 113, 0.3); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; color: #F87171;">
    <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.15rem;"></i>
    <div style="font-size: 0.9rem; font-weight: 500;">{{ session('error') }}</div>
</div>
@endif

<!-- Header Section -->
<div class="ann-header-bar">
    <div>
        <div class="ann-badge-pill">
            <i class="bi bi-broadcast"></i> Campus Communication Hub
        </div>
        <h1 class="ann-page-title">Announcements & Broadcasts</h1>
        <p class="ann-page-subtitle">Publish critical alerts, campus notices, and general announcements to students and staff.</p>
    </div>
    
    <div>
        <button type="button" class="ann-btn-primary" onclick="openModal('addAnnouncementModal')">
            <i class="bi bi-megaphone-fill"></i> New Announcement
        </button>
    </div>
</div>

<!-- Quick Statistics Bar -->
<div class="ann-stats-grid">
    <div class="ann-stat-card">
        <div class="ann-stat-icon-wrap ann-stat-icon-gold">
            <i class="bi bi-megaphone-fill"></i>
        </div>
        <div>
            <div class="ann-stat-num">{{ $stats['total'] ?? 0 }}</div>
            <div class="ann-stat-label">Total Broadcasts</div>
        </div>
    </div>
    <div class="ann-stat-card">
        <div class="ann-stat-icon-wrap ann-stat-icon-emerald">
            <i class="bi bi-globe2"></i>
        </div>
        <div>
            <div class="ann-stat-num">{{ $stats['all'] ?? 0 }}</div>
            <div class="ann-stat-label">Campus-Wide</div>
        </div>
    </div>
    <div class="ann-stat-card">
        <div class="ann-stat-icon-wrap ann-stat-icon-blue">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div>
            <div class="ann-stat-num">{{ $stats['student'] ?? 0 }}</div>
            <div class="ann-stat-label">Student Targeted</div>
        </div>
    </div>
    <div class="ann-stat-card">
        <div class="ann-stat-icon-wrap ann-stat-icon-amber">
            <i class="bi bi-person-workspace"></i>
        </div>
        <div>
            <div class="ann-stat-num">{{ $stats['teacher'] ?? 0 }}</div>
            <div class="ann-stat-label">Instructor Notices</div>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="ann-card">
    <div class="ann-card-header">
        <form method="GET" action="{{ route('admin.announcements.index') }}" class="ann-filter-form">
            <div class="ann-search-box">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="ann-input" placeholder="Search announcements by title or content..." value="{{ request('search') }}">
            </div>
            
            <select name="audience" class="ann-select">
                <option value="">All Audiences</option>
                <option value="all" {{ request('audience') === 'all' ? 'selected' : '' }}>Campus-Wide (Everyone)</option>
                <option value="student" {{ request('audience') === 'student' ? 'selected' : '' }}>Students Only</option>
                <option value="teacher" {{ request('audience') === 'teacher' ? 'selected' : '' }}>Instructors Only</option>
                <option value="parent" {{ request('audience') === 'parent' ? 'selected' : '' }}>Parents Only</option>
            </select>
            
            <button type="submit" class="ann-btn-filter">
                <i class="bi bi-funnel"></i> Filter
            </button>
            
            @if(request()->hasAny(['search', 'audience']))
                <a href="{{ route('admin.announcements.index') }}" class="ann-btn-clear">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            @endif
        </form>
    </div>

    <div class="ann-table-wrap">
        <table class="ann-table">
            <thead>
                <tr>
                    <th style="min-width: 280px;">Title & Summary</th>
                    <th style="min-width: 170px;">Posted By</th>
                    <th style="min-width: 150px;">Target Audience</th>
                    <th style="min-width: 140px;">Date Posted</th>
                    <th style="min-width: 110px;">Status</th>
                    <th style="min-width: 100px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($announcements as $announcement)
                <tr>
                    <td>
                        <div class="ann-title-text">{{ $announcement->title }}</div>
                        <div class="ann-snippet-text">
                            {{ strip_tags($announcement->content) }}
                        </div>
                    </td>
                    <td>
                        @php
                            $authorRole = $announcement->author->role ?? 'admin';
                            $isAdmin = strtolower($authorRole) === 'admin';
                        @endphp
                        <div style="display: flex; align-items: center; gap: 9px;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: {{ $isAdmin ? 'rgba(212,175,55,0.18)' : 'rgba(122,26,26,0.3)' }}; border: 1px solid {{ $isAdmin ? '#D4AF37' : 'rgba(212,175,55,0.3)' }}; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: {{ $isAdmin ? '#D4AF37' : '#FCF8F2' }};">
                                {{ strtoupper(substr($announcement->author->name ?? 'A', 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.88rem; color: #FCF8F2;">{{ $announcement->author->name ?? 'System Admin' }}</div>
                                <div style="font-size: 0.72rem; color: {{ $isAdmin ? '#D4AF37' : '#A39683' }}; font-weight: 500;">
                                    {{ $isAdmin ? 'Administrator' : 'Instructor' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @php
                            $aud = strtolower($announcement->target_audience ?? 'all');
                        @endphp
                        @if($aud === 'all')
                            <span class="ann-badge ann-badge-all">
                                <i class="bi bi-globe"></i> Campus Wide
                            </span>
                        @elseif($aud === 'student')
                            <span class="ann-badge ann-badge-student">
                                <i class="bi bi-mortarboard-fill"></i> Students
                            </span>
                        @elseif($aud === 'teacher')
                            <span class="ann-badge ann-badge-teacher">
                                <i class="bi bi-person-workspace"></i> Instructors
                            </span>
                        @elseif($aud === 'parent')
                            <span class="ann-badge ann-badge-parent">
                                <i class="bi bi-people-fill"></i> Parents
                            </span>
                        @else
                            <span class="ann-badge ann-badge-default">
                                <i class="bi bi-tag-fill"></i> {{ ucfirst($announcement->target_audience) }}
                            </span>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #FCF8F2;">{{ $announcement->created_at->format('M d, Y') }}</div>
                        <div style="font-size: 0.75rem; color: #A39683;">{{ $announcement->created_at->format('h:i A') }}</div>
                    </td>
                    <td>
                        <span class="ann-status-pill">
                            <span class="ann-status-dot"></span>
                            Published
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: inline-flex; gap: 8px;">
                            <button type="button" 
                                    class="ann-action-btn" 
                                    title="Edit Announcement"
                                    onclick="openEditModal({{ $announcement->id }}, '{{ addslashes($announcement->title) }}', '{{ strtolower($announcement->target_audience) }}', '{{ addslashes($announcement->content) }}')">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ann-action-btn ann-action-btn-danger" title="Delete Announcement">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="ann-empty-container">
                            <div class="ann-empty-icon-circle">
                                <i class="bi bi-megaphone"></i>
                            </div>
                            <h3 class="ann-empty-title">No announcements found</h3>
                            <p class="ann-empty-desc">
                                @if(request()->hasAny(['search', 'audience']))
                                    No announcements matched your search criteria. Try adjusting or clearing your filters.
                                @else
                                    Keep your campus community connected by broadcasting timely notices and updates.
                                @endif
                            </p>
                            @if(request()->hasAny(['search', 'audience']))
                                <a href="{{ route('admin.announcements.index') }}" class="ann-btn-clear" style="display: inline-flex; margin-right: 8px;">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                                </a>
                            @endif
                            <button type="button" class="ann-btn-primary" onclick="openModal('addAnnouncementModal')">
                                <i class="bi bi-plus-lg"></i> Create Announcement
                            </button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($announcements->hasPages())
    <div style="padding: 16px 24px; border-top: 1px solid rgba(212, 175, 55, 0.15); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: rgba(20, 12, 12, 0.5);">
        <div style="font-size: 0.84rem; color: #A39683;">
            Showing <span style="color: #D4AF37; font-weight: 600;">{{ $announcements->firstItem() ?? 0 }}</span> to <span style="color: #D4AF37; font-weight: 600;">{{ $announcements->lastItem() ?? 0 }}</span> of <span style="color: #FCF8F2; font-weight: 600;">{{ $announcements->total() }}</span> announcements
        </div>
        <div>
            {{ $announcements->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Add Announcement Modal -->
<div id="addAnnouncementModal" class="ann-modal-backdrop" onclick="if(event.target === this) closeModal('addAnnouncementModal')">
    <div class="ann-modal-card">
        <div class="ann-modal-header">
            <div class="ann-modal-title">
                <i class="bi bi-megaphone-fill text-warning"></i>
                <span>Broadcast New Announcement</span>
            </div>
            <button type="button" class="ann-modal-close" onclick="closeModal('addAnnouncementModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form action="{{ route('admin.announcements.store') }}" method="POST">
            @csrf
            <div class="ann-modal-body">
                <div style="margin-bottom: 18px;">
                    <label class="ann-form-label">Target Audience</label>
                    <select name="target_role" class="ann-form-control" required>
                        <option value="all">Campus Wide (Everyone)</option>
                        <option value="student">Students Only</option>
                        <option value="teacher">Instructors Only</option>
                        <option value="parent">Parents Only</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 18px;">
                    <label class="ann-form-label">Announcement Title</label>
                    <input type="text" name="title" class="ann-form-control" placeholder="e.g., Midterm Exam Schedule & Room Assignments" required>
                </div>
                
                <div style="margin-bottom: 0;">
                    <label class="ann-form-label">Message Content</label>
                    <textarea name="content" class="ann-form-control" rows="6" placeholder="Compose your announcement details, instructions, or deadlines..." required></textarea>
                </div>
            </div>
            <div class="ann-modal-footer">
                <button type="button" class="ann-btn-ghost" onclick="closeModal('addAnnouncementModal')">Cancel</button>
                <button type="submit" class="ann-btn-primary">
                    <i class="bi bi-send-fill"></i> Publish Broadcast
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div id="editAnnouncementModal" class="ann-modal-backdrop" onclick="if(event.target === this) closeModal('editAnnouncementModal')">
    <div class="ann-modal-card">
        <div class="ann-modal-header">
            <div class="ann-modal-title">
                <i class="bi bi-pencil-square text-warning"></i>
                <span>Edit Announcement</span>
            </div>
            <button type="button" class="ann-modal-close" onclick="closeModal('editAnnouncementModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="editAnnouncementForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="ann-modal-body">
                <div style="margin-bottom: 18px;">
                    <label class="ann-form-label">Target Audience</label>
                    <select name="target_role" id="edit_target_role" class="ann-form-control" required>
                        <option value="all">Campus Wide (Everyone)</option>
                        <option value="student">Students Only</option>
                        <option value="teacher">Instructors Only</option>
                        <option value="parent">Parents Only</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 18px;">
                    <label class="ann-form-label">Announcement Title</label>
                    <input type="text" name="title" id="edit_title" class="ann-form-control" required>
                </div>
                
                <div style="margin-bottom: 0;">
                    <label class="ann-form-label">Message Content</label>
                    <textarea name="content" id="edit_content" class="ann-form-control" rows="6" required></textarea>
                </div>
            </div>
            <div class="ann-modal-footer">
                <button type="button" class="ann-btn-ghost" onclick="closeModal('editAnnouncementModal')">Cancel</button>
                <button type="submit" class="ann-btn-primary">
                    <i class="bi bi-check2-circle"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script @cspNonce>
    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('show');
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('show');
    }

    function openEditModal(id, title, audience, content) {
        const form = document.getElementById('editAnnouncementForm');
        form.action = "{{ url('admin/announcements') }}/" + id;
        
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_content').value = content;
        
        const audienceSelect = document.getElementById('edit_target_role');
        if (audienceSelect) {
            audienceSelect.value = audience || 'all';
        }
        
        openModal('editAnnouncementModal');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('addAnnouncementModal');
            closeModal('editAnnouncementModal');
        }
    });
</script>
@endpush
