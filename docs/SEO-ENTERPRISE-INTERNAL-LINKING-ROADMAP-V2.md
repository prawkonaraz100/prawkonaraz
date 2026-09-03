# Enterprise SEO Internal Linking — stan wdrożenia i roadmapa V2

Status dokumentu: `active / living document`  
Ostatnia aktualizacja: `2026-07-28`
Zakres: publiczna baza pytań, relacje pytanie–pytanie, huby tematyczne i linkowanie wewnętrzne  
Stan produkcji opisany w dokumencie: odczyt z `2026-07-28`

## 1. Cel dokumentu

Ten dokument odpowiada na trzy pytania:

1. Co zostało już zbudowane i wdrożone?
2. W jakim miejscu znajduje się obecnie system linkowania wewnętrznego?
3. Jakie prace należy wykonać, aby przejść od działającego V1 do kontrolowanego grafu wiedzy V2?

Dokument jest operacyjną roadmapą dla zespołu technicznego, SEO i redakcji. Nie zastępuje szczegółowej dokumentacji wdrożeniowej ani polityki SEO.

Dokumenty powiązane:

- `docs/PUBLIC-QUESTION-RELATIONS-SEO-HUBS-IMPLEMENTATION.md` — dokumentacja wdrożonego V1,
- `docs/ADR-SEO-RELATION-GRAPH-V2.md` — zaakceptowana architektura danych, rankingu i rolloutu V2,
- `docs/SEO-RELATION-GRAPH-V2-FOUNDATION-IMPLEMENTATION.md` — zakres, walidacja i procedura wdrożenia addytywnego Etapu A,
- `docs/SEO-RELATION-ZAWRACANIE-LEGAL-VERIFICATION-2026-07-28.md` — P0 audyt materiału, treści i podstaw prawnych reprezentatywnych pytań pilota,
- `docs/SEO-RELATION-V2-CANARY-RUNBOOK.md` — lokalnie przygotowana, domyślnie wyłączona ścieżka publicznego canary,
- `docs/QUESTION-DATABASE-SEO-MASTERPLAN.md` — nadrzędna strategia rozwoju publicznej bazy pytań,
- `docs/QUESTION-DATABASE-SEO-POLICY.md` — przyjęta polityka indeksacji, canonicali, sitemap i internal linkingu,
- `E:\SEO_ENTERPRISE_LINKING_AGENT.md` — zewnętrzny dokument docelowej architektury enterprise, wykorzystany jako źródło wymagań V2.

## 2. Decyzja strategiczna

Nie budujemy drugiego systemu relacji ani drugiej taksonomii.

Wdrożony system V1 pozostaje aktywnym fundamentem. V2 ma rozszerzać istniejące modele, importer, panel redakcyjny, serwis rekomendacji, huby i komponent publiczny.

Najważniejsze zasady:

- nie zmieniamy istniejących kanonicznych URL-i pytań,
- nie publikujemy masowo niezweryfikowanych relacji,
- nie zwiększamy liczby linków kosztem trafności,
- najpierw wykonujemy pilot jednego klastra,
- każda zmiana V2 musi mieć feature flagę albo bezpieczny sposób powrotu do V1,
- relacje redakcyjne mają pierwszeństwo przed automatycznymi,
- nowe huby są indeksowane wyłącznie po spełnieniu kryteriów jakości.

## 3. Legenda statusów

| Status | Znaczenie |
|---|---|
| `DONE` | wdrożone i działające na produkcji |
| `PARTIAL` | istnieje fundament, ale brakuje części funkcji lub kontroli jakości |
| `TODO` | praca nie została rozpoczęta albo nie ma jeszcze produkcyjnego rozwiązania |
| `BLOCKED` | wykonanie zależy od decyzji, danych albo pracy redakcyjnej |

## 4. Podsumowanie wykonawcze

### Co zrobiliśmy

Uruchomiliśmy produkcyjne V1, które:

- importuje centralny graf relacji i kontrolowaną taksonomię,
- chroni wcześniejsze relacje redakcyjne,
- przechowuje kandydatów oraz relacje automatyczne,
- zapewnia co najmniej 15 kanonicznych linków na stronie pytania,
- odróżnia relacje bezpośrednie od szerszego kontekstu tematycznego,
- renderuje wszystkie linki w SSR HTML,
- posiada podstawowe huby, paginację, breadcrumbs i sitemapę tematów,
- udostępnia panel do przeglądu relacji,
- ma import w trybie preview, kwarantannę i wyłącznik V1.

### Gdzie jesteśmy

V1 działa, ale nie jest jeszcze pełnym grafem wiedzy enterprise.

ADR V2 jest zaakceptowany. Addytywny Etap A i idempotentny backfill Etapu B zostały wdrożone na produkcji po osobnych backupach 27 lipca 2026 r. Zapis utworzył 19 819 evidences, sklasyfikował 101 tematów, oznaczył 2 176 głównych membershipów i znormalizował 3 199 kierunków. Osobny preview po zapisie ma zerowy delta. Zamrożono również gold set pilota „Zawracanie”: 72 pytania, 394 ocenione pary semantyczne i 14 technicznych negatywów kolizji ID. Drugi pass wykonano jako jawny `assisted_self_review` po rezygnacji właściciela z review człowieka; wynik ma 337 pozytywów, 57 negatywów, 0 pending i 7/7 typów. Zbudowano też pierwszy niepubliczny snapshot shadow dla 72 pytań: 1 087 linków, 15–19 wychodzących i 7–19 przychodzących na pytanie. Rdzeń `score >= 0.30` osiąga 97,74% precision w gold secie, a hubowy fallback pozostaje jawnie nieocenionym szerszym kontekstem. Produkcyjny adapter zapisał niepubliczny run V2 nr 1 dla 29 źródeł core i 435 rekomendacji, ze statusem `validated` i `published_at=null`; drugi zapis miał zerowy delta. Następnie aktywowano rollout nr 1 w `mode=shadow` z ekspozycją 0% oraz obie flagi odczytu. Audyt ma status `ok`, a publiczny selektor nadal renderuje wyłącznie V1.

28 lipca 2026 wdrożono chroniony panel administratora V1 ↔ V2 oraz cykliczny monitor quality gate. Następnie po świeżym backupie zaimportowano skorygowany, idempotentny run V2 nr 2 dla `secondary:zawracanie` (29 źródeł core, 435 rekomendacji, dokładnie 15 na źródło) i atomowo zastąpiono nim poprzedni rollout `shadow` o ekspozycji 0%. Audyt i monitor runu nr 2 mają `status=ok`, po 0 błędów i ostrzeżeń; publiczny SSR nadal renderuje tylko V1. Podpisany preview pytania 352 potwierdza V2=15 oraz `noindex,nofollow,noarchive`. Zakończony P0 audyt 10 reprezentatywnych pytań potwierdził zgodność medium, treści i podstaw prawnych; nie wykrył zmiany wymagającej wycofania runu. Szczegóły: `docs/SEO-RELATION-ZAWRACANIE-LEGAL-VERIFICATION-2026-07-28.md`.

Największe ograniczenia to:

- publiczny V1 nadal korzysta z jednego przypisania tematycznego na pytanie,
- tylko dwa aktywne poziomy taksonomii,
- jeden dominujący typ relacji na parę bez jawnego modelu dowodów,
- duża liczba kandydatów bez kontroli redakcyjnej,
- fallback tematyczny sortowany technicznie, a nie pedagogicznie,
- brak produkcyjnych relacji temat–temat,
- istnieje walidowany snapshot V2, aktywny odczyt shadow, chroniony preview administratora i cykliczny monitoring; przed nami pozostają dedykowana analityka kliknięć oraz redakcyjny workflow różnic,
- bezpieczny mechanizm publicznego canary jest przygotowany lokalnie, ale nie został wdrożony i nie ma jeszcze zgody na ekspozycję użytkowników,
- proste, szablonowe treści hubów,
- pilot „Zawracanie” ma skorygowany run V2 nr 2, komponent podglądu niepublicznie i zakończony P0 self-review, ale nie ma ekspozycji dla użytkownika ani zgody na canary.

### Dokąd zmierzamy

Docelowo system ma działać jako kontrolowany graf wiedzy:

```text
macro hub
→ topic hub
→ subtopic hub
→ pytanie
→ podobne pytanie / kontrast / ta sama zasada / następny krok
```

Każde pytanie ma mieć jeden temat główny, tematy dodatkowe, dowody relacji i indywidualny, deterministyczny ranking.

## 5. Stan produkcyjny V1

### 5.1. Dane źródłowe

Import wdrożeniowy wykorzystał dokładnie:

- `question-catalog.jsonl` — katalog pytań i przypisania tematyczne,
- `relations.jsonl` — centralne pary relacji.

Stan źródła z 17 lipca 2026 r.:

| Metryka | Wartość |
|---|---:|
| rekordy katalogu | 2 180 |
| unikalne źródłowe ID | 2 176 |
| kolizyjne ID | 4 |
| wybrane pary relacji | 4 804 |
| kandydaci wyeksportowani do analizy | 17 919 |
| tematy główne | 13 |
| podtematy | 88 |

Kolizyjne ID:

- `2328`,
- `3540`,
- `3545`,
- `3623`.

### 5.2. Stan bazy produkcyjnej

Odczyt z 21 lipca 2026 r.:

| Element | Liczba | Status |
|---|---:|---|
| wszystkie relacje pytanie–pytanie | 6 877 | aktywne dane V1 |
| `editorial + verified` | 3 678 | publiczne |
| `graph + automatic` | 318 | publiczne |
| `graph + candidate` | 2 881 | niepubliczne do czasu akceptacji |
| przypisania pytanie–temat | 2 176 | pełne pokrycie publicznych wyjaśnień |
| wszystkie rekordy tematów | 101 | 13 głównych + 88 podrzędnych |
| indeksowalne huby | 55 | publiczne i obecne w sitemapie po odzyskaniu membershipów |
| relacje temat–temat | 0 | struktura tabeli istnieje, dane nie zostały zasilone |

### 5.3. Wynik importu i kwarantanny

| Zdarzenie | Liczba |
|---|---:|
| pary związane z kolizyjnymi ID | 35 |
| bezpiecznie odzyskane pary kolizyjne | 21 |
| sprzeczne pary pozostawione w kwarantannie | 14 |
| relacje z aliasem `pj360:*` odzyskane do oceny | 169 |
| relacje z brakującym końcem po odzyskaniu aliasów | 0 |
| aliasy `pj360:*` dopasowane treścią do katalogu | 46 |
| kolizyjne ID dopasowane treścią do właściwego wiersza | 4 |
| poprawnie utworzone członkostwa | 2 176 |

Żaden rekord nie został dopasowany wyłącznie po podobnym numerze. Alias albo kolizyjne ID jest odzyskiwane tylko wtedy, gdy znormalizowana treść produkcyjnego pytania jednoznacznie odpowiada jednemu wierszowi katalogu. W relacjach z kolizyjnym ID wymagamy dodatkowo zgodności podtematu zapisanego w parze z wybranym pytaniem.

### 5.4. Zachowanie publicznego komponentu

Obecna kolejność źródeł:

1. zweryfikowane relacje redakcyjne,
2. automatycznie opublikowane relacje grafowe,
3. pytania z tego samego podtematu,
4. pytania z tego samego tematu głównego,
5. kontrolowane tematy sąsiednie,
6. fallback kategorii egzaminacyjnej.

Aktualne reguły UI:

- minimum 15 unikalnych linków,
- wszystkie prawdziwe relacje bezpośrednie mogą przekroczyć minimum,
- desktop pokazuje pierwszych 15 linków,
- mobile pokazuje 8 linków oraz rozwinięcie kolejnych 7,
- pozycje dalsze niż 15 trafiają do kolejnego `<details>`,
- linki istnieją w początkowym SSR HTML,
- linki prowadzą do kanonicznych URL-i.

### 5.5. Przykład diagnostyczny — pytanie 99

Strona pytania 99 pokazuje 15 linków:

| Grupa | Liczba | Faktyczne źródło |
|---|---:|---|
| Najbliższe pytania | 8 | wcześniejsze relacje redakcyjne `verified` |
| Więcej z tego tematu | 5 | przypisanie „Tramwaje i przystanki” z importowanego katalogu |
| Rozszerz temat | 2 | szerszy temat „Przejazdy kolejowe i tramwaje” |

Wnioski:

- graf i taksonomia rzeczywiście wpływają na publiczną listę,
- kandydat grafowy nie jest automatycznie przedstawiany jako relacja bezpośrednia,
- szeroki fallback potrafi dostarczyć pytania mniej trafne niż ręcznie ocenione relacje,
- temat „Tramwaje i przystanki” ma 7 opublikowanych członkostw, dlatego pozostaje nieindeksowalny i strona pokazuje link do całej kategorii zamiast do huba.

### 5.6. Powtarzalny baseline grafu V1

Kod zawiera komendę tylko do odczytu:

```bash
php artisan seo:audit-question-relation-graph \
  --report=storage/app/seo-audits/question-relation-graph-v1.json \
  --fail-on-errors
```

Komenda nie aktualizuje relacji, tematów ani pytań. Opcja `--report` jest jedynym zapisem i tworzy raport JSON we wskazanej lokalizacji.

Raport obejmuje:

- liczebność grafu według źródła, statusu, typu i kierunku,
- stopnie relacji publicznych oraz wszystkich relacji,
- publiczne relacje prowadzące do niepublicznych pytań lub wyjaśnień,
- automatyczne relacje poniżej progu albo bez wspólnego podtematu,
- pokrycie pytaniami z tematem i relacją bezpośrednią,
- etap, na którym każda strona osiąga minimum linków: relacje bezpośrednie, podtemat, temat główny, temat sąsiedni albo kategoria,
- zgodność liczników hubów z publicznymi membershipami,
- kolejkę kandydatów według priorytetu,
- powtarzalność identycznych zestawów bezpośrednich rekomendacji,
- ograniczone próbki błędów i ostrzeżeń bez rozdmuchiwania pliku.

Przydatne warianty:

```bash
# pełny JSON na standardowym wyjściu
php artisan seo:audit-question-relation-graph --json

# większa liczba próbek diagnostycznych
php artisan seo:audit-question-relation-graph --sample-limit=100 --report=/tmp/question-graph.json
```

Raport produkcyjny został wygenerowany 21 lipca 2026 r. i zapisany jako:

```text
storage/app/seo-audits/question-relation-graph-v1-20260721-alias-recovered.json
```

Najważniejsze wyniki:

| Metryka | Wynik |
|---|---:|
| publiczne wyjaśnienia rozwiązywalne do pytania | 2 176 |
| pytania z membershipem | 2 176 |
| pytania bez membershipu | 0 |
| pytania z co najmniej jedną relacją bezpośrednią | 1 913 |
| pytania bez relacji bezpośredniej | 263 |
| minimum 15 osiągnięte relacjami bezpośrednimi | 29 |
| minimum 15 osiągnięte w podtemacie | 1 914 |
| minimum 15 osiągnięte w temacie głównym | 233 |
| wymagany fallback kategorii | 0 |
| identyczne zestawy co najmniej 3 rekomendacji | 1 grupa / 2 pytania |
| kandydaci do przeglądu | 2 881 |

Rozkład priorytetów kandydatów:

- wysoki: 810,
- średni: 799,
- niski: 1 272.

Pierwszy audyt wykrył jeden błąd blokujący: hub `znaki-sygnaly-ogolne` był oznaczony jako indeksowalny, ale miał 9 publicznych pytań przy progu 10, podczas gdy zapisany licznik wynosił 11. Ponadto 41 liczników tematów różniło się od faktycznej liczby publicznych membershipów.

Przyczyną było liczenie `question_count` i `is_indexable` ze wszystkich poprawnych wierszy katalogu, także dla 46 ID bez opublikowanego wyjaśnienia. Importer został poprawiony tak, aby liczyć wyłącznie rekordy rzeczywiście rozwiązane do opublikowanych wyjaśnień. Po backupie bazy wykonano idempotentny reimport tego samego źródła i ponowny audyt:

- 0 błędów integralności,
- 0 rozbieżności liczników tematów,
- 54 indeksowalne huby,
- 47 nieindeksowalnych tematów,
- cienki w tamtym momencie hub `znaki-sygnaly-ogolne` zwracał 404,
- sitemap tematów zawierała 54 URL-e i nie zawierała cienkiego huba,
- liczba oraz statusy 6 728 relacji nie zmieniły się na tym etapie.

Następnie odzyskano 46 jednoznacznych aliasów `pj360:*` i właściwe membershipy 4 kolizyjnych ID. Po tej operacji wszystkie 2 176 publiczne wyjaśnienia mają temat, żadne nie wymaga fallbacku kategorii, a hub `znaki-sygnaly-ogolne` osiągnął 11 rzeczywistych członkostw, ponownie spełnia próg indeksacji i znajduje się w sitemapie. Audyt końcowy ma 0 błędów integralności.

### 5.7. Baseline wydajności

Pomiar wykonano wewnętrznym żądaniem HTTP na produkcji po rozgrzaniu aplikacji, z licznikiem zapytań SQL.

| Widok | Zapytania SQL | Czas SQL | Czas żądania | Status |
|---|---:|---:|---:|---:|
| pytanie 99 przed batchingiem | 248 | 174–179 ms | 471–510 ms | 200 |
| pytanie 99 po batchingu | 58 | 97–163 ms | 330–547 ms | 200 |
| hub `znaki-sygnaly-ogolne` | 4 | 1,7–1,8 ms | 8,6 ms | 200 |
| hub `tramwaje-przystanki` | 1 | 0,5–0,6 ms | 3–10 ms | 404 zgodnie z polityką |

Batching zmniejszył liczbę zapytań strony pytania o 190, czyli o 76,6%, bez zmiany 15 linków SSR ani rozmiaru odpowiedzi. Pozostałe 58 zapytań obejmuje także całą treść pytania, media, podstawy prawne, audio, autora i pozostałe elementy strony, a nie wyłącznie komponent relacji.

## 6. Co zostało wdrożone

| Obszar | Status | Zakres |
|---|---|---|
| audyt dostarczonych danych | `DONE` | duplikaty, brakujące cele, typy, stopnie grafu i taksonomia |
| migracje grafu i tematów | `DONE` | relacje, tematy, membershipy i relacje tematów |
| importer JSONL | `DONE` | preview, zapis, walidacja, prompt-matched aliasy, kontrolowane kolizje, kwarantanna i idempotencja |
| ochrona relacji redakcyjnych | `DONE` | import grafu nie obniża statusu redakcyjnego |
| statusy publikacji | `DONE` | `verified`, `automatic`, `candidate`, `rejected` |
| publiczny selektor relacji | `DONE` | deterministyczna kolejność źródeł i deduplikacja |
| minimum 15 linków | `DONE` | podtemat, temat główny, sąsiedztwo, kategoria |
| SSR i canonicale | `DONE` | prawdziwe `<a href>` w HTML |
| responsywny komponent | `DONE` | 15 desktop, 8 + 7 mobile, dalsze relacje rozwijane |
| podstawowe huby tematyczne | `DONE` | stabilny URL, paginacja po 20, canonical i dane strukturalne |
| sitemap tematów | `DONE` | wyłącznie indeksowalne huby |
| panel relacji Filament | `PARTIAL` | status, typ, kolejność, powód, różnica i anchory |
| testy feature i importu | `DONE` | minimum linków, import, hub i sitemap |
| powtarzalny audyt grafu V1 | `DONE` | komenda, JSON, kontrola błędów, testy i raport z pełnych danych produkcyjnych |
| wyłącznik V1 | `DONE` | `QUESTION_RELATIONS_ENABLED=false` |
| addytywny fundament danych V2 | `DONE` | wdrożony na produkcji, bez zmiany odczytu V1 |
| idempotentny backfill V2 | `DONE` | zapis produkcyjny po backupie, post-write preview z zerowym delta i 0 blockerów |
| feature flagi per klaster i algorytm | `PARTIAL` | produkcyjnie działa `mode=shadow`; osobny, domyślnie wyłączony mechanizm publicznego canary jest przygotowany lokalnie, bez deployu i bez ekspozycji V2 |
| chroniony preview i monitoring shadow | `DONE` | panel Filament admina, komenda quality gate i harmonogram produkcyjny o 04:00 |
| canary konfigurator i monitoring | `PARTIAL` | lokalny konfigurator z jawnym potwierdzeniem, stabilną kohortą i monitorem; nie wdrożono na produkcję |

## 7. Obecny przepływ danych

```text
question-catalog.jsonl + relations.jsonl
→ questions:import-seo-relations
→ question_seo_topics
→ question_seo_topic_memberships
→ question_relations
→ PublicQuestionRelationsService
→ PublicQuestionDatabaseController
→ related-question-groups.blade.php
→ SSR HTML i kanoniczne linki
```

Oddzielny przepływ hubów:

```text
question_seo_topics
→ PublicQuestionTopicController
→ paginowana lista pytań
→ canonical + BreadcrumbList + ItemList
→ sitemap tematów
```

## 8. Luki względem architektury enterprise

| Wymaganie docelowe | Stan V1 | Luka | Priorytet |
|---|---|---|---|
| macro → topic → subtopic | dwa aktywne poziomy | potrzebny trzeci poziom i reguły publikacji | P0 |
| jeden temat główny i wiele dodatkowych | jedno membership na pytanie | migracja modelu membership | P0 |
| 100–200 kandydatów na pytanie | ograniczony zbiór kandydatów | generator V2 i precomputing | P1 |
| 24–40 linków w bogatych klastrach | minimum 15 | selekcja zależna od jakości, bez sztucznej kwoty | P1 |
| 4–6 nazwanych bloków | zwykle 2–5 możliwych, często 3 | ranking i kompletność typów | P1 |
| relacje z przepisami, znakami i manewrami | część dowodów w metadanych | jawny model encji/dowodów | P0 |
| indywidualny ranking per pytanie | bezpośrednie relacje + techniczny fallback | ranking V2 i różnorodność | P0 |
| sterowanie tematem głównym | brak w panelu | panel topic membership | P0 |
| przypinanie i wykluczanie automatyki | częściowa zmiana kolejności/statusu | jawne `pinned` i `excluded` | P1 |
| unikalne treści edukacyjne hubów | opis szablonowy | workflow redakcyjny | P0 |
| subhuby i powiązane huby | tabela istnieje, 0 relacji | zasilenie i UI | P1 |
| breadcrumbs tematyczne na pytaniu | breadcrumb kategorii | temat główny + flaga | P1 |
| algorytm wersjonowany end-to-end | pole `version` w relacji | wersja generatora, rankingu i snapshotu | P1 |
| monitoring jakości grafu | raport importu i testy | cykliczny audyt produkcyjny | P0 |
| analityka kliknięć relacji | brak zdarzeń grafowych V1 | eventy i dashboard | P1 |
| rollout jednego klastra | V1 działa globalnie | osobna flaga pilota V2 | P0 |

## 9. Decyzje architektoniczne dla V2

Poniższe decyzje są kierunkiem do potwierdzenia w ADR przed migracją.

### 9.1. Rozszerzamy istniejące tabele

Nie tworzymy drugich tabel `topics_v2` ani `related_questions_v2`.

Preferowane rozszerzenie:

- `question_seo_topics` obsługuje dowolną głębokość przez `parent_id`,
- `question_seo_topic_memberships` obsługuje wiele tematów na pytanie,
- `question_relations` pozostaje centralnym źródłem par pytanie–pytanie,
- dowody relacji mogą zostać zapisane w metadanych albo w jawnej tabeli encji, jeżeli audyt wykaże potrzebę zapytań relacyjnych.

### 9.2. Jeden dominujący typ pedagogiczny, wiele dowodów

Para pytań powinna mieć jeden główny typ używany do prezentacji, np.:

- ta sama zasada,
- kontrast,
- często mylone,
- warunek wstępny,
- następny krok,
- rozszerzenie,
- relacja redakcyjna.

Znaki, przepisy, manewry, miejsca i uczestnicy powinny być dowodami relacji. Jedna para może mieć wiele takich dowodów.

### 9.3. Liczba linków wynika z jakości

Reguła operacyjna:

- minimum: 15 sensownych linków,
- cel dla bogatego klastra: 20–32,
- do 40 tylko wtedy, gdy kolejne pozycje zachowują trafność i różnorodność,
- brak obowiązku wypełniania listy przypadkowymi pytaniami,
- 100–200 kandydatów może być przechowywane bez publicznego renderowania.

### 9.4. V1 pozostaje rollbackiem

V2 powinno działać za osobnymi flagami, np.:

```text
SEO_RELATION_SCORING_V2_ENABLED
SEO_RELATION_SCORING_V2_CLUSTERS
SEO_TOPIC_BREADCRUMBS_ENABLED
SEO_TOPIC_HUBS_V2_ENABLED
SEO_RELATION_ANALYTICS_ENABLED
```

Wyłączenie V2 ma przywracać obecny selektor V1 bez usuwania danych.

## 10. Roadmapa etapowa

Czasy są szacunkami roboczymi, nie zobowiązaniem terminowym. Każda faza kończy się decyzją `go / poprawki / stop`.

### Faza 0 — zamknięcie obrazu V1

Status: `DONE`
Szacunek: 1–2 dni

Zakres:

- [x] zapisać stan produkcyjnych tabel i liczników,
- [x] udokumentować rzeczywisty przepływ danych,
- [x] porównać produkcję z artefaktami źródłowymi,
- [x] wskazać przypadek diagnostyczny pytania 99,
- [x] wygenerować pełny raport inbound/outbound dla wszystkich pytań,
- [x] policzyć powtarzalność identycznych zestawów rekomendacji,
- [x] zmierzyć czasy zapytań i liczbę zapytań SQL dla strony pytania i huba.

Artefakty:

- niniejsza roadmapa,
- raport bazowy grafu V1,
- lista problemów danych do naprawy.

Kryterium zakończenia:

- znamy baseline dla jakości, pokrycia i wydajności przed V2.

### Faza 1 — audyt architektury i ADR V2

Status: `DONE — ADR zaakceptowany 2026-07-21`

Szacunek: 3–5 dni

Zakres:

- [x] zaprojektować model macro/topic/subtopic na istniejącym `parent_id`,
- [x] zaprojektować wiele membershipów i oznaczenie tematu głównego,
- [x] zdecydować: osobna tabela dowodów z JSON-em tylko na niestabilne szczegóły,
- [x] zdefiniować dominujące typy relacji,
- [x] zdefiniować scoring, tie-breakery i limity różnorodności,
- [x] zaprojektować wersjonowanie generatora, niezmienne runy i atomową publikację rankingu,
- [x] zaprojektować flagi, deterministyczny canary per strona i rollback,
- [x] opisać migrację addytywną bez utraty V1,
- [x] zaakceptować ADR przez Engineering, SEO/redakcję i właściciela produktu.

Artefakty:

- `ADR-SEO-RELATION-GRAPH-V2.md` — `ACCEPTED`,
- projekt migracji — opisany w ADR,
- kontrakt danych generatora V2 — opisany w ADR,
- plan rollbacku — opisany w ADR.

Kryterium zakończenia:

- zatwierdzony model danych bez równoległego systemu tematów i relacji.

### Faza 2 — przygotowanie danych i próbki referencyjnej

Status: `DONE — GOLD SET FROZEN, ASSISTED SELF REVIEW, HUMAN REVIEW WAIVED`
Szacunek: 5–10 dni, zależnie od dostępności redakcji

Zakres:

- [x] rozstrzygnąć membershipy 4 kolizyjnych ID i odzyskać 21 zgodnych relacji,
- [x] wyjaśnić 46 brakujących rekordów katalogowych jako jednoznaczne aliasy `pj360:*`,
- [x] zapisać 14 sprzecznych par kolizyjnych jako techniczne negatywy mapowania encji, wyłączone z metryk semantycznych,
- [x] utworzyć deterministyczny pakiet 394 par dla 72 pytań pilota wraz z manifestem SHA-256,
- [x] opisać instrukcję redakcyjną i kryteria ukończenia gold setu,
- [x] dodać walidator decyzji CSV, chronionych pól, kompletności typów i idempotentnego zamrożenia gold setu,
- [x] wykonać pierwszą ocenę wspomaganą 91 kandydatur wysokiego priorytetu: 65 pozytywnych, 26 negatywnych, 0 błędów walidacji partial,
- [x] wykonać pierwszą ocenę wspomaganą 99 kandydatur średniego priorytetu: 67 pozytywnych, 32 negatywne, 0 błędów walidacji partial,
- [x] wykonać pierwszą ocenę wspomaganą 204 kandydatur niskiego priorytetu: 202 pozytywne, 2 negatywne, 0 błędów pełnej walidacji preview,
- [x] jawnie odstąpić od niezależnej drugiej oceny 394 par decyzją właściciela i zapisać `human_review_waived=true`,
- [x] wykonać drugi pass AI dla 91 kandydatur wysokiego priorytetu,
- [x] wykonać drugi pass AI dla 99 kandydatur średniego priorytetu,
- [x] wykonać drugi pass AI dla 204 kandydatur niskiego priorytetu,
- [x] przejrzeć kandydatów o priorytecie wysokim i średnim w pierwszym przebiegu wspomaganym,
- [x] ukończyć pierwszy przebieg wspomagany wszystkich 394 kandydatur,
- [x] utworzyć gold set oceniony w trybie `assisted_self_review`: 337 pozytywów, 57 negatywów i 14 technicznych negatywów,
- [x] oznaczyć typ i zachować dowody dla każdej pary gold setu,
- [x] zdefiniować negatywne przykłady relacji,
- [ ] skalibrować progi publikacji automatycznej,
- [x] wykonać wspomagany audyt spójności prawnej i pedagogicznej próbki; bez twierdzenia o review człowieka.

Minimalny gold set:

- co najmniej 30–50 pytań,
- co najmniej 300–500 ocenionych par,
- przykłady prawidłowych i błędnych relacji każdego obsługiwanego typu,
- osobna ocena kontrastów i „nie pomyl z”.

Kryterium zakończenia:

- scoring V2 można mierzyć względem zamrożonej prawdy referencyjnej z jawną polityką `assisted_self_review`.

### Faza 3 — pilot „Zawracanie”

Status: `PRODUCTION SHADOW + INTERNAL PREVIEW/MONITORING ACTIVE — PUBLIC ROLLOUT OFF`
Szacunek: 7–15 dni

Zakres pilota:

- jeden topic hub „Zawracanie”,
- 5–7 subhubów, jeśli dane rzeczywiście uzasadniają ich odrębność,
- istniejące pytania dotyczące zawracania,
- relacje ze znakami, przepisami, manewrami, miejscami i pułapkami,
- 100–150 przechowywanych kandydatów dla najważniejszych pytań,
- minimum 15 publicznych linków; cel 20–32 przy zachowaniu jakości,
- 4–6 bloków tylko tam, gdzie istnieją wartościowe relacje,
- unikalne treści edukacyjne hubu i subhubów,
- tematyczne breadcrumbs,
- paginacja, canonicale, sitemap i monitoring,
- panel redakcyjny dla tematów i relacji pilota,
- dedykowana feature flaga klastra.

Baseline scoringu względem zamrożonego gold setu:

- precision kandydatur: `85,53%`,
- precision@10 micro dla dostępnych pozycji: `88,17%`,
- precision@10 macro dla 44 pytań z co najmniej 10 kandydatami: `90,00%`,
- tylko `44/72` pytania mają minimum 10 kandydatów, `2/72` mają minimum 20,
- mediana to 12 kandydatów, więc sam bezpośredni ranking nie zapewnia 15 linków,
- zgodność proponowanego typu z typem gold wynosi tylko `25,52%`,
- `score >= 0.30` daje `97,74%` precision i `77,15%` recall wewnątrz próbki,
- wynik uzasadnia dwuwarstwowy shadow: precyzyjny rdzeń oraz dopełnienie z
  właściwego huba/subhuba; proponowane typy wymagają osobnego modelu.

Proponowane subhuby do weryfikacji z danymi:

- zakazy zawracania,
- zawracanie na skrzyżowaniach,
- zawracanie a znaki,
- zawracanie a sygnalizacja,
- wybór pasa i tor jazdy,
- pierwszeństwo podczas zawracania,
- najczęstsze pułapki egzaminacyjne.

Nie tworzymy subhuba, jeżeli:

- ma zbyt mało pytań,
- nie odpowiada na odrębną intencję,
- nie ma unikalnej treści,
- powiela inny hub.

Kryterium zakończenia:

- pilot przechodzi pełną walidację techniczną, redakcyjną, SEO i produktową.

### Faza 4 — implementacja i QA V2

Status: `PARTIAL — shadow, preview i lokalny canary guard gotowe; pozostałe elementy TODO`
Szacunek: 5–10 dni po zatwierdzeniu ADR

Zakres:

- [ ] migracja wielu membershipów,
- [ ] temat główny i tematy dodatkowe,
- [ ] generator kandydatów V2,
- [ ] ranking i różnorodność,
- [ ] precomputing lub cache wyników,
- [ ] rozszerzenie panelu Filament,
- [ ] komponent 4–6 bloków,
- [ ] linki do hubów i encji,
- [ ] breadcrumbs tematyczne,
- [ ] monitoring i analityka,
- [ ] testy jednostkowe, integracyjne, E2E i SEO,
- [ ] pomiary wydajności przed i po.

Kryterium zakończenia:

- V2 jest gotowe do canary rollout dla klastra pilota bez wpływu na pozostałe pytania.

### Faza 5 — canary i decyzja o skalowaniu

Status: `PREPARED LOCALLY / PUBLIC OFF`
Szacunek obserwacji: minimum 2–4 tygodnie

Zakres:

- [ ] włączyć V2 wyłącznie dla „Zawracania”,
- [ ] monitorować błędy, indeksację i crawl,
- [ ] mierzyć CTR bloków i przejścia pytanie → pytanie/hub,
- [ ] porównać liczbę kolejnych odsłon i sesji nauki,
- [ ] sprawdzić duplicate related sets,
- [ ] przeprowadzić ręczny audyt SERP i Search Console,
- [ ] zebrać decyzje redakcyjne i poprawić scoring.

Decyzja końcowa:

- `GO` — rozpoczęcie rollout kolejnych klastrów,
- `IMPROVE` — korekta modelu i ponowny canary,
- `STOP` — wyłączenie flagi V2 i pozostawienie V1.

### Faza 6 — rollout klastrowy

Status: `TODO`

Preferowana kolejność po pilocie:

1. pierwszeństwo i skrzyżowania,
2. wyprzedzanie,
3. piesi i przejścia,
4. znaki i sygnały,
5. zatrzymanie i postój,
6. przejazdy kolejowe i tramwaje,
7. prędkość, odstęp i hamowanie,
8. pierwsza pomoc i wypadki,
9. stan techniczny pojazdu,
10. pozostałe klastry.

Każdy klaster przechodzi osobno:

```text
audyt danych
→ gold set
→ treści hubów
→ preview
→ QA
→ feature flag
→ monitoring
→ pełne włączenie
```

### Faza 7 — utrzymanie ciągłe

Status: `TODO / ONGOING`

Zakres:

- cykliczne przeliczenie kandydatów,
- kolejka redakcyjna,
- monitoring błędnych linków i canonicali,
- kontrola sierot i głębokości kliknięć,
- przegląd cienkich i pustych hubów,
- kontrola zmian zestawów rekomendacji,
- wersjonowanie algorytmu,
- raport miesięczny SEO i produktowy.

## 11. Backlog priorytetowy

### P0 — przed pilotem

- [x] `SEO-LINK-V2-001` — raport baseline grafu V1 wygenerowany na pełnych danych produkcyjnych,
- [x] `SEO-LINK-V2-002` — ADR modelu tematów i relacji V2 zaakceptowany 2026-07-21,
- [x] `SEO-LINK-V2-003` — addytywny schemat wielu membershipów z `is_primary`, quality gates i idempotentny backfill są na produkcji,
- [ ] `SEO-LINK-V2-004` — `IN PROGRESS`: tabela, model i 19 819 evidences V1 są na produkcji; pozostaje workflow redakcyjny,
- [x] `SEO-LINK-V2-005` — rozwiązano membershipy kolizyjnych i aliasowych ID; sprzeczne pary pozostają jawnie w kwarantannie,
- [x] `SEO-LINK-V2-006` — zamrożony gold set 394/394 w trybie `assisted_self_review`: 337 pozytywów, 57 negatywów, 7 rozstrzygniętych korekt, 0 błędów i 7/7 typów; człowiek został jawnie pominięty decyzją właściciela,
- [x] `SEO-LINK-V2-007` — wdrożono i aktywowano produkcyjnie dwie flagi, resolver oraz quality gates `mode=shadow`; rollout nr 1 wskazuje skorygowany run nr 2, ma ekspozycję 0%, a audyt V1 ↔ V2 ma 0 błędów,
- [x] `SEO-LINK-V2-008` — monitoring jakości shadow: komenda quality gate, raport JSON, czas audytu oraz harmonogram produkcyjny przy aktywnych flagach,
- [ ] `SEO-LINK-V2-009` — treść i struktura pilota „Zawracanie”. `IN PROGRESS`: P0 wykluczył 2 błędne relacje `direct` i zweryfikował próbkę prawną/medium; pozostają pełne dossier, zakresy i treści subhubów. Szczegóły: `docs/SEO-RELATION-ZAWRACANIE-EDITORIAL-AUDIT.md`.

### P1 — pilot i canary

- [x] `SEO-LINK-V2-010` — generator shadow i idempotentny adapter utworzyły na produkcji skorygowany, niepubliczny run `validated` nr 2: 29 źródeł core i 435 rekomendacji (181 `direct`, 254 `context`); rollout nr 1 wskazuje ten run w `mode=shadow` (0%, bez publicznego outputu),
- [x] `SEO-LINK-V2-011` — stabilne tie-breakery i balans inbound zostały zamrożone w runie; nie publikujemy jeszcze typów relacji,
- [ ] `SEO-LINK-V2-012` — precomputed ranking/cache,
- [ ] `SEO-LINK-V2-013` — panel tematów i głównego membershipu,
- [ ] `SEO-LINK-V2-014` — pin/exclude/reorder relacji,
- [x] `SEO-LINK-V2-015` — chroniony komponent preview V1 ↔ V2 w panelu Filament; nie renderuje V2 na stronie publicznej,
- [ ] `SEO-LINK-V2-016` — breadcrumbs tematyczne,
- [ ] `SEO-LINK-V2-017` — hub i subhuby „Zawracanie”,
- [ ] `SEO-LINK-V2-018` — zdarzenia analityczne,
- [ ] `SEO-LINK-V2-019` — `PARTIAL`: lokalny konfigurator, stabilna kohorta i monitor canary są gotowe; brak deployu, dashboardu i alertów produkcyjnych.

### P2 — skalowanie

- [ ] `SEO-LINK-V2-020` — relacje temat–temat,
- [ ] `SEO-LINK-V2-021` — linkowanie znak/przepis/manewr → pytanie,
- [ ] `SEO-LINK-V2-022` — raport click depth i inbound links,
- [ ] `SEO-LINK-V2-023` — workflow publikacji kolejnych klastrów,
- [ ] `SEO-LINK-V2-024` — miesięczny raport jakości grafu,
- [ ] `SEO-LINK-V2-025` — automatyczna regresja SEO dla całego klastra.

## 12. Kryteria akceptacji pilota

Pilot jest gotowy dopiero wtedy, gdy spełnia wszystkie poniższe warunki.

### Dane

- każde pytanie ma dokładnie jeden temat główny,
- dodatkowe tematy nie tworzą duplikatów membershipów,
- brak relacji do samego siebie,
- brak relacji do nieopublikowanych, `noindex`, 3xx i 4xx,
- relacje mają dominujący typ i konkretne dowody,
- kandydaci automatyczni są oddzieleni od zweryfikowanych.

### SEO

- wszystkie linki są zwykłymi `<a href>`,
- wszystkie linki wskazują canonical,
- huby mają unikalny H1, title, description i treść,
- breadcrumbs widoczne i JSON-LD są zgodne,
- paginacja jest crawlable,
- sitemap zawiera tylko indeksowalne strony zwracające 200,
- brak pustych i cienkich hubów.

### UX

- pierwsze relacje są najbardziej użyteczne,
- użytkownik rozumie nazwy bloków,
- powód relacji jest konkretny,
- cała treść pytania jest anchorem,
- mobile i desktop zachowują logiczną kolejność,
- rozwinięcie działa klawiaturą i ma poprawne stany dostępności.

### Redakcja

- można zmienić temat główny,
- można dodać temat dodatkowy,
- można zaakceptować lub odrzucić relację,
- można przypiąć i wykluczyć relację,
- można zmienić kolejność i uzasadnienie,
- każda decyzja ma autora i datę.

### Operacje

- istnieje feature flag i rollback,
- import preview nie zmienia bazy,
- ponowny import jest idempotentny,
- czas odpowiedzi i liczba zapytań nie pogarszają się poza przyjęty budżet,
- monitoring nie wykazuje sierot, błędnych canonicali ani linków do błędnych stron.

## 13. Metryki sukcesu

Baseline dla metryk oznaczonych `TBD` powstaje w Fazie 0.

### Jakość grafu

- precision@10 i precision@20 względem gold setu,
- odsetek zaakceptowanych kandydatów,
- odsetek odrzuconych relacji automatycznych,
- liczba pytań bez zweryfikowanej relacji,
- liczba relacji przychodzących per pytanie,
- udział relacji kontrastowych, tej samej zasady i często mylonych,
- duplicate related sets,
- stabilność rankingu między wersjami.

### SEO techniczne

- `orphan_indexable_pages = 0`,
- `links_to_3xx = 0`,
- `links_to_4xx = 0`,
- `links_to_noindex = 0`,
- `canonical_mismatch = 0`,
- `empty_hubs = 0`,
- `pagination_gaps = 0`,
- średnia i mediana click depth,
- liczba poprawnie indeksowanych hubów.

### Produkt

- CTR każdego bloku relacji,
- CTR pytanie → hub,
- CTR hub → pytanie,
- liczba kolejnych odsłon pytań w sesji,
- rozpoczęte sesje nauki po wejściu z SEO,
- różnica mobile/desktop,
- udział kliknięć według typu relacji.

### Wydajność

- czas generowania widoku,
- liczba zapytań SQL,
- cache hit rate,
- czas przebudowy rankingu,
- liczba błędów jobów generujących relacje.

## 14. Ryzyka i zabezpieczenia

| Ryzyko | Skutek | Zabezpieczenie |
|---|---|---|
| identyczne ogólne treści pytań zawyżają podobieństwo | błędne „bliźniacze” relacje | porównanie kontekstu, odpowiedzi, przepisu i encji |
| wymuszanie 24–40 linków | słabe lub przypadkowe rekomendacje | próg jakości ważniejszy od liczby |
| zbyt wiele typów relacji | chaos redakcyjny | dominujący typ + wiele dowodów |
| drugi system tematów | niespójność danych | rozszerzenie istniejących tabel |
| masowa publikacja kandydatów | pogorszenie UX i SEO | gold set, progi, canary i statusy |
| thin content hubów | ryzyko indeksacji małej wartości | progi, unikalna treść i ręczna akceptacja |
| zmiana URL-i | duplikaty i utrata sygnałów | zachowanie obecnych canonicali |
| niestabilny ranking | zmienne linkowanie i trudne debugowanie | wersja, snapshot i deterministyczny tie-breaker |
| koszt zapytań | wolniejsze strony | precomputing, eager loading i cache |
| brak redakcji | kolejka kandydatów nie maleje | priorytety, batch review i raport postępu |

## 15. Odpowiedzialności

### Engineering

- model danych i migracje,
- importer, scoring i cache,
- komponenty, huby i sitemap,
- feature flagi, testy i monitoring,
- wydajność oraz rollback.

### SEO / redakcja

- taksonomia i treści hubów,
- gold set,
- akceptacja typów i uzasadnień,
- decyzje `index/noindex`,
- kontrola jakości kandydatów.

### Product / analytics

- definicja zdarzeń,
- dashboard CTR i dalszej nauki,
- kryteria sukcesu canary,
- decyzja o rollout kolejnych klastrów.

## 16. Najbliższy rekomendowany sprint

Cel sprintu: przygotować bezpieczne wejście do pilota „Zawracanie”, bez zmiany publicznego V1.

Kolejność:

1. ~~`SEO-LINK-V2-001` — pełny baseline grafu V1.~~ `DONE`
2. ~~naprawić niespójny hub `znaki-sygnaly-ogolne` i regułę synchronizacji liczników tematów.~~ `DONE`
3. ~~przypisać lub świadomie sklasyfikować 50 pytań bez membershipu.~~ `DONE`
4. ~~`SEO-LINK-V2-002` — zaakceptować gotowy projekt ADR modelu danych V2.~~ `DONE`
5. ~~`SEO-LINK-V2-005` — plan rozwiązania kolizji i brakujących ID.~~ `DONE`
6. `SEO-LINK-V2-006` — `DONE`: wykonano drugi pass AI 394 par, scalono 7 korekt i zamrożono gold set; `human_review_waived=true`,
7. ~~zmierzyć bazowy scoring względem gold setu.~~ `DONE`: P@10 micro 88,17%, type accuracy 25,52%, pokrycie 10 linków 44/72,
8. ~~`SEO-LINK-V2-010` / `011` — niepubliczny snapshot shadow dla 29 źródeł core i 435 rekomendacji.~~ `DONE`: produkcyjny, skorygowany run nr 2 ma status `validated`; rollout shadow nadal ma 0%, a publiczny output pozostaje wyłączony,
9. ~~`SEO-LINK-V2-007` — wdrożenie i aktywacja feature flag shadow z ekspozycją 0%, następnie audyt V1 ↔ V2.~~ `DONE`: rollout nr 1 wskazuje run nr 2 jako `shadow` 0%, audyt ma 0 błędów, a V1 nie zmienił zestawu publicznego,
10. ~~`SEO-LINK-V2-008` — monitoring quality gate shadow.~~ `DONE`: komenda, raport i harmonogram produkcyjny zwracają po deployu 0 błędów.
11. ~~`SEO-LINK-V2-015` — preview V2.~~ `DONE`: panel administratora porównuje V1 ↔ V2, a podpisany, wygasający URL pozwala zobaczyć V2 dla jednego pytania na prawdziwej stronie bez globalnego outputu, indeksowania ani canary.
12. `SEO-LINK-V2-009` — audyt wszystkich pytań o zawracaniu i propozycja subhubów. `IN PROGRESS`: P0 jest zamknięte, a P1 obejmuje pozostałe źródła, cztery duże klastry i dwie mikrosekcje.
13. `SEO-LINK-V2-018` — zdarzenia analityczne dla przyszłego publicznego canary.
14. `SEO-LINK-V2-019` — wdrożyć tylko po odrębnej zgodzie lokalny mechanizm canary, wykonać backup, preview, monitor i obserwację; runbook: `docs/SEO-RELATION-V2-CANARY-RUNBOOK.md`.

Definition of Done sprintu:

- istnieje zatwierdzony ADR,
- istnieje lista pytań pilota i proponowana hierarchia,
- istnieje zamrożona próbka ocenionych relacji z jawnym trybem review,
- istnieje baseline jakości i wydajności V1,
- znany jest zakres migracji,
- żadne zachowanie produkcyjnego V1 nie zostało zmienione.

## 17. Rollback

### Rollback V2

1. wyłączyć flagę klastra albo scoringu V2,
2. wrócić do obecnego `PublicQuestionRelationsService`,
3. pozostawić nowe dane do analizy, ale nie używać ich publicznie,
4. nie cofać migracji destrukcyjnie na produkcji,
5. zachować relacje redakcyjne i obecne membershipy V1.

### Rollback V1

Awaryjny wyłącznik:

```env
QUESTION_RELATIONS_ENABLED=false
```

Po wyłączeniu aplikacja korzysta z wcześniejszego fallbacku pytań kategorii. Dane w tabelach pozostają nienaruszone.

## 18. Dziennik decyzji

| Data | Decyzja |
|---|---|
| 2026-07-18 | wdrożono V1 grafu relacji, tematów, hubów i komponentu SSR |
| 2026-07-18 | ustalono minimum 15 linków i brak limitu dla wartościowych relacji bezpośrednich |
| 2026-07-18 | relacje redakcyjne zachowują pierwszeństwo przed importem grafu |
| 2026-07-18 | kolizyjne ID i brakujące końce relacji trafiają do kwarantanny |
| 2026-07-21 | potwierdzono identyczność źródłowych JSONL z artefaktem użytym we wdrożeniu |
| 2026-07-21 | V1 uznano za fundament, a nie pełną realizację enterprise V2 |
| 2026-07-21 | rekomendowanym pilotem V2 pozostaje klaster „Zawracanie” |
| 2026-07-21 | dodano powtarzalny, tylko do odczytu audyt grafu V1 i testy integracyjne |
| 2026-07-21 | wygenerowano produkcyjny baseline V1 i zamknięto Fazę 0; wykryto 1 błąd indeksowalności, 50 pytań bez membershipu i 267 bez relacji bezpośredniej |
| 2026-07-21 | batching kart powiązanych pytań zmniejszył liczbę zapytań strony pytania 99 z 248 do 58 bez zmiany SSR linków |
| 2026-07-21 | poprawiono liczenie hubów na podstawie rozwiązywalnych membershipów; po reimporcie audyt ma 0 błędów i 0 rozbieżności liczników, a sitemap tematów zawiera 54 poprawne URL-e |
| 2026-07-21 | odzyskano 46 aliasów `pj360:*`, membershipy 4 kolizyjnych ID, 169 relacji aliasowych i 21 zgodnych relacji kolizyjnych; 14 sprzecznych par pozostało w kwarantannie |
| 2026-07-21 | pełne pokrycie tematami osiągnęło 2 176/2 176, fallback kategorii spadł do zera, a końcowy audyt grafu ma 0 błędów |
| 2026-07-21 | przygotowano ADR V2: rozszerzenie istniejącej taksonomii, wiele membershipów, osobne dowody, niezmienne runy rankingu, atomowa publikacja i rollout per klaster; dokument oczekuje na akceptację |
| 2026-07-21 | wykryto 3 199 wartości `symetryczna` i 3 678 `symmetric`; ADR wymaga ich normalizacji przed uruchomieniem generatora V2 |
| 2026-07-21 | zaakceptowano ADR `SEO-LINK-V2-002`; rozpoczęto addytywny Etap A z zachowaniem publicznego selektora V1 jako rollbacku |
| 2026-07-21 | zaimplementowano addytywny fundament V2: wiele membershipów, evidences, niezmienne runy rankingu, recommendations i rollout per klaster; migracja oraz rollback przeszły na SQLite i izolowanym PostgreSQL |
| 2026-07-27 | wdrożono addytywny fundament V2 po backupie; liczniki 101 tematów, 2 176 membershipów i 6 877 relacji nie zmieniły się, a publiczny V1 przeszedł smoke testy |
| 2026-07-27 | przygotowano idempotentny Etap B: domyślny preview bez mutacji, raport JSON, quality gates, transakcję z blokadą PostgreSQL i jawne `--write` wymagające osobnej decyzji |
| 2026-07-27 | produkcyjny preview Etapu B przeszedł 6 quality gates: planuje 101 klasyfikacji tematów, 2 176 głównych membershipów, normalizację 3 199 kierunków i 19 819 evidences; checksumy czterech tabel przed i po są identyczne, `--write` nie uruchomiono |
| 2026-07-27 | po świeżym i sprawdzonym backupie wykonano transakcyjny Etap B: 101 tematów, 2 176 głównych membershipów, 3 199 normalizacji i 19 819 evidences; post-write preview ma zerowy delta, 0 blockerów i nie utworzył żadnego rankingu, rekomendacji ani rolloutu V2 |
| 2026-07-27 | przygotowano idempotentny pakiet redakcyjny pilota „Zawracanie”: 29 pytań rdzeniowych, 43 kandydatów wspierających, 394 pary do ręcznej oceny i 14 technicznych negatywów kolizji ID; publiczny V1 pozostał bez zmian |
| 2026-07-27 | scalono trzy batche pierwszej oceny do kontrolowanego pakietu drugiego review: 394 puste decyzje drugiej osoby, wymuszony inny reviewer, jawne rozbieżności i obowiązkowa adjudykacja; freeze i publiczny rollout pozostają zablokowane |
| 2026-07-27 | dodano walidator ręcznych decyzji: chroni pola źródłowe i locked negatives, wymaga kompletnych etykiet, pokrycia typów i semantycznych negatywów oraz zamraża idempotentny JSONL z manifestem SHA-256 |
| 2026-07-27 | wykonano pierwszą ocenę wspomaganą 91 kandydatur wysokiego priorytetu: 65 pozytywnych, 26 negatywnych i 26 korekt typu; partial validation ma 0 błędów, wszystkie decyzje oczekują na niezależny drugi review |
| 2026-07-27 | wykonano pierwszą ocenę wspomaganą 99 kandydatur średniego priorytetu: 67 pozytywnych, 32 negatywne i 59 korekt typu; skumulowany wynik 190/394 pokrywa 7/7 typów, ma 0 błędów partial validation i nadal wymaga niezależnego drugiego review |
| 2026-07-27 | ukończono pierwszy przebieg wspomagany wszystkich 394 kandydatur: 334 pozytywne i 60 negatywnych; pełny preview przechodzi 0 błędów i 7/7 typów, ale nie wykonano `--write`, shadow ani rolloutu, ponieważ wszystkie decyzje oczekują na niezależny drugi review |
| 2026-07-27 | właściciel jawnie zrezygnował z review człowieka; wykonano oznaczony `assisted_self_review` wszystkich 394 par, skorygowano 7 niespójności art. 22 ust. 6 pkt 1 i zamrożono gold set: 337 pozytywów, 57 negatywów, 14 technicznych negatywów, 0 pending, 0 błędów i 7/7 typów; publiczny V1 bez zmian |
| 2026-07-27 | zmierzono bazowy scoring offline: precision kandydatur 85,53%, P@10 micro 88,17%, tylko 44/72 pytań z co najmniej 10 kandydatami i type accuracy 25,52%; próg 0,30 daje 97,74% precision, dlatego rekomendowany shadow ma łączyć precyzyjny rdzeń z hubowym dopełnieniem do 15 linków |
| 2026-07-27 | zbudowano deterministyczny ranking shadow bez użycia gold labeli podczas selekcji: 1 087 linków dla 72 pytań, 532 w rdzeniu scoringowym i 555 w hubowym fallbacku; każde pytanie ma 15–19 linków wychodzących i 7–19 przychodzących, rdzeń zachowuje 97,74% precision, a publiczny rollout pozostaje wyłączony |
| 2026-07-27 | zaimplementowano idempotentny adapter shadow → run V2: do klastra `secondary:zawracanie` importuje tylko 29 źródeł core i 435 rekomendacji, zapisuje co najwyżej status `validated`, nie tworzy rolloutu i domyślnie działa jako preview; lokalna pusta baza poprawnie zablokowała zapis, produkcyjny preview pozostaje następnym krokiem |
| 2026-07-27 | po świeżym backupie PostgreSQL zapisano produkcyjny run V2 nr 1 dla `secondary:zawracanie`: `validated`, `published_at=null`, 29 źródeł core i 435 rekomendacji (184 direct, 251 same_subtopic); powtórny zapis miał zerowy delta, rolloutów nadal jest 0, a health/smoke i endpointy publiczne są OK |
| 2026-07-27 | zaimplementowano lokalnie niepubliczny odczyt V2 shadow: globalna flaga V2, flaga shadow, `mode=shadow` z ekspozycją 0%, resolver snapshotu, idempotentny konfigurator i audyt V1 ↔ V2; publiczny renderer V1 nie został zmieniony |
| 2026-07-27 | wdrożono i aktywowano produkcyjny shadow dla `secondary:zawracanie`: po świeżym backupie utworzono rollout nr 1 (`mode=shadow`, 0%), włączono obie flagi i ponowiono audyt; 29 źródeł oraz 435 linków V1/V2 dało 0 błędów, zestaw V1 pytania 352 pozostał identyczny, a SSR nadal renderuje wyłącznie V1 |
| 2026-07-28 | wdrożono chroniony panel `admin/relacje-v2-shadow` i produkcyjny monitor shadow: 1 rollout, 0 błędów i 0 ostrzeżeń; preview pytania 352 zwrócił V1=15/V2=15 (7 wspólnych), niezalogowany użytkownik otrzymuje przekierowanie do `/admin/login`, a SSR publiczny nadal nie zawiera V2 |
| 2026-07-28 | po backupie zaimportowano skorygowany run V2 nr 2 z artefaktu `zawracanie-v2-editorial-exclusions`: 29 źródeł, 435 rekomendacji (181 `direct`, 254 `context`); rollout nr 1 pozostał `shadow` 0%, a audyt i monitor zwróciły 0 błędów i 0 ostrzeżeń |
| 2026-07-28 | zamknięto P0 self-review 10 reprezentatywnych pytań „Zawracanie”: medium, treść i podstawy prawne są zgodne; pytanie 1433 pozostaje wyłącznie przykładem warunkowym, a publiczny canary nie został włączony |
| 2026-07-28 | przygotowano lokalnie, domyślnie wyłączony mechanizm canary: dwa wyłączniki, stabilną kohortę, jawny konfigurator i monitor; bez deployu oraz bez zmiany produkcyjnego HTML |
