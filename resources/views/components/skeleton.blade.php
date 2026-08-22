@props([
    'type' => 'line', // line | avatar | badge | card | chart | table-row | kpi | stat
    'count' => 1,
    'width' => '100%',
    'height' => null,
])

<div class="skel-container" style="display: flex; flex-direction: column; gap: 10px; width: 100%;" aria-hidden="true">
    @for($i = 0; $i < $count; $i++)
        @switch($type)
            @case('avatar')
                <div class="skel-shimmer {{ $attributes->get('class', '') }}" style="width: {{ $height ?? '40px' }}; height: {{ $height ?? '40px' }}; border-radius: var(--ds-radius-md, 12px); flex-shrink: 0; background: rgba(255,255,255,0.06);"></div>
                @break

            @case('badge')
                <div class="skel-shimmer {{ $attributes->get('class', '') }}" style="width: {{ $width !== '100%' ? $width : '64px' }}; height: {{ $height ?? '24px' }}; border-radius: var(--ds-radius-pill, 9999px); background: rgba(255,255,255,0.06);"></div>
                @break

            @case('kpi')
                <div class="skel-kpi {{ $attributes->get('class', '') }}" style="display: flex; align-items: center; gap: 14px; padding: 20px; border-radius: var(--ds-radius-lg, 16px); background: var(--ds-surface, #1E1515); border: 1px solid var(--ds-border, rgba(212, 175, 55, 0.15));">
                    <div class="skel-shimmer" style="width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0; background: rgba(255,255,255,0.06);"></div>
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                        <div class="skel-shimmer" style="width: 55%; height: 12px; border-radius: 4px; background: rgba(255,255,255,0.06);"></div>
                        <div class="skel-shimmer" style="width: 40%; height: 26px; border-radius: 6px; background: rgba(255,255,255,0.08);"></div>
                    </div>
                </div>
                @break

            @case('card')
                <div class="skel-card {{ $attributes->get('class', '') }}" style="padding: 24px; border-radius: var(--ds-radius-lg, 16px); background: var(--ds-surface, #1E1515); border: 1px solid var(--ds-border, rgba(212, 175, 55, 0.15)); display: flex; flex-direction: column; gap: 12px;">
                    <div class="skel-shimmer" style="width: 45%; height: 16px; border-radius: 4px; background: rgba(255,255,255,0.07);"></div>
                    <div class="skel-shimmer" style="width: 100%; height: 10px; border-radius: 3px; background: rgba(255,255,255,0.05);"></div>
                    <div class="skel-shimmer" style="width: 85%; height: 10px; border-radius: 3px; background: rgba(255,255,255,0.05);"></div>
                    <div class="skel-shimmer" style="width: 60%; height: 10px; border-radius: 3px; background: rgba(255,255,255,0.05);"></div>
                </div>
                @break

            @case('chart')
                <div class="skel-chart {{ $attributes->get('class', '') }}" style="height: {{ $height ?? '240px' }}; border-radius: var(--ds-radius-lg, 16px); background: var(--ds-surface, #1E1515); border: 1px solid var(--ds-border, rgba(212, 175, 55, 0.15)); padding: 20px; display: flex; align-items: flex-end; gap: 10px;">
                    @for($b = 0; $b < 8; $b++)
                        <div class="skel-shimmer" style="flex: 1; height: {{ [35, 60, 45, 80, 50, 75, 40, 90][$b] }}%; border-radius: 6px 6px 0 0; background: rgba(255,255,255,0.06);"></div>
                    @endfor
                </div>
                @break

            @case('table-row')
                <div class="skel-table-row {{ $attributes->get('class', '') }}" style="display: flex; align-items: center; gap: 14px; padding: 14px 16px; border-bottom: 1px solid var(--ds-border-subtle, rgba(255,255,255,0.08));">
                    <div class="skel-shimmer" style="width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0; background: rgba(255,255,255,0.06);"></div>
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                        <div class="skel-shimmer" style="width: {{ 40 + (($i * 17) % 35) }}%; height: 12px; border-radius: 4px; background: rgba(255,255,255,0.07);"></div>
                        <div class="skel-shimmer" style="width: {{ 25 + (($i * 13) % 25) }}%; height: 10px; border-radius: 3px; background: rgba(255,255,255,0.05);"></div>
                    </div>
                    <div class="skel-shimmer" style="width: 70px; height: 24px; border-radius: var(--ds-radius-pill, 9999px); flex-shrink: 0; background: rgba(255,255,255,0.06);"></div>
                </div>
                @break

            @default
                <div class="skel-shimmer {{ $attributes->get('class', '') }}" style="width: {{ $width }}; height: {{ $height ?? '14px' }}; border-radius: 4px; background: rgba(255,255,255,0.06);"></div>
        @endswitch
    @endfor
</div>
