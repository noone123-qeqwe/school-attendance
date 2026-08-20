@props([
    'icon' => 'inbox',
    'title' => 'No records found',
    'message' => null,
    'description' => null,
    'action' => null,
    'actionUrl' => null,
    'actionText' => 'Add New'
])

@php
    $finalMessage = $message ?? $description ?? 'There are no items to display at the moment.';
    $cleanIcon = str_starts_with($icon, 'bi-') || str_starts_with($icon, 'bi ') ? $icon : 'bi bi-' . $icon;
@endphp

<div class="empty-state ent-empty-state {{ $attributes->get('class') }}" style="text-align:center; padding:56px 24px; display:flex; flex-direction:column; align-items:center; justify-content:center; width:100%; min-height:280px; background:rgba(255,255,255,0.02); border:1px dashed rgba(207,164,111,0.18); border-radius:20px;">
    <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, rgba(207,164,111,0.12), rgba(207,164,111,0.02)); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; border: 1px solid rgba(207,164,111,0.2); box-shadow: 0 8px 24px rgba(0,0,0,0.3);">
        <i class="{{ $cleanIcon }}" style="font-size: 2.2rem; color: #cfa46f; opacity: 0.75;"></i>
    </div>
    <h4 style="color: #f8e7d3; font-weight: 700; font-size: 1.15rem; margin-bottom: 8px; letter-spacing: -0.01em;">
        {{ $title }}
    </h4>
    @if($finalMessage)
        <p style="color: #8f826f; font-size: 0.9rem; max-width: 340px; margin: 0 auto 20px; line-height: 1.55;">
            {{ $finalMessage }}
        </p>
    @endif

    @if(isset($actionUrl) && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-primary" style="background: linear-gradient(135deg, #7A1A1A, #9c2727); border: none; border-radius: 12px; padding: 10px 24px; font-weight: 600; font-size: 0.9rem; color: #fff; box-shadow: 0 4px 14px rgba(122,26,26,0.35); display: inline-flex; align-items: center; gap: 8px;">
            <i class="bi bi-plus-lg"></i> {{ $actionText }}
        </a>
    @elseif($action)
        <div>
            {{ $action }}
        </div>
    @endif
</div>
