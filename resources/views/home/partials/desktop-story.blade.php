<div class="home-ops">
    @php
        $googleLoginAvailable = filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    @endphp

    <section id="home-reference" class="home-entry" aria-labelledby="home-entry-title">
        <div class="home-entry__shell home-entry__grid home-entry__grid--reference">
            <div class="home-entry__copy" data-home-reveal>
                <h1 id="home-entry-title">
                    <span class="home-entry__seo-kicker">Testy na prawo jazdy <span class="home-entry__seo-kicker-accent">{{ $seoYear }}</span></span>
                    <span class="home-entry__headline-line">Ucz się szybko</span>
                    <span class="home-entry__headline-line home-entry__headline-line--second">zdaj <span class="home-entry__headline-accent">prawko na raz!</span></span>
                </h1>
                <div class="home-entry__conversion">
                    <div class="home-entry__proof" aria-label="Zaufanie kursantów">
                        <img class="home-entry__reference-avatars" src="{{ asset('images/home-reference-avatars-optimized.webp') }}" alt="" width="170" height="44" aria-hidden="true">
                        <span>Zaufało nam <strong>2137</strong> kursantów</span>
                    </div>
                    <a href="{{ auth()->check() ? route('session.index', absolute: false) : route('public.tests', absolute: false) }}" class="home-entry__reference-cta">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <path d="M8 5.5h8M9 3h6a1 1 0 0 1 1 1v2H8V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M7 5.5H5.5A1.5 1.5 0 0 0 4 7v12a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 20 19V7a1.5 1.5 0 0 0-1.5-1.5H17" stroke="currentColor" stroke-width="1.8"/>
                            <path d="m8 13 2.2 2.2L16.5 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>{{ auth()->check() ? 'Kontynuuj naukę' : 'Rozpocznij naukę za darmo' }}</span>
                    </a>
                </div>
                <p class="home-entry__lead">
                    Oficjalna baza pytań na prawo jazdy, testy próbne i proste wyjaśnienia.<br>
                    Przygotuj się do egzaminu teoretycznego krok po kroku.
                </p>
            </div>
        </div>

        <div class="home-entry__shell">
            <div class="home-entry__showcase-meta" data-home-reveal>
                <nav aria-label="Media społecznościowe PrawkoNaRaz">
                    <span>Znajdź nas</span>
                    <a href="https://www.youtube.com/channel/UCSrCDt_Aj1yslMY8sfXFBkg" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="5" fill="currentColor"/><path d="m10 8 6 4-6 4Z" fill="white"/></svg></a>
                    <a href="https://www.instagram.com/prawkonaraz.pl/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.5" cy="6.7" r="1" fill="currentColor" stroke="none"/></svg></a>
                    <a href="https://www.tiktok.com/@prawkonaraz" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M14.8 3v11.3a4.7 4.7 0 1 1-4-4.6v3.1a1.7 1.7 0 1 0 1 1.5V3h3Zm0 0c.5 2.5 1.9 4 4.5 4.5v3.1a8.4 8.4 0 0 1-4.5-1.7"/></svg></a>
                </nav>
            </div>
            @php
                // Add IDs and titles of PrawkoNaRaz YouTube videos here when they are ready.
                // Empty IDs keep the moving layout without loading or opening third-party videos.
                $learningVideoExamples = [
                    ['id' => '', 'title' => ''],
                    ['id' => '', 'title' => ''],
                    ['id' => '', 'title' => ''],
                    ['id' => '', 'title' => ''],
                ];
            @endphp
            <section class="home-showcase home-showcase--videos" aria-labelledby="home-showcase-title" data-home-reveal data-home-learning-videos>
                <div class="home-showcase-videos__heading">
                    <h2 id="home-showcase-title">
                        Zobacz jak się uczyć szybko!
                        <svg class="home-showcase-videos__arrow" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                            <path d="M3 5C24 3 37 18 36 40m-9-10 9 12 6-14" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </h2>
                    <p>Własne filmy PrawkoNaRaz pojawią się tutaj wkrótce.</p>
                </div>

                @for ($row = 0; $row < 3; $row++)
                    <div class="home-showcase-videos__viewport" aria-label="Miejsca na filmy o nauce do egzaminu">
                        <div class="home-showcase-videos__marquee home-showcase-videos__marquee--row-{{ $row + 1 }}">
                            @for ($copy = 0; $copy < 2; $copy++)
                                <div class="home-showcase-videos__group" @if ($copy === 1) aria-hidden="true" @endif>
                                    @foreach (array_merge(array_slice($learningVideoExamples, $row), array_slice($learningVideoExamples, 0, $row)) as $video)
                                        @if ($video['id'] !== '')
                                            <button class="home-showcase-videos__card" type="button" data-home-learning-video-id="{{ $video['id'] }}" data-home-learning-video-title="{{ $video['title'] ?: 'Film PrawkoNaRaz' }}" @if ($copy === 1) tabindex="-1" @endif aria-label="Odtwórz film: {{ $video['title'] ?: 'Film PrawkoNaRaz' }}">
                                                <span class="home-showcase-videos__thumbnail">
                                                    <img src="https://i.ytimg.com/vi/{{ $video['id'] }}/hqdefault.jpg" alt="" loading="lazy" decoding="async" width="480" height="360">
                                                    <span class="home-showcase-videos__play" aria-hidden="true">▶</span>
                                                </span>
                                                <span class="home-showcase-videos__card-copy">
                                                    <strong>{{ $video['title'] ?: 'Film PrawkoNaRaz' }}</strong>
                                                    <span>YouTube · PrawkoNaRaz</span>
                                                </span>
                                            </button>
                                        @else
                                            <div class="home-showcase-videos__card home-showcase-videos__card--empty" aria-label="Miejsce na przyszły film PrawkoNaRaz">
                                                <span class="home-showcase-videos__thumbnail home-showcase-videos__thumbnail--empty" aria-hidden="true"></span>
                                                <span class="home-showcase-videos__card-copy">
                                                    <strong>Materiał w przygotowaniu</strong>
                                                    <span>PrawkoNaRaz · wkrótce</span>
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endfor
                        </div>
                    </div>
                @endfor

                <dialog class="home-showcase-videos__dialog" aria-label="Odtwarzacz filmu o nauce do egzaminu" data-home-learning-video-dialog>
                    <button type="button" class="home-showcase-videos__close" data-home-learning-video-close aria-label="Zamknij film">×</button>
                    <div class="home-showcase-videos__player" data-home-learning-video-player></div>
                    <p data-home-learning-video-caption></p>
                </dialog>
            </section>
        </div>

    </section>

    @include('home.partials.learning-story')

    @if (false)
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
    @endif

    @php
        $contactErrors = $errors->getBag('contact');
        $contactDialogShouldOpen = $contactErrors->any() || session()->has('contact_error');
    @endphp

    <aside
        id="kontakt"
        class="home-contact"
        data-contact-advisor-day="{{ $contactAdvisor['day_index'] }}"
        aria-label="Szybki kontakt z zespołem"
    >
        <button
            type="button"
            class="home-contact__launcher"
            data-home-contact-trigger
            aria-haspopup="dialog"
            aria-label="Masz pytanie? Napisz wiadomość do zespołu PrawkoNaRaz"
        >
            <span class="home-contact__advisor-image">
                <img
                    src="{{ $contactAdvisor['image'] }}"
                    alt=""
                    width="720"
                    height="720"
                    loading="lazy"
                    decoding="async"
                >
            </span>
            <span class="home-contact__question" aria-hidden="true">?</span>
            <span class="home-contact__availability" aria-hidden="true"></span>
        </button>

        @if (session('contact_success'))
            <p class="home-contact__status" role="status">
                <span aria-hidden="true">✓</span>
                {{ session('contact_success') }}
            </p>
        @endif

        <dialog
            class="home-contact__dialog"
            data-home-contact-dialog
            data-home-contact-auto-open="{{ $contactDialogShouldOpen ? 'true' : 'false' }}"
            aria-labelledby="home-contact-dialog-title"
        >
            <div class="home-contact__dialog-panel">
                <button type="button" class="home-contact__dialog-close" data-home-contact-close aria-label="Zamknij formularz">×</button>

                <div class="home-contact__dialog-grid">
                    <div class="home-contact__dialog-aside">
                        <img
                            class="home-contact__dialog-art"
                            src="{{ asset('images/home/contact/contact-dialog-desk-v1.png') }}"
                            alt=""
                            aria-hidden="true"
                            width="1024"
                            height="1536"
                            decoding="async"
                        >
                        <div class="home-contact__dialog-aside-content">
                            <header class="home-contact__dialog-header">
                                <p>Napisz do PrawkoNaRaz</p>
                                <h3 id="home-contact-dialog-title">W czym możemy<br>pomóc?</h3>
                            </header>
                            <p class="home-contact__dialog-intro">
                                Uzupełnij krótki formularz. Odpowiemy na podany przez Ciebie adres e-mail zazwyczaj w ciągu 24 godzin.
                            </p>
                            <ul class="home-contact__benefits" aria-label="Dlaczego warto do nas napisać">
                                <li>
                                    <span class="home-contact__benefit-icon home-contact__benefit-icon--blue" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 5.5h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9l-4.5 3v-3A2 2 0 0 1 3 15.5v-8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8 11.5h.01m4 0h.01m4 0h.01" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/></svg>
                                    </span>
                                    <span><strong>Szybka odpowiedź</strong><small>Zazwyczaj w ciągu 24 godzin</small></span>
                                </li>
                                <li>
                                    <span class="home-contact__benefit-icon home-contact__benefit-icon--yellow" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="7.5" r="3.5" stroke="currentColor" stroke-width="1.8"/><path d="M5 20v-1.5a7 7 0 0 1 14 0V20H5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                    </span>
                                    <span><strong>Pomoc ekspertów</strong><small>Odpowiada nasz zespół</small></span>
                                </li>
                                <li>
                                    <span class="home-contact__benefit-icon home-contact__benefit-icon--green" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><path d="m12 2.5 8 3v6.1c0 5-3.4 8.1-8 9.9-4.6-1.8-8-4.9-8-9.9V5.5l8-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                    </span>
                                    <span><strong>Twoje dane są bezpieczne</strong><small>Nie udostępniamy ich osobom trzecim</small></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="home-contact__dialog-main">
                        @if (session('contact_error'))
                            <p class="home-contact__form-alert" role="alert">{{ session('contact_error') }}</p>
                        @elseif ($contactErrors->any())
                            <p class="home-contact__form-alert" role="alert">Sprawdź zaznaczone pola i spróbuj ponownie.</p>
                        @endif

                        <form method="POST" action="{{ route('about.contact.store', absolute: false) }}" class="home-contact__form" data-home-contact-form>
                    @csrf

                    <div class="home-contact__honeypot" aria-hidden="true">
                        <label for="contact-website">Strona internetowa</label>
                        <input id="contact-website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="home-contact__field-grid">
                        <label class="home-contact__field">
                            <span>Imię</span>
                            <span class="home-contact__input-wrap">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="7.5" r="3.2" stroke="currentColor" stroke-width="1.8"/><path d="M5.5 20v-1.2a6.5 6.5 0 0 1 13 0V20h-13Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                <input
                                    type="text"
                                    name="contact_name"
                                    value="{{ old('contact_name') }}"
                                    maxlength="100"
                                    autocomplete="name"
                                    placeholder="Twoje imię"
                                    required
                                    @class(['is-invalid' => $contactErrors->has('contact_name')])
                                >
                            </span>
                            @error('contact_name', 'contact')
                                <small>{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="home-contact__field">
                            <span>Adres e-mail</span>
                            <span class="home-contact__input-wrap">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <input
                                    type="email"
                                    name="contact_email"
                                    value="{{ old('contact_email') }}"
                                    maxlength="254"
                                    autocomplete="email"
                                    placeholder="np. jan@przyklad.pl"
                                    required
                                    @class(['is-invalid' => $contactErrors->has('contact_email')])
                                >
                            </span>
                            @error('contact_email', 'contact')
                                <small>{{ $message }}</small>
                            @enderror
                        </label>
                    </div>

                    <label class="home-contact__field">
                        <span>Temat</span>
                        <span class="home-contact__input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 6h12M9 12h12M9 18h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="4.5" cy="6" r="1" fill="currentColor"/><circle cx="4.5" cy="12" r="1" fill="currentColor"/><circle cx="4.5" cy="18" r="1" fill="currentColor"/></svg>
                            <select name="contact_topic" required @class(['is-invalid' => $contactErrors->has('contact_topic')])>
                                <option value="">Wybierz temat</option>
                                @foreach ($contactTopics as $topicValue => $topicLabel)
                                    <option value="{{ $topicValue }}" @selected(old('contact_topic') === $topicValue)>{{ $topicLabel }}</option>
                                @endforeach
                            </select>
                        </span>
                        @error('contact_topic', 'contact')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="home-contact__field">
                        <span>Wiadomość</span>
                        <span class="home-contact__input-wrap home-contact__input-wrap--message">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m4 20 4.5-.8L20 7.7a2.1 2.1 0 0 0-3-3L5.5 16.2 4 20Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="m14.5 7.2 2.3 2.3M5.5 16.2l2.3 2.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            <textarea
                                name="contact_message"
                                rows="6"
                                minlength="10"
                                maxlength="5000"
                                placeholder="Napisz krótko, czego dotyczy sprawa..."
                                required
                                @class(['is-invalid' => $contactErrors->has('contact_message')])
                            >{{ old('contact_message') }}</textarea>
                        </span>
                        <span class="home-contact__character-count" data-home-contact-count>{{ mb_strlen((string) old('contact_message', '')) }} / 5000</span>
                        @error('contact_message', 'contact')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                    <p class="home-contact__form-hint">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 10.5v5M12 7.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Im więcej szczegółów podasz, tym szybciej będziemy mogli Ci pomóc.
                    </p>

                    <label class="home-contact__consent">
                        <input type="checkbox" name="contact_consent" value="1" required @checked(old('contact_consent'))>
                        <span>
                            Potwierdzam zapoznanie się z
                            <a href="{{ route('legal.privacy', absolute: false) }}" target="_blank">Polityką prywatności</a>.
                        </span>
                    </label>
                    @error('contact_consent', 'contact')
                        <small class="home-contact__consent-error">{{ $message }}</small>
                    @enderror

                    <div class="home-contact__form-actions">
                        <button type="button" class="home-contact__cancel" data-home-contact-close>Anuluj</button>
                        <button type="submit" class="home-contact__submit">
                            Wyślij wiadomość
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>
                        </form>
                    </div>
                </div>
            </div>
        </dialog>
    </aside>
</div>
