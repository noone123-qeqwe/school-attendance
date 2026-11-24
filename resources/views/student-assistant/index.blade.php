@extends('layouts.app')
@section('page-title', 'Student Assistant')

@section('content')
<div class="d-grid gap-3 mx-auto" style="max-width:960px;">
    <section class="tch-card" aria-labelledby="assistant-classes-heading">
        <div class="tch-card-head">
            <h1 id="assistant-classes-heading" class="tch-card-title mb-0">
                <i class="bi bi-qr-code" aria-hidden="true"></i> Assigned classes
            </h1>
        </div>
        <div class="p-3 p-sm-4 d-grid gap-3">
            @forelse($assignments as $assignment)
                <div class="tch-card p-3">
                    <h2 class="h6 text-break">{{ $assignment->subject?->name }} · {{ $assignment->subject?->section }}</h2>
                    <p style="color:#b39b82;" class="mb-1">Assignment ends {{ $assignment->expires_at->format('M d, Y') }}</p>
                    <p style="color:#b39b82;">{{ $assignment->can_submit_attendance ? 'Manage the QR and submit attendance for teacher review.' : 'Manage the attendance QR.' }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        @forelse($sessions->where('subject_code', $assignment->subject?->code) as $session)
                            <a class="ent-btn ent-btn-primary" href="{{ route('student-assistant.sessions.show', $session) }}">Open active session</a>
                        @empty
                            <span style="color:#b39b82;">No attendance session is available right now.</span>
                        @endforelse
                    </div>
                </div>
            @empty
                <p class="mb-0" style="color:#b39b82;">You have no active Student Assistant assignments.</p>
            @endforelse
        </div>
    </section>
    @if($attendanceReports->isNotEmpty() || $assignments->contains('can_submit_attendance', true))
        <section class="tch-card" aria-labelledby="assistant-history-heading">
            <div class="tch-card-head">
                <h2 id="assistant-history-heading" class="tch-card-title mb-0">
                    <i class="bi bi-clock-history" aria-hidden="true"></i> Recent attendance submissions
                </h2>
            </div>
            <div class="p-3 p-sm-4">
                @if($attendanceReports->isEmpty())
                    <p class="mb-0" style="color:#b39b82;">Submissions you send for teacher review will appear here.</p>
                @else
                    @include('student-assistant.report-list', ['showReportSession' => true])
                @endif
            </div>
        </section>
    @endif
</div>
@endsection
