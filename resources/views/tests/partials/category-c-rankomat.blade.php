<section class="rankomat-guide">
    <div class="rankomat-guide__shell">
        @include('components.site.test-online-sidebar')

        <main class="rankomat-guide__main">
            <div class="rankomat-guide__category">KATEGORIA C</div>
            <h1>Testy na prawo jazdy kat. C {{ $year }}</h1>
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
                Przygotuj się do egzaminu teoretycznego na <strong>prawo jazdy kategorii C</strong>.
                Ćwicz pytania podstawowe i specjalistyczne związane z prowadzeniem samochodów ciężarowych.
            </p>
            <a href="{{ route('public.tests', absolute: false) }}" class="rankomat-guide__mobile-cta">Przejdź do testów online</a>
            <figure class="rankomat-guide__hero">
                <img src="/images/testy/prawo-jazdy-kat-c-editorial.jpg"
                     alt="Samochód ciężarowy na drodze — przygotowanie do egzaminu kategorii C"
                     width="1774" height="887" decoding="async" fetchpriority="high">
            </figure>

            <section class="rankomat-guide__important" aria-labelledby="category-c-important">
                <h2 id="category-c-important">Najważniejsze informacje</h2>
                <ul>
                    <li>Egzamin teoretyczny obejmuje <strong>32 pytania</strong>: 20 podstawowych i 12 specjalistycznych.</li>
                    <li>Na rozwiązanie testu masz <strong>25 minut</strong>.</li>
                    <li>Do zdania potrzeba co najmniej <strong>68 z 74 punktów</strong>.</li>
                    <li>Kategoria C obejmuje ciężarówkę z <strong>przyczepą lekką do 750 kg</strong>; cięższa przyczepa wymaga odpowiedniego C+E.</li>
                    <li>Standardowy minimalny wiek to <strong>21 lat</strong>, a przy odpowiedniej kwalifikacji wstępnej <strong>18 lat</strong>.</li>
                </ul>
            </section>

            <nav class="rankomat-guide__toc" aria-label="Spis treści">
                <p>Przejdź do sekcji:</p>
                <ol>
                    <li><a href="#asystent-ai-c">Asystent AI do testów kat. C</a></li>
                    <li><a href="#przygotowanie-c">Przygotowanie do egzaminu WORD</a></li>
                    <li><a href="#egzamin-c">Jak wygląda egzamin teoretyczny?</a></li>
                    <li><a href="#pytania-c">Pytania egzaminacyjne kat. C</a></li>
                    <li><a href="#baza-c">Oficjalne pytania kat. C</a></li>
                    <li><a href="#uprawnienia-c">Uprawnienia C i C+E</a></li>
                    <li><a href="#wiek-c">Minimalny wiek i kategoria B</a></li>
                    <li><a href="#nauka-c">Najtrudniejsze pytania i plan nauki</a></li>
                    <li><a href="#kod-95-c">Kategoria C a kod 95</a></li>
                    <li><a href="#faq-c">Najczęstsze pytania</a></li>
                    <li><a href="#jak-dzialaja-testy-c">Jak działają testy w serwisie?</a></li>
                </ol>
            </nav>

            <section id="asystent-ai-c" class="rankomat-guide__section">
                <h2>Asystent AI do testów na prawo jazdy kat. C</h2>
                <p>Nie rozumiesz pytania albo nie wiesz, dlaczego odpowiedź jest prawidłowa? Warto przeanalizować przepis i sytuację drogową, zamiast zapamiętywać wariant na pamięć.</p>
                <p><strong>Asystent AI PrawkoNaRaz</strong> może pomóc wyjaśnić trudniejsze pytania podczas nauki. Pamiętaj, by istotne zasady potwierdzać w aktualnych przepisach.</p>
                <div class="rankomat-guide__callout rankomat-guide__callout--ai">
                    <div class="rankomat-guide__callout-title"><span>AI</span> Ucz się ze zrozumieniem</div>
                    <p>Po błędnej odpowiedzi ustal, jaka zasada decyduje o poprawnym rozwiązaniu, a potem wróć do podobnych zadań.</p>
                    <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Przeglądaj pytania kategorii C →</a>
                </div>
            </section>

            <section id="przygotowanie-c" class="rankomat-guide__section">
                <h2>Testy na prawo jazdy kat. C — przygotowanie do egzaminu WORD</h2>
                <p><strong>Testy kat. C</strong> pomagają przygotować się do teorii na samochód ciężarowy. Oprócz przepisów ogólnych ważna jest specyfika dużego pojazdu: masa, droga hamowania, ograniczenia, ładunek i manewry.</p>
                <p>Regularna nauka pozwala sprawdzić wiedzę i tempo odpowiadania przed egzaminem w WORD. Warto ćwiczyć pytania specjalistyczne oddzielnie od zagadnień dotyczących kwalifikacji zawodowej.</p>
                <div class="rankomat-guide__categories" aria-label="Inne kategorie testów">
                    @foreach ($categoryMenu as $item)
                        <a href="{{ $item['href'] }}" class="{{ $item['code'] === 'C' ? 'is-active' : '' }}">{{ $item['code'] }}</a>
                    @endforeach
                </div>
            </section>

            <section id="egzamin-c" class="rankomat-guide__section">
                <h2>Jak wygląda egzamin teoretyczny na prawo jazdy kat. C?</h2>
                <p>Egzamin odbywa się przy komputerze w WORD. Składa się z <strong>32 pytań</strong>: 20 z wiedzy podstawowej i 12 z wiedzy specjalistycznej.</p>
                <p>W części podstawowej wybierasz <strong>TAK albo NIE</strong>, a w specjalistycznej jedną z odpowiedzi <strong>A, B lub C</strong>. Pytania są warte 1, 2 albo 3 punkty.</p>
                <p>Cały egzamin trwa <strong>25 minut</strong>. Maksymalny wynik to 74 punkty, a próg zaliczenia wynosi <strong>68 punktów</strong>. Po zakończeniu pytania nie można wrócić do poprzedniej odpowiedzi.</p>
            </section>

            <section id="pytania-c" class="rankomat-guide__section">
                <h2>Pytania egzaminacyjne kat. C — czego trzeba się nauczyć?</h2>
                <p>Egzamin sprawdza przepisy ogólne oraz wiedzę potrzebną kierowcy samochodu ciężarowego. Zwróć szczególną uwagę na:</p>
                <ul>
                    <li>znaki, sygnały i pierwszeństwo przejazdu,</li>
                    <li>masę, wymiary i ograniczenia dotyczące ciężarówek,</li>
                    <li>drogę hamowania i stabilność pojazdu,</li>
                    <li>rozmieszczenie oraz zabezpieczenie ładunku,</li>
                    <li>martwe pola i bezpieczne manewrowanie,</li>
                    <li>pierwszą pomoc i reakcję w sytuacji zagrożenia.</li>
                </ul>
                <p>Część podstawowa jest wspólna z innymi kategoriami, ale część specjalistyczna dotyczy kategorii C.</p>
            </section>

            <section id="baza-c" class="rankomat-guide__section">
                <h2>Oficjalne pytania na prawo jazdy kat. C</h2>
                <p>Ministerstwo Infrastruktury sprawuje nadzór nad bazą pytań egzaminacyjnych i publikuje materiały dla kandydatów na kierowców. Podczas nauki warto korzystać z aktualnej bazy i śledzić zmiany przepisów.</p>
                <p>Na PrawkoNaRaz.pl możesz przeglądać pytania przypisane do kategorii C i wracać do zagadnień, które sprawiają trudność.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Zobacz pytania kategorii C →</a></p>
                @if ($sampleQuestions->isNotEmpty())
                    <div class="rankomat-guide__questions">
                        @foreach ($sampleQuestions as $question)
                            <a href="{{ $question['url'] }}"><span>#{{ $question['display_external_id'] ?? $question['external_id'] }}</span><strong>{{ $question['prompt_plain'] }}</strong><b aria-hidden="true">→</b></a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rankomat-guide__section">
                <h2>Ile punktów trzeba zdobyć, żeby zdać test kat. C?</h2>
                <p>Wynik pozytywny wymaga co najmniej <strong>68 z 74 możliwych punktów</strong>. Po każdym próbnym egzaminie analizuj nie tylko wynik, lecz także pytania, na które odpowiedziałeś nieprawidłowo.</p>
            </section>

            <section id="uprawnienia-c" class="rankomat-guide__section">
                <h2>Co można prowadzić z prawem jazdy kategorii C?</h2>
                <p>Kategoria C pozwala kierować pojazdem samochodowym o DMC <strong>powyżej 3,5 t</strong>, z wyjątkiem autobusu. Obejmuje też taki pojazd z przyczepą lekką o DMC do <strong>750 kg</strong> oraz pojazdy kategorii AM.</p>
                <p>Na terytorium Polski uprawnia również do kierowania ciągnikiem rolniczym i pojazdem wolnobieżnym w zakresie określonym przepisami.</p>
                <h3>Czym różni się kategoria C od C+E?</h3>
                <p><strong>C</strong> obejmuje ciężarówkę z przyczepą lekką. <strong>C+E</strong> dotyczy zestawu z pojazdem kategorii C i przyczepą inną niż lekka — na przykład typowego ciągnika siodłowego z naczepą. Materiały dla tych uprawnień warto traktować osobno.</p>
                <p><a class="rankomat-guide__inline-link" href="https://www.gov.pl/web/gov/kategorie-prawa-jazdy" rel="noopener noreferrer">Pełny zakres uprawnień na gov.pl ↗</a></p>
            </section>

            <section id="wiek-c" class="rankomat-guide__section">
                <h2>Od ilu lat można zrobić prawo jazdy kat. C?</h2>
                <p>Standardowy minimalny wiek wynosi <strong>21 lat</strong>. Przy odpowiedniej <strong>kwalifikacji wstępnej</strong> kategorię C można uzyskać od <strong>18 lat</strong>. Szkolenie i egzamin można rozpocząć przed osiągnięciem wymaganego wieku w terminach przewidzianych przepisami.</p>
                <h3>Czy do kategorii C trzeba mieć prawo jazdy kat. B?</h3>
                <p>Tak. Uzyskanie kategorii C wymaga posiadania kategorii B. Potrzebny jest również Profil Kandydata na Kierowcę (PKK) oraz odpowiednie orzeczenia lekarskie i psychologiczne.</p>
                <p><a class="rankomat-guide__inline-link" href="https://www.gov.pl/web/gov/kategorie-prawa-jazdy" rel="noopener noreferrer">Sprawdź wymagania wiekowe na gov.pl ↗</a></p>
            </section>

            <section id="nauka-c" class="rankomat-guide__section">
                <h2>Najtrudniejsze pytania na prawo jazdy kat. C</h2>
                <p>Więcej uwagi często wymagają zagadnienia rzadziej spotykane podczas jazdy samochodem osobowym: ograniczenia tonażowe, ładunek, hamowanie ciężkiego pojazdu, martwe pola i manewrowanie.</p>
                <p>Nie ucz się odpowiedzi mechanicznie. Wróć do przepisu, zrozum zasadę, a następnie rozwiąż podobne pytania ponownie.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ url('/najtrudniejsze-pytania-na-prawo-jazdy') }}">Sprawdź najtrudniejsze pytania →</a></p>
                <h3>Jak przygotować się do egzaminu kat. C?</h3>
                <ol>
                    <li>Przejdź pytania tematyczne kategorii C.</li>
                    <li>Sprawdź błędne odpowiedzi i powtórz słabsze działy.</li>
                    <li>Ćwicz pytania specjalistyczne dotyczące ciężarówek.</li>
                    <li>Rozwiązuj pełne testy na czas i dąż do stabilnych wyników.</li>
                </ol>
                <p>Jeden zdany test to początek. Regularne wyniki powyżej progu lepiej pokazują gotowość do egzaminu.</p>
            </section>

            <section id="kod-95-c" class="rankomat-guide__section">
                <h2>Prawo jazdy kat. C a kierowca zawodowy i kod 95</h2>
                <p><strong>Prawo jazdy kategorii C</strong> określa uprawnienia do kierowania pojazdem. Zawodowe wykonywanie przewozów to odrębny temat: zależnie od rodzaju pracy potrzebne są także kwalifikacje zawodowe i odpowiedni wpis.</p>
                <p>Kwalifikacja wstępna ma również znaczenie dla obniżenia minimalnego wieku kategorii C. Na tej stronie skupiamy się na państwowym egzaminie na kategorię C; materiały o kwalifikacjach rozwijamy osobno.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.code95', absolute: false) }}">Przejdź do kursu kod 95 →</a></p>
            </section>

            <section id="faq-c" class="rankomat-guide__section rankomat-guide__faq">
                <h2>Najczęstsze pytania o testy na prawo jazdy kat. C</h2>
                @foreach ($faq as $item)
                    <details><summary>{{ $item['question'] }}<span aria-hidden="true">+</span></summary><p>{{ $item['answer'] }}</p></details>
                @endforeach
            </section>

            <section id="jak-dzialaja-testy-c" class="rankomat-guide__section">
                <h2>Jak działają testy na PrawkoNaRaz.pl?</h2>
                <p>Publicznie możesz przeglądać pytania kategorii C. Pełna nauka i sesje egzaminacyjne są dostępne w panelu po zalogowaniu, zgodnie z zasadami dostępu pokazanymi w serwisie.</p>
                <p>Publiczny zestaw próbny na stronie testów zawiera <strong>20 pytań kategorii B</strong>; nie należy mylić go z pełnym egzaminem teoretycznym kategorii C.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Zacznij od pytań kat. C →</a></p>
            </section>

            <section class="rankomat-guide__section rankomat-guide__final">
                <h2>Testy na prawo jazdy kat. C {{ $year }} — zacznij naukę</h2>
                <p>Ćwicz pytania dotyczące samochodów ciężarowych, analizuj błędy i przygotuj się do teorii w WORD.</p>
                <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}" class="rankomat-guide__main-cta">Zobacz pytania na prawo jazdy kat. C</a>
            </section>
        </main>
    </div>
</section>
