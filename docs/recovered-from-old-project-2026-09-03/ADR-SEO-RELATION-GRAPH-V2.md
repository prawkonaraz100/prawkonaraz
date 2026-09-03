# ADR: Graf relacji i hubów pytań V2

Status: `ACCEPTED`

Data: `2026-07-21`

Data akceptacji: `2026-07-21`

Identyfikator: `SEO-LINK-V2-002`

Zakres: publiczna baza pytań, membershipy tematyczne, relacje pytanie–pytanie, ranking i rollout

## 1. Decyzja w skrócie

Rozwijamy istniejący graf V1. Nie tworzymy równoległej taksonomii ani drugiej tabeli relacji pytanie–pytanie.

V2 wprowadza:

1. hierarchię `macro → topic → subtopic` w istniejącej tabeli `question_seo_topics`,
2. wiele membershipów na pytanie, z dokładnie jednym membershipem głównym,
3. jeden dominujący typ pedagogiczny na parę pytań,
4. wiele jawnych dowodów uzasadniających relację,
5. wersjonowany, precomputowany ranking osobno dla każdego pytania, publikowany jako niezmienny snapshot,
6. rollout per klaster z możliwością natychmiastowego powrotu do selektora V1,
7. minimum 15 linków jako sumę relacji bezpośrednich i jawnie opisanych linków kontekstowych, bez przedstawiania szerokiego fallbacku jako „podobnych pytań”.

## 2. Kontekst i stan wejściowy

Produkcyjne V1 po odzyskaniu aliasów ma:

| Metryka | Stan |
|---|---:|
| publiczne wyjaśnienia | 2 176 |
| membershipy główne | 2 176 |
| wszystkie relacje | 6 877 |
| relacje publiczne | 3 996 |
| kandydaci | 2 881 |
| indeksowalne huby | 55 |
| relacje temat–temat | 0 |
| pytania wymagające fallbacku kategorii | 0 |
| pytania bez relacji bezpośredniej | 263 |

V1 spełnia obecny kontrakt publiczny, ale ma ograniczenia:

- membership jest ograniczony do jednego tematu,
- istnieją tylko dwa wykorzystywane poziomy hierarchii,
- `reason`, `difference`, anchory i `metadata` nie tworzą spójnego modelu dowodów,
- pole `direction` zawiera dwa zapisy tej samej wartości: `symetryczna` i `symmetric`,
- selektor oblicza listę podczas żądania,
- kandydaci nie mają wersjonowanego rankingu per pytanie,
- tabela relacji tematów istnieje, lecz nie ma danych ani workflow publikacji,
- rollout jest globalny, a nie klastrowy.

## 3. Cele

- zwiększyć trafność i różnorodność linków bez utraty minimum 15,
- utrzymać pełne SSR i kanoniczne URL-e,
- rozdzielić relacje bezpośrednie od linków poszerzających temat,
- umożliwić pytaniu uczestnictwo w kilku kontekstach,
- zapewnić redakcji dowody, kolejkę i możliwość pin/exclude/reorder,
- zapewnić deterministyczny ranking i audyt zmian między wersjami,
- wdrażać V2 klaster po klastrze,
- zachować V1 jako działający rollback do czasu zakończenia rolloutu.

## 4. Poza zakresem ADR

- automatyczne generowanie treści wyjaśnień,
- zmiana kanonicznych URL-i pytań,
- masowa publikacja wszystkich 2 881 kandydatów,
- linkowanie artykułów prawnych i znaków drogowych do pytań,
- zastąpienie panelu redakcyjnego pełnym zewnętrznym systemem MDM,
- usuwanie pól lub tabel V1 w pierwszej migracji.

## 5. Model tematów

### 5.1. Jedna tabela i trzy poziomy

Pozostaje tabela `question_seo_topics` i relacja `parent_id`.

Dodajemy pola:

| Pole | Typ | Znaczenie |
|---|---|---|
| `kind` | string | `macro`, `topic`, `subtopic` |
| `status` | string | `draft`, `published`, `archived` |
| `content_quality_status` | string | `missing`, `draft`, `legacy`, `approved` |
| `metadata` | JSON nullable | dane pomocnicze i wersja klasyfikatora |

Reguły:

- `macro` nie ma rodzica,
- `topic` ma rodzica `macro`,
- `subtopic` ma rodzica `topic`,
- publiczny hub uruchomiony w V2 wymaga `status=published`, `is_indexable=true`, minimalnej liczby publicznych pytań i `content_quality_status=approved`,
- `question_count` pozostaje cachem operacyjnym, ale jest zawsze wyliczany z publicznych membershipów, nigdy z samego pliku źródłowego,
- zmiana rodzica nie zmienia `slug` ani istniejącego URL-a bez osobnej decyzji i redirectu.

### 5.2. Migracja obecnych tematów

- obecne rekordy `primary:*` bez `parent_id` otrzymują `kind=macro`,
- obecne rekordy `secondary:*` z rodzicem `primary:*` otrzymują `kind=topic`,
- rekordy niespełniające tych reguł trafiają do raportu migracji; backfill nie zgaduje poziomu wyłącznie na podstawie obecności `parent_id`,
- subtopic powstaje dopiero dla pilota i kolejnych ręcznie zaakceptowanych klastrów,
- istniejące tematy otrzymują `status=published` i `content_quality_status=legacy`,
- istniejące 55 indeksowalnych URL-i, ich `is_indexable`, routing i obecność w sitemapie pozostają bez zmian podczas migracji addytywnej,
- przed przełączeniem konkretnego klastra na V2 jego publiczne huby wymagają przeglądu treści i zmiany `legacy → approved`.

### 5.3. Integralność hierarchii

Aplikacja i audyt blokują:

- cykle,
- rodzica samego dla siebie,
- pominięcie poziomu, np. `subtopic → macro`,
- opublikowany subtopic z nieopublikowanym rodzicem,
- indeksowalny hub poniżej progu jakości.

## 6. Model membershipów

### 6.1. Zmiana ograniczenia

Obecny unikalny indeks tylko na `question_public_explanation_id` zostaje zastąpiony unikalnością pary:

```text
(question_public_explanation_id, question_seo_topic_id)
```

Dodajemy:

| Pole | Typ | Znaczenie |
|---|---|---|
| `is_primary` | boolean | główny temat pytania |
| `role` | string | `primary`, `supporting`, `contrast`, `prerequisite` |
| `status` | string | `candidate`, `verified`, `rejected` |
| `confidence` | decimal nullable | pewność klasyfikatora |
| `reason` | text nullable | redakcyjne uzasadnienie |
| `metadata` | JSON nullable | źródło cech, wersja i ślad importu |
| `reviewed_by_user_id` | FK nullable | recenzent |
| `reviewed_at` | timestamp nullable | czas decyzji |

### 6.2. Inwariant głównego membershipu

Każde publiczne wyjaśnienie ma dokładnie jeden membership z `is_primary=true` i statusem `verified`.

Na PostgreSQL wymuszamy najwyżej jeden zweryfikowany główny membership częściowym indeksem unikalnym. Serwis domenowy i audyt wymuszają co najmniej jeden. W testowym SQLite ten sam kontrakt jest sprawdzany przez serwis i testy.

`is_primary` i `role` nie mogą być sprzeczne: `role=primary` występuje wtedy i tylko wtedy, gdy `is_primary=true`. Membershipy publicznie wykorzystywane przez V2 muszą mieć `status=verified`; kandydaci nie wpływają na huby, breadcrumbs ani fallback.

### 6.3. Backfill

Wszystkie obecne 2 176 membershipów otrzymują:

```text
is_primary = true
role = primary
status = verified
source = graph
```

Odzyskane aliasy i kolizje zachowują w `metadata` informację o dopasowaniu treścią.

## 7. Model relacji pytanie–pytanie

### 7.1. Jedna para, jeden dominujący typ

Pozostaje `question_relations` z kanoniczną parą:

```text
left_explanation_id < right_explanation_id
```

Unikalność pary pozostaje bez zmian. `relation_type` oznacza dominujący cel pedagogiczny:

- `blizniacze`,
- `ta_sama_zasada`,
- `wariant`,
- `kontrast`,
- `nie_pomyl_z`,
- `rozszerzenie`,
- `tematyczne`.

Nie tworzymy kilku wierszy tej samej pary tylko dlatego, że istnieje kilka dowodów.

### 7.2. Kierunek

Relacja może być symetryczna albo kierunkowa. Para nadal jest przechowywana kanonicznie, a `direction` i anchory określają prezentację A→B oraz B→A.

V2 normalizuje `direction` do kontrolowanego zbioru `symmetric`, `left_to_right`, `right_to_left`. Produkcyjny backfill mapuje `symetryczna → symmetric`; inne nieznane wartości trafiają do kwarantanny zamiast otrzymać domyślny kierunek. Generator nie tworzy rekomendacji dla strony niedozwolonej przez kierunek.

Audyt musi wykrywać:

- self-linki,
- pary w niekanonicznej kolejności,
- kierunek bez wymaganego anchora,
- relację publiczną do niepublicznego pytania,
- automatyczną publikację poniżej progu,
- sprzeczność typu dominującego z dowodami.

### 7.3. Pola V1

`reason`, `difference`, anchory i `metadata` pozostają w pierwszym etapie. Są źródłem backfillu i rollbackiem panelu. Usunięcie lub zmiana ich znaczenia wymaga osobnego ADR po pełnym rolloucie.

## 8. Dowody relacji

Powstaje tabela `question_relation_evidences`.

Minimalny kontrakt:

| Pole | Znaczenie |
|---|---|
| `question_relation_id` | relacja nadrzędna |
| `evidence_type` | np. `shared_rule`, `shared_sign`, `shared_maneuver`, `same_hazard`, `contrast`, `editorial_note` |
| `summary` | czytelny opis dowodu |
| `source` | `editorial`, `graph`, `classifier`, `import` |
| `confidence` | pewność dowodu |
| `status` | `candidate`, `verified`, `rejected` |
| `metadata` | identyfikatory cech i ślad źródłowy |
| `version` | wersja generatora |
| `reviewed_by_user_id`, `reviewed_at` | decyzja redakcyjna |

Dlaczego osobna tabela, a nie wyłącznie JSON:

- dowody trzeba filtrować i przeglądać w kolejce redakcyjnej,
- potrzebujemy raportów pokrycia według typu dowodu,
- jeden dowód może zostać odrzucony bez odrzucania całej relacji,
- zmiany dowodów muszą być audytowalne,
- JSON pozostaje miejscem na niestabilne szczegóły cech, nie na status workflow.

## 9. Precomputowany ranking i snapshoty

Powstaje tabela `question_relation_ranking_runs`, która reprezentuje niezmienny wynik jednego uruchomienia generatora dla klastra.

| Pole | Znaczenie |
|---|---|
| `question_seo_topic_id` | korzeń klastra |
| `input_version` | identyfikator wersji danych wejściowych |
| `generator_version` | wersja algorytmu i wag |
| `config_hash` | skrót pełnej konfiguracji generatora |
| `status` | `generated`, `validated`, `published`, `rejected`, `retired` |
| `metrics` | JSON z quality gates i porównaniem do poprzedniej wersji |
| `generated_at`, `validated_at`, `published_at` | historia cyklu życia |

Powstaje tabela `question_relation_recommendations`.

Każdy wiersz opisuje rekomendację kierunkową dla jednej strony pytania:

| Pole | Znaczenie |
|---|---|
| `question_relation_ranking_run_id` | niezmienny snapshot rankingu |
| `source_explanation_id` | strona źródłowa |
| `target_explanation_id` | pytanie docelowe |
| `question_relation_id` nullable | bezpośrednia relacja, jeśli istnieje |
| `scope` | `direct`, `same_subtopic`, `same_topic`, `adjacent_topic` |
| `group_key` | blok UI: `closest`, `same_rule`, `dont_confuse`, `extension`, `context` |
| `rank` | stabilna kolejność dla źródła |
| `score` | wynik końcowy |
| `score_components` | JSON z rozkładem punktów i kar |
| `status` | `selected`, `suppressed` |
| `suppression_reason` | jawna przyczyna pominięcia |

Publiczny request nie uruchamia generatora. Czyta opublikowaną wersję rankingu i buduje karty w stałej liczbie zapytań.

Wymagane ograniczenia bazowe:

- unikalna para `(ranking_run_id, source_explanation_id, target_explanation_id)`,
- unikalna pozycja `(ranking_run_id, source_explanation_id, rank)` dla `status=selected`,
- brak modyfikacji rekomendacji po publikacji runu; korekta tworzy nowy run.

### 9.1. Determinizm

Dla tych samych danych i wersji generatora wynik musi być identyczny.

Tie-breakery:

1. pin redakcyjny,
2. zweryfikowana relacja,
3. wynik końcowy,
4. typ pedagogiczny,
5. stabilny `target_explanation_id`.

### 9.2. Skład wyniku

Wynik może uwzględniać:

- typ i status relacji,
- liczbę oraz jakość dowodów,
- wspólny subtopic i supporting memberships,
- zgodność kategorii egzaminacyjnej,
- wartość kontrastu,
- różnorodność typów w całym zestawie,
- kary za duplikaty, zbyt szeroki temat i nadmierną powtarzalność zestawu,
- ręczne pin/exclude.

Wagi są wersjonowane i kalibrowane na gold secie. Nie zapisujemy ich wyłącznie w kodzie bez identyfikatora wersji.

## 10. Kontrakt minimum 15 linków

Wymaganie produktu pozostaje: każda publiczna strona pytania ma co najmniej 15 kanonicznych linków do pytań.

Kolejność:

1. wszystkie opublikowane relacje bezpośrednie spełniające jakość,
2. uzupełnienie z tego samego subtopicu,
3. uzupełnienie z tego samego topicu,
4. wyłącznie redakcyjnie opublikowane tematy sąsiednie,
5. awaryjny fallback V1 monitorowany jako incydent jakościowy.

Reguły prezentacji:

- link kontekstowy nie może udawać relacji bezpośredniej,
- wartościowych relacji bezpośrednich nie obcinamy do 15,
- ranking stosuje limity różnorodności, aby jeden typ nie zdominował całej listy,
- brak 15 linków po poziomie `adjacent_topic` blokuje publikację rankingu V2 dla klastra i pozostawia V1,
- relacja `candidate` nigdy nie jest publiczna tylko dlatego, że brakuje linków.

## 11. Relacje temat–temat

Rozszerzamy istniejącą `question_seo_topic_relations` o:

- `status`,
- `source`,
- `reason`,
- `score`,
- `reviewed_by_user_id`,
- `reviewed_at`,
- `metadata`.

Relacja jest kierunkowa. To pozwala stwierdzić, że topic B jest dobrym następnym krokiem po A, bez wymuszania identycznej rekomendacji w drugą stronę.

Publiczny fallback korzysta wyłącznie z `status=verified`.

## 12. Rollout per klaster

Powstaje tabela `question_relation_rollouts`:

| Pole | Znaczenie |
|---|---|
| `question_seo_topic_id` | topic pilota |
| `mode` | `v1`, `shadow`, `canary`, `v2` |
| `active_ranking_run_id` | atomowo wskazywany, opublikowany snapshot |
| `exposure_percentage` | deterministyczny odsetek pytań klastra w canary |
| `cohort_seed` | wersja stabilnego przydziału pytań do canary |
| `started_at`, `ended_at` | okno rolloutu |
| `approved_by_user_id` | osoba zatwierdzająca |
| `metadata` | progi i notatka decyzyjna |

Znaczenie trybów:

- `v1` — obecny selektor,
- `shadow` — V2 liczy wynik, ale użytkownik widzi V1,
- `canary` — stały, deterministyczny podzbiór stron pytań widzi V2,
- `v2` — pełne włączenie dla klastra.

Canary nie jest losowane per request ani per użytkownik. Dany kanoniczny URL zawsze renderuje tę samą wersję linków do czasu jawnej zmiany kohorty lub aktywnego snapshotu. Chroni to stabilność SSR, cache i sygnałów SEO.

Globalna flaga `QUESTION_RELATIONS_V2_ENABLED` może natychmiast skierować wszystkie klastry do V1. Dotychczasowa `QUESTION_RELATIONS_ENABLED` pozostaje wyłącznikiem całego mechanizmu relacji.

## 13. Publiczny przepływ odczytu

```text
request strony pytania
→ ustalenie głównego topicu i trybu rolloutu
→ V1 albo `active_ranking_run_id` i jego rekomendacje
→ zbiorcze pobranie pytań docelowych i assetów
→ grupy direct/context
→ SSR HTML z kanonicznymi href
```

Budżet dla samego komponentu relacji V2:

- maksymalnie 8 zapytań SQL,
- brak zapytania per karta,
- brak uruchamiania klasyfikatora lub generatora w requestcie,
- deterministyczny wynik do czasu publikacji nowej wersji.

Budżet kontrolny całej strony pytania po dalszej optymalizacji: nie więcej niż 35 zapytań SQL dla ciepłego requestu. Aktualny baseline po batchingu wynosi 58.

## 14. Zapis, publikacja i wersjonowanie

```text
dane źródłowe
→ walidacja i kwarantanna
→ membership candidates / relation candidates / evidences
→ generator rankingu w wersji N
→ raport różnic N-1 vs N
→ decyzja redakcyjna lub quality gate
→ publikacja kompletnej wersji dla klastra
```

Publikacja wersji jest atomowa: po walidacji kompletnego runu jedna transakcja zmienia `active_ranking_run_id` rolloutu. Publiczny request nie może zobaczyć połowy nowego rankingu.

Każdy raport generatora zapisuje:

- wersję wejścia,
- wersję algorytmu,
- liczbę dodanych, usuniętych i przesuniętych linków,
- pokrycie minimum 15,
- liczbę suppressed i przyczynę,
- zmianę powtarzalności zestawów,
- błędy integralności.

## 15. Migracja bez utraty V1

### Etap A — migracje addytywne

- dodać nullable/defaultowe kolumny tematów i membershipów,
- zmienić indeks membershipów na unikalność pary,
- dodać częściowy indeks jednego głównego membershipu,
- utworzyć tabele evidences, ranking runs, recommendations i rollouts,
- rozszerzyć relacje tematów,
- nie usuwać żadnego pola V1.

### Etap B — backfill

- oznaczyć istniejące tematy jako `macro` i `topic` na podstawie kluczy i prawidłowego rodzica,
- oznaczyć 2 176 membershipów jako główne i zweryfikowane,
- znormalizować `symetryczna → symmetric` i skontrolować wszystkie pozostałe kierunki,
- utworzyć evidences z istniejących `reason`, `difference`, anchorów i `metadata`,
- zachować 14 sprzecznych par jako odrzucone/negatywne przykłady,
- uruchomić audyt i porównać liczniki przed/po.

### Etap C — shadow

- wygenerować V2 dla pilota bez zmiany HTML,
- porównać V1 i V2,
- ręcznie ocenić gold set,
- zmierzyć wydajność i stabilność.

### Etap D — canary

- włączyć V2 tylko dla jednego klastra,
- zachować natychmiastowy przełącznik `mode=v1`,
- obserwować CTR, dalszą naukę, błędy i SEO.

### Etap E — rollout

- publikować kolejne klastry po spełnieniu tych samych bramek,
- nie usuwać V1 przed pełnym pokryciem, okresem stabilizacji i osobną decyzją.

## 16. Rollback

Rollback nie cofa destrukcyjnie migracji.

1. Ustawić rollout klastra na `v1`.
2. W razie problemu globalnego wyłączyć V2 dla wszystkich klastrów.
3. Pozostawić ranking i evidences w bazie do analizy.
4. Czyścić tylko cache komponentu i aktywnego snapshotu, nie sesje użytkowników.
5. Nie usuwać membershipów głównych ani relacji redakcyjnych.
6. Przy błędzie migracji danych użyć manifestu backupu i osobnej procedury restore.

## 17. Quality gates

Klaster może przejść z `shadow` do `canary`, gdy:

- 100% pytań ma dokładnie jeden główny membership,
- 100% pytań ma co najmniej 15 linków do poziomu `adjacent_topic`,
- 0 publicznych relacji prowadzi do niepublicznego pytania,
- 0 automatycznych relacji jest poniżej progu,
- 0 duplikatów URL w zestawie,
- 0 self-linków i niekanonicznych par,
- wszystkie topics użyte jako adjacent są zweryfikowane,
- gold set osiąga ustalony próg precision,
- wynik jest deterministyczny w dwóch kolejnych uruchomieniach,
- komponent mieści się w budżecie zapytań,
- test SSR potwierdza kompletne `<a href>` bez zależności od JavaScriptu.

## 18. Obserwowalność

Wymagane metryki:

- liczba pytań w V1/shadow/canary/V2,
- rozkład scope i typów relacji,
- średni i percentylowy stopień grafu,
- pokrycie minimum 15 na każdym poziomie fallbacku,
- liczba orphanów, suppressed i kandydatów,
- liczba zmian zestawu rekomendacji między wersjami,
- CTR per pozycja, blok, scope i typ,
- przejście do kolejnego pytania i dalsza nauka,
- czas oraz liczba zapytań SQL komponentu,
- błędy canonicali, 404 i cienkie huby.

Zdarzenie kliknięcia nie może zawierać treści odpowiedzi użytkownika ani danych wrażliwych. Wystarczą identyfikatory pytań, pozycja, typ, scope, wersja i klaster.

## 19. Odrzucone alternatywy

### Druga taksonomia V2

Odrzucona, ponieważ prowadziłaby do rozjazdu URL-i, panelu, sitemap i membershipów.

### Kilka wierszy relacji dla jednej pary

Odrzucone. Utrudnia deduplikację i ranking. Wiele sygnałów reprezentują evidences.

### Dowody wyłącznie w JSON

Odrzucone jako model docelowy. JSON nie zapewnia wygodnego workflow, statusów i raportowania.

### Ranking liczony podczas requestu

Odrzucony z powodu kosztu, niestabilności i trudnego rollbacku.

### Automatyczne publikowanie kandydatów do osiągnięcia 15

Odrzucone. Minimum linków nie może obniżać progu jakości relacji bezpośrednich.

### Natychmiastowe usunięcie V1

Odrzucone. V1 jest działającym zabezpieczeniem dla pilota i migracji.

## 20. Konsekwencje

Korzyści:

- spójny model bez równoległego systemu,
- lepsza kontrola trafności i różnorodności,
- szybszy publiczny odczyt,
- audytowalność decyzji i wersji,
- bezpieczny rollout klastrowy,
- możliwość rozwijania grafu poza prostą zbieżność tematu.

Koszty:

- cztery nowe tabele i migracja indeksu membershipów,
- konieczność backfillu dowodów,
- większy panel redakcyjny,
- generator i proces publikacji wersji,
- potrzeba gold setu oraz metryk canary.

## 21. Kolejność implementacji

1. zaakceptować niniejszy ADR,
2. przygotować migrację addytywną i test rollbacku,
3. utworzyć gold set z pilota „Zawracanie” i 14 negatywnych par kolizyjnych,
4. dodać serwis integralności membershipów,
5. dodać evidences i backfill V1,
6. dodać generator oraz recommendations,
7. dodać rollout `shadow`,
8. rozszerzyć panel redakcyjny,
9. wykonać porównanie V1/V2,
10. rozpocząć canary dopiero po przejściu quality gates.

## 22. Kryterium akceptacji ADR

Kryterium zostało spełnione 2026-07-21. Właściciel produktu zaakceptował model i rozpoczęcie addytywnego Etapu A bez przełączania publicznego V1.

ADR jest zaakceptowany, gdy Engineering, SEO/redakcja i właściciel produktu potwierdzą:

- jeden istniejący system tematów i relacji,
- wiele membershipów z jednym głównym,
- osobną tabelę dowodów,
- precomputowany ranking,
- niezmienne runy rankingu i atomową publikację aktywnego snapshotu,
- minimum 15 z jawnym rozdzieleniem direct/context,
- rollout per klaster,
- V1 jako rollback do końca wdrożenia.
