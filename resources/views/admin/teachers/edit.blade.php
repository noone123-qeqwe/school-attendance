@extends('layouts.admin_premium')
@section('page-title', 'Edit Teacher')

@section('content')
<a href="{{ route('admin.teachers') }}" class="adm-btn adm-btn-ghost" style="margin-bottom:20px;text-decoration:none;">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="adm-card" style="max-width:680px;">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-pencil-fill"></i></div>
            Edit Teacher
        </div>
    </div>
    <div style="padding:24px;">
        @if($errors->any())
        <div style="background:rgba(220,38,38,0.14);border:1px solid rgba(220,38,38,0.3);color:#fca5a5;border-radius:12px;padding:12px 16px;margin-bottom:18px;font-size:0.875rem;">
            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('admin.teacher.update', $teacher) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Full Name</label>
                    <input type="text" name="name" class="adm-input" value="{{ old('name', $teacher->name) }}" required style="width:100%;">
                </div>
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Employee ID</label>
                    <input type="text" name="employee_id" class="adm-input" value="{{ old('employee_id', $teacher->employee_id) }}" required style="width:100%;">
                </div>
                <div class="col-md-12">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Email</label>
                    <input type="email" name="email" class="adm-input" value="{{ old('email', $teacher->email) }}" required style="width:100%;">
                </div>
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Department</label>
                    <input type="text" name="department" class="adm-input" value="{{ old('department', $teacher->department) }}" style="width:100%;">
                </div>
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Position</label>
                    <input type="text" name="position" class="adm-input" value="{{ old('position', $teacher->position) }}" style="width:100%;">
                </div>
                <div class="col-12" style="margin-top:16px;">
                    <button type="submit" class="adm-btn adm-btn-primary"><i class="bi bi-save me-2"></i>Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
