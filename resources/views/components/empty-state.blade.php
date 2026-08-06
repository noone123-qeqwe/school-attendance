@props(['icon' => 'bi bi-inbox', 'title' => 'Nothing here yet', 'message' => '', 'action' => null])

<div class="ds-empty-state {{ $attributes->get('class') }}">
    <div class="ds-empty-state-icon">
        <i class="{{ $icon }}"></i>
    </div>
    <h4 class="ds-empty-state-title">{{ $title }}</h4>
    @if($message)
        <p class="ds-empty-state-text">{{ $message }}</p>
    @endif
    @if($action)
        <div style="margin-top: var(--ds-space-sm);">
            {{ $action }}
        </div>
    @endif
</div>
