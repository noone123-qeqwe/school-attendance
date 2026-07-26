@props(['title' => null, 'icon' => null, 'headerActions' => null, 'class' => ''])

<div class="panel-section {{ $class }}">
    @if($title || $icon || $headerActions)
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="panel-title mb-0">
                @if($icon)
                    <i class="{{ $icon }} me-2"></i>
                @endif
                {{ $title }}
            </h5>
            @if($headerActions)
                <div class="header-actions">
                    {{ $headerActions }}
                </div>
            @endif
        </div>
    @endif
    
    {{ $slot }}
</div>
