@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Audit Logs</h1>
        <p class="saas-text-muted" style="margin:0;">Track user activity, modifications, and system events.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <a href="{{ route('admin.activity.log.export', request()->all()) }}" class="saas-btn saas-btn-secondary" style="text-decoration: none;">
            <i class="bi bi-download"></i> Export Logs
        </a>
    </div>
</div>

<div class="saas-card">
    <form method="GET" action="{{ route('admin.activity.log') }}" class="saas-card-header" style="gap:16px; flex-wrap:wrap; margin: 0; border: none; background: transparent;">
        <div class="saas-search" style="width:250px;">
            <i class="bi bi-search"></i>
            <input type="text" name="search" class="saas-search-input" placeholder="Search logs..." value="{{ request('search') }}">
        </div>
        
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <input type="date" name="date" class="saas-input" style="width:140px; padding:6px 12px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd; border-radius: 8px;" value="{{ request('date') }}">
            
            <select name="action" class="saas-input saas-select" style="width:140px; padding:6px 30px 6px 12px; background: rgba(0,0,0,0.2) url('data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 16 16\'%3e%3cpath fill=\'none\' stroke=\'%23f3e7cd\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'m2 5 6 6 6-6\'/%3e%3c/svg%3e') no-repeat right 12px center; background-size: 16px 12px; border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd; border-radius: 8px; appearance: none;">
                <option value="" style="background: #190f0f;" {{ request('action') == '' ? 'selected' : '' }}>All Actions</option>
                <option value="created" style="background: #190f0f;" {{ request('action') == 'created' ? 'selected' : '' }}>Created</option>
                <option value="updated" style="background: #190f0f;" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated</option>
                <option value="deleted" style="background: #190f0f;" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                <option value="login" style="background: #190f0f;" {{ request('action') == 'login' ? 'selected' : '' }}>Login</option>
            </select>
            
            <button type="submit" class="saas-btn saas-btn-secondary" style="padding:6px 12px;">
                <i class="bi bi-funnel"></i> Filter
            </button>

            @if(request()->hasAny(['search', 'date', 'action', 'log_name', 'causer_id']))
                <a href="{{ route('admin.activity.log') }}" class="saas-btn" style="padding:6px 12px; color: #f87171; border: 1px solid rgba(239,68,68,0.3); text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; height: 38px;">
                    Clear
                </a>
            @endif
        </div>
    </form>
    
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Resource</th>
                    <th>IP Address</th>
                    <th style="text-align: right; padding-right: 20px;">Details</th>
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
                        <td colspan="6" style="font-weight:600; font-size:0.95rem; padding:12px 20px; border-bottom:2px solid var(--saas-border);">
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
                        @if($log->subject_type)
                            <span style="font-family:monospace; font-size:0.85rem; color:var(--saas-gold);">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</span>
                        @else
                            <span class="saas-text-muted" style="font-size:0.85rem;">N/A</span>
                        @endif
                    </td>
                    <td class="saas-text-muted" style="font-family:monospace; font-size:0.8rem;">
                        {{ $log->properties['ip'] ?? '127.0.0.1' }}
                    </td>
                    <td style="text-align: right; padding-right: 20px;">
                        @if(isset($log->properties['attributes']) || isset($log->properties['old']))
                            <button class="saas-btn saas-btn-secondary btn-sm btn-view-details" 
                                    style="padding: 4px 8px; font-size: 0.75rem; border-radius: 6px;"
                                    data-title="{{ $log->subject_type ? (class_basename($log->subject_type) . ' #' . $log->subject_id) : 'System Event' }} - {{ ucfirst($log->description) }}"
                                    data-properties="{{ json_encode($log->properties) }}">
                                <i class="bi bi-eye"></i> View
                            </button>
                        @else
                            <span class="saas-text-muted" style="font-size: 0.75rem;">None</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:48px 20px;">
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

@if($logs->hasPages())
<div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div style="color: var(--saas-text-muted); font-size: 0.85rem;">
        Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} results
    </div>
    <div>
        {{ $logs->links('pagination::bootstrap-4') }}
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.btn-view-details');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const title = this.getAttribute('data-title');
            const properties = JSON.parse(this.getAttribute('data-properties'));
            
            const attributes = properties.attributes || {};
            const old = properties.old || {};
            
            let contentHtml = '<div class="table-responsive" style="max-height: 400px; overflow-y: auto;"><table class="table table-bordered" style="color: #ffffff; border-color: rgba(255,255,255,0.08); margin: 0;">';
            contentHtml += '<thead><tr style="background: rgba(255,255,255,0.03); color: #f3e7cd;"><th>Field</th><th>Old Value</th><th>New Value</th></tr></thead>';
            contentHtml += '<tbody>';
            
            const keys = Array.from(new Set([...Object.keys(attributes), ...Object.keys(old)]));
            
            let hasChanges = false;
            keys.forEach(key => {
                if (key === 'password' || key === 'remember_token') {
                    return; // Skip sensitive keys
                }
                hasChanges = true;
                
                const oldVal = old[key] !== undefined ? (typeof old[key] === 'object' ? JSON.stringify(old[key]) : old[key]) : '<span style="color: rgba(255,255,255,0.3);">N/A</span>';
                const newVal = attributes[key] !== undefined ? (typeof attributes[key] === 'object' ? JSON.stringify(attributes[key]) : attributes[key]) : '<span style="color: rgba(255,255,255,0.3);">N/A</span>';
                
                contentHtml += `<tr>
                    <td style="font-weight: 600; color: #60a5fa; font-family: monospace; border-color: rgba(255,255,255,0.08);">${key}</td>
                    <td style="font-family: monospace; word-break: break-all; border-color: rgba(255,255,255,0.08); color: #f87171;">${oldVal}</td>
                    <td style="font-family: monospace; word-break: break-all; border-color: rgba(255,255,255,0.08); color: #4ade80;">${newVal}</td>
                </tr>`;
            });
            
            if (!hasChanges) {
                contentHtml += '<tr><td colspan="3" class="text-center text-muted" style="border-color: rgba(255,255,255,0.08);">No readable property changes.</td></tr>';
            }
            
            contentHtml += '</tbody></table></div>';
            
            openDrawer(title, contentHtml, null);
        });
    });
});
</script>
@endpush
@endsection
