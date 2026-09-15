# Newsroom Admin CMS / Filament Specification

## 1. Status

- Status: Proposed / implementation-ready CMS contract
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Powiązane:
  - [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md)
  - [NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md](./NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md)
- Data: 2026-09-15
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
2. Treść
3. Źródła
4. Powiązania
5. Media
6. SEO
7. Workflow i publikacja
8. Freshness / review
9. Notatki wewnętrzne
10. Checklista publikacyjna

---

## 11. Sekcja „Tożsamość i klasyfikacja”

Pola:

- type
- category_id
- title
- slug
- tags
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

## 12. Sekcja „Treść”

Pola:

- lead
- key_points
- body

### 12.1. Lead

Textarea:

- 2–4 zdania sugerowane,
- required przed review/publish.

### 12.2. Key points

Repeater:

- max 5,
- krótkie teksty,
- optional.

### 12.3. Body editor

Wymagania funkcjonalne:

- H2
- H3
- paragraphs
- bold
- italic
- ordered/unordered lists
- links
- blockquote
- tables tylko jeśli editor bezpiecznie wspiera
- controlled embeds później

Niedozwolone:

- arbitrary script
- arbitrary iframe
- inline event handlers
- dowolny style injection

Wybór konkretnego Filament editor component musi być zgodny z sanitization strategy.

---

## 13. Autosave

Nie jest wymagane w pierwszym PR.

Jeśli wdrażamy później:

- jawny status „Zapisano”,
- ochrona przed nadpisaniem równoległej edycji,
- nie publikować autosave.

---

## 14. Sekcja „Źródła”

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

## 15. Source warnings

Panel ostrzega:

- news bez żadnego źródła,
- news w kategorii Przepisy bez official/legislation source,
- dwa primary source nie są błędem globalnym, ale UI powinno pokazać stan,
- źródło bez title/url jest niekompletne.

Publish service ma finalną walidację, niezależnie od ostrzeżeń UI.

---

## 16. Sekcja „Powiązania”

Podsekcje:

### 16.1. Pytania

Searchable multi-select lub relation manager.

Pokazujemy:

- external_id,
- skrócony prompt,
- kategorie,
- aktywny/publiczny status.

Nie wybieramy po samym internal ID bez kontekstu.

### 16.2. Legal units

Search:

- act short title,
- label art./§,
- title/summary.

### 16.3. Traffic signs

Search:

- code
- name

Może być deferred do N3/N4.

---

## 17. Relation ordering

Powiązania publiczne mają sort_order.

Admin powinien umożliwiać reorder lub przynajmniej kolejność dodania.

---

## 18. Sekcja „Media”

Pola:

- hero image
- hero alt
- width/height metadata
- OG image
- image credit
- image license note

Wykorzystujemy istniejący media layer.

Nie przechowujemy raw binary w bazie.

---

## 19. Image validation

Przy upload/wyborze:

- format allowlist,
- size policy zgodna z media system,
- dimensions odczytywane automatycznie jeśli pipeline wspiera,
- alt wymagany przed publish jeśli hero exists.

Dodatkowe warning:

- szerokość < 1200 px dla materiału oznaczonego jako Discover-ready/featured, jeśli taki marker zostanie wdrożony.

Nie blokować wszystkich publikacji wyłącznie przez Discover recommendation.

---

## 20. Sekcja „SEO”

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

## 21. Robots control

Nie dajemy redaktorowi przypadkowego free-text robots bez zabezpieczeń.

Opcje kontrolowane:

- default
- noindex,follow
- noindex,nofollow

index,follow wynika z published/default policy.

---

## 22. Sekcja „Workflow i publikacja”

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

## 23. Preferred workflow actions

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

## 24. Publish action

Po kliknięciu:

1. ContentArticlePublishingService waliduje record,
2. jeśli braki -> nie publikuje,
3. UI pokazuje listę braków,
4. jeśli OK -> transakcja publish,
5. redirect/notification do view/edit,
6. event dispatch.

Filament resource nie duplikuje logiki publish.

---

## 25. Schedule action

Modal:

- date/time,
- Europe/Warsaw w UI,
- podgląd finalnej daty,
- confirmation.

Backend zapisuje jednoznaczny timestamp.

Nie pozwala scheduled_for <= now bez jawnej konwersji na „Publish now”.

---

## 26. Breaking action

Warunki:

- tylko published news,
- breaking_expires_at wymagane.

Modal:

- expiry,
- opcjonalny label/priority jeśli model później wspiera.

Default expiry może być np. kilka godzin, ale nie hardcodujemy bez uzgodnienia editorial policy.

---

## 27. Featured action

Może być toggle/action.

Jeśli featured slots zostaną dodane później, ten action może rozszerzyć się o pozycję.

V1:

- is_featured
- editorial_priority

---

## 28. Freshness section

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

## 29. Checklista publikacyjna w adminie

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

## 30. Severity checklist

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
- brak OG-specific image.

---

## 31. Preview action

Action otwiera:

- signed preview URL lub authenticated preview route,
- nowa karta opcjonalnie.

Preview banner zawiera:

- status,
- article id,
- planned publish time,
- link „Edytuj”.

---

## 32. View/Infolist

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

## 33. Table bulk actions

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

## 34. Categories resource

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

## 35. Tags UX

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

## 36. Author integration

Select author używa ContentAuthor.

W UI warto pokazać:

- name
- role/title
- active/public state jeśli model ma pole

Nie tworzymy autora ad hoc w article form bez pełnego profilu, chyba że Filament flow create-related jest bezpieczny.

---

## 37. Reviewer policy hints

Przy wybraniu kategorii/type admin może pokazać:

„Ten materiał wymaga review przed publikacją”

Policy logic pozostaje po stronie service/policy, nie tylko JS/Filament visibility.

---

## 38. Concurrent editing

V1 minimum:

- pokaż updated_at,
- ostrzeżenie jeśli rekord został zmieniony od otwarcia formularza, jeśli łatwe do wdrożenia.

Nie jest akceptowalne ciche nadpisywanie istotnego materiału przy rosnącej redakcji.

Można wdrożyć optimistic lock później po realnej potrzebie.

---

## 39. Draft leakage prevention

Filament:

- nie tworzy public URL zwykłym anchor do draft,
- preview action wyraźnie odseparowana,
- list view nie pokazuje public link dla unpublished jako działającego public URL.

---

## 40. Audit visibility

Na view/edit:

- created by
- updated by
- last publish actor
- last review actor
- slug change history opcjonalnie.

Pełny audit może pozostać w istniejącym Audit Logs resource.

---

## 41. Keyboard/editor UX

Edytor musi wspierać:

- normalne tab order,
- keyboard shortcuts podstawowe,
- brak focus traps,
- label dla controls.

Filament custom widgets nie mogą pogarszać accessibility.

---

## 42. Error handling

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

## 43. Admin performance

List page:

- eager load category/author/reviewer,
- counts tylko przez withCount,
- brak N+1.

Selects:

- preload dla małych słowników,
- searchable query dla dużych collections jak questions/legal units.

---

## 44. Question relation picker performance

Nie preloadujemy całej bazy pytań.

Search endpoint/query minimum 2–3 znaki lub external ID.

Result limit.

Pokazujemy kontekst, aby redaktor nie podpiął złego pytania.

---

## 45. Legal unit picker performance

Search po:

- label,
- title,
- act short title.

Result limit.

Nie preloadujemy wszystkich legal units.

---

## 46. Content source URL UX

Po wpisaniu URL:

- walidacja syntaktyczna,
- przycisk „Otwórz źródło”,
- nie fetchujemy arbitralnego URL server-side w v1, aby nie tworzyć SSRF surface.

Metadata source redaktor wpisuje ręcznie.

---

## 47. File uploads security

Uploads korzystają z istniejących allowlist/size rules.

Nie pozwalamy:

- SVG z niekontrolowanym skryptem, jeśli pipeline tego nie sanitizuje,
- HTML files,
- arbitrary executable content.

---

## 48. CMS Definition of Done

Panel newsroom v1 jest gotowy, gdy redaktor może:

- utworzyć draft,
- ustawić typ/kategorię/autora,
- wpisać lead/body,
- dodać źródła,
- powiązać pytania/legal,
- dodać hero i alt,
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

## 49. Testy CMS

Feature/Livewire/Filament tests zależnie od obecnego test pattern:

- create draft,
- validation,
- source repeater persistence,
- relation persistence,
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

## 50. Stan implementacji

Na 2026-09-15:

- TrafficSigns CMS daje wzorzec workflow/checklist,
- ContentAuthors resource istnieje,
- newsroom resources nie istnieją,
- article editor nie istnieje,
- preview nie istnieje.

---

## 51. Pozostałe zadania

- [ ] zatwierdzić editor component i sanitization,
- [ ] wdrożyć ContentArticleResource,
- [ ] wdrożyć Form/Table/Infolist,
- [ ] wdrożyć ContentCategoryResource,
- [ ] wdrożyć relations pickers,
- [ ] wdrożyć workflow actions,
- [ ] wdrożyć checklist computed state,
- [ ] wdrożyć preview,
- [ ] wdrożyć tests.

---

## 52. Historia zmian

### 2026-09-15 — v0.1

- zdefiniowano newsroom CMS w Filament,
- wykorzystano istniejący wzorzec TrafficSigns Resource/Form/Table/Infolist,
- rozpisano pola, akcje, validation, checklistę, preview i performance,
- zablokowano bulk publish i SSRF-prone automatic source fetching w v1.
