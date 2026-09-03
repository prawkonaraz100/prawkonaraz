# Znaki Drogowe SEO: Etap 1 Plan Wdrozenia

## 1. Cel dokumentu

Ten dokument przeklada strategie SEO dla `znakow drogowych` na konkretny plan wdrozenia pod obecne repo:

- `Laravel 12`
- `Inertia.js + Vue 3 + TypeScript` dla czesci aplikacyjnej
- `Filament` dla backoffice
- brak publicznego `SSR` w obecnym frontendzie

To jest dokument operacyjny. Ma sluzyc do:

- odhaczania postepu,
- pilnowania zakresu,
- zapisywania aktualnego statusu,
- rozdzielania `Etapu 1` od dalszych etapow `kodeks / mandaty / AI / multilang`.

## 2. Snapshot startowy

Data utworzenia planu: `2026-04-27`

Stan obecny:

- aplikacja ma domkniete techniczne MVP dla glownego produktu edukacyjnego,
- publiczna warstwa contentowa istnieje tylko szczatkowo i jest oparta glownie o placeholdery,
- nie ma jeszcze modeli, tabel i admina dla `znakow drogowych`,
- nie ma jeszcze osobnego publicznego shellu `Blade` dla content SEO,
- obecny publiczny frontend jest oparty o `Inertia` bez pelnego `SSR`,
- nie ma jeszcze sitemap dla nowego modulu contentowego ani routingu dla `znaki -> kategoria -> detail`.

Decyzja kanoniczna dla tego modulu:

- `Etap 1` robimy jako `Blade-first public content module`,
- nie przepinamy obecnej warstwy aplikacyjnej na `Inertia SSR`,
- `Vue / Inertia` zostaje tam, gdzie juz daje wartosc aplikacyjna,
- modul `znaki drogowe` budujemy jako nowy pion produktu obok glownej aplikacji.

Status startowy `Etapu 1`:

- `Not started`

## 3. Cel Etapu 1

Dowiezc pierwsza, publikowalna wersje modulu `znaki drogowe`, ktora:

- ma serwerowo renderowane strony publiczne,
- ma poprawna architekture URL,
- ma fundament technicznego SEO,
- ma prosty, realny backoffice w `Filamencie`,
- pozwala bezpiecznie rozwijac kolejne klastry contentowe.

Po `Etapie 1` uzytkownik i crawler maja dostac minimum:

- `/znaki-drogowe`
- `/znaki-drogowe/kategorie/{kategoria}`
- `/znaki-drogowe/{znak}`
- `/autorzy/{slug}`

## 4. Zakres Etapu 1

W scope `Etapu 1` wchodzi:

- hub `znaki drogowe`,
- strony kategorii znakow,
- strony pojedynczych znakow,
- podstawowy model autora contentu,
- widoczna warstwa byline i dat publikacji / aktualizacji,
- semantyczny i dostepny HTML dla glownego modulu contentowego,
- panel admina dla autorow, kategorii i znakow,
- meta tagi, canonicale, breadcrumbs, podstawowe schema,
- sitemap dla modulu,
- `robots.txt` z linkiem do sitemap,
- podstawowa polityka canonical / redirect / status codes,
- przygotowanie layoutu i komponentow pod przyszly rozwoj.

Poza scope `Etapu 1` zostaje:

- `/kodeks-drogowy`,
- `/mandaty`,
- wielojezycznosc i realne `hreflang`,
- quizy / testy osadzone na kartach znakow,
- rozbudowane linkowanie automatyczne po calym corpusie,
- pelny model `Legislation` i powiazania miedzy aktami prawnymi,
- pipeline masowego generowania tresci,
- rollout setek znakow naraz,
- migracja calej publicznej warstwy na jeden frontend stack.

## 5. Definition of done dla Etapu 1

`Etap 1` uznajemy za domkniety dopiero, gdy wszystkie ponizsze warunki sa prawdziwe:

- istnieje nowy model danych dla `autor -> kategoria -> znak`,
- admin moze dodac i opublikowac znak bez grzebania w kodzie,
- kazda strona znaku zwraca pelny HTML z serwera,
- kazda strona znaku ma `title`, `description`, `canonical`, `breadcrumbs` i podstawowe `JSON-LD`,
- kazda strona znaku ma widocznego autora i widoczne daty `publikacja / aktualizacja`,
- istnieje `sitemap index` obejmujacy modul,
- `robots.txt` wskazuje aktualny sitemap index,
- istnieje polityka `301/308` dla zmian URL i `404/410` dla usunietych tresci,
- istnieja przynajmniej `3` kompletne rekordy testowe gotowe do recznego QA,
- testy feature obejmuja najwazniejsze publiczne trasy i statusy HTTP,
- `npm run build` i `php artisan test` przechodza po wdrozeniu zmian,
- modul nie psuje istniejacego flow aplikacji edukacyjnej.

## 6. Architektura docelowa dla Etapu 1

### Warstwa renderowania

- publiczny modul SEO bedzie renderowany przez `Blade`,
- layout contentowy bedzie osobny od `resources/views/app.blade.php`,
- `Inertia` zostaje dla obecnych ekranow aplikacyjnych,
- `Blade` i `Inertia` wspoldziela tylko globalne style, assety i ewentualnie pojedyncze elementy UI tam, gdzie to ma sens.

### Model danych

Minimalny zestaw encji:

- `ContentAuthor`
- `TrafficSignCategory`
- `TrafficSign`

Zakladane relacje:

- jeden `ContentAuthor` ma wiele `TrafficSign`,
- jedna `TrafficSignCategory` ma wiele `TrafficSign`,
- jeden `TrafficSign` nalezy do jednego `ContentAuthor`,
- jeden `TrafficSign` nalezy do jednej `TrafficSignCategory`.

Minimalny zestaw pol dla `TrafficSign`:

- `code`
- `slug`
- `name`
- `intro_definition`
- `meaning`
- `placement`
- `driver_behavior`
- `legal_summary`
- `fine_summary`
- `common_mistakes`
- `faq_items` jako `json`
- `meta_title`
- `meta_description`
- `image_path`
- `og_image_path`
- `is_published`
- `published_at`

Minimalny zestaw pol dla `ContentAuthor`:

- `name`
- `slug`
- `job_title`
- `bio`
- `photo_path`
- `linkedin_url`
- `external_profile_url`
- `is_published`

Minimalny zestaw pol dla `TrafficSignCategory`:

- `name`
- `slug`
- `description`
- `intro_title`
- `intro_body`
- `is_published`

### Routing publiczny

Docelowe trasy `Etapu 1`:

- `GET /znaki-drogowe`
- `GET /znaki-drogowe/kategorie/{trafficSignCategory:slug}`
- `GET /znaki-drogowe/{trafficSign:slug}`
- `GET /autorzy/{contentAuthor:slug}`

Zasady:

- trasa kategorii ma osobny prefiks `kategorie`, bo w praktycznej implementacji jednopoziomowe URL-e `/{categorySlug}` i `/{signSlug}` koliduja ze soba,
- publiczne strony contentowe nie wymagaja logowania,
- opublikowane rekordy zwracaja `200`,
- nieopublikowane rekordy publicznie zwracaja `404`,
- usuniete lub wycofane rekordy bez zamiennika powinny miec jawna polityke `404` lub `410`,
- zmiana slugu dla strony, ktora juz byla publiczna, wymaga `301` lub `308`,
- przyszle rozszerzenia `/kodeks-drogowy` i `/mandaty` nie zmieniaja tego kontraktu.

### Backoffice

Nowe zasoby `Filament`:

- `TrafficSigns`
- `TrafficSignCategories`
- `ContentAuthors`

Backoffice ma obslugiwac:

- CRUD,
- status publikacji,
- walidacje slugow,
- upload / wybor obrazow,
- edycje blokow FAQ,
- podglad podstawowych pol SEO.

### SEO foundation

Minimalny zakres SEO w `Etapie 1`:

- `title`
- `meta description`
- self-referencing `canonical` na wszystkich stronach indeksowalnych
- `Open Graph`
- `Twitter Card`
- `BreadcrumbList`
- `Article`
- `FAQPage` jako dodatek pomocniczy, nie glowny lewar CTR
- `ProfilePage` dla stron autorow
- `Organization` na stronie glownej lub dedykowanej stronie organizacji
- sitemap index
- image sitemap
- `robots.txt`
- widoczne byline i daty publikacji / aktualizacji
- crawlable internal links oparte o prawdziwe `<a href>`
- podstawowy accessibility baseline:
  - poprawna hierarchia naglowkow
  - sensowne `alt`
  - semantyczne tabele
  - brak ukrywania istotnych danych tylko w `JSON-LD`

### Granica warstwy prawnej w Etapie 1

W `Etapie 1` na stronach znakow robimy tylko lekka, kontrolowana warstwe prawna:

- widoczna sekcja `podstawa prawna`,
- ostrozny opis znaczenia przepisu dla danego znaku,
- link do zewnetrznego lub przyszlego wewnetrznego zrodla prawnego,
- pola i tresc gotowe do pozniejszego powiazania z klastrem `kodeks`.

W `Etapie 1` nie robimy jeszcze:

- osobnych stron przepisow w ramach `kodeksu`,
- rozbudowanego modelu nowelizacji i historii zmian aktow,
- pelnego `Legislation schema`,
- grafu zaleznosci `przepis -> zmiana -> interpretacja -> znak`.

To jest swiadomie odlozone do `Etapu 3`.

## 7. Mapa plikow planowanego wdrozenia

Ta mapa ma charakter wykonawczy. Nie kazdy plik musi powstac pod dokladnie ta nazwa, ale odchylenia powinny byc swiadome i male.

### Baza i modele

- `database/migrations/*_create_content_authors_table.php`
- `database/migrations/*_create_traffic_sign_categories_table.php`
- `database/migrations/*_create_traffic_signs_table.php`
- `database/seeders/TrafficSignSeoSeeder.php`
- `app/Models/ContentAuthor.php`
- `app/Models/TrafficSignCategory.php`
- `app/Models/TrafficSign.php`

### Routing i kontrolery

- `routes/web.php`
- `app/Http/Controllers/TrafficSignHubController.php`
- `app/Http/Controllers/TrafficSignCategoryController.php`
- `app/Http/Controllers/TrafficSignShowController.php`
- `app/Http/Controllers/ContentAuthorController.php`

### Support / SEO

- `config/content.php`
- `app/Support/TrafficSignSeoService.php`
- `app/Support/TrafficSignSchemaService.php`
- `app/Support/TrafficSignBreadcrumbs.php`
- `app/Support/TrafficSignRedirectPolicy.php`

### Widoki Blade

- `resources/views/layouts/public-content.blade.php`
- `resources/views/traffic-signs/index.blade.php`
- `resources/views/traffic-signs/category.blade.php`
- `resources/views/traffic-signs/show.blade.php`
- `resources/views/authors/show.blade.php`
- `resources/views/about/organization.blade.php`
- `resources/views/components/seo/breadcrumbs.blade.php`
- `resources/views/sitemaps/index.blade.php`
- `resources/views/sitemaps/urlset.blade.php`

### Filament

- `app/Filament/Resources/TrafficSigns/...`
- `app/Filament/Resources/TrafficSignCategories/...`
- `app/Filament/Resources/ContentAuthors/...`

### SEO / konfiguracja / public assets

- `composer.json`
- `routes/web.php`
- `app/Http/Controllers/RobotsController.php`
- `app/Http/Controllers/SitemapController.php`
- `config/content.php`

### Testy

- `tests/Feature/Public/TrafficSignPagesTest.php`
- `tests/Feature/Admin/TrafficSignPublicationTest.php`

## 8. Kolejnosc wdrozenia

### Milestone 0: Planning freeze

Cel:

- zamknac zakres `Etapu 1`,
- nie mieszac go z `/kodeks-drogowy` i `/mandaty`,
- ustalic, ze idziemy `Blade-first`.

Checklist:

- [x] Potwierdzic architekture `Blade-first` dla modulu SEO.
- [x] Potwierdzic, ze `Etap 1` nie obejmuje `kodeksu` i `mandatow`.
- [x] Utworzyc kanoniczny dokument planu wdrozenia.
- [x] Wskazac pierwsza partie danych testowych do wdrozenia `Etapu 1`.

### Milestone 1: Domain foundation

Cel:

- zbudowac minimalny model danych i kontrakt publikacji.

Checklist:

- [x] Dodac migracje dla `content_authors`.
- [x] Dodac migracje dla `traffic_sign_categories`.
- [x] Dodac migracje dla `traffic_signs`.
- [x] Dodac indeksy i unikalne slugi.
- [x] Dodac modele Eloquent z relacjami i castami.
- [x] Dodac scoped query dla `published`.
- [x] Dodac pola `published_at`, `updated_at` i statusy potrzebne do jawnych dat oraz future review.
- [x] Dodac fabryki lub prosty seed testowy dla rekordow contentowych.
- [x] Zaktualizowac `docs/DATABASE-SCHEMA.md`, jesli finalna implementacja zmieni kanoniczny model danych.

Warunek wyjscia:

- baza umie przechowac komplet jednego znaku, kategorii i autora bez workaroundow.

### Milestone 2: Admin and content ops

Cel:

- dac zespolowi miejsce do zarzadzania trescia bez edycji kodu.

Checklist:

- [x] Dodac `ContentAuthors` resource w `Filament`.
- [x] Dodac `TrafficSignCategories` resource w `Filament`.
- [x] Dodac `TrafficSigns` resource w `Filament`.
- [x] Dodac walidacje slugow i statusow publikacji.
- [x] Dodac pola SEO do formularzy admina.
- [x] Dodac repeater dla `faq_items`.
- [x] Dodac pola lub pomocnicze sekcje dla zrodel / notatek redakcyjnych, nawet jesli jeszcze nie beda publicznie renderowane szeroko.
- [x] Dodac widoczne ostrzezenia dla brakujacego `meta_title`, `meta_description`, `image_path` i `og_image_path`.
- [x] Dodac audit-friendly publikowanie / wycofywanie tresci.

Warunek wyjscia:

- admin jest w stanie dodac kompletnego autora, kategorie i znak z panelu.

### Milestone 3: Public rendering

Cel:

- wystawic nowy modul publiczny z pelnym HTML po stronie serwera.

Checklist:

- [x] Dodac publiczny layout `Blade` dla contentu.
- [x] Dodac kontroler i widok hubu `/znaki-drogowe`.
- [x] Dodac kontroler i widok kategorii `/znaki-drogowe/kategorie/{category}`.
- [x] Dodac kontroler i widok znaku `/znaki-drogowe/{sign}`.
- [x] Dodac kontroler i widok autora `/autorzy/{slug}`.
- [x] Dodac widocznego autora i widoczne daty publikacji / aktualizacji na stronie znaku.
- [x] Dodac `404` dla nieopublikowanych rekordow.
- [x] Dodac sekcje `powiazane znaki` bez recznego pivotu na start.
- [x] Podpiac obraz znaku jako glowny asset powyzej foldu.
- [x] Upewnic sie, ze wszystkie linki wewnetrzne sa crawlable jako `<a href>`.
- [x] Upewnic sie, ze layout i komponenty spelniaja podstawy dostepnosci semantycznej.

Warunek wyjscia:

- crawler i uzytkownik widza kompletne strony bez koniecznosci renderowania `Inertia`.

### Milestone 4: SEO foundation

Cel:

- zapewnic techniczne minimum SEO dla pierwszych publikowanych URL-i.

Checklist:

- [x] Dodac pakiet do meta tagow i OG albo swiadomie utrzymac wlasna cienka warstwe helperow.
- [x] Dodac generowanie `title`, `description` i `canonical`.
- [x] Dodac `Article` schema dla stron znaku.
- [x] Dodac `BreadcrumbList` schema dla stron kategorii i znaku.
- [x] Dodac `FAQPage` schema jako warstwe pomocnicza.
- [x] Dodac `ProfilePage` schema dla stron autorow.
- [x] Dodac `Organization` schema na stronie glownej lub stronie organizacji.
- [x] Dodac osobny `robots.txt` z linkiem do sitemap index.
- [x] Dodac generowanie sitemap index.
- [x] Dodac sitemap dla `traffic-signs`.
- [x] Dodac sitemap dla `traffic-sign-categories`.
- [x] Dodac sitemap dla `authors`.
- [x] Dodac image sitemap lub rozszerzenie image do sitemap znakow.
- [x] Zapewnic poprawne i wiarygodne `lastmod` w sitemapach.
- [x] Dodac `fetchpriority="high"` i jawne `width/height` dla glownego obrazu znaku.
- [x] Dodac preload obrazu LCP na stronach znaku.
- [x] Spisac polityke `301/308`, `404/410` i zmiany slugow.

Warunek wyjscia:

- kazda strona modulu przechodzi podstawowy reczny audit technicznego SEO i ma stabilne dane w `head`.

### Milestone 5: Sample content and QA

Cel:

- domknac pierwsza publikowalna partie i sprawdzic modul zanim zaczniemy masowy rollout.

Checklist:

- [x] Przygotowac pierwsza partie minimum `3` znakow do QA.
- [x] Przygotowac minimum `2` kategorie do QA.
- [x] Przygotowac minimum `1` strone autora do QA.
- [x] Sprawdzic recznie statusy `200 / 404`.
- [ ] Sprawdzic recznie scenariusz zmiany slugu i redirect `301/308`.
- [x] Sprawdzic recznie `title`, `description`, `canonical`, `OG`.
- [x] Sprawdzic recznie render `JSON-LD`.
- [x] Sprawdzic recznie breadcrumb UX i linkowanie wewnetrzne.
- [x] Sprawdzic widocznosc autora, dat i spojnosc tych danych z `JSON-LD`.
- [ ] Sprawdzic strony w `Rich Results Test` i `URL Inspection`.
- [x] Sprawdzic strony w `Schema Markup Validator`.
- [x] Dodac feature testy dla tras publicznych.
- [x] Dodac test publikacji / ukrywania tresci.
- [x] Dodac test polityki redirectow dla zmiany slugu, jesli wdrozymy ja juz w `Etapie 1`.
- [x] Uruchomic `php artisan test`.
- [x] Uruchomic `npm run build`.

Warunek wyjscia:

- mamy mala, ale kompletna paczke tresci gotowa do dalszego rolloutowego wzorca.

## 9. Minimalny rollout contentowy dla Etapu 1

Nie zaczynamy od `100+` stron. Zaczynamy od malego, reprezentatywnego zestawu.

Rekomendowany pierwszy batch:

- `1` autor
- `2` kategorie
- `3-5` znakow

Proponowana logika doboru:

- jeden znak bardzo popularny,
- jeden znak z wyraznym aspektem mandatowym,
- jeden znak z dobrym potencjalem edukacyjnym i linkowaniem wewnetrznym.

Cel tego batcha:

- sprawdzic model danych,
- sprawdzic ergonomie panelu,
- sprawdzic jak wyglada finalny layout,
- sprawdzic czy nie produkujemy zbyt cienkich tresci.

## 10. Status board do biezacego odhaczania

Aktualizowac ten blok po kazdym wiekszym kroku.

### Status ogolny

- `Etap 1`: `Ready for staging validation`
- `Milestone 0`: `Completed`
- `Milestone 1`: `Completed`
- `Milestone 2`: `Completed`
- `Milestone 3`: `Completed`
- `Milestone 4`: `Completed`
- `Milestone 5`: `Ready for staging validation`

### Ostatnio zakonczone

- utworzono model danych `author -> category -> sign` z migracjami, fabrykami i seederem QA,
- dodano backoffice `Filament` dla autorow, kategorii i znakow,
- wystawiono pierwsze publiczne trasy `Blade` dla hubu, kategorii, znaku i autora,
- dodano testy feature dla publicznego modulu i poprawiono cleanup testowej bazy sqlite na Windows,
- wdrozono cienka warstwe SEO: meta/head, `JSON-LD`, dynamiczny `robots.txt`, sitemapy XML i polityke `redirect / gone`,
- domknieto pierwszy batch QA `1 author / 2 categories / 3 signs`, dodano placeholderowe assety i runbook `Milestone 5`,
- browser QA ujawnil brak osobnego entry `Vite` dla publicznego layoutu `Blade`, co zostalo poprawione przez `resources/js/public-content.ts`,
- potwierdzono dzialanie modulu na realnym stacku `Docker Compose + PostgreSQL`, uruchomiono migracje i seed batcha QA bez przechodzenia na sqlite,
- publiczny pass przez tymczasowy tunnel ujawnil problem z `localhost/http` w canonicalach, assetach i schema za proxy; zostalo to naprawione przez `trustProxies` i host-aware `PublicUrlResolver`,
- oficjalny `Schema Markup Validator` przeszedl dla strony znaku bez bledow i bez warningow,
- domknieto fundament `Etapu 2A`: dodano workflow redakcyjny, pola review / source-check / freshness, checklisty publikacyjne oraz bulk actions do seryjnej pracy w panelu `Filament`,
- ujednolicono publiczny shell nawigacyjny: strony `Blade` modulu SEO dostaly wspolny header i footer zgodny z publiczna warstwa serwisu, a glowna nawigacja publiczna dostala bezposredni link do `/znaki-drogowe`,
- poprawiono lokalne renderowanie placeholderowych assetow znakow: checked-in SVG dla batcha QA sa serwowane z realnego `public/...`, co omija problem windowsowego `public/storage` junction w Dockerze,
- poprawiono punkt styku `Inertia -> Blade/Filament`: publiczne linki prowadzace do pelnych dokumentow (`/znaki-drogowe`, `/o-serwisie`, `/admin`) przechodza teraz przez zwykle document navigation zamiast `Inertia Link`,
- publiczny pack `php artisan test` dla modulu SEO przechodzi po zmianach; po domknieciu content ops zielony jest tez test adminowy dla seryjnych akcji `Filament`, a pelny suite repo nadal ma osobny, historyczny blocker srodowiskowy poza tym modulem.
- dodano trust layer `kontakt / metodologia`, rozszerzono kontrakt assetow o `alt + wymiary + OG metadata` i poszerzono batch QA z `3` do `5` znakow.
- dodano zasob `Mapa zapytan` w panelu `Filament`, seed operacyjnej watchlisty i pierwszy batch planowania `rollout-01` ponad obecny sample QA.
- realny pass przez panel admina potwierdzil create/edit/bulk publish dla nowych rekordow: `B-35` i `B-36` zostaly dodane, zaktualizowane i opublikowane bez dotykania kodu, a seed `rollout-01` zostal zsynchronizowany z tym wynikiem.
- domknieto pierwszy supporting page `A-7 vs B-20`, wraz z routingiem, schema, sitemap i linkowaniem powrotnym z kart znakow, co zamknelo `rollout-01` jako pierwszy pelny mini-klaster ponad sample QA.
- dodano kanoniczny inwentarz wszystkich znakow zakazu `B-1` do `B-44` oraz osobny seeder backlogu, dzieki czemu dalsze wdrazanie kategorii `Znaki zakazu` ma juz kompletna liste bez luk.
- rozszerzono `rollout-02-prohibitions` z samego backlogu do pelnego szkicu redakcyjnego: wszystkie brakujace znaki zakazu sa teraz w systemie jako `in_review` z bazowymi tresciami, FAQ, meta i placeholder assetami, bez wymuszania przedwczesnej publikacji cienkich stron.
- opublikowano pierwszy duzy batch `Znaki zakazu` po sample QA: `B-21`, `B-22`, `B-23`, `B-24`, `B-25`, `B-27`, `B-33`, `B-34`, `B-37`, `B-38`, `B-43`, `B-44`, a wraz z nimi `3` nowe materialy wspierajace (`B-21 vs B-23`, `B-33 vs B-43`, `B-35 vs B-36`).
- rollout-03 domknal kolejny duzy batch `Znaki zakazu`: opublikowano `B-3`, `B-3a`, `B-4`, `B-5`, `B-6`, `B-7`, `B-8`, `B-9`, `B-10`, `B-11`, `B-12`, `B-13`, `B-13a`, `B-14`, `B-41` i `B-42`, a razem z nimi `3` nowe materialy wspierajace (`B-1 vs B-2`, `B-3 vs B-5 vs B-7`, `B-13 vs B-13a vs B-14`).
- rollout-04 domknal cala kategorie `Znaki zakazu`: opublikowano pozostale `B-15`, `B-16`, `B-17`, `B-18`, `B-19`, `B-26`, `B-28`, `B-29`, `B-30`, `B-31`, `B-32`, `B-39` i `B-40`, a razem z nimi `4` nowe materialy wspierajace (`B-15 do B-19`, `B-25 do B-28`, `B-29 vs B-30`, `B-39 vs B-40`).
- publiczny corpus modulu wzrosl do `48` stron znakow, z czego `46` nalezy juz do kategorii `Znaki zakazu`.
- quality pass dla `Znaki zakazu` dodal semantyczny ranking `Powiazanych znakow` oraz automatyczny test calego klastra pod kluczowe sekcje, supporting links i stabilnosc publicznego corpusu.
- przygotowano wejscie w kolejna kategorie: `Znaki ostrzegawcze` dostaly oficjalny katalog `42` znakow, seeder backlogu i placeholder assets, a potem zostaly poprowadzone batchami do pelnego publicznego rollout.
- rollout-05 uruchomil pierwszy publiczny batch `Znaki ostrzegawcze`: opublikowano `A-5`, `A-6a`, `A-6b`, `A-6c`, `A-8`, `A-16` i `A-24` oraz `2` supporting pages (`A-5 do A-8`, `A-16 vs A-17 vs A-24`).
- rollout-06 rozszerzyl warning cluster o `A-1`, `A-2`, `A-3`, `A-4`, `A-9`, `A-10`, `A-11`, `A-11a`, `A-12a`, `A-12b` i `A-12c` oraz `3` nowe supporting pages (`A-1 do A-4`, `A-9 vs A-10`, `A-11 do A-12c`).
- rollout-07 domknal kolejny batch warning signs: opublikowano `A-6d`, `A-6e`, `A-14`, `A-15`, `A-18a`, `A-18b`, `A-20`, `A-29` i `A-30` oraz `4` nowe supporting pages (`A-6d vs A-6e`, `A-14 / A-15 / A-20`, `A-18a vs A-18b`, `A-29 vs A-30`).
- rollout-08 domknal cala kategorie `Znaki ostrzegawcze`: opublikowano `A-13`, `A-19`, `A-21`, `A-22`, `A-23`, `A-25`, `A-26`, `A-27`, `A-28`, `A-31`, `A-32`, `A-33` i `A-34` oraz `4` nowe supporting pages (`A-13 / A-19 / A-21`, `A-22 vs A-23`, `A-25 do A-28`, `A-31 do A-34`).
- po lokalnym seedzie modul ma teraz `88` rekordow znakow w bazie i `88` publicznych stron znakow; warning backlog dla tej kategorii zostal wyzerowany.
- quality pass dla `Znaki ostrzegawcze` zastapil wspolny `warning-generic` osobnymi assetami `main + OG` per znak i dodal sekcje `Powiazane porownania` na stronach kategorii, zeby warning supporting pages byly lepiej podlaczone wewnetrznie.
- rollout-09 domknal cala kategorie `Znaki nakazu`: opublikowano `23/23` znaki rodziny `C` oraz `6` supporting pages (`C-1 do C-4`, `C-5 do C-8`, `C-9 do C-11`, `C-13 do C-16a`, `C-14 vs C-15`, `C-18 vs C-19`).
- rollout-10 domknal pelny publiczny batch `Znaki informacyjne`: glowny katalog `D` ma w kodzie `72/72` publiczne znaki oraz `4` supporting pages (`D-1 vs D-2`, `D-4a vs D-4b`, `D-6 vs D-6a`, `D-18 / D-23 / D-28 / D-34`).
- glowny `TrafficSignSeoSeeder` wyszedl juz poza pierwotny kwartet `A/B/C/D` i aktywnie seeduje rollouty `11-16` dla `E`, `F`, `T`, `G`, `P` i `S`.
- lokalny seed modulu obejmuje obecnie `10` aktywnie obslugiwanych kategorii z realnym sign-level coverage: `A(42)`, `B(51)`, `C(23)`, `D(72)`, `E(7)`, `F(4)`, `T(4)`, `G(5)`, `P(6)`, `S(3)` czyli lacznie `217` stron znakow; osobne shelle pod `kontrolki`, `osobe kierujaca ruchem`, `znaki wojskowe`, `tramwaje` i `urzadzenia BRD` sa juz przygotowane w kodzie, ale nie maja jeszcze rownie kompletnej dokumentacji wykonawczej.

### Nastepny konkret

- przy braku stagingu lub serwera odkladamy finalny pass `Rich Results Test / URL Inspection` do momentu pojawienia sie stabilnego publicznego adresu,
- `A/B/C/D` sa juz najlepiej domkniete dokumentacyjnie; kolejny ruch dokumentacyjny to osobne inventory i runbooki dla rolloutow `11-16`, zeby docs dogonily aktywny zakres `TrafficSignSeoSeeder`,
- operacyjnie po `Znaki ostrzegawcze`, `Znaki zakazu`, `Znaki nakazu` i `Znaki informacyjne` najblizszy quality pass powinien objac publiczne rollouty `11-16`, zanim odpalimy kolejne fale `17+`,
- osobno od tego trzeba zdecydowac, ktora z juz przygotowanych, ale jeszcze nie wlaczonych rodzin (`kontrolki`, `osoba kierujaca ruchem`, `znaki wojskowe`, `sygnaly/znaki tramwajowe`, `urzadzenia BRD`) wchodzi jako kolejny priorytet tresciowy,
- stagingowe walidacje Google nadal wracaja dopiero wtedy, gdy pojawi sie stabilny publiczny adres.

## 11. Ryzyka i guardraile

### Ryzyko 1: Scope creep

Nie wolno w `Etapie 1` dorzucac:

- `kodeksu`,
- `mandatow` jako osobnego klastra,
- multilang,
- quiz engine na kartach znakow,
- automatycznego generowania dziesiatek stron.

### Ryzyko 2: Nadmierna zlozonosc frontendu

Nie wolno zamieniac tego etapu w refaktor calego publicznego frontendu.

Ten etap ma dodac nowy modul contentowy, nie przebudowac wszystko.

### Ryzyko 3: Thin content

Nie publikujemy znaku bez:

- sensownej definicji otwierajacej,
- sekcji zachowania kierowcy,
- sekcji bledow,
- minimum kilku sensownych pytan FAQ.

### Ryzyko 4: Niezweryfikowane tresci prawne

W `Etapie 1` tresci prawne i mandatowe maja byc:

- ostrozne,
- zrodlowe,
- gotowe na review przed szersza publikacja.

Nie probujemy jeszcze udawac kompletnego portalu prawnego na poziomie modelu danych i schema.

### Ryzyko 5: Brak warstwy zaufania i entity SEO

Sam `Article` na stronie znaku nie wystarczy.

Potrzebujemy tez:

- sensownej strony autora,
- widocznych byline i dat,
- strony organizacji / about,
- pozniej takze metodologii, polityki redakcyjnej i stron zrodlowych.

### Ryzyko 6: Brak jawnej ludzkiej kontroli nad trescia

W tej niszy nie wystarczy dobrze wygenerowac lub dobrze zlozyc tekst.

Musimy miec docelowo:

- jawna polityke redakcyjna,
- widoczny proces review,
- czytelna odpowiedzialnosc za aktualizacje i korekte tresci.

## 12. Rekomendowany pierwszy task wykonawczy

Najbardziej sensowny pierwszy task developerski po tym planie:

`Milestone 1 + poczatek Milestone 2`

Czyli:

- migracje,
- modele,
- podstawowe relacje,
- pierwszy resource `Filament` dla autorow i kategorii,
- bez jeszcze budowania calej warstwy widokow publicznych.

To da nam od razu:

- realny fundament pod dalszy kod,
- material do dalszego review,
- mozliwosc szybkiego sprawdzenia, czy zakres modelu danych jest dobrze dobrany.

## 13. Dokumenty powiazane

- [strategia-seo-znaki-drogowe.md](C:/Users/xxx/Desktop/strategia-seo-znaki-drogowe.md)
- [docs/SEO-CONTENT-ROADMAP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/SEO-CONTENT-ROADMAP.md)
- [docs/README.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/README.md)
- [docs/ROADMAP-TECH.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ROADMAP-TECH.md)
- [docs/STATUS-MVP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/STATUS-MVP.md)
- [docs/DATABASE-SCHEMA.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/DATABASE-SCHEMA.md)
- [docs/ZNAKI-OSTRZEGAWCZE-INWENTARZ.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ZNAKI-OSTRZEGAWCZE-INWENTARZ.md)
- [docs/ZNAKI-ZAKAZU-INWENTARZ.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ZNAKI-ZAKAZU-INWENTARZ.md)
- [docs/ZNAKI-NAKAZU-INWENTARZ.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ZNAKI-NAKAZU-INWENTARZ.md)
- [docs/ZNAKI-INFORMACYJNE-INWENTARZ.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ZNAKI-INFORMACYJNE-INWENTARZ.md)
- [routes/web.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/routes/web.php)
- [resources/views/app.blade.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/views/app.blade.php)
- [resources/js/app.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/app.ts)
