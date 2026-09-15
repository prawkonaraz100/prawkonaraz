# Newsroom Data Model and Domain Specification

## 1. Status

- Status: Proposed / implementation-ready design
- Obszar: newsroom / media portal
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Bazowy stan repo przy projektowaniu: main@6a38c95ce76ee05997977d614d795ed8513462f1
- Data: 2026-09-15
- Zakres: model domenowy, baza danych, invariants, serwisy aplikacyjne, routing domeny i kolejność migracji

Ten dokument opisuje docelowy model danych newsroomu. Nie oznacza, że opisane tabele lub klasy już istnieją. Stan wdrożenia należy aktualizować po każdej zmianie kodu.

---

## 2. Źródła nadrzędne

W razie konfliktu:

1. STACK-DECISION.md
2. ADR-001-MODULAR-MONOLITH.md
3. DATABASE-SCHEMA.md dla zasad ogólnych bazy
4. NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md
5. ten dokument

Istniejące wzorce, które należy zachować:

- Laravel + PostgreSQL,
- jedna aplikacja i jedna główna baza,
- Eloquent models,
- Filament jako backoffice,
- publiczny content SSR/Blade,
- osobne serwisy katalogowe/SEO/schema zamiast ciężkich kontrolerów,
- jawne workflow i freshness podobne do traffic signs i legal content,
- ContentAuthor jako istniejący byt autora.

---

## 3. Granica domeny

Newsroom odpowiada za:

- artykuły redakcyjne,
- kategorie newsroomu,
- tagi,
- źródła materiału,
- stan redakcyjny i publikacyjny,
- scheduling,
- ekspozycję featured/breaking,
- powiązania artykułów z pytaniami, przepisami i znakami,
- redirect history po zmianie slugów,
- dane potrzebne do publicznych hubów i feedów.

Newsroom nie odpowiada za:

- treść pytań egzaminacyjnych,
- legal acts / legal units,
- traffic signs,
- auth,
- role organizacyjne OSK,
- płatności,
- sesje nauki,
- media storage jako osobny subsystem.

Relacje do tych domen mają być jawne, ale newsroom nie przejmuje ich własności.

---

## 4. Agregat główny

Agregatem głównym jest ContentArticle.

Jeden model obsługuje różne rodziny treści przez kontrolowany enum type:

- news
- guide
- explainer
- analysis
- report

Nie tworzymy osobnych tabel news_articles, guides i analyses, dopóki nie pojawi się realna różnica invariants lub cyklu życia.

### 4.1. Dlaczego jeden agregat

Korzyści:

- wspólny workflow,
- jeden system autorów,
- jeden system źródeł,
- jeden system linkowania do produktu,
- jeden mechanizm sitemap/feed,
- jedna wyszukiwarka administracyjna,
- brak duplikacji SEO/publishing code.

---

## 5. Tabela content_articles

### 5.1. Tożsamość i klasyfikacja

Pola:

- id: bigint primary key
- type: varchar(32), wymagane
- category_id: FK -> content_categories, wymagane
- author_id: FK -> content_authors, wymagane przy publikacji
- reviewer_id: FK -> content_authors, nullable
- created_by_user_id: FK -> users, nullable
- updated_by_user_id: FK -> users, nullable
- origin_type: varchar(32), default original

### 5.2. Treść

- title: varchar(255), wymagane
- slug: varchar(255), unique
- lead: text, wymagane przy publikacji
- body_blocks: jsonb, wymagane przy publikacji
- key_points: jsonb nullable
- correction_note: text nullable
- editorial_note: text nullable, tylko backoffice

`body_blocks` jest kanonicznym, uporządkowanym dokumentem artykułu. Nie utrzymujemy równolegle drugiego pełnego źródła `body_html` albo `body_markdown`.

Każdy blok ma co najmniej:

- `type`,
- `data`,
- opcjonalne stabilne `key`/ID techniczne potrzebne do edycji.

Dozwolone typy v1:

- rich_text
- image
- quote
- table
- context
- related_article
- legal_reference
- question_group
- traffic_sign_group
- product_cta
- embed

Payload każdego typu ma osobny kontrakt walidacyjny. Arbitrary HTML/JS/CSS nie jest typem bloku.

### 5.2.1. Kontekst regulacyjny / egzaminacyjny

Dla materiałów, w których ma to zastosowanie:

- regulatory_status: varchar(32), default not_applicable
- effective_from: date nullable
- change_summary: text nullable
- applies_to: text nullable
- exam_impact: text nullable

Dozwolone `regulatory_status` v1:

- not_applicable
- proposal
- consultation
- official_announcement
- adopted_future
- in_force

Te pola nie zastępują body ani źródeł. Służą do kontroli redakcyjnej i renderowania powtarzalnego boxu „co się zmienia / od kiedy / kogo dotyczy / wpływ na egzamin”.

### 5.3. Publikacja

- workflow_status: varchar(32), default draft
- published_at: timestamptz nullable
- first_published_at: timestamptz nullable
- scheduled_for: timestamptz nullable
- reviewed_at: timestamptz nullable
- needs_review_at: timestamptz nullable
- archived_at: timestamptz nullable

Zalecenie: w PostgreSQL używać timestamp with time zone dla zdarzeń publikacyjnych. Warstwa aplikacyjna prezentuje daty publiczne w Europe/Warsaw.

### 5.4. Ekspozycja redakcyjna

- is_featured: boolean default false
- is_breaking: boolean default false
- breaking_expires_at: timestamptz nullable
- editorial_priority: smallint default 0
- featured_position: smallint nullable

Nie kodujemy layoutu strony głównej w rekordzie artykułu. Powyższe pola opisują priorytet treści, nie strukturę strony.

### 5.5. Media

- hero_image_path: varchar(1024) nullable
- hero_image_alt: varchar(500) nullable
- hero_image_width: unsigned integer nullable
- hero_image_height: unsigned integer nullable
- hero_focal_x: numeric(5,4) nullable
- hero_focal_y: numeric(5,4) nullable
- og_image_path: varchar(1024) nullable
- og_image_width: unsigned integer nullable
- og_image_height: unsigned integer nullable
- image_credit: varchar(500) nullable
- image_license_note: text nullable

Storage i public URL rozwiązujemy przez istniejący media layer, nie przez ręczne sklejanie URL.

Focal point używa znormalizowanych współrzędnych 0..1. Brak wartości oznacza środek obrazu. Warianty lead/standard/compact/OG są pochodnymi assetu i nie powinny być ręcznie przechowywanymi, niezależnymi kopiami, jeśli media layer może wygenerować je deterministycznie.

### 5.6. SEO

- seo_title: varchar(255) nullable
- seo_description: varchar(320) nullable
- canonical_url: varchar(2048) nullable
- robots: varchar(128) nullable

Domyślne zasady:

- brak canonical_url oznacza self-canonical,
- brak robots oznacza policy wynikające ze statusu,
- draft/in_review/scheduled preview nie jest indeksowalny,
- published domyślnie index,follow,max-image-preview:large.

### 5.7. Freshness

- source_checked_at: timestamptz nullable
- freshness_review_due_at: timestamptz nullable
- last_substantive_update_at: timestamptz nullable

updated_at nie jest automatycznie równoważne istotnej aktualizacji merytorycznej.

### 5.8. Standardowe timestamps

- created_at
- updated_at

---

## 6. Constraints dla content_articles

### 6.1. Unikalność

- unique(slug)

### 6.2. Dozwolone typy

Dozwolone type:

- news
- guide
- explainer
- analysis
- report

W pierwszej wersji walidacja może być aplikacyjna przez PHP enum. Jeżeli dodajemy DB CHECK, musi być zgodny z enumem i objęty testem migracji.

### 6.3. Dozwolone workflow_status

- draft
- in_review
- scheduled
- published
- needs_review
- archived

### 6.4. Invariants publikacji

Status published wymaga:

- title != empty,
- slug != empty,
- lead != empty,
- body_blocks zawiera co najmniej jeden renderowalny blok,
- category_id != null,
- author_id != null,
- first_published_at != null,
- published_at != null.

Status scheduled wymaga:

- scheduled_for != null,
- kompletności jak dla publikacji,
- scheduled_for > moment przyjęcia komendy schedule.

### 6.5. Invariants breaking

is_breaking = true wymaga:

- status = published,
- type = news,
- breaking_expires_at != null.

Po breaking_expires_at materiał nie powinien być renderowany w module „pilne”, nawet jeśli flaga nie została jeszcze fizycznie wyzerowana.

---

## 7. Indeksy content_articles

Wymagane:

- unique index na slug
- index(workflow_status, published_at desc)
- index(category_id, workflow_status, published_at desc)
- index(type, workflow_status, published_at desc)
- index(is_featured, workflow_status, editorial_priority desc)
- index(is_breaking, breaking_expires_at)
- index(freshness_review_due_at)
- index(scheduled_for, workflow_status)

Nie dodawać indeksów „na zapas” dla pól, których nie używamy w zapytaniach.

---

## 8. Tabela content_categories

Pola:

- id
- name varchar(120)
- slug varchar(160) unique
- description text nullable
- position smallint default 0
- is_active boolean default true
- seo_title varchar(255) nullable
- seo_description varchar(320) nullable
- created_at
- updated_at

Kategorie v1:

- prawo-jazdy
- egzaminy
- przepisy
- word
- kierowcy
- osk

### 8.1. Invariants

- slug stabilny po publikacji kategorii,
- kategoria nie może być usunięta, jeśli ma artykuły; użyć is_active=false,
- jedna kategoria główna na artykuł w v1.

---

## 9. Tabela content_tags

Pola:

- id
- name varchar(120)
- slug varchar(160) unique
- created_at
- updated_at

Pivot:

content_article_tag

- article_id
- tag_id
- created_at
- unique(article_id, tag_id)

Tagi nie tworzą automatycznie stron indeksowalnych. Publiczne tag pages wymagają osobnej decyzji SEO.

---

## 10. Tabela content_topics

Topic/dossier jest ręcznie zarządzanym hubem redakcyjnym. Nie jest aliasem taga.

Pola:

- id
- title varchar(180)
- slug varchar(200) unique
- description text
- status varchar(32) default draft
- featured_article_id FK -> content_articles nullable
- seo_title varchar(255) nullable
- seo_description varchar(320) nullable
- published_at timestamptz nullable
- created_at
- updated_at

Dozwolone statusy v1:

- draft
- published
- archived

Pivot:

content_article_topic

- article_id
- topic_id
- sort_order smallint default 0
- created_at
- unique(article_id, topic_id)

Reguły:

- publiczny topic wymaga własnego opisu i statusu published,
- samo przypięcie taga nie tworzy topicu,
- featured_article_id musi wskazywać publiczny artykuł przy renderowaniu,
- brak wystarczającego corpus oznacza, że topic pozostaje draftem.

---

## 11. Tabela content_article_sources

Każdy materiał informacyjny musi wspierać wiele źródeł.

Pola:

- id
- article_id FK -> content_articles cascade delete
- source_type varchar(32)
- publisher varchar(255) nullable
- title varchar(500)
- url varchar(2048)
- published_at timestamptz nullable
- accessed_at timestamptz nullable
- is_primary boolean default false
- is_official boolean default false
- note text nullable
- sort_order smallint default 0
- created_at
- updated_at

### 10.1. source_type v1

- official
- legislation
- institution
- primary_data
- interview
- report
- media
- other

### 10.2. Reguły

- news o zmianie prawa powinien mieć co najmniej jedno źródło official lub legislation, jeśli takie istnieje,
- media konkurencyjne nie są domyślnym źródłem pierwotnym,
- URL źródła publicznego jest renderowany tylko jeśli redakcja oznaczy go jako bezpieczny do publikacji,
- accessed_at zapisujemy dla źródeł webowych, gdy ma znaczenie weryfikacyjne.

---

## 12. Relacja artykuł -> pytania

Tabela:

content_article_question

Pola:

- id
- article_id
- question_id
- relation_type varchar(32)
- sort_order smallint default 0
- note text nullable
- created_at
- updated_at

Dozwolone relation_type:

- direct
- practice
- background
- related

Constraints:

- unique(article_id, question_id)

Publiczny renderer pokazuje tylko aktywne/publiczne pytania zgodnie z regułami domeny Questions.

Newsroom nie może zmieniać stanu pytania.

---

## 13. Relacja artykuł -> legal units

Tabela:

content_article_legal_unit

Pola:

- id
- article_id
- legal_unit_id
- relation_type varchar(32)
- sort_order smallint default 0
- note text nullable
- created_at
- updated_at

relation_type:

- direct_basis
- changed_rule
- supporting_context
- related

Constraints:

- unique(article_id, legal_unit_id)

Newsroom nie duplikuje official_excerpt ani treści aktu prawnego.

---

## 14. Relacja artykuł -> traffic signs

Tabela:

content_article_traffic_sign

Pola:

- id
- article_id
- traffic_sign_id
- relation_type varchar(32)
- sort_order smallint default 0
- created_at
- updated_at

relation_type:

- direct
- example
- related

Wdrożenie może zostać przesunięte do N3/N4, jeśli N1 wymaga ograniczenia scope.

---

## 15. Historia slugów i redirecty

Tabela:

content_article_redirects

Pola:

- id
- article_id
- from_path varchar(1024) unique
- to_path varchar(1024)
- http_status smallint default 301
- created_at
- updated_at

### 15.1. Reguły

- zmiana sluga opublikowanego artykułu tworzy redirect,
- nie tworzymy redirect chain; nowy wpis powinien wskazywać canonical destination,
- to_path zawsze lokalny canonical path dla własnego contentu,
- usunięcie artykułu nie oznacza automatycznego redirectu do huba,
- 410/404 jest lepsze niż niepowiązany redirect.

---

## 16. Ekspozycja strony głównej — content_home_placements

Kontrolowane placements wchodzą do newsroom v1. Nie jest to page builder.

Tabela:

content_home_placements

Pola:

- id
- surface_key varchar(64), default newsroom_home
- slot_key varchar(64)
- context_key varchar(120) nullable
- position smallint default 0
- article_id FK -> content_articles
- starts_at timestamptz nullable
- ends_at timestamptz nullable
- created_by_user_id FK -> users nullable
- updated_by_user_id FK -> users nullable
- created_at
- updated_at

### 16.1. Stałe sloty v1

Kod, nie baza, definiuje dozwolone sloty:

- lead
- secondary
- category_lead
- guides_lead

`context_key` służy np. do rozróżnienia kategorii przy `category_lead`.

Nie pozwalamy administratorowi tworzyć dowolnych nowych nazw modułów w bazie.

### 16.2. Czas ekspozycji

- null starts_at oznacza aktywność od razu po spełnieniu innych warunków,
- null ends_at oznacza brak automatycznego końca,
- artykuł musi być publiczny w czasie, dla którego rozwiązujemy kompozycję,
- scheduled article może być widoczny w future preview, ale nie w bieżącej stronie przed publikacją.

### 16.3. Kolizje placements

Application service nie pozwala na nierozstrzygnięte nakładanie się dwóch aktywnych rekordów dla tego samego:

- surface_key,
- slot_key,
- context_key,
- position.

Nie próbujemy modelować przedziałów czasowych przez skomplikowany DB exclusion constraint w pierwszej wersji; invariant ma testy domenowe i transakcyjne.

### 16.4. Fallback i deduplikacja

NewsroomHomeCompositionService:

1. rozwiązuje aktywne ręczne placementy,
2. waliduje, że wskazane artykuły są publiczne dla czasu renderowania,
3. uzupełnia puste sloty deterministycznym fallbackiem,
4. prowadzi zbiór wykorzystanych article IDs,
5. nie powtarza artykułu w kolejnych card modules,
6. jeśli brakuje unikalnego kandydata, zwraca krótszą sekcję zamiast duplikatu.

Breaking strip jest niezależnym alertem i może wskazywać ten sam artykuł co lead, ponieważ nie jest kolejną kartą contentową.

---

## 17. Lokalność i WORD

V1 modeluje kategorię WORD, ale nie tworzy masowo lokalnych hubów.

Przyszły model może zawierać:

content_locations

- id
- type: voivodeship/city/word
- name
- slug
- parent_id nullable
- external_reference nullable

oraz pivot content_article_location.

Ta część jest deferred i nie blokuje newsroom v1.

---

## 18. PHP enums

Rekomendowane enumy:

- ContentArticleType
- ContentArticleWorkflowStatus
- ContentArticleSourceType
- ContentArticleQuestionRelationType
- ContentArticleLegalRelationType
- ContentArticleOriginType
- ContentArticleRegulatoryStatus

Enum ma być jedynym źródłem listy wartości w logice aplikacyjnej.

Filament Select, request validation i serwisy korzystają z enumów zamiast powtarzać magic strings.

---

## 19. Model Eloquent ContentArticle

Odpowiedzialności modelu:

- casts,
- relacje,
- scopes,
- proste predicates,
- route key slug.

Model nie powinien:

- wykonywać publikacji wieloetapowej,
- czyścić cache samodzielnie,
- budować schema.org,
- generować feedu,
- zarządzać redirectami w observerze bez use-case layer.

### 19.1. Casts

- is_featured: boolean
- is_breaking: boolean
- body_blocks: array
- key_points: array
- hero_focal_x: decimal
- hero_focal_y: decimal
- effective_from: immutable_date
- published_at: immutable_datetime
- first_published_at: immutable_datetime
- scheduled_for: immutable_datetime
- reviewed_at: immutable_datetime
- needs_review_at: immutable_datetime
- breaking_expires_at: immutable_datetime
- source_checked_at: immutable_datetime
- freshness_review_due_at: immutable_datetime
- last_substantive_update_at: immutable_datetime

### 18.2. Scopes

- published()
- scheduled()
- latestPublished()
- forCategory()
- featured()
- activeBreaking()
- needsFreshnessReview()

Definicja published:

- workflow_status = published
- published_at != null
- published_at <= now()

---

## 19. Serwisy domenowe / application services

Rekomendowane klasy w app/Support/Newsroom lub analogicznej, jasno wydzielonej przestrzeni nazw.

### 19.1. ContentArticlePublishingService

Odpowiada za:

- publish,
- schedule,
- unpublish do in_review/draft zgodnie z policy,
- archive,
- timestamps,
- walidację invariants,
- dispatch domenowych eventów.

### 19.2. ContentArticleSlugService

Odpowiada za:

- normalizację sluga,
- unique slug,
- zmianę sluga,
- tworzenie redirect history.

### 19.3. ContentArticleCatalogService

Odpowiada za read-side:

- latest,
- hub sections,
- category listings,
- related content,
- product bridge data.

Nie mieszać write workflow z katalogiem publicznym.

### 19.4. ContentArticleSeoService

Odpowiada za:

- title,
- description,
- canonical,
- robots,
- published/modified metadata,
- OG/Twitter meta model.

### 19.5. ContentArticleSchemaService

Odpowiada za:

- NewsArticle/Article JSON-LD,
- author Person,
- publisher Organization,
- BreadcrumbList.

### 19.6. ContentArticleFreshnessService

Odpowiada za:

- due dates,
- needs_review transitions,
- listę materiałów przeterminowanych,
- politykę freshness per type/category.

---

## 20. Events

Rekomendowane domain/application events:

- ContentArticlePublished
- ContentArticleUpdated
- ContentArticleArchived
- ContentArticleSlugChanged
- ContentArticleBreakingChanged

Listenery mogą:

- czyścić cache,
- zgłaszać URL do IndexNow, jeśli policy to dopuszcza,
- odświeżać feed cache,
- odświeżać sitemap cache.

Event nie powinien wykonywać ciężkiej logiki synchronicznie w request bez potrzeby.

---

## 21. Scheduling

Scheduled publishing wymaga deterministycznego procesu.

Rekomendacja:

- scheduler uruchamia komendę np. newsroom:publish-due,
- query pobiera tylko workflow_status=scheduled i scheduled_for <= now(),
- publikacja przechodzi przez ContentArticlePublishingService,
- komenda jest idempotentna.

### 21.1. Race safety

Dwa równoległe uruchomienia nie mogą opublikować artykułu dwa razy ani nadpisać first_published_at.

Rekomendacja:

- transakcja DB,
- row lock lub atomic conditional update,
- test współbieżności na poziomie możliwym w obecnym stacku.

---

## 22. Time semantics

Publiczne daty:

- first_published_at: pierwsza publikacja; nigdy nie resetować przy zwykłej edycji,
- published_at: bieżący timestamp aktywnej publikacji; w v1 może równać się first_published_at po pierwszym publish,
- last_substantive_update_at: istotna zmiana treści,
- updated_at: techniczny timestamp rekordu.

Structured data datePublished bierze first_published_at.
dateModified bierze last_substantive_update_at albo kontrolowany modified timestamp, nie dowolny touch rekordu.

---

## 23. Author i reviewer

Używamy istniejącego ContentAuthor.

Nie tworzymy NewsroomAuthor.

Wymagania do publikacji:

- author aktywny/publiczny,
- author ma slug,
- publiczny profil autora dostępny pod istniejącym route,
- reviewer opcjonalny zależnie od policy.

Dla treści prawnie wrażliwych reviewer może być wymagany przez Editorial Policy, nie przez wszystkie typy globalnie.

---

## 24. Walidacja i sanitization body_blocks

Dokładny komponent edytora i serializacja wewnętrzna są decyzją N0-004, ale kontrakt domenowy jest stały:

- `body_blocks` jest jednym kanonicznym źródłem body,
- każdy `type` ma allowlistowany schema payloadu,
- rich_text sanitizuje HTML/doc nodes,
- script/style/event handlers są zabronione,
- embed przyjmuje tylko allowlisted providers/URL,
- linki z `target=_blank` otrzymują bezpieczne `rel`,
- block renderer ignoruje/odrzuca nieznany typ zamiast wykonywać go jako HTML,
- publiczny renderer nie interpretuje arbitralnych klas CSS przekazanych z CMS.

Nie utrzymujemy pełnego `body_html` i `body_blocks` jako dwóch edytowalnych źródeł prawdy.

---

## 25. Search w adminie

N1:

- PostgreSQL ILIKE / Filament search po title i slug wystarcza.

Nie wdrażamy Elasticsearch/Meilisearch tylko dla CMS.

Publiczna wyszukiwarka artykułów nie jest wymogiem newsroom v1.

---

## 26. Cache model

Cache keys powinny być oparte o publiczne read models, np.:

- newsroom:home:v1
- newsroom:category:{slug}:page:{n}
- newsroom:article:{id}:public
- newsroom:feed:latest

Cache invalidation przez eventy publikacyjne.

Nie cache’ujemy preview jako publicznej strony.

---

## 27. Route binding

ContentArticle:

- getRouteKeyName() => slug

Publiczny controller nie powinien polegać tylko na implicit binding, jeśli musimy rozróżnić opublikowany vs nieopublikowany rekord.

Preferowany publiczny lookup:

ContentArticleCatalogService::findPublishedBySlug($slug)

Preview używa oddzielnej ścieżki i policy.

---

## 28. Publiczne route contracts

V1:

- GET /aktualnosci
- GET /aktualnosci/{slug}
- GET /aktualnosci/kategoria/{categorySlug}
- GET /poradniki
- GET /poradniki/{slug}
- GET /aktualnosci/feed.xml
- sitemap endpoints zgodne z istniejącym SitemapController pattern

### 28.1. Konflikt slug vs category

Nie wolno pozostawić niejednoznaczności:

/aktualnosci/{slug}

nie może równocześnie oznaczać kategorii i artykułu bez jawnej reguły.

Przyjęty wariant:

- /aktualnosci
- /aktualnosci/kategoria/{categorySlug}
- /aktualnosci/{articleSlug}

Jest jednoznaczny dla routingu i przyszłych zmian. Zmiana na krótsze category URLs wymaga zmiany decyzji architektonicznej, reserved-slug policy i testów konfliktów.

---

## 29. Migracje — kolejność

Rekomendowane migracje:

1. create_content_categories_table
2. create_content_tags_table
3. create_content_articles_table
4. create_content_topics_table
5. create_content_article_tag_table
6. create_content_article_topic_table
7. create_content_article_sources_table
8. create_content_article_question_table
9. create_content_article_legal_unit_table
10. create_content_article_traffic_sign_table
11. create_content_article_redirects_table
12. create_content_home_placements_table

Nie łączymy wszystkiego w jedną migrację, jeżeli utrudnia to rollback i review.

---

## 30. Seed danych systemowych

Kategorie v1 mogą być seedowane deterministycznie.

Seeder:

- updateOrCreate po slug,
- nie nadpisuje ręcznie zmienionej treści SEO bez jawnej decyzji,
- działa wielokrotnie bez duplikatów.

Nie seedujemy sztucznych produkcyjnych artykułów.

Test fixtures pozostają w factories/seed smoke data.

---

## 31. Factories

Potrzebne:

- ContentCategoryFactory
- ContentArticleFactory
- ContentArticleSourceFactory
- ContentTagFactory
- ContentTopicFactory
- ContentHomePlacementFactory

Stany factory:

- draft
- inReview
- scheduled
- published
- breaking
- needsReview
- archived

Factories mają umożliwiać czytelne testy workflow.

---

## 32. Deletion policy

Artykuł opublikowany:

- domyślnie archiwizujemy zamiast hard delete,
- hard delete tylko administracyjnie dla błędnych/testowych rekordów bez historii publicznej.

Category:

- nie hard-delete, jeśli istnieją artykuły.

Source/tag pivots:

- cascade delete z artykułem jest dopuszczalne.

Redirect history:

- zachowujemy tak długo, jak artykuł ma publiczną historię URL.

---

## 33. Audit

Istniejący AuditLog powinien być użyty tam, gdzie pasuje do architektury.

Minimum audit events:

- create article,
- change workflow status,
- publish,
- schedule,
- archive,
- slug change,
- breaking toggle,
- source removal po publikacji,
- author/reviewer change po publikacji.

Audit nie zastępuje zwykłego updated_at.

---

## 34. Uprawnienia

N1 może korzystać z istniejącej roli administratora, ale kod ma przygotować policies.

Docelowe abilities:

- viewAny newsroom
- create article
- update draft
- submit for review
- review article
- publish article
- archive article
- manage categories
- manage sources

Nie zakładamy roli wyłącznie na podstawie ukrycia przycisku Filament. Backend policy jest źródłem autoryzacji.

---

## 35. Polityka zmian modelu

Każde nowe pole musi mieć odpowiedź na:

- kto je zapisuje,
- kto je czyta,
- jaki ma default,
- czy może być null,
- czy wymaga indeksu,
- jak wpływa na istniejące rekordy,
- jak wygląda rollback.

Nie dodajemy pól „może kiedyś się przyda”.

---

## 36. Mapa plików — target

Przykładowa struktura zgodna z obecnym repo:

~~~text
app/
  Models/
    ContentArticle.php
    ContentCategory.php
    ContentTag.php
    ContentTopic.php
    ContentArticleSource.php
    ContentHomePlacement.php

  Support/
    Newsroom/
      ContentArticleCatalogService.php
      ContentArticlePublishingService.php
      ContentArticleSlugService.php
      ContentArticleSeoService.php
      ContentArticleSchemaService.php
      ContentArticleFreshnessService.php
      NewsroomHomeCompositionService.php
      ContentTopicCatalogService.php

  Http/Controllers/
    NewsroomHomeController.php
    NewsroomArticleController.php
    NewsroomCategoryController.php
    GuidesController.php
    NewsroomFeedController.php

  Filament/Resources/
    ContentArticles/
    ContentCategories/
    ContentTopics/

resources/views/
  newsroom/
    index.blade.php
    show.blade.php
    category.blade.php
    partials/

database/
  migrations/
  factories/
  seeders/
~~~

Nazwy można dostosować do konwencji repo, ale granice odpowiedzialności powinny zostać zachowane.

---

## 37. Data query contracts

### 37.1. Newsroom home

Musi zwrócić gotowy, ograniczony read model:

- lead story,
- secondary stories,
- latest N,
- per-category N,
- guides N,
- opcjonalny breaking strip.

Źródłem kompozycji jest `NewsroomHomeCompositionService`, który łączy ręczne placements z fallbackami i deduplikacją.

Nie pobieramy całego corpusu i nie filtrujemy w PHP.

### 37.2. Category page

- published only,
- category_id,
- ordered by published_at desc,
- stabilna paginacja,
- eager load author + minimal media metadata.

### 37.3. Article show

- published by slug,
- author,
- reviewer jeśli publiczny,
- category,
- tags,
- topics,
- sources,
- related questions,
- legal units,
- traffic signs jeśli wdrożone.

---

## 38. Related content

V1:

- ręczne relacje do pytań i przepisów,
- powiązane artykuły mogą być liczone przez kategorię/tagi z możliwością ręcznego override później.

Nie wdrażamy ML recommendera.

---

## 39. Freshness defaults

Wartości są policy, nie twardym invariant DB.

Proponowane punkty startowe:

- news: review tylko gdy temat nadal aktywny; nie przepisujemy historii bez powodu,
- guide: 90–180 dni zależnie od tematu,
- explainer prawny: 60–90 dni lub event-driven po zmianie prawa,
- analysis/report: aktualizacja tylko gdy metodologia/dane wymagają korekty.

Finalne wartości definiuje dokument Editorial Operations.

---

## 40. Wpływ na DATABASE-SCHEMA.md

Ten dokument jest specyfikacją przed implementacją.

Po merge migracji N1 należy:

1. zaktualizować DATABASE-SCHEMA.md o rzeczywiście utworzone tabele,
2. nie kopiować planowanych pól, których finalnie nie wdrożono,
3. wskazać ten dokument jako szczegółowy kontrakt newsroomu.

---

## 41. Definition of Done modelu danych

Model danych jest gotowy, gdy:

- migracje przechodzą up/down,
- constraints i indeksy są przetestowane,
- factories pokrywają główne statusy,
- publikacja nie może stworzyć niekompletnego publicznego rekordu,
- scheduling jest idempotentny,
- slug change zachowuje redirect history,
- relations do questions/legal są jawne,
- `body_blocks` przechodzą walidację per block type,
- homepage placements mają fallback i deduplikację,
- focal point ma poprawny zakres 0..1,
- topic nie powstaje automatycznie z taga,
- admin policies nie opierają się wyłącznie na UI,
- current DATABASE-SCHEMA.md odzwierciedla faktyczny kod.

---

## 42. Stan implementacji

Na moment utworzenia dokumentu:

- ContentAuthor istnieje,
- legal trust layer istnieje,
- traffic signs workflow istnieje,
- newsroom tables nie istnieją,
- ContentArticle nie istnieje,
- newsroom CMS nie istnieje,
- route /aktualnosci jest placeholderem.

---

## 43. Pozostałe zadania

- [ ] finalizować naming tabel i klas,
- [ ] domknąć N0-004: serializacja bloków + editor + sanitizer,
- [ ] wdrożyć model topics,
- [ ] wdrożyć home placements/composition service,
- [ ] wdrożyć focal point w media contract,
- [ ] wdrożyć origin/regulatory context fields,
- [ ] wdrożyć migracje,
- [ ] wdrożyć enumy,
- [ ] wdrożyć modele i factories,
- [ ] wdrożyć policies,
- [ ] wdrożyć publishing service,
- [ ] wdrożyć scheduling,
- [ ] wdrożyć slug redirects,
- [ ] zaktualizować DATABASE-SCHEMA.md po faktycznej implementacji.

---

## 44. Historia zmian

### 2026-09-15 — v0.2

- zastąpiono plan pojedynczego `body` kanonicznym `body_blocks`,
- dodano kontrolowaną bibliotekę typów bloków i pola kontekstu regulacyjnego,
- dodano `origin_type`, focal point oraz model topics/dossier,
- awansowano `content_home_placements` do zakresu v1 wraz z fallbackiem i deduplikacją,
- nie dodano systemu revision snapshots zgodnie z decyzją produktową,
- audio/AI pozostają rozszerzeniem bez prealokowania pól w bazie.

### 2026-09-15 — v0.1

- utworzono wykonawczą specyfikację modelu danych newsroomu,
- rozpisano constraints, indeksy, workflow i relacje,
- ustalono wykorzystanie istniejącego ContentAuthor i legal trust layer,
- rozdzielono write-side publishing od publicznego catalog read-side,
- zapisano strategię slug redirects, scheduling i cache events.
