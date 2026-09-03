# Google Identity Login Plan

Stan na: 2026-07-03
Zakres: logowanie przez Google dla istniejacych kont, bez automatycznej rejestracji.

## Cel

Chcemy dodac logowanie podobne do widoku ze zrzutu ekranu, czyli oficjalny Google Identity Services flow: spersonalizowany przycisk albo prompt "Kontynuuj jako ...". Na tym etapie mechanizm ma sluzyc tylko do logowania. Rejestracja zostaje poza zakresem, bo aplikacja wymaga wyboru kategorii prawa jazdy przed zalozeniem konta.

## Status Implementacji

Stan po rozpoczeciu developmentu: 2026-07-03.

Zrobione:

- Dodano zaleznosc `google/apiclient`.
- Dodano konfiguracje:
  - `GOOGLE_IDENTITY_ENABLED`
  - `GOOGLE_ONE_TAP_ENABLED`
  - `services.google.identity_enabled`
  - `services.google.one_tap_enabled`
- Dodano endpoint:
  - `POST /auth/google/identity`
  - nazwa: `google.identity.login`
- Dodano backend login-only:
  - `App\Http\Controllers\Auth\GoogleIdentityLoginController`
  - `App\Support\GoogleIdentityTokenVerifier`
  - `SocialAccountService::resolveExistingForLogin`
- Dodano testy:
  - `tests/Feature/Auth/GoogleIdentityLoginTest.php`
- Dodano frontend Vue/Inertia:
  - `resources/js/lib/googleIdentity.ts`
  - `resources/js/Components/Auth/GoogleIdentityLoginButton.vue`
  - integracja w `resources/js/Components/Auth/LoginDrawer.vue`
- Dodano frontend Blade/public-content:
  - kontener GIS w `resources/views/components/site/login-drawer.blade.php`
  - inicjalizacja w `resources/js/public-content.ts`
- Zachowano klasyczny OAuth redirect jako fallback.
- One Tap pozostaje wylaczony domyslnie i kontrolowany flaga.

Weryfikacja wykonana:

- `docker compose exec -T app composer install --no-interaction --no-scripts`
- `docker compose exec -T app composer validate --no-check-publish --strict`
- `docker compose exec -T app php artisan route:list --name=google.identity`
- `docker compose exec -T app php artisan test tests/Feature/Auth/GoogleIdentityLoginTest.php tests/Feature/Auth/SocialLoginTest.php`
  - wynik: 24 testy, 134 asercje, wszystkie przeszly
- `npm run build`
- `docker compose exec -T app ./vendor/bin/pint ...`

## Wdrozenie Produkcyjne 2026-07-03

Status: wdrozone na `https://prawkonaraz.pl`.

Wdrozone:

- kod Google Identity login-only z commita `984603c5`
- endpoint `POST /auth/google/identity`
- gotowy frontend build z assetem `googleIdentity`
- flagi produkcyjne:
  - `GOOGLE_IDENTITY_ENABLED=true`
  - `GOOGLE_ONE_TAP_ENABLED=false`
- hotfix kompatybilnosci starszego publicznego headera:
  - trasa `public.questions.search`
  - lokalny commit dokumentujacy/poprawiajacy kompatybilnosc: `4e807a1c`

Backupi na VPS:

- glowny deploy: `/tmp/prawkonaraz-google-identity-backup-20260703171102`
- finalny hotfix route: `/tmp/prawkonaraz-route-alias-hotfix-v3-backup-20260703172121`

Weryfikacja produkcyjna:

- `php artisan route:list --name=google.identity` pokazuje endpoint
- Laravel widzi:
  - `GOOGLE_CLIENT_ID_SET`
  - `GOOGLE_CLIENT_SECRET_SET`
  - `GOOGLE_IDENTITY_ENABLED_TRUE`
  - `GOOGLE_ONE_TAP_ENABLED_FALSE`
- `php artisan ops:health-report`: OK
- `php artisan ops:smoke-test`: OK
- `https://prawkonaraz.pl/api/v1/health`: 200
- `https://prawkonaraz.pl/`: 200
- publiczny HTML zawiera `data-google-identity-login` i `/auth/google/identity`
- legacy URL `/oficjalna-baza-pytan-na-prawo-jazdy/szukaj?q=test`: 200, bez petli redirectow

Nadal do testu manualnego:

- klikniecie oficjalnego przycisku Google w przegladarce na produkcji
- logowanie istniejacym kontem z podpietym Google
- logowanie istniejacym kontem lokalnym z tym samym zweryfikowanym adresem Google, jesli utrzymujemy auto-link
- proba kontem Google bez konta w aplikacji: oczekiwany brak rejestracji i komunikat o wyborze kategorii

Pozostaje po wdrozeniu:

- Potwierdzic w realnej przegladarce, ze Google Cloud Console dopuszcza produkcyjny JavaScript origin `https://prawkonaraz.pl`.
- Przetestowac faktyczne klikniecie przycisku Google na koncie testowym.
- Zostawic `GOOGLE_ONE_TAP_ENABLED=false` do czasu osobnej decyzji UX.
- Monitorowac logi `419`, `422`, `403` i bledy weryfikacji tokena po pierwszych probach logowania.

## Decyzje Produktowe

- One Tap / "Kontynuuj jako..." nie tworzy nowych kont.
- Jesli konto Google jest juz podpiete do uzytkownika, logujemy uzytkownika.
- Jesli istnieje konto lokalne z tym samym zweryfikowanym adresem e-mail Google, mozemy je automatycznie podpiac i zalogowac, zgodnie z obecna logika.
- Jesli konto nie istnieje, pokazujemy komunikat kierujacy do rejestracji z wyborem kategorii.
- Obecny klasyczny redirect OAuth zostaje jako fallback.
- Facebook pozostaje bez zmian.

## Co Juz Mamy

Backend:

- Trasy OAuth:
  - `GET /auth/{provider}/redirect`
  - `GET /auth/{provider}/callback`
  - plik: `routes/web.php`
- Kontroler OAuth:
  - `App\Http\Controllers\Auth\SocialAuthController`
  - przechowuje `state`, intencje logowania/linkowania, `target_category_id` i `preferred_learning_track`
- Klient providera:
  - `App\Support\SocialAuthProviderClient`
  - obsluguje Google OAuth code flow
  - wymienia `code` na token
  - pobiera profil z `https://openidconnect.googleapis.com/v1/userinfo`
- Logika kont spolecznosciowych:
  - `App\Support\SocialAccountService`
  - umie:
    - zalogowac konto juz podpiete przez `provider_user_id`
    - podpiac Google do istniejacego konta z tym samym zweryfikowanym e-mailem
    - utworzyc konto tylko wtedy, gdy jest `target_category_id`
    - zablokowac odpietcie ostatniej metody logowania
- Model i tabela:
  - `App\Models\UserSocialAccount`
  - migracja `2026_05_01_092000_add_social_login_foundation.php`
  - tabela `user_social_accounts`
  - kolumna `users.password_login_enabled`
- Testy:
  - `tests/Feature/Auth/SocialLoginTest.php`
  - ostatnio sprawdzone: 17 testow, 99 asercji, wszystkie przeszly przez `docker compose exec -T app php artisan test tests/Feature/Auth/SocialLoginTest.php`

Frontend:

- Inertia/Vue login drawer:
  - `resources/js/Components/Auth/LoginDrawer.vue`
  - ma przycisk `Zaloguj sie z Google`, gdy provider jest wlaczony
- Inertia/Vue register drawer:
  - `resources/js/Components/Auth/RegisterDrawer.vue`
  - ma rejestracje spolecznosciowa po wyborze kategorii
- Starsze widoki Blade tez maja przyciski social:
  - `resources/views/components/site/login-drawer.blade.php`
  - `resources/views/components/site/register-drawer.blade.php`
- Shared props:
  - `App\Http\Middleware\HandleInertiaRequests::enabledSocialProviders()`
  - provider pokazuje sie tylko, gdy `client_id` i `client_secret` sa ustawione

Konfiguracja:

- `config/services.php` ma:
  - `services.google.client_id`
  - `services.google.client_secret`
  - `services.google.redirect`
- `.env.example` i `.env.mikrus.example` maja placeholdery Google.

## Co Nie Dziala Jeszcze W Srodowisku

- Lokalny `.env` nie ma obecnie:
  - `GOOGLE_CLIENT_ID`
  - `GOOGLE_CLIENT_SECRET`
  - `GOOGLE_REDIRECT_URI`
- W aktualnym kontenerze Laravel config zwraca puste wartosci Google, wiec przyciski social sa ukryte.
- `docker-compose.yml` nie przekazuje obecnie zmiennych Google do serwisu `app`, wiec samo dopisanie ich do hostowego `.env` moze nie wystarczyc dla kontenera.
- Nie ma jeszcze endpointu dla Google Identity Services ID token / JWT.
- Nie ma jeszcze frontowego loadera `https://accounts.google.com/gsi/client`.
- Nie ma jeszcze logiki "login-only", ktora zabrania One Tap tworzenia kont bez kategorii.

## Implementation Readiness Pass

Ten przebieg ma byc krotki i celowany, nie jest ponownym audytem calego systemu. Jego celem jest przygotowanie pierwszych patchy tak, zeby implementacja nie rozjechala sie miedzy Vue/Inertia, Blade i konfiguracja kontenerow.

### Ustalenia Z Przebiegu Technicznego

- Glowne publiczne strony marketingowe i SEO korzystaja z Blade:
  - `resources/views/layouts/public-content.blade.php`
  - `resources/views/components/site/login-drawer.blade.php`
  - `resources/js/public-content.ts`
  - przyklady: home, cennik, baza pytan, znaki drogowe, przepisy, autorzy, partnerzy
- Czesc aplikacyjna i panele korzystaja z Vue/Inertia:
  - `resources/views/app.blade.php`
  - `resources/js/app.ts`
  - `resources/js/Components/Auth/LoginDrawer.vue`
  - `resources/js/Components/SiteHeader.vue`
- To oznacza, ze wdrozenie tylko w `LoginDrawer.vue` nie pokryje strony glownej ani wielu publicznych landingow.
- Nowy endpoint najlepiej dodac do `routes/web.php`, obok `/auth/csrf-token` i obecnych tras social, bo potrzebujemy sesji web i CSRF.
- Jesli frontend Vue ma uzywac `route('google.identity.login')`, trzeba dopisac trase do `config/ziggy.php`.
- Dla Blade mozna uzyc bezposredniego URL `/auth/google/identity` albo tez wygenerowac go w data-atrybucie z Blade.
- Endpoint powinien zwracac JSON i frontend musi wysylac `Accept: application/json`, bo Laravel renderuje JSON dla `expectsJson()`.
- Istniejacy `EnsureUserIsNotBanned` dobrze obsluguje JSON, ale endpoint i tak powinien sprawdzic bana po znalezieniu usera, tak jak obecny `SocialAuthController`.
- Testy najlepiej pisac przez fake/mocking nowego serwisu weryfikacji tokena, nie przez prawdziwy Google API Client.
- Obecny `docker-compose.yml` nie przekazuje zmiennych Google do kontenera `app`, wiec konfiguracja lokalna musi byc czescia Etapu 0.

### Dokladne Pliki Do Zmiany

Backend i konfiguracja:

- `composer.json`
  - dodac `google/apiclient`
- `composer.lock`
  - zaktualizuje sie po `composer require google/apiclient`
- `config/services.php`
  - dodac `google.identity_enabled`
  - opcjonalnie `google.one_tap_enabled`
- `.env.example`
  - dodac `GOOGLE_IDENTITY_ENABLED=false`
  - opcjonalnie `GOOGLE_ONE_TAP_ENABLED=false`
- `.env.mikrus.example`
  - dodac analogiczne flagi produkcyjne
- `docker-compose.yml`
  - przekazac Google envy do serwisu `app`
  - minimum: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `GOOGLE_IDENTITY_ENABLED`
- `routes/web.php`
  - dodac `POST /auth/google/identity`
  - middleware: `guest`, `throttle:20,1`
- `config/ziggy.php`
  - dodac `google.identity.login`, jesli Vue bedzie uzywac helpera `route()`
- `app/Http/Middleware/HandleInertiaRequests.php`
  - udostepnic `googleIdentity.enabled` i `googleIdentity.clientId`
- `app/Http/Controllers/Auth/GoogleIdentityLoginController.php`
  - nowy kontroler JSON
- `app/Support/GoogleIdentityTokenVerifier.php`
  - nowy serwis weryfikacji i mapowania ID tokena
- `app/Support/SocialAccountService.php`
  - dodac metode login-only, bez tworzenia konta
- `tests/Feature/Auth/GoogleIdentityLoginTest.php`
  - nowe testy endpointu GIS
- `tests/Feature/Auth/SocialLoginTest.php`
  - pozostawic jako regresja obecnego OAuth fallback

Frontend Vue/Inertia:

- `resources/js/types/index.d.ts`
  - dodac `googleIdentity` w `PageProps`
- `resources/js/types/global.d.ts`
  - dodac minimalne typy `window.google.accounts.id`
- `resources/js/lib/googleIdentity.ts`
  - nowy loader skryptu GIS
- `resources/js/Components/Auth/GoogleIdentityLoginButton.vue`
  - nowy komponent oficjalnego przycisku Google
- `resources/js/Components/Auth/LoginDrawer.vue`
  - wpiac komponent w sekcji `Szybki dostep`
  - zostawic obecny OAuth link jako fallback

Frontend Blade/public-content:

- `resources/views/layouts/public-content.blade.php`
  - upewnic sie, ze meta CSRF juz jest dostepne; obecnie jest
  - opcjonalnie dodac globalne data-atrybuty konfiguracji Google, jesli nie chcemy powielac logiki w drawerze
- `resources/views/components/site/login-drawer.blade.php`
  - dodac kontener dla oficjalnego przycisku GIS
  - zostawic obecny link OAuth fallback
- `resources/js/public-content.ts`
  - uzyc tego samego helpera `googleIdentity.ts` albo lekkiego adaptera
  - wysylac credential do `/auth/google/identity`

### Rekomendowana Kolejnosc Patchy

1. Konfiguracja i dependency:
   - `composer require google/apiclient`
   - `config/services.php`
   - `.env.example`
   - `.env.mikrus.example`
   - `docker-compose.yml`
2. Backend login-only:
   - `GoogleIdentityTokenVerifier`
   - `SocialAccountService::resolveExistingForLogin`
   - `GoogleIdentityLoginController`
   - `routes/web.php`
   - `config/ziggy.php`
   - testy backendowe
3. Inertia shared props i Vue:
   - `HandleInertiaRequests`
   - typy TypeScript
   - `googleIdentity.ts`
   - `GoogleIdentityLoginButton.vue`
   - `LoginDrawer.vue`
4. Blade/public-content:
   - `login-drawer.blade.php`
   - `public-content.ts`
5. One Tap:
   - dopiero po potwierdzeniu dzialania oficjalnego przycisku w obu torach

## Docelowa Architektura Dla Logowania

1. Uzytkownik otwiera login drawer.
2. Frontend laduje Google Identity Services client library:
   - `https://accounts.google.com/gsi/client?hl=pl`
3. Frontend inicjalizuje Google:
   - `client_id`: publiczny Google OAuth Web Client ID
   - `callback`: handler odbierajacy `credential`
   - `context`: `signin`
4. Google zwraca `credential`, czyli podpisany ID token JWT.
5. Frontend wysyla `credential` przez `fetch` do Laravel:
   - `POST /auth/google/identity`
   - `credentials: same-origin`
   - `X-CSRF-TOKEN` z meta tagu
   - `Accept: application/json`
6. Backend weryfikuje ID token.
7. Backend mapuje payload Google do `SocialProviderUser`.
8. Backend odpala logike login-only.
9. Po sukcesie backend loguje uzytkownika, regeneruje sesje i zwraca JSON:
   - `{ "redirect": "/dashboard" }`
10. Frontend przekierowuje na zwrocony adres.

## Backend Do Zrobienia

### 1. Konfiguracja Google Identity

Rozszerzyc `config/services.php`, np.:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
    'identity_enabled' => env('GOOGLE_IDENTITY_ENABLED', false),
],
```

Opcjonalnie zamiast nowej flagi mozna wlaczac GIS po samym `GOOGLE_CLIENT_ID`, ale flaga pozwala stopniowo wdrazac One Tap bez ryzyka.

### 2. Udostepnienie Client ID Do Frontendu

W `HandleInertiaRequests` dodac np.:

```php
'googleIdentity' => [
    'enabled' => (bool) config('services.google.identity_enabled')
        && filled(config('services.google.client_id')),
    'clientId' => config('services.google.client_id'),
],
```

Uwaga: `client_id` jest publiczny, ale `client_secret` nigdy nie moze trafiac do frontendu.

### 3. Nowa Trasa

Dodac trase:

```php
Route::post('/auth/google/identity', [GoogleIdentityLoginController::class, 'store'])
    ->middleware(['guest', 'throttle:20,1'])
    ->name('google.identity.login');
```

Nazwa kontrolera do decyzji:

- `GoogleIdentityLoginController`
- albo metoda w `SocialAuthController`

Preferowany osobny kontroler, bo przeplyw JWT jest inny niz OAuth redirect/callback.

Jesli Vue ma uzywac `route('google.identity.login')`, dopisac trase do grupy `app` w `config/ziggy.php`.

### 4. Weryfikacja ID Tokena

Opcja rekomendowana: dodac zaleznosc:

```bash
composer require google/apiclient
```

Google oficjalnie pokazuje dla PHP:

```php
$client = new Google_Client(['client_id' => $WEB_CLIENT_ID]);
$payload = $client->verifyIdToken($idToken);
```

Weryfikacja musi obejmowac:

- podpis tokena
- `aud` rowne naszemu `GOOGLE_CLIENT_ID`
- `iss` rowne `accounts.google.com` albo `https://accounts.google.com`
- `exp` nie moze byc przeszle
- stabilny identyfikator uzytkownika tylko z `sub`

### 5. Mapper Payloadu

Dla payloadu ID tokena tworzymy:

```php
new SocialProviderUser(
    id: (string) $payload['sub'],
    email: $payload['email'] ?? null,
    name: $payload['name'] ?? null,
    avatarUrl: $payload['picture'] ?? null,
    emailVerified: isset($payload['email_verified'])
        ? (bool) $payload['email_verified']
        : null,
);
```

Mozna to dodac do `SocialAuthProviderClient`, ale czytelniej bedzie stworzyc osobny serwis, np. `GoogleIdentityTokenVerifier`.

### 6. Login-Only Resolver

Nie uzywac bezposrednio obecnego `resolveForLogin()` dla GIS, bo ta metoda potrafi utworzyc konto, jesli dostanie `target_category_id`.

Dodac metode w `SocialAccountService`, np.:

```php
public function resolveExistingForLogin(
    string $provider,
    SocialProviderUser $providerUser,
): User
```

Reguly:

- sprawdz `ensureProviderUserIsUsable()`
- jesli istnieje `user_social_accounts.provider = google` i `provider_user_id = sub`, zaktualizuj avatar/dane i zwroc usera
- jesli istnieje `users.email = normalizedEmail()`:
  - wymagaj `emailVerified === true`
  - podepnij Google do usera
  - zwroc usera
- jesli nie ma uzytkownika:
  - rzuc `ValidationException` z komunikatem:
    - `Nie znalezlismy konta dla tego adresu Google. Zarejestruj sie i wybierz kategorie prawa jazdy.`

### 7. Odpowiedz JSON

Sukces:

```json
{
  "redirect": "/dashboard"
}
```

Bledy:

```json
{
  "message": "Nie znalezlismy konta dla tego adresu Google. Zarejestruj sie i wybierz kategorie prawa jazdy.",
  "registerUrl": "/register"
}
```

Statusy:

- `200` sukces
- `422` walidacja / konto nie istnieje / e-mail niezweryfikowany
- `403` konto zbanowane
- `503` Google Identity nie skonfigurowane

### 8. IP History I Sesja

Po sukcesie robimy tak jak w `SocialAuthController`:

- `Auth::login($user)`
- `$request->session()->regenerate()`
- `UserIpHistoryService->record($user, $request, 'google_identity_login', force: true)`

## Frontend Do Zrobienia

### 1. Typy

Rozszerzyc `resources/js/types/index.d.ts`:

```ts
googleIdentity: {
    enabled: boolean;
    clientId: string | null;
};
```

### 2. Definicje Typow Google

Rozszerzyc `resources/js/types/global.d.ts` o minimalne typy:

```ts
interface Window {
    google?: {
        accounts: {
            id: {
                initialize(options: unknown): void;
                renderButton(element: HTMLElement, options: unknown): void;
                prompt(callback?: (notification: unknown) => void): void;
                cancel(): void;
            };
        };
    };
}
```

Mozna potem doprecyzowac.

### 3. Loader Skryptu GIS

Stworzyc helper, np.:

- `resources/js/lib/googleIdentity.ts`

Odpowiedzialnosc:

- zaladowac skrypt tylko raz
- obsluzyc blad ladowania
- zwrocic promise, gdy `window.google.accounts.id` jest gotowe

Ten helper powinien byc mozliwy do uzycia zarowno z Vue, jak i z `resources/js/public-content.ts`, zeby nie utrzymywac dwoch osobnych loaderow GIS.

### 4. Komponent Przycisku

Stworzyc komponent:

- `resources/js/Components/Auth/GoogleIdentityLoginButton.vue`

Zadania:

- renderowac kontener na oficjalny przycisk Google
- `onMounted`:
  - `loadGoogleIdentity()`
  - `google.accounts.id.initialize(...)`
  - `google.accounts.id.renderButton(...)`
- callback wysyla `credential` do `/auth/google/identity`
- pokazuje stan:
  - ladowanie
  - blad
  - sukces / przekierowanie

Wariant startowy:

- tylko oficjalny przycisk w login drawerze
- bez automatycznego `prompt()`

Wariant drugi:

- wlaczyc `google.accounts.id.prompt()` po otwarciu login drawera
- tylko gdy:
  - user nie jest zalogowany
  - login drawer jest otwarty
  - `googleIdentity.enabled === true`
  - nie ma lokalnego komunikatu bledu

### 5. Integracja W LoginDrawer

W `LoginDrawer.vue`:

- jesli `page.props.googleIdentity.enabled`:
  - pokazac oficjalny przycisk GIS jako pierwszy wariant Google
  - zostawic klasyczny link OAuth jako fallback, np. `Inny sposob logowania Google`
- jesli GIS nie jest wlaczone:
  - zostaje obecny przycisk `social.redirect`

Nie integrowac GIS w `RegisterDrawer.vue` w tym etapie.

### 6. Blade Public Drawer

Po dodatkowym przebiegu kodu: Blade nie jest dodatkiem kosmetycznym, bo publiczne strony marketingowe i SEO korzystaja z `layouts.public-content`. Strona glowna, cennik, baza pytan i znaki drogowe korzystaja z Blade drawerow. Dlatego mamy dwie rozsadne opcje:

- Wariant A: w pierwszym wdrozeniu pokryc i Vue, i Blade, ale bez One Tap.
- Wariant B: najpierw Vue jako proof of concept, a zaraz potem drugi patch Blade przed wlaczeniem produkcyjnym.

Rekomendacja: Wariant A, bo logowanie z publicznych landingow jest najwazniejsze biznesowo.

## Konfiguracja Google Cloud

W Google Cloud Console:

- typ klienta: Web application
- Authorized JavaScript origins:
  - produkcja: `https://prawkonaraz.pl`
  - ewentualnie `https://www.prawkonaraz.pl`, jesli domena dziala z `www`
  - lokalnie: `http://localhost:8000`
- Authorized redirect URIs dla klasycznego fallback OAuth:
  - produkcja: `https://prawkonaraz.pl/auth/google/callback`
  - lokalnie: `http://localhost:8000/auth/google/callback`
- OAuth consent screen:
  - nazwa aplikacji zgodna z marka
  - domena aplikacji
  - link do polityki prywatnosci
  - link do regulaminu

W `.env`:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
GOOGLE_IDENTITY_ENABLED=false
GOOGLE_ONE_TAP_ENABLED=false
```

Na produkcji `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` i `GOOGLE_REDIRECT_URI` sa juz wedlug informacji od wlasciciela ustawione. Nowy login Google Identity uzywa tego samego `GOOGLE_CLIENT_ID`; `GOOGLE_CLIENT_SECRET` jest potrzebny tylko dla klasycznego OAuth redirect fallback. Do wdrozenia nowego przycisku potrzebujemy wiec przede wszystkim dopisac/wlaczyc `GOOGLE_IDENTITY_ENABLED`, a `GOOGLE_ONE_TAP_ENABLED` zostawic domyslnie wylaczone na start.

W `docker-compose.yml` dla serwisu `app` dodac:

```yaml
GOOGLE_CLIENT_ID: ${GOOGLE_CLIENT_ID:-}
GOOGLE_CLIENT_SECRET: ${GOOGLE_CLIENT_SECRET:-}
GOOGLE_REDIRECT_URI: ${GOOGLE_REDIRECT_URI:-}
GOOGLE_IDENTITY_ENABLED: ${GOOGLE_IDENTITY_ENABLED:-false}
GOOGLE_ONE_TAP_ENABLED: ${GOOGLE_ONE_TAP_ENABLED:-false}
```

Prawdopodobnie warto dodac te same zmienne do `scheduler` tylko wtedy, gdy jakies komendy schedulera potrzebuja pelnego configu services. Dla samego logowania wystarczy `app`.

## Server Sanity Check Przed Produkcja

Ten check nie jest potrzebny do rozpoczecia implementacji lokalnej, ale powinien byc wykonany przed wlaczeniem funkcji na produkcji. Nie wypisujemy sekretow, sprawdzamy tylko czy sa ustawione.

### Kod I Wersja Na Serwerze

- [ ] Sprawdzic aktualny commit/wydanie na serwerze:
  - `git rev-parse --short HEAD`
  - `git status --short`
- [ ] Upewnic sie, ze na serwerze nie ma recznych zmian w plikach aplikacji.
- [ ] Potwierdzic, ze deploy zawiera nowy endpoint, config i frontend build.
- [ ] Potwierdzic, ze `public/build/manifest.json` istnieje po deployu.

### Konfiguracja Laravel

- [ ] Sprawdzic `APP_URL`, bez ujawniania sekretow:
  - powinno wskazywac produkcyjna domene HTTPS, np. `https://prawkonaraz.pl`
- [ ] Sprawdzic status zmiennych Google jako `set/empty`, bez pokazywania wartosci:
  - `GOOGLE_CLIENT_ID`
  - `GOOGLE_CLIENT_SECRET`
  - `GOOGLE_REDIRECT_URI`
  - `GOOGLE_IDENTITY_ENABLED`
  - opcjonalnie `GOOGLE_ONE_TAP_ENABLED`
- [ ] Jesli stare zmienne Google sa juz ustawione, nie zmieniac klienta ani sekretu bez potrzeby; dopisac tylko brakujace flagi GIS.
- [ ] Po zmianie env wykonac:
  - `php artisan optimize:clear`
  - `php artisan config:cache`
- [ ] Potwierdzic, ze Laravel widzi wartosci po cache:
  - `config('services.google.client_id')` jest ustawione
  - `config('services.google.client_secret')` jest ustawione, jesli OAuth fallback ma dzialac
  - `config('services.google.redirect')` wskazuje produkcyjny callback
  - `config('services.google.identity_enabled')` ma oczekiwana wartosc

### HTTPS I Domeny

- [ ] Potwierdzic, ze produkcja dziala po HTTPS.
- [ ] Potwierdzic, ze canonical host jest zgodny z Google Cloud originem:
  - `https://prawkonaraz.pl`
  - oraz `https://www.prawkonaraz.pl`, jesli `www` jest realnie uzywane
- [ ] Sprawdzic, czy nie ma mieszania `http` i `https` w `APP_URL`, callbackach i linkach generowanych przez aplikacje.

### Google Cloud Console

- [ ] Authorized JavaScript origins zawieraja produkcyjna domene:
  - `https://prawkonaraz.pl`
  - opcjonalnie `https://www.prawkonaraz.pl`
- [ ] Authorized redirect URIs zawieraja OAuth fallback:
  - `https://prawkonaraz.pl/auth/google/callback`
  - opcjonalnie wariant z `www`, jesli potrzebny
- [ ] OAuth consent screen ma aktualne:
  - nazwe aplikacji
  - domene
  - link do polityki prywatnosci
  - link do regulaminu
- [ ] Client ID uzywany na produkcji jest tym samym Web Client ID, ktory ma ustawione powyzsze originy.

### Smoke Test Po Wdrozeniu

- [ ] Otworzyc publiczna strone, np. `/`, i login drawer.
- [ ] Sprawdzic, czy oficjalny przycisk Google renderuje sie bez bledow w konsoli.
- [ ] Zalogowac konto z juz podpietym Google.
- [ ] Zalogowac konto lokalne z tym samym zweryfikowanym e-mailem Google i potwierdzic auto-link, jesli ta decyzja produktowa zostanie utrzymana.
- [ ] Sprobowac kontem Google bez konta w aplikacji:
  - oczekiwany wynik: brak nowego uzytkownika, komunikat z przejsciem do rejestracji.
- [ ] Sprawdzic, ze obecny OAuth redirect fallback nadal dziala.
- [ ] Sprawdzic logi aplikacji pod katem:
  - bledow weryfikacji tokena
  - 419 CSRF
  - 422 walidacji
  - 403 dla kont zbanowanych
- [ ] Sprawdzic, ze nie sa logowane pelne tokeny JWT ani sekrety.

## Bezpieczenstwo

- Nie tworzyc kont przez One Tap bez kategorii.
- Nie uzywac e-maila jako identyfikatora Google. Do powiazania konta Google uzywac `sub`.
- Wymagac `email_verified === true` przy auto-linkowaniu do istniejacego konta po e-mailu.
- Nie wysylac `GOOGLE_CLIENT_SECRET` do frontendu.
- Endpoint GIS zabezpieczyc:
  - Laravel CSRF token
  - `same-origin` fetch
  - throttling
  - walidacja rozmiaru `credential`
  - weryfikacja ID tokena na backendzie
- Po zalogowaniu regenerowac sesje.
- Rejestrowac zdarzenie w historii IP jako osobny typ, np. `google_identity_login`.
- Nie logowac pelnych tokenow JWT.

## Testy Do Dodania

Backend feature tests:

- `google identity login logs in linked social account`
- `google identity login auto links existing verified email account`
- `google identity login rejects unknown account without creating user`
- `google identity login rejects unverified email`
- `google identity login rejects invalid token`
- `google identity login rejects banned user`
- `google identity login records ip history`
- `google identity login is throttled`

Unit tests:

- `GoogleIdentityTokenVerifier` mapuje payload na `SocialProviderUser`
- `SocialAccountService::resolveExistingForLogin` nie tworzy konta bez kategorii

Frontend tests, jesli bedzie praktyczne:

- helper laduje GIS tylko raz
- komponent pokazuje fallback, gdy GIS nie zaladuje sie
- callback wysyla credential i przekierowuje po sukcesie
- blad 422 pokazuje komunikat i link do rejestracji

Manual QA:

- niezalogowany user widzi oficjalny przycisk Google w login drawerze
- user z podpiętym Google loguje sie bez hasla
- user z lokalnym kontem i zweryfikowanym Google e-mailem zostaje zalogowany i konto Google zostaje podpiete
- user bez konta dostaje komunikat o rejestracji z wyborem kategorii
- obecny redirect OAuth dalej dziala jako fallback
- login drawer nie otwiera rejestracji automatycznie przez One Tap
- logout i ponowny login dzialaja poprawnie

## Etapy Wdrozenia

### Etap 0: Przygotowanie Konfiguracji

- [ ] Utworzyc OAuth Web Client w Google Cloud.
- [ ] Uzupelnic lokalny `.env`.
- [ ] Dodac zmienne Google do `docker-compose.yml`.
- [ ] Potwierdzic, ze `config('services.google.client_id')` jest widoczny w kontenerze.
- [ ] Sprawdzic obecny klasyczny OAuth redirect na lokalnym srodowisku.

### Etap 1: Backend Login-Only

- [ ] Dodac `google/apiclient`.
- [ ] Dodac `GoogleIdentityTokenVerifier`.
- [ ] Dodac `SocialAccountService::resolveExistingForLogin`.
- [ ] Dodac `GoogleIdentityLoginController`.
- [ ] Dodac trase `POST /auth/google/identity`.
- [ ] Dodac testy backendowe.
- [ ] Uruchomic `php artisan test tests/Feature/Auth/SocialLoginTest.php` oraz nowe testy.

### Etap 2: Frontend Oficjalnego Przycisku

- [ ] Dodac shared prop `googleIdentity`.
- [ ] Rozszerzyc typy TypeScript.
- [ ] Dodac loader GIS.
- [ ] Dodac `GoogleIdentityLoginButton.vue`.
- [ ] Wpiac komponent do `LoginDrawer.vue`.
- [ ] Zostawic OAuth redirect jako fallback.
- [ ] Uruchomic `npm run build`.

### Etap 2B: Blade/Public Content

- [ ] Wpiac ten sam loader GIS do `resources/js/public-content.ts`.
- [ ] Dodac kontener oficjalnego przycisku w `resources/views/components/site/login-drawer.blade.php`.
- [ ] Przekazac `client_id`, endpoint i flage enabled przez Blade data-atrybuty.
- [ ] Zostawic obecny link OAuth jako fallback.
- [ ] Sprawdzic strony:
  - `/`
  - `/cennik`
  - `/oficjalna-baza-pytan-na-prawo-jazdy`
  - `/znaki-drogowe`

### Etap 3: One Tap Prompt

- [ ] Wlaczyc `google.accounts.id.prompt()` tylko na login drawerze.
- [ ] Dodac warunki anty-nadmiernego pokazywania promptu.
- [ ] Obsluzyc skipped/dismissed moments bez agresywnego ponawiania.
- [ ] Sprawdzic, czy prompt nie jest zaslaniany przez drawer/overlay.
- [ ] Przetestowac na desktopie i mobile.

### Etap 4: Blade Drawer, Jesli Nadal Potrzebny

- [ ] Sprawdzic, ktore publiczne strony korzystaja jeszcze z Blade drawerow.
- [ ] Dolozyc wariant GIS do `resources/views/components/site/login-drawer.blade.php`.
- [ ] Dolozyc JS w `resources/js/public-content.ts`.
- [ ] Zachowac fallback OAuth.

### Etap 5: Produkcja

- [ ] Potwierdzic istniejace zmienne produkcyjne Google jako `set/empty`, bez wypisywania wartosci.
- [ ] Dopisac brakujace flagi:
  - `GOOGLE_IDENTITY_ENABLED`
  - `GOOGLE_ONE_TAP_ENABLED`
- [ ] Ustawic originy i redirect URI w Google Cloud.
- [ ] Wlaczyc `GOOGLE_IDENTITY_ENABLED=true`.
- [ ] Sprawdzic logowanie testowym kontem.
- [ ] Sprawdzic konto bez rejestracji: powinien byc komunikat, nie nowe konto.
- [ ] Monitorowac logi walidacji tokenow i bledow logowania.

## Otwarte Pytania

- Czy auto-linkowanie po zweryfikowanym e-mailu ma byc dozwolone od razu, czy wymagamy najpierw klasycznego zalogowania e-mailem i podpiecia Google w profilu?
- Czy One Tap ma pokazywac sie automatycznie po kliknieciu "Zaloguj sie", czy dopiero jako oficjalny przycisk w drawerze?
- Czy wdrazamy rownoczesnie Blade drawer, czy czekamy az potwierdzimy, ze glowny ruch idzie przez Vue/Inertia?
- Czy chcemy osobny feature flag tylko na One Tap, np. `GOOGLE_ONE_TAP_ENABLED`, niezalezny od `GOOGLE_IDENTITY_ENABLED`?

## Rekomendowany Najblizszy Krok

Najpierw wykonac Etap 0 i Etap 1. Dopiero gdy backend login-only bedzie testowany, podlaczyc oficjalny przycisk Google w `LoginDrawer.vue`. One Tap prompt wlaczyc jako ostatni krok, bo jest najbardziej widoczny dla uzytkownika i najlatwiej nim przesadzic UX.

## Zrodla

- Google Identity Services setup: https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid
- Sign in with Google button: https://developers.google.com/identity/gsi/web/guides/display-button
- Google One Tap: https://developers.google.com/identity/gsi/web/guides/display-google-one-tap
- Verify Google ID token server-side: https://developers.google.com/identity/gsi/web/guides/verify-google-id-token
