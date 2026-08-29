@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'headerActions' => null,
    'class' => '',
    'type' => 'default', // default | kpi | section | glass
    'accent' => 'gold',  // gold | maroon | success | danger | info
    'label' => null,
    'value' => null,
    'trend' => null,
    'trendDir' => 'up',
    'interactive' => false,
    'padding' => 'normal', // none | sm | normal | lg
])

@php
    $paddingStyles = [
        'none'   => 'padding: 0;',
        'sm'     => 'padding: 14px 18px;',
        'normal' => 'padding: 22px 24px;',
        'lg'     => 'padding: 28px 32px;',
    ];

    $cardPadding = $paddingStyles[$padding] ?? $paddingStyles['normal'];
    $cleanIcon = $icon ? (str_starts_with($icon, 'bi-') ? 'bi ' . $icon : $icon) : null;
@endphp

@if($type === 'kpi')
    <div class="ent-kpi-card {{ $class }}" data-accent="{{ $accent }}" style="position: relative; background: linear-gradient(145deg, rgba(30, 21, 21, 0.7) 0%, rgba(18, 10, 10, 0.85) 100%); border: 1px solid var(--ds-border, rgba(212, 175, 55, 0.16)); border-radius: var(--ds-radius-lg, 18px); padding: 20px; box-shadow: var(--ds-shadow-md, 0 8px 24px rgba(0,0,0,0.35)); overflow: hidden; transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: {{ $accent === 'success' ? 'var(--ds-success, #4ade80)' : ($accent === 'danger' ? 'var(--ds-danger, #f87171)' : ($accent === 'warning' ? 'var(--ds-warning, #fbbf24)' : ($accent === 'info' ? 'var(--ds-info, #60a5fa)' : 'var(--ds-gold, #D4AF37)'))) }}; opacity: 0.85;"></div>
        @if($icon)
            <div class="ent-kpi-icon" style="width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: rgba(212, 175, 55, 0.1); color: var(--ds-gold, #D4AF37); font-size: 1.3rem; margin-bottom: 12px; border: 1px solid rgba(212, 175, 55, 0.2);">
                <i class="{{ $cleanIcon }}"></i>
            </div>
        @endif
        <div class="ent-kpi-body">
            @if($label)
                <div class="ent-kpi-label" style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ds-text-secondary, #D1C5B4); margin-bottom: 4px;">{{ $label }}</div>
            @endif
            @if($value !== null)
                <div class="ent-kpi-value" style="font-size: 1.85rem; font-weight: 800; color: #ffffff; line-height: 1.1; letter-spacing: -0.5px;">{{ $value }}</div>
            @endif
            @if($trend)
                <div class="ent-kpi-trend {{ $trendDir }}" style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; font-weight: 600; margin-top: 8px; color: {{ $trendDir === 'up' ? 'var(--ds-success, #4ade80)' : 'var(--ds-danger, #f87171)' }};">
                    <i class="bi bi-arrow-{{ $trendDir }}-short" style="font-size: 1.1em;"></i>
                    {{ $trend }}
                </div>
            @endif
            {{ $slot }}
        </div>
    </div>
@elseif($type === 'section')
    <section class="ent-section {{ $class }}" style="position: relative; background: rgba(24, 15, 15, 0.75); border: 1px solid var(--ds-border, rgba(212, 175, 55, 0.16)); border-radius: var(--ds-radius-xl, 20px); box-shadow: var(--ds-shadow-lg, 0 10px 30px rgba(0,0,0,0.4)); overflow: hidden; backdrop-filter: blur(16px);">
        @if($title || $icon || $headerActions)
            <header class="ent-section-header" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 24px; border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(0,0,0,0.2);">
                <div class="ent-section-title" style="display: flex; align-items: center; gap: 10px; font-size: 1.08rem; font-weight: 700; color: #ffffff; letter-spacing: -0.01em;">
                    @if($icon)
                        <div class="ent-section-title-icon" style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: rgba(212, 175, 55, 0.12); color: var(--ds-gold, #D4AF37); border: 1px solid rgba(212, 175, 55, 0.2);">
                            <i class="{{ $cleanIcon }}"></i>
                        </div>
                    @endif
                    <div>
                        <span>{{ $title }}</span>
                        @if($subtitle)
                            <p style="margin: 2px 0 0; font-size: 0.78rem; font-weight: 400; color: var(--ds-text-muted, #A39683);">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
                @if($headerActions)
                    <div style="flex-shrink: 0;">{{ $headerActions }}</div>
                @endif
            </header>
        @endif
        <div class="ent-section-body" style="{{ $cardPadding }}">
            {{ $slot }}
        </div>
    </section>
@else
    <article {{ $attributes->merge(['class' => 'adm-card ' . $class]) }} style="position: relative; background: linear-gradient(145deg, rgba(30, 21, 21, 0.65) 0%, rgba(16, 9, 9, 0.85) 100%); border: 1px solid var(--ds-border, rgba(212, 175, 55, 0.15)); border-radius: var(--ds-radius-lg, 18px); box-shadow: var(--ds-shadow-md, 0 8px 24px rgba(0,0,0,0.35)); overflow: hidden; backdrop-filter: blur(16px); {{ $interactive ? 'cursor: pointer; transition: transform 0.25s ease, border-color 0.25s ease;' : '' }}">
        @if($title || $icon || $headerActions || isset($header))
            <header class="adm-card-head" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 24px; border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(0,0,0,0.15);">
                @if(isset($header))
                    {{ $header }}
                @else
                    <div class="adm-card-title" style="display: flex; align-items: center; gap: 10px; font-size: 1.05rem; font-weight: 700; color: #ffffff;">
                        @if($icon)
                            <div class="adm-card-icon" style="display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; background: rgba(212, 175, 55, 0.12); color: var(--ds-gold, #D4AF37); border: 1px solid rgba(212, 175, 55, 0.2);">
                                <i class="{{ $cleanIcon }}"></i>
                            </div>
                        @endif
                        <div>
                            <span>{{ $title }}</span>
                            @if($subtitle)
                                <p style="margin: 2px 0 0; font-size: 0.78rem; font-weight: 400; color: var(--ds-text-muted, #A39683);">{{ $subtitle }}</p>
                            @endif
                        </div>
                    </div>
                @endif
                @if($headerActions || isset($actions))
                    <div class="header-actions" style="flex-shrink: 0; display: flex; align-items: center; gap: 8px;">
                        {{ $headerActions ?? $actions }}
                    </div>
                @endif
            </header>
        @endif
        
        <div style="{{ $cardPadding }}">
            {{ $slot }}
        </div>

        @if(isset($footer))
            <footer style="padding: 14px 24px; border-top: 1px solid rgba(255,255,255,0.06); font-size: 0.8rem; color: var(--ds-text-muted, #A39683); background: rgba(0,0,0,0.1);">
                {{ $footer }}
            </footer>
        @endif
    </article>
@endif
