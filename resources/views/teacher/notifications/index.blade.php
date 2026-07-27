@extends('teacher.layout')

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
        border: 1px solid #f1f5f9; 
        border-radius: 12px; 
        margin-bottom: 12px; 
        background: white; 
        box-shadow: 0 1px 4px rgba(0,0,0,.05); 
    }
    .tch-table tbody td { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 12px 16px; 
        border-bottom: 1px solid #f8fafc; 
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
        <div style="width:38px;height:38px;border-radius:10px;background:#fef7f7;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-bell-fill" style="color:var(--tch-primary);font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: var(--tch-primary);">{{ $notifications->total() }}</div>
        <div class="tch-stat-lbl">Total Notifications</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:#fffbeb;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-exclamation-triangle" style="color:#d97706;font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: #d97706;">{{ $notifications->where('type', 'warning_2')->count() }}</div>
        <div class="tch-stat-lbl">2nd Warnings</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-x-circle" style="color:#dc2626;font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: #dc2626;">{{ $notifications->where('type', 'warning_3')->count() }}</div>
        <div class="tch-stat-lbl">Final Notices</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:#f5f3ff;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-shield-exclamation" style="color:#7c2d12;font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: #7c2d12;">{{ $notifications->where('type', 'warning_consecutive_3')->count() }}</div>
        <div class="tch-stat-lbl">OSAS Warnings</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-chat-text" style="color:#16a34a;font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: #16a34a;">{{ $notifications->where('type', 'custom')->count() }}</div>
        <div class="tch-stat-lbl">Custom Messages</div>
    </div>
</div>

<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background: #fef7f7; color: var(--tch-primary);">
                <i class="bi bi-bell-fill"></i>
            </div>
            My Notifications
        </div>
    </div>

    <!-- Filters -->
    <div style="padding: 16px 22px; border-bottom: 1px solid #f8fafc;">
        <form method="GET" class="filter-form">
            <div class="row g-3">
                <div class="col-md-3">
                    <label style="font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px; display: block;">Status</label>
                    <select name="status" class="tch-input" style="width: 100%;">
                        <option value="">Active Notifications</option>
                        <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label style="font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px; display: block;">Type</label>
                    <select name="type" class="tch-input" style="width: 100%;">
                        <option value="">All Types</option>
                        <option value="warning_2" {{ request('type') == 'warning_2' ? 'selected' : '' }}>2nd Warning</option>
                        <option value="warning_3" {{ request('type') == 'warning_3' ? 'selected' : '' }}>Final Notice</option>
                        <option value="warning_consecutive_3" {{ request('type') == 'warning_consecutive_3' ? 'selected' : '' }}>OSAS Warning</option>
                        <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px; display: block;">Student</label>
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
                                <img src="{{ $notification->user->profile_image ? (str_starts_with($notification->user->profile_image, 'http') ? $notification->user->profile_image : asset('storage/'.$notification->user->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($notification->user->name).'&background=800000&color=fff' }}" 
                                     style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid #f1f5f9;">
                                <div>
                                    <div style="font-weight: 600; color: #1e293b;">{{ $notification->user->name }}</div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">{{ $notification->user->student_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Type">
                            @if($notification->type === 'warning_2')
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
                                @if($notification->archived_at)
                                    <button onclick="unarchiveNotification({{ $notification->id }})" 
                                            class="view-btn" style="background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;">
                                        <i class="bi bi-arrow-up-circle"></i> Unarchive
                                    </button>
                                @else
                                    <button onclick="archiveNotification({{ $notification->id }})" 
                                            class="view-btn" style="background: #fffbeb; color: #d97706; border-color: #fed7aa;">
                                        <i class="bi bi-archive"></i> Archive
                                    </button>
                                @endif
                                <button onclick="deleteNotification({{ $notification->id }})" 
                                        class="view-btn" style="background: #fef2f2; color: #dc2626; border-color: #fecaca;">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
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

<script>
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