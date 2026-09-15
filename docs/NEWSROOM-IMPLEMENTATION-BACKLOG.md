# Newsroom Implementation Backlog

## 1. Status

- Status: Canonical implementation plan before coding
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Specyfikacje wykonawcze:
  - [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md)
  - [NEWSROOM-ADMIN-CMS-SPEC.md](./NEWSROOM-ADMIN-CMS-SPEC.md)
  - [NEWSROOM-PUBLIC-UI-UX-SPEC.md](./NEWSROOM-PUBLIC-UI-UX-SPEC.md)
  - [NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md](./NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md)
  - [NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md](./NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md)
- Data: 2026-09-16
- Cel: rozbić newsroom na małe, weryfikowalne PR-y z jasnymi zależnościami i Definition of Done.

---

## 2. Zasada wykonywania

Każdy task przechodzi kolejno:

1. implementacja,
2. testy lokalne,
3. review kodu,
4. aktualizacja tylko powiązanej dokumentacji,
5. CI,
6. merge,
7. weryfikacja main,
8. dopiero kolejny task.

Nie łączymy kilku dużych etapów w jeden PR.

---

## 3. Gate hierarchy

~~~text
N0 FOUNDATION
  ↓
N1 DOMAIN + DB
  ↓
N2 CMS + PUBLISHING
  ↓
N3 PUBLIC ARTICLE
  ↓
N4 NEWSROOM HUB + CATEGORIES + GUIDES
  ↓
N5 SEO + FEEDS + ANALYTICS
  ↓
N6 PRODUCTION HARDENING + CONTENT ROLLOUT
~~~

---

# N0 — Foundation decisions and cleanup

## NEWSROOM-N0-001 — Publisher branding source of truth

### Cel

Usunąć niespójność PrawkoNaRaz / Orły na Drodze w structured data.

### Potwierdzony stan repo

Istnieją już:

- `config/content.php['organization']`,
- `SchemaIds::organization()` i `SchemaIds::website()`,
- `SchemaRenderer`,
- Organization/WebSite/Person graph pattern w istniejących schema services.

Problemem jest `HomePageController`, który nadal hardcoduje „Orły na Drodze” i stare logo.

### Zakres

- audit HomePageController,
- audit Organization/WebSite schema w publicznym content,
- audit logo references i dimensions,
- zachować istniejący organization config jako source of truth,
- wyekstrahować/reużyć wspólny site identity schema builder, jeśli obecna logika jest zduplikowana,
- zachować stabilne `/#organization` i `/#website`,
- dodać homepage WebSite name/url oraz sensowny alternateName/fallback,
- ujednolicić `og:site_name`,
- przenieść homepage name/url/logo na istniejący source of truth,
- test Organization + WebSite/site-name consistency.

### Expected files

- config/content.php tylko jeśli kontrakt wymaga uzupełnienia danych; nie tworzyć równoległego config/brand.php
- app/SEO/Schema/SchemaIds.php, jeśli potrzebne są newsroom IDs
- współdzielony SiteIdentity/Organization schema service albo refactor istniejącego schema service
- HomePageController.php
- resources/views/layouts/public-content.blade.php dla og:site_name/feed discovery contract
- schema services
- tests

### DoD

- nie ma przypadkowego publisher „Orły na Drodze”,
- homepage i przyszły newsroom korzystają z `config/content.php['organization']` i stabilnych graph IDs,
- domena ma jeden WebSite/site name, bez osobnego site name dla /aktualnosci,
- Organization logo jest publiczne, crawlable i spełnia ustalone dimensions baseline,
- test blokuje regresję.

### Dokumentacja

- NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md
- odpowiedni branding/menu doc, jeśli faktycznie zmieniony.

---

## NEWSROOM-N0-002 — Final route contract

### Decyzja

Preferowany:

- /aktualnosci
- /aktualnosci/kategoria/{categorySlug}
- /aktualnosci/temat/{topicSlug}
- /aktualnosci/{articleSlug}
- /poradniki
- /poradniki/{articleSlug}

### Zadania

- sprawdzić conflicts z istniejącymi routes,
- reserved slug list,
- route naming convention,
- test route matching.

### DoD

- brak ambiguity category vs article,
- dokumenty aktualizowane.

---

## NEWSROOM-N0-003 — Taxonomy seed contract

### Zakres

Zatwierdzić kategorie v1:

- prawo-jazdy
- egzaminy
- przepisy
- word
- kierowcy
- osk

### DoD

- seed contract,
- kolejność,
- nazwy publiczne,
- SEO descriptions mogą pozostać draftem, ale bez niejasności slugów.

---

## NEWSROOM-N0-004 — Block editor + serialization + sanitization decision

### Cel

Domknąć techniczny sposób edycji kanonicznego `body_blocks`.

### Do sprawdzenia

- Filament Builder lub równoważny komponent,
- format payloadu rich_text,
- schema payloadu każdego block type,
- sanitizer,
- allowlisted embeds.

### Decyzja musi opisać

- `body_blocks` jako jedyne źródło body,
- serializację,
- allowed nodes/elements,
- link handling,
- image/embed handling,
- unknown block behavior,
- XSS tests.

### DoD

- brak „ustalimy podczas formularza”,
- nie powstaje równoległe edytowalne `body_html`,
- wszystkie v1 block types mają kontrakt.

---

# N1 — Domain and database

## NEWSROOM-N1-001 — Enums + migrations

### Zakres

- ContentArticleType
- ContentArticleWorkflowStatus
- ContentArticleSourceType
- ContentArticleOriginType
- ContentArticleRegulatoryStatus
- content topics
- content home placements
- body_blocks + focal point + regulatory fields
- tables zgodne z data spec.

### Testy

- migrate fresh PostgreSQL,
- rollback,
- constraints,
- indexes.

### DoD

- schema rzeczywiście odpowiada docs,
- DATABASE-SCHEMA.md zaktualizowane tylko po merge.

---

## NEWSROOM-N1-002 — Models + factories

### Zakres

- ContentArticle
- ContentCategory
- ContentTag
- ContentTopic
- ContentArticleSource
- ContentHomePlacement
- relations
- scopes
- factories.

### Testy

- published scope,
- category scope,
- active breaking,
- source relations,
- question/legal pivots.

---

## NEWSROOM-N1-003 — Slug service + redirects

### Zakres

- initial slug,
- unique handling,
- published slug change,
- redirect record,
- no chains.

### Testy

- draft slug change,
- published slug change,
- old path redirect,
- duplicate slug reject.

---

## NEWSROOM-N1-004 — Publishing service

### Zakres

- submit for review,
- publish,
- schedule,
- archive,
- needs review.

### Testy

- invalid transition,
- missing requirements,
- first_published_at stable,
- date semantics.

---

## NEWSROOM-N1-005 — Scheduler

### Zakres

- newsroom:publish-due,
- scheduler registration,
- idempotency.

### Testy

- future not published,
- due published,
- already published ignored,
- repeated command safe.

---

## NEWSROOM-N1-006 — Home composition service

### Zakres

- resolve active placements,
- validate publication-at-preview-time,
- deterministic fallback,
- global card deduplication,
- context-aware category leads.

### Testy

- manual placement wins,
- expired/future placement ignored at current time,
- future preview resolves scheduled article only after its publish time,
- duplicate article excluded from later card modules,
- missing unique candidate shortens module.

### DoD

- public controller nie implementuje composition logic ręcznie,
- breaking strip może wskazać lead jako jedyny jawny wyjątek dedupe.

---

# N2 — CMS and editorial workflow

## NEWSROOM-N2-001 — ContentCategoryResource

### Zakres

- CRUD,
- active,
- order,
- article counts.

### DoD

- nie można skasować kategorii używanej przez artykuły.

---

## NEWSROOM-N2-002 — ContentArticleResource shell

### Zakres

- Resource,
- pages,
- table,
- infolist,
- basic form.

### DoD

- draft can be created,
- list filters/search work,
- eager loading no N+1.

---

## NEWSROOM-N2-003 — Block editor and sanitization

### Zakres

- final Builder/editor component,
- body_blocks ordering,
- schema per block type,
- safe rich-text storage/render,
- allowlisted embeds,
- XSS validation.

### Test cases

- script,
- onclick,
- javascript: links,
- unsafe iframe,
- allowed H2/list/link,
- unknown block type,
- invalid block payload,
- unsafe embed provider.

---

## NEWSROOM-N2-004 — Sources editor

### Zakres

- source repeater/relation,
- primary/official badges,
- validation.

### DoD

- news cannot publish without required source policy.

---

## NEWSROOM-N2-005 — Relations and topics editor

### Zakres

- questions searchable picker,
- legal units picker,
- optional traffic signs,
- topics picker/order.

### Performance

- no preload of all questions/legal units.

---

## NEWSROOM-N2-006 — Workflow actions

### Zakres

- review,
- schedule,
- publish,
- archive,
- featured,
- breaking.

### DoD

- actions call services,
- UI does not duplicate transition logic.

---

## NEWSROOM-N2-007 — Publication checklist

### Zakres

Computed blocking/warning items.

### DoD

- admin sees precise missing fields,
- publish cannot bypass blocking checklist.

---

## NEWSROOM-N2-008 — Article preview

### Zakres

- signed/authenticated route,
- noindex,
- public-like renderer,
- preview banner.

### Security tests

- anonymous without valid token denied,
- expired token denied,
- no sitemap/feed exposure.

---

## NEWSROOM-N2-009 — NewsroomHomeComposer + future preview

### Zakres

- fixed slot UI,
- article search,
- starts_at / ends_at,
- fallback visibility,
- duplicate warnings,
- preview entire `/aktualnosci` at selected timestamp.

### DoD

- redaktor nie tworzy nowych layout modules,
- preview korzysta z tego samego composition service co publiczny hub,
- future preview uwzględnia scheduled publishing.

---

## NEWSROOM-N2-010 — Provenance, regulatory context and media art direction

### Zakres

- origin_type,
- regulatory_status/effective_from/change_summary/applies_to/exam_impact,
- focal point control,
- crop previews,
- publish checklist warnings.

### DoD

- prawny/regulacyjny news ma spójny status i źródło,
- redaktor widzi efekt cropu przed publikacją,
- origin type jest kontrolowanym enumem.

---

## NEWSROOM-N2-011 — ContentTopicResource

### Zakres

- topic CRUD,
- status,
- description,
- featured article,
- article ordering,
- SEO metadata.

### DoD

- topic nie powstaje automatycznie z taga,
- draft topic nie jest publiczny,
- publish waliduje minimalny corpus/description.

---

# N3 — Public article

## NEWSROOM-N3-001 — Public catalog service

### Zakres

- findPublishedBySlug,
- related data,
- eager load policy.

### DoD

- draft/scheduled hidden.

---

## NEWSROOM-N3-002 — Article SEO service

### Zakres

- title,
- description,
- canonical,
- robots,
- OG/Twitter,
- dates.

---

## NEWSROOM-N3-003 — Article schema graph service

### Zakres

Re-use istniejących `SchemaIds` / `SchemaRenderer`.

Graph:

- WebSite `/#website`,
- Organization `/#organization`,
- WebPage `{canonical}#webpage`,
- NewsArticle/Article `{canonical}#article`,
- Person `/autorzy/{slug}#person`,
- BreadcrumbList,
- ImageObject nodes.

Dodatkowo:

- mainEntityOfPage -> WebPage,
- publisher/author przez @id,
- articleSection/inLanguage,
- datePublished/dateModified policy,
- realne image variants, jeśli istnieją.

### DoD

- nie ma literalnych, rozjeżdżających się kopii publishera/authora,
- IDs są stabilne,
- visible dates i schema dates są spójne,
- snapshot/unit tests.

---

## NEWSROOM-N3-004 — Article Blade page + block renderer

### Zakres

- breadcrumbs,
- H1/lead/byline/provenance,
- hero + focal-point crops,
- regulatory/exam context box,
- body block renderer,
- sources,
- correction,
- related modules,
- author box.

### Browser QA

- 360/390/430,
- 768,
- 1024,
- 1440.

---

## NEWSROOM-N3-005 — Product bridge

### Zakres

- questions,
- legal,
- signs,
- contextual CTA.

### DoD

- no random relations,
- no draft targets.

---

## NEWSROOM-N3-006 — Redirect resolver

### Zakres

Old article path -> 301 canonical.

### Test

- exactly one hop.

---

## NEWSROOM-N3-007 — Author profile integration

### Zakres

Re-use istniejącego `ContentAuthorController` i ProfilePage.

- dodać opublikowane ContentArticle do publicznej listy publikacji autora,
- ujednolicić ProfilePage mainEntity Person do stabilnego `/autorzy/{slug}#person`,
- Person worksFor -> canonical `/#organization`,
- author sitemap lastmod uwzględnia najnowszy publiczny newsroom article,
- article graph referuje dokładnie ten sam Person @id.

### DoD

- nie istnieje drugi newsroom author/profile model,
- article -> author URL działa,
- author page -> article działa,
- ProfilePage i Article mają identyczną identity autora,
- nieopublikowane articles nie wpływają na publiczny profil/sitemap.

---

# N4 — Hub, categories and guides

## NEWSROOM-N4-001 — /aktualnosci editorial composition read model

### Zakres

- fixed placements,
- lead,
- secondary,
- latest,
- category blocks,
- guides,
- breaking,
- fallback,
- global card deduplication.

### Performance

- bounded queries,
- eager loads,
- cacheable read model.

---

## NEWSROOM-N4-002 — Hub Blade layout

### Zakres

- UI spec implementation,
- responsive,
- empty section behavior.

### DoD

- no MarketingPlaceholder.

---

## NEWSROOM-N4-003 — Category pages

### Zakres

- category header,
- chronological list,
- pagination,
- SEO.

---

## NEWSROOM-N4-004 — /poradniki hub

### Zakres

- guide-only listing,
- evergreen UI variant,
- article reuse.

---

## NEWSROOM-N4-005 — Navigation integration

### Zakres

- PublicNavigation,
- documentNavigationPrefixes if required,
- footer links,
- active states.

### DoD

- Vue + Blade renderers verified.

---

## NEWSROOM-N4-006 — Cache

### Zakres

- home,
- category,
- topic,
- feed later,
- invalidation events.

### Test

- publish shows article after invalidation,
- placement change invalidates home,
- archive removes article from cached sections.

---

## NEWSROOM-N4-007 — Topic / dossier pages

### Zakres

- public topic route,
- intro/description,
- featured article,
- ordered/latest topic corpus,
- pagination,
- SEO.

### DoD

- only published topics public,
- no automatic tag pages,
- thin/empty topic not launched.

---

# N5 — SEO, distribution and analytics

## NEWSROOM-N5-001 — Articles sitemap + deterministic sharding

- wszystkie publiczne indexable articles,
- meaningful lastmod,
- absolute canonical HTTPS URLs,
- exclude noindex/draft/redirect-source,
- istniejący sitemap index jako parent,
- deterministic sharding readiness przed limitami pojedynczego pliku,
- żadnego niestabilnego offset-shardingu.

### Testy

- single shard,
- boundary crossing,
- stable shard assignment,
- no duplicate URL across shards,
- XML size/URL-count guard.

---

## NEWSROOM-N5-002 — News sitemap

- current Google constraints reverified at implementation,
- recent news eligibility po `first_published_at`, nie po updated/published refresh,
- tylko type=news + published + indexable,
- news:name = canonical publication name,
- news:language = pl,
- news:publication_date = original first_published_at,
- news:title = visible title bez author/brand/date,
- split powyżej aktualnego limitu entry,
- sitemap index integration.

### Testy

- old updated article nie wraca do news sitemap,
- required namespace/tags,
- publication date timezone/format,
- 1000-entry boundary zgodnie z aktualną specyfikacją.

---

## NEWSROOM-N5-003 — RSS/Atom feed + discovery

- latest items,
- stable GUID,
- correct content type,
- cache/invalidation,
- `<link rel="alternate" type="application/rss+xml|application/atom+xml">` w publicznym layoutcie,
- public absolute canonical links.

---

## NEWSROOM-N5-004 — Analytics hooks

- article view,
- module click,
- product bridge,
- sources,
- related links.

### Privacy

- no body or PII in event params.

---

## NEWSROOM-N5-005 — IndexNow integration review

### Cel

Re-use existing IndexNow pipeline if appropriate.

### DoD

- publish/update can submit asynchronously,
- failure does not block article publication.

---

## NEWSROOM-N5-006 — Extend existing SEO/sitemap audits

Rozszerzyć istniejący `SeoSitemapAuditor` zamiast tworzyć równoległy sitemap validator.

Raportuje/testuje m.in.:

- duplicate/canonical sitemap loc,
- article/news shard limits,
- required news namespace/tags,
- old article in news sitemap,
- noindex/draft/redirect source in sitemap,
- orphan article,
- broken related relation,
- draft target,
- source URL empty,
- expired breaking.

Można dodać newsroom-specific `newsroom:audit-links` dla graph/link checks, ale sitemap rules pozostają w istniejącym audytorze.

---

## NEWSROOM-N5-007 — Crawler-facing HTTP caching

### Zakres

Sitemap + feed responses:

- ETag i/lub Last-Modified,
- conditional request -> 304, gdy representation unchanged,
- Cache-Control zgodny z invalidation,
- zmiana corpus -> nowy validator / 200 z nowym outputem.

### Stan istniejący

Obecny `SitemapController` ustawia Content-Type, ale nie ma jeszcze validators/304.

### DoD

- 304 działa dla unchanged response,
- publish/archive/slug-change nie zostawia stale XML/feed,
- testy HTTP headers i body.

---

# N6 — Production hardening and rollout

## NEWSROOM-N6-001 — E2E golden path

Admin:

draft -> sources -> relations -> preview -> publish.

Public:

hub -> article -> related question -> product.

---

## NEWSROOM-N6-002 — Scheduled publication production smoke

- create scheduled sample,
- verify not visible before,
- verify visible after scheduler,
- verify sitemap/feed.

---

## NEWSROOM-N6-003 — Enterprise SEO production validation

- homepage site name/WebSite validation,
- Organization logo crawlability/dimensions,
- URL Inspection sample per article/category/topic,
- Rich Results/Schema validator,
- graph @id consistency,
- visible dates vs datePublished/dateModified,
- canonical + Google-selected canonical sample,
- main sitemap index submit,
- child sitemap/shard diagnostics where useful,
- news sitemap sample,
- feed discovery,
- robots,
- conditional 304 sample for sitemap/feed,
- Search Console segmentation articles/categories/topics/guides.

---

## NEWSROOM-N6-004 — Performance pass

Measure:

- article,
- hub,
- category.

Check:

- LCP,
- CLS,
- TTFB,
- query count,
- image weight.

---

## NEWSROOM-N6-005 — Security pass

- XSS,
- preview auth,
- admin policy,
- upload validation,
- source URL no server-side fetch.

---

## NEWSROOM-N6-006 — First editorial batch

Recommended:

- 3 news,
- 3 explainers,
- 3 guides,
- 1 data/analysis sample only if methodology ready.

Nie zaczynamy od 100 artykułów.

---

## NEWSROOM-N6-007 — Search Console observation window

Po rollout:

- submitted vs indexed,
- crawl,
- impressions/clicks,
- declared vs Google-selected canonical,
- structured data,
- Discover, jeśli raport się pojawi,
- osobna obserwacja newsroom paths i sitemap/shards.

Nie zmieniamy architektury po 48 godzinach bez danych.

---

## NEWSROOM-N6-008 — Preferred Sources eligibility check

Po ustabilizowaniu domenowego publisher/site identity:

- sprawdzić, czy `prawkonaraz.pl` jest dostępne w Google Preferred Sources tool,
- jeśli tak, ocenić button/deeplink jako kontrolowany eksperyment UX,
- nie wdrażać agresywnego popupu,
- brak eligibility nie blokuje newsroomu.

---

## NEWSROOM-N6-009 — Publisher transparency production gate

### Zakres

Audit publicznych powierzchni przed regularnym rolloutem:

- article headline/date+time/byline,
- author profile,
- publication/publisher/company identity,
- contact information,
- correction/editorial principles discoverability,
- sponsorship disclosure contract.

Preferować istniejące Organization/Contact/Methodology pages. Nowy publiczny dokument/page tylko gdy istniejące powierzchnie nie pokrywają realnego wymagania.

### DoD

- czytelnik może łatwo ustalić kto napisał i kto publikuje materiał,
- kontakt jest publicznie dostępny,
- news ma jasną datę/czas/byline,
- publishingPrinciples nie jest emitowane bez realnej publicznej strony,
- brak założenia, że Publisher Center enrollment gwarantuje/warunkuje Google News.

---

# 4. PR boundaries

Rekomendacja:

### PR A
N0 branding

### PR B
N0 routes/taxonomy/editor decision docs + small code if needed

### PR C
N1 migrations/enums

### PR D
N1 models/factories

### PR E
N1 publishing/slug/scheduler/home composition service

### PR F
N2 categories + topics CMS

### PR G
N2 article CMS + controlled block editor

### PR H
N2 sources/relations/workflow/provenance/media + article/home preview

### PR I
N3 article public + author profile integration

### PR J
N4 newsroom hub

### PR K
N4 categories/topics/guides/nav/cache

### PR L
N5 article/news sitemap + sharding + feed/discovery + HTTP validators

### PR M
N5 analytics/IndexNow/existing-auditor extension

### PR N
N6 tests/hardening

Nie jest wymagane dokładnie 14 PR-ów, ale każde połączenie musi zachować reviewability.

---

# 5. Test gates per PR

Każdy PR backend:

- targeted tests,
- full backend CI przed merge.

Frontend/public:

- build,
- targeted browser test,
- full CI.

DB:

- PostgreSQL, nie tylko sqlite.

Docs-only:

- link consistency/manual diff.

---

# 6. Definition of Done całego newsroom v1

### Domain

- [ ] schema wdrożona
- [ ] models/factories
- [ ] publishing service
- [ ] scheduling
- [ ] redirects

### CMS

- [ ] article resource
- [ ] category resource
- [ ] topic resource
- [ ] controlled block editor
- [ ] sources
- [ ] relations
- [ ] origin/regulatory fields
- [ ] focal point/crop preview
- [ ] article preview
- [ ] home composer + future preview
- [ ] checklist
- [ ] workflow

### Public

- [ ] article + controlled block renderer
- [ ] regulatory context box/provenance
- [ ] author profile/newsroom publication integration
- [ ] newsroom hub with placements/fallback/dedupe
- [ ] category
- [ ] topic/dossier
- [ ] guides
- [ ] responsive/accessibility

### SEO

- [ ] stable entity graph + site identity
- [ ] visible/schema dates consistency
- [ ] article sitemap + deterministic sharding readiness
- [ ] news sitemap full required metadata
- [ ] feed + discovery
- [ ] sitemap/feed HTTP validators + 304
- [ ] canonical
- [ ] author ProfilePage / publisher / WebSite

### Operations

- [ ] publisher transparency/contact/editorial principles gate
- [ ] scheduler monitored
- [ ] audit
- [ ] correction flow
- [ ] freshness
- [ ] analytics
- [ ] production smoke

---

# 6.1. Planned post-v1 extensions

Te elementy są architektonicznie przewidziane, ale nie blokują newsroom v1:

- audio/TTS article derivative,
- transcript lifecycle,
- AI summary,
- „zapytaj o ten artykuł”.

Przed rozpoczęciem każdego z nich wymagany jest osobny task z kontraktem bezpieczeństwa, UX, danych i SEO.

Nie implementujemy z wyprzedzeniem pustych tabel/pól tylko dla tych rozszerzeń.

---

# 7. No-go rules

Nie robimy podczas v1:

- osobnego WordPressa,
- mikroserwisu CMS,
- Elasticsearch tylko dla newsroomu,
- dowolnego page buildera (kontrolowane body blocks i stałe homepage placements są częścią v1),
- revision snapshot/diff/restore systemu,
- komentarzy,
- newslettera,
- automatycznego AI publish,
- masowego local SEO,
- systemu reklamowego,
- infinite scroll,
- recommendation ML.

---

# 8. Ryzyka implementacyjne

## R1 — brand schema mismatch

Mitigation: N0-001 przed NewsArticle.

## R2 — rich text XSS

Mitigation: N0-004 + N2-003 przed public renderer.

## R3 — routy kategorii kolidują ze slugami

Mitigation: N0-002.

## R4 — CMS bypassuje domain service

Mitigation: test actions + services as single write path.

## R5 — scheduler nie działa na prod

Mitigation: N6 scheduled smoke + monitoring.

## R6 — sitemap/feed stale cache

Mitigation: event-driven invalidation + tests.

## R7 — duże obrazy degradują LCP

Mitigation: image baseline + perf pass.

## R8 — redakcja publikuje projekt prawa jako obowiązujące

Mitigation: editorial source/status policy + reviewer gate.

## R9 — homepage powtarza te same materiały w wielu modułach

Mitigation: jeden NewsroomHomeCompositionService + exclusion set + testy deduplikacji.

## R10 — crop obrazu ucina istotny subject

Mitigation: focal point + preview wariantów + testy media contract.

## R11 — topic pages stają się thin SEO pages

Mitigation: ręczna publikacja topicu, minimalny corpus, własny opis i brak automatycznego mapowania tag -> topic.

## R12 — site identity rozjeżdża się między homepage i newsroomem

Mitigation: jeden config/content.php organization + stabilne SchemaIds + test graph consistency.

## R13 — sitemap przestaje skalować lub przesuwa URL między shardami

Mitigation: deterministic sharding + hard limits + duplicate audit.

## R14 — news sitemap fałszuje świeżość po aktualizacji starego artykułu

Mitigation: eligibility wyłącznie po first_published_at + boundary tests.

## R15 — crawler otrzymuje kosztowne pełne XML przy każdym request

Mitigation: cache validators + conditional 304 + invalidation tests.

---

# 9. Aktualizacja dokumentacji podczas wdrożenia

Po każdym tasku:

- architecture: tylko jeśli zmienia się decyzja,
- data spec: jeśli zmienia się model,
- CMS spec: jeśli zmienia się backoffice,
- UI spec: jeśli zmienia się publiczny kontrakt,
- SEO spec: jeśli zmienia się dystrybucja,
- backlog: oznaczyć wykonanie,
- current docs jak DATABASE-SCHEMA/MENU/TEST dopiero gdy kod faktycznie zmieniony.

Nie oznaczać tasku DONE przed merge + green verification.

---

# 10. Aktualny stan

Na 2026-09-16:

- pakiet projektowy newsroomu obejmuje architekturę, model danych, CMS, UI, governance, SEO, backlog i runbook,
- niniejsze rozszerzenie doprecyzowuje editorial composition; implementacja newsroomu nadal nie rozpoczęta,
- /aktualnosci i /poradniki nadal placeholder,
- fundamenty ContentAuthor/legal/traffic signs/public SEO istnieją,
- istnieją config/content.php organization, SchemaIds/SchemaRenderer, sitemap builder/auditor i IndexNow pipeline,
- HomePageController nadal ma legacy „Orły na Drodze”, a sitemap responses nie mają jeszcze 304 validators.

---

# 11. Pierwszy następny task po zamknięciu dokumentacji

NEWSROOM-N0-001 — Publisher branding source of truth.

Dopiero po jego zamknięciu:

NEWSROOM-N0-002 / N0-003 / N0-004.

---

# 12. Historia zmian

### 2026-09-16 — v0.3

- skorygowano N0-001 do realnego istniejącego site identity/schema infrastructure,
- rozbudowano N3 schema task do stabilnego graphu z @id,
- dodano deterministic sitemap sharding, pełny News Sitemap contract i istniejący auditor reuse,
- dodano feed discovery oraz crawler-facing ETag/Last-Modified/304,
- rozszerzono production SEO validation i Search Console segmentation,
- dodano post-launch Preferred Sources eligibility check oraz nowe ryzyka enterprise SEO.

### 2026-09-15 — v0.2

- rozszerzono N0-004 o kontrolowany block editor i serializację body_blocks,
- dodano home composition service, NewsroomHomeComposer/future preview oraz topic resource/pages,
- dodano provenance, regulatory context i focal-point/crop work,
- rozszerzono publiczny renderer o bloki, context box i deduplikowaną kompozycję huba,
- audio/AI zapisano jako post-v1 extensions,
- revision snapshot/diff/restore świadomie pozostawiono poza zakresem.

### 2026-09-15 — v0.1

- utworzono wykonawczy backlog N0–N6,
- rozbito prace na małe gates,
- zdefiniowano PR boundaries, test gates, no-go rules i DoD,
- ustawiono branding cleanup jako pierwszy task.
