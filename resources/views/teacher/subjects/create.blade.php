@extends('teacher.layout')
@section('page-title', 'Add Subject')

@section('content')
<a href="{{ route('teacher.classroom.index') }}" style="display:inline-flex;align-items:center;gap:7px;font-size:.85rem;font-weight:600;color:#64748b;text-decoration:none;padding:8px 14px;border:1.5px solid #e2e8f0;border-radius:9px;background:white;margin-bottom:20px;" onmouseover="this.style.color='#059669';this.style.borderColor='#059669';" onmouseout="this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="tch-card" style="max-width:680px;">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-plus-circle-fill"></i></div>
            Add New Subject
        </div>
    </div>
    <div style="padding:24px;">
        @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:10px;padding:10px 14px;font-size:.85rem;margin-bottom:16px;">{{ $errors->first() }}</div>
        @endif
        <form action="{{ route('teacher.subjects.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Subject Code</label>
                    <input type="text" name="code" class="tch-input" value="{{ old('code') }}" required style="width:100%;" placeholder="e.g. CC101">
                </div>
                <div class="col-md-8">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Subject Name</label>
                    <input type="text" name="name" class="tch-input" value="{{ old('name') }}" required style="width:100%;">
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Year Level</label>
                    <select name="year_level" class="tch-input" required style="width:100%;">
                        <option value="">Select</option>
                        @foreach([1,2,3,4] as $y)<option value="{{ $y }}" {{ old('year_level')==$y?'selected':'' }}>Year {{ $y }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Semester</label>
                    <select name="semester" class="tch-input" required style="width:100%;">
                        <option value="">Select</option>
                        <option value="1" {{ old('semester')=='1'?'selected':'' }}>1st Semester</option>
                        <option value="2" {{ old('semester')=='2'?'selected':'' }}>2nd Semester</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Units</label>
                    <input type="number" name="units" class="tch-input" value="{{ old('units') }}" min="1" max="6" style="width:100%;">
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Days (MWF, TTH)</label>
                    <input type="text" name="days" class="tch-input" value="{{ old('days') }}" style="width:100%;" placeholder="MWF">
                </div>
                <div class="col-md-2">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Start Time</label>
                    <input type="time" name="start_time" class="tch-input" value="{{ old('start_time') }}" style="width:100%;">
                </div>
                <div class="col-md-2">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">End Time</label>
                    <input type="time" name="end_time" class="tch-input" value="{{ old('end_time') }}" style="width:100%;">
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Section</label>
                    <input type="text" name="section" class="tch-input" value="{{ old('section') }}" style="width:100%;">
                </div>
                <div class="col-12" style="margin-top:8px;">
                    <button type="submit" class="tch-btn tch-btn-primary"><i class="bi bi-check2 me-2"></i>Add Subject</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection