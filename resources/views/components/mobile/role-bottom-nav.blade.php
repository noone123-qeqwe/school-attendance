@php
    $navUser = auth()->user();
    $role = ucfirst($navUser->role);
    $items = \App\Support\MobileNavigation::tabs($navUser);
@endphp

<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav" aria-label="{{ $role }} navigation">
    @foreach($items as $item)
        @php
            $isActive = $navUser->isTeacher()
                ? \App\Support\TeacherNavigation::isActive($item['key'], $item['active'])
                : request()->routeIs(...$item['active']);
            $childAware = in_array($item['route'], ['parent.dashboard', 'parent.calendar'], true);
            $isParentAlerts = $item['route'] === 'parent.notifications';
            $isStudentScan = $navUser->isStudent() && $item['key'] === 'scan';
        @endphp
        @if($item['route'] === null)
            <button type="button" @class(['mbn-item', 'mbn-item-more', 'active' => $isActive])
                    aria-label="{{ $item['label'] }}" aria-haspopup="dialog"
                    aria-controls="moreSheet" aria-expanded="false">
                <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['shortLabel'] }}</span>
            </button>
        @else
            <a href="{{ route($item['route'], $item['query']) }}"
               @class([
                   'mbn-item',
                   'mbn-item-featured' => $item['featured'] && !$isStudentScan,
                   'mbn-item-scan' => $isStudentScan,
                   'active' => $isActive,
               ])
               @if($isActive) aria-current="{{ request()->routeIs($item['route']) ? 'page' : 'location' }}" @endif
               aria-label="{{ $item['label'] }}"
               @if($childAware) data-parent-child-context @endif
               @if($isParentAlerts) id="parentMobileAlertsLink" @endif>
                @if($item['featured'])
                    <span class="{{ $isStudentScan ? 'mbn-scan-icon' : 'mbn-featured-btn' }}" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.5 8.5V5.5C3.5 4.4 4.4 3.5 5.5 3.5H8.5M15.5 3.5H18.5C19.6 3.5 20.5 4.4 20.5 5.5V8.5M3.5 15.5V18.5C3.5 19.6 4.4 20.5 5.5 20.5H8.5M15.5 20.5H18.5C19.6 20.5 20.5 19.6 20.5 18.5V15.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 7h3.5v3.5H7zM13.5 7H17v3.5h-3.5zM7 13.5h3.5V17H7z" fill="currentColor"/>
                            <circle cx="15.25" cy="15.25" r="1.75" fill="currentColor"/>
                        </svg>
                    </span>
                @else
                    <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                @endif
                @if($isParentAlerts)
                    <span class="mbn-badge" data-parent-unread-count @if($parentUnreadCount === 0) hidden @endif aria-label="{{ $parentUnreadCount }} unread alerts">{{ $parentUnreadCount > 9 ? '9+' : $parentUnreadCount }}</span>
                @endif
                <span>{{ $item['shortLabel'] }}</span>
            </a>
        @endif
    @endforeach
</nav>
