<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="School logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">Parent &amp; guardian portal</div>
    </div>
</div>
<div class="sidebar-divider"></div>
<nav class="sidebar-nav" aria-label="Parent navigation">
    @include('partials.parent-navigation')
</nav>
