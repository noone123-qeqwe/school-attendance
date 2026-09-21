@extends('layouts.app')

@section('title', 'Parents & Guardians')

@section('content')
<style>
    /* Scoped Parents Dashboard Styles */
    .par-header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .par-badge-pill {
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
    .par-page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #FCF8F2;
        margin: 0 0 6px 0;
        letter-spacing: -0.02em;
    }
    .par-page-subtitle {
        color: #A39683;
        font-size: 0.9rem;
        margin: 0;
    }
    .par-btn-primary {
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
    .par-btn-primary:hover {
        background: linear-gradient(135deg, #8E1F1F 0%, #B22E2E 100%);
        box-shadow: 0 6px 20px rgba(122, 26, 26, 0.6), 0 0 12px rgba(212, 175, 55, 0.25);
        transform: translateY(-1px);
        color: #FFFFFF !important;
    }
    
    /* Quick Stats Grid */
    .par-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .par-stat-card {
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
    .par-stat-card:hover {
        border-color: rgba(212, 175, 55, 0.35);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        transform: translateY(-2px);
    }
    .par-stat-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .par-stat-icon-gold {
        background: rgba(212, 175, 55, 0.12);
        color: #D4AF37;
        border: 1px solid rgba(212, 175, 55, 0.25);
    }
    .par-stat-icon-emerald {
        background: rgba(52, 211, 153, 0.12);
        color: #34D399;
        border: 1px solid rgba(52, 211, 153, 0.25);
    }
    .par-stat-icon-blue {
        background: rgba(96, 165, 250, 0.12);
        color: #60A5FA;
        border: 1px solid rgba(96, 165, 250, 0.25);
    }
    .par-stat-icon-amber {
        background: rgba(251, 191, 36, 0.12);
        color: #FBBF24;
        border: 1px solid rgba(251, 191, 36, 0.25);
    }
    .par-stat-num {
        font-size: 1.4rem;
        font-weight: 700;
        color: #FCF8F2;
        line-height: 1.2;
    }
    .par-stat-label {
        font-size: 0.78rem;
        color: #A39683;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 500;
    }

    /* Main Container Card */
    .par-card {
        background: rgba(26, 17, 17, 0.75);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(212, 175, 55, 0.18);
        border-radius: 18px;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45);
        overflow: hidden;
        margin-bottom: 24px;
    }
    .par-card-header {
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
    .par-filter-form {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        width: 100%;
    }
    .par-search-box {
        position: relative;
        flex: 1;
        min-width: 260px;
    }
    .par-search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #A39683;
        font-size: 0.9rem;
        pointer-events: none;
    }
    .par-input {
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
    .par-input:focus {
        border-color: #D4AF37;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
        background: rgba(17, 10, 10, 0.95);
    }
    .par-select {
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
    .par-select:focus {
        border-color: #D4AF37;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
    }
    .par-select option {
        background-color: #1A1111;
        color: #FCF8F2;
    }
    .par-btn-filter {
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
    .par-btn-filter:hover {
        background: rgba(212, 175, 55, 0.22);
        border-color: #D4AF37;
        color: #FFF;
    }
    .par-btn-clear {
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
    .par-btn-clear:hover {
        background: rgba(248, 113, 113, 0.12);
        border-color: #F87171;
        color: #FCA5A5;
    }

    /* Parents Table */
    .par-table-wrap {
        width: 100%;
        overflow-x: auto;
    }
    .par-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    .par-table th {
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
    .par-table td {
        padding: 16px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        color: #FCF8F2;
        vertical-align: middle;
        font-size: 0.88rem;
    }
    .par-table tbody tr {
        transition: background 0.2s ease;
    }
    .par-table tbody tr:hover {
        background: rgba(212, 175, 55, 0.035);
    }
    .par-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Linked Children Tags */
    .par-child-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.76rem;
        font-weight: 600;
        background: rgba(96, 165, 250, 0.12);
        color: #93C5FD;
        border: 1px solid rgba(96, 165, 250, 0.25);
        margin: 2px 4px 2px 0;
    }
    .par-child-unlink-btn {
        background: none;
        border: none;
        color: #F87171;
        cursor: pointer;
        padding: 0 0 0 4px;
        font-size: 0.8rem;
        line-height: 1;
        opacity: 0.8;
        transition: opacity 0.2s;
    }
    .par-child-unlink-btn:hover {
        opacity: 1;
    }

    /* Status Pill */
    .par-status-active {
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
    .par-status-inactive {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 8px;
        font-size: 0.74rem;
        font-weight: 600;
        background: rgba(248, 113, 113, 0.12);
        color: #F87171;
        border: 1px solid rgba(248, 113, 113, 0.25);
    }
    .par-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    /* Action Buttons */
    .par-action-btn {
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
    .par-action-btn:hover {
        background: rgba(212, 175, 55, 0.15);
        color: #D4AF37;
        border-color: #D4AF37;
        transform: translateY(-1px);
    }
    .par-action-btn-danger:hover {
        background: rgba(248, 113, 113, 0.15);
        color: #F87171;
        border-color: #F87171;
    }
    .par-action-btn-success:hover {
        background: rgba(52, 211, 153, 0.15);
        color: #34D399;
        border-color: #34D399;
    }

    /* Empty State */
    .par-empty-container {
        text-align: center;
        padding: 56px 24px;
    }
    .par-empty-icon-circle {
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

    /* Modals */
    .par-modal-backdrop {
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
    .par-modal-backdrop.show {
        display: flex;
        opacity: 1;
    }
    .par-modal-card {
        width: 100%;
        max-width: 580px;
        background: linear-gradient(145deg, #1F1515 0%, #150D0D 100%);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.8), 0 0 20px rgba(212, 175, 55, 0.1);
        transform: scale(0.95);
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
    }
    .par-modal-backdrop.show .par-modal-card {
        transform: scale(1);
    }
    .par-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid rgba(212, 175, 55, 0.15);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(122, 26, 26, 0.12);
    }
    .par-modal-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #FCF8F2;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .par-modal-close {
        background: transparent;
        border: none;
        color: #A39683;
        font-size: 1.1rem;
        cursor: pointer;
        padding: 4px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    .par-modal-close:hover {
        color: #FCF8F2;
        background: rgba(255, 255, 255, 0.08);
    }
    .par-modal-body {
        padding: 24px;
    }
    .par-modal-footer {
        padding: 16px 24px;
        border-top: 1px solid rgba(212, 175, 55, 0.15);
        background: rgba(15, 9, 9, 0.6);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
    .par-form-label {
        display: block;
        font-size: 0.82rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #D4AF37;
        margin-bottom: 8px;
    }
    .par-form-control {
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
    .par-form-control:focus {
        border-color: #D4AF37;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.18);
        background: rgba(13, 8, 8, 0.95);
    }
    .par-btn-ghost {
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
    .par-btn-ghost:hover {
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

@if($errors->any())
<div style="background: rgba(248, 113, 113, 0.12); border: 1px solid rgba(248, 113, 113, 0.3); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #F87171;">
    <div style="font-weight: 600; margin-bottom: 6px;">Please correct the following errors:</div>
    <ul style="margin: 0; padding-left: 20px; font-size: 0.88rem;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Header Section -->
<div class="par-header-bar">
    <div>
        <div class="par-badge-pill">
            <i class="bi bi-people-fill"></i> Parent &amp; Guardian Directory
        </div>
        <h1 class="par-page-title">Parents &amp; Guardians</h1>
        <p class="par-page-subtitle">Manage registered parent accounts, track verified student guardians, and handle portal linkages.</p>
    </div>
    
    <div>
        <button type="button" class="par-btn-primary" onclick="openModal('addParentModal')">
            <i class="bi bi-person-plus-fill"></i> Register Parent
        </button>
    </div>
</div>

<!-- Quick Statistics Bar -->
<div class="par-stats-grid">
    <div class="par-stat-card">
        <div class="par-stat-icon-wrap par-stat-icon-gold">
            <i class="bi bi-people-fill"></i>
        </div>
        <div>
            <div class="par-stat-num">{{ $stats['total'] ?? 0 }}</div>
            <div class="par-stat-label">Total Parents</div>
        </div>
    </div>
    <div class="par-stat-card">
        <div class="par-stat-icon-wrap par-stat-icon-emerald">
            <i class="bi bi-shield-check"></i>
        </div>
        <div>
            <div class="par-stat-num">{{ $stats['active'] ?? 0 }}</div>
            <div class="par-stat-label">Active Accounts</div>
        </div>
    </div>
    <div class="par-stat-card">
        <div class="par-stat-icon-wrap par-stat-icon-blue">
            <i class="bi bi-link-45deg"></i>
        </div>
        <div>
            <div class="par-stat-num">{{ $stats['linked'] ?? 0 }}</div>
            <div class="par-stat-label">Linked to Students</div>
        </div>
    </div>
    <div class="par-stat-card">
        <div class="par-stat-icon-wrap par-stat-icon-amber">
            <i class="bi bi-person-dash-fill"></i>
        </div>
        <div>
            <div class="par-stat-num">{{ $stats['unlinked'] ?? 0 }}</div>
            <div class="par-stat-label">Unlinked Parents</div>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="par-card">
    <div class="par-card-header">
        <form method="GET" action="{{ route('admin.parents.index') }}" class="par-filter-form">
            <div class="par-search-box">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="par-input" placeholder="Search by name, email, phone, or linked student..." value="{{ request('search') }}">
            </div>
            
            <select name="status" class="par-select">
                <option value="">All Records</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Accounts</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Deactivated Accounts</option>
                <option value="linked" {{ request('status') === 'linked' ? 'selected' : '' }}>Has Linked Students</option>
                <option value="unlinked" {{ request('status') === 'unlinked' ? 'selected' : '' }}>No Linked Students</option>
            </select>
            
            <button type="submit" class="par-btn-filter">
                <i class="bi bi-funnel"></i> Filter
            </button>
            
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.parents.index') }}" class="par-btn-clear">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            @endif
        </form>
    </div>

    <div class="par-table-wrap">
        <table class="par-table">
            <thead>
                <tr>
                    <th style="min-width: 220px;">Parent / Guardian</th>
                    <th style="min-width: 150px;">Contact Number</th>
                    <th style="min-width: 240px;">Connected Students</th>
                    <th style="min-width: 110px;">Status</th>
                    <th style="min-width: 130px;">Joined</th>
                    <th style="min-width: 140px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parents as $parent)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(212,175,55,0.15); border: 1px solid rgba(212,175,55,0.3); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; color: #D4AF37;">
                                {{ strtoupper(substr($parent->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.9rem; color: #FCF8F2;">{{ $parent->name }}</div>
                                <div style="font-size: 0.78rem; color: #A39683;">{{ $parent->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($parent->phone)
                            <div style="font-size: 0.85rem; color: #D1C5B4;">
                                <i class="bi bi-telephone-fill me-1 text-warning" style="font-size: 0.75rem;"></i>
                                {{ $parent->phone }}
                            </div>
                        @else
                            <span style="font-size: 0.8rem; color: #736759;">Not provided</span>
                        @endif
                    </td>
                    <td>
                        @if($parent->children->isNotEmpty())
                            <div style="display: flex; flex-wrap: wrap; gap: 4px; align-items: center;">
                                @foreach($parent->children as $child)
                                    <span class="par-child-chip">
                                        <i class="bi bi-mortarboard-fill"></i>
                                        <span>{{ $child->name }}</span>
                                        <form action="{{ route('admin.parents.unlink_student', ['parent' => $parent->id, 'student' => $child->id]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Unlink {{ $child->name }} from {{ $parent->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="par-child-unlink-btn" title="Unlink Student">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        </form>
                                    </span>
                                @endforeach
                                <button type="button" 
                                        onclick="openLinkModal({{ $parent->id }}, '{{ addslashes($parent->name) }}')"
                                        class="btn btn-sm" 
                                        style="font-size: 0.72rem; padding: 2px 8px; border-radius: 6px; background: rgba(212,175,55,0.12); color: #D4AF37; border: 1px dashed rgba(212,175,55,0.3);" 
                                        title="Link Another Student">
                                    <i class="bi bi-plus"></i> Add
                                </button>
                            </div>
                        @else
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 0.8rem; color: #A39683; font-style: italic;">No student linked</span>
                                <button type="button" 
                                        onclick="openLinkModal({{ $parent->id }}, '{{ addslashes($parent->name) }}')"
                                        class="btn btn-sm" 
                                        style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px; background: rgba(212,175,55,0.15); color: #D4AF37; border: 1px solid rgba(212,175,55,0.3);">
                                    <i class="bi bi-link-45deg"></i> Link Student
                                </button>
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($parent->is_active ?? true)
                            <span class="par-status-active">
                                <span class="par-status-dot" style="background:#34D399; box-shadow:0 0 6px #34D399;"></span>
                                Active
                            </span>
                        @else
                            <span class="par-status-inactive">
                                <span class="par-status-dot" style="background:#F87171;"></span>
                                Deactivated
                            </span>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: 500; font-size: 0.85rem; color: #FCF8F2;">{{ $parent->created_at->format('M d, Y') }}</div>
                        <div style="font-size: 0.72rem; color: #A39683;">{{ $parent->created_at->diffForHumans() }}</div>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: inline-flex; gap: 6px;">
                            <button type="button" 
                                    class="par-action-btn" 
                                    title="Edit Parent"
                                    onclick="openEditModal({{ $parent->id }}, '{{ addslashes($parent->name) }}', '{{ addslashes($parent->email) }}', '{{ addslashes($parent->phone ?? '') }}')">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            
                            <button type="button" 
                                    class="par-action-btn" 
                                    title="Link Student"
                                    onclick="openLinkModal({{ $parent->id }}, '{{ addslashes($parent->name) }}')">
                                <i class="bi bi-link-45deg"></i>
                            </button>

                            @if($parent->is_active ?? true)
                                <form action="{{ route('admin.parents.deactivate', $parent) }}" method="POST" style="display: inline;" onsubmit="return confirm('Deactivate account for {{ $parent->name }}?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="par-action-btn par-action-btn-danger" title="Deactivate Parent">
                                        <i class="bi bi-slash-circle"></i>
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.parents.reactivate', $parent->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Reactivate account for {{ $parent->name }}?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="par-action-btn par-action-btn-success" title="Reactivate Parent">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('admin.parents.destroy', $parent) }}" method="POST" style="display: inline;" onsubmit="return confirm('Permanently delete {{ $parent->name }}? This action cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="par-action-btn par-action-btn-danger" title="Delete Account">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="par-empty-container">
                            <div class="par-empty-icon-circle">
                                <i class="bi bi-people"></i>
                            </div>
                            <h3 style="font-size: 1.25rem; font-weight: 700; color: #FCF8F2; margin-bottom: 8px;">No parent accounts found</h3>
                            <p style="color: #A39683; font-size: 0.9rem; max-width: 440px; margin: 0 auto 24px auto;">
                                @if(request()->hasAny(['search', 'status']))
                                    No records matched your search filters. Try adjusting or clearing them.
                                @else
                                    Register parents to allow them access to student attendance monitoring and real-time alerts.
                                @endif
                            </p>
                            @if(request()->hasAny(['search', 'status']))
                                <a href="{{ route('admin.parents.index') }}" class="par-btn-clear" style="display: inline-flex; margin-right: 8px;">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                                </a>
                            @endif
                            <button type="button" class="par-btn-primary" onclick="openModal('addParentModal')">
                                <i class="bi bi-plus-lg"></i> Register Parent
                            </button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($parents->hasPages())
    <div style="padding: 16px 24px; border-top: 1px solid rgba(212, 175, 55, 0.15); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: rgba(20, 12, 12, 0.5);">
        <div style="font-size: 0.84rem; color: #A39683;">
            Showing <span style="color: #D4AF37; font-weight: 600;">{{ $parents->firstItem() ?? 0 }}</span> to <span style="color: #D4AF37; font-weight: 600;">{{ $parents->lastItem() ?? 0 }}</span> of <span style="color: #FCF8F2; font-weight: 600;">{{ $parents->total() }}</span> parents
        </div>
        <div>
            {{ $parents->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Add Parent Modal -->
<div id="addParentModal" class="par-modal-backdrop" onclick="if(event.target === this) closeModal('addParentModal')">
    <div class="par-modal-card">
        <div class="par-modal-header">
            <div class="par-modal-title">
                <i class="bi bi-person-plus-fill text-warning"></i>
                <span>Register Parent / Guardian</span>
            </div>
            <button type="button" class="par-modal-close" onclick="closeModal('addParentModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form action="{{ route('admin.parents.store') }}" method="POST">
            @csrf
            <div class="par-modal-body">
                <div style="margin-bottom: 16px;">
                    <label class="par-form-label">Full Name</label>
                    <input type="text" name="name" class="par-form-control" placeholder="e.g. Maria Santos" required>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label class="par-form-label">Gmail Address</label>
                    <input type="email" name="email" class="par-form-control" placeholder="e.g. maria.santos@gmail.com" required>
                    <div style="font-size: 0.75rem; color: #A39683; margin-top: 4px;">Must be a valid Gmail account for authentication &amp; OTP security.</div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="par-form-label">Contact Phone Number (Optional)</label>
                    <input type="text" name="phone" class="par-form-control" placeholder="e.g. 09171234567">
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="par-form-label">Initial Password (Optional)</label>
                    <input type="password" name="password" class="par-form-control" placeholder="Leave empty for default (Parent@{{ date('Y') }})">
                </div>

                <div style="margin-bottom: 0;">
                    <label class="par-form-label">Initial Student to Link (Optional)</label>
                    <select name="student_id" class="par-form-control">
                        <option value="">-- Select Student to Link --</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">
                                {{ $student->name }} ({{ $student->student_number ?? 'No ID' }} - {{ $student->course ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="par-modal-footer">
                <button type="button" class="par-btn-ghost" onclick="closeModal('addParentModal')">Cancel</button>
                <button type="submit" class="par-btn-primary">
                    <i class="bi bi-check2-circle"></i> Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Parent Modal -->
<div id="editParentModal" class="par-modal-backdrop" onclick="if(event.target === this) closeModal('editParentModal')">
    <div class="par-modal-card">
        <div class="par-modal-header">
            <div class="par-modal-title">
                <i class="bi bi-pencil-square text-warning"></i>
                <span>Edit Parent Account</span>
            </div>
            <button type="button" class="par-modal-close" onclick="closeModal('editParentModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="editParentForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="par-modal-body">
                <div style="margin-bottom: 16px;">
                    <label class="par-form-label">Full Name</label>
                    <input type="text" name="name" id="edit_name" class="par-form-control" required>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label class="par-form-label">Gmail Address</label>
                    <input type="email" name="email" id="edit_email" class="par-form-control" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="par-form-label">Contact Phone Number</label>
                    <input type="text" name="phone" id="edit_phone" class="par-form-control">
                </div>

                <div style="margin-bottom: 0;">
                    <label class="par-form-label">New Password (Optional)</label>
                    <input type="password" name="password" class="par-form-control" placeholder="Leave empty to keep current password">
                </div>
            </div>
            <div class="par-modal-footer">
                <button type="button" class="par-btn-ghost" onclick="closeModal('editParentModal')">Cancel</button>
                <button type="submit" class="par-btn-primary">
                    <i class="bi bi-check2-circle"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Link Student Modal -->
<div id="linkStudentModal" class="par-modal-backdrop" onclick="if(event.target === this) closeModal('linkStudentModal')">
    <div class="par-modal-card">
        <div class="par-modal-header">
            <div class="par-modal-title">
                <i class="bi bi-link-45deg text-warning"></i>
                <span>Link Student to <span id="linkModalParentName" style="color: #D4AF37;"></span></span>
            </div>
            <button type="button" class="par-modal-close" onclick="closeModal('linkStudentModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="linkStudentForm" method="POST" action="">
            @csrf
            <div class="par-modal-body">
                <div style="margin-bottom: 0;">
                    <label class="par-form-label">Select Student</label>
                    <select name="student_id" class="par-form-control" required>
                        <option value="">-- Choose Student --</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">
                                {{ $student->name }} ({{ $student->student_number ?? 'No ID' }} - {{ $student->course ?? 'N/A' }} {{ $student->year_level ? 'Yr ' . $student->year_level : '' }})
                            </option>
                        @endforeach
                    </select>
                    <div style="font-size: 0.75rem; color: #A39683; margin-top: 6px;">
                        This will grant this parent access to view attendance, excuse submissions, and schedule for this student.
                    </div>
                </div>
            </div>
            <div class="par-modal-footer">
                <button type="button" class="par-btn-ghost" onclick="closeModal('linkStudentModal')">Cancel</button>
                <button type="submit" class="par-btn-primary">
                    <i class="bi bi-link"></i> Link Student
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

    function openEditModal(id, name, email, phone) {
        const form = document.getElementById('editParentForm');
        form.action = "{{ url('admin/parents') }}/" + id;
        
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_phone').value = phone || '';
        
        openModal('editParentModal');
    }

    function openLinkModal(parentId, parentName) {
        const form = document.getElementById('linkStudentForm');
        form.action = "{{ url('admin/parents') }}/" + parentId + "/link-student";
        document.getElementById('linkModalParentName').textContent = parentName;
        
        openModal('linkStudentModal');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('addParentModal');
            closeModal('editParentModal');
            closeModal('linkStudentModal');
        }
    });
</script>
@endpush
