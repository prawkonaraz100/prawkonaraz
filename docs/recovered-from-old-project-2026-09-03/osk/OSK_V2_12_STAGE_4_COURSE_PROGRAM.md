# OSK V2.12 — Etap 4A: wersjonowany program teorii

**Status:** zakończony lokalnie, bez UI, routingu, danych produkcyjnych i deployu.
**Data weryfikacji:** 2026-08-25.
**Branch implementacyjny:** `codex/osk-course-program-pilot-b`.

Ten dokument opisuje wyłącznie fundament programu nauki teorii. Nie jest opisem obecnego modułu `/nauka`, nie jest materiałem dydaktycznym dla kursanta i nie stanowi tabeli wymagań formalnych OSK.

## 1. Zakres wykonany

Dodano wyłącznie addytywne elementy pod istniejący `CourseVersion`:

```text
CourseVersion
  -> CourseModule
    -> CourseLesson
      -> CourseLessonStep
```

| Warstwa | Tabela | Rola |
| --- | --- | --- |
| Moduł | `course_modules` | część programu i jej kolejność |
| Lekcja | `course_lessons` | mała, uporządkowana jednostka nauki |
| Krok lekcji | `course_lesson_steps` | pojedynczy element do przyszłego renderowania |

Każda warstwa ma własny `code`, pozycję i unikalność w zakresie rodzica. Foreign keys są addytywne; w normalnym flow modelowym kaskadowe usunięcie dotyczy wyłącznie draftu, ponieważ wersje opublikowane są blokowane przed usunięciem.

## 2. Kontrakt kroków

`CourseLessonStep` obsługuje następujące typy:

```text
content | image | video | question | scenario | summary
```

oraz cele renderowania:

```text
HOOK | TEACH | DEMO | PRACTICE | EXAM | CHECK | SUMMARY
```

`content` jest elastycznym JSON-em kroku. Etap 4A nie interpretuje jeszcze jego pól, nie tworzy renderera i nie zapisuje czasu formalnego. Dzięki temu przyszły renderer ma stabilny kontrakt, ale dzisiejsza implementacja nie udaje gotowego kursu.

## 3. Publikacja i niezmienność

`CourseProgramService` jest jedyną nową ścieżką zapisu domenowego dla programu:

1. `addModule`, `addLesson` i `addStep` zapisują dane wyłącznie do wersji `DRAFT`.
2. `publish` blokuje rekordy, wymaga co najmniej jednego modułu, lekcji i kroku na każdym poziomie, a następnie atomowo zapisuje canonical snapshot oraz `content_hash`.
3. `forkNextDraft` kopiuje pełny program opublikowanej wersji do nowego draftu o kolejnym numerze.

Po ustawieniu `published_at` nie można:

- zmienić snapshotu, numeru, kursu ani wersji wymagań;
- zmienić ani usunąć modułu, lekcji lub kroku przez modele Eloquent;
- usunąć opublikowanej wersji ani kursu, który taką wersję zawiera.

Zarchiwizowana wersja pozostaje zamrożona, ponieważ zachowuje `published_at`.

### Ważna granica techniczna

Bezpośredni `query()->update()` i ręczne SQL mogą ominąć eventy modelu Eloquent. W Etapie 4A nie istnieje żaden endpoint, panel ani command wykonujący takie zapisy. Kolejny ekran administracyjny musi korzystać z `CourseProgramService`; nie wolno dodawać masowych update'ów dla opublikowanych wersji.

## 4. Pilot kategorii B

Test `CourseProgramTest` tworzy mały, kontrolowany program kategorii B. Sprawdza kolejność dwóch modułów, kroki `content` i `scenario`, pełny zestaw obsługiwanych typów oraz fork wersji 2.

To jest fixture testowy, nie seed produkcyjny. System nie udostępnia jeszcze kursantom kursu B, jego treści ani wejścia z `/nauka`.

## 5. Świadomie poza zakresem

Etap 4A nie dodaje:

- routingu, kontrolerów, Inertia/Vue ani `LessonPlayer`;
- pełnej, zatwierdzonej treści programu kategorii B;
- formalnego czasu, heartbeatów, evidence lub ukończenia;
- assessmentów, egzaminu wewnętrznego, PAPER, PDF ani Browser Exam Station;
- połączenia z `StudySession`, `QuestionCollection`, `ProductAccessGrant`, checkoutem B2C lub obecną stroną `/nauka`;
- seeda produkcyjnego, feature flagi lub deployu.

## 6. Weryfikacja lokalna

W lokalnym Dockerze:

```text
php artisan migrate --force
2026_08_25_120000_create_osk_course_program_foundation [61] Ran

php artisan test tests/Feature/Osk/OrganizationFoundationTest.php \
  tests/Feature/Osk/EnrollmentCreditsTest.php \
  tests/Feature/Osk/LegalRequirementsTest.php \
  tests/Feature/Osk/CourseProgramTest.php --compact
29 tests passed, 173 assertions

CourseProgramTest
6 tests passed, 36 assertions
```

`pint --test` przeszedł dla wszystkich nowych modeli, migracji, factory, serwisu i testów Etapu 4A.

W trakcie regresji wykryto niezależny błąd API istniejącego modułu nauki: zamknięcie w `StudySessionApiPayloadBuilder` nie przejmowało mapy publicznych wyjaśnień. Naprawa była jednym osobnym commitem i została pokryta przez `ApiSessionTest`: `12` testów / `244` asercje. Nie zmienia ona kontraktu OSK.

## 7. Następny krok: Etap 4B

Przed kodem należy uzgodnić niewielki, merytoryczny pilot kategorii B: właściciela treści, zakres jednego modułu i źródło każdej treści. Następnie można utworzyć odseparowany od `/nauka` renderer:

```text
opublikowany CourseVersion snapshot
  -> LessonPlayer
  -> StepRenderer
```

Renderer czyta wyłącznie wersję przypiętą do enrollmentu. Nie implementuje formalnego czasu, ukończenia ani assessmentu; te obszary należą do Etapu 5.
