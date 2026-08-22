@props([
    'variant' => 'primary', // primary (maroon) | gold | secondary | outline | danger | ghost | success
    'size' => 'md',         // sm | md | lg | icon
    'type' => 'button',
    'href' => null,
    'loading' => false,
    'disabled' => false,
    'icon' => null,
    'iconLeading' => null,
    'iconTrailing' => null,
    'loadingText' => null,
])

@php
    $baseStyles = "display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-family: inherit; text-decoration: none; border-radius: var(--ds-radius-md, 12px); cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); user-select: none; border: 1px solid transparent; box-sizing: border-box; line-height: 1.4;";

    $variants = [
        'primary'   => 'background: linear-gradient(135deg, var(--ds-maroon, #7A1A1A) 0%, var(--ds-maroon-light, #9c2727) 100%); color: #ffffff; border-color: rgba(255, 255, 255, 0.15); box-shadow: 0 4px 14px rgba(122, 26, 26, 0.35);',
        'gold'      => 'background: linear-gradient(135deg, var(--ds-gold, #D4AF37) 0%, var(--ds-gold-dark, #A68822) 100%); color: #110A0A; border-color: rgba(212, 175, 55, 0.3); font-weight: 700; box-shadow: 0 4px 15px rgba(212, 175, 55, 0.25);',
        'secondary' => 'background: rgba(255, 255, 255, 0.05); color: var(--ds-text-primary, #FCF8F2); border-color: rgba(212, 175, 55, 0.25); backdrop-filter: blur(8px);',
        'outline'   => 'background: transparent; color: var(--ds-gold-soft, #F9E596); border-color: rgba(212, 175, 55, 0.35);',
        'danger'    => 'background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: #ffffff; border-color: rgba(239, 68, 68, 0.3); box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);',
        'success'   => 'background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: #ffffff; border-color: rgba(34, 197, 94, 0.3); box-shadow: 0 4px 14px rgba(22, 163, 74, 0.3);',
        'ghost'     => 'background: transparent; color: var(--ds-text-muted, #A39683); border-color: transparent;',
    ];

    $sizes = [
        'sm'   => 'padding: 6px 14px; font-size: 0.82rem; gap: 6px;',
        'md'   => 'padding: 10px 20px; font-size: 0.92rem; gap: 8px;',
        'lg'   => 'padding: 14px 28px; font-size: 1.05rem; gap: 10px;',
        'icon' => 'padding: 10px; width: 40px; height: 40px; justify-content: center;',
    ];

    $activeStyle = ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
    $isDisabled = $disabled || $loading;
    $leadIcon = $iconLeading ?? $icon;
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if($href)
        href="{{ $isDisabled ? '#' : $href }}"
        role="button"
        @if($isDisabled) aria-disabled="true" tabindex="-1" style="{{ $baseStyles }} {{ $activeStyle }} opacity: 0.55; pointer-events: none;" @else style="{{ $baseStyles }} {{ $activeStyle }}" @endif
    @else
        type="{{ $type }}"
        @if($isDisabled) disabled style="{{ $baseStyles }} {{ $activeStyle }} opacity: 0.55; cursor: not-allowed;" @else style="{{ $baseStyles }} {{ $activeStyle }}" @endif
    @endif
    {{ $attributes->merge(['class' => 'ent-btn ent-btn-' . $variant . ($size !== 'md' ? ' ent-btn-' . $size : '')]) }}
    @if($loading) aria-busy="true" @endif
>
    @if($loading)
        <svg style="width: 16px; height: 16px; animation: spin 1s linear infinite; flex-shrink: 0;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>{{ $loadingText ?? $slot }}</span>
    @else
        @if($leadIcon)
            <i class="{{ str_starts_with($leadIcon, 'bi-') ? 'bi ' . $leadIcon : $leadIcon }}" style="font-size: 1.05em; flex-shrink: 0;" aria-hidden="true"></i>
        @endif

        @if(trim((string)$slot) !== '')
            <span>{{ $slot }}</span>
        @endif

        @if($iconTrailing)
            <i class="{{ str_starts_with($iconTrailing, 'bi-') ? 'bi ' . $iconTrailing : $iconTrailing }}" style="font-size: 1.05em; flex-shrink: 0;" aria-hidden="true"></i>
        @endif
    @endif
</{{ $tag }}>
