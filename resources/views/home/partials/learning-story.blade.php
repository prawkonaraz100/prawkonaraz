<section class="home-learning" aria-labelledby="home-learning-title">
    <div class="home-entry__shell">
        @php
            $jakubQuoteAuthor = $learningQuoteAuthors->get('jakub-wisniewski');
            $katarzynaQuoteAuthor = $learningQuoteAuthors->get('katarzyna-wisniewska');
        @endphp

        @include('home.partials.learning-paths')

        <section
            class="home-learning__quote"
            aria-labelledby="home-expert-opinions-title"
            data-home-expert-carousel
            data-home-reveal
        >
            <p class="home-learning__quote-lead" id="home-expert-opinions-title">
                Eksperci o nauce z PrawkoNaRaz.pl
            </p>

            <div class="home-learning__quote-divider" aria-hidden="true">
                <span></span>
                <strong>”</strong>
                <span></span>
            </div>

            <div class="home-learning__expert-carousel">
                <button
                    type="button"
                    class="home-learning__expert-control home-learning__expert-control--previous"
                    data-home-expert-scroll="previous"
                    aria-label="Pokaż poprzednią opinię eksperta"
                >
                    <span aria-hidden="true">←</span>
                </button>

                <div
                    class="home-learning__expert-rail"
                    data-home-expert-rail
                    tabindex="0"
                    aria-label="Opinie ekspertów o PrawkoNaRaz"
                >
                    <article class="home-learning__expert-slide">
                        <div class="home-learning__quote-content">
                            <div class="home-learning__quote-author">
                                @if ($jakubQuoteAuthor)
                                    <a href="{{ route('content-authors.show', $jakubQuoteAuthor->slug, absolute: false) }}">
                                        <img src="{{ asset($jakubQuoteAuthor->photo_path) }}" alt="{{ $jakubQuoteAuthor->name }}" width="64" height="64">
                                        <strong>{{ $jakubQuoteAuthor->name }}</strong>
                                    </a>
                                    <span>{{ $jakubQuoteAuthor->job_title }}</span>
                                @endif
                            </div>

                            <blockquote>
                                <h3>Jak zdać egzamin państwowy na prawo jazdy? Oceniamy platformę PrawkoNaRaz.pl</h3>
                                <p>
                                    „Jako były egzaminator WORD doskonale wiem, że o negatywnym wyniku testu teoretycznego
                                    najczęściej decydują błędy wynikające ze stresu oraz zaskoczenie formą pytań. Kluczem do
                                    sukcesu na egzaminie państwowym jest trening na materiałach, które w skali 1:1 odwzorowują
                                    system egzaminacyjny.
                                </p>
                                <p>
                                    Platforma <strong>PrawkoNaRaz.pl</strong> zawiera aktualną i pełną bazę pytań WORD
                                    obowiązującą we wszystkich ośrodkach w kraju. Wysoki poziom szczegółowości multimediów
                                    — zdjęć i filmów — pozwala kursantowi bezbłędnie ocenić pierwszeństwo przejazdu czy
                                    właściwy manewr na drodze.
                                </p>
                                <p><strong>Zakres bazy egzaminacyjnej według kategorii:</strong></p>
                                <ul>
                                    <li><strong>Kategorie A, AM, A1, A2</strong> — motocykle</li>
                                    <li><strong>Kategorie B, B1</strong> — samochody osobowe</li>
                                    <li><strong>Kategorie C, C1, D, D1</strong> — transport zawodowy i autobusy</li>
                                    <li><strong>Kategoria T</strong> — ciągniki rolnicze</li>
                                </ul>
                                <p>
                                    <strong>Rekomendacja eksperta:</strong> Aby bezstresowo zaliczyć oficjalny test
                                    teoretyczny, unikaj nauki „na pamięć” w ostatnią noc. Zamiast tego poświęć 15 minut
                                    dziennie na systematyczne powtórki na PrawkoNaRaz.pl. Pozwoli Ci to opanować zarówno
                                    proste, jak i trudniejsze zagadnienia.”
                                </p>
                            </blockquote>
                        </div>
                    </article>

                    <article class="home-learning__expert-slide">
                        <div class="home-learning__quote-content">
                            <div class="home-learning__quote-author">
                                @if ($katarzynaQuoteAuthor)
                                    <a href="{{ route('content-authors.show', $katarzynaQuoteAuthor->slug, absolute: false) }}">
                                        <img src="{{ asset($katarzynaQuoteAuthor->photo_path) }}" alt="{{ $katarzynaQuoteAuthor->name }}" width="64" height="64">
                                        <strong>{{ $katarzynaQuoteAuthor->name }}</strong>
                                    </a>
                                    <span>{{ $katarzynaQuoteAuthor->job_title }}</span>
                                @endif
                            </div>

                            <blockquote>
                                <h3>Szybka i skuteczna nauka teorii na prawo jazdy z PrawkoNaRaz.pl</h3>
                                <p>
                                    „Prawidłowe przygotowanie do testu teoretycznego to nie tylko krok do zdobycia dokumentu,
                                    ale przede wszystkim fundament bezpiecznego poruszania się po drogach. Z perspektywy
                                    organizacji ruchu kluczowa jest nauka na zweryfikowanym i aktualnym materiale.
                                </p>
                                <p>
                                    Dostępna na <strong>PrawkoNaRaz.pl</strong> baza testów na prawo jazdy jest w pełni
                                    zgodna z oficjalnymi wytycznymi państwowymi. Oznacza to, że arkusz w serwisie zawiera
                                    identyczny zestaw pytań, zdjęć i multimediów, jaki otrzymasz w swoim lokalnym WORD-zie.
                                    Wysoka rozdzielczość materiałów wizualnych ułatwia szybką analizę oznakowania i manewrów.
                                </p>
                                <p><strong>System wspiera kandydatów na kierowców wszystkich grup:</strong></p>
                                <ul>
                                    <li><strong>Kategorie AM, A1, A2, A</strong> — dla miłośników jednośladów</li>
                                    <li><strong>Kategorie B1, B</strong> — dla kierowców pojazdów osobowych</li>
                                    <li><strong>Kategorie C1, C, D1, D</strong> — dla przyszłych kierowców zawodowych</li>
                                    <li><strong>Kategoria T</strong> — dla operatorów sprzętu rolniczego</li>
                                </ul>
                                <p>
                                    Regularne rozwiązywanie arkuszy w dedykowanym trybie nauki PrawkoNaRaz.pl skutecznie
                                    utrwala przepisy i pozwala podejść do egzaminu teoretycznego z pełnym spokojem.”
                                </p>
                            </blockquote>
                        </div>
                    </article>
                </div>

                <button
                    type="button"
                    class="home-learning__expert-control home-learning__expert-control--next"
                    data-home-expert-scroll="next"
                    aria-label="Pokaż następną opinię eksperta"
                >
                    <span aria-hidden="true">→</span>
                </button>
            </div>

            <p class="home-learning__expert-position" aria-live="polite">
                <span data-home-expert-position>1 / 2</span>
            </p>
        </section>

        <header class="home-learning__intro home-learning__intro--centered" data-home-reveal>
            <p class="home-learning__eyebrow">Darmowe testy na prawo jazdy</p>
            <h2 id="home-learning-title">Pełne przygotowanie do egzaminu teoretycznego</h2>
        </header>

        <section class="home-learning__personalization" aria-labelledby="home-learning-modes-title">
            <div class="home-learning__feature-list">
                <article class="home-learning__feature home-learning__feature--explanations-overview" data-home-reveal>
                    <div class="home-learning__feature-copy home-learning__feature-copy--explanations-overview">
                        <p><strong>Wyjaśniamy, dlaczego odpowiedź jest poprawna.</strong> Pokazujemy najważniejszą zasadę i element sytuacji, który naprawdę decyduje o odpowiedzi.</p>
                        <p><strong>Znaki i grafiki pomagają zrozumieć.</strong> Ważne znaki drogowe pokazujemy bezpośrednio przy wyjaśnieniu, żeby łatwiej połączyć obraz z przepisem.</p>
                        <p><strong>Pokazujemy haczyki i typowe błędy.</strong> Wiesz, co może zmylić Cię na egzaminie i na co zwrócić uwagę przy podobnych pytaniach.</p>
                        <p><strong>Sprawdzisz też statystyki i poziom trudności.</strong> Zobaczysz, jak z pytaniem radzili sobie inni kursanci.</p>
                        <p><strong>Podstawa prawna jest zawsze pod ręką.</strong> Jeśli chcesz, możesz sprawdzić konkretny przepis, na którym opiera się wyjaśnienie.</p>
                    </div>
                    <figure class="home-learning__feature-screen home-learning__feature-screen--sticky">
                        <button
                            type="button"
                            class="home-learning__image-trigger"
                            data-home-learning-image-trigger
                            data-image-src="{{ $explanationsOverviewScreen }}"
                            data-image-alt="Przykładowe pytanie egzaminacyjne z odpowiedzią, statystykami i szczegółowym wyjaśnieniem"
                            data-image-title="Wyjaśnienia odpowiedzi w PrawkoNaRaz"
                            aria-haspopup="dialog"
                            aria-label="Powiększ ekran szczegółowego wyjaśnienia odpowiedzi"
                        >
                            <img
                                src="{{ $explanationsOverviewScreen }}"
                                alt="Przykładowe pytanie egzaminacyjne z odpowiedzią, statystykami i szczegółowym wyjaśnieniem"
                                width="1157"
                                height="940"
                                loading="lazy"
                                decoding="async"
                            >
                        </button>
                    </figure>
                </article>

                <article class="home-learning__feature home-learning__feature--methodology" data-home-reveal>
                    <div class="home-learning__feature-copy">
                        <p class="home-learning__methodology-lead">
                            <strong class="home-learning__methodology-heading">Nie każdy uczy się tak samo.</strong>
                            Jedni chcą widzieć postęp i mieć pełną kontrolę nad sesją.
                            Inni wolą prosty ekran i maksymalne skupienie na pytaniu.
                        </p>
                        <p>
                            Dlatego w PrawkoNaRaz możesz dopasować zarówno widok nauki, jak i sposób działania pytań,
                            filmów, audio i wyjaśnień.
                        </p>
                        <h3 id="home-learning-modes-title">Dwa tryby widoku</h3>
                        <h4>Tryb klasyczny</h4>
                        <p>Postęp, poprawne odpowiedzi, pozostałe pytania, skuteczność i aktualne ustawienia zawsze pod ręką.</p>
                    </div>
                    <figure class="home-learning__feature-screen home-learning__feature-screen--methodology">
                        <button
                            type="button"
                            class="home-learning__image-trigger"
                            data-home-learning-image-trigger
                            data-image-src="{{ $classicLearningScreen }}"
                            data-image-alt="Tryb klasyczny nauki z panelem postępu i ustawieniami sesji"
                            data-image-title="Tryb klasyczny"
                            aria-haspopup="dialog"
                            aria-label="Powiększ ekran trybu klasycznego"
                        >
                            <img
                                src="{{ $classicLearningScreen }}"
                                alt="Tryb klasyczny nauki z panelem postępu i ustawieniami sesji"
                                width="1394"
                                height="871"
                                loading="lazy"
                                decoding="async"
                            >
                        </button>
                    </figure>
                </article>

                <article class="home-learning__feature home-learning__feature--focus" data-home-reveal>
                    <div class="home-learning__feature-copy">
                        <h3>Tryb skupienia</h3>
                        <p>Minimum elementów na ekranie. Zostają pytanie, materiał, odpowiedzi i podstawowy postęp. Mniej rozpraszaczy, więcej koncentracji.</p>
                    </div>
                    <figure class="home-learning__feature-screen home-learning__feature-screen--sticky">
                        <button
                            type="button"
                            class="home-learning__image-trigger"
                            data-home-learning-image-trigger
                            data-image-src="{{ $focusLearningScreen }}"
                            data-image-alt="Tryb skupienia z pytaniem i ograniczoną liczbą elementów interfejsu"
                            data-image-title="Tryb skupienia"
                            aria-haspopup="dialog"
                            aria-label="Powiększ ekran trybu skupienia"
                        >
                            <img
                                src="{{ $focusLearningScreen }}"
                                alt="Tryb skupienia z pytaniem i ograniczoną liczbą elementów interfejsu"
                                width="1271"
                                height="893"
                                loading="lazy"
                                decoding="async"
                            >
                        </button>
                    </figure>
                </article>

                <article class="home-learning__feature home-learning__feature--explanation" data-home-reveal>
                    <div class="home-learning__feature-copy">
                        <h3>Po odpowiedzi od razu wiesz, dlaczego</h3>
                        <p>Możesz zobaczyć wyjaśnienie, prostą zasadę do zapamiętania oraz — gdy to potrzebne — graficznie pokazany znak drogowy.</p>
                        <p>Dzięki temu szybciej utrwalasz skojarzenia i łatwiej zapamiętujesz poprawną odpowiedź.</p>
                    </div>
                    <figure class="home-learning__feature-screen home-learning__feature-screen--sticky">
                        <button
                            type="button"
                            class="home-learning__image-trigger"
                            data-home-learning-image-trigger
                            data-image-src="{{ $explanationFocusScreen }}"
                            data-image-alt="Wyjaśnienie odpowiedzi i zasada do zapamiętania w trybie skupienia"
                            data-image-title="Wyjaśnienie po odpowiedzi"
                            aria-haspopup="dialog"
                            aria-label="Powiększ ekran wyjaśnienia po odpowiedzi"
                        >
                            <img
                                src="{{ $explanationFocusScreen }}"
                                alt="Wyjaśnienie odpowiedzi i zasada do zapamiętania w trybie skupienia"
                                width="1324"
                                height="883"
                                loading="lazy"
                                decoding="async"
                            >
                        </button>
                    </figure>
                </article>

                <article class="home-learning__feature home-learning__feature--session" data-home-reveal>
                    <div class="home-learning__feature-copy">
                        <h3>Ty decydujesz, jak przebiega sesja.</h3>
                        <p><strong>Automatyczne przejście.</strong> Kolejne pytanie może pojawiać się samo — bez ciągłego klikania „Dalej”.</p>
                        <p><strong>Audio pytania.</strong> Czytaj sam, gdy zależy Ci na szybkości, albo włącz lektora przy długich pytaniach lub wtedy, gdy jesteś zmęczony.</p>
                        <p><strong>Wskazówki i filmy.</strong> Pogrubienia, kolory i strzałki pomagają wychwycić ważne elementy. Możesz też ustawić odtwarzanie i prędkość filmu.</p>

                        <h4>Skróty klawiaturowe</h4>
                        <p>Przechodź przez pytania bez ciągłego sięgania po mysz:</p>
                        <p class="home-learning__shortcut-line">
                            <span><strong>A lub ←</strong> — TAK</span>
                            <span><strong>S lub ↓</strong> — wyjaśnienie</span>
                            <span><strong>D lub →</strong> — NIE</span>
                        </p>
                        <p class="home-learning__pace">Sesja, która w klasycznym sposobie pracy zajmuje około 15 minut, przy szybkim trybie i skrótach może zająć około 3 minut.</p>
                    </div>
                    <figure class="home-learning__feature-screen home-learning__feature-screen--video home-learning__feature-screen--sticky">
                        <video
                            controls
                            autoplay
                            muted
                            loop
                            playsinline
                            preload="metadata"
                            poster="{{ $mistakesLearningPoster }}"
                            aria-label="Film pokazujący pracę z błędnymi pytaniami w PrawkoNaRaz"
                        >
                            <source src="{{ $mistakesLearningVideo }}" type="video/mp4">
                            Twoja przeglądarka nie obsługuje odtwarzania filmu.
                        </video>
                    </figure>

                    <div class="home-learning__session-close">
                        <strong>To środowisko nauki, które dopasowuje się do Twojego tempa, sposobu zapamiętywania i poziomu skupienia.</strong>
                    </div>
                </article>
            </div>

            <dialog class="home-learning__image-dialog" data-home-learning-image-dialog aria-labelledby="home-learning-image-dialog-title">
                <div class="home-learning__image-dialog-panel">
                    <button type="button" class="home-learning__image-dialog-close" data-home-learning-image-close aria-label="Zamknij powiększony podgląd">×</button>
                    <img data-home-learning-image src="" alt="">
                    <p id="home-learning-image-dialog-title" data-home-learning-image-title></p>
                </div>
            </dialog>

        </section>

    </div>
</section>

@include('home.partials.video-library')

@include('home.partials.reviews')

<section class="home-answers" aria-labelledby="home-answers-title">
    <div class="home-entry__shell">
        <header class="home-answers__intro home-answers__intro--faq" data-home-reveal>
            <h2 id="home-answers-title">Najczęściej zadawane pytania - FAQ</h2>
        </header>

        <div class="home-answers__list" data-home-reveal>
            @foreach ($faqItems as $faqItem)
                <details @if ($loop->first) open @endif>
                    <summary>
                        <span>{{ $faqItem['question'] }}</span>
                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </summary>
                    <p>{{ $faqItem['answer'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
