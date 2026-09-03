# Premium Review Trainer Roadmap (Trener pamieci)

## 1. Cel

Ten dokument definiuje, jak rozbudowujemy obecny modul `Trener pamieci` od prostego `sr_review` do pelnego premium trenera pamieciowego.

Ma odpowiedziec na cztery pytania:

- co dziala juz dzisiaj,
- czego nadal brakuje do "klikam i system sam mnie prowadzi",
- jak rozbudowac algorytm bez przepalenia architektury,
- jak zrobic to tak, aby system dalo sie skalowac, wersjonowac i mierzyc.

To jest dokument produktowo-techniczny.

Nie opisuje finalnego UI 1:1.
Opisuje silnik, modele danych, flow i kolejnosc wdrozenia.

### Nazewnictwo

W tym dokumencie rozdzielamy:

- `Trener pamieci` - publiczna nazwa produktu i ekranu user-facing,
- `sr_review` - obecny techniczny tryb sesji,
- `review trainer` - nazwa silnika i warstwy algorytmicznej.

To jest celowe.
UI i komunikacja z userem maja mowic `Trener pamieci`, ale backend moze nadal przez pewien czas zachowac techniczne nazwy kompatybilnosci.

### Kolejnosc wdrozenia wzgledem pozostalych dokumentow

Ten dokument nie powinien byc wdrazany jako pierwszy z calej trojki dokumentow premium.

Rekomendowana kolejnosc jest taka:

1. `QUESTION-EXPLANATION-VISUAL-SYSTEM.md`
2. `PREMIUM-REVIEW-TRAINER-ROADMAP.md`
3. `ADAPTIVE-LEARNING-ARCHITECTURE.md`

Powod:

- `Trener pamieci` moze rozwijac planner i silnik decyzji niezaleznie od warstwy wizualnej,
- ale warstwa `premium` nabiera pelnego sensu dopiero wtedy, gdy ma juz gotowe interwencje dydaktyczne do wykorzystania,
- wdrozenie `QUESTION-EXPLANATION-VISUAL-SYSTEM.md` jako pierwszego daje nam materialy, komponenty i toggles, ktore planner bedzie mogl potem uruchamiac swiadomie,
- dokument integracyjny ma sens dopiero wtedy, gdy oba fundamenty istnieja.

W praktyce oznacza to:

- fundament z `QUESTION-EXPLANATION-VISUAL-SYSTEM.md` nie jest juz tylko planem,
- mamy juz wdrozony `Sprint 1 / MVP v1` explainera:
  - blok referencyjny,
  - globalny toggle,
  - lokalny toggle w sesji,
- mamy tez wdrozony bazowy `Etap 2 / annotations MVP`:
  - `question_explanation_annotations`,
  - overlaye `label / circle` na obrazie pytania,
  - formularzowe zarzadzanie adnotacjami,
- kolejne kroki z tego dokumentu powinny zakladac, ze taka bazowa warstwa dydaktyczna juz istnieje,
- ale nadal nie powinny zakladac, ze mamy juz planner review v1 ani adaptive layer.

## 2. Werdykt

Na dzis `Trener pamieci`:

- maja sensowny fundament,
- nie sa jeszcze premium trenerem pamieciowym,
- juz wspieraja prosty spaced repetition,
- sa gotowe do rozbudowy bez wywracania calego modulu `Sesja`.

Najkrotsza uczciwa ocena:

- dzisiaj user moze klikac `Trener pamieci` i system bedzie go prowadzil po pytaniach due,
- ale logika jest jeszcze zbyt prosta, aby traktowac ja jako inteligentnego trenera premium.

## 3. Stan obecny

### Co juz mamy

Obecny system opiera sie na:

- trybie sesji `sr_review`,
- tabeli `user_question_progress`,
- polu `next_review_at`,
- prostym quality scoringu bazujacym na poprawnosci i czasie odpowiedzi,
- prostym algorytmie interwalow.

Obecny przeplyw:

1. User odpowiada na pytanie w sesji.
2. `StudySessionManager` zapisuje odpowiedz.
3. `QuestionProgressManager` aktualizuje progres pytania.
4. System ustawia:
   - `repetitions`
   - `correct_streak`
   - `last_quality`
   - `interval_days`
   - `easiness_factor`
   - `next_review_at`
5. `Trener pamieci` pobiera tylko pytania `due`, czyli z `next_review_at <= today()`.
6. Tryb `sr_review` buduje kolejke tych pytan i uruchamia osobna sesje.

### Co to znaczy produktowo

`Statystyki` odpowiadaja na pytanie:

- "jaki jest moj stan w tej kategorii?"

`Trener pamieci` odpowiada na pytanie:

- "co mam teraz zrobic?"

To nie sa dwa takie same ekrany.

`Trener pamieci` jest warstwa wykonawcza.

## 4. Co dokladnie dziala dzisiaj

### 4.1 Dobor pytan due

Pytania do powtorki sa wybierane przez `scopeDueForReview` na modelu `Question`:

- tylko pytania z wpisem w `user_question_progress`,
- tylko dla danego usera,
- tylko te, ktorych `next_review_at <= today()`.

To znaczy, ze kolejka nie jest losowa.
Jest oparta o faktyczny stan pamieci usera zapisany w bazie.

### 4.2 Osobny tryb sesji

`Trener pamieci` uruchamia osobny `mode = sr_review`, a nie zwykla sesje `learn`.

To daje nam dobra baze pod osobne zasady:

- doboru pytan,
- priorytetyzacji kolejki,
- limitowania batcha,
- przyszlych eksperymentow algorytmicznych.

### 4.3 Aktualizacja progresu po odpowiedzi

Kazda odpowiedz, rowniez w `sr_review`, wraca do `QuestionProgressManager`.

To jest bardzo wazne.
Oznacza, ze `Trener pamieci` nie jest martwym ekranem.
To pelnoprawny silnik uczenia z petla zwrotna.

### 4.4 Prosty quality scoring

Dzis quality score dziala tak:

- bledna odpowiedz => `1`
- poprawna bez czasu => `4`
- poprawna bardzo szybka => `5`
- poprawna srednia => `4`
- poprawna wolna => `3`

To jest proste, ale logiczne MVP.

Wazne doprecyzowanie:

- `Trener pamieci` nie jest i nie ma byc trybem na czas,
- informacja o czasie odpowiedzi moze istniec technicznie jako lekki sygnal legacy,
- UI nie powinno budowac presji czasowej ani karac usera komunikacyjnie za wolniejsza odpowiedz.

### 4.5 Prosty algorytm interwalow

Dzis interwaly sa ustawiane tak:

- przy slabej odpowiedzi: reset i szybki powrot,
- przy dobrej odpowiedzi:
  - pierwsza powtorka: `1 dzien`
  - druga: `3 dni`
  - kolejne: rosnace przez `easiness_factor`

To jest prosty, sensowny model startowy.

## 5. Czego brakuje do premium

Dzisiejszy system nie jest jeszcze premium, bo nie ma:

- inteligentnego planera dnia,
- priorytetyzacji pytan ponad prosty due queue,
- wykrywania pytan "leech", ktore user myli ciagle,
- osobnego traktowania relearn vs review,
- swiadomej obslugi pytan stale mylonych,
- predykcji, ile pytan user powinien dzis zrobic,
- mechanizmu rekomendowanego batcha "kliknij i jedziemy",
- polityki wersjonowania algorytmu,
- event logu do analizy skutecznosci algorytmu,
- eksperymentow A/B,
- telemetry premium mierzonej nie tylko po poprawnosci, ale tez po retencji.

Najwieksza luka:

dzis system umie planowac terminy.
Nie umie jeszcze byc inteligentnym trenerem.

## 6. Docelowa wizja premium

Docelowy system ma dzialac tak:

1. User otwiera `/nauka`.
2. Widzi prosty komunikat:
   - ile ma pytan due,
   - jaka jest rekomendowana sesja na teraz,
   - jaki jest szacowany czas.
3. Klika jedno CTA:
   - `Rozpocznij powtorki`
4. System sam:
   - dobiera batch,
   - miesza due, relearn i booster questions wedlug polityki,
   - prowadzi usera przez sesje,
   - po kazdej odpowiedzi aktualizuje model pamieci,
   - na koniec buduje nastepny plan.

Docelowe odczucie produktu:

- user nie zarzadza algorytmem,
- user ufa systemowi,
- system wie, co pokazac teraz,
- system nie wymaga recznego myslenia o kolejce.

## 7. Zasady architektoniczne

### 7.1 Nie budujemy tego jako serii hackow

Nie chcemy:

- doklejac kolejnych ifow do `QuestionProgressManager`,
- trzymac algorytmu tylko w widoku `ReviewQueue`,
- kodowac planera dnia we frontendzie.

### 7.2 Zachowujemy obecny fundament

To, co mamy dzisiaj, jest dobra baza:

- `StudySessionManager`
- `QuestionProgressManager`
- `user_question_progress`
- `sr_review`

Rozbudowa ma byc ewolucyjna, nie rewolucyjna.

### 7.3 Algorytm ma byc wersjonowalny

W premium systemie nie mozemy miec "magii bez historii".

Musimy wiedziec:

- jaka polityka planowania byla aktywna,
- jak liczyl sie score,
- po jakiej wersji algorytmu wzrosla retencja,
- czy dana zmiana poprawila wyniki.

### 7.4 Event log jest obowiazkowy

Samo aktualne `user_question_progress` nie wystarczy.

Potrzebujemy tez historii zdarzen:

- odpowiedz,
- quality,
- opoznienie,
- plan dnia,
- wybrany batch,
- polityka algorytmu.

Bez tego nie zbudujemy wersji premium ani analityki B2C/B2B.

## 8. Docelowe warstwy systemu

### 8.1 Warstwa progresu pytania

To jest stan zmaterializowany dla pytania i usera.

Na dzis juz istnieje:

- `repetitions`
- `correct_streak`
- `correct_count`
- `incorrect_count`
- `last_quality`
- `interval_days`
- `easiness_factor`
- `next_review_at`

Docelowo rozszerzamy to o:

- `lapses_count`
- `leech_score`
- `memory_state`
- `stability_score`
- `difficulty_score`
- `last_review_mode`
- `overdue_days_cache` albo jego pochodna
- `last_algorithm_version`

### 8.2 Warstwa zdarzen review

Potrzebna jest osobna tabela zdarzen, np. `review_events`.

Kazdy event powinien zapisywac:

- user,
- question,
- session,
- mode,
- timestamp,
- selected_answer,
- is_correct,
- response_time_ms,
- quality_score,
- old_progress_snapshot,
- new_progress_snapshot,
- algorithm_version,
- source_context.

To da:

- debugowalnosc,
- analityke,
- mozliwosc trenowania lepszego algorytmu,
- bezpieczny rollback.

### 8.3 Warstwa planowania dnia

Potrzebna jest warstwa `ReviewPlannerService`, ktora odpowie:

- ile pytan pokazac userowi teraz,
- ktore due sa najwazniejsze,
- czy dorzucic relearn,
- czy dorzucic booster,
- ile minut realnie zajmie batch.

To nie powinno byc liczone w kontrolerze ani na froncie.

### 8.4 Warstwa polityki algorytmu

Potrzebna jest jawna polityka, np. `ReviewPolicyService`.

Jej zadanie:

- przeliczyc score pytania,
- nadac priorytet,
- obslugiwac leeche,
- sterowac planem dnia,
- byc wersjonowalna.

## 9. Docelowy model stanowy pytania

Premium trener nie powinien patrzec na pytania tylko jako:

- poprawne,
- bledne,
- due.

Docelowy model:

- `new`
- `learning`
- `review`
- `relearning`
- `mastered`
- `leech`
- `suspended` w przyszlosci, jesli produktowo bedzie potrzebne

Znaczenie:

- `new` - user jeszcze realnie nie ruszyl pytania
- `learning` - user dopiero buduje slady pamieci
- `review` - pytanie jest w aktywnym cyklu utrwalania
- `relearning` - pytanie zostalo zapomniane i wraca do intensywniejszego obiegu
- `mastered` - pytanie jest utrwalone i ma odlegly termin
- `leech` - pytanie regularnie powoduje bledy i wymaga specjalnego traktowania

To jest bardzo wazny krok do premium.

## 10. Docelowy produktowy flow usera

### Dzis

User:

1. wchodzi do `Trenera pamieci`,
2. wybiera kategorie,
3. czasem zmienia liczbe pytan,
4. startuje review.

### Docelowo

User:

1. wchodzi na `Nauka`,
2. widzi:
   - `Masz dzis 18 pytan do treningu pamieci`
   - `Rekomendowany batch: 12 pytan / 7 min`
3. klika:
   - `Rozpocznij trening pamieci`
4. system prowadzi go przez sesje bez recznej konfiguracji.

Zaawansowane opcje zostaja, ale sa drugiego poziomu.

## 11. Docelowy UX premium

### 11.1 Wejscie

`Trener pamieci` nie powinien byc glowna top zakladka w headerze.

Powinny byc:

- widoczne na `/nauka`,
- widoczne na `Statystykach` kategorii,
- opcjonalnie sygnalizowane badge'em `X due` przy `Nauka`.

### 11.2 Sesja

Sesja powtorki powinna miec:

- zero lub prawie zero konfiguracji,
- jasny plan,
- szybkie przejscia,
- ciagly rytm,
- minimalna liczbe rozproszen.

### 11.3 Koniec sesji

Na koncu system powinien powiedziec:

- ile pytan zostalo dowiezionych,
- ile nadal wymaga szybkiej powtorki,
- kiedy wrocic,
- jaka jest nowa gotowosc.

## 12. Fazy rozbudowy

### Faza 0 - Hardening obecnej bazy

Cel:

- uznac obecny `sr_review` za baseline v1,
- niczego jeszcze nie komplikowac,
- przygotowac teren pod rozbudowe.

Zakres:

- dokumentacja,
- doprecyzowanie UX `Trenera pamieci`,
- usuniecie rzeczy user-facing, ktore nie powinny byc publiczne,
- zdefiniowanie event logu i polityki wersji algorytmu.

Definition of Done:

- wiemy, co jest baseline,
- mamy plan ewolucji,
- UI `Trenera pamieci` nie komunikuje juz roboczego charakteru.

### Faza 1 - One-click review planner

Cel:

- przejsc z ekranu "sam wybierz ile pytan" do flow "system rekomenduje i prowadzi".

Zakres:

- `ReviewPlannerService`
- rekomendowany batch size
- CTA `Rozpocznij powtorki`
- wstepny priorytet:
  - overdue first
  - more incorrect first
  - harder first

Nie robimy jeszcze:

- leech engine,
- polityk eksperymentalnych,
- ML.

Definition of Done:

- user moze kliknac jedno CTA i sensownie przejsc review bez recznego myslenia.

#### Sprint 1 - Planner v1 dla Trenera pamieci

Cel sprintu:

- dowiezc pierwsza inteligentna warstwe ponad obecny due queue,
- bez ruszania jeszcze memory states v2 i bez leech engine.

Zakres sprintu:

1. Publiczny produkt ma byc wszedzie komunikowany jako `Trener pamieci`.
2. Wejscie do modulu ma sie pojawic:
   - na `/nauka`,
   - w `Statystykach` kategorii,
   - opcjonalnie jako badge `X due` przy `Nauka`.
3. Powstaje `ReviewPlannerService`, ktory na bazie obecnych danych wylicza:
   - `recommended_question_count`,
   - `estimated_duration_seconds`,
   - `ordered_question_ids`,
   - `planner_version`.
4. Planner v1 dziala tylko na pytaniach `due`, ale nie sortuje ich juz jedynie po dacie.

Planner v1 score powinien uwzgledniac:

- `overdue_days` jako najmocniejszy sygnal,
- `incorrect_count` jako sygnal ryzyka,
- niski `correct_streak` jako sygnal niestabilnej pamieci,
- wyzsza `difficulty` jako delikatny mnoznik,
- starsze `last_answered_at` jako tie-breaker.

Rekomendowana polityka batcha v1:

- jesli due count <= 10 => pokaz wszystko,
- jesli due count 11-30 => rekomenduj batch 10-15,
- jesli due count > 30 => rekomenduj batch 15-20,
- estymacja czasu ma bazowac na historycznym srednim czasie usera, a gdy go brak na bezpiecznym fallbacku.

Uwaga:

- te liczby byly baseline v1 dla pierwszego bezpiecznego planera,
- Sprint 4B zastepuje docelowy model produktu budzetem 80 pytan dziennie i pierwszym blokiem minimum 50 pytan,
- stare wartosci 10-20 nie powinny juz sterowac docelowym UX trenera pamieci.

Poza zakresem sprintu:

- leech detection,
- relearning policy,
- booster questions,
- A/B testy polityk,
- model ML,
- nowy memory state engine.

Definition of Done Sprintu 1:

- user widzi `Trener pamieci` zamiast starej etykiety,
- moze kliknac jedno glowne CTA bez ustalania liczby pytan recznie,
- system zwraca rekomendowany batch i sensowna kolejnosc,
- kolejnosc jest deterministyczna i testowalna,
- aktualna logika `QuestionProgressManager` pozostaje nietknieta jako baseline,
- nic nie psuje zwyklej nauki, egzaminu ani statystyk.

#### Sprint 1 - decyzja implementacyjna po audycie kodu

Status audytu:

- obecny `QuestionProgressManager` jest wspoldzielony przez zwykla nauke, PJM, hard/quick i `sr_review`,
- dlatego Sprint 1 nie zmienia scoringu, interwalow ani tabeli `user_question_progress`,
- rozwijamy tylko warstwe planowania ponad istniejaca kolejka due,
- `sr_review` pozostaje technicznym trybem sesji, a `Trener pamieci` pozostaje nazwa produktu.

Bezpieczny zakres developmentu:

1. Dodac `ReviewPlannerService` jako jedyne zrodlo rekomendacji dla `Trenera pamieci`.
2. Planner v1 zwraca:
   - `planner_version`,
   - `due_count`,
   - `recommended_question_count`,
   - `estimated_duration_seconds`,
   - `ordered_question_ids`.
3. Planner dziala tylko na pytaniach due dla aktywnej kategorii/kategorii uzytkownika.
4. `StudySessionManager` uzywa planera tylko dla `MODE_SR_REVIEW`.
5. UI `/trener-pamieci` pokazuje jedno glowne CTA z rekomendowanym batchem.
6. UI `/trener-pamieci` nie pokazuje pionowej listy promptow pytan.
7. Podglad planu ma byc syntetyczny i kreatywny:
   - okragly wskaznik pamieci,
   - liczba powtorek,
   - szacowany czas,
   - segmenty planu typu `Zalegle`, `Ryzykowne`, `Do utrwalenia`.
8. Backend moze nadal zwracac preview pytan dla API i testow, ale user-facing ekran nie ma wygladac jak kolejka z bazy.
9. Ekran musi byc wizualnie spojny z publicznymi stronami `Cennik`, `Znaki drogowe` i `Oficjalna baza pytan`:
   - biale tlo,
   - niebieskie glowne CTA,
   - spokojna typografia,
   - cienkie separatory,
   - ograniczone zaokraglenia,
   - bez cukierkowej palety i bez wielkich dekoracyjnych kart.
10. Testy musza potwierdzic, ze zwykla nauka, hard mode i dotychczasowy zapis progresu pozostaja bez zmian.

Guardrails:

- nie dotykamy `QuestionProgressManager` w tym sprincie,
- nie dodajemy nowych pol do `user_question_progress`,
- nie wprowadzamy leech engine,
- nie dodajemy event logu jeszcze w Sprint 1,
- nie zmieniamy odtwarzacza sesji ani sposobu odpowiedzi na pytania.

Definition of Done implementacyjny:

- `/trener-pamieci` moze wystartowac rekomendowany batch jednym kliknieciem,
- API zwraca metadane planu, zeby frontend i przyszla aplikacja mobilna mialy ten sam kontrakt,
- sesja `sr_review` dostaje pytania w kolejnosci planera,
- due count i rekomendacja sa liczone deterministycznie,
- build i testy regresji przechodza.

Status po implementacji Sprintu 1:

- `ReviewPlannerService` istnieje jako izolowana warstwa rekomendacji,
- `StudySessionManager` korzysta z planera tylko dla `MODE_SR_REVIEW`,
- `/trener-pamieci` pokazuje jedno glowne CTA z rekomendowana liczba pytan,
- docelowy UI `/trener-pamieci` ma byc ekranem misji dnia z okraglym wskaznikiem pamieci, a nie lista pytan,
- API `api/v1/me/review-queue` zwraca `plan`, dzieki czemu web i przyszle klienty beda korzystac z tego samego kontraktu,
- `QuestionProgressManager`, interwaly, scoring i `user_question_progress` pozostaly nietkniete,
- regresje dla sesji, API, kolejki powtorek i guardu progresu przechodza.

#### Sprint 2 - bezpieczny event log decyzji planera

Cel sprintu:

- zaczac mierzyc, jakie decyzje podejmuje `Trener pamieci`, bez zmiany algorytmu nauki,
- przygotowac fundament pod pozniejsza ocene skutecznosci planera,
- zachowac pelna izolacje od klasycznej nauki, PJM, egzaminu, hard/quick i `QuestionProgressManager`.

Bezpieczny zakres:

1. Dodac append-only tabele `review_trainer_events`.
2. Dodac model `ReviewTrainerEvent`.
3. Dodac maly serwis `ReviewTrainerEventLogger`.
4. W Sprint 2 logowac tylko `review.session_started` dla `mode = sr_review`.
5. Po domknieciu sesji `sr_review` logowac idempotentny event `review.completed`.
6. Payload eventu startowego ma przechowywac snapshot decyzji planera:
   - `planner_version`,
   - `due_count`,
   - `recommended_question_count`,
   - `selected_question_count`,
   - `estimated_duration_seconds`.
7. Event startowy moze przechowywac `selected_question_ids`, bo batch v1 jest maly i ograniczony rekomendacja planera.
8. Payload eventu koncowego ma przechowywac:
   - `answered_count`,
   - `correct_answers_count`,
   - `total_questions_count`,
   - `score_percent`,
   - `duration_seconds`.
9. Dodac `ReviewTrainerAnalyticsService`, ktory czyta event log i zwraca lekki summary dla strony oraz API:
   - liczba startow,
   - liczba zakonczonych sesji,
   - completion rate,
   - sredni wynik,
   - sredni czas,
   - ostatnio zakonczona powtorka.

Poza zakresem Sprintu 2:

- nie logujemy jeszcze kazdej odpowiedzi,
- nie zmieniamy `QuestionProgressManager`,
- nie dodajemy nowego modelu SRS,
- nie zmieniamy UI sesji,
- nie robimy dashboardow analitycznych.

Definition of Done Sprintu 2:

- start sesji `sr_review` tworzy jeden event `review.session_started`,
- zakonczenie sesji `sr_review` tworzy jeden event `review.completed`,
- start zwyklej nauki nie tworzy eventu trenera pamieci,
- event ma `event_id`, `user_id`, `license_category_id`, `study_session_id`, `planner_version`, `payload` i `occurred_at`,
- `/trener-pamieci` oraz API zwracaja lekki `telemetry` summary,
- testy potwierdzaja, ze telemetry jest ograniczona tylko do `sr_review`,
- build i regresje nadal przechodza.

Status po implementacji Sprintu 2:

- `review_trainer_events` istnieje jako append-only log zdarzen trenera,
- `ReviewTrainerEventLogger` loguje start i zakonczenie sesji `sr_review`,
- logowanie zakonczenia jest idempotentne,
- `ReviewTrainerAnalyticsService` zwraca lekki summary dla strony i API,
- warstwa telemetry ma bezpieczny fallback, gdy tabela eventow nie jest jeszcze zmigrowana,
- `/trener-pamieci` pokazuje telemetry bez pionowej listy promptow pytan,
- `sr_review` korzysta z tego samego szybkiego lokalnego flow sesji co klasyczna nauka i PJM:
  - `questionPool`,
  - `prefetchedQuestions`,
  - JSON answer sync,
  - brak ciezkiego przeladowania Inertia po kazdej odpowiedzi.

#### Sprint 2.5 - UX i operacyjny hardening

Cel sprintu:

- domknac edge case'y wokol juz wdrozonego Sprintu 1 i 2,
- poprawic odczucie produktu bez ruszania algorytmu,
- upewnic sie, ze `Trener pamieci` nie wyglada jak kolejny techniczny ekran kolejki.

Zakres sprintu:

1. Dopracowac empty state, gdy user nie ma pytan due:
   - jasny komunikat, ze na teraz trening jest domkniety,
   - brak pozornego CTA startu,
   - spokojny powrot do `/nauka`.
2. Utrzymac okragly wskaznik pamieci jako glowny element ekranu.
3. Utrzymac segmenty planu:
   - `zalegle`,
   - `ryzykowne`,
   - `do utrwalenia`.
4. Nie pokazywac userowi promptow pytan na ekranie startowym.
5. Utrzymac spojnosc wizualna z publicznymi stronami:
   - biale tlo,
   - niebieskie glowne akcje,
   - cienkie separatory,
   - prosta typografia.
6. Nie zmieniac `QuestionProgressManager`, interwalow ani modelu SRS.

Definition of Done Sprintu 2.5:

- user z pytaniami due widzi jedno glowne CTA treningu,
- user bez pytan due widzi jasny stan domknietego treningu i przejscie do nauki,
- build i regresje dla sesji/trenera przechodza,
- roadmapa jasno pokazuje, ze kolejny duzy etap to dopiero memory states/leech policy.

### Faza 2 - Event log i telemetry

Cel:

- mierzyc, czy algorytm faktycznie pomaga.

Zakres:

- tabela `review_events`
- snapshot policy version
- telemetry:
  - retention after 1 day
  - retention after 3 days
  - relearn rate
  - overdue pressure
  - time to mastery

Definition of Done:

- kazda odpowiedz review jest audytowalna,
- mozemy mierzyc skutecznosc algorytmu.

### Faza 3 - Adaptive memory states

Cel:

- wyjsc poza prosty due queue.

Zakres:

- `memory_state`
- `lapses_count`
- `leech_score`
- relearning policy
- booster policy dla pytan ledwo utrzymanych

Definition of Done:

- algorytm rozroznia pytania stabilne, chwiejne i stale problematyczne.

#### Sprint 3 - read-only memory signals

Decyzja po audycie kodu:

- nie zaczynamy Fazy 3 od migracji nowych kolumn,
- nie zmieniamy jeszcze `QuestionProgressManager`,
- nie zmieniamy interwalow ani quality scoringu,
- nie zmieniamy tego, jak klasyczna nauka, PJM, egzamin, hard i quick zapisuja progres.

Powod:

- `QuestionProgressManager` jest wspolnym rdzeniem dla wielu modulow,
- bezpieczniej jest najpierw zbudowac warstwe diagnostyczna,
- dopiero potem mozna zdecydowac, ktore sygnaly materializowac w bazie.

Zakres Sprintu 3:

1. Dodac `ReviewMemorySignalService`.
2. Serwis wylicza sygnaly pamieci tylko z istniejacych pol:
   - `total_attempts`,
   - `correct_count`,
   - `incorrect_count`,
   - `correct_streak`,
   - `last_quality`,
   - `repetitions`,
   - `easiness_factor`,
   - `next_review_at`,
   - trudnosc pytania.
3. Serwis zwraca:
   - `memory_state`,
   - `plan_segment`,
   - `leech_score`,
   - `stability_score`,
   - `difficulty_score`,
   - `overdue_days`,
   - `version`.
4. `ReviewPlannerService` dodaje do planu:
   - `memory_signal_version`,
   - `segment_counts`,
   - `memory_state_counts`.
5. `/trener-pamieci` uzywa segmentow z backendu, a nie frontendowej heurystyki.
6. API zwraca `memory_signal` przy preview pytania.

Poza zakresem Sprintu 3:

- brak nowych kolumn w `user_question_progress`,
- brak zmiany interwalow,
- brak automatycznego zawieszania pytan leech,
- brak osobnych sesji relearning,
- brak coachingu po sesji.

Definition of Done Sprintu 3:

- planner umie rozroznic pytania `overdue`, `risky`, `reinforce`,
- pytania stale mylone dostaja `memory_state = leech`,
- sygnaly sa wersjonowane,
- testy potwierdzaja, ze to warstwa read-only,
- regresje trenera i sesji przechodza.

#### Sprint 3.5 - UX sygnalow pamieci

Cel sprintu:

- pokazac uzytkownikowi efekty Sprintu 3 bez technicznego zargonu,
- utrzymac `Trener pamieci` jako prosty ekran decyzji, a nie liste pytan,
- dac feedback po sesji, zeby user wiedzial, co zostalo odzyskane, co jest chwiejne i co wroci do powtorki.

Decyzja implementacyjna:

- nie pokazujemy publicznie nazw `leech`, `memory_state`, `relearning`,
- mapujemy je na proste komunikaty:
  - `Do odzyskania` - pytania stale mylone albo wracajace do ponownej nauki,
  - `W nauce` - pytania, ktore dopiero buduja slad pamieci,
  - `Stabilne` - pytania utrwalone albo w normalnym cyklu powtorek.

Zakres Sprintu 3.5:

1. `/trener-pamieci` pokazuje dodatkowa mape pamieci oparta o `memory_state_counts`.
2. Segmenty planu nadal pochodza z backendu.
3. Podsumowanie zakonczonej sesji `sr_review` pokazuje lekki panel:
   - ile pytan wymaga szybkiego powrotu,
   - ile jest w nauce,
   - ile jest stabilnych,
   - krotki komunikat trenerski.
4. Panel po sesji dziala tylko dla `mode = sr_review`.
5. Payload panelu jest liczony backendowo z istniejacych pol progresu.

Poza zakresem Sprintu 3.5:

- brak zmian interwalow,
- brak zmian `QuestionProgressManager`,
- brak nowych kolumn,
- brak automatycznego zawieszania pytan problematycznych,
- brak osobnych sesji relearning.

Definition of Done Sprintu 3.5:

- ekran startu nie pokazuje listy pytan,
- user widzi prosta mape pamieci zamiast technicznych stanow,
- wynik `sr_review` ma feedback trenerski,
- zwykla nauka, PJM, egzamin i Zen nie dostaja tego panelu,
- build i regresje przechodza.

### Faza 4 - Premium coaching layer

Cel:

- zrobic z review realnego trenera, a nie tylko scheduler.

Zakres:

- dynamiczny plan dnia,
- dzienny budzet 80 pytan,
- pierwszy mocny blok minimum 50 pytan,
- inteligentny komentarz po sesji,
- sygnaly "masz za duzo overdue",
- sygnaly "to pytanie wraca zbyt czesto",
- integracja z warstwa explainera i wizualnych objasnien.

Definition of Done:

- user czuje, ze system nie tylko pokazuje pytania, ale faktycznie prowadzi nauke.

#### Sprint 4A - next-step coaching bez zmiany algorytmu

Cel sprintu:

- dodac warstwe "co teraz?" na bazie danych, ktore juz liczymy,
- prowadzic usera prostym komunikatem przed i po sesji,
- nadal nie ruszac SRS, interwalow, `QuestionProgressManager` ani kolejnosci planera.

Zakres Sprintu 4A:

1. `ReviewPlannerService` dodaje do planu obiekt `coach`.
2. `coach` zawiera:
   - wersje komunikacji,
   - ton rekomendacji,
   - naglowek,
   - krotki komunikat,
   - etykiete glownej akcji,
   - syntetyczny opis zakresu.
3. `/trener-pamieci` korzysta z `coach` w hero zamiast frontendowych heurystyk.
4. `ReviewTrainerCompletionSummaryService` dodaje do wyniku:
   - `next_review_label`,
   - `next_step`.
5. `next_step` mowi, czy user ma wrocic od razu, jutro, za kilka dni, albo po prostu poczekac na kolejny plan.

Poza zakresem Sprintu 4A:

- brak dziennych planow w bazie,
- brak dziennego budzetu 80 pytan,
- brak wyboru pierwszego bloku minimum 50 pytan,
- brak eksperymentow A/B,
- brak automatycznego relearning mode,
- brak zmian w algorytmie priorytetyzacji.

Definition of Done Sprintu 4A:

- plan API zwraca `coach`,
- wynik `sr_review` zwraca `next_step`,
- UI pokazuje te informacje bez technicznych nazw,
- klasyczna nauka, PJM, egzamin i Zen nie zmieniaja zachowania,
- testy i build przechodza.

#### Sprint 4B - dzienny budzet pamieci i kontrola obciazenia

Decyzja produktowa:

- `Trener pamieci` nie powinien byc lista zaleglosci,
- `Trener pamieci` ma zarzadzac dziennym obciazeniem pamieci,
- celem dziennym jest 80 pytan w aktualnie wybranej kategorii,
- minimalny sensowny blok sesji to 50 pytan,
- system nie moze karac regularnosci przez dokladanie `+80` pytan na kazdy nastepny dzien.

Uzasadnienie:

- proba przypomnienia odpowiedzi aktywuje pamiec od pierwszego pytania,
- lepszy efekt daje powtarzanie rozlozone w czasie niz jedna narastajaca kolejka,
- 80 pytan dziennie jest celem aktywnosci, a nie obietnica, ze kazde pytanie wroci jutro,
- informacja o czasie odpowiedzi sluzy tylko do szacowania dlugosci sesji; nie projektujemy trybu na czas.

Podstawowe reguly Sprintu 4B:

1. Dzienny cel dla usera wynosi 80 pytan per aktualnie wybrana kategoria.
2. Pierwsza sesja powinna proponowac 50 pytan jako mocny blok startowy.
3. Po 50 pytaniach UI pokazuje checkpoint: minimum zrobione, zostalo 30 do celu dnia.
4. Po wykonaniu 80 pytan UI pokazuje dzien jako domkniety.
5. Pytania ponad 80 sa opcjonalne i nie powinny agresywnie zwiekszac obciazenia kolejnych dni.
6. Brakujace pytania z dnia nie przechodza 1:1 na jutro.
7. Nowe pytania moga dopelniac dzienny cel tylko wtedy, gdy prognoza kolejki na kolejne dni jest bezpieczna.
8. Pytania stale mylone maja priorytet, ale musza miec limit udzialu w sesji.

Priorytet doboru pytan:

1. Najpierw pytania due, overdue i ryzykowne.
2. Potem pytania bledne albo wracajace do odzyskania.
3. Potem pytania wzmacniajace, ktore warto utrwalic zanim wypadna z pamieci.
4. Dopiero na koncu nowe pytania, jesli dopelniaja cel dnia bez przeciagania kolejnych dni.

Mechanizm przeciwko efektowi `+80`:

- planner patrzy nie tylko na dzisiaj, ale tez na prognoze najblizszych dni,
- jesli jutro lub pojutrze kolejka jest juz ciezka, planner nie dodaje duzo nowych pytan,
- jesli kolejka jest lekka, planner moze dobrac nowe albo wzmacniajace pytania do dziennego celu,
- zaleglosci sa rozkladane przez kolejne dni, a nie pokazywane jako jedna sciana do przerobienia.

Edge case'y zaakceptowane dla Sprintu 4B:

1. User ma wiecej niz 80 pytan due:
   - UI pokazuje dzisiejszy plan 80 pytan,
   - nie pokazuje userowi pelnej liczby jako obowiazku,
   - planner wybiera najpilniejsze pytania.
2. User wraca po dluzszej przerwie:
   - system nie karze go setkami zaleglych pytan na raz,
   - dzisiejszy plan nadal ma limit 80,
   - reszta zaleglosci jest rozkladana na nastepne dni.
3. User ma mniej niz 50 pytan dostepnych:
   - sesja startuje z tym, co jest dostepne,
   - UI komunikuje "dostepne dzisiaj" zamiast blokowac start.
4. User ma 0 pytan due:
   - system moze dobrac pytania wzmacniajace albo nowe,
   - tylko jesli nie przeciazy to prognozy kolejnych dni.
5. User przerywa sesje:
   - do dziennego celu licza sie tylko realnie zakonczone odpowiedzi,
   - brakujaca czesc nie przechodzi automatycznie 1:1 na jutro.
6. User odpowiada slabo:
   - system ogranicza dokladanie nowych pytan,
   - wiekszy nacisk idzie na odzyskiwanie i utrwalanie.
7. User odpowiada bardzo dobrze:
   - system moze bezpiecznie dokladac pytania wzmacniajace albo nowe,
   - nadal pilnuje prognozy kolejnych dni.
8. Pytania stale problematyczne:
   - sa widoczne dla algorytmu jako wazne,
   - nie moga zdominowac calej sesji,
   - powinny miec osobny limit udzialu w batchu.

Zakres techniczny Sprintu 4B:

1. Dodac do planu backendowego informacje o dziennym celu:
   - `daily_target_question_count = 80`,
   - `minimum_session_question_count = 50`,
   - `completed_today_count`,
   - `remaining_today_count`,
   - `recommended_session_question_count`.
2. Dodac prognoze obciazenia kolejki na kolejne dni:
   - ile pytan jest zaplanowanych na jutro,
   - ile na 3 dni,
   - ile na 7 dni.
3. Uzyc prognozy jako guardraila przy dopelnianiu planu nowymi albo wzmacniajacymi pytaniami.
4. Nie zmieniac jeszcze `QuestionProgressManager` bez osobnego sprintu policy engine.
5. Nie dodawac presji czasu ani oceny czasu odpowiedzi.
6. UI `/trener-pamieci` powinno pokazac:
   - okragly wskaznik `dzisiaj: X / 80`,
   - glowny CTA dla pierwszego bloku 50 pytan,
   - po przekroczeniu 50 pytan komunikat o checkpointcie,
   - po przekroczeniu 80 pytan komunikat o domknietym dniu.

Poza zakresem Sprintu 4B:

- brak ML,
- brak A/B testow,
- brak recznego wyboru interwalow przez usera,
- brak presji czasowej,
- brak zmiany klasycznej nauki, PJM, egzaminu i Zen,
- brak wymuszania, zeby user zrobil 80 pytan jednorazowo.

Definition of Done Sprintu 4B:

- planner zwraca dzienny budzet i rekomendowany rozmiar sesji,
- UI prowadzi usera przez 50 -> 80 pytan bez straszenia pelna zalegloscia,
- przerwana sesja liczy wykonane pytania, ale nie tworzy kary na jutro,
- nowe pytania sa dobierane tylko z guardrailem prognozy,
- testy potwierdzaja, ze klasyczna nauka, PJM, egzamin i Zen nie zmieniaja zachowania,
- build i regresje przechodza.

#### Sprint 4C - verified memory vs training exposure

Decyzja produktowa:

- klasyczna nauka, Zen, egzamin i inne tryby cwiczenia sa kuznia materialu,
- `Trener pamieci` jest osobnym modulem weryfikacji pamieci,
- poprawna odpowiedz poza `Trenerem pamieci` nie powinna byc traktowana jako mocny dowod opanowania pytania,
- dopiero odpowiedzi w `Trenerze pamieci` powinny budowac status `verified memory`.

Problem, ktory rozwiazujemy:

- user moze w klasycznej nauce albo Zen zrobic 100-300 pytan i czesc z nich trafiac przypadkowo,
- zwykly tryb nauki nie ma przycisku `Nie wiem`,
- przypadkowo poprawna odpowiedz moze zawyzac stan pamieci, jesli algorytm potraktuje ja tak samo jak pewna odpowiedz w module review,
- wtedy `Trener pamieci` przestaje byc filtrem rzeczywistej wiedzy i zaczyna dziedziczyc szum z innych trybow.

Nowe rozroznienie:

- `training exposure` - user mial kontakt z pytaniem, cwiczyl je, mogl strzelic albo uczyc sie przez powtorzenia,
- `verified memory` - `Trener pamieci` potwierdzil, ze user potrafi odzyskac odpowiedz w dedykowanym module review.

Reguly produktowe:

1. Klasyczna nauka, Zen i egzamin moga zasilac liste kandydatow do sprawdzenia.
2. Bledna odpowiedz poza `Trenerem pamieci` jest sygnalem ryzyka.
3. Poprawna odpowiedz poza `Trenerem pamieci` jest sygnalem ekspozycji, a nie dowodem opanowania.
4. Jedna poprawna odpowiedz nigdzie nie powinna robic pytania trwale opanowanym.
5. Pytanie staje sie realnie potwierdzone dopiero po poprawnych odpowiedziach w `Trenerze pamieci` w czasie.
6. `Trener pamieci` moze obalic pewnosc zbudowana w innych trybach, jesli user nie potrafi odpowiedziec w module weryfikacji.

Przycisk `Nie wiem`:

- przycisk `Nie wiem` ma sens przede wszystkim w `Trenerze pamieci`,
- nie musi byc dodawany do klasycznej nauki, Zen ani egzaminu,
- `Nie wiem` oznacza uczciwy brak odzyskania odpowiedzi,
- komunikacyjnie powinno byc lagodniejsze niz "zla odpowiedz",
- algorytmicznie powinno kierowac pytanie do odzyskania albo ponownego utrwalenia.

Edge case: przypadkowy strzal poprawny

- nie probujemy wykrywac intencji usera,
- nie zakladamy, ze umiemy odroznic pewna odpowiedz od strzalu po samym wyniku,
- zamiast tego polityka mowi: poprawny strzal poza `Trenerem pamieci` nie nadaje statusu `verified memory`,
- pytanie musi przejsc przez `Trener pamieci`, zeby zostalo uznane za rzeczywiscie potwierdzone.

Konsekwencje architektoniczne:

1. Obecne `user_question_progress` jest wspoldzielone przez kilka trybow i dlatego nie powinno byc jedynym zrodlem prawdy dla `verified memory`.
2. Potrzebujemy mode-aware interpretacji postepu:
   - odpowiedzi z `sr_review` maja wysoka wage dla pamieci,
   - odpowiedzi z klasycznej nauki, Zen i egzaminu maja wage ekspozycji,
   - bledy z kazdego trybu moga zwiekszac ryzyko i priorytet sprawdzenia.
3. Docelowo warto rozwazyc osobna warstwe lub tabele dla zweryfikowanej pamieci, np. `review_memory_progress`.
4. Do czasu migracji planner moze budowac sygnaly read-only, ale musi jasno rozdzielac "widziane/cwiczone" od "potwierdzone w review".

Zakres techniczny Sprintu 4C:

1. Udokumentowac i nazwac statusy:
   - `training_exposure`,
   - `review_candidate`,
   - `verified_memory`,
   - `needs_recovery`.
2. Dodac do planera rozroznienie zrodel sygnalu bez zmiany klasycznej nauki.
3. Dodac `Nie wiem` tylko do sesji `mode = sr_review`.
4. Odpowiedz `Nie wiem` nie powinna byc zapisywana jako przypadkowa zla odpowiedz z wybrana opcja.
5. Wynik po sesji powinien rozroznic:
   - pytania potwierdzone,
   - pytania do odzyskania,
   - pytania tylko widziane w innych trybach.

Poza zakresem Sprintu 4C:

- brak dodawania `Nie wiem` do egzaminu,
- brak dodawania `Nie wiem` do klasycznej nauki i Zen,
- brak usuwania obecnego `user_question_progress`,
- brak natychmiastowego przepisywania calego SRS,
- brak zgadywania intencji usera na podstawie czasu odpowiedzi.

Definition of Done Sprintu 4C:

- dokumentacja jasno rozdziela ekspozycje od zweryfikowanej pamieci,
- planner nie traktuje poprawnej odpowiedzi z innych trybow jako pelnego dowodu opanowania,
- `Trener pamieci` moze sluzyc jako filtr rzeczywistej wiedzy,
- testy potwierdzaja, ze inne tryby nie dostaja przycisku `Nie wiem`,
- klasyczna nauka, Zen, egzamin i PJM nie zmieniaja swojego podstawowego flow.

##### Audyt techniczny po analizie kodu

Wniosek:

- wdrozenie `verified memory` jest mozliwe,
- ale nie powinno zaczynac sie od modyfikacji znaczenia `user_question_progress`,
- obecny progres jest wspolnym kontraktem dla wielu modulow i musi zostac kompatybilny.

Potwierdzone miejsca zapisu:

1. `StudySessionManager::recordAnswer()` zapisuje odpowiedz sesji, a potem bezwarunkowo wywoluje `QuestionProgressManager::recordAnswer()`.
2. Dotyczy to klasycznej nauki, PJM, `sr_review`, hard/quick oraz web/API sesji.
3. Egzamin ma dodatkowa sciezke timeoutu w `syncExamState()`, ktora tworzy `study_session_answers.selected_answer = null`, `is_correct = false`, a potem rowniez aktualizuje `QuestionProgressManager`.
4. `QuestionProgressManager` nie zna trybu sesji. Dostaje tylko:
   - usera,
   - pytanie,
   - `isCorrect`,
   - `responseTimeMs`,
   - `answeredAt`.

Potwierdzone miejsca odczytu `user_question_progress`:

- `ReviewPlannerService` - planuje `Trener pamieci` po `next_review_at <= today()`,
- `ReviewQueueController` - liczy kategorie i preview pytan do trenera,
- `ReviewMemorySignalService` - liczy read-only stany pamieci,
- `ReviewTrainerCompletionSummaryService` - buduje podsumowanie sesji review,
- `DashboardMetricsService` - pokazuje gotowe powtorki na dashboardzie,
- `CategoryAnalyticsService` - liczy readiness, weak spots, due count i aktywnosc kategorii,
- `StudyTopicGroupsService` - liczy statusy dzialow klasycznej nauki,
- `PjmQuestionProgressService` - liczy dzialy i progres PJM,
- `HardQuestionService` - wybiera trudne pytania,
- `UserReadinessService` - liczy gotowosc uzytkownika,
- `QuestionDailyStatsAggregator` i `PublicQuestionDifficultyService` - uzywaja progresu do statystyk/metryk publicznych.

Konsekwencja:

- `user_question_progress` jest dzisiaj warstwa "general study progress",
- nie moze zostac nagle przemianowane logicznie na `verified memory`,
- poprawna odpowiedz w innych trybach nadal moze byc potrzebna dla klasycznej nauki, PJM, statystyk i trudnych pytan,
- `Trener pamieci` potrzebuje wlasnej interpretacji albo wlasnej tabeli.

Najbezpieczniejsza implementacja:

1. Zostawic `QuestionProgressManager` jako kompatybilny baseline dla wszystkich trybow.
2. Dodac osobna tabele `review_memory_progress` albo analogiczna warstwe domenowa dla `verified memory`.
3. Aktualizowac `review_memory_progress` tylko dla `StudySession::mode = sr_review`.
4. W pierwszym etapie traktowac `user_question_progress` jako zrodlo kandydatow do sprawdzenia, a nie jako zrodlo prawdy o potwierdzonej pamieci.
5. Planner powinien laczyc:
   - kandydatow z `user_question_progress`,
   - status potwierdzenia z `review_memory_progress`,
   - eventy z `review_trainer_events`,
   - przyszla prognoze obciazenia dziennego.

Proponowany model `review_memory_progress`:

- `id`,
- `user_id`,
- `question_id`,
- `license_category_id`,
- `verified_attempts_count`,
- `verified_correct_count`,
- `verified_unknown_count`,
- `verified_incorrect_count`,
- `verified_correct_streak`,
- `last_verified_result`,
- `last_verified_at`,
- `next_verified_review_at`,
- `verified_memory_state`,
- `source_policy_version`,
- `last_study_session_answer_id`,
- `created_at`,
- `updated_at`.

Uzasadnienie osobnej tabeli:

- pozwala bezpiecznie oddzielic `training exposure` od `verified memory`,
- nie psuje PJM, dashboardu, analityki, hard questions i publicznych statystyk,
- daje mozliwosc migracji stopniowej,
- pozwala wycofac albo porownac polityke review bez zmiany bazowego SRS.

Przycisk `Nie wiem` - szczegol techniczny:

- `study_session_answers.selected_answer` jest juz nullable,
- ale `null` nie moze sam oznaczac `Nie wiem`,
- egzamin juz uzywa `selected_answer = null` dla timeoutu/braku odpowiedzi,
- dlatego potrzebny jest jawny typ odpowiedzi, np. `answer_kind`.

Proponowane wartosci `answer_kind`:

- `choice` - user wybral A/B/C,
- `unknown` - user kliknal `Nie wiem` w `Trenerze pamieci`,
- `timeout` - systemowy timeout egzaminu,
- opcjonalnie `skipped` - jesli kiedys dodamy jawne pominiecie pytania.

Reguly dla `answer_kind`:

1. Domyslnie stare rekordy i zwykle odpowiedzi maja `choice`.
2. `unknown` jest dozwolone tylko dla `mode = sr_review`.
3. `timeout` jest ustawiane przez logike egzaminu, nie przez usera.
4. Klasyczna nauka, Zen, PJM i egzamin nie dostaja UI `Nie wiem`.
5. API/web requesty musza walidowac `unknown` warunkowo po trybie sesji.

Kolejnosc wdrozenia bezpieczna dla produkcji:

1. Migracja addytywna:
   - dodac `answer_kind` do `study_session_answers` z defaultem `choice`,
   - opcjonalnie dodac `review_memory_progress`.
2. Backend:
   - dodac value object albo enum-like helper dla typow odpowiedzi,
   - rozszerzyc `StudySessionManager::recordAnswer()` tak, aby znal `answer_kind`,
   - nie zmieniac jeszcze zachowania innych trybow.
3. `Nie wiem`:
   - dopuscic `answer_kind = unknown` tylko dla `sr_review`,
   - zapisac `selected_answer = null`, `is_correct = false`, `answer_kind = unknown`,
   - aktualizowac `review_memory_progress` jako `needs_recovery`.
4. Planner:
   - zaczac czytac `review_memory_progress`,
   - uzyc `user_question_progress` jako zrodla kandydatow i ekspozycji,
   - nie zdejmowac od razu starych due count z dashboardu.
5. UI:
   - pokazac `Nie wiem` tylko w `Trenerze pamieci`,
   - komunikacyjnie traktowac to jako "do odzyskania", nie jako porazke.
6. Testy:
   - `sr_review` akceptuje `unknown`,
   - learn/PJM/exam/API zwykle odrzucaja `unknown`,
   - timeout egzaminu zapisuje `answer_kind = timeout`,
   - `QuestionProgressManager` dalej dziala dla istniejacych trybow,
   - planner nie uznaje poprawnej ekspozycji poza review za `verified memory`.

Ryzyka do pilnowania:

- nie pomylic `unknown` z timeoutem egzaminu,
- nie zepsuc wynikow sesji, ktore dzis wyswietlaja `selected_answer = null` jako "Brak odpowiedzi",
- nie przeliczyc nagle readiness score na nowa definicje bez zmiany copy,
- nie usunac due countow z dashboardu, dopoki nie mamy rownoleglej metryki verified memory,
- nie zmienic publicznych statystyk trudnosci pytan bez osobnej decyzji.

##### Finalny preflight przed developmentem

Ten przebieg sprawdzil miejsca, ktore moga nas zaskoczyc przy wdrozeniu `verified memory`, `Nie wiem` i dziennego budzetu 80 pytan.

Blokery funkcjonalne przed Sprintem 4B/4C:

1. `StudySessionManager::normalizeQuestionCount()` ogranicza dzisiaj wiekszosc trybow do 40 pytan. `sr_review` wpada w ten domyslny limit, wiec pierwszy blok 50 i dzienny cel 80 nie zadzialaja bez jawnej reguly dla `MODE_SR_REVIEW`.
2. `ReviewPlannerService::recommendedQuestionCount()` rekomenduje maksymalnie 20 pytan, a `recommendedQuestionIdsFromPlan()` dodatkowo ucina wybor do `recommended_question_count`. Samo ustawienie formularza na 50/80 nic nie da, dopoki planner nie dostanie nowej polityki.
3. `ReviewQueue/Index.vue` liczy ring pamieci przez `recommendedQuestionCount / 20`. Po zmianie polityki trzeba przepiac go na dzienny target albo procent wykonania dnia, inaczej wskaznik bedzie zawsze pelny przy wartosciach powyzej 20.
4. `StoreStudySessionAnswerRequest` wymaga `selected_answer in a,b,c`. `ApiStudySessionAnswerStoreRequest` wymaga `user_answer in a,b,c`. `Nie wiem` wymaga warunkowej walidacji po trybie sesji i po `answer_kind`.
5. `StudySessions/Show.vue` ma lokalny flow odpowiedzi zbudowany wokol A/B/C: `selectAnswer()`, `submitSelectedAnswer()`, `buildLocalResult()` i `persistLocalAnswer()`. `Nie wiem` musi dostac osobna sciezke, ktora nie wymaga `selectedAnswer`.

Powierzchnie kompatybilnosci:

- Trasy i middleware sa bezpieczne kierunkowo: `/trener-pamieci` oraz tworzenie sesji sa za `product.access`, a darmowy wyjatek `study.session.access` dotyczy tylko `mode = pjm`. Nie wolno przez przypadek pokazac `Nie wiem` w PJM, klasycznej nauce, Zen, egzaminie ani rankingu.
- Web i API maja rozne nazwy pola odpowiedzi: web uzywa `selected_answer`, API uzywa `user_answer`. `answer_kind` musi wejsc addytywnie do obu kontraktow, z defaultem `choice`.
- Egzamin juz zapisuje `selected_answer = null` dla timeoutu. Dlatego `unknown` nie moze byc rozpoznawany po samym `null`; potrzebujemy `answer_kind = unknown`, a timeout egzaminu powinien dostac `answer_kind = timeout`.
- `ReviewTrainerCompletionSummaryService` liczy dzisiaj `incorrect_answers_count` przez `is_correct = false`. Po dodaniu `unknown` trzeba pokazac osobne `unknown_count` albo `needs_recovery_count`, zeby copy nie mowilo userowi, ze "zle odpowiedzial", gdy uczciwie kliknal `Nie wiem`.
- `QuestionDailyStatsAggregator`, `PublicQuestionDifficultyService` i `CategoryAnalyticsService` czytaja `study_session_answers` ze wszystkich trybow. Jesli `unknown` zostanie zapisany jako `is_correct = false`, bedzie liczony jako blad w publicznych i adminowych statystykach. To moze byc poprawne jako sygnal "nie umiem", ale wymaga swiadomej decyzji i testu regresji.
- `DashboardMetricsService` liczy dzisiejsze odpowiedzi ze wszystkich `StudySessionAnswer`. Dzienny cel trenera pamieci nie powinien opierac sie na tej metryce, tylko na odpowiedziach `sr_review` albo `review_memory_progress`.
- `AdminUserAccountService::resetLearningData()` usuwa `study_sessions`, `study_session_answers`, `user_question_progress` i dane rankingu, ale nie usuwa `review_trainer_events`. Przy nowej tabeli reset musi usuwac rowniez `review_memory_progress`; warto tez zdecydowac, czy eventy review maja byc czyszczone przy resecie nauki.
- `ops:prune-study-history` usuwa stare sesje i odpowiedzi przez usuniecie `StudySession`. `review_memory_progress` nie moze byc czyszczony przez prune historii, bo jest aktualnym stanem pamieci. Eventy historyczne moga zostac, ale testy powinny to opisac.
- Factory i testy musza dostac default `answer_kind = choice`, z osobnymi stanami dla `unknown` i `timeout`. Bez tego stare testy moga przechodzic przypadkiem, ale nowe migracje nie beda dobrze pokryte.

Decyzje po finalnym audycie:

1. Nie zmieniamy znaczenia `user_question_progress`; to dalej jest ogolny progres nauki i zrodlo kandydatow.
2. `review_memory_progress` jest zrodlem prawdy dla zweryfikowanej pamieci.
3. `QuestionProgressManager` moze na poczatku dalej dostawac odpowiedzi z `sr_review`, ale planner i UI trenera pamieci maja czytac stan zweryfikowany z `review_memory_progress`.
4. Poprawne odpowiedzi z klasycznej nauki, Zen, PJM i egzaminu nie podnosza `verified_memory_state`.
5. Zle odpowiedzi i szybkie strzaly z innych trybow moga podnosic ryzyko kandydata, ale nie tworza pelnego stanu pamieci.
6. `Nie wiem` jest jawna odpowiedzia tylko w `sr_review`, zapisywana jako `answer_kind = unknown`, `selected_answer = null`, `is_correct = false`.
7. Dzienny target 80 liczymy w module trenera pamieci, a nie jako globalne "odpowiedzi dzisiaj" z calej aplikacji.
8. Sesja 50 pytan jest minimum planu, ale jesli realnie jest mniej kandydatow, system moze uruchomic mniejszy zestaw bez sztucznego dopelniania.
9. Gdy `due_count = 0`, kolejny sprint moze dodac lekki booster/reinforcement, ale nie mieszamy tego z pierwszym wdrozeniem `unknown`.

Minimalna kolejnosc zmian po tym audycie:

1. Migracje addytywne: `answer_kind` i `review_memory_progress`.
2. Backend answer contract: enum/helper, walidacja web/API, timeout egzaminu jako `timeout`.
3. Service `ReviewMemoryProgressService` i testy przejsc stanow.
4. UI `Nie wiem` tylko w `sr_review`, z osobnym copy "do odzyskania".
5. Planner 50/80: najpierw usunac limit 40 dla `sr_review`, potem podniesc rekomendacje z 20 do polityki dziennej.
6. Completion summary: osobne liczenie `unknown_count`, `incorrect_count` i `needs_recovery_count`.
7. Regresje: PJM, klasyczna nauka, Zen, egzamin, API, public difficulty, daily stats, admin reset.

##### Drugi preflight - obszary wczesniej niepokryte

Ten przebieg sprawdzil miejsca, ktore nie sa oczywiste z samego flow `/trener-pamieci`, ale moga popsuc wdrozenie po czasie.

Tworzenie sesji i lifecycle:

- `StudySessionManager::start()` przed utworzeniem nowej sesji zamyka wszystkie poprzednie `in_progress` sesje usera przez bulk update.
- Dla starej sesji `sr_review` taki bulk update nie wywoluje `logReviewSessionCompleted()`, wiec `review.session_started` moze nie miec odpowiadajacego `review.completed`.
- To nie musi byc blad, bo moze oznaczac porzucona sesje, ale daily target 80 nie moze bazowac na `review.completed`. Powinien liczyc faktyczne odpowiedzi albo `review_memory_progress`.
- Warto dodac osobny event `review.abandoned` albo jawnie zaakceptowac, ze completion rate mierzy tylko sesje domkniete odpowiedzia lub manualnym complete.
- `complete()` moze recznie domknac niepelna sesje i zapisac `review.completed` z `answered_count < total_questions_count`. Dlatego dzienny progres nie moze utozsamiac `completed` z wykonaniem calego planu.

Requesty tworzenia sesji:

- Web `StudySessionStoreRequest` dopuszcza `review` i `sr_review`, a backend normalizuje `review` do `sr_review`.
- API `ApiStudySessionStoreRequest` tez dopuszcza `review` i `sr_review`, ale nie dopuszcza `pjm`, bo PJM jest osobnym webowym modulem.
- API domyslnie tworzy sesje z `question_count = 20`, jesli klient nie poda liczby. Przy polityce 50/80 klient mobilny albo zewnetrzny musi dostac nowy kontrakt planu, nie moze polegac na starym defaultcie.
- `StudySessionManager::start()` zapisuje w payload `question_count` jako liczbe faktycznie wybranych pytan dla `sr_review`. To jest dobre, ale po zmianie planera testy musza potwierdzic 50/80 na payloadzie, nie tylko na UI.

Idempotencja odpowiedzi i wyscigi:

- `study_session_answers` ma unique `study_session_id + question_id`, wiec baza chroni przed podwojna odpowiedzia na to samo pytanie.
- `StudySessionManager::recordAnswer()` robi jednak sekwencje `find existing answer -> create`. Przy rownoleglym double-clicku albo retry API moze dojsc do wyscigu i QueryException z unique constraint.
- UI juz blokuje wybor po kliknieciu, ale `Nie wiem` doda druga sciezke akcji, wiec backend powinien byc odporny niezaleznie od UI.
- Rekomendacja: przy wdrozeniu `answer_kind` poprawic idempotencje przez `lockForUpdate`, `firstOrCreate` z obsluga konfliktu albo catch duplicate i ponowny odczyt istniejacej odpowiedzi.
- Jesli pierwsza zapisana odpowiedz to `choice`, a drugie klikniecie to `unknown`, backend powinien zwrocic pierwsza odpowiedz i nie zmieniac historii.

Nie wiem w wynikach i lokalnym flow:

- `StudySessions/Show.vue` ma `ResultItem` bez `answer_kind`.
- `displayAnsweredResults` uzywa `shouldIncludeResultInCompletion()`.
- Dla trybow innych niz egzamin `shouldIncludeResultInCompletion()` zwraca dzisiaj `result.selected_answer !== null`.
- Jesli `unknown` bedzie zapisany jako `selected_answer = null`, wynik moze zniknac z podsumowania lokalnego flow.
- Dlatego frontend musi dostac `answer_kind` albo jawne `is_answered`, a logika wynikow musi traktowac `unknown` jako odpowiedz wykonana, ale "do odzyskania".
- Teksty "Brak odpowiedzi" i "Nie udalo sie odczytac tresci tej odpowiedzi" nie moga byc uzyte dla `unknown`.

Implicit time penalty:

- `QuestionProgressManager::qualityScore()` obniza jakosc poprawnej odpowiedzi po czasie:
  - do 7 sekund: quality 5,
  - do 15 sekund: quality 4,
  - powyzej 15 sekund: quality 3.
- User ustalil, ze `Trener pamieci` nie jest trybem na czas, a 18 sekund bylo tylko informacja orientacyjna.
- Jesli `sr_review` nadal przekazuje `response_time_ms` do `QuestionProgressManager`, wolniejsza poprawna odpowiedz moze dostac slabszy interwal w ogolnym progresie.
- Rekomendacja: `review_memory_progress` nie powinien karac za czas odpowiedzi. Osobno trzeba zdecydowac, czy dla `sr_review` do starego `QuestionProgressManager` przekazujemy realny czas, czy `null`, zeby uniknac ukrytej presji czasu.
- Estymacja dlugosci sesji w `ReviewPlannerService` tez bazuje dzisiaj na wszystkich trybach i dodaje okolo 20 sekund na pytanie. W nowym UX lepiej uzyc ostroznej etykiety albo danych tylko z `sr_review`.

API i payloady:

- `StudySessionApiPayloadBuilder::question()` zwraca `selected_answer`, `is_answered` i `response_time_ms`, ale nie zwraca `answer_kind`.
- `ApiStudySessionAnswerController` zwraca tylko `accepted`, `question_id`, `is_correct`, `answered_questions` i `session_status`.
- Po dodaniu `unknown` API musi dostac `answer_kind` w requestach i odpowiedziach, bo inaczej klient nie odrozni `unknown` od timeoutu/braku odpowiedzi.
- Web uzywa pola `selected_answer`, API uzywa `user_answer`; testy musza objac oba warianty.

Operacje, migracje i monitoring:

- `ops:copy-sqlite-to-pgsql` ma reczna liste tabel. Obecnie nie zawiera `review_trainer_events`, a po wdrozeniu trzeba dopisac rowniez `review_memory_progress`.
- Jesli tego nie zrobimy, lokalna migracja danych SQLite -> PostgreSQL ominie telemetry i zweryfikowana pamiec.
- `ReviewTrainerAnalyticsService` i `ReviewTrainerEventLogger` maja guard `Schema::hasTable()`, co ochronilo przed brakiem tabeli po migracji.
- Nowy `ReviewMemoryProgressService` tez powinien miec bezpieczna strategie na okres deployu: albo migracja musi byc wykonana przed uruchomieniem kodu, albo odczyty musza miec guard/fallback.
- Monitoring snapshot liczy dzisiaj sesje, odpowiedzi i agregaty pytan, ale nie liczy eventow review ani przyszlego `review_memory_progress`. To nie blokuje MVP, ale przy premium warto dodac te liczniki do panelu adminowego.

Wydajnosc i rozmiar sesji:

- `renderSessionPage()` ma windowing dla interaktywnych sesji, gdy liczba pytan przekracza `LEARN_FULL_POOL_THRESHOLD = 24`. Sesje 50/80 beda wiec szly sciezka okienkowa, co jest dobre.
- Mimo windowingu backend nadal wykonuje kilka operacji po wszystkich `question_ids`: conflict map, section summary, lista wynikow.
- 50/80 jest bezpiecznym zakresem. Nie nalezy bez dodatkowej optymalizacji podnosic jednorazowych sesji review do setek pytan.
- Frontend w kilku miejscach mapuje cale `questionIds` i uzywa `indexOf`; dla 80 jest OK, dla bardzo duzych sesji moze byc kosztowne.

Nowe decyzje po drugim preflight:

1. Dzienny postep trenera pamieci liczymy z odpowiedzi `sr_review` albo `review_memory_progress`, nie z eventow completion.
2. `unknown` musi miec `answer_kind` w backendzie, API, Inertia props i lokalnym stanie Vue.
3. Podsumowanie sesji musi miec osobna definicje "odpowiedz wykonana" niezalezna od `selected_answer`.
4. Przy wdrozeniu `answer_kind` naprawiamy idempotencje odpowiedzi albo przynajmniej dodajemy test race-safe retry.
5. Nowe tabele dodajemy do `ops:copy-sqlite-to-pgsql` i do resetu danych nauki.
6. Nie opieramy zadnej decyzji memory engine na czasie odpowiedzi bez osobnej zgody produktowej.

##### Trzeci preflight - zachowanie po calym dniu pracy

Ten przebieg sprawdzil, co stanie sie po wielu kliknieciach, retry requestach, bledach i pustej kolejce due.

Idempotencja progresu jest slabsza niz idempotencja odpowiedzi:

- `study_session_answers` ma unique `study_session_id + question_id`, wiec druga odpowiedz na to samo pytanie nie tworzy drugiego rekordu odpowiedzi.
- `StudySessionManager::recordAnswer()` przy istniejacej odpowiedzi zwraca ja z transakcji, ale po transakcji nadal bezwarunkowo wywoluje `QuestionProgressManager::recordAnswer()`.
- To oznacza, ze retry requestu albo double-click moze nie zdublowac `study_session_answers`, ale moze zdublowac `user_question_progress`.
- Skutek: `total_attempts`, `correct_count`, `incorrect_count`, `correct_streak`, `next_review_at` i `last_quality` moga zostac zmienione drugi raz za te sama odpowiedz.
- Przy `unknown` byloby to szczegolnie grozne, bo jedno uczciwe `Nie wiem` mogloby policzyc sie jako kilka niepowodzen.

Decyzja techniczna:

- `StudySessionManager::recordAnswer()` musi wiedziec, czy odpowiedz jest nowo utworzona.
- `QuestionProgressManager::recordAnswer()` i przyszly `ReviewMemoryProgressService` powinny byc wywolane tylko dla nowej odpowiedzi.
- Dla istniejacej odpowiedzi backend powinien zwracac istniejacy stan bez zmiany progresu.
- Test regresji: dwa identyczne POST-y na to samo pytanie zostawiaja `user_question_progress.total_attempts` bez drugiego przyrostu.

Zapetlenie due tego samego dnia:

- `QuestionProgressManager` ustawia `next_review_at = answeredAt->toDateString()` dla odpowiedzi z quality < 3.
- Bledna odpowiedz oraz przyszle `unknown` zostaja wiec due natychmiast tego samego dnia.
- `ReviewPlannerService` wybiera pytania przez `next_review_at <= today()`, bez wykluczenia pytan juz przerobionych dzisiaj w `sr_review`.
- Skutek: po sesji z bledami kolejna sesja trenera moze znowu dobrac te same pytania, zamiast prowadzic usera przez dzienny target 80 unikalnych prob.
- To jest sprzeczne z celem "nie karac regularnosci" i moze stworzyc wrazenie, ze kolejka nigdy sie nie konczy.

Decyzja produktowo-algorytmiczna:

- Dzienny cel 80 powinien liczyc unikalne pytania/proby trenera z danego dnia, a nie tylko aktualny due count.
- Pytanie oznaczone `unknown` moze wejsc do mini-powrotu tego samego dnia tylko przez jawna polityke `same_day_recovery`, nie przez domyslne `next_review_at <= today()`.
- W pierwszym wdrozeniu bezpieczniej wykluczyc z glownego batcha pytania juz odpowiedziane dzisiaj w `sr_review`.
- Osobny recovery block moze byc pozniejszym sprintem, z limitem udzialu w sesji.

Brak zrodla nowych i booster pytan:

- Obecny `ReviewPlannerService` czyta tylko `user_question_progress`.
- Pytania bez progresu nie trafiaja do planu.
- Pytania zaplanowane na przyszlosc tez nie trafiaja do planu, nawet jesli dzienny target 80 nie jest wypelniony.
- Test `review queue renders an empty training plan when nothing is due now` potwierdza obecne zachowanie: gdy nic nie jest due, plan jest pusty.
- Dlatego reguly "dopelnij dzienny cel nowymi albo wzmacniajacymi pytaniami" wymagaja nowego zrodla kandydatow z `questions` oraz `review_memory_progress`, a nie tylko zmiany limitu.

Decyzja dla dopelniania 80:

- Faza 1: due/recovery tylko z bezpiecznym wykluczeniem pytan juz przerobionych dzisiaj.
- Faza 2: booster z pytan przyszlych, ale tylko gdy prognoza kolejnych dni jest lekka.
- Faza 3: nowe pytania bez progresu, ale tylko jako kontrolowane dopelnienie i bez mieszania z `verified memory`.
- Nowe pytania po pierwszej ekspozycji staja sie kandydatami do pozniejszej weryfikacji, nie od razu `verified memory`.

Skalowanie planera:

- `ReviewPlannerService::orderedProgress()` pobiera wszystkie due rekordy usera do pamieci i sortuje je w PHP.
- Przy obecnej bazie kategorii B to nadal jest akceptowalne dla jednego usera, ale przy duzej kolejce i planie 80 nie powinnismy bez potrzeby renderowac ani sortowac setek/tysiecy elementow na kazdym odswiezeniu.
- `PREVIEW_LIMIT = 60` oznacza, ze UI preview i tak pokazuje tylko pierwsze 60, ale backend nadal buduje pelna liste `ordered_question_ids`.
- Przy docelowym daily plannerze warto rozwazyc limitowanie kandydatow na poziomie SQL albo dwuetapowy wybor: najpierw mala pula priorytetowa, potem sortowanie domenowe.

Kontrakt kategorii i pustych stanow:

- Ekran `/trener-pamieci` buduje liste kategorii z due countow.
- Gdy nic nie jest due, `categories` moze byc puste, mimo ze user ma aktywna kategorie nauki.
- Po dodaniu booster/new candidates UI nie moze polegac wylacznie na `categories` z due. Musi znac aktywna kategorie z profilu albo planu.
- Empty state powinien rozrozniac:
  - brak zaleglych powtorek,
  - dzienny cel juz domkniety,
  - mozliwy lekki booster,
  - brak materialu w kategorii.

Nowe decyzje po trzecim preflight:

1. Naprawa idempotencji progresu jest blokujaca przed `unknown`.
2. Planner musi miec filtr "nie dobieraj ponownie w glownym batchu pytan juz odpowiedzianych dzisiaj w `sr_review`".
3. Same-day recovery moze istniec, ale tylko jako jawny i limitowany blok, nie jako efekt uboczny `next_review_at = today`.
4. Dopelnianie do 80 wymaga nowych zrodel kandydatow, nie tylko podniesienia limitow.
5. Empty state `/trener-pamieci` musi byc oparty o plan dnia, nie tylko o due count.

##### Czwarty preflight - kontrakty danych i operacje

Ten przebieg sprawdzil miejsca poza samym ekranem trenera: import SQLite -> PostgreSQL, reset danych usera, shared header, monitoring i typy danych.

Idempotencja `verified memory` musi miec zrodlo odpowiedzi:

- `study_session_answers` ma unikalnosc `study_session_id + question_id` i w normalnym retry zwraca istniejaca odpowiedz.
- Problem z trzeciego preflight polega na tym, ze sam progres moze zostac przeliczony drugi raz dla tej samej odpowiedzi.
- Dlatego `ReviewMemoryProgressService` nie powinien aktualizowac licznikow tylko dlatego, ze dostal usera i pytanie.
- Aktualizacja `review_memory_progress` powinna byc zwiazana z konkretnym `study_session_answer_id`.
- MVP moze miec `last_study_session_answer_id` w `review_memory_progress` i procesowac tylko nowo utworzone odpowiedzi.
- Docelowo `review_events` powinno miec `study_session_answer_id` z unikalnym indeksem dla eventow odpowiedzi, zeby event log byl prawdziwym ledgerem.

Shared header i licznik due:

- `HandleInertiaRequests` wystawia dzisiaj tylko `studyContext.reviewDueCount`.
- Ten count pochodzi z `StudyContextService::dueReviewCount()` i liczy cale `user_question_progress` usera po `next_review_at <= today()`.
- Ten count nie filtruje kategorii przez aktywne/visible categories ani przez stale przypisana kategorie konta.
- `SiteHeader.vue` pokazuje tekst `Brak zaleglych powtorek`, gdy count wynosi 0.
- Po dodaniu daily planu 80 pytan moze byc sytuacja: brak zaleglych due, ale nadal istnieje lekki booster albo dzienny plan.
- Wniosek: header nie moze byc jedynym zrodlem prawdy dla trenera pamieci.
- Najbezpieczniej dodac osobny kontrakt planu, np. `studyContext.reviewPlanToday` albo zostawic header jako legacy due i nie mieszac go z dziennym celem.
- Sam `reviewDueCount` powinien zostac przefiltrowany tak samo jak `/trener-pamieci`, zeby locked category i stare dane z innych kategorii nie pokazywaly blednej liczby.

Dzienny cel i pole daty:

- `DashboardMetricsService` liczy `answered_today` po `study_session_answers.created_at`.
- To jest metryka ogolnej aktywnosci, nie dobry licznik dziennego celu trenera pamieci.
- Dzienny cel 80 powinien liczyc tylko `sr_review` albo `review_memory_progress`.
- Polem czasu powinno byc `answered_at` albo `last_verified_at`/`occurred_at`, a `created_at` najwyzej fallbackiem.
- Liczenie dnia musi uzywac app timezone, zeby odpowiedzi okolo polnocy nie przeskakiwaly miedzy dniami inaczej niz UI.

Import, reset i monitoring:

- `ops:copy-sqlite-to-pgsql` ma reczne listy `$tables` i `$tablesWithIdentity`.
- Obecnie lista nie zawiera `review_trainer_events`; po migracji trzeba dopisac rowniez `review_memory_progress`.
- Jesli tabela ma FK do `users`, `questions` albo `study_sessions`, `TRUNCATE ... CASCADE` moze ja wyczyscic, a brak kopiowania potem zostawi puste dane review.
- Dlatego nowe tabele review musza wejsc do importu w tym samym PR co migracje.
- `AdminUserAccountService::resetLearningData()` usuwa sesje, odpowiedzi, `user_question_progress` i ranking, ale nie usuwa `review_trainer_events`.
- Przy zmianie kategorii konta trzeba usunac `review_memory_progress`; dla `review_trainer_events` trzeba podjac decyzje: czyscimy razem z nauka albo zostawiamy jako historyczna telemetry z kategoria.
- `MonitoringSnapshotService` historycznie liczyl tylko sesje, odpowiedzi i agregaty pytan.
- Status po sprincie 18.19: snapshoty i panel admina licza juz `review_trainer_events`, `review_memory_progress` oraz `review_trainer_daily_answers`, wiec wzrost memory engine jest widoczny operacyjnie.

Typy danych i indeksy:

- `study_sessions.correct_answers_count` i `total_questions_count` sa `unsignedSmallInteger`; dla sesji 50/80 to jest bezpieczne.
- Liczniki zyciowe w `review_memory_progress` nie powinny byc smallintami, bo moga rosnac latami.
- Dla `verified_attempts_count`, `verified_correct_count`, `verified_unknown_count` i podobnych uzywamy co najmniej `unsignedInteger`.
- `review_trainer_events.event_id` jest unikalny i dobry jako publiczny identyfikator eventu.
- `review_trainer_events.study_session_id` jest nullable; po prune historii wiele eventow moze miec `study_session_id = null`, wiec unikalnosc po `study_session_id + event_name` nie ochroni eventow odpowiedzi. To kolejny argument za `study_session_answer_id` dla answer-level telemetry.

Luki testowe do zamkniecia przy wdrozeniu:

- test double POST na to samo pytanie nie zmienia drugi raz `user_question_progress`,
- test double POST na `unknown` nie zwieksza drugi raz `verified_unknown_count`,
- test `review_memory_progress` przetwarza dana odpowiedz tylko raz po `study_session_answer_id`,
- test `studyContext.reviewDueCount` respektuje aktywna/przypisana kategorie,
- test resetu kategorii usuwa `review_memory_progress` i dokumentuje decyzje dla `review_trainer_events`,
- test SQLite -> PostgreSQL importu ma nowe tabele review w listach,
- test dziennego celu uzywa `answered_at`/`last_verified_at`, nie globalnego `created_at`.

Nowe decyzje po czwartym preflight:

1. `review_memory_progress` musi miec powiazanie z ostatnia przetworzona odpowiedzia albo korzystac z answer-level event logu.
2. `ReviewMemoryProgressService` aktualizuje stan tylko dla nowej odpowiedzi `sr_review`.
3. Header `reviewDueCount` pozostaje legacy due albo zostaje filtrowany; dzienny plan trenera dostaje osobny kontrakt.
4. Nowe tabele review dopisujemy do importu SQLite -> PostgreSQL, admin resetu i docelowego monitoringu w tym samym sprincie co migracje.
5. Liczniki zyciowe `verified memory` projektujemy jako `unsignedInteger`, nie jako male liczniki sesyjne.

##### Piaty preflight - kontrakt klienta, API i metryki produktu

Ten przebieg sprawdzil, gdzie przyszle `answer_kind`, `Nie wiem` i dzienny plan 80 moga rozjechac sie z obecnym web/API flow.

Web i API maja dwa rozne kontrakty odpowiedzi:

- Webowy request uzywa `selected_answer`.
- API request uzywa `user_answer`.
- Web i API maja osobne request klasy: `StoreStudySessionAnswerRequest` oraz `ApiStudySessionAnswerStoreRequest`.
- Web i API maja osobne kontrolery odpowiedzi.
- API `StudySessionApiPayloadBuilder::question()` zwraca `selected_answer`, `is_answered` i `response_time_ms`, ale nie zwraca `answer_kind`.
- API odpowiedzi po zapisie zwraca `accepted = $answer->wasRecentlyCreated`.
- Po naprawie idempotencji `accepted = false` moze znaczyc "odpowiedz juz istniala", a nie "nie udalo sie zapisac".

Decyzja:

- `answer_kind` musi wejsc jednoczesnie do web requestu, API requestu, web response, API response i payload buildera.
- API powinno miec jawne pola, np. `answer_kind`, `is_answered`, `accepted`, `already_answered`, zeby klient nie zgadywal znaczenia `accepted`.
- Web i API musza miec wspolny test kontraktu dla `choice`, `unknown` i `timeout`.

Lokalny optymistyczny flow sesji:

- `StudySessions/Show.vue` traktuje `sr_review` jako `isLocalLearningMode`.
- Odpowiedz jest najpierw zapisywana lokalnie w `localResults`, a dopiero potem synchronizowana z backendem.
- `persistLocalAnswer()` zwraca juz istniejacy lokalny wynik i nie wysyla ponownie requestu dla tego pytania.
- Jesli zapis na serwerze padnie, UI ustawia `answerSyncFailedQuestionId` i pokazuje modal z prosba o odswiezenie strony.
- To jest akceptowalne dla obecnego A/B/C, ale przy rolloutcie `unknown` blad walidacji moze wygladac dla usera jak awaria serwera.

Decyzja:

- Najpierw wdrazamy backendowy kontrakt `answer_kind`, potem UI `Nie wiem`.
- Dla `sr_review` po dodaniu `unknown` trzeba albo:
  - dodac prawdziwy retry zapisu odpowiedzi,
  - albo nie utrwalac lokalnego `unknown` przed sukcesem backendu,
  - albo umiec wyczyscic lokalny wynik po walidacji i pozwolic userowi odpowiedziec ponownie.
- Test regresji powinien sprawdzic, ze odrzucone `unknown` poza `sr_review` nie zamraza lokalnego flow.

Kolejnosc odpowiedzi i liczenie progresu:

- `StudySessionManager::allowsFlexibleAnswerOrder()` dopuszcza elastyczna kolejnosc dla `sr_review`.
- Frontend moze synchronizowac odpowiedzi przez kolejke requestow, ale user moze nawigowac po pytaniach w lokalnym flow.
- `current_index` nie jest wiarygodnym zrodlem wykonania dnia.
- Dzienny cel 80 musi liczyc zaakceptowane odpowiedzi z backendu, nie pozycje UI ani optimistic state.
- W `review_memory_progress` liczymy tylko odpowiedzi, ktore maja zapisany rekord `study_session_answer`.

Score percent nie jest dobra metryka trenera pamieci:

- `StudySession` nadal ma `correct_answers_count` i `score_percent`.
- `ReviewTrainerCompletionSummaryService` liczy `incorrect_answers_count` jako `is_correct = false`.
- `ReviewTrainerAnalyticsService` liczy `average_score_percent` i completion rate z eventow.
- Przy przycisku `Nie wiem` uczciwa odpowiedz "nie odzyskalem" obnizy klasyczny score tak samo jak bledny strzal.
- Dla trenera pamieci to moze byc mylace, bo celem modulu jest odsianie watpliwosci, a nie tylko procent poprawnych.

Decyzja:

- `score_percent` moze zostac technicznym legacy polem sesji.
- UI i telemetry trenera pamieci powinny promowac metryki domenowe:
  - `verified_count`,
  - `needs_recovery_count`,
  - `unknown_count`,
  - `daily_completed_count`,
  - `daily_target_count`.
- `average_score_percent` nie powinno byc glowna metryka premium memory engine.

Widocznosc kategorii i user-facing API:

- `/trener-pamieci` buduje allowed categories przez `StudyContextService::activeCategories()`.
- Start sesji web/API pobiera kategorie przez `LicenseCategory::where('is_active', true)->findOrFail(...)`.
- To nie jest identyczne z `visibleCategoriesQuery()`, ktore filtruje oficjalne kategorie i gotowe pytania.
- Dla zwyklych locked userow ryzyko jest male, bo `assertUserCanUseCategory()` pilnuje przypisanej kategorii.
- Dla kont z pelnym dostepem albo systemowych API mozna jednak latwiej trafic w aktywna kategorie wewnetrzna/testowa.

Decyzja:

- User-facing start sesji `sr_review` powinien korzystac z tego samego zakresu kategorii co `/trener-pamieci`.
- Test powinien potwierdzic, ze ukryta albo wewnetrzna kategoria nie startuje sesji memory z publicznego API, jesli nie jest w visible categories.
- Wyjatek dla kont systemowych powinien byc jawny, a nie efektem ubocznym `canUseAllStudyCategories()`.

Aktualny ekran `/trener-pamieci` nadal jest oparty o due-only plan:

- `ReviewQueue/Index.vue` typuje plan jako `due_count`, `recommended_question_count`, segmenty i memory state counts.
- Ring nadal opiera sie na `recommendedQuestionCount / 20`.
- `activeCategory` jest szukane w `categories`, a `categories` zawiera tylko kategorie z due countem.
- Po dodaniu booster/new candidates moze byc plan dla kategorii, ktorej nie ma w `categories`.
- Wtedy label zakresu moze pokazac "Wszystkie aktywne kategorie", mimo ze plan dotyczy konkretnej kategorii.

Decyzja:

- Plan dnia powinien zwracac wlasny `category`/`scope`, niezalezny od listy due categories.
- UI powinno czytac label zakresu z planu albo ze shared `studyContext.categories`, nie tylko z `categories` wyliczonych z due.

Czas odpowiedzi nadal jest widoczny w sesji:

- `StudySessions/Show.vue` pokazuje `Czas: ...` przy wynikach sesji.
- `ReviewPlannerService` estymuje czas po historycznych `response_time_ms` ze wszystkich trybow.
- User jasno ustalil, ze trener pamieci nie jest trybem na czas.

Decyzja:

- Dla `sr_review` nie eksponowac czasu jako oceny w wynikach.
- Jesli zostawiamy estymacje, ma byc tylko orientacyjna etykieta czasu trwania sesji, nie metryka pojedynczej odpowiedzi.

Nowe decyzje po piatym preflight:

1. `answer_kind` wdrazamy jako wspolny kontrakt web + API + payload builder, nie tylko jako pole w webowym formularzu.
2. Przed UI `Nie wiem` trzeba zabezpieczyc lokalny optimistic flow przed zamrozeniem po walidacji albo dodac retry.
3. Dzienny progres liczy backendowe zaakceptowane odpowiedzi, nie `current_index`, optimistic state ani completion event.
4. `score_percent` zostaje legacy, ale premium memory UI i telemetry przechodza na metryki `verified/unknown/recovery/daily`.
5. Start sesji `sr_review` powinien uzywac tego samego zakresu kategorii co ekran `/trener-pamieci`.
6. Plan dnia musi miec wlasny opis kategorii/scope, niezalezny od listy kategorii z due countem.

##### Szosty preflight - backfill, historia i agregaty

Ten przebieg sprawdzil, co moze sie wydarzyc po dodaniu `answer_kind` do systemu, ktory ma juz historyczne odpowiedzi, timeouty egzaminacyjne i materializowane statystyki pytan.

Istniejace `selected_answer = null` nie znaczy automatycznie `Nie wiem`:

- Egzamin automatycznie zapisuje timeout jako:
  - `selected_answer = null`,
  - `is_correct = false`,
  - `response_time_ms` z limitu czasu pytania,
  - `answered_at` ustawione na deadline.
- Testy juz potwierdzaja, ze timeout egzaminu zapisuje `selected_answer = null`.
- Starsze testy i dane moga miec manualne rekordy `selected_answer = null` poza egzaminem.
- Nowy przycisk `Nie wiem` nie istnial historycznie, wiec zadnego starego rekordu nie wolno backfillowac do `unknown` tylko dlatego, ze ma `selected_answer = null`.

Rekomendowana macierz backfillu `answer_kind`:

- `selected_answer IS NOT NULL` -> `choice`,
- `selected_answer IS NULL` i `study_sessions.mode = exam` -> `timeout`,
- `selected_answer IS NULL` i `study_sessions.mode != exam` -> `skipped` albo `legacy_unanswered`,
- `unknown` zaczyna sie od zera w dniu rollouttu i moze powstawac tylko z jawnego przycisku w `sr_review`.

Strategia migracji:

1. Najpierw dodac `answer_kind` addytywnie, najlepiej jako nullable albo z bardzo ostroznym defaultem.
2. Backfill zrobic jawnie, w oparciu o join do `study_sessions.mode`.
3. Dopiero po backfillu ustawic default `choice` dla nowych odpowiedzi, a ewentualne `NOT NULL` dodac tylko jesli mamy pewnosc cross-DB.
4. W tym samym sprincie zaktualizowac:
   - `StudySessionAnswer::$fillable`,
   - casty/model constants,
   - `StudySessionAnswerFactory`,
   - factory states dla `choice`, `unknown`, `timeout`, `skipped/legacy_unanswered`,
   - testy web/API.

Statystyki publiczne i adminowe moga zostac zanieczyszczone przez `unknown`, jesli potraktujemy je jak zwykle `is_correct = false`:

- `QuestionDailyStatsAggregator` liczy `incorrect_answers_count` po `is_correct = false`.
- `PublicQuestionDifficultyService` takze czyta `is_correct` i czas odpowiedzi bez znajomosci `answer_kind`.
- `ReviewTrainerCompletionSummaryService` obecnie traktuje kazde `is_correct = false` jako bledna odpowiedz w podsumowaniu.
- Jesli `unknown` ma byc uczciwym sygnalem "nie odzyskalem z pamieci", trzeba zdecydowac, czy:
  - w publicznej trudnosci liczy sie jako blad,
  - jest osobna metryka `unknown_count`,
  - jest wykluczane z klasycznych agregatow i widoczne tylko w trenerze pamieci.

Materializowane agregaty wymagaja osobnej decyzji cutover:

- `ops:aggregate-question-daily-stats` domyslnie przelicza tylko ostatnie dni.
- `ops:rollup-question-monthly-stats` archiwizuje starsze dzienne statystyki do miesiecznych.
- `ops:prune-study-history` usuwa stare sesje i odpowiedzi po okresie retencji.
- Po prune nie zawsze da sie odtworzac stare statystyki z raw `study_session_answers`.

Decyzja:

- Przed zmiana semantyki `unknown` dla statystyk trzeba wybrac:
  - forward-only cutover od daty rollouttu,
  - albo komenda rebuild/backfill agregatow przed prune,
  - albo wersjonowane statystyki, gdzie stare i nowe liczenie sa jawnie rozdzielone.
- Dla MVP najbezpieczniejszy jest forward-only cutover: stare statystyki zostaja klasyczne, a nowe metryki trenera pamieci sa liczone osobno w `review_memory_progress`/eventach.

SQLite i PostgreSQL nie sa identycznym srodowiskiem ryzyka:

- Testy dzialaja na SQLite.
- Lokalny stack u usera dziala na PostgreSQL.
- Constrainty typu enum/check/partial index moga zachowywac sie inaczej albo zostac przetestowane tylko czesciowo.

Decyzja:

- W pierwszym etapie `answer_kind` powinien byc prostym stringiem z walidacja aplikacyjna i wspolnymi constants/helperami.
- Twarde constrainty DB mozna dodac pozniej, gdy mamy osobny smoke test PostgreSQL.
- Nie opieramy bezpieczenstwa MVP na constraintach, ktorych nie pokrywa obecny test runner.

Timeout, `unknown` i legacy null musza zostac rozdzielone produktowo:

- `timeout` to zdarzenie systemowe egzaminu albo przyszlego trybu z limitem.
- `unknown` to swiadoma decyzja usera w `sr_review`.
- `skipped/legacy_unanswered` to historyczny albo techniczny fallback.
- Timeout egzaminu moze dalej aktualizowac ogolny progres nauki, ale nie powinien podnosic ani obnizac `verified memory` jak odpowiedz w trenerze pamieci.

Nowe decyzje po szostym preflight:

1. Backfill `answer_kind` wymaga jawnej macierzy, testow i joinu do `study_sessions.mode`.
2. Historyczne `selected_answer = null` nie staje sie `unknown`.
3. `unknown_count` startuje od zera w dniu rollouttu.
4. Publiczne/adminowe agregaty musza miec decyzje, czy `unknown` liczy sie jako blad, osobny sygnal czy jest wykluczony.
5. Dla MVP wybieramy prosta kolumne string + walidacja aplikacyjna, a DB constrainty zostawiamy na etap po smoke tescie PostgreSQL.
6. Przed zmiana agregatow wybieramy forward-only cutover albo plan rebuild/backfill, bo prune historii moze usunac raw dane.

##### Siodmy preflight - izolacja UI, payload i metryki wspolne

Ten przebieg sprawdzil, czy trener pamieci jest juz wystarczajaco oddzielony od klasycznej nauki w UI i w payloadach sesji.

Wspolny ekran sesji traktuje `sr_review` jako local learning mode:

- `StudySessions/Show.vue` ustawia `isLocalLearningMode` dla `learn`, `pjm` oraz `sr_review`.
- To jest technicznie wygodne, bo daje szybki lokalny flow odpowiedzi.
- Jednoczesnie wiele warunkow w UI uzywa tylko `!isPjmMode`, a nie rozroznia `learn` od `sr_review`.
- W efekcie ekran trenera moze dostac klasyczne akcje:
  - `Powtorz bledne pytania`,
  - `Powtorz ten dzial`,
  - `Zacznij nastepny dzial`,
  - `Wybierz kolejny dzial`,
  - picker dzialow w trakcie sesji.
- Te akcje startuja nowe sesje `mode = learn`, nie `sr_review`.
- Start nowej sesji zamyka poprzednia sesje `in_progress` bulk updatem, wiec aktywny trening pamieci moze zostac cicho zakonczony bez pelnej telemetry.

Decyzja:

- W UI potrzebny jest osobny guard, np. `isClassicLearningMode = props.session.mode === 'learn'`.
- Klasyczne akcje dzialow i follow-upow powinny byc widoczne tylko dla klasycznej nauki.
- `sr_review` powinien miec w wynikach tylko akcje domenowe:
  - wroc do trenera,
  - kontynuuj plan dnia, jesli zostaly pytania,
  - odzyskaj pytania do powrotu, jesli zrobimy taki osobny batch w `sr_review`.
- Zadna akcja w wynikach `sr_review` nie powinna po cichu przechodzic do `learn`, chyba ze copy jasno mowi, ze wychodzimy z trenera do klasycznej nauki.

Copy wynikow jest dzialowe, a trener nie zawsze jest dzialowy:

- Wynik wspolny pokazuje tekst w stylu `pytań w tym dziale`.
- `activeSessionTopic` jest liczone z topic groups i aktywnego pytania.
- Sesja `sr_review` moze byc kolejka mieszana po kilku tematach w jednej kategorii.
- Pokazywanie jednego dzialu w wyniku trenera moze sugerowac, ze user przerabial rozdzial, a nie plan pamieci.

Decyzja:

- Dla `sr_review` copy powinno mowic o `treningu pamieci`, `planie dnia` albo `zestawie`, nie o dziale.
- Zakres/kategoria w wyniku powinny pochodzic z `review_plan` albo plan scope, nie z przypadkowego `activeSessionTopic`.
- `completionTitle`, statystyki i CTA dla `sr_review` powinny miec osobna sciezke copy.

Wspolny wynik nadal eksponuje czas i klasyczna skutecznosc:

- W summary zakonczonej sesji zawsze moze pojawic sie `Czas sesji`.
- Moze pojawic sie tez sredni czas poprawnej odpowiedzi.
- `Skutecznosc`, `Dobrze`, `Bledne` bazuja na klasycznym `is_correct`.
- Przy `unknown` to bedzie zbyt ubogie, bo "nie wiem" nie jest zwyklym bledem ani timeoutem.

Decyzja:

- Dla `sr_review` ukrywamy albo degradowamy metryki czasu.
- Glowna sekcja wyniku `sr_review` pokazuje:
  - wykonane w planie,
  - odzyskane,
  - `Nie wiem` / do odzyskania,
  - bledne strzaly, jesli zostaja osobno,
  - nastepny bezpieczny krok.
- `score_percent` moze zostac w danych technicznych, ale nie powinien byc centralnym UI dla trenera pamieci.

Payload sesji laduje PJM assets takze poza PJM:

- `StudySessionController::renderSessionPage()` i batch endpoints eager-loaduja `activeSignLanguageAssets` dla pytan sesji.
- `transformQuestion()` zawsze zwraca `sign_language_assets`.
- Vue realnie uzywa `sign_language_assets` do bloku PJM tylko wtedy, gdy `isPjmMode`.
- Po ostatnich poprawkach PJM nie wyswietla sie w klasycznej nauce i Zen, ale payload dalej moze niesc dane PJM.
- Przy sesjach 50/80 w `sr_review` to jest niepotrzebny koszt zapytan, serializacji i rozmiaru JSON.

Decyzja:

- Przed podniesieniem trenera do 50/80 trzeba zrobic mode-aware payload:
  - `activeSignLanguageAssets` tylko dla `mode = pjm`,
  - `sign_language_assets` tylko dla PJM,
  - klasyczna nauka i `sr_review` nie powinny pobierac PJM assets, skoro ich nie renderuja.
- To jest potencjalny low-risk performance win i moze tlumaczyc czesc odczucia, ze sesje review dzialaja wolno.

Topic groups sa budowane takze dla trenera:

- `renderSessionPage()` buduje `topicGroups` dla kazdej sesji z kategoria.
- `canUseTopicPicker` jest dzisiaj `!isPjmMode && filteredTopicGroups.length > 0`, wiec obejmuje takze `sr_review`.
- Te same topic groups zasilaja klasyczne akcje dzialow i roadmapy.

Decyzja:

- Dla `sr_review` nie budujemy topic groups, chyba ze nowy design trenera faktycznie ich potrzebuje.
- Jesli potrzebujemy podzialu w trenerze, powinien to byc payload planu pamieci, a nie klasyczny topic picker.
- To ogranicza payload, usuwa przypadkowe CTA i wzmacnia izolacje produktu.

Metryki wspolne nadal mieszaja tryby:

- `DashboardMetricsService` pokazuje ostatnie sesje i dzisiejsze odpowiedzi ze wszystkich trybow.
- `CategoryAnalyticsService` liczy odpowiedzi, accuracy i completed sessions bez filtra trybu.
- `UserReadinessService` w fallbacku liczy sredni `score_percent` z zakonczonych sesji bez rozroznienia trybu.
- Adminowe wykresy aktywnosci tez bazuja na `study_sessions` bez mode-aware segmentow.

Decyzja:

- To moze zostac jako ogolna aktywnosc, ale klasyczna gotowosc do egzaminu nie powinna byc przypadkowo sterowana przez `unknown` z trenera.
- Wprowadzamy rozroznienie:
  - `classic_learning_metrics` dla nauki/egzaminu/trybow klasycznych,
  - `memory_trainer_metrics` dla `sr_review`,
  - `overall_activity` tylko jako licznik aktywnosci produktu.
- Przed dodaniem `unknown` trzeba oznaczyc, ktore dashboardy maja zostac wspolne, a ktore maja ignorowac `sr_review`.

Nowe decyzje po siodmym preflight:

1. `sr_review` potrzebuje osobnych guardow UI; `!isPjmMode` nie moze oznaczac "klasyczna nauka".
2. Klasyczne akcje dzialow/follow-upow nie powinny pojawiac sie w aktywnej ani zakonczonej sesji trenera pamieci.
3. Wynik `sr_review` ma mowic o planie pamieci, nie o dziale.
4. Payload PJM assets powinien byc mode-aware i nie jechac przez klasyczna nauke ani trenera.
5. `topicGroups` dla `sr_review` sa do usuniecia albo zastapienia planem pamieci.
6. Dashboardy i readiness score wymagaja decyzji, ktore metryki sa klasyczne, a ktore sa memory-only.

##### Osmy preflight - granice dnia, cache i lifecycle dostepu

Ten przebieg sprawdzil operacyjne miejsca, ktore moga rozjechac dzienny plan 80 pytan mimo poprawnego algorytmu.

Definicja dnia musi byc jedna:

- `config/app.php` ustawia timezone aplikacji na `Europe/Warsaw`.
- `user_question_progress.next_review_at` jest polem `date`.
- Due queries uzywaja `whereDate(next_review_at, '<=', today())`.
- `study_session_answers.answered_at`, `study_sessions.started_at`, `completed_at` i `created_at` sa timestampami.
- Czesc metryk dziennych liczy aktywnosc po `created_at`, czesc po `answered_at`, a streak usera po rozpoczeciu sesji.

Decyzja:

- Dla trenera pamieci definiujemy `review_day` jako date w app timezone.
- Dzienny cel 80 liczymy po zaakceptowanych odpowiedziach `sr_review` zapisanych w answer-level ledger albo dziennym agregacie:
  - `study_session_answers.answered_at` moze byc raw zrodlem, dopoki rekordy odpowiedzi istnieja,
  - `review_trainer_answer_events` albo `review_daily_progress` powinien byc trwalym zrodlem po prune raw sesji,
  - `review_memory_progress.last_verified_at` nadaje sie do liczenia unikalnych pytan dotknietych dzisiaj, ale nie jako jedyny licznik wszystkich zaakceptowanych odpowiedzi/prob.
- Nie liczymy dziennego celu po:
  - `study_sessions.created_at`,
  - `study_sessions.started_at`,
  - `study_sessions.completed_at`,
  - `UserProfileService::last_study_date`.
- `review_daily_plans`, jesli powstanie, powinien miec jawne `plan_date`/`review_day`.

Streak i plan dnia to dwa rozne pojecia:

- `StudySessionManager::start()` wywoluje `UserProfileService::markStudyActivity()` na starcie sesji.
- User moze zaczac sesje przed polnoca, a odpowiedziec po polnocy.
- Wtedy streak/start aktywnosci i dzienny cel trenera moga logicznie trafic w rozne dni.

Decyzja:

- Nie uzywamy `study_streak` ani `last_study_date` do celu 80.
- Streak moze zostac ogolna metryka aktywnosci produktu.
- Memory daily target musi byc answer-based, nie session-start-based.

Scheduler i retencja maja znaczenie dla nowych agregatow:

- Produkcyjne joby sa ulozone mniej wiecej tak:
  - daily stats okolo 02:55,
  - monitoring snapshot okolo 03:00,
  - monthly rollup okolo 03:10,
  - prune study history okolo 03:20.
- `ops:prune-study-history` usuwa zakonczone sesje po retencji i porzucone `in_progress` po kilku dniach.
- Usuniecie `StudySession` kaskadowo usuwa `study_session_answers`.
- `review_trainer_events.study_session_id` ma `nullOnDelete`, wiec eventy moga zostac, ale straca link do sesji.
- `review_memory_progress` ma byc aktualnym stanem i nie moze zalezec od przezycia raw session history.

Decyzja:

- Jesli dodamy dzienne agregaty trenera, musza byc materializowane przed prune.
- `review_memory_progress` musi byc zasilane przy zapisie odpowiedzi, nie rekonstruowane dopiero z raw sesji po czasie.
- Eventy answer-level powinny miec wlasne identyfikatory i nie polegac tylko na `study_session_id`, bo po prune link moze zniknac.
- Porzucone sesje `sr_review` nie moga trzymac "zarezerwowanych" pytan w planie dnia na stale.

Plan UI i plan backendu moga sie rozjechac:

- `/trener-pamieci` liczy plan i ustawia `question_count` w formularzu.
- `StudySessionManager::start()` przy POST liczy plan ponownie.
- To jest dobre, bo backend jest zrodlem prawdy.
- Ale przy polityce 50/80 user moze miec otwarta stara karte, druga sesje albo kilka szybkich klikniec.

Decyzja:

- Backend nie moze ufac `question_count` z klienta jako stanowi planu.
- Start `sr_review` powinien zwracac albo zapisywac faktyczny:
  - `plan_date`,
  - `planner_version`,
  - `daily_target_count`,
  - `completed_today_count` w chwili startu,
  - `selected_question_count`.
- UI po starcie i po wyniku musi czytac stan z backendu, nie zakladac, ze klikniety plan nadal jest aktualny.
- Dzienny budzet nie powinien byc "rezerwowany" przez samo utworzenie sesji; licza sie dopiero odpowiedzi.

Cache moze ukryc szybkie zmiany:

- `CategoryAnalyticsService` ma cache 30 sekund per user/category.
- `ReviewPlannerService` dzisiaj nie ma cache, co jest bezpieczne dla MVP.
- Po dodaniu daily planu kuszace bedzie cache'owanie planu, ale po kazdej odpowiedzi plan moze sie zmienic.

Decyzja:

- Plan trenera na MVP zostaje bez dlugiego cache.
- Jesli dodamy cache, klucz musi zawierac:
  - user id,
  - category id/scope,
  - `review_day`,
  - `planner_version`/`policy_version`.
- Cache musi miec krotki TTL albo byc invalidowany po zaakceptowanej odpowiedzi `sr_review`.
- Metryki dashboardu moga byc cache'owane, ale CTA trenera i licznik dzienny powinny byc swieze.

Zmiana dostepu i kategorii:

- Zwykly kursant z zakupionym dostepem nadal jest przypisany do jednej kategorii.
- Wszystkie kategorie moga uzywac tylko konta systemowe: admin, moderator, test account.
- Admin moze zmienic userowi `target_category_id`.
- Dane z poprzedniej kategorii pozostana w `user_question_progress`, sesjach i przyszlym `review_memory_progress`, ale powinny byc ukryte przez aktywny zakres kategorii.
- Aktywna sesja utworzona przed zmiana kategorii moze nadal istniec.

Decyzja:

- Planner trenera zawsze filtruje po aktualnie dozwolonych kategoriach.
- `review_memory_progress` nie kasujemy przy samej zmianie kategorii; kasujemy przy jawnym resecie danych nauki.
- Przy zmianie kategorii przez admina trzeba podjac decyzje:
  - albo zamknac/porzucic aktywne sesje spoza nowej kategorii,
  - albo pozwolic je obejrzec jako historyczny wynik, ale nie kontynuowac jako aktywny plan.
- `reviewDueCount`, dashboard i trener musza miec ten sam allowed category scope.

Wygaśniecie dostepu w trakcie sesji:

- `study.session.access` pozwala kontynuowac bez pelnego produktu tylko dla `mode = pjm`.
- Dla `sr_review` wygasniety dostep oznacza blokade kontynuacji.
- Odpowiedzi zapisane przed wygasnieciem dostepu zostaja w historii.

Decyzja:

- To jest akceptowalne, ale UI po reaktywacji dostepu musi przeliczyc plan od nowa.
- Nie wolno zakladac, ze nieukonczona sesja sprzed wygasniecia nadal jest dobrym planem dnia.

Nowe decyzje po osmym preflight:

1. `review_day` jest jawna data w app timezone i musi byc wspolna dla planu, progresu i UI.
2. Cel 80 liczymy z zaakceptowanych odpowiedzi `sr_review` w answer-level ledger albo dziennym agregacie, nie ze startu ani zakonczenia sesji.
3. `review_memory_progress` aktualizujemy przy odpowiedzi, bo raw answers moga zniknac przez prune.
4. Plan trenera nie powinien miec dlugiego cache bez invalidacji po odpowiedzi.
5. Backend recompute planu przy starcie jest poprawny; klient nie jest zrodlem prawdy dla `question_count`.
6. Zmiana kategorii/admin lifecycle wymaga decyzji dla aktywnych sesji spoza nowego scope.
7. Po wygasnieciu i reaktywacji dostepu plan trenera ma byc liczony od nowa.

##### Dziewiaty preflight - concurrency, stale plany i licznik dzienny

Ten przebieg sprawdzil ryzyka, ktore zwykle pojawiaja sie dopiero przy realnym uzyciu: dwie karty, podwojny start, sesja zaczeta poprzedniego dnia oraz wydajnosc liczenia celu 80.

Start sesji nie jest dzisiaj serializowany per user:

- `StudySessionManager::start()` najpierw liczy plan i wybiera `question_ids`, a dopiero potem w transakcji zamyka poprzednie `in_progress` i tworzy nowa sesje.
- Brakuje `lockForUpdate()` na wspolnym rekordzie wlasciciela sesji i brakuje unikalnego ograniczenia "jedna aktywna sesja per user".
- Formularz `ReviewQueue/Index.vue` ma `reviewForm.processing`, ale to chroni tylko jedna karte UI.
- API, dwie karty, retry przegladarki albo szybkie requesty moga uruchomic dwa starty prawie rownoczesnie.
- W takim scenariuszu oba requesty moga policzyc podobny plan, a przy braku aktywnej sesji oba moga utworzyc wlasna sesje `in_progress`.

Decyzja:

- Start sesji `sr_review` musi byc serializowany po userze.
- Najbezpieczniejszy MVP:
  - w transakcji zablokowac rekord usera przez `lockForUpdate()` albo uzyc krotkiego `Cache::lock("study-session-start:{user_id}")`,
  - dopiero pod tym lockiem zamknac stare sesje i utworzyc nowa,
  - dodac test double-start/multi-tab.
- Unikalny partial index na aktywna sesje bylby mocny w PostgreSQL, ale jest mniej przenosny do SQLite testow, wiec nie powinien byc pierwszym krokiem bez osobnej decyzji.
- Jesli wykryjemy aktywna sesje `sr_review` utworzona kilka sekund temu dla tego samego `review_day` i tej samej polityki, backend powinien ja zwrocic albo jawnie zamknac jako `abandoned/replaced`; nie powinien cicho tworzyc drugiego rownoleglego planu.

Stara aktywna sesja z poprzedniego dnia moze wracac jako aktualna:

- `activeSessionForUser()` zwraca najnowsza sesje `in_progress` niezaleznie od trybu i daty planu.
- `ops:prune-study-history` usuwa porzucone `in_progress` dopiero po domyslnie 7 dniach.
- `payload.review_plan` ma dzisiaj `planner_version`, `due_count`, `recommended_question_count`, `selected_question_count` i `estimated_duration_seconds`, ale nie ma `review_day`/`plan_date`.
- User moze zaczac sesje wieczorem, wrocic nastepnego dnia i nadal odpowiadac na plan policzony wczoraj.

Decyzja:

- Payload i eventy `sr_review` powinny zawierac:
  - `plan_date`/`review_day`,
  - `daily_target_count`,
  - `completed_today_count` w chwili startu,
  - `policy_version`.
- Polityka stalej sesji:
  - nieodpowiedziana stara sesja `sr_review` moze byc porzucona i przeliczona od nowa,
  - czesciowo odpowiedziana stara sesja moze byc dokonczona, ale odpowiedzi liczymy do dnia faktycznej odpowiedzi,
  - event completion powinien rozroznic `started_review_day` i `completed_review_day`.
- Wynik sesji nie powinien udawac, ze wczorajszy plan nadal jest dzisiejszym planem dnia.

`review_memory_progress` jest stanem, nie dziennikiem:

- Proponowana tabela ma `unique(user_id, question_id)`, wiec opisuje aktualny stan jednego pytania u jednego usera.
- Jesli pytanie zostanie przetworzone drugi raz, rekord stanu zostanie zaktualizowany, a nie powstanie osobny dzienny rekord.
- To jest dobre dla "jaki jest aktualny stan pamieci?", ale za malo dla "ile zaakceptowanych prob user zrobil dzisiaj?".
- Same `verified_attempts_count` sa licznikami zyciowymi i bez osobnego dziennego sladu nie powiedza, ile wydarzylo sie konkretnego dnia.
- Raw `study_session_answers` moga zniknac przez prune historii, wiec nie moga byc jedynym dlugoterminowym zrodlem dziennych raportow.

Decyzja:

- `review_memory_progress` zostaje tabela stanu verified memory.
- Dzienny target 80 wymaga dodatkowego trwalego zrodla:
  - answer-level eventow dla odpowiedzi `sr_review`, albo
  - materializowanej tabeli `review_daily_progress`/`review_daily_user_stats`.
- Rekomendowany MVP: przy kazdej zaakceptowanej odpowiedzi `sr_review` idempotentnie zapisac:
  - `review_day`,
  - `study_session_answer_id`,
  - `user_id`,
  - `license_category_id`,
  - `question_id`,
  - `answer_kind`,
  - `result`,
  - `policy_version`.
- Dzienny cel moze pokazywac dwie metryki:
  - `accepted_answer_count` jako glowny postep do 80,
  - `unique_question_count` jako kontrola jakosci, zeby ten sam problem nie sztucznie pompowal celu.

Indeksy pod cel dzienny i plan 50/80:

- Obecne indeksy `study_session_answers` obejmuja `question_id + answered_at`, `question_id + created_at` oraz `study_session_id + created_at`.
- Brakuje indeksu `study_session_id + answered_at`.
- `study_sessions` ma `user_id + status`, `license_category_id + mode`, `user_id + created_at`, ale nie ma indeksu pod typowe zapytanie: user + mode `sr_review` + category + dzien odpowiedzi przez join do answers.
- `ReviewPlannerService::orderedProgress()` pobiera wszystkie due rekordy do pamieci i sortuje w PHP, co przy planie 50/80 jest akceptowalne na MVP, ale przy boosterach i pelnej kategorii wymaga limitow SQL albo materializowanego planu.

Decyzja:

- Jesli dzienny licznik bedzie liczony po raw answers, trzeba dodac indeksy:
  - `study_session_answers(study_session_id, answered_at)`,
  - `study_sessions(user_id, mode, license_category_id, started_at)` albo analogiczny indeks dopasowany do finalnego zapytania.
- Jesli dodamy answer-level ledger/daily stats, to on powinien miec podstawowy indeks:
  - `user_id, license_category_id, review_day`,
  - plus unikalnosc/idempotencje po `study_session_answer_id`.
- UI nie powinno liczyc dziennego postepu przez `new Date()` w przegladarce; backend ma zwracac gotowy `review_day`, `completed_today_count` i `daily_target_count`.

Nowe decyzje po dziewiatym preflight:

1. Start `sr_review` trzeba serializowac per user; UI processing nie wystarczy.
2. Payload i telemetry planu musza miec `review_day`/`plan_date`.
3. Stara sesja `sr_review` wymaga jawnej polityki: refresh, kontynuacja czesciowa albo abandonment.
4. `review_memory_progress` nie jest dziennikiem dziennego targetu, tylko stanem pytania.
5. Dzienny target 80 potrzebuje answer-level ledger albo materializowanego dziennego agregatu.
6. Indeksy trzeba projektowac pod faktyczny ksztalt zapytan, zanim wlaczymy 50/80 na szersza skale.

##### Dziesiaty preflight - granice modulu, reveal odpowiedzi i snapshot wyniku

Ten przebieg sprawdzil, czy `Trener pamieci` jest juz naprawde osobnym modulem, czy nadal dziedziczy zbyt duzo zachowania klasycznej nauki.

Aktywna sesja jest globalna, nie per modul:

- `StudySessionManager::start()` zamyka wszystkie poprzednie sesje `in_progress` danego usera, bez wzgledu na tryb.
- `activeSessionForUser()` zwraca najnowsza aktywna sesje `in_progress`, tez bez rozroznienia trybu.
- Oznacza to, ze start `sr_review` zamknie aktywna sesje `learn`, `pjm`, `quick` albo `exam`; start klasycznej nauki zamknie aktywny `sr_review`.
- To jest zgodne z obecnym modelem "jedna aktywna sesja nauki", ale nie jest zgodne z intuicja, ze `Trener pamieci` jest calkiem osobnym modulem.

Decyzja:

- Musimy jawnie zdecydowac, czy zostajemy przy jednej aktywnej sesji na usera.
- Najbezpieczniejszy MVP to zostawic jeden aktywny slot, ale dodac:
  - `replaced_by_session_id` albo event `review.abandoned/replaced` dla przerwanej sesji trenera,
  - jasne copy/metryke, ze stara sesja zostala zastapiona nowa,
  - test, ze start klasycznej nauki nie tworzy podwojnego aktywnego `sr_review`.
- Jesli produktowo chcemy pelna niezaleznosc modulow, potrzebna jest wieksza zmiana: aktywna sesja per `mode` albo per `module_key`, a nie tylko per user.

`sr_review` dziedziczy client-side reveal z lokalnej nauki:

- `StudySessionController::isInteractiveLearningMode()` zwraca true dla `learn`, `pjm` i `sr_review`.
- Przy `isLearnInProgress` backend przekazuje `correct_answer` do `currentQuestion`, `questionPool`, `prefetchedQuestions`, `currentQuestionData` i batch data.
- Testy obecnie potwierdzaja, ze aktywna sesja `sr_review` ma w Inertia props:
  - `currentQuestion.correct_answer`,
  - `questionPool.*.correct_answer`.
- `transformQuestion()` zawsze przekazuje tez `explanation`, a przy trybach interaktywnych przekazuje `explanation_asset` i `explanation_annotations`.
- Frontend moze tego nie pokazywac przed odpowiedzia, ale dane sa juz w payloadzie klienta.

Decyzja:

- Dla klasycznej nauki mozemy zostawic obecny szybki lokalny flow.
- Dla `Trenera pamieci` jako modulu weryfikacji nie powinnismy wysylac przed odpowiedzia:
  - `correct_answer`,
  - `explanation`,
  - `explanation_asset`,
  - `explanation_annotations`, jesli moga zdradzac rozwiazanie.
- `sr_review` powinien miec server-authoritative reveal:
  - przed odpowiedzia klient dostaje tylko pytanie, opcje i media pytania,
  - po odpowiedzi backend zwraca `is_correct`, poprawna odpowiedz, wyjasnienie i stan memory,
  - lokalny optimistic flow moze zostac, ale nie moze potrzebowac `correct_answer` z payloadu startowego.
- To nie jest kwestia anty-cheatu; chodzi o czystosc sygnalu `verified memory`. Jesli odpowiedz jest w payloadzie przed kliknieciem, przypadkowo lub celowo mozna podbic metryke bez realnego odzyskania z pamieci.

`/trener-pamieci` nadal buduje preview pytan:

- `ReviewQueueController` zwraca `questions` oparte o `preview_question_ids`.
- Dla tych rekordow laduje `question.media`, `question.licenseCategory` i `question.questionTopic`.
- UI `/trener-pamieci` ma byc ekranem misji dnia, nie pionowa lista pytan.
- Przy planie 50/80 taki preview payload moze niepotrzebnie obciazac wejscie do trenera i API `/api/v1/me/review-queue`.

Decyzja:

- Domyslny kontrakt `/trener-pamieci` powinien byc plan-first:
  - daily ring,
  - CTA,
  - liczniki segmentow,
  - scope/kategoria,
  - bez promptow i mediow pytan.
- Preview pytan moze zostac tylko jako opcjonalny debug/admin/mobile endpoint, jesli bedzie realnie potrzebny.
- API `me/review-queue` powinno miec wersjonowany kontrakt: `plan` domyslnie, `questions` tylko na jawne zadanie albo osobnej trasie.

Podsumowanie `sr_review` nie jest snapshotem historycznym:

- `ReviewTrainerCompletionSummaryService` przelicza stany z aktualnego `user_question_progress`.
- Uzywa `today()`, wiec etykieta kolejnej powtorki i memory state moga sie zmieniac z dnia na dzien.
- Jesli user po sesji zrobi kolejne odpowiedzi, stary ekran wyniku moze pokazac inne recovery/stable counts niz w momencie zakonczenia.
- `review.completed` zapisuje tylko ogolne liczby sesji, bez snapshotu memory state i daily target.

Decyzja:

- Wynik sesji powinien miec dwa rozdzielone pojecia:
  - snapshot sesji w chwili zakonczenia,
  - aktualny nastepny krok liczony na teraz.
- Snapshot powinien trafic do `review.completed` albo osobnego answer-level/daily ledgeru:
  - `review_day`,
  - `answered_count`,
  - `unknown_count`,
  - `correct_count`,
  - `needs_recovery_count`,
  - `stable_count`,
  - `daily_completed_count_after_session`.
- Ekran wyniku moze nadal pokazywac aktualny CTA, ale copy musi jasno mowic, czy patrzymy na "ta sesje" czy "stan na teraz".

API sesji nie jest jeszcze kontraktem premium trenera:

- API `POST /api/v1/sessions` dopuszcza alias `mode = review`, ktory backend normalizuje do `sr_review`.
- Po starcie API zwraca `data.questions`, czyli cala liste pytan sesji.
- `StudySessionApiPayloadBuilder` nie ma osobnego payloadu dla `sr_review`, nie zwraca `answer_kind`, `review_day`, daily targetu ani memory progressu.
- API nie wysyla `correct_answer` przed zakonczeniem, ale wysyla materialy wyjasniajace przez `explanation_asset`/`explanation_annotations`.

Decyzja:

- Web i API musza dostac wspolny, mode-aware kontrakt `sr_review`.
- Przy 50/80 API nie powinno musiec zwracac wszystkich pytan naraz; lepszy jest current question + window albo batch endpoint bez reveal.
- Alias `review` mozna utrzymac dla kompatybilnosci, ale dokumentacja i nowe testy powinny uzywac kanonicznego `sr_review`.
- Payload API trenera musi zawierac te same pola domenowe co web: `review_day`, `daily_target_count`, `completed_today_count`, `answer_kind` i wynik memory update po odpowiedzi.

Nowe decyzje po dziesiatym preflight:

1. Musimy zdecydowac, czy aktywna sesja jest globalna per user, czy per modul; MVP moze zostac globalny, ale z eventem `abandoned/replaced`.
2. `sr_review` nie powinien dostawac `correct_answer` ani wyjasnien przed odpowiedzia, jesli ma byc weryfikacja pamieci.
3. Lokalny flow trenera powinien przejsc na backendowy reveal po odpowiedzi.
4. `/trener-pamieci` powinien byc plan-first i nie powinien domyslnie wysylac preview pytan z mediami.
5. Wynik `sr_review` potrzebuje snapshotu sesji, inaczej stary wynik zmienia sie razem z aktualnym progresem.
6. API trenera wymaga osobnego, mode-aware kontraktu przed wlaczeniem 50/80 i `Nie wiem`.

##### Jedenasty preflight - current_index, czesciowe zakonczenia i wynik 50/80

Ten przebieg sprawdzil, czy mechanika pojedynczej sesji dobrze zniesie dluzsze batch'e 50/80 i backendowy reveal.

`current_index` nie oznacza kompletu poprzednich odpowiedzi:

- `StudySessionManager::allowsFlexibleAnswerOrder()` zwraca true dla `learn`, `pjm` i `sr_review`.
- Przy elastycznej kolejnosci `recordAnswer()` ustawia:
  - `current_index = max(current_index, questionIndex + 1)`.
- To oznacza "najdalej odwiedzona pozycja", a nie "pierwsze nieodpowiedziane pytanie".
- Jesli klient albo API odpowie na ostatnie pytanie przed wczesniejszymi, `current_index` moze wskazac koniec listy przy `answered_count < total_questions_count`.
- `currentQuestionId()` wtedy zwroci null, a `StudySessionController::renderSessionPage()` traktuje `currentQuestion === null` jako `isCompleted = true`, nawet jesli status sesji nadal jest `in_progress`.
- Obecne testy pokrywaja odpowiedz poza kolejnoscia w srodku sesji, ale nie pokrywaja odpowiedzi na ostatnie pytanie jako pierwszej.

Decyzja:

- Dla `sr_review` nie powinnismy opierac kolejnego kroku na `current_index` jako jedynym zrodle prawdy.
- Backend powinien umiec wyznaczyc `next_unanswered_question_id` po faktycznych odpowiedziach w bazie.
- Przy server-authoritative reveal odpowiedz powinna zwracac:
  - zapisany `study_session_answer_id`,
  - aktualny licznik odpowiedzi,
  - `next_question_id` albo informację, ze batch jest kompletny,
  - snapshot wyniku odpowiedzi.
- Test musi sprawdzic przypadek: user odpowiada na ostatnie pytanie jako pierwsze i nadal dostaje nastepne nieodpowiedziane pytanie zamiast pustego ekranu/pozornego wyniku.

`answered_count` w payloadzie nie powinien byc autorytatywny:

- `StudySessionManager::answeredCount()` bierze `max(DB answers count, payload.answered_count)`.
- `recordAnswer()` uzywa `payload.answered_count` do wyliczenia nowego `answeredCount` i decyzji o `completed`.
- W normalnym flow to dziala, ale po retry, starym payloadzie albo przyszlym refaktorze latwo o rozjazd.
- Przy trybie premium dzienny target i status sesji powinny opierac sie o unikalne zapisane odpowiedzi, nie o licznik w JSON payload.

Decyzja:

- `payload.answered_count` moze zostac cache'em UI, ale nie zrodlem prawdy dla:
  - zakonczenia sesji,
  - daily targetu,
  - memory progressu,
  - eventow answer-level.
- Dla `sr_review` completion powinno bazowac na liczbie unikalnych `study_session_answers` albo answer-level ledgerze.

Reczne zakonczenie sesji jest dzisiaj "completed", nawet gdy sesja jest niepelna:

- W UI aktywnej sesji istnieje przycisk `Konczę naukę`.
- `complete()` z `forceComplete = true` zamyka sesje jako `completed`, nawet przy `answered_count < total_questions_count`.
- `review.completed` jest wtedy logowany idempotentnie, ale oznacza domkniecie procesu, nie wykonanie calego planu.
- `syncProgress()` nie aktualizuje jednoznacznie `payload.current_index` i `payload.answered_count` do stanu koncowego partial session.

Decyzja:

- Dla `sr_review` trzeba rozroznic statusy/copy:
  - `completed_all` - wykonano caly batch,
  - `finished_partial` - user zakonczyl na teraz,
  - `abandoned/replaced` - sesja zostala zastapiona lub porzucona.
- Event `review.completed` moze zostac dla kompatybilnosci, ale telemetry premium powinno miec `completion_type`.
- Daily target liczy odpowiedzi, nie fakt klikniecia "zakoncz".

Wynik sesji 50/80 nie powinien byc lista 80 kart:

- `StudySessions/Show.vue` buduje `displayAnsweredResults`.
- Sekcja wyniku pokazuje:
  - jesli sa bledy: tylko `incorrectAnsweredResults`,
  - jesli nie ma bledow: wszystkie `displayAnsweredResults`.
- Dla sesji 80/80 bez bledow oznacza to potencjalnie 80 kart z pytaniami, mediami, odpowiedziami i wyjasnieniami.
- Backend przy zakonczonym wyniku laduje dla wszystkich pytan `media`, `questionTopic`, `referenceExplanationAsset`, `explanationAnnotations`, `activeSignLanguageAssets`.
- To jest sprzeczne z kierunkiem UX trenera: duze ikony, malo tekstu, jasny plan i brak kolejnej listy pytan.

Decyzja:

- Wynik `sr_review` powinien byc summary-first:
  - dzienny postep,
  - ile potwierdzone,
  - ile `Nie wiem`,
  - ile do odzyskania,
  - nastepny krok.
- Lista pytan powinna byc ukryta za akcja "Przejrzyj pytania" i ladowana leniwie.
- Domyslnie pokazujemy tylko material do odzyskania: `unknown`, bledne, stale problematyczne.
- Przy sesji bez bledow nie pokazujemy automatycznie 50/80 kart poprawnych odpowiedzi.

Windowing 50/80 musi byc server-aware:

- Dla sesji powyzej `LEARN_FULL_POOL_THRESHOLD = 24` backend przechodzi na `questionPoolMode = windowed`.
- Frontend pobiera kolejne pytania przez endpoint batch z limitem `max:20`.
- To jest dobre dla wydajnosci, ale obecnie klient wybiera ID pytan do pobrania na bazie `questionIds`.
- Po odcieciu pre-answer reveal i przejsciu na backendowy reveal warto ograniczyc mozliwosc pobierania dowolnych przyszlych pytan z odpowiedziami/wyjasnieniami.

Decyzja:

- Dla `sr_review` backend powinien zwracac nastepne pytanie albo male okno bez correct/reveal, a nie pozwalac klientowi dowolnie skladac batch po `questionIds`.
- Po odpowiedzi backend moze zwrocic nastepne `next_question` i reveal poprzedniego pytania w jednym response.
- To uprości multi-tab, refresh i mobile/API.

Nowe decyzje po jedenastym preflight:

1. `current_index` nie jest wystarczajacy dla `sr_review`; potrzebujemy `next_unanswered_question_id` liczony po zapisanych odpowiedziach.
2. `payload.answered_count` zostaje pomocniczy, ale nie moze sterowac completion, daily targetem ani memory progress.
3. `sr_review` potrzebuje rozroznienia `completed_all`, `finished_partial` i `abandoned/replaced`.
4. Wynik 50/80 ma byc summary-first, a lista pytan leniwa i domyslnie ograniczona do materialu do odzyskania.
5. Windowing trenera powinien byc server-aware, z backendowym wyborem nastepnego pytania i reveal dopiero po odpowiedzi.

##### Dwunasty preflight - wplyw `unknown` na klasyczny SRS, PJM i statystyki

Ten przebieg sprawdzil, co stanie sie, jesli `Nie wiem` potraktujemy technicznie jak zwykla bledna odpowiedz.

`QuestionProgressManager` nie zna intencji odpowiedzi:

- Przy kazdym `is_correct = false` nadaje `quality = 1`.
- Resetuje `repetitions` do 0.
- Ustawia `interval_days = 1`.
- Ustawia `next_review_at` na dzisiaj.
- Zwieksza `total_attempts` i `incorrect_count`.
- Zeruje `correct_streak`.
- Nie rozroznia:
  - blednego strzalu,
  - uczciwego `Nie wiem`,
  - timeoutu,
  - odpowiedzi z klasycznej nauki,
  - odpowiedzi z trenera pamieci.

Wniosek:

- Jesli `unknown` trafi bezposrednio do wspolnego `QuestionProgressManager`, to klasyczny SRS potraktuje go jak zwykly blad.
- To moze byc za mocne, bo `Nie wiem` jest sygnalem "nie odzyskalem z pamieci", a nie "wybralem zla odpowiedz".
- To moze tez rozjechac zalozenie, ze `Trener pamieci` jest osobnym modulem weryfikujacym, a nie kolejnym miejscem mieszajacym ogolny progres.

Efekt domina na inne moduly:

- `ReviewMemorySignalService` liczy `memory_state`, `leech_score` i `stability_score` z `user_question_progress`.
- `HardQuestionService` traktuje pytanie jako trudne, gdy:
  - `last_quality <= 2`,
  - `incorrect_count > correct_count`,
  - albo accuracy spada ponizej 60%.
- `UserReadinessService` liczy gotowosc z `user_question_progress`.
- `DashboardMetricsService` pokazuje `ready_for_review_count`, `hard_questions_count`, `readiness_score` i `answered_today` na podstawie wspolnych danych.
- `StudyTopicGroupsService` uzywa `QuestionProgressManager::progressBucketFromSnapshot()` do dzialow klasycznej nauki.
- `PjmQuestionProgressService` uzywa tego samego bucketowania dla modulu PJM.
- `CategoryAnalyticsService` i `QuestionDailyStatsAggregator` licza odpowiedzi ze `study_session_answers`.
- `PublicQuestionDifficultyService` moze potraktowac `unknown` jako blad w publicznym rankingu najtrudniejszych pytan.

Najwazniejsze ryzyko:

- Jedno klikniecie `Nie wiem` w trenerze moze:
  - obnizyc klasyczna gotowosc do egzaminu,
  - wrzucic pytanie do klasycznych "trudnych pytan",
  - zmienic progres dzialow w `/nauka`,
  - zmienic progres PJM, jesli pytanie ma film PJM,
  - wejsc do publicznych/adminowych statystyk jako blad,
  - podbic ranking trudnosci pytania.

To nie zawsze byloby bledem, ale musi byc swiadoma polityka, nie efekt uboczny.

`ReviewMemorySignalService` nie powinien byc finalnym silnikiem premium bez zmiany zrodla danych:

- Dzis jego nazwa brzmi jak docelowy memory engine.
- W praktyce czyta wspolny `user_question_progress`, czyli miks:
  - klasycznej nauki,
  - PJM,
  - Zen,
  - egzaminu,
  - hard/quick,
  - `sr_review`.
- Dla baseline v1 to wystarcza.
- Dla premium `verified memory` leech/stability powinny byc liczone z `review_memory_progress` albo z answer-level ledgeru `sr_review`.

Decyzja:

- Obecny `ReviewMemorySignalService` zostaje narzedziem baseline planera v1.
- Nie traktujemy go jako finalnego zrodla prawdy o zweryfikowanej pamieci.
- Przed leech policy premium dodajemy osobny signal/scoring oparty o `review_memory_progress`.

`HardQuestionService` wymaga osobnej decyzji produktowej:

- Jesli `unknown` bedzie mirrorowane do `user_question_progress`, pytania "nie odzyskane" zaczna pojawiac sie w klasycznych trudnych pytaniach.
- To moze pomagac, jesli hard mode ma byc remediacja.
- Ale moze tez mieszac intencje, bo user kliknal `Nie wiem` w module weryfikacji, a nie popelnil blad w klasycznej nauce.

Decyzja MVP:

- `unknown` zapisujemy przede wszystkim w `review_memory_progress`.
- Nie mirrorujemy `unknown` do klasycznego `incorrect_count`, dopoki nie zatwierdzimy polityki "hard mode korzysta z memory trainer".
- Jesli w przyszlosci mirrorujemy, to jawnie:
  - z `source_mode = sr_review`,
  - z osobna metryka `unknown_count`,
  - z testami dashboardu, PJM i hard mode.

Czas odpowiedzi jest drugim ukrytym mieszaczem:

- `QuestionProgressManager::qualityScore()` daje poprawnej odpowiedzi:
  - `5` do 7 sekund,
  - `4` do 15 sekund albo bez czasu,
  - `3` powyzej 15 sekund.
- User ustalil, ze `Trener pamieci` nie ma byc trybem na czas.
- Jesli `sr_review` dalej bedzie przekazywal realny `response_time_ms` do klasycznego SRS, to szybkie/wolne odpowiedzi nadal beda ukrycie zmieniac quality.
- `PublicQuestionDifficultyService` i agregaty dzienne tez czytaja czasy odpowiedzi ze wszystkich sesji.

Decyzja:

- `review_memory_progress` nie karze za czas odpowiedzi.
- W `sr_review` czas odpowiedzi moze byc zapisany analitycznie, ale nie moze byc glownym sygnalem pamieci.
- Przed wlaczeniem trenera 50/80 trzeba zdecydowac, czy:
  - dla klasycznego `QuestionProgressManager` w `sr_review` przekazujemy `null`,
  - czy calkiem odcinamy aktualizacje `user_question_progress` dla `unknown`,
  - czy wprowadzamy mode-aware policy dla `choice` i `unknown`.

Potrzebujemy macierzy "answer impact policy":

| Tryb | `answer_kind` | `study_session_answers` | `user_question_progress` | `review_memory_progress` | public/admin stats |
| --- | --- | --- | --- | --- | --- |
| `learn` / `pjm` / `zen` | `choice` | tak | tak | nie | tak, klasycznie |
| `exam` | `choice` / `timeout` | tak | wedlug obecnego zachowania egzaminu | nie | tak, ale timeout osobno |
| `sr_review` | `choice` | tak | do decyzji: legacy mirror albo brak | tak | osobne metryki memory |
| `sr_review` | `unknown` | tak | domyslnie nie | tak | nie jako zwykly blad |
| `sr_review` | `skipped` | tak albo event-only | nie | zalezy od polityki | nie jako zwykly blad |

Ta macierz musi byc zatwierdzona przed implementacja `Nie wiem`.

Nowe decyzje po dwunastym preflight:

1. `unknown` nie moze domyslnie aktualizowac klasycznego `incorrect_count`.
2. `review_memory_progress` jest pierwszym miejscem zapisu `unknown` i zweryfikowanej pamieci.
3. `ReviewMemorySignalService` zostaje baseline v1, ale finalny premium signal musi czytac mode-aware dane.
4. `HardQuestionService`, PJM progress, topic progress, dashboard readiness i publiczne statystyki musza miec jawna polityke dla `sr_review`.
5. Czas odpowiedzi w trenerze ma byc analityczny, nie karzacy.
6. Przed kodowaniem `Nie wiem` trzeba zatwierdzic i przetestowac macierz `answer impact policy`.

### Faza 5 - Policy engine i eksperymenty

Cel:

- skalowac i optymalizowac algorytm bez recznych rewritow.

Zakres:

- `algorithm_version`
- `policy snapshot`
- rollout cohorts
- A/B testy polityk review
- porownanie retencji per policy

Definition of Done:

- mozemy bezpiecznie zmieniac algorytm i mierzyc, czy nowa wersja jest lepsza.

## 13. Model danych v2

### 13.1 Rozszerzenie `user_question_progress`

Status po audycie:

- nie jest to pierwszy rekomendowany krok dla `verified memory`,
- tabela pozostaje kompatybilnym progressem ogolnej nauki,
- pola ponizej sa nadal mozliwe w przyszlosci, ale nie powinny byc uzyte do natychmiastowego rozdzielenia ekspozycji i zweryfikowanej pamieci.

Planowane pola:

- `memory_state`
- `lapses_count`
- `leech_score`
- `stability_score`
- `difficulty_score`
- `last_review_mode`
- `last_algorithm_version`

### 13.2 Nowa tabela `review_events`

MVP v2 pola:

- `id`
- `user_id`
- `question_id`
- `study_session_id`
- `study_session_answer_id`
- `mode`
- `answer_kind`
- `selected_answer`
- `is_correct`
- `response_time_ms`
- `quality_score`
- `algorithm_version`
- `old_snapshot_json`
- `new_snapshot_json`
- `reviewed_at`

### 13.3 Nowa tabela `review_daily_plans`

Pola:

- `id`
- `user_id`
- `license_category_id`
- `algorithm_version`
- `planned_for_date`
- `target_question_count`
- `estimated_duration_seconds`
- `due_count_at_plan_time`
- `plan_payload_json`
- `started_at`
- `completed_at`

Ta tabela bedzie potrzebna, jesli chcemy wejsc w premium dzienne plany i raportowanie.

### 13.4 Nowa tabela `review_memory_progress`

To jest rekomendowana pierwsza tabela dla `verified memory`.

Pola MVP:

- `id`
- `user_id`
- `question_id`
- `license_category_id`
- `verified_attempts_count`
- `verified_correct_count`
- `verified_unknown_count`
- `verified_incorrect_count`
- `verified_correct_streak`
- `last_verified_result`
- `last_verified_at`
- `last_study_session_answer_id`
- `next_verified_review_at`
- `verified_memory_state`
- `source_policy_version`
- `created_at`
- `updated_at`

Indeksy:

- `unique(user_id, question_id)`
- `index(user_id, license_category_id, next_verified_review_at)`
- `index(user_id, verified_memory_state)`
- `index(last_study_session_answer_id)`
- `index(source_policy_version)`

Relacje i cleanup:

- `user_id` powinno usuwac rekordy po usunieciu usera,
- `question_id` powinno usuwac rekordy po usunieciu pytania,
- `license_category_id` jest denormalizacja do szybkich zapytan po kategorii,
- `last_study_session_answer_id` pozwala wykryc, czy ta sama odpowiedz nie jest przetwarzana drugi raz,
- reset danych nauki w adminie musi usuwac `review_memory_progress`,
- prune starej historii sesji nie powinien usuwac `review_memory_progress`.

Zasada:

- aktualizujemy ja tylko przez `Trener pamieci`,
- klasyczna nauka, Zen, PJM i egzamin nie podnosza bezposrednio `verified_memory_state`,
- inne tryby moga zasilac kandydatow przez `user_question_progress` i `study_session_answers`,
- liczniki zyciowe tej tabeli powinny byc `unsignedInteger`, nie `unsignedSmallInteger`.

Granica odpowiedzialnosci:

- `review_memory_progress` jest tabela stanu per `user_id + question_id`.
- Nie jest pelnym dziennikiem dziennych odpowiedzi, bo kolejna odpowiedz na to samo pytanie aktualizuje ten sam rekord.
- Dzienny target 80 powinien czytac answer-level ledger albo dzienny agregat, a `review_memory_progress` powinien sluzyc do planowania, stanu pamieci i nastepnego terminu powtorki.
- Jesli raw `study_session_answers` zostana wyczyszczone przez prune, dzienne raporty trenera musza nadal miec swoje trwale zrodlo.

Status implementacyjny:

- tabela `review_memory_progress` zostala dodana jako addytywna migracja,
- dodano model `ReviewMemoryProgress` i `ReviewMemoryProgressService`,
- serwis aktualizuje stan tylko dla `mode = sr_review`,
- zwykla nauka, Zen, PJM i egzamin nie tworza `review_memory_progress`,
- klasyczny `user_question_progress` nadal jest aktualizowany dla nowej odpowiedzi, ale retry/double POST tej samej odpowiedzi nie przelicza go drugi raz,
- `answer_kind = unknown` w `sr_review` zapisuje `review_memory_progress`, ale nie aktualizuje klasycznego `user_question_progress`,
- `answer_kind = unknown` poza `sr_review` jest blokowane w backendzie,
- `review_memory_progress.last_study_session_answer_id` chroni przed ponownym przetworzeniem tej samej odpowiedzi,
- adminowy reset danych nauki usuwa `review_memory_progress`,
- `ops:copy-sqlite-to-pgsql` ma dopisane `review_trainer_events` i `review_memory_progress`.

### 13.5 Rozszerzenie `study_session_answers`

Potrzebujemy jawnie rozroznic typ odpowiedzi.

Planowane pole:

- `answer_kind`

Wartosci:

- `choice`
- `unknown`
- `timeout`
- `skipped`

Domyslna wartosc:

- `choice`

Zasady kompatybilnosci:

- stare rekordy bez `answer_kind` sa interpretowane jako `choice`,
- `selected_answer = null` bez `answer_kind` nie moze oznaczac `Nie wiem`,
- `unknown` jest dozwolone tylko dla `sr_review`,
- `timeout` zostaje domena egzaminu.

Status implementacyjny fundamentu:

- dodano addytywna kolumne `study_session_answers.answer_kind` z domyslnym `choice`,
- dodano stale domenowe `choice`, `unknown`, `timeout`, `skipped`,
- klasyczne odpowiedzi web/API nadal zapisuja sie jako `choice`,
- automatyczny timeout egzaminu zapisuje sie jako `timeout`,
- `unknown` jest dopuszczone w kontrakcie web/API tylko jako `answer_kind = unknown`,
- `unknown` nie wymaga `selected_answer` / `user_answer`,
- `unknown` jest przyjmowane tylko dla `mode = sr_review`,
- `unknown` nie jest jeszcze wlaczone w UI.

## 14. Nowe serwisy domenowe

Docelowo potrzebujemy:

### `ReviewPlannerService`

Odpowiada za:

- dobor batcha,
- estymacje czasu,
- plan dnia,
- priorytet due vs relearn vs booster.

### `RecallScoringService`

Odpowiada za:

- liczenie quality/review score,
- przeliczenie poprawnosci, opoznienia i plynnosci odpowiedzi,
- przyszle confidence scoring, jesli dodamy user feedback.

### `ReviewPolicyService`

Odpowiada za:

- wersjonowalna polityke,
- leech handling,
- overdue pressure,
- reguly przejsc miedzy stanami pamieci.

### `ReviewMemoryProgressService`

Odpowiada za:

- zapis i odczyt `review_memory_progress`,
- aktualizacje zweryfikowanej pamieci tylko dla `sr_review`,
- interpretacje `choice`, `unknown` i przyszlych typow odpowiedzi,
- oddzielenie `training exposure` od `verified memory`.

### `StudySessionAnswerKind`

Lekki helper albo enum-like obiekt odpowiada za:

- stale `choice`, `unknown`, `timeout`, `skipped`,
- walidacje typow odpowiedzi,
- mapowanie legacy odpowiedzi na `choice`,
- blokade `unknown` poza `sr_review`.

### `ReviewTelemetryService`

Odpowiada za:

- zapis i agregacje metryk,
- raportowanie retencji,
- porownania polityk.

## 15. Co oznacza "skalowalne"

System ma byc skalowalny nie tylko wydajnosciowo, ale tez operacyjnie.

### 15.1 Skalowanie techniczne

Potrzebujemy:

- indeksow na `user_id`, `next_review_at`, `memory_state`,
- lekkich zapytan do liczenia due counts,
- materializowanego progresu per pytanie,
- append-only event logu zamiast nadpisywania wszystkiego w ciemno.

### 15.2 Skalowanie algorytmiczne

Potrzebujemy:

- `algorithm_version`,
- feature flags,
- policy snapshots,
- cohort rollout.

### 15.3 Skalowanie produktowe

Potrzebujemy:

- mozliwosci prostego wejscia dla zwyklego usera,
- mozliwosci bardziej zaawansowanej konfiguracji dla power usera,
- przejrzystego komunikowania "co system robi i dlaczego".

### 15.4 Skalowanie biznesowe

Potrzebujemy:

- mierzenia retencji,
- mierzenia `time to mastery`,
- mierzenia skutecznosci explainera i warstwy wizualnej,
- przyszlego eksportu raportow B2B.

## 16. Czego nie robimy od razu

Na starcie nie robimy:

- ML modelu przewidujacego zapominanie,
- timed annotations powiazanego z algorytmem review,
- recznego wybierania "latwe / trudne" po kazdej odpowiedzi,
- wielowarstwowego planera energii dnia,
- synchronizacji z zewnetrznymi kalendarzami,
- community rankingow opartych o review.

Najpierw budujemy mocny deterministic core.

## 17. Najwieksze ryzyka

### 17.1 Przedwczesna komplikacja

Najlatwiej popsuc ten modul, jesli zaczniemy od:

- zbyt skomplikowanego algorytmu,
- zbyt wielu nowych pol,
- zbyt wielu edge case'ow naraz.

### 17.2 Brak telemetry

Jesli nie dodamy event logu, pozniej nie bedziemy wiedzieli:

- czy nowy algorytm jest lepszy,
- czy pogorszyl retencje,
- czy konkretny feature rzeczywiscie pomaga.

### 17.3 Brak wersjonowania polityk

Jesli algorytm bedzie "zywa masa kodu", nie bedziemy umieli:

- robic bezpiecznych rolloutow,
- porownywac wariantow,
- tlumaczyc regresji.

## 18. Recommended next move

Aktualny status po ostatnim sprincie:

- `answer_kind` jest wdrozone addytywnie,
- timeout egzaminu ma jawny `answer_kind = timeout`,
- klasyczne odpowiedzi maja `answer_kind = choice`,
- `review_memory_progress` jest wdrozone jako osobny stan zweryfikowanej pamieci,
- `sr_review` zapisuje `review_memory_progress` dla odpowiedzi `choice`,
- `sr_review` obsluguje backendowo `answer_kind = unknown`,
- `unknown` nie podbija klasycznego `user_question_progress`,
- klasyczna nauka, Zen, PJM i egzamin nie tworza `review_memory_progress`,
- retry tej samej odpowiedzi nie dubluje klasycznego `user_question_progress` ani `review_memory_progress`,
- `Nie wiem` jest wlaczone w UI tylko dla `sr_review` i jest pokazywane w wynikach jako material do odzyskania, nie jako "Brak odpowiedzi".

Po Sprintach 3, 3.5, 4A oraz audycie 4B/4C i kolejnych preflightach nastepny sensowny krok to:

1. utrzymac obecny `sr_review` jako baseline v1,
2. przed migracja `answer_kind` zatwierdzic macierz backfillu:
   - `choice` dla historycznych wyborow A/B/C,
   - `timeout` dla egzaminow z `selected_answer = null`,
   - `skipped/legacy_unanswered` dla pozostalych historycznych nulli,
   - `unknown` tylko dla nowych odpowiedzi z przycisku w `sr_review`,
3. zatwierdzic macierz `answer impact policy`:
   - `unknown` w `sr_review` domyslnie zapisuje `review_memory_progress`, ale nie podbija klasycznego `incorrect_count`,
   - `choice` w `sr_review` ma jawna decyzje: legacy mirror do `user_question_progress` albo tylko verified memory,
   - statystyki publiczne/adminowe dla `unknown` ida forward-only, przez rebuild/backfill agregatow, albo przez wersjonowane metryki,
   - PJM progress, topic progress, hard questions i readiness maja testy potwierdzajace wybrana polityke,
4. dodac addytywna migracje `answer_kind` do `study_session_answers`,
5. zaktualizowac `StudySessionAnswer`, factory states oraz web/API testy dla `choice`, `unknown`, `timeout` i `skipped/legacy_unanswered`,
6. dodac `review_memory_progress` jako osobna warstwe `verified memory`,
7. powiazac `review_memory_progress` z konkretnym `study_session_answer_id`, zeby ta sama odpowiedz nie przeliczala progresu drugi raz,
8. rozszerzyc wspolny kontrakt web + API + payload builder o `answer_kind`,
9. zabezpieczyc lokalny optimistic flow przed zamrozeniem po odrzuconym `unknown` albo dodac retry,
10. wdrozyc `Nie wiem` tylko dla `sr_review`,
11. dopilnowac, zeby `unknown` bylo widoczne w wynikach jako odpowiedz wykonana, nie jako "Brak odpowiedzi",
12. odizolowac UI `sr_review` od klasycznych akcji dzialow:
   - `!isPjmMode` zastapic guardem typu `isClassicLearningMode` tam, gdzie chodzi o klasyczna nauke,
   - ukryc topic picker, `Powtorz ten dzial`, `Zacznij nastepny dzial` i klasyczne follow-upy w trenerze,
   - dac wynikowi `sr_review` osobne copy o planie pamieci,
13. odchudzic payload sesji dla trenera:
   - nie eager-loadowac ani nie serializowac `activeSignLanguageAssets` poza PJM,
   - nie budowac `topicGroups` dla `sr_review`, jesli nie zasila to nowego planu pamieci,
14. odciac pre-answer reveal w `sr_review`:
   - nie wysylac `correct_answer` przed odpowiedzia,
   - nie wysylac `explanation`/`explanation_asset`/`explanation_annotations`, jesli moga zdradzac rozwiazanie,
   - po odpowiedzi zwracac reveal z backendu,
15. przestawic `sr_review` na backendowe `next_unanswered_question_id`, zamiast traktowac `current_index` jako prawde o kolejnym kroku,
16. nie uzywac `payload.answered_count` jako zrodla prawdy dla completion, daily targetu ani memory progressu,
17. odchudzic `/trener-pamieci` i API `me/review-queue` do kontraktu plan-first:
   - plan, CTA, liczniki i scope jako domyslne,
   - preview pytan/media tylko opcjonalnie albo osobnym endpointem,
18. dodac snapshot wyniku `sr_review`, zeby stary ekran wyniku nie zmienial znaczenia po kolejnych dniach/progresach,
19. zrobic wynik 50/80 jako summary-first, z leniwym przegladem pytan i domyslnym pokazaniem tylko materialu do odzyskania,
20. zdecydowac, czy aktywna sesja zostaje globalna per user, czy per modul:
   - MVP moze zostac globalny,
   - ale wymaga eventu/copy `abandoned/replaced` dla zastapionego `sr_review`,
21. rozroznic `completed_all`, `finished_partial` i `abandoned/replaced` w telemetry/copy trenera,
22. ustalic i wdrozyc wspolna definicje `review_day` w app timezone dla planu, UI i progresu,
23. zatwierdzic, ze dzienny target liczy `accepted_answer_count`, a `unique_question_count` jest metryka kontrolna jakosci,
24. dodac answer-level ledger albo dzienny agregat dla `sr_review`, bo `review_memory_progress` jest stanem pytania, nie pelnym licznikiem dnia,
25. serializowac start sesji `sr_review` per user przez lock w transakcji albo krotki app lock i dodac test double-start/multi-tab,
26. dodac `review_day`/`plan_date`, `daily_target_count`, `completed_today_count` i `policy_version` do payloadu oraz eventow planu,
27. zdecydowac i przetestowac polityke starych aktywnych sesji `sr_review`:
   - nieodpowiedziana stara sesja moze byc porzucona i przeliczona,
   - czesciowo odpowiedziana moze byc dokonczona, ale odpowiedzi licza sie do dnia odpowiedzi,
   - completion event rozroznia `started_review_day` i `completed_review_day`,
28. dodac filtr, ktory nie wrzuca ponownie do glownego batcha pytan juz odpowiedzianych dzisiaj w `sr_review`,
29. dodac indeksy lub dzienny agregat pod licznik 80:
   - jesli liczymy po raw answers: `study_session_answers(study_session_id, answered_at)` i indeks sesji pod user/mode/category,
   - jesli liczymy po ledgerze: `user_id, license_category_id, review_day` oraz idempotencja po `study_session_answer_id`,
30. przed polityka 50/80 usunac obecne limity `sr_review`:
   - limit 40 w `StudySessionManager::normalizeQuestionCount()`,
   - limit 20 w `ReviewPlannerService::recommendedQuestionCount()`,
   - ring UI oparty o `recommendedQuestionCount / 20`,
31. nie budowac memory engine na czasie odpowiedzi bez osobnej decyzji produktowej,
32. nie traktowac `score_percent` jako glownej metryki trenera pamieci; wprowadzic metryki `verified/unknown/recovery/daily`,
33. rozdzielic dashboard/readiness na metryki klasyczne, memory-only i overall activity tam, gdzie `sr_review` moglby zanieczyscic gotowosc do egzaminu,
34. dodac osobny kontrakt dziennego planu w shared props albo przefiltrowac legacy `reviewDueCount`, zeby header nie klamal przy locked category i planach boosterowych,
35. dopilnowac, zeby start sesji `sr_review` uzywal tego samego zakresu kategorii co `/trener-pamieci`,
36. zdecydowac, co robimy z aktywna sesja `sr_review`, jesli admin zmieni userowi kategorie albo wygasnie/odnowi sie dostep,
37. dopisac nowe tabele do `ops:copy-sqlite-to-pgsql`, resetu danych nauki i docelowo monitoringu,
38. zaplanowac kolejnosc jobow i retencji tak, zeby agregaty/memory progress powstawaly przed prune raw sesji,
39. dopiero potem przepiac planner na mode-aware sygnaly:
   - `user_question_progress` jako ekspozycja/kandydaci,
   - `review_memory_progress` jako potwierdzona pamiec,
   - `review_trainer_events` jako telemetry,
   - dzienny budzet 80 i pierwszy blok 50 jako UX planu,
   - osobne zrodla booster/new candidates, jesli due count nie wypelnia celu dnia,
40. przed leech policy premium dodac signal/scoring oparty o `review_memory_progress`, zamiast uzywac finalnie `ReviewMemorySignalService` czytajacego wspolny `user_question_progress`,
41. dodac testy, ze `unknown` z `sr_review` nie zmienia klasycznego progresu, PJM progressu, hard questions, readiness ani publicznych statystyk, chyba ze wybrana polityka jawnie to wlacza,
42. ustalic polityke `response_time_ms` dla `sr_review`:
   - czas moze byc zapisany analitycznie,
   - ale nie powinien karac usera w `review_memory_progress`,
   - a jesli nadal aktualizujemy klasyczny `QuestionProgressManager`, trzeba zdecydowac, czy przekazujemy realny czas czy `null`.

Status realizacji po sprintach 18.1-18.48:

- Wykonane: punkty 4-11, 14-19, 22-24, 26, 28, 31-33, 35, 40-41 sa pokryte wdrozeniami albo testami opisanymi w statusach sprintow.
- W duzej czesci wykonane: punkt 12, bo UI `sr_review` ma juz osobny guard dla `Nie wiem`, wynik summary-first i ukryte klasyczne CTA; zostaje tylko stale obserwowac nowe akcje, ktore moglyby przypadkiem wrocic do trenera.
- Wykonane: punkt 13, bo `sr_review` nie buduje `topicGroups`, nie wysyla startowego `questionPool` ani `prefetchedQuestions`, frontend nie robi juz klientowego batch-prefetchu dla trenera, a `activeSignLanguageAssets` sa ladowane i serializowane tylko w `mode = pjm`.
- Wykonane: punkt 17, bo webowy `/trener-pamieci` jest plan-first, a API `me/review-queue` ma opcjonalne `include_questions=0`, walidacje parametru i ukrywanie `preview_question_ids` w lekkim kontrakcie.
- Wykonane: punkt 25, bo start `sr_review` jest serializowany lockiem na userze, a test double-start pilnuje, ze ten sam plan dnia nie tworzy duplikatu sesji ani telemetry.
- Wykonane: punkt 30, bo `sr_review` nie jest juz uciety do 40/20, a UI dziennego planu opiera sie na targetach 50/80.
- W duzej czesci wykonane: punkty 20-21 i 27, bo mamy `review.replaced`, `completion_type = auto_full/manual_partial`, rozroznienie `started_review_day` i `completed_review_day` oraz testy; zostaje decyzja, czy statusowo wprowadzamy osobny stan `abandoned/replaced`, czy zostawiamy obecne `completed` plus event telemetry.
- W duzej czesci wykonane: punkt 29, bo ledger ma indeks `user_id, license_category_id, review_day`, idempotencje po `study_session_answer_id`, a fallback raw answers dostal zakres daty oraz indeksy pod licznik dnia.
- Wykonane: punkt 42, bo `response_time_ms` zostaje zapisany analitycznie, ale `sr_review` nie aktualizuje klasycznego `QuestionProgressManager`, a `ReviewMemoryProgressService` nie uzywa czasu jako kary w scoringu pamieci.
- Wykonane: punkt 37, bo `ops:copy-sqlite-to-pgsql` zna `review_trainer_events`, `review_memory_progress` i `review_trainer_daily_answers`, reset danych nauki usuwa memory progress oraz daily ledger, a monitoring ma liczniki i testy dla nowych tabel.
- Wykonane: punkt 38, bo `review_memory_progress` jest aktualizowane przy odpowiedzi, prune historii zostawia verified memory state przy zyciu, a schedule buduje agregaty i monitoring przed prune raw historii.
- Wykonane: punkt 34 wybrana lekka sciezka, bo shared `studyContext.reviewDueCount` pozostaje legacy due count, ale jest filtrowany przez aktywny zakres kategorii, aktywne pytania i brak `delivery_issue`; nie uruchamiamy pelnego planera w globalnych shared props.
- Wykonane: punkt 36 w MVP, bo aktywny `sr_review` poza aktualnym zakresem kategorii albo po utracie dostepu jest zamykany telemetrycznie jako `review.replaced`, a web/API nie pozwala go dalej kontynuowac.
- Wykonane: punkt 39 w MVP, bo planner uzywa `user_question_progress` jako ekspozycji/kandydatow, `review_memory_progress` jako zweryfikowanej pamieci, dziennego budzetu 80 i pierwszego bloku 50; plan rozroznia `candidate_source_counts.primary`, `candidate_source_counts.seen_booster` i `candidate_source_counts.new_candidate`.
- Wykonane: `new_candidate` ma limitowana polityke pierwszej ekspozycji, zamiast pozostawac na zawsze `0` albo agresywnie dopelniac sesje.
- Wykonane: planner potrafi ponownie znalezc pytanie zapisane tylko w `review_memory_progress`, bez tworzenia klasycznego `user_question_progress`.
- Nadal do obserwacji: limity pierwszej ekspozycji (`15` dziennie i `30%` pierwszego bloku) moga wymagac strojenia po danych z produkcji.
- Wykonane: regresja reveal po odpowiedzi w aktywnym `sr_review` jest domknieta. UI czyta teraz tekst wyjasnienia, blok referencyjny i adnotacje z answer reveal po odpowiedzi, a nie z pre-answer `activeQuestion`. API answer response dla `sr_review` zwraca analogiczny reveal dla ocenionego pytania, ale nadal nie ujawnia nastepnego pytania.
- Najblizszy bezpieczny kierunek: manualna weryfikacja `/trener-pamieci` na koncie z mala liczba due oraz obserwacja telemetry `new_candidate_count`.

To jest najlepsza droga do premium bez przepalania kodu i bez ryzyka, ze zmienimy wspolny progres w sposob, ktory popsuje klasyczna nauke, PJM, analityke albo dashboard.

### 18.1 Status po sprincie UI `Nie wiem`

Zrobione w tym sprincie:

- `StudySessions/Show.vue` rozpoznaje `answer_kind` w typach, lokalnym stanie i wynikach sesji.
- Przycisk `Nie wiem` jest widoczny tylko dla `mode = sr_review`.
- Klikniecie `Nie wiem` wysyla `answer_kind = unknown`, `selected_answer = null` i nie korzysta z klasycznych odpowiedzi A/B/C.
- Lokalny optimistic flow traktuje `unknown` jako odpowiedz wykonana, wiec pytanie nie znika z podsumowania.
- Podsumowanie nie pokazuje dla `unknown` tekstow "Brak odpowiedzi" ani "Nie udalo sie odczytac tresci tej odpowiedzi".
- Wyniki trenera uzywaja copy "do odzyskania", zeby nie karac usera za uczciwe oznaczenie braku pewnosci.
- Klasyczne akcje dzialow (`Powtorz bledne pytania`, `Powtorz ten dzial`, `Zacznij nastepny dzial`, topic picker) sa ukryte w wyniku `sr_review`, zeby nie mieszac trenera pamieci z klasyczna nauka.
- `ReviewTrainerCompletionSummaryService` rozdziela `unknown_answers_count`, `choice_incorrect_answers_count` i `needs_recovery_answers_count`, wiec summary nie musi zgadywac po samym `is_correct = false`.
- Jesli sesja ma `unknown`, coach message i next step traktuja to jako material do odzyskania.
- Wynik `sr_review` jest teraz summary-first: szczegolowa lista pytan jest ukryta za przyciskiem `Pokaż material do odzyskania` / `Pokaż pytania z sesji`, zeby glownym ekranem byl plan pamieci, a nie kolejna dluga lista.

Weryfikacja:

- `npm run build` przechodzi po zmianach UI.
- Test `sr review completion payload exposes unknown answer as an answered result` pilnuje, ze wynik `sr_review` nadal niesie `answer_kind = unknown`, `selected_answer = null` i jest liczony jako odpowiedz wykonana.
- Ten sam test pilnuje metryk `unknown_answers_count`, `choice_incorrect_answers_count`, `needs_recovery_answers_count` i `next_step.tone = recovery`.

Nastepne bezpieczne kroki:

1. Przejsc do dziennego planu 50/80 dopiero po doprecyzowaniu `review_day`, daily ledger/agregatu i polityki aktywnych sesji.
2. Nadal nie uzywac czasu odpowiedzi jako kary w algorytmie pamieci bez osobnej decyzji produktowej.

### 18.2 Status po sprincie daily plan foundation

Zrobione w tym sprincie:

- Dodany zostal `ReviewTrainerDailyPlanService` jako jedno miejsce definicji dziennej polityki trenera.
- `review_day` jest liczony po app timezone i trafia do planu, payloadu sesji oraz telemetry.
- Obecna polityka ma jawne stale:
  - `daily_plan_policy_version = review-daily-plan-v1`,
  - `daily_target_count = 80`,
  - `minimum_session_question_count = 50`.
- `ReviewPlannerService` zwraca teraz dzienny kontekst:
  - `review_day`,
  - `daily_target_count`,
  - `minimum_session_question_count`,
  - `completed_today_count`,
  - `daily_remaining_count`.
- `StudySessionManager` zapisuje ten kontekst w `payload.review_plan`, zeby wynik i telemetry mialy snapshot planu z momentu startu.
- Event `review.session_started` zapisuje dzienny kontekst startu.
- Event `review.completed` rozroznia `started_review_day` i `completed_review_day`, zeby sesje zaczete jednego dnia i skonczone drugiego nie mylily analityki.
- `/trener-pamieci` pokazuje okragly wskaznik dziennego planu oparty o projektowany postep dnia, a nie o stary limit 20 pytan.
- Licznik `completed_today_count` jest na tym etapie liczony addytywnie z odpowiedzi `study_session_answers` tylko dla `mode = sr_review` i wybranej kategorii.
- Planner `sr_review` nie uzywa juz starego limitu 20:
  - pierwszy blok dnia celuje w `minimum_session_question_count = 50`,
  - kolejne bloki domykaja `daily_target_count = 80`,
  - jesli due count jest mniejszy niz cel bloku, planner bierze dostepna liczbe pytan.
- `StudySessionManager::normalizeQuestionCount()` ma osobna regule dla `MODE_SR_REVIEW` i nie ucina juz tego trybu do domyslnego limitu 40.
- Planner wyklucza z glownego batcha pytania, na ktore user odpowiedzial dzisiaj w `sr_review`, zeby drugi start tego samego dnia nie mielil natychmiast tych samych pytan.
- Dodana zostala tabela `review_trainer_daily_answers` jako answer-level ledger dla trenera pamieci.
- Ledger jest idempotentny po `study_session_answer_id`, zapisuje:
  - `review_day`,
  - `daily_plan_policy_version`,
  - `answer_kind`,
  - `is_correct`,
  - user/category/question/session/answer ids.
- Ledger powstaje tylko dla nowych odpowiedzi `sr_review`; klasyczna nauka i Zen/PJM/egzamin nie zasilaja tego licznika.
- Reset danych nauki z panelu admina usuwa tez `review_trainer_daily_answers`.
- `ops:copy-sqlite-to-pgsql` zna nowa tabele.
- Start `sr_review` jest idempotentny dla tej samej kategorii, `review_day` i `daily_plan_policy_version`.
- W transakcji bierzemy lock na userze, a jesli istnieje juz aktywna sesja trenera dla tego samego planu dnia, backend zwraca ja zamiast tworzyc duplikat.
- Gdy nowy start zamyka starsza aktywna sesje `sr_review`, zapisujemy event `review.replaced` zamiast udawac w telemetry normalne ukonczenie.
- Event `review.replaced` niesie `started_review_day`, `replacement_review_day`, `replacement_mode`, `replacement_license_category_id`, `answered_count` i `total_questions_count`.
- API session payload dla `sr_review` zwraca teraz `session.review_plan` z tym samym dziennym kontraktem co web.
- API answer response dla `sr_review` zwraca `daily_progress`, zeby klient po odpowiedzi widzial aktualny licznik dnia bez zgadywania po lokalnym czasie.

Wazne ograniczenie:

- To jeszcze nie jest trwala tabela dziennego planu.
- Dzienne liczenie w plannerze korzysta z `review_trainer_daily_answers`, gdy tabela istnieje.
- Dla bezpieczenstwa deploymentu planner dolicza raw `study_session_answers`, ktorych nie ma jeszcze w ledgerze, zeby nie zgubic dzisiejszych odpowiedzi sprzed migracji i nie policzyc dwa razy odpowiedzi juz zledgerowanej.
- To nadal nie jest pelny agregat dzienny, tylko ledger + fallback; przy duzej skali mozemy dodac materializowany agregat.
- Stare aktywne sesje nadal sa statusowo zamykane przez istniejacy mechanizm `completed`, ale telemetry odroznia teraz replacement od prawdziwego `review.completed`.
- Nie rozdzielilismy jeszcze globalnej aktywnej sesji per modul.

Nastepny bezpieczny krok:

1. Decyzja, czy statusowo wprowadzamy `abandoned/replaced`, czy zostawiamy obecne `completed` plus event telemetry.
2. Decyzja, czy globalna aktywna sesja zostaje per user, czy docelowo per modul.
3. Decyzja, czy booster/new candidates uzupelniaja dzienny cel, gdy due count jest za maly.
4. Opcjonalny agregat dzienny, jesli ledger + fallback stanie sie kosztowny.

Weryfikacja:

- `tests/Unit/Support/ReviewPlannerServiceTest.php` pilnuje dziennego kontekstu i liczenia tylko dzisiejszych odpowiedzi `sr_review` z wybranej kategorii, bez podwojnego liczenia odpowiedzi juz obecnych w ledgerze.
- Ten sam test pilnuje pierwszego bloku 50 oraz wykluczania pytan odpowiedzianych dzisiaj.
- `tests/Feature/ReviewTrainerTelemetryTest.php` pilnuje, ze start i completion eventy maja dzienny payload oraz ze nowe odpowiedzi `sr_review` trafiaja do `review_trainer_daily_answers` idempotentnie.
- Ten sam test pilnuje, ze `sr_review` moze wystartowac 50 pytan i nie jest juz uciety do 40.
- Ten sam test pilnuje, ze drugi start `sr_review` dla tego samego dnia i polityki zwraca istniejaca sesje zamiast tworzyc nowa.
- Ten sam test pilnuje, ze starsza aktywna sesja `sr_review` dostaje `review.replaced`, a nie `review.completed`.
- `tests/Feature/ApiSessionTest.php` pilnuje `session.review_plan` i `daily_progress` w API `sr_review`.
- `tests/Feature/ReviewQueueTest.php` pilnuje kontraktu web/API dla planu dziennego.
- `tests/Feature/Admin/AdminUserAccountSprint4Test.php` pilnuje, ze reset danych nauki usuwa daily ledger.
- `npm run build` przechodzi po zmianie UI okraglego wskaznika.

### 18.3 Status po sprincie verified memory signals

Zrobione w tym sprincie:

- Dodany zostal `ReviewMemoryVerifiedSignalService` jako osobna warstwa scoringu dla `review_memory_progress`.
- Ten scoring nie czyta klasycznego `user_question_progress`, wiec nie miesza strzalow z nauki, Zen/PJM ani egzaminu z faktycznie zweryfikowana pamiecia.
- Serwis zwraca wersjonowany kontrakt:
  - `version = verified-memory-signals-v1`,
  - `source_policy_version`,
  - `verified_memory_state`,
  - `plan_segment`,
  - `recovery_score`,
  - `leech_score`,
  - `stability_score`,
  - `overdue_days`,
  - liczniki prob i bledow zweryfikowanej pamieci.
- `plan_segment` rozroznia:
  - `recovery` dla pytan po `unknown`, bledzie, skipie albo stanie `needs_recovery`,
  - `due` dla pytan z zaplanowana powtorka na dzisiaj,
  - `reinforce` dla stabilnych pytan wzmacniajacych.
- `leech_score` jest liczony na podstawie zweryfikowanych prob w trenerze pamieci, a nie z klasycznego postepu.
- `stability_score` premiuje serie poprawnych odpowiedzi w trenerze i nie karze uzytkownika za sam czas odpowiedzi.
- `ReviewPlannerService` zaczal uwzgledniac `review_memory_progress` przy budowaniu kolejki:
  - pytanie w `needs_recovery` wraca do planu nawet wtedy, gdy klasyczny `user_question_progress.next_review_at` jest w przyszlosci,
  - pytanie z `next_verified_review_at <= dzisiaj` wraca jako zweryfikowana powtorka pamieci,
  - zweryfikowane recovery ma wyzszy priorytet niz zwykla klasyczna due kolejka,
  - pytania odpowiedziane dzisiaj w `sr_review` nadal sa wykluczane, zeby nie mielic ich w kolko.
- Kategorie i `ready_for_review_count` na `/trener-pamieci` licza teraz ten sam typ kandydatow co planner: klasyczne due plus verified recovery/due, z wykluczeniem pytan odpowiedzianych dzisiaj.
- Plan zwraca `verified_memory_signal_version`, zeby web/API mogly rozpoznac wersje nowego scoringu bez zgadywania.
- Planner dodal bezpieczne boostery:
  - jesli due/recovery jest za malo na pierwszy blok, moze dobrac pytania widziane wczesniej w klasycznych trybach,
  - booster wymaga `user_question_progress.total_attempts > 0`, wiec trener nie odpala zupelnie nowych, niewidzianych pytan,
  - plan rozroznia `due_count`, `candidate_count` i `booster_count`,
  - `recommended_question_count` moze dojsc do 50 dzieki boosterom, ale nadal respektuje dzienny limit 80.
- UI `/trener-pamieci` pokazuje teraz sklad sesji:
  - `Ta sesja` = ile pytan backend faktycznie uruchomi teraz,
  - `Pilne` = due/recovery do odzyskania,
  - `Wzmocnienie` = bezpieczne boostery z materialu juz widzianego,
  - `Po sesji` = ile zostanie po aktualnym bloku.
- Copy kategorii i statystyk unika juz mylacego "do powtorki" tam, gdzie licznik obejmuje tez boostery; widok mowi o pytaniach "w planie".
- Dropdown kategorii nie pokazuje juz opcji "Wszystkie aktywne kategorie", bo obecny start sesji wymaga jednej `license_category_id`; unikamy obietnicy trybu multi-category przed wdrozeniem go end-to-end.

Wazne ograniczenie:

- Nadal nie dodajemy losowych nowych pytan do trenera pamieci; kandydat musi miec klasyczny `user_question_progress`, czyli user musial juz zobaczyc pytanie w innym trybie albo wczesniej w trenerze.
- Preview pytan na `/trener-pamieci` nadal pokazuje klasyczny `memory_signal`; osobny `verified_memory_signal` dla kart pytan mozemy dodac dopiero wtedy, gdy zdecydujemy, ile szczegolow w ogole ma byc widoczne w UI.
- Boostery sa na razie kandydatami z istniejacego klasycznego postepu, nie z pelnej tabeli `questions`. To chroni przed sytuacja, w ktorej trener pamieci staje sie drugim trybem pierwszej nauki.

Weryfikacja:

- `tests/Unit/Support/ReviewMemoryVerifiedSignalServiceTest.php` pilnuje recovery/leech risk, stabilnej pamieci i due review.
- `tests/Unit/Support/ReviewPlannerServiceTest.php` pilnuje, ze verified recovery wygrywa z przyszlym klasycznym terminem, pytanie odpowiedziane dzisiaj nie wraca od razu do planu, boostery dopelniaja pierwszy blok i niewidziane przyszle rekordy nie trafiaja do boostera.
- `tests/Feature/ReviewQueueTest.php` pilnuje, ze kategorie na `/trener-pamieci` licza verified recovery tak samo jak planner.
- `tests/Unit/Support/ReviewMemorySignalServiceTest.php` nadal pilnuje starego scoringu klasycznego, wiec mamy oba kontrakty osobno.

### 18.4 Status po sprincie backendowego reveal

Zrobione w tym sprincie:

- Aktywna sesja `sr_review` nie dziedziczy juz pre-answer reveal z klasycznej nauki.
- Przed odpowiedzia payload webowy i API trenera pamieci zawiera pytanie, opcje, media i kontekst, ale nie zawiera:
  - `correct_answer`,
  - tekstowego `explanation`,
  - `explanation_asset`,
  - `explanation_annotations`.
- To dotyczy pierwszego renderu `StudySessions/Show`, pelnego `questionPool`, `prefetchedQuestions`, endpointow batch/single question oraz payloadu `StudySessionApiPayloadBuilder` dla aktywnego `sr_review`.
- Po zapisaniu odpowiedzi backend zwraca server-authoritative reveal w `answer`:
  - `is_correct`,
  - `correct_answer`,
  - `correct_answer_text`,
  - `explanation`,
  - `explanation_asset`,
  - `explanation_annotations`.
- Frontend scala reveal z lokalnym `ResultItem` dopiero po odpowiedzi, wiec UI nadal moze pokazac feedback i wyjasnienie bez wkladania rozwiazania do payloadu przed kliknieciem.
- Egzamin nie dostaje reveal w odpowiedzi z aktualnej trasy webowej.

Dlaczego to jest wazne:

- `Trener pamieci` ma byc modulem weryfikacji realnego odzyskania z pamieci, a nie kolejnym trybem nauki z odpowiedzia dostepna w danych klienta.
- Ten krok wzmacnia czystosc sygnalu `verified memory`, bo poprawna odpowiedz w `sr_review` nie powinna byc latwa do podejrzenia przed decyzja usera.

Weryfikacja:

- `tests/Feature/StudySessionFlowTest.php` pilnuje, ze aktywne `sr_review` nie wysyla reveal przed odpowiedzia ani przez batch endpoints, a po odpowiedzi zwraca reveal w JSON.
- `tests/Feature/ApiSessionTest.php` pilnuje, ze API `sr_review` nie wysyla correct/explanation ani wizualnych wyjasnien przed odpowiedzia.
- `npm run build` powinien potwierdzic, ze kontrakt TypeScript dla odpowiedzi jest spojny z lokalnym flow.

### 18.5 Status po sprincie next unanswered

Zrobione w tym sprincie:

- Aktywne `sr_review` nie uzywa juz samego `current_index` do wyznaczania aktualnego pytania.
- Backend liczy pierwsze nieodpowiedziane pytanie na podstawie zapisanych `study_session_answers`.
- Po odpowiedzi w `sr_review` payload sesji zapisuje pomocnicze `next_unanswered_question_id`, zeby stan sesji byl czytelny diagnostycznie.
- Jesli odpowiedz przyjdzie poza kolejnoscia, np. z API, retry albo drugiej karty, ekran sesji po odswiezeniu wraca do pierwszego realnie nieodpowiedzianego pytania zamiast udawac koniec sesji.
- Frontendowy `resolveNextQuestionMeta()` w `sr_review` pomija lokalnie odpowiedziane pytania i umie zawrocic do pierwszej nieodpowiedzianej pozycji.

Wazne ograniczenie:

- Nadal nie budujemy jeszcze pelnego server-driven `next_question` w odpowiedzi po zapisie. To moze byc nastepny krok, jesli chcemy calkiem uniezaleznic klienta od lokalnego `questionIds`.

Weryfikacja:

- `tests/Feature/StudySessionFlowTest.php` pilnuje przypadku: user/API odpowiada na ostatnie pytanie jako pierwsze, a aktywna sesja `sr_review` nadal pokazuje pierwsze nieodpowiedziane pytanie i pozostaje `in_progress`.

### 18.6 Status po sprincie server-driven next question

Zrobione w tym sprincie:

- Webowy zapis odpowiedzi w aktywnym `sr_review` zwraca teraz:
  - reveal poprzedniego pytania w `answer`,
  - `nextQuestion` i `nextQuestionNumber` wyliczone przez backend po zapisanych odpowiedziach.
- `nextQuestion` nie zawiera `correct_answer`, tekstowego `explanation`, `explanation_asset` ani `explanation_annotations`.
- Frontend od razu cache'uje backendowe `nextQuestion` jako `preparedNextQuestion`, wiec przejscie dalej nie musi zgadywac po lokalnym oknie pytan.
- API answer response dla `sr_review` zwraca analogiczne `next_question` i `next_question_number`, rowniez bez reveal.

Wazne ograniczenie:

- Nadal zostawiamy `questionIds` jako podstawowa mape sesji w UI. Backendowe `nextQuestion` jest teraz zrodlem najblizszego kroku, ale nie przebudowujemy jeszcze calego odtwarzacza na pelny server-driven flow.

Weryfikacja:

- `tests/Feature/StudySessionFlowTest.php` pilnuje, ze webowy answer response po out-of-order odpowiedzi zwraca nastepne nieodpowiedziane pytanie bez reveal.
- `tests/Feature/ApiSessionTest.php` pilnuje tego samego kontraktu dla API `sr_review`.

### 18.7 Status po sprincie snapshotu wyniku

Zrobione w tym sprincie:

- Zakonczone sesje `sr_review` dostaja snapshot podsumowania w `study_sessions.payload.review_completion_snapshot`.
- Snapshot ma `snapshot_version = review-completion-summary-v1` i `snapshot_created_at`, ale zachowuje dotychczasowy kontrakt `reviewCompletion` dla UI.
- `ReviewTrainerCompletionSummaryService::summary()` najpierw czyta zapisany snapshot, a dopiero dla starszych sesji bez snapshotu liczy podsumowanie dynamicznie.
- Webowy zapis ostatniej odpowiedzi, manualne zakonczenie sesji i API zapis ostatniej odpowiedzi zapisuja snapshot w chwili domkniecia `sr_review`.
- Snapshot nie dotyka klasycznej nauki, PJM, egzaminu ani quick mode.

Dlaczego to jest wazne:

- Ekran wyniku `sr_review` ma pokazywac historyczny efekt konkretnej sesji, a nie aktualny stan pamieci po kolejnych dniach.
- To przygotowuje grunt pod wynik 50/80 jako summary-first: mozemy pokazac liczby odzyskiwania, `Nie wiem`, stabilizacji i planu dnia bez ryzyka, ze stare podsumowanie zmieni znaczenie.

Wazne ograniczenie:

- Starsze sesje bez snapshotu nadal maja fallback dynamiczny, zeby nie zepsuc istniejacych wynikow.
- Snapshot nie jest jeszcze pelnym answer-level ledgerem ani dziennym agregatem; to zamrozenie podsumowania jednej zakonczonej sesji.

Weryfikacja:

- `tests/Unit/Support/ReviewTrainerCompletionSummaryServiceTest.php` pilnuje, ze zapisany snapshot wygrywa z pozniejsza zmiana progresu pytania.
- `tests/Feature/StudySessionFlowTest.php` pilnuje, ze webowe domkniecie `sr_review` zapisuje snapshot.
- `tests/Feature/ApiSessionTest.php` pilnuje, ze API domkniecie `sr_review` tez zapisuje snapshot.

### 18.8 Status po sprincie summary-first wyniku 50/80

Zrobione w tym sprincie:

- Zakonczony webowy wynik `sr_review` nie wysyla juz w initial payload pelnej listy poprawnych pytan z media i wyjasnieniami.
- `results` dla zakonczonego `sr_review` zawiera tylko material do odzyskania, czyli pytania z `is_correct = false`, w tym `Nie wiem`.
- Gorne liczniki wyniku trenera pamieci czytaja `reviewCompletion` ze snapshotu, wiec nadal pokazuja pelna liczbe przerobionych, poprawnych i do odzyskania pytan.
- Klasyczna nauka, PJM, egzamin i in-progress `sr_review` zachowuja dotychczasowy payload wynikow.

Dlaczego to jest wazne:

- Sesja 50/80 nie powinna renderowac ani transportowac kilkudziesieciu kart pytan tylko po to, zeby pokazac userowi "wynik".
- Produktowo wynik trenera jest mapa pamieci i material do odzyskania, a nie kolejna pionowa lista calego batcha.

Wazne ograniczenie:

- To nie jest jeszcze pelny lazy endpoint do pobierania poprawnych odpowiedzi na zadanie. Na ten moment celowo nie pokazujemy ich w initial result payload dla `sr_review`.

Weryfikacja:

- `tests/Feature/StudySessionFlowTest.php` pilnuje, ze zakonczony `sr_review` zwraca w `results` tylko material do odzyskania, a pelne liczniki zostaja w `reviewCompletion`.

### 18.9 Status po sprincie verified completion summary

Zrobione w tym sprincie:

- `ReviewTrainerCompletionSummaryService` preferuje teraz `review_memory_progress` przy liczeniu segmentow i stanu pamieci dla wyniku `sr_review`.
- Jesli pytanie ma stan verified memory, summary mapuje go do dotychczasowego kontraktu UI:
  - `needs_recovery` -> `relearning` / `risky`,
  - `verified_memory` -> `mastered`,
  - `review` -> `review`,
  - `new` -> `new`.
- Dla starszych sesji lub pytan bez `review_memory_progress` nadal dziala fallback do klasycznego `user_question_progress`.
- `next_review_label` dla wyniku trenera bierze najpierw `next_verified_review_at`, a dopiero dla braku verified row wraca do `next_review_at` z klasycznego progresu.

Dlaczego to jest wazne:

- Wynik trenera pamieci powinien opisywac to, co trener realnie zweryfikowal, a nie tylko ekspozycje z klasycznej nauki, Zen/PJM albo egzaminu.
- Poprawny wynik w klasycznej nauce moze byc strzalem; `verified memory` jest sygnalem z dedykowanego modulu.

Wazne ograniczenie:

- Publiczny kontrakt `reviewCompletion` pozostaje kompatybilny z obecnym UI. Nie dodajemy jeszcze osobnych pol `verified_*` do frontu wyniku.

Weryfikacja:

- `tests/Unit/Support/ReviewTrainerCompletionSummaryServiceTest.php` pilnuje, ze verified recovery wygrywa z klasycznym stanem mastered.

### 18.10 Status po sprincie API review completion

Zrobione w tym sprincie:

- API answer response dla zakonczonego `sr_review` zwraca teraz `data.review_completion`.
- API session details zwraca `data.review_completion` dla `sr_review`, korzystajac z tego samego `ReviewTrainerCompletionSummaryService` co web.
- Pole jest addytywne i ma snake_case, zeby pasowalo do reszty kontraktu API.
- Dla innych trybow `review_completion` pozostaje `null` albo nie steruje logika klienta.

Dlaczego to jest wazne:

- Web i API maja wspolna definicje wyniku trenera pamieci.
- Przyszla aplikacja mobilna nie musi rekonstruowac wyniku z listy pytan ani z klasycznego `score_percent`.

Weryfikacja:

- `tests/Feature/ApiSessionTest.php` pilnuje, ze API po `unknown` w `sr_review` zwraca recovery summary w answer response i session details.

### 18.11 Status po sprincie answer-count hardening

Zrobione w tym sprincie:

- `StudySessionManager::recordAnswer()` nie ufa juz `payload.answered_count` przy wyliczaniu, czy sesja ma byc zakonczona.
- `StudySessionManager::answeredCount()` dla `sr_review` zwraca teraz liczbe realnych `study_session_answers`, bez podbijania jej przez payload.
- Po zapisaniu odpowiedzi backend liczy `answered_count` z realnych rekordow `study_session_answers`.
- `correct_answers_count` tez jest synchronizowany z zapisanych odpowiedzi, zamiast polegac na inkrementacji poprzedniej wartosci sesji.

Dlaczego to jest wazne:

- `payload` jest pomocniczy i diagnostyczny. Nie powinien decydowac o completion, daily target ani memory progress.
- Stary, niespojny albo recznie zmieniony payload nie moze przedwczesnie domknac `sr_review`.

Weryfikacja:

- `tests/Feature/StudySessionFlowTest.php` pilnuje, ze zawyzony `payload.answered_count` nie konczy sesji ani nie zawyza progresu widoku, dopoki w bazie nie ma kompletu odpowiedzi.

### 18.12 Status po sprincie completion type telemetry

Zrobione w tym sprincie:

- Event `review.completed` dostal pole `completion_type`.
- `completion_type = auto_full`, gdy liczba zapisanych odpowiedzi pokrywa caly batch.
- `completion_type = manual_partial`, gdy sesja zostala domknieta recznie bez kompletu odpowiedzi.

Dlaczego to jest wazne:

- Completion rate i skutecznosc trenera nie powinny mieszac pelnego treningu 50/80 z recznie przerwana sesja.
- To przygotowuje telemetry pod pozniejszy `review.abandoned` albo bardziej szczegolowe stany sesji.

Weryfikacja:

- `tests/Feature/ReviewTrainerTelemetryTest.php` pilnuje `auto_full` dla automatycznego domkniecia i `manual_partial` dla recznego zakonczenia bez odpowiedzi.

### 18.13 Status po sprincie classified analytics

Zrobione w tym sprincie:

- `ReviewTrainerAnalyticsService` nadal zwraca dotychczasowe `completed_sessions_count` i `completion_rate_percent`, zeby nie zmienic istniejacego kontraktu UI/API.
- Addytywnie dodane zostaly liczniki:
  - `completed_full_sessions_count`,
  - `completed_partial_sessions_count`,
  - `completed_unclassified_sessions_count`,
  - `full_completion_rate_percent`.
- `completed_full_sessions_count` liczy tylko eventy `review.completed` z `completion_type = auto_full`.
- `completed_partial_sessions_count` liczy eventy `review.completed` z `completion_type = manual_partial`.
- `completed_unclassified_sessions_count` chroni historyczne eventy bez `completion_type`, wiec stare dane nie sa po cichu uznawane za pelne sesje.

Dlaczego to jest wazne:

- Dalszy UI i raportowanie beda mogly pokazywac prawdziwa skutecznosc pelnych blokow 50/80 bez mieszania ich z recznie przerwanymi sesjami.
- Zachowujemy kompatybilnosc wsteczna, ale mamy juz metryke gotowa pod premium analytics.

Weryfikacja:

- `tests/Feature/ReviewTrainerTelemetryTest.php` pilnuje rozdzielenia `auto_full`, `manual_partial` oraz legacy completion bez `completion_type`.

### 18.14 Status po sprincie memory-only answer impact

Zrobione w tym sprincie:

- Odpowiedzi `choice` w `sr_review` nie aktualizuja juz klasycznego `user_question_progress`.
- `sr_review` nadal zapisuje:
  - `study_session_answers`,
  - `review_memory_progress`,
  - `review_trainer_daily_answers`,
  - telemetry start/completion.
- Klasyczna nauka, PJM, Zen/quick i egzamin zachowuja dotychczasowa sciezke klasycznego progresu tam, gdzie juz z niej korzystaly.
- `unknown` pozostaje bez zmian: zapisuje zweryfikowana pamiec trenera, ale nie podbija klasycznych bledow.

Dlaczego to jest wazne:

- Trener pamieci staje sie osobnym modulem weryfikacji, a nie kolejnym zrodlem klasycznych statystyk.
- Poprawna odpowiedz w trenerze nie zmienia statusu pytania w klasycznej nauce, wiec ranking gotowosci, dzialy, PJM i statystyki klasyczne nie sa zanieczyszczane sygnalem z innego trybu.
- Planner nadal moze korzystac z klasycznego progresu jako zrodla kandydatow/boosterow, ale wynik trenera zapisuje sie w `review_memory_progress`.

Weryfikacja:

- `tests/Feature/ReviewTrainerTelemetryTest.php` pilnuje, ze poprawna odpowiedz w `sr_review` aktualizuje `review_memory_progress`, daily ledger i wynik sesji, ale nie zwieksza `user_question_progress.total_attempts`.
- Ten sam plik pilnuje retry tej samej odpowiedzi bez dublowania verified memory.

### 18.15 Status po sprincie category analytics isolation

Zrobione w tym sprincie:

- `CategoryAnalyticsService` nie liczy juz sesji `sr_review` do klasycznych metryk kategorii:
  - `completed_sessions_count`,
  - `answered_count`,
  - `correct_answers_count`,
  - `accuracy_pct`,
  - fallbackowego `readiness_score`,
  - `last_answered_at`,
  - `recent_activity`.
- Metryki oparte o `user_question_progress` pozostaja bezpieczne po sprincie 18.14, bo `sr_review` nie dopisuje juz klasycznego progresu.
- Trener pamieci zachowuje wlasne zrodla prawdy: `review_memory_progress`, `review_trainer_daily_answers` i telemetry.

Dlaczego to jest wazne:

- Klasyczna analityka kategorii nie bedzie rosla od odpowiedzi w trenerze pamieci.
- Uzytkownik moze traktowac trener jako weryfikator wiedzy, a nie kolejny tryb, ktory sztucznie poprawia lub psuje klasyczne statystyki nauki.

Weryfikacja:

- `tests/Feature/CategoryAnalyticsApiTest.php` pilnuje, ze zakonczona sesja `sr_review` z poprawna odpowiedzia nie podbija klasycznego summary kategorii.

### 18.16 Status po sprincie dashboard activity split

Zrobione w tym sprincie:

- `DashboardMetricsService` zachowuje dotychczasowe pola `sessions_today` i `answered_today` jako aktywnosc ogolem.
- Addytywnie dodane zostaly pola:
  - `classic_sessions_today`,
  - `classic_answered_today`,
  - `memory_trainer_sessions_today`,
  - `memory_trainer_answered_today`.
- Klasyczne liczniki wykluczaja `mode = sr_review`.
- Liczniki trenera pamieci obejmuja tylko `mode = sr_review`.
- `ready_for_review_count`, `hard_questions_count` i `readiness_score` nadal ida z klasycznego progresu, wiec po sprincie 18.14 nie sa podbijane odpowiedziami trenera.
- Fallback `UserReadinessService`, uzywany gdy uzytkownik nie ma jeszcze `user_question_progress`, tez wyklucza `sr_review`, zeby zakonczona sesja trenera nie udawala klasycznej gotowosci.

Dlaczego to jest wazne:

- Dashboard moze pokazywac cala aktywnosc uzytkownika, ale UI i API maja juz jawne rozdzielenie klasycznej nauki od trenera pamieci.
- Nie lamiemy starego kontraktu API, a jednoczesnie przygotowujemy ekran pod memory-only komunikaty.

Weryfikacja:

- `tests/Feature/DashboardTest.php` pilnuje, ze sesja `sr_review` trafia do memory-only licznikow, ale nie do klasycznych licznikow dashboardu ani fallbackowej gotowosci.

### 18.17 Status po sprincie memory-only duration estimate

Zrobione w tym sprincie:

- `ReviewPlannerService::estimatedDurationSeconds()` estymuje czas trenera pamieci tylko z odpowiedzi `mode = sr_review`.
- Odpowiedzi z klasycznej nauki, PJM, Zen/quick i egzaminu nie wplywaja juz na czas pokazany w planie trenera.
- Gdy uzytkownik nie ma jeszcze historii czasow w trenerze pamieci, planner wraca do stalego fallbacku `45 s / pytanie`.

Dlaczego to jest wazne:

- Trener pamieci zachowuje osobny kontekst UX, bez ukrytego mieszania tempa z innych trybow.
- Nadal nie uzywamy czasu odpowiedzi jako kary w algorytmie pamieci; czas sluzy tylko do neutralnej estymacji dlugosci sesji.

Weryfikacja:

- `tests/Unit/Support/ReviewPlannerServiceTest.php` pilnuje, ze klasyczna odpowiedz z dlugim czasem nie zmienia estymacji trenera, a historyczna odpowiedz `sr_review` moze ja ustawic.

### 18.18 Status po sprincie full-session analytics

Zrobione w tym sprincie:

- `ReviewTrainerAnalyticsService` zachowuje dotychczasowe ogolne pola telemetry.
- Addytywnie dodane zostaly metryki liczone tylko z `completion_type = auto_full`:
  - `full_average_score_percent`,
  - `full_average_duration_seconds`,
  - `full_average_duration_label`,
  - `full_total_answered_count`.
- Ręcznie przerwane sesje `manual_partial` i legacy completion bez `completion_type` nie zasilaja metryk `full_*`.

Dlaczego to jest wazne:

- UI premium moze pokazywac skutecznosc pelnych blokow bez mieszania jej z sesjami przerwanymi recznie.
- Stare pola pozostaja kompatybilne, ale nowe pola sa bezpieczniejszym zrodlem dla komunikatow o jakosci treningu.

Weryfikacja:

- `tests/Feature/ReviewTrainerTelemetryTest.php` pilnuje, ze `full_*` rosna tylko dla `auto_full`, a `manual_partial` i legacy completion zostaja poza tym zbiorem.

### 18.19 Status po sprincie memory trainer monitoring coverage

Zrobione w tym sprincie:

- `monitoring_snapshots` dostalo addytywne liczniki:
  - `review_trainer_events_count`,
  - `review_memory_progress_count`,
  - `review_trainer_daily_answers_count`.
- `MonitoringSnapshotService` zapisuje kumulatywne wartosci nowych tabel:
  - eventy po `occurred_at`,
  - stan zweryfikowanej pamieci po `created_at`,
  - dzienny ledger trenera po `answered_at`.
- Adminowy monitoring pokazuje nowe tabele w wolumenie danych, growth rankingu, trendzie tygodniowym i osobnym wykresie "Przyrost trenera pamieci".

Dlaczego to jest wazne:

- Po oddzieleniu trenera pamieci od klasycznej nauki mamy operacyjna widocznosc, czy nowe warstwy danych rosna zdrowo.
- Snapshoty nie zmieniaja algorytmu ani UX sesji, ale daja szybki sygnal, gdy ledger albo event log zaczna puchnac szybciej niz zakladamy.

Weryfikacja:

- `tests/Feature/Console/MonitoringSnapshotCommandTest.php` pilnuje, ze snapshot zapisuje trzy nowe liczniki.
- `tests/Feature/Admin/DataMonitoringPageTest.php` pilnuje, ze panel admina renderuje nowa sekcje wzrostu trenera pamieci.

### 18.20 Status po sprincie verified summary alignment

Zrobione w tym sprincie:

- `ReviewTrainerCompletionSummaryService` zachowuje stare pole `version` dla kompatybilnosci.
- Addytywnie zwraca teraz jawne wersje:
  - `memory_signal_version`,
  - `verified_memory_signal_version`.
- Podsumowanie koncowe sesji mapuje `review_memory_progress.needs_recovery` do:
  - `leech`, gdy `leech_score >= 65`,
  - `relearning`, gdy ryzyko jest nizsze.
- Ten mapping jest zgodny z plannerem, ktory juz wczesniej rozroznial silny leech risk od zwyklego recovery.

Dlaczego to jest wazne:

- Widok wyniku koncowego i planner nie beda rozjezdzac sie w klasyfikacji tego samego pytania.
- Zachowujemy kompatybilnosc UI/API, a jednoczesnie mamy jawny kontrakt wersji verified-memory signal pod dalsze premium analytics.

Weryfikacja:

- `tests/Unit/Support/ReviewTrainerCompletionSummaryServiceTest.php` pilnuje nowych pol wersji i spójnego mappingu verified leech risk.

### 18.21 Status po sprincie verified queue preview signal

Zrobione w tym sprincie:

- `/trener-pamieci` i API kolejki nadal zwracaja kompatybilne pole `questions[].memory_signal`.
- Gdy istnieje `review_memory_progress`, preview pytania liczy `memory_signal` z `ReviewMemoryVerifiedSignalService`, a nie ze starego `user_question_progress`.
- Gdy nie ma verified memory, preview wraca do klasycznego `ReviewMemorySignalService`.
- `memory_signal.source` rozroznia:
  - `verified_memory`,
  - `classic_progress`.
- Preview pytania pokazuje `next_review_at` z `next_verified_review_at`, gdy pytanie ma verified memory.

Dlaczego to jest wazne:

- Plan, liczby w "mapie pamieci" i preview/API pytan nie beda juz opowiadac dwoch roznych historii o tym samym pytaniu.
- Klasyczny progres pozostaje fallbackiem, ale trener pamieci preferuje wlasne zrodlo prawdy.

Weryfikacja:

- `tests/Feature/ReviewQueueTest.php` pilnuje, ze pytanie recovery z `review_memory_progress` dostaje `memory_signal.source = verified_memory`, verified signal version i legacy mapping `leech/risky`.

### 18.22 Status po sprincie verified signal mapper

Zrobione w tym sprincie:

- Dodany zostal `ReviewMemoryLegacySignalMapper`.
- Jeden mapper odpowiada teraz za zgodne mapowanie `ReviewMemoryVerifiedSignalService` do starego kontraktu:
  - `plan_segment`,
  - `memory_state`,
  - preview payload `questions[].memory_signal`.
- `ReviewPlannerService`, `ReviewTrainerCompletionSummaryService` i `ReviewQueueController` korzystaja z tego samego mappingu.
- Przyjelismy zachowanie zgodne z plannerem: `due today` nie jest pokazywane jako legacy `overdue`, dopiero `overdue_days > 0` trafia do segmentu `overdue`.

Dlaczego to jest wazne:

- Dalsze zmiany leech policy i verified-memory scoringu nie beda wymagaly recznej synchronizacji trzech miejsc.
- UI nie bedzie mowil "po terminie" o pytaniu, ktore jest po prostu zaplanowane na dzisiaj.

Weryfikacja:

- `tests/Unit/Support/ReviewMemoryLegacySignalMapperTest.php` pilnuje segmentow, leech/relearning mappingu i kompatybilnego preview payloadu.

### 18.23 Status po sprincie header memory label

Zrobione w tym sprincie:

- Globalny header nie pokazuje juz tekstu `Brak zaleglych powtorek`, gdy `reviewDueCount = 0`.
- Przy dodatnim liczniku header pokazuje neutralne `X pytan w planie pamieci`.
- Dodana zostala mala odmiana etykiety pytan, zeby uniknac komunikatow typu `1 pytan`.

Dlaczego to jest wazne:

- Shared prop `studyContext.reviewDueCount` nadal jest kompatybilnym licznikiem legacy z `user_question_progress`.
- Po wprowadzeniu verified memory, daily capow i boosterow pelny plan trenera moze byc szerszy niz sam legacy due count.
- Header nie powinien wiec sugerowac, ze plan jest pusty, jezeli modul trenera pamieci moze jeszcze zaproponowac prace.

Decyzja techniczna:

- Nie uruchamiamy pelnego `ReviewPlannerService` w globalnych Inertia shared props na kazdym requestcie.
- Pelny planner zostaje w `/trener-pamieci`, gdzie jego koszt jest uzasadniony i kontrolowany.
- Header pozostaje lekki i semantycznie bezpieczny do czasu osobnego sprintu na shared summary/prefetch planu pamieci.

### 18.24 Status po sprincie scoped shared due count

Zrobione w tym sprincie:

- `StudyContextService::dueReviewCount()` nie liczy juz wszystkich historycznych due rekordow usera.
- Licznik jest filtrowany przez aktywny zakres kategorii z `StudyContextService::activeCategories()`.
- Licznik ignoruje pytania nieaktywne i pytania z `delivery_issue`.
- Dodany zostal test dla konta przypisanego do jednej kategorii: due z innej kategorii nie trafia do `studyContext.reviewDueCount`.

Dlaczego to jest wazne:

- Header i shared props nie beda pokazywaly liczby powtorek z kategorii, ktorej user nie powinien teraz przerabiac.
- To domyka bezpieczna czesc preflightu 4 bez uruchamiania pelnego planera w kazdym Inertia requestcie.

### 18.25 Status po sprincie public category start guard

Zrobione w tym sprincie:

- Webowy start sesji korzysta teraz z `StudyContextService::visibleCategoriesQuery()` zamiast z dowolnej aktywnej kategorii.
- API start sesji korzysta z tego samego publicznego zakresu kategorii.
- `assertUserCanUseCategory()` nadal pilnuje przypisanej kategorii konta, ale endpointy nie pozwalaja juz wystartowac ukrytej/testowej kategorii spoza publicznego kontekstu.
- Dodane zostaly testy web i API dla `sr_review`: kategoria `INTERNAL` z due pytaniem nie tworzy sesji i zwraca 404.

Dlaczego to jest wazne:

- `/trener-pamieci`, `/nauka` i start sesji maja ten sam kontrakt widocznosci kategorii.
- Trener pamieci nie moze zostac uruchomiony w zakresie, ktorego UI nie pokazuje uzytkownikowi.

### 18.26 Status po sprincie empty-state copy alignment

Zrobione w tym sprincie:

- Pusty stan `/trener-pamieci` nie uzywa juz naglowka `Na teraz nie ma zaleglych powtorek`.
- Coach message mowi neutralnie o braku pytan w planie pamieci.

Dlaczego to jest wazne:

- Po daily capach, boosterach i verified memory slowo `zalegle` nie jest juz dobrym opisem calego planu trenera.
- UI pozostaje spojny z decyzja, ze trener pokazuje plan pamieci, a nie tylko liste zaleglych due.

### 18.27 Status po sprincie memory-native telemetry copy

Zrobione w tym sprincie:

- Dolny pasek metryk `/trener-pamieci` nie pokazuje juz `average_score_percent` jako kafla `srednio`.
- Kafel zostal zastapiony liczba `completed_full_sessions_count` pod etykieta `pelne sesje`.
- Backendowy kontrakt telemetry zostaje kompatybilny; legacy `average_score_percent` nadal istnieje dla API/analityki, ale UI trenera nie promuje go jako glownej metryki.

Dlaczego to jest wazne:

- Trener pamieci ma weryfikowac i kondensowac wiedze, a nie budowac kolejny klasyczny procent wyniku.
- UI przesuwa akcent z `score_percent` na domykanie pelnych blokow treningu.

### 18.28 Status po sprincie memory-native plan copy

Zrobione w tym sprincie:

- Sekcja skladu sesji `/trener-pamieci` nie uzywa juz komunikatu `Bez zaleglosci`.
- Gdy po sesji nic nie zostaje w planie, UI pokazuje `Plan czysty`, czyli jezyk pasujacy do osobnego modulu weryfikacji pamieci.

Dlaczego to jest wazne:

- Trener pamieci nie jest lista zaleglych pytan, tylko dziennym planem weryfikacji i utrwalania.
- Zmiana jest wylacznie copy/UI: nie dotyka selekcji pytan, ledgerow, eventow ani kontraktu API.

### 18.29 Status po sprincie daily ledger telemetry

Zrobione w tym sprincie:

- `ReviewTrainerAnalyticsService` zwraca addytywne liczniki z `review_trainer_daily_answers`:
  - `today_answered_count`,
  - `today_correct_count`,
  - `today_unknown_count`,
  - `today_needs_recovery_count`,
  - `today_review_day`.
- Dolny pasek `/trener-pamieci` pokazuje teraz `odzyskane` oraz `do odzyskania` z dziennego ledgeru trenera, zamiast uzywac klasycznego procentu lub samej liczby sesji.
- Legacy event telemetry zostaje kompatybilne: `completed_full_sessions_count`, `average_score_percent` i `full_*` nadal sa w kontrakcie API/analityki.

Dlaczego to jest wazne:

- UI zaczyna mowic jezykiem pamieci: ile dzisiaj faktycznie odzyskano i ile trzeba jeszcze odzyskac.
- Liczniki sa liczone wylacznie z `sr_review`, wiec klasyczna nauka, Zen mode, PJM i egzamin nie zanieczyszczaja metryk trenera.
- Zmiana nie dotyka planera ani zapisu odpowiedzi; to bezpieczny krok prezentacyjno-analityczny nad istniejacym ledgerem.

### 18.30 Status po sprincie visible unknown metric

Zrobione w tym sprincie:

- Dolny pasek `/trener-pamieci` pokazuje teraz jawny kafel `nie wiem` z `today_unknown_count`.
- Kafel `ostatnio` zostal usuniety z widoku, bo bazowal na legacy event telemetry i byl mniej wazny niz dzisiejszy sygnal pamieciowy.
- Kafel `do odzyskania` zostaje jako laczna liczba odpowiedzi wymagajacych powrotu, czyli obejmuje zarowno `Nie wiem`, jak i bledne odpowiedzi wyboru.

Dlaczego to jest wazne:

- Uczciwe `Nie wiem` jest osobnym edge casem produktowym: nie jest przypadkowym strzalem i nie powinno znikac w zwyklym wyniku procentowym.
- UI lepiej komunikuje mechanike trenera: rozdzielamy odzyskane, niewiedziane i material do odzyskania.
- Zmiana jest frontendowa i korzysta z juz dodanych pol telemetry, bez zmian w plannerze, sesjach ani klasycznym progresie.

### 18.31 Status po sprincie choice-vs-unknown telemetry split

Zrobione w tym sprincie:

- `ReviewTrainerAnalyticsService` dodal addytywne pole `today_choice_incorrect_count`.
- Pole liczy dzisiejsze odpowiedzi `sr_review`, gdzie user wybral odpowiedz A/B/C i byla ona bledna.
- `today_unknown_count` dalej liczy tylko jawne `Nie wiem`.
- `today_needs_recovery_count` zostaje lacznym koszykiem do powrotu, czyli `unknown + bledne choice`.

Dlaczego to jest wazne:

- To domyka edge case z naszej decyzji produktowej: uczciwe `Nie wiem` i bledny wybor/strzal nie sa tym samym sygnalem.
- Analytics moze odroznic brak odzyskania wiedzy od aktywnego blednego wyboru bez mieszania tych danych z klasyczna nauka, Zen, PJM ani egzaminem.
- Zmiana jest addytywna dla API i nie zmienia zachowania planera ani zapisu odpowiedzi.

### 18.32 Status po sprincie scoped daily telemetry test

Zrobione w tym sprincie:

- Dodany zostal test, ze dzienne liczniki telemetry trenera szanuja:
  - wybrana kategorie,
  - liste dozwolonych kategorii z aktywnego kontekstu usera.
- Test obejmuje `today_answered_count`, `today_correct_count`, `today_unknown_count`, `today_choice_incorrect_count` i `today_needs_recovery_count`.

Dlaczego to jest wazne:

- Trener pamieci nie moze mieszac dziennego progresu miedzy kategoriami.
- To zabezpiecza przypadek, w ktorym user ma dane w kilku kategoriach, ale aktualny widok albo konto pracuje tylko na jednej.
- Zmiana jest testowa/dokumentacyjna i nie zmienia runtime.

### 18.33 Status po sprincie web plan-first queue payload

Zrobione w tym sprincie:

- Webowy `/trener-pamieci` nie buduje juz preview pytan z mediami.
- `ReviewQueueController@index` przekazuje do Inertia pusty `questions`, ale zachowuje plan, kategorie, telemetry, `preview_count` i CTA.
- API `api/v1/me/review-queue` zostaje kompatybilne i nadal zwraca `data.questions` z preview payloadem.
- Testy zostaly przestawione tak, zeby:
  - web sprawdzal plan-first payload bez pytan,
  - API nadal pilnowalo preview pytan, mediow oraz verified-memory signal.

Dlaczego to jest wazne:

- Ekran trenera pamieci jest misja dnia, nie lista pytan z bazy.
- Unikamy niepotrzebnego eager-loadu `question.media`, `question.licenseCategory` i `question.questionTopic` przy zwyklym wejsciu na `/trener-pamieci`.
- Zmiana nie rusza startu sesji, planera, daily ledgeru ani klasycznego progresu.

### 18.34 Status po sprincie optional API plan-first payload

Zrobione w tym sprincie:

- API `api/v1/me/review-queue` obsluguje opcjonalny parametr `include_questions=0`.
- Domyslne API pozostaje kompatybilne i nadal zwraca `data.questions`.
- Przy `include_questions=0` API zwraca plan, kategorie i telemetry, ale `data.questions` jest puste.
- Dodany zostal test kontraktu, ze plan-first API nadal niesie `ready_for_review_count`, `plan.due_count`, `plan.preview_count` i `recommended_question_count`.

Dlaczego to jest wazne:

- To bezpieczny most do docelowego kontraktu plan-first bez przerywania obecnych klientow.
- Mobile albo przyszly frontend moze uniknac preview pytan i mediow, gdy potrzebuje tylko misji dnia.
- Zmiana jest addytywna i nie dotyka startu sesji ani algorytmu planera.

### 18.35 Status po sprincie web payload query guard

Zrobione w tym sprincie:

- Dodany zostal test regresji, ze zwykle wejscie na `/trener-pamieci` nie wykonuje zapytan do `question_media`.
- Test nadal tworzy media dla pytania, wiec pilnuje realnego przypadku: gdy preview mialoby byc eager-loadowane, zapytanie pojawiloby sie w logu.
- API preview nadal ma osobne testy i pozostaje kompatybilne.

Dlaczego to jest wazne:

- Chroni optymalizacje plan-first przed przypadkowym cofnieciem.
- Wzmacnia decyzje, ze webowy trener pamieci jest ekranem misji dnia, a nie lista pytan z mediami.
- Zmiana nie dotyka runtime; to zabezpieczenie testowe.

### 18.36 Status po sprincie plan-first preview id cleanup

Zrobione w tym sprincie:

- Payload plan-first usuwa wewnetrzne `preview_question_ids`.
- Dotyczy to webowego `/trener-pamieci` oraz API `api/v1/me/review-queue?include_questions=0`.
- Domyslne API z preview pytaniami nadal zachowuje `preview_question_ids`, zeby nie zlamac kompatybilnego kontraktu.

Dlaczego to jest wazne:

- Plan-first payload powinien niesc misje dnia i liczniki, nie techniczne ID pytan preview.
- Zmniejszamy payload i ograniczamy niepotrzebne szczegoly implementacyjne po stronie klienta.
- Zmiana jest warunkowa i nie dotyka planera ani startu sesji.

### 18.37 Status po sprincie include_questions contract hardening

Zrobione w tym sprincie:

- API `api/v1/me/review-queue` waliduje teraz `include_questions` jako jawna wartosc logiczna.
- Obslugiwane sa czytelne warianty `0/1`, `true/false`, `yes/no` oraz `on/off`.
- Niepoprawny parametr zwraca normalne `422` z bledem walidacji, zamiast cicho wybierac przypadkowe zachowanie.
- `docs/API-SPEC.md` opisuje kontrakt plan-first i kompatybilny preview payload.

Dlaczego to jest wazne:

- Przyszly klient mobilny albo frontend moze bezpiecznie prosic o lekki plan dnia bez preview pytan.
- Kontrakt jest jawny: domyslne API nadal zachowuje kompatybilnosc, a plan-first nie ujawnia technicznych ID preview.
- Zmiana nie dotyka planera, sesji, klasycznej nauki, Zen, PJM ani egzaminu.

### 18.38 Status po sprincie sr_review topic groups skip

Zrobione w tym sprincie:

- Ekran sesji `sr_review` nie buduje juz `topicGroups` dla calej kategorii.
- Klasyczna nauka, PJM, egzamin i pozostale tryby zachowuja dotychczasowy kontrakt propsow.
- Test `review alias normalizes to sr review mode and only pulls due questions from the user review queue` tworzy temat pytan i pilnuje, ze `sr_review` zwraca `topicGroups` jako pusta kolekcje.

Dlaczego to jest wazne:

- Topic navigator nie jest uzywany w trenerze pamieci, a jego budowanie skanuje pytania i progres calej kategorii.
- Przy sesjach 50/80 ten koszt mogl dokladac odczuwalne opoznienie do wejscia w trening.
- Zmiana jest izolowana do renderowania strony sesji i nie dotyka selekcji pytan, odpowiedzi, planera, wynikow ani klasycznego progresu.

### 18.39 Status po sprincie sr_review lean session payload

Zrobione w tym sprincie:

- In-progress sesja `sr_review` nie wysyla juz `questionPool` z pelnym zestawem pytan nawet przy malych sesjach.
- `sr_review` nie wysyla tez startowego `prefetchedQuestions`; pierwsze wejscie w trening dostaje tylko aktualne pytanie, liste ID i lekki stan sesji.
- Frontend nie uruchamia juz klientowego batch-prefetchu dla `sr_review`, bo po zapisaniu odpowiedzi backend zwraca server-driven `nextQuestion`.
- Klasyczna nauka i PJM zachowuja dotychczasowe zachowanie: male sesje moga miec pelny pool, a wieksze sesje korzystaja z okna/prefetchu.

Dlaczego to jest wazne:

- To realizuje kolejny punkt z planu `odchudzic payload sesji dla trenera`.
- Przy docelowych blokach 50/80 trener pamieci nie powinien zachowywac sie jak klasyczna lista pytan do lokalnego przeskakiwania.
- Zmiana wzmacnia osobny model trenera: backend prowadzi nastepny krok, a UI nie laduje z gory materialu, ktory nie jest jeszcze potrzebny.
- Nie zmieniamy wyboru pytan, zapisu odpowiedzi, daily ledgeru, klasycznej nauki, PJM, Zen ani egzaminu.

### 18.40 Status po sprincie PJM asset payload isolation

Zrobione w tym sprincie:

- `StudySessionManager::orderedQuestions()` i `currentQuestion()` laduja `activeSignLanguageAssets` tylko dla sesji `mode = pjm`.
- `StudySessionController` serializuje `sign_language_assets` tylko dla PJM.
- Klasyczna nauka, Zen, egzamin i `sr_review` dostaja puste `sign_language_assets` i nie powinny pytac bazy o `question_sign_language_assets` podczas renderu pytania.
- Test klasycznej sesji z przypietym assetem PJM pilnuje, ze payload nie niesie PJM-assets i nie wykonuje query do `question_sign_language_assets`.

Dlaczego to jest wazne:

- Domyka punkt `nie eager-loadowac ani nie serializowac activeSignLanguageAssets poza PJM`.
- Zmniejsza payload i ryzyko przypadkowego mieszania modulu PJM z klasyczna nauka, Zen oraz trenerem pamieci.
- PJM zachowuje swoje assety, bo tryb `mode = pjm` nadal dolacza relacje i payload.

### 18.41 Status po sprincie daily count indexes

Zrobione w tym sprincie:

- Fallback dziennego licznika trenera nie uzywa juz `whereDate()` na `study_session_answers.answered_at`.
- Liczenie raw odpowiedzi `sr_review` przechodzi po zakresie dnia:
  - `answered_at >= start dnia`,
  - `answered_at < start nastepnego dnia`.
- Ledger dzienny filtruje `review_day` zakresem dnia bez `whereDate()`.
- Dodane zostaly indeksy:
  - `study_sessions(user_id, mode, license_category_id, id)`,
  - `study_session_answers(study_session_id, answered_at)`.

Dlaczego to jest wazne:

- Dzienny target 80 i pierwszy blok 50 beda liczone na indeksowalnych warunkach, takze w okresie przejsciowym, gdy planner musi doliczyc raw odpowiedzi nieobecne jeszcze w ledgerze.
- Zmiana nie dotyka selekcji pytan, scoringu pamieci, PJM, klasycznej nauki, Zen ani egzaminu.
- Domyka techniczna czesc punktu 29 bez wymuszania jeszcze materializowanego agregatu dziennego.

### 18.42 Status po sprincie response time policy guard

Zrobione w tym sprincie:

- Potwierdzona zostala polityka `response_time_ms` dla `sr_review`:
  - czas odpowiedzi jest zapisywany przy `study_session_answers`,
  - planner moze go uzywac do neutralnej estymacji dlugosci sesji,
  - verified memory nie uzywa czasu jako kary.
- Dodany zostal test regresyjny dla poprawnej odpowiedzi `sr_review` z bardzo dlugim `response_time_ms`.
- Test pilnuje, ze mimo dlugiego czasu:
  - odpowiedz pozostaje `RESULT_CORRECT`,
  - stan pamieci przechodzi do `STATE_REVIEW`,
  - `verified_incorrect_count` i `verified_unknown_count` nie rosna,
  - nastepna powtorka jest liczona jak po poprawnej odpowiedzi.

Dlaczego to jest wazne:

- Czas odpowiedzi pozostaje metryka analityczna i UX-owa, nie ukryta kara za wolniejsze czytanie albo potrzebe skupienia.
- To domyka punkt 42 i trzyma sie ustalenia produktowego, ze trener pamieci nie jest trybem na czas.

### 18.43 Status po sprincie prune retention guard

Zrobione w tym sprincie:

- Dodany zostal test dla `ops:prune-study-history`, ktory usuwa stara zakonczona sesje `sr_review` razem z raw odpowiedzia.
- Komenda prune jawnie sprzata `study_session_answers` i `review_trainer_daily_answers` oraz odcina `review_memory_progress.last_study_session_answer_id`, zamiast polegac wylacznie na kaskadach konkretnego silnika bazy.
- Eventy `review_trainer_events` zachowuja telemetry, ale po prune dostaja `study_session_id = null`, tak jak zaklada kontrakt `nullOnDelete`.
- Test pilnuje, ze:
  - `study_sessions` i `study_session_answers` starej sesji sa usuwane,
  - `review_trainer_daily_answers` znika razem z raw odpowiedzia,
  - `review_memory_progress` zostaje jako aktualny stan zweryfikowanej pamieci,
  - `last_study_session_answer_id` przechodzi na `null` przez `nullOnDelete`.
- Potwierdzony zostal obecny schedule produkcyjny:
  - dzienne agregaty pytan okolo `02:55`,
  - monitoring okolo `03:00`,
  - prune historii okolo `03:20`.

Dlaczego to jest wazne:

- Trener pamieci nie traci stanu wiedzy po retencji raw historii sesji.
- Daily ledger moze pozostac answer-level licznikiem dziennym, a dlugoterminowa pamiec zostaje w `review_memory_progress`.
- Domyka to operacyjna czesc punktu 38 bez wprowadzania nowego agregatu dziennego.

### 18.44 Status po sprincie active review lifecycle guard

Zrobione w tym sprincie:

- Aktywna sesja `sr_review` jest zamykana, gdy przestaje nalezec do aktualnego zakresu kategorii usera.
- Po wygasnieciu pelnego dostepu aktywny `sr_review` jest zamykany przed przekierowaniem do aktywacji dostepu.
- Webowe `/nauka/teraz` nie pokazuje starego planu pamieci po zmianie kategorii.
- API nie pozwala zapisac odpowiedzi do aktywnego `sr_review`, ktory jest poza aktualnym category scope.
- Telemetry dostaje `review.replaced` z:
  - `replacement_reason = category_scope_changed`,
  - albo `replacement_reason = access_lost`.

Dlaczego to jest wazne:

- Po zmianie kategorii przez admina user nie kontynuuje przypadkiem starego planu pamieci.
- Po odnowieniu dostepu trener przeliczy plan od nowa, zamiast wracac do sesji rozpoczętej przed przerwa w dostepie.
- Zachowujemy obecny model jednego aktywnego slotu sesji, ale bez cichego mieszania planow.

### 18.45 Status po sprincie candidate source contract

Zrobione w tym sprincie:

- `ReviewPlannerService` zwraca teraz jawne zrodla kandydatow:
  - `candidate_source_counts.primary` dla pilnych due/recovery,
  - `candidate_source_counts.seen_booster` dla bezpiecznych boosterow z materialu juz widzianego,
  - `candidate_source_counts.new_candidate` dla przyszlego zrodla nowych kandydatow.
- `new_candidate_count` jest obecnie zawsze `0`, bo nie podjelismy jeszcze decyzji, ze trener pamieci moze wciagac zupelnie nowe pytania.
- Dotychczasowe pola `due_count`, `candidate_count`, `booster_count` i `recommended_question_count` zostaja kompatybilne.
- Stored `review_plan`, API sesji i telemetry `review.session_started`/`review.completed` niosa ten sam kontrakt zrodel kandydatow.
- Nie zmienilismy selekcji pytan: sesja nadal moze byc dopelniana tylko bezpiecznym boosterem z `user_question_progress.total_attempts > 0`.

Dlaczego to jest wazne:

- Punkt 39 jest teraz mierzalny: widzimy, czy sesja sklada sie z realnych powtorek, boosterow czy potencjalnie nowych kandydatow.
- Mozemy bezpiecznie dopracowac UI/copy dla malych planow, zanim zdecydujemy o dodawaniu nowych pytan.
- Chronimy zalozenie produktowe, ze `Trener pamieci` weryfikuje wiedze, a nie miesza sie z pierwsza nauka.

### 18.46 Status po sprincie short safe plan copy

Zrobione w tym sprincie:

- `/trener-pamieci` pokazuje krotki komunikat, gdy rekomendowana sesja jest mniejsza niz pierwszy blok 50.
- Komunikat pojawia sie tylko wtedy, gdy:
  - `recommended_question_count > 0`,
  - `recommended_question_count < minimum_session_question_count`,
  - `new_candidate_count = 0`.
- Copy wyjasnia, ze to bezpieczny zestaw i ze nowe pytania nie sa dokladane bez wczesniejszej ekspozycji.
- Nie dodajemy nowych kontrolek, ustawien ani recznego zarzadzania kolejka.

Dlaczego to jest wazne:

- User nie odbierze malej sesji jako bledu systemu.
- UI zachowuje kierunek "kliknij i jedziemy", ale nie obiecuje sztucznego dopelniania do 50.
- Zostawiamy miejsce na przyszla decyzje o `new_candidate`, bez zmiany algorytmu w tym sprincie.

### 18.47 Status po sprincie limited first exposure candidates

Zrobione w tym sprincie:

- `ReviewPlannerService` przeszedl na `review-planner-v2`.
- `new_candidate` nie jest juz stale `0`, ale ma kontrolowane limity:
  - maksymalnie `15` nowych pytan dziennie,
  - maksymalnie `30%` docelowego bloku sesji,
  - tylko wtedy, gdy `primary + seen_booster` nie wypelnia celu sesji.
- Nowe kandydaty sa pobierane z aktywnych pytan w aktualnym zakresie kategorii usera.
- Nowy kandydat nie moze miec:
  - klasycznego `user_question_progress`,
  - `review_memory_progress`,
  - odpowiedzi zapisanej dzisiaj.
- Po odpowiedzi w `sr_review` nowe pytanie zapisuje tylko `review_memory_progress` i dzienny ledger; klasyczny progres nie jest tworzony ani podbijany.
- Planner umie potem znalezc pytania, ktore istnieja tylko w `review_memory_progress`, wiec pierwsza ekspozycja nie wypada z cyklu powtorek.
- API `/api/v1/me/review-queue` pokazuje nowe kandydaty jako `memory_signal.source = new_candidate`.
- `/trener-pamieci` pokazuje osobna liczbe `Nowe` i wyjasnia, ze pierwsza odpowiedz otwiera cykl pamieci, a potwierdzenie przyjdzie w kolejnej powtorce.
- Idempotencja aktywnej sesji `sr_review` uwzglednia teraz `planner_version`, zeby stary plan dnia nie maskowal zmiany polityki.

Dlaczego to jest wazne:

- Trener moze utrzymac rytm pracy, gdy due/recovery jest za malo, ale nie zmienia sie w losowa liste nowych pytan.
- Poprawny strzal w nowe pytanie nie robi z niego od razu potwierdzonej pamieci.
- Klasyczna nauka, Zen, PJM i egzamin pozostaja odseparowane od verified memory.
- Mamy jasna telemetry: `primary`, `seen_booster`, `new_candidate`, co pozwala pozniej stroic limity na danych.

### 18.48 Status po sprincie sr_review reveal hydration

Zrobione w tym sprincie:

- Naprawiona zostala regresja w aktywnej sesji `sr_review`, w ktorej po odpowiedzi karta wyjasnienia nadal czytala dane z pre-answer `activeQuestion`.
- Frontend ma teraz jedno miejsce wyboru zrodla wyjasnienia:
  - najpierw answer reveal zapisany w `localResults`,
  - dopiero potem pre-answer payload pytania.
- Dzieki temu po odpowiedzi w trenerze pamieci widoczne sa:
  - tekstowe `explanation`,
  - `explanation_asset`,
  - `explanation_annotations` na obrazie albo klatce filmu.
- API `api/v1/sessions/{session}/answers` dla `sr_review` zwraca teraz reveal dla wlasnie ocenionego pytania:
  - `correct_answer`,
  - `explanation`,
  - `explanation_asset`,
  - `explanation_annotations`.
- `next_question` w web/API nadal nie zawiera `correct_answer`, tekstowego `explanation`, `explanation_asset` ani `explanation_annotations`.
- Dodane zostaly testy regresyjne dla web answer response, API answer response oraz frontendowego wyboru zrodla wyjasnienia.

Dlaczego to jest wazne:

- Domyka praktycznie punkt 14: nie ujawniamy odpowiedzi przed kliknieciem, ale po odpowiedzi trener ma realna warstwe dydaktyczna.
- Przygotowuje grunt pod integracje z adaptive learning: bledna odpowiedz w trenerze moze pokazac pomoc edukacyjna bez oslabiania czystosci sygnalu `verified memory`.
- Klasyczna nauka, PJM, Zen i egzamin nie zmieniaja kontraktu.

## 19. Definition of success

Uznajemy, ze premium review trainer jest dowieziony, gdy:

- user moze klikac praktycznie tylko `Powtarzaj`,
- system sam podsuwa sensowny batch,
- stale problematyczne pytania sa traktowane inteligentniej,
- kolejka due jest kontrolowana i nie zalewa usera,
- `Trener pamieci` rozdziela ekspozycje od zweryfikowanej pamieci,
- przypadkowy poprawny strzal poza review nie podnosi pytania do statusu `verified memory`,
- metryki retencji po czasie rosna,
- mozemy porownywac wersje algorytmu,
- review jest realna przewaga produktu, a nie dodatkiem.

## 20. Decyzja kanoniczna

Przyjmujemy, ze:

- `Trener pamieci` zostaje jako funkcja,
- obecny `sr_review` jest baseline v1,
- docelowy kierunek to `premium trainer memory engine`,
- rozbudowa ma byc fazowa,
- event log i versioned policy sa obowiazkowe,
- `user_question_progress` pozostaje ogolnym progresem nauki,
- `review_memory_progress` jest rekomendowanym miejscem na zweryfikowana pamiec,
- UI powinno prowadzic usera do "kliknij i jedziemy", a nie do recznej konfiguracji kolejki.
