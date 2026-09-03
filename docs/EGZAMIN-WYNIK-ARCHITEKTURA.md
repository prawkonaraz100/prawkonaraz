# Wynik Egzaminu Teoretycznego

## Cel

Oddzielić wynik zakończonego egzaminu od wyniku zwykłej sesji nauki.

Do tej pory:
- aktywny egzamin był renderowany przez `StudySessions/Exam`
- zakończony egzamin wpadał z powrotem do `StudySessions/Show`
- przez to końcowy ekran egzaminu dziedziczył teksty i CTA naukowe typu:
  - `Wynik sesji`
  - `Powtórz błędne pytania`
  - `Powtórz ten dział`
  - `Wybierz kolejny dział`

To nie odpowiada formalnemu charakterowi trybu egzaminacyjnego.

## Decyzja architektoniczna

Wprowadzamy osobny ekran:

- `resources/js/Pages/StudySessions/ExamResult.vue`

Nowy komponent jest używany wyłącznie wtedy, gdy:
- `session.mode === exam`
- `session.status === completed`

W efekcie mamy trzy niezależne ścieżki:
- `StudySessions/Exam` dla egzaminu w toku
- `StudySessions/ExamResult` dla egzaminu zakończonego
- `StudySessions/Show` dla nauki i pozostałych trybów

## Backend

Kontroler `StudySessionController` dalej buduje wspólny payload sesji, ale dodatkowo wystawia nowy blok:

- `examResult`

`examResult` zawiera:
- `passed`
- `earned_points`
- `available_points`
- `pass_threshold`
- `official_max_points`
- `correct_answers_count`
- `incorrect_answers_count`
- `unanswered_count`
- breakdown dla sekcji:
  - `basic`
  - `specialist`

Każda sekcja ma:
- `answered`
- `total`
- `correct`
- `incorrect`
- `unanswered`
- `earned_points`
- `available_points`

Dodatkowo `results` dostały pola:
- `structure_scope`
- `points`

To pozwala renderować przegląd błędnych pytań bez ponownego liczenia wszystkiego na froncie.

## Frontend

`ExamResult.vue` ma być ekranem formalnym i technicznym, a nie edukacyjnym.

Powinien pokazywać:
- werdykt `zdany / niezdany`
- wynik punktowy
- próg zaliczenia
- czas egzaminu
- kategorię
- podział na część podstawową i specjalistyczną
- listę pytań błędnych lub bez odpowiedzi

Nie powinien zawierać CTA naukowych typu:
- powtórka działu
- przejście do kolejnego działu
- język sugerujący zwykłą sesję treningową

## Kryteria akceptacji

Feature uznajemy za gotowy, gdy:

1. Zakończony egzamin renderuje `StudySessions/ExamResult`, a nie `StudySessions/Show`.
2. Ekran pokazuje osobny status `Egzamin zdany / Egzamin niezdany`.
3. Wynik nie zawiera CTA zależnych od działu nauki.
4. Widok pokazuje punkty, próg, czas i podział sekcji.
5. Błędne pytania oraz pytania bez odpowiedzi są widoczne w osobnym przeglądzie.
6. `Zen mode`, `Nauka klasyczna` i zwykły `Show.vue` nie zmieniają swojego zachowania.

## Zakres tego wdrożenia

To wdrożenie nie zmienia:
- mechaniki egzaminu w trakcie rozwiązywania
- logiki `Zen mode`
- logiki `Nauki klasycznej`

Zakres dotyczy wyłącznie:
- zakończonego widoku egzaminu
- danych potrzebnych do jego renderu
- regresji testowej dla `mode=exam`
