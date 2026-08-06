@extends('layouts.portal')
@section('page-title', 'Submit Excuse')

@section('content')
<div class="ent-section ent-fade-up" style="max-width:800px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 800; color: #f3e7cd; margin-bottom:4px; letter-spacing: -0.02em;">Submit Excuse</h1>
            <p style="color: #b39b82; font-size: 0.95rem; margin:0;">Provide details for your absence excuse</p>
        </div>
        <a href="{{ route('excuses') }}" class="ent-btn ent-btn-secondary" style="border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">
            <i class="bi bi-arrow-left"></i> Back to Excuses
        </a>
    </div>

    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.2);">
        <div style="padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(0,0,0,0.2);">
            <div>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--gold); margin-bottom: 4px;">{{ $attendance->subject->name ?? $attendance->subject_code }}</h3>
                <div style="font-size:0.875rem; color: #b39b82; font-weight: 600;">
                    <i class="bi bi-calendar-event me-1"></i> {{ \Carbon\Carbon::parse($attendance->date)->format('F j, Y (l)') }}
                    <span style="margin:0 8px; opacity: 0.3;">|</span>
                    <i class="bi bi-circle-fill" style="color: #f87171; font-size:0.5rem; vertical-align:middle; margin-right:4px;"></i> <span style="color: #fca5a5;">Absent</span>
                </div>
            </div>
        </div>

        <form action="{{ route('excuses.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
            
            <div style="padding: 32px;">
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #b39b82; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Reason for Absence <span style="color: #f87171;">*</span></label>
                    <select name="reason" style="width: 100%; padding: 12px 16px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #f3e7cd; font-size: 0.95rem; outline: none; transition: all 0.2s;" onfocus="this.style.boxShadow='0 0 0 3px rgba(207,164,111,0.2)'; this.style.borderColor='var(--gold)';" onblur="this.style.boxShadow='none'; this.style.borderColor='rgba(255,255,255,0.1)';" required>
                        <option value="" style="background: #1a1a2e;">Select a reason</option>
                        <option value="Medical/Health Issues" style="background: #1a1a2e;">Medical/Health Issues</option>
                        <option value="Family Emergency" style="background: #1a1a2e;">Family Emergency</option>
                        <option value="Personal Emergency" style="background: #1a1a2e;">Personal Emergency</option>
                        <option value="Transportation Issues" style="background: #1a1a2e;">Transportation Issues</option>
                        <option value="Weather Conditions" style="background: #1a1a2e;">Weather Conditions</option>
                        <option value="Academic Activity" style="background: #1a1a2e;">Academic Activity</option>
                        <option value="Other" style="background: #1a1a2e;">Other</option>
                    </select>
                    @error('reason')
                        <div style="color: #f87171; font-size: 0.75rem; margin-top: 6px; font-weight: 600;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #b39b82; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Detailed Description <span style="color: #f87171;">*</span></label>
                    <textarea name="description" rows="4" style="width: 100%; padding: 12px 16px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #f3e7cd; font-size: 0.95rem; resize: vertical; outline: none; transition: all 0.2s;" onfocus="this.style.boxShadow='0 0 0 3px rgba(207,164,111,0.2)'; this.style.borderColor='var(--gold)';" onblur="this.style.boxShadow='none'; this.style.borderColor='rgba(255,255,255,0.1)';" placeholder="Please provide a detailed explanation of your absence..." required>{{ old('description') }}</textarea>
                    @error('description')
                        <div style="color: #f87171; font-size: 0.75rem; margin-top: 6px; font-weight: 600;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #b39b82; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Supporting Documents (Optional)</label>
                    
                    <div style="position:relative; width:100%;">
                        <input type="file" name="attachments[]" id="attachments" multiple 
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" onchange="handleFileSelect(this)"
                               style="position:absolute; left:-9999px;">
                        
                        <label for="attachments" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:32px; border:1px dashed rgba(255,255,255,0.2); border-radius: 12px; background: rgba(0,0,0,0.2); cursor:pointer; transition:all 0.2s;" onmouseover="this.style.borderColor='var(--gold)'; this.style.background='rgba(0,0,0,0.4)';" onmouseout="this.style.borderColor='rgba(255,255,255,0.2)'; this.style.background='rgba(0,0,0,0.2)';">
                            <i class="bi bi-cloud-arrow-up" style="font-size:2.5rem; color: rgba(255,255,255,0.2); margin-bottom:12px;"></i>
                            <span style="font-size: 0.95rem; font-weight: 600; color: #f3e7cd;">Click to upload documents</span>
                            <span style="font-size: 0.8rem; color: #b39b82; margin-top: 4px;">Medical certificates, letters, etc.</span>
                        </label>
                    </div>
                    
                    <div style="font-size: 0.75rem; color: #b39b82; margin-top: 8px;">
                        Accepted formats: JPG, PNG, PDF, DOC, DOCX (Max 5MB each)
                    </div>
                    
                    <div id="fileList" style="margin-top: 16px; display: flex; flex-direction: column; gap: 8px;"></div>
                    
                    @error('attachments.*')
                        <div style="color: #f87171; font-size: 0.75rem; margin-top: 6px; font-weight: 600;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div style="padding: 20px 32px; border-top: 1px solid rgba(255,255,255,0.06); background: rgba(0,0,0,0.2); display: flex; justify-content: flex-end; gap: 16px;">
                <a href="{{ route('excuses') }}" class="ent-btn ent-btn-secondary" style="border: 1px solid rgba(255,255,255,0.1); background: transparent;">Cancel</a>
                <button type="submit" class="ent-btn ent-btn-primary" style="background: linear-gradient(135deg, var(--gold), #b88a44); color: #1a1a2e; font-weight: 700; border: none;">
                    <i class="bi bi-send-fill" style="margin-right: 6px;"></i> Submit Excuse
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