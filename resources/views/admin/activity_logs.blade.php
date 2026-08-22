@extends('layouts.app')

@section('title', 'Enterprise Audit Logs')

@section('content')
<style>
    /* ── Audit Logs Core Theme & Variables ── */
    .audit-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .audit-title-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .audit-title-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(207, 164, 111, 0.2) 0%, rgba(143, 110, 74, 0.1) 100%);
        border: 1px solid rgba(207, 164, 111, 0.35);
        color: #CFA46F;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35), 0 0 15px rgba(207, 164, 111, 0.15);
    }

    /* ── KPI Stats Grid ── */
    .audit-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .audit-kpi-card {
        background: rgba(26, 17, 16, 0.75);
        border: 1px solid rgba(207, 164, 111, 0.22);
        border-radius: 16px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
    }

    .audit-kpi-card:hover {
        transform: translateY(-2px);
        border-color: rgba(207, 164, 111, 0.45);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35), 0 0 20px rgba(207, 164, 111, 0.12);
    }

    .audit-kpi-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .kpi-total { background: rgba(96, 165, 250, 0.15); color: #60A5FA; border: 1px solid rgba(96, 165, 250, 0.3); }
    .kpi-auth { background: rgba(74, 222, 128, 0.15); color: #4ADE80; border: 1px solid rgba(74, 222, 128, 0.3); }
    .kpi-mutation { background: rgba(251, 191, 36, 0.15); color: #FBBF24; border: 1px solid rgba(251, 191, 36, 0.3); }
    .kpi-operators { background: rgba(192, 132, 252, 0.15); color: #C084FC; border: 1px solid rgba(192, 132, 252, 0.3); }

    .audit-kpi-val {
        font-size: 1.55rem;
        font-weight: 800;
        color: #F3E7CD;
        line-height: 1.1;
        margin-bottom: 3px;
        font-family: 'Inter', sans-serif;
    }

    .audit-kpi-lbl {
        font-size: 0.78rem;
        font-weight: 600;
        color: #B39B82;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .audit-kpi-sub {
        font-size: 0.74rem;
        color: rgba(243, 231, 205, 0.6);
        margin-top: 2px;
    }

    /* ── Controls & Filter Panel ── */
    .audit-panel {
        background: rgba(26, 17, 16, 0.85);
        border: 1px solid rgba(207, 164, 111, 0.25);
        border-radius: 18px;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
        overflow: hidden;
        margin-bottom: 24px;
    }

    .audit-filter-bar {
        padding: 18px 22px;
        border-bottom: 1px solid rgba(207, 164, 111, 0.15);
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .audit-pill-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }

    .audit-pill-btn {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #B39B82;
        padding: 6px 14px;
        border-radius: 99px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }

    .audit-pill-btn:hover {
        background: rgba(207, 164, 111, 0.12);
        color: #F3E7CD;
        border-color: rgba(207, 164, 111, 0.35);
    }

    .audit-pill-btn.active {
        background: linear-gradient(135deg, #CFA46F 0%, #8F6E4A 100%);
        color: #110A0A;
        border-color: transparent;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(207, 164, 111, 0.35);
    }

    .audit-search-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }

    .audit-search-input-wrap {
        position: relative;
        flex: 1;
        min-width: 240px;
    }

    .audit-search-input-wrap i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #B39B82;
        font-size: 0.9rem;
    }

    .audit-search-input {
        width: 100%;
        padding: 9px 14px 9px 38px;
        background: rgba(17, 10, 10, 0.75);
        border: 1px solid rgba(207, 164, 111, 0.25);
        border-radius: 10px;
        color: #F3E7CD;
        font-size: 0.88rem;
        transition: all 0.2s;
    }

    .audit-search-input:focus {
        outline: none;
        border-color: #CFA46F;
        box-shadow: 0 0 0 3px rgba(207, 164, 111, 0.2);
    }

    .audit-select {
        padding: 9px 32px 9px 14px;
        background: rgba(17, 10, 10, 0.75) url('data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 16 16\'%3e%3cpath fill=\'none\' stroke=\'%23cfa46f\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'m2 5 6 6 6-6\'/%3e%3c/svg%3e') no-repeat right 12px center;
        background-size: 14px 10px;
        border: 1px solid rgba(207, 164, 111, 0.25);
        border-radius: 10px;
        color: #F3E7CD;
        font-size: 0.85rem;
        appearance: none;
        cursor: pointer;
    }

    .audit-select:focus {
        outline: none;
        border-color: #CFA46F;
    }

    .audit-date-input {
        padding: 9px 14px;
        background: rgba(17, 10, 10, 0.75);
        border: 1px solid rgba(207, 164, 111, 0.25);
        border-radius: 10px;
        color: #F3E7CD;
        font-size: 0.85rem;
        width: 150px;
    }

    .audit-date-input:focus {
        outline: none;
        border-color: #CFA46F;
    }

    /* ── Table Styling ── */
    .audit-table-wrap {
        overflow-x: auto;
        border-radius: 0;
    }

    .audit-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        color: #F3E7CD;
        font-size: 0.88rem;
    }

    .audit-table th {
        background: rgba(17, 10, 10, 0.95);
        color: #B39B82;
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 14px 18px;
        border-bottom: 1px solid rgba(207, 164, 111, 0.2);
        white-space: nowrap;
    }

    .audit-table td {
        padding: 14px 18px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        vertical-align: middle;
    }

    .audit-table tr:hover td {
        background: rgba(207, 164, 111, 0.04);
    }

    /* ── Group Headers ── */
    .audit-date-group-row td {
        background: rgba(20, 13, 13, 0.95) !important;
        padding: 10px 18px;
        border-bottom: 1px solid rgba(207, 164, 111, 0.25);
        border-top: 1px solid rgba(207, 164, 111, 0.15);
        font-weight: 700;
        font-size: 0.82rem;
        color: #CFA46F;
        letter-spacing: 0.3px;
    }

    /* ── Badges & Chips ── */
    .audit-action-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.2px;
        white-space: nowrap;
    }

    .action-created { background: rgba(74, 222, 128, 0.15); color: #4ADE80; border: 1px solid rgba(74, 222, 128, 0.3); }
    .action-updated { background: rgba(251, 191, 36, 0.15); color: #FBBF24; border: 1px solid rgba(251, 191, 36, 0.3); }
    .action-deleted { background: rgba(248, 113, 113, 0.15); color: #F87171; border: 1px solid rgba(248, 113, 113, 0.3); }
    .action-login   { background: rgba(96, 165, 250, 0.15); color: #60A5FA; border: 1px solid rgba(96, 165, 250, 0.3); }
    .action-system  { background: rgba(192, 132, 252, 0.15); color: #C084FC; border: 1px solid rgba(192, 132, 252, 0.3); }

    .audit-user-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .audit-avatar-init {
        width: 32px;
        height: 32px;
        border-radius: 99px;
        background: linear-gradient(135deg, rgba(207,164,111,0.25), rgba(143,110,74,0.15));
        border: 1px solid rgba(207,164,111,0.4);
        color: #F3E7CD;
        font-weight: 700;
        font-size: 0.78rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .audit-ip-chip {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.78rem;
        background: rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 3px 8px;
        border-radius: 6px;
        color: #B39B82;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .audit-entity-chip {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.8rem;
        color: #E5C394;
        background: rgba(207, 164, 111, 0.1);
        border: 1px solid rgba(207, 164, 111, 0.25);
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .audit-inspect-btn {
        background: rgba(207, 164, 111, 0.12);
        color: #CFA46F;
        border: 1px solid rgba(207, 164, 111, 0.35);
        border-radius: 8px;
        padding: 5px 12px;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .audit-inspect-btn:hover {
        background: linear-gradient(135deg, #CFA46F 0%, #8F6E4A 100%);
        color: #110A0A;
        border-color: transparent;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(207, 164, 111, 0.3);
    }

    /* ── Inspector Modal ── */
    .audit-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        z-index: 999999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: auditFadeIn 0.25s ease;
    }

    .audit-modal-dialog {
        background: rgba(24, 15, 14, 0.98);
        border: 1px solid rgba(207, 164, 111, 0.35);
        border-radius: 20px;
        max-width: 680px;
        width: 100%;
        max-height: 88vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.7), 0 0 30px rgba(207, 164, 111, 0.15);
        overflow: hidden;
    }

    .audit-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid rgba(207, 164, 111, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .audit-modal-body {
        padding: 20px 24px;
        overflow-y: auto;
        flex: 1;
    }

    .audit-modal-footer {
        padding: 16px 24px;
        border-top: 1px solid rgba(207, 164, 111, 0.2);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: rgba(17, 10, 10, 0.6);
    }

    .diff-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 16px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        overflow: hidden;
    }

    .diff-table th {
        background: rgba(0, 0, 0, 0.4);
        padding: 10px 14px;
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #B39B82;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .diff-table td {
        padding: 10px 14px;
        font-size: 0.82rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        word-break: break-all;
    }

    .diff-old {
        background: rgba(239, 68, 68, 0.1);
        color: #F87171;
    }

    .diff-new {
        background: rgba(34, 197, 94, 0.1);
        color: #4ADE80;
    }

    .json-code-box {
        background: #0D0808;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 14px;
        color: #E2E8F0;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.8rem;
        max-height: 250px;
        overflow: auto;
        white-space: pre-wrap;
    }

    @keyframes auditFadeIn {
        from { opacity: 0; transform: scale(0.97); }
        to { opacity: 1; transform: scale(1); }
    }
</style>

<!-- Page Header -->
<div class="audit-page-header">
    <div class="audit-title-wrap">
        <div class="audit-title-icon">
            <i class="bi bi-shield-check"></i>
        </div>
        <div>
            <h1 class="saas-heading saas-heading-lg" style="margin-bottom: 3px;">Enterprise Audit Logs</h1>
            <p class="saas-text-muted" style="margin: 0; font-size: 0.88rem;">
                Immutable system activity ledger, authentication tracking, and resource lifecycle audits.
            </p>
        </div>
    </div>
    
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <span class="audit-ip-chip" style="color: #4ADE80; border-color: rgba(74,222,128,0.3); background: rgba(74,222,128,0.08);">
            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Audit Stream Active
        </span>
        <a href="{{ route('admin.activity.log.export', request()->all()) }}" class="saas-btn saas-btn-secondary" style="text-decoration: none; padding: 8px 16px; font-size: 0.85rem;">
            <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> Export CSV
        </a>
    </div>
</div>

<!-- KPI Stats Grid -->
<div class="audit-kpi-grid">
    <div class="audit-kpi-card">
        <div class="audit-kpi-icon kpi-total">
            <i class="bi bi-activity"></i>
        </div>
        <div>
            <div class="audit-kpi-val">{{ number_format($totalLogsCount ?? 0) }}</div>
            <div class="audit-kpi-lbl">Total Audit Events</div>
            <div class="audit-kpi-sub">+{{ number_format($todayLogsCount ?? 0) }} recorded today</div>
        </div>
    </div>

    <div class="audit-kpi-card">
        <div class="audit-kpi-icon kpi-auth">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div>
            <div class="audit-kpi-val">{{ number_format($authLogsCount ?? 0) }}</div>
            <div class="audit-kpi-lbl">Authentication & Access</div>
            <div class="audit-kpi-sub">Logins, biometrics & 2FA</div>
        </div>
    </div>

    <div class="audit-kpi-card">
        <div class="audit-kpi-icon kpi-mutation">
            <i class="bi bi-pencil-square"></i>
        </div>
        <div>
            <div class="audit-kpi-val">{{ number_format($mutationLogsCount ?? 0) }}</div>
            <div class="audit-kpi-lbl">Data Mutations</div>
            <div class="audit-kpi-sub">Creations, edits & removals</div>
        </div>
    </div>

    <div class="audit-kpi-card">
        <div class="audit-kpi-icon kpi-operators">
            <i class="bi bi-people-fill"></i>
        </div>
        <div>
            <div class="audit-kpi-val">{{ number_format($uniqueCausersCount ?? 0) }}</div>
            <div class="audit-kpi-lbl">Active Operators</div>
            <div class="audit-kpi-sub">Unique causers identified</div>
        </div>
    </div>
</div>

<!-- Main Audit Panel -->
<div class="audit-panel">
    <!-- Filter & Search Console -->
    <div class="audit-filter-bar">
        <!-- Quick Filter Tabs -->
        <div class="audit-pill-tabs">
            <span style="font-size: 0.78rem; font-weight: 700; color: #B39B82; text-transform: uppercase; margin-right: 4px;">Quick Filter:</span>
            <a href="{{ route('admin.activity.log') }}" class="audit-pill-btn {{ !request()->has('action') ? 'active' : '' }}">
                <i class="bi bi-grid-fill"></i> All Events
            </a>
            <a href="{{ route('admin.activity.log', array_merge(request()->except('page'), ['action' => 'login'])) }}" class="audit-pill-btn {{ request('action') === 'login' ? 'active' : '' }}">
                <i class="bi bi-key-fill"></i> Logins & Auth
            </a>
            <a href="{{ route('admin.activity.log', array_merge(request()->except('page'), ['action' => 'created'])) }}" class="audit-pill-btn {{ request('action') === 'created' ? 'active' : '' }}">
                <i class="bi bi-plus-circle-fill"></i> Created
            </a>
            <a href="{{ route('admin.activity.log', array_merge(request()->except('page'), ['action' => 'updated'])) }}" class="audit-pill-btn {{ request('action') === 'updated' ? 'active' : '' }}">
                <i class="bi bi-pencil-fill"></i> Updated
            </a>
            <a href="{{ route('admin.activity.log', array_merge(request()->except('page'), ['action' => 'deleted'])) }}" class="audit-pill-btn {{ request('action') === 'deleted' ? 'active' : '' }}">
                <i class="bi bi-trash3-fill"></i> Deleted
            </a>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('admin.activity.log') }}" class="audit-search-row">
            @if(request('action'))
                <input type="hidden" name="action" value="{{ request('action') }}">
            @endif

            <div class="audit-search-input-wrap">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="audit-search-input" placeholder="Search by causer, email, IP, description, resource..." value="{{ request('search') }}">
            </div>

            <!-- Operator Filter -->
            <select name="causer_id" class="audit-select" style="max-width: 180px;">
                <option value="" style="background: #190f0f;">All Operators</option>
                @foreach($causersList ?? [] as $causer)
                    <option value="{{ $causer->id }}" style="background: #190f0f;" {{ request('causer_id') == $causer->id ? 'selected' : '' }}>
                        {{ $causer->name }} ({{ ucfirst($causer->role) }})
                    </option>
                @endforeach
            </select>

            <!-- Date Preset -->
            <select name="date_preset" class="audit-select" style="max-width: 140px;" onchange="if(this.value){ document.getElementById('specificDateInput').value=''; }">
                <option value="" style="background: #190f0f;">Any Time</option>
                <option value="today" style="background: #190f0f;" {{ request('date_preset') == 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" style="background: #190f0f;" {{ request('date_preset') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="7days" style="background: #190f0f;" {{ request('date_preset') == '7days' ? 'selected' : '' }}>Last 7 Days</option>
                <option value="30days" style="background: #190f0f;" {{ request('date_preset') == '30days' ? 'selected' : '' }}>Last 30 Days</option>
            </select>

            <!-- Specific Date -->
            <input type="date" name="date" id="specificDateInput" class="audit-date-input" value="{{ request('date') }}" title="Select Specific Date">

            <button type="submit" class="saas-btn saas-btn-primary" style="padding: 9px 18px; font-size: 0.85rem; font-weight: 700;">
                <i class="bi bi-funnel-fill me-1"></i> Apply
            </button>

            @if(request()->hasAny(['search', 'date', 'date_preset', 'action', 'log_name', 'causer_id']))
                <a href="{{ route('admin.activity.log') }}" class="saas-btn saas-btn-secondary" style="padding: 9px 14px; font-size: 0.85rem; color: #F87171; border-color: rgba(239,68,68,0.3); text-decoration: none;">
                    <i class="bi bi-x-circle me-1"></i> Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="audit-table-wrap">
        <table class="audit-table">
            <thead>
                <tr>
                    <th style="width: 130px;">Timestamp</th>
                    <th>Operator / Causer</th>
                    <th>Action</th>
                    <th>Target Resource</th>
                    <th>IP & Network</th>
                    <th style="text-align: right; padding-right: 24px;">Diff / Details</th>
                </tr>
            </thead>
            <tbody>
                @php $currentGroupDate = null; @endphp
                @forelse($logs ?? [] as $log)
                    @php
                        $logDate = $log->created_at->format('Y-m-d');
                        $isToday = $log->created_at->isToday();
                        $isYesterday = $log->created_at->isYesterday();
                        $dateLabel = $isToday ? 'Today — ' . $log->created_at->format('F j, Y') : ($isYesterday ? 'Yesterday — ' . $log->created_at->format('F j, Y') : $log->created_at->format('l, F j, Y'));
                    @endphp

                    @if($currentGroupDate !== $logDate)
                        @php $currentGroupDate = $logDate; @endphp
                        <tr class="audit-date-group-row">
                            <td colspan="6">
                                <i class="bi bi-calendar-event me-2"></i> {{ $dateLabel }}
                            </td>
                        </tr>
                    @endif

                    @php
                        $desc = strtolower($log->description ?? '');
                        $actionClass = 'action-system';
                        $actionIcon = 'bi-gear-wide-connected';
                        
                        if (str_contains($desc, 'create') || str_contains($desc, 'register')) {
                            $actionClass = 'action-created';
                            $actionIcon = 'bi-plus-circle-fill';
                        } elseif (str_contains($desc, 'update') || str_contains($desc, 'edit') || str_contains($desc, 'modify')) {
                            $actionClass = 'action-updated';
                            $actionIcon = 'bi-pencil-square';
                        } elseif (str_contains($desc, 'delete') || str_contains($desc, 'remove') || str_contains($desc, 'destroy')) {
                            $actionClass = 'action-deleted';
                            $actionIcon = 'bi-trash3-fill';
                        } elseif (str_contains($desc, 'login') || str_contains($desc, 'auth') || str_contains($desc, 'password')) {
                            $actionClass = 'action-login';
                            $actionIcon = 'bi-shield-lock-fill';
                        }

                        $causer = $log->causer;
                        $causerName = $causer->name ?? 'System Daemon';
                        $causerEmail = $causer->email ?? 'Automated Event';
                        $initials = $causer ? strtoupper(substr($causer->name, 0, 1)) : 'S';
                        $ip = $log->properties['ip'] ?? '127.0.0.1';
                        $hasDiff = isset($log->properties['attributes']) || isset($log->properties['old']);
                    @endphp

                    <tr>
                        <td>
                            <div style="font-family: monospace; font-weight: 700; font-size: 0.85rem; color: #F3E7CD;">
                                {{ $log->created_at->format('h:i:s A') }}
                            </div>
                            <div style="font-size: 0.72rem; color: #B39B82;">
                                {{ $log->created_at->diffForHumans() }}
                            </div>
                        </td>
                        <td>
                            <div class="audit-user-cell">
                                <div class="audit-avatar-init">{{ $initials }}</div>
                                <div>
                                    <div style="font-weight: 700; font-size: 0.88rem; color: #F3E7CD;">
                                        {{ $causerName }}
                                        @if($causer && $causer->role)
                                            <span style="font-size: 0.68rem; font-weight: 700; padding: 1px 6px; border-radius: 4px; background: rgba(207,164,111,0.15); color: #CFA46F; margin-left: 4px;">
                                                {{ strtoupper($causer->role) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div style="font-size: 0.75rem; color: #B39B82;">
                                        {{ $causerEmail }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="audit-action-chip {{ $actionClass }}">
                                <i class="bi {{ $actionIcon }}"></i>
                                {{ ucfirst($log->description ?? 'Event') }}
                            </span>
                        </td>
                        <td>
                            @if($log->subject_type)
                                <span class="audit-entity-chip">
                                    <i class="bi bi-box-seam"></i>
                                    {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                </span>
                            @else
                                <span style="color: #64748B; font-size: 0.8rem; font-style: italic;">
                                    General Subsystem
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="audit-ip-chip">
                                <i class="bi bi-globe2"></i> {{ $ip }}
                            </span>
                        </td>
                        <td style="text-align: right; padding-right: 24px;">
                            @if($hasDiff || !empty($log->properties))
                                <button type="button" class="audit-inspect-btn btn-inspect-audit"
                                    data-title="{{ $log->subject_type ? (class_basename($log->subject_type) . ' #' . $log->subject_id) : 'System Event' }} — {{ ucfirst($log->description) }}"
                                    data-action="{{ ucfirst($log->description) }}"
                                    data-operator="{{ $causerName }} ({{ $causerEmail }})"
                                    data-time="{{ $log->created_at->format('M j, Y h:i:s A') }}"
                                    data-ip="{{ $ip }}"
                                    data-properties="{{ json_encode($log->properties) }}">
                                    <i class="bi bi-code-slash"></i> Inspect Diff
                                </button>
                            @else
                                <span style="font-size: 0.75rem; color: rgba(255,255,255,0.25);">Standard Event</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 60px 20px;">
                            <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(207,164,111,0.1); border: 1px solid rgba(207,164,111,0.25); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: #CFA46F; font-size: 1.8rem;">
                                <i class="bi bi-journal-x"></i>
                            </div>
                            <div class="saas-heading" style="font-size: 1.15rem; margin-bottom: 6px;">No audit records found</div>
                            <p class="saas-text-muted" style="max-width: 420px; margin: 0 auto 16px; font-size: 0.85rem;">
                                No system events match your current search and filter criteria. Try adjusting your filters or date range.
                            </p>
                            <a href="{{ route('admin.activity.log') }}" class="saas-btn saas-btn-primary" style="padding: 8px 18px; font-size: 0.85rem;">
                                Reset All Filters
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
@if(isset($logs) && $logs->hasPages())
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-top: 16px; padding: 0 4px;">
        <div style="font-size: 0.85rem; color: #B39B82;">
            Showing <strong style="color: #F3E7CD;">{{ $logs->firstItem() ?? 0 }}</strong> to <strong style="color: #F3E7CD;">{{ $logs->lastItem() ?? 0 }}</strong> of <strong style="color: #F3E7CD;">{{ $logs->total() }}</strong> events
        </div>
        <div>
            {{ $logs->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endif

<!-- Dynamic Diff Inspector Modal -->
<div class="audit-modal-backdrop" id="auditInspectorModal">
    <div class="audit-modal-dialog">
        <div class="audit-modal-header">
            <div>
                <h3 id="modalAuditTitle" style="margin: 0; font-size: 1.1rem; color: #F3E7CD; font-weight: 700;">Event Diff Inspection</h3>
                <div id="modalAuditSubtitle" style="font-size: 0.78rem; color: #B39B82; margin-top: 2px;">Details & State Transition</div>
            </div>
            <button type="button" id="closeAuditModalBtn" style="background: none; border: none; color: #B39B82; font-size: 1.4rem; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <div class="audit-modal-body">
            <!-- Event Meta Strip -->
            <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.06); border-radius: 10px; padding: 12px 16px; margin-bottom: 18px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.8rem;">
                <div>
                    <span style="color: #B39B82;">Operator:</span>
                    <div id="modalAuditOperator" style="color: #F3E7CD; font-weight: 600;">System</div>
                </div>
                <div>
                    <span style="color: #B39B82;">IP Address:</span>
                    <div id="modalAuditIp" style="color: #F3E7CD; font-family: monospace;">127.0.0.1</div>
                </div>
            </div>

            <!-- Tab Switcher -->
            <div style="display: flex; gap: 8px; margin-bottom: 14px;">
                <button type="button" class="audit-pill-btn active" id="tabBtnDiff" onclick="switchAuditTab('diff')">
                    <i class="bi bi-columns-gap"></i> Attribute Diff
                </button>
                <button type="button" class="audit-pill-btn" id="tabBtnRaw" onclick="switchAuditTab('raw')">
                    <i class="bi bi-filetype-json"></i> Raw JSON Payload
                </button>
            </div>

            <!-- Diff Content View -->
            <div id="auditTabDiffContent">
                <div id="auditDiffTableContainer"></div>
            </div>

            <!-- Raw JSON View -->
            <div id="auditTabRawContent" style="display: none;">
                <div style="display: flex; justify-content: flex-end; margin-bottom: 8px;">
                    <button type="button" id="copyJsonBtn" class="audit-inspect-btn" style="padding: 4px 10px; font-size: 0.75rem;">
                        <i class="bi bi-clipboard"></i> Copy JSON
                    </button>
                </div>
                <div class="json-code-box" id="modalRawJson"></div>
            </div>
        </div>

        <div class="audit-modal-footer">
            <button type="button" class="saas-btn saas-btn-primary" id="modalDismissBtn" style="padding: 8px 18px; font-size: 0.85rem;">
                Close Inspector
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentRawProperties = {};

function switchAuditTab(tab) {
    const tabDiff = document.getElementById('auditTabDiffContent');
    const tabRaw = document.getElementById('auditTabRawContent');
    const btnDiff = document.getElementById('tabBtnDiff');
    const btnRaw = document.getElementById('tabBtnRaw');

    if (tab === 'diff') {
        if (tabDiff) tabDiff.style.display = 'block';
        if (tabRaw) tabRaw.style.display = 'none';
        if (btnDiff) btnDiff.classList.add('active');
        if (btnRaw) btnRaw.classList.remove('active');
    } else {
        if (tabDiff) tabDiff.style.display = 'none';
        if (tabRaw) tabRaw.style.display = 'block';
        if (btnDiff) btnDiff.classList.remove('active');
        if (btnRaw) btnRaw.classList.add('active');
    }
}

function openAuditModal(data) {
    const modal = document.getElementById('auditInspectorModal');
    const titleEl = document.getElementById('modalAuditTitle');
    const subEl = document.getElementById('modalAuditSubtitle');
    const opEl = document.getElementById('modalAuditOperator');
    const ipEl = document.getElementById('modalAuditIp');
    const diffContainer = document.getElementById('auditDiffTableContainer');
    const rawBox = document.getElementById('modalRawJson');

    if (!modal) return;

    if (titleEl) titleEl.textContent = data.title || 'Event Diff Inspector';
    if (subEl) subEl.textContent = `${data.action} • Logged at ${data.time}`;
    if (opEl) opEl.textContent = data.operator || 'System';
    if (ipEl) ipEl.textContent = data.ip || '127.0.0.1';

    currentRawProperties = data.properties || {};
    if (rawBox) rawBox.textContent = JSON.stringify(currentRawProperties, null, 2);

    const attributes = currentRawProperties.attributes || {};
    const old = currentRawProperties.old || {};
    const keys = Array.from(new Set([...Object.keys(attributes), ...Object.keys(old)]));

    let diffHtml = '';
    let hasChanges = false;

    if (keys.length > 0) {
        diffHtml += '<table class="diff-table"><thead><tr><th style="width:28%;">Field / Attribute</th><th style="width:36%;">Previous Value (Old)</th><th style="width:36%;">New Value (Updated)</th></tr></thead><tbody>';

        keys.forEach(key => {
            if (key === 'password' || key === 'remember_token') return;
            hasChanges = true;

            const oldVal = old[key] !== undefined ? (typeof old[key] === 'object' ? JSON.stringify(old[key]) : String(old[key])) : '<span style="opacity:0.35;">—</span>';
            const newVal = attributes[key] !== undefined ? (typeof attributes[key] === 'object' ? JSON.stringify(attributes[key]) : String(attributes[key])) : '<span style="opacity:0.35;">—</span>';

            diffHtml += `<tr>
                <td style="color:#60A5FA; font-weight:700;">${key}</td>
                <td class="diff-old">${oldVal}</td>
                <td class="diff-new">${newVal}</td>
            </tr>`;
        });

        diffHtml += '</tbody></table>';
    }

    // If no attribute diff but other properties exist (e.g. IP, agent, context)
    if (!hasChanges) {
        const extraKeys = Object.keys(currentRawProperties).filter(k => k !== 'attributes' && k !== 'old');
        if (extraKeys.length > 0) {
            diffHtml += '<table class="diff-table"><thead><tr><th>Property Key</th><th>Recorded Value</th></tr></thead><tbody>';
            extraKeys.forEach(k => {
                const val = typeof currentRawProperties[k] === 'object' ? JSON.stringify(currentRawProperties[k]) : String(currentRawProperties[k]);
                diffHtml += `<tr>
                    <td style="color:#CFA46F; font-weight:700;">${k}</td>
                    <td style="color:#F3E7CD;">${val}</td>
                </tr>`;
            });
            diffHtml += '</tbody></table>';
        } else {
            diffHtml = '<div style="text-align:center; padding:30px 10px; color:#B39B82;">No individual property modifications recorded for this event.</div>';
        }
    }

    if (diffContainer) diffContainer.innerHTML = diffHtml;
    switchAuditTab('diff');
    modal.style.display = 'flex';
}

function closeAuditModal() {
    const modal = document.getElementById('auditInspectorModal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-inspect-audit');
        if (btn) {
            e.preventDefault();
            const data = {
                title: btn.getAttribute('data-title'),
                action: btn.getAttribute('data-action'),
                operator: btn.getAttribute('data-operator'),
                time: btn.getAttribute('data-time'),
                ip: btn.getAttribute('data-ip'),
                properties: JSON.parse(btn.getAttribute('data-properties') || '{}')
            };
            openAuditModal(data);
            return;
        }

        const closeBtn = e.target.closest('#closeAuditModalBtn') || e.target.closest('#modalDismissBtn');
        if (closeBtn) {
            e.preventDefault();
            closeAuditModal();
            return;
        }

        const modal = document.getElementById('auditInspectorModal');
        if (modal && e.target === modal) {
            closeAuditModal();
            return;
        }

        const copyBtn = e.target.closest('#copyJsonBtn');
        if (copyBtn) {
            e.preventDefault();
            navigator.clipboard.writeText(JSON.stringify(currentRawProperties, null, 2)).then(() => {
                const originalHtml = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
                setTimeout(() => { copyBtn.innerHTML = originalHtml; }, 2000);
            });
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAuditModal();
    });
});
</script>
@endpush
@endsection
