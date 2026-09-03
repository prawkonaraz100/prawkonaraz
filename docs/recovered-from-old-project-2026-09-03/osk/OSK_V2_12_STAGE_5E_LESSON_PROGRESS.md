# OSK V2.12 - Etap 5E: postęp lekcji

**Status:** zakończony lokalnie; bez deployu produkcyjnego.
**Data:** 2026-08-26.
**Zakres:** wyłącznie nowa Nauka teorii OSK pod `/osk/nauka`.

## Cel

Kursant ma po odświeżeniu lub powrocie do lekcji zobaczyć ostatnio oglądany krok. Ten etap zapamiętuje miejsce w lekcji. Nie oznacza ukończenia lekcji, modułu ani kursu i nie jest formalnym potwierdzeniem czasu nauki.

## Twarde granice

- Nie zmieniamy `StudySession`, `StudySessionAnswer`, `QuestionCollection`, `QuestionModule` ani routingu `/nauka`.
- Nie zapisujemy minut, nie wywołujemy heartbeat-u i nie aktualizujemy źródłowej sesji czasu podczas zapisu kursora.
- Nie tworzymy `TheoryCompletionGate`, evidence, assessmentu, wyniku egzaminu, wpisu prawnego ani dokumentu PAPER.
- Nie ufamy kodowi kroku przekazanemu przez przeglądarkę. Krok zawsze jest sprawdzany w opublikowanym snapshotcie przypiętym do enrollmentu.
- Nie zastępujemy historii zdarzeń aktualnym kursorem. Projekcja wznowienia jest wygodnym odczytem; zdarzenia pozostają append-only.

## Modele i dane

### `learning_lesson_progresses`

Jedna projekcja na tuple:

```text
course_enrollment_id
course_version_id
module_code
lesson_code
```

Rekord przechowuje ponadto numer wersji, hash programu, ostatni `resume_step_code`, jego pozycję, czas pierwszej interakcji i czas ostatniej interakcji. Numer wersji i hash są niezmienne po utworzeniu. Zmieniać można wyłącznie kursor i znaczniki czasu interakcji.

### `learning_lesson_progress_events`

Każde świadome pokazanie kroku zapisuje zdarzenie `STEP_VIEWED` z:

- identyfikatorem projekcji;
- `learning_session_id` oraz `actor_user_id`;
- kodem i pozycją kroku;
- kluczem idempotencji;
- technicznym payloadem: pozycjami modułu i lekcji oraz typem i celem kroku;
- serwerowym czasem zdarzenia.

Zdarzenia nie mogą być aktualizowane ani usuwane. Ponowione żądanie z tym samym kluczem idempotencji zwraca pierwotne zdarzenie i nie przesuwa kursora drugi raz.

## Kontrakt backendu

Trasa:

```text
POST /osk/nauka/{courseEnrollment}/sesje/{learningSession}/postep
```

Wymaga `auth`, `verified`, scoped bindings i limitu `90` żądań na minutę. Żądanie zawiera:

```json
{
  "client_session_key": "...",
  "idempotency_key": "...",
  "module_code": "...",
  "lesson_code": "...",
  "step_code": "..."
}
```

Serwis wykonuje kolejno:

1. sprawdzenie dostępu kursanta do enrollmentu;
2. kontrolę, że sesja należy do tego enrollmentu, użytkownika i klucza przeglądarki;
3. odtworzenie timeoutu sesji bez zapisu nowego heartbeat-u;
4. blokadę enrollmentu, sesji i projekcji w transakcji;
5. walidację modułu, lekcji i kroku wobec zamrożonego snapshotu;
6. utworzenie lub przesunięcie kursora oraz append-only zdarzenia.

Zamknięta albo wygasła sesja zwraca `409 SESSION_CLOSED`. Niedostępny enrollment, cudza sesja i niepasujący snapshot pozostają fail-closed. Problemy z chwilową dostępnością postępu mogą pokazać ostrzeżenie UI, ale nie wolno ich interpretować jako zapisanej aktywności.

## Kontrakt UI

`LessonPlayer.vue` otrzymuje z backendu wyłącznie:

- URL endpointu postępu jako szablon z ID aktualnej sesji;
- aktualny `resume_step_code`, pozycję i czas ostatniej interakcji.

Player ustawia aktywny krok z kursora. Po kliknięciu `Rozpocznij naukę` lub `Wznów naukę` zapisuje bieżący krok. Później zapis następuje po kliknięciu poprzedniego albo następnego kroku. Samo wejście na adres, render strony, odświeżenie oraz unmount nie tworzą zdarzenia postępu.

Przeglądarka przechowuje tylko najnowszy oczekujący kursor. Gdy żądanie zakończy się błędem sieci, najnowszy krok zostaje podjęty ponownie przy następnej zmianie kroku. Nie tworzymy klientowego licznika ani lokalnego dowodu czasu.

## Weryfikacja

Testy celu:

```powershell
docker compose exec -T app php artisan test tests/Feature/Osk/LessonProgressTest.php tests/Feature/Osk/TheoryLearningPlayerTest.php --compact
```

Aktualny wynik celu: **11 testów / 149 asercji**.

Pełna weryfikacja po migracji:

- OSK: **53 testy / 406 asercji**;
- istniejąca nauka, kolekcje, dostęp B2C i audit: **72 testy / 1265 asercji**;
- `php artisan migrate --force`, Pint, `npm run build` oraz `git diff --check`: przeszły lokalnie.

Wykonane przed commitem:

```powershell
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan test tests/Feature/Osk --compact
docker compose exec -T app php artisan test tests/Feature/StudySessionFlowTest.php tests/Feature/QuestionCollectionLearningTest.php tests/Feature/QuestionCollectionAccessTest.php tests/Feature/ProductAccessGateTest.php tests/Feature/AuditLoggingTest.php --compact
docker compose exec -T vite npm run build
docker compose exec -T app vendor/bin/pint
git diff --check
```

Ręczny smoke po migracji:

1. uruchom lekcję dopiero przyciskiem;
2. przejdź do kolejnego kroku;
3. odśwież stronę i potwierdź wznowienie na tym kroku;
4. zamknij albo pozwól wygasnąć sesji i sprawdź brak zapisu po `SESSION_CLOSED`;
5. potwierdź, że `/nauka` i istniejące sesje pytań nie są używane ani nie otrzymują rekordów.

## Następny krok

Po zielonej weryfikacji należy wykonać osobny, świadomy etap dla formalnego ukończenia, assessmentu i ewentualnego evidence. Nie wolno nadawać temu kursorowi znaczenia formalnego bez wersjonowanych reguł, decyzji compliance i nowego kontraktu testowego.
