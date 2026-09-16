@php
                $languageRoute = request()->route()?->getName();
                if (!$languageRoute || $languageRoute === 'home') $languageRoute = 'home.localized';
                $languageParameters = array_intersect_key(request()->route()?->parameters() ?? [], array_flip(request()->route()?->parameterNames() ?? []));
                if ($languageRoute === 'password.reset') $languageParameters = array_merge($languageParameters, request()->only('token', 'email'));
            @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#086c63">
    <title>@yield('title', __('ui.meta_title')) · {{ config('app.name') }}</title>
    <meta name="description" content="{{ __('ui.meta_description') }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="{{ route('home.localized', ['locale' => app()->getLocale()]) }}">
    <link rel="alternate" hreflang="ro" href="{{ route($languageRoute, array_merge($languageParameters, ['locale' => 'ro'])) }}">
    <link rel="alternate" hreflang="en" href="{{ route($languageRoute, array_merge($languageParameters, ['locale' => 'en'])) }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body id="top">
    <a class="skip-link" href="#main">{{ __('ui.skip') }}</a>
    <header class="site-header">
        <div class="container header-inner">
            <x-brand />
            <span class="brand-tagline">{{ __('ui.tagline') }}</span>
            <nav class="desktop-nav" aria-label="{{ __('ui.navigation') }}">
                <a href="{{ route('home.localized', ['locale' => app()->getLocale()]) }}#despre">{{ __('ui.vision') }}</a>
                <a href="{{ route('home.localized', ['locale' => app()->getLocale()]) }}#principii">{{ __('ui.principles') }}</a>
                <a href="{{ auth()->check() ? route('account', ['locale' => app()->getLocale()]) : route('login', ['locale' => app()->getLocale()]) }}">{{ auth()->check() ? __('auth.account') : __('auth.login') }}</a>
            </nav>

            <nav class="language-switch" aria-label="{{ __('ui.language') }}">
                <a href="{{ route($languageRoute, array_merge($languageParameters, ['locale' => 'ro'])) }}" lang="ro" hreflang="ro" @if(app()->getLocale() === 'ro') aria-current="page" @endif>RO</a>
                <a href="{{ route($languageRoute, array_merge($languageParameters, ['locale' => 'en'])) }}" lang="en" hreflang="en" @if(app()->getLocale() === 'en') aria-current="page" @endif>EN</a>
            </nav>
            <details class="mobile-menu">
                <summary aria-label="{{ __('ui.menu') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></summary>
                <nav aria-label="{{ __('ui.navigation') }}">
                    <a href="{{ route('home.localized', ['locale' => app()->getLocale()]) }}#despre">{{ __('ui.vision') }}</a>
                    <a href="{{ route('home.localized', ['locale' => app()->getLocale()]) }}#principii">{{ __('ui.principles') }}</a>
                <a href="{{ auth()->check() ? route('account', ['locale' => app()->getLocale()]) : route('login', ['locale' => app()->getLocale()]) }}">{{ auth()->check() ? __('auth.account') : __('auth.login') }}</a>
                </nav>
            </details>
        </div>
    </header>
    <main id="main" tabindex="-1">@yield('content')</main>
    <footer class="site-footer">
        <div class="container footer-inner">
            <x-brand />
            <p>{{ __('ui.footer') }}</p>
            <a href="#top">{{ __('ui.back_top') }} <span aria-hidden="true">↑</span></a>
        </div>
    </footer>
</body>
</html>
