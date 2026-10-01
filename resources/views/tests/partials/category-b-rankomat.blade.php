<section class="rankomat-guide">
    <div class="rankomat-guide__shell">
        @include('components.site.test-online-sidebar')

        <main class="rankomat-guide__main">
            <div class="rankomat-guide__category">KATEGORIA B</div>
            <h1>Testy na prawo jazdy kat. B {{ $year }}</h1>
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
                Przygotuj się do egzaminu teoretycznego na <strong>prawo jazdy kategorii B</strong>.
                Rozwiązuj pytania egzaminacyjne, ćwicz zasady ruchu drogowego i sprawdzaj wiedzę przed egzaminem w WORD.
            </p>
            <a href="{{ route('public.tests', absolute: false) }}" class="rankomat-guide__mobile-cta">Przejdź do testów online</a>
            <figure class="rankomat-guide__hero">
                <img src="/images/testy/prawo-jazdy-kat-b-editorial.jpg"
                     alt="Samochód osobowy na miejskiej drodze — przygotowanie do egzaminu kategorii B"
                     width="1920" height="816" decoding="async" fetchpriority="high">
            </figure>

            <section class="rankomat-guide__important" aria-labelledby="category-b-important">
                <h2 id="category-b-important">Najważniejsze informacje</h2>
                <ul>
                    <li>Egzamin teoretyczny obejmuje <strong>32 pytania</strong>: 20 podstawowych i 12 specjalistycznych.</li>
                    <li>Na rozwiązanie testu masz <strong>25 minut</strong>.</li>
                    <li>Maksymalny wynik to <strong>74 punkty</strong>; do zdania potrzeba co najmniej <strong>68 punktów</strong>.</li>
                    <li>Po zakończeniu pytania <strong>nie można wrócić</strong> do poprzedniej odpowiedzi.</li>
                    <li>Prawo jazdy kat. B można uzyskać od <strong>17 lat</strong>, ale przed ukończeniem 18 lat obowiązują dodatkowe ograniczenia.</li>
                </ul>
            </section>

            <nav class="rankomat-guide__toc" aria-label="Spis treści">
                <p>Przejdź do sekcji:</p>
                <ol>
                    <li><a href="#asystent-ai-b">Asystent AI do testów kat. B</a></li>
                    <li><a href="#przygotowanie-b">Przygotowanie do egzaminu WORD</a></li>
                    <li><a href="#egzamin-b">Jak wygląda egzamin teoretyczny kat. B?</a></li>
                    <li><a href="#pytania-b">Pytania egzaminacyjne kat. B</a></li>
                    <li><a href="#baza-b">Oficjalna baza pytań</a></li>
                    <li><a href="#nauka-b">Jak skutecznie się przygotować?</a></li>
                    <li><a href="#uprawnienia-b">Uprawnienia kategorii B</a></li>
                    <li><a href="#wiek-b">Wiek i zasady dla 17-latków</a></li>
                    <li><a href="#praktyka-b">Egzamin praktyczny</a></li>
                    <li><a href="#faq-b">Najczęstsze pytania</a></li>
                    <li><a href="#jak-dzialaja-testy-b">Jak działają testy na PrawkoNaRaz.pl?</a></li>
                </ol>
            </nav>

            <section id="asystent-ai-b" class="rankomat-guide__section">
                <h2>Asystent AI do testów na prawo jazdy kat. B</h2>
                <p>Nie wiesz, dlaczego dana odpowiedź jest prawidłowa? Zamiast zapamiętywać ją bez zrozumienia, sprawdź wyjaśnienie i zasadę, na której opiera się pytanie.</p>
                <p><strong>Asystent AI PrawkoNaRaz</strong> pomaga analizować trudne pytania, znaki, pierwszeństwo przejazdu i sytuacje drogowe. Dzięki temu łatwiej zrozumiesz popełniony błąd.</p>
                <div class="rankomat-guide__callout rankomat-guide__callout--ai">
                    <div class="rankomat-guide__callout-title"><span>AI</span> Ucz się ze zrozumieniem</div>
                    <p>Po niepoprawnej odpowiedzi wróć do przepisu lub zasady i sprawdź, czy potrafisz zastosować ją w podobnej sytuacji.</p>
                    <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Przeglądaj pytania kat. B →</a>
                </div>
            </section>

            <section id="przygotowanie-b" class="rankomat-guide__section">
                <h2>Testy na prawo jazdy kat. B — przygotuj się do egzaminu WORD</h2>
                <p><strong>Testy na prawo jazdy kat. B</strong> pomagają sprawdzić przygotowanie do państwowego egzaminu teoretycznego. Możesz ćwiczyć zagadnienia i wracać do pytań, które sprawiają trudność.</p>
                <p>Na egzaminie liczy się nie tylko znajomość przepisów, ale też szybka ocena sytuacji. Regularne rozwiązywanie pytań oswaja z formatem testu przed wizytą w WORD.</p>
                <div class="rankomat-guide__categories" aria-label="Inne kategorie testów">
                    @foreach ($categoryMenu as $item)
                        <a href="{{ $item['href'] }}" class="{{ $item['code'] === 'B' ? 'is-active' : '' }}">{{ $item['code'] }}</a>
                    @endforeach
                </div>
            </section>

            <section id="egzamin-b" class="rankomat-guide__section">
                <h2>Jak wygląda egzamin teoretyczny na prawo jazdy kat. B?</h2>
                <p>Egzamin składa się z <strong>32 pytań</strong>: 20 z wiedzy podstawowej i 12 z wiedzy specjalistycznej. W części podstawowej wybierasz <strong>TAK lub NIE</strong>, a w specjalistycznej jedną odpowiedź: <strong>A, B lub C</strong>.</p>
                <p>Pytania są warte 1, 2 albo 3 punkty. Możesz zdobyć maksymalnie <strong>74 punkty</strong>, a wynik pozytywny wymaga co najmniej <strong>68 punktów</strong>. Cały egzamin trwa <strong>25 minut</strong>.</p>
                <p>Po zakończeniu pytania nie ma możliwości powrotu do niego. Warto zatem ćwiczyć zarówno poprawność odpowiedzi, jak i tempo podejmowania decyzji.</p>
            </section>

            <section id="pytania-b" class="rankomat-guide__section">
                <h2>Pytania egzaminacyjne kat. B — czego trzeba się nauczyć?</h2>
                <p>Na egzaminie pojawiają się zagadnienia związane z ogólnymi przepisami i prowadzeniem samochodu osobowego. Szczególną uwagę poświęć:</p>
                <ul>
                    <li>pierwszeństwu przejazdu i skrzyżowaniom,</li>
                    <li>znakom i sygnałom drogowym,</li>
                    <li>prędkości, wyprzedzaniu, zatrzymaniu i postojowi,</li>
                    <li>zachowaniu wobec pieszych i rowerzystów,</li>
                    <li>bezpieczeństwu jazdy, pierwszej pomocy i sytuacjom zagrożenia.</li>
                </ul>
                <p>Część pytań wykorzystuje zdjęcia lub nagrania. Trzeba umieć zastosować przepisy w konkretnej sytuacji, nie tylko znać definicje.</p>
            </section>

            <section id="baza-b" class="rankomat-guide__section">
                <h2>Oficjalna baza pytań na prawo jazdy</h2>
                <p>Ministerstwo Infrastruktury publikuje bazę pytań egzaminacyjnych dla kandydatów na kierowców. Podczas przygotowań korzystaj z aktualnych materiałów i zwracaj uwagę na zmiany w przepisach.</p>
                <p>Na PrawkoNaRaz.pl możesz ćwiczyć pytania przypisane do kategorii B i sprawdzać wiedzę w testach.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Zobacz pytania kategorii B →</a></p>
                @if ($sampleQuestions->isNotEmpty())
                    <div class="rankomat-guide__questions">
                        @foreach ($sampleQuestions as $question)
                            <a href="{{ $question['url'] }}"><span>#{{ $question['display_external_id'] ?? $question['external_id'] }}</span><strong>{{ $question['prompt_plain'] }}</strong><b aria-hidden="true">→</b></a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rankomat-guide__section">
                <h2>Ile punktów trzeba zdobyć, żeby zdać test kat. B?</h2>
                <p>Do zdania teorii potrzebujesz <strong>68 z 74 możliwych punktów</strong>. Margines błędu jest mały, zwłaszcza w pytaniach za 3 punkty. Sprawdzaj nie tylko liczbę pomyłek, ale też ich przyczyny.</p>
            </section>

            <section id="nauka-b" class="rankomat-guide__section">
                <h2>Jak skutecznie przygotować się do teorii kat. B?</h2>
                <ol>
                    <li>Przejdź przez pytania z poszczególnych działów.</li>
                    <li>Wróć do tematów, w których najczęściej popełniasz błędy.</li>
                    <li>Sprawdź wyjaśnienia i zrozum zasadę, nie tylko poprawną odpowiedź.</li>
                    <li>Wykonuj pełne testy na czas i obserwuj, czy regularnie osiągasz wynik powyżej progu.</li>
                </ol>
                <h3>Najtrudniejsze pytania na prawo jazdy kat. B</h3>
                <p>Najwięcej uwagi często wymagają pierwszeństwo, piesi, wyprzedzanie i dopuszczalne prędkości. Jeśli mylisz się w jednym temacie, wróć do reguły, a potem ponownie rozwiąż podobne zadania.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ url('/najtrudniejsze-pytania-na-prawo-jazdy') }}">Sprawdź najtrudniejsze pytania →</a></p>
                <h3>Test próbny kat. B</h3>
                <p>Próbny test pomaga sprawdzić wiedzę pod presją czasu. Jeden pozytywny wynik to dobry początek, ale lepszym sygnałem gotowości jest regularne zdawanie kolejnych testów.</p>
                <a href="{{ route('public.tests', absolute: false) }}" class="rankomat-guide__main-cta">Przejdź do testów kat. B</a>
            </section>

            <section id="uprawnienia-b" class="rankomat-guide__section">
                <h2>Co można prowadzić z prawem jazdy kategorii B?</h2>
                <p>Kategoria B obejmuje przede wszystkim pojazd samochodowy o dopuszczalnej masie całkowitej do <strong>3,5 t</strong>, z wyjątkiem autobusu i motocykla. Pozwala też kierować pojazdami kategorii AM oraz określonymi zestawami z przyczepą.</p>
                <p>Po co najmniej trzech latach posiadania kat. B można w Polsce kierować motocyklem do 125 cm³ i 11 kW, o stosunku mocy do masy nieprzekraczającym 0,1 kW/kg. Przyczepy i cięższe zestawy podlegają dodatkowym limitom.</p>
                <p><a class="rankomat-guide__inline-link" href="https://www.gov.pl/web/gov/kategorie-prawa-jazdy" rel="noopener noreferrer">Sprawdź pełny zakres uprawnień na gov.pl ↗</a></p>
            </section>

            <section id="wiek-b" class="rankomat-guide__section">
                <h2>Od ilu lat można zrobić prawo jazdy kat. B w {{ $year }} roku?</h2>
                <p>Prawo jazdy kategorii B można uzyskać od <strong>17. roku życia</strong>. Do ukończenia 18 lat uprawnienie działa wyłącznie na terytorium Polski. Przez pierwsze 6 miesięcy od uzyskania prawa jazdy, nie dłużej niż do pełnoletności, jazda samochodem wymaga obecności osoby spełniającej ustawowe warunki.</p>
                <p><a class="rankomat-guide__inline-link" href="https://www.gov.pl/web/infrastruktura/uwaga-kierowcy--od-3-marca-obowiazuja-nowe-przepisy---sprawdz-co-sie-zmienilo" rel="noopener noreferrer">Przeczytaj aktualne zasady na gov.pl ↗</a></p>
            </section>

            <section id="praktyka-b" class="rankomat-guide__section">
                <h2>Jak wygląda egzamin praktyczny na kategorię B?</h2>
                <p>Po zaliczeniu wymaganej teorii kandydat przystępuje do egzaminu praktycznego. Obejmuje on zadania na placu manewrowym oraz jazdę w ruchu drogowym.</p>
                <p>Znajomość przepisów pomaga również podczas praktyki: na drodze decyzje o pierwszeństwie, znakach i bezpieczeństwie trzeba podejmować płynnie.</p>
            </section>

            <section id="faq-b" class="rankomat-guide__section rankomat-guide__faq">
                <h2>Najczęstsze pytania o testy na prawo jazdy kat. B</h2>
                @foreach ($faq as $item)
                    <details><summary>{{ $item['question'] }}<span aria-hidden="true">+</span></summary><p>{{ $item['answer'] }}</p></details>
                @endforeach
            </section>

            <section id="jak-dzialaja-testy-b" class="rankomat-guide__section">
                <h2>Jak działają testy na PrawkoNaRaz.pl?</h2>
                <p>Bez zakładania konta możesz wypróbować publiczny zestaw <strong>20 pytań próbnych</strong> kategorii B. To podgląd sposobu nauki, a nie pełny egzamin państwowy z 32 pytaniami.</p>
                <p>Na stronie testów znajdziesz informacje o pełnym dostępie, rejestracji i dostępnych wariantach korzystania z serwisu.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.tests', absolute: false) }}">Sprawdź pytania próbne →</a></p>
            </section>

            <section class="rankomat-guide__section rankomat-guide__final">
                <h2>Testy na prawo jazdy kat. B {{ $year }} — zacznij naukę</h2>
                <p>Ćwicz aktualne pytania, analizuj błędy i sprawdzaj przygotowanie przed egzaminem w WORD.</p>
                <a href="{{ route('public.tests', absolute: false) }}" class="rankomat-guide__main-cta">Przejdź do testów na prawo jazdy</a>
            </section>
        </main>
    </div>
</section>
