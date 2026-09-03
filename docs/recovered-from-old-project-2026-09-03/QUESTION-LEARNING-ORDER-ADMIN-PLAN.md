# Reczna kolejnosc pytan w nauce - dokumentacja i plan wdrozenia

## Cel

Chcemy przejac pelna kontrole nad "stala kolejnoscia" pytan w trybach:

- `Nauka klasyczna`,
- `Zen mode`.

Obecnie uzytkownik moze wybrac:

- `Stala kolejnosc`,
- `Losowa kolejnosc`.

Problem: aktualna "stala kolejnosc" nie jest redakcyjnie ulozona. Jest wynikiem technicznego sortowania po polach `difficulty` i `published_at`, a przy wielu remisach kolejnosc moze byc praktycznie przypadkowa z punktu widzenia zespolu.

Docelowo administrator ma miec w panelu admina zakladke do recznego ukladania kolejnosci pytan w obrebie kategorii prawa jazdy i dzialu.

Ten dokument opisuje aktualny stan, decyzje architektoniczne, ryzyka, plan implementacji i kryteria gotowosci.

## Status prac

Stan na teraz:

- [x] Utworzono branch roboczy: `codex/question-order-admin`.
- [x] Przeanalizowano frontend `/nauka`.
- [x] Przeanalizowano backend startu sesji.
- [x] Przeanalizowano aktualne indeksy bazy pod sesje.
- [x] Przeanalizowano import i przypisywanie pytan do dzialow.
- [x] Przeanalizowano zaleznosci z rekordami dzialow.
- [x] Przeanalizowano ryzyko dla adnotacji, pogrubien i grafik pomocniczych.
- [x] Ustalono rekomendowany model danych.
- [x] Doprecyzowano wzorce migracji, modeli, Filament page i testow admina.
- [x] Sprawdzono ryzyka systemowe: autoryzacja admina, SQLite w testach, publikacja aktywnego setu.
- [x] Przeskanowano realna baze PostgreSQL runtime i lokalny snapshot SQLite.
- [x] Sprawdzono wplyw aktualizacji tresci pytan, odpowiedzi i wyjasnien na planowana kolejnosc.
- [x] Sprawdzono dodatkowe wejscia startu sesji: `/nauka`, ekran podsumowania, lista postepu i boczny panel dzialow.
- [x] Implementacja migracji.
- [x] Implementacja modeli i serwisu kolejnosci.
- [x] Wpiecie w start sesji nauki.
- [x] Panel admina MVP.
- [x] Testy backendowe.
- [x] Testy administracyjne.
- [ ] Build frontendu.
- [ ] Manualne QA.

Ten dokument nie jest implementacja.

## Pre-flight check bazy i zaleznosci

### Ktora baza jest zrodlem prawdy

`.env` wskazuje na PostgreSQL:

```text
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=prawkobit
```

Plik:

```text
database/database.sqlite
```

jest starszym lokalnym snapshotem. Ma pytania, ale nie ma wszystkich nowszych tabel, np. tabel assetow wyjasnien i PJM. Nie traktujemy go jako zrodla prawdy dla tej funkcjonalnosci.

Pre-flight check decyzyjny byl wykonany na PostgreSQL runtime.

### Stan migracji PostgreSQL

W PostgreSQL sa zastosowane migracje do:

```text
2026_05_22_150000_create_user_topic_completion_records_table
```

Liczba migracji w runtime:

```text
74
```

Nie istnieja jeszcze tabele:

```text
question_learning_order_sets
question_learning_order_items
```

Nie ma wiec kolizji nazw z planowana funkcjonalnoscia.

### Liczebnosc danych runtime

Stan PostgreSQL runtime:

```text
questions: 17 044
questions_active: 17 044
questions_active_ready: 17 044
question_topics: 31
license_categories: 12
study_sessions: 768
study_session_answers: 11 488
user_question_progress: 2 825
user_topic_completion_records: 11
question_media: 23 345
question_explanation_assets: 11
question_explanation_annotations: 2 195
shared_question_explanation_assets: 69
question_sign_language_assets: 1 976
```

Wszystkie 12 kategorii maja aktywne gotowe pytania:

```text
B: 2 194
AM: 1 513
C: 1 498
D: 1 492
D1: 1 479
C1: 1 445
A: 1 428
B1: 1 424
A1: 1 414
A2: 1 412
T: 1 306
PT: 439
```

Wniosek: panel admina musi obslugiwac wszystkie 12 kategorii, nie tylko glowny zestaw `A/B/C/D/T`.

### Rozmiar dzialow

Runtime ma:

```text
368 bucketow category + topic
srednio 46.3 pytania na bucket
max 165 pytan w buckecie
min 1 pytanie w buckecie
```

Najwieksze buckety sa w praktyce duzo mniejsze niz zakladalismy po lokalnym SQLite, ale UI nadal powinno byc odporne na setki pytan, bo importy i zakresy moga sie zmieniac.

Sa tez male buckety z 1-3 pytaniami. Panel admina musi dobrze obslugiwac krotkie listy, bez wymuszania niepotrzebnej paginacji/drag flow.

### Scope pytan

Rozklad `metadata.structure_scope`:

```text
PODSTAWOWY: 14 798
SPECJALISTYCZNY: 2 246
```

Nie znaleziono aktywnych gotowych pytan z nieznanym scope.

### Jakosc danych dla kolejnosci

W runtime nie znaleziono:

```text
active_ready_without_topic: 0
active_ready_missing_external_id: 0
active_ready_missing_prompt: 0
active_ready_unknown_scope: 0
requires_primary_media_without_media: 0
```

To znaczy, ze aktualna pula jest gotowa do recznego ukladania kolejnosci bez masowego czyszczenia danych przed startem feature.

### Powtarzalnosc `external_id`

`external_id` powtarza sie miedzy kategoriami:

```text
questions: 17 044
distinct_external_ids: 3 576
cross_category_repeated_external_ids: 13 468
```

To jest spodziewane, ale wazne dla admina:

- zapis kolejnosci musi uzywac lokalnego `questions.id`,
- `external_id` moze byc tylko etykieta pomocnicza,
- UI powinno zawsze pokazywac kategorie przy `external_id`,
- import/export kolejnosci nie moze identyfikowac pytan samym `external_id` bez kategorii.

### Aktualna "stala kolejnosc" nadal jest niekontrolowana

W PostgreSQL runtime sa duze grupy remisow dla obecnego sortowania:

```text
tie_groups: 2 920
questions_in_tie_groups: 15 149
largest_tie_group: 37
```

To potwierdza problem: obecne sortowanie po `difficulty` i `published_at` nie daje redakcyjnej kontroli. Dodanie `questions.id ASC` stabilizuje fallback, ale nie rozwiazuje potrzeby recznej kolejnosci.

### Zaleznosci z usprawnieniami nauki

Runtime zawiera aktywne dane dla usprawnien:

```text
questions_with_explanation_assets: 11
questions_with_annotations: 1 694
questions_with_sign_language_assets_by_external_id: 9 085
```

Wniosek bez zmian: reczna kolejnosc nie moze dotykac assetow i adnotacji. Kolejnosc zapisujemy po `question_id`, a adnotacje/asset payload buduje sie pozniej dla konkretnego pytania.

## Aktualizacje tresci pytan, odpowiedzi i wyjasnien

### Szybka edycja w widoku nauki

Glowne miejsca:

```text
resources/js/Pages/StudySessions/Show.vue
app/Http/Controllers/AdminQuestionPromptController.php
app/Http/Controllers/AdminQuestionExplanationController.php
app/Support/SharedQuestionScopeService.php
```

Szybka edycja z ekranu nauki obsluguje aktualnie:

- tresc pytania (`prompt`),
- wyjasnienie (`explanation`).

Nie obsluguje tam edycji wariantow odpowiedzi ani `correct_answer`.

Zakres `Wszystkie pytania z tym samym numerem zrodlowym` dziala po:

```text
external_id + source
```

Jezeli `source` jest uzupelnione, aktualizacja nie idzie po samym `external_id`, tylko dodatkowo zawedza do tego samego zrodla. To wazne, bo `external_id` powtarza sie miedzy kategoriami.

Wniosek dla recznej kolejnosci: takie aktualizacje nie powinny psuc kolejnosci, bo nie zmieniaja `questions.id`. Order sety trzymaja `question_id`, a nie kopie tresci pytania.

### Pelna edycja pytania w panelu admina

Glowne miejsca:

```text
app/Filament/Resources/Questions/Schemas/QuestionForm.php
app/Filament/Resources/Questions/Pages/EditQuestion.php
app/Filament/Resources/Questions/Pages/Concerns/InteractsWithQuestionExplanationAsset.php
```

Pelny formularz Filament zapisuje na rekordzie pytania:

- `prompt`,
- `explanation`,
- `option_a`,
- `option_b`,
- `option_c`,
- `correct_answer`,
- `question_type`,
- `difficulty`,
- `points`,
- `is_active`,
- `published_at`,
- kategorie/dzial i metadane, jezeli sa edytowane.

Wspolny zakres `Wszystkie pytania z tym samym numerem zrodlowym` w formularzu dotyczy obecnie grafik wyjasnien i adnotacji, nie standardowego zapisu odpowiedzi A/B/C ani `correct_answer`.

Wniosek: zmiana tresci/odpowiedzi jednego pytania nie wymaga zmiany order setu, dopoki nie zmienia `questions.id`, kategorii, dzialu, aktywnosci albo gotowosci delivery.

### Import i masowe aktualizacje pytan

Glowne miejsca:

```text
app/Support/QuestionCatalogImporter.php
app/Support/AdminQuestionService.php
app/Support/QuestionTopicAssigner.php
```

Importer aktualizuje pytanie po:

```text
license_category_id + external_id
```

Gdy znajdzie istniejacy rekord, nadpisuje m.in.:

- tresc pytania,
- wyjasnienie,
- warianty odpowiedzi,
- poprawna odpowiedz,
- trudnosc,
- punkty,
- typ pytania,
- aktywnosc,
- source,
- published_at,
- metadata,
- media.

Potem uruchamia przypisanie dzialu (`QuestionTopicAssigner`) i synchronizacje gotowosci delivery.

Wniosek: masowa aktualizacja pytan i odpowiedzi nie powinna uszkodzic samej recznej kolejnosci, jezeli rekordy sa aktualizowane w miejscu. Ryzyko pojawia sie wtedy, gdy import:

- przeniesie pytanie do innego dzialu,
- zmieni kategorie,
- dezaktywuje pytanie,
- ustawi problem delivery,
- utworzy nowy rekord zamiast zaktualizowac istniejacy.

Dlatego runtime kolejnosci musi ignorowac stale pozycje i dopinac brakujace pytania fallbackiem, a panel admina musi pokazywac braki/stale pozycje po imporcie.

### Trwajace i historyczne sesje

Sesja zapisuje w payloadzie tylko liste ID:

```text
study_sessions.payload.question_ids
```

Widok sesji i API pobieraja aktualna tresc pytan z tabeli `questions`, m.in.:

```text
prompt
explanation
option_a
option_b
option_c
correct_answer
```

To znaczy:

- nowe sesje zobacza aktualna tresc/odpowiedzi,
- trwajaca sesja po kolejnym pobraniu danych lub odswiezeniu tez powinna widziec aktualny rekord pytania,
- frontend moze miec chwilowy lokalny cache aktywnego pytania, wiec zmiana nie musi pojawic sie magicznie w juz otwartym ekranie bez reloadu albo kolejnego requestu.

### Najwazniejsze ryzyko: zmiana poprawnej odpowiedzi

Zapis odpowiedzi w sesji dziala tak, ze w momencie odpowiedzi system porownuje wybor uzytkownika z aktualnym:

```text
questions.correct_answer
```

i zapisuje wynik logiczny do:

```text
study_session_answers.is_correct
study_sessions.correct_answers_count
study_sessions.score_percent
user_question_progress
```

Wniosek: jezeli pozniej zmienimy `correct_answer`, stare odpowiedzi i statystyki nie przelicza sie automatycznie. To nie psuje recznej kolejnosci, ale moze oznaczac, ze historia wynikow pozostaje policzona wedlug poprzedniej wersji odpowiedzi.

Rekomendowana decyzja produktowo-techniczna:

- zmiany tresci pytania, wyjasnien i wariantow odpowiedzi traktujemy jako bezpieczne dla kolejnosci,
- zmiane `correct_answer` traktujemy jako korekte merytoryczna z osobna polityka,
- w pierwszej wersji nie przeliczamy historii automatycznie,
- jezeli bedziemy masowo poprawiac `correct_answer`, warto dodac osobny raport/backfill do przeliczenia dotknietych sesji albo przyjac jawnie, ze historia zostaje snapshotem z czasu odpowiedzi.

## Dodatkowe wejscia startu sesji i ryzyka regresji

### Jeden wspolny backend startu

Kluczowy wniosek: prawie wszystkie wejscia do nauki klasycznej i Zen przechodza przez ten sam backend:

```text
POST /study-sessions
StudySessionController::store()
StudySessionManager::start()
StudySessionManager::questionQuery()
```

To znaczy, ze reczna kolejnosc powinna byc wpieta centralnie w `StudySessionManager`, a nie osobno w kazdy przycisk frontendu.

### Wejscia z `/nauka`

Glowne miejsce:

```text
resources/js/Pages/Session/Index.vue
```

Wejscia:

- `startLearning()` - standardowy start klasycznej nauki, Zen albo egzaminu,
- `startGlobalIncorrectLearning()` - powtorka blednych pytan z calej kategorii,
- wybor dzialu z dropdownu,
- przycisk nastepnego dzialu na panelu `/nauka`,
- przelaczanie `Stala kolejnosc` / `Losowa kolejnosc`,
- przelaczanie `Wszystkie` / `Podstawowe` / `Specjalistyczne`,
- przelaczanie statusu pytan.

Wniosek: reczna kolejnosc ma dzialac tylko dla:

```text
mode = learn
randomize_order = false
question_topic_id != null
```

Powtorka bledow z calej kategorii ma `question_topic_id = null`, wiec nie powinna korzystac z recznego order setu dzialu.

### Wejscia z ekranu podsumowania

Glowne miejsce:

```text
resources/js/Pages/StudySessions/Show.vue
```

Wejscia:

- `startFollowUpSession()` - popraw bledne pytania albo powtorz aktualny dzial,
- `startNextTopicSession()` - przejdz do nastepnego dzialu,
- `startCompletionProgressTopicSession()` - klikniecie dzialu w lewym panelu postepu,
- `startPjmFollowUpSession()` - osobna sciezka PJM.

Wazne zachowanie:

- skróty z podsumowania dziedzicza `ui_shell`, wiec klasyczna nauka zostaje klasyczna, a Zen zostaje Zen,
- skróty dziedzicza `randomize_order` z aktualnej sesji,
- klikniecie dzialu w panelu postepu wymusza `question_status = all` i `question_scope = all`,
- PJM idzie przez `session.pjm.store`, wiec nie powinien dostac recznej kolejnosci w pierwszej wersji.

Wniosek: testy musza sprawdzic, ze reczna kolejnosc dziala tak samo przy starcie z `/nauka`, z przycisku `Powtorz ten dzial`, z `Przejdz do nastepnego dzialu` i z klikniecia dzialu w lewym panelu postepu.

### Wejscia z bocznego panelu dzialow

Glowne miejsce:

```text
resources/js/Pages/StudySessions/Show.vue
```

Wejscie:

```text
switchTopicSession()
```

Panel boczny tworzy nowa sesje przez `study-sessions.store` i przekazuje:

```text
license_category_id
ui_shell
question_topic_id
question_scope
question_status
randomize_order
question_count
```

Wniosek: jezeli aktualna sesja byla w stalej kolejnosci, przelaczenie dzialu z bocznego panelu tez powinno uzyc recznej kolejnosci. Jezeli aktualna sesja byla losowa, przelaczenie dzialu powinno zostac losowe.

### Ekran podsumowania i rekordy dzialow

Glowne miejsca:

```text
app/Support/StudyTopicCompletionRecordService.php
app/Http/Controllers/StudySessionController.php
resources/js/Pages/StudySessions/Show.vue
```

Rekord dzialu kwalifikuje sie tylko wtedy, gdy sesja pokrywa pelna aktualna pule pytan danego dzialu. Hash pytan jest liczony z posortowanej listy ID, wiec sama kolejnosc przejscia nie zmienia kwalifikacji rekordu.

Wniosek: reczna kolejnosc nie powinna psuc rekordow czasu. Moze natomiast zmienic subiektywna trudnosc przejscia dzialu, co jest oczekiwane, bo wlasnie po to ustawiamy redakcyjna kolejnosc.

### Usprawnienia nauki i prefetch

Glowne miejsca:

```text
app/Http/Controllers/StudySessionController.php
app/Support/StudySessionApiPayloadBuilder.php
resources/js/Pages/StudySessions/Show.vue
```

Widok aktywnej sesji buduje payload pytania po `question_id`. Prefetch nastepnych pytan tez bazuje na `question_ids` zapisanych w payloadzie sesji, a potem mapuje dane pytan z bazy.

To chroni:

- adnotacje,
- grafiki pomocnicze,
- grafiki znakow,
- media pytan,
- PJM assety, jezeli tryb PJM zostaje w swojej sciezce,
- formatowanie/pogrubienia w `prompt` i `explanation`,
- zapis odpowiedzi po `question_id`.

Warunek: nowy serwis kolejnosci zwraca tylko ID pytan, ktore nadal naleza do aktualnej puli sesji.

## Aktualny stan kodu

### Frontend `/nauka`

Glowne miejsce:

```text
resources/js/Pages/Session/Index.vue
```

Istotne fakty:

- formularz startu sesji wysyla `randomize_order`,
- `Stala kolejnosc` ustawia `randomize_order = false`,
- `Losowa kolejnosc` ustawia `randomize_order = true`,
- `Zen mode` i `Nauka klasyczna` korzystaja z tego samego backendowego trybu `learn`,
- roznica miedzy klasyczna nauka i Zen siedzi w `ui_shell`.

Nie trzeba zmieniac kontraktu frontendu w pierwszej wersji. Pole `randomize_order` moze zostac. Zmieniamy tylko znaczenie backendowego wariantu `false`: jezeli istnieje aktywna reczna kolejnosc, uzywamy jej zamiast technicznego sortowania.

### Backend startu sesji

Glowne miejsce:

```text
app/Support/StudySessionManager.php
```

Najwazniejsza metoda:

```text
StudySessionManager::questionQuery(...)
```

Obecne zachowanie:

```text
MODE_LEARN + randomize_order = true
=> inRandomOrder()

MODE_LEARN + randomize_order = false
=> orderBy('difficulty')->orderByDesc('published_at')
```

Po utworzeniu sesji wynikowa kolejnosc pytan jest zapisywana w:

```text
study_sessions.payload.question_ids
```

To bardzo wazne: kolejnosc jest wyliczana raz przy starcie sesji. Pozniejszy ekran nauki i API odpowiedzi pracuja juz na `question_ids` zapisanych w payloadzie.

### Ekran aktywnej sesji

Glowne miejsca:

```text
resources/js/Pages/StudySessions/Show.vue
app/Support/StudySessionApiPayloadBuilder.php
app/Http/Controllers/StudySessionController.php
app/Http/Controllers/StudySessionAnswerController.php
```

Adnotacje, grafiki pomocnicze, wyjasnienia, pogrubienia i metadata sa pobierane dla konkretnego pytania po `question_id` / `external_id`.

Zmiana kolejnosci `question_ids` nie powinna psuc:

- adnotacji,
- pogrubien,
- kolorow w tresci pytania,
- wyjasnien,
- grafik pomocniczych,
- assetow video/image,
- logiki odpowiedzi.

Warunek: nie zmieniamy ID pytan, tylko ich kolejnosc w sesji.

### Import i przypisywanie dzialow

Glowne miejsca:

```text
app/Support/QuestionCatalogImporter.php
app/Support/QuestionTopicAssigner.php
app/Support/QuestionTopicOverrideResolver.php
```

Istotne fakty:

- importer tworzy/aktualizuje pytania,
- `QuestionTopicAssigner` przypisuje pytanie do dzialu,
- override'y tematow moga przeniesc pytanie do innego dzialu,
- import moze aktywowac/dezaktywowac pytanie albo zmienic jego dane.

Wniosek: reczna kolejnosc musi umiec wykryc pytania stale, brakujace i przeniesione. Nie mozemy zalozyc, ze zapisany raz uklad bedzie zawsze idealnie pasowal do aktualnej puli.

### Rekordy dzialow

Glowne miejsce:

```text
app/Support/StudyTopicCompletionRecordService.php
```

Istotne fakty:

- rekordy dzialow porownuja zestaw pytan jako zbior,
- hash pytan jest liczony z posortowanej listy ID,
- kolejnosc przejscia nie jest warunkiem kwalifikacji rekordu.

Wniosek: reczna kolejnosc nie powinna psuc rekordow dzialow, jezeli pula pytan pozostaje ta sama.

### Wzorce techniczne potwierdzone w kodzie

Migracje w projekcie uzywaja jawnie nazwanych indeksow przy dluzszych kluczach. Nowe migracje powinny robic to samo, zeby uniknac problemow z limitami nazw indeksow na roznych bazach.

Przyklad istniejacego wzorca:

```text
topic_records_unique_user_category_topic_scope
questions_session_learning_order_idx
```

Modele domenowe trzymaja proste `fillable`, `casts` i relacje Eloquent. Dla nowej funkcji powinny powstac:

```text
app/Models/QuestionLearningOrderSet.php
app/Models/QuestionLearningOrderItem.php
```

Strony admina w Filament sa zwykle cienkimi warstwami UI, a logika raportow/operacji siedzi w `app/Support`. Nowy panel powinien trzymac sie tego wzorca:

```text
App\Filament\Pages\QuestionLearningOrder
App\Support\QuestionLearningOrderAdminService
App\Support\QuestionLearningOrderService
```

Testy admina korzystaja z:

```text
User::factory()->admin()->create()
```

oraz sprawdzaja, ze zwykly uzytkownik dostaje `403`.

Autoryzacja panelu admina jest centralnie oparta o:

```text
User::canAccessPanel(...)
User::isAdministrator()
User::isBanned()
```

Nowa strona Filament nie musi tworzyc osobnego systemu uprawnien, ale testy powinny sprawdzac co najmniej:

- admin ma dostep,
- zwykly uzytkownik dostaje `403`,
- zbanowany admin nie ma dostepu do panelu, jezeli testujemy dostep na poziomie panelu.

Testy projektu uruchamiaja migracje na osobnej bazie SQLite dla kazdego testu. Produkcyjny `.env.example` wskazuje PostgreSQL. Nowe migracje i ograniczenia musza byc zgodne z SQLite i PostgreSQL.

## Zasady architektoniczne

### 1. Nie zmieniamy globalnego sortowania pytan

Nie wolno wprowadzac recznej kolejnosci jako domyslnego sortowania w modelu `Question` albo w publicznym katalogu pytan.

Reczna kolejnosc dotyczy tylko:

```text
mode = learn
randomize_order = false
question_topic_id != null
```

### 2. Nie ruszamy losowej kolejnosci

`randomize_order = true` zostaje bez zmian i dalej uzywa losowania.

### 3. Nie ruszamy egzaminu

Tryb egzaminacyjny losuje osobny zestaw pytan i nie powinien korzystac z recznej kolejnosci nauki.

### 4. Nie ruszamy trenera pamieci

Trener pamieci ma osobny algorytm planowania powtorek. Nie powinien korzystac z redakcyjnej kolejnosci dzialu.

### 5. Nie ruszamy PJM w pierwszym etapie

PJM ma osobny tryb i osobne filtry assetow. Reczna kolejnosc dotyczy na start tylko `MODE_LEARN`.

### 6. Sesja zapisuje snapshot kolejnosci

Po starcie sesji finalna kolejnosc pytan dalej musi byc zapisana w:

```text
study_sessions.payload.question_ids
```

Dzieki temu pozniejsze zmiany w panelu admina nie zmieniaja juz trwajacej ani historycznej sesji.

### 7. Fallback jest obowiazkowy

Jezeli nie ma aktywnej recznej kolejnosci albo czesc pytan nie ma pozycji, backend musi uzyc stabilnego fallbacku:

```text
difficulty ASC
published_at DESC
questions.id ASC
```

Obecnie w `MODE_LEARN` brakuje koncowego `questions.id ASC`, wiec warto go dodac jako stabilizator.

## Proponowany model danych

### Tabela `question_learning_order_sets`

Reprezentuje wersje ukladu kolejnosci dla kategorii, dzialu i zakresu pytan.

Proponowane pola:

```text
id
license_category_id
question_topic_id
question_scope
status
version
notes
created_by
updated_by
published_at
active_marker
created_at
updated_at
```

`question_scope`:

```text
all
basic
specialist
```

`status`:

```text
draft
active
archived
```

Indeksy:

```text
index(license_category_id, question_topic_id, question_scope, status) as qlos_lookup_idx
unique(license_category_id, question_topic_id, question_scope, active_marker) as qlos_one_active_unique
index(updated_by) as qlos_updated_by_idx
index(published_at) as qlos_published_at_idx
```

`active_marker` jest nullable. Dla aktywnego setu ma wartosc:

```text
active
```

Dla `draft` i `archived` ma `null`.

To daje cross-DB zabezpieczenie przed dwoma aktywnymi setami bez partial unique index:

- wiele draftow moze miec `active_marker = null`,
- wiele archiwalnych setow moze miec `active_marker = null`,
- tylko jeden set dla `category + topic + scope` moze miec `active_marker = active`.

Nie rekomenduje partial unique index w pierwszej wersji, bo projekt lokalnie korzysta z SQLite w testach, a produkcyjnie moze pracowac na PostgreSQL. `active_marker` plus zwykly unique index bedzie prostszy do testowania i utrzymania.

Jeden aktywny set dla `category + topic + scope` wymuszamy transakcyjnie w serwisie publikacji:

1. blokujemy/pobieramy aktywne sety dla zakresu,
2. ustawiamy stare aktywne sety jako `archived` i `active_marker = null`,
3. publikujemy nowy set jako `active` i `active_marker = active`,
4. commit.

Unique index jest dodatkowa siatka bezpieczenstwa na przypadek rownoleglej publikacji. Jezeli dwa procesy sprobuja opublikowac set rownoczesnie, jeden powinien wygrac, a drugi powinien dostac kontrolowany blad i komunikat w panelu admina.

Relacje modelu:

```text
licenseCategory()
questionTopic()
items()
createdBy()
updatedBy()
```

### Tabela `question_learning_order_items`

Reprezentuje pozycje pytania w konkretnym secie.

Proponowane pola:

```text
id
question_learning_order_set_id
question_id
position
created_at
updated_at
```

Indeksy:

```text
unique(question_learning_order_set_id, question_id) as qloi_set_question_unique
unique(question_learning_order_set_id, position) as qloi_set_position_unique
index(question_id) as qloi_question_idx
```

Nie zapisujemy samej pozycji bez setu, bo wtedy tracimy:

- draft,
- publikacje,
- historie,
- mozliwosc cofniecia,
- bezpieczne przygotowanie kolejnosci przed wlaczeniem jej dla uzytkownikow.

Relacje modelu:

```text
orderSet()
question()
```

Factory pod testy powinny powstac od razu. `QuestionFactory` nie przypisuje domyslnie `question_topic_id`, wiec testy kolejnosci musza jawnie tworzyc `QuestionTopic` i przypisywac go do pytan.

## Serwis domenowy

Dodac serwis:

```text
app/Support/QuestionLearningOrderService.php
```

Odpowiedzialnosci:

- znalezc aktywny order set dla kategorii, dzialu i zakresu,
- zbudowac finalna liste ID pytan dla `MODE_LEARN`,
- przefiltrowac stale pozycje,
- wykryc brakujace pytania,
- dopiac brakujace pytania fallbackiem,
- zwrocic metadata diagnostyczne dla payloadu sesji.

Serwis powinien uznawac pytanie za czesc aktualnej puli tylko wtedy, gdy pytanie spelnia te same warunki co sesja nauki:

```text
license_category_id = wybrana kategoria
question_topic_id = wybrany dzial
is_active = true
delivery_issue IS NULL
question_scope pasuje do metadata.structure_scope
question_status pasuje do filtra uzytkownika, jezeli jest uzyty
```

Proponowany kontrakt:

```text
orderedQuestionIds(User $user, LicenseCategory $category, array $filters): QuestionLearningOrderResult
```

Wynik:

```text
question_ids
order_set_id
order_set_version
manual_count
fallback_count
stale_count
missing_count
used_fallback
filtered_out_count
```

Metadata warto zapisac w:

```text
study_sessions.payload.filters.learning_order
```

Przyklad:

```text
learning_order: {
  source: "manual",
  order_set_id: 12,
  order_set_version: 3,
  manual_count: 64,
  fallback_count: 2,
  stale_count: 0,
  missing_count: 2,
  filtered_out_count: 0
}
```

To pomoze debugowac pytanie: "dlaczego uzytkownik zobaczyl taka kolejnosc?".

`filtered_out_count` dotyczy pytan, ktore istnieja w recznym secie, ale nie wchodza do konkretnej sesji przez filtr uzytkownika, np. `Jeszcze nieprzerobione`, `Z bledami`, `Dobrze rozwiazane`. To nie jest blad setu i nie powinno byc liczone jako stale pytanie.

## Wpiecie w start sesji

Miejsce:

```text
app/Support/StudySessionManager.php
```

Docelowy flow dla `MODE_LEARN`:

1. Znormalizuj filtry.
2. Jezeli `randomize_order = true`, uzyj obecnego losowania.
3. Jezeli `randomize_order = false` i jest `question_topic_id`, zapytaj `QuestionLearningOrderService`.
4. Jezeli serwis znajdzie aktywna kolejnosc, uzyj jej.
5. Jezeli nie znajdzie aktywnej kolejnosci, uzyj fallbacku.
6. Zapisz finalne `question_ids` w payloadzie sesji.
7. Zapisz metadata `learning_order` w payloadzie.

Nie rekomenduje robienia tego jako `JOIN` w istniejacym `questionQuery()` dla wszystkich trybow. Lepiej wydzielic sciezke tylko dla fixed learn, zeby nie dotknac innych modulow.

## Panel admina

Rekomendowane miejsce:

```text
app/Filament/Pages/QuestionLearningOrder.php
resources/views/filament/pages/question-learning-order.blade.php
```

Nawigacja:

```text
Grupa: Zawartość
Label: Kolejnosc pytan
```

`AdminPanelProvider` ma juz `maxContentWidth(Width::Full)`, wiec panel moze korzystac z szerokiego layoutu bez dodatkowego hackowania szerokosci.

Panel powinien pozwalac:

- wybrac kategorie prawa jazdy,
- wybrac dzial,
- wybrac zakres pytan: `all`, `basic`, `specialist`,
- zobaczyc aktualny aktywny set,
- utworzyc draft z obecnej kolejnosci technicznej,
- edytowac kolejnosc,
- zapisac draft,
- opublikowac draft jako aktywny,
- zobaczyc brakujace pytania,
- zobaczyc stale pytania,
- dopiac brakujace pytania na koniec,
- usunac stale pozycje,
- podejrzec pytanie.

### Wzorzec implementacyjny Filament

Istniejace strony typu `PjmCoverage` i `DataMonitoring` pokazuja dobry wzorzec:

- `Page` w `app/Filament/Pages`,
- statyczny `navigationIcon`, `navigationGroup`, `navigationSort`, `title`, `slug`,
- widok Blade w `resources/views/filament/pages`,
- cienki `getViewData()`,
- ciezsza logika w serwisie `app/Support`,
- testy dostepu admin/non-admin.

Dla nowej funkcji rekomendowany podzial:

```text
QuestionLearningOrder.php
question-learning-order.blade.php
QuestionLearningOrderAdminService.php
QuestionLearningOrderService.php
```

`QuestionLearningOrderAdminService` powinien obslugiwac dane i akcje panelu admina. `QuestionLearningOrderService` powinien byc czystym serwisem runtime dla startu sesji.

### UX admina

Najwiekszy aktualny bucket `category + topic` w PostgreSQL runtime ma 165 pytan, ale nie robimy UI, ktore wymaga przeciagania jednego elementu przez dluga liste. Dane moga urosnac po imporcie, a panel ma byc wygodny takze przy kilkuset pytaniach.

Rekomendacja:

- lista z paginacja albo wirtualizacja,
- wyszukiwarka po `external_id` i tresci pytania,
- pole `position`,
- akcje `przesun wyzej`, `przesun nizej`,
- opcjonalnie drag & drop w aktualnie widocznym fragmencie,
- masowa renumeracja pozycji co 10.

Pozycje mozna trzymac jako:

```text
10, 20, 30, 40...
```

Dzieki temu pozniej latwiej wstawiac pytania pomiedzy istniejace bez renumerowania calej listy przy kazdej drobnej zmianie.

## Walidacje

Przy zapisie draftu:

- `question_id` nie moze sie powtarzac w secie,
- `position` musi byc dodatnia liczba calkowita,
- pytania z innej kategorii/dzialu powinny byc oznaczone jako stale albo blokowane,
- draft moze byc niepelny, ale UI musi pokazac licznik brakow.

Przy publikacji:

- aktywny moze byc tylko jeden set dla `category + topic + scope`,
- publikacja powinna byc transakcyjna,
- stale pozycje powinny blokowac publikacje albo wymagac jawnego potwierdzenia,
- braki powinny blokowac publikacje albo byc jawnie dopinane na koniec.

Rekomendacja dla pierwszej wersji:

- stale pozycje blokuja publikacje,
- brakujace pytania mozna automatycznie dopiac na koniec po kliknieciu akcji,
- duplikaty zawsze blokuja zapis.

## Wplyw na wydajnosc

### Bezpieczne zalozenie

Reczna kolejnosc jest liczona tylko przy starcie sesji nauki. Nie jest liczona przy kazdym pytaniu.

### Ryzyka

- wolny panel admina przy ladowaniu wielu pytan z mediami,
- brak indeksow na tabelach kolejnosci,
- globalny `JOIN` do kolejnosci w publicznych zapytaniach,
- niepotrzebne przeliczanie kolejnosci na ekranie aktywnej sesji,
- pobieranie pelnych modeli pytan tam, gdzie potrzebujemy tylko ID.
- rownolegla publikacja dwoch setow przez dwoch adminow.

### Ograniczenia ryzyka

- w runtime startu sesji pobierac glownie ID,
- uzywac indeksow `set_id + position`,
- uzywac `active_marker` i unique index do ochrony jednego aktywnego setu,
- nie dolaczac assetow w adminowej liscie domyslnie,
- nie zmieniac `QuestionCatalogController`,
- nie zmieniac `ReviewPlannerService`,
- nie zmieniac trybu egzaminu,
- nie zmieniac PJM w pierwszej wersji.

## Ryzyka systemowe

### Autoryzacja admina

Panel admina jest chroniony przez `User::canAccessPanel(...)`. Nowa strona Filament powinna polegac na tym mechanizmie, a nie dublowac logiki w kazdej akcji.

W akcjach pobocznych, jezeli dodamy osobne route'y do eksportu/importu kolejnosci, trzeba jawnie sprawdzic:

```text
abort_unless((bool) $request->user()?->is_admin, 403)
```

albo zastosowac analogiczny request/policy pattern.

### SQLite w testach

Testy tworza osobna baze SQLite per test case i odpalaja migracje od zera. To oznacza:

- migracje musza byc kompatybilne z SQLite,
- surowe SQL-e specyficzne dla PostgreSQL powinny byc unikane,
- partial unique index nie jest dobrym fundamentem pierwszej wersji,
- `lockForUpdate()` w SQLite nie daje takiej samej gwarancji jak w PostgreSQL.

Dlatego krytyczna ochrona jednego aktywnego setu powinna byc oparta o zwykly unique index z `active_marker`, a transakcja powinna obslugiwac kontrolowany konflikt.

### Rownolegla publikacja

Najbardziej niebezpieczny przypadek:

1. Nie ma aktywnego setu.
2. Dwoch adminow publikuje dwa rozne drafty jednoczesnie.
3. Sama transakcja i `lockForUpdate()` nie zawsze zablokuja brakujacy wiersz.

Ochrona:

- `active_marker = active` tylko na aktywnym secie,
- unique index `category + topic + scope + active_marker`,
- publikacja w `DB::transaction(...)`,
- obsluga wyjatku unique constraint jako komunikatu: `Ktos opublikowal kolejny set przed Toba. Odswiez widok i sprobuj ponownie.`

## Zaleznosci z importem

Po imporcie pytan moga pojawic sie trzy sytuacje:

1. Nowe pytanie w dziale nie ma pozycji.
2. Pytanie z pozycji zostalo przeniesione do innego dzialu.
3. Pytanie z pozycji zostalo dezaktywowane albo przestalo byc gotowe do delivery.

Backend startu sesji powinien:

- ignorowac stale pozycje,
- dopinac brakujace pytania fallbackiem,
- zapisywac licznik `missing_count` i `stale_count` w payloadzie.

Panel admina powinien:

- pokazywac stale pozycje,
- pokazywac brakujace pytania,
- miec akcje porzadkujace.

Nie rekomenduje automatycznego przepisywania kolejki podczas importu w pierwszej wersji. Import powinien pozostac przewidywalny, a admin dostanie jasny sygnal, ze dana kolejnosc wymaga przegladu.

## Test plan

### Testy backendowe

Dodac testy dla `QuestionLearningOrderService`:

- uzywa aktywnego setu dla `MODE_LEARN + randomize_order=false`,
- ignoruje reczna kolejnosc dla `randomize_order=true`,
- ignoruje reczna kolejnosc dla egzaminu,
- ignoruje reczna kolejnosc dla trenera pamieci,
- fallback dziala bez aktywnego setu,
- brakujace pytania sa dopinane na koniec,
- stale pytania sa ignorowane,
- duplikaty w secie nie moga powstac,
- finalna lista nie zawiera pytan z innego dzialu,
- finalna lista nie zawiera pytan nieaktywnych lub niedostepnych.

### Testy integracyjne sesji

Aktualizowac testy w:

```text
tests/Feature/StudySessionFlowTest.php
tests/Feature/SessionPageTest.php
```

Scenariusze:

- start nauki klasycznej ze stala kolejnoscia,
- start Zen ze stala kolejnoscia,
- start nauki losowej,
- start z dzialu wybranego z ekranu podsumowania,
- start z panelu bocznego / szybkiego wyboru dzialu,
- zapis odpowiedzi nadal dziala po zmianie kolejnosci,
- ekran podsumowania nadal widzi poprawny zestaw pytan.

### Testy admina

Dodac testy:

- admin widzi strone `Kolejnosc pytan`,
- zwykly uzytkownik nie ma dostepu,
- strona korzysta z `QuestionLearningOrderAdminService`,
- mozna utworzyc draft z aktualnej kolejnosci,
- mozna zapisac nowe pozycje,
- publikacja ustawia jeden aktywny set,
- publikacja archiwizuje/pasywuje poprzedni aktywny set,
- stale pozycje sa wykrywane,
- brakujace pytania sa wykrywane.

### Testy regresji usprawnien nauki

Sprawdzic, ze po recznym ulozeniu pytan nadal dzialaja:

- `explanation_asset`,
- `explanation_annotations`,
- pogrubienia/formatowanie promptu,
- media pytania,
- zapis odpowiedzi po `question_id`,
- podsumowanie blednych i poprawnych pytan.

### Ostatnie checki przed implementacja

Przed kodowaniem nie robimy juz duzego skanu calego systemu. Wystarczy kontrola techniczna w trakcie implementacji:

- wybrac istniejace testy, ktore najlepiej rozszerzyc, zamiast dublowac scenariusze,
- sprawdzic najnowszy wzorzec stron Filament w panelu admina i trzymac sie tego ukladu,
- potwierdzic, ze migracje z `active_marker` dzialaja na SQLite testowym i PostgreSQL runtime,
- zapisac `learning_order` metadata w `study_sessions.payload.filters`, bo tam sa juz filtry sesji i latwo debugowac zrodlo kolejnosci,
- sprawdzic, ze nowe testy chronia start z `/nauka`, ekran podsumowania, lewy panel postepu i boczny panel dzialow.

## Plan wdrozenia etapami

### Etap 1 - Fundament bazy danych

Zakres:

- migracja `question_learning_order_sets`,
- migracja `question_learning_order_items`,
- modele Eloquent,
- relacje,
- podstawowe factory pod testy.

Gotowe, gdy:

- migracje przechodza,
- modele maja relacje,
- test prostego create/read przechodzi.

### Etap 2 - Serwis kolejnosci

Zakres:

- `QuestionLearningOrderService`,
- wynik DTO/value object,
- fallback techniczny,
- filtrowanie stale/missing,
- stabilne sortowanie z `questions.id`.

Gotowe, gdy:

- serwis zwraca poprawna finalna liste ID,
- testy pokrywaja manual, fallback, stale i missing.

### Etap 3 - Wpiecie w `StudySessionManager`

Zakres:

- podmiana tylko sciezki `MODE_LEARN + randomize_order=false`,
- zapis metadata `learning_order` w payloadzie,
- zachowanie starego fallbacku, gdy nie ma aktywnego setu.

Gotowe, gdy:

- klasyczna nauka uzywa recznej kolejnosci,
- Zen uzywa tej samej recznej kolejnosci,
- losowa kolejnosc nadal jest losowa,
- egzamin i trener pamieci bez zmian,
- istniejace testy sesji przechodza po aktualizacji oczekiwan.

### Etap 4 - Panel admina MVP

Zakres:

- nowa strona Filament,
- wybieranie kategorii/dzialu/zakresu,
- podglad aktywnej kolejnosci,
- tworzenie draftu z obecnego fallbacku,
- edycja pozycji numerami,
- publikacja draftu,
- liczniki brakujacych i stalych pozycji.

Gotowe, gdy:

- admin moze przygotowac i opublikowac kolejnosc bez dotykania bazy recznie,
- publikacja jest transakcyjna,
- UI pokazuje problemy z pula pytan.

### Etap 5 - UX admina wygodniejszy

Zakres:

- szybkie przesuwanie pozycji,
- wyszukiwarka,
- filtrowanie po typie pytania / ID / fragmencie tresci,
- akcja `Dodaj brakujace na koniec`,
- akcja `Usun stale pozycje`,
- ewentualny drag & drop w widocznym fragmencie.

Gotowe, gdy:

- ukladanie setek pytan jest realnie wykonalne,
- admin nie musi przeciagac elementu przez cala dluga liste.

### Etap 6 - QA i rollout

Zakres:

- `php artisan test` dla obszaru sesji i admina,
- `npm run build`,
- manualne przejscie `/nauka` -> start klasycznej,
- manualne przejscie `/nauka` -> start Zen,
- manualne przejscie z losowa kolejnoscia,
- manualne sprawdzenie adnotacji i grafik pomocniczych,
- manualne sprawdzenie podsumowania dzialu.

Gotowe, gdy:

- brak regresji w starcie sesji,
- brak regresji w wyswietlaniu pytan,
- brak regresji w zapisie odpowiedzi,
- admin potrafi opublikowac pierwszy aktywny set.

## Definition of Done

Feature mozna uznac za gotowy, gdy:

- istnieje panel admina `Kolejnosc pytan`,
- admin moze ulozyc kolejnosc dla kategorii, dzialu i zakresu,
- tylko jeden set jest aktywny dla `category + topic + scope`,
- klasyczna nauka uzywa aktywnego setu przy `Stala kolejnosc`,
- Zen mode uzywa tego samego aktywnego setu przy `Stala kolejnosc`,
- `Losowa kolejnosc` dziala jak wczesniej,
- egzamin dziala jak wczesniej,
- trener pamieci dziala jak wczesniej,
- adnotacje, pogrubienia i grafiki pomocnicze dzialaja jak wczesniej,
- nowe/brakujace/stale pytania sa widoczne w adminie,
- finalna kolejnosc sesji jest zapisana w `study_sessions.payload.question_ids`,
- testy backendowe i build frontendu przechodza.

## Otwarte decyzje produktowe

### Czy zakresy maja miec osobne kolejnosci?

Rekomendacja: tak, ale w praktyce pierwszy set robimy dla `all`.

Powod:

- uzytkownik moze wybrac `Wszystkie`, `Podstawowe`, `Specjalistyczne`,
- osobne sety daja pelna kontrole,
- fallback dopnie brakujace pytania, jesli set nie jest przygotowany.

### Czy admin moze opublikowac niepelny set?

Rekomendacja: nie w pierwszej wersji.

Powod:

- mniej niejasnosci,
- latwiejsze QA,
- admin od razu widzi, ze trzeba dodac brakujace pytania.

### Czy historia zmian kolejnosci ma byc widoczna?

Rekomendacja: przechowujemy wersje od poczatku, ale pelny ekran historii mozna dodac pozniej.

Powod:

- wersje sa tanie,
- przy problemach z nauka latwo sprawdzimy, jaki uklad byl aktywny.

### Czy import ma automatycznie aktualizowac order sety?

Rekomendacja: nie w pierwszej wersji.

Powod:

- import powinien tylko zmieniac katalog pytan,
- panel admina powinien pokazac braki/stale pozycje,
- automatyczna zmiana redakcyjnej kolejnosci moglaby ukryc problem.

## Najwazniejszy wniosek

Ta funkcjonalnosc jest bezpieczna, jezeli potraktujemy ja jako osobny mechanizm redakcyjnej kolejnosci dla startu sesji nauki, a nie jako globalna zmiane sortowania pytan.

Najbardziej stabilny wariant to:

```text
manual order set -> final question_ids at session start -> immutable session payload
```

Dzieki temu admin ma kontrole nad nauka, a reszta aplikacji nadal dziala po swoich dotychczasowych zasadach.
