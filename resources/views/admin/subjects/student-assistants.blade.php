@extends('layouts.app')

@section('title', 'Student Assistants · ' . $subject->code)

@section('content')
<div class="admin-page-heading">
    <div>
        <h1 class="saas-heading saas-heading-lg">Student Assistants</h1>
        <p class="saas-text-muted">
            {{ $subject->code }} · {{ $subject->name }}
            @if($subject->instructorUser)
                · {{ $subject->instructorUser->name }}
            @endif
        </p>
    </div>
    <x-back-link :href="route('admin.subjects')" label="Back to Subjects" variant="saas" />
</div>

@if(session('success'))
    <div class="ent-alert success mb-3" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="ent-alert danger mb-3" role="alert">{{ $errors->first() }}</div>
@endif

<x-card type="section" title="Current assignments" class="ent-mb-lg">
    <p class="saas-text-muted mb-3">{{ $assignments->count() }} of 2 slots in use. The teacher receives a notification when an admin changes an assignment.</p>
    @forelse($assignments as $assistant)
        <div class="py-3 {{ $loop->first ? '' : 'border-top' }}">
            <h2 class="h6 mb-1">{{ $assistant->student?->name ?? 'Unavailable student' }}</h2>
            <p class="saas-text-muted mb-1">
                {{ $assistant->status }} · {{ $assistant->starts_at->format('M j, Y') }} to {{ $assistant->expires_at->format('M j, Y') }}
            </p>
            <p class="mb-1">{{ $assistant->can_submit_attendance ? 'QR and attendance submissions' : 'QR only' }}</p>
            @if($assistant->assigningAdmin)
                <small class="saas-text-muted">Appointed by {{ $assistant->assigningAdmin->name }} (admin)</small>
            @endif
            <details class="mt-2">
                <summary>Change permissions</summary>
                <form action="{{ route('admin.subjects.assistants.permissions', [$subject, $assistant]) }}" method="POST">
                    @csrf @method('PATCH')
                    @include('partials.student-assistant-permission', [
                        'permissionId' => 'adminEditSubmission' . $assistant->id,
                        'allowSubmission' => $assistant->can_submit_attendance,
                    ])
                    <button type="submit" class="saas-btn saas-btn-secondary mt-2">Save permissions</button>
                </form>
            </details>
            @if($canAppoint && $eligibleAssistantStudents->isNotEmpty())
                <details class="mt-2">
                    <summary>Replace assistant</summary>
                    <form action="{{ route('admin.subjects.assistants.replace', [$subject, $assistant]) }}" method="POST" class="mt-3">
                        @csrf @method('PUT')
                        @include('partials.student-assistant-assignment-fields', [
                            'labelClass' => 'ent-label',
                            'inputClass' => 'ent-input',
                        ])
                        <button type="submit" class="saas-btn saas-btn-secondary mt-3">Replace assistant</button>
                    </form>
                </details>
            @endif
            <details class="mt-2">
                <summary>Remove assistant</summary>
                <form action="{{ route('admin.subjects.assistants.destroy', [$subject, $assistant]) }}" method="POST" class="mt-2">
                    @csrf @method('DELETE')
                    <p class="saas-text-muted mb-2">This ends the assignment immediately.</p>
                    <button type="submit" class="saas-btn saas-btn-secondary">Confirm removal</button>
                </form>
            </details>
        </div>
    @empty
        <p class="mb-0">There are no current assistants for this class.</p>
    @endforelse
</x-card>

<x-card type="section" title="Appoint an assistant">
    @if(!$canAppoint)
        <p class="mb-0">Assign an active teacher to this class before appointing an assistant.</p>
    @elseif($assignments->count() >= 2)
        <p class="mb-0">Both slots are in use. Replace or remove an assistant from the current assignments.</p>
    @elseif($eligibleAssistantStudents->isEmpty())
        <p class="mb-0">There are no other active students in this class.</p>
    @else
        <form action="{{ route('admin.subjects.assistants.store', $subject) }}" method="POST">
            @csrf
            @include('partials.student-assistant-assignment-fields', [
                'assistant' => null,
                'labelClass' => 'ent-label',
                'inputClass' => 'ent-input',
            ])
            <button type="submit" class="saas-btn saas-btn-primary mt-3">Appoint assistant</button>
        </form>
    @endif
</x-card>
@endsection
