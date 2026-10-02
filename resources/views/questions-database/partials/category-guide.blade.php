<div class="question-database-guide__editorial">
    <section class="rankomat-guide__section question-database-guide__about" aria-labelledby="o-bazie">
        <h2 id="o-bazie">Baza pytań kat. {{ $category->code }} z odpowiedziami — co tu znajdziesz?</h2>
        <p>
            Liczba pytań egzaminacyjnych przypisanych do prawa jazdy kat. {{ $category->code }}
            i opublikowanych w naszym serwisie: <strong>{{ number_format($categoryContent['total'], 0, ',', ' ') }}</strong>. {{ $categoryIntro }}
            Każda pozycja prowadzi do pełnej treści pytania i poprawnej odpowiedzi.
            Tam, gdzie są dostępne, znajdziesz również materiał zdjęciowy lub film oraz wyjaśnienie odpowiedzi.
        </p>
        <p>
            Nie ucz się wyłącznie numerów i wariantów odpowiedzi. Zwracaj uwagę na to, o co dokładnie pyta polecenie,
            jaki element sytuacji ma znaczenie i z jakiej zasady wynika rozwiązanie.
            Wyjaśnienia w PrawkoNaRaz są pomocą w nauce, a nie częścią urzędowej treści pytania.
        </p>
        <p class="question-database-guide__source">
            Źródło katalogu pytań egzaminacyjnych i materiałów:
            <a href="https://www.gov.pl/web/infrastruktura/jak-uzyskac-prawo-jazdy" target="_blank" rel="noopener">Ministerstwo Infrastruktury — informacje o uzyskaniu prawa jazdy</a>.
            Licznik na tej stronie dotyczy pytań opublikowanych w naszym serwisie dla tej kategorii.
        </p>
    </section>

    @if ($categoryContent['topics'] !== [])
        <section class="rankomat-guide__section" aria-labelledby="zagadnienia">
            <h2 id="zagadnienia">Jakie zagadnienia obejmują pytania kategorii {{ $category->code }}?</h2>
            <p>
                Poniższe działy wynikają z przypisania pytań w naszej bazie. Liczby pokazują zawartość tej kategorii,
                a przykłady prowadzą bezpośrednio do pytań z odpowiedziami. Wybraliśmy zagadnienia ogólne
                oraz specjalistyczne występujące w tej kategorii.
            </p>
            <div class="question-category-guide__topic-grid">
                @foreach (array_slice($categoryContent['topics'], 0, 6) as $topic)
                    <article class="question-category-guide__topic">
                        <div class="question-category-guide__topic-heading">
                            <h3>{{ $topic['label'] }}</h3>
                            <span>Liczba pytań: {{ number_format($topic['count'], 0, ',', ' ') }}</span>
                        </div>
                        <ul>
                            @foreach ($topic['examples'] as $example)
                                <li><a href="{{ $example['url'] }}"><small>Pytanie {{ $example['number'] }}</small>{{ $example['prompt'] }}</a></li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
            @if (count($categoryContent['topics']) > 6)
                <details class="question-category-guide__more-topics">
                    <summary>Pozostałe zagadnienia w tej kategorii ({{ count($categoryContent['topics']) - 6 }})</summary>
                    <ul>
                        @foreach (array_slice($categoryContent['topics'], 6) as $topic)
                            <li><span>{{ $topic['label'] }}</span><span>Pytań: {{ number_format($topic['count'], 0, ',', ' ') }}</span></li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </section>
    @endif

    <div class="question-database-guide__reading-grid">
        <section class="rankomat-guide__section" aria-labelledby="jak-sie-uczyc">
            <h2 id="jak-sie-uczyc">Jak uczyć się pytań na prawo jazdy kat. {{ $category->code }}?</h2>
            <ol class="question-database-guide__steps">
                <li><strong>Przeczytaj całą treść.</strong> Zauważ słowa zmieniające sens pytania: „możesz”, „musisz”, „zabronione”.</li>
                <li><strong>Przeanalizuj sytuację.</strong> Jeśli pytanie ma zdjęcie lub film, sprawdź znaki, sygnały i położenie uczestników ruchu.</li>
                <li><strong>Porównaj odpowiedź z zasadą.</strong> Po sprawdzeniu rozwiązania przeczytaj dostępne wyjaśnienie. W razie potrzeby wróć do <a href="{{ route('traffic-signs.index') }}">znaków drogowych</a>.</li>
                <li><strong>Ćwicz i wracaj do trudniejszych pytań.</strong> Przeglądanie bazy połącz z rozwiązywaniem <a href="{{ route('public.tests') }}">testów na prawo jazdy online</a>.</li>
            </ol>
        </section>
        <section class="rankomat-guide__section" aria-labelledby="typy-pytan">
            <h2 id="typy-pytan">Jak odpowiadać na pytania z tej bazy?</h2>
            @if ($categoryContent['boolean'] > 0)
                <p><strong>TAK / NIE — liczba pytań: {{ number_format($categoryContent['boolean'], 0, ',', ' ') }}.</strong> Oceń, czy stwierdzenie jest prawdziwe w przedstawionej sytuacji. Odpowiadaj na konkretne polecenie, a nie na ogólne skojarzenie z obrazem.</p>
            @endif
            @if ($categoryContent['single_choice'] > 0)
                <p><strong>A / B / C — liczba pytań: {{ number_format($categoryContent['single_choice'], 0, ',', ' ') }}.</strong> Porównaj wszystkie warianty i wybierz jedną prawidłową odpowiedź. Podobnie brzmiące odpowiedzi mogą różnić się istotnym szczegółem.</p>
            @endif
            <p>Wyszukiwarka nad listą pozwala odnaleźć pytanie po fragmencie treści lub numerze. Kolejne strony prowadzą do dalszych pytań tej samej kategorii.</p>
        </section>
    </div>

    <section class="rankomat-guide__section question-database-guide__practice">
        <h2>Przejdź od odpowiedzi do samodzielnego rozwiązywania</h2>
        <p>Znajomość odpowiedzi to początek. Sprawdź ją w praktyce: przejdź do <a href="{{ route('public.tests') }}">testów online</a> lub <a href="{{ route('public.tests.demo.show') }}">wypróbuj bezpłatne demo nauki</a>. Możesz też zobaczyć <a href="{{ route('public.questions.hub') }}">bazę pytań dla pozostałych kategorii</a>.</p>
    </section>

    <section class="rankomat-guide__section rankomat-guide__faq" aria-labelledby="pytania-i-odpowiedzi">
        <h2 id="pytania-i-odpowiedzi">Pytania i odpowiedzi o bazie kat. {{ $category->code }}</h2>
        <details>
            <summary>Ile pytań kategorii {{ $category->code }} jest w tej bazie?<span aria-hidden="true">+</span></summary>
            <p>Liczba opublikowanych pytań tej kategorii: {{ number_format($categoryContent['total'], 0, ',', ' ') }}. Liczymy unikalne numery źródłowe, a nie kopie tego samego pytania. Wynik wyszukiwania może być mniejszy — licznik kategorii nadal pokazuje całą jej opublikowaną zawartość.</p>
        </details>
        <details>
            <summary>Czy zobaczę prawidłowe odpowiedzi?<span aria-hidden="true">+</span></summary>
            <p>Tak. Kliknij treść pytania na liście, aby otworzyć jego stronę z poprawną odpowiedzią. Jeśli dla pytania przygotowano wyjaśnienie, możesz z niego skorzystać, aby lepiej zrozumieć rozwiązanie.</p>
        </details>
        <details>
            <summary>Czy pytania mogą powtarzać się w innych kategoriach?<span aria-hidden="true">+</span></summary>
            <p>Tak. Ten sam numer źródłowy może być przypisany do kilku kategorii prawa jazdy. Dlatego suma liczników kategorii nie musi odpowiadać liczbie unikalnych pytań w całym serwisie. Tutaj przeglądasz pytania przypisane do kat. {{ $category->code }}.</p>
        </details>
        <details>
            <summary>Czy przeglądanie bazy wymaga konta?<span aria-hidden="true">+</span></summary>
            <p>Publiczne strony pytań możesz przeglądać bez logowania. Nauka z zapisem postępu to osobna funkcja, dostępna w <a href="{{ route('session.index') }}">panelu nauki</a> zgodnie z uprawnieniami Twojego konta.</p>
        </details>
    </section>
</div>
