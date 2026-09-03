# Etap 5N - operacyjna obsluga zapisow kursantow

**Stan:** zakonczony lokalnie 2026-08-29. Brak deployu.

## Cel

Etap 5J pozwalal utworzyc pojedynczy zapis kursanta i jednorazowo przekazac
link aktywacyjny. Etap 5N domyka codzienna obsluge tego zapisu bez otwierania
globalnej listy wszystkich kursantow:

1. wlasciciel OSK widzi tylko zapisy swojej szkoly;
2. administrator platformy najpierw wskazuje jedna szkole;
3. oba panele maja liste, filtrowanie, ponowne wydanie linku i anulowanie
   oczekujacego zapisu;
4. zadna akcja nie zapisuje surowego tokenu ani nie wlacza pilota.

## Zakres wykonany

### Lista i skala

- `OrganizationEnrollmentDirectoryService` zwraca maksymalnie 20 zapisow na
  strone przez `simplePaginate`, bez kosztownego liczenia calej populacji.
- Lista jest zawężona do jednego OSK przed filtrowaniem. Wlasciciel moze
  wybrac wyłącznie szkole, do ktorej ma uprawnienie zarzadzania kursantami;
  administrator widzi dane dopiero po wskazaniu konkretnej szkoly.
- Dostepne sa filtry: kurs, stan dostepu oraz imie, nazwisko lub e-mail.
  Wyszukiwanie tekstowe zaczyna dzialac od trzech znakow, aby przypadkowe
  wpisanie jednej litery nie powodowalo szerokiego zapytania.
- Lista pokazuje tylko dane operacyjne: kursanta, kurs i wersje, stan
  dostepu oraz daty. Nie pokazuje tokenu, jego hasha ani linku aktywacyjnego.

### Ponowne wydanie linku

- Dzialanie jest dostepne tylko dla zapisu `PENDING`.
- Poprzedni niewykorzystany link jest odwolany w tej samej transakcji, a nowy
  link jest pokazany tylko raz bezposrednio po akcji.
- Wlasciciel OSK i administrator platformy maja oddzielne, audytowalne
  kanaly. Log audytu zapisuje odpowiednio akcje
  `osk.course_activation_token.reissued` oraz
  `osk.course_activation_token.reissued_by_platform_admin`.
- Nie ma automatycznej wysylki e-mail, kodu QR ani ukrytego zapisu surowego
  tokenu w bazie albo w audycie.

### Anulowanie oczekujacego zapisu

- Anulowac mozna tylko zapis `PENDING`; dla aktywnego dostepu nie ma tej
  akcji w UI ani w serwisie.
- Operacja odwoluje niewykorzystane linki, ustawia dostep na `CANCELLED` i
  zwalnia w ledgerze zarezerwowane miejsce.
- Dzialanie wlasciciela zapisuje audit
  `osk.student_course_access.cancelled`, a administratora
  `osk.student_course_access.cancelled_by_platform_admin`.

## Granice

Etap 5N nie:

- nie zmienia globalnej flagi `OSK_THEORY_LEARNING_PILOT_ENABLED`, allow-listy
  ani aktywnosci kursu;
- nie tworzy dostepu do `/nauka` ani nie miesza go z nauka teorii OSK;
- nie dodaje automatycznej wysylki linku, QR, SMS/OTP, platnosci ani masowych
  operacji dla wielu kursantow;
- nie usuwa historii aktywnego dostepu ani nie tworzy formalnego evidence,
  ukonczenia kursu, assessmentu lub egzaminu wewnetrznego.

Masowe akcje wymagaja osobnego etapu: kolejki, limitow, szczegolowego audytu
per kursant i kontroli skutkow czesciowych. Nie nalezy ich doklejac do
pojedynczego formularza ani ladowac populacji wszystkich szkol w jednym
widoku.

## Potwierdzenia lokalne

- `EnrollmentCreditsTest`: 18 testow przeszlo, w tym uniewaznienie starego
  linku, audit bez sekretu, anulowanie i zwolnienie miejsca.
- `OrganizationEnrollmentManagementTest`: 8 testow / 102 asercje przeszlo,
  w tym tenant isolation, akcje wlasciciela i administratora oraz nowe trasy
  Ziggy.
- `npm run build` w kontenerze Vite przeszedl (`vue-tsc` i produkcyjny build).
- `git diff --check` przeszedl.
- Szerszy przebieg onboardingu trafila raz na celowy limit `429` publicznego
  formularza. Dokladnie ten test uruchomiony osobno przeszedl; limit pozostaje
  bez zmian jako ochrona formularza publicznego.

## Wynik manual smoke lokalnego (2026-08-29)

Pelny przebieg wykonano w przegladarce na odrebnych, sztucznych danych lokalnych
swiezo zatwierdzonego OSK z pakietem pieciu miejsc.

1. Wlasciciel utworzyl dwa zapisy do aktywnej wersji kursu B. Pula zmienila sie
   z `5` na `3` miejsca.
2. Dla pierwszego zapisu wydano nowy link. Poprzedni link zostal odwolany i
   zwrocil `404`; surowy token nie zostal zapisany w dokumentacji ani audycie.
3. Drugi zapis zostal anulowany po widocznym potwierdzeniu. Stan zmienil sie na
   `CANCELLED`, akcje zniknely z listy, a pula wrocila z `3` do `4` miejsc.
4. Pierwszy kursant aktywowal dostep przez nowy link. Wymagana allow-lista
   pilota zostala wlaczona tylko dla tego jednego enrollmentu przez serwis
   domenowy; kursant zobaczyl kurs, plan, player i przeszedl do kolejnej lekcji
   bez wymuszonego zakonczenia sesji.
5. Drugi kursant nie zobaczyl aktywnego kursu OSK, a bezposredni adres kursu
   zwrocil `404`. Wlasciciel na koncu widzial w swoim katalogu jeden zapis
   `ACTIVE`, jeden `CANCELLED` i cztery dostepne miejsca.

Po smoke poprawiono ekran startowy wlasciciela OSK. `PostAuthRedirectController`
kieruje teraz kazdego zweryfikowanego wlasciciela lub operatora aktywnej,
zatwierdzonej szkoly z prawami `STUDENT_MANAGE` i `ACCESS_MANAGE` do
`/osk/zarzadzanie/kursanci`, takze gdy szkola zostala utworzona recznie poza
publicznym onboardingiem. Oczekujace i odrzucone zgloszenie nadal prowadzi do
statusu zgloszenia, a kursant z aktywnym dostepem OSK zachowuje obecne wejscie
do `/osk/nauka`. Regresje `DashboardTest` i `OrganizationOnboardingTest`
potwierdzily te granice.

## Nastepna bezpieczna decyzja

Przed projektowaniem notyfikacji nalezy wybrac kanal dostarczenia linku:
reczne bezpieczne przekazanie, e-mail albo QR. Dopiero po tej decyzji mozna
planowac maly, oddzielny etap techniczny. Nie jest to zgoda na wlaczenie pilota
produkcyjnego ani na deploy.
