<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Request could not be completed · {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #182230; background: #f5f7fa; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 1.25rem; box-sizing: border-box; }
        main { width: min(100%, 32rem); padding: 2rem; border: 1px solid #e2e8f0; border-radius: 1rem; background: #fff; box-shadow: 0 1rem 3rem #18223012; }
        h1 { margin: 0 0 .75rem; font-size: 1.4rem; }
        p { line-height: 1.6; color: #475467; }
        code { font-size: .85rem; color: #667085; }
        a { display: inline-block; margin-top: .5rem; color: #175cd3; font-weight: 600; }
    </style>
</head>
<body>
<main>
    <h1>We couldn’t complete that request</h1>
    <p>{{ $message }}</p>
    <code>Reference: {{ $errorCode }}</code>
    <br>
    <a href="{{ url('/') }}">Return to the home page</a>
</main>
</body>
</html>
