@extends('layouts.app')
@section('page-title', 'Submit Excuse â€” ' . $child->name)

@section('content')
<div class="p-4">
    {{-- Back Link --}}
    <a href="{{ route('parent.child', $child) }}" class="student-back-link">
        <i class="bi bi-arrow-left"></i> Back to {{ $child->name }}'s Records
    </a>

    <h3 style="color: #f3e7cd; font-weight: 800; margin-bottom: 24px;">
        <i class="bi bi-file-earmark-text" style="color: #cfa46f;"></i> Submit Excuse Letter
    </h3>

    {{-- Error/Success Messages --}}
    @if(session('error'))
    <div style="background: rgba(239,83,80,0.1); border: 1px solid rgba(239,83,80,0.2); color: #ef5350; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.88rem;">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div style="background: rgba(239,83,80,0.1); border: 1px solid rgba(239,83,80,0.2); color: #ef5350; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.88rem;">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul style="margin: 0; padding-left: 18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="row g-4">
        {{-- Attendance Details --}}
        <div class="col-lg-4">
            <div class="adm-card" style="height: 100%;">
                <div class="adm-card-head">
                    <div class="adm-card-title">
                        <div class="adm-card-icon"><i class="bi bi-info-circle"></i></div>
                        Attendance Details
                    </div>
                </div>
                <div class="adm-card-body">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 0.72rem; color: #8f826f; text-transform: uppercase; font-weight: 600;">Student</label>
                        <p style="color: #f3e7cd; font-weight: 600; margin: 4px 0 0;">{{ $child->name }}</p>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 0.72rem; color: #8f826f; text-transform: uppercase; font-weight: 600;">Subject</label>
                        <p style="color: #f3e7cd; font-weight: 600; margin: 4px 0 0;">{{ $attendance->subject->name ?? $attendance->subject_code }}</p>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 0.72rem; color: #8f826f; text-transform: uppercase; font-weight: 600;">Date</label>
                        <p style="color: #f3e7cd; font-weight: 600; margin: 4px 0 0;">{{ \Carbon\Carbon::parse($attendance->date)->format('l, M d, Y') }}</p>
                    </div>
                    <div>
                        <label style="font-size: 0.72rem; color: #8f826f; text-transform: uppercase; font-weight: 600;">Status</label>
                        <p style="margin: 4px 0 0;"><span class="badge-absent">{{ $attendance->status }}</span></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Excuse Form --}}
        <div class="col-lg-8">
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">
                        <div class="adm-card-icon" style="background: rgba(66,165,245,0.15); color: #42a5f5;">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        Excuse Details
                    </div>
                </div>
                <div class="adm-card-body">
                    <form method="POST" action="{{ route('parent.excuse.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 0.82rem; color: #e7dcc8; font-weight: 600; display: block; margin-bottom: 8px;">
                                Reason for Absence <span style="color: #ef5350;">*</span>
                            </label>
                            <select name="reason_type" class="tch-input" style="width: 100%; margin-bottom: 12px;" onchange="toggleCustomReason(this)">
                                <option value="">Select a reason...</option>
                                <option value="illness">Illness / Medical Condition</option>
                                <option value="medical_appointment">Medical / Dental Appointment</option>
                                <option value="family_emergency">Family Emergency</option>
                                <option value="bereavement">Bereavement / Death in Family</option>
                                <option value="religious">Religious Observance</option>
                                <option value="official_event">Official School Event / Activity</option>
                                <option value="transportation">Transportation Issues</option>
                                <option value="natural_disaster">Natural Disaster / Calamity</option>
                                <option value="other">Other (please specify)</option>
                            </select>
                            <textarea name="reason" class="tch-input" style="width: 100%; min-height: 120px; resize: vertical;"
                                      placeholder="Provide details about the reason for absence..." required>{{ old('reason') }}</textarea>
                        </div>

                        <div style="margin-bottom: 24px;">
                            <label style="font-size: 0.82rem; color: #e7dcc8; font-weight: 600; display: block; margin-bottom: 8px;">
                                Supporting Document <span style="color: #8f826f;">(optional)</span>
                            </label>
                            <div style="background: rgba(255,235,190,0.04); border: 2px dashed rgba(255,215,145,0.15); border-radius: 12px; padding: 24px; text-align: center; cursor: pointer; transition: all 0.2s;" 
                                 onclick="document.getElementById('fileInput').click()" id="uploadArea">
                                <i class="bi bi-cloud-arrow-up" style="font-size: 2rem; color: #cfa46f;"></i>
                                <p style="color: #b39b82; font-size: 0.85rem; margin: 8px 0 0;" id="uploadText">
                                    Click to upload â€” PDF, JPG, or PNG (max 5MB)
                                </p>
                                <input type="file" name="document" id="fileInput" style="display: none;" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this)">
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px; justify-content: flex-end;">
                            <a href="{{ route('parent.child', $child) }}" class="adm-btn adm-btn-ghost">Cancel</a>
                            <button type="submit" class="adm-btn adm-btn-primary">
                                <i class="bi bi-send me-1"></i> Submit Excuse
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCustomReason(select) {
    const textarea = document.querySelector('textarea[name="reason"]');
    if (select.value && select.value !== 'other') {
        const selectedText = select.options[select.selectedIndex].text;
        if (!textarea.value || textarea.value === textarea.getAttribute('data-auto')) {
            textarea.value = selectedText + ': ';
            textarea.setAttribute('data-auto', selectedText + ': ');
        }
    }
}

function updateFileName(input) {
    const text = document.getElementById('uploadText');
    const area = document.getElementById('uploadArea');
    if (input.files.length > 0) {
        text.innerHTML = '<i class="bi bi-file-earmark-check me-1" style="color: #66bb6a;"></i> ' + input.files[0].name;
        area.style.borderColor = 'rgba(102,187,106,0.4)';
    }
}
</script>
@endsection
