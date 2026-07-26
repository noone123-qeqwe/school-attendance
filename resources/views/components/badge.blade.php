@props(['type' => 'pending', 'dashboard' => false])

@php
    $classes = [
        'present' => 'badge-present',
        'late' => 'badge-late',
        'absent' => 'badge-absent',
        'excused' => 'badge-excused',
        'pending' => 'badge-pending'
    ];
    
    $badgeClass = $classes[$type] ?? 'badge-pending';
    $dashboardClass = $dashboard ? 'dashboard-badge' : '';
@endphp

<span class="status-badge {{ $badgeClass }} {{ $dashboardClass }} {{ $attributes->get('class') }}">
    {{ $slot }}
</span>
