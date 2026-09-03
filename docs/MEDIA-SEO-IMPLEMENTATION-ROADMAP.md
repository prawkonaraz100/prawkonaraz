# Media SEO Implementation Roadmap

Status: Fazy 1-4 wdrozone na produkcji, trwa monitoring GSC i porzadkowanie assetow
Data utworzenia: 2026-05-28  
Ostatnia aktualizacja: 2026-05-29
Domena: `https://prawkonaraz.pl`  
Zakres: obrazy pytan, postery wideo, filmy, sitemap, structured data, GSC

## 1. Cel

Celem nie jest samo dodanie kolejnych plikow sitemap. Celem jest zbudowanie spojnego kontraktu SEO:

- publiczna strona pytania pokazuje realna tresc,
- media sa dostepne publicznie i stabilnie,
- HTML ma dobre `alt`, wymiary i kontekst,
- schema JSON-LD opisuje to samo, co widzi uzytkownik,
- sitemap tylko potwierdza poprawne, publiczne zasoby,
- Google Search Console pokazuje czysta diagnostyke.

Najwazniejsza zasada:

> Sitemap nie moze obiecywac Google treści, ktorej crawler nie widzi na publicznej stronie.

## 2. Aktualny status

### Zrobione

- [x] `sitemap.xml` dziala jako sitemap index.
- [x] Pytania sa rozbite na sitemap per kategoria, np. `questions-b.xml`, `questions-a.xml`.
- [x] Image extension w sitemapach pytan juz dziala.
- [x] Zabezpieczono image sitemap przed dodawaniem raw `.mp4` do `image:loc`.
- [x] Dodano komende audytowa `seo:audit-question-media-readiness`.
- [x] Audyt produkcyjny mediow przeszedl bez blokerow.
- [x] Produkcyjne sitemapy zostaly wygenerowane po deployu.
- [x] `seo:audit-sitemaps` przeszedl na produkcji.
- [x] `ops:smoke-test` i `ops:smoke-test --require-media` przeszly na produkcji.
- [x] Potwierdzono, ze w `public/sitemaps` nie ma wzorca `<image:loc>...mp4`.
- [x] Faza 1 zostala wdrozona na produkcji: publiczne strony pytan maja opisowe `alt`, `aria-label`, `og:image:alt` i `twitter:image:alt`.
- [x] Faza 2: komenda enrichmentu metadanych wideo zostala wdrozona na produkcji.
- [x] Faza 2: pelny produkcyjny preview przeszedl dla 8195 filmow bez `probe_failed` i bez bledow.
- [x] Faza 2: przed masowym zapisem wykonano backup bazy produkcyjnej.
- [x] Faza 2: wykonano audyt wplywu `duration_seconds`, `width`, `height`, `bytes` i `mime_type` na modul nauki, egzaminu, odtwarzacz oraz adnotacje.
- [x] Faza 2: produkcyjny zapis metadanych wideo zostal wykonany dla 8195 filmow.
- [x] Faza 2: audyt po zapisie przeszedl bez blockerow.
- [x] Faza 2: `ops:smoke-test --require-media` przeszedl po zapisie.
- [x] Faza 2: pelny audyt publicznych URL-i filmow i posterow przeszedl dla 8223 filmow oraz 8223 posterow.
- [x] Faza 3: publiczne strony pytan z wideo dostaja JSON-LD `VideoObject`.
- [x] Faza 4: dodano osobna video sitemap `/sitemaps/videos.xml`.
- [x] Produkcyjny `MEDIA_PUBLIC_BASE_URL` zostal przestawiony z tymczasowego `wild-bison5536.byst.re` na `https://prawkonaraz.pl/storage-bulk`.
- [x] Sitemapy pytan i wideo nie zawieraja juz domeny `wild-bison5536.byst.re`.
- [x] Publiczne SEO znakow drogowych pomija obrazki znakow, ktore nie istnieja jako pliki w `public/`, zeby nie publikowac w sitemap/OG/JSON-LD martwych URL-i `404`.

### Wynik ostatniego audytu produkcyjnego

Raport:

```txt
/var/www/prawkobit/current/storage/app/reports/question-media-sitemap-readiness-20260528154821.json
```

Podsumowanie:

```txt
Publiczne pytania: rows=17044 canonical=3576 with_media=15798 requires_media_without_media=0
Question media: total=23345 images=15122 videos=8223 image_candidates=23317 video_candidates=8195
PJM: total=1976 | explanation_assets=17 | http_checked=250
Readiness: image_sitemap=ready video_sitemap=ready_for_implementation video_blockers=0
Warnings: 16390 total
```

Ostrzezenia:

- `video_missing_dimensions=8195`
- `video_missing_duration=8195`

Interpretacja:

- obrazy i postery sa bezpieczne do obecnej image sitemap,
- storage i publiczne URL-e nie maja blokerow,
- video sitemap jest technicznie mozliwa, ale przed wdrozeniem warto uzupelnic metadane filmow.

## 3. Czego nie robimy

Na tym etapie nie robimy:

- jednej wielkiej sitemap pytań,
- `image:title`, bo Google usunal ten tag z aktualnej dokumentacji image sitemap,
- osobnej video sitemap bez enrichmentu filmow,
- indeksowania PJM jako osobnego video SEO,
- wrzucania do sitemap mediow, ktore sa tylko czescia zamknietego flow nauki.

## 4. Faza 1: SEO obrazow na publicznej stronie pytania

Status: `done - deployed`

### Cel

Wzmocnic SEO obrazow bez zmiany architektury sitemap.

### Do zrobienia

 - [x] Zbadac HTML publicznej strony pytania z obrazem.
 - [x] Sprawdzic, czy obraz jest widoczny dla crawlera bez logowania.
 - [x] Sprawdzic, czy obraz ma sensowny `alt`.
 - [x] Sprawdzic, czy obraz ma `width` i `height`.
 - [x] Sprawdzic, czy lazy loading nie ukrywa kluczowego obrazu przed crawlerem.
 - [x] Ustalic generator `alt` dla roznych typow mediow.
 - [x] Dodac testy HTML dla pytania z obrazem.
 - [x] Dodac testy HTML dla pytania z wideo/posterem.

### Reguly `alt`

- Pytanie sytuacyjne: skrocony opis pytania.
- Znak drogowy: kod + nazwa znaku.
- Poster wideo: opis sytuacji pokazanej w filmie.
- Bez keyword stuffing.
- Bez nazw plikow typu `b-42.jpg`.

### Kryteria akceptacji

- [x] Publiczne pytanie z obrazem ma poprawny `alt`.
- [x] Obraz ma stabilny `https://` URL.
- [x] Obraz nie zwraca `404`.
- [x] Image sitemap nadal przechodzi audyt.
- [x] Brak `.mp4` w `image:loc`.

### Development 2026-05-28

Branch:

```txt
codex/public-question-media-seo
```

Zakres zmian:

- `PublicQuestionCatalogService::primaryMediaPayload()` doklada teraz `alt_text` dla publicznych mediow pytania.
- Publiczny widok pytania uzywa opisowego `alt` dla obrazu.
- Publiczny widok pytania uzywa opisowego `aria-label` dla wideo.
- Publiczny widok pytania doklada `width` i `height` do tagu `video`, jesli metadane sa dostepne.
- `PublicQuestionSeoService` uzywa tego samego `alt_text` dla `og:image:alt` i `twitter:image:alt`.
- Dodano testy dla publicznej strony pytania z obrazem i pytania z wideo.

Kontrola lokalna:

```txt
12 tests, 104 assertions - passed
```

Commit:

```txt
b813d14 Improve public question media SEO markup
64971f1 Merge public question media SEO markup
```

Deploy produkcyjny:

```txt
DEPLOY_PUBLIC_QUESTION_MEDIA_SEO_OK
```

Kontrola produkcyjna:

```txt
ops:smoke-test - OK
ops:smoke-test --require-media - OK
seo:audit-sitemaps - OK
seo:audit-question-media-readiness --http --http-limit=100 - OK, brak blokerow
```

Sprawdzona strona produkcyjna:

```txt
https://prawkonaraz.pl/pytanie/99/czy-w-tej-sytuacji-masz-obowiazek-zatrzymac-pojazd
```

Potwierdzone w HTML:

- `og:image:alt`
- `twitter:image:alt`
- `video[aria-label]`

Do obserwacji po wdrozeniu:

- GSC po ponownym przetworzeniu sitemap,
- ewentualne ostrzezenia Google dotyczace miniaturek lub wideo,
- metadane filmow, ktore sa juz zadaniem Fazy 2.

## 5. Faza 2: Enrichment metadanych wideo

Status: `done - production metadata and public URL verification complete`

### Cel

Przygotowac filmy do przyszlej video sitemap i VideoObject.

### Do zrobienia

- [x] Przygotowac komende enrichmentu filmow.
- [x] Uzyc `ffprobe` do odczytu:
  - `duration_seconds`,
  - `width`,
  - `height`.
- [x] Uzupelnic `bytes`, jesli brakuje.
- [x] Zweryfikowac i uzupelnic puste `mime_type = video/mp4` dla plikow `.mp4`.
- [x] Wdrozyc komende na produkcje bez masowego zapisu.
- [x] Uruchomic produkcyjny preview z limitem 25.
- [x] Uruchomic pelny produkcyjny preview bez limitu.
- [x] Wykonac backup bazy przed potencjalnym `--write`.
- [x] Przeprowadzic audyt skutkow ubocznych dla nauki, egzaminu, odtwarzacza i adnotacji.
- [x] Wykonac produkcyjny `--write`.
- [x] Wykonac audyt mediow po zapisie.
- [x] Wykonac produkcyjny smoke test po zapisie.
- [x] Sprawdzic, czy kazdy film ma `poster_path`.
- [x] Sprawdzic, czy poster istnieje na storage.
- [x] Sprawdzic, czy poster zwraca publiczne `200`.
- [x] Sprawdzic, czy `.mp4` zwraca publiczne `200` albo `206`.
- [x] Dodac testy dla enrichmentu.

### Development 2026-05-28

Branch:

```txt
codex/video-media-metadata-enrichment
```

Dodane elementy:

- `VideoMetadataProbe` - izoluje wywolanie `ffprobe`.
- `QuestionVideoMetadataEnricher` - wybiera publiczne rekordy `question_media.kind = video` i wylicza bezpieczne aktualizacje.
- `seo:enrich-question-video-metadata` - komenda operacyjna dla preview i zapisu.
- Testy feature dla preview, zapisu, braku nadpisywania bez `--force` i jawnego nadpisywania przez `--force`.

Zasady bezpieczenstwa:

- komenda domyslnie dziala w trybie `PREVIEW`,
- zapis do bazy wymaga jawnego `--write`,
- domyslny zakres to tylko publiczne pytania gotowe do SEO,
- komenda nie rusza plikow mediow, sciezek, posterow ani odtwarzacza,
- bez `--force` uzupelniane sa tylko brakujace pola,
- zdalne URL-e w `question_media.path` sa pomijane zamiast pobierane w ciemno,
- dla dyskow lokalnych uzywany jest plik z obecnego storage; dla nielokalnych dyskow komenda tworzy tymczasowa kopie tylko na czas `ffprobe`.

Komenda preview:

```bash
php artisan seo:enrich-question-video-metadata --limit=5 --report=storage/app/testing-media-readiness/video-metadata-preview.json
```

Wynik lokalnego preview:

```txt
Tryb: PREVIEW
Zakres: publiczne pytania | force=no | limit=5
Video metadata: candidates=5 probed=5 updated=0 would_update=5 no_changes=0
Skipped: external_url=0 missing_file=0 unconfigured_disk=0 probe_failed=0 errors=0
[field] duration_seconds=5
[field] height=5
[field] width=5
```

Kontrola lokalna:

```txt
10 tests, 77 assertions - passed
```

Plan produkcyjnego uruchomienia:

1. Wdrozyc kod komendy bez uruchamiania masowego zapisu. `DONE`
2. Uruchomic produkcyjny preview z malym limitem:

```bash
php artisan seo:enrich-question-video-metadata --limit=25 --report=storage/app/reports/video-metadata-preview-YYYYMMDDHHMMSS.json
```

   Wynik produkcyjny:

```txt
Video metadata: candidates=25 probed=25 updated=0 would_update=25 no_changes=0
Skipped: external_url=0 missing_file=0 unconfigured_disk=0 probe_failed=0 errors=0
[field] duration_seconds=25
[field] height=25
[field] width=25
```

3. Jesli preview nie pokaze `probe_failed` ani bledow, uruchomic pelny preview bez limitu. `DONE`

   Wynik produkcyjny:

```txt
Video metadata: candidates=8195 probed=8195 updated=0 would_update=8195 no_changes=0
Skipped: external_url=0 missing_file=0 unconfigured_disk=0 probe_failed=0 errors=0
[field] duration_seconds=8195
[field] height=8195
[field] width=8195
Report written: storage/app/reports/video-metadata-preview-full-20260528171000.json
```

4. Wykonac backup bazy przed zapisem. `DONE`

   Backup produkcyjny:

```txt
backups/database/2026/05/20260528-171543-prawkonarazpl-pgsql-pgsql.sql.gz
```

5. Przed `--write` sprawdzic skutki uboczne dla modulu nauki i egzaminu. `DONE`
6. Dopiero po potwierdzeniu raportu uruchomic: `DONE`

```bash
php artisan seo:enrich-question-video-metadata --write --report=storage/app/reports/video-metadata-write-YYYYMMDDHHMMSS.json
```

   Wynik produkcyjny:

```txt
Video metadata: candidates=8195 probed=8195 updated=8195 would_update=0 no_changes=0
Skipped: external_url=0 missing_file=0 unconfigured_disk=0 probe_failed=0 errors=0
[field] duration_seconds=8195
[field] height=8195
[field] width=8195
Report written: storage/app/reports/video-metadata-write-20260528180816.json
```

   Backup bezposrednio przed zapisem:

```txt
backups/database/2026/05/20260528-180807-prawkonarazpl-pgsql-pgsql.sql.gz
```

7. Po zapisie ponowic: `DONE`

```bash
php artisan seo:audit-question-media-readiness --http --http-limit=250
```

   Wynik produkcyjny:

```txt
Readiness: image_sitemap=ready video_sitemap=ready_for_implementation video_blockers=0
Media sitemap readiness audit has no blocking errors.
Report written: storage/app/reports/question-media-readiness-after-video-write-20260528181645.json
```

8. Po zapisie potwierdzic, ze komenda nie widzi juz kandydatow w domyslnym zakresie brakujacych metadanych. `DONE`

```txt
php artisan seo:enrich-question-video-metadata --limit=25
Video metadata: candidates=0 probed=0 updated=0 would_update=0 no_changes=0
Skipped: external_url=0 missing_file=0 unconfigured_disk=0 probe_failed=0 errors=0
```

9. Po zapisie wykonac smoke test produkcji z wymogiem mediow. `DONE`

```txt
php artisan ops:smoke-test --require-media
Smoke test status: OK
```

10. Wdrozyc dedykowany audyt publicznych URL-i filmow i posterow. `DONE`

Dodana komenda:

```bash
php artisan seo:audit-question-video-public-urls
```

Komenda sprawdza tylko publiczne `question_media.kind = video`:

- istnienie pliku w storage,
- istnienie postera w storage,
- publiczny status HTTP dla filmu,
- publiczny status HTTP dla postera,
- retry dla przejsciowych timeoutow, 5xx, 408 i 429.

Kontrola lokalna:

```txt
php artisan test tests/Feature/Public/QuestionVideoPublicUrlAuditTest.php tests/Feature/Public/QuestionMediaSitemapReadinessAuditTest.php tests/Feature/Console/QuestionVideoMetadataEnrichmentCommandTest.php
9 tests passed, 55 assertions
```

11. Wykonac pelny audyt produkcyjny URL-i filmow i posterow. `DONE`

```txt
php artisan seo:audit-question-video-public-urls --concurrency=25 --timeout=8 --retries=2 --retry-timeout=25 --report=storage/app/reports/question-video-url-audit-full-retry-20260528214217.json --fail-on-errors

Question videos: total=8223 checks=16446 ok=16446 retried=9 errors=0 warnings=0
URL roles: video=8223 poster=8223 | storage_errors=0 http_errors=0
[http-status:video] 200=8223
[http-status:poster] 200=8223
Question video public URL audit has no blocking errors.
```

### Audyt skutkow ubocznych przed `--write`

Wniosek:

- `duration_seconds` ma jeden realny efekt behawioralny: timer fazy `media` w egzaminie uzywa znanego czasu filmu zamiast fallbacku.
- Ten efekt jest zgodny z intencja systemu: egzamin ma przejsc z fazy wideo do odpowiedzi po realnej dlugosci klipu.
- Przy braku `duration_seconds` frontend traktuje wideo jako czas nieznany i nie zmniejsza lokalnego licznika fazy `media`.
- Po enrichmentu frontend dostanie znany czas filmu i licznik fazy `media` bedzie odliczal realny czas klipu.

Obszary bez zmiany zachowania:

- nauka klasyczna i zen: przyspieszenie/zwolnienie wideo uzywa `HTMLVideoElement.playbackRate`, nie pola z bazy;
- auto-przewijanie do koncowki filmu uzywa natywnego `video.duration`, nie `question_media.duration_seconds`;
- podglady wynikow uzywaja natywnego `video.duration` i `currentTime`;
- komponent stopklatki adnotacji uzywa natywnego `video.duration`, `currentTime`, `videoWidth` i `videoHeight`; pola z bazy sa tylko fallbackiem wymiarow;
- edytor adnotacji w panelu admina startuje z `duration_seconds`, ale po `loadedmetadata` nadpisuje go natywnym czasem filmu;
- PJM / filmy jezyka migowego sa osobnym modelem i nie sa ruszane przez komende enrichmentu.

Ryzyko operacyjne:

- Jesli `ffprobe` odczyta bledny czas pojedynczego pliku, timer egzaminu dla tego pytania bedzie zalezny od tej blednej wartosci.
- Ryzyko ogranicza pelny preview produkcyjny: 8195/8195 plikow zostalo odczytanych bez `probe_failed`.
- Komenda bez `--force` uzupelnia tylko puste pola, wiec nie nadpisuje istniejacych recznych metadanych.

Kontrola lokalna po audycie:

```txt
npm run test:unit -- --run
18 files passed, 106 tests passed

php artisan test tests/Unit/Support/QuestionMediaPayloadBuilderTest.php tests/Feature/AdminMediaApiTest.php
12 tests passed, 95 assertions

php artisan test tests/Feature/Admin/QuestionVisualExplanationEditPageTest.php
15 tests passed, 116 assertions

php artisan test tests/Feature/StudySessionFlowTest.php
32 tests passed, 706 assertions
```

### Kryteria akceptacji

- [ ] `video_missing_dimensions = 0`.
- [ ] `video_missing_duration = 0`.
- [ ] `video_missing_poster = 0`.
- [ ] Audyt mediow z `--http` przechodzi bez blokerow.
- [x] `video_missing_dimensions = 0`.
- [x] `video_missing_duration = 0`.
- [x] `video_missing_poster = 0`.
- [x] Dedykowany audyt publicznych URL-i filmow/posterow przechodzi bez blokerow.

## 6. Faza 3: VideoObject na stronie pytania

Status: `done - deployed`

### Cel

Dac Google spojny sygnal, ze film jest realna czescia publicznej strony pytania.

### Do zrobienia

- [x] Sprawdzic, czy publiczna strona pytania realnie renderuje wideo.
- [x] Dodac albo rozszerzyc JSON-LD `VideoObject`.
- [x] `VideoObject.name` oprzec o pytanie.
- [x] `VideoObject.description` oprzec o neutralny opis filmu SEO bez uzywania odpowiedzi ani wyjasnienia pytania.
- [x] `VideoObject.thumbnailUrl` ustawic na poster.
- [x] `VideoObject.contentUrl` ustawic na bezposredni `.mp4`.
- [x] `VideoObject.duration` generowac z `duration_seconds`.
- [x] `VideoObject.uploadDate` ustawic na realna date publikacji lub bezpieczny fallback.
- [x] Dodac test zgodnosci `VideoObject` z `QuestionMedia`.

### Development 2026-05-28

Zakres zmian:

- `PublicQuestionSchemaService::question()` przyjmuje payload medium i doklada osobny JSON-LD `VideoObject`, gdy glowne medium pytania jest filmem.
- `VideoObject` jest generowany tylko przy komplecie minimalnych danych:
  - publiczny URL filmu,
  - publiczny URL postera,
  - data publikacji / utworzenia / aktualizacji pytania.
- `VideoObject.name` bazuje na numerze pytania i tresci pytania.
- `VideoObject.description` bazuje na `QuestionVideoSeoDescriptionService`: opisuje material wideo, numer pytania, kategorie i temat sceny, ale nie uzywa `explanation`, `correct_answer` ani opcji odpowiedzi.
- Docelowa dlugosc opisu wideo SEO: neutralny opis ok. 180-320 znakow. Opis ma byc unikalny per pytanie, bez ujawniania odpowiedzi i bez startu od `Tak.` / `Nie.`.
- `duration` jest zapisywane jako ISO 8601, np. `PT22S`.
- Pytania z obrazem nie dostaja `VideoObject`.

Kontrola lokalna:

```txt
php artisan test tests/Feature/PublicQuestionDatabasePageTest.php tests/Feature/Public/SeoSitemapGenerationTest.php tests/Feature/Public/QuestionMediaSitemapReadinessAuditTest.php tests/Feature/Public/QuestionVideoPublicUrlAuditTest.php
15 tests passed, 126 assertions
```

Deploy produkcyjny:

```txt
DEPLOY_PUBLIC_QUESTION_VIDEOOBJECT_OK
ops:smoke-test --require-media - OK
```

Sprawdzona strona produkcyjna:

```txt
https://prawkonaraz.pl/pytanie/99/czy-w-tej-sytuacji-masz-obowiazek-zatrzymac-pojazd
```

Potwierdzone w HTML:

- `"@type":"VideoObject"`
- `thumbnailUrl` wskazuje publiczny poster,
- `contentUrl` wskazuje bezposredni plik `.mp4`,
- `duration` jest w formacie ISO 8601, np. `PT10S`.

### Kryteria akceptacji

- [x] Pytanie z wideo ma `VideoObject`.
- [x] `thumbnailUrl` i `contentUrl` sa publiczne.
- [x] `contentUrl` nie jest tym samym URL-em co strona pytania.
- [x] Dane schema sa zgodne z tym, co widac w HTML.

## 7. Faza 4: Osobna video sitemap

Status: `done - deployed`

### Cel

Dodac `/sitemaps/videos.xml` tylko dla filmow, ktore sa w pelni gotowe.

### Minimalny format

```xml
<url>
  <loc>https://prawkonaraz.pl/pytanie/99/slug</loc>
  <video:video>
    <video:thumbnail_loc>https://prawkonaraz.pl/storage/.../poster.webp</video:thumbnail_loc>
    <video:title>Tytul filmu</video:title>
    <video:description>Opis filmu</video:description>
    <video:content_loc>https://prawkonaraz.pl/storage/.../clip.mp4</video:content_loc>
    <video:duration>35</video:duration>
    <video:family_friendly>yes</video:family_friendly>
  </video:video>
</url>
```

### Do zrobienia

- [x] Dodac renderer video sitemap.
- [x] Dodac generator `/sitemaps/videos.xml`.
- [x] Dodac wpis do sitemap index.
- [x] Dodac audyt video sitemap:
  - wymagane tagi,
  - publiczne URL-e,
  - `content_loc != loc`,
  - brak URL-i za loginem,
  - brak uszkodzonych posterow.
- [x] Dodac testy XML.
- [x] Wygenerowac sitemap na produkcji.

### Development 2026-05-29

Zakres zmian:

- Dodano trase `/sitemaps/videos.xml`.
- `SeoSitemapGenerator` generuje statyczny plik `public/sitemaps/videos.xml`.
- `sitemap.xml` wskazuje video sitemap jako osobna mape.
- `SeoSitemapXmlRenderer::videoUrlset()` renderuje XML z namespace `video`.
- `PublicQuestionCatalogService::videoSitemapUrls()` wybiera tylko kanoniczne publiczne pytania, ktorych glowne medium jest wideo.
- `SeoSitemapAuditor` waliduje wymagane tagi video sitemap i dopuszcza celowe powtorzenie URL-a strony pytania miedzy zwykla sitemap pytań a video sitemap.

Zasady wpisu video sitemap:

- `<loc>` = publiczna strona pytania,
- `<video:thumbnail_loc>` = publiczny poster,
- `<video:title>` = numer pytania + tresc pytania,
- `<video:description>` = unikalny, neutralny opis materialu wideo z `QuestionVideoSeoDescriptionService`; nie wolno tu podawac odpowiedzi ani wyjasnienia pytania,
- `<video:content_loc>` = bezposredni publiczny `.mp4`,
- `<video:duration>` = liczba sekund,
- `<video:publication_date>` = data publikacji pytania,
- `<video:family_friendly>` = `yes`.

Kontrola lokalna:

```txt
php artisan test tests/Feature/Public/SeoSitemapGenerationTest.php tests/Feature/PublicQuestionDatabasePageTest.php tests/Feature/Public/QuestionMediaSitemapReadinessAuditTest.php tests/Feature/Public/QuestionVideoPublicUrlAuditTest.php tests/Feature/Public/TrafficSignSeoInfrastructureTest.php
24 tests passed, 244 assertions
```

Deploy produkcyjny:

```txt
DEPLOY_PUBLIC_QUESTION_VIDEO_SITEMAP_OK

seo:refresh-sitemaps:
sitemaps/videos.xml urls=1215 bytes=1538657
Sitemap files generated and audit passed.

seo:audit-sitemaps:
Sitemap audit passed.

ops:smoke-test --require-media:
Smoke test status: OK
```

Kontrola publiczna po domenie:

```txt
https://prawkonaraz.pl/sitemap.xml zawiera https://prawkonaraz.pl/sitemaps/videos.xml
https://prawkonaraz.pl/sitemaps/videos.xml zwraca XML z xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"
public/sitemaps/videos.xml: <url>=1215, <video:video>=1215
public/sitemaps/videos.xml: brak wzorca <loc>.*.mp4
```

### Kryteria akceptacji

- [x] XML jest poprawny lokalnie.
- [x] Główna sitemap wskazuje video sitemap lokalnie.
- [x] `seo:audit-sitemaps` przechodzi lokalnie.
- [x] `/sitemaps/videos.xml` zwraca `200` na produkcji.
- [x] Główna sitemap wskazuje video sitemap na produkcji.
- [x] Produkcyjny `seo:audit-sitemaps` przechodzi.

## 8. Faza 5: Google Search Console

### Cel

Zweryfikowac, jak Google realnie przetwarza media.

### Do zrobienia

- [ ] Po wdrozeniu video sitemap przeslac sitemap w GSC.
- [ ] Monitorowac status `Couldn't fetch`.
- [ ] Monitorowac bledy miniatur.
- [ ] Monitorowac brakujace wymagane pola video.
- [ ] Monitorowac `video not indexed`.
- [ ] Sprawdzac logi nginx dla Googlebota.
- [ ] Przez pierwsze 7 dni robic krotki daily check.

### Kryteria akceptacji

- [ ] GSC odczytuje video sitemap.
- [ ] Brak bledow parsowania.
- [ ] Brak masowych bledow miniaturek.
- [ ] Brak masowych bledow dostepu do `.mp4`.

## 8A. Korekta domeny mediow i brakujacych assetow

Status: `done - deployed/configured`

### Problem

Po wdrozeniu video sitemap crawler widzial czesc obrazow i miniaturek przez
tymczasowy host `wild-bison5536.byst.re`. Technicznie pliki odpowiadaly, ale
SEO powinno wskazywac kanoniczna domene serwisu. Dodatkowy pelny test URL-i z
sitemap pokazal osobny problem: czesc obrazkow znakow drogowych wskazywala na
`/storage/traffic-signs/...`, mimo ze odpowiadajace pliki nie byly opublikowane
na produkcji i zwracaly `404`.

### Decyzja

- Media pytan i wideo pozostaja na lokalnym `storage-bulk` jako etap pomostowy,
  ale publiczne URL-e sa generowane przez `https://prawkonaraz.pl/storage-bulk`.
- Docelowo po migracji do R2 `MEDIA_PUBLIC_BASE_URL` zmieniamy na
  `https://media.prawkonaraz.pl`.
- Assety znakow drogowych traktujemy jako checked-in public assets. Jezeli plik
  nie istnieje w `public/`, nie wolno dodawac go do `image:loc`, `og:image`,
  `twitter:image`, preload ani JSON-LD `ImageObject`.

### Wykonane kontrole

```txt
MEDIA_PUBLIC_BASE_URL=https://prawkonaraz.pl/storage-bulk
seo:refresh-sitemaps: OK
seo:audit-sitemaps: OK
public/sitemap.xml: brak wild-bison5536.byst.re
public/sitemaps/questions-*.xml: media przez https://prawkonaraz.pl
public/sitemaps/videos.xml: media przez https://prawkonaraz.pl
ops:smoke-test --require-media: OK
```

Testy lokalne:

```txt
tests/Feature/Public/TrafficSignSeoInfrastructureTest.php
tests/Feature/Public/SeoSitemapGenerationTest.php
tests/Feature/Public/QuestionMediaSitemapReadinessAuditTest.php
tests/Feature/Public/QuestionVideoPublicUrlAuditTest.php

19 passed, 180 assertions
```

### Otwarte po tej korekcie

- Uporzadkowac dane/asset pack znakow drogowych tak, zeby opublikowane znaki
  mialy komplet realnych plikow graficznych, a nie tylko puste referencje.
- Po migracji mediow do R2 ponownie wykonac pelny audyt sitemap media URL.

## 9. Kolejnosc prac

Rekomendowana kolejnosc:

1. Faza 1: HTML i `alt` obrazow.
2. Faza 2: enrichment filmow.
3. Faza 3: `VideoObject`.
4. Faza 4: `/sitemaps/videos.xml`.
5. Faza 5: GSC monitoring.

## 10. Najblizszy task developerski

Najblizszy task:

> Wdrozyc komendę Fazy 2 i uruchomic produkcyjny preview enrichmentu metadanych wideo.

Powod:

- kod komendy jest gotowy lokalnie i przetestowany,
- obecny audyt pokazuje `video_missing_dimensions=8195`,
- obecny audyt pokazuje `video_missing_duration=8195`,
- przed masowym `--write` potrzebujemy raportu produkcyjnego,
- bez tych danych nie warto jeszcze publikowac osobnej video sitemap.

## 11. Komendy kontrolne

Lokalnie:

```bash
php artisan test tests/Feature/Public
php artisan test tests/Feature/Console/QuestionVideoMetadataEnrichmentCommandTest.php tests/Feature/Public/QuestionMediaSitemapReadinessAuditTest.php tests/Feature/Public/SeoSitemapGenerationTest.php
php artisan seo:enrich-question-video-metadata --limit=5 --report=storage/app/testing-media-readiness/video-metadata-preview.json
php artisan seo:audit-question-media-readiness
php artisan seo:audit-sitemaps
```

Na produkcji po deployu:

```bash
cd /var/www/prawkobit/current
php artisan seo:refresh-sitemaps
php artisan seo:audit-sitemaps
php artisan seo:enrich-question-video-metadata --limit=25 --report=storage/app/reports/video-metadata-preview-YYYYMMDDHHMMSS.json
php artisan seo:audit-question-media-readiness --http --http-limit=250
php artisan ops:smoke-test --require-media
```

Kontrola raw video w image sitemap:

```bash
grep -R '<image:loc>.*\.mp4' public/sitemaps
```

Brak wyniku oznacza stan poprawny.
