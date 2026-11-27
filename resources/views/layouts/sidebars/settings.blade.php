@php
    $settingsUser = $settingsUser ?? Auth::user();
    $isStudentUser = (bool) $settingsUser?->isStudent();
    $contentId = $settingsUser?->isTeacher() ? 'teacherSettingsContent' : 'studentSettingsContent';
    $dashboardUrl = \App\Support\SettingsNavigation::dashboardUrl($settingsUser);
    $settingsGroups = \App\Support\SettingsNavigation::groups($settingsUser);
@endphp

<div class="sidebar-head {{ $isStudentUser ? 'student-sidebar-brand settings-sidebar-brand' : 'settings-sidebar-brand' }}">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="School logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<nav class="sidebar-nav {{ $isStudentUser ? 'student-sidebar-nav ' : '' }}settings-sidebar-nav" aria-label="Settings navigation">
    @foreach($settingsGroups as $groupLabel => $items)
        <div class="{{ $isStudentUser ? 'student-nav-label ' : '' }}nav-section-label">{{ $groupLabel }}</div>
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
