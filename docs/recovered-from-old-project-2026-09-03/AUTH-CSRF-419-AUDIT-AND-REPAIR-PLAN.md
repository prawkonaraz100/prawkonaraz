# Audyt i plan naprawy bledow CSRF 419

Status: audyt zakonczony, naprawa wdrozona produkcyjnie 2026-06-18
Data audytu: 2026-06-18
Zakres: top menu, drawery logowania i rejestracji, logout oraz operacje zmieniajace stan w module nauki

## 1. Cel

Ten dokument zapisuje potwierdzony stan problemu `419 Page Expired`, odrzucone
hipotezy oraz bezpieczny plan naprawy.

Najwazniejsze rozroznienie:

- publiczne strony Blade zawieraja token CSRF zapisany w HTML,
- token moze stac sie nieaktualny po wygasnieciu albo zmianie sesji,
- produkcyjny HTML nie jest obecnie wspoldzielonym full-page cache pomiedzy
  uzytkownikami.

Nie wolno naprawiac problemu przez wylaczenie CSRF.

## 2. Wynik audytu produkcji

### 2.1 Full-page cache nie jest przyczyna

W dniu 2026-06-18 sprawdzono dwa niezalezne pobrania publicznej strony pytania.

Kazde pobranie otrzymalo:

- inny cookie sesji,
- inny `XSRF-TOKEN`,
- inny token w HTML,
- `Cache-Control: no-cache, private`,
- `cf-cache-status: DYNAMIC`.

Nginx nie ma wlaczonego `proxy_cache` ani `fastcgi_cache` dla HTML. Cache
`7d immutable` dotyczy tylko assetow statycznych, takich jak CSS, JavaScript,
obrazy i fonty.

Wniosek: raport zakladajacy jeden token zapisany w cache i rozdawany wielu
uzytkownikom nie odpowiada obecnej konfiguracji produkcyjnej.

### 2.2 Swiezy token dziala

W kontrolowanym tescie:

1. pobrano publiczna strone do nowego cookie jar,
2. odczytano token z formularza,
3. wyslano `POST /login` z tym samym cookie sesji i tokenem.

Serwer odpowiedzial `302`, a nie `419`. Oznacza to, ze klasyczny formularz
dziala poprawnie, dopoki token i sesja sa nadal zgodne.

### 2.3 Bledy 419 sa realne i szersze niz drawery auth

Logi Nginx potwierdzaja produkcyjne odpowiedzi `419` m.in. dla:

- `POST /login`,
- `POST /logout`,
- `POST /study-sessions`,
- `POST /nauka/teraz/odpowiedzi`,
- operacji potwierdzenia usuniecia konta.

Problem nie jest wiec ograniczony do publicznych stron pytan ani jednego
komponentu menu.

## 3. Konfiguracja sesji

Produkcja w dniu audytu:

- driver sesji: `redis`,
- czas bezczynnosci: `120` minut,
- cookie: `prawkonarazpl-session`,
- domena: `.prawkonaraz.pl`,
- `secure=true`,
- `http_only=true`,
- `same_site=lax`,
- sesje korzystaja z Redis DB `0`,
- cache aplikacyjny korzysta z osobnej Redis DB `1`.

Rozdzielenie DB sesji i cache jest poprawne. Zwykle `cache:clear` nie powinno
kasowac sesji.

Redis byl restartowany 9 i 11 czerwca 2026. Restart sam w sobie nie musi
kasowac danych, jesli persistence dziala poprawnie, ale jest waznym tropem
operatorskim przy analizie naglych serii 419.

## 4. Najbardziej prawdopodobny mechanizm

Token CSRF jest zwiazany z aktualna sesja Laravel. Moze przestac pasowac, gdy:

1. karta pozostaje otwarta dluzej niz czas zycia bezczynnej sesji,
2. przegladarka przywraca stary DOM z back-forward cache,
3. sesja Redis wygasa, zostaje utracona albo odtworzona,
4. logowanie, rejestracja, logout albo inna operacja regeneruje sesje,
5. frontend nadal uzywa tokenu odczytanego przed regeneracja.

To tlumaczy przypadki 419 na publicznym logowaniu, logout oraz w dlugo otwartym
module nauki.

## 5. Mapa miejsc podatnych na stary token

### Blade / publiczne strony SEO

- `resources/views/layouts/public-content.blade.php`
  - meta `csrf-token` jest generowane przy renderowaniu dokumentu.
- `resources/views/components/site/login-drawer.blade.php`
  - formularz `POST /login` ma statyczne `@csrf`.
- `resources/views/components/site/register-drawer.blade.php`
  - formularz `POST /register` ma statyczne `@csrf`.
- `resources/views/components/site/public-header.blade.php`
  - desktopowy i mobilny logout maja statyczne `@csrf`.
- `resources/js/public-content.ts`
  - otwiera drawery, ale obecnie nie odswieza tokenu przed wyslaniem.

### Vue / Inertia

- `resources/js/Components/SiteHeader.vue`
  - token dla formularzy logout jest odczytywany z meta tylko raz podczas
    inicjalizacji komponentu.
- `resources/js/Components/Auth/LoginDrawer.vue`
  - logowanie korzysta z `useForm`.
- `resources/js/Components/Auth/RegisterDrawer.vue`
  - rejestracja korzysta z `useForm`.
- `resources/js/lib/apiClient.ts`
  - ma juz poprawny wzorzec dla requestow JSON: preferuje aktualny cookie
    `XSRF-TOKEN` zamiast potencjalnie starego tokenu z meta.

## 6. Decyzja architektoniczna

Rekomendowany jest lekki endpoint sesyjny:

`GET /auth/csrf-token`

Endpoint:

- dziala w standardowym middleware `web`,
- jest dostepny dla goscia i zalogowanego uzytkownika,
- zwraca aktualny `csrf_token()` oraz stan `authenticated`,
- odpowiada `Cache-Control: no-store, private`,
- nie moze byc objety Cloudflare Cache Rule ani cache aplikacyjnym,
- nie zmienia danych i nie wymaga wylaczania ochrony CSRF.

Przykladowy kontrakt:

```json
{
  "token": "aktualny-token-sesji",
  "authenticated": false
}
```

Istniejacy `/sanctum/csrf-cookie` moze odswiezyc cookie `XSRF-TOKEN`, ale nie
zwraca prostego tokenu potrzebnego klasycznemu polu formularza `_token`.
Dedykowany endpoint jest czytelniejszy dla obecnego polaczenia Blade i Vue.

## 7. Docelowe zachowanie frontendu

### 7.1 Login i rejestracja w publicznym drawerze

1. Otwarcie drawera moze wykonac wstepny prefetch tokenu.
2. Bezposrednio przed submit frontend ponownie pobiera aktualny token.
3. Aktualizuje ukryte pole `_token`.
4. Dopiero potem wysyla klasyczny formularz.
5. Przycisk jest chwilowo zablokowany i pokazuje stan przygotowania.
6. Jezeli odswiezenie tokenu sie nie uda, formularz nie jest wysylany ze starym
   tokenem; uzytkownik dostaje czytelny komunikat i link do pelnej strony
   `/login` albo `/register`.

Odswiezenie przy submit jest wazniejsze niz samo odswiezenie przy otwarciu
drawera, bo uzytkownik moze zostawic drawer otwarty na dlugo.

### 7.2 Logout

Przed `POST /logout` nalezy pobrac aktualny token.

Jesli endpoint zwroci `authenticated=false`, sesja juz wygasla. Wtedy nie ma
sensu wysylac chronionego POST. UI powinno przekierowac na strone glowna albo
logowanie z komunikatem:

`Twoja sesja juz wygasla. Nie jestes zalogowany.`

Jesli `authenticated=true`, frontend aktualizuje `_token` i wysyla
`POST /logout`.

### 7.3 Operacje w module nauki

Dla odpowiedzi i innych operacji zmieniajacych postep nie wolno wykonywac
automatycznego retry po 419. Ponowienie mogloby:

- zapisac odpowiedz drugi raz,
- przesunac sesje do kolejnego pytania,
- zduplikowac statystyki albo zdarzenia.

Po 419 frontend powinien:

1. zatrzymac interakcje,
2. pokazac komunikat o wygaslej sesji,
3. zachowac lokalnie nieszkodliwy kontekst, jesli to mozliwe,
4. zaproponowac odswiezenie strony albo ponowne logowanie,
5. nie powtarzac automatycznie mutacji.

## 8. Obsluga back-forward cache

Na zdarzeniu `pageshow` z `event.persisted === true` publiczny frontend powinien
odswiezyc stan CSRF albo oznaczyc token jako wymagajacy odswiezenia przed
nastepnym submit.

Nie trzeba przeladowywac calej strony przy kazdym powrocie z historii.

## 9. Testy wymagane przed wdrozeniem

### Backend

- endpoint zwraca token dla goscia,
- endpoint zwraca token dla zalogowanego,
- odpowiedz ma `no-store, private`,
- dwa rozne cookie jar otrzymuja rozne sesje/tokeny,
- endpoint nie jest cache'owany,
- standardowy `POST /login` z tokenem i zgodna sesja przechodzi przez CSRF.

### Frontend

- login drawer odswieza token przed submit,
- register drawer odswieza token przed submit,
- desktopowy logout odswieza token,
- mobilny logout odswieza token,
- podwojne klikniecie nie wysyla dwoch formularzy,
- awaria endpointu daje komunikat i bezpieczny fallback,
- powrot przez back-forward cache nie wysyla starego tokenu.

### Test dlugiej sesji

1. Otworzyc publiczna strone.
2. Zachowac dokument z poczatkowym tokenem.
3. Uniewaznic albo zastapic sesje testowa.
4. Otworzyc drawer i wyslac formularz.
5. Potwierdzic, ze frontend pobral nowy token i nie otrzymal 419.

### Regresja modulu nauki

- normalny zapis odpowiedzi nadal dziala,
- symulowany 419 nie wykonuje automatycznego retry,
- uzytkownik dostaje czytelna sciezke odzyskania sesji.

## 10. Monitoring

Po wdrozeniu nalezy monitorowac liczbe odpowiedzi 419 per endpoint:

- `/login`,
- `/register`,
- `/logout`,
- `/study-sessions`,
- `/nauka/teraz/odpowiedzi`,
- endpointy profilu.

Sam kod statusu nie wystarcza do diagnozy. Log powinien zawierac:

- request ID,
- route/path,
- czy request mial cookie sesji,
- czy request mial `_token`, `X-CSRF-TOKEN` albo `X-XSRF-TOKEN`,
- bez zapisywania wartosci tokenu.

## 11. Czego nie robic

- nie wylaczac middleware CSRF,
- nie dodawac tras auth do wyjatkow CSRF,
- nie rozwiazywac problemu samym wydluzeniem `SESSION_LIFETIME`,
- nie wylaczac Cloudflare,
- nie usuwac bezpiecznego `POST /logout`,
- nie retry'owac automatycznie operacji zapisujacych postep,
- nie logowac pelnych tokenow ani cookies.

## 12. Rollout

Rekomendowana kolejnosc:

1. endpoint i testy backendowe,
2. wspolny helper frontendowy do odswiezania tokenu,
3. Blade login/register,
4. Blade i Vue logout,
5. komunikat 419 w module nauki,
6. test produkcyjny na starej karcie,
7. monitoring logow przez minimum 7 dni.

Rollback:

- wycofac helper i endpoint,
- pozostawic dotychczasowe `@csrf`,
- nie zmieniac konfiguracji sesji ani zabezpieczen middleware.
