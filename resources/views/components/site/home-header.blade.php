@props([
    'immediate' => true,
    'authOverlay' => false,
])

@php
    $user = auth()->user();
    $navigation = app(\App\Support\PublicNavigation::class)->data($user);
    $userName = trim((string) ($user?->name ?? ''));
    $userFirstName = collect(preg_split('/\s+/', $userName, -1, PREG_SPLIT_NO_EMPTY))->first() ?: 'Moje konto';
    $userAvatar = $user ? app(\App\Support\UserAvatarService::class)->payload($user) : null;
    $userAvatarUrl = $userAvatar['url'] ?? null;
    $testsNavigationCurrent = request()->is('testy-na-prawo-jazdy*', 'najtrudniejsze-pytania-na-prawo-jazdy*', 'oficjalna-baza-pytan-na-prawo-jazdy*');
    $signsNavigationCurrent = request()->is('znaki-drogowe*');
    $links = $navigation['top'];
    $testMenu = $navigation['test_menu'];
    $trafficSignMenu = $navigation['traffic_sign_menu'];
    $testCategoryLinks = static fn (array $codes): array => array_values(array_filter(
        $testMenu,
        static fn (array $item): bool => in_array($item['code'] ?? null, $codes, true),
    ));
    $signLinks = static fn (array $labels): array => array_values(array_filter(
        $trafficSignMenu,
        static fn (array $item): bool => in_array($item['label'], $labels, true),
    ));
    $testMegaGroups = [
        [
            'label' => 'Testy online', 'icon' => 'exam', 'heading' => 'Testy i pytania egzaminacyjne',
            'links' => [$testMenu[0], end($testMenu), ['label' => 'Oficjalna baza pytań', 'href' => route('public.questions.hub', absolute: false)]],
            'promo_title' => 'Sprawdź swoją wiedzę przed WORD', 'promo_text' => 'Wybierz kategorię, przejrzyj pytania i ćwicz w swoim tempie.',
            'cta_label' => 'Przejdź do testów', 'cta_href' => route('public.tests', absolute: false),
        ],
        [
            'label' => 'Samochody i inne pojazdy', 'icon' => 'car', 'heading' => 'Kategorie samochodowe i T',
            'links' => $testCategoryLinks(['B', 'C', 'D', 'T']),
            'promo_title' => 'Wybierz swoją kategorię', 'promo_text' => 'Pytania i przewodniki dopasowane do egzaminu, który zdajesz.',
            'cta_label' => 'Testy kategorii B', 'cta_href' => route('public.tests.category', ['categorySlug' => 'b'], absolute: false),
        ],
        [
            'label' => 'Motocykle i motorowery', 'icon' => 'motorcycle', 'heading' => 'Kategorie motocyklowe',
            'links' => $testCategoryLinks(['A', 'A1', 'AM']),
            'promo_title' => 'Nauka na jednoślad', 'promo_text' => 'Znajdź pytania na motocykl, lekki motocykl lub motorower.',
            'cta_label' => 'Testy kategorii A', 'cta_href' => route('public.tests.category', ['categorySlug' => 'a'], absolute: false),
        ],
        [
            'label' => 'Kursy i nauka', 'icon' => 'study', 'heading' => 'Ucz się krok po kroku',
            'links' => $navigation['courses'],
            'promo_title' => 'Cała nauka w jednym miejscu', 'promo_text' => 'Kurs teorii, wykłady i testy pomagają przygotować się do egzaminu.',
            'cta_label' => 'Zobacz kursy', 'cta_href' => route('public.course', absolute: false),
        ],
    ];
    $signMegaGroups = [
        [
            'label' => 'Wszystkie znaki', 'icon' => 'all-signs', 'heading' => 'Poznaj znaki drogowe',
            'links' => [$trafficSignMenu[0], ...$signLinks(['Znaki ostrzegawcze', 'Znaki zakazu', 'Znaki nakazu', 'Znaki informacyjne'])],
            'promo_title' => 'Ucz się znaków ze zrozumieniem', 'promo_text' => 'Przeglądaj grupy znaków i wracaj do tych, które sprawiają trudność.',
            'cta_label' => 'Zobacz wszystkie znaki', 'cta_href' => route('traffic-signs.index', absolute: false),
        ],
        [
            'label' => 'Znaki pionowe', 'icon' => 'vertical-signs', 'heading' => 'Znaki pionowe',
            'links' => $signLinks(['Znaki ostrzegawcze', 'Znaki zakazu', 'Znaki nakazu', 'Znaki informacyjne', 'Znaki uzupełniające']),
            'promo_title' => 'Rozpoznawaj znaki szybciej', 'promo_text' => 'Poznaj znaczenie znaków spotykanych na drodze.',
            'cta_label' => 'Znaki ostrzegawcze', 'cta_href' => route('traffic-signs.categories.show', ['categorySlug' => 'znaki-ostrzegawcze'], absolute: false),
        ],
        [
            'label' => 'Znaki poziome', 'icon' => 'road-signs', 'heading' => 'Oznakowanie na jezdni',
            'links' => $signLinks(['Znaki poziome']),
            'promo_title' => 'Czytaj oznakowanie drogi', 'promo_text' => 'Linie, strzałki i symbole na jezdni mają znaczenie na egzaminie i w ruchu.',
            'cta_label' => 'Zobacz znaki poziome', 'cta_href' => route('traffic-signs.categories.show', ['categorySlug' => 'znaki-drogowe-poziome'], absolute: false),
        ],
        [
            'label' => 'Sygnały i kontrolki', 'icon' => 'signals', 'heading' => 'Sygnały i oznaczenia pojazdu',
            'links' => $signLinks(['Sygnały świetlne', 'Osoba kierująca ruchem', 'Kontrolki pojazdu']),
            'promo_title' => 'Sprawdź sytuacje na drodze', 'promo_text' => 'Utrwal sygnały świetlne i polecenia osoby kierującej ruchem.',
            'cta_label' => 'Sygnały świetlne', 'cta_href' => route('traffic-signs.categories.show', ['categorySlug' => 'sygnaly-swietlne'], absolute: false),
        ],
    ];
    $googleLoginAvailable = filled(config('services.google.client_id'))
        && filled(config('services.google.client_secret'));
@endphp

<header
    class="home-site-header {{ request()->routeIs('home') ? 'home-site-header--reference' : '' }} {{ $immediate ? 'is-visible' : 'home-site-header--awaiting-pointer' }} {{ request()->routeIs('home') ? 'home-site-header--home-unified' : '' }} {{ ! $authOverlay ? 'home-site-header--floating' : '' }} {{ $authOverlay ? 'auth-page-header auth-drawer-top-navigation' : '' }}"
    data-home-site-header
>
    <div class="home-site-header__shell">
        <div class="home-site-header__identity">
            <a class="home-site-header__back" href="/" aria-label="Wróć na stronę główną">
                <span class="home-site-header__back-icon" aria-hidden="true">
                    <img
                        class="home-site-header__back-icon-full"
                        src="{{ asset('images/site-brand-mark-shield-v2-optimized.webp') }}"
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
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" @class(['home-site-header__home-current' => (request()->routeIs('home') || request()->is('aktualnosci*')) && $link['label'] === 'Portal'])>
                    <span>{{ $link['label'] }}</span>
                </a>

                @if ($loop->first)
                    <details @class(['home-site-header__catalog', 'home-site-header__catalog--tests', 'home-site-header__catalog--mega', 'is-current' => $testsNavigationCurrent]) data-home-header-menu>
                        <summary>
                            Testy na prawo jazdy
                            <svg class="home-site-header__chevron" aria-hidden="true" viewBox="0 0 20 20" fill="none"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </summary>
                        @include('components.site.mega-menu-panel', ['groups' => $testMegaGroups])
                    </details>

                    <details @class(['home-site-header__catalog', 'home-site-header__catalog--signs', 'home-site-header__catalog--mega', 'is-current' => $signsNavigationCurrent]) data-home-header-menu>
                        <summary>
                            Znaki drogowe
                            <svg class="home-site-header__chevron" aria-hidden="true" viewBox="0 0 20 20" fill="none"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </summary>
                        @include('components.site.mega-menu-panel', ['groups' => $signMegaGroups])
                    </details>

                @endif
            @endforeach
        </nav>

        <div class="home-site-header__desktop-actions {{ $user ? 'home-site-header__desktop-actions--user' : '' }}">
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
            <a class="home-site-header__account {{ $user ? 'home-site-header__account--user' : '' }}" href="{{ $user ? route('profile.edit', absolute: false) : route('login', absolute: false) }}">
                @if ($userAvatarUrl)
                    <img
                        class="home-site-header__account-avatar"
                        src="{{ $userAvatarUrl }}"
                        alt=""
                        aria-hidden="true"
                        referrerpolicy="no-referrer"
                    >
                @else
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
                        <circle cx="12" cy="9" r="3" stroke="currentColor" stroke-width="1.6"/>
                        <path d="M6.7 19.15c.85-3.05 2.62-4.55 5.3-4.55s4.45 1.5 5.3 4.55" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                @endif
                <span>{{ $user ? $userFirstName : 'Logowanie' }}</span>
            </a>

            <details class="home-site-header__launcher {{ $user ? 'home-site-header__launcher--account' : '' }}" data-home-header-menu>
                <summary aria-label="Otwórz menu serwisu">
                    @if (! $authOverlay)
                        <svg class="home-site-header__learning-chevron" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    @else
                        <span class="home-site-header__launcher-icon" aria-hidden="true">
                            @for ($dot = 0; $dot < 9; $dot++)
                                <i></i>
                            @endfor
                        </span>
                    @endif
                </summary>
                <div class="home-site-header__launcher-panel">
                    @if ($user)
                        <a href="{{ $navigation['learning_href'] }}">
                            <x-heroicon-o-clipboard-document-check aria-hidden="true" />
                            <span>Kontynuuj naukę</span>
                        </a>
                        @foreach ($navigation['header_actions'] as $action)
                            <a href="{{ $action['href'] }}">
                                @if (($action['href'] ?? '') === route('profile.edit', absolute: false))
                                    <x-heroicon-o-user-circle aria-hidden="true" />
                                @else
                                    <x-heroicon-o-squares-2x2 aria-hidden="true" />
                                @endif
                                <span>{{ ($action['href'] ?? '') === route('profile.edit', absolute: false) ? 'Moje konto' : $action['label'] }}</span>
                            </a>
                        @endforeach
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
                <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </summary>
            <div class="home-site-header__mobile-panel">
                <nav aria-label="Nawigacja mobilna strony głównej">
                    @foreach ($links as $link)
                        <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                        @if ($loop->first)
                            <details class="home-site-header__mobile-catalog">
                                <summary>Testy na prawo jazdy</summary>
                                <div>
                                    @foreach ($testMenu as $item)
                                        <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                                    @endforeach
                                </div>
                            </details>
                            <details class="home-site-header__mobile-catalog">
                                <summary>Znaki drogowe</summary>
                                <div>
                                    @foreach ($trafficSignMenu as $item)
                                        <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                                    @endforeach
                                </div>
                            </details>
                        @endif
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
