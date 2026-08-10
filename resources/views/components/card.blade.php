@props(['title' => null, 'icon' => null, 'headerActions' => null, 'class' => '', 'type' => 'default', 'accent' => 'gold', 'label' => null, 'value' => null, 'trend' => null, 'trendDir' => 'up'])

@if($type === 'kpi')
    <div class="ent-kpi-card {{ $class }}" data-accent="{{ $accent }}">
        @if($icon)
            <div class="ent-kpi-icon"><i class="{{ $icon }}"></i></div>
        @endif
        <div class="ent-kpi-body">
            @if($label)
                <div class="ent-kpi-label">{{ $label }}</div>
            @endif
            @if($value !== null)
                <div class="ent-kpi-value">{{ $value }}</div>
            @endif
            @if($trend)
                <div class="ent-kpi-trend {{ $trendDir }}">
                    <i class="bi bi-arrow-{{ $trendDir }}-short"></i>
                    {{ $trend }}
                </div>
            @endif
            {{ $slot }}
        </div>
    </div>
@elseif($type === 'section')
    <div class="ent-section {{ $class }}">
        @if($title || $icon || $headerActions)
            <div class="ent-section-header">
                <div class="ent-section-title">
                    @if($icon)
                        <div class="ent-section-title-icon"><i class="{{ $icon }}"></i></div>
                    @endif
                    {{ $title }}
                </div>
                @if($headerActions)
                    <div>{{ $headerActions }}</div>
                @endif
            </div>
        @endif
        <div class="ent-section-body">
            {{ $slot }}
        </div>
    </div>
@else
    <div class="adm-card {{ $class }}">
        @if($title || $icon || $headerActions)
            <div class="adm-card-head">
                <div class="adm-card-title">
                    @if($icon)
                        <div class="adm-card-icon" style="background: rgba(212, 175, 55, 0.1); color: var(--gold);">
                            <i class="{{ $icon }}"></i>
                        </div>
                    @endif
                    {{ $title }}
                </div>
                @if($headerActions)
                    <div class="header-actions">
                        {{ $headerActions }}
                    </div>
                @endif
            </div>
        @endif
        
        <div style="padding: 24px;">
            {{ $slot }}
        </div>
    </div>
@endif
