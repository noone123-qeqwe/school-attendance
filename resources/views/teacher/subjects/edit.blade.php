@extends('layouts.app')
@section('page-title', 'Edit Subject')

@section('content')
<a href="{{ route('teacher.classroom.index') }}" style="display:inline-flex;align-items:center;gap:7px;font-size:.85rem;font-weight:600;color:#64748b;text-decoration:none;padding:8px 14px;border:1.5px solid #e2e8f0;border-radius:9px;background:white;margin-bottom:20px;" onmouseover="this.style.color='#059669';this.style.borderColor='#059669';" onmouseout="this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="tch-card" style="max-width:680px;">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-pencil-fill"></i></div>
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

        <form action="{{ route('teacher.subjects.update', $subject->id) }}" method="POST">
            @csrf @method('PUT')
            @php
                // Load existing schedule data for pre-filling the form
                $schedules   = $subject->schedules;
                $firstSched  = $schedules->first();
                // Build days string from schedule day names e.g. "Monday,Thursday" â†’ "TTH"
                $dayCodeMap  = ['Monday'=>'M','Tuesday'=>'T','Wednesday'=>'W','Thursday'=>'TH','Friday'=>'F','Saturday'=>'S','Sunday'=>'U'];
                $existingDays = $schedules->map(fn($s) => $dayCodeMap[$s->day] ?? '')->filter()->implode('');
                $existingStart = $firstSched ? \Carbon\Carbon::parse($firstSched->start_time)->format('H:i') : '';
                $existingEnd   = $firstSched ? \Carbon\Carbon::parse($firstSched->end_time)->format('H:i') : '';
            @endphp
            <div class="row g-3">

                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Subject Code</label>
                    <input type="text" name="code" class="tch-input"
                        value="{{ old('code', $subject->code) }}"
                        required style="width:100%;">
                </div>

                <div class="col-md-8">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Subject Name</label>
                    <input type="text" name="name" class="tch-input"
                        value="{{ old('name', $subject->name) }}"
                        required style="width:100%;">
                </div>

                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Year Level</label>
                    <select name="year_level" class="tch-input" required style="width:100%;">
                        @foreach([1,2,3,4] as $y)
                            <option value="{{ $y }}" {{ old('year_level', $subject->year_level) == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Semester</label>
                    <select name="semester" class="tch-input" required style="width:100%;">
                        <option value="1" {{ old('semester', $subject->semester) == '1' ? 'selected' : '' }}>1st Semester</option>
                        <option value="2" {{ old('semester', $subject->semester) == '2' ? 'selected' : '' }}>2nd Semester</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Units</label>
                    <input type="number" name="units" class="tch-input"
                        value="{{ old('units', $subject->units) }}"
                        min="1" max="6" style="width:100%;">
                </div>

                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Days (MWF, TTH)</label>
                    <input type="text" name="days" class="tch-input"
                        value="{{ old('days', $existingDays) }}"
                        style="width:100%;" placeholder="e.g. MWF or TTHS">
                </div>

                <div class="col-md-2">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Start Time</label>
                    <input type="time" name="start_time" class="tch-input"
                        value="{{ old('start_time', $existingStart) }}"
                        style="width:100%;">
                </div>

                <div class="col-md-2">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">End Time</label>
                    <input type="time" name="end_time" class="tch-input"
                        value="{{ old('end_time', $existingEnd) }}"
                        style="width:100%;">
                </div>

                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Section</label>
                    <input type="text" name="section" class="tch-input"
                        value="{{ old('section', $subject->section) }}"
                        style="width:100%;">
                </div>

                <div class="col-12" style="margin-top:8px;">
                    <button type="submit" class="tch-btn tch-btn-primary">
                        <i class="bi bi-check2 me-2"></i>Save Changes
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>
@endsection
