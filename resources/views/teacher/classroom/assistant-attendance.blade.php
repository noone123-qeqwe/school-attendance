@extends('layouts.app')
@section('page-title', 'Assistant submissions')

@section('content')
<div class="mx-auto" style="max-width: 880px;">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('teacher.classroom.show', $subject->code) }}" class="tch-btn tch-btn-secondary">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to class
        </a>
        <a href="{{ route('teacher.qr', $subject->code) }}" class="tch-btn tch-btn-secondary">Attendance QR</a>
    </div>

    <h1 class="h3 mb-2">Assistant submissions</h1>
    <p class="mb-3">{{ $subject->name }} · {{ $subject->code }}</p>
    <p style="color: var(--text-secondary);">Confirm the student's attendance before approving. Approval updates the official class record.</p>

    <nav aria-label="Submission status" class="d-flex flex-wrap gap-2 mb-3">
        @foreach (['pending' => 'Needs review', 'approved' => 'Approved', 'dismissed' => 'Dismissed'] as $value => $label)
            <a href="{{ route('teacher.classroom.assistant-attendance.index', ['subjectCode' => $subject->code, 'status' => $value]) }}"
               class="tch-btn {{ $status === $value ? 'tch-btn-primary' : 'tch-btn-secondary' }}"
               @if ($status === $value) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    @error('decision')
        <p role="alert" class="alert alert-warning">{{ $message }}</p>
    @enderror

    <div class="d-grid gap-3 mb-3">
        @forelse ($reports as $report)
            <article class="tch-card p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-2">
                    <h2 class="h5 mb-0">{{ $report->student?->name ?? 'Former student' }}</h2>
                    <span style="color: var(--gold);">{{ ucfirst($report->suggested_status) }}</span>
                </div>
                <p class="mb-2" style="color: var(--text-secondary);">
                    Submitted by {{ $report->submitter?->name ?? 'Former assistant' }}
                    · {{ $report->created_at->timezone('Asia/Manila')->format('M j, Y · g:i A') }}
                </p>
                <p class="mb-2" style="color: var(--text-secondary);">
                    Session began {{ $report->session->created_at->timezone('Asia/Manila')->format('M j, Y · g:i A') }}
                </p>
                @if ($report->reason)
                    <p class="mb-2 text-break">{{ $report->reason }}</p>
                @endif
                @if ($report->review_status === 'pending')
                    <form method="POST" action="{{ route('teacher.classroom.assistant-attendance.update', [$subject->code, $report]) }}" class="d-flex flex-wrap gap-2 mt-3">
                        @csrf
                        @method('PATCH')
                        <button type="submit" name="decision" value="approve" class="tch-btn tch-btn-primary">Approve and record</button>
                        <button type="submit" name="decision" value="dismiss" class="tch-btn tch-btn-secondary">Dismiss</button>
                    </form>
                @else
                    <p class="mb-0" style="color: var(--text-secondary);">
                        {{ ucfirst($report->review_status) }} by {{ $report->reviewer?->name ?? 'Former teacher' }}
                        · {{ $report->reviewed_at?->timezone('Asia/Manila')->format('M j, Y · g:i A') }}
                    </p>
                @endif
            </article>
        @empty
            <p class="tch-card p-3 mb-0">There are no {{ $status }} submissions for this class.</p>
        @endforelse
    </div>

    {{ $reports->links() }}
</div>
@endsection
