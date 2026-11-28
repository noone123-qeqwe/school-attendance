@extends('layouts.app')
@section('page-title', 'Submit Excuse Letter')

@section('content')
@php
    $orderedSubjects = collect($subjects)->sortByDesc(function ($s) use ($attendance, $sameDayAttendances) {
        if ($s->code === $attendance->subject_code) return 2;
        if (isset($sameDayAttendances[$s->code])) return 1;
        return 0;
    })->values();
    $extraSubjectsCount = $orderedSubjects->filter(fn ($s) => $s->code !== $attendance->subject_code && !isset($sameDayAttendances[$s->code]))->count();
@endphp

<div style="max-width:820px;margin:0 auto;">
    <header class="sf-page-head">
        <div>
            <h1>Submit Excuse Letter</h1>
            <p>Provide details for your absence and choose which subject(s) this excuse covers</p>
        </div>
        <div class="sf-page-actions">
            <a href="{{ route('excuses') }}" class="sf-btn sf-btn-ghost">← Back to Excuses</a>
        </div>
    </header>

    @if($errors->any())
        <div class="sf-flash is-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="sf-card">
        {{-- Context Header --}}
        <div class="sf-card-head" style="background:var(--sf-surface-2);border-radius:14px 14px 0 0;">
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <span class="sf-code-chip is-rose">{{ $attendance->subject_code }}</span>
                    <span class="sf-pill is-danger">Awaiting Excuse</span>
                </div>
                <h2 style="margin:0 0 3px;font-size:16px;font-weight:700;color:var(--sf-text);">
                    {{ $attendance->subject->name ?? $attendance->subject_code }}
                </h2>
                <div class="sf-mono" style="font-size:12px;color:var(--sf-text-muted);">
                    {{ \Carbon\Carbon::parse($attendance->date)->format('F j, Y (l)') }} · Unexcused Absent
                </div>
            </div>
        </div>

        <form action="{{ route('excuses.store') }}" method="POST" enctype="multipart/form-data" id="excuseForm">
            @csrf
            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
            <input type="hidden" name="date" value="{{ \Carbon\Carbon::parse($attendance->date)->format('Y-m-d') }}">

            <div class="sf-card-body" style="display:flex;flex-direction:column;gap:18px;">
                {{-- Subject Selection --}}
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                        <div>
                            <label class="sf-label" style="margin-bottom:2px;">Subject(s) Covered *</label>
                            <div style="font-size:11.5px;color:var(--sf-text-muted);">Your missed class is pre-selected. Expand if this excuse covers additional classes on {{ \Carbon\Carbon::parse($attendance->date)->format('M j, Y') }}.</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <span id="subjectCountBadge" class="sf-pill is-rose sf-mono" aria-live="polite">1 of {{ count($subjects) }} Selected</span>
                            @if($extraSubjectsCount > 0)
                                <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm" onclick="selectAllSubjects(true)">Select All</button>
                                <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm" onclick="selectOnlyCurrentSubject()">Only This</button>
                            @endif
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(230px, 1fr));gap:10px;" id="subjectsGrid">
                        @foreach($orderedSubjects as $subject)
                            @php
                                $isPrimary = ($subject->code === $attendance->subject_code);
                                $hasOtherAbsence = isset($sameDayAttendances[$subject->code]);
                                $isExtra = !$isPrimary && !$hasOtherAbsence;
                            @endphp
                            <div class="sd-subject-card-slot {{ $isExtra ? 'is-extra-subject' : '' }}" @if($isExtra) hidden @endif>
                                @include('partials.student-excuse-subject', ['subject' => $subject, 'isPrimary' => $isPrimary, 'hasOtherAbsence' => $hasOtherAbsence])
                            </div>
                        @endforeach
                    </div>

                    @if($extraSubjectsCount > 0)
                        <button type="button" id="toggleExtraSpecificSubjectsBtn" class="sf-btn sf-btn-ghost sf-btn-sm" style="margin-top:10px;" aria-expanded="false" onclick="toggleExtraSpecificSubjects()">
                            Also cover other classes? (+{{ $extraSubjectsCount }})
                        </button>
                    @endif

                    <div id="subjectError" role="alert" style="display:none;color:var(--sf-danger);font-size:12px;margin-top:8px;font-weight:600;">
                        Please select at least one subject to submit your excuse letter.
                    </div>
                </div>

                {{-- Reason --}}
                <div>
                    <label for="excuseReason" class="sf-label">Reason for Absence *</label>
                    <select name="reason" id="excuseReason" class="sf-select" required>
                        <option value="">Select a reason</option>
                        <option value="Medical/Health Issues" @selected(old('reason') == 'Medical/Health Issues')>Medical / Health Issues</option>
                        <option value="Family Emergency" @selected(old('reason') == 'Family Emergency')>Family Emergency</option>
                        <option value="Personal Emergency" @selected(old('reason') == 'Personal Emergency')>Personal Emergency</option>
                        <option value="Transportation Issues" @selected(old('reason') == 'Transportation Issues')>Transportation Issues</option>
                        <option value="Weather Conditions" @selected(old('reason') == 'Weather Conditions')>Weather Conditions / Calamity</option>
                        <option value="Official School Activity / Event" @selected(old('reason') == 'Official School Activity / Event')>Official School Activity / Event</option>
                        <option value="Academic Activity" @selected(old('reason') == 'Academic Activity')>Academic Activity</option>
                        <option value="Other" @selected(old('reason') == 'Other')>Other</option>
                    </select>
                </div>

                {{-- Explanation --}}
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;">
                        <label for="descInput" class="sf-label" style="margin-bottom:0;">Detailed Explanation *</label>
                        <span id="charCount" class="sf-mono" style="font-size:11px;color:var(--sf-text-muted);">0 / 500</span>
                    </div>
                    <textarea name="description" id="descInput" rows="4" maxlength="500" class="sf-textarea" placeholder="Please provide a clear and respectful explanation for your absence..." required>{{ old('description') }}</textarea>
                </div>

                {{-- Supporting Documents --}}
                <div>
                    <label for="attachments" class="sf-label">Supporting Documents (Optional)</label>
                    <input type="file" name="attachments[]" id="attachments" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="sf-input">
                    <div style="font-size:11px;color:var(--sf-text-muted);margin-top:5px;">Accepted formats: JPG, PNG, PDF, DOC, DOCX (Max 5MB each)</div>
                </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;padding:14px 16px;border-top:1px solid var(--sf-border);background:var(--sf-surface-2);border-radius:0 0 14px 14px;">
                <a href="{{ route('excuses') }}" class="sf-btn sf-btn-ghost">Cancel</a>
                <button type="submit" id="submitBtn" class="sf-btn sf-btn-primary">Submit Excuse Letter</button>
            </div>
        </form>
    </section>
</div>

@push('scripts')
<script @cspNonce>
const primarySubjectCode = @json($attendance->subject_code);
let specificSubjectsExpanded = false;

function toggleExtraSpecificSubjects(forceOpen) {
    const slots = document.querySelectorAll('.sd-subject-card-slot.is-extra-subject');
    const btn = document.getElementById('toggleExtraSpecificSubjectsBtn');
    specificSubjectsExpanded = typeof forceOpen === 'boolean' ? forceOpen : !specificSubjectsExpanded;
    slots.forEach(slot => {
        const cb = slot.querySelector('.subject-checkbox');
        slot.hidden = !specificSubjectsExpanded && !(cb && cb.checked);
    });
    if (btn) {
        btn.setAttribute('aria-expanded', specificSubjectsExpanded ? 'true' : 'false');
        btn.textContent = specificSubjectsExpanded ? 'Show fewer subjects' : ('Also cover other classes? (+' + slots.length + ')');
    }
}

function selectAllSubjects(select) {
    if (select) toggleExtraSpecificSubjects(true);
    document.querySelectorAll('.subject-checkbox').forEach(cb => { cb.checked = select; });
    updateSubjectSelectionState();
}

function selectOnlyCurrentSubject() {
    document.querySelectorAll('.subject-checkbox').forEach(cb => { cb.checked = (cb.value === primarySubjectCode); });
    updateSubjectSelectionState();
}

function updateSubjectSelectionState() {
    const cards = document.querySelectorAll('.excuse-subject-card');
    let selectedCount = 0;
    cards.forEach(card => {
        const cb = card.querySelector('.subject-checkbox');
        const checked = Boolean(cb && cb.checked);
        card.classList.toggle('selected', checked);
        card.style.borderColor = checked ? 'var(--sf-rose)' : 'var(--sf-border-strong)';
        card.style.background = checked ? 'var(--sf-brand-soft)' : 'var(--sf-surface-2)';
        if (checked) selectedCount++;
    });
    const badge = document.getElementById('subjectCountBadge');
    if (badge) badge.textContent = selectedCount + ' of ' + cards.length + ' Selected';
    const err = document.getElementById('subjectError');
    if (err && selectedCount > 0) err.style.display = 'none';
}

const descInput = document.getElementById('descInput');
const charCount = document.getElementById('charCount');
if (descInput && charCount) {
    const updateCount = () => { charCount.textContent = descInput.value.length + ' / 500'; };
    descInput.addEventListener('input', updateCount);
    updateCount();
}

document.getElementById('excuseForm')?.addEventListener('submit', function (e) {
    if (!document.querySelectorAll('.subject-checkbox:checked').length) {
        e.preventDefault();
        const err = document.getElementById('subjectError');
        if (err) err.style.display = 'block';
    }
});
document.addEventListener('DOMContentLoaded', updateSubjectSelectionState);
</script>
@endpush
@endsection
