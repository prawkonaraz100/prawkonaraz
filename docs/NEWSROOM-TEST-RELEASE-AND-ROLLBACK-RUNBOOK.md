# Newsroom Test, Release and Rollback Runbook

## 1. Status

- Status: Canonical quality/release plan + live test state
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Backlog: [NEWSROOM-IMPLEMENTATION-BACKLOG.md](./NEWSROOM-IMPLEMENTATION-BACKLOG.md)
- Powiązane:
  - [TEST-STRATEGY.md](./TEST-STRATEGY.md)
  - [CI-CD.md](./CI-CD.md)
  - [RUNBOOK-OPS.md](./RUNBOOK-OPS.md)
  - [NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md](./NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md)
- Data: 2026-09-17
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

Stan po NEWSROOM-N1-001: kanoniczny `ci.yml` zachowuje szybki job `quality` na SQLite i ma addytywny job `newsroom-postgres` na PostgreSQL 16. Dla zmian newsroom DB/concurrency wymagane jest przejście obu właściwych gate'ów; samo SQLite `quality` nie zastępuje PostgreSQL evidence.

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

Stan po NEWSROOM-N6-003 — enterprise SEO production validation:

- istnieje osobny workflow `Newsroom Enterprise SEO Production Validation`,
- PR runs są REPORT-ONLY przeciw live production; manualny `workflow_dispatch` domyślnie STRICT,
- workflow reużywa istniejący `scripts/production-seo-delivery-smoke.sh`, więc robots/sitemap/static cache/Set-Cookie/validator/304 contract nie jest dublowany,
- nowy Node validator sprawdza homepage canonical + WebSite/Organization identity, Organization logo, newsroom hub indexing contract, sitemap index/children, feed/discovery oraz opcjonalne representative article/category/topic samples,
- finalny HEAD PR #125 `8a64f81c15fb6c52e2a90db26c5c65c9ad999801`: Enterprise SEO Production Validation #2 workflow success w REPORT-ONLY mode, Scheduled Publication Smoke #12 PASS, Browser Smoke #96 PASS, CI #472 PASS,
- merge `main@0344427cdf42804a037c688df54da6216f243ca6`: post-merge CI #473 PASS — 1139 passed / 20 219 assertions / 2 skipped, PostgreSQL PASS, frontend build PASS.

Live baseline z report-only evidence nadal ma failures i dlatego nie jest release PASS:

- robots cache nadal `max-age=14400`,
- live homepage nie ma jeszcze stable WebSite/Organization `@id` zgodnych z aktualnym `main`,
- live `/aktualnosci` nie spełnia ani publicznego newsroom contractu, ani repo placeholder `noindex` contractu,
- brak article/news sitemap coverage i feed pozostaje 404.

GSC 2026-09-19:

- homepage URL Inspection: `Duplicate, Google chose different canonical than user`,
- `/aktualnosci`: `URL is unknown to Google`,
- główny sitemap index jest submitted i ostatnio pobrany 2026-09-19 bez reported warnings/errors.

N6-003 pozostaje IN PROGRESS do aktualnego production deploy/runtime, zielonego STRICT workflow i reprezentatywnych article/category/topic samples.

Stan po PR #127 — repo-level SEO release guard:

- `deploy/mikrus/deploy.sh` ma opt-in `SEO_RELEASE=1`,
- w tym trybie po standardowym deployu wykonuje `seo:refresh-sitemaps`, `seo:audit-sitemaps`, `nginx -t` i istniejący publiczny `production-seo-delivery-smoke.sh`,
- `REQUIRE_NEWSROOM_PUBLIC=1` wymusza publiczny feed contract; domyślne `0` nadal pozwala na rollout-gated 404 feedu,
- guard nie aplikuje ani nie reloaduje aktywnego vhosta Nginx oraz nie włącza `NEWSROOM_PUBLIC_ENABLED`,
- finalny HEAD PR #127 `e84758d911ffc4f6ff10a04f88a2b8c48beb22c6`: exact-head CI #477 PASS — 1140 passed / 20 229 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS, frontend build PASS,
- merge `main@b26317bd979dd0c372b7a9341a8358f2599a9550`: post-merge CI #478 pełny PASS,
- świeży report-only Production SEO Delivery Smoke przy PR #127 ponownie potwierdził live `/robots.txt` `max-age=14400` i underlying exit `1`; to nadal nie jest production PASS.

Stan po PR #129 — canonical-host migration validation:

- istniejący N6-003 Node validator sprawdza na production originie `http://prawkonaraz.pl/`, oba warianty `www` i historyczny `https://prawkoapp.pl/`,
- każdy wariant musi redirectować przed podaniem treści i zakończyć się na `https://prawkonaraz.pl/` z HTTP 200,
- report-only Enterprise SEO Production Validation #3 potwierdził PASS dla wszystkich wariantów; `http://www.prawkonaraz.pl/` ma 2-hop chain jako warning, pozostałe sprawdzone warianty 1 hop,
- GSC URL Inspection: apex HTTP = `Page with redirect`, oba `www` = unknown to Google; canonical HTTPS homepage nadal ma `Duplicate, Google chose different canonical than user` i `https://prawkoapp.pl/` w referring URLs,
- nie zapisujemy host migration jako przyczyny Google-selected canonical mismatchu; wynik ją obecnie zawęża, ale nie rozstrzyga źródła mismatchu,
- finalny HEAD PR #129 `379f673e681c504322dbcabe370de110ce03dcc5`: exact-head CI #481 PASS — 1140 passed / 20 229 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS, frontend build PASS,
- merge `main@8ea54ba1a8b1ca5349a468353e519fa7262a16d4`: post-merge CI #482 pełny PASS.

Stan po NEWSROOM-N6-002 — scheduled publication release smoke:

- istnieje osobny workflow `Newsroom Scheduled Publication Smoke` uruchamiany dla zmian w scheduler/publishing/SEO-refresh contract oraz ręcznie przez `workflow_dispatch`,
- smoke używa realnego czasu; nie stosuje time travel ani bezpośredniego wymuszenia `published` w bazie,
- przed due wymaga 404 dla artykułu i braku wpisu w hub/feed/`articles.xml`/`news.xml`,
- przed due istniejące `newsroom:publish-due` musi zakończyć się `published=0 failed=0`,
- po due ta sama komenda musi opublikować dokładnie jeden fixture z audit trigger `scheduler`,
- po publikacji smoke wymaga dirty/version advance, następnie wykonuje istniejące `newsroom:refresh-seo-artifacts-if-dirty`,
- po refreshu artykuł ma być publiczny i obecny w feed/`articles.xml`/`news.xml`, a sitemap hashes i clean version mają się zmienić,
- HTTP path działa na stabilnym Docker app+Nginx originie z `APP_ENV=production`, canonical `APP_URL=https://prawkonaraz.pl` i wspólnym SQLite/storage,
- finalny HEAD PR #123 `2426f19215326a1c3386e8185222030b634ed795`: Scheduled Publication Smoke #10 PASS, Browser Smoke #94 PASS, CI #467 PASS,
- merge `main@afaa44dca0012144ab842c9195856eebec4553d3`: post-merge CI #468 PASS.

Boundary: ten smoke jest izolowanym production-mode release evidence w GitHub Actions. Nie stanowi live-production database mutation evidence ani nie zamyka osobnych N5-007 live Nginx/STRICT HTTP/Cloudflare/GSC gate'ów.

Stan po NEWSROOM-N6-001:

- istnieje dedykowany `scripts/e2e-newsroom-golden-path.mjs` uruchamiany jako osobny job `newsroom-golden-path` w istniejącym Browser Smoke workflow,
- admin path obejmuje realny login, create przez komponent Filament, ordered body/source/topic/question/hero state, private preview oraz workflow `draft -> in_review -> reviewed -> published`,
- public path obejmuje `/aktualnosci` hub -> article -> related public question -> product CTA,
- workflow transition jest uznawany na podstawie potwierdzonego stanu domenowego; toast jest tylko dodatkowym UI evidence,
- finalny HEAD PR #121 `76d0fa7d428b11f2db9d901eac698c7a169722ff` przeszedł Browser Smoke #84 i CI #454,
- merge `main@b0b29d9b4a3337f4e527334f6ead8b1be85303ad` przeszedł post-merge CI #455.

N6-001 nie zastępuje N6-002 scheduled-publication production smoke ani nadal otwartych produkcyjnych evidence N5-007.

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
- active-state timestamps set/cleared consistently with workflow status,
- scheduled scope,
- category relation,
- brak redundantnych article created_by/updated_by actor columns; actor history comes from AuditLog,
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
| archived | Republish | published | PASS only after current review/checklist; archived URL stays 200 until atomic transition |
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
- key_points jeśli ustawione: 2–5 plain-text items,
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

### 11.0. Aktualny stan po N1-003

Istnieją `tests/Feature/NewsroomSlugServiceTest.php` oraz `tests/Postgres/NewsroomPostgresSlugServiceTest.php`.

Pokrywają już service/domain layer:

- deterministic initial slug + suffix allocation,
- reserved newsroom slugs,
- duplicate current slug rejection,
- draft slug change bez redirectu,
- published slug history i one-hop rewrite,
- same-article historical path reclaim,
- historical full-path reservation przeciw innemu artykułowi,
- draft cross-family type change, published cross-family block i same-family published change,
- service-level route-family exclusivity przez `ContentArticlePathResolver`,
- compact AuditLog metadata,
- PostgreSQL advisory-lock contention na drugim połączeniu.

Finalny N1-003 PostgreSQL gate: 6 testów / 86 asercji PASS. Finalny ogólny gate: 906 passed / 18 565 assertions / 2 skipped; Pint 980 files i frontend build PASS.

N3-004 uruchomiło current-canonical public detail 200/404/410, a NEWSROOM-N3-006 dodało `tests/Feature/NewsroomArticleRedirectTest.php` i publiczny old path -> exactly one 301 do current canonical dla newsroom/guides. Invalid/non-301/stale redirect state failuje do 404, a query params nie są forwardowane. N5-001 domknęło sitemap-only-current-canonical dla standard article sitemap: current canonical może wejść do sitemap tylko przy spełnieniu eligibility, a canonical path kolidujący z historycznym `from_path` jest wykluczany.

- initial slug unique,
- duplicate current slug rejected/resolved zgodnie z service,
- canonical path colliding with another article historical `from_path` rejected,
- same-article historical path reclaim rewrites/removes conflicting redirect and leaves all old paths one-hop to current canonical,
- two concurrent mutations targeting same current/historical full path cannot both commit; PostgreSQL lock test,
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
- significant correction atomically commits correction_note + changed public content + last_substantive_update_at,
- failed correction validation leaves previous public content/note unchanged,
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

### 13.0. Aktualny stan po N0-004 / N2-003 / N3-005

Istnieje unit/security regression `tests/Unit/Support/NewsroomBodyContractTest.php` dla wykonywalnego `NewsroomBodyContract`. Pokrywa m.in. unknown/disabled block, schema version fail-closed, structured rich-text node/mark allowlist, unsafe URLs/targets, XSS escaping przez `RichContentRenderer`, block keys, image paths, relation IDs oraz table shape.

Warstwa N2 editor jest zmaterializowana i ma feature regression dla Builder/RichEditor persistence, reorder, canonical key round-trip oraz unsafe payload rejection. Po N3-004 istnieje publiczny `NewsroomArticleBodyRenderer` i `NewsroomPublicArticlePageTest`, a admin preview reużywa ten sam renderer z `includeDeferredBlocks: true`.

Po N3-005 publiczny renderer obsługuje `rich_text`, `image`, `quote`, `table`, `context`, public-safe `related_article` oraz `legal_reference`, `question_group`, `traffic_sign_group` i `product_cta` przygotowane przez `NewsroomArticleProductBridgeService`. Dla question/legal/sign sam identyfikator w body nie wystarcza: target musi być jawnie powiązany przez article-owned pivot i spełnić istniejący public eligibility contract. `embed` pozostaje feature-disabled/fail-closed zgodnie z N0-004.

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
- embed disabled by default unless provider allowlist + sandbox/referrerpolicy + production CSP/frame-src gate passes,
- when enabled, allowlisted embed only and unknown provider rejected.

Format-evolution tests:

- `body_schema_version` jest wymagany/obsługiwany zgodnie z kontraktem,
- zapisany payload starszej wspieranej wersji nadal się renderuje,
- nowy block type nie może być tworzony przez editor przed dostępnością renderera,
- unknown future block failuje bezpiecznie, bez wykonywania HTML,
- breaking schema migration ma jawny migrator/test; revision-history snapshot nie jest do tego wymagany.

### 13.1. Newsroom media storage foundation tests

Po N0-006 istnieje `NewsroomMediaStorageTest` i pokrywa:

- dedykowany newsroom managed namespace zamiast question-specific uploadera,
- unique/immutable ULID source paths oraz nowy path przy kolejnym prepare,
- JPEG/PNG/WebP/AVIF allowlist z odrzuceniem SVG,
- actual stored bytes policy,
- actual decoded MIME i odrzucenie declared MIME mismatch,
- actual width/height oraz max-dimension policy,
- non-raster payload podszywający się pod obraz,
- path traversal/absolute URL/manual unmanaged path rejection,
- public URL przez współdzielony `MediaUrlResolver` bez signed-expiry semantics.

N2-010 materializuje newsroom article media persistence i focal-point CMS; N3-004 wykorzystuje zapisane dimensions oraz focal `object-position` w publicznym renderze. Fizyczne crop variants nie są deklarowane jako istniejące.

---

## 14. Public article HTTP tests

### 14.0. Aktualny stan po N3-006

`tests/Feature/NewsroomPublicArticlePageTest.php`, `tests/Feature/NewsroomPublicCatalogServiceTest.php` i `tests/Feature/Public/NewsroomRouteContractTest.php` pokrywają current-canonical article detail HTTP/render boundary. Publicznie widoczny rekord zwraca 200 w poprawnej route family, hidden/not-found 404, a historycznie publiczny withdrawn rekord neutralną 410 surface bez body/source/product leakage. 404/410 mają `noindex,follow` także w `X-Robots-Tag`.

N3-006 wdraża historyczne old-slug 301 przez istniejący `ContentArticlePathResolver`; redirect jest konsultowany po current-canonical `not_found`, wymaga persisted 301 bezpośrednio do bieżącego canonical i failuje zamknięcie do 404 przy stale/malformed state.

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
- archive itself never implies 301/404/410,
- archived review/correction flow never causes temporary 404,
- Republish clears archived_at atomically and returns article to active distribution.

Unknown slug:

- 404.

Old slug:

- 301 canonical — **wdrożone w N3-006**.

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

N3-004 podłącza istniejący `ContentArticleSeoService` do publicznego `newsroom.article`; od NEWSROOM-N5-003 wspólny public-content layout emituje Atom discovery dla crawlable newsroom/guide surfaces przy włączonym gate.

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

N3-003 pokrywa service-level graph; N3-004 dodatkowo sprawdza JSON-LD emission w publicznym article response/browser QA.

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
- public citation with URL renders one crawlable `<a href>` to the validated source URL; URL/title are escaped and no `javascript:`/invalid scheme can survive validation/rendering,
- public citation with null URL renders as text without empty/broken anchor,
- official/public source links are **not** given `nofollow`, `ugc` or `sponsored` by default; takie rel pojawia się tylko przy jawnej, uzasadnionej policy,
- jeśli renderer używa `target="_blank"`, link ma co najmniej `rel="noopener noreferrer"`; brak target=_blank nie wymaga sztucznego noopener,
- `is_publicly_cited=false` source title/publisher/url/note never leaks — także przez JSON-LD, serialized props, HTML comments ani analytics payload,
- internal editorial/source note not rendered,
- private metadata such as `image_license_note`/internal source evidence not leaked,
- regression sprawdza kolejność/publiczną etykietę sources oraz brak przypadkowego `nofollow` na oficjalnym źródle.

---

## 19. Product bridge tests

NEWSROOM-N3-005 jest zmaterializowane przez `tests/Feature/NewsroomProductBridgeTest.php`. Regression sprawdza:

- linked public question pojawia się z canonical URL z istniejącego `PublicQuestionCatalogService`,
- inactive/nonpublic question oraz question wskazane tylko w body bez article-owned pivotu są pomijane,
- legal reference pojawia się wyłącznie dla jawnie powiązanej verified jednostki/verified aktu z published page pod published topic,
- draft/unlinked legal target jest pomijany,
- pivot/internal notes i pełny `official_excerpt` nie wyciekają do publicznego HTML,
- traffic sign musi być jawnie powiązany z artykułem i spełniać published sign + published author + published category contract,
- traffic sign pivot `relation_type=related` jest fail-closed; publiczny bridge dopuszcza `direct` i `example`,
- niepowiązany/ukryty sign jest pomijany,
- contextual CTA mapuje wyłącznie istniejące canonical destinations: `public.tests`, `public.questions.hub`, `session.index`.

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
- per-article audit zwraca `has_crawlable_inbound`, jawne inbound sources i `estimated_hub_depth` dla fixtures; site-wide orphan/click-depth scanner/komenda pozostaje osobnym możliwym zakresem N5/N6.

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
- needs_review+indexable pozostaje w oznaczonej sekcji „w trakcie weryfikacji”, a detail page pokazuje review banner,
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
- meaningful article lastmod = max(first_published_at, last_substantive_update_at, public_state_changed_at),
- home/category/topic/guides hub lastmod changes only when visible public output/corpus/composition meaningfully changes, never just generator run time,
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

**Stan implementacji:** N5-003 jest pokryte przez `tests/Feature/Public/NewsroomFeedTest.php`, `NewsroomPublicGateTest` i `NewsroomRouteContractTest`; exact-head CI #380 oraz post-merge CI #381 są PASS. Poniższy kontrakt pozostaje regresyjnym wymaganiem, a production delivery smoke jest osobnym gate'em.

- XML valid,
- content type,
- RSS GUID / Atom id = dokładnie `urn:prawkonaraz:content-article:{id}` (gdzie `{id}` = `content_articles.id`) i nie zależy od sluga/canonical/timestampów,
- slug change zmienia item link na nowy canonical, ale GUID/id pozostaje identyczny i czytnik nie widzi „nowego” wpisu,
- pub date = `first_published_at`,
- updated date = `last_substantive_update_at ?? first_published_at`,
- latest order,
- draft/needs_review/archived excluded zgodnie z `activelyDistributed()`,
- absolute canonical item URLs,
- HTML head discovery link points to correct feed,
- cache invalidated after publish/substantive update.


### 25.1. IndexNow integration tests

- first publish commituje publiczny canonical `200` zanim pojawi się queue row z lokalnym `EVENT_CREATED`,
- substantive public update / republish enqueue'uje canonical po commit jako lokalny `EVENT_UPDATED`,
- rollback publish/update/withdraw/slug-change nie tworzy ani nie mutuje newsroom submission row,
- withdrawn: przed enqueue URL już zwraca `410`, a queue row używa lokalnego `EVENT_DELETED`,
- restore withdrawn -> review pozostawia `410` i nie enqueue'uje; dopiero skuteczny republish po przywróceniu `200` zgłasza URL,
- slug change: old URL zwraca `301` do new, new zwraca `200` + self-canonical; after commit oba URL-e są enqueue'owane, old z lokalnym `EVENT_UPDATED` i **nigdy** `EVENT_DELETED`,
- archive utrzymujący detail `200` nie jest delete; brak enqueue, jeśli zmieniła się wyłącznie dystrybucja, albo `EVENT_UPDATED` jeśli publiczny detail/robots faktycznie się zmienił,
- draft/in_review/preview/scheduled-before-time/noindex oraz `NEWSROOM_PUBLIC_ENABLED=false` nie trafiają do newsroom automation/collector,
- HTTP submission payload nadal zawiera tylko `host`, `key`, opcjonalne `keyLocation` i `urlList`; lokalny `event_type` nie jest serializowany jako protocol verb,
- istniejące canonical-host/HTTPS/query/private-path filters, dedupe po URL hash, debounce/retry i 10k batching pozostają bez regresji,
- IndexNow 200/202 oznacza accepted request; test nie interpretuje tego jako dowodu indeksacji,
- failure/retry IndexNow nie cofa ani nie blokuje zakończonej transakcji publikacyjnej.

**Stan po NEWSROOM-N5-005:** kontrakt jest wdrożony w `tests/Feature/Public/NewsroomIndexNowAutomationTest.php` oraz rozszerzonym `IndexNowQueueAutomationTest`. Exact-head CI #392 na `44c8099ac2ea020bf5d9e31cfe547af8cb2149f7` zakończył 1116 passed / 20 095 assertions / 2 skipped, Pint PASS, frontend build PASS i PostgreSQL 7/94. Po merge PR #105 post-merge CI #393 na `main@1cc9f4bec8c7d7fc105fd3495664434cf4323eea` powtórzył ten sam backend/PostgreSQL wynik oraz Pint/build PASS. Browser Smoke nie został uruchomiony dla tego backendowego zakresu przez istniejące path filters i nie jest przedstawiany jako dowód N5-005.

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

Topology:

- test/deploy evidence określa single-node vs multi-node,
- single-node: wszystkie requesty widzą ten sam atomowo przełączany set,
- multi-node: smoke z każdego origin/node albo warstwy wspólnej potwierdza identyczny sitemap artifact generation/version,
- Redis lock/onOneServer bez synchronizacji plików nie zalicza topology gate.

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

Po N3-005 dedykowany automatyczny article Browser QA obejmuje dokładnie tę macierz i dodatkowo sprawdza publiczny Product Bridge/CTA. Hub/category/topic pozostają downstream, a pełny accessibility gate nadal nie jest zastąpiony przez ten smoke.

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

Nie uznajemy samego Lighthouse score za pełny accessibility test. PASS N3-005 Browser QA nie oznacza zamknięcia pełnego accessibility gate.

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

Zachowujemy istniejący szybki SQLite CI i addytywny PostgreSQL gate.

Wymagane:

- istniejący php tests / smoke / Pint / frontend build bez regresji,
- addytywny `newsroom-postgres` job od PR C dla migracji, constraints, transactional/concurrency paths,
- Filament/feature tests w automatycznym CI,
- dedykowane browser E2E dla publicznych powierzchni, gdy dany renderer jest zmaterializowany.

Po N3-005 `.github/workflows/browser-smoke.yml` ma osobny job `newsroom-article`, uruchamiany dla relevant PR paths oraz manualnie. Genericzny historyczny `browser-smoke` pozostaje `workflow_dispatch` i nie jest dowodem newsroom QA.

Dedykowany harness `npm run e2e:newsroom-article`:

- buduje frontend assets,
- tworzy deterministyczny SQLite fixture,
- renderuje article route przez Laravel HTTP kernel do SSR snapshotu,
- uruchamia Chromium z JavaScript disabled i service workers blocked,
- sprawdza H1, breadcrumbs, body/source, canonical, JSON-LD oraz publiczny Product Bridge/CTA,
- weryfikuje, że realny zbudowany stylesheet jest zastosowany,
- sprawdza brak horizontal overflow na 360/390/430/768/1024/1440,
- zapisuje screenshot/report artifact.

External Google Fonts są w harnessie deterministycznie stubowane, ale realny zbudowany `pwa-*.css` aplikacji nadal jest ładowany i walidowany; rozwiązanie nie maskuje produkcyjnych reguł layoutu.

Browser Smoke #20 na finalnym N3-005 head `7d795b895865cda49ba94a4fec50533d7b0f7a97` zakończył się PASS. Artifact `newsroom-article-browser-qa` ma ID `10508254861`, rozmiar 1 124 509 bytes i SHA256 `753b717cb5f0319e2e7799552b181fd972b41cd3b3a66bba7dd9a82d7ce96a2a`.

Po NEWSROOM-N5-004 ten sam `newsroom-article` harness zachowuje dotychczasowy JS-disabled responsive pass, a następnie wykonuje osobny JS-enabled analytics pass z lokalnym `gtag` stubem. Regression potwierdza delayed `prawkonaraz:analytics-ready` -> dokładnie jeden `newsroom_article_view`, dokładnie jeden event na kliknięcie Product Bridge/source/related link, generic `newsroom_module_click` ze stabilnym kontekstem oraz brak eventu dla non-link i `aria-disabled`. Browser Smoke #55 zakończył PASS dla `newsroom-article`, `newsroom-home`, `newsroom-category`, `newsroom-guides`, `newsroom-topic` i `newsroom-semantic-links`.

Po NEWSROOM-N4-002 workflow ma również osobny automatyczny job `newsroom-home` oraz komendę `npm run e2e:newsroom-home`. Harness buduje assets, seeduje deterministyczny hub corpus, renderuje `/aktualnosci` przez Laravel kernel i uruchamia Chromium z JavaScript disabled. Sprawdza H1, newsroom subnavigation, lead/latest/category/guides/Product Bridge, self-canonical, robots `index,follow,max-image-preview:large`, realny built CSS oraz brak horizontal overflow na 360/390/430/768/1024/1280/1440. Browser Smoke #27 na finalnym N4-002 head `1036b67aa44b56fffb0bc1e9083d3a0d1d3963d0` zakończył PASS dla obu relewantnych jobów `newsroom-home` i `newsroom-article`; genericzny `browser-smoke` pozostał prawidłowo skipped na evencie pull_request, ponieważ jest manual-only.

---

### 37.1. Public gate integration tests

NEWSROOM-N3-008 jest wdrożone i ma rzeczywisty regression `tests/Feature/NewsroomPublicGateTest.php`.

Przy pre-launch `NEWSROOM_PUBLIC_ENABLED=false` test potwierdza:

- news i guide detail failują do 404 bez ujawnienia treści,
- historyczny old-path nie wykonuje 301 do dark-deployed canonical,
- `/aktualnosci` i `/poradniki` zachowują istniejący placeholder 200 + `X-Robots-Tag: noindex, follow`,
- category/topic routes failują do 404 przy gate=false; N5-003 feed również zwraca wtedy 404 i discovery jest suppressowane, natomiast przy gate=true feed zwraca publiczny Atom 1.0 response,
- author page nie pokazuje newsroom publications,
- newsroom-only author nie jest kwalifikowany przez author sitemap, a newsroomowy `public_state_changed_at` nie wnosi freshness contribution przy wyłączonym gate,
- obecny `IndexNowUrlCollector` nie przepuszcza namespace `/aktualnosci` ani `/poradniki`,
- authenticated admin private preview nadal działa.

Przy `NEWSROOM_PUBLIC_ENABLED=true` regression potwierdza obecnie istniejące powierzchnie: public article detail, publiczny Hub Blade `/aktualnosci`, category pages, `/poradniki` hub, topic dossier, author publication, author sitemap eligibility, historyczny redirect, N4-008 semantic/reverse-link modules, N5-001 standard article sitemap/hub coverage, N5-002 Google News Sitemap oraz N5-003 Atom feed/discovery. Gate=false feed 404 i brak discovery są również pokryte.

Existing public-article PHPUnit baseline i dedykowany Browser Smoke `newsroom-article` jawnie ustawiają gate na `true`, dzięki czemu bezpieczny produkcyjny default `false` nie maskuje regresji publicznego renderer'a.

---

### 37.2. Repository gate status

Aktualny `main` nie ma branch protection/required checks. Przed publicznym rolloutem:

- branch protection/ruleset aktywne,
- normalny direct push zablokowany,
- stabilny CI quality check required,
- newsroom-postgres objęty required/aggregate check po jego dodaniu,
- wyjątki administracyjne, jeśli istnieją, są świadome i audytowalne.

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

Prosty config gate `NEWSROOM_PUBLIC_ENABLED` jest wdrożony przez NEWSROOM-N3-008 jako wymagany element v1 dark deploy.

- `config/newsroom.php` czyta env z bezpiecznym defaultem `false`,
- przy `false` blokuje obecnie istniejące public article/guide detail i historyczne redirecty bez usuwania admin/data/private preview,
- przy `false` wyłącza newsroom contribution w author profile/author sitemap oraz filtruje newsroom namespace z obecnego IndexNow collectora,
- N4-008 reverse-link implementation respektuje ten sam gate; przyszłe feed/article-sitemap/news-sitemap implementacje również muszą go respektować,
- podczas cutover zmiana env wymaga odświeżenia config cache zgodnie z Phase B,
- po launch emergency procedure nadal rozróżnia technical 503/code rollback od content withdrawn; nie używamy długiego `false` do masowego 404 już indeksowanych URL-i.

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
- [ ] production sample News Sitemap ma wymagane metadata/eligibility zgodne z wdrożonym N5-002
- [ ] fresh dirty-marker/scheduled refresh dla News Sitemap
- [x] child-before-index atomic publication — repository-level regression potwierdzone w PR #109; production static delivery nadal wymaga osobnego smoke
- [ ] production sample Atom feed + head discovery na publicznym środowisku
- [ ] sitemap static delivery headers/304 na faktycznej warstwie
- [ ] brak regresji istniejących question/sign/legal/author sitemap

### Admin

- [ ] admin allowed / non-admin denied
- [ ] list
- [ ] edit
- [ ] stale edit rejected
- [x] article preview private,no-store
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
- semantic silo/reverse-link rules i per-article inbound/depth audit data mają regression coverage bez modyfikacji question graphu; site-wide orphan scanner pozostaje osobnym możliwym hardeningiem,
- static sitemap atomic publication/dirty-marker refresh/news metadata ma regression coverage bez wymogu queue workera,
- rzeczywista warstwa static delivery/robots ma production smoke,
- content może być cofnięty bez deploy,
- draft/XSS/canonical incidents mają procedurę,
- test/release docs są aktualizowane po faktycznej zmianie pipeline.

---

## 58. Stan implementacji

Na 2026-09-18 po NEWSROOM-N4-008, zweryfikowanym na `main@3d7ac8ab8a3ed1c299cb0cdd4cb1ef8ac6b53f78`:

- istnieją globalne backend tests, ops backup/restore/health commands i kanoniczny CI z `quality` na SQLite oraz addytywnym `newsroom-postgres` na PostgreSQL 16,
- istnieją newsroom-specific unit/security i feature regression dla body contract/editor, media storage/article media, enum/schema/model, slug/history, publishing workflow, home composition/placements, article preview, topic/CMS oraz N3-001 catalog, N3-002 SEO, N3-003 schema service, N3-005 Product Bridge, N3-006 redirect, N3-007 author profile, N3-008 public gate, N4-001 home read model, N4-002 public home renderer, N4-003 category pages i N4-004 guides hub,
- N3-004 dostarcza `NewsroomArticleBodyRenderer`, `NewsroomArticlePresentationService`, publiczny `ContentArticleController`, Blade `newsroom.article`/`article-unavailable` i feature tests publicznego article response,
- N3-005 dodaje `NewsroomArticleProductBridgeService`, `newsroom.product-bridge-block` i `NewsroomProductBridgeTest`; publiczny article renderer obsługuje teraz questions/legal/signs/contextual CTA bez losowych relacji i bez draft targetów,
- current-canonical public detail ma automatyczne 200/404/410 coverage; 410/404 nie renderują treści i są noindex, a `NewsroomArticleRedirectTest` pokrywa old-slug one-hop 301/fail-closed behavior N3-006,
- `ContentAuthorProfileTest` pokrywa N3-007 lifecycle profilu autora, wspólną Person/Organization identity, author-sitemap freshness z `public_state_changed_at` oraz blokadę odpublikowania autora z zależnym indexable article i odblokowanie po noindex,
- `NewsroomHomeCompositionServiceTest` nadal chroni fixed placements, manual-over-fallback, future preview, global card dedupe, category context, short modules i breaking exception; N4-001 rozszerza ten sam composer zamiast tworzyć drugi resolver,
- nowy `tests/Feature/NewsroomHomeReadModelServiceTest.php` potwierdza `NewsroomPublicGate` dla publicznego read modelu, scalar/JSON-serializable projection z canonical/category/author/hero metadata oraz stały query budget przy wzroście liczby aktywnych kategorii z 1 do 8,
- Product Bridge wymaga body target + article-owned pivot + istniejący public eligibility; question group ma limit 5, legal wymaga verified act/unit + published legal page/topic, a signs wymagają published sign/author/category i pivot `direct` albo `example`; luźny `related` jest fail-closed,
- publiczny response emituje SEO metadata i JSON-LD z istniejących N3-002/N3-003 services, route-family breadcrumbs, hero/provenance/regulatory context, public sources, correction, author box, public-safe related article oraz Product Bridge,
- dedicated `.github/workflows/browser-smoke.yml` job `newsroom-article` istnieje; Browser Smoke #23 na finalnym N3-008 head `c8484aa1529eb41805a76ceb7be1f55db63aec14` zakończył się PASS po jawnym ustawieniu `NEWSROOM_PUBLIC_ENABLED=true`,
- harness działa z JS disabled, sprawdza realny built CSS, H1/breadcrumb/body/source/canonical/JSON-LD/Product Bridge/CTA oraz horizontal overflow i zapisuje artifact,
- N4-002 dodaje `NewsroomHomePageTest` dla gate=false/gate=true/empty-section behavior oraz aktualizuje `NewsroomPublicGateTest` i `Public/NewsroomRouteContractTest` do nowego enabled-state huba,
- dedykowany Browser Smoke #27 na finalnym N4-002 head `1036b67a...` zakończył PASS dla `newsroom-home` i `newsroom-article`; hub harness działa z JS disabled na 360/390/430/768/1024/1280/1440 i sprawdza realny built CSS, canonical/robots, CTA oraz horizontal overflow,
- exact-head CI #321 dla PR #83 zakończył PASS, a post-merge CI #322 na `main@04a3e961a40207bd65c41b684a5bf1cd8d2c5e10` zakończył się pełnym PASS: `quality` 1062 passed / 19 638 assertions / 2 skipped, Pint 1061 files PASS, frontend build 7.59 s; `newsroom-postgres` 7 passed / 94 assertions,
- N4-003 dodaje `NewsroomCategoryPageTest` dla gate, active/inactive/unknown/out-of-range, eligibility, deterministic order, pagination, empty-state/noindex i schema; istniejące `NewsroomPublicGateTest`, `Public/NewsroomRouteContractTest` i `NewsroomHomePageTest` zostały zsynchronizowane z nowym category surface,
- workflow `Browser Smoke` ma osobny automatyczny job `newsroom-category`; Browser Smoke #29 na finalnym N4-003 head `8e4f707060fbaa348e9392f0b19beb7b4517beca` zakończył PASS dla `newsroom-category`, `newsroom-home` i `newsroom-article`; category harness działa z JS disabled na 360/390/430/768/1024/1280/1440 i sprawdza page 2/canonical,
- exact-head CI #326 zakończył PASS, a post-merge CI #327 na `main@84bcb2aeff57a1374def7af411c39db07a8fb38d` zakończył pełny gate: `quality` 1067 passed / 19 684 assertions / 2 skipped, Pint 1065 files PASS, frontend build 9.40 s; `newsroom-postgres` 7 passed / 94 assertions,
- N4-004 dodaje `NewsroomGuidesHubTest` dla gate, guide-only eligibility, deterministic order, empty-state/noindex, pagination, canonical i schema; `NewsroomPublicGateTest` i `NewsroomHomePageTest` zostały rozszerzone o publiczny guide hub i crawlable wejście z `/aktualnosci`,
- workflow `Browser Smoke` ma osobny automatyczny job `newsroom-guides`; Browser Smoke #31 na finalnym N4-004 head `119cbd94d1fb6ff6f9f2025e726242190927266d` zakończył PASS dla `newsroom-guides`, `newsroom-category`, `newsroom-home` i `newsroom-article`; guides harness działa z JS disabled na 360/390/430/768/1024/1280/1440 i sprawdza page 2/canonical,
- exact-head CI #331 zakończył PASS, a post-merge CI #332 na `main@2bb22142b1e9bec803f9c3889c11000194f46783` zakończył pełny gate: `quality` 1072 passed / 19 731 assertions / 2 skipped, Pint 1068 files PASS, frontend build 10.14 s; `newsroom-postgres` 7 passed / 94 assertions,
- N4-005 dodaje `Public/NewsroomNavigationIntegrationTest.php` dla canonical primary links, duplicate-free `PublicFooter::service_links`, wspólnego Vue/Blade footer source i istniejących document-navigation prefixes,
- `e2e-newsroom-guides.mjs` od N4-005 sprawdza dokładnie po jednym footer linku `Aktualności` i `Poradniki`; Browser Smoke #32 na finalnym N4-005 head `189219b3586d2df8e4ea73045318fd68f38f0fa1` zakończył PASS dla `newsroom-guides`, `newsroom-category`, `newsroom-home` i `newsroom-article`,
- exact-head CI #335 zakończył PASS, a post-merge CI #336 na `main@a9fb9ccfed058de88efdb6e0833b67911aeb09aa` zakończył pełny gate: `quality` 1075 passed / 19 745 assertions / 2 skipped, Pint 1069 files PASS, frontend build 7.32 s; `newsroom-postgres` 7 passed / 94 assertions,
- N4-006 dodaje `NewsroomPublicReadCacheTest` dla obserwowalnego snapshot reuse i invalidation po publish, placement change, archive, category metadata oraz outer-transaction rollback/commit boundary,
- feature-test base czyści trwały `CACHE_STORE=file` pomiędzy testami z osobnymi SQLite databases, więc cache nie może przenosić snapshotu między izolowanymi test cases,
- exact-head CI #342 zakończył PASS na `093ca709d3155d5fa3f13bae224312fd286077dc`, Browser Smoke #36 zakończył PASS dla `newsroom-guides`, `newsroom-category`, `newsroom-home` i `newsroom-article`, a post-merge CI #343 na `main@5f1880e03469fc6e340a86fdb1b4246c7afd116b` zakończył pełny gate: `quality` 1081 passed / 19 770 assertions / 2 skipped, Pint 1077 files PASS, frontend build 9.44 s; `newsroom-postgres` 7 passed / 94 assertions,
- N4-007 dodaje `NewsroomTopicPageTest`, rozszerza public-gate/route contract regressions oraz dedykowany `e2e-newsroom-topic.mjs`; coverage obejmuje gate, published/draft/future/archived HTTP, eligible mixed-family corpus, featured dedupe, późniejszy below-baseline corpus, pagination/canonical i CollectionPage schema,
- workflow `Browser Smoke` ma osobny automatyczny job `newsroom-topic`; Browser Smoke #39 na finalnym N4-007 head `812313afc38e30e53c59901d46c2d24188e3f6fa` zakończył PASS dla `newsroom-topic`, `newsroom-guides`, `newsroom-category`, `newsroom-home` i `newsroom-article`; topic harness działa z JS disabled na 360/390/430/768/1024/1280/1440 i sprawdza page 2/canonical oraz horizontal overflow,
- exact-head CI #348 zakończył pełny PASS, a post-merge CI #349 na `main@a97373003c5a249a28761df15342f19e656c50c5` zakończył pełny gate: `quality` 1087 passed / 19 827 assertions / 2 skipped, Pint 1081 files PASS, frontend build 9.48 s; `newsroom-postgres` 7 passed / 94 assertions,
- N4-008 dodaje `NewsroomSemanticLinkIntegrationTest`: deterministic related resolver, bounded reverse links, exclusion niekwalifikowanych targetów, explicit topic/category links, brak mutacji question graphu, legal/sign explicit-pivot behavior i gate=false suppression,
- `NewsroomPublicArticlePageTest` został zsynchronizowany z nowym kontraktem guide detail: primary category jest crawlable classification linkiem bez zmiany guide breadcrumb family,
- workflow `Browser Smoke` ma osobny job `newsroom-semantic-links`; Browser Smoke #48 na finalnym N4-008 head `bd63773bc1febdcc6aa8c2507c6621e908d63b09` zakończył PASS dla semantic-links, article, home, category, guides i topic,
- exact-head CI #361 zakończył pełny PASS, a post-merge CI #362 na `main@3d7ac8ab8a3ed1c299cb0cdd4cb1ef8ac6b53f78` zakończył: `quality` 1093 passed / 19 881 assertions / 2 skipped, Pint PASS, frontend build 7.42 s; `newsroom-postgres` 7 passed / 94 assertions,
- publiczny Hub Blade, category pages, guide hub, topic dossier, navigation integration, home/category cache, N4-008 semantic/reverse links, N5-001 standard article sitemap/hub coverage, N5-002 Google News Sitemap, N5-003 Atom feed/discovery, N5-004 analytics hooks, N5-005 article-specific IndexNow automation oraz N5-006 sitemap/link audit hardening są zamknięte implementacyjnie,
- pierwszy podkrok NEWSROOM-N5-007 jest potwierdzony po PR #109: complete-set XML pre-validation, child-before-index atomic publication oraz post-switch cleanup zarządzanych article/news sitemap files; exact-head CI #403 i post-merge CI #404 zakończyły pełny PASS,
- drugi podkrok NEWSROOM-N5-007 jest potwierdzony po PR #112: cache-backed version/clean-version coordinator, shared lock, clean-state skip, failure/race retention, after-commit dirty events/observers oraz every-minute scheduler z `onOneServer()` + `withoutOverlapping()`; exact-head CI #409 i post-merge CI #410 zakończyły pełny PASS,
- aktualne canonical deployment docs potwierdzają single-node Mikrus 4.1 z lokalnym `public/`, lokalnym Redisem i jednym cronem `schedule:run`, więc topology gate jest spełniony dla obecnego contractu,
- NEWSROOM-N5-007 pozostaje IN PROGRESS: PR #115 dodał crawler-safe Nginx contract, `production-seo-delivery-smoke.sh` i repo-level Nginx regression; PR #117 dodał GitHub Actions smoke harness; PR #119 potwierdził dedykowany scheduler-definition/shared-lock regression. Report-only public run wykazał `Cache-Control: max-age=14400` dla `/robots.txt` zamiast oczekiwanego `public, max-age=3600`, więc faktyczny STRICT production PASS oraz HTTP/Cloudflare/GSC evidence nadal nie są potwierdzone. Produkcyjny rollout fazy 2 IndexNow/Bing verification pozostaje osobnym otwartym evidence.

---

## 59. Pozostałe zadania

- [ ] dodać dalsze test files w trakcie N5; N4-001 dodało `NewsroomHomeReadModelServiceTest`, N4-002 `NewsroomHomePageTest` i `e2e-newsroom-home.mjs`, N4-003 `NewsroomCategoryPageTest` i `e2e-newsroom-category.mjs`, N4-004 `NewsroomGuidesHubTest` i `e2e-newsroom-guides.mjs`, N4-005 `Public/NewsroomNavigationIntegrationTest.php`, N4-006 `NewsroomPublicReadCacheTest`, N4-007 `NewsroomTopicPageTest`/`e2e-newsroom-topic.mjs`, a N4-008 `NewsroomSemanticLinkIntegrationTest`/`e2e-newsroom-semantic-links.mjs`,
- [x] dodać dedykowany browser E2E dla publicznego article detail N3-004,
- [x] dodać N2 media/topic/focal-point integration i N3-004 renderer/browser regression; pełne crop-variant generation nadal nie istnieje,
- [x] dodać service-level site-identity/entity-graph/date-consistency regression oraz publiczne HTML/JSON-LD emission dla article detail,
- [x] dodać N3-005 Product Bridge tests dla questions/legal/signs/contextual CTA,
- [x] dodać N4-008 semantic silo/reverse-link/per-article inbound-depth regression bez mutacji question graphu,
- [x] dodać N5-006 site-wide `newsroom:audit-links` regression w `NewsroomLinkAuditTest`: healthy pass, orphan/source/breaking/non-public topic/sign/broken featured relation oraz public-gate suppression; excessive click-depth threshold/duplicate-module URL/redirect-chain crawler nie są fałszywie zaliczone,
- [x] dodać old-slug 301 HTTP integration w N3-006; `NewsroomArticleRedirectTest` pokrywa newsroom/guides one-hop redirect, current canonical 200 oraz invalid redirect fail-closed 404,
- [x] dodać N3-007 `ContentAuthorProfileTest` dla author-profile lifecycle, shared schema identity, public-state sitemap freshness i unpublish guard,
- [x] dodać N2 stale-write/Apply-public-update oraz HomeComposer stale-write regression; ContentArticle i placement same-second conflicts są blokowane,
- [x] dodać N5-001 standard article sitemap regression w istniejącym `SeoSitemapGenerationTest`: single shard, boundary crossing, stable fixed-ID-range assignment, no duplicate URL across shards, public-gate suppression, eligibility/current-canonical oraz URL-count/byte-size guards,
- [x] dodać N5-002 News Sitemap regression w istniejącym `SeoSitemapGenerationTest`: required namespace/tags, visible title/canonical identity, `first_published_at` 2-day eligibility, old-updated exclusion, public-gate suppression, non-news/status/noindex/category/author/redirect-source exclusions i realny 1000→1001 boundary,
- [x] dodać child-before-index atomic publication + obsolete-shard cleanup tests — `SeoSitemapGenerationTest` potwierdza root-index-last, fail-before-switch dla invalid XML i cleanup zarządzanych article/news files przy zachowaniu unrelated XML,
- [x] dodać dirty-marker refresh tests — `NewsroomSeoArtifactRefreshCoordinatorTest` pokrywa version/clean-version, after-commit lifecycle dirty events, clean-state skip, success, audit failure i version race,
- [x] dodać N5-003 feed/discovery regression: Atom metadata/corpus/limit, stable id + slug change, workflow invalidation, ETag/Last-Modified/304, no Set-Cookie, gate/discovery,
- [x] dodać N5-004 analytics regression: render/privacy contract, GA readiness signal oraz JS-enabled Browser Smoke dla exactly-once article view, one-click-one-event, module click i disabled/non-link suppression,
- [x] dodać N5-005 IndexNow lifecycle regression: publish/republish/update/withdraw/restore/slug, scheduled/noindex/gate suppression, outer rollback, queue failure isolation i brak lokalnego `event_type` w HTTP payload,
- [x] dodać dedykowany regression scheduler definition / lock contention dla N5-007 — PR #119 rozszerza istniejący `NewsroomSeoArtifactRefreshCoordinatorTest` o runtime schedule contract (`everyMinute`, production-only, `onOneServer()`, `withoutOverlapping()`) oraz shared-lock contention z brakiem generator/auditor work i zachowaniem dirty state,
- [x] dodać production-like static robots/sitemap/feed delivery smoke — PR #115 dodaje `scripts/production-seo-delivery-smoke.sh` z Content-Type/cache/no-Set-Cookie/validator/304 checks oraz rollout-gated feed contract,
- [x] dodać N5-006 `NewsroomSeoSitemapAuditTest` i rozszerzyć istniejący `SeoSitemapAuditor` o duplicate/missing-child, News namespace/tags/date/window, current-canonical/indexability/redirect-source, topology/shard/obsolete-file checks przy zachowaniu generic entry-count/byte-size guards,
- [x] stworzyć production smoke checklist w praktyce — executable checklist istnieje jako `scripts/production-seo-delivery-smoke.sh`,
- [ ] po wdrożeniu aktualnego Nginx configu uruchomić STRICT `workflow_dispatch` Production SEO Delivery Smoke i zachować artifact evidence dla robots/root/static sitemap/feed oraz conditional 304; report-only PR #117 już wykazał cache mismatch i nie zalicza tego gate'u,
- [x] wdrożyć i przetestować `NEWSROOM_PUBLIC_ENABLED` w N3-008 dla obecnie istniejących public detail/redirect + author + IndexNow surfaces; przyszłe N4/N5 discovery surfaces nadal wymagają tego samego gate,
- [x] dodać N4-001 `NewsroomHomeReadModelServiceTest` dla rollout gate, scalar/cacheable projection i stałego query budgetu niezależnego od liczby kategorii,
- [x] dodać N4-002 public Hub Blade/browser regression wraz z rzeczywistym publicznym rendererem,
- [x] dodać N4-003 category page feature/browser regression wraz z rzeczywistym publicznym rendererem,
- [x] dodać N4-004 guide-hub feature/browser regression wraz z rzeczywistym publicznym rendererem,
- [x] dodać N4-005 navigation/footer regression dla canonical primary links, duplicate-free footer oraz Vue/Blade shared source,
- [x] dodać N4-006 cache regression dla snapshot reuse, event invalidation i after-commit transaction boundary,
- [x] dodać N4-007 topic feature/browser regression dla HTTP lifecycle, eligible corpus, featured dedupe, pagination/canonical i responsive SSR,
- [x] dodać N4-008 semantic-link feature/browser regression dla category/topic/related/reverse modules, gate i responsive no-overflow,
- [x] dodać N6-001 dedykowany editorial/public Browser Smoke golden path i potwierdzić exact-head oraz post-merge CI,
- [x] dodać N6-002 production-mode scheduled-publication release smoke i potwierdzić real-clock pre-due/post-due publication, dirty/version oraz feed/sitemap refresh bez daily cron,
- [x] dodać N6-003 report-only/STRICT enterprise SEO production-validation harness reużywający existing static-delivery smoke,
- [x] dodać repo-level `SEO_RELEASE=1` deploy guard, który wymusza sitemap refresh/audit + `nginx -t` + publiczny production SEO delivery smoke bez samoczynnego włączania newsroomu lub aplikowania vhosta,
- [x] dodać canonical-host migration validation dla `http`/`www`/legacy `prawkoapp.pl` i zapisać GSC evidence bez uznawania redirect PASS za rozwiązanie Google-selected canonical mismatchu,
- [ ] uzyskać zielony manualny STRICT N6-003 run po aktualnym deploy/runtime i zachować live article/category/topic + GSC evidence,
- [ ] po pierwszym release wpisać rzeczywiste wyniki i ewentualne różnice od planu.

---

## 60. Historia zmian

### 2026-09-19 — v0.44

- PR #129 rozszerzył istniejący enterprise SEO validator o production-only canonical-host migration checks dla apex HTTP, obu `www` i historycznego `prawkoapp.pl`; custom `BASE_URL` jawnie pomija ten project-specific guard,
- Enterprise SEO Production Validation #3 potwierdził, że wszystkie warianty kończą się na `https://prawkonaraz.pl/`; `http://www` ma 2-hop warning, pozostałe sprawdzone warianty 1 hop,
- GSC 2026-09-19 potwierdza apex HTTP jako `Page with redirect`, oba `www` jako unknown i nadal nierozstrzygnięty `Duplicate, Google chose different canonical than user` dla canonical HTTPS homepage; `prawkoapp.pl` pozostaje referring URL,
- finalny HEAD `379f673e681c504322dbcabe370de110ce03dcc5` przeszedł CI #481; merge `main@8ea54ba1a8b1ca5349a468353e519fa7262a16d4` ma post-merge CI #482 PASS — 1140 passed / 20 229 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS i frontend build PASS,
- N6-003 nadal jest IN PROGRESS; trzy live failures i wymaganie actual deploy + STRICT + representative live samples pozostają otwarte.

### 2026-09-19 — v0.43

- PR #127 dodał opt-in `SEO_RELEASE=1` do istniejącego `deploy/mikrus/deploy.sh`: refresh/audit sitemap artifacts, `nginx -t` oraz publiczny production SEO delivery smoke po wyjściu z maintenance mode,
- `REQUIRE_NEWSROOM_PUBLIC=1` mapuje wymaganie rollout na publiczny feed smoke; guard nie stosuje ani nie reloaduje aktywnego vhosta i nie zmienia `NEWSROOM_PUBLIC_ENABLED`,
- finalny HEAD `e84758d911ffc4f6ff10a04f88a2b8c48beb22c6` przeszedł CI #477; merge `main@b26317bd979dd0c372b7a9341a8358f2599a9550` ma post-merge CI #478 PASS — 1140 passed / 20 229 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS i frontend build PASS,
- report-only Production SEO Delivery Smoke nadal wykazuje live `/robots.txt` `max-age=14400` i underlying exit `1`; N6-003/N5-007 pozostają IN PROGRESS do faktycznego deploy/runtime i zielonego STRICT evidence.

### 2026-09-19 — v0.42

- NEWSROOM-N6-003 otrzymał dedykowany enterprise SEO production-validation harness w PR #125; nie zmieniono produkcyjnej logiki aplikacji ani architektury,
- report-only PR mode zbiera live evidence, manualny `workflow_dispatch` domyślnie egzekwuje STRICT; workflow reużywa existing production static-delivery smoke i dodaje homepage/site-identity/logo/newsroom/sitemap/feed/sample checks,
- finalny HEAD `8a64f81c15fb6c52e2a90db26c5c65c9ad999801`: Enterprise SEO Production Validation #2 workflow success w REPORT-ONLY mode, Scheduled Publication Smoke #12 PASS, Browser Smoke #96 PASS, CI #472 PASS,
- merge PR #125 utworzył `main@0344427cdf42804a037c688df54da6216f243ca6`; post-merge CI #473 PASS — 1139 passed / 20 219 assertions / 2 skipped, PostgreSQL PASS, frontend build PASS,
- live report-only evidence nadal wykazuje robots cache mismatch, nieaktualny homepage graph identity, niespójny `/aktualnosci` indexing contract, brak article/news sitemap coverage i feed 404; GSC homepage ma Google-selected canonical mismatch, a `/aktualnosci` jest unknown,
- N6-003 pozostaje IN PROGRESS; nie zapisujemy report-only workflow success jako production PASS.

### 2026-09-19 — v0.41

- NEWSROOM-N6-002 zrealizowano przez PR #123 jako production-mode real-clock scheduled-publication release smoke bez zmiany produkcyjnej logiki publikacji ani architektury,
- smoke potwierdza hidden-before-due, `published=0` przed terminem, `published=1 failed=0 skipped=0` po realnym due, audit trigger `scheduler`, dirty/version advance oraz natychmiastowy refresh feed/`articles.xml`/`news.xml` bez daily cron,
- finalny implementation/test HEAD `2426f19215326a1c3386e8185222030b634ed795`: Newsroom Scheduled Publication Smoke #10 PASS, Browser Smoke #94 PASS, CI #467 PASS,
- merge PR #123 utworzył `main@afaa44dca0012144ab842c9195856eebec4553d3`; post-merge CI #468 również PASS,
- evidence pochodzi z izolowanego GitHub Actions environment z `APP_ENV=production` i stabilnym Docker app+Nginx originem; nie jest to live-production database mutation evidence,
- następny N6 task: NEWSROOM-N6-003 Enterprise SEO production validation; NEWSROOM-N5-007 live-production Nginx/STRICT HTTP/Cloudflare/GSC gate'y pozostają otwarte.

### 2026-09-19 — v0.40

- NEWSROOM-N6-001 zrealizowano w PR #121 jako dedykowany Browser Smoke golden path bez zmiany produkcyjnego kodu ani architektury; zmieniono wyłącznie `.github/workflows/browser-smoke.yml`, `package.json` i `scripts/e2e-newsroom-golden-path.mjs`,
- golden path potwierdza create draft przez rzeczywisty Filament state, source/topic/question/hero persistence, private/no-store/noindex preview, workflow `draft -> in_review -> reviewed -> published`, a następnie public hub -> article -> related question -> product CTA,
- finalny implementation/test HEAD `76d0fa7d428b11f2db9d901eac698c7a169722ff`: Browser Smoke #84 PASS oraz CI #454 PASS — 1139 passed / 20 219 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS, frontend build 9.47 s,
- merge PR #121 to `main@b0b29d9b4a3337f4e527334f6ead8b1be85303ad`; post-merge CI #455 również PASS — 1139 passed / 20 219 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS, frontend build 10.17 s,
- następny N6 task to NEWSROOM-N6-002 scheduled-publication production smoke; N5-007 produkcyjne Nginx/STRICT HTTP/GSC gate'y pozostają otwarte.

### 2026-09-19 — v0.39

- PR #119 dodał wyłącznie dedykowane scheduler-definition/shared-lock contention test evidence do istniejącego `NewsroomSeoArtifactRefreshCoordinatorTest`; kod produkcyjny nie został zmieniony,
- runtime assertion chroni `everyMinute`, production-only, `onOneServer()` i `withoutOverlapping()`, a lock test faktycznie zajmuje coordinator lock i potwierdza successful skip bez generatora/audytora oraz zachowanie dirty state,
- exact-head CI #424: 1139 passed / 20 219 assertions / 2 skipped, PostgreSQL 7/94, Pint 1102 files PASS, frontend 9.86 s; post-merge CI #425 powtórzył 1139 / 20 219 / 2 skipped, PostgreSQL 7/94, Pint 1102 files PASS i frontend 9.04 s,
- następny twardy release gate pozostaje produkcyjny: zastosować aktualny Nginx config i uzyskać zielony STRICT workflow run, potem zachować Cloudflare/origin/GSC evidence.

### 2026-09-19 — v0.38

- PR #117 dodał `.github/workflows/production-seo-delivery-smoke.yml` nad istniejącym `scripts/production-seo-delivery-smoke.sh`,
- PR mode jest REPORT-ONLY, manualny `workflow_dispatch` domyślnie STRICT; raport jest uploadowany jako artifact,
- exact-head CI #420: 1137 passed / 20 207 assertions / 2 skipped, PostgreSQL 7/94, Pint 1102 files PASS, frontend 9.66 s; post-merge CI #421 powtórzył 1137 / 20 207 / 2 skipped, PostgreSQL 7/94, Pint 1102 files PASS i frontend 5.49 s,
- report-only run przeciw produkcji miał underlying exit code 1, bo `/robots.txt` zwrócił `Cache-Control: max-age=14400` zamiast oczekiwanego `public, max-age=3600`; nie zapisujemy tego jako production PASS,
- następny twardy release gate: zastosować aktualny Nginx config i uzyskać zielony STRICT workflow run; scheduler-definition/lock-contention regression pozostaje osobnym open evidence.

### 2026-09-18 — v0.37

- trzeci podkrok NEWSROOM-N5-007 zmergowano przez PR #115; finalny implementation head `30fa65d63eacdcb31b2a1c8c815f4034e29b5166`, merge `main@9a9c98d3534492e30ba215fd9785b7dd425a1f75`,
- dodano `NginxSeoStaticDeliveryConfigurationTest` chroniący repo-level production config dla robots/root/child sitemap: Content-Type, public cache, ETag, `if_modified_since exact`, nosniff i static-first `try_files`,
- dodano `scripts/production-seo-delivery-smoke.sh`, który sprawdza HTTP 200, Content-Type, public Cache-Control, brak Set-Cookie, ETag/Last-Modified i conditional 304; dla `/aktualnosci/feed.xml` 404 jest akceptowane tylko przy niewymaganym public feedzie,
- exact-head CI #416: 1137 passed / 20 207 assertions / 2 skipped, PostgreSQL 7/94, Pint 1102 files PASS, frontend build 6.82 s; post-merge CI #417 powtórzył 1137 / 20 207 / 2 skipped, PostgreSQL 7/94, Pint PASS i build 10.16 s,
- production smoke harness istnieje, ale nie zapisujemy go jako production PASS: brak jeszcze dowodu rzeczywistego uruchomienia przeciw `https://prawkonaraz.pl` po wdrożeniu aktualnego Nginx configu,
- NEWSROOM-N5-007 pozostaje IN PROGRESS; dodatkowo dedykowany scheduler-definition/lock-contention regression nadal jest otwarty.

### 2026-09-18 — v0.36

- drugi podkrok NEWSROOM-N5-007 zmergowano przez PR #112; finalny implementation head `6f1c3b99d11796f74987c7fe640302b2d76578b7`, merge `main@79b6de0276ba8cdce500c366a4f98098e528dcd8`,
- `NewsroomSeoArtifactRefreshCoordinatorTest` potwierdza version/clean-version coalescing, after-commit lifecycle dirty signals, clean-state skip, successful clean, audit failure retention oraz nowszą version pojawiającą się podczas generation,
- kod implementuje shared cache lock oraz production schedule co minutę z `onOneServer()` + `withoutOverlapping()`; dedykowany test lock contention / schedule definition nie został dodany i pozostaje jawnym open evidence,
- daily `seo:refresh-sitemaps` nadal istnieje jako niezależny recovery path; testy działają przy `QUEUE_CONNECTION=sync` i implementacja nie zakłada queue workera,
- canonical deployment docs potwierdzają obecny single-node Mikrus 4.1 z lokalnym `public/`, Redisem i jednym scheduler cronem; topology gate jest spełniony dla tego contractu, ale multi-node wymaga ponownej weryfikacji i shared artifact distribution,
- exact-head CI #409 zakończył 1136 passed / 20 182 assertions / 2 skipped, PostgreSQL 7/94, Pint 1101 files PASS i frontend build 10.03 s; post-merge CI #410 na exact `main@79b6de0276ba8cdce500c366a4f98098e528dcd8` powtórzył 1136 / 20 182 / 2 skipped, PostgreSQL 7/94, Pint PASS i build 7.54 s,
- Browser Smoke nie został uruchomiony dla backend/scheduler/test-only zakresu #112; nie zapisujemy nieistniejącego browser evidence,
- production-like static robots/sitemap/feed delivery smoke i rzeczywiste HTTP headers/cache/validators nadal pozostają otwarte, więc NEWSROOM-N5-007 nie jest DONE.

### 2026-09-18 — v0.35

- pierwszy podkrok NEWSROOM-N5-007 zmergowano przez PR #109; finalny implementation head `dbcd5cdb2f0e6663c998f930c034624f8fe36bf4`, merge `main@1744a93fd8f0bddfe7fc5bff90b146fdea24b016`,
- `SeoSitemapGenerationTest` dodaje regression dla root `sitemap.xml` publikowanego po child payloads, invalid XML odrzuconego przed zmianą istniejącego setu oraz obsolete zarządzanych article/news files usuwanych po switchu bez kasowania unrelated XML,
- CI #401 i #402 nie są finalnym evidence ze względu na Pint failure w test helperze; finalny exact-head CI #403 przeszedł 1130 passed / 20 144 assertions / 2 skipped, PostgreSQL 7/94, Pint 1093 files PASS i frontend build 9.94 s,
- post-merge CI #404 na exact `main@1744a93fd8f0bddfe7fc5bff90b146fdea24b016` powtórzył 1130 / 20 144 / 2 skipped, PostgreSQL 7/94, Pint PASS i frontend build 9.69 s,
- Browser Smoke nie został uruchomiony dla tego backend/test-only zakresu; nie zapisujemy nieistniejącego browser evidence,
- dirty-marker refresh tests, scheduler/shared-lock regression oraz production-like static robots/sitemap/feed delivery smoke pozostają otwarte, więc NEWSROOM-N5-007 nie jest DONE.

### 2026-09-18 — v0.34

- NEWSROOM-N5-006 implementation PR #107 miał finalny head `64feb88b614b737a69784d43e86e97591aaec3d5`; merge to `main@18300baf3a91ffc5e3c549ab664c471bfd34ffe4`,
- `NewsroomSeoSitemapAuditTest` pokrywa valid output, duplicate index loc + missing child, missing News namespace, required tag + duplicate News URL, News entry/window guard, stale draft/noindex/redirect-source, mixed topology + obsolete shard oraz generic article entry-limit guard,
- `NewsroomLinkAuditTest` pokrywa healthy pass, orphan/source/breaking/non-public semantic targets/broken featured topic oraz gate=false suppression dla `newsroom:audit-links`,
- pierwszy run CI #396 nie jest finalnym evidence: backend 1127/20 131 i PostgreSQL/Browser przeszły, ale Pint wykrył jeden unused import w `NewsroomSemanticLinkService`; po style-only fix cały gate został uruchomiony od nowa na nowym SHA,
- finalny exact-head CI #397: 1127 passed / 20 131 assertions / 2 skipped, PostgreSQL 7/94, Pint PASS, frontend build PASS; Browser Smoke #57 zakończył PASS dla article/home/category/guides/topic/semantic-links,
- post-merge CI #398 na exact `main@18300baf3a91ffc5e3c549ab664c471bfd34ffe4` powtórzył 1127 passed / 20 131 assertions / 2 skipped, PostgreSQL 7/94, Pint PASS i frontend build PASS,
- N5-006 nie zmieniło generatora ani publication/freshness coordinatora; następnym wykonywalnym taskiem jest NEWSROOM-N5-007.

### 2026-09-18 — v0.33

- NEWSROOM-N5-005 implementation PR #105 zakończył exact-head CI #392 PASS na `44c8099ac2ea020bf5d9e31cfe547af8cb2149f7`; merge to `main@1cc9f4bec8c7d7fc105fd3495664434cf4323eea`,
- `NewsroomIndexNowAutomationTest` pokrywa after-commit first publish, republish/archive, withdraw/restore, substantive update/no-op, old+new slug paths bez duplikacji, scheduled/noindex/gate suppression, outer rollback oraz failure isolation,
- `IndexNowQueueAutomationTest` dodatkowo chroni protokół HTTP: lokalny `event_type` nie jest serializowany do payloadu `IndexNowSubmissionService`,
- implementacja przeszła finalny exact-head gate dopiero w CI #392 po usunięciu podwójnej rejestracji auto-discovered listenera, korekcie fixture czasu i style-only Pint fix; wcześniejsze failing runy nie są traktowane jako evidence wykonania,
- CI #392 i post-merge CI #393 zakończyły 1116 passed / 20 095 assertions / 2 skipped, PostgreSQL 7/94, Pint PASS i frontend build PASS,
- Browser Smoke nie został uruchomiony przez path filters dla backendowego zakresu #105; nie zastępujemy tej informacji fałszywym browser evidence,
- następnym wykonywalnym taskiem jest NEWSROOM-N5-006 — Extend existing SEO/sitemap audits.
### 2026-09-18 — v0.32

- NEWSROOM-N5-004 implementation PR #103 zakończył exact-head CI #384 PASS na `6235b7dbadd60549a82ceebcb4eda47aaa6596fa`; Browser Smoke #55 PASS dla article/home/category/guides/topic/semantic-links; merge to `main@5704b3c3a8acde029001e28567980c5de8e27cdf`,
- `NewsroomAnalyticsHooksTest` chroni render i privacy-safe event hooks, `GoogleAnalyticsTagTest` readiness signal, a `NewsroomHomePageTest` stabilne module names i brak content fields,
- `newsroom-article` Browser QA ma teraz dodatkowy JS-enabled analytics pass: delayed readiness -> exactly one article view, click -> one event, generic module click oraz brak eventu dla non-link/`aria-disabled`,
- exact-head CI #384: 1106 passed / 20 051 assertions / 2 skipped, Pint PASS, frontend build 9.72 s, PostgreSQL 7/94; post-merge CI #385 powtórzył 1106 / 20 051 / 2 skipped, Pint PASS, frontend build 7.30 s i PostgreSQL 7/94,
- Browser Smoke #55 pozostaje PR-level release evidence; push CI nie uruchamia tego workflow, więc post-merge dowodem JS analytics jest zweryfikowany exact-head Browser Smoke #55 plus post-merge backend/build CI #385,
- następnym wykonywalnym taskiem jest NEWSROOM-N5-005 — IndexNow integration review.
### 2026-09-18 — v0.31

- NEWSROOM-N5-003 implementation PR #101 zakończył exact-head CI #380 PASS na `1c89f370e899895eb25ac80bd437b19c1797a9ec`; Browser Smoke #54 PASS dla article/home/category/guides/topic/semantic-links; merge to `main@18cd07233e3c8712cf2c7fc9e0c32018baef65d3`,
- `NewsroomFeedTest` pokrywa Atom content/metadata, bounded latest corpus, non-news/non-active exclusion, stable URN przy slug change, archive invalidation, ETag/Last-Modified, osobne conditional 304 i brak `Set-Cookie`,
- public gate regression potwierdza feed 404 + discovery suppression przy false oraz Atom 200 przy true; route regression nie oczekuje już historycznego 404 dla wdrożonego feedu,
- post-merge CI #381: 1104 passed / 20 024 assertions / 2 skipped, Pint PASS, frontend build 6.21 s; `newsroom-postgres` 7 passed / 94 assertions,
- produkcyjny static/CDN/Nginx smoke i Search Console evidence pozostają niewykonane; nie są zastępowane przez application-level tests,
- następnym wykonywalnym taskiem jest NEWSROOM-N5-004 — Analytics hooks.

### 2026-09-18 — v0.30

- NEWSROOM-N5-002 implementation PR #99 zakończył exact-head CI #371 PASS na `9bc16a7e42ae55c23b1916a4e214b9af846fd3dd`; merge to `main@827f3816487d3a404df26c381a103a6cd1a9f413`,
- `tests/Feature/Public/SeoSitemapGenerationTest.php` pokrywa News Sitemap namespace/required metadata, canonical publication name, visible title, 2-day `first_published_at` eligibility, old-updated exclusion, public gate, non-news/status/noindex/category/author/redirect-source exclusion oraz realny 1000→1001 split,
- existing auditor dopuszcza legalny overlap URL standard article/news sitemap; pełne namespace/age/tag/shard checks pozostają N5-006 i nie są fałszywie zaliczane,
- post-merge CI #372 zakończył pełny gate: 1100 passed / 19 964 assertions / 2 skipped, Pint PASS, frontend build 10.28 s; `newsroom-postgres` 7 passed / 94 assertions,
- nie ma jeszcze dowodu produkcyjnego static/Nginx/CDN/GSC ani child-before-index/obsolete-shard cleanup/dirty refresh; feed tests pozostają otwarte,
- następnym wykonywalnym taskiem jest NEWSROOM-N5-003 — RSS/Atom feed + discovery.

### 2026-09-18 — v0.29

- NEWSROOM-N5-001 implementation PR #97 zakończył exact-head CI #367 PASS na `b4ff321011c3d58438876c9d69f324e342ec9021`; merge to `main@5ddfa48c0646fa89cc802d129e6d9ccee9d18957`,
- `tests/Feature/Public/SeoSitemapGenerationTest.php` pokrywa rollout-gated standard article sitemap, public/indexable eligibility, current-canonical/redirect-source exclusion, meaningful `lastmod`, single shard, boundary crossing, stabilny fixed-ID-range assignment, brak duplikatów oraz URL-count/byte-size guards,
- istniejący `SeoSitemapAuditor` sprawdza również ogólne protocol limits wygenerowanych plików; news namespace-specific checks pozostają downstream,
- post-merge CI #368 zakończył pełny gate: 1098 passed / 19 926 assertions / 2 skipped, Pint PASS, frontend build 9.61 s; `newsroom-postgres` 7 passed / 94 assertions,
- nie ma jeszcze dowodu produkcyjnego static/Nginx/CDN/GSC ani child-before-index/obsolete-shard cleanup; news sitemap, atomic publication, dirty refresh i feed tests pozostają otwarte,
- następnym wykonywalnym taskiem jest NEWSROOM-N5-002 — News sitemap.

### 2026-09-18 — v0.28

- NEWSROOM-N4-008 implementation PR #95 zakończył exact-head CI #361 PASS na `bd63773bc1febdcc6aa8c2507c6621e908d63b09` oraz Browser Smoke #48 PASS dla `newsroom-semantic-links`, `newsroom-article`, `newsroom-home`, `newsroom-category`, `newsroom-guides` i `newsroom-topic`; merge to `main@3d7ac8ab8a3ed1c299cb0cdd4cb1ef8ac6b53f78`,
- `NewsroomSemanticLinkIntegrationTest` pokrywa deterministic related order/limit, reverse bounds/eligibility, question graph non-mutation, legal explicit pivots, TrafficSign `direct|example` fail-closed i public-gate suppression,
- `NewsroomPublicArticlePageTest` chroni crawlable primary-category link na guide detail, a `e2e-newsroom-semantic-links.mjs` sprawdza rzeczywiste article/question/legal/sign surfaces,
- post-merge CI #362 zakończył pełny gate: 1093 passed / 19 881 assertions / 2 skipped, Pint PASS, frontend build 7.42 s; `newsroom-postgres` 7 passed / 94 assertions,
- per-article audit data jest wdrożone; site-wide orphan/click-depth auditor nie został dodany i nie jest fałszywie zaliczany,
- następnym wykonywalnym taskiem jest NEWSROOM-N5-001.

### 2026-09-18 — v0.27

- NEWSROOM-N4-007 implementation PR #93 zakończył exact-head CI #348 PASS na `812313afc38e30e53c59901d46c2d24188e3f6fa` oraz Browser Smoke #39 PASS dla `newsroom-topic`, `newsroom-guides`, `newsroom-category`, `newsroom-home` i `newsroom-article`; merge to `main@a97373003c5a249a28761df15342f19e656c50c5`,
- `NewsroomTopicPageTest` pokrywa rollout gate, published/draft/future/archived lifecycle, activelyDistributed+indexable mixed corpus, eligible featured, later below-baseline behavior, pagination/canonical i structured data,
- `e2e-newsroom-topic.mjs` wykonuje JS-disabled responsive QA na 360/390/430/768/1024/1280/1440, realny built CSS, H1/opis/featured/listing, canonical, horizontal overflow i page-2 canonical,
- post-merge CI #349 zakończył pełny gate: 1087 passed / 19 827 assertions / 2 skipped, Pint 1081 files PASS, frontend build 9.48 s; `newsroom-postgres` 7 passed / 94 assertions,
- N4-007 nie zmienia schema/CMS, nie dodaje automatycznych tag pages, topic cache, reverse links ani N5 discovery; następnym wykonywalnym taskiem jest NEWSROOM-N4-008.

### 2026-09-18 — v0.26

- NEWSROOM-N4-006 implementation PR #91 zakończył exact-head CI #342 PASS na `093ca709d3155d5fa3f13bae224312fd286077dc` oraz Browser Smoke #36 PASS dla `newsroom-guides`, `newsroom-category`, `newsroom-home` i `newsroom-article`; merge to `main@5f1880e03469fc6e340a86fdb1b4246c7afd116b`,
- `NewsroomPublicReadCacheTest` potwierdza rzeczywisty cache reuse oraz invalidation po publish, placement change, archive, category metadata i outer-transaction commit; test base czyści trwały file cache między izolowanymi feature tests,
- post-merge CI #343 zakończył pełny gate: 1081 passed / 19 770 assertions / 2 skipped, Pint 1077 files PASS, frontend build 9.44 s; `newsroom-postgres` 7 passed / 94 assertions,
- Browser Smoke #36 potwierdził brak regresji publicznych article/home/category/guides surfaces; case-insensitive evergreen assertion jest test-only hardening i nie zmienia renderer contract,
- N4-006 nie obejmuje guide/article/topic/feed cache ani N5 dirty/version/sitemap/IndexNow; następnym wykonywalnym taskiem jest NEWSROOM-N4-007 — Topic / dossier pages.

### 2026-09-18 — v0.25

- NEWSROOM-N4-005 implementation PR #89 zakończył exact-head CI #335 PASS na `189219b3586d2df8e4ea73045318fd68f38f0fa1` oraz Browser Smoke #32 PASS dla `newsroom-guides`, `newsroom-category`, `newsroom-home` i `newsroom-article`; merge to `main@a9fb9ccfed058de88efdb6e0833b67911aeb09aa`,
- `NewsroomNavigationIntegrationTest` pokrywa canonical primary links, duplicate-free shared footer i istniejące document-navigation prefixes; `e2e-newsroom-guides.mjs` sprawdza realny Blade footer na pełnej macierzy viewportów,
- post-merge CI #336 zakończył pełny gate: 1075 passed / 19 745 assertions / 2 skipped, Pint 1069 files PASS, frontend build 7.32 s; `newsroom-postgres` 7 passed / 94 assertions,
- primary links oraz `documentNavigationPrefixes` nie zostały zmienione; funkcjonalną zmianą N4-005 są tylko pojedyncze wejścia `Aktualności` i `Poradniki` w `PublicFooter::service_links`, współdzielone przez Vue i Blade,
- następnym wykonywalnym taskiem jest NEWSROOM-N4-006 — Cache.

### 2026-09-18 — v0.24

- NEWSROOM-N4-004 implementation PR #87 zakończył exact-head CI #331 PASS na `119cbd94d1fb6ff6f9f2025e726242190927266d` oraz Browser Smoke #31 PASS dla `newsroom-guides`, `newsroom-category`, `newsroom-home` i `newsroom-article`; merge to `main@2bb22142b1e9bec803f9c3889c11000194f46783`,
- `NewsroomGuidesHubTest` pokrywa rollout gate, guide-only eligibility, deterministic order, pagination, empty state/noindex, canonical i schema; `e2e-newsroom-guides.mjs` wykonuje JS-disabled responsive QA oraz page-2 canonical check,
- post-merge CI #332 zakończył pełny gate: 1072 passed / 19 731 assertions / 2 skipped, Pint 1068 files PASS, frontend build 10.14 s; `newsroom-postgres` 7 passed / 94 assertions,
- gate=false zachowuje `/poradniki` placeholder/noindex; gate=true renderuje publiczny guide-only SSR hub, empty hub ma 200/noindex, invalid/out-of-range page failuje do 404,
- następnym wykonywalnym taskiem jest NEWSROOM-N4-005 — Navigation integration.

### 2026-09-18 — v0.23

- NEWSROOM-N4-003 implementation PR #85 zakończył exact-head CI #326 PASS na `8e4f707060fbaa348e9392f0b19beb7b4517beca` oraz Browser Smoke #29 PASS dla `newsroom-category`, `newsroom-home` i `newsroom-article`; merge to `main@84bcb2aeff57a1374def7af411c39db07a8fb38d`,
- `NewsroomCategoryPageTest` pokrywa rollout gate, eligibility/status filtering, deterministic order, paginację, empty state/noindex, canonical i schema; `e2e-newsroom-category.mjs` wykonuje JS-disabled responsive QA oraz page-2 canonical check,
- post-merge CI #327 zakończył pełny gate: 1067 passed / 19 684 assertions / 2 skipped, Pint 1065 files PASS, frontend build 9.40 s; `newsroom-postgres` 7 passed / 94 assertions,
- aktywna pusta kategoria pozostaje 200/noindex; invalid/out-of-range i gate=false failują do 404; topic/feed, `/poradniki` hub, cache/invalidation i N5 discovery pozostają otwarte,
- następnym wykonywalnym taskiem jest NEWSROOM-N4-004 — `/poradniki` hub.

### 2026-09-18 — v0.22

- NEWSROOM-N4-002 implementation PR #83 zakończył exact-head CI #321 PASS na `1036b67aa44b56fffb0bc1e9083d3a0d1d3963d0` oraz Browser Smoke #27 PASS dla `newsroom-home` i `newsroom-article`; merge to `main@04a3e961a40207bd65c41b684a5bf1cd8d2c5e10`,
- dodano `NewsroomHomePageTest` i `e2e-newsroom-home.mjs`; hub browser QA działa z JavaScript disabled na 360/390/430/768/1024/1280/1440 i sprawdza built CSS, H1/subnav/content/CTA, canonical/robots oraz horizontal overflow,
- post-merge CI #322 zakończył pełny gate: 1062 passed / 19 638 assertions / 2 skipped, Pint 1061 files PASS, frontend build 7.59 s; `newsroom-postgres` 7 passed / 94 assertions,
- gate=false zachowuje placeholder/noindex, gate=true renderuje SSR Hub Blade; category/topic/feed, `/poradniki` hub, cache/invalidation i N5 discovery pozostają otwarte,
- następnym wykonywalnym taskiem jest NEWSROOM-N4-003 — Category pages.

### 2026-09-18 — v0.21

- NEWSROOM-N4-001 implementation PR #81 zakończył exact-head CI #314 PASS na `813aba108b6b68f0526df3a9c8c86d82df5ca0f6`, a post-merge CI #315 przeszedł pełny gate na `main@e0e06e9af8a6b02a63ef4b3e1eb2d772409ad974`,
- dodano `tests/Feature/NewsroomHomeReadModelServiceTest.php` dla rollout-gated scalar projection oraz regresji stałego query budgetu; istniejący `NewsroomHomeCompositionServiceTest` nadal pokrywa placement/fallback/dedupe/breaking/future-preview semantics,
- post-merge `quality`: 1059 passed / 19 611 assertions / 2 skipped, Pint 1060 files PASS, frontend build 9.93 s; `newsroom-postgres`: 7 passed / 94 assertions,
- N4-001 nie miało osobnego Browser Smoke, ponieważ publiczny `/aktualnosci` nadal jest placeholderem; N4-002 odpowiada za Hub Blade i jego browser regression.

### 2026-09-18 — v0.20

- NEWSROOM-N3-008 implementation PR #79 zakończył exact-head CI #308 PASS na `c8484aa1529eb41805a76ceb7be1f55db63aec14`; dedykowany Browser Smoke #23 również zakończył się PASS, a post-merge CI #309 przeszedł pełny gate na `main@23b952b77e39cd25fb39edc252faf05849946bd7`,
- dodano `tests/Feature/NewsroomPublicGateTest.php` dla enabled/disabled route, guide, old-path redirect, placeholder, author profile, author sitemap contribution, IndexNow collector i private preview,
- `phpunit.xml` i job `newsroom-article` jawnie ustawiają public gate na `true`, podczas gdy `.env.example` utrwala bezpieczny dark-deploy default `false`,
- public hub/category/topic/feed, reverse links oraz article/news sitemap pozostają N4/N5; N4-001 jest następnym taskiem po dokumentacyjnym zamknięciu N3-008.


### 2026-09-17 — v0.19

- NEWSROOM-N3-007 implementation PR #77 zakończył exact-head CI #295 PASS na `bf7981d23ff99a6335b55ecaeb6ce36b6622a042`; post-merge CI #296 zakończył PASS na `main@c68672f6aa7c41defaeec56debb541d5a60d9f4f`,
- `ContentAuthorProfileTest` potwierdza lifecycle author profile, public-state sitemap freshness, blokadę unpublish z indexable article oraz odblokowanie po noindex,
- post-merge `quality`: 1055 passed / 19 540 assertions / 2 skipped; Pint 1055 files PASS; frontend build PASS; `newsroom-postgres` PASS,
- N3-007 nie miało osobnego Browser Smoke i dokument nie dopisuje takiego dowodu; następny zakres zaczyna się od N3-008.

### 2026-09-17 — v0.18

- NEWSROOM-N3-006 zmergowano przez PR #75; finalny implementation head `f48e7a12fa6c53422cd2ef8c369af81769163b9d`, post-merge `main@33d9946219595a4be75d789b19cc8d10efc2ecc0`,
- dodano `tests/Feature/NewsroomArticleRedirectTest.php`: newsroom i guide historical paths zwracają one-hop 301 do bieżącego canonical; current canonical pozostaje 200, a invalid/non-301/stale redirect state failuje do 404 z istniejącą noindex unavailable policy,
- query params nie są przenoszone do redirect target; domenowe one-hop history/locking pozostają pokryte istniejącymi N1-003 tests,
- exact-head CI #279 i Browser Smoke #21 były PASS; post-merge CI #280 zakończył się pełnym PASS (`quality` 1051 passed / 19 512 assertions / 2 skipped, Pint 1052 files PASS, frontend build PASS; `newsroom-postgres` PASS),
- N3-007 author profile integration jest następnym taskiem; sitemap-only-current-canonical pozostaje N5.

### 2026-09-17 — v0.17

- NEWSROOM-N3-005 zmergowano przez PR #73; finalny implementation head `7d795b895865cda49ba94a4fec50533d7b0f7a97`, a zweryfikowany post-merge `main` to `fcc8074f89db141d522c5000742afc2e07a68565`,
- dodano `tests/Feature/NewsroomProductBridgeTest.php` dla explicit body+pivot+public eligibility, omission inactive/draft/unlinked targets, private-note/official-excerpt leakage guard, traffic-sign `related` fail-closed i trzech contextual CTA destinations,
- Browser Smoke #20 zakończył się PASS na pełnej macierzy 360/390/430/768/1024/1440 z JS disabled, realnym built CSS i Product Bridge/CTA assertions; artifact `newsroom-article-browser-qa` ma SHA256 `753b717cb5f0319e2e7799552b181fd972b41cd3b3a66bba7dd9a82d7ce96a2a`,
- finalny exact-head CI #275 na PR head był PASS, a post-merge CI #276 na `main@fcc8074f...` zakończył się pełnym PASS: quality 1049 passed / 19 498 assertions / 2 skipped, Pint 1051 files PASS, frontend build PASS i newsroom-postgres PASS,
- N3-006 old-slug 301 jest następnym wykonywalnym taskiem; N3-007 author integration, N3-008 rollout gate, N4-008 reverse links oraz pełny accessibility/hub/category/topic/feed/release E2E pozostają otwarte.

### 2026-09-17 — v0.16

- NEWSROOM-N3-004 zmergowano przez PR #71 na `main@7398c5d930d38d6cc9d9953e53b4298df41cfce8`; publiczny article HTTP/Blade renderer i jego feature regression są teraz stanem rzeczywistym, nie planem,
- dedykowany Browser Smoke #18 zakończył się PASS na całej wymaganej macierzy 360/390/430/768/1024/1440 z JS disabled, realnym built CSS, canonical/JSON-LD/content assertions i horizontal-overflow guard,
- finalny push CI #270 na merge commit zakończył się pełnym PASS: quality 1048 passed / 19 473 assertions / 2 skipped, Pint 1049 files PASS, frontend build PASS; newsroom-postgres 7 passed / 94 assertions,
- genericzny product browser smoke nie jest używany jako dowód N3-004; dedykowany `newsroom-article` job jest automatycznym PR gate dla relevant public article changes,
- N3-005 Product Bridge, N3-006 old-slug 301, N3-007 author integration i N3-008 rollout gate pozostają otwarte; pełny accessibility/hub/category/topic/feed/release E2E nadal nie jest uznany za wykonany.

### 2026-09-17 — v0.15

- NEWSROOM-N3-003 dodał rzeczywisty `tests/Feature/NewsroomArticleSchemaServiceTest.php` dla backendowego schema graph service; proponowana mapa testów w sekcji 4 pozostaje jawnie targetem, nie listą istniejących plików,
- regression sprawdza stabilne entity IDs, canonical/date consistency, Organization/WebSite dedupe, author/publisher relations, NewsArticle/Article mapping, newsroom/guide breadcrumbs, path-backed/deduplicated image nodes oraz fail-closed invalid public inputs,
- finalny zweryfikowany `main@5f85bec331428a73f3859ae40b555f55e7e7820d` przeszedł CI #245: quality 1043 passed / 19 418 assertions / 2 skipped, Pint 1045 files, frontend build 9.04 s; newsroom-postgres 7 passed / 94 assertions,
- publiczny article controller/Blade oraz browser/E2E JSON-LD response regression nadal nie istnieją i pozostają częścią N3-004/release gate.

### 2026-09-16 — v0.14

- NEWSROOM-N2-009 dodał `NewsroomHomeComposerTest`: admin-only custom page, fixed-slot UI, fallback visibility, bounded search, create/update/delete placement flow, AuditLog actor, stale-write reject, duplicate warning i admin-only future preview,
- `NewsroomHomePlacementServiceTest` pokrywa deterministyczny edit token, same-second stale update, stale delete i audit; istniejący PostgreSQL advisory-lock regression nadal przechodzi,
- `NewsroomHomeCompositionServiceTest` potwierdza deterministyczny fallback przy `includeManualPlacements=false`, a future preview nadal wykorzystuje scheduled readiness i nie mutuje workflow,
- finalny PR #58: quality 1006 passed / 19 194 assertions / 2 skipped, Pint 1024 files PASS, frontend build PASS (11.05 s); newsroom-postgres PASS,
- browser E2E publicznego newsroomu nadal nie istnieje i nie jest fałszywie uznany za pokryty przez te feature tests.

### 2026-09-16 — v0.13

- NEWSROOM-N2-008 dodał feature/security regression dla private Article preview: anonymous denied, non-admin 403, admin allowed, `private, no-store`, meta/header noindex,nofollow, brak shareable signed tokenów i brak public analytics,
- test potwierdza escaping rich text/XSS, `noopener noreferrer` dla target=_blank oraz brak wycieku `is_publicly_cited=false` source evidence/notatek,
- publiczny `/aktualnosci/{slug}` nadal 404 w tym etapie, więc test jawnie chroni granicę N2 preview vs N3 public renderer,
- finalny PR #56: quality 997 passed / 19 146 assertions / 2 skipped, Pint 1020 files PASS, frontend build PASS; newsroom-postgres PASS.

### 2026-09-16 — v0.12

- NEWSROOM-N1-006 dodał `NewsroomHomeCompositionServiceTest` i `NewsroomHomePlacementServiceTest` dla manual/fallback resolution, current/future windows, scheduled preview, global dedupe, category context, short modules oraz breaking exception,
- PostgreSQL gate dodał realny advisory-lock regression dla konkurencyjnego zapisu pustego placement tuple,
- N2 stale-write editor behavior pozostaje niewdrożone i nie jest utożsamiane z N1 tuple-concurrency contract,
- finalny PR #35: quality 935 passed / 18 769 assertions / 2 skipped, Pint 990 files, frontend build PASS; newsroom-postgres 7 passed / 89 assertions.

### 2026-09-16 — v0.11

- NEWSROOM-N1-004 dodał `NewsroomPublishingServiceTest` dla transition matrix, publish/schedule invariants, date semantics, AuditLog actor/metadata i breaking cleanup,
- test rollbacku potwierdza, że `ContentArticleWorkflowTransitioned` nie jest dostarczany przed outer commit i nie pozostaje po rollbacku,
- feature regression obejmuje archive/needs_review/withdraw/restore/republish i stabilność `first_published_at`,
- publiczny HTTP 410 dla withdrawn nadal nie jest pokryty, ponieważ N3 controllers nie istnieją; obecne testy weryfikują domenowy tombstone/public visibility state,
- finalny PR #30: quality 917 passed / 18 656 assertions / 2 skipped, Pint 983 files, frontend build PASS; newsroom-postgres 6 passed / 86 assertions.

### 2026-09-16 — v0.10

- NEWSROOM-N1-003 dodał service-level slug/history/canonical resolver regression na SQLite i PostgreSQL,
- feature tests pokrywają slug allocation, history reservation/reclaim, one-hop redirects, route-family transition/exclusivity i compact audit metadata,
- PostgreSQL test używa drugiego connection + lock_timeout do realnego sprawdzenia transaction advisory lock contention,
- finalny `newsroom-postgres`: 6 testów / 86 asercji PASS; finalny `quality`: 906 passed / 18 565 assertions / 2 skipped, Pint 980 files, frontend build PASS,
- public HTTP old-slug 301/new-canonical 200 oraz sitemap-only-current-canonical pozostają niewykonane do czasu N3/N5 integration.

### 2026-09-16 — v0.9

- NEWSROOM-N1-001 dodał realny addytywny job `newsroom-postgres` do canonical CI,
- PostgreSQL 16 gate uruchamia izolowany `NewsroomPostgresMigrationTest` poza globalnym Pest Feature scope,
- gate wykonuje migrate fresh, sprawdza krytyczne indeksy i FK delete rules oraz realny rollback 12 newsroom migrations,
- finalny targeted wynik: 3 testy / 64 asercje PASS; ogólny `quality`, Pint i frontend build również PASS,
- dotychczasowy szybki SQLite `quality` pozostał bez zastępowania PostgreSQL gate,
- browser E2E, editor/renderer integration i N5 static-delivery regressions pozostają otwarte.

### 2026-09-16 — v0.8

- zapisano rzeczywisty test state po N0-006: `NewsroomMediaStorageTest` pokrywa storage namespace, immutable paths, actual bytes/MIME/dimensions, SVG/non-raster i stable public URL,
- rozdzielono istniejący storage/security foundation od nadal brakujących N2 uploader/focal-point/crop integration i N3 media rendering/E2E,
- code gate N0-006 przeszedł 883 tests / 18 419 assertions / 2 skipped, Pint 939 files oraz frontend build,
- nie oznaczono crop variants ani upload endpoints jako istniejących.

### 2026-09-16 — v0.7

- zapisano rzeczywisty test state po N0-004: `NewsroomBodyContractTest` pokrywa body schema/validation/security foundation,
- rozdzielono istniejące unit/security regression od nadal brakujących N2 editor i N3 renderer integration/E2E,
- potwierdzono fail-closed unknown version/block, structured rich-text XSS/URL guards oraz disabled embed jako wykonany kontrakt,
- pełny public renderer/editor release gate pozostaje niewykonany.

### 2026-09-16 — v0.6

- doprecyzowano source-link regression: public/private citation leakage, crawlable href, bez przypadkowego nofollow i bezpieczny external-link contract,
- przypięto feed identity do `content_articles.id` i dodano test niezmienności GUID/id przy slug change,
- dodano pełny IndexNow transition matrix testujący commit-before-enqueue, withdrawn=410/deleted, slug-old=301/updated oraz brak event verb w HTTP payload.

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
- historycznie dodano child-before-index atomic publication, async/debounced refresh i daily recovery tests; **async/debounced model zastąpiono później** dirty/version coordinator tests,
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
