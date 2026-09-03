# Gov.pl Integration Plan

## Cel

Zasilić lokalny projekt oficjalną bazą pytań i multimediami publikowanymi przez Ministerstwo Infrastruktury w sposób:

- zgodny z aktualną architekturą `manifest -> import`,
- oszczędny dla VPS i storage,
- gotowy do uruchomienia jako `B-first MVP`,
- odporny na braki i niespójności w oficjalnych paczkach.

## Oficjalne źródła

- `baza-pytan.xlsx`
- `multimedia_do_pytan.zip`
- `multimedia do pytań cz. 2`

Lokalny mirror roboczy:

- `D:\datasets\mi-prawo-jazdy-2026\raw\baza-pytan.xlsx`
- `D:\datasets\mi-prawo-jazdy-2026\raw\multimedia-cz1.zip`
- `D:\datasets\mi-prawo-jazdy-2026\raw\multimedia-cz2.zip`

## Co jest już gotowe

- importer stagingowy `catalog:prepare-gov-batch`,
- parser oficjalnego XLSX bez zależności od Excela,
- indeksowanie plików z ZIP i katalogów,
- mapowanie oficjalnych wierszy do naszego `manifest.json + questions.csv`,
- zapis metadanych rządowych do `questions.metadata`,
- audit głównych mediów i PJM,
- filtr kategorii `--categories=...`,
- chunkowanie stagingu przez `--source-offset`, `--source-limit` i `catalog:prepare-gov-batch-series`,
- wznawianie długiej serii chunków przez `catalog:prepare-gov-batch-series --skip-existing`,
- generowanie `thumb.webp` dla obrazów przy dostępności `ffmpeg`,
- transkodowanie `WMV -> MP4` oraz generowanie posterów dla wideo,
- automatyczne odchudzanie pojedynczych oversized obrazów podczas `catalog:import-manifest*`, gdy oficjalny JPG przekracza limit `MEDIA_MAX_IMAGE_BYTES`,
- rejestr runów importu i stagingu w `content_import_runs`,
- testy jednostkowe i integracyjne dla pipeline'u.

## Wynik audytu

### Pełna baza (`katalog` + `W trakcie weryfikacji`)

- wiersze źródłowe: `3716`
- pytania po rozbiciu na kategorie: `18373`
- referencje do głównych mediów: `3104`
- znalezione główne media: `2996`
- brakujące główne media: `108`
- unikalne brakujące główne media: `81`
- referencje PJM: `1959`
- znalezione PJM: `0`
- brakujące PJM: `1959`

### Kategoria `B` jako wariant MVP

- wiersze źródłowe `B` w arkuszu `katalog`: `2142`
- wiersze po doliczeniu `W trakcie weryfikacji`: `2306`
- pytania w batchu `B-only`: `2306`
- wiersze odfiltrowane poza `B`: `1410`
- główne media w `B-only`: `2050`
- znalezione główne media w `B-only`: `1953`
- brakujące główne media w `B-only`: `97`
- unikalne brakujące główne media w `B-only`: `75`
- referencje PJM w `B-only`: `1379`
- znalezione PJM w `B-only`: `0`

### Rozkład mediów dla kategorii `B` w arkuszu `katalog`

- `1032` pliki `WMV`
- `910` pliki `JPG`
- `3` pliki `JPEG`
- `197` pytań bez głównego medium

Wniosek:

- obrazy można stagingować od razu,
- wideo wymaga transkodowania `WMV -> MP4`,
- PJM nie jest obecnie dostarczone w oficjalnych ZIP-ach, więc nie może wejść do MVP jako działające medium.

## Rekomendowana strategia wdrożenia

### Etap 1. B-first staging

Używamy tylko kategorii `B` i budujemy staging batch poza repo:

```bash
php artisan catalog:prepare-gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --include-verification
```

To daje gotowy `questions.csv`, `manifest.json` i `report.json` bez dotykania produkcyjnego storage.

Jeśli na danej stacji nie ma jeszcze `ffmpeg`, można tymczasowo zbudować batch tylko z rekordów gotowych do natychmiastowego importu:

```bash
php artisan catalog:prepare-gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b-ready D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --include-verification --ready-only
```

Ten wariant zachowuje pytania tekstowe i obrazkowe gotowe przy aktualnej konfiguracji, a odcina rekordy, które bez transkodowania `WMV` nadal nie nadają się do importu.

Jeżeli staging ma objąć duży wycinek kategorii `B`, batch można budować porcjami:

```bash
php artisan catalog:prepare-gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b-chunk-01 D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --materialize-media --source-offset=0 --source-limit=50
php artisan catalog:prepare-gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b-chunk-02 D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --materialize-media --source-offset=50 --source-limit=50
```

Każdy chunk raportuje własne `source_offset`, `source_limit` i `source_rows_considered`, więc łatwo odtworzyć staging po przerwaniu albo rozłożyć transkodowanie wideo na kilka uruchomień.

Jeśli chcemy zautomatyzować całą serię chunków jednym przebiegiem, używamy:

```bash
php artisan catalog:prepare-gov-batch-series D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b-series D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --materialize-media --chunk-size=50
```

Komenda zapisuje `chunk-0001`, `chunk-0002`, ... oraz zbiorczy `series-report.json`, dzięki czemu można łatwo wznowić albo ocenić, na którym kawałku zatrzymał się staging.

Jeśli staging został przerwany po kilku poprawnych chunkach, można go wznowić bez ponownego budowania zakończonych części:

```bash
php artisan catalog:prepare-gov-batch-series D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b-series D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --materialize-media --chunk-size=50 --skip-existing
```

`--skip-existing` reuse'uje tylko te chunki, które mają poprawny `report.json`, zgodny `source_offset` i komplet plików `manifest.json + questions.csv`. Dzięki temu nie transkodujemy ponownie tych samych obrazów i wideo.

Jeżeli chcemy zrobić długi staging mimo braków w oficjalnych paczkach MI, możemy dodatkowo przełączyć serię w tryb audytu zamiast fail-fast:

```bash
php artisan catalog:prepare-gov-batch-series D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b-series D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --materialize-media --chunk-size=50 --skip-existing --allow-missing-media
```

`--allow-missing-media` zostawia brakujące główne media w raporcie i ostrzeżeniach, ale nie zatrzymuje całego batcha ani całej serii na pierwszej luce w paczce źródłowej.

### Etap 2. Instalacja ffmpeg i staging mediów

Przed realnym importem produkcyjnym instalujemy `ffmpeg`, a potem uruchamiamy staging z materializacją:

```bash
php artisan catalog:prepare-gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b-media D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --include-verification --materialize-media
```

Jeśli `ffmpeg` nie jest w `PATH`, wskazujemy go przez `MEDIA_FFMPEG_BINARY`, np. `C:/Users/<user>/scoop/apps/ffmpeg/current/bin/ffmpeg.exe`.

Aktualne zachowanie pipeline'u:

- obrazy trafiają do `media/shared/<question-id>/full.<ext>`,
- dla obrazów generowany jest też `media/shared/<question-id>/thumb.webp`,
- filmy są transkodowane do `media/shared/<question-id>/full.mp4`,
- postery są generowane do `media/shared/<question-id>/poster.jpg`.
- jeśli pojedynczy staged obraz nadal przekracza limit importu, importer przygotowuje lokalny wariant `.import-fit-<limit>.webp` i używa go zamiast failować cały chunk.

Zweryfikowany przebieg na realnym wycinku `B` (`source-limit=12`, `--materialize-media`, `--ready-only`):

- `10` przygotowanych pytań,
- `6` obrazów zmaterializowanych,
- `6` thumbnaili wygenerowanych,
- `4` wideo `WMV -> MP4`,
- `4` postery,
- `0` błędów na etapie stagingu,
- `0` błędów na etapie `catalog:import-manifest --dry-run`.

### Etap 3. Polityka braków

Nie blokujemy całego importu przez wszystkie braki naraz. Dzielimy je na trzy klasy:

- `main media missing`
  - pytania wymagają decyzji: wyciąć z MVP, zastąpić własnym medium albo wprowadzić jako tekst-only tylko do wewnętrznych testów
- `PJM missing`
  - zapisujemy referencje w `metadata`, ale nie publikujemy jako aktywnego feature'a MVP
- `verification sheet`
  - można go trzymać w stagingu, ale do pierwszego publicznego batcha rozważyć wyłączenie

### Etap 4. Import do aplikacji

Gdy batch `B-only` z mediami będzie gotowy:

```bash
php artisan catalog:import-manifest storage/app/import/gov-batch-b-media/manifest.json --dry-run
php artisan catalog:import-manifest storage/app/import/gov-batch-b-media/manifest.json
```

Najpierw zawsze `dry-run`, potem dopiero realny import.

Jeżeli staging został przygotowany w postaci serii chunków, import wykonujemy zbiorczo:

```bash
php artisan catalog:import-manifest-series storage/app/import/gov-batch-b-series --dry-run
php artisan catalog:import-manifest-series storage/app/import/gov-batch-b-series
```

Komenda potrafi też wznowić zakres chunków:

```bash
php artisan catalog:import-manifest-series storage/app/import/gov-batch-b-series --from-chunk=5 --to-chunk=8 --continue-on-error
```

Jeśli wcześniejszy `series import` zakończył się częściowym sukcesem, można pominąć chunki już oznaczone jako poprawne w poprzednim raporcie:

```bash
php artisan catalog:import-manifest-series storage/app/import/gov-batch-b-series --resume-from-report storage/app/import-reports/latest-series-import.json --report storage/app/import-reports/latest-series-import.json
```

W tym trybie import czyta poprzedni raport, skipuje chunki ze statusem `ok` i pracuje tylko na tych, które jeszcze nie zostały poprawnie zaimportowane.

W lokalnym środowisku developerskim, jeśli nie ma jeszcze adaptera S3/R2, import batcha obrazkowego wykonujemy na dysk `public`:

```powershell
$env:MEDIA_UPLOAD_DISK='public'
$env:MEDIA_DISK='public'
.tools\php83\php.exe artisan catalog:import-manifest storage\app\import\gov-batch-b-ready-v2\manifest.json
```

Jeśli staging i pełny import mają wyjść poza repo, można użyć lokalnego dysku bulk:

```powershell
$env:MEDIA_UPLOAD_DISK='media_local'
$env:MEDIA_DISK='media_local'
$env:MEDIA_PUBLIC_DISK='media_local'
$env:MEDIA_LOCAL_ROOT='D:/datasets/mi-prawo-jazdy-2026/app-media'
$env:MEDIA_LOCAL_URL='http://localhost:8000/storage-bulk'
.tools\php83\php.exe artisan storage:link
.tools\php83\php.exe artisan catalog:import-manifest-series D:\datasets\mi-prawo-jazdy-2026\staging\gov-full-series --dry-run
.tools\php83\php.exe artisan catalog:import-manifest-series D:\datasets\mi-prawo-jazdy-2026\staging\gov-full-series
```

Zweryfikowany pełny przebieg lokalny:

- `38/38` chunków,
- `18373` pytań,
- `23496` rekordów mediów,
- `31680` uploadów assetów,
- `0` błędów dry-run,
- `0` błędów realnego importu,
- `5` oversized JPG z oficjalnego wsadu zostało automatycznie przekształconych do `.webp`, zamiast blokować serię.

Po kazdym takim pelnym imporcie uruchamiany jest teraz dodatkowo audit integralnosci pytan. Jesli import serii:

- nie jest `dry-run`,
- nie ma bledow,
- nie jest tylko czesciowym zakresem chunkow,

to system automatycznie zapisuje snapshot aktualnego katalogu, diff wzgledem poprzedniego snapshotu i skrot wyniku w `content_import_runs.summary.integrity_audit`.

Pelny standard tego kroku jest opisany w [QUESTION-INTEGRITY-AUDIT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/QUESTION-INTEGRITY-AUDIT.md).

### Etap 4b. Audit gotowości dostarczenia przed pilotem

Po pełnym imporcie uruchamiamy audit gotowości pytań do realnego delivery:

```bash
php artisan content:audit-delivery-readiness
php artisan content:audit-delivery-readiness --category=B
php artisan content:audit-delivery-readiness --deactivate-missing-primary-media
```

Komenda:

- przelicza `requires_primary_media` na podstawie metadanych oficjalnego wsadu,
- oznacza pytania z brakującym głównym medium jako `delivery_issue=missing_primary_media`,
- potrafi od razu ustawić `is_active=false` dla rekordów, które nie powinny wejść do pilota,
- zostawia pytania tekstowe i pytania z poprawnie zaimportowanym medium jako `ready for delivery`.

Zweryfikowany wynik na lokalnym pełnym wsadzie:

- `18375` pytań łącznie,
- `788` pytań z brakującym głównym medium,
- `788` takich rekordów zdezaktywowanych przed pilotem,
- `17587` aktywnych pytań gotowych do delivery po audycie.

Największe ubytki po audycie mają obecnie:

- `B`: `97`,
- `B1`: `77`,
- `D1`: `75`,
- `D`: `72`,
- `A`, `A1`, `A2`: po `71`.

### Etap 5. QA po imporcie

Po imporcie sprawdzamy:

- katalog pytań,
- sesję `learn`,
- sesję `exam`,
- ładowanie obrazów,
- start i poster dla wideo,
- dashboard i analytics,
- smoke test aplikacji.

## Decyzje architektoniczne

### 1. Oficjalne metadane zostają w `questions.metadata`

Przechowujemy tam:

- `government_question_id`,
- numer wiersza,
- arkusz źródłowy,
- zakres struktury,
- oryginalne kategorie,
- tłumaczenia,
- referencje PJM,
- oryginalną nazwę głównego medium.

To pozwala:

- zachować pełny ślad pochodzenia,
- łatwo robić reimport,
- w przyszłości dobudować narzędzia QA i diffy między wersjami bazy.

### 2. Nie importujemy `WMV` bezpośrednio do aplikacji

Projekt frontendowy i storage są ustawione pod lekkie, przeglądarkowe assety. Dlatego:

- `WMV` jest tylko formatem wejściowym,
- `MP4` jest formatem stagingowym i docelowym,
- storage produkcyjny dostaje wyłącznie assety po preprocessing.

### 3. Repo nie przechowuje raw datasetów

Surowe ZIP-y i XLSX zostają poza repo na osobnym dysku. Do repo trafia tylko kod, raporty i ewentualnie małe, testowe sample.

## Ryzyka

### 1. Braki w oficjalnych paczkach

Oficjalne ZIP-y nie pokrywają 100% referencji do głównych mediów i nie zawierają PJM.

### 2. Czas transkodowania

Kategoria `B` zawiera ponad `1000` plików `WMV`, więc staging wideo trzeba traktować jako zadanie offline, a nie operację wykonywaną przy deployu.

### 3. Prawa do treści

Techniczny pipeline jest gotowy, ale publiczny launch z tym contentem nadal zależy od odpowiedzi Ministerstwa i oceny prawnej.

## Następne kroki

Etapy techniczne z tego dokumentu zostały już wykonane lokalnie.

Aktualne następne kroki nie są już stricte importowe, tylko wdrożeniowe i produktowe:

1. przejść ręczny QA na localhost na realnej zawartości `gov.pl`,
2. przygotować testowy deploy na VPS lub innym zamkniętym środowisku,
3. zdecydować, czy pilot ma korzystać wyłącznie z wariantu `B-first`, mimo że lokalnie załadowano pełny wsad,
4. utrzymywać `content:audit-delivery-readiness --deactivate-missing-primary-media` jako bramkę przed każdym kolejnym reimportem i przed pilotem,
5. poczekać na odpowiedź Ministerstwa w sprawie statusu prawnego treści,
6. dopiero po tym rozważyć publiczny launch.

Kanoniczny status projektu po wykonaniu tych etapów jest zapisany w [STATUS-MVP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/STATUS-MVP.md).
