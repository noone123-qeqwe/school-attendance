@php
    $adminArea = \App\Support\AdminNavigation::context(auth()->user(), request()->route()?->getName())['group'];
    $isAcademic = $adminArea === 'Academic Management';
@endphp
<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav" aria-label="Admin navigation">
    <a href="{{ route('admin.dashboard') }}" @class(['mbn-item', 'active' => request()->routeIs('admin.dashboard')]) @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
        <i class="bi bi-grid" aria-hidden="true"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('admin.academic.overview') }}" @class(['mbn-item', 'active' => $isAcademic]) @if($isAcademic) aria-current="location" @endif>
        <i class="bi bi-mortarboard" aria-hidden="true"></i>
        <span>Academic</span>
    </a>
    <a href="{{ route('admin.reports') }}" @class(['mbn-item', 'active' => request()->routeIs('admin.reports*')]) @if(request()->routeIs('admin.reports*')) aria-current="location" @endif>
        <i class="bi bi-bar-chart" aria-hidden="true"></i>
        <span>Reports</span>
    </a>
    <a href="{{ route('admin.notifications') }}" @class(['mbn-item', 'active' => request()->routeIs('admin.notifications*')]) @if(request()->routeIs('admin.notifications*')) aria-current="page" @endif>
        <i class="bi bi-bell" aria-hidden="true"></i>
        <span>Alerts</span>
    </a>
    <button type="button" @class(['mbn-item', 'mbn-item-more', 'active' => in_array($adminArea, ['School operations', 'Settings & access'], true) && !request()->routeIs('admin.notifications*')]) aria-label="Open all admin pages" aria-haspopup="dialog" aria-controls="moreSheet" aria-expanded="false">
        <i class="bi bi-list" aria-hidden="true"></i>
        <span>Menu</span>
    </button>
</nav>
