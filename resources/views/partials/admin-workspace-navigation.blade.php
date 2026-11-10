@php
    $navigationId = $navigationId ?? 'adminSidebar';
    $navigationGroups = \App\Support\AdminNavigation::groups(auth()->user());
@endphp
<nav class="admin-workspace-nav" data-admin-navigation aria-label="Administration">
    <label class="admin-nav-search" for="{{ $navigationId }}Search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="{{ $navigationId }}Search" data-admin-nav-search placeholder="Find a page" autocomplete="off">
        <span class="visually-hidden">Find an admin page</span>
    </label>
    @foreach($navigationGroups as $group)
        @php($groupActive = collect($group['items'])->contains(fn ($item) => \App\Support\AdminNavigation::matches($item[3], request()->route()?->getName())))
        <details class="admin-nav-group" @if($groupActive) open @endif data-admin-nav-group>
            <summary title="{{ $group['label'] }}">
                <i class="bi {{ $group['icon'] }}" aria-hidden="true"></i>
                <span class="admin-nav-group-name">{{ $group['label'] }}</span>
                <i class="bi bi-chevron-down admin-nav-chevron" aria-hidden="true"></i>
            </summary>
            <div class="admin-nav-links">
                @foreach($group['items'] as [$label, $destination, $icon, $patterns])
                    @php($active = \App\Support\AdminNavigation::matches($patterns, request()->route()?->getName()))
                    <a href="{{ route($destination) }}" class="nav-link {{ $active ? 'active' : '' }}" data-admin-nav-link data-title="{{ $label }}" @if($active) aria-current="page" @endif title="{{ $label }}">
                        <i class="bi {{ $icon }}" aria-hidden="true"></i><span class="nav-link-text">{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        </details>
    @endforeach
    <p class="admin-nav-empty" data-admin-nav-empty role="status" hidden>No matching pages. Try “students”, “reports”, or “settings”.</p>
</nav>
