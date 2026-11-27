@props(['alertKey' => null, 'version' => null, 'required' => false, 'dismissLabel' => 'Dismiss alert', 'closeId' => null])

<div {{ $attributes }} data-alert-banner
     @if($alertKey !== null) data-alert-key="{{ $alertKey }}" @endif
     @if($version !== null) data-alert-version="{{ $version }}" @endif
     @if($required) data-alert-required @endif>
    {{ $slot }}
    @unless($required)
        <button type="button" class="alert-dismiss-button" data-alert-close aria-label="{{ $dismissLabel }}"
                @if($closeId) id="{{ $closeId }}" @endif>
            <span aria-hidden="true">×</span>
        </button>
    @endunless
</div>
