@php
    $navigationId = $navigationId ?? 'adminSidebar';
    $navigationGroups = \App\Support\AdminNavigation::groups(auth()->user());
@endphp
<nav class="admin-workspace-nav" data-admin-navigation aria-label="Administration">
    <div class="admin-nav-search">
        <label class="visually-hidden" for="{{ $navigationId }}Search">Find an admin page</label>
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="{{ $navigationId }}Search" data-admin-nav-search placeholder="Find a page…" autocomplete="off" aria-controls="{{ $navigationId }}Links">
    </div>
    <div id="{{ $navigationId }}Links">
    @foreach($navigationGroups as $group)
        @php($groupActive = collect($group['items'])->contains(fn ($item) => \App\Support\AdminNavigation::matches($item[3], request()->route()?->getName())))
        <details class="admin-nav-group" @if($groupActive) open @endif data-admin-nav-group>
            <summary title="{{ $group['label'] }}">
                <i class="bi {{ $group['icon'] }}" aria-hidden="true"></i>
                <span class="admin-nav-group-name"><strong>{{ $group['label'] }}</strong><small>{{ $group['description'] }}</small></span>
                <i class="bi bi-chevron-down admin-nav-chevron" aria-hidden="true"></i>
            </summary>
            <div class="admin-nav-links">
                @foreach($group['items'] as $item)
                    @php([$label, $destination, $icon, $patterns] = $item)
                    @php($active = \App\Support\AdminNavigation::matches($patterns, request()->route()?->getName()))
                    <a href="{{ route($destination) }}" class="nav-link {{ $active ? 'active' : '' }}" data-admin-nav-link data-admin-nav-keywords="{{ $item[4] ?? '' }} {{ $group['label'] }}" data-title="{{ $label }}" @if($active) aria-current="page" @endif title="{{ $label }}">
                        <i class="bi {{ $icon }}" aria-hidden="true"></i><span class="nav-link-text">{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        </details>
    @endforeach
    </div>
    <p class="admin-nav-empty" data-admin-nav-empty role="status" hidden>No matching pages. Try “students”, “reports”, or “settings”.</p>
</nav>
