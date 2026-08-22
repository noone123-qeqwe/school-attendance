@props([
    'headers' => [],
    'empty' => false,
    'emptyTitle' => 'No records found',
    'emptyMessage' => 'There are no records to display.',
    'emptyIcon' => 'bi-inbox',
    'emptyActionText' => null,
    'emptyActionUrl' => null,
    'loading' => false,
    'loadingRows' => 5,
])

@php
    $isEmpty = is_bool($empty) ? $empty : (is_countable($empty) ? count($empty) === 0 : empty($empty));
@endphp

<div class="table-responsive" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: var(--ds-radius-md, 12px);">
    <table {{ $attributes->merge(['class' => 'adm-table ds-table']) }} style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; color: var(--ds-text-primary, #FCF8F2);">
        @if(!empty($headers))
            <thead>
                <tr style="background: rgba(0, 0, 0, 0.25); border-bottom: 1px solid var(--ds-border, rgba(212, 175, 55, 0.2)); text-transform: uppercase; font-size: 0.74rem; font-weight: 700; letter-spacing: 0.05em; color: var(--ds-text-secondary, #D1C5B4);">
                    @foreach($headers as $header)
                        <th scope="col" style="padding: 14px 18px; white-space: nowrap;">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif

        <tbody style="divide-y: 1px solid var(--ds-border-subtle, rgba(255,255,255,0.06));">
            @if($loading)
                @for($i = 0; $i < $loadingRows; $i++)
                    <tr style="border-bottom: 1px solid var(--ds-border-subtle, rgba(255,255,255,0.06));">
                        <td colspan="{{ max(1, count($headers)) }}" style="padding: 14px 18px;">
                            <x-skeleton type="table-row" />
                        </td>
                    </tr>
                @endfor
            @elseif($isEmpty)
                <tr>
                    <td colspan="{{ max(1, count($headers)) }}" style="padding: 36px 18px; text-align: center;">
                        <x-empty-state
                            :icon="$emptyIcon"
                            :title="$emptyTitle"
                            :message="$emptyMessage"
                            :action-text="$emptyActionText"
                            :action-url="$emptyActionUrl"
                        />
                    </td>
                </tr>
            @else
                {{ $slot }}
            @endif
        </tbody>
    </table>
</div>
