@props([
    'immediate' => true,
    'authOverlay' => false,
])

@php
    $user = auth()->user();
    $navigation = app(\App\Support\PublicNavigation::class)->data($user);
    $links = $navigation['top'];
    $googleLoginAvailable = filled(config('services.google.client_id'))
        && filled(config('services.google.client_secret'));
@endphp

<header
    class="home-site-header {{ $immediate ? 'is-visible' : 'home-site-header--awaiting-pointer' }} {{ $authOverlay ? 'auth-page-header auth-drawer-top-navigation' : '' }}"
    data-home-site-header
>
    <div class="home-site-header__shell">
        <div class="home-site-header__identity">
            <a class="home-site-header__back" href="/" aria-label="Wróć na stronę główną">
                <span class="home-site-header__back-icon" aria-hidden="true">
                    <img
                        class="home-site-header__back-icon-full"
                        src="{{ asset('images/site-brand-mark-shield-v2.png') }}"
                        alt=""
                        width="256"
                        height="256"
                    >
                    <svg class="home-site-header__back-icon-compact" viewBox="0 0 20 28" fill="none">
                        <path d="m12.5 5.5-7.25 8.5 7.25 8.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="home-site-header__back-label" aria-hidden="true">Wróć</span>
            </a>
            <a class="home-site-header__brand" href="/" aria-label="Prawko na Raz — strona główna">
                <span>prawko</span><strong>naraz</strong>
            </a>
        </div>

        <nav class="home-site-header__nav home-site-header__nav--reference" aria-label="Nawigacja strony głównej">
            <a href="{{ $navigation['learning_href'] }}"><span>Portal</span></a>
            <details class="home-site-header__courses">
                <summary>Kursy <span aria-hidden="true">⌄</span></summary>
                <div>
                    <a href="{{ route('public.course', absolute: false) }}">Kurs teorii</a>
                    <a href="{{ route('public.tests', absolute: false) }}">Testy online</a>
                    <a href="{{ route('public.lectures', absolute: false) }}">Wykłady</a>
                </div>
            </details>
            <a href="#aplikacje"><span>Aplikacje</span></a>
            <a href="{{ route('public.pricing', absolute: false) }}"><span>Cennik</span></a>
            <a href="{{ route('about.organization', absolute: false) }}"><span>O nas</span></a>
            <a href="{{ route('about.contact', absolute: false) }}"><span>Kontakt</span></a>
        </nav>

        <div class="home-site-header__desktop-actions">
            <a class="home-site-header__osk" href="{{ route('about.contact', absolute: false) }}">Strefa OSK</a>
            @guest
                <a
                    class="home-site-header__google"
                    href="{{ $googleLoginAvailable ? route('social.redirect', ['provider' => 'google'], absolute: false) : route('login', absolute: false) }}"
                >
                    <svg aria-hidden="true" viewBox="0 0 48 48">
                        <path fill="#EA4335" d="M24 9.5c3.2 0 6.1 1.1 8.4 3.2l6.3-6.3C34.8 2.8 29.7.8 24 .8 14.8.8 6.9 6 3 13.6l7.3 5.7C12.1 13.6 17.5 9.5 24 9.5Z"/>
                        <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.7c-.5 2.8-2.2 5.2-4.7 6.8l7.2 5.6c4.2-3.9 7.3-9.6 7.3-16.4Z"/>
                        <path fill="#FBBC05" d="M10.3 28.7A14.6 14.6 0 0 1 9.5 24c0-1.6.3-3.2.8-4.7L3 13.6A23.1 23.1 0 0 0 .5 24c0 3.7.9 7.3 2.5 10.4l7.3-5.7Z"/>
                        <path fill="#34A853" d="M24 47.2c5.7 0 10.6-1.9 14.1-5.1l-6.1-6.8c-1.7 1.1-4 1.9-8 1.9-6.5 0-11.9-4.1-13.7-9.8L3 33.1c3.9 7.8 11.8 14.1 21 14.1Z"/>
                    </svg>
                    <span>Kontynuuj z Google</span>
                </a>
            @endguest
            <a class="home-site-header__account" href="{{ $user ? route('profile.edit', absolute: false) : route('login', absolute: false) }}">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
                    <circle cx="12" cy="9" r="3" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M6.7 19.15c.85-3.05 2.62-4.55 5.3-4.55s4.45 1.5 5.3 4.55" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
                <span>{{ $user ? 'Moje konto' : 'Logowanie' }}</span>
            </a>

            <details class="home-site-header__launcher" data-home-header-menu>
                <summary aria-label="Otwórz menu serwisu">
                    <span class="home-site-header__launcher-icon" aria-hidden="true">
                        @for ($dot = 0; $dot < 9; $dot++)
                            <i></i>
                        @endfor
                    </span>
                </summary>
                <div class="home-site-header__launcher-panel">
                    @if ($user)
                        <a href="{{ $navigation['learning_href'] }}">
                            <x-heroicon-o-clipboard-document-check aria-hidden="true" />
                            <span>Kontynuuj naukę</span>
                        </a>
                        <a href="{{ route('profile.edit', absolute: false) }}">
                            <x-heroicon-o-user-circle aria-hidden="true" />
                            <span>Moje konto</span>
                        </a>
                        @if ($navigation['logout_href'])
                            <form
                                method="POST"
                                action="{{ $navigation['logout_href'] }}"
                                data-csrf-refresh-form
                                data-csrf-form-kind="logout"
                            >
                                @csrf
                                <button type="submit" data-csrf-submit>
                                    <x-heroicon-o-arrow-right-on-rectangle aria-hidden="true" />
                                    <span>Wyloguj</span>
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('login', absolute: false) }}">
                            <x-heroicon-o-key aria-hidden="true" />
                            <span>Zaloguj się</span>
                        </a>
                        <a href="{{ route('register', absolute: false) }}">
                            <x-heroicon-o-user-plus aria-hidden="true" />
                            <span>Załóż konto</span>
                        </a>
                    @endif
                </div>
            </details>
        </div>

        <details class="home-site-header__mobile-menu" data-home-header-menu>
            <summary aria-label="Otwórz menu">
                <span class="home-site-header__launcher-icon" aria-hidden="true">
                    @for ($dot = 0; $dot < 9; $dot++)
                        <i></i>
                    @endfor
                </span>
            </summary>
            <div class="home-site-header__mobile-panel">
                <nav aria-label="Nawigacja mobilna strony głównej">
                    @foreach ($links as $link)
                        <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </nav>
                <div>
                    @if ($user)
                        <a href="{{ $navigation['learning_href'] }}">Kontynuuj naukę</a>
                        <a href="{{ route('profile.edit', absolute: false) }}">Moje konto</a>
                        @if ($navigation['logout_href'])
                            <form
                                method="POST"
                                action="{{ $navigation['logout_href'] }}"
                                data-csrf-refresh-form
                                data-csrf-form-kind="logout"
                            >
                                @csrf
                                <button type="submit" data-csrf-submit>Wyloguj</button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('register', absolute: false) }}">Załóż konto</a>
                        <a href="{{ route('login', absolute: false) }}">Zaloguj się</a>
                    @endif
                </div>
            </div>
        </details>
    </div>
</header>

<noscript>
    <style>.home-site-header--awaiting-pointer .home-site-header__shell { opacity: 1; visibility: visible; transform: none; pointer-events: auto; }</style>
</noscript>
