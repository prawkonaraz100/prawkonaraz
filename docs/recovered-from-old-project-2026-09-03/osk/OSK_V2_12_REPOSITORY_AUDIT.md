# Audyt repozytorium pod PrawkoNaRaz OSK V2.12

**Data:** 2026-08-24  
**Zakres:** drugi, pogłębiony przegląd lokalnego repozytorium przed rozpoczęciem implementacji OSK  
**Dokument źródłowy:** [PrawkoNaRaz OSK V2.12 CLEAN MASTER](./prawkonaraz_osk_v2_12_CLEAN_MASTER_existing_exam_module_audit.md)  
**Plan wdrożenia:** [OSK V2.12 Implementation Plan](./OSK_V2_12_IMPLEMENTATION_PLAN.md)

> **Status historyczny:** ten raport opisuje stan sprzed implementacji OSK i
> celowo zachowuje dawne braki w modelach oraz tabelach. Aktualny stan po
> Etapach 1-5P, wynik lokalnego pilota i następny krok są w
> [README](./README.md) oraz [planie wdrożenia](./OSK_V2_12_IMPLEMENTATION_PLAN.md).

## 1. Wynik w skrócie

Blueprint V2.12 jest możliwy do wdrożenia w tym projekcie, ale wyłącznie jako **nowy, odseparowany kontekst OSK**. Nie może być rozbudową obecnego `/nauka` ani istniejących kolekcji pytań.

Najważniejsza korekta po audycie:

> W repozytorium nie istnieje formalny moduł egzaminu wewnętrznego OSK do migracji. Istnieje działający symulator egzaminu państwowego dla kursanta. Jest to `StudySession` w trybie `exam`, a nie `InternalTheoryExamSession`.

Dlatego etap formalnego egzaminu z blueprintu musi powstać jako nowy moduł. Możemy później współdzielić niskopoziomowe elementy, takie jak renderer pytania, media i walidacja odpowiedzi, ale dopiero po osobnym audycie kontraktów tych komponentów.

## 2. Twarde granice domenowe

| Obszar | Obecny stan | Decyzja architektoniczna |
| --- | --- | --- |
| `/nauka` | Nauka pytań, powtórki, trening pamięci i próbny egzamin państwowy | **Zachować i izolować** |
| `StudySession` | Operacyjna sesja pojedynczego użytkownika; nowa sesja może zamknąć inną aktywną sesję tego użytkownika | **Nie używać** jako formalnej teorii ani egzaminu OSK |
| `QuestionCollection` / `QuestionModule` | Osobne kolekcje pytań, w tym zawodowa „Kwalifikacja wstępna przyspieszona — kat. C” | **Zachować jako ćwiczenia pytań**, nie zamieniać w `CourseVersion` |
| Baza `Question`, odpowiedzi i media | Dojrzałe, używane przez naukę i publiczne pytania | **Współdzielone źródło treści** |
| Nowa teoria OSK | Nie istnieje | **Zbudować osobno**: kurs, wersje, lekcje, kroki, assessmenty, evidence |
| Formalny egzamin wewnętrzny OSK | Nie istnieje | **Zbudować osobno**: reguły, sesje, odpowiedzi, formalni aktorzy, snapshoty |

### 2.1. Dlaczego `StudySession` nie może zostać formalnym zapisem OSK

W `app/Support/StudySessionManager.php` start nowej sesji aktualizuje wszystkie inne sesje użytkownika ze statusem `in_progress` do `completed`. To ma sens dla produktu treningowego, ale byłoby błędne dla formalnej historii szkolenia lub egzaminu.

Ponadto istniejąca sesja:

- jest przypisana wyłącznie do `user_id` i kategorii prawa jazdy;
- przechowuje bieżący stan w `payload` przeznaczonym dla UI i ćwiczeń;
- nie ma organizacji, enrollmentu, wersji programu, wersji reguł prawnych ani formalnego aktora;
- nie rozróżnia formalnego wyniku OSK od wyniku symulacji;
- aktualny tryb `exam` realizuje zestaw 20 pytań podstawowych + 12 specjalistycznych oraz własne timery symulacji.

To oznacza: zachowujemy ten moduł bez zmiany semantyki, a nie „migrujemy” go do OSK.

### 2.2. Dlaczego istniejąca „Kwalifikacja” nie jest nauką teorii OSK

Istniejące trasy `/nauka/kursy/{questionCollection:slug}` i `QuestionCollectionLearningController` uruchamiają moduły przez `StudySessionManager::startQuestionModule()`. Kolekcja przechowuje uporządkowane pytania i moduły pytań, nie lekcje, kroki, evidence ani formalny progres programu.

Repozytorium ma już zabezpieczenia tej separacji:

- `study_sessions.question_collection_id` i `study_sessions.question_module_id` oddzielają kontekst kursowej kolekcji od zwykłej kategorii;
- `StudySession::scopeRegularCategory()` pomija sesje kolekcji;
- kursowe błędne pytania są oddzielone od zwykłej listy;
- dostęp jest sterowany przez `QuestionCollectionAccessService` i przełącznik `is_available_to_learners`.

To jest dobry wzorzec izolacji, ale nie jest fundamentem formalnej teorii OSK.

## 3. Mapa techniczna potwierdzona w audycie

### 3.1. Istniejący moduł nauki pytań

- `app/Models/StudySession.php`
- `app/Models/StudySessionAnswer.php`
- `app/Support/StudySessionManager.php`
- `app/Http/Controllers/StudySessionController.php`
- `app/Http/Controllers/StudySessionAnswerController.php`
- `resources/js/Pages/StudySessions/Show.vue`
- `resources/js/Pages/StudySessions/Exam.vue`
- `resources/js/Pages/StudySessions/ExamResult.vue`

Jest to stabilny, chroniony moduł B2C. Regresja przeprowadzona wcześniej dla `StudySessionFlowTest` zakończyła się wynikiem **35 testów / 824 asercje**.

### 3.2. Istniejące kolekcje pytań

- `app/Models/QuestionCollection.php`
- `app/Models/QuestionModule.php`
- `app/Http/Controllers/QuestionCollectionLearningController.php`
- `app/Support/QuestionCollectionAccessService.php`
- `app/Support/QuestionCollectionProgressService.php`
- `database/migrations/2026_07_30_170000_create_question_collections_and_modules_tables.php`
- `database/migrations/2026_08_19_130000_add_course_context_to_study_sessions_and_collections.php`
- `database/migrations/2026_08_19_170000_create_question_collection_incorrect_questions_table.php`

To moduł pytań zawodowych i ich powtórek. Nie należy go nazywać ani implementować jako `CourseVersion` z V2.12.

### 3.3. Tożsamość, dostęp i panel administracyjny

- `app/Models/User.php` zawiera globalne role `admin`, `moderator`, `student` oraz zgodność wsteczną `is_admin`.
- `app/Support/ProductAccessResolver.php` i `ProductAccessGrant` sterują bieżącym dostępem B2C.
- `app/Http/Middleware/EnsureStudySessionAccess.php` chroni obecne sesje na podstawie dostępu produktu i kolekcji pytań.
- `app/Providers/Filament/AdminPanelProvider.php` udostępnia istniejący Filament pod `/admin`.
- Nie ma obecnie modeli organizacji, membershipów, enrollmentów, kredytów OSK ani katalogu `app/Policies`.

Wniosek: globalnych ról i dostępu B2C nie zastępujemy. Nowe role `ORG_OWNER`, `ORG_ADMIN`, `ORG_MEMBER` muszą mieszkać w relacji organizacyjnej, z oddzielnymi politykami / scope'ami tenantowymi.

### 3.4. Audit, kolejki i infrastruktura

- `app/Models/AuditLog.php` i `app/Support/AuditLogService.php` zapewniają aktora, request ID, IP, user agent oraz filtrowanie sekretów z metadanych.
- Globalny middleware `AssignRequestId` zapewnia identyfikację żądania.
- Redis i istniejący transport realtime dla rankingu są dostępne jako techniczne punkty odniesienia.
- Filament jest właściwą bazą dla administracji OSK.
- `composer.json` nie zawiera obecnie biblioteki do generowania PDF.

Wniosek: można rozszerzyć aktualny audit, ale formalne zdarzenia OSK muszą przechowywać dodatkowo `organization_id`, enrollment, operatora aplikacyjnego, formalnego aktora oraz snapshoty. Formalnych dokumentów nie budujemy bez canonical template i decyzji o generatorze PDF.

## 4. Zgodność z V2.12

| Wymaganie blueprintu | Stan repozytorium | Działanie |
| --- | --- | --- |
| Multi-tenant / organization | Brak | Zbudować od Etapu 1 |
| `CourseEnrollment` i `StudentCourseAccess` | Brak | Zbudować osobno od B2C access |
| Kredyty OSK z ledgerem | Brak | Zbudować transakcyjnie; nie używać `ProductAccessGrant` |
| Wersjonowany kurs teorii | Brak | Zbudować `CourseVersion` i niezmienne snapshoty |
| Time Evidence / completion gate | Brak | Zbudować od surowych zdarzeń |
| PAPER | Brak generatora i wzoru | Poczekać na canonical template oraz decyzję techniczną |
| Internal Theory Exam | Brak formalnego silnika | Zbudować osobno; najpierw audyt rendereru / kalkulacji symulatora |
| Browser Exam Station | Brak domeny, jest transport realtime | Później, po silniku formalnego egzaminu |
| Rezerwacje jazd i kalendarz | Poza zakresem użytkownika | Nie budować w tej inicjatywie |

## 5. Ryzyka i wymagane zabezpieczenia

1. **Kolizja nazewnictwa „kurs”.** UI może używać prostego słowa „kurs”, ale w kodzie trzeba rozróżniać `QuestionCollection` od formalnego `CourseVersion` OSK.
2. **Przeciek danych między OSK.** Ponieważ projekt nie ma dziś polityk tenantowych, Etap 1 musi od razu wprowadzić scope organizacji i testy negatywne między tenantami.
3. **Pomieszanie dostępu B2C z OSK.** Zakup indywidualny i kredyt organizacji to dwa niezależne źródła prawa dostępu.
4. **Pomieszanie dowodu systemowego z formalnym wpisem.** `StudySession` może być tylko informacją pomocniczą; dane formalne wymagają nowych, niezmiennych zdarzeń i akceptacji OSK.
5. **Reguły prawne.** Blueprint zawiera model, lecz konkretne reguły, retencja i wzory dokumentów muszą mieć wersję, źródło oraz zatwierdzenie właściciela prawnego.
6. **PDF.** Brak biblioteki PDF i canonical template oznacza, że dokumentów PAPER nie należy zaczynać od własnego wyglądu.
7. **Browser Exam Station.** Realtime może przyspieszać interfejs, ale baza / HTTP pozostają source of truth; nie można przenosić semantyki modułu rankingowego.

## 6. Zalecana granica kodu

Nowy kod powinien zaczynać się poza aktualnym modułem nauki:

```text
app/Domain/Osk/
  Organizations/
  Enrollments/
  Access/
  Requirements/
  TheoryLearning/
  Evidence/
  Documents/
  InternalTheoryExam/
  SharedQuestionBridge/

routes/osk.php
resources/js/Pages/Osk/
```

Pierwsze migracje mają być addytywne. Nie wolno w pierwszych etapach zmieniać semantyki ani kolumn `study_sessions`, `study_session_answers`, `question_collections`, `product_access_grants` i istniejących tras `/nauka`.

## 7. Testy wykonane w ramach drugiego audytu

W lokalnym Dockerze, bez zmian danych aplikacyjnych, przeszły:

```text
php artisan test \
  tests/Feature/QuestionCollectionLearningTest.php \
  tests/Feature/QuestionCollectionAccessTest.php \
  tests/Feature/ProductAccessGateTest.php \
  tests/Feature/AuditLoggingTest.php \
  --compact

37 tests passed, 441 assertions
```

Testy potwierdzają między innymi izolację postępu kolekcji od zwykłej nauki, kontrolę dostępu do kursu pytań, dostęp B2C i zapisy do audit logu.

## 8. Stan gotowości do implementacji

Jesteśmy gotowi do **Etapu 1: Organization + membership + tenant isolation**, pod warunkiem że pierwszy commit zawiera wyłącznie fundament multi-tenant oraz testy. Nie jesteśmy jeszcze gotowi do formalnego egzaminu, Browser Exam Station ani dokumentów PAPER.

Przed Etapem 7 trzeba wykonać osobny, komponentowy audyt istniejącego rendereru pytania, kalkulacji wyniku, timerów, mediów i frontendu egzaminu próbnego. Jego celem będzie wybór małego adaptera / współdzielonego kontraktu, nie migracja `StudySession`.

## 9. Decyzje nadal wymagające od właściciela produktu

- pierwsze OSK pilotażowe i pierwsza obsługiwana kategoria;
- docelowy model kredytów i okres dostępu kursanta;
- canonical wzory dokumentów PAPER oraz właściciel akceptacji prawnej;
- retencja danych, zwłaszcza negatywnych wyników egzaminu;
- pierwszy tryb obsługi egzaminu wewnętrznego: `IN_PLATFORM`, `EXTERNAL_OSK` czy `NOT_MANAGED`.
