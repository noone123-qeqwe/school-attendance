@extends('layouts.app')
@section('page-title', 'Student Assistant')

@section('content')
<div class="tch-card" style="max-width:960px;margin:auto;">
    <div class="tch-card-head"><div class="tch-card-title"><i class="bi bi-qr-code me-2"></i> Student Assistant</div></div>
    <div style="padding:20px;">
        <p style="color:#b39b82;">Open an active attendance session for one of your assigned classes.</p>
        @forelse($assignments as $assignment)
            <div class="tch-card mb-3" style="padding:18px;">
                <h2 style="font-size:1.1rem;color:#f3e7cd;">{{ $assignment->subject?->name }} — {{ $assignment->subject?->section }}</h2>
                <p style="color:#b39b82;">Assignment ends {{ $assignment->expires_at->format('M d, Y') }}</p>
                @php $classSessions = $sessions->where('subject_code', $assignment->subject?->code); @endphp
                @forelse($classSessions as $session)
                    <a class="tch-btn tch-btn-primary" href="{{ route('student-assistant.sessions.show', $session) }}">Open active session</a>
                @empty
                    <span style="color:#b39b82;">No attendance session is available right now.</span>
                @endforelse
            </div>
        @empty
            <p style="color:#b39b82;">You have no active Student Assistant assignments.</p>
        @endforelse
    </div>
</div>
@endsection
