<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'ResumeForge' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/1.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/1.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ auth()->check() ? route('dashboard') : route('home') }}">
            <span class="brand-mark">R</span> ResumeForge
        </a>
        @auth
            <button class="menu-toggle" type="button" data-menu-toggle aria-label="Open navigation" aria-expanded="false">☰</button>
            <nav class="nav-links authenticated-nav" data-mobile-nav>
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('cvs.index') }}">My CVs</a>
                <a href="{{ route('templates.index') }}">Templates</a>
                <a href="{{ route('templates.manage') }}">Manage templates</a>
                <span class="user-name">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf <button class="link-button">Logout</button></form>
            </nav>
        @else
            <nav class="nav-links"><a href="{{ route('login') }}">Login</a><a class="button button-small" href="{{ route('register') }}">Get started</a></nav>
        @endauth
    </header>
    <main class="page-container">
        @if(session('success')) <div class="toast toast-success" data-toast><span class="toast-icon">✓</span><span>{{ session('success') }}</span><button type="button" data-toast-close aria-label="Close">×</button></div> @endif
        @if(session('info')) <div class="toast toast-info" data-toast><span class="toast-icon">i</span><span>{{ session('info') }}</span><button type="button" data-toast-close aria-label="Close">×</button></div> @endif
        @if($errors->any()) <div class="toast toast-error" data-toast><span class="toast-icon">!</span><span>{{ $errors->first() }}</span><button type="button" data-toast-close aria-label="Close">×</button></div> @endif
        @yield('content')
    </main>
</body>
</html>
