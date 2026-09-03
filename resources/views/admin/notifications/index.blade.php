@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Notifications</h1>
        <p class="saas-text-muted" style="margin:0;">System alerts, excuse letters, and activity updates.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <form method="POST" action="{{ route('admin.notifications.markAllRead') }}">
            @csrf
            <button type="submit" class="saas-btn saas-btn-secondary">
                <i class="bi bi-check2-all"></i> Mark All as Read
            </button>
        </form>
    </div>
</div>

<div class="saas-card">
    <div class="saas-card-header" style="gap:16px; flex-wrap:wrap;">
        <div class="saas-search" style="width:300px;">
            <i class="bi bi-search"></i>
            <input type="text" class="saas-search-input" placeholder="Search notifications...">
        </div>
        
        <div style="display:flex; gap:12px; align-items:center;">
            <select class="saas-input saas-select" style="width:160px; padding:6px 30px 6px 12px;">
                <option value="">All Types</option>
                <option value="excuse">Excuse Letters</option>
                <option value="system">System Alerts</option>
                <option value="security">Security</option>
            </select>
        </div>
    </div>
    
    <div style="padding:16px;">
        @php
            $currentDate = null;
        @endphp
        @forelse($notifications as $notification)
        @if($currentDate !== $notification->created_at->format('Y-m-d'))
            @php
                $currentDate = $notification->created_at->format('Y-m-d');
            @endphp
            <div style="font-weight:600; font-size:0.9rem; margin: {{ $loop->first ? '0 0 12px 0' : '24px 0 12px 0' }}; color:var(--saas-text-muted); text-transform:uppercase; letter-spacing:0.5px;">
                {{ $notification->created_at->isToday() ? 'Today' : ($notification->created_at->isYesterday() ? 'Yesterday' : $notification->created_at->format('F j, Y')) }}
            </div>
        @endif
        <div style="padding:16px; border:1px solid {{ $notification->is_read ? 'var(--saas-border)' : 'var(--saas-primary)' }}; border-radius:var(--saas-radius-md); margin-bottom:12px; background:{{ $notification->is_read ? 'transparent' : 'rgba(255,215,145,0.03)' }}; display:flex; gap:16px; align-items:flex-start; transition:all 0.2s;">
            <div style="width:40px; height:40px; border-radius:50%; background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                @if($notification->type === 'system_update')
                    <i class="bi bi-rocket-takeoff" style="color:#34d399;"></i>
                @elseif($notification->type === 'excuse')
                    <i class="bi bi-envelope-paper saas-text-muted"></i>
                @elseif($notification->type === 'system')
                    <i class="bi bi-info-circle saas-text-muted"></i>
                @else
                    <i class="bi bi-bell saas-text-muted"></i>
                @endif
            </div>
            
            <div style="flex:1;">
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <div style="font-weight:600; font-size:0.95rem; color:{{ $notification->is_read ? 'var(--saas-text)' : 'var(--saas-gold)' }};">
                        {{ $notification->type === 'system_update' ? '🚀 System Update' : ($notification->type === 'excuse' ? 'New Excuse Letter' : 'System Notification') }}
                    </div>
                    <div class="saas-text-muted" style="font-size:0.75rem;">
                        {{ $notification->created_at->diffForHumans() }}
                    </div>
                </div>
                
                <p class="saas-text-muted" style="margin-bottom:8px; font-size:0.875rem; line-height:1.5;">
                    {{ $notification->message }}
                </p>
                
                <div style="display:flex; gap:12px; align-items:center;">
                    @if($notification->type === 'excuse')
                        <a href="{{ route('admin.excuses') }}" class="saas-btn saas-btn-secondary" style="padding:4px 10px; font-size:0.75rem;">
                            Review Request
                        </a>
                    @endif
                    
                    @if(!$notification->is_read)
                        <form method="POST" action="{{ route('admin.notifications.markRead', $notification->id) }}">
                            @csrf
                            <button type="submit" class="saas-btn saas-btn-secondary" style="padding:4px 10px; font-size:0.75rem; border:none; background:transparent;">
                                Mark as Read
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div style="text-align:center; padding:48px 20px;">
            <i class="bi bi-bell-slash saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
            <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">All caught up!</div>
            <p class="saas-text-muted" style="max-width:400px; margin-inline:auto;">You have no new notifications at this time.</p>
        </div>
        @endforelse
    </div>
    
    @if(isset($notifications) && $notifications->hasPages())
    <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:space-between; align-items:center;">
        <div class="saas-text-muted">
            Showing {{ $notifications->firstItem() ?? 0 }} to {{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }} results
        </div>
        <div>
            {{ $notifications->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
