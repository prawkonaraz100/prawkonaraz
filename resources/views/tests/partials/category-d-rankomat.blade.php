<section class="rankomat-guide">
    <div class="rankomat-guide__shell">
        @include('components.site.test-online-sidebar')

        <main class="rankomat-guide__main">
            <div class="rankomat-guide__category">KATEGORIA D</div>
            <h1>Testy na prawo jazdy kat. D {{ $year }}</h1>
            <div class="rankomat-guide__meta">
                <div class="rankomat-guide__author">
                    <img src="/images/site-brand-mark-shield-v2-optimized.webp" alt="" width="42" height="42">
                    <div><strong>PrawkoNaRaz.pl</strong><span>Materiały do nauki</span></div>
                </div>
                <div class="rankomat-guide__meta-divider" aria-hidden="true"></div>
                <div class="rankomat-guide__updated"><strong>Aktualizacja</strong><span>{{ $year }}</span></div>
                <div class="rankomat-guide__trust">✓ Aktualne zasady egzaminu</div>
            </div>
            <p class="rankomat-guide__lead">
                Przygotuj się do egzaminu teoretycznego na <strong>prawo jazdy kategorii D</strong>.
                Ćwicz pytania podstawowe i specjalistyczne dotyczące autobusu oraz bezpieczeństwa pasażerów.
            </p>
            <a href="{{ route('public.tests', absolute: false) }}" class="rankomat-guide__mobile-cta">Przejdź do testów online</a>
            <figure class="rankomat-guide__hero">
                <img src="/images/testy/prawo-jazdy-kat-d-editorial.jpg"
                     alt="Autobus przy przystanku — przygotowanie do egzaminu kategorii D"
                     width="1919" height="820" decoding="async" fetchpriority="high">
            </figure>

            <section class="rankomat-guide__important" aria-labelledby="category-d-important">
                <h2 id="category-d-important">Najważniejsze informacje</h2>
                <ul>
                    <li>Egzamin teoretyczny obejmuje <strong>32 pytania</strong>: 20 podstawowych i 12 specjalistycznych.</li>
                    <li>Na rozwiązanie testu masz <strong>25 minut</strong>.</li>
                    <li>Do zdania potrzeba co najmniej <strong>68 z 74 punktów</strong>.</li>
                    <li>Kategoria D obejmuje autobus z <strong>przyczepą lekką do 750 kg</strong>; cięższa przyczepa wymaga odpowiedniego D+E.</li>
                    <li>Standardowy minimalny wiek to <strong>24 lata</strong>. Wyjątki zależą od kwalifikacji i rodzaju przewozu.</li>
                </ul>
            </section>

            <nav class="rankomat-guide__toc" aria-label="Spis treści">
                <p>Przejdź do sekcji:</p>
                <ol>
                    <li><a href="#asystent-ai-d">Asystent AI do testów kat. D</a></li>
                    <li><a href="#przygotowanie-d">Przygotowanie do egzaminu WORD</a></li>
                    <li><a href="#egzamin-d">Jak wygląda egzamin teoretyczny?</a></li>
                    <li><a href="#pytania-d">Pytania egzaminacyjne kat. D</a></li>
                    <li><a href="#baza-d">Oficjalne pytania kat. D</a></li>
                    <li><a href="#uprawnienia-d">Uprawnienia D i D+E</a></li>
                    <li><a href="#wiek-d">Minimalny wiek w 2026 roku</a></li>
                    <li><a href="#nauka-d">Najtrudniejsze pytania i plan nauki</a></li>
                    <li><a href="#kod-95-d">Kategoria D a kod 95</a></li>
                    <li><a href="#praktyka-d">Egzamin praktyczny</a></li>
                    <li><a href="#faq-d">Najczęstsze pytania</a></li>
                    <li><a href="#jak-dzialaja-testy-d">Jak działają testy w serwisie?</a></li>
                </ol>
            </nav>

            <section id="asystent-ai-d" class="rankomat-guide__section">
                <h2>Asystent AI do testów na prawo jazdy kat. D</h2>
                <p>Nie rozumiesz pytania dotyczącego autobusu, przystanku lub zachowania wobec pasażerów? Warto przeanalizować przepis i sytuację drogową, zamiast zapamiętywać odpowiedź na pamięć.</p>
                <p><strong>Asystent AI PrawkoNaRaz</strong> może pomóc wyjaśnić trudniejsze pytania podczas nauki. Istotne zasady potwierdzaj w aktualnych przepisach.</p>
                <div class="rankomat-guide__callout rankomat-guide__callout--ai">
                    <div class="rankomat-guide__callout-title"><span>AI</span> Ucz się ze zrozumieniem</div>
                    <p>Po błędnej odpowiedzi ustal, jaka zasada decyduje o poprawnym rozwiązaniu, a potem wróć do podobnych zadań.</p>
                    <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Przeglądaj pytania kategorii D →</a>
                </div>
            </section>

            <section id="przygotowanie-d" class="rankomat-guide__section">
                <h2>Testy na prawo jazdy kat. D — przygotowanie do egzaminu WORD</h2>
                <p><strong>Testy kat. D</strong> pomagają przygotować się do teorii na autobus. Oprócz przepisów ogólnych ważne są zagadnienia dotyczące dużego pojazdu przeznaczonego do przewozu osób.</p>
                <p>Podczas nauki zwracaj uwagę na bezpieczeństwo pasażerów, zachowanie na przystankach, martwe pola, manewrowanie i reakcję w sytuacjach zagrożenia. Pytania kategorii D warto ćwiczyć oddzielnie od zagadnień kwalifikacji zawodowej.</p>
                <div class="rankomat-guide__categories" aria-label="Inne kategorie testów">
                    @foreach ($categoryMenu as $item)
                        <a href="{{ $item['href'] }}" class="{{ $item['code'] === 'D' ? 'is-active' : '' }}">{{ $item['code'] }}</a>
                    @endforeach
                </div>
            </section>

            <section id="egzamin-d" class="rankomat-guide__section">
                <h2>Jak wygląda egzamin teoretyczny na prawo jazdy kat. D?</h2>
                <p>Egzamin odbywa się przy komputerze w WORD. Składa się z <strong>32 pytań</strong>: 20 z wiedzy podstawowej i 12 z wiedzy specjalistycznej.</p>
                <p>W części podstawowej wybierasz <strong>TAK albo NIE</strong>, a w specjalistycznej jedną z odpowiedzi <strong>A, B lub C</strong>. Pytania są warte 1, 2 albo 3 punkty.</p>
                <p>Cały egzamin trwa <strong>25 minut</strong>. Maksymalny wynik to 74 punkty, a próg zaliczenia wynosi <strong>68 punktów</strong>. Po zakończeniu pytania nie można wrócić do poprzedniej odpowiedzi.</p>
            </section>

            <section id="pytania-d" class="rankomat-guide__section">
                <h2>Pytania egzaminacyjne kat. D — czego trzeba się nauczyć?</h2>
                <p>Egzamin sprawdza przepisy ogólne oraz wiedzę potrzebną kierowcy autobusu. Zwróć szczególną uwagę na:</p>
                <ul>
                    <li>znaki, sygnały i pierwszeństwo przejazdu,</li>
                    <li>bezpieczeństwo pasażerów oraz wsiadanie i wysiadanie,</li>
                    <li>zachowanie w pobliżu przystanków,</li>
                    <li>hamowanie, skręcanie i manewrowanie dużym pojazdem,</li>
                    <li>martwe pola i obserwację otoczenia,</li>
                    <li>ograniczenia dotyczące autobusów, pierwszą pomoc i sytuacje awaryjne.</li>
                </ul>
                <p>Część podstawowa dotyczy ogólnych zasad ruchu, ale część specjalistyczna jest dostosowana do kategorii D.</p>
            </section>

            <section id="baza-d" class="rankomat-guide__section">
                <h2>Oficjalne pytania na prawo jazdy kat. D</h2>
                <p>Podczas nauki warto korzystać z aktualnej bazy pytań egzaminacyjnych i śledzić zmiany przepisów. Same pytania ogólne nie wystarczą — potrzebna jest też wiedza o autobusie i bezpiecznym przewozie osób.</p>
                <p>Na PrawkoNaRaz.pl możesz przeglądać pytania przypisane do kategorii D i wracać do zagadnień, które sprawiają trudność.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Zobacz pytania kategorii D →</a></p>
                @if ($sampleQuestions->isNotEmpty())
                    <div class="rankomat-guide__questions">
                        @foreach ($sampleQuestions as $question)
                            <a href="{{ $question['url'] }}"><span>#{{ $question['display_external_id'] ?? $question['external_id'] }}</span><strong>{{ $question['prompt_plain'] }}</strong><b aria-hidden="true">→</b></a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rankomat-guide__section">
                <h2>Ile punktów trzeba zdobyć, żeby zdać test kat. D?</h2>
                <p>Wynik pozytywny wymaga co najmniej <strong>68 z 74 możliwych punktów</strong>. Po każdym próbnym egzaminie analizuj nie tylko wynik, lecz także zagadnienia, w których popełniasz błędy.</p>
            </section>

            <section id="uprawnienia-d" class="rankomat-guide__section">
                <h2>Co można prowadzić z prawem jazdy kategorii D?</h2>
                <p>Kategoria D uprawnia do kierowania <strong>autobusem</strong>, także z przyczepą lekką o DMC do <strong>750 kg</strong>, oraz pojazdami kategorii AM. Na terytorium Polski obejmuje również określone pojazdy rolnicze i wolnobieżne.</p>
                <h3>Czym różni się kategoria D od D+E?</h3>
                <p><strong>D</strong> obejmuje autobus z przyczepą lekką. <strong>D+E</strong> dotyczy zestawu z pojazdem kategorii D i przyczepą inną niż lekka. Nie do każdej przyczepy przy autobusie potrzebna jest kategoria D+E.</p>
                <p><a class="rankomat-guide__inline-link" href="https://www.gov.pl/web/gov/kategorie-prawa-jazdy" rel="noopener noreferrer">Pełny zakres uprawnień na gov.pl ↗</a></p>
            </section>

            <section id="wiek-d" class="rankomat-guide__section">
                <h2>Od ilu lat można zrobić prawo jazdy kat. D w 2026 roku?</h2>
                <p>Standardowy minimalny wiek wynosi <strong>24 lata</strong>. Po uzyskaniu odpowiedniej <strong>kwalifikacji wstępnej</strong> kategorię D lub D+E można uzyskać od <strong>21 lat</strong>.</p>
                <p>Od <strong>3 września 2026 r.</strong> przepisy przewidują dodatkowe wyjątki: <strong>20 lat</strong> przy przewozie drogowym wykonywanym na terytorium Polski oraz <strong>18 lat</strong> przy przewozie na regularnych liniach w Polsce o trasie nieprzekraczającej 50 km — w obu przypadkach po uzyskaniu wymaganej kwalifikacji wstępnej.</p>
                <p>Wiek zależy więc nie tylko od kategorii, lecz także od kwalifikacji i rodzaju wykonywanego przewozu. Przed rozpoczęciem szkolenia sprawdź warunki dotyczące swojej sytuacji.</p>
                <p><a class="rankomat-guide__inline-link" href="https://www.gov.pl/web/gov/kategorie-prawa-jazdy" rel="noopener noreferrer">Sprawdź wymagania wiekowe na gov.pl ↗</a></p>
            </section>

            <section id="nauka-d" class="rankomat-guide__section">
                <h2>Najtrudniejsze pytania na prawo jazdy kat. D</h2>
                <p>Więcej uwagi często wymagają zagadnienia dotyczące pasażerów, przystanków, gabarytów autobusu, martwych pól, drogi hamowania oraz postępowania w sytuacjach awaryjnych.</p>
                <p>Nie ucz się odpowiedzi mechanicznie. Wróć do przepisu, zrozum zasadę, a następnie rozwiąż podobne pytania ponownie.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ url('/najtrudniejsze-pytania-na-prawo-jazdy') }}">Sprawdź najtrudniejsze pytania →</a></p>
                <h3>Jak przygotować się do egzaminu kat. D?</h3>
                <ol>
                    <li>Przejdź pytania tematyczne kategorii D.</li>
                    <li>Sprawdź błędne odpowiedzi i powtórz słabsze działy.</li>
                    <li>Ćwicz zagadnienia dotyczące autobusu i bezpieczeństwa pasażerów.</li>
                    <li>Rozwiązuj pełne testy na czas i dąż do stabilnych wyników.</li>
                </ol>
                <p>Jeden zdany test to początek. Regularne wyniki powyżej progu lepiej pokazują gotowość do egzaminu.</p>
            </section>

            <section id="kod-95-d" class="rankomat-guide__section">
                <h2>Prawo jazdy kat. D a kwalifikacja zawodowa i kod 95</h2>
                <p><strong>Prawo jazdy kategorii D</strong> określa uprawnienia do kierowania autobusem. Zawodowy przewóz osób to odrębny temat: potrzebna może być również kwalifikacja zawodowa i odpowiedni wpis.</p>
                <p>Kwalifikacja może wpływać na minimalny wiek kierowcy. Na tej stronie skupiamy się na państwowym egzaminie na kategorię D; materiały o kwalifikacjach i kodzie 95 rozwijamy osobno.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.code95', absolute: false) }}">Przejdź do kursu kod 95 →</a></p>
            </section>

            <section id="praktyka-d" class="rankomat-guide__section">
                <h2>Jak wygląda egzamin praktyczny kat. D?</h2>
                <p>Po spełnieniu wymaganych warunków kandydat przystępuje również do części praktycznej. Oceniane są między innymi przygotowanie pojazdu, wymagane manewry oraz bezpieczne prowadzenie autobusu w ruchu drogowym.</p>
                <p>Znajomość teorii pomaga później skupić się na gabarytach autobusu, torze jazdy, obserwacji otoczenia i bezpieczeństwie innych uczestników ruchu.</p>
            </section>

            <section id="faq-d" class="rankomat-guide__section rankomat-guide__faq">
                <h2>Najczęstsze pytania o testy na prawo jazdy kat. D</h2>
                @foreach ($faq as $item)
                    <details><summary>{{ $item['question'] }}<span aria-hidden="true">+</span></summary><p>{{ $item['answer'] }}</p></details>
                @endforeach
            </section>

            <section id="jak-dzialaja-testy-d" class="rankomat-guide__section">
                <h2>Jak działają testy na PrawkoNaRaz.pl?</h2>
                <p>Publicznie możesz przeglądać pytania kategorii D. Pełna nauka i sesje egzaminacyjne są dostępne w panelu po zalogowaniu, zgodnie z zasadami dostępu pokazanymi w serwisie.</p>
                <p>Publiczny zestaw próbny na stronie testów zawiera <strong>20 pytań kategorii B</strong>; nie należy mylić go z pełnym egzaminem teoretycznym kategorii D.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Zacznij od pytań kat. D →</a></p>
            </section>

            <section class="rankomat-guide__section rankomat-guide__final">
                <h2>Testy na prawo jazdy kat. D {{ $year }} — zacznij naukę</h2>
                <p>Ćwicz pytania dotyczące autobusów, analizuj błędy i przygotuj się do teorii w WORD.</p>
                <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}" class="rankomat-guide__main-cta">Zobacz pytania na prawo jazdy kat. D</a>
            </section>
        </main>
    </div>
</section>
