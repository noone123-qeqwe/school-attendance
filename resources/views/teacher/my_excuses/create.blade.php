@extends('layouts.app')
@section('page-title', 'Submit Excuse')

@section('content')
<div class="ent-section ent-fade-up" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <a href="{{ route('teacher.excuses') }}" style="color: #b39b82; text-decoration: none; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 4px; transition: color 0.2s;" onmouseover="this.style.color='#f3e7cd'" onmouseout="this.style.color='#b39b82'">
            <i class="bi bi-arrow-left"></i> Back to My Excuses
        </a>
    </div>

    <div style="background: rgba(30,21,21,0.6); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(212,175,55,0.15); border-radius: 16px; overflow: hidden; box-shadow: 0 12px 40px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.05);">
        <div style="padding: 24px; border-bottom: 1px solid rgba(212,175,55,0.1); background: rgba(0,0,0,0.25);">
            <h1 style="font-size: 1.5rem; font-weight: 800; color: #f3e7cd; margin-bottom: 4px;">Submit New Excuse</h1>
            <p style="color: #b39b82; font-size: 0.9rem; margin: 0;">Provide details about your absence for a specific class.</p>
        </div>

        <div style="padding: 24px;">
            <form action="{{ route('teacher.excuses.store') }}" method="POST" enctype="multipart/form-data" id="excuseForm">
                @csrf
                <div class="mb-3">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #b39b82; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Select Subject <span style="color: #f87171;">*</span></label>
                    <select name="subject_code" class="form-select @error('subject_code') is-invalid @enderror" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd; border-radius: 10px; padding: 12px 16px; font-size: 0.95rem; appearance: auto; outline: none; transition: all 0.2s;" onfocus="this.style.boxShadow='0 0 0 3px rgba(207,164,111,0.2)'; this.style.borderColor='var(--gold)';" onblur="this.style.boxShadow='none'; this.style.borderColor='rgba(255,255,255,0.1)';" required>
                        <option value="" style="background: #1a1a2e;">-- Choose Subject --</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->code }}" {{ old('subject_code') == $subject->code ? 'selected' : '' }}>
                                {{ $subject->name }} ({{ $subject->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('subject_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #b39b82; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Date of Absence <span style="color: #f87171;">*</span></label>
                    <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd; border-radius: 10px; padding: 12px 16px; font-size: 0.95rem; outline: none; transition: all 0.2s; color-scheme: dark;" onfocus="this.style.boxShadow='0 0 0 3px rgba(207,164,111,0.2)'; this.style.borderColor='var(--gold)';" onblur="this.style.boxShadow='none'; this.style.borderColor='rgba(255,255,255,0.1)';" required max="{{ date('Y-m-d') }}">
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #b39b82; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Reason <span style="color: #f87171;">*</span></label>
                    <select name="reason" class="form-select @error('reason') is-invalid @enderror" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd; border-radius: 10px; padding: 12px 16px; font-size: 0.95rem; appearance: auto; outline: none; transition: all 0.2s;" onfocus="this.style.boxShadow='0 0 0 3px rgba(207,164,111,0.2)'; this.style.borderColor='var(--gold)';" onblur="this.style.boxShadow='none'; this.style.borderColor='rgba(255,255,255,0.1)';" required>
                        <option value="" style="background: #1a1a2e;">-- Select Reason --</option>
                        <option value="Medical / Illness" {{ old('reason') == 'Medical / Illness' ? 'selected' : '' }}>Medical / Illness</option>
                        <option value="Emergency" {{ old('reason') == 'Emergency' ? 'selected' : '' }}>Emergency</option>
                        <option value="Official Business" {{ old('reason') == 'Official Business' ? 'selected' : '' }}>Official Business</option>
                        <option value="Other" {{ old('reason') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom: 8px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #b39b82; text-transform: uppercase; letter-spacing: 0.5px; margin:0;">Detailed Description <span style="color: #f87171;">*</span></label>
                        <span id="charCount" style="font-size:0.7rem; color: #888;">0 / 500</span>
                    </div>
                    <textarea name="description" id="descInput" class="form-control @error('description') is-invalid @enderror" rows="4" maxlength="500" placeholder="Provide more context about your absence..." style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd; border-radius: 10px; padding: 12px 16px; font-size: 0.95rem; resize: vertical; outline: none; transition: all 0.2s;" onfocus="this.style.boxShadow='0 0 0 3px rgba(207,164,111,0.2)'; this.style.borderColor='var(--gold)';" onblur="this.style.boxShadow='none'; this.style.borderColor='rgba(255,255,255,0.1)';" required>{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #b39b82; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Attachments (Optional)</label>
                    <div style="position:relative; width:100%;">
                        <input type="file" name="attachments[]" id="attachments" multiple 
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" onchange="handleFileSelect(this)"
                               style="position:absolute; left:-9999px;">
                        
                        <label for="attachments" id="dropZone" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:32px; border:1.5px dashed rgba(212,175,55,0.3); border-radius: 12px; background: rgba(0,0,0,0.2); cursor:pointer; transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
                            <i class="bi bi-cloud-arrow-up" style="font-size:2.5rem; color: rgba(255,255,255,0.2); margin-bottom:12px;"></i>
                            <span style="font-size: 0.95rem; font-weight: 600; color: #f3e7cd;">Click to upload documents</span>
                            <span style="font-size: 0.8rem; color: #b39b82; margin-top: 4px;">You can upload multiple files (JPG, PNG, PDF, DOCX) up to 5MB each.</span>
                        </label>
                    </div>
                    <div id="fileList" style="margin-top: 16px; display: flex; flex-direction: column; gap: 8px;"></div>
                </div>

                <div style="display: flex; gap: 16px; justify-content: flex-end; padding-top: 16px; border-top: 1px solid rgba(212,175,55,0.1);">
                    <a href="{{ route('teacher.excuses') }}" class="ent-btn ent-btn-secondary" style="border: 1px solid rgba(255,255,255,0.1); background: transparent;">Cancel</a>
                    <button type="submit" id="submitBtn" class="ent-btn ent-btn-primary" style="background: linear-gradient(135deg, var(--gold), #b88a44); color: #1a1a2e; border: none; font-weight: 700; transition: all 0.2s;">
                        <i class="bi bi-send-fill" style="margin-right: 6px;"></i> <span>Submit Excuse</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
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
            dropZone.style.transform = 'scale(1.02)';
        }, false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.borderColor = 'rgba(212,175,55,0.3)';
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
            fileItem.style.cssText = 'display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:rgba(0,0,0,0.3); border:1px solid rgba(212,175,55,0.2); border-radius:10px; font-size:0.875rem; color:#f3e7cd; animation:fadeInUp 0.3s ease;';
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

// Form Submission Loading State
const form = document.getElementById('excuseForm');
if (form) {
    form.addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.8';
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="width: 1rem; height: 1rem; border-width: 0.15em;"></span> <span>Submitting...</span>';
        }
    });
}
</script>
@endpush
@endsection
