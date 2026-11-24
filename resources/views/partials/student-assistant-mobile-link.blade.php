@if(!empty($hasActiveAssistantAssignment))
    <a href="{{ route('student-assistant.classes') }}" class="more-sheet-item" data-color="gold" onclick="closeMoreSheet()" @if(request()->routeIs('student-assistant.*')) aria-current="page" @endif>
        <div class="more-sheet-item-icon"><i class="bi bi-qr-code" aria-hidden="true"></i></div>
        <span class="more-sheet-item-label">Student Assistant</span>
    </a>
@endif
