<section class="rankomat-guide">
    <div class="rankomat-guide__shell">
        @include('components.site.test-online-sidebar')

        <main class="rankomat-guide__main">
            <div class="rankomat-guide__category">KATEGORIA T</div>
            <h1>Testy na prawo jazdy kat. T {{ $year }}</h1>
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
                Przygotuj się do egzaminu teoretycznego na <strong>prawo jazdy kategorii T</strong>.
                Ćwicz pytania podstawowe i specjalistyczne dotyczące ciągników rolniczych, pojazdów wolnobieżnych oraz przyczep.
            </p>
            <a href="{{ route('public.tests', absolute: false) }}" class="rankomat-guide__mobile-cta">Przejdź do testów online</a>
            <figure class="rankomat-guide__hero">
                <img src="/images/testy/prawo-jazdy-kat-t-editorial.jpg"
                     alt="Ciągnik rolniczy z przyczepą na drodze — przygotowanie do egzaminu kategorii T"
                     width="1942" height="809" decoding="async" fetchpriority="high">
            </figure>

            <section class="rankomat-guide__important" aria-labelledby="category-t-important">
                <h2 id="category-t-important">Najważniejsze informacje</h2>
                <ul>
                    <li>Egzamin teoretyczny obejmuje <strong>32 pytania</strong>: 20 podstawowych i 12 specjalistycznych.</li>
                    <li>Na rozwiązanie testu masz <strong>25 minut</strong>; do zdania potrzeba <strong>68 z 74 punktów</strong>.</li>
                    <li>Kategoria T obejmuje <strong>ciągnik rolniczy i pojazd wolnobieżny</strong>, także z przyczepą lub przyczepami.</li>
                    <li>Prawo jazdy kat. T można uzyskać od <strong>16 lat</strong>.</li>
                    <li>Kategoria T <strong>nie uprawnia do kierowania samochodem osobowym</strong> kategorii B.</li>
                </ul>
            </section>

            <nav class="rankomat-guide__toc" aria-label="Spis treści">
                <p>Przejdź do sekcji:</p>
                <ol>
                    <li><a href="#asystent-ai-t">Asystent AI do testów kat. T</a></li>
                    <li><a href="#przygotowanie-t">Przygotowanie do egzaminu WORD</a></li>
                    <li><a href="#egzamin-t">Jak wygląda egzamin teoretyczny?</a></li>
                    <li><a href="#pytania-t">Pytania egzaminacyjne kat. T</a></li>
                    <li><a href="#baza-t">Oficjalne pytania kat. T</a></li>
                    <li><a href="#uprawnienia-t">Uprawnienia kategorii T</a></li>
                    <li><a href="#wiek-t">Wiek i zgoda opiekuna</a></li>
                    <li><a href="#t-vs-b">Kategoria T a B</a></li>
                    <li><a href="#quad-kombajn-t">Quad i kombajn</a></li>
                    <li><a href="#nauka-t">Najtrudniejsze pytania i plan nauki</a></li>
                    <li><a href="#praktyka-t">Egzamin praktyczny</a></li>
                    <li><a href="#faq-t">Najczęstsze pytania</a></li>
                    <li><a href="#jak-dzialaja-testy-t">Jak działają testy w serwisie?</a></li>
                </ol>
            </nav>

            <section id="asystent-ai-t" class="rankomat-guide__section">
                <h2>Asystent AI do testów na prawo jazdy kat. T</h2>
                <p>Nie rozumiesz pytania o ciągnik, przyczepę albo znak drogowy? Warto przeanalizować przepis i sytuację drogową, zamiast zapamiętywać odpowiedź na pamięć.</p>
                <p><strong>Asystent AI PrawkoNaRaz</strong> może pomóc wyjaśnić trudniejsze pytania podczas nauki. Istotne zasady potwierdzaj w aktualnych przepisach.</p>
                <div class="rankomat-guide__callout rankomat-guide__callout--ai">
                    <div class="rankomat-guide__callout-title"><span>AI</span> Ucz się ze zrozumieniem</div>
                    <p>Po błędnej odpowiedzi ustal, jaka zasada decyduje o poprawnym rozwiązaniu, a potem wróć do podobnych zadań.</p>
                    <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Przeglądaj pytania kategorii T →</a>
                </div>
            </section>

            <section id="przygotowanie-t" class="rankomat-guide__section">
                <h2>Testy na prawo jazdy kat. T — przygotowanie do egzaminu WORD</h2>
                <p><strong>Testy kat. T</strong> pomagają przygotować się do teorii na ciągnik rolniczy i pojazd wolnobieżny. Oprócz przepisów ogólnych ważne są zagadnienia dotyczące zestawów z przyczepami.</p>
                <p>Podczas nauki zwracaj uwagę na oznakowanie, oświetlenie, gabaryty i masę pojazdu, bezpieczne manewrowanie oraz jazdę wolniejszym pojazdem po drodze publicznej.</p>
                <div class="rankomat-guide__categories" aria-label="Inne kategorie testów">
                    @foreach ($categoryMenu as $item)
                        <a href="{{ $item['href'] }}" class="{{ $item['code'] === 'T' ? 'is-active' : '' }}">{{ $item['code'] }}</a>
                    @endforeach
                </div>
            </section>

            <section id="egzamin-t" class="rankomat-guide__section">
                <h2>Jak wygląda egzamin teoretyczny na prawo jazdy kat. T?</h2>
                <p>Egzamin odbywa się przy komputerze w WORD. Składa się z <strong>32 pytań</strong>: 20 z wiedzy podstawowej i 12 z wiedzy specjalistycznej.</p>
                <p>W części podstawowej wybierasz <strong>TAK albo NIE</strong>, a w specjalistycznej jedną z odpowiedzi <strong>A, B lub C</strong>. Pytania są warte 1, 2 albo 3 punkty.</p>
                <p>Cały egzamin trwa <strong>25 minut</strong>. Maksymalny wynik to 74 punkty, a próg zaliczenia wynosi <strong>68 punktów</strong>. Po zakończeniu pytania nie można wrócić do poprzedniej odpowiedzi.</p>
            </section>

            <section id="pytania-t" class="rankomat-guide__section">
                <h2>Pytania egzaminacyjne kat. T — czego trzeba się nauczyć?</h2>
                <p>Egzamin sprawdza przepisy ogólne oraz wiedzę potrzebną kierowcy pojazdu rolniczego. Zwróć szczególną uwagę na:</p>
                <ul>
                    <li>znaki, sygnały i pierwszeństwo przejazdu,</li>
                    <li>oznakowanie i oświetlenie ciągnika oraz przyczepy,</li>
                    <li>sprzęganie pojazdów i zabezpieczenie ładunku,</li>
                    <li>cofanie, skręcanie i manewrowanie zestawem,</li>
                    <li>widoczność i poruszanie się po drogach publicznych,</li>
                    <li>pierwszą pomoc i reakcję w sytuacji zagrożenia.</li>
                </ul>
                <p>Część podstawowa dotyczy ogólnych zasad ruchu, ale część specjalistyczna jest dostosowana do kategorii T.</p>
            </section>

            <section id="baza-t" class="rankomat-guide__section">
                <h2>Oficjalne pytania na prawo jazdy kat. T</h2>
                <p>Podczas nauki warto korzystać z aktualnej bazy pytań egzaminacyjnych i śledzić zmiany przepisów. Same pytania ogólne nie wystarczą — potrzebna jest też wiedza o ciągnikach, pojazdach wolnobieżnych i przyczepach.</p>
                <p>Na PrawkoNaRaz.pl możesz przeglądać pytania przypisane do kategorii T i wracać do zagadnień, które sprawiają trudność.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Zobacz pytania kategorii T →</a></p>
                @if ($sampleQuestions->isNotEmpty())
                    <div class="rankomat-guide__questions">
                        @foreach ($sampleQuestions as $question)
                            <a href="{{ $question['url'] }}"><span>#{{ $question['display_external_id'] ?? $question['external_id'] }}</span><strong>{{ $question['prompt_plain'] }}</strong><b aria-hidden="true">→</b></a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section id="uprawnienia-t" class="rankomat-guide__section">
                <h2>Co można prowadzić z prawem jazdy kategorii T?</h2>
                <p>Kategoria T uprawnia do kierowania <strong>ciągnikiem rolniczym</strong>, <strong>pojazdem wolnobieżnym</strong> oraz zespołem każdego z tych pojazdów z <strong>przyczepą lub przyczepami</strong>. Obejmuje też pojazdy kategorii AM.</p>
                <p>Ważną cechą kategorii T jest właśnie zakres dotyczący zestawów rolniczych. Nie oznacza ona natomiast uprawnienia do kierowania samochodem osobowym kategorii B.</p>
                <p><a class="rankomat-guide__inline-link" href="https://www.gov.pl/web/gov/kategorie-prawa-jazdy" rel="noopener noreferrer">Pełny zakres uprawnień na gov.pl ↗</a></p>
            </section>

            <section id="wiek-t" class="rankomat-guide__section">
                <h2>Od ilu lat można zrobić prawo jazdy kat. T?</h2>
                <p>Prawo jazdy kategorii T można uzyskać od <strong>16 lat</strong>. Kurs i egzamin można rozpocząć przed osiągnięciem tego wieku w terminach przewidzianych przepisami, lecz nie wcześniej niż 3 miesiące przed 16. urodzinami.</p>
                <p>Osoba niepełnoletnia potrzebuje <strong>pisemnej zgody rodzica lub opiekuna</strong> na uzyskanie kategorii T.</p>
                <p><a class="rankomat-guide__inline-link" href="https://www.gov.pl/web/gov/kategorie-prawa-jazdy" rel="noopener noreferrer">Sprawdź wymagania wiekowe na gov.pl ↗</a></p>
            </section>

            <section id="t-vs-b" class="rankomat-guide__section">
                <h2>Kategoria T a kategoria B — jaka jest różnica przy ciągniku?</h2>
                <p>W Polsce prawo jazdy kategorii B obejmuje ciągnik rolniczy i pojazd wolnobieżny, także z <strong>przyczepą lekką</strong> o DMC do 750 kg. Kategoria T obejmuje zestaw takiego pojazdu z <strong>przyczepą lub przyczepami</strong>.</p>
                <p>Różnica dotyczy zatem przede wszystkim zakresu zestawów rolniczych. Kategoria T nie zastępuje prawa jazdy B, jeśli chcesz prowadzić samochód osobowy.</p>
            </section>

            <section id="quad-kombajn-t" class="rankomat-guide__section">
                <h2>Czy kategorią T można prowadzić quada lub kombajn?</h2>
                <p><strong>Quad:</strong> to zależy od prawnej klasyfikacji pojazdu. Kategoria T obejmuje pojazdy kategorii AM, w tym czterokołowce lekkie spełniające jej wymagania. Nie obejmuje automatycznie każdego pojazdu nazywanego quadem.</p>
                <p><strong>Kombajn:</strong> kategoria T obejmuje pojazdy wolnobieżne, ale możliwość prowadzenia konkretnej maszyny po drodze publicznej zależy od jej klasyfikacji i zasad dopuszczenia do ruchu.</p>
            </section>

            <section id="nauka-t" class="rankomat-guide__section">
                <h2>Najtrudniejsze pytania na prawo jazdy kat. T</h2>
                <p>Więcej uwagi często wymagają zagadnienia dotyczące oznakowania i oświetlenia zestawu, sprzęgania przyczep, zabezpieczenia ładunku, gabarytów oraz manewrowania ciągnikiem po drodze publicznej.</p>
                <p>Nie ucz się odpowiedzi mechanicznie. Wróć do przepisu, zrozum zasadę, a następnie rozwiąż podobne pytania ponownie.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ url('/najtrudniejsze-pytania-na-prawo-jazdy') }}">Sprawdź najtrudniejsze pytania →</a></p>
                <h3>Jak przygotować się do egzaminu kat. T?</h3>
                <ol>
                    <li>Przejdź pytania tematyczne kategorii T.</li>
                    <li>Sprawdź błędne odpowiedzi i powtórz słabsze działy.</li>
                    <li>Ćwicz zagadnienia dotyczące ciągników, przyczep i znaków.</li>
                    <li>Rozwiązuj pełne testy na czas i dąż do stabilnych wyników.</li>
                </ol>
                <p>Jeden zdany test to początek. Regularne wyniki powyżej progu lepiej pokazują gotowość do egzaminu.</p>
            </section>

            <section id="praktyka-t" class="rankomat-guide__section">
                <h2>Jak wygląda egzamin praktyczny kat. T?</h2>
                <p>Po spełnieniu wymaganych warunków kandydat przystępuje również do części praktycznej. Oceniane są między innymi przygotowanie pojazdu, wymagane manewry oraz bezpieczne prowadzenie zespołu w ruchu drogowym.</p>
                <p>Znajomość teorii pomaga później skupić się na torze jazdy, pracy z przyczepą i obserwacji otoczenia.</p>
            </section>

            <section id="faq-t" class="rankomat-guide__section rankomat-guide__faq">
                <h2>Najczęstsze pytania o testy na prawo jazdy kat. T</h2>
                @foreach ($faq as $item)
                    <details><summary>{{ $item['question'] }}<span aria-hidden="true">+</span></summary><p>{{ $item['answer'] }}</p></details>
                @endforeach
            </section>

            <section id="jak-dzialaja-testy-t" class="rankomat-guide__section">
                <h2>Jak działają testy na PrawkoNaRaz.pl?</h2>
                <p>Publicznie możesz przeglądać pytania kategorii T. Pełna nauka i sesje egzaminacyjne są dostępne w panelu po zalogowaniu, zgodnie z zasadami dostępu pokazanymi w serwisie.</p>
                <p>Publiczny zestaw próbny na stronie testów zawiera <strong>20 pytań kategorii B</strong>; nie należy mylić go z pełnym egzaminem teoretycznym kategorii T.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Zacznij od pytań kat. T →</a></p>
            </section>

            <section class="rankomat-guide__section rankomat-guide__final">
                <h2>Testy na prawo jazdy kat. T {{ $year }} — zacznij naukę</h2>
                <p>Ćwicz pytania dotyczące ciągników rolniczych i przyczep, analizuj błędy oraz przygotuj się do teorii w WORD.</p>
                <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}" class="rankomat-guide__main-cta">Zobacz pytania na prawo jazdy kat. T</a>
            </section>
        </main>
    </div>
</section>
