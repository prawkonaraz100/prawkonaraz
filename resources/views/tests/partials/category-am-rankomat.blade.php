<section class="rankomat-guide">
    <div class="rankomat-guide__shell">
        @include('components.site.test-online-sidebar')

        <main class="rankomat-guide__main">
            <div class="rankomat-guide__category">KATEGORIA AM</div>
            <h1>Testy na prawo jazdy kat. AM {{ $year }}</h1>
            <div class="rankomat-guide__meta">
                <div class="rankomat-guide__author">
                    <img src="/images/site-brand-mark-shield-v2-optimized.webp" alt="" width="42" height="42">
                    <div><strong>PrawkoNaRaz.pl</strong><span>Materiały do nauki</span></div>
                </div>
                <div class="rankomat-guide__meta-divider" aria-hidden="true"></div>
                <div class="rankomat-guide__updated"><strong>Aktualizacja</strong><span>{{ $year }}</span></div>
                <div class="rankomat-guide__trust">✓ Aktualne zasady egzaminu</div>
            </div>
            <p class="rankomat-guide__lead">Przygotuj się do egzaminu teoretycznego na <strong>prawo jazdy kategorii AM</strong>. Przeglądaj pytania egzaminacyjne dotyczące motoroweru i czterokołowca lekkiego oraz sprawdzaj wiedzę przed egzaminem w WORD.</p>
            <a href="{{ route('public.tests', absolute: false) }}" class="rankomat-guide__mobile-cta">Przejdź do testów online</a>
            <figure class="rankomat-guide__hero">
                <img src="/images/testy/prawo-jazdy-kat-am-editorial.jpg" alt="Osoba w kasku jadąca motorowerem na drodze — przygotowanie do egzaminu kategorii AM" width="1774" height="887" decoding="async" fetchpriority="high">
            </figure>

            <section class="rankomat-guide__important" aria-labelledby="category-am-important">
                <h2 id="category-am-important">Najważniejsze informacje</h2>
                <ul>
                    <li>Egzamin teoretyczny obejmuje <strong>32 pytania</strong>: 20 podstawowych i 12 specjalistycznych.</li>
                    <li>Na rozwiązanie masz <strong>25 minut</strong>; do zdania potrzeba <strong>68 z 74 punktów</strong>.</li>
                    <li>Kategoria AM obejmuje <strong>motorower i czterokołowiec lekki</strong>.</li>
                    <li>Prawo jazdy kat. AM można uzyskać od <strong>14 lat</strong>.</li>
                    <li>Motorower może rozwijać konstrukcyjnie najwyżej <strong>45 km/h</strong>.</li>
                </ul>
            </section>

            <nav class="rankomat-guide__toc" aria-label="Spis treści">
                <p>Przejdź do sekcji:</p>
                <ol>
                    <li><a href="#asystent-ai-am">Asystent AI do testów kat. AM</a></li>
                    <li><a href="#przygotowanie-am">Przygotowanie do egzaminu WORD</a></li>
                    <li><a href="#egzamin-am">Egzamin teoretyczny</a></li>
                    <li><a href="#pytania-am">Zakres pytań</a></li>
                    <li><a href="#baza-am">Pytania kat. AM w bazie</a></li>
                    <li><a href="#uprawnienia-am">Uprawnienia kategorii AM</a></li>
                    <li><a href="#motorower-am">Motorower i skuter</a></li>
                    <li><a href="#czterokolowiec-am">Czterokołowiec lekki</a></li>
                    <li><a href="#wiek-am">Wiek i karta motorowerowa</a></li>
                    <li><a href="#am-vs-a1-b">AM, A1 i B</a></li>
                    <li><a href="#nauka-am">Plan nauki i test próbny</a></li>
                    <li><a href="#bezpieczenstwo-am">Bezpieczeństwo i praktyka</a></li>
                    <li><a href="#faq-am">Najczęstsze pytania</a></li>
                    <li><a href="#jak-dzialaja-testy-am">Jak działają testy w serwisie?</a></li>
                </ol>
            </nav>

            <section id="asystent-ai-am" class="rankomat-guide__section">
                <h2>Asystent AI do testów na prawo jazdy kat. AM</h2>
                <p>Nie rozumiesz pytania o znak, pierwszeństwo albo manewr? Warto sprawdzić zasadę, która decyduje o poprawnej odpowiedzi.</p>
                <p><strong>Asystent AI PrawkoNaRaz</strong> może pomóc wyjaśnić trudniejsze pytania podczas nauki. Istotne zasady potwierdzaj w aktualnych przepisach.</p>
                <div class="rankomat-guide__callout rankomat-guide__callout--ai">
                    <div class="rankomat-guide__callout-title"><span>AI</span> Ucz się ze zrozumieniem</div>
                    <p>Po błędzie przeanalizuj sytuację drogową i wróć do podobnych pytań.</p>
                    <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Przeglądaj pytania kategorii AM →</a>
                </div>
            </section>

            <section id="przygotowanie-am" class="rankomat-guide__section">
                <h2>Testy na prawo jazdy kat. AM — przygotowanie do egzaminu WORD</h2>
                <p><strong>Testy kat. AM</strong> pomagają przygotować się do teorii na motorower lub czterokołowiec lekki. Ważna jest znajomość przepisów oraz bezpieczne zachowanie na drodze.</p>
                <p>Ćwicz pierwszeństwo, znaki i sygnały, włączanie się do ruchu oraz zachowanie wobec pieszych i rowerzystów. Regularne powtórki pozwalają znaleźć tematy, które wymagają dodatkowej nauki.</p>
                <div class="rankomat-guide__categories" aria-label="Inne kategorie testów">
                    @foreach ($categoryMenu as $item)
                        <a href="{{ $item['href'] }}" class="{{ $item['code'] === 'AM' ? 'is-active' : '' }}">{{ $item['code'] }}</a>
                    @endforeach
                </div>
            </section>

            <section id="egzamin-am" class="rankomat-guide__section">
                <h2>Jak wygląda egzamin teoretyczny na prawo jazdy kat. AM?</h2>
                <p>Egzamin w WORD odbywa się przy komputerze i zawiera <strong>32 pytania</strong>: 20 z wiedzy podstawowej i 12 z wiedzy specjalistycznej.</p>
                <p>W części podstawowej wybierasz <strong>TAK albo NIE</strong>, a w specjalistycznej jedną odpowiedź <strong>A, B lub C</strong>. Pytania są warte 1, 2 albo 3 punkty.</p>
                <p>Na cały egzamin masz <strong>25 minut</strong>. Maksymalny wynik to 74 punkty, a do zaliczenia potrzeba <strong>co najmniej 68 punktów</strong>. Po zakończeniu pytania nie można wrócić do poprzedniej odpowiedzi.</p>
            </section>

            <section id="pytania-am" class="rankomat-guide__section">
                <h2>Pytania egzaminacyjne kat. AM — czego trzeba się nauczyć?</h2>
                <p>Poza podstawowymi przepisami ruchu zwróć szczególną uwagę na:</p>
                <ul>
                    <li>znaki, sygnały i pierwszeństwo na skrzyżowaniach,</li>
                    <li>bezpieczne włączanie się do ruchu i wyprzedzanie,</li>
                    <li>zachowanie wobec pieszych i rowerzystów,</li>
                    <li>prędkość, hamowanie i obserwację otoczenia,</li>
                    <li>wyposażenie pojazdu i pierwszą pomoc.</li>
                </ul>
                <p>Motorowerzysta nie jest osłonięty nadwoziem samochodu. Dlatego wiedzę z testów warto od razu łączyć z przewidywaniem zagrożeń podczas jazdy.</p>
            </section>

            <section id="baza-am" class="rankomat-guide__section">
                <h2>Pytania na prawo jazdy kat. AM w naszej bazie</h2>
                <p>Przeglądaj pytania przypisane do kategorii AM: ogólne zasady ruchu oraz zagadnienia specjalistyczne dotyczące motorowerów i lekkich czterokołowców.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Zobacz pytania kategorii AM →</a></p>
                @if ($sampleQuestions->isNotEmpty())
                    <div class="rankomat-guide__questions">
                        @foreach ($sampleQuestions as $question)
                            <a href="{{ $question['url'] }}"><span>#{{ $question['display_external_id'] ?? $question['external_id'] }}</span><strong>{{ $question['prompt_plain'] }}</strong><b aria-hidden="true">→</b></a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section id="uprawnienia-am" class="rankomat-guide__section">
                <h2>Co można prowadzić z prawem jazdy kategorii AM?</h2>
                <p>Kategoria AM uprawnia do kierowania <strong>motorowerem</strong> i <strong>czterokołowcem lekkim</strong>. W Polsce obejmuje także zespół pojazdu tej kategorii z przyczepą.</p>
                <p>Nie uprawnia natomiast do kierowania samochodem osobowym ani motocyklem wymagającym wyższej kategorii. Liczy się prawna klasyfikacja konkretnego pojazdu.</p>
                <p><a class="rankomat-guide__inline-link" href="https://www.gov.pl/web/gov/kategorie-prawa-jazdy" rel="noopener noreferrer">Pełny zakres uprawnień na gov.pl ↗</a></p>
            </section>

            <section id="motorower-am" class="rankomat-guide__section">
                <h2>Motorower i skuter na kategorię AM</h2>
                <p>Motorower jest pojazdem dwu- lub trójkołowym, którego konstrukcja ogranicza prędkość jazdy do <strong>45 km/h</strong>. Silnik spalinowy może mieć do <strong>50 cm³</strong>, a elektryczny moc do <strong>4 kW</strong>.</p>
                <p>Możesz prowadzić skuter na AM, jeżeli konkretny pojazd jest prawnie sklasyfikowany jako motorower. Nie każdy skuter spełnia te wymagania; większy pojazd może wymagać kategorii A1, A2 lub A.</p>
            </section>

            <section id="czterokolowiec-am" class="rankomat-guide__section">
                <h2>Czterokołowiec lekki i mały quad na AM</h2>
                <p>Czterokołowiec lekki ma masę własną do <strong>350 kg</strong>, a jego konstrukcja ogranicza prędkość do <strong>45 km/h</strong>. Do tej klasy mogą należeć niektóre małe quady i mikrosamochody.</p>
                <p>O uprawnieniu decyduje klasyfikacja i dokumenty pojazdu, nie jego potoczna nazwa ani wygląd.</p>
            </section>

            <section id="wiek-am" class="rankomat-guide__section">
                <h2>Od ilu lat można zrobić prawo jazdy AM?</h2>
                <p>Kategorię AM można uzyskać od <strong>14 lat</strong>. Kurs i egzamin można rozpocząć przed osiągnięciem tego wieku, jednak nie wcześniej niż 3 miesiące przed 14. urodzinami. Osoba niepełnoletnia potrzebuje pisemnej zgody rodzica lub opiekuna.</p>
                <h3>Kategoria AM a dawna karta motorowerowa</h3>
                <p>Kategoria AM zastąpiła dawny system kart motorowerowych. Osoba posiadająca taką kartę może ją wymienić na prawo jazdy kategorii AM.</p>
            </section>

            <section id="am-vs-a1-b" class="rankomat-guide__section">
                <h2>AM, A1 czy B — kiedy potrzebujesz kategorii AM?</h2>
                <p><strong>AM</strong> jest dostępna od 14 lat i obejmuje motorowery oraz lekkie czterokołowce. <strong>A1</strong> rozszerza uprawnienia o motocykle spełniające limity tej kategorii i obejmuje również pojazdy AM.</p>
                <p>Posiadacz kategorii <strong>B</strong> także może prowadzić pojazdy określone dla AM. Jeżeli planujesz jeździć tylko motorowerem, sprawdź, czy masz już odpowiednie uprawnienie z innej kategorii.</p>
            </section>

            <section id="nauka-am" class="rankomat-guide__section">
                <h2>Najtrudniejsze pytania i plan nauki do kat. AM</h2>
                <p>Najwięcej uwagi poświęć pierwszeństwu, znakom, bezpiecznemu odstępowi, hamowaniu i zachowaniu wobec pieszych. Po błędzie sprawdź, z jakiej zasady wynika poprawna odpowiedź.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ url('/najtrudniejsze-pytania-na-prawo-jazdy') }}">Sprawdź najtrudniejsze pytania →</a></p>
                <h3>Jak przygotować się do testu próbnego kat. AM?</h3>
                <ol>
                    <li>Przejdź pytania tematyczne kategorii AM.</li>
                    <li>Powtarzaj przepisy i analizuj błędne odpowiedzi.</li>
                    <li>Ćwicz sytuacje charakterystyczne dla motorowerzysty.</li>
                    <li>Rozwiązuj pełne sesje na czas i obserwuj wynik.</li>
                </ol>
                <p>Jeden pozytywny wynik nie oznacza jeszcze gotowości. Regularnie wracaj do tematów, w których nadal popełniasz błędy.</p>
            </section>

            <section id="bezpieczenstwo-am" class="rankomat-guide__section">
                <h2>Bezpieczeństwo podczas jazdy i egzamin praktyczny kat. AM</h2>
                <p>Motorower jest niewielki, a jego kierowca nie ma ochrony nadwozia. Ważne są obserwacja otoczenia, przewidywanie zachowania innych, sygnalizowanie manewrów i dostosowanie jazdy do warunków.</p>
                <p>Egzamin praktyczny sprawdza przygotowanie pojazdu, wykonywanie wymaganych manewrów oraz bezpieczne zachowanie w ruchu drogowym. Znajomość teorii pomaga skupić uwagę na obsłudze pojazdu i sytuacji na drodze.</p>
            </section>

            <section id="faq-am" class="rankomat-guide__section rankomat-guide__faq">
                <h2>Najczęstsze pytania o testy na prawo jazdy kat. AM</h2>
                @foreach ($faq as $item)
                    <details><summary>{{ $item['question'] }}<span aria-hidden="true">+</span></summary><p>{{ $item['answer'] }}</p></details>
                @endforeach
            </section>

            <section id="jak-dzialaja-testy-am" class="rankomat-guide__section">
                <h2>Jak działają testy na PrawkoNaRaz.pl?</h2>
                <p>Publicznie możesz przeglądać pytania kategorii AM. Pełna nauka i sesje egzaminacyjne są dostępne w panelu po zalogowaniu, zgodnie z zasadami dostępu pokazanymi w serwisie.</p>
                <p>Publiczny zestaw próbny na stronie testów zawiera <strong>20 pytań kategorii B</strong>; nie jest pełnym egzaminem kategorii AM.</p>
                <p><a class="rankomat-guide__inline-link" href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}">Zacznij od pytań kat. AM →</a></p>
            </section>

            <section class="rankomat-guide__section rankomat-guide__final">
                <h2>Testy na prawo jazdy kat. AM {{ $year }} — zacznij naukę</h2>
                <p>Ćwicz pytania dotyczące motorowerów i czterokołowców lekkich, analizuj błędy i przygotuj się do teorii w WORD.</p>
                <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}" class="rankomat-guide__main-cta">Zobacz pytania na prawo jazdy kat. AM</a>
            </section>
        </main>
    </div>
</section>
