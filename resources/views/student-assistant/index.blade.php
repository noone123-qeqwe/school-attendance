@extends('student-assistant.layout')

@section('assistant-content')
@php
    $sessionsByClass = $sessions->groupBy('subject_code');
    $orderedAssignments = $assignments->sortByDesc(fn ($assignment) => $sessionsByClass->has($assignment->subject?->code));
    $showHistory = $attendanceReports->isNotEmpty() || $assignments->contains('can_submit_attendance', true);
@endphp

<header class="assistant-page-head">
    <div>
        <h1>Assigned classes</h1>
        <p class="assistant-muted">Open an active session to display the attendance QR.</p>
    </div>
    @if($showHistory || ($assignments->isNotEmpty() && $sessions->isEmpty()))
        <nav class="assistant-quick-links" aria-label="Student Assistant actions">
            @if($assignments->isNotEmpty() && $sessions->isEmpty())
                <a class="assistant-text-link" href="{{ route('student-assistant.classes') }}">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Check for sessions
                </a>
            @endif
            @if($showHistory)
                <a class="assistant-text-link" href="#assistant-history">
                    <i class="bi bi-clock-history" aria-hidden="true"></i> Submission history
                </a>
            @endif
        </nav>
    @endif
</header>

<div class="assistant-class-list">
    @forelse($orderedAssignments as $assignment)
        @php $classSessions = $sessionsByClass->get($assignment->subject?->code, collect()); @endphp
        <article @class(['assistant-panel assistant-class', 'assistant-class--live' => $classSessions->isNotEmpty()])>
            <div class="assistant-class-details">
                <div class="assistant-class-heading">
                    <h2 class="assistant-panel-title">{{ $assignment->subject?->name ?? 'Assigned class' }}</h2>
                    @if($classSessions->isNotEmpty())
                        <span class="assistant-status assistant-status--approved"><i class="bi bi-broadcast" aria-hidden="true"></i> Live session</span>
                    @endif
                </div>
                <p class="assistant-muted">
                    <span>{{ $assignment->subject?->code }}</span>
                    @if($assignment->subject?->section)
                        <span> · {{ $assignment->subject->section }}</span>
                    @endif
                    <span> · Assigned until {{ $assignment->expires_at->format('M j, Y') }}</span>
                </p>
                <p class="assistant-class-duty">
                    {{ $assignment->can_submit_attendance ? 'QR and attendance submissions' : 'QR management' }}
                </p>
            </div>
            <div class="assistant-class-actions">
                @forelse($classSessions as $session)
                    <a class="assistant-button assistant-button--primary" href="{{ route('student-assistant.sessions.show', $session) }}">
                        <i class="bi bi-qr-code" aria-hidden="true"></i>
                        {{ $classSessions->count() > 1 ? 'Open session ending ' . $session->session_ends_at->format('g:i A') : 'Open QR session' }}
                    </a>
                @empty
                    <p class="assistant-muted assistant-class-wait">
                        <i class="bi bi-clock" aria-hidden="true"></i> Waiting for a teacher session
                    </p>
                @endforelse
            </div>
        </article>
    @empty
        <section class="assistant-panel assistant-empty" aria-labelledby="assistant-empty-title">
            <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
            <h2 id="assistant-empty-title" class="assistant-panel-title">No active assignments</h2>
            <p class="assistant-muted">Your classes will appear here when a Student Assistant assignment begins.</p>
        </section>
    @endforelse
</div>

@if($showHistory)
    <section class="assistant-panel assistant-history" id="assistant-history" aria-labelledby="assistant-history-heading">
        <h2 id="assistant-history-heading" class="assistant-panel-title">Recent submissions</h2>
        @if($attendanceReports->isEmpty())
            <p class="assistant-muted">Submissions you send for teacher review will appear here.</p>
        @else
            @include('student-assistant.report-list', ['showReportSession' => true])
        @endif
    </section>
@endif
@endsection
