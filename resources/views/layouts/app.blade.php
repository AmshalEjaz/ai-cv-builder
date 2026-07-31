<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'ResumeForge' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ auth()->check() ? route('dashboard') : route('home') }}">
            <span class="brand-mark">R</span> ResumeForge
        </a>
        @auth
            <nav class="nav-links">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('cvs.index') }}">My CVs</a>
                <a href="{{ route('templates.index') }}">Templates</a>
                <span class="user-name">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf <button class="link-button">Log out</button></form>
            </nav>
        @else
            <nav class="nav-links"><a href="{{ route('login') }}">Log in</a><a class="button button-small" href="{{ route('register') }}">Get started</a></nav>
        @endauth
    </header>
    <main class="page-container">
        @if(session('success')) <div class="alert success">{{ session('success') }}</div> @endif
        @if(session('info')) <div class="alert info">{{ session('info') }}</div> @endif
        @if($errors->any()) <div class="alert error"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        @yield('content')
    </main>
</body>
</html>
