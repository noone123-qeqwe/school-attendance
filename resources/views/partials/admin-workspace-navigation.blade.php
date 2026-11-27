@php
    $navigationId = $navigationId ?? 'adminSidebar';
    $navigationGroups = \App\Support\AdminNavigation::groups(auth()->user());
    $routeName = request()->route()?->getName();
@endphp
<nav class="admin-workspace-nav" aria-label="Administration">
    <ul class="admin-nav-links" id="{{ $navigationId }}Links">
        @foreach($navigationGroups as $group)
            @foreach($group['items'] as [$label, $destination, $icon, $patterns])
                @php($active = \App\Support\AdminNavigation::matches($patterns, $routeName))
                <li>
                    <a href="{{ route($destination) }}" class="nav-link {{ $active ? 'active' : '' }}" data-title="{{ $label }}" @if($active) aria-current="{{ $destination === $routeName ? 'page' : 'location' }}" @endif title="{{ $label }}">
                        <i class="bi {{ $icon }}" aria-hidden="true"></i><span class="nav-link-text">{{ $label }}</span>
                    </a>
                </li>
            @endforeach
        @endforeach
    </ul>
</nav>
