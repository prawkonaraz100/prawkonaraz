# Newsroom Data Model and Domain Specification

## 1. Status

- Status: Proposed / implementation-ready design
- Obszar: newsroom / media portal
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Bazowy stan repo przy projektowaniu: main@6a38c95ce76ee05997977d614d795ed8513462f1
- Ostatnia weryfikacja zgodności z kodem: main@eb13b2160e4b8d49c128869ad4761eb0875b2dac (2026-09-16)
- Data: 2026-09-16
- Zakres: model domenowy, baza danych, invariants, serwisy aplikacyjne, routing domeny i kolejność migracji

Ten dokument opisuje docelowy model danych newsroomu. Schema N1-001 oraz modele/factories/scopes N1-002 są już zmaterializowane, ale serwisy aplikacyjne kolejnych etapów nadal nie istnieją. Stan wdrożenia należy czytać z sekcji 43 i aktualizować po każdej zmianie kodu.

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
- origin_type: varchar(32), default original

### 5.2. Treść

- title: varchar(255), wymagane
- slug: varchar(255), unique
- lead: text, wymagane przy publikacji
- body_blocks: jsonb, wymagane przy publikacji
- body_schema_version: smallint default 1
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

#### 5.2.1. Kontekst regulacyjny / egzaminacyjny

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
- withdrawn_at: timestamptz nullable
- withdrawal_reason: text nullable, tylko backoffice

Semantyka aktywnych timestampów:

- `scheduled_for` jest aktywne tylko w statusie `scheduled`; po successful publish jest czyszczone,
- `needs_review_at` ustawiamy przy wejściu w `needs_review`; po successful republish wraca do null, historia pozostaje w AuditLog,
- `archived_at` ustawiamy przy archive; po dedykowanym Republish wraca do null,
- `withdrawn_at` + `withdrawal_reason` pozostają aktywnym tombstonem aż do successful publish po restore-to-review,
- `reviewed_at` oznacza ostatnie zatwierdzenie review i może pozostać jako historyczny timestamp,
- `first_published_at` nigdy nie jest czyszczone po pierwszej publikacji.

Zalecenie: w PostgreSQL używać timestamp with time zone dla zdarzeń publikacyjnych. Warstwa aplikacyjna prezentuje daty publiczne w Europe/Warsaw.

### 5.4. Ekspozycja redakcyjna

- is_featured: boolean default false
- is_breaking: boolean default false
- breaking_expires_at: timestamptz nullable
- editorial_priority: smallint default 0

Nie kodujemy layoutu strony głównej ani konkretnej pozycji w rekordzie artykułu. Konkretne sloty/pozycje są wyłącznie w `content_home_placements`. Powyższe pola opisują priorytet treści i fallback, nie strukturę strony.

### 5.5. Media

- hero_image_path: varchar(1024) nullable
- hero_image_alt: varchar(500) nullable
- hero_image_width: unsigned integer nullable
- hero_image_height: unsigned integer nullable
- hero_image_caption: text nullable
- hero_focal_x: numeric(5,4) nullable
- hero_focal_y: numeric(5,4) nullable
- og_image_path: varchar(1024) nullable
- og_image_alt: varchar(500) nullable
- og_image_width: unsigned integer nullable
- og_image_height: unsigned integer nullable
- image_credit: varchar(500) nullable, publiczny credit jeśli potrzebny
- image_license_note: text nullable, tylko backoffice

Aktualny stan implementacji po NEWSROOM-N0-006:

- istnieje `MediaUrlResolver` i wspólna konfiguracja public/upload disk,
- istniejący `AdminMediaUploadService` pozostaje **question-specific** i zapisuje `QuestionMedia`; newsroom go nie reużywa,
- istnieje dedykowany `NewsroomMediaStorage` jako storage/validation foundation,
- newsroom używa osobnego konfigurowalnego `media.newsroom_disk`, `media.newsroom_prefix` i `media.newsroom_max_dimension`,
- source paths mają immutable/unique format `newsroom/articles/source/{ULID}.{ext}`,
- przygotowanie nowego obrazu zawsze generuje nową ścieżkę; replacement nie nadpisuje starego publicznego URL,
- rzeczywisty zapisany obiekt jest sprawdzany po storage: bytes, dekodowalny raster, MIME oraz width/height; deklarowane MIME/bytes mogą być porównane i mismatch jest odrzucany,
- v1 baseline to JPEG/PNG/WebP/AVIF intersectowane ze wspólną image allowlistą; SVG i non-raster payload są odrzucane,
- managed path guard odrzuca traversal, absolute URL/path i ręczne ścieżki spoza dedykowanego namespace,
- publiczny URL jest rozwiązywany przez istniejący `MediaUrlResolver` i musi być stabilnym HTTP(S) URL,
- Traffic Signs/ContentAuthor nadal przechowują własne ścieżki assetów; nie utworzono wspólnego newsroom asset modelu,
- nie ma automatycznego pipeline'u cropów 1:1/4:3/16:9 ani fizycznych lead/standard/compact/OG variants dla newsroomu.

Pozostały target N2/N3:

- hero/OG upload adapter lub endpoint korzysta z `NewsroomMediaStorage` zamiast question-specific `AdminMediaUploadService`,
- zapis do `ContentArticle` przechowuje storage-relative path + zweryfikowane metadata, nie temporary/signed URL,
- crop/variant jest deklarowany w schema/SEO tylko jeśli rzeczywisty plik został wygenerowany i jest publicznie osiągalny,
- focal-point i OG-alt UX pozostają do wdrożenia w CMS/public renderer.

Nie sklejamy ręcznie publicznych URL-i i nie zapisujemy signed/temporary URLs jako hero/OG.

Focal point używa znormalizowanych współrzędnych 0..1. Brak wartości oznacza środek obrazu. Warianty lead/standard/compact/OG są pochodnymi assetu i nie powinny być ręcznie przechowywanymi, niezależnymi kopiami, jeśli media layer może wygenerować je deterministycznie.

`og_image_alt` jest wymagany, gdy dedykowany OG asset przedstawia coś innego niż hero. Może odziedziczyć `hero_image_alt` tylko wtedy, gdy semantycznie jest to ten sam obraz/crop.

Publiczny resolver obrazu używany przez OG/schema nie może zwracać wygasających signed URLs. URL musi być stabilny i publicznie crawlable.

### 5.6. SEO

- seo_title: varchar(255) nullable
- seo_description: varchar(320) nullable
- robots: varchar(128) nullable, ale zapisywane wyłącznie z allowlistowanej policy

V1 nie przechowuje ręcznego `canonical_url`.

Zasady:

- canonical jest zawsze wyliczany z route family + slug,
- article slug nie może być reserved segmentem swojej route family,
- article page jest self-canonical,
- brak robots oznacza policy wynikające ze statusu,
- CMS nie przyjmuje dowolnego free-text robots; v1 używa kontrolowanych wartości/policy, np. default index policy albo `noindex,follow`,
- sprzeczne/nieobsługiwane kombinacje są odrzucane,
- draft/in_review/scheduled preview nie jest indeksowalny,
- published domyślnie index,follow,max-image-preview:large,
- cross-domain/cross-URL canonical override wymaga w przyszłości osobnej decyzji architektonicznej i nie może zostać dodany jako zwykłe pole redaktora.

### 5.7. Freshness

- source_checked_at: timestamptz nullable
- freshness_review_due_at: timestamptz nullable
- last_substantive_update_at: timestamptz nullable
- public_state_changed_at: timestamptz nullable

`freshness_review_due_at <= now()` tworzy **computed overdue state / kolejkę pracy**, ale samo w sobie NIE zmienia `workflow_status`.

`needs_review` jest jawną decyzją workflow, że materiał ma pozostać pod publicznym URL-em, ale ma wypaść z aktywnej dystrybucji do czasu review. Może zostać ustawione przez administratora albo przez przyszłą, jawnie zdefiniowaną regułę bezpieczeństwa (np. potwierdzona utrata wiarygodności primary source), ale nie przez sam upływ terminu.

`updated_at` nie jest automatycznie równoważne istotnej aktualizacji merytorycznej.

`public_state_changed_at` zmienia się tylko przy zmianie mającej wpływ na publiczną dyspozycję/SEO bez zmiany treści, np. archive/withdraw/restore, robots/indexability lub inna jawna zmiana public state. Nie zastępuje `last_substantive_update_at`.

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
- withdrawn

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
- kompletności pól treści/źródeł/autora/kategorii jak dla publikacji,
- **nie wymaga** ustawienia `first_published_at` ani `published_at` przed faktycznym publish,
- scheduled_for > moment przyjęcia komendy schedule,
- v1 pozwala schedule dla never-published article; scheduled republish istniejącego publicznego 200 nie jest wspierany bez staging/revision systemu,
- wyjątek: withdrawn article może zostać przygotowany w review, ale publiczny tombstone pozostaje 410 aż do jawnego publish; v1 nie potrzebuje scheduled restore.

Status `needs_review` wymaga:

- first_published_at != null,
- needs_review_at != null.

Status `archived` wymaga:

- first_published_at != null,
- archived_at != null.

Status withdrawn wymaga:

- first_published_at != null,
- withdrawn_at != null,
- niepustego withdrawal_reason.

Restore-to-review zmienia workflow_status na in_review, ale pozostawia withdrawn_at/withdrawal_reason jako aktywny tombstone do czasu udanego ponownego Publish. Publish po review zapisuje historię w AuditLog, a następnie czyści bieżące withdrawn_at/withdrawal_reason w tej samej transakcji.

Archiwalny artykuł nie przechodzi przez `in_review`, jeśli miałoby to zamienić historyczny 200 w chwilowe 404. V1 ma dedykowany `Republish` z `archived -> published`, który wymaga aktualnego review/checklisty i czyści `archived_at` w tej samej transakcji.

### 6.5. Invariants breaking

`is_breaking = true` wymaga:

- status = published,
- type = news,
- breaking_expires_at != null.

Transition z `published` do `needs_review`, `archived` albo `withdrawn` automatycznie czyści `is_breaking=false` i `breaking_expires_at=null` w tej samej transakcji. Nie zostawiamy rekordu łamiącego własny invariant.

Po `breaking_expires_at` materiał nie jest renderowany w module „pilne”, nawet zanim housekeeping fizycznie wyzeruje flagę.

### 6.6. Route family invariant

`ContentArticleType` mapuje się do stabilnej rodziny publicznego URL:

- `newsroom`: news, explainer, analysis, report,
- `guides`: guide.

Reguły:

- draft bez `first_published_at`: type może zmienić route family,
- po pierwszej publikacji: zwykła edycja type nie może zmienić route family,
- zmiana news -> analysis/report/explainer jest dozwolona, bo canonical path pozostaje w `/aktualnosci/{slug}`,
- guide <-> dowolny typ newsroom jest zablokowane po pierwszej publikacji,
- manualny SQL omijający invariant nie jest wspieranym workflow.

Przyszła uprzywilejowana migracja route family, jeśli kiedykolwiek zostanie dodana, musi utworzyć 301 starego pełnego path do nowego canonical i zaktualizować wszystkie istniejące redirecty tak, aby nie powstał chain.

---

## 7. Indeksy content_articles

Wymagane:

- unique index na slug
- canonical path wyliczony z route family + slug nie może kolidować z żadnym `content_article_redirects.from_path` należącym do innego artykułu
- slug nie może należeć do reserved segments określonych przez route contract
- index(workflow_status, first_published_at desc)
- index(category_id, workflow_status, first_published_at desc)
- index(type, workflow_status, first_published_at desc)
- index(is_featured, workflow_status, editorial_priority desc)
- index(is_breaking, breaking_expires_at)
- index(freshness_review_due_at)
- index(scheduled_for, workflow_status)

Nie dodawać indeksów „na zapas” dla pól, których nie używamy w zapytaniach.

### 7.1. Indeksy relacji pod reverse lookup

Ponieważ newsroom renderuje także reverse links z istniejących encji, unique index zaczynający się od `article_id` nie wystarcza.

Wymagane/przewidywane:

- `content_article_topic(topic_id, article_id)`,
- `content_article_tag(tag_id, article_id)`,
- `content_article_question(question_id, sort_order, article_id)`,
- `content_article_legal_unit(legal_unit_id, sort_order, article_id)`,
- `content_article_traffic_sign(traffic_sign_id, sort_order, article_id)`,
- `content_article_sources(article_id, sort_order)`,
- `content_home_placements(surface_key, slot_key, context_key, position, starts_at, ends_at)`,
- `content_topics(status, published_at)`.

Finalny PR migracyjny ma potwierdzić query plan/use case i nie dodawać dubli indeksów, które PostgreSQL już pokrywa przez unique prefix.

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

Kategorie v1 są zamrożone przez `NewsroomTaxonomyContract::categories()`:

| position | slug | name |
| ---: | --- | --- |
| 10 | `prawo-jazdy` | Prawo jazdy |
| 20 | `egzaminy` | Egzaminy |
| 30 | `przepisy` | Przepisy |
| 40 | `word` | WORD |
| 50 | `kierowcy` | Kierowcy |
| 60 | `osk` | OSK |

Kontrakt N0-003 jest wykonywalny i przetestowany, ale nie zapisuje jeszcze rekordów do bazy. `description`, `seo_title` i `seo_description` pozostają w v1 seed contract jawnie `null` do czasu zatwierdzenia treści redakcyjnej.

### 8.1. Invariants

- w v1 slug kategorii jest immutable po utworzeniu/seedzie; nie mamy category redirect history,
- kategoria nie może być usunięta, jeśli ma artykuły,
- `is_active=false` jest dozwolone tylko, gdy kategoria nie ma publicznie widocznych/aktywnie dystrybuowanych artykułów; najpierw należy je przepiąć lub wycofać,
- publikacja/scheduling artykułu wymaga aktywnej primary category,
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
- created_at
- unique(article_id, topic_id)

V1 nie ma ręcznego rankingu całego corpus topicu. `featured_article_id` daje pojedynczy lead, a pozostała lista jest chronologiczna po `ContentArticle.first_published_at DESC`.

Reguły:

- publiczny topic wymaga własnego, niepustego opisu redakcyjnego i statusu published,
- v1 baseline publikacji topicu: co najmniej 3 `activelyDistributed()` i indeksowalne artykuły przypięte do topicu; to reguła jakości produktu, nie gwarancja SEO,
- samo przypięcie taga nie tworzy topicu,
- `featured_article_id`, jeśli ustawione, musi wskazywać `activelyDistributed()` i indeksowalny artykuł należący do tego samego topicu,
- slug topicu może zmieniać się w draft; po pierwszej publikacji jest immutable w v1, ponieważ nie mamy topic redirect history,
- baseline >=3 jest gate'em przy pierwszym publish/ponownym publish topicu; chwilowy późniejszy spadek corpus nie zmienia automatycznie HTTP/indexability,
- published topic poniżej baseline dostaje health warning w CMS/audycie i nie powinien być promowany jako featured topic do czasu naprawy,
- jeśli corpus trwale utraci wartość, administrator ustawia status archived; archived topic jest usuwany z nawigacji/sitemap i jego wcześniej publiczny URL zwraca 410, chyba że istnieje realny następca.

---

## 11. Tabela content_article_sources

Każdy materiał informacyjny musi wspierać wiele źródeł.

Pola:

- id
- article_id FK -> content_articles cascade delete
- source_type varchar(32)
- publisher varchar(255) nullable
- title varchar(500)
- url varchar(2048) nullable
- published_at timestamptz nullable
- accessed_at timestamptz nullable
- is_primary boolean default false
- is_official boolean default false
- is_publicly_cited boolean default true
- note text nullable
- sort_order smallint default 0
- created_at
- updated_at

### 11.1. source_type v1

- official
- legislation
- institution
- primary_data
- interview
- report
- media
- other

### 11.2. Reguły

- news o zmianie prawa powinien mieć co najmniej jedno publicznie cytowalne źródło official lub legislation z URL, jeśli takie istnieje,
- media konkurencyjne nie są domyślnym źródłem pierwotnym,
- `url` może być null dla interview/direct evidence/źródła bez publicznego linku,
- `is_publicly_cited=true` oznacza, że publiczny renderer może pokazać citation; jeśli URL istnieje, renderuje bezpieczny link, a jeśli nie — tekstową citation bez linku,
- `is_publicly_cited=false` zachowuje source jako wewnętrzny dowód i nigdy nie renderuje jego title/publisher/url,
- `note` jest zawsze wewnętrzne i nigdy nie jest częścią publicznej citation,
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

Newsroom nie może zmieniać stanu pytania, membershipów `question_seo_topics`, `question_relations`, rankingu V1/V2 ani źródeł dowodowych istniejącego question graphu. Pivot `content_article_question` opisuje wyłącznie relację artykuł ↔ istniejąca encja pytania.

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
- każdy wcześniej publiczny `from_path` jest trwałą rezerwacją ścieżki przed użyciem przez **inny** artykuł,
- create/slug change sprawdza kolizję nie tylko z bieżącymi slugami, ale też z historycznymi `from_path`,
- ponowne użycie własnego historycznego path przez **ten sam** artykuł jest dozwolone tylko przez `ContentArticleSlugService`: usuwa/aktualizuje kolidujący redirect dla odzyskanego path i przepisuje wszystkie pozostałe historyczne redirecty bezpośrednio do nowego canonical,
- zwykły Filament unique(slug) nie jest wystarczającym zabezpieczeniem historycznej ścieżki,
- ponieważ invariant obejmuje `content_articles` i `content_article_redirects`, create/slug-change/reclaim serializują mutację pełnego path w PostgreSQL (np. transaction-level advisory lock na znormalizowanym full path); check-then-insert bez locka ma race TOCTOU,
- przy operacji dotykającej starego i nowego path locki bierzemy w deterministycznej kolejności, aby nie tworzyć deadlocków,
- v1 nie pozwala zwykłą edycją zmienić route family opublikowanego artykułu,
- jeśli przyszła kontrolowana migracja route family zostanie kiedyś wdrożona, zapisuje poprzedni pełny path w tej samej tabeli,
- nie tworzymy redirect chain; nowy wpis powinien wskazywać canonical destination,
- to_path zawsze lokalny canonical path dla własnego contentu,
- usunięcie artykułu nie oznacza automatycznego redirectu do huba,
- 410/404 jest lepsze niż niepowiązany redirect.

### 15.2. Aktualny stan implementacji

NEWSROOM-N1-003 jest wdrożone.

Kod zawiera:

- `ContentArticleSlugService` dla create/initial slug allocation, explicit slug, slug changes i type changes,
- `ContentArticleRedirect` oraz relację `ContentArticle::redirects()`,
- `ContentArticlePathResolver` dla service-level canonical lookup per route family oraz historycznych redirect records,
- `PostgresTransactionAdvisoryLock` z transaction-only PostgreSQL advisory locks i deterministycznym sortowaniem kluczy,
- generated slug suffix allocation, reserved newsroom slug handling i global current-slug uniqueness zgodną z istniejącym unique indexem,
- published slug history przepisywaną one-hop do bieżącego canonical,
- service-only same-article historical path reclaim,
- blockadę przejęcia historycznego `from_path` innego artykułu,
- cross-family type-change guard po `first_published_at`,
- kompaktowe AuditLog events dla create/slug/type mutations.

PostgreSQL 16 gate wykonuje realny contention test na drugim połączeniu i potwierdza serialization tego samego full path. Finalny wynik po N1-003: 6 testów / 86 asercji.

Publiczne article controllers nadal nie istnieją, więc HTTP 301 dla old path i 200 dla new canonical nie są jeszcze zmaterializowane w routingu; N3 ma konsumować `ContentArticlePathResolver` zamiast duplikować lookup logic.

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
- important_now

`context_key` służy np. do rozróżnienia kategorii przy `category_lead`.

Nie pozwalamy administratorowi tworzyć dowolnych nowych nazw modułów w bazie.

### 16.2. Czas ekspozycji

- null starts_at oznacza aktywność od razu po spełnieniu innych warunków,
- null ends_at oznacza brak automatycznego końca,
- bieżący publiczny render placementu może wskazać wyłącznie `activelyDistributed()` article,
- `needs_review`, `archived`, active-withdrawal tombstone, draft i future scheduled są niekwalifikowane do bieżącego placementu,
- scheduled article może być widoczny w future preview tylko wtedy, gdy dla wybranego czasu resolver przewiduje stan `published`; preview nie zmienia danych.

### 16.3. Kolizje placements

Application service nie pozwala na nierozstrzygnięte nakładanie się dwóch aktywnych rekordów dla tego samego:

- surface_key,
- slot_key,
- context_key,
- position.

Nie próbujemy modelować przedziałów czasowych przez skomplikowany DB exclusion constraint w pierwszej wersji.

Application service musi jednak serializować zapis dla danego `surface_key + slot_key + context_key + position`: transakcja + row lock na istniejących kandydackich rekordach albo PostgreSQL advisory lock, następnie walidacja overlap wewnątrz locka. Dwa równoległe requesty nie mogą oba przejść walidacji i utworzyć kolizji.

Invariant ma test domenowy/transakcyjny oraz test konkurencji na PostgreSQL.

### 16.4. Fallback i deduplikacja

NewsroomHomeCompositionService:

1. rozwiązuje aktywne ręczne placementy,
2. waliduje, że wskazane artykuły są publiczne dla czasu renderowania,
3. uzupełnia puste sloty deterministycznym fallbackiem,
4. prowadzi zbiór wykorzystanych article IDs,
5. nie powtarza artykułu w kolejnych card modules,
6. jeśli brakuje unikalnego kandydata, zwraca krótszą sekcję zamiast duplikatu.

Breaking strip jest niezależnym alertem i może wskazywać ten sam artykuł co lead, ponieważ nie jest kolejną kartą contentową.

### 16.5. Aktualny stan implementacji N1-006

NEWSROOM-N1-006 jest wdrożone na `main`:

- `NewsroomHomeCompositionService` materializuje kolejność lead -> secondary -> latest -> category blocks -> guides -> important now i prowadzi globalny zbiór użytych article IDs,
- manual placements są rozwiązywane przed fallbackiem, ale placement poza oknem czasowym nie dyskwalifikuje samego artykułu z legalnego fallbacku,
- bieżący render używa `activelyDistributed()`; future preview może uwzględnić initial `scheduled` dopiero od `scheduled_for`, po `ContentArticlePublishingService::assertScheduledPreviewReady()` i bez mutowania workflow,
- fallback jest deterministyczny i nie pobiera całego corpusu do filtrowania w PHP,
- category lead respektuje category context, guides lead wymaga typu `guide`, a krótkie moduły są dozwolone przy braku unikalnych kandydatów,
- breaking strip jest rozwiązywany niezależnie i może powtórzyć lead,
- `NewsroomHomePlacementService` wykonuje create/update w transakcji, waliduje kontrolowane surface/slot/context/date ranges i sprawdza overlap po acquisition PostgreSQL advisory locka oraz row locka,
- half-open interval contract pozwala na sąsiadujące okna,
- PostgreSQL concurrency test potwierdza, że dwa równoległe zapisy tego samego pustego tuple nie mogą równocześnie przejść walidacji.

Nie wdrożono jeszcze Filament UI placements ani publicznego kontrolera konsumującego composer; odpowiednio pozostają N2/N3.

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

`ContentArticleOriginType` v1:

- original
- compiled
- official_source
- data_analysis
- licensed_agency

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
- body_schema_version: integer
- effective_from: immutable_date
- published_at: immutable_datetime
- first_published_at: immutable_datetime
- scheduled_for: immutable_datetime
- reviewed_at: immutable_datetime
- needs_review_at: immutable_datetime
- archived_at: immutable_datetime
- withdrawn_at: immutable_datetime
- breaking_expires_at: immutable_datetime
- source_checked_at: immutable_datetime
- freshness_review_due_at: immutable_datetime
- last_substantive_update_at: immutable_datetime
- public_state_changed_at: immutable_datetime

### 19.2. Scopes / public visibility

Nazwy scope'ów nie mogą utożsamiać `workflow_status=published` z całą widocznością publiczną, bo `needs_review` pozostaje publiczne, a `archived` zachowuje historyczny canonical URL.

Wymagane rozróżnienie:

- `publiclyVisible()` — artykuł po pierwszej publikacji, którego workflow dopuszcza publiczny detail URL: `published`, `needs_review` oraz `archived`,
- `activelyDistributed()` — wyłącznie `workflow_status=published` z poprawnym `published_at <= now()`; używane przez home/category/topic/latest/feed/news sitemap,
- `indexable()` — publiclyVisible + aktualna robots/SEO policy nie jest noindex,
- `scheduled()`,
- `forCategory()`,
- `featured()`,
- `activeBreaking()`,
- `needsFreshnessReview()`.

Zwykłe read modele list/hubów nie mogą używać `publiclyVisible()` zamiast `activelyDistributed()`. `needs_review` pozostaje osiągalne pod canonical URL, ale świadomie znika z aktywnej promocji do czasu ponownego review. Jeśli nadal jest `indexable()`, musi zachować crawlable inbound; fallback v1 to oznaczona sekcja „w trakcie weryfikacji” na publicznym profilu autora.

Artykuł `archived`, który nigdy nie był publiczny (`first_published_at=null`), nie uzyskuje publicznego detail URL tylko dlatego, że ma status archived.

`withdrawn` nigdy nie jest `publiclyVisible()`: rekord i historia pozostają w backoffice, ale jego kanoniczny dawny path jest rozpoznawany przez resolver jako celowe `410 Gone`, chyba że istnieje jawny redirect do rzeczywistego następcy.

---

## 20. Serwisy domenowe / application services

Rekomendowane klasy w app/Support/Newsroom lub analogicznej, jasno wydzielonej przestrzeni nazw.

### 20.1. ContentArticlePublishingService

Odpowiada za:

- initial publish,
- atomic public update dla już publicznego artykułu,
- schedule wyłącznie initial publish v1,
- unpublish do in_review/draft zgodnie z policy,
- archive,
- withdraw/restore-to-review,
- timestamps,
- walidację invariants,
- zapis wymaganych zdarzeń AuditLog,
- dispatch domenowych eventów.

Transakcja stanu publicznego obejmuje co najmniej rekord artykułu, krytyczne timestampy/invariants i audit opisujący tę zmianę. Eventy uruchamiające zewnętrzne side effecty (cache invalidation, sitemap dirty signal, IndexNow, notification) są dispatchowane dopiero po udanym commit. Rollback transakcji nie może zostawić „ghost publish” w cache/sitemap/IndexNow.

#### 20.1.0. Aktualny stan implementacji N1-004

Na `main` istnieje `ContentArticlePublishingService` z transakcyjnymi przejściami:

- draft -> in_review i in_review -> draft,
- mark reviewed dla in_review / needs_review / archived,
- initial-only schedule,
- publish dla in_review / needs_review / due scheduled,
- published -> needs_review,
- published -> archived,
- archived -> published przez dedykowany republish po fresh review,
- published / needs_review / archived -> withdrawn z reason,
- withdrawn -> in_review z aktywnym tombstone do skutecznego reviewed publish.

Serwis blokuje brak wymaganych pól/body/category/author/source, nieaktywną kategorię, niepublicznego autora, niepoprawne hero/OG metadata, invalid key_points i breaking state. Zapis state/timestamps oraz AuditLog odbywa się w jednej transakcji. `ContentArticleWorkflowTransitioned` implementuje `ShouldDispatchAfterCommit`; test rollbacku potwierdza, że event nie jest dostarczany przed outer commit i znika przy rollbacku.

Granice obecnej implementacji:

- `applyPublicUpdate` opisany niżej nie jest jeszcze wdrożony; pozostaje N2 orchestration/stale-write scope,
- scheduler command i batch due processing zostały wdrożone downstream w N1-005 i reużywają tego service boundary,
- publiczny HTTP 410/301/200 pozostaje N3; service ustanawia withdrawal tombstone, ale nie renderuje odpowiedzi HTTP,
- cache/sitemap/IndexNow listeners nie są jeszcze podłączone; istnieje jedynie bezpieczny after-commit event hook.

#### 20.1.1. Edycja już opublikowanego artykułu bez revisions

V1 nie ma staged revision/snapshot systemu. Dlatego:

- zwykły CRUD Save może edytować publiczne pola swobodnie tylko przed pierwszą publikacją albo gdy article jest aktywnie withdrawn/tombstoned,
- dla `publiclyVisible()` article publiczne pola (`title`, `slug`, `lead`, `body_blocks`, public source/citation, author/category, hero/SEO/public context`) nie mogą być zapisywane przez zwykły low-level Filament save,
- dedykowany `applyPublicUpdate(payload, actor)` waliduje cały nowy publiczny stan i zapisuje go atomowo,
- meaningful public change ustawia `last_substantive_update_at`; zmiana tylko public-state/robots używa właściwej semantyki `public_state_changed_at`,
- audit zapisuje typy/IDs/summary zmiany, ale nie pełny body,
- wewnętrzne pola niepubliczne mogą mieć osobny, bezpieczny save path bez fałszowania SEO freshness.

Konsekwencja: v1 **nie zapewnia review-before-live dla zmian już opublikowanego 200**. Jeśli taki workflow stanie się wymagany, należy świadomie dodać staging/revision model; nie wolno udawać go statusem na jednym rekordzie.

### 20.2. ContentArticleSlugService

Odpowiada za:

- normalizację sluga,
- unique slug,
- zmianę sluga,
- tworzenie redirect history.

### 20.3. ContentArticleCatalogService

Odpowiada za read-side:

- latest,
- hub sections,
- category listings,
- related content,
- product bridge data.

Nie mieszać write workflow z katalogiem publicznym.

### 20.4. ContentArticleSeoService

Odpowiada za:

- title,
- description,
- canonical,
- robots,
- published/modified metadata,
- OG/Twitter meta model.

### 20.5. ContentArticleSchemaService

Odpowiada za:

- NewsArticle/Article JSON-LD,
- author Person,
- publisher Organization,
- BreadcrumbList.

### 20.6. ContentArticleFreshnessService

Odpowiada za:

- wyliczanie due dates,
- computed states fresh/due-soon/overdue,
- listę materiałów przeterminowanych,
- politykę freshness per type/category,
- jawne rekomendowanie/wywołanie transition do `needs_review` tylko gdy istnieje osobny trigger bezpieczeństwa.

**Nie** zmienia automatycznie każdego overdue rekordu na `needs_review` tylko dlatego, że minął termin.

---

## 21. Events

Rekomendowane domain/application events:

- ContentArticlePublished
- ContentArticleSubstantivelyUpdated
- ContentArticleArchived
- ContentArticleWithdrawn
- ContentArticleSlugChanged
- ContentArticleBreakingChanged

Listenery po commit mogą:

- czyścić cache,
- zgłaszać URL do istniejącego IndexNow pipeline, jeśli policy to dopuszcza,
- odświeżać feed cache,
- oznaczać statyczne sitemap jako wymagające refreshu.

Nie zakładamy, że `ShouldQueue` oznacza asynchroniczność: aktualny repo contract ma `QUEUE_CONNECTION=sync`. Pełny refresh sitemap nie może wykonywać się w request publikacji.

`ContentArticleSubstantivelyUpdated` jest emitowany wyłącznie, gdy zmieniła się publiczna treść/meaningful metadata i ustawiono `last_substantive_update_at`. Techniczny zapis, audit note, cache touch lub pole niewidoczne publicznie nie emituje tego eventu tylko po to, by odświeżyć SEO freshness.

Event nie powinien wykonywać ciężkiej logiki synchronicznie w request bez potrzeby.

---

## 22. Scheduling

Scheduled publishing wymaga deterministycznego procesu.

Rekomendacja:

- scheduler uruchamia komendę np. newsroom:publish-due,
- query pobiera tylko workflow_status=scheduled i scheduled_for <= now(),
- przed faktycznym publish serwis **ponownie** waliduje pełną checklistę/invariants w aktualnym stanie: aktywna category, publiczny author, reviewer/source policy, body/media/security,
- jeśli record przestał być eligible między schedule a due time, pozostaje nieopublikowany, failure jest audytowalny/logowany i nie blokuje kolejnych rekordów,
- publikacja przechodzi przez ContentArticlePublishingService,
- komenda jest idempotentna.

### 22.1. Race safety

Dwa równoległe uruchomienia nie mogą opublikować artykułu dwa razy ani nadpisać first_published_at.

Rekomendacja:

- transakcja DB,
- row lock lub atomic conditional update,
- test współbieżności na poziomie możliwym w obecnym stacku.

### 22.2. Aktualny stan implementacji

NEWSROOM-N1-005 jest wdrożone na `main`:

- `newsroom:publish-due` wybiera wyłącznie rekordy `scheduled` z `scheduled_for <= now()`, w deterministycznej kolejności `scheduled_for, id`,
- każdy rekord jest przed publikacją ponownie pobierany, a właściwa mutacja przechodzi przez `ContentArticlePublishingService::publish(..., trigger: 'scheduler')`,
- publishing service wykonuje row lock oraz ponownie waliduje publication-ready i fresh-review invariants w aktualnym stanie zależności,
- scheduled record z istniejącym `first_published_at` jest odrzucany; v1 nie wykonuje scheduled republish,
- failure jednego rekordu pozostawia go w stanie `scheduled`, zapisuje mały `content_article.scheduled_publish_failed` AuditLog i warning log, po czym batch przechodzi dalej,
- identyczny failure AuditLog jest deduplikowany dla tego samego `scheduled_for`, klasy i komunikatu błędu, aby minutowy scheduler nie generował nieograniczonego audytu,
- jeśli po pobraniu ID inny proces zmieni stan rekordu, refetch traktuje go jako idempotentny skip; row lock w publishing service pozostaje finalnym race boundary,
- komenda zwraca non-zero po przetworzeniu batcha, jeśli pozostały faktyczne due failures,
- `routes/console.php` rejestruje komendę co minutę tylko dla production z `withoutOverlapping()`; nie deklarujemy `onOneServer()` ani distributed single-execution guarantee,
- reviewer identity nadal zależy od policy; technicznie scheduler wymaga fresh `reviewed_at` przez istniejący publishing contract i nie dodaje osobnego mandatory reviewer-id invariant.

---

## 23. Time semantics

Publiczne daty:

- first_published_at: pierwsza publikacja; nigdy nie resetować przy zwykłej edycji,
- published_at: timestamp ostatniego wejścia w stan published; nie jest źródłem daty pierwotnej ani automatycznego „najnowsze”,
- last_substantive_update_at: istotna zmiana treści/claimu/publicznej merytorycznej metadata,
- public_state_changed_at: istotna zmiana publicznego stanu bez twierdzenia, że treść została merytorycznie zaktualizowana,
- updated_at: techniczny timestamp rekordu.

Structured data:

- `datePublished = first_published_at`,
- `dateModified = last_substantive_update_at ?? first_published_at`.

Publiczny label „Aktualizacja” pojawia się tylko, gdy `last_substantive_update_at` rzeczywiście istnieje i jest późniejszy od pierwszej publikacji.

Structured data `dateModified` i feed `updated` używają merytorycznej semantyki `last_substantive_update_at ?? first_published_at`.

Article sitemap `lastmod` może dodatkowo uwzględnić `public_state_changed_at`, ponieważ archive/restore/robots mogą realnie zmienić odpowiedź publiczną bez zmiany treści. Nigdy nie używa technicznego `updated_at`.

Chronologia publicznych list „najnowsze”, kategorii i topiców używa `first_published_at DESC` (z deterministycznym tie-breakerem, np. id DESC). Ponowne wejście w published nie robi ze starego materiału nowego. Jeśli chcemy ponownie promować istotnie zaktualizowany materiał, robimy to przez placement/featured, nie przez fałszowanie daty pierwszej publikacji.

---

## 24. Author, reviewer i actor

Używamy istniejącego `ContentAuthor` jako publicznej/redakcyjnej tożsamości autora i reviewera. Nie tworzymy `NewsroomAuthor`.

Osobnym bytem jest `User`:

- tylko istniejący administrator może wejść do Filament v1,
- `User` jest aktorem create/update/review/publish/archive i trafia do `AuditLog.actor_user_id`,
- `ContentAuthor.author_id/reviewer_id` nie nadaje dostępu do panelu i nie jest kontem logowania.

Wymagania do publikacji:

- author jest `isPubliclyVisible()`,
- author ma slug i publiczny profil pod istniejącym route,
- po wdrożeniu relacji newsroomu nie wolno odpublikować ContentAuthor, jeśli istnieje zależny indexable/publiclyVisible artykuł, dopóki artykuły nie zostaną przepisane do innego publicznego autora albo wycofane/noindex zgodnie z policy,
- reviewer opcjonalny zależnie od policy; jeśli jest pokazywany publicznie, również musi być publiczny.

Dla treści prawnie wrażliwych policy może wymagać `reviewer_id + reviewed_at`. V1 nie udaje jednak kryptograficznej separacji obowiązków: bez osobnego RBAC/linku ContentAuthor↔User system nie może dowieść, że reviewer był innym zalogowanym człowiekiem. AuditLog pokazuje faktycznego administratora, który wykonał akcję.

---

## 25. Walidacja i sanitization body_blocks

NEWSROOM-N0-004 jest zamknięte przez wykonywalny `App\Support\NewsroomBodyContract`.

Kontrakt domenowy:

- `body_blocks` pozostaje jedynym kanonicznym źródłem body; nie istnieje równoległe edytowalne `body_html`,
- `body_schema_version=1` jest jedyną aktualnie obsługiwaną wersją; nieznana wersja failuje zamknięcie,
- zapis domenowy jest uporządkowaną listą `{key?, type, data}`; associative UUID-keyed state Filament Buildera jest wyłącznie formatem adaptera UI,
- każdy aktywny `type` ma allowlistowany schema payloadu,
- `rich_text` jest structured TipTap JSON, nie HTML-em; backend waliduje allowlistę nodes/marks niezależnie od browsera,
- dozwolone rich-text nodes: `doc`, `paragraph`, `heading` level 2/3, `bulletList`, `orderedList`, `listItem`, `text`, `hardBreak`,
- dozwolone marks: `bold`, `italic`, `link`; raw HTML/style/custom nodes i marks są odrzucane,
- link przyjmuje root-relative/fragment/http/https; `javascript:` oraz nieobsługiwane targety są odrzucane, a `target=_blank` normalizuje się do `rel="noopener noreferrer"`,
- aktywne bloki v1: `rich_text`, `image`, `quote`, `table`, `context`, `related_article`, `legal_reference`, `question_group`, `traffic_sign_group`, `product_cta`,
- `embed` jest znany kontraktowi, ale feature-disabled/fail-closed, dopóki nie istnieje provider allowlista, sandbox/referrer policy oraz zgodny z produkcją CSP/`frame-src` contract,
- image block przyjmuje wyłącznie storage-relative path i pola strukturalne; N0-006 zapewnia `NewsroomMediaStorage` dla managed paths/validation, a faktyczny upload UI/persistence i crop generation pozostają w N2/N3,
- domain blocks utrzymują IDs, nie zduplikowane fragmenty HTML lub kart,
- block key jest opcjonalny, ale jeśli występuje, ma stabilny format, musi być unikalny i nie może kolidować z Builder item key,
- unknown block type oraz unknown payload field failują zamknięcie zamiast wykonywać nieznaną treść.

Aktualny repo nadal nie ma newsroom-ready CSP middleware ani osobnego arbitrary-HTML sanitizer package. N0-004 nie dodaje ich, ponieważ wybrany structured JSON contract nie przyjmuje arbitralnego HTML jako formatu body. Publiczny N3 renderer nadal musi renderować wyłącznie kontrolowane komponenty.

Strategia format evolution:

- writer/editor może zapisywać tylko wersje obsługiwane przez reader/renderer,
- nowy block type może zostać udostępniony w CMS dopiero po wdrożeniu jego readera/renderera,
- v2 wymaga najpierw backward-compatible readera albo jawnej migracji danych z testem,
- rollback nie może zostać wykonany do kodu, który nie potrafi bezpiecznie odczytać zapisanych wersji bez osobnego data planu.

---

## 26. Search w adminie

N1:

- PostgreSQL ILIKE / Filament search po title i slug wystarcza.

Nie wdrażamy Elasticsearch/Meilisearch tylko dla CMS.

Publiczna wyszukiwarka artykułów nie jest wymogiem newsroom v1.

---

## 27. Cache model

Cache keys powinny być oparte o publiczne read models, np.:

- newsroom:home:v1
- newsroom:category:{slug}:page:{n}
- newsroom:article:{id}:public
- newsroom:feed:latest

Cache invalidation przez eventy publikacyjne.

Nie cache’ujemy preview jako publicznej strony.

---

## 28. Route binding

ContentArticle:

- getRouteKeyName() => slug

Publiczny controller nie powinien polegać tylko na implicit binding, jeśli musimy rozróżnić opublikowany vs nieopublikowany rekord.

Preferowany publiczny lookup nie jest surowym route bindingiem, lecz resolverem dyspozycji:

`ContentArticleCatalogService::resolvePublicPath($slug, $routeFamily)`

Resolver rozstrzyga jawnie:

- visible article -> 200,
- redirect history -> 301 do canonical,
- aktywny withdrawal tombstone (`withdrawn_at != null`), także po restore-to-review przed ponownym Publish -> 410,
- draft/scheduled/never-public/unknown -> 404.

Listy/home/feed używają osobnych `activelyDistributed()` queries. Controller nie może utożsamić publicznego detail URL z aktywną dystrybucją.

Preview używa oddzielnej ścieżki i admin-only policy.

---

## 29. Publiczne route contracts

V1:

- GET /aktualnosci
- GET /aktualnosci/{slug}
- GET /aktualnosci/kategoria/{categorySlug}
- GET /aktualnosci/temat/{topicSlug}
- GET /poradniki
- GET /poradniki/{slug}
- GET /aktualnosci/feed.xml
- newsroom/news sitemap jako statyczne artefakty rozszerzające istniejący `SeoSitemapGenerator`; istniejące controller routes mogą pozostać kompatybilnością, ale nie są produkcyjnym source of truth

### 29.1. Konflikt slug vs category / reserved segments

Nie wolno pozostawić niejednoznaczności:

/aktualnosci/{slug}

nie może równocześnie oznaczać kategorii i artykułu bez jawnej reguły.

Przyjęty wariant:

- /aktualnosci
- /aktualnosci/kategoria/{categorySlug}
- /aktualnosci/{articleSlug}

Jest jednoznaczny dla routingu i przyszłych zmian.

V1 route params używają slug regex `[a-z0-9-]+`. Dla article sluga w rodzinie newsroom rezerwujemy co najmniej segmenty `kategoria` i `temat`, aby nie tworzyć mylących URL-i będących jednocześnie namespace hubów. Route `/aktualnosci/feed.xml` jest deklarowany przed catch-all article route i nie pasuje do slug regex z powodu kropki.

Zmiana na krótsze category URLs wymaga zmiany decyzji architektonicznej, reserved-slug policy i testów konfliktów.

### 29.2. Type -> route family

Resolver canonical path:

- guide -> `/poradniki/{slug}`,
- news/explainer/analysis/report -> `/aktualnosci/{slug}`.

Publiczny lookup musi dodatkowo sprawdzić, czy rekord należy do route family obsługiwanej przez dany controller. Ten sam rekord nie może odpowiadać 200 pod oboma adresami.

### 29.3. Aktualny stan implementacji route contract

NEWSROOM-N0-002 jest wdrożone na `main`.

Kod zawiera:

- `NewsroomRouteContract` jako wykonywalny kontrakt dla regexu slugów, reserved newsroom segments, type -> route family, canonical path i cross-family type transition guard po `first_published_at`,
- zachowane hub route names `public.news` i `public.guides`,
- `public.news.feed`, `public.news.categories.show`, `public.news.topics.show`, `public.news.show` i `public.guides.show`,
- routing namespace/catch-all w kolejności zgodnej z tym dokumentem,
- `/aktualnosci` i `/poradniki` jako nadal działające placeholdery 200 z `X-Robots-Tag: noindex, follow`,
- przyszłe feed/category/topic/detail routes jako jawne 404 do czasu wdrożenia odpowiadających controllerów.

`ContentArticle` istnieje już jako model N1-002, ale publiczne article/category/topic/feed controllery nadal nie istnieją. Z tego powodu record-level family lookup guard pozostaje obowiązkiem downstream N3 i nie jest opisany jako wdrożony.

---

## 30. Migracje — kolejność

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

### 30.1. FK / on-delete safety

Nowe FK nie mogą pozwolić newsroomowi skasować istniejących bytów produktu.

Docelowe zasady:

- `content_articles.category_id -> content_categories`: restrict/no cascade,
- `author_id/reviewer_id -> content_authors`: restrict/no cascade, aby nie utracić attribution/review identity,
- `content_topics.featured_article_id -> content_articles`: nullable + nullOnDelete,
- child records należące wyłącznie do artykułu (`sources`, redirects, home placements, article-* pivots) mogą cascade-delete przy dopuszczalnym hard delete artykułu,
- pivot FK do istniejącego question/legal/sign może cascade-delete wyłącznie **wiersz pivotu**, gdy target zostaje usunięty; nigdy nie ma ścieżki kasującej question/legal/sign z powodu usunięcia artykułu,
- tag/topic membership pivots mogą cascade-delete własny pivot przy usunięciu jednego końca,
- `content_home_placements.created_by_user_id/updated_by_user_id`: nullOnDelete.

Migration tests muszą sprawdzać co najmniej krytyczne restrict/cascade directions na PostgreSQL.

---

## 31. Seed danych systemowych

NEWSROOM-N0-003 dostarcza `NewsroomTaxonomyContract` jako jedyne wykonywalne źródło wartości kategorii v1. Po N1-001 tabela `content_categories` już istnieje, ale kontrakt nadal ma być źródłem danych dla przyszłego seedera.

Przyszły dedykowany seeder N1:

- konsumuje `NewsroomTaxonomyContract::categories()` zamiast utrzymywać własną kopię listy,
- używa `updateOrCreate` po slug,
- jest idempotentny i nie tworzy duplikatów,
- utrzymuje kontrakt pozycji/nazw dla danych systemowych,
- nie nadpisuje ręcznie zmienionej treści SEO bez jawnej decyzji,
- nie seeduje sztucznych produkcyjnych artykułów.

Na obecnym etapie tabela `content_categories` **już istnieje** po N1-001, a model `ContentCategory` istnieje po N1-002. Dedykowany idempotentny DB seeder kategorii **nie istnieje jeszcze** i pozostaje osobnym małym krokiem domenowym; ma konsumować `NewsroomTaxonomyContract`.

Test fixtures pozostają w factories/seed smoke data.

---

## 32. Factories

Wdrożone w N1-002:

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

Factories umożliwiają czytelne testy workflow. `ContentArticleFactory` ma jawne stany `draft`, `inReview`, `scheduled`, `published`, `breaking`, `needsReview`, `archived` oraz dodatkowy `withdrawn`; kanoniczny payload body buduje przez `NewsroomBodyContract`.

---

## 33. Archive / deletion policy

`archived` w v1 jest stanem dystrybucji, nie stanem HTTP „gone”.

Artykuł wcześniej opublikowany po archive:

- znika z home/latest/category/topic active listings, feed, reverse-link promotion i news sitemap,
- canonical detail URL nadal zwraca 200,
- pozostaje w standardowej article sitemap tylko jeśli nadal jest indexable,
- jeśli pozostaje indexable, musi zachować co najmniej jeden crawlable inbound link; gwarantowanym fallbackiem v1 jest publiczny profil autora, który może listować archived+indexable publikacje z jawnym oznaczeniem „archiwalne”,
- może mieć `noindex` przez kontrolowaną robots policy, jeśli istnieje merytoryczny powód; wtedy nie wymagamy obecności w author archive/public sitemap,
- nie dostaje automatycznego 301/404/410.

301 wymaga rzeczywistego następcy.

### 33.1. Withdrawn / takedown

V1 zawiera jawny status `withdrawn` dla materiału, który musi przestać być publicznie dostępny, ale którego nie chcemy kasować z historii administracyjnej.

- transition wymaga confirmation + `withdrawal_reason`,
- ustawia `withdrawn_at`,
- usuwa URL z home/list/topic/feed/news/article sitemap oraz reverse-link modules,
- dawny canonical path zwraca `410 Gone`, jeśli nie ma realnego następcy,
- jeśli istnieje rzeczywisty następca, jawny redirect może zwracać 301 zamiast 410,
- treść/body/source pozostają dostępne wyłącznie w adminie dla audytu/ewentualnego review,
- restore nie wraca bezpośrednio do published; przechodzi przez in_review zgodnie z policy,
- podczas restore-to-review publiczny tombstone pozostaje nieaktywny jako treść; URL nie wraca do 200 article przed udanym Publish,
- successful Publish po review zapisuje withdrawal history w AuditLog, czyści bieżące `withdrawn_at` + `withdrawal_reason` i aktualizuje `public_state_changed_at` w tej samej transakcji.

Hard delete jest dopuszczalny tylko administracyjnie dla błędnych/testowych rekordów bez historii publicznej.

Category:

- nie hard-delete, jeśli istnieją artykuły.

Source/tag pivots:

- cascade delete z artykułem jest dopuszczalne.

Redirect history:

- zachowujemy tak długo, jak artykuł ma publiczną historię URL.

---

## 34. Audit

Istniejący `AuditLog` / `AuditLogService` jest kanoniczną warstwą audytu. Nie dodajemy do `content_articles` pól `created_by_user_id`, `updated_by_user_id`, `published_by` ani `reviewed_by` tylko po to, by powielać historię aktorów. Bieżącego/pierwszego aktora UI wyprowadza z audytu.

`actor_user_id` wskazuje zalogowanego `User` administratora; scheduled/system action może mieć actor=null z jawnym metadata `trigger=scheduler`.

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

Audit nie zastępuje zwykłego updated_at ani revision history.

Metadata audytu ma być małe i allowlistowane: IDs, status before/after, timestamps, reason/trigger, powiązane entity IDs. Nie zapisujemy w metadata pełnego `body_blocks`, leadu, notatek źródłowych, raw source payloadów ani dużych fragmentów treści.

---

## 35. Uprawnienia

V1 zachowuje aktualny kontrakt `User::canAccessPanel()`: panel Filament jest admin-only. Newsroom nie rozszerza dostępu moderatorom ani nie tworzy roli editor/reviewer w tym module.

Policies nadal są wymagane jako backendowe zabezpieczenie operacji newsroomu oraz przygotowanie pod ewentualny przyszły RBAC.

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

W v1 wszystkie te abilities mapują się do istniejącego administratora. Nie zakładamy uprawnienia wyłącznie na podstawie ukrycia przycisku Filament. Backend policy/service jest źródłem autoryzacji. Rozszerzenie panelu na nie-adminów wymaga osobnego projektu auth/RBAC i nie jest częścią newsroom v1.

---

## 36. Polityka zmian modelu

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

## 37. Mapa plików — target

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

## 38. Data query contracts

### 38.1. Newsroom home

Musi zwrócić gotowy, ograniczony read model z `activelyDistributed()`; `needs_review` i `archived` nie są kandydatami do promocji:



- lead story,
- secondary stories,
- latest N,
- per-category N,
- guides N,
- opcjonalny breaking strip.

Źródłem kompozycji jest `NewsroomHomeCompositionService`, który łączy ręczne placements z fallbackami i deduplikacją.

Nie pobieramy całego corpusu i nie filtrujemy w PHP.

### 38.2. Category page

- activelyDistributed only,
- category_id,
- ordered by first_published_at desc + deterministic tie-breaker,
- stabilna paginacja,
- eager load author + minimal media metadata.

### 38.3. Article show

- `resolvePublicPath(slug, routeFamily)` disposition,
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

## 39. Related content

V1:

- ręczne relacje do pytań i przepisów,
- powiązane artykuły mogą być liczone przez kategorię/tagi z możliwością ręcznego override później.

Nie wdrażamy ML recommendera.

---

## 40. Freshness defaults

Wartości są policy, nie twardym invariant DB.

Proponowane punkty startowe:

- news: review tylko gdy temat nadal aktywny; nie przepisujemy historii bez powodu,
- guide: 90–180 dni zależnie od tematu,
- explainer prawny: 60–90 dni lub event-driven po zmianie prawa,
- analysis/report: aktualizacja tylko gdy metodologia/dane wymagają korekty.

Finalne wartości definiuje dokument Editorial Operations.

---

## 41. Wpływ na DATABASE-SCHEMA.md

Ten dokument jest specyfikacją przed implementacją.

Po merge migracji N1 należy:

1. zaktualizować DATABASE-SCHEMA.md o rzeczywiście utworzone tabele,
2. nie kopiować planowanych pól, których finalnie nie wdrożono,
3. wskazać ten dokument jako szczegółowy kontrakt newsroomu.

---

## 42. Definition of Done modelu danych

Model danych jest gotowy, gdy:

- migracje przechodzą up/down,
- constraints i indeksy są przetestowane,
- factories pokrywają główne statusy,
- publikacja nie może stworzyć niekompletnego publicznego rekordu,
- scheduling jest idempotentny,
- technical update nie zmienia SEO freshness ani nie emituje substantive-update eventu,
- cross-route-family type change po first publish jest zablokowany,
- slug change zachowuje redirect history,
- relations do questions/legal są jawne,
- sources rozróżniają public citation od wewnętrznego evidence i wspierają źródło bez URL,
- `body_blocks` + `body_schema_version` przechodzą walidację i compatibility policy per block type,
- homepage placements mają fallback i deduplikację,
- focal point ma poprawny zakres 0..1,
- hero caption, jeśli istnieje, jest zwykłym tekstem redakcyjnym renderowanym jako figcaption i nie zastępuje alt/credit,
- OG alt/fallback jest spójny z faktycznym assetem,
- publiczne URL-e obrazów dla SEO nie wygasają,
- topic nie powstaje automatycznie z taga,
- admin policies nie opierają się wyłącznie na UI i nie rozszerzają dostępu poza istniejących administratorów,
- AuditLog rozróżnia `User` actora od `ContentAuthor` author/reviewer identity i nie przechowuje pełnej treści artykułu,
- public visibility i active distribution są osobnymi scope'ami; needs_review/archived nie są aktywnie promowane, archive ma deterministyczny 200-history policy, a withdrawn ma deterministyczny 410/tombstone policy,
- public chronology używa first_published_at, nie ostatniego published_at/updated_at,
- public_state_changed_at oddziela sitemap/public-state freshness od merytorycznego dateModified,
- FK delete directions nie mogą kaskadować z newsroom article do istniejącego question/legal/sign/author/category,
- category/topic identity nie może zostać złamana przez zmianę publicznego sluga/dezaktywację,
- homepage placement overlap jest chroniony również przed równoległymi zapisami,
- public side effecty są emitowane after-commit,
- current DATABASE-SCHEMA.md odzwierciedla faktyczny kod.

---

## 43. Stan implementacji

Na 2026-09-16:

- ContentAuthor istnieje,
- legal trust layer istnieje,
- traffic signs workflow istnieje,
- NEWSROOM-N0-002 route contract jest wdrożony i przetestowany,
- NEWSROOM-N0-003 taxonomy seed contract jest wdrożony i przetestowany jako `NewsroomTaxonomyContract` v1,
- NEWSROOM-N0-004 body-format contract jest wdrożony i przetestowany jako `NewsroomBodyContract` v1,
- NEWSROOM-N0-006 media storage/validation contract jest wdrożony i przetestowany jako `NewsroomMediaStorage`,
- NEWSROOM-N1-001 jest wdrożone: istnieje 5 enumów domenowych oraz 12 migracji newsroomu,
- NEWSROOM-N1-002 jest wdrożone: istnieją modele `ContentArticle`, `ContentCategory`, `ContentTag`, `ContentTopic`, `ContentArticleSource`, `ContentHomePlacement`, ich factories, relations, reverse relations i scopes/predicates,
- NEWSROOM-N1-003 jest wdrożone: istnieją `ContentArticleSlugService`, `ContentArticleRedirect`, `ContentArticlePathResolver` i PostgreSQL advisory-lock serialization,
- NEWSROOM-N1-004 jest wdrożone w zakresie publishing/workflow foundation: istnieją `ContentArticlePublishingService`, `ContentArticleWorkflowTransitioned` i feature regression dla workflow/invariants/audit/after-commit rollback boundary,
- `/aktualnosci` i `/poradniki` pozostają placeholderami 200 z dedykowanym noindex header,
- przyszłe detail/category/topic/feed routes są zarejestrowane, lecz zwracają 404 do czasu publicznej implementacji,
- newsroom schema istnieje: `content_categories`, `content_tags`, `content_articles`, `content_topics`, pivots/relations, redirects i `content_home_placements` są tworzone przez 12 migracji,
- schema i warstwa modelowa są zweryfikowane na SQLite i PostgreSQL 16; `newsroom-postgres` uruchamia migration contract oraz model/scope contract,
- `ContentCategory` Eloquent model istnieje; N2-001 dodało jego Filament `ContentCategoryResource` oraz modelowe slug/delete/deactivation guards; dedykowany DB seeder kategorii nadal nie istnieje,
- `ContentArticle`, `ContentTag`, `ContentTopic`, `ContentArticleSource` i `ContentHomePlacement` Eloquent models/factories istnieją; factory workflow states pokrywają dokumentowany baseline,
- service-level route-family lookup guard istnieje w `ContentArticlePathResolver`; nadal nie jest podłączony do publicznych controllerów N3,
- NEWSROOM-N1-005 scheduler istnieje jako `newsroom:publish-due`, jest zarejestrowany co minutę w production i deleguje due-time revalidation/publish do `ContentArticlePublishingService`,
- NEWSROOM-N1-006 jest wdrożone: istnieją `NewsroomHomeCompositionService`, `NewsroomHomePlacementService` i niemutujący `ContentArticlePublishingService::assertScheduledPreviewReady()`; overlap/concurrency jest testowane również na PostgreSQL,
- newsroom CMS jest częściowo rozpoczęty przez `ContentCategoryResource`; article/topic resources, article editor/workflow UI i HomeComposer nadal nie istnieją.

---

## 44. Pozostałe zadania

- [ ] wdrożyć N2 Filament Builder/RichEditor adapter oparty o `NewsroomBodyContract`,
- [ ] wdrożyć N3 publiczny renderer bloków zgodny z `NewsroomBodyContract`,
- [ ] podłączyć `NewsroomMediaStorage` do N2 hero/OG uploader + `ContentArticle` persistence oraz wdrożyć focal point/OG-alt UX,
- [ ] wdrożyć crop/variant generation dopiero wraz z fizycznymi artefaktami i ich testami,
- [ ] podłączyć istniejące origin/regulatory columns do modeli, CMS i publish validation,
- [ ] wdrożyć dedykowany idempotentny DB seeder kategorii konsumujący `NewsroomTaxonomyContract`,
- [ ] wdrożyć policies,
- [ ] wdrożyć `applyPublicUpdate` orchestration/stale-write path dla już publicznego artykułu w N2,
- [ ] podłączyć `ContentArticlePathResolver` do publicznych N3 article controllers i zweryfikować HTTP canonical/301/404/410 behavior,
- [ ] dodać sitemap/public-discovery regression korzystające wyłącznie z current canonical URL,

---

## 45. Historia zmian

### 2026-09-16 — v0.16

- wdrożono NEWSROOM-N2-001 bez zmiany istniejących category invariants z §8.1,
- `ContentCategoryResource` udostępnia admin-only index/create/view/edit, article counts i zarządzanie `position`/active/SEO,
- slug syntax oraz immutability są wymuszane modelowo, niezależnie od disabled edit field,
- delete guard odpytuje faktyczną relację artykułów, a deactivation guard świeżo sprawdza `publiclyVisible()` i `activelyDistributed()`,
- stale preloaded counts służą prezentacji, ale nie mogą obejść save/delete invariants,
- szerszy newsroom CMS nadal nie jest uznany za wdrożony; następnym resource taskiem jest N2-002.

### 2026-09-16 — v0.15

- wdrożono NEWSROOM-N1-006 jako `NewsroomHomeCompositionService` + `NewsroomHomePlacementService`,
- kompozycja rozwiązuje manual placements, future-preview eligibility, deterministyczne fallbacki oraz globalne card dedupe zgodnie z §16,
- `ContentArticlePublishingService::assertScheduledPreviewReady()` udostępnia wspólną publication/fresh-review/breaking rewalidację dla future preview bez mutacji,
- placement writer serializuje overlap check przez PostgreSQL transaction advisory lock + row lock; half-open intervals dopuszczają sąsiadujące okna,
- category/guides context jest walidowany po stronie domenowej, a breaking strip pozostaje niezależnym wyjątkiem dedupe,
- finalny CI PR #35: quality 935 passed / 18 769 assertions / 2 skipped, Pint 990 files, frontend build PASS; newsroom-postgres 7 passed / 89 assertions,
- N1 domain foundation jest zamknięte; Filament placement UI i publiczny controller pozostają odpowiednio N2/N3.

### 2026-09-16 — v0.14

- wdrożono NEWSROOM-N1-005 jako komendę `newsroom:publish-due` oraz produkcyjną rejestrację co minutę z `withoutOverlapping()`,
- due publication reużywa `ContentArticlePublishingService`, więc publication/fresh-review invariants są rewalidowane pod istniejącym row lockiem,
- invalid due record pozostaje nieopublikowany, failure jest logowany i audytowany bez blokowania dalszych rekordów,
- failure AuditLog jest deduplikowany dla identycznego failure/scheduled_for, a concurrent/already-transitioned rekord po refetchu jest bezpiecznym skipem,
- service-level guard blokuje scheduled republish rekordu z istniejącym `first_published_at`,
- nie deklarujemy distributed `onOneServer` guarantee; obecny schedule contract to production + everyMinute + withoutOverlapping,
- finalny CI PR #33: quality 922 passed / 18 734 assertions / 2 skipped, Pint 985 files, frontend build PASS; newsroom-postgres 6 passed / 86 assertions.

### 2026-09-16 — v0.13

- wdrożono NEWSROOM-N1-004 publishing/workflow foundation przez `ContentArticlePublishingService`,
- state/timestamp mutation i AuditLog są objęte jedną transakcją z row lockiem artykułu,
- `ContentArticleWorkflowTransitioned` używa `ShouldDispatchAfterCommit`; rollback regression blokuje ghost side effects,
- publish/schedule walidują canonical body, category/author/source/media/key-points/breaking invariants i fresh review,
- archive/needs_review/withdraw czyszczą breaking state, republish zachowuje `first_published_at`, a withdrawal tombstone jest czyszczony dopiero przy skutecznym publish,
- AuditLog actor pozostaje `User`, niezależny od ContentAuthor author/reviewer identity,
- publiczne HTTP 410 oraz `applyPublicUpdate` nie są oznaczone jako wdrożone: odpowiednio pozostają N3 i N2,
- finalny CI PR #30: quality 917 passed / 18 656 assertions / 2 skipped, Pint 983 files, frontend build PASS; newsroom-postgres 6 passed / 86 assertions.

### 2026-09-16 — v0.12

- wdrożono NEWSROOM-N1-003 jako canonical slug/history/path-resolution layer,
- dodano `ContentArticleSlugService`, `ContentArticleRedirect`, `ContentArticlePathResolver` oraz `PostgresTransactionAdvisoryLock`,
- historyczne full paths są zarezerwowane przed innym artykułem, same-article reclaim jest service-only, a redirect history pozostaje one-hop,
- route-family transition po pierwszej publikacji jest blokowany, a resolver uniemożliwia service-level 200 lookup tego samego rekordu pod obiema rodzinami,
- PostgreSQL gate rozszerzono o slug service concurrency; finalnie 6 testów / 86 asercji PASS,
- ogólny gate przeszedł 906 testów / 18 565 asercji / 2 skipped, Pint 980 files i frontend build,
- publiczne HTTP redirect/canonical controllers nadal nie istnieją i pozostają N3 integration.

### 2026-09-16 — v0.11

- wdrożono NEWSROOM-N1-002 jako warstwę Eloquent nad schema N1-001,
- dodano `ContentArticle`, `ContentCategory`, `ContentTag`, `ContentTopic`, `ContentArticleSource` i `ContentHomePlacement` wraz z casts, relations i reverse relations do istniejących bytów produktu,
- rozdzielono wykonywalne scopes/predicates `publiclyVisible`, `activelyDistributed` i `indexable` oraz dodano category/topic/breaking/freshness/source/home-placement contracts,
- factories pokrywają wymagane stany `draft/inReview/scheduled/published/breaking/needsReview/archived`; dodatkowo istnieje `withdrawn`, a body fixture reużywa `NewsroomBodyContract`,
- PostgreSQL model/scope test został dodany do istniejącego gate; `newsroom-postgres` uruchamia cały `tests/Postgres` i finalnie przeszedł 4 testy / 81 asercji,
- ogólny finalny gate przeszedł 895 testów / 18 532 asercji / 2 skipped, Pint 974 files i frontend build,
- dedykowany DB seeder kategorii oraz N1-003+ services pozostają niewdrożone.

### 2026-09-16 — v0.10

- wdrożono NEWSROOM-N1-001 jako pierwszą zmaterializowaną warstwę DB newsroomu,
- dodano 5 enumów domenowych i 12 migracji zgodnych z tym dokumentem,
- materializacja obejmuje categories/tags/articles/topics, membership pivots, sources, question/legal/sign relations, redirect history i home placements,
- `content_articles` zawiera body schema, regulatory context, media/focal fields, public-state/freshness fields i wymagane indeksy; nie zawiera `canonical_url` ani `featured_position`,
- dodano SQLite schema regression oraz addytywny PostgreSQL 16 gate; PostgreSQL migration contract przeszedł 3 testy / 64 asercje wraz z rollbackiem,
- Eloquent models/factories/scopes pozostają niewdrożone i przechodzą do N1-002.

### 2026-09-16 — v0.9

- wdrożono N0-006 jako dedykowany `NewsroomMediaStorage` bez reużycia question-specific upload workflow,
- utrwalono immutable ULID source paths, dedykowany newsroom disk/prefix i shared image byte policy,
- backend inspectuje rzeczywisty obiekt po storage: bytes, raster MIME oraz width/height; deklarowane metadata mismatch są odrzucane,
- baseline obrazów to JPEG/PNG/WebP/AVIF; SVG/non-raster/path traversal/unmanaged path failują,
- publiczny URL jest rozwiązywany przez `MediaUrlResolver` i musi być stabilnym HTTP(S) URL,
- nie zadeklarowano crop variants ani newsroom asset modelu, których kod jeszcze nie posiada; N2/N3 integration pozostaje otwarta.

### 2026-09-16 — v0.8

- wdrożono N0-004 jako wykonywalny `NewsroomBodyContract` v1,
- kanoniczny body zapisano jako listę `{key?, type, data}`, oddzielając format domenowy od associative state Filament Buildera,
- rich text zamrożono jako TipTap JSON z allowlistą nodes/marks i bez arbitrary HTML,
- zdefiniowano ścisłe payload contracts dla aktywnych bloków i wyłączono `embed` do czasu provider/CSP gate,
- zapisano fail-closed `body_schema_version` i strategię reader-before-writer / migration-before-breaking-change,
- testy obejmują XSS escaping, unsafe URLs/nodes/marks, unknown blocks/version, keys, media paths, relacje i table shape,
- N2 editor i N3 public renderer pozostają niewdrożone.

### 2026-09-16 — v0.7

- wdrożono N0-003 jako wykonywalny `NewsroomTaxonomyContract` v1,
- zamrożono sześć slugów i nazw publicznych kategorii oraz deterministyczne pozycje 10..60,
- powiązano walidację slugów kontraktu z istniejącym `NewsroomRouteContract::SLUG_PATTERN`,
- pozostawiono description/SEO copy jawnie `null` do czasu zatwierdzenia redakcyjnego,
- doprecyzowano, że tabela, model i rzeczywisty DB seeder powstaną dopiero w N1 i mają konsumować kontrakt zamiast duplikować listę.

### 2026-09-16 — v0.6

- wdrożono N0-002 jako wykonywalny route contract zgodny z §29,
- zarejestrowano finalne named route namespaces i zabezpieczono kolejność feed/category/topic przed article catch-all,
- utrwalono `[a-z0-9-]+`, newsroom reserved segments `kategoria`/`temat` oraz type -> route family resolver,
- dodano post-publication cross-family type transition guard,
- zachowano pre-launch hub placeholders jako 200/noindex, podczas gdy przyszłe szczegółowe routes pozostają 404,
- record-level family lookup pozostawiono jawnie otwarty do momentu istnienia `ContentArticle` i publicznych controllerów.

### 2026-09-16 — v0.5

- po finalnym audycie rozdzielono `User` actora od `ContentAuthor` author/reviewer identity i uszczelniono audit metadata,
- zdefiniowano publicVisible vs activelyDistributed (needs_review pozostaje URL-em, ale nie aktywną dystrybucją) oraz deterministyczne zachowanie archive,
- chronologię publiczną związano z first_published_at zamiast published_at,
- poprawiono source model: URL może być null, a is_publicly_cited rozdziela public citation od wewnętrznego evidence,
- dodano jawny status withdrawn z backoffice reason/timestamp i 410 public disposition, oddzielając historyczne archive od takedownu,
- dodano public_state_changed_at dla uczciwego sitemap lastmod bez zanieczyszczania dateModified/updated_at,
- zdefiniowano bezpieczne kierunki FK/on-delete, aby newsroom nie mógł kaskadowo usuwać istniejących bytów produktu,
- usunięto redundantne created_by/updated_by z ContentArticle; AuditLog pozostaje jedynym źródłem aktorów artykułu,
- dodano immutable public slugs/active-category guards, trwałą rezerwację historycznych article paths i minimalny topic corpus baseline,
- rozdzielono freshness overdue od jawnego workflow needs_review,
- doprecyzowano faktyczny media baseline: resolver/config istnieją, ale uploader jest question-specific; newsroom wymaga własnego bezpiecznego adaptera,
- dodano body_schema_version i wymóg rzeczywistego backend sanitizera/structured format,
- dodano guard przed odpublikowaniem autora zależnych publicznych artykułów,
- dodano serializację concurrent homepage placements przez DB/advisory lock,
- zapisano transaction + after-commit contract dla publikacji i side effectów,
- usunięto założenie o async queue workerze przy `QUEUE_CONNECTION=sync`,
- poprawiono numerację source/scopes oraz kontrakt statycznych sitemap routes.

### 2026-09-16 — v0.4

- usunięto `featured_position` z artykułu; konkretna pozycja należy wyłącznie do content_home_placements,
- usunięto ręczny `canonical_url` z v1 i przyjęto twardy self-canonical,
- dodano `hero_image_caption`,
- nie zakodowano zmiennego zewnętrznego limitu długości headline jako DB/publish invariant; długość pozostaje kontrolą redakcyjną,
- zablokowano zmianę route family po pierwszej publikacji,
- jawnie odseparowano article-question pivot od istniejącego question relation graphu.

### 2026-09-16 — v0.3

- doprecyzowano dateModified/lastmod/feed timestamp semantics i substantive-update event,
- poprawiono numerację race-safety subsection,
- doprecyzowano media contract o og_image_alt i semantyczny fallback,
- zabroniono wygasających signed URLs dla obrazów używanych w OG/schema,
- dodano odpowiednie media invariants do DoD bez zmiany stanu implementacji.

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
