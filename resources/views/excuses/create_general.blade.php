@extends('layouts.app')
@section('page-title', 'Submit Excuse Letter')
@section('page-sub', 'Select affected subjects and attach supporting documents')

@section('content')
<div class="ent-section ent-fade-up student-excuse-form" style="max-width: 880px; margin: 0 auto;">
    <header class="sd-excuse-hero">
        <div>
            <h1>
                <i class="bi bi-file-earmark-medical me-2" style="color: #e48fa0;" aria-hidden="true"></i>Submit Excuse Letter
            </h1>
            <p>Choose the date and subjects for an existing absence, then add your explanation and supporting documents.</p>
        </div>
        <x-back-link :href="route('excuses')" label="Back to Excuses" variant="entity" />
    </header>

    <x-error-summary title="Please review your excuse submission" />

    <div class="sd-excuse-form-card">
        <form action="{{ route('excuses.store_general') }}" method="POST" enctype="multipart/form-data" id="excuseForm">
            @csrf

            <div class="sd-excuse-form-body">
                {{-- STEP 1: CHOOSE SUBJECT(S) --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <label class="sd-form-label mb-1">
                                <i class="bi bi-journal-check" style="color: #e48fa0;" aria-hidden="true"></i>
                                Choose Subjects <span class="text-danger">*</span>
                            </label>
                            <span style="font-size: 0.8rem; color: #baa3ab;">You can select one or multiple subjects</span>
                        </div>
                        <div class="student-excuse-selection-actions d-flex align-items-center gap-2 flex-wrap">
                            <span id="subjectCountBadge" class="sub-code-badge" aria-live="polite" style="border-radius: 999px; padding: 4px 11px;">
                                0 of {{ count($subjects) }} Selected
                            </span>
                            <button type="button" class="quick-sub-btn" onclick="selectAllSubjects(true)">Select All</button>
                            <button type="button" class="quick-sub-btn" onclick="selectAllSubjects(false)">Clear</button>
                        </div>
                    </div>

                    @if($subjects->isEmpty())
                        <div class="sd-excuse-empty" style="background: rgba(15,4,7,0.45); border-radius: 14px; border: 1px dashed rgba(236,224,228,0.2);">
                            <i class="bi bi-journal-x" style="font-size: 2rem; color: #baa3ab;" aria-hidden="true"></i>
                            <h4>No enrolled subjects found</h4>
                            <p>Enrolled subjects for this semester will appear here once assigned.</p>
                        </div>
                    @else
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px;" id="subjectsGrid">
                            @foreach($subjects as $subject)
                                @include('partials.student-excuse-subject', ['subject' => $subject, 'isPrimary' => false, 'hasOtherAbsence' => false])
                            @endforeach
                        </div>
                    @endif

                    <div id="subjectError" role="alert" style="display: none; color: #ff8e9b; font-size: 0.8rem; margin-top: 10px; font-weight: 600;">
                        <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> Please select at least one subject to submit your excuse letter.
                    </div>
                </div>

                {{-- STEP 2: DATE & REASON --}}
                <div class="student-excuse-fields mb-4">
                    <div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <label for="absenceDateInput" class="sd-form-label mb-0">
                                Absence Date <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex gap-1">
                                <button type="button" class="quick-date-btn" onclick="setDatePreset(0)">Today</button>
                                <button type="button" class="quick-date-btn" onclick="setDatePreset(-1)">Yesterday</button>
                            </div>
                        </div>
                        <input type="date" name="date" id="absenceDateInput" max="{{ today()->toDateString() }}" value="{{ old('date', date('Y-m-d')) }}" class="sd-form-input" required>
                        @error('date')
                            <div class="mt-2 small fw-semibold" style="color: #ff8e9b;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="excuseReason" class="sd-form-label">
                            Reason for Absence <span class="text-danger">*</span>
                        </label>
                        <select name="reason" id="excuseReason" class="sd-form-input" required>
                            <option value="">Select a reason</option>
                            <option value="Medical/Health Issues" {{ old('reason') == 'Medical/Health Issues' ? 'selected' : '' }}>Medical / Health Issues</option>
                            <option value="Family Emergency" {{ old('reason') == 'Family Emergency' ? 'selected' : '' }}>Family Emergency</option>
                            <option value="Personal Emergency" {{ old('reason') == 'Personal Emergency' ? 'selected' : '' }}>Personal Emergency</option>
                            <option value="Transportation Issues" {{ old('reason') == 'Transportation Issues' ? 'selected' : '' }}>Transportation Issues</option>
                            <option value="Weather Conditions" {{ old('reason') == 'Weather Conditions' ? 'selected' : '' }}>Weather Conditions / Calamity</option>
                            <option value="Official School Activity / Event" {{ old('reason') == 'Official School Activity / Event' ? 'selected' : '' }}>Official School Activity / Event</option>
                            <option value="Academic Activity" {{ old('reason') == 'Academic Activity' ? 'selected' : '' }}>Academic Activity</option>
                            <option value="Other" {{ old('reason') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('reason')
                            <div class="mt-2 small fw-semibold" style="color: #ff8e9b;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- STEP 3: DETAILED DESCRIPTION --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <label for="descInput" class="sd-form-label mb-0">
                            Detailed Explanation <span class="text-danger">*</span>
                        </label>
                        <span id="charCount" class="sd-tabular" style="font-size: 0.76rem; color: #baa3ab;">0 / 500</span>
                    </div>
                    <textarea name="description" id="descInput" rows="4" maxlength="500" class="sd-form-input" style="resize: vertical;" placeholder="Please provide a clear and respectful explanation for your absence in the selected subject(s)..." required>{{ old('description') }}</textarea>
                    @error('description')
                        <div class="mt-2 small fw-semibold" style="color: #ff8e9b;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- STEP 4: SUPPORTING DOCUMENTS --}}
                <div>
                    <label for="attachments" class="sd-form-label">
                        Supporting Documents / Attachments (Optional)
                    </label>

                    <div class="position-relative w-100">
                        <input type="file" name="attachments[]" id="attachments" multiple
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" onchange="handleFileSelect(this)"
                               class="visually-hidden">

                        <label for="attachments" id="dropZone" class="sd-dropzone">
                            <i class="bi bi-cloud-arrow-up" style="font-size: 2.2rem; color: #e48fa0; margin-bottom: 8px;" aria-hidden="true"></i>
                            <span style="font-size: 0.95rem; font-weight: 700; color: #fcf9fa;">Click or drag &amp; drop files here</span>
                            <span style="font-size: 0.8rem; color: #baa3ab; margin-top: 4px;">Medical certificates, letters, or official receipts</span>
                        </label>
                    </div>

                    <div style="font-size: 0.76rem; color: #baa3ab; margin-top: 8px;">
                        Accepted formats: JPG, PNG, PDF, DOC, DOCX (Max 5MB each)
                    </div>

                    <div id="fileList" class="mt-3 d-flex flex-column gap-2"></div>

                    @error('attachments.*')
                        <div class="mt-2 small fw-semibold" style="color: #ff8e9b;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="sd-excuse-form-footer">
                <a href="{{ route('excuses') }}" class="ent-btn ent-btn-secondary">Cancel</a>
                <button type="submit" id="submitBtn" class="ent-btn ent-btn-primary" style="padding: 10px 24px;">
                    <i class="bi bi-send-fill me-1" aria-hidden="true"></i> <span>Submit Excuse Letter</span>
                </button>
            </div>
        </form>
    </div>
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
        if (cb && cb.checked) {
            card.classList.add('selected');
            selectedCount++;
        } else {
            card.classList.remove('selected');
        }
    });

    const badge = document.getElementById('subjectCountBadge');
    if (badge) badge.textContent = `${selectedCount} of ${cards.length} Selected`;

    const err = document.getElementById('subjectError');
    if (err && selectedCount > 0) err.style.display = 'none';
}

function setDatePreset(offsetDays) {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const el = document.getElementById('absenceDateInput');
    if (el) el.value = `${yyyy}-${mm}-${dd}`;
}

const descInput = document.getElementById('descInput');
const charCount = document.getElementById('charCount');
if (descInput && charCount) {
    const updateCount = () => {
        const len = descInput.value.length;
        charCount.textContent = `${len} / 500`;
        charCount.style.color = len >= 490 ? '#ff6b7a' : '#baa3ab';
    };
    descInput.addEventListener('input', updateCount);
    updateCount();
}

const dropZone = document.getElementById('dropZone');
if (dropZone) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => { e.preventDefault(); e.stopPropagation(); }, false);
    });
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('is-dragover'), false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('is-dragover'), false);
    });
    dropZone.addEventListener('drop', (e) => {
        const input = document.getElementById('attachments');
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            handleFileSelect(input);
        }
    }, false);
}

function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'bi-image text-success';
    if (ext === 'pdf') return 'bi-file-pdf text-danger';
    if (['doc', 'docx'].includes(ext)) return 'bi-file-word text-info';
    return 'bi-file-earmark-text text-secondary';
}

function handleFileSelect(input) {
    const fileList = document.getElementById('fileList');
    fileList.innerHTML = '';

    if (input.files.length > 0) {
        Array.from(input.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'sd-file-chip';
            const iconClass = getFileIcon(file.name);
            const left = document.createElement('div');
            left.style.cssText = 'display:flex;align-items:center;gap:12px;min-width:0;';
            const icon = document.createElement('i');
            icon.className = `bi ${iconClass}`;
            icon.style.fontSize = '1.2rem';
            icon.setAttribute('aria-hidden', 'true');
            const info = document.createElement('div');
            info.style.cssText = 'display:flex;flex-direction:column;min-width:0;';
            const nameEl = document.createElement('span');
            nameEl.style.cssText = 'font-weight:700;line-height:1.25;overflow-wrap:anywhere;';
            nameEl.textContent = file.name;
            const sizeEl = document.createElement('span');
            sizeEl.style.cssText = 'color:#baa3ab;font-size:0.75rem;line-height:1.2;';
            sizeEl.textContent = `${(file.size / 1024 / 1024).toFixed(2)} MB`;
            info.append(nameEl, sizeEl);
            left.append(icon, info);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.setAttribute('aria-label', `Remove ${file.name}`);
            removeBtn.style.cssText = 'background:none;border:none;color:#ff8e9b;cursor:pointer;padding:6px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;min-width:36px;min-height:36px;';
            removeBtn.innerHTML = '<i class="bi bi-trash3" aria-hidden="true"></i>';
            removeBtn.addEventListener('click', () => removeFile(index));

            fileItem.append(left, removeBtn);
            fileList.appendChild(fileItem);
        });
    }
}

function removeFile(index) {
    const input = document.getElementById('attachments');
    const dt = new DataTransfer();
    Array.from(input.files).forEach((file, i) => { if (i !== index) dt.items.add(file); });
    input.files = dt.files;
    handleFileSelect(input);
}

const form = document.getElementById('excuseForm');
if (form) {
    form.addEventListener('submit', function(e) {
        const selected = document.querySelectorAll('.subject-checkbox:checked');
        if (selected.length === 0) {
            e.preventDefault();
            const err = document.getElementById('subjectError');
            if (err) err.style.display = 'block';
            document.getElementById('subjectsGrid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        const btn = document.getElementById('submitBtn');
        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.8';
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> <span>Submitting...</span>';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    updateSubjectSelectionState();
});
</script>
@endpush
@endsection
