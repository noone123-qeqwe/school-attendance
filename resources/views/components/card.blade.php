@props(['title' => null, 'icon' => null, 'headerActions' => null, 'class' => ''])

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
