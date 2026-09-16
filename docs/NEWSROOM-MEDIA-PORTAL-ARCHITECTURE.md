# Newsroom / Media Portal Architecture

## 1. Status dokumentu

- **Status:** Proposed / canonical design before implementation
- **Obszar:** publiczny serwis informacyjny, newsroom, aktualności, poradniki i dystrybucja treści
- **Repozytorium:** `prawkonaraz100/prawkonaraz`
- **Bazowy stan kodu:** `main@4b10738705f3696bc2bcce730a707473eab8cd2b`
- **Data utworzenia:** 2026-09-15
- **Właściciel decyzji produktowej:** PrawkoNaRaz
- **Cel:** zaprojektować profesjonalny pion medialny bez dublowania istniejącej platformy, bez osobnego CMS/WordPressa i bez rozbijania modularnego monolitu.

Ten dokument jest **kanoniczną specyfikacją dla obszaru newsroom/media portal**. Nie zastępuje dokumentów nadrzędnych dotyczących całego systemu.

## 2. Dokumenty nadrzędne i zależności

W razie konfliktu obowiązuje następująca kolejność:

1. [STACK-DECISION.md](./STACK-DECISION.md) — wybór stacku technologicznego.
2. [ADR-001-MODULAR-MONOLITH.md](./ADR-001-MODULAR-MONOLITH.md) — granice architektury i decyzja o modularnym monolicie.
3. [SEO-CONTENT-ROADMAP.md](./SEO-CONTENT-ROADMAP.md) — strategia publicznego contentu, SEO, trust layer i workflow redakcyjny.
4. [SEO-SITEMAP-REPAIR-PLAN.md](./SEO-SITEMAP-REPAIR-PLAN.md) — istniejący kanoniczny kontrakt produkcyjnego sitemap/robots delivery; newsroom rozszerza go bez zmiany istniejącego modelu.
5. [SEO-ENTERPRISE-INTERNAL-LINKING-ROADMAP-V2.md](./SEO-ENTERPRISE-INTERNAL-LINKING-ROADMAP-V2.md) — istniejący graph/taksonomia pytań; newsroom nie tworzy jego drugiej wersji. Roadmapa odwołuje się historycznie do ADR V2, ale w aktualnym drzewie `docs/` nie ma pliku o tej nazwie, więc nie tworzymy martwego odnośnika.
6. [MENU-SYSTEM-REFERENCE.md](./MENU-SYSTEM-REFERENCE.md) — kanoniczna referencja publicznej nawigacji.
7. **Ten dokument** — szczegółowa architektura newsroomu, portalu informacyjnego i powiązania z produktem.

Dokument nie zmienia stacku i nie wprowadza nowej aplikacji. Projekt newsroomu ma być naturalnym rozwinięciem istniejącego systemu.

### 2.1. Mapa dokumentacji wykonawczej newsroomu

Ten dokument opisuje decyzje nadrzędne. Szczegóły implementacyjne są rozdzielone celowo:

- [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md) — model domenowy, tabele, constraints, indeksy, serwisy, scheduling i redirecty.
- [NEWSROOM-ADMIN-CMS-SPEC.md](./NEWSROOM-ADMIN-CMS-SPEC.md) — panel Filament, formularze, workflow actions, checklisty, preview i uprawnienia.
- [NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md](./NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md) — proces redakcyjny, źródła, korekty, breaking, freshness, AI policy i governance.
- [NEWSROOM-PUBLIC-UI-UX-SPEC.md](./NEWSROOM-PUBLIC-UI-UX-SPEC.md) — kontrakt publicznego layoutu, komponenty, mobile, accessibility i performance UI.
- [NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md](./NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md) — canonical, schema, obrazy, news sitemap, feed, Discover, analytics i monitoring.
- [NEWSROOM-IMPLEMENTATION-BACKLOG.md](./NEWSROOM-IMPLEMENTATION-BACKLOG.md) — kolejność N0–N6, taski, zależności, granice PR-ów i Definition of Done.
- [NEWSROOM-TEST-RELEASE-AND-ROLLBACK-RUNBOOK.md](./NEWSROOM-TEST-RELEASE-AND-ROLLBACK-RUNBOOK.md) — test matrix, E2E, release, smoke, incident handling i rollback.

### 2.2. Jak rozstrzygać konflikt między dokumentami newsroomu

1. decyzja architektoniczna i granica systemu — ten dokument,
2. dane/invariants — Data Model and Domain Spec,
3. zachowanie backoffice — Admin CMS Spec,
4. reguły redakcyjne — Editorial Operations,
5. zachowanie publicznego UI — Public UI/UX Spec,
6. Search/dystrybucja/monitoring — SEO Distribution and Observability,
7. kolejność implementacji — Implementation Backlog,
8. test/deploy/rollback — Test Release and Rollback Runbook.

Jeżeli kod wymusi zmianę decyzji nadrzędnej, najpierw aktualizujemy ten dokument. Jeżeli zmienia się tylko szczegół wykonawczy, aktualizujemy właściwą specyfikację bez przepisywania całej architektury.

---

## 3. Problem do rozwiązania

PrawkoNaRaz ma rozbudowany produkt edukacyjny i rosnącą warstwę publicznego contentu, ale nie ma jeszcze pełnoprawnego serwisu informacyjnego, który:

- regularnie pozyskuje ruch informacyjny,
- reaguje na zmiany przepisów, egzaminów i rynku,
- buduje rozpoznawalność marki poza samym momentem nauki do egzaminu,
- prowadzi użytkownika z informacji do pytań, wyjaśnień, testów i produktu,
- tworzy własny kanał dystrybucji niezależny od płatnej reklamy,
- daje redakcji narzędzia do szybkiej, kontrolowanej i audytowalnej publikacji.

Celem nie jest stworzenie „bloga firmowego”. Celem jest **profesjonalny portal wertykalny** skupiony na prawie jazdy, kierowcach, przepisach, egzaminach, WORD, OSK i bezpieczeństwie ruchu.

Inspiracją może być hierarchia informacji znana z dużych portali, ale projekt **nie może kopiować layoutu, brandingu ani wzorców 1:1**. PrawkoNaRaz ma mieć własny, czytelny system redakcyjny.

---

## 4. Cele produktu

### 4.1. Cel główny

Zbudować publiczny pion medialny, który zwiększa zasięg informacyjny i prowadzi część ruchu do istniejącego produktu.

### 4.2. Cele wtórne

- budowa topical authority wokół prawa jazdy i kierowców,
- szybsze wejście w zapytania związane ze zmianami prawa i egzaminów,
- rozwój ruchu lokalnego wokół WORD,
- budowa profili autorów i warstwy zaufania,
- tworzenie materiałów cytowalnych na podstawie własnych, zagregowanych danych,
- wykorzystanie istniejących pytań, znaków i treści prawnych jako przewagi redakcyjnej,
- przygotowanie kanałów pod Google Search, Discover, social media, RSS i przyszłe integracje dystrybucyjne.

### 4.3. Antycele

Na pierwszym etapie nie budujemy:

- osobnego WordPressa,
- osobnej domeny lub subdomeny newsroomu,
- mikroserwisu contentowego,
- automatycznej masowej publikacji generowanej bez review,
- systemu reklamowego klasy dużego portalu,
- newsroomu ogólnotematycznego,
- agregatora kopiującego cudze newsy,
- paywalla dla zwykłych treści informacyjnych.

---

## 5. Aktualny stan implementacji

Stan sprawdzony ponownie 2026-09-16 względem aktualnego `main` po wdrożeniu NEWSROOM-N0-001 i NEWSROOM-N0-002.

### 5.1. Elementy już istniejące

Repo ma już istotny fundament:

- Laravel 12,
- Inertia + Vue 3 + TypeScript,
- Filament jako backoffice,
- PostgreSQL,
- publiczny layout Blade z:
  - canonical,
  - robots,
  - OpenGraph,
  - Twitter Cards,
  - author metadata,
  - published/modified time,
  - JSON-LD,
- modele i publiczne profile `ContentAuthor`,
- route `/autorzy/{authorSlug}`,
- statyczny produkcyjny pipeline sitemap `SeoSitemapGenerator` + `SeoSitemapBuilder` + `SeoSitemapAuditor`, z codziennym `seo:refresh-sitemaps` jako istniejącym safety netem,
- dynamiczny `SitemapController`, który współistnieje z generowanymi artefaktami i nie jest samodzielnym source of truth produkcyjnego XML,
- statyczny `public/robots.txt` oraz istniejący `RobotsController`; produkcyjny kontrakt robots pozostaje zgodny z `SEO-SITEMAP-REPAIR-PLAN.md`,
- breadcrumbs,
- `config/content.php['organization']` jako istniejące dane organizacji,
- `SchemaIds` ze stabilnymi `/#organization` i `/#website`,
- `SchemaRenderer` i istniejący graph pattern,
- schema services dla treści prawnych i znaków,
- publiczny klaster znaków drogowych,
- publiczna baza pytań,
- relacje z podstawami prawnymi,
- publiczna nawigacja z pozycją „Aktualności”,
- route `/aktualnosci` i `/poradniki` z zachowanymi top-level route names,
- wykonywalny `NewsroomRouteContract`,
- zarejestrowane route namespaces feed/category/topic/article zgodne z finalnym kontraktem,
- przyszłe detail/category/topic/feed routes pozostające 404 do czasu wdrożenia publicznych controllerów,
- SEO/content roadmap,
- Filamentowy workflow dla części istniejącego contentu.

### 5.2. Elementy istniejące tylko jako placeholder

`/aktualnosci` i `/poradniki` nadal renderują `Public/MarketingPlaceholder.vue`, teraz przez dedykowany `NewsroomPlaceholderController`, który ustawia `X-Robots-Tag: noindex, follow`.

Finalne route namespaces są już utrwalone, lecz nie publikują treści:

- `/aktualnosci/feed.xml`,
- `/aktualnosci/kategoria/{categorySlug}`,
- `/aktualnosci/temat/{topicSlug}`,
- `/aktualnosci/{articleSlug}`,
- `/poradniki/{articleSlug}`

zwracają obecnie 404 do czasu wdrożenia właściwych publicznych controllerów.

To oznacza, że:

- adresy, IA i matching/order contract już istnieją,
- nie istnieje właściwy model publikacji newsroomowej,
- nie istnieje lista artykułów,
- nie istnieje widok artykułu informacyjnego,
- nie istnieje record-level ContentArticle route-family lookup,
- nie istnieje redakcyjny CMS dla newsów.

### 5.3. Brakujące elementy

Nie ma obecnie kompletnego odpowiednika:

- `ContentArticle`,
- `ContentCategory`,
- tagów redakcyjnych,
- relacji artykuł ↔ pytanie,
- relacji artykuł ↔ podstawa prawna,
- workflow newsroomowego,
- modułu „pilne / ważne / featured”,
- news sitemap,
- feedu RSS/Atom,
- strony kategorii newsroomowej,
- strony pojedynczego newsa,
- rankingów najnowsze / najczęściej czytane,
- pomiaru ekspozycji i CTR modułów redakcyjnych.

### 5.4. Znana niespójność brandingu

Przed wdrożeniem schema `NewsArticle` należy uporządkować branding publishera.

Historycznie `HomePageController.php` zawierał pozostałości marki „Orły na Drodze”. NEWSROOM-N0-001 usunął ten hardcode: homepage korzysta teraz ze wspólnego kanonicznego site identity PrawkoNaRaz.

Repo korzysta już ze wspólnego fundamentu `config/content.php['organization']`, `SchemaIds`, `SchemaRenderer` i `SiteIdentitySchema`. Historyczna niespójność homepage została zamknięta przez NEWSROOM-N0-001.

**Gate:** przed uruchomieniem newsroomu `Organization`, `WebSite`, publisher, site name, logo, OG site name i publiczny branding muszą korzystać z jednego istniejącego source of truth oraz stabilnych graph IDs.

---

## 6. Główne decyzje architektoniczne

### DEC-NR-001 — newsroom pozostaje w tym samym repo i systemie

**Decyzja:** newsroom jest modułem obecnej aplikacji Laravel.

**Uzasadnienie:**

- może bezpośrednio korzystać z pytań, znaków, autorów i podstaw prawnych,
- jeden auth i jeden backoffice,
- jeden deployment,
- jeden model audytu,
- jedna domena i wspólny autorytet SEO,
- brak synchronizacji między CMS a produktem.

### DEC-NR-002 — jedna domena kanoniczna

Publiczny portal działa pod:

`https://prawkonaraz.pl`

Bez oddzielnego `news.`, `blog.` i bez osobnej domeny.

### DEC-NR-003 — publiczny content newsroomowy jest SSR-first

Widoki indeksowalne powinny korzystać z tego samego podejścia co obecna publiczna warstwa contentowa:

- Blade/server rendering jako domyślna warstwa dokumentu,
- pełne meta i structured data w initial HTML,
- JavaScript tylko tam, gdzie daje wartość użytkową,
- brak zależności od hydration do odczytania głównej treści.

Inertia/Vue pozostaje właściwe dla powierzchni aplikacyjnych i interaktywnych, a Filament dla backoffice.

### DEC-NR-004 — jeden model artykułu z kontrolowanymi typami

Zamiast osobnych tabel `news`, `guides`, `analysis` projekt powinien mieć wspólny agregat `content_articles` z polem `type`.

Pierwsze typy:

- `news` — aktualność,
- `guide` — poradnik evergreen,
- `explainer` — wyjaśnienie zmiany lub zjawiska,
- `analysis` — analiza danych / trendu,
- `report` — materiał własny oparty na danych.

Nowy typ wymaga jawnej decyzji; nie tworzymy dowolnych stringów.

### DEC-NR-005 — produktowy home pozostaje bezpiecznie oddzielony na pierwszym etapie

Pierwszym pełnym hubem newsroomu jest:

`/aktualnosci`

Obecne `/` pozostaje stroną produktową.

Po uruchomieniu i zebraniu danych można dodać blok „Najnowsze informacje” na stronie głównej. Pełna zmiana `/` w portal hybrydowy wymaga osobnej decyzji opartej o dane.

### DEC-NR-006 — redakcja steruje stałymi slotami strony głównej

Newsroom v1 ma kontrolowany system ekspozycji redakcyjnej dla `/aktualnosci`.

Nie jest to page builder. Kod definiuje skończony zestaw powierzchni i slotów, np.:

- lead,
- secondary,
- category lead,
- guides lead,
- important_now / „Ważne teraz”.

Redaktor może przypisać artykuł do slotu oraz ustawić czas początku i końca ekspozycji. Brak ręcznego przypisania uruchamia deterministyczny fallback oparty o aktualnie opublikowane treści.

Układ DOM, liczba sekcji i typ komponentu pozostają pod kontrolą kodu.

### DEC-NR-007 — artykuł korzysta z kontrolowanej biblioteki bloków

Treść artykułu nie może ograniczać się do niekontrolowanego jednego pola HTML, jeżeli chcemy bezpiecznie i konsekwentnie renderować materiały redakcyjne.

Newsroom v1 przewiduje uporządkowany dokument z kontrolowanymi typami bloków, m.in.:

- rich text,
- image,
- quote,
- table,
- context/callout,
- related article,
- legal reference,
- question group,
- traffic sign group,
- product CTA,
- embed — typ zarezerwowany, lecz wyłączony w v1 do czasu osobnego provider/CSP security gate.

To nadal nie jest uniwersalny page builder: redaktor nie definiuje dowolnego HTML, CSS, layoutu ani nowych typów komponentów.

NEWSROOM-N0-004 domknęło tę decyzję: `NewsroomBodyContract` v1 zapisuje kanonicznie uporządkowaną listę `{key?, type, data}`, niezależną od wewnętrznego associative state Filament Buildera. Przyszły N2 editor używa Buildera jako adaptera UI, a `rich_text` przechowuje structured TipTap JSON z RichEditor, bez równoległego `body_html`. Nieznany typ/wersja failuje zamknięcie. Nowy writer/block type nie może wyprzedzić kompatybilnego readera/renderera lub jawnej migracji danych.

### DEC-NR-008 — topic jest innym bytem niż tag

Tag służy lekkiej klasyfikacji i nie tworzy automatycznie publicznego URL.

Topic/dossier jest ręcznie zarządzanym hubem redakcyjnym o własnym:

- tytule,
- slugu,
- opisie,
- statusie publikacji,
- materiale wyróżnionym,
- zestawie powiązanych artykułów.

Model topics może wejść w v1, natomiast publiczne uruchomienie konkretnego huba wymaga realnego corpus i wartości dla użytkownika. Nie generujemy masowych thin pages z tagów.

### DEC-NR-009 — media mają art direction, nie tylko jedną ścieżkę obrazu

Hero ma przechowywać punkt zainteresowania/focal point. Media layer może generować kontrolowane warianty/cropy potrzebne dla:

- lead,
- standard card,
- compact card,
- social/OG.

Redaktor nie powinien ręcznie uploadować wielu niezależnych kopii tego samego obrazu, jeśli pipeline może przygotować warianty deterministycznie.

### DEC-NR-010 — audio i funkcje AI są zaplanowanym rozszerzeniem, nie gate v1

Architektura artykułu ma nie blokować późniejszego:

- odsłuchu artykułu,
- transkrypcji,
- kontrolowanego streszczenia,
- funkcji „zapytaj o ten artykuł”.

Nie tworzymy jednak pól/tabel ani publicznych controls tylko „na zapas”, dopóki nie rozpocznie się odpowiedni etap implementacji. Takie pochodne treści nie mogą samodzielnie publikować ani zmieniać faktów źródłowego artykułu.

### DEC-NR-011 — osobny system snapshotów wersji artykułu jest poza zakresem

Na wyraźną decyzję produktową nie projektujemy dodatkowego revision history z diff/restore snapshotów. Pozostaje istniejący model audytu zmian i publiczna polityka korekt.

### DEC-NR-012 — jeden site identity/entity graph w całym serwisie

Newsroom nie tworzy własnego publisher graph ani własnego `WebSite`.

Target wykorzystuje istniejące:

- `config/content.php['organization']`,
- `SchemaIds::organization()` -> `/#organization`,
- `SchemaIds::website()` -> `/#website`,
- `SchemaRenderer`.

Article page składa spójny graph z WebSite, Organization, WebPage, Article/NewsArticle, Person, BreadcrumbList i ImageObject, połączony stabilnymi `@id`.

Homepage pozostaje miejscem kanonicznego `WebSite name/url` dla domeny. `/aktualnosci` jako subdirectory nie ustanawia osobnego site name.

### DEC-NR-013 — SEO distribution jest projektowane na skalowanie, nie na jeden plik

Newsroom rozszerza istniejący sitemap subsystem zamiast tworzyć równoległy generator.

Kontrakt obejmuje:

- article sitemap z deterministic sharding readiness,
- osobną news sitemap z pełnym news namespace,
- istniejący główny sitemap index,
- rozszerzenie istniejącego `SeoSitemapAuditor`,
- ETag/Last-Modified i conditional 304 dla sitemap/feed,
- feed discovery w publicznym HTML.

Limity zewnętrzne są ponownie weryfikowane przy implementacji, ale architektura nie może zakładać, że corpus zawsze zmieści się w jednym pliku.


### DEC-NR-014 — semantic silo = primary hierarchy + controlled cross-domain graph

Każdy artykuł ma jedną primary category. Topic/dossier i tag są dodatkowymi wymiarami, nie konkurencyjnymi parentami canonical.

Hierarchia bazowa:

- `/aktualnosci -> category -> article`,
- `/aktualnosci -> topic -> article`,
- `/poradniki -> guide`.

Nie izolujemy klastrów sztucznie. Jawne relacje article-question/legal/sign mogą tworzyć ograniczone dwukierunkowe linki publiczne, aby połączyć świeży newsroom z istniejącymi evergreen/source-of-truth klastrami.

Relacja article ↔ question jest osobnym newsroomowym mostem do istniejącej encji pytania. Nie zmienia `question_relations`, rankingu V1/V2 ani taksonomii `question_seo_topics`.

Sitemap nie zastępuje internal linking. Każdy ważny publiczny URL musi być osiągalny crawlable linkiem z innej publicznej strony.

### DEC-NR-015 — newsroom rozszerza statyczny produkcyjny pipeline sitemap/robots

Źródłem nadrzędnym dla sposobu dostarczania sitemap/robots jest istniejący `SEO-SITEMAP-REPAIR-PLAN.md`.

Newsroom:

- rozszerza `SeoSitemapGenerator`, `SeoSitemapBuilder` i `SeoSitemapAuditor`,
- generuje newsroom/news XML jako statyczne artefakty do `public/`,
- nie zastępuje tego pipeline osobnym runtime generatorem,
- nie usuwa `SitemapController` ani `RobotsController` w tym samym zakresie implementacyjnym,
- zachowuje istniejący `public/robots.txt` jako produkcyjny kontrakt do czasu osobnego, zweryfikowanego hardeningu warstwy webserver/CDN.

Nagłówki cache/ETag/Last-Modified/304 dla statycznych sitemap są kontraktem warstwy Nginx/CDN/static delivery. Nie zakładamy, że zmiana tylko w Laravel controller wpłynie na produkcyjny artefakt.

Publikacja newsroomu nie może blokować requestu pełnym generowaniem sitemap. Po commit zapisywany jest tani dirty/version signal we współdzielonym store; częsty scheduler z distributed lockiem coalescuje zmiany i odświeża statyczne artefakty poza requestem. Nie zakładamy asynchronicznego Laravel queue workera; istniejący daily refresh pozostaje niezależnym safety netem.

### DEC-NR-016 — route family jest stabilne po pierwszej publikacji

Typy dzielą się na dwie rodziny publicznego URL:

- `newsroom`: news, explainer, analysis, report -> `/aktualnosci/{slug}`,
- `guides`: guide -> `/poradniki/{slug}`.

Przed pierwszą publikacją typ można zmieniać.

Po ustawieniu `first_published_at` zwykła edycja może zmieniać typ tylko wewnątrz tej samej route family. Zmiana pomiędzy `newsroom` i `guides` jest zablokowana w v1, ponieważ zmieniałaby canonical path.

Jeśli w przyszłości dopuścimy migrację route family, będzie to osobna uprzywilejowana operacja z pełnym 301 poprzedniego path, aktualizacją linków/sitemap i testem jednego hopu. Nie robimy tego przez zwykły Select ani ręczną zmianę w DB.

---

## 7. Architektura informacji

### 7.1. Główne wejścia

```text
/
├── aktualnosci/
│   ├── kategoria/prawo-jazdy/
│   ├── kategoria/egzaminy/
│   ├── kategoria/przepisy/
│   ├── kategoria/word/
│   ├── kategoria/kierowcy/
│   ├── kategoria/osk/
│   └── temat/{topicSlug}/
├── poradniki/
├── przepisy/
├── znaki-drogowe/
├── oficjalna-baza-pytan-na-prawo-jazdy/
├── testy-na-prawo-jazdy/
└── nauka/
```

### 7.2. URL artykułu

Rekomendowany model:

`/aktualnosci/{slug}`

oraz:

`/poradniki/{slug}`

Nie umieszczamy daty w canonical URL. Data jest metadanym artykułu, nie częścią jego tożsamości.

### 7.3. URL kategorii

Rekomendowany i przyjęty do backlogu wykonawczego wariant:

`/aktualnosci/kategoria/{categorySlug}`

Jawny segment `kategoria` usuwa kolizję routingu między slugiem kategorii i slugiem artykułu. Slug kategorii jest stabilny i zarządzany centralnie. Każda zmiana tej decyzji wymaga aktualizacji architektury i testu konfliktów route.

### 7.4. URL topicu / dossier

`/aktualnosci/temat/{topicSlug}`

Topic ma własny publiczny URL dopiero po publikacji i spełnieniu warunków jakości opisanych w governance/SEO. Tag nie otrzymuje tego URL automatycznie.

### 7.5. Redirect governance

Zmiana opublikowanego sluga:

- zapisuje poprzedni URL,
- generuje trwały redirect do canonical,
- aktualizuje linkowanie wewnętrzne,
- nie tworzy dwóch indeksowalnych kopii.

---

## 8. Taksonomia newsroomu

### 8.1. Kategorie podstawowe v1

1. **Prawo jazdy**
   - PKK,
   - kurs,
   - wymagania,
   - kategorie uprawnień.

2. **Egzaminy**
   - teoria,
   - praktyka,
   - zmiany zasad,
   - pojazdy egzaminacyjne.

3. **Przepisy**
   - nowelizacje,
   - obowiązki kierowców,
   - pierwszeństwo,
   - prędkość,
   - znaki i organizacja ruchu.

4. **WORD**
   - informacje o ośrodkach,
   - zmiany lokalne,
   - terminy i organizacja egzaminów,
   - dane i statystyki.

5. **Kierowcy**
   - dokumenty,
   - mandaty i punkty,
   - bezpieczeństwo,
   - obowiązki po uzyskaniu uprawnień.

6. **OSK**
   - szkoły jazdy,
   - szkolenie,
   - wymagania,
   - rynek i cyfryzacja.

### 8.1.1. Aktualny stan kontraktu kategorii

NEWSROOM-N0-003 jest wdrożone jako `NewsroomTaxonomyContract` v1. Kontrakt utrwala tę samą kolejność przez pozycje 10, 20, 30, 40, 50, 60 oraz dokładne pary slug/nazwa publiczna. Description i pola SEO pozostają jawnie niezatwierdzone (`null`).

To jest foundation contract dla przyszłej migracji/seedera, nie zmaterializowana taksonomia DB: `content_categories`, `ContentCategory` i dedykowany seeder nadal należą do N1.

### 8.2. Tagi

Tag nie może zastępować kategorii.

Tagi służą do przecięcia tematów, np.:

- PKK,
- egzamin teoretyczny,
- egzamin praktyczny,
- młody kierowca,
- kategoria B,
- Ministerstwo Infrastruktury.

Tagi wymagają deduplikacji i normalizacji.

### 8.3. Geografia

Lokalność powinna być modelowana jawnie, nie tylko tagiem.

Docelowo:

- województwo,
- miasto,
- WORD,
- opcjonalny identyfikator jednostki.

Pozwoli to budować lokalne huby bez mnożenia przypadkowych URL.

---

## 9. Model danych

Poniższy model jest **docelowym projektem**, a nie opisem obecnej bazy.

### 9.1. `content_articles`

Poniższa lista jest skrótem architektonicznym. **Normatywny kontrakt pól, typów, constraints i indeksów znajduje się w [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md).**

Minimalny rdzeń:

```text
id
type
category_id
author_id
reviewer_id nullable

title
slug
lead
body_blocks
body_schema_version
key_points nullable

workflow_status
is_featured
is_breaking
editorial_priority
breaking_expires_at nullable

hero_image_path nullable
hero_image_alt nullable
hero_image_width nullable
hero_image_height nullable
og_image_path nullable
hero_image_caption nullable

seo_title nullable
seo_description nullable
robots nullable

scheduled_for nullable
published_at nullable
first_published_at nullable
reviewed_at nullable
needs_review_at nullable
archived_at nullable
withdrawn_at nullable
withdrawal_reason nullable
source_checked_at nullable
freshness_review_due_at nullable
last_substantive_update_at nullable
public_state_changed_at nullable

created_at
updated_at
```

### 9.2. `content_categories`

```text
id
name
slug
description
position
is_active
seo_title
seo_description
created_at
updated_at
```

### 9.3. `content_tags`

```text
id
name
slug
created_at
updated_at
```

### 9.4. Pivoty

- `content_article_tag`
- `content_article_question`
- `content_article_legal_unit`
- opcjonalnie później `content_article_traffic_sign`

### 9.5. Źródła

Jeżeli artykuły mają korzystać z wielu źródeł, nie przechowujemy ich jako jednego dużego pola JSON bez semantyki.

Docelowo:

`content_article_sources`

z polami m.in.:

- `article_id`,
- `source_type`,
- `publisher`,
- `title`,
- `url`,
- `published_at`,
- `accessed_at`,
- `is_primary`,
- `is_official`,
- `is_publicly_cited`,
- wewnętrzne `note`.

To pozwala budować kontrolowany system aktualizacji i weryfikacji.

---

## 10. Statusy i workflow redakcyjny

### 10.1. Statusy

```text
draft
  ↓
in_review
  ↓
scheduled
  ↓
published
  ↓
needs_review
  ↓
archived
```

Dopuszczalny jest powrót `in_review -> draft`.

### 10.2. Reguły publikacji

Artykuł nie może być publicznie indeksowalny, jeśli:

- nie ma tytułu,
- nie ma leadu,
- nie ma autora,
- nie ma kategorii,
- nie ma treści,
- nie ma poprawnego sluga,
- ma niespójny `workflow_status` / `scheduled_for` / `published_at`,
- nie spełnia minimalnej checklisty źródeł dla typu wymagającego źródeł.

### 10.3. Breaking / pilne

`is_breaking` jest flagą redakcyjną o krótkim cyklu życia.

Wymagania:

- ma być używana oszczędnie,
- nie może zastępować kategorii,
- musi mieć timestamp ostatniej zmiany,
- powinna automatycznie tracić ekspozycję po określonym czasie lub po ręcznym zdjęciu flagi.

### 10.4. Aktualizacja artykułu

Każda istotna zmiana po publikacji powinna:

- aktualizować `updated_at`,
- opcjonalnie aktualizować publiczne `modified_time`,
- pozostawiać ślad w audycie,
- nie zmieniać automatycznie `first_published_at`.

---

## 11. Filament / backoffice newsroomu

### 11.1. Resource: Artykuły

Lista powinna obsługiwać:

- status,
- typ,
- kategorię,
- autora,
- datę publikacji,
- featured,
- breaking,
- needs review,
- wyszukiwanie po tytule/slugu,
- filtr „zaplanowane”,
- filtr „wymaga aktualizacji”.

### 11.2. Formularz artykułu

Sekcje:

1. Treść
2. Klasyfikacja
3. Autor / reviewer
4. Media
5. Powiązania produktowe
6. Źródła
7. SEO
8. Publikacja
9. Historia / audyt

### 11.3. Preview

Redaktor musi mieć możliwość podglądu artykułu przed publikacją bez wystawiania go do indeksacji.

### 11.4. Role

Pierwszy model może wykorzystać istniejące role administracyjne, ale przed wejściem większej redakcji należy rozdzielić:

- author,
- editor,
- reviewer,
- publisher/admin.

Nie należy rozszerzać uprawnień dopóki nie pojawi się realna potrzeba operacyjna.

---

## 12. Publiczny layout `/aktualnosci`

Portal ma mieć **hierarchię redakcyjną**, nie zwykłą siatkę kart.

### 12.1. Kolejność modułów v1

1. pasek ważnych tematów,
2. lead story,
3. 2–4 secondary stories,
4. „Najnowsze”,
5. sekcja „Zmiany w prawie”,
6. sekcja „Egzaminy i WORD”,
7. sekcja „Kierowcy”,
8. sekcja „Poradniki”,
9. „Najczęściej czytane” dopiero po wdrożeniu wiarygodnych danych,
10. moduł produktu: test / pytania / nauka.

### 12.2. Zasady UI

- biały, czytelny layout,
- mocna hierarchia typografii,
- ograniczona liczba kolorów,
- akcent marki zamiast agresywnego „breaking red” wszędzie,
- duże zdjęcia tylko tam, gdzie wspierają hierarchię,
- mobile-first,
- brak ciężkiego masonry,
- brak infinite scroll w v1,
- brak autoplay video na listach.

### 12.3. Moduły muszą być konfigurowalne przez kontrolowane placements

Redakcja steruje zawartością stałych powierzchni zdefiniowanych przez kod:

- lead story,
- secondary stories,
- category leads,
- guides lead,
- wybrane linki „Ważne teraz”.

Placement może mieć przedział aktywności. Resolver kompozycji strony wybiera aktywne ręczne przypisanie albo deterministyczny fallback.

Publiczny resolver ma również prowadzić zbiór już użytych artykułów, tak aby ten sam materiał nie był przypadkowo powtarzany w leadzie, secondary, latest i kolejnych sekcjach. Jeśli brakuje unikalnych kandydatów, sekcja może być krótsza zamiast powielać materiał.

Nie projektujemy pełnego page buildera: redaktor nie zmienia struktury layoutu, tylko obsadza z góry znane sloty.

---

## 13. Strona artykułu

### 13.1. Elementy obowiązkowe

- breadcrumbs,
- kategoria,
- H1,
- lead,
- autor,
- data publikacji,
- data aktualizacji, jeśli różni się istotnie,
- hero image,
- body,
- blok źródeł,
- powiązane treści,
- powiązane pytania/test,
- informacje o autorze,
- link do metodologii / zasad redakcyjnych.

### 13.2. Kontrolowane bloki artykułu

Body jest renderowane z kontrolowanej biblioteki bloków. Pozwala to zachować ten sam standard na desktopie, mobile, preview i przyszłych kanałach bez dopuszczania dowolnego HTML/CSS.

Bloki v1 obejmują co najmniej:

- rich text,
- image,
- quote,
- table,
- context/callout,
- related article,
- legal reference,
- question group,
- traffic sign group,
- product CTA,
- allowlisted embed.

### 13.3. W skrócie i kontekst branżowy

Dla dłuższych materiałów można stosować blok „W skrócie”, ale:

- musi być ręcznie lub kontrolowanie redagowany,
- nie może powtarzać całego leadu,
- nie może być ukrytym tekstem SEO.

Dla newsów regulacyjnych i egzaminacyjnych model powinien dodatkowo wspierać strukturalne informacje:

- co się zmienia,
- status zmiany/prawa,
- od kiedy,
- kogo dotyczy,
- wpływ na egzamin.

Dane te mają być używane do czytelnego boxu publicznego oraz kontroli redakcyjnej, a nie do automatycznego wymyślania treści.

### 13.4. Product bridge

Przykłady:

- artykuł o zmianie przepisów → powiązane przepisy i pytania,
- artykuł o znaku → karta znaku i pytania,
- artykuł o egzaminie → publiczny test/demo,
- poradnik → właściwy etap procesu użytkownika.

CTA powinno wynikać z kontekstu, nie być globalnym banerem „kup teraz”.

---

## 14. Content graph

Największą przewagą PrawkoNaRaz ma być połączenie newsroomu z istniejącą domeną wiedzy.

```text
ARTICLE
├── CATEGORY
├── TAGS
├── TOPICS
├── AUTHOR
├── SOURCES
├── LEGAL UNITS
├── TRAFFIC SIGNS
├── QUESTIONS
└── PRODUCT ACTION
```

### 14.1. Reguły

- relacje muszą być jawne i edytowalne,
- automatyczne rekomendacje mogą wspierać redakcję,
- publikowana relacja powinna być kontrolowana,
- nie tworzymy linków tylko dla SEO,
- link musi mieć wartość dla czytelnika.

### 14.2. Huby topic/dossier

Publiczny topic jest świadomie utworzoną powierzchnią redakcyjną, a nie automatyczną stroną tagu.

Może łączyć:

- newsy,
- poradniki,
- przepisy,
- pytania,
- znaki,
- analizy danych.

Publicacja topicu wymaga własnego opisu, odpowiedniego corpus i review. Z czasem można tworzyć huby tematyczne łączące:

- newsy,
- poradniki,
- przepisy,
- pytania,
- znaki,
- analizy danych.

---

## 15. SEO i dane strukturalne

### 15.1. Meta

Każdy artykuł:

- unikalny title,
- unikalny description,
- self-canonical domyślnie,
- `max-image-preview:large`,
- poprawny OG image,
- author metadata,
- published/modified metadata.

### 15.2. Schema

Typ schema wynika z typu treści:

- `NewsArticle` dla rzeczywistych newsów,
- `Article` dla części materiałów redakcyjnych,
- `BlogPosting` nie jest domyślnym wyborem dla newsroomu,
- `BreadcrumbList`,
- `Person` dla autora,
- spójny `Organization` jako publisher.

Nie deklarujemy schema, którego treść nie wspiera.

### 15.3. Sitemap

Docelowo:

- zwykła sitemap artykułów,
- osobna sitemap newsroomowa zgodna z aktualnymi wymaganiami wyszukiwarek,
- istniejący sitemap index rozszerzony o nowe zasoby.

Wymagania zewnętrznych platform należy zweryfikować ponownie w momencie implementacji zamiast zamrażać w kodzie nieaktualne limity.

### 15.4. Feed

V1 powinno przewidywać:

- RSS lub Atom dla najnowszych publikacji,
- opcjonalne feedy per kategoria później.

### 15.5. Canonical i duplikaty

Ten sam artykuł nie może żyć równolegle jako:

- `/aktualnosci/x`,
- `/poradniki/x`,
- `/kategoria/x`.

Typ i canonical URL są stabilne po publikacji.

---

## 16. Standard redakcyjny i źródła

### 16.1. Zasada źródła pierwotnego

Dla zmian prawa, administracji i egzaminów preferowane są:

- akty prawne,
- oficjalne komunikaty,
- strony ministerstw i instytucji,
- dokumenty publiczne,
- bezpośrednie dane źródłowe.

Media konkurencyjne mogą być źródłem tropu, ale nie powinny automatycznie zastępować źródła pierwotnego.

### 16.2. Rozdzielenie faktu i interpretacji

Artykuł powinien odróżniać:

- co zostało oficjalnie opublikowane,
- od kiedy obowiązuje,
- kogo dotyczy,
- co jest projektem lub zapowiedzią,
- co jest interpretacją redakcyjną.

### 16.3. Korekty

Musi istnieć proces korekt:

- błąd merytoryczny poprawiamy,
- przy istotnej korekcie odnotowujemy zmianę,
- nie podmieniamy historii tak, żeby zmieniać sens bez śladu.

### 16.4. AI

AI może wspierać:

- research,
- streszczenia robocze,
- tagowanie,
- propozycje linków,
- checklistę braków.

AI nie powinno samodzielnie publikować newsów bez review człowieka.

---

## 17. Media i obrazy

### 17.1. Wymagania

Każdy hero:

- ma alt,
- ma jawne wymiary,
- ma zoptymalizowany format,
- ma punkt zainteresowania/focal point,
- ma wariant do OG i poprawny alt właściwy dla tego wariantu,
- publiczny SEO image URL jest stabilny, crawlable i nie wymaga auth/signed expiry,
- może otrzymać deterministyczne cropy do lead/standard/compact,
- nie powoduje CLS,
- publiczny URL jest rozwiązywany przez istniejący `MediaUrlResolver`/media config,
- upload newsroomu ma własny adapter/service lub jawny Filament upload contract; obecny `AdminMediaUploadService` jest question-specific i nie jest genericznym uploaderem newsroomu,
- nie deklaruje fizycznych crop variants, których system realnie nie wygenerował.

### 17.2. Prawa do materiałów

Nie kopiujemy losowych zdjęć z internetu.

Każdy asset powinien mieć znane pochodzenie:

- własny,
- licencjonowany,
- oficjalny materiał możliwy do wykorzystania,
- asset generowany zgodnie z polityką projektu.

### 17.3. Video

Video w newsroomie jest przyszłym rozszerzeniem. V1 nie wymaga osobnego playera redakcyjnego.

---

## 18. Dystrybucja

Newsroom powinien być projektowany pod wiele kanałów, ale źródłem treści pozostaje własna strona.

Kanały:

- Google Search,
- Google News / Top stories, jeśli zewnętrzne systemy zakwalifikują content,
- Google Discover,
- Google Preferred Sources jako opcja post-launch, jeśli domena jest dostępna w narzędziu,
- social media,
- newsletter w przyszłości,
- RSS,
- linkowanie wewnętrzne,
- partnerstwa OSK,
- materiały własne oparte na danych.

Nie implementujemy osobnych pipeline’ów dla każdego kanału przed uruchomieniem podstawowego newsroomu.

---

## 19. Analityka

### 19.1. Minimalne eventy

- article_view,
- article_scroll_depth,
- related_article_click,
- related_question_click,
- product_cta_click,
- category_click,
- source_click.

### 19.2. KPI newsroomu

Nie oceniamy newsroomu wyłącznie liczbą page views.

Obserwujemy:

- organic impressions,
- organic clicks,
- CTR,
- returning readers,
- article → question CTR,
- article → test CTR,
- article → registration CTR,
- średnią liczbę stron na wejście informacyjne,
- udział ruchu branded vs non-branded,
- widoczność kategorii i tematów.

### 19.3. Najczęściej czytane

Moduł „Najczęściej czytane” nie może opierać się na przypadkowym liczniku odsłon bez okna czasowego.

Wdrożenie wymaga jawnej definicji:

- zakres czasu,
- filtr botów,
- minimalny próg,
- zasada tie-break.

---

## 20. Wydajność

### 20.1. Założenia

Strony artykułów są read-heavy i powinny być tanie w obsłudze.

Wymagania:

- brak N+1,
- eager loading relacji potrzebnych do renderu,
- cache dla stabilnych elementów nawigacji i list,
- optymalizowane obrazy,
- brak ciężkiego JS do podstawowego czytania,
- lazy loading obrazów poza LCP,
- limit liczby modułów i kart per request.

### 20.2. Cache invalidation

Publikacja lub aktualizacja artykułu musi unieważniać:

- cache artykułu,
- cache właściwej kategorii,
- cache modułów strony `/aktualnosci`,
- feed,
- sitemap jeśli jest generowana/cachowana.

### 20.3. Crawler-facing HTTP caching

Sitemap i feed powinny mieć cache validators (`ETag` i/lub `Last-Modified`) i odpowiadać `304 Not Modified`, gdy reprezentacja się nie zmieniła.

Aktualny `SitemapController` nie ma tej warstwy; jest to zaplanowana praca, nie stan istniejący.

---

## 21. Bezpieczeństwo i audyt

- publiczne endpointy są read-only,
- zapis wyłącznie przez autoryzowany backoffice,
- preview v1 wymaga istniejącej autoryzacji administratora, jest `private, no-store` i nie używa shareable signed URL; ewentualny przyszły signed preview wymaga osobnego threat modelu,
- body i embed content muszą być sanityzowane,
- audytujemy publikację i istotne edycje,
- uploady przechodzą istniejące zasady mediów,
- nie dopuszczamy arbitrary HTML/JS od redaktora bez sanitizacji.

---

## 22. Test strategy

### 22.1. Backend

Testy:

- status workflow,
- publikacja i scheduling,
- slug uniqueness,
- canonical redirect,
- unpublished 404/noindex behavior,
- relacje article-question/legal,
- sitemap inclusion + deterministic sharding,
- news namespace / first_published_at eligibility,
- feed inclusion + discovery,
- conditional 304 dla sitemap/feed,
- stabilne site identity graph IDs,
- permission checks.

### 22.2. Frontend / rendering

Testy:

- meta,
- canonical,
- OG,
- structured data graph i spójność @id,
- WebSite/Organization/site name consistency,
- visible dates zgodne z JSON-LD,
- breadcrumbs,
- hero dimensions,
- related content,
- mobile layout.

### 22.3. E2E

Golden path:

```text
admin creates draft
→ adds source
→ links questions
→ preview
→ publish
→ article visible publicly
→ article appears in category/hub
→ CTA opens correct product surface
```

Drugi golden path:

```text
scheduled article
→ not public before time
→ becomes public after time
→ appears in latest list
```

---

## 23. Etapy wdrożenia

Szczegółowy tasking i granice PR-ów są kanonicznie utrzymywane w [NEWSROOM-IMPLEMENTATION-BACKLOG.md](./NEWSROOM-IMPLEMENTATION-BACKLOG.md). Na poziomie architektury obowiązuje:

### Etap N0 — foundation

- publisher/Organization branding source of truth,
- finalny route contract,
- taxonomy v1,
- block editor + serialization + sanitization + format-evolution decision,
- existing SEO delivery compatibility contract,
- admin identity/authorization contract.

**Exit criteria:** wszystkie decyzje wymagane przez konkretny downstream gate są zamknięte. N0 nie jest sztuczną barierą „wszystko albo nic”; szczegółową macierz zależności utrzymuje backlog.

### Etap N1 — domain + database

- migrations,
- enumy,
- models/factories,
- topics,
- home placements,
- slug redirects,
- publishing service,
- scheduler,
- home composition service.

**Exit criteria:** domena może bezpiecznie przechować i deterministycznie opublikować artykuł bez CMS i publicznego renderera.

### Etap N2 — CMS + workflow

- ContentCategoryResource,
- ContentTopicResource,
- ContentArticleResource,
- controlled block editor,
- origin/regulatory context,
- media focal point/crop preview,
- źródła,
- relacje,
- workflow actions,
- checklist,
- article preview,
- NewsroomHomeComposer + future preview.

**Exit criteria:** redaktor może przygotować, sprawdzić, podejrzeć, zaplanować i opublikować artykuł bez edycji kodu.

### Etap N3 — publiczny artykuł

- public catalog service,
- SEO/schema services,
- Blade article page,
- controlled block renderer,
- provenance + regulatory context,
- focal-point-aware media,
- sources/byline,
- product bridge,
- old-slug redirects.

**Exit criteria:** pojedynczy opublikowany artykuł jest poprawnie renderowany, indeksowalny i połączony z istniejącym produktem.

### Etap N4 — hub + kategorie + topics + poradniki

- editorial home `/aktualnosci` z placements/fallback/dedupe,
- category pages,
- topic/dossier pages,
- `/poradniki`,
- navigation integration,
- cache/invalidation.

**Exit criteria:** portal działa jako redakcyjny system wejść, a nie pojedyncza strona artykułu.

### Etap N5 — SEO + dystrybucja + analytics

- articles sitemap,
- news sitemap,
- RSS/Atom,
- analytics events,
- IndexNow review,
- link/content audit.

**Exit criteria:** publiczny corpus ma pełną warstwę dystrybucji i obserwowalności.

### Etap N6 — hardening + pierwszy rollout

- E2E golden paths,
- production scheduler smoke,
- production SEO validation,
- performance/security pass,
- pierwszy kontrolowany batch treści,
- Search Console observation.

**Exit criteria:** newsroom jest operacyjnie gotowy do regularnej publikacji.

### Po v1 — rozszerzenia zależne od danych

Dopiero po realnym ruchu i obserwacji:

- „najczęściej czytane”,
- personalizacja,
- lokalne huby WORD,
- newsletter,
- większe raporty własne,
- ewentualna rozbudowa home `/`.

---
## 24. Definition of Done v1

Newsroom v1 jest ukończony, gdy:

- artykuł powstaje bez zmiany kodu,
- ma autora, kategorię, origin, źródła i workflow,
- treść korzysta z kontrolowanego body_blocks,
- preview artykułu działa bez publicznej indeksacji,
- home composer i future preview działają na tym samym resolverze co publiczny hub,
- publikacja i scheduling działają deterministycznie,
- `/aktualnosci` ma redakcyjną hierarchię, placements, fallback i deduplikację,
- istnieje publiczna strona artykułu z regulatory context i focal-point-aware media,
- publikowany topic/dossier ma własną wartość i nie jest aliasem taga,
- canonical/meta/schema graph są poprawne i spójne z domenowym site identity,
- statyczny sitemap pipeline uwzględnia właściwe rekordy, pełne news metadata, deterministic sharding i bezpieczny child-before-index switch,
- newsroom refresh sitemap jest coalesced/debounced bez założenia o działającym Laravel queue workerze; istniejący daily refresh pozostaje safety netem,
- rzeczywista warstwa static/Nginx/CDN ma zweryfikowane nagłówki/cache validators bez zakładania, że Laravel controller serwuje produkcyjny XML,
- feed ma własny poprawny cache/validator contract,
- author ProfilePage/Person jest reużywany, nie duplikowany,
- artykuł może być połączony z pytaniami i treścią prawną,
- działa co najmniej jeden kontekstowy most do produktu,
- są testy backend + rendering + E2E,
- nie ma niespójności publishera/brandingu,
- dokumentacja odzwierciedla faktyczny stan wdrożenia.

---

## 25. Decyzje zamknięte i otwarte przed kodowaniem

### 25.1. Decyzje zamknięte przez pakiet projektowy

1. `guide`, `news`, `explainer`, `analysis` i `report` korzystają ze wspólnego agregatu `content_articles`.
2. Źródła są osobną relacyjną tabelą `content_article_sources` już w v1.
3. Reviewer nie jest wymagany dla wszystkich newsów; wymóg wynika z typu/ryzyka materiału.
4. Lokalne huby WORD nie wchodzą do v1; model ma nie blokować ich późniejszego dodania.
5. `/` pozostaje produktowym home w v1.
6. Page builder nie wchodzi do v1; stosujemy kontrolowane moduły.
7. Komentarze użytkowników są poza scope v1.
8. Kategorie używają ścieżki `/aktualnosci/kategoria/{categorySlug}`.
9. Topic/dossier używa ścieżki `/aktualnosci/temat/{topicSlug}`.
10. Feed v1 używa `/aktualnosci/feed.xml`.
11. Publiczne strony newsroomu są SSR/Blade-first.
12. Strona główna korzysta z kontrolowanych placements i fallbacków, nie z pełnego page buildera.
13. Body artykułu jest kontrolowanym dokumentem blokowym; N0-004 zamknęło serializację jako `NewsroomBodyContract` v1 z canonical list `{key?, type, data}` i structured TipTap JSON dla rich text.
14. Topic jest odrębnym bytem od taga.
15. Media używają focal point i deterministycznych cropów, jeśli pipeline je wspiera.
16. Audio/AI są rozszerzeniami po v1, bez prealokowania schema.
17. Osobny revision snapshot/diff/restore system pozostaje poza zakresem.
18. Kanoniczne dane Organization pozostają w istniejącym `config/content.php['organization']`; nie tworzymy równoległego brand configu.
19. Structured data newsroomu korzysta ze stabilnych `SchemaIds` i graph pattern zamiast izolowanych kopii encji.
20. Sitemap subsystem rozszerza istniejące `SeoSitemapGenerator`/`SeoSitemapBuilder`/`SeoSitemapAuditor`; statyczny pipeline produkcyjny pozostaje nadrzędny.
21. Domena ma jeden site name; `/aktualnosci` nie tworzy osobnego site name.
22. Google Preferred Sources jest opcją post-launch, nie gate v1 ani obietnicą widoczności.
23. Semantic silo ma jedną primary category per article; topics/tags nie tworzą konkurencyjnego canonical parent.
24. Jawne relacje do pytań/przepisów/znaków mogą renderować kontrolowane reverse links, bez sitewide reciprocal-link farm i bez modyfikowania istniejącego question relation graphu.
25. `canonical_url` override nie jest częścią newsroom v1: artykuły są self-canonical zgodnie z route contract.
26. Po pierwszej publikacji route family artykułu jest stabilne; cross-family type change jest zablokowany w zwykłym CMS.
27. Produkcyjne sitemap newsroomu są statycznymi artefaktami publikowanymi bez okna index -> brakujący child; controller routes pozostają kompatybilnością, nie drugim source of truth.
28. V1 nie rozszerza dostępu do Filament: `/admin` pozostaje dostępne wyłącznie dla istniejących administratorów. `User` jest aktorem operacji/audytu, a `ContentAuthor` jest publiczną tożsamością autora/reviewera.
29. Workflow `archived` oznacza wycofanie z aktywnej dystrybucji, nie automatyczne usunięcie URL. Wcześniej opublikowany artykuł archiwalny pozostaje pod canonical URL jako 200; 301/404/410 wymagają osobnego, jawnego use case.
30. Side effecty po publikacji nie mogą zależeć od nieistniejącego workera. Przy obecnym `QUEUE_CONNECTION=sync` v1 używa lekkiego dirty/version signal + scheduler/lock do coalesced refreshu albo dopiero po wdrożeniu monitorowanego async transportu może użyć queued job.
31. Publiczny newsroom ma prosty config gate/feature flag. Do N6 można wdrażać dane, admin i renderer bez przełączania istniejących publicznych placeholderów/indeksacji; włączenie publiczne następuje dopiero po release gate. Gdy gate=false, wyłączone są także article discovery w author pages/reverse links, feed, newsroom sitemap entries i IndexNow — private admin preview pozostaje dostępne.
32. `archived` oznacza historyczny canonical 200 poza aktywną dystrybucją; osobny `withdrawn` służy do jawnego takedownu i daje 410 bez treści (albo 301 przy realnym następcy).
33. Historyczny publiczny article path jest trwałą rezerwacją względem innych artykułów; current slug uniqueness nie wystarcza bez sprawdzenia redirect history.
34. Freshness overdue jest computed kolejką review, nie automatycznym `needs_review`; workflow zmienia się tylko przez jawną/audytowaną decyzję.
35. Media URL resolution jest współdzielone, ale upload nie: question-specific `AdminMediaUploadService` nie może zostać użyty jako newsroom uploader bez osobnej adaptacji.
36. Po pierwszym publicznym launch `NEWSROOM_PUBLIC_ENABLED=false` nie jest długotrwałym technicznym rollbackiem, jeśli tworzyłby masowe 404; awaria techniczna używa 503/Retry-After lub code rollback, a content takedown używa `withdrawn`.
37. Ponieważ v1 świadomie nie ma revision/staging systemu, zwykły Save nie może po cichu modyfikować publicznych pól już opublikowanego artykułu. Publiczna zmiana istniejącego 200 przechodzi przez dedykowany atomowy use case `Apply public update`; review-before-live dla takich zmian wymaga w przyszłości osobnej decyzji o staging/revisions.

### 25.2. Otwarte decyzje N0 wymagające domknięcia przed implementacją zależnych elementów

- finalne wspólne design tokens używane przez newsroom po audycie obecnego publicznego UI.

Pozostałe szczegóły nie powinny blokować N1, jeśli nie wpływają na schema, bezpieczeństwo body albo publiczny routing.

---
## 26. Ukończone prace związane z tym obszarem

Na moment utworzenia dokumentu za ukończone uznajemy wyłącznie elementy rzeczywiście istniejące w kodzie:

- publiczny shell SEO,
- autorzy,
- publiczne profile autorów,
- część warstwy trust/legal,
- breadcrumbs,
- statyczny sitemap generator + builder/auditor + daily refresh,
- statyczny public/robots.txt oraz istniejący RobotsController,
- SchemaIds/SchemaRenderer i organization config,
- NEWSROOM-N0-001: współdzielony `SiteIdentitySchema` dla Organization/WebSite,
- homepage oparty o `config/content.php['organization']`, stabilne `/#organization` i `/#website`, bez legacy „Orły na Drodze”,
- wspólny `og:site_name` oraz Organization logo ImageObject z potwierdzonym baseline 256×256,
- traffic-sign/public-question/legal-content graph services reużywające wspólnego site identity buildera,
- NEWSROOM-N0-002: `NewsroomRouteContract`, finalne route namespaces, reserved slug policy i route-family transition guard,
- NEWSROOM-N0-003: `NewsroomTaxonomyContract` v1 z sześcioma kategoriami, nazwami publicznymi i deterministyczną kolejnością,
- NEWSROOM-N0-004: `NewsroomBodyContract` v1 z canonical block list, structured TipTap rich text, ścisłymi payload schemas i disabled embed,
- routes `/aktualnosci` i `/poradniki` jako dedykowane pre-launch 200/noindex placeholders,
- placeholdery tych tras,
- publiczna nawigacja prowadząca do aktualności,
- istniejące klastry pytań, znaków i przepisów, które mogą zostać powiązane z artykułami.

**Nie uznajemy jeszcze właściwego newsroomu (domain/CMS/public content) za zaimplementowany; ukończone są foundation tasks NEWSROOM-N0-001, NEWSROOM-N0-002, NEWSROOM-N0-003 i NEWSROOM-N0-004. Modele/tabele, N2 article editor i N3 renderer nadal nie istnieją.**

---

## 27. Pozostałe zadania

Szczegółowym źródłem backlogu jest [NEWSROOM-IMPLEMENTATION-BACKLOG.md](./NEWSROOM-IMPLEMENTATION-BACKLOG.md). Najbliższa kolejność:

- [x] `NEWSROOM-N0-001` — publisher branding source of truth,
- [x] `NEWSROOM-N0-002` — test/utrwalenie przyjętego route contract,
- [x] `NEWSROOM-N0-003` — deterministyczny taxonomy seed contract,
- [x] `NEWSROOM-N0-004` — block editor + serialization + sanitization + format-evolution decision,
- [x] `NEWSROOM-N0-005` — compatibility decision istniejącego SEO delivery jest udokumentowana; kodowy regression gate pozostaje w N5,
- [ ] `NEWSROOM-N0-006` — media upload/storage contract,
- [ ] następnie wykonywać N1 zgodnie z macierzą hard gates z backlogu.

Pozostałe elementy N2–N6 są celowo utrzymywane w wykonawczym backlogu zamiast dublować tu checklistę.

---
## 28. Zasady utrzymania dokumentu

Po każdej implementacji dotyczącej newsroomu dokument musi zostać zaktualizowany tak, aby osobno pokazywał:

- **decyzje architektoniczne**,
- **aktualny stan implementacji**,
- **ukończone prace**,
- **pozostałe zadania**,
- **historię zmian**.

Nie wolno opisywać planu jako stanu wdrożonego.

Jeżeli implementacja odchodzi od tego dokumentu, należy:

1. zaktualizować decyzję,
2. podać powód,
3. opisać wpływ,
4. dopiero potem traktować nowy stan jako kanoniczny.

---

## 29. Historia zmian

### 2026-09-16 — v0.10

- wdrożono i zmergowano NEWSROOM-N0-004 po green CI,
- utrwalono `NewsroomBodyContract` v1 jako canonical block-format/validation contract,
- Filament Builder pozostaje adapterem przyszłego N2 UI, a rich text ma structured TipTap JSON zamiast raw HTML,
- `embed` jest wyłączony do czasu osobnego provider/CSP security gate,
- reader-before-writer i explicit migration pozostają warunkiem przyszłej ewolucji body schema,
- właściwy CMS i public renderer nie są jeszcze wdrożone,
- uporządkowano tę samą checklistę: N0-005 ma zamkniętą decyzję dokumentacyjną, N0-006 pozostaje otwartym foundation contractem.

### 2026-09-16 — v0.9

- wdrożono i zmergowano NEWSROOM-N0-003 po green CI,
- dodano `NewsroomTaxonomyContract` v1 jako wykonywalny source of truth sześciu kategorii newsroomu,
- utrwalono publiczne nazwy, slugi i kolejność 10..60 bez zatwierdzania jeszcze description/SEO copy,
- zamknięto G0-A routing/taxonomy jako foundation dependency gate,
- pozostawiono `content_categories`, model `ContentCategory` i rzeczywisty DB seeder do N1; przyszły seeder ma konsumować kontrakt.

### 2026-09-16 — v0.8

- wdrożono i zmergowano NEWSROOM-N0-002 po green CI,
- utrwalono finalne route namespaces, regex slugów, reserved segments i type -> route family w `NewsroomRouteContract`,
- pre-launch `/aktualnosci` i `/poradniki` zachowują placeholder UX z `X-Robots-Tag: noindex, follow`,
- future feed/category/topic/detail routes są zarejestrowane, ale pozostają 404 bez publicznych controllerów,
- record-level ContentArticle route-family lookup pozostaje zadaniem downstream,
- poprawiono bieżący opis historycznie zamkniętej niespójności brandingu oraz usunięto przestarzały snapshot SHA z sekcji aktualnego stanu.

### 2026-09-16 — v0.7

- wdrożono NEWSROOM-N0-001 i zamknięto branding/site-identity foundation gate,
- dodano wspólny `SiteIdentitySchema` oparty o istniejący organization config i stabilne SchemaIds,
- homepage przestał emitować legacy „Orły na Drodze” i korzysta z tego samego Organization/WebSite graph co istniejące publiczne moduły,
- dodano kanoniczne `og:site_name`, WebSite alternateName oraz zweryfikowane wymiary publicznego logo,
- usunięto site-identity builder z listy otwartych decyzji N0; następnym taskiem jest N0-002.

### 2026-09-16 — v0.6

- wykonano finalny audyt planu implementacji, admin panelu, gate'ów i SEO względem aktualnego main,
- rozdzielono tożsamość `User` (aktor/admin) od `ContentAuthor` (autor/reviewer),
- zdefiniowano deterministic archive URL policy zamiast pozostawiania 200/404/410 do decyzji podczas kodowania,
- usunięto założenie o działającym queue workerze dla sitemap freshness,
- dodano publiczny config gate dla bezpiecznego rollout/rollback obejmujący wszystkie kanały discovery,
- oddzielono historyczne archived=200 od jawnego withdrawn=410 takedown,
- zsynchronizowano nadrzędny model z body_schema_version/public_state/withdrawal/public-citation contracts,
- zamknięto lukę „Save zmienia live content” przy braku revision systemu przez dedykowany atomic Apply public update contract,
- doprecyzowano historyczne path reservation, overdue-vs-needs_review i faktyczną granicę media upload layer,
- doprecyzowano, że N0 ma dependency gates, a nie sztuczną sekwencję blokującą każdy N1 task.

### 2026-09-16 — v0.5

- po głębokim audycie zgodności z istniejącym backendem podporządkowano newsroom istniejącemu statycznemu pipeline sitemap/robots i question graphowi,
- usunięto ze skrótu architektury stare `body` i ręczny `canonical_url`,
- zdefiniowano stabilność route family po pierwszej publikacji,
- historycznie zapisano kontrakt async/debounced sitemap refresh bez usuwania controller routes; **zastąpiony w v0.6** przez dirty/version + scheduler/lock po audycie `QUEUE_CONNECTION=sync`,
- doprecyzowano, że newsroom reverse links nie modyfikują istniejącego question-question graphu.

### 2026-09-16 — v0.4

- wykonano enterprise SEO audit względem aktualnego main i oficjalnych wymagań Google,
- skorygowano branding source of truth: istniejący config/content.php + SchemaIds/SchemaRenderer zamiast nowego równoległego configu,
- przyjęto jeden stabilny entity graph dla domeny i newsroomu,
- dodano sitemap sharding readiness, news namespace completeness, feed discovery i conditional 304,
- doprecyzowano author ProfilePage reuse, stable SEO image URLs i visible/schema date consistency,
- Google Preferred Sources zapisano jako opcję post-launch, nie wymóg,
- stan implementacji nadal jawnie pozostaje przed newsroomem.

### 2026-09-15 — v0.3

- przyjęto kontrolowane homepage placements jako część newsroom v1,
- przyjęto kontrolowany dokument blokowy artykułu zamiast nieograniczonego HTML,
- rozdzielono topic/dossier od tagów,
- dodano art direction przez focal point i generowane warianty obrazu,
- zaplanowano audio/AI jako rozszerzenia po v1 bez prealokowania schema,
- zapisano decyzję o braku osobnego revision-history snapshot systemu.

### 2026-09-15 — v0.2

- dodano mapę siedmiu wykonawczych dokumentów newsroomu i reguły rozstrzygania konfliktów,
- ujednolicono routing kategorii do `/aktualnosci/kategoria/{categorySlug}`,
- oznaczono szczegółowy Data Model Spec jako normatywny kontrakt schema,
- wyrównano etapy N0–N6 z wykonawczym backlogiem.

### 2026-09-15 — v0.1

- utworzono kanoniczny projekt newsroomu,
- zapisano stan istniejącego repo,
- potwierdzono użycie tego samego repo, domeny i modularnego monolitu,
- przyjęto SSR-first dla publicznej warstwy newsowej,
- zaprojektowano taxonomy, data model, workflow, Filament, portal hub, article page, SEO, dystrybucję i testy,
- wydzielono etapy N0–N6,
- wskazano niespójność publisher/brandingu jako gate przed schema `NewsArticle`.
