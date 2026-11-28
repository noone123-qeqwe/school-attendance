@extends('layouts.app')
@section('page-title', 'Submit Excuse Letter')

@section('content')
<div style="max-width:820px;margin:0 auto;">
    <header class="sf-page-head">
        <div>
            <h1>Submit Excuse Letter</h1>
            <p>Choose the absence date and subject(s), then attach supporting documentation for review</p>
        </div>
        <div class="sf-page-actions">
            <a href="{{ route('excuses') }}" class="sf-btn sf-btn-ghost">← Back to Excuses</a>
        </div>
    </header>

    @if($errors->any())
        <div class="sf-flash is-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="sf-card">
        <form action="{{ route('excuses.store_general') }}" method="POST" enctype="multipart/form-data" id="excuseForm">
            @csrf

            <div class="sf-card-body" style="display:flex;flex-direction:column;gap:18px;">
                {{-- Date & Reason --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <label for="absenceDateInput" class="sf-label" style="margin-bottom:0;">Absence Date *</label>
                            <div style="display:flex;gap:4px;">
                                <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm" style="height:24px;font-size:11px;" onclick="setDatePreset(0)">Today</button>
                                <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm" style="height:24px;font-size:11px;" onclick="setDatePreset(-1)">Yesterday</button>
                            </div>
                        </div>
                        <input type="date" name="date" id="absenceDateInput" max="{{ today()->toDateString() }}" value="{{ old('date', date('Y-m-d')) }}" class="sf-input" required>
                    </div>

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
                </div>

                {{-- Subject Selector --}}
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                        <div>
                            <label class="sf-label" style="margin-bottom:2px;">Choose Subjects *</label>
                            <div style="font-size:11.5px;color:var(--sf-text-muted);">Select one or more enrolled classes missed on this date</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <span id="subjectCountBadge" class="sf-pill is-rose sf-mono" aria-live="polite">0 of {{ count($subjects) }} Selected</span>
                            <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm" onclick="selectAllSubjects(true)">Select All</button>
                            <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm" onclick="selectAllSubjects(false)">Clear</button>
                        </div>
                    </div>

                    @if($subjects->isEmpty())
                        <div class="sf-empty-state" style="padding:24px;border:1px dashed var(--sf-border-strong);border-radius:12px;">
                            <h3>No enrolled subjects found</h3>
                            <p>Enrolled subjects for this semester will appear here once assigned.</p>
                        </div>
                    @else
                        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(230px, 1fr));gap:10px;" id="subjectsGrid">
                            @foreach($subjects as $subject)
                                @include('partials.student-excuse-subject', ['subject' => $subject, 'isPrimary' => false, 'hasOtherAbsence' => false])
                            @endforeach
                        </div>
                    @endif

                    <div id="subjectError" role="alert" style="display:none;color:var(--sf-danger);font-size:12px;margin-top:8px;font-weight:600;">
                        Please select at least one subject to submit your excuse letter.
                    </div>
                </div>

                {{-- Detailed Explanation --}}
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;">
                        <label for="descInput" class="sf-label" style="margin-bottom:0;">Detailed Explanation *</label>
                        <span id="charCount" class="sf-mono" style="font-size:11px;color:var(--sf-text-muted);">0 / 500</span>
                    </div>
                    <textarea name="description" id="descInput" rows="4" maxlength="500" class="sf-textarea" placeholder="Please provide a clear explanation for your absence..." required>{{ old('description') }}</textarea>
                </div>

                {{-- Attachments --}}
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
function selectAllSubjects(select) {
    document.querySelectorAll('.subject-checkbox').forEach(cb => { cb.checked = select; });
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

function setDatePreset(offsetDays) {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    const el = document.getElementById('absenceDateInput');
    if (el) {
        el.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
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
