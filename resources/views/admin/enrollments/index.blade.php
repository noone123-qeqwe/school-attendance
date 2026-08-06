@extends('layouts.app')

@section('title', 'Manage Roster - ' . $subject->code)

@section('content')
<div class="saas-container">
    <div class="saas-header">
        <div>
            <h1 class="saas-title">Manage Roster: {{ $subject->code }}</h1>
            <p class="saas-subtitle">{{ $subject->name }} | {{ $subject->year_level }} Year, {{ $subject->semester }}{{ (int)$subject->semester === 1 ? 'st' : 'nd' }} Sem</p>
        </div>
        <a href="{{ route('admin.subjects.index') }}" class="adm-btn adm-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Subjects
        </a>
    </div>

    @if(session('success'))
        <div class="saas-alert saas-alert-success">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="saas-alert saas-alert-error">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="saas-card" style="margin-bottom: 2rem;">
        <div class="saas-card-header">
            <h3 style="margin:0;font-size:1.1rem;font-weight:600;color:#0f172a;">Enroll a Student</h3>
        </div>
        <div class="saas-card-body">
            <form action="{{ route('admin.enrollments.store', $subject) }}" method="POST" style="display:flex;gap:15px;align-items:flex-end;">
                @csrf
                <div style="flex:1;">
                    <label class="saas-label">Select Student</label>
                    <select name="student_id" class="saas-input" required>
                        <option value="">-- Select Student --</option>
                        @foreach($availableStudents as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->student_number ?? 'No ID' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="adm-btn adm-btn-primary">
                        <i class="bi bi-plus-lg"></i> Enroll Student
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="saas-card">
        <div class="saas-card-header">
            <h3 style="margin:0;font-size:1.1rem;font-weight:600;color:#0f172a;">Enrolled Students ({{ $subject->enrolledStudents->count() }})</h3>
        </div>
        <div class="saas-card-body" style="padding:0;">
            <div class="saas-table-responsive">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Student Number</th>
                            <th>Course</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subject->enrolledStudents as $student)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    @if($student->profile_image)
                                        <img src="{{ filter_var($student->profile_image, FILTER_VALIDATE_URL) ? $student->profile_image : Storage::url($student->profile_image) }}" alt="{{ $student->name }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                                    @else
                                        <div style="width:32px;height:32px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;font-weight:600;font-size:0.8rem;">
                                            {{ substr($student->name, 0, 2) }}
                                        </div>
                                    @endif
                                    <div style="font-weight:600;color:#0f172a;">{{ $student->name }}</div>
                                </div>
                            </td>
                            <td>{{ $student->student_number ?? '—' }}</td>
                            <td>{{ $student->course ?? '—' }}</td>
                            <td style="text-align:right;">
                                <form action="{{ route('admin.enrollments.destroy', [$subject, $student]) }}" method="POST" onsubmit="return confirm('Remove this student from the roster?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="saas-btn-icon" style="color:#ef4444;" title="Remove from Roster">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align:center;padding:3rem 1rem;color:#64748b;">
                                <i class="bi bi-people" style="font-size:2rem;margin-bottom:10px;display:block;"></i>
                                No students enrolled in this subject yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
