@extends('parent.layout')
@section('page-title', 'Messages')

@section('content')
<div class="p-4">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
        <h2 style="color: #f3e7cd; font-weight: 800; margin: 0;">
            <i class="bi bi-envelope-fill" style="color: #cfa46f; margin-right: 8px;"></i>Messages
        </h2>
        <a href="{{ route('messages.create') }}" class="adm-btn adm-btn-primary">
            <i class="bi bi-pencil-square me-1"></i> Compose
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="background: rgba(74,222,128,0.15); border: 1px solid rgba(74,222,128,0.3); color: #4ade80; border-radius: 12px;">
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" style="color: #f3e7cd;" id="pills-inbox-tab" data-bs-toggle="pill" data-bs-target="#pills-inbox" type="button" role="tab">Inbox</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" style="color: #f3e7cd;" id="pills-sent-tab" data-bs-toggle="pill" data-bs-target="#pills-sent" type="button" role="tab">Sent</button>
                </li>
            </ul>
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-inbox" role="tabpanel">
                    <div class="adm-card">
                        <div class="table-responsive">
                            <table class="adm-table">
                                <thead>
                                    <tr>
                                        <th>From</th>
                                        <th>Subject</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($inbox as $msg)
                                        <tr>
                                            <td>{{ $msg->sender->name }}</td>
                                            <td>
                                                <a href="{{ route('messages.show', $msg) }}" style="color: #cfa46f; text-decoration: none; font-weight: 600;">
                                                    {{ $msg->subject ?: 'No Subject' }}
                                                </a>
                                            </td>
                                            <td>{{ $msg->created_at->format('M d, Y h:i A') }}</td>
                                            <td>
                                                @if($msg->read_at)
                                                    <span style="color: #b39b82;">Read</span>
                                                @else
                                                    <span style="color: #4ade80; font-weight: bold;">New</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center" style="color:#b39b82;">No messages in inbox.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-3">{{ $inbox->links() }}</div>
                </div>
                <div class="tab-pane fade" id="pills-sent" role="tabpanel">
                    <div class="adm-card">
                        <div class="table-responsive">
                            <table class="adm-table">
                                <thead>
                                    <tr>
                                        <th>To</th>
                                        <th>Subject</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($sent as $msg)
                                        <tr>
                                            <td>{{ $msg->receiver->name }}</td>
                                            <td>
                                                <a href="{{ route('messages.show', $msg) }}" style="color: #cfa46f; text-decoration: none; font-weight: 600;">
                                                    {{ $msg->subject ?: 'No Subject' }}
                                                </a>
                                            </td>
                                            <td>{{ $msg->created_at->format('M d, Y h:i A') }}</td>
                                            <td>
                                                @if($msg->read_at)
                                                    <span style="color: #b39b82;">Read</span>
                                                @else
                                                    <span style="color: #f87171;">Unread</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center" style="color:#b39b82;">No sent messages.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-3">{{ $sent->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
