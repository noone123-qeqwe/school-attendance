@extends('parent.layout')
@section('page-title', 'Notifications')

@section('content')
<div class="p-4">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="color: #f3e7cd; font-weight: 800; margin: 0;">
            <i class="bi bi-bell-fill" style="color: #cfa46f; margin-right: 8px;"></i>Notifications
        </h2>
        <button id="markAllReadBtn" class="adm-btn adm-btn-ghost" style="font-size: 0.82rem;" onclick="markAllNotificationsRead()">
            <i class="bi bi-check2-all"></i> Mark All as Read
        </button>
    </div>

    {{-- Filters --}}
    <div class="adm-card" style="margin-bottom: 24px;">
        <div class="adm-card-body" style="padding: 16px 20px;">
            <form method="GET" action="{{ route('parent.notifications') }}" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.72rem; font-weight: 600; color: #b39b82; text-transform: uppercase;">Child</label>
                    <select name="child_id" class="tch-input" style="min-width: 160px;">
                        <option value="">All Children</option>
                        @foreach($children as $child)
                            <option value="{{ $child->id }}" {{ request('child_id') == $child->id ? 'selected' : '' }}>{{ $child->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.72rem; font-weight: 600; color: #b39b82; text-transform: uppercase;">Type</label>
                    <select name="type" class="tch-input" style="min-width: 120px;">
                        <option value="">All Types</option>
                        <option value="absence" {{ request('type') == 'absence' ? 'selected' : '' }}>Absences</option>
                        <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Warnings</option>
                    </select>
                </div>
                <button type="submit" class="adm-btn adm-btn-primary" style="font-size: 0.82rem;"><i class="bi bi-funnel"></i> Filter</button>
                <a href="{{ route('parent.notifications') }}" class="adm-btn adm-btn-ghost" style="font-size: 0.82rem;">Clear</a>
            </form>
        </div>
    </div>

    {{-- Notifications List --}}
    <div class="adm-card">
        <div class="adm-card-body" style="padding: 0;">
            @if($notifications->count() > 0)
                <div class="list-group list-group-flush" style="border-radius: 22px;">
                    @foreach($notifications as $notification)
                        <div class="list-group-item" style="background: {{ $notification->is_read ? 'transparent' : 'rgba(255,235,190,0.03)' }}; border-bottom: 1px solid rgba(255,215,145,0.08); padding: 20px; border-left: {{ $notification->is_read ? 'none' : '3px solid #cfa46f' }};">
                            <div style="display: flex; gap: 16px;">
                                <div>
                                    @if(str_contains($notification->type, 'warning'))
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(239,83,80,0.15); color: #ef5350; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                        </div>
                                    @else
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(66,165,245,0.15); color: #42a5f5; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                                            <i class="bi bi-info-circle-fill"></i>
                                        </div>
                                    @endif
                                </div>
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                        <div style="font-size: 0.85rem; font-weight: 700; color: #f3e7cd; display: flex; align-items: center; gap: 8px;">
                                            {{ $notification->user->name }}
                                            @if($notification->subject)
                                                <span style="color: #b39b82; font-weight: 500;">&bull; {{ $notification->subject->name ?? $notification->subject_code }}</span>
                                            @endif
                                            @if(!$notification->is_read)
                                                <span style="background: #dc2626; width: 8px; height: 8px; border-radius: 50%; display: inline-block;"></span>
                                            @endif
                                        </div>
                                        <div style="font-size: 0.75rem; color: #8f826f;">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <p style="margin: 0; color: #e7dcc8; font-size: 0.9rem; line-height: 1.5;">
                                        {{ $notification->message }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div style="padding: 16px 20px; border-top: 1px solid rgba(255,215,145,0.08);">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="empty-state" style="padding: 60px;">
                    <i class="bi bi-bell-slash" style="font-size: 3rem;"></i>
                    <span style="font-size: 1.1rem; color: #f3e7cd;">No notifications</span>
                    <small style="color: #8f826f;">You're all caught up!</small>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function markAllNotificationsRead() {
    fetch('{{ route('parent.notifications.read') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    }).then(response => response.json()).then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}
</script>
@endsection
