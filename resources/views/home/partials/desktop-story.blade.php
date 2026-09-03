<div class="home-ops">
    <section class="home-ops__hero" aria-labelledby="home-ops-title">
        <div class="home-ops__shell home-ops__hero-grid" data-home-reveal>
            <div class="home-ops__hero-title">
                <span class="home-ops__rule" aria-hidden="true"></span>
                <p class="home-ops__hero-kicker">Oficjalna baza {{ now()->year }}</p>
                <h1 id="home-ops-title">
                    Testy na<br>
                    prawo jazdy<em>.</em>
                </h1>
                <p class="home-ops__hero-title-note">Teoria bez zgadywania.</p>
            </div>

            <figure class="home-ops__hero-person">
                <span class="home-ops__hero-person-backdrop" aria-hidden="true"></span>
                <img src="{{ $heroPortrait }}" alt="Kierująca samochodem z kluczykami po zdanym egzaminie">
            </figure>

            <div class="home-ops__hero-copy">
                <span class="home-ops__rule" aria-hidden="true"></span>
                <p class="home-ops__hero-promise">
                    Nie klikaj w ciemno.<br>
                    <strong>Zrozum, dlaczego.</strong>
                </p>
                <p class="home-ops__hero-description">
                    PrawkoNaRaz pokazuje najważniejszy detal, tłumaczy każdą odpowiedź i prowadzi Cię przez oficjalne pytania aż do pozytywnego wyniku egzaminacyjnego.
                </p>
                <p class="home-ops__hero-source"><span aria-hidden="true">✓</span> Pytania z państwowej bazy</p>
                <p class="home-ops__hero-source"><span aria-hidden="true">✓</span> Inteligentna platforma, która prowadzi Cię krok po kroku</p>
            </div>
        </div>

        <div class="home-ops__signal-band" aria-hidden="true">
            <div class="home-ops__signal-panel home-ops__signal-panel--left">
                <span class="home-ops__road-mark"></span>
                <span class="home-ops__sign-unit">
                    <span class="home-ops__sign-ring"></span>
                    <span class="home-ops__sign-post"></span>
                    <img src="{{ asset('traffic-signs/sign-cutouts/c-12-ruch-okrezny.png') }}" alt="">
                </span>
            </div>
            <div class="home-ops__signal-core"></div>
            <div class="home-ops__signal-panel home-ops__signal-panel--right">
                <span class="home-ops__road-mark"></span>
                <span class="home-ops__sign-unit">
                    <span class="home-ops__sign-ring"></span>
                    <span class="home-ops__sign-post"></span>
                    <img src="{{ asset('traffic-signs/sign-cutouts/b-33-ograniczenie-predkosci.png') }}" alt="">
                </span>
            </div>
        </div>

        <div class="home-ops__shell home-ops__product-wrap">
            <a href="{{ route('public.tests', absolute: false) }}" class="home-ops__primary-cta">
                Rozpocznij Speedrun
                <span aria-hidden="true">→</span>
            </a>
            <div class="home-ops__product-frame">
                <picture>
                    <source media="(max-width: 767px)" srcset="{{ $mobileAppScreen }}">
                    <img
                        src="{{ $heroProduct }}"
                        alt="Widok platformy podczas rozwiązywania pytania egzaminacyjnego"
                        loading="eager"
                        fetchpriority="high"
                        decoding="async"
                    >
                </picture>
            </div>
        </div>
    </section>

    <section class="home-ops__app-teaser" aria-labelledby="home-ops-app-teaser-title">
        <div class="home-ops__shell home-ops__app-teaser-grid" data-home-reveal>
            <div class="home-ops__app-teaser-intro">
                <p class="home-ops__app-teaser-label">PrawkoNaRaz na telefonie</p>
                <h2 id="home-ops-app-teaser-title">Najlepsza, najbardziej intuicyjna aplikacja mobilna do nauki teorii<br>na prawo jazdy<em>.</em></h2>
            </div>

            <div class="home-ops__app-teaser-copy">
                <p>Cała nauka jest pod ręką: pytania, wyjaśnienia i Twój postęp - bez szukania po ekranie.</p>
                <a href="#home-ops-mobile" class="home-ops__app-teaser-link">
                    Zobacz aplikację
                    <span aria-hidden="true">↓</span>
                </a>
            </div>

        </div>
    </section>

    <section id="home-ops-mobile" class="home-ops__mobile-promo" aria-labelledby="home-ops-mobile-title">
        <div class="home-ops__shell home-ops__mobile-promo-grid">
            <div class="home-ops__mobile-promo-visual" data-home-reveal aria-hidden="true">
                <div class="home-ops__phone home-ops__phone--back">
                    <img src="{{ asset('pwa/icon-192.png') }}" alt="">
                    <span>PrawkoNaRaz</span>
                    <small>na ekranie głównym</small>
                </div>
                <div class="home-ops__phone home-ops__phone--front">
                    <span class="home-ops__phone-speaker"></span>
                    <img src="{{ $mobileAppScreen }}" alt="" loading="lazy" decoding="async">
                </div>
                <div class="home-ops__mobile-promo-badge">
                    <img src="{{ asset('pwa/icon-192.png') }}" alt="">
                    <span><strong>Jedno konto</strong>Postęp zawsze z Tobą</span>
                </div>
            </div>

            <div class="home-ops__mobile-promo-copy" data-home-reveal>
                <p class="home-ops__tag">Nauka na telefonie</p>
                <h2 id="home-ops-mobile-title">Twoja nauka<br>jedzie z Tobą<em>.</em></h2>
                <p class="home-ops__mobile-promo-lead">
                    Otwórz PrawkoNaRaz na telefonie i dodaj aplikację do ekranu głównego.<br>
                    Postęp, błędne pytania i aktywna sesja zostają na Twoim koncie.
                </p>
                <ul class="home-ops__mobile-promo-list">
                    <li><span aria-hidden="true">✓</span>Uczysz się, gdzie chcesz</li>
                    <li><span aria-hidden="true">✓</span>Wracasz tam, gdzie skończyłeś</li>
                    <li><span aria-hidden="true">✓</span>Dodajesz do ekranu głównego</li>
                </ul>
                <div class="home-ops__mobile-promo-actions">
                    <a href="{{ route('session.index', absolute: false) }}" class="home-ops__primary-cta home-ops__primary-cta--light">
                        Otwórz na telefonie
                        <span aria-hidden="true">→</span>
                    </a>
                    <p><i aria-hidden="true"></i>Google Play: w przygotowaniu</p>
                </div>
            </div>
        </div>
    </section>

    @php
        $comparisonGroups = [
            [
                'label' => 'Pytania i wyjaśnienia',
                'rows' => [
                    [
                        'label' => 'Źródło i aktualność',
                        'value' => 'Oficjalna baza państwowa z numerem źródłowym',
                        'random' => 'Często bez źródła i daty aktualizacji',
                        'notes' => 'Materiały przepisywane ręcznie',
                        'image' => $proofExplanation,
                        'alt' => 'Publiczna karta pytania z oficjalnym źródłem i datą aktualizacji',
                        'eyebrow' => 'Oficjalne pytania',
                        'title' => 'Wiesz, z jakiego pytania się uczysz',
                        'description' => 'Karta pytania pokazuje numer źródłowy, kategorię i datę aktualizacji. Powiązane treści prowadzą dalej bez szukania materiałów w kilku miejscach.',
                        'point_one' => 'Pytania są przypisane do właściwych kategorii prawa jazdy.',
                        'point_two' => 'Źródło i aktualność są widoczne bezpośrednio przy pytaniu.',
                    ],
                    [
                        'label' => 'Po błędnej odpowiedzi',
                        'value' => 'Wyjaśnienie, podstawa prawna i haczyk egzaminacyjny',
                        'random' => 'Zwykle tylko poprawny wariant',
                        'notes' => 'Samodzielne szukanie przepisu',
                        'image' => $proofExplanation,
                        'alt' => 'Wyjaśnienie pytania z opisem sytuacji i powiązanym znakiem drogowym',
                        'eyebrow' => 'Wyjaśnienia po odpowiedzi',
                        'title' => 'Błąd od razu zamienia się w lekcję',
                        'description' => 'Po odpowiedzi otrzymujesz proste wyjaśnienie sytuacji, właściwą zasadę oraz dodatkowe sekcje, takie jak haczyk egzaminacyjny, najczęstsze błędy i „Nie pomyl z”.',
                        'point_one' => 'Nie musisz otwierać osobno przepisów i wyszukiwarki.',
                        'point_two' => 'Powiązany znak lub sytuacja są pokazane w tym samym kontekście.',
                    ],
                    [
                        'label' => 'Obraz, film i audio',
                        'value' => 'Media, odsłuch i oznaczenia ważnych detali',
                        'random' => 'Podstawowy odtwarzacz bez omówienia',
                        'notes' => 'Statyczne materiały bez kontekstu',
                        'image' => $proofExplanation,
                        'alt' => 'Pytanie wideo z możliwością odsłuchania wyjaśnienia',
                        'eyebrow' => 'Materiał pytania',
                        'title' => 'Widzisz i słyszysz to, co ma znaczenie',
                        'description' => 'Platforma obsługuje pytania tekstowe, obrazy i filmy, a wybrane treści można odsłuchać. Adnotacje pomagają zauważyć detal decydujący o odpowiedzi.',
                        'point_one' => 'Film można zatrzymać i przeanalizować razem z wyjaśnieniem.',
                        'point_two' => 'Audio pomaga uczyć się bez ciągłego czytania ekranu.',
                    ],
                    [
                        'label' => 'Trudność pytania',
                        'value' => 'Statystyki odpowiedzi kursantów i poziom trudności',
                        'random' => 'Brak wiarygodnej próby odpowiedzi',
                        'notes' => 'Brak danych o typowych błędach',
                        'image' => $proofExplanation,
                        'alt' => 'Statystyki odpowiedzi kursantów z procentami i poziomem trudności',
                        'eyebrow' => 'Statystyki pytania',
                        'title' => 'Od razu widzisz, gdzie mylą się inni',
                        'description' => 'Publiczne statystyki pokazują rozkład odpowiedzi, liczebność próby, datę aktualizacji oraz wyliczony poziom trudności pytania.',
                        'point_one' => 'Wyniki opierają się na faktycznych odpowiedziach kursantów.',
                        'point_two' => 'Łatwiej rozpoznasz pytania, którym warto poświęcić więcej czasu.',
                    ],
                ],
            ],
            [
                'label' => 'Nauka i powtórki',
                'rows' => [
                    [
                        'label' => 'Plan kategorii',
                        'value' => 'Działy i filtry dopasowane do wybranego pojazdu',
                        'random' => 'Jeden wspólny zbiór pytań',
                        'notes' => 'Osobne źródła i ręczny plan',
                        'image' => $proofDashboard,
                        'alt' => 'Panel nauki kategorii B z działami i filtrami pytań',
                        'eyebrow' => 'Plan nauki',
                        'title' => 'Uczysz się dokładnie swojej kategorii',
                        'description' => 'Wybierasz dział, zakres pytań i ich status. System oddziela pytania podstawowe od specjalistycznych oraz pokazuje postęp w całej kategorii.',
                        'point_one' => 'Możesz ćwiczyć nowe, błędne, poprawne lub utrwalone pytania.',
                        'point_two' => 'Stała kolejność pozwala przejść materiał krok po kroku.',
                    ],
                    [
                        'label' => 'Tryb nauki',
                        'value' => 'Nauka klasyczna albo spokojny Zen mode',
                        'random' => 'Jeden sposób rozwiązywania',
                        'notes' => 'Własny układ bez zapisu postępu',
                        'image' => $proofDashboard,
                        'alt' => 'Panel z wyborem nauki klasycznej, trybu Zen, trenera pamięci i egzaminu',
                        'eyebrow' => 'Tryby nauki',
                        'title' => 'Dopasowujesz ekran do swojego tempa',
                        'description' => 'Nauka klasyczna prowadzi przez materiał i wyjaśnienia, a Zen mode ogranicza rozpraszacze, zachowując ten sam postęp na koncie.',
                        'point_one' => 'Tryb możesz dobrać do nauki, powtórki albo pełnego skupienia.',
                        'point_two' => 'Zmiana widoku nie zeruje przerobionego materiału.',
                    ],
                    [
                        'label' => 'Pytania do poprawy',
                        'value' => 'Automatyczna lista błędów z własnymi ustawieniami',
                        'random' => 'Powrót do całego zestawu',
                        'notes' => 'Ręczne zapisywanie numerów pytań',
                        'image' => $proofIncorrectQuestions,
                        'alt' => 'Lista pytań do poprawy z filtrem tematów i przyciskiem powtórki',
                        'eyebrow' => 'Lista błędnych pytań',
                        'title' => 'Trudne pytania nie giną po zakończeniu testu',
                        'description' => 'Błędne odpowiedzi trafiają na zarządzaną listę. Możesz filtrować ją tematami, uruchomić osobną sesję i zdecydować, kiedy pytanie ma zniknąć.',
                        'point_one' => 'Jednym przyciskiem uruchamiasz powtórkę całej listy.',
                        'point_two' => 'Automatyczne usuwanie po dobrej odpowiedzi jest opcjonalne.',
                    ],
                    [
                        'label' => 'Trener pamięci',
                        'value' => 'Codzienny plan oparty na tym, co naprawdę pamiętasz',
                        'random' => 'Losowe powtarzanie pytań',
                        'notes' => 'Ręczne układanie fiszek',
                        'image' => $proofMemoryTrainer,
                        'alt' => 'Trener pamięci z planem dziennym i mapą pytań do odzyskania',
                        'eyebrow' => 'Inteligentne powtórki',
                        'title' => 'Najpierw wracają pytania, które zaczynasz zapominać',
                        'description' => 'Plan dzienny bierze pod uwagę historię odpowiedzi i termin powtórki. Rozdziela pytania na wymagające odzyskania, będące w nauce i już stabilne.',
                        'point_one' => 'System ogranicza dokładanie nowych pytań, gdy zalegają pilne powtórki.',
                        'point_two' => 'Przed startem widzisz wielkość planu i przewidywany czas sesji.',
                    ],
                    [
                        'label' => 'Znaki drogowe',
                        'value' => 'Osobny trening znaków, podobnych par i opisów',
                        'random' => 'Znaki wymieszane z testami',
                        'notes' => 'Kartki lub statyczne zestawienia',
                        'image' => $proofTrafficSigns,
                        'alt' => 'Panel nauki znaków drogowych z kategoriami i wariantami treningu',
                        'eyebrow' => 'Nauka znaków',
                        'title' => 'Najpierw rozpoznajesz znak, potem sytuację',
                        'description' => 'Osobny moduł dzieli znaki na grupy i pozwala trenować je mieszanie, porównywać podobne znaki albo przechodzić od opisu do symbolu.',
                        'point_one' => 'Postęp jest liczony osobno dla każdej grupy znaków.',
                        'point_two' => 'Podobne znaki można ćwiczyć bez przekopywania całej bazy.',
                    ],
                ],
            ],
            [
                'label' => 'Egzamin i postęp',
                'rows' => [
                    [
                        'label' => 'Egzamin próbny',
                        'value' => '32 pytania, 25 minut i struktura 20 + 12',
                        'random' => 'Różne zasady i długość testu',
                        'notes' => 'Brak pełnej symulacji',
                        'image' => $proofExam,
                        'alt' => 'Konfiguracja próbnego egzaminu z 32 pytaniami i czasem 25 minut',
                        'eyebrow' => 'Symulacja WORD',
                        'title' => 'Próbujesz dokładnie takiego tempa jak na egzaminie',
                        'description' => 'Tryb egzaminacyjny blokuje przypadkowe ustawienia i uruchamia pełną kategorię: 32 pytania, układ 20 + 12 oraz 25 minut na rozwiązanie.',
                        'point_one' => 'Punktacja i przebieg są oddzielone od zwykłego trybu nauki.',
                        'point_two' => 'Po zakończeniu dostajesz wynik całej sesji.',
                    ],
                    [
                        'label' => 'Postęp',
                        'value' => 'Skuteczność, poprawne i błędne odpowiedzi oraz tempo',
                        'random' => 'Wynik tylko z ostatniego testu',
                        'notes' => 'Ręczne liczenie postępów',
                        'image' => $proofDashboard,
                        'alt' => 'Panel postępu z procentem ukończenia i liczbą poprawnych odpowiedzi',
                        'eyebrow' => 'Postęp kursanta',
                        'title' => 'Widzisz nie tylko wynik, ale drogę do celu',
                        'description' => 'Panel łączy postęp kategorii, liczbę poprawnych i błędnych odpowiedzi oraz aktywną sesję, do której możesz wrócić.',
                        'point_one' => 'Dane są liczone dla wybranej kategorii prawa jazdy.',
                        'point_two' => 'Szczegółowe statystyki pomagają wybrać kolejny dział do nauki.',
                    ],
                    [
                        'label' => 'Ranking 1 na 1',
                        'value' => 'Pojedynki na żywo, ELO i historia wyników',
                        'random' => 'Samotne rozwiązywanie zestawów',
                        'notes' => 'Brak rywalizacji w czasie rzeczywistym',
                        'image' => $proofRanking,
                        'alt' => 'Ranking kursantów z punktami ELO i profilem gracza',
                        'eyebrow' => 'Tryb rankingowy',
                        'title' => 'Sprawdzasz wiedzę pod presją czasu i przeciwnika',
                        'description' => 'Tryb rankingowy dobiera przeciwnika w tej samej kategorii, prowadzi mecz pytanie po pytaniu i aktualizuje wynik ELO.',
                        'point_one' => 'Wynik uwzględnia poprawność i tempo odpowiedzi.',
                        'point_two' => 'Po meczu otrzymujesz porównanie obu graczy.',
                    ],
                ],
            ],
            [
                'label' => 'Dostępność i urządzenia',
                'rows' => [
                    [
                        'label' => 'Kontynuacja nauki',
                        'value' => 'Aktywna sesja i postęp zapisane na koncie',
                        'random' => 'Test zaczynany od początku',
                        'notes' => 'Notatki rozproszone między urządzeniami',
                        'image' => $proofDashboard,
                        'alt' => 'Panel z przyciskiem kontynuowania aktywnej sesji nauki',
                        'eyebrow' => 'Zapis sesji',
                        'title' => 'Wracasz dokładnie tam, gdzie skończyłeś',
                        'description' => 'Panel pokazuje bieżący dział i aktywną sesję. To samo konto przechowuje postęp, listę błędów i plan powtórek.',
                        'point_one' => 'Nie musisz pamiętać numeru ostatniego pytania.',
                        'point_two' => 'Zmiana urządzenia nie rozdziela historii nauki.',
                    ],
                    [
                        'label' => 'Nauka na telefonie',
                        'value' => 'Instalacja na ekranie głównym bez osobnego konta',
                        'random' => 'Strona bez ciągłości sesji',
                        'notes' => 'Osobne pliki i aplikacje',
                        'image' => $mobileAppScreen,
                        'alt' => 'Mobilny widok pytania PrawkoNaRaz na telefonie',
                        'eyebrow' => 'Telefon i PWA',
                        'title' => 'Pełna nauka mieści się na ekranie telefonu',
                        'description' => 'PrawkoNaRaz można dodać do ekranu głównego. Mobilny widok zachowuje pytania, filmy, wyjaśnienia i postęp z tego samego konta.',
                        'point_one' => 'Nie zakładasz drugiego konta dla aplikacji mobilnej.',
                        'point_two' => 'Możesz płynnie przejść z komputera na telefon.',
                    ],
                    [
                        'label' => 'Dostępna forma nauki',
                        'value' => 'Zen mode, odsłuch treści i ścieżka PJM',
                        'random' => 'Najczęściej jeden standardowy widok',
                        'notes' => 'Dostosowanie materiałów we własnym zakresie',
                        'image' => $proofPjm,
                        'alt' => 'Panel nauki pytań w polskim języku migowym',
                        'eyebrow' => 'Różne potrzeby kursantów',
                        'title' => 'Materiał możesz odbierać na więcej niż jeden sposób',
                        'description' => 'Spokojny tryb Zen ogranicza rozpraszacze, wybrane treści mają odsłuch, a rozwijana ścieżka PJM prowadzi przez dostępne tłumaczenia działami.',
                        'point_one' => 'PJM ma własny widok postępu i listę działów.',
                        'point_two' => 'Dostępność nie wymaga rezygnacji z zapisu wyników.',
                    ],
                ],
            ],
        ];
    @endphp

    <section class="home-ops__compare" aria-labelledby="home-ops-compare-title">
        <div class="home-ops__shell">
            <header data-home-reveal>
                <p class="home-ops__tag">Sprawdź, co dostajesz</p>
                <h2 id="home-ops-compare-title">Dlaczego nasz sposób nauki działa najlepiej.</h2>
                <p>Każdy element platformy został zaprojektowany z myślą o tym, aby jak najszybciej pomóc Ci zapamiętać pytanie, odpowiedź i konkretną sytuację drogową, z którą możesz spotkać się na drodze.<br>Naszym celem jest maksymalne skrócenie czasu nauki i lepsze przygotowanie Cię do państwowego egzaminu.</p>
            </header>

            <div class="home-ops__comparison" data-home-reveal>
                <div class="home-ops__comparison-head">
                    <span class="home-ops__comparison-corner">Obszar nauki</span>
                    <strong class="home-ops__comparison-brand"><span>PrawkoNaRaz</span><small>Zobacz prawdziwy ekran</small></strong>
                    <strong class="home-ops__comparison-alternative">Losowe testy</strong>
                    <strong class="home-ops__comparison-alternative">Notatki i fiszki</strong>
                </div>
                @foreach ($comparisonGroups as $group)
                    <p class="home-ops__comparison-group"><span>{{ $group['label'] }}</span></p>
                    @foreach ($group['rows'] as $row)
                        <div class="home-ops__comparison-row">
                            <span class="home-ops__comparison-label">{{ $row['label'] }}</span>
                            <button
                                type="button"
                                class="home-ops__comparison-proof"
                                data-home-proof-trigger
                                data-proof-image="{{ $row['image'] }}"
                                data-proof-alt="{{ $row['alt'] }}"
                                data-proof-eyebrow="{{ $row['eyebrow'] }}"
                                data-proof-title="{{ $row['title'] }}"
                                data-proof-description="{{ $row['description'] }}"
                                data-proof-point-one="{{ $row['point_one'] }}"
                                data-proof-point-two="{{ $row['point_two'] }}"
                                aria-haspopup="dialog"
                            >
                                <strong>{{ $row['value'] }}</strong>
                                <small>Zobacz ekran <span aria-hidden="true">↗</span></small>
                            </button>
                            <span class="home-ops__comparison-alternative-copy">{{ $row['random'] }}</span>
                            <span class="home-ops__comparison-alternative-copy">{{ $row['notes'] }}</span>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        <dialog class="home-ops__proof-dialog" data-home-proof-dialog aria-labelledby="home-ops-proof-title">
            <div class="home-ops__proof-panel">
                <button type="button" class="home-ops__proof-close" data-home-proof-close aria-label="Zamknij podgląd">×</button>
                <figure class="home-ops__proof-media">
                    <img data-home-proof-image src="" alt="" loading="lazy" decoding="async">
                </figure>
                <div class="home-ops__proof-copy">
                    <p data-home-proof-eyebrow></p>
                    <h3 id="home-ops-proof-title" data-home-proof-title></h3>
                    <p data-home-proof-description></p>
                    <ul data-home-proof-points></ul>
                    <button type="button" class="home-ops__proof-done" data-home-proof-close>Rozumiem</button>
                </div>
            </div>
        </dialog>
    </section>
</div>
