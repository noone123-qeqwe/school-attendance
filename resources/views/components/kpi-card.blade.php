@props([
    'label',
    'value',
    'delta' => null,           // e.g. "+12.5%" or "-3"
    'deltaType' => 'positive', // positive | negative | neutral
    'deltaText' => 'vs last week',
    'icon' => null,
    'accent' => 'gold',        // gold | maroon | success | danger | info
    'loading' => false,
])

@php
    $accentStyles = [
        'gold'    => 'background: var(--ds-gold-glow, rgba(212, 175, 55, 0.15)); color: var(--ds-gold, #D4AF37); border: 1px solid var(--ds-border, rgba(212, 175, 55, 0.3));',
        'maroon'  => 'background: var(--ds-maroon-alpha, rgba(144, 0, 0, 0.15)); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.25);',
        'success' => 'background: var(--ds-success-alpha, rgba(74, 222, 128, 0.15)); color: var(--ds-success-text, #4ade80); border: 1px solid var(--ds-success-border, rgba(74, 222, 128, 0.25));',
        'danger'  => 'background: var(--ds-danger-alpha, rgba(248, 113, 113, 0.15)); color: var(--ds-danger-text, #f87171); border: 1px solid var(--ds-danger-border, rgba(248, 113, 113, 0.25));',
        'info'    => 'background: var(--ds-info-alpha, rgba(96, 165, 250, 0.15)); color: var(--ds-info-text, #60a5fa); border: 1px solid var(--ds-info-border, rgba(96, 165, 250, 0.25));',
    ];

    $deltaColors = [
        'positive' => 'color: var(--ds-success-text, #4ade80); background: var(--ds-success-alpha, rgba(74, 222, 128, 0.15)); border: 1px solid var(--ds-success-border, rgba(74, 222, 128, 0.25));',
        'negative' => 'color: var(--ds-danger-text, #f87171); background: var(--ds-danger-alpha, rgba(248, 113, 113, 0.15)); border: 1px solid var(--ds-danger-border, rgba(248, 113, 113, 0.25));',
        'neutral'  => 'color: var(--ds-text-muted, #A39683); background: rgba(255, 255, 255, 0.05); border: 1px solid var(--ds-border-subtle, rgba(255, 255, 255, 0.08));',
    ];

    $deltaIcons = [
        'positive' => 'bi-arrow-up-right',
        'negative' => 'bi-arrow-down-right',
        'neutral'  => 'bi-dash',
    ];

    $cleanIcon = $icon ? (str_starts_with($icon, 'bi-') ? 'bi ' . $icon : $icon) : null;
@endphp

<div {{ $attributes->merge(['class' => 'ent-kpi-card ent-kpi-summary-card']) }} style="position: relative; background: linear-gradient(145deg, rgba(30, 21, 21, 0.7) 0%, rgba(18, 10, 10, 0.85) 100%); border: 1px solid var(--ds-border, rgba(212, 175, 55, 0.16)); border-radius: var(--ds-radius-lg, 18px); padding: 22px; box-shadow: var(--ds-shadow-md, 0 8px 24px rgba(0,0,0,0.35)); overflow: hidden; backdrop-filter: blur(16px); transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);">
    <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: {{ $accent === 'success' ? 'var(--ds-success, #4ade80)' : ($accent === 'danger' ? 'var(--ds-danger, #f87171)' : ($accent === 'warning' ? 'var(--ds-warning, #fbbf24)' : ($accent === 'info' ? 'var(--ds-info, #60a5fa)' : 'var(--ds-gold, #D4AF37)'))) }}; opacity: 0.85;"></div>
    @if($loading)
        <div class="ent-kpi-summary-loading" style="display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="height: 14px; width: 45%; background: rgba(255,255,255,0.06); border-radius: 4px;"></div>
                <div style="width: 36px; height: 36px; background: rgba(255,255,255,0.06); border-radius: 10px;"></div>
            </div>
            <div style="height: 32px; width: 60%; background: rgba(255,255,255,0.08); border-radius: 6px;"></div>
            <div style="height: 12px; width: 40%; background: rgba(255,255,255,0.04); border-radius: 4px;"></div>
        </div>
    @else
        <div class="ent-kpi-summary-header">
            <span class="ent-kpi-summary-label">
                {{ $label }}
            </span>
            @if($cleanIcon)
                <div class="ent-kpi-summary-icon" style="{{ $accentStyles[$accent] ?? $accentStyles['gold'] }}">
                    <i class="{{ $cleanIcon }}" aria-hidden="true"></i>
                </div>
            @endif
        </div>

        <div class="ent-kpi-value ent-kpi-summary-value">{{ $value }}</div>

        @if($delta !== null)
            <div class="ent-kpi-summary-footer">
                <span class="ent-kpi-summary-delta" style="{{ $deltaColors[$deltaType] ?? $deltaColors['neutral'] }}">
                    <i class="bi {{ $deltaIcons[$deltaType] ?? 'bi-dash' }}" style="font-size: 0.9em;" aria-hidden="true"></i>
                    {{ $delta }}
                </span>
                <span class="ent-kpi-summary-delta-text">
                    {{ $deltaText }}
                </span>
            </div>
        @endif

        @if(trim((string)$slot) !== '')
            <div class="ent-kpi-summary-slot">
                {{ $slot }}
            </div>
        @endif
    @endif
</div>
