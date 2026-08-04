@extends('layouts.portal')
@section('page-title', 'Submit Excuse')

@section('content')
<div class="saas-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Submit Excuse</h1>
            <p class="saas-text-muted" style="margin:0;">Provide details for your absence excuse</p>
        </div>
        <a href="{{ route('excuses') }}" class="saas-btn saas-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Excuses
        </a>
    </div>

    <div class="saas-card" style="max-width:700px;">
        <div class="saas-card-header" style="background:rgba(255,255,255,0.02); border-bottom:1px solid var(--saas-border);">
            <div>
                <h3 class="saas-heading" style="margin-bottom:4px;">{{ $attendance->subject->name ?? $attendance->subject_code }}</h3>
                <div style="font-size:0.875rem; color:var(--saas-text-muted);">
                    <i class="bi bi-calendar-event me-1"></i> {{ \Carbon\Carbon::parse($attendance->date)->format('F j, Y (l)') }}
                    <span style="margin:0 8px;">|</span>
                    <i class="bi bi-circle-fill" style="color:var(--saas-danger); font-size:0.5rem; vertical-align:middle; margin-right:4px;"></i> <span style="color:var(--saas-danger); font-weight:600;">Absent</span>
                </div>
            </div>
        </div>

        <form action="{{ route('excuses.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
            
            <div class="saas-card-body" style="padding:24px;">
                <div class="saas-form-group" style="margin-bottom:20px;">
                    <label class="saas-label">Reason for Absence <span style="color:var(--saas-danger);">*</span></label>
                    <select name="reason" class="saas-input saas-select" required>
                        <option value="">Select a reason</option>
                        <option value="Medical/Health Issues">Medical/Health Issues</option>
                        <option value="Family Emergency">Family Emergency</option>
                        <option value="Personal Emergency">Personal Emergency</option>
                        <option value="Transportation Issues">Transportation Issues</option>
                        <option value="Weather Conditions">Weather Conditions</option>
                        <option value="Academic Activity">Academic Activity</option>
                        <option value="Other">Other</option>
                    </select>
                    @error('reason')
                        <div style="color:var(--saas-danger); font-size:0.75rem; margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="saas-form-group" style="margin-bottom:20px;">
                    <label class="saas-label">Detailed Description <span style="color:var(--saas-danger);">*</span></label>
                    <textarea name="description" class="saas-input" rows="4" style="resize:vertical;"
                              placeholder="Please provide a detailed explanation of your absence..." required>{{ old('description') }}</textarea>
                    @error('description')
                        <div style="color:var(--saas-danger); font-size:0.75rem; margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="saas-form-group" style="margin-bottom:8px;">
                    <label class="saas-label">Supporting Documents (Optional)</label>
                    
                    <div style="position:relative; width:100%;">
                        <input type="file" name="attachments[]" id="attachments" multiple 
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" onchange="handleFileSelect(this)"
                               style="position:absolute; left:-9999px;">
                        
                        <label for="attachments" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:24px; border:2px dashed var(--saas-border); border-radius:var(--saas-radius-md); background:rgba(255,255,255,0.02); cursor:pointer; transition:all 0.2s;" onmouseover="this.style.borderColor='var(--saas-primary)'; this.style.background='rgba(255,255,255,0.04)';" onmouseout="this.style.borderColor='var(--saas-border)'; this.style.background='rgba(255,255,255,0.02)';">
                            <i class="bi bi-cloud-arrow-up" style="font-size:2rem; color:var(--saas-text-muted); margin-bottom:8px;"></i>
                            <span style="font-size:0.875rem; font-weight:600; color:var(--saas-text);">Click to upload documents</span>
                            <span style="font-size:0.75rem; color:var(--saas-text-muted); margin-top:4px;">Medical certificates, letters, etc.</span>
                        </label>
                    </div>
                    
                    <div style="font-size:0.75rem; color:var(--saas-text-muted); margin-top:8px;">
                        Accepted formats: JPG, PNG, PDF, DOC, DOCX (Max 5MB each)
                    </div>
                    
                    <div id="fileList" style="margin-top:12px; display:flex; flex-direction:column; gap:8px;"></div>
                    
                    @error('attachments.*')
                        <div style="color:var(--saas-danger); font-size:0.75rem; margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="saas-card-body" style="padding:16px 24px; border-top:1px solid var(--saas-border); background:rgba(0,0,0,0.2); display:flex; justify-content:flex-end; gap:12px;">
                <a href="{{ route('excuses') }}" class="saas-btn saas-btn-secondary">Cancel</a>
                <button type="submit" class="saas-btn saas-btn-primary">
                    <i class="bi bi-send"></i> Submit Excuse
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function handleFileSelect(input) {
    const fileList = document.getElementById('fileList');
    fileList.innerHTML = '';
    
    if (input.files.length > 0) {
        Array.from(input.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.style.cssText = 'display:flex; align-items:center; justify-content:space-between; padding:10px 12px; background:rgba(255,255,255,0.04); border:1px solid var(--saas-border); border-radius:var(--saas-radius-sm); font-size:0.875rem; color:var(--saas-text);';
            fileItem.innerHTML = `
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="bi bi-file-earmark-text text-primary"></i>
                    <span style="font-weight:500;">${file.name}</span>
                    <span style="color:var(--saas-text-muted); font-size:0.75rem;">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                </div>
                <button type="button" onclick="removeFile(${index})" style="background:none; border:none; color:var(--saas-danger); cursor:pointer; padding:4px; display:flex; align-items:center; justify-content:center; border-radius:4px;" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='none'">
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
</script>
@endpush
@endsection