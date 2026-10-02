<div class="question-database-guide__editorial">
    <section class="rankomat-guide__section question-database-guide__about" aria-labelledby="question-database-about">
        <h2 id="question-database-about">Pytania na prawo jazdy z odpowiedziami i wyjaśnieniami</h2>
        <p>
            Przygotowanie do egzaminu teoretycznego zaczyna się od poznania pytań, ale nie kończy się na zapamiętaniu odpowiedzi.
            Ważne jest również zrozumienie sytuacji drogowej: znaczenia znaku, zachowania uczestników ruchu i warunków opisanych w pytaniu.
            Baza PrawkoNaRaz pozwala przeglądać pytania według kategorii prawa jazdy i przechodzić do ich szczegółowych omówień.
        </p>
        <p>
            Na stronie pytania sprawdzisz jego treść, dostępny materiał graficzny lub filmowy oraz prawidłową odpowiedź.
            Tam, gdzie udostępniono wyjaśnienie, możesz również przeanalizować zasadę, która prowadzi do właściwego rozwiązania.
            To przydatne zarówno podczas nauki nowego działu, jak i przy powrocie do pytania, które wcześniej sprawiło Ci trudność.
        </p>
        <p class="question-database-guide__source">
            Oficjalny katalog pytań egzaminacyjnych i materiały do pytań publikuje
            <a href="https://www.gov.pl/web/infrastruktura/jak-uzyskac-prawo-jazdy">Ministerstwo Infrastruktury na gov.pl</a>.
            Wyjaśnienia w serwisie są materiałem edukacyjnym uzupełniającym treść pytania, a nie częścią oficjalnego katalogu.
        </p>
    </section>

    <div class="question-database-guide__reading-grid">
        <section class="rankomat-guide__section" aria-labelledby="question-database-how">
            <h2 id="question-database-how">Jak korzystać z bazy pytań?</h2>
            <ol class="question-database-guide__steps">
                <li><strong>Wybierz swoją kategorię.</strong> Otwórz odpowiedni kafel, aby przeglądać pytania przypisane do Twojej kategorii prawa jazdy.</li>
                <li><strong>Znajdź interesujące Cię pytanie.</strong> W widoku kategorii możesz przejrzeć dostępne pytania lub użyć wyszukiwarki, wpisując numer albo fragment treści.</li>
                <li><strong>Najpierw oceń sytuację.</strong> Przeczytaj pytanie i obejrzyj materiał, zanim sprawdzisz prawidłową odpowiedź. Zwróć uwagę na szczegóły, które mogą zmienić sens pytania.</li>
                <li><strong>Sprawdź i utrwal rozwiązanie.</strong> Porównaj swoją odpowiedź z poprawną, zapoznaj się z dostępnym wyjaśnieniem, a następnie wróć do podobnych pytań podczas ćwiczeń.</li>
            </ol>
        </section>

        <section class="rankomat-guide__section" aria-labelledby="question-database-understanding">
            <h2 id="question-database-understanding">Ucz się zasady, nie tylko odpowiedzi</h2>
            <p>
                Dwa pytania mogą wyglądać podobnie, a mimo to wymagać innej odpowiedzi.
                Znaczenie ma to, o co dokładnie pytają: możliwość wykonania manewru, obowiązek kierowcy czy zachowanie w określonej sytuacji.
                Podczas nauki porównuj treść pytania z tym, co rzeczywiście widać na zdjęciu lub filmie.
            </p>
            <p>
                Jeśli trudność dotyczy oznakowania, zajrzyj do
                <a href="{{ route('traffic-signs.index', absolute: false) }}">znaków drogowych i ich znaczenia</a>.
                Gdy chcesz sprawdzić, które zagadnienia sprawiają kursantom najwięcej problemów, skorzystaj z
                <a href="{{ route('public.hardest-questions.index', absolute: false) }}">rankingu najtrudniejszych pytań</a>.
                Oba materiały pomagają wrócić do tematu, który wymaga dodatkowej uwagi.
            </p>
        </section>
    </div>

    <section class="rankomat-guide__section question-database-guide__practice" aria-labelledby="question-database-practice">
        <h2 id="question-database-practice">Od przeglądania bazy do ćwiczenia testów</h2>
        <p>
            Baza pytań służy do odnajdywania konkretnych zadań i sprawdzania odpowiedzi.
            Jeśli chcesz samodzielnie odpowiadać na kolejne pytania, przejdź do
            <a href="{{ route('public.tests', absolute: false) }}">testów na prawo jazdy online</a>.
            Możesz zacząć od <a href="{{ route('public.tests.demo.show', absolute: false) }}">bezpłatnego demo</a>, żeby poznać sposób pracy z pytaniami.
        </p>
        <p>
            W <a href="{{ route('session.index', absolute: false) }}">panelu nauki po zalogowaniu</a>
            znajdziesz działy i ustawienia sesji. Dzięki temu możesz oddzielić poznawanie nowych zagadnień od powtarzania materiału.
            Wybierz sposób pracy odpowiadający temu, czego teraz potrzebujesz: zrozumienie jednego pytania, naukę działu lub ćwiczenie testu.
        </p>
    </section>

    <section class="rankomat-guide__section rankomat-guide__faq" aria-labelledby="question-database-faq">
        <h2 id="question-database-faq">Najczęstsze pytania o bazę</h2>
        <details>
            <summary>Czy mogę przeglądać pytania bez zakładania konta?<span aria-hidden="true">+</span></summary>
            <p>Tak. Kategorie i publiczne strony pytań możesz przeglądać bez logowania. Sposób ćwiczenia pytań możesz wcześniej sprawdzić w demo. Panel nauki z działami i ustawieniami sesji wymaga zalogowania; dostęp do pełnej nauki zależy od uprawnień konta.</p>
        </details>
        <details>
            <summary>Dlaczego liczba pytań w kategoriach nie sumuje się do liczby całej bazy?<span aria-hidden="true">+</span></summary>
            <p>
                To samo pytanie może być przypisane do kilku kategorii prawa jazdy.
                Licznik całej bazy pokazuje odrębne pytania dostępne w serwisie. Obecnie jest ich {{ number_format($canonicalQuestionsCount, 0, ',', ' ') }}.
                Natomiast każdy kafel podaje liczbę pytań przypisanych do danej kategorii. Sumowanie kafli może więc wielokrotnie uwzględnić te same pytania.
            </p>
        </details>
        <details>
            <summary>Jak odnaleźć pytanie, którego numer lub treść już znam?<span aria-hidden="true">+</span></summary>
            <p>Wybierz kategorię prawa jazdy i skorzystaj z wyszukiwarki nad pytaniami. Wpisz numer albo charakterystyczny fragment treści, a następnie otwórz odpowiedni wynik, aby sprawdzić odpowiedź i dostępne omówienie.</p>
        </details>
        <details>
            <summary>Czym różni się poprawna odpowiedź od wyjaśnienia?<span aria-hidden="true">+</span></summary>
            <p>Poprawna odpowiedź wskazuje właściwe rozwiązanie zadania. Wyjaśnienie opisuje, dlaczego jest ono właściwe i na co zwrócić uwagę w przedstawionej sytuacji. Dostępne omówienia są uzupełnieniem nauki; samo wskazanie odpowiedzi nie zastępuje zrozumienia zasady.</p>
        </details>
    </section>
</div>
