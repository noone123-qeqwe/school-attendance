@extends('layouts.app')
@section('page-title', 'Edit Subject')

@section('content')
<a href="{{ route('admin.subjects') }}" class="adm-btn adm-btn-ghost" style="margin-bottom:20px;text-decoration:none;">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="adm-card" style="max-width:680px;">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-pencil-fill"></i></div>
            Edit â€” {{ $subject->code }}
        </div>
    </div>
    <div style="padding:24px;">

        @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:10px;padding:10px 14px;font-size:.85rem;margin-bottom:16px;">
            <ul style="margin:0;padding-left:16px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('admin.subjects.update', $subject->id) }}" method="POST">
            @csrf @method('PUT')
            @php
                // Force fresh load of schedules from database
                $subject->load('schedules');
                $schedules   = $subject->schedules;
                $firstSched  = $schedules->first();
                
                // Build days string from schedule day names e.g. "Monday,Thursday" â†’ "MTH"
                $dayCodeMap  = ['Monday'=>'M','Tuesday'=>'T','Wednesday'=>'W','Thursday'=>'TH','Friday'=>'F','Saturday'=>'S','Sunday'=>'U'];
                $existingDays = $schedules->map(fn($s) => $dayCodeMap[$s->day] ?? '')->filter()->implode('');
                $existingStart = $firstSched ? \Carbon\Carbon::parse($firstSched->start_time)->format('H:i') : '';
                $existingEnd   = $firstSched ? \Carbon\Carbon::parse($firstSched->end_time)->format('H:i') : '';
            @endphp
            <div class="row g-3">

                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Subject Code</label>
                    <input type="text" name="code" class="adm-input"
                        value="{{ old('code', $subject->code) }}"
                        required style="width:100%;">
                </div>

                <div class="col-md-8">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Subject Name</label>
                    <input type="text" name="name" class="adm-input"
                        value="{{ old('name', $subject->name) }}"
                        required style="width:100%;">
                </div>

                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Year Level</label>
                    <select name="year_level" class="adm-input" required style="width:100%;">
                        @foreach([1,2,3,4] as $y)
                            <option value="{{ $y }}" {{ old('year_level', $subject->year_level) == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Semester</label>
                    <select name="semester" class="adm-input" required style="width:100%;">
                        <option value="1" {{ old('semester', $subject->semester) == '1' ? 'selected' : '' }}>1st Semester</option>
                        <option value="2" {{ old('semester', $subject->semester) == '2' ? 'selected' : '' }}>2nd Semester</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Course</label>
                    <select name="course" class="adm-input" required style="width:100%;">
                        <option value="">Select Course</option>
                        @foreach(['BSCS','BSIT','BSIS'] as $c)
                        <option value="{{ $c }}" {{ old('course', $subject->course) == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Units</label>
                    <input type="number" name="units" class="adm-input"
                        value="{{ old('units', $subject->units) }}"
                        min="1" max="6" style="width:100%;">
                </div>

                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Days (MWF, TTH, S)</label>
                    <input type="text" name="days" class="adm-input"
                        value="{{ old('days', $existingDays) }}"
                        style="width:100%;background:white;" placeholder="e.g. MWF, TTHS"
                        maxlength="15">
                </div>

                <div class="col-md-2">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Start Time</label>
                    <input type="time" name="start_time" class="adm-input"
                        value="{{ old('start_time', $existingStart) }}"
                        style="width:100%;">
                </div>

                <div class="col-md-2">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">End Time</label>
                    <input type="time" name="end_time" class="adm-input"
                        value="{{ old('end_time', $existingEnd) }}"
                        style="width:100%;">
                </div>

                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Instructor</label>
                    <select name="instructor_id" class="adm-input" style="width:100%;">
                        <option value="">Select Instructor</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ old('instructor_id', $subject->instructor_id) == $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Section</label>
                    <input type="text" name="section" class="adm-input"
                        value="{{ old('section', $subject->section) }}"
                        style="width:100%;" placeholder="e.g. A, B, 1001, CS-1A">
                </div>

                <div class="col-12" style="margin-top:8px;">
                    <button type="submit" class="adm-btn adm-btn-primary">
                        <i class="bi bi-check2 me-2"></i>Save Changes
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple fix - ensure the form submits properly
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            console.log('Form submitting...');
        });
    }
});
</script>
@endsection
