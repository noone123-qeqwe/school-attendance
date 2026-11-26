@props([
    'href',
    'label' => 'Back',
    'icon' => 'bi-arrow-left',
    'variant' => 'default', // default | admin | saas | teacher | teacher-ghost | entity | plain
    'hideOnMobile' => true,
])

@php
    $variantClasses = match ($variant) {
        'admin' => 'adm-btn adm-btn-ghost',
        'saas' => 'saas-btn saas-btn-secondary',
        'teacher' => 'tch-btn tch-btn-secondary',
        'teacher-ghost' => 'tch-btn tch-btn-ghost',
        'entity' => 'ent-btn ent-btn-secondary',
        'plain' => '',
        default => 'adm-btn adm-btn-ghost',
    };

    $cleanIcon = str_starts_with($icon, 'bi-') ? 'bi ' . $icon : $icon;
    $responsiveClass = $hideOnMobile ? 'd-none d-md-inline-flex' : 'd-inline-flex';
    $baseClass = trim($variantClasses . ' ' . $responsiveClass . ' ui-back-link');
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClass]) }}>
    <i class="{{ $cleanIcon }}" aria-hidden="true"></i>
    <span>{{ $label }}</span>
</a>
