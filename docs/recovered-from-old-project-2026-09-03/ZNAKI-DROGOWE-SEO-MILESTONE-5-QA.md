# Znaki Drogowe SEO: Milestone 5 QA

## 1. Cel dokumentu

Ten dokument opisuje powtarzalny QA pass dla pierwszego batcha publikacyjnego modulu `znaki drogowe`.

To nie jest juz pelny opis calego obecnego corpusu. Po dalszych rolloutach modul urósł znacznie bardziej, ale `Milestone 5` zostaje malym, stabilnym batch'em referencyjnym do regresji `Etapu 1`.

Ma sluzyc do:

- szybkiego przygotowania lokalnego srodowiska QA,
- sprawdzania, czy pierwszy batch nadal jest publikowalny po kolejnych zmianach,
- odrozniania rzeczy sprawdzanych lokalnie od rzeczy, ktore wymagaja stagingu lub publicznego URL-a,
- prowadzenia QA na realnym stacku projektu `Docker Compose + PostgreSQL`, a nie na pomocniczej sqlite.

## 2. Zakres batcha QA

Aktualny batch `Milestone 5` obejmuje:

- `1` autora: `katarzyna-wisniewska` (dawny `/autorzy/redakcja-brd` przekierowuje 301)
- `2` kategorie:
  - `znaki-ostrzegawcze`
  - `znaki-zakazu`
- `3` znaki:
  - `a-7-ustap-pierwszenstwa`
  - `b-20-stop`
  - `b-2-zakaz-wjazdu`

Ten batch pozostaje celowo maly. Nowsze rollouty kategorii `C`, `D` oraz dalszych grup seedowanych przez glowny `TrafficSignSeoSeeder` wymagaja osobnych clusterowych checkow i nie sa recznie odhaczane tym runbookiem.

Powiazane assety placeholderowe:

- `storage/app/public/traffic-signs/placeholders/a7.svg`
- `storage/app/public/traffic-signs/placeholders/b20.svg`
- `storage/app/public/traffic-signs/placeholders/b2.svg`
- `storage/app/public/traffic-signs/placeholders/og/a7.svg`
- `storage/app/public/traffic-signs/placeholders/og/b20.svg`
- `storage/app/public/traffic-signs/placeholders/og/b2.svg`

## 3. Preflight lokalny

Przed QA upewnij sie, ze:

- migracje sa aktualne,
- `public/storage` wskazuje na `storage/app/public`,
- seed pierwszego batcha zostal odpalony,
- `APP_URL` odpowiada faktycznemu lokalnemu adresowi serwera.

Rekomendowany setup dla tego repo:

```powershell
docker compose up -d
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan db:seed --class=TrafficSignSeoSeeder --force
docker compose exec -T app php artisan storage:link
docker compose exec -T app php artisan optimize:clear
```

Fallback tylko do pracy poza kontenerami:

```powershell
.tools\php83\php.exe artisan migrate --force
.tools\php83\php.exe artisan db:seed --class=TrafficSignSeoSeeder
.tools\php83\php.exe artisan storage:link
.tools\php83\php.exe artisan optimize:clear
```

## 4. URL-e do obchodu

Podstawowy obchod QA:

- `/znaki-drogowe`
- `/znaki-drogowe/kategorie/znaki-ostrzegawcze`
- `/znaki-drogowe/kategorie/znaki-zakazu`
- `/znaki-drogowe/a-7-ustap-pierwszenstwa`
- `/znaki-drogowe/b-20-stop`
- `/znaki-drogowe/b-2-zakaz-wjazdu`
- `/autorzy/katarzyna-wisniewska`
- `/o-serwisie`
- `/robots.txt`
- `/sitemap.xml`
- `/sitemaps/traffic-signs.xml`
- `/sitemaps/traffic-sign-categories.xml`
- `/sitemaps/authors.xml`

## 5. Co sprawdzamy lokalnie

### Widocznosc i statusy

- `200` dla hubu, kategorii, znakow, autora i strony organizacji
- `404` dla nieopublikowanych rekordow
- `410` dla slugow oznaczonych jako `gone` w `config/content.php`

### Head i metadata

- poprawny `title`
- poprawny `meta description`
- self-canonical
- `Open Graph`
- `Twitter Card`
- preload dla obrazu LCP

### Widok publiczny

- widoczny autor
- widoczne daty publikacji i aktualizacji
- render glownego obrazu znaku
- breadcrumb UX
- linkowanie wewnetrzne po zwyklych `<a href>`
- FAQ widoczne w HTML, nie tylko w `JSON-LD`

### Structured data

- `Article` na stronie znaku
- `BreadcrumbList`
- `FAQPage`
- `ProfilePage` autora
- `Organization` na stronie `/o-serwisie`

## 6. Co sprawdzamy testami automatycznymi

Aktualny minimalny zestaw:

- [tests/Feature/Public/TrafficSignPagesTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/Public/TrafficSignPagesTest.php)
- [tests/Feature/Public/TrafficSignSeoInfrastructureTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/Public/TrafficSignSeoInfrastructureTest.php)
- [tests/Feature/Public/TrafficSignSampleBatchTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/Public/TrafficSignSampleBatchTest.php)

Uruchomienie:

```powershell
.tools\php83\php.exe artisan test tests/Feature/Public/TrafficSignPagesTest.php tests/Feature/Public/TrafficSignSeoInfrastructureTest.php tests/Feature/Public/TrafficSignSampleBatchTest.php tests/Feature/DashboardTest.php
```

## 7. Walidatory zewnetrzne

Te kroki wymagaja URL-a dostepnego publicznie albo przynajmniej tunelu / stagingu:

- `Rich Results Test`
- `Schema Markup Validator`
- `Google Search Console URL Inspection`

Wazna zasada:

- nie oznaczamy tych punktow jako wykonane na `localhost`,
- lokalnie sprawdzamy tylko to, czy HTML i `JSON-LD` sa obecne i spojne,
- finalny pass Google robimy dopiero po wystawieniu batcha na staging lub domenie.

### Publiczny pass wykonany w developmentcie

Do walidacji zewnetrznej wykorzystano tymczasowy publiczny tunnel `*.loca.lt`, skierowany na lokalny serwer aplikacji dzialajacy na realnej bazie `PostgreSQL`.

Ten pass ujawnil istotny problem:

- canonicale, preloady i assety SEO potrafily wyplywac jako `http://localhost:8000/...` zamiast publicznego `https://...` przy ruchu za proxy.

Naprawa wdrozona w kodzie:

- `bootstrap/app.php` otrzymal `trustProxies(at: '*')`,
- dodano `app/Support/PublicUrlResolver.php`,
- znormalizowano publiczne URL-e w SEO, schema i media resolverach.

Po poprawce:

- publiczne canonicale, `og:image`, preload LCP, `Article`, `Organization` i `robots.txt` wskazuja poprawny host i schemat `https`.

### Oficjalny wynik `Schema Markup Validator`

Zweryfikowano publiczne URL-e pierwszego batcha referencyjnego:

- `/znaki-drogowe/a-7-ustap-pierwszenstwa`
- `/autorzy/katarzyna-wisniewska`
- `/o-serwisie`

Wynik:

- `0` errors
- `0` warnings

Poprawnie wykryte typy:

- strona znaku:
  - `BreadcrumbList`
  - `Article`
  - `FAQPage`
- strona autora:
  - `BreadcrumbList`
  - `ProfilePage`
- strona organizacji:
  - `BreadcrumbList`
  - `Organization`

Ten krok mozna uznac za wykonany dla pierwszego batcha referencyjnego.

### Status `Rich Results Test`

Automatyczny pass na publicznym URL-u dochodzi do narzedzia, ale po stronie Google pojawia sie komunikat:

- `Something went wrong`
- `Log in and try again`

W praktyce oznacza to, ze dalsza walidacja w tym narzedziu wymaga wejscia w interakcje z kontem Google albo recznego uruchomienia testu w normalnej sesji przegladarki. Tego nie oznaczamy jako wykonane bez jawnego dostepu do odpowiedniego konta.

## 8. Slug change i redirect QA

Polityka redirectow jest juz wdrozona w kodzie, ale reczny check wymaga realnej mapy zmienionego slugu w `config/content.php`.

Do czasu pierwszej prawdziwej zmiany slugu:

- zachowanie `301` i `410` utrzymujemy testami feature,
- reczny check przeprowadzamy przy pierwszym realnym przypadku migracji URL.

Punkty odniesienia:

- [app/Support/TrafficSignRedirectPolicy.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/TrafficSignRedirectPolicy.php)
- [config/content.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/config/content.php)

## 9. Aktualny status

Lokalnie domkniete:

- batch seedowy `1 author / 2 categories / 3 signs`
- placeholderowe assety obrazu i OG
- testy publicznych tras
- testy SEO infrastructure
- test batcha i idempotencji seedera
- browser pass dla `/znaki-drogowe`, strony znaku, autora i `/o-serwisie`
- manualny check `200 / 404`, `title`, `description`, `canonical`, `OG`, `JSON-LD`, breadcrumbs, autora i dat
- poprawka brakujacego entry `Vite` dla publicznego layoutu `Blade` przez `resources/js/public-content.ts`
- pass na realnym stacku `Docker Compose + PostgreSQL`
- poprawka proxy / public host dla canonicali, schema i assetow SEO
- oficjalny pass `Schema Markup Validator` bez bledow i warningow
- poza tym referencyjnym batch-em modul ma juz znacznie szerszy corpus lokalny; ten runbook nie zastepuje osobnych quality passow dla kolejnych rolloutow kategorii.

Do domkniecia przy publicznym URL:

- `Rich Results Test`
- `URL Inspection`
- pierwszy reczny redirect pass na realnie zmienionym slugu

## 10. Brak stagingu / serwera

Jesli projekt nie ma jeszcze stabilnego stagingu albo publicznej domeny, traktujemy to jako jawna blokade infrastrukturalna, a nie brak po stronie developmentu modulu.

W takiej sytuacji:

- `Milestone 5` uznajemy za lokalnie gotowy,
- finalne walidacje Google odkladamy do momentu pojawienia sie prawdziwego publicznego adresu,
- tymczasowy tunnel sluzy tylko do szybkiego passu developerskiego i nie zastępuje normalnego stagingu ani domeny docelowej.

Rekomendacja procesowa:

- nie blokowac dalszego developmentu modulu tylko dlatego, ze nie ma jeszcze serwera,
- przejsc do kolejnego etapu roadmapy,
- wrocic do `Rich Results Test`, `URL Inspection` i finalnego release QA przy pierwszym dostepnym stagingu.
