@extends('layouts.app')
@section('page-title', 'Submit Excuse Letter')

@section('content')
<style>
.excuse-subject-card {
    background: rgba(255, 235, 190, 0.03);
    border: 1.5px solid rgba(255, 215, 145, 0.12);
    border-radius: 12px;
    padding: 14px 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 14px;
    user-select: none;
}
.excuse-subject-card:hover {
    background: rgba(255, 235, 190, 0.06);
    border-color: rgba(207, 164, 111, 0.35);
    transform: translateY(-1px);
}
.excuse-subject-card.selected {
    background: rgba(207, 164, 111, 0.12);
    border-color: #cfa46f;
    box-shadow: 0 4px 16px rgba(207, 164, 111, 0.15);
}
.excuse-subject-card input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #cfa46f;
    cursor: pointer;
}
.sub-code-badge {
    background: rgba(207, 164, 111, 0.15);
    border: 1px solid rgba(207, 164, 111, 0.3);
    color: #cfa46f;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.5px;
}
.quick-date-btn, .quick-sub-btn {
    padding: 4px 12px;
    background: rgba(255, 235, 190, 0.05);
    border: 1px solid rgba(255, 215, 145, 0.15);
    border-radius: 8px;
    color: #b39b82;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}
.quick-date-btn:hover, .quick-sub-btn:hover {
    background: rgba(207, 164, 111, 0.15);
    border-color: #cfa46f;
    color: #f3e7cd;
}
</style>

<div class="ent-section ent-fade-up student-excuse-form" style="max-width:860px; margin: 0 auto;">
    <header class="student-page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 800; color: var(--cs-ink, #f3e7cd); margin-bottom:4px; letter-spacing: -0.02em;">
                <i class="bi bi-file-earmark-medical me-2" style="color: var(--cs-accent, #cfa46f);"></i>Submit Excuse Letter
            </h1>
            <p style="color: var(--cs-muted, #b39b82); font-size: 0.95rem; margin:0;">Choose the date and subjects for an existing absence, then add your explanation and supporting documents.</p>
        </div>
        <a href="{{ route('excuses') }}" class="ent-btn ent-btn-secondary cs-btn-ghost" style="border: 1px solid var(--cs-border-strong, rgba(255,255,255,0.1)); background: var(--cs-surface-2, rgba(0,0,0,0.2)); color: var(--cs-ink, #f3e7cd);">
            <i class="bi bi-arrow-left"></i> Back to Excuses
        </a>
    </header>

    @if($errors->any())
    <div role="alert" class="cs-flash-error" style="background: var(--cs-absent-soft, rgba(239,68,68,0.1)); border: 1px solid var(--cs-absent-border, rgba(239,68,68,0.2)); color: var(--cs-absent, #f87171); border-radius: 12px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:1.25rem;"></i>
        <span style="font-size:0.875rem; font-weight:500;">{{ $errors->first() }}</span>
    </div>
    @endif

    <div class="excuse-form-card" style="background: var(--cs-surface, rgba(30,21,21,0.6)); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid var(--cs-border, rgba(212,175,55,0.15)); border-radius: 18px; overflow: hidden; box-shadow: 0 12px 40px rgba(0,0,0,0.2);">
        
        <form action="{{ route('excuses.store_general') }}" method="POST" enctype="multipart/form-data" id="excuseForm">
            @csrf
            
            <div style="padding: 32px;">
                
                {{-- STEP 1: CHOOSE SUBJECT(S) --}}
                <div style="margin-bottom: 28px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 700; color: var(--cs-ink, #f3e7cd); text-transform: uppercase; letter-spacing: 0.5px; margin: 0; display: flex; align-items: center; gap: 6px;">
                                <i class="bi bi-journal-check" style="color: var(--cs-accent, #cfa46f);"></i> Choose Subjects <span style="color: #dc2626;">*</span>
                            </label>
                            <span style="font-size: 0.78rem; color: var(--cs-muted, #b39b82);">You can select one or multiple subjects</span>
                        </div>
                        <div class="student-excuse-selection-actions">
                            <span id="subjectCountBadge" style="background: var(--cs-accent-soft, rgba(207,164,111,0.15)); border: 1px solid var(--cs-accent-border, rgba(207,164,111,0.3)); color: var(--cs-accent, #cfa46f); padding: 3px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 700;">
                                0 of {{ count($subjects) }} Selected
                            </span>
                            <button type="button" class="quick-sub-btn" onclick="selectAllSubjects(true)">Select All</button>
                            <button type="button" class="quick-sub-btn" onclick="selectAllSubjects(false)">Clear</button>
                        </div>
                    </div>

                    @if($subjects->isEmpty())
                        <div style="padding: 24px; text-align: center; background: var(--cs-surface-2, rgba(0,0,0,0.2)); border-radius: 12px; border: 1px dashed var(--cs-border-strong, rgba(255,215,145,0.15));">
                            <i class="bi bi-journal-x" style="font-size: 2rem; color: var(--cs-muted, #b39b82); margin-bottom: 8px; display: block;"></i>
                            <p style="color: var(--cs-ink, #f3e7cd); font-size: 0.9rem; margin: 0;">No enrolled subjects found for this semester.</p>
                        </div>
                    @else
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px;" id="subjectsGrid">
                            @foreach($subjects as $subject)
                                @include('partials.student-excuse-subject', ['subject' => $subject, 'isPrimary' => false, 'hasOtherAbsence' => false])
                            @endforeach
                        </div>
                    @endif

                    <div id="subjectError" role="alert" style="display: none; color: #dc2626; font-size: 0.78rem; margin-top: 8px; font-weight: 600;">
                        <i class="bi bi-exclamation-circle me-1"></i> Please select at least one subject to submit your excuse letter.
                    </div>
                </div>

                {{-- STEP 2: DATE & REASON --}}
                <div class="student-excuse-fields">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                            <label for="absenceDateInput" style="font-size: 0.75rem; font-weight: 700; color: var(--cs-muted, #b39b82); text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">
                                Absence Date <span style="color: #dc2626;">*</span>
                            </label>
                            <div style="display: flex; gap: 4px;">
                                <button type="button" class="quick-date-btn" onclick="setDatePreset(0)">Today</button>
                                <button type="button" class="quick-date-btn" onclick="setDatePreset(-1)">Yesterday</button>
                            </div>
                        </div>
                        <input type="date" name="date" id="absenceDateInput" max="{{ today()->toDateString() }}" value="{{ old('date', date('Y-m-d')) }}" class="excuse-input" style="width: 100%; padding: 12px 16px; background: var(--cs-surface-2, rgba(0,0,0,0.3)); border: 1.5px solid var(--cs-border-strong, rgba(255,215,145,0.12)); border-radius: 10px; color: var(--cs-ink, #f3e7cd); font-size: 0.95rem; outline: none;" required>
                        @error('date')
                            <div style="color: #dc2626; font-size: 0.75rem; margin-top: 6px; font-weight: 600;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="excuseReason" style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--cs-muted, #b39b82); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                            Reason for Absence <span style="color: #dc2626;">*</span>
                        </label>
                        <select name="reason" id="excuseReason" class="excuse-input" style="width: 100%; padding: 12px 16px; background: var(--cs-surface-2, rgba(0,0,0,0.3)); border: 1.5px solid var(--cs-border-strong, rgba(255,215,145,0.12)); border-radius: 10px; color: var(--cs-ink, #f3e7cd); font-size: 0.95rem; outline: none; transition: all 0.2s;" required>
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
                            <div style="color: #dc2626; font-size: 0.75rem; margin-top: 6px; font-weight: 600;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- STEP 3: DETAILED DESCRIPTION --}}
                <div style="margin-bottom: 24px;">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom: 8px;">
                        <label for="descInput" style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--cs-muted, #b39b82); text-transform: uppercase; letter-spacing: 0.5px; margin:0;">
                            Detailed Explanation <span style="color: #dc2626;">*</span>
                        </label>
                        <span id="charCount" style="font-size:0.75rem; color: var(--cs-muted, #888);">0 / 500</span>
                    </div>
                    <textarea name="description" id="descInput" rows="4" maxlength="500" class="excuse-input" style="width: 100%; padding: 12px 16px; background: var(--cs-surface-2, rgba(0,0,0,0.3)); border: 1.5px solid var(--cs-border-strong, rgba(255,215,145,0.12)); border-radius: 10px; color: var(--cs-ink, #f3e7cd); font-size: 0.95rem; resize: vertical; outline: none; transition: all 0.2s;" placeholder="Please provide a clear and respectful explanation for your absence in the selected subject(s)..." required>{{ old('description') }}</textarea>
                    @error('description')
                        <div style="color: #dc2626; font-size: 0.75rem; margin-top: 6px; font-weight: 600;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- STEP 4: SUPPORTING DOCUMENTS --}}
                <div style="margin-bottom: 16px;">
                    <label for="attachments" style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--cs-muted, #b39b82); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                        Supporting Documents / Attachments (Optional)
                    </label>
                    
                    <div style="position:relative; width:100%;">
                        <input type="file" name="attachments[]" id="attachments" multiple 
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" onchange="handleFileSelect(this)"
                               class="visually-hidden">
                        
                        <label for="attachments" id="dropZone" class="file-upload-zone" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:28px; border:1.5px dashed var(--cs-border-strong, rgba(207,164,111,0.35)); border-radius: 14px; background: var(--cs-surface-2, rgba(0,0,0,0.2)); cursor:pointer; transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
                            <i class="bi bi-cloud-arrow-up" style="font-size:2.2rem; color: var(--cs-accent, #cfa46f); margin-bottom:8px;"></i>
                            <span style="font-size: 0.95rem; font-weight: 600; color: var(--cs-ink, #f3e7cd);">Click or drag &amp; drop files here</span>
                            <span style="font-size: 0.8rem; color: var(--cs-muted, #b39b82); margin-top: 4px;">Medical certificates, letters, or official receipts</span>
                        </label>
                    </div>
                    
                    <div style="font-size: 0.75rem; color: var(--cs-muted, #b39b82); margin-top: 8px;">
                        Accepted formats: JPG, PNG, PDF, DOC, DOCX (Max 5MB each)
                    </div>
                    
                    <div id="fileList" style="margin-top: 14px; display: flex; flex-direction: column; gap: 8px;"></div>
                    
                    @error('attachments.*')
                        <div style="color: #dc2626; font-size: 0.75rem; margin-top: 6px; font-weight: 600;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="excuse-form-foot" style="padding: 20px 32px; border-top: 1px solid var(--cs-border, rgba(212,175,55,0.1)); background: var(--cs-surface-2, rgba(0,0,0,0.25)); display: flex; justify-content: flex-end; gap: 16px;">
                <a href="{{ route('excuses') }}" class="ent-btn ent-btn-secondary cs-btn-ghost" style="border: 1px solid var(--cs-border-strong, rgba(255,255,255,0.1)); background: transparent; color: var(--cs-ink, #f3e7cd);">Cancel</a>
                <button type="submit" id="submitBtn" class="ent-btn ent-btn-primary cs-btn-primary" style="background: linear-gradient(135deg, var(--gold), #b88a44); color: #16100c; font-weight: 700; border: none; padding: 10px 24px;">
                    <i class="bi bi-send-fill" style="margin-right: 6px;"></i> <span>Submit Excuse Letter</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script @cspNonce>
function selectAllSubjects(select) {
    const checkboxes = document.querySelectorAll('.subject-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = select;
    });
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
    if (badge) {
        badge.textContent = `${selectedCount} of ${cards.length} Selected`;
    }

    const err = document.getElementById('subjectError');
    if (err && selectedCount > 0) {
        err.style.display = 'none';
    }
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

// Character Counter
const descInput = document.getElementById('descInput');
const charCount = document.getElementById('charCount');
if(descInput && charCount) {
    const updateCount = () => {
        const len = descInput.value.length;
        charCount.textContent = `${len} / 500`;
        charCount.style.color = len >= 490 ? '#f87171' : '#888';
    };
    descInput.addEventListener('input', updateCount);
    updateCount();
}

// Drag and Drop styling
const dropZone = document.getElementById('dropZone');
if (dropZone) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.borderColor = 'var(--gold)';
            dropZone.style.background = 'rgba(212,175,55,0.08)';
            dropZone.style.transform = 'scale(1.01)';
        }, false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.borderColor = 'rgba(207,164,111,0.35)';
            dropZone.style.background = 'rgba(0,0,0,0.2)';
            dropZone.style.transform = 'scale(1)';
        }, false);
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
    if (['doc', 'docx'].includes(ext)) return 'bi-file-word text-primary';
    return 'bi-file-earmark-text text-secondary';
}

function handleFileSelect(input) {
    const fileList = document.getElementById('fileList');
    fileList.innerHTML = '';
    
    if (input.files.length > 0) {
        Array.from(input.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.style.cssText = 'display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:rgba(0,0,0,0.3); border:1px solid rgba(212,175,55,0.2); border-radius:10px; font-size:0.875rem; color:#f3e7cd;';
            const iconClass = getFileIcon(file.name);
            fileItem.innerHTML = `
                <div style="display:flex; align-items:center; gap:12px;">
                    <i class="bi ${iconClass}" style="font-size:1.2rem;"></i>
                    <div style="display:flex; flex-direction:column;">
                        <span style="font-weight:600; line-height:1.2;">${file.name}</span>
                        <span style="color:#b39b82; font-size:0.75rem; line-height:1.2;">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                    </div>
                </div>
                <button type="button" onclick="removeFile(${index})" style="background:none; border:none; color:#f87171; cursor:pointer; padding:6px; display:flex; align-items:center; justify-content:center; border-radius:6px; transition:all 0.2s;" onmouseover="this.style.background='rgba(239,68,68,0.15)'" onmouseout="this.style.background='none'" title="Remove File">
                    <i class="bi bi-trash3"></i>
                </button>
            `;
            fileList.appendChild(fileItem);
        });
    }
}

function removeFile(index) {
    const input = document.getElementById('attachments');
    const dt = new DataTransfer();
    
    Array.from(input.files).forEach((file, i) => {
        if (i !== index) {
            dt.items.add(file);
        }
    });
    
    input.files = dt.files;
    handleFileSelect(input);
}

// Form Submission validation
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
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="width: 1rem; height: 1rem; border-width: 0.15em;"></span> <span>Submitting...</span>';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    updateSubjectSelectionState();
});
</script>
@endpush
@endsection
