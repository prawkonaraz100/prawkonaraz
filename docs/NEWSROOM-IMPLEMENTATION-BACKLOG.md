# Newsroom Implementation Backlog

## 1. Status

- Status: Canonical implementation plan and live implementation status
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Specyfikacje wykonawcze:
  - [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md)
  - [NEWSROOM-ADMIN-CMS-SPEC.md](./NEWSROOM-ADMIN-CMS-SPEC.md)
  - [NEWSROOM-PUBLIC-UI-UX-SPEC.md](./NEWSROOM-PUBLIC-UI-UX-SPEC.md)
  - [NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md](./NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md)
  - [NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md](./NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md)
- Data: 2026-09-17
- Cel: rozbić newsroom na małe, weryfikowalne PR-y z jasnymi zależnościami i Definition of Done.

---

## 2. Zasada wykonywania

Każdy task przechodzi przez ten sam kontrakt dowodowy, ale kod i dokumentacja mogą być rozdzielone na dwa PR-y, jeżeli implementation PR ma jawny guardrail „no documentation changes”.

### Wariant A — kod i dokumentacja w jednym PR

1. implementacja,
2. testy lokalne pomocniczo,
3. review faktycznie zmienionego kodu,
4. aktualizacja tylko powiązanej dokumentacji,
5. pełny GitHub Actions Quality Gate na finalnym HEAD zawierającym kod i dokumentację,
6. merge,
7. post-merge weryfikacja `main`,
8. dopiero kolejny task.

### Wariant B — implementation PR + osobny docs-sync PR

1. implementacja bez dokumentacji zgodnie z guardrailem PR,
2. review faktycznie zmienionego kodu,
3. pełny GitHub Actions Quality Gate na exact final implementation HEAD,
4. merge implementation PR,
5. post-merge pełny Quality Gate na `main`,
6. dopiero po jego PASS osobny docs-sync PR aktualizujący wyłącznie dokumenty związane z faktycznie wdrożonym zakresem,
7. pełny GitHub Actions Quality Gate na exact final docs-sync HEAD,
8. merge docs-sync PR i post-merge weryfikacja `main`,
9. dopiero wtedy task może zostać oznaczony jako w pełni zamknięty dokumentacyjnie i można przejść dalej.

Nie łączymy kilku dużych etapów w jeden PR. Starszy PASS nie jest dowodem dla HEAD zmienionego później; finalny status zawsze odnosi się do konkretnego SHA.

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
- route contract nie przesądza o bieżącym publicznym statusie downstream controllerów; stan detail routes opisuje sekcja N3 i rozdział 10,
- `/aktualnosci` przy gate=false zachowuje pre-launch placeholder UX z `X-Robots-Tag: noindex, follow`, a przy gate=true po N4-002 renderuje SSR `newsroom.home`; od N4-003 `public.news.categories.show` przy gate=true renderuje SSR category page, a przy gate=false failuje do 404; od N4-004 `/poradniki` przy gate=true renderuje SSR `newsroom.guides`, a przy gate=false zachowuje placeholder/noindex,
- shared `Public/MarketingPlaceholder` innych sekcji nie został globalnie zmieniony,
- `NewsroomRouteContract` implementuje type -> route family, canonical path, reserved slug policy i guard cross-family type transition po pierwszej publikacji.

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

N0-002 ustanowiło bazowy pre-launch noindex bez zmiany route contract. NEWSROOM-N3-008 później zmaterializowało `NEWSROOM_PUBLIC_ENABLED`, a NEWSROOM-N4-002 konsumuje ten sam gate dla top-level `/aktualnosci`; route contract i route names pozostają bez zmian.

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

N1-003 zamyka domenowy kontrakt slugów, historycznych full paths, one-hop redirects, route-family exclusivity resolver oraz PostgreSQL serialization. Nie oznacza jeszcze publicznego HTTP 301/200, ponieważ publiczne historyczne redirecty są osobnym N3-006.

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

Aktualny zakres implementuje domenowe przejścia workflow, walidację publish/schedule eligibility, audyt i after-commit event boundary. Publiczne HTTP disposition jest realizowane warstwami N3; historyczny old-path 301 pozostaje N3-006.

Potwierdzony kod:

- `ContentArticlePublishingService`,
- `ContentArticleWorkflowTransitioned implements ShouldDispatchAfterCommit`,
- `NewsroomPublishingServiceTest`.

Zamknięte przejścia obejmują draft ↔ review, review mark, initial schedule, publish, needs_review, archive, dedicated archived republish, withdraw oraz restore-to-review z zachowaniem tombstone aż do skutecznego publish.

Istotne granice pozostają otwarte:
- `applyPublicUpdate` dla już publicznego artykułu jest wdrożone w N2,
- scheduler command/registration został wdrożony w N1-005,
- PR #30 ustanowił bezpieczny after-commit hook; N4-006 później podłączyło lightweight cache invalidation listeners dla istniejących home/category read models. Sitemap/IndexNow/dirty-version side effects nadal pozostają osobnym zakresem N5.

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
- withdrawn wymaga reason, publiczny detail resolver zwraca 410 bez contentu i rekord nie trafia do dystrybucji/sitemap.

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

Implementacja obejmuje warstwę domenową/read-model kompozycji oraz transakcyjny writer placements. Nie oznacza jeszcze wdrożenia publicznego kontrolera `/aktualnosci`, Filament UI placements ani stale-write UX; te elementy pozostają odpowiednio N4/N2.

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
- kolejne N2 taski materializują Builder/RichEditor, sources, media, workflow actions, stale-write, preview i HomeComposer.

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
- `is_publicly_cited=false` pozostaje jednoznacznym internal-evidence stanem; publiczny renderer N3-004 pokazuje wyłącznie publicznie cytowalne źródła i nie ujawnia private evidence/note,
- osobny computed warning „brak primary source dla prawnego newsa” pozostaje zależny od istniejących checklist/regulatory rules,
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
- publiczny Product Bridge N3-005 konsumuje article-owned questions/legal/signs tylko wtedy, gdy body wskazuje ten sam target i istniejący public eligibility contract go dopuszcza; reverse links pozostają N4-008,
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
- publiczny detail N3-004 renderuje provenance/regulatory context i focal-point-aware media; fizyczny crop/OG variant generator nadal nie został dodany.

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

### Status implementacji

**DONE — zmergowano PR #62 na `main@ff81f92fe75442b60e67297f3945d2a63b7c5128` po exact-head CI #224 dla `c62ea2974572299b725387ee90ba7d38bfe0493e`. `quality` zakończył się wynikiem 1025 passed / 19 297 assertions / 2 skipped, Pint 1038 files PASS i frontend build PASS; `newsroom-postgres` zakończył się wynikiem 7 passed / 89 assertions. Status DONE dotyczy warstwy N2/G2 admin-domain; publiczne skutki archive z ostatniego punktu DoD są jawnie zależnością N4/N5 i nie są jeszcze oznaczone jako wdrożone.**

### Aktualny stan implementacji

- istnieje admin-only Filament `ContentTopicResource` z index/create/view/edit, wyszukiwaniem, filtrem statusu, countami całego i eligible corpus oraz read-only health/promotability signals,
- create/edit zarządza istniejącą relacją `content_article_topic` bez dodawania ręcznego rankingu corpus; pojedynczy `featured_article_id` musi należeć do wybranego corpus już na poziomie formularza,
- `ContentTopicPublishingService` realizuje pod row lockiem kontrolowane przejścia draft -> published, published -> archived i archived -> published oraz zapisuje `User` actor w istniejącym `AuditLog`,
- publish/republish wymaga niepustego własnego opisu, minimum 3 powiązanych artykułów spełniających jednocześnie `activelyDistributed()` i `indexable()` oraz — jeśli featured jest ustawiony — jego membershipu i tej samej eligibility,
- `ContentTopic` egzekwuje dozwolone statusy i format sluga; slug oraz `published_at` są chronione po pierwszej publikacji, a topic po pierwszej publikacji nie może zostać usunięty zwykłym delete,
- późniejszy spadek eligible corpus poniżej baseline nie zmienia automatycznie statusu ani widoczności domenowej; `isCorpusBelowBaseline()` daje warning, a `isEditoriallyPromotable()` przestaje kwalifikować topic do promocji,
- N2-011 nie uruchamia publicznego kontrolera topicu: `/aktualnosci/temat/{topicSlug}` nadal pozostaje 404 zgodnie z pre-launch contract. Publiczne 200/410, usunięcie archived topicu z nawigacji oraz integracja sitemap pozostają downstream w N4/N5 i nie są deklarowane jako wykonane przez ten PR.

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

**Stan tego cross-stage punktu DoD:** domenowe `archived` i blokada dalszej promocji są wdrożone w N2-011, publiczny route/410 został domknięty przez N4-007, a N5-001 domknęło standard article sitemap coverage/exclusion dla indexable archived content. N4-007 nie zmienia faktu, że N2/G2 było zamknięte wcześniej na poziomie domeny/CMS.

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

### Status implementacji

**DONE — PR #64 zmergowano na `main@8215e142af2cd88c7335f17bc085bbd0c04d5790`. Exact-head PR CI #231 był pełnym PASS; finalny push-CI #232 na tym samym SHA zakończył się `quality` PASS (1029 passed / 19 335 assertions / 2 skipped, Pint PASS, frontend build PASS) oraz `newsroom-postgres` PASS.**

### Aktualny stan implementacji

- `ContentArticlePublicCatalogService::findPubliclyVisibleBySlug()` realizuje current-canonical lookup z route-family guard,
- `resolveDetailBySlug()` zwraca jawne `visible/gone/not_found` dla 200/410/404 na poziomie backendowego resolution,
- `activelyDistributedQuery()` jest osobną granicą dla hubów/list/feed candidates, z deterministycznym `first_published_at DESC, id DESC`,
- archived po wcześniejszej publikacji pozostaje detail-visible, ale nie trafia do active query; needs_review pozostaje detail-visible zgodnie z policy,
- draft/scheduled/never-published archived i never-published withdrawn są ukryte,
- detail eager loading/column policy nie eksponuje private editorial/license/withdrawal/source-note fields,
- publiczny controller/detail Blade został później podłączony w N3-004; old historical path -> 301 pozostaje osobnym NEWSROOM-N3-006.

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

### Status implementacji

**DONE — PR #66 zmergowano na `main@d9be735eee1915f4b53a6de42ec665a37442acae`. Exact-head PR CI #236 zakończył się pełnym PASS; finalny push-CI #237 na `main` również był pełnym PASS: `quality` 1034 passed / 19 376 assertions / 2 skipped, Pint 1043 files PASS, frontend build PASS (8.50 s), `newsroom-postgres` 7 passed / 94 assertions.**

### Aktualny stan implementacji

- `ContentArticleSeoService` generuje layout-compatible metadata tylko dla `isPubliclyVisible()`; draft/scheduled/withdrawn nie dostają publicznego SEO payloadu,
- self-canonical jest liczony wyłącznie z `NewsroomRouteContract::canonicalPath(type, slug)` i normalizowany przez `PublicUrlResolver`; brak CMS canonical override,
- title używa `seo_title` z fallbackiem do `title` i jednego canonical organization brand bez podwójnego suffixu; description używa `seo_description` z fallbackiem do `lead`, po sanitizacji/plain-text i limicie 160 znaków,
- robots zachowuje jawne pole artykułu albo bezpieczny default `index,follow,max-image-preview:large`,
- social image wybiera dedykowany OG asset, potem hero fallback; zachowuje alt/dimensions, a hero może być wskazany do preload,
- `published_time` = `first_published_at`; `modified_time` = `last_substantive_update_at` z fallbackiem do pierwszej publikacji — nigdy techniczne `updated_at`,
- service zwraca format konsumowalny przez istniejący `public-content.blade.php`, który emituje canonical/OG/Twitter/article times,
- N3-004 podłączył ten payload do publicznego article controller/Blade; historyczne 301 pozostają N3-006.

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

### Status implementacji

**DONE na poziomie backendowego schema service — PR #68 zmergowany.** Feature merge: `main@cdef77c66459537c98b2e044a76a97c082af2ef2`; finalny zweryfikowany HEAD po deterministycznym fixture fixie: `main@5f85bec331428a73f3859ae40b555f55e7e7820d`. Exact-head PR CI #242 PASS, finalny push-CI #245 PASS.

N3-004 podłączył ten graph do publicznego response; historyczne redirecty pozostają N3-006.

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

### Aktualny stan implementacji

- `ContentArticleSchemaService::article()` reużywa `SiteIdentitySchema`, `SchemaIds`, `SchemaRenderer` i canonical/date metadata z `ContentArticleSeoService`,
- graph zawiera Organization, WebSite, WebPage, NewsArticle/Article, Person, BreadcrumbList i ImageObject nodes wyłącznie dla ustawionych hero/OG media paths,
- news -> `NewsArticle`; explainer/analysis/report/guide -> `Article`,
- author/publisher/isPartOf/mainEntityOfPage są relacjami przez stabilne `@id`, a `articleSection`, `inLanguage`, `datePublished` i `dateModified` są spójne z publicznym kontraktem,
- newsroom breadcrumb zawiera primary category; guide breadcrumb pozostaje bez category hop,
- hero/OG images są deduplikowane, a brak ustawionego hero/OG media path nie tworzy ImageObject,
- hidden article, unpublished author i inactive category są fail-closed,
- regression: `tests/Feature/NewsroomArticleSchemaServiceTest.php`.

### DoD

- nie ma literalnych, rozjeżdżających się kopii publishera/authora,
- IDs są stabilne,
- visible dates i schema dates są spójne,
- snapshot/unit tests.

---

## NEWSROOM-N3-004 — Article Blade page + block renderer

### Status implementacji

**DONE — PR #71 zmergowano na `main@7398c5d930d38d6cc9d9953e53b4298df41cfce8`. Finalny exact-head implementacji `5d164e10ca83e6003b52ec279a16234ec5bcea96` przeszedł CI #269 oraz Browser Smoke #18; po merge push-CI #270 na exact `main` również zakończył się pełnym PASS.**

### Aktualny stan implementacji

- `ContentArticleController` obsługuje `/aktualnosci/{articleSlug}` i `/poradniki/{articleSlug}` przez istniejący public catalog/route-family contract,
- visible article renderuje publiczny Blade; withdrawn/historycznie publiczny tombstone zwraca neutralne 410, a hidden/not-found 404 bez ujawniania treści,
- `NewsroomArticlePresentationService` składa breadcrumbs, hero/focal-point metadata, public sources, provenance, regulatory context, daty publikacji/aktualizacji, correction note i author box,
- `NewsroomArticleBodyRenderer` renderuje publicznie `rich_text`, image, quote, table, context i bezpiecznie rozwiązany `related_article`; admin preview reużywa ten renderer z jawnym `includeDeferredBlocks=true`,
- zakres N3-004 świadomie nie renderował jeszcze `legal_reference`, `question_group`, `traffic_sign_group` ani `product_cta`; te bloki zostały później podłączone przez NEWSROOM-N3-005,
- publiczny controller podłącza metadata z N3-002 i graph z N3-003 do istniejącego `public-content` layoutu,
- publiczne sources obejmują wyłącznie `is_publicly_cited=true`; private evidence i `image_license_note` nie są emitowane,
- hero zachowuje alt, caption, credit, dimensions, focal `object-position` i preload/fetch priority contract,
- top-level `/aktualnosci` i `/poradniki` są dziś rollout-gated publicznymi hubami (placeholder/noindex tylko przy gate=false); category pages są publiczne od N4-003, topic dossier od N4-007, a Atom feed `/aktualnosci/feed.xml` jest publiczny od N5-003 przy gate=true,
- historyczny old-path -> 301 pozostaje NEWSROOM-N3-006; `NEWSROOM_PUBLIC_ENABLED` pozostaje NEWSROOM-N3-008.

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

Browser Smoke #18 (`35225396732`) przeszedł na finalnym exact-head N3-004 dla wszystkich wymaganych viewportów:

- 360x800,
- 390x844,
- 430x932,
- 768x1024,
- 1024x768,
- 1440x900.

Harness renderuje publiczną trasę przez Laravel HTTP kernel, ładuje rzeczywisty Vite-built CSS i izoluje wyłącznie zewnętrzny import Google Fonts deterministycznym stubem. Testy sprawdzają m.in. status/H1/breadcrumbs/body/source/canonical/JSON-LD, brak deferred N3-005 placeholders i brak horizontal overflow.

Finalny post-merge CI #270 na `main@7398c5d...` potwierdził: `quality` PASS — 1048 passed / 19 473 assertions / 2 skipped, Pint 1049 files PASS, frontend build PASS (9.06 s); `newsroom-postgres` PASS — 7 passed / 94 assertions.

---

## NEWSROOM-N3-005 — Product bridge

### Status implementacji

**DONE — PR #73 zmergowano; finalny implementation head `7d795b895865cda49ba94a4fec50533d7b0f7a97`, a zweryfikowany post-merge `main` to `fcc8074f89db141d522c5000742afc2e07a68565`. CI #275 i Browser Smoke #20 na finalnym PR head były PASS, a post-merge CI #276 na exact `main` również zakończył się pełnym PASS.**

### Aktualny stan implementacji

- dodano `NewsroomArticleProductBridgeService`; `NewsroomArticleBodyRenderer` deleguje do niego publiczne `question_group`, `legal_reference`, `traffic_sign_group` i `product_cta`,
- nowy partial `resources/views/newsroom/product-bridge-block.blade.php` renderuje public-safe payloady zamiast kopiować HTML/card data do body,
- body block ID dla question/legal/sign nie wystarcza: target musi być również jawnie powiązany z artykułem przez istniejący article-owned pivot i przejść istniejący public eligibility contract,
- questions reużywają `PublicQuestionCatalogService`, zachowują kanoniczne URL-e i są limitowane do 5 pozycji na blok; inactive/nonpublic/unlinked targets są pomijane,
- legal reference wymaga verified `LegalUnit`, verified `LegalAct` i co najmniej jednej published `LegalContentPage` pod published `LegalTopic`; publiczny payload nie zawiera pivot notes ani pełnego `official_excerpt`,
- traffic signs wymagają `relation_type` `direct` lub `example` na article pivot oraz published znaku, autora i kategorii; luźny `related` jest fail-closed zgodnie z Public UI/UX Spec,
- contextual CTA mapują tylko kontrolowane `kind`: `test` -> `public.tests`, `related_questions` -> `public.questions.hub`, `learning` -> `session.index`,
- N3-005 nie dodaje migracji, reverse links, historycznych redirectów ani rollout gate.

### Zakres

- questions,
- legal,
- signs,
- contextual CTA.

### Testy / evidence

- `tests/Feature/NewsroomProductBridgeTest.php` pokrywa linked public target, inactive/draft/unlinked omission, brak wycieku internal notes/official excerpt, canonical destination routes, `related` sign fail-closed oraz trzy CTA,
- Browser Smoke #20 przeszedł 360/390/430/768/1024/1440 i zachował existing article/CSS/overflow assertions,
- finalny CI #275 na PR head: `quality` PASS — 1049 passed / 19 498 assertions / 2 skipped, Pint 1051 files PASS, frontend build PASS; `newsroom-postgres` PASS,
- post-merge CI #276 na `main@fcc8074f...` potwierdził ten sam pełny Quality Gate.

### DoD

- [x] no random relations — target wymaga body + article-owned pivot,
- [x] no draft targets — każdy target przechodzi istniejący public eligibility contract.

---

## NEWSROOM-N3-006 — Redirect resolver

### Status implementacji

**DONE — NEWSROOM-N3-006 zmergowany przez PR #75 i potwierdzony post-merge CI #280 na `main@33d9946219595a4be75d789b19cc8d10efc2ecc0`.** Domenowy/historyczny redirect foundation z N1-003 jest teraz podłączony do publicznego HTTP.

Aktualny stan implementacji:

- `ContentArticleController` konsultuje redirect history wyłącznie po current-canonical `not_found`; bieżące 200/410 zachowują pierwszeństwo,
- `ContentArticlePathResolver::findCanonicalRedirectTarget()` akceptuje wyłącznie zapisany HTTP 301 wskazujący dokładnie bieżący canonical route family + slug i odrzuca stale/malformed/self-loop records,
- old path zwraca dokładnie jeden 301 do current canonical; query params nie są kopiowane,
- `tests/Feature/NewsroomArticleRedirectTest.php` pokrywa newsroom i guide route family, one-hop history oraz fail-closed invalid redirect state,
- exact-head CI #279 i Browser Smoke #21 były PASS; post-merge CI #280 był pełnym PASS.

### Zakres

Old article path -> 301 canonical.

### Test

- exactly one hop.

---

## NEWSROOM-N3-007 — Author profile integration

### Status implementacji

**DONE w kodzie — PR #77 zmergowany i zweryfikowany na `main@c68672f6aa7c41defaeec56debb541d5a60d9f4f`.** Ten osobny docs-sync synchronizuje źródła prawdy po potwierdzonym post-merge gate.

### Cel

Rozszerzyć istniejący publiczny profil `ContentAuthor` o Newsroom bez tworzenia drugiego modelu autora, drugiej strony profilowej ani rozbieżnej tożsamości schema.

### Stan przed implementacją

- `ContentAuthorController` agregował Traffic Signs i legal content,
- structured data autora było budowane przez traffic-sign-specific `TrafficSignSchemaService::author(...)`,
- article graph używał `SchemaIds::contentAuthorPerson(...)`, ale profil autora nie gwarantował jeszcze wspólnego buildera tej samej tożsamości,
- author sitemap kwalifikował autorów przez starsze moduły contentowe i nie uwzględniał Newsroom-only corpus.

### Aktualny stan implementacji

- `ContentAuthorController` pobiera `authoredContentArticles()->indexable()` wyłącznie z aktywnych kategorii,
- `published` jest dołączane do bieżących publikacji autora, `needs_review` jest renderowane w osobnej sekcji „W trakcie weryfikacji”, a `archived` w osobnym „Archiwum”,
- noindex, scheduled, withdrawn oraz artykuły z nieaktywnych kategorii nie są listowane na publicznym profilu autora,
- `ContentAuthorSchemaService` jest neutralnym, współdzielonym builderem `ProfilePage -> Person`; `SharedAuthorTrafficSignSchemaService` deleguje do niego istniejące traffic-sign schema consumers,
- profil i article graph używają identycznego `SchemaIds::contentAuthorPerson($author)` (`/autorzy/{slug}#person`), a `Person.worksFor` wskazuje stabilne `/#organization`,
- `SeoSitemapBuilder::authorUrls()` kwalifikuje autorów również przez indeksowalne artykuły Newsroomu w aktywnych kategoriach; newsroomowa świeżość author sitemap używa maksimum `public_state_changed_at`, nie technicznego `updated_at`,
- model `ContentAuthor` blokuje przejście z publicznego do niepublicznego, gdy istnieje zależny `authoredContentArticles()->indexable()`; po noindex blokada znika,
- nie zmieniono `ContentArticle::dateModified` semantics ani nie utworzono drugiego autora/profile modelu.

### Potwierdzone testy i Quality Gate

- `tests/Feature/ContentAuthorProfileTest.php` pokrywa lifecycle visibility, wspólną tożsamość Person/Organization, public-state freshness author sitemap oraz blokadę/odblokowanie odpublikowania autora,
- exact implementation HEAD `bf7981d23ff99a6335b55ecaeb6ce36b6622a042`: CI #295 — PASS,
- merge commit `main@c68672f6aa7c41defaeec56debb541d5a60d9f4f`: post-merge CI #296 — PASS,
- post-merge `quality`: 1055 passed / 19 540 assertions / 2 skipped, Pint 1055 files PASS, frontend build PASS; `newsroom-postgres` PASS.

### Zakres

- reużyć istniejący `ContentAuthor` i `/autorzy/{slug}`,
- utrzymać jedną stabilną tożsamość Person pomiędzy ProfilePage i Article,
- listować `published`, `needs_review+indexable` i `archived+indexable` zgodnie z ich lifecycle semantics; `needs_review` musi być wyraźnie oznaczone i nie może być promowane jako aktywna publikacja,
- wykluczać noindex, draft, scheduled, withdrawn i nieaktywną kategorię,
- rozszerzyć author sitemap o publiczny/indexowalny Newsroom corpus i public-state freshness,
- blokować odpublikowanie autora, jeśli pozostają zależne indeksowalne publikacje Newsroomu.

### DoD

- [x] brak drugiego Newsroom author/profile modelu,
- [x] article -> author URL działa,
- [x] author page -> indexable article działa,
- [x] ProfilePage i Article wskazują identyczne author `Person @id`,
- [x] archived+indexable pozostaje crawlable z profilu i jest oznaczone jako archiwalne; archived+noindex nie musi być listowane,
- [x] `needs_review+indexable` zachowuje inbound przez osobną sekcję „W trakcie weryfikacji” bez aktywnej promocji,
- [x] withdrawn/draft/scheduled nie są listowane,
- [x] zmiana public eligibility wpływa na author-page/sitemap freshness przez `public_state_changed_at` bez fałszowania article `dateModified`,
- [x] próba odpublikowania `ContentAuthor` z zależnym indexable Newsroom article jest blokowana do reassignment/withdraw/noindex,
- [x] exact-head implementation CI i post-merge CI na `main` są PASS.

### Pozostała praca

- NEWSROOM-N3-008 — Public rollout config gate.

---

## NEWSROOM-N3-008 — Public rollout config gate

### Status implementacji

**DONE w kodzie — PR #79 zmergowano na `main@23b952b77e39cd25fb39edc252faf05849946bd7`.** Finalny implementation head `c8484aa1529eb41805a76ceb7be1f55db63aec14` przeszedł CI #308 oraz Browser Smoke #23; post-merge CI #309 na exact `main` zakończył się pełnym PASS. Ten docs-sync synchronizuje wyłącznie istniejące źródła prawdy po potwierdzonym gate.

### Aktualny stan implementacji

- `config/newsroom.php` czyta `NEWSROOM_PUBLIC_ENABLED` z bezpiecznym defaultem `false`; `.env.example` również utrwala `NEWSROOM_PUBLIC_ENABLED=false`,
- `NewsroomPublicGate` jest jednym prostym source of truth dla publicznego dark-deploy gate; nie dodano równoległego feature-flag frameworka,
- przy `false` `ContentArticleController` failuje zamknięcie do publicznego 404 przed lookupem artykułu i przed historycznym redirect resolverem, więc zarówno current detail, jak i old-path 301 nie ujawniają dark-deployed content,
- przy `false` top-level `/aktualnosci` i `/poradniki` zachowują pre-launch placeholder 200 z `X-Robots-Tag: noindex, follow`, article/category/topic routes failują do 404, a N5-003 feed również zwraca 404 i nie emituje discovery; przy `true` home/category/guides/topic surfaces są aktywne po N4-002/N4-003/N4-004/N4-007, a `/aktualnosci/feed.xml` zwraca publiczny Atom feed,
- przy `false` profil autora nie pobiera publikacji Newsroomu, a `SeoSitemapBuilder` nie kwalifikuje newsroom-only authora ani nie dodaje newsroomowego `public_state_changed_at` do freshness author sitemap,
- `IndexNowUrlCollector` defensywnie odrzuca namespace `/aktualnosci` i `/poradniki` przy wyłączonym gate; faktyczna article-specific automatyzacja IndexNow nadal należy do N5-005,
- admin/data i authenticated private preview pozostają dostępne przy `false`,
- istniejące publiczne testy i dedykowane Browser Smoke jawnie ustawiają gate na `true`; `NewsroomPublicGateTest` pokrywa disabled/enabled state dla news + guide detail, old-path redirect, top-level placeholderów, category/topic gate behavior, feed enabled/disabled behavior, author discovery, author sitemap contribution, IndexNow collector i private preview,
- public hub/category/guides/topic, N4-008 semantic/reverse links, N5-001 article/hub sitemap, N5-002 News Sitemap oraz N5-003 Atom feed/discovery konsumują dziś ten sam `NewsroomPublicGate`; feed nie ma własnego przełącznika.


### Cel

Wdrożyć publiczną warstwę bez natychmiastowego przełączania istniejących placeholderów/indeksacji.

### Zakres

- prosty config/env `NEWSROOM_PUBLIC_ENABLED`,
- default bezpieczny dla wdrożenia przed rolloutem,
- gdy wyłączony: admin/dane/private preview mogą działać, nowe public article/category/topic routes nie stają się indeksowalne, a istniejące top-level placeholder behavior nie jest przypadkowo usuwane,
- gate obejmuje również newsroom entries w author profiles/reverse links/feed/sitemaps/IndexNow; nie może istnieć crawlable „tylne wejście” do dark-deployed content,
- włączenie dopiero w N6 release sequence i powoduje cache/distribution refresh.

### DoD

- [x] public switch nie wymaga rollbacku migracji,
- [x] disabled state blokuje wszystkie **obecnie istniejące** publiczne wejścia Newsroomu poza świadomie zachowanymi placeholderami,
- [x] enabled/disabled regression obejmuje current detail, guide detail, old-path redirect, author profile, author sitemap contribution, IndexNow collector i private preview,
- [x] config/env ma bezpieczny default `false`, a istniejący release runbook opisuje `config cache`/cutover semantics,
- [x] reverse-link behavior jest objęte tym samym gate od N4-008; [ ] feed/article-sitemap/news-sitemap pozostają N5 i nadal muszą konsumować ten sam gate.

---

# N4 — Hub, categories and guides

## NEWSROOM-N4-001 — /aktualnosci editorial composition read model

### Status implementacji

**DONE w kodzie — PR #81 zmergowano na `main@e0e06e9af8a6b02a63ef4b3e1eb2d772409ad974`.** Finalny implementation head `813aba108b6b68f0526df3a9c8c86d82df5ca0f6` przeszedł exact-head CI #314, a post-merge CI #315 na exact `main` zakończył się pełnym PASS. Publiczny renderer huba nie należy do tego tasku i pozostaje NEWSROOM-N4-002.

### Aktualny stan implementacji

- istniejący `NewsroomHomeCompositionService` pozostaje jedynym composerem; N4-001 nie tworzy równoległej logiki kompozycji,
- zachowano kolejność lead -> secondary -> latest -> category blocks -> guides -> important now oraz globalny zbiór użytych article IDs; breaking pozostaje jedynym świadomym wyjątkiem i może powtórzyć lead,
- manual placements nadal wygrywają wyłącznie dla kwalifikowanego `activelyDistributed()` targetu, a deterministyczny fallback zachowuje istniejące kryteria,
- category composition nie wykonuje już query-per-category: aktywne category placements są pobierane batchem, a kandydaci editorial/chronological są ograniczani per category przez ranking `ROW_NUMBER() OVER (PARTITION BY category_id ...)`; relacje `category` i `author` są eager-loaded jednym batchem dla wybranego zbioru,
- `NewsroomHomeReadModelService` materializuje publiczny scalar-array projection dla lead/secondary/latest/categories/guides/important_now/breaking, z canonical path, category/author i hero metadata; wynik jest serializowalny i gotowy do późniejszego cache,
- publiczny read model respektuje istniejący `NewsroomPublicGate`: przy `NEWSROOM_PUBLIC_ENABLED=false` zwraca `null`; admin preview nadal korzysta bezpośrednio z domenowego composera i pozostaje dostępny,
- `tests/Feature/NewsroomHomeReadModelServiceTest.php` chroni rollout gate, scalar/cacheable projection oraz stały query budget przy wzroście liczby aktywnych kategorii,
- N4-001 samo nie wdrażało cache store/invalidation ani publicznego Blade/controllera; publiczny renderer został zmaterializowany przez NEWSROOM-N4-002, a faktyczny home/category cache/invalidation przez NEWSROOM-N4-006.

### Zakres

- [x] fixed placements,
- [x] lead,
- [x] secondary,
- [x] latest,
- [x] category blocks,
- [x] guides,
- [x] breaking,
- [x] fallback,
- [x] global card deduplication.

### Performance

- [x] bounded queries niezależne od liczby aktywnych kategorii,
- [x] eager loads dla category/author,
- [x] cacheable scalar-array read model; faktyczny home/category cache/invalidation został zmaterializowany w N4-006.

---

## NEWSROOM-N4-002 — Hub Blade layout

### Status implementacji

**DONE w kodzie — PR #83 zmergowano na `main@04a3e961a40207bd65c41b684a5bf1cd8d2c5e10`.** Finalny implementation head `1036b67aa44b56fffb0bc1e9083d3a0d1d3963d0` przeszedł exact-head CI #321 i Browser Smoke #27; post-merge CI #322 na exact `main` zakończył się pełnym PASS.

### Aktualny stan implementacji

- istniejący route `public.news` nadal używa `NewsroomPlaceholderController::news()`; nie dodano drugiego route ani równoległego controllera,
- przy `NEWSROOM_PUBLIC_ENABLED=false` `NewsroomHomeReadModelService::build()` zwraca `null`, a controller zachowuje dotychczasowy `Public/MarketingPlaceholder` 200 + `X-Robots-Tag: noindex, follow`,
- przy `NEWSROOM_PUBLIC_ENABLED=true` controller renderuje SSR `resources/views/newsroom/home.blade.php` z istniejącego N4-001 read modelu i ustawia self-canonical oraz `index,follow,max-image-preview:large`,
- Hub Blade renderuje tylko niepuste moduły: newsroom subnavigation, important-now, breaking, lead/secondary, latest, category blocks, guides i Product Bridge; brak danych nie tworzy sztucznych kart ani pustych placeholderów,
- subnavigation kategorii nadal kotwiczy do sekcji na `/aktualnosci`, ale category blocks mają crawlable wejścia do publicznych category pages od N4-003; publiczny topic route istnieje od N4-007, a jawne article→topic discovery i controlled reverse links są wdrożone od N4-008; feed pozostaje 404 do N5,
- Product Bridge reużywa istniejący route `public.tests`; wspólny `layouts.public-content`, header i footer pozostają bez zmian,
- `tests/Feature/NewsroomHomePageTest.php` pokrywa gate=false, gate=true i empty-section behavior; istniejące `NewsroomPublicGateTest` i `Public/NewsroomRouteContractTest` zostały zsynchronizowane z nowym enabled-state huba,
- dedykowany `scripts/e2e-newsroom-home.mjs` działa z JavaScript disabled, realnym built CSS i viewportami 360/390/430/768/1024/1280/1440; workflow `Browser Smoke` ma automatyczny job `newsroom-home` dla relewantnych PR-ów.

### Zakres

- [x] UI spec implementation dla publicznego `/aktualnosci`,
- [x] responsive,
- [x] empty section behavior.

### DoD

- [x] przy gate=true brak `MarketingPlaceholder` na `/aktualnosci`,
- [x] przy gate=false zachowany bezpieczny pre-launch placeholder/noindex,
- [x] dedicated hub Browser QA PASS.

N4-002 nie obejmuje category/topic/feed, osobnego huba `/poradniki`, cache/invalidation ani sitemap/discovery.

---

## NEWSROOM-N4-003 — Category pages

### Status implementacji

**DONE w kodzie — PR #85 zmergowano na `main@84bcb2aeff57a1374def7af411c39db07a8fb38d`.** Finalny implementation head `8e4f707060fbaa348e9392f0b19beb7b4517beca` przeszedł exact-head CI #326, Browser Smoke #29 i post-merge CI #327.

### Aktualny stan implementacji

- istniejący route `public.news.categories.show` jest podłączony do `NewsroomCategoryController`; przy gate=false category request kończy 404, przy gate=true aktywna kategoria renderuje `newsroom.category`,
- `NewsroomCategoryReadModelService` reużywa `ContentArticlePublicCatalogService::activelyDistributedQuery(NewsroomRouteContract::FAMILY_NEWSROOM)`, filtruje po `category_id`, sortuje `first_published_at DESC, id DESC` i paginuje po 20,
- inactive/unknown category, nieprawidłowy `page` oraz out-of-range page failują do 404,
- aktywna pusta kategoria renderuje użyteczny 200, ale ma meta robots i `X-Robots-Tag: noindex, follow`,
- page 1 ma canonical bez `?page=1`; kolejne strony mają self-canonical `?page=N` i crawlable prev/next/page links,
- `NewsroomCategorySchemaService` emituje `CollectionPage`, `BreadcrumbList` oraz `ItemList` tylko dla niepustego corpus,
- related categories obejmują wyłącznie aktywne kategorie mające `activelyDistributed()` newsroom-family articles,
- Hub Blade linkuje category blocks przez `Zobacz wszystkie` do istniejącego route category,
- `NewsroomCategoryPageTest` i `scripts/e2e-newsroom-category.mjs` chronią gate, eligibility, deterministic order, empty state, paginację, canonical/robots/schema i responsive browser surface.

### Zakres

- [x] category header,
- [x] activelyDistributed-only chronological list,
- [x] order by first_published_at desc + deterministic tie-breaker,
- [x] pagination,
- [x] SEO.

### DoD

- [x] category route aktywuje się bez tworzenia drugiego route contract,
- [x] public listing nie pokazuje guide/draft/archived/needs_review corpus,
- [x] paginacja jest SSR/crawlable i canonical-safe,
- [x] empty category nie staje się indeksowalną thin page,
- [x] dedykowany Browser QA PASS.

N4-003 nie obejmuje `/poradniki` huba, topic/feed, cache/invalidation, reverse links ani N5 discovery.

---

## NEWSROOM-N4-004 — /poradniki hub

### Status implementacji

**DONE w kodzie — PR #87 zmergowano na `main@2bb22142b1e9bec803f9c3889c11000194f46783`.** Finalny implementation head `119cbd94d1fb6ff6f9f2025e726242190927266d` przeszedł exact-head CI #331, Browser Smoke #31 i post-merge CI #332.

### Aktualny stan implementacji

- zachowano istniejący top-level route `public.guides` i istniejący `NewsroomPlaceholderController::guides()`; nie dodano drugiego route/controller boundary,
- przy `NEWSROOM_PUBLIC_ENABLED=false` controller zachowuje pre-launch `MarketingPlaceholder` 200 + `X-Robots-Tag: noindex, follow`,
- przy gate=true `NewsroomGuideHubReadModelService` reużywa `ContentArticlePublicCatalogService::activelyDistributedQuery(FAMILY_GUIDES)` i renderuje SSR `newsroom.guides`,
- listing jest guide-only, sortuje `first_published_at DESC, id DESC`, paginuje po 20 i reużywa istniejące `public.guides.show` detail URLs,
- page 1 ma canonical bez `?page=1`, dalsze strony mają self-canonical `?page=N` i crawlable prev/next/page links,
- aktywny pusty hub renderuje użyteczny 200 + meta/header noindex; invalid i out-of-range `page` failują do 404,
- evergreen UI de-emphasizes publication time, używa labelu „Poradnik” i wspólnego `layouts.public-content`,
- `NewsroomGuideHubSchemaService` emituje `CollectionPage`, `BreadcrumbList` i `ItemList` tylko dla niepustego corpus,
- `/aktualnosci` linkuje sekcję poradników przez crawlable `Zobacz wszystkie poradniki` do `public.guides`,
- `NewsroomGuidesHubTest` oraz `scripts/e2e-newsroom-guides.mjs` chronią gate, eligibility, deterministic order, empty-state/noindex, paginację, canonical/schema i responsive browser surface.

### Zakres

- [x] guide-only listing,
- [x] evergreen UI variant,
- [x] article reuse.

### DoD

- [x] istniejący route contract `public.guides` zachowany bez duplikatu,
- [x] listing nie pokazuje newsroom/draft/archived/needs_review corpus,
- [x] SSR paginacja i canonical policy są crawlable i deterministic,
- [x] pusty hub nie staje się indeksowalną thin page,
- [x] dedykowany Browser QA PASS.

N4-004 nie obejmuje global navigation changes N4-005, cache/invalidation N4-006, topic pages N4-007, reverse links N4-008 ani N5 discovery.

---

## NEWSROOM-N4-005 — Navigation integration

### Status implementacji

**DONE w kodzie — PR #89 zmergowano na `main@a9fb9ccfed058de88efdb6e0833b67911aeb09aa`.** Finalny implementation head `189219b3586d2df8e4ea73045318fd68f38f0fa1` przeszedł exact-head CI #335, Browser Smoke #32 i post-merge CI #336.

### Aktualny stan implementacji

- `PublicNavigation::primary` zachowuje dokładnie po jednym wpisie „Aktualności” i „Poradniki”; nie dodano duplikatów i nie zmieniono active-state prefixów,
- `documentNavigationPrefixes` już zawiera `/aktualnosci` i `/poradniki`; N4-005 nie wymagało zmiany helpera,
- `PublicFooter::service_links` został uzupełniony o oba huby,
- Vue `SiteFooter.vue` i Blade `public-footer.blade.php` nadal renderują wspólne dane z `PublicFooter`,
- `NewsroomNavigationIntegrationTest` chroni canonical links, duplicate-free footer i prefixy,
- `newsroom-guides` Browser QA sprawdza dokładnie po jednym linku footerowym do obu hubów, a Browser Smoke #32 przeszedł wszystkie relewantne newsroom joby.

### Zakres

- [x] zachować istniejące linki i active states, bez dublowania,
- [x] sprawdzić footer/inne renderery i dodać tylko brakujące, uzasadnione wejścia,
- [x] pozostawić `documentNavigationPrefixes` bez zmiany, ponieważ oba namespace’y już istniały.

### DoD

- [x] zero duplicate nav items,
- [x] Vue + Blade renderers verified.

---

## NEWSROOM-N4-006 — Cache

### Status implementacji

**DONE w kodzie — PR #91 zmergowano na `main@5f1880e03469fc6e340a86fdb1b4246c7afd116b`.** Finalny implementation head `093ca709d3155d5fa3f13bae224312fd286077dc` przeszedł exact-head CI #342, Browser Smoke #36 i post-merge CI #343.

### Aktualny stan implementacji

- `NewsroomHomeReadModelService` i `NewsroomCategoryReadModelService` korzystają z jednego `NewsroomPublicReadCache`,
- home/category keys są generation-based, więc invalidation rotuje namespace zamiast enumerować stare klucze,
- `newsroom.cache_ttl_seconds=60` jest safety netem dla time-window changes; event invalidation pozostaje głównym mechanizmem,
- istniejący after-commit `ContentArticleWorkflowTransitioned` invaliduje home+category przy wejściu/wyjściu z `published`,
- `ContentArticlePublicReadChanged` invaliduje home+category po aktywnym public update oraz featured/breaking exposure changes,
- `ContentHomePlacementChanged` invaliduje tylko home po create/update/delete placementu,
- `ContentCategoryObserver` działa after-commit i invaliduje home+category po save/delete kategorii,
- admin/private preview, guide hub, article detail, topic/feed nie są cache’owane przez ten task,
- N5 dirty/version/sitemap/IndexNow/feed-refresh coordinator pozostaje osobnym zakresem.

### Zakres

- [x] home,
- [x] category,
- [ ] topic — pozostaje do N4-007, gdy publiczny topic surface będzie istniał,
- [ ] feed later — pozostaje N5,
- [x] invalidation events.

### Test

- [x] publish shows article after invalidation,
- [x] placement change invalidates home,
- [x] archive removes article from cached sections,
- [x] category metadata invalidates home/category projections,
- [x] cache reuse jest obserwowalny przed invalidation,
- [x] invalidation event nie wykonuje side effectu przed outer transaction commit.

N4-006 nie obejmuje topic/feed, guide-hub/article cache ani N5 discovery side effects.

---

## NEWSROOM-N4-007 — Topic / dossier pages

### Status implementacji

**DONE w kodzie — PR #93 zmergowano na `main@a97373003c5a249a28761df15342f19e656c50c5`.** Finalny implementation head `812313afc38e30e53c59901d46c2d24188e3f6fa` przeszedł exact-head CI #348, Browser Smoke #39 i post-merge CI #349.

### Aktualny stan implementacji

- istniejący route `/aktualnosci/temat/{topicSlug}` używa `NewsroomTopicController`,
- `NEWSROOM_PUBLIC_ENABLED=false`, draft, future i unknown topic failują do 404 + noindex; wcześniej publiczny archived topic zwraca 410 + noindex,
- publiczny topic reużywa istniejący `ContentTopic`, jego publish/archive/republish workflow oraz jawny `content_article_topic` pivot; nie powstał drugi topic/tag model ani nowa migracja,
- `NewsroomTopicReadModelService` wybiera `activelyDistributed()+indexable()` corpus typów news/explainer/analysis/report/guide i sortuje `first_published_at DESC, id DESC`,
- opcjonalny eligible `featured_article_id` jest pojedynczym leadem na page 1 i jest wykluczony z chronologicznego listingu/paginacji,
- późniejszy spadek corpus poniżej baseline 3 nie przełącza automatycznie HTTP/indexability; baseline pozostaje gate'em publish/republish i health signalem,
- `newsroom.topic` jest SSR/Blade-first, ma editorial description, featured, chronological listing i crawlable pagination,
- `NewsroomTopicSchemaService` emituje `CollectionPage` + `BreadcrumbList` + `ItemList`; page 1 jest self-canonical bez `?page=1`, a page N używa `?page=N`,
- brak automatycznych tag pages, topic cache, reverse links i N5 sitemap/feed/IndexNow.

### Zakres

- [x] public topic route,
- [x] intro/description,
- [x] featured article,
- [x] activelyDistributed/indexable topic corpus,
- [x] latest ordering by first_published_at + id tie-breaker,
- [x] pagination,
- [x] SEO metadata + CollectionPage schema.

### DoD

- [x] only published topics public,
- [x] no automatic tag pages,
- [x] thin topic nie jest publikowany automatycznie: publish/republish zachowuje minimum 3 eligible articles; późniejszy spadek corpus nie tworzy ukrytego HTTP flipu.

N4-007 nie obejmuje semantic silo/reverse links N4-008 ani N5 discovery.

---

## NEWSROOM-N4-008 — Semantic silo / reverse-link integration

### Status implementacji

**DONE w kodzie — PR #95 zmergowano na `main@3d7ac8ab8a3ed1c299cb0cdd4cb1ef8ac6b53f78`.** Finalny implementation head `bd63773bc1febdcc6aa8c2507c6621e908d63b09` przeszedł exact-head CI #361 i Browser Smoke #48; post-merge CI #362 na exact `main` również zakończył pełny PASS.

### Aktualny stan implementacji

- `NewsroomSemanticLinkService` materializuje jeden kontrolowany resolver linków semantycznych za istniejącym `NewsroomPublicGate`; N4-008 nie dodaje migracji ani drugiego graphu relacji,
- publiczny article/guide detail ma crawlable primary-category link, a opublikowane jawnie powiązane topics są renderowane jako zwykłe linki do istniejących dossier,
- related articles są deterministyczne i ograniczone do maks. 4: shared published topics, następnie ta sama primary category, `editorial_priority`, `first_published_at` i `id`,
- reverse modules na publicznych pytaniach, opublikowanych stronach prawnych i detalach znaków używają wyłącznie istniejących newsroom-owned pivotów, mają limit maks. 3 i pokazują wyłącznie `activelyDistributed()+indexable()` targets z aktywną kategorią i publicznym autorem,
- TrafficSign zachowuje fail-closed contract `direct|example`; luźny `related` nie tworzy reverse linku,
- article -> question/legal/sign pozostaje istniejącym N3-005 Product Bridge; N4-008 nie dubluje tej warstwy i nie modyfikuje `question_relations`, `question_seo_topics` ani rankingów question graphu,
- `audit()` udostępnia per-article inbound sources, liczbę jawnych reverse edges i deterministyczne `estimated_hub_depth`; osobna site-wide komenda `newsroom:audit-links` nie została dodana i pozostaje ewentualnym zakresem N5/N6,
- dedykowany `newsroom-semantic-links` Browser Smoke oraz `NewsroomSemanticLinkIntegrationTest` chronią publiczny kontrakt, gate i brak mutacji innych graphów.

### Zakres

- [x] primary category link dla każdego public article/guide detail,
- [x] topic links tylko dla jawnych opublikowanych relacji,
- [x] article -> legal/question/sign przez istniejący Product Bridge oraz public related articles przez N4-008,
- [x] article-question edge pozostaje wyłącznie w newsroom pivot bez modyfikowania `question_relations` / `question_seo_topics`,
- [x] ograniczone reverse links z legal/question/sign surfaces do wybranych public articles,
- [x] natural/descriptive anchors,
- [x] deterministic related resolver,
- [x] per-article inbound-link/click-depth audit data.

### DoD

- [x] publiczny indexable corpus ma crawlable inbound z właściwego huba/category/topic/author surface zgodnie z istniejącymi lifecycle contracts; audit ujawnia wynik dla artykułu,
- [x] resolver raportuje deterministyczny estimated hub depth: guide hub 1, active newsroom+category 2, author fallback 3,
- [x] reverse links wynikają z jawnej relacji, mają bounded count i pokazują tylko `activelyDistributed()+indexable()` targets przy globalnym public gate=true,
- [x] related/reverse resolver nie promuje draft/noindex/withdrawn/nieaktywnie dystrybuowanych targets i generuje wyłącznie current canonical paths,
- [x] brak automatycznego sitewide reciprocal linking,
- [x] sitemap nie jest jedyną drogą discovery; publiczne hub/category/topic/author/entity surfaces dostarczają crawlable `<a href>`.

Site-wide orphan/click-depth crawler/komenda pozostaje osobnym możliwym hardeningiem N5/N6 i nie jest opisywany jako część ukończonego N4-008.

---

# N5 — SEO, distribution and analytics

## NEWSROOM-N5-001 — Extend static generator: articles + hub sitemap coverage + deterministic sharding

### Status implementacji

**DONE w kodzie — PR #97 zmergowano na `main@5ddfa48c0646fa89cc802d129e6d9ccee9d18957`.** Finalny implementation head `b4ff321011c3d58438876c9d69f324e342ec9021` przeszedł exact-head CI #367; post-merge CI #368 na exact `main` również zakończył pełny PASS.

### Aktualny stan implementacji

- istniejące `SeoSitemapBuilder`, `SeoSitemapGenerator` i `SeoSitemapAuditor` zostały rozszerzone; nie powstał drugi sitemap engine,
- przy `NEWSROOM_PUBLIC_ENABLED=false` article shards i newsroom hub coverage nie są emitowane,
- standard article sitemap obejmuje indexable newsroom/guide detail URLs z aktywną kategorią i publicznym autorem oraz wyklucza noindex/draft/withdrawn i current canonical path kolidujący z historycznym `ContentArticleRedirect.from_path`,
- przy corpus mieszczącym się w jednym zakresie generator używa `/sitemaps/articles.xml`; po przekroczeniu skonfigurowanego zakresu używa stabilnych shardów `articles-{id-range}.xml` opartych o niezmienne zakresy `content_articles.id`, bez OFFSET shardingu,
- wynikowe article files są wpisywane bezpośrednio do istniejącego root `/sitemap.xml`; nie dodano zagnieżdżonego newsroom sitemap-index,
- `static.xml` otrzymuje rollout-gated coverage dla `/aktualnosci`, warunkowo `/poradniki`, aktywnych category hubs i published topic hubs,
- article `lastmod` używa `max(first_published_at, last_substantive_update_at, public_state_changed_at)`; huby używają deterministycznych timestampów publicznego outputu zamiast czasu generowania,
- generator i auditor mają guardy 50 000 wpisów / 50 MB nieskompresowanego XML,
- N5-001 nie wdraża news sitemap, feedu, dirty/version refresh coordinatora, article-specific IndexNow, child-before-index set switch/obsolete-shard cleanup ani produkcyjnej weryfikacji Nginx/CDN/GSC.

### Zakres

- [x] wszystkie publiczne indexable articles,
- [x] `/aktualnosci`, `/poradniki`, active categories i published/indexable topics mają jawne sitemap coverage,
- [x] meaningful lastmod,
- [x] absolute canonical HTTPS URLs,
- [x] exclude noindex/draft/withdrawn/redirect-source,
- [x] istniejący sitemap index jako parent,
- [x] deterministic sharding readiness przed limitami pojedynczego pliku,
- [x] żadnego niestabilnego offset-shardingu.

### Testy

- [x] single shard,
- [x] boundary crossing,
- [x] stable shard assignment,
- [x] no duplicate URL across shards,
- [x] XML size/URL-count guard,
- [x] public-gate suppression i eligibility regression w istniejącym `SeoSitemapGenerationTest`.

### Dowód Quality Gate

- exact-head CI #367: 1098 passed / 19 926 assertions / 2 skipped, `newsroom-postgres` 7 passed / 94 assertions, Pint PASS, frontend build PASS,
- post-merge CI #368 na `main@5ddfa48c0646fa89cc802d129e6d9ccee9d18957`: 1098 passed / 19 926 assertions / 2 skipped, `newsroom-postgres` 7 passed / 94 assertions, Pint PASS, frontend build PASS.

---

## NEWSROOM-N5-002 — News sitemap

### Status implementacji

**DONE w kodzie — PR #99 zmergowano na `main@827f3816487d3a404df26c381a103a6cd1a9f413`.** Finalny implementation head `9bc16a7e42ae55c23b1916a4e214b9af846fd3dd` przeszedł exact-head CI #371; post-merge CI #372 na exact `main` również zakończył pełny PASS.

### Aktualny stan implementacji

- aktualne wymagania Google News Sitemap zostały ponownie zweryfikowane przy implementacji: 2-dniowe okno po pierwotnej publikacji, maks. 1000 `news:news` entries na plik oraz wymagane `news:name`, `news:language`, `news:publication_date`, `news:title`,
- istniejące `SeoSitemapBuilder`, `SeoSitemapGenerator` i `SeoSitemapXmlRenderer` zostały rozszerzone; nie powstał drugi sitemap engine,
- przy `NEWSROOM_PUBLIC_ENABLED=false` News Sitemap nie jest generowana ani wpisywana do root `/sitemap.xml`,
- eligibility wymaga `type=news`, aktywnej dystrybucji, statusu `published`, indexable, aktywnej kategorii, publicznego autora i current-canonical path niekolidującego z historycznym redirect source,
- okno świeżości jest liczone wyłącznie po `first_published_at >= now()-2 days`; nowszy `published_at`, `last_substantive_update_at` lub `public_state_changed_at` nie przywraca starego artykułu do News Sitemap,
- `news:name` korzysta z istniejącego canonical publication identity przez `SiteIdentitySchema::siteName()`; nie dodano równoległego configu,
- `news:language=pl`, `news:publication_date=first_published_at` w ISO/W3C, a `news:title` używa widocznego `ContentArticle.title`, nie SEO title ani brandingu,
- przy maks. 1000 kwalifikowanych wpisów generator używa `/sitemaps/news.xml`; powyżej limitu generuje stabilne fixed-`content_articles.id` range shards i wpisuje je bezpośrednio do istniejącego root sitemap index,
- istniejący `SeoSitemapAuditor` dopuszcza legalny overlap URL-i pomiędzy standard article sitemap i News Sitemap; cross-stage status: NEWSROOM-N5-006 później domknęło namespace/tag/age/eligibility/topology/shard/obsolete-file audit rules bez drugiego validatora,
- N5-002 nie wdraża feedu, dirty/version refresh coordinatora, child-before-index atomic publication/obsolete-shard cleanup, article-specific IndexNow ani produkcyjnej weryfikacji Nginx/CDN/GSC.

### Zakres

- [x] current Google constraints reverified at implementation,
- [x] recent news eligibility po `first_published_at`, nie po updated/published refresh,
- [x] tylko type=news + published + indexable,
- [x] news:name = canonical publication name,
- [x] news:language = pl,
- [x] news:publication_date = original first_published_at,
- [x] news:title = visible title bez author/brand/date,
- [x] split powyżej aktualnego limitu entry,
- [x] sitemap index integration,
- [x] statyczny output generowany przez istniejący generator,
- [ ] `news:name` po launch porównane z nazwą publikacji widoczną w Google News.

### Testy

- [x] old updated article nie wraca do news sitemap,
- [x] required namespace/tags,
- [x] publication date timezone/format,
- [x] 1000-entry boundary zgodnie z aktualną specyfikacją,
- [x] public-gate suppression, non-news/status/noindex/category/author/redirect-source exclusions.

### Dowód Quality Gate

- exact-head CI #371: 1100 passed / 19 964 assertions / 2 skipped, `newsroom-postgres` 7 passed / 94 assertions, Pint PASS, frontend build PASS,
- post-merge CI #372 na `main@827f3816487d3a404df26c381a103a6cd1a9f413`: 1100 passed / 19 964 assertions / 2 skipped, `newsroom-postgres` 7 passed / 94 assertions, Pint PASS, frontend build PASS.

---

## NEWSROOM-N5-003 — RSS/Atom feed + discovery

### Status implementacji

**DONE w kodzie — PR #101 zmergowano na `main@18cd07233e3c8712cf2c7fc9e0c32018baef65d3`.** Finalny implementation head `1c89f370e899895eb25ac80bd437b19c1797a9ec` przeszedł exact-head CI #380 i Browser Smoke #54; post-merge CI #381 na exact `main` również zakończył pełny PASS.

### Aktualny stan implementacji

- istnieje jeden publiczny **Atom 1.0** feed pod istniejącym route `/aktualnosci/feed.xml`; nie dodano równoległego RSS endpointu,
- feed obejmuje maksymalnie konfigurowalne `newsroom.feed_items_limit` najnowszych aktywnie dystrybuowanych wpisów `type=news` (default 50),
- entry id jest stabilne i ma dokładnie postać `urn:prawkonaraz:content-article:{id}`; zmiana sluga aktualizuje canonical link, ale nie tworzy nowej tożsamości wpisu,
- `published` używa `first_published_at`, a `updated` używa `last_substantive_update_at ?? first_published_at`,
- entry zawiera absolute canonical link, plain-text summary i publiczną nazwę autora; feed-level identity reużywa istniejący `SiteIdentitySchema`,
- `NewsroomPublicReadCache` ma osobną generation-based przestrzeń feedu; istniejące after-commit invalidation `invalidateAll()` odświeża feed po workflow/public update,
- response jest bezstanowy względem session middleware i nie emituje `Set-Cookie`; 200 ma `application/atom+xml; charset=UTF-8`, `ETag`, `Last-Modified`, public `Cache-Control` oraz `X-Content-Type-Options: nosniff`,
- `If-None-Match` i `If-Modified-Since` są obsługiwane przez conditional `304 Not Modified`,
- wspólny public-content layout emituje `<link rel="alternate" type="application/atom+xml">` dla crawlable publicznych newsroom/guide surfaces przy włączonym gate,
- `NEWSROOM_PUBLIC_ENABLED=false` wyłącza feed do 404 i suppressuje head discovery; nie powstał osobny feature flag dla feedu.

### Zakres

- [x] latest items,
- [x] stable GUID/Atom id = dokładnie `urn:prawkonaraz:content-article:{id}`,
- [x] slug change zmienia item link, ale nie GUID/id,
- [x] correct Atom content type,
- [x] generation cache/invalidation,
- [x] head auto-discovery w istniejącym public layoutcie,
- [x] public absolute canonical links,
- [x] ETag + Last-Modified + conditional 304,
- [x] brak session cookie na feed response,
- [x] wspólny `NewsroomPublicGate`.

### Testy

- [x] content type, metadata, bounded latest-item limit i XML escaping,
- [x] exclusion non-news / needs_review / archived,
- [x] stable id przy zmianie sluga,
- [x] cache invalidation po archive workflow,
- [x] ETag / Last-Modified na 200 oraz osobne conditional 304 dla `If-None-Match` i `If-Modified-Since`,
- [x] discovery przy gate=true oraz feed 404/discovery suppression przy gate=false,
- [x] route regression i brak `Set-Cookie`.

### Dowód Quality Gate

- exact-head CI #380: **1104 passed / 20 024 assertions / 2 skipped**, PostgreSQL **7 passed / 94 assertions**, Pint PASS, frontend build PASS,
- Browser Smoke #54: PASS dla article, home, category, guides, topic i semantic-links,
- post-merge CI #381 na `main@18cd07233e3c8712cf2c7fc9e0c32018baef65d3`: **1104 passed / 20 024 assertions / 2 skipped**, PostgreSQL **7 passed / 94 assertions**, Pint PASS, frontend build PASS.

### Poza zakresem N5-003

- drugi RSS endpoint,
- produkcyjna weryfikacja CDN/Nginx dla feedu,
- dirty/version sitemap coordinator i child-before-index static publication,
- article-specific IndexNow,
- analytics hooks oraz namespace-specific sitemap audit.

---

## NEWSROOM-N5-004 — Analytics hooks

### Status implementacji

**DONE w kodzie — PR #103 zmergowano na `main@5704b3c3a8acde029001e28567980c5de8e27cdf`.** Finalny implementation head `6235b7dbadd60549a82ceebcb4eda47aaa6596fa` przeszedł exact-head CI #384 i Browser Smoke #55; post-merge CI #385 na exact `main` również zakończył pełny PASS.

### Aktualny stan implementacji

- analytics reużywa istniejący `trackAnalyticsEvent` i istniejący Google Analytics / consent layer; nie powstał drugi klient GA, backend telemetryczny ani nowa tabela,
- `resources/js/public/newsroomAnalytics.ts` jest jednym delegowanym handlerem dla SSR newsroomu, inicjalizowanym przez `public-content.ts`,
- istnieją eventy `newsroom_article_view`, `newsroom_module_click`, `newsroom_product_cta_click`, `newsroom_source_click`, `newsroom_related_article_click`, `newsroom_related_question_click`, `newsroom_related_legal_click`, `newsroom_related_sign_click`, `newsroom_category_click` i `newsroom_pagination_click`,
- article/module context używa wyłącznie stabilnych pól: `article_id`, `article_type`, `category_slug`, `module`, `position`, `destination_path`,
- `destination_path` jest redukowany do pathname; tytuł, autor, body, lead, source title/publisher i inne treści/PII nie są wysyłane jako parametry,
- istniejący GA component emituje `prawkonaraz:analytics-ready` po konfiguracji consent-ready gtag; article view czeka na gotowość i jest emitowany jednokrotnie dla instancji strony,
- home/category/guides/article/Product Bridge używają semantycznych `data-*`; podstawowy publiczny UI/routing/read model nie został zmieniony,
- optional scroll-depth z SEO spec nie został wdrożony w N5-004.

### Testy

- `NewsroomAnalyticsHooksTest` chroni publiczny render, event names i zakaz body/title/author w atrybutach telemetrycznych,
- `GoogleAnalyticsTagTest` chroni readiness signal istniejącego GA layer,
- `NewsroomHomePageTest` chroni stabilne module names i privacy-safe hooki na hubie,
- Browser Smoke #55 rozszerza `newsroom-article` o JS-enabled gtag stub: delayed readiness -> dokładnie jeden article view, click -> jeden event, generic module click oraz brak eventu dla non-link / `aria-disabled` target; wszystkie sześć newsroom jobs jest PASS.

### Dowód Quality Gate

- exact-head CI #384: **1106 passed / 20 051 assertions / 2 skipped**, PostgreSQL **7 passed / 94 assertions**, Pint PASS, frontend build PASS,
- Browser Smoke #55: PASS dla article, home, category, guides, topic i semantic-links,
- post-merge CI #385 na `main@5704b3c3a8acde029001e28567980c5de8e27cdf`: **1106 passed / 20 051 assertions / 2 skipped**, PostgreSQL **7 passed / 94 assertions**, Pint PASS, frontend build PASS.

### Poza zakresem N5-004

- własny backend/event store telemetryczny,
- ranking popularności i bot/dedupe policy,
- optional scroll depth,
- article-specific IndexNow,
- namespace-specific sitemap audit oraz static publication/freshness hardening.

---

## NEWSROOM-N5-005 — IndexNow integration review

### Status implementacji

**DONE w kodzie — PR #105 zmergowano na `main@1cc9f4bec8c7d7fc105fd3495664434cf4323eea`.** Finalny implementation head `44c8099ac2ea020bf5d9e31cfe547af8cb2149f7` przeszedł exact-head CI #392; post-merge CI #393 na exact `main` również zakończył pełny PASS.

### Cel

Re-use existing IndexNow queue/submission pipeline; nie tworzyć drugiego klienta ani traktować lokalnego `event_type` jak pola protokołu.

### Potwierdzony stan implementacji

- `ContentArticleIndexNowRequested` implementuje `ShouldDispatchAfterCommit`; side effect jest uruchamiany dopiero po skutecznym commit i rollback nie tworzy newsroom submission row,
- `QueueNewsroomArticleIndexNow` jest pojedynczym listenerem odkrywanym przez Laravel i reużywa istniejący `IndexNowQueueService`, `PublicUrlResolver`, dedupe/debounce/retry oraz URL safety filters; nie ma drugiego klienta ani drugiej kolejki,
- first publish -> canonical URL z lokalnym `EVENT_CREATED`, republish -> `EVENT_UPDATED`,
- substantive public update bez zmiany canonical path -> `EVENT_UPDATED`; semantic no-op nie enqueue'uje,
- archive przy obecnym kontrakcie detail `200`/robots nie enqueue'uje i nie jest traktowane jako delete,
- withdraw po ustanowieniu withdrawn/410 -> ten sam URL z lokalnym `EVENT_DELETED`,
- restore-to-review pozostaje bez enqueue; późniejszy publish po fresh review zgłasza URL jako `EVENT_UPDATED`,
- zmiana sluga wcześniej publikowanego artykułu po commit zgłasza old redirect path i new canonical jako dwa lokalne `EVENT_UPDATED`; public update ze zmianą sluga nie dodaje trzeciego duplikatu canonical enqueue,
- scheduled-before-time, noindex i `NEWSROOM_PUBLIC_ENABLED=false` są suppressowane; draft/in_review nie mają ścieżki automatycznego enqueue,
- listener izoluje exception kolejki przez `report()` i nie cofa już zatwierdzonej transakcji publikacji,
- `event_type` pozostaje lokalną metadaną `indexnow_url_submissions`; HTTP payload `IndexNowSubmissionService` nadal zawiera tylko `host`, `key`, opcjonalne `keyLocation` i `urlList`.

### Testy i Quality Gate

- `NewsroomIndexNowAutomationTest` pokrywa publish, republish/archive, withdraw/restore, substantive update/no-op, slug old+new bez duplikacji, scheduled/noindex/gate suppression, outer rollback i izolację awarii kolejki,
- `IndexNowQueueAutomationTest` chroni brak `event_type` w HTTP payload,
- exact-head CI #392: **1116 passed / 20 095 assertions / 2 skipped**, PostgreSQL **7 passed / 94 assertions**, Pint PASS, frontend build PASS,
- post-merge CI #393 na `main@1cc9f4bec8c7d7fc105fd3495664434cf4323eea`: **1116 passed / 20 095 assertions / 2 skipped**, PostgreSQL **7 passed / 94 assertions**, Pint PASS, frontend build PASS.

### Poza zakresem N5-005

- produkcyjne włączenie/deploy fazy 2 IndexNow i weryfikacja w Bing Webmaster Tools,
- sitemap namespace/age/tag/shard audit hardening — N5-006,
- child-before-index atomic publication, obsolete-shard cleanup i dirty/version refresh coordinator — N5-007,
- 200/202 nadal oznacza tylko przyjęcie zgłoszenia, nie gwarancję crawl/index/ranking.

---

## NEWSROOM-N5-006 — Extend existing SEO/sitemap audits

### Status implementacji

**DONE — implementation merged + post-merge Quality Gate PASS.**

Potwierdzony stan na `main@18300baf3a91ffc5e3c549ab664c471bfd34ffe4`:

- istniejący `SeoSitemapAuditor` został rozszerzony; nie powstał równoległy sitemap validator,
- auditor sprawdza duplicate index loc, brakujący child file, canonical/current article eligibility, noindex/draft/non-public author/inactive category/redirect-source entries, Google News namespace/required tags/identity/language/title/date/2-day window, article/news topology i shard overlap oraz obsolete unreferenced article/news shard files,
- istniejący generic URL-count/byte-size guard nadal jest reużywany również dla newsroomowych plików,
- `NewsroomSemanticLinkService::auditAll()` wykonuje site-wide QA na tym samym resolverze semantic links co publiczne renderowanie,
- cienka komenda `newsroom:audit-links` raportuje orphan article bez gwarantowanego crawlable inbound, pusty publicznie cytowany source URL, expired breaking, non-public topic/legal/sign targets, niekwalifikowany/broken related target lub canonical URL oraz broken topic featured-article relation,
- public gate wyłączony => link audit kończy się bez fałszywych błędów dla dark deployment,
- dodano `NewsroomSeoSitemapAuditTest` i `NewsroomLinkAuditTest`; nie dodano migracji, schema ani drugiego graph/sitemap subsystemu.

### Potwierdzone testy i Quality Gate

- finalny implementation HEAD `64feb88b614b737a69784d43e86e97591aaec3d5`,
- pre-merge CI #397: 1127 passed / 20 131 assertions / 2 skipped, PostgreSQL 7/94, Pint PASS, frontend build PASS,
- Browser Smoke #57: PASS dla article/home/category/guides/topic/semantic-links,
- PR #107 zmergowany do `main@18300baf3a91ffc5e3c549ab664c471bfd34ffe4`,
- post-merge CI #398: 1127 passed / 20 131 assertions / 2 skipped, PostgreSQL 7/94, Pint PASS, frontend build PASS.

### Poza zakresem N5-006

- child-before-index atomic publication,
- generator-side obsolete-shard cleanup po przełączeniu indexu,
- dirty/version freshness coordinator i częsty scheduler lock,
- produkcyjna static/Nginx/CDN/GSC verification.

Te elementy pozostają NEWSROOM-N5-007.

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

### Potwierdzony stan implementacji po PR #109, #112, #115, #117, #119 i #127

NEWSROOM-N5-007 pozostaje **IN PROGRESS**.

Potwierdzone podkroki:
- PR #109: `SeoSitemapGenerator::generate()` buduje cały replacement set i waliduje protocol limits oraz XML przed modyfikacją opublikowanych plików,
- child XML są zapisywane atomowo przed głównym `sitemap.xml`,
- root `sitemap.xml` jest przełączany dopiero po zapisaniu child files,
- obsolete zarządzane `articles*.xml` / `news*.xml` są usuwane dopiero po przełączeniu root indexu,
- niezwiązane XML w `public/sitemaps` nie są usuwane przez ten cleanup,
- PR #112: `NewsroomSeoArtifactRefreshCoordinator` utrzymuje version/clean-version w skonfigurowanym cache store oraz shared lock,
- `newsroom:refresh-seo-artifacts-if-dirty` kończy się tanio przy clean state, generuje i audytuje tylko przy dirty, a failure albo zmiana version podczas pracy nie czyści dirty state,
- after-commit article workflow/public-read/home-placement events ustawiają dirty signal przez auto-discovered listenery; category/topic/author observers również działają after commit,
- produkcyjny scheduler uruchamia lekką komendę co minutę z `onOneServer()` i `withoutOverlapping()`,
- istniejący daily `seo:refresh-sitemaps` pozostaje niezależnym recovery path; nie dodano queue workera ani zmiany `QUEUE_CONNECTION=sync`.
- PR #115: produkcyjny wzorzec Nginx dla `/robots.txt`, `/sitemap.xml` i `/sitemaps/*` ustawia jawny Content-Type, public `Cache-Control: max-age=3600`, ETag, `if_modified_since exact` i `X-Content-Type-Options: nosniff`,
- `scripts/production-seo-delivery-smoke.sh` potrafi zweryfikować HTTP 200, Content-Type, public cache, brak `Set-Cookie`, obecność validatora i conditional 304 dla robots/root/static sitemap; dla feedu akceptuje 404 tylko gdy public gate jest wyłączony,
- `NginxSeoStaticDeliveryConfigurationTest` chroni repo-level Nginx contract dla crawler assets.
- PR #117: `.github/workflows/production-seo-delivery-smoke.yml` uruchamia istniejący smoke na GitHub-hosted runnerze; PR runs są REPORT-ONLY, a manualny `workflow_dispatch` jest domyślnie STRICT i zapisuje artifact raportu.
- PR #119: istniejący `NewsroomSeoArtifactRefreshCoordinatorTest` ma dedykowany regression runtime definition schedulera (`everyMinute`, production-only, `onOneServer()`, `withoutOverlapping()`) oraz shared refresh-lock contention; przy zajętym locku komenda kończy się sukcesem bez generatora/audytora i zachowuje dirty state.
- PR #127: `deploy/mikrus/deploy.sh` ma jawny, opt-in `SEO_RELEASE=1`; po standardowym deployu odświeża i audytuje sitemap artifacts, waliduje aktywną składnię `nginx -t` i uruchamia istniejący publiczny `production-seo-delivery-smoke.sh`. `REQUIRE_NEWSROOM_PUBLIC=1` mapuje się na wymagany feed smoke. Guard nie kopiuje ani nie przeładowuje vhosta i nie włącza `NEWSROOM_PUBLIC_ENABLED`; nadal nie zastępuje faktycznego zastosowania produkcyjnego Nginx/runtime.

Implementation PR #109 miał finalny head `dbcd5cdb2f0e6663c998f930c034624f8fe36bf4` i został zmergowany do `main@1744a93fd8f0bddfe7fc5bff90b146fdea24b016`. Exact-head CI #403 oraz post-merge CI #404 zakończyły pełny PASS: 1130 passed / 20 144 assertions / 2 skipped, PostgreSQL 7/94, Pint 1093 files PASS i frontend build PASS.

Implementation PR #112 miał finalny head `6f1c3b99d11796f74987c7fe640302b2d76578b7` i został zmergowany do `main@79b6de0276ba8cdce500c366a4f98098e528dcd8`. Exact-head CI #409 i post-merge CI #410 zakończyły pełny PASS: 1136 passed / 20 182 assertions / 2 skipped, PostgreSQL 7/94, Pint 1101 files PASS i frontend build PASS.

Implementation PR #117 miał finalny head `2a46398e781b76c260617f5e8990191917a36516` i został zmergowany do `main@38102d1c3867d13666308ee657d6579fcb5f1d7a`. Exact-head CI #420 i post-merge CI #421 zakończyły pełny PASS: 1137 passed / 20 207 assertions / 2 skipped, PostgreSQL 7/94, Pint 1102 files PASS i frontend build PASS (odpowiednio 9.66 s i 5.49 s). Dedykowany PR report-only Production SEO Delivery Smoke również wykonał się na GitHub Actions, ale underlying smoke zakończył się kodem 1 z powodu publicznego `/robots.txt` zwracającego `Cache-Control: max-age=14400` zamiast oczekiwanego `public, max-age=3600`; nie jest to production PASS.

Test-evidence PR #119 miał finalny head `30935470028d5fe830ea1f2809d4ac8edcf97b0d` i został zmergowany do `main@abb42032370a185a4cf7d7ee9a474ccbca6d86a4`. Exact-head CI #424 i post-merge CI #425 zakończyły pełny PASS: 1139 passed / 20 219 assertions / 2 skipped, PostgreSQL 7/94, Pint 1102 files PASS i frontend build PASS (odpowiednio 9.86 s i 9.04 s). Zakres #119 zmienił wyłącznie istniejący test; kod produkcyjny i decyzje architektoniczne pozostały bez zmian.

Deployment-guard PR #127 miał finalny head `e84758d911ffc4f6ff10a04f88a2b8c48beb22c6` i został zmergowany do `main@b26317bd979dd0c372b7a9341a8358f2599a9550`. Exact-head CI #477 oraz post-merge CI #478 zakończyły pełny PASS: 1140 passed / 20 229 assertions / 2 skipped, `newsroom-postgres` PASS, Pint 1102 files PASS i frontend build PASS. Świeży PR report-only Production SEO Delivery Smoke ponownie miał underlying exit `1` z powodu live `/robots.txt` `Cache-Control: max-age=14400`; to potwierdza drift i nie jest production PASS.

Topology evidence z aktualnych dokumentów wdrożeniowych potwierdza bieżący kontrakt produkcyjny jako 1x VPS Mikrus 4.1 z Nginx/PHP/PostgreSQL/Redis na jednej maszynie, lokalnym `public/` i jednym cronem `schedule:run`. Dla tej topologii same-filesystem atomic replace jest właściwym modelem. Ewentualne przejście na wiele web node'ów ponownie otwiera wymóg wspólnej dystrybucji artifact setu; Redis lock/`onOneServer()` nie synchronizuje lokalnych plików między node'ami.

Nadal niewykonane w N5-007:
- zastosowanie aktualnego Nginx configu na produkcji i udany STRICT `workflow_dispatch` workflow `Production SEO Delivery Smoke`; report-only run z PR #117 wykazał obecnie rozbieżność Cache-Control dla `/robots.txt`,
- potwierdzenie Cloudflare/origin HTTP evidence dla Content-Type/cache/braku Set-Cookie/ETag lub Last-Modified/conditional 304,
- production Search Console / GSC verification.

### Robots compatibility

- nie usuwać `RobotsController` w tym tasku,
- zweryfikować produkcyjny `/robots.txt` przez HTTP i warstwę Nginx/Cloudflare,
- `public/robots.txt` pozostaje preferowanym kontraktem zgodnie z SEO-SITEMAP-REPAIR-PLAN,
- cleanup duplikatu tylko w osobnym późniejszym PR.

### Topology gate

Potwierdzony aktualny kontrakt deploymentu:
- `docs/INFRA-MVP-MIKRUS-4.1-R2.md`, `docs/DEPLOYMENT-RUNBOOK.md` i `docs/RUNBOOK-OPS.md` opisują 1x VPS Mikrus 4.1,
- Nginx + PHP-FPM + PostgreSQL + Redis działają na tej samej maszynie,
- statyczny `public/` jest lokalny dla tego app node,
- jeden systemowy cron uruchamia `php artisan schedule:run` co minutę.

Dla obecnego single-node kontraktu same-filesystem atomic replace jest wystarczającym modelem, więc topology gate jest spełniony na poziomie aktualnej kanonicznej topologii repo/deployment docs.

Jeśli topologia zmieni się na wiele web node'ów:
- wygenerowany set musi trafić atomowo/spójnie do współdzielonego volume, artifact distribution lub edge/origin wspólnego dla wszystkich node'ów,
- `onOneServer()`/Redis lock zapobiega podwójnej generacji, ale **nie synchronizuje lokalnych plików pomiędzy node'ami**,
- topology gate trzeba wtedy ponownie otworzyć i potwierdzić nowy model dystrybucji.

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

### Status implementacji

**DONE — implementation + exact-head Browser Smoke + post-merge CI potwierdzone na `main`.**

Zakres został zrealizowany przez PR #121 bez zmiany produkcyjnej implementacji ani architektury newsroomu. Zmienione pliki to wyłącznie:

- `.github/workflows/browser-smoke.yml`,
- `package.json`,
- `scripts/e2e-newsroom-golden-path.mjs`.

### Potwierdzony golden path

Admin:

- login admina,
- utworzenie draftu przez rzeczywisty komponent Filament,
- ordered `body_blocks`,
- public source,
- topic + question relation,
- zapis hero przez newsroom media service wraz z focal point,
- prywatny preview z `private, no-store` i `noindex`,
- `draft -> in_review -> reviewed -> published` przez rzeczywiste akcje workflow.

Public:

- `/aktualnosci` hub -> opublikowany artykuł,
- artykuł -> powiązane publiczne pytanie,
- artykuł -> product CTA.

Test używa rzeczywistego stanu Filament Builder/Repeater/FileUpload, rzeczywistego confirmation modal oraz odświeżenia edit UI pomiędzy transitionami. Toast nie jest twardym kontraktem testu; o powodzeniu transition decyduje potwierdzony stan domenowy.

### Evidence

- finalny implementation/test HEAD PR #121: `76d0fa7d428b11f2db9d901eac698c7a169722ff`,
- Browser Smoke #84: PASS, w tym `newsroom-golden-path` oraz wszystkie pozostałe newsroom Browser Smoke,
- exact-head CI #454: PASS — 1139 passed / 20 219 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS, frontend build PASS,
- merge PR #121: `main@b0b29d9b4a3337f4e527334f6ead8b1be85303ad`,
- post-merge CI #455 na tym exact `main`: PASS — 1139 passed / 20 219 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS, frontend build PASS.

### Boundary

N6-001 nie zalicza produkcyjnych gate'ów N5-007 ani scheduled-publication production smoke. Następnym logicznym taskiem N6 pozostaje **NEWSROOM-N6-002 — Scheduled publication production smoke**.

---

## NEWSROOM-N6-002 — Scheduled publication production smoke

### Status implementacji

**DONE — executable production-mode release smoke + exact-head/post-merge CI potwierdzone.**

Zakres został zrealizowany przez PR #123 bez zmiany produkcyjnej logiki publikacji ani architektury newsroomu. Zmienione pliki to wyłącznie:

- `.github/workflows/newsroom-scheduled-publication-smoke.yml`,
- `package.json`,
- `scripts/newsroom-scheduled-publication-smoke.mjs`.

### Potwierdzony scenariusz

Smoke działa na realnym zegarze, bez `Carbon::setTestNow` i bez bezpośredniego wymuszania publikacji w bazie:

- tworzy scheduled sample przez istniejący `ContentArticlePublishingService`,
- przed terminem potwierdza brak artykułu w public article route, hubie, Atom feedzie, `articles.xml` i `news.xml`,
- przed terminem uruchamia istniejące `newsroom:publish-due` i potwierdza `published=0 failed=0`,
- po realnym due uruchamia to samo `newsroom:publish-due` i potwierdza `published=1 failed=0 skipped=0`,
- potwierdza audit trigger `scheduler` oraz przejście workflow do `published`,
- potwierdza dirty/version advance po publikacji,
- uruchamia istniejące `newsroom:refresh-seo-artifacts-if-dirty`,
- potwierdza publiczną widoczność artykułu, obecność w feedzie, `articles.xml` i Google News `news.xml`,
- potwierdza zmianę hashy sitemap oraz clean version po refreshu bez oczekiwania na daily cron.

HTTP smoke działa przeciw stabilnemu Docker app+Nginx originowi z `APP_ENV=production`, kanonicznym `APP_URL=https://prawkonaraz.pl` i współdzielonym SQLite/storage pomiędzy hostowymi komendami a aplikacją.

### Evidence

- finalny implementation/test HEAD PR #123: `2426f19215326a1c3386e8185222030b634ed795`,
- Newsroom Scheduled Publication Smoke #10: PASS,
- Browser Smoke #94: PASS, w tym `newsroom-golden-path` oraz wszystkie pozostałe newsroom Browser Smoke,
- exact-head CI #467: PASS — `quality` + `newsroom-postgres`,
- merge PR #123: `main@afaa44dca0012144ab842c9195856eebec4553d3`,
- post-merge CI #468 na tym exact `main`: PASS — `quality` + `newsroom-postgres`.

Smoke evidence potwierdza m.in. baseline SEO artifact refresh do version 3, `published=0` przed terminem, `published=1` po realnym due, a następnie refresh do version 4 z `articles.xml` i `news.xml` zawierającymi nowy artykuł.

### Boundary

To jest powtarzalny **production-mode release smoke w izolowanym GitHub Actions environment**. Nie jest to dowód mutacji ani publikacji próbnej na live production database i nie należy go tak opisywać.

NEWSROOM-N5-007 pozostaje IN PROGRESS z osobnymi live-production Nginx/STRICT HTTP/Cloudflare/GSC gate'ami. Następnym logicznym taskiem N6 jest **NEWSROOM-N6-003 — Enterprise SEO production validation**.

---

## NEWSROOM-N6-003 — Enterprise SEO production validation

### Status

**IN PROGRESS — repo-level production-validation harness jest wdrożony i zielony w CI, ale live production nie przechodzi jeszcze STRICT contractu.**

Zakres bazowy pozostaje bez zmian:

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

### Potwierdzony repo-level podkrok

PR #125 dodał wyłącznie validation tooling:

- `.github/workflows/newsroom-enterprise-seo-production-validation.yml`,
- `scripts/newsroom-enterprise-seo-production-validation.mjs`,
- `package.json`.

Nie zmieniono produkcyjnej logiki aplikacji, publikacji, routingu ani architektury.

Workflow działa:

- w PR jako REPORT-ONLY przeciw live production,
- przez ręczny `workflow_dispatch` domyślnie jako STRICT,
- reużywa istniejący `scripts/production-seo-delivery-smoke.sh`,
- sprawdza homepage canonical + WebSite/Organization identity,
- sprawdza crawlability/dimensions Organization logo,
- sprawdza newsroom hub public-vs-placeholder indexing contract,
- sprawdza sitemap index/child HTTP/XML/no-Set-Cookie contract,
- sprawdza rollout-gated Atom feed i discovery,
- przy podaniu próbek sprawdza article/category/topic canonical, graph identity oraz article visible-date vs `datePublished`/`dateModified`.

PR #129 rozszerzył ten sam validator o canonical-host migration evidence dla produkcyjnego originu bez zmiany aplikacji/Nginx/rollout:

- `http://prawkonaraz.pl/`, `https://www.prawkonaraz.pl/`, `http://www.prawkonaraz.pl/` i historyczny `https://prawkoapp.pl/` muszą najpierw redirectować zamiast serwować własne 200,
- każdy wariant musi zakończyć się `https://prawkonaraz.pl/` z HTTP 200,
- redirect chain jest zapisywany w raporcie; wielohop jest warningiem, nie automatycznym production failure,
- custom/non-production `BASE_URL` jawnie pomija project-specific host checks.

PR #127 domknął osobny repo-level deployment-drift guard bez zmiany architektury rollout:

- `deploy/mikrus/deploy.sh` obsługuje opt-in `SEO_RELEASE=1`,
- w tym trybie wykonuje `seo:refresh-sitemaps`, `seo:audit-sitemaps`, `nginx -t` i istniejący publiczny production SEO delivery smoke,
- `REQUIRE_NEWSROOM_PUBLIC=1` wymaga publicznego feed contractu,
- guard nie stosuje sam aktywnego vhosta Nginx i nie przełącza `NEWSROOM_PUBLIC_ENABLED`; live deploy nadal pozostaje osobnym wymaganym krokiem.

### Evidence repo/CI

- finalny HEAD PR #125: `8a64f81c15fb6c52e2a90db26c5c65c9ad999801`,
- Newsroom Enterprise SEO Production Validation #2: workflow PASS w REPORT-ONLY mode,
- Newsroom Scheduled Publication Smoke #12: PASS,
- Browser Smoke #96: PASS dla wszystkich newsroom jobs, w tym golden path,
- exact-head CI #472: PASS — 1139 passed / 20 219 assertions / 2 skipped, `newsroom-postgres` PASS, frontend build PASS,
- merge PR #125: `main@0344427cdf42804a037c688df54da6216f243ca6`,
- post-merge CI #473 na tym exact `main`: PASS — 1139 passed / 20 219 assertions / 2 skipped, `newsroom-postgres` PASS, frontend build PASS.
- deployment-guard PR #127 finalny HEAD `e84758d911ffc4f6ff10a04f88a2b8c48beb22c6`: CI #477 PASS — 1140 passed / 20 229 assertions / 2 skipped, `newsroom-postgres` PASS, Pint 1102 files PASS, frontend build PASS,
- merge PR #127: `main@b26317bd979dd0c372b7a9341a8358f2599a9550`; post-merge CI #478 również pełny PASS.
- canonical-host validator PR #129 finalny HEAD `379f673e681c504322dbcabe370de110ce03dcc5`: Enterprise SEO Production Validation #3 workflow success w REPORT-ONLY mode oraz exact-head CI #481 PASS — 1140 passed / 20 229 assertions / 2 skipped, `newsroom-postgres` PASS, Pint 1102 files PASS, frontend build PASS,
- merge PR #129: `main@8ea54ba1a8b1ca5349a468353e519fa7262a16d4`; post-merge CI #482 również pełny PASS — 1140 passed / 20 229 assertions / 2 skipped, `newsroom-postgres` PASS, Pint 1102 files PASS, frontend build PASS.

### Potwierdzony live-production baseline

REPORT-ONLY run nie jest production PASS. Wykrył rzeczywiste rozbieżności:

- `/robots.txt` nadal zwraca `Cache-Control: max-age=14400` zamiast repo contract `public, max-age=3600`,
- live homepage zwraca self-canonical i crawlable Organization logo >=112x112, ale aktualna odpowiedź nie zawiera jeszcze stabilnych `WebSite @id=https://prawkonaraz.pl/#website` i `Organization @id=https://prawkonaraz.pl/#organization`, które emituje aktualny kod `main`,
- live `/aktualnosci` zwraca 200 jako cienka indexable surface bez canonical/H1/meta/structured data i bez wymaganego przez aktualny gate `X-Robots-Tag: noindex, follow`,
- production sitemap index ma poprawne sprawdzone child XML responses bez `Set-Cookie`, ale nie zawiera jeszcze newsroom article/news sitemap coverage,
- `/aktualnosci/feed.xml` pozostaje rollout-gated 404.

Search Console evidence z 2026-09-19:

- property: `sc-domain:prawkonaraz.pl`,
- główny `https://prawkonaraz.pl/sitemap.xml` jest submitted, ostatnio pobrany 2026-09-19, bez reported warnings/errors,
- URL Inspection dla homepage: `Duplicate, Google chose different canonical than user`, robots allowed, indexing allowed, last crawl 2026-08-27,
- URL Inspection dla `/aktualnosci`: `URL is unknown to Google`,
- brak jeszcze Search Analytics rows dla `/aktualnosci` w settled window kończącym się 2026-09-16.
- dodatkowy URL Inspection 2026-09-19: `http://prawkonaraz.pl/` = `Page with redirect`; `https://www.prawkonaraz.pl/` i `http://www.prawkonaraz.pl/` = `URL is unknown to Google`; homepage nadal wskazuje `https://prawkoapp.pl/` jako referring URL,
- Enterprise SEO Production Validation #3 potwierdził live HTTP migration: `http://prawkonaraz.pl/`, `https://www.prawkonaraz.pl/` i `https://prawkoapp.pl/` kończą na canonical homepage w jednym redirect hop; `http://www.prawkonaraz.pl/` kończy poprawnie, ale ma dwa redirect hops (warning),
- ten wynik zawęża diagnozę: aktualny Google-selected canonical mismatch homepage nie jest potwierdzony jako aktywna awaria host-migration; przyczyna pozostaje nierozstrzygnięta do ponownego crawl/deploy i dalszego GSC evidence.

### Boundary / następny krok

N6-003 nie jest DONE i nie wolno zapisywać report-only workflow success jako zielonego production gate'u. Następny krok N6-003 wymaga aktualnego deploy/runtime na produkcji, zielonego STRICT runu oraz reprezentatywnych live article/category/topic samples do URL Inspection/schema/canonical/date validation.

NEWSROOM-N5-007 również pozostaje IN PROGRESS; jego Nginx/STRICT static-delivery/Cloudflare/GSC evidence jest częściowo współdzielone z N6-003, ale oba taski zachowują własny zakres i status.

---

## NEWSROOM-N6-004 — Performance pass

Status: **IN PROGRESS**

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

### Potwierdzony repo-level baseline po PR #131

PR #131 rozszerzył istniejące Browser Smoke harnessy dla article/hub/category bez zmiany produkcyjnej logiki aplikacji. Harnessy zapisują i raportują:

- Laravel kernel render duration,
- SQL query count,
- SSR HTML bytes,
- lokalne obrazy z rzeczywistą wagą i intrinsic dimensions,
- użyte built JS/CSS asset bytes.

Finalny implementation/test HEAD: `0d1e16a66faaf7481b30ccde589aca291e398b83`.

Exact-head evidence:

- Browser Smoke #101: pełny PASS dla newsroom article/home/category/topic/guides/semantic-links/golden-path,
- CI #489: pełny PASS — 1140 passed / 20 229 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS, frontend build PASS.

Merge PR #131: `main@f147af4fb504ffbf9b953701c891c275ecf2d98d`.

Post-merge CI #490: pełny PASS — 1140 passed / 20 229 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS, frontend build PASS.

Potwierdzone baseline'y z Browser Smoke #101:

- article: 14 queries, 61 098 B HTML, 73.48 ms kernel render,
- hub: 33 queries, 95 354 B HTML, 87.75 ms kernel render,
- category page 1: 9 queries, 96 622 B HTML, 48.66 ms kernel render,
- category page 2: 7 queries, 62 499 B HTML, 29.15 ms kernel render,
- wspólny WebP: 71 342 B, 1000×750 px,
- built CSS: 402 335 B,
- built JS: 15 225 B.

Regresyjne repo budgets są oparte na tym zmierzonym baseline:

- article: query ≤ 18, HTML ≤ 75 000 B,
- hub: query ≤ 40, HTML ≤ 120 000 B,
- category: query ≤ 12, HTML ≤ 120 000 B,
- obrazy lokalne ≤ 100 000 B,
- CSS ≤ 450 000 B,
- JS ≤ 20 000 B,
- intrinsic image dimensions muszą być rozpoznawalne.

Kernel render time jest raportowany, ale nie jest twardym gate'em, ponieważ współdzielony GitHub Actions runner nie daje stabilnego środowiska do wiarygodnego progu czasowego.

### Potwierdzony CLS hardening / pozostały zakres

PR #133 zamknął potwierdzony repo-level finding `missing_declared_dimensions = 1` bez zmiany layoutu, assetu, routingu ani decyzji architektonicznych:

- `resources/views/components/site/login-drawer.blade.php` i `resources/views/components/site/register-drawer.blade.php` deklarują zweryfikowane `width="1000" height="750"` dla wspólnego `hero-composite-v3.webp`,
- istniejący N6-004 performance budget failuje teraz również przy dowolnym lokalnym obrazie z `missing_declared_dimensions > 0`,
- istniejący Browser Smoke uruchamia się również po zmianie obu drawerów i `scripts/newsroom-performance-metrics.mjs`; nie utworzono nowego workflow.

Finalny exact-head PR #133: `18e266dcb5ea45b9757a3b74a8ed1ec65e2ffd19`.

Potwierdzone evidence:

- Browser Smoke #102: wszystkie newsroom joby PASS; article, home oraz category page 1/2 raportują `missing_intrinsic_dimensions = 0` i `missing_declared_dimensions = 0`, a zmierzony obraz ma intrinsic i declared dimensions 1000×750,
- CI #494: pełny PASS — 1140 passed / 20 229 assertions / 2 skipped, `newsroom-postgres` PASS, Pint 1102 files PASS, frontend build PASS,
- merge PR #133: `main@f5fba3c069849078aea032429aed7a139d6a5c62`,
- post-merge CI #495: pełny PASS — 1140 passed / 20 229 assertions / 2 skipped, `newsroom-postgres` PASS, Pint 1102 files PASS, frontend build PASS.

Repo-level brak deklarowanych dimensions jest zamknięty. Dokumentacyjne domknięcie tego repo-level podkroku przeszło exact-head CI #496 na PR #134 i zostało zmergowane jako `main@f68509d7e3add7b14386f33f2b4b377bc60829e1`. Post-merge CI #497 ujawnił niezależny, niedeterministyczny failure w `TrafficSignLearningTest`: test tworzył jawne `A-1`, a późniejszy negatywny fixture bez jawnego `code` mógł losowo otrzymać ten sam kod z `TrafficSignFactory` i naruszyć UNIQUE constraint `traffic_signs.code`. Merge commit PR #134 nie miał żadnych różnic plikowych względem jego exact-head SHA, więc failure nie był regresją dokumentacji ani N6-004.

PR #135 ustabilizował wyłącznie testowy fixture przez jawne unikalne kody dla pięciu negatywnych rekordów, bez zmiany produkcyjnej logiki, schematu, globalnej factory ani architektury. Exact-head `4f992a4f9716fd8a7aa8138b99f97b4e834a9b9b` przeszedł CI #498 — 1140 passed / 20 229 assertions / 2 skipped, `newsroom-postgres` PASS, Pint 1102 files PASS, frontend build PASS. Merge PR #135: `main@d15976687e56fd825a1eeb29d24fbca6fdf36c04`; post-merge CI #499 również pełny PASS z tym samym bilansem testów i buildów.

Kod, testowy gate oraz dokumentacja repo-level N6-004 są więc ponownie spójne i zielone. N6-004 pozostaje jednak **IN PROGRESS**, ponieważ produkcyjne LCP/INP/CLS/TTFB nadal wymagają rollout/live production evidence zgodnie z runbookiem.

---

## NEWSROOM-N6-005 — Security and rollout-gate pass

Status: **IN PROGRESS**

- XSS,
- preview admin-only/private-no-store,
- admin policy / no access widening,
- stale-write/concurrency,
- AuditLog data minimization,
- upload validation,
- source URL no server-side fetch — **repo-level PASS potwierdzony**,
- verify `NEWSROOM_PUBLIC_ENABLED=false` before cutover and controlled enable during release.

### Potwierdzony podkrok: source URL pozostaje metadanymi / no server-side fetch

PR #137 dodał test regresyjny do istniejącego `NewsroomPublishingServiceTest` bez zmiany produkcyjnej logiki ani architektury. Test:

- blokuje wszystkie outbound requesty przez Laravel HTTP client,
- używa loopback i link-local URL jako źródeł,
- przechodzi przez rzeczywisty workflow `review -> publish -> Apply public update`,
- wymaga `Http::assertNothingSent()`, więc przyszła próba dereferencji source URL przez ten path ma failować test.

Finalny HEAD PR #137: `68660f5caa08e5aed82f3bb62cd48fef345d1171`.

Evidence:

- exact-head CI #502: pełny PASS — 1141 passed / 20 231 assertions / 2 skipped, `newsroom-postgres` 7 passed / 94 assertions, Pint 1102 files PASS, frontend build PASS,
- merge PR #137: `main@f73153ccbb9522201a98e0754d71de269ea7c6f7`,
- post-merge CI #503: pełny PASS z tym samym bilansem testów i buildów.

Ten podkrok nie zmienia polityki URL: source URL nadal może być poprawnym `http`/`https` adresem, ale backend nie pobiera go w tym workflow.

### Repo-level security audit po PR #137 / #138

Ponowny audyt aktualnego `main@2ba4cffbe5d065724d876b35394bc50b2051d38e` potwierdził, że pozostałe techniczne punkty N6-005 mają już istniejące wykonanie i regression coverage:

- XSS / executable rich text / unsafe link / raw iframe oraz `embed` fail-closed: `NewsroomBodyContractTest` i admin create regression,
- preview: moderator/student denied, admin-only, `private, no-store`, noindex/nofollow, brak public analytics i brak wycieku private source evidence: `NewsroomArticlePreviewTest`,
- admin policy / no access widening: `ContentArticleResourceTest` odrzuca moderatora i studenta; panel pozostaje administrator-only,
- stale-write/concurrency: draft/public article update i HomeComposer mają deterministic stale-state rejection; same-second child/source conflict nie wykonuje last-write-wins,
- AuditLog data minimization: publish/public-update/featured/breaking regressions utrzymują allowlisted metadata bez body/lead/editorial/private source note payloadów,
- upload validation: `NewsroomMediaStorageTest` sprawdza allowlist raster MIME, wyłączenie SVG, actual-bytes MIME/dimensions, byte limits, managed immutable namespace i path traversal,
- source URL no server-side fetch: PR #137 wymusza brak outbound HTTP przy loopback/link-local source URLs,
- public gate ma bezpieczny repo default: `config/newsroom.php` i `.env.example` utrzymują `NEWSROOM_PUBLIC_ENABLED=false`, a `NewsroomPublicGateTest` pokrywa dark-deploy suppression i kontrolowane `true` dla istniejących public surfaces.

Post-merge CI #505 na `main@2ba4cffbe5d065724d876b35394bc50b2051d38e` zakończył pełny PASS: 1141 passed / 20 231 assertions / 2 skipped, PostgreSQL 7/94, Pint 1102 files PASS i frontend build PASS.

**N6-005 pozostaje IN PROGRESS wyłącznie dla runtime/release evidence**: zgodnie z istniejącym runbookiem Phase A wymaga faktycznego potwierdzenia `NEWSROOM_PUBLIC_ENABLED=false` na produkcji przed cutoverem, a Phase B jawnego ustawienia `true`, odświeżenia config cache i publicznych smoke checks. `deploy/mikrus/deploy.sh` celowo nie przełącza tego gate automatycznie.

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

### Status implementacji

**DONE — publiczny publisher-transparency contract jest wdrożony i potwierdzony na `main`.**

PR #140 został zmergowany jako `main@6defc5fe82fef6c6724d67659486d58736231386`. Finalny implementation HEAD `ed7ce64c997d5fc74163bddd6772b5883f78dab9` przeszedł wymagane exact-head gates, a post-merge CI #511 na dokładnym merge SHA zakończył się pełnym PASS:

- `quality`: 1142 passed / 20 246 assertions / 2 skipped,
- Pint: 1103 files PASS,
- frontend build PASS,
- `newsroom-postgres`: PASS.

Faktycznie wdrożony zakres obejmuje:

- publiczną stronę `/zasady-redakcyjne` z kanonicznym SEO, breadcrumbs i WebPage schema,
- linkowanie do zasad z publicznego artykułu, footera oraz strony `O nas`,
- `NewsArticle.publishingPrinciples` wskazujące realną publiczną stronę,
- publiczne opisanie autorstwa, źródeł, weryfikacji, korekt, konfliktów interesów i kontraktu dotyczącego ewentualnych materiałów sponsorowanych.

N6-009 **nie** dodało migracji, nowych pól artykułu ani zmian RBAC/workflow dla sponsoringu. Zachowana pozostaje decyzja architektoniczna, że materiały sponsorowane są poza newsroom v1.

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

## NEWSROOM-N6-010 — Correction workflow hardening

### Status

**DONE — NEWSROOM-N6-010 zmergowano przez PR #146 jako `main@c58feafe6cffbb8bf54bbfcd0b3f1d4587fee97d` po pełnym exact-head i post-merge Quality Gate.**

### Potwierdzony stan implementacji

- finalny implementation HEAD: `9abb721a69bfb9a8d9a50c5e881166ad189d68a6`,
- exact-head CI #522: 1149 passed / 20 300 assertions / 2 skipped, `newsroom-postgres` PASS, Pint 1103 files PASS, frontend build PASS,
- Newsroom Scheduled Publication Smoke #13: PASS; brak regresji scheduler/feed/sitemap refresh,
- merge PR #146: `main@c58feafe6cffbb8bf54bbfcd0b3f1d4587fee97d`,
- post-merge CI #523: 1149 passed / 20 300 assertions / 2 skipped, `newsroom-postgres` PASS, Pint 1103 files PASS, frontend build PASS,
- Browser Smoke nie był triggerowany przez path contract dla zmian Filament/service; publiczny renderer `correction_note` nie był zmieniany i jego istniejący public feature regression pozostał bez zmian.

Faktycznie wdrożono dedykowane `Apply correction` na bazie tego samego stale-safe, atomowego `Apply public update` boundary. Korekta wymaga niepustego publicznego `correction_note` oraz istniejącego fresh-review contractu, zapisuje content + note w jednej transakcji, aktualizuje `last_substantive_update_at` przy semantycznej zmianie i tworzy allowlisted `content_article.corrected` AuditLog bez pełnego body/lead/note payloadu. Nie dodano revision/snapshot systemu, nowych pól, migracji ani zmian RBAC.

### Cel

Zmaterializować istniejący kontrakt `Apply correction` z `NEWSROOM-ADMIN-CMS-SPEC.md` §28.1 oraz politykę korekt z `NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md` §16/§34 bez dodawania revision/snapshot systemu.

### Zakres

- dedykowana akcja `Apply correction` wyłącznie dla publicznie widocznego artykułu,
- wymagany krótki publiczny `correction_note` dla istotnej korekty,
- wymagany aktualny/fresh review zgodnie z istniejącą policy,
- re-use istniejącego stale-safe, atomowego `Apply public update` transaction/write path zamiast drugiej ścieżki zapisu,
- treść + correction note publikowane w jednym commit,
- istotna korekta aktualizuje `last_substantive_update_at`,
- AuditLog zachowuje `User` actora i allowlisted metadata bez pełnego body/lead/private notes,
- publiczny renderer nadal pokazuje correction note z istniejącego pola,
- drobna korekta bez wpływu na sens nadal może użyć zwykłego `Apply public update` bez correction note.

### DoD

- brak możliwości zatwierdzenia istotnej korekty bez correction note i fresh review,
- stale token / validation failure nie pozostawia częściowej korekty,
- korekta używa istniejącego domain service/write boundary, bez równoległej logiki publikacji,
- testy pokrywają success, brak note, stale state, brak fresh review, atomic rollback i AuditLog minimization,
- exact-head CI + wymagane browser/feature regression PASS przed merge,
- docs po implementacji opisują wyłącznie potwierdzony stan.

---

## NEWSROOM-N6-011 — Editorial freshness workflow hardening

### Status

**DONE — NEWSROOM-N6-011 zmergowano przez PR #148 jako `main@6b816afe64a818392cca402a1b7b16e09393e4f4` po pełnym exact-head i post-merge Quality Gate.**

### Cel

Domknąć istniejący kontrakt freshness z `NEWSROOM-ADMIN-CMS-SPEC.md` §29 i `NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md` §26 bez automatycznego zmieniania workflow po upływie terminu.

### Potwierdzony fundament

- model ma `source_checked_at`, `freshness_review_due_at`, `last_substantive_update_at`, `public_state_changed_at`,
- tabela CMS pokazuje `freshness_review_due_at`,
- testowany filtr `freshness_overdue` już istnieje,
- `freshness_review_due_at <= now()` jest wyłącznie kolejką pracy; `Mark needs review` pozostaje jawną, audytowaną decyzją.

### Pozostały zakres

- pełna sekcja freshness w CMS zgodna z istniejącym kontraktem pól,
- kontrolowana edycja `source_checked_at` i `freshness_review_due_at`,
- service-controlled timestamps pozostają read-only,
- czytelny computed status co najmniej `fresh` / `overdue` / `not scheduled`,
- `due soon` pozostaje **nierozstrzygniętym progiem policy**: obecne źródła prawdy nie definiują liczby dni, więc implementacja nie może jej hardcodować bez osobnej decyzji,
- regression potwierdzający, że overdue nie zmienia samoczynnie `workflow_status` ani public distribution.

### DoD

- CMS pozwala operacyjnie utrzymywać freshness bez bezpośredniej mutacji service-controlled timestamps,
- overdue pozostaje filtrem/kolejką pracy, a nie automatycznym transition,
- brak wymyślonego `due soon` threshold bez decyzji w source-of-truth policy,
- exact-head CI + wymagane regression PASS.

### Potwierdzony implementation evidence przed merge

- finalny implementation HEAD przed docs-sync: `cee06ee552208dd19d06110009237c2c0d90fb03`,
- exact-head CI #526: 1151 passed / 20 326 assertions / 2 skipped, `newsroom-postgres` PASS, Pint 1103 files PASS, frontend build PASS,
- zmaterializowano `ContentArticle::freshnessStatus()` dla `fresh` / `overdue` / `not_scheduled`; `due soon` nie jest wyliczany, ponieważ source-of-truth policy nadal nie definiuje progu,
- Filament ma sekcję Freshness z edytowalnymi `source_checked_at` i `freshness_review_due_at` oraz read-only service-controlled timestamps,
- ordinary public Save zapisuje wyłącznie bezpieczne freshness/internal metadata pod istniejącym stale-token guardem; public content, workflow i service-controlled timestamps pozostają niezmienione,
- tabela i infolist pokazują computed freshness status/backlog,
- regression potwierdza, że overdue nie zmienia `workflow_status` ani active distribution oraz że termin można jawnie wyczyścić do `not_scheduled`,
- Browser Smoke nie był triggerowany przez path contract: PR zmienia wyłącznie model/admin Filament/feature regression, bez publicznego renderera.
- finalny PR HEAD `fbd87e99d731813431fe33d0deefbb4a374ec262` przeszedł exact-head CI #530: 1151 passed / 20 326 assertions / 2 skipped, `newsroom-postgres` PASS, Pint 1103 files PASS, frontend PASS,
- PR #148 zmergowano jako `main@6b816afe64a818392cca402a1b7b16e09393e4f4`, a post-merge CI #531 powtórzył pełny PASS z tym samym bilansem,
- Browser Smoke nadal nie był triggerowany przez path contract, ponieważ zakres nie zmieniał publicznego renderera; nie zapisujemy nieuruchomionego gate'u jako PASS.

---

## NEWSROOM-N6-012 — Accessibility gate

### Status

**TODO — responsive/browser smoke PASS nie jest pełnym accessibility evidence.**

### Cel

Domknąć istniejący accessibility contract z public UI/test runbooku dla publicznych newsroom surfaces przed regularnym rolloutem.

### Zakres

- article,
- hub,
- category,
- topic/dossier,
- guides,
- Product Bridge i istotne CTA/navigation elementy,
- heading order,
- landmarks,
- keyboard navigation,
- visible focus,
- link/control names,
- image alt,
- form/control labels tam, gdzie występują,
- error association tam, gdzie występuje,
- reduced-motion behavior,
- contrast/manual evidence tam, gdzie automatyczny check nie jest wystarczający.

### DoD

- automatyczne testy i wymagane manual evidence są jawnie rozdzielone,
- istniejący responsive Browser Smoke pozostaje regression gate, ale nie jest samodzielnie traktowany jako a11y PASS,
- wykryte repo-level problemy są naprawione przed oznaczeniem tasku DONE,
- exact-head CI/Browser Smoke PASS i dokumentacja zsynchronizowana z faktycznym wynikiem.

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

- link consistency/manual diff,
- finalny CI/GitHub Actions gate na exact-head docs PR przed merge.

---

# 6. Definition of Done całego newsroom v1

### Domain

- [x] schema wdrożona
- [x] models/factories
- [x] publishing service
- [x] scheduling
- [x] public HTTP redirects / withdrawn 410 disposition

### CMS

- [x] article resource
- [x] category resource
- [x] topic resource
- [x] controlled block editor
- [x] sources
- [x] relations
- [x] origin/regulatory fields
- [x] focal point/crop preview — CSS previews 16:9/4:3/1:1 + focal point; bez deklarowania fizycznych crop variants
- [x] article preview
- [x] home composer + future preview
- [x] checklist
- [x] workflow — transition/exposure actions + stale-safe `Apply public update` dla publicznego `ContentArticle`
- [x] admin-only authorization bez rozszerzenia panel access
- [x] stale-write rejection — `ContentArticle` i `NewsroomHomeComposer`
- [x] AuditLog User actor / ContentAuthor identity separation

### Public

- [x] article + controlled block renderer
- [x] regulatory context box/provenance
- [x] Product Bridge questions/legal/signs/contextual CTA z explicit-pivot/public-eligibility guard
- [x] author profile/newsroom publication integration
- [x] semantic silo + controlled reverse links
- [x] newsroom hub with placements/fallback/dedupe
- [x] category
- [x] topic/dossier
- [x] guides
- [ ] responsive/accessibility — article detail/Product Bridge ma PASS wymaganej macierzy responsive, ale pełny accessibility gate pozostaje dalszym hardeningiem

### SEO

- [x] stable entity graph + site identity dla article detail
- [x] visible/schema dates consistency dla article detail
- [x] self-canonical + route-family exclusivity dla current article detail
- [x] article sitemap przez istniejący static generator + deterministic sharding readiness + rollout-gated hub coverage (NEWSROOM-N5-001)
- [x] News Sitemap full required metadata + `first_published_at` eligibility + 1000-entry deterministic split (NEWSROOM-N5-002)
- [x] dirty/version scheduled refresh bez queue-worker assumption — potwierdzone w NEWSROOM-N5-007 PR #112: version/clean-version, shared lock, every-minute scheduler, `onOneServer()` + `withoutOverlapping()`, daily recovery
- [x] child-before-index atomic static publication — potwierdzone w NEWSROOM-N5-007 PR #109; dirty/version refresh i production delivery smoke pozostają osobnymi otwartymi gate'ami
- [x] istniejący `SeoSitemapAuditor` rozszerzony o newsroom/news namespace/tag/date/window/eligibility/topology/shard/obsolete-file checks w NEWSROOM-N5-006; generic protocol-limit guards nadal są reużywane
- [ ] rzeczywisty static/Nginx/CDN delivery smoke (Content-Type/cache/Set-Cookie/validators) — tooling/Nginx contract są w PR #115, workflow w PR #117; report-only produkcja wykazała Cache-Control mismatch, więc brak nadal STRICT production PASS
- [x] Atom feed + discovery + generation cache/validator contract (NEWSROOM-N5-003)
- [x] author ProfilePage / publisher / WebSite

### Operations

- [ ] NEWSROOM_PUBLIC_ENABLED controlled rollout/rollback gate
- [x] publisher transparency/contact/editorial principles gate
- [ ] scheduler monitored
- [x] audit — AuditLog operacyjny + site-wide `newsroom:audit-links`/SEO audit mają regression evidence
- [x] correction flow — NEWSROOM-N6-010 / PR #146 / post-merge CI #523
- [x] freshness — NEWSROOM-N6-011 / PR #148 / post-merge CI #531
- [x] analytics
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

- architecture: tylko jeśli zmienia się decyzja lub jego live implementation status wymaga synchronizacji,
- data spec: jeśli zmienia się model,
- CMS spec: jeśli zmienia się backoffice,
- UI spec: jeśli zmienia się publiczny kontrakt,
- SEO spec: jeśli zmienia się dystrybucja,
- backlog: oznaczyć wykonanie,
- current docs jak DATABASE-SCHEMA/MENU/TEST dopiero gdy kod faktycznie zmieniony.

Nie oznaczać tasku DONE przed merge + green verification.

---

# 10. Aktualny stan

Na 2026-09-18, po zweryfikowanym NEWSROOM-N4-008 na `main@3d7ac8ab8a3ed1c299cb0cdd4cb1ef8ac6b53f78`:

- pakiet projektowy newsroomu obejmuje architekturę, model danych, CMS, UI, governance, SEO, backlog i runbook,
- foundation N0, pełny etap N1-001..N1-006, admin/domain N2-001..N2-012, cały etap NEWSROOM-N3-001..N3-008 oraz NEWSROOM-N4-001..N4-008 są wdrożone,
- publiczne article detail, hub/category/guides/topic oraz kontrolowane semantic/reverse links konsumują wspólny `NEWSROOM_PUBLIC_ENABLED` gate,
- N4-008 reużywa istniejące `content_article_topic`, `content_article_question`, `content_article_legal_unit` i `content_article_traffic_sign`; nie powstała nowa tabela ani równoległy graph,
- article/guide detail ma primary-category link, jawne published topic links i deterministic related articles do maks. 4,
- pytania, publiczne legal pages i traffic-sign details mają reverse links do maks. 3 eligible newsroom/guide targets; TrafficSign zachowuje tylko `direct|example`,
- reverse/related targets muszą przejść `activelyDistributed()+indexable()`, aktywną kategorię oraz publicznego autora; public gate=false zwraca pusty resolver,
- N4-008 nie mutuje `question_relations`, `question_seo_topics` ani rankingów question graphu; forward question/legal/sign rendering nadal należy do istniejącego Product Bridge N3-005,
- `NewsroomSemanticLinkService::audit()` daje per-article inbound sources, explicit reverse-edge count i estimated hub depth; site-wide `newsroom:audit-links` nie istnieje,
- Browser Smoke #48 potwierdził `newsroom-semantic-links` oraz brak regresji w article/home/category/guides/topic,
- exact-head CI #361 był pełnym PASS, a post-merge CI #362 na exact `main@3d7ac8ab...` zakończył: 1093 passed / 19 881 assertions / 2 skipped, Pint PASS, frontend build 7.42 s; `newsroom-postgres` 7 passed / 94 assertions,
- N5-001 wdrożyło standard article sitemap + hub coverage, N5-002 Google News Sitemap, N5-003 Atom feed/discovery, N5-004 analytics hooks, N5-005 article-specific IndexNow automation, a N5-006 istniejący sitemap/link audit hardening; dirty/version refresh i atomic child-before-index publication pozostają N5-007,
- QUEUE_CONNECTION w env example jest sync; stały queue worker nie jest gwarantowany,
- panel Filament pozostaje admin-only i ten kontrakt pozostaje wymaganiem v1.

---

# 11. Pierwszy następny task

### Repo-level przed operacyjnym deployem

**NEWSROOM-N6-012 — Accessibility gate.**

NEWSROOM-N6-011 freshness workflow jest zamknięty po PR #148 i post-merge CI #531. To ostatni repo-level brak wykryty przez audyt DoD.

Po N6-012 nie ma kolejnego zaplanowanego repo-level feature/hardening tasku newsroomu; dalsza praca przechodzi do production/release evidence zgodnie z istniejącymi N5-007/N6-000/N6-003/N6-004/N6-005/N6-006/N6-007/N6-008.

### Równoległy production-only blocker

NEWSROOM-N5-007 pozostaje **IN PROGRESS** i wymaga zastosowania aktualnego Nginx configu na produkcji, udanego STRICT Production SEO Delivery Smoke oraz późniejszego GSC verification. Brak dostępu GitHub do serwera nie jest zastępowany nową ścieżką SSH/deployment architecture.

---

# 12. Historia zmian

### 2026-09-20 — v0.70

- NEWSROOM-N6-011 freshness workflow hardening jest DONE po PR #148,
- finalny PR HEAD `fbd87e99d731813431fe33d0deefbb4a374ec262` przeszedł CI #530: 1151 passed / 20 326 assertions / 2 skipped, PostgreSQL PASS, Pint 1103 files PASS, frontend PASS,
- merge utworzył `main@6b816afe64a818392cca402a1b7b16e09393e4f4`; post-merge CI #531 powtórzył pełny PASS z tym samym bilansem,
- globalny DoD `freshness` zamknięto; następny i ostatni repo-level brak z audytu DoD to NEWSROOM-N6-012 Accessibility gate.

### 2026-09-20 — v0.69

- NEWSROOM-N6-011 implementation zmaterializowano na PR #148 bez migracji, zmian publicznego renderera lub nowego workflow transition,
- implementation HEAD `cee06ee552208dd19d06110009237c2c0d90fb03` przeszedł CI #526: 1151 passed / 20 326 assertions / 2 skipped, PostgreSQL PASS, Pint 1103 files PASS, frontend PASS,
- computed freshness ma wyłącznie `fresh` / `overdue` / `not_scheduled`; `due soon` pozostaje jawnie niewdrożone z powodu braku zdefiniowanego progu policy,
- backlog nie oznacza N6-011 jako DONE przed finalnym docs-sync CI, merge i post-merge evidence.

### 2026-09-20 — v0.68

- NEWSROOM-N6-010 correction workflow hardening wdrożono przez PR #146 bez migracji, nowych pól, revision/snapshot systemu ani zmian RBAC,
- finalny implementation HEAD `9abb721a69bfb9a8d9a50c5e881166ad189d68a6` przeszedł CI #522 — 1149 passed / 20 300 assertions / 2 skipped, PostgreSQL PASS, Pint 1103 files PASS, frontend PASS; Scheduled Publication Smoke #13 również PASS,
- PR #146 zmergowano jako `main@c58feafe6cffbb8bf54bbfcd0b3f1d4587fee97d`; post-merge CI #523 powtórzył pełny PASS z tym samym bilansem testów,
- globalny DoD correction flow zamknięto; następny repo-level task to NEWSROOM-N6-011 editorial freshness workflow, a N5-007 pozostaje oddzielnym production-only blockerem.

### 2026-09-20 — v0.67

- audyt historii backlogu wykazał, że correction flow, pełny editorial freshness workflow oraz accessibility od początku były wymaganiami globalnego DoD/specyfikacji, ale nie otrzymały własnych atomowych tasków wykonawczych,
- dodano NEWSROOM-N6-010/N6-011/N6-012 jako pre-deploy hardening bez zmiany architektury lub modelu danych; scope wyprowadzono wyłącznie z istniejących source-of-truth docs,
- dla N6-011 zachowano jawne nierozstrzygnięcie: `due soon` nie ma obecnie zdefiniowanego threshold i nie wolno go wymyślać w implementacji,
- repo-level next task ustawiono na N6-010; N5-007 pozostaje równoległym production-only blockerem.

### 2026-09-20 — v0.66

- audyt globalnego DoD po post-merge CI #517 potwierdził, że operacyjny audit nie jest już luką: istniejący `AuditLog` zachowuje User actor/allowlisted metadata, a NEWSROOM-N5-006 dostarcza site-wide `newsroom:audit-links` oraz rozszerzony `SeoSitemapAuditor`,
- pozostawiono otwarte pełny correction flow, pełny freshness workflow/admin section, accessibility oraz production-only rollout/static-delivery/monitoring smoke; audyt nie oznacza ich wykonania.

### 2026-09-20 — v0.65

- zsynchronizowano globalny Definition of Done z istniejącymi, wcześniej potwierdzonymi taskami i kodem bez zmiany funkcjonalności: publiczne 301/withdrawn 410, ContentTopicResource, provenance/regulatory fields, focal-point/CSS crop preview, author profile integration, hub/category/topic/guides, semantic reverse links, author ProfilePage/publisher/WebSite oraz analytics mają już zakończone implementation i regression evidence,
- pozostawiono otwarte wyłącznie pozycje, których nie potwierdza jeszcze repo-level evidence albo wymagają runtime/production: pełny accessibility gate, static/Nginx/CDN STRICT smoke, controlled public rollout, scheduler monitoring, operations audit/correction/freshness oraz live production smoke.

### 2026-09-20 — v0.64

- zsynchronizowano globalny Definition of Done z potwierdzonym stanem NEWSROOM-N6-009: publisher transparency/contact/editorial principles gate jest zamknięty po PR #140, docs-sync PR #141 i post-merge CI #513 na exact `main@763bd868d8a1a9c8c9b0bc0eed78f1d17c6ae35b`,
- nie zmieniono zakresu funkcjonalnego, architektury ani statusu pozostałych gate'ów produkcyjnych.

### 2026-09-20 — v0.63

- NEWSROOM-N6-009 wdrożono przez PR #140 jako publisher-transparency production gate bez migracji, nowych pól artykułu ani zmian RBAC/workflow sponsoringu; materiały sponsorowane pozostają poza newsroom v1,
- dodano publiczne `/zasady-redakcyjne`, linki z artykułu/footer/O nas, canonical SEO + breadcrumbs + WebPage schema oraz `NewsArticle.publishingPrinciples` prowadzące do realnej publicznej strony,
- finalny implementation HEAD `ed7ce64c997d5fc74163bddd6772b5883f78dab9` przeszedł wymagane exact-head gates, w tym Browser Smoke #105 z golden path i Enterprise SEO validation,
- PR #140 zmergowano jako `main@6defc5fe82fef6c6724d67659486d58736231386`; post-merge CI #511 zakończył pełny PASS: 1142 passed / 20 246 assertions / 2 skipped, PostgreSQL PASS, Pint 1103 files PASS i frontend build PASS,
- publiczna polityka opisuje autorstwo, źródła, weryfikację, korekty, konflikty interesów i ewentualny przyszły sponsorship disclosure, bez deklarowania nieistniejącego mechanizmu sponsoringu.

### 2026-09-19 — v0.62

- PR #131 dodał repo-level performance baseline i regresyjne budgets do istniejących Browser Smoke harnessów article/hub/category bez zmiany produkcyjnej logiki aplikacji,
- finalny HEAD `0d1e16a66faaf7481b30ccde589aca291e398b83` przeszedł Browser Smoke #101 i CI #489; merge `main@f147af4fb504ffbf9b953701c891c275ecf2d98d` ma post-merge CI #490 pełny PASS — 1140 passed / 20 229 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS i frontend build PASS,
- potwierdzone baseline'y: article 14 queries / 61 098 B HTML, hub 33 / 95 354 B, category page 1 9 / 96 622 B, page 2 7 / 62 499 B; wspólny WebP 71 342 B i 1000×750; CSS 402 335 B, JS 15 225 B,
- collector wykrywa brak deklarowanych HTML `width`/`height` dla wspólnego obrazu; to pozostaje otwartym repo-actionable CLS findingiem,
- N6-004 pozostaje IN PROGRESS; production LCP/INP/CLS/TTFB nadal wymagają live rollout evidence.

### 2026-09-19 — v0.61

- PR #129 rozszerzył istniejący N6-003 validator o produkcyjne canonical-host migration checks dla `http`/`www` i historycznego `prawkoapp.pl`; custom `BASE_URL` pozostaje poza tym project-specific guardem,
- Enterprise SEO Production Validation #3 potwierdził redirect-to-canonical PASS dla wszystkich czterech wariantów; `http://www.prawkonaraz.pl/` ma 2-hop chain i jest warningiem, pozostałe sprawdzone warianty mają 1 hop,
- GSC URL Inspection 2026-09-19: apex HTTP jest `Page with redirect`, oba `www` są unknown to Google, a canonical HTTPS homepage nadal ma `Duplicate, Google chose different canonical than user` i referring URL z `https://prawkoapp.pl/`; host migration nie jest więc potwierdzoną aktywną przyczyną mismatchu,
- finalny HEAD `379f673e681c504322dbcabe370de110ce03dcc5` przeszedł CI #481; PR #129 zmergowano jako `main@8ea54ba1a8b1ca5349a468353e519fa7262a16d4`, a post-merge CI #482 zakończył pełny PASS: 1140 passed / 20 229 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS i frontend build PASS,
- N6-003 nadal pozostaje IN PROGRESS: robots cache, live stable `@id`, `/aktualnosci` indexing contract, aktualny deploy/runtime, zielony STRICT run i representative live samples pozostają otwarte.

### 2026-09-19 — v0.60

- PR #127 dodał do istniejącego `deploy/mikrus/deploy.sh` jawny `SEO_RELEASE=1` guard: refresh/audit sitemap artifacts, `nginx -t` oraz publiczny production SEO delivery smoke; `REQUIRE_NEWSROOM_PUBLIC=1` wymusza feed contract,
- guard nie zmienia manualnego modelu deployu, nie aplikuje sam vhosta Nginx i nie włącza `NEWSROOM_PUBLIC_ENABLED`, więc nie jest dowodem naprawy live production,
- finalny HEAD `e84758d911ffc4f6ff10a04f88a2b8c48beb22c6` przeszedł CI #477; PR #127 zmergowano jako `main@b26317bd979dd0c372b7a9341a8358f2599a9550`, a post-merge CI #478 zakończył pełny PASS: 1140 passed / 20 229 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS, frontend build PASS,
- świeży report-only Production SEO Delivery Smoke przy PR #127 ponownie potwierdził live `/robots.txt` `Cache-Control: max-age=14400` i underlying exit `1`; N5-007 oraz N6-003 nadal pozostają IN PROGRESS do faktycznego deploy/runtime i zielonego STRICT production evidence.

### 2026-09-19 — v0.59

- NEWSROOM-N6-003 otrzymał repo-level enterprise SEO production-validation harness przez PR #125 bez zmiany produkcyjnej logiki aplikacji ani architektury; zmieniono wyłącznie workflow, Node validator i package script,
- finalny HEAD `8a64f81c15fb6c52e2a90db26c5c65c9ad999801` przeszedł Enterprise SEO Production Validation #2 (REPORT-ONLY workflow success), Scheduled Publication Smoke #12, Browser Smoke #96 i CI #472,
- PR #125 zmergowano jako `main@0344427cdf42804a037c688df54da6216f243ca6`; post-merge CI #473 zakończył pełny PASS: 1139 passed / 20 219 assertions / 2 skipped, PostgreSQL PASS, frontend build PASS,
- report-only live evidence nadal wykazuje produkcyjne rozbieżności: robots cache `max-age=14400`, brak aktualnych stable WebSite/Organization @id na live homepage, niespójny `/aktualnosci` placeholder/indexing contract, brak article/news sitemap coverage i rollout-gated feed 404,
- GSC URL Inspection 2026-09-19: homepage ma `Duplicate, Google chose different canonical than user`, a `/aktualnosci` jest unknown to Google; N6-003 pozostaje IN PROGRESS do zielonego STRICT production validation i reprezentatywnych live samples.

### 2026-09-19 — v0.58

- NEWSROOM-N6-002 zakończono przez PR #123 jako dedykowany production-mode scheduled-publication release smoke bez zmiany produkcyjnej logiki publikacji ani architektury; diff obejmuje wyłącznie nowy workflow, package script i `scripts/newsroom-scheduled-publication-smoke.mjs`,
- finalny implementation/test HEAD `2426f19215326a1c3386e8185222030b634ed795` przeszedł Newsroom Scheduled Publication Smoke #10, Browser Smoke #94 i CI #467,
- smoke potwierdził `published=0` przed realnym terminem, `published=1 failed=0 skipped=0` po due, audit trigger `scheduler`, dirty/version advance oraz refresh sitemap/feed bez oczekiwania na daily cron; `articles.xml` i `news.xml` zostały odświeżone z wersji 3 do 4 i zawierały nowy artykuł,
- PR #123 zmergowano jako `main@afaa44dca0012144ab842c9195856eebec4553d3`; post-merge CI #468 zakończył pełny PASS,
- evidence dotyczy izolowanego GitHub Actions environment z `APP_ENV=production` i stabilnym Docker app+Nginx originem; nie jest to live-production database mutation evidence,
- NEWSROOM-N5-007 pozostaje bez zmian; kolejnym N6 taskiem jest NEWSROOM-N6-003 Enterprise SEO production validation.

### 2026-09-19 — v0.57

- NEWSROOM-N6-001 zakończono przez PR #121 jako dedykowany Browser Smoke golden path bez zmiany produkcyjnego kodu ani architektury; diff obejmuje wyłącznie workflow, package script i `scripts/e2e-newsroom-golden-path.mjs`,
- finalny implementation/test HEAD `76d0fa7d428b11f2db9d901eac698c7a169722ff` przeszedł Browser Smoke #84 oraz CI #454; golden path potwierdził admin create/preview/workflow publish oraz public hub -> article -> related question -> product,
- exact-head CI #454: 1139 passed / 20 219 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS, frontend build 9.47 s; merge PR #121 utworzył `main@b0b29d9b4a3337f4e527334f6ead8b1be85303ad`,
- post-merge CI #455 na tym exact `main` również zakończył pełny PASS: 1139 passed / 20 219 assertions / 2 skipped, PostgreSQL PASS, Pint 1102 files PASS, frontend build 10.17 s,
- NEWSROOM-N5-007 produkcyjne gate'y pozostają bez zmian; kolejnym taskiem N6 jest NEWSROOM-N6-002 scheduled-publication production smoke.

### 2026-09-19 — v0.56

- PR #119 dodał wyłącznie test evidence w istniejącym `NewsroomSeoArtifactRefreshCoordinatorTest`: runtime scheduler contract (`everyMinute`, production-only, `onOneServer()`, `withoutOverlapping()`) oraz shared refresh-lock contention z zachowaniem dirty state i bez generator/auditor work przy zajętym locku,
- exact-head CI #424 i post-merge CI #425 zakończyły pełny PASS: 1139 passed / 20 219 assertions / 2 skipped, PostgreSQL 7/94, Pint 1102 files PASS, frontend build 9.86 s / 9.04 s,
- finalny implementation/test head #119 to `30935470028d5fe830ea1f2809d4ac8edcf97b0d`, merge `main@abb42032370a185a4cf7d7ee9a474ccbca6d86a4`; kod produkcyjny i architektura nie zostały zmienione,
- NEWSROOM-N5-007 pozostaje IN PROGRESS wyłącznie z otwartymi gate'ami produkcyjnymi: deploy aktualnego Nginx, zielony STRICT production smoke, Cloudflare/origin HTTP evidence i GSC verification.

### 2026-09-19 — v0.55

- PR #117 dodał dedykowany workflow GitHub Actions `Production SEO Delivery Smoke` bez kopiowania starych wariantów skryptu/testu z PR #114; workflow reużywa potwierdzony kod z PR #115,
- PR run działa REPORT-ONLY, manualny `workflow_dispatch` jest domyślnie STRICT, a raport jest zapisywany jako artifact,
- exact-head CI #420 i post-merge CI #421 zakończyły pełny PASS: 1137 passed / 20 207 assertions / 2 skipped, PostgreSQL 7/94, Pint 1102 files PASS, frontend build 9.66 s / 5.49 s,
- report-only production run nie jest PASS: `/robots.txt` zwrócił `Cache-Control: max-age=14400` zamiast oczekiwanego `public, max-age=3600`, underlying smoke exit code 1,
- NEWSROOM-N5-007 pozostaje IN PROGRESS; następny twardy gate to wdrożenie aktualnego Nginx configu i zielony STRICT `workflow_dispatch`, potem GSC verification.

### 2026-09-18 — v0.54

- trzeci podkrok NEWSROOM-N5-007 zmergowano przez PR #115; finalny implementation head `30fa65d63eacdcb31b2a1c8c815f4034e29b5166`, merge `main@9a9c98d3534492e30ba215fd9785b7dd425a1f75`,
- `deploy/mikrus/nginx/prawkobit.conf.example` ma dedykowane static crawler blocks dla `/robots.txt`, `/sitemap.xml` i `/sitemaps/*` z jawny Content-Type, public cache, ETag, `if_modified_since exact` i nosniff,
- dodano `scripts/production-seo-delivery-smoke.sh` do rzeczywistej weryfikacji robots/root/static sitemap oraz rollout-gated Atom feedu; script sprawdza 200/404 contract, Content-Type, Cache-Control, brak Set-Cookie i conditional 304,
- `NginxSeoStaticDeliveryConfigurationTest` chroni repo-level Nginx delivery contract,
- exact-head CI #416: 1137 passed / 20 207 assertions / 2 skipped, PostgreSQL 7/94, Pint 1102 files PASS, frontend build 6.82 s; post-merge CI #417 powtórzył 1137 / 20 207 / 2 skipped, PostgreSQL 7/94, Pint PASS i build 10.16 s,
- NEWSROOM-N5-007 pozostaje IN PROGRESS: istnieje tooling do production smoke, ale brak jeszcze evidence z realnego uruchomienia przeciw produkcji oraz GSC verification; dedykowany scheduler-definition/lock-contention regression również pozostaje otwarty.

### 2026-09-18 — v0.53

- drugi podkrok NEWSROOM-N5-007 zmergowano przez PR #112; finalny implementation head `6f1c3b99d11796f74987c7fe640302b2d76578b7`, merge `main@79b6de0276ba8cdce500c366a4f98098e528dcd8`,
- dodano cache-backed `NewsroomSeoArtifactRefreshCoordinator` z version/clean-version oraz shared lockiem; marker jest czyszczony tylko dla niezmienionej wersji,
- `newsroom:refresh-seo-artifacts-if-dirty` nie generuje przy clean state, po dirty uruchamia istniejący generator + auditor, a failure lub nowa version pozostawia recovery signal,
- after-commit article workflow/public-read/home-placement events oraz category/topic/author observers ustawiają dirty signal bez wykonywania pełnej generacji w publish request,
- produkcyjny scheduler uruchamia coordinator co minutę z `onOneServer()` + `withoutOverlapping()`; daily `seo:refresh-sitemaps` pozostał bez zmian jako safety net, bez założenia queue workera,
- aktualne canonical deployment docs potwierdzają single-node Mikrus 4.1 z lokalnym `public/`, lokalnym Redisem i jednym cronem `schedule:run`; dla tej topologii topology gate jest spełniony, ale multi-node wymagałby ponownego otwarcia gate'u,
- exact-head CI #409: 1136 passed / 20 182 assertions / 2 skipped, PostgreSQL 7/94, Pint 1101 files PASS, frontend build 10.03 s; post-merge CI #410 powtórzył 1136 / 20 182 / 2 skipped, PostgreSQL 7/94, Pint PASS i build 7.54 s,
- NEWSROOM-N5-007 pozostaje IN PROGRESS: production static/Nginx/Cloudflare/GSC HTTP delivery verification oraz production-like static delivery smoke nadal są otwarte; dedykowany scheduler/lock contention regression również nie jest przedstawiany jako wykonany.

### 2026-09-18 — v0.52

- pierwszy podkrok NEWSROOM-N5-007 zmergowano przez PR #109; finalny implementation head `dbcd5cdb2f0e6663c998f930c034624f8fe36bf4`, merge `main@1744a93fd8f0bddfe7fc5bff90b146fdea24b016`,
- `SeoSitemapGenerator` waliduje kompletny generated set przed publication, zapisuje child XML przed root `sitemap.xml` i usuwa obsolete zarządzane article/news files dopiero po switchu root indexu; unrelated XML nie są objęte cleanupem,
- `SeoSitemapGenerationTest` chroni root-index-last ordering, brak modyfikacji starego setu przy invalid XML oraz post-switch obsolete-newsroom cleanup,
- CI #401 i #402 nie są finalnym evidence, ponieważ backend/PostgreSQL przechodziły, ale Pint wykrywał konflikt stylu w nowym test helperze; po minimalnej korekcie testowej cały gate uruchomiono od nowa na finalnym SHA,
- exact-head CI #403: 1130 passed / 20 144 assertions / 2 skipped, PostgreSQL 7/94, Pint 1093 files PASS, frontend build 9.94 s; post-merge CI #404 na exact main powtórzył 1130 / 20 144 / 2 skipped, PostgreSQL 7/94, Pint PASS i build 9.69 s,
- NEWSROOM-N5-007 pozostaje IN PROGRESS: dirty/version signal, refresh coordinator + distributed lock, version-safe marker clearing, topology gate i production static delivery verification nadal są otwarte.

### 2026-09-18 — v0.51

- NEWSROOM-N5-006 zmergowano przez PR #107; finalny implementation head `64feb88b614b737a69784d43e86e97591aaec3d5`, merge `main@18300baf3a91ffc5e3c549ab664c471bfd34ffe4`,
- rozszerzono istniejący `SeoSitemapAuditor` o newsroom/news canonical, eligibility, namespace/tag/date/window, topology/shard i obsolete-file checks bez drugiego validatora,
- dodano site-wide `NewsroomSemanticLinkService::auditAll()` oraz cienką komendę `newsroom:audit-links` dla orphan/source/breaking/non-public-target/related/featured-topic QA, reużywające istniejący semantic graph i public gate,
- nie zmieniono generatora sitemap, migracji, schema ani mechanizmu publikacji statycznych plików; atomic child-before-index publication, cleanup po switchu i dirty/version coordinator pozostają N5-007,
- pierwszy pre-merge CI #396 nie jest evidence zamknięcia: backend/PostgreSQL/Browser były zielone, ale Pint wykrył jeden unused import; po style-only fix finalny HEAD przeszedł cały gate od początku,
- CI #397 zakończył 1127 passed / 20 131 assertions / 2 skipped, PostgreSQL 7/94, Pint/build PASS; Browser Smoke #57 PASS 6/6; post-merge CI #398 powtórzył 1127 / 20 131 / 2 skipped, PostgreSQL 7/94 i Pint/build PASS,
- następnym wykonywalnym taskiem jest NEWSROOM-N5-007 — Static sitemap publication + freshness + delivery hardening.

### 2026-09-18 — v0.50

- NEWSROOM-N5-005 zmergowano przez PR #105; finalny implementation head `44c8099ac2ea020bf5d9e31cfe547af8cb2149f7`, merge `main@1cc9f4bec8c7d7fc105fd3495664434cf4323eea`,
- wdrożono dedicated `ContentArticleIndexNowRequested` z `ShouldDispatchAfterCommit` i pojedynczy auto-discovered listener `QueueNewsroomArticleIndexNow`, reużywające istniejący `IndexNowQueueService`/submission pipeline zamiast drugiego klienta,
- publish/republish/substantive update/withdraw/slug change mapują się na lokalne `EVENT_CREATED|UPDATED|DELETED` zgodnie z publicznym HTTP lifecycle; archive, restore-to-review, no-op, scheduled-before-time, noindex i gate=false nie generują nieprawidłowych zgłoszeń,
- slug change zgłasza old 301 path i new canonical po commit, a public update ze zmianą sluga nie dubluje canonical enqueue; outer rollback nie tworzy row, a błąd kolejki nie cofa zatwierdzonej publikacji,
- `event_type` pozostaje lokalną metadaną kolejki i regression test potwierdza brak tego pola w HTTP payload `IndexNowSubmissionService`,
- exact-head CI #392 zakończył 1116 passed / 20 095 assertions / 2 skipped, PostgreSQL 7/94, Pint PASS i frontend build PASS; post-merge CI #393 na exact main powtórzył 1116 / 20 095 / 2 skipped, PostgreSQL 7/94, Pint/build PASS,
- produkcyjne wdrożenie fazy 2 IndexNow/Bing verification nie jest deklarowane jako wykonane; następnym taskiem jest NEWSROOM-N5-006 — Extend existing SEO/sitemap audits.
### 2026-09-18 — v0.49

- NEWSROOM-N5-004 zmergowano przez PR #103; finalny implementation head `6235b7dbadd60549a82ceebcb4eda47aaa6596fa`, merge `main@5704b3c3a8acde029001e28567980c5de8e27cdf`,
- telemetryka reużywa istniejący `trackAnalyticsEvent` i GA/consent layer; nie dodano backendowego event store, migracji, nowych tabel ani drugiego klienta analytics,
- wdrożono delegated SSR tracking dla article view, module/category/pagination clicks, Product Bridge, sources i related article/question/legal/sign links z parametrami ograniczonymi do `article_id`, `article_type`, `category_slug`, `module`, `position`, `destination_path`,
- `prawkonaraz:analytics-ready` rozwiązuje delayed-consent readiness; Browser Smoke #55 potwierdza dokładnie jeden article view po readiness, one click -> one event, generic module click oraz suppression non-link/`aria-disabled`,
- `NewsroomAnalyticsHooksTest`, `GoogleAnalyticsTagTest` i `NewsroomHomePageTest` chronią render/privacy contract; optional scroll depth i popular-content ranking pozostają poza N5-004,
- exact-head CI #384 zakończył 1106 passed / 20 051 assertions / 2 skipped, PostgreSQL 7/94, Pint PASS i frontend build PASS; Browser Smoke #55 był PASS; post-merge CI #385 na exact main powtórzył 1106 / 20 051 / 2 skipped, PostgreSQL 7/94, Pint i build PASS,
- następnym taskiem jest NEWSROOM-N5-005 — IndexNow integration review; N5-006/N5-007 pozostają osobnymi zakresami.
### 2026-09-18 — v0.48

- NEWSROOM-N5-003 zmergowano przez PR #101; finalny implementation head `1c89f370e899895eb25ac80bd437b19c1797a9ec`, merge `main@18cd07233e3c8712cf2c7fc9e0c32018baef65d3`,
- wdrożono jeden Atom 1.0 feed na istniejącym `/aktualnosci/feed.xml` z bounded latest-news corpus, stabilnym `urn:prawkonaraz:content-article:{id}`, canonical links, `first_published_at` / substantive-update semantics i public author/summary metadata,
- feed reużywa `NewsroomPublicGate`, `SiteIdentitySchema` oraz generation-based `NewsroomPublicReadCache`; gate=false daje 404 i suppressuje discovery, a istniejące after-commit invalidation odświeża feed cache,
- public-content layout emituje Atom auto-discovery dla crawlable newsroom/guide surfaces; feed response jest bezstanowy i ma `application/atom+xml`, ETag, Last-Modified, public Cache-Control, no-sniff, bez `Set-Cookie` oraz conditional 304,
- `NewsroomFeedTest` pokrywa corpus/limit, identity, slug change, archive invalidation, validators/304, discovery/gate i cookie regression; route/public-gate regressions zostały zsynchronizowane z wdrożonym kontraktem,
- exact-head CI #380 zakończył 1104 passed / 20 024 assertions / 2 skipped, PostgreSQL 7/94, Pint PASS i frontend build PASS; Browser Smoke #54 był PASS; post-merge CI #381 na exact main powtórzył 1104 / 20 024 / 2 skipped, PostgreSQL 7/94, Pint i build PASS,
- drugi RSS endpoint, produkcyjny CDN/Nginx smoke feedu, dirty/version coordinator, atomic static publication, analytics, article-specific IndexNow i namespace-specific sitemap audit pozostają osobnymi zakresami; następnym taskiem jest NEWSROOM-N5-004.
### 2026-09-18 — v0.47

- NEWSROOM-N5-002 zmergowano przez PR #99; finalny implementation head `9bc16a7e42ae55c23b1916a4e214b9af846fd3dd`, merge `main@827f3816487d3a404df26c381a103a6cd1a9f413`,
- News Sitemap reużywa istniejący static sitemap pipeline i rollout gate; corpus to wyłącznie świeże `type=news`, `published`, indexable current-canonical articles z aktywną kategorią i publicznym autorem,
- eligibility czasowa jest liczona po `first_published_at`; aktualizacja starego artykułu nie przywraca go do 2-dniowego Google News window,
- wymagane news metadata używa istniejącego canonical publication identity, języka `pl`, pierwotnej daty publikacji i widocznego title; limit 1000 entries/file jest chroniony przez deterministic fixed-ID-range split,
- exact-head CI #371 i post-merge CI #372 zakończyły pełny PASS: 1100 passed / 19 964 assertions / 2 skipped, Pint PASS, frontend build PASS i PostgreSQL 7/94,
- N5-002 nie wdraża feedu, namespace-specific newsroom/news audytora, child-before-index atomic publication/obsolete-shard cleanup, dirty/version coordinatora, article-specific IndexNow ani production static/Nginx/CDN/GSC verification; następnym taskiem jest NEWSROOM-N5-003.

### 2026-09-18 — v0.46

- NEWSROOM-N5-001 zmergowano przez PR #97; finalny implementation head `b4ff321011c3d58438876c9d69f324e342ec9021`, merge `main@5ddfa48c0646fa89cc802d129e6d9ccee9d18957`,
- rozszerzono istniejący static sitemap pipeline o rollout-gated standard article sitemap, hub coverage i deterministic fixed-ID-range sharding bez tworzenia drugiego generatora ani nested sitemap-index,
- article corpus wyklucza noindex/draft/withdrawn, niepublicznego autora, nieaktywną kategorię i canonical path będący historycznym redirect source; `lastmod` korzysta z publicznych timestampów domenowych,
- generator i auditor egzekwują 50 000 entries / 50 MB; istniejący `SeoSitemapGenerationTest` pokrywa single-shard, boundary crossing, stabilność assignment, brak duplikatów, public gate i oba protocol guards,
- exact-head CI #367 i post-merge CI #368 zakończyły pełny PASS: 1098 passed / 19 926 assertions / 2 skipped, Pint PASS, frontend build PASS i PostgreSQL 7/94,
- N5-001 nie wdraża news sitemap, feedu, dirty/version coordinatora, article-specific IndexNow, child-before-index atomic set switch/obsolete-shard cleanup ani produkcyjnej weryfikacji static/Nginx/CDN; następnym wykonywalnym taskiem jest NEWSROOM-N5-002 — News sitemap.

### 2026-09-18 — v0.45

- NEWSROOM-N4-008 zmergowano przez PR #95; finalny implementation head `bd63773bc1febdcc6aa8c2507c6621e908d63b09`, merge `main@3d7ac8ab8a3ed1c299cb0cdd4cb1ef8ac6b53f78`,
- `NewsroomSemanticLinkService` reużywa istniejące newsroom pivots i wspólny public gate; nie dodano migracji, drugiej taksonomii ani równoległego relation graphu,
- article/guide detail renderuje crawlable primary category, jawne topic links i deterministic related articles (max 4); pytania/legal/sign surfaces renderują bounded reverse links (max 3) wyłącznie do `activelyDistributed()+indexable()` targets,
- TrafficSign zachowuje fail-closed `direct|example`; article-question relacja nie modyfikuje `question_relations`, `question_seo_topics` ani rankingów,
- per-article audit udostępnia inbound sources, explicit reverse-edge count i estimated hub depth; site-wide orphan/click-depth command pozostaje niewdrożony i może wejść do N5/N6,
- `NewsroomSemanticLinkIntegrationTest`, zaktualizowany `NewsroomPublicArticlePageTest` i dedykowany `newsroom-semantic-links` Browser Smoke chronią kontrakt,
- exact-head CI #361 i Browser Smoke #48 zakończyły PASS; post-merge CI #362 zakończył 1093 passed / 19 881 assertions / 2 skipped, Pint PASS, frontend build 7.42 s i PostgreSQL 7/94,
- pierwszym następnym taskiem jest NEWSROOM-N5-001; N5 feed/news sitemap/analytics/IndexNow pozostają osobnymi zakresami.

### 2026-09-18 — v0.44

- NEWSROOM-N4-007 zmergowano przez PR #93; finalny implementation head `812313afc38e30e53c59901d46c2d24188e3f6fa`, merge `main@a97373003c5a249a28761df15342f19e656c50c5`,
- istniejący public topic route został aktywowany bez zmian schema/CMS; reużywa `ContentTopic`, explicit pivot i istniejący publish/archive contract,
- topic renderer obsługuje eligible featured lead, mixed newsroom/guide corpus, deterministic chronology, SSR pagination/canonical oraz CollectionPage/BreadcrumbList/ItemList,
- draft/future/unknown/gate=false failują do 404 + noindex, archived historyczny do 410 + noindex; późniejszy below-baseline corpus nie powoduje automatycznego HTTP/indexability flipu,
- `NewsroomTopicPageTest`, public-gate/route regressions i dedykowany Browser Smoke #39 chronią publiczny kontrakt,
- exact-head CI #348 PASS; post-merge CI #349 PASS: 1087 passed / 19 827 assertions / 2 skipped, Pint 1081 files PASS, frontend build 9.48 s, PostgreSQL 7/94,
- NEWSROOM-N4-008 Semantic silo / reverse-link integration jest następnym wykonywalnym taskiem.

### 2026-09-18 — v0.43

- NEWSROOM-N4-006 zmergowano przez PR #91; finalny implementation head `093ca709d3155d5fa3f13bae224312fd286077dc`, merge `main@5f1880e03469fc6e340a86fdb1b4246c7afd116b`,
- `NewsroomPublicReadCache` materializuje generation-based cache dla home/category public read models z 60-sekundowym TTL safety net,
- after-commit invalidation obejmuje publish/archive workflow, aktywne public/exposure updates, placement create/update/delete i category save/delete; placement invaliduje tylko home,
- preview, guide hub, article detail, topic/feed i N5 dirty/version/sitemap/IndexNow pozostają poza zakresem N4-006,
- `NewsroomPublicReadCacheTest` oraz izolacja trwałego feature-test cache potwierdzają reuse/invalidation i transaction boundary,
- exact-head CI #342 oraz Browser Smoke #36 PASS; post-merge CI #343 PASS: 1081 passed / 19 770 assertions / 2 skipped, Pint 1077 files PASS, frontend build 9.44 s, PostgreSQL 7 passed / 94 assertions,
- NEWSROOM-N4-007 Topic / dossier pages jest następnym wykonywalnym taskiem.

### 2026-09-18 — v0.42

- NEWSROOM-N4-005 zmergowano przez PR #89; finalny implementation head `189219b3586d2df8e4ea73045318fd68f38f0fa1`, merge `main@a9fb9ccfed058de88efdb6e0833b67911aeb09aa`,
- zachowano istniejące primary links „Aktualności” i „Poradniki” oraz ich active-state prefixes bez duplikowania i bez zmiany `documentNavigationPrefixes`,
- `PublicFooter::service_links` uzupełniono o oba huby; Vue i Blade compact footer renderują wspólne dane,
- dodano `NewsroomNavigationIntegrationTest` oraz footer assertions do `e2e-newsroom-guides.mjs`; Browser Smoke #32 PASS dla wszystkich czterech newsroom jobów,
- exact-head CI #335 i post-merge CI #336 PASS; post-merge: 1075 passed / 19 745 assertions / 2 skipped, Pint 1069 files PASS, frontend build 7.32 s; PostgreSQL 7 passed / 94 assertions,
- NEWSROOM-N4-006 Cache jest następnym wykonywalnym taskiem.

### 2026-09-18 — v0.41

- NEWSROOM-N4-004 zmergowano przez PR #87; finalny implementation head `119cbd94d1fb6ff6f9f2025e726242190927266d`, merge `main@2bb22142b1e9bec803f9c3889c11000194f46783`,
- `public.guides` pozostał istniejącym top-level route; gate=false zachowuje placeholder/noindex, gate=true renderuje guide-only SSR `newsroom.guides`,
- listing reużywa `activelyDistributedQuery(FAMILY_GUIDES)`, deterministic order i SSR pagination po 20; empty hub ma 200 + noindex, invalid/out-of-range page -> 404,
- dodano guide-hub `CollectionPage`/`BreadcrumbList`/conditional `ItemList`, crawlable wejście z `/aktualnosci`, `NewsroomGuidesHubTest` i `e2e-newsroom-guides.mjs`,
- Browser Smoke #31 PASS dla `newsroom-guides`, `newsroom-category`, `newsroom-home`, `newsroom-article`; exact-head CI #331 i post-merge CI #332 PASS, post-merge: 1072 passed / 19 731 assertions / 2 skipped, Pint 1068 files PASS, frontend build 10.14 s, PostgreSQL 7 passed / 94 assertions,
- NEWSROOM-N4-005 Navigation integration jest następnym wykonywalnym taskiem.

### 2026-09-18 — v0.40

- NEWSROOM-N4-003 zmergowano przez PR #85; finalny implementation head `8e4f707060fbaa348e9392f0b19beb7b4517beca`, merge `main@84bcb2aeff57a1374def7af411c39db07a8fb38d`,
- aktywowano istniejący `public.news.categories.show` jako rollout-gated SSR category page z `activelyDistributed()` newsroom corpus, deterministic order i paginacją po 20,
- pusta aktywna kategoria ma 200 + noindex, a inactive/unknown/invalid/out-of-range requests failują do 404; page 1 nie emituje redundantnego `?page=1`,
- dodano category `CollectionPage`/`BreadcrumbList`/conditional `ItemList`, crawlable hub links oraz dedykowany `NewsroomCategoryPageTest` i `e2e-newsroom-category.mjs`,
- Browser Smoke #29 PASS dla `newsroom-category`, `newsroom-home`, `newsroom-article`; exact-head CI #326 i post-merge CI #327 PASS, post-merge: 1067 passed / 19 684 assertions / 2 skipped, Pint 1065 files PASS, frontend build 9.40 s, PostgreSQL 7 passed / 94 assertions,
- NEWSROOM-N4-004 `/poradniki` hub jest następnym wykonywalnym taskiem.

### 2026-09-18 — v0.39

- NEWSROOM-N4-002 zmergowano przez PR #83; finalny implementation head `1036b67aa44b56fffb0bc1e9083d3a0d1d3963d0`, merge `main@04a3e961a40207bd65c41b684a5bf1cd8d2c5e10`,
- `public.news` przy gate=true renderuje SSR `newsroom.home` z N4-001 read modelu, a gate=false zachowuje MarketingPlaceholder 200 + noindex; nie uruchomiono category/topic/feed ani `/poradniki` hub,
- dodano `NewsroomHomePageTest` i dedykowany Playwright `e2e:newsroom-home`; Browser Smoke #27 zakończył PASS dla `newsroom-home` i `newsroom-article`,
- exact-head CI #321 i post-merge CI #322 zakończyły się PASS; post-merge: 1062 passed / 19 638 assertions / 2 skipped, Pint 1061 files PASS, frontend build 7.59 s, PostgreSQL 7 passed / 94 assertions,
- następnym wykonywalnym taskiem jest NEWSROOM-N4-003 — Category pages.

### 2026-09-18 — v0.38

- NEWSROOM-N4-001 zmergowano przez PR #81; finalny implementation head `813aba108b6b68f0526df3a9c8c86d82df5ca0f6`, merge `main@e0e06e9af8a6b02a63ef4b3e1eb2d772409ad974`,
- `NewsroomHomeCompositionService` zachowuje istniejący fixed-placement/fallback/dedupe contract, ale category composition ma teraz stały query budget dzięki batchowi placements, per-category window rankingowi i wspólnemu eager-loadowi,
- dodano rollout-gated `NewsroomHomeReadModelService` zwracający serializowalne scalar arrays gotowe dla późniejszego Blade/cache; nie dodano publicznego huba ani cache invalidation,
- exact-head CI #314 oraz post-merge CI #315 zakończyły się PASS; post-merge `quality`: 1059 passed / 19 611 assertions / 2 skipped, Pint 1060 files PASS, frontend build 9.93 s; `newsroom-postgres`: 7 passed / 94 assertions,
- następnym wykonywalnym taskiem jest NEWSROOM-N4-002 — Hub Blade layout.

### 2026-09-18 — v0.37

- NEWSROOM-N3-008 zmergowano przez PR #79; finalny implementation head `c8484aa1529eb41805a76ceb7be1f55db63aec14`, merge `main@23b952b77e39cd25fb39edc252faf05849946bd7`,
- `NEWSROOM_PUBLIC_ENABLED` ma bezpieczny default `false`, `NewsroomPublicGate` centralizuje decyzję, public article/guide detail i historyczne redirecty są fail-closed przed cutoverem, a top-level placeholdery pozostają 200 + noindex,
- przy `false` Newsroom znika z author profile/author sitemap contribution oraz z namespace'ów zbieranych przez istniejący IndexNow collector; admin i private preview pozostają dostępne,
- exact-head CI #308, Browser Smoke #23 oraz post-merge CI #309 zakończyły się PASS; N4-001 `/aktualnosci` editorial composition read model jest następnym taskiem po dokumentacyjnym domknięciu N3-008.



### 2026-09-17 — v0.36

- NEWSROOM-N3-007 implementation PR #77 zmergowano jako `main@c68672f6aa7c41defaeec56debb541d5a60d9f4f` po exact-head CI #295 PASS; post-merge CI #296 również zakończył się pełnym PASS,
- finalny kod reużywa `ContentAuthor`, rozdziela published/needs_review/archived na profilu, współdzieli stabilny Person schema builder i rozszerza author sitemap o Newsroom z `public_state_changed_at`,
- modelowy invariant blokuje odpublikowanie autora z zależnym indexable Newsroom article; `ContentAuthorProfileTest` pokrywa lifecycle, schema, sitemap freshness i unpublish guard,
- skorygowano wcześniejszy sprzeczny zapis zakresu: `needs_review+indexable` nie jest wykluczane z profilu, tylko pozostaje w osobnej, jawnie oznaczonej sekcji,
- wykonawczy workflow doprecyzowano o legalny split implementation PR -> post-merge main gate -> osobny docs-sync PR z własnym exact-head gate,
- następny task: NEWSROOM-N3-008 — Public rollout config gate.

### 2026-09-17 — v0.35

- NEWSROOM-N3-006 zmergowano przez PR #75; finalny implementation head `f48e7a12fa6c53422cd2ef8c369af81769163b9d`, a zweryfikowany post-merge `main` to `33d9946219595a4be75d789b19cc8d10efc2ecc0`,
- publiczny old-path flow reużywa istniejący `ContentArticlePathResolver` i działa tylko po current-canonical `not_found`; current 200 oraz withdrawn 410 nie są nadpisywane,
- resolver akceptuje tylko persisted 301 bezpośrednio do bieżącego canonical; stale/malformed/self-loop records failują do 404, a query params nie są forwardowane,
- `NewsroomArticleRedirectTest` pokrywa one-hop redirects dla newsroom/guides oraz invalid redirect state; CI #279, Browser Smoke #21 i post-merge CI #280 są PASS,
- NEWSROOM-N3-007 author-profile integration staje się pierwszym następnym taskiem; N3-008/N4/N5 pozostają otwarte.

### 2026-09-17 — v0.34

- NEWSROOM-N3-005 zmergowano przez PR #73; finalny implementation head `7d795b895865cda49ba94a4fec50533d7b0f7a97`, a zweryfikowany post-merge `main` to `fcc8074f89db141d522c5000742afc2e07a68565`,
- dodano `NewsroomArticleProductBridgeService` i `newsroom/product-bridge-block.blade.php`; existing article renderer obsługuje teraz `question_group`, `legal_reference`, `traffic_sign_group` i `product_cta`,
- question/legal/sign target wymaga równocześnie wskazania w body, article-owned pivotu i public eligibility; inactive/draft/unlinked targety oraz internal notes/official excerpt nie wyciekają,
- traffic signs są filtrowane przez pivot `relation_type in ['direct','example']`; luźny `related` jest fail-closed zgodnie z nadrzędnym UI/UX contract,
- contextual CTA reużywa istniejące trasy `public.tests`, `public.questions.hub` i `session.index`; nie dodano migracji, reverse links, N3-006 ani N3-008,
- `NewsroomProductBridgeTest` oraz Browser Smoke #20 chronią Product Bridge; finalny CI #275 i Browser #20 były PASS, a post-merge CI #276 na `main@fcc8074f...` zakończył się pełnym PASS (`quality` 1049 passed / 19 498 assertions / 2 skipped, Pint 1051 files PASS, frontend build PASS; `newsroom-postgres` PASS),
- następnym taskiem wykonawczym jest NEWSROOM-N3-006 historical redirect resolver; N3-007/N3-008 pozostają otwarte.

### 2026-09-17 — v0.33

- NEWSROOM-N3-004 zmergowano przez PR #71 na `main@7398c5d930d38d6cc9d9953e53b4298df41cfce8`; finalny exact-head implementacji `5d164e10ca83e6003b52ec279a16234ec5bcea96` przeszedł CI #269 i Browser Smoke #18, a post-merge push-CI #270 na exact `main` zakończył się pełnym PASS,
- publiczne route’y `/aktualnosci/{articleSlug}` i `/poradniki/{articleSlug}` są obsługiwane przez `ContentArticleController`; 200/404/410 wynika z istniejącego public catalog contract, a historyczne old-path 301 pozostają N3-006,
- `NewsroomArticlePresentationService` i `NewsroomArticleBodyRenderer` materializują breadcrumbs, hero/focal-point presentation, public sources, provenance/regulatory context, correction, author box oraz publiczne rich-text/image/quote/table/context/related-article bloki,
- SEO metadata z N3-002 i schema graph z N3-003 są emitowane w publicznym article response; prywatne źródła/licence note nie wyciekają,
- `legal_reference`, `question_group`, `traffic_sign_group` i `product_cta` są nadal publicznie odroczone i pozostają NEWSROOM-N3-005; regresje i Browser QA potwierdzają brak tych placeholderów na publicznym detailu,
- Browser Smoke #18 przeszedł 360/390/430/768/1024/1440; finalny CI #270 potwierdził `quality` 1048 passed / 19 473 assertions / 2 skipped, Pint 1049 files PASS, frontend build 9.06 s oraz `newsroom-postgres` 7 passed / 94 assertions,
- następnym taskiem wykonawczym jest NEWSROOM-N3-005 `Product Bridge`; N3-006 redirecty i N3-008 rollout gate pozostają otwarte.

### 2026-09-17 — v0.32

- NEWSROOM-N3-003 zmergowano przez PR #68 na `main@cdef77c66459537c98b2e044a76a97c082af2ef2`; exact-head PR CI #242 PASS. Finalny zweryfikowany `main@5f85bec331428a73f3859ae40b555f55e7e7820d` po osobnym deterministic fixture fixie przeszedł push-CI #245: 1043 passed / 19 418 assertions / 2 skipped, Pint 1045 files, frontend build 9.04 s, PostgreSQL 7/94,
- `ContentArticleSchemaService` materializuje stabilny Organization/WebSite/WebPage/NewsArticle-or-Article/Person/Breadcrumb/ImageObject graph nad canonical/date metadata z N3-002, bez duplikowania publisher/author literals,
- testy pokrywają type mapping, canonical/date consistency, breadcrumbs, stable entity IDs, path-backed/deduplicated images oraz fail-closed hidden-author/category states,
- publiczne detail controllers/Blade na tym etapie pozostawały 404; N3-003 nie był utożsamiany z publicznym JSON-LD response surface,
- następnym taskiem wykonawczym był NEWSROOM-N3-004 `Article Blade page + block renderer`; historyczne 301 pozostawały N3-006.

### 2026-09-17 — v0.31

- NEWSROOM-N3-002 zmergowano przez PR #66 na `main@d9be735eee1915f4b53a6de42ec665a37442acae`; exact-head PR CI #236 PASS, finalny push-CI #237 PASS: 1034 passed / 19 376 assertions / 2 skipped, Pint 1043 files PASS, frontend build 8.50 s, PostgreSQL 7 passed / 94 assertions,
- `ContentArticleSeoService` materializuje self-canonical z route family + slug, title/description fallbacks, robots policy, OG/hero image fallback oraz publication/substantive-modification dates bez CMS canonical override,
- metadata są kompatybilne z istniejącym `public-content.blade.php`; na tym etapie publiczne detail controllers/Blade były jeszcze wyłączone, schema graph pozostawał N3-003, article page N3-004, historyczny redirect N3-006,
- następnym taskiem wykonawczym był NEWSROOM-N3-003 `Article schema graph service`.

### 2026-09-17 — v0.30

- PR #64 zmergowano na `main@8215e142af2cd88c7335f17bc085bbd0c04d5790`; exact-head PR CI #231 PASS, a finalny push-CI #232 na `main` zakończył się `quality` PASS (1029 passed / 19 335 assertions / 2 skipped, Pint PASS, frontend build PASS) oraz `newsroom-postgres` PASS,
- NEWSROOM-N3-001 jest **DONE**: wdrożono route-family-scoped current-canonical lookup, osobny active-distribution list query, explicit 200/410/404 resolution semantics oraz public-safe eager-load/column policy,
- testy pokrywają published/needs_review/archived detail visibility, hidden draft/scheduled/never-published states, withdrawn tombstone, route-family isolation, deterministic chronology i private-field exclusion; PostgreSQL gate obejmuje N3-001,
- N3-001 nie uruchamiał jeszcze publicznych controllerów ani historycznych 301; detail routes pozostawały 404 do dalszych N3 tasków, a redirect resolver pozostawał N3-006,
- następnym taskiem wykonawczym był NEWSROOM-N3-002 `Article SEO service`.

### 2026-09-16 — v0.29

- PR #62 zmergowano na `main@ff81f92fe75442b60e67297f3945d2a63b7c5128` po exact-head CI #224 dla `c62ea2974572299b725387ee90ba7d38bfe0493e`; `quality` PASS: 1025 passed / 19 297 assertions / 2 skipped, Pint 1038 files PASS, frontend build PASS (9.99 s), a `newsroom-postgres` PASS: 7 passed / 89 assertions,
- NEWSROOM-N2-011 jest **DONE** w warstwie admin/domain: istnieje `ContentTopicResource`, kontrolowany `ContentTopicPublishingService`, modelowe identity/status guards, corpus/featured eligibility, health/promotability i AuditLog,
- topic corpus nadal nie ma ręcznego rankingu; pojedynczy featured article jest kontrolowany, a publish/republish wymaga własnego opisu i minimum 3 actively-distributed + indexable linked articles,
- po pierwszej publikacji slug pozostaje immutable i zwykły delete jest zablokowany; spadek corpus poniżej baseline daje warning/wyłączenie z promocji bez automatycznego status/HTTP flip,
- publiczny topic controller, 410 archived topicu, nav i sitemap nie zostały wdrożone w N2-011 i pozostają downstream N4/N5,
- etap N2 jest zamknięty; następnym taskiem wykonawczym jest NEWSROOM-N3-001 `Public catalog service`.

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
- future feed/category/topic/detail routes były świadomie 404 do czasu wdrożenia downstream publicznych controllerów,
- record-level ContentArticle lookup pozostawał wymaganiem downstream i nie był deklarowany jako ukończony w N0-002.

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
