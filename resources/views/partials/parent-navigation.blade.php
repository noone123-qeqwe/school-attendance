@foreach(\App\Support\ParentNavigation::items() as [$label, $destination, $icon, $patterns, $color])
    @php($active = request()->routeIs(...$patterns))
    <a href="{{ route($destination) }}" class="{{ ($mobile ?? false) ? 'more-sheet-item' : 'nav-link' }} {{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif @if($mobile ?? false) data-color="{{ $color }}" onclick="closeMoreSheet()" @endif title="{{ $label }}">
        @if($mobile ?? false)<div class="more-sheet-item-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
        @else <i class="bi {{ $icon }}" aria-hidden="true"></i> @endif
        <span class="{{ ($mobile ?? false) ? 'more-sheet-item-label' : 'nav-link-text' }}">{{ $label }}</span>
        @if($destination === 'parent.notifications')
            <span class="parent-nav-count" data-parent-unread-count @if(($parentUnreadCount ?? 0) === 0) hidden @endif aria-label="{{ $parentUnreadCount ?? 0 }} unread alerts">{{ ($parentUnreadCount ?? 0) > 99 ? '99+' : ($parentUnreadCount ?? 0) }}</span>
        @endif
    </a>
@endforeach
