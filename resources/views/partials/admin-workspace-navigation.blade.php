@php
    $navigationId = $navigationId ?? 'adminSidebar';
    $navigationGroups = \App\Support\AdminNavigation::groups(auth()->user());
    $routeName = request()->route()?->getName();
@endphp
<nav class="admin-workspace-nav" data-admin-navigation aria-label="Administration">
    <div class="admin-nav-search">
        <label class="visually-hidden" for="{{ $navigationId }}Search">Find an admin page</label>
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="{{ $navigationId }}Search" data-admin-nav-search placeholder="Find a page…" autocomplete="off" aria-controls="{{ $navigationId }}Links">
    </div>
    <div id="{{ $navigationId }}Links">
    @foreach($navigationGroups as $group)
        <div class="admin-nav-group" data-admin-nav-group>
            <p class="admin-nav-label">{{ $group['label'] }}</p>
            <ul class="admin-nav-links">
                @foreach($group['items'] as $item)
                    @php([$label, $destination, $icon, $patterns] = $item)
                    @php($active = \App\Support\AdminNavigation::matches($patterns, $routeName))
                    <li data-admin-nav-entry>
                        <a href="{{ route($destination) }}" class="nav-link {{ $active ? 'active' : '' }}" data-admin-nav-link data-admin-nav-keywords="{{ $item[4] ?? '' }} {{ $group['label'] }}" data-title="{{ $label }}" @if($active) aria-current="{{ $destination === $routeName ? 'page' : 'location' }}" @endif title="{{ $label }}">
                            <i class="bi {{ $icon }}" aria-hidden="true"></i><span class="nav-link-text">{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
    </div>
    <p class="admin-nav-empty" data-admin-nav-empty role="status" hidden>No matching pages. Try “students”, “reports”, or “settings”.</p>
</nav>
