@php
    $user = auth()->user();
    $navigation = app(\App\Support\PublicNavigation::class)->data(auth()->user());
    $currentPath = '/'.ltrim(request()->path(), '/');
    $linkPath = static fn (string $href): string => parse_url($href, PHP_URL_PATH) ?: $href;
    $matchesCurrentPath = static function (array $matchPaths) use ($currentPath): bool {
        foreach ($matchPaths as $matchPath) {
            if ($currentPath === $matchPath || str_starts_with($currentPath, $matchPath.'/')) {
                return true;
            }
        }

        return false;
    };
    $headerActions = array_values(array_filter(
        $navigation['header_actions'],
        static fn (array $action): bool => $linkPath($action['href']) !== $currentPath,
    ));
    $userName = trim((string) ($user?->name ?? ''));
    $userAvatar = $user ? app(\App\Support\UserAvatarService::class)->payload($user) : null;
    $userAvatarUrl = $userAvatar['url'] ?? null;
    $userInitials = $userAvatar['initials'] ?? collect(preg_split('/\s+/', $userName, -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8'))
        ->implode('') ?: 'U';

    $mobilePanelActions = $headerActions;
    $mobilePrimaryLinks = [
        [
            'label' => 'Strona główna',
            'href' => route('home', absolute: false),
            'match' => ['/'],
            'icon' => 'home',
        ],
        [
            'label' => 'Aktualności',
            'href' => route('public.news', absolute: false),
            'match' => ['/aktualnosci'],
            'icon' => 'news',
        ],
        [
            'label' => 'Nauka',
            'href' => $navigation['learning_href'],
            'match' => ['/nauka', '/study-sessions', '/trener-pamieci'],
            'icon' => 'learn',
        ],
        [
            'label' => 'Pytania',
            'href' => route('public.questions.hub', absolute: false),
            'match' => ['/oficjalna-baza-pytan-na-prawo-jazdy', '/pytanie'],
            'icon' => 'question',
        ],
        [
            'label' => 'Znaki drogowe',
            'href' => route('traffic-signs.index', absolute: false),
            'match' => ['/znaki-drogowe'],
            'icon' => 'signs',
        ],
        [
            'label' => 'Przepisy',
            'href' => route('public.regulations', absolute: false),
            'match' => ['/przepisy'],
            'icon' => 'book',
        ],
        [
            'label' => 'Testy online',
            'href' => route('public.tests', absolute: false),
            'match' => ['/testy-na-prawo-jazdy'],
            'icon' => 'monitor',
        ],
        [
            'label' => 'Cennik',
            'href' => route('public.pricing', absolute: false),
            'match' => ['/cennik'],
            'icon' => 'tag',
        ],
    ];
    $desktopPrimaryLinks = [
        [
            'label' => 'Nauka',
            'href' => $navigation['learning_href'],
            'match' => ['/nauka', '/study-sessions', '/trener-pamieci'],
            'icon' => 'learn',
        ],
        [
            'label' => 'Testy',
            'href' => route('public.tests', absolute: false),
            'match' => ['/testy-na-prawo-jazdy'],
            'icon' => 'monitor',
        ],
        [
            'label' => 'Znaki',
            'href' => route('traffic-signs.index', absolute: false),
            'match' => ['/znaki-drogowe'],
            'icon' => 'signs',
        ],
        [
            'label' => 'Przepisy',
            'href' => route('public.regulations', absolute: false),
            'match' => ['/przepisy'],
            'icon' => 'book',
        ],
        [
            'label' => 'Cennik',
            'href' => route('public.pricing', absolute: false),
            'match' => ['/cennik'],
            'icon' => 'tag',
        ],
    ];
@endphp

@once
    <style>
        .site-account-menu > summary {
            list-style: none;
        }

        .site-account-menu > summary::-webkit-details-marker {
            display: none;
        }

        .site-account-menu:not([open]) .site-account-menu__panel {
            display: none;
        }

        .site-account-menu[open] .site-account-menu__chevron {
            transform: rotate(180deg);
        }
    </style>
@endonce

<header class="site-header">
    <div class="site-header__source-strip" data-public-source-strip>
        <a href="{{ route('public.partners', absolute: false) }}" class="site-header__source-strip-link" aria-label="Zobacz partnerów i źródło oficjalnej bazy pytań">
            <span class="site-header__source-emblem" aria-hidden="true">
                <img src="{{ Vite::asset('resources/images/session/question-source-emblem-crop.png') }}" alt="">
            </span>
            <span class="site-header__source-badge">Oficjalna baza {{ now()->year }}</span>
            <span class="site-header__source-copy">Pytania pochodzą z rządowego źródła</span>
            <span class="site-header__source-handwritten">Ministerstwa Infrastruktury</span>
            <span class="site-header__source-arrow" aria-hidden="true">
                <svg viewBox="0 0 90 44" focusable="false">
                    <path class="site-header__source-arrow-line site-header__source-arrow-line--ghost" d="M5 8C20 12 31 21 47 24C59 26 67 23 75 30" />
                    <path class="site-header__source-arrow-line" d="M4 6C19 10 31 19 47 22C59 24 68 22 77 30" />
                    <path class="site-header__source-arrow-head" d="M66 21L79 31L64 38" />
                </svg>
            </span>
            <span class="site-header__source-link">Partnerzy i źródła <span aria-hidden="true">→</span></span>
        </a>
        <button type="button" class="site-header__source-dismiss" aria-label="Zamknij pasek informacyjny" data-public-source-strip-dismiss>
            <svg aria-hidden="true" viewBox="0 0 16 16" focusable="false">
                <path d="m4.2 4.2 7.6 7.6M11.8 4.2l-7.6 7.6" />
            </svg>
        </button>
    </div>
    <div class="site-header__shell">
        <div class="site-header__mobile">
            <a href="{{ route('home', absolute: false) }}" class="inline-flex min-w-0 shrink items-center text-[#151515]">
                <span class="site-header-logo">
                    <img
                        src="/images/site-header-logo-20260728.png"
                        alt="PrawkoNaRaz.pl - Pytania na Prawo Jazdy"
                        class="site-header-logo__image"
                        width="1150"
                        height="310"
                    >
                </span>
            </a>

            <div class="site-header__mobile-actions">
                <a href="{{ route('public.tests', absolute: false) }}" class="site-header__mobile-button site-header__mobile-button--primary">
                    SpeedRun
                </a>
                <button
                    type="button"
                    class="site-header__mobile-button"
                    aria-expanded="false"
                    aria-controls="public-mobile-site-menu"
                    data-public-mobile-menu-button
                >
                    Menu
                </button>
            </div>
        </div>

        <div
            id="public-mobile-site-menu"
            class="site-header__mobile-panel max-h-[calc(100svh-76px)] overflow-y-auto border-t border-[#eceff2] py-4 xl:hidden"
            data-public-mobile-menu-panel
            hidden
        >
            <form method="GET" action="{{ route('public.questions.hub') }}" class="mb-4 flex overflow-hidden rounded-full border border-[#cfd4db] bg-white">
                <label class="sr-only" for="public-mobile-header-question-search">Szukaj pytań</label>
                <input
                    id="public-mobile-header-question-search"
                    type="search"
                    name="q"
                    placeholder="Szukaj pytań, odpowiedzi, przepisów..."
                    class="min-w-0 flex-1 border-0 px-4 py-3 text-sm font-medium text-[#151515] placeholder:text-[#8d94a0] focus:ring-0"
                >
                <button type="submit" class="m-1 grid w-11 shrink-0 place-items-center rounded-full bg-[#d01921] text-white" aria-label="Szukaj">
                    <svg aria-hidden="true" class="h-5 w-5">
                        <use href="/images/site-header-symbols.svg#arrow-right"></use>
                    </svg>
                </button>
            </form>

            <nav aria-label="Menu mobilne" class="grid sm:grid-cols-2">
                @foreach ($mobilePrimaryLinks as $link)
                    <a
                        href="{{ $link['href'] }}"
                        class="flex min-h-12 items-center gap-3 border-b border-[#eef1f4] py-2.5 text-[0.82rem] font-bold uppercase transition {{ $matchesCurrentPath($link['match']) ? 'text-[#d01921]' : 'text-[#151515] hover:text-[#d01921]' }}"
                        data-public-mobile-menu-link
                    >
                        <svg aria-hidden="true" class="h-6 w-6 shrink-0">
                            <use href="/images/site-header-symbols.svg#{{ $link['icon'] }}"></use>
                        </svg>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            @if ($mobilePanelActions || $navigation['logout_href'])
                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach ($mobilePanelActions as $action)
                        <a
                            href="{{ $action['href'] }}"
                            class="inline-flex h-11 w-full items-center justify-center rounded-[4px] px-4 text-sm font-bold transition {{ ($action['variant'] ?? 'text') === 'primary' ? 'bg-[#d01921] text-white hover:bg-[#b9151c]' : 'border border-[#d6d9df] text-[#151515] hover:border-[#d01921]' }}"
                            data-public-mobile-menu-link
                        >
                            {{ $action['label'] }}
                        </a>
                    @endforeach

                    @if ($navigation['logout_href'])
                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                            data-csrf-refresh-form
                            data-csrf-form-kind="logout"
                        >
                            @csrf
                            <p class="mb-2 hidden text-xs font-semibold text-red-700" role="alert" data-csrf-form-error>
                                Nie udało się sprawdzić sesji. Odśwież stronę.
                            </p>
                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-[4px] border border-[#d6d9df] px-4 text-sm font-bold text-[#151515] transition hover:border-[#d01921] disabled:cursor-not-allowed disabled:opacity-60" data-csrf-submit>
                                Wyloguj
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            <nav aria-label="Informacje" class="mt-4 flex flex-wrap gap-x-4 gap-y-2 border-t border-[#eceff2] pt-4">
                @foreach ($navigation['utility'] as $link)
                    <a
                        href="{{ $link['href'] }}"
                        class="text-[0.76rem] font-bold tracking-[0.02em] transition {{ $matchesCurrentPath($link['match']) ? 'text-[#d01921]' : 'text-[#4b5563] hover:text-[#d01921]' }}"
                        data-public-mobile-menu-link
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="site-header__desktop">
            <div class="site-header__top">
                <div class="site-header__brand-cell">
                    <a href="{{ route('home', absolute: false) }}" class="site-header-logo" aria-label="PrawkoNaRaz.pl - Pytania na Prawo Jazdy">
                        <img
                            src="/images/site-header-logo-20260728.png"
                            alt="PrawkoNaRaz.pl - Pytania na Prawo Jazdy"
                            class="site-header-logo__image"
                            width="1150"
                            height="310"
                        >
                    </a>
                </div>

                <div class="site-header__search-cell">
                    <form method="GET" action="{{ route('public.questions.hub') }}" class="site-header__search">
                        <label class="sr-only" for="public-header-question-search">Szukaj pytań</label>
                        <span class="site-header__search-leading" aria-hidden="true">
                            <svg class="h-6 w-6">
                                <use href="/images/site-header-symbols.svg#search"></use>
                            </svg>
                        </span>
                        <input
                            id="public-header-question-search"
                            type="search"
                            name="q"
                            placeholder="Szukaj pytań, odpowiedzi, przepisów..."
                            class="site-header__search-input"
                        >
                        <button type="submit" class="site-header__search-submit" aria-label="Szukaj">
                            <svg aria-hidden="true" class="h-6 w-6">
                                <use href="/images/site-header-symbols.svg#arrow-right"></use>
                            </svg>
                        </button>
                    </form>
                </div>

                <nav aria-label="Nawigacja główna" class="site-header__nav site-header__nav--inline">
                    @foreach ($desktopPrimaryLinks as $link)
                        <a
                            href="{{ $link['href'] }}"
                            class="site-header__nav-link {{ $matchesCurrentPath($link['match']) ? 'is-active' : '' }}"
                        >
                            <svg aria-hidden="true" class="site-header__nav-icon">
                                <use href="/images/site-header-symbols.svg#{{ $link['icon'] }}"></use>
                            </svg>
                            <span>{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="site-header__exam-cell">
                    <a href="{{ route('public.tests', absolute: false) }}" class="site-header__exam">
                        <span>Start SpeedRun</span>
                        <svg aria-hidden="true" class="h-5 w-5">
                            <use href="/images/site-header-symbols.svg#arrow-right"></use>
                        </svg>
                    </a>
                </div>

                <div class="relative flex items-center justify-end">
                    @if ($user)
                        <details class="site-account-menu relative">
                            <summary class="site-header__account cursor-pointer">
                                <span class="site-header__account-avatar" aria-hidden="true">
                                    @if ($userAvatarUrl)
                                        <img src="{{ $userAvatarUrl }}" alt="" referrerpolicy="no-referrer">
                                    @else
                                        <span>{{ $userInitials }}</span>
                                    @endif
                                </span>
                                <span>Moje konto</span>
                                <svg aria-hidden="true" class="site-account-menu__chevron h-4 w-4 transition-transform">
                                    <use href="/images/site-header-symbols.svg#chevron-down"></use>
                                </svg>
                            </summary>

                            <div class="site-account-menu__panel absolute right-0 top-[calc(100%+0.2rem)] z-50 w-64 rounded-[8px] border border-[#dde3ea] bg-white p-2 shadow-[0_20px_48px_rgba(15,23,42,0.16)]">
                                <div class="mb-2 flex items-center gap-3 border-b border-[#edf0f4] px-3 py-3">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full border border-[#d4d8de] bg-[#f7f8fa] text-xs font-bold uppercase text-[#10172f]">
                                        @if ($userAvatarUrl)
                                            <img src="{{ $userAvatarUrl }}" alt="" class="h-full w-full object-cover" referrerpolicy="no-referrer">
                                        @else
                                            {{ $userInitials }}
                                        @endif
                                    </span>
                                    <span class="min-w-0 truncate text-sm font-bold text-[#111827]">{{ $userName ?: 'Moje konto' }}</span>
                                </div>
                                <a href="{{ $navigation['learning_href'] }}" class="flex min-h-10 items-center rounded-[5px] px-3 text-sm font-bold text-[#111827] transition hover:bg-[#f6f7f9] hover:text-[#d01921]">
                                    Nauka
                                </a>
                                @foreach ($navigation['header_actions'] as $action)
                                    <a href="{{ $action['href'] }}" class="flex min-h-10 items-center rounded-[5px] px-3 text-sm font-bold text-[#111827] transition hover:bg-[#f6f7f9] hover:text-[#d01921]">
                                        {{ $action['label'] }}
                                    </a>
                                @endforeach
                                @if ($navigation['logout_href'])
                                    <form
                                        method="POST"
                                        action="{{ route('logout') }}"
                                        class="mt-2 border-t border-[#edf0f4] pt-2"
                                        data-csrf-refresh-form
                                        data-csrf-form-kind="logout"
                                    >
                                        @csrf
                                        <p class="hidden px-3 py-2 text-xs font-semibold text-red-700" role="alert" data-csrf-form-error>
                                            Nie udało się sprawdzić sesji. Odśwież stronę.
                                        </p>
                                        <button type="submit" class="flex min-h-10 w-full items-center rounded-[5px] px-3 text-left text-sm font-bold text-[#40454f] transition hover:bg-[#fff4f4] hover:text-[#d01921] disabled:cursor-not-allowed disabled:opacity-60" data-csrf-submit>
                                            Wyloguj
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </details>
                    @else
                        <div class="site-header__auth-actions">
                            <div class="site-header__login" aria-label="Logowanie i rejestracja">
                                <a href="{{ route('login', absolute: false) }}" class="site-header__login-icon-link" aria-label="Zaloguj się">
                                    <svg aria-hidden="true" class="site-header__login-icon" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="9.5" stroke="currentColor" stroke-width="1.75" />
                                        <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="1.75" />
                                        <path d="M6.8 19.8V19c0-1.4 1.1-2.5 2.5-2.5h5.4c1.4 0 2.5 1.1 2.5 2.5v.8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
                                    </svg>
                                </a>
                                <span class="site-header__login-copy">
                                    <a href="{{ route('login', absolute: false) }}" class="site-header__login-main">Zaloguj się</a>
                                    <a href="{{ route('register', absolute: false) }}" class="site-header__login-sub">lub zarejestruj</a>
                                </span>
                                <a href="{{ route('register', absolute: false) }}" class="site-header__login-chevron-link" aria-label="Zarejestruj się">
                                    <svg aria-hidden="true" class="site-header__login-chevron" viewBox="0 0 16 16">
                                        <path d="M4.5 6.5 8 10l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
