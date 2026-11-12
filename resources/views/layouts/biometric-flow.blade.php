<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#120e0b">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/biometric-flow.css') }}?v={{ filemtime(public_path('css/biometric-flow.css')) }}">
</head>
<body class="biometric-flow-page @yield('pageClass')">
    <main class="biometric-flow">
        <a class="biometric-flow-back" href="{{ route('settings') }}#tab-biometrics"><span aria-hidden="true">←</span> Back to Biometrics</a>
        <header class="biometric-flow-heading">
            <h1>@yield('title')</h1>
            <p>@yield('description')</p>
        </header>
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
