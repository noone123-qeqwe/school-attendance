@extends('layouts.admin_premium')

@section('title', 'Audit Logs')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Audit Logs</h1>
        <p class="saas-text-muted" style="margin:0;">Track user activity, modifications, and system events.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button class="saas-btn saas-btn-secondary">
            <i class="bi bi-download"></i> Export Logs
        </button>
    </div>
</div>

<div class="saas-card">
    <div class="saas-card-header" style="gap:16px; flex-wrap:wrap;">
        <div class="saas-search" style="width:250px;">
            <i class="bi bi-search"></i>
            <input type="text" class="saas-search-input" placeholder="Search logs...">
        </div>
        
        <div style="display:flex; gap:12px; align-items:center;">
            <input type="date" class="saas-input" style="width:140px; padding:6px 12px;">
            
            <select class="saas-input saas-select" style="width:140px; padding:6px 30px 6px 12px;">
                <option value="">All Actions</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
                <option value="login">Login</option>
            </select>
            
            <button class="saas-btn saas-btn-secondary" style="padding:6px 12px;">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </div>
    
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Resource</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $currentDate = null;
                @endphp
                @forelse($logs ?? [] as $log)
                @if($currentDate !== $log->created_at->format('Y-m-d'))
                    @php
                        $currentDate = $log->created_at->format('Y-m-d');
                    @endphp
                    <tr style="background:rgba(255,255,255,0.02);">
                        <td colspan="5" style="font-weight:600; font-size:0.95rem; padding:12px 20px; border-bottom:2px solid var(--saas-border);">
                            <i class="bi bi-calendar-event saas-text-muted" style="margin-right:8px;"></i> 
                            {{ $log->created_at->format('F j, Y - l') }}
                        </td>
                    </tr>
                @endif
                <tr>
                    <td>
                        <div style="font-weight:600; font-family:monospace; font-size:0.85rem;">{{ $log->created_at->format('h:i:s A') }}</div>
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:0.875rem;">{{ $log->causer->name ?? 'System' }}</div>
                        <div class="saas-text-muted" style="font-size:0.75rem;">{{ $log->causer->email ?? 'System Process' }}</div>
                    </td>
                    <td>
                        @if($log->description === 'created' || str_contains(strtolower($log->description), 'login'))
                            <span class="saas-badge saas-badge-success">{{ ucfirst($log->description) }}</span>
                        @elseif($log->description === 'updated')
                            <span class="saas-badge saas-badge-warning">{{ ucfirst($log->description) }}</span>
                        @elseif($log->description === 'deleted')
                            <span class="saas-badge saas-badge-danger">{{ ucfirst($log->description) }}</span>
                        @else
                            <span class="saas-badge saas-badge-info">{{ ucfirst($log->description) }}</span>
                        @endif
                    </td>
                    <td>
                        <span style="font-family:monospace; font-size:0.85rem; color:var(--saas-gold);">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</span>
                    </td>
                    <td class="saas-text-muted" style="font-family:monospace; font-size:0.8rem;">
                        {{ $log->properties['ip'] ?? '127.0.0.1' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-shield-lock saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No audit logs available</div>
                        <p class="saas-text-muted" style="max-width:400px; margin-inline:auto;">System activity will appear here once users start interacting with the application.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
