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
- Data: 2026-09-15
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

### Zakres

- audit HomePageController,
- audit Organization schema w publicznym content,
- audit logo references,
- ustalić canonical brand config/service,
- przenieść name/url/logo do jednego źródła,
- test Organization schema.

### Expected files

- config/brand.php lub równoważny
- app/Support/...Organization...
- HomePageController.php
- schema services
- tests

### DoD

- nie ma przypadkowego publisher „Orły na Drodze”,
- homepage i przyszły newsroom korzystają z tego samego źródła,
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

## NEWSROOM-N0-004 — Editor + sanitization decision

### Cel

Wybrać sposób przechowywania body.

### Opcje do sprawdzenia

- Filament rich editor + sanitization,
- Markdown + render pipeline.

### Decyzja musi opisać

- canonical source,
- allowed elements,
- link handling,
- image/embed handling,
- XSS tests.

### DoD

- brak „ustalimy podczas formularza”.

---

# N1 — Domain and database

## NEWSROOM-N1-001 — Enums + migrations

### Zakres

- ContentArticleType
- ContentArticleWorkflowStatus
- ContentArticleSourceType
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
- ContentArticleSource
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

## NEWSROOM-N2-003 — Body editor and sanitization

### Zakres

- final editor component,
- safe storage/render,
- XSS validation.

### Test cases

- script,
- onclick,
- javascript: links,
- unsafe iframe,
- allowed H2/list/link/blockquote.

---

## NEWSROOM-N2-004 — Sources editor

### Zakres

- source repeater/relation,
- primary/official badges,
- validation.

### DoD

- news cannot publish without required source policy.

---

## NEWSROOM-N2-005 — Relations editor

### Zakres

- questions searchable picker,
- legal units picker,
- optional traffic signs.

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

## NEWSROOM-N2-008 — Preview

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

## NEWSROOM-N3-003 — Article schema service

### Zakres

- NewsArticle/Article,
- Person author,
- Organization publisher,
- BreadcrumbList.

### DoD

- snapshot/unit tests.

---

## NEWSROOM-N3-004 — Article Blade page

### Zakres

- breadcrumbs,
- H1/lead/byline,
- hero,
- body,
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

# N4 — Hub, categories and guides

## NEWSROOM-N4-001 — /aktualnosci home query model

### Zakres

- lead,
- secondary,
- latest,
- category blocks,
- guides,
- breaking.

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
- feed later,
- invalidation events.

### Test

- publish shows article after invalidation,
- archive removes article from cached sections.

---

# N5 — SEO, distribution and analytics

## NEWSROOM-N5-001 — Articles sitemap

- all public indexable articles,
- meaningful lastmod.

---

## NEWSROOM-N5-002 — News sitemap

- recent news only,
- current Google constraints reverified at implementation,
- sitemap index integration.

---

## NEWSROOM-N5-003 — RSS/Atom feed

- latest items,
- stable GUID,
- correct content type,
- cache/invalidation.

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

## NEWSROOM-N5-006 — Link/content audit command

Optional but recommended before rollout.

Reports:

- orphan article,
- broken related relation,
- draft target,
- source URL empty,
- expired breaking.

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

## NEWSROOM-N6-003 — SEO production validation

- URL Inspection sample,
- schema validation,
- canonical,
- sitemap submit,
- robots.

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

- index status,
- crawl,
- impressions,
- canonical,
- structured data.

Nie zmieniamy architektury po 48 godzinach bez danych.

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
N1 publishing/slug/scheduler

### PR F
N2 categories CMS

### PR G
N2 article CMS basic

### PR H
N2 sources/relations/workflow/preview

### PR I
N3 article public

### PR J
N4 newsroom hub

### PR K
N4 categories/guides/nav/cache

### PR L
N5 sitemap/feed

### PR M
N5 analytics/IndexNow/audits

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
- [ ] sources
- [ ] relations
- [ ] preview
- [ ] checklist
- [ ] workflow

### Public

- [ ] article
- [ ] newsroom hub
- [ ] category
- [ ] guides
- [ ] responsive/accessibility

### SEO

- [ ] schema
- [ ] article sitemap
- [ ] news sitemap
- [ ] feed
- [ ] canonical
- [ ] author/publisher

### Operations

- [ ] scheduler monitored
- [ ] audit
- [ ] correction flow
- [ ] freshness
- [ ] analytics
- [ ] production smoke

---

# 7. No-go rules

Nie robimy podczas v1:

- osobnego WordPressa,
- mikroserwisu CMS,
- Elasticsearch tylko dla newsroomu,
- page buildera,
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

Na 2026-09-15:

- projekt dokumentacyjny jest w toku,
- implementacja newsroomu nie rozpoczęta,
- /aktualnosci i /poradniki nadal placeholder,
- fundamenty ContentAuthor/legal/traffic signs/public SEO istnieją.

---

# 11. Pierwszy następny task po zamknięciu dokumentacji

NEWSROOM-N0-001 — Publisher branding source of truth.

Dopiero po jego zamknięciu:

NEWSROOM-N0-002 / N0-003 / N0-004.

---

# 12. Historia zmian

### 2026-09-15 — v0.1

- utworzono wykonawczy backlog N0–N6,
- rozbito prace na małe gates,
- zdefiniowano PR boundaries, test gates, no-go rules i DoD,
- ustawiono branding cleanup jako pierwszy task.
