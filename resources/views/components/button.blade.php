@props(['type' => 'button', 'variant' => 'primary', 'icon' => null, 'size' => 'md'])

@php
    $variantClass = match($variant) {
        'primary'   => 'ent-btn ent-btn-primary',
        'secondary' => 'ent-btn ent-btn-secondary',
        'outline'   => 'ent-btn ent-btn-secondary',
        'ghost'     => 'ent-btn ent-btn-ghost',
        'danger'    => 'ent-btn ent-btn-danger',
        'success'   => 'ent-btn ent-btn-success',
        default     => 'ent-btn ent-btn-primary',
    };

    $sizeClass = match($size) {
        'sm' => 'ent-btn-sm',
        'lg' => 'ent-btn-lg',
        default => '',
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $variantClass . ($sizeClass ? ' ' . $sizeClass : '')]) }}>
    @if($icon)
        <i class="{{ $icon }}"></i>
    @endif
    {{ $slot }}
</button>
