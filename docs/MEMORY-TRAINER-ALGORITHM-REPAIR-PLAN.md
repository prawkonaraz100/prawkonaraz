# Plan naprawczy algorytmu trenera pamieci

## Status

Branch: `codex/memory-trainer-algorithm-fix`

Na tym etapie wykonano diagnoze kodu i danych oraz wdrozono pierwszy etap naprawy algorytmu.

Wykonane:

- `ReviewPlannerService` traktuje `review_memory_progress` jako zrodlo prawdy, gdy rekord istnieje.
- Pytanie z przyszlym `next_verified_review_at` nie wraca juz przez stare klasyczne `user_question_progress.next_review_at`.
- Klasyczne due bez `review_memory_progress` zostaje kandydatem do weryfikacji, ale jest wybierane do sesji w limicie dziennego bloku.
- Widziane boostery sa wybierane tylko do dopelnienia sesji, a nie jako cala techniczna pula na ekranie.
- `/nauka` korzysta z planu trenera zamiast liczyc surowe klasyczne zaleglosci.
- Aktywna kategoria w `/trener-pamieci` pokazuje liczbe z planu, nie nadmuchany klasyczny count.
- Dodano regresyjny test: verified future memory suppresses stale classic due progress.
- Naprawiono wolne wejscie w `/trener-pamieci`: `orderedProgress()` nie przelicza juz priorytetu przy kazdym porownaniu sortowania, tylko tworzy jednorazowy wiersz priorytetu dla kazdego pytania.

Pomiar dla `smoke@local.test`, kategoria C:

- przed optymalizacja: `ReviewPlannerService::plan()` okolo `4194 ms`, `orderedProgress()` okolo `3966 ms`,
- po optymalizacji: `ReviewPlannerService::plan()` okolo `431 ms`, `orderedProgress()` okolo `411 ms`,
- wynik planu pozostal taki sam: `candidate_count=50`, `recommended_question_count=50`.

Zweryfikowane:

- `tests/Unit/Support/ReviewPlannerServiceTest.php`
- `tests/Feature/ReviewQueueTest.php`
- `tests/Feature/ApiSessionTest.php`
- `tests/Feature/ReviewTrainerTelemetryTest.php`

## Problem

Trener pamieci ma dzialac jak osobny modul weryfikacji faktycznej wiedzy, ale obecnie jego kolejka nadal mocno opiera sie na klasycznym `user_question_progress`.

Efekt:

- pytania z nauki klasycznej i Zen wpadaja do `user_question_progress`,
- gdy `user_question_progress.next_review_at <= dzis`, pytanie staje sie kandydatem do trenera,
- odpowiedz w trenerze zapisuje stan w `review_memory_progress`,
- ale klasyczny `user_question_progress.next_review_at` nie jest wygaszany,
- to samo pytanie moze wiec nadal wisiec jako zalegle przez klasyczny zegar, mimo ze trener pamieci ma juz nowszy stan i przyszly termin powrotu.

To powoduje, ze licznik wyglada jak rosnacy dlug, zamiast jak priorytetyzowany plan.

## Potwierdzenie na danych lokalnych

Dla `smoke@local.test`, kategoria C:

- `candidate_count`: `1067`,
- `primary`: `769`,
- `seen_booster`: `298`,
- `recommended_question_count`: `50`.

Rozbicie calej puli:

- `757` pytan ma klasyczne `user_question_progress.next_review_at <= dzis`,
- `310` pytan to widziane boostery z przyszlym klasycznym terminem,
- `95` pytan ma juz `review_memory_progress`,
- `22` pytania maja przyszly termin w `review_memory_progress`, ale nadal wpadaja przez klasyczny dlug.

W pierwszych 50 pytaniach planu:

- `50/50` ma klasyczne `next_review_at <= dzis`,
- `47/50` ma juz `review_memory_progress`,
- `22/50` ma przyszly termin w `review_memory_progress`.

Przyklad:

- pytanie `external_id=3532`,
- `user_question_progress.next_review_at = 2026-04-24`,
- `review_memory_progress.verified_memory_state = verified_memory`,
- `review_memory_progress.next_verified_review_at = 2026-05-21`,
- mimo to pytanie nadal pojawia sie wysoko w planie, bo klasyczny zegar jest zalegly.

## Gdzie jest dziura w kodzie

### 1. Planer laczy klasyczny dlug z trenerem

Plik: `app/Support/ReviewPlannerService.php`

Kluczowy warunek w `orderedProgress()`:

- `whereDate('user_question_progress.next_review_at', '<=', $today)`,
- `orWhere(review_memory_progress needs recovery / due)`,
- `orWhere('user_question_progress.total_attempts', '>', 0)`.

To oznacza:

- klasyczne due wchodzi jako `primary`,
- kazde widziane pytanie moze wejsc jako `seen_booster`,
- nowszy stan `review_memory_progress` nie blokuje starszego klasycznego due.

### 2. Odpowiedz w trenerze celowo nie zmienia klasycznego progresu

Plik: `app/Support/StudySessionManager.php`

`QuestionProgressManager::recordAnswer()` jest pomijany dla `MODE_SR_REVIEW`.

Plik: `app/Support/ReviewMemoryProgressService.php`

`sr_review` aktualizuje tylko `review_memory_progress`.

To jest zgodne z decyzja produktowa, ze trener pamieci ma byc osobny. Problemem nie jest sam rozdzial tabel. Problemem jest to, ze planer nadal traktuje klasyczny zegar jako wazniejszy lub rownorzedny, nawet gdy istnieje nowszy zegar trenera.

### 3. Testy utrwalaja obecne zachowanie

Plik: `tests/Feature/ReviewTrainerTelemetryTest.php`

Istnieje test:

- `sr review answers update verified memory progress without changing classic progress`.

Ten test jest sensowny i powinien zostac, ale musimy dopisac nowe testy planera:

- `review_memory_progress` z przyszlym terminem powinien wyciszyc klasyczne due,
- `review_memory_progress needs_recovery` powinien przebic klasyczny stan,
- brak `review_memory_progress` pozwala klasycznemu due wejsc jako kandydat do weryfikacji.

### 4. UI i liczniki moga dalej pokazywac stary dlug

Plik: `resources/js/Pages/ReviewQueue/Index.vue`

`selectedDueCount` bierze `plan.candidate_count`, czyli cala pule kandydatow, nie dzisiejszy realny plan ani realnie pilny dlug.

Plik: `app/Support/StudyContextService.php`

`dueReviewCount()` liczy tylko `user_question_progress.next_review_at <= dzis`. Po naprawie planera ten licznik moglby nadal pokazywac zawyzona liczbe na `/nauka`.

Plik: `app/Http/Controllers/ReviewQueueController.php`

`categories` i `stats.ready_for_review_count` tez bazuja na klasycznym due + warunku `total_attempts > 0`, a potem sa nadpisywane `candidate_count` dla aktywnej kategorii.

## Zasada docelowa

Trener pamieci ma miec jedna prawde priorytetu:

1. Jezeli istnieje `review_memory_progress`, to on decyduje o stanie pytania w trenerze.
2. Klasyczny `user_question_progress` jest tylko zrodlem kandydatow do pierwszej weryfikacji.
3. Klasyczne due nie moze przebijac przyszlego terminu w `review_memory_progress`.
4. `needs_recovery`, `unknown`, `incorrect` i due w `review_memory_progress` maja najwyzszy priorytet.
5. `user_question_progress.total_attempts > 0` nie powinno oznaczac automatycznie wielkiego dlugu; to moze byc tylko limitowany `seen_booster`.
6. UI ma pokazywac dzisiejszy plan i realnie pilne pytania, nie cala napuchnieta pule kandydatow.

## Proponowany model priorytetow

### Segment A: verified recovery

Zrodlo: `review_memory_progress`

Warunek:

- `verified_memory_state = needs_recovery`,
- albo `last_verified_result in unknown/incorrect/skipped`,
- albo sygnal `recovery_score` wysoki.

Status: najwyzszy priorytet. Pytanie wraca szybko.

### Segment B: verified due

Zrodlo: `review_memory_progress`

Warunek:

- `next_verified_review_at <= dzis`,
- stan nie jest recovery.

Status: normalna powtorka trenera.

### Segment C: classic candidate

Zrodlo: `user_question_progress`

Warunek:

- brak `review_memory_progress`,
- pytanie bylo widziane w klasycznej nauce,
- pytanie jest klasycznie due albo ryzykowne.

Status: kandydat do weryfikacji, nie potwierdzony dlug trenera.

### Segment D: seen booster

Zrodlo: `user_question_progress`

Warunek:

- brak `review_memory_progress`,
- pytanie bylo widziane,
- nie jest klasycznie due,
- potrzebujemy dopelnic plan.

Status: limitowane wzmocnienie, nie pokazywane jako pilny dlug.

### Segment E: new candidate

Zrodlo: `questions`

Warunek:

- brak klasycznego i verified progresu,
- dzienny plan jest zbyt krotki,
- obowiazuje limit dzienny i limit udzialu w sesji.

Status: pierwsza ekspozycja, nie potwierdzona pamiec.

## Plan wdrozenia

### Sprint 1: testy regresyjne i nowy kontrakt planera - wykonany

Dodac testy do `tests/Unit/Support/ReviewPlannerServiceTest.php`:

1. `verified future suppresses classic due`
   - pytanie ma `user_question_progress.next_review_at <= dzis`,
   - ma tez `review_memory_progress.next_verified_review_at > dzis`,
   - oczekujemy: nie wchodzi do `primary`, nie jest w `ordered_question_ids`.

2. `verified recovery overrides future classic progress`
   - pytanie ma klasyczny przyszly termin,
   - ma `review_memory_progress.needs_recovery`,
   - oczekujemy: wchodzi jako `primary`.

3. `classic due without verified progress remains verification candidate`
   - pytanie ma tylko klasyczne due,
   - brak `review_memory_progress`,
   - oczekujemy: wchodzi, ale docelowo w metrykach jako `classic_candidate`, nie jako nieograniczony dlug.

4. `seen boosters are capped`
   - 300 przyszlych widzianych pytan,
   - brak due,
   - oczekujemy: booster nie tworzy `candidate_count=300` jako komunikatu dlugu; dopelnia tylko plan.

### Sprint 2: refaktor `ReviewPlannerService` - wykonany w wersji bezpiecznej

W `ReviewPlannerService` rozdzielic pobieranie kandydatow:

1. `verifiedActionableRows()`
   - recovery + due z `review_memory_progress`.

2. `classicVerificationCandidateRows()`
   - klasyczne due tylko wtedy, gdy nie istnieje `review_memory_progress`,
   - albo gdy verified stan jest jawnie actionable.

3. `seenBoosterRows()`
   - tylko brak `review_memory_progress`,
   - limit do niedoboru planu,
   - nie liczyc calej bazy widzianych pytan jako kandydatow.

4. `newCandidateQuestionIds()`
   - zostawic limitowane jak teraz.

5. `ordered_question_ids`
   - skladac z segmentow w kolejnosci:
     `verified_recovery`, `verified_due`, `classic_candidate`, `seen_booster`, `new_candidate`.

### Sprint 3: nowe metryki planu - wykonany czesciowo

Zmienic payload planu tak, zeby UI nie musial zgadywac:

- `actionable_count` - realnie pilne pytania trenera wybrane do aktualnego bloku sesji,
- `verification_candidate_count` - pytania z klasycznej nauki do sprawdzenia,
- `booster_count` - limitowane wzmocnienie,
- `new_candidate_count` - pierwsza ekspozycja,
- `backlog_count` - opcjonalnie techniczna liczba kandydatow poza dzisiejszym planem, nie eksponowana jako alarm.

Zachowac stare pola (`due_count`, `candidate_count`) tymczasowo dla kompatybilnosci, ale przemapowac ich znaczenie ostroznie:

- `due_count` oznacza wybrana porcje primary dla tej sesji: verified actionable + limitowani kandydaci klasyczni,
- `candidate_count` oznacza realny zestaw wybrany do planu/sesji, a nie cala techniczna pule w bazie.

Dodane pola:

- `actionable_count`,
- `verification_candidate_count`,
- `selected_verification_candidate_count`.

Nie dodano jeszcze osobnego `backlog_count`, bo celowo nie chcemy eksponowac uzytkownikowi calej technicznej puli jako dlugu.

### Sprint 4: UI i liczniki - wykonany czesciowo

Plik: `resources/js/Pages/ReviewQueue/Index.vue`

Zmiany:

- glowny licznik ma pokazywac `recommended_question_count` / plan dzienny,
- "Po sesji" nie powinno liczyc `candidate_count - recommended_question_count`, jesli `candidate_count` zawiera techniczna pule,
- `Pilne` ma pokazywac `actionable_count`,
- `Wzmocnienie` ma pokazywac realnie wybrane boostery do sesji, nie wszystkie boostery w bazie.

Plik: `app/Support/StudyContextService.php`

`dueReviewCount()` powinien korzystac z tej samej logiki co planer albo z lekkiego serwisu liczenia actionable, a nie tylko z `user_question_progress.next_review_at`.

Wykonane: `dueReviewCount()` korzysta teraz z `ReviewPlannerService` i zwraca `recommended_question_count`.

Plik: `app/Http/Controllers/ReviewQueueController.php`

`stats.ready_for_review_count` i `categories.due_count` powinny byc spojne z planem. Aktywna kategoria moze brac wartosci z planu, a lista kategorii powinna liczyc actionable/verification candidates zgodnie z nowa polityka.

Wykonane: aktywna kategoria jest nadpisywana wartoscia `candidate_count` z planu, bez `max()` do starego nadmuchanego counta.

Do ewentualnego dopracowania: lista pozostalych kategorii nadal korzysta z lekkiego zapytania agregujacego. Przy twardej kategorii uzytkownika to nie powinno mieszac glownego flow, ale dla kont z dostepem do wielu kategorii mozna w kolejnym kroku policzyc kazda kategorie przez ten sam kontrakt planera albo lekki odpowiednik SQL.

### Sprint 5: migracja/porzadkowanie danych

Nie zaczynac od migracji danych. Najpierw naprawic planer.

Po wdrozeniu nowego planera mozna opcjonalnie przygotowac komendy diagnostyczne:

- ile klasycznych due jest wyciszonych przez verified future,
- ile pytan ma konflikt klasyczny due + verified memory,
- ile realnie wraca jako recovery.

Na tym etapie nie kasujemy `user_question_progress`, bo jest potrzebny do nauki klasycznej, statystyk dzialow i historii.

## Ryzyka

### Ryzyko 1: zbyt mocne odciecie klasycznej nauki

Jesli calkowicie odetniemy `user_question_progress`, nowy uzytkownik po klasycznej nauce moze miec pusty trener.

Mitigacja:

- klasyczne pytania bez `review_memory_progress` zostaja jako kandydaci do pierwszej weryfikacji,
- ale sa limitowane i nie udaja potwierdzonego dlugu trenera.

### Ryzyko 2: UI zacznie pokazywac mniejsze liczby

To jest oczekiwane. Mniejsza liczba nie znaczy mniej pracy w bazie, tylko mniej psychologicznego dlugu.

Mitigacja:

- komunikaty: "Dzisiejszy plan", "Do odzyskania", "Do sprawdzenia",
- unikac komunikatu "1067 czeka".

### Ryzyko 3: testy historyczne beda czerwone

Obecne testy zakladaja, ze przyszle widziane boostery moga wypelnic duzy plan i ze klasyczne due zawsze liczy sie jako due.

Mitigacja:

- zaktualizowac testy zgodnie z nowym kontraktem,
- zostawic test separacji: `sr_review` nie zmienia klasycznego progresu.

## Definition of Done

Naprawa jest gotowa, gdy:

- pytanie z `review_memory_progress.next_verified_review_at > dzis` nie wraca przez stare klasyczne due,
- pytanie `needs_recovery` wraca natychmiast niezaleznie od klasycznego terminu,
- klasyczna nauka zasila trenera tylko jako limitowane kandydaty do weryfikacji,
- `/trener-pamieci` nie pokazuje napuchnietego `candidate_count` jako dlugu,
- `/nauka` nie pokazuje zawyzonej liczby powtorek z samego klasycznego `next_review_at`,
- sesja nadal ma minimum 50 pytan, gdy realny material to uzasadnia,
- dzienny target 80 pozostaje planem, ale nie wymusza narastania +80 kazdego dnia,
- testy `ReviewPlannerServiceTest`, `ReviewQueueTest`, `ReviewTrainerTelemetryTest` przechodza.

## Rekomendowana decyzja produktowa

Najlepszy model dla nas:

Trener pamieci ma byc osobnym modulem weryfikacji. Klasyczna nauka i Zen moga dostarczac kandydatow, ale nie moga tworzyc nieskonczonego dlugu trenera.

Dlatego priorytet trenera powinien byc oparty na `review_memory_progress`, a klasyczny `user_question_progress` powinien miec role pomocnicza:

- "to pytanie bylo widziane, warto je sprawdzic",
- nie: "to pytanie zawsze jest pilna powtorka".
