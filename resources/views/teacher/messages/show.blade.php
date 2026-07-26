@extends('teacher.layout')
@section('page-title', 'View Message')

@section('content')
<div class="p-4">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
        <h2 style="color: #f3e7cd; font-weight: 800; margin: 0;">
            <i class="bi bi-envelope-open" style="color: #cfa46f; margin-right: 8px;"></i>Message Thread
        </h2>
        <a href="{{ route('messages.index') }}" class="tch-btn" style="background: rgba(255,255,255,0.05); color: #b39b82; border-color: rgba(255,215,145,0.2);">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="tch-card p-4">
        <div style="border-bottom: 1px solid rgba(255,215,145,0.1); padding-bottom: 16px; margin-bottom: 20px;">
            <h4 style="color: #f3e7cd; font-weight: 700; margin-bottom: 8px;">{{ $message->subject ?: 'No Subject' }}</h4>
            <div style="color: #b39b82; font-size: 0.9rem;">
                <strong>From:</strong> {{ $message->sender->name }} <br>
                <strong>To:</strong> {{ $message->receiver->name }} <br>
                <strong>Date:</strong> {{ $message->created_at->format('F d, Y h:i A') }}
            </div>
        </div>

        <div style="color: #e7dcc8; line-height: 1.6; white-space: pre-wrap; font-size: 1rem;">{{ $message->body }}</div>

        @if($message->receiver_id === auth()->id())
        <div style="margin-top: 30px;">
            <a href="{{ route('messages.create', ['reply_to' => $message->sender_id]) }}" class="tch-btn">
                <i class="bi bi-reply me-1"></i> Reply
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
