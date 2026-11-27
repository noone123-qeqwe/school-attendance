<div class="sidebar-head student-sidebar-brand">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="School logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<nav class="sidebar-nav student-sidebar-nav" aria-label="Student navigation">
    @foreach(\App\Support\StudentNavigation::sidebarItems(!empty($hasActiveAssistantAssignment)) as $item)
        @php($isActive = request()->routeIs(...$item['active']))
        <a href="{{ route($item['route']) }}" class="nav-link {{ $isActive ? 'active' : '' }}" @if($isActive) aria-current="page" @endif title="{{ $item['label'] }}" data-title="{{ $item['label'] }}" aria-label="{{ $item['label'] }}">
            <i class="bi bi-{{ $item['icon'] }}" aria-hidden="true"></i>
            <span class="nav-link-text">{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
