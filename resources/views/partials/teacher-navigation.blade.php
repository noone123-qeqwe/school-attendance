@php $mobile = $mobile ?? false; @endphp
@if($mobile)
    @php
        $primaryRoutes = \App\Support\MobileNavigation::primaryRoutes(auth()->user());
        $sidebarItems = array_merge(...\App\Support\TeacherNavigation::sidebarSections());
        $sidebarItems = array_filter($sidebarItems, fn (array $item) => !in_array($item[2], $primaryRoutes, true));
        $sidebarKeys = array_column($sidebarItems, 0);
    @endphp
    @foreach(\App\Support\TeacherNavigation::groups() as $group)
        @php
            $items = array_filter($group['items'], fn ($item) => in_array($item[0], $sidebarKeys, true));
        @endphp
        @continue(!$items)
        <div class="teacher-nav-group">
            <p class="teacher-nav-label">{{ $group['label'] }}</p>
            @foreach($items as [$key, $label, $destination, $icon, $patterns, $description, $query])
                @php
                    $active = \App\Support\TeacherNavigation::isActive($key, $patterns);
                    $pendingCount = $key === 'requests' ? (($pendingExcusesCount ?? 0) + ($pendingCorrectionsCount ?? 0)) : 0;
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
        @foreach($items as [$key, $label, $destination, $icon, $patterns, , $query])
            @php
                $active = \App\Support\TeacherNavigation::isActive($key, $patterns);
                $pendingCount = $key === 'requests' ? (($pendingExcusesCount ?? 0) + ($pendingCorrectionsCount ?? 0)) : 0;
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
