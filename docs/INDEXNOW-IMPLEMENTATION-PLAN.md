# IndexNow Implementation Plan

Status: faza 1 wdrozona na produkcji; faza 2 automatyzacji zaimplementowana lokalnie i czeka na rollout
Data: 2026-07-09, aktualizacja: 2026-07-10  
Domena produkcyjna: `https://prawkonaraz.pl`  
Zakres: IndexNow dla publicznych, kanonicznych URL-i SEO

## 1. Cel

Celem jest dodanie IndexNow w sposob kontrolowany i zgodny z obecna architektura SEO projektu.

IndexNow ma sluzyc do powiadamiania wyszukiwarek, ze konkretne publiczne URL-e zostaly dodane, zmienione albo usuniete. To nie zastepuje sitemap i nie gwarantuje indeksacji. Jest to dodatkowy sygnal, ktory moze przyspieszyc odkrywanie zmian przez Bing i inne wyszukiwarki uczestniczace w protokole.

Nie wdrazamy tego na slepo. Najpierw opieramy sie na juz istniejacym, przetestowanym zrodle prawdy dla URL-i: sitemapach i katalogach publicznych stron.

## 2. Zrodla protokolu

Oficjalne materialy:

- `https://www.indexnow.org/documentation`
- `https://www.indexnow.org/faq`
- `https://www.bing.com/indexnow/getstarted`

Wnioski praktyczne:

- klucz ma miec od 8 do 128 znakow,
- dozwolone sa litery, cyfry i myslniki,
- preferowany plik weryfikacyjny to `https://prawkonaraz.pl/{key}.txt`,
- plik musi byc publiczny, w UTF-8 i zawierac sam klucz,
- bulk submit idzie przez `POST https://api.indexnow.org/indexnow`,
- payload zawiera `host`, `key`, `urlList` i opcjonalnie `keyLocation`,
- jeden request moze zawierac maksymalnie 10 000 URL-i,
- odpowiedzi `200` i `202` traktujemy jako przyjete,
- `400`, `403`, `422`, `429` wymagaja raportowania i ostroznego retry,
- nie nalezy masowo wysylac starych URL-i bez realnej zmiany tresci.

## 3. Gdzie jestesmy

### Zrobione

- przeprowadzony audyt kodu SEO/sitemap,
- potwierdzona domena kanoniczna `https://prawkonaraz.pl`,
- potwierdzone, ze sitemap pipeline juz istnieje i jest dobrym zrodlem URL-i,
- potwierdzone, ze produkcja ma `QUEUE_CONNECTION=sync`,
- potwierdzone, ze prawdziwego klucza IndexNow nie nalezy commitowac do repo,
- uruchomiony celowany test sitemap,
- dodana konfiguracja `config/indexnow.php`,
- dodane zmienne `INDEXNOW_*` do env examples,
- dodana komenda `seo:indexnow-key-file`,
- dodana obsluga `seo:indexnow-key-file --source=...` dla klucza przekazanego jako lokalny plik `.txt`,
- dodany kolektor publicznych URL-i z istniejacych zrodel sitemap,
- dodany serwis submitowania URL-i do IndexNow,
- dodana komenda `seo:indexnow-submit`,
- dodana obsluga `seo:indexnow-submit --key-source=...` dla submitu bez wpisywania klucza do lokalnego `.env`,
- dodane testy komendy pliku klucza,
- dodane testy komendy submitowania,
- dodany `.gitignore` dla publicznych plikow klucza `.txt`,
- celowany test `IndexNowKeyFileCommandTest` + `IndexNowSubmitCommandTest` + regresja `SeoSitemapGenerationTest` przechodza lokalnie,
- commit wdrozeniowy: `33b6ff60 Add IndexNow key and submit tooling`,
- faza 1 wdrozona na produkcji,
- dodana tabela kolejki `indexnow_url_submissions`,
- dodany model `IndexNowUrlSubmission`,
- dodany serwis `IndexNowQueueService`,
- dodany observer `QuestionPublicExplanationObserver`,
- dodane komendy `seo:indexnow-drain-queue` i `seo:indexnow-enqueue-public-explanations`,
- dodany scheduler drenowania kolejki co 10 minut, wlaczany przez `INDEXNOW_AUTOMATION_ENABLED`,
- dodane testy automatyzacji kolejki.

### Wykonane operacyjnie 2026-07-10

- zweryfikowano lokalny plik klucza `d0eff...0cd4.txt`: nazwa zgadza sie z trescia, format jest poprawny,
- wygenerowano lokalny publiczny plik `public/d0eff...0cd4.txt`,
- potwierdzono, ze plik publiczny jest ignorowany przez git,
- wgrano plik klucza na produkcje do `public/`,
- potwierdzono przez HTTPS `200` i zgodnosc tresci z lokalnym plikiem,
- wykonano pierwszy maly submit testowy 4 URL-i do `https://api.indexnow.org/indexnow`,
- endpoint zwrocil `202`, czyli zgloszenie zostalo przyjete do walidacji.
- wdrozono commit `33b6ff60` na produkcje,
- potwierdzono, ze produkcyjne komendy `seo:indexnow-key-file` i `seo:indexnow-submit` sa dostepne,
- znormalizowano produkcyjny publiczny plik klucza do formatu generowanego przez aplikacje,
- potwierdzono publiczny plik klucza przez HTTPS: `200`, rozmiar `33` bajty,
- wykonano produkcyjny dry-run z sitemap: zebrano `3589` URL-i, zaakceptowano probke `10`, odrzucono `0`,
- wykonano realny produkcyjny submit probki `10` URL-i: `sent=1`, `accepted=1`, status `200`,
- potwierdzono smoke check strony glownej i `sitemap.xml`: oba `200`.

Raporty lokalne:

- `storage/app/reports/indexnow-first-manual-dry-run.json`,
- `storage/app/reports/indexnow-first-manual-submit-powershell.json`.

Wynik testow z 2026-07-10:

```text
Tests: 26 passed (169 assertions)
```

Komenda testowa:

```powershell
.\.tools\php83\php.exe artisan test tests\Feature\Public\IndexNowKeyFileCommandTest.php tests\Feature\Public\IndexNowSubmitCommandTest.php tests\Feature\Public\SeoSitemapGenerationTest.php
```

Wynik:

```text
PASS
26 passed, 169 assertions
```

### Aktualnie w produkcji

- wdrozona produkcja dziala jeszcze w trybie recznym fazy 1,
- lokalny kod fazy 2 ma juz scheduler i observer, ale wymaga migracji oraz wlaczenia env na produkcji,
- produkcyjne submitowanie wykonujemy recznie komenda `seo:indexnow-submit`,
- do czasu ustawienia `INDEXNOW_KEY` w produkcyjnym `.env` submit produkcyjny powinien uzywac `--key-source` i aktualnego pliku klucza z `public/`.

## 4. Istniejace miejsca w kodzie, ktore wykorzystamy

### Sitemap i publiczne URL-e

- `app/Support/SeoSitemapBuilder.php`
- `app/Support/SeoSitemapGenerator.php`
- `app/Support/SeoSitemapAuditor.php`
- `app/Support/PublicQuestionCatalogService.php`
- `app/Support/LegalContentCatalogService.php`
- `app/Http/Controllers/SitemapController.php`
- `app/Http/Controllers/SitemapQuestionsController.php`
- `app/Console/Commands/RefreshSeoSitemapsCommand.php`
- `routes/web.php`
- `routes/console.php`

Wazna decyzja: nie skladamy URL-i pytan recznie. `PublicQuestionCatalogService` zawiera logike kanonicznych URL-i, `external_id`, slugow, kategorii i reprezentantow pytan. IndexNow musi korzystac z tej samej logiki co sitemap.

### Publiczne typy stron do objecia

Faza 1 powinna zbierac URL-e stron HTML, a nie surowe media:

- statyczne strony SEO z sitemap,
- hub bazy pytan,
- kategorie pytan,
- pojedyncze publiczne pytania,
- strony znakow drogowych,
- supporting pages znakow,
- kategorie znakow,
- strony autorow,
- strony tresci prawnych.

Na start nie wysylamy do IndexNow raw URL-i obrazow, posterow ani filmow z `storage-bulk`.

## 5. Zasady bezpieczenstwa

### 5.1 Tylko kanoniczna domena

Do IndexNow moga trafic tylko URL-e zaczynajace sie od:

```text
https://prawkonaraz.pl/
```

Odrzucamy:

- `http://...`,
- `www.prawkonaraz.pl`,
- stare domeny,
- `localhost`,
- URL-e z innego hosta,
- URL-e wzgledne po nieudanym znormalizowaniu.

### 5.2 Tylko publiczne strony

Nie wysylamy:

- `/admin`,
- `/api`,
- `/profile`,
- `/nauka`,
- endpointow auth,
- endpointow technicznych,
- plikow builda,
- surowych URL-i mediow,
- URL-i z query paramami, chyba ze zostana jawnie dopuszczone w przyszlosci.

### 5.3 Brak HTTP z panelu admina w fazie 1

Produkcja ma `QUEUE_CONNECTION=sync`. Dlatego w fazie 1 nie podpinamy automatycznego wysylania IndexNow bezposrednio po zapisach w panelu admina.

Najpierw wdrazamy:

- komendy Artisan,
- dry-run,
- raport,
- reczne uruchomienie po zmianach SEO/importach.

Automatyzacja po zapisach lub importach moze wejsc pozniej, gdy bedziemy mieli deduplikacje, throttling i jasna polityke retry.

### 5.4 Klucz nie trafia do repo

Prawdziwy klucz IndexNow jest sekretem operacyjnym. Nie commitujemy:

- `public/{key}.txt`,
- wartosci `INDEXNOW_KEY`,
- zadnych logow zawierajacych pelny klucz.

Klucz z prefiksem `indexnow-` jest wygodny, ale nie jest wymagany. Bing moze wygenerowac klucz bez prefiksu i taki klucz tez jest poprawny, jesli ma 8-128 znakow z dozwolonego zestawu. Repo ignoruje publiczne pliki `.txt` z wyjatkami dla jawnie utrzymywanych plikow, takich jak `robots.txt` i `llms.txt`.

## 6. Proponowana implementacja fazy 1

### 6.1 Konfiguracja

Dodac `config/indexnow.php`:

```php
return [
    'enabled' => env('INDEXNOW_ENABLED', false),
    'endpoint' => env('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow'),
    'host' => env('INDEXNOW_HOST', 'prawkonaraz.pl'),
    'key' => env('INDEXNOW_KEY'),
    'key_location' => env('INDEXNOW_KEY_LOCATION'),
    'timeout' => env('INDEXNOW_TIMEOUT', 10),
    'max_urls_per_request' => env('INDEXNOW_MAX_URLS_PER_REQUEST', 10000),
];
```

Dodac przyklady do env:

- `.env.example`
- `.env.mikrus.example`
- opcjonalnie pozostale env example, jesli sa utrzymywane jako rownorzedne wzorce.

Przykladowe wartosci:

```dotenv
INDEXNOW_ENABLED=false
INDEXNOW_ENDPOINT=https://api.indexnow.org/indexnow
INDEXNOW_HOST=prawkonaraz.pl
INDEXNOW_KEY=
INDEXNOW_KEY_LOCATION=
INDEXNOW_TIMEOUT=10
INDEXNOW_MAX_URLS_PER_REQUEST=10000
```

### 6.2 Serwis submitujacy

Dodac `app/Support/IndexNowSubmissionService.php`.

Odpowiedzialnosci:

- sprawdzic, czy IndexNow jest wlaczony,
- zwalidowac format klucza,
- znormalizowac URL-e,
- odrzucic URL-e spoza `https://prawkonaraz.pl`,
- odrzucic sciezki techniczne i prywatne,
- usunac duplikaty,
- podzielic payload na paczki do 10 000 URL-i,
- wyslac `POST` przez Laravel HTTP client,
- traktowac `200` i `202` jako sukces operacyjny,
- raportowac `400`, `403`, `422`, `429`,
- wspierac `dry-run`,
- nie rzucac wyjatkow dla spodziewanych odpowiedzi API, tylko zwracac raport.

### 6.3 Kolektor URL-i

Dodac `app/Support/IndexNowUrlCollector.php` albo podobny maly serwis.

Odpowiedzialnosci:

- pobierac URL-e z istniejacych builderow sitemap,
- nie duplikowac logiki kanonicznych URL-i,
- zbierac tylko strony HTML,
- pozwolic ograniczyc liczbe URL-i przez `--limit`.

Kolektor powinien wykorzystywac:

- `SeoSitemapBuilder`,
- `PublicQuestionCatalogService`,
- `LegalContentCatalogService`.

### 6.4 Komenda wysylki

Dodac komenda Artisan:

```bash
php artisan seo:indexnow-submit
```

Proponowana sygnatura:

```text
seo:indexnow-submit
    {url* : Absolute URLs or paths to submit}
    {--from-sitemap : Collect current public page URLs from sitemap sources}
    {--key-source= : Read the IndexNow key from a local text file instead of INDEXNOW_KEY}
    {--dry-run : Build and validate the payload without sending HTTP requests}
    {--limit= : Limit the number of URLs}
    {--report= : Write JSON report to a file}
```

Zasady:

- bez `url*` i bez `--from-sitemap` komenda konczy sie bledem z instrukcja,
- `--dry-run` pokazuje liczbe, odrzucone URL-e i probke payloadu,
- `--limit` pomaga przy pierwszych testach produkcyjnych,
- `--report` zapisuje JSON do `storage/app/reports/...`.

### 6.5 Komenda pliku klucza

Dodac komenda Artisan:

```bash
php artisan seo:indexnow-key-file
```

Proponowana sygnatura:

```text
seo:indexnow-key-file
    {--source= : Read the IndexNow key from a local text file instead of INDEXNOW_KEY}
    {--dry-run : Show the target path and URL without writing the file}
    {--force : Overwrite an existing key file}
```

Zasady:

- bierze klucz z `INDEXNOW_KEY`,
- albo z lokalnego pliku przekazanego przez `--source`,
- tworzy `public/{key}.txt`,
- zawartosc pliku to sam klucz plus newline,
- waliduje format klucza,
- pokazuje publiczny URL pliku,
- nie wyswietla pelnego klucza w logach, jesli da sie tego uniknac.

### 6.6 Git ignore

Ignorujemy publiczne pliki `.txt`, ale zostawiamy wyjatki dla znanych plikow repo:

```gitignore
/public/*.txt
!/public/robots.txt
!/public/llms.txt
!/public/llms-full.txt
!/public/ads.txt
```

To chroni zarowno klucze z prefiksem `indexnow-`, jak i klucze wygenerowane przez Bing bez tego prefiksu.

## 7. Testy do dodania

Nowy plik testow, np.:

```text
tests/Feature/Public/IndexNowSubmissionTest.php
```

Zakres testow:

- `--dry-run` nie wykonuje requestu HTTP,
- brak wlaczonego IndexNow nie wysyla requestu,
- niepoprawny klucz blokuje wysylke,
- URL spoza `https://prawkonaraz.pl` jest odrzucany,
- URL prywatny/techniczny jest odrzucany,
- payload zawiera `host`, `key`, `keyLocation`, `urlList`,
- request jest chunkowany do limitu,
- `200` i `202` sa raportowane jako przyjete,
- `403`, `422`, `429` sa raportowane jako blad bez crasha,
- komenda pliku klucza tworzy poprawny plik w `public/`,
- komenda pliku klucza nie nadpisuje istniejacego pliku bez `--force`.

Do requestow HTTP uzyc `Http::fake()`.

## 8. Rollout produkcyjny

### 8.1 Przed deployem

Lokalnie:

```powershell
.\.tools\php83\php.exe artisan test tests\Feature\Public\SeoSitemapGenerationTest.php
.\.tools\php83\php.exe artisan test tests\Feature\Public\IndexNowSubmissionTest.php
```

Po zmianach frontendu nie powinno byc potrzeby `npm run build`, bo zakres jest backendowy/CLI. Jesli implementacja dotknie assetow albo widokow, decyzje aktualizujemy.

### 8.2 Konfiguracja produkcyjna

W produkcyjnym `.env`:

```dotenv
INDEXNOW_ENABLED=true
INDEXNOW_ENDPOINT=https://api.indexnow.org/indexnow
INDEXNOW_HOST=prawkonaraz.pl
INDEXNOW_KEY=indexnow-...
INDEXNOW_KEY_LOCATION=https://prawkonaraz.pl/indexnow-....txt
```

Po zmianie env:

```bash
php artisan config:clear
php artisan config:cache
```

### 8.3 Plik klucza

Na produkcji:

```bash
php artisan seo:indexnow-key-file --dry-run
php artisan seo:indexnow-key-file
curl https://prawkonaraz.pl/indexnow-....txt
```

Oczekiwane:

- HTTP `200`,
- body zawiera tylko klucz,
- plik jest dostepny bez logowania,
- Cloudflare/Nginx nie blokuje `.txt`.

### 8.4 Pierwsza wysylka

Najpierw dry-run:

```bash
php artisan seo:indexnow-submit --from-sitemap --dry-run --limit=10
```

Potem mala probka:

```bash
php artisan seo:indexnow-submit --from-sitemap --limit=10 --report=storage/app/reports/indexnow-first-submit.json
```

Dopiero po poprawnym `200` albo `202` mozna zdecydowac, czy wysylamy wieksza paczke.

Uwaga: oficjalne zalecenie mowi, zeby wysylac URL-e zmienione od momentu startu uzywania IndexNow. Dlatego pelna wysylka calej sitemap jest decyzja operacyjna, nie domyslny krok techniczny.

### 8.5 Obecne uruchamianie po rollout

Na dzien 2026-07-10 faza 1 dziala na produkcji jako narzedzia CLI. Kod fazy 2
automatyzuje IndexNow bez obciazania serwera: zapis tresci tylko wrzuca
publiczny URL do lokalnej kolejki, a scheduler wysyla male paczki pozniej.

Do czasu produkcyjnego wlaczenia fazy 2 manualne uruchomienie po zmianach SEO,
imporcie albo publikacji tresci:

```bash
php artisan config:clear
INDEXNOW_ENABLED=true php artisan seo:indexnow-submit --from-sitemap --limit=10 --key-source=public/{key}.txt --report=storage/app/reports/indexnow-manual-submit.json
php artisan config:cache
```

Zasady operacyjne:

- najpierw uruchomic `--dry-run`, jezeli zmiana dotyczy duzej liczby URL-i,
- zaczynac od malego limitu, np. `--limit=10`,
- jezeli produkcyjna konfiguracja jest cache'owana, a `INDEXNOW_ENABLED` nie jest wlaczone w `.env`, przed jednorazowa komenda z inline env wykonac `php artisan config:clear` i po wszystkim przywrocic `php artisan config:cache`,
- jezeli ustawimy `INDEXNOW_ENABLED=true` w produkcyjnym `.env` i przebudujemy cache konfiguracji, nie trzeba uzywac inline env przy kazdej komendzie,
- nie zapisywac pelnego klucza w dokumentacji ani w publicznych logach,
- `200` i `202` oznaczaja przyjecie operacyjne,
- `429` oznacza, ze nalezy poczekac albo zmniejszyc liczbe URL-i,
- nie wysylac cyklicznie calej sitemap bez decyzji operacyjnej.

### 8.6 Weryfikacja w Bing Webmaster Tools

Po pierwszych submitach:

- sprawdzic w Bing Webmaster Tools, czy URL-e sa odbierane,
- zapisac date pierwszej poprawnej wysylki,
- zapisac uzyty endpoint,
- zapisac status odpowiedzi API,
- nie zapisywac pelnego klucza w dokumentacji.

## 9. Automatyzacja pozniejsza

Faza 2 automatyzuje IndexNow przez lokalna kolejke URL-i, a nie przez cykliczne
wysylanie calej sitemap.

### 9.1 Docelowy flow dla publicznych wyjasnien pytan

1. Edytujemy `question_public_explanations`, np. `body`, `dont_confuse_with`,
   `exam_trap`, `common_mistakes`, `related_questions`, `status` albo
   `published_at`.
2. Observer/serwis wykrywa zmiane po zapisie modelu.
3. System wylicza kanoniczny publiczny URL pytania przez
   `PublicQuestionCatalogService`, bez recznego skladania slugow.
4. URL trafia do tabeli kolejki jako `pending`.
5. Scheduler co kilka minut odpala drenowanie kolejki malym limitem, np. 50
   URL-i.
6. Jezeli kolejka jest pusta, komenda wykonuje tylko lekkie zapytanie po
   indeksie i konczy prace bez HTTP requestow.

Nie wykrywamy zmian przez parsowanie HTML ani przez odczytywanie tekstu
`Aktualizacja wyjasnienia: ...` ze strony. Zrodlem prawdy jest baza danych,
zwlaszcza `question_public_explanations.updated_at`, `last_reviewed_at` i pola
tresci.

### 9.2 Kolejka i harmonogram

Elementy implementacji:

- tabela `indexnow_url_submissions`,
- deduplikacja po `url_hash`,
- statusy `pending`, `processing`, `sent`, `failed`,
- `available_at` do debounce i retry,
- `attempts`, `last_http_status`, `last_error`, `last_submitted_at`,
- serwis `IndexNowQueueService`,
- komenda `seo:indexnow-drain-queue --limit=50`,
- komenda `seo:indexnow-enqueue-public-explanations --updated-since=YYYY-MM-DD`,
- observer `QuestionPublicExplanationObserver`,
- scheduler:

```php
Schedule::command('seo:indexnow-drain-queue --limit=50')
    ->everyTenMinutes()
    ->environments(['production'])
    ->when(fn (): bool => (bool) config('indexnow.automation_enabled', false))
    ->withoutOverlapping();
```

Brak zmian przez miesiac albo dwa jest poprawnym stanem. Scheduler nadal moze
sie uruchamiac, ale przy pustej kolejce wykona tylko szybki odczyt `pending`
URL-i i nie wysle nic do IndexNow.

### 9.3 Masowe aktualizacje

Dla jednorazowej aktualizacji okolo 2000 istniejacych stron pytan nie wysylamy
requestu po kazdym zapisie. Najbezpieczniejszy przebieg:

1. aktualizujemy publiczne wyjasnienia,
2. wrzucamy zmienione URL-e do kolejki automatycznie przez observer albo
   jednorazowo komenda po dacie,
3. scheduler wysyla paczki stopniowo.

Komenda backfill:

```bash
php artisan seo:indexnow-enqueue-public-explanations --updated-since=2026-07-09
```

Komenda ma znalezc opublikowane publiczne wyjasnienia zmienione od wskazanej
daty, wyliczyc ich kanoniczne URL-e i dodac je do kolejki bez wysylania HTTP.

### 9.4 Zasady bezpieczenstwa

- zapis w panelu/admin API nie wykonuje requestu HTTP do IndexNow,
- scheduler wysyla male paczki,
- ten sam URL jest deduplikowany,
- retry po bledach ma backoff,
- `429` nie powoduje natychmiastowego ponowienia calej paczki,
- automatyzacje mozna wylaczyc przez env,
- nie wysylamy cyklicznie calej sitemap bez realnych zmian,
- nie zapisujemy pelnego klucza w dokumentacji ani raportach publicznych.

### 9.5 Integracja Newsroomu po NEWSROOM-N5-005

Newsroom reuzywa dokladnie ten sam lokalny queue/submission pipeline. Nie powstal drugi klient HTTP, druga tabela ani drugi scheduler.

Potwierdzony flow:

1. use case publikacji/sluga zapisuje stan publiczny i audit w swojej transakcji,
2. `ContentArticleIndexNowRequested` implementuje `ShouldDispatchAfterCommit`, wiec rollback nie zostawia ghost submission row,
3. auto-discovered `QueueNewsroomArticleIndexNow` ponownie laduje article i stosuje `NEWSROOM_PUBLIC_ENABLED`, noindex/lifecycle eligibility oraz `PublicUrlResolver`,
4. listener przekazuje URL do istniejacego `IndexNowQueueService`; exception queue jest raportowany, ale nie cofa zakonczonej publikacji,
5. istniejacy drain/submission pipeline wysyla finalny HTTP request.

Lokalne lifecycle semantics:

- first publish -> `EVENT_CREATED`,
- republish / substantive public update -> `EVENT_UPDATED`,
- archive przy obecnym detail `200` i niezmienionym robots -> brak enqueue,
- withdraw po ustanowieniu `410` -> `EVENT_DELETED`,
- restore-to-review -> brak enqueue; pozniejszy publish po fresh review -> `EVENT_UPDATED`,
- published slug change -> old redirect URL i new canonical jako dwa `EVENT_UPDATED`,
- scheduled-before-time, noindex i gate=false -> brak enqueue.

`event_type` jest tylko lokalna metadana `indexnow_url_submissions`. `IndexNowSubmissionService` nadal serializuje do protokolu tylko `host`, `key`, opcjonalne `keyLocation` i `urlList`.

Evidence implementacyjne:

- PR #105, finalny implementation head `44c8099ac2ea020bf5d9e31cfe547af8cb2149f7`, merge `main@1cc9f4bec8c7d7fc105fd3495664434cf4323eea`,
- exact-head CI #392: 1116 passed / 20 095 assertions / 2 skipped, PostgreSQL 7/94, Pint/build PASS,
- post-merge CI #393: 1116 passed / 20 095 assertions / 2 skipped, PostgreSQL 7/94, Pint/build PASS.

To evidence dotyczy kodowej integracji Newsroomu. Nie potwierdza produkcyjnego wlaczenia fazy 2 ani statusu crawl/index w Bing.

## 10. Checklist

### Audyt i plan

- [x] Zbadac oficjalne wymagania IndexNow.
- [x] Zbadac obecna architekture sitemap.
- [x] Potwierdzic kanoniczna domene.
- [x] Potwierdzic ograniczenie `QUEUE_CONNECTION=sync`.
- [x] Uruchomic celowany test sitemap.
- [x] Spisac plan implementacji.

### Implementacja fazy 1

- [x] Dodac `config/indexnow.php`.
- [x] Dodac zmienne do env example.
- [x] Dodac ignore dla produkcyjnego pliku klucza.
- [x] Dodac `IndexNowSubmissionService`.
- [x] Dodac `IndexNowUrlCollector`.
- [x] Dodac `seo:indexnow-key-file`.
- [x] Dodac `seo:indexnow-submit`.
- [x] Dodac testy komendy pliku klucza.
- [x] Dodac testy submitowania z `Http::fake()`.
- [x] Uruchomic testy sitemap i IndexNow submit.
- [x] Zaktualizowac runbook deployowy o plik klucza.
- [x] Zaktualizowac runbook deployowy o submit IndexNow.

### Rollout

- [x] Wygenerowac/odebrac klucz IndexNow z Bing Webmaster Tools.
- [x] Wgrac publiczny plik klucza na produkcje.
- [x] Odswiezyc cache konfiguracji po wdrozeniu.
- [x] Wygenerowac albo znormalizowac publiczny plik klucza.
- [x] Sprawdzic plik klucza przez HTTPS/curl.
- [x] Uruchomic produkcyjny dry-run wysylki.
- [x] Wyslac mala probke produkcyjna.
- [ ] Sprawdzic status w Bing Webmaster Tools.
- [x] Zapisac date startu IndexNow: 2026-07-10.
- [ ] Opcjonalnie ustawic `INDEXNOW_KEY` i `INDEXNOW_ENABLED=true` w produkcyjnym `.env`, jezeli rezygnujemy z trybu `--key-source`.
- [x] Zdecydowac o automatyzacji fazy 2: kolejka URL-i + scheduler malych paczek.

### Implementacja fazy 2

- [x] Dodac konfiguracje `INDEXNOW_AUTOMATION_*`.
- [x] Dodac `INDEXNOW_KEY_SOURCE`.
- [x] Dodac migracje `indexnow_url_submissions`.
- [x] Dodac model `IndexNowUrlSubmission`.
- [x] Dodac `IndexNowQueueService`.
- [x] Dodac `QuestionPublicExplanationObserver`.
- [x] Podpiac observer w `AppServiceProvider`.
- [x] Dodac `seo:indexnow-drain-queue`.
- [x] Dodac `seo:indexnow-enqueue-public-explanations`.
- [x] Dodac scheduler co 10 minut z `withoutOverlapping`.
- [x] Dodac testy kolejki i automatyzacji.
- [x] Podlaczyc Newsroom do istniejacego queue/submission pipeline przez after-commit event/listener bez nowego klienta ani schema (NEWSROOM-N5-005).
- [x] Pokryc Newsroom lifecycle regression: publish/republish/update/withdraw/restore/slug, rollback, gate/noindex/scheduled suppression i failure isolation.
- [x] Potwierdzic regression testem, ze lokalny `event_type` nie jest polem HTTP payload IndexNow.
- [ ] Wdrozyc faze 2 na produkcje.
- [ ] Uruchomic migracje produkcyjna.
- [ ] Ustawic produkcyjne env `INDEXNOW_AUTOMATION_ENABLED=true`.
- [ ] Ustawic `INDEXNOW_KEY_SOURCE` albo `INDEXNOW_KEY`.
- [ ] Wykonac produkcyjny dry-run drenowania kolejki.
- [ ] Wykonac backfill dla zmienionych publicznych wyjasnien po dacie.

## 11. Otwarte decyzje

1. Czy generujemy wlasny klucz z prefiksem `indexnow-`, czy uzywamy klucza wygenerowanego w Bing Webmaster Tools?
2. Czy pierwsza realna wysylka ma obejmowac tylko mala probke, czy wybrane swiezo zmienione URL-e?
3. Czy produkcyjne submitowanie ma pozostac w trybie `INDEXNOW_KEY_SOURCE`, czy ustawiamy `INDEXNOW_KEY` w `.env`?
4. Od jakiej daty wykonujemy pierwszy backfill `question_public_explanations` po wdrozeniu fazy 2?

## 12. Decyzja rekomendowana

Rekomendowany najblizszy krok:

1. wdrozyc faze 2 na produkcje,
2. uruchomic migracje,
3. ustawic `INDEXNOW_AUTOMATION_ENABLED=true`,
4. ustawic `INDEXNOW_KEY_SOURCE=public/{key}.txt` albo `INDEXNOW_KEY`,
5. wykonac `seo:indexnow-drain-queue --dry-run --limit=10`,
6. wykonac backfill po dacie aktualizacji publicznych wyjasnien,
7. obserwowac raporty i Bing Webmaster Tools.
