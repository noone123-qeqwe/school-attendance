<div class="mb-3">
    <label class="ds-label" for="studentAssistant{{ $assistant->id ?? 'New' }}">Student from this class</label>
    <select class="ds-input w-100" id="studentAssistant{{ $assistant->id ?? 'New' }}" name="student_id" required>
        <option value="">Select a student</option>
        @foreach($eligibleAssistantStudents as $student)
            <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->student_number }})</option>
        @endforeach
    </select>
</div>
<div class="row g-3">
    <div class="col-sm-6">
        <label class="ds-label" for="assistantStart{{ $assistant->id ?? 'New' }}">Assignment start</label>
        <input class="ds-input w-100" id="assistantStart{{ $assistant->id ?? 'New' }}" type="date" name="starts_at" min="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}" required>
    </div>
    <div class="col-sm-6">
        <label class="ds-label" for="assistantEnd{{ $assistant->id ?? 'New' }}">Assignment end</label>
        <input class="ds-input w-100" id="assistantEnd{{ $assistant->id ?? 'New' }}" type="date" name="expires_at" min="{{ today()->toDateString() }}" value="{{ today()->addMonths(4)->toDateString() }}" required>
    </div>
</div>
