# Traffic Sign Enterprise Schema Plan

Data aktualizacji: 2026-06-17  
Branch roboczy: `codex/enterprise-traffic-sign-schema`

## 1. Cel

Przeniesc publiczne strony znakow drogowych z legacy list wielu osobnych JSON-LD do jednego spojnego `@graph`, tak jak zrobilismy dla publicznej bazy pytan.

Zakres tego etapu:

- `/znaki-drogowe`,
- `/znaki-drogowe/{kategoria}`,
- `/znaki-drogowe/{znak}`.

Poza zakresem tego etapu:

- produkt nauki `/nauka`,
- sesje nauki, egzamin, progres i API nauki,
- refaktor widokow publicznych,
- migracje danych,
- przebudowa stron autorow, kontaktu, metodologii i supporting pages na nowy graph.

## 2. Guardraile

- Nie zmieniamy plikow dzialu nauki, chyba ze testy pokaza regresje bezposrednio wywolana schema.
- Nie oslabiamy istniejacych testow nauki, sesji ani publicznych stron znakow.
- Schema opisuje tylko realna publiczna zawartosc strony.
- Nie tworzymy sztucznego typu `TrafficSign`; uzywamy oficjalnych typow schema.org.
- Konkretny znak ma stabilne `@id` oparte o wewnetrzny identyfikator rekordu, a publiczny `url` dalej uzywa kanonicznego sluga.

## 3. Model docelowy graphu

### Stale encje

- `Organization` - `PrawkoNaRaz`,
- `WebSite`,
- `BreadcrumbList`.

### Hub znakow

`/znaki-drogowe` renderuje jeden JSON-LD:

- `CollectionPage`,
- `DefinedTermSet` jako baza znakow drogowych,
- `ItemList` kategorii,
- opcjonalny `ItemList` ostatnio aktualizowanych znakow,
- `DefinedTerm` dla kazdej widocznej kategorii,
- `DefinedTerm` dla widocznych znakow z listy "Ostatnio aktualizowane".

### Kategoria znakow

`/znaki-drogowe/{kategoria}` renderuje jeden JSON-LD:

- `CollectionPage`,
- `DefinedTermSet`,
- `DefinedTerm` kategorii,
- `ItemList` znakow w kategorii,
- `DefinedTerm` dla kazdego widocznego znaku.

### Strona znaku

`/znaki-drogowe/{znak}` renderuje jeden JSON-LD:

- `WebPage`,
- `DefinedTermSet`,
- `DefinedTerm` kategorii,
- `DefinedTerm` konkretnego znaku,
- `Article`,
- `ImageObject`, jesli obraz istnieje publicznie,
- `FAQPage`, jesli znak ma widoczne FAQ,
- `CreativeWork` jako inline `citation`, jesli na stronie widoczna jest podstawa prawna z etykieta lub linkiem.

## 4. Status implementacji

Zrobione lokalnie:

- [x] Dodano identyfikatory schema dla znakow w `app/SEO/Schema/SchemaIds.php`.
- [x] Przepisano `TrafficSignSchemaService::hub()` na pojedynczy `@graph`.
- [x] Przepisano `TrafficSignSchemaService::category()` na pojedynczy `@graph`.
- [x] Przepisano `TrafficSignSchemaService::sign()` na pojedynczy `@graph`.
- [x] Podpieto hub pod realne kolekcje kategorii i ostatnio aktualizowanych znakow.
- [x] Zachowano kompatybilnosc legacy schema dla author/trust/supporting pages.
- [x] Dodano testy JSON-LD graph dla huba, kategorii i znaku.
- [x] Potwierdzono brak duplikatow `@id`.
- [x] Potwierdzono, ze dzial nauki znakow nadal przechodzi testy.

Do zrobienia pozniej:

- [ ] Przepisac author/trust/supporting pages znakow na ten sam graph builder.
- [ ] Dodac osobne node'y prawne `Legislation` dla znakow, ale tylko po podpieciu zweryfikowanych publicznych zrodel prawnych.
- [x] Uruchomic smoke produkcyjny po merge/deploy.
- [ ] Sprawdzic wybrane adresy w zewnetrznym validatorze, gdy beda dostepne po deployu.

## 5. Weryfikacja lokalna

Wykonane 2026-06-17:

```text
docker compose exec -T app php artisan test tests/Feature/Public/TrafficSignSeoInfrastructureTest.php
10 passed (171 assertions)

docker compose exec -T app php artisan test tests/Feature/Public/TrafficSignPagesTest.php
6 passed (36 assertions)

docker compose exec -T app php artisan test tests/Feature/TrafficSignLearningTest.php
21 passed (214 assertions)

docker compose exec -T app php artisan test tests/Feature/SessionPageTest.php
25 passed (442 assertions)

docker compose exec -T app php artisan test tests/Feature/ApiSessionTest.php
10 passed (142 assertions)

docker compose exec -T app php artisan test tests/Feature/StudySessionFlowTest.php
32 passed (706 assertions)
```

Uwaga operacyjna: laczny pakiet `SessionPageTest + StudySessionFlowTest + ApiSessionTest` przekraczal limit czasu jako jedna komenda w tym kontenerze, wiec zostal uruchomiony osobno per plik. Wszystkie pliki przeszly.

## 5.1 Deploy produkcyjny

Wykonane 2026-06-17 na `main`.

Commity:

- `92d1d3e` - `Implement enterprise schema graph for traffic signs`,
- `3156001` - `Merge enterprise traffic sign schema graph`.

Paczka produkcyjna:

```text
output/release-traffic-sign-schema-20260617115235.tar.gz
```

Zakres paczki runtime:

```text
app/Http/Controllers/TrafficSignHubController.php
app/SEO/Schema/SchemaIds.php
app/Support/TrafficSignSchemaService.php
```

Wynik deployu:

```text
DEPLOY_TRAFFIC_SIGN_SCHEMA_OK
ops:health-report: OK
ops:smoke-test: OK
seo:refresh-sitemaps: OK
seo:audit-sitemaps: OK
BACKUP_DIR=/tmp/prawkonaraz-traffic-sign-schema-backup-20260617095435
```

Smoke produkcyjny JSON-LD:

```text
https://prawkonaraz.pl/znaki-drogowe
200 OK, 1 JSON-LD script, @graph, 31 nodes, duplicate @id count=0
Typy: BreadcrumbList, CollectionPage, DefinedTerm, DefinedTermSet, ItemList, Organization, WebSite

https://prawkonaraz.pl/znaki-drogowe/kategorie/znaki-ostrzegawcze
200 OK, 1 JSON-LD script, @graph, 49 nodes, duplicate @id count=0
Typy: BreadcrumbList, CollectionPage, DefinedTerm, DefinedTermSet, ItemList, Organization, WebSite

https://prawkonaraz.pl/znaki-drogowe/a-7-ustap-pierwszenstwa
200 OK, 1 JSON-LD script, @graph, 11 nodes, duplicate @id count=0
Typy: Article, BreadcrumbList, DefinedTerm, DefinedTermSet, FAQPage, ImageObject, Organization, Person, WebPage, WebSite
```

## 6. Zrodla typow schema.org

Uzyte typy sa oparte o oficjalne definicje schema.org:

- `DefinedTerm`: https://schema.org/DefinedTerm
- `DefinedTermSet`: https://schema.org/DefinedTermSet
- `ItemList`: https://schema.org/ItemList
- `Article`: https://schema.org/Article
- `FAQPage`: https://schema.org/FAQPage
- `WebPage`: https://schema.org/WebPage

## 7. Kryteria gotowosci do merge

- [x] Jeden JSON-LD script dla huba znakow.
- [x] Jeden JSON-LD script dla kategorii znakow.
- [x] Jeden JSON-LD script dla strony znaku.
- [x] Kazdy script ma `@context=https://schema.org` i `@graph`.
- [x] Graph nie ma duplikatow `@id`.
- [x] Strona znaku ma `DefinedTerm` dla znaku i `Article` opisujacy znak.
- [x] Obraz nie trafia do schema, jesli asset nie istnieje publicznie.
- [x] FAQ trafia do graphu tylko przy realnych `faq_items`.
- [x] Podstawa prawna trafia jako `citation` tylko gdy ma realna etykiete albo URL.
- [x] Testy publicznych stron znakow przechodza.
- [x] Testy guardrail dla `/nauka` przechodza.
