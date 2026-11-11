@php
    $adminGroup = \App\Support\AdminNavigation::currentGroup(auth()->user(), request()->route()?->getName());
@endphp
@if($adminGroup && !request()->routeIs('admin.dashboard'))
<div class="admin-page-context">
    <nav aria-label="Breadcrumb" class="admin-breadcrumb">
        <a href="{{ route('admin.dashboard') }}"><i class="bi bi-house-door" aria-hidden="true"></i> Home</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span>{{ $adminGroup['label'] }}</span>
    </nav>
    <details class="admin-section-switcher" data-admin-section-switcher>
        <summary><i class="bi {{ $adminGroup['icon'] }}" aria-hidden="true"></i> In {{ $adminGroup['label'] }} <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
        <nav aria-label="Related {{ $adminGroup['label'] }} pages">
            @foreach($adminGroup['items'] as [$label, $destination, $icon, $patterns])
                <a href="{{ route($destination) }}" @if(\App\Support\AdminNavigation::matches($patterns, request()->route()?->getName())) aria-current="page" @endif>
                    <i class="bi {{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>
    </details>
</div>
@endif
