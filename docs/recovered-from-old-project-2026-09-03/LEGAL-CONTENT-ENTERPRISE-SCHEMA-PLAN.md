# Legal Content Enterprise Schema Plan

Data aktualizacji: 2026-06-17  
Branch roboczy: `codex/enterprise-legal-content-schema`  
Status produkcyjny: wdrozone na `main` 2026-06-17

## 1. Cel

Przeniesc publiczny dzial `/przepisy` z legacy list wielu osobnych JSON-LD do jednego spojnego `@graph`, zgodnego z podejsciem wdrozonym juz dla publicznej bazy pytan i znakow drogowych.

Zakres tego etapu:

- `/przepisy`,
- `/przepisy/{slug}`,
- `/metodologia/przepisy-i-podstawy-prawne`.

Poza zakresem:

- zmiana tresci artykulow prawnych,
- zmiana seedera merytorycznego,
- zmiana widokow Blade,
- zmiana inline edytora podstaw prawnych przy pytaniach,
- produkt nauki `/nauka`, sesje, progres i API nauki.

## 2. Guardraile

- Schema opisuje tylko tresc widoczna publicznie na stronie.
- Nie publikujemy w schema tekstu przepisu, jesli na stronie widoczny jest tylko opis/summary.
- Nie tworzymy sztucznych node'ow prawnych dla draftow.
- Nie ruszamy `/nauka`.
- Nie oslabiamy istniejacych testow warstwy prawnej, pytan ani znakow.
- Stabilne `@id` jednostek prawnych pozostaje wspolne z publicznym schema pytan: `/entity/law/{legal_unit_slug}`.

## 3. Model graphu

### Stale node'y

- `Organization`,
- `WebSite`,
- `BreadcrumbList`.

### Hub `/przepisy`

Jeden JSON-LD `@graph`:

- `CollectionPage`,
- `ItemList` dla widocznych artykulow,
- `DefinedTerm` dla widocznych tematow prawnych,
- wspolne `Organization` i `WebSite`.

### Artykul `/przepisy/{slug}`

Jeden JSON-LD `@graph`:

- `WebPage`,
- `Article`,
- `DefinedTerm` dla tematu prawnego,
- `Legislation` dla widocznych jednostek prawnych,
- `ItemList` dla sekcji `Podstawa prawna`,
- opcjonalny `ItemList` dla sekcji `Powiazane pytania egzaminacyjne`,
- opcjonalne `Question` node'y dla pytan widocznych w powiazanej sekcji,
- `Person` dla autora i reviewera, jesli sa widoczni.

### Metodologia

Jeden JSON-LD `@graph`:

- `WebPage`,
- `Organization`,
- `WebSite`,
- `BreadcrumbList`.

## 4. Status implementacji lokalnej

Zrobione:

- [x] Dodano generyczne identyfikatory schema dla `/przepisy` w `SchemaIds`.
- [x] Ujednolicono ID jednostki prawnej przez `SchemaIds::legalUnit()`.
- [x] Przepisano `LegalContentSchemaService::hub()` na pojedynczy `@graph`.
- [x] Przepisano `LegalContentSchemaService::page()` na pojedynczy `@graph`.
- [x] Przepisano `LegalContentSchemaService::methodology()` na pojedynczy `@graph`.
- [x] Dodano `Legislation` node'y dla widocznych jednostek prawnych.
- [x] Dodano testy jednego JSON-LD `@graph` dla huba, artykulu i metodologii.
- [x] Dodano test braku duplikatow `@id`.
- [x] Uruchomiono regresje publicznych pytan i znakow.

Do zrobienia pozniej:

- [x] Smoke produkcyjny po merge/deploy.
- [ ] Walidacja zewnetrznym narzedziem Google/schema.org po deployu.
- [ ] Rozwazyc osobny `Legislation` dla aktu prawnego jako parent, jesli bedziemy publicznie pokazywac wiecej danych aktu.

## 5. Weryfikacja lokalna

Wykonane 2026-06-17:

```text
docker compose exec -T app php artisan test tests/Feature/Public/LegalTrustLayerMvpTest.php
12 passed (208 assertions)

docker compose exec -T app php artisan test tests/Feature/PublicQuestionDatabasePageTest.php
15 passed (371 assertions)

docker compose exec -T app php artisan test tests/Feature/Public/TrafficSignSeoInfrastructureTest.php
10 passed (171 assertions)
```

## 6. Weryfikacja produkcyjna

Deploy wykonany 2026-06-17:

- commit feature: `f66ebd6 Implement enterprise schema graph for legal content`,
- merge do `main`: `544a8db Merge enterprise legal content schema graph`,
- paczka runtime: `output/release-legal-content-schema-20260617131410.tar.gz`,
- backup produkcyjny: `/tmp/prawkonaraz-legal-content-schema-backup-20260617111606`.

Smoke po deployu:

```text
https://prawkonaraz.pl/przepisy
200, JSON-LD scripts=1, graph nodes=15, duplicate @id=0
types: BreadcrumbList, CollectionPage, DefinedTerm, ItemList, Organization, WebSite

https://prawkonaraz.pl/przepisy/tramwaje-i-przystanki
200, JSON-LD scripts=1, graph nodes=13, duplicate @id=0
types: Article, BreadcrumbList, DefinedTerm, ItemList, Legislation, Organization, Person, Question, WebPage, WebSite

https://prawkonaraz.pl/metodologia/przepisy-i-podstawy-prawne
200, JSON-LD scripts=1, graph nodes=4, duplicate @id=0
types: BreadcrumbList, Organization, WebPage, WebSite
```

## 7. Kryteria gotowosci

- [x] `/przepisy` renderuje jeden JSON-LD script.
- [x] `/przepisy/{slug}` renderuje jeden JSON-LD script.
- [x] `/metodologia/przepisy-i-podstawy-prawne` renderuje jeden JSON-LD script.
- [x] Kazdy script ma `@context=https://schema.org` i `@graph`.
- [x] Graph nie ma duplikatow `@id`.
- [x] Artykul prawny ma `Article`, `WebPage`, `Legislation`, `Person`, `DefinedTerm` i `ItemList`.
- [x] Powiazane pytania wchodza do graphu tylko, gdy sa realnie zweryfikowane i publiczne.
- [x] Publiczne pytania nadal przechodza testy enterprise schema.
- [x] Publiczne znaki nadal przechodza testy enterprise schema.

## 8. Powiazane dokumenty

- [docs/ENTERPRISE-SCHEMA-STAGE-1-2-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ENTERPRISE-SCHEMA-STAGE-1-2-PLAN.md)
- [docs/TRAFFIC-SIGN-ENTERPRISE-SCHEMA-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/TRAFFIC-SIGN-ENTERPRISE-SCHEMA-PLAN.md)
- [docs/LEGAL-TRUST-LAYER-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/LEGAL-TRUST-LAYER-PLAN.md)
- [docs/LEGAL-CONTENT-AUTHORING-WORKFLOW.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/LEGAL-CONTENT-AUTHORING-WORKFLOW.md)
