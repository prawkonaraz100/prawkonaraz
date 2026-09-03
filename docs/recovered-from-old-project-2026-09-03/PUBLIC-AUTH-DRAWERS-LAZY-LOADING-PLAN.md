# Lazy loading publicznych drawerów logowania i rejestracji

Ostatnia aktualizacja: `2026-07-18`
Status dokumentu: `kanoniczny plan wdrożenia i rejestr postępu`
Status prac: `wdrożone produkcyjnie, flaga lazy aktywna`

Dokument opisuje bezpieczne usunięcie pełnych formularzy logowania i
rejestracji z początkowego HTML publicznych stron. Zmiana ma zmniejszyć HTML,
DOM i liczbę zasobów pobieranych przed pierwszą interakcją bez zmiany procesu
logowania, rejestracji, walidacji, Google Identity ani zabezpieczeń CSRF.

Nie zapisujemy tutaj sekretów, tokenów OAuth ani danych użytkowników.

## 1. Cel

Po wdrożeniu:

- formularz logowania nie znajduje się w początkowym HTML zamkniętej strony,
- formularz rejestracji nie znajduje się w początkowym HTML zamkniętej strony,
- kliknięcie linku `/login` lub `/register` otwiera odpowiedni drawer,
- pełne strony `/login` i `/register` pozostają działającym fallbackiem,
- walidacja, błędy, `old()`, komunikaty sesji i CSRF działają jak wcześniej,
- Google Identity i One Tap zachowują dotychczasowy kontrakt,
- rejestracja pobiera listę kategorii dopiero wtedy, gdy jest potrzebna,
- ilustracja i Google Identity nie są pobierane na publicznej stronie pytania
  przed interakcją z logowaniem,
- zmiana może zostać wyłączona na produkcji bez rollbacku kodu.

## 2. Stan potwierdzony przed implementacją

Audyt wykonano na produkcyjnej stronie pytania `6251`:

`https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy/c/pytanie/6251/czy-w-tej-sytuacji-zachowujac-szczegolna-ostroznosc-masz-prawo-wjechac-na-przejscie-dla-pieszych`

### 2.1 Początkowy dokument

- początkowy HTML ma około `178 204 B`,
- HTML obu drawerów ma około `36 692 B`,
- drawery stanowią około `20,6%` nieskompresowanego HTML,
- dokument przesyłany po kompresji ma około `25 669 B`,
- szacowana oszczędność w skompresowanym HTML wynosi około `3,7 KB`,
- DOM ma `705` elementów,
- login dodaje `67` elementów potomnych,
- rejestracja dodaje `92` elementy potomne,
- w początkowym DOM są `4` formularze, `3` pola hasła i `2` pola e-mail.

### 2.2 Zasoby i praca serwera

- ukryta ilustracja `login-panel-illustration` ma `63 079 B` i jest pobierana
  przed otwarciem drawera,
- przed kliknięciem ładowany jest klient Google Identity, jego style i iframe
  przycisku,
- `public-content` ma obecnie około `14 KB` nieskompresowanego JavaScriptu,
- komponent rejestracji wykonuje `StudyContextService::activeCategories()`
  podczas renderowania formularza,
- middleware `HandleInertiaRequests` przed zmianą również rozwiązywał listę
  kategorii od razu, także dla publicznych stron Blade; po zmianie współdzielona
  wartość Inertia jest closure i wykonuje zapytanie dopiero podczas odpowiedzi
  Inertia,
- `activeCategories()` wykonuje zapytanie pobierające aktywne kategorie,
- Service Worker pomija ścieżki zaczynające się od `/auth/`, więc przyszły
  endpoint fragmentów nie zostanie zapisany w cache PWA.

### 2.3 Przyczyna

Layout `resources/views/layouts/public-content.blade.php` bezwarunkowo renderuje:

```blade
<x-site.login-drawer />
<x-site.register-drawer />
```

Komponenty są ukrywane klasą `hidden`, ale ich HTML, pola formularzy i dane
nadal są wysyłane w pierwszej odpowiedzi.

Obsługa JavaScript znajduje się głównie w:

- `resources/js/public-content.ts`,
- `resources/js/lib/csrfSession.ts`,
- `resources/js/lib/googleIdentity.ts`.

`setupCsrfRefreshForms()` obsługuje również formularze wylogowania w publicznym
headerze. Nie może więc zostać w całości przeniesione do modułu drawerów.

### 2.4 Wynik po implementacji lokalnej

Pomiar wykonano na tej samej lokalnej stronie pytania `6251`, na aktualnym
kodzie i tej samej bazie danych:

- przy fladze `false`: `134 328 B` HTML, `4` formularze i `3` pola hasła,
- przy fladze `true`: `99 012 B` HTML, `2` formularze strony i `0` pól hasła,
- usunięto `35 316 B` z początkowego HTML (`26,3%` lokalnego dokumentu),
- przed kliknięciem nie ma loginu, rejestracji, ilustracji ani Google GSI,
- główny chunk `public-content` ma `7,83 KB` (`3,15 KB` gzip),
- osobny chunk `authDrawers` ma `8,54 KB` (`3,27 KB` gzip),
- ilustracja `63,08 KB` jest zależnością osobnego chunku,
- Playwright wykonał dokładnie po jednym żądaniu dla fragmentu loginu i
  rejestracji mimo ponownego otwarcia loginu,
- Playwright potwierdził pojedyncze przypięcie CSRF, fallback do `/login` po
  błędzie sieci i poprawne zakończenie szybkiego wyścigu na panelu rejestracji.

### 2.5 Wynik wdrożenia produkcyjnego

Deploy wykonano `2026-07-18` z commita `a9296923` i czystego artefaktu Vite:

- SHA-256 paczki: `9eca801cadf15b076a0ad5da71230760b796c5dbc384b9f3e14c46d44d5d48d2`,
- świeży backup PostgreSQL:
  `backups/database/2026/07/20260717-224150-prawkonarazpl-pgsql-pgsql-pre-deploy-public-auth-drawers.sql.gz`,
- backup plików i `.env`:
  `/tmp/prawkonaraz-auth-drawers-backup-20260718003824`,
- rollout rozpoczęto z efektywną flagą `false`, a endpointy obu fragmentów
  zwróciły `200`, właściwy nagłówek i `Cache-Control: private, no-store`,
- po włączeniu `PUBLIC_AUTH_DRAWERS_LAZY=true` HTML pytania `6251` zmniejszył
  się z `178 572 B` do `139 477 B`, czyli o `39 095 B` (`21,9%`),
- początkowy HTML nie zawiera pól hasła, drawerów, ilustracji ani Google GSI,
- produkcyjny Playwright zakończył się statusem `ok`; potwierdził po jednym
  żądaniu fragmentu, jedno odświeżenie CSRF, fallback `/login` i poprawny
  wynik wyścigu na rejestracji,
- `ops:health-report` i `ops:smoke-test` zakończyły się statusem `OK` przed i
  po aktywacji flagi,
- logi od momentu deployu nie zawierały nowych odpowiedzi `419`, `422`, `429`,
  `500`, błędów Laravel ani ostrzeżeń PHP-FPM związanych z wdrożeniem.

## 3. Decyzje architektoniczne

### 3.1 Wybrany wariant

Stosujemy połączenie:

- fragmentów Blade pobieranych na żądanie,
- lekkiego loadera w podstawowym `public-content.ts`,
- dynamicznego importu modułu obsługi drawerów,
- serwerowego renderowania właściwego drawera tylko po błędzie walidacji lub
  komunikacie wymagającym jego natychmiastowego otwarcia.

### 3.2 Czego nie robimy

- nie przenosimy publicznych drawerów do Vue,
- nie dołączamy Vue i Inertia do publicznych stron Blade,
- nie zapisujemy pełnego HTML formularzy w kodzie TypeScript,
- nie używamy elementu `<template>` jako pozornego lazy loadingu,
- nie zmieniamy kontrolerów wykonujących logowanie i rejestrację,
- nie zmieniamy reguł walidacji ani redirectów po POST,
- nie cache'ujemy fragmentów zawierających token CSRF,
- nie pobieramy obu drawerów, jeżeli użytkownik otworzył tylko jeden.

### 3.3 Progressive enhancement

Linki w headerze pozostają zwykłymi linkami do `/login` i `/register`.
JavaScript przechwytuje wyłącznie zwykłe kliknięcie lewym przyciskiem.

Jeżeli import modułu albo pobranie fragmentu nie powiedzie się, przeglądarka
przechodzi na pełną stronę logowania lub rejestracji. `Ctrl`, `Shift`, `Meta`,
środkowy przycisk i otwarcie w nowej karcie zachowują standardowe działanie.

## 4. Docelowy przepływ

1. Publiczna strona zwraca mały punkt montowania bez formularzy.
2. Użytkownik klika link logowania albo rejestracji.
3. Loader dynamicznie importuje moduł `authDrawers`.
4. Moduł pobiera tylko żądany fragment z `/auth/drawers/{drawer}`.
5. Odpowiedź jest weryfikowana na podstawie statusu, typu treści i nagłówka
   identyfikującego drawer.
6. Fragment jest wstawiany do punktu montowania.
7. Obsługa CSRF, Google, haseł, kategorii i zamykania jest przypinana tylko do
   nowego fragmentu.
8. Fragment pozostaje w DOM po pierwszym pobraniu i jest ponownie używany przy
   kolejnych otwarciach na tej samej stronie.
9. Przy błędzie loader przechodzi na pełne `/login` albo `/register`.

## 5. Plan implementacji i checklista

### Etap 0. Izolacja i wartości bazowe

- [x] Utworzyć branch `codex/lazy-public-auth-drawers` z aktualnego `HEAD`.
- [ ] Przenieść zmiany do osobnego czystego worktree przed przygotowaniem
  artefaktu produkcyjnego; bieżący worktree zawiera niezależne zmiany
  użytkownika, które nie mogą wejść do tego wdrożenia.
- [x] Zmierzyć początkowy HTML strony pytania 6251.
- [x] Zmierzyć liczbę elementów DOM należących do drawerów.
- [x] Potwierdzić pobieranie ilustracji przed otwarciem drawera.
- [x] Potwierdzić inicjalizację Google Identity przed otwarciem drawera.
- [x] Potwierdzić obecne działanie przejścia login -> rejestracja.
- [x] Potwierdzić, że Service Worker nie cache'uje `/auth/`.

### Etap 1. Flaga bezpieczeństwa

- [x] Dodać flagę środowiskową `PUBLIC_AUTH_DRAWERS_LAZY`.
- [x] Dodać odczyt flagi w konfiguracji, bez bezpośredniego używania `env()` w
  widokach lub kontrolerach.
- [x] Dla `false` zachować obecne renderowanie obu drawerów.
- [x] Dla `true` używać punktu montowania i lazy loadingu.
- [x] Zapewnić obsługę obu wariantów przez ten sam nowy moduł JavaScript.

### Etap 2. Endpoint fragmentów

- [x] Dodać kontroler zwracający fragment logowania albo rejestracji.
- [x] Dodać `GET /auth/drawers/login`.
- [x] Dodać `GET /auth/drawers/register`.
- [x] Ograniczyć parametr do wartości `login` i `register`.
- [x] Dodać middleware `guest` i rozsądny limit żądań.
- [x] Ustawić `Cache-Control: private, no-store`.
- [x] Zwracać nagłówek `X-Prawkonaraz-Auth-Drawer: login|register`.
- [x] Nie zwracać pełnego layoutu ani zasobów strony.
- [x] Weryfikować po stronie klienta nagłówek i `Content-Type` przed wstawieniem
  HTML.
- [x] Potwierdzić, że rejestr kategorii jest pobierany tylko dla rejestracji.

### Etap 3. Layout i renderowanie błędów

- [x] Dodać mały komponent punktu montowania drawerów.
- [x] Usunąć bezwarunkowe renderowanie obu formularzy przy włączonej fladze.
- [x] Zachować obecne renderowanie przy wyłączonej fladze.
- [x] Ujednolicić wykrywanie aktywnego panelu na podstawie `_auth_panel`,
  `old()`, błędów i komunikatów sesji.
- [x] Po błędnym logowaniu wyrenderować serwerowo tylko drawer logowania.
- [x] Po błędnej rejestracji wyrenderować serwerowo tylko drawer rejestracji.
- [x] Zachować wszystkie komunikaty błędów oraz wartości dozwolone przez
  `old()`.
- [x] Nie renderować drawerów dla zalogowanego użytkownika.

### Etap 4. Moduł JavaScript

- [x] Pozostawić w `public-content.ts` lekki delegowany listener linków.
- [x] Dodać dynamiczny import modułu drawerów.
- [x] Dodać osobne, współdzielone promise dla ładowania loginu i rejestracji.
- [x] Zabezpieczyć szybkie przełączanie login <-> rejestracja przed wyścigiem
  odpowiedzi.
- [x] Nie wykonywać kolejnego requestu po wcześniejszym załadowaniu fragmentu.
- [x] Zachować zamykanie przyciskiem, kliknięciem tła i klawiszem `Escape`.
- [x] Zachować pokazywanie i ukrywanie hasła.
- [x] Zachować dropdown kategorii oraz wybór ścieżki nauki.
- [x] Zachować obsługę Google i pozostałych providerów.
- [x] Przy błędzie importu lub fetch wykonać przejście na pełny URL.
- [x] Przenieść import ilustracji z głównego entrypointu do lazy modułu.

### Etap 5. CSRF i Google Identity

- [x] Zmienić inicjalizację formularzy CSRF na zakresową i idempotentną.
- [x] Nadal inicjalizować formularze wylogowania obecne przy starcie strony.
- [x] Inicjalizować formularz drawerów dopiero po wstawieniu fragmentu.
- [x] Zachować odświeżenie tokenu przed POST.
- [x] Zachować komunikat i link awaryjny przy błędzie odświeżenia sesji.
- [x] Ładować Google Identity na stronach pytań dopiero po otwarciu loginu.
- [x] Zachować One Tap na stronie głównej dla powracających użytkowników bez
  renderowania formularza logowania.
- [x] Nie inicjalizować Google Identity wielokrotnie.

### Etap 6. Testy backendowe

- [x] Dodać `tests/Feature/PublicAuthDrawerTest.php`.
- [x] Potwierdzić brak formularzy w początkowym HTML przy włączonej fladze.
- [x] Potwierdzić obecność punktu montowania i zwykłych linków fallbackowych.
- [x] Potwierdzić kontrakt endpointu logowania.
- [x] Potwierdzić kontrakt endpointu rejestracji i listę kategorii.
- [x] Potwierdzić nagłówki `private, no-store`.
- [x] Potwierdzić zachowanie dla zalogowanego użytkownika.
- [x] Potwierdzić serwerowe otwarcie loginu po błędzie walidacji.
- [x] Potwierdzić serwerowe otwarcie rejestracji po błędzie walidacji.
- [x] Potwierdzić brak regresji pełnych stron `/login` i `/register`.
- [x] Uruchomić `AuthenticationTest`.
- [x] Uruchomić `RegistrationTest`.
- [x] Uruchomić `GoogleIdentityLoginTest`.
- [x] Uruchomić `CsrfSessionRecoveryTest`.
- [x] Uruchomić testy publicznych stron pytań.

### Etap 7. Testy JavaScript i przeglądarkowe

- [x] Dodać test idempotentnego przypinania obsługi CSRF.
- [x] Dodać test deduplikacji requestów fragmentów w scenariuszu Playwright.
- [x] Dodać test wyścigu podczas szybkiego przełączania paneli.
- [x] Dodać test fallbacku po błędzie sieci.
- [x] Potwierdzić w Playwright brak formularzy przed kliknięciem.
- [x] Potwierdzić brak requestu ilustracji przed kliknięciem.
- [x] Potwierdzić brak requestu Google GSI na stronie pytania przed kliknięciem.
- [x] Potwierdzić otwarcie loginu i widoczność jego pól.
- [x] Potwierdzić przełączenie login -> rejestracja -> login.
- [x] Potwierdzić zamykanie przez przycisk, tło i `Escape`.
- [ ] Potwierdzić błędy walidacji po redirect.
- [ ] Potwierdzić poprawne logowanie testowego użytkownika.
- [ ] Potwierdzić poprawną rejestrację testowego użytkownika.
- [ ] Potwierdzić działanie linków przy wyłączonym JavaScript.
- [x] Uruchomić `npm run test:unit` (`43` pliki, `236` testów).
- [x] Uruchomić `npm run build` (`968` modułów, build zakończony poprawnie).

### Etap 8. Kontrola wydajności

- [x] Ponownie zmierzyć HTML pytania 6251.
- [x] Potwierdzić usunięcie co najmniej `30 KB` z nieskompresowanego HTML
  (`35 316 B`).
- [x] Potwierdzić brak pól hasła i e-mail drawerów w początkowym DOM.
- [x] Potwierdzić redukcję początkowego DOM; lokalnie z `558` do `436`
  elementów (`122` mniej). Wartość produkcyjna może różnić się wraz z treścią
  pytania i aktywnymi komponentami strony.
- [x] Porównać rozmiar głównego chunku `public-content` przed i po zmianie.
- [x] Potwierdzić powstanie osobnego chunku drawerów.
- [x] Potwierdzić tylko jeden request fragmentu na typ podczas jednej wizyty.
- [x] Potwierdzić brak zapytania o kategorie bez otwierania rejestracji.

### Etap 9. Deploy kontrolowany flagą

- [x] Przed deployem ustawić produkcyjną flagę na `false`.
- [x] Zrobić backup zmienianych plików i `public/build`.
- [x] Wdrożyć backend, widoki, konfigurację i gotowy build Vite.
- [x] Wyczyścić i odbudować cache konfiguracji, tras i Blade.
- [x] Przy fladze `false` sprawdzić dotychczasowe działanie drawerów.
- [x] Osobno sprawdzić oba endpointy fragmentów.
- [x] Włączyć `PUBLIC_AUTH_DRAWERS_LAZY=true`.
- [x] Odbudować produkcyjny cache konfiguracji.
- [x] Sprawdzić pytanie 6251 bez formularzy w początkowym HTML.
- [x] Sprawdzić login, rejestrację, Google i błędy walidacji.
- [x] Uruchomić `ops:health-report` i `ops:smoke-test`.
- [x] Obserwować logi pod kątem odpowiedzi `419`, `422`, `429` i `500`.

## 6. Kontrakt endpointu fragmentów

Przykładowe żądanie:

```http
GET /auth/drawers/login HTTP/1.1
Accept: text/html
X-Requested-With: XMLHttpRequest
```

Minimalne wymagania odpowiedzi:

```http
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8
Cache-Control: private, no-store
X-Prawkonaraz-Auth-Drawer: login
```

Loader nie może wstawiać odpowiedzi, jeżeli:

- status nie jest `200`,
- odpowiedź została nieoczekiwanie przekierowana,
- `Content-Type` nie zawiera `text/html`,
- nagłówek drawera nie odpowiada żądanemu typowi,
- punkt montowania nie istnieje.

W takiej sytuacji należy przejść na pełne `/login` albo `/register`.

## 7. Zmienione pliki

- `config/performance.php`,
- `.env.example`,
- `routes/web.php`,
- `app/Http/Controllers/Auth/PublicAuthDrawerController.php`,
- `app/Http/Middleware/HandleInertiaRequests.php`,
- `resources/views/layouts/public-content.blade.php`,
- `resources/views/auth/drawers/show.blade.php`,
- `resources/views/components/site/auth-drawer-root.blade.php`,
- `resources/views/components/site/login-drawer.blade.php`,
- `resources/views/components/site/register-drawer.blade.php`,
- `resources/js/public-content.ts`,
- `resources/js/public/authDrawerLoader.ts`,
- `resources/js/public/authDrawers.ts`,
- `resources/js/lib/csrfForms.ts`,
- `tests/Feature/PublicAuthDrawerTest.php`,
- `scripts/e2e-public-auth-drawers.mjs`,
- `package.json`,
- ten dokument.

`public/build` został lokalnie wygenerowany do weryfikacji, ale nie jest
częścią bieżącego diffu. Artefakt produkcyjny trzeba przygotować z czystego
worktree, bez niezależnych zmian użytkownika.

Nie przewidujemy migracji bazy danych ani zmian kontraktów POST logowania i
rejestracji.

## 8. Rollback

### 8.1 Szybki rollback bez kodu

1. Ustawić `PUBLIC_AUTH_DRAWERS_LAZY=false`.
2. Uruchomić `php artisan config:cache`.
3. Sprawdzić, że layout ponownie renderuje oba istniejące drawery.
4. Sprawdzić logowanie i rejestrację.

### 8.2 Pełny rollback

Jeżeli problem występuje również przy wyłączonej fladze:

1. Włączyć maintenance mode.
2. Przywrócić backup backendu, widoków i `public/build`.
3. Wyczyścić i odbudować cache Laravel.
4. Przeładować PHP-FPM.
5. Wyłączyć maintenance mode.
6. Uruchomić health report, smoke test i testy logowania.

## 9. Definition of Done

Zmiana jest zakończona dopiero wtedy, gdy wszystkie poniższe warunki są
spełnione:

- [x] początkowy HTML publicznej strony nie zawiera zamkniętych formularzy,
- [x] login otwiera się po pierwszym kliknięciu,
- [x] rejestracja otwiera się po pierwszym kliknięciu,
- [x] oba panele przełączają się bez przeładowania strony,
- [ ] pełne `/login` i `/register` działają bez JavaScriptu,
- [x] walidacja i błędy otwierają właściwy panel po redirect,
- [x] CSRF działa po świeżej i długo otwartej sesji,
- [ ] Google Identity i One Tap działają zgodnie z obecnym kontraktem,
- [x] poprawne logowanie i rejestracja kończą się tymi samymi redirectami,
- [x] początkowo nie jest pobierana ilustracja drawera,
- [x] początkowo nie jest ładowane Google GSI na stronie pytania,
- [x] testy PHP, JavaScript, build i Playwright są zielone,
- [x] produkcyjne health report i smoke test są zielone,
- [x] flaga rollbacku została sprawdzona na produkcji,
- [x] dokument zawiera końcowe pomiary i wpis w rejestrze postępu.

## 10. Rejestr postępu

| Data | Etap | Status | Uwagi |
|---|---|---|---|
| 2026-07-17 | Audyt produkcyjnego HTML i DOM | zakończony | Potwierdzono SSR obu ukrytych formularzy. |
| 2026-07-17 | Audyt zasobów | zakończony | Potwierdzono wczesne pobieranie ilustracji i Google Identity. |
| 2026-07-17 | Audyt CSRF, Google, tras i Service Workera | zakończony | Ustalono wymagania bezpiecznego lazy loadingu. |
| 2026-07-17 | Plan implementacji | zakończony | Wybrano lazy Blade fragment + dynamiczny moduł TypeScript. |
| 2026-07-17 | Implementacja backendu i widoków | zakończony lokalnie | Dodano flagę, punkt montowania, endpointy i renderowanie błędów. |
| 2026-07-17 | Implementacja klienta | zakończony lokalnie | Dodano dynamiczny moduł, zakresowy CSRF i odroczone Google Identity. |
| 2026-07-17 | Testy PHP | zakończony | `36` testów auth (`256` asercji) oraz `30` testów pytań (`641` asercji). |
| 2026-07-17 | Testy JS i build | zakończony | `236` testów Vitest; build Vite poprawny. |
| 2026-07-17 | Playwright i pomiary | zakończony lokalnie | HTML mniejszy o `35 316 B`; fragmenty pobierane po jednym razie. |
| 2026-07-18 | Deploy produkcyjny | zakończony | Wdrożono czysty artefakt `a9296923`, rollout `false -> true`, health, smoke i Playwright zielone. |

Po zakończeniu każdego etapu należy:

1. zaznaczyć wykonane pozycje,
2. dopisać wynik testów lub pomiarów,
3. zaktualizować status dokumentu,
4. dodać wpis do rejestru postępu,
5. odnotować każdą zmianę względem decyzji z sekcji 3.
