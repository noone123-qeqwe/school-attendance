@props([
    'type' => 'line',
    'count' => 1,
    'width' => '100%',
    'height' => null,
])

@for($i = 0; $i < $count; $i++)
@switch($type)
    @case('kpi')
        <div class="skel-kpi {{ $attributes->get('class', '') }}">
            <div class="skel-shimmer skel-circle" style="width:48px;height:48px;border-radius:var(--ent-radius-md,12px);flex-shrink:0;"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:8px;">
                <div class="skel-shimmer skel-line" style="width:60%;height:12px;"></div>
                <div class="skel-shimmer skel-line" style="width:40%;height:24px;"></div>
            </div>
        </div>
        @break

    @case('card')
        <div class="skel-card {{ $attributes->get('class', '') }}">
            <div class="skel-shimmer skel-line" style="width:70%;height:14px;margin-bottom:12px;"></div>
            <div class="skel-shimmer skel-line" style="width:100%;height:10px;margin-bottom:8px;"></div>
            <div class="skel-shimmer skel-line" style="width:85%;height:10px;margin-bottom:8px;"></div>
            <div class="skel-shimmer skel-line" style="width:50%;height:10px;"></div>
        </div>
        @break

    @case('chart')
        <div class="skel-chart {{ $attributes->get('class', '') }}" style="height:{{ $height ?? '260px' }}">
            <div style="display:flex;align-items:flex-end;gap:8px;height:100%;padding:20px;">
                @for($b = 0; $b < 7; $b++)
                <div class="skel-shimmer" style="flex:1;height:{{ rand(30,90) }}%;border-radius:6px 6px 0 0;"></div>
                @endfor
            </div>
        </div>
        @break

    @case('table-row')
        <div class="skel-table-row {{ $attributes->get('class', '') }}">
            <div class="skel-shimmer skel-circle" style="width:32px;height:32px;border-radius:50%;flex-shrink:0;"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:6px;">
                <div class="skel-shimmer skel-line" style="width:{{ rand(50,80) }}%;height:12px;"></div>
                <div class="skel-shimmer skel-line" style="width:{{ rand(30,50) }}%;height:10px;"></div>
            </div>
            <div class="skel-shimmer skel-line" style="width:60px;height:24px;border-radius:99px;flex-shrink:0;"></div>
        </div>
        @break

    @case('stat')
        <div class="skel-stat {{ $attributes->get('class', '') }}">
            <div class="skel-shimmer skel-circle" style="width:36px;height:36px;border-radius:10px;flex-shrink:0;"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:6px;">
                <div class="skel-shimmer skel-line" style="width:50%;height:10px;"></div>
                <div class="skel-shimmer skel-line" style="width:35%;height:18px;"></div>
            </div>
        </div>
        @break

    @default
        <div class="skel-shimmer skel-line {{ $attributes->get('class', '') }}" style="width:{{ $width }};height:{{ $height ?? '14px' }};"></div>
@endswitch
@endfor
