@extends('layouts.app')
@section('page-title', 'Notifications')

@section('content')
<div class="p-4">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="color: #f3e7cd; font-weight: 800; margin: 0;">
            <i class="bi bi-bell-fill" style="color: #cfa46f; margin-right: 8px;"></i>Notifications
        </h2>
        <button id="markAllReadBtn" class="ent-btn ent-btn-ghost" onclick="markAllNotificationsRead()">
            <i class="bi bi-check2-all"></i> Mark All as Read
        </button>
    </div>

    {{-- Filters --}}
    <x-card type="section" class="ent-mb-lg">
        <form method="GET" action="{{ route('parent.notifications') }}" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
            <div style="flex:1; min-width: 160px;">
                <label class="ent-label">Child</label>
                <select name="child_id" class="ent-input">
                    <option value="">All Children</option>
                    @foreach($children as $child)
                        <option value="{{ $child->id }}" {{ request('child_id') == $child->id ? 'selected' : '' }}>{{ $child->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1; min-width: 120px;">
                <label class="ent-label">Type</label>
                <select name="type" class="ent-input">
                    <option value="">All Types</option>
                    <option value="absence" {{ request('type') == 'absence' ? 'selected' : '' }}>Absences</option>
                    <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Warnings</option>
                </select>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="ent-btn ent-btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                @if(request()->hasAny(['child_id', 'type']))
                    <a href="{{ route('parent.notifications') }}" class="ent-btn ent-btn-ghost text-danger">Clear</a>
                @endif
            </div>
        </form>
    </x-card>

    {{-- Notifications List --}}
    <x-card type="section" style="padding: 0;">
        @if($notifications->count() > 0)
            <div class="list-group list-group-flush" style="border-radius: 20px;">
                @foreach($notifications as $notification)
                    <div class="list-group-item" style="background: {{ $notification->is_read ? 'transparent' : 'rgba(255,235,190,0.04)' }}; border-bottom: 1px solid rgba(255,215,145,0.08); padding: 24px 20px; transition: background 0.3s; position: relative;">
                        @if(!$notification->is_read)
                            <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #cfa46f; border-top-right-radius: 4px; border-bottom-right-radius: 4px;"></div>
                        @endif
                        <div style="display: flex; gap: 20px;">
                            <div>
                                @if($notification->type === 'system_update')
                                    <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16,185,129,0.18); color: #34d399; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; border: 1px solid rgba(16,185,129,0.35);">
                                        <i class="bi bi-rocket-takeoff-fill"></i>
                                    </div>
                                @elseif(str_contains($notification->type, 'warning'))
                                    <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(239,83,80,0.15); color: #ef5350; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; border: 1px solid rgba(239,83,80,0.2);">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </div>
                                @else
                                    <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(66,165,245,0.15); color: #42a5f5; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; border: 1px solid rgba(66,165,245,0.2);">
                                        <i class="bi bi-info-circle-fill"></i>
                                    </div>
                                @endif
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 8px; gap: 10px;">
                                    <div style="font-size: 0.95rem; font-weight: 700; color: #f3e7cd; display: flex; align-items: center; gap: 8px;">
                                        {{ $notification->user->name }}
                                        @if($notification->subject)
                                            <span style="color: #b39b82; font-weight: 500;">&bull; {{ $notification->subject->name ?? $notification->subject_code }}</span>
                                        @endif
                                        @if(!$notification->is_read)
                                            <span class="ent-badge ent-badge-warning" style="font-size: 0.65rem; padding: 2px 6px;">New</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 0.8rem; color: #8f826f; font-weight: 500;">
                                        <i class="bi bi-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                <p style="margin: 0; color: #e7dcc8; font-size: 0.95rem; line-height: 1.6;">
                                    {{ $notification->message }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div style="padding: 16px 24px; border-top: 1px solid rgba(255,215,145,0.08);">
                {{ $notifications->links() }}
            </div>
        @else
            <div class="ent-empty" style="padding: 60px;">
                <div class="ent-empty-icon" style="width:72px;height:72px;font-size:2.5rem; margin-bottom:16px;">
                    <i class="bi bi-bell-slash"></i>
                </div>
                <div class="ent-empty-text">No notifications</div>
                <div style="color: #8f826f; font-size: 0.9rem; margin-top: 8px;">You're all caught up!</div>
            </div>
        @endif
    </x-card>
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
