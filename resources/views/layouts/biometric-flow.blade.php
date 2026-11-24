<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#F5F3EC">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=JetBrains+Mono:wght@500;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/biometric-flow.css') }}?v={{ filemtime(public_path('css/biometric-flow.css')) }}">
</head>
<body class="biometric-flow-page @yield('pageClass')">
    <main class="biometric-flow">
        <div class="biometric-flow-topbar">
            <a class="biometric-flow-back" href="{{ route('settings') }}#tab-biometrics"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Back to Biometrics</span></a>
            <span class="biometric-flow-kicker"><i class="bi bi-shield-check" aria-hidden="true"></i> Security &amp; Verification</span>
        </div>
        <header class="biometric-flow-heading">
            <h1>@yield('title')</h1>
            <p>@yield('description')</p>
        </header>
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
