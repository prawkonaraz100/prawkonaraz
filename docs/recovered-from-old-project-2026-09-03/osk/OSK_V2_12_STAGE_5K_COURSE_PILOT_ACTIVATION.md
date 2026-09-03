# OSK V2.12 - Etap 5K: aktywacja kursu do kontrolowanego pilota

**Status:** zakonczony lokalnie 2026-08-28. Wykonano kontrolowany pilot z jednym testowym OSK i jednym testowym kursantem dla zrodlowej wersji B. Brak deployu.

## Po co jest ten etap

Opublikowanie tresci nie moze samo otwierac kursu dla kursantow. Wczesniej
brakowalo jednej jawnej decyzji miedzy "wersja V1 jest gotowa" a "mozna dodac
kursanta". Etap 5K dodaje te decyzje jako osobna, audytowalna brame.

Nie jest to globalny przelacznik produktu i nie dodaje zadnego kursanta.

## Piec bram dostepu

Kursant widzi player nauki teorii tylko wtedy, gdy jednoczesnie:

1. kurs ma zatwierdzona, opublikowana i zamrozona wersje z kompletnym snapshotem programu;
2. administrator wlaczyl konkretny `Course` do kontrolowanego pilota (`is_active=true`);
3. kursant ma w tym OSK aktywny `StudentCourseAccess`;
4. administrator wlaczyl allow-liste dokladnie tego `CourseEnrollment`;
5. srodowiskowa brama `OSK_THEORY_LEARNING_PILOT_ENABLED` jest wlaczona.

Kazda brakujaca brama jest fail-closed. Player, endpointy sesji i postepu nie
sa dostepne dla niegotowego enrollmentu.

## Implementacja

`CourseAvailabilityService` jest jedyna sciezka zmiany aktywnosci kursu dla
pilota. W transakcji blokuje kurs oraz jego opublikowane wersje i sprawdza:

- rzeczywiste uprawnienie administratora platformy;
- zatwierdzenie tresci;
- status `PUBLISHED` i zamrozenie wersji;
- kompletny snapshot `OSK_COURSE_PROGRAM_V1` z modulem, lekcja i krokiem.

Akcja zapisuje audit:

- `osk.course.activated_for_pilot`;
- `osk.course.deactivated_for_pilot`.

Panel `/admin/programy-nauki-osk` pokazuje wprost:

- **Wlacz kurs do lokalnego pilota** - gdy kurs ma gotowa tresc;
- **Wstrzymaj kurs** - gdy kurs jest aktywny.

Wstrzymanie kursu blokuje nowe zapisy oraz player dla istniejacych
enrollmentow. Nie usuwa historii, tokenow, sesji, postepu ani allow-listy;
ponowne wlaczenie kursu przywraca tylko techniczna mozliwosc dalszego
kontrolowanego dostepu.

## Stan zrodlowego kursu B przed pilotem

Kurs `osk-teoria-b-pierwszenstwo-zrodlowy` ma lokalnie:

- opublikowana, zamrozona wersje `V1`;
- zatwierdzona tresc z przypisanymi zrodlami;
- kompletny snapshot programu;
- brak enrollmentu dla tej wersji przed rozpoczeciem tego etapu.

Lokalna baza zawiera tez starsze dane demonstracyjne kategorii B. Sa oznaczone
jako demo i nie sa dowodem wykonania tego pilota. Zawsze sprawdz stan flagi
srodowiskowej oraz allow-listy konkretnego enrollmentu zamiast wyciagac wnioski
z samej obecnosci tych danych.

## Wykonany pilot lokalny

Do pilota uzyto tylko lokalnych danych oznaczonych jako testowe:

- kurs: `osk-teoria-b-pierwszenstwo-zrodlowy`, wersja `V1`;
- partner: `OSK Zrodlowy Kurs B - PILOT LOKALNY`, z metadata `demo_only=true`;
- jeden kursant oznaczony jako `is_test_account=true`;
- jeden enrollment z aktywnym dostepem i pojedyncza allow-lista.

W tej lokalnej bazie pozostaja trzy starsze allow-listy niezaleznych danych
demonstracyjnych. Nie byly zmieniane przez ten etap. Dla zrodlowego kursu B
istnieje dokladnie jedna aktywna allow-lista, nalezaca do opisanego wyzej
testowego enrollmentu.

Kolejnosc zostala zachowana przez serwisy domenowe: aktywacja kursu, utworzenie
organizacji, zapis przez administratora platformy, aktywacja tokenu i dopuszczenie
enrollmentu. Audit zawiera osobne rekordy dla kazdego z tych krokow. Surowy token
nie zostal zapisany w dokumentacji ani audycie.

Flaga `OSK_THEORY_LEARNING_PILOT_ENABLED` byla juz wlaczona w tym lokalnym
srodowisku przed rozpoczeciem pilota. Ten etap jej nie zmienial. W kazdym innym
srodowisku jej stan trzeba odczytac niezaleznie; wartosc produkcyjna nadal ma
pozostac `false` do osobnej decyzji wdrozeniowej.

## Kontrolowany scenariusz lokalny

1. Administrator wlacza tylko zrodlowy kurs B przez `CourseAvailabilityService` albo przycisk w panelu.
2. Tworzony jest jeden nowy, wyraznie oznaczony lokalny partner OSK oraz jedno konto testowe kursanta.
3. Administrator platformy tworzy zapis przez `OrganizationEnrollmentService`; powstaje `PENDING` access, rezerwacja jednego kredytu i hashowany token.
4. Kursant aktywuje token przez `StudentCourseActivationService`; access przechodzi na `ACTIVE`, a kredyt zostaje skonsumowany.
5. Administrator wlacza allow-liste tylko tego enrollmentu przez `TheoryLearningPilotAccessService`.
6. Smoke sprawdza pozytywny dostep tego kursanta, brak dostepu u drugiego kursanta oraz brak zmiany w istniejacym `/nauka`.

Nie wolno tworzyc tych danych bezposrednimi aktualizacjami SQL. Surowy token
nie trafia do dokumentacji, audit logu ani odpowiedzi dla uzytkownika.

## Szybki rollback

Kolejnosc od najszerszej do najwezszej:

1. ustawic `OSK_THEORY_LEARNING_PILOT_ENABLED=false`, gdy trzeba natychmiast zatrzymac caly lokalny pilot;
2. wylaczyc allow-liste konkretnego enrollmentu w `/admin/pilot-nauki-teorii-osk`;
3. wstrzymac konkretny kurs w `/admin/programy-nauki-osk`.

Rollback nie usuwa zadnych zapisow zrodlowych ani audytu. Dane sa potrzebne do
odtworzenia tego, co zostalo sprawdzone.

## Weryfikacja bramy i pilota

Lokalnie przeszly przed wykonaniem pilota:

```text
tests/Feature/Osk/CourseAvailabilityTest.php                 3 testy
tests/Feature/Admin/OskTheoryLearningProgramsPageTest.php   11 testow / 70 asercji
tests/Feature/Admin/OskTheoryLearningPilotPageTest.php       7 testow / 41 asercji
```

Pokryte sa: aktywacja, wstrzymanie, audit, blokada niezatwierdzonej tresci,
blokada allow-listy dla wstrzymanego kursu oraz UI administratora.

Wykonany smoke HTTP lokalnego pilota:

- `/osk/nauka` kursanta: `200`, komponent `Osk/TheoryLearning/Index`, dokladnie jeden kurs zrodlowy;
- `/osk/nauka/{enrollment}`: `200`;
- pierwszy player lekcji: `200`, komponent `Osk/TheoryLearning/LessonPlayer`;
- start sesji: `201`, status `OPEN`;
- heartbeat: `200` z oczekiwanym `THROTTLED` wyslany bezposrednio po starcie;
- zapis pierwszego kroku: `200`;
- jawne zamkniecie sesji: `200`, status `CLOSED`;
- operator OSK bez enrollmentu: bezposredni adres kursanta zwraca `404`;
- istniejace `/nauka`: `200` w tej samej sesji kursanta.

Sesja, krok postepu i audit pozostaly w lokalnej bazie jako odtwarzalny slad
pilota. Nie utworzono formalnego ukonczenia ani evidence.

## Granice

Etap nie dodaje formalnego evidence, ukonczenia, assessmentu, egzaminu
wewnetrznego, PAPER, wysylki e-maila, QR ani deployu. `/nauka` pozostaje
niezaleznym modulem pytan egzaminacyjnych.

## Kontrola po pilocie - 2026-08-29, lokalnie

Ponownie sprawdzono pelny, lokalny przeplyw wlasciciela OSK oraz kursanta:

1. Wlasciciel OSK otwiera `/osk/zarzadzanie/kursanci`, widzi wylacznie wlasna
   szkole i dostepne miejsca, a nastepnie tworzy zapis kursanta do aktywnego
   kursu B.
2. Przy utworzeniu zapisu saldo miejsc zmienia sie z `5` na `4`. To rezerwacja
   jednego miejsca; aktywacja kursanta domyka ja zapisem `CONSUME` o ilosci `0`,
   wiec saldo nie jest odjete drugi raz.
3. Aktywacja jednorazowym linkiem tworzy dostep `ACTIVE` na 90 dni. Przed
   potwierdzeniem e-maila kursant nie wejdzie do nauki, a po wlaczeniu
   allow-listy widzi tylko swoj kurs OSK.
4. Wykryto i naprawiono lokalnie brak domyslnego przekierowania po zwyklym
   logowaniu: kursant z gotowym dostepem OSK byl wysylany na B2C
   `/aktywuj-dostep`. `PostAuthRedirectController` korzysta teraz z tego samego
   `TheoryLearningAccessService` co player, wiec sprawdza wszystkie piec bram
   dostepu i kieruje takiego kursanta do `/osk/nauka`. B2C i PJM zachowuja
   dotychczasowe przekierowania.

W chwili wykonania tego pilota brakowało publicznego onboardingu firmy. Ten
brak został później częściowo domknięty przez Etap 5M: istnieje formularz
szkoły, walidacja NIP i telefonu, zwykłe potwierdzenie e-maila oraz ręczna
weryfikacja administratora. Etap 5N dodał następnie listę zapisanych
kursantów oraz ponowne wydawanie linku i anulowanie oczekującego zapisu w
panelach właściciela i administratora. Nadal nie ma automatycznej wysyłki
linku e-mailem/QR ani masowych operacji. Brak deployu.

## Ponowiony mały pilot - 2026-08-30, lokalnie

Do powtarzalnego smoke'u użyto wyłącznie wbudowanej komendy
`osk:seed-demo-theory-pilot`. Tworzy lub odświeża ona osobny, fikcyjny zestaw
`demo_only`: jedno OSK, jednego kursanta i kurs kategorii B. Nie działa poza
środowiskiem `local` lub `testing`, nie wysyła e-maila i nie zmienia flagi
środowiskowej pilota.

Po uruchomieniu potwierdzono pięć bram dla aktualnego enrollmentu: aktywny
kurs, opublikowaną i zamrożoną wersję z poprawnym snapshotem, aktywny
dostęp kursanta, pojedynczy wpis allow-listy oraz lokalnie włączoną bramę
środowiskową. Readiness nie zwrócił żadnej blokady.

Ręczny smoke w przeglądarce potwierdził:

1. kursant demonstracyjny po zalogowaniu trafia do `/osk/nauka`, widzi swój
   kurs, plan i player;
2. uruchomienie lekcji rozpoczyna sesję dopiero po świadomym kliknięciu;
3. na końcu całego programu główny przycisk zamyka sesję i prowadzi do planu
   kursu, bez tworzenia formalnego ukończenia ani evidence;
4. operator OSK bez własnego enrollmentu widzi panel zarządzania, ale
   bezpośredni adres kursanta `/osk/nauka/{enrollment}` zwraca `404`;
5. istniejące `/nauka` nadal zwraca `200` i pozostaje niezależnym modułem
   pytań egzaminacyjnych.

Po smoke'u przeszła regresja obejmująca player, ciągły flow lekcji, kursor
postępu, kredyty i oba panele zapisów oraz dashboard: **61 testów / 674
asercje**.

Nie wykorzystano realnych danych, tokenów ani skrzynek e-mail. Nie wykonano
deployu, nie zmieniono produkcyjnej flagi i nie aktywowano kursu dla
prawdziwego OSK.
