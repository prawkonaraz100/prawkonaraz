# Newsroom Test, Release and Rollback Runbook

## 1. Status

- Status: Proposed / canonical quality and release plan before implementation
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Backlog: [NEWSROOM-IMPLEMENTATION-BACKLOG.md](./NEWSROOM-IMPLEMENTATION-BACKLOG.md)
- Powiązane:
  - [TEST-STRATEGY.md](./TEST-STRATEGY.md)
  - [CI-CD.md](./CI-CD.md)
  - [RUNBOOK-OPS.md](./RUNBOOK-OPS.md)
  - [NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md](./NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md)
- Data: 2026-09-16
- Cel: zapewnić, że newsroom jest wdrażany i wycofywany bez zgadywania.

---

## 2. Zasada nadrzędna

Newsroom nie ma obniżać jakości istniejącego produktu.

Każdy etap musi przejść odpowiednie do ryzyka:

- testy domenowe,
- testy HTTP/render,
- testy bezpieczeństwa,
- build frontendu,
- PostgreSQL dla newsroom DB/concurrency paths,
- odpowiednie browser E2E,
- pełny istniejący CI przed merge.

Stan wejściowy: kanoniczny `ci.yml` używa SQLite. Dlatego od PR C/N1 dokładamy osobny PostgreSQL gate zamiast udawać, że obecny „full CI green” już go obejmuje.

---

## 3. Warstwy testów

### 3.1. Unit

Dla:

- enum/value semantics,
- SEO fallback,
- schema builder,
- checklist computation,
- slug normalization,
- freshness policy,
- body block validation,
- homepage placement resolution,
- homepage deduplication,
- regulatory/origin labels,
- focal-point normalization.

### 3.2. Feature / integration

Dla:

- migrations/model relations,
- publishing workflow,
- scheduling,
- redirects,
- topics,
- homepage placements,
- future homepage preview,
- routes,
- sitemap/feed,
- admin policies,
- preview security.

### 3.3. Rendering tests

Dla:

- meta,
- structured data,
- body block rendering/sanitization,
- regulatory context,
- provenance/byline,
- focal-point-aware media variants,
- article modules,
- homepage composition/deduplication,
- topic page,
- breadcrumbs,
- category pagination.

### 3.4. E2E

Dla krytycznego redakcyjnego golden path.

---

## 4. Proponowana mapa testów

Nazwy są targetem, nie opisem istniejących plików.

~~~text
tests/Feature/Newsroom/
  ContentArticleModelTest.php
  ContentArticlePublishingTest.php
  ContentArticleSchedulingTest.php
  ContentArticleSlugRedirectTest.php
  ContentArticlePreviewTest.php
  ContentArticlePublicPageTest.php
  ContentArticleCategoryPageTest.php
  ContentArticleSitemapTest.php
  ContentArticleNewsSitemapTest.php
  ContentArticleFeedTest.php
  ContentArticleSecurityTest.php
  ContentArticlePolicyTest.php
  ContentTopicTest.php
  ContentHomePlacementTest.php
  NewsroomHomeTest.php
  NewsroomHomePreviewTest.php

tests/Unit/Newsroom/
  ContentArticleSeoServiceTest.php
  ContentArticleSchemaServiceTest.php
  ContentArticleChecklistTest.php
  ContentArticleFreshnessTest.php
  ContentArticleBlockValidationTest.php
  NewsroomHomeCompositionServiceTest.php

tests/e2e/
  newsroom-editorial.spec.ts
  newsroom-public.spec.ts
~~~

Jeśli repo ma inną ustaloną konwencję katalogów Pest/Playwright, nazwy dopasować bez zmiany pokrycia scenariuszy.

---

## 5. Migration tests

Minimum na PostgreSQL:

- migrate fresh,
- constraints,
- foreign keys,
- unique slug,
- indexes istnieją zgodnie z migration,
- FK delete directions: article delete nie może skasować category/author/question/legal/sign,
- expected child/pivot cascade usuwa wyłącznie newsroom-owned rows,
- rollback świeżej migracji w środowisku testowym.

Nie uznajemy sqlite-only za wystarczające dla tabel docelowo działających na PostgreSQL.

PR C/N1 musi dodać addytywny job `newsroom-postgres` z usługą PostgreSQL (lub równoważny automatyczny job w istniejącym workflow). Nie usuwamy szybkiego SQLite CI. Do czasu dodania joba PR musi mieć jawny, powtarzalny PostgreSQL evidence; po dodaniu joba ręczny evidence nie zastępuje jego wyniku.

---

## 6. Model tests

### ContentArticle

- casts,
- route key slug,
- publiclyVisible / activelyDistributed / indexable scopes,
- archived historical 200 vs active distribution,
- needs_review public visibility,
- scheduled scope,
- category relation,
- author/reviewer relation,
- sources,
- tags,
- question pivots,
- legal pivots.

### Category

- active scope,
- articles relation,
- delete restriction policy,
- immutable slug v1,
- deactivate blocked while public/actively-distributed articles depend on category.

### Topic

- draft/published/archived scopes,
- publish requires own description + min. 3 actively-distributed/indexable linked articles,
- featured article if set belongs to topic and is public,
- slug immutable after first publication.

---

## 7. Workflow transition matrix tests

Dla każdego przejścia:

| From | Action | To | Expected |
| --- | --- | --- | --- |
| draft | submit review | in_review | PASS if minimum draft complete |
| in_review | return draft | draft | PASS |
| in_review + first_published_at=null | schedule | scheduled | PASS if publish checklist complete |
| already-public article | schedule republish | scheduled | REJECT in v1; use Apply public update |
| in_review | publish | published | PASS if checklist complete |
| scheduled | publish due | published | PASS when due |
| published | needs review | needs_review | PASS; clears breaking flag/expiry |
| needs_review | republish/update | published | PASS after review |
| published | archive | archived | PASS; clears breaking flag/expiry |
| published/needs_review/archived | withdraw | withdrawn | PASS with reason; breaking cleared |
| archived | direct publish | published | REJECT in v1; return to review flow first |
| withdrawn | direct publish | published | REJECT; restore to review first |
| withdrawn | restore to review | in_review | PASS; withdrawal tombstone remains active and public URL stays 410 |
| in_review + active withdrawal tombstone | publish | published | PASS if checklist complete; clears current tombstone after audit |

Testować również niedozwolone przejścia.

---

## 8. Publishing invariants tests

Initial Publish / Apply public update blokuje odpowiednio:

- brak title,
- brak slug,
- brak lead,
- brak renderowalnego body_blocks,
- invalid block payload,
- brak category,
- brak author,
- brak wymaganych źródeł,
- reviewer missing when policy requires,
- invalid media state, jeśli dany warunek jest blocking.

---

### 8.1. Transaction / audit / after-commit tests

- publish state + timestamps + AuditLog commit atomowo,
- AuditLog actor = authenticated `User`, author/reviewer = `ContentAuthor`,
- audit metadata nie zawiera `body_blocks`, leadu ani prywatnych notes,
- rolled-back publish nie czyści public cache, nie ustawia sitemap dirty signal i nie enqueue'uje IndexNow,
- scheduler/system publish zapisuje jawny system/scheduler trigger i nie fałszuje user actor,
- source/author/reviewer/slug/public-state changes po publikacji mają wymagane audit events.

---

## 9. Date semantics tests

- first_published_at ustawione tylko przy pierwszym publish,
- późniejsza edycja nie zmienia first_published_at,
- scheduled_for nie staje się datePublished,
- last_substantive_update_at steruje dateModified policy,
- public_state_changed_at nie zmienia dateModified,
- archive/withdraw/robots public-state change aktualizuje sitemap lastmod semantics,
- techniczny updated_at nie zmienia dateModified/lastmod,
- timezone serializacja poprawna.

---

## 10. Scheduler tests

### Due initial publish

- scheduled_for <= now + current checklist/invariants still valid -> published.
- never-published scheduled article nie ma first_published_at/published_at ustawionych przed due publish.
- category became inactive / author unpublished / required source or reviewer invalidated after scheduling -> not published; logged/audited failure.

### Future

- scheduled_for > now -> unchanged.

### Idempotency

- drugi run -> bez duplikatów/event duplication.

### Multiple records

- publikuje tylko due + valid.

### Failure isolation

Jeśli jeden rekord nie może się opublikować, strategia ma być jawna:

- log failure,
- pozostałe rekordy nie powinny zostać zablokowane bez potrzeby.

---

## 11. Slug and redirect tests

- initial slug unique,
- duplicate current slug rejected/resolved zgodnie z service,
- canonical path colliding with another article historical `from_path` rejected,
- same-article historical path reclaim rewrites/removes conflicting redirect and leaves all old paths one-hop to current canonical,
- draft slug change no public redirect required,
- published slug change creates redirect,
- old path -> 301,
- new path -> 200,
- no redirect chain,
- sitemap only new URL.

---

### 11.1. Published edit safety tests

Given publiclyVisible article:

- zwykły low-level Filament save public field jest blocked/read-only albo routed do dedicated use case,
- zmiana title/lead/body/source/hero przez `Apply public update` commit atomowo,
- invalid payload nie zmienia żadnego public field,
- stale token -> reject bez partial update,
- meaningful update ustawia `last_substantive_update_at`,
- internal-only note update nie ustawia substantive timestamp i nie zmienia public output,
- scheduled republish publicznego 200 jest rejected,
- audit nie przechowuje pełnego body.

---

## 12. Preview security tests

V1 nie ma shareable signed preview.

- anonymous -> denied,
- non-admin authenticated user -> denied,
- admin authenticated -> allowed,
- response `Cache-Control: private, no-store`,
- preview meta noindex,nofollow,
- preview URL absent from sitemap,
- preview absent from feed,
- preview absent from newsroom home/category,
- preview event nie jest liczony jako public article view.

---

## 13. Block validation and sanitization security tests

Payloads:

- unknown block type,
- malformed block payload,
- script tag inside rich_text,
- onclick attribute,
- javascript: URL,
- iframe unknown host,
- style injection,
- malformed HTML,
- SVG payload if editor allows image markup.

Expected:

- unknown/invalid block rejected or safely ignored according to contract,
- unsafe markup removed/escaped/rejected zgodnie z wybraną strategią,
- no arbitrary HTML/CSS/JS execution,
- allowed rich text/formatting preserved,
- server-side sanitizer/structured renderer testowany niezależnie od browser/Filament,
- allowlisted embed only.

Format-evolution tests:

- `body_schema_version` jest wymagany/obsługiwany zgodnie z kontraktem,
- zapisany payload starszej wspieranej wersji nadal się renderuje,
- nowy block type nie może być tworzony przez editor przed dostępnością renderera,
- unknown future block failuje bezpiecznie, bez wykonywania HTML,
- breaking schema migration ma jawny migrator/test; revision-history snapshot nie jest do tego wymagany.

---

## 14. Public article HTTP tests

Published:

- 200.

Draft:

- 404 on public route.

Scheduled future:

- 404.

Archived:

- previously published archived article -> 200 canonical detail,
- excluded from home/latest/category/topic active listings, feed and news sitemap,
- included in standard article sitemap only when indexable,
- archived + controlled noindex -> 200 noindex and absent from article sitemap,
- never-published archived record -> 404,
- archive itself never implies 301/404/410.

Unknown slug:

- 404.

Old slug:

- 301 canonical.

---

## 15. Article meta tests

Assertions:

- title,
- meta description,
- canonical,
- robots,
- og:type,
- og:site_name,
- og:title,
- og:url,
- og:image when available,
- article:published_time,
- article:modified_time when applicable,
- twitter card,
- RSS/Atom discovery link.

---

### 15.1. Provenance, regulatory context and media tests

Assertions:

- origin label rendered only for supported origin type,
- internal origin notes never leak,
- regulatory box shows only applicable non-empty fields,
- proposal/consultation is not rendered as in_force,
- effective_from uses expected public date format,
- focal point stays within 0..1 contract,
- generated/card image variant uses focal metadata,
- missing focal point falls back to center,
- hero dimensions remain present.

---

## 16. Structured data tests

Decode JSON-LD graph and assert:

- valid JSON,
- exactly one canonical WebSite identity for the domain graph,
- WebSite @id = canonical root + /#website,
- Organization @id = canonical root + /#organization,
- Article/NewsArticle @id stable for canonical URL,
- WebPage @id stable for canonical URL,
- Person author @id references public /autorzy/{slug}#person,
- @type correct per article type,
- headline,
- datePublished = first_published_at,
- dateModified policy,
- visible dates match equivalent structured dates,
- timezone/offset correct for Europe/Warsaw,
- author name is only author name, not role/brand suffix,
- publisher references canonical Organization @id,
- mainEntityOfPage references WebPage @id,
- image references only real, public assets,
- articleSection,
- inLanguage.

Homepage-specific:

- WebSite name/url present on homepage,
- no competing newsroom-specific WebSite/site-name node,
- Organization logo URL is public/crawlable and dimensions baseline is satisfied,
- legacy „Orły na Drodze” absent after N0-001.

Nie snapshotować dynamicznych pól bez stabilizacji czasu/config.

---

## 17. Breadcrumb tests

Newsroom article:

- Home -> Aktualności -> primary category -> article.

Guide:

- Home -> Poradniki -> guide,
- primary category może być osobnym linkiem klasyfikacyjnym, ale nie elementem głównego breadcrumb.

Visual breadcrumb i BreadcrumbList są zgodne; URLs absolute/canonical zgodnie z istniejącym schema pattern.

---

## 18. Source rendering tests

- `is_publicly_cited=true` source visible,
- public citation with null URL renders as text without broken anchor,
- `is_publicly_cited=false` source title/publisher/url/note never leaks,
- URL escaped,
- title escaped,
- external link safe,
- internal editorial note not rendered,
- private metadata not leaked.

---

## 19. Product bridge tests

- linked public question appears,
- inactive/nonpublic question omitted,
- legal relation valid,
- traffic sign relation valid,
- destination URLs canonical.

---

### 19.1. Semantic silo / internal-link graph tests

Fixtures:

- article z jedną primary category,
- article z topic relation,
- article -> legal/question/sign relations,
- reverse-link eligible entity pages,
- draft/noindex/redirected article targets.

Assertions:

- article ma crawlable primary-category link,
- category/topic pages linkują do public article przez zwykłe `<a href>`,
- indexable article ma co najmniej jeden public inbound link w fixture graph,
- archived+indexable article nadal ma inbound co najmniej z author profile archive surface,
- reverse link pojawia się tylko dla jawnej public relation i activelyDistributed target przy NEWSROOM_PUBLIC_ENABLED=true,
- article-question write nie zmienia rekordów `question_relations`, `question_seo_topics` ani aktywnego rankingu V1/V2,
- reverse link list ma bounded count i deterministic order,
- draft/needs_review/archived/withdrawn/noindex/redirect-source nie pojawia się w related/reverse modules,
- related anchors są opisowe; nie generujemy pustych/„kliknij tutaj” anchors jako domyślnego UI,
- ten sam URL nie jest bez potrzeby powielany w kilku related modules,
- audit wykrywa orphan i nadmierny click depth dla ważnych fixtures.

Sitemap inclusion sama nie spełnia inbound-link assertion.

---

## 20. Newsroom home composition tests

Given fixtures:

- manual lead placement,
- secondary placements,
- latest,
- category content,
- guide,
- fallback candidates,
- scheduled future article.

Assert:

- active manual placement wins only for activelyDistributed target,
- expired placement ignored,
- future placement ignored for current render,
- future preview resolves scheduled article only after selected publication time,
- no draft/needs_review/archived/withdrawn targets in current public card slots,
- same article not repeated across card modules,
- fallback fills empty slot deterministically,
- insufficient unique candidates shorten section instead of duplicating,
- expired breaking not shown,
- breaking may point to lead as explicit alert exception,
- empty category block omitted,
- dwa równoległe zapisy overlapping placement nie mogą oba przejść,
- stale home-composer write jest odrzucony przed nadpisaniem cudzej zmiany.

---

## 21. Category tests

- only actively distributed category articles,
- only activelyDistributed (`published`) included; needs_review/archived keep detail URL but are excluded from active listing,
- first_published_at DESC + deterministic tie-breaker,
- pagination,
- page 2 self-canonical,
- invalid category 404,
- category slug edit blocked in v1,
- active=false blocked when public/actively-distributed articles depend on category.

---

## 22. Topic / dossier tests

- draft topic public route -> 404,
- published topic -> 200,
- description rendered,
- featured article must be activelyDistributed + indexable and belong to topic,
- only activelyDistributed + indexable linked articles rendered,
- tag creation does not create topic URL,
- pagination/canonical correct,
- publish blocked below 3 actively-distributed/indexable linked articles,
- featured article, if set, belongs to topic and is public,
- published topic slug change blocked,
- corpus falling below baseline po wcześniejszym publish nie powoduje automatycznego 404/410; topic pozostaje 200, CMS/audit sygnalizuje health warning i topic nie jest promowany,
- explicit archived topic -> 410 jeśli był wcześniej publiczny, absent from sitemap/nav.

---

## 23. Guides tests

- only guide type according to product decision,
- published,
- no accidental news mixing unless explicitly designed.

---

### 23.1. Author profile integration tests

- shared author ProfilePage schema builder preserves existing traffic-sign/legal behavior and adds stable Person @id/worksFor,
- author profile pokazuje activelyDistributed newsroom articles,
- archived+indexable pozostaje dostępne jako oznaczona publikacja archiwalna i daje crawlable inbound do artykułu,
- archived+noindex może być pominięte,
- needs_review/withdrawn/draft/scheduled nie pojawiają się na publicznej liście,
- article graph author @id == ProfilePage Person @id,
- publish/archive/needs-review/withdraw/restore zmienia author profile output i właściwy author sitemap lastmod,
- techniczny article updated_at bez public output change nie zmienia author sitemap lastmod,
- author with only newsroom public/indexable content is included in authors.xml,
- NEWSROOM_PUBLIC_ENABLED=false usuwa newsroom publications z author page i author sitemap freshness contribution,
- ContentAuthor unpublish is rejected while dependent indexable/publiclyVisible newsroom articles exist.

---

## 24. Sitemap tests

### articles + hub sitemap

- newsroom rozszerza istniejący statyczny `SeoSitemapGenerator`,
- published indexable article included,
- /aktualnosci, /poradniki, active category hubs i published/indexable topic hubs mają sitemap coverage,
- draft/noindex/redirect-source excluded,
- archived policy honored,
- absolute HTTPS canonical URL,
- meaningful lastmod = max(first_published_at, last_substantive_update_at, public_state_changed_at),
- deterministic shard assignment,
- no URL duplicated across shards,
- shard remains within current URL-count and uncompressed-size limits,
- crossing a shard boundary does not reshuffle unrelated historical URLs.

### news.xml

At implementation reverify Google requirements.

Tests based on verified rules:

- only type=news,
- only published/indexable,
- recent window determined by first_published_at,
- old article with recent substantive update remains excluded if first_published_at is outside window,
- required news namespace,
- news:name exactly from canonical publication identity,
- news:language = pl,
- news:publication_date = original first_published_at in allowed format,
- news:title = visible title without author/publication/date additions,
- split before current news-entry limit,
- old news excluded from news sitemap but still in articles sitemap if indexable.

### Static publication safety

Regression test najpierw odtwarza/chroni przed potwierdzonym obecnym problemem: existing generator nie może delete-all `public/sitemaps/*.xml` i publikować main indexu zanim replacement child files są gotowe.

- generation builds/validates complete next set before switch,
- child files are published before new main index,
- failure before index switch leaves previous complete set active,
- obsolete shards are removed only after index switch,
- no index entry points to a missing child at any observable checkpoint,
- existing question/sign/legal/author sitemap files remain present and semantically unchanged unless their own data changed.

### Existing auditor integration

- extend `SeoSitemapAuditor`,
- duplicate loc checks cover newsroom shards,
- canonical-host rules cover newsroom,
- invalid news metadata produces audit failure.

---

## 25. Feed tests

- XML valid,
- content type,
- stable guid,
- pub date,
- latest order,
- draft excluded,
- absolute canonical item URLs,
- HTML head discovery link points to correct feed,
- cache invalidated after publish.

---

## 26. Cache, dirty-marker refresh and crawler delivery tests

Application cache:

- home cache hit possible,
- publish invalidates,
- archive invalidates,
- category affected invalidated,
- unrelated category not necessarily invalidated if granular design supports.

Sitemap refresh coordinator:

- successful public-state commit ustawia dirty/version signal dopiero after commit,
- freshness overdue bez osobnego workflow trigger nie ustawia article na needs_review ani nie usuwa go z active distribution,
- rollbacked DB transaction nie ustawia public sitemap change,
- scheduler przy braku dirty marker kończy się tanio,
- burst kilku publikacji coalescuje się do ograniczonej liczby pełnych refreshy,
- shared Redis/distributed lock blokuje równoległy pełny refresh; multi-node scheduler ma `onOneServer()` lub równoważny single-run guard,
- zmiana version podczas generacji pozostawia wymagany kolejny pass,
- publish request nie wywołuje pełnego `SeoSitemapGenerator::generate`,
- test działa przy `QUEUE_CONNECTION=sync`; nie wymaga worker process,
- failed refresh nie cofa publikacji, pozostawia recovery signal/log,
- daily scheduled `seo:refresh-sitemaps` nadal działa jako recovery path.

Static HTTP delivery:

- production-like test/HTTP smoke odczytuje faktyczny statyczny `sitemap.xml` / child files,
- poprawny Content-Type,
- brak Set-Cookie,
- jeśli Nginx/CDN ma validators: matching conditional request zwraca 304,
- zmiana artefaktu zmienia validator,
- test nie uznaje headerów `SitemapController` za wystarczające, jeśli statyczny plik ma pierwszeństwo.

Feed może mieć oddzielny test validators na warstwie aplikacyjnej.

Nie testować implementation detail cache key jeśli kontrakt może być testowany przez rezultat.

---

## 27. Analytics tests

Server/render tests:

- data attributes stable,
- article id/type/category present where expected.

JS tests/E2E:

- one click -> one event,
- no PII,
- no event on disabled/non-link element.

---

## 28. Filament/CMS tests

Minimum:

- resource visible to admin,
- unauthorized user denied,
- create draft,
- update draft,
- body block persistence/reorder/validation,
- source persistence,
- origin/regulatory persistence,
- topic relation persistence,
- hero caption/focal point persistence,
- brak edytowalnego canonical override,
- relation persistence,
- publish blocked with missing requirements,
- schedule works,
- archive works with deterministic public-detail policy,
- withdraw requires reason and produces 410/zero distribution,
- only admin can access newsroom resources/actions,
- User audit actor differs from ContentAuthor author/reviewer identity,
- stale article update rejected,
- stale/overlapping home placement update rejected,
- category slug/deactivation guards,
- topic corpus/slug guards,
- admin-only preview with private,no-store.

---

## 29. E2E Golden Path A — editorial

~~~text
login admin
→ open Content Articles
→ create draft
→ type/category/author/origin
→ title/lead
→ add/reorder body blocks
→ add regulatory context if applicable
→ add source
→ link question/topic
→ set hero focal point
→ save
→ preview
→ submit review
→ publish
→ open public URL
→ verify article
~~~

---

## 30. E2E Golden Path B — scheduled

~~~text
create complete article
→ schedule future near test-controlled time
→ public URL unavailable
→ advance/test clock or execute due condition
→ run scheduler command
→ public URL available
→ hub contains article
→ feed/sitemap correct
~~~

Production smoke nie używa sztucznego time travel; używa bezpiecznego realnego test record lub staging.

---

## 31. E2E Golden Path — editorial homepage composition

~~~text
publish several articles
→ open Newsroom Home Composer
→ assign lead/secondary/category lead
→ schedule one future placement
→ preview current /aktualnosci
→ verify no duplicate cards
→ preview future timestamp
→ verify future scheduled article appears only after its publish time
→ open public /aktualnosci
→ verify current composition
~~~

---

## 32. E2E Golden Path C — slug change

~~~text
publish article
→ capture URL A
→ change slug through admin flow
→ URL A 301
→ URL B 200
→ canonical B
→ internal hub links B
~~~

---

## 33. Responsive browser matrix

Minimum public visual/functional QA:

- 360x800
- 390x844
- 430x932
- 768x1024
- 1024x768
- 1440x900

Sprawdzić:

- no horizontal overflow,
- title wrapping,
- image aspect,
- nav scroll,
- source URLs,
- table overflow,
- CTA.

---

## 34. Accessibility QA

Automated + manual:

- heading order,
- landmark roles,
- keyboard tab,
- visible focus,
- link names,
- image alt,
- contrast,
- form labels,
- error association,
- reduced motion.

Nie uznajemy samego Lighthouse score za pełny accessibility test.

---

## 35. Performance QA

Local/staging:

- query count,
- N+1,
- HTML size,
- image dimensions/weight,
- JS bundle delta.

Production after rollout:

- LCP,
- INP,
- CLS,
- TTFB.

---

## 36. Security QA

- authz policies,
- preview admin-only auth + private/no-store,
- XSS,
- upload validation,
- no SSRF source fetch,
- CSRF admin writes,
- rate limiting public dynamic endpoints if any,
- no draft leaks through API/search/sitemap/feed.

---

## 37. CI integration

Zachowujemy istniejący szybki SQLite CI i dodajemy tylko brakujący gate.

Wymagane:

- istniejący php tests / smoke / Pint / frontend build bez regresji,
- addytywny `newsroom-postgres` job od PR C dla migracji, constraints, transactional/concurrency paths,
- Filament/feature tests w automatycznym CI,
- `newsroom-editorial.spec.ts` i `newsroom-public.spec.ts` jako jawny browser release gate po ustabilizowaniu CMS/public renderer.

Istniejący `browser-smoke.yml` dotyczy produktu i nie jest dowodem przejścia newsroom E2E. Newsroom E2E może początkowo działać jako osobny manual/workflow gate przed produkcją, ale nie może pozostać lokalną, nieweryfikowalną instrukcją.

---

### 37.1. Public gate integration tests

Przy pre-launch `NEWSROOM_PUBLIC_ENABLED=false`:

- article/category/topic public routes nie ujawniają newsroom content,
- existing top-level placeholder behavior pozostaje zgodne z decyzją rollout,
- author page nie pokazuje newsroom publications,
- existing question/legal/sign pages nie pokazują newsroom reverse links,
- feed nie ujawnia newsroom items,
- sitemap generator nie dodaje newsroom URLs,
- IndexNow collector nie zgłasza newsroom URLs,
- admin resource + private preview nadal działają.

Przy `true` te powierzchnie działają zgodnie z public eligibility.

---

## 38. Pre-merge checklist

- [ ] diff ograniczony do task scope
- [ ] tests added/updated
- [ ] targeted tests green
- [ ] full existing CI green
- [ ] jeśli PR dotyka newsroom DB/concurrency: newsroom-postgres green
- [ ] docs match code
- [ ] no plan described as implemented
- [ ] migrations reviewed
- [ ] no secrets/assets accidentally committed

---

## 39. First production release prerequisites

- wymagane hard gates G0–G5 zamknięte,
- N1–N5 required scope done,
- `NEWSROOM_PUBLIC_ENABLED=false` przed cutover,
- newsroom editorial + public browser E2E green,
- at least sample production-safe content,
- backups healthy,
- scheduler healthy,
- no unrelated failing CI,
- canonical APP_URL correct,
- Search/robots production config correct.

---

## 40. Backup before first newsroom migrations

Use existing ops process.

Recommended:

- verify latest backup freshness,
- create manual DB backup if release changes production schema,
- record manifest/location in release note.

Nie kopiować sekretów ani DB dump do repo.

---

## 41. Deployment sequence — first release

### Phase A — deploy dark

1. backup verification
2. potwierdź `NEWSROOM_PUBLIC_ENABLED=false`
3. deploy code
4. migrations
5. cache/config clear/build per normal deploy
6. scheduler registration + dirty-marker coordinator verification
7. smoke /health i pełny istniejący produkt smoke
8. admin resource + authenticated private preview smoke
9. PostgreSQL/newsroom backend checks
10. wygeneruj/audytuj statyczne sitemap i potwierdź brak regresji istniejących question/sign/legal/author XML
11. robots/static delivery baseline HTTP check

Jeśli Phase A failuje, publiczny newsroom nadal jest wyłączony.

### Phase B — controlled cutover

12. przygotuj sample production-safe content
13. włącz `NEWSROOM_PUBLIC_ENABLED=true` i odśwież config cache w kontrolowanym oknie
14. public /aktualnosci + category/topic + sample article/guide smoke
15. sprawdź canonical/schema/author/breadcrumb
16. sprawdź dirty-marker refresh -> świeży news/article sitemap bez czekania na daily cron
17. sprawdź main index -> wszystkie child files 200
18. sitemap static HTTP headers / conditional 304 smoke, jeśli skonfigurowane
19. feed smoke/validators
20. homepage site-name/Organization graph smoke
21. robots HTTP response + Sitemap directive
22. sprawdź scheduler/coordinator logs/locks/failures
23. Search Console actions po stabilnym production

Przed pierwszym launch rollback dark-deploy może użyć `NEWSROOM_PUBLIC_ENABLED=false`.

Po pierwszym publicznym/indexowanym launch nie używamy długiego `false` jako technicznego rollbacku powodującego masowe 404. Temporary technical incident -> 503/Retry-After lub rollback kodu zachowujący URL-e. Zła pojedyncza treść -> withdrawn.

---

## 42. Migration safety

Przed produkcją:

- migrations additive where possible,
- no destructive rename/drop without separate plan,
- indexes assessed for lock/time on current table sizes,
- FK behavior reviewed.

Newsroom v1 tworzy głównie nowe tabele, więc ryzyko dla istniejących danych powinno być małe.

---

## 43. Rollback layers

### Layer A — feature/content rollback

Najpierw:

- unfeature/unbreaking,
- archive problematic article,
- remove newsroom nav link if necessary,
- leave tables intact.

### Layer B — application rollback

Deploy previous code revision if regression.

Nowe tabele mogą pozostać, jeśli są backward-compatible and unused.

### Layer C — database rollback

Tylko jeśli:

- migracje bezpiecznie reversible,
- nie utracimy realnej treści redakcyjnej,
- backup exists.

Nie uruchamiać migrate:rollback automatycznie po tym, jak redakcja stworzyła dane, bez oceny utraty danych.

---

## 44. Emergency disable strategy

Prosty config gate `NEWSROOM_PUBLIC_ENABLED` jest wymaganym elementem v1 release.

- wyłącza publiczny newsroom/article/category/topic rollout bez usuwania admin/data,
- nie wymaga rozbudowanego feature flag service,
- jest sprawdzany w config cache/deploy smoke,
- przed launch przy false blokuje też author/reverse-link/feed/sitemap/IndexNow discovery,
- po launch emergency procedure rozróżnia technical 503/code rollback od content withdrawn; nie masowo 404 przez flagę.

Nie cofamy migracji ani treści tylko po to, by wyłączyć publiczną ekspozycję.

---

## 45. Rollback of bad content

Nie wymaga deploy:

- correction,
- remove breaking/featured,
- archive, jeśli historyczny 200 jest bezpieczny,
- withdraw, jeśli materiał musi natychmiast przestać być publicznie dostępny.

Nie używamy archive jako substytutu takedownu.

To jest główny powód rozdzielenia content state od kodu.

---

## 46. Scheduler failure procedure

Symptom:

scheduled article nie opublikował się.

Check:

1. scheduler/cron health
2. command logs
3. article status
4. scheduled_for/timezone
5. checklist failure
6. DB exception

Recovery:

- jeśli content gotowy i czas minął, publisher może publish now,
- naprawić root cause,
- nie edytować first_published_at fałszywie.

---

## 47. Sitemap failure procedure

Symptom:

/sitemaps/news.xml lub article shard zwraca 500, invalid XML, duplicate URL albo błędne news metadata.

Actions:

1. remove/disable only broken sitemap endpoint/shard from index if necessary,
2. public articles remain available,
3. fix generator/auditor failure,
4. validate XML + namespace + limits,
5. verify HTTP validators are not serving stale content,
6. restore index reference.

Sitemap failure nie powinien wyłączać publicznego newsroomu.

---

## 48. Feed failure procedure

Feed może być tymczasowo wyłączony bez wyłączania articles.

Nie pozwalamy, aby błąd feed serialization powodował 500 na article publish request.

---

## 49. Bad canonical procedure

High priority.

If production article canonical points to wrong host/path:

1. fix config/render ASAP,
2. invalidate cache,
3. verify sample URLs,
4. regenerate sitemap/feed if affected,
5. inspect Search Console later.

---

## 50. Draft leak incident

Jeśli draft stał się publiczny:

1. disable public access,
2. remove from hub/sitemap/feed,
3. inspect how leak occurred,
4. audit whether other drafts exposed,
5. fix policy/query,
6. add regression test.

---

## 51. XSS incident

1. disable affected article/public renderer if needed,
2. sanitize/remove payload,
3. audit all body records,
4. rotate/review admin sessions if compromise suspected,
5. patch sanitizer,
6. add payload regression test.

---

## 52. Production smoke checklist

### Core

- [ ] /
- [ ] /aktualnosci
- [ ] one category
- [ ] one topic/dossier
- [ ] one news with multiple block types
- [ ] one guide
- [ ] /autorzy/{author}
- [ ] /o-nas
- [ ] /kontakt
- [ ] /metodologia
- [ ] /robots.txt — rzeczywista odpowiedź zawiera canonical Sitemap directive
- [ ] related question link
- [ ] related legal link
- [ ] reverse link z co najmniej jednej istniejącej entity/content page do newsroom article

### SEO

- [ ] self-canonical bez CMS override
- [ ] route family exclusivity
- [ ] robots — statyczny/produkcyjny response zweryfikowany HTTP
- [ ] JSON-LD graph @id consistency
- [ ] homepage WebSite/site name + Organization
- [ ] no legacy Orły na Drodze identity
- [ ] author Person/ProfilePage reference
- [ ] news visible date + time + byline
- [ ] publication/publisher/company/contact information discoverable
- [ ] visible dates == structured date semantics
- [ ] OG image + alt + stable public URL
- [ ] og:site_name
- [ ] articles sitemap/shard z istniejącego static generatora
- [ ] news sitemap required metadata + fresh dirty-marker/scheduled refresh
- [ ] child-before-index atomic publication
- [ ] feed + head discovery
- [ ] sitemap static delivery headers/304 na faktycznej warstwie
- [ ] brak regresji istniejących question/sign/legal/author sitemap

### Admin

- [ ] admin allowed / non-admin denied
- [ ] list
- [ ] edit
- [ ] stale edit rejected
- [ ] article preview private,no-store
- [ ] home composer
- [ ] overlapping/stale placement rejected
- [ ] future home preview private,no-store
- [ ] AuditLog actor/state metadata sane
- [ ] save draft

---

## 53. Post-release 24h review

Check:

- error logs,
- 404 anomalies,
- scheduled jobs,
- sitemap health,
- asset errors,
- analytics events,
- cache behavior.

Nie oczekujemy pełnych danych SEO w 24h.

---

## 54. Post-release 7d review

Check:

- Search Console discovery/indexing by newsroom segment,
- submitted vs indexed per sitemap/shard,
- crawl errors,
- declared vs Google-selected canonical sample,
- impressions/clicks initial,
- Core Web Vitals field data może jeszcze nie być kompletne,
- editorial friction,
- correction bugs,
- CMS usability.

---

## 55. Post-release 30d review

Ocenić:

- kategorię ruchu,
- article -> product CTR,
- returning users,
- publishing cadence,
- freshness backlog,
- performance.

Dopiero wtedy rozważać:

- popular ranking,
- local WORD expansion,
- home / hybrid redesign,
- newsletter.

---

## 56. Release record

Każdy większy release newsroomu powinien zanotować:

- commit/PR,
- migrations,
- content count,
- sitemap/feed change,
- smoke result,
- rollback note.

Może być w PR/release notes; nie potrzebujemy nowej tabeli tylko do tego.

---

## 57. Definition of Done runbook

Runbook jest spełniony, gdy:

- wszystkie krytyczne flows mają test,
- istniejący CI + dedykowany PostgreSQL gate obejmuje newsroom,
- newsroom browser E2E jest jawnie wykonanym release gate,
- first release ma backup/smoke/rollback + public config-disable plan,
- scheduler ma monitoring,
- entity graph/site identity ma regression coverage,
- route-family/canonical exclusivity ma regression coverage,
- semantic silo/orphan/reverse-link rules mają regression coverage bez modyfikacji question graphu,
- static sitemap atomic publication/dirty-marker refresh/news metadata ma regression coverage bez wymogu queue workera,
- rzeczywista warstwa static delivery/robots ma production smoke,
- content może być cofnięty bez deploy,
- draft/XSS/canonical incidents mają procedurę,
- test/release docs są aktualizowane po faktycznej zmianie pipeline.

---

## 58. Stan implementacji

Na 2026-09-16:

- istnieją globalne backend tests,
- istnieje Playwright smoke dla produktu,
- istnieją ops backup/restore/health commands,
- newsroom-specific tests i E2E jeszcze nie istnieją,
- homepage placement/topic/block editor tests jeszcze nie istnieją,
- newsroom entity graph/news sitemap/sharding/feed-discovery/static-delivery tests jeszcze nie istnieją,
- atomic static publication i dirty/version newsroom refresh coordinator jeszcze nie istnieją,
- canonical CI jest SQLite-only; newsroom-postgres job jeszcze nie istnieje,
- newsroom-specific browser E2E nie jest pokryty istniejącym product browser smoke,
- istniejący SeoSitemapAuditor nie obsługuje jeszcze newsroom/news namespace.

---

## 59. Pozostałe zadania

- [ ] dodać test files w trakcie N1–N5,
- [ ] podłączyć do CI,
- [ ] stworzyć newsroom E2E,
- [ ] dodać block/composition/topic/focal-point tests,
- [ ] dodać site-identity/entity-graph/date-consistency tests,
- [ ] dodać semantic silo/orphan/reverse-link/click-depth tests,
- [ ] dodać route-family/canonical exclusivity tests,
- [ ] dodać newsroom-postgres CI job,
- [ ] dodać audit/stale-write/placement-concurrency/category-topic guard tests,
- [ ] dodać news namespace + sitemap sharding + atomic publish + dirty-marker refresh/feed-discovery tests,
- [ ] dodać production-like static robots/sitemap delivery smoke,
- [ ] rozszerzyć istniejący SeoSitemapAuditor,
- [ ] stworzyć production smoke checklist w praktyce,
- [ ] po pierwszym release wpisać rzeczywiste wyniki i ewentualne różnice od planu.

---

## 60. Historia zmian

### 2026-09-16 — v0.5

- wyrównano runbook z faktycznym SQLite CI przez wymagany additive newsroom-postgres gate,
- dodano historical full-path collision/reclaim, backend sanitizer/body version, newsroom-specific media i author-unpublish tests,
- static publication regression jawnie obejmuje istniejący delete-all/index-first generator gap,
- dark-deploy false oddzielono od post-launch temporary rollback 503/code rollback,
- preview v1 zmieniono na admin-only/private-no-store i dodano rozdzielenie User actor vs ContentAuthor identity,
- dodano stale-write, placement concurrency, category/topic identity oraz block-format compatibility tests,
- archive otrzymało deterministyczny historical-200 contract, a withdrawn jawny 410 takedown flow,
- async queue assumptions zastąpiono dirty/version coordinator tests działającymi przy QUEUE_CONNECTION=sync,
- NEWSROOM_PUBLIC_ENABLED stał się wymaganym dark-deploy/cutover/rollback gate,
- newsroom browser E2E oddzielono od istniejącego product browser smoke.

### 2026-09-16 — v0.4

- dodano route-family/canonical exclusivity tests,
- dodano gwarancję, że newsroom article-question edges nie modyfikują istniejącego question graphu,
- zastąpiono controller-centric sitemap validator tests testami rzeczywistego statycznego delivery,
- dodano child-before-index atomic publication, async/debounced refresh i daily recovery tests,
- dodano produkcyjny robots/static sitemap smoke bez usuwania istniejącego backendu.

### 2026-09-16 — v0.3

- usunięto zduplikowany Expected w security test contract,
- rozszerzono structured data tests do pełnego stabilnego entity graphu i site-name identity,
- dodano visible/schema date consistency oraz author ProfilePage reference tests,
- rozbudowano sitemap tests o full News Sitemap metadata, deterministic sharding i istniejący SeoSitemapAuditor,
- dodano feed discovery i conditional ETag/Last-Modified/304 tests,
- rozszerzono production smoke i Search Console review o enterprise SEO checks.

### 2026-09-15 — v0.2

- rozszerzono test matrix o body blocks, homepage placements, future preview i deduplikację,
- dodano topic/dossier, provenance/regulatory i focal-point testy,
- rozszerzono golden path CMS i dodano golden path kompozycji strony głównej,
- doprecyzowano security/performance smoke dla nowych mechanizmów,
- nie dodano testów revision snapshot/diff/restore, ponieważ funkcja jest poza zakresem.

### 2026-09-15 — v0.1

- utworzono quality/release/rollback runbook,
- zdefiniowano warstwy testów, golden paths i responsive/security QA,
- zdefiniowano first production release sequence,
- zapisano bezpieczny rollback bez automatycznego cofania DB po powstaniu realnych treści,
- dodano procedury scheduler/sitemap/canonical/draft/XSS incidents.
