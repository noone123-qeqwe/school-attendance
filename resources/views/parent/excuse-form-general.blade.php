@extends('layouts.app')
@section('page-title', 'Submit General Excuse')

@section('content')
<div class="p-4" style="max-width: 800px; margin: 0 auto;">
    <a href="{{ route('parent.excuses') }}" style="color: #b39b82; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.9rem; margin-bottom: 24px; transition: color 0.2s;" onmouseover="this.style.color='#f3e7cd'" onmouseout="this.style.color='#b39b82'">
        <i class="bi bi-arrow-left"></i> Back to Excuse Letters
    </a>

    <h2 style="color: #f3e7cd; font-weight: 800; margin-bottom: 24px;">
        <i class="bi bi-pencil-square" style="color: #cfa46f; margin-right: 8px;"></i>Submit Excuse Letter
    </h2>

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

    <x-card type="section" style="padding: 32px;">
        <form method="POST" action="{{ route('parent.excuses.store_general') }}" enctype="multipart/form-data">
            @csrf
            
            <div style="margin-bottom: 24px;">
                <label class="ent-label">Select Child</label>
                <select id="childSelect" name="child_id" class="ent-input" required onchange="updateAttendanceOptions()">
                    <option value="">Select a child...</option>
                    @foreach($children as $child)
                        <option value="{{ $child->id }}" data-attendances="{{ json_encode($child->attendances->map(function($a) {
                            return [
                                'id' => $a->id,
                                'date' => \Carbon\Carbon::parse($a->date)->format('M d, Y'),
                                'subject' => $a->subject->name ?? $a->subject_code,
                                'status' => $a->status
                            ];
                        })) }}">
                            {{ $child->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 24px;">
                <label class="ent-label">Select Absence / Lateness to Excuse</label>
                <select id="attendanceSelect" name="attendance_id" class="ent-input" required disabled>
                    <option value="">First select a child...</option>
                </select>
            </div>

            <div style="margin-bottom: 24px;">
                <label class="ent-label">Reason Category <span style="color: #ef5350;">*</span></label>
                <select name="reason_type" class="ent-input" onchange="toggleCustomReason(this)">
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
            </div>

            <div style="margin-bottom: 24px;">
                <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom: 8px;">
                    <label class="ent-label" style="margin: 0;">Detailed Explanation <span style="color: #ef5350;">*</span></label>
                    <span id="charCount" style="font-size:0.75rem; color: #8f826f;">0 / 500</span>
                </div>
                <textarea name="reason" id="descInput" class="ent-input" maxlength="500" style="min-height: 120px; resize: vertical;" placeholder="Provide details about the reason for absence..." required>{{ old('reason') }}</textarea>
            </div>

            <div style="margin-bottom: 32px;">
                <label class="ent-label">Supporting Document <span style="color: #8f826f; font-weight: normal;">(optional)</span></label>
                <label for="fileInput" id="dropZone" style="display:block; background: rgba(0,0,0,0.2); border: 2px dashed rgba(255,255,255,0.1); border-radius: 12px; padding: 24px; text-align: center; cursor: pointer; transition: all 0.2s;">
                    <i class="bi bi-cloud-arrow-up" style="font-size: 2rem; color: #cfa46f;"></i>
                    <p style="color: #b39b82; font-size: 0.9rem; margin: 8px 0 0;" id="uploadText">
                        Click or drag to upload &mdash; PDF, JPG, or PNG (max 5MB)
                    </p>
                    <input type="file" name="document" id="fileInput" style="display: none;" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this)">
                </label>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <a href="{{ route('parent.excuses') }}" class="ent-btn ent-btn-ghost">Cancel</a>
                <button type="submit" class="ent-btn ent-btn-primary">
                    <i class="bi bi-send me-1"></i> Submit Excuse
                </button>
            </div>
        </form>
    </x-card>
</div>

<script>
function updateAttendanceOptions() {
    const childSelect = document.getElementById('childSelect');
    const attendanceSelect = document.getElementById('attendanceSelect');
    
    if (childSelect.selectedIndex <= 0) {
        attendanceSelect.innerHTML = '<option value="">First select a child...</option>';
        attendanceSelect.disabled = true;
        return;
    }
    
    const selectedOption = childSelect.options[childSelect.selectedIndex];
    const attendancesDataStr = selectedOption.getAttribute('data-attendances');
    
    if (!attendancesDataStr) return;
    
    const attendances = JSON.parse(attendancesDataStr);
    
    attendanceSelect.disabled = false;
    attendanceSelect.innerHTML = '<option value="">Select the date/subject...</option>';
    
    if (attendances.length === 0) {
        attendanceSelect.innerHTML = '<option value="">No un-excused absences found for this child.</option>';
        attendanceSelect.disabled = true;
        return;
    }
    
    attendances.forEach(function(att) {
        const option = document.createElement('option');
        option.value = att.id;
        option.textContent = `${att.date} — ${att.subject} (${att.status})`;
        attendanceSelect.appendChild(option);
    });
}

// Character Counter
const descInput = document.getElementById('descInput');
const charCount = document.getElementById('charCount');
if(descInput && charCount) {
    const updateCount = () => {
        const len = descInput.value.length;
        charCount.textContent = `${len} / 500`;
        charCount.style.color = len >= 490 ? '#ef5350' : '#8f826f';
    };
    descInput.addEventListener('input', updateCount);
    updateCount();
}

function toggleCustomReason(select) {
    const textarea = document.getElementById('descInput');
    if (select.value && select.value !== 'other') {
        const selectedText = select.options[select.selectedIndex].text;
        if (!textarea.value || textarea.value === textarea.getAttribute('data-auto')) {
            textarea.value = selectedText + ': ';
            textarea.setAttribute('data-auto', selectedText + ': ');
            if (descInput) {
                descInput.dispatchEvent(new Event('input'));
            }
        }
    }
}

// File Upload Drag and Drop
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
            dropZone.style.background = 'rgba(207,164,111,0.05)';
        }, false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.borderColor = 'rgba(255,255,255,0.1)';
            dropZone.style.background = 'rgba(0,0,0,0.2)';
        }, false);
    });
    dropZone.addEventListener('drop', (e) => {
        const input = document.getElementById('fileInput');
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            updateFileName(input);
        }
    }, false);
}

function updateFileName(input) {
    const text = document.getElementById('uploadText');
    const area = document.getElementById('dropZone');
    if (input.files.length > 0) {
        text.innerHTML = '<i class="bi bi-file-earmark-check me-1" style="color: #4ade80;"></i> ' + input.files[0].name;
        area.style.borderColor = 'rgba(74, 222, 128, 0.4)';
    } else {
        text.innerHTML = 'Click or drag to upload &mdash; PDF, JPG, or PNG (max 5MB)';
        area.style.borderColor = 'rgba(255,255,255,0.1)';
    }
}
</script>
@endsection
