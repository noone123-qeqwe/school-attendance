@once
    <link rel="stylesheet" href="{{ asset('css/alert-banners.css') }}?v={{ filemtime(public_path('css/alert-banners.css')) }}">
    <script @cspNonce defer src="{{ asset('js/alert-banners.js') }}?v={{ filemtime(public_path('js/alert-banners.js')) }}"
            data-alert-scope="{{ auth()->id() ?? 'guest' }}"></script>
@endonce
