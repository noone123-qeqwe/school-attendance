@php
    $availableChildren = isset($children) ? $children : collect([$child ?? $selectedChild ?? null])->filter();
    $contextChild = $selectedChild ?? $child ?? $availableChildren->first();
    $contextSection = $contextSection ?? '';
    $childRouteTemplate = request()->routeIs('parent.child.warnings')
        ? route('parent.child.warnings', ['child' => '__parent_child__'])
        : (request()->routeIs('parent.child') ? route('parent.child', ['child' => '__parent_child__']) : null);
@endphp

@if($contextChild)
    <section class="parent-child-context" aria-label="Student workspace">
        <div class="parent-child-context-main">
            <div class="parent-child-context-avatar" aria-hidden="true">{{ strtoupper(mb_substr($contextChild->name, 0, 1)) }}</div>
            <div class="parent-child-context-copy">
                <span class="parent-child-context-eyebrow">Viewing student</span>
                <strong>{{ $contextChild->name }}</strong>
                <span>{{ $contextChild->student_number ?: 'Student account' }}</span>
            </div>
            @if($availableChildren->count() > 1)
                <form id="child-select-form" method="GET" action="{{ request()->url() }}" class="parent-child-context-select">
                    @foreach(request()->except('child_id', 'page') as $name => $value)
                        @if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
                    @endforeach
                    <label for="parentContextChild">Student</label>
                    <select id="parentContextChild" name="child_id" class="form-select premium-select"
                            @if($childRouteTemplate) data-parent-child-context-switch data-parent-child-context-template="{{ $childRouteTemplate }}" @else onchange="this.form.submit()" @endif>
                        @foreach($availableChildren as $availableChild)
                            <option value="{{ $availableChild->id }}" {{ $contextChild->id === $availableChild->id ? 'selected' : '' }}>{{ $availableChild->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
        <nav class="parent-child-context-links" aria-label="Student tasks">
            <a href="{{ route('parent.attendance.calendar', ['child_id' => $contextChild->id]) }}" class="{{ $contextSection === 'attendance' ? 'active' : '' }}" aria-label="View attendance for {{ $contextChild->name }}">
                <i class="bi bi-calendar-check" aria-hidden="true"></i><span>Attendance</span>
            </a>
            <a href="{{ route('parent.child', $contextChild) }}" class="{{ $contextSection === 'records' ? 'active' : '' }}" aria-label="View detailed attendance records for {{ $contextChild->name }}">
                <i class="bi bi-clock-history" aria-hidden="true"></i><span>Detailed records</span>
            </a>
            <a href="{{ route('parent.schedule', ['child_id' => $contextChild->id]) }}" class="{{ $contextSection === 'schedule' ? 'active' : '' }}" aria-label="View class schedule for {{ $contextChild->name }}">
                <i class="bi bi-clock" aria-hidden="true"></i><span>Class schedule</span>
            </a>
            <a href="{{ route('parent.excuses.create_general', ['child_id' => $contextChild->id]) }}" class="{{ $contextSection === 'excuse' ? 'active' : '' }}" aria-label="Submit an excuse for {{ $contextChild->name }}">
                <i class="bi bi-file-earmark-plus" aria-hidden="true"></i><span>Submit an excuse</span>
            </a>
        </nav>
    </section>
@endif
