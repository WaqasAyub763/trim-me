<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Trim — Link Shortener')</title>
    <meta name="description" content="@yield('description', 'A small link shortener with built-in click analytics.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="app-header">
        <div class="app-header__inner">
            <a href="{{ route('links.create') }}" class="brand">
                <span class="brand__mark">T</span>
                <span class="brand__name">Trim</span>
            </a>
            <nav class="app-nav" aria-label="Primary">
                <a href="{{ route('links.create') }}"
                   class="app-nav__item @if(request()->routeIs('links.create') || request()->routeIs('links.show')) app-nav__item--active @endif">
                    Create
                </a>
                @if(request()->routeIs('links.stats'))
                    <span class="app-nav__item app-nav__item--active">Analytics</span>
                @endif
            </nav>
            <div class="app-header__right">
                <span class="status-pill">
                    <span class="status-pill__dot" aria-hidden="true"></span>
                    All systems normal
                </span>
                <span class="kbd">v{{ config('app.version', '0.1.0') }}</span>
            </div>
        </div>
    </header>

    <main class="page @yield('page_class')">
        @yield('content')
    </main>

    <footer class="app-footer">
        <div class="app-footer__inner">
            <span>© {{ date('Y') }} Trim · A small internal link shortener</span>
            <div class="app-footer__links">
                <a href="{{ route('links.create') }}">Create</a>
                <a href="#">Privacy</a>
                <a href="#">Source</a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
