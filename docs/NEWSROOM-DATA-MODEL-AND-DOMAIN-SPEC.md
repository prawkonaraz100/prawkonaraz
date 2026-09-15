# Newsroom Data Model and Domain Specification

## 1. Status

- Status: Proposed / implementation-ready design
- Obszar: newsroom / media portal
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Bazowy stan repo przy projektowaniu: main@6a38c95ce76ee05997977d614d795ed8513462f1
- Ostatnia weryfikacja zgodności z kodem: main@37dbfafa2ec491054429d1151d2de16b2470647b (2026-09-16)
- Data: 2026-09-16
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

Stan kodu podczas audytu:

- istnieje `MediaUrlResolver` i wspólna konfiguracja public/upload disk,
- istniejący `AdminMediaUploadService` jest **question-specific** i zapisuje `QuestionMedia`; nie jest gotowym uploaderem newsroomu,
- Traffic Signs/ContentAuthor przechowują obecnie ścieżki assetów bez wspólnego newsroom asset modelu,
- nie ma potwierdzonego automatycznego pipeline'u cropów 1:1/4:3/16:9 dla newsroomu.

Target:

- URL-e publiczne rozwiązujemy przez istniejący `MediaUrlResolver` / public media config,
- newsroom dostaje własny bezpieczny upload adapter/service lub jawnie skonfigurowany Filament upload do newsroom prefix; **nie reużywa question-specific AdminMediaUploadService**,
- storage path, MIME, bytes i rzeczywiste dimensions są walidowane po stronie backendu przed uznaniem assetu za gotowy,
- newsroom media path jest unikalny/immutable (np. ULID/hash w nazwie); replacement zapisuje nowy object/path zamiast overwrite pod istniejącym publicznym URL,
- dozwolone obrazy v1: raster MIME zgodny z security/media config (JPEG/PNG/WebP/AVIF); SVG nie jest domyślnie dopuszczone dla newsroom upload,
- crop/variant jest deklarowany w schema/SEO tylko jeśli rzeczywisty plik został wygenerowany i jest publicznie osiągalny.

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

- `content_article_topic(topic_id, sort_order, article_id)`,
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

Kategorie v1:

- prawo-jazdy
- egzaminy
- przepisy
- word
- kierowcy
- osk

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
- sort_order smallint default 0
- created_at
- unique(article_id, topic_id)

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

Dokładny komponent edytora i serializacja wewnętrzna są decyzją N0-004, ale kontrakt domenowy jest stały:

- `body_blocks` jest jednym kanonicznym źródłem body,
- dokument body ma jawny `body_schema_version`,
- każdy `type` bloku ma allowlistowany schema payloadu,
- rich_text sanitizuje HTML/doc nodes po stronie serwera; nie polegamy wyłącznie na Filament/browser sanitization,
- aktualny composer nie zawiera jawnej backendowej biblioteki HTML sanitizer, więc N0-004 musi wybrać i przetestować konkretny sanitizer albo format strukturalny niewymagający arbitralnego HTML,
- aktualny bootstrap nie pokazuje newsroom-ready CSP middleware; nie wprowadzamy szerokiej CSP zmiany przy okazji edytora bez zgodności z istniejącymi analytics/fonts/scripts,
- script/style/event handlers są zabronione,
- `embed` jest domyślnie wyłączony w CMS, dopóki nie istnieje jawna provider allowlista, sandbox/referrer policy oraz zgodny z produkcją CSP/`frame-src` contract,
- po włączeniu embed przyjmuje tylko allowlisted providers/URL i renderer nie emituje arbitralnego iframe HTML,
- linki z `target=_blank` otrzymują bezpieczne `rel`,
- block renderer ignoruje/odrzuca nieznany typ zamiast wykonywać go jako HTML,
- publiczny renderer nie interpretuje arbitralnych klas CSS przekazanych z CMS.

Nie utrzymujemy pełnego `body_html` i `body_blocks` jako dwóch edytowalnych źródeł prawdy.

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

Kategorie v1 mogą być seedowane deterministycznie.

Seeder:

- updateOrCreate po slug,
- nie nadpisuje ręcznie zmienionej treści SEO bez jawnej decyzji,
- działa wielokrotnie bez duplikatów.

Nie seedujemy sztucznych produkcyjnych artykułów.

Test fixtures pozostają w factories/seed smoke data.

---

## 32. Factories

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

Na moment utworzenia dokumentu:

- ContentAuthor istnieje,
- legal trust layer istnieje,
- traffic signs workflow istnieje,
- newsroom tables nie istnieją,
- ContentArticle nie istnieje,
- newsroom CMS nie istnieje,
- route /aktualnosci jest placeholderem.

---

## 44. Pozostałe zadania

- [ ] finalizować naming tabel i klas,
- [ ] domknąć N0-004: serializacja bloków + editor + sanitizer,
- [ ] wdrożyć model topics,
- [ ] wdrożyć home placements/composition service,
- [ ] wdrożyć focal point + OG alt/stable public URL w media contract,
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

## 45. Historia zmian

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
