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
- Data: 2026-09-15
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
- freshness policy.

### 3.2. Feature / integration

Dla:

- migrations/model relations,
- publishing workflow,
- scheduling,
- redirects,
- routes,
- sitemap/feed,
- admin policies,
- preview security.

### 3.3. Rendering tests

Dla:

- meta,
- structured data,
- body sanitization,
- article modules,
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
  NewsroomHomeTest.php

tests/Unit/Newsroom/
  ContentArticleSeoServiceTest.php
  ContentArticleSchemaServiceTest.php
  ContentArticleChecklistTest.php
  ContentArticleFreshnessTest.php

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
- brak body,
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

## 13. Sanitization security tests

Payloads:

- script tag,
- onclick attribute,
- javascript: URL,
- iframe unknown host,
- style injection,
- malformed HTML,
- SVG payload if editor allows image markup.

Expected:

- removed/escaped/rejected zgodnie z wybraną strategy,
- allowed formatting retained.

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
- og:title,
- og:url,
- og:image when available,
- article:published_time,
- article:modified_time when applicable,
- twitter card.

---

## 16. Structured data tests

Decode JSON-LD and assert:

- valid JSON,
- @type correct per article type,
- headline,
- datePublished,
- dateModified policy,
- author name/url,
- publisher canonical identity,
- mainEntityOfPage,
- image,
- articleSection,
- inLanguage.

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

## 20. Newsroom home tests

Given fixtures:

- one lead/featured,
- secondary,
- latest,
- category content,
- guide.

Assert:

- no drafts,
- no future scheduled,
- correct order,
- expired breaking not shown,
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

## 22. Guides tests

- only guide type according to product decision,
- published,
- no accidental news mixing unless explicitly designed.

---

## 23. Sitemap tests

### articles.xml

- published indexable article included,
- draft excluded,
- archived policy honored,
- canonical URL,
- meaningful lastmod.

### news.xml

At implementation reverify Google requirements.

Tests based on verified rules:

- only news,
- only recent window,
- required news fields,
- old news excluded from news sitemap but still in articles sitemap.

---

## 24. Feed tests

- XML valid,
- content type,
- stable guid,
- pub date,
- latest order,
- draft excluded,
- cache invalidated after publish.

---

## 25. Cache tests

- home cache hit possible,
- publish invalidates,
- archive invalidates,
- category affected invalidated,
- unrelated category not necessarily invalidated if granular design supports.

Nie testować implementation detail cache key jeśli kontrakt może być testowany przez rezultat.

---

## 26. Analytics tests

Server/render tests:

- data attributes stable,
- article id/type/category present where expected.

JS tests/E2E:

- one click -> one event,
- no PII,
- no event on disabled/non-link element.

---

## 27. Filament/CMS tests

Minimum:

- resource visible to admin,
- unauthorized user denied,
- create draft,
- update draft,
- source persistence,
- relation persistence,
- publish blocked with missing requirements,
- schedule works,
- archive works.

---

## 28. E2E Golden Path A — editorial

~~~text
login admin
→ open Content Articles
→ create draft
→ type/category/author
→ title/lead/body
→ add source
→ link question
→ save
→ preview
→ submit review
→ publish
→ open public URL
→ verify article
~~~

---

## 29. E2E Golden Path B — scheduled

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

## 30. E2E Golden Path C — slug change

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

## 31. Responsive browser matrix

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

## 32. Accessibility QA

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

## 33. Performance QA

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

## 34. Security QA

- authz policies,
- preview signed/auth,
- XSS,
- upload validation,
- no SSRF source fetch,
- CSRF admin writes,
- rate limiting public dynamic endpoints if any,
- no draft leaks through API/search/sitemap/feed.

---

## 35. CI integration

Nie tworzymy osobnego CI tylko dla newsroomu, jeśli obecne pipeline’y mogą go objąć.

Wymagane:

- php tests,
- lint/format zgodnie z repo,
- frontend build,
- Playwright targeted/full according to CI strategy,
- PostgreSQL test path.

Nowe testy muszą wejść do istniejących jobs, nie być lokalną instrukcją bez CI.

---

## 36. Pre-merge checklist

- [ ] diff ograniczony do task scope
- [ ] tests added/updated
- [ ] targeted tests green
- [ ] full CI green
- [ ] docs match code
- [ ] no plan described as implemented
- [ ] migrations reviewed
- [ ] no secrets/assets accidentally committed

---

## 37. First production release prerequisites

- N0 done,
- N1–N5 required scope done,
- at least sample production-safe content,
- backups healthy,
- scheduler healthy,
- no unrelated failing CI,
- canonical APP_URL correct,
- Search/robots production config correct.

---

## 38. Backup before first newsroom migrations

Use existing ops process.

Recommended:

- verify latest backup freshness,
- create manual DB backup if release changes production schema,
- record manifest/location in release note.

Nie kopiować sekretów ani DB dump do repo.

---

## 39. Deployment sequence — first release

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
11. logs check
12. Search Console actions after stable production

---

## 40. Migration safety

Przed produkcją:

- migrations additive where possible,
- no destructive rename/drop without separate plan,
- indexes assessed for lock/time on current table sizes,
- FK behavior reviewed.

Newsroom v1 tworzy głównie nowe tabele, więc ryzyko dla istniejących danych powinno być małe.

---

## 41. Rollback layers

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

## 42. Emergency disable strategy

Rekomendowane rozwiązanie minimalne:

config/feature flag dla publicznego newsroomu może być rozważona przed release.

Jeśli nie ma globalnego feature flag system:

- route/hub może zostać tymczasowo wyłączony deployem,
- admin/data pozostają.

Nie dodawać rozbudowanego feature flag service tylko dla newsroomu, jeśli prosty config wystarczy.

---

## 43. Rollback of bad content

Nie wymaga deploy:

- archive,
- correction,
- remove breaking/featured.

To jest główny powód rozdzielenia content state od kodu.

---

## 44. Scheduler failure procedure

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

## 45. Sitemap failure procedure

Symptom:

/sitemaps/news.xml 500 lub invalid.

Actions:

1. remove/disable only broken sitemap endpoint from index if necessary,
2. public articles remain available,
3. fix generator,
4. validate XML,
5. restore index reference.

Sitemap failure nie powinien wyłączać publicznego newsroomu.

---

## 46. Feed failure procedure

Feed może być tymczasowo wyłączony bez wyłączania articles.

Nie pozwalamy, aby błąd feed serialization powodował 500 na article publish request.

---

## 47. Bad canonical procedure

High priority.

If production article canonical points to wrong host/path:

1. fix config/render ASAP,
2. invalidate cache,
3. verify sample URLs,
4. regenerate sitemap/feed if affected,
5. inspect Search Console later.

---

## 48. Draft leak incident

Jeśli draft stał się publiczny:

1. disable public access,
2. remove from hub/sitemap/feed,
3. inspect how leak occurred,
4. audit whether other drafts exposed,
5. fix policy/query,
6. add regression test.

---

## 49. XSS incident

1. disable affected article/public renderer if needed,
2. sanitize/remove payload,
3. audit all body records,
4. rotate/review admin sessions if compromise suspected,
5. patch sanitizer,
6. add payload regression test.

---

## 50. Production smoke checklist

### Core

- [ ] /
- [ ] /aktualnosci
- [ ] one category
- [ ] one news
- [ ] one guide
- [ ] /autorzy/{author}
- [ ] related question link
- [ ] related legal link

### SEO

- [ ] canonical
- [ ] robots
- [ ] JSON-LD
- [ ] OG image
- [ ] articles sitemap
- [ ] news sitemap
- [ ] feed

### Admin

- [ ] list
- [ ] edit
- [ ] preview
- [ ] save draft

---

## 51. Post-release 24h review

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

## 52. Post-release 7d review

Check:

- Search Console discovery/indexing,
- crawl errors,
- impressions/clicks initial,
- Core Web Vitals field data może jeszcze nie być kompletne,
- editorial friction,
- correction bugs,
- CMS usability.

---

## 53. Post-release 30d review

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

## 54. Release record

Każdy większy release newsroomu powinien zanotować:

- commit/PR,
- migrations,
- content count,
- sitemap/feed change,
- smoke result,
- rollback note.

Może być w PR/release notes; nie potrzebujemy nowej tabeli tylko do tego.

---

## 55. Definition of Done runbook

Runbook jest spełniony, gdy:

- wszystkie krytyczne flows mają test,
- CI obejmuje newsroom,
- first release ma backup/smoke/rollback plan,
- scheduler ma monitoring,
- content może być cofnięty bez deploy,
- draft/XSS/canonical incidents mają procedurę,
- test/release docs są aktualizowane po faktycznej zmianie pipeline.

---

## 56. Stan implementacji

Na 2026-09-15:

- istnieją globalne backend tests,
- istnieje Playwright smoke dla produktu,
- istnieją ops backup/restore/health commands,
- newsroom-specific tests i E2E jeszcze nie istnieją.

---

## 57. Pozostałe zadania

- [ ] dodać test files w trakcie N1–N5,
- [ ] podłączyć do CI,
- [ ] stworzyć newsroom E2E,
- [ ] stworzyć production smoke checklist w praktyce,
- [ ] po pierwszym release wpisać rzeczywiste wyniki i ewentualne różnice od planu.

---

## 58. Historia zmian

### 2026-09-15 — v0.1

- utworzono quality/release/rollback runbook,
- zdefiniowano warstwy testów, golden paths i responsive/security QA,
- zdefiniowano first production release sequence,
- zapisano bezpieczny rollback bez automatycznego cofania DB po powstaniu realnych treści,
- dodano procedury scheduler/sitemap/canonical/draft/XSS incidents.
