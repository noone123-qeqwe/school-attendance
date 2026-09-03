@extends('layouts.app')
@section('page-title', 'Add Teacher')

@section('content')
<a href="{{ route('admin.teachers') }}" class="adm-btn adm-btn-ghost" style="margin-bottom:20px;text-decoration:none;">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="adm-card" style="max-width:680px;">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-person-plus-fill"></i></div>
            Add Teacher
        </div>
    </div>
    <div style="padding:24px;">
        @if($errors->any())
        <div style="background:rgba(220,38,38,0.14);border:1px solid rgba(220,38,38,0.3);color:#fca5a5;border-radius:12px;padding:12px 16px;margin-bottom:18px;font-size:0.875rem;">
            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('admin.teacher.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Full Name</label>
                    <input type="text" name="name" class="adm-input" value="{{ old('name') }}" required style="width:100%;">
                </div>
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Employee ID</label>
                    <input type="text" name="employee_id" class="adm-input" value="{{ old('employee_id') }}" required style="width:100%;">
                </div>
                <div class="col-md-12">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Email</label>
                    <input type="email" name="email" class="adm-input" value="{{ old('email') }}" required style="width:100%;">
                </div>
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Department</label>
                    <input type="text" name="department" class="adm-input" value="{{ old('department') }}" style="width:100%;">
                </div>
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Position</label>
                    <input type="text" name="position" class="adm-input" value="{{ old('position') }}" style="width:100%;">
                </div>
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Password</label>
                    <input type="password" name="password" class="adm-input" required minlength="8" style="width:100%;">
                </div>
                <div class="col-md-6">
                    <label style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px;">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="adm-input" required style="width:100%;">
                </div>
                <div class="col-12" style="margin-top:16px;">
                    <button type="submit" class="adm-btn adm-btn-primary"><i class="bi bi-person-plus me-2"></i>Add Teacher</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
