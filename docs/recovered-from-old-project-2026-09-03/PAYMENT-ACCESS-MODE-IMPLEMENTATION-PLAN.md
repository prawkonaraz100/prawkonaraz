# Payment Access Mode - Plan Wdrozenia

Status: `wdrozone produkcyjnie 2026-08-20`
Data: `2026-08-20`  
Branch: `codex/payment-module-toggle`  
Zakres: dostep do nauki, checkout, rejestracja, cennik, panel administratora i PJM.

## 1. Cel

Administrator ma moc wlaczenia albo wylaczenia wymogu platnego dostepu bez
deployu, bez masowego nadawania dostepow i bez zmiany danych zakupowych.

To ustawienie steruje dostepem do produktu, a nie sama mozliwoscia zalozenia
konta.

## 2. Ustalony flow kursanta

### Gdy wymaganie platnosci jest wlaczone

1. Kursant zaklada konto.
2. Potwierdza adres e-mail.
3. Moze zobaczyc aktywacje dostepu i cennik.
4. Pelna nauka otwiera sie dopiero po zakupie albo innym istniejacym grancie
   dostepu.

Konto bez zakupu istnieje, ale nie ma pelnego dostepu do nauki. To jest
zamierzony i aktualnie poprawny model.

### Gdy wymaganie platnosci jest wylaczone

1. Kursant zaklada konto.
2. Potwierdza adres e-mail.
3. Pelna nauka jest od razu dostepna bez zakupu.

Przelaczenie nie tworzy zamowien ani grantow dostepu. Po ponownym wlaczeniu
wymogu platnosci konto bez prawdziwego grantu znow podlega standardowej bramce
dostepu.

## 3. Decyzje produktowe

- Rejestracja zawsze pozostaje darmowa.
- Nie budujemy checkoutu goscia ani platnosci przed utworzeniem konta.
- Pelny dostep w trybie darmowym wymaga potwierdzonego e-maila.
- Zablokowane konta, konta tymczasowe bez przejecia i konta wymagajace zmiany
  hasla nadal nie dostaja dostepu.
- Administratorzy, moderatorzy i konta testowe zachowuja obecne systemowe
  uprawnienia.
- Wylaczenie wymogu platnosci obejmuje cala pelna nauke, w tym dostepne kursy
  zawodowe, znaki, egzaminy, statystyki, bledne pytania, ranking i powtorki.
  Nadal obowiazuja niezalezne blokady kursu lub modulu.
- PJM jest czasowo zawieszony: nie pokazujemy go kursantom w rejestracji ani w
  nauce, a trasy kursanta maja zwracac kontrolowany komunikat o niedostepnosci.
  Nie usuwamy danych, filmow ani kodu PJM.
- Realny operator platnosci nie jest czescia tego wdrozenia. Obecny checkout
  korzysta z providera `sandbox`; integracja BLIK, kart i webhookow jest osobnym
  zadaniem.

## 4. Stan zbadany przed implementacja

### Zrobione

- [x] Utworzono izolowany branch `codex/payment-module-toggle` z czystego
  worktree.
- [x] Zmapowano rejestracje, weryfikacje e-maila, checkout, granty dostepu,
  bramki HTTP/API, kursy zawodowe, zaproszenia i darmowy wyjatek PJM.
- [x] Potwierdzono, ze obecny checkout jest sandboxem, a nie finalnym
  operatorem platnosci.
- [x] Uzgodniono, ze konto nie wymaga platnosci do zalozenia; platnosc steruje
  tylko dostepem do nauki.
- [x] Uzgodniono zawieszenie PJM dla kursantow.
- [x] Zapisano plan i kryteria akceptacji w tym dokumencie.
- [x] Wykonano preflight tras i bramek: web, API oraz kontynuacja sesji
  przechodza przez wspolne middleware dostepowe; PJM pozostaje jedynym
  niezaleznym wyjatkiem do kontrolowanego wstrzymania.
- [x] Uruchomiono pakiet regresji dostepu przed implementacja: `68` testow i
  `555` asercji przeszlo (`ProductAccessResolver`, bramki HTTP/API, PJM,
  checkout, kolekcje pytan i zaproszenia).

### Zastany kod

- `app/Support/ProductAccessResolver.php` jest centralnym zrodlem decyzji o
  dostepie do pelnego produktu.
- `app/Http/Middleware/EnsureProductAccess.php` i
  `app/Http/Middleware/EnsureStudySessionAccess.php` egzekwuja ta decyzje na
  trasach nauki i sesji.
- `app/Support/ProductCheckoutService.php`, `PurchaseOrder` oraz
  `ProductAccessGrant` obsluguja plan, zamowienie i dostep po zakupie.
- `app/Support/PjmFreeAccessResolver.php` daje dzis darmowy wyjatek PJM;
  musi przestac byc wejsciem dla kursanta.
- `app/Support/SystemSettingsStore.php` oraz tabela `system_settings` sa
  gotowym, utrwalonym magazynem ustawien runtime.
- Filament ma grupe nawigacji `Dostep`, dostepna tylko administratorom.
- Cennik znajduje sie w `PricingPageController` oraz
  `resources/views/pricing/index.blade.php`.
- Tekst rejestracji jest wspoldzielony przez `RegisterDrawer.vue` i propsy
  `HandleInertiaRequests`.

## 5. Docelowa architektura

### 5.1. Jedno zrodlo prawdy

Dodajemy `PaymentRequirementService` oparty o `SystemSettingsStore`.

- Klucz: `product_access.payment_requirement`.
- Payload: `{"requires_payment": true}`.
- Brak wpisu, blad odczytu albo niepoprawny payload oznacza
  `requires_payment=true` (fail closed).
- Odczyt jest cacheowany w shared cache na krotki czas; zapis z panelu
  uniewaznia cache od razu.
- Zmiana jest rejestrowana przez `AuditLogService` z administratorem, poprzednim
  stanem i nowym stanem.

Nie zapisujemy darmowych grantow dla kazdego uzytkownika. Globalny tryb otwarty
jest decyzja runtime, a nie zmiana danych klienta.

### 5.2. Kolejnosc decyzji dostepowej

`ProductAccessResolver` ma zachowac obecne blokady konta, a potem dodac nowy
tryb:

1. blokada konta, konto tymczasowe bez przejecia i wymuszona zmiana hasla;
2. systemowe konta administratora, moderatora i testowe;
3. jezeli `requires_payment=false` i e-mail jest potwierdzony: dostep
   `global_free_mode`;
4. obecne granty: zakup, moderator, zaproszenie;
5. brak dostepu.

Zrodlo `global_free_mode` nie ma rekordu `ProductAccessGrant`. Sluzy tylko do
jasnego pokazania decyzji w payloadach, logach i testach.

### 5.3. PJM

PJM nie moze pozostac drugim darmowym resolverem przy wlaczonym wymogu
platnosci. Dla kont kursantow:

- nie jest dostepny jako wybor rejestracji,
- nie jest prezentowany jako kafel lub start nauki,
- wejscie na trase PJM zwraca komunikat o czasowym zawieszeniu modulu,
- dane PJM i modele pozostaja nienaruszone.

Przy przyszlym powrocie do PJM bedzie potrzebna osobna decyzja produktowa i
osobny plan aktywacji, zamiast przypadkowego wlaczenia starego wyjatku.

### 5.4. Checkout i historyczne zamowienia

- Gdy `requires_payment=false`, nie tworzymy nowych zamowien i nie pokazujemy
  technicznego potwierdzenia sandboxa.
- Historyczne `pending`, `paid`, `cancelled` i `refunded` pozostaja w bazie bez
  modyfikacji.
- Potwierdzenie juz rozpoczetego, realnego zakupu w przyszlej integracji nie
  moze zgubic platnosci tylko dlatego, ze tryb otwarty zostal wlaczony po drodze.
  Ten przypadek zostanie obsluzony jawnie przy wdrozeniu operatora i webhookow.
- W aktualnym sandboxie endpoint potwierdzenia ma byc niedostepny w trybie
  otwartym, aby nie tworzyc sztucznych zakupow.

## 6. Panel administratora

Dodajemy strone Filament w grupie `Dostep`:

`Admin -> Dostep -> Platnosci i dostep`

Strona ma pokazywac:

- aktualny stan: `Platnosc wymagana` albo `Dostep otwarty`,
- krotki, nietechniczny opis skutku dla kursantow,
- osobna akcje wlaczenia i wylaczenia,
- wymagane potwierdzenie przed zmiana globalnego trybu,
- czas ostatniej zmiany i wpis w audycie.

Nie uzywamy autosave toggle, ktory moglby przypadkowo otworzyc caly produkt po
jednym kliknieciu.

## 7. Zmiany UX poza adminem

- Rejestracja: tekst po zalozeniu konta zalezy od trybu. W trybie platnym
  informuje o potwierdzeniu e-maila i zakupie; w otwartym tylko o potwierdzeniu
  e-maila i rozpoczeciu nauki.
- `/aktywuj-dostep`: w trybie otwartym nie powinien byc widoczny dla
  kwalifikujacego sie kursanta, bo resolver kieruje go do nauki.
- `/cennik`: w trybie otwartym zamiast ofert pokazuje stan otwartego dostepu i
  kieruje do logowania/rejestracji albo nauki. Nie publikuje wtedy `Offer` ani
  `OfferCatalog` w schema.
- Checkout: nowe wejscia sa blokowane po stronie serwera, nie tylko ukryte w UI.
- Cale menu i dashboard nie moga eksponowac PJM kursantowi.

## 8. Plan realizacji

### Etap 0 - Kontrakt i dokumentacja

Status: `zrobione`.

- [x] Branch i punkt powrotu.
- [x] Skan kodu oraz dotychczasowej dokumentacji.
- [x] Preflight bramek HTTP/API i testow regresji.
- [x] Uzgodnione decyzje biznesowe.
- [x] Kanoniczna dokumentacja wdrozenia.

### Etap 1 - Serwis ustawienia i centralna decyzja dostepu

Status: `zrobione`.

1. [x] Dodano `PaymentRequirementService` z bezpiecznym defaultem i cache
   invalidation.
2. [x] Podpieto tryb otwarty do `ProductAccessResolver`.
3. [x] Dodano czytelne zrodlo decyzji `global_free_mode`.
4. [x] Nie utworzono migracji ani grantow: istniejaca tabela `system_settings`
   jest dostepna w testach.
5. [x] Potwierdzono testem integracyjnym, ze tryb otwarty wpuszcza
   zweryfikowanego kursanta do `/nauka` i chronionego API sesji.

### Etap 2 - Zamkniecie PJM dla kursanta

Status: `zrobione`.

1. [x] Usunieto darmowa sciezke PJM z decyzji dostepowej kursanta.
2. [x] Usunieto PJM z wyboru w rejestracji oraz listy trybow API; istniejace
   widoki dashboardu nie otrzymuja juz dostepnego kafla PJM.
3. [x] Dodano kontrolowana odpowiedz dla starych bookmarkow: kursant wraca do
   dashboardu z komunikatem o czasowym zawieszeniu modulu.
4. [x] Zachowano dane PJM i testy silnika: korzystaja z konta systemowego,
   natomiast kursant nie moze kontynuowac starej sesji PJM.
5. [x] Zweryfikowano bramke dostepu, rejestracje, logowanie spolecznosciowe i
   produkcyjny build frontendu.

### Etap 3 - Checkout, aktywacja i cennik

Status: `zrobione`.

1. [x] Zablokowano tworzenie checkoutu w kontrolerze i serwisie oraz
   potwierdzenie sandboxa w trybie otwartym.
2. [x] Dostosowano `/cennik`, `/aktywuj-dostep` i teksty rejestracji.
3. [x] Usunieto w trybie otwartym plany i `OfferCatalog` z JSON-LD.
4. [x] Historyczne zamowienie `pending` pozostaje niezmienione; nie otrzymuje
   grantu po wlaczeniu dostepu otwartego.
5. [x] Zweryfikowano `37` testow i `240` asercji oraz produkcyjny build
   frontendu.

### Etap 4 - Panel administratora i audit

Status: `zrobione`.

1. [x] Dodano strone Filament `Dostep -> Platnosci i dostep`.
2. [x] Dodano dwie akcje z potwierdzeniem i opisem skutku; widoczna jest tylko
   akcja przeciwna do aktualnego stanu.
3. [x] Zapis zmiany uniewaznia cache i dodaje wpis audytu z poprzednim oraz
   nowym stanem.
4. [x] Test potwierdza, ze zwykly kursant nie moze otworzyc strony ani zmienic
   ustawienia.
5. [x] Zweryfikowano `41` testow i `266` asercji.

### Etap 5 - Testy regresji

Status: `zrobione`.

- [x] Resolver: tryb platny, tryb otwarty, konto niepotwierdzone, blokada,
  konto tymczasowe, zmiana hasla i konta systemowe.
- [x] Middleware web i API: klasyczna nauka, sesja, znaki, kursy zawodowe,
  lista blednych pytan, powtorki i trener pamieci.
- [x] PJM: brak wejscia kursanta niezaleznie od wybranego tracku lub
  bookmarka; konta systemowe zachowuja dostep serwisowy.
- [x] Checkout: brak nowego zamowienia w trybie otwartym, zachowanie obecnego
  flow w trybie platnym i brak zmiany historycznych zamowien.
- [x] Cennik i schema: brak ofert oraz `OfferCatalog` w trybie otwartym.
- [x] Admin: uprawnienia, potwierdzona zmiana, cache invalidation i audit.
- [x] Rejestracja klasyczna oraz Google: brak wyboru PJM i zachowanie
  weryfikacji e-maila.
- [x] Dodano regresje kursu zawodowego: zweryfikowany kursant w trybie
  otwartym dostaje dostep do aktywnego kursu desktopowego, ale mobilne API
  nadal odrzuca kurs desktop-only.
- [x] Wykonano dwa przekrojowe pakiety regresji: lacznie `158` testow i
  `1345` asercji przeszlo. Obejmowaly dostep, checkout, PJM, panel admina,
  kursy, znaki, bledne pytania, powtorki, zaproszenia oraz rejestracje.

### Etap 6 - Commit i deploy

Status: `zrobione`.

1. [x] Uruchomiono celowane testy oraz uzasadniony, szeroki zestaw regresji
   obejmujacy cala bramke dostepu i zalezne moduly.
2. [x] Wykonano produkcyjny build frontendu (`vue-tsc` i Vite).
3. [x] Zacommitowano kod, testy i dokumentacje jako
   `f63f04f7 feat: add runtime payment access mode`.
4. [x] Przed deployem potwierdzono zdrowie produkcji, wykonano nowy backup bazy
   `20260820-183751-prawkonarazpl-pgsql-pgsql-payment-access-mode-predeploy-20260820`
   oraz backup nadpisywanych plikow:
   `/tmp/prawkonaraz-payment-access-backup-20260820183826`.
5. [x] Wdrozono atomowo, wyczyszczono cache aplikacji, przeprowadzono
   `migrate --force` (brak nowych migracji), przeladowano PHP-FPM i uruchomiono
   serwerowy smoke test.
6. [x] Potwierdzono po deployu: health API, cennik i logowanie zwracaja `200`,
   nowa trasa admina jest zarejestrowana, a `requires_payment=true`. Nie
   przelaczano globalnego dostepu na produkcji testowo, aby nawet chwilowo nie
   otworzyc nauki wszystkim kursantom; oba stany sa pokryte testami regresji.

## 9. Kryteria akceptacji

- Administrator moze bez deployu wlaczyc i wylaczyc wymog platnosci.
- Konto mozna zawsze zalozyc, ale w trybie platnym nie uzyska pelnej nauki bez
  potwierdzonego e-maila i prawdziwego grantu.
- W trybie otwartym potwierdzone konto kursanta ma dostep do pelnej nauki bez
  powstania `PurchaseOrder` lub `ProductAccessGrant`.
- Po ponownym wlaczeniu platnosci darmowy dostep znika natychmiast, a zakupione
  i recznie nadane dostepy nadal dzialaja.
- PJM nie jest dostepny kursantom.
- Zadne istniejace zamowienie, grant, refund ani zaproszenie nie jest usuwane
  lub zmieniane przez przelaczenie trybu.
- Kazda zmiana trybu ma wpis w audycie.
- Serwer po deployu przechodzi smoke test, a produkcja ma przygotowany backup
  poprzedniego release'u.

## 10. Poza zakresem tego branchu

- finalny operator platnosci, BLIK, karty i szybkie przelewy;
- webhooki operatora, faktury oraz rozliczenia;
- zwroty obslugiwane przez operatora;
- przywrocenie lub dalszy rozwoj PJM;
- masowa zmiana historycznych grantow albo zamowien.
