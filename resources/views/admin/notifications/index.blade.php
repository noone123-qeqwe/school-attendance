@extends('layouts.app')

@section('page-title', 'Notifications')

@section('content')
    @php
        $isAuditor = auth()->user()->admin_sub_role === 'auditor';
        $search = request()->query('q');
        $search = is_string($search) ? $search : '';
        $status = request('status') === 'archived' ? 'archived' : 'active';
        $types = ['system' => 'System alert', 'system_update' => 'System update', 'excuse' => 'Excuse letter', 'security' => 'Security alert'];
        $type = request('type');
        if (is_string($type) && $type !== '' && !array_key_exists($type, $types)) {
            $types[$type] = Str::headline($type);
        }
        $filters = request()->only('q', 'type', 'user_id');
    @endphp

    <header class="admin-section-masthead">
        <div>
            <h1>Notifications</h1>
            <p>Review school activity, excuse submissions, and system updates.</p>
        </div>
        @unless($isAuditor)
            <form method="POST" action="{{ route('admin.notifications.markAllRead') }}">
                @csrf
                <button type="submit" class="saas-btn saas-btn-secondary">
                    <i class="bi bi-check2-all" aria-hidden="true"></i> Mark all as read
                </button>
            </form>
        @endunless
    </header>

    <section class="admin-panel" aria-label="Notifications inbox">
        <div class="admin-inbox-controls">
            <nav class="admin-inbox-state" aria-label="Notification status">
                @foreach(['active' => 'Inbox', 'archived' => 'Archived'] as $state => $label)
                    <a href="{{ route('admin.notifications', $filters + ['status' => $state]) }}" @if($status === $state) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <form method="GET" action="{{ route('admin.notifications') }}" class="admin-filter-toolbar">
                <input type="hidden" name="status" value="{{ $status }}">
                @if(is_scalar(request('user_id')))
                    <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                @endif
                <div>
                    <label for="adminNotificationSearch">Find a message</label>
                    <input class="saas-input" id="adminNotificationSearch" name="q" type="search" maxlength="100" value="{{ $search }}">
                </div>
                <div>
                    <label for="adminNotificationType">Type</label>
                    <select class="saas-input saas-select" id="adminNotificationType" name="type">
                        <option value="">All types</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="saas-btn saas-btn-secondary">Apply filters</button>
                @if($search !== '' || $type || request()->filled('user_id'))
                    <a class="admin-panel-link" href="{{ route('admin.notifications', ['status' => $status]) }}">Clear</a>
                @endif
            </form>
        </div>

        @if($notifications->isEmpty())
            <p class="admin-empty-state">{{ $search !== '' || $type || request()->filled('user_id') ? 'No notifications match these filters.' : 'There are no notifications in this view.' }}</p>
        @else
            <ol class="admin-inbox">
                @foreach($notifications as $notification)
                    <li @class(['is-unread' => !$notification->is_read])>
                        <i class="bi {{ $notification->type === 'excuse' ? 'bi-envelope-paper' : 'bi-bell' }}" aria-hidden="true"></i>
                        <div class="admin-inbox-message">
                            <div class="admin-inbox-title">
                                <strong>{{ $types[$notification->type] ?? 'Notification' }}</strong>
                                @unless($notification->is_read)<small>Unread</small>@endunless
                            </div>
                            <p>{{ $notification->message }}</p>
                            <time datetime="{{ $notification->created_at->toAtomString() }}">{{ $notification->created_at->format('M j, Y · g:i A') }}</time>
                        </div>
                        <div class="admin-inbox-actions">
                            @if($notification->type === 'excuse')
                                <a href="{{ route('admin.excuses') }}" class="admin-panel-link">Review request</a>
                            @endif
                            @if(!$isAuditor && !$notification->is_read)
                                <form method="POST" action="{{ route('admin.notifications.markRead', $notification) }}">
                                    @csrf
                                    <button type="submit" class="saas-btn saas-btn-secondary" aria-label="Mark notification from {{ $notification->created_at->format('M j, g:i A') }} as read">Mark as read</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
            @if($notifications->hasPages())
                <nav class="admin-inbox-pagination" aria-label="More notifications">
                    {{ $notifications->links() }}
                </nav>
            @endif
        @endif
    </section>
@endsection
