# Newsroom / Media Portal Architecture

## 1. Status dokumentu

- **Status:** Canonical architecture + live implementation status
- **Obszar:** publiczny serwis informacyjny, newsroom, aktualności, poradniki i dystrybucja treści
- **Repozytorium:** `prawkonaraz100/prawkonaraz`
- **Bazowy stan kodu:** `main@3d7ac8ab8a3ed1c299cb0cdd4cb1ef8ac6b53f78`
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

Stan sprawdzony ponownie 2026-09-18 względem `main@18cd07233e3c8712cf2c7fc9e0c32018baef65d3` po wdrożeniu N0, N1-001..N1-006, N2-001..N2-012, NEWSROOM-N3-001..N3-008, NEWSROOM-N4-001..N4-008 oraz NEWSROOM-N5-001..N5-003. Zakres admin/domain N2, public article layer N3 i public IA/semantic layer N4 są zamknięte. N5-001/N5-002 rozszerzają istniejący static sitemap pipeline, a N5-003 materializuje rollout-gated Atom feed + discovery, generation cache i HTTP validators; pozostałe N5/N6 pozostają otwarte.

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
- N3-007: profil `/autorzy/{slug}` agreguje indeksowalne publikacje Newsroomu z aktywnych kategorii; `published` trafia do bieżących publikacji, `needs_review` do osobnej sekcji „W trakcie weryfikacji”, a `archived` do osobnego „Archiwum”,
- N3-007: `ContentAuthorSchemaService` jest współdzielonym builderem `ProfilePage -> Person`; ten sam stabilny `/autorzy/{slug}#person` jest używany przez profil i article graph, a `worksFor` wskazuje `/#organization`,
- N3-007: author sitemap kwalifikuje również autorów z indeksowalnym Newsroom corpus i używa `public_state_changed_at` dla newsroomowej świeżości zamiast technicznego `updated_at`,
- N3-007: model `ContentAuthor` blokuje odpublikowanie autora, dopóki zależny artykuł Newsroomu pozostaje indexable; noindex usuwa tę blokadę,
- N3-008: `config/newsroom.php` + `NewsroomPublicGate` centralizują `NEWSROOM_PUBLIC_ENABLED` z bezpiecznym defaultem `false`; `.env.example` również utrwala `false`,
- N3-008: przy gate=false publiczne article/guide detail oraz historyczne old-path redirecty failują do 404 przed lookupem/redirect resolverem; top-level `/aktualnosci` i `/poradniki` pozostają pre-launch placeholderami 200 + `X-Robots-Tag: noindex, follow`,
- N3-008: gate=false usuwa Newsroom z author profile oraz newsroom-only author sitemap eligibility/freshness i filtruje namespace `/aktualnosci` + `/poradniki` z istniejącego `IndexNowUrlCollector`; admin/private preview pozostają dostępne,
- N4-001: istniejący `NewsroomHomeCompositionService` pozostaje jedynym resolverem fixed placements/fallback/dedupe; category composition korzysta z batched placements, per-category SQL window rankingu i wspólnego eager-loadu zamiast query-per-category,
- N4-001: `NewsroomHomeReadModelService` wystawia rollout-gated scalar-array projection lead/secondary/latest/categories/guides/important_now/breaking z canonical path, category/author i hero metadata; wynik jest serializowalny i gotowy do późniejszego cache,
- N4-002: przy `NEWSROOM_PUBLIC_ENABLED=true` `NewsroomPlaceholderController::news()` konsumuje istniejący `NewsroomHomeReadModelService` i renderuje SSR `newsroom.home` z self-canonical oraz `index,follow,max-image-preview:large`; przy gate=false zachowuje wcześniejszy placeholder 200 + `X-Robots-Tag: noindex, follow`,
- N4-003: istniejący route `public.news.categories.show` jest podłączony do `NewsroomCategoryController`; przy gate=false category route failuje do 404, a przy gate=true aktywna kategoria renderuje SSR `newsroom.category`,
- N4-003: `NewsroomCategoryReadModelService` reużywa `ContentArticlePublicCatalogService::activelyDistributedQuery()` dla newsroom-family corpus, sortuje `first_published_at DESC, id DESC`, paginuje po 20 rekordów i emituje tylko public-safe scalar data,
- N4-003: `NewsroomCategorySchemaService` emituje `CollectionPage`, `BreadcrumbList` i — tylko przy niepustym corpus — `ItemList`; pusta aktywna kategoria pozostaje użytecznym 200, ale ma `noindex,follow`,
- N4-004: istniejący `NewsroomPlaceholderController::guides()` pozostaje rollout switchem dla `public.guides`; gate=false zachowuje pre-launch placeholder 200 + `X-Robots-Tag: noindex, follow`, a gate=true renderuje SSR `newsroom.guides`,
- N4-004: `NewsroomGuideHubReadModelService` reużywa `ContentArticlePublicCatalogService::activelyDistributedQuery(NewsroomRouteContract::FAMILY_GUIDES)`, sortuje `first_published_at DESC, id DESC`, paginuje po 20 i emituje public-safe guide data,
- N4-004: aktywny pusty guide hub renderuje użyteczny 200 + `noindex,follow`; invalid/out-of-range `page` failuje do 404, a `NewsroomGuideHubSchemaService` emituje `CollectionPage`, `BreadcrumbList` i conditional `ItemList`,
- N4-005: `PublicNavigation::primary` zachowuje pojedyncze kanoniczne wpisy „Aktualności” i „Poradniki” z dotychczasowymi active-state prefixami; `documentNavigationPrefixes` już zawiera `/aktualnosci` i `/poradniki` i nie zostało zmienione,
- N4-005: `PublicFooter::service_links` dodaje dokładnie po jednym wejściu do `/aktualnosci` i `/poradniki`; wspólne dane konsumują oba aktualne renderery compact footera — Vue `SiteFooter.vue` i Blade `public-footer.blade.php`,
- N4-006: `NewsroomPublicReadCache` cache’uje istniejące publiczne home/category read models przez generation-based keys; TTL 60 s jest safety netem, a generation rotation daje store-agnostic invalidation bez enumeracji kluczy,
- N4-006: after-commit invalidation reużywa `ContentArticleWorkflowTransitioned` dla wejścia/wyjścia z `published`, dodaje `ContentArticlePublicReadChanged` dla aktywnych public/exposure updates, `ContentHomePlacementChanged` dla home placementów oraz after-commit `ContentCategoryObserver` dla metadata kategorii,
- N4-006: placement invaliduje tylko home; article workflow/public update/category invaliduje home + category. Preview, guide hub, article detail, topic/feed i N5 dirty/version/sitemap/IndexNow nie są objęte tym cache layerem,
- N4-007: istniejący route `public.news.topics.show` jest podłączony do `NewsroomTopicController`; gate=false/draft/future/unknown failują do 404 + noindex, wcześniej publiczny archived topic zwraca 410 + noindex, a opublikowany topic renderuje SSR `newsroom.topic`,
- N4-007: `NewsroomTopicReadModelService` reużywa istniejący `ContentTopic` i jawny pivot; corpus to `activelyDistributed()+indexable()` dla news/explainer/analysis/report/guide, featured jest opcjonalnym pojedynczym leadem, a lista sortuje `first_published_at DESC, id DESC` i paginuje po 20,
- N4-007: późniejszy spadek corpus poniżej publication baseline 3 nie zmienia automatycznie HTTP/indexability; `NewsroomTopicSchemaService` emituje `CollectionPage`, `BreadcrumbList` i `ItemList`, a dalsze strony mają self-canonical `?page=N`,
- route `/autorzy/{authorSlug}`,
- statyczny produkcyjny pipeline sitemap `SeoSitemapGenerator` + `SeoSitemapBuilder` + `SeoSitemapAuditor`, z codziennym `seo:refresh-sitemaps` jako istniejącym safety netem,
- N5-001: ten sam pipeline generuje rollout-gated standard article sitemap; przy małym corpus `/sitemaps/articles.xml`, a po przekroczeniu zakresu bezpośrednie fixed-ID-range shards wpisane do root `/sitemap.xml`, bez OFFSET i bez nested newsroom sitemap-index,
- N5-001: `static.xml` obejmuje newsroom home, warunkowo guides, active categories i published topics; article `lastmod` używa domenowych public-state timestamps, a generator/auditor egzekwują 50 000 entries / 50 MB,
- N5-002: ten sam pipeline generuje rollout-gated Google News Sitemap dla świeżych `type=news`, `published`, indexable current-canonical articles z aktywną kategorią/publicznym autorem; `news:name` reużywa `SiteIdentitySchema::siteName()`, language=`pl`, publication date=`first_published_at`, title=widoczny article title,
- N5-002: przy <=1000 entries używany jest `/sitemaps/news.xml`, a powyżej limitu fixed-`content_articles.id` range shards są wpisywane bezpośrednio do root `/sitemap.xml`; N5-006 domknęło news namespace/age/tag/eligibility/topology audit w istniejącym `SeoSitemapAuditor`,
- N5-003: `/aktualnosci/feed.xml` zwraca przy gate=true jeden Atom 1.0 feed z latest `type=news`, stabilnym `urn:prawkonaraz:content-article:{id}`, canonical links, `published`/`updated`, summary/autorem, generation cache, ETag/Last-Modified/304 i bez session cookie; public layout emituje Atom discovery,
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
- publiczna nawigacja z pojedynczymi pozycjami „Aktualności” i „Poradniki”; N4-005 uzupełnia wspólny compact footer o oba huby bez duplikowania primary navigation,
- route `/aktualnosci` i `/poradniki` z zachowanymi top-level route names,
- wykonywalny `NewsroomRouteContract`,
- zarejestrowane route namespaces feed/category/topic/article zgodne z finalnym kontraktem,
- `ContentArticlePublicCatalogService`, `ContentArticleSeoService` i `ContentArticleSchemaService`,
- `ContentArticleController` dla route families news/guides,
- `NewsroomArticlePresentationService`, `NewsroomArticleBodyRenderer` i `NewsroomArticleProductBridgeService`,
- Blade `newsroom.article`, neutralny `newsroom.article-unavailable` oraz `newsroom.product-bridge-block`,
- publiczne article detail 200/404/410 zgodne z catalog resolution i fail-closed handling,
- initial HTML article detail z canonical/OG/article dates/JSON-LD, breadcrumbs, byline/provenance, hero, regulatory context, public sources, correction i author box,
- publiczne, fail-closed Product Bridge blocks dla jawnie powiązanych pytań, podstaw prawnych, znaków i contextual CTA,
- dedykowany browser QA article detail z JS disabled na 360/390/430/768/1024/1440,
- SEO/content roadmap,
- Filamentowy workflow newsroomu.

### 5.2. Elementy nadal pre-launch / odroczone

`/aktualnosci` jest po N4-002 dwustanową powierzchnią rolloutową w istniejącym `NewsroomPlaceholderController`: przy `NEWSROOM_PUBLIC_ENABLED=false` nadal renderuje `Public/MarketingPlaceholder.vue` jako 200 + `X-Robots-Tag: noindex, follow`, a przy `true` renderuje SSR Blade `newsroom.home` z danych `NewsroomHomeReadModelService`. `/poradniki` od N4-004 używa tego samego globalnego gate: przy `false` zachowuje pre-launch placeholder 200 + noindex, a przy `true` renderuje guide-only SSR `newsroom.guides`.

Następujący namespace nadal pozostaje downstream:

- `/aktualnosci/feed.xml`.

Category namespace `/aktualnosci/kategoria/{categorySlug}` jest od N4-003 aktywny wyłącznie przy `NEWSROOM_PUBLIC_ENABLED=true`; przy wyłączonym gate failuje do 404.

Detail routes są już podłączone:

- `/aktualnosci/{articleSlug}`,
- `/poradniki/{articleSlug}`.

To oznacza, że:

- adresy, IA i matching/order contract istnieją,
- model domenowy artykułów/kategorii/tagów/topiców i relacji oraz backendowy publishing/scheduling foundation istnieją,
- publiczny hub `/aktualnosci` istnieje po N4-002 przy włączonym gate, publiczne category pages istnieją po N4-003, guide-only hub `/poradniki` po N4-004, topic/dossier `/aktualnosci/temat/{topicSlug}` po N4-007, a Atom feed `/aktualnosci/feed.xml` po N5-003,
- `ContentArticlePublicCatalogService` jest konsumowany przez publiczny detail controller i rozróżnia `visible/gone/not_found`,
- `ContentArticleSeoService` i `ContentArticleSchemaService` są podłączone do publicznego article response,
- `NewsroomArticleBodyRenderer` renderuje publicznie `rich_text`, `image`, `quote`, `table`, `context`, public-safe `related_article` oraz przygotowane przez Product Bridge `legal_reference`, `question_group`, `traffic_sign_group` i `product_cta`,
- `NewsroomArticleProductBridgeService` wymaga dla question/legal/sign zarówno wskazania w body, jak i jawnego article-owned pivotu; target musi dodatkowo przejść istniejący public eligibility contract,
- question groups reużywają `PublicQuestionCatalogService` i mają limit 5 pozycji na blok,
- legal reference wymaga verified `LegalUnit`, verified `LegalAct` i opublikowanej strony przepisu w opublikowanym topicu; internal notes/official excerpt nie są emitowane,
- traffic sign group dopuszcza tylko article pivot `relation_type` `direct` lub `example`, a następnie wymaga published znaku, autora i kategorii; luźny `related` jest fail-closed,
- contextual CTA reużywa istniejące trasy testu, publicznej bazy pytań i nauki,
- reverse links nie są częścią N3-005; zostały później zmaterializowane w N4-008 na tych samych article-owned pivots,
- `ContentArticlePathResolver` pozostaje canonical/history path foundation; NEWSROOM-N3-006 podłącza go do publicznego HTTP po current-canonical `not_found` i zwraca one-hop 301 wyłącznie dla zapisanego 301 wskazującego bieżący canonical; stale/malformed/self-loop redirect failuje zamknięcie do 404, a query string nie jest przenoszony do celu,
- `NEWSROOM_PUBLIC_ENABLED` nie został wdrożony przez N3-005 i pozostaje NEWSROOM-N3-008,
- zakres admin/domain CMS N2 pozostaje zmaterializowany, w tym `ContentCategoryResource`, `ContentArticleResource`, Builder/sources/relations/workflow/public-update/checklist/preview/HomeComposer/provenance-media i `ContentTopicResource`.

### 5.3. Brakujące elementy

Nie ma obecnie kompletnego end-to-end odpowiednika:

- site-wide orphan/click-depth audit command ponad per-article audit data — może wejść do N5/N6,
- publicznego feed rendererera `/aktualnosci/feed.xml` — pozostaje N5,
- news sitemap,
- feedu RSS/Atom,
- rankingów najnowsze / najczęściej czytane,
- pomiaru ekspozycji i CTR modułów redakcyjnych.

### 5.4. Znana niespójność brandingu

Przed wdrożeniem schema `NewsArticle` należało uporządkować branding publishera.

Historycznie `HomePageController.php` zawierał pozostałości marki „Orły na Drodze”. NEWSROOM-N0-001 usunął ten hardcode: homepage korzysta teraz ze wspólnego kanonicznego site identity PrawkoNaRaz.

Repo korzysta ze wspólnego fundamentu `config/content.php['organization']`, `SchemaIds`, `SchemaRenderer` i `SiteIdentitySchema`. Historyczna niespójność homepage została zamknięta przez NEWSROOM-N0-001, a N3-003/N3-004 reużywają tę samą identity w publicznym article graph.

**Gate:** przed uruchomieniem newsroomu `Organization`, `WebSite`, publisher, site name, logo, OG site name i publiczny branding muszą nadal korzystać z jednego istniejącego source of truth oraz stabilnych graph IDs.

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

NEWSROOM-N0-004 domknęło tę decyzję: `NewsroomBodyContract` v1 zapisuje kanonicznie uporządkowaną listę `{key?, type, data}`, niezależną od wewnętrznego associative state Filament Buildera. N2 editor używa Buildera jako adaptera UI, a `rich_text` przechowuje structured TipTap JSON z RichEditor, bez równoległego `body_html`. N3-004 materializuje zgodny reader/public renderer, a N3-005 podłącza publiczne, fail-closed rozwiązanie domain blocks dla pytań, podstaw prawnych, znaków i product CTA. Nieznany typ/wersja failuje zamknięcie. Nowy writer/block type nie może wyprzedzić kompatybilnego readera/renderera lub jawnej migracji danych.

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

To jest foundation contract dla danych systemowych kategorii. Po N1-001 tabela `content_categories` jest już zmaterializowana w DB; model `ContentCategory` i admin resource istnieją. Dedykowany seeder konsumujący `NewsroomTaxonomyContract` nie jest tu deklarowany jako część N3-005.

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

N3-004 materializuje publiczny detail wraz z breadcrumbs, H1/lead, byline/provenance, datami, hero, body, public sources, correction i author box. N3-005 rozszerza ten sam detail o fail-closed Product Bridge dla jawnie powiązanych pytań, podstaw prawnych, znaków oraz contextual CTA; nie dodaje reverse links.

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

Po N3-005 publiczny renderer obsługuje `rich_text`, `image`, `quote`, `table`, `context`, public-safe `related_article` oraz `legal_reference`, `question_group`, `traffic_sign_group` i `product_cta` przez `NewsroomArticleProductBridgeService`. Dla question/legal/sign sam ID bloku nie wystarcza: target musi być jawnie powiązany z artykułem i publicznie kwalifikowany. `embed` pozostaje feature-disabled zgodnie z N0-004.

### 13.3. W skrócie i kontekst branżowy

Dla dłuższych materiałów można stosować blok „W skrócie”, ale:

- musi być ręcznie lub kontrolowanie redagowany,
- nie może powtarzać całego leadu,
- nie może być ukrytym tekstem SEO.

Dla newsów regulacyjnych i egzaminacyjnych model wspiera strukturalne informacje:

- co się zmienia,
- status zmiany/prawa,
- od kiedy,
- kogo dotyczy,
- wpływ na egzamin.

N3-004 renderuje ten kontekst publicznie, jeśli dane istnieją; renderer nie wymyśla brakujących wartości.

### 13.4. Product bridge

Przykłady:

- artykuł o zmianie przepisów → powiązane przepisy i pytania,
- artykuł o znaku → karta znaku i pytania,
- artykuł o egzaminie → publiczny test/demo,
- poradnik → właściwy etap procesu użytkownika.

CTA powinno wynikać z kontekstu, nie być globalnym banerem „kup teraz”.

**Aktualny stan:** NEWSROOM-N3-005 jest wdrożone. `NewsroomArticleProductBridgeService` rozwiązuje wyłącznie jawnie powiązane, publicznie kwalifikowane targety; question group ma limit 5, legal reference wymaga verified legal data i opublikowanej publicznej strony, a traffic sign wymaga relacji `direct` lub `example` oraz published sign/author/category. CTA reużywa istniejące trasy testu, bazy pytań i nauki. N4-008 później dodało kontrolowane reverse links oraz article→topic/related discovery bez dublowania Product Bridge i bez nowego relation graphu.

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

N3-003 materializuje graph, a N3-004 emituje go w publicznym article response. Nie deklarujemy schema, którego treść nie wspiera.

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

### 17.1.1. Aktualny foundation i article media state

`NewsroomMediaStorage` jest wdrożonym storage/validation contractem:

- ma dedykowany newsroom disk/prefix i immutable ULID source namespace,
- reużywa shared image byte/MIME policy, ale ogranicza newsroom do JPEG/PNG/WebP/AVIF,
- po zapisie sprawdza rzeczywisty object bytes, raster MIME i width/height,
- odrzuca SVG/non-raster, declared metadata mismatch, traversal i unmanaged paths,
- stabilny publiczny URL rozwiązuje przez `MediaUrlResolver`,
- nie generuje ani nie deklaruje crop/variant files.

N2-010 dodało `NewsroomArticleMediaService`, hero/OG persistence oraz focal X/Y 0..1 z CSS crop previews 16:9/4:3/1:1. N3-004 wykorzystuje istniejące focal point w publicznym `object-position` dla hero/image blocks. Fizyczny crop/variant generator nadal nie istnieje i dokumentacja nie deklaruje takich plików jako wdrożonych.

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

Powyższe nazwy są konceptualne na poziomie architektury. Finalny kontrakt nazw/parametrów utrzymuje `NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md`. NEWSROOM-N5-004 materializuje prefiksowane eventy `newsroom_*`, w tym wymagany przez backlog `newsroom_module_click`; optional scroll depth pozostaje niewdrożony.

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

N3-004 ma feature regression publicznego article response oraz dedykowany Browser QA. N3-005 dodaje `NewsroomProductBridgeTest` i rozszerza ten sam Browser QA o publiczny Product Bridge/CTA na pełnej macierzy 360/390/430/768/1024/1440. Pełny release E2E dla całego newsroomu pozostaje N6.

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

**Stan:** N3-001..N3-007 są zmaterializowane. Rollout gate N3-008 pozostaje do wykonania.

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

Za ukończone uznajemy wyłącznie elementy rzeczywiście istniejące w kodzie i zweryfikowane na `main`:

- publiczny shell SEO,
- autorzy i publiczne profile autorów,
- część warstwy trust/legal,
- breadcrumbs,
- statyczny sitemap generator + builder/auditor + daily refresh,
- statyczny public/robots.txt oraz istniejący RobotsController,
- SchemaIds/SchemaRenderer i organization config,
- NEWSROOM-N0-001: współdzielony `SiteIdentitySchema` dla Organization/WebSite,
- NEWSROOM-N0-002: `NewsroomRouteContract`, finalne route namespaces, reserved slug policy i route-family transition guard,
- NEWSROOM-N0-003: `NewsroomTaxonomyContract` v1,
- NEWSROOM-N0-004: `NewsroomBodyContract` v1,
- NEWSROOM-N0-006: `NewsroomMediaStorage`,
- NEWSROOM-N1-001..N1-006: schema/models/slug/workflow/scheduler/home composition foundation,
- NEWSROOM-N2-001..N2-012: kategorie, artykuły, body editor, sources/relations, workflow/public update, checklist, private preview, HomeComposer, provenance/regulatory/media art direction, topics oraz stale-write/audit hardening,
- NEWSROOM-N3-001: public catalog/read boundary,
- NEWSROOM-N3-002: article SEO metadata service,
- NEWSROOM-N3-003: article schema graph service,
- NEWSROOM-N3-004: publiczny `ContentArticleController`, `NewsroomArticlePresentationService`, `NewsroomArticleBodyRenderer`, Blade article/unavailable, public response SEO/JSON-LD oraz feature/browser regression,
- NEWSROOM-N3-005: `NewsroomArticleProductBridgeService`, publiczne questions/legal/signs/contextual CTA, `newsroom.product-bridge-block`, backend regression i rozszerzony browser QA.

N3-005 zmergowano przez PR #73. Finalny implementation head `7d795b895865cda49ba94a4fec50533d7b0f7a97` przeszedł CI #275 i Browser Smoke #20. Zweryfikowany post-merge `main@fcc8074f89db141d522c5000742afc2e07a68565` przeszedł CI #276: `quality` 1049 passed / 19 498 assertions / 2 skipped, Pint 1051 files PASS, frontend build PASS; `newsroom-postgres` PASS. Browser Smoke #20 przeszedł 360/390/430/768/1024/1440, a artifact `newsroom-article-browser-qa` ma SHA256 `753b717cb5f0319e2e7799552b181fd972b41cd3b3a66bba7dd9a82d7ce96a2a`.

---

## 27. Pozostałe zadania

Szczegółowym źródłem backlogu jest [NEWSROOM-IMPLEMENTATION-BACKLOG.md](./NEWSROOM-IMPLEMENTATION-BACKLOG.md). Najbliższa kolejność:

- [x] `NEWSROOM-N0-001` — publisher branding source of truth,
- [x] `NEWSROOM-N0-002` — test/utrwalenie przyjętego route contract,
- [x] `NEWSROOM-N0-003` — deterministyczny taxonomy seed contract,
- [x] `NEWSROOM-N0-004` — block editor + serialization + sanitization + format-evolution decision,
- [x] `NEWSROOM-N0-005` — compatibility decision istniejącego SEO delivery jest udokumentowana; kodowy regression gate pozostaje w N5,
- [x] `NEWSROOM-N0-006` — media upload/storage contract,
- [x] `NEWSROOM-N1-001..N1-006` — domain/database/publishing/scheduling/home composition,
- [x] `NEWSROOM-N2-001..N2-012` — CMS/workflow/admin hardening,
- [x] `NEWSROOM-N3-001` — Public catalog service,
- [x] `NEWSROOM-N3-002` — Article SEO service,
- [x] `NEWSROOM-N3-003` — Article schema graph service,
- [x] `NEWSROOM-N3-004` — Article Blade page + block renderer,
- [x] `NEWSROOM-N3-005` — Product Bridge: questions/legal/signs/contextual CTA,
- [x] `NEWSROOM-N3-006` — old-path -> canonical 301.
- [x] `NEWSROOM-N3-007` — author-profile integration.
- [x] `NEWSROOM-N3-008` — public rollout config gate.
- [x] `NEWSROOM-N4-001` — `/aktualnosci` editorial composition read model.
- [x] `NEWSROOM-N4-002` — publiczny Hub Blade `/aktualnosci`.
- [x] `NEWSROOM-N4-003` — publiczne category pages `/aktualnosci/kategoria/{categorySlug}`.
- [x] `NEWSROOM-N4-004` — publiczny guide-only hub `/poradniki`.
- [x] `NEWSROOM-N4-005` — Navigation integration bez duplikowania istniejących primary links.
- [x] `NEWSROOM-N4-006` — generation-based cache/invalidation dla publicznych home/category read models.
- [x] `NEWSROOM-N4-007` — publiczne rollout-gated Topic / dossier pages na istniejącym `ContentTopic` i route `/aktualnosci/temat/{topicSlug}`.
- [x] `NEWSROOM-N4-008` — semantic silo / controlled reverse links na istniejących topic/question/legal/sign pivots, bez nowej schema.
- [x] `NEWSROOM-N5-001` — standard article sitemap + rollout-gated hub coverage + deterministic fixed-ID-range sharding w istniejącym static sitemap pipeline.
- [x] `NEWSROOM-N5-002` — rollout-gated Google News Sitemap w tym samym static pipeline: 2-day `first_published_at` eligibility, required news metadata i deterministic >1000 split.
- [x] `NEWSROOM-N5-003` — rollout-gated Atom 1.0 feed + head discovery, generation cache, stable IDs, public validators/304 i stateless delivery.
- [x] `NEWSROOM-N5-004` — privacy-safe delegated analytics hooks reużywające istniejący `trackAnalyticsEvent` i GA/consent layer, bez backendowego event store i zmian schema.
- [x] `NEWSROOM-N5-005` — article-specific IndexNow lifecycle integration przez dedicated after-commit event/listener nad istniejącym `IndexNowQueueService`/submission pipeline, bez nowego klienta, kolejki ani schema.
- [x] `NEWSROOM-N5-006` — istniejący `SeoSitemapAuditor` rozszerzony o newsroom/news audit hardening oraz site-wide `newsroom:audit-links` nad istniejącym semantic graph, bez drugiego validatora, graph subsystemu, migracji ani schema.
- [ ] `NEWSROOM-N5-007` — **IN PROGRESS**: PR #109 domknął pre-validation, child-before-index atomic publication i post-switch cleanup zarządzanych article/news sitemap files; PR #112 cache-backed dirty/version coordinator, shared lock, every-minute scheduler i aktualny single-node topology gate; PR #115 repo-level Nginx/static-delivery contract oraz production smoke tooling; PR #117 dedykowany GitHub Actions smoke harness z REPORT-ONLY PR mode i STRICT manual mode. Report-only produkcja wykazała Cache-Control mismatch, więc realny production HTTP/Cloudflare/GSC PASS nadal pozostaje otwarty.

N5-001..N5-006 oraz cztery repo-level podkroki N5-007 są zmaterializowane i potwierdzone na `main@38102d1c3867d13666308ee657d6579fcb5f1d7a`; decyzje o jednym static sitemap pipeline, jednym `NewsroomPublicGate`, jednym semantic-link graph i jednym IndexNow queue/submission pipeline pozostały bez zmian. PR #109 materializuje pre-validation + child-before-index atomic publication + post-switch cleanup, PR #112 cache-backed dirty/version coordinator, shared lock, every-minute scheduler i version-safe marker clearing bez queue workera, PR #115 crawler-safe Nginx delivery contract + production smoke script, a PR #117 GitHub Actions smoke harness. Aktualne deployment docs potwierdzają 1x Mikrus 4.1 z lokalnym `public/`, więc topology gate jest spełniony dla obecnego single-node contractu. Report-only run #117 wykazał, że aktywny publiczny delivery nadal nie spełnia cache contractu dla `/robots.txt`; następnym wykonywalnym podkrokiem jest zastosowanie configu i zielony STRICT smoke, a następnie GSC verification. N5-007 nadal nie jest DONE.

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

### 2026-09-19 — v0.55

- PR #117 dodał GitHub Actions production-smoke harness nad istniejącym skryptem z PR #115; nie zmienia static source of truth ani decyzji architektonicznej,
- PR run jest REPORT-ONLY, manualny `workflow_dispatch` domyślnie STRICT; artifact zachowuje evidence,
- exact-head CI #420 i post-merge CI #421 są pełnym PASS na finalnym workflow/main,
- report-only public run wykazał `Cache-Control: max-age=14400` dla `/robots.txt` zamiast repo contractu `public, max-age=3600`; z tego powodu nie zapisujemy production PASS,
- N5-007 pozostaje IN PROGRESS do wdrożenia aktualnego Nginx configu, zielonego STRICT smoke i GSC verification.

### 2026-09-18 — v0.54

- trzeci podkrok NEWSROOM-N5-007 zmergowano przez PR #115; finalny implementation head `30fa65d63eacdcb31b2a1c8c815f4034e29b5166`, merge `main@9a9c98d3534492e30ba215fd9785b7dd425a1f75`,
- decyzja architektoniczna nie zmieniła się: statyczne pliki w `public/` pozostają source of truth, a Nginx jest warstwą delivery; runtime generator nie zastępuje static pipeline,
- PR #115 dodaje dedykowane Nginx blocks dla `/robots.txt`, `/sitemap.xml` i `/sitemaps/*` oraz `scripts/production-seo-delivery-smoke.sh` do weryfikacji rzeczywistego HTTP contractu,
- repo-level regression `NginxSeoStaticDeliveryConfigurationTest` chroni Content-Type/cache/validator/nosniff contract,
- exact-head CI #416 i post-merge CI #417 były pełnym PASS: 1137 passed / 20 207 assertions / 2 skipped, PostgreSQL 7/94, Pint 1102 files PASS i frontend build PASS,
- NEWSROOM-N5-007 pozostaje IN PROGRESS: realny smoke run przeciw produkcji, Cloudflare/origin evidence i GSC verification nadal nie są potwierdzone; scheduler-definition/lock-contention regression pozostaje oddzielnym open evidence.

### 2026-09-18 — v0.53

- drugi podkrok NEWSROOM-N5-007 zmergowano przez PR #112; finalny implementation head `6f1c3b99d11796f74987c7fe640302b2d76578b7`, merge `main@79b6de0276ba8cdce500c366a4f98098e528dcd8`,
- architektura nie zmieniła się: statyczny `SeoSitemapGenerator` pozostaje source of truth, a freshness jest realizowane przez tani dirty/version signal + scheduler/lock zamiast runtime generation albo nieistniejącego queue workera,
- `NewsroomSeoArtifactRefreshCoordinator` utrzymuje version/clean-version oraz lock w skonfigurowanym cache store; full refresh uruchamia się poza publish requestem i nie czyści dirty state po failure albo version race,
- after-commit article/home events oraz category/topic/author observers ustawiają dirty signal; scheduler production odpala coordinator co minutę z `onOneServer()` + `withoutOverlapping()`, a daily `seo:refresh-sitemaps` pozostaje recovery path,
- aktualne canonical deployment docs potwierdzają 1x Mikrus 4.1, lokalny `public/`, lokalny Redis i jeden cron `schedule:run`; dla tej topologii topology gate jest spełniony, natomiast przyszły multi-node wymaga shared artifact distribution i ponownej weryfikacji,
- exact-head CI #409 i post-merge CI #410 były pełnym PASS: 1136 passed / 20 182 assertions / 2 skipped, PostgreSQL 7/94, Pint 1101 files PASS i frontend build PASS,
- production static HTTP/Nginx/Cloudflare/GSC delivery verification nadal pozostaje warunkiem domknięcia NEWSROOM-N5-007; nie przedstawiamy również dedykowanego scheduler/lock contention regression jako istniejącego.

### 2026-09-18 — v0.52

- pierwszy podkrok NEWSROOM-N5-007 zmergowano przez PR #109; finalny implementation head `dbcd5cdb2f0e6663c998f930c034624f8fe36bf4`, merge `main@1744a93fd8f0bddfe7fc5bff90b146fdea24b016`,
- decyzja architektoniczna nie zmieniła się: istnieje nadal jeden statyczny `SeoSitemapGenerator`/builder/renderer pipeline i produkcyjnym kierunkiem nie jest runtime generation,
- generator waliduje kompletny set przed publication, zapisuje child files przed root `sitemap.xml`, przełącza root index na końcu i dopiero potem usuwa obsolete zarządzane article/news sitemap files; cleanup nie obejmuje unrelated XML,
- implementation nie dodaje tabel, migracji, kolejki ani nowego subsystemu; dirty/version coordinator, distributed lock/scheduler i version-safe marker clearing pozostają kolejnym podzakresem N5-007,
- exact-head CI #403 i post-merge CI #404 były pełnym PASS: 1130 passed / 20 144 assertions / 2 skipped, PostgreSQL 7/94, Pint 1093 files PASS i frontend build PASS,
- topology gate oraz production static/Nginx/Cloudflare/GSC verification nadal pozostają warunkiem domknięcia N5-007.

### 2026-09-18 — v0.51

- NEWSROOM-N5-006 zmergowano przez PR #107; finalny implementation head `64feb88b614b737a69784d43e86e97591aaec3d5`, merge `main@18300baf3a91ffc5e3c549ab664c471bfd34ffe4`,
- architektura nie zmieniła się: Newsroom nadal rozszerza istniejące `SeoSitemapAuditor` i `NewsroomSemanticLinkService`; nie powstał równoległy sitemap validator, graph subsystem, tabela ani migracja,
- `newsroom:audit-links` jest cienkim wejściem CLI do site-wide QA i reużywa istniejący public gate/semantic resolver; sitemap-specific rules pozostają w istniejącym auditorze,
- N5-006 jest wyłącznie warstwą audytu/QA: generator, child-before-index publication, post-switch cleanup i dirty/version freshness coordinator pozostają NEWSROOM-N5-007,
- CI #397 zakończył 1127 passed / 20 131 assertions / 2 skipped, PostgreSQL 7/94, Pint/build PASS; Browser Smoke #57 PASS 6/6; post-merge CI #398 powtórzył 1127 / 20 131 / 2 skipped, PostgreSQL 7/94 i Pint/build PASS,
- następnym wykonywalnym taskiem jest NEWSROOM-N5-007; decyzje architektoniczne dotyczące static sitemap source of truth pozostają bez zmian.

### 2026-09-18 — v0.50

- NEWSROOM-N5-005 zmergowano przez PR #105; finalny implementation head `44c8099ac2ea020bf5d9e31cfe547af8cb2149f7`, merge `main@1cc9f4bec8c7d7fc105fd3495664434cf4323eea`,
- `ContentArticleIndexNowRequested` materializuje after-commit boundary, a `QueueNewsroomArticleIndexNow` reużywa istniejący IndexNow queue/submission subsystem; brak nowej tabeli, migracji, klienta HTTP i równoległego schedulera,
- lifecycle mapping zachowuje public HTTP semantics: first publish created, republish/substantive update updated, withdraw deleted dopiero po 410, archive bez zmiany detail/robots bez enqueue, restore-to-review bez enqueue, slug old+new jako updated po commit,
- public gate/noindex/scheduled-before-time i outer rollback są fail-closed; listener izoluje awarię queue od zatwierdzonej transakcji publikacji,
- lokalny `event_type` nie stał się polem protokołu IndexNow; istniejący `IndexNowSubmissionService` nadal odpowiada za finalny HTTP payload,
- exact-head CI #392 i post-merge CI #393 były pełnym PASS: 1116 passed / 20 095 assertions / 2 skipped, PostgreSQL 7/94, Pint/build PASS,
- N5-006 — Extend existing SEO/sitemap audits jest następnym wykonywalnym taskiem; decyzje architektoniczne pozostają bez zmian.
### 2026-09-18 — v0.49

- NEWSROOM-N5-004 zmergowano przez PR #103; finalny implementation head `6235b7dbadd60549a82ceebcb4eda47aaa6596fa`, merge `main@5704b3c3a8acde029001e28567980c5de8e27cdf`,
- implementacja reużywa istniejący public JS entrypoint, `trackAnalyticsEvent` i istniejący Google Analytics/consent layer; nie dodano drugiego analytics subsystemu, event store, migracji ani modeli,
- jeden delegated `newsroomAnalytics.ts` obsługuje article/module/category/pagination/Product Bridge/source/related click tracking przez semantyczne `data-*`, bez zmiany SSR-first layoutu i bez wpływu na czytanie treści bez JS,
- privacy contract ogranicza parametry do stabilnych identyfikatorów/kontekstu i pathname destination; body/title/author/source metadata nie są przenoszone do analytics,
- `prawkonaraz:analytics-ready` integruje delayed consent bez tworzenia osobnej kolejki; optional scroll depth i popular-content ranking pozostają poza wdrożonym zakresem,
- exact-head CI #384, Browser Smoke #55 i post-merge CI #385 zakończyły pełny PASS; post-merge: 1106 passed / 20 051 assertions / 2 skipped, PostgreSQL 7/94, Pint/build PASS,
- NEWSROOM-N5-005 IndexNow integration review jest następnym wykonywalnym taskiem; decyzje architektoniczne newsroomu pozostają bez zmian.
### 2026-09-18 — v0.48

- NEWSROOM-N5-003 zmergowano przez PR #101; finalny implementation head `1c89f370e899895eb25ac80bd437b19c1797a9ec`, merge `main@18cd07233e3c8712cf2c7fc9e0c32018baef65d3`,
- publiczny route `/aktualnosci/feed.xml` materializuje jeden Atom 1.0 feed i reużywa istniejący public catalog, route contract, canonical identity, rollout gate oraz generation-based cache zamiast tworzyć równoległy subsystem,
- feed ma stable content-article URN, absolute canonical item links, original publish/substantive-update semantics, summary/public author, default limit 50 i after-commit invalidation przez istniejące `invalidateAll()`,
- head discovery zostało dodane do wspólnego public-content layoutu; feed jest bezstanowy, nie wysyła session cookie i obsługuje ETag/Last-Modified oraz conditional 304,
- exact-head CI #380 i Browser Smoke #54 zakończyły PASS; post-merge CI #381 na exact main: 1104 passed / 20 024 assertions / 2 skipped, Pint PASS, frontend build 6.21 s i PostgreSQL 7/94,
- NEWSROOM-N5-004 Analytics hooks jest następnym wykonywalnym taskiem; N5-003 nie zmienia static sitemap architecture ani persistence schema.

### 2026-09-18 — v0.47

- NEWSROOM-N5-002 zmergowano przez PR #99; finalny implementation head `9bc16a7e42ae55c23b1916a4e214b9af846fd3dd`, merge `main@827f3816487d3a404df26c381a103a6cd1a9f413`,
- decyzja architektoniczna nie zmieniła się: Google News Sitemap rozszerza istniejące `SeoSitemapBuilder` / `SeoSitemapGenerator` / `SeoSitemapXmlRenderer`, bez równoległego engine i bez nested newsroom/news sitemap-index,
- rollout gate, current-canonical eligibility, canonical publication identity i fixed-ID-range sharding reużywają istniejące N3/N5 fundamenty; 2-dniowe okno jest liczone wyłącznie po `first_published_at`,
- istniejący auditor został zmieniony tylko tyle, by legalny URL overlap standard article/news sitemap nie był traktowany jako duplikat; pełny namespace/age/tag/shard audit pozostaje N5-006,
- exact-head CI #371 i post-merge CI #372 zakończyły pełny PASS: 1100 passed / 19 964 assertions / 2 skipped, Pint PASS, frontend build PASS i PostgreSQL 7/94,
- feed, atomic publication/obsolete-shard cleanup, dirty/version coordinator, article-specific IndexNow i production static/Nginx/CDN/GSC verification pozostają otwarte; następnym taskiem jest NEWSROOM-N5-003.

### 2026-09-18 — v0.46

- NEWSROOM-N5-001 zmergowano przez PR #97; finalny implementation head `b4ff321011c3d58438876c9d69f324e342ec9021`, merge `main@5ddfa48c0646fa89cc802d129e6d9ccee9d18957`,
- decyzja DEC-NR dotycząca sitemap nie zmieniła się: Newsroom rozszerza istniejące `SeoSitemapGenerator` / `SeoSitemapBuilder` / `SeoSitemapAuditor`, a statyczny pipeline pozostaje source of truth zamiast równoległego runtime generatora,
- standard article sitemap i hub coverage respektują `NEWSROOM_PUBLIC_ENABLED`; sharding jest stabilny po fixed `content_articles.id` ranges, a root sitemap index wskazuje shards bez zagnieżdżonego newsroom index,
- generator/auditor egzekwują 50 000 entries / 50 MB; exact-head CI #367 i post-merge CI #368 zakończyły pełny PASS: 1098 passed / 19 926 assertions / 2 skipped, Pint PASS, frontend build PASS i PostgreSQL 7/94,
- N5-001 nie wdraża news sitemap, feedu, child-before-index set switch/obsolete-shard cleanup, dirty/version coordinatora, article-specific IndexNow ani production static/Nginx/CDN/GSC verification; następnym taskiem jest NEWSROOM-N5-002.

### 2026-09-18 — v0.45

- NEWSROOM-N4-008 zmergowano przez PR #95; finalny implementation head `bd63773bc1febdcc6aa8c2507c6621e908d63b09`, merge `main@3d7ac8ab8a3ed1c299cb0cdd4cb1ef8ac6b53f78`,
- decyzja architektoniczna nie zmieniła się: semantic graph korzysta z jawnych relacji i nie tworzy sitewide reciprocal-link farm; implementacja reużywa istniejące `content_article_*` pivots,
- article/guide detail ma primary-category, explicit topic links i deterministic related articles; reverse links są bounded na publicznych question/legal/sign surfaces, a TrafficSign pozostaje fail-closed dla `direct|example`,
- istniejący N3-005 Product Bridge nadal odpowiada za article→question/legal/sign, więc N4-008 nie tworzy drugiego forward-link mechanizmu ani nie dotyka `question_relations` / `question_seo_topics`,
- per-article audit materializuje inbound sources, explicit reverse-edge count i estimated hub depth; site-wide orphan/click-depth command nie został wdrożony,
- exact-head CI #361 i Browser Smoke #48 zakończyły PASS; post-merge CI #362 zakończył 1093 passed / 19 881 assertions / 2 skipped, Pint PASS, frontend build 7.42 s i PostgreSQL 7/94,
- następnym wykonywalnym taskiem jest NEWSROOM-N5-001.

### 2026-09-18 — v0.44

- NEWSROOM-N4-007 zmergowano przez PR #93; finalny implementation head `812313afc38e30e53c59901d46c2d24188e3f6fa`, merge `main@a97373003c5a249a28761df15342f19e656c50c5`,
- nie zmieniono decyzji o topicu jako ręcznie publikowanym dossier odrębnym od taga ani istniejącego `ContentTopic`/pivot schema; N4-007 uruchamia tylko publiczną warstwę read/HTTP/SSR,
- publiczny topic używa jawnego `ContentTopic` status contract: published -> 200 przy gate=true, draft/future/unknown/gate=false -> 404, wcześniej publiczny archived -> 410; późniejszy spadek corpus poniżej baseline nie wykonuje automatycznego status/HTTP flipu,
- read model pokazuje opcjonalny eligible featured article i chronologiczny `activelyDistributed()+indexable()` corpus news/explainer/analysis/report/guide bez ręcznego rankingu; featured nie jest duplikowany w liście,
- SSR Blade i `NewsroomTopicSchemaService` realizują istniejący route/canonical/CollectionPage contract; nie dodano automatycznych tag pages, topic cache, reverse links ani N5 sitemap/feed/IndexNow,
- Browser Smoke #39 PASS dla `newsroom-topic`, `newsroom-guides`, `newsroom-category`, `newsroom-home`, `newsroom-article`; exact-head CI #348 PASS, post-merge CI #349 PASS: 1087 passed / 19 827 assertions / 2 skipped, Pint 1081 files PASS, frontend build 9.48 s, PostgreSQL 7/94,
- NEWSROOM-N4-008 Semantic silo / reverse-link integration jest następnym wykonywalnym taskiem.

### 2026-09-18 — v0.43

- NEWSROOM-N4-006 zmergowano przez PR #91; finalny implementation head `093ca709d3155d5fa3f13bae224312fd286077dc`, merge `main@5f1880e03469fc6e340a86fdb1b4246c7afd116b`,
- `NewsroomPublicReadCache` cache’uje tylko istniejące publiczne home/category read models przez generation-based keys i 60-sekundowy TTL safety net; preview/guide/article/topic/feed pozostają poza zakresem,
- after-commit invalidation obejmuje publish/archive workflow, aktywne public/exposure updates, home placement changes i category save/delete; placement rotuje tylko home generation,
- cache layer nie przejmuje odpowiedzialności N5: dirty/version refresh, sitemap, feed discovery i IndexNow pozostają osobnym zakresem,
- `NewsroomPublicReadCacheTest` chroni cache reuse/invalidation i outer-transaction boundary; feature-test harness czyści trwały cache między izolowanymi bazami,
- exact-head CI #342 oraz Browser Smoke #36 PASS; post-merge CI #343 PASS: 1081 passed / 19 770 assertions / 2 skipped, Pint 1077 files PASS, frontend build 9.44 s, PostgreSQL 7 passed / 94 assertions,
- NEWSROOM-N4-007 Topic / dossier pages jest następnym wykonywalnym taskiem; reverse links N4-008 i N5 discovery pozostają otwarte.

### 2026-09-18 — v0.42

- NEWSROOM-N4-005 zmergowano przez PR #89; finalny implementation head `189219b3586d2df8e4ea73045318fd68f38f0fa1`, merge `main@a9fb9ccfed058de88efdb6e0833b67911aeb09aa`,
- audyt potwierdził istniejące pojedyncze primary links `Aktualności -> /aktualnosci` i `Poradniki -> /poradniki` z właściwymi prefixami; nie dodano duplikatów i nie zmieniono `documentNavigationPrefixes`,
- wspólny `PublicFooter::service_links` został uzupełniony o oba huby; Vue `SiteFooter.vue` i Blade `public-footer.blade.php` konsumują ten sam source of truth,
- dodano `NewsroomNavigationIntegrationTest` dla canonical links, duplicate-free footer i prefixów oraz rozszerzono `newsroom-guides` Browser QA o rzeczywiste linki footera; Browser Smoke #32 PASS dla `newsroom-guides`, `newsroom-category`, `newsroom-home`, `newsroom-article`,
- exact-head CI #335 PASS; post-merge CI #336 PASS: 1075 passed / 19 745 assertions / 2 skipped, Pint 1069 files PASS, frontend build 7.32 s, `newsroom-postgres` 7 passed / 94 assertions,
- NEWSROOM-N4-006 Cache jest następnym wykonywalnym taskiem; topic pages N4-007, reverse links N4-008 i N5 discovery pozostają otwarte.

### 2026-09-18 — v0.41

- NEWSROOM-N4-004 zmergowano przez PR #87; finalny implementation head `119cbd94d1fb6ff6f9f2025e726242190927266d`, merge `main@2bb22142b1e9bec803f9c3889c11000194f46783`,
- istniejący `public.guides` route i `NewsroomPlaceholderController::guides()` zachowano jako rollout boundary: gate=false -> pre-launch 200 + noindex, gate=true -> SSR `newsroom.guides`,
- guide hub reużywa `activelyDistributedQuery(FAMILY_GUIDES)`, deterministic order `first_published_at DESC, id DESC`, SSR pagination po 20, canonical bez redundantnego `?page=1`; empty hub ma 200 + noindex, invalid/out-of-range page -> 404,
- evergreen renderer reużywa istniejące guide detail URLs i wspólny public layout; schema to `CollectionPage` + `BreadcrumbList` + conditional `ItemList`, a `/aktualnosci` ma crawlable `Zobacz wszystkie poradniki`,
- Browser Smoke #31 zakończył PASS dla `newsroom-guides`, `newsroom-category`, `newsroom-home` i `newsroom-article`; exact-head CI #331 PASS, post-merge CI #332 PASS: 1072 passed / 19 731 assertions / 2 skipped, Pint 1068 files PASS, frontend build 10.14 s, `newsroom-postgres` 7 passed / 94 assertions,
- NEWSROOM-N4-005 Navigation integration jest następnym wykonywalnym taskiem; topic/feed, cache/invalidation, reverse links i N5 discovery pozostają otwarte.

### 2026-09-18 — v0.40

- NEWSROOM-N4-003 zmergowano przez PR #85; finalny implementation head `8e4f707060fbaa348e9392f0b19beb7b4517beca`, merge `main@84bcb2aeff57a1374def7af411c39db07a8fb38d`,
- aktywowano istniejący `public.news.categories.show` bez zmiany route contract: gate=false -> 404, gate=true -> SSR `newsroom.category` dla aktywnej kategorii,
- listing reużywa `activelyDistributedQuery()` dla newsroom-family corpus, sortuje `first_published_at DESC, id DESC`, paginuje po 20 rekordów; strona 1 nie emituje redundantnego `?page=1`, a dalsze strony mają self-canonical,
- aktywna pusta kategoria renderuje użyteczny 200 + `noindex,follow`; SEO graph category page używa `CollectionPage` + `BreadcrumbList` oraz `ItemList` tylko dla niepustego corpus,
- Browser Smoke #29 zakończył PASS dla `newsroom-category`, `newsroom-home` i `newsroom-article`; exact-head CI #326 PASS na `8e4f7070...`, post-merge CI #327 PASS na `main@84bcb2ae...`: 1067 passed / 19 684 assertions / 2 skipped, Pint 1065 files PASS, frontend build 9.40 s, `newsroom-postgres` 7 passed / 94 assertions,
- NEWSROOM-N4-004 `/poradniki` hub jest następnym wykonywalnym taskiem; topic/feed, cache/invalidation, reverse links i N5 discovery pozostają otwarte.

### 2026-09-18 — v0.39

- NEWSROOM-N4-002 zmergowano przez PR #83; finalny implementation head `1036b67aa44b56fffb0bc1e9083d3a0d1d3963d0`, merge `main@04a3e961a40207bd65c41b684a5bf1cd8d2c5e10`,
- przy gate=true `/aktualnosci` renderuje SSR `newsroom.home` z N4-001 read modelu; gate=false zachowuje pre-launch MarketingPlaceholder 200 + noindex, a `/poradniki`, category/topic/feed pozostają downstream,
- Hub Blade materializuje responsywne lead/secondary/latest/category/guides/important-now/breaking, pomija puste sekcje i reużywa istniejący Product Bridge route `public.tests`,
- Browser Smoke #27: `newsroom-home` i `newsroom-article` PASS; hub QA działa z JS disabled na 360/390/430/768/1024/1280/1440 i sprawdza built CSS, canonical, robots, CTA oraz horizontal overflow,
- exact-head CI #321 PASS na `1036b67a...`; post-merge CI #322 PASS na `main@04a3e961...`: 1062 passed / 19 638 assertions / 2 skipped, Pint 1061 files PASS, frontend build 7.59 s, `newsroom-postgres` 7 passed / 94 assertions,
- NEWSROOM-N4-003 Category pages jest następnym wykonywalnym taskiem; N4-004 `/poradniki`, N4-006 cache/invalidation i N5 discovery pozostają otwarte.

### 2026-09-18 — v0.38

- NEWSROOM-N4-001 zmergowano przez PR #81; finalny implementation head `813aba108b6b68f0526df3a9c8c86d82df5ca0f6`, merge `main@e0e06e9af8a6b02a63ef4b3e1eb2d772409ad974`,
- nie zmieniono decyzji DEC-NR-006 ani nie utworzono drugiego composera: `NewsroomHomeCompositionService` nadal rozstrzyga fixed placements, fallback, global dedupe i breaking exception,
- category composition ma stały query budget dzięki batchowi placements, SQL window rankingowi per category i jednemu eager-loadowi relacji dla wybranych kandydatów; `NewsroomHomeReadModelService` daje rollout-gated scalar-array projection dla przyszłego huba,
- publiczny `/aktualnosci` nadal jest placeholderem 200 + noindex; N4-002 pozostaje odpowiedzialne za Hub Blade, a N4-006 za faktyczny cache/invalidation,
- exact-head CI #314 i post-merge CI #315 były PASS; post-merge: 1059 tests passed / 19 611 assertions / 2 skipped, Pint 1060 files PASS, frontend build 9.93 s, PostgreSQL 7 tests / 94 assertions.

### 2026-09-18 — v0.37

- NEWSROOM-N3-008 zmergowano przez PR #79; finalny implementation head `c8484aa1529eb41805a76ceb7be1f55db63aec14`, merge `main@23b952b77e39cd25fb39edc252faf05849946bd7`,
- wdrożono prosty `NewsroomPublicGate` oparty o `config/newsroom.php` i `NEWSROOM_PUBLIC_ENABLED=false` jako bezpieczny dark-deploy default,
- gate=false blokuje publiczne detail/guide i historyczne redirecty, usuwa newsroom corpus z profilu autora oraz jego wkład do author sitemap, filtruje newsroom namespace z obecnego IndexNow collectora i pozostawia admin/private preview dostępne,
- exact-head CI #308 i Browser Smoke #23 oraz post-merge CI #309 zakończyły się PASS; N4-001 jest kolejnym taskiem wykonawczym.



### 2026-09-17 — v0.36

- NEWSROOM-N3-007 zmergowano przez PR #77; finalny implementation head `bf7981d23ff99a6335b55ecaeb6ce36b6622a042`, a zweryfikowany post-merge `main` to `c68672f6aa7c41defaeec56debb541d5a60d9f4f`,
- istniejący `ContentAuthor` i `/autorzy/{slug}` pozostają jedyną publiczną tożsamością autora; nie utworzono drugiego modelu/profile surface,
- profil autora agreguje publiczne/indexowalne publikacje Newsroomu z rozdzieleniem `published`, `needs_review` i `archived`, a noindex/scheduled/withdrawn/inactive-category pozostają poza listą,
- wspólny `ContentAuthorSchemaService` utrwala identyczny `Person @id` dla ProfilePage i Article oraz `worksFor -> /#organization`; author sitemap używa newsroomowego `public_state_changed_at`,
- odpublikowanie `ContentAuthor` jest blokowane, gdy zależne artykuły Newsroomu pozostają indexable; regresje potwierdza `ContentAuthorProfileTest`,
- exact-head CI #295 oraz post-merge CI #296 były pełnym PASS; po N3-007 następnym taskiem jest NEWSROOM-N3-008.

### 2026-09-17 — v0.35

- NEWSROOM-N3-006 zmergowano przez PR #75; finalny implementation head `f48e7a12fa6c53422cd2ef8c369af81769163b9d`, a zweryfikowany post-merge `main` to `33d9946219595a4be75d789b19cc8d10efc2ecc0`,
- `ContentArticleController` konsultuje historyczny resolver dopiero po current-canonical `not_found`, więc istniejące current 200 oraz withdrawn 410 pozostają bez zmian,
- `ContentArticlePathResolver::findCanonicalRedirectTarget()` zwraca cel wyłącznie dla zapisanego HTTP 301, którego `to_path` jest dokładnie bieżącym canonical wyliczonym z route family + slug; stale/malformed/self-loop records failują zamknięcie do 404,
- historyczne ścieżki zwracają dokładnie jeden 301 do bieżącego canonical, a tracking query params nie są kopiowane; `NewsroomArticleRedirectTest` chroni newsroom i guide route family oraz fail-closed behavior,
- exact-head CI #279 i Browser Smoke #21 zakończyły się PASS; post-merge CI #280 na `main@33d99462...` zakończył się pełnym PASS (`quality` 1051 passed / 19 512 assertions / 2 skipped, Pint 1052 files PASS, frontend build PASS; `newsroom-postgres` PASS),
- następnym taskiem wykonawczym jest NEWSROOM-N3-007 author-profile integration; N3-008 rollout gate, N4 reverse links/huby oraz N5 discovery pozostają otwarte.

### 2026-09-17 — v0.34

- NEWSROOM-N3-005 zmergowano przez PR #73; finalny implementation head `7d795b895865cda49ba94a4fec50533d7b0f7a97`, a zweryfikowany post-merge `main` to `fcc8074f89db141d522c5000742afc2e07a68565`,
- `NewsroomArticleProductBridgeService` materializuje publiczne `question_group`, `legal_reference`, `traffic_sign_group` i `product_cta` bez losowych relacji: question/legal/sign target musi jednocześnie istnieć w body, article-owned pivot i istniejącym public eligibility contract,
- questions reużywają `PublicQuestionCatalogService` i są limitowane do 5 pozycji; legal wymaga verified jednostki/aktu oraz opublikowanej strony/topicu; private notes i pełny official excerpt nie są emitowane,
- traffic signs są dodatkowo filtrowane przez pivot `relation_type in ['direct','example']`; luźny `related` pozostaje fail-closed zgodnie z nadrzędnym UI/UX contract,
- contextual CTA reużywają istniejące trasy testu, oficjalnej bazy pytań i nauki; reverse links nie zostały wdrożone i pozostają N4-008,
- finalny CI #275 i Browser Smoke #20 dla PR head zakończyły się PASS; post-merge CI #276 na `main@fcc8074f...` zakończył się pełnym PASS (`quality` 1049 passed / 19 498 assertions / 2 skipped, Pint 1051 files PASS, frontend build PASS; `newsroom-postgres` PASS),
- następnym taskiem wykonawczym jest NEWSROOM-N3-006 historical old-path -> canonical 301; N3-007 i N3-008 pozostają otwarte.

### 2026-09-17 — v0.33

- NEWSROOM-N3-004 zmergowano przez PR #71; finalny PR implementation head `5d164e10ca83e6003b52ec279a16234ec5bcea96`, a merge commit na `main` to `7398c5d930d38d6cc9d9953e53b4298df41cfce8`,
- publiczne detail routes `/aktualnosci/{articleSlug}` i `/poradniki/{articleSlug}` konsumują `ContentArticlePublicCatalogService`, SEO N3-002 i schema N3-003; visible renderuje 200, historyczny withdrawn 410, hidden/not-found 404,
- `NewsroomArticlePresentationService` i `NewsroomArticleBodyRenderer` materializują SSR Blade article surface z breadcrumbs/byline/provenance/hero/regulatory context/public sources/correction/author box oraz publicznymi `rich_text`, `image`, `quote`, `table`, `context` i bezpiecznym `related_article`,
- `legal_reference`, `question_group`, `traffic_sign_group` i `product_cta` pozostają celowo deferred do N3-005; old-slug 301 pozostaje N3-006, author-profile integration N3-007, a `NEWSROOM_PUBLIC_ENABLED` N3-008,
- Browser Smoke #18 przeszedł 360/390/430/768/1024/1440; finalny push-CI #270 na merge commit zakończył się pełnym PASS: quality 1048 passed / 19 473 assertions / 2 skipped, Pint 1049 files PASS, frontend build PASS; newsroom-postgres 7 passed / 94 assertions,
- następnym taskiem wykonawczym jest NEWSROOM-N3-005 `Product Bridge`.

### 2026-09-17 — v0.32

- NEWSROOM-N3-003 zmergowano przez PR #68 na `main@cdef77c66459537c98b2e044a76a97c082af2ef2`; exact-head PR CI #242 był PASS. Następnie na `main@5f85bec331428a73f3859ae40b555f55e7e7820d` poprawiono wyłącznie deterministyczność niezwiązanego fixture testowego i finalny push-CI #245 potwierdził pełny PASS: 1043 passed / 19 418 assertions / 2 skipped, Pint 1045 files PASS, frontend build 9.04 s oraz PostgreSQL 7 passed / 94 assertions,
- `ContentArticleSchemaService` reużywa `SiteIdentitySchema`, `SchemaIds`, `SchemaRenderer` i `ContentArticleSeoService`; buduje jeden stabilny graph Organization/WebSite/WebPage/NewsArticle-or-Article/Person/BreadcrumbList oraz ImageObject nodes dla ustawionych hero/OG media paths,
- schema canonical i daty są zgodne z N3-002; news używa `NewsArticle`, pozostałe typy `Article`, newsroom breadcrumb zawiera primary category, a guide breadcrumb nie dodaje category hop,
- service odrzuca niepubliczny artykuł, nieopublikowanego autora i nieaktywną kategorię, nie tworzy ImageObject bez ustawionego hero/OG media path i deduplikuje ten sam URL; publiczne detail controllers/Blade nadal pozostają 404, więc emission JSON-LD w HTTP należy do N3-004, a historyczne redirecty pozostają N3-006,
- następnym taskiem wykonawczym jest NEWSROOM-N3-004 `Article Blade page + block renderer`.

### 2026-09-17 — v0.31

- PR #66 zmergowano na `main@d9be735eee1915f4b53a6de42ec665a37442acae`; exact-head PR CI #236 i finalny push-CI #237 zakończyły się pełnym PASS, a #237 potwierdził 1034 passed / 19 376 assertions / 2 skipped, Pint 1043 files PASS, frontend build 8.50 s i PostgreSQL 7 passed / 94 assertions,
- NEWSROOM-N3-002 jest **DONE**: `ContentArticleSeoService` generuje jeden self-canonical z route family + slug, canonical organization title branding, sanitized description fallback, robots, OG/hero image metadata oraz publication/substantive-modification dates,
- service nie korzysta z technicznego `updated_at` jako dateModified i odrzuca niepubliczne/withdrawn states; nie dodano CMS canonical override,
- publiczne article controllers/renderery nadal pozostają wyłączone i detail routes są 404; następnym taskiem jest N3-003 schema graph, następnie N3-004 article page i N3-006 historyczne redirecty.

### 2026-09-17 — v0.30

- PR #64 zmergowano na `main@8215e142af2cd88c7335f17bc085bbd0c04d5790`; exact-head PR CI #231 był pełnym PASS, a finalny push-CI #232 na tym samym `main` zakończył się `quality` PASS (1029 passed / 19 335 assertions / 2 skipped, Pint PASS, frontend build PASS) oraz `newsroom-postgres` PASS,
- NEWSROOM-N3-001 jest **DONE**: `ContentArticlePublicCatalogService` rozdziela canonical detail lookup od `activelyDistributed()` list query, respektuje route family i ładuje wyłącznie public-safe pola/relacje,
- `ContentArticlePublicResolution` rozróżnia current-canonical visible 200, historycznie publiczny withdrawn 410 oraz hidden/not-found 404; draft, scheduled i never-published archived nie uzyskują publicznego detailu,
- archived po wcześniejszej publikacji pozostaje widocznym detail candidate, ale nie trafia do active listings; needs_review pozostaje publicznie widoczne zgodnie z policy, lecz nie jest aktywnie dystrybuowane,
- publiczne article controllery/renderery nadal są wyłączone i detail routes pozostają 404 w pre-launch stanie; historyczny old-path 301 jest osobnym NEWSROOM-N3-006,
- następnym taskiem wykonawczym jest NEWSROOM-N3-002 `Article SEO service`.

### 2026-09-16 — v0.29

- PR #62 zmergowano na `main@ff81f92fe75442b60e67297f3945d2a63b7c5128` po exact-head CI #224 (`quality` 1025 passed / 19 297 assertions / 2 skipped, Pint 1038 files PASS, frontend build PASS; `newsroom-postgres` 7 passed / 89 assertions),
- N2-011 materializuje adminowy `ContentTopicResource` nad istniejącym modelem/pivotem, bez nowych migracji i bez ręcznego rankingu topic corpus,
- `ContentTopicPublishingService` utrwala kontrolowane publish/archive/republish, corpus/featured eligibility i AuditLog; model utrwala post-publication slug/`published_at` identity oraz delete guard,
- corpus below baseline po publikacji nie zmienia automatycznie statusu ani HTTP i wyłącza topic z redakcyjnej promocji przez istniejące predicate,
- publiczny topic controller oraz 200/410/nav/sitemap behavior pozostają N4/N5; route nadal 404 w pre-launch stanie,
- N2 jest zamknięte implementacyjnie; następnym taskiem jest N3-001 Public catalog service.

### 2026-09-16 — v0.28

- PR #60 zmergowano na `main@4936d14d56fa15e59e6dd771e443e93895d3d281` po exact-head CI #217 (`quality` 1015 passed / 19 245 assertions / 2 skipped, Pint 1027 files PASS, frontend build PASS; `newsroom-postgres` 7 passed / 89 assertions),
- N2-010 materializuje kontrolowane provenance i regulatory context w istniejącym `ContentArticleResource`; aktywny status regulacyjny i `official_source` są backendowo powiązane z publicznie cytowanym źródłem official/legislation, a statusy przyszłe/obowiązujące wymagają `effective_from`,
- hero/OG upload korzysta z nowego `NewsroomArticleMediaService` nad istniejącym `NewsroomMediaStorage`; rzeczywisty asset jest ponownie inspektowany pod kątem MIME/bytes/dimensions/stabilnego publicznego URL i nie korzysta z question-specific `AdminMediaUploadService`,
- focal point jest przechowywany jako X/Y 0..1, a CMS pokazuje CSS previews 16:9 / 4:3 / 1:1 bez deklarowania fizycznych wariantów,
- N2-010 nie dodaje migracji, asset modelu, crop generatora ani publicznego N3/N4 renderera; następnym wykonawczym taskiem N2 jest N2-011 `ContentTopicResource`.

### 2026-09-16 — v0.27

- PR #58 zmergowano na `main@bcb8d783171fc565810a2149b735d86ca6039b00` po exact-head CI #211 (`quality` 1006 passed / 19 194 assertions / 2 skipped, Pint 1024 files PASS, frontend build PASS; `newsroom-postgres` PASS),
- N2-009 materializuje custom Filament `NewsroomHomeComposer` jako fixed-slot CMS nad istniejącym placement/composition modelem; nie wprowadza page-buildera ani nowego typu layoutu,
- fallback UI korzysta z tego samego `NewsroomHomeCompositionService`; future preview również używa tego resolvera i pozostaje prywatnym admin-only surface, więc publiczne N3/N4 route’y nadal nie są uruchomione,
- N2-012 jest DONE: placement update/delete używają deterministycznego loaded tokenu pod row lockiem, a istniejące tuple advisory locks nadal chronią overlap/concurrency,
- następny task wykonawczy to N2-010 provenance/regulatory/media art direction.

### 2026-09-16 — v0.26

- PR #56 zmergowano na `main@9aa621c083e02e157e72294f5e994ea566e45a9f` po exact-head CI #202 (`quality` 997 passed / 19 146 assertions / 2 skipped, Pint 1020 files PASS, frontend build PASS; `newsroom-postgres` PASS),
- N2-008 materializuje admin-only private Article preview w istniejącym monolicie bez uruchamiania publicznych controllerów N3,
- preview reużywa public-content shell, ale transport jest `private, no-store`, robots są `noindex,nofollow`, a public analytics/consent są wyłączone,
- rich text jest renderowany z istniejącego structured TipTap contract; publicznie cytowalne sources są widoczne, private evidence pozostaje poza view payload,
- publiczne article detail namespaces pozostają 404; następny task wykonawczy to N2-009 NewsroomHomeComposer + future preview.

### 2026-09-16 — v0.25

- PR #52 zmergowano na `main@eb37034090e7233e72cd9452d12fe9876631440a` po exact-head CI #194 (`quality` 992 passed / 19 114 assertions / 2 skipped, Pint 1017 files PASS, frontend build PASS; `newsroom-postgres` PASS),
- N2-007 materializuje read-only publication checklist w `ContentArticleResource`,
- twarde checklist blockers nie są osobną logiką Filament: `ContentArticlePublishingService` i UI współdzielą `ContentArticlePublicationChecklist`,
- warningi pozostają nieblokujące; domain/media invariants, w tym dedykowany różny OG asset wymagający własnego alt, nadal blokują backend,
- N2-007 jest DONE; następny task wykonawczy to N2-008 Article preview.

### 2026-09-16 — v0.24

- PR #50 zmergowano na `main@570f884a89869ec44d24f57f0506f4444d20a7d2` po exact-head CI #190 (`quality` 987 passed / 19 088 assertions / 2 skipped, Pint 1015 files PASS, frontend build PASS; `newsroom-postgres` PASS),
- publiczny `ContentArticle` ma teraz dedykowany atomowy `Apply public update` zamiast low-level public Save,
- deterministyczny loaded-state token obejmuje article + sources + article-owned relations/topics i wykrywa same-second child changes,
- N2-006 jest DONE; N2-012 pozostaje PARTIAL tylko dla przyszłego HomeComposer stale-write,
- następny task wykonawczy to N2-007 Publication checklist.

### 2026-09-16 — v0.23

- PR #48 zmergowano na `main@88533b04d74a839c3bbccf86b707909ccdd235f8`; exact-head CI #176 przeszedł dla `quality` i `newsroom-postgres`,
- zmaterializowano wspólną warstwę Edit/View workflow actions delegującą do `ContentArticlePublishingService`,
- review/schedule/publish/archive/withdraw/restore/republish oraz featured/breaking są dostępne jako kontrolowane actions; exposure mutations zachowują audit/public-state semantics,
- ordinary `publiclyVisible()` Edit nadal nie ma low-level public Save,
- N2-006 pozostaje **PARTIAL**: `Apply public update` i loaded-token stale-write rejection nadal należą do N2-012,
- kolejny wykonawczy krok to N2-012, aby domknąć zależność zamiast fałszywie oznaczać N2-006 jako ukończone.

### 2026-09-16 — v0.22

- wdrożono i zmergowano NEWSROOM-N2-005 przez PR #46 na `main@262d9fab0b171c13a159f7c97670dee3db583b56`,
- istniejące article-owned relacje `questions()`, `legalUnits()`, `trafficSigns()` i `topics()` są edytowane w `ContentArticleResource` bez nowych tabel ani duplikowania istniejącego graphu pytań/przepisów/znaków,
- questions/legal/signs używają ordered Repeaterów i zapisują kolejność do istniejącego pivot `sort_order`; topics używają searchable multi-select bez ręcznego rankingu zgodnie z istniejącą decyzją dla `content_article_topic`,
- `NewsroomArticleRelationsEditorAdapter` waliduje allowlisty `relation_type`, duplikaty oraz istnienie targetów przed synchronizacją; sync nie mutuje target entities i jawnie bumpuje parent `ContentArticle.updated_at`,
- pickery używają bounded search zamiast preloadu dużych corpusów; question label pokazuje external ID, kategorię i active/public state, legal/sign/topic pickery pokazują kontekstowe etykiety,
- ordinary Edit `publiclyVisible()` nadal ignoruje mutacje relations/topics i pozostawia jedynie wewnętrzną ścieżkę `editorial_note`; pełny stale-write reject nadal pozostaje N2-012,
- publiczny reverse-link/article relation renderer nadal nie istnieje i pozostaje zakresem N3/N4,
- finalny exact-head gate PR #46: `quality` 972 passed / 18 940 assertions / 2 skipped, Pint 1011 files PASS, frontend build PASS (9.49 s); `newsroom-postgres` 7 passed / 89 assertions,
- następnym taskiem jest `NEWSROOM-N2-006` Workflow actions.

### 2026-09-16 — v0.21

- wdrożono i zmergowano NEWSROOM-N2-004 przez PR #44 na `main@fd2042f22532b7b7c14dc993e532c0887876e159`,
- `ContentArticleResource` ma relationship Repeater źródeł oparty o istniejące `ContentArticleSource`; `defaultItems(0)` zachowuje możliwość zapisu draftu bez źródła, a `sort_order` zachowuje redakcyjną kolejność,
- editor obsługuje wszystkie source types v1, nullable URL dla interview/direct/internal evidence, bezpieczną akcję otwarcia tylko dla HTTP(S) oraz jawne stany PRIMARY/OFFICIAL/PUBLIC/TYLKO WEWNĘTRZNE,
- `ContentArticlePublishingService` wymusza source policy przed review/publish: news wymaga co najmniej jednego źródła, source wymaga title i wspieranego typu, a niepusty URL musi być poprawnym HTTP(S); dla kategorii `przepisy`, jeśli istnieje primary `official`/`legislation`, co najmniej jeden taki primary musi być publicznie cytowalny z HTTP(S) URL,
- `ContentArticleSource` dotyka parent `ContentArticle.updated_at`; pełny stale-write reject nadal pozostaje N2-012 i nie jest uznany za wdrożony,
- ordinary Edit `publiclyVisible()` nadal nie zapisuje publicznych pól ani source relationship; publiczny renderer źródeł nadal nie istnieje i pozostaje N3,
- finalny exact-head gate PR #44: `quality` 968 passed / 18 917 assertions / 2 skipped, Pint 1010 files PASS, frontend build PASS; `newsroom-postgres` 7 passed / 89 assertions,
- następnym taskiem jest `NEWSROOM-N2-005` Relations and topics editor.

### 2026-09-16 — v0.20

- wdrożono i zmergowano NEWSROOM-N2-003 przez PR #42 na `main@13a22058c7c945196e8d490a0620b93dcf449641`,
- `ContentArticleResource` ma kontrolowany Filament Builder dla aktywnych bloków v1 oraz RichEditor zapisujący structured TipTap JSON zgodnie z `NewsroomBodyContract`,
- `NewsroomBodyEditorAdapter` oddziela efemeryczne UUID Buildera od kanonicznego `body_blocks.key`, zachowuje kolejność i normalizuje formularz do jedynego domenowego kontraktu przed zapisem,
- `embed` pozostaje feature-disabled/fail-closed zgodnie z N0-004; N2-003 nie włącza providerów bez osobnego CSP/provider security gate,
- image block przyjmuje storage-relative path, ale upload/object verification/crop pozostają dalszym media scope N2; dokumentacja nie przypisuje editorowi nieistniejącej weryfikacji assetu,
- zwykły Edit `publiclyVisible()` nadal nie może zmieniać body/public fields; regression potwierdza też trwałość canonical key oraz odrzucanie unsafe rich text/iframe/embed payloads,
- finalny gate PR #42: `quality` 963 passed / 18 895 assertions / 2 skipped, Pint 1010 files PASS, frontend build PASS; `newsroom-postgres` PASS,
- następnym taskiem jest `NEWSROOM-N2-004` Sources editor.

### 2026-09-16 — v0.19

- wdrożono i zmergowano NEWSROOM-N2-002 po zielonych jobach `quality` i `newsroom-postgres`,
- `ContentArticleResource` shell materializuje podstawowy admin CRUD surface bez dublowania domenowego slug/path/workflow foundation,
- create oraz draftowe type/slug changes reużywają `ContentArticleSlugService`; list/search/filters/eager loading obejmują wymagany baseline N2-002,
- ordinary Save `publiclyVisible()` rekordu nie mutuje publicznych pól; wewnętrzny `editorial_note` pozostaje osobnym zapisem,
- admin-only panel contract i rozdzielenie AuditLog `User` actor od publicznego `ContentAuthor` pozostają zachowane,
- Builder/workflow/stale-write/media/sources/preview/HomeComposer nadal nie są uznane za wdrożone; następnym krokiem jest N2-003,
- finalny gate PR #40: `quality` 951 passed / 18 857 assertions / 2 skipped, Pint 1008 files, frontend build PASS; `newsroom-postgres` 7 passed / 89 assertions.

### 2026-09-16 — v0.18

- wdrożono i zmergowano NEWSROOM-N2-001 po zielonych jobach `quality` i `newsroom-postgres`,
- pierwszy newsroom Filament resource zarządza kategoriami bez zmiany istniejącego public route/taxonomy contract,
- slug pozostaje stabilną publiczną tożsamością kategorii; delete/deactivation invariants są wymuszane również poza UI,
- table order wykorzystuje `position`; list/infolist pokazują all/publicly-visible/actively-distributed article counts,
- nie uznano całego CMS za wdrożony; następny krok to N2-002 ContentArticleResource shell.

### 2026-09-16 — v0.17

- wdrożono i zmergowano NEWSROOM-N1-006 po zielonych jobach `quality` i `newsroom-postgres`,
- dodano domenowy composer home z manual placements, future-preview validation, deterministycznym fallbackiem i globalnym card dedupe,
- dodano transakcyjny placement writer z kontrolowanymi slotami/context oraz PostgreSQL advisory-lock serialization dla overlap validation,
- breaking strip pozostaje niezależnym alertem i jedynym jawnym wyjątkiem od dedupe kart,
- future preview reużywa publication invariants przez niemutujący `assertScheduledPreviewReady()`,
- finalny gate: quality 935 passed / 18 769 assertions / 2 skipped, Pint 990 files, frontend build PASS; newsroom-postgres 7 passed / 89 assertions,
- zamknięto etap N1; następnym zadaniem wykonawczym jest N2-001 ContentCategoryResource.

### 2026-09-16 — v0.16

- wdrożono i zmergowano NEWSROOM-N1-005 po zielonych jobach `quality` i `newsroom-postgres`,
- `newsroom:publish-due` publikuje tylko due initial-scheduled records przez istniejący publishing service i pełną due-time rewalidację,
- invalid due record pozostaje `scheduled`; failure jest deduplikowanie audytowany/logowany i nie blokuje kolejnych rekordów,
- row lock serwisu oraz refetch/skip utrzymują idempotency przy powtórnym lub konkurencyjnym przetwarzaniu,
- production scheduler uruchamia komendę co minutę z `withoutOverlapping()`; distributed single-server guarantee nie jest deklarowany,
- scheduled republish z istniejącym `first_published_at` jest blokowany,
- finalny gate: quality 922 passed / 18 734 assertions / 2 skipped, Pint 985 files, frontend build PASS; newsroom-postgres 6 passed / 86 assertions,
- następnym taskiem wykonawczym jest NEWSROOM-N1-006 Home composition service.

### 2026-09-16 — v0.15

- wdrożono i zmergowano NEWSROOM-N1-004 po zielonych jobach `quality` i `newsroom-postgres`,
- `ContentArticlePublishingService` materializuje transakcyjny workflow review/schedule/publish/needs_review/archive/withdraw/restore/republish,
- AuditLog pozostaje oparty o `User` actora, a workflow event jest dispatchowany dopiero after commit,
- zachowano first-publish/date/public-state semantics i automatyczne czyszczenie breaking state przy wyjściu z aktywnej dystrybucji,
- withdrawn ustanawia domenowy tombstone, ale publiczne HTTP 410 pozostaje N3; `applyPublicUpdate` pozostaje N2,
- finalny gate: quality 917 passed / 18 656 assertions / 2 skipped, Pint 983 files, frontend build PASS; newsroom-postgres 6 passed / 86 assertions,
- następnym taskiem wykonawczym jest NEWSROOM-N1-005 scheduler.

### 2026-09-16 — v0.14

- wdrożono i zmergowano NEWSROOM-N1-003 po zielonych jobach `quality` i `newsroom-postgres`,
- dodano canonical slug allocation/change service, model redirect history, service-level canonical/path resolver i współdzielony PostgreSQL transaction advisory lock,
- published slug changes zapisują one-hop redirect history, historyczny full path innego artykułu pozostaje reserved, a own-history reclaim jest service-only,
- cross-family type change jest blokowany po pierwszej publikacji; same-family change zachowuje canonical family,
- PostgreSQL gate obejmuje realny lock contention i finalnie przeszedł 6 testów / 86 asercji; ogólny gate 906 passed / 18 565 assertions / 2 skipped, Pint 980 files, frontend build PASS,
- publiczne article controllers nadal nie istnieją, więc HTTP old-path 301 / canonical 200 pozostaje N3 integration,
- następnym taskiem wykonawczym jest NEWSROOM-N1-004.

### 2026-09-16 — v0.13

- wdrożono i zmergowano NEWSROOM-N1-002 po zielonych jobach `quality` i `newsroom-postgres`,
- dodano 6 modeli Eloquent newsroomu, factories i reverse relations do `ContentAuthor`, `Question`, `LegalUnit` i `TrafficSign`,
- `ContentArticle` implementuje odrębne `publiclyVisible`, `activelyDistributed`, `indexable` oraz pozostałe N1-002 scopes/predicates bez wprowadzania jeszcze workflow services,
- factories mają dokumentowane workflow states i reużywają `NewsroomBodyContract` dla body fixture,
- PostgreSQL gate obejmuje teraz cały `tests/Postgres`; finalnie 4 testy / 81 asercji PASS, a ogólny gate: 895 passed / 18 532 assertions / 2 skipped, Pint 974 files PASS, frontend build PASS,
- dedykowany category DB seeder oraz N1-003 slug/redirect service i dalsze N1 services pozostają otwarte,
- następnym taskiem wykonawczym jest NEWSROOM-N1-003.

### 2026-09-16 — v0.12

- wdrożono i zmergowano NEWSROOM-N1-001 po zielonych jobach `quality` i `newsroom-postgres`,
- dodano 5 enumów domenowych oraz 12 migracji materializujących newsroom schema,
- PostgreSQL 16 gate sprawdza migrate fresh, krytyczne indeksy/FK delete rules i rollback; finalny targeted wynik: 3 testy / 64 asercje PASS,
- zachowano dotychczasowy szybki SQLite `quality` job i dodano PostgreSQL jako addytywny gate,
- schema nie zawiera `canonical_url` ani `featured_position`; home composition nadal opiera się na `content_home_placements`,
- Eloquent models/factories/scopes pozostają N1-002 i nie są deklarowane jako istniejące.

### 2026-09-16 — v0.11

- wdrożono i zmergowano NEWSROOM-N0-006 po green CI,
- dodano `NewsroomMediaStorage` jako osobny newsroom storage/validation foundation bez reużycia `QuestionMedia` workflow,
- utrwalono immutable ULID source paths oraz backendową inspekcję actual bytes/MIME/dimensions,
- publiczny URL pozostaje współdzielony przez `MediaUrlResolver`, a SVG/non-raster/unmanaged paths failują,
- nie zadeklarowano nieistniejących crop variants; uploader UI/focal point/crop pipeline nadal należą do N2/N3,
- foundation N0 jest domknięte w zakresie wymaganym przed wejściem do N1; kolejnym taskiem jest N1-001.

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
