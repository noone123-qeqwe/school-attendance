@extends('layouts.app')
@section('page-title', 'Edit Student')

@section('content')
<a href="{{ route('admin.students') }}" class="adm-btn adm-btn-ghost" style="margin-bottom:20px;text-decoration:none;">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="adm-card" style="max-width:640px;">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-pencil-fill"></i></div>
            Edit â€” {{ $student->name }}
        </div>
    </div>
    <div style="padding:24px;">
        @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:10px;padding:10px 14px;font-size:.85rem;margin-bottom:16px;">{{ $errors->first() }}</div>
        @endif
        <form action="{{ route('admin.student.update', $student->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Full Name</label>
                    <input type="text" name="name" class="adm-input" value="{{ old('name', $student->name) }}" required style="width:100%;">
                </div>
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Student Number</label>
                    <input type="text" class="adm-input" value="{{ $student->student_number }}" disabled style="width:100%;background:#f8fafc;color:#94a3b8;">
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Course</label>
                    <select name="course" class="adm-input" required style="width:100%;">
                        @foreach(['BSCS','BSIT','BSIS'] as $c)
                        <option value="{{ $c }}" {{ old('course',$student->course)==$c?'selected':'' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Year Level</label>
                    <select name="year_level" class="adm-input" required style="width:100%;">
                        @foreach([1,2,3,4] as $y)
                        <option value="{{ $y }}" {{ old('year_level',$student->year_level)==$y?'selected':'' }}>Year {{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Semester</label>
                    <select name="semester" class="adm-input" required style="width:100%;">
                        <option value="1" {{ old('semester',$student->semester)=='1'?'selected':'' }}>1st Semester</option>
                        <option value="2" {{ old('semester',$student->semester)=='2'?'selected':'' }}>2nd Semester</option>
                    </select>
                </div>
                <div class="col-12">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Email Address</label>
                    <input type="email" name="email" class="adm-input" value="{{ old('email', $student->email) }}" required style="width:100%;">
                </div>
                <div class="col-12" style="margin-top:8px;">
                    <button type="submit" class="adm-btn adm-btn-primary"><i class="bi bi-check2 me-2"></i>Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
