<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="School logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">Administration</div>
    </div>
</div>
<div class="admin-workspace-role sidebar-text">
    <i class="bi bi-shield-check" aria-hidden="true"></i>
    {{ match(auth()->user()->admin_sub_role) { 'super_admin' => 'Super administrator', 'auditor' => 'Read-only auditor', 'data_entry' => 'Data entry', default => 'Administrator' } }}
</div>
<div class="sidebar-nav">
    @include('partials.admin-workspace-navigation', ['navigationId' => 'adminSidebar'])
</div>
