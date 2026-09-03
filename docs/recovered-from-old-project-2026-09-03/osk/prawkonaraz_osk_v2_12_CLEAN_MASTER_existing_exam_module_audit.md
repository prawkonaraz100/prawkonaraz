# PrawkoNaRaz OSK — V2.12 CLEAN MASTER

## Canonical Product, Legal-Compliance & Technical Blueprint

**Status:** CANONICAL / CLEAN MASTER  
**Stack:** Laravel + Vue  
**Data skonsolidowania:** 24.08.2026  
**Zakres startowy produktu:** komercyjne OSK, kategoria B jako pierwszy pełny vertical slice; architektura przygotowana na pozostałe kategorie i wyjątki.  
**Dokument zastępuje:** V1.x, V2.0, V2.1, V2.2, V2.3, V2.4, V2.5, V2.6, V2.7, V2.8, V2.9, V2.10 oraz V2.11.

> **Jeżeli jakikolwiek wcześniejszy dokument, komentarz, model tabeli albo decyzja jest sprzeczna z V2.12 CLEAN MASTER, obowiązuje V2.12 CLEAN MASTER.**

---

# 0. Po co istnieje ten dokument

Ten dokument jest jedyną specyfikacją wdrożeniową PrawkoNaRaz OSK.

Nie jest historią projektu.  
Nie zawiera starych modeli pozostawionych „dla kontekstu”.  
Nie należy implementować niczego na podstawie wcześniejszych wersji bez sprawdzenia V2.12 CLEAN MASTER.

Główna zasada produktu:

> **PrawkoNaRaz sam ustala wymagania konkretnego enrollmentu, prowadzi kursanta przez wymagany zakres, zapisuje wiarygodny przebieg, przygotowuje dokumentację i prosi OSK wyłącznie o te decyzje lub formalne potwierdzenia, których system nie może wykonać w jego imieniu.**

---

# 1. Stan architektury po konsolidacji

## 1.1. Gotowe koncepcyjnie / do implementacji

```text
Organization / multi-tenant
ORG_OWNER / ORG_ADMIN / ORG_MEMBER
Formal staff qualifications
Student onboarding
Activation QR / one-time token
Access / billing / credits
CourseEnrollment
CourseVersion immutability
EnrollmentFacts
EnrollmentRequirementsResolver
RequirementAdjustments
Legal Requirements Registry
Learning Engine
ModuleAssessment
Time Evidence
FormalReadiness
System Evidence
Instructor Review
PAPER Training Card flow
Internal Theory Exam flow
Browser Exam Station / realtime handoff
Internal Practical Exam PAPER flow
First Aid formal status
Practical Training minimal documentation model
Dual Documentation architecture
Electronic Documentation blueprint
Audit / snapshots / hashes / versioning
```

## 1.2. P0 przed komercyjnym formalnym launch

```text
1. finalny legal audit implementacji,
2. canonical PDF arkusza praktycznego egzaminu wewnętrznego,
3. wersjonowane reguły silnika teoretycznego egzaminu wewnętrznego,
4. domknięcie wybranych edge-case'ów redukcji godzin,
5. pełna polityka RODO / retencji / bezpieczeństwa,
6. komplet testów compliance.
```

## 1.3. Nie jest blockerem PAPER V1

```text
bezpośrednia integracja PKK / CEK
CRM leadów
grafik jazd
kalendarz instruktorów
pełny fleet management
SMS
AI tutor
adaptive learning
mastery engine
```

---

# 2. Zasady nadrzędne — NIE ŁAMAĆ

1. **Compliance by design.** Reguły prawne są w backendzie, nie w głowie przedsiębiorcy.
2. **Exception-first UX.** OSK widzi normalny stan lub konkretny problem i następną akcję.
3. **Jeden Learning Engine.** Nie tworzymy osobnego silnika dla B2C i OSK.
4. **Operational availability ≠ formal requirement.** Dostępność narzędzia nie oznacza, że dana czynność jest formalnie wymagana.
5. **Learning Engine ≠ Internal Exam Engine.**
6. **ModuleAssessment ≠ egzamin wewnętrzny.**
7. **System Evidence ≠ dokumentacja zaakceptowana przez OSK.**
8. **Źródłowych sesji i assessmentów nie edytujemy po cichu.**
9. **Formalne dokumenty po zatwierdzeniu są immutable i wersjonowane.**
10. **Wyjątek prawny zmienia `EnrollmentRequirements`, a nie kod Learning Engine.**
11. **Nie używamy jednego `exemption = true/false`.**
12. **Nie hardkodujemy wyjątków w Vue ani kontrolerach egzaminu.**
13. **Nie blokujemy funkcji operacyjnej tylko dlatego, że Learning Engine nie jest ukończony, jeśli blokada nie jest wymagana do konkretnej formalnej czynności.**
14. **PAPER najpierw; ELECTRONIC rozwijamy na tym samym modelu danych.**
15. **Urzędowy PDF ma wyglądać jak urzędowy formularz, nie jak dokument PrawkoNaRaz.**

---

# 3. Architektura domenowa

```text
User
│
├── OrganizationUser
│     └── Application Role
│            ├── ORG_OWNER
│            ├── ORG_ADMIN
│            └── ORG_MEMBER
│
├── OrganizationStaffMember
│     ├── StaffQualification
│     │      ├── INSTRUCTOR
│     │      └── LECTURER
│     └── FormalRole
│            └── OSK_MANAGER
│
└── CourseEnrollment
      │
      ├── StudentCourseAccess
      ├── EnrollmentFacts
      ├── EnrollmentRequirements
      │      └── RequirementAdjustments[]
      │
      ├── CourseVersion
      │      └── Learning Engine
      │             ├── LearningSessions
      │             ├── Lessons / Steps
      │             └── ModuleAssessments
      │
      ├── System Evidence
      │
      ├── FirstAidCompletion
      ├── PracticalTrainingEntries
      ├── InternalTheoryExam
      │      └── BrowserExamStation
      ├── InternalPracticalExam
      │
      └── Documentation
             ├── TrainingCardDataDraft
             ├── PAPER
             │     └── PaperTrainingCardExport
             └── ELECTRONIC
                   └── ElectronicTrainingCard
```

---

# 4. Tożsamość, role i OSK

## 4.1. Application roles

Canonical:

```text
ORG_OWNER
ORG_ADMIN
ORG_MEMBER
STUDENT
```

### `ORG_OWNER`

Nadrzędny właściciel organizacji.

```text
permissions(ORG_OWNER)
⊇
permissions(any other organization role)
```

Ma wszystkie funkcje produktowe organizacji oraz ownership-level operations.

### `ORG_ADMIN`

Delegowany administrator organizacji.

Najczęściej jest to osoba prowadząca codzienną obsługę OSK.

Może otrzymać m.in.:

```text
STUDENT_READ
STUDENT_MANAGE
LEARNING_READ
EXAM_READ
EXAM_MANAGE
DOCUMENT_READ
DOCUMENT_MANAGE
STAFF_READ
STAFF_MANAGE
FIRST_AID_MANAGE
PRACTICAL_TRAINING_MANAGE
ACCESS_MANAGE
```

Zakres jest ograniczany przez permissions nadane przez `ORG_OWNER`.

### `ORG_MEMBER`

Opcjonalne konto organizacji z mniejszym zakresem permissions.

### `STUDENT`

Konto osoby szkolonej.

Może:

- aktywować własny dostęp,
- logować się do Learning Engine,
- wykonywać przypisane działania kursanta,
- widzieć własny postęp i wyniki dostępne w produkcie.

## 4.2. Application identity ≠ formal staff identity

`OrganizationUser` opisuje osobę posiadającą konto aplikacyjne.

`OrganizationStaffMember` opisuje osobę występującą formalnie w działalności OSK.

Instruktor / wykładowca w PAPER nie musi posiadać konta aplikacyjnego.

```text
organization_staff_members

id
organization_id

first_name
last_name

registry_number nullable
categories_json nullable

active
linked_user_id nullable

created_at
updated_at
```

Canonical qualifications:

```text
organization_staff_qualifications

id
organization_staff_member_id

qualification_type
# INSTRUCTOR
# LECTURER

category
valid_from nullable
valid_to nullable
verified_at nullable

created_at
updated_at
```

Canonical formal roles:

```text
organization_formal_roles

id
organization_staff_member_id

formal_role
# OSK_MANAGER

valid_from
valid_to nullable

created_at
updated_at
```

`ORG_OWNER`, `ORG_ADMIN` i `ORG_MEMBER` nie oznaczają automatycznie żadnej kwalifikacji formalnej.

## 4.3. Formalny e-learning OSK

```text
organization_formal_elearning_status

organization_id
category

status
# UNVERIFIED
# VERIFIED
# SUSPENDED
# EXPIRED

verification_source nullable
verified_at nullable
valid_from nullable
valid_to nullable
```

Dla `training_context = FORMAL_OSK` formalna teoria online wymaga odpowiedniego statusu organizacji zgodnie z wersjonowanym `LegalRequirementsRegistry`.

---

# 4A. OPERATIONS-FIRST — model operacyjny OSK

## 4A.1. Decyzja produktowa

W PAPER V1 operatorem PrawkoNaRaz jest `ORG_OWNER` albo konto organizacyjne utworzone przez ORG_OWNERa. `ORG_OWNER` zawsze ma pełen dostęp; konto organizacyjne otrzymuje delegowany, ograniczony zakres permissions.

czyli osoba pracująca w biurze OSK / pracownik administracyjny.

Nie zakładamy, że każdy instruktor będzie codziennie logował się do PrawkoNaRaz.

Nie tworzymy specjalnego konta:

```text
EXAMINER
```

i nie wymagamy osobnego konta aplikacyjnego dla każdego instruktora w trybie PAPER.

## 4A.2. Konto aplikacyjne ≠ profil formalnego pracownika

Rozdzielić:

```text
A. Application User
B. Organization Staff Member
```

### Application User

Osoba faktycznie obsługująca PrawkoNaRaz:

```text
ORG_OWNER
ORG_ADMIN
```

### Organization Staff Member

Osoba istniejąca w dokumentacji OSK:

```text
INSTRUCTOR
LECTURER
OSK_MANAGER
```

Może nie mieć konta aplikacyjnego.

Canonical:

```text
organization_staff_members

id
organization_id

first_name
last_name

staff_type
# INSTRUCTOR
# LECTURER
# OSK_MANAGER

registry_number nullable
categories_json nullable

valid_from nullable
valid_to nullable
active

linked_user_id nullable

created_at
updated_at
```

`linked_user_id = null` jest całkowicie poprawne w PAPER.

## 4A.3. Role aplikacyjne

Canonical dla V1:

```text
ORG_OWNER
ORG_ADMIN
```

Jeżeli istniejący kod posiada legacy enum `MODERATOR`, migracja domenowa mapuje go na `ORG_ADMIN`.

### `ORG_OWNER`

Pełny dostęp:

```text
kursanci
dokumenty
egzaminy
instruktorzy
ustawienia OSK
billing
użytkownicy biura
```

### ORG_ADMIN

Codzienna obsługa:

```text
kursanci
aktywacje
postęp
dokumenty
egzaminy
instruktorzy / wykładowcy
wpisy praktyczne
pierwsza pomoc
wydruki
```

Domyślnie bez:

```text
billing
zmiana właściciela
krytyczne ustawienia organizacji
zarządzanie innymi ORG_OWNER
```

OSK może mieć jednego ORG_OWNERa, który praktycznie prawie się nie loguje, oraz jedną lub kilka osób `ORG_ADMIN`.

## 4A.4. PAPER — instruktor nie potrzebuje loginu

Flow:

```text
ORG_ADMIN loguje się swoim kontem
↓
otwiera kursanta
↓
wybiera instruktora z listy staff
↓
wypełnia / przygotowuje dokument
↓
drukuje
↓
instruktor składa wymagany podpis na papierze
↓
kursant składa wymagany podpis na papierze
```

PrawkoNaRaz zapisuje:

```text
prepared_by_user_id
formal_actor_staff_member_id
```

To są dwie różne osoby / role.

Nie zapisywać:

```text
prepared_by == formal_actor
```

jako domyślnego założenia.

## 4A.5. Egzamin teoretyczny — OPERATIONS-FIRST

ORG_ADMIN może:

```text
- wybrać kursanta,
- utworzyć sesję egzaminu,
- wybrać instruktora / wykładowcę przeprowadzającego,
- przygotować stanowisko,
- uruchomić workflow techniczny,
- wydrukować wynik,
- przygotować dokumentację.
```

Formalnie:

```text
conducted_by_staff_member_id
```

musi wskazywać właściwego instruktora / wykładowcę.

Operator systemu:

```text
created_by_user_id
prepared_by_user_id
```

może być pracownikiem biura.

W PAPER wymagany podpis instruktora / wykładowcy i osoby szkolonej następuje na papierowej dokumentacji.

## 4A.6. Egzamin praktyczny — OPERATIONS-FIRST

ORG_ADMIN może przed egzaminem przygotować:

```text
kursant
data
kategoria
instruktor egzaminujący
instruktor prowadzący
pojazd
pusty arkusz
```

Po egzaminie może również przepisać do PrawkoNaRaz dane z wypełnionego dokumentu, jeżeli OSK chce prowadzić roboczą dokumentację w systemie.

Ale:

```text
formal_examiner_staff_member_id
lead_instructor_staff_member_id
```

wskazują rzeczywiste osoby formalne.

W PAPER wymagane podpisy pozostają fizycznie na arkuszu.

## 4A.7. Nie projektujemy współdzielenia haseł

PrawkoNaRaz nie powinno wymagać od pracownik administracyjnyu znajomości loginów i haseł instruktorów.

Nie modelujemy procesu:

```text
ORG_ADMIN
↓
loguje się jako instruktor
↓
wykonuje jego czynność
```

Zamiast tego:

```text
ORG_ADMIN loguje się raz jako ORG_ADMIN
↓
przygotowuje operację
↓
system zapisuje operatora
+
wskazaną formalną osobę
```

Dzięki temu audit jest zgodny z rzeczywistym przebiegiem.

## 4A.8. PAPER vs ELECTRONIC

### PAPER

Instruktor może nie mieć konta aplikacyjnego.

```text
organization_staff_members.linked_user_id = null
```

jest dozwolone.

### ELECTRONIC

Jeżeli konkretna czynność wymaga elektronicznego uwierzytelnienia instruktora / wykładowcy, dana osoba musi mieć własną tożsamość uwierzytelnianą.

Dopiero wtedy:

```text
organization_staff_members.linked_user_id != null
```

oraz:

```text
FormalAuthenticationService
```

obsługuje minimalne potwierdzenie.

Nie budujemy tego wymagania w PAPER.

## 4A.9. Minimalny ELECTRONIC handoff

Docelowo pracownik administracyjny może przygotować wszystko:

```text
ORG_ADMIN
↓
Przygotuj dokument
↓
[Wyślij do potwierdzenia instruktorowi]
```

Instruktor dostaje tylko:

```text
Jan Kowalski
Egzamin wewnętrzny — praktyka
Wynik: POZYTYWNY

[Potwierdź]
```

Nie dostaje całego panelu administracyjnego.

## 4A.10. Audit — operator vs formal actor

Każda formalnie istotna operacja powinna rozróżniać:

```text
operator_user_id
formal_actor_staff_member_id
authenticated_by_user_id nullable
```

Przykład PAPER:

```text
operator_user_id = ORG_ADMIN
formal_actor_staff_member_id = instruktor Adam Nowak
authenticated_by_user_id = null
paper_signature = outside system
```

Przykład ELECTRONIC:

```text
operator_user_id = ORG_ADMIN
formal_actor_staff_member_id = instruktor Adam Nowak
authenticated_by_user_id = konto Adama Nowaka
```

## 4A.11. Główny panel biura

Canonical navigation V1:

```text
PrawkoNaRaz

Kursanci
Nauka
Egzaminy
Dokumenty
Instruktorzy
```

ORG_OWNER dodatkowo:

```text
Dostępy i płatności
Ustawienia OSK
Użytkownicy organizacji
```

Nie budować osobnego rozbudowanego dashboardu instruktora przed PAPER vertical slice.

## 4A.12. Widok „Egzaminy” dla pracownik administracyjnyu

```text
Egzaminy

Jan Kowalski        B
Teoria              Do przeprowadzenia
[Otwórz]

Anna Nowak          C+E
Praktyka            Do przygotowania
[Przygotuj arkusz]

Piotr Kowalski      B
Teoria              Poza PrawkoNaRaz
[Odnotuj wynik]
```

ORG_ADMIN nie musi przełączać kont.

## 4A.13. Widok instruktora jako opcja, nie fundament

W przyszłości można dodać `STAFF_PORTAL`, ale:

```text
PAPER V1
```

nie może być od niego zależne.

Jeżeli kiedyś instruktor chce mieć dostęp:

```text
organization_staff_member
↓
link account
↓
limited STAFF_PORTAL
```

bez zmiany formalnego profilu staff.

## 4A.14. Reguła nadrzędna OPERATIONS-FIRST

> **W PAPER PrawkoNaRaz jest narzędziem biura OSK. ORG_ADMIN przygotowuje dane, egzaminy i dokumenty jednym własnym kontem. Instruktor jest formalną osobą wskazaną w dokumentacji i składa wymagany podpis na papierze; nie musi posiadać loginu PrawkoNaRaz.**

> **Dopiero ELECTRONIC wymusza indywidualne elektroniczne uwierzytelnienie tam, gdzie prawo wymaga potwierdzenia konkretnej osoby.**




---

# 4B. ORG_OWNER-FIRST — canonical permission model

## 4B.1. Zasada nadrzędna

`ORG_OWNER` jest jedyną nadrzędną rolą organizacyjną.

```text
ORG_OWNER
= pełny dostęp do wszystkich funkcji OSK
```

Każde inne konto organizacji:

```text
ORG_ADMIN
ORG_MEMBER
future STAFF_PORTAL
```

jest wyłącznie ograniczonym delegatem ORG_OWNERa.

Nie budować modelu:

```text
ORG_OWNER ma funkcje A
ORG_ADMIN ma funkcje B
INSTRUCTOR ma funkcje C
```

jako trzech równoległych, niezależnych światów.

Prawidłowo:

```text
ORG_OWNER
└── wszystkie permissions
    ├── delegowane do ORG_ADMIN
    ├── delegowane do ORG_MEMBER
    └── ewentualnie delegowane do innych kont
```

## 4B.2. `ORG_OWNER` ma zawsze wszystko

ORG_OWNER może:

```text
Kursanci
Aktywacje
Nauka
Postęp
Egzaminy
Dokumenty
Pierwsza pomoc
Praktyka
Instruktorzy / wykładowcy
Pojazdy
Wydruki
Billing
Pakiety dostępów
Użytkownicy organizacji
Ustawienia OSK
Tryby PAPER / ELECTRONIC
Ustawienia obsługi egzaminów
Audyt
```

Nie tworzyć sytuacji, w której `ORG_ADMIN` może coś zrobić, czego ORG_OWNER nie może.

Canonical invariant:

```text
permissions(ORG_OWNER)
⊇
permissions(any_other_organization_role)
```

## 4B.3. Konta pod ORG_OWNERem są ograniczane

ORG_OWNER tworzy konto użytkownika organizacji.

Najprostszy UX:

```text
Dodaj użytkownika organizacji

Imię i nazwisko
E-mail

Zakres dostępu:

[✓] Kursanci
[✓] Nauka i postęp
[✓] Egzaminy
[✓] Dokumenty
[✓] Instruktorzy
[✓] Pierwsza pomoc
[✓] Praktyka
[ ] Płatności
[ ] Ustawienia OSK

[Dodaj]
```

Nie wymagać od ORG_OWNERa rozumienia RBAC.

Można zaoferować preset:

```text
Pełna obsługa biura
```

który zaznacza wszystkie operacyjne uprawnienia poza krytycznymi właścicielskimi.

## 4B.4. Role jako presety, permissions jako source of truth

Role produktowe są wygodnym presetem:

```text
ORG_OWNER
ORG_ADMIN
ORG_MEMBER
```

ale backend autoryzuje przez permissions.

Przykład:

```text
permissions

STUDENTS_VIEW
STUDENTS_MANAGE

LEARNING_VIEW
EXAMS_MANAGE

DOCUMENTS_VIEW
DOCUMENTS_MANAGE

STAFF_VIEW
STAFF_MANAGE

PRACTICAL_MANAGE
FIRST_AID_MANAGE

ACCESS_MANAGE

BILLING_VIEW
BILLING_MANAGE

ORGANIZATION_SETTINGS_MANAGE
OFFICE_USERS_MANAGE
```

### `ORG_OWNER`

```text
ALL
```

Nie przechowywać dla ORG_OWNERa ręcznie zaznaczanej listy, która może przypadkiem utracić uprawnienie.

Backend:

```text
if organization_user.role == ORG_OWNER:
    allow()
```

z wyjątkiem czynności wymagających dodatkowej kwalifikacji formalnej / uwierzytelnienia konkretnej osoby.

## 4B.5. Organization Admin

Domyślny preset:

```text
STUDENTS_VIEW
STUDENTS_MANAGE

LEARNING_VIEW

EXAMS_MANAGE

DOCUMENTS_VIEW
DOCUMENTS_MANAGE

STAFF_VIEW
STAFF_MANAGE

PRACTICAL_MANAGE
FIRST_AID_MANAGE

ACCESS_MANAGE
```

Domyślnie bez:

```text
BILLING_MANAGE
ORGANIZATION_SETTINGS_MANAGE
OFFICE_USERS_MANAGE
```

ORG_OWNER może rozszerzyć albo ograniczyć zakres.

## 4B.6. Organization Member

Opcjonalny bardziej ograniczony preset.

Przykład:

```text
STUDENTS_VIEW
LEARNING_VIEW
DOCUMENTS_VIEW
```

Może służyć np. pracownikowi recepcji.

Nie jest wymagany w V1, jeżeli `ORG_ADMIN` wystarczy.

## 4B.7. Formalna kwalifikacja nadal jest osobną osią

Pełny dostęp ORG_OWNERa do UI nie oznacza automatycznie, że ORG_OWNER jest:

```text
INSTRUCTOR
LECTURER
OSK_MANAGER
```

Dlatego trzeba rozróżnić:

```text
CAN_MANAGE_EXAM_WORKFLOW
```

od:

```text
CAN_BE_FORMAL_EXAMINER
```

ORG_OWNER może przygotować egzamin i dokumentację, ale:

```text
formal_examiner_staff_member_id
```

musi wskazywać właściwą osobę formalną.

Jeżeli ORG_OWNER sam posiada odpowiednią kwalifikację:

```text
ORG_OWNER User
+
linked OrganizationStaffMember
+
INSTRUCTOR qualification
```

może być jednocześnie operatorem i formalnym aktorem.

## 4B.8. ORG_ADMIN jako delegacja ORG_OWNERa

W praktyce:

```text
ORG_OWNER
↓
tworzy konto pracownik administracyjnyu
↓
nadaje "Pełna obsługa biura"
↓
pracownik administracyjny robi prawie wszystko operacyjne
```

ORG_OWNER nadal może wejść w każdy ten sam ekran.

Nie tworzymy osobnego „osobnego panelu administracyjnego”.

UI ORG_OWNERa i `ORG_ADMIN` korzysta z tych samych ekranów; różnica polega na widocznych akcjach i sekcjach.

## 4B.9. Navigation

### `ORG_OWNER`

```text
Kursanci
Nauka
Egzaminy
Dokumenty
Instruktorzy
Dostępy i płatności
Ustawienia OSK
Użytkownicy organizacji
```

### ORG_ADMIN

Najczęściej:

```text
Kursanci
Nauka
Egzaminy
Dokumenty
Instruktorzy
```

Jeżeli ORG_OWNER nada dodatkowe permission:

```text
Dostępy
```

może pojawić się również ta sekcja.

## 4B.10. Krytyczne czynności właścicielskie

Nawet przy bardzo szerokim Organization Admin można zachować ORG_OWNER-only dla:

```text
DELETE_ORGANIZATION
TRANSFER_ORG_OWNERSHIP
MANAGE_ORG_OWNERS
CHANGE_LEGAL_ORGANIZATION_IDENTITY
```

Billing może być:

```text
ORG_OWNER_ONLY
```

w V1 albo delegowalny później przez permission.

## 4B.11. Audit

Każda operacja zapisuje faktycznego operatora:

```text
actor_user_id
organization_user_id
permission_used
created_at
```

Nigdy nie zapisujemy operacji pracownik administracyjnyu jako wykonanej przez ORG_OWNERa tylko dlatego, że ORG_OWNER nadał jej uprawnienie.

## 4B.12. Reguła nadrzędna

> **ORG_OWNER jest super-adminem organizacji i zawsze posiada wszystkie funkcje produktowe. Każde inne konto OSK jest wyłącznie ograniczoną delegacją jego możliwości.**

> **Nie projektujemy osobnych światów ORG_OWNERa, pracownik administracyjnyu i instruktora. ORG_OWNER i biuro korzystają z tych samych ekranów; permissions ograniczają akcje. Instruktor pozostaje przede wszystkim formalnym profilem staff, chyba że ELECTRONIC wymaga jego osobistego uwierzytelnienia.**




# 4C. Canonical Domain Naming — nazewnictwo dla implementacji

## 4C.1. Zasada

W kodzie, bazie danych, API, testach i dokumentacji dla agenta używać nazw domenowych opisujących **odpowiedzialność systemową**, a nie stanowisko spotykane w konkretnym OSK.

Nie używać jako nazw technicznych:

```text
SECRETARY
OFFICE_LADY
RECEPTIONIST
BIURO_USER
```

To są opisy realnego użytkownika biznesowego, nie stabilne role domenowe.

## 4C.2. Canonical application roles

```text
ORG_OWNER
ORG_ADMIN
ORG_MEMBER
```

### `ORG_OWNER`

Nadrzędny właściciel organizacji.

```text
ORG_OWNER
= wszystkie product permissions
+ ownership-level operations
```

### `ORG_ADMIN`

Delegowany administrator organizacji.

Najczęstszy realny użytkownik:

```text
pracownik administracyjny OSK
pracownik administracyjny
manager biura
```

ale nazwa techniczna pozostaje:

```text
ORG_ADMIN
```

Może otrzymać szeroki zakres operacyjny bez uprawnień właścicielskich.

### `ORG_MEMBER`

Opcjonalne konto organizacji z ograniczonym zestawem permissions.

Przykłady:

```text
recepcja
pomoc administracyjna
pracownik tylko do podglądu
```

Nie jest wymagane w pierwszym vertical slice, jeżeli `ORG_ADMIN` wystarcza.

## 4C.3. Canonical entity names

```text
Organization
OrganizationUser
OrganizationStaffMember
StaffQualification
FormalRole
CourseEnrollment
EnrollmentFacts
EnrollmentRequirements
RequirementAdjustment
```

Nie używać:

```text
Secretary
ExaminerAccount
InstructorAccount
OfficeAccount
```

jako encji domenowych.

## 4C.4. Application identity vs formal staff identity

```text
OrganizationUser
```

oznacza:

> osobę posiadającą konto i uprawnienia do korzystania z aplikacji w imieniu organizacji.

```text
OrganizationStaffMember
```

oznacza:

> osobę występującą formalnie w działalności OSK, np. instruktora, wykładowcę lub kierownika.

Te encje mogą, ale nie muszą, wskazywać tę samą osobę.

Canonical relationship:

```text
organization_staff_members.linked_user_id nullable
```

PAPER:

```text
linked_user_id = null
```

jest poprawne.

ELECTRONIC może wymagać:

```text
linked_user_id != null
```

dla czynności wymagających indywidualnego uwierzytelnienia.

## 4C.5. Formal qualifications

```text
StaffQualificationType

INSTRUCTOR
LECTURER
```

Formal organizational role:

```text
FormalRoleType

OSK_MANAGER
```

Nie modelować:

```text
EXAMINER
```

jako stałej roli konta.

Osoba jest egzaminującym w kontekście konkretnego egzaminu:

```text
internal_practical_exam.examiner_staff_member_id
```

lub:

```text
internal_theory_exam.conducted_by_staff_member_id
```

## 4C.6. Canonical permission names

Preferować permission enums w stylu:

```text
STUDENT_READ
STUDENT_MANAGE

LEARNING_READ

EXAM_READ
EXAM_MANAGE

DOCUMENT_READ
DOCUMENT_MANAGE

STAFF_READ
STAFF_MANAGE

FIRST_AID_MANAGE
PRACTICAL_TRAINING_MANAGE

ACCESS_MANAGE

BILLING_READ
BILLING_MANAGE

ORGANIZATION_SETTINGS_MANAGE
ORGANIZATION_USERS_MANAGE

AUDIT_READ
```

Nie kodować biznesowej logiki jako:

```text
if user.isSecretary()
```

Prawidłowo:

```text
authorization.can(user, EXAM_MANAGE)
```

## 4C.7. ORG_OWNER invariant

```text
ORG_OWNER
```

ma wszystkie permissions.

Canonical invariant:

```text
permissions(ORG_OWNER)
⊇
permissions(any other organization role)
```

Dodatkowo tylko `ORG_OWNER` wykonuje ownership-level operations, np.:

```text
TRANSFER_ORG_OWNERSHIP
DELETE_ORGANIZATION
MANAGE_ORG_OWNERSHIP
```

## 4C.8. UI labels mogą być prostsze niż nazwy w kodzie

Backend:

```text
ORG_OWNER
ORG_ADMIN
ORG_MEMBER
```

UI PL:

```text
Właściciel
Administrator
Pracownik
```

Nie pokazywać użytkownikowi nazw enumów.

## 4C.9. Operator vs formal actor

Canonical technical fields:

```text
operator_user_id
formal_actor_staff_member_id
authenticated_by_user_id nullable
```

Znaczenie:

```text
operator_user_id
= kto wykonał operację w aplikacji

formal_actor_staff_member_id
= kogo formalnie dotyczy czynność / kto jest osobą podpisującą lub prowadzącą

authenticated_by_user_id
= kto wykonał wymagane osobiste elektroniczne uwierzytelnienie
```

Przykład PAPER:

```text
operator_user_id
= ORG_ADMIN user

formal_actor_staff_member_id
= INSTRUCTOR staff member

authenticated_by_user_id
= null
```

Przykład ELECTRONIC:

```text
operator_user_id
= ORG_ADMIN user

formal_actor_staff_member_id
= INSTRUCTOR staff member

authenticated_by_user_id
= linked instructor user
```

## 4C.10. Zasada dla AI codera

> **Nie implementuj nazewnictwa na podstawie stanowisk potocznych. Modeluj organizację, tożsamość aplikacyjną, permissions i formalne kwalifikacje jako oddzielne koncepty domenowe.**


# 5. Multi-tenant

Każdy rekord należący do OSK posiada:

```text
organization_id
```

Dane OSK A nie mogą być dostępne OSK B.

Wszystkie endpointy administracyjne muszą być scope'owane backendowo po organizacji.

Nie polegać na filtrach Vue.

---

# 6. User, Enrollment i Access to trzy różne rzeczy

## 6.1. User

Globalna tożsamość użytkownika.

## 6.2. CourseEnrollment

Konkretny udział użytkownika w konkretnym kursie / kategorii / organizacji.

```text
course_enrollments

id
user_id
organization_id nullable
course_version_id

source
# ORGANIZATION
# INDIVIDUAL

training_context
# FORMAL_OSK
# SUPPLEMENTARY_OSK
# INDIVIDUAL

target_entitlement_type
target_category nullable

documentation_mode nullable
# PAPER
# ELECTRONIC

internal_theory_exam_handling_mode
# IN_PLATFORM
# EXTERNAL_OSK
# NOT_MANAGED

internal_practical_exam_handling_mode
# IN_PLATFORM
# EXTERNAL_OSK
# NOT_MANAGED

documentation_mode_selected_at nullable
documentation_mode_selected_by_user_id nullable
documentation_mode_locked_at nullable

learning_health
formal_state

lead_instructor_staff_member_id nullable
theory_supervisor_staff_member_id nullable

started_at nullable
completed_theory_at nullable
formal_started_at nullable

created_at
updated_at
```

## 6.3. StudentCourseAccess

Prawo technicznego dostępu do aplikacji.

```text
student_course_accesses

id
course_enrollment_id
status
# PENDING
# ACTIVE
# EXPIRED
# CANCELLED

issued_at
activated_at nullable
expires_at nullable

payer_type
# ORGANIZATION
# STUDENT

created_at
updated_at
```

Wygaśnięcie Access:

```text
NIE usuwa:
- CourseEnrollment,
- postępu,
- odpowiedzi,
- dokumentacji,
- historii.
```

---

# 7. Billing i aktywacje

## 7.1. Pakiet startowy

Nowe OSK:

```text
5 darmowych dostępów
```

Niewykorzystane sloty w V1 nie wygasają.

## 7.2. Reserve → consume → release

Przy dodaniu kursanta:

```text
slot AVAILABLE
↓
RESERVED
```

Po aktywacji:

```text
RESERVED
↓
CONSUMED
```

Po anulowaniu przed aktywacją:

```text
RESERVED
↓
RELEASED
```

## 7.3. Ledger

Nie opierać billing source-of-truth na jednym liczniku.

```text
organization_credit_transactions

id
organization_id
type
# GRANT
# PURCHASE
# RESERVE
# CONSUME
# RELEASE
# ADJUSTMENT

quantity
course_enrollment_id nullable
metadata_json
created_at
```

## 7.4. 90 dni

```text
activated_at
↓
expires_at = activated_at + 90 dni
```

Nie liczyć od utworzenia kursanta.

Przedłużenie V1:

```text
+30 dni
```

OSK może zezwolić na samodzielne przedłużanie przez kursanta.

---

# 8. Dodanie i aktywacja kursanta

Minimalne dane onboardingowe:

```text
imię
nazwisko
e-mail lub telefon
docelowa kategoria / uprawnienie
```

Flow:

```text
ORG_OWNER / ORG_ADMIN
↓
Dodaj kursanta
↓
find/create User
↓
create CourseEnrollment
↓
freeze CourseVersion
↓
reserve access
↓
create PENDING access
↓
generate activation token + QR
↓
student activates
↓
sets own password
↓
token invalidated
↓
access ACTIVE / 90 days
```

Token:

- losowy,
- jednorazowy,
- odwoływalny,
- bez PII w URL,
- poprzedni token unieważniany po wygenerowaniu nowego.

ORG_OWNER/ORG_ADMIN nigdy nie widzi aktualnego hasła kursanta.

SMS nie jest potrzebny w V1.

---

# 9. CourseVersion i content immutability

```text
courses
course_versions
modules
lessons
lesson_steps
module_assessments
```

Opublikowany `CourseVersion`:

- jest immutable dla aktywnych enrollmentów,
- posiada snapshot,
- posiada `content_hash`,
- posiada `legal_requirements_version`,
- nie jest podmieniany aktywnemu kursantowi po nowej publikacji.

---

# 10. Learning Engine

## 10.1. Jeden silnik

Ten sam runtime działa dla:

```text
ORGANIZATION / FORMAL_OSK
ORGANIZATION / SUPPLEMENTARY_OSK
INDIVIDUAL
```

Różnica znajduje się w `EnrollmentRequirements` i formalnym znaczeniu wyniku, nie w silniku UI.

## 10.2. Hierarchia

```text
CourseVersion
↓
Module
↓
Lesson
↓
Step
↓
ModuleAssessment
```

## 10.3. Step types

Minimum:

```text
content
image
video
question
scenario
summary
```

Purpose:

```text
HOOK
TEACH
DEMO
PRACTICE
EXAM
CHECK
SUMMARY
```

## 10.4. Vue

Jeden renderer:

```text
LessonPlayer.vue
↓
StepRenderer.vue
↓
step.type
```

Nie tworzyć komponentów per konkretna lekcja.

## 10.5. Laravel source of truth

Backend decyduje:

- który krok jest dostępny,
- czy odpowiedź jest poprawna,
- czy lekcja jest ukończona,
- czy assessment został zaliczony,
- czy kolejny moduł jest dostępny.

---

# 11. ModuleAssessment

`ModuleAssessment`:

- kończy wymagany moduł tematyczny,
- ma wersjonowane reguły,
- posiada immutable attempt snapshot,
- `FAIL` nie odblokowuje następnego wymaganego modułu,
- `PASS` może odblokować kolejny moduł.

Nie jest egzaminem wewnętrznym.

```text
module_assessment_attempts

id
module_assessment_id
course_enrollment_id
attempt_number

score
passed
status

assessment_version
question_set_snapshot_json
passing_rule_snapshot_json
content_hash

started_at
submitted_at
created_at
```

---

# 12. Formalny czas teorii — CANONICAL

## 12.1. Najważniejsza korekta

**Formalnego minimalnego czasu nie przypisujemy jako niezmiennego prawnego minimum do każdego modułu.**

`CourseVersion` definiuje:

```text
wymagany zakres
kolejność
granice modułów
assessmenty
```

`EnrollmentRequirements` definiuje:

```text
łączny wymagany czas formalnej teorii dla konkretnego enrollmentu
```

Canonical:

```text
EnrollmentRequirements.required_theory_seconds
```

Pole w module może istnieć wyłącznie jako:

```text
estimated_time_seconds
recommended_time_seconds
```

dla UX / planowania, ale nie jako niezależny formalny blocker prawny.

## 12.2. TheoryCompletionGate

Formalna teoria jest gotowa dopiero, gdy:

```text
all_required_modules_completed == true
AND
all_required_module_assessments_passed == true
AND
counted_theory_seconds >= EnrollmentRequirements.required_theory_seconds
```

## 12.3. Kursant skończył zakres za szybko

Jeżeli:

```text
required scope = complete
but
counted time < required_theory_seconds
```

system NIE każe kursantowi „czekać z otwartą stroną”.

Uruchamia:

```text
TIME_COMPLETION_REVIEW
```

czyli kontrolowany review / reinforcement z już opublikowanego contentu do momentu osiągnięcia wymaganego czasu.

Nie budować sztucznego timera bez aktywnej nauki.

---

# 13. Time Evidence

## 13.1. Source of truth

```text
learning_sessions
```

Każda zakończona sesja jest historycznym rekordem źródłowym.

## 13.2. Time policy

```text
time_policies

version
heartbeat_seconds
disconnect_grace_seconds
max_continuous_session_seconds
overlap_strategy
policy_json
valid_from
valid_to nullable
```

V1:

```text
heartbeat_seconds = 60
disconnect_grace_seconds = 60
max_continuous_session_seconds = 10800  # 3 h
overlap_strategy = UNION
```

Limit 3 h:

- nie jest prawnym limitem dziennym,
- służy jakości evidence,
- po limicie sesja zamyka się,
- nie uruchamia się automatycznie nowa,
- kursant musi świadomie wybrać `Kontynuuj`.

## 13.3. Wiele urządzeń

Nakładające się przedziały liczymy jako union.

```text
10:00–11:00 urządzenie A
10:30–11:30 urządzenie B

counted = 90 min
not 120 min
```

## 13.4. Czego nie robimy

Nie implementować jako wymogu:

```text
camera
mouse tracking
keyboard tracking
focus tracking
idle guessing
CAPTCHA attention checks
heurystyki "za szybka odpowiedź = oszustwo"
```

## 13.5. Film ×2

Liczymy rzeczywisty czas upływający w sesji, nie nominalną długość materiału.

## 13.6. Evidence validator

Przed formalnym raportem:

```text
TimeEvidenceValidator
```

sprawdza m.in.:

- brak niemożliwych interwałów,
- overlap union,
- limity sesji,
- spójność agregatu,
- wersję polityki czasu.

---

# 14. System Evidence ≠ dane przyjęte przez OSK

Trzy warstwy:

```text
A. System Evidence
B. Instructor Documentation Draft
C. Formal Documentation
```

Przykład:

```text
System observed: 47 h 18 min
Required formal: 26 godz. szkoleniowych
OSK accepted: 26 godz. szkoleniowych
```

Nie zmieniamy 47 h 18 min.

Przechowywać:

```text
observed_learning_seconds
required_training_seconds
osk_accepted_training_seconds
```

Instruktor może zmniejszyć czas przyjmowany do dokumentacji, ale:

```text
accepted_total >= required_total
```

---

# 15. EnrollmentFacts — fakty wejściowe

Nie przechowujemy „zwolniony z teorii” jako ręcznego faktu.

Przechowujemy to, co wiemy o osobie.

```text
course_enrollment_facts

id
course_enrollment_id

target_entitlement_type
# CATEGORY
# B_CODE_96
# REMOVE_AUTOMATIC_RESTRICTION

target_category nullable

held_categories_json
held_category_restrictions_json

positive_state_exam_results_json
recognized_state_theory_results_json

parallel_training_enrollment_ids_json
parallel_training_same_provider
parallel_training_started_together
parallel_training_completed_before_first_state_exam

previous_training_credits_json

provider_context
# COMMERCIAL_OSK
# SCHOOL
# MILITARY
# UNIFORMED_SERVICE

facts_source
# PKK
# CEK
# OSK_VERIFIED
# MANUAL_WITH_EVIDENCE

verified_by_user_id nullable
verified_at nullable

facts_version
created_at
updated_at
```

PKK jest źródłem / pomocniczą referencją, nie bramką Learning Engine.

---

# 16. EnrollmentRequirementsResolver

## 16.1. Wejście

```text
EnrollmentFacts
+
LegalRequirementsRegistry
```

## 16.2. Wyjście

```text
course_enrollment_requirements

id
course_enrollment_id

requirements_version
legal_rules_version

basic_training_required

formal_theory_training_required
required_theory_seconds

state_theory_exam_required
internal_theory_exam_required

first_aid_required
required_first_aid_seconds nullable

practical_training_required
required_practical_seconds

internal_practical_exam_required

resolved_from_facts_version
resolved_at

created_at
```

## 16.3. RequirementAdjustments

Jeden enrollment może posiadać wiele równoczesnych zmian.

```text
course_enrollment_requirement_adjustments

id
course_enrollment_requirement_id

legal_rule_id
legal_rules_version

dimension
# BASIC_TRAINING
# THEORY_TRAINING
# THEORY_SECONDS
# FIRST_AID
# PRACTICAL_TRAINING
# PRACTICAL_SECONDS
# INTERNAL_THEORY_EXAM
# INTERNAL_PRACTICAL_EXAM
# STATE_THEORY_EXAM
# ENTITLEMENT

operation
# WAIVE
# DEEM_COMPLETED
# CREDIT
# SUBTRACT_SECONDS
# SET_SECONDS
# DEEM_POSITIVE_RESULT
# ALREADY_ENTITLED
# NOT_APPLICABLE

value_seconds nullable
basis_fact_snapshot_json

priority
stacking_group nullable
stacking_policy nullable

created_at
```

Nie używać pojedynczego `exemption_code` jako canonical modelu.

---

# 17. Trzy resolvery — nie mieszać

## 17.1. EnrollmentRequirementsResolver

Odpowiada:

> Co OSK musi przeprowadzić dla tego konkretnego enrollmentu?

## 17.2. EntitlementEquivalenceResolver

Odpowiada:

> Czy posiadane już uprawnienia obejmują docelowe uprawnienie bez nowego formalnego kursu?

## 17.3. StateExamEligibilityResolver

Odpowiada:

> Do jakiego egzaminu państwowego osoba może przystąpić na podstawie posiadanych uprawnień, wyników lub ukończonego szkolenia?

---

# 18. Legal Exception Matrix — canonical summary

Reguły są wersjonowane w:

```text
LegalRequirementsRegistry
```

Snapshot:

```text
legal_rules_version = PL_DRIVING_OSK_2026_08_24
```

## 18.1. Bazowa teoria

```text
AM       5 h
A1      26 h
A2      26 h
A       26 h
B1      26 h
B       26 h
T       26 h
C1      20 h
C       20 h
D1      20 h
D       20 h
```

## 18.2. Kategorie +E

```text
B+E
C1+E
C+E
D1+E
D+E
```

Bazowo:

```text
formal_theory_training_required = false
required_theory_seconds = 0
state_theory_exam_required = false
internal_theory_exam_required = false

practical_training_required = true
internal_practical_exam_required = true
```

## 18.3. Bazowa praktyka

```text
AM       5 h
A1      20 h
A2      20 h
A       20 h
B1      30 h
B       30 h
B+E     15 h
C1      20 h
C1+E    20 h
C       30 h
C+E     25 h
D1      30 h
D1+E    20 h
D       60 h
D+E     25 h
T       20 h
```

## 18.4. Uznanie teorii / przejścia

Canonical matrix:

```text
A1 → A2
A1 → A
A2 → A
B1 → B
C1 → C
D1 → D
```

Właściwe posiadane uprawnienie lub uznany pozytywny państwowy wynik może uruchamiać:

```text
formal_theory_training_required = false
required_theory_seconds = 0
state_theory_exam_required = false
internal_theory_exam_required = false
```

zgodnie z wersjonowaną regułą prawną.

## 18.5. Art. 23a — pozytywny wynik państwowej teorii

Jeżeli dla docelowego uprawnienia istnieje prawnie uznany pozytywny wynik państwowej teorii:

```text
formal_theory_training_required = false
required_theory_seconds = 0
internal_theory_exam_required = false
```

Learning Engine pozostaje opcjonalnie dostępny:

```text
SUPPLEMENTARY / OPTIONAL
```

## 18.6. Redukcje praktyki

Canonical przykłady:

```text
A1 held → A2:
20 h → 10 h

A2 held → A:
20 h → 10 h

B1 held → B:
30 h → 20 h
```

Redukcje C/C1/D/D1 są przechowywane jako jawne rule rows, nie rozproszone `if`.

## 18.7. Pierwsza pomoc z B

Dla właściwych kategorii grup B+E/C/D, gdy osoba posiada B lub spełnia warunki równoległego B:

```text
first_aid_required = false
first_aid_credit_source = CATEGORY_B
```

## 18.8. Kod 96

```text
target_entitlement_type = B_CODE_96
```

Nie modelować jako `B+E`.

## 18.9. Usunięcie ograniczenia automatycznej skrzyni

```text
target_entitlement_type = REMOVE_AUTOMATIC_RESTRICTION
```

Nie tworzyć ponownie pełnego podstawowego kursu teorii, jeżeli prawo uznaje wcześniejszy zakres.

## 18.10. Ekwiwalencje posiadanych uprawnień

Przykłady obsługiwane przez `EntitlementEquivalenceResolver`:

```text
B + C1+E → zakres B+E
B + D1+E → zakres B+E
B + C+E  → zakres B+E
B + D+E  → zakres B+E

C+E + D → zakres D+E
```

---

# 19. Szkolenia równoległe

Nie wystarczy „dwa aktywne enrollmenty”.

Fakty:

```text
parallel_training_same_provider
parallel_training_started_together
parallel_training_completed_before_first_state_exam
```

Reguły mogą redukować:

```text
THEORY_SECONDS
PRACTICAL_SECONDS
FIRST_AID
```

## 19.1. Stacking

Resolver nie sumuje redukcji automatycznie.

```text
stacking_policy:
STACK
MAX_ONLY
FIRST_MATCH
MUTUALLY_EXCLUSIVE
LEGAL_REVIEW_REQUIRED
```

Jeżeli reguła jest niejednoznaczna:

```text
do not guess
↓
LegalReviewFlag
```

---

# 20. LegalReviewFlags

```text
legal_review_flags

id
course_enrollment_id nullable

rule_code
severity
status
# OPEN
# RESOLVED

resolution nullable
resolved_by nullable
resolved_at nullable
```

P0:

```text
PARALLEL_THEORY_WITH_NO_THEORY_CATEGORY
MULTIPLE_PRACTICAL_REDUCTIONS_STACKING
CATEGORY_EQUIVALENCE_EDGE_CASE
INTERNAL_THEORY_FAILED_ATTEMPT_RETENTION
```

---

# 21. FormalReadiness

`FormalReadiness` odpowiada:

> Czy PrawkoNaRaz może wykonać konkretną formalną czynność / wygenerować formalne potwierdzenie?

Nie odpowiada:

> Czy OSK może użyć funkcji aplikacji?

Canonical:

```text
FormalReadiness
        X
InternalTheoryTestEngine.canLaunch()
```

Formal blocker zawsze sprawdza odpowiednie pole `EnrollmentRequirements`.

Przykład:

```text
if requirements.internal_theory_exam_required:
    require_theory_internal_pass_for_formal_completion
else:
    skip_requirement
```

---


# 21A. Obsługa egzaminów wewnętrznych przez PrawkoNaRaz — CANONICAL V2.6

## 21A.1. Dlaczego potrzebujemy osobnego ustawienia

W praktyce rynkowej część OSK chce prowadzić egzaminy wewnętrzne bezpośrednio w PrawkoNaRaz, część wykonuje je własnym dotychczasowym sposobem, a część nie chce, aby PrawkoNaRaz w ogóle zarządzało tym procesem.

Nie należy z tego robić wyjątku prawnego.

Rozdzielamy:

```text
CZY EGZAMIN JEST FORMALNIE WYMAGANY?
        ↓
EnrollmentRequirements

od

CZY PRAWKONARAZ MA OBSŁUGIWAĆ TEN EGZAMIN?
        ↓
InternalExamHandlingMode
```

`EnrollmentRequirements` jest wynikiem prawa.

`InternalExamHandlingMode` jest wyborem produktowym / operacyjnym OSK.

## 21A.2. Tryby obsługi

Dla teorii i praktyki niezależnie:

```text
IN_PLATFORM
EXTERNAL_OSK
NOT_MANAGED
```

### `IN_PLATFORM`

OSK korzysta z pełnego modułu PrawkoNaRaz.

```text
test / arkusz
↓
wynik
↓
dokumentacja
↓
audit
```

### `EXTERNAL_OSK`

OSK przeprowadza egzamin własnym sposobem poza PrawkoNaRaz.

PrawkoNaRaz:

- nie wymusza użycia własnego silnika,
- nie uruchamia własnego formalnego testu / arkusza,
- może przyjąć minimalny wynik / dane potrzebne do dokumentacji,
- oznacza źródło jako `OSK_EXTERNAL`.

Przykład:

```text
Egzamin wewnętrzny — teoria
Obsługa: poza PrawkoNaRaz

[Odnotuj wynik]
```

### `NOT_MANAGED`

PrawkoNaRaz w ogóle nie zarządza tym etapem.

System:

- nie pokazuje egzaminu jako obowiązkowej czynności produktowej,
- nie blokuje Learning Engine,
- nie blokuje dostępu kursanta,
- nie blokuje Evidence Pack teorii,
- nie zmusza OSK do wprowadzania wyniku.

Jednocześnie, jeżeli `EnrollmentRequirements` wskazuje, że egzamin jest formalnie wymagany:

```text
PrawkoNaRaz NIE może oznaczyć:
FULL_FORMAL_TRAINING_COMPLETED
```

na podstawie własnych danych, ponieważ nie posiada dowodu tego etapu.

Może natomiast poprawnie oznaczyć:

```text
PRAWKONARAZ_SCOPE_COMPLETED
```

oraz wygenerować dokumenty dotyczące zakresu, który rzeczywiście obsługuje.

## 21A.3. Ustawienie organizacji

OSK może wybrać domyślny sposób pracy:

```text
organizations.default_internal_theory_exam_handling_mode
organizations.default_internal_practical_exam_handling_mode
```

UX:

```text
Egzaminy wewnętrzne

Teoria:
(●) W PrawkoNaRaz
( ) Poza PrawkoNaRaz
( ) Nie zarządzaj w PrawkoNaRaz

Praktyka:
(●) W PrawkoNaRaz
( ) Poza PrawkoNaRaz
( ) Nie zarządzaj w PrawkoNaRaz
```

Nie używać etykiety:

```text
"Egzamin niewymagany"
```

jako ustawienia OSK.

`NIEWYMAGANY` może wynikać wyłącznie z `EnrollmentRequirements`.

## 21A.4. Snapshot na enrollment

Domyślne ustawienia organizacji są kopiowane na nowy `CourseEnrollment`.

```text
internal_theory_exam_handling_mode
internal_practical_exam_handling_mode
```

Zmiana ustawień organizacji nie zmienia historycznych enrollmentów.

Tryb danego egzaminu można zmienić przed powstaniem jego formalnego rekordu.

Po zatwierdzeniu formalnego wyniku:

```text
handling_mode_locked_at
```

dla danego komponentu.

## 21A.5. Macierz zachowania

| Formal requirement | Handling mode | Zachowanie |
|---|---|---|
| REQUIRED | IN_PLATFORM | pełny workflow PrawkoNaRaz |
| REQUIRED | EXTERNAL_OSK | OSK robi poza systemem; można odnotować wynik |
| REQUIRED | NOT_MANAGED | PrawkoNaRaz nie zarządza; brak claimu pełnego formalnego ukończenia |
| NOT_REQUIRED | IN_PLATFORM | można uruchomić tylko jako DIAGNOSTIC / pomocniczy workflow |
| NOT_REQUIRED | EXTERNAL_OSK | brak formalnego znaczenia dla PrawkoNaRaz |
| NOT_REQUIRED | NOT_MANAGED | nic nie pokazujemy |

## 21A.6. Brak blokady nauki

Żaden z trybów:

```text
IN_PLATFORM
EXTERNAL_OSK
NOT_MANAGED
```

nie może wpływać na możliwość korzystania z Learning Engine.

W szczególności:

```text
internal_exam_handling_mode != IN_PLATFORM
```

NIE oznacza:

```text
disable_course
disable_learning
disable_evidence
disable_access
```

## 21A.7. Formal Completion Scope

Dodać jawne rozróżnienie:

```text
platform_scope_status
# IN_PROGRESS
# COMPLETED

formal_training_status
# NOT_APPLICABLE
# INCOMPLETE
# READY
# COMPLETED
# UNVERIFIED_EXTERNAL_COMPONENTS
```

Jeżeli wymagany egzamin ma:

```text
handling_mode = NOT_MANAGED
```

wtedy po wykonaniu pozostałych elementów:

```text
platform_scope_status = COMPLETED
formal_training_status = UNVERIFIED_EXTERNAL_COMPONENTS
```

To nie jest komunikat ostrzegawczy dla kursanta.

To wewnętrzny status PrawkoNaRaz mówiący:

> platforma zakończyła swój zakres, ale nie posiada danych pozwalających potwierdzić cały formalny proces OSK.

## 21A.8. EXTERNAL_OSK — minimalne odnotowanie wyniku

Teoria:

```text
internal_theory_exam_results

source = OSK_EXTERNAL
course_enrollment_id
result = PASS
passed_at
conducted_by_staff_member_id nullable
score nullable
max_score nullable
error_count nullable
```

Nie wymyślać danych, których OSK nie podało.

Praktyka:

```text
internal_practical_exam_external_records

id
course_enrollment_id

exam_date
result
# PASS / FAIL

examiner_staff_member_id nullable
document_reference nullable
attachment_id nullable

source = OSK_EXTERNAL
recorded_by_user_id
recorded_at
```

Załącznik może być opcjonalny w produkcie PAPER, chyba że konkretny obowiązek prawny / polityka compliance wymaga jego posiadania przez platformę do określonego claimu.

## 21A.9. Profil kursanta OSK

### IN_PLATFORM

```text
Egzamin wewnętrzny — teoria
○ Do przeprowadzenia
[Uruchom egzamin]
```

### EXTERNAL_OSK

```text
Egzamin wewnętrzny — teoria
Obsługiwany przez OSK poza PrawkoNaRaz
[Odnotuj wynik]
```

### NOT_MANAGED

Domyślnie nie pokazywać dużego modułu egzaminacyjnego.

W szczegółach:

```text
Egzamin wewnętrzny
PrawkoNaRaz nie zarządza tym etapem.
```

## 21A.10. Najważniejsza reguła

> **PrawkoNaRaz nie zmusza OSK do przeprowadzania egzaminu w naszej aplikacji.**

> **Jednocześnie wybór „nie zarządzaj” nie zmienia prawa i nie może automatycznie zmienić `internal_*_exam_required` na `false`.**

> **Formalny wymóg wynika z resolvera; sposób obsługi wynika z preferencji OSK.**

---

# 22. Egzamin wewnętrzny — teoria

## 22.1. Osobny silnik

```text
Learning Engine
      X
InternalTheoryExamEngine
```

Nie sprawdza przed uruchomieniem:

```text
course completion
theory percentage
required theory time
module completion
module assessments
practical hours
first aid
```

OSK decyduje operacyjnie, kiedy udostępnić test.

## 22.2. Formal vs diagnostic

Silnik testowy:

```text
InternalTheoryTestEngine
```

Purpose:

```text
FORMAL_INTERNAL
DIAGNOSTIC
```

### FORMAL_INTERNAL

Stosować, gdy formalny egzamin teoretyczny ma znaczenie dla enrollmentu.

### DIAGNOSTIC

Dostępny na decyzję OSK również wtedy, gdy:

```text
internal_theory_exam_required = false
```

Przykład `C+E`:

```text
formal theory exam = NOT REQUIRED
diagnostic test = AVAILABLE
```

DIAGNOSTIC nie tworzy formalnego wyniku egzaminu wewnętrznego.

## 22.3. Próby

Kursant ma nieograniczoną liczbę prób.

```text
FAIL
↓
pokaż wynik sesji
↓
[Spróbuj ponownie]
↓
nowa próba
```

Brak cooldownu.

## 22.4. FAIL — przyjęta decyzja biznesowa

W bieżącym produkcie:

```text
FAIL
↓
session discarded
↓
brak trwałej historii nieudanych prób
```

Dane sesji mogą tymczasowo istnieć do policzenia wyniku.

Nie tworzyć:

```text
failed_internal_theory_exam_history
```

**Status prawny:** `LEGAL_REVIEW_REQUIRED` przed oznaczeniem finalnej formalnej zgodności.

## 22.5. PASS

Dopiero pozytywny wynik tworzy trwały rekord:

```text
internal_theory_exam_results

id
course_enrollment_id

result
# PASS

score nullable
max_score nullable
error_count nullable

passed_at

conducted_by_staff_member_id

rules_version
question_set_snapshot_hash nullable

source
# PRAWKONARAZ
# OSK_EXTERNAL
# OSK_MANUAL

created_at
```

Wynik należy do `CourseEnrollment`, nie globalnego `User`.

## 22.6. Ręczny PASS OSK

OSK może odnotować pozytywny wynik uzyskany poza silnikiem PrawkoNaRaz:

```text
source = OSK_MANUAL
```

Nie wymyślamy punktacji, której OSK nie podało.

## 22.7. Reguły samego egzaminu

Nie hardkodować reguł w kontrolerze.

```text
internal_theory_exam_rule_versions

id
category
version

question_count
basic_question_count nullable
specialist_question_count nullable

duration_seconds
max_score
passing_score

scoring_rules_json
timing_rules_json
question_selection_rules_json

legal_source
effective_from
effective_to nullable

rules_hash
```

Attempt / PASS zachowuje wersję reguł.

**P0 przed wdrożeniem:** zasilić tę tabelę aktualnymi parametrami wynikającymi z właściwych przepisów egzaminacyjnych.

---



---

# 22A. Browser Exam Station — canonical SaaS workflow

## 22A.0. Scope

Ten subsystem dotyczy teoretycznego egzaminu wykonywanego w PrawkoNaRaz:

```text
internal_theory_exam_handling_mode = IN_PLATFORM
```

Nie zmienia zachowania:

```text
EXTERNAL_OSK
NOT_MANAGED
```

i nie zmienia `EnrollmentRequirements`.

Jeżeli formalny egzamin teoretyczny nie jest wymagany, ten sam runtime może zostać uruchomiony wyłącznie jako:

```text
purpose = DIAGNOSTIC
```

zgodnie z zasadami `InternalTheoryTestEngine`.

## 22A.1. Cel UX

PrawkoNaRaz działa przez Internet i zwykłą przeglądarkę Chrome, Edge, Firefox lub inną wspieraną przeglądarkę.

Nie wymagamy:

```text
instalacji aplikacji desktopowej
PWA
lokalnego serwera OSK
VPN
specjalnego hardware
```

Dla typowego małego OSK:

```text
ORG_ADMIN: 1 klik
STUDENT:   1 klik
INSTRUCTOR w PAPER: 0 klików w aplikacji
```

Normalny przebieg:

```text
kursant przychodzi
↓
ORG_ADMIN otwiera kursanta
↓
[▶ Egzamin teraz]
↓
druga przeglądarka automatycznie pokazuje gotowy egzamin
↓
kursant siada
↓
[Rozpocznij egzamin]
```

Nie wymagamy od kursanta:

```text
login
hasło
PIN
kod egzaminu
QR
PESEL
wyboru kursanta
wyboru kategorii
```

## 22A.2. Dwa komputery / dwie przeglądarki

### Browser A — panel organizacji

Zalogowany:

```text
OrganizationUser
role = ORG_OWNER | ORG_ADMIN
```

### Browser B — stanowisko egzaminacyjne

Otwarta strona:

```text
/egzamin
```

Działa w ograniczonym:

```text
BrowserExamStationMode
```

Nie jest panelem administracyjnym i nie posiada permissions organizacyjnych.

## 22A.3. Logical station ≠ physical computer

Canonical:

```text
ExamStation
≠ komputer
≠ fingerprint sprzętu

ExamStation
= logiczne stanowisko organizacji

ExamStationBrowserRegistration
= aktualnie autoryzowana przeglądarka obsługująca stanowisko
```

Dzięki temu awaria lub wymiana komputera nie wymaga tworzenia nowego formalnego stanowiska.

## 22A.4. Logical station model

```text
exam_stations

id
organization_id
name

status
# ACTIVE
# DISABLED

is_default
current_exam_session_id nullable

created_at
updated_at
```

Nie używać identyfikatorów sprzętowych ani fingerprintu komputera jako canonical identity stanowiska.

## 22A.5. Browser registration

```text
exam_station_browser_registrations
exam_station_runtime_leases

id
exam_station_id

credential_hash

status
# ACTIVE
# REVOKED

registered_by_user_id
registered_at

last_seen_at nullable
revoked_at nullable

created_at
updated_at
```

Invariant:

```text
max 1 ACTIVE browser registration
per exam_station
```

Credential:

- losowy i wysokiej entropii,
- przechowywany po stronie serwera jako hash,
- przekazywany przeglądarce jako bezpieczne poświadczenie stanowiska,
- preferowany `HttpOnly + Secure + SameSite` cookie,
- nie zawiera danych kursanta,
- nie nadaje dostępu do admin API.

## 22A.6. Pierwsza konfiguracja przeglądarki

Tylko raz dla danej przeglądarki:

```text
/egzamin
↓
[Skonfiguruj stanowisko]
↓
ORG_OWNER / ORG_ADMIN uwierzytelnia konfigurację
↓
wybiera lub tworzy logical ExamStation
↓
browser registration ACTIVE
↓
sesja administracyjna zostaje zakończona
↓
BrowserExamStationMode
```

Ekran końcowy:

```text
Stanowisko egzaminacyjne

● GOTOWE

Oczekiwanie na egzamin...
```

Nie wymagamy ponownego logowania przed każdym egzaminem.

## 22A.7. One-click exam launch

Jeżeli OSK ma:

```text
1 aktywne / domyślne stanowisko
+
domyślnego kwalifikowanego prowadzącego
```

profil kursanta pokazuje:

```text
Jan Kowalski
Kategoria B

Egzamin wewnętrzny — teoria
Do przeprowadzenia

[▶ Egzamin teraz]
```

Kliknięcie wykonuje atomowo:

```text
resolve exam requirements
↓
resolve default qualified staff member
↓
resolve free default exam station
↓
create InternalTheoryExamSession
↓
assign session to ExamStation
↓
commit DB transaction
↓
publish EXAM_ASSIGNED event
```

Nie pokazujemy formularza konfiguracji w normalnym przypadku.

## 22A.8. Exception-only configuration

Jeżeli system nie może wykonać bezpiecznego wyboru automatycznie, dopiero wtedy pokazuje minimalny wybór.

Przykłady:

```text
brak domyślnego prowadzącego
więcej niż jedno wolne stanowisko bez default
domyślne stanowisko zajęte
prowadzący nie ma kwalifikacji dla kategorii
```

UX:

```text
Prowadzący
[Adam Nowak ▼]

Stanowisko
[Stanowisko 2 ▼]

[Uruchom]
```

Złożoność pojawia się tylko w wyjątku.

## 22A.9. Automatyczna zmiana drugiej przeglądarki

Po `EXAM_ASSIGNED` stanowisko automatycznie przechodzi z:

```text
● GOTOWE
Oczekiwanie na egzamin...
```

do:

```text
Egzamin wewnętrzny — teoria

Jan Kowalski
Kategoria B

[Rozpocznij egzamin]
```

Pracownik organizacji nie chodzi do drugiego pomieszczenia i nie odświeża strony.

## 22A.10. Server source of truth

Canonical:

```text
Browser UI
= presentation + input

Laravel + DB
= source of truth dla stanu egzaminu
```

Serwer przechowuje:

```text
session
status
rules_version
course_enrollment_id
exam_station_id
formal conductor
start / finish timestamps
accepted answers
result
```

Czas egzaminu jest autorytatywnie liczony na podstawie server-side timestamps i `rules_version`.

Timer w przeglądarce jest wyłącznie prezentacją stanu serwera.

Odświeżenie Chrome nie może zgubić egzaminu.

Po reconnect:

```text
GET current station state
↓
restore correct screen
```

## 22A.11. HTTP API vs realtime

HTTP API obsługuje właściwe dane i komendy:

```text
prepare / assign exam
fetch current exam state
start exam
fetch question snapshot
submit answer
finish exam
fetch result
release station
```

Realtime służy wyłącznie do lekkich sygnałów:

```text
EXAM_ASSIGNED
EXAM_STARTED
EXAM_FINISHED
STATION_RELEASED
STATION_REPLACED
```

Nie przesyłamy całego stanu aplikacji przez WebSocket.

## 22A.12. Realtime transport

Preferowany:

```text
WebSocket
```

lub równoważny mechanizm realtime.

Przykładowo w stacku Laravel:

```text
Laravel Reverb / compatible realtime transport
```

Stanowisko subskrybuje wyłącznie prywatny kanał odpowiadający jego `exam_station_id`.

## 22A.13. Fallback polling

Polling jest fallbackiem, nie normalnym mechanizmem.

```text
realtime connected
→ brak polling

realtime disconnected
→ lightweight polling co kilka sekund
→ reconnect z backoff
→ po odzyskaniu realtime polling OFF
```

Nie stosować:

```text
sub-second polling
ciągłego odpytywania DB podczas poprawnego realtime
```

Można użyć jitter/backoff, aby wiele stanowisk nie odpytywało serwera jednocześnie.

## 22A.14. Server-load policy

Idle Browser Exam Station ma być bardzo lekkie.

Canonical rules:

1. Otwarte stanowisko nie wykonuje stałych ciężkich zapytań.
2. WebSocket utrzymuje połączenie, ale brak eventów = praktycznie brak payloadu aplikacyjnego.
3. Keepalive transportu nie oznacza zapisu do DB przy każdym ping.
4. `last_seen_at` aktualizować co najwyżej okresowo, np. raz na 60 s, a nie przy każdym frame.
5. Po `EXAM_ASSIGNED` stanowisko robi pojedynczy fetch aktualnego state.
6. Odpowiedź egzaminacyjna jest zapisywana po stronie serwera po zaakceptowaniu.
7. Media pytań powinny być serwowane jako statyczne assety / storage / CDN, a nie streamowane przez proces PHP/Laravel.
8. Realtime może być skalowany niezależnie od głównego HTTP API.

## 22A.15. InternalTheoryExamSession

```text
internal_theory_exam_sessions

id
course_enrollment_id
exam_station_id

purpose
# FORMAL_INTERNAL
# DIAGNOSTIC

status
# PREPARED
# IN_PROGRESS
# INTERRUPTED
# FINISHED
# CANCELLED

created_by_user_id
conducted_by_staff_member_id

rules_version_id

prepared_at
started_at nullable
interrupted_at nullable
finished_at nullable
cancelled_at nullable

created_at
updated_at
```

Question/answer state pozostaje po stronie serwera lub w osobnych attempt-state records.

Aktualna decyzja o braku trwałego zapisu `FAIL` pozostaje bez zmian:

```text
FAIL
→ pokaż wynik sesji
→ po zakończeniu lifecycle zastosuj bieżącą politykę retention FAIL
```

`INTERRUPTED` nie jest automatycznie `FAIL`.

## 22A.16. Exam start

Kursant widzi wyłącznie przygotowaną dla niego sesję:

```text
Jan Kowalski
Kategoria B

[Rozpocznij egzamin]
```

Po kliknięciu:

```text
PREPARED
↓
IN_PROGRESS
```

Nie można po starcie:

- zmienić kursanta,
- zmienić kategorii,
- zmienić formalnego prowadzącego,
- przejść do panelu administracyjnego.

## 22A.17. Admin live status

Panel `ORG_OWNER` / `ORG_ADMIN` może pokazywać:

```text
Stanowisko egzaminacyjne 1
● ONLINE

Jan Kowalski
○ OCZEKUJE NA ROZPOCZĘCIE
```

po starcie:

```text
● EGZAMIN W TRAKCIE
Rozpoczęto: 16:03
```

po zakończeniu:

```text
✅ ZAKOŃCZONY
POZYTYWNY
70 / 74

[Przygotuj dokument]
```

Panel administracyjny nie potrzebuje podglądu aktualnie zaznaczanych odpowiedzi kursanta.

## 22A.18. Zakończenie i zwolnienie stanowiska

Po zakończeniu stanowisko pokazuje:

```text
Egzamin zakończony

Wynik:
POZYTYWNY

Proszę zgłosić się do obsługi OSK.
```

Następnie stanowisko wraca do:

```text
● GOTOWE
Oczekiwanie na egzamin...
```

Automatyczne zwolnienie może nastąpić dopiero po bezpiecznym zapisaniu wyniku.

## 22A.19. Awaria przeglądarki / odświeżenie

Jeżeli Chrome zostanie odświeżony:

```text
browser reconnect
↓
authenticate ExamStationCredential
↓
GET current state
↓
restore PREPARED / IN_PROGRESS / result screen
```

Nie tworzymy nowej sesji egzaminacyjnej.

## 22A.20. Awaria komputera przed startem

Jeżeli komputer padnie przy `PREPARED`:

```text
exam session remains server-side
```

Po podłączeniu innej przeglądarki do tego samego logical station można przypisać istniejącą sesję i kontynuować przygotowanie.

## 22A.21. Awaria w trakcie egzaminu

Jeżeli stanowisko traci połączenie w `IN_PROGRESS`:

```text
IN_PROGRESS
↓
INTERRUPTED
```

System:

- zachowuje zaakceptowane odpowiedzi,
- zachowuje server-side timestamps,
- nie wylicza automatycznie `FAIL`,
- pokazuje `ORG_ADMIN` procedurę awaryjną,
- nie zgaduje automatycznie formalnego sposobu kontynuacji.

Dalsza decyzja musi respektować wersjonowane reguły egzaminu i wynik finalnego legal/compliance review.

## 22A.22. Wymiana zepsutego komputera

`ExamStation` nie jest usuwane.

Na nowym komputerze:

```text
/egzamin
↓
[Skonfiguruj stanowisko]
↓
ORG_OWNER / ORG_ADMIN
↓
wybierz istniejące ExamStation
↓
[Zastąp aktywną przeglądarkę]
```

Backend atomowo:

```text
revoke previous BrowserRegistration
↓
create new ACTIVE BrowserRegistration
↓
publish STATION_REPLACED
```

Stary credential natychmiast traci ważność.

## 22A.23. Single active runtime

Jedna browser registration może technicznie zostać otwarta w kilku kartach tej samej przeglądarki.

Dlatego runtime powinien posiadać krótkotrwały server-side lease:

```text
exam_station_runtime_leases

exam_station_id
runtime_instance_id
lease_expires_at
```

Tylko aktualny lease holder może:

```text
START_EXAM
SUBMIT_ANSWER
FINISH_EXAM
```

Druga karta pokazuje:

```text
Stanowisko jest aktywne w innej karcie.
[Przejmij tę kartę]
```

Przejęcie unieważnia poprzedni runtime lease.

## 22A.24. Security boundary

Browser Exam Station może wywoływać tylko dedykowane Exam Station API.

Nie może:

```text
listować kursantów
otwierać dokumentów
zarządzać staff
widzieć billingu
zmieniać organizacji
wykonywać admin API
```

Każdy request jest scope'owany do:

```text
organization_id
exam_station_id
current_exam_session_id
```

## 22A.25. Multiple stations

Jeżeli OSK ma więcej stanowisk:

```text
ExamStation 1 — wolne
ExamStation 2 — zajęte
ExamStation 3 — wolne
```

algorytm może wybrać domyślne / pierwsze wolne stanowisko.

Manual picker pokazujemy dopiero, gdy automatyczny wybór jest niemożliwy albo użytkownik wybierze `Zmień`.

## 22A.26. Canonical small-OSK workflow

```text
ORG_ADMIN
↓
Jan Kowalski
↓
[▶ Egzamin teraz]
↓
server creates + assigns session
↓
EXAM_ASSIGNED
↓
BrowserExamStation:
Jan Kowalski / B
[Rozpocznij egzamin]
↓
student starts
↓
answers persisted server-side
↓
result calculated server-side
↓
EXAM_FINISHED
↓
ORG_ADMIN receives result
↓
[Przygotuj dokument]
```

> **Dla zwykłego przypadku nie ma konfiguracji per egzamin. Złożoność jest ukryta w ustawieniach domyślnych i pojawia się tylko w wyjątkach.**

> **Browser Exam Station jest lekkim terminalem SaaS. Laravel/DB pozostaje source of truth, realtime służy wyłącznie do sygnałów, a polling działa tylko jako fallback.**

---

# 23. Pierwsza pomoc

Pierwsza pomoc jest osobnym formalnym elementem.

Nie jest automatycznie ukończona dlatego, że kursant obejrzał materiał online.

Canonical:

```text
first_aid_completions

id
course_enrollment_id

status
# PENDING
# CONFIRMED
# CREDITED
# NOT_REQUIRED

completed_at nullable

required_seconds nullable
accepted_seconds nullable

recorded_by_user_id nullable
confirmed_by_staff_member_id nullable

source
# OSK_SESSION
# PREVIOUS_CATEGORY_CREDIT
# PARALLEL_TRAINING_CREDIT
# OTHER_LEGAL_BASIS

legal_rule_id nullable
evidence_snapshot_json nullable

created_at
updated_at
```

UX:

```text
Pierwsza pomoc
○ Do realizacji
[Potwierdź realizację]
```

albo:

```text
Pierwsza pomoc
✅ Zaliczone z wcześniejszej kategorii B
```

Źródłem decyzji o tym, czy jest wymagana, jest `EnrollmentRequirements`.

---

# 24. Zajęcia praktyczne — minimalny subsystem

Nie budujemy kalendarza ani pełnego fleet management.

Budujemy dokumentacyjne minimum:

```text
practical_training_entries

id
course_enrollment_id

training_date
started_at
ended_at

duration_seconds
distance_km nullable

instructor_staff_member_id
vehicle_id nullable

status
# DRAFT
# CONFIRMED
# SUPERSEDED

source
# PRAWKONARAZ_ENTRY
# PAPER_IMPORT

created_at
updated_at
```

## 24.1. PAPER

PrawkoNaRaz może prowadzić roboczą strukturę wpisów praktycznych i wykorzystać ją do przygotowania kompletnej karty.

Formalność pozostaje papierowa:

```text
entry draft
↓
official PDF / paper card
↓
paper signatures
```

Nie wymagamy w V1 kalendarza jazd.

## 24.2. Pojazdy

```text
training_vehicles

id
organization_id
registration_number
category
active
```

---

# 25. Egzamin wewnętrzny — praktyka

## 25.1. Dla instruktora

To jest przede wszystkim ekran OSK / instruktora.

Kursant nie:

- wybiera wyniku,
- wypełnia arkusza,
- zarządza błędami.

## 25.2. Dostępność — CANONICAL

**Nie uzależniać dostępności arkusza praktycznego od `THEORY PASS`.**

Prawidłowo:

```text
if EnrollmentRequirements.internal_practical_exam_required
AND internal_practical_exam_handling_mode == IN_PLATFORM:
    practical_exam_module_available = true

if handling_mode == EXTERNAL_OSK:
    show_external_result_recording_workflow()

if handling_mode == NOT_MANAGED:
    hide_practical_exam_workflow_from_normal_product_flow()
```

To obsługuje m.in.:

```text
C+E
D+E
B+E
```

gdzie formalnej teorii wewnętrznej nie ma.

### Gdy teoria jest wymagana

UX może po `THEORY PASS` automatycznie zaproponować:

```text
[Przygotuj arkusz praktyczny]
```

ale PASS nie jest technicznym źródłem dostępności modułu.

## 25.3. Operational draft ≠ formal approval

Instruktor może:

```text
utworzyć arkusz
wypełnić dane
przygotować draft
```

bez blokady Learning Engine.

Natomiast przed formalnym zatwierdzeniem system sprawdza wymagane prawem formalne preconditions dla danego enrollmentu.

```text
prepare / edit = operational
approve formal document = compliance validation
```

## 25.4. PAPER

```text
InternalPracticalExam

id
course_enrollment_id

exam_date nullable

status
# DRAFT
# READY_FOR_REVIEW
# APPROVED_FOR_PRINT
# SUPERSEDED

result
# PASS
# FAIL
# nullable while draft

examiner_staff_member_id
lead_instructor_staff_member_id nullable
vehicle_id nullable

template_version

approved_for_print_by_user_id nullable
approved_for_print_at nullable

snapshot_json nullable
document_hash nullable
pdf_path nullable

created_at
updated_at
```

Pozycje:

```text
internal_practical_exam_items

id
internal_practical_exam_id

item_code
item_label_snapshot
sequence_number

status nullable
error_markers_json nullable
notes nullable
```

## 25.5. PASS i FAIL

Praktyczny egzamin przechowuje oba wyniki.

Nie stosować:

```text
FAIL → destroy
```

## 25.6. Canonical Practical Exam Sheet

P0 asset:

```text
canonical_practical_internal_exam_sheet.pdf
```

Wymagania:

- aktualny urzędowy wzór,
- bez brandingu PrawkoNaRaz,
- bez elementów publikatora,
- system wypełnia istniejące pola,
- version + hash,
- visual regression test.

**Brak tego assetu jest P0 do ukończenia PAPER practical exam.**

---

# 26. PAPER — Karta przeprowadzonych zajęć

## 26.1. Canonical template

Jedyną bazą generatora jest zaakceptowany czysty urzędowy formularz:

```text
karta_przeprowadzonych_zajec_czysty_formularz_A4.pdf
```

Nie tworzyć „podobnej” wersji HTML/CSS.

Preferowany renderer:

```text
canonical PDF template
↓
overlay wartości na istniejące pola
↓
validation
↓
flatten
↓
final PDF
```

## 26.2. Zakaz dodatków

Formalny PDF nie zawiera:

```text
logo PrawkoNaRaz
prawkonaraz.pl
QR
nazw modułów
System Evidence
statusów aplikacji
marketingu
własnych pól
```

## 26.3. Common draft

```text
TrainingCardDataDraft
```

to wspólny roboczy model danych przed formalizacją.

Nie nazywać go `OfficialTrainingCard`.

## 26.4. PAPER output

```text
PaperTrainingCardExport
```

Formalny papierowy workflow:

```text
TrainingCardDataDraft
↓
Instructor Review
↓
Compliance Validator
↓
APPROVED_FOR_PRINT
↓
PaperTrainingCardExport
↓
PDF
↓
druk
↓
podpisy papierowe
```

## 26.5. Time review

Instruktor widzi np.:

```text
Data    Moduł                System      Do dokumentacji
03.09   Znaki                180 min     [90 min]
04.09   Pierwszeństwo        165 min     [90 min]
```

Formalny formularz nie otrzymuje nazwy modułu.

## 26.6. Dokumenty pomocnicze

Minimum:

```text
Karta przeprowadzonych zajęć — formal PDF
Wykaz realizacji teorii online — helper PDF
System Evidence — PDF / CSV
```

---

# 27. Document lifecycle

Canonical:

```text
DRAFT
READY_FOR_REVIEW
APPROVED_FOR_PRINT   # PAPER
CONFIRMED            # ELECTRONIC
SUPERSEDED
```

Po zatwierdzeniu:

- snapshot immutable,
- template version zapisany,
- template hash zapisany,
- document hash zapisany,
- ponowne pobranie tej samej wersji daje ten sam dokument.

Korekta:

```text
v1 APPROVED
↓
v2 DRAFT
↓
v2 APPROVED
↓
v1 SUPERSEDED
```

Nie UPDATE zatwierdzonego dokumentu.

---

# 28. Documentation Mode

```text
PAPER
ELECTRONIC
```

Domyślne ustawienie może pochodzić z OSK:

```text
organizations.default_documentation_mode
```

ale wybór jest snapshotowany na `CourseEnrollment`.

Przed `formal_started_at`:

```text
PAPER ↔ ELECTRONIC
```

może być zmienione.

Po formalnym starcie:

```text
documentation_mode_locked_at
```

i zwykła zmiana jest zablokowana.

Zmiana ustawienia organizacji nie migruje aktywnych enrollmentów.

---

# 29. ELECTRONIC — architektura docelowa

PAPER i ELECTRONIC wykorzystują te same dane wejściowe.

```text
Enrollment
↓
System Evidence
↓
TrainingCardDataDraft
↓
Instructor Review
↓
Accepted Documentation Data
     ├── PAPER
     └── ELECTRONIC
```

Subsystem:

```text
ElectronicDocumentationEngine

LegalRequirementsRegistry
TrainingCardService
FormalAuthenticationService
ElectronicEntryService
ElectronicCorrectionService
OutageFallbackService
TrainingInterruptionService
TrainingTransferService
RetentionService
ElectronicDocumentExportService
ElectronicAuditService
```

## 29.1. Dwa statusy

```text
electronic_card_environment_status
# ISOLATED
# PILOT
# PRODUCTION

electronic_card_compliance_status
# INCOMPLETE
# INTERNAL_REVIEW
# LEGAL_REVIEW
# READY
```

W `ISOLATED` oba przyciski PAPER/ELECTRONIC mogą być aktywne UX-owo.

W realnym `PRODUCTION` formalne użycie ELECTRONIC wymaga:

```text
compliance_status = READY
```

## 29.2. ELECTRONIC obejmuje docelowo

```text
teoria
praktyka
egzamin wewnętrzny
formal authentication
append-only corrections
outage fallback
interruption
transfer/import
retention
read / print
formal export
audit
```

## 29.3. Nie traktować zwykłego kliknięcia jako formalnego podpisu

Metoda uwierzytelnienia wynika z:

```text
actor
action
LegalRequirementsRegistry
```

---

# 30. Electronic formal entries

Formalny wpis po uwierzytelnieniu jest immutable.

Korekta:

```text
original entry
↓
correction entry
```

Nie:

```text
UPDATE original authenticated entry
```

Audit zapisuje:

```text
actor
formal_role
action_type
before_snapshot
after_snapshot
authentication_method
payload_hash
timestamp
```

---

# 31. Awaria / przerwanie / transfer — ELECTRONIC

## 31.1. Outage

```text
e-card unavailable
↓
paper fallback
↓
system restored
↓
reconciliation
↓
formal authentication
```

## 31.2. Przerwanie

Nie:

```text
status = CANCELLED
```

Tylko formalny workflow:

```text
freeze state
export
required authentication
print representation
archive snapshot
handoff
```

## 31.3. Transfer

```text
export previous training
import previous document
validate
attach to new enrollment
```

---

# 32. Retencja i RODO

Formalna dokumentacja nie może być kasowana dlatego, że 90-dniowy Access wygasł.

System musi mieć osobne:

```text
access retention
learning data retention
formal documentation retention
account/privacy lifecycle
```

Przed produkcją wymagane jest osobne zatwierdzenie:

```text
DataRetentionPolicy
GDPRControllerProcessorPolicy
DataSubjectRequestPolicy
BackupAndRestorePolicy
SecurityIncidentPolicy
```

Nie hardkodować okresów retencji na podstawie samego okresu dostępu produktu.

---

# 33. Minimalne API domenowe

Przykładowe endpointy / commands:

```text
POST /enrollments
POST /enrollments/{id}/activate
GET  /enrollments/{id}

POST /enrollments/{id}/facts
POST /enrollments/{id}/resolve-requirements
GET  /enrollments/{id}/requirements

POST /learning/{enrollment}/start
POST /learning/{enrollment}/steps/{step}/submit
POST /learning/{enrollment}/resume

POST /learning/{enrollment}/sessions/start
POST /learning/{enrollment}/sessions/heartbeat
POST /learning/{enrollment}/sessions/close

POST /module-assessments/{id}/start
POST /module-assessments/{id}/submit

POST /internal-theory/{enrollment}/start
POST /internal-theory/{enrollment}/submit
POST /internal-theory/{enrollment}/record-manual-pass

POST /first-aid/{enrollment}/confirm

POST /practical-training/{enrollment}/entries

POST /internal-practical/{enrollment}/draft
POST /internal-practical/{exam}/items
POST /internal-practical/{exam}/approve-print

GET  /documentation/{enrollment}
POST /documentation/{enrollment}/review
POST /documentation/{enrollment}/approve-paper
GET  /documentation/{enrollment}/paper-card.pdf
```

Nazwy endpointów są przykładowe. Domain services i invariants są ważniejsze niż URL.

---

# 34. Canonical data model — lista encji

## Identity / OSK

```text
users
organizations
organization_users
organization_staff_members
organization_staff_qualifications
organization_formal_roles
organization_formal_elearning_status
training_vehicles
```

## Billing / access

```text
student_course_accesses
organization_credit_transactions
access_extension_transactions
```

## Enrollment / legal rules

```text
course_enrollments
course_enrollment_facts
course_enrollment_requirements
course_enrollment_requirement_adjustments
legal_requirement_rules
theory_result_equivalence_rules
legal_review_flags
```

## Learning

```text
courses
course_versions
modules
lessons
lesson_steps
lesson_attempts
lesson_step_attempts
module_assessments
module_assessment_attempts
learning_sessions
course_enrollment_time_summaries
time_policies
concepts
lesson_step_concepts
```

## Formal elements

```text
first_aid_completions
practical_training_entries
internal_theory_exam_rule_versions
internal_theory_exam_sessions
internal_theory_exam_results
exam_stations
exam_station_browser_registrations
internal_practical_exams
internal_practical_exam_items
```

## Documentation

```text
training_card_data_drafts
paper_training_card_exports
paper_training_card_entries
theory_training_report_versions

electronic_training_cards
electronic_formal_entries
electronic_entry_corrections
formal_authentications

previous_training_documents
formal_document_exports
```

## Audit / evidence

```text
compliance_evidence_packs
compliance_audit_events
document_version_snapshots
```

---

# 35. Formal state vs learning health

Nie mieszać.

```text
learning_health:
OK
ATTENTION
PROBLEM
```

```text
formal_state:
NOT_APPLICABLE
NOT_READY
READY
IN_PROGRESS
READY_FOR_CONFIRMATION
CONFIRMED
BLOCKED
INTERRUPTED
```

`learning_health = PROBLEM` nie musi oznaczać formalnej blokady.

`formal_state = BLOCKED` nie oznacza, że użytkownik ma problem dydaktyczny.

---

# 36. Profil kursanta OSK

Docelowo jedna strona pokazuje:

```text
Jan Kowalski
Kategoria B

Dostęp
ACTIVE · do 22.11.2026

Wymagania
Teoria                 WYMAGANA
Pierwsza pomoc          WYMAGANA
Praktyka                WYMAGANA
Egzamin wewn. teoria    WYMAGANY
Egzamin wewn. praktyka  WYMAGANY

Nauka
Teoria online           68%
Czas wymagany           18 / 26 godz. szkoleniowych

Pierwsza pomoc
○ Do potwierdzenia

Egzamin wewnętrzny
Teoria                  ○ brak pozytywnego wyniku
Praktyka                ○ do przeprowadzenia

Dokumentacja
PAPER
[Dokumenty]
```

Dla enrollmentu z wyjątkiem:

```text
Teoria                 NIEWYMAGANA — uznany wynik państwowy
Egzamin wewn. teoria   NIEWYMAGANY
Learning Engine         opcjonalny
```

---

# 37. Ekran kursanta

Najważniejszy ekran:

```text
Twój następny krok
[Kontynuuj naukę]
```

Kursant nie konfiguruje:

- kolejności,
- wymaganych modułów,
- assessmentów,
- compliance.

Jeśli Learning Engine nie jest formalnie wymagany:

```text
Materiały dodatkowe
[Ucz się]
```

bez komunikowania, że są formalnym obowiązkiem.

---

# 38. Internal exam UI

## 38.1. Teoria — OSK

```text
Egzamin wewnętrzny — teoria

Formalny status:
WYMAGANY / NIEWYMAGANY

[Uruchom formalny egzamin]
lub
[Uruchom test diagnostyczny]
```

Przycisk formalny jest prezentowany zgodnie z `EnrollmentRequirements`.

Sam silnik testowy nie zależy od Learning Engine.

## 38.2. Praktyka — instruktor

```text
Egzamin wewnętrzny — praktyka

[Przygotuj arkusz]

Data
Instruktor egzaminujący
Instruktor prowadzący
Pojazd

zadania / błędy

Wynik:
PASS / FAIL

[Zatwierdź do wydruku]
```

---

# 39. Formalna kolejność nie może wynikać z UI

Reguły walidacji formalnej są w backendzie.

Przykład praktycznego arkusza:

```text
canCreateDraft() = operational permission
canApproveAsFormal() = legal/compliance validation
```

Dzięki temu:

- OSK może przygotować dokument wcześniej,
- kategoria `+E` nie jest blokowana brakiem teorii,
- formalny PDF nie jest zatwierdzony, jeżeli konkretne wymagane warunki prawne nie są spełnione.

---

# 40. Content / curriculum

Pierwszy pełny vertical slice: kategoria B.

Przed publikacją pełnego kursu:

```text
Category B Compliance Curriculum Matrix
```

mapuje:

```text
legal_requirement_key
↓
CourseVersion / Module / Lesson / Step
↓
assessment coverage
```

Nie publikować pełnego formalnego programu bez potwierdzenia pokrycia wymaganych tematów.

---

# 41. Versioning

Wersjonować:

```text
CourseVersion
LegalRequirementsRegistry
EnrollmentRequirements
RequirementAdjustments
TimePolicy
InternalTheoryExamRules
Canonical PDF templates
ModuleAssessment
Formal statement versions
Electronic export formats
```

Aktywny enrollment zachowuje historyczny snapshot zasad, według których był prowadzony.

Zmiana prawa nie może po cichu zmieniać już zatwierdzonego historycznego dokumentu.

---

# 42. Security

Minimum:

- tenant isolation backend,
- authorization policies dla każdej operacji OSK,
- secure activation tokens,
- brak jawnych haseł,
- rate limiting,
- CSRF/session protection zgodnie ze stackiem,
- audit formalnych operacji,
- encryption dla wrażliwych referencji,
- signed/private access do dokumentów,
- dokumenty nie mogą mieć publicznych przewidywalnych URL,
- backup + restore test,
- monitoring błędów formalnych workflows.

Formal auth ELECTRONIC jest osobnym poziomem od zwykłego zalogowania do aplikacji.

---




# 42.8. Testy P0 — ORG_OWNER permission model

```text
owner_has_all_product_permissions
org_owner_can_access_every_org_admin_screen

no_subordinate_role_has_permission_unavailable_to_owner

org_admin_permissions_are_delegated
org_admin_can_be_more_restricted_than_default_preset

owner_permissions_cannot_be_accidentally_removed

formal_qualification_is_independent_from_product_permission

owner_without_instructor_qualification_cannot_be_recorded_as_examiner
owner_with_linked_instructor_staff_profile_can_be_recorded_as_examiner

same_ui_routes_are_used_by_org_owner_and_org_admin
navigation_hides_actions_without_permission

audit_records_actual_user_not_permission_grantor
```

---

# 42.9. Testy P0 — organization operations

```text
paper_v1_does_not_require_instructor_application_account
org_admin_can_manage_students
org_admin_can_prepare_exam_workflow
org_admin_can_prepare_paper_documents

staff_member_can_exist_without_linked_user
staff_member_can_later_link_user_for_electronic_mode

exam_stores_operator_separately_from_formal_actor
paper_document_stores_preparer_separately_from_signing_staff

org_admin_cannot_be_silently_recorded_as_examiner
unless_org_admin_is_linked_to_selected_qualified_staff_member

paper_formal_signature_is_not_replaced_by_org_admin_click

electronic_formal_action_requires_individual_auth_when_rule_requires_it

legacy_moderator_role_migrates_to_org_admin
```

---

# 42A. Testy P0 — opcjonalna obsługa egzaminów przez platformę

```text
organization_can_choose_internal_theory_exam_handling_mode
organization_can_choose_internal_practical_exam_handling_mode

new_enrollment_snapshots_exam_handling_modes
organization_setting_change_does_not_mutate_existing_enrollment

required_exam_can_be_in_platform
required_exam_can_be_external_osk
required_exam_can_be_not_managed_by_platform

not_managed_exam_does_not_block_learning_engine
not_managed_exam_does_not_block_access
not_managed_exam_does_not_block_theory_evidence_pack

required_not_managed_exam_prevents_full_formal_completion_claim
required_not_managed_exam_allows_platform_scope_completed

external_osk_theory_pass_can_be_recorded
external_osk_practical_result_can_be_recorded

not_required_exam_never_becomes_required_due_to_organization_setting

plus_e_theory_remains_not_required_regardless_of_handling_preference

exam_handling_mode_does_not_modify_enrollment_requirements
```

---

# 43. Testy P0 — architektura wyjątków

```text
all_plus_e_categories_have_zero_required_theory
all_plus_e_categories_have_no_formal_internal_theory_exam
all_plus_e_categories_can_require_internal_practical_exam

c_to_c_plus_e_does_not_repeat_theory
b_to_b_plus_e_does_not_repeat_theory

b1_to_b_waives_theory
c1_to_c_waives_theory
d1_to_d_waives_theory
a1_to_a2_waives_theory
a1_or_a2_to_a_waives_theory

a1_to_a2_reduces_practical_to_10h
a2_to_a_reduces_practical_to_10h
b1_to_b_reduces_practical_to_20h

positive_state_theory_applies_article23a
article23a_waives_formal_theory
article23a_waives_internal_theory_exam

held_b_credits_first_aid_when_rule_applies

requirements_use_multiple_adjustments
adjustment_has_legal_rule_id
adjustment_has_facts_snapshot
requirements_snapshot_is_immutable

ambiguous_reductions_do_not_silently_stack
```

---

# 44. Testy P0 — Learning / time

```text
course_version_is_frozen_for_active_enrollment

required_scope_is_server_enforced
required_module_assessment_fail_blocks_next_module
required_module_assessment_pass_unlocks_next_module

formal_time_is_global_per_enrollment
module_estimated_time_is_not_formal_legal_minimum

theory_completion_requires_all_required_scope
theory_completion_requires_all_required_assessments
theory_completion_requires_required_total_time

time_shortfall_routes_to_controlled_review
time_shortfall_does_not_require_idle_waiting

overlapping_sessions_count_once
max_session_closes_without_auto_restart
film_speed_counts_real_elapsed_time
correct_fast_answer_is_not_invalidated

evidence_validator_recalculates_total_from_source_sessions
```

---

# 45. Testy P0 — egzaminy wewnętrzne

## Theory

```text
internal_theory_engine_is_independent_of_learning_progress
internal_theory_can_run_without_required_theory_time
internal_theory_has_unlimited_retries

failed_internal_theory_session_is_not_persisted
failed_internal_theory_can_retry_immediately

passed_internal_theory_is_persisted
passed_result_belongs_to_course_enrollment
manual_pass_can_be_recorded_by_osk

diagnostic_test_available_when_formal_exam_not_required
diagnostic_test_does_not_create_formal_exam_record
```

## Practical

```text
practical_exam_availability_uses_enrollment_requirements
practical_exam_does_not_require_theory_pass_when_theory_not_required
plus_e_can_prepare_practical_exam_sheet

practical_exam_draft_is_independent_of_learning_progress
practical_exam_supports_pass
practical_exam_supports_fail

formal_approval_runs_compliance_validation

approved_practical_sheet_is_immutable
correction_creates_new_version
```

---



# 45.9. Testy P0 — existing exam module migration

```text
existing_exam_module_is_audited_before_new_engine_work

existing_frontend_components_are_classified
existing_backend_components_are_classified
existing_exam_tables_are_classified

regression_tests_exist_before_exam_refactor

no_duplicate_exam_engine_remains_after_migration
no_duplicate_question_renderer_is_created_without_justification

rewrite_requires_written_technical_justification

existing_correct_result_logic_is_reused_or_extracted
existing_correct_question_renderer_is_reused_or_adapted

canonical_v2_12_behavior_wins_over_incompatible_legacy_behavior
```

---

# 45A. Testy P0 — Browser Exam Station

```text
exam_station_is_logical_org_resource
exam_station_is_not_bound_to_hardware_fingerprint

browser_registration_belongs_to_exam_station
only_one_active_browser_registration_per_station
replacing_browser_revokes_previous_credential

browser_station_has_no_admin_permissions

org_admin_can_launch_exam_in_one_primary_action
default_station_removes_station_selection_step
default_qualified_conductor_removes_conductor_selection_step

station_receives_exam_without_student_input
student_does_not_login
student_does_not_enter_code
student_does_not_select_enrollment
student_only_sees_assigned_exam

one_active_exam_session_per_station

prepared_exam_can_be_cancelled_before_start
student_identity_cannot_change_after_exam_start

http_api_is_source_of_truth
realtime_event_does_not_replace_persisted_state
station_fetches_current_state_after_connect
browser_refresh_restores_current_exam

polling_is_disabled_while_realtime_connected
fallback_polling_activates_on_realtime_failure
fallback_polling_uses_backoff_or_reasonable_interval

websocket_keepalive_does_not_write_db_every_ping
station_last_seen_is_rate_limited
idle_station_does_not_continuously_query_exam_state

answers_are_persisted_server_side
exam_result_is_calculated_server_side
exam_result_is_pushed_back_to_admin_ui

interrupted_session_is_not_automatically_failed

new_browser_can_replace_broken_computer
old_browser_credential_is_revoked_after_replacement

only_one_runtime_lease_can_control_station
second_tab_cannot_submit_without_runtime_lease
runtime_takeover_revokes_previous_lease

station_credential_cannot_access_admin_api
```

---

# 46. Testy P0 — PAPER documentation

```text
paper_card_uses_canonical_template
paper_card_has_no_prawkonaraz_branding
paper_card_has_no_publication_headers

known_student_data_is_prefilled
known_organization_data_is_prefilled
known_instructor_data_is_prefilled

system_evidence_is_not_mutated_by_instructor_review
accepted_documentation_time_is_separate

cannot_approve_time_below_required_enrollment_time

approved_document_is_immutable
approved_document_has_snapshot_and_hash
same_version_redownload_is_deterministic
correction_creates_new_version
```

---

# 47. ELECTRONIC = READY — Definition of Done

`electronic_card_compliance_status = READY` dopiero gdy:

- wszystkie P0 e-card requirements są zaimplementowane,
- każda formalna czynność ma właściwą metodę auth,
- theory flow działa end-to-end,
- practical entries działają,
- internal exams działają,
- corrections są append-only,
- outage fallback działa,
- interruption/export działa,
- transfer/import działa,
- retention działa,
- read/print działa,
- test suite przechodzi,
- security review przechodzi,
- legal review przechodzi bez blockerów.

---

# 48. Czego NIE budujemy przed PAPER vertical slice

```text
CRM
lead management
pełny kalendarz jazd
rozbudowany fleet management
SMS gateway
AI tutor
AI compliance agent
adaptive learning
Bayesian mastery
spaced repetition
graph editor
pełny ERP OSK
```

---

# 49. Brakujące canonical assets / reguły

## P0-A — Practical Internal Exam Sheet

Potrzebny:

```text
canonical_practical_internal_exam_sheet.pdf
```

z aktualnego urzędowego wzoru.

## P0-B — Internal Theory Exam Rules

Potrzebny zweryfikowany rekord:

```text
internal_theory_exam_rule_versions
```

dla każdej obsługiwanej kategorii.

## P0-C — Legal edge cases

Do finalnej decyzji:

```text
PARALLEL_THEORY_WITH_NO_THEORY_CATEGORY
MULTIPLE_PRACTICAL_REDUCTIONS_STACKING
CATEGORY_EQUIVALENCE_EDGE_CASE
INTERNAL_THEORY_FAILED_ATTEMPT_RETENTION
```

## P0-D — Privacy / retention

Potrzebne przed production:

```text
RODO role matrix
retention matrix
account deletion vs formal records rules
backup/restore policy
incident process
```

---


# 49A. Existing Internal Theory Exam Module — AUDIT BEFORE BUILD

## 49A.1. Stan istniejący

W istniejącym produkcie / repozytorium **znajduje się już wstępny, prosty webowy moduł egzaminu teoretycznego**.

Nie traktować V2.12 jako polecenia utworzenia całego modułu egzaminacyjnego od zera.

Canonical instruction:

> **Najpierw zbadaj istniejący moduł egzaminu. Dopiero po audycie zdecyduj, które elementy można zachować i dostosować do architektury V2.12.**

## 49A.2. Zakaz greenfield rewrite przed audytem

Agent / AI coder NIE może rozpocząć od:

```text
create new exam frontend
create new exam engine
create new exam routes
create new question renderer
create new result screen
```

bez wcześniejszego zidentyfikowania istniejącej implementacji.

Nie zakładać, że prosty istniejący moduł jest zły tylko dlatego, że nie implementuje jeszcze pełnego V2.12.

Preferencja:

```text
REUSE
↓
ADAPT
↓
EXTEND
↓
REFACTOR
↓
REPLACE ONLY IF JUSTIFIED
```

Nie:

```text
IGNORE EXISTING CODE
↓
REBUILD EVERYTHING
```

## 49A.3. Mandatory repository audit

Przed zmianami agent ma zlokalizować co najmniej:

```text
frontend routes
Vue views / components
exam question renderer
answer controls
navigation between questions
timer
result screen
exam start flow

Laravel routes
controllers
services
models
migrations
question-selection logic
answer-validation logic
result-calculation logic
session/state persistence
existing API contracts

authentication / middleware
student identity assumptions
existing exam configuration
existing logging / audit
```

Jeżeli nazwy są inne — znaleźć funkcjonalne odpowiedniki.

## 49A.4. Required audit output

Przed implementacją agent powinien sporządzić krótką mapę:

```text
EXISTING EXAM MODULE AUDIT

1. Entry points
2. Frontend components
3. Backend endpoints
4. Domain/service logic
5. Persistence model
6. Question selection
7. Answer flow
8. Timer implementation
9. Result calculation
10. Authentication assumptions
11. Reusable parts
12. Parts requiring refactor
13. Parts incompatible with V2.12
14. Proposed migration path
```

Dla każdego elementu klasyfikacja:

```text
KEEP
KEEP_WITH_MINOR_CHANGE
REFACTOR
REPLACE
REMOVE
UNKNOWN_NEEDS_TEST
```

## 49A.5. Co szczególnie ocenić

### A. Question renderer

Sprawdzić, czy istniejący renderer może obsłużyć:

```text
current official question types
text
image
video if applicable
answer options
timing rules
```

Jeżeli działa poprawnie, nie tworzyć drugiego renderera.

### B. Exam engine

Sprawdzić, czy istniejąca logika:

```text
selects questions
orders questions
tracks current question
stores answers
calculates result
handles time
```

może zostać opakowana / przeniesiona do:

```text
InternalTheoryExamEngine
```

bez pełnego rewrite.

### C. Existing result calculation

Nie przepisywać punktacji tylko dlatego, że powstaje nowy workflow Browser Exam Station.

Najpierw:

```text
compare existing calculation
vs
InternalTheoryExamRulesVersion
```

Jeżeli jest poprawna:

```text
extract + version + test
```

zamiast pisać ponownie.

### D. Existing frontend

Jeżeli aktualny prosty web ma działające:

```text
question screen
answer buttons
next step
timer
result screen
```

należy preferować ich adaptację do:

```text
BrowserExamStationMode
```

zamiast tworzenia równoległego interfejsu.

### E. Existing persistence

Sprawdzić, czy obecny moduł zapisuje:

```text
attempt
answers
current question
result
timestamps
```

Jeżeli istniejące tabele są użyteczne:

```text
migration / extension
```

jest preferowane nad:

```text
new duplicate tables
```

## 49A.6. Target architecture remains V2.12

Istniejący moduł nie staje się canonical tylko dlatego, że już istnieje.

Po audycie jego elementy muszą zostać dopasowane do:

```text
InternalTheoryExamEngine
InternalTheoryExamRulesVersion
InternalTheoryExamSession
BrowserExamStation
ExamStationBrowserRegistration
server-side source of truth
ORG_OWNER / ORG_ADMIN permissions
OrganizationStaffMember formal actor
IN_PLATFORM / EXTERNAL_OSK / NOT_MANAGED
FORMAL_INTERNAL / DIAGNOSTIC
```

Czyli:

```text
existing code
↓
audit
↓
map to canonical domains
↓
minimal safe refactor
```

## 49A.7. No duplicate exam engines

Po migracji nie mogą istnieć dwa niezależne silniki:

```text
OldExamEngine
NewInternalTheoryExamEngine
```

obsługujące ten sam przypadek biznesowy.

Docelowo jedno canonical API/domain engine.

Możliwe przejściowo:

```text
existing UI
↓
adapter
↓
new canonical service
```

ale adapter ma być etapem migracji, nie trwałą duplikacją logiki.

## 49A.8. Preserve working behavior

Podczas refaktoru należy zabezpieczyć istniejące działające zachowanie przez testy regresyjne.

Minimum:

```text
exam_can_start
question_is_rendered
answer_can_be_submitted
navigation_works
timer_behaves_correctly
result_is_calculated
pass_result_is_saved_when_applicable
```

Dopiero potem rozszerzać o:

```text
Browser Exam Station
realtime assignment
staff actor
rules versioning
diagnostic/formal purpose
server-side recovery
```

## 49A.9. Migration strategy

Preferowana kolejność:

```text
1. Discover existing module
2. Document current flow
3. Add regression tests
4. Compare with V2.12 target
5. Extract reusable engine logic
6. Introduce canonical rule versioning
7. Introduce canonical session model
8. Adapt existing UI
9. Add Browser Exam Station wrapper
10. Add realtime assignment
11. Add recovery / interruption handling
12. Remove superseded legacy paths
```

## 49A.10. Rewrite requires justification

Pełne przepisanie istniejącego modułu jest dopuszczalne wyłącznie, gdy audit wykaże np.:

```text
unsafe architecture
incorrect exam logic
unmaintainable coupling
no server-side state
unrecoverable data model
duplicate question implementation
fundamental incompatibility with canonical requirements
```

Wtedy agent ma zapisać:

```text
WHY_REWRITE_IS_REQUIRED
WHAT_CAN_STILL_BE_REUSED
MIGRATION_RISK
DATA_MIGRATION_PLAN
```

przed rozpoczęciem rewrite.

## 49A.11. Canonical instruction for AI coder

> **PrawkoNaRaz posiada już wstępny prosty webowy moduł egzaminu. Nie twórz modułu egzaminu od zera. Najpierw przeprowadź audit istniejącej implementacji w Laravel/Vue, ustal co już działa, dodaj testy regresyjne i zaproponuj najmniejszy refactor potrzebny do osiągnięcia architektury V2.12.**

> **Existing code is an implementation asset, not the source of legal/domain truth. V2.12 defines target behavior; existing code should be reused wherever it is technically sound and compatible.**

---

# 50. Nowa kolejność implementacji — CANONICAL

## Etap 1 — Audit istniejącego Laravel/Vue

Sprawdzić:

```text
auth
users
questions
courses
progress
billing
existing models
migrations
PDF generation

EXISTING INTERNAL THEORY EXAM MODULE
frontend routes/components
backend routes/controllers/services
question renderer
question selection
timer
answer flow
result calculation
session persistence
```

**Istniejący prosty webowy moduł egzaminu jest obowiązkowym przedmiotem audytu.**

Nie wykonywać ślepych rename'ów.

Nie tworzyć nowego silnika / UI egzaminu przed sklasyfikowaniem istniejących elementów jako:

```text
KEEP
KEEP_WITH_MINOR_CHANGE
REFACTOR
REPLACE
REMOVE
```

## Etap 2 — Core identity / multi-tenant

```text
Organization
OrganizationUser
ORG_OWNER super-admin semantics
permission system + office presets
OrganizationStaffMember
staff qualifications / formal roles
tenant authorization
operator vs formal actor separation
```

## Etap 3 — Enrollment / Access / Billing

```text
User
CourseEnrollment
StudentCourseAccess
credit ledger
activation token
QR
90 days
extensions
```

## Etap 4 — Legal Requirements Registry

Utworzyć wersjonowany rejestr reguł prawnych.

## Etap 5 — EnrollmentFacts + Resolvers

```text
EnrollmentFacts
EnrollmentRequirementsResolver
EntitlementEquivalenceResolver
StateExamEligibilityResolver
RequirementAdjustments
LegalReviewFlags
```

Najpierw testy macierzy wyjątków.

## Etap 6 — CourseVersion immutability

```text
CourseVersion
snapshot
hash
legal version
```

## Etap 7 — Learning Engine vertical slice

Jedna mała część kursu:

```text
Module
Lesson
Steps
ModuleAssessment
Resume
```

## Etap 8 — Time Evidence

```text
LearningSession
heartbeat
max session
overlap union
global required time
TimeEvidenceValidator
```

## Etap 9 — TheoryCompletionGate

```text
scope
assessments
global enrollment time
TIME_COMPLETION_REVIEW
```

## Etap 10 — System Evidence

Immutable Evidence Pack + report.

## Etap 11 — PAPER Training Card

```text
TrainingCardDataDraft
canonical A4 template
Instructor Review
accepted time
PaperTrainingCardExport
version/hash
```

To jest pierwszy krytyczny biznesowy vertical slice.

## Etap 12 — First Aid subsystem

```text
FirstAidCompletion
manual confirm
credits from requirements resolver
```

## Etap 13 — Internal Theory Exam

```text
rule versions
FORMAL_INTERNAL / DIAGNOSTIC
unlimited attempts
FAIL ephemeral
PASS enrollment record
manual PASS
```

### Etap 13A — Browser Exam Station

```text
logical ExamStation
BrowserRegistration
one-click launch
HTTP source of truth
realtime assignment events
fallback polling
server-side answer persistence
browser replacement / revoke
interruption state
```

Nie wiązać stanowiska z hardware fingerprint.

Przed produkcją zamknąć legal flag dotyczącą FAIL retention.

## Etap 14 — Practical Training minimal entries

Bez kalendarza.

```text
date
time
km
instructor
vehicle
```

## Etap 15 — Internal Practical Exam PAPER

Najpierw pozyskać canonical official sheet.

Potem:

```text
draft
items/errors
PASS/FAIL
PDF
paper signatures
version/hash
```

## Etap 16 — Complete PAPER student documentation

Profil kursanta + komplet dokumentów.

## Etap 17 — Pilot PAPER

Jedno partnerskie OSK.

Sprawdzić realny workflow właściciela i instruktora.

## Etap 18 — Legal + Security audit PAPER

Dopiero potem komercyjny formalny rollout.

## Etap 19 — ELECTRONIC Core

```text
ElectronicTrainingCard
read/print
formal entries
```

## Etap 20 — ELECTRONIC Formal Auth

Instruktor / wykładowca / kursant / manager wg legal registry.

## Etap 21 — ELECTRONIC Practical + Internal Exams

## Etap 22 — Outage / Transfer / Retention

## Etap 23 — ELECTRONIC legal/security audit

## Etap 24 — ELECTRONIC PRODUCTION READY

---

# 51. PAPER V1 — Definition of Done

PAPER V1 jest gotowy biznesowo, gdy:

### OSK

- tworzy organizację,
- ma ORG_OWNERa,
- opcjonalnie dodaje `ORG_ADMIN`,
- ma 5 startowych dostępów,
- może dokupić dostęp,
- dodaje kursanta,
- wydaje kartę aktywacyjną QR,
- widzi wymagania konkretnego kursanta,
- system poprawnie rozpoznaje wyjątki,
- widzi postęp i formalne elementy,
- może przygotować dokumentację bez przepisywania danych.

### Kursant

- aktywuje konto,
- widzi swój następny krok,
- przechodzi Learning Engine,
- system pilnuje zakresu, assessmentów i wymaganego czasu,
- może wznowić naukę,
- jego źródłowe dane są zachowane.

### Formalna teoria

- requirements są policzone per enrollment,
- wyjątek nie tworzy fałszywego obowiązku,
- Evidence Pack jest odtwarzalny,
- instruktor przegląda accepted documentation data,
- formalna karta PAPER jest generowana z canonical template.

### Egzamin wewnętrzny

- teoria jest osobnym silnikiem,
- nie zależy od Learning Engine,
- wymagany vs diagnostic jest rozdzielony,
- OSK może wybrać `IN_PLATFORM`, `EXTERNAL_OSK` albo `NOT_MANAGED`,
- sposób obsługi egzaminu nie zmienia prawnego `EnrollmentRequirements`,
- `NOT_MANAGED` nie blokuje Learning Engine ani zakresu platformy,
- brak danych o formalnie wymaganym egzaminie nie pozwala PrawkoNaRaz deklarować pełnego formalnego ukończenia,
- praktyka jest osobnym modułem instruktora,
- `+E` nie jest blokowane brakiem teorii,
- canonical practical sheet działa.

### Dokumenty

- formalne PDF są wersjonowane,
- snapshot/hash istnieją,
- System Evidence nie jest edytowane,
- dokumenty można ponownie pobrać.

---

# 52. Zasady dla agenta wdrożeniowego

1. Najpierw przeczytaj cały V2.12 CLEAN MASTER.
2. Zrób audit istniejącego repozytorium przed migracjami.
3. Istniejący prosty webowy moduł egzaminu teoretycznego MUSI zostać zbadany przed rozpoczęciem implementacji `InternalTheoryExamEngine` lub `BrowserExamStation`.
4. Nie twórz równoległego nowego exam engine / exam UI, jeżeli istniejące elementy można bezpiecznie dostosować.
5. Przed rewrite przedstaw klasyfikację `KEEP | KEEP_WITH_MINOR_CHANGE | REFACTOR | REPLACE | REMOVE` oraz uzasadnienie.
6. Nie implementuj tabel tylko dlatego, że istniały w V2.4.
7. Canonical schemas i decyzje implementacyjne są wyłącznie w V2.12 CLEAN MASTER.
8. Nie wprowadzaj pojedynczego `exemption_code`.
9. Nie uzależniaj Internal Theory Exam od Learning Engine.
10. Nie zmieniaj `EnrollmentRequirements` na podstawie preferencji OSK dotyczącej obsługi egzaminów.
11. Obsłuż `IN_PLATFORM | EXTERNAL_OSK | NOT_MANAGED` niezależnie dla teorii i praktyki.
12. `NOT_MANAGED` nie blokuje Learning Engine, ale przy formalnie wymaganym egzaminie nie pozwala platformie deklarować pełnego formalnego ukończenia.
13. Nie uzależniaj Practical Internal Exam od Theory PASS jako globalnej reguły.
14. Nie przypisuj formalnego minimalnego czasu na sztywno do modułów.
15. Nie zmieniaj System Evidence w procesie przygotowania dokumentacji.
16. Nie twórz własnego wyglądu urzędowych PDF.
17. Nie implementuj ELECTRONIC jako formalnie produkcyjnego bez `READY`.
18. Każda nowa reguła compliance ma mieć:
   - `legal_rule_id`,
   - wersję,
   - test.
19. Każdy wyjątek musi być odtwarzalny z faktów.
20. Jeżeli prawo / reguła jest niejednoznaczna:
   - nie zgaduj,
   - dodaj `LegalReviewFlag`.
21. `ExamStation` modeluj jako logiczne stanowisko organizacji, nie fizyczny komputer.
22. Browser registration jest wymienialne i posiada odwoływalny credential.
23. HTTP/DB jest source of truth dla egzaminu; realtime przenosi wyłącznie lekkie eventy.
24. Polling jest fallbackiem i nie działa równolegle z poprawnie działającym realtime.
25. Nie używaj kodów/PIN/QR/logowania kursanta w standardowym Browser Exam Station workflow.
26. Nie rozszerzaj scope'u przed ukończeniem PAPER vertical slice.

---


# 52A. V2.11 consistency audit — resolved conflicts

Audyt V2.10 → V2.11 został wykonany przed oznaczeniem dokumentu jako canonical.

## A. Application roles

Canonical application roles:

```text
ORG_OWNER
ORG_ADMIN
ORG_MEMBER
STUDENT
```

Istniejący legacy role enum z poprzedniej implementacji należy migrować do `ORG_ADMIN` zgodnie z sekcją identity/roles.

## B. Application identity vs formal staff

Wszystkie canonical referencje do formalnych instruktorów, wykładowców i kierowników wskazują:

```text
OrganizationStaffMember
```

a nie konto aplikacyjne `OrganizationUser`.

Pola formalnego aktora używają konsekwentnie sufiksu:

```text
*_staff_member_id
```

Operator aplikacji używa:

```text
*_user_id
```

## C. Staff qualifications

Kwalifikacje i role formalne są przypisane do:

```text
organization_staff_member_id
```

Nie są przypisane bezpośrednio do application role.

## D. Browser Exam Station identity

Canonical:

```text
ExamStation
= logical organization resource

ExamStationBrowserRegistration
= replaceable browser authorization
```

Stanowisko nie jest identyfikowane przez fingerprint fizycznego komputera.

## E. Exam launch UX

Canonical normal case:

```text
[▶ Egzamin teraz]
```

Konfiguracja prowadzącego lub stanowiska pojawia się wyłącznie, gdy system nie może bezpiecznie użyć wartości domyślnych.

## F. Student handoff

Canonical:

```text
zero credential input by student
```

Kursant siada przy przygotowanym ekranie i wykonuje tylko:

```text
[Rozpocznij egzamin]
```

## G. Realtime vs server source of truth

Canonical:

```text
HTTP + DB
= source of truth

WebSocket / realtime
= lightweight event delivery

polling
= fallback only
```

Nie ma konfliktu z zasadą backend-protected state.

## H. Optional internal-exam handling

Browser Exam Station działa wyłącznie dla:

```text
IN_PLATFORM
```

Nie zmienia:

```text
EXTERNAL_OSK
NOT_MANAGED
EnrollmentRequirements
```

## I. Internal Exam Engine independence

Browser Exam Station jest terminalem/adaptorem do:

```text
InternalTheoryExamEngine
```

Nie jest częścią Learning Engine i nie wprowadza zależności od postępu Learning Engine.

## J. PAPER staff accounts

Canonical PAPER:

```text
operator_user_id
= ORG_OWNER / ORG_ADMIN

conducted_by_staff_member_id
= formalny INSTRUCTOR / LECTURER

authenticated_by_user_id
= null
```

Instruktor nie musi posiadać loginu PrawkoNaRaz w PAPER.

## K. FAIL retention

Server-side `InternalTheoryExamSession` nie zmienia bieżącej decyzji produktowej dotyczącej braku trwałej historii `FAIL`.

Session istnieje podczas wykonywania egzaminu i obsługi lifecycle.

`INTERRUPTED` nie jest `FAIL`.

Finalna polityka trwałego zapisu nieudanych prób pozostaje oznaczona do legal review.

## L. Server load

Idle station:

```text
no continuous heavy HTTP
no continuous DB polling
realtime connection idle
polling OFF while realtime works
```

Media są serwowane przez storage/CDN zamiast przez proces PHP/Laravel.

## M. Canonical source

Jedynym źródłem prawdy dla implementacji jest:

```text
V2.12 CLEAN MASTER
```

Starsze wersje nie są alternatywną specyfikacją.

---

# 53. Ostateczny model mentalny

```text
                        LEGAL RULES
                             │
                             ▼
Enrollment Facts ──► Requirements Resolver
                             │
                             ▼
                    EnrollmentRequirements
                       │              │
                       │              ├── formal theory?
                       │              ├── first aid?
                       │              ├── practical?
                       │              ├── internal theory?
                       │              └── internal practical?
                       │
             ┌─────────┴─────────┐
             ▼                   ▼
       Learning Engine      Formal OSK modules
             │              /    |     \
             │             /     |      \
             ▼            ▼      ▼       ▼
       System Evidence  First  Internal  Practical
                        Aid    Exams      Training
             \            |      |         /
              \           |      |        /
               └───────────┴──────┴───────┘
                            │
                            ▼
                   Documentation Data
                            │
                ┌───────────┴───────────┐
                ▼                       ▼
              PAPER                 ELECTRONIC
                │                       │
           PDF + podpis            Formal auth
```

> **PrawkoNaRaz nie jest jednym sztywnym kursem dla wszystkich. Jest jednym silnikiem produktu, który na podstawie faktów i wersjonowanych reguł prawa ustala formalne wymagania konkretnego kursanta.**

> **OSK ma dostać prosty workflow. Złożoność prawa, wyjątków, wersjonowania, dowodów i dokumentów pozostaje po stronie systemu.**

---

# KONIEC — V2.12 CLEAN MASTER
