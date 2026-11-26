@extends('layouts.app')

@section('page-title', 'Notifications')

@section('content')
<style>
/* Mobile responsive fixes for notifications */
@media (max-width: 768px) {
    .tch-stats { 
        grid-template-columns: repeat(2, 1fr) !important; 
        gap: 12px !important; 
    }
    .tch-table thead { display: none; }
    .tch-table tbody tr { 
        display: block; 
        border: 1px solid rgba(255,255,255,0.08); 
        border-radius: 12px; 
        margin-bottom: 12px; 
        background: var(--portal-surface-2, #261b1b); 
        box-shadow: 0 4px 12px rgba(0,0,0,.22); 
    }
    .tch-table tbody td { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 12px 16px; 
        border-bottom: 1px solid rgba(255,255,255,0.07); 
        font-size: .82rem; 
    }
    .tch-table tbody td:last-child { border-bottom: none; }
    .tch-table tbody td::before { 
        content: attr(data-label); 
        font-size: .7rem; 
        font-weight: 700; 
        color: #94a3b8; 
        text-transform: uppercase; 
        letter-spacing: .5px; 
        margin-right: 10px; 
        flex-shrink: 0; 
    }
    .filter-form .row { flex-direction: column; }
    .filter-form .col-md-2, 
    .filter-form .col-md-3, 
    .filter-form .col-md-5 { 
        width: 100% !important; 
        margin-bottom: 8px;
    }
}

@media (max-width: 480px) {
    .tch-stats { 
        grid-template-columns: repeat(2, 1fr) !important; 
        gap: 10px !important; 
    }
}
</style>

<div class="tch-stats" style="grid-template-columns: repeat(5, 1fr);">
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(212,175,55,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-bell-fill" style="color:var(--portal-gold);font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: var(--portal-gold);">{{ $notifications->total() }}</div>
        <div class="tch-stat-lbl">Total Notifications</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(251,191,36,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-exclamation-triangle" style="color:var(--portal-amber);font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: var(--portal-amber);">{{ $notifications->where('type', 'warning_2')->count() }}</div>
        <div class="tch-stat-lbl">2nd Warnings</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(251,113,133,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-x-circle" style="color:var(--portal-rose);font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: var(--portal-rose);">{{ $notifications->where('type', 'warning_3')->count() }}</div>
        <div class="tch-stat-lbl">Final Notices</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(122,26,26,0.22);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-shield-exclamation" style="color:var(--portal-gold-bright);font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: var(--portal-gold-bright);">{{ $notifications->where('type', 'warning_consecutive_3')->count() }}</div>
        <div class="tch-stat-lbl">OSAS Warnings</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(52,211,153,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-chat-text" style="color:var(--portal-emerald);font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: var(--portal-emerald);">{{ $notifications->where('type', 'custom')->count() }}</div>
        <div class="tch-stat-lbl">Custom Messages</div>
    </div>
</div>

<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background: rgba(122,26,26,0.22); color: var(--portal-gold);">
                <i class="bi bi-bell-fill"></i>
            </div>
            My Notifications
        </div>
    </div>

    <!-- Filters -->
    <div style="padding: 16px 22px; border-bottom: 1px solid rgba(255,255,255,0.08);">
        <form method="GET" class="filter-form">
            <div class="row g-3">
                <div class="col-md-3">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--portal-text-soft); margin-bottom: 4px; display: block;">Status</label>
                    <select name="status" class="tch-input" style="width: 100%;">
                        <option value="">Active Notifications</option>
                        <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--portal-text-soft); margin-bottom: 4px; display: block;">Type</label>
                    <select name="type" class="tch-input" style="width: 100%;">
                        <option value="">All Types</option>
                        <option value="warning_2" {{ request('type') == 'warning_2' ? 'selected' : '' }}>2nd Warning</option>
                        <option value="warning_3" {{ request('type') == 'warning_3' ? 'selected' : '' }}>Final Notice</option>
                        <option value="warning_consecutive_3" {{ request('type') == 'warning_consecutive_3' ? 'selected' : '' }}>OSAS Warning</option>
                        <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--portal-text-soft); margin-bottom: 4px; display: block;">Student</label>
                    <select name="user_id" class="tch-input" style="width: 100%;">
                        <option value="">All Students</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->student_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2" style="display: flex; align-items: end; gap: 8px; flex-wrap: wrap;">
                    <button type="submit" class="tch-btn tch-btn-primary" style="white-space: nowrap;">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('teacher.notifications') }}" class="tch-btn tch-btn-ghost" style="white-space: nowrap;">
                        <i class="bi bi-arrow-clockwise"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Notifications Table -->
    <div style="overflow-x: auto;">
        <table class="tch-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Sent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $notification)
                    <tr style="{{ $notification->archived_at ? 'opacity: 0.6;' : '' }}">
                        <td data-label="Student">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="{{ $notification->user->profile_image ? (str_starts_with($notification->user->profile_image, 'http') ? $notification->user->profile_image : public_storage_url($notification->user->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($notification->user->name).'&background=800000&color=fff' }}"
                                     class="profile-avatar-frame" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                <div>
                                    <div style="font-weight: 600; color: var(--portal-text);">{{ $notification->user->name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--portal-muted);">{{ $notification->user->student_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Type">
                            @if($notification->type === 'system_update')
                                <span style="background: #ecfdf5; color: #059669; padding: 3px 8px; border-radius: 99px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="bi bi-rocket-takeoff-fill"></i> Update
                                </span>
                            @elseif($notification->type === 'warning_2')
                                <span style="background: #fffbeb; color: #d97706; padding: 3px 8px; border-radius: 99px; font-size: 0.7rem; font-weight: 700;">
                                    2nd Warning
                                </span>
                            @elseif($notification->type === 'warning_3')
                                <span style="background: #fef2f2; color: #dc2626; padding: 3px 8px; border-radius: 99px; font-size: 0.7rem; font-weight: 700;">
                                    Final Notice
                                </span>
                            @else
                                <span style="background: #f0f9ff; color: #0369a1; padding: 3px 8px; border-radius: 99px; font-size: 0.7rem; font-weight: 700;">
                                    Custom
                                </span>
                            @endif
                        </td>
                        <td data-label="Subject">
                            <div>
                                <div style="font-weight: 600;">{{ $notification->subject_code }}</div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">{{ $notification->subject->name ?? 'N/A' }}</div>
                            </div>
                        </td>
                        <td data-label="Message">
                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                 title="{{ $notification->message }}">
                                {{ $notification->message }}
                            </div>
                        </td>
                        <td data-label="Sent">
                            <div>{{ $notification->created_at->format('M j, Y') }}</div>
                            <div style="font-size: 0.75rem; color: #94a3b8;">{{ $notification->created_at->format('g:i A') }}</div>
                        </td>
                        <td data-label="Actions">
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                @if((int) $notification->user_id === (int) auth()->id())
                                    @if($notification->archived_at)
                                        <button onclick="unarchiveNotification({{ $notification->id }})"
                                                class="view-btn" style="background: rgba(52,211,153,0.14); color: var(--portal-emerald); border-color: rgba(52,211,153,0.3);">
                                            <i class="bi bi-arrow-up-circle"></i> Unarchive
                                        </button>
                                    @else
                                        <button onclick="archiveNotification({{ $notification->id }})"
                                                class="view-btn" style="background: rgba(251,191,36,0.14); color: var(--portal-amber); border-color: rgba(251,191,36,0.3);">
                                            <i class="bi bi-archive"></i> Archive
                                        </button>
                                    @endif
                                    <button onclick="deleteNotification({{ $notification->id }})"
                                            class="view-btn" style="background: rgba(251,113,133,0.14); color: var(--portal-rose); border-color: rgba(251,113,133,0.3);">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                @else
                                    <span class="text-muted small">Recipient only</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="bi bi-bell"></i>
                            <div>No notifications found</div>
                            <div style="font-size: 0.8rem; margin-top: 4px;">
                                @if(request('status') == 'archived')
                                    No archived notifications yet.
                                @else
                                    You haven't sent any notifications yet.
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($notifications->hasPages())
        <div style="padding: 16px 22px; border-top: 1px solid #f8fafc;">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

<script @cspNonce>
function archiveNotification(id) {
    if (confirm('Archive this notification?')) {
        fetch(`/teacher/notifications/${id}/archive`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

function unarchiveNotification(id) {
    if (confirm('Unarchive this notification?')) {
        fetch(`/teacher/notifications/${id}/unarchive`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

function deleteNotification(id) {
    if (confirm('Permanently delete this notification? This action cannot be undone.')) {
        fetch(`/teacher/notifications/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }
}
</script>
@endsection
