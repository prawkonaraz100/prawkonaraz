# Newsroom Admin CMS / Filament Specification

## 1. Status

- Status: Proposed / implementation-ready CMS contract
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Powiązane:
  - [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md)
  - [NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md](./NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md)
- Data: 2026-09-16
- Cel: zdefiniować panel redakcyjny Filament na tyle dokładnie, aby implementacja nie wymagała projektowania workflow podczas kodowania.

---

## 2. Wzorzec istniejący w repo

Newsroom ma wykorzystywać wzorce już obecne w:

- app/Filament/Resources/TrafficSigns/TrafficSignResource.php
- Schemas/TrafficSignForm.php
- Tables/TrafficSignsTable.php
- Schemas/TrafficSignInfolist.php
- App/Filament/Resources/ContentAuthors

W szczególności zachowujemy:

- podział Resource / Form / Table / Infolist,
- navigation group „Zawartość”,
- filtry workflow,
- checklisty publikacyjne,
- bulk actions tylko tam, gdzie są bezpieczne,
- eager loading relacji,
- czytelne status badges.

Nie kopiujemy jednak 1:1 pól traffic signs.

### 2.1. V1 auth / identity contract

Aktualny panel `/admin` jest dostępny wyłącznie dla `User::isAdministrator()` przez `User::canAccessPanel()`. Newsroom v1 zachowuje ten kontrakt.

Rozróżnienie bytów:

- `User` — zalogowany administrator i actor operacji/audytu,
- `ContentAuthor` — publiczna tożsamość autora/reviewera materiału,
- przypisanie `author_id` albo `reviewer_id` nie nadaje dostępu do Filament.

Nie dodajemy w newsroom v1 roli editor/reviewer/moderator do panelu. Jeśli w przyszłości wielu redaktorów ma logować się z ograniczonymi abilities, jest to osobny projekt RBAC.

---

## 3. Resources v1

Wymagane:

1. ContentArticleResource
2. ContentCategoryResource
3. ContentTopicResource
4. NewsroomHomeComposer — custom Filament page do obsadzania stałych slotów `/aktualnosci`

Tagi mogą być zarządzane inline lub przez osobny resource zależnie od prostoty implementacji.

Źródła są zarządzane jako relation/repeater w artykule; osobny globalny SourceResource nie jest potrzebny v1.

---

## 4. ContentArticleResource

Navigation:

- group: Zawartość
- label: Aktualności i artykuły
- icon: semantycznie związana z dokumentem/newspaper
- sort po istniejących content resources tak, aby panel pozostał logiczny

Pages:

- index
- create
- view
- edit

Preview v1:

- action korzysta z authenticated administrator-only route,
- nie tworzymy shareable signed preview tokenów w v1,
- renderer może być public-like, ale transport pozostaje prywatny/no-store.

---

## 5. Lista artykułów

Kolumny obowiązkowe:

- title
- type
- category.name
- workflow_status
- author.name
- reviewer.name
- published_at
- scheduled_for
- is_featured
- is_breaking
- freshness_review_due_at
- updated_at

Nie wszystkie muszą być domyślnie szerokie; część toggleable.

---

## 6. Status badges

workflow_status:

- draft -> gray
- in_review -> warning
- scheduled -> info
- published -> success
- needs_review -> warning/danger zależnie od overdue
- archived -> gray
- withdrawn -> danger

Kolor jest wsparciem, nie jedynym nośnikiem informacji.

---

## 7. Search

Search po:

- title
- slug
- opcjonalnie lead

Nie wyszukujemy full body w v1, jeśli powoduje ciężkie query.

---

## 8. Filtry listy

Wymagane:

- status
- type
- category
- author
- reviewer
- featured
- breaking
- scheduled
- freshness overdue
- published date range

Quick filters mile widziane:

- „Do publikacji”
- „Zaplanowane”
- „Wymaga review”
- „Pilne aktywne”

---

## 9. Default sort

updated_at desc lub first_published_at desc zależnie od filtra.

Dla standardowego panelu redakcyjnego preferujemy updated_at desc, aby ostatnia praca była na górze. Widok chronologii publikacji używa first_published_at, nie ostatniego wejścia w workflow published.

---

## 10. Create form — sekcje

Kolejność:

1. Tożsamość i klasyfikacja
2. Pochodzenie i kontekst
3. Treść blokowa
4. Źródła
5. Powiązania
6. Media / art direction
7. SEO
8. Workflow i publikacja
9. Freshness / review
10. Notatki wewnętrzne
11. Checklista publikacyjna

---

## 11. Sekcja „Tożsamość i klasyfikacja”

Pola:

- type
- category_id
- title
- slug
- tags
- topics
- author_id
- reviewer_id

Dla `type=news` UI pokazuje licznik znaków tytułu oraz redakcyjny warning dla nadmiernie długiego headline. Nie kodujemy twardego limitu znaków pochodzącego z zewnętrznej dokumentacji bez ponownej weryfikacji; aktualne wytyczne Google zalecają tytuł zwięzły, a nie stały limit 110.

Po pierwszej publikacji zwykły Select `type` nie może przenieść rekordu pomiędzy route family `newsroom` i `guides`. Może nadal zmienić news/explainer/analysis/report wewnątrz rodziny `newsroom`, jeśli pozostałe invariants są spełnione.

### 11.1. Title

- required
- max 255
- live counter opcjonalnie
- nie blokować tylko ze względu na „SEO ideal length”.

### 11.2. Slug

- generowany z title przy create,
- po ręcznej zmianie nie nadpisuje się sam po każdej edycji title,
- unique(ignoreRecord),
- ostrzeżenie przy zmianie opublikowanego sluga,
- reserved segments route family (minimum `kategoria`, `temat` dla newsroom) blokowane przez backend/service, nie tylko UI.

Zmiana opublikowanego sluga musi przejść przez ContentArticleSlugService.

Walidacja pola `unique(ignoreRecord)` jest tylko UX. Service dodatkowo rezerwuje/checkuje pełny canonical path wobec historycznych `content_article_redirects.from_path`, aby nowy artykuł nie przejął starego publicznego URL innego artykułu.

Form nie może po prostu zapisać nowego sluga z pominięciem redirect history.

---

## 12. Sekcja „Pochodzenie i kontekst”

Pola:

- origin_type
- regulatory_status
- effective_from
- change_summary
- applies_to
- exam_impact

### 12.1. origin_type

Opcje zgodne z enumem domenowym, np.:

- original
- compiled
- official_source
- data_analysis
- licensed_agency

`licensed_agency` jest dostępne wyłącznie, jeśli istnieje faktyczne prawo/licencja do takiego użycia. CMS nie sugeruje, że samo wskazanie medium jako źródła czyni materiał „agencyjnym”.

### 12.2. Kontekst regulacyjny

Pola są pokazywane warunkowo dla tematów, w których mają sens.

Panel powinien zadawać redaktorowi konkretne pytania:

- Jaki jest status zmiany?
- Od kiedy obowiązuje?
- Co dokładnie się zmienia?
- Kogo dotyczy?
- Czy wpływa na egzamin?

Brak `regulatory_status != not_applicable` bez odpowiedniego źródła powinien generować warning/blocking zgodnie z Editorial Policy.

---

## 13. Sekcja „Treść blokowa”

CMS nie udostępnia jednego dowolnego pola HTML jako całego artykułu.

Edytor pracuje na uporządkowanym `body_blocks`.

### 13.1. Dozwolone bloki v1

- Rich text
- Image
- Quote
- Table
- Context / callout
- Related article
- Legal reference
- Question group
- Traffic sign group
- Product CTA
- Embed — typ znany kontraktowi, ale wyłączony w v1 do czasu osobnego security/CSP gate

### 13.2. Builder UX

NEWSROOM-N0-004 wybrało `Filament\Forms\Components\Builder` jako komponent/adaptor przyszłego N2 editora. Jego wewnętrzny associative item state nie jest jednak formatem domenowym: przy zapisie musi zostać znormalizowany przez `NewsroomBodyContract` do uporządkowanej listy `{key?, type, data}`.

**Stan implementacji:** podstawowy `ContentArticleResource` shell już istnieje po N2-002, ale Builder/RichEditor adapter do `body_blocks` nadal nie istnieje i pozostaje zakresem N2-003.

Redaktor powinien móc:

- dodać blok,
- zmienić kolejność,
- zduplikować bezpieczny blok treściowy, jeśli komponent na to pozwala,
- usunąć blok z confirmation dla bloków z większą ilością treści,
- zobaczyć czytelną etykietę typu,
- edytować payload wyłącznie przez pola przewidziane dla danego typu.

Nie pozwalamy na:

- dowolne klasy CSS,
- dowolny HTML block,
- własny JS,
- własne iframe poza allowlistą,
- zmianę szerokości/layoutu przez redaktora.

### 13.3. Rich text block

N0-004 wybiera `Filament\Forms\Components\RichEditor` w trybie structured TipTap JSON, nie HTML. Docelowy N2 adapter ma używać toolbaru zgodnego z `NewsroomBodyContract::richTextToolbarButtons()`:

- bold / italic / link,
- H2 / H3,
- ordered / unordered lists,
- undo / redo.

Paragraph i hard break wynikają z dozwolonego TipTap document contract. Blockquote pozostaje osobnym blokiem `quote`, nie rich-text node.

Backendowy `NewsroomBodyContract` allowlistuje nodes/marks, odrzuca raw HTML/style/custom nodes, unsafe URLs/targets oraz normalizuje `target=_blank` do bezpiecznego `rel`.

### 13.4. Context block

Kontrolowane warianty prezentacyjne, np.:

- dlaczego_to_wazne
- co_sie_zmienia
- uwaga
- metodologia

Wariant wpływa na semantykę i styl komponentu, ale nie pozwala redaktorowi wprowadzać dowolnego koloru/layoutu.

### 13.5. Domain blocks

Legal reference, question group i traffic sign group wybierają istniejące rekordy przez wyszukiwarkę, nie kopiują ich treści ręcznie.

Product CTA wybiera kontrolowany typ akcji/destination zamiast dowolnego HTML buttona.

### 13.6. Key points

`key_points` jest publiczną, strukturalną listą „W skrócie”:

- 2–5 krótkich punktów,
- każdy punkt plain text / bez arbitralnego HTML,
- opcjonalne dla artykułu,
- kolejność edytowalna,
- nie generujemy automatycznie z body.

---

## 14. Autosave

Nie jest wymagane w pierwszym PR.

Jeśli wdrażamy później:

- jawny status „Zapisano”,
- ochrona przed nadpisaniem równoległej edycji,
- nie publikować autosave.

---

## 15. Sekcja „Źródła”

Źródła jako repeater/relationship records.

Pola per source:

- source_type
- publisher
- title
- url nullable
- published_at
- accessed_at
- is_primary
- is_official
- is_publicly_cited
- note wewnętrzne
- sort_order

UI:

- akcja „Dodaj źródło”
- przycisk otwarcia URL tylko gdy URL istnieje
- validation URL tylko dla niepustej wartości
- badge primary/official/public citation
- wyraźne oznaczenie „tylko wewnętrzne” dla `is_publicly_cited=false`

### 15.1. Aktualny stan implementacji N2-004

Sekcja źródeł jest zmaterializowana w istniejącym `ContentArticleResource` jako relationship Repeater `sources`:

- korzysta z istniejącej relacji `ContentArticle::sources()` i modelu `ContentArticleSource`,
- startuje z `defaultItems(0)`, więc zwykły draft może zostać zapisany bez sztucznego pustego źródła,
- reorder zapisuje `sort_order`,
- formularz pokazuje stany PRIMARY / OFFICIAL / PUBLIC / TYLKO WEWNĘTRZNE i wszystkie source types v1,
- URL jest opcjonalny; dla niepustej wartości UI wymaga HTTP(S), a akcja „Otwórz źródło” pojawia się tylko dla bezpiecznego HTTP(S) URL,
- ordinary Edit `publiclyVisible()` ma source Repeater disabled, a server-side public edit path nie zapisuje source relationship,
- `ContentArticleSource` nadal bumpuje parent `ContentArticle.updated_at`, ale po PR #50 loaded-state guard używa deterministycznego tokenu obejmującego również source state; same-second child mutation jest odrzucana przed persistence,
- publiczny renderer źródeł nadal nie istnieje, więc N2-004 nie jest deklarowane jako wdrożenie public citation UI.

---

## 16. Source warnings

Panel ostrzega/blokuje zgodnie z policy:

- news bez żadnego źródła,
- news w kategorii Przepisy bez publicznie cytowalnego official/legislation source z URL, jeśli takie źródło istnieje,
- dwa primary source nie są błędem globalnym, ale UI powinno pokazać stan,
- source bez title jest niekompletny,
- brak URL jest dozwolony dla interview/direct evidence/other bez publicznego linku,
- `is_publicly_cited=false` oznacza, że title/publisher/url nie mogą wyciec do publicznego renderera.

Publish service ma finalną walidację, niezależnie od ostrzeżeń UI.

Aktualny backend po N2-004 egzekwuje mechanicznie:

- co najmniej jeden source dla news,
- wymagany title i wspierany source type dla każdego source,
- poprawny HTTP(S) dla każdego niepustego URL,
- dla kategorii `przepisy`: jeśli istnieje primary `official`/`legislation`, co najmniej jeden taki primary musi być publicznie cytowalny z HTTP(S) URL.

Nie wdrożono jeszcze osobnego computed warningu „brak primary source dla prawnego newsa”; pozostaje on w późniejszym publication checklist/provenance scope. To jest granica implementacji, a nie zmiana policy redakcyjnej.

---

## 17. Sekcja „Powiązania”

Podsekcje:

### 17.1. Pytania

Searchable multi-select lub relation manager.

Pokazujemy:

- external_id,
- skrócony prompt,
- kategorie,
- aktywny/publiczny status.

Nie wybieramy po samym internal ID bez kontekstu.

### 17.2. Legal units

Search:

- act short title,
- label art./§,
- title/summary.

### 17.3. Traffic signs

Search:

- code
- name

Może być deferred do N3/N4.

---

## 18. Relation ordering

Relacje, dla których publiczna kolejność jest redakcyjna (questions/legal/signs), mają `sort_order` i admin umożliwia reorder lub przynajmniej zachowuje kolejność dodania.

`content_article_topic` jest wyjątkiem: topic ma pojedynczy `featured_article_id`, a reszta corpus jest chronologiczna po `first_published_at`; nie utrzymujemy drugiego ręcznego rankingu topicu.

### 18.1. Aktualny stan implementacji N2-005

Sekcja „Powiązania” jest zmaterializowana w istniejącym `ContentArticleResource`:

- questions/legal/signs używają ordered Repeaterów i istniejących pivotów; kolejność UI zapisuje się jako `sort_order`,
- topics używają searchable multi-select bez reorder i bez nowego `sort_order`, zgodnie z powyższą decyzją architektoniczną,
- questions są wyszukiwane po external ID/prompt, a label zawiera external ID, kategorię prawa jazdy i active/public state,
- legal units są wyszukiwane po label/title/canonical path z kontekstem aktu; traffic signs po code/name/slug; topics po title/slug/description,
- wszystkie search callbacks są bounded do 50 rekordów i nie używają preloadu dużych corpusów,
- `NewsroomArticleRelationsEditorAdapter` waliduje target existence, duplicate targets i relation-type allowlists przed sync,
- create/edit synchronizują article-owned pivots transakcyjnie; ordinary Edit `publiclyVisible()` nie przechodzi przez ten sync path,
- relacja nie modyfikuje target entity ani istniejącego question/legal/sign graphu,
- relation sync bumpuje parent `ContentArticle.updated_at`, a po PR #50 deterministyczny loaded-state token obejmuje także article-owned relations/topics i jest sprawdzany przed sync,
- publiczny renderer/reverse-link UI nadal nie istnieje.

---

## 19. Sekcja „Media / art direction”

Pola:

- hero image
- hero alt
- hero caption opcjonalnie
- width/height metadata
- focal point X/Y
- podgląd cropów lead / standard / compact
- OG image lub wygenerowany OG variant
- OG image alt
- image credit — publiczny, jeśli potrzebny
- image license note — tylko backoffice, nigdy publicznie

Reużywamy istniejących reguł storage/media URL, allowlist i size policy, ale **nie** reużywamy bezpośrednio `AdminMediaUploadService`: jest question-specific (`Question`/`QuestionMedia`). Newsroom potrzebuje własnego adaptera/service zapisującego newsroom asset metadata i generującego immutable/unique public paths.

Focal point powinien być ustawiany wizualnie na obrazie, jeśli komponent na to pozwala, z fallbackiem do pól liczbowych/środka. Redaktor nie uploaduje ręcznie osobnych kopii dla każdej karty, jeśli system może wygenerować crop z tego samego źródła.

Nie przechowujemy raw binary w bazie.

### 19.1. Aktualny foundation po N0-006

Kod posiada `NewsroomMediaStorage`, który:

- jest osobny od question-specific `AdminMediaUploadService`,
- przygotowuje immutable/unique source paths pod dedykowanym newsroom prefixem,
- używa wspólnej image MIME/size policy, zawężonej do JPEG/PNG/WebP/AVIF,
- po zapisie inspectuje rzeczywisty object: bytes, dekodowalny raster MIME, width i height,
- odrzuca SVG/non-raster, metadata mismatch, path traversal i obiekty spoza managed namespace,
- rozwiązuje stabilny publiczny HTTP(S) URL przez `MediaUrlResolver`.

Nie istnieją jeszcze: Filament hero uploader, presign/confirm endpointy newsroomu, zapis asset metadata do `ContentArticle`, focal-point picker ani crop generator. N2 ma użyć istniejącego storage contract zamiast budować drugi upload policy.

---

## 20. Image validation

Przy upload/wyborze:

- format allowlist,
- size policy zgodna z media system,
- dimensions odczytywane automatycznie jeśli pipeline wspiera,
- alt wymagany przed publish jeśli hero exists,
- caption opcjonalny i widoczny jako figcaption; nie zastępuje alt ani credit.

Dodatkowe warning:

- szerokość < 1200 px dla materiału oznaczonego jako Discover-ready/featured, jeśli taki marker zostanie wdrożony,
- dedicated OG image bez alt,
- publiczny SEO image URL wymaga auth lub wygasającego podpisu — blocking; OG/schema asset musi mieć stabilny publiczny URL bez auth,
- crop usuwa główny subject.

Nie blokować wszystkich publikacji wyłącznie przez Discover recommendation. Natomiast obraz wskazany w publicznym OG/schema musi mieć stabilny publiczny URL.

---

## 21. Sekcja „SEO”

Pola:

- seo_title
- seo_description
- robots

UI pokazuje fallback preview:

- final title,
- final description,
- canonical wyliczony z route family + slug.

V1 nie ma edytowalnego canonical override. Redaktor nie może skierować artykułu na inny canonical URL z poziomu formularza.

`seo_title` może różnić się od H1, ale:

- nie może zmieniać znaczenia lub głównego claimu,
- nie może obiecywać informacji nieobecnej w artykule,
- nie może dodawać sztucznego clickbaitu/sensacyjności,
- `news:title` w News Sitemap nadal bierze widoczny `title`, nie `seo_title`.

---

## 22. Robots control

Nie dajemy redaktorowi przypadkowego free-text robots bez zabezpieczeń.

Opcje kontrolowane:

- default
- noindex,follow
- noindex,nofollow

index,follow wynika z published/default policy.

---

## 23. Sekcja „Workflow i publikacja”

Pola/actions:

- workflow_status jako badge/action-driven
- scheduled_for ustawiane przez Schedule action
- first_published_at read-only
- published_at read-only / service-controlled
- is_featured
- editorial_priority
- is_breaking
- breaking_expires_at

Workflow zmieniamy przez actions/service, nie przez dowolny Select, jeśli przejście wymaga walidacji.

Można pokazać pole status jako read-only badge i osobne actions.

---

### 23.1. Published-content edit safety without revisions

V1 nie ma revision/staging copy. Dlatego normalny Filament Edit nie może sugerować, że można zmienić opublikowany body i „zapisać draft”, gdy publiczna strona czyta ten sam rekord.

Dla `publiclyVisible()` article:

- publiczne pola są w zwykłym formularzu read-only albo ich Save jest przechwycony przez dedykowany use case,
- UI ma jawny action/mode `Apply public update`,
- przed commit pokazuje checklistę i informację „ta zmiana stanie się publiczna natychmiast po zapisie”,
- backend ponownie waliduje całość i stale-write token,
- cały public payload zapisuje się atomowo,
- meaningful update ustawia `last_substantive_update_at`,
- cancellation/validation failure pozostawia publiczną wersję bez zmian.

Pola wewnętrzne (np. editorial_note, freshness due) mogą mieć osobny save bez publicznej zmiany.

Jeśli organizacja potrzebuje edycji + review + przyszłego publish **bez zmiany aktualnej publicznej wersji**, jest to jawny future staging/revision scope i nie może zostać zasymulowany tym jednym rekordem.

---

## 24. Preferred workflow actions

Record actions:

- Save draft
- Submit for review
- Return to draft
- Mark reviewed
- Schedule
- Publish now
- Apply public update — dla już publiclyVisible article
- Mark needs review
- Archive
- Republish — tylko archived, po aktualnym review/checklist
- Withdraw from public
- Restore to review — tylko dla withdrawn
- Preview

### 24.1. Aktualny stan implementacji N2-006

Po PR #48 na `main@88533b04d74a839c3bbccf86b707909ccdd235f8` Edit i View używają wspólnej warstwy `InteractsWithContentArticleWorkflowActions`.

Wdrożone actions:

- Submit for review,
- Return to draft,
- Mark reviewed,
- Schedule pierwszej publikacji,
- Publish now,
- Mark needs review,
- Archive,
- Republish archived po fresh review,
- Withdraw from public z wymaganym `withdrawal_reason`,
- Restore to review,
- Featured + `editorial_priority` / unfeature,
- Breaking z wymaganym przyszłym `breaking_expires_at` / clear breaking.

UI deleguje do `ContentArticlePublishingService`; dodatkowe `setFeatured()`, `enableBreaking()` i `clearBreaking()` zachowują transaction/row-lock pattern, allowlisted AuditLog i `public_state_changed_at` dla public exposure changes.

Po PR #50 na `main@570f884a89869ec44d24f57f0506f4444d20a7d2` wdrożono także:

- jawny mode/action `Apply public update` dla `publiclyVisible()`,
- deterministyczny loaded-state token obejmujący article + sources + article-owned relations/topics zamiast polegania wyłącznie na sekundowym `updated_at`,
- stale-write reject przed article/source/relation mutation zarówno dla ordinary Save, jak i `Apply public update`,
- atomowy public payload write przez `ContentArticlePublishingService`, z rollbackiem przy failed validation,
- `last_substantive_update_at` tylko dla semantycznej publicznej zmiany oraz allowlisted AuditLog z `User` actorem.

N2-006 jest przez to DONE. N2-012 pozostaje PARTIAL tylko dla analogicznego stale-write guard w przyszłym `NewsroomHomeComposer`; Preview nadal pozostaje NEWSROOM-N2-008.

Każda action:

- ma confirmation, jeśli jest destrukcyjna/publiczna,
- wywołuje application service,
- pokazuje błędy checklisty.

`Withdraw from public` jest high-friction action:

- wymaga niepustego `withdrawal_reason`,
- pokazuje, że URL stanie się 410 i zniknie z dystrybucji,
- nie jest bulk action,
- jeśli istnieje realny następca, administrator zamiast tego używa kontrolowanego redirect flow.

`Restore to review`:

- nie przywraca publicznej treści,
- zmienia workflow do in_review,
- dawny URL nadal daje 410, dopóki ponowny Publish nie przejdzie pełnej walidacji,
- ponowny Publish czyści aktywny withdrawal tombstone po zapisaniu historii w AuditLog.

---

## 25. Publish action

Po kliknięciu:

1. ContentArticlePublishingService waliduje record,
2. jeśli braki -> nie publikuje,
3. UI pokazuje listę braków,
4. jeśli OK -> transakcja publish + wymagany AuditLog,
5. commit,
6. dopiero after commit dispatch public side effects/events,
7. redirect/notification do view/edit.

Rollback transakcji nie może wyemitować cache/sitemap/IndexNow side effect.

Filament resource nie duplikuje logiki publish.

---

## 26. Schedule action

Modal:

- date/time,
- Europe/Warsaw w UI,
- podgląd finalnej daty,
- confirmation.

Backend zapisuje jednoznaczny timestamp.

Nie pozwala scheduled_for <= now bez jawnej konwersji na „Publish now”.

---

## 27. Breaking action

Warunki:

- tylko published news,
- breaking_expires_at wymagane.

Modal:

- expiry,
- opcjonalny label/priority jeśli model później wspiera.

Default expiry może być np. kilka godzin, ale nie hardcodujemy bez uzgodnienia editorial policy.

---

## 28. Featured action

Może być toggle/action.

Featured nie zastępuje homepage placement.

V1:

- is_featured opisuje rekomendację redakcyjną,
- editorial_priority pomaga fallbackom,
- konkretne miejsce na `/aktualnosci` ustawia NewsroomHomeComposer.

---

### 28.1. Correction action

`correction_note` nie jest zwykłym polem roboczym obok `editorial_note`.

Dla istotnej korekty publicznego artykułu action `Apply correction`:

- wymaga krótkiego publicznego `correction_note`,
- wymaga aktualnego review zgodnie z policy,
- korzysta z tego samego atomowego `Apply public update`,
- ustawia `last_substantive_update_at`,
- zapisuje AuditLog bez pełnego body,
- publikuje note i zmianę treści w jednym commit.

Drobna korekta bez wpływu na sens może użyć `Apply public update` bez public correction note, ale nadal ma audit.

---

## 29. Freshness section

Pola:

- source_checked_at
- freshness_review_due_at
- last_substantive_update_at read-only / service-controlled
- public_state_changed_at read-only / service-controlled
- reviewed_at read-only, ustawiane przez Mark reviewed/workflow service
- `editorial_note` edytowalne jako planowane pole wewnętrzne na notatki review/redakcyjne; nie tworzymy osobnego `review_notes` bez decyzji modelowej

Status computed:

- fresh
- due soon
- overdue
- not scheduled

`overdue` jest filtrem/kolejką pracy, **nie** automatycznym workflow transition. Upływ `freshness_review_due_at` nie może sam wyrzucić artykułu z home/category/feed. Akcja `Mark needs review` pozostaje oddzielną, audytowaną decyzją.

---

## 30. Checklista publikacyjna w adminie

Panel pokazuje live/read-only listę.

Minimum:

- title
- slug
- type
- category
- author
- lead
- co najmniej jeden renderowalny `body_blocks`
- source
- hero alt if hero
- review if policy requires
- publish date state
- no unresolved validation errors

Każdy item:

- green/ok
- warning
- blocking

Nie wszystko musi blokować.

---

## 31. Severity checklist

Blocking:

- brak renderowalnego `body_blocks`,
- brak author,
- brak category,
- brak source dla news,
- wymagany review/fresh review,
- scheduled state invalid,
- hero ma asset bez wymaganego alt/wymiarów,
- dedykowany OG asset semantycznie różny od hero nie ma własnego alt albo poprawnych wymiarów,
- inne twarde invariants `ContentArticlePublicationChecklist` / `ContentArticlePublishingService`.

Warning:

- brak hero,
- brak manual SEO description,
- brak related questions,
- brak OG-specific image, jeśli hero daje poprawny fallback,
- brak primary official/legislation source dla prawnego newsa jest sygnałem redakcyjnym, o ile nie narusza twardej source policy.

`OG alt` nie jest warningiem, gdy istnieje dedykowany inny OG asset: zgodnie z domain/media contract taki asset wymaga własnego alt i jest blockerem. Fallback bez dedykowanego OG assetu pozostaje nieblokujący.

---

## 32. Preview action

V1 używa wyłącznie authenticated administrator-only preview route.

Action może otwierać nową kartę, ale:

- anonymous -> denied,
- non-admin -> denied przez istniejący panel/auth contract,
- response ma `Cache-Control: private, no-store`,
- robots = `noindex,nofollow`,
- preview URL nie trafia do publicznych linków, sitemap, feed ani analytics page-view liczonego jako publiczny artykuł.

Nie projektujemy shareable signed preview tokenów w v1. Jeśli kiedyś będzie potrzebny external review, dostaje osobny threat model, TTL/revocation i audit.

Preview banner zawiera:

- status,
- article id,
- planned publish time,
- informację „Podgląd — niepubliczne”,
- link „Edytuj”.

---

## 33. NewsroomHomeComposer

To jest custom Filament page do redakcyjnego układania `/aktualnosci`.

Nie jest page builderem.

### 33.1. Widok

Panel pokazuje stałe sekcje/sloty zdefiniowane przez kod:

- lead,
- secondary 1..N,
- category leads,
- guides lead,
- important_now 1..N.

Każdy slot pokazuje:

- aktualnie przypisany artykuł,
- okres aktywności,
- fallback, który zostałby użyty bez ręcznego przypisania,
- warning i brak możliwości aktywnego zapisu, jeśli artykuł nie będzie `activelyDistributed()` w wybranym czasie; dotyczy m.in. needs_review, archived, active-withdrawal tombstone, draft i future scheduled poza wybranym czasem.

### 33.2. Obsługa placementu

Redaktor może:

- wyszukać artykuł,
- przypisać go do slotu,
- ustawić `starts_at`,
- ustawić `ends_at`,
- ustawić pozycję dla slotów wieloelementowych,
- usunąć ręczne przypisanie i wrócić do fallbacku.

Nie może:

- stworzyć nowego typu slotu,
- zmienić gridu,
- wkleić dowolnego HTML,
- ustawić tego samego artykułu w kilku card slots dla tego samego czasu bez warningu/blokady wynikającej z reguł kompozycji.

### 33.3. Future preview całej strony

Panel ma akcję:

`Podgląd /aktualnosci`

z opcjonalnym parametrem czasu.

Przykład:

`Pokaż stan strony: 2026-09-16 08:00 Europe/Warsaw`

Resolver preview uwzględnia:

- placements aktywne w wybranym czasie,
- artykuły scheduled, które do tego czasu będą już opublikowane,
- fallbacki,
- deduplikację modułów.

Preview jest authenticated admin-only, `private, no-store`, noindex/nofollow i zabezpieczone identycznie jak preview artykułu.

---

## 34. ContentTopicResource

Pola:

- title
- slug
- description
- status
- featured article
- articles/relation ordering
- SEO title/description
- published_at

Publiczny route topicu:

`/aktualnosci/temat/{topicSlug}`

Publicacja topicu blokuje, jeśli:

- brak własnego opisu redakcyjnego,
- mniej niż 3 actively-distributed, indeksowalne artykuły w corpus,
- featured article jest ustawiony, ale nie jest actively-distributed + indexable albo nie należy do tego topicu.

Dodatkowo:

- slug można edytować w draft,
- po pierwszej publikacji slug topicu jest read-only w v1,
- próg >=3 jest blocking przy publish/republish,
- jeśli już opublikowany topic później spadnie poniżej 3, CMS pokazuje `corpus below baseline` warning i wyłącza go z redakcyjnej promocji/featured-topic selection, ale nie zmienia automatycznie statusu ani HTTP,
- administrator uzupełnia corpus albo jawnie archiwizuje topic; archived topic po wcześniejszej publikacji daje 410.

Próg 3 jest baseline jakości produktu v1, nie gwarancją rankingu Google.

Tag creation pozostaje oddzielnym lekkim mechanizmem.

---

## 35. View/Infolist

Sekcje:

- identity
- workflow
- publication
- SEO readiness
- sources
- relations counts
- media
- freshness
- audit summary

Infolist jest miejscem szybkiego read-only review bez wchodzenia do pełnego edytora.

---

## 36. Table bulk actions

Bezpieczne v1:

- set freshness review date
- assign reviewer
- set category, tylko dla draftów jeśli potrzebne
- archive selected z confirmation, ale ostrożnie

Niebezpieczne / nie rekomendowane v1:

- bulk publish
- bulk breaking
- bulk change slug
- bulk delete published

Publikacja powinna być świadoma per record, przynajmniej do czasu stabilnego workflow.

---

## 37. Categories resource

Fields:

- name
- slug
- description
- position
- active
- seo title
- seo description

List:

- name
- slug
- active
- articles count
- published articles count
- position

Nie pozwala:

- delete kategorii z artykułami,
- zmienić sluga kategorii po utworzeniu/seedzie w v1,
- ustawić inactive, jeśli istnieją publicznie widoczne/aktywnie dystrybuowane artykuły w tej kategorii.

Przed dezaktywacją administrator musi przepiąć lub wycofać zależne publiczne materiały.

---

## 38. Tags UX

Jeśli inline:

- searchable
- create option only dla uprawnionych,
- trim/normalize,
- duplicate prevention case-insensitive.

Nie tworzyć tagów przez literówki typu:

- PKK
- pkk
- Pkk

---

## 39. Author integration

Select author używa ContentAuthor.

W UI pokazujemy:

- name
- role/title
- public state z istniejącego `ContentAuthor::isPubliclyVisible()`

Nie tworzymy autora ad hoc w article form bez pełnego profilu, chyba że Filament flow create-related jest bezpieczny.

---

## 40. Reviewer policy hints

Przy wybraniu kategorii/type admin może pokazać:

„Ten materiał wymaga review przed publikacją”

Policy logic pozostaje po stronie service/policy, nie tylko JS/Filament visibility.

---

## 41. Concurrent editing / stale-write guard

V1 wymaga ochrony przed cichym nadpisaniem co najmniej dla:

- ContentArticle edit,
- NewsroomHomeComposer placement write.

Przy otwarciu formularza zapamiętujemy wersję/timestamp rekordu. Przed zapisem backend porównuje bieżący `updated_at` (lub równoważny token) z wartością załadowaną przez edytora.

Jeśli rekord zmienił się w międzyczasie:

- zapis jest odrzucony,
- UI pokazuje komunikat „rekord został zmieniony przez inną operację/użytkownika”,
- redaktor musi odświeżyć i świadomie ponowić zmiany.

Nie dokładamy kolumny `lock_version`, jeśli `updated_at` wystarcza. Warning bez blokady nie spełnia v1.

Aby guard był wiarygodny, każda zmiana article-owned child data wykonywana z tego edytora (sources, topics, relations, media metadata) musi w tej samej operacji dotknąć/bumpnąć parent `ContentArticle.updated_at` albo równoważny edit token. Relation manager nie może zmienić istotnego child recordu „za plecami” wersji formularza.

Dla placement overlap dodatkowo obowiązuje transakcyjny row/advisory lock opisany w Data Model; stale-write guard nie zastępuje concurrency locka.

---

## 42. Draft leakage prevention

Filament:

- nie tworzy public URL zwykłym anchor do draft,
- preview action wyraźnie odseparowana,
- list view nie pokazuje public link dla unpublished jako działającego public URL.

---

## 43. Audit visibility

Na view/edit:

- created by / updated by jako `User` actor,
- author / reviewer jako osobne `ContentAuthor` identities,
- ostatnia akcja publish/review/archive wyprowadzona z istniejącego `AuditLog`,
- slug redirect history.

Nie dodajemy `published_by` / `reviewed_by` tylko na potrzeby UI. Pełny audit pozostaje w istniejącym read-only Audit Logs resource.

Audit metadata nie może zawierać pełnego `body_blocks`, leadu ani prywatnych notatek; panel ma pokazywać zmianę stanu/IDs, nie kopię treści.

Nie projektujemy osobnego panelu snapshotów wersji/diff/restore artykułu. Jest to świadomie poza zakresem.

---

## 44. Keyboard/editor UX

Edytor musi wspierać:

- normalne tab order,
- keyboard shortcuts podstawowe,
- brak focus traps,
- label dla controls.

Filament custom widgets nie mogą pogarszać accessibility.

---

## 45. Error handling

Validation:

- field-specific,
- po polsku,
- zachowuje wpisany content.

Publish service error:

- czytelna lista braków,
- bez generic „Something went wrong”, jeśli znamy problem domenowy.

Unexpected error:

- log/request id,
- bez ujawniania stack trace.

---

## 46. Admin performance

List page:

- eager load category/author/reviewer,
- counts tylko przez withCount,
- brak N+1.

Selects:

- preload dla małych słowników,
- searchable query dla dużych collections jak questions/legal units.

---

## 47. Question relation picker performance

Nie preloadujemy całej bazy pytań.

Search endpoint/query minimum 2–3 znaki lub external ID.

Result limit.

Pokazujemy kontekst, aby redaktor nie podpiął złego pytania.

---

## 48. Legal unit picker performance

Search po:

- label,
- title,
- act short title.

Result limit.

Nie preloadujemy wszystkich legal units.

---

## 49. Content source URL UX

Po wpisaniu URL:

- walidacja syntaktyczna,
- przycisk „Otwórz źródło”,
- nie fetchujemy arbitralnego URL server-side w v1, aby nie tworzyć SSRF surface.

Metadata source redaktor wpisuje ręcznie.

---

## 50. File uploads security

Uploads korzystają z istniejących allowlist/size rules.

Nie pozwalamy:

- SVG z niekontrolowanym skryptem, jeśli pipeline tego nie sanitizuje,
- HTML files,
- arbitrary executable content.

---

## 51. CMS Definition of Done

Panel newsroom v1 jest gotowy, gdy redaktor może:

- utworzyć draft,
- ustawić typ/kategorię/autora,
- wpisać lead i zbudować body z kontrolowanych bloków,
- ustawić pochodzenie i kontekst regulacyjny, jeśli dotyczy,
- dodać key points,
- dodać publiczne lub wewnętrzne źródła, w tym źródło bez URL, bez wycieku `is_publicly_cited=false`,
- powiązać pytania/legal,
- dodać hero, alt i focal point,
- zarządzać topicami,
- obsadzić stałe sloty strony głównej,
- podejrzeć bieżący lub przyszły stan całego `/aktualnosci`,
- zobaczyć SEO fallback,
- wysłać do review,
- podejrzeć wyłącznie jako zalogowany administrator bez publicznego cache,
- zaplanować,
- opublikować,
- poprawić,
- oznaczyć freshness,
- zarchiwizować,

a wszystkie publiczne przejścia statusu przechodzą przez serwis domenowy, `User` actor trafia do AuditLog, stale-write jest odrzucany i panel nie rozszerza dostępu poza istniejących administratorów.

---

## 52. Testy CMS

Feature/Livewire/Filament tests zależnie od obecnego test pattern:

- create draft,
- validation,
- key_points 0 albo 2–5 ordered plain-text items,
- correction action atomically updates correction_note + substantive timestamp + content,
- source repeater persistence + nullable URL + public/internal citation behavior,
- newsroom media upload MIME/size/dimensions/stable-path validation bez użycia question-specific upload service,
- body blocks validation/persistence,
- origin/regulatory context persistence,
- relation persistence,
- topic persistence,
- home placement overlap/fallback/dedupe,
- future home preview,
- publish blocked by missing fields,
- schedule validation,
- slug change redirect,
- admin-only panel/abilities; moderator/student/non-admin denied,
- User actor vs ContentAuthor author/reviewer identity,
- AuditLog without body/private notes payload,
- stale article edit rejected,
- concurrent placement overlap cannot be committed,
- category slug/deactivation guards,
- historical article-path reservation / same-article reclaim tests,
- ContentAuthor unpublish blocked while dependent public/indexable newsroom articles exist,
- topic slug/corpus guards,
- preview action admin-only + private,no-store.

E2E:

- admin login
- create article
- preview
- publish
- public verify.

---

## 53. Stan implementacji

Na 2026-09-16:

- TrafficSigns CMS daje wzorzec workflow/checklist,
- ContentAuthors resource istnieje,
- `NewsroomBodyContract` v1 i jego unit/security tests istnieją,
- `NewsroomMediaStorage` i jego unit regression istnieją jako N0-006 storage/validation foundation,
- N0-004 wybrało Builder + RichEditor TipTap JSON jako adapter, a N2-003 zmaterializowało go w `ContentArticleResource` przez `NewsroomBodyEditorAdapter`,
- backendowy `ContentArticlePublishingService` istnieje i implementuje audytowane workflow transitions oraz after-commit event boundary z N1-004,
- backendowe `NewsroomHomeCompositionService` i `NewsroomHomePlacementService` istnieją po N1-006; zapewniają composition/fallback/future-preview eligibility i concurrency-safe placement writes,
- custom Filament `NewsroomHomeComposer` nadal nie istnieje; N1-006 nie dostarcza UI, stale-write UX ani admin preview route,
- `ContentCategoryResource` istnieje: index/create/view/edit, article counts, active filter, `position` reorder oraz category invariants,
- N2-002 dodało podstawowy `ContentArticleResource` shell z index/create/view/edit, Form/Infolist/Table, search/filter setem oraz eager loadingiem category/author/reviewer,
- N2-004 dodało relationship source editor na istniejącym `ContentArticleSource`, reorder/status indicators, nullable-evidence URL handling oraz finalną source-policy validation w `ContentArticlePublishingService`,
- N2-005 dodało article-owned questions/legal/signs/topics editor i `NewsroomArticleRelationsEditorAdapter`; ordered pivots zachowują `sort_order`, topics celowo nie mają ręcznego rankingu,
- PR #48 zmaterializował N2-006 workflow/exposure action slice na Edit/View: review/schedule/publish/archive/withdraw/republish oraz featured/breaking delegują do `ContentArticlePublishingService`,
- create draft oraz draftowe zmiany type/sluga delegują do `ContentArticleSlugService`; `User` actor i `ContentAuthor` author/reviewer pozostają rozdzielone,
- ordinary Edit dla `publiclyVisible()` nadal nie zapisuje publicznych pól przez zwykły Save i pozwala w tej ścieżce tylko na osobny zapis `editorial_note`; publiczny payload zmienia wyłącznie jawny `Apply public update`,
- PR #50 dodał deterministyczny `_edit_token`, stale-write reject dla article/source/relation state oraz atomowy `Apply public update` z pełną service validation i allowlisted audytem,
- PR #52 dodał `ContentArticlePublicationChecklist`; formularz artykułu pokazuje read-only listę `OK` / `OSTRZEŻENIE` / `BLOKUJE`, a `ContentArticlePublishingService` deleguje do tej samej klasy review/publication/fresh-review assertions,
- warningi checklisty nie blokują publikacji, natomiast domain blockers pozostają autorytatywne po stronie backendu; dedykowany różny OG asset bez własnego alt pozostaje blockerem,
- `ContentTopicResource` i custom `NewsroomHomeComposer` nadal nie istnieją,
- N2-006 i N2-007 są DONE; N2-012 pozostaje PARTIAL wyłącznie dla stale-write UX/guard `NewsroomHomeComposer`; media/origin-regulatory UI i private preview także pozostają otwarte,
- publiczny renderer bloków nie istnieje.

---

## 54. Pozostałe zadania

- [ ] wdrożyć ContentTopicResource,
- [ ] wdrożyć public/reverse relation rendering w odpowiednim etapie N3/N4,
- [ ] wdrożyć NewsroomHomeComposer + future preview,
- [ ] wdrożyć hero/OG uploader korzystający z `NewsroomMediaStorage` i zapis verified metadata do `ContentArticle`,
- [ ] wdrożyć focal-point/crop UX; nie deklarować variantów bez fizycznie wygenerowanych plików,
- [ ] wdrożyć origin/regulatory fields,
- [x] N2-006: workflow/exposure actions + atomowy stale-safe `Apply public update` dla `ContentArticle`,
- [x] NEWSROOM-N2-007: wdrożyć computed publication checklist współdzielącą backend invariants,
- [ ] NEWSROOM-N2-008: wdrożyć admin-only private preview route/rendering z `private, no-store` i `noindex,nofollow`,
- [ ] wdrożyć stale-write guard dla `NewsroomHomeComposer` po materializacji N2-009; article stale-write jest już wdrożony,
- [ ] wdrożyć topic identity guards; category slug/delete/deactivation guards są już zmaterializowane przez N2-001,
- [ ] rozszerzać testy CMS wraz z kolejnymi taskami (Builder, workflow, stale-write, preview i HomeComposer).

---

## 55. Historia zmian

### 2026-09-16 — v0.17

- PR #52 zmergowano na `main@eb37034090e7233e72cd9452d12fe9876631440a`; exact-head CI #194: 992 passed / 19 114 assertions / 2 skipped, Pint 1017 files PASS, frontend build PASS oraz `newsroom-postgres` PASS,
- `ContentArticlePublicationChecklist` współdzieli twarde readiness assertions z `ContentArticlePublishingService`; Filament nie duplikuje reguł publish,
- `ContentArticleResource` pokazuje read-only computed checklistę z trzema severity: OK, warning i blocking,
- warningi nie zatrzymują publish, ale aktywna kategoria, publiczny autor, body/source/review/media/breaking invariants nadal są egzekwowane backendowo,
- doprecyzowano OG severity: brak dedykowanego OG assetu może być warningiem, ale dedykowany różny OG asset bez własnego alt jest blockerem zgodnie z domain contract,
- N2-007 jest DONE; następnym taskiem wykonawczym jest N2-008 Article preview.

### 2026-09-16 — v0.16

- PR #50 zmergowano na `main@570f884a89869ec44d24f57f0506f4444d20a7d2`; exact-head CI #190 przeszedł dla `quality` i `newsroom-postgres`,
- `ContentArticleResource` ma jawny `Apply public update` mode dla już publicznego rekordu; ordinary public Save nadal nie mutuje publicznych pól,
- `_edit_token` jest równoważnym loaded-state tokenem obejmującym article + sources + article-owned relations/topics i wykrywa same-second child changes,
- public update zapisuje aktualnie zmaterializowany public editor payload atomowo przez `ContentArticlePublishingService`, rollbackuje na validation failure i odświeża token po własnych workflow actions,
- N2-006 jest DONE; N2-012 pozostaje PARTIAL wyłącznie dla przyszłego `NewsroomHomeComposer` stale-write,
- następnym wykonawczym taskiem jest N2-007 Publication checklist.

### 2026-09-16 — v0.15

- PR #48 zmergowano na `main@88533b04d74a839c3bbccf86b707909ccdd235f8`; exact-head CI #176: `quality` PASS i `newsroom-postgres` PASS,
- Edit i View mają wspólny workflow action layer bez duplikowania transition logic z `ContentArticlePublishingService`,
- wdrożono review/draft, mark-reviewed, initial schedule, publish, needs-review, archive, fresh-review republish, withdraw/restore oraz featured/breaking exposure actions,
- withdraw wymaga reason; breaking wymaga published news + przyszłego expiry; featured jest ograniczone do scheduled/published,
- exposure mutations zapisują allowlisted AuditLog i aktualizują `public_state_changed_at` zgodnie z public-state semantics,
- ordinary `publiclyVisible()` Edit pozostaje zablokowany dla publicznych pól,
- `Apply public update` i stale-write guard pozostają NEWSROOM-N2-012, więc pełny N2-006 DoD nadal jest otwarty.

### 2026-09-16 — v0.14

- N2-005 zmaterializowało sekcję article-owned relations/topics w istniejącym `ContentArticleResource`, bez nowego resource dla targetów i bez migracji,
- questions/legal/signs są ordered Repeaterami z bounded search i zapisują kolejność do istniejącego pivot `sort_order`; topics pozostają searchable multi-selectem bez ręcznego rankingu,
- `NewsroomArticleRelationsEditorAdapter` waliduje duplicate/missing targets i relation-type allowlists przed sync,
- create/edit syncują tylko article-owned pivots; target Question/LegalUnit/TrafficSign content nie jest modyfikowany,
- ordinary public Edit nie może zmieniać relations/topics; relation sync bumpuje parent `updated_at`, ale loaded-token stale-write rejection pozostaje N2-012,
- publiczny renderer relacji nie jest oznaczony jako wdrożony,
- finalny exact-head gate PR #46: `quality` 972 passed / 18 940 assertions / 2 skipped, Pint 1011 files PASS, frontend build PASS (9.49 s); `newsroom-postgres` 7 passed / 89 assertions,
- następnym taskiem jest `NEWSROOM-N2-006` Workflow actions.

### 2026-09-16 — v0.13

- N2-004 zmaterializowało sekcję źródeł jako relationship Repeater w istniejącym `ContentArticleResource`, bez nowego source resource/modelu/migracji,
- `defaultItems(0)` zachowuje lekki draft flow; źródła są wymagane przez service boundary przy review/publish news, nie przy pierwszym zapisie,
- editor utrzymuje `sort_order`, source types v1, nullable interview/internal URL, bezpieczną akcję otwarcia HTTP(S) oraz jawne stany PRIMARY/OFFICIAL/PUBLIC/TYLKO WEWNĘTRZNE,
- ordinary Edit `publiclyVisible()` nie może mutować sources; source model bumpuje parent `updated_at`, ale właściwy stale-write loaded-token reject nadal nie istnieje,
- backend source policy jest finalnie walidowana przez `ContentArticlePublishingService`; osobny warning o braku primary source dla prawnego newsa pozostaje N2-007/N2-010,
- public source/citation renderer nadal pozostaje N3,
- finalny exact-head gate PR #44: `quality` 968 passed / 18 917 assertions / 2 skipped, Pint 1010 files PASS, frontend build PASS; `newsroom-postgres` 7 passed / 89 assertions,
- następnym taskiem jest `NEWSROOM-N2-005` Relations and topics editor.

### 2026-09-16 — v0.12

- N2-003 zmaterializowało kontrolowany article Builder/RichEditor w istniejącym `ContentArticleResource`, bez tworzenia drugiego CMS ani arbitrary-HTML body,
- `NewsroomBodyEditorAdapter` mapuje UI do `NewsroomBodyContract` v1, zachowuje canonical `key` niezależnie od efemerycznych UUID Filament Buildera i utrzymuje kolejność bloków,
- aktywne bloki v1 mają dedykowane pola formularza; domain pickery używają bounded searchable queries zamiast preloadu całych corpusów pytań/przepisów/znaków,
- RichEditor zapisuje TipTap JSON i przed persistence cały body przechodzi server-side `NewsroomBodyContract::normalize()`; embed pozostaje fail-closed,
- ordinary Edit `publiclyVisible()` nadal nie zapisuje body/public fields; upload/crop, sources, relations, workflow, stale-write i preview pozostają otwarte,
- finalny gate PR #42: `quality` 963 passed / 18 895 assertions / 2 skipped, Pint 1010 files PASS, frontend build PASS; `newsroom-postgres` PASS,
- następnym taskiem jest `NEWSROOM-N2-004` Sources editor.

### 2026-09-16 — v0.11

- N2-002 zmaterializowało `ContentArticleResource` shell z index/create/view/edit oraz rozdzielonymi Form/Infolist/Table,
- lista implementuje wymagane kolumny, search title/slug/lead, filtry redakcyjne i eager loading category/author/reviewer,
- create draft używa `ContentArticleSlugService`, a draftowe type/slug changes przechodzą przez jego `changeType()` / `changeSlug()`,
- dostęp nadal wynika wyłącznie z istniejącego admin panel gate; nie dodano newsroom RBAC,
- `User` actor AuditLog pozostaje niezależny od `ContentAuthor` author/reviewer,
- ordinary Save `publiclyVisible()` rekordu nie może zmienić publicznych pól; shell zachowuje osobny wewnętrzny zapis `editorial_note`,
- Builder/RichEditor, workflow actions, `Apply public update`, stale-write, media/sources/relations, preview i HomeComposer nadal pozostają otwarte,
- finalny gate PR #40: `quality` 951 passed / 18 857 assertions / 2 skipped, Pint 1008 files, frontend build PASS; `newsroom-postgres` 7 passed / 89 assertions.

### 2026-09-16 — v0.10

- wdrożono `ContentCategoryResource` jako pierwszy newsroom Filament resource,
- dostęp pozostaje admin-only przez istniejący `User::canAccessPanel()` contract; nie dodano newsroom RBAC,
- resource ma index/create/view/edit, trzy article counts, active filter i reorder po `position`,
- slug jest create-only w UI oraz immutable/model-validated w domenie,
- delete/deactivation guards są autorytatywne na modelu; edit page powierzchniowo zwraca błąd pola `is_active`, ale reactive toggle nie wykonuje relacyjnych zapytań,
- stale eager counts nie mogą ominąć guards; jest to objęte feature regression,
- `ContentArticleResource`, Builder/editor, `ContentTopicResource`, HomeComposer i preview nadal pozostają otwarte.

### 2026-09-16 — v0.9

- zsynchronizowano CMS current state z N1-006 bez oznaczania UI jako wdrożonego,
- odnotowano istniejące backendowe `NewsroomHomeCompositionService` i `NewsroomHomePlacementService`,
- `NewsroomHomeComposer`, stale-write UX i admin-only future-preview page pozostają N2 i nadal są otwarte.

### 2026-09-16 — v0.8

- N1-004 dostarczył backendowy `ContentArticlePublishingService`, AuditLog actor contract i after-commit workflow event,
- przyszłe N2 workflow actions mają wywoływać ten serwis zamiast implementować transition logic w Filament,
- UI checklist/confirmation, `Apply public update`, stale-write guard i newsroom resources nadal nie istnieją,
- withdrawn UI nadal musi komunikować przyszłe HTTP 410, ale sam publiczny controller/410 response pozostaje N3.

### 2026-09-16 — v0.7

- zamknięto N0-006 storage contract przed wdrożeniem article media UI,
- dodano `NewsroomMediaStorage` jako dedykowany newsroom foundation zamiast question-specific `AdminMediaUploadService`,
- actual stored bytes/MIME/dimensions oraz managed immutable path są walidowane backendowo,
- SVG/non-raster i niestabilne/unmanaged pathy są odrzucane, a public URL przechodzi przez `MediaUrlResolver`,
- nie oznaczono hero uploader endpointów, focal-point picker ani crop generation jako wdrożonych.

### 2026-09-16 — v0.6

- zamknięto decyzję N0-004: przyszły article editor używa Filament Builder jako adaptera do kanonicznego `NewsroomBodyContract`,
- rich text wybrano jako RichEditor TipTap JSON z zamrożonym toolbar contract, bez arbitrary HTML body,
- `embed` pozostaje wyłączony w v1 do czasu provider/sandbox/referrerpolicy/CSP gate,
- block/payload validation i XSS regression istnieją w warstwie kontraktu,
- nie oznaczono `ContentArticleResource`, Builder UI ani preview jako wdrożonych.

### 2026-09-16 — v0.5

- utrwalono admin-only V1: User jest aktorem auth/audytu, ContentAuthor publiczną tożsamością autora/reviewera,
- signed/shareable preview usunięto z v1 na rzecz authenticated admin-only + private,no-store,
- stale-write rejection awansowano z opcjonalnego warningu do gate'u v1,
- przy braku revisions zablokowano zwykły live Save publicznych pól i dodano jawny Apply public update contract,
- dodano high-friction Withdraw from public z obowiązkowym reason i bez bulk action,
- dodano category/topic public-identity guards i minimalny topic corpus baseline,
- ujednolicono wewnętrzne notatki do editorial_note oraz checklistę do body_blocks,
- audit UI opiera się na istniejącym AuditLog bez nowych published_by/reviewed_by pól,
- źródła wspierają nullable URL oraz jawne is_publicly_cited, aby prywatny evidence nie wyciekał publicznie,
- freshness overdue oddzielono od jawnego needs_review transition,
- slug service waliduje historyczne public paths, nie tylko current unique(slug),
- media uploader opisano zgodnie z kodem: resolver jest wspólny, ale istniejący upload service jest question-specific,
- dodano ContentAuthor unpublish guard dla zależnych publicznych artykułów,
- service-owned timestamps są read-only, a article-owned child writes muszą bumpować parent edit token dla stale-write guard.

### 2026-09-16 — v0.4

- usunięto ręczny canonical override z CMS v1,
- dodano licznik i warning długości headline bez sztucznego hard limitu,
- zablokowano zmianę route family po pierwszej publikacji,
- dodano hero caption i regułę seo_title vs H1,
- doprecyzowano, że article-question picker nie modyfikuje istniejącego question graphu.

### 2026-09-16 — v0.3

- dodano OG image alt i kontrolę stabilności publicznych URL-i obrazów SEO,
- doprecyzowano media warnings bez deklarowania nieistniejącej implementacji.

### 2026-09-15 — v0.2

- rozszerzono CMS o kontrolowany block editor zamiast jednego dowolnego body,
- dodano origin/regulatory context, topics oraz art direction obrazu,
- dodano NewsroomHomeComposer ze stałymi placements, fallbackami i future preview,
- rozdzielono featured od konkretnego placementu strony głównej,
- potwierdzono brak osobnego revision snapshot/diff/restore UI.

### 2026-09-15 — v0.1

- zdefiniowano newsroom CMS w Filament,
- wykorzystano istniejący wzorzec TrafficSigns Resource/Form/Table/Infolist,
- rozpisano pola, akcje, validation, checklistę, preview i performance,
- zablokowano bulk publish i SSRF-prone automatic source fetching w v1.
