@props([
    'label' => null,
    'name',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'helper' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'iconLeading' => null,
    'iconTrailing' => null,
])

@php
    $inputId = $id ?? $name;
    $errorId = "{$inputId}-error";
    $helperId = "{$inputId}-helper";
    $hasError = !empty($error) || $errors->has($name);
    $errorMessage = $error ?? $errors->first($name);
    $leadIcon = $iconLeading ? (str_starts_with($iconLeading, 'bi-') ? 'bi ' . $iconLeading : $iconLeading) : null;
    $trailIcon = $iconTrailing ? (str_starts_with($iconTrailing, 'bi-') ? 'bi ' . $iconTrailing : $iconTrailing) : null;
@endphp

<div class="ds-form-group" style="margin-bottom: var(--ds-space-md, 16px); width: 100%;">
    @if($label)
        <label for="{{ $inputId }}" class="ds-label" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; font-size: 0.76rem; font-weight: 700; color: var(--ds-text-secondary, #D1C5B4); text-transform: uppercase; letter-spacing: 0.05em;">
            <span>
                {{ $label }}
                @if($required)
                    <span style="color: var(--ds-danger-text, #f87171); font-weight: 800; margin-left: 2px;" aria-hidden="true">*</span>
                @endif
            </span>
            @if(!$required)
                <span style="font-size: 0.7rem; font-weight: 400; text-transform: none; color: var(--ds-text-muted, #A39683);">Optional</span>
            @endif
        </label>
    @endif

    <div style="position: relative; border-radius: var(--ds-radius-sm, 8px);">
        @if($leadIcon)
            <div style="position: absolute; top: 0; bottom: 0; left: 0; padding-left: 12px; display: flex; align-items: center; pointer-events: none; color: var(--ds-text-muted, #A39683); font-size: 0.95rem;">
                <i class="{{ $leadIcon }}" aria-hidden="true"></i>
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $inputId }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if($hasError)
                aria-invalid="true"
                aria-describedby="{{ $errorId }}"
            @elseif($helper)
                aria-describedby="{{ $helperId }}"
            @endif
            class="ds-input {{ $hasError ? 'is-invalid' : '' }}"
            style="{{ $leadIcon ? 'padding-left: 38px;' : '' }} {{ ($trailIcon || $hasError) ? 'padding-right: 38px;' : '' }}"
            {{ $attributes }}
        />

        @if($hasError || $trailIcon)
            <div style="position: absolute; top: 0; bottom: 0; right: 0; padding-right: 12px; display: flex; align-items: center; pointer-events: none; font-size: 0.95rem;">
                @if($hasError)
                    <i class="bi bi-exclamation-circle-fill" style="color: var(--ds-danger-text, #f87171);" aria-hidden="true"></i>
                @elseif($trailIcon)
                    <i class="{{ $trailIcon }}" style="color: var(--ds-text-muted, #A39683);" aria-hidden="true"></i>
                @endif
            </div>
        @endif
    </div>

    @if($hasError)
        <p id="{{ $errorId }}" class="ds-invalid-feedback" role="alert" style="display: flex; align-items: center; gap: 4px; margin-top: 5px; font-size: 0.78rem; color: var(--ds-danger-text, #f87171); font-weight: 600;">
            <i class="bi bi-x-circle-fill" style="font-size: 0.9em;" aria-hidden="true"></i>
            {{ $errorMessage }}
        </p>
    @elseif($helper)
        <p id="{{ $helperId }}" style="margin: 5px 0 0; font-size: 0.76rem; color: var(--ds-text-muted, #A39683); line-height: 1.4;">
            {{ $helper }}
        </p>
    @endif
</div>
