<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'ResumeForge' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest-shell">
    <main class="auth-card">
        <a class="brand guest-card-brand" href="{{ route('home') }}"><span class="brand-mark">R</span> ResumeForge</a>
        @if(session('success')) <div class="toast toast-success" data-toast><span class="toast-icon">✓</span><span>{{ session('success') }}</span><button type="button" data-toast-close aria-label="Close">×</button></div> @endif
        @if($errors->any()) <div class="toast toast-error" data-toast><span class="toast-icon">!</span><span>{{ $errors->first() }}</span><button type="button" data-toast-close aria-label="Close">×</button></div> @endif
        @yield('content')
    </main>
</body>
</html>
