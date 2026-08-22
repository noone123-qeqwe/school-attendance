@props([
    'type' => null,
    'variant' => null,
    'size' => 'md',     // sm | md | lg
    'pulse' => false,
    'icon' => null,
])

@php
    $key = strtolower((string)($variant ?? $type ?? 'info'));

    $styles = [
        'present' => 'background: var(--ds-success-alpha, rgba(74, 222, 128, 0.15)); color: var(--ds-success-text, #4ade80); border: 1px solid var(--ds-success-border, rgba(74, 222, 128, 0.25));',
        'success' => 'background: var(--ds-success-alpha, rgba(74, 222, 128, 0.15)); color: var(--ds-success-text, #4ade80); border: 1px solid var(--ds-success-border, rgba(74, 222, 128, 0.25));',
        'late'    => 'background: var(--ds-warning-alpha, rgba(251, 191, 36, 0.15)); color: var(--ds-warning-text, #fbbf24); border: 1px solid var(--ds-warning-border, rgba(251, 191, 36, 0.25));',
        'warning' => 'background: var(--ds-warning-alpha, rgba(251, 191, 36, 0.15)); color: var(--ds-warning-text, #fbbf24); border: 1px solid var(--ds-warning-border, rgba(251, 191, 36, 0.25));',
        'absent'  => 'background: var(--ds-danger-alpha, rgba(248, 113, 113, 0.15)); color: var(--ds-danger-text, #f87171); border: 1px solid var(--ds-danger-border, rgba(248, 113, 113, 0.25));',
        'danger'  => 'background: var(--ds-danger-alpha, rgba(248, 113, 113, 0.15)); color: var(--ds-danger-text, #f87171); border: 1px solid var(--ds-danger-border, rgba(248, 113, 113, 0.25));',
        'excused' => 'background: var(--ds-info-alpha, rgba(96, 165, 250, 0.15)); color: var(--ds-info-text, #60a5fa); border: 1px solid var(--ds-info-border, rgba(96, 165, 250, 0.25));',
        'info'    => 'background: var(--ds-info-alpha, rgba(96, 165, 250, 0.15)); color: var(--ds-info-text, #60a5fa); border: 1px solid var(--ds-info-border, rgba(96, 165, 250, 0.25));',
        'gold'    => 'background: var(--ds-gold-glow, rgba(212, 175, 55, 0.15)); color: var(--ds-gold-bright, #D4AF37); border: 1px solid var(--ds-border, rgba(212, 175, 55, 0.3));',
        'neutral' => 'background: rgba(255, 255, 255, 0.05); color: var(--ds-text-muted, #A39683); border: 1px solid var(--ds-border-subtle, rgba(255, 255, 255, 0.08));',
        'pending' => 'background: rgba(255, 255, 255, 0.05); color: var(--ds-text-muted, #A39683); border: 1px solid var(--ds-border-subtle, rgba(255, 255, 255, 0.08));',
        'default' => 'background: rgba(255, 255, 255, 0.05); color: var(--ds-text-muted, #A39683); border: 1px solid var(--ds-border-subtle, rgba(255, 255, 255, 0.08));',
    ];

    $pulseColors = [
        'present' => '#4ade80',
        'success' => '#4ade80',
        'late'    => '#fbbf24',
        'warning' => '#fbbf24',
        'absent'  => '#f87171',
        'danger'  => '#f87171',
        'excused' => '#60a5fa',
        'info'    => '#60a5fa',
        'gold'    => '#D4AF37',
        'neutral' => '#A39683',
        'pending' => '#A39683',
        'default' => '#A39683',
    ];

    $sizeStyles = [
        'sm' => 'padding: 2px 8px; font-size: 0.7rem; gap: 4px;',
        'md' => 'padding: 3px 10px; font-size: 0.75rem; gap: 5px;',
        'lg' => 'padding: 5px 14px; font-size: 0.84rem; gap: 6px;',
    ];

    $activeStyle = ($styles[$key] ?? $styles['info']) . ' ' . ($sizeStyles[$size] ?? $sizeStyles['md']);
    $pulseColor = $pulseColors[$key] ?? '#60a5fa';
@endphp

<span 
    class="ds-badge ds-badge-{{ $key }} {{ $attributes->get('class') }}" 
    style="display: inline-flex; align-items: center; border-radius: var(--ds-radius-pill, 9999px); font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; line-height: 1.5; white-space: nowrap; user-select: none; {{ $activeStyle }}"
    role="status"
>
    @if($pulse)
        <span style="position: relative; display: inline-flex; width: 6px; height: 6px; margin-right: 2px;" aria-hidden="true">
            <span style="position: absolute; width: 100%; height: 100%; border-radius: 50%; background: {{ $pulseColor }}; opacity: 0.75; animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;"></span>
            <span style="position: relative; display: inline-flex; border-radius: 50%; width: 6px; height: 6px; background: {{ $pulseColor }};"></span>
        </span>
    @endif

    @if($icon)
        <i class="{{ str_starts_with($icon, 'bi-') ? 'bi ' . $icon : $icon }}" style="font-size: 1.05em;" aria-hidden="true"></i>
    @endif

    <span>{{ $slot }}</span>
</span>
