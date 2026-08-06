@extends('layouts.app')

@section('title', 'Cover a Class')

@section('content')
<div class="mb-4">
    <h1 class="saas-heading saas-heading-lg mb-1">Cover a Class</h1>
    <p class="saas-text-muted m-0">Temporarily access another teacher's class to mark attendance.</p>
</div>

<x-card title="Substitute Registration" icon="bi bi-person-badge">
    <form method="POST" action="{{ route('teacher.cover.store') }}">
        @csrf
        
        <div class="mb-3">
            <label class="form-label" style="color: #f3e7cd;">Teacher to Cover</label>
            <select id="teacherSelect" class="form-select" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;">
                <option value="">Select a teacher...</option>
                @foreach($otherTeachers as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label" style="color: #f3e7cd;">Subject / Class</label>
            <select name="subject_id" id="subjectSelect" class="form-select" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;" required disabled>
                <option value="">Select a teacher first...</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label" style="color: #f3e7cd;">Date of Substitution</label>
            <input type="date" name="date" class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;" value="{{ today()->format('Y-m-d') }}" required>
            <small class="text-muted d-block mt-1">You will only have access to this class on the selected date.</small>
        </div>

        <button type="submit" class="btn btn-primary" style="background: var(--gold); border: none;">
            <i class="bi bi-check-circle"></i> Register as Substitute
        </button>
    </form>
</x-card>

<script>
    document.getElementById('teacherSelect').addEventListener('change', function() {
        const teacherId = this.value;
        const subjectSelect = document.getElementById('subjectSelect');
        
        if (!teacherId) {
            subjectSelect.innerHTML = '<option value="">Select a teacher first...</option>';
            subjectSelect.disabled = true;
            return;
        }

        subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';
        subjectSelect.disabled = true;

        fetch(`/teacher/cover-class/subjects/${teacherId}`)
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    subjectSelect.innerHTML = '<option value="">No subjects found for this teacher</option>';
                    return;
                }
                
                let options = '<option value="">Select a subject...</option>';
                data.forEach(subject => {
                    options += `<option value="${subject.id}">${subject.code} - ${subject.name}</option>`;
                });
                
                subjectSelect.innerHTML = options;
                subjectSelect.disabled = false;
            })
            .catch(err => {
                console.error(err);
                subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
            });
    });
</script>
@endsection
