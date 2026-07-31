<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'ResumeForge' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest-shell">
    <a class="brand guest-brand" href="{{ route('home') }}"><span class="brand-mark">R</span> ResumeForge</a>
    <main class="auth-card">@yield('content')</main>
</body>
</html>
