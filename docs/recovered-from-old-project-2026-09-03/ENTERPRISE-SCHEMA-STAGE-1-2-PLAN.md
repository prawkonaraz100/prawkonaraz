# Enterprise Schema Refactor: Etap 1 + Etap 2

Status: etap 1 + etap 2 wdrozone na produkcje  
Branch roboczy: `codex/enterprise-schema-analysis`  
Data utworzenia: 2026-06-16  
Ostatnia aktualizacja: 2026-06-17  
Zakres: publiczna baza pytan, kategorie bazy pytan, pojedyncze strony pytan

## 1. Cel

Chcemy przejsc z obecnego modelu kilku lokalnych generatorow schema do spojnego systemu grafu wiedzy dla publicznej bazy pytan.

Po etapie 1 + 2 kazda strona z zakresu:

- `/oficjalna-baza-pytan-na-prawo-jazdy`,
- `/oficjalna-baza-pytan-na-prawo-jazdy/{categorySlug}`,
- `/pytanie/{externalId}/{slug}`,

ma emitowac jeden blok JSON-LD:

```json
{
  "@context": "https://schema.org",
  "@graph": []
}
```

Graf ma miec stabilne `@id`, wspolne encje bazowe i relacje, ktore pozwalaja odtworzyc strukture:

```text
PrawkoNaRaz
`-- Oficjalna baza pytan na prawo jazdy
    |-- Kategoria A
    |   |-- Pytanie 99
    |   `-- Pytanie ...
    `-- Kategoria B
```

## 2. Definicja poziomu enterprise dla tego etapu

Na tym etapie `enterprise` oznacza:

1. Jeden centralny sposob renderowania JSON-LD.
2. Jeden `@graph` na strone, bez wielu niezaleznych skryptow.
3. Stabilne i przewidywalne `@id` dla waznych encji.
4. Jedna encja `Organization` i jedna encja `WebSite` w calym serwisie.
5. Relacje miedzy encjami zamiast samych zagniezdzonych obiektow bez identyfikatorow.
6. Schema opisuje tylko to, co jest prawdziwe i widoczne lub realnie reprezentowane przez dane strony.
7. Testy kontraktowe pilnuja grafu, a nie tylko obecnosci fragmentu tekstu.

## 3. Czego nie robimy w etapie 1 + 2

To jest wazne, zeby nie rozszerzyc refaktoru poza bezpieczny zakres.

Nie robimy jeszcze:

- pelnego systemu `/entity/{type}/{id}` dla calego serwisu,
- przebudowy schema znakow drogowych,
- przebudowy schema stron prawnych `/przepisy`,
- relacji miedzy wszystkimi pytaniami typu `sameTopic` albo `relatedQuestion` w grafie,
- osobnego publicznego endpointu grafu wiedzy,
- automatycznej walidacji z zewnetrznym Schema Validator w CI,
- masowej zmiany contentu lub widocznych sekcji strony,
- zmian w dziale nauki, playerze nauki, sesjach nauki, progresie, odpowiedziach uzytkownika, trenerze pamieci ani API uzywanym przez nauke,
- zmian w danych `questions.explanation`, `correct_answer`, opcjach odpowiedzi, punktach, kolejnosci pytan w nauce ani logice walidacji odpowiedzi,
- usuwania, pomijania albo oslabiania istniejacych testow po to, zeby refaktor schema "przeszedl",
- `QAPage`, dopoki nie chcemy spelnic wszystkich wytycznych Google dla Q&A pages.

Etap 1 + 2 ma zbudowac fundament, na ktorym pozniej mozna bezpiecznie rozwijac P1/P2.

## 3.1 Twarde guardraile: dzial nauki i istniejace testy

Refaktor schema jest refaktorem publicznej warstwy SEO. Nie jest refaktorem produktu nauki.

Zasady bez wyjatkow:

1. Nie zmieniamy zachowania `/nauka`, `/sesja`, egzaminu, trenera pamieci, rankingow ani dashboardow nauki.
2. Nie zmieniamy payloadow API uzywanych przez frontend nauki, chyba ze osobny task dotyczy nauki i ma wlasne testy.
3. Nie zmieniamy logiki wyboru pytan, walidacji odpowiedzi, progresu, powtorek, punktow ani zapisu odpowiedzi.
4. Nie przenosimy publicznych tresci SEO do produktu nauki. `question_public_explanations` i schema sa warstwa publiczna; `/nauka` dalej korzysta ze swojego ustalonego kontraktu.
5. Nie usuwamy istniejacych testow i nie rozluzniamy asercji testow niezaleznych od schema.
6. Jesli test nauki, sesji, API lub progresu zacznie padac po refaktorze schema, traktujemy to jako regresje do naprawy, a nie jako test do aktualizacji.
7. Aktualizujemy tylko te testy, ktore bezposrednio sprawdzaja kontrakt structured data albo brand w publicznym schema.
8. Przed uznaniem etapu za gotowy uruchamiamy nie tylko testy publicznej bazy pytan, ale tez reprezentatywny zestaw testow nauki/sesji.

Minimalny zestaw regresyjny dla ochrony dzialu nauki:

```powershell
docker compose exec -T app php artisan test tests/Feature/SessionPageTest.php tests/Feature/StudySessionFlowTest.php tests/Feature/ApiSessionTest.php
```

Jesli czas wykonania bedzie zbyt dlugi dla kazdego malego commita, mozna uruchamiac go na bramce przed merge/deploy, ale nie wolno deployowac refaktoru schema bez tej kontroli.

## 4. Stan obecny

### 4.1 Produkcja

Sprawdzone 2026-06-16:

| URL | Obecny stan JSON-LD | Problem |
| --- | --- | --- |
| `/oficjalna-baza-pytan-na-prawo-jazdy` | 2 skrypty: `BreadcrumbList`, `CollectionPage` | brak jednego `@graph`, brak `Organization`, `WebSite`, `Dataset` |
| `/oficjalna-baza-pytan-na-prawo-jazdy/a` | 2 skrypty: `BreadcrumbList`, `CollectionPage` | brak jednego `@graph`, brak `Organization`, `WebSite`, `DefinedTerm` kategorii |
| `/pytanie/99/...` | 1 skrypt z `@graph` | najblizej celu, ale bez `LearningResource`, `Dataset`, `Category`, `Law/Legislation` |

### 4.2 Kod

Aktualne glowne miejsca:

- `resources/views/layouts/public-content.blade.php`
  - renderuje `structuredData` jako liste wielu skryptow JSON-LD,
  - nie ma centralnego renderera.
- `app/Support/PublicQuestionSchemaService.php`
  - hub i kategoria zwracaja liste osobnych schema,
  - strona pytania zwraca juz jeden `@graph`.
- `app/Support/PublicQuestionBreadcrumbs.php`
  - buduje `BreadcrumbList`.
- `app/Support/PublicQuestionSeoService.php`
  - meta/canonical/OG dla publicznych stron bazy pytan.
- `app/Http/Controllers/PublicQuestionDatabaseController.php`
  - przekazuje `structuredData` do widokow.

Powiazane dokumenty:

- `docs/QUESTION-DATABASE-SEO-MASTERPLAN.md`
- `docs/PUBLIC-QUESTION-EXPLANATION-LAYER-PLAN.md`
- `docs/LEGAL-BASIS-REPAIR-PLAN.md`
- `docs/MEDIA-SEO-IMPLEMENTATION-ROADMAP.md`

### 4.3 Testy, ktore juz istnieja

Istniejace testy stabilizuja obecny stan:

- `tests/Feature/PublicQuestionDatabasePageTest.php`
- `tests/Feature/Public/TrafficSignSeoInfrastructureTest.php`
- `tests/Feature/SessionPageTest.php`
- `tests/Feature/StudySessionFlowTest.php`
- `tests/Feature/ApiSessionTest.php`

Ostatnia weryfikacja przed utworzeniem tego planu:

```text
21 passed (302 assertions)
```

## 5. Decyzje architektoniczne

### 5.1 Jeden renderer

JSON-LD powinien byc renderowany przez jedna klase, a nie przez Blade `foreach`.

Docelowo:

```text
app/SEO/Schema/SchemaRenderer.php
```

Odpowiedzialnosc:

- przyjmuje gotowy graf jako tablice,
- usuwa puste wartosci tam, gdzie to bezpieczne,
- deduplikuje encje po `@id`,
- koduje JSON przez `json_encode`,
- zwraca jeden payload:

```php
[
    '@context' => 'https://schema.org',
    '@graph' => [...],
]
```

HTML nadal moze renderowac Blade, ale Blade nie powinien skladac logiki grafu.

### 5.2 Jedna warstwa ID

Docelowo:

```text
app/SEO/Schema/SchemaIds.php
```

Kontrakt ID dla etapu 1 + 2:

| Encja | Wzor `@id` |
| --- | --- |
| Organization | `https://prawkonaraz.pl/#organization` |
| WebSite | `https://prawkonaraz.pl/#website` |
| Hub WebPage | `https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy#webpage` |
| Dataset | `https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy#dataset` |
| Hub ItemList | `https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy#categories` |
| Category WebPage | `https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy/a#webpage` |
| Category DefinedTerm | `https://prawkonaraz.pl/entity/category/a` |
| Category ItemList | `https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy/a#questions` |
| Question WebPage | `https://prawkonaraz.pl/pytanie/99/slug#webpage` |
| Question schema | `https://prawkonaraz.pl/entity/question/{question_id}` albo przejsciowo `canonicalUrl#question` |
| Question LearningResource | `https://prawkonaraz.pl/pytanie/99/slug#learning-resource` |
| Breadcrumb | `{canonicalUrl}#breadcrumb` |
| VideoObject | `{canonicalUrl}#video-{displayExternalId}` |
| ImageObject | `{canonicalUrl}#image` |
| LegalUnit/Law | `https://prawkonaraz.pl/entity/law/{legal_unit_slug}` |

Decyzja do wdrozenia:

- dla `Organization`, `WebSite`, `Dataset`, `Category` stosujemy globalne stabilne ID,
- dla pytania docelowo preferujemy stabilne ID oparte o wewnetrzne `questions.id`, bo slug moze sie zmienic,
- jezeli w etapie 2 nie chcemy zmieniac od razu kontraktu testow, mozemy zrobic przejsciowy krok: zostawic `canonicalUrl#question` i dopiero w etapie 3 przejsc na `/entity/question/{id}`.

Rekomendacja: w etapie 2 przejsc od razu na stabilne `/entity/question/{id}`, ale zostawic URL strony w `url` oraz `mainEntityOfPage`.

### 5.3 Brand

Docelowy brand w schema:

```json
{
  "@id": "https://prawkonaraz.pl/#organization",
  "@type": "Organization",
  "name": "PrawkoNaRaz",
  "url": "https://prawkonaraz.pl"
}
```

Obecnie testy i konfiguracja miejscami oczekuja `prawkonaraz.pl`.

Do zrobienia:

- zmienic `CONTENT_ORGANIZATION_NAME` / fallback config na `PrawkoNaRaz`,
- utrzymac `url` jako `https://prawkonaraz.pl`,
- `legalName` zostawic wedlug realnej decyzji biznesowej; jesli nie mamy formalnej nazwy, nie udawac jej w schema,
- zaktualizowac testy publicznej bazy pytan.

### 5.4 SearchAction

`SearchAction` moze zostac jako `WebSite.potentialAction`, ale nie traktujemy go jako krytycznego rich result, bo Google wycofalo widoczny sitelinks search box.

Docelowy target:

```text
https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy?question={search_term_string}
```

### 5.5 FAQPage i QAPage

Nie wprowadzamy `QAPage` w etapie 1 + 2.

Powod:

- publiczna strona pytania jest edukacyjna i ma jedna poprawna odpowiedz,
- Google QAPage jest ryzykowne, jesli strona nie jest realnie systemem odpowiedzi uzytkownikow,
- mamy lepszy, bezpieczniejszy kierunek: `Question`, `Answer`, `LearningResource`, `Dataset`, `DefinedTerm`.

`FAQPage` nie jest priorytetem, bo Google wygasilo FAQ rich results. Mozemy zachowac FAQ tam, gdzie ma wartosc semantyczna, ale nie jako glowny element strategii.

## 6. Etap 1: centralny fundament schema

### 6.1 Cel etapu 1

Po etapie 1 mamy centralne elementy techniczne, ale jeszcze nie musimy miec pelnego grafu dla wszystkich stron.

### 6.2 Pliki do utworzenia

```text
app/SEO/Schema/SchemaIds.php
app/SEO/Schema/SchemaGraph.php
app/SEO/Schema/SchemaRenderer.php
app/SEO/Schema/Nodes/OrganizationSchema.php
app/SEO/Schema/Nodes/WebSiteSchema.php
app/SEO/Schema/Nodes/WebPageSchema.php
app/SEO/Schema/Nodes/BreadcrumbSchema.php
```

Minimalny wariant moze zaczac od mniejszej liczby klas:

```text
app/SEO/Schema/SchemaIds.php
app/SEO/Schema/SchemaRenderer.php
app/SEO/Schema/PublicQuestionSchemaBuilder.php
```

Rekomendacja: zaczac od wariantu umiarkowanego, czyli `SchemaIds`, `SchemaRenderer`, `SchemaGraph` i node classes dla encji globalnych. Nie tworzyc kilkunastu klas zanim kod zacznie realnie korzystac z abstrakcji.

### 6.3 Pliki do zmiany

```text
resources/views/layouts/public-content.blade.php
app/Support/PublicQuestionSchemaService.php
tests/Feature/PublicQuestionDatabasePageTest.php
config/content.php
.env.example
```

Opcjonalnie:

```text
app/Providers/AppServiceProvider.php
```

Tylko jesli potrzebne sa bindingi serwisow.

### 6.4 Kontrakt `SchemaRenderer`

Renderer musi:

- przyjmowac liste node'ow,
- zwracac jeden payload `@context` + `@graph`,
- usuwac puste node'y,
- nie generowac HTML w klasie PHP,
- nie ukrywac bledow JSON,
- deduplikowac encje po `@id`, jesli dwa buildery dodadza ta sama encje,
- zachowac kolejnosc czytelna dla debugowania:
  1. `Organization`
  2. `WebSite`
  3. `BreadcrumbList`
  4. `WebPage` / `CollectionPage`
  5. encje glowne strony
  6. media / prawo / elementy wspierajace

Blade layout moze nadal robic:

```blade
<script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
```

ale `structuredData` powinno byc pojedynczym payloadem, nie lista niezaleznych schema.

### 6.5 Kompatybilnosc przejsciowa

Na czas migracji layout moze obslugiwac oba formaty:

1. nowy payload:

```php
[
    '@context' => 'https://schema.org',
    '@graph' => [...],
]
```

2. stara lista:

```php
[
    ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList'],
    ['@context' => 'https://schema.org', '@type' => 'CollectionPage'],
]
```

Rekomendacja: migracja dla bazy pytan powinna od razu przejsc na nowy payload, ale layout moze byc odporny, zeby nie zepsuc znakow, cennika, strony glownej i przepisow.

### 6.6 Testy etapu 1

Dodac albo rozszerzyc testy:

- `public question hub renders exactly one json-ld graph`,
- `public question category renders exactly one json-ld graph`,
- `public question detail still renders exactly one json-ld graph`,
- `organization and website ids are stable across hub category question`,
- `schema graph has no duplicate @id values`,
- `schema graph can be json encoded and decoded`.

Zasada ochronna: nowe testy moga rozszerzac kontrakt publicznego schema, ale nie moga zastepowac ani usuwac istniejacych testow nauki, sesji, API i progresu.

Pomocniczy helper testowy:

```php
function publicJsonLdGraph(TestResponse $response): array
```

powinien:

- znalezc dokladnie jeden `<script type="application/ld+json">`,
- zdekodowac JSON,
- sprawdzic `@context`,
- zwrocic `@graph`.

### 6.7 Kryteria akceptacji etapu 1

- [x] Layout nie renderuje wielu skryptow dla stron objetych refaktorem.
- [x] `SchemaRenderer` istnieje i jest uzywany przez bazy pytan.
- [x] `Organization` ma `@id=https://prawkonaraz.pl/#organization`.
- [x] `WebSite` ma `@id=https://prawkonaraz.pl/#website`.
- [x] Nazwa organizacji w schema to `PrawkoNaRaz`.
- [x] Nie ma duplikatow `@id` w grafie.
- [x] Stare strony spoza zakresu nie sa popsute w reprezentatywnym tescie znakow drogowych.
- [x] Testy publicznej bazy pytan przechodza.
- [x] Reprezentatywne testy dzialu nauki/sesji przechodza.
- [x] Nie usunieto ani nie oslabiono istniejacych testow niezaleznych od schema.

## 7. Etap 2: enterprise graph dla bazy pytan

### 7.1 Cel etapu 2

Po etapie 2 publiczna baza pytan ma semantyczna strukture:

```text
Dataset -> Category -> Question
```

oraz strona pytania laczy tresc, odpowiedz, media, kategorie, temat i podstawe prawna tam, gdzie dane istnieja.

### 7.2 Hub bazy pytan

URL:

```text
/oficjalna-baza-pytan-na-prawo-jazdy
```

Wymagane node'y:

- `Organization`
- `WebSite`
- `BreadcrumbList`
- `CollectionPage`
- `Dataset`
- `ItemList`

Minimalny graf:

```text
Organization
WebSite -> publisher -> Organization
CollectionPage -> isPartOf -> WebSite
CollectionPage -> breadcrumb -> BreadcrumbList
CollectionPage -> mainEntity -> Dataset
Dataset -> creator -> Organization
Dataset -> hasPart -> Category DefinedTerm[]
ItemList -> itemListElement -> category URLs
```

Wazne pola `Dataset`:

- `@id`
- `@type=Dataset`
- `name=Oficjalna baza pytan na prawo jazdy`
- `description`
- `creator`
- `url`
- opcjonalnie `license`, jesli mamy realna decyzje,
- opcjonalnie `dateModified`, jesli mamy wiarygodne maksimum dat.

Do zrobienia:

- [x] Dodac node `Dataset`.
- [x] Dodac `ItemList` kategorii z `@id`.
- [x] Polaczyc `CollectionPage.mainEntity` z `Dataset`.
- [x] Polaczyc `Dataset.hasPart` z kategoriami.
- [x] Zachowac widoczne kategorie zgodnie z HTML.

### 7.3 Strona kategorii

URL:

```text
/oficjalna-baza-pytan-na-prawo-jazdy/{categorySlug}
```

Wymagane node'y:

- `Organization`
- `WebSite`
- `BreadcrumbList`
- `CollectionPage`
- `Dataset`
- `DefinedTerm` kategorii
- `ItemList` pytan

Minimalny graf:

```text
CollectionPage -> mainEntity -> Category DefinedTerm
Category DefinedTerm -> isPartOf -> Dataset
Category DefinedTerm -> hasPart -> Question entities z listy
ItemList -> itemListElement -> publiczne URL-e pytan
```

Wazne pola `DefinedTerm` kategorii:

- `@id=https://prawkonaraz.pl/entity/category/{slug}`
- `@type=DefinedTerm`
- `name=Prawo jazdy kategorii A` albo aktualna nazwa kategorii
- `termCode=A`
- `description`
- `url`
- `isPartOf` -> Dataset

Do zrobienia:

- [x] Dodac `DefinedTerm` dla kategorii.
- [x] Dodac relacje `isPartOf` do datasetu.
- [x] Dodac `ItemList` pytan jako osobny node z `@id`.
- [x] Lista pytan w schema musi odpowiadac pytaniom widocznym na danej stronie paginacji/filtru.
- [x] Nie publikowac w schema pytan ukrytych przez filtr.

### 7.4 Strona pytania

URL:

```text
/pytanie/{externalId}/{slug}
```

Wymagane node'y:

- `Organization`
- `WebSite`
- `BreadcrumbList`
- `WebPage` albo `CollectionPage`/`LearningResource` jako osobny node; rekomendacja: zostawic `WebPage`
- `Dataset`
- `DefinedTerm` kategorii
- `Question`
- `Answer`
- `LearningResource`
- `ImageObject`, jesli pytanie ma obraz/poster
- `VideoObject`, jesli pytanie ma film
- `Legislation` albo `DefinedTerm`/`CreativeWork` dla przepisu, jesli pytanie ma zweryfikowana podstawe prawna
- `DefinedTerm` tematu, jesli pytanie ma wiarygodny temat

Minimalny graf:

```text
WebPage -> isPartOf -> WebSite
WebPage -> breadcrumb -> BreadcrumbList
WebPage -> mainEntity -> Question
Question -> acceptedAnswer -> Answer
Question -> about -> Question entity / category / topic / law
Question -> isPartOf -> Category DefinedTerm
Category DefinedTerm -> isPartOf -> Dataset
LearningResource -> about -> Question
LearningResource -> isPartOf -> Dataset
VideoObject -> mainEntityOfPage -> WebPage
ImageObject -> mainEntityOfPage albo primaryImageOfPage -> WebPage
```

Wazne: `Answer.text` musi byc ta sama trescia, ktora widzi uzytkownik w sekcji `Wyjasnienie`.

Nie wolno:

- wrzucac draftow publicznych wyjasnien,
- wrzucac draftow podstaw prawnych,
- ukrywac w schema tekstu, ktorego nie ma na stronie,
- generowac `VideoObject`, jesli pytanie nie ma filmu,
- generowac `ImageObject`, jesli URL obrazu jest pusty albo niepubliczny,
- dodawac `QAPage` bez osobnej decyzji.

### 7.5 LearningResource

`LearningResource` jest kluczowy dla edukacyjnego charakteru pytan.

Proponowany node:

```json
{
  "@id": "https://prawkonaraz.pl/pytanie/99/slug#learning-resource",
  "@type": "LearningResource",
  "name": "Pytanie 99",
  "learningResourceType": "Practice Problem",
  "educationalLevel": "Prawo jazdy",
  "about": {
    "@id": "https://prawkonaraz.pl/entity/question/123"
  },
  "isPartOf": {
    "@id": "https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy#dataset"
  }
}
```

Do decyzji:

- czy `educationalLevel` ma byc `Prawo jazdy`, `Egzamin teoretyczny na prawo jazdy`, czy per kategoria,
- czy `learningResourceType` zostawiamy jako tekst `Practice Problem`, czy mapujemy na wewnetrzny slownik.

Rekomendacja: na start `Practice Problem` + `Prawo jazdy`.

### 7.6 Prawo i podstawa prawna

Mamy juz katalog przepisow i zweryfikowane `QuestionLegalReference`.

W etapie 2 dodajemy prawo tylko wtedy, gdy:

- relacja pytania jest `verified`,
- `verified_at` nie jest puste,
- `legalUnit` istnieje,
- `legalUnit.status=verified`,
- tresc przepisu albo jego etykieta jest widoczna na stronie.

Proponowany node:

```json
{
  "@id": "https://prawkonaraz.pl/entity/law/art-26-ust-6",
  "@type": "Legislation",
  "name": "Prawo o ruchu drogowym art. 26 ust. 6",
  "legislationIdentifier": "art. 26 ust. 6",
  "text": "Kierujacy pojazdem jest obowiazany...",
  "url": "https://..."
}
```

Relacja z pytaniem:

```json
{
  "@id": "https://prawkonaraz.pl/entity/question/123",
  "about": [
    { "@id": "https://prawkonaraz.pl/entity/law/art-26-ust-6" }
  ]
}
```

Do zrobienia:

- [x] Dodac mapping `LegalUnit -> Legislation`.
- [x] Dodac test, ze draft nie trafia do schema.
- [x] Dodac test, ze `official_excerpt` trafia do schema tylko gdy jest widoczny publicznie.
- [x] Dodac test, ze `public_note` nie jest wymagane.

### 7.7 Tematy

Mamy dwa typy tematow:

- `QuestionTopic` - produktowo-edukacyjny temat pytania,
- `LegalTopic` - temat prawno-redakcyjny.

W etapie 2 nie musimy budowac pelnej ontologii tematow. Wystarczy:

- dodac `QuestionTopic` jako `DefinedTerm`, jesli pytanie ma aktywny temat,
- dodac `LegalTopic` tylko wtedy, gdy jest przypisany i sensowny,
- nie wymuszac tematu prawnego, jesli go nie ma.

Do zrobienia:

- [x] Zdefiniowac ID tematu: `https://prawkonaraz.pl/entity/topic/{key}`.
- [x] Dodac `Question.about -> Topic`, tylko gdy temat istnieje.
- [x] Nie tworzyc sztucznego tematu `inne`, `brak`, `do uzupelnienia` w publicznym schema.

### 7.8 Media

Obecnie `VideoObject` dla pytania jest juz wdrozony i testowany.

Etap 2 ma dopiac:

- `ImageObject` dla obrazu lub postera,
- `WebPage.primaryImageOfPage -> ImageObject`,
- `Question.image -> ImageObject`, jesli obraz jest faktycznie zwiazany z pytaniem,
- `VideoObject.thumbnailUrl` zostaje jako URL tablicy, zgodnie z obecnym testem.

Do zrobienia:

- [x] Dodac `ImageObject` przy pytaniu z obrazem.
- [x] Dodac `ImageObject` przy pytaniu z filmem i posterem.
- [x] Nie dodawac `ImageObject`, jesli brak publicznego URL.
- [x] Zachowac obecny kontrakt `VideoObject`: `name`, `description`, `thumbnailUrl`, `uploadDate`, `contentUrl`.

## 8. Proponowana struktura kodu po etapie 2

```text
app/SEO/Schema/
|-- SchemaGraph.php
|-- SchemaIds.php
|-- SchemaRenderer.php
|-- PublicQuestionSchemaBuilder.php
`-- Nodes/
    |-- BreadcrumbSchema.php
    |-- DatasetSchema.php
    |-- ImageSchema.php
    |-- LegalUnitSchema.php
    |-- LearningResourceSchema.php
    |-- OrganizationSchema.php
    |-- QuestionSchema.php
    |-- WebPageSchema.php
    |-- WebSiteSchema.php
    `-- VideoSchema.php
```

Mozliwa migracja istniejacego serwisu:

```text
app/Support/PublicQuestionSchemaService.php
```

zostaje jako fasada kompatybilnosci i deleguje do:

```text
app/SEO/Schema/PublicQuestionSchemaBuilder.php
```

To ogranicza zmiany w kontrolerach.

## 9. Plan implementacji

### 9.1 Krok A: testy kontraktowe przed refaktorem

Najpierw dopisac testy opisujace stan docelowy.

Przed dopisaniem nowych testow trzeba zachowac istniejace testy jako baseline. Testy publicznego schema moga zmienic oczekiwany ksztalt JSON-LD, ale testy nauki/sesji/API nie sa czescia refaktoru i nie powinny wymagac zmian.

Plik:

```text
tests/Feature/PublicQuestionDatabasePageTest.php
```

Nowe testy:

- [x] hub ma dokladnie jeden JSON-LD script,
- [x] hub graph zawiera `Organization`, `WebSite`, `BreadcrumbList`, `CollectionPage`, `Dataset`, `ItemList`,
- [x] kategoria ma dokladnie jeden JSON-LD script,
- [x] kategoria graph zawiera `DefinedTerm` z `termCode`,
- [x] pytanie graph zawiera `LearningResource`,
- [x] pytanie z prawem zawiera node `Legislation`,
- [x] pytanie bez filmu nie zawiera `VideoObject`,
- [x] pytanie z obrazem zawiera `ImageObject`,
- [x] graf nie ma duplikatow `@id`,
- [x] `Organization.name=PrawkoNaRaz`.

### 9.2 Krok B: renderer i ID

Dodac:

- `SchemaIds`,
- `SchemaGraph`,
- `SchemaRenderer`.

Testy jednostkowe:

```text
tests/Unit/SEO/Schema/SchemaRendererTest.php
tests/Unit/SEO/Schema/SchemaIdsTest.php
```

Zakres testow:

- deduplikacja po `@id`,
- zachowanie node'ow bez `@id`, jesli sa celowo anonimowe,
- poprawny `@context`,
- brak pustego `@graph`,
- stabilne ID dla root, dataset, category, question.

### 9.3 Krok C: hub

Przerobic `PublicQuestionSchemaService::hub()`.

Akceptacja:

- jeden `@graph`,
- `Dataset`,
- `ItemList`,
- wspolne `Organization`/`WebSite`,
- testy przechodza.

### 9.4 Krok D: kategoria

Przerobic `PublicQuestionSchemaService::category()`.

Akceptacja:

- jeden `@graph`,
- `DefinedTerm` kategorii,
- `ItemList` pytan,
- relacja do `Dataset`,
- lista zgodna z widocznym HTML.

### 9.5 Krok E: pytanie

Przerobic `PublicQuestionSchemaService::question()`.

Akceptacja:

- zachowac obecne dzialajace node'y,
- dodac `Dataset`,
- dodac `DefinedTerm` kategorii,
- dodac `LearningResource`,
- dodac `ImageObject`,
- dodac `Legislation`, gdy istnieje zweryfikowana podstawa prawna,
- nie popsuc `VideoObject`.

### 9.6 Krok F: konfiguracja brandu

Zmiany:

- `config/content.php`,
- `.env.example`,
- test setup.

Akceptacja:

- `Organization.name=PrawkoNaRaz`,
- `WebSite.name=PrawkoNaRaz`,
- URL nadal `https://prawkonaraz.pl`.

### 9.7 Krok G: smoke i produkcyjna walidacja

Lokalnie:

```powershell
docker compose exec -T app php artisan test tests/Feature/PublicQuestionDatabasePageTest.php
docker compose exec -T app php artisan test tests/Feature/Public/TrafficSignSeoInfrastructureTest.php
docker compose exec -T app php artisan test tests/Feature/SessionPageTest.php tests/Feature/StudySessionFlowTest.php tests/Feature/ApiSessionTest.php
npm run build
```

Po deployu:

```powershell
curl.exe -I https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy
curl.exe -I https://prawkonaraz.pl/oficjalna-baza-pytan-na-prawo-jazdy/a
curl.exe -I https://prawkonaraz.pl/pytanie/99/czy-w-tej-sytuacji-masz-obowiazek-zatrzymac-pojazd
```

Oraz reczny odczyt JSON-LD:

- hub: jeden skrypt, `@graph`,
- kategoria: jeden skrypt, `@graph`,
- pytanie: jeden skrypt, `@graph`,
- brak duplikatow `@id`.

## 10. Checklist statusu

### 10.0 Ostatnia weryfikacja po implementacji etapu 1 + 2

Wykonane 2026-06-17 na branchu `codex/enterprise-schema-analysis`:

```text
docker compose exec -T app php artisan test tests/Feature/PublicQuestionDatabasePageTest.php
15 passed (371 assertions)

docker compose exec -T app php artisan test tests/Feature/Public/TrafficSignSeoInfrastructureTest.php
9 passed (117 assertions)

docker compose exec -T app php artisan test tests/Feature/SessionPageTest.php tests/Feature/StudySessionFlowTest.php tests/Feature/ApiSessionTest.php
67 passed (1290 assertions)

npm run build
passed
```

Wynik guardrailu: dzial nauki, sesje i API nauki przeszly bez zmian w testach i bez zmian w logice produktu nauki.

Deploy produkcyjny wykonany 2026-06-17 na `https://prawkonaraz.pl`.

Wynik deployu:

```text
DEPLOY_ENTERPRISE_SCHEMA_OK
ops:health-report: OK
ops:smoke-test: OK
seo:refresh-sitemaps: OK
seo:audit-sitemaps: OK
```

Smoke produkcyjny JSON-LD:

```text
/oficjalna-baza-pytan-na-prawo-jazdy
200 OK, 1 JSON-LD script, @graph, Organization.name=PrawkoNaRaz, Dataset/ItemList/DefinedTerm obecne

/oficjalna-baza-pytan-na-prawo-jazdy/a
200 OK, 1 JSON-LD script, @graph, Organization.name=PrawkoNaRaz, Dataset/DefinedTerm/ItemList obecne

/pytanie/99/czy-w-tej-sytuacji-masz-obowiazek-zatrzymac-pojazd
200 OK, 1 JSON-LD script, @graph, Organization.name=PrawkoNaRaz, Question/LearningResource/ImageObject/VideoObject/Legislation obecne
```

### 10.1 Zrobione przed tym refaktorem

- [x] Publiczne strony pytan sa server-side rendered.
- [x] Strona pojedynczego pytania ma juz jeden `@graph`.
- [x] Strona pojedynczego pytania ma `Organization`, `WebSite`, `BreadcrumbList`, `WebPage`, `Question`.
- [x] `acceptedAnswer.text` jest spojne z widoczna sekcja `Wyjasnienie`.
- [x] `VideoObject` istnieje tylko dla pytan z filmem.
- [x] Sekcja `Uzasadnienie prawne` ma pelny katalog PoRD i moze wyswietlac tresc przepisu.
- [x] `Opis prawny` przy podstawie prawnej jest opcjonalny.
- [x] Testy publicznej bazy pytan przechodza na obecnym kontrakcie.
- [x] Dzial nauki jest poza zakresem refaktoru schema i ma zostac chroniony przez osobny zestaw regresyjny.

### 10.2 Etap 1

- [x] Utworzyc `app/SEO/Schema`.
- [x] Dodac `SchemaIds`.
- [x] Dodac `SchemaGraph`.
- [x] Dodac `SchemaRenderer`.
- [x] Dodac builder dla `Organization` w publicznym schema bazy pytan.
- [x] Dodac builder dla `WebSite` w publicznym schema bazy pytan.
- [x] Dodac builder dla `BreadcrumbList` w publicznym schema bazy pytan.
- [x] Zmienic layout tak, zeby obslugiwal pojedynczy payload `@graph` i zachowal kompatybilnosc ze starymi listami schema.
- [x] Zmienic brand schema na `PrawkoNaRaz`.
- [x] Dodac testy jednego JSON-LD dla hub/category/question.
- [x] Uruchomic reprezentatywne testy nauki/sesji po zmianach Etapu 1.
- [x] Nie zmieniac plikow dzialu nauki, chyba ze testy wskaza konieczna naprawe regresji wywolanej schema.

### 10.3 Etap 2

- [x] Przepisac hub bazy pytan na nowy graph builder.
- [x] Dodac `Dataset` dla bazy pytan.
- [x] Dodac `ItemList` kategorii jako node z `@id`.
- [x] Przepisac strone kategorii na nowy graph builder.
- [x] Dodac `DefinedTerm` kategorii.
- [x] Dodac `ItemList` pytan jako node z `@id`.
- [x] Rozszerzyc strone pytania o `Dataset`.
- [x] Rozszerzyc strone pytania o `DefinedTerm` kategorii.
- [x] Rozszerzyc strone pytania o `LearningResource`.
- [x] Dodac `ImageObject`.
- [x] Dodac schema podstawy prawnej dla zweryfikowanych `QuestionLegalReference`.
- [x] Dodac opcjonalne `Topic` jako `DefinedTerm`, jesli dane istnieja.
- [x] Dodac test braku schema dla draftow.
- [x] Dodac test braku `VideoObject` bez filmu.
- [x] Dodac test braku `ImageObject` bez publicznego obrazu.
- [x] Uruchomic reprezentatywne testy nauki/sesji po zmianach Etapu 2.

## 11. Kryteria koncowe etapu 1 + 2

Projekt etapu 1 + 2 mozna uznac za zakonczony, gdy:

- [x] wszystkie 3 typy stron bazy pytan renderuja dokladnie jeden JSON-LD script,
- [x] kazdy script ma `@context=https://schema.org` i `@graph`,
- [x] graf nie ma duplikatow `@id`,
- [x] `Organization` i `WebSite` sa identyczne na hubie, kategorii i pytaniu,
- [x] `Organization.name=PrawkoNaRaz`,
- [x] hub zawiera `Dataset`,
- [x] kategoria zawiera `DefinedTerm`,
- [x] pytanie zawiera `LearningResource`,
- [x] pytanie z filmem zawiera `VideoObject`,
- [x] pytanie bez filmu nie zawiera `VideoObject`,
- [x] pytanie z publicznym obrazem/posterem zawiera `ImageObject`,
- [x] pytanie z verified podstawa prawna zawiera node prawny,
- [x] pytanie bez verified podstawy prawnej nie udaje node'a prawnego,
- [x] `acceptedAnswer.text` nadal odpowiada widocznej sekcji `Wyjasnienie`,
- [x] schema podstawy prawnej odpowiada widocznej sekcji `Uzasadnienie prawne`,
- [x] testy publicznej bazy pytan przechodza,
- [x] testy regresyjne dzialu nauki/sesji/API przechodza,
- [x] istniejace testy nie zostaly usuniete ani oslabione poza konieczna aktualizacja testow schema,
- [x] refaktor nie zmienia zachowania `/nauka`, sesji nauki, progresu ani API nauki,
- [x] smoke produkcyjny po deployu potwierdza `200 OK` i poprawny JSON-LD.

## 12. Ryzyka i zabezpieczenia

| Ryzyko | Skutek | Zabezpieczenie |
| --- | --- | --- |
| Zbyt duzy refaktor naraz | regresje SEO na wielu stronach | etap 1 + 2 ograniczone do bazy pytan |
| Wiele generatorow doda te sama encje | duplikaty `@id` | `SchemaGraph` deduplikuje i testuje duplikaty |
| Schema opisze tekst niewidoczny publicznie | naruszenie zasad Google | testy zgodnosci HTML vs schema |
| Brand zmieni sie niespojnie | niespojny Knowledge Graph | jedna konfiguracja `content.organization.name` |
| Puste tematy/prawo w schema | sztuczne encje | dodawac tylko verified/realne dane |
| `QAPage` zostanie uzyty za wczesnie | ryzyko niezgodnosci z Google | nie wdrazac w etapie 1 + 2 |
| Layout zepsuje inne strony publiczne | regresje na znakach/przepisach/cenniku | kompatybilnosc przejsciowa layoutu |
| Refaktor schema dotknie dzialu nauki | regresja produktu, sesji albo progresu | zakaz zmian w `/nauka` i obowiazkowe testy regresyjne nauki/sesji |
| Testy zostana oslabione pod refaktor | falszywe poczucie bezpieczenstwa | wolno aktualizowac tylko testy bezposrednio dotyczace schema/brandu |

## 13. Zrodla i zasady zewnetrzne

Przy wdrozeniu trzymamy sie zasad:

- JSON-LD jest rekomendowanym formatem structured data przez Google.
- Structured data musi opisywac realna zawartosc strony.
- Dane ukryte przed uzytkownikiem nie powinny byc publikowane w schema jako glowna tresc.
- `Dataset` ma sens dla uporzadkowanego zbioru danych, ale musi miec prawdziwy opis i stabilny URL.
- `VideoObject` generujemy tylko przy realnym filmie z wymaganymi polami.
- `SearchAction` moze zostac, ale nie jest juz mocnym elementem rich result.
- `FAQPage` i `QAPage` nie sa fundamentem tej strategii.

Referencje:

- Google Structured Data Guidelines: https://developers.google.com/search/docs/appearance/structured-data/sd-policies
- Google Dataset Structured Data: https://developers.google.com/search/docs/appearance/structured-data/dataset
- Google VideoObject Structured Data: https://developers.google.com/search/docs/appearance/structured-data/video
- Google Sitelinks Search Box sunset: https://developers.google.com/search/blog/2024/10/sitelinks-search-box
- Google QAPage: https://developers.google.com/search/docs/appearance/structured-data/qapage
- Schema.org QAPage: https://schema.org/QAPage

## 14. Nastepny krok po tej dokumentacji

Rekomendowany kolejny task:

```text
Wdrozyc etap 1: SchemaIds + SchemaGraph + SchemaRenderer + testy jednego @graph dla hub/category/question.
```

Po zielonych testach etapu 1 przechodzimy do etapu 2 w kolejnosci:

1. hub,
2. kategoria,
3. pytanie,
4. prawo/media/tematy,
5. smoke produkcyjny.

## 15. Addendum: etap znakow drogowych

Status 2026-06-17: etap publicznych znakow drogowych zostal wydzielony do osobnego dokumentu:

- [docs/TRAFFIC-SIGN-ENTERPRISE-SCHEMA-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/TRAFFIC-SIGN-ENTERPRISE-SCHEMA-PLAN.md)

Zakres etapu znakow:

- `/znaki-drogowe`,
- `/znaki-drogowe/{kategoria}`,
- `/znaki-drogowe/{znak}`.

Decyzja modelowa:

- nie tworzymy sztucznego typu `TrafficSign`,
- baze znakow opisujemy jako `DefinedTermSet`,
- kategorie i konkretne znaki opisujemy jako `DefinedTerm`,
- listy widoczne na hubie i stronie kategorii opisujemy jako `ItemList`,
- strona znaku laczy `WebPage`, `DefinedTerm`, `Article`, opcjonalne `ImageObject`, opcjonalne `FAQPage` i opcjonalne `citation`.

Guardrail pozostaje bez zmian: refaktor schema znakow nie zmienia `/nauka`, sesji nauki, egzaminu, progresu ani API nauki.

## 16. Addendum: etap przepisow

Status 2026-06-17: etap publicznego dzialu `/przepisy` zostal wydzielony do osobnego dokumentu:

- [docs/LEGAL-CONTENT-ENTERPRISE-SCHEMA-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/LEGAL-CONTENT-ENTERPRISE-SCHEMA-PLAN.md)

Zakres etapu:

- `/przepisy`,
- `/przepisy/{slug}`,
- `/metodologia/przepisy-i-podstawy-prawne`.

Decyzja modelowa:

- hub `/przepisy` opisujemy jako `CollectionPage` z `ItemList`,
- artykul prawny opisujemy jako `Article` powiazany z `WebPage`,
- widoczne jednostki prawne opisujemy jako osobne node'y `Legislation`,
- tematy prawne opisujemy jako `DefinedTerm`,
- powiazane pytania egzaminacyjne opisujemy jako `ItemList` i referencje do publicznych `Question`,
- nie publikujemy w schema pelnego tekstu przepisu, jesli na stronie widoczny jest tylko opis/summary.

Guardrail pozostaje bez zmian: refaktor schema przepisow nie zmienia `/nauka`, sesji nauki, egzaminu, progresu ani API nauki.
