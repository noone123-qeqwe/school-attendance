@php
    $shell = $studentShellData ?? [
        'hasActiveAssistantAssignment' => (bool) ($hasActiveAssistantAssignment ?? false),
        'classesCount' => 0,
        'recordsCount' => 0,
        'excusesCount' => 0,
    ];
    $navIcons = [
        'home' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>',
        'classes' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
        'assistant' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/></svg>',
        'attendance' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/></svg>',
        'excuses' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>',
    ];
    $navBadges = [
        'classes' => $shell['classesCount'] ?? null,
        'attendance' => $shell['recordsCount'] ?? null,
        'excuses' => $shell['excusesCount'] ?? null,
    ];
@endphp

<div class="sf-sidebar-head">
    <a href="{{ route('home') }}" class="sf-brand" aria-label="{{ config('app.name') }} home">
        <img src="{{ asset('images/logo.png') }}" class="sf-brand-logo" alt="School logo">
        <span class="sf-brand-text">
            <span class="sf-brand-title">{{ config('app.name') }}</span>
            <span class="sf-brand-sub">Classroom Portal</span>
        </span>
    </a>
    <button type="button" class="sf-sidebar-toggle" id="burgerBtn" onclick="window.toggleSidebar?.()" aria-controls="sidebar" aria-expanded="true" aria-label="Collapse sidebar" title="Toggle sidebar">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m16 15-3-3 3-3"/></svg>
    </button>
</div>

<nav class="sf-sidebar-nav" aria-label="Student navigation">
    <div class="sf-nav-section">Workspace</div>
    @foreach(\App\Support\StudentNavigation::sidebarItems(!empty($hasActiveAssistantAssignment)) as $item)
        @php
            $isActive = request()->routeIs(...$item['active']);
            $badgeValue = $navBadges[$item['key']] ?? null;
        @endphp
        <a href="{{ route($item['route']) }}"
           class="sf-nav-item nav-link {{ $isActive ? 'active' : '' }}"
           @if($isActive) aria-current="page" @endif
           title="{{ $item['label'] }}"
           data-title="{{ $item['label'] }}"
           aria-label="{{ $item['label'] }}">
            {!! $navIcons[$item['key']] ?? '' !!}
            <span class="sf-nav-label nav-link-text">{{ $item['label'] }}</span>
            @if($badgeValue !== null)
                <span class="sf-nav-badge">{{ $badgeValue }}</span>
            @endif
        </a>
    @endforeach
</nav>
