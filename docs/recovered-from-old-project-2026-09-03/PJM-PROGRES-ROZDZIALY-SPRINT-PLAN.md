# PJM Progres Po Rozdzialach - Plan Sprintow

Status dokumentu: `plan implementacyjny po realizacji sprintow 3-4`
Data: `2026-05-06`
Branch: `codex/fix-pjm-question-pool-count`
Zakres: darmowy modul PJM, progres uzytkownika po dzialach, pelny dzial PJM w jednej sesji, kontynuacja od aktualnego rozdzialu, bez mieszania z platna nauka klasyczna.

Dokument powiazany: `docs/PJM-STARTER-UX-NAUKA-SPRINT-PLAN.md` opisuje osobny sprint UX dla ekranu `/nauka`: widocznosc kafla tylko dla preferencji PJM, wiekszy kafel ze strzalkami, wyszarzenie opcji pelnej nauki i jedno CTA `Aktywuj pelna nauke`.

## Problem

Modul PJM pokazuje uzytkownikowi tylko ogolna liczbe pytan z tlumaczeniem PJM i uruchamia krotka sesje z globalnej puli. Uzytkownik nie widzi, na jakim etapie jest, nie widzi dzialow i nie ma jasnego mechanizmu kontynuacji od aktualnego rozdzialu.

Ekran wyniku moze dodatkowo sugerowac, ze uzytkownik ukonczyl cala pule PJM, mimo ze faktycznie ukonczyl tylko jedna sesje.

Po sprintach 1-2 ekran startu i wynik zaczely rozumiec progres po dzialach, ale nadal zostal krytyczny problem logiczny: sesje PJM sa ograniczane do krotkich paczek, zamiast uruchamiac caly aktualny dzial PJM.

## Aktualne Ustalenia

Dla kategorii `B` w aktualnej bazie:

- wszystkie gotowe pytania: `2194`,
- pytania z filmem PJM: `969`,
- pytania bez filmu PJM: `1225`,
- realne pokrycie PJM: `44.17%`,
- uzytkownik z sesji `602` w momencie pierwszej diagnozy mial `109` przerobionych pytan PJM i `860` nieprzerobionych,
- po kolejnych lokalnych sesjach testowych uzytkownik `54` mial `145` przerobionych pytan PJM i `824` nieprzerobione,
- pytania PJM sa rozlozone na `31` dzialow,
- sesja `602` miala w payloadzie `question_count = 12` i `12` identyfikatorow pytan,
- nowsze sesje `606-613` tez mialy `question_count = 12`, nawet gdy byly juz ograniczone do dzialu,
- przykladowe dzialy PJM sa wieksze niz 12 pytan: `Znaki ostrzegawcze` ma `45`, `Oznakowanie poziome` ma `57`, `Skrzyzowania z pierwszenstwem` ma `70`, `Pozycja pojazdu, zatrzymanie i postoj` ma `91`.

Wniosek: pierwotny start PJM byl globalna paczka pytan, a aktualny start po dzialach nadal jest paczka z dzialu, nie pelnym dzialem.

## Diagnoza Limitu 12 I 40

Pelny dzial PJM jest technicznie mozliwy, bo `StudySessionManager` potrafi filtrowac po:

- kategorii prawa jazdy,
- `question_topic_id`,
- statusie pytania, np. `unanswered`,
- aktywnym filmie pytania PJM.

Aktualne blokady:

- `resources/js/Pages/Session/PjmIndex.vue` liczy `question_count` jako maksimum `12`,
- `resources/js/Pages/StudySessions/Show.vue` dla `Kontynuuj PJM` tez liczy maksimum `12`,
- `resources/js/Pages/StudySessions/Show.vue` dodatkowo ucina wysylany `question_count` do `40`,
- `PjmSessionPageController` waliduje `question_count` przez `max:40`,
- `StudySessionManager::normalizeQuestionCount()` dla PJM zwraca maksimum `40`,
- `StudySessionManager::start()` dla PJM zawsze robi `limit($questionCount)`.

To oznacza, ze sama zmiana UI z `12` na liczbe pytan dzialu nie wystarczy. Dzialy wieksze niz `40` nadal zostalyby uciete przez backend.

## Diagnoza Buga `Kontynuuj PJM` Bez Hard Refreshu

Po zakonczeniu sesji przez AJAX frontend aktualizuje lokalny status sesji i zwykly progress sesji, ale nie dostaje swiezego `pjmCompletion.progress`.

Skutek:

- ekran wyniku moze dalej widziec stary `current_topic_id`,
- `Kontynuuj PJM` moze wyslac poprzedni dzial z `question_status = unanswered`,
- jesli poprzedni dzial zostal wlasnie domkniety, backend nie znajduje pytan albo tworzy nieoczekiwana kolejna paczke,
- hard refresh naprawia problem, bo wtedy `StudySessionController` liczy `pjmCompletion` od nowa.

Wniosek: kontynuacja PJM musi bazowac na swiezym stanie po ostatniej odpowiedzi, nie tylko na propsach z poczatkowego renderu strony.

## Decyzje Produktowe

### Progres PJM Liczymy Jako Pierwsze Przejscie

Glowny wskaznik etapu PJM:

`przerobione = all - unanswered`

Powod: uzytkownik ma wiedziec, ktore dzialy juz zobaczyl i gdzie kontynuowac. Bledna odpowiedz nie powinna blokowac przejscia do nastepnego dzialu.

### Bledne Pytania Sa Osobna Kolejka

Na kartach dzialow pokazujemy tez `do poprawki`, ale nie traktujemy tego jako glownego hamulca pierwszego przejscia.

### Aktualny Dzial

Aktualny dzial PJM to pierwszy dzial w kolejnosci nauki, ktory ma `unanswered > 0`.

Jesli wszystkie dzialy maja `unanswered = 0`, modul moze proponowac:

- powtorke blednych PJM,
- ponowne przejscie calej puli PJM,
- przejscie do pelnego dostepu.

### PJM Nie Uzywa Platnego Store Klasycznej Nauki

Darmowy uzytkownik PJM nie powinien uruchamiac sesji przez `study-sessions.store`, bo ten endpoint jest za brama pelnego produktu. Kontynuacja PJM musi isc przez `session.pjm.store`.

### Pelny Dzial Nie Moze Polegac Na Surowym `question_count`

Frontend nie powinien wysylac arbitralnie duzej liczby pytan, np. `999`. Bezpieczny kontrakt powinien mowic o intencji:

`question_count_strategy = topic_remaining`

Backend musi sam policzyc, ile pytan pasuje do wybranego dzialu, statusu i filtrow PJM, a dopiero potem utworzyc sesje. Dzieki temu uzytkownik moze przerobic caly dzial, ale nie da sie przypadkiem uruchomic ogromnej globalnej sesji calej kategorii.

### Sesja Pelnego Dzialu W Pierwszym Przejsciu

Dla glownej sciezki `Kontynuuj PJM` sesja powinna obejmowac:

- jeden dzial,
- tylko pytania z aktywnym filmem PJM,
- domyslnie `question_status = unanswered`,
- wszystkie pasujace pytania z dzialu, bez limitu `12` i bez limitu `40`.

Powtorki blednych i ponowne przejscie calej puli moga zostac osobnymi trybami z osobnym limitem produktowym.

## Kontrakt Danych

Endpoint `GET /nauka/pjm` powinien dostawac dodatkowy payload `progress`.

Proponowany ksztalt:

```php
[
    'total_questions' => 969,
    'answered_questions' => 109,
    'unanswered_questions' => 860,
    'incorrect_questions' => 25,
    'correct_questions' => 84,
    'memorized_questions' => 0,
    'progress_percent' => 11.25,
    'current_topic_id' => 1,
    'topic_groups' => [
        [
            'label' => 'Pytania podstawowe',
            'options' => [
                [
                    'id' => 1,
                    'key' => 'warning_signs',
                    'label' => 'Znaki ostrzegawcze',
                    'questions_count' => 45,
                    'counts' => [
                        'all' => 45,
                        'unanswered' => 43,
                        'incorrect' => 0,
                        'correct' => 2,
                        'memorized' => 0,
                    ],
                    'answered_count' => 2,
                    'progress_percent' => 4.44,
                ],
            ],
        ],
    ],
]
```

Endpoint `POST /nauka/pjm` powinien przyjac:

- `question_topic_id` opcjonalnie,
- `question_status`,
- `question_count`,
- `question_count_strategy` opcjonalnie: `fixed` albo `topic_remaining`,
- `randomize_order`.

Jesli `question_topic_id` nie przyjdzie, backend wybiera aktualny dzial PJM automatycznie.

Kontrakt docelowy:

```php
[
    'question_topic_id' => 4,
    'question_status' => 'unanswered',
    'question_count_strategy' => 'topic_remaining',
    'question_count' => null,
    'randomize_order' => false,
]
```

Zasady:

- `fixed` zachowuje obecne limity dla malych paczek i kompatybilnosci wstecznej,
- `topic_remaining` wymaga dzialu po automatycznym lub jawnym rozstrzygnieciu,
- `topic_remaining` moze dzialac tylko dla `MODE_PJM`,
- `topic_remaining` nie moze losowo wziac calej kategorii bez dzialu,
- backend zapisuje w payloadzie faktyczna strategie i faktyczna liczbe pytan.

Odpowiedz AJAX ostatniej odpowiedzi w sesji PJM powinna zwracac swieze `pjm_completion` albo frontend musi po zakonczeniu sesji wykonac czesciowy reload `pjmCompletion`. Preferowane jest zwrocenie swiezego payloadu z backendu, bo wtedy przycisk `Kontynuuj PJM` moze byc aktywny od razu i bez migania.

## Sprint 1 - Bezpieczny Fundament I Ekran Startu

Status: `zrealizowany lokalnie, do domkniecia testami regresji po kolejnych sprintach`

Cel: uzytkownik widzi progres PJM po dzialach i startuje sesje z aktualnego lub wybranego dzialu.

Zakres:

- dodac serwis liczacy PJM-only progres po dzialach,
- serwis musi uzywac tego samego mechanizmu bucketow progresu co klasyczna nauka,
- `GET /nauka/pjm` zwraca `progress`,
- `POST /nauka/pjm` przyjmuje `question_topic_id`,
- brak `question_topic_id` oznacza automatyczny wybor aktualnego dzialu,
- UI `/nauka/pjm` pokazuje globalny pasek progresu i karty dzialow,
- przycisk kontynuacji startuje aktualny dzial,
- przycisk na karcie startuje wybrany dzial.

Poza zakresem sprintu 1:

- rozbudowana mapa rozdzialow na ekranie wyniku,
- przebudowa calego copy ekranu wyniku,
- ujednolicanie wszystkich duplikatow filtrow PJM w jednym refaktorze,
- zmiana limitu sesji z `12` na pelny dzial PJM.

Testy akceptacyjne:

- progres PJM liczy tylko pytania z aktywnym filmem pytania PJM,
- progres PJM ignoruje pytania bez PJM i pytania bez dzialu,
- progres rozroznia `unanswered`, `incorrect`, `correct`, `memorized`,
- start PJM bez dzialu wybiera pierwszy dzial z `unanswered > 0`,
- start PJM z dzialem wybiera tylko pytania PJM z tego dzialu,
- darmowy PJM nadal nie otwiera klasycznych sesji bez pelnego dostepu.

## Sprint 2 - Ekran Wyniku I Kontynuacja

Status: `zrealizowany lokalnie czesciowo; wykryty bug stalego pjmCompletion po AJAX`

Cel: po sesji PJM uzytkownik widzi, co zrobil, ile zostalo i gdzie kontynuowac.

Zakres:

- dodac PJM completion progress do `StudySessions/Show`,
- zmienic komunikat `Ukonczyles pule` na `Ukonczyles sesje PJM`,
- pokazac `przerobione / wszystkie PJM`,
- pokazac aktualny lub nastepny dzial,
- CTA `Kontynuuj PJM` startuje kolejna sciezke z aktualnego dzialu,
- CTA `Powtorz bledne PJM` pozostaje osobna sciezka.

Testy akceptacyjne:

- ekran wyniku nie sugeruje zakonczenia calej puli, gdy sa nieprzerobione pytania,
- CTA kontynuacji uzywa `session.pjm.store`,
- payload wyniku pokazuje user-specific counts, nie tylko coverage kategorii.

Otwarte po diagnozie:

- `pjmCompletion.progress` musi byc odswiezany po zakonczeniu sesji przez AJAX,
- `Kontynuuj PJM` nie moze uzywac starego `current_topic_id`,
- liczba pytan dla kontynuacji nie moze byc ograniczona do `12`.

## Sprint 3 - Pelny Dzial PJM

Status: `zrealizowany lokalnie`

Cel: `Kontynuuj PJM` i start dzialu uruchamiaja caly aktualny dzial PJM, a nie paczke 12 pytan.

Zakres:

- dodac strategie liczby pytan `topic_remaining` albo rownowazny backendowy tryb pelnego dzialu,
- zmienic `POST /nauka/pjm`, zeby dla glownej sciezki wysylal intencje pelnego dzialu zamiast surowego `question_count = 12`,
- rozstrzygac brak `question_topic_id` do aktualnego dzialu przed budowa query,
- w `StudySessionManager` pominac limit `questionCount` tylko dla PJM + dzial + strategia pelnego dzialu,
- zachowac limit `fixed` dla innych trybow i dla powtorek globalnych,
- zapisac w payloadzie sesji `question_count_strategy` i faktyczna liczbe pytan,
- poprawic copy UI, zeby uzytkownik widzial `Dzial: X pytan`, nie `Paczka 12 pytan`.

Testy akceptacyjne:

- dzial PJM z `57` pytaniami tworzy sesje z `57` pytaniami, jesli wszystkie sa `unanswered`,
- dzial PJM z `31` nieprzerobionymi pytaniami tworzy sesje z `31` pytaniami,
- backend nie pozwala uruchomic `topic_remaining` bez rozstrzygnietego dzialu,
- backend nadal filtruje tylko pytania z aktywnym filmem PJM,
- klasyczna nauka, egzamin, quick i hard nie zmieniaja swoich limitow,
- powtorka globalna PJM nie uruchamia przypadkiem calej kategorii bez jasnej decyzji produktowej.

Implementacja:

- dodano strategie `topic_remaining` w `StudySessionManager`,
- `topic_remaining` dziala tylko dla `MODE_PJM` i wymaga rozstrzygnietego dzialu,
- dla `topic_remaining` backend pobiera wszystkie pasujace pytania z dzialu bez limitu `12` i `40`,
- payload sesji zapisuje faktyczna liczbe pytan i `question_count_strategy`,
- `/nauka/pjm` i `Kontynuuj PJM` wysylaja intencje pelnego dzialu zamiast duzej liczby pytan.
- `/nauka/pjm` ma jawny wybor trybu dzialu: `Nieprzerobione` albo `Caly dzial`, zeby uzytkownik mogl powtorzyc dzial od poczatku.

## Sprint 4 - Swiezy Progres Po Zakonczeniu Sesji

Status: `zrealizowany lokalnie`

Cel: `Kontynuuj PJM` dziala natychmiast po ukonczeniu sesji, bez hard refreshu strony.

Zakres:

- zrobic `pjmCompletion` stanem mutowalnym w `StudySessions/Show.vue`, inicjalizowanym z propsow,
- po ostatniej odpowiedzi w sesji PJM zaktualizowac ten stan swiezym payloadem,
- preferowana sciezka: `StudySessionAnswerController` zwraca `pjm_completion` dla zakonczonej sesji PJM,
- alternatywa: frontend robi czesciowy Inertia reload `only: ['pjmCompletion']` przed aktywacja CTA,
- `Kontynuuj PJM` uzywa odswiezonego `current_topic_id`, `unanswered` i listy dzialow.

Testy akceptacyjne:

- po zakonczeniu ostatniego pytania ekran wyniku pokazuje aktualna liczbe `unanswered`,
- jesli dzial zostal domkniety, `Kontynuuj PJM` wskazuje nastepny dzial,
- przycisk dziala bez hard refreshu,
- odpowiedz JSON dla ostatniej odpowiedzi PJM zawiera wystarczajace dane do odswiezenia CTA,
- wynik sesji nadal dziala dla klasycznych trybow bez dodatkowego payloadu PJM.

Implementacja:

- `StudySessionAnswerController` zwraca `pjmCompletion` w odpowiedzi JSON, gdy sesja PJM zostaje zakonczona,
- `StudySessions/Show.vue` trzyma `pjmCompletion` jako stan mutowalny, inicjalizowany z propsow,
- po ostatniej odpowiedzi frontend podmienia `pjmCompletion` na swiezy payload,
- `Kontynuuj PJM` korzysta z aktualnego `current_topic_id`, a nie ze starych propsow z pierwszego renderu.

## Sprint 5 - Uporzadkowanie Filtrow PJM

Cel: zmniejszyc ryzyko rozjazdu filtrow PJM.

Zakres:

- wyniesc query PJM eligibility do jednego miejsca,
- podlaczyc do niego progres, coverage, resolver darmowego dostepu i manager sesji,
- zostawic zgodnosc wynikow z obecnymi testami coverage.

Testy akceptacyjne:

- coverage kategorii nie zmienia sie nieintencjonalnie,
- start sesji i raport coverage widza ten sam zestaw pytan PJM,
- assety `review_required` nadal sa traktowane jako dostepne.

## Zabezpieczenia Regresji

- Nie zmieniamy bram dostepu `product.access` i `pjm.access` w sprincie 1.
- Nie podpinamy PJM pod klasyczny endpoint startu sesji.
- Nie zmieniamy sposobu zapisu `user_question_progress`.
- Nie zmieniamy semantyki bucketow `QuestionProgressManager`.
- Nie usuwamy obecnego coverage copy; dopiero sprint 2 poprawia copy wyniku.
- Testy sprintu 1 musza przejsc przed dalszym refaktorem.
- Pelny dzial PJM nie moze byc realizowany przez wyslanie z frontendu duzej liczby w `question_count`.
- Tryb pelnego dzialu musi byc ograniczony do jednego rozstrzygnietego `question_topic_id`.
- Przed kliknieciem `Kontynuuj PJM` po zakonczeniu sesji frontend musi miec swieze dane `pjmCompletion`.

## Ryzyka

### Rozjazd Filtrow PJM

Obecnie filtr aktywnych assetow PJM jest powielony w kilku miejscach. Sprint 1 ogranicza zmiane do nowego serwisu i store PJM, a pelne ujednolicenie zostawia na sprint 5.

### Mylenie Progresu Pierwszego Przejscia Z Opanowaniem

Klasyczna mapa wyniku uzywa logiki `correct + memorized`. Dla PJM sprint 1 uzywa `answered`, bo celem jest etap rozdzialowy, a nie mastery.

### Darmowy PJM Kontra Pelny Produkt

UI PJM musi wysylac start sesji do `session.pjm.store`, zeby darmowy uzytkownik nie trafial w brame pelnego produktu.

### Pelny Dzial Kontra Wydajnosc

Niektore dzialy maja ponad `70` pytan. To jest akceptowalne dla sesji dzialowej, ale nie wolno przez przypadek uruchomic calej kategorii `969` pytan jednym kliknieciem. Dlatego strategia pelnego dzialu wymaga dzialu i nie zastepuje globalnych powtorek.

### Stary Progres Po AJAX

Jesli wynik sesji opiera sie na propsach z pierwszego renderu, CTA moze kontynuowac stary dzial. Ten problem musi byc domkniety przed uznaniem sprintu 4 za zakonczony.

## Definition Of Done Sprintu 1

- Dokumentacja sprintowa istnieje w repo.
- `/nauka/pjm` pokazuje globalny progres i dzialy PJM.
- Start domyslny nie idzie globalnie przez cala pule, tylko przez aktualny dzial.
- Wybor dzialu startuje sesje ograniczona do tego dzialu.
- Testy backendowe PJM przechodza.
- Zmiana jest malozakresowa i nie dotyka platnej nauki klasycznej poza wspoldzielonym zapisem progresu.

## Definition Of Done Dla Pelnego Dzialu PJM

- `/nauka/pjm` pokazuje, ile pytan zostalo w aktualnym dziale.
- Start aktualnego dzialu tworzy sesje ze wszystkimi nieprzerobionymi pytaniami PJM tego dzialu.
- Ekran wyniku po zakonczeniu sesji pokazuje swiezy nastepny dzial bez odswiezania strony.
- `Kontynuuj PJM` po AJAX-owym zakonczeniu sesji dziala od razu.
- Payload sesji pokazuje strategie `topic_remaining` i faktyczna liczbe pytan.
- Nie ma regresji w testach PJM, coverage, session page i study session flow.
