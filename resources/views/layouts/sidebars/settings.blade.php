@php
    $settingsUser = $settingsUser ?? Auth::user();
    $isStudentUser = (bool) $settingsUser?->isStudent();
    $contentId = $settingsUser?->isTeacher() ? 'teacherSettingsContent' : 'studentSettingsContent';
    $dashboardUrl = \App\Support\SettingsNavigation::dashboardUrl($settingsUser);
    $settingsGroups = \App\Support\SettingsNavigation::groups($settingsUser);
@endphp

@if($isStudentUser)
    @php
        $sfSettingsIcons = [
            'profile' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>',
            'family' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'credentials' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
            'biometrics' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4"/><path d="M14 13.12c0 2.38 0 6.38-1 8.88"/><path d="M17.29 21.02c.12-.6.43-2.3.5-3.02"/><path d="M2 12a10 10 0 0 1 18-6"/><path d="M2 16h.01"/><path d="M21.8 16c.2-2 .131-5.354 0-6"/><path d="M5 19.5C5.5 18 6 15 6 12a6 6 0 0 1 .34-2"/><path d="M8.65 22c.21-.66.45-1.32.57-2"/><path d="M9 6.8a6 6 0 0 1 9 5.2v2"/></svg>',
            'device' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>',
            'recovery' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>',
            'preferences' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/></svg>',
        ];
    @endphp

    <div class="sf-sidebar-head">
        <a href="{{ $dashboardUrl }}" class="sf-brand" aria-label="{{ config('app.name') }} home">
            <img src="{{ asset('images/logo.png') }}" class="sf-brand-logo" alt="School logo">
            <span class="sf-brand-text">
                <span class="sf-brand-title">{{ config('app.name') }}</span>
                <span class="sf-brand-sub">Settings</span>
            </span>
        </a>
        <button type="button" class="sf-sidebar-toggle" id="burgerBtn" onclick="window.toggleSidebar?.()" aria-controls="sidebar" aria-expanded="true" aria-label="Collapse sidebar" title="Toggle sidebar">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m16 15-3-3 3-3"/></svg>
        </button>
    </div>

    <div class="sf-sidebar-back-wrap">
        <a href="{{ $dashboardUrl }}"
           class="sf-sidebar-back"
           id="settingsBackToDashboardLink"
           title="Back to Dashboard"
           data-title="Back to Dashboard"
           aria-label="Back to Dashboard">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            <span>Back to app</span>
        </a>
    </div>

    <nav class="sf-sidebar-nav settings-sidebar-nav" aria-label="Settings navigation">
        @foreach($settingsGroups as $groupLabel => $items)
            <div class="sf-nav-section">{{ $groupLabel }}</div>
            @foreach($items as $item)
                @php $isInitialActive = $item['destination'] === 'profile'; @endphp
                <button type="button"
                        class="sf-nav-item nav-link settings-sidebar-item {{ $isInitialActive ? 'active' : '' }}"
                        data-settings-page="{{ $item['destination'] }}"
                        data-title="{{ $item['label'] }}"
                        title="{{ $item['label'] }}"
                        aria-label="{{ $item['label'] }}"
                        aria-controls="{{ $contentId }}"
                        @if($isInitialActive) aria-current="page" @endif>
                    {!! $sfSettingsIcons[$item['destination']] ?? '' !!}
                    <span class="sf-nav-label nav-link-text">{{ $item['label'] }}</span>
                </button>
            @endforeach
        @endforeach
    </nav>
@else
    <div class="sidebar-head settings-sidebar-brand">
        <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="School logo">
        <div class="sidebar-text">
            <div class="sidebar-brand">{{ config('app.name') }}</div>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <nav class="sidebar-nav settings-sidebar-nav" aria-label="Settings navigation">
        @foreach($settingsGroups as $groupLabel => $items)
            <div class="nav-section-label">{{ $groupLabel }}</div>
            @foreach($items as $item)
                @php
                    $isInitialActive = $item['destination'] === 'profile';
                @endphp
                <button type="button"
                        class="nav-link settings-sidebar-item {{ $isInitialActive ? 'active' : '' }}"
                        data-settings-page="{{ $item['destination'] }}"
                        data-title="{{ $item['label'] }}"
                        title="{{ $item['label'] }}"
                        aria-label="{{ $item['label'] }}"
                        aria-controls="{{ $contentId }}"
                        @if($isInitialActive) aria-current="page" @endif>
                    <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                    <span class="nav-link-text">{{ $item['label'] }}</span>
                </button>
            @endforeach
        @endforeach
    </nav>

    <div class="settings-sidebar-back-wrap">
        <a href="{{ $dashboardUrl }}"
           class="nav-link settings-sidebar-back-btn"
           id="settingsBackToDashboardLink"
           title="Back to Dashboard"
           data-title="Back to Dashboard"
           aria-label="Back to Dashboard">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="nav-link-text">Back to Dashboard</span>
        </a>
    </div>
@endif
