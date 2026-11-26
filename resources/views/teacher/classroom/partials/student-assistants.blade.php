<section class="classroom-header" aria-labelledby="assistant-heading">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h2 id="assistant-heading" style="font-size:1.25rem;font-weight:700;color:#f3e7cd;margin:0;">Student Assistants</h2>
            <p style="color:#b39b82;margin:5px 0 0;">Assign up to two enrolled students to display the QR and, if permitted, submit attendance for your review.</p>
        </div>
        @if($availableAssistantSlots > 0 && $eligibleAssistantStudents->isNotEmpty())
            <button type="button" class="btn-action" data-bs-toggle="modal" data-bs-target="#assignAssistantModal">
                <i class="bi bi-person-plus"></i> Assign Student Assistant
            </button>
        @endif
    </div>

    @if($errors->any())
        <div class="alert alert-danger mt-3" role="alert">{{ $errors->first() }}</div>
    @endif

    @php
        $currentAssistants = $studentAssistants->filter(fn ($item) => $item->active_slot !== null);
        $pastAssistants = $studentAssistants->filter(fn ($item) => $item->active_slot === null);
    @endphp
    @if($currentAssistants->isEmpty())
        <p style="color:#b39b82;margin:18px 0 0;">No Student Assistants assigned yet.</p>
    @else
        <div class="row g-3 mt-1">
            @foreach($currentAssistants as $assistant)
                <div class="col-12 col-md-6">
                    <div class="info-card h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                            <div>
                                <div class="info-label">Assistant {{ $assistant->active_slot }} · {{ $assistant->status }}</div>
                                <div class="info-value">{{ $assistant->student?->name ?? 'Unavailable student' }}</div>
                                <small style="color:#b39b82;">Through {{ $assistant->expires_at->format('M d, Y') }}</small>
                                <div><small>{{ $assistant->can_submit_attendance ? 'QR and attendance submissions' : 'QR only' }}</small></div>
                            </div>
                            @if($assistant->revoked_at === null && $assistant->expires_at >= now())
                                <div class="d-flex gap-2 flex-wrap">
                                    @if($eligibleAssistantStudents->isNotEmpty())
                                        <button type="button" class="btn-secondary btn-action" data-bs-toggle="modal" data-bs-target="#replaceAssistantModal{{ $assistant->id }}">Change</button>
                                    @endif
                                    <form method="POST" action="{{ route('teacher.classroom.assistants.destroy', [$subject->code, $assistant->id]) }}" onsubmit="return confirm('Remove this Student Assistant?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-secondary btn-action">Remove</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                        @if($assistant->revoked_at === null && $assistant->expires_at >= now())
                            <details class="mt-3">
                                <summary>Change permissions</summary>
                                <form action="{{ route('teacher.classroom.assistants.permissions', [$subject->code, $assistant->id]) }}" method="POST">
                                    @csrf @method('PATCH')
                                    @include('partials.student-assistant-permission', [
                                        'permissionId' => 'assistantEditSubmission' . $assistant->id,
                                        'allowSubmission' => $assistant->can_submit_attendance,
                                    ])
                                    <button type="submit" class="btn-secondary btn-action mt-2">Save permissions</button>
                                </form>
                            </details>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    @if($pastAssistants->isNotEmpty())
        <details class="mt-3" style="color:#b39b82;">
            <summary>Previous assignments ({{ $pastAssistants->count() }})</summary>
            <ul class="mt-2 mb-0">
                @foreach($pastAssistants as $assistant)
                    <li>{{ $assistant->student?->name ?? 'Unavailable student' }} — {{ $assistant->status }}</li>
                @endforeach
            </ul>
        </details>
    @endif
</section>

@if($availableAssistantSlots > 0 && $eligibleAssistantStudents->isNotEmpty())
<div class="modal fade" id="assignAssistantModal" tabindex="-1" aria-labelledby="assignAssistantTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('teacher.classroom.assistants.store', $subject->code) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="assignAssistantTitle">Assign Student Assistant</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @include('partials.student-assistant-assignment-fields', ['assistant' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary btn-action" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-action">Assign</button>
            </div>
        </form>
    </div>
</div>
@endif

@foreach($currentAssistants->filter(fn ($item) => $item->revoked_at === null && $item->expires_at >= now()) as $assistant)
    @if($eligibleAssistantStudents->isNotEmpty())
        <div class="modal fade" id="replaceAssistantModal{{ $assistant->id }}" tabindex="-1" aria-labelledby="replaceAssistantTitle{{ $assistant->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('teacher.classroom.assistants.replace', [$subject->code, $assistant->id]) }}" class="modal-content">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="replaceAssistantTitle{{ $assistant->id }}">Change {{ $assistant->student?->name }}'s Assignment</h2>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('partials.student-assistant-assignment-fields')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary btn-action" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-action">Change</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach
