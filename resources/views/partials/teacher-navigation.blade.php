@php $mobile = $mobile ?? false; @endphp
@if($mobile)
    @foreach(\App\Support\TeacherNavigation::groups() as $group)
        <div class="teacher-nav-group">
            <p class="teacher-nav-label">{{ $group['label'] }}</p>
            @foreach($group['items'] as [$key, $label, $destination, $icon, $patterns, $description, $query])
                @php
                    $active = \App\Support\TeacherNavigation::isActive($key, $patterns);
                    $pendingCount = $key === 'excuses' ? ($pendingExcusesCount ?? 0) : ($key === 'corrections' ? ($pendingCorrectionsCount ?? 0) : 0);
                @endphp
                <a href="{{ route($destination, $query) }}" class="more-sheet-item {{ $active ? 'active' : '' }}"
                   data-title="{{ $label }}" @if($active) aria-current="page" @endif data-color="gold" onclick="closeMoreSheet()">
                    <span class="more-sheet-item-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                    <span class="teacher-nav-copy">
                        <span class="more-sheet-item-label">{{ $label }}</span>
                        <small class="teacher-nav-description">{{ $description }}</small>
                    </span>
                    @if($pendingCount > 0)<span class="teacher-nav-count" aria-label="{{ $pendingCount }} pending requests">{{ $pendingCount > 99 ? '99+' : $pendingCount }}</span>@endif
                </a>
            @endforeach
        </div>
    @endforeach
@else
    @foreach(\App\Support\TeacherNavigation::sidebarSections() as $sectionIndex => $items)
        @if($sectionIndex > 0)
            <div class="teacher-nav-divider" role="separator"></div>
        @endif
        @foreach($items as [$key, $label, $destination, $icon, $patterns, $query])
            @php
                $active = \App\Support\TeacherNavigation::isActive($key, $patterns);
                $pendingCount = $key === 'excuses' ? ($pendingExcusesCount ?? 0) : ($key === 'corrections' ? ($pendingCorrectionsCount ?? 0) : 0);
            @endphp
            <a href="{{ route($destination, $query) }}"
               class="nav-link {{ $active ? 'active' : '' }}"
               title="{{ $label }}"
               data-title="{{ $label }}"
               aria-label="{{ $label }}"
               @if($active) aria-current="page" @endif>
                <i class="bi {{ $icon }}" aria-hidden="true"></i>
                <span class="nav-link-text">{{ $label }}</span>
                @if($pendingCount > 0)
                    <span class="teacher-nav-count" aria-label="{{ $pendingCount }} pending requests">{{ $pendingCount > 99 ? '99+' : $pendingCount }}</span>
                @endif
            </a>
        @endforeach
    @endforeach
@endif
