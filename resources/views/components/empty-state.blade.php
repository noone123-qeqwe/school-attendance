@props([
    'icon' => 'inbox',
    'title' => 'No records found',
    'message' => null,
    'description' => null,
    'action' => null,
    'actionUrl' => null,
    'actionText' => 'Add New',
    'actionIcon' => 'bi-plus-lg',
    'secondaryText' => null,
    'secondaryUrl' => null,
])

@php
    $finalMessage = $message ?? $description ?? 'There are no items to display at the moment.';
    $cleanIcon = str_starts_with($icon, 'bi-') || str_starts_with($icon, 'bi ') ? $icon : 'bi bi-' . $icon;
    $cleanActionIcon = str_starts_with($actionIcon, 'bi-') || str_starts_with($actionIcon, 'bi ') ? $actionIcon : 'bi bi-' . $actionIcon;
@endphp

<section 
    class="empty-state ent-empty-state ds-empty-state {{ $attributes->get('class') }}" 
    style="text-align: center; padding: 48px 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; min-height: 280px; background: rgba(255,255,255,0.02); border: 1px dashed var(--ds-border, rgba(212, 175, 55, 0.2)); border-radius: var(--ds-radius-xl, 20px);"
    role="region"
    aria-label="{{ $title }}"
>
    <div style="width: 76px; height: 76px; border-radius: 50%; background: linear-gradient(135deg, var(--ds-gold-glow, rgba(212, 175, 55, 0.15)), rgba(212, 175, 55, 0.02)); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 18px; border: 1px solid var(--ds-border, rgba(212, 175, 55, 0.25)); box-shadow: 0 8px 24px rgba(0,0,0,0.35);">
        <i class="{{ $cleanIcon }}" style="font-size: 2.1rem; color: var(--ds-gold, #D4AF37); opacity: 0.85;" aria-hidden="true"></i>
    </div>

    <h4 style="color: var(--ds-text-primary, #FCF8F2); font-weight: 700; font-size: 1.15rem; margin: 0 0 8px; letter-spacing: -0.01em;">
        {{ $title }}
    </h4>

    @if($finalMessage)
        <p style="color: var(--ds-text-muted, #A39683); font-size: 0.88rem; max-width: 380px; margin: 0 auto 20px; line-height: 1.55;">
            {{ $finalMessage }}
        </p>
    @endif

    @if($actionUrl && $actionText)
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 10px;">
            <a href="{{ $actionUrl }}" class="ent-btn ent-btn-primary" style="background: linear-gradient(135deg, var(--ds-maroon, #7A1A1A) 0%, var(--ds-maroon-light, #9c2727) 100%); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: var(--ds-radius-md, 12px); padding: 10px 22px; font-weight: 600; font-size: 0.9rem; color: #fff; box-shadow: 0 4px 14px rgba(122,26,26,0.35); text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                <i class="{{ $cleanActionIcon }}" aria-hidden="true"></i>
                <span>{{ $actionText }}</span>
            </a>

            @if($secondaryText && $secondaryUrl)
                <a href="{{ $secondaryUrl }}" class="ent-btn ent-btn-secondary" style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--ds-border, rgba(212, 175, 55, 0.25)); border-radius: var(--ds-radius-md, 12px); padding: 10px 20px; font-weight: 600; font-size: 0.9rem; color: var(--ds-text-primary, #FCF8F2); text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <span>{{ $secondaryText }}</span>
                </a>
            @endif
        </div>
    @elseif($action)
        <div style="margin-top: 4px;">
            {{ $action }}
        </div>
    @endif

    @if(trim((string)$slot) !== '')
        <div style="margin-top: 14px;">
            {{ $slot }}
        </div>
    @endif
</section>
