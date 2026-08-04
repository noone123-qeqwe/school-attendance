@props(['type' => 'button', 'variant' => 'primary', 'icon' => null])

@php
    $variantClass = match($variant) {
        'primary' => 'btn-primary',
        'outline' => 'btn-outline',
        'danger' => 'btn-danger',
        default => 'btn-primary'
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'btn ' . $variantClass]) }}>
    @if($icon)
        <i class="{{ $icon }}"></i>
    @endif
    {{ $slot }}
</button>
