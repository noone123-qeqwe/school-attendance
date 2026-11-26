<div class="sidebar-head teacher-sidebar-brand">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="School logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
    </div>
</div>
<div class="sidebar-divider"></div>

<nav class="sidebar-nav teacher-sidebar-nav" aria-label="Teacher navigation">
    @include('partials.teacher-navigation')
</nav>
