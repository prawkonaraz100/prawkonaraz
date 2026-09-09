@extends('layouts.public-content')

@php
    $friendInvitationsEnabled = app(\App\Support\PaymentRequirementService::class)->requiresPayment();

    $steps = [
        [
            'number' => '01',
            'title' => 'Wybierasz kategorię prawa jazdy',
            'body' => 'Zaczynasz od kategorii, do której się przygotowujesz. Serwis prowadzi Cię do oficjalnej bazy pytań, znaków i materiałów powiązanych z Twoim egzaminem.',
        ],
        [
            'number' => '02',
            'title' => 'Uczysz się w trybie, który pasuje do dnia',
            'body' => 'Możesz przechodzić pytania klasycznie, pracować spokojniej w trybie Zen, wracać do błędów albo sprawdzić się w warunkach zbliżonych do egzaminu.',
        ],
        [
            'number' => '03',
            'title' => 'Wracasz do tego, co naprawdę wymaga poprawy',
            'body' => 'Po sesji widzisz wynik, błędne odpowiedzi i pytania do powtórki. Dzięki temu nie tracisz czasu na materiał, który już dobrze rozumiesz.',
        ],
        [
            'number' => '04',
            'title' => 'Łączysz pytania ze znakami i przepisami',
            'body' => 'Publiczne strony pytań, znaków i przepisów pomagają zrozumieć zasadę stojącą za odpowiedzią, a nie tylko zapamiętać literę A, B albo C.',
        ],
    ];

    $features = [
        [
            'title' => 'Oficjalna baza pytań',
            'body' => 'Publiczne listy pytań według kategorii, pojedyncze strony pytań i wyszukiwarka po treści oraz ID GOV.',
            'href' => route('public.questions.hub'),
            'label' => 'Zobacz bazę pytań',
        ],
        [
            'title' => 'Tryby nauki',
            'body' => 'Nauka klasyczna, spokojniejszy Zen mode, egzamin, powtórki błędów i postęp zapisany na koncie.',
            'href' => auth()->check() ? route('session.index') : route('login'),
            'label' => 'Rozpocznij naukę',
        ],
        [
            'title' => 'Znaki drogowe',
            'body' => 'Katalog znaków z opisami, przykładami zachowania kierowcy i powiązaniami z tematami egzaminacyjnymi.',
            'href' => route('traffic-signs.index'),
            'label' => 'Przejdź do znaków',
        ],
        [
            'title' => 'Przepisy i podstawy prawne',
            'body' => 'Warstwa zaufania, która stopniowo łączy pytania i znaki z podstawami prawnymi oraz datą weryfikacji.',
            'href' => route('public.regulations'),
            'label' => 'Zobacz przepisy',
        ],
        [
            'title' => 'Rankingi',
            'body' => 'Dodatkowa motywacja dla osób, które lubią rywalizację i chcą widzieć swoje wyniki w szerszym kontekście.',
            'href' => route('session.ranking'),
            'label' => 'Zobacz rankingi',
        ],
        [
            'title' => 'Wyjaśnienia i adnotacje',
            'body' => 'Przy trudnych pytaniach pomagamy zauważyć element obrazu, filmu albo znaku, który decyduje o poprawnej odpowiedzi.',
            'href' => route('public.questions.hub'),
            'label' => 'Sprawdź przykłady',
        ],
    ];

    $promotions = [
        [
            'title' => 'Darmowy start',
            'body' => 'Możesz sprawdzić część materiałów bez zobowiązań i zobaczyć, czy sposób nauki pasuje do Ciebie.',
        ],
        ...($friendInvitationsEnabled ? [[
            'title' => 'Zaproszenie od znajomego',
            'body' => 'Jeśli masz kod lub link od znajomego, możesz aktywować dostęp z poziomu dedykowanej strony zaproszenia.',
        ]] : []),
        [
            'title' => 'Plany dla spokojniejszej nauki',
            'body' => 'Cennik pokazuje aktualne warianty dostępu, w tym dłuższe plany dla osób, które chcą uczyć się bez presji.',
        ],
    ];
@endphp

@section('content')
    <section class="content-band">
        <div class="content-shell grid gap-10 py-12 md:py-16 lg:grid-cols-[minmax(0,1fr)_420px] lg:items-center">
            <div>
                <p class="content-kicker">Jak to działa</p>
                <h1 class="mt-4 max-w-4xl text-4xl font-semibold tracking-tight text-slate-950 md:text-6xl md:leading-[1.04]">
                    Uczysz się tak, żeby rozumieć sytuację, nie tylko klikać odpowiedzi.
                </h1>
                <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-700">
                    PrawkoNaRaz łączy oficjalne pytania, tryby nauki, powtórki błędów, znaki drogowe i przepisy w jeden prosty proces. Zaczynasz od kategorii, robisz sesję, widzisz wynik i wracasz dokładnie do tego, co wymaga pracy.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ auth()->check() ? route('session.index') : route('login') }}" class="inline-flex items-center justify-center bg-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-950">
                        Rozpocznij naukę
                    </a>
                    <a href="{{ route('public.pricing') }}" class="inline-flex items-center justify-center border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:border-slate-950">
                        Zobacz cennik
                    </a>
                </div>
            </div>

            <aside class="border border-slate-200 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.08)]">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Najprościej</p>
                <ol class="mt-5 space-y-4">
                    @foreach ($steps as $step)
                        <li class="grid grid-cols-[44px_minmax(0,1fr)] gap-4">
                            <span class="flex h-11 w-11 items-center justify-center bg-slate-950 text-sm font-semibold text-white">{{ $step['number'] }}</span>
                            <span>
                                <span class="block text-base font-semibold text-slate-950">{{ $step['title'] }}</span>
                                <span class="mt-1 block text-sm leading-6 text-slate-600">{{ $step['body'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            </aside>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell py-10 md:py-14">
            <div class="max-w-3xl">
                <p class="content-kicker">Funkcje</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">
                    Wszystkie moduły mają pomagać w jednej rzeczy: podjąć dobrą decyzję na egzaminie.
                </h2>
                <p class="content-muted mt-4 text-base leading-7">
                    Nie traktujemy bazy pytań jak długiej tabeli do przeklikania. Każdy moduł ma swoją rolę: szybkie przejście materiału, spokojne zrozumienie, powtórka błędów albo sprawdzenie gotowości.
                </p>
            </div>

            <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($features as $feature)
                    <article class="flex min-h-[230px] flex-col justify-between border border-slate-200 bg-white p-6">
                        <div>
                            <h3 class="text-xl font-semibold text-slate-950">{{ $feature['title'] }}</h3>
                            <p class="content-muted mt-3 text-sm leading-6">{{ $feature['body'] }}</p>
                        </div>
                        <a href="{{ $feature['href'] }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-red-700 transition hover:text-slate-950">
                            {{ $feature['label'] }}
                            <span aria-hidden="true">→</span>
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell grid gap-10 py-10 md:py-14 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:items-start">
            <div>
                <p class="content-kicker">Promocje i dostęp</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">
                    Możesz zacząć ostrożnie, a pełny dostęp dobrać wtedy, gdy wiesz, że serwis Ci pomaga.
                </h2>
                <p class="content-muted mt-5 text-base leading-7">
                    @if ($friendInvitationsEnabled)
                        Aktualne formy dostępu pokazujemy w cenniku. Jeśli dostajesz kod od znajomego, wpisujesz go na stronie zaproszenia i przechodzisz przez normalny, bezpieczny proces konta.
                    @else
                        Dostęp do platformy jest obecnie otwarty. Załóż konto, potwierdź adres e-mail i rozpocznij naukę bez kupowania planu.
                    @endif
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('public.pricing') }}" class="inline-flex items-center justify-center bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-700">
                        Sprawdź plany
                    </a>
                    @if ($friendInvitationsEnabled)
                        <a href="{{ route('friend-invitations.code.create') }}" class="inline-flex items-center justify-center border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:border-slate-950">
                            Mam kod zaproszenia
                        </a>
                    @endif
                </div>
            </div>

            <div class="divide-y divide-slate-200 border-y border-slate-200">
                @foreach ($promotions as $promotion)
                    <article class="py-6">
                        <h3 class="text-xl font-semibold text-slate-950">{{ $promotion['title'] }}</h3>
                        <p class="content-muted mt-3 text-base leading-7">{{ $promotion['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell py-10 md:py-14">
            <div class="grid gap-8 border-y border-slate-200 py-8 md:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] md:items-center">
                <div>
                    <p class="content-kicker">Zaufanie</p>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">
                        Chcesz wiedzieć, kto odpowiada za treści?
                    </h2>
                </div>

                <div>
                    <p class="content-muted text-base leading-7">
                        Strona „O nas” pokazuje osoby odpowiedzialne za warstwę merytoryczną, bezpieczeństwo ruchu drogowego, znaki i recenzję treści szkoleniowych.
                    </p>
                    <a href="{{ route('about.organization') }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-950 transition hover:text-red-700">
                        Poznaj zespół
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
