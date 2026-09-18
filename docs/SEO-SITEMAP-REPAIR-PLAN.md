# SEO Sitemap Repair Plan

Status: w implementacji na branchu `codex/seo-sitemaps-hardening`  
Data audytu: 2026-05-28  
Zakres: `robots.txt`, `sitemap.xml`, child sitemaps, sitemap pytań, walidacja produkcyjna  
Domena produkcyjna: `https://prawkonaraz.pl`

## 1. Cel

Celem jest zbudowanie stabilnej, szybkiej i przewidywalnej warstwy sitemap/robots, ktora maksymalnie ulatwia Google, Bing i innym respektujacym robotom odkrywanie publicznych stron serwisu.

Naprawa ma byc prowadzona pod SEO, czyli priorytetem jest:

- brak bledow `500`, `404` i redirectow w sitemap,
- jasna informacja o sitemap w `robots.txt`,
- jeden kanoniczny URL dla kazdej publicznej strony,
- szybkie serwowanie XML bez obciazania PHP i bazy przy kazdym wejsciu robota,
- mozliwosc regularnego audytu po deployu.

## 2. Stan obecny po audycie produkcji

### Co dziala

- `https://prawkonaraz.pl/sitemap.xml` zwraca `200`.
- Sitemap index wskazuje child sitemaps dla:
  - znakow drogowych,
  - huba pytan,
  - kategorii pytan,
  - pojedynczych pytan,
  - supporting pages znakow,
  - kategorii znakow,
  - autorow.
- Wiekszosc child sitemap zwraca `200` i poprawny `Content-Type: application/xml`.
- Publiczne URL-e w sitemap sa generowane jako `https://prawkonaraz.pl/...`.
- Sitemap znakow zawiera wpisy `image:image`.

### Co nie dziala

1. `https://prawkonaraz.pl/sitemaps/questions.xml` zwraca `500`.

   Przyczyna z produkcyjnego `nginx/error.log`:

   - `Allowed memory size of 134217728 bytes exhausted`,
   - endpoint probuje zbudowac sitemap pytań zbyt ciezko w runtime,
   - aktualny kod laduje zbyt duzo rekordow Eloquent i mediow naraz.

2. `https://prawkonaraz.pl/robots.txt` nie pokazuje naszego kontrolera Laravel.

   Aktualnie publiczna odpowiedz pochodzi z warstwy Cloudflare Managed Content / managed robots, a nie z `RobotsController`.

   Efekt SEO:

   - publiczny `robots.txt` nie zawiera naszego wpisu `Sitemap: https://prawkonaraz.pl/sitemap.xml`,
   - roboty nie dostaja najprostszego sygnalu, gdzie jest mapa serwisu.

3. Dynamiczne XML-e sa generowane przez `web` middleware.

   Efekt uboczny:

   - odpowiedzi sitemap moga dostawac cookies sesyjne,
   - cache headers nie sa idealne dla crawlerow,
   - kazdy request crawlera moze dotykac aplikacji i bazy.

## 3. Zasady SEO, ktorych sie trzymamy

### 3.1 Tylko strony indeksowalne

Sitemap moze zawierac tylko URL-e, ktore:

- zwracaja `200 OK`,
- sa publiczne,
- maja self-canonical albo jednoznaczny canonical na siebie,
- nie sa za loginem,
- nie sa adminem,
- nie sa API,
- nie sa wynikami filtrowania bez wartosci SEO,
- nie sa stronami testowymi ani technicznymi.

### 3.2 Zero redirectow w sitemap

W sitemap nie umieszczamy:

- `http://...`,
- `www.prawkonaraz.pl`,
- starych domen `prawkoapp.pl` i `prawkobit.pl`,
- adresow, ktore koncza jako `301` lub `302`.

Docelowy standard:

```txt
https://prawkonaraz.pl/...
```

### 3.3 Jeden kanoniczny URL na jedno logiczne pytanie

Pytanie moze wystepowac w kilku kategoriach albo wariantach, ale w sitemap powinno miec jeden wpis kanoniczny.

Regula:

- grupujemy po `external_id`,
- wybieramy reprezentanta zgodnie z logika aplikacji,
- generujemy jeden URL typu:

```txt
https://prawkonaraz.pl/pytanie/{external_id}/{slug}
```

Przyklad:

```xml
<url>
    <loc>https://prawkonaraz.pl/pytanie/99/czy-w-tej-sytuacji-masz-obowiazek-zatrzymac-pojazd</loc>
    <lastmod>2026-05-28T13:40:00+02:00</lastmod>
</url>
```

### 3.4 `lastmod` tylko prawdziwy

`lastmod` ma wskazywac realna ostatnia zmiane tresci strony, nie czas wygenerowania sitemap.

Dla pytan:

- max `updated_at` z grupy pytan tego samego `external_id`,
- ewentualnie w przyszlosci rowniez zmiana publicznych assetow powiazanych z pytaniem, jesli realnie zmienia zawartosc strony.

Dla kategorii pytan:

- max `updated_at` publicznych pytan w kategorii.

Dla znakow:

- `updated_at` znaku albo max ze zmian powiazanych publicznych elementow.

Nie dodajemy `changefreq` i `priority`, bo Google ich nie uzywa jako sygnalu rankingowego/crawl scheduling w praktycznym sensie. Skupiamy sie na `loc` i wiarygodnym `lastmod`.

### 3.5 Statyczne pliki sa preferowane nad runtime generation

Docelowo robot powinien pobierac gotowy XML z `public/`, a nie uruchamiac drogi kontroler i zapytania SQL.

Powod:

- mniejsze zuzycie RAM,
- szybsza odpowiedz,
- mniejsze ryzyko `500`,
- latwiejszy cache,
- latwiejszy audit,
- mniejsze obciazenie malego VPS-a.

## 4. Docelowa architektura

### 4.1 Pliki publiczne

Docelowy zestaw publiczny:

```txt
/robots.txt
/sitemap.xml
/sitemaps/static.xml
/sitemaps/question-hub.xml
/sitemaps/question-categories.xml
/sitemaps/questions-a.xml
/sitemaps/questions-a1.xml
/sitemaps/questions-a2.xml
/sitemaps/questions-am.xml
/sitemaps/questions-b.xml
/sitemaps/questions-b1.xml
/sitemaps/questions-c.xml
/sitemaps/questions-c1.xml
/sitemaps/questions-d.xml
/sitemaps/questions-d1.xml
/sitemaps/questions-t.xml
/sitemaps/traffic-signs.xml
/sitemaps/traffic-sign-categories.xml
/sitemaps/traffic-sign-supporting-pages.xml
/sitemaps/authors.xml
/sitemaps/articles.xml
/sitemaps/news.xml
```

Dla newsroomu `/sitemaps/articles.xml` jest nazwą pojedynczego pliku przy małym corpus. Po przekroczeniu skonfigurowanego zakresu N5-001 generuje stabilne shard files `/sitemaps/articles-{id-range}.xml` i wpisuje je bezpośrednio do głównego `/sitemap.xml`; nie powstaje zagnieżdżony newsroom sitemap-index.

Dla Google News `/sitemaps/news.xml` jest nazwą pojedynczego pliku do 1000 kwalifikowanych wpisów. Po przekroczeniu limitu N5-002 generuje stabilne fixed-`content_articles.id` range shards `/sitemaps/news-{id-range}.xml` i również wpisuje je bezpośrednio do głównego `/sitemap.xml`; nie powstaje osobny nested news sitemap-index.

`/sitemap.xml` ma byc sitemap index, czyli lista child sitemap.

### 4.2 Podzial pytan

Pytania dzielimy po kategoriach prawa jazdy, a nie wrzucamy wszystkich do jednego `questions.xml`.

Powody:

- mniejsze pliki,
- latwiejszy monitoring w Google Search Console,
- latwiej wykryc, ktora kategoria ma problem,
- mniej ryzyka pamieciowego,
- lepsza obserwowalnosc indeksacji per klaster.

Jesli kiedys jedna kategoria przekroczy limit sitemap, dzielimy dalej:

```txt
/sitemaps/questions-b-001.xml
/sitemaps/questions-b-002.xml
```

Limity protokolu:

- maksymalnie `50 000` URL-i w jednym sitemap,
- maksymalnie `50 MB` po rozpakowaniu w jednym sitemap.

W praktyce ustawiamy wlasny konserwatywny limit:

- maksymalnie `10 000-20 000` URL-i na plik,
- preferowany podzial logiczny po kategorii.

### 4.3 `robots.txt`

Docelowa minimalna tresc:

```txt
User-agent: *
Allow: /

Disallow: /admin/
Disallow: /api/

Sitemap: https://prawkonaraz.pl/sitemap.xml
```

Decyzja implementacyjna:

- preferujemy statyczny `public/robots.txt`,
- nginx moze serwowac ten plik bez przechodzenia przez Laravel,
- w Cloudflare trzeba wylaczyc lub skorygowac managed `robots.txt`, jesli nadpisuje nasza tresc.

### 4.4 Cache i naglowki

Dla XML/TXT:

```txt
Content-Type: application/xml; charset=UTF-8
Cache-Control: public, max-age=3600
```

Dla `robots.txt`:

```txt
Content-Type: text/plain; charset=UTF-8
Cache-Control: public, max-age=3600
```

Nie powinno byc:

- `Set-Cookie`,
- sesji Laravel,
- `Cache-Control: no-cache, private`.

## 5. Plan implementacji

### Faza 0: Branch i baseline

Cel: zaczac bezpiecznie i miec powtarzalny punkt odniesienia.

Kroki:

1. Utworzyc branch:

   ```bash
   git checkout -b codex/seo-sitemaps-hardening
   ```

2. Zanotowac aktualne wyniki produkcji:

   ```bash
   curl -I https://prawkonaraz.pl/robots.txt
   curl -I https://prawkonaraz.pl/sitemap.xml
   curl -I https://prawkonaraz.pl/sitemaps/questions.xml
   ```

3. Potwierdzic aktualny blad w logach:

   - `nginx/error.log`,
   - `storage/logs/laravel-YYYY-MM-DD.log`.

Status wejscia:

- `robots.txt` nie jest naszym plikiem,
- `questions.xml` zwraca `500`.

### Faza 1: Naprawa `robots.txt`

Cel SEO: kazdy robot ma od razu dostac link do sitemap index.

Kroki:

1. Dodac statyczny plik:

   ```txt
   public/robots.txt
   ```

2. Tresc:

   ```txt
   User-agent: *
   Allow: /

   Disallow: /admin/
   Disallow: /api/

   Sitemap: https://prawkonaraz.pl/sitemap.xml
   ```

3. Zweryfikowac nginx:

   - obecne `location = /robots.txt` moze zostac, jesli plik fizycznie istnieje,
   - jesli nadal zwraca zla tresc, poprawic nginx lub Cloudflare.

4. Zweryfikowac Cloudflare:

   - sprawdzic, czy Managed `robots.txt` nie nadpisuje pliku,
   - po zmianie wyczyscic cache dla `/robots.txt`.

Kryteria akceptacji:

```bash
curl -s https://prawkonaraz.pl/robots.txt
```

musi zawierac:

```txt
Sitemap: https://prawkonaraz.pl/sitemap.xml
```

### Faza 2: Generator statycznych sitemap

Cel SEO: robot pobiera gotowy XML, a nie odpala ciezki runtime aplikacji.

Dodac komendy:

```bash
php artisan seo:generate-sitemaps
php artisan seo:audit-sitemaps
```

`seo:generate-sitemaps`:

- generuje pliki do `public/sitemaps`,
- generuje `public/sitemap.xml`,
- uzywa tylko publicznych, indeksowalnych URL-i,
- generuje XML atomowo:
  - zapis do pliku tymczasowego,
  - walidacja,
  - rename na plik docelowy.

`seo:audit-sitemaps`:

- sprawdza lokalne pliki XML,
- liczy URL-e,
- sprawdza limity,
- wykrywa `http://`,
- wykrywa stare domeny,
- wykrywa duplikaty `<loc>`,
- opcjonalnie robi HTTP HEAD/GET na publicznym URL-u po deployu.

Kryteria akceptacji:

- generator mozna uruchomic recznie,
- audyt zwraca exit code `0`,
- wygenerowane pliki sa deterministyczne przy tych samych danych.

### Faza 3: Sitemap pytan per kategoria

Cel SEO: kazde publiczne pytanie ma osobny kanoniczny wpis, bez `500`.

Zmieniamy obecny model:

```txt
/sitemaps/questions.xml
```

na:

```txt
/sitemaps/questions-a.xml
/sitemaps/questions-b.xml
/sitemaps/questions-c.xml
...
```

Logika danych:

- baza: `questions`,
- filtr:
  - `is_active = true`,
  - `readyForDelivery`,
  - `external_id` niepusty,
  - kategoria widoczna publicznie,
- grupowanie:
  - jeden wpis per `external_id`,
- reprezentant:
  - zgodny z aktualna logika `PublicQuestionCatalogService`,
  - preferuje rekord z mediami i stabilna kategorie.

Wydajnosc:

- nie ladowac wszystkich pytań z mediami przez Eloquent naraz,
- uzyc `chunkById`, `cursor` albo zoptymalizowanego query,
- pobierac tylko pola potrzebne do sitemap:
  - `id`,
  - `external_id`,
  - `prompt`,
  - `updated_at`,
  - `license_category_id`,
  - pola mediow potrzebne do publicznego obrazu/postera.

Media:

- dodawac `image:image` tylko dla publicznych obrazow/posterow,
- nie dodawac prywatnych, brakujacych albo niestabilnych URL-i,
- video sitemap odlozyc do osobnej fazy po potwierdzeniu stabilnych publicznych miniaturek i plikow.

Kryteria akceptacji:

- nie istnieje juz jedna ciezka mapa, ktora zjada RAM,
- kazda kategoria zwraca `200`,
- kazdy publiczny URL pytania jest `https://prawkonaraz.pl/...`,
- brak duplikatow po `external_id`.

### Faza 4: Pozostale sitemap

Cel SEO: zachowac to, co juz dziala, ale przeprowadzic przez ten sam standard.

Do wygenerowania statycznie:

- `static.xml`,
- `question-hub.xml`,
- `question-categories.xml`,
- `traffic-signs.xml`,
- `traffic-sign-categories.xml`,
- `traffic-sign-supporting-pages.xml`,
- `authors.xml`.

`static.xml` powinien objac tylko publiczne strony stale, np.:

- `/`,
- `/cennik`,
- `/o-serwisie`,
- `/oficjalna-baza-pytan-na-prawo-jazdy`,
- `/najtrudniejsze-pytania-na-prawo-jazdy`,
- inne publiczne landingi SEO, jesli spelniaja warunki indeksacji.

Nie dodawac:

- `/login`,
- `/register`,
- `/profile`,
- `/nauka`,
- `/admin`,
- `/api`,
- flow platnosci,
- stron sesji uzytkownika.

Kryteria akceptacji:

- wszystkie obecne sitemap dzialajace przed zmiana nadal istnieja w nowym modelu,
- sitemap index wskazuje wszystkie aktualne child sitemaps,
- zadna child sitemap nie jest pusta bez powodu.

### Faza 5: Routing i kompatybilnosc

Cel SEO: nie robic naglych 404 dla starych adresow sitemap.

Opcje:

1. `/sitemaps/questions.xml` jako legacy index dla nowych map pytan.
2. `/sitemaps/questions.xml` jako redirect `301` do `/sitemap.xml`.
3. Usuniecie dopiero po upewnieniu sie, ze Google nie ma go juz jako aktywnego child sitemap.

Rekomendacja:

- przez pierwsze wdrozenie zostawic `/sitemaps/questions.xml` jako lekki sitemap index albo statyczny plik legacy,
- nie zostawiac go jako endpointu generujacego `500`.

Kryteria akceptacji:

- stary URL nie zwraca `500`,
- najlepiej zwraca `200` z lekka trescia albo `301` do aktualnego indeksu,
- Google Search Console nie dostaje naglej awarii.

### Faza 6: Testy automatyczne

Dodac lub rozszerzyc testy:

1. `RobotsTxtTest`

   Sprawdza:

   - `robots.txt` istnieje,
   - zawiera `Sitemap: https://prawkonaraz.pl/sitemap.xml`,
   - blokuje `/admin/` i `/api/`,
   - nie blokuje `/`.

2. `SitemapGenerationCommandTest`

   Sprawdza:

   - komenda generuje `sitemap.xml`,
   - generuje child sitemaps,
   - XML jest poprawny,
   - pytania sa dzielone per kategoria,
   - duplikaty `external_id` nie tworza duplikatow URL-i.

3. `SitemapXmlContractTest`

   Sprawdza:

   - brak `http://`,
   - brak starych domen,
   - brak `/admin`,
   - brak `/api`,
   - brak pustych `<loc>`,
   - poprawny namespace XML,
   - `lastmod` ma poprawny format.

4. Test wydajnosciowy/operacyjny komendy

   Minimum:

   - generator nie przekracza rozsadnej pamieci,
   - nie laduje calej bazy pytan z mediami jako jeden wielki graf Eloquent.

### Faza 7: Deploy

Przed deployem:

```bash
php artisan test tests/Feature/Public
php artisan seo:generate-sitemaps
php artisan seo:audit-sitemaps
```

Po deployu na serwerze:

```bash
cd /var/www/prawkobit/current
php artisan seo:generate-sitemaps
php artisan seo:audit-sitemaps
```

Potem smoke publiczny:

```bash
curl -I https://prawkonaraz.pl/robots.txt
curl -I https://prawkonaraz.pl/sitemap.xml
curl -I https://prawkonaraz.pl/sitemaps/questions-b.xml
curl -I https://prawkonaraz.pl/sitemaps/traffic-signs.xml
```

Kryteria akceptacji:

- `robots.txt` zwraca `200`,
- `robots.txt` zawiera `Sitemap: https://prawkonaraz.pl/sitemap.xml`,
- `sitemap.xml` zwraca `200`,
- kazdy child sitemap zwraca `200`,
- brak `500`,
- brak `Set-Cookie` na sitemap/robots,
- brak `http://` w XML,
- brak starych domen.

### Faza 8: Google Search Console i Bing

Po technicznym wdrozeniu:

1. Dodac lub odswiezyc sitemap w Google Search Console:

   ```txt
   https://prawkonaraz.pl/sitemap.xml
   ```

2. Dodac sitemap w Bing Webmaster Tools.

3. W GSC sprawdzic:

   - status przetworzenia sitemap,
   - liczbe odkrytych URL-i,
   - bledy parsowania,
   - bledy `Couldn't fetch`,
   - `Indexed` vs `Discovered - currently not indexed`.

4. Nie wysylac osobnych child sitemap recznie, jesli index sitemap jest poprawny. Wystarczy glowny `sitemap.xml`, chyba ze GSC pokazuje problem z konkretnym klastrem.

5. Po dodaniu sitemap zapisac w dokumentacji status:

   - data dodania do Google Search Console,
   - data dodania do Bing Webmaster Tools,
   - status pierwszego przetworzenia,
   - ewentualne bledy z raportow.

## 6. Monitoring po wdrozeniu

### Codziennie przez pierwsze 7 dni

Sprawdzic:

- `robots.txt`,
- `sitemap.xml`,
- status child sitemap pytań,
- logi nginx pod `sitemaps`,
- logi Laravel pod bledy generatora.

### Co tydzien

Sprawdzic:

- GSC: sitemap status,
- GSC: coverage/indexing dla pytan,
- URL Inspection dla kilku reprezentatywnych pytan,
- czy Google widzi canonical poprawnie.

### Alerty / komenda health

Docelowo `ops:health-report` albo osobna komenda `seo:audit-sitemaps` powinna wykrywac:

- `robots.txt` bez `Sitemap:`,
- sitemap child `500`,
- sitemap child `404`,
- stara domena w XML,
- `http://` w XML,
- pusty sitemap,
- przekroczenie limitow.

## 7. Kolejnosc priorytetow

### P0: Krytyczne

1. Naprawic `robots.txt`.
2. Usunac `500` z `/sitemaps/questions.xml`.
3. Wygenerowac lekkie sitemap pytań per kategoria.
4. Potwierdzic publiczne `200` dla wszystkich sitemap.

### P1: Wysoka wartosc SEO

1. Statyczny generator sitemap.
2. Audyt sitemap jako komenda.
3. Brak cookies/sesji na XML/TXT.
4. Stabilne `lastmod`.
5. GSC/Bing submission.

### P2: Rozszerzenia

1. Image sitemap dla pytan, jesli media sa publiczne i stabilne.
2. Video sitemap dla pytan z filmami, po osobnym audycie publicznej dostepnosci wideo/posterow.
3. Dalsze sitemap dla topic/guide/ranking/stats pages, dopiero gdy te strony beda mialy unikalna wartosc SEO.

## 8. Ryzyka

### Ryzyko 1: Duplikaty pytan

Problem:

- jedno pytanie moze istniec w kilku wariantach/kategoriach.

Mitigacja:

- jeden URL per `external_id`,
- test na duplikaty `<loc>`,
- test na duplikaty external id w sitemap.

### Ryzyko 2: Sitemap zawiera URL-e bez publicznej wartosci

Problem:

- zbyt szeroka sitemap moze obnizyc jakosc sygnalu.

Mitigacja:

- wlaczamy tylko strony publiczne, crawlable i indeksowalne,
- nie publikujemy search/facet pages bez unikalnej tresci.

### Ryzyko 3: Nieprawdziwy `lastmod`

Problem:

- Google moze przestac ufac `lastmod`, jesli zmienia sie bez realnej zmiany tresci.

Mitigacja:

- nie uzywamy czasu generowania sitemap,
- bierzemy realne `updated_at` z danych contentowych.

### Ryzyko 4: Cloudflare nadpisuje `robots.txt`

Problem:

- aplikacja moze byc poprawna, ale publiczny robot nadal widzi Cloudflare managed robots.

Mitigacja:

- test publiczny po deployu,
- wylaczenie/skorygowanie managed robots,
- purge cache dla `/robots.txt`.

### Ryzyko 5: Stare adresy sitemap w GSC

Problem:

- Google moze miec zapamietany `/sitemaps/questions.xml`.

Mitigacja:

- nie zostawiamy starego URL-a jako `500`,
- dajemy lekki legacy response albo `301`,
- obserwujemy GSC po wdrozeniu.

## 9. Definition of Done

Naprawe uznajemy za zakonczona, gdy:

- `https://prawkonaraz.pl/robots.txt` zwraca `200`,
- publiczny `robots.txt` zawiera `Sitemap: https://prawkonaraz.pl/sitemap.xml`,
- `https://prawkonaraz.pl/sitemap.xml` zwraca `200`,
- wszystkie child sitemap z indexu zwracaja `200`,
- zadna sitemap nie zwraca `500`,
- `questions.xml` nie zjada pamieci PHP,
- publiczne sitemap nie ustawiaja cookies sesyjnych,
- XML jest poprawny skladniowo,
- sitemap zawiera tylko `https://prawkonaraz.pl`,
- nie ma URL-i `/admin`, `/api`, `/profile`, `/nauka`,
- kazde publiczne pytanie ma jeden kanoniczny wpis,
- komenda audytu przechodzi lokalnie i na produkcji,
- sitemap zostala dodana/odswiezona w Google Search Console.

## 9.1. Status implementacji 2026-05-28

Wykonane na branchu `codex/seo-sitemaps-hardening`:

- dodano statyczny `public/robots.txt` z linkiem do `https://prawkonaraz.pl/sitemap.xml`,
- dodano generator `php artisan seo:generate-sitemaps`,
- dodano audyt `php artisan seo:audit-sitemaps`,
- przeniesiono wspolna logike sitemap do serwisow `SeoSitemapBuilder`, `SeoSitemapGenerator`, `SeoSitemapAuditor` i `SeoSitemapXmlRenderer`,
- `/sitemaps/questions.xml` przestal byc ciezka mapa pojedynczych pytan i stal sie lekkim legacy sitemap index,
- dodano kategorie pytan jako osobne sitemap:
  - `/sitemaps/questions-a.xml`,
  - `/sitemaps/questions-b.xml`,
  - itd.,
- dodano testy dla generatora i lekkiej trasy legacy,
- potwierdzono na realnej bazie Docker, ze generator tworzy osobne pliki i audyt przechodzi po wymuszeniu produkcyjnego `APP_URL=https://prawkonaraz.pl`.

Wynik realnego generatora na bazie developerskiej:

- `sitemap.xml`: 17 wpisow,
- `question-hub.xml`: 1 URL,
- `question-categories.xml`: 11 URL-i,
- `questions.xml`: 10 wpisow jako legacy index,
- `questions-a.xml`: 1428 URL-i,
- `questions-a1.xml`: 4 URL-e,
- `questions-a2.xml`: 4 URL-e,
- `questions-am.xml`: 299 URL-i,
- `questions-b.xml`: 917 URL-i,
- `questions-b1.xml`: 14 URL-i,
- `questions-c.xml`: 170 URL-i,
- `questions-d.xml`: 182 URL-e,
- `questions-d1.xml`: 1 URL,
- `questions-t.xml`: 137 URL-i,
- `traffic-signs.xml`: 249 URL-i,
- `traffic-sign-supporting-pages.xml`: 46 URL-i,
- `traffic-sign-categories.xml`: 16 URL-i,
- `authors.xml`: 1 URL.

Uwagi przed deployem:

- wygenerowane XML-e nie sa commitowane do repo; sa artefaktem operacyjnym,
- po deployu trzeba uruchomic `php artisan seo:generate-sitemaps` na serwerze w katalogu widzianym przez nginx,
- potem koniecznie `php artisan seo:audit-sitemaps`,
- jesli publiczny `robots.txt` nadal pokazuje Cloudflare Managed Content bez `Sitemap:`, trzeba skorygowac ustawienie Cloudflare albo wyczyscic cache dla `/robots.txt`.

## 9.3. Rozszerzenie Newsroom N5-001 — status kodu 2026-09-18

Potwierdzony stan repo po PR #97, merge `main@5ddfa48c0646fa89cc802d129e6d9ccee9d18957`:

- istniejące `SeoSitemapBuilder`, `SeoSitemapGenerator` i `SeoSitemapAuditor` zostały rozszerzone zamiast tworzenia drugiego generatora,
- standard article sitemap obejmuje indexable newsroom/guide current-canonical URLs przy aktywnej kategorii i publicznym autorze; noindex/draft/withdrawn oraz canonical path kolidujący z historycznym redirect source są wykluczane,
- `NEWSROOM_PUBLIC_ENABLED=false` wyłącza article shards i newsroom hub coverage,
- `static.xml` obejmuje rollout-gated `/aktualnosci`, warunkowo `/poradniki`, aktywne category hubs i published topic hubs,
- article `lastmod` używa `max(first_published_at, last_substantive_update_at, public_state_changed_at)`, a nie czasu generowania XML,
- pojedynczy corpus używa `/sitemaps/articles.xml`; większy corpus używa stabilnych fixed-`content_articles.id` range shards bez OFFSET shardingu,
- root `/sitemap.xml` wskazuje article file/shards bezpośrednio; nie dodano nested newsroom sitemap-index,
- generator i auditor mają twarde guardy `50 000` entries i `50 MB` nieskompresowanego XML,
- exact-head CI #367 oraz post-merge CI #368 zakończyły PASS; post-merge: 1098 passed / 19 926 assertions / 2 skipped, Pint PASS, frontend build PASS, PostgreSQL 7/94.

Ten wpis potwierdza **stan kodu i CI**, nie stan produkcyjnego delivery. Nadal nie ma w tym tasku potwierdzenia publicznych Nginx/CDN headers/304, GSC/Bing processing, child-before-index atomic set publication ani obsolete-shard cleanup. Te elementy pozostają osobnymi zadaniami i nie wolno traktować ich jako wykonanych na podstawie N5-001.

## 9.4. Google News Sitemap N5-002 — status kodu 2026-09-18

Potwierdzony stan repo po PR #99, merge `main@827f3816487d3a404df26c381a103a6cd1a9f413`:

- Google News constraints zostały ponownie sprawdzone przy implementacji: 2-dniowe okno po pierwotnym `first_published_at`, maks. 1000 `news:news` entries per plik oraz wymagane `news:name`, `news:language`, `news:publication_date`, `news:title`,
- istniejący `SeoSitemapBuilder`, `SeoSitemapGenerator` i `SeoSitemapXmlRenderer` generują statyczną News Sitemap; nie ma drugiego generatora,
- corpus wymaga `NEWSROOM_PUBLIC_ENABLED=true`, `type=news`, aktywnej dystrybucji, workflow `published`, indexable current canonical, aktywnej kategorii i publicznego autora,
- świeżość jest liczona wyłącznie przez `first_published_at >= now()-2 days`; późniejsza aktualizacja lub ponowne ustawienie `published_at` nie przywraca starego artykułu do News Sitemap,
- `news:name` pochodzi z istniejącego `SiteIdentitySchema::siteName()`, language to `pl`, publication date to pierwotny `first_published_at`, a title to widoczny `ContentArticle.title`,
- do 1000 entries używany jest `/sitemaps/news.xml`; powyżej limitu powstają deterministic fixed-ID-range shards wskazywane bezpośrednio przez root sitemap index,
- istniejący auditor dopuszcza legalny overlap URL pomiędzy standard article sitemap i News Sitemap; pełne newsroom/news namespace/required-tag/age/shard checks pozostają osobnym N5-006,
- exact-head CI #371 oraz post-merge CI #372 zakończyły PASS; post-merge: 1100 passed / 19 964 assertions / 2 skipped, Pint PASS, frontend build 10.28 s, PostgreSQL 7/94.

Ten wpis potwierdza **stan kodu i CI**, nie produkcyjne delivery. Nadal nie ma dowodu publicznych Nginx/CDN headers/validators, Google News/GSC processing, child-before-index atomic set publication, obsolete-shard cleanup ani kilku-minutowego dirty/version refresh SLA. Nie wolno traktować ich jako wykonanych na podstawie N5-002.

## 9.2. Automatyczne odswiezanie sitemap

Wykonane na branchu `codex/seo-sitemap-scheduler`:

- dodano komenda operacyjna `php artisan seo:refresh-sitemaps`,
- `seo:refresh-sitemaps` generuje statyczne sitemap i od razu uruchamia audyt,
- Laravel scheduler uruchamia `seo:refresh-sitemaps` codziennie na produkcji o `03:30`,
- godzine mozna zmienic przez `SEO_SITEMAP_REFRESH_AT`,
- po udanym imporcie katalogu JSON, manifestu albo serii manifestow aplikacja automatycznie odswieza sitemap,
- tryb `--dry-run` i importy zakonczone bledami nie odswiezaja sitemap, zeby nie publikowac pol-stanu,
- do diagnostyki nadal zostaja osobne komendy:

  ```bash
  php artisan seo:generate-sitemaps
  php artisan seo:audit-sitemaps
  ```

Status produkcyjny do potwierdzenia po deployu:

```bash
php artisan schedule:list | grep seo:refresh-sitemaps
php artisan seo:refresh-sitemaps
```

## 10. Zrodla i standardy

- Google Search Central: budowanie i zglaszanie sitemap  
  `https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap`
- Google robots.txt specification  
  `https://developers.google.com/crawling/docs/robots-txt/robots-txt-spec`
- Sitemaps protocol  
  `https://www.sitemaps.org/protocol.html`
- Cloudflare Managed robots.txt  
  `https://developers.cloudflare.com/bots/additional-configurations/managed-robots-txt/`
