# Skonsolidowany plan naprawczy auth, CSRF i błędów 419

Status: rdzeń P0/P1 wdrożony produkcyjnie 2026-06-18; trwa obserwacja metryk po deployu
Data: 2026-06-18
Priorytet: P0/P1
Zakres: auth, logout, długotrwałe sesje nauki, odpowiedzi JSON, monitoring i Redis

## 0. Stan realizacji

Zaimplementowane:

- endpoint `GET /auth/csrf-token`, kontrakt JSON `419` i bezpieczne logowanie
  diagnostyczne,
- wspólny helper CSRF z deduplikacją, obsługą BFCache i długiej nieaktywności,
- preflight dla loginu, rejestracji i wszystkich powierzchni logout,
- terminalny stan utraty sesji dla zwykłej nauki, PJM, egzaminu, znaków
  drogowych, publicznego demo i rankingu,
- zatrzymanie timerów, kolejek, pollingu i realtime bez automatycznego
  ponawiania mutacji,
- testy z rzeczywistą walidacją CSRF oraz testy frontendowych kontraktów.

Pozostaje poza tym branchem:

- obserwacja metryk `419` przez minimum 7 dni po deployu,
- rozszerzenie wygodnej obsługi `419` na pozostałe formularze profilu i
  administracyjne powierzchnie P2.

### 0.1 Wynik wdrożenia produkcyjnego 2026-06-18

- merge do `main`: `0c62438`,
- endpoint `/auth/csrf-token`: `200`, `Cache-Control: no-store, private`,
  `CF-Cache-Status: DYNAMIC`,
- dwa niezależne cookie jar otrzymały różne tokeny,
- stary token zwrócił `419` z kodem `CSRF_TOKEN_MISMATCH`,
- po odświeżeniu tokenu ten sam flow przeszedł warstwę CSRF i dotarł do
  walidacji logowania (`422` dla celowo błędnych danych),
- `ops:health-report` i `ops:smoke-test`: OK,
- backup plikowy:
  `/tmp/prawkonaraz-csrf-419-backup-20260618150745`.

Audyt Redis:

- Redis `7.0.15`, usługa aktywna,
- `maxmemory=0`, `maxmemory-policy=noeviction`,
- użycie pamięci około `1.24 MB`,
- RDB włączone przez `save 3600 1 300 100 60 10000`,
- ostatni background save: `ok`,
- AOF wyłączone,
- w logu systemd potwierdzone restarty 9 i 11 czerwca 2026,
- `db0` zawiera wygasające rekordy sesji; podczas deployu nie wykonano
  `FLUSHALL`, `FLUSHDB` ani restartu Redis.

Wniosek operatorski: eviction nie jest obecnie źródłem utraty sesji. Restart
Redis pozostaje możliwym źródłem wygaśnięcia otwartych kart, a nowy flow
obsługuje taki stan bez ponawiania mutacji.

## 1. Cel dokumentu

Ten dokument łączy ustalenia z:

- `docs/AUTH-CSRF-419-AUDIT-AND-REPAIR-PLAN.md`,
- `docs/AUTH-CSRF-419-DEEP-CODE-AUDIT.md`,
- `docs/AUTH-DEPLOYMENT-REPAIR-PLAN.md`,

z weryfikacją aktualnego kodu aplikacji.

Plan ma doprowadzić do stanu, w którym:

1. login, rejestracja i logout nie wysyłają tokenu z nieaktualnej sesji,
2. moduły nauki rozpoznają `419` jako utratę sesji, a nie zwykły błąd sieci,
3. żadna mutacja nauki nie jest automatycznie ponawiana po `419`,
4. użytkownik otrzymuje jednoznaczną ścieżkę odzyskania sesji,
5. testy naprawdę wykonują walidację CSRF,
6. produkcja pozwala odróżnić stary token od awarii Redis lub błędu wdrożenia.

Wyłączenie CSRF, dodawanie wyjątków dla tras auth albo zmiana logoutu na `GET`
nie są dopuszczalnymi rozwiązaniami.

## 2. Stan bazowy potwierdzony przed implementacją

### 2.1 Naprawa nie była jeszcze zaimplementowana

W aktualnym kodzie nie ma:

- trasy `GET /auth/csrf-token`,
- wspólnego helpera odświeżającego sesję i token,
- jawnej klasyfikacji `419` w `apiClient`,
- centralnego stanu UI „sesja wygasła”,
- monitoringu `TokenMismatchException` z metadanymi requestu.

### 2.2 Tokeny statyczne występują w kilku warstwach

Potwierdzone powierzchnie:

- Blade:
  - `resources/views/components/site/login-drawer.blade.php`,
  - `resources/views/components/site/register-drawer.blade.php`,
  - `resources/views/components/site/public-header.blade.php`.
- Vue/Inertia:
  - `resources/js/Components/Auth/LoginDrawer.vue`,
  - `resources/js/Components/Auth/RegisterDrawer.vue`,
  - `resources/js/Components/SiteHeader.vue`,
  - `resources/js/Pages/Auth/LogoutConfirm.vue`,
  - `resources/js/Pages/Auth/VerifyEmail.vue`.
- dodatkowe statyczne użycia tokenu:
  - `resources/views/questions-database/show.blade.php`,
  - konfiguracja frontendu panelu mediów w
    `app/Filament/Resources/Questions/Pages/ManageQuestionMedia.php`.

### 2.3 Odpowiedzi JSON poza `/api` nie mają gwarantowanego kontraktu błędu

`bootstrap/app.php` wymusza odpowiedzi JSON tylko dla ścieżek `api/*`.
Mutacje nauki, takie jak:

- `/nauka/teraz/odpowiedzi`,
- `/nauka/teraz/egzamin/stan`,
- `/nauka/znaki-drogowe/odpowiedzi/sync`,

również są wywoływane jako JSON, ale leżą poza prefiksem `/api`.

Po `419` frontend może więc otrzymać HTML zamiast stabilnego obiektu błędu.

### 2.4 Moduły nauki nie rozróżniają `419` od awarii sieci

Potwierdzone problemy:

- `resources/js/Pages/StudySessions/Show.vue`
  - pokazuje ogólny błąd zapisu,
  - nie odróżnia utraty sesji,
  - operuje na optymistycznie zmienionym stanie lokalnym.
- `resources/js/Pages/StudySessions/Exam.vue`
  - `syncExamState`, zapis odpowiedzi i zakończenie egzaminu nie mają
    dedykowanej obsługi błędu,
  - licznik może nadal próbować wykonywać kolejne synchronizacje.
- `resources/js/Pages/TrafficSignLearning/Show.vue`
  - po dowolnym błędzie odkłada batch z powrotem do kolejki,
  - kolejny debounce, przejście dalej lub unmount może wysłać go ponownie,
  - takie zachowanie po `419` jest niedopuszczalne.
- `resources/js/Pages/Session/Ranking.vue`
  - pokazuje techniczny komunikat `API request failed with status 419`,
  - polling, stream lub `pong` mogą nadal działać po utracie sesji.

### 2.5 Backend ma częściową idempotencję, ale nie pozwala ona na ogólny retry

Pozytywne zabezpieczenia:

- odpowiedź w zwykłej sesji nauki jest unikalna dla
  `study_session_id + question_id`,
- `StudySessionManager::recordAnswer()` zwraca istniejącą odpowiedź,
- odpowiedź rankingowa jest unikalna dla meczu, użytkownika i pytania,
- batch znaków drogowych akceptuje ponowienie identycznej już zapisanej
  odpowiedzi.

Nie jest to wystarczający powód do automatycznego retry, ponieważ:

- uruchomienie nowej sesji kończy poprzednią i tworzy nową,
- zakończenie sesji uruchamia dodatkowe synchronizacje i snapshoty,
- część mutacji nie ma klucza idempotencji,
- frontend nie wie, czy request dotarł do aplikacji przed zerwaniem połączenia.

Decyzja pozostaje bez zmian: mutacji nie ponawiamy automatycznie po `419`.

### 2.6 Testy aplikacji domyślnie nie sprawdzają CSRF

`tests/TestCase.php` wyłącza:

```php
ValidateCsrfToken::class
```

dla całej suite.

W Laravel 12.55.1 samo ponowne włączenie middleware nie wystarczy, ponieważ
middleware pomija walidację podczas testów jednostkowych. Potrzebny jest
dedykowany testowy subclass, który wymusi `runningUnitTests() === false`, albo
test HTTP poza środowiskiem `testing`.

### 2.7 Warstwa operatorska Redis wymaga osobnego potwierdzenia

Kod poprawnie rozdziela:

- sesje: Redis DB `0`,
- cache: Redis DB `1`.

To chroni sesje przed zwykłym czyszczeniem cache, ale nie gwarantuje trwałości.
Polityka `maxmemory` i eviction działa na poziomie całej instancji Redis, a nie
osobno dla DB. Należy potwierdzić persistence, limity pamięci i historię
restartów na produkcji.

## 3. Docelowa architektura

### 3.1 Endpoint sesyjny

Dodać:

`GET /auth/csrf-token`

Endpoint musi:

- działać w standardowej grupie `web`,
- być dostępny dla gościa i zalogowanego użytkownika,
- uruchamiać/odtwarzać sesję,
- zwracać aktualny `csrf_token()`,
- zwracać stan `authenticated`,
- ustawiać aktualne cookie `XSRF-TOKEN` przez middleware Laravel,
- mieć `Cache-Control: no-store, private`,
- nie być objęty cache Cloudflare ani cache aplikacyjnym.

Kontrakt:

```json
{
  "token": "aktualny-token-sesji",
  "authenticated": true
}
```

Trasa nie może być umieszczona wyłącznie w grupie `guest` ani `auth`.

### 3.2 Wspólny helper frontendowy

Utworzyć np.:

`resources/js/lib/csrfSession.ts`

Odpowiedzialność helpera:

- wykonanie `GET /auth/csrf-token` z `credentials: 'same-origin'`,
- walidacja odpowiedzi i nagłówka JSON,
- aktualizacja meta `csrf-token`,
- opcjonalna aktualizacja pola formularza `_token`,
- zwrócenie `authenticated`,
- deduplikacja równoległych odświeżeń przez jeden wspólny Promise,
- rozróżnienie:
  - błędu endpointu/sieci,
  - wygaśnięcia zalogowanej sesji,
  - nieprawidłowej odpowiedzi serwera.

Helper nie może:

- ponawiać requestu zmieniającego stan,
- uruchamiać stałego keepalive,
- ukrywać błędu `419`,
- przechowywać tokenu w `localStorage` lub `sessionStorage`.

### 3.3 Stabilny kontrakt błędu sesji

Zmienić obsługę wyjątków tak, aby requesty oczekujące JSON otrzymywały JSON
również poza `/api`.

Rekomendowany warunek:

```php
$request->is('api/*') || $request->expectsJson()
```

Dla `TokenMismatchException` zwracać status `419` i kod:

```json
{
  "error": {
    "code": "CSRF_TOKEN_MISMATCH",
    "message": "Sesja wygasła. Odśwież stronę lub zaloguj się ponownie."
  }
}
```

`apiClient` powinien udostępnić co najmniej:

- `isCsrfMismatchError(error)`,
- `isAuthenticationExpiredError(error)` dla `401` i kontrolowanych przypadków
  `419`.

## 4. Fazy wdrożenia

### Faza 0 — testy odtwarzające błąd

Priorytet: P0

Przed zmianą zachowania dodać testy, które obecnie zawodzą:

1. stary token jest odrzucany kodem `419`,
2. endpoint CSRF jeszcze nie istnieje,
3. odtworzenie sesji przez GET pozwala wykonać kolejny poprawny POST,
4. klient znaków drogowych po `419` nie może zachowywać się jak przy zwykłym
   błędzie sieci,
5. egzamin po `419` musi zatrzymać synchronizację czasu.

Pliki:

- nowy dedykowany test backendowy, np.
  `tests/Feature/Security/CsrfSessionRecoveryTest.php`,
- testowy middleware wymuszający realną walidację CSRF,
- testy Vitest dla helpera i `apiClient`.

Kryterium zakończenia:

- test poprawnego tokenu przechodzi,
- test starego tokenu otrzymuje prawdziwe `419`,
- test nie polega na globalnie wyłączonym CSRF.

### Faza 1 — endpoint, kontrakt błędu i monitoring

Priorytet: P0

Zakres:

1. dodać kontroler lub małą klasę invokable dla endpointu,
2. dodać trasę `GET /auth/csrf-token`,
3. dodać nagłówki `no-store, private`,
4. rozszerzyć regułę odpowiedzi JSON na `expectsJson()`,
5. dodać kontrolowany renderer `TokenMismatchException`,
6. dodać log zdarzenia `csrf_token_mismatch`.

Log może zawierać:

- request ID,
- metodę,
- path i nazwę trasy,
- ID użytkownika, jeśli istnieje,
- obecność cookie sesji,
- obecność `_token`,
- obecność `X-CSRF-TOKEN`,
- obecność `X-XSRF-TOKEN`,
- `User-Agent` w formie ograniczonej zgodnie z obecną polityką logowania.

Log nie może zawierać:

- wartości tokenu,
- wartości cookie sesji,
- pełnego payloadu formularza,
- hasła, emaila ani danych odpowiedzi użytkownika.

Kryterium zakończenia:

- endpoint działa dla gościa i użytkownika,
- dwa niezależne cookie jar nie współdzielą sesji/tokenów,
- odpowiedź nie jest cache’owana,
- `419` dla JSON ma stabilny kod błędu i request ID.

### Faza 2 — helper CSRF i krytyczne flow auth

Priorytet: P0

#### 2A. Blade login i rejestracja

W `resources/js/public-content.ts`:

1. zachować istniejące `@csrf`,
2. przechwycić submit formularza,
3. zablokować wielokrotne kliknięcie,
4. pobrać świeży token bezpośrednio przed submit,
5. podmienić `_token`,
6. wykonać natywny submit dopiero po sukcesie.

Przy awarii endpointu:

- nie wysyłać starego formularza,
- pokazać komunikat,
- udostępnić link do pełnej strony `/login` lub `/register`.

#### 2B. Vue/Inertia login i rejestracja

W:

- `resources/js/Components/Auth/LoginDrawer.vue`,
- `resources/js/Components/Auth/RegisterDrawer.vue`,

wykonać preflight przed `form.post()`.

Token nie musi być ręcznie dodawany do payloadu, ponieważ endpoint odświeży
cookie `XSRF-TOKEN`. Przycisk ma pozostać zablokowany przez cały preflight i
submit.

#### 2C. Wszystkie powierzchnie logout

Objąć:

- mobilny i desktopowy logout Blade,
- mobilny i desktopowy logout `SiteHeader.vue`,
- `LogoutConfirm.vue`,
- logout na ekranie `VerifyEmail.vue`.

Przed POST:

1. pobrać aktualny stan sesji,
2. jeśli `authenticated=true`, wysłać logout z aktualnym tokenem,
3. jeśli `authenticated=false`, nie wysyłać POST i przejść do bezpiecznego
   ekranu z komunikatem „Sesja już wygasła”.

Preferowane jest wydzielenie wspólnego komponentu/composable logout zamiast
kopiowania logiki.

Kryterium zakończenia:

- żadna powierzchnia logout nie czyta tokenu tylko raz przy inicjalizacji,
- podwójne kliknięcie nie wysyła dwóch requestów,
- awaria preflight nie kończy się wysłaniem starego tokenu.

### Faza 3 — bezpieczne zachowanie modułów nauki

Priorytet: P0

Nie wykonywać preflight przed każdą odpowiedzią. Zwiększałoby to opóźnienie,
liczbę zapisów sesji i sztucznie utrzymywało sesję przy życiu.

Zamiast tego:

- korzystać z aktualnego cookie `XSRF-TOKEN`,
- odświeżać stan po powrocie z BFCache lub długiej nieaktywności,
- traktować `419` jako stan terminalny bieżącego UI.

#### 3A. Wspólny stan „sesja wygasła”

Utworzyć mały współdzielony mechanizm, np. composable:

`resources/js/composables/useSessionExpiry.ts`

Powinien:

- ustawić terminalny stan wygaśnięcia,
- zablokować kolejne mutacje,
- zatrzymać timery, debounce, polling i kolejki,
- pokazać komunikat z akcją:
  - „Zaloguj się ponownie”,
  - „Odśwież stronę”,
  - dla publicznego demo: „Uruchom demo od początku”.

Nie powinien automatycznie odtwarzać lub wysyłać mutacji.

#### 3B. Zwykła nauka i PJM

W `resources/js/Pages/StudySessions/Show.vue`:

- wykrywać `419` w zapisie odpowiedzi, zmianie tematu, uruchomieniu kolejnej
  sesji i zakończeniu,
- zatrzymać auto-advance,
- nie wysyłać ponownie niepotwierdzonej odpowiedzi,
- zachować lokalnie wybraną odpowiedź wyłącznie jako kontekst wizualny,
- jasno oznaczyć, że nie została potwierdzona przez serwer.

#### 3C. Egzamin

W `resources/js/Pages/StudySessions/Exam.vue`:

- dodać obsługę błędów do `syncExamState`, zapisu odpowiedzi i zakończenia,
- po `419` zatrzymać countdown,
- zablokować odpowiedzi i przyciski kończące,
- zapobiec kolejnym wywołaniom synchronizacji przez interval,
- nie próbować rekonstruować wyniku po stronie klienta.

#### 3D. Znaki drogowe

W `resources/js/Pages/TrafficSignLearning/Show.vue`:

- w `sendPendingBatch()` rozróżnić `419`,
- po `419` nie wkładać batcha ponownie do aktywnej kolejki,
- przenieść go co najwyżej do niemutowalnego kontekstu diagnostycznego w
  pamięci komponentu,
- anulować debounce i automatyczne przejście,
- nie wykonywać flush na unmount po wygaśnięciu sesji.

Dla zwykłego błędu sieci można pozostawić ręczne ponowienie przez użytkownika,
ale nie automatyczny retry bez jego decyzji.

#### 3E. Ranking

W `resources/js/Pages/Session/Ranking.vue`:

- rozpoznać `401/419` jako utratę sesji,
- zatrzymać polling, stream i cykliczne `pong`,
- zablokować join/leave/ready/answer/abandon,
- pokazać komunikat użytkowy zamiast technicznego statusu HTTP.

Kryterium zakończenia:

- po pierwszym `419` liczba kolejnych mutacji z danego ekranu wynosi zero,
- żaden timer lub transport realtime nie próbuje pisać do backendu,
- UI nie twierdzi, że odpowiedź została zapisana.

### Faza 4 — BFCache, powrót do karty i pozostałe formularze

Priorytet: P1

#### 4A. BFCache

Na `pageshow` z `event.persisted === true`:

- oznaczyć token jako wymagający odświeżenia,
- dla auth/logout można odświeżyć go natychmiast,
- dla modułu zalogowanego sprawdzić stan sesji przed pierwszą kolejną mutacją.

Nie przeładowywać automatycznie całej strony.

#### 4B. Powrót po długiej nieaktywności

Po `visibilitychange` z powrotem do aktywnej karty:

- jeśli karta była nieaktywna przez ustalony próg, np. 15–30 minut, oznaczyć
  sesję jako wymagającą sprawdzenia,
- sprawdzenie wykonać przed następną mutacją,
- nie uruchamiać cyklicznego keepalive.

#### 4C. Pozostałe mutacje auth i profilu

Przejrzeć co najmniej:

- reset i potwierdzenie hasła,
- resend weryfikacji email,
- przejęcie konta tymczasowego,
- zmianę hasła i profilu,
- wysłanie linku usunięcia konta,
- podpisane potwierdzenie zmiany emaila i usunięcia konta,
- zaproszenia użytkowników.

Nie każdy formularz wymaga preflight przed każdym submit. Każdy musi jednak
otrzymać czytelne zachowanie po `419`, bez utraty danych wpisanych przez
użytkownika, jeśli można je bezpiecznie zachować.

#### 4D. Publiczne demo

Publiczne demo również używa sesji.

Po jej utracie:

- nie ponawiać odpowiedzi ani zakończenia,
- poinformować, że stan demo wygasł,
- zaoferować restart demo od początku.

### Faza 5 — dodatkowe powierzchnie administracyjne

Priorytet: P2

Usunąć zależność od tokenu odczytanego tylko raz w:

- inline editorach `resources/views/questions-database/show.blade.php`,
- frontendzie zarządzania mediami Filament.

Preferować wspólny `apiClient` i aktualne cookie `XSRF-TOKEN`. Dla długich sesji
administracyjnych również obsłużyć `401/419` jako konieczność ponownego
logowania, bez automatycznego powtarzania zapisu.

### Faza 6 — audyt produkcyjnego Redis

Priorytet: P1, równolegle z pracami frontendowymi

Na produkcji sprawdzić i zapisać w runbooku:

1. `INFO persistence`,
2. `CONFIG GET appendonly`,
3. `CONFIG GET save`,
4. `CONFIG GET maxmemory`,
5. `CONFIG GET maxmemory-policy`,
6. `INFO memory`,
7. historię restartów usługi Redis,
8. status ostatniego RDB/AOF,
9. czy deploy lub skrypty operatorskie nie wykonują `FLUSHALL`, `FLUSHDB 0`
   albo restartu bez potrzeby.

Ważne:

- rozdzielenie DB `0/1` nie izoluje sesji od globalnej polityki eviction,
- `cache:clear` nie powinno usuwać DB `0`,
- restart Redis musi mieć przewidywalne zachowanie persistence,
- alert powinien obejmować restart, błąd persistence i skok liczby `419`.

Nie zmieniać konfiguracji Redis bez:

- backupu konfiguracji,
- oceny zużycia pamięci,
- testu restartu na środowisku bez danych produkcyjnych,
- przygotowanego rollbacku.

## 5. Matryca testów

### 5.1 Backend

- endpoint zwraca token gościowi,
- endpoint zwraca token zalogowanemu,
- `authenticated` ma poprawną wartość,
- odpowiedź ma `no-store, private`,
- GET aktualizuje cookie `XSRF-TOKEN`,
- dwa cookie jar otrzymują różne sesje,
- poprawny token przepuszcza POST,
- stary token zwraca prawdziwe `419`,
- JSON request poza `/api` otrzymuje JSON dla `419`,
- log zawiera tylko informacje o obecności tokenów/cookie,
- endpoint nie jest dostępny z cache.

### 5.2 Frontend unit

- helper deduplikuje równoległe odświeżenia,
- helper aktualizuje meta i `_token`,
- helper odrzuca nieprawidłowy JSON,
- login i rejestracja nie wysyłają formularza po błędzie preflight,
- logout nie wysyła POST, gdy `authenticated=false`,
- `apiClient` rozpoznaje `419`,
- kolejka znaków nie odkłada batcha po `419`,
- egzamin zatrzymuje timer po `419`,
- ranking zatrzymuje transport realtime po utracie sesji.

### 5.3 E2E

Scenariusz starej karty:

1. otworzyć stronę,
2. zapisać początkowy token i cookie,
3. unieważnić rekord sesji,
4. wykonać login, rejestrację lub logout,
5. potwierdzić preflight i brak `419`.

Scenariusz nauki:

1. rozpocząć sesję,
2. unieważnić sesję serwerową,
3. wysłać odpowiedź,
4. potwierdzić pojedyncze `419`,
5. potwierdzić brak automatycznego retry,
6. potwierdzić zablokowanie dalszych mutacji.

Scenariusz znaków:

1. zbudować lokalny batch,
2. wymusić `419` przy synchronizacji,
3. poczekać dłużej niż debounce,
4. przejść dalej lub odmontować komponent,
5. potwierdzić, że batch nie został wysłany ponownie.

Scenariusz BFCache:

1. otworzyć publiczną stronę,
2. przejść dalej i wrócić historią,
3. zasymulować `pageshow.persisted`,
4. potwierdzić odświeżenie przed następną mutacją.

## 6. Kolejność pull requestów i deployów

Rekomendowane małe, odwracalne paczki:

1. PR 1: test wymuszający realne CSRF, endpoint i kontrakt błędu,
2. PR 2: helper frontendowy oraz Blade login/register,
3. PR 3: Vue login/register i wszystkie logouty,
4. PR 4: zwykła nauka, PJM i egzamin,
5. PR 5: znaki drogowe i publiczne demo,
6. PR 6: ranking oraz globalne zachowanie po wygaśnięciu sesji,
7. PR 7: BFCache, pozostałe formularze i powierzchnie administracyjne,
8. zmiana operatorska: Redis i monitoring, wdrażane osobno od zmian UI.

Nie łączyć zmiany konfiguracji Redis z pierwszym deployem kodu.

## 7. Rollout produkcyjny

Przed deployem:

- uruchomić dedykowane testy CSRF,
- uruchomić testy auth, profilu i nauki,
- uruchomić `npm run test:unit`,
- uruchomić `npm run build`,
- przygotować dwa cookie jar do smoke testu,
- potwierdzić brak cache dla endpointu.

Po deployu:

1. sprawdzić endpoint jako gość,
2. sprawdzić endpoint jako użytkownik,
3. wykonać login ze starej karty,
4. wykonać desktopowy i mobilny logout,
5. wymusić utratę sesji podczas nauki,
6. potwierdzić brak retry,
7. obserwować `419` per endpoint przez minimum 7 dni.

Metryki:

- liczba `419` per route/path,
- udział `419` z obecnym cookie sesji,
- liczba restartów Redis,
- błędy persistence Redis,
- liczba błędów endpointu `/auth/csrf-token`,
- liczba terminalnych stanów „sesja wygasła” w module nauki.

## 8. Rollback

Rollback kodu:

- przywrócić poprzedni release,
- wyczyścić cache tras, konfiguracji i widoków,
- pozostawić ochronę CSRF włączoną,
- nie dodawać tras do wyjątków middleware.

Jeśli problem dotyczy tylko nowego frontendu:

- wycofać integrację helpera,
- zachować istniejące `@csrf`,
- endpoint może pozostać, jeśli działa poprawnie i nie jest cache’owany.

Jeśli problem dotyczy Redis:

- zastosować przygotowany rollback konfiguracji,
- nie wykonywać `FLUSHALL`,
- po restarcie założyć, że otwarte karty mogą mieć stare tokeny,
- poinformować użytkowników o potrzebie odświeżenia lub ponownego logowania.

## 9. Definition of Done

Naprawa jest zakończona dopiero, gdy:

- świeży token jest pobierany przed krytycznymi formularzami auth i logout,
- wszystkie powierzchnie logout są pokryte,
- `419` ma stabilny kontrakt JSON dla requestów JSON,
- moduły nauki nie wykonują automatycznego retry po `419`,
- timery, kolejki i realtime zatrzymują się po utracie sesji,
- testy wykonują realną walidację CSRF,
- monitoring nie zapisuje sekretów,
- endpoint nie jest cache’owany,
- konfiguracja persistence i eviction Redis jest potwierdzona,
- produkcyjny test starej karty przechodzi,
- przez 7 dni po wdrożeniu nie występuje niewyjaśniona seria `419`.

## 10. Decyzje końcowe

- Nie wydłużamy `SESSION_LIFETIME` jako głównej naprawy.
- Nie wykonujemy preflight przed każdą odpowiedzią w nauce.
- Nie uruchamiamy stałego keepalive.
- Nie retry’ujemy automatycznie mutacji po `419`.
- Zachowujemy `@csrf` jako fallback bez JavaScript.
- Używamy jednego endpointu i jednego helpera zamiast wielu lokalnych
  implementacji.
- Traktujemy audyt Redis jako część naprawy, ale wdrażamy go niezależnie od
  zmian aplikacyjnych.
