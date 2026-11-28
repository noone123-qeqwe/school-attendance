<!doctype html>
<html lang="en" data-student-theme="dark" class="dark">
<head>
    <script @cspNonce>
        (function () {
            try {
                var saved = localStorage.getItem('student_portal_theme');
                var theme = saved === 'light' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-student-theme', theme);
                document.documentElement.classList.toggle('dark', theme === 'dark');
            } catch (e) {}
        })();
    </script>
    @include('partials.alert-banner-assets')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0C0709">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/student-frontend.css') }}?v={{ filemtime(public_path('css/student-frontend.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/biometric-flow.css') }}?v={{ filemtime(public_path('css/biometric-flow.css')) }}">
    @stack('styles')
</head>
<body class="sf-body biometric-flow-page @yield('pageClass')">
    <main class="biometric-flow">
        <div class="biometric-flow-topbar">
            <a class="sf-btn sf-btn-ghost" href="{{ route('settings') }}#tab-biometrics">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span>Back to Biometrics</span>
            </a>
            <span class="sf-pill is-emerald">
                <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                <span>Biometric Verification</span>
            </span>
        </div>
        <header class="biometric-flow-heading">
            <h1>@yield('title')</h1>
            <p>@yield('description')</p>
            @yield('aside_notes')
        </header>
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
