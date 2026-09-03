# Darmowy Modul Nauki PJM - Dokumentacja I Plan Sprintow

Status dokumentu: `draft strategiczny`
Data utworzenia: `2026-05-05`
Zakres: rejestracja, dostep, import PJM, obrobka wideo, modul nauki PJM, wspolny progres i przejscie do pelnej nauki.

## Cel

Celem jest zbudowanie osobnego, darmowego modulu nauki z tlumaczeniami PJM dla pytan, dla ktorych realnie posiadamy material migowy.

Nie sprzedajemy tego jako pelnej bazy PJM, dopoki nie mamy pokrycia calej kategorii. To jest wazna decyzja etyczna i produktowa:

- uzytkownik moze korzystac bezplatnie z pytan, ktore maja oficjalne tlumaczenie PJM,
- pelna baza pytan, tryby klasyczne, Zen, egzaminy, statystyki i ranking pozostaja czescia platnego dostepu,
- komunikujemy jasno, ze PJM jest dostepne tylko dla czesci pytan,
- nie udajemy pelnego pokrycia kategorii `B`, skoro obecnie go nie mamy.

## Decyzje Produktowe

### PJM Nie Jest Platnym Produktem Na Starcie

Dopoki pokrycie nie obejmuje calej bazy pytan dla danej kategorii, modul PJM ma byc dostepny bezplatnie.

Uczciwy komunikat:

`Tlumaczenia PJM udostepniamy bezplatnie tam, gdzie sa dostepne. Pelna baza pytan i wszystkie tryby nauki sa czescia platnego dostepu.`

Tego unikamy:

- `Pelna baza pytan w PJM`,
- `Nauka kategorii B w PJM`,
- `Kompletny kurs PJM`,
- kazdej komunikacji sugerujacej 100% pokrycia.

### PJM To Osobny Modul, Nie Przycisk W Klasycznej Nauce

Nie doklejamy kolejnego przycisku do klasycznej nauki. Tworzymy osobny tor:

- `Nauka PJM`,
- wlasny kafel na `/nauka`,
- wlasny filtr pytan,
- wlasny UI sesji,
- wspolny progres z reszta produktu.

Dzieki temu uzytkownik wie, ze wchodzi w tryb zaprojektowany wokol filmu migowego, a nie w klasyczna nauke z przypadkowym dodatkiem.

### Wspolny Progres

Pytanie przerobione w PJM jest przerobione w calej kategorii uzytkownika.

Nie robimy osobnych stanow:

- `przerobione w PJM`,
- `nieprzerobione w klasycznej`,
- `osobno przerobione w Zen`.

Tryb nauki jest sposobem prezentacji pytania, a nie osobna historia pytania.

W praktyce:

- uzytkownik odpowiada na pytanie w module PJM,
- zapis trafia do obecnego `user_question_progress`,
- to samo pytanie nie jest juz `nieprzerobione` w klasycznej nauce,
- jesli uzytkownik odpowie zle, pytanie trafia do bledow/powtorek,
- jesli pytanie ma PJM, powtorka moze pojawic sie w module PJM,
- jesli uzytkownik przejdzie do klasycznej nauki z filtrem `Jeszcze nieprzerobione`, nie powinien widziec pytan PJM, ktore juz zrobil.

## Aktualny Stan Danych PJM

Zbadany folder:

`D:/pytania_egzaminacyjne_na_prawo_jazdy_tlumaczenia_migowe_12_2025 (1)/Pytania egzaminacyjne na prawo jazdy - tlumaczenia migowe 2025`

Stan paczki:

- pliki `.wmv`: `1976`,
- unikalne numery pytan w paczce: `1139`,
- pytania tylko z filmem pytania: `860`,
- pytania z filmem pytania oraz odpowiedziami `A/B/C`: `279`,
- rozmiar paczki: okolo `11.46 GB`.

Stan aktywnej bazy lokalnej:

- aktywne unikalne `external_id`: `3576`,
- unikalne pytania z PJM w aktywnej bazie: `1120`,
- pytania PJM z paczki, ktore nie sa w aktywnej bazie: `19`,
- pytania oznaczone w `questions.metadata.pjm`, dla ktorych brakuje pliku w folderze: `0`.

Pokrycie kategorii:

| Kategoria | Wszystkie | Z PJM | Brak PJM | Pokrycie |
|---|---:|---:|---:|---:|
| AM | 1513 | 745 | 768 | 49.24% |
| A1 | 1414 | 773 | 641 | 54.67% |
| A2 | 1412 | 772 | 640 | 54.67% |
| A | 1428 | 778 | 650 | 54.48% |
| B1 | 1424 | 842 | 582 | 59.13% |
| B | 2194 | 969 | 1225 | 44.17% |
| C1 | 1445 | 924 | 521 | 63.94% |
| C | 1498 | 954 | 544 | 63.68% |
| D1 | 1479 | 797 | 682 | 53.89% |
| D | 1492 | 805 | 687 | 53.95% |
| T | 1306 | 724 | 582 | 55.44% |
| PT | 439 | 2 | 437 | 0.46% |

Rozbicie kategorii `B`:

- wszystkie pytania `B`: `2194`,
- z PJM: `969`,
- bez PJM: `1225`,
- realne pokrycie `B`: `44.17%`,
- `B-only`: `721`, z PJM `94`, pokrycie `13.04%`,
- `B` wspolne z innymi kategoriami: `1473`, z PJM `875`, pokrycie `59.40%`.

Wniosek:

- paczka jest kompletna wzgledem metadanych PJM, ktore mamy w bazie,
- paczka nie jest kompletna wzgledem calej kategorii `B`,
- najwiekszy brak jest w pytaniach `B-only`.

## Grupy Uzytkownikow

### Uzytkownik Self-Service PJM

Zaklada konto samodzielnie.

Flow:

1. wybiera kategorie prawa jazdy,
2. wybiera preferowany start nauki `PJM`,
3. potwierdza e-mail,
4. bez platnosci moze wejsc do darmowego modulu PJM,
5. jesli chce pelna baze i pozostale tryby, przechodzi do zakupu pelnego dostepu.

### Uzytkownik Self-Service Klasyczny

Zaklada konto samodzielnie, ale nie wybiera PJM.

Flow:

1. wybiera kategorie,
2. wybiera `Pelna nauka klasyczna` albo `Zdecyduje pozniej`,
3. po weryfikacji e-mail trafia do standardowej bramy dostepu,
4. bez aktywnego dostepu widzi paywall.

### Uzytkownik Dodany Przez Moderatora

Konto tworzy moderator.

Flow:

1. moderator wybiera kategorie,
2. moderator moze zaznaczyc preferowany start `PJM`,
3. uzytkownik otrzymuje konto pelne albo tymczasowe,
4. konto ma 90 dni dostepu moderatorskiego,
5. uzytkownik po pierwszym logowaniu moze potwierdzic lub zmienic preferencje startu,
6. pelny dostep moderatorski nie ogranicza go do darmowego PJM.

### Administrator, Moderator, Konto Testowe

Role systemowe zachowuja pelny dostep do kategorii i nie powinny byc ograniczane mechanika darmowego PJM.

## Rejestracja I Onboarding

### Formularz Rejestracji

Obecnie formularz zbiera:

- imie i nazwisko,
- e-mail,
- haslo,
- twarda kategorie nauki.

Docelowo dodajemy neutralne pytanie:

`Jak chcesz zaczac nauke?`

Opcje:

- `Nauka z tlumaczeniami PJM`,
- `Pelna nauka klasyczna`,
- `Zdecyduje pozniej`.

Nie pytamy:

- `Czy jestes osoba glucha?`,
- `Czy jestes osoba nieslyszaca?`,
- `Czy masz niepelnosprawnosc?`.

Powod:

- nie potrzebujemy danych medycznych,
- preferencja nauki wystarcza produktowo,
- unikamy zbierania wrazliwych informacji,
- jezyk jest bardziej ludzki i mniej stygmatyzujacy.

### Social Login

Przy Google/Facebook:

- kategoria nadal musi byc wybrana przed redirectem,
- preferencja startu powinna byc zapisana w sesji razem z `target_category_id`,
- po powrocie z providera konto dostaje profil z kategoria i preferencja.

Jesli preferencji nie ma:

- ustawiamy `undecided`,
- po pierwszym wejsciu na `/nauka` pokazujemy wybor modulow.

### Konto Tymczasowe Od Moderatora

Jesli konto tworzy moderator bez e-maila:

- preferencja PJM moze byc ustawiona przez moderatora,
- po przejeciu konta uzytkownik potwierdza e-mail,
- preferencja moze zostac bez zmian albo byc zmieniona przez uzytkownika.

## Dostep I Paywall

### Dwa Typy Dostepu

Musimy rozdzielic:

- `full_product_access`,
- `pjm_free_access`.

`full_product_access` daje:

- pelna nauke klasyczna,
- Zen mode,
- pelne egzaminy,
- ranking,
- statystyki,
- powtorki calej kategorii,
- wszystkie pytania aktywnej kategorii.

`pjm_free_access` daje:

- darmowy modul PJM,
- sesje PJM,
- pytania z aktywnej kategorii, ktore maja PJM,
- progres dla tych pytan,
- podsumowanie modulu PJM,
- ekran zakupu pelnego dostepu.

`pjm_free_access` nie daje:

- klasycznej nauki calej kategorii,
- Zen mode dla calej kategorii,
- pelnego egzaminu,
- rankingu,
- wszystkich statystyk premium,
- pytan bez PJM.

### Warunki Darmowego Dostepu PJM

Uzytkownik powinien miec darmowy dostep PJM, jesli:

- jest zalogowany,
- ma potwierdzony e-mail,
- ma przypisana twarda kategorie,
- w tej kategorii istnieje co najmniej jedno aktywne pytanie z PJM,
- konto nie jest zablokowane,
- konto nie jest usuniete ani wygasle w sensie systemowym.

### Zachowanie Po Logowaniu

Resolver po logowaniu powinien dzialac tak:

1. konto zablokowane: odmowa,
2. admin/moderator/test: trasa systemowa lub pelny dostep,
3. konto tymczasowe wymagajace przejecia: claim flow,
4. brak potwierdzonego e-maila: verify-email,
5. brak kategorii: onboarding kategorii,
6. aktywny pelny dostep: `/nauka`,
7. preferencja `pjm` i dostepny PJM w kategorii: `/nauka` z kaflem PJM jako pierwszym albo bezposrednio `/nauka/pjm`,
8. brak pelnego dostepu i brak preferencji PJM: paywall,
9. brak pelnego dostepu, ale PJM dostepne: pokazac ekran wyboru `Darmowy PJM` albo `Pelna nauka`.

## Model Danych

### User Profile

Dodajemy pole:

`preferred_learning_track`

Wartosci:

- `pjm`,
- `classic`,
- `undecided`.

Alternatywa:

`learning_preferences` jako JSON, ale na start prostsze i czytelniejsze bedzie pole tekstowe.

Rekomendacja:

- uzyc zwyklego pola string,
- walidowac enumem aplikacyjnym,
- nie przechowywac informacji medycznej.

### Sign Language Assets

Tworzymy osobna tabele, np. `question_sign_language_assets`.

Nie uzywamy zwyklego `question_media`, bo:

- PJM jest warstwa dostepnosci, a nie glownym materialem pytania,
- jeden `external_id` moze wystepowac w wielu kategoriach,
- assety PJM sa wspolne dla wielu rekordow `questions`,
- role `question`, `answer_a`, `answer_b`, `answer_c` maja inne znaczenie niz zwykle media pytania.

Proponowane pola:

- `id`,
- `external_id`,
- `asset_role`,
- `disk`,
- `path`,
- `source_disk`,
- `source_path`,
- `source_filename`,
- `source_extension`,
- `mime_type`,
- `bytes`,
- `duration_seconds`,
- `width`,
- `height`,
- `checksum`,
- `processing_status`,
- `processing_notes`,
- `is_active`,
- `import_batch_id`,
- `created_at`,
- `updated_at`.

`asset_role`:

- `question`,
- `answer_a`,
- `answer_b`,
- `answer_c`.

`processing_status`:

- `discovered`,
- `queued`,
- `processed`,
- `failed`,
- `skipped`,
- `orphaned`.

Indeksy:

- unique `external_id + asset_role`,
- index `external_id`,
- index `processing_status`,
- index `is_active`.

### Import Runs

Mozemy:

- uzyc istniejacego `content_import_runs`, jesli pasuje do raportowania,
- albo stworzyc osobna tabele `sign_language_import_runs`.

Rekomendacja:

- jesli `content_import_runs` jest wystarczajaco elastyczne, reuse,
- jesli brakuje pol dla media processing, stworzyc osobna tabele.

Minimalne pola import run:

- `id`,
- `source_path`,
- `source_files_count`,
- `discovered_question_ids_count`,
- `matched_question_ids_count`,
- `orphaned_question_ids_count`,
- `processed_files_count`,
- `failed_files_count`,
- `total_source_bytes`,
- `total_processed_bytes`,
- `started_at`,
- `finished_at`,
- `status`,
- `report`.

## Import PJM

### Komenda

Docelowa komenda:

```bash
php artisan pjm:import-sign-language-assets "D:/pytania_egzaminacyjne_na_prawo_jazdy_tlumaczenia_migowe_12_2025 (1)/Pytania egzaminacyjne na prawo jazdy - tłumaczenia migowe 2025"
```

Tryby:

- `--dry-run`,
- `--process-video`,
- `--skip-processing`,
- `--category=B`,
- `--limit=100`,
- `--force`,
- `--report=storage/app/reports/pjm-import.json`.

### Etap Discovery

Importer:

- listuje pliki `pjm*.wmv`,
- parsuje nazwe,
- wyciaga `external_id`,
- wyciaga role:
- `pjm10793.wmv` = `question`,
- `pjm10793a.wmv` = `answer_a`,
- `pjm10793b.wmv` = `answer_b`,
- `pjm10793c.wmv` = `answer_c`,
- liczy checksum,
- porownuje z aktywna baza,
- oznacza osierocone pliki.

### Etap Mapping

Mapowanie odbywa sie po:

`numeric part of source filename == questions.external_id`

Nie mapujemy po `question_id`, bo:

- `question_id` jest lokalne,
- jeden `external_id` moze miec wiele rekordow w roznych kategoriach,
- PJM dotyczy tresci pytania, a nie pojedynczego rekordu kategorii.

### Etap Processing

Nie importujemy surowych `.wmv` bezposrednio do runtime.

Proces:

1. `WMV` jako material zrodlowy,
2. analiza rozdzielczosci i kadru,
3. decyzja o crop/pad/scale,
4. transkodowanie do `MP4/H.264`,
5. opcjonalnie drugi wariant lekki,
6. zapis assetu docelowego,
7. zapis metadanych,
8. raport oszczednosci rozmiaru.

## Obrobka Wideo PJM

### Problem

Obecne pliki sa duze i nieoptymalne:

- cala paczka ma okolo `11.46 GB`,
- format `WMV` nie jest docelowym formatem webowym,
- duzo przestrzeni po bokach jest puste,
- kluczowy obraz to osoba migajaca na srodku,
- zbyt szeroki kadr zwieksza rozmiar i pogarsza odbior na mobile.

### Stan Aktualny Paczki Wideo

Folder roboczy:

`E:/Pytania egzaminacyjne na prawo jazdy tlumaczenia migowe 2025`

Aktualny skan folderu:

- liczba plikow: `1976`,
- format zrodlowy: `.wmv`,
- kodek wideo: glownie `wmv3`,
- rozmiar laczny: okolo `10.67 GiB` / `11.46 GB`,
- sredni rozmiar pliku: okolo `5.53 MiB`,
- najmniejszy plik: okolo `0.78 MiB`,
- najwiekszy plik: okolo `39.31 MiB`.

Rozdzielczosci:

- `1024x576`: `4` pliki,
- `1280x720`: `1166` plikow,
- `1920x1080`: `806` plikow.

Wniosek:

- mamy mieszanke `720p` i `1080p`,
- nie mozemy zalozyc jednego wymiaru zrodlowego,
- automatyczny `cropdetect` nie wystarcza, bo nie wykrywa istotnych marginesow, jesli tlo nie jest czarne,
- potrzebujemy kontrolowanego profilu kadrowania i walidacji wizualnej.

### Narzedzia Lokalne

Stan srodowiska roboczego:

- `ffmpeg` jest dostepny lokalnie,
- `ffprobe` jest dostepny lokalnie,
- zainstalowano `mediainfo`,
- zainstalowano `imagemagick`,
- dodano lokalny shim `magick`.

Obecny `ffmpeg` to pelny build `Gyan 8.1`, z obsluga:

- `libx264` dla docelowego `H.264/MP4`,
- `libx265` dla testow `HEVC`, jesli kiedys bedzie potrzebny,
- `libsvtav1` i `libaom` dla testow `AV1`,
- `libvpx` dla `VP9`,
- `libvmaf` dla pomiarow jakosci,
- enkoderow sprzetowych `NVENC`, `QSV`, `AMF`,
- akceleracji `cuda`, `dxva2`, `d3d11va`, `d3d12va`, `qsv`, `opencl`, `vulkan`, `amf`.

Wniosek:

- nie potrzebujemy wymieniac `ffmpeg` na inny build,
- do webowego startu rekomendowany pozostaje `H.264/MP4`,
- dodatkowe kodeki typu `HEVC` albo `AV1` mozemy testowac pozniej, ale nie powinny byc pierwszym formatem produkcyjnym,
- `mediainfo` uzywamy do raportow technicznych,
- `imagemagick` uzywamy do kontaktowych arkuszy miniaturek i porownan wizualnych przed/po.

### Cel Obrobki

Chcemy:

- zachowac czytelnosc osoby migajacej,
- ograniczyc puste marginesy boczne,
- nie obciac rak i gestow,
- obnizyc bitrate i rozmiar,
- uzyskac stabilny format webowy,
- przygotowac wariant mobile-friendly.

### Ryzyko Kadrowania

Kadrowanie PJM jest bardziej ryzykowne niz zwykle przyciecie filmu.

Nie wolno:

- obciac dloni,
- obciac mimiki,
- obciac zakresu ruchu,
- automatycznie przyciac tylko po pierwszej klatce,
- zbyt agresywnie zoomowac postaci.

Rekomendacja:

- najpierw zrobic probe na reprezentatywnych plikach,
- wykryc bounding box ruchu/postaci na wielu klatkach,
- dodac bezpieczny margines,
- porownac crop przed/po wizualnie,
- dopiero potem przetwarzac cala paczke.

### Proponowane Warianty Eksportu

Wariant docelowy `standard`:

- format `mp4`,
- kodek `H.264`,
- wysokosc okolo `720p` lub mniej, jesli zrodlo nie uzasadnia wiekszej,
- `faststart`,
- umiarkowany bitrate,
- zachowany dzwiek tylko jesli kiedykolwiek bedzie potrzebny; na start prawdopodobnie `bez audio`.

Wariant docelowy `mobile`:

- nizsza rozdzielczosc,
- mocniejsza kompresja,
- nadal czytelne rece i mimika.

Na start rekomendacja:

- nie robic od razu dwoch wariantow dla wszystkich,
- najpierw ustalic jeden dobry preset,
- dopiero po testach w UI zdecydowac, czy potrzebny jest wariant mobile.

### Pipeline Obrobki

Etap badawczy:

1. wybrac probe `20-30` plikow,
2. zmierzyc rozdzielczosci, bitrate, duration, rozmiar,
3. wygenerowac miniatury przed/po,
4. przetestowac kilka cropow,
5. sprawdzic czy nie obcina gestow,
6. wybrac preset.

Etap automatyzacji:

1. skrypt analizuje klatki,
2. wykrywa obszar aktywny,
3. dodaje margines bezpieczenstwa,
4. transkoduje do MP4,
5. zapisuje raport przed/po,
6. oznacza pliki wymagajace recznego review.

Etap QA:

1. losowa kontrola probki,
2. kontrola plikow, gdzie crop byl agresywny,
3. kontrola plikow, gdzie wykrycie postaci bylo niepewne,
4. kontrola dlugich filmow i filmow odpowiedzi A/B/C.

### Bezpieczny Workflow Dla 1976 Plikow

Zasada najwazniejsza:

`Nie edytujemy oryginalow. Nigdy.`

Oryginalna paczka zostaje nietknieta i traktowana jako material zrodlowy tylko do odczytu.

Tworzymy osobny katalog wynikowy, np.:

- `E:/pjm-video-processing/source` - opcjonalny mirror albo wskazanie na oryginal,
- `E:/pjm-video-processing/output/v1` - gotowe pliki `.mp4`,
- `E:/pjm-video-processing/tmp` - pliki tymczasowe,
- `E:/pjm-video-processing/logs` - logi przetwarzania,
- `E:/pjm-video-processing/reports` - raporty CSV/JSON/HTML.

Nazwa pliku musi zostac logicznie zachowana:

- `pjm100.wmv` -> `pjm100.mp4`,
- `pjm10793b.wmv` -> `pjm10793b.mp4`,
- `pjm11509c.wmv` -> `pjm11509c.mp4`.

Nie robimy:

- zmiany nazw na losowe UUID,
- zmiany nazw na kolejne numery,
- recznego dopisywania prefiksow,
- usuwania sufiksow `a`, `b`, `c`,
- nadpisywania wynikow bez jawnego `--force`.

### Manifest Przetwarzania

Przed jakakolwiek obrobka generujemy manifest.

Manifest moze byc plikiem `JSONL`, `CSV` albo tabela techniczna w bazie. Rekomendacja na start:

- lokalny `JSONL` dla procesu przetwarzania,
- pozniej import metadanych do bazy aplikacji.

Kazdy wiersz manifestu:

- `source_filename`,
- `source_path`,
- `source_extension`,
- `source_checksum_sha256`,
- `source_bytes`,
- `external_id`,
- `asset_role`,
- `source_width`,
- `source_height`,
- `duration_seconds`,
- `output_filename`,
- `output_path`,
- `output_tmp_path`,
- `processing_profile`,
- `crop_box`,
- `status`,
- `attempts`,
- `started_at`,
- `finished_at`,
- `error_message`,
- `processed_bytes`,
- `processed_checksum_sha256`,
- `saving_percent`,
- `review_required`.

Statusy:

- `pending` - jeszcze nie ruszone,
- `processing` - aktualnie przetwarzane,
- `done` - plik gotowy i zweryfikowany,
- `failed` - blad przetwarzania,
- `review_required` - plik technicznie gotowy, ale wymaga kontroli,
- `skipped` - swiadomie pominiety.

To rozwiazuje problem utraty miejsca przy awarii:

- proces zawsze wie, ktore pliki sa `done`,
- po restarcie wraca do `pending` i `failed`,
- pliki `processing` starsze niz np. `30 minut` mozna automatycznie cofnac do `pending`,
- `.tmp` bez wpisu `done` nie jest uznawany za gotowy wynik.

### Atomiczny Zapis Wynikow

Kazdy plik przetwarzamy tak:

1. odczytaj rekord z manifestu,
2. ustaw status `processing`,
3. zapisz wynik do `tmp/pjm100.mp4.tmp`,
4. uruchom `ffprobe` na pliku tymczasowym,
5. sprawdz duration, rozmiar, kodek i brak audio,
6. policz checksum wyniku,
7. przenies `tmp/pjm100.mp4.tmp` do `output/v1/pjm100.mp4`,
8. ustaw status `done`.

Jesli proces padnie w polowie:

- zostaje tylko plik `.tmp`,
- rekord nie ma statusu `done`,
- kolejny run moze bezpiecznie usunac stare `.tmp` i powtorzyc plik.

### Tryby Uruchamiania Skryptu

Docelowy skrypt powinien miec tryby:

- `scan` - tworzy manifest bez obrobki,
- `sample` - wybiera probe do testow,
- `preview` - generuje miniatury przed/po,
- `process` - przetwarza pliki,
- `verify` - sprawdza gotowe MP4,
- `report` - generuje raport rozmiarow i bledow,
- `retry-failed` - ponawia tylko bledy,
- `resume` - kontynuuje od ostatniego stabilnego miejsca.

Przykladowe komendy docelowe:

```bash
php artisan pjm:media:scan --source="E:/Pytania egzaminacyjne na prawo jazdy tlumaczenia migowe 2025" --manifest="E:/pjm-video-processing/manifest.jsonl"
php artisan pjm:media:process --manifest="E:/pjm-video-processing/manifest.jsonl" --profile="v1-safe" --limit=50
php artisan pjm:media:process --manifest="E:/pjm-video-processing/manifest.jsonl" --profile="v1-safe" --resume
php artisan pjm:media:verify --manifest="E:/pjm-video-processing/manifest.jsonl"
php artisan pjm:media:report --manifest="E:/pjm-video-processing/manifest.jsonl"
```

Na czas badan mozemy zaczac od skryptu technicznego, np. `scripts/pjm_video_processing.ps1` albo `scripts/pjm_video_processing.py`, a dopiero po potwierdzeniu presetow przeniesc logike do komend Artisan.

### Kadrowanie Bez Ryzyka

Nie stosujemy agresywnego automatycznego kadrowania calej paczki.

Bezpieczna strategia:

1. zachowac pelna wysokosc kadru,
2. cropowac glownie szerokosc,
3. trzymac osobe migajaca w centrum,
4. zostawic duzy margines na ruch rak,
5. osobno sprawdzic profile dla `1280x720` i `1920x1080`,
6. oznaczac do review pliki, w ktorych ruch zbliza sie do krawedzi.

Profile testowe:

- `no-crop-compress` - tylko konwersja i kompresja, punkt odniesienia,
- `safe-center-crop-80` - zachowana wysokosc, okolo `80%` szerokosci,
- `safe-center-crop-75` - zachowana wysokosc, okolo `75%` szerokosci,
- `manual-review` - brak automatycznego cropu, jesli plik wyglada ryzykownie.

Przyklad myslenia:

- `1280x720` moze testowo zejsc do okolic `1024x720` albo `960x720`,
- `1920x1080` moze testowo zejsc do okolic `1536x1080` albo `1440x1080`, a potem zostac przeskalowane do nizszej wysokosci,
- finalny wymiar wybieramy dopiero po obejrzeniu probek.

### Preset Kompresji Do Testow

Pierwszy bezpieczny preset testowy:

```bash
ffmpeg -i input.wmv -vf "crop=PROFILE,scale=PROFILE" -an -c:v libx264 -preset slow -crf 24 -pix_fmt yuv420p -movflags +faststart output.mp4
```

Testujemy co najmniej:

- `crf 23` - lepsza jakosc, wiekszy plik,
- `crf 24` - prawdopodobny kompromis,
- `crf 26` - mocniejsza kompresja, trzeba sprawdzic rece i mimike.

Nie wybieramy CRF tylko po rozmiarze. Kryterium nadrzedne:

`Gesty, rece i mimika musza byc czytelne.`

### Kontrola Jakosci Przed Masowym Procesem

Przed puszczeniem calej paczki robimy `pilot`.

Pilot:

- `10` plikow malych,
- `10` plikow srednich,
- `10` plikow najwiekszych,
- `10` plikow z sufiksami `a`, `b`, `c`,
- wszystkie rozdzielczosci zrodlowe.

Dla pilota generujemy:

- film wynikowy,
- miniatury przed/po,
- raport rozmiaru,
- raport duration,
- raport crop box,
- liste plikow do recznego review.

Dopiero po zaakceptowaniu pilota idziemy w batch.

### Wnioski Z Pierwszego Pilota

Pierwszy pilot wykonano na:

- `pjm100.wmv` - lekki plik `1280x720`,
- `pjm2875.wmv` - ciezki plik `1920x1080`.

Wyniki orientacyjne:

- `pjm100.wmv`: okolo `4.94 MiB` zrodla,
- `pjm100` bez cropu `CRF 24`: okolo `1.05 MiB`,
- `pjm100` crop `80%`: okolo `0.90 MiB`,
- `pjm100` crop `75%`: okolo `0.87 MiB`,
- `pjm2875.wmv`: okolo `39.31 MiB` zrodla,
- `pjm2875` bez cropu, skalowane do `720p`, `CRF 24`: okolo `2.17 MiB`,
- `pjm2875` crop `80%`, skalowane do `720p`, `CRF 24`: okolo `1.90 MiB`,
- `pjm2875` crop `75%`, skalowane do `720p`, `CRF 24`: okolo `1.82 MiB`.

Wniosek techniczny:

- sama konwersja `WMV -> MP4/H.264` daje bardzo duzy zysk,
- crop pomaga, ale najwieksza redukcja pochodzi z sensownego kodowania i usuniecia audio,
- crop `75%` wyglada obiecujaco na pierwszych klatkach, ale nie moze jeszcze zostac uznany za bezpieczny preset globalny,
- na start najbezpieczniejszy kandydat to `crop 80% + H.264 CRF 24`,
- trzeba obejrzec pelne wideo, nie tylko stopklatki.

Wazne odkrycie:

`pjm2875.wmv` ma dodatkowy strumien `attached pic` przed wlasciwym strumieniem wideo.

To oznacza, ze pipeline nie moze slepo mapowac:

`0:v:0`

Importer musi wybierac pierwszy prawdziwy strumien wideo, pomijajac:

- `attached_pic`,
- strumienie obrazkow,
- miniatury,
- nietypowe dodatki kontenera.

W praktyce:

- `ffprobe` musi zapisac liste strumieni,
- manifest musi zapisac `selected_video_stream_index`,
- `ffmpeg` ma uzywac wskazanego indeksu, np. `-map 0:1`,
- pliki z nietypowym ukladem strumieni powinny dostawac `review_required` albo specjalna flage diagnostyczna.

### Skrypt Roboczy Do Przetwarzania

Utworzono skrypt:

`scripts/pjm-video-processing.ps1`

Skrypt obsluguje akcje:

- `scan` - skanuje pliki `.wmv`, wybiera prawdziwy strumien wideo i tworzy manifest,
- `process` - przetwarza rekordy z manifestu do `.mp4`,
- `verify` - sprawdza, czy wyniki maja wideo i nie maja audio,
- `report` - tworzy raport `CSV` i podsumowanie `JSON`,
- `preview` - tworzy arkusz podgladu z klatkami przed/po.

Skrypt zachowuje bezpieczne zasady:

- nie modyfikuje oryginalow,
- zachowuje logiczne nazwy plikow,
- zapisuje wynik najpierw do `.tmp.mp4`,
- po sukcesie przenosi plik do finalnego `.mp4`,
- zapisuje status po kazdym pliku,
- umie kontynuowac prace przez `-Resume`,
- rozroznia `pending`, `processing`, `done`, `failed`, `review_required`.

Przyklad batcha testowego:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\pjm-video-processing.ps1 -Action scan -Source "E:\Pytania egzaminacyjne na prawo jazdy tlumaczenia migowe 2025" -WorkRoot "E:\pjm-video-processing\batch25" -Manifest "E:\pjm-video-processing\batch25\manifest.json" -Profile safe-center-crop-80 -Limit 25 -Force
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\pjm-video-processing.ps1 -Action process -WorkRoot "E:\pjm-video-processing\batch25" -Manifest "E:\pjm-video-processing\batch25\manifest.json" -Profile safe-center-crop-80 -Limit 25 -Resume
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\pjm-video-processing.ps1 -Action verify -WorkRoot "E:\pjm-video-processing\batch25" -Manifest "E:\pjm-video-processing\batch25\manifest.json" -Profile safe-center-crop-80
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\pjm-video-processing.ps1 -Action report -WorkRoot "E:\pjm-video-processing\batch25" -Manifest "E:\pjm-video-processing\batch25\manifest.json" -Profile safe-center-crop-80
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\pjm-video-processing.ps1 -Action preview -WorkRoot "E:\pjm-video-processing\batch25" -Manifest "E:\pjm-video-processing\batch25\manifest.json" -Profile safe-center-crop-80 -Limit 12
```

### Wyniki Batcha 25

Wykonano testowy batch `25` plikow na profilu:

`safe-center-crop-80`

Wynik:

- przetworzone rekordy: `25`,
- status `done`: `25`,
- status `failed`: `0`,
- suma zrodel: okolo `188.93 MiB`,
- suma wynikow: okolo `48.15 MiB`,
- srednia oszczednosc: okolo `71.02%`,
- weryfikacja techniczna: `25/25` OK,
- audio usuniete,
- wyniki zapisane poza oryginalna paczka.

Pliki batcha:

- manifest: `E:/pjm-video-processing/batch25/manifest.json`,
- wyniki: `E:/pjm-video-processing/batch25/output/safe-center-crop-80`,
- raport CSV: `E:/pjm-video-processing/batch25/reports/pjm-processing-report.csv`,
- raport JSON: `E:/pjm-video-processing/batch25/reports/pjm-processing-summary.json`,
- podglad: `E:/pjm-video-processing/batch25/previews/pjm-batch-contact-sheet.jpg`.

Najmniejsze oszczednosci w batchu byly w okolicach `55%`, najwieksze w okolicach `84%`.

Wniosek:

- preset `safe-center-crop-80` przechodzi pierwszy maly batch stabilnie,
- przed batch `100` warto obejrzec kilka pelnych plikow z najnizsza oszczednoscia i kilka z najwyzsza,
- jesli wizualnie bedzie OK, mozemy przejsc do `--limit=100`.

### Wyniki Batcha 100

Wykonano testowy batch `100` plikow na profilu:

`safe-center-crop-80`

Wynik:

- przetworzone rekordy: `100`,
- weryfikacja techniczna: `100/100` OK,
- status `done`: `99`,
- status `review_required`: `1`,
- status `failed`: `0`,
- suma zrodel: okolo `418.53 MiB`,
- suma wynikow: okolo `134.51 MiB`,
- srednia oszczednosc: okolo `58.80%`,
- audio usuniete,
- nazwy plikow zachowane,
- wyniki zapisane poza oryginalna paczka.

Pliki batcha:

- manifest: `E:/pjm-video-processing/batch100/manifest.json`,
- wyniki: `E:/pjm-video-processing/batch100/output/safe-center-crop-80`,
- raport CSV: `E:/pjm-video-processing/batch100/reports/pjm-processing-report.csv`,
- raport JSON: `E:/pjm-video-processing/batch100/reports/pjm-processing-summary.json`,
- podglad batcha: `E:/pjm-video-processing/batch100/previews/pjm-batch-contact-sheet.jpg`,
- podglad pliku do review: `E:/pjm-video-processing/batch100/previews/pjm10805-review/pjm10805-review-sheet.jpg`.

Plik oznaczony do review:

- `pjm10805.wmv`,
- powod: nietypowy uklad strumieni, dodatkowy obrazkowy strumien `mjpeg/attached pic`,
- wybrany prawdziwy stream: `0:1`,
- zrodlo: okolo `33.99 MiB`,
- wynik: okolo `1.61 MiB`,
- oszczednosc: okolo `95.26%`.

Wniosek po batchu `100`:

- skrypt po poprawce poprawnie obsluguje pliki z dodatkowym strumieniem obrazka,
- `review_required` jest dobrym statusem kontrolnym, a nie bledem,
- `safe-center-crop-80` pozostaje rekomendowanym presetem,
- mozemy przejsc do pelnego skanu paczki i przetwarzania produkcyjnego z `-Resume`, ale przed tym warto zdecydowac, czy przetwarzamy cala paczke od razu, czy robimy jeszcze batch `250`.

### Wyniki Batcha 250

Wykonano testowy batch `250` plikow na profilu:

`safe-center-crop-80`

Wynik:

- przetworzone rekordy: `250`,
- weryfikacja techniczna: `250/250` OK,
- status `done`: `249`,
- status `review_required`: `1`,
- status `failed`: `0`,
- suma zrodel: okolo `826.31 MiB`,
- suma wynikow: okolo `296.18 MiB`,
- srednia oszczednosc: okolo `57.03%`,
- audio usuniete,
- nazwy plikow zachowane,
- wyniki zapisane poza oryginalna paczka.

Pliki batcha:

- manifest: `E:/pjm-video-processing/batch250/manifest.json`,
- wyniki: `E:/pjm-video-processing/batch250/output/safe-center-crop-80`,
- raport CSV: `E:/pjm-video-processing/batch250/reports/pjm-processing-report.csv`,
- raport JSON: `E:/pjm-video-processing/batch250/reports/pjm-processing-summary.json`,
- podglad batcha: `E:/pjm-video-processing/batch250/previews/pjm-batch-contact-sheet.jpg`.

Plik oznaczony do review:

- `pjm10805.wmv`,
- powod: nietypowy uklad strumieni, dodatkowy obrazkowy strumien,
- wybrany prawdziwy stream: `0:1`,
- zrodlo: okolo `33.99 MiB`,
- wynik: okolo `1.61 MiB`,
- oszczednosc: okolo `95.26%`.

Najmniejsze oszczednosci w batchu:

- okolo `47-49%`,
- glownie male pliki odpowiedzi,
- nadal akceptowalne, bo pliki wynikowe pozostaja male.

Najwieksze oszczednosci w batchu:

- okolo `84-95%`,
- glownie duze pliki lub pliki z nietypowym strumieniem obrazka.

Wniosek po batchu `250`:

- preset `safe-center-crop-80` jest stabilny na wiekszej probie,
- pipeline `scan -> process -> verify -> report -> preview` dziala poprawnie,
- mechanizm `review_required` wykryl i utrzymal tylko jeden znany przypadek,
- nastepny bezpieczny krok to pelny skan `1976` plikow i przetwarzanie produkcyjne w tym samym katalogu roboczym z mozliwoscia `-Resume`.

### Pelne Przetwarzanie Produkcyjne

Uruchomiono pelny skan paczki:

- liczba rekordow w manifiescie: `1976`,
- status po skanie: `1976 pending`,
- `scan_failed`: `0`,
- pliki oznaczone do recznego review: `33`,
- powod review: nietypowe strumienie obrazka / `attached pic`,
- manifest: `E:/pjm-video-processing/full/manifest.json`,
- katalog wynikowy: `E:/pjm-video-processing/full/output/safe-center-crop-80`,
- logi procesu: `E:/pjm-video-processing/full/logs`.

Uruchomiono pelne przetwarzanie z:

`safe-center-crop-80`

Tryb:

- proces w tle,
- z logami,
- z zapisem manifestu po kazdym pliku,
- z mozliwoscia `-Resume`,
- bez modyfikowania oryginalow.

Wazna poprawka w trakcie:

- proces tła wykazal, ze w tym srodowisku `Get-FileHash` nie jest dostepny,
- skrypt zostal poprawiony na wlasna implementacje SHA-256 przez `.NET`,
- po poprawce test naprawczy przeszedl,
- stare rekordy `failed` sa naprawiane przez `-Resume`.

Checkpoint po uruchomieniu poprawionego procesu:

- `done`: `24`,
- `processing`: `1`,
- `pending`: `1951`,
- `failed`: `0`.

Wynik koncowy pelnego procesu:

- przetworzone rekordy: `1976`,
- weryfikacja techniczna: `1976/1976` OK,
- status `done`: `1943`,
- status `review_required`: `33`,
- status `failed`: `0`,
- suma zrodel: okolo `10929.87 MiB`,
- suma wynikow: okolo `2409.62 MiB`,
- srednia oszczednosc: okolo `71.52%`,
- audio usuniete,
- nazwy plikow zachowane,
- oryginaly pozostaly nietkniete.

Wyniki pelnego procesu:

- manifest: `E:/pjm-video-processing/full/manifest.json`,
- wyniki MP4: `E:/pjm-video-processing/full/output/safe-center-crop-80`,
- raport CSV: `E:/pjm-video-processing/full/reports/pjm-processing-report.csv`,
- raport JSON: `E:/pjm-video-processing/full/reports/pjm-processing-summary.json`,
- lista plikow do review: `E:/pjm-video-processing/full/reports/pjm-review-required.csv`,
- podglad kontrolny: `E:/pjm-video-processing/full/previews/pjm-batch-contact-sheet.jpg`.

Interpretacja `review_required`:

- to nie sa bledy konwersji,
- to pliki z nietypowym ukladem strumieni, najczesciej dodatkowy obrazkowy strumien przed prawdziwym wideo,
- wszystkie zostaly poprawnie przetworzone,
- powinny zostac recznie obejrzane przed importem produkcyjnym.

Po zakonczeniu pelnego procesu trzeba wykonac:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\pjm-video-processing.ps1 -Action verify -WorkRoot "E:\pjm-video-processing\full" -Manifest "E:\pjm-video-processing\full\manifest.json" -Profile safe-center-crop-80
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\pjm-video-processing.ps1 -Action report -WorkRoot "E:\pjm-video-processing\full" -Manifest "E:\pjm-video-processing\full\manifest.json" -Profile safe-center-crop-80
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\pjm-video-processing.ps1 -Action preview -WorkRoot "E:\pjm-video-processing\full" -Manifest "E:\pjm-video-processing\full\manifest.json" -Profile safe-center-crop-80 -Limit 80
```

### Batchowanie

Masowe przetwarzanie robimy w paczkach:

- najpierw `--limit=25`,
- potem `--limit=100`,
- potem pelny `--resume`.

Po kazdej paczce:

- uruchamiamy `verify`,
- sprawdzamy liczbe `done`,
- sprawdzamy liczbe `failed`,
- sprawdzamy najwieksze oszczednosci i najwieksze pogorszenia,
- losowo ogladamy kilka wynikow.

Jesli cos pojdzie zle:

- zatrzymujemy proces,
- poprawiamy profil,
- nie ruszamy plikow `done` bez decyzji,
- mozemy wypuscic `v2` do osobnego katalogu bez niszczenia `v1`.

### Raport Media Processing

Raport powinien zawierac:

- `source_filename`,
- `external_id`,
- `asset_role`,
- `source_bytes`,
- `processed_bytes`,
- `saving_percent`,
- `source_width`,
- `source_height`,
- `processed_width`,
- `processed_height`,
- `crop_box`,
- `duration_seconds`,
- `status`,
- `review_required`,
- `notes`.

## Modul Nauki PJM

### Ekran `/nauka`

Aktualizacja UX `2026-05-06`: szczegolowy plan wdrozenia widocznosci i startera PJM jest w `docs/PJM-STARTER-UX-NAUKA-SPRINT-PLAN.md`.

Dla uzytkownika z preferencja PJM i dostepnym modulem PJM ekran `/nauka` pokazuje duzy kafel z symbolem PJM jako wejscie do modulu.

Kafel PJM nie jest ogolnym elementem marketingowym. Ma byc widoczny tylko dla osob, ktore przy rejestracji wybraly PJM albo maja te preferencje ustawiona administracyjnie.

Dla darmowego uzytkownika PJM bez pelnego produktu:

- kafel PJM jest aktywny i prowadzi do `/nauka/pjm`,
- kafel jest wiekszy i wizualnie wskazany dwiema strzalkami,
- klasyczna nauka, Zen mode, egzamin, ranking i pozostale opcje niezwiązane z PJM sa wyszarzone,
- na ekranie zostaje tylko jeden button platny: `Aktywuj pelna nauke`,
- nie pokazujemy wielu opisow, licznikow i osobnych CTA przy module PJM.

### Start Sesji PJM

Nowy tryb:

`StudySessionManager::MODE_PJM = 'pjm'`

Sesja PJM:

- bierze kategorie z profilu uzytkownika,
- filtruje pytania z aktywnym `question_sign_language_assets`,
- moze przyjac `question_status`,
- domyslnie startuje od `unanswered`,
- pozwala wejsc w `incorrect` i `review`,
- zapisuje odpowiedzi przez obecny mechanizm progresu.

### UI Sesji PJM

Uklad pytania:

1. media glowne pytania, jesli istnieja, albo panel `Pytanie tekstowe GOV.PL`,
2. jeden film PJM tlumaczacy tresc pytania,
3. tekst pytania,
4. odpowiedzi tekstowe jako glowne przyciski wyboru,
5. wynik i wyjasnienie.

Decyzja UI `2026-05-06`:

- w glownym flow pokazujemy tylko jeden film PJM przypisany do pytania,
- filmow PJM odpowiedzi A/B/C nie pokazujemy pod pytaniem,
- odpowiedzi A/B/C albo TAK/NIE maja byc przede wszystkim tekstowe i klikalne,
- assety PJM odpowiedzi pozostaja w bazie jako dane techniczne / przyszla warstwa dostepnosci,
- jesli kiedys wrócimy do filmow odpowiedzi, musza byc zaprojektowane jako pomocniczy element karty odpowiedzi, nie osobna sekcja przed odpowiedziami.

Dla pytan tekstowych GOV.PL:

- nie udajemy brakujacego medium,
- pokazujemy panel informujacy, ze materialem egzaminacyjnym jest tresc pytania i odpowiedzi,
- obok pokazujemy jeden film PJM pytania.

### Koniec Modulu PJM

Po przerobieniu wszystkich pytan PJM w kategorii:

Komunikat:

`Ukonczyles pytania z tlumaczeniem PJM. W kategorii B zostaly jeszcze pytania bez tlumaczenia migowego. Mozesz kontynuowac nauke w pelnym trybie klasycznym albo powtorzyc pytania PJM.`

CTA:

- `Powtorz bledy PJM`,
- `Powtorz wszystkie PJM`,
- `Przejdz do pelnej nauki`,
- `Zobacz plany dostepu`.

### Egzaminopodobny Tryb PJM

Nie nazywamy tego `Egzamin B w PJM`, bo nie mamy pelnego pokrycia.

Nazwa:

`Test z pytan PJM`

Opis:

`Sesja testowa z pytan, ktore maja tlumaczenia PJM. Nie obejmuje calej bazy kategorii.`

## Wspolny Progres

Obecny fundament:

- `user_question_progress` zapisuje progres po `user_id + question_id`,
- `StudySessionManager` ma filtry `all`, `unanswered`, `incorrect`, `correct`, `memorized`,
- odpowiedz w sesji zapisuje progres przez `QuestionProgressManager`.

Docelowo:

- sesja PJM uzywa tego samego `recordAnswer`,
- pytanie zrobione w PJM przestaje byc `unanswered`,
- klasyczna nauka z filtrem `unanswered` nie pokazuje juz tego pytania,
- bledy z PJM trafiaja do wspolnej puli bledow,
- powtorki moga byc odpalane z PJM lub pelnego produktu, zalezenie od dostepu i dostepnosci assetu.

Wazne ograniczenie:

- progres jest po `question_id`, czyli po rekordzie kategorii,
- przy twardej kategorii uzytkownika to jest poprawne,
- nie potrzebujemy na start progresu po `external_id`.

## API I Backend

### Nowe Lub Zmienione Elementy

Backend:

- model `QuestionSignLanguageAsset`,
- migracja `question_sign_language_assets`,
- opcjonalny model import run,
- komenda `pjm:import-sign-language-assets`,
- serwis `PjmAssetManifestBuilder`,
- serwis `PjmCoverageService`,
- serwis `PjmAccessResolver` albo rozszerzenie `ProductAccessResolver`,
- rozszerzenie `StudySessionManager` o `MODE_PJM`,
- payload builder dodajacy assety PJM do pytania.

Frontend:

- pole preferencji w rejestracji,
- kafel `Nauka PJM` na `/nauka`,
- ekran startowy modulu PJM,
- UI sesji PJM,
- komponent `PjmVideoBlock` dla filmu pytania,
- brak osobnego komponentu filmow odpowiedzi w glownym flow,
- ekran koncowy modulu,
- komunikaty upsell do pelnej nauki.

Admin:

- raport coverage PJM,
- lista assetow PJM,
- status importu,
- lista orphaned files,
- status processingu,
- ewentualny podglad przed/po obrobce.

## Routing

Proponowane trasy:

- `GET /nauka/pjm` - ekran modulu PJM,
- `POST /nauka/pjm` - start sesji PJM,
- `GET /nauka/pjm/koniec` - opcjonalny ekran koncowy,
- `GET /admin/pjm/coverage` - raport admin,
- `GET /admin/pjm/assets` - lista assetow.

Alternatywa:

- korzystac z istniejacego `/nauka` i startowac sesje przez parametr `mode=pjm`.

Rekomendacja:

- kafel na `/nauka`,
- osobna trasa startowa dla czytelnosci,
- wspolny ekran sesji po starcie, ale z `ui_shell = pjm`.

## Teksty I Komunikaty

### Rejestracja

`Jak chcesz zaczac nauke?`

`Nauka z tlumaczeniami PJM`

`Skorzystasz bezplatnie z pytan, dla ktorych mamy tlumaczenia migowe. Pelna baza i pozostale tryby sa dostepne po aktywacji planu.`

### Kafel PJM

`Nauka PJM`

`Darmowy modul z tlumaczeniami migowymi dla pytan, ktore je posiadaja.`

`969 pytan z PJM w kategorii B`

### Brak Pelnego Dostepu

`Masz dostep do darmowego modulu PJM. Pelna baza pytan, Zen mode, egzaminy i ranking wymagaja aktywnego planu.`

### Koniec PJM

`Ukonczyles pytania z tlumaczeniem PJM. W tej kategorii zostaly jeszcze pytania bez tlumaczenia migowego. Mozesz kontynuowac w pelnej nauce albo powtorzyc PJM.`

## Sprinty

### Sprint 0 - Decyzje I Dokumentacja

Cel:

Ustalic zakres, zasady uczciwej komunikacji i granice darmowego dostepu.

Zakres:

- zatwierdzic, ze PJM jest darmowy do czasu pelnego pokrycia,
- zatwierdzic, ze pelna nauka pozostaje platna,
- zatwierdzic osobny modul PJM,
- zatwierdzic wspolny progres,
- zatwierdzic brak pytania o niepelnosprawnosc w rejestracji,
- zatwierdzic nazwy UI.

Kryteria akceptacji:

- [ ] dokument zaakceptowany,
- [ ] decyzje produktowe sa jasne,
- [ ] wiadomo, co jest darmowe, a co platne,
- [ ] znamy ryzyka media processingu.

### Sprint 1 - Model Danych I Coverage PJM

Cel:

Zbudowac fundament danych bez jeszcze widocznego UI.

Status realizacji `2026-05-05`:

- [x] dodano migracje `question_sign_language_assets`,
- [x] dodano model `QuestionSignLanguageAsset`,
- [x] dodano relacje z `Question` po `external_id`,
- [x] dodano serwis `PjmCoverageService` liczacy pokrycie per kategoria,
- [x] dodano testy relacji i coverage dla pytan wspoldzielonych miedzy kategoriami,
- [x] raport realnych wartosci z lokalnej bazy zostal potwierdzony po imporcie assetow PJM do tabeli.

Zakres:

- migracja `question_sign_language_assets`,
- model `QuestionSignLanguageAsset`,
- relacje/scope po `external_id`,
- serwis coverage PJM,
- raport coverage per kategoria,
- testy jednostkowe mapowania nazw plikow.

Kryteria akceptacji:

- [x] mozna zapisac asset `question`, `answer_a`, `answer_b`, `answer_c`,
- [x] unique key nie pozwala zdublowac roli dla tego samego `external_id`,
- [x] coverage dla `B` zwraca `969 / 2194` po imporcie assetow,
- [x] orphaned ids sa raportowane w importerze dry-run, gdy CLI ma dostep do bazy,
- [x] kod nie miesza PJM ze zwyklym `question_media`.

### Sprint 2 - Importer PJM Dry Run

Cel:

Bezpiecznie czytac folder PJM i generowac raport bez ruszania plikow.

Status realizacji `2026-05-05`:

- [x] dodano parser nazw `pjm{id}.mp4` oraz `pjm{id}a/b/c.mp4`,
- [x] dodano komende `pjm:import-sign-language-assets --dry-run`,
- [x] komenda odmawia pracy bez `--dry-run`, zeby uniknac przypadkowego zapisu,
- [x] dodano raport tekstowy w konsoli,
- [x] dodano opcjonalny raport JSON przez `--report=`,
- [x] dodano testy parsera i raportu dry-run,
- [x] dry-run na `E:/pjm-video-processing/full/output/safe-center-crop-80` znajduje `1976` plikow i `1139` unikalnych numerow,
- [x] dry-run z dostepem do bazy potwierdza `19` osieroconych numerow.

Zakres:

- komenda `pjm:import-sign-language-assets --dry-run`,
- parser nazw plikow,
- rozpoznawanie roli assetu,
- porownanie z aktywna baza,
- raport JSON,
- raport tekstowy w konsoli,
- testy parsera.

Kryteria akceptacji:

- [x] importer znajduje `1976` plikow,
- [x] importer znajduje `1139` unikalnych numerow,
- [x] importer wykrywa `279` zestawow z A/B/C,
- [x] importer wykrywa `19` osieroconych numerow po uruchomieniu z dostepem do bazy,
- [x] dry run nic nie zapisuje do storage produkcyjnego.

### Sprint 3 - Badanie I Preset Obrobki Wideo

Cel:

Nie przetwarzac calej paczki zanim nie wiemy, jak bezpiecznie kadrowac i kompresowac PJM.

Status realizacji `2026-05-06`:

- [x] przygotowano i zweryfikowano preset `safe-center-crop-80`,
- [x] przetworzono `1976 / 1976` plikow do MP4,
- [x] usunieto audio i zachowano nazwy plikow,
- [x] oryginaly pozostaly nietkniete,
- [x] srednia oszczednosc rozmiaru wynosi `71.52%`,
- [x] `33` pliki oznaczono jako `review_required`,
- [x] wynik znajduje sie w `E:/pjm-video-processing/full/output/safe-center-crop-80`.

Zakres:

- wybrac probe `20-30` plikow,
- sprawdzic rozdzielczosci, bitrate i rozmiary,
- przygotowac kilka presetow ffmpeg,
- sprawdzic crop poziomy,
- porownac przed/po,
- ustalic margines bezpieczenstwa dla rak i mimiki,
- spisac rekomendowany preset.

Kryteria akceptacji:

- [x] mamy raport przed/po dla probki,
- [x] znamy srednia oszczednosc rozmiaru,
- [x] zadna zaakceptowana probka nie obcina gestow,
- [x] mamy jeden preset `safe-center-crop-80`,
- [x] mamy liste przypadkow wymagajacych recznego review.

### Sprint 4 - Importer Z Processingiem MP4

Cel:

Zapisac przetworzone assety PJM MP4 do storage i tabeli, bez mieszania ich ze zwyklym `question_media`.

Status realizacji `2026-05-06`:

- [x] komenda obsluguje osobny tryb `--write`,
- [x] komenda wymaga dokladnie jednego trybu: `--dry-run` albo `--write`,
- [x] import zapisuje pliki do konfigurowalnego dysku i prefixu,
- [x] domyslny dysk PJM to `media_local`,
- [x] domyslny prefix PJM to `pjm/sign-language`,
- [x] import zapisuje rekordy w `question_sign_language_assets`,
- [x] import zapisuje `bytes`, `checksum_sha256`, `source_filename`, `source_path`, `processing_profile`, `processing_status`,
- [x] import probuje uzupelnic `duration_seconds`, `width`, `height` przez `ffprobe`,
- [x] import potrafi oznaczyc `review_required` na podstawie raportu CSV,
- [x] import ma opcje `--limit` i `--force`,
- [x] drugi przebieg bez `--force` pomija istniejace rekordy,
- [x] bledy pojedynczych plikow sa raportowane i nie zatrzymuja calej petli,
- [x] import calego folderu zostal uruchomiony z hosta PHP przez `127.0.0.1:5432`,
- [x] zaimportowano `1976` assetow z `E:/pjm-video-processing/full/output/safe-center-crop-80`,
- [x] import oznaczyl `33` assety jako `review_required` i zapisal raport `output/analysis/pjm-import-full-report.json`,
- [x] `duration_seconds`, `width` i `height` zostaly potwierdzone na prawdziwym imporcie pelnej paczki.

Zakres:

- import gotowych plikow `MP4`,
- zapis do storage,
- checksum zrodla,
- metadane duration/width/height/bytes,
- status processingu,
- raport oszczednosci,
- retry dla bledow,
- opcja `--limit`,
- opcja `--force`.

Kryteria akceptacji:

- [x] mozna zaimportowac ograniczona probe przez `--limit`,
- [x] assety sa zapisywane w tabeli,
- [x] MP4 odtwarza sie w przegladarce po podpieciu UI,
- [x] importer zapisuje metadane wideo `duration/width/height`, jesli `ffprobe` je zwroci,
- [x] bledne pliki nie wywracaja calego importu.

### Sprint 5 - Dostep PJM Free I Preferencja Rejestracji

Cel:

Dac darmowy dostep tylko do PJM, bez odblokowania pelnego produktu.

Status realizacji `2026-05-05`:

- [x] dodano pole `preferred_learning_track` w `user_profiles`,
- [x] dodano wartosci `pjm`, `classic`, `undecided`,
- [x] rejestracja e-mail zapisuje preferencje startu nauki,
- [x] social login przenosi preferencje przez stan sesji OAuth,
- [x] dodano resolver `PjmFreeAccessResolver`,
- [x] resolver PJM nie odblokowuje pelnego produktu,
- [x] dodano middleware `EnsurePjmModuleAccess` i alias `pjm.access`,
- [x] konto zablokowane nie przechodzi przez resolver PJM,
- [x] konto bez potwierdzonego e-maila nie przechodzi przez resolver PJM,
- [x] konta systemowe `admin`, `moderator`, `test` zachowuja dostep,
- [ ] moderator provisioning z preferencja PJM zostanie dopiety przy kolejnym dotknieciu formularza tworzenia kont.

Zakres:

- pole `preferred_learning_track`,
- walidacja rejestracji,
- social login z preferencja,
- moderator provisioning z preferencja,
- resolver `pjm_free_access`,
- middleware `EnsurePjmModuleAccess`,
- testy dostepu.

Kryteria akceptacji:

- [x] uzytkownik bez pelnego dostepu moze przejsc resolver PJM, jesli jego kategoria ma assety PJM,
- [x] ten sam uzytkownik nie dostaje pelnego produktu bez aktywnego dostepu,
- [x] social login nie omija zasad,
- [x] konto moderatora nadal ma pelny 90-dniowy dostep przez istniejacy grant,
- [x] konto zablokowane nie wchodzi do PJM.

### Sprint 6 - Kafel PJM Na `/nauka`

Cel:

Pokazac uzytkownikowi jasny wybor sciezki nauki.

Status realizacji `2026-05-05`:

- [x] `/nauka` moze dzialac jako ekran wyboru dla uzytkownika z darmowym PJM,
- [x] pelne akcje nauki pozostaja za `product.access`,
- [x] dashboard kieruje uzytkownika z dostepem PJM na `/nauka`, zamiast na paywall,
- [x] dodano kafel `Nauka PJM` z coverage dla wybranej kategorii,
- [x] kafel pokazuje, ze PJM jest darmowym modulem i nie sugeruje pelnego pokrycia,
- [x] dodano trase `/nauka/pjm` za middleware `pjm.access`,
- [x] `/nauka/pjm` pokazuje ekran przejsciowy z coverage i jasnym komunikatem, ze sesja PJM powstaje w Sprint 7,
- [x] uzytkownik bez pelnego dostepu nie moze uruchomic klasycznej nauki, Zen, egzaminu ani rankingu bez aktywacji planu.

Zakres:

- dane coverage na ekranie `/nauka`,
- kafel `Nauka PJM`,
- stan darmowy,
- stan premium,
- CTA do pelnej nauki,
- komunikaty dla kategorii bez PJM,
- UI zgodny z obecnym stylem strony.

Kryteria akceptacji:

- [x] uzytkownik PJM widzi kafel jako pierwszy,
- [ ] licznik dla `B` pokazuje `969 pytan z PJM` po imporcie pelnej paczki do tabeli,
- [x] brak pelnego dostepu nie blokuje PJM,
- [x] brak pelnego dostepu blokuje pelne tryby,
- [x] komunikaty nie sugeruja pelnego pokrycia.

### Sprint 7 - Sesja `MODE_PJM`

Cel:

Uruchomic sesje nauki ograniczona do pytan z PJM.

Status realizacji `2026-05-05`:

- [x] dodano `StudySessionManager::MODE_PJM`,
- [x] dodano filtr pytan po aktywnym assetcie PJM w roli `question`,
- [x] sesja PJM domyslnie startuje od `question_status = unanswered`,
- [x] `/nauka/pjm` ma formularz startu sesji,
- [x] `POST /nauka/pjm` tworzy sesje PJM dla twardej kategorii uzytkownika,
- [x] dodano `study.session.access`, ktore pozwala darmowemu PJM tylko na sesje `mode=pjm`,
- [x] pelne tryby sesji nadal wymagaja `product.access`,
- [x] payload pytania zawiera `sign_language_assets`,
- [x] odpowiedzi PJM korzystaja z obecnego `QuestionProgressManager`,
- [x] dodano testy wyboru pytan, payloadu assetow, wspolnego progresu i blokady zwyklych sesji bez abonamentu.

Zakres:

- `MODE_PJM`,
- filtr tylko pytan z PJM,
- domyslny `question_status = unanswered`,
- wspolny zapis progresu,
- payload z assetami PJM,
- testy wyboru pytan,
- testy progresu.

Kryteria akceptacji:

- [x] sesja PJM dla `B` wybiera tylko pytania z PJM,
- [x] odpowiedz zapisuje `user_question_progress`,
- [x] pytanie zrobione w PJM nie jest juz `unanswered` dla tego samego `question_id`,
- [x] bledna odpowiedz trafia do wspolnego progresu bledow przez `QuestionProgressManager`,
- [x] pytania bez PJM nigdy nie pojawiaja sie w sesji PJM.

### Sprint 8 - UI Sesji PJM

Cel:

Zbudowac czytelny ekran pytania z filmem migowym.

Status realizacji `2026-05-05`:

- [x] dodano komponent `PjmVideoBlock`,
- [x] sesja `mode=pjm` korzysta z lokalnego flow nauki jak klasyczna nauka,
- [x] film pytania PJM jest pokazywany w glownym panelu mediow,
- [x] filmy odpowiedzi A/B/C nie sa pokazywane w glownym flow sesji,
- [x] odpowiedzi pozostaja tekstowe i klikalne,
- [x] pytanie tekstowe GOV.PL ma jawny panel zamiast pustego miejsca na medium,
- [ ] UI wymaga jeszcze oceny wizualnej na szerszym desktopie i mobile po decyzji `1 pytanie = 1 PJM`.

Zakres:

- komponent filmu PJM pytania,
- fallback dla pytan tekstowych bez glownego medium,
- odtwarzacz mobile/desktop,
- preload i lazy loading,
- zachowanie przy wolnym laczu,
- dopasowanie do obecnego shellu nauki.

Kryteria akceptacji:

- [x] film pytania PJM jest widoczny i czytelny w komponencie sesji,
- [x] odpowiedzi A/B/C nie sa poprzedzane osobna siatka filmow PJM,
- [x] odpowiedzi tekstowe sa glownym miejscem wyboru,
- [x] layout przechodzi build TypeScript/Vite dla desktop/mobile breakpointow,
- [x] odtwarzacz nie psuje klasycznej sesji, bo renderuje sie tylko przy `mode=pjm`.

### Sprint 9 - Ekran Koncowy I Upsell Do Pelnej Nauki

Cel:

Poprowadzic uzytkownika po przerobieniu darmowej puli PJM.

Status realizacji `2026-05-05`:

- [x] backend przekazuje `pjmCompletion` tylko dla sesji `mode=pjm`,
- [x] `pjmCompletion` zawiera coverage kategorii, status pelnego dostepu i URL-e CTA,
- [x] ekran koncowy PJM pokazuje uczciwy komunikat, ze uzytkownik ukonczyl tylko pule z filmami PJM,
- [x] ekran pokazuje liczniki: pytania PJM, cala kategoria, pokrycie PJM,
- [x] CTA dla PJM nie uzywa pelnej nauki za `product.access`, tylko darmowego `POST /nauka/pjm`,
- [x] uzytkownik bez pelnego dostepu widzi opcjonalne przejscie do cennika,
- [ ] UI wymaga jeszcze oceny w przegladarce po imporcie realnych assetow PJM do lokalnego storage.

Zakres:

- ekran ukonczenia puli PJM,
- liczniki `PJM przerobione` i `cala kategoria`,
- CTA do powtorek PJM,
- CTA do pelnej nauki,
- CTA do planow dostepu,
- komunikaty dla braku pelnego dostepu.

Kryteria akceptacji:

- [x] uzytkownik rozumie, ze ukonczyl tylko pule PJM,
- [x] uzytkownik widzi ile zostalo w pelnej kategorii,
- [x] moze powtorzyc bledy PJM,
- [x] moze przejsc do zakupu pelnego dostepu,
- [x] tekst jest uczciwy i nie wywiera presji.

### Sprint 10 - Admin Coverage I Operacje

Cel:

Administrator musi widziec stan PJM i jakosc importu.

Status realizacji `2026-05-06`:

- [x] dodano serwis `AdminPjmCoverageReportService`,
- [x] dodano strone Filament `PJM Coverage` w grupie `Operacje`,
- [x] admin widzi globalne coverage PJM, liczbe brakow, orphaned assets, statusy processingu i rozmiar storage,
- [x] admin widzi coverage per kategoria, w tym wyrozniona kategorie `B`,
- [x] admin moze sprawdzic konkretny `external_id` i zobaczyc powiazane pytania oraz assety PJM,
- [x] dodano eksport raportu przez `admin.pjm.report.download` w formacie JSON i CSV,
- [x] dodano testy dostepu admin/non-admin oraz poprawnosci raportu,
- [ ] pozostaje podglad assetu bezposrednio w panelu, jesli bedziemy chcieli odtwarzac MP4 inline zamiast linku do pliku.

Zakres:

- dashboard coverage per kategoria,
- lista assetow PJM,
- status processingu,
- orphaned files,
- missing expected assets,
- eksport raportu CSV/JSON,
- podglad assetu.

Kryteria akceptacji:

- [x] admin widzi coverage `B`,
- [x] admin widzi pliki osierocone,
- [x] admin widzi bledy processingu,
- [x] admin moze sprawdzic asset dla `external_id`,
- [x] raport da sie zapisac do pliku.

### Sprint 11 - QA, Regresja I Release

Cel:

Zamknac modul przed udostepnieniem.

Zakres:

- testy backend,
- testy frontend,
- testy flow rejestracji,
- testy paywalla,
- testy wspolnego progresu,
- testy mobile,
- testy performance,
- finalna weryfikacja tekstow.

Kryteria akceptacji:

- [x] uzytkownik bez platnosci widzi PJM i nie widzi pelnej nauki,
- [x] uzytkownik z platnoscia widzi wszystko,
- [x] uzytkownik po PJM ma poprawny progres w `user_question_progress`,
- [x] pytania bez PJM nie trafiaja do PJM,
- [x] MP4 dzialaja w przegladarce,
- [x] UI nie myli darmowego PJM z pelnym produktem.

Status QA `2026-05-06`:

- utworzono konto `pjm-b-test@local.test` z haslem lokalnym `password` i twarda kategoria `B`,
- konto bez pelnego dostepu uruchamia modul PJM i nie odblokowuje klasycznej nauki,
- sprawdzono kat. `B`: `969 / 2194` pytan z PJM, czyli `44.17%`,
- przetestowano recznie pelna sesje `12 / 12` pytan PJM w kat. `B`, w tym pytanie z filmami odpowiedzi `A/B/C`,
- potwierdzono, ze oryginalne medium pytania egzaminacyjnego pozostaje widoczne razem z filmem tlumacza PJM,
- potwierdzono zapis `12` odpowiedzi i `12` rekordow progresu dla konta QA,
- potwierdzono ekran koncowy PJM z uczciwa informacja o `1225` pytaniach bez filmu PJM,
- usunieto z trybu PJM panel `Dzialy`, zeby darmowy uzytkownik nie uruchamial przez pomylke klasycznej sesji.
- dopisano test regresji potwierdzajacy, ze uzytkownik z preferencja PJM po zakupie pelnego dostepu ma `starter_mode = false`.
- browser desktop potwierdzil starter PJM na `/nauka`: jeden kafel PJM, jeden button `Aktywuj pelna nauke`, wyszarzone linki poza PJM z `aria-disabled` i `tabindex=-1`.
- browser mobile `390x844` potwierdzil brak poziomego overflow, kafel PJM `~168x168 px` i button aktywacji bezposrednio pod kaflem.

## Matryca Weryfikacji

Scenariusze obowiazkowe:

- [x] rejestracja self-service z preferencja PJM,
- [x] social login z preferencja PJM,
- [ ] konto moderatora z preferencja PJM,
- [x] konto bez pelnego dostepu wchodzi do PJM,
- [x] konto bez pelnego dostepu nie wchodzi do klasycznej nauki,
- [x] konto z pelnym dostepem wchodzi do PJM i klasycznej nauki,
- [ ] konto zablokowane nie wchodzi nigdzie,
- [x] sesja PJM wybiera tylko pytania z assetami PJM,
- [x] odpowiedz w PJM aktualizuje `user_question_progress`,
- [x] klasyczna nauka `unanswered` nie powtarza pytan zrobionych w PJM,
- [x] koniec PJM pokazuje pozostale pytania bez PJM,
- [x] pytanie z assetami A/B/C nie pokazuje filmow odpowiedzi w glownym flow,
- [x] odpowiedzi pod pytaniem pozostaja tekstowe i klikalne,
- [x] import raportuje `orphaned`,
- [x] media processing raportuje oszczednosc rozmiaru,
- [x] brak pliku PJM nie wywala sesji.

## Ryzyka

### Ryzyko 1 - Niepelne Pokrycie

Najwieksze ryzyko produktowe to mylne oczekiwanie uzytkownika, ze PJM obejmuje wszystko.

Mitigacja:

- jasne liczniki,
- jasne teksty,
- darmowy dostep do PJM,
- brak komunikacji `pelna baza PJM`.

### Ryzyko 2 - Obrobka Wideo Obetnie Gesty

Automatyczny crop moze obciac rece albo mimike.

Mitigacja:

- probe przed masowym processingiem,
- margines bezpieczenstwa,
- QA probki,
- oznaczanie plikow wymagajacych review.

### Ryzyko 3 - Za Duzo Transferu

Filmy moga byc ciezkie, szczegolnie na mobile.

Mitigacja:

- MP4/H.264,
- crop,
- kompresja,
- lazy loading,
- opcjonalny wariant mobile,
- cache i CDN/storage docelowy.

### Ryzyko 4 - Pomieszanie Darmowego I Platnego Dostepu

Jesli middleware bedzie nieprecyzyjne, darmowy PJM moze przypadkowo odblokowac pelny produkt.

Mitigacja:

- osobny resolver PJM,
- testy dostepu,
- osobne middleware,
- jawne scenariusze regresji.

### Ryzyko 5 - Dublowanie Progresu

Jesli zrobimy osobny progres PJM, uzytkownik bedzie widzial powtorki jako nowe pytania w klasycznej nauce.

Mitigacja:

- jeden wspolny `user_question_progress`,
- tryby jako filtry prezentacji,
- test `PJM answer removes classic unanswered`.

## Otwarte Decyzje

- [x] Czy trasa modulu ma byc `/nauka/pjm`, czy kafel startuje normalna sesje z `mode=pjm`? Decyzja: `GET /nauka/pjm` jako ekran modulu i `POST /nauka/pjm` jako start sesji.
- [ ] Czy robimy jeden wariant MP4, czy od razu `standard + mobile`?
- [ ] Czy import run trzymamy w `content_import_runs`, czy w osobnej tabeli?
- [ ] Czy uzytkownik bez pelnego dostepu moze widziec ograniczone statystyki PJM?
- [ ] Czy `Test z pytan PJM` wchodzi do pierwszego release, czy dopiero po podstawowym module?
- [ ] Czy administrator ma miec reczne wlaczanie/wylaczanie pojedynczego assetu PJM?

## Rekomendowany Porzadek Prac

Nie zaczynamy od UI.

Najbezpieczniejsza kolejnosc:

1. model danych,
2. coverage,
3. importer dry-run,
4. badanie crop/kompresji,
5. processing MP4,
6. access resolver,
7. kafel PJM,
8. sesja PJM,
9. ekran koncowy,
10. admin coverage,
11. QA.

Powod:

- UI bez stabilnych assetow bedzie kruchy,
- assety bez dobrego processingu zjedza storage i transfer,
- darmowy dostep bez osobnego resolvera moze popsuc paywall,
- progres trzeba zachowac wspolny od pierwszej implementacji.

## Wniosek

Modul PJM ma sens, ale tylko jako uczciwie ograniczony, darmowy tor nauki.

Najwazniejsze zasady:

- PJM jest darmowy, dopoki nie mamy pelnego pokrycia,
- pelna nauka pozostaje platna,
- PJM jest osobnym modulem, nie przyciskiem w klasycznej nauce,
- progres jest wspolny dla wszystkich trybow,
- assety PJM trzymamy osobno po `external_id`,
- przed masowym importem musimy rozwiazac crop i kompresje wideo,
- komunikacja musi jasno mowic, ile pytan ma PJM i czego modul nie obejmuje.
