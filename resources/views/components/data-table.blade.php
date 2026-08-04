@props(['headers' => []])

<div class="table-responsive">
    <table {{ $attributes->merge(['class' => 'adm-table']) }}>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
