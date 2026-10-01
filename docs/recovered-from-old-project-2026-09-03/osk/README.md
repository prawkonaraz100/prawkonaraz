# PrawkoNaRaz OSK V2.12 — start i przekazanie pracy

> **Biezaca weryfikacja repozytorium — 2026-09-15:** ten katalog jest
> odzyskanym snapshotem dokumentacji starego projektu. Statusy implementacyjne
> ponizej opisuja historycznie zweryfikowany local worktree z 2026-08-26 do 2026-08-30,
> a nie potwierdzony stan aktualnego
> `prawkonaraz100/prawkonaraz@main`. Biezacy `main` nie zawiera runtime'u
> `/osk/nauka`, `LessonPlayer.vue`, `TheoryLearningController` ani
> `PublishedCourseProgramPayloadBuilder` opisanych w etapach 4B/5D/5E/5G.
> Nie usuwamy tej historii ani nie zmieniamy decyzji architektonicznych, ale
> przed dalsza implementacja trzeba wskazac rzeczywisty working tree uruchamiany
> lokalnie. Szczegolowa rozbieznosc dla przeplywu ostatniego kroku jest zapisana
> w [Etapie 5G](./OSK_V2_12_STAGE_5G_CONTINUOUS_LESSON_FLOW.md).


**Historyczny status local worktree (2026-08-30):** Etapy 1–3, Etap 4A, techniczna warstwa Etapu 4B, Etapy 5A–5P oraz przygotowanie danych Etapu 6A — organizacje, enrollment, kredyty, wersjonowane wymagania, program, odizolowany player, źródłowe sesje czasu, walidacja przedziałów, warstwa HTTP, integracja sesji z playerem, izolowany kursor postępu, kontrolowany rollout, płynne przejście między lekcjami, profesjonalny edytor, źródłowy kurs kategorii B, publikacja V1, kontrola gotowości enrollmentu, dwa kanały zapisu kursanta, brama aktywacji pilota, katalog B, onboarding B2B, operacyjna lista zapisów, konto gotowe przy biurku OSK, fail-closed preflight e-maila z hasłem oraz immutable dane robocze PAPER — wdrożone i zweryfikowane lokalnie. Jeden kontrolowany pilot lokalny źródłowej wersji B przeszedł pełny smoke HTTP oraz ręczny smoke B2B.
**Historyczna ostatnia weryfikacja:** 2026-08-30. Etap 5P: końcowa regresja OSK, paneli administratora, logowania i dashboardu `147` testów / `1278` asercji. Po niej ponowiony mały pilot DEMO potwierdził pięć bram dostępu, logowanie kursanta, player, blokadę operatora bez enrollmentu oraz niezależność `/nauka`; regresja pilota: `61` testów / `674` asercje. Przeszły również build Vite, cache konfiguracji i widoków Blade oraz kontrola diffu. Docker i wszystkie migracje OSK są gotowe lokalnie.
**Historyczny stan smoke B2B:** pełny przebieg lokalny jest zakończony na oddzielnych danych: onboarding szkoły, pięć miejsc startowych, utworzenie dwóch zapisów, ponowne wydanie linku i odwołanie starego, anulowanie drugiego zapisu ze zwrotem miejsca, aktywacja pierwszego kursanta, allow-lista pilota oraz pozytywny i negatywny test dostępu.
**Historyczny najbliższy bezpieczny krok (2026-08-30):** decyzje operacyjne przed prawdziwym pilotem: partner OSK, osoba odpowiedzialna, mała grupa kursantów, produkcyjny mailer i nadawca, SPF/DKIM/DMARC, monitoring oraz rollback. Nie wykonywać deployu ani nie zmieniać produkcyjnej flagi środowiskowej bez osobnej decyzji; lokalny test nie jest zgodą na uruchomienie produkcji.
**Historyczny stan katalogu i onboardingu:** Etap 5L jest zakończony lokalnie dla kategorii B, Etap 5M dodaje ręcznie akceptowany onboarding OSK, Etap 5N operacyjną listę zapisów, a Etapy 5O–5P konto gotowe przy biurku z bezpiecznym wydrukiem domyślnie. E-mail z hasłem pojawia się dopiero po jawnej konfiguracji środowiska. Nie rozszerzaj katalogu o inne kategorie ani nie przedstawiaj walidacji NIP/telefonu jako automatycznej weryfikacji firmy bez osobnej decyzji o zakresie i integracjach.
**Historyczny stan PAPER:** zapisano źródłowy wyciąg urzędowego wzoru karty z załącznika nr 3, a Etap 6A dodaje wersjonowane dane robocze z jawnymi blockerami. Identyczne odświeżenie nie tworzy kopii, a wyłącznie zastąpiona historia robocza jest automatycznie porządkowana po 90 dniach na produkcji. Nie ma zatwierdzonego operacyjnego szablonu, formalnych danych, review, generatora PDF, procesu podpisów ani formalnego wystawiania dokumentów.
**Historyczna weryfikacja Etapu 6A (2026-08-31):** `6` testów / `50` asercji, lokalna migracja, dry run retencji oraz filtr harmonogramu przeszły. Harmonogram nie wykonuje się lokalnie, a najnowsza rewizja nigdy nie jest usuwana.

**Bieżący stan potwierdzony dla repozytorium (2026-09-15):** aktualny `main@4b10738705f3696bc2bcce730a707473eab8cd2b` nie zawiera opisanego niżej runtime'u OSK. Kod historycznego local worktree nie został jeszcze wskazany ani odzyskany do bieżącego `main`. Bieżący status odzyskiwania i następny krok są nadrzędnie prowadzone w [głównym planie odzyskania i wdrożeń](../../RECOVERY-AND-DEPLOYMENT-MASTER-PLAN.md), Etap 6.

Ten plik jest punktem startowym dla człowieka lub kolejnego agenta. Najpierw przeczytaj go w całości, potem otwieraj dokumenty w kolejności poniżej. Nie zaczynaj od losowego pliku z katalogu `docs/` ani od implementacji w `StudySession`.

## 1. Najważniejsza decyzja

W projekcie istnieją trzy różne obszary:

1. **Obecne `/nauka`** — ćwiczenia z pytaniami, powtórki i próbny egzamin państwowy. Ten moduł zostaje bez zmian semantycznych.
2. **Nowa Nauka teorii OSK** — wersjonowany program szkolenia z lekcjami, sesjami czasu i postępem. Player oraz techniczna integracja sesji istnieją lokalnie, ale nie ma jeszcze formalnego evidence ani uznawania ukończenia.
3. **Formalny egzamin wewnętrzny OSK** — osobny proces formalny. Tego modułu jeszcze nie ma.

`StudySession`, `StudySessionAnswer`, `QuestionCollection` i `QuestionModule` należą do pierwszego obszaru. W szczególności zawodowa „Kwalifikacja wstępna przyspieszona — kat. C” jest kolekcją pytań do ćwiczeń, a nie formalnym kursem teorii OSK.

Zasada blueprintu „jeden Learning Engine” dotyczy nowego, formalnego silnika kursowego (`CourseVersion` → lekcje → kroki → assessmenty), który ma być wielokrotnego użycia dla kursów, a nie osobnej kopii dla OSK i ewentualnego przyszłego B2C. Obecne `/nauka` jest silnikiem ćwiczeń z pytaniami, nie formalnym Learning Engine; nie przenosimy do niego formalnego czasu, enrollmentu ani ukończenia OSK.

## 2. Kolejność czytania

1. [Plan wdrożenia OSK V2.12](./OSK_V2_12_IMPLEMENTATION_PLAN.md) — aktualny plan etapów, granice oraz checklisty.
2. [Audyt repozytorium OSK V2.12](./OSK_V2_12_REPOSITORY_AUDIT.md) — stan faktyczny kodu, wykryte ryzyka i wyniki testów.
3. [Audyt zgodności blueprintu](./OSK_V2_12_BLUEPRINT_CONSISTENCY_AUDIT.md) — obowiązujące doprecyzowania między blueprintem a aktualnym repozytorium.
4. [Kontrakt reguł prawnych Etapu 3](./OSK_V2_12_STAGE_3_LEGAL_REQUIREMENTS.md) — modele, fail-closed i warunek uruchomienia produkcyjnego.
5. [Źródło wzoru karty PAPER Etapu 6](./OSK_V2_12_STAGE_6_PAPER_SOURCE_TEMPLATE.md) — urzędowy wyciąg, hash i granice wdrożenia dokumentu.
6. [Dane robocze PAPER Etapu 6A](./OSK_V2_12_STAGE_6A_TRAINING_CARD_DATA_DRAFT.md) — wersjonowany snapshot, blokery i granica przed formalnym dokumentem.
7. [Kontrakt programu teorii Etapu 4A](./OSK_V2_12_STAGE_4_COURSE_PROGRAM.md) — hierarchy, snapshot, immutability i granice następnego kroku.
8. [Kontrakt LessonPlayera Etapu 4B](./OSK_V2_12_STAGE_4B_LESSON_PLAYER.md) — routing, fail-closed access i granice odczytowego playera.
9. [Kontrakt fundamentu czasu Etapu 5A](./OSK_V2_12_STAGE_5A_TIME_EVIDENCE_FOUNDATION.md) — source records, polityka, heartbeat i twarde granice.
10. [Kontrakt walidacji czasu Etapu 5B](./OSK_V2_12_STAGE_5B_TIME_EVIDENCE_VALIDATOR.md) — odtwarzanie przedziałów i `UNION`.
11. [Kontrakt cyklu sesji Etapu 5C](./OSK_V2_12_STAGE_5C_SESSION_ENDPOINTS.md) — HTTP start/wznowienie, heartbeat i close.
12. [Kontrakt sesji w LessonPlayerze Etapu 5D](./OSK_V2_12_STAGE_5D_LESSON_PLAYER_SESSION_UX.md) — świadomy start, wznowienie, heartbeat i close w UI.
13. [Kontrakt postępu lekcji Etapu 5E](./OSK_V2_12_STAGE_5E_LESSON_PROGRESS.md) — odtwarzany kursor, append-only zdarzenia i granice wobec formalnego evidence.
14. [Kontrakt rolloutu pilota Etapu 5F](./OSK_V2_12_STAGE_5F_PILOT_ROLLOUT.md) — globalna brama, allow-lista enrollmentów, audyt i runbook uruchomienia.
15. [Kontrakt płynnego przejścia lekcji Etapu 5G](./OSK_V2_12_STAGE_5G_CONTINUOUS_LESSON_FLOW.md) — dynamiczny przycisk główny, przejście bez auto-close oraz granice wobec formalnego ukończenia.
16. Raport szkieletu treści Etapu 5H **(plik nie został odzyskany; wymaga weryfikacji)** — edytor, źródłowy draft kategorii B, zatwierdzanie treści, publikacja wersji i twarde granice dostępu kursanta.
17. Gotowość enrollmentu do pilota Etapu 5I **(plik nie został odzyskany; wymaga weryfikacji)** — wspólny preflight playera i panelu.
18. Dwa bezpieczne kanały zapisu Etapu 5J **(plik nie został odzyskany; wymaga weryfikacji)** — osobny panel operatora OSK i administratora platformy, token aktywacyjny oraz audit.
19. [Aktywacja kursu do lokalnego pilota Etapu 5K](./OSK_V2_12_STAGE_5K_COURSE_PILOT_ACTIVATION.md) — osobna brama kursu, runbook kontrolowanego smoke i rollback.
20. [Katalog kursow kategorii B Etapu 5L](./OSK_V2_12_STAGE_5L_COURSE_CATALOG.md) — tworzenie, archiwum, skalowanie listy i granice innych kategorii.
21. Bezpieczny onboarding B2B Etapu 5M **(plik nie został odzyskany; wymaga weryfikacji)** — rejestracja szkoły, e-mail, NIP, telefon, kolejka administratora i granice weryfikacji.
22. [Operacyjna obsługa zapisów Etapu 5N](./OSK_V2_12_STAGE_5N_ENROLLMENT_OPERATIONS.md) — lista, ponowne wydanie linku, anulowanie oczekującego zapisu i granice skali.
23. [Konto gotowe przy biurku Etapu 5O](./OSK_V2_12_STAGE_5O_ACTIVATION_LINK_DELIVERY.md) — aktywne konto, wydruk lub bezpieczny e-mail bez zapisywania sekretu.
24. [Gotowość do kontrolowanego pilota produkcyjnego Etapu 5P](./OSK_V2_12_STAGE_5P_PRODUCTION_PILOT_READINESS.md) — lokalna bramka mailera i wydruku; partner, monitoring i deploy pozostają osobnymi decyzjami.
25. [Karta decyzji treści pilota kategorii B](./OSK_V2_12_PILOT_CONTENT_DECISION.md) — aktualny zakres, źródła, akceptacja administratora i bramy przed jednym prawdziwym enrollmentem.
26. [PrawkoNaRaz OSK V2.12 CLEAN MASTER](./prawkonaraz_osk_v2_12_CLEAN_MASTER_existing_exam_module_audit.md) — docelowy blueprint produktu i compliance.
27. [Architektura sesji](../SESSION-MODULE-ARCHITECTURE.md) — tylko po to, aby nie naruszyć istniejącego `/nauka`.
28. [Plan separacji kolekcji pytań](../QUESTION-COLLECTION-COURSE-SEPARATION-PLAN.md) — tylko po to, aby nie mieszać OSK z istniejącą „Kwalifikacją”.
29. [Strategia testów](../TEST-STRATEGY.md) i [runbook deployu](../DEPLOYMENT-RUNBOOK.md) — przed pierwszym kodem i przed każdym wdrożeniem.

Plik blueprintu jest przechowywany jako niezmieniony dokument źródłowy. Jego
SHA-256 na dzień audytu: `BAC86136C7168D80BFF10D5B1F396FCDC4FC7DCC4895CCDC92129D70CB3D37C6`.
Nie poprawiaj go w ramach implementacji; nowe interpretacje i decyzje zapisuj
w planie lub raporcie audytowym.

> **Uwaga o brakach odzyskania:** brak plików dokumentacyjnych Etapów 5H, 5I,
> 5J i 5M w bieżącym snapshotcie nie jest dowodem, że historyczne prace nie
> istniały. Oznacza wyłącznie, że wskazane pliki nie zostały odzyskane do
> dostępnego drzewa repozytorium; ich treść i historyczne statusy wymagają
> weryfikacji w rzeczywistym starym/local working tree.

## 3. Hierarchia źródeł i rozstrzyganie konfliktów

| Pytanie | Źródło prawdy |
| --- | --- |
| Co ma powstać w OSK i jakie ma mieć znaczenie biznesowe | Blueprint V2.12 |
| Co już istnieje i czego nie wolno zepsuć | Audyt repozytorium oraz aktualny kod i migracje |
| W jakiej kolejności implementować OSK | Plan wdrożenia OSK V2.12 |
| Jak uruchomić testy i wdrożyć zmianę | `docs/TEST-STRATEGY.md` oraz `docs/DEPLOYMENT-RUNBOOK.md` |
| Gdzie odnotować nowe ustalenie | Plan wdrożenia i sekcja „Historia zmian” poniżej |

Jeżeli blueprint i obecny kod mówią o tym samym pojęciu inaczej, nie zmieniaj istniejącego modułu w ciemno. Najpierw zapisz rozbieżność w planie, dodaj test regresyjny i zaprojektuj adapter albo nowy model OSK.

### Canonical nazewnictwo OSK

W nowym kodzie OSK obowiązują nazwy z blueprintu:

```text
Organization
OrganizationUser
OrganizationStaffMember
CourseEnrollment
StudentCourseAccess
CourseVersion
LessonStep
ModuleAssessment
InternalTheoryExamSession
```

W obecnym kodzie Laravelowym blueprintowy `LessonStep` ma nazwę `CourseLessonStep`, aby jednoznacznie wskazywać jego przynależność do kursu i nie mieszać go z przyszłymi krokami innych modułów.

Tabela membershipu dla OSK ma używać nazwy `organization_users`, nie `organization_members`. Starszy, ogólny przykład `organization_members` w `docs/DATABASE-SCHEMA.md` i dokumentach B2B jest historycznym szkicem V2, nie kontraktem implementacyjnym dla OSK V2.12.

Nowe klucze muszą na początku pozostać zgodne z aktualnym projektem Laravel: istniejące `users.id` używa `$table->id()`, a relacje korzystają z `foreignId()`. Nie wprowadzaj UUID tylko dla OSK bez osobnej, zapisanej decyzji architektonicznej.

## 4. Twarde zakazy

- Nie dodawaj `organization_id`, formalnego czasu ani statusu ukończenia OSK do `study_sessions`.
- Nie uznawaj wyniku obecnego trybu `exam` za formalny egzamin OSK.
- Nie zmieniaj `QuestionCollection` w `CourseVersion`.
- Nie używaj `ProductAccessGrant` jako ledgeru kredytów OSK.
- Nie zmieniaj globalnych ról `admin`, `moderator`, `student`; role OSK są rolami membershipu organizacji.
- Nie rozpoczynaj Browser Exam Station ani generatora PAPER przed fundamentem OSK.
- Nie twórz pojedynczego `exemption = true/false` ani wyjątków prawnych w Vue lub kontrolerach; wynikają one z wersjonowanych faktów i resolverów.
- Nie traktuj `ModuleAssessment` jako formalnego egzaminu wewnętrznego.
- Nie nadpisuj źródłowych sesji, assessmentów, evidence ani zatwierdzonych dokumentów; korekty są append-only i wersjonowane.
- Nie używaj w standardowym Browser Exam Station loginu, hasła, PIN-u, kodu lub QR kursanta.
- Nie buduj w tej inicjatywie rezerwacji jazd, kalendarza, dostępności instruktorów, floty, CRM ani SMS.

## 5. Historyczny stan local worktree — snapshot 2026-08-30

Poniższa tabela zachowuje stan potwierdzony w dawnym local worktree. Nie opisuje aktualnego `main`; bieżący stan implementacji OSK w dostępnym repozytorium jest opisany wyżej oraz w Etapie 6 głównego planu odzyskania.

| Obszar | Stan |
| --- | --- |
| Dokument źródłowy V2.12 | Zapisany w tym katalogu bez modyfikacji merytorycznej |
| Audyt kodu i dokumentacji | Historyczny audyt bazowy oraz ponowny przegląd po Etapie 5P; bieżący stan jest w tym pliku i planie |
| Audyt zgodności blueprintu | Zgodny; po Etapie 5P nie wykryto konfliktu z granicami `/nauka`, wersjonowania, dostępu ani pilota |
| Testy obecnej nauki | `StudySessionFlowTest`: 35 testów / 824 asercje |
| Testy kolekcji, dostępu i audytu | 37 testów / 441 asercji |
| Fundament organizacyjny OSK | Istnieje lokalnie: modele, migracja, factory, polityki, tenant scope i kontekst audytu |
| Enrollment i kredyty OSK | Istnieją lokalnie: `Course`, zamrożony `CourseVersion`, `CourseEnrollment`, dostęp kursanta, token aktywacyjny i append-only ledger |
| Reguły formalne OSK | Istnieją lokalnie: immutable wersje reguł i faktów, snapshoty wymagań, korekty oraz fail-closed flagi ręcznego przeglądu |
| Program nauki teorii OSK | Istnieje lokalnie: wersjonowane moduły, lekcje i kroki, atomowa publikacja snapshotu, blokada edycji/usunięcia oraz fork kolejnego draftu; źródłowa wersja V1 kategorii B jest opublikowana, zatwierdzona i sprawdzona w jednym lokalnym pilocie |
| Katalog kursów / kolejne kategorie | Katalog B istnieje lokalnie: tworzenie audytowalnego draftu, wyszukiwanie, filtry, stronicowanie, archiwum i bezpieczne usunięcie pustego szkicu. Inne kategorie pozostają poza zakresem. |
| Trasy i UI OSK | Istnieje lokalnie odizolowany LessonPlayer, panel operatora OSK `/osk/zarzadzanie/kursanci`, panel administratora `/admin/zapisy-kursantow-osk`, aktywacja tokenem oraz audytowalna brama kursu w `/admin/programy-nauki-osk`; brak deployu |
| Źródłowe sesje czasu OSK | Etap 5A istnieje lokalnie: polityka czasu, immutable sesje, append-only heartbeaty i serwis domenowy |
| Walidacja czasu OSK | Etap 5B istnieje lokalnie: odczytowe przedziały, `UNION` i fail-closed problemy; brak UI i evidence |
| HTTP cykl sesji OSK | Etap 5C istnieje lokalnie: start/wznowienie, heartbeat i close |
| Integracja sesji z playerem | Etap 5D istnieje lokalnie: świadomy start/wznowienie, heartbeat, timeout, błędy i jawne close |
| Postęp lekcji OSK | Etap 5E istnieje lokalnie: kursor na zamrożonym programie i append-only zdarzenia `STEP_VIEWED`; brak formalnego ukończenia i evidence |
| Rollout pilota OSK | Etapy 5F, 5I, 5J, 5K, 5O i 5P istnieją lokalnie: brama środowiskowa, per-enrollment allow-lista, preflight enrollmentu, dwa kanały zapisu, aktywacja tokenem, konto gotowe przy biurku, osobna brama kursu i fail-closed preflight e-maila; jeden kontrolowany pilot lokalny przeszedł smoke, brak deployu i decyzji o pilocie produkcyjnym |
| Formalny egzamin wewnętrzny OSK | Jeszcze nie istnieje |
| Robocze dane PAPER | Etap 6A istnieje lokalnie: jawny wybor PAPER, immutable snapshoty, deduplikacja identycznego stanu i opcjonalna retencja tylko zastapionej historii; najnowsza rewizja zostaje, a to nadal nie jest dokument urzedowy |
| Generator PDF i canonical PAPER template | Zapisano źródłowy wzór z załącznika nr 3; nadal brak zatwierdzonego szablonu operacyjnego, formalnych danych, review i generatora |
| Rezerwacje jazd / kalendarz | Świadomie poza zakresem |

## 6. Historyczny handoff dla kolejnego agenta — 2026-08-30

Poniższe komendy, etapy i wyniki testów są zachowanym handoffem starego local worktree. Nie należy wykonywać ich jako dowodu stanu bieżącego `main`. Dla aktualnego repozytorium pierwszym krokiem pozostaje zidentyfikowanie rzeczywistego working tree / commita uruchamiającego lokalny runtime OSK, zgodnie z Etapem 6 głównego planu odzyskania.

### Przed pierwszą edycją kodu

```powershell
git status --short --branch
docker compose ps
docker compose exec -T app php artisan test tests/Feature/StudySessionFlowTest.php --compact
docker compose exec -T app php artisan test tests/Feature/QuestionCollectionLearningTest.php tests/Feature/QuestionCollectionAccessTest.php tests/Feature/ProductAccessGateTest.php tests/Feature/AuditLoggingTest.php --compact
```

Jeżeli testy lub Docker nie działają, najpierw napraw środowisko albo wyjaśnij problem. Nie zaczynaj migracji OSK przy niezweryfikowanym baseline.

### Etap 1 — zakończony lokalnie

1. Dodano wyłącznie addytywne modele i migrację `organizations`, `organization_users`, `organization_staff_members`, `organization_staff_qualifications` oraz `organization_formal_roles`; `linked_user_id` staffu jest nullable.
2. Tenant scope i polityki OSK nie korzystają z `User::role`. `ORG_OWNER` ma zawsze pełen, wirtualny zestaw product permissions i nie może zostać ograniczony poniżej `ORG_ADMIN` lub `ORG_MEMBER`.
3. Rozdzielono zalogowanego operatora od formalnego staffu. Kwalifikacje i role formalne pozostają oddzielnymi danymi, a PAPER nadal nie wymaga konta aplikacyjnego od instruktora.
4. Dodano factory i testy negatywne: tenant A nie widzi danych tenant B, globalny administrator nie omija scope'u, a delegowany użytkownik nie może przekroczyć ownera.
5. Krytyczne akcje administracyjne OSK zapisują kontekst organizacji, membershipu i użytego uprawnienia w istniejącym audit logu.
6. Testy: 4 testy OSK / 29 asercji, PostgreSQL `migrate --pretend` i lokalne `migrate`, Pint oraz regresja 72 istniejących testów / 1265 asercji.
7. Nadal nie ma routingu, UI, enrollmentu, kredytów, lekcji, formalnego egzaminu ani dokumentów PAPER. Zmiana nie została wdrożona na produkcję.

### Etap 2 — zakończony lokalnie

1. Dodano addytywną migrację `2026_08_25_100000_create_osk_enrollment_credit_foundation` oraz modele `Course`, `CourseVersion`, `CourseEnrollment`, `StudentCourseAccess`, `StudentCourseActivationToken` i `OrganizationCreditTransaction`.
2. Enrollment jest zawsze przypisany do organizacji, kursanta i zamrożonej wersji kursu. `CourseVersion` ma snapshot, hash i blokadę edycji po publikacji; pełne moduły, lekcje i kroki pozostają zakresem Etapu 4.
3. Nowa organizacja otrzymuje 5 bezterminowych kredytów startowych. Przy dodaniu kursanta ledger rezerwuje jeden kredyt, aktywacja zapisuje tylko zdarzenie konsumpcji, a anulowanie oczekującego dostępu zwalnia rezerwację.
4. Token aktywacyjny jest jednorazowy, odwoływalny, przechowywany wyłącznie jako hash i wygasa po 14 dniach. Dostęp zaczyna się w momencie aktywacji na 90 dni; przedłużenie aktywnego dostępu dodaje 30 dni.
5. Nowo utworzony kursant ustawia hasło przy aktywacji. Istniejący użytkownik może aktywować token tylko po uwierzytelnieniu jako właściciel enrollmentu.
6. Nie powstał route, ekran, mail ani QR. Nie zmieniono `ProductAccessGrant`, `PurchaseOrder`, checkoutu, `StudySession` ani `QuestionCollection`.
7. Testy Etapu 2: 10 testów / 59 asercji; `migrate --pretend`, lokalne `migrate`, Pint oraz regresja `/nauka`, kolekcji, B2C access, checkoutu i audytu przeszły lokalnie.

### Etap 3 — zakończony lokalnie

1. Dodano wersjonowane `LegalRequirementsVersion`, append-only `CourseEnrollmentFacts`, immutable snapshoty `CourseEnrollmentRequirement`, korekty oraz `LegalReviewFlag`.
2. Dodano osobne resolvery wymagań enrollmentu, równoważności uprawnienia i gotowości do egzaminu państwowego. Niejednoznaczność blokuje wynik zamiast go zgadywać.
3. Wynik można odtworzyć z konkretnej wersji faktów, wersji reguł i snapshotu; nie zmieniono B2C checkoutu, `ProductAccessGrant` ani `/nauka`.
4. Testy: Etap 3 — 9 / 49; Etapy 1–3 — 23 / 137; regresja istniejącej aplikacji — 98 / 1399. Lokalna migracja PostgreSQL przeszła.
5. Nie ma UI ani produkcyjnego seeda reguł. Przed formalnym użyciem wymagana jest akceptacja compliance opisana w [kontrakcie Etapu 3](./OSK_V2_12_STAGE_3_LEGAL_REQUIREMENTS.md).

### Etap 4A — zakończony lokalnie

1. Dodano addytywną migrację `2026_08_25_120000_create_osk_course_program_foundation` oraz modele `CourseModule`, `CourseLesson` i `CourseLessonStep` pod istniejącym `CourseVersion`.
2. `CourseProgramService` tworzy program tylko dla draftu, waliduje pełną hierarchię, atomowo publikuje canonical snapshot i wylicza jego hash. Opublikowanej lub zarchiwizowanej wersji nie można zmieniać ani usunąć; kolejna korekta zaczyna się od skopiowanego draftu.
3. Obsługiwane są kontraktowe typy kroków `content`, `image`, `video`, `question`, `scenario`, `summary` i cele renderowania `HOOK`, `TEACH`, `DEMO`, `PRACTICE`, `EXAM`, `CHECK`, `SUMMARY`.
4. Pilot kategorii B istnieje wyłącznie jako fixture testowy. Nie dodano kursu, treści, route'u, UI ani dostępu produkcyjnego dla kursanta.
5. Nie dodano formalnego czasu, postępu, evidence, assessmentu, egzaminu, dokumentów PAPER ani integracji z `StudySession`, `QuestionCollection`, `ProductAccessGrant`, `PurchaseOrder` lub checkoutem B2C.
6. Testy: Etap 4A — 6 testów / 36 asercji; Etapy 1–4A — 29 testów / 173 asercje; lokalna migracja PostgreSQL i Pint przeszły. Szczegóły są w [kontrakcie Etapu 4A](./OSK_V2_12_STAGE_4_COURSE_PROGRAM.md).

### Etap 4B — techniczny player zakończony lokalnie

1. Dodano odizolowane trasy `/osk/nauka` oraz `LessonPlayer` / `StepRenderer` dla sześciu kontraktowych typów kroku.
2. Player odczytuje wyłącznie zamrożony snapshot przypięty do własnego `CourseEnrollment`; cudzy, oczekujący, wygasły albo niekompletny program zwraca `404`.
3. Nie zapisuje postępu, czasu, ukończenia, odpowiedzi, assessmentu ani evidence. Nie integruje się z `/nauka`.
4. Testy: 7 testów / 76 asercji. Pełny kontrakt znajduje się w [Etapie 4B](./OSK_V2_12_STAGE_4B_LESSON_PLAYER.md).
5. Nadal nie istnieje zatwierdzony seed treści, entry point w głównym menu ani deploy. Techniczna feature flaga i per-enrollment allow-lista są opisane w [Etapie 5F](./OSK_V2_12_STAGE_5F_PILOT_ROLLOUT.md), ale pozostają domyślnie wyłączone.

### Etap 5A — fundament źródłowych sesji czasu zakończony lokalnie

1. Dodano `TimePolicy`, `LearningSession` i append-only `LearningSessionHeartbeat`; rekord sesji zamraża enrollment, wersję i hash programu oraz snapshot polityki czasu.
2. `LearningSessionService` używa serwerowego czasu, transakcji i blokad. Ten sam klucz przeglądarki wznawia otwartą sesję, inny klucz tworzy osobny zapis źródłowy do późniejszego liczenia metodą `UNION`.
3. V1 polityki: heartbeat 60 s, grace 60 s, maksymalnie 3 h. Timeout i limit zamykają sesję dokładnie na granicy polityki, a nie dopisują czasu po czasie.
4. Nie ma jeszcze endpointu, UI ani automatycznego timera. Player pozostaje odczytowy, a obecne `/nauka` pozostaje nietknięte.
5. Testy: 6 testów / 41 asercji. Pełny kontrakt znajduje się w [Etapie 5A](./OSK_V2_12_STAGE_5A_TIME_EVIDENCE_FOUNDATION.md).

### Etap 5B — walidacja źródłowego czasu zakończona lokalnie

1. `TimeEvidenceValidator` odtwarza przedziały z sesji i heartbeatów na podstawie serwerowych timestampów oraz punktu `asOf`.
2. Nakładające się sesje z kilku urządzeń są łączone metodą `UNION`, a uszkodzony rekord nie jest cicho wliczany do czasu.
3. Wynik nie zapisuje evidence, postępu ani ukończenia. Nie powstał endpoint ani automatyczny timer.
4. Testy kontraktu 5A/5B przechodzą lokalnie: 11 testów / 64 asercje; obejmują granice timeoutu, historyczne `asOf`, świadome zamknięcie, dwa urządzenia, lukę heartbeat-u i niezgodny hash snapshotu. Pełny pakiet OSK przeszedł: 47 testów / 313 asercji; regresje istniejącej nauki, kolekcji, B2C access, checkoutu i audytu także są zielone.
5. Szczegóły znajdują się w [Etapie 5B](./OSK_V2_12_STAGE_5B_TIME_EVIDENCE_VALIDATOR.md).

### Etap 5C — cykl sesji przez HTTP zakończony lokalnie

1. Dodano trzy endpointy `start/resume`, `heartbeat` i `close` pod `/osk/nauka/{enrollment}/sesje`.
2. Kontroler waliduje klucze, wymaga `auth`/`verified`, stosuje limity żądań i nie zapisuje danych poza `LearningSessionService`.
3. Scope enrollmentu, idempotencja heartbeat-u, serwerowy czas oraz odpowiedź `SESSION_CLOSED` po timeout są pokryte testami.
4. `LearningSessionFoundationTest` przechodzi lokalnie: 13 testów / 84 asercje, aktualny pełny pakiet OSK: 49 testów / 341 asercji, a regresja `/nauka`, kolekcji, B2C access, checkoutu i audytu: 72 testy / 1265 asercji. Integracja z playerem jest opisana osobno w Etapie 5D.
5. Szczegóły znajdują się w [Etapie 5C](./OSK_V2_12_STAGE_5C_SESSION_ENDPOINTS.md).

### Etap 5D — integracja sesji z LessonPlayerem zakończona technicznie i ręcznie lokalnie

1. `LessonPlayer.vue` nie uruchamia sesji przy samym wejściu. Kursant musi kliknąć `Rozpocznij naukę` albo `Wznów naukę`.
2. `useOskLearningSession` korzysta z endpointów 5C, przechowuje klucz przeglądarki per enrollment i używa idempotency key przy heartbeatach.
3. Timer jest pojedynczym, odtwarzanym po odpowiedzi `setTimeout`; respektuje politykę serwera, zatrzymuje się przy timeoutcie, zamknięciu, błędzie autoryzacji lub nieprawidłowym payloadzie.
4. Odświeżenie i przejście do następnej lekcji nie zamyka sesji automatycznie. Jawne `Zakończ sesję` kończy ją przez backend.
5. Player ukrywa kroki przed aktywacją i po zamknięciu, ale nie zmienia renderera kroków ani logiki istniejącego `/nauka`.
6. Podczas smoke testu naprawiono kolizję lokalnego payloadu `navigation` ze współdzielonym menu Inertia; dane nawigacji playera są przekazywane jako `lesson_navigation`.
7. Testy playera + endpointów/fundamentu: 20 testów / 172 asercje; pełny pakiet OSK: 49 testów / 345 asercji; regresja istniejących modułów: 72 testy / 1265 asercji. `npm run build`, Pint i `git diff --check` przechodzą.
8. Ręczny smoke potwierdził świadomy start, wznowienie po odświeżeniu, przejście między lekcjami bez auto-close, timeout, jawne close, retry po `503` i zatrzymanie po `401`. Po końcowym odświeżeniu konsola była czysta.
9. Szczegóły znajdują się w [Etapie 5D](./OSK_V2_12_STAGE_5D_LESSON_PLAYER_SESSION_UX.md).

### Etap 5E — kursor postępu lekcji zakończony technicznie lokalnie

1. Dodano addytywne tabele `learning_lesson_progresses` i `learning_lesson_progress_events`. Pierwsza przechowuje wyłącznie ostatnio widziany krok danej lekcji; druga jest append-only źródłem zdarzeń `STEP_VIEWED`.
2. Kursor i zdarzenie są trwale przypięte do enrollmentu, zamrożonej wersji kursu, numeru wersji, hash-a programu oraz kodów modułu, lekcji i kroku.
3. Endpoint postępu działa tylko dla właściciela aktywnego enrollmentu i otwartej sesji OSK. Weryfikuje krok względem przypiętego snapshotu, jest idempotentny i fail-closed zwraca `SESSION_CLOSED` po timeoutcie.
4. Player zapisuje bieżący krok dopiero po świadomym uruchomieniu lub wznowieniu sesji oraz po przejściu w przód albo wstecz. Po odświeżeniu lekcja otwiera się na zapisanym kroku.
5. Etap nie dopisuje heartbeat-u, nie liczy minut, nie uznaje ukończenia lekcji ani kursu, nie tworzy assessmentu ani formalnego evidence. Nie dotyka `StudySession`, `QuestionCollection` ani `/nauka`.
6. Testy celu: `LessonProgressTest` i `TheoryLearningPlayerTest` — 11 testów / 149 asercji. Pełny pakiet OSK: 53 testy / 406 asercji. Regresja istniejących modułów: 72 testy / 1265 asercji. Lokalna migracja, Pint, `npm run build` i `git diff --check` przeszły.
7. Szczegóły znajdują się w [Etapie 5E](./OSK_V2_12_STAGE_5E_LESSON_PROGRESS.md).

### Etap 5F — kontrolowany rollout pilota zakończony lokalnie

1. Dodano twardą, domyślnie wyłączoną bramę `OSK_THEORY_LEARNING_PILOT_ENABLED` oraz osobną allow-listę pojedynczych enrollmentów.
2. Dostęp do playera, sesji i kursora wymaga równocześnie obu bram, własności enrollmentu, aktywnego `StudentCourseAccess` i poprawnego zamrożonego snapshotu.
3. Administrator zarządza allow-listą przez `/admin/pilot-nauki-teorii-osk`. Każde rzeczywiste włączenie lub wyłączenie zapisuje audit z aktorem i kontekstem enrollmentu.
4. Pilot można przygotować w panelu przy globalnie wyłączonej bramie; dostęp nie otworzy się przed świadomą zmianą konfiguracji środowiska.
5. Dodano wyłącznie lokalny, fikcyjny seed demonstracyjny. Nie dodano realnego kursu, seeda produkcyjnego, wejścia w głównym menu, formalnego evidence, ukończenia, assessmentu ani deployu.
6. Szczegóły znajdują się w [Etapie 5F](./OSK_V2_12_STAGE_5F_PILOT_ROLLOUT.md).
7. Weryfikacja: pakiet celu 28 / 283, pełny OSK 55 / 447, regresja istniejących modułów 72 / 1265; migracja, Pint, build i kontrola diffu przeszły lokalnie.

### Etap 5G — płynne przejście między lekcjami zakończone lokalnie

1. Główny przycisk playera prowadzi kursanta kolejno przez kroki, następne lekcje i następne działy bez obowiązkowego ekranu `Sesja zakończona`.
2. Kolejny cel jest wyznaczany tylko z przypiętego, zamrożonego programu enrollmentu. Po potwierdzonym zapisie ostatniego kroku ta sama otwarta sesja przechodzi do następnej lekcji.
3. `Zakończ sesję` nadal jest ręcznym przerwaniem nauki. Ostatni krok całego programu pokazuje `Zakończ naukę`, zamyka sesję i prowadzi do planu kursu, ale nie uznaje formalnego ukończenia.
4. Testy `TheoryLearningPlayerTest` i `LessonProgressTest` pokrywają kolejność następnej lekcji oraz kontynuację jednej sesji między modułami. Pełny pakiet OSK: 60 / 606; Pint i build przeszły.
5. Szczegóły znajdują się w [Etapie 5G](./OSK_V2_12_STAGE_5G_CONTINUOUS_LESSON_FLOW.md).

### Etap 5H — roboczy szkielet pierwszego modułu kategorii B zakończony lokalnie

1. Dodano administratorowi stronę `/admin/programy-nauki-osk`, która tworzy idempotentny, nieaktywny draft kategorii B.
2. Draft zawiera moduł `Pierwszeństwo i obserwacja drogi` oraz trzy krótkie lekcje bez kroków i bez merytorycznej treści dla kursanta.
3. Administrator może redagować robocze tytuły i opisy modułu oraz lekcji, a serwis domenowy odrzuca każdą próbę zmiany opublikowanej wersji.
4. Nie ma publikacji, enrollmentu, kredytu, tokenu, maila, dostępu do pilota ani deployu. Lokalny draft został sprawdzony: jest nieaktywny, `DRAFT`, ma zero enrollmentów i zero kroków.
5. Testy strony administratora oraz blokady programu: 10 testów / 59 asercji. Ręczny test z 2026-08-27 objął redakcję modułów, lekcji i sześciu formatów kroków; wykryty brak zapisu opcjonalnego kontekstu pytania został naprawiony i objęty regresją. Szczegóły znajdują się w Etapie 5H **(plik nie został odzyskany; wymaga weryfikacji)**.

### Etap 5J — dwa bezpieczne kanały zapisu zakonczony lokalnie

1. Wlasciciel lub delegowany operator OSK z `STUDENT_MANAGE` i `ACCESS_MANAGE` dodaje kursanta tylko do wlasnej organizacji przez `/osk/zarzadzanie/kursanci`.
2. Administrator platformy ma niezalezny panel `/admin/zapisy-kursantow-osk`; wskazuje OSK jawnie i nie dostaje sztucznego membershipu tej organizacji.
3. Obie sciezki wykorzystuja ten sam `OrganizationEnrollmentService`: aktywny opublikowany kurs, kredyt OSK, `PENDING` access, jednorazowy token oraz blokada duplikatu dla tego samego OSK, kursanta i wersji kursu.
4. Token OSK ma osobny ekran `/osk/aktywuj/{token}`. Nowe konto ustawia haslo, istniejace konto musi potwierdzic sie logowaniem na wlasciwy e-mail; B2C `/aktywuj-dostep` pozostaje nietkniete.
5. Audit rozroznia dzialanie operatora OSK od administratora platformy. Wariant platformowy nie ma `organization_user_id`, ale zachowuje faktyczne `actor_user_id`.
6. Lokalnie przeszly: 18 testow zapisu / 139 asercji, regresja OSK i obecnej nauki 94 / 1631, Pint, `view:cache`, lista tras oraz build Vite. Szczegoly sa w Etapie 5J **(plik nie został odzyskany; wymaga weryfikacji)**.
7. W chwili zakończenia Etapu 5J źródłowy kurs B pozostawał nieaktywny. Późniejsza aktywacja oraz kontrolowany lokalny enrollment są opisane w Etapie 5K; nie wykonano deployu.

### Etap 5K — aktywacja kursu i kontrolowany pilot zakończone lokalnie

1. Administrator ma w `/admin/programy-nauki-osk` osobne akcje **Włącz kurs do lokalnego pilota** i **Wstrzymaj kurs**.
2. `CourseAvailabilityService` wymaga administratora oraz zatwierdzonej, opublikowanej i kompletnej wersji programu. Każda zmiana trafia do audytu.
3. Aktywność kursu jest teraz kolejną bramą `TheoryLearningEnrollmentReadinessService`: wstrzymany kurs blokuje nowy zapis, allow-listę i player, lecz nie usuwa danych kursanta.
4. Nie powstał automatyczny enrollment, automatyczne dopuszczenie do allow-listy ani zmiana flagi środowiskowej.
5. Wykonano jeden lokalny pilot na nowych danych testowych: źródłowy kurs B `V1`, jedno OSK `demo_only`, jeden kursant `is_test_account`, jeden aktywny enrollment i jedna allow-lista.
6. Smoke HTTP potwierdził listę kursów, bezpośredni dostęp do kursu i playera, start oraz zamknięcie sesji, zapis kroku, izolację operatora OSK bez enrollmentu i niezmienione `/nauka`.
7. Szczegóły, wyniki i rollback znajdują się w [Etapie 5K](./OSK_V2_12_STAGE_5K_COURSE_PILOT_ACTIVATION.md). Brak deployu i brak decyzji o pilocie produkcyjnym.

### Etap 5L — katalog kursów kategorii B zakończony lokalnie

1. Administrator tworzy w `/admin/programy-nauki-osk` kurs kategorii B przez nazwę i kod. Powstaje wyłącznie nieaktywny `DRAFT`, bez treści i bez dostępu kursanta.
2. Katalog ma wyszukiwanie, filtry, strony po 20 kursów i ładuje pełne drzewo tylko aktualnie otwartego szkicu.
3. Archiwum blokuje nowy zapis i aktywację kursu, ale nie kasuje historii oraz nie odbiera dostępu osobie już uczącej się. Akcja **Wstrzymaj kurs** nadal odpowiada za przerwę dla wszystkich.
4. Usunąć można tylko pusty, nieopublikowany szkic bez treści oraz zapisów. Każdy kurs z historią jest archiwizowany.
5. Zmiana jest ograniczona do B. Kategorie A, AM, B1, C, D i kolejne wymagają osobnej decyzji produktowej. Szczegóły są w [Etapie 5L](./OSK_V2_12_STAGE_5L_COURSE_CATALOG.md).

### Etap 5M — bezpieczny onboarding B2B OSK zakończony lokalnie

1. Właściciel składa zgłoszenie szkoły pod `/dla-osk/rejestracja`; nowe konto dostaje istniejący e-mail weryfikacyjny, a szkoła zaczyna jako nieaktywne `PENDING` z zerem kredytów.
2. NIP jest normalizowany, sprawdzany sumą kontrolną i unikalny; telefon jest wyłącznie poprawnie sformatowanym polskim kontaktem. Nie ma automatycznej integracji z CEIDG/KRS/VAT ani SMS/OTP.
3. Administrator sprawdza kolejkę `/admin/rejestracje-osk`, zatwierdza szkołę albo zwraca ją do poprawy prostym komunikatem. Przed akceptacją właściciel musi potwierdzić e-mail.
4. Tylko `is_active + APPROVED` pozwala na zarządzanie kursantami. Ta reguła działa także w serwisie zapisu, więc nie da się jej ominąć bezpośrednim wywołaniem endpointu.
5. Zatwierdzenie uruchamia istniejący, idempotentny pakiet pięciu kredytów; nie tworzy automatycznie zapisu kursanta, aktywacji kursu, allow-listy ani dostępu do playera.
6. Kolejka jest stronicowana po 25 wyników. Masowe decyzje nie są jeszcze implementowane, bo wymagają oddzielnej kolejki, limitów i audytu per OSK. Szczegóły i smoke checklist są w Etapie 5M **(plik nie został odzyskany; wymaga weryfikacji)**.

### Etap 5N — operacyjna obsługa zapisów kursantów zakończona lokalnie

1. Właściciel OSK widzi wyłącznie własne zapisy, a administrator najpierw wybiera szkołę. Lista ładuje 20 wyników na stronę i ma filtry kursu, stanu oraz wyszukiwanie od trzech znaków.
2. Dla zapisu `PENDING` można bezpiecznie wydać nowy link: stary zostaje odwołany, nowy jest pokazany tylko raz, a akcja trafia do audytu bez sekretu.
3. Dla zapisu `PENDING` można go anulować: linki są odwoływane, dostęp otrzymuje stan `CANCELLED`, a zarezerwowane miejsce wraca do puli OSK.
4. Pełny ręczny smoke B2B potwierdził te operacje, aktywację kursanta, przejście do kolejnej lekcji bez wymuszonego zakończenia sesji oraz blokadę osoby spoza pilota.
5. Po logowaniu właściciel lub operator gotowej szkoły z prawem zarządzania kursantami trafia do panelu zapisów OSK; kursant OSK zachowuje wejście do Centrum kursanta.
6. Nie ma automatycznej wysyłki e-maila, QR ani masowych akcji. Te decyzje wymagają odrębnego etapu. Szczegóły, testy i wynik smoke są w [Etapie 5N](./OSK_V2_12_STAGE_5N_ENROLLMENT_OPERATIONS.md).

### Etap 5O — konto gotowe przy biurku OSK zakończony lokalnie

1. Właściciel/delegowany operator OSK oraz administrator platformy mogą utworzyć konto dla nowej osoby przy biurku. Zwykła ścieżka aktywacji linkiem pozostaje bez zmian.
2. System tworzy login oraz hasło `Imie123456` bez myślnika, aktywuje dostęp od razu na 90 dni i konsumuje dokładnie jedno miejsce OSK.
3. Wydruk jest dostępny zawsze. E-mail lub oba kanały pojawiają się dopiero po jawnym preflighcie Etapu 5P: fladze środowiska, zatwierdzonym nietestowym mailerze, produkcyjnym nadawcy i HTTPS.
4. Dla aktywnego konta utworzonego przy biurku można wydać nowe dane na prośbę kursanta. Stare hasło natychmiast przestaje działać, a operacja nie pobiera dodatkowego miejsca.
5. Odpowiedź z hasłem jest jednorazowa: po pokazaniu URL wraca do listy kursantów, wpis historii nie zawiera danych, a odświeżenie niczego nie odsłania.
6. Pełny kontrakt, granice i wynik weryfikacji są w [Etapie 5O](./OSK_V2_12_STAGE_5O_ACTIVATION_LINK_DELIVERY.md).

### Etap 5P — gotowość do kontrolowanego pilota produkcyjnego zakończona technicznie lokalnie

1. Kod domyślnie ukrywa e-mail z hasłem i umożliwia wydruk. Serwer odmawia e-maila również wtedy, gdy ktoś ręcznie wyśle żądanie `EMAIL`.
2. Preflight wymaga flagi środowiska, jawnej allow-listy mailera, bezpiecznego transportu, prawidłowego nadawcy i publicznego HTTPS. Odrzuca `log`, `array`, `failover` oraz `roundrobin`.
3. Audit zapisuje tylko kanał, kod blokady i zlecenie wysyłki; nie zawiera hasła. Operator widzi „zlecono bezpieczną wysyłkę”, a nie nieprawdziwe potwierdzenie doręczenia.
4. Nadal nie uruchamiamy produkcji. Przed nią trzeba wskazać partnera, osoby odpowiedzialne, małą grupę, nadawcę, SPF/DKIM/DMARC, monitoring i próbę na kontrolowanej skrzynce.
5. Szczegóły, checklisty i granice są w [Etapie 5P](./OSK_V2_12_STAGE_5P_PRODUCTION_PILOT_READINESS.md). Brak deployu.

### Bramy dalszych etapów

| Po Etapie | Warunek przejścia |
| --- | --- |
| 1: organizacje | pełna izolacja tenantów potwierdzona testami |
| 2: enrollment i kredyty | B2C access działa bez zmian, kredyty OSK są osobnym ledgerem |
| 3: reguły prawne | każdy wynik wymagań ma wersję, źródło i test |
| 4–5: teoria | program, czas i evidence są niezależne od `/nauka` |
| 5O: konto przy biurku | dane są jednorazowe, nie utrwalają sekretu i mają bezpieczny fallback wydruku |
| 5P: pilot produkcyjny | partner, transport e-mail, nadawca, monitoring i rollback przechodzą fail-closed preflight |
| 6A: robocze dane PAPER | snapshot i rewizje sa odtwarzalne, a UI nie przedstawia ich jako dokumentu urzedowego |
| 6B+: formalny PAPER | istnieje zatwierdzony canonical template, decyzja o danych/review/podpisie oraz dopiero potem decyzja o PDF |
| 7: egzamin teorii | wykonany komponentowy audyt rendereru / timerów / wyniku symulatora |
| 8: Browser Station | formalny egzamin ma własne, stabilne API i persistence |

## 7. Minimalny standard zmian OSK

- Każda migracja jest addytywna, ma factory i testy.
- Każdy endpoint OSK ma jawny scope organizacji oraz test dostępu między tenantami.
- Każda decyzja dotycząca prawa, retencji albo dokumentów ma wersję i źródło.
- Każda zmiana dotykająca współdzielonych pytań uruchamia regresję `/nauka`.
- Produkcyjny rollout zaczyna się od feature flagi i jednego OSK pilotażowego.
- Nie wdrażaj zmian OSK razem z niezwiązanym redesignem lub zmianami danych pytań.

## 8. Otwarte decyzje produktowe

Przed wdrożeniem produkcyjnym albo etapami zależnymi od nich trzeba ustalić:

- pierwsze **produkcyjne** OSK pilotażowe, właściciela pilota, liczbę kursantów i monitoring rollbacku; lokalny pilot B nie jest tą decyzją;
- zakres PAPER V1 i partnera, z którym zostanie sprawdzony;
- canonical wzory dokumentów PAPER i osobę zatwierdzającą prawnie;
- retencję danych, zwłaszcza negatywnych wyników;
- pierwszy tryb obsługi egzaminu: `IN_PLATFORM`, `EXTERNAL_OSK` lub `NOT_MANAGED`;
- kiedy i dla ktorych kategorii poza B zaprojektowac osobny zakres katalogu oraz tresci.

Brak odpowiedzi nie blokuje utrzymania katalogu B, ale blokuje PAPER, formalny egzamin, produkcyjny rollout oraz rozszerzenie o inne kategorie.

## 9. Stan Git i przekazanie

Na dzień tego dokumentu Etapy 1–5P i przygotowanie danych Etapu 6A są zaimplementowane lokalnie, ale nie są wdrożone na produkcję. Etap 5P dodał fail-closed preflight e-maila z hasłem: domyślnie dostępny jest wydruk, a e-mail wymaga jawnej flagi, allow-listy nietestowego mailera, prawidłowego nadawcy i publicznego HTTPS. Końcowa regresja OSK, paneli administratora, logowania i dashboardu przeszła `147` testów / `1278` asercji; przeszły także build Vite, cache konfiguracji i Blade oraz kontrola diffu. Etap 6A ma osobny test fundamentu: `6` testów / `50` asercji. Worktree zawiera zweryfikowane lokalnie rozszerzenia edytora, gotowości pilota, dwóch kanałów zapisu, aktywacji i wstrzymania kursu, katalogu B, ręcznie zatwierdzanego onboardingu B2B, operacyjnej obsługi zapisów, desk-assisted credentials i roboczych danych PAPER. Zastąpione rewizje PAPER mają techniczną retencję 90 dni i sa automatycznie porzadkowane na produkcji; najnowsza rewizja nie jest usuwana automatycznie. Źródłowy kurs B `V1` jest zatwierdzony, opublikowany i przeszedł jeden lokalny smoke z testowym OSK oraz kursantem; nie oznacza to zgody na produkcję. Etap 6A nie tworzy dokumentu, nie zbiera danych urzędowych, nie zmienia formalnego stanu i nie uruchamia PDF ani podpisu. Nadal nie ma automatycznej weryfikacji rejestrowej firmy, SMS/OTP, płatności, masowej wysyłki, rzeczywistego partnera ani produkcyjnej konfiguracji poczty. Etap 3 nie ma produkcyjnej tabeli reguł i nie może być użyty jako wiążąca decyzja formalna bez akceptacji compliance. Przed pracą kolejny agent zawsze sprawdza `git status --short --branch`; nie zakłada, że branch, worktree ani deploy są w tym samym stanie co w poprzedniej rozmowie.

Przy każdym zakończonym etapie:

1. zaktualizuj `OSK_V2_12_IMPLEMENTATION_PLAN.md`;
2. dopisz wyniki testów oraz deploymentu;
3. zaktualizuj tę sekcję o następny bezpieczny krok;
4. nie zmieniaj oryginalnego blueprintu — dopisuj interpretacje do planu lub raportu.

## 10. Historia zmian

| Data | Zmiana |
| --- | --- |
| 2026-08-24 | Utworzono punkt wejścia OSK, ustalono hierarchię dokumentów i usunięto niejednoznaczność między formalnym OSK a istniejącą nauką pytań. |
| 2026-08-24 | Dodano audyt zgodności z blueprintem V2.12 i twarde interpretacje dla Learning Engine, staffu PAPER oraz Browser Exam Station. |
| 2026-08-25 | Zakończono lokalnie Etap 1: fundament multi-tenant, formalny staff, audyt organizacyjny, polityki i testy izolacji. |
| 2026-08-25 | Zakończono lokalnie Etap 2: zamrożony kontrakt kursu, enrollment, token aktywacyjny, dostęp 90 dni i odseparowany ledger kredytów OSK. |
| 2026-08-25 | Zakończono lokalnie Etap 3: wersje reguł i faktów, snapshoty wymagań, korekty, trzy resolvery i fail-closed flagi ręcznego przeglądu; brak UI, seeda produkcyjnego i deployu. |
| 2026-08-25 | Zakończono lokalnie Etap 4A: wersjonowane moduły, lekcje i kroki programu teorii, atomowa publikacja snapshotu, blokada mutacji/usunięcia i testowy pilot kategorii B; brak routingu, UI, seeda treści i deployu. |
| 2026-08-25 | Zakończono lokalnie Etap 5A: wersjonowana polityka czasu, immutable sesje nauki i append-only heartbeaty; brak endpointu, UI, automatycznego timera, formalnego licznika czasu, assessmentu i evidence. |
| 2026-08-25 | Zakończono lokalnie Etap 5B: odczytowy walidator czasu, przedziały `UNION`, fail-closed problemy i testy granic; nadal brak endpointu, UI, formalnego evidence i deployu. |
| 2026-08-25 | Zakończono lokalnie Etap 5C: endpointy start/wznowienie, heartbeat i close z idempotencją, scope enrollmentu i limitami; integracja UI została wykonana osobno w Etapie 5D. |
| 2026-08-25 | Zakończono technicznie lokalnie Etap 5D: świadomy start/wznowienie w LessonPlayerze, heartbeat, obsługa timeoutu/błędów i jawne close; wymagany ręczny smoke test, brak postępu/evidence i deployu. |
| 2026-08-26 | Zakończono ręczny smoke test Etapu 5D lokalnie: start/wznowienie, heartbeat, przejście między lekcjami, timeout, close, mock błędu sieci i wygasłego logowania; naprawiono kolizję payloadu `navigation` ze wspólnym menu. Nadal brak postępu/evidence, seeda, feature flagi i deployu. |
| 2026-08-26 | Zakończono lokalnie Etap 5E: kursor ostatniego kroku lekcji na zamrożonym programie, append-only zdarzenia `STEP_VIEWED`, endpoint idempotentny i integrację z playerem. Pełny OSK `53 / 406`, regresja istniejących modułów `72 / 1265`, migracja, Pint, build i kontrola diffu przeszły. Brak formalnego ukończenia, evidence, assessmentu i deployu. |
| 2026-08-26 | Zakończono lokalnie Etap 5F: domyślnie wyłączona brama środowiskowa, per-enrollment allow-lista, panel administratora oraz audit. Nie dodano realnej treści, automatycznego seeda ani deployu. |
| 2026-08-26 | Dodano lokalny, fikcyjny seed pilota kategorii B: jedna lekcja i pięć kroków demonstracyjnych, konto testowe kursanta oraz kontrola polityki czasu. Brama środowiskowa pozostaje lokalna i świadomie włączana; produkcja nie została zmieniona. |
| 2026-08-26 | Rozszerzono lokalny seed do 2 modulow, 3 lekcji i 15 krokow. Starszy, rozpoznany program lokalny jest forkowany do V2, zachowuje historie enrollmentu i dostepu, a jego allow-lista pilota zostaje odwracalnie wylaczona z audytem. Dodano test przejscia V1 -> V2; produkcja nie zostala zmieniona. |
| 2026-08-26 | Dodano lokalnie Etap 5G: plynny przycisk glowny w playerze, ktory po ostatnim kroku prowadzi do kolejnej lekcji albo dzialu bez auto-close sesji. Koniec programu swiadomie zamyka sesje i wraca do planu, bez formalnego ukonczenia. Pelny OSK: 60 / 606; build i Pint przeszly. Produkcja nie zostala zmieniona. |
| 2026-08-26 | Dodano lokalnie Etap 5H: panel administratora oraz rzeczywisty, nieaktywny szkic kategorii B z modułem „Pierwszeństwo i obserwacja drogi” i trzema lekcjami bez kroków. Testy strony i blokady programu: 10 / 59; brak publikacji, enrollmentu i deployu. |
| 2026-08-27 | Rozszerzono ręczny smoke Etapu 5H o pełną redakcję lokalnego `DRAFT`: moduły, lekcje, zmianę kolejności oraz sześć formatów kroków. Naprawiono zapis opcjonalnego kontekstu pytania i dodano regresję. Dane `TEST UI` pozostają lokalne, nieaktywne i nie są przeznaczone do publikacji. |
| 2026-08-27 | Zakonczono lokalnie Etap 5J: wlasciciel/delegowany operator OSK i administrator platformy maja odrebne, audytowalne UI zapisu kursanta; dodano token aktywacyjny OSK oraz testy zapisu i regresje. Kurs zrodlowy, allow-lista i globalna flaga pozostaly bez zmian; brak deployu. |
| 2026-08-28 | Zakonczono lokalnie Etap 5K: dodano audytowalna aktywacje i wstrzymanie kursu, a nastepnie przeprowadzono jeden kontrolowany pilot z testowym OSK i kursantem dla zrodlowej wersji B. Smoke HTTP potwierdzil player, sesje, postep i izolacje dostepu. Brak deployu, formalnego ukonczenia, evidence oraz decyzji o pilocie produkcyjnym. |
| 2026-08-30 | Dodano lokalnie Etap 6A PAPER: jawny wybor trybu, immutable dane robocze i append-only rewizje z hashem, audit oraz stronicowany panel administratora. Test fundamentu: 4 / 34. Brak danych urzedowych, review, formalnego dokumentu, PDF, podpisu i deployu. |
| 2026-08-31 | Uzupełniono Etap 6A o deduplikację identycznego stanu źródłowego oraz kontrolowaną retencję wyłącznie zastąpionych rewizji roboczych: 90 dni domyślnie, dry run, automatyczny harmonogram produkcyjny z możliwością wyłączenia flagą i zachowanie najnowszej rewizji. Test fundamentu: 6 / 50. Zapisano osobno granicę wobec retencji formalnej karty z par. 18 rozporządzenia. |
| 2026-08-28 | Ponownie przeskanowano dokumentacje i kod po pilocie. Oznaczono historyczne audyty, poprawiono biezacy stan oraz dodano jako propozycje nieuruchomiony Etap 5L: generyczny katalog i archiwizacja kursow przed dalszym rozszerzaniem kategorii. |
| 2026-08-28 | Zakonczono lokalnie Etap 5L w zakresie kategorii B: katalog jest stronicowany, tworzenie i archiwizacja sa audytowalne, pusty draft mozna bezpiecznie usunac, a archiwum blokuje nowe zapisy bez odebrania historii. Inne kategorie pozostaja poza zakresem; brak deployu. |
| 2026-08-29 | Przed Etapem 5M powtórzono lokalny przepływ właściciel OSK -> zapis -> aktywacja -> allow-lista -> nauka. Naprawiono przekierowanie po zwykłym logowaniu kursanta z gotowym dostępem OSK do `/osk/nauka`; regresje B2C, PJM i playera przeszły. Późniejszy Etap 5M domknął publiczne zgłoszenie firmy, walidację NIP/telefonu i ręczną weryfikację szkoły. Nadal brak listy kursantów, ponownego wydania linku oraz automatycznej wysyłki e-mail/QR. Brak deployu. |
| 2026-08-29 | Zakończono lokalnie Etap 5M: publiczne zgłoszenie OSK, walidacja NIP i telefonu, zwykła weryfikacja e-maila, stronicowana kolejka administratora oraz fail-closed aktywacja szkoły. Zatwierdzenie dopiero po potwierdzeniu e-maila uruchamia jednorazowy pakiet pięciu kredytów; nie ma deployu, integracji rejestrowej, SMS/OTP, płatności ani masowych decyzji. |
| 2026-08-29 | Ponownie porównano dokumentację z kodem po Etapie 5M. Potwierdzono lokalną migrację oraz ręczny smoke zgłoszenia, potwierdzenia e-maila, akceptacji i pakietu pięciu miejsc. Jako następny, nieuruchomiony etap zaproponowano operacyjne zarządzanie zapisami kursantów przed decyzją o pilocie produkcyjnym. |
| 2026-08-29 | Zakończono lokalnie Etap 5N: stronicowane, odizolowane listy zapisów dla właściciela OSK i administratora, ponowne wydanie linku tylko dla `PENDING`, anulowanie oczekującego zapisu ze zwolnieniem miejsca oraz audit bez surowego tokenu. Testy domenowe i paneli, build Vite oraz kontrola diffu przeszły. Brak deployu, e-maila, QR i masowych operacji. |
| 2026-08-29 | Ponownie przeskanowano dokumentację, blueprint i kod po Etapie 5N oraz zmianie domyślnego ekranu właściciela OSK. Pełny lokalny pakiet OSK, paneli administracyjnych i redirectów przeszedł: `130` testów / `1211` asercji. Jako następny nieuruchomiony etap zapisano 5O: świadoma wysyłka e-maila z bezpiecznym ręcznym fallbackiem; brak deployu. |
| 2026-08-30 | Zakończono lokalnie Etap 5O: właściciel OSK, operator i administrator mogą utworzyć dla nowego kursanta aktywne konto przy biurku z loginem i hasłem `Imie123456`, wydrukiem oraz bezpiecznym kanałem e-mail. Ponowne wydanie zmienia hasło bez drugiego miejsca. Jednorazowy widok nie pozostawia hasła w historii przeglądarki ani po odświeżeniu. Przeszło `50` testów / `343` asercje, build Vite i ręczny smoke. Brak deployu oraz produkcyjnego mailera. |
| 2026-08-30 | Ponownie porównano dokumentację z kodem po Etapie 5O. Potwierdzono bramy dostępu pilota, aktywność kursu, izolację OSK i lokalny zakres testów. Zapisano plan Etapu 5P: przed produkcyjnym e-mailem kod musi odrzucać transporty testowe oraz fallbacki do logów, a partner, nadawca, monitoring i rollback muszą być wskazane. Brak deployu. |
| 2026-08-30 | Zakończono technicznie lokalnie Etap 5P: domyślnie wyłączony e-mail z hasłem, jawna allow-lista mailera, blokada `log`/`array`/`failover`/`roundrobin`, walidacja nadawcy i HTTPS oraz czytelny fallback do wydruku w panelu OSK i administratora. Końcowa regresja OSK, paneli administratora, logowania i dashboardu `147` / `1278`, build Vite, cache konfiguracji i Blade oraz kontrola diffu przeszły. Naprawiono też test harness, aby usuwał pliki SQLite `-wal` i `-shm` po teście. Nadal brak deployu, partnera pilota, produkcyjnego nadawcy, DNS poczty i monitoringu. |
| 2026-08-30 | Ponowiono mały, wyłącznie lokalny pilot na danych `demo_only`: pięć bram dostępu, logowanie kursanta, plan i player, świadomy start sesji, zakończenie programu bez formalnego ukończenia, blokada operatora bez enrollmentu (`404`) oraz niezależne `/nauka`. Regresja: `61` testów / `674` asercje. Brak deployu, prawdziwych danych i wysyłki e-mail. |
