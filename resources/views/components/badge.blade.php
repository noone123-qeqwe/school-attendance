@props(['type' => 'info'])

@php
    $classes = [
        'present' => 'ds-badge ds-badge-present',
        'success' => 'ds-badge ds-badge-success',
        'late'    => 'ds-badge ds-badge-late',
        'warning' => 'ds-badge ds-badge-warning',
        'absent'  => 'ds-badge ds-badge-absent',
        'danger'  => 'ds-badge ds-badge-danger',
        'excused' => 'ds-badge ds-badge-excused',
        'info'    => 'ds-badge ds-badge-info',
        'pending' => 'ds-badge ds-badge-pending',
        'default' => 'ds-badge ds-badge-default',
    ];
    
    $badgeClass = $classes[$type] ?? 'ds-badge ds-badge-info';
@endphp

<span class="{{ $badgeClass }} {{ $attributes->get('class') }}">
    {{ $slot }}
</span>
