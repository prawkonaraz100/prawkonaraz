# Rekordy Dzialow Nauki - Plan Wdrozenia

## Cel

Chcemy dodac do ekranu podsumowania sesji nauki prosty drugi komponent: tabele dzialow dla aktualnej kategorii prawa jazdy, z czasami ukonczenia i nagroda za rekord.

Ten dokument opisuje decyzje produktowe, model danych i plan implementacji. Nie jest jeszcze implementacja.

## Decyzje produktowe

- Pierwsze perfekcyjne ukonczenie dzialu daje pozytywny komunikat, np. `Pierwszy rekord dzialu!`.
- Kolejne perfekcyjne ukonczenie, szybsze od poprzedniego najlepszego czasu, daje komunikat, np. `Nowy rekord! Szybciej o 42 s`.
- `Nauka klasyczna` i `Zen mode` korzystaja z tej samej mechaniki i tych samych pytan, wiec rekord jest wspolny dla obu shelli.
- `ui_shell` zapisujemy tylko jako metadata (`best_ui_shell`, `last_ui_shell`), ale nie wchodzi do klucza rekordu.
- Rekordy sa oddzielne dla `question_scope`: `all`, `basic`, `specialist`.
- Ranking w przyszlosci powinien korzystac z tej samej tabeli rekordow, bez ponownego liczenia JSON-ow w `study_sessions.payload`.

## Aktualny stan kodu

### Backend

- Glowna logika sesji: `app/Support/StudySessionManager.php`
- Kontroler sesji i payload Inertia: `app/Http/Controllers/StudySessionController.php`
- Zapis odpowiedzi: `app/Http/Controllers/StudySessionAnswerController.php`
- API zapis odpowiedzi: `app/Http/Controllers/ApiStudySessionAnswerController.php`
- Aktualne grupy dzialow: `app/Support/StudyTopicGroupsService.php`

Istotne fakty:

- `Nauka klasyczna` i `Zen mode` sa backendowo `mode = learn`.
- Roznica wizualna siedzi w `payload.ui_shell`: `exam_like` albo `zen`.
- Filtry sesji sa w `payload.filters`.
- Dzial sesji jest w `payload.filters.question_topic_id`.
- Zakres pytan jest w `payload.filters.question_scope`.
- Status puli pytan jest w `payload.filters.question_status`.
- Czas sesji liczymy z `started_at` i `completed_at`, nie z sumy `response_time_ms`.

### Frontend

- Podsumowanie sesji jest w `resources/js/Pages/StudySessions/Show.vue`.
- Sekcja konca sesji zaczyna sie przy `data-testid="session-complete"`.
- Pod pierwsza karta wyniku istnieje juz komponent `Sciezka dzialow`; to jest naturalne miejsce na nowa tabele.
- `topicGroups` juz daje liste dzialow dla kategorii, ale nie zawiera czasow ani rekordow.

## Definicja pelnego ukonczenia dzialu

Sesja kwalifikuje sie jako pelne ukonczenie dzialu, jezeli:

- `study_sessions.status = completed`,
- `study_sessions.mode = learn`,
- `payload.filters.question_topic_id` nie jest puste,
- `payload.filters.question_scope` jest jednym z `all`, `basic`, `specialist`,
- zestaw `payload.question_ids` odpowiada aktualnej pelnej puli pytan dla wybranego dzialu, kategorii i zakresu,
- `total_questions_count > 0`,
- liczba odpowiedzi w `study_session_answers` jest co najmniej rowna `total_questions_count`,
- `started_at` i `completed_at` sa ustawione.

Nie opieramy kwalifikacji wylacznie na `payload.filters.question_status`, bo sesje uruchamiane z ekranu wyniku albo panelu bocznego moga przeniesc starsza etykiete filtra. Decyduje faktyczny zestaw pytan w sesji.

Sesja kwalifikuje sie jako perfekcyjny rekord, jezeli dodatkowo:

- `correct_answers_count = total_questions_count`,
- czas sesji jest krotszy niz poprzedni najlepszy perfekcyjny czas albo uzytkownik nie mial jeszcze perfekcyjnego rekordu dla tego dzialu.

Manualne zakonczenie pustej albo niedokonczonej sesji nie powinno utworzyc rekordu.

## Proponowana tabela

Tabela: `user_topic_completion_records`

Klucz biznesowy:

```text
user_id + license_category_id + question_topic_id + question_scope
```

Proponowane pola:

```text
id
user_id
license_category_id
question_topic_id
question_scope

last_study_session_id
last_duration_seconds
last_score_percent
last_completed_at
last_ui_shell
last_questions_count
last_question_ids_hash

best_study_session_id
best_duration_seconds
best_completed_at
best_ui_shell
best_questions_count
best_question_ids_hash

completion_count
perfect_completion_count
first_completed_at

created_at
updated_at
```

Uwagi:

- `last_*` opisuje ostatnie pelne ukonczenie dzialu, nawet jezeli byly bledy.
- `best_*` opisuje najlepsze perfekcyjne ukonczenie dzialu i bedzie baza przyszlego rankingu.
- `question_ids_hash` to hash posortowanej listy pytan z sesji. Przyda sie, gdy baza pytan w dziale sie zmieni. Ranking bedzie mogl filtrowac rekordy zgodne z aktualnym snapshotem pytan.
- `best_duration_seconds` moze byc `null`, jezeli uzytkownik ukonczyl dzial, ale nigdy perfekcyjnie.
- `completion_count` powinien liczyc pelne ukonczenia dzialu.
- `perfect_completion_count` powinien liczyc tylko pelne ukonczenia na 100%.

Indeksy:

```text
unique(user_id, license_category_id, question_topic_id, question_scope)
index(user_id, license_category_id)
index(license_category_id, question_topic_id, question_scope, best_duration_seconds)
index(license_category_id, question_topic_id, question_scope, best_question_ids_hash, best_duration_seconds)
```

## Serwis domenowy

Dodac serwis:

```text
app/Support/StudyTopicCompletionRecordService.php
```

Odpowiedzialnosci:

- sprawdzic, czy sesja jest pelnym ukonczeniem dzialu,
- policzyc `duration_seconds`,
- policzyc `question_ids_hash`,
- zaktualizowac `last_*`,
- zaktualizowac `best_*`, jezeli sesja jest perfekcyjna i jest pierwszym albo szybszym rekordem,
- zwrocic wynik dla UI:
  - `record_state`: `none`, `first_record`, `improved_record`,
  - `saved_duration_seconds`,
  - `previous_best_duration_seconds`,
  - `best_duration_seconds`.

Serwis powinien byc idempotentny dla tej samej sesji w normalnym flow. Nie powinien byc odpalany z renderowania widoku jako mutacja danych.

Po synchronizacji serwis zapisuje tez krotki snapshot zdarzenia w `study_sessions.payload.topic_completion_record`. To nie jest osobna historia rekordow, tylko marker dla ekranu wyniku konkretnej sesji:

```text
record_state
saved_duration_seconds
previous_best_duration_seconds
best_duration_seconds
record_id
study_session_id
synced_at
```

Dzieki temu odswiezenie strony wyniku nadal wie, czy ta konkretna sesja byla pierwszym rekordem albo pobiciem rekordu.

## Miejsca wpiecia

### Po ostatniej odpowiedzi

Po `StudySessionManager::recordAnswer(...)`, jezeli sesja przeszla w `completed`, odpalic:

```text
StudyTopicCompletionRecordService::syncFromCompletedSession($studySession)
```

Dotyczy:

- `StudySessionAnswerController`
- `ApiStudySessionAnswerController`

### Po manualnym zakonczeniu

Po `StudySessionManager::complete(...)` mozna odpalic ten sam serwis, ale serwis ma odrzucic sesje, jezeli nie ma kompletu odpowiedzi.

Dotyczy:

- `StudySessionController::completeStudySession`
- `ApiStudySessionController::complete`

### Payload podsumowania

W `StudySessionController::renderSessionPage(...)` dodac nowy prop, np.:

```text
topicCompletionOverview
```

Proponowany ksztalt:

```text
{
  category_id,
  question_scope,
  current_topic_id,
  items: [
    {
      topic_id,
      key,
      label,
      bucket_label,
      questions_count,
      last_duration_seconds,
      last_score_percent,
      best_duration_seconds,
      completion_count,
      perfect_completion_count,
      record_state,
      saved_duration_seconds
    }
  ]
}
```

`items` powinno bazowac na tych samych dzialach co `StudyTopicGroupsService`, z dolaczonymi rekordami z `user_topic_completion_records`.

## UI

Miejsce:

- bezposrednio pod pierwsza karta `Wynik sesji`,
- przed albo zamiast obecnej wizualnej `Sciezki dzialow`.

Pierwsza wersja UI:

- prosta tabela/sekcja,
- kolumny: `Dzial`, `Czas`, `Status`,
- dla aktualnego dzialu pokazac wyroznienie,
- dla `first_record`: `Pierwszy rekord dzialu!`,
- dla `improved_record`: `Nowy rekord!` + roznica czasu,
- dla braku danych: `Jeszcze bez czasu`.

Na mobile tabela moze przejsc w liste wierszy.

## Plan implementacji

1. Dodac migracje i model `UserTopicCompletionRecord`.
2. Dodac `StudyTopicCompletionRecordService`.
3. Podpiac serwis po zakonczeniu sesji przez ostatnia odpowiedz.
4. Podpiac serwis po manualnym zakonczeniu, z odrzuceniem niepelnych sesji.
5. Dodac builder/serwis dla payloadu `topicCompletionOverview`.
6. Dodac prop i typy w `Show.vue`.
7. Dodac prosty komponent tabeli w sekcji podsumowania.
8. Dodac testy feature dla zapisu rekordu i payloadu Inertia.
9. Uruchomic testy i build frontendu.

## Testy akceptacyjne

Minimalny zestaw:

- pierwsze perfekcyjne ukonczenie dzialu tworzy rekord i pokazuje `first_record`,
- drugie perfekcyjne ukonczenie z krotszym czasem aktualizuje rekord i pokazuje `improved_record`,
- drugie perfekcyjne ukonczenie z dluzszym czasem nie pokazuje nagrody,
- ukonczenie z bledem aktualizuje `last_*`, ale nie aktualizuje `best_*`,
- manualne zakonczenie bez wszystkich odpowiedzi nie tworzy rekordu,
- `exam_like` i `zen` dziela ten sam rekord,
- `basic`, `specialist` i `all` maja oddzielne rekordy,
- inna kategoria albo inny dzial nie wplywa na rekord aktualnego dzialu,
- payload `topicCompletionOverview.items` zawiera wszystkie dzialy dla kategorii sesji.

## Backfill

Na start mozemy nie robic backfillu, jezeli funkcja ma dzialac od momentu wdrozenia.

Jezeli chcemy uzupelnic dane historyczne, dodac osobna komende Artisan:

```text
php artisan study:backfill-topic-completion-records
```

Komenda powinna:

- przejsc po zakonczonych `learn` sesjach,
- odrzucic niepelne i niekwalifikujace sie sesje,
- przeliczyc rekordy chronologicznie po `completed_at`,
- wypisac liczbe przeanalizowanych sesji, utworzonych rekordow i pominietych sesji.

## Otwarte decyzje

- Czy w tabeli podsumowania pokazujemy `last_duration_seconds`, `best_duration_seconds`, czy oba. Rekomendacja: pokazac najlepszy czas jako glowny, a ostatni czas jako drobny tekst pomocniczy dopiero w kolejnej iteracji.
- Czy obecna `Sciezka dzialow` zostaje obok tabeli, czy tabela ja zastepuje. Rekomendacja: w pierwszej wersji zastapic wizualna sciezke prosta tabela, zeby ekran podsumowania nie byl przeladowany.
- Czy ranking ma filtrowac tylko rekordy z aktualnym `best_question_ids_hash`. Rekomendacja: tak, zeby po zmianach w bazie pytan nie mieszac starych, latwiejszych rekordow z aktualnymi.
