@extends('layouts.app')
@section('page-title', 'Excuse Details')

@section('content')
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color: #f3e7cd; font-weight: 800; margin: 0;">
            <i class="bi bi-file-earmark-text" style="color: #cfa46f; margin-right: 8px;"></i>Excuse Details
        </h2>
        <a href="{{ route('parent.excuses') }}" class="adm-btn adm-btn-ghost"><i class="bi bi-arrow-left"></i> Back to Excuses</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-5 mb-4">
            <div class="adm-card h-100">
                <div class="adm-card-head">
                    <div class="adm-card-title">
                        <div class="adm-card-icon"><i class="bi bi-info-circle"></i></div>
                        Submission Info
                    </div>
                </div>
                <div class="adm-card-body">
                    <div class="mb-3">
                        <label class="text-muted small fw-bold text-uppercase">Child</label>
                        <div style="color: #f3e7cd; font-weight: 600;">{{ $excuseSubmission->user->name }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-bold text-uppercase">Subject/Class</label>
                        <div style="color: #f3e7cd;">{{ $excuseSubmission->attendance->subject->name ?? $excuseSubmission->attendance->subject_code }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-bold text-uppercase">Date of Absence</label>
                        <div style="color: #f3e7cd;">{{ \Carbon\Carbon::parse($excuseSubmission->attendance->date)->format('l, M d, Y') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-bold text-uppercase">Status</label>
                        <div>
                            @if($excuseSubmission->status === 'pending')
                                <span style="background: rgba(255,167,38,0.15); color: #ffa726; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; font-weight: 600;">Pending</span>
                            @elseif($excuseSubmission->status === 'approved')
                                <span style="background: rgba(102,187,106,0.15); color: #66bb6a; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; font-weight: 600;">Approved</span>
                            @else
                                <span style="background: rgba(239,83,80,0.15); color: #ef5350; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; font-weight: 600;">Rejected</span>
                            @endif
                        </div>
                    </div>
                    <hr style="border-color: rgba(255,215,145,0.1);">
                    <div class="mb-3">
                        <label class="text-muted small fw-bold text-uppercase">Reason Provided</label>
                        <p style="color: #e7dcc8;">{{ $excuseSubmission->reason }}</p>
                    </div>
                    @if(!empty($excuseSubmission->attachments) && count($excuseSubmission->attachments) > 0)
                    <div class="mb-3">
                        <label class="text-muted small fw-bold text-uppercase">Document Attached</label><br>
                        <a href="{{ asset('storage/' . $excuseSubmission->attachments[0]) }}" target="_blank" class="adm-btn adm-btn-primary" style="font-size: 0.85rem; padding: 6px 12px; margin-top: 4px;">
                            <i class="bi bi-paperclip"></i> View Document
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-7 mb-4">
            <div class="adm-card h-100 d-flex flex-column">
                <div class="adm-card-head">
                    <div class="adm-card-title">
                        <div class="adm-card-icon"><i class="bi bi-chat-dots"></i></div>
                        Communication Thread
                    </div>
                </div>
                <div class="adm-card-body flex-grow-1" style="overflow-y: auto; max-height: 400px; display: flex; flex-direction: column; gap: 16px;">
                    @if($excuseSubmission->comments->count() > 0)
                        @foreach($excuseSubmission->comments as $comment)
                            <div style="display: flex; gap: 12px; {{ $comment->user_id === Auth::id() ? 'flex-direction: row-reverse;' : '' }}">
                                <img src="{{ $comment->user->profile_image ? (str_starts_with($comment->user->profile_image, 'http') ? $comment->user->profile_image : asset('storage/'.$comment->user->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($comment->user->name).'&background=800000&color=fff' }}"
                                     class="rounded-circle" style="width: 36px; height: 36px;">
                                <div style="max-width: 75%; background: {{ $comment->user_id === Auth::id() ? 'rgba(207,164,111,0.15)' : 'rgba(255,255,255,0.05)' }}; padding: 12px 16px; border-radius: 12px; border: 1px solid {{ $comment->user_id === Auth::id() ? 'rgba(207,164,111,0.3)' : 'rgba(255,255,255,0.1)' }};">
                                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px; gap: 12px;">
                                        <span style="color: #f3e7cd; font-weight: 600; font-size: 0.85rem;">{{ $comment->user->name }}</span>
                                        <span style="color: #8f826f; font-size: 0.7rem;">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div style="color: #e7dcc8; font-size: 0.9rem;">
                                        {{ $comment->body }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-chat-square-text mb-2" style="font-size: 1.5rem;"></i>
                            <p class="mb-0">No comments yet. Use this thread to communicate with the teacher regarding this excuse.</p>
                        </div>
                    @endif
                </div>
                <div class="adm-card-body" style="border-top: 1px solid rgba(255,215,145,0.1); background: rgba(0,0,0,0.1);">
                    <form action="{{ route('parent.excuse.comment', $excuseSubmission->id) }}" method="POST">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="body" class="tch-input" placeholder="Type a message..." required style="border-radius: 8px 0 0 8px; flex-grow: 1;">
                            <button type="submit" class="adm-btn adm-btn-primary" style="border-radius: 0 8px 8px 0;">
                                <i class="bi bi-send-fill"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
