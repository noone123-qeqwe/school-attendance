@php
    $mobile = $mobile ?? false;
@endphp
@foreach(\App\Support\ParentNavigation::groups() as $group)
    <div class="parent-nav-group {{ $mobile ? 'parent-nav-group-mobile' : '' }}">
        <p class="parent-nav-group-title">{{ $group['label'] }}</p>
        @foreach($group['items'] as [$label, $destination, $icon, $patterns, $color, $description])
            @php
                $active = request()->routeIs(...$patterns);
                $childAware = in_array($destination, ['parent.dashboard', 'parent.schedule', 'parent.calendar'], true);
            @endphp
            <a href="{{ route($destination) }}"
               class="{{ $mobile ? 'more-sheet-item' : 'nav-link' }} {{ $active ? 'active' : '' }}"
               @if($active) aria-current="page" @endif
               @if($childAware) data-parent-child-context @endif
               @if($mobile) data-color="{{ $color }}" onclick="closeMoreSheet()" @endif
               title="{{ $description }}">
                @if($mobile)
                    <div class="more-sheet-item-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
                @else
                    <i class="bi {{ $icon }}" aria-hidden="true"></i>
                @endif
                @if($mobile)
                    <span class="parent-mobile-menu-copy">
                        <span class="more-sheet-item-label">{{ $label }}</span>
                        <small class="parent-nav-link-description">{{ $description }}</small>
                    </span>
                @else
                    <span class="nav-link-text">{{ $label }}</span>
                    <small class="parent-nav-link-description">{{ $description }}</small>
                @endif
                @if($destination === 'parent.notifications')
                    <span class="parent-nav-count" data-parent-unread-count @if(($parentUnreadCount ?? 0) === 0) hidden @endif aria-label="{{ $parentUnreadCount ?? 0 }} unread alerts">{{ ($parentUnreadCount ?? 0) > 99 ? '99+' : ($parentUnreadCount ?? 0) }}</span>
                @endif
            </a>
        @endforeach
    </div>
@endforeach
