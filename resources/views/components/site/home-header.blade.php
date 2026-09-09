@props([
    'immediate' => true,
    'authOverlay' => false,
])

@php
    $user = auth()->user();
    $navigation = app(\App\Support\PublicNavigation::class)->data($user);
    $links = $navigation['top'];
@endphp

<header
    class="home-site-header {{ $immediate ? 'is-visible' : 'home-site-header--awaiting-pointer' }} {{ $authOverlay ? 'auth-page-header auth-drawer-top-navigation' : '' }}"
    data-home-site-header
>
    <div class="home-site-header__shell">
        <div class="home-site-header__identity">
            <a class="home-site-header__back" href="/" aria-label="Wróć na stronę główną">
                <span class="home-site-header__back-icon" aria-hidden="true">
                    <svg class="home-site-header__back-icon-full" viewBox="0 0 40 40" fill="none">
                        <circle cx="20" cy="20" r="18.75" stroke="currentColor" stroke-width="1.5"/>
                        <path d="m21.5 12.75-7.25 7.25 7.25 7.25M14.5 20h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
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

        <nav class="home-site-header__nav" aria-label="Nawigacja strony głównej">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}">
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="home-site-header__desktop-actions">
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
