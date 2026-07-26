@props(['icon', 'value', 'label', 'color' => 'gold'])

<div class="stat-box">
    <div class="stat-icon" style="background: rgba(var(--{{ $color }}-rgb, 207, 164, 111), 0.1); color: var(--{{ $color }}, #cfa46f);">
        <i class="{{ $icon }}"></i>
    </div>
    <div class="adm-stat-val">{{ $value }}</div>
    <div class="adm-stat-lbl">{{ $label }}</div>
</div>
