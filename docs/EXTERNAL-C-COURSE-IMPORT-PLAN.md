# Plan audytu, eksportu i importu zewnętrznego kursu kategorii C

Data audytu: 2026-07-30

Status: etapy 0–6 w zakresie eksportu, preview, backupu i lokalnego importu są zakończone. Wszystkie 14 modułów i 1323 pytania znajdują się w lokalnej bazie, korzystają z istniejącego modułu nauki i pozostają dostępne wyłącznie administratorowi. Kolekcja nadal ma `is_public = false`, a pytania `is_active = false`; publikacja wymaga osobnej decyzji.

## Wynik pilota 2026-07-30

Moduł źródłowy `100` (`1.1`) został pobrany przez autoryzowane API bez używania nawigacji pytań i bez zapisywania postępu kursanta.

- 117 pytań i 117 unikalnych identyfikatorów;
- 0 brakujących treści i wymaganych odpowiedzi;
- 0 nieprawidłowych oznaczeń poprawnej odpowiedzi;
- 87 odwołań do właściwych obrazów, 30 placeholderów `KW_BRAK.jpg`;
- 37 unikalnych obrazów pobranych lokalnie, 0 błędów pobierania;
- 0 wyjaśnień i 0 podstaw prawnych w odpowiedzi tego modułu;
- manifest preview: 117 pytań, 87 mediów, 0 błędów;
- kontrolowane tabele bazy pozostały bez zmian;
- paczka nie zawiera danych logowania, identyfikatorów sesji ani danych kursanta.

Pytania w manifeście i lokalnej bazie pozostają `is_active = false`, a kolekcja ma `is_public = false`. Nie są dostępne w zwykłej nauce ani egzaminie kategorii C.

## Wynik lokalnego importu pilota 2026-07-30

Przed migracją wykonano pełny backup lokalnej bazy PostgreSQL:

- plik: `storage/app/backups/qualification-c-pilot/before-module-100-20260730.dump`;
- rozmiar: 7 650 908 bajtów;
- SHA-256: `E8AA964B454162EAF12B604AE44D46074FF981E85D199883798347503A280B89`.

Po migracji i imporcie:

- 1 kolekcja `qualification-c-accelerated`, aktywna technicznie, ale niepubliczna;
- 1 moduł `1.1`, źródłowy identyfikator `100`, oczekiwane 117 pytań;
- 117 nieaktywnych pytań i 117 relacji moduł–pytanie;
- pozycje są unikalne i kompletne od 1 do 117;
- 87 rekordów mediów, 87 fizycznie dostępnych plików, 0 braków;
- 0 zduplikowanych `external_id`, 0 osieroconych relacji;
- 0 aktywnych pytań pilota w zwykłym katalogu kategorii C.

Ponowny import zakończył się bez błędów: 0 nowych pytań, 0 nowych relacji, 117 relacji bez zmian, 87 mediów bez zmian i 87 pominiętych uploadów. Potwierdza to idempotencję pilota.

## Wynik pełnego importu 2026-07-30

Autoryzowane API zostało pobrane bez klikania kolejnych pytań i bez zapisywania odpowiedzi lub postępu w serwisie źródłowym. Powtarzalny kolektor znajduje się w `scripts/collect_qualification_c_course.py`, a idempotentny runner importu w `scripts/import_qualification_c_course.php`.

- 14 modułów i 1323 pytania;
- 1323 unikalne, namespacowane identyfikatory `tpj:Y:*`;
- 0 brakujących treści, odpowiedzi wymaganych i oznaczeń poprawnej odpowiedzi;
- 809 rzeczywistych odwołań do obrazów i 514 placeholderów `KW_BRAK.jpg`;
- 201 unikalnych nazw obrazów, 200 pobranych unikalnych plików;
- 1 brak po stronie źródła: `KW_0132.jpg` dla pytania `90394` zwraca HTTP 404; pytanie jest kompletne tekstowo i zostało zaimportowane bez grafiki;
- 0 materiałów oznaczonych jako wideo w aktualnym zakresie kursu;
- pełny preview: 1323 pytania, 808 mediów, 0 błędów i wszystkie kontrolowane tabele bez zmian.

Backup przed pełnym importem:

- katalog: `storage/app/backups/qualification-c-full/before-full-import-20260730-194927`;
- baza: `database.dump`, 7 691 738 bajtów, SHA-256 `257C3BE9A0573D77D503BC52E8616E0525C58D69B5BAFD70ED8EB85E1694A3BB`;
- istniejące media pilota: `media-before-import.zip`, 14 758 192 bajtów, SHA-256 `E6F501EDE6EFED370645057F9EAD2DB7723C3DED99B4D7614D1E243281DDED84`.

Stan po imporcie:

- 1 niepubliczna kolekcja i 14 aktywnych technicznie modułów;
- 1323 nieaktywne pytania i 1323 relacje moduł–pytanie;
- w każdym module kompletne, unikalne pozycje od 1 do oczekiwanej liczby pytań;
- 808 rekordów mediów i 808 fizycznych plików, 0 braków i 0 niezgodności rozmiaru;
- 0 duplikatów `external_id`, 0 osieroconych relacji i 0 aktywnych pytań w zwykłym katalogu C;
- ponowny import: 0 nowych pytań, 0 nowych mediów, 0 nowych relacji, 808 mediów bez zmian, 1323 relacje bez zmian i 808 pominiętych uploadów;
- w interfejsie administratora widoczne jest 14 modułów; moduł 1.2 uruchamia istniejący widok nauki, pokazuje licznik `1 / 170`, odpowiedzi A/B/C i obraz 640×360.

## 1. Najważniejszy wniosek

Zalogowana aplikacja udostępnia kurs **„Kwalifikacja wstępna przyspieszona — kat. C”**. W kodzie aplikacji kurs ma kategorię źródłową `Y` i składa się z 14 modułów oraz 1323 pytań. Zakres został potwierdzony 2026-07-30.

Zbioru nie należy publikować jako „ADR”. Moduł 2.2 dotyczy przepisów regulujących przewóz towarów, ale nie zmienia to klasyfikacji całego kursu. Ewentualny przyszły kurs ADR musi mieć osobną kolekcję, moduły i źródło importu.

Prawo do kopiowania i ponownego publikowania pytań, odpowiedzi, wyjaśnień oraz mediów zostało potwierdzone 2026-07-30 przez właściciela projektu. Dane dostępowe i sesyjne nie mogą być utrwalane w paczkach, logach ani repozytorium.

## 2. Ustalenia techniczne

### 2.1. Moduły i liczba pytań

| ID | Kod | Skrócona nazwa | Liczba pytań |
|---:|---|---|---:|
| 100 | 1.1 | Układ przeniesienia napędu | 117 |
| 101 | 1.2 | Urządzenia służące bezpieczeństwu | 170 |
| 102 | 1.3 | Załadowanie pojazdu | 80 |
| 103 | 1.4 | Optymalizacja zużycia paliwa | 102 |
| 126 | 1.4(a) | Przewidywanie i ocena zagrożeń | 18 |
| 104 | 2.1 | Uwarunkowania społeczne transportu drogowego | 164 |
| 105 | 2.2 | Przepisy regulujące przewóz towarów | 148 |
| 106 | 3.1 | Zagrożenia wypadkami przy pracy | 87 |
| 107 | 3.2 | Przestępstwa i przemyt | 63 |
| 108 | 3.3 | Zagrożenia fizyczne | 83 |
| 109 | 3.4 | Predyspozycje fizyczne i psychiczne | 91 |
| 110 | 3.5 | Sytuacje awaryjne | 74 |
| 111 | 3.6 | Wizerunek przewoźnika | 61 |
| 112 | 3.7 | Uwarunkowania ekonomiczne transportu rzeczy | 65 |
|  |  | **Razem** | **1323** |

### 2.2. Mechanizm źródłowej aplikacji

Aplikacja AngularJS korzysta z API pod adresem `https://api.testynaprawojazdy.eu/eprawko-rest`.

Zidentyfikowane wywołania:

- lista i liczniki modułów: `GET /questions/getModules/{category}`;
- utworzenie/pobranie nauki całego modułu: `PUT /learning/create/studentId/{studentId}/category/{category}/lang/{lang}/moduleId/{moduleId}`;
- zapis postępu przy przechodzeniu po pytaniach: `GET /progress/learn/store/{studentId}/{moduleId}/{current}/{max}/{category}`.

Odpowiedź modułu jest przypisywana do obiektu `exam`, a pytania znajdują się w `exam.execution`. Kolektor powinien pobierać moduł jako całość i **nie używać przycisków następne/poprzednie**, ponieważ nawigacja zapisuje postęp użytkownika.

### 2.3. Pola pytania

W modelu źródłowym występują między innymi:

- `id`, `externalId`, `number`;
- `questionText`;
- `answerA`, `answerB`, `answerC`;
- `correct`;
- `explenation` — pisownia zgodna ze źródłem;
- `legalSource`;
- `media`, `madiaType` — pisownia zgodna ze źródłem;
- `type`, `weight`, `part`, `time`, `tools`, `hard`.

Poprawna odpowiedź jest już obecna w modelu klienta. Nie trzeba automatycznie odpowiadać na pytania, aby ją ustalić.

### 2.4. Media

Bazowy adres mediów to `https://bezpiecznykierowca.eu/internetmedia/640x360/`.

- dla `madiaType = VIDEO` aplikacja próbuje wariantów `.mp4`, `.webm` i `.ogg` na podstawie tej samej nazwy;
- dla pozostałych typów używa bezpośrednio wartości `media` jako nazwy pliku obrazu;
- `KW_BRAK.jpg` jest placeholderem braku grafiki i powinien zostać zamieniony na brak medium, a nie zapisany jako obraz pytania.

Po uzyskaniu prawa do wykorzystania media należy pobrać do własnego storage, policzyć SHA-256, wykryć MIME i parametry pliku oraz nie hotlinkować ich z domeny źródłowej.

## 3. Dopasowanie do obecnej bazy

Obecny model `questions` obsługuje już treść pytania, trzy odpowiedzi, poprawną odpowiedź, wyjaśnienie, punkty, źródło, metadane i relację z `question_media`. Importer `catalog:import-json` ma tryb `--dry-run`, raportowanie i idempotentny upsert po `license_category_id + external_id`.

Osobna struktura kursu i modułów została wdrożona. `question_topics` nadal służą wyłącznie do klasyfikacji semantycznej i SEO, a program szkolenia jest przechowywany niezależnie.

Wdrożone rozszerzenie:

1. `question_collections` — osobna kolekcja, kategoria, typ, źródło i niezależne flagi `is_active`/`is_public`;
2. `question_modules` — kolekcja, ID źródłowe, kod 1.1/1.2, tytuł, kolejność;
3. `question_module_question` — relacja pytania z modułem oraz kolejność źródłowa;
4. użycie istniejącego `content_import_runs` do śledzenia preview, importu i rollbacku.

Pytania mogą pozostać przypisane do kategorii prawa jazdy `C`, ale w interfejsie muszą być filtrowane dodatkowo przez kolekcję. Dzięki temu oficjalna baza egzaminacyjna C, kwalifikacja zawodowa i przyszły ADR nie zostaną zmieszane.

### 3.1. Uszczelnienia importera — wykonane

Przed pilotem usunięto trzy ograniczenia:

- `seedTopics()` działa wewnątrz transakcji i jest cofane przez dry-run;
- media są synchronizowane idempotentnie po `kind + variant + sort_order`, bez wymiany istniejącego rekordu i utraty pól redakcyjnych;
- identyfikatory są namespacowane jako `tpj:Y:{externalId}`;
- manifest obsługuje kolekcję, moduł, pozycję i tryb pełnej synchronizacji `replace`;
- komendy importu mają opcję `--skip-sitemap` dla lokalnych i niepublicznych wsadów.

Paczka surowa i preview pozostają poza bazą. Do bazy trafił wyłącznie znormalizowany, zweryfikowany i niepubliczny pilot modułu 1.1.

## 4. Mapowanie danych

| Źródło | Nasza baza |
|---|---|
| `questionText` | `questions.prompt` |
| `answerA/B/C` | `option_a/b/c` |
| `correct` | `correct_answer`, małe `a/b/c` |
| `explenation` | `explanation` |
| `weight` | `points`, po walidacji zakresu |
| `externalId` lub `id` | `external_id = tpj:Y:{id}` |
| `media`, `madiaType` | `question_media` |
| `legalSource`, `number`, `part`, `time`, `tools`, `hard` | `questions.metadata.source_payload` |
| ID i kod modułu | `question_modules` + tabela łącząca |
| źródło | `questions.source = testynaprawojazdy.eu` |

Pole `hard` nie powinno być automatycznie mapowane na trudność, dopóki nie potwierdzimy, czy jest właściwością pytania, czy prywatną flagą użytkownika.

## 5. Roadmapa wdrożenia

### Etap 0 — bramka zakresu i licencji

- [x] zakres potwierdzony: kwalifikacja wstępna przyspieszona C;
- [x] prawo do kopiowania i publikacji tekstów oraz mediów potwierdzone;
- [x] źródło jest zachowywane w kolekcji, pytaniu i metadanych importu.

### Etap 1 — kolektor preview

- [x] użyć wyłącznie autoryzowanej sesji użytkownika;
- [x] pobrać listę modułów i moduł 1.1;
- [x] zapisać surową odpowiedź JSON bez modyfikowania postępu;
- [x] zapisać manifest i SHA-256 paczki źródłowej;
- [x] nie zapisywać hasła, tokenów ani cookies w plikach i logach.

### Etap 2 — normalizacja i media

- [x] przekształcić źródłowy JSON do osobnego formatu stagingowego;
- [x] zachować pełny surowy rekord w paczce audytowej;
- [x] pobrać obrazy, odrzucić placeholder i zdeduplikować staging po SHA-256;
- [x] utworzyć raport liczników, typów pytań, brakujących pól i mediów;
- [ ] obsługę MP4 i posterów wdrożyć przy pierwszym przyszłym wsadzie zawierającym wideo; aktualne 1323 pytania mają wyłącznie obrazy.

### Etap 3 — warstwa kolekcji i modułów

- [x] dodać migracje, modele i relacje kolekcji/modułów;
- [x] rozszerzyć manifest importu o `collection`, `module` i kolejność;
- [x] zachować kompatybilność z istniejącą oficjalną bazą pytań;
- [x] pozostawić pytania pilota nieaktywne, aby nie mogły wejść do istniejącej sesji nauki;
- [x] dodać sesję nauki filtrującą jawnie po kolekcji i modułach.

### Etap 4 — idempotentny import preview

- [x] przenieść `seedTopics()` do transakcji importera;
- [x] zastąpić kasowanie mediów synchronizacją idempotentną;
- [x] wygenerować paczkę dla modułu 1.1;
- [x] wykonać dry-run: 117 pytań, 87 mediów, 0 błędów, baza bez zmian;
- [x] wykonać backup, migrację i lokalny import pilota;
- [x] powtórzyć import i potwierdzić brak duplikatów oraz zbędnych uploadów.

### Etap 5 — pilot w aplikacji

- [x] dodać listę niepublicznych kolekcji i modułów dostępną wyłącznie administratorowi;
- [x] pobierać pytania przez uporządkowaną relację modułu, bez ogólnego filtra kategorii C;
- [x] wykorzystać istniejący `StudySessions/Show.vue` zamiast tworzyć osobny odtwarzacz pytań;
- [x] uruchamiać uporządkowany moduł jako standardową sesję `learn` z obecnymi mediami, odpowiedziami A/B/C, nawigacją i podsumowaniem;
- [x] zapisywać odpowiedzi i postęp przez istniejący mechanizm `StudySession` oraz `StudySessionAnswer`;
- [x] przekazywać do interfejsu nazwę i kod modułu oraz powrót do listy kolekcji;
- [x] ukryć przełącznik działów oficjalnej bazy C w sesji modułowej, aby nie mieszać programów;
- [x] zachować pytania pilota jako nieaktywne w zwykłej nauce i dopuścić je wyłącznie przez kontrolowane uruchomienie modułu administratora;
- [x] automatycznie zweryfikować autoryzację, kolejność, media, zmianę pytania po odpowiedzi i użycie istniejącego komponentu nauki;
- [x] potwierdzić, że aktualny pełny zakres nie zawiera MP4; test odtwarzania pozostaje warunkowy dla przyszłego wsadu wideo;

### Etap 6 — pełny import i publikacja

- [x] pobrać wszystkie zatwierdzone moduły z możliwością wznowienia;
- [x] przed zapisem wykonać backup bazy i storage;
- [x] uruchomić pełny dry-run, następnie import modułami;
- [x] po imporcie wykonać audyt liczników, integralności i mediów;
- [x] powtórzyć pełny import i potwierdzić idempotencję;
- [ ] wykonać kontrolę redakcyjną znanego braku `KW_0132.jpg`;
- [ ] publikować kolekcję dopiero po osobnej akceptacji.

### Etap 7 — produkcyjna wersja robocza

- [x] wdrażać pełną kolekcję na produkcję jako niepubliczną i dostępną wyłącznie administratorowi;
- [x] oznaczyć kolekcję w interfejsie jako wersję roboczą przeznaczoną do dalszego rozwoju;
- [x] zapisać w metadanych `lifecycle_status = working_version` oraz `planned_refactor = true`;
- [x] oznaczyć w kodzie tymczasowy adapter wykorzystujący obecny `StudySession`;
- [ ] przebudować kod i docelowy moduł kwalifikacji przed udostępnieniem go kursantom;
- [ ] wykonać osobny audyt i uzyskać osobną akceptację przed zmianą `is_public` lub aktywacją pytań.

## 6. Kryteria akceptacji

- suma pytań zgadza się z zaakceptowanym zakresem; dla obecnie widocznego kursu oczekiwane jest 1323;
- każde pytanie ma stabilny, namespacowany `external_id`;
- każda poprawna odpowiedź wskazuje istniejącą opcję;
- brak zduplikowanych pytań w obrębie kolekcji i modułu;
- wszystkie media dostępne w źródle są lokalne i mają poprawny MIME; znany wyjątek `KW_0132.jpg` jest jawnie raportowany jako źródłowe HTTP 404;
- placeholder `KW_BRAK.jpg` nie jest importowany;
- preview ma 0 błędów i nie zmienia bazy ani postępu w serwisie źródłowym;
- ponowne uruchomienie importu nie tworzy duplikatów;
- rollback całego batcha jest możliwy po identyfikatorze importu;
- oficjalna baza pytań C pozostaje odseparowana od kwalifikacji zawodowej i ADR.

## 7. Status produkcyjny

Wdrożenie z 2026-07-30 jest świadomie traktowane jako **wersja robocza**. Pełna kolekcja może znajdować się na serwerze produkcyjnym, lecz pozostaje za autoryzacją administratora, z `is_public = false`, a jej pytania mają `is_active = false`. Obecne uruchamianie kursu korzysta z istniejącego mechanizmu sesji nauki jako rozwiązania przejściowego. Kod oraz doświadczenie użytkownika tego modułu są przeznaczone do przebudowy w następnym etapie.

### Dziennik wdrożenia 2026-07-30

- commit kodu: `936819b9` (`Add working qualification C question collection`);
- produkcyjny backup bazy: `backups/database/2026/07/20260730-181736-prawkonarazpl-pgsql-pgsql-pre-qualification-c-working-20260730.sql.gz`;
- SHA-256 backupu bazy: `65edc68c688f389b438b22093e3db0dca2ba8c0a65fb57ce9797c4eee4bb840b`;
- backup nadpisanych plików: `/tmp/prawkonaraz-qualification-c-code-backup-20260730181808`;
- SHA-256 paczki kodu: `bc92dce64c60871c5df8e7daea65c25652c9ebf58e6889143d15d1bb302f4efa`;
- SHA-256 paczki danych: `4063b6fe8e371940f348419f22894f2815f2e3af44ce07178927e60c5a1c651b`;
- preview produkcyjny: 14/14 manifestów, 1323 pytania, 808 mediów, 0 błędów i brak zmian w bazie;
- import produkcyjny: 1323 utworzone pytania, 808 utworzonych mediów, 1323 przypisania do modułów, 0 błędów;
- drugi import: 0 nowych pytań, 0 nowych mediów, 0 nowych przypisań, 808 pominiętych uploadów;
- audyt końcowy: 1 niepubliczna kolekcja, 14 modułów, 1323 nieaktywne pytania, 808 dostępnych plików, 0 rozbieżności liczników;
- health report, smoke test oraz smoke test z wymaganiem mediów: `OK`;
- publiczny panel kolekcji bez autoryzacji przekierowuje do logowania i nie ujawnia danych.

## 8. Następny krok

Następnym krokiem jest kontrola redakcyjna pytania `90394` bez dostępnego obrazu oraz decyzja, czy kolekcję udostępniać użytkownikom. Do czasu tej decyzji wszystkie moduły pozostają niepubliczne i nie mieszają się z oficjalną bazą ani egzaminem kategorii C.
