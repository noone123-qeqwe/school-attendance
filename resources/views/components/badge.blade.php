@props(['type' => 'info'])

@php
    $classes = [
        'present' => 'badge-success',
        'success' => 'badge-success',
        'late' => 'badge-warning',
        'warning' => 'badge-warning',
        'absent' => 'badge-danger',
        'danger' => 'badge-danger',
        'excused' => 'badge-info',
        'info' => 'badge-info',
        'pending' => 'badge-warning'
    ];
    
    $badgeClass = $classes[$type] ?? 'badge-info';
@endphp

<span class="badge {{ $badgeClass }} {{ $attributes->get('class') }}">
    {{ $slot }}
</span>
