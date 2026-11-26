<div class="sidebar-head admin-sidebar-brand">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="School logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
    </div>
</div>
<div class="sidebar-divider"></div>
<div class="sidebar-nav">
    @include('partials.admin-workspace-navigation', ['navigationId' => 'adminSidebar'])
</div>
