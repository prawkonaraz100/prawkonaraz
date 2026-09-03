# Globalne bledy w nauce klasycznej - dokumentacja wdrozenia

## Cel

Dodac w `/nauka` osobny, czytelny CTA `Powtorz bledy ze wszystkich dzialow`.

Obecny filtr `Z bledami` zostaje bez zmian i nadal dziala dla aktualnie wybranego dzialu. Nowy CTA ma uruchamiac osobna sesje nauki klasycznej z pytaniami blednymi z calej aktywnej kategorii uzytkownika.

## Status wdrozenia

Wykonane:

- dodano osobny CTA w sciezce `Nauka klasyczna`,
- dodano osobny formularz frontendowy dla globalnych bledow,
- nie zmieniono istniejacego filtra `Z bledami` dla wybranego dzialu,
- dodano test regresyjny dla bledow z wielu dzialow,
- potwierdzono, ze sesja nie miesza kategorii.

Zweryfikowane:

- `docker compose exec -T app php artisan test tests/Feature/SessionPageTest.php`
- `npm run build`

## Problem

Uzytkownik moze dzisiaj wybrac:

- dzial,
- status `Z bledami`.

To daje tylko bledy z jednego dzialu. Gdy uzytkownik chce szybko poprawic wszystkie bledy po kilku przerobionych dzialach, musi recznie przechodzic dzial po dziale. To spowalnia nauke i niepotrzebnie rozbija flow.

## Decyzja produktowa

Wybieramy osobny CTA zamiast rozszerzania istniejacego filtra.

Powody:

- nie zmieniamy dotychczasowego zachowania `Z bledami` dla wybranego dzialu,
- unikamy dodatkowego przelacznika `Ten dzial / Wszystkie dzialy`,
- uzytkownik dostaje jasna akcje: popraw wszystko, co poszlo zle,
- backend moze wykorzystac istniejacy mechanizm `question_topic_id = null` + `question_status = incorrect`,
- nie dotykamy PJM, trenera pamieci, egzaminu ani Zen mode.

## Zakres funkcjonalny

Nowy CTA:

- widoczny tylko w sciezce `Nauka klasyczna`,
- widoczny tylko dla pelnego dostepu,
- widoczny tylko gdy `totalIncorrectQuestions > 0`,
- uruchamia sesje:
  - `mode = learn`,
  - `ui_shell = exam_like`,
  - `question_topic_id = null`,
  - `question_scope = all`,
  - `question_status = incorrect`,
  - `randomize_order = false`,
  - `question_count = totalIncorrectQuestions`,
- dziala w aktywnej kategorii przypisanej do konta.

## Zaleznosci w kodzie

### Backend

`app/Support/StudySessionManager.php`

`questionQuery()` juz obsluguje brak `question_topic_id`. Jesli topic jest pusty, query zostaje na poziomie calej kategorii.

`applyQuestionStatusFilter()` juz obsluguje `incorrect`:

- dolacza `user_question_progress`,
- wymaga `total_attempts > 0`,
- wymaga `incorrect_count > 0`,
- wyklucza pytania utrwalone wedlug `masteredProgressSql()`.

Wniosek: nie potrzebujemy nowej trasy ani nowego trybu sesji.

### Frontend

`resources/js/Pages/Session/Index.vue`

Istnieje `totalIncorrectQuestions`, liczone z `group_options`. Mozemy uzyc tej liczby jako glownego licznika CTA i jako `question_count` sesji.

## Plan wdrozenia

### Sprint 1: dokumentacja i kontrakt

- zapisac decyzje produktowa,
- opisac payload sesji,
- wskazac miejsca ryzyka.

### Sprint 2: UI `/nauka`

- dodac osobny `useForm` dla globalnych bledow, zeby nie mutowac konfiguratora dzialu,
- dodac CTA w sekcji `Nauka klasyczna`,
- pokazac liczbe bledow z calej kategorii,
- zostawic obecny filtr `Z bledami` bez zmian.

### Sprint 3: testy regresyjne

Dodac test:

- pytania bledne w dwoch roznych dzialach,
- start sesji z `question_topic_id = null` i `question_status = incorrect`,
- sesja zawiera pytania z obu dzialow,
- pytanie poprawne i nieprzerobione nie wchodzi do sesji.

### Sprint 4: weryfikacja

- uruchomic `SessionPageTest`,
- uruchomic build frontendu,
- sprawdzic, ze obecny filtr dzialowy nadal dziala.

## Ryzyka i zabezpieczenia

### Ryzyko: przypadkowa zmiana filtra dzialowego

Zabezpieczenie: osobny formularz dla globalnego CTA. Nie zmieniamy `sessionForm.question_topic_id` uzywanego przez konfigurator.

### Ryzyko: mieszanie kategorii

Zabezpieczenie: backend nadal filtruje po `license_category_id`, a kategoria pochodzi z aktywnego kontekstu uzytkownika.

### Ryzyko: zbyt duza sesja

Na tym etapie globalna sesja ma celowo zebrac wszystkie bledy z aktywnej kategorii. Jesli w praktyce pojawi sie kilkaset bledow, mozemy dodac limit sesji albo dzielic ja na paczki, ale nie robimy tego w pierwszym wdrozeniu, zeby nie zmieniac semantyki "wszystkie bledy".

## Definition of Done

- Na `/nauka` w `Nauka klasyczna` widac CTA `Powtorz bledy ze wszystkich dzialow`, gdy sa bledy.
- Klikniecie CTA uruchamia klasyczna sesje bez wybranego dzialu.
- Sesja zawiera bledne pytania z calej aktywnej kategorii.
- Istniejacy filtr `Z bledami` dla pojedynczego dzialu dziala jak dotychczas.
- Test regresyjny przechodzi.
- Build frontendu przechodzi.
