# OSK V2.12 — Etap 4B: odizolowany LessonPlayer

**Status:** techniczna warstwa playera zakończona lokalnie; cykl sesji został dodany później w Etapie 5D, a pilot treści kategorii B, feature flag i deploy pozostają poza zakresem.
**Data weryfikacji:** 2026-08-25.
**Branch implementacyjny:** `codex/osk-course-program-pilot-b`.

Ten dokument opisuje pierwszy odczytowy ekran nowej Nauki teorii OSK. Nie opisuje obecnego modułu `/nauka`, nie tworzy formalnego zapisu szkolenia i nie stanowi zgody na publikację materiału dla kursantów.

## 1. Co zostało dodane

Nowy, odizolowany od B2C obszar ma trzy trasy:

```text
GET /osk/nauka
GET /osk/nauka/{courseEnrollment}
GET /osk/nauka/{courseEnrollment}/moduly/{moduleCode}/lekcje/{lessonCode}
```

Wszystkie wymagają zalogowanego i zweryfikowanego użytkownika. Ekran listy pokazuje tylko kursy należące do zalogowanego kursanta. Wejście do kursu prowadzi do pierwszej lekcji zamrożonego programu.

Nowe elementy kodu:

```text
app/Domain/Osk/TheoryLearning/TheoryLearningAccessService.php
app/Domain/Osk/TheoryLearning/PublishedCourseProgramPayloadBuilder.php
app/Http/Controllers/Osk/TheoryLearningController.php
routes/osk.php
resources/js/Pages/Osk/TheoryLearning/
```

`LessonPlayer` ma własną nawigację po modułach i lekcjach. `StepRenderer` obsługuje kontraktowe typy kroku:

```text
content | image | video | question | scenario | summary
```

## 2. Reguły dostępu i snapshotu

`TheoryLearningAccessService` jest fail-closed. Kursant zobaczy program wyłącznie wtedy, gdy równocześnie:

1. `CourseEnrollment` należy do jego `User`;
2. istnieje `StudentCourseAccess` ze statusem `ACTIVE`, który nie wygasł;
3. przypięty `CourseVersion` jest opublikowany lub zarchiwizowany, ale nadal zamrożony;
4. snapshot ma schemat `OSK_COURSE_PROGRAM_V1` i kompletną hierarchię moduł → lekcja → krok.

Brak któregokolwiek warunku kończy się `404`. Dzięki temu inny kursant nie może odczytać cudzego enrollmentu ani sprawdzać, czy istnieje.

Player czyta wyłącznie `CourseVersion.snapshot`; nie odczytuje żywych rekordów `CourseModule`, `CourseLesson` ani `CourseLessonStep`. Opublikowanie kolejnego draftu nie zmieni widoku kursanta, który jest nadal przypięty do poprzedniej wersji.

Zarchiwizowana wersja pozostaje dostępna dla aktywnego enrollmentu, ponieważ archiwizacja w tej architekturze zatrzymuje nowe użycie wersji, lecz nie niszczy istniejącego, zamrożonego programu kursanta.

## 3. Świadomie poza zakresem Etapu 4B

Sam odczytowy kontrakt Etapu 4B nie definiował i nie zapisuje:

- formalnego postępu i ukończenia nauki;
- operacyjnego cyklu sesji czasu i heartbeatów;
- postępu, ukończenia lekcji lub modułu;
- odpowiedzi, assessmentu, wyniku ani formalnego egzaminu;
- evidence, PAPER, PDF ani danych do dokumentacji;
- integracji z `StudySession`, `QuestionCollection`, `ProductAccessGrant`, checkoutem B2C lub `/nauka`.

Operacyjny cykl `start/resume`, heartbeat i `close` jest odizolowanym rozszerzeniem opisanym w [Etapie 5C](./OSK_V2_12_STAGE_5C_SESSION_ENDPOINTS.md) oraz [Etapie 5D](./OSK_V2_12_STAGE_5D_LESSON_PLAYER_SESSION_UX.md). Nie tworzy on jeszcze postępu, ukończenia ani formalnego evidence.

Krok `question` jest obecnie tylko odczytowym materiałem do przemyślenia. Nie zapisuje odpowiedzi i nie sugeruje, że kursant zaliczył cokolwiek formalnie.

## 4. Stan treści pilota B

Testy tworzą kontrolowany pilot techniczny kategorii B obejmujący wszystkie typy kroków. To fixture testowy, nie seed danych produkcyjnych i nie zatwierdzony materiał prawny.

Przed pierwszym realnym kursem trzeba zatwierdzić:

1. właściciela treści;
2. jeden mały zakres merytoryczny kategorii B;
3. źródło i przegląd każdej treści, obrazu oraz materiału wideo;
4. sposób udostępnienia jednemu pilotażowemu OSK przez feature flagę lub równoważną bramę;
5. osobny plan testu ręcznego aktywacji oraz dostępu kursanta.

Nie dodawaj automatycznie seeda ani odnośnika w głównym menu przed tymi decyzjami.

## 5. Weryfikacja lokalna

```text
php artisan test tests/Feature/Osk/TheoryLearningPlayerTest.php --compact
7 tests passed, 76 assertions
```

Testy potwierdzają:

- dostęp wyłącznie dla właściciela aktywnego enrollmentu;
- blokadę cudzej, oczekującej, wygasłej i nieprawidłowej wersji;
- czytanie snapshotu po opublikowaniu kolejnego draftu;
- dostęp do zarchiwizowanej, ale nadal zamrożonej wersji;
- wymóg zalogowania i weryfikacji e-maila.
- obecność wszystkich tras playera w konfiguracji Ziggy dla Inertia.

Laravel Pint przeszedł dla nowych plików PHP. Lokalny Vite został naprawiony po pierwotnym błędzie manifestu: kontener ma dostęp do `vendor/tightenco/ziggy`, HMR wskazuje `localhost`, a `npm run build` przeszedł 2026-08-25. To jest wyłącznie poprawka lokalnego środowiska developerskiego; nie zmienia kontraktu playera ani nie stanowi deployu OSK.

## 6. Następny krok

Po ręcznym smoke teście Etapu 5D i zatwierdzeniu małego pilota treści można projektować osobne modele postępu, assessmentu i evidence. Nie wolno zastępować ich stanem komponentu Vue ani istniejącymi `StudySession`.
