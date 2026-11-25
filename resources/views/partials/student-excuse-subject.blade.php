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
                <span style="font-size:.75rem;color:#ff8e9b;font-weight:700;">Current absence</span>
            @elseif($hasOtherAbsence)
                <span style="font-size:.75rem;color:#fbbf24;font-weight:700;">Also absent</span>
            @elseif($subject->section)
                <span style="font-size:.75rem;color:#baa3ab;">Section {{ $subject->section }}</span>
            @endif
        </span>
        <span class="student-excuse-subject-name">{{ $subject->name }}</span>
        <span class="student-excuse-subject-teacher"><i class="bi bi-person-badge me-1" aria-hidden="true"></i>{{ $subject->instructorUser?->name ?: ($subject->getRawOriginal('instructor') ?: 'Instructor not assigned') }}</span>
    </span>
</label>
