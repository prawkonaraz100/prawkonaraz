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

Optional później:

- preview action bez osobnej Filament page, jeśli signed preview jest prostszy.

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

updated_at desc lub published_at desc zależnie od filtra.

Dla standardowego panelu redakcyjnego preferujemy updated_at desc, aby ostatnia praca była na górze.

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

### 11.1. Title

- required
- max 255
- live counter opcjonalnie
- nie blokować tylko ze względu na „SEO ideal length”.

### 11.2. Slug

- generowany z title przy create,
- po ręcznej zmianie nie nadpisuje się sam po każdej edycji title,
- unique(ignoreRecord),
- ostrzeżenie przy zmianie opublikowanego sluga.

Zmiana opublikowanego sluga musi przejść przez ContentArticleSlugService.

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
- Allowlisted embed

### 13.2. Builder UX

Preferowany jest Filament Builder lub równoważny komponent, jeżeli po weryfikacji N0-004 spełni wymagania.

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

Wymagane funkcje:

- H2
- H3
- paragraphs
- bold
- italic
- ordered/unordered lists
- links

Blockquote ma osobny typ, jeśli zapewnia to lepszą kontrolę prezentacji.

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
- url
- published_at
- accessed_at
- is_primary
- is_official
- note
- sort_order

UI:

- akcja „Dodaj źródło”
- przycisk otwarcia URL
- validation URL
- badge primary/official

---

## 16. Source warnings

Panel ostrzega:

- news bez żadnego źródła,
- news w kategorii Przepisy bez official/legislation source,
- dwa primary source nie są błędem globalnym, ale UI powinno pokazać stan,
- źródło bez title/url jest niekompletne.

Publish service ma finalną walidację, niezależnie od ostrzeżeń UI.

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

Powiązania publiczne mają sort_order.

Admin powinien umożliwiać reorder lub przynajmniej kolejność dodania.

---

## 19. Sekcja „Media / art direction”

Pola:

- hero image
- hero alt
- width/height metadata
- focal point X/Y
- podgląd cropów lead / standard / compact
- OG image lub wygenerowany OG variant
- OG image alt
- image credit
- image license note

Wykorzystujemy istniejący media layer.

Focal point powinien być ustawiany wizualnie na obrazie, jeśli komponent na to pozwala, z fallbackiem do pól liczbowych/środka. Redaktor nie uploaduje ręcznie osobnych kopii dla każdej karty, jeśli system może wygenerować crop z tego samego źródła.

Nie przechowujemy raw binary w bazie.

---

## 20. Image validation

Przy upload/wyborze:

- format allowlist,
- size policy zgodna z media system,
- dimensions odczytywane automatycznie jeśli pipeline wspiera,
- alt wymagany przed publish jeśli hero exists.

Dodatkowe warning:

- szerokość < 1200 px dla materiału oznaczonego jako Discover-ready/featured, jeśli taki marker zostanie wdrożony,
- dedicated OG image bez alt,
- publiczny SEO image URL wymaga wygasającego podpisu/auth,
- crop usuwa główny subject.

Nie blokować wszystkich publikacji wyłącznie przez Discover recommendation. Natomiast obraz wskazany w publicznym OG/schema musi mieć stabilny publiczny URL.

---

## 21. Sekcja „SEO”

Pola:

- seo_title
- seo_description
- canonical_url
- robots

UI pokazuje fallback preview:

- final title,
- final description,
- canonical.

canonical_url advanced field powinno być zwinięte/oznaczone jako wyjątkowe. Większość artykułów używa self-canonical.

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

- workflow_status
- scheduled_for
- first_published_at readonly po pierwszym publish
- published_at
- is_featured
- editorial_priority
- is_breaking
- breaking_expires_at

Workflow zmieniamy przez actions/service, nie przez dowolny Select, jeśli przejście wymaga walidacji.

Można pokazać pole status jako read-only badge i osobne actions.

---

## 24. Preferred workflow actions

Record actions:

- Save draft
- Submit for review
- Return to draft
- Mark reviewed
- Schedule
- Publish now
- Mark needs review
- Archive
- Preview

Każda action:

- ma confirmation, jeśli jest destrukcyjna/publiczna,
- wywołuje application service,
- pokazuje błędy checklisty.

---

## 25. Publish action

Po kliknięciu:

1. ContentArticlePublishingService waliduje record,
2. jeśli braki -> nie publikuje,
3. UI pokazuje listę braków,
4. jeśli OK -> transakcja publish,
5. redirect/notification do view/edit,
6. event dispatch.

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

## 29. Freshness section

Pola:

- source_checked_at
- freshness_review_due_at
- last_substantive_update_at
- reviewed_at
- review notes

Status computed:

- fresh
- due soon
- overdue
- not scheduled

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
- body
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

- brak body,
- brak author,
- brak category,
- brak source dla news,
- wymagany reviewer niezatwierdzony,
- scheduled time invalid.

Warning:

- brak hero,
- brak manual SEO description,
- brak related questions,
- brak OG-specific image, jeśli hero daje poprawny fallback,
- brak dedykowanego OG alt, gdy dedykowany OG asset semantycznie różni się od hero.

---

## 32. Preview action

Action otwiera:

- signed preview URL lub authenticated preview route,
- nowa karta opcjonalnie.

Preview banner zawiera:

- status,
- article id,
- planned publish time,
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
- warning, jeśli artykuł jest archiwalny, draftem lub nie będzie publiczny w wybranym czasie.

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

Preview jest noindex i zabezpieczone jak zwykły preview artykułu.

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

Publicacja topicu powinna ostrzegać/blokować, jeśli:

- brak opisu,
- brak odpowiedniego corpus,
- featured article nie jest publiczny.

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

Nie pozwala delete kategorii z artykułami.

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

W UI warto pokazać:

- name
- role/title
- active/public state jeśli model ma pole

Nie tworzymy autora ad hoc w article form bez pełnego profilu, chyba że Filament flow create-related jest bezpieczny.

---

## 40. Reviewer policy hints

Przy wybraniu kategorii/type admin może pokazać:

„Ten materiał wymaga review przed publikacją”

Policy logic pozostaje po stronie service/policy, nie tylko JS/Filament visibility.

---

## 41. Concurrent editing

V1 minimum:

- pokaż updated_at,
- ostrzeżenie jeśli rekord został zmieniony od otwarcia formularza, jeśli łatwe do wdrożenia.

Nie jest akceptowalne ciche nadpisywanie istotnego materiału przy rosnącej redakcji.

Można wdrożyć optimistic lock później po realnej potrzebie.

---

## 42. Draft leakage prevention

Filament:

- nie tworzy public URL zwykłym anchor do draft,
- preview action wyraźnie odseparowana,
- list view nie pokazuje public link dla unpublished jako działającego public URL.

---

## 43. Audit visibility

Na view/edit:

- created by
- updated by
- last publish actor
- last review actor
- slug change history opcjonalnie.

Pełny audit może pozostać w istniejącym Audit Logs resource.

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
- dodać źródła,
- powiązać pytania/legal,
- dodać hero, alt i focal point,
- zarządzać topicami,
- obsadzić stałe sloty strony głównej,
- podejrzeć bieżący lub przyszły stan całego `/aktualnosci`,
- zobaczyć SEO fallback,
- wysłać do review,
- podejrzeć,
- zaplanować,
- opublikować,
- poprawić,
- oznaczyć freshness,
- zarchiwizować,

a wszystkie publiczne przejścia statusu przechodzą przez serwis domenowy.

---

## 52. Testy CMS

Feature/Livewire/Filament tests zależnie od obecnego test pattern:

- create draft,
- validation,
- source repeater persistence,
- body blocks validation/persistence,
- origin/regulatory context persistence,
- relation persistence,
- topic persistence,
- home placement overlap/fallback/dedupe,
- future home preview,
- publish blocked by missing fields,
- schedule validation,
- slug change redirect,
- permissions,
- preview action.

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
- newsroom resources nie istnieją,
- article editor nie istnieje,
- preview nie istnieje.

---

## 54. Pozostałe zadania

- [ ] zatwierdzić block editor component, serializację i sanitization,
- [ ] wdrożyć ContentArticleResource,
- [ ] wdrożyć Form/Table/Infolist,
- [ ] wdrożyć ContentCategoryResource,
- [ ] wdrożyć ContentTopicResource,
- [ ] wdrożyć NewsroomHomeComposer + future preview,
- [ ] wdrożyć focal-point/crop UX,
- [ ] wdrożyć origin/regulatory fields,
- [ ] wdrożyć relations pickers,
- [ ] wdrożyć workflow actions,
- [ ] wdrożyć checklist computed state,
- [ ] wdrożyć preview,
- [ ] wdrożyć tests.

---

## 55. Historia zmian

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
