@php
    $isPrimary = $isPrimary ?? false;
    $hasOtherAbsence = $hasOtherAbsence ?? false;
    $selected = in_array($subject->code, old('subject_codes', $isPrimary ? [$subject->code] : []));
@endphp
<label class="excuse-subject-card {{ $selected ? 'selected' : '' }}">
    <input type="checkbox" name="subject_codes[]" value="{{ $subject->code }}" id="excuse-subject-{{ $subject->id }}" class="subject-checkbox" @checked($selected) onchange="updateSubjectSelectionState()">
    <span class="student-excuse-subject-copy">
        <span class="student-excuse-subject-heading">
            <span class="sub-code-badge">{{ $subject->code }}</span>
            @if($isPrimary)
                <span class="excuse-sub-flag is-primary" style="font-size:.75rem;color:#fca5a5;">Current absence</span>
            @elseif($hasOtherAbsence)
                <span class="excuse-sub-flag is-other" style="font-size:.75rem;color:#fcd34d;">Also absent</span>
            @elseif($subject->section)
                <span class="excuse-sub-flag is-sec" style="font-size:.75rem;color:var(--cs-muted, #b39b82);">Section {{ $subject->section }}</span>
            @endif
        </span>
        <span class="student-excuse-subject-name">{{ $subject->name }}</span>
        <span class="student-excuse-subject-teacher"><i class="bi bi-person-badge me-1" aria-hidden="true"></i>{{ $subject->instructorUser?->name ?: ($subject->getRawOriginal('instructor') ?: 'Instructor not assigned') }}</span>
    </span>
</label>
