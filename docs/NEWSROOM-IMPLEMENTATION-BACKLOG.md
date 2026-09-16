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

## 3. Gate hierarchy i hard dependencies

N0–N6 pozostają etapami organizacyjnymi, ale nie stosujemy fałszywego „wszystko N0 blokuje wszystko N1”. Obowiązują konkretne hard gates:

| Gate | Musi być zamknięte przed | Dowód zamknięcia |
| --- | --- | --- |
| G0-A Routing/taxonomy: N0-002 + N0-003 | N1 slug/category invariants, N3 public routes | route tests + seed contract |
| G0-B Body format: N0-004 | N1 publish validation, N2 editor, N3 renderer | schema blocków + backend sanitizer/structured format + body_schema_version strategy |
| G0-C Brand identity: N0-001 | N3 structured data i N5 SEO launch | regression tests Organization/WebSite/site name |
| G0-D Existing SEO compatibility: N0-005 | N5 sitemap/robots changes | docs contract + baseline regression tests |
| G1 Domain/DB | N2 workflow/CMS writes | migrations/models/services green na PostgreSQL |
| G2 Admin/workflow | użycie redakcyjne N2 i public rollout | admin-only policy, AuditLog actor, stale-write, private preview |
| G3 Public article | N4 reverse links i final N5 article SEO | public HTTP/schema/canonical tests |
| G4 IA/hubs | final internal-link/sitemap audit | category/topic/guide/hub tests |
| G5 SEO distribution | włączenie indeksowalnego newsroomu | static sitemap/feed/robots/IndexNow regression |
| G6 Release | `NEWSROOM_PUBLIC_ENABLED=true` | backup + E2E + production SEO/scheduler/static-delivery smoke |

Prace niezależne mogą iść po zamknięciu własnych prerequisites, ale **publiczny rollout jest liniowo blokowany przez G1→G6**.

---

# N0 — Foundation decisions and cleanup

## NEWSROOM-N0-001 — Publisher branding source of truth

### Status implementacji

**DONE — G0-C zamknięty.** Implementacja została wykonana i zweryfikowana pełnym CI w PR #15.

### Cel

Usunąć niespójność PrawkoNaRaz / Orły na Drodze w structured data.

### Stan przed implementacją

Istniały już:

- `config/content.php['organization']`,
- `SchemaIds::organization()` i `SchemaIds::website()`,
- `SchemaRenderer`,
- Organization/WebSite/Person graph pattern w istniejących schema services.

Problemem był `HomePageController`, który hardcodował „Orły na Drodze” i stare logo.

### Aktualny stan implementacji

- dodano współdzielony `App\SEO\Schema\SiteIdentitySchema`,
- `config/content.php['organization']` pozostaje jedynym source of truth dla danych Organization,
- homepage emituje wspólny graph Organization/WebSite z istniejącymi stabilnymi `/#organization` i `/#website`,
- homepage nie emituje już legacy publishera ani assetu „Orły na Drodze”,
- wspólny public layout emituje `og:site_name` z kanonicznego organization config,
- WebSite ma jeden `name` oraz `alternateName` z `app.name`, jeśli jest rzeczywiście różne,
- Organization logo używa publicznego `favicon.png`; zweryfikowane fizyczne wymiary 256×256 są zapisane w organization config/env contract,
- `TrafficSignSchemaService`, `PublicQuestionSchemaService` i `LegalContentSchemaService` delegują Organization/WebSite do tego samego buildera,
- test homepage blokuje powrót „Orły na Drodze” i sprawdza site name, stabilne IDs, publisher relation oraz logo dimensions,
- istniejące testy public question graph sprawdzają wspólny logo/site-name contract.

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

- config/content.php tylko jeśli kontrakt wymaga uzupełnienia istniejących danych; nie tworzyć równoległego brand configu
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

### Status implementacji

**DONE — route-contract foundation zamknięty na `main` przez PR #16.**

Zakres N0-002 utrwala routing i executable route-family contract. Nie oznacza jeszcze wdrożenia `ContentArticle`, publicznych controllerów artykułów ani record-level lookupu; te elementy pozostają zadaniami downstream i muszą konsumować ten kontrakt.

### Decyzja

Preferowany:

- /aktualnosci
- /aktualnosci/kategoria/{categorySlug}
- /aktualnosci/temat/{topicSlug}
- /aktualnosci/{articleSlug}
- /poradniki
- /poradniki/{articleSlug}

### Stan przed implementacją

- `/aktualnosci` już istniało jako named route `public.news` i renderowało `Public/MarketingPlaceholder`,
- `/poradniki` już istniało jako named route `public.guides` i renderowało placeholder,
- oba linki już istniały w primary `PublicNavigation`.

### Zadania

- **zachować istniejące top-level route names** i podmienić target/controller zamiast dodawać duplikaty,
- dodać detail/category/topic/feed routes przed catch-all zgodnie z route contract,
- reserved slug list + regex,
- jawnie zmapować type -> route family,
- test route matching/order.

### Aktualny stan implementacji

- zachowano top-level route names `public.news` i `public.guides`,
- dodano named routes:
  - `public.news.feed` -> `/aktualnosci/feed.xml`,
  - `public.news.categories.show` -> `/aktualnosci/kategoria/{categorySlug}`,
  - `public.news.topics.show` -> `/aktualnosci/temat/{topicSlug}`,
  - `public.news.show` -> `/aktualnosci/{articleSlug}`,
  - `public.guides.show` -> `/poradniki/{articleSlug}`,
- parametry używają kontraktu `[a-z0-9-]+`; newsroom article route rezerwuje segmenty `kategoria` i `temat`,
- feed/category/topic routes są deklarowane przed article catch-all,
- przyszłe feed/category/topic/detail routes są obecnie zarejestrowane, ale celowo zwracają 404 do czasu wdrożenia właściwych controllerów,
- `/aktualnosci` i `/poradniki` nadal zachowują pre-launch placeholder UX, ale przez dedykowany `NewsroomPlaceholderController` dodają `X-Robots-Tag: noindex, follow`,
- shared `Public/MarketingPlaceholder` innych sekcji nie został globalnie zmieniony,
- `NewsroomRouteContract` implementuje type -> route family, canonical path, reserved slug policy i guard cross-family type transition po pierwszej publikacji,
- record-level lookup przeciw faktycznemu `ContentArticle` nie istnieje jeszcze; N3 public controllers muszą użyć route-family contract tak, aby rekord nie mógł odpowiadać 200 pod obiema rodzinami.

### Route family

- news/explainer/analysis/report -> `/aktualnosci/{slug}`
- guide -> `/poradniki/{slug}`

Po pierwszej publikacji zwykła edycja nie może przenieść rekordu pomiędzy tymi rodzinami.

### DoD

- brak ambiguity category vs article,
- ten sam rekord nie odpowiada 200 pod oboma route families,
- cross-family type change po first publish zablokowany,
- pre-launch `/aktualnosci` i `/poradniki` placeholdery nie są pozostawione jako indeksowalne thin pages: przy public gate=false mają jawne `noindex` bez zmiany shared MarketingPlaceholder dla niepowiązanych routes,
- dokumenty aktualizowane.

Aktualny N0-002 realizuje pre-launch noindex bez feature flaga; `NEWSROOM_PUBLIC_ENABLED` pozostaje osobnym zadaniem N3-008 i ma później przejąć sterowanie rolloutem bez zmiany route contract.

---

## NEWSROOM-N0-003 — Taxonomy seed contract

### Status implementacji

**DONE — executable taxonomy seed contract zamknięty na `main` przez PR #18.**

N0-003 zamraża wartości wejściowe dla przyszłego seeda DB. Nie oznacza jeszcze istnienia tabeli `content_categories`, modelu `ContentCategory` ani seedera zapisującego rekordy do bazy; te elementy należą do N1.

### Zakres

Zatwierdzić kategorie v1:

- prawo-jazdy
- egzaminy
- przepisy
- word
- kierowcy
- osk

### Aktualny stan implementacji

`NewsroomTaxonomyContract::categories()` jest jedynym wykonywalnym kontraktem kategorii v1 i zwraca deterministycznie:

| position | slug | nazwa publiczna |
| ---: | --- | --- |
| 10 | `prawo-jazdy` | Prawo jazdy |
| 20 | `egzaminy` | Egzaminy |
| 30 | `przepisy` | Przepisy |
| 40 | `word` | WORD |
| 50 | `kierowcy` | Kierowcy |
| 60 | `osk` | OSK |

Dla wszystkich pozycji kontrakt ustawia `is_active=true`, a `description`, `seo_title` i `seo_description` pozostają jawnie `null` do czasu zatwierdzenia copy. Test blokuje przypadkową zmianę listy, nazw, kolejności, duplikaty slugów/pozycji oraz slugi niezgodne z `NewsroomRouteContract::SLUG_PATTERN`.

Przyszły dedykowany seeder N1 ma konsumować ten kontrakt zamiast utrzymywać drugą listę kategorii.

### DoD

- seed contract,
- kolejność,
- nazwy publiczne,
- SEO descriptions mogą pozostać draftem, ale bez niejasności slugów.

---

## NEWSROOM-N0-004 — Block editor + serialization + sanitization decision

### Status implementacji

**DONE — G0-B body-format contract zamknięty na `main` przez PR #20.**

N0-004 zamyka format, walidację i compatibility policy. Nie oznacza jeszcze wdrożenia N2 article editor ani N3 public body renderer.

### Cel

Domknąć techniczny sposób edycji kanonicznego `body_blocks`.

### Do sprawdzenia

- Filament Builder lub równoważny komponent,
- format payloadu rich_text,
- schema payloadu każdego block type,
- sanitizer,
- aktualny security-header/CSP state,
- allowlisted embeds lub jawne wyłączenie embed v1.

### Decyzja musi opisać

- `body_blocks` jako jedyne źródło body,
- serializację,
- allowed nodes/elements,
- link handling,
- image/embed handling,
- embed default-off dopóki provider allowlist + sandbox/referrerpolicy + CSP/frame-src nie są wdrożone i przetestowane,
- unknown block behavior,
- dokument/block schema version strategy,
- zasada ewolucji payloadów: renderer backward-compatible lub jawna migracja danych,
- renderer dla nowego block type musi zostać wdrożony przed umożliwieniem jego tworzenia w CMS,
- rollback code nie może zostać wykonany do wersji, która nie potrafi bezpiecznie odczytać już zapisanych blocków bez osobnego data planu,
- XSS tests.

### Aktualny stan implementacji

`App\Support\NewsroomBodyContract` jest wykonywalnym source of truth dla body schema v1:

- kanoniczny zapis to uporządkowana lista `{key?, type, data}`; stan Filament Buildera jest tylko adapterem UI i nie staje się formatem domenowym,
- przyszły N2 editor używa `Filament\Forms\Components\Builder`,
- `rich_text` używa structured TipTap JSON z `Filament\Forms\Components\RichEditor`, bez zapisu arbitrary HTML,
- toolbar rich text jest zamrożony do bold/italic/link, H2/H3, ordered/unordered lists oraz undo/redo,
- aktywne v1 block types: `rich_text`, `image`, `quote`, `table`, `context`, `related_article`, `legal_reference`, `question_group`, `traffic_sign_group`, `product_cta`,
- `embed` jest znanym typem, ale w v1 jest feature-disabled/fail-closed,
- unknown block type, unknown payload field i unsupported `body_schema_version` failują zamknięcie,
- rich text allowlistuje tylko paragraph, H2/H3, lists, text, hard break oraz marks bold/italic/link; raw HTML/style nodes nie są formatem wejściowym,
- `javascript:` i nieobsługiwane targety linków są odrzucane; `target=_blank` otrzymuje wymuszone `rel="noopener noreferrer"`,
- image block przyjmuje tylko storage-relative path; N0-006 dostarcza już `NewsroomMediaStorage`, a faktyczny N2 upload UI/persistence i crop pipeline pozostają downstream,
- domain blocks zapisują kontrolowane IDs, a nie skopiowane HTML/card payloady,
- block `key` ma stabilny format, jest unikalny i nie może być sprzeczny z Builder item key.

Test `tests/Unit/Support/NewsroomBodyContractTest.php` pokrywa kontrakt payloadów, fail-closed schema evolution, unsafe URLs/nodes/marks, disabled embed, image path traversal, relation duplicates, table shape oraz XSS regression przez `RichContentRenderer`.

Strategia ewolucji formatu pozostaje zgodna z decyzją architektoniczną: obecny reader obsługuje wyłącznie v1 i failuje bezpiecznie dla przyszłej wersji. V2 może zostać włączone dopiero po wdrożeniu readera zgodnego wstecz albo jawnej migracji danych; writer/editor nie może wyprzedzić readera/renderera. Rollback nie może kierować do kodu, który nie umie odczytać już zapisanej wersji bez osobnego data planu.

### DoD

- brak „ustalimy podczas formularza”,
- nie powstaje równoległe edytowalne `body_html`,
- wszystkie włączone v1 block types mają kontrakt; `embed` może pozostać feature-disabled bez blokowania reszty newsroomu,
- istnieje jawna strategia compatibility/migration przy zmianie formatu bez wprowadzania revision history.

---


## NEWSROOM-N0-005 — Existing SEO delivery compatibility contract

**Decision status:** zamknięty dokumentacyjnie; nie oznacza to wdrożenia N5.

### Cel

Zamrozić sposób integracji newsroomu z już działającym backendem SEO przed rozpoczęciem N5.

### Potwierdzony fundament

- `SEO-SITEMAP-REPAIR-PLAN.md` preferuje statyczne artefakty w `public/`,
- istnieją `SeoSitemapGenerator`, `SeoSitemapBuilder`, `SeoSitemapAuditor`,
- działa daily `seo:refresh-sitemaps`,
- istnieją równolegle route `SitemapController` oraz statyczne XML,
- istnieją statyczny `public/robots.txt` i `RobotsController`,
- istniejący question relation graph/taksonomia jest osobnym nadrzędnym subsystemem.

### Decyzja

- newsroom rozszerza statyczny generator/auditor zamiast tworzyć drugi sitemap engine,
- nie usuwa kontrolerów ani nie zmienia Nginx/Cloudflare w PR-ach N1-N4,
- newsroom article-question relation nie edytuje `question_relations` ani `question_seo_topics`,
- produkcyjny robots/sitemap source musi być zweryfikowany HTTP przed jakimkolwiek cleanupem duplikatu.

### DoD

- implementacja N1-N4 nie zmienia zachowania istniejących sitemap/robots/question graph,
- N5 ma osobny rollback i regression suite dla starego SEO backendu.

---

## NEWSROOM-N0-006 — Media upload/storage contract

### Status implementacji

**DONE — storage/validation foundation zamknięty na `main` przez PR #22.**

Zakres N0-006 zamraża bezpieczny storage contract przed N2 media editor. Nie oznacza jeszcze wdrożenia presign/confirm endpointów, `ContentArticle` media persistence, Filament hero/focal-point UI ani crop generatora.

### Stan przed implementacją

- `MediaUrlResolver` i media disk config są wspólne,
- `AdminMediaUploadService` jest question-specific i zapisuje QuestionMedia,
- brak potwierdzonego generic newsroom uploader/crop pipeline.

### Decyzja do zamknięcia przed N2 media editor

- newsroom-specific upload adapter/service lub jawny Filament upload do wydzielonego prefixu,
- reuse public disk/MediaUrlResolver conventions,
- backend MIME/size/dimensions validation,
- stabilne, immutable/unique public storage paths; replacement tworzy nowy path zamiast nadpisywać istniejący asset pod tym samym URL,
- brak signed URL w modelu,
- JPEG/PNG/WebP/AVIF baseline; SVG disabled unless separate security decision,
- nie deklarować/generated crop variants, jeśli fizycznie nie istnieją.

### Aktualny stan implementacji

- istnieje dedykowany `NewsroomMediaStorage`; nie reużywa `AdminMediaUploadService`,
- newsroom ma osobny konfigurowalny `media.newsroom_disk`, `media.newsroom_prefix` i `media.newsroom_max_dimension`,
- source image path ma immutable/unique namespace `newsroom/articles/source/{ULID}.{ext}`; każdy replacement generuje nową ścieżkę,
- baseline MIME jest przecięciem wspólnej image allowlisty z JPEG/PNG/WebP/AVIF; SVG jest odrzucane nawet jeśli pojawi się we wspólnej allowliście,
- `inspectStoredImage()` sprawdza faktycznie zapisany obiekt: istniejący path, rzeczywisty bytes, dekodowalny raster, rzeczywisty MIME oraz width/height,
- deklarowany przez klienta MIME/bytes może zostać porównany z rzeczywistym obiektem i mismatch jest odrzucany,
- path traversal, URL/absolute path i obiekty spoza managed immutable namespace są odrzucane,
- publiczny URL jest rozwiązywany przez istniejący `MediaUrlResolver` i musi być stabilnym HTTP(S) URL,
- kontrakt nie zapisuje signed/temporary URL,
- crop/variant files nie są deklarowane ani generowane, bo newsroom crop pipeline nadal nie istnieje,
- unit regression pokrywa unique paths, MIME spoofing/non-raster, actual bytes/dimensions, SVG rejection, namespace oraz public URL.

### DoD

- implementator nie reużywa question-specific service przez przypadek,
- storage/public URL contract jest przetestowany przed hero uploaderem.

---

# N1 — Domain and database

## NEWSROOM-N1-001 — Enums + migrations

### Status implementacji

**DONE — schema foundation zmergowany przez PR #24 na `main@47e748047ec655ff2fdb5669d8cbff7e51041dc8`.**

Zakres obejmuje 5 enumów, 12 migracji, SQLite schema regression oraz addytywny `newsroom-postgres` gate. PostgreSQL 16 zweryfikował `migrate:fresh`, krytyczne indeksy/FK delete rules i rollback 12 newsroom migrations. W momencie zamknięcia N1-001 modele/factories/scopes były następnym zakresem N1-002; obecnie N1-002 również jest zmergowane.

### Aktualny stan implementacji

- istnieją enumy: `ContentArticleType`, `ContentArticleWorkflowStatus`, `ContentArticleSourceType`, `ContentArticleOriginType`, `ContentArticleRegulatoryStatus`,
- istnieje 12 migracji newsroomu: categories, tags, articles, topics, membership pivots, sources, question/legal/sign relations, redirects i home placements,
- `content_articles` zawiera `body_blocks`, `body_schema_version`, regulatory/media/focal/public-state fields zgodne z data spec,
- `canonical_url` i `featured_position` nie zostały dodane,
- istnieją testy schema/enum na zwykłym CI oraz osobny PostgreSQL migration contract,
- workflow CI ma zachowany job `quality` na SQLite oraz addytywny job `newsroom-postgres` na PostgreSQL 16,
- finalny PostgreSQL gate: 3 testy / 64 asercje PASS; finalny `quality`: backend, Pint i frontend build PASS.

### Zakres

- ContentArticleType
- ContentArticleWorkflowStatus
- ContentArticleSourceType
- ContentArticleOriginType
- ContentArticleRegulatoryStatus
- content topics
- content home placements
- body_blocks + body_schema_version + hero caption + focal point + regulatory fields + public_state_changed_at
- brak `canonical_url` override w v1
- brak `featured_position` w content_articles; pozycja wyłącznie w content_home_placements
- tables zgodne z data spec.

### Testy / CI

- migrate fresh PostgreSQL,
- rollback,
- constraints,
- indexes,
- krytyczne FK/on-delete directions (no cascade from article into existing product entities),
- zachować istniejący szybki CI na SQLite,
- w tym PR albo przed jego merge dodać addytywny job `newsroom-postgres` (PostgreSQL service) obejmujący newsroom migration/domain tests.

### DoD

- schema rzeczywiście odpowiada docs,
- istniejący ogólny CI nadal przechodzi,
- PostgreSQL gate ma automatyczny job albo równoważny, zapisany dowód uruchomienia; samo „full CI green” na SQLite nie spełnia gate,
- DATABASE-SCHEMA.md zaktualizowane tylko po merge.

---

## NEWSROOM-N1-002 — Models + factories

### Status implementacji

**DONE — modele, relacje, scopes i factories zostały zmergowane w PR #26 i zweryfikowane na SQLite oraz PostgreSQL 16.**

Zakres N1-002 materializuje warstwę Eloquent nad schema N1-001. Nie obejmuje slug/redirect service, workflow publishing, schedulera, home composition, CMS ani publicznego renderera.

### Aktualny stan implementacji

- istnieją `ContentArticle`, `ContentCategory`, `ContentTag`, `ContentTopic`, `ContentArticleSource` i `ContentHomePlacement`,
- `ContentArticle` ma enum/date/body casts oraz relacje do category/author/reviewer/tags/topics/sources/home placements/questions/legal units/traffic signs,
- istnieją reverse relations na `ContentAuthor`, `Question`, `LegalUnit` i `TrafficSign`,
- rozdzielono `publiclyVisible()`, `activelyDistributed()` i `indexable()`; istnieją też scopes/predicates dla scheduled/category/featured/active breaking/freshness,
- category/topic/source/home-placement mają własne scopes/predicates zgodne z Domain Spec,
- factories istnieją dla wszystkich 6 modeli N1-002; `ContentArticleFactory` ma jawne stany `draft`, `inReview`, `scheduled`, `published`, `breaking`, `needsReview`, `archived` oraz dodatkowy `withdrawn`,
- `ContentArticleFactory` reużywa `NewsroomBodyContract` zamiast duplikować format body,
- addytywny job `newsroom-postgres` uruchamia całe `tests/Postgres`, więc zachowuje migration contract i dodaje model/scope contract.

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

- publiclyVisible vs activelyDistributed vs indexable scopes,
- archived historical URL vs active listings,
- category active/publication invariant,
- topic corpus/publication invariant,
- category scope,
- active breaking,
- source relations,
- question/legal pivots.

---

## NEWSROOM-N1-003 — Slug service + redirects

### Status implementacji

**DONE — canonical slug/history service został zmergowany przez PR #28 na `main@f2ccc997b4aea4634b148ff4696aa66eaaf75d91`.**

N1-003 zamyka domenowy kontrakt slugów, historycznych full paths, one-hop redirects, route-family exclusivity resolver oraz PostgreSQL serialization. Nie oznacza jeszcze publicznego HTTP 301/200, ponieważ N3 article controllers nadal nie istnieją.

### Aktualny stan implementacji

- istnieje `ContentArticleSlugService` dla create, initial slug allocation, explicit slug validation, slug change i type change,
- generated slug allocation jest deterministyczne i suffixuje kolizje; reserved newsroom segments są pomijane,
- opublikowana zmiana sluga zapisuje `ContentArticleRedirect` 301 i przepisuje całą historię bezpośrednio do aktualnego canonical path,
- własny historyczny path można odzyskać tylko przez service; historyczny `from_path` innego artykułu pozostaje zarezerwowany,
- cross-family type change jest dozwolony tylko przed `first_published_at`; opublikowana zmiana w obrębie tej samej route family pozostaje dozwolona,
- `ContentArticlePathResolver` egzekwuje service-level route-family exclusivity i rozwiązuje historyczne redirect records,
- `PostgresTransactionAdvisoryLock` serializuje mutacje slug/full-path; lock keys są deduplikowane i sortowane przed pobraniem,
- slug/type mutations zapisują kompaktowy `AuditLog` bez body,
- finalny `newsroom-postgres`: 6 testów / 86 asercji PASS, w tym realny contention test na drugim połączeniu PostgreSQL,
- finalny `quality`: 906 passed / 18 565 assertions / 2 skipped; Pint 980 files PASS; frontend build PASS.

### Zakres

- initial slug,
- unique handling,
- published slug change,
- redirect record,
- historyczne full paths są reserved przed innym article,
- same-article historical path reclaim tylko przez service,
- no chains,
- type -> route family resolver,
- block cross-family type change after first publication.

### Testy

- draft slug change,
- published slug change,
- old path redirect,
- duplicate current slug reject,
- new/current canonical path colliding with another article historical from_path reject,
- same article can intentionally reclaim own historical path with redirects rewritten one-hop,
- concurrent create/slug-change for same historical/current full path is serialized by PostgreSQL advisory/row-lock strategy,
- draft guide -> news allowed before first publish,
- published guide -> news blocked,
- published news -> analysis keeps same canonical family,
- same record never returns 200 under both route families.

---

## NEWSROOM-N1-004 — Publishing service

### Status implementacji

**DONE — publishing/workflow foundation zmergowany przez PR #30 na `main@701c9eb41003bd0d6a18c417f1051ccc6372668b`.**

Aktualny zakres implementuje domenowe przejścia workflow, walidację publish/schedule eligibility, audyt i after-commit event boundary. Nie oznacza jeszcze publicznych controllerów/HTTP disposition ani CMS actions.

Potwierdzony kod:

- `ContentArticlePublishingService`,
- `ContentArticleWorkflowTransitioned implements ShouldDispatchAfterCommit`,
- `NewsroomPublishingServiceTest`.

Zamknięte przejścia obejmują draft ↔ review, review mark, initial schedule, publish, needs_review, archive, dedicated archived republish, withdraw oraz restore-to-review z zachowaniem tombstone aż do skutecznego publish.

Istotne granice pozostają otwarte:
- publiczne HTTP `410 Gone` dla withdrawn nadal należy do N3; N1-004 ustanawia stan/tombstone, ale nie renderuje HTTP,
- `applyPublicUpdate` dla już publicznego artykułu pozostaje N2 orchestration/stale-write scope,
- scheduler command/registration został wdrożony w N1-005; N1-004 pozostaje domenowym service boundary używanym przez scheduler,
- cache/sitemap/IndexNow listeners nie są jeszcze podłączone; PR #30 ustanawia wyłącznie bezpieczny after-commit hook.

### Zakres

- submit for review,
- publish,
- schedule,
- archive,
- withdraw + restore-to-review,
- needs review,
- transition out of published clears breaking flag/expiry,
- transaction boundary dla state/timestamps/AuditLog,
- User actor w AuditLog oddzielony od ContentAuthor author/reviewer,
- public side effects wyłącznie after commit.

### Testy

- invalid transition,
- missing requirements,
- first_published_at stable,
- date semantics: dateModified vs public_state_changed_at/sitemap lastmod,
- rollback nie emituje cache/sitemap/IndexNow side effect,
- audit actor/metadata bez pełnej treści,
- archived previously-published article zachowuje public 200, ale znika z active distribution,
- archived Republish wymaga fresh review/checklist i nie przechodzi przez publiczne 404,
- withdrawn wymaga reason, zwraca 410 bez contentu i nie trafia do dystrybucji/sitemap.

---

## NEWSROOM-N1-005 — Scheduler

### Status implementacji

**DONE — scheduled initial publish zmergowany przez PR #33 na `main@7157b60b643fa57e38c111a26a42cf70b5715024`.**

Potwierdzony kod:

- `PublishDueNewsroomArticlesCommand` / komenda `newsroom:publish-due`,
- produkcyjna rejestracja w `routes/console.php` co minutę z `withoutOverlapping()`,
- delegacja publikacji do `ContentArticlePublishingService` z istniejącym row lockiem i pełną rewalidacją eligibility,
- deduplikowany AuditLog `content_article.scheduled_publish_failed` + warning log dla due recordów, które przestały spełniać invariants,
- service-level guard blokujący scheduled republish, gdy `first_published_at` już istnieje,
- `NewsroomPublishDueCommandTest`.

Aktualne zachowanie:

- query wybiera wyłącznie `workflow_status=scheduled` z `scheduled_for <= now()`,
- przyszłe rekordy nie są dotykane,
- valid due article publikuje się raz; powtórny run jest bezpieczny,
- due-time failure pozostawia artykuł w `scheduled`, jest audytowalny/logowany i nie blokuje kolejnych rekordów,
- identyczny powtarzający się failure dla tego samego `scheduled_for`/exception nie tworzy nowego AuditLog przy każdym minutowym uruchomieniu,
- konkurencyjna/powtórna zmiana stanu po pobraniu ID jest traktowana jako skip po refetchu, a finalna mutacja nadal przechodzi przez row lock serwisu,
- komenda kończy przetwarzanie całego batcha, ale zwraca non-zero, jeśli pozostały faktyczne due failures,
- reviewer identity pozostaje opcjonalne zgodnie z policy; scheduler rewaliduje istniejący kontrakt publication-ready oraz fresh `reviewed_at`, nie wprowadza nowej mandatory `reviewer_id` policy.

### Zakres

- newsroom:publish-due dla initial publish,
- scheduler registration,
- idempotency,
- pełna rewalidacja eligibility w due time,
- brak scheduled republish publicznego 200 w v1.

### Testy

- future not published,
- due + nadal valid published,
- due ale author/category/source/reviewer/body policy przestała być valid -> nie publikuje, log/audit failure,
- failure jednego rekordu nie blokuje kolejnych,
- already published ignored,
- repeated command safe.

---

## NEWSROOM-N1-006 — Home composition service

### Status implementacji

**DONE — zmergowano PR #35 na `main@eb13b2160e4b8d49c128869ad4761eb0875b2dac` po zielonych jobach `quality` i `newsroom-postgres`.**

Implementacja obejmuje warstwę domenową/read-model kompozycji oraz transakcyjny writer placements. Nie oznacza jeszcze wdrożenia publicznego kontrolera `/aktualnosci`, Filament UI placements ani stale-write UX; te elementy pozostają odpowiednio N3/N2.

### Zakres

- resolve active placements,
- validate publication-at-preview-time,
- deterministic fallback,
- global card deduplication,
- context-aware category leads.

### Testy

- manual placement wins tylko dla activelyDistributed target,
- needs_review/archived/withdrawn target nie może pozostać aktywnym placementem; resolver przechodzi do fallbacku,
- równoległe overlapping placement writes są serializowane i tylko jeden może wygrać,
- expired/future placement ignored at current time,
- future preview resolves scheduled article only after its publish time,
- duplicate article excluded from later card modules,
- missing unique candidate shortens module.

### Aktualny stan implementacji

- `NewsroomHomeCompositionService` rozwiązuje ręczne placements przed fallbackiem,
- bieżący render przyjmuje wyłącznie `activelyDistributed()` artykuły; future preview może uwzględnić initial `scheduled` dopiero od `scheduled_for` po pełnej, niemutującej rewalidacji publishing invariants,
- fallback jest deterministyczny: featured, `editorial_priority`, data publikacji/schedule i stabilny tie-breaker po `id`,
- globalny zbiór użytych article IDs deduplikuje kolejno lead -> secondary -> latest -> category blocks -> guides -> important now,
- breaking strip jest niezależnym wyjątkiem i może powtórzyć lead,
- category lead respektuje `context_key`; guides lead wymaga typu `guide`,
- brak unikalnych kandydatów skraca moduł zamiast duplikować kartę,
- `NewsroomHomePlacementService` waliduje kontrolowane sloty/context/date ranges i serializuje overlap check przez transaction advisory lock + row lock,
- PostgreSQL concurrency regression potwierdza serializację także dla pustego placement tuple,
- `ContentArticlePublishingService::assertScheduledPreviewReady()` udostępnia wspólny, niemutujący contract publikowalności future preview.

### DoD

- overlap validation wykonuje się wewnątrz transakcyjnego row/advisory locka,
- public controller nie implementuje composition logic ręcznie,
- breaking strip może wskazać lead jako jedyny jawny wyjątek dedupe.

---

# N2 — CMS and editorial workflow

## NEWSROOM-N2-001 — ContentCategoryResource

### Status implementacji

**DONE — zmergowano PR #37 na `main@c103dda20c96b75f21413f72c684f774d081a6d0` po zielonych jobach `quality` i `newsroom-postgres`.**

### Aktualny stan implementacji

- istnieje Filament `ContentCategoryResource` z pages index/create/view/edit,
- formularz zarządza nazwą, opisem, SEO, `position` i `is_active`; slug jest podawany przy tworzeniu i disabled na edit,
- slug jest dodatkowo walidowany i immutable na poziomie modelu, więc UI nie jest jedyną ochroną,
- tabela pokazuje liczniki wszystkich, `publiclyVisible()` i `activelyDistributed()` artykułów,
- kolejność można zmieniać przez `position`, w tym table reorder,
- delete jest blokowany modelowo, jeśli istnieje jakikolwiek artykuł; brak bulk delete,
- deactivation jest blokowana modelowo oraz przez edit-page validation, jeśli istnieją publiczne lub aktywnie dystrybuowane artykuły,
- guardy save/delete wykonują świeże zapytania relacji; testy potwierdzają, że stale preloaded counts nie omijają invariantów,
- resource pozostaje dostępny wyłącznie przez istniejący admin-only Filament panel contract.

### Zakres

- CRUD,
- active,
- order,
- article counts.

### DoD

- nie można skasować kategorii używanej przez artykuły,
- slug kategorii jest immutable w v1,
- active=false blokowane, jeśli istnieją publiczne/aktywnie dystrybuowane artykuły.

---

## NEWSROOM-N2-002 — ContentArticleResource shell

### Status implementacji

**DONE — zmergowano PR #40 na `main@5a4f92e08c8618ff70270abb683f97bd88d02700` po zielonych jobach `quality` i `newsroom-postgres`.**

### Aktualny stan implementacji

- istnieje Filament `ContentArticleResource` z pages index/create/view/edit oraz rozdzielonymi Form/Infolist/Table,
- create draft deleguje do istniejącego `ContentArticleSlugService`, więc generowanie lub jawny slug nadal przechodzi canonical/history reservation contract,
- draftowe zmiany `type` i `slug` również delegują do `ContentArticleSlugService`; resource nie implementuje równoległej logiki route-family/history,
- lista eager-loaduje `category`, `author` i `reviewer`, wyszukuje po title/slug/lead oraz ma filtry workflow/type/category/author/reviewer/featured/breaking/scheduled/freshness/published date,
- dostęp pozostaje admin-only przez istniejący `User::canAccessPanel()` contract; moderator i student są odrzucani,
- zalogowany `User` pozostaje aktorem AuditLog, a `ContentAuthor` publiczną tożsamością autora/reviewera,
- dla `publiclyVisible()` zwykły Edit ma także server-side guard: publiczne pola nie są zapisywane, a shell pozwala w tej ścieżce tylko na wewnętrzny `editorial_note`,
- Builder/RichEditor, sources, media, origin/regulatory fields, workflow actions, stale-write, preview i HomeComposer nie należą do N2-002 i pozostają otwarte.

### Zakres

- Resource,
- pages,
- table,
- infolist,
- basic form.

### DoD

- draft can be created przez istniejącego administratora,
- moderator/student/non-admin nie uzyskuje dostępu do panelu newsroom,
- User actor i ContentAuthor identity nie są utożsamiane,
- list filters/search work,
- eager loading no N+1.

---

## NEWSROOM-N2-003 — Block editor and sanitization

### Status implementacji

**DONE — zmergowano PR #42 na `main@13a22058c7c945196e8d490a0620b93dcf449641` po zielonych jobach `quality` i `newsroom-postgres`.**

### Aktualny stan implementacji

- istnieje kontrolowany Filament Builder w `ContentArticleResource` dla wszystkich aktywnych bloków v1 z `NewsroomBodyContract`,
- `NewsroomBodyEditorAdapter` tłumaczy Builder state na kanoniczne `body_blocks` i zachowuje jawne `key` niezależnie od efemerycznych UUID UI,
- RichEditor zapisuje structured TipTap JSON z toolbar/allowlist wynikającymi z `NewsroomBodyContract`, bez równoległego `body_html`,
- create/edit zapisują `body_schema_version=1` i wykonują server-side `NewsroomBodyContract::normalize()` przed persistence,
- bounded searchable pickers istnieją dla related article, legal unit, question group i traffic sign group; duże corpusy nie są preloadowane,
- `embed` pozostaje znany kontraktowi, ale feature-disabled/fail-closed zgodnie z N0-004; N2-003 nie włącza providerów bez osobnego provider/CSP security gate,
- image block przyjmuje storage-relative path i metadata formularza; upload, actual object verification i crop pipeline pozostają dalszym media scope,
- ordinary Edit `publiclyVisible()` nadal ma server-side blokadę body/public fields,
- regression pokrywa canonical key round-trip, kolejność/persistence, script-looking content, onclick/style, `javascript:` link, raw iframe, allowed H2/list/safe link, unknown/invalid payload i disabled embed,
- finalny gate: `quality` 963 passed / 18 895 assertions / 2 skipped, Pint 1010 files PASS, frontend build PASS; `newsroom-postgres` PASS.

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

### Status implementacji

**DONE — zmergowano PR #44 na `main@fd2042f22532b7b7c14dc993e532c0887876e159` po zielonych jobach `quality` i `newsroom-postgres`.**

### Aktualny stan implementacji

- istniejący `ContentArticleSource` jest edytowany jako relationship Repeater `sources` w `ContentArticleResource`; nie dodano osobnego source resource ani migracji,
- Repeater używa `defaultItems(0)`, więc zwykły draft nie jest blokowany pustym source row; wymóg source dla news pozostaje finalnym review/publish invariant,
- wszystkie source types v1 i pola modelu są dostępne, reorder zapisuje `sort_order`, a UI pokazuje PRIMARY/OFFICIAL/PUBLIC/TYLKO WEWNĘTRZNE,
- `url` może być null dla interview/direct/internal evidence; podany URL musi być poprawnym HTTP(S) zarówno w form validation, jak i w `ContentArticlePublishingService`,
- ordinary Edit `publiclyVisible()` nie może mutować source relationship; regression potwierdza zachowanie istniejącego source,
- `ContentArticleSource::$touches = ['article']` nadal bumpuje parent `updated_at`, ale po PR #50 stale-write nie polega wyłącznie na tym timestampie: deterministyczny edit token obejmuje także raw source state i odrzuca same-second child mutation przed sync,
- source policy przed review/publish wymaga co najmniej jednego source dla news; dla kategorii `przepisy`, jeśli istnieje primary `official`/`legislation`, co najmniej jeden taki primary musi być publicznie cytowalny z poprawnym HTTP(S) URL,
- `is_publicly_cited=false` pozostaje jednoznacznym internal-evidence stanem; publiczny renderer nie istnieje jeszcze, więc N3 nadal musi egzekwować brak publicznego wycieku title/publisher/url,
- osobny computed warning „brak primary source dla prawnego newsa” nie należy do ukończonego editor gate i pozostaje N2-007/N2-010,
- finalny exact-head gate PR #44: `quality` 968 passed / 18 917 assertions / 2 skipped, Pint 1010 files PASS, frontend build PASS; `newsroom-postgres` 7 passed / 89 assertions.

### Zakres

- source repeater/relation,
- primary/official badges,
- validation.

### DoD

- news cannot publish without required source policy,
- source URL może być null dla interview/direct evidence,
- `is_publicly_cited=false` source nie wycieka do publicznego renderera,
- prawny news wymaga publicznie cytowalnego official/legislation URL, jeśli taki primary source istnieje.

---

## NEWSROOM-N2-005 — Relations and topics editor

### Status implementacji

**DONE — zmergowano PR #46 na `main@262d9fab0b171c13a159f7c97670dee3db583b56` po zielonych jobach `quality` i `newsroom-postgres`.**

### Aktualny stan implementacji

- istniejące `ContentArticle::questions()`, `legalUnits()`, `trafficSigns()` i `topics()` są edytowane z article form; nie dodano nowych tabel ani alternatywnego relation modelu,
- questions/legal/signs są ordered Repeaterami, a kolejność jest zapisywana do istniejącego pivot `sort_order`; topics są searchable multi-selectem bez ręcznego rankingu zgodnie z Admin CMS Spec,
- questions/legal/signs/topics korzystają z bounded search, bez preloadu dużych corpusów; UI pokazuje kontekstowe etykiety zamiast surowych internal IDs,
- `NewsroomArticleRelationsEditorAdapter` odrzuca duplicate targets, brakujące rekordy i relation types spoza istniejących allowlist,
- sync zapisuje tylko article-owned pivots i nie mutuje target Question/LegalUnit/TrafficSign ani ich innych grafów/źródeł,
- ordinary Edit `publiclyVisible()` nie może zmieniać relations/topics,
- relation sync nadal bumpuje parent `ContentArticle.updated_at`, a po PR #50 deterministic edit token obejmuje także article-owned relation/topic state; stale relation/topic mutation jest odrzucana przed sync,
- publiczny relation renderer/reverse linking nadal pozostaje dalszym etapem N3/N4,
- finalny exact-head gate PR #46: `quality` 972 passed / 18 940 assertions / 2 skipped, Pint 1011 files PASS, frontend build PASS (9.49 s); `newsroom-postgres` 7 passed / 89 assertions.

### Zakres

- questions searchable picker,
- legal units picker,
- optional traffic signs,
- topics picker/order.

### Performance

- no preload of all questions/legal units.

---

## NEWSROOM-N2-006 — Workflow actions

### Status implementacji

**DONE — workflow/exposure action slice z PR #48 został domknięty przez stale-safe `Apply public update` dla `ContentArticle` w PR #50, zmergowanym na `main@570f884a89869ec44d24f57f0506f4444d20a7d2` po zielonym exact-head CI #190. Publiczne pola nadal nie mają low-level Save; jedyną ścieżką zmiany już publicznego payloadu jest kontrolowany use case `Apply public update`.**

### Aktualny stan implementacji

- Edit i View współdzielą `InteractsWithContentArticleWorkflowActions`; Filament nie duplikuje transition logic z `ContentArticlePublishingService`,
- dostępne są: submit for review, return to draft, mark reviewed, initial schedule, publish now, mark needs review, archive, republish archived po fresh review, withdraw z wymaganym reason oraz restore to review,
- exposure actions obejmują featured + `editorial_priority`, unfeature, breaking z wymaganym przyszłym expiry oraz clear breaking,
- `setFeatured()`, `enableBreaking()` i `clearBreaking()` działają w istniejącym transaction/row-lock boundary i zapisują allowlisted AuditLog z `User` actorem bez body/private notes,
- featured można włączyć tylko dla scheduled/published; breaking tylko dla published news z przyszłym expiry,
- ordinary Edit `publiclyVisible()` nadal nie zapisuje publicznych pól przez zwykły Save; wewnętrzna `editorial_note` pozostaje osobnym zapisem,
- `Apply public update` jest jawnie uruchamianym trybem dla już publicznego artykułu i deleguje atomowy zapis aktualnie zmaterializowanego public editor payloadu do `ContentArticlePublishingService`,
- stale-write guard używa deterministycznego równoważnego tokenu obejmującego rekord, sources oraz article-owned relations/topics; konflikt jest odrzucany przed mutacją, również przy zmianie child state w tej samej sekundzie,
- substantive public change aktualizuje `last_substantive_update_at`, no-op public save nie zmienia tej daty, a AuditLog zapisuje `User` actor i allowlisted metadata bez body/lead/private notes,
- finalny gate domykający N2-006: PR #50 -> `main@570f884a89869ec44d24f57f0506f4444d20a7d2`; exact-head CI #190 PASS (`quality`: 987 passed / 19 088 assertions / 2 skipped, Pint 1015 files PASS, frontend build PASS; `newsroom-postgres` PASS).

### Zakres

- review,
- schedule,
- publish,
- republish archived,
- archive,
- withdraw,
- featured,
- breaking.

### DoD

- actions call services,
- publiclyVisible article nie ma low-level public-field Save; używa Apply public update,
- UI does not duplicate transition logic,
- każda istotna akcja zapisuje istniejący AuditLog z User actorem i bez pełnego body/private notes payload.

---

## NEWSROOM-N2-007 — Publication checklist

### Status implementacji

**DONE — zmergowano przez PR #52 na `main@eb37034090e7233e72cd9452d12fe9876631440a` po exact-head CI #194. `ContentArticlePublicationChecklist` jest wspólnym source of truth dla blocking readiness assertions i read-only checklisty w `ContentArticleResource`; `ContentArticlePublishingService` deleguje do tej samej klasy, więc publish/review/schedule nie mogą ominąć twardych blockerów.**

### Zakres

Computed blocking/warning items.

### DoD

- admin sees precise missing fields,
- publish cannot bypass blocking checklist.

---

## NEWSROOM-N2-008 — Article preview

### Status implementacji

**DONE — zmergowano przez PR #56 na `main@9aa621c083e02e157e72294f5e994ea566e45a9f` po exact-head CI #202. Preview działa wyłącznie przez authenticated administrator-only route, używa prywatnego/no-store transportu, `noindex,nofollow`, nie ładuje public analytics ani shareable signed tokenów i nie otwiera publicznych tras N3.**

### Zakres

- authenticated administrator-only route,
- `Cache-Control: private, no-store`,
- noindex,nofollow,
- public-like renderer,
- preview banner.

### Security tests

- anonymous denied,
- moderator/student/non-admin denied,
- admin allowed,
- brak shareable signed tokenów w v1,
- no sitemap/feed/public analytics exposure.

---

## NEWSROOM-N2-009 — NewsroomHomeComposer + future preview

### Status implementacji

**DONE — zmergowano przez PR #58 na `main@bcb8d783171fc565810a2149b735d86ca6039b00` po exact-head CI #211. Custom Filament `NewsroomHomeComposer` zarządza wyłącznie stałymi slotami zdefiniowanymi w kodzie, pokazuje manual placement + deterministyczny fallback, obsługuje article search i windows `starts_at/ends_at`, ostrzega o duplikatach oraz otwiera admin-only future preview całego układu. Preview używa tego samego `NewsroomHomeCompositionService`, uwzględnia scheduled publishing i pozostaje `private, no-store` + `noindex,nofollow` bez public analytics.**

### Zakres

- fixed slot UI,
- article search,
- starts_at / ends_at,
- fallback visibility,
- duplicate warnings,
- preview entire `/aktualnosci` at selected timestamp.

### DoD

- redaktor nie tworzy nowych layout modules,
- stale-write jest odrzucany,
- overlap zapis chroniony row/advisory lockiem,
- preview jest admin-only/private/no-store i korzysta z tego samego composition service co publiczny hub,
- future preview uwzględnia scheduled publishing.

---

## NEWSROOM-N2-010 — Provenance, regulatory context and media art direction

### Status implementacji

**DONE — zmergowano PR #60 na `main@4936d14d56fa15e59e6dd771e443e93895d3d281` po exact-head CI #217. `quality` zakończył się wynikiem 1015 passed / 19 245 assertions / 2 skipped, Pint 1027 files PASS i frontend build PASS; `newsroom-postgres` zakończył się wynikiem 7 passed / 89 assertions.**

### Aktualny stan implementacji

- `ContentArticleResource` ma kontrolowane pola `origin_type` i `regulatory_status` oraz `effective_from`, `change_summary`, `applies_to` i `exam_impact`; origin/regulatory są widoczne także w tabeli, filtrach i infoliście,
- `ContentArticlePublicationChecklist` i `ContentArticlePublishingService` współdzielą backendowe reguły spójności: `official_source` oraz aktywny kontekst regulacyjny wymagają publicznie cytowanego źródła `official` lub `legislation` z bezpiecznym HTTP(S) URL, a `adopted_future` / `in_force` wymagają `effective_from`,
- brak `change_summary`, `applies_to` lub `exam_impact` przy aktywnym kontekście regulacyjnym jest warningiem, nie obejściem twardych blockerów,
- `NewsroomArticleMediaService` zapisuje hero/OG przez istniejący `NewsroomMediaStorage`; question-specific `AdminMediaUploadService` nie jest używany,
- hero/OG używają immutable managed source paths; backend ponownie sprawdza faktyczne bytes, raster MIME, dimensions i stabilny publiczny URL przed persistence oraz ponownie przy publication readiness,
- zapisane width/height są wyprowadzane z inspekcji rzeczywistego assetu; publication gate odrzuca metadata niezgodne z obiektem storage,
- hero ma focal X/Y jako parę znormalizowanych współrzędnych 0..1; formularz pokazuje CSS crop previews 16:9, 4:3 i 1:1 z focal pointem, bez tworzenia lub deklarowania fizycznych wariantów,
- hero/OG obsługują alt, hero caption i publiczny image credit; `image_license_note` pozostaje wyłącznie backoffice,
- pola N2-010 są częścią istniejącego atomowego stale-safe `Apply public update`; zwykły public Save nadal nie omija tego kontraktu,
- nie dodano migracji, nowego asset modelu, crop/OG variant generatora ani publicznego renderera N3/N4.

### Zakres

- origin_type,
- regulatory_status/effective_from/change_summary/applies_to/exam_impact,
- newsroom media upload zgodny z N0-006,
- focal point control,
- crop previews bez deklarowania nieistniejących fizycznych wariantów,
- publish checklist warnings.

### DoD

- prawny/regulacyjny news ma spójny status i źródło,
- uploader nie używa question-specific AdminMediaUploadService,
- backend sprawdza MIME/size/dimensions i zapisuje stabilny path,
- redaktor widzi efekt cropu przed publikacją,
- origin type jest kontrolowanym enumem.

---

## NEWSROOM-N2-011 — ContentTopicResource

### Zakres

- topic CRUD,
- status,
- description,
- featured article,
- article membership bez ręcznego corpus rankingu,
- SEO metadata.

### DoD

- topic nie powstaje automatycznie z taga,
- draft topic nie jest publiczny,
- publish/republish wymaga własnego opisu + min. 3 actively-distributed/indexable linked articles,
- featured article, jeśli ustawiony, jest actively-distributed + indexable i należy do topicu,
- slug po pierwszej publikacji jest immutable,
- spadek corpus poniżej baseline po publikacji daje warning/wyłączenie z promocji, ale nie automatyczny HTTP flip,
- explicit topic archive usuwa go z sitemap/nav i zwraca 410 dla wcześniej publicznego URL.

---

## NEWSROOM-N2-012 — Admin stale-write + audit identity hardening

### Status implementacji

**DONE — `ContentArticle` stale-write/public-update slice został zamknięty przez PR #50, a brakujący `NewsroomHomeComposer` loaded-token stale-write guard przez PR #58 na `main@bcb8d783171fc565810a2149b735d86ca6039b00` po exact-head CI #211. Oba adminowe write flows odrzucają stale loaded state zamiast wykonywać last-write-wins; placement overlap nadal dodatkowo chroni istniejący row/advisory-lock contract.**

### Aktualny stan implementacji

- Edit zapisuje deterministyczny `_edit_token` zamiast polegać wyłącznie na `updated_at`; token obejmuje raw article state, sources oraz article-owned questions/legal/signs/topics i wykrywa także same-second child mutations,
- draft/internal Save oraz `Apply public update` sprawdzają token pod row lockiem przed jakąkolwiek ręczną synchronizacją sources/relations; konflikt kończy się czytelnym rejectem bez last-write-wins,
- `Apply public update` zapisuje w jednej transakcji aktualnie zmaterializowany public editor payload, uruchamia pełną service validation i rollbackuje article + child mutations przy błędzie,
- `last_substantive_update_at` zmienia się tylko przy semantycznej publicznej zmianie,
- `content_article.public_updated` używa `User` actor i allowlisted metadata; nie zapisuje `body_blocks`, `lead`, `editorial_note` ani source private note,
- nie dodano `published_by` ani `reviewed_by`; `ContentAuthor` pozostaje publiczną tożsamością author/reviewer,
- `NewsroomHomeComposer` używa deterministycznego `ContentHomePlacementEditToken`; update/delete sprawdzają loaded token pod row lockiem, a write service zachowuje tuple advisory locks i overlap validation,
- placement create/update/delete zapisują `User` actor w `AuditLog` z allowlisted metadata oraz utrzymują `created_by_user_id` / `updated_by_user_id`,
- same-second placement mutation i stale delete/update mają regression tests; konflikt nie nadpisuje cudzej zmiany.

### Zakres

- ContentArticle edit zapisuje/porównuje loaded `updated_at` lub równoważny token,
- Apply public update dla publiclyVisible content ma ten sam stale-write guard i pełną service validation,
- konflikt = reject + czytelny komunikat, bez last-write-wins,
- NewsroomHomeComposer ma analogiczny stale-write guard,
- `User` actor trafia do AuditLog; `ContentAuthor` pozostaje author/reviewer identity,
- audit metadata allowlistuje stan/IDs i nie przechowuje body_blocks/lead/private notes.

### DoD

- ciche nadpisanie rekordu jest niemożliwe w testowanym flow,
- brak nowych pól published_by/reviewed_by,
- istniejący AuditLog resource pozostaje źródłem historii operacyjnej.

---

# N3 — Public article

## NEWSROOM-N3-001 — Public catalog service

### Zakres

- findPubliclyVisibleBySlug z route-family guard,
- osobny activelyDistributed query dla hubów/list,
- related data,
- eager load policy.

### DoD

- draft/scheduled/never-published archived hidden,
- previously-published archived detail URL = 200,
- withdrawn resolved explicitly as 410 (or 301 only when redirect successor exists),
- archived excluded z active listings/feed/news sitemap,
- needs_review pozostaje publiczne zgodnie z policy.

---

## NEWSROOM-N3-002 — Article SEO service

### Zakres

- title,
- description,
- self-canonical wyliczony z route family + slug,
- brak CMS canonical override,
- robots,
- OG/Twitter,
- dates.

### DoD

- canonical/og:url/schema url są zgodne,
- stary redirect path nie pozostaje 200,
- guide i newsroom article nie mają konkurencyjnych canonical URLs.

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

- breadcrumbs zależne od route family,
- H1/lead/byline/provenance,
- hero + alt/caption/credit + focal-point crops,
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

### Potwierdzony stan

- `ContentAuthorController` już agreguje Traffic Signs i legal content,
- structured data autora jest dziś budowane przez traffic-sign-specific `TrafficSignSchemaService::author(..., $signs)`,
- obecny ProfilePage Person nie ma jeszcze docelowego stabilnego `/autorzy/{slug}#person` ani `worksFor -> /#organization`,
- `SeoSitemapBuilder::authorUrls()` kwalifikuje autora tylko przez signs/legal content.

### Zakres

Re-use istniejącego `ContentAuthorController` i route, ale wyekstrahować współdzielony author/ProfilePage schema builder zamiast dokładania newsroom semantics do `TrafficSignSchemaService`.

- publiczny profil autora pokazuje activelyDistributed publications oraz osobno/oznaczone archived+indexable publications; needs_review/withdrawn/draft/scheduled są wykluczone,
- ujednolicić ProfilePage mainEntity Person do stabilnego `/autorzy/{slug}#person`,
- Person worksFor -> canonical `/#organization`,
- `authorUrls()` uwzględnia autora także wtedy, gdy jego jedynym publicznym/indexable dorobkiem jest newsroom,
- author sitemap lastmod uwzględnia zmianę outputu profilu wynikającą z publish/archive/needs-review/withdraw/restore przez public-state timestamps, nie techniczny updated_at,
- article graph referuje dokładnie ten sam Person @id.

### DoD

- nie istnieje drugi newsroom author/profile model,
- article -> author URL działa,
- author page -> article działa,
- ProfilePage i Article mają identyczną identity autora,
- archived+indexable pozostaje crawlable przez author profile i jest oznaczone jako archiwalne; archived+noindex nie musi być listowane,
- needs_review+indexable pozostaje w osobnej/oznaczonej sekcji autora jako „w trakcie weryfikacji”, aby zachować inbound bez aktywnej promocji,
- withdrawn/draft/scheduled nie są listowane,
- zmiana public eligibility artykułu aktualizuje author-page/sitemap freshness bez fałszowania article dateModified,
- próba odpublikowania ContentAuthor z zależnymi indexable/publiclyVisible newsroom articles jest blokowana do reassignment/withdraw/noindex.

---

## NEWSROOM-N3-008 — Public rollout config gate

### Cel

Wdrożyć publiczną warstwę bez natychmiastowego przełączania istniejących placeholderów/indeksacji.

### Zakres

- prosty config/env `NEWSROOM_PUBLIC_ENABLED`,
- default bezpieczny dla wdrożenia przed rolloutem,
- gdy wyłączony: admin/dane/private preview mogą działać, nowe public article/category/topic routes nie stają się indeksowalne, a istniejące top-level placeholder behavior nie jest przypadkowo usuwane,
- gate obejmuje również newsroom entries w author profiles/reverse links/feed/sitemaps/IndexNow; nie może istnieć crawlable „tylne wejście” do dark-deployed content,
- włączenie dopiero w N6 release sequence i powoduje cache/distribution refresh.

### DoD

- public switch nie wymaga rollbacku migracji,
- disabled state ma zero public newsroom discovery leakage poza świadomie zachowanym placeholderem,
- test enabled/disabled dla routes + author/reverse links + feed/sitemap/IndexNow,
- config cache/deploy semantics udokumentowane.

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
- activelyDistributed-only chronological list,
- order by first_published_at desc + deterministic tie-breaker,
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

### Potwierdzony stan

`PublicNavigation` ma już primary links „Aktualności” i „Poradniki” z właściwymi prefixami.

### Zakres

- zachować istniejące linki i active states, bez dublowania,
- sprawdzić footer/inne renderery i dodać tylko brakujące, uzasadnione wejścia,
- documentNavigationPrefixes tylko jeśli faktycznie potrzebne.

### DoD

- zero duplicate nav items,
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
- activelyDistributed/indexable topic corpus,
- latest ordering by first_published_at,
- pagination,
- SEO.

### DoD

- only published topics public,
- no automatic tag pages,
- thin/empty topic not launched.

---

## NEWSROOM-N4-008 — Semantic silo / reverse-link integration

### Zakres

Zaimplementować jawny internal-link graph bez tworzenia automatycznej link farmy.

- primary category link dla każdego public article,
- topic links tylko dla jawnych relacji,
- article -> legal/question/sign/public related links,
- article-question edge zapisany wyłącznie w newsroom pivot bez modyfikowania `question_relations` / `question_seo_topics`,
- ograniczone reverse links z legal/question/sign surfaces do wybranych public articles,
- natural/descriptive anchors,
- deterministic related resolver,
- inbound-link/click-depth audit data.

### DoD

- każdy indexable article ma co najmniej jeden crawlable inbound link,
- ważne/evergreen articles są zwykle <= 3 hops od właściwego top-level huba,
- reverse links wynikają z jawnej relacji, mają bounded count i pokazują tylko activelyDistributed targets przy globalnym public gate=true,
- brak draft/noindex/redirect-source targets,
- brak automatycznego sitewide reciprocal linking,
- sitemap nie jest jedyną drogą discovery.

---

# N5 — SEO, distribution and analytics

## NEWSROOM-N5-001 — Extend static generator: articles + hub sitemap coverage + deterministic sharding

Rozszerzyć istniejący `SeoSitemapGenerator` / `SeoSitemapBuilder`, nie tworzyć osobnego generatora.

- wszystkie publiczne indexable articles,
- `/aktualnosci`, `/poradniki`, active categories i published/indexable topics mają jawne sitemap coverage,
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
- sitemap index integration,
- statyczny output generowany przez istniejący generator,
- `news:name` po launch porównane z nazwą publikacji widoczną w Google News.

### Testy

- old updated article nie wraca do news sitemap,
- required namespace/tags,
- publication date timezone/format,
- 1000-entry boundary zgodnie z aktualną specyfikacją.

---

## NEWSROOM-N5-003 — RSS/Atom feed + discovery

- latest items,
- stable GUID/Atom id = dokładnie `urn:prawkonaraz:content-article:{id}` (gdzie `{id}` = `content_articles.id`); identyfikator nie zależy od sluga, canonical URL ani timestampów,
- slug change zmienia item link, ale nie GUID/id i nie tworzy nowego feed item,
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

Re-use existing IndexNow queue/submission pipeline; nie tworzyć drugiego klienta ani traktować lokalnego `event_type` jak pola protokołu.

### DoD

- payload pozostaje zgodny z istniejącym `IndexNowSubmissionService`: `host` + `key` + opcjonalne `keyLocation` + `urlList`; brak własnego created/updated/deleted verb w HTTP payload,
- first publish po commit -> canonical `200` + lokalny `EVENT_CREATED`,
- substantive public update / republish po commit -> canonical `200` + lokalny `EVENT_UPDATED`,
- archive zachowujący detail `200` nie jest lokalnym delete; enqueue updated tylko jeśli publiczna reprezentacja/robots detail page faktycznie się zmieniła,
- withdraw najpierw ustanawia `410`, a dopiero after commit enqueue tego samego URL z lokalnym `EVENT_DELETED`,
- slug change najpierw ustanawia old `301 -> new` i new `200`; after commit enqueue obu URL-i, old jako lokalny `EVENT_UPDATED` (nie deleted), new jako created/updated,
- restore-to-review pozostawia `410` i nie enqueue'uje; republish zgłasza URL dopiero po przywróceniu `200`,
- preview/draft/in_review/scheduled-before-time/noindex nie trafiają do kolejki,
- `NEWSROOM_PUBLIC_ENABLED=false` wyłącza newsroom collector/automation,
- rollback transakcji nie tworzy submission row,
- integracja używa istniejącego IndexNow queue/submission pipeline i jego URL safety filters/dedupe/retry,
- failure does not block article publication/state transaction,
- 200/202 oznacza tylko przyjęcie zgłoszenia, nie gwarancję crawl/index/ranking.

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
- expired breaking,
- index wskazujący brakujący child,
- duplicate/stale obsolete shard po publication switch.

Można dodać newsroom-specific `newsroom:audit-links` dla graph/link checks, ale sitemap rules pozostają w istniejącym audytorze.

---

## NEWSROOM-N5-007 — Static sitemap publication + freshness + delivery hardening

### Zakres

Nie zmieniamy produkcyjnego modelu na runtime generation.

1. najpierw usunąć potwierdzony existing gap: `prepareSitemapDirectory()` nie może delete-all istniejących child XML przed gotowym replacement set, a main index nie może być zapisywany przed childami,
2. rozszerzyć istniejący statyczny generator o newsroom/news,
3. generować komplet payloadów przed publikacją,
4. walidować przed przełączeniem,
5. atomowo podmieniać child files,
6. podmieniać główny `sitemap.xml` dopiero na końcu,
7. stare, nieużywane shardy usuwać po przełączeniu indexu,
8. po commit ustawić tani dirty/version signal zamiast uruchamiać pełny generator w request,
9. dodać częstą scheduler command, która przy dirty signal bierze **shared Redis/distributed lock** i uruchamia refresh; przy wielu scheduler nodes użyć także `onOneServer()` lub równoważnej gwarancji single execution,
10. czyścić marker tylko gdy version nie zmieniła się podczas generacji,
11. istniejący daily `seo:refresh-sitemaps` zachować jako niezależny safety net.

Nie implementować tego jako zwykłego `ShouldQueue`, dopóki produkcja ma `QUEUE_CONNECTION=sync` i brak monitorowanego workera.

### Robots compatibility

- nie usuwać `RobotsController` w tym tasku,
- zweryfikować produkcyjny `/robots.txt` przez HTTP i warstwę Nginx/Cloudflare,
- `public/robots.txt` pozostaje preferowanym kontraktem zgodnie z SEO-SITEMAP-REPAIR-PLAN,
- cleanup duplikatu tylko w osobnym późniejszym PR.

### Topology gate

Przed wdrożeniem częstego refreshu potwierdzić rzeczywistą topologię produkcji:

- single web node + lokalny `public/` -> same-filesystem atomic replace jest wystarczającym modelem,
- multiple web nodes -> wygenerowany set musi trafić atomowo/spójnie do współdzielonego volume, artifact distribution lub edge/origin wspólnego dla wszystkich node'ów,
- `onOneServer()`/Redis lock zapobiega podwójnej generacji, ale **nie synchronizuje lokalnych plików pomiędzy node'ami**.

N5-007 nie jest DONE bez tego dowodu.

### HTTP delivery

Dla statycznych XML/TXT sprawdzić rzeczywistą warstwę serwującą:

- Content-Type,
- public Cache-Control,
- brak Set-Cookie,
- ETag/Last-Modified/304, jeśli wspierane przez Nginx/CDN.

Nie zaliczamy tasku przez dodanie headerów tylko do `SitemapController`.

Feed może mieć validators aplikacyjne osobno.

### DoD

- nie istnieje okno, w którym nowy index wskazuje brakujący child,
- publiczny publish nie czeka synchronicznie na pełną generację sitemap,
- fresh news trafia do statycznego news XML w docelowym SLA kilku minut,
- publish request nie uruchamia pełnego generatora,
- awaria coordinator/scheduled refresh pozostawia dirty state i jest monitorowana, a daily cron zachowuje recovery path,
- istniejące question/sign/legal/author sitemap pozostają bez regresji.

---

# N6 — Production hardening and rollout

## NEWSROOM-N6-000 — Repository merge-gate enforcement

### Potwierdzony stan

GitHub branch `main` jest obecnie niechroniony i nie ma required status checks. G0–G6 są więc proceduralne, dopóki repo rules tego nie egzekwują.

### Gate przed publicznym rolloutem

- włączyć branch protection/ruleset dla `main`,
- wymagać PR zamiast direct push dla normalnej pracy,
- required check co najmniej aktualny `CI / quality`,
- po dodaniu joba PostgreSQL wymagać również newsroom-postgres dla PR-ów, które go uruchamiają albo ustawić workflow tak, by stabilny required check agregował oba,
- nie wymagać manual `Browser Smoke` jako statusu, jeśli workflow_dispatch nie daje stabilnego required context; newsroom E2E pozostaje jawny release evidence do czasu automatyzacji.

Nie zmieniamy ustawień repo w ramach docs PR; to osobny operational action.

---

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
- verify dirty/version coordinator + scheduled sitemap/feed refresh,
- verify news sitemap artifact becomes fresh without waiting for next daily cron.

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
- rzeczywisty produkcyjny robots response + Sitemap directive,
- statyczny sitemap index/child consistency,
- brak Set-Cookie na statycznym XML/TXT,
- conditional 304 sample na faktycznej warstwie static/CDN, jeśli skonfigurowane,
- feed validators osobno,
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

## NEWSROOM-N6-005 — Security and rollout-gate pass

- XSS,
- preview admin-only/private-no-store,
- admin policy / no access widening,
- stale-write/concurrency,
- AuditLog data minimization,
- upload validation,
- source URL no server-side fetch,
- verify `NEWSROOM_PUBLIC_ENABLED=false` before cutover and controlled enable during release.

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
- brak założenia o ręcznym Publisher Center enrollment jako warunku lub gwarancji Google News.

---

# 4. PR boundaries

Rekomendacja:

### PR A
N0 branding

### PR B
N0 routes/taxonomy/editor format-evolution decision + SEO compatibility docs

### PR C
N1 migrations/enums + additive newsroom-postgres CI job

### PR D
N1 models/factories

### PR E
N1 publishing/slug/scheduler/home composition service

### PR F
N2 categories + topics CMS

### PR G
N2 article CMS + controlled block editor

### PR H
N2 sources/relations/workflow/provenance/media + admin-only preview + stale-write/audit hardening

### PR I
N3 article public + author profile integration + public rollout config gate

### PR J
N4 newsroom hub

### PR K
N4 categories/topics/guides/nav/cache + semantic silo/reverse links

### PR L
N5 article/news sitemap + sharding + feed/discovery + dirty/version refresh coordinator + static delivery

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

- istniejący CI pozostaje szybki na SQLite,
- newsroom migration/domain DB gate musi przejść na PostgreSQL,
- od PR C/N1 wymagany jest additive `newsroom-postgres` job albo równoważny jawny dowód do czasu jego dodania,
- „full CI green” na obecnym SQLite nie zastępuje PostgreSQL gate.

Docs-only:

- link consistency/manual diff.

---

# 6. Definition of Done całego newsroom v1

### Domain

- [x] schema wdrożona
- [x] models/factories
- [x] publishing service
- [x] scheduling
- [ ] public HTTP redirects / withdrawn 410 disposition

### CMS

- [x] article resource
- [x] category resource
- [ ] topic resource
- [x] controlled block editor
- [x] sources
- [x] relations
- [ ] origin/regulatory fields
- [ ] focal point/crop preview
- [x] article preview
- [x] home composer + future preview
- [x] checklist
- [x] workflow — transition/exposure actions + stale-safe `Apply public update` dla publicznego `ContentArticle`
- [x] admin-only authorization bez rozszerzenia panel access
- [x] stale-write rejection — `ContentArticle` i `NewsroomHomeComposer`
- [x] AuditLog User actor / ContentAuthor identity separation

### Public

- [ ] article + controlled block renderer
- [ ] regulatory context box/provenance
- [ ] author profile/newsroom publication integration
- [ ] semantic silo + controlled reverse links
- [ ] newsroom hub with placements/fallback/dedupe
- [ ] category
- [ ] topic/dossier
- [ ] guides
- [ ] responsive/accessibility

### SEO

- [ ] stable entity graph + site identity
- [ ] visible/schema dates consistency
- [ ] self-canonical + route-family exclusivity
- [ ] article sitemap przez istniejący static generator + deterministic sharding readiness
- [ ] news sitemap full required metadata + dirty/version scheduled refresh bez queue-worker assumption
- [ ] child-before-index atomic static publication
- [ ] istniejący SeoSitemapAuditor rozszerzony bez drugiego auditora
- [ ] rzeczywisty static/Nginx/CDN delivery smoke (Content-Type/cache/Set-Cookie/validators)
- [ ] feed + discovery + własny cache/validator contract
- [ ] author ProfilePage / publisher / WebSite

### Operations

- [ ] NEWSROOM_PUBLIC_ENABLED controlled rollout/rollback gate
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

## R6 — news sitemap jest nieświeża po publikacji

Mitigation: after-commit dirty/version signal + frequent scheduled coordinator/lock + monitoring + istniejący daily seo:refresh-sitemaps jako safety net. Nie opierać v1 na QUEUE_CONNECTION=sync.

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

## R15 — crawler odpala runtime XML albo dostaje niewłaściwą warstwę cache

Mitigation: zachować statyczny production pipeline z SEO-SITEMAP-REPAIR-PLAN; validators sprawdzać na Nginx/CDN/static delivery, nie tylko w Laravel controller.

## R16 — sitemap index wskazuje child, który nie został jeszcze opublikowany

Potwierdzony stan: obecny generator usuwa child XML przed zapisem i zapisuje main index przed child files.

Mitigation: refactor istniejącego generatora do generacji/validacji pełnego next set, child files first, main index last, obsolete shard cleanup dopiero po switch; dopiero potem zwiększać frequency refresh.

## R17 — cleanup robots/sitemap controllerów psuje istniejący production routing

Mitigation: newsroom nie usuwa istniejących controller routes; source-of-truth cleanup jest osobnym PR po production HTTP/CDN verification.

## R18 — newsroom nadpisuje istniejący question graph

Mitigation: content_article_question jest osobnym edge; brak write path do question_relations/question_seo_topics w newsroom taskach.

## R19 — admin workflow rozszerza dostęp do Filament albo miesza User z ContentAuthor

Mitigation: v1 admin-only; User=actor, ContentAuthor=author/reviewer; policy + audit tests.

## R20 — równoległe edycje nadpisują treść/placement

Mitigation: stale-write reject na article/home composer + row/advisory lock dla overlap invariant.

## R21 — SQLite CI maskuje błąd PostgreSQL migration/lock

Mitigation: additive newsroom-postgres CI job od PR C.

## R22 — block schema evolution łamie rollback/odczyt starych artykułów

Mitigation: versioned/compatible block contract; renderer-first; data migration plan przed breaking format change.

## R23 — publiczny moduł zostaje włączony przed SEO/smoke gate

Mitigation: NEWSROOM_PUBLIC_ENABLED i jawny N6 cutover.

## R24 — archive jest używane jako takedown, ale URL nadal zwraca 200

Mitigation: osobny withdrawn workflow z reason + AuditLog + 410; archive pozostaje historycznym 200.

## R25 — historyczny slug zostaje przejęty przez inny artykuł

Mitigation: full-path reservation przeciw `content_article_redirects.from_path` + service-only same-article reclaim + one-hop redirect rewrite.

## R26 — freshness cron wyłącza promocję artykułów tylko dlatego, że minął termin

Mitigation: overdue jest computed work-queue state; `needs_review` to osobna audytowana decyzja.

## R27 — newsroom próbuje użyć question-specific media uploader

Mitigation: N0-006 media contract + newsroom adapter/service; reuse resolver/storage conventions, nie QuestionMedia workflow.

## R28 — autor zostaje odpublikowany i psuje publiczne article schema/profile

Mitigation: guard ContentAuthor unpublish przy zależnych public/indexable newsroom articles.

## R29 — po launch feature flag false masowo tworzy 404

Mitigation: false służy do dark deploy; po indeksacji temporary technical rollback używa 503/Retry-After lub code rollback, a content takedown używa withdrawn.

## R30 — zwykły Save zmienia live content przed review

Przy braku revision/staging systemu jeden rekord jest jednocześnie publiczną wersją. Mitigation: public fields publiclyVisible article zapisuje wyłącznie atomowy `Apply public update`; zwykły draft Save jest niedostępny. Review-before-live późniejszych zmian wymaga osobnego staging/revision scope.

## R31 — scheduled republish publicznego artykułu powoduje chwilowe zniknięcie URL

Mitigation: v1 schedule tylko initial publish; istniejący publiczny artykuł aktualizujemy przez Apply public update.

## R32 — needs_review staje się SEO orphanem

Mitigation: jeśli nadal indexable, detail page ma publiczny review banner i crawlable inbound co najmniej z oznaczonej sekcji profilu autora.

## R33 — nadpisany media object zostawia stale OG/CDN cache

Mitigation: immutable/unique newsroom media paths; replacement = nowy path, nie overwrite.

## R34 — sitemap wygenerowana na jednym node nie istnieje na pozostałych

Mitigation: topology gate N5-007; shared filesystem/artifact distribution albo potwierdzony single-node.

## R35 — wymagane gate'y można ominąć direct push do main

Mitigation: N6-000 branch protection/ruleset + required CI checks przed public rollout.

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
- foundation N0 oraz pełny etap N1-001..N1-006 są wdrożone; N2 ma już `ContentCategoryResource`, `ContentArticleResource`, kontrolowany Builder/RichEditor dla `body_blocks`, source relationship editor, article-owned relations/topics editor, pełne N2-006 workflow actions, stale-safe `Apply public update`, N2-007 publication checklist, N2-008 private Article preview oraz N2-009 `NewsroomHomeComposer` + future preview; N2-012 stale-write/audit identity hardening jest DONE, natomiast `ContentTopicResource`, media/origin-regulatory UI i publiczny newsroom nadal nie są wdrożone,
- `/aktualnosci` i `/poradniki` nadal renderują pre-launch placeholder, teraz z dedykowanym `X-Robots-Tag: noindex, follow`; finalne detail/category/topic/feed route namespaces są zarejestrowane, ale pozostają 404 bez publicznych controllerów,
- fundamenty ContentAuthor/legal/traffic signs/public SEO istnieją,
- istnieją config/content.php organization, SchemaIds/SchemaRenderer oraz współdzielony SiteIdentitySchema; homepage i istniejące główne publiczne graph services korzystają z kanonicznego Organization/WebSite identity,
- istnieją public/robots.txt i RobotsController; newsroom nie zmienia tej warstwy bez osobnego production-delivery audit,
- HomePageController nie hardcoduje już legacy „Orły na Drodze”; homepage korzysta z kanonicznego site identity, og:site_name i stabilnych graph IDs,
- newsroom dirty/version refresh coordinator, atomic child-before-index publication i newsroom/news sitemap output jeszcze nie istnieją,
- canonical CI zachowuje szybki SQLite job `quality` i ma addytywny `newsroom-postgres` job uruchamiający komplet `tests/Postgres` dla migration/FK/index/rollback oraz model/scope contracts,
- QUEUE_CONNECTION w env example jest sync; stały queue worker nie jest gwarantowany,
- panel Filament jest obecnie admin-only i ten kontrakt pozostaje wymaganiem v1.

---

# 11. Pierwszy następny task

NEWSROOM-N2-010 — Provenance, regulatory context and media art direction.

N2-009 oraz brakująca HomeComposer część N2-012 są zamknięte po PR #58. Następny krok podłącza istniejące pola provenance/regulatory do CMS i publish validation oraz materializuje newsroomowy hero/OG upload na `NewsroomMediaStorage` z focal-point/crop preview, bez deklarowania fizycznych wariantów, których pipeline jeszcze nie generuje. Publiczny N3 nadal pozostaje osobnym etapem.

---

# 12. Historia zmian

### 2026-09-16 — v0.28

- PR #60 zmergowano na `main@4936d14d56fa15e59e6dd771e443e93895d3d281`; exact-head CI #217: `quality` PASS (1015 passed / 19 245 assertions / 2 skipped, Pint 1027 files PASS, frontend build PASS) oraz `newsroom-postgres` 7 passed / 89 assertions,
- NEWSROOM-N2-010 jest **DONE**: `ContentArticleResource` ma kontrolowane provenance/regulatory fields, hero/OG upload oparty o `NewsroomArticleMediaService` + istniejący `NewsroomMediaStorage`, focal X/Y 0..1 oraz CSS crop previews 16:9 / 4:3 / 1:1,
- backend wymaga spójności `official_source` / aktywnego regulatory statusu z publicznie cytowanym official/legislation HTTP(S) source; `adopted_future` i `in_force` wymagają `effective_from`,
- hero/OG są ponownie inspectowane przed persistence i publication readiness, a zapisane dimensions muszą odpowiadać faktycznemu managed assetowi; `image_license_note` pozostaje backoffice-only,
- nie dodano migracji, asset modelu, fizycznego crop/OG variant generatora ani publicznego N3/N4 renderera,
- następnym taskiem wykonawczym N2 jest NEWSROOM-N2-011 `ContentTopicResource`.

### 2026-09-16 — v0.27

- PR #58 zmergowano na `main@bcb8d783171fc565810a2149b735d86ca6039b00`; exact-head CI #211: `quality` PASS (1006 passed / 19 194 assertions / 2 skipped, Pint 1024 files PASS, frontend build PASS w 11.05 s) oraz `newsroom-postgres` PASS,
- dodano custom Filament `NewsroomHomeComposer` z fixed slots: lead, 4 secondary, aktywne category leads, guides lead i 6 important-now; redaktor nie może tworzyć nowych typów slotów ani zmieniać gridu,
- composer pokazuje aktywny manual placement, jego window oraz deterministyczny fallback liczony przez ten sam `NewsroomHomeCompositionService` z manual placements wyłączonymi do porównania; article search jest bounded, a duplikaty są jawnie ostrzegane,
- prywatny `/admin/newsroom/home-preview` renderuje composition dla wybranego czasu, uwzględnia future scheduled publishing, nie mutuje workflow i wymusza admin-only + private/no-store + noindex/nofollow + brak public analytics,
- `ContentHomePlacementEditToken` domyka N2-012: stale update/delete są odrzucane pod row lockiem, tuple advisory-lock/overlap contract pozostaje aktywny, a create/update/delete audytują `User` actor z allowlisted metadata,
- NEWSROOM-N2-009 i NEWSROOM-N2-012 są **DONE**; następnym taskiem jest NEWSROOM-N2-010 Provenance, regulatory context and media art direction.

### 2026-09-16 — v0.26

- PR #56 zmergowano na `main@9aa621c083e02e157e72294f5e994ea566e45a9f`; exact-head CI #202: `quality` PASS (997 passed / 19 146 assertions / 2 skipped, Pint 1020 files PASS, frontend build PASS) oraz `newsroom-postgres` PASS,
- dodano authenticated administrator-only article preview pod prywatnym route; anonymous jest kierowany do auth, a non-admin otrzymuje 403,
- response wymusza `Cache-Control: private, no-store`, `X-Robots-Tag: noindex, nofollow`, meta robots `noindex,nofollow` i nie ładuje Google Analytics/consent UI,
- preview renderuje zapisany `body_blocks` przez allowlisted rich-text renderer, pokazuje tylko publicznie cytowalne sources i nie ujawnia private evidence/notatek; moduły zależne od finalnego N3 pozostają jawnie oznaczonymi placeholderami,
- publiczne detail routes newsroomu/poradników nadal pozostają 404; N2-008 nie materializuje N3,
- NEWSROOM-N2-008 jest **DONE**; następnym taskiem jest NEWSROOM-N2-009 NewsroomHomeComposer + future preview, wraz z domknięciem HomeComposer części N2-012.

### 2026-09-16 — v0.25

- zmergowano PR #52 na `main@eb37034090e7233e72cd9452d12fe9876631440a`; exact-head CI #194: `quality` PASS (992 passed / 19 114 assertions / 2 skipped, Pint 1017 files PASS, frontend build PASS) oraz `newsroom-postgres` PASS,
- dodano `ContentArticlePublicationChecklist` jako wspólny read-model + source of truth dla review/publication/fresh-review assertions; `ContentArticlePublishingService` deleguje do tej samej klasy zamiast utrzymywać drugi zestaw reguł,
- `ContentArticleResource` pokazuje read-only listę `OK` / `OSTRZEŻENIE` / `BLOKUJE` z precyzyjnymi domenowymi powodami,
- warningi takie jak brak hero, manual SEO description, powiązanych pytań i dedykowanego OG assetu nie blokują publish; twarde domain invariants nadal blokują review/schedule/publish,
- dedykowany OG asset różny od hero bez własnego alt pozostaje blockerem zgodnie z domain/media contract,
- NEWSROOM-N2-007 jest **DONE**; następnym taskiem jest NEWSROOM-N2-008 Article preview.

### 2026-09-16 — v0.24

- zmergowano PR #50 na `main@570f884a89869ec44d24f57f0506f4444d20a7d2`; exact-head CI #190: `quality` PASS (987 passed / 19 088 assertions / 2 skipped, Pint 1015 files PASS, frontend build PASS) oraz `newsroom-postgres` PASS,
- dodano deterministyczny `ContentArticleEditToken`, który obejmuje article + sources + article-owned relations/topics i nie zależy wyłącznie od sekundowej precyzji `updated_at`,
- ordinary draft/internal Save oraz public `Apply public update` odrzucają stale loaded state przed child sync; same-second source mutation ma test regresyjny i nie może zostać nadpisana,
- `Apply public update` zapisuje atomowo aktualnie zmaterializowany public editor payload, rollbackuje przy failed validation, aktualizuje `last_substantive_update_at` tylko dla semantycznej zmiany i audytuje przez `User` bez body/private notes,
- NEWSROOM-N2-006 jest teraz **DONE**; NEWSROOM-N2-012 pozostaje **PARTIAL** tylko dla analogicznego stale-write guard w przyszłym `NewsroomHomeComposer` z N2-009,
- pierwszym kolejnym taskiem wykonawczym jest NEWSROOM-N2-007 Publication checklist.

### 2026-09-16 — v0.23

- zmergowano PR #48 na `main@88533b04d74a839c3bbccf86b707909ccdd235f8`; exact-head CI #176 zakończył się PASS dla `quality` i `newsroom-postgres`,
- zmaterializowano współdzielone Edit/View workflow actions: review/draft, mark reviewed, initial schedule, publish, needs-review, archive, republish, withdraw/restore,
- dodano audytowane exposure actions dla featured/editorial priority oraz breaking/expiry; Filament deleguje do `ContentArticlePublishingService` zamiast kopiować transition logic,
- ordinary public Edit pozostaje server-side locked i nie uzyskał low-level public Save,
- NEWSROOM-N2-006 pozostaje **PARTIAL**, ponieważ jego DoD wymaga `Apply public update`; atomowy public payload write + stale loaded-token rejection nadal należą do NEWSROOM-N2-012,
- następnym wykonawczym taskiem jest NEWSROOM-N2-012, aby domknąć zależność N2-006 bez fałszywego oznaczania go jako DONE.

### 2026-09-16 — v0.22

- zamknięto NEWSROOM-N2-005 po merge PR #46 na `main@262d9fab0b171c13a159f7c97670dee3db583b56`,
- zmaterializowano article-owned questions/legal/signs/topics editor bez nowych migracji; existing pivots pozostają source of truth,
- questions/legal/signs zapisują redakcyjną kolejność jako `sort_order`, podczas gdy topics zgodnie z architekturą nie otrzymują drugiego ręcznego rankingu,
- bounded searchable pickers nie preloadują dużych corpusów, a contextual labels nie wymagają wybierania po samym internal ID,
- `NewsroomArticleRelationsEditorAdapter` waliduje duplicate/missing targets oraz relation-type allowlists i synchronizuje tylko article-owned pivots,
- ordinary public Edit nie zmienia relations/topics; sync bumpuje parent `updated_at`, ale pełny stale-write reject pozostaje N2-012,
- publiczny relation renderer/reverse-link surface nadal pozostaje N3/N4,
- finalny exact-head gate PR #46: `quality` 972 passed / 18 940 assertions / 2 skipped, Pint 1011 files PASS, frontend build PASS (9.49 s); `newsroom-postgres` 7 passed / 89 assertions,
- następnym taskiem jest NEWSROOM-N2-006 Workflow actions.

### 2026-09-16 — v0.21

- zamknięto NEWSROOM-N2-004 po merge PR #44 na `main@fd2042f22532b7b7c14dc993e532c0887876e159`,
- dodano relationship Repeater źródeł do istniejącego article form z `defaultItems(0)`, reorder przez `sort_order` i jawnymi statusami source,
- źródło może być prywatnym interview/direct evidence bez URL; niepusty URL jest ograniczony do poprawnego HTTP(S) również przez backend service gate,
- review/publish news wymaga source, a primary official/legislation dla `przepisy` musi mieć co najmniej jeden publicznie cytowalny HTTP(S) URL,
- ordinary public Edit nie zmienia source relationship; source child bumpuje parent `updated_at`, lecz pełny stale-write reject nadal pozostaje N2-012,
- public citation renderer i computed warning o braku primary source dla prawnego newsa nie są fałszywie oznaczone jako ukończone,
- finalny exact-head gate PR #44: `quality` 968 passed / 18 917 assertions / 2 skipped, Pint 1010 files PASS, frontend build PASS; `newsroom-postgres` 7 passed / 89 assertions,
- następnym taskiem jest NEWSROOM-N2-005 Relations and topics editor.

### 2026-09-16 — v0.20

- zamknięto NEWSROOM-N2-003 po merge PR #42 na `main@13a22058c7c945196e8d490a0620b93dcf449641`,
- dodano kontrolowany Filament Builder/RichEditor do istniejącego `ContentArticleResource` oraz `NewsroomBodyEditorAdapter` do round-trip canonical `body_blocks`,
- canonical `key` jest przechowywany jako jawne pole bloku i nie jest utożsamiany z efemerycznym UUID Buildera,
- zapis body nadal jest autorytatywnie walidowany przez `NewsroomBodyContract`; RichEditor używa TipTap JSON, unsafe HTML/URLs/iframe i disabled embed failują zamknięcie,
- image block nie udaje ukończonego media pipeline: upload/object verification/crop nadal są otwarte,
- ordinary public Edit zachowuje server-side body/public-field lock,
- finalny gate PR #42: `quality` 963 passed / 18 895 assertions / 2 skipped, Pint 1010 files PASS, frontend build PASS; `newsroom-postgres` PASS,
- następnym taskiem jest NEWSROOM-N2-004 Sources editor.

### 2026-09-16 — v0.19

- zamknięto NEWSROOM-N2-002 po merge PR #40 na `main@5a4f92e08c8618ff70270abb683f97bd88d02700`,
- dodano Filament `ContentArticleResource` z index/create/view/edit, podstawowym form/infolist/table, wymaganym search/filter setem i eager loadingiem relacji redakcyjnych,
- create oraz draftowe zmiany type/sluga reużywają `ContentArticleSlugService`; historyczne path reservations i reserved slug contract nie są omijane przez Filament,
- admin-only panel contract pozostaje bez zmian; feature regression potwierdza odmowę dla moderatora/studenta oraz rozdzielenie AuditLog `User` actor od `ContentAuthor`,
- zwykły Edit publicznego rekordu ma UI i server-side blokadę publicznych pól; w shellu osobno zapisuje się wyłącznie `editorial_note`,
- Builder/RichEditor, sources/media, workflow actions, stale-write, preview i HomeComposer pozostają otwarte,
- finalny gate PR #40: `quality` 951 passed / 18 857 assertions / 2 skipped, Pint 1008 files, frontend build PASS; `newsroom-postgres` 7 passed / 89 assertions,
- następnym taskiem jest NEWSROOM-N2-003 Block editor and sanitization.

### 2026-09-16 — v0.18

- zamknięto NEWSROOM-N2-001 po merge PR #37 na `main@c103dda20c96b75f21413f72c684f774d081a6d0`,
- dodano Filament `ContentCategoryResource` z index/create/view/edit, form/infolist/table i admin-only access przez istniejący panel gate,
- wdrożono trzy article counts, filtrowanie active oraz reorder po `position`,
- category slug jest route-compatible i immutable na poziomie modelu; edit UI nie zapisuje sluga,
- delete używanej kategorii oraz deactivation kategorii z publicznym/aktywnie dystrybuowanym corpusem są blokowane modelowo; edit page powierzchniowo zwraca validation error dla deactivation,
- regression tests potwierdzają także, że stale preloaded counts nie omijają save/delete guards,
- finalny gate PR #37: `quality` 945 passed / 18 806 assertions / 2 skipped, Pint 999 files, frontend build PASS; `newsroom-postgres` 7 passed / 89 assertions,
- duplikujący zakres PR #38 został zamknięty; następnym taskiem jest NEWSROOM-N2-002 ContentArticleResource shell.

### 2026-09-16 — v0.17

- zamknięto NEWSROOM-N1-006 po merge PR #35 na `main@eb13b2160e4b8d49c128869ad4761eb0875b2dac`,
- dodano `NewsroomHomeCompositionService` z manual-placement-first resolution, future-preview eligibility, deterministycznym fallbackiem, global dedupe i context-aware category/guides modules,
- dodano `NewsroomHomePlacementService` z kontrolowanymi slotami/context oraz overlap validation pod transaction advisory/row lockiem,
- `ContentArticlePublishingService::assertScheduledPreviewReady()` reużywa publication/fresh-review/breaking invariants bez mutowania stanu,
- breaking strip pozostaje jedynym jawnym wyjątkiem od globalnego card dedupe,
- PostgreSQL test potwierdza serializację konkurencyjnego zapisu pustego placement tuple,
- finalny gate PR #35: `quality` 935 passed / 18 769 assertions / 2 skipped, Pint 990 files, frontend build PASS; `newsroom-postgres` 7 passed / 89 assertions,
- N1 jest zamknięte; następnym taskiem wykonawczym jest NEWSROOM-N2-001 ContentCategoryResource.

### 2026-09-16 — v0.16

- zamknięto NEWSROOM-N1-005 po merge PR #33 na `main@7157b60b643fa57e38c111a26a42cf70b5715024`,
- dodano `newsroom:publish-due` dla initial scheduled publish oraz produkcyjną rejestrację co minutę z `withoutOverlapping()`,
- due query ogranicza się do statusu `scheduled` i `scheduled_for <= now()`; publikacja deleguje do `ContentArticlePublishingService`, więc ponownie przechodzi pełny publication/fresh-review contract pod row lockiem,
- failure jednego due rekordu nie blokuje kolejnych; rekord pozostaje nieopublikowany, dostaje deduplikowany AuditLog + warning log, a batch zwraca non-zero po zakończeniu, jeśli failures pozostały,
- concurrent/already-transitioned rekord po refetchu jest idempotentnym skipem,
- scheduled republish wcześniej publicznego artykułu jest blokowany także w samym publishing service,
- finalny gate PR #33: `quality` 922 passed / 18 734 assertions / 2 skipped, Pint 985 files, frontend build PASS; `newsroom-postgres` 6 passed / 86 assertions,
- następnym taskiem wykonawczym jest NEWSROOM-N1-006 Home composition service.

### 2026-09-16 — v0.15

- zamknięto NEWSROOM-N1-004 po merge PR #30 na `main@701c9eb41003bd0d6a18c417f1051ccc6372668b`,
- dodano `ContentArticlePublishingService` oraz after-commit `ContentArticleWorkflowTransitioned`,
- wdrożono audytowane, transakcyjne przejścia review/schedule/publish/needs_review/archive/withdraw/restore/republish wraz z required invariants i timestamp semantics,
- transition poza aktywny published distribution czyści breaking state; `first_published_at` pozostaje stabilny,
- withdrawal wymaga reason i utrzymuje tombstone przez restore-to-review aż do skutecznego reviewed publish,
- AuditLog używa `User` actora niezależnie od `ContentAuthor`, a metadata nie przechowuje pełnego body/lead,
- rollback test potwierdza brak workflow eventu przed outer commit i brak eventu po rollbacku,
- publiczne HTTP 410/301/200 nadal nie istnieje; withdrawn HTTP disposition pozostaje N3,
- `applyPublicUpdate` pozostaje N2 orchestration; scheduler command pozostaje N1-005,
- finalny gate PR #30: `quality` 917 passed / 18 656 assertions / 2 skipped, Pint 983 files, frontend build PASS; `newsroom-postgres` 6 passed / 86 assertions,
- następnym taskiem wykonawczym jest NEWSROOM-N1-005.

### 2026-09-16 — v0.14

- zamknięto NEWSROOM-N1-003 po merge PR #28 i zielonych jobach `quality` + `newsroom-postgres`,
- dodano `ContentArticleSlugService`, `ContentArticleRedirect`, `ContentArticlePathResolver` i współdzielony `PostgresTransactionAdvisoryLock`,
- opublikowane slug changes utrzymują one-hop history, same-article reclaim jest service-only, a historyczny full path innego artykułu pozostaje reserved,
- route-family transition jest blokowany po pierwszej publikacji, natomiast same-family published type change pozostaje dozwolony,
- PostgreSQL concurrency jest realnie testowane drugim połączeniem; finalny gate: 6 testów / 86 asercji,
- ogólny gate: 906 passed / 18 565 assertions / 2 skipped, Pint 980 files, frontend build PASS,
- publiczny HTTP 301/200 nie jest jeszcze wdrożony; N3 controllers mają konsumować `ContentArticlePathResolver`,
- następnym taskiem wykonawczym jest NEWSROOM-N1-004.

### 2026-09-16 — v0.13

- zamknięto NEWSROOM-N1-002 po merge PR #26,
- dodano 6 modeli Eloquent newsroomu wraz z relacjami, reverse relations, casts i domenowymi scopes/predicates,
- `publiclyVisible`, `activelyDistributed` i `indexable` są odrębnymi kontraktami query/model zgodnie z Domain Spec; archive/needs_review nie są aktywnie dystrybuowane,
- dodano factories dla wszystkich modeli N1-002; `ContentArticleFactory` ma wymagane jawne stany `draft/inReview/scheduled/published/breaking/needsReview/archived` i reużywa `NewsroomBodyContract`,
- `newsroom-postgres` uruchamia teraz cały katalog `tests/Postgres`; finalny PostgreSQL gate: 4 testy / 81 asercji PASS,
- finalny `quality`: 895 passed / 18 532 assertions / 2 skipped, Pint 974 files PASS, frontend build PASS,
- dedykowany produkcyjny seeder kategorii nadal nie istnieje; slug/redirect service, publishing, scheduler, home composition, CMS i public renderer pozostają kolejnymi zakresami,
- następnym taskiem wykonawczym jest NEWSROOM-N1-003.

### 2026-09-16 — v0.12

- zamknięto NEWSROOM-N1-001 po merge PR #24,
- dodano 5 enumów domenowych i 12 migracji zgodnych z Data Model/Domain,
- `content_articles` materializuje canonical body/regulatory/media/freshness/public-state contract bez `canonical_url` i bez `featured_position`,
- dodano SQLite schema regression oraz izolowany PostgreSQL migration contract,
- CI zachowuje istniejący szybki `quality` i dodaje `newsroom-postgres` na PostgreSQL 16,
- PostgreSQL gate potwierdził 3 testy / 64 asercje: migrate fresh, indeksy/FK delete rules i rollback 12 newsroom migrations,
- finalny ogólny CI, Pint i frontend build przeszły,
- modele/factories/scopes pozostają N1-002; nie opisano ich jako istniejących.

### 2026-09-16 — v0.11

- zamknięto NEWSROOM-N0-006 po merge PR #22 i green CI,
- dodano dedykowany `NewsroomMediaStorage` zamiast reużycia question-specific `AdminMediaUploadService`,
- storage contract generuje immutable ULID source paths, korzysta ze wspólnych media limits/resolvera i sprawdza rzeczywisty stored MIME/bytes/dimensions,
- JPEG/PNG/WebP/AVIF są baseline, SVG/non-raster/path escape/mismatch metadata są odrzucane,
- publiczny URL przechodzi przez `MediaUrlResolver`; kontrakt nie przechowuje signed/temporary URL,
- nie zadeklarowano nieistniejących crop variants; N2 hero uploader/focal-point UI i crop generation pozostają otwarte,
- code gate PR #22: 883 passed / 18 419 assertions / 2 skipped, Pint 939 files PASS, frontend build PASS,
- foundation N0 jest domknięte; kolejnym taskiem wykonawczym jest NEWSROOM-N1-001.

### 2026-09-16 — v0.10

- zamknięto NEWSROOM-N0-004 / G0-B po merge PR #20 i green CI,
- dodano wykonywalny `NewsroomBodyContract` z body schema v1, canonical list `{key?, type, data}` i fail-closed version policy,
- wybrano Filament Builder jako przyszły adapter N2 oraz RichEditor TipTap JSON dla rich text; nie powstało równoległe `body_html`,
- aktywne typy bloków mają ścisłe payload schemas, a `embed` pozostaje wyłączony do czasu provider/CSP security gate,
- dodano unit/security regression dla unsafe nodes/marks/URLs, XSS escaping, block keys, image pathów, relacji i table shape,
- właściwy N2 CMS, N3 renderer, hero upload endpoint/UI, focal-point UX, crop pipeline i CSP/embed nadal nie są wdrożone,
- `NewsroomMediaStorage` istnieje jako storage/validation foundation, ale nie jest jeszcze podłączony do `ContentArticle`,
- foundation N0 jest domknięte w zakresie wymaganym przed N1; pierwszym następnym taskiem jest N1-001.

### 2026-09-16 — v0.9

- zamknięto NEWSROOM-N0-003 po merge PR #18 i green CI,
- dodano wykonywalny `NewsroomTaxonomyContract` v1 z sześcioma zatwierdzonymi slugami, nazwami publicznymi i kolejnością 10..60,
- testy blokują duplikaty i zmianę kontraktu oraz potwierdzają zgodność slugów z newsroom route regex,
- SEO description/title i opis kategorii pozostają jawnie niezatwierdzone (`null`), zgodnie z wcześniejszym DoD,
- nie utworzono jeszcze `content_categories`, `ContentCategory` ani DB seedera; przyszły N1 seeder ma konsumować kontrakt zamiast go duplikować,
- G0-A routing/taxonomy jest zamknięty; pierwszym następnym taskiem wykonawczym jest N0-004.

### 2026-09-16 — v0.8

- zamknięto NEWSROOM-N0-002 po merge PR #16 i green CI,
- utrwalono finalne route namespaces i ich kolejność przed article catch-all,
- dodano executable `NewsroomRouteContract` z regexem slugów, reserved segments, type -> route family i post-publication cross-family guard,
- pre-launch huby `/aktualnosci` i `/poradniki` zachowują placeholder UX z `X-Robots-Tag: noindex, follow`, bez zmiany innych MarketingPlaceholder routes,
- future feed/category/topic/detail routes są świadomie 404 do czasu wdrożenia publicznych controllerów,
- record-level ContentArticle lookup pozostaje wymaganiem downstream, nie jest deklarowany jako ukończony.

### 2026-09-16 — v0.7

- zamknięto NEWSROOM-N0-001 / G0-C po wdrożeniu wspólnego `SiteIdentitySchema`,
- homepage przeniesiono z legacy „Orły na Drodze” na `config/content.php['organization']` i stabilne `/#organization` + `/#website`,
- dodano spójne `og:site_name`, WebSite alternateName oraz publiczne logo ImageObject z potwierdzonymi wymiarami 256×256,
- istniejące traffic-sign, public-question i legal-content graph services reużywają wspólny Organization/WebSite builder,
- regression tests blokują ponowne rozjechanie publisher/site identity.

### 2026-09-16 — v0.6

- doprecyzowano N5 feed GUID do immutable article ID i dodano regression dla slug change bez zmiany item identity,
- wyrównano N5 IndexNow do istniejącego queue/submission pipeline oraz realnego protokołu bez HTTP event verbów,
- poprawiono transition semantics: withdrawn=410 + local deleted, slug-old=301 + local updated, enqueue wyłącznie after commit.

### 2026-09-16 — v0.5

- zastąpiono liniowy „wszystko N0 blokuje N1” jawną macierzą hard dependencies G0–G6,
- dodano N0-006 media contract, bo istniejący uploader jest question-specific,
- dodano historyczne full-path reservation, author-unpublish guard oraz overdue-vs-needs_review gate,
- N5-007 jawnie zaczyna od naprawy istniejącego delete-all/index-first sitemap window,
- sitemap coverage obejmuje również hub/category/topic URLs,
- dodano obowiązkowy PostgreSQL CI gate bez usuwania szybkiego SQLite CI,
- dodano admin-only auth, User-vs-ContentAuthor audit identity, stale-write i placement concurrency gates,
- preview v1 zamknięto do authenticated admin + private,no-store,
- dodano block schema evolution/rollback compatibility,
- usunięto założenie o queue workerze; N5 używa dirty/version signal + scheduled lock/coalescing,
- dodano deterministyczny archive/public visibility plan i public rollout config flag.

### 2026-09-16 — v0.4

- dodano N0 compatibility contract chroniący istniejący sitemap/robots/question graph backend,
- zablokowano cross-route-family type changes po first publish,
- usunięto canonical override i featured_position z planowanego v1,
- historycznie rozszerzono N5 o istniejący statyczny SeoSitemapGenerator, atomowy publication switch i async/debounced refresh; **refresh model zastąpiono później** dirty/version + scheduler/lock po audycie realnego queue contract,
- przeniesiono HTTP validator verification na faktyczną warstwę static/Nginx/CDN,
- robots cleanup oddzielono od newsroom implementation, aby nie ryzykować regresji serwisu.

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
