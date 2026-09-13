@extends('layouts.app')
@section('page-title', 'Add Subject')

@section('content')
<a href="{{ route('admin.subjects') }}" class="adm-btn adm-btn-ghost" style="margin-bottom:20px;text-decoration:none;">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="adm-card" style="max-width:680px;">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-plus-circle-fill"></i></div>
            Add New Subject
        </div>
    </div>
    <div style="padding:24px;">
        @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:10px;padding:10px 14px;font-size:.85rem;margin-bottom:16px;">{{ $errors->first() }}</div>
        @endif
        <form action="{{ route('admin.subjects.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Subject Code</label>
                    <input type="text" name="code" class="adm-input" value="{{ old('code') }}" required style="width:100%;" placeholder="e.g. CC101">
                </div>
                <div class="col-md-8">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Subject Name</label>
                    <input type="text" name="name" class="adm-input" value="{{ old('name') }}" required style="width:100%;">
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Year Level</label>
                    <select name="year_level" class="adm-input" required style="width:100%;">
                        <option value="">Select</option>
                        @foreach([1,2,3,4] as $y)<option value="{{ $y }}" {{ old('year_level')==$y?'selected':'' }}>Year {{ $y }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Semester</label>
                    <select name="semester" class="adm-input" required style="width:100%;">
                        <option value="">Select</option>
                        <option value="1" {{ old('semester')=='1'?'selected':'' }}>1st Semester</option>
                        <option value="2" {{ old('semester')=='2'?'selected':'' }}>2nd Semester</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Course</label>
                    <select name="course" class="adm-input" required style="width:100%;">
                        <option value="">Select Course</option>
                        @foreach(['BSCS','BSIT','BSIS'] as $c)
                        <option value="{{ $c }}" {{ old('course')==$c?'selected':'' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Units</label>
                    <input type="number" name="units" class="adm-input" value="{{ old('units') }}" min="1" max="6" style="width:100%;">
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Days (MWF, TTH, S)</label>
                    <input type="text" name="days" class="adm-input" value="{{ old('days') }}" 
                        style="width:100%;" placeholder="MWF" 
                        id="days-field" maxlength="15"
                        title="Enter class days using: M=Monday, T=Tuesday, W=Wednesday, TH=Thursday, F=Friday, S=Saturday">
                </div>
                <div class="col-md-2">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Start Time</label>
                    <input type="time" name="start_time" class="adm-input" value="{{ old('start_time') }}" style="width:100%;">
                </div>
                <div class="col-md-2">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">End Time</label>
                    <input type="time" name="end_time" class="adm-input" value="{{ old('end_time') }}" style="width:100%;">
                </div>
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Instructor</label>
                    <select name="instructor_id" class="adm-input" style="width:100%;">
                        <option value="">Select Instructor</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ old('instructor_id') == $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Section</label>
                    <input type="text" name="section" class="adm-input" value="{{ old('section') }}" style="width:100%;" placeholder="e.g. A, B, 1001, CS-1A">
                </div>
                <div class="col-12" style="margin-top:8px;">
                    <button type="submit" class="adm-btn adm-btn-primary"><i class="bi bi-check2 me-2"></i>Add Subject</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Enhance the days field functionality
document.addEventListener('DOMContentLoaded', function() {
    const daysField = document.getElementById('days-field');
    if (daysField) {
        // Add visual feedback
        daysField.addEventListener('focus', function() {
            this.style.borderColor = '#800000';
            this.style.boxShadow = '0 0 0 3px rgba(128,0,0,0.1)';
        });
        
        daysField.addEventListener('blur', function() {
            this.style.borderColor = '#e2e8f0';
            this.style.boxShadow = 'none';
        });
        
        // Auto-uppercase and format input
        daysField.addEventListener('input', function() {
            let value = this.value.toUpperCase();
            // Remove invalid characters
            value = value.replace(/[^MTWTHFSU]/g, '');
            this.value = value;
        });
    }
});
</script>
@endsection
