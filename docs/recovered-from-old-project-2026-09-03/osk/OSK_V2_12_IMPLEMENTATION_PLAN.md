# Plan wdrożenia PrawkoNaRaz OSK V2.12

**Status:** Etapy 1–3, Etap 4A, techniczna część Etapu 4B oraz Etapy 5A–5P wdrożone lokalnie; źródłowy kurs kategorii B ma opublikowaną i zatwierdzoną wersję V1, jeden kontrolowany pilot lokalny przeszedł pełny smoke HTTP, onboarding B2B OSK jest ręcznie zatwierdzany i fail-closed, a zapisy kursantów mają operacyjną obsługę, konto gotowe przy biurku oraz fail-closed preflight e-maila z hasłem. Brak deployu OSK.
**Ostatnia weryfikacja repozytorium:** 2026-08-30
**Punkt wejścia i przekazanie pracy:** [OSK V2.12 — start](./README.md)  
**Dokument źródłowy:** [PrawkoNaRaz OSK V2.12 CLEAN MASTER](./prawkonaraz_osk_v2_12_CLEAN_MASTER_existing_exam_module_audit.md)
**Raport techniczny:** [Audyt repozytorium OSK V2.12](./OSK_V2_12_REPOSITORY_AUDIT.md)
**Audyt zgodności z blueprintem:** [OSK V2.12 — zgodność blueprintu](./OSK_V2_12_BLUEPRINT_CONSISTENCY_AUDIT.md)
**Kontrakt Etapu 3:** [Reguły prawne i fakty enrollmentu](./OSK_V2_12_STAGE_3_LEGAL_REQUIREMENTS.md)
**Kontrakt Etapu 4A:** [Wersjonowany program teorii](./OSK_V2_12_STAGE_4_COURSE_PROGRAM.md)
**Kontrakt Etapu 5A:** [Fundament źródłowych sesji czasu](./OSK_V2_12_STAGE_5A_TIME_EVIDENCE_FOUNDATION.md)
**Kontrakt Etapu 5B:** [Walidacja źródłowego czasu](./OSK_V2_12_STAGE_5B_TIME_EVIDENCE_VALIDATOR.md)
**Kontrakt Etapu 5C:** [Cykl sesji przez HTTP](./OSK_V2_12_STAGE_5C_SESSION_ENDPOINTS.md)
**Kontrakt Etapu 5D:** [Sesja w LessonPlayerze](./OSK_V2_12_STAGE_5D_LESSON_PLAYER_SESSION_UX.md)
**Kontrakt Etapu 5E:** [Kursor postępu lekcji](./OSK_V2_12_STAGE_5E_LESSON_PROGRESS.md)
**Kontrakt Etapu 5F:** [Kontrolowany rollout pilota](./OSK_V2_12_STAGE_5F_PILOT_ROLLOUT.md)
**Kontrakt Etapu 5I:** [Gotowość enrollmentu do pilota](./OSK_V2_12_STAGE_5I_PILOT_READINESS.md)
**Kontrakt Etapu 5J:** [Dwa bezpieczne kanały zapisu kursanta](./OSK_V2_12_STAGE_5J_ENROLLMENT_PROVISIONING.md)
**Kontrakt Etapu 5K:** [Aktywacja kursu do lokalnego pilota](./OSK_V2_12_STAGE_5K_COURSE_PILOT_ACTIVATION.md)
**Kontrakt Etapu 5L:** [Katalog kursów kategorii B](./OSK_V2_12_STAGE_5L_COURSE_CATALOG.md)
**Kontrakt Etapu 5M:** [Bezpieczny onboarding B2B OSK](./OSK_V2_12_STAGE_5M_SAFE_B2B_ONBOARDING.md)
**Kontrakt Etapu 5N:** [Operacyjna obsługa zapisów kursantów](./OSK_V2_12_STAGE_5N_ENROLLMENT_OPERATIONS.md)
**Kontrakt Etapu 5O:** [Konto gotowe przy biurku OSK](./OSK_V2_12_STAGE_5O_ACTIVATION_LINK_DELIVERY.md)
**Plan Etapu 5P:** [Gotowość do kontrolowanego pilota produkcyjnego](./OSK_V2_12_STAGE_5P_PRODUCTION_PILOT_READINESS.md)

## 1. Najważniejsze rozróżnienie architektoniczne

W projekcie istnieją i będą istnieć trzy odrębne obszary.

### A. Obecna `Nauka` = testy egzaminacyjne

Aktualna ścieżka `/nauka` jest modułem przygotowania do pytań egzaminacyjnych na prawo jazdy. Obejmuje między innymi:

- sesje pytań i odpowiedzi,
- tryb nauki, powtórek, trudnych pytań i egzaminu próbnego,
- multimedia, wyjaśnienia, audio i oznaczenia na mediach,
- postęp kursanta w pytaniach,
- istniejące kolekcje i moduły pytań.

Jej główne elementy to obecnie `StudySession`, `StudySessionAnswer`, `StudySessionManager`, `QuestionCollection` oraz `QuestionModule`.

**Ten moduł zostaje zachowany.** Nie zmieniamy jego znaczenia na formalną naukę teorii OSK i nie uznajemy jego obecnego czasu, wyniku ani ukończenia za formalny zapis szkolenia.

### B. Nowa `Nauka teorii OSK`

To nowy moduł z blueprintu V2.12. Będzie prowadził kursanta przez zaprojektowany program teorii:

```text
CourseVersion
-> Module
-> Lesson
-> LessonStep
-> ModuleAssessment
```

Jego zadaniem jest przekazywanie treści, pilnowanie kolejności, zapisywanie czasu i dowodów nauki oraz przygotowanie danych do dokumentacji PAPER.

To jest canonical Learning Engine z blueprintu: ma zostać zbudowany raz, jako silnik wielokrotnego użycia dla kursów, a organizacja, enrollment i wymagania prawne mają być jego kontekstem. Nie powstaje osobny silnik lekcji „dla OSK” i drugi „dla B2C”. Obecny `StudySession` jest wyłącznie silnikiem treningu pytań i nie jest wcześniejszą implementacją tego Learning Engine.

### C. Formalny egzamin wewnętrzny z teorii

To trzeci moduł: formalny lub diagnostyczny egzamin wewnętrzny OSK. Nie jest on ani zwykłą lekcją, ani bieżącą sesją testową.

```text
InternalTheoryExamRulesVersion
-> InternalTheoryExamSession
-> InternalTheoryExamAnswer
-> wynik / evidence / wpis do enrollmentu
```

## 2. Zasada współpracy modułów

Moduły mogą się uzupełniać, lecz nie mogą mieszać odpowiedzialności.

```text
Wspólna baza pytań, mediów i znaków
                 |
    +------------+-------------+
    |                          |
Nauka testów                Nauka teorii OSK
StudySession                LessonPlayer / Time Evidence
    |                          |
    +----------- opcjonalne ---+
                 |
       trening pytań po lekcji
                 |
      Formalny egzamin wewnętrzny
```

### Dozwolone integracje

- Lekcja OSK może skierować kursanta do ćwiczenia konkretnych pytań w obecnym module testowym.
- Oba moduły mogą czytać tę samą bazę `Question`, media i wyjaśnienia.
- Panel OSK może później pokazywać wynik ćwiczeń jako **informację pomocniczą**.
- Formalny egzamin może w przyszłości współdzielić renderer pytania, walidację opcji i obsługę mediów po wydzielonym audycie tych fragmentów.

### Niedozwolone skróty

- Nie dopisujemy `organization_id`, formalnego czasu ani statusu ukończenia OSK do `study_sessions`.
- Nie uznajemy ukończenia sesji testowej za ukończenie lekcji, modułu lub teorii formalnej.
- Nie podłączamy `ProductAccessGrant` bezpośrednio do enrollmentu OSK.
- Nie przerabiamy `QuestionCollection` na `CourseVersion`.
- Nie używamy obecnego trybu `exam` jako formalnego egzaminu wewnętrznego bez osobnego modelu, wersji reguł i evidence.

### Zasady blueprintu, które obowiązują wszystkie etapy

- **Operational availability != formal requirement:** funkcja operacyjna nie czeka na ukończenie Learning Engine, chyba że konkretny wymóg formalny wyraźnie tego wymaga.
- **ModuleAssessment != egzamin wewnętrzny:** assessment kończy moduł; formalny egzamin ma osobną wersję reguł, sesję, wynik i evidence.
- **System Evidence != zaakceptowana dokumentacja OSK:** evidence jest źródłowym zapisem systemu; akceptacja przez OSK i formalny dokument to następne warstwy.
- **Źródła nie są cicho edytowane:** sesje, assessmenty, evidence i formalne dokumenty są immutable lub append-only; korekta tworzy nowy wpis albo wersję z powodem i aktorem.
- **Wyjątek prawny zmienia wymagania enrollmentu:** nie tworzymy pojedynczego `exemption` ani wyjątków zaszytych w Vue, kontrolerze egzaminu lub logice Learning Engine.
- **PAPER przed ELECTRONIC:** formalny PDF używa zaakceptowanego canonical template i nie wygląda jak materiał marketingowy PrawkoNaRaz.

## 3. Stan bazowy repozytorium przed implementacja OSK

Ponizsza tabela jest historycznym wynikiem audytu z 2026-08-24 i wyjasnia,
dlaczego OSK powstalo jako osobny kontekst. Nie opisuje aktualnego stanu po
Etapach 1-5N; aktualne wykonanie jest w sekcji 9 i w [README](./README.md).

| Obszar V2.12 | Stan obecny | Decyzja |
| --- | --- | --- |
| Baza pytań, odpowiedzi i mediów | Dojrzała, używana przez `StudySession` | **KEEP** jako współdzielone źródło treści |
| Nauka testów `/nauka` | Działający niezależny moduł B2C | **KEEP ISOLATED** |
| Sesja testowa `StudySession` | Zapisuje pytania, odpowiedzi, czas i wynik; jedna aktywna sesja użytkownika | **KEEP ISOLATED**, nie jest formalnym zapisem OSK |
| Egzamin próbny | Symulator państwowy: 20 pytań podstawowych + 12 specjalistycznych, timer i wynik po stronie serwera | **KEEP ISOLATED**; nie jest istniejącym formalnym egzaminem OSK do migracji |
| `QuestionCollection` i `QuestionModule` | Kolekcje pytań z dostępem opartym o bieżący produkt; obejmują też zawodową „Kwalifikację” | **KEEP ISOLATED**, mogą być materiałem ćwiczeniowym, nie `CourseVersion` ani kursem formalnym |
| Użytkownicy i role | Globalne `admin`, `moderator`, `student` | **EXTEND**, dodać organizacyjne członkostwa zamiast zmieniać obecne role |
| Dostęp i płatności B2C | `ProductAccessGrant`, `PurchaseOrder` | **KEEP ISOLATED**, wykorzystać jedynie wzorce transakcyjne |
| Audit log | Ogólny `AuditLog` z aktorem i metadanymi | **KEEP WITH EXTENSION**, formalne zdarzenia OSK wymagają własnego kontekstu i niezmienności |
| WebSocket / Redis | Działający transport dla rankingu | **REUSE AS TECHNICAL REFERENCE**, nie wiązać z rankingiem |
| Filament | Działający panel administracyjny | **KEEP**, dobra baza dla biura OSK |
| Organizacje, enrollmenty, kadra | Brak | **BUILD** |
| Kurs wersjonowany, lekcje, kroki | Brak | **BUILD** |
| Czas formalny i evidence | Brak | **BUILD** |
| Dokumenty PAPER i PDF | Brak formalnego generatora i canonical template | **BUILD** |
| Egzamin wewnętrzny OSK / stanowisko | Brak | **BUILD** po fundamencie OSK |

## 4. Wynik audytu obecnego modułu testów

Przeprowadzony audyt potwierdził, że bieżący moduł jest technicznie stabilną bazą ćwiczeń, ale nie jest modelem formalnego szkolenia OSK.

### Potwierdzone elementy

- `StudySessionManager` wybiera pełny zestaw próbnego egzaminu, prowadzi timer po stronie serwera i zapisuje odpowiedzi.
- `StudySessionAnswer` ma trwały zapis odpowiedzi, czasu odpowiedzi i wyniku.
- Widoki `StudySessions/Exam.vue` oraz wynik egzaminu istnieją i korzystają z bieżących endpointów `/nauka/teraz`.
- Test regresyjny `StudySessionFlowTest` przeszedł lokalnie w Dockerze: **35 testów, 824 asercje**.
- `QuestionCollection` jest obecnie częścią ćwiczeń pytań, a nie silnikiem lekcji lub formalnego kursu.
- Drugi audyt potwierdził, że kolekcja „Kwalifikacja wstępna przyspieszona — kat. C” również uruchamia `StudySession` i jest kursem pytań, nie nauką teorii OSK.
- Drugi zestaw regresji dla kolekcji, dostępu B2C i audit logu przeszedł lokalnie: **37 testów, 441 asercje**.

### Dlaczego nie wolno użyć go jako formalnego kursu

- Sesja testowa jest przypisana do indywidualnego użytkownika, nie do organizacji i enrollmentu.
- Mechanizm startu nowej sesji zamyka inne aktywne sesje tego samego użytkownika.
- Stan jest przechowywany jako operacyjny payload sesji, bez snapshotu wersji programu, reguł prawnych i formalnego aktora.
- Nie ma modelu required theory time, evidence, akceptacji danych przez OSK, dokumentu PAPER ani historii wersji programu.

Wniosek: obecny moduł ma być chronioną funkcją produktu, a jego ewentualne połączenia z OSK muszą zostać wykonane przez jawny adapter, nie przez rozszerzanie istniejących tabel.

## 5. Docelowe granice kodu

Nowe moduły powinny być odseparowane od istniejącej nauki w kodzie, trasach, bazie i uprawnieniach.

```text
app/Domain/Osk/
  Organizations/
  Enrollments/
  Requirements/
  TheoryLearning/
  Evidence/
  Documents/
  InternalTheoryExam/
  SharedQuestionBridge/

routes/osk.php                 # tylko panel OSK i kursant OSK
resources/js/Pages/Osk/        # oddzielne widoki
```

Nazwy tabel powinny być zgodne z V2.12, np. `organizations`, `organization_users`, `course_enrollments`, `student_course_accesses`, `course_versions`, `module_assessments` i `internal_theory_exam_sessions`.

Nowe migracje mają być wyłącznie addytywne. Na etapie fundacji nie zmieniamy struktury ani semantyki `study_sessions`, `study_session_answers`, `product_access_grants` ani istniejącego `/nauka`.

## 6. Plan wdrożenia

### Etap 0 — Zablokowanie granic i kontraktów

**Cel:** przygotować bezpieczny punkt startu bez zmian produktowych.

- [x] Zachowano oryginalny blueprint V2.12 w `docs/osk/`.
- [x] Przeprowadzono pierwszy audyt istniejącego modułu testów i egzaminu próbnego.
- [x] Przeprowadzono drugi, pogłębiony audyt repozytorium i zapisano go w `OSK_V2_12_REPOSITORY_AUDIT.md`.
- [x] Potwierdzono rozdzielenie `Nauka testów` i `Nauka teorii OSK`.
- [x] Potwierdzono, że nie istnieje formalny moduł egzaminu wewnętrznego OSK do migracji; obecny `exam` jest symulatorem testowym.
- [x] Potwierdzono, że obecna „Kwalifikacja” jest kolekcją pytań i pozostaje poza formalną teorią OSK.
- [x] Ustalono, że kalendarz i rezerwacje jazd nie wchodzą do obecnego zakresu.
- [x] Porównano plan z blueprintem; obowiązkowe interpretacje zapisano w `OSK_V2_12_BLUEPRINT_CONSISTENCY_AUDIT.md`.
- [ ] Przed Etapem 7 sporządzić komponentowy audyt rendereru pytania, kalkulacji wyniku, timerów i mediów jako kandydatów do adaptera formalnego egzaminu.
- [ ] Przy Etapie 1 dodać kontraktowe testy regresyjne istniejącego `/nauka` do pipeline'u zmian OSK.

**Brama wyjścia:** żaden element OSK nie zmienia zachowania istniejącego modułu testów.

### Etap 1 — Organizacja i uprawnienia multi-tenant

**Cel:** wprowadzić bezpieczną przestrzeń OSK.

- `Organization`, `OrganizationUser`, uprawnienia i zakres tenantowy.
- Nazewnictwo OSK jest canonical: `organization_users`, nie starsze `organization_members` z ogólnego szkicu V2.
- Klucze nowych tabel pozostają zgodne z istniejącym Laravelowym `id` / `foreignId`; UUID wymagają osobnej decyzji architektonicznej.
- `ORG_OWNER`, `ORG_ADMIN`, `ORG_MEMBER` jako role w organizacji, bez zastępowania globalnych ról `User`; invariant: `permissions(ORG_OWNER)` obejmuje uprawnienia każdej innej roli organizacyjnej.
- `OrganizationStaffMember`, kwalifikacje i formalne role personelu; `linked_user_id` jest nullable, ponieważ instruktor w PAPER nie musi mieć konta aplikacji.
- Rozdzielić osobę zalogowaną wykonującą akcję od formalnego pracownika OSK; operator nie może być cicho zapisany jako egzaminator tylko dlatego, że ma uprawnienie.
- Dodać polityki, testy izolacji tenantów i audit krytycznych akcji, w tym test, że `ORG_OWNER` widzi każdy ekran dostępny dla `ORG_ADMIN`.

**Brama wyjścia:** użytkownik jednej organizacji nie może odczytać ani zmienić danych drugiej organizacji.

**Stan realizacji: zakończony lokalnie (2026-08-25).**

- Dodano addytywną migrację `2026_08_25_090000_create_osk_organization_foundation` z tabelami `organizations`, `organization_users`, `organization_staff_members`, `organization_staff_qualifications` i `organization_formal_roles`.
- Dodano modele, factory oraz enumy ról, typów formalnych i product permissions OSK. Role globalne `User::role` pozostały niezmienione.
- `ORG_OWNER` ma w kodzie wirtualny pełny zestaw uprawnień i nie przechowuje ręcznie ograniczanej listy permissions; delegowany użytkownik nie może otrzymać uprawnienia niedostępnego dla właściciela ani zarządzać właścicielem.
- Formalny staff jest odrębny od użytkownika aplikacji; `linked_user_id` może pozostać pusty. Kwalifikacje i formalne role mają własne, niezależne rekordy.
- Dodano polityki organizacji, membershipu i staffu oraz serwisy dostępu i administracji organizacyjnej. Domyślny globalny `admin` nie omija tenant scope.
- Rozszerzono istniejący `AuditLog` o nullable kontekst `organization_id`, `organization_user_id` i `permission_used`; wszystkie istniejące wpisy audytu pozostają kompatybilne.
- Potwierdzenia: PostgreSQL `migrate --pretend` oraz lokalne `migrate`, Laravel Pint, 4 nowe testy OSK / 29 asercji oraz regresja istniejących modułów: 72 testy / 1265 asercji.
- Nie dodano routingu, kontrolerów, ekranów, enrollmentu, kredytów, lekcji, egzaminu, PAPER, płatności ani zmian w `/nauka`.

### Etap 2 — Enrollment, dostęp kursanta i kredyty OSK

**Cel:** OSK może dodać kursanta i dać mu dostęp do konkretnego kursu.

- `CourseEnrollment` jako formalny kontekst kursanta.
- `StudentCourseAccess` jako techniczne prawo wejścia do kursu.
- Organizacyjny ledger kredytów: `GRANT`, `PURCHASE`, `RESERVE`, `CONSUME`, `RELEASE`, `ADJUSTMENT`.
- Aktywacja kursanta oraz ograniczenie czasowe dostępu zgodne z V2.12.
- QR lub jednorazowy token aktywacyjny służy wyłącznie kursantowi do aktywacji dostępu. Nie jest credentialem Browser Exam Station i nie może być użyty jako standardowy login/PIN/QR do egzaminu.
- Niezależność od istniejących zakupów B2C i `ProductAccessGrant`.

**Brama wyjścia:** dostęp OSK działa per organization + enrollment, a zakup B2C nadal działa bez zmian.

**Stan realizacji: zakończony lokalnie (2026-08-25).**

- Dodano addytywną migrację `2026_08_25_100000_create_osk_enrollment_credit_foundation` z tabelami `courses`, `course_versions`, `course_enrollments`, `student_course_accesses`, `student_course_activation_tokens` i `organization_credit_transactions`.
- `CourseEnrollment` jest formalnym kontekstem `organization + user + CourseVersion`; zachowuje docelowe uprawnienie, kategorię, tryby dokumentacji i egzaminu oraz wskazanych formalnych pracowników OSK. Nie ma żadnego odwołania do `StudySession`, `QuestionCollection` ani B2C access.
- Dodano minimalny `Course` i wersjonowany `CourseVersion`, ponieważ enrollment musi od początku wskazywać zamrożoną wersję programu. Snapshot i hash są utrwalane, a opublikowanej wersji nie można zmienić w miejscu. Pełny program modułów, lekcji i kroków nadal należy do Etapu 4.
- Nowa organizacja otrzymuje jednorazowo 5 bezterminowych kredytów startowych. Ledger jest append-only i idempotentny: `RESERVE = -1`, `CONSUME = 0` jako zdarzenie potwierdzające oraz `RELEASE = +1`; dzięki temu aktywacja nie może podwójnie pomniejszyć salda.
- Dodanie kursanta rezerwuje kredyt w transakcji, tworzy dostęp `PENDING` i jednorazowy token. Token jest przechowywany wyłącznie jako hash, wygasa po 14 dniach, może zostać unieważniony, a użycie jest jednorazowe.
- Nowo utworzone konto kursanta ma losowe, nieujawniane hasło i wyłączone logowanie hasłem do czasu aktywacji. Istniejący użytkownik może aktywować dostęp wyłącznie po uwierzytelnieniu jako ten sam użytkownik. To chroni istniejące konto przed przejęciem przez sam link aktywacyjny.
- Aktywacja startuje 90-dniowy dostęp w chwili użycia tokenu, nie przy dodaniu kursanta. Anulowanie oczekującego dostępu unieważnia token i zwalnia rezerwację. Przedłużenie aktywnego dostępu dodaje 30 dni.
- Nie dodano jeszcze route'ów, maili, QR, ekranów admina ani ekranu kursanta. Na tym etapie token jest kontraktem domenowym, a nie publicznym flow.
- Potwierdzenia: PostgreSQL `migrate --pretend` i lokalne `migrate`, Laravel Pint, 10 nowych testów OSK / 59 asercji oraz zielona regresja `StudySession`, kolekcji pytań, B2C access, checkoutu i audit logu. Brak zmian w `ProductAccessGrant`, `PurchaseOrder`, checkoutcie, `StudySession` lub `QuestionCollection`.

### Etap 3 — Reguły prawne i fakty enrollmentu

**Cel:** system liczy wymagania z faktów i wersjonowanych reguł, a nie z rozproszonych warunków w UI.

- `LegalRequirementsVersion`, `EnrollmentFacts`, `EnrollmentRequirements` i `RequirementAdjustments`.
- `EnrollmentRequirementsResolver`, `EntitlementEquivalenceResolver`, `StateExamEligibilityResolver`.
- `LegalReviewFlag` dla niejednoznacznych sytuacji.
- Żadnego pojedynczego `exemption = true/false`, hardcode wyjątku w Vue ani w kontrolerach. Wyjątek zawsze ma fakty, wersję reguły, źródło i wynik resolvera.
- Ustawienie operacyjne OSK nie może samo zamieniać wymogu formalnego ani blokować nauki, jeśli blokada nie wynika z konkretnego `EnrollmentRequirement`.
- Pełna macierz testów wyjątków przed podłączeniem ekranu kursanta.

**Brama wyjścia:** dla każdego enrollmentu można odtworzyć, z jakich faktów i wersji reguł wynikają wymagania.

**Stan realizacji: zakończony lokalnie (2026-08-25).**

- Dodano addytywną migrację `2026_08_25_110000_create_osk_legal_requirements_foundation` z tabelami `legal_requirements_versions`, `course_enrollment_facts`, `course_enrollment_requirements`, `course_enrollment_requirement_adjustments` i `legal_review_flags`.
- `LegalRequirementsVersion` ma status `DRAFT` / `PUBLISHED` / `ARCHIVED`, canonical hash reguł i źródło. Opublikowanej wersji nie można zmienić w miejscu.
- `CourseEnrollmentFacts` są append-only i wersjonowane per enrollment. Snapshot obejmuje docelowe uprawnienie, posiadane kategorie i ograniczenia, wyniki państwowe, uznane wyniki teorii, warunki szkolenia równoległego, kredyty wcześniejszego szkolenia, kontekst dostawcy, źródło oraz weryfikację.
- `CourseEnrollmentRequirement` jest niezmiennym wynikiem resolvera. Zawiera snapshot wymagań, hash, wersję faktów i wersję reguł. Każda korekta jest zapisana jako osobny `CourseEnrollmentRequirementAdjustment`.
- Resolver wymagań używa trzech rozdzielonych decyzji: wymagań enrollmentu, równoważności uprawnienia oraz gotowości do egzaminu państwowego. Nie przenosi logiki wyjątków do UI, kontrolerów ani obecnego `/nauka`.
- Sytuacja niejednoznaczna, brak reguły bazowej, nieobsługiwany warunek lub konflikt sposobu łączenia korekt kończy się bez wyniku formalnego i tworzy `LegalReviewFlag` powiązany z konkretną wersją faktów. Resolver nie zgaduje.
- Potwierdzenia: lokalna migracja PostgreSQL, Laravel Pint, 9 nowych testów Etapu 3 / 49 asercji, zestaw Etapów 1–3: 23 testy / 137 asercji oraz regresja 98 istniejących testów / 1399 asercji. Nie zmieniono `ProductAccessGrant`, `PurchaseOrder`, checkoutu, `StudySession` ani `QuestionCollection`.
- Nie dodano produkcyjnego rejestru reguł ani UI. Przed użyciem formalnym konkretne reguły i źródła muszą zostać zatwierdzone przez właściciela compliance; testowe reguły nie są deklaracją kompletności prawnej.

### Etap 4 — CourseVersion i program nauki teorii

**Cel:** przygotować wersjonowany program szkolenia niezależny od testów.

- `Course`, `CourseVersion`, moduły, lekcje, kroki i publikacja wersji.
- Snapshot, `content_hash` i `legal_requirements_version` dla opublikowanej wersji.
- Opublikowany `CourseVersion` jest immutable dla aktywnych enrollmentów; zmiana programu tworzy kolejną wersję, nie edycję w miejscu.
- Pierwszy zakres: jeden mały moduł teorii dla pilota, nie cały kurs naraz.
- Renderer `LessonPlayer` + `StepRenderer` dla `content`, `image`, `video`, `question`, `scenario`, `summary`.
- Opcjonalna akcja w lekcji: „Przećwicz pytania” prowadząca do istniejącego `/nauka` bez formalnego zaliczenia.

**Brama wyjścia:** aktywny enrollment ma niezmienny program, nawet gdy opublikowana zostanie kolejna wersja.

**Stan realizacji: Etap 4A zakończony lokalnie (2026-08-25).**

- Dodano addytywną migrację `2026_08_25_120000_create_osk_course_program_foundation` z tabelami `course_modules`, `course_lessons` i `course_lesson_steps`.
- `CourseVersion` otrzymał relację do uporządkowanych modułów i blokadę usunięcia po publikacji. Kurs z opublikowaną wersją również nie może zostać usunięty.
- `CourseProgramService` jest jedynym nowym zapisem domenowym: dodaje moduły, lekcje i kroki wyłącznie do draftu, sprawdza kompletność struktury, publikuje snapshot oraz tworzy kolejną wersję jako niezależny draft.
- Snapshot obsługuje typy kroków `content`, `image`, `video`, `question`, `scenario`, `summary` i cele `HOOK`, `TEACH`, `DEMO`, `PRACTICE`, `EXAM`, `CHECK`, `SUMMARY`. Nie zawiera formalnego czasu ani wyniku assessmentu.
- Testowy program kategorii B służy wyłącznie jako kontrakt w `CourseProgramTest`; nie ma seeda produkcyjnego, trasy, UI, aktywnego enrollmentu ani treści dla kursanta.
- Potwierdzenia: migracja PostgreSQL, Pint, 6 testów Etapu 4A / 36 asercji oraz 29 testów Etapów 1–4A / 173 asercje. Nie zmieniono `StudySession`, `QuestionCollection`, B2C access, checkoutu ani istniejącej nauki.

**Etap 4B — techniczna część zakończona lokalnie (2026-08-25).**

- Dodano osobne trasy `/osk/nauka`, `TheoryLearningAccessService`, `PublishedCourseProgramPayloadBuilder`, `LessonPlayer` i `StepRenderer`. Nie dotykają one istniejącego `/nauka`.
- Dostęp jest fail-closed: tylko właściciel aktywnego `StudentCourseAccess` odczyta kompletny snapshot `OSK_COURSE_PROGRAM_V1` z przypiętego enrollmentu. Cudzy, wygasły, oczekujący lub nieprawidłowy program jest ukryty jako `404`.
- Player obsługuje odczyt `content`, `image`, `video`, `question`, `scenario`, `summary`, ale nie zapisuje odpowiedzi, postępu, czasu, ukończenia, assessmentu ani evidence.
- Test `TheoryLearningPlayerTest`: 7 testów / 76 asercji, w tym izolacja kursantów, kontrakt Ziggy i potwierdzenie, że kolejny draft nie zmienia aktualnego snapshotu kursanta.
- Pozostaje merytoryczny pilot kategorii B: zatwierdzone treści, źródła i właściciel treści. Techniczna feature flaga oraz ograniczony rollout są gotowe w Etapie 5F; lokalny, ręcznie uruchamiany seed demonstracyjny jest dozwolony, ale nie dodawaj automatycznego seeda produkcyjnego ani linku w głównym menu bez tych decyzji.

### Etap 5 — Postęp, assessment i Time Evidence

**Cel:** nowe `Nauka teorii OSK` ma własny postęp i wiarygodne źródłowe dane czasu.

- Sesje lekcji, heartbeat, limity, wznowienie i ochrona przed nakładającymi się urządzeniami.
- `ModuleAssessment` z wersją zasad i snapshotem zestawu pytań.
- System evidence oddzielone od danych zaakceptowanych później przez OSK.
- Źródłowe sesje, próby assessmentu i evidence nie są nadpisywane; poprawka jest append-only i zawiera aktora, czas oraz powód.
- `TheoryCompletionGate` oparty o wymagania enrollmentu, assessmenty i łączny czas, nie o czas per moduł.
- Raporty pomocnicze mogą pokazać statystyki z `/nauka`, lecz nie są formalnym dowodem czasu.

**Brama wyjścia:** można odtworzyć przebieg teorii kursanta z surowych zdarzeń i wersji programu.

**Stan realizacji: Etap 5A zakończony lokalnie (2026-08-25).**

- Dodano addytywną migrację `2026_08_25_130000_create_osk_time_evidence_foundation` z `time_policies`, `learning_sessions` oraz append-only `learning_session_heartbeats`.
- `LearningSessionService` jest odizolowanym serwisem domenowym: jawnie startuje lub wznawia sesję, przyjmuje ograniczony heartbeat albo zamyka rekord. Korzysta z serwerowego czasu, transakcji, blokad oraz istniejącego fail-closed `TheoryLearningAccessService`.
- Sesja zamraża enrollment, wersję programu, hash programu, politykę czasu, snapshot polityki oraz hash; nie może zostać później usunięta ani edytowana poza przesuwaniem heartbeat-u i jednym zamknięciem.
- V1 polityki: heartbeat 60 s, grace 60 s, limit 3 h oraz strategia nakładających się urządzeń `UNION`. Brak lub konflikt aktywnych polityk blokuje nową sesję.
- Nie dodano route'u, endpointu, UI ani automatycznego timera playera. Nie liczymy jeszcze czasu formalnego, postępu, ukończenia, assessmentu ani evidence. `StudySession` i `/nauka` pozostały bez zmian.
- Potwierdzenia: lokalna migracja i `LearningSessionFoundationTest`: 6 testów / 41 asercji. Szczegóły są w [kontrakcie Etapu 5A](./OSK_V2_12_STAGE_5A_TIME_EVIDENCE_FOUNDATION.md).

### Etap 5B — odtwarzalna walidacja źródłowego czasu

**Cel:** policzyć czas z surowych sesji bez podwójnego naliczania urządzeń i bez cichego akceptowania uszkodzonych rekordów.

**Stan realizacji: zakończony lokalnie (2026-08-25).**

- Dodano odczytowy `TimeEvidenceValidator` oraz immutable obiekty przedziału, problemu i wyniku.
- Walidator respektuje `asOf`, granice heartbeat/grace/limitu, dokładne powody zamknięcia oraz zamrożony snapshot wersji programu i polityki.
- Nakładające się sesje są łączone metodą `UNION`; nie powstaje jeszcze formalny zapis evidence.
- Sesja z luką, cofniętym timestampem, błędną sekwencją lub niezgodnym snapshotem jest wyłączana z wyniku i zwraca jawny kod problemu.
- Nie dodano endpointu, timera, UI, postępu, assessmentu, `TheoryCompletionGate`, crona ani zmian w `/nauka`.
- Potwierdzenia: `LearningSessionFoundationTest` — 11 testów / 64 asercje; pełny pakiet OSK — 47 testów / 313 asercji; regresje istniejącej nauki, kolekcji, B2C access, checkoutu i audytu również przeszły lokalnie. Szczegóły są w [kontrakcie Etapu 5B](./OSK_V2_12_STAGE_5B_TIME_EVIDENCE_VALIDATOR.md).

### Etap 5C — jawny cykl sesji przez HTTP

**Cel:** udostępnić kontrolowaną warstwę `start/resume`, `heartbeat` i `close` bez podłączania jeszcze automatycznego timera do playera.

**Stan realizacji: zakończony lokalnie (2026-08-25).**

- Dodano `TheoryLearningSessionController` oraz trzy trasy scope'owane przez enrollment.
- Żądania mają walidowane klucze, CSRF, middleware `auth`/`verified`, limity żądań i korzystają wyłącznie z `LearningSessionService`.
- Odpowiedzi nie ujawniają klucza klienta, hashy ani pełnego snapshotu; heartbeat zwraca stan `RECORDED`, `IDEMPOTENT`, `THROTTLED` lub `SESSION_CLOSED`.
- Cudza sesja, brak dostępu i uszkodzony stan kończą się fail-closed; timeout zwraca `409 SESSION_CLOSED`.
- Nie podłączono `LessonPlayer.vue`, timera, workera, crona, postępu ani formalnego evidence.
- Potwierdzenia: endpointy są pokryte w `LearningSessionFoundationTest` — 13 testów / 84 asercje; aktualny pełny pakiet OSK przeszedł — 49 testów / 341 asercji; regresja `/nauka`, kolekcji, B2C access, checkoutu i audytu — 72 testy / 1265 asercji. Szczegóły są w [kontrakcie Etapu 5C](./OSK_V2_12_STAGE_5C_SESSION_ENDPOINTS.md).

### Etap 5D — integracja sesji z LessonPlayerem

**Cel:** podłączyć cykl sesji do nowej Nauki teorii OSK bez uznawania samego wejścia na lekcję za rozpoczęcie formalnego czasu.

**Stan realizacji: zakończony technicznie i ręcznie lokalnie (2026-08-26); przed pilotem wymaga decyzji rolloutowej.**

- Do payloadu Inertia dodano wyłącznie enrollment i trzy URL-e endpointów; Vue nie konstruuje routingu samodzielnie.
- `useOskLearningSession` obsługuje świadomy start/wznowienie, klucz przeglądarki, idempotency key, pojedynczy timer heartbeatów, widoczność karty, timeout, błędy sieci, wygasłe logowanie i jawne zamknięcie.
- Otwarta sesja może być wznowiona po odświeżeniu lub przejściu do kolejnej lekcji; unmount nie wysyła best-effort `close` i nie dopisuje lokalnego czasu.
- Player blokuje kroki przed startem, zatrzymuje się po `SESSION_CLOSED` lub nieprawidłowym payloadzie i nie pokazuje optymistycznego licznika formalnych minut.
- Podczas smoke testu wykryto i naprawiono kolizję nazwy lokalnego payloadu `navigation` ze współdzielonym menu Inertia; dane playera są teraz przekazywane jako `lesson_navigation`.
- Nie dodano postępu, ukończenia, assessmentu, evidence, workera, crona ani zmian w `StudySession`, `QuestionCollection` i `/nauka`.
- Potwierdzenia: player + fundament sesji — 20 testów / 172 asercje; pełny pakiet OSK — 49 testów / 345 asercji; regresja istniejących modułów — 72 testy / 1265 asercji; `vue-tsc`, `vite build`, Pint i `git diff --check` przechodzą. Ręczny smoke obejmował start, wznowienie, przejście między lekcjami, timeout, close oraz lokalne mocki `503` i `401`. Szczegóły są w [kontrakcie Etapu 5D](./OSK_V2_12_STAGE_5D_LESSON_PLAYER_SESSION_UX.md).

### Etap 5E — odtwarzalny postęp lekcji

**Cel:** zapisać ostatnio oglądany krok lekcji na przypiętej wersji programu bez utożsamiania aktywności kursanta z formalnym ukończeniem lub czasem szkolenia.

**Stan realizacji: zakończony lokalnie (2026-08-27).**

- Dodano addytywne `learning_lesson_progresses` jako mutowalną projekcję wznowienia oraz `learning_lesson_progress_events` jako append-only źródło zdarzeń `STEP_VIEWED`.
- Każdy rekord jest przypięty do `CourseEnrollment`, `CourseVersion`, numeru wersji i hash-a snapshotu oraz kodów modułu, lekcji i kroku. Krok jest walidowany wyłącznie względem zamrożonego snapshotu enrollmentu.
- Endpoint postępu wymaga aktywnej, własnej sesji OSK oraz jej `client_session_key`; działa idempotentnie i zwraca `409 SESSION_CLOSED`, gdy sesja nie jest już otwarta.
- Player zapisuje kursor wyłącznie po świadomym starcie/wznowieniu i po zmianie kroku. Odczyt lekcji wybiera zapisany krok bez tworzenia nowej sesji lub heartbeat-u.
- Etap nie liczy czasu, nie tworzy `TheoryCompletionGate`, evidence, assessmentu, odpowiedzi ani ukończenia. Nie używa `StudySession`, `QuestionCollection` ani `/nauka`.
- Potwierdzenia celu: `LessonProgressTest` + `TheoryLearningPlayerTest` — 11 testów / 149 asercji; pełny pakiet OSK — 53 testy / 406 asercji; regresja istniejących modułów — 72 testy / 1265 asercji. Lokalna migracja, Pint, `npm run build` i `git diff --check` przeszły.
- Szczegóły są w [kontrakcie Etapu 5E](./OSK_V2_12_STAGE_5E_LESSON_PROGRESS.md).

### Etap 5F — kontrolowany rollout pilota

**Cel:** przygotować odwracalny dostęp tylko dla wybranych enrollmentów bez otwierania OSK dla całej platformy.

**Stan realizacji: zakończony lokalnie (2026-08-27).**

- Dodano konfigurację `OSK_THEORY_LEARNING_PILOT_ENABLED`, która domyślnie blokuje cały player OSK.
- Dodano `theory_learning_pilot_accesses`: jedną ręczną decyzję dla jednego enrollmentu, z ostatnim aktorem i czasem włączenia lub wyłączenia.
- `TheoryLearningAccessService` wymaga obu bram także przy sesjach i postępie. Cudzy, nieaktywny, niedopuszczony lub uszkodzony enrollment pozostaje fail-closed.
- Panel `/admin/pilot-nauki-teorii-osk` pozwala administratorowi dopuścić lub wycofać pojedynczy enrollment; każda zmiana jest audytowana.
- Nie dodano realnego kursu, seeda treści, menu, formalnego evidence, ukończenia, assessmentu ani deployu.
- Potwierdzenia: pakiet celu 28 / 283, pełny OSK 55 / 447, regresja istniejących modułów 72 / 1265; migracja, Pint, build i kontrola diffu przeszły lokalnie.
- Szczegóły są w [kontrakcie Etapu 5F](./OSK_V2_12_STAGE_5F_PILOT_ROLLOUT.md).

### Etap 5G — plynne przejscie przez program

**Cel:** utrzymac rytm nauki na koncu lekcji bez cichego domykania sesji oraz bez utozsamiania przejscia przez kroki z formalnym ukonczeniem kursu.

**Stan realizacji: zakonczony lokalnie (2026-08-26).**

- `PublishedCourseProgramPayloadBuilder` wyznacza kolejna lekcje z przypietego snapshotu; kontroler przekazuje tylko gotowy URL i typ przejscia do playera.
- Glowny przycisk playera zmienia etykiete od `Dalej` przez `Przejdz do nastepnej lekcji` albo `Przejdz do nastepnego dzialu` do `Zakoncz nauke` na rzeczywistym koncu programu.
- Ostatni krok jest najpierw potwierdzany przez idempotentny endpoint postepu. Nieudany zapis lub zamknieta sesja zatrzymuja przejscie fail-closed.
- Przejscie miedzy lekcjami nie zamyka sesji; reczne `Zakoncz sesje` pozostaje jedyna akcja pokazujaca bramke zakonczenia. Koncowe `Zakoncz nauke` zamyka sesje i prowadzi do planu kursu, nie tworzy ukonczenia ani evidence.
- Nie dodano migracji, assessmentu, `TheoryCompletionGate`, zmian w `/nauka`, `StudySession` lub `QuestionCollection`.
- Potwierdzenia: pelny pakiet OSK 60 / 606, `vue-tsc` + `vite build`, Pint i kontrola diffu. Szczegoly sa w [kontrakcie Etapu 5G](./OSK_V2_12_STAGE_5G_CONTINUOUS_LESSON_FLOW.md).

### Etap 5H — roboczy szkielet i źródłowy zakres pierwszego modułu kategorii B

**Cel:** przygotować redakcyjny punkt startowy dla pierwszego realnego modułu bez publikowania treści lub otwierania dostępu kursantowi.

**Stan realizacji: zakończony lokalnie (2026-08-26).**

- Dodano stronę `/admin/programy-nauki-osk`, z której administrator może tworzyć i redagować robocze wersje programu kategorii B.
- Osobny, nieaktywny kurs `osk-teoria-b-pierwszenstwo-zrodlowy` ma jeden moduł `Pierwszeństwo i obserwacja drogi`, trzy krótkie lekcje i 12 kroków źródłowych. Każdy krok wskazuje ELI oraz konkretny artykuł/ustęp `Prawa o ruchu drogowym`.
- Panel administratora pozwala dodawać, zmieniać kolejność, edytować i usuwać robocze moduły, lekcje i kroki. Formularz kroku zapisuje kontrakty `content`, `image`, `video`, `question`, `scenario` i `summary`, zgodne z istniejącym playerem.
- Każdy administrator może zatwierdzić kompletną treść draftu; zatwierdzenie zapisuje konto i czas. Zmiana struktury lub treści automatycznie je cofa.
- Po zatwierdzeniu każdy administrator może osobną akcją opublikować wersję. Powstaje niezmienny snapshot, lecz kurs pozostaje nieaktywny i nie powstaje dostęp kursanta.
- Nie dodano aktywacji dostępu, enrollmentu, allow-listy, kredytu, maila ani wejścia dla kursanta. Istniejące `/nauka` pozostaje bez zmian.
- Potwierdzenia: lokalna migracja, `CourseProgramTest` 11 / 67, `OskTheoryCategoryBSourceDraftServiceTest` 1 / 62, `OskTheoryLearningProgramsPageTest` 9 / 54, Blade view cache oraz ręczna kontrola lokalnego draftu; brak deployu.
- Szczegóły są w [raporcie Etapu 5H](./OSK_V2_12_STAGE_5H_DRAFT_CONTENT_SKELETON.md).

### Etap 5I — gotowość enrollmentu do kontrolowanego pilota

**Cel:** nie dopuścić do allow-listy enrollmentu, który nie ma aktywnego dostępu albo kompletnego, zamrożonego programu.

**Stan realizacji: zakończony lokalnie (2026-08-27).**

- `TheoryLearningEnrollmentReadinessService` jest wspólną kontrolą dla playera i administracyjnej allow-listy. Wymaga aktywnego dostępu kursowego, zamrożonej wersji `PUBLISHED`/`ARCHIVED` oraz kompletnego snapshotu `OSK_COURSE_PROGRAM_V1`.
- Panel `/admin/pilot-nauki-teorii-osk` pokazuje gotowość i konkretny brak przy każdym enrollmentie. Niegotowy kandydat ma zablokowaną akcję `Włącz pilot`.
- Aktualizacja lokalna 2026-08-28: panel jest katalogiem `OSK + kurs` (24 grupy na ekranie), a szczegóły jednej grupy ładują najwyżej 50 kursantów naraz. Dzięki temu nie renderuje całej bazy zapisów przy 500 szkołach i setkach tysięcy kursantów. Dodano indeks `course_enrollments_access_directory_idx`; masowe akcje pozostają poza zakresem, dopóki nie dostaną osobnej kolejki i audytu.
- Serwis allow-listy waliduje gotowość ponownie w transakcji. Nie da się jej obejść przez bezpośrednie wywołanie akcji Livewire lub serwisu.
- Nie utworzono organizacji, enrollmentu, tokenu ani aktywnego dostępu. Nie zmieniono aktywności kursu ani globalnej flagi pilota.
- Potwierdzenia: `OskTheoryLearningPilotPageTest` i `TheoryLearningPlayerTest` — 17 testów / 305 asercji; Pint, Blade view cache i kontrola diffu przeszły.
- Szczegóły są w [kontrakcie Etapu 5I](./OSK_V2_12_STAGE_5I_PILOT_READINESS.md).

### Etap 5J — dwa bezpieczne kanały zapisu kursanta

**Cel:** pozwolić wlascicielowi OSK oraz administratorowi platformy niezaleznie dodac kursanta, bez mieszania tenant scope'u ani audytu.

**Stan realizacji: zakonczony lokalnie (2026-08-27).**

- Dodano panel operatora OSK pod `/osk/zarzadzanie/kursanci`. Jest dostepny tylko dla aktywnego membershipu z `STUDENT_MANAGE` i `ACCESS_MANAGE` i pozwala wybrac wylacznie wlasne OSK.
- Dodano osobna strone administratora platformy `/admin/zapisy-kursantow-osk`. Administrator wybiera OSK jawnie; nie powstaje sztuczny membership OSK.
- Obie sciezki korzystaja z `OrganizationEnrollmentService`, rezerwuja kredyt OSK, wydaja hashowany token i blokuja drugi `PENDING`/`ACTIVE` enrollment tego samego kursanta do tej samej wersji w tym samym OSK.
- Audit rozdziela `organization_operator` od `platform_administration`; akcja administratora platformy ma puste `organization_user_id` oraz rzeczywiste `actor_user_id`.
- Dodano `/osk/aktywuj/{token}`. Nowe konto ustawia haslo, istniejace konto wymaga zalogowania jako wlasciciel enrollmentu; aktywacja nie korzysta z B2C `/aktywuj-dostep`.
- Nie zmieniono aktywnosci kursu, globalnej flagi pilota, allow-listy ani istniejacego `/nauka`. Zrodlowy kurs B pozostaje nieaktywny, wiec prawdziwy zapis nie moze powstac przypadkiem.
- Potwierdzenia: testy zapisu `18 / 139`, regresja OSK i obecnej nauki `94 / 1631`, Pint, `view:cache`, lista tras oraz build Vite.
- Szczegoly sa w [kontrakcie Etapu 5J](./OSK_V2_12_STAGE_5J_ENROLLMENT_PROVISIONING.md).

### Etap 5K — świadoma aktywacja kursu pilota

**Cel:** oddzielić publikację treści od decyzji, że konkretny kurs może zostać przypisany jednemu kursantowi w kontrolowanym pilocie.

**Stan realizacji: zakończony lokalnie (2026-08-28).**

- Dodano `CourseAvailabilityService` oraz dwie audytowalne akcje administratora: aktywację i wstrzymanie kursu.
- Aktywacja wymaga zatwierdzonej, opublikowanej, zamrożonej wersji z kompletnym snapshotem programu; nie tworzy enrollmentu, tokenu, allow-listy ani nie zmienia flagi środowiskowej.
- `TheoryLearningEnrollmentReadinessService` wymaga teraz również aktywnego kursu. Wstrzymanie blokuje player i dalsze dopuszczanie enrollmentów bez kasowania historii.
- Panel `/admin/programy-nauki-osk` pokazuje stan prostym językiem i nie zachęca do uruchomienia kursu bez gotowej treści.
- Potwierdzenia: `CourseAvailabilityTest` (3), `OskTheoryLearningProgramsPageTest` (11 / 70), `OskTheoryLearningPilotPageTest` (7 / 41).
- Wykonano kontrolowany pilot tylko na danych testowych: jedno OSK `demo_only`, jeden kursant `is_test_account`, jeden aktywny enrollment i jedna allow-lista dla źródłowej wersji B `V1`.
- Ręczny smoke HTTP potwierdził listę kursów, bezpośredni kurs i player, start/heartbeat/zamknięcie sesji, zapis postępu pierwszego kroku, izolację operatora OSK bez enrollmentu oraz niezmienione `/nauka`.
- Lokalna flaga środowiskowa była już włączona przed testem i nie została przez etap zmieniona. Nie wykonano deployu ani testu produkcyjnego.
- Szczegóły i runbook są w [kontrakcie Etapu 5K](./OSK_V2_12_STAGE_5K_COURSE_PILOT_ACTIVATION.md).

### Etap 5L - katalog i cykl zycia kursow kategorii B

**Stan realizacji: zakonczony lokalnie (2026-08-28).**

- Zakres jest swiadomie ograniczony do kategorii B. Nie powstal formularz ani
  automatyzacja dla A, AM, B1, C, D i kolejnych; beda osobnym zakresem.
- `CourseCatalogService` tworzy audytowalny kurs `CATEGORY/B` z `V1 DRAFT`,
  nieaktywny i pusty. Administrator wpisuje tylko nazwe oraz kod.
- Dodano `courses.archived_at` i indeks katalogu. Archiwum blokuje nowy zapis
  oraz aktywacje kursu, ale nie kasuje historii ani dostepu juz aktywnego
  kursanta. Zatrzymanie wszystkich robi nadal osobna akcja `is_active=false`.
- Pusty, nieopublikowany draft bez modulow i enrollmentow mozna usunac. Kazdy
  kurs z trescia, publikacja albo historia zapisu trafia do archiwum.
- `/admin/programy-nauki-osk` wyszukuje po kodzie/nazwie, filtruje stany i
  stronicuje po 20 kursow. Pelne drzewo programu laduje tylko dla wybranego
  draftu, a nie dla calego katalogu.
- Potwierdzenia: panel `12 / 78`, nowy cykl zycia `5 / 35`, caly pakiet OSK
  `83 / 838`; lokalna migracja, lint PHP, `view:cache` i kontrola widoku.
- Szczegoly, granice i runbook sa w [kontrakcie Etapu 5L](./OSK_V2_12_STAGE_5L_COURSE_CATALOG.md).

### Etap 5M — bezpieczny onboarding B2B OSK

**Cel:** umożliwić zgłoszenie szkoły przez właściciela bez przypadkowego
otworzenia dostępu do kursantów, kredytów lub pilota.

**Stan realizacji: zakończony lokalnie (2026-08-29).**

- Dodano publiczny formularz `/dla-osk/rejestracja`, który tworzy firmowe
  zgłoszenie `PENDING`, nieaktywne OSK oraz aktywne membership właściciela.
  Nowy właściciel korzysta z istniejącego e-maila weryfikacyjnego Laravel.
- NIP jest normalizowany, sprawdzany algorytmem kontrolnym i unikalny; telefon
  firmowy jest walidowany i normalizowany do polskiego `+48...`.
- Status właściciela jest czytelny pod `/osk/rejestracja/status`; odrzucone
  zgłoszenie może zostać poprawione i wysłane ponownie.
- Administrator ma stronicowaną kolejkę `/admin/rejestracje-osk` z filtrem,
  wyszukiwaniem i prostymi decyzjami: zatwierdź albo zwróć do poprawy.
- `Organization::isReadyForOperations()` wymaga aktywnej, zatwierdzonej szkoły
  i jest egzekwowane także w `OrganizationEnrollmentService`.
- Akceptacja wymaga administratora, `PENDING` i potwierdzonego e-maila
  właściciela. Dopiero wtedy działa idempotentny pakiet pięciu kredytów.
- Nie dodano integracji CEIDG/KRS/VAT, SMS/OTP, płatności, masowych decyzji,
  automatycznego enrollmentu ani zmiany flagi pilota.
- Potwierdzenia: `OrganizationOnboardingTest` i
  `OskOrganizationOnboardingPageTest` — 10 testów / 88 asercji. Szczegóły,
  granice i smoke checklist są w [kontrakcie Etapu 5M](./OSK_V2_12_STAGE_5M_SAFE_B2B_ONBOARDING.md).

### Etap 5N — operacyjna obsługa zapisów kursantów

**Stan realizacji: zakończony lokalnie (2026-08-29).**

- Właściciel OSK widzi tylko zapisy swojej szkoły, administrator platformy
  zaczyna od wyboru jednej szkoły. Listy są stronicowane po 20 rekordów przez
  `simplePaginate`, bez globalnego ładowania kursantów.
- Dostępne są filtry kursu, stanu `PENDING` / `ACTIVE` / `CANCELLED` oraz
  wyszukiwanie imienia, nazwiska lub e-maila od trzech znaków.
- Dla `PENDING` działa ponowne wydanie linku: poprzedni token zostaje odwołany,
  nowy link jest pokazany tylko raz, a log audytu nie zawiera sekretu.
- Dla `PENDING` działa anulowanie: linki zostają odwołane, dostęp ma stan
  `CANCELLED`, a zarezerwowane miejsce wraca do kredytów OSK.
- Oba kanały, właściciela i administratora, mają osobne akcje audytu i
  weryfikację tenant isolation. Nie dodano automatycznej allow-listy.
- Potwierdzenia: `EnrollmentCreditsTest` — 18 testów;
  `OrganizationEnrollmentManagementTest` — 8 testów / 102 asercje; build
  Vite i `git diff --check` przeszły.
- Nie dodano automatycznej wysyłki e-maila, QR ani masowych akcji. Szczegóły,
  granice i ręczny smoke są w [kontrakcie Etapu 5N](./OSK_V2_12_STAGE_5N_ENROLLMENT_OPERATIONS.md).

### Etap 5O — konto gotowe przy biurku OSK

**Stan realizacji: zakończony lokalnie (2026-08-30). Brak deployu.**

**Cel:** obsłużyć kursanta siedzącego przy biurku OSK bez zmuszania go do
klikania linku aktywacyjnego, bez osłabiania izolacji organizacji ani kontroli
nad pilotem.

- Dodano aktywną od razu ścieżkę `DESK_ASSISTED` dla nowego konta. Tworzy
  login, hasło w formacie `Imie123456`, enrollment, 90-dniowy dostęp i
  rezerwację z konsumpcją dokładnie jednego miejsca OSK.
- Właściciel/delegowany operator OSK i administrator platformy mogą użyć
  ścieżki przy biurku; dotychczasowy `SELF_SERVICE_LINK` nadal jest dostępny
  dla samodzielnej aktywacji.
- Istniejący e-mail nie może wywołać resetu ani wydania hasła. Ponowne dane
  można wydać tylko aktywnemu kontu utworzonemu przy biurku, na wyraźną prośbę
  kursanta; zmiana unieważnia stare hasło bez drugiego kredytu.
- Operator świadomie wybiera wydruk, e-mail albo oba kanały. Dane są dostępne
  tylko w bieżącej odpowiedzi: po pokazaniu wpis historii jest zastępowany
  bezpiecznym adresem listy, więc odświeżenie nie pokazuje sekretu ani nie
  kieruje do endpointu POST.
- `MAIL_MAILER=log` jest blokowany dla hasła. Lokalny fallback to wydruk;
  bezpieczny mailer testowy pokrywa wysyłkę bez trwałej kolejki z sekretem.
- Nieweryfikowany kursant utworzony przy biurku może wejść wyłącznie do
  własnego, gotowego playera OSK. B2C, profile, płatności i panele OSK nadal
  wymagają zwykłej weryfikacji e-maila.
- Nie zmieniono globalnej flagi pilota, aktywności kursu ani per-enrollment
  allow-listy. Utworzenie konta nie otwiera pilota samoczynnie.

**Brama wyjścia:** testy `EnrollmentCreditsTest`,
`OrganizationEnrollmentManagementTest`, `AuthenticationTest` i `DashboardTest`
przeszły lokalnie: **50 testów / 343 asercje**. Przeszedł również build Vite i
ręczny smoke wydania, ponownego wydania oraz odświeżenia jednorazowego widoku.
Produkcja wymaga osobnej decyzji o mailerze, nadawcy, SPF/DKIM/DMARC,
monitoringu i kontrolowanym pilocie.

Szczegółowy kontrakt jest w [Etapie 5O](./OSK_V2_12_STAGE_5O_ACTIVATION_LINK_DELIVERY.md).

### Etap 5P — gotowość do kontrolowanego pilota produkcyjnego

**Stan realizacji: technicznie zakończony lokalnie (2026-08-30). Nie rozpoczynaj deployu ani nie zmieniaj flagi produkcyjnej bez osobnej decyzji.**

**Cel:** dodać fail-closed bramkę operacyjną przed pierwszą prawdziwą szkołą i
pierwszymi kursantami. Etap nie rozszerza katalogu, nie tworzy masowych
operacji i nie uruchamia jeszcze pilota.

- Dodano `DeskAssistedCredentialEmailReadinessService`: domyślnie blokuje
  e-mail z hasłem, wymaga jawnie zatwierdzonego mailera, prawidłowego nadawcy
  i publicznego HTTPS oraz nie zwraca sekretów.
- Konfiguracja używa `OSK_DESK_ASSISTED_CREDENTIAL_EMAIL_ENABLED=false` i
  `OSK_DESK_ASSISTED_CREDENTIAL_EMAIL_ALLOWED_MAILERS`. Preflight odrzuca
  `log`, `array`, `failover` oraz `roundrobin`, także wtedy, gdy ich nazwa
  trafi do allow-listy.
- Właściciel OSK i administrator widzą prosty stan „wydruk”, dopóki e-mail
  nie jest gotowy. Wymuszone żądanie e-maila po stronie serwera również kończy
  się bezpiecznym fallbackiem wydruku.
- Audit zawiera jedynie kanał, kod blokady i fakt zlecenia; UI używa tekstu
  „zlecono bezpieczną wysyłkę”, a nie twierdzenia o doręczeniu.
- Nadal trzeba ustalić jednego partnera OSK, odpowiedzialną osobę, małą grupę,
  okno wsparcia, produkcyjnego nadawcę, SPF/DKIM/DMARC, monitoring oraz
  rollback. To są blokery produkcyjne, nie zadania do wykonania przez kod.

**Brama wyjścia:** lokalne testy konfiguracji odrzucają `log`, `array`,
`failover` i `roundrobin`; wydruk, zwykły link aktywacyjny, logowanie, B2C i
tenant isolation pozostały zielone. Przed produkcją muszą być jeszcze zapisane
partner, nadawca, monitoring i rollback; pierwszy dostęp ma dać się włączyć i
wyłączyć bez wpływu na innych użytkowników.

Szczegółowy stan techniczny i checklisty operacyjne są w [Etapie 5P](./OSK_V2_12_STAGE_5P_PRODUCTION_PILOT_READINESS.md).

### Etap 6 — PAPER: dokumentacja teorii

**Cel:** przygotowac dokumentacje PAPER warstwami, bez udawania formalnego
dokumentu przed decyzja wlasciciela procesu prawnego.

- **Etap 6A — dane robocze (zakończony lokalnie):** administrator platformy
  moze jawnie wybrac `PAPER` przed formalnym startem enrollmentu, a system
  tworzy immutable, append-only rewizje `TrainingCardDataDraft`. Snapshot
  obejmuje dostepne dane organizacji, enrollmentu, wersji programu, staffu i
  odczytowe evidence czasu, z hashem oraz jawnymi blockerami. `source_state_hash`
  zapobiega tworzeniu rewizji przy identycznym odswiezeniu. Opcjonalna retencja
  obejmuje tylko starsze, zastapione dane robocze (domyslnie 90 dni,
  automatycznie na produkcji, z mozliwoscia natychmiastowego wstrzymania);
  najnowsza rewizja zawsze zostaje. Nie zmienia
  formalnego stanu ani nie uznaje ukonczenia.
- **Etap 6B — formalne dane i review (otwarty):** po pisemnej decyzji
  compliance dodac tylko zatwierdzony zakres danych urzedowych, role review i
  append-only decyzje pracownika OSK.
- Źródłowy wzór z załącznika nr 3 został zapisany w repozytorium; rozporządzenie dopuszcza format A5 albo A4. Przed generowaniem właściciel procesu prawnego zatwierdza operacyjny canonical template, format oraz sposób podpisu.
- Wersjonowane i immutable PDF, snapshot danych, hash, możliwość ponownego pobrania oraz append-only korekty.
- Bez własnego "urzędowego" wyglądu lub brandingu PrawkoNaRaz przed uzyskaniem canonical template.
- Retencja formalnej karty jest osobna od technicznej historii 6A: par. 18 ust. 2
  wskazuje 24 miesiace od ostatniego wpisu, a ust. 3 wymaga wpisu godzin do
  rejestru przed zniszczeniem karty. Nie automatyzowac tego przed 6B.

Szczegóły źródła i hasha są w [notatce PAPER](./OSK_V2_12_STAGE_6_PAPER_SOURCE_TEMPLATE.md), a zakres działającego lokalnie przygotowania danych w
[Etapie 6A](./OSK_V2_12_STAGE_6A_TRAINING_CARD_DATA_DRAFT.md).

**Brama 6A:** dane z systemu sa odtwarzalne, wersjonowane i jednoznacznie
oznaczone jako robocze, bez udawania dokumentu urzedowego.

**Brama pełnego Etapu 6:** jedno OSK może przygotować pełny dokument PAPER bez
przepisywania danych, dopiero po zatwierdzeniu wzoru, zakresu danych, retencji,
review i sposobu podpisu.

### Etap 7 — Formalny egzamin wewnętrzny z teorii

**Cel:** dodać osobny formalny proces egzaminu bez ruszania obecnej symulacji egzaminu.

- `InternalTheoryExamRulesVersion` i `InternalTheoryExamSession`.
- Rozróżnienie `FORMAL_INTERNAL` oraz `DIAGNOSTIC`.
- `IN_PLATFORM`, `EXTERNAL_OSK`, `NOT_MANAGED` niezależnie od wymagań prawnych enrollmentu.
- Snapshot pytań, reguł, wyniku, operatora i formalnego aktora.
- Formalny wynik należy do `CourseEnrollment`, a nie globalnie do `User`; `ModuleAssessment` nie może być jego substytutem.
- Internal Exam Engine nie zależy od ukończenia Learning Engine jako globalnej reguły; wymaganie wynika wyłącznie z `EnrollmentRequirements`.
- Najpierw adapter/reuse audytowanych fragmentów renderera i bazy pytań; nie duplikować kodu pytania bez potrzeby.
- Obecny `StudySession` pozostaje egzaminem próbnym dla kursanta.

**Brama wyjścia:** formalna próba jest odtwarzalna, a jej wynik nie może zostać pomylony z testem treningowym.

### Etap 8 — Browser Exam Station

**Cel:** uruchamianie formalnego egzaminu na stanowisku OSK.

- Logiczne `ExamStation`, wymienialna rejestracja przeglądarki i osobne, losowe, hashowane oraz odwoływalne credentiale stanowiska.
- Standardowy flow stanowiska nie wymaga od kursanta loginu, hasła, PIN-u, kodu ani QR. QR aktywacyjny kursanta jest odrębnym mechanizmem.
- HTTP i baza danych jako source of truth.
- Realtime wyłącznie dla lekkiego powiadomienia o przydzieleniu; fallback polling.
- Obsługa odświeżenia, przerwania, wymiany komputera i jednego aktywnego runtime.
- Wykorzystać doświadczenia istniejącego WebSocket/Redis, ale bez zależności od modułu rankingowego.

**Brama wyjścia:** awaria lub odświeżenie przeglądarki nie niszczy formalnej sesji.

### Etap 9 — Pozostałe elementy PAPER V1

**Cel:** domknąć zakres przewidziany przez V2.12 po działającym vertical slice teorii.

- Pierwsza pomoc.
- Minimalne wpisy zajęć praktycznych: data, czas, km, instruktor, pojazd.
- Praktyczny egzamin wewnętrzny PAPER po pozyskaniu canonical official sheet.
- Profil kursanta OSK i komplet dokumentów PAPER.
- Pilot z jednym partnerskim OSK oraz audyt prawny i bezpieczeństwa.

**Wyraźnie poza zakresem tego planu:** rezerwacje jazd, kalendarz, dostępność instruktorów, fleet management, CRM, SMS i pełny ERP OSK.

## 7. Zasady bezpiecznego wdrożenia

1. Każda migracja OSK jest addytywna i ma własne fabryki oraz testy.
2. Żadna pierwsza migracja OSK nie modyfikuje semantyki obecnych tabel nauki.
3. Każdy endpoint OSK jest scope'owany przez organizację i chroniony polityką.
4. Formalne dane mają snapshot wersji programu i reguł, a nie odwołanie do aktualnego UI.
5. System Evidence jest niezmienne; akceptacja przez OSK jest odrębną warstwą.
6. Najpierw PAPER, potem ELECTRONIC.
7. Każda reguła prawna ma identyfikator, wersję, test i źródło decyzji.
8. Przed każdym etapem wykonujemy regresję istniejących testów `StudySession` i testy izolacji OSK.
9. Wdrożenie produkcyjne zaczyna się od feature flagi i jednego pilota OSK.
10. `QuestionCollection` / „Kwalifikacja” nie są etapem przejściowym do formalnej teorii OSK.
11. Nie zakładamy migracji starego formalnego egzaminu: w repozytorium go nie ma.
12. [README OSK](./README.md) jest obowiązkowym punktem wejścia przed kolejnym etapem i zawiera aktualną checklistę przekazania pracy.
13. [Audyt zgodności blueprintu](./OSK_V2_12_BLUEPRINT_CONSISTENCY_AUDIT.md) jest obowiązkowy przed zmianą granic Learning Engine, formalnego egzaminu, PAPER albo Browser Exam Station.
14. `ORG_OWNER` jest super-adminem organizacji: żadna delegowana rola nie może uzyskać product permission niedostępnego dla właściciela.
15. W PAPER formalny staff może nie mieć konta aplikacji; w ELECTRONIC wymagane osobiste uwierzytelnienie wynika z konkretnej reguły, nie z roli UI.
16. Browser Exam Station ma dedykowane API i credential stanowiska bez uprawnień administracyjnych; realtime nie zastępuje persistence HTTP/DB.

## 8. Decyzje wymagające potwierdzenia przed etapami zaleznymi lub produkcja

- Pierwsza produkcyjna organizacja pilotażowa, właściciel pilota, liczba
  kursantów, monitoring oraz plan rollbacku. Lokalny pilot kategorii B nie
  zastępuje tej decyzji.
- Zakres PAPER V1, który ma zostać sprawdzony z partnerem OSK.
- Canonical wzory dokumentów i właściciel ich akceptacji prawnej.
- Polityka retencji danych, w szczególności wyników negatywnych egzaminu.
- Model sprzedaży kredytów dla OSK oraz terminy dostępu kursanta.
- Czy pierwszy formalny egzamin jest obsługiwany przez platformę, zewnętrzne OSK czy jako `NOT_MANAGED`.
- Czy zaakceptować propozycję Etapu 5L przed PAPER, aby kolejne kategorie
  kursów nie wymagały specjalnego kodu dla każdej z nich.

## 9. Aktualny stan realizacji

- [x] Blueprint V2.12 zapisany w repozytorium.
- [x] Pierwszy audyt istniejącego kodu wykonany.
- [x] Drugi, pogłębiony audyt zapisany w `OSK_V2_12_REPOSITORY_AUDIT.md`.
- [x] Ponowny audyt zgodności blueprintu zapisany w `OSK_V2_12_BLUEPRINT_CONSISTENCY_AUDIT.md`.
- [x] Rozdzielenie modułu testów i modułu nauki teorii OSK zapisane jako decyzja architektoniczna.
- [x] Lokalny regresyjny test `StudySessionFlowTest` zaliczony: 35 testów, 824 asercje.
- [x] Lokalna regresja kolekcji pytań, B2C access i audit logu zaliczona: 37 testów, 441 asercje.
- [x] Etap 1: dodano modele, addytywną migrację, factory, polityki, tenant scope i kontekst audytu OSK.
- [x] Izolacja tenantów, invariant `ORG_OWNER`, niezależność formalnego staffu i audit zostały potwierdzone: 4 testy / 29 asercji.
- [x] Po wdrożeniu Etapu 1 powtórzono regresję `/nauka`, kolekcji, B2C access i audytu: 72 testy / 1265 asercji.
- [x] Etap 2: dodano wersjonowany kontrakt kursu, enrollment, dostęp kursanta, token aktywacyjny i niezależny ledger kredytów OSK.
- [x] Etap 2: 5 kredytów startowych, rezerwacja przed aktywacją, aktywacja na 90 dni i przedłużenie o 30 dni są pokryte testami.
- [x] Po wdrożeniu Etapu 2 powtórzono regresję `/nauka`, kolekcji, B2C access, checkoutu i audytu.
- [x] Etap 3: dodano wersjonowane fakty enrollmentu, wersje reguł prawnych, snapshoty wymagań, korekty, trzy niezależne resolvery oraz flagi ręcznego przeglądu.
- [x] Etap 3 nie zgaduje: niejednoznaczność lub brak reguły blokuje wyliczenie i zapisuje flagę z konkretną wersją faktów.
- [x] Po wdrożeniu Etapu 3 potwierdzono lokalną migrację, 23 testy OSK / 137 asercji oraz regresję 98 istniejących testów / 1399 asercji.
- [x] Etap 4A: dodano wersjonowane moduły, lekcje i kroki programu, atomową publikację snapshotu, fork draftu oraz blokady mutacji i usunięcia dla opublikowanych wersji.
- [x] Etap 4A: potwierdzono lokalną migrację, 6 testów / 36 asercji dla programu oraz 29 testów OSK / 173 asercje łącznie.
- [x] Etap 4B: dodano odizolowany, odczytowy `LessonPlayer` / `StepRenderer` i fail-closed access do snapshotu enrollmentu.
- [x] Etap 5F: dodano domyślnie wyłączoną feature flagę, per-enrollment allow-listę, panel administratora i audit.
- [x] Etap 4B/5F: przygotowano lokalny, fikcyjny seed pilota kategorii B z jednym aktualnie dopuszczonym enrollmentem, dwoma modulami, trzema lekcjami, pietnastoma krokami demonstracyjnymi i kontrola pojedynczej aktywnej polityki czasu. Rozpoznany wczesniejszy lokalny program jest bezpiecznie forkowany do V2; stary enrollment zostaje historyczny, lecz traci tylko allow-liste pilota. Test seeda: 3 / 70, pelny pakiet OSK z panelem i seedem: 60 / 526. Nadal nie ma zatwierdzonej tresci ani seeda produkcyjnego.
- [x] Etap 5A: dodano odizolowany fundament sesji czasu i heartbeatów bez endpointu, UI, licznika formalnego czasu ani integracji z `/nauka`.
- [x] Etap 5B: dodano odczytowy `TimeEvidenceValidator`, przedziały `UNION`, fail-closed problemy i testy luk czasu/snapshotów.
- [x] Etap 5C: dodano HTTP `start/resume`, `heartbeat` i `close` z idempotencją, scope enrollmentu i limitami żądań.
- [x] Etap 5D: podłączono sesję do `LessonPlayer.vue` z świadomym startem/wznowieniem, heartbeatami, obsługą timeoutu/błędów, jawnym zamknięciem i ręcznym smoke testem lokalnym.
- [x] Etap 5E: dodano izolowany kursor wznowienia i append-only zdarzenia postępu na zamrożonym programie; lokalna migracja, OSK, regresje, Pint, build i kontrola diffu przeszły.
- [x] Etap 5G: dynamiczny przycisk przeprowadza przez kroki, lekcje i moduly jednej aktywnej sesji bez automatycznego close i bez tworzenia formalnego ukończenia.
- [x] Etap 5H: dodano nieaktywny edytor roboczego programu kategorii B z modułem „Pierwszeństwo i obserwacja drogi”, trzema lekcjami oraz bezpieczną obsługą kroków, zatwierdzania i publikacji wersji.
- [x] Etap 5H: przygotowano osobny, źródłowy draft kategorii B z 3 lekcjami i 12 krokami. Każdy administrator może zatwierdzić go, a następnie osobną akcją opublikować w panelu; późniejsza edycja wycofuje zatwierdzenie. Publikacja nie aktywuje kursu ani nie tworzy dostępu. Dane `TEST UI` pozostają lokalne i nie są kandydatem do publikacji.
- [x] Etap 5H: lokalnie zatwierdzono i opublikowano wersję `V1` źródłowego kursu kategorii B. Kurs był nieaktywny i miał zero enrollmentów do czasu świadomego pilota Etapu 5K.
- [x] Etap 5I: panel pilota i player korzystają z tej samej walidacji gotowości enrollmentu; niegotowy enrollment nie może trafić na allow-listę.
- [x] Etap 5J: wlasciciel/delegowany operator OSK i administrator platformy maja odrebne, audytowalne UI do tworzenia zapisu; aktywacja odbywa sie osobnym tokenem OSK.
- [x] Etap 5K: administrator wlacza albo wstrzymuje konkretny kurs w osobnej, audytowalnej akcji; aktywnosc kursu jest wymagana przez preflight playera i allow-listy.
- [x] Etap 5K: wykonano jeden kontrolowany pilot lokalny źródłowej wersji B z testowym OSK i jednym testowym kursantem; pozytywny dostęp, izolacja drugiej osoby i niezależność `/nauka` zostały potwierdzone.
- [x] Etap 5L: katalog, tworzenie i archiwizacja kursow kategorii B z lekkim, stronicowanym panelem; inne kategorie pozostaja poza zakresem.
- [x] Etap 5M: publiczny onboarding OSK z NIP-em, telefonem, e-mailem, ręczną akceptacją administratora, ekranem statusu oraz blokadą dostępu i kredytów przed akceptacją.
- [x] Etap 5N: odizolowana lista zapisów, filtry, ponowne wydanie linku i anulowanie oczekującego zapisu ze zwolnieniem miejsca dla właściciela oraz administratora; po logowaniu gotowy właściciel lub operator trafia do panelu zapisów OSK.
- [x] Pełny ręczny smoke B2B: utworzenie dwóch zapisów przez świeżo zatwierdzonego właściciela, nowy link i odwołanie starego, anulowanie osobnego `PENDING` ze zwrotem miejsca, aktywacja pierwszego kursanta, allow-lista pilota oraz pozytywny i negatywny test dostępu.
- [x] Etap 5O: konto dla nowej osoby może być gotowe przy biurku z loginem, hasłem `Imie123456`, wydrukiem lub bezpiecznym e-mailem; ponowne wydanie danych zmienia hasło bez drugiego miejsca i bez pozostawiania sekretu po odświeżeniu.
- [x] Etap 5P: lokalna bramka fail-closed e-maila z hasłem, domyślne wydanie na wydruku, jawny zatwierdzony mailer, blokada transportów testowych, nadawca HTTPS i audit bez sekretu.
- [x] Ponowiony mały pilot lokalny na oznaczonych danych `demo_only`: potwierdzono pięć bram dostępu, kursanta w playerze, `404` dla operatora bez enrollmentu i niezależne `/nauka`; regresja: 61 testów / 674 asercje. Nie zmieniono konfiguracji produkcyjnej.
- [x] Etap 6A: dodano jawny wybor `PAPER` przed formalnym startem, immutable dane robocze i rewizje z hashem, audit oraz stronicowany panel administratora. Identyczne odswiezenie nie tworzy kopii; retencja dotyczy tylko zastapionych rewizji, dziala domyslnie na produkcji i nie usuwa najnowszej. Testy fundamentu: 6 / 50. Brak formalnych danych, review, podpisu, PDF i deployu.
- [ ] Przed pierwszym prawdziwym OSK: wskazać partnera i osoby odpowiedzialne, produkcyjny mailer/nadawcę, SPF/DKIM/DMARC, monitoring, kontrolowaną skrzynkę testową, małą grupę oraz rollback.
- [ ] Nie dodano automatycznego sprawdzania CEIDG/KRS/VAT, SMS/OTP, płatności B2B, masowych decyzji, formalnego evidence, ukończenia, assessmentu, egzaminu ani formalnego dokumentu PAPER; produkcyjny mailer, partner OSK i świadoma decyzja o pilocie produkcyjnym nadal są wymagane.

## 10. Najbliższy bezpieczny krok

Pełny ręczny local smoke B2B jest zakończony: na odrębnych danych potwierdzono
onboarding szkoły, pięć miejsc startowych, dwa zapisy, ponowne wydanie i
odwołanie starego linku, anulowanie osobnego `PENDING` ze zwrotem miejsca,
aktywację pierwszego kursanta, allow-listę pilota, player oraz blokadę drugiej
osoby. Szczegóły są w [Etapie 5N](./OSK_V2_12_STAGE_5N_ENROLLMENT_OPERATIONS.md).

Etapy 5O–5P domknęły lokalnie obsługę przy biurku i bezpieczną bramkę e-maila:
wydruk działa zawsze, a e-mail z hasłem wymaga jawnej flagi, allow-listy
nietestowego mailera, poprawnego nadawcy i publicznego HTTPS. Kod odrzuca
`log`, `array`, `failover` i `roundrobin`, więc lokalny transport nie może
przez pomyłkę potraktować hasła jako prawdziwie wysłanej poczty.

Najbliższa praca nie jest już kodowa: **przygotowanie operacyjne
kontrolowanego pilota produkcyjnego**. Właściciel produktu musi wskazać partnera
OSK, osobę po stronie szkoły i platformy, małą grupę testową, produkcyjny
mailer i nadawcę, SPF/DKIM/DMARC, monitoring, kontrolowaną skrzynkę oraz
rollback. Nie wykonuj deployu ani nie włączaj produkcyjnej flagi
`OSK_THEORY_LEARNING_PILOT_ENABLED` bez tej decyzji.

Najblizsza praca kodowa nie powinna rozszerzac teraz katalogu o kolejne
kategorie. Etap 5L jest domkniety lokalnie dla B. Etap 6A bezpiecznie
przygotowal dane z istniejacych zrodel i ma kontrolowana techniczna historie;
Etap 6B zaczynamy dopiero po otrzymaniu decyzji o canonical PAPER template,
zakresie danych, retencji formalnej i wskazaniu wlasciciela akceptacji prawnej.
Szczegoly lokalnego testu i rollback sa w [Etapie 5K](./OSK_V2_12_STAGE_5K_COURSE_PILOT_ACTIVATION.md),
granice katalogu B w [Etapie 5L](./OSK_V2_12_STAGE_5L_COURSE_CATALOG.md),
a onboarding B2B w [Etapie 5M](./OSK_V2_12_STAGE_5M_SAFE_B2B_ONBOARDING.md),
a operacje zapisów w [Etapie 5N](./OSK_V2_12_STAGE_5N_ENROLLMENT_OPERATIONS.md),
a konto przy biurku w [Etapie 5O](./OSK_V2_12_STAGE_5O_ACTIVATION_LINK_DELIVERY.md),
a przygotowanie pilota produkcyjnego w [Etapie 5P](./OSK_V2_12_STAGE_5P_PRODUCTION_PILOT_READINESS.md).
