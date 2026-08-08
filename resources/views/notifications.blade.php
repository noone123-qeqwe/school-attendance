@extends('layouts.app')

@section('content')
<style>
    .notification-card {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        transition: all 0.2s ease;
        color: #f8e7d3;
    }
    .notification-card:hover {
        box-shadow: 0 12px 28px rgba(0,0,0,0.2);
        transform: translateY(-1px);
    }
    .notification-card.archived {
        background: rgba(255,255,255,0.04);
        border-color: rgba(255,255,255,0.08);
        opacity: 0.95;
    }
    .notification-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .notification-info {
        flex: 1;
    }
    .notification-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .notification-type {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .type-warning_2 {
        background: rgba(245, 158, 11, 0.15);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }
    .type-warning_3 {
        background: rgba(239, 68, 68, 0.15);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    .type-custom {
        background: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }
    .notification-message {
        font-size: 0.9rem;
        color: #e5e7eb;
        line-height: 1.5;
        margin-bottom: 12px;
    }
    .notification-meta {
        display: flex;
        align-items: center;
        gap: 16px;
        font-size: 0.8rem;
        color: rgba(248,231,211,0.75);
    }
    .notification-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .action-btn {
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 6px;
        padding: 6px 12px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: #f8e7d3;
    }
    .action-btn:hover {
        transform: translateY(-1px);
        background: rgba(255,255,255,0.12);
    }
    .btn-unarchive {
        color: #059669;
        border-color: #d1fae5;
        background: #ecfdf5;
    }
    .btn-unarchive:hover {
        background: #dcfce7;
        border-color: #a7f3d0;
    }
    .filter-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        padding-bottom: 16px;
    }
    .filter-tab {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    .filter-tab.active {
        background: #800000;
        color: white;
    }
    .filter-tab:not(.active) {
        color: #cbd5e1;
        background: rgba(255,255,255,0.05);
        border-color: rgba(255,255,255,0.08);
    }
    .filter-tab:not(.active):hover {
        background: rgba(255,255,255,0.1);
        color: #f8e7d3;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: rgba(248,231,211,0.65);
    }
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 16px;
        opacity: 0.3;
    }
    .page-header {
        background: linear-gradient(135deg, #800000 0%, #a30000 60%, #c0392b 100%);
        border-radius: 16px;
        padding: 24px 28px;
        color: white;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .page-header::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .page-header h1 {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 4px;
    }
    .page-header p {
        font-size: 0.9rem;
        opacity: 0.8;
        margin: 0;
    }
    
    @media (max-width: 768px) {
        .page-header {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        .page-header h1 { font-size: 1.25rem; }
        .notification-card { padding: 16px; }
        .notification-header {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }
        .notification-actions {
            width: 100%;
        }
        .notification-actions form {
            width: 100%;
        }
        .action-btn {
            width: 100%;
            justify-content: center;
            padding: 8px 16px;
        }
        .filter-tabs {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 8px;
        }
        .filter-tabs::-webkit-scrollbar { display: none; }
        .notification-meta {
            flex-wrap: wrap;
            gap: 8px 16px;
        }
    }
</style>

<div class="container-fluid" style="max-width: 1000px;">
    <div class="page-header">
        <h1><i class="bi bi-bell-fill me-2"></i>My Notifications</h1>
        <p>View and manage your notifications and alerts</p>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="{{ route('notifications') }}" 
           class="filter-tab {{ !request('status') || request('status') === 'active' ? 'active' : '' }}">
            <i class="bi bi-bell"></i> Active
        </a>
        <a href="{{ route('notifications') }}?status=archived" 
           class="filter-tab {{ request('status') === 'archived' ? 'active' : '' }}">
            <i class="bi bi-archive"></i> Archived
        </a>
    </div>

    <!-- Notifications List -->
    @forelse($notifications as $notification)
        <div class="notification-card {{ $notification->isArchived() ? 'archived' : '' }}" id="notification-{{ $notification->id }}">
            <div class="notification-header">
                <div class="notification-info">
                    <div class="notification-type type-{{ $notification->type }}">
                        @if($notification->type === 'warning_2')
                            <i class="bi bi-exclamation-triangle-fill"></i> Warning (2 Absences)
                        @elseif($notification->type === 'warning_3')
                            <i class="bi bi-exclamation-octagon-fill"></i> Final Warning (3+ Absences)
                        @else
                            <i class="bi bi-info-circle-fill"></i> Custom Notice
                        @endif
                    </div>
                    
                    <div class="notification-message">
                        {{ $notification->message }}
                    </div>
                    
                    <div class="notification-meta">
                        @if($notification->subject)
                            <span>
                                <i class="bi bi-book"></i>
                                {{ $notification->subject->name }}
                            </span>
                        @endif
                        <span>
                            <i class="bi bi-clock"></i>
                            {{ $notification->created_at->diffForHumans() }}
                        </span>
                        @if($notification->sender)
                            <span>
                                <i class="bi bi-person-badge"></i>
                                From {{ $notification->sender->name }}
                            </span>
                        @endif
                    </div>
                </div>
                
                @if($notification->isArchived())
                    <div class="notification-actions">
                        <button class="action-btn btn-unarchive" onclick="unarchiveNotification({{ $notification->id }})">
                            <i class="bi bi-arrow-up-circle"></i> Unarchive
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="empty-state">
            <i class="bi bi-bell-slash"></i>
            <h4>No notifications found</h4>
            <p>{{ request('status') === 'archived' ? 'No archived notifications yet.' : 'No active notifications at the moment.' }}</p>
        </div>
    @endforelse

    <!-- Pagination -->
    @if($notifications->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

<script>
function unarchiveNotification(id) {
    if (!confirm('Unarchive this notification?')) return;
    
    fetch(`/notifications/${id}/unarchive`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById(`notification-${id}`);
            card.style.opacity = '0.5';
            card.style.transform = 'translateX(10px)';
            setTimeout(() => {
                window.location.reload();
            }, 300);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to unarchive notification');
    });
}
</script>
@endsection