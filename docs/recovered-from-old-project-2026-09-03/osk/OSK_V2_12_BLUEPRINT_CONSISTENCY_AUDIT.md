# Audyt zgodnosci blueprintu OSK V2.12 z dokumentacja wdrozenia

**Status:** zgodny po zapisaniu ponizszych doprecyzowan.
**Data:** 2026-08-24.
**Zakres:** porownanie niezmienionego blueprintu V2.12 z `README.md`, planem wdrozenia i audytem repozytorium.
**Kod aplikacji:** nie byl zmieniany w ramach tego audytu.

> **Status historyczny z aktualizacja:** audyt ustalil granice przed Etapem 1.
> Etapy 1-5P zostaly pozniej wykonane lokalnie i ponowny przeglad po pilocie,
> onboardingu B2B, operacyjnej obsludze zapisow oraz koncie przy biurku nie
> wykazal konfliktu z tymi granicami. Biezacy stan i granice Etapu 5P sa w [README](./README.md) i
> [planie wdrozenia](./OSK_V2_12_IMPLEMENTATION_PLAN.md).

## Werdykt

Blueprint jest zgodny z dokumentacja wdrozenia i nie ma konfliktu blokujacego rozpoczecie Etapu 1. Dokumentacja nie kopiuje blueprintu doslownie: mapuje go na rzeczywisty, istniejacy projekt Laravel i wyraznie chroni obecny modul `/nauka`.

Jedno wazne doprecyzowanie jest celowe:

- obecny `StudySession` i tryb `exam` sa silnikiem cwiczen oraz symulatorem egzaminu panstwowego;
- nie sa one istniejacym formalnym Learning Engine ani formalnym Internal Theory Exam Engine OSK;
- formalny, kursowy Learning Engine z `CourseVersion -> Module -> Lesson -> LessonStep -> ModuleAssessment` zostanie zbudowany raz, jako silnik wielokrotnego uzycia dla kursow. Organizacja, enrollment i wymagania prawne beda jego otoczeniem, nie osobna kopia silnika dla OSK;
- obecny `/nauka` pozostaje niezalezna praktyka pytan. Mozna go wywolac z lekcji, ale jego czas, wynik i ukonczenie nie maja znaczenia formalnego.

To nie narusza zasady blueprintu "jeden Learning Engine": w repozytorium nie ma dzis formalnego silnika lekcji B2C, ktory nalezaloby migrowac. Nie wolno natomiast nazwac ani przerobic `StudySession` na taki silnik tylko ze wzgledu na podobne ekrany pytan.

## Macierz zgodnosci

| Wymaganie blueprintu | Ustalenie w dokumentacji | Stan |
| --- | --- | --- |
| Compliance by design i exception-first UX | wersjonowane `LegalRequirements`, facts, resolver oraz `LegalReviewFlag` | Zgodne |
| Jeden Learning Engine | formalny silnik kursowy jest wspolny i reusable; `/nauka` jest tylko treningiem pytan | Zgodne po doprecyzowaniu |
| Dostepnosc operacyjna nie jest wymogiem formalnym | funkcje operacyjne nie czekaja na ukonczenie Learning Engine, chyba ze konkretny wymog tego wymaga | Zgodne po doprecyzowaniu |
| Learning Engine != Internal Exam Engine | osobne modele `InternalTheoryExam*`, brak migracji `StudySession` | Zgodne |
| `ModuleAssessment` != egzamin wewnetrzny | assessment jest elementem modulu; egzamin ma osobne reguly, sesje i wynik | Zgodne po doprecyzowaniu |
| System Evidence != zaakceptowana dokumentacja OSK | evidence, draft, review i formalna dokumentacja sa oddzielnymi warstwami | Zgodne |
| Zrodla i formalne dokumenty sa immutable | snapshoty, hashe, wersje i korekty append-only zamiast cichej edycji | Zgodne po doprecyzowaniu |
| Wyjatki prawne nie sa flagami lub kodem Vue | `EnrollmentRequirements` i resolvery, bez pojedynczego `exemption` i bez hardcode w kontrolerach | Zgodne po doprecyzowaniu |
| PAPER przed ELECTRONIC | PAPER vertical slice, canonical template i brak brandingowego PDF | Zgodne |
| `ORG_OWNER` ma zawsze pelny dostep | invariant `permissions(ORG_OWNER) >= permissions(kazdej innej roli)` | Zgodne po doprecyzowaniu |
| PAPER nie wymaga konta od instruktora | `OrganizationStaffMember.linked_user_id` jest nullable; operator i formalny aktor sa rozni | Zgodne po doprecyzowaniu |
| Wynik egzaminu nalezy do enrollmentu | formalny wynik zapisuje sie przy `CourseEnrollment`, nie globalnie przy `User` | Zgodne po doprecyzowaniu |
| `IN_PLATFORM`, `EXTERNAL_OSK`, `NOT_MANAGED` | handling mode nie zmienia wymagan prawnych i nie blokuje nauki bez podstawy | Zgodne |
| Browser Exam Station | logiczne stanowisko, osobne odwolalne credentiale, HTTP/DB jako prawda, realtime tylko sygnal, polling fallback | Zgodne po doprecyzowaniu |
| Standardowy Browser Station nie uzywa loginu/PIN/QR kursanta | QR aktywacyjny kursanta i credential stanowiska sa odrebne; normalny flow ma zero danych uwierzytelniajacych od kursanta | Zgodne po doprecyzowaniu |
| Audyt istnieacego egzaminu przed nowym silnikiem | obecny symulator zostal sklasyfikowany; przed Etapem 7 wymagany jest audyt komponentow do ewentualnego reuse | Zgodne |
| Enrollment i kredyty OSK nie sa dostepem B2C | `CourseEnrollment`, `StudentCourseAccess` i append-only ledger OSK sa odrebne od `ProductAccessGrant`, `PurchaseOrder` i checkoutu | Zgodne po implementacji Etapu 2 |
| Dostep kursanta zaczyna sie przy aktywacji | rezerwacja kredytu poprzedza aktywacje; token jest jednorazowy, hashowany i odwolalny, a 90 dni liczy sie od aktywacji | Zgodne po implementacji Etapu 2 |

## Rozstrzygniecia, ktorych nie wolno zmienic bez nowej decyzji

### 1. Formalny Learning Engine a obecne `/nauka`

`StudySession` nie jest baza danych ani mechanizmem formalnego postepu OSK. Dopuszczalny reuse dotyczy tylko wydzielonych komponentow, takich jak renderer pytania, media albo walidacja odpowiedzi, po audycie i testach regresyjnych. Nie dopisujemy do `study_sessions` organizacji, formalnego czasu, enrollmentu ani formalnego ukonczenia.

### 2. QR aktywacyjny a Browser Exam Station

QR lub jednorazowy token aktywuje kursanta do kursu. Nie jest haslem stanowiska egzaminacyjnego i nie moze sluzyc do jego standardowego uruchamiania. Browser Exam Station ma wlasny, losowy, hashowany i odwolalny credential powiazany z logicznym `ExamStation`; kursant nie wpisuje loginu, hasla, PIN-u, kodu ani QR.

### 3. PAPER i formalni pracownicy

W PAPER operator aplikacji moze byc `ORG_OWNER` albo `ORG_ADMIN`, a instruktor lub wykladowca jest wskazanym formalnym `OrganizationStaffMember`. Brak konta aplikacji instruktora jest poprawny. Klikniecie operatora nie zastepuje podpisu na papierowym formularzu ani kwalifikacji formalnego aktora.

### 4. Nieedytowalnosc i korekty

Zdarzenia nauki, proby assessmentu, evidence, zatwierdzone dokumenty i formalne wyniki nie sa nadpisywane. Korekta tworzy nowy wpis lub nowa wersje z powodem, aktorem i czasem. UI nie moze oferowac "edytuj w miejscu" dla danych formalnych.

## Bramy przed implementacja

1. **Etap 1:** mozna rozpoczac. Nie wymaga decyzji o PDF, egzaminie ani partnerze OSK.
2. **Etap 4-5:** formalny Learning Engine musi pozostac reusable; nie tworzymy wersji "tylko OSK" z osobna logika postepu.
3. **Etap 6:** wymagany canonical template PAPER i akceptacja osoby odpowiedzialnej za proces prawny.
4. **Etap 7:** przed kodem wykonac komponentowy audyt obecnego `StudySession` / `exam` jako potencjalnego zrodla rendererow, timerow, mediow i liczenia wyniku. Nie jest to migracja istniejacego formalnego egzaminu, bo takiego modulu w repozytorium nie ma.
5. **Etap 8:** najpierw stabilne API i persistence formalnego egzaminu; dopiero potem Browser Station oraz realtime.

## Potwierdzenie implementacji Etapów 1–2

Etapy 1–2 wdrozone lokalnie 2026-08-25 pozostaja zgodne z tym audytem:

- nowe tabele OSK sa addytywne i nie zmieniaja semantyki `StudySession`, `QuestionCollection`, `ProductAccessGrant` ani globalnych rol `User`;
- membership organizacji jest osobna warstwa dostepu, a `ORG_OWNER` ma pelny, niewykluczany zestaw uprawnien;
- formalny `OrganizationStaffMember` moze istniec bez konta aplikacji, a kwalifikacje oraz role formalne nie zostaly zredukowane do roli UI;
- kontekst organizacji zostal dopiety do istniejacego `AuditLog` jako nullable rozszerzenie, bez zmiany kompatybilnosci dotychczasowych wpisow;
- `CourseEnrollment` zawsze wskazuje `CourseVersion`; publikacja wersji zamraza jej snapshot i hash, a rozbudowa lekcji pozostaje na Etap 4;
- kredyty OSK sa osobnym ledgerem z idempotentna rezerwacja, konsumpcja i zwolnieniem; nie zmieniono `ProductAccessGrant`, `PurchaseOrder` ani checkoutu B2C;
- jednorazowy token aktywacyjny jest hashowany i odwolalny; nowy kursant ustawia haslo przy aktywacji, a istniejacy musi potwierdzic swoja tozsamosc przez zalogowanie;
- nie dodano routingu, UI, maili/QR, formalnego Learning Engine, Browser Exam Station ani PAPER.

Weryfikacja lokalna: migracja PostgreSQL w trybie `--pretend` oraz lokalnie wykonane `migrate`, 4 testy Etapu 1 / 29 asercji, 10 testow Etapu 2 / 59 asercji oraz zielona regresja istniejacej nauki, kolekcji, B2C access, checkoutu i audit logu.

## Pozostale ryzyka poza zgodnoscia dokumentacji

- Zgodnosc architektoniczna nie zastepuje pozniejszej weryfikacji prawnej canonical dokumentow, retencji i wymagan konkretnego OSK.
- Blueprint pozostaje zrodlem prawdy produktowej. Kazda przyszla zmiana, ktora odbiega od niego, wymaga wpisu w planie i testu kontraktowego.
- Nie wolno poszerzac zakresu o rezerwacje jazd, kalendarz, CRM, flote ani SMS przed PAPER vertical slice.

## Historia

| Data | Zmiana |
| --- | --- |
| 2026-08-24 | Wykonano ponowny audyt zgodnosci blueprintu z dokumentacja; zapisano interpretacje jednego Learning Engine, granice `/nauka`, Browser Station, QR oraz niezmiennosc formalnych danych. |
| 2026-08-25 | Potwierdzono, ze implementacja Etapu 1 zachowuje ustalone granice blueprintu; nie rozszerzono zakresu o UI ani formalny proces kursanta. |
| 2026-08-25 | Potwierdzono zgodnosc Etapu 2: osobny enrollment, dostep kursanta, token aktywacyjny i ledger kredytow nie naruszaja B2C ani obecnego `/nauka`. |
| 2026-08-28 | Po Etapach 3-5K i lokalnym pilocie ponownie potwierdzono granice: `/nauka` pozostaje niezalezne, wersja kursu i sesje sa snapshotowane, a pilot jest fail-closed przez aktywnosc kursu, dostep, allow-liste i flage srodowiskowa. |
| 2026-08-29 | Po Etapie 5M potwierdzono, że publiczne zgłoszenie OSK pozostaje wyłącznie bramą organizacyjną: nie daje dostępu do `/nauka`, nie aktywuje kursu ani pilota, a aktywność szkoły jest dodatkowo wymagana przez serwis zapisu kursanta. |
| 2026-08-29 | Po Etapie 5N potwierdzono, że operacyjna lista zapisów pozostaje scoped do jednej organizacji, surowy token nie trafia do listy ani audytu, a ponowne wydanie i anulowanie nie zmieniają globalnej flagi pilota, aktywności kursu ani semantyki `/nauka`. |
