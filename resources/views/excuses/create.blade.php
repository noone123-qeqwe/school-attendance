@extends('layouts.app')

@section('content')
<style>
    .form-card {
        background: rgba(255,255,255,0.05);
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.1);
        box-shadow: 0 18px 48px rgba(0,0,0,0.24);
        padding: 32px;
        max-width: 600px;
        margin: 0 auto;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #f8e7d3;
        margin-bottom: 6px;
        display: block;
    }
    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid rgba(255,255,255,0.12);
        border-radius: 10px;
        font-size: 0.875rem;
        font-family: 'Inter', sans-serif;
        background: rgba(255,255,255,0.08);
        color: #f8e7d3;
        outline: none;
        transition: all 0.2s;
    }
    .form-control::placeholder { color: rgba(248,231,211,0.6); }
    .form-control:focus {
        border-color: var(--gold);
        background: rgba(255,255,255,0.12);
        box-shadow: 0 0 0 3px rgba(216,179,92,0.12);
    }
    .form-select {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23f8e7d3' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 12px center;
        background-repeat: no-repeat;
        background-size: 16px 12px;
        padding-right: 40px;
        color: #f8e7d3;
    }
    .file-input {
        position: relative;
        overflow: hidden;
        display: inline-block;
        cursor: pointer;
        width: 100%;
    }
    .file-input input[type=file] {
        position: absolute;
        left: -9999px;
    }
    .file-input-label {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        border: 2px dashed rgba(255,255,255,0.18);
        border-radius: 10px;
        background: rgba(255,255,255,0.06);
        color: rgba(248,231,211,0.9);
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .file-input-label:hover {
        border-color: #d8b35c;
        background: rgba(255,255,255,0.12);
        color: #f8e7d3;
    }
    .btn-primary {
        background: var(--gold);
        color: #2d0708;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-primary:hover {
        background: #c9a552;
        transform: translateY(-1px);
    }
    .btn-secondary {
        background: rgba(255,255,255,0.06);
        color: #f8e7d3;
        border: 1.5px solid rgba(255,255,255,0.12);
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-secondary:hover {
        background: rgba(255,255,255,0.12);
        color: #f8e7d3;
    }
    .attendance-info {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
    }
    .attendance-title {
        font-size: 1rem;
        font-weight: 700;
        color: #f8e7d3;
        margin: 0 0 8px 0;
    }
    .attendance-details {
        font-size: 0.875rem;
        color: rgba(248,231,211,0.78);
        margin: 0;
    }
    .file-list {
        margin-top: 8px;
    }
    .file-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14);
        border-radius: 8px;
        margin-bottom: 4px;
        font-size: 0.75rem;
        color: #f8e7d3;
    }
    .file-remove {
        color: #fda4af;
        cursor: pointer;
        padding: 2px;
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 style="font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0;">Submit Excuse</h1>
                    <p style="color: #64748b; margin: 4px 0 0 0;">Provide details for your absence excuse</p>
                </div>
            </div>

            <div class="form-card">
                <!-- Attendance Information -->
                <div class="attendance-info">
                    <h3 class="attendance-title">{{ $attendance->subject->name ?? $attendance->subject_code }}</h3>
                    <p class="attendance-details">
                        <strong>Date:</strong> {{ \Carbon\Carbon::parse($attendance->date)->format('F j, Y (l)') }}<br>
                        <strong>Status:</strong> <span style="color: #dc2626;">Absent</span>
                    </p>
                </div>

                <form action="{{ route('excuses.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

                    <div class="form-group">
                        <label class="form-label">Reason for Absence <span style="color: #dc2626;">*</span></label>
                        <select name="reason" class="form-control form-select" required>
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
                            <div style="color: #dc2626; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Detailed Description <span style="color: #dc2626;">*</span></label>
                        <textarea name="description" class="form-control" rows="4" 
                                  placeholder="Please provide a detailed explanation of your absence..." required>{{ old('description') }}</textarea>
                        @error('description')
                            <div style="color: #dc2626; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Supporting Documents (Optional)</label>
                        <div class="file-input">
                            <input type="file" name="attachments[]" id="attachments" multiple 
                                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" onchange="handleFileSelect(this)">
                            <label for="attachments" class="file-input-label">
                                <i class="bi bi-cloud-upload"></i>
                                Choose files (Medical certificates, letters, etc.)
                            </label>
                        </div>
                        <div style="font-size: 0.75rem; color: #6b7280; margin-top: 4px;">
                            Accepted formats: JPG, PNG, PDF, DOC, DOCX (Max 5MB each)
                        </div>
                        <div id="fileList" class="file-list"></div>
                        @error('attachments.*')
                            <div style="color: #dc2626; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="display: flex; gap: 12px; margin-top: 32px;">
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-send"></i> Submit Excuse
                        </button>
                        <a href="{{ route('excuses') }}" class="btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function handleFileSelect(input) {
    const fileList = document.getElementById('fileList');
    fileList.innerHTML = '';
    
    if (input.files.length > 0) {
        Array.from(input.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <span><i class="bi bi-paperclip me-1"></i>${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                <i class="bi bi-x file-remove" onclick="removeFile(${index})"></i>
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
@endsection