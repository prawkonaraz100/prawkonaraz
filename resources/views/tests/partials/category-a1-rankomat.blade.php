<section class="rankomat-guide">
    <div class="rankomat-guide__shell">
        @include('components.site.test-online-sidebar')

        <main class="rankomat-guide__main">
            <div class="rankomat-guide__category">KATEGORIA A1</div>
            <h1>Testy na prawo jazdy kat. A1 {{ $year }}</h1>
            <div class="rankomat-guide__meta">
                <div class="rankomat-guide__author">
                    <img src="/images/site-brand-mark-shield-v2-optimized.webp" alt="" width="42" height="42">
                    <div><strong>PrawkoNaRaz.pl</strong><span>Materiały do nauki</span></div>
                </div>
                <div class="rankomat-guide__meta-divider" aria-hidden="true"></div>
                <div class="rankomat-guide__updated"><strong>Aktualizacja</strong><span>{{ $year }}</span></div>
                <div class="rankomat-guide__trust">✓ Aktualne zasady egzaminu</div>
            </div>
            <p class="rankomat-guide__lead">Przygotuj się do egzaminu teoretycznego na <strong>prawo jazdy kategorii A1</strong>. Przeglądaj pytania egzaminacyjne, poznaj zasady dotyczące motocykli do 125 cm³ i sprawdzaj wiedzę przed egzaminem w WORD.</p>
            <a href="{{ route('public.tests', absolute: false) }}" class="rankomat-guide__mobile-cta">Przejdź do testów online</a>
            <figure class="rankomat-guide__hero">
                <img src="/images/testy/prawo-jazdy-kat-a1-editorial.jpg" alt="Motocyklista na lekkim motocyklu na drodze — przygotowanie do egzaminu kategorii A1" width="1774" height="887" decoding="async" fetchpriority="high">
            </figure>

            <section class="rankomat-guide__important" aria-labelledby="category-a1-important">
                <h2 id="category-a1-important">Najważniejsze informacje</h2>
                <ul>
                    <li>Egzamin teoretyczny obejmuje <strong>32 pytania</strong>: 20 podstawowych i 12 specjalistycznych.</li>
                    <li>Na rozwiązanie masz <strong>25 minut</strong>; do zdania potrzeba <strong>68 z 74 punktów</strong>.</li>
                    <li>Motocykl A1: do <strong>125 cm³, 11 kW i 0,1 kW/kg</strong> — wszystkie limity obowiązują łącznie.</li>
                    <li>Prawo jazdy kat. A1 można uzyskać od <strong>16 lat</strong>.</li>
                    <li>Posiadanie kat. B przez 3 lata pozwala w Polsce jeździć określonym motocyklem 125 cm³, ale <strong>nie nadaje kategorii A1</strong>.</li>
                </ul>
            </section>

            <nav class="rankomat-guide__toc" aria-label="Spis treści">
                <p>Przejdź do sekcji:</p>
                <ol>
                    <li><a href="#asystent-ai-a1">Asystent AI do testów kat. A1</a></li>
                    <li><a href="#przygotowanie-a1">Przygotowanie do egzaminu WORD</a></li>
                    <li><a href="#egzamin-a1">Egzamin teoretyczny</a></li>
                    <li><a href="#pytania-a1">Zakres pytań egzaminacyjnych</a></li>
                    <li><a href="#baza-a1">Pytania kat. A1 w bazie</a></li>
                    <li><a href="#uprawnienia-a1">Uprawnienia i motocykl 125 cm³</a></li>
                    <li><a href="#wiek-a1">Minimalny wiek</a></li>
                    <li><a href="#a1-vs-b">Kategoria A1 a B</a></li>
                    <li><a href="#a1-vs-am-a2-a">A1, AM, A2 i A</a></li>
                    <li><a href="#nauka-a1">Plan nauki i test próbny</a></li>
                    <li><a href="#praktyka-a1">Egzamin praktyczny</a></li>
                    <li><a href="#faq-a1">Najczęstsze pytania</a></li>
                    <li><a href="#jak-dzialaja-testy-a1">Jak działają testy w serwisie?</a></li>
                </ol>
            </nav>

            <section id="asystent-ai-a1" class="rankomat-guide__section">
                <h2>Asystent AI do testów na prawo jazdy kat. A1</h2>
                <p>Nie rozumiesz pytania dotyczącego motocykla lub pierwszeństwa? Ustal, jaka zasada decyduje o poprawnej odpowiedzi, zamiast zapamiętywać sam wariant.</p>
                <p><strong>Asystent AI PrawkoNaRaz</strong> może pomóc wyjaśnić trudniejsze pytania podczas nauki. Istotne zasady potwierdzaj w aktualnych przepisach.</p>
                <div class="rankomat-guide__callout rankomat-guide__callout--ai">
                    <div class="rankomat-guide__callout-title"><span>AI</span> Ucz się ze zrozumieniem</div>
                    <p>Po błędnej odpowiedzi przeanalizuj sytuację drogową i wróć do podobnych pytań.</p>
                    <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Przeglądaj pytania kategorii A1 →</a>
                </div>
            </section>

            <section id="przygotowanie-a1" class="rankomat-guide__section">
                <h2>Testy na prawo jazdy kat. A1 — przygotowanie do egzaminu WORD</h2>
                <p><strong>Testy kat. A1</strong> są przeznaczone dla osób przygotowujących się do jazdy motocyklem spełniającym limity tej kategorii. Łączą przepisy ogólne z zagadnieniami dotyczącymi bezpieczeństwa motocyklisty.</p>
                <p>Regularnie ćwicz obserwację otoczenia, hamowanie, pokonywanie zakrętów i ocenę przyczepności. To pomoże rozpoznawać sytuacje zagrożenia, a nie tylko odtwarzać odpowiedzi.</p>
                <div class="rankomat-guide__categories" aria-label="Inne kategorie testów">
                    @foreach ($categoryMenu as $item)
                        <a href="{{ $item['href'] }}" class="{{ $item['code'] === 'A1' ? 'is-active' : '' }}">{{ $item['code'] }}</a>
                    @endforeach
                </div>
            </section>

            <section id="egzamin-a1" class="rankomat-guide__section">
                <h2>Jak wygląda egzamin teoretyczny na prawo jazdy kat. A1?</h2>
                <p>Egzamin w WORD odbywa się przy komputerze i składa z <strong>32 pytań</strong>: 20 podstawowych oraz 12 specjalistycznych.</p>
                <p>W części podstawowej wybierasz <strong>TAK albo NIE</strong>, a w specjalistycznej jedną odpowiedź <strong>A, B lub C</strong>. Pytania są warte 1, 2 albo 3 punkty.</p>
                <p>Na cały egzamin przewidziano <strong>25 minut</strong>. Maksymalnie można zdobyć 74 punkty; wynik pozytywny wymaga <strong>co najmniej 68 punktów</strong>. Po zakończeniu pytania nie można wrócić do poprzedniej odpowiedzi.</p>
            </section>

            <section id="pytania-a1" class="rankomat-guide__section">
                <h2>Pytania egzaminacyjne kat. A1 — czego trzeba się nauczyć?</h2>
                <p>Oprócz znaków, sygnałów i pierwszeństwa przejazdu zwróć szczególną uwagę na:</p>
                <ul>
                    <li>bezpieczną pozycję motocyklisty na jezdni i jego widoczność,</li>
                    <li>hamowanie, odstęp od innych pojazdów i pokonywanie zakrętów,</li>
                    <li>przyczepność na mokrej lub zabrudzonej nawierzchni,</li>
                    <li>obserwację martwych pól i zachowanie wobec pieszych,</li>
                    <li>wyposażenie, stan techniczny motocykla i pierwszą pomoc.</li>
                </ul>
                <p>Zrozumienie zasad pomoże również wtedy, gdy podobna sytuacja pojawi się na egzaminie w inny sposób.</p>
            </section>

            <section id="baza-a1" class="rankomat-guide__section">
                <h2>Pytania na prawo jazdy kat. A1 w naszej bazie</h2>
                <p>Przeglądaj pytania przypisane do kategorii A1: zarówno ogólne zasady ruchu, jak i zagadnienia specjalistyczne dotyczące motocykli.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Zobacz pytania kategorii A1 →</a></p>
                @if ($sampleQuestions->isNotEmpty())
                    <div class="rankomat-guide__questions">
                        @foreach ($sampleQuestions as $question)
                            <a href="{{ $question['url'] }}"><span>#{{ $question['display_external_id'] ?? $question['external_id'] }}</span><strong>{{ $question['prompt_plain'] }}</strong><b aria-hidden="true">→</b></a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section id="uprawnienia-a1" class="rankomat-guide__section">
                <h2>Co można prowadzić z prawem jazdy kategorii A1?</h2>
                <p>Kategoria A1 obejmuje motocykl o pojemności silnika do <strong>125 cm³</strong>, mocy do <strong>11 kW</strong> i stosunku mocy do masy własnej do <strong>0,1 kW/kg</strong>. Każdy z tych parametrów musi mieścić się w limicie — sama pojemność nie wystarcza.</p>
                <p>Uprawnia także do kierowania motocyklem trójkołowym o mocy do <strong>15 kW</strong> i pojazdami kategorii AM. W Polsce obejmuje również zespół pojazdu tej kategorii z przyczepą.</p>
                <p>Przed wyborem motocykla „na 125” sprawdź pojemność, moc i stosunek mocy do masy w dokumentach pojazdu.</p>
                <p><a class="rankomat-guide__inline-link" href="https://www.gov.pl/web/gov/kategorie-prawa-jazdy" rel="noopener noreferrer">Pełny zakres uprawnień na gov.pl ↗</a></p>
            </section>

            <section id="wiek-a1" class="rankomat-guide__section">
                <h2>Od ilu lat można zrobić prawo jazdy kat. A1?</h2>
                <p>Kategorię A1 można uzyskać od <strong>16 lat</strong>. Kurs i egzamin można rozpocząć wcześniej, lecz nie wcześniej niż 3 miesiące przed osiągnięciem tego wieku.</p>
                <p>Osoba niepełnoletnia potrzebuje <strong>pisemnej zgody rodzica lub opiekuna</strong>.</p>
            </section>

            <section id="a1-vs-b" class="rankomat-guide__section">
                <h2>Masz kategorię B? Kiedy możesz jeździć motocyklem 125 cm³?</h2>
                <p>Po posiadaniu prawa jazdy B przez <strong>co najmniej 3 lata</strong> można <strong>w Polsce</strong> prowadzić motocykl do 125 cm³, 11 kW i 0,1 kW/kg.</p>
                <p>Nie oznacza to automatycznego uzyskania prawa jazdy kategorii A1. Jeżeli potrzebujesz formalnej kategorii A1, musisz uzyskać ją na właściwych zasadach.</p>
            </section>

            <section id="a1-vs-am-a2-a" class="rankomat-guide__section">
                <h2>A1, AM, A2 czy A — czym się różnią?</h2>
                <p><strong>AM</strong> dotyczy między innymi motorowerów i czterokołowców lekkich; A1 obejmuje także te pojazdy. <strong>A1</strong> rozszerza uprawnienia o motocykle spełniające limity 125 cm³, 11 kW i 0,1 kW/kg.</p>
                <p><strong>A2</strong> pozwala prowadzić mocniejsze motocykle w granicach przewidzianych dla tej kategorii, a <strong>A</strong> motocykle bez ograniczenia mocy. Minimalny wiek i wymagania dla każdej kategorii są odrębne.</p>
            </section>

            <section id="nauka-a1" class="rankomat-guide__section">
                <h2>Najtrudniejsze pytania i plan nauki do kat. A1</h2>
                <p>Wiele błędów dotyczy pierwszeństwa, hamowania na różnych nawierzchniach, widoczności motocyklisty i bezpiecznej reakcji na przeszkodę. Po błędzie sprawdź przepis i rozwiąż podobne pytania ponownie.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ url('/najtrudniejsze-pytania-na-prawo-jazdy') }}">Sprawdź najtrudniejsze pytania →</a></p>
                <h3>Jak przygotować się do testu próbnego kat. A1?</h3>
                <ol>
                    <li>Przejdź pytania tematyczne kategorii A1.</li>
                    <li>Analizuj błędy i powtarzaj przepisy oraz znaki.</li>
                    <li>Skup się na bezpieczeństwie jazdy motocyklem.</li>
                    <li>Rozwiązuj pełne sesje na czas i dąż do stabilnych wyników.</li>
                </ol>
                <p>Jeden zdany test nie wystarczy do oceny gotowości. Regularnie sprawdzaj wynik i wracaj do słabszych tematów.</p>
            </section>

            <section id="praktyka-a1" class="rankomat-guide__section">
                <h2>Jak wygląda egzamin praktyczny kat. A1?</h2>
                <p>Egzamin praktyczny obejmuje przygotowanie motocykla, zadania na placu manewrowym oraz jazdę w ruchu drogowym. Liczą się równowaga, bezpieczne manewry, obserwacja otoczenia i prawidłowe sygnalizowanie zamiarów.</p>
                <p>Znajomość teorii pozwala podczas jazdy skupić się na technice i sytuacji na drodze.</p>
            </section>

            <section id="faq-a1" class="rankomat-guide__section rankomat-guide__faq">
                <h2>Najczęstsze pytania o testy na prawo jazdy kat. A1</h2>
                @foreach ($faq as $item)
                    <details><summary>{{ $item['question'] }}<span aria-hidden="true">+</span></summary><p>{{ $item['answer'] }}</p></details>
                @endforeach
            </section>

            <section id="jak-dzialaja-testy-a1" class="rankomat-guide__section">
                <h2>Jak działają testy na PrawkoNaRaz.pl?</h2>
                <p>Publicznie możesz przeglądać pytania kategorii A1. Pełna nauka i sesje egzaminacyjne są dostępne w panelu po zalogowaniu, zgodnie z zasadami dostępu pokazanymi w serwisie.</p>
                <p>Publiczny zestaw próbny na stronie testów zawiera <strong>20 pytań kategorii B</strong>; nie jest pełnym egzaminem kategorii A1.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Zacznij od pytań kat. A1 →</a></p>
            </section>

            <section class="rankomat-guide__section rankomat-guide__final">
                <h2>Testy na prawo jazdy kat. A1 {{ $year }} — zacznij naukę</h2>
                <p>Ćwicz pytania dotyczące motocykli, analizuj błędy i przygotuj się do egzaminu teoretycznego w WORD.</p>
                <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}" class="rankomat-guide__main-cta">Zobacz pytania na prawo jazdy kat. A1</a>
            </section>
        </main>
    </div>
</section>
