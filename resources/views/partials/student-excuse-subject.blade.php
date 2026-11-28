@php
    $isPrimary = $isPrimary ?? false;
    $hasOtherAbsence = $hasOtherAbsence ?? false;
    $selected = in_array($subject->code, old('subject_codes', $isPrimary ? [$subject->code] : []));
@endphp
<label class="excuse-subject-card {{ $selected ? 'selected' : '' }}"
       style="display:flex;align-items:flex-start;gap:10px;padding:12px 13px;border-radius:11px;border:1px solid {{ $selected ? 'var(--sf-rose)' : 'var(--sf-border-strong)' }};background:{{ $selected ? 'var(--sf-brand-soft)' : 'var(--sf-surface-2)' }};cursor:pointer;transition:border-color 140ms ease, background-color 140ms ease;">
    <input type="checkbox"
           name="subject_codes[]"
           value="{{ $subject->code }}"
           id="excuse-subject-{{ $subject->id }}"
           class="subject-checkbox"
           style="margin-top:3px;accent-color:var(--sf-brand-strong);"
           @checked($selected)
           onchange="updateSubjectSelectionState()">
    <span style="display:flex;flex-direction:column;gap:3px;min-width:0;flex:1;">
        <span style="display:flex;align-items:center;justify-content:space-between;gap:6px;flex-wrap:wrap;">
            <span class="sf-code-chip is-rose">{{ $subject->code }}</span>
            @if($isPrimary)
                <span class="sf-pill is-danger">Current absence</span>
            @elseif($hasOtherAbsence)
                <span class="sf-pill is-amber">Also absent</span>
            @elseif($subject->section)
                <span style="font-size:11px;color:var(--sf-text-muted);">Sec {{ $subject->section }}</span>
            @endif
        </span>
        <span style="font-size:13px;font-weight:600;color:var(--sf-text);line-height:1.3;">{{ $subject->name }}</span>
        <span style="font-size:11.5px;color:var(--sf-text-muted);">{{ $subject->instructorUser?->name ?: ($subject->getRawOriginal('instructor') ?: 'Instructor not assigned') }}</span>
    </span>
</label>
