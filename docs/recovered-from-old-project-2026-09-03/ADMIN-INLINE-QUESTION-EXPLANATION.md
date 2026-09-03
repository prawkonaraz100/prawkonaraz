# Admin-only inline edit wyjasnienia z trybu nauki

## Cel

Administrator ma moc poprawienia pola `explanation` bez opuszczania trybu nauki.

Najwazniejszy scenariusz:

1. Admin rozwiazuje albo przeglada pytanie w `Nauka klasyczna` lub `Zen mode`.
2. Widzi, ze wyjasnienie jest nieprecyzyjne, za dlugie albo bledne.
3. Kliknie szybka akcje `Edytuj wyjasnienie`.
4. Otwiera sie modal z textarea.
5. Po zapisie tresc odswieza sie lokalnie na ekranie, bez przeklikiwania do Filamenta.

## Problem, ktory rozwiazujemy

Dzisiaj pole `questions.explanation` da sie edytowac tylko przez panel admina.

To oznacza, ze redakcja contentu w praktyce wymaga:

- wyjscia z kontekstu pytania,
- przejscia do `admin/questions/...`,
- znalezienia rekordu,
- zapisania zmian,
- powrotu do nauki.

Przy intensywnej korekcie tresci jest to zbyt wolne.

## Zakres MVP

MVP obejmuje tylko:

- admin-only akcje w `resources/js/Pages/StudySessions/Show.vue`,
- edycje jednego pola: `questions.explanation`,
- zapis przez osobny endpoint JSON,
- natychmiastowe odswiezenie tresci w aktywnym pytaniu i lokalnym podsumowaniu sesji.

## Poza zakresem MVP

Na tym etapie nie robimy:

- edycji `prompt`,
- edycji odpowiedzi `A/B/C` lub `TAK/NIE`,
- moderacji wieloosobowej,
- workflow akceptacji zmian,
- wersjonowania tresci,
- osobnego pola typu `editorial_explanation`,
- edycji z poziomu trybu `Egzamin`.

## Zasada produktowa

Feature ma dzialac tylko wtedy, gdy:

- uzytkownik jest zalogowany,
- ma `is_admin = true`,
- przebywa w trybie nauki (`session.mode = learn`).

To oznacza:

- `Egzamin` pozostaje czystym, nieedytowalnym flow,
- zwykly kursant nie zobaczy zadnych kontrolek redakcyjnych.

## UX

### Aktywne pytanie

W playerze nauki admin widzi mala akcje `Edytuj wyjasnienie` przy tresci pytania.

Klik:

- otwiera modal,
- wypelnia textarea aktualnym `explanation`,
- pozwala zapisac albo anulowac.

Po zapisie:

- modal sie zamyka,
- jesli wyjasnienie jest aktualnie widoczne, od razu pokazuje nowa tresc,
- jesli pytanie nie jest jeszcze ocenione, aktualizuje sie lokalny cache pytania.

### Podsumowanie sesji

W review po zakonczeniu nauki admin widzi te sama akcje przy sekcjach `Wyjasnienie pytania`.

Po zapisie:

- aktualizuje sie tresc w przegladzie pytan,
- aktualizuje sie tez lokalny stan pytania, jesli to samo pytanie jest jeszcze w cache.

## Backend

Dodajemy osobny endpoint typu JSON:

- `PATCH /api/v1/admin/questions/{question}/explanation`

Zasady:

- tylko `auth + verified`,
- tylko admin,
- payload przyjmuje wylacznie:
  - `explanation`

Zapis:

- nadpisuje bezposrednio `questions.explanation`,
- pusty string moze zostac znormalizowany do `null`,
- zwraca zaktualizowane dane pytania potrzebne frontowi.

## Audit / obserwowalnosc

Kazda zmiana powinna zostawic wpis w audit logu z akcja podobna do:

- `admin.question.explanation_updated`

Minimalne metadane:

- `source = study_session_inline`,
- `question_id`,
- informacja, czy wyjasnienie istnialo przed i po zmianie.

## Frontend

`Show.vue` dostaje:

- rozpoznanie admina z `auth.user.is_admin`,
- stan modala,
- formularz textarea,
- akcje:
  - otwarcie,
  - zamkniecie,
  - zapis,
  - lokalne podmiany `explanation` w cache.

## Miejsca aktualizacji lokalnego stanu

Po udanym zapisie trzeba zsynchronizowac:

- `questionCache`,
- `activeQuestionState`,
- `preparedNextQuestion`,
- `localResults`.

Dzieki temu nie trzeba reloadowac calej strony.

## Acceptance criteria

Feature uznajemy za gotowy, gdy:

1. Admin widzi przycisk `Edytuj wyjasnienie` w trybie nauki.
2. Zwykly kursant nie widzi tej akcji.
3. Zapis aktualizuje rekord `questions.explanation`.
4. Po zapisie tresc odswieza sie lokalnie bez opuszczania sesji.
5. Review po sesji pokazuje juz nowa tresc.
6. `Egzamin` nie dostaje zadnych kontrolek edycji.
7. Endpoint zwraca `403` dla nie-admina.

## Najbezpieczniejsza architektura MVP

- osobny request do walidacji,
- osobny endpoint do samego `explanation`,
- zero ingerencji w glowny admin upsert,
- zero zmian w logice nauki i egzaminu poza malym UI admina.

## Dalszy rozwoj

Jesli MVP sie sprawdzi, kolejne kroki moga byc takie:

1. `Otworz pelna edycje pytania` jako fallback.
2. Edycja `prompt` i odpowiedzi z tego samego modala.
3. `editorial_explanation` zamiast nadpisywania canonical `explanation`.
4. Historia zmian / ostatni redaktor.
