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

Każdy etap musi przejść:

- testy domenowe,
- testy HTTP/render,
- testy bezpieczeństwa,
- build frontendu,
- PostgreSQL,
- odpowiednie browser E2E,
- pełny istniejący CI przed merge.

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
- rollback świeżej migracji w środowisku testowym.

Nie uznajemy sqlite-only za wystarczające dla tabel docelowo działających na PostgreSQL.

---

## 6. Model tests

### ContentArticle

- casts,
- route key slug,
- published scope,
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
- delete restriction policy.

---

## 7. Workflow transition matrix tests

Dla każdego przejścia:

| From | Action | To | Expected |
| --- | --- | --- | --- |
| draft | submit review | in_review | PASS if minimum draft complete |
| in_review | return draft | draft | PASS |
| in_review | schedule | scheduled | PASS if publish checklist complete |
| in_review | publish | published | PASS if checklist complete |
| scheduled | publish due | published | PASS when due |
| published | needs review | needs_review | PASS |
| needs_review | republish/update | published | PASS after review |
| published | archive | archived | PASS |
| archived | direct publish | published | policy decision; default reject until reviewed |

Testować również niedozwolone przejścia.

---

## 8. Publishing invariants tests

Publish blokuje:

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

## 9. Date semantics tests

- first_published_at ustawione tylko przy pierwszym publish,
- późniejsza edycja nie zmienia first_published_at,
- scheduled_for nie staje się datePublished,
- last_substantive_update_at steruje dateModified policy,
- timezone serializacja poprawna.

---

## 10. Scheduler tests

### Due publish

- scheduled_for <= now -> published.

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
- duplicate rejected/resolved zgodnie z service,
- draft slug change no public redirect required,
- published slug change creates redirect,
- old path -> 301,
- new path -> 200,
- no redirect chain,
- sitemap only new URL.

---

## 12. Preview security tests

- anonymous no token -> denied,
- valid signed preview -> allowed jeśli taki model wybrany,
- expired signed preview -> denied,
- admin authenticated -> allowed,
- preview meta noindex,
- preview URL absent from sitemap,
- preview absent from feed,
- preview absent from newsroom home/category.

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
- allowlisted embed only.

---

## 14. Public article HTTP tests

Published:

- 200.

Draft:

- 404 on public route.

Scheduled future:

- 404.

Archived:

- according to chosen archive policy; test explicit.

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

## 15.1. Provenance, regulatory context and media tests

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

Article:

- home,
- aktualności/poradniki,
- category if part of visual path,
- article.

URLs absolute/canonical zgodnie z istniejącym schema pattern.

---

## 18. Source rendering tests

- published source visible,
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

## 19.1. Semantic silo / internal-link graph tests

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
- reverse link pojawia się tylko dla jawnej public relation,
- reverse link list ma bounded count i deterministic order,
- draft/noindex/redirect-source nie pojawia się w related/reverse modules,
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

- active manual placement wins,
- expired placement ignored,
- future placement ignored for current render,
- future preview resolves scheduled article only after selected publication time,
- no drafts in current public render,
- same article not repeated across card modules,
- fallback fills empty slot deterministically,
- insufficient unique candidates shorten section instead of duplicating,
- expired breaking not shown,
- breaking may point to lead as explicit alert exception,
- empty category block omitted.

---

## 21. Category tests

- only category articles,
- only published,
- descending published order,
- pagination,
- page 2 self-canonical,
- invalid category 404.

---

## 22. Topic / dossier tests

- draft topic public route -> 404,
- published topic -> 200,
- description rendered,
- featured article must be public,
- only public linked articles rendered,
- tag creation does not create topic URL,
- pagination/canonical correct,
- empty/thin topic publish validation according to CMS policy.

---

## 23. Guides tests

- only guide type according to product decision,
- published,
- no accidental news mixing unless explicitly designed.

---

## 24. Sitemap tests

### articles sitemap

- published indexable article included,
- draft/noindex/redirect-source excluded,
- archived policy honored,
- absolute HTTPS canonical URL,
- meaningful lastmod based on substantive public change,
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

## 26. Cache and crawler HTTP validator tests

Application cache:

- home cache hit possible,
- publish invalidates,
- archive invalidates,
- category affected invalidated,
- unrelated category not necessarily invalidated if granular design supports.

Sitemap/feed HTTP:

- initial request returns 200 + Content-Type + ETag and/or Last-Modified,
- matching If-None-Match / If-Modified-Since returns 304 with no stale body requirement,
- publish/archive/slug change that changes representation rotates validator and returns fresh 200,
- unchanged corpus keeps stable validator,
- no validator may cause a changed sitemap/feed to remain incorrectly 304.

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
- focal point persistence,
- relation persistence,
- publish blocked with missing requirements,
- schedule works,
- archive works.

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
- preview signed/auth,
- XSS,
- upload validation,
- no SSRF source fetch,
- CSRF admin writes,
- rate limiting public dynamic endpoints if any,
- no draft leaks through API/search/sitemap/feed.

---

## 37. CI integration

Nie tworzymy osobnego CI tylko dla newsroomu, jeśli obecne pipeline’y mogą go objąć.

Wymagane:

- php tests,
- lint/format zgodnie z repo,
- frontend build,
- Playwright targeted/full according to CI strategy,
- PostgreSQL test path.

Nowe testy muszą wejść do istniejących jobs, nie być lokalną instrukcją bez CI.

---

## 38. Pre-merge checklist

- [ ] diff ograniczony do task scope
- [ ] tests added/updated
- [ ] targeted tests green
- [ ] full CI green
- [ ] docs match code
- [ ] no plan described as implemented
- [ ] migrations reviewed
- [ ] no secrets/assets accidentally committed

---

## 39. First production release prerequisites

- N0 done,
- N1–N5 required scope done,
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

1. backup verification
2. deploy code
3. migrations
4. cache/config clear/build per normal deploy
5. scheduler registration verification
6. smoke /health
7. admin resource smoke
8. public /aktualnosci smoke
9. sample article smoke
10. sitemap/feed smoke
11. sitemap/feed conditional 304 smoke
12. homepage site-name/Organization graph smoke
13. logs check
14. Search Console actions after stable production

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

Rekomendowane rozwiązanie minimalne:

config/feature flag dla publicznego newsroomu może być rozważona przed release.

Jeśli nie ma globalnego feature flag system:

- route/hub może zostać tymczasowo wyłączony deployem,
- admin/data pozostają.

Nie dodawać rozbudowanego feature flag service tylko dla newsroomu, jeśli prosty config wystarczy.

---

## 45. Rollback of bad content

Nie wymaga deploy:

- archive,
- correction,
- remove breaking/featured.

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
- [ ] related question link
- [ ] related legal link
- [ ] reverse link z co najmniej jednej istniejącej entity/content page do newsroom article

### SEO

- [ ] canonical
- [ ] robots
- [ ] JSON-LD graph @id consistency
- [ ] homepage WebSite/site name + Organization
- [ ] no legacy Orły na Drodze identity
- [ ] author Person/ProfilePage reference
- [ ] news visible date + time + byline
- [ ] publication/publisher/company/contact information discoverable
- [ ] visible dates == structured date semantics
- [ ] OG image + alt + stable public URL
- [ ] og:site_name
- [ ] articles sitemap/shard
- [ ] news sitemap required metadata
- [ ] feed + head discovery
- [ ] sitemap/feed 304 validators

### Admin

- [ ] list
- [ ] edit
- [ ] article preview
- [ ] home composer
- [ ] future home preview
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
- CI obejmuje newsroom,
- first release ma backup/smoke/rollback plan,
- scheduler ma monitoring,
- entity graph/site identity ma regression coverage,
- semantic silo/orphan/reverse-link rules mają regression coverage,
- sitemap scaling/news metadata/304 mają regression coverage,
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
- newsroom entity graph/news sitemap/sharding/feed-discovery/304 tests jeszcze nie istnieją,
- istniejący SeoSitemapAuditor nie obsługuje jeszcze newsroom/news namespace.

---

## 59. Pozostałe zadania

- [ ] dodać test files w trakcie N1–N5,
- [ ] podłączyć do CI,
- [ ] stworzyć newsroom E2E,
- [ ] dodać block/composition/topic/focal-point tests,
- [ ] dodać site-identity/entity-graph/date-consistency tests,
- [ ] dodać semantic silo/orphan/reverse-link/click-depth tests,
- [ ] dodać news namespace + sitemap sharding + 304/feed-discovery tests,
- [ ] rozszerzyć istniejący SeoSitemapAuditor,
- [ ] stworzyć production smoke checklist w praktyce,
- [ ] po pierwszym release wpisać rzeczywiste wyniki i ewentualne różnice od planu.

---

## 60. Historia zmian

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
