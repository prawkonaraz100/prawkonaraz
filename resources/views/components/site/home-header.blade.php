@php
    $user = auth()->user();
    $navigation = app(\App\Support\PublicNavigation::class)->data($user);
    $links = [
        ['label' => 'Baza pytań', 'href' => route('public.questions.hub', absolute: false)],
        ['label' => 'Plany nauki', 'href' => route('public.pricing', absolute: false)],
        ['label' => 'Kursy', 'href' => route('public.course', absolute: false)],
        ['label' => 'Cennik', 'href' => route('public.pricing', absolute: false)],
        ['label' => 'Kontakt', 'href' => route('about.contact', absolute: false)],
    ];
@endphp

<header class="home-site-header home-site-header--awaiting-pointer" data-home-site-header>
    <div class="home-site-header__shell">
        <nav class="home-site-header__nav" aria-label="Nawigacja strony głównej">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <details class="home-site-header__launcher" data-home-header-menu>
            <summary aria-label="Otwórz menu konta">
                <span class="home-site-header__launcher-grid" aria-hidden="true">
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

        <details class="home-site-header__mobile-menu" data-home-header-menu>
            <summary aria-label="Otwórz menu">
                <span class="home-site-header__launcher-grid" aria-hidden="true">
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
