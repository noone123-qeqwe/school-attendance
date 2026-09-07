@extends('layouts.app')

@section('title', 'Manage Roster - ' . $subject->code)

@section('content')
<div class="saas-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 class="ent-section-title" style="margin-bottom:4px; font-size:1.5rem;">Manage Roster: {{ $subject->code }}</h1>
            <p class="ent-text-muted" style="margin:0;">{{ $subject->name }} | {{ $subject->year_level }} Year, {{ $subject->semester }}{{ (int)$subject->semester === 1 ? 'st' : 'nd' }} Sem</p>
        </div>
        <a href="{{ route('admin.subjects') }}" class="ent-btn ent-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Subjects
        </a>
    </div>

    @if(session('success'))
        <div class="ent-alert success" style="margin-bottom: 20px;">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="ent-alert danger" style="margin-bottom: 20px;">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    <x-card type="section" title="Enroll a Student" class="ent-mb-lg">
        <form action="{{ route('admin.enrollments.store', $subject) }}" method="POST" style="display:flex;gap:15px;align-items:flex-end; flex-wrap:wrap;">
            @csrf
            <div style="flex:1; min-width: 250px;">
                <label class="ent-label" style="display:block; margin-bottom:8px;">Select Student</label>
                <select name="student_id" class="ent-input" required style="width: 100%;">
                    <option value="">-- Select Student --</option>
                    @foreach($availableStudents as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->student_number ?? 'No ID' }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="ent-btn ent-btn-primary">
                    <i class="bi bi-plus-lg"></i> Enroll Student
                </button>
            </div>
        </form>
    </x-card>

    <x-card type="section" title="Enrolled Students ({{ $subject->enrolledStudents->count() }})">
        <div class="ent-scroll-x" style="margin: -20px;">
            <table class="ent-table" style="min-width: 600px; margin-bottom: 0;">
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
                        <td data-label="Student Name">
                            <div style="display:flex;align-items:center;gap:12px;">
                                @if($student->profile_image)
                                    <img src="{{ filter_var($student->profile_image, FILTER_VALIDATE_URL) ? $student->profile_image : Storage::url($student->profile_image) }}" alt="{{ $student->name }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                                @else
                                    <div class="ent-avatar ent-avatar-round" style="width:32px;height:32px;font-size:0.8rem;">
                                        {{ substr($student->name, 0, 2) }}
                                    </div>
                                @endif
                                <div style="font-weight:600;color:var(--ent-text);">{{ $student->name }}</div>
                            </div>
                        </td>
                        <td data-label="Student Number">{{ $student->student_number ?? '—' }}</td>
                        <td data-label="Course">{{ $student->course ?? '—' }}</td>
                        <td data-label="Actions" style="text-align:right;">
                            <form action="{{ route('admin.enrollments.destroy', [$subject, $student]) }}" method="POST" onsubmit="return confirm('Remove this student from the roster?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ent-btn ent-btn-xs ent-btn-ghost text-danger" title="Remove from Roster">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <div class="ent-empty" style="padding:48px 20px;">
                                <div class="ent-empty-icon" style="width:64px;height:64px;font-size:2rem; margin-bottom:16px;">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="ent-empty-text">No students enrolled in this subject yet.</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection
