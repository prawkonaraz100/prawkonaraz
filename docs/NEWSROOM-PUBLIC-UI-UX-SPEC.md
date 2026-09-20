# Newsroom Public UI/UX Specification

## 1. Status

- Status: Proposed / implementation-ready UX contract
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Powiązane:
  - [MENU-SYSTEM-REFERENCE.md](./MENU-SYSTEM-REFERENCE.md)
  - [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md)
  - [NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md](./NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md)
- Data: 2026-09-17
- Cel: zablokować główne decyzje UX przed implementacją, aby frontend nie powstawał metodą prób i cofek.

---

## 2. Założenie projektowe

PrawkoNaRaz ma zyskać pełnoprawny portal informacyjny o hierarchii znanej z dużych serwisów newsowych, ale:

- bez kopiowania ich layoutu 1:1,
- bez wizualnego chaosu,
- bez nadmiaru reklamowych slotów,
- bez dark patterns,
- bez sztucznego „breaking”,
- ze spójnym językiem wizualnym istniejącego produktu.

Kluczowa różnica PrawkoNaRaz:

newsroom jest połączony z pytaniami, przepisami, znakami i testami.

---

## 3. Źródła istniejącego UI

Newsroom korzysta z:

- resources/views/layouts/public-content.blade.php
- x-site.public-header / home-header zgodnie z decyzją implementacyjną
- x-site.public-footer
- app/Support/PublicNavigation.php
- istniejących auth drawerów na publicznych stronach Blade
- istniejącego media URL resolvera
- public-content.ts tylko dla funkcji wzbogacających

Nie budujemy drugiego systemu menu.

---

## 4. Rendering

Publiczne strony newsroomu:

- SSR/Blade,
- pełna treść w initial HTML,
- bez wymogu JS do odczytania artykułu,
- progressively enhanced.

Vue/Inertia nie jest domyślnym rendererem artykułów.

---

## 5. Design tokens

Newsroom nie wprowadza własnego równoległego brand systemu.

Przed N3 należy ustalić:

- finalny accent color,
- neutral palette,
- typography scale,
- border radius policy,
- shadow policy,
- spacing scale.

Do czasu tego audytu komponenty używają istniejących tokenów/CSS variables lub wspólnej warstwy app.css.

Nie hardcodujemy wielu nowych kolorów w każdym partialu.

---

## 6. Szerokość i grid

Istniejący public content shell ma max width 1440 px.

Newsroom home powinien pozostać w tym samym systemie.

Rekomendowany desktop grid:

- 12 kolumn,
- gap 20–24 px,
- lead story 7–8 kolumn,
- right rail 4–5 kolumn.

Tablet:

- 8 kolumn.

Mobile:

- 4 kolumn,
- praktycznie pojedyncza kolumna contentu.

---

## 7. Globalna struktura /aktualnosci

Kolejność:

1. Header globalny
2. Newsroom subnavigation / topic strip
3. Optional breaking strip
4. Lead zone
5. Latest stream
6. Category blocks
7. Guides/evergreen block
8. Product bridge
9. Footer

Nie wszystkie sekcje muszą być widoczne, jeśli nie mają wystarczającej liczby opublikowanych materiałów.

### 7.1. Editorial composition

Zawartość strony jest rozwiązywana przez stałe placements i fallbacki, a nie przez prosty sort po jednym `priority`.

Kod definiuje layout i sloty. Redaktor obsadza sloty w CMS.

Priorytet rozwiązywania:

1. aktywne ręczne placement wskazujące `activelyDistributed()` article,
2. fallback redakcyjny wyłącznie z `activelyDistributed()` na podstawie type/category/featured/priority,
3. brak kandydata -> krótszy moduł zamiast sztucznego placeholdera.

`needs_review` i `archived` mogą nadal mieć publiczny canonical detail URL, ale nie są kandydatami do lead/latest/category promotion.

### 7.2. Deduplikacja

Ten sam artykuł nie powinien pojawiać się wielokrotnie jako karta na tej samej stronie.

Resolver prowadzi exclusion set w kolejności:

- lead,
- secondary,
- latest,
- category blocks,
- guides.

Jeśli kolejna sekcja nie ma wystarczającej liczby unikalnych materiałów, renderuje mniej pozycji.

Breaking strip jest wyjątkiem: jako alert może wskazywać artykuł, który równocześnie jest leadem.

---

## 8. Newsroom subnavigation

Cel:

- szybkie wejście w główne sekcje redakcyjne.

Pozycje v1:

- Najnowsze
- Prawo jazdy
- Egzaminy
- Przepisy
- WORD
- Kierowcy
- OSK
- Poradniki

Zasady:

- desktop: horizontal nav,
- mobile: poziomy scroll bez ukrywania kluczowych pozycji za dropdownem,
- aktywna sekcja ma wyraźny state,
- semantyczny nav z aria-label.

---

## 9. Pasek „Ważne teraz”

Nie jest równoważny breaking.

Może zawierać 3–6 pozycji z kontrolowanych placementów `important_now`. W v1 targetem placementu jest artykuł; topic/dossier może wejść do tego stripu dopiero po rozszerzeniu kontraktu targetów.

Przykład:

~~~text
WAŻNE TERAZ
Zmiany w egzaminach | PKK | Nowe przepisy | Terminy WORD
~~~

Zasady:

- jedna linia desktop,
- horizontal scroll mobile,
- brak auto-scroll carousel,
- brak animowanego tickera.

---

## 10. Breaking strip

Renderowany tylko, jeśli istnieje aktywny breaking article.

Struktura:

- label „PILNE” lub neutralniejsze „WAŻNE” zgodnie z editorial policy,
- krótki tytuł,
- timestamp opcjonalny,
- link do artykułu.

Nie używamy migania, czerwonego pełnoekranowego alertu ani dźwięku.

---

## 11. Lead zone — desktop

Przykład:

~~~text
┌──────────────────────────────────────────────────────────────┐
│ lead story 8 col                      │ secondary 4 col       │
│                                       │ ───────────────────── │
│ [16:9 image]                          │ secondary story       │
│ CATEGORY                              │ ───────────────────── │
│ Duży tytuł                            │ secondary story       │
│ Krótki lead                           │ ───────────────────── │
│ Autor / czas                          │ secondary story       │
└──────────────────────────────────────────────────────────────┘
~~~

### 11.1. Lead story

Elementy:

- hero 16:9 lub 3:2,
- kategoria,
- H2/link,
- lead max 2–3 linie,
- czas publikacji,
- opcjonalny badge analysis/report.

Lead story nie jest H1 strony. H1 huba może być wizualnie dyskretny, np. „Aktualności”.

### 11.2. Secondary stories

2–4 materiały.

Każdy:

- miniatura opcjonalna zależnie od wariantu,
- kategoria,
- tytuł,
- timestamp.

---

## 12. Lead zone — mobile

Mobile nie kopiuje desktopowego gridu.

Kolejność:

1. lead image
2. lead category
3. lead title
4. lead text
5. metadata
6. secondary stories jako pionowa lista

Lead musi być czytelny bez poziomego przewijania całej strony.

---

## 13. Latest stream

Sekcja „Najnowsze” ma być chronologiczna po `first_published_at DESC`, z deterministycznym tie-breakerem. Ponowne publish/unarchive nie przesuwa starego materiału na początek listy; istotna aktualizacja może być oznaczona osobnym „Aktualizacja”, ale nie udaje nowej daty publikacji.

Row desktop:

- timestamp,
- opcjonalna kategoria,
- title,
- thumbnail opcjonalnie.

Mobile:

- timestamp nad lub obok kategorii,
- title,
- mała miniatura po prawej tylko jeśli nie ściska tekstu.

Nie wprowadzamy infinite scroll v1.

Paginacja:

- linkowa,
- SSR,
- crawlable.

---

## 14. Category block

Każdy blok:

- heading + link „Zobacz wszystkie”,
- 1 primary card,
- 2–4 secondary cards/list items.

Nie każdy dział musi mieć identyczny układ.

Warianty komponentowe mogą być 2–3, ale kontrolowane.

---

## 15. Guides block

Poradniki powinny wyglądać inaczej niż newsy:

- evergreen label,
- mniej nacisku na godzinę publikacji,
- mocniej widoczna wartość praktyczna.

Przykładowe karty:

- „Jak założyć PKK?”
- „Ile kosztuje prawo jazdy?”
- „Jak wygląda egzamin teoretyczny?”

---

## 16. „Najczęściej czytane”

Nie renderować przed wdrożeniem wiarygodnej metryki.

Po wdrożeniu:

- ranking 1–5,
- okres widoczny lub opisany w metodologii,
- nie mieszać z featured.

Placeholder „najczęściej czytane” z ręczną listą byłby mylący.

---

## 17. Product bridge na hubie

Cel:

- połączyć medium z produktem bez agresywnego sprzedażowego layoutu.

Wariant:

~~~text
SPRAWDŹ SIĘ
Czy zdałbyś teorię dzisiaj?
20 pytań z oficjalnej bazy
[Rozpocznij test]
~~~

Alternatywnie moduł może prowadzić do:

- bazy pytań,
- najtrudniejszych pytań,
- nauki.

Jeden główny product bridge na viewport/sekcję wystarczy.

---

## 18. Strona kategorii

URL zgodny z route decision.

Struktura:

1. breadcrumbs
2. H1 kategorii
3. krótki opis
4. opcjonalny featured
5. lista chronologiczna
6. paginacja
7. related categories
8. footer

Nie indeksujemy losowych kombinacji filtrów.

---

### 18.1. Breadcrumb contract

Newsroom article:

`Home → Aktualności → Primary category → Artykuł`

Guide:

`Home → Poradniki → Guide`

Primary category guide'a może być pokazana jako osobny link klasyfikacyjny, ale nie zmienia głównego breadcrumb/canonical hierarchy.

---

## 19. Strona artykułu — desktop

Rekomendowany shell:

- główna kolumna tekstu 720–800 px,
- opcjonalny rail 280–340 px dopiero po ustaleniu realnej zawartości,
- szeroki hero może wyjść poza prose width, ale nie poza content shell.

Kolejność:

1. breadcrumbs
2. kategoria
3. H1
4. lead
5. byline + provenance + datePublished/dateModified
6. hero + public credit
7. key points opcjonalnie
8. regulatory/exam context box, jeśli ma zastosowanie
9. body blocks
10. sources
11. correction note, jeśli istnieje
12. related legal/questions/signs
13. product bridge
14. author box
15. related articles

---

## 20. H1

Wymagania:

- jedno H1,
- bez sztucznego łamania słów,
- desktop target 42–56 px zależnie od długości,
- mobile 30–38 px,
- line-height umożliwiający długie polskie tytuły.

Nie ustawiamy sztywnej wysokości kontenera H1.

Dla newsów CMS może ostrzegać przy nadmiernie długim tytule, ale UI nie ucina H1 arbitralnie do historycznego limitu znaków. `news:title` bierze pełny widoczny title; wyszukiwarka może sama skrócić prezentację na urządzeniu.

---

## 21. Lead artykułu

Wizualnie wyraźniejszy od body, ale nie większy niż H1.

Desktop:

- 19–22 px,
- max width zgodny z prose.

Mobile:

- 18–20 px.

---

## 22. Byline, daty i pochodzenie materiału

Pokazuje:

- avatar opcjonalnie,
- nazwę autora jako link do /autorzy/{slug},
- datePublished,
- dla typu news: wyraźny czas publikacji obok daty,
- „Aktualizacja” + czas tylko gdy last_substantive_update_at ma znaczenie,
- pochodzenie materiału tylko wtedy, gdy wnosi informację dla czytelnika.

Widoczne daty/czasy muszą odpowiadać semantyce datePublished/dateModified w structured data. `effective_from` i daty wydarzeń są wizualnie oddzielone, aby crawler/czytelnik nie pomylił ich z datą publikacji.

Przykładowe publiczne etykiety:

- „Materiał własny”
- „Opracowanie na podstawie oficjalnych źródeł”
- „Analiza własna”
- „Na podstawie materiału agencyjnego” — wyłącznie gdy istnieje odpowiednia licencja.

Nie pokazujemy technicznego updated_at użytkownikowi ani wewnętrznych notatek redakcyjnych.

---

## 23. Hero i art direction

Wymagania:

- width/height attributes,
- alt,
- opcjonalny caption renderowany jako semantyczny `<figcaption>`,
- credit jeśli wymagany,
- focal point,
- eager/fetchpriority high tylko jeśli hero jest LCP,
- responsive srcset, jeśli istniejący pipeline to wspiera,
- nie rozciągać małego assetu.

Aspect ratios preferowane:

- 16:9 — lead/article hero,
- 4:3 — wybrane standard cards,
- 1:1 lub zbliżony — compact cards, jeśli projekt tego wymaga,
- 1.91:1 / 1200x630 — OG, jeśli generujemy dedykowany wariant.

Crop powinien respektować focal point. Dla tego samego source assetu nie wymagamy ręcznego uploadowania osobnego pliku do każdej karty.

Caption opisuje kontekst/znaczenie obrazu dla czytelnika; alt pozostaje tekstem alternatywnym, a credit informacją o autorstwie/licencji. Nie łączymy tych trzech pól w jeden tekst.

---

## 24. Key points / „W skrócie”

Komponent:

- 2–5 punktów,
- semantyczna lista,
- tło subtelne,
- bez accordion dla kluczowych informacji.

Nie generować automatycznie z pierwszych zdań.

---

## 25. Regulatory / exam context box

Dla materiałów, w których ma zastosowanie, publiczna strona może pokazać uporządkowany box:

- Status: projekt / konsultacje / przyjęte / obowiązuje
- Co się zmienia?
- Od kiedy?
- Kogo dotyczy?
- Co to oznacza na egzaminie?

Box korzysta z pól strukturalnych artykułu, nie z automatycznego streszczenia body.

Nie renderujemy pustych wierszy.

---

## 26. Biblioteka bloków body

Body artykułu jest sekwencją kontrolowanych komponentów.

### 26.1. Rich text

Renderuje:

- p
- H2/H3
- listy
- links
- inline emphasis

### 26.2. Image

- asset
- alt
- caption
- credit
- dimensions
- focal point/crop policy

### 26.3. Quote

- cytat
- attribution
- source link opcjonalnie

### 26.4. Table

- caption
- semantic headers
- responsive overflow

### 26.5. Context/callout

Kontrolowane warianty:

- Dlaczego to ważne?
- Co się zmienia?
- Uwaga
- Metodologia

### 26.6. Domain blocks

- Related article
- Legal reference
- Question group
- Traffic sign group
- Product CTA

Te komponenty pobierają publiczne dane wskazanego rekordu; nie są ręcznie skopiowanymi kartami HTML.

### 26.7. Embed

Tylko allowlisted providers i bezpieczny wrapper responsywny.

Brak arbitralnego iframe/HTML.

---

---

## 27. Body typography

Wymagania:

- prose line length około 65–80 znaków,
- base 18 px desktop,
- co najmniej 17 px mobile,
- line-height około 1.65–1.8,
- H2/H3 wyraźna hierarchia,
- listy z wystarczającym spacingiem,
- tabele responsywne.

Nie używamy font-size 14–15 px dla głównego artykułu.

---

## 28. H2/H3

H2:

- główne sekcje,
- nie tylko dla SEO.

H3:

- podsekcje.

Zakaz:

- przeskakiwania H1 -> H4 z powodów wizualnych.

---

## 29. Cytat

Blockquote:

- wyraźny, ale nie dekoracyjny do przesady,
- cytat + attribution,
- nie mieszać z calloutem redakcyjnym.

---

## 30. Tabele

Dla opłat/statystyk:

- semantic table,
- caption,
- headers,
- mobile overflow-x jako fallback,
- lepiej stacked representation tylko jeśli zachowuje relacje danych.

Tabel nie renderujemy jako obraz.

---

## 31. Sources block

Na końcu treści lub przed related modules.

Wygląd:

- heading „Źródła”,
- numerowana lub zwykła lista,
- tylko rekordy `is_publicly_cited=true`,
- publisher/title,
- link tylko jeśli source URL istnieje,
- bez-URL interview/direct citation może być pokazana jako tekst,
- opcjonalna data.

`is_publicly_cited=false` oraz wewnętrzne `note` nigdy nie są renderowane. Nie ukrywamy publicznych źródeł w małym szarym tekście.

`image_license_note` jest polem backoffice i nigdy nie jest renderowane publicznie; publiczny hero może pokazać wyłącznie `image_credit`.

---

## 32. Correction note

Jeśli istotna korekta:

- widoczna,
- blisko metadanych albo na końcu z anchor,
- zawiera datę i krótki opis.

Nie stylizować jak błąd systemu.

---

## 33. Related questions

Komponent powinien wykorzystywać istniejące publiczne dane pytania.

Wariant:

~~~text
SPRAWDŹ, CZY TO UMIESZ
[miniatura] Pytanie ...
[miniatura] Pytanie ...
[Zobacz wszystkie powiązane pytania]
~~~

Maksymalnie 3–5 pozycji na artykule v1.

---

## 34. Related legal content

Dla artykułów prawnych:

- nazwa przepisu/tematu,
- krótki public note,
- link do /przepisy/...

Nie kopiujemy pełnego official excerpt do news article.

---

## 35. Related signs

Karta:

- kod znaku,
- obraz znaku,
- nazwa,
- link.

Nie pokazujemy, jeśli relacja jest tylko luźna.

---

## 36. Product CTA na artykule

CTA wynika z typu.

News o egzaminie:
- „Sprawdź się w teście”.

News o przepisie:
- „Zobacz pytania z tego zagadnienia”.

Guide:
- „Przejdź do nauki / testu”.

CTA nie może przykrywać treści sticky popupem v1.

---

## 37. Author box

Elementy:

- zdjęcie,
- imię/nazwisko,
- rola/opis,
- link „Więcej materiałów autora”.

Dane pochodzą z ContentAuthor.

---

## 38. Related articles

2–4 materiały.

Priorytet:

1. ręcznie lub semantycznie związane,
2. ta sama kategoria/tag,
3. nie pokazuj aktualnego artykułu.

---

## 39. Share controls

V1 optional.

Jeśli wdrażamy:

- kopiuj link,
- system share na mobile, jeśli wspierane,
- bez śledzących skryptów third-party przy samym renderze ikon.

---

## 40. Reading time

Nie jest wymagane.

Jeśli wdrożone:

- wyliczane deterministycznie,
- traktowane jako przybliżenie,
- nie zapisujemy ręcznie bez potrzeby.

---

## 41. Ads

Poza v1.

Layout nie powinien jednak uniemożliwiać późniejszego dodania jawnie oznaczonych slotów bez przebudowy całej struktury.

Nie projektujemy pustych reklamowych dziur w v1.

---

## 42. Topic / dossier page

Route v1:

`/aktualnosci/temat/{topicSlug}`

Topic nie jest stroną zwykłego taga.

Struktura:

1. breadcrumbs
2. H1 topicu
3. opis redakcyjny
4. featured article opcjonalnie
5. lista materiałów topicu
6. powiązane przepisy/pytania, jeśli redakcyjnie uzasadnione
7. paginacja przy większym corpus
8. footer

Topic draft nie ma publicznego URL indeksowalnego.

Jeśli corpus jest zbyt mały, topic nie powinien być publikowany tylko dla SEO.

### 42.1. Aktualny stan implementacji N4-007

Na `main@a97373003c5a249a28761df15342f19e656c50c5` istnieje publiczny SSR `newsroom.topic`:

- istniejący route `/aktualnosci/temat/{topicSlug}` jest podłączony do `NewsroomTopicController`,
- gate=false, draft, future i unknown topic nie renderują publicznego dossier; historyczny archived topic zwraca neutralne 410 + noindex,
- header pokazuje label „Temat”, H1 i własny opis redakcyjny topicu,
- eligible `featured_article_id`, jeśli nadal należy do `activelyDistributed()+indexable()` corpus, jest pojedynczym leadem na pierwszej stronie i nie jest duplikowany w listingu,
- pozostały corpus może łączyć newsroom articles i guide, zachowuje canonical route family każdego artykułu i chronologię `first_published_at DESC, id DESC`,
- listing i paginacja są crawlable SSR; page N ma self-canonical `?page=N`,
- późniejszy spadek corpus poniżej publish baseline nie ukrywa automatycznie wcześniej opublikowanego topicu; decyzja redakcyjna o archive pozostaje jawna,
- nie utworzono automatycznych tag pages ani drugiego topic UI systemu,
- moduły powiązanych przepisów/pytań z punktu 6 nie zostały dodane przez N4-007; wymagają jawnych relacji w N4-008.

---

## 43. Empty states

### 43.1. Brak materiałów kategorii

Nie renderujemy pustej sekcji na homepage.

Na category page:

- komunikat „Nie ma jeszcze opublikowanych materiałów”,
- link do aktualności,
- noindex rozważyć, jeśli strona nie ma unikalnej wartości.

### 43.2. Brak hero

Karta ma wariant bez obrazu.
Nie używamy przypadkowego placeholder photo.

---

### 43.1. Needs-review transparency

Jeśli artykuł ma workflow `needs_review` i nadal zwraca 200:

- nad treścią/byline pokazujemy dyskretny, ale czytelny komunikat „Materiał jest w trakcie ponownej weryfikacji”,
- pokazujemy datę ostatniej merytorycznej aktualizacji / „stan informacji”, jeśli dostępna,
- nie oznaczamy go jako breaking/featured/current,
- jeśli pozostaje indexable, musi zachować crawlable inbound link (fallback przez profil autora),
- banner nie zmienia `dateModified` sam z siebie; public-state change może zmienić sitemap lastmod.

---

## 44. Error and archive states

Archived article, jeśli był wcześniej opublikowany:

- canonical detail URL pozostaje 200,
- nie pojawia się w aktywnych listingach,
- może pokazać dyskretną informację „Materiał archiwalny”, jeśli pomaga uniknąć wrażenia aktualności,
- data publikacji/aktualizacji pozostaje widoczna,
- renderer nie może przedstawiać archiwalnego materiału jako breaking/current tylko dlatego, że stary body używa czasu teraźniejszego.

Withdrawn article:

- standardowa publiczna 410/Gone surface,
- bez renderowania treści artykułu/body/source,
- bez related/product modules z wycofanego materiału,
- może zawierać neutralny link do /aktualnosci,
- bez automatycznego przekierowania do homepage.

404 article:

- standardowa publiczna 404,
- link do /aktualnosci,
- opcjonalnie najnowsze materiały.

Nie redirectujemy każdego 404 do homepage.

500:

- bez wycieku stack trace,
- standardowe public error handling.

---

## 45. Preview UI

Preview:

- dostępne wyłącznie w authenticated admin flow,
- pasek u góry „PODGLĄD — materiał nieopublikowany”,
- informacja o statusie,
- link do edycji dla zalogowanego admina,
- noindex,nofollow,
- `Cache-Control: private, no-store`,
- brak public analytics article-view.

Preview nie może być pomylony z produkcyjną stroną przez redaktora ani zostać udostępniony jako publiczny signed link w v1.

---

## 46. Scheduled preview

Pokazuje:

- planowaną datę publikacji,
- status scheduled,
- finalny layout.

---

## 47. Accessibility

Minimum:

- WCAG 2.2 AA jako target,
- keyboard navigation,
- visible focus,
- semantic nav/main/article/aside/footer,
- alt,
- contrast,
- no color-only states,
- skip link,
- poprawne labels,
- reduced motion.

### Stan implementacji NEWSROOM-N6-012 przed merge

PR #152 materializuje repo-level gate bez zmiany design systemu ani architektury publicznych stron:

- wspólny `layouts.public-content` dodaje skip link `Przejdź do treści` do `main#main-content`,
- service-menu desktop/mobile ma jawny keyboard focus outline,
- wielokrotne article complementary landmarks mają dostępne nazwy,
- article/home/category/topic/guides reużywają shared Playwright accessibility checker,
- checker obejmuje landmark naming, H1/heading order, alt, accessible names, labels, error association, keyboard focus i reduced-motion behavior,
- Browser Smoke #108 przeszedł dla article/home/category/topic/guides oraz istniejących semantic-links/golden-path jobs,
- manual screenshot review wykonano dla 390 i 1024 px; focus menu jest widoczny, a reprezentatywne pary kontrastu mają: 17.85:1, 4.76:1, 8.63:1, 10.86:1 i 4.55:1,
- exact-head implementation CI #538 na `23eea2eec14fc5396ce40bd1831112cf47572450` ma pełny PASS: 1151 / 20 326 / 2 skipped, PostgreSQL PASS, Pint 1103 files PASS, frontend PASS.

To jest historyczny pre-merge evidence snapshot. Na tym etapie pełny status DONE wymagał jeszcze finalnego docs-sync CI, merge i post-merge CI; nie utożsamiamy Browser Smoke ani reprezentatywnego contrast review z formalną zewnętrzną certyfikacją WCAG.

### Finalny stan NEWSROOM-N6-012

- finalny PR HEAD `14c3e9a1840e9d593e28533f36ccb8003aa9d686` wymaga dla `nav` i wielokrotnych `aside` author-provided `aria-label` / `aria-labelledby`, dzięki czemu zwykły tekst landmarku nie maskuje braku dostępnej nazwy,
- CI #542 ma pełny PASS; Browser Smoke #112 ma PASS dla article/home/category/topic/guides/semantic-links/golden-path, a Enterprise SEO Production Validation #11 również ma PASS,
- PR #152 zmergowano jako `main@0f3645475a2a7c72c69cac80388891d2a9ed2a72`,
- post-merge CI #543 ma pełny PASS dla `newsroom-postgres`, backend suite, Pint i frontend build,
- NEWSROOM-N6-012 jest DONE jako repo-level accessibility regression gate; WCAG 2.2 AA pozostaje targetem i nie jest tu deklarowane jako zewnętrzna certyfikacja całego serwisu.

---

## 48. Link targets

Wewnętrzne:

- normalne linki bez target=_blank.

Źródła zewnętrzne:

- decyzja UX może pozostawić ten sam tab lub nowy; jeśli nowy, bezpieczny rel i dostępna informacja dla screen readera, jeśli potrzebna.

Nie stosujemy target=_blank mechanicznie dla każdego linku.

---

## 49. Mobile touch targets

Minimum praktyczne:

- 44x44 px dla controls,
- wystarczający spacing między nav links,
- pagination nie jako małe cyfry bez paddingu.

---

## 50. Sticky behavior

Global header może zachować istniejącą politykę.

Nie wdrażamy sticky sidebar/CTA w N3, jeśli nie ma danych, że pomaga.

---

## 51. Performance budgets UI

Newsroom page nie powinien dołączać dużych bibliotek tylko dla prostych interakcji.

Zasady:

- SSR,
- minimal JS,
- lazy media below fold,
- brak autoplay,
- brak ciężkich carousel libraries.

Docelowe metryki są w SEO/Observability spec.

---

## 52. CSS architecture

Preferowane:

- istniejący Tailwind/app.css pattern,
- komponentowe klasy/partials,
- wspólne tokeny.

Nie dodajemy inline style bloków per każdy article partial, jeśli można utrzymać styl w jednym miejscu.

---

## 53. Blade component map

Rekomendowane komponenty/partials:

- newsroom.section-nav
- newsroom.breaking-strip
- newsroom.story-card
- newsroom.story-list-item
- newsroom.lead-story
- newsroom.category-section
- newsroom.article-byline
- newsroom.article-provenance
- newsroom.key-points
- newsroom.regulatory-context
- newsroom.block-rich-text
- newsroom.block-image
- newsroom.block-quote
- newsroom.block-table
- newsroom.block-context
- newsroom.sources
- newsroom.related-questions
- newsroom.related-legal
- newsroom.related-signs
- newsroom.product-bridge
- newsroom.author-box
- newsroom.pagination

Komponent nie powinien mieć dziesiątek wariantów sterowanych stringami.

---

## 54. Karty — warianty v1

Dozwolone:

- lead
- standard
- compact
- horizontal

Wystarczy.

Nie tworzymy osobnego komponentu dla każdej sekcji homepage.

---

## 55. Image behavior w kartach

- lead: 16:9
- standard: 16:9 lub 4:3 zgodnie z finalnym systemem
- compact: opcjonalny square/4:3 thumbnail
- crop respektuje zapisany focal point
- object-fit cover tylko dla zdjęć, które można kadrować
- dla znaków drogowych nie używać agresywnego cover crop

---

## 56. Typ badges

Dozwolone:

- ANALIZA
- PORADNIK
- RAPORT
- PILNE/WAŻNE zgodnie z policy

Nie pokazujemy badge NEWS przy każdym newsie, jeśli kategoria już daje kontekst.

---

## 57. Responsive breakpoints

Preferować obecne breakpoints Tailwind.

Projekt sprawdzić minimum:

- 360
- 390
- 430
- 768
- 1024
- 1280
- 1440+

Nie projektujemy tylko pod 1920 desktop.

---

## 58. Header integration

PublicNavigation jest źródłem linków.

Implementacja newsroomu nie może:

- wstawić osobnego logo headera,
- skopiować menu do newsroomowego Blade,
- zduplikować auth controls.

Newsroom subnav jest drugim poziomem, a nie alternatywnym globalnym menu.

---

## 59. Footer integration

Używać istniejącego public footer.

Można dodać linki newsroomowe przez PublicFooter/PublicNavigation po decyzji menu, nie w hardcoded HTML konkretnej strony.

---

## 60. SEO content visibility

Główna treść:

- nie w accordion,
- nie po client-side fetch,
- nie po click „czytaj dalej”.

Pagination links są prawdziwymi href.

---

## 61. Loading states

Public SSR pages nie wymagają skeletonów initial load.

Jeśli przyszłe dynamic modules fetchują dane:

- progressive enhancement,
- skeleton tylko tam, gdzie faktycznie jest async.

---

## 62. Future audio / AI controls

Architektura UI rezerwuje logiczne miejsce pod przyszłe funkcje, ale v1 ich nie renderuje.

Przyszłe controls mogą pojawić się w okolicy byline/lead:

- Odsłuchaj artykuł
- Skróć artykuł
- Zapytaj o ten artykuł

Warunki przed uruchomieniem:

- funkcja ma gotowe dane/derivative po stronie backendu,
- stan loading/error jest zaprojektowany,
- AI nie tworzy alternatywnego indeksowalnego URL z duplikatem artykułu,
- odpowiedź AI jasno wynika z treści artykułu i/lub jawnych źródeł,
- brak funkcji nie zostawia pustego miejsca w layoutcie.

---

## 63. Analytics hooks

NEWSROOM-N5-004 jest wdrożone bez zmiany wizualnego layoutu. Publiczne SSR surface’y wystawiają semantyczne hooki:

- istniejące `data-analytics-module` pozostaje markerem sekcji/paginacji,
- tracking cards/links używa `data-newsroom-analytics-module`, `data-newsroom-analytics-position`, `data-newsroom-analytics-event`,
- stabilny kontekst kart używa `data-article-id`, `data-article-url`, `data-article-type`, `data-category-slug`,
- article detail wystawia `data-newsroom-analytics-article` jako page context.

Logika nie jest duplikowana w Blade partialach: jeden `resources/js/public/newsroomAnalytics.ts`, inicjalizowany przez `public-content.ts`, deleguje click tracking i reużywa istniejący `trackAnalyticsEvent`/GA consent layer. Brak JS nie blokuje czytania, routingu ani linków.

Do analytics nie przenosimy body, tytułu, autora ani source metadata. `destination_path` jest pathname. Optional scroll depth nie został wdrożony.

---

## 64. Wireframe /aktualnosci desktop

~~~text
┌──────────────────────────────────────────────────────────────────────────┐
│ GLOBAL HEADER                                                            │
├──────────────────────────────────────────────────────────────────────────┤
│ Najnowsze | Prawo jazdy | Egzaminy | Przepisy | WORD | Kierowcy | OSK │
├──────────────────────────────────────────────────────────────────────────┤
│ WAŻNE TERAZ: temat | temat | temat                                       │
├──────────────────────────────────────────────────────────────────────────┤
│ [LEAD IMAGE.................] | secondary                               │
│ KATEGORIA                    | secondary                                 │
│ GŁÓWNY TYTUŁ                 | secondary                                 │
│ lead                         |                                           │
├──────────────────────────────────────────────────────────────────────────┤
│ NAJNOWSZE                                                                │
│ 22:10  tytuł                                                        img  │
│ 21:45  tytuł                                                        img  │
│ 20:30  tytuł                                                        img  │
├──────────────────────────────────────────────────────────────────────────┤
│ PRZEPISY                                      Zobacz wszystkie →          │
│ primary card | card | card                                                │
├──────────────────────────────────────────────────────────────────────────┤
│ EGZAMINY I WORD                                                           │
│ primary card              | list                                          │
├──────────────────────────────────────────────────────────────────────────┤
│ PORADNIKI                                                                 │
│ card | card | card                                                        │
├──────────────────────────────────────────────────────────────────────────┤
│ SPRAWDŹ SIĘ                                                               │
├──────────────────────────────────────────────────────────────────────────┤
│ FOOTER                                                                    │
└──────────────────────────────────────────────────────────────────────────┘
~~~

---

## 65. Wireframe article mobile

~~~text
[GLOBAL HEADER]
[breadcrumbs]

PRZEPISY

Duży tytuł artykułu,
który może mieć kilka linii

Lead tekstowy 2–4 zdania.

Autor
15 września 2026, 21:30
Aktualizacja: 22:10

[HERO 16:9]
credit

[W SKRÓCIE]
• ...
• ...
• ...

[STATUS / OD KIEDY / KOGO DOTYCZY]
jeśli dotyczy

[block: rich text]
[block: context]
[block: image]
[block: legal reference]
[block: question group]

ŹRÓDŁA
1. ...

SPRAWDŹ SIĘ
[powiązane pytania]

AUTOR
[box]

CZYTAJ TAKŻE
[stories]

[FOOTER]
~~~

---

## 66. UI Definition of Done

Frontend newsroom v1 jest UI-complete, gdy:

- /aktualnosci działa desktop/mobile,
- category page działa z paginacją,
- article page działa dla wszystkich typów,
- brak hero ma poprawny wariant,
- long title nie rozwala layoutu,
- breadcrumbs poprawne,
- source block czytelny i nie ujawnia internal evidence/notes,
- hero caption/alt/credit mają rozdzielone semantyczne role,
- guide i newsroom article mają właściwy, różny breadcrumb path,
- news ma widoczną datę i czas publikacji przy byline,
- author link prowadzi do publicznego ProfilePage,
- related/product modules działają,
- homepage placements respektują fallback i deduplikację,
- body blocks renderują się spójnie desktop/mobile,
- regulatory context nie pokazuje pustych danych,
- cropy respektują focal point,
- topic page działa dla opublikowanego topicu,
- focus/keyboard działa,
- 360/390/430 nie mają horizontal overflow,
- obrazy mają dimensions,
- JS nie jest wymagany do czytania,
- header/footer są wspólne z resztą serwisu.

---

## 67. Stan implementacji

Na 2026-09-18 po NEWSROOM-N4-008, zweryfikowanym na `main@3d7ac8ab8a3ed1c299cb0cdd4cb1ef8ac6b53f78`:

- `/aktualnosci` używa istniejącego `NewsroomPlaceholderController::news()` jako rollout switch: gate=false zachowuje pre-launch `MarketingPlaceholder.vue` 200 + `X-Robots-Tag: noindex, follow`, a gate=true renderuje SSR `newsroom.home`; od N4-004 `/poradniki` używa istniejącego `NewsroomPlaceholderController::guides()` jako analogicznego rollout switcha: gate=false zachowuje placeholder 200 + noindex, gate=true renderuje SSR `newsroom.guides`,
- category route `/aktualnosci/kategoria/{categorySlug}` jest od N4-003 publicznym SSR surface przy gate=true; topic route `/aktualnosci/temat/{topicSlug}` jest publicznym SSR dossier od N4-007; od N5-003 `/aktualnosci/feed.xml` jest publicznym Atom feedem przy gate=true i 404 przy gate=false,
- detail routes `/aktualnosci/{articleSlug}` i `/poradniki/{articleSlug}` są podłączone do `ContentArticleController`; publicznie widoczny rekord renderuje `newsroom.article`, withdrawn historyczny rekord otrzymuje neutralną 410 surface, a hidden/not-found 404,
- 404/410 article surfaces używają `noindex,follow` i `X-Robots-Tag: noindex, follow` bez renderowania body/source/product modules,
- publiczny article renderer reużywa istniejący `public-content` layout, header/footer oraz gotowe N3-001 catalog, N3-002 SEO metadata i N3-003 schema graph; canonical/OG/article dates i JSON-LD są obecne w initial HTML,
- `NewsroomArticlePresentationService` materializuje route-family breadcrumbs, category kicker, H1, lead, byline, provenance, widoczne publication/update dates, key points, hero, regulatory/exam context, public sources, correction note, author box i transparentne `needs_review`/archived states,
- `NewsroomArticleBodyRenderer` renderuje publicznie `rich_text`, `image`, `quote`, `table`, `context`, public-safe `related_article` oraz po N3-005 jawnie rozwiązane bloki `legal_reference`, `question_group`, `traffic_sign_group` i `product_cta`,
- `NewsroomArticleProductBridgeService` traktuje identyfikator w body jako niewystarczający sam w sobie: question/legal/sign target musi również istnieć w article-owned pivot i spełniać istniejący public eligibility contract,
- question group używa istniejącego `PublicQuestionCatalogService` i pokazuje maksymalnie 5 publicznych pytań; inactive/nonpublic oraz niepowiązane pytania są pomijane,
- legal reference pokazuje wyłącznie zweryfikowaną jednostkę z verified aktem i opublikowaną stroną przepisu w opublikowanym topicu; private note i pełny `official_excerpt` nie trafiają do publicznego payloadu,
- traffic sign group pokazuje tylko opublikowane znaki z opublikowanym autorem i kategorią; luźny pivot `relation_type=related` jest fail-closed zgodnie z sekcją 35, a publiczny bridge dopuszcza relacje bezpośrednie/przykładowe wynikające z istniejącego kontraktu,
- contextual CTA reużywa istniejące trasy produktu: publiczny test, hub oficjalnej bazy pytań i naukę; N3-005 nie dodaje sticky popupu ani nowego systemu routingu,
- tylko `is_publicly_cited=true` sources mogą wejść do presentation; private source evidence/note oraz `image_license_note` nie są publicznym payloadem,
- hero i image blocks zachowują dimensions/alt/caption/credit oraz focal-point-aware `object-position`; N3-005 nie deklaruje nieistniejących fizycznych crop variants,
- related article block nadal rozwiązuje tylko publicznie widoczny target z aktywną kategorią i opublikowanym autorem; nie renderuje draft targetu,
- publiczny artykuł nie wymaga JavaScript do odczytania; Browser Smoke #23 na finalnym N3-008 implementation head `c8484aa1529eb41805a76ceb7be1f55db63aec14` przeszedł dedykowany `newsroom-article` QA, zachowując public renderer po wprowadzeniu gate,
- N3-005 nie implementował reverse links; NEWSROOM-N4-008 później dodało controlled reverse links i article→topic/related discovery bez dublowania Product Bridge,
- historyczne old-slug -> current canonical 301 są wdrożone przez NEWSROOM-N3-006 bez dodatkowego UI surface; `NEWSROOM_PUBLIC_ENABLED` jest wdrożone przez NEWSROOM-N3-008; przy `false` publiczne article/guide detail i historyczne redirecty failują do 404 przed lookupem, podczas gdy top-level placeholdery pozostają 200 + noindex,
- NEWSROOM-N3-007 rozszerza istniejący `/autorzy/{slug}`: `published` trafia do aktualnych publikacji, `needs_review+indexable` do osobnej sekcji „W trakcie weryfikacji”, `archived+indexable` do osobnego „Archiwum”, a noindex/scheduled/withdrawn/inactive-category nie są listowane,
- N4-001 rozszerza istniejący `NewsroomHomeCompositionService` bez tworzenia drugiego systemu kompozycji: fixed placements, fallback, globalna deduplikacja i breaking exception pozostają tym samym kontraktem, a category blocks korzystają z batched placements i bounded per-category ranking zamiast query-per-category,
- `NewsroomHomeReadModelService` dostarcza rollout-gated scalar-array projection bezpośrednio do publicznego huba: lead/secondary/latest/categories/guides/important_now/breaking z canonical path, category/author i hero metadata; przy `NEWSROOM_PUBLIC_ENABLED=false` nadal zwraca `null`,
- N4-002 podłącza read model do `resources/views/newsroom/home.blade.php`: renderuje responsywnie newsroom subnavigation, important-now, breaking, lead/secondary, latest, category blocks, guides i Product Bridge; puste sekcje są pomijane zamiast wypełniane sztucznymi kartami,
- subnavigation kategorii na hubie nadal prowadzi do kotwic sekcji, ale od N4-003 każdy category block ma crawlable `Zobacz wszystkie` do `public.news.categories.show`; publiczny topic route istnieje od N4-007, od N4-008 article detail ma jawne topic links i controlled reverse links z question/legal/sign surfaces, a N5-003 dodaje Atom auto-discovery w shared public-content head dla crawlable newsroom/guide surfaces,
- `newsroom.category` renderuje category header, chronologiczny listing kart, useful empty state, related categories oraz SSR paginację; wszystkie publiczne linki są czytelne bez JavaScript,
- category page reużywa wspólny `layouts.public-content`, header/footer i istniejące article card metadata; nie tworzy równoległego design systemu,
- gate=false, inactive/unknown category i invalid/out-of-range page nie pokazują publicznego category UI; aktywna pusta kategoria pokazuje komunikat i powrót do `/aktualnosci` zamiast sztucznych kart,
- dedykowany Browser Smoke #29 `newsroom-category` przeszedł z JavaScript disabled na 360/390/430/768/1024/1280/1440 oraz dodatkowo sprawdził page 2/canonical; równoległe `newsroom-home` i `newsroom-article` również zakończyły PASS,
- N4-004 renderuje `newsroom.guides` jako evergreen guide-only hub: H1/lead, praktyczny label `Poradnik`, hero/lead card, grid dalszych poradników, useful empty-state i SSR pagination; publication time nie jest głównym sygnałem UI,
- guide hub reużywa wspólny `layouts.public-content`, istniejące guide detail URLs oraz category label jako klasyfikację bez kierowania guide cards do newsroom category page,
- `/aktualnosci` ma crawlable `Zobacz wszystkie poradniki` do `public.guides`; N4-005 potwierdziło istniejące pojedyncze primary links „Aktualności” i „Poradniki” i nie dodało drugiego systemu nawigacji,
- Browser Smoke #31 `newsroom-guides` przeszedł z JS disabled na 360/390/430/768/1024/1280/1440 oraz page-2 canonical check; `newsroom-category`, `newsroom-home` i `newsroom-article` w tym samym runie również zakończyły PASS,
- N4-005 zachowuje istniejące header primary links i active states bez duplikatów; `documentNavigationPrefixes` dla `/aktualnosci` i `/poradniki` pozostaje bez zmian,
- compact footer ma wspólny backend source `PublicFooter::service_links`; od N4-005 zarówno Vue `SiteFooter.vue`, jak i Blade `public-footer.blade.php` renderują po jednym crawlable wejściu „Aktualności” i „Poradniki”,
- Browser Smoke #32 potwierdził oba linki footerowe na guide hubie przy JS disabled i brak regresji w `newsroom-guides`, `newsroom-category`, `newsroom-home` i `newsroom-article`,
- N4-007 renderuje `newsroom.topic` z H1/opisem, opcjonalnym featured leadem, mixed-family listą materiałów i SSR pagination; featured nie jest duplikowany w chronologicznym listingu,
- topic surface reużywa wspólny `layouts.public-content`, header/footer i istniejące canonical article/guide URLs; nie wymaga JavaScript do czytania,
- Browser Smoke #39 `newsroom-topic` przeszedł z JS disabled na 360/390/430/768/1024/1280/1440 oraz page-2 canonical check; równoległe `newsroom-guides`, `newsroom-category`, `newsroom-home` i `newsroom-article` również zakończyły PASS,
- N4-008 rozszerza ten sam `newsroom.article`: primary category jest zwykłym crawlable linkiem także na guide detail, jawne published topics są linkowane w sekcji „Tematy”, a „Powiązane materiały” renderują deterministyczny related set do maks. 4,
- wspólny komponent `newsroom-reverse-links` renderuje do maks. 3 canonical newsroom/guide links na publicznym pytaniu, opublikowanej stronie prawnej i detalu znaku; pusty resolver nie emituje pustego modułu,
- reverse-link UI konsumuje ten sam `NEWSROOM_PUBLIC_ENABLED` gate i tylko `activelyDistributed()+indexable()` targets; TrafficSign nie pokazuje pivotu `related`, a wyłącznie `direct|example`,
- N4-008 nie zmienia guide-hub cards: category label na `/poradniki` pozostaje klasyfikacją bez linkowania kart do newsroom category page; nowy primary-category link dotyczy detail article/guide,
- Browser Smoke #48 dodał `newsroom-semantic-links` i potwierdził article/home/category/guides/topic bez regresji; exact-head CI #361 i post-merge CI #362 zakończyły PASS,
- Hub Blade reużywa `layouts.public-content`, wspólny header/footer i route `public.tests`; przy gate=true emituje self-canonical i `index,follow,max-image-preview:large`,
- dedykowany Browser Smoke #27 `newsroom-home` przeszedł z JavaScript disabled na 360/390/430/768/1024/1280/1440, sprawdzając realny built CSS, H1/subnavigation/content/CTA, canonical/robots i brak horizontal overflow; równoległy `newsroom-article` także zakończył PASS.

---

## 68. Pozostałe zadania

- [ ] zatwierdzić finalne wspólne design tokens po audycie publicznego UI,
- [ ] przygotować low-fidelity implementation layout dla pozostałych hub/category/topic surfaces,
- [ ] zdefiniować finalny visual contract stałych homepage slots,
- [x] zbudować publiczny renderer kontrolowanych body blocks w zakresie N3-004,
- [x] zbudować regulatory context box dla article detail,
- [x] podłączyć focal-point-aware rendering istniejących hero/image assets; fizyczne crop variants nie są deklarowane jako istniejące,
- [x] zbudować topic page w NEWSROOM-N4-007,
- [x] zbudować wymagane N4-007 topic Blade components dla H1/opisu/featured/listingu/paginacji,
- [x] zbudować NEWSROOM-N4-008 semantic/reverse-link UI: category/topic/related links na article detail oraz bounded reverse module na question/legal/sign surfaces,
- [x] zbudować publiczny Hub Blade `/aktualnosci` w NEWSROOM-N4-002,
- [x] zbudować category page w NEWSROOM-N4-003,
- [x] zbudować article page,
- [x] dodać responsive Browser QA dla article detail na wymaganej macierzy N3-004,
- [x] domknąć repo-level accessibility QA newsroomu — NEWSROOM-N6-012 DONE po CI #542 + Browser Smoke #112 + manual focus/contrast evidence, merge `main@0f3645475a2a7c72c69cac80388891d2a9ed2a72` i post-merge CI #543 PASS,
- [x] dodać dedykowany browser snapshot/E2E dla article detail,
- [x] zbudować NEWSROOM-N3-005 Product Bridge dla questions/legal/signs/contextual CTA,
- [x] zbudować NEWSROOM-N3-006 historical redirect resolver HTTP,
- [x] zbudować NEWSROOM-N3-007 author-profile integration,
- [x] zbudować NEWSROOM-N3-008 public rollout config gate; `NEWSROOM_PUBLIC_ENABLED=false` jest bezpiecznym defaultem,
- [x] zbudować NEWSROOM-N4-001 editorial composition read model,
- [x] zbudować NEWSROOM-N4-002 Hub Blade layout i dedykowany responsive Browser QA,
- [x] zbudować NEWSROOM-N4-003 Category pages i dedykowany responsive Browser QA,
- [x] zbudować NEWSROOM-N4-004 `/poradniki` hub i dedykowany responsive Browser QA,
- [x] zweryfikować NEWSROOM-N4-005 Navigation integration bez dublowania istniejących linków i uzupełnić wspólny compact footer o oba huby.
- [x] NEWSROOM-N4-006 Cache jest wdrożone dla home/category read models.
- [x] NEWSROOM-N4-007 Topic / dossier pages jest wdrożone i ma dedykowany responsive Browser QA.
- [x] NEWSROOM-N4-008 Semantic silo / reverse-link integration jest wdrożone i ma dedykowany responsive Browser QA.
- [ ] kolejne publiczne zmiany wynikają z N5 discovery/delivery; szczegółową kolejność utrzymuje backlog.

---

## 69. Historia zmian

### 2026-09-20 — v0.27

- NEWSROOM-N6-012 domknięto na finalnym PR HEAD `14c3e9a1840e9d593e28533f36ccb8003aa9d686` z author-provided landmark-name checks,
- CI #542, Browser Smoke #112 i Enterprise SEO Production Validation #11 mają PASS,
- PR #152 zmergowano jako `main@0f3645475a2a7c72c69cac80388891d2a9ed2a72`, a post-merge CI #543 powtórzył pełny PASS,
- repo-level accessibility QA jest zamknięty; dokument nadal nie deklaruje formalnej zewnętrznej certyfikacji całego serwisu WCAG 2.2 AA.

### 2026-09-20 — v0.26

- NEWSROOM-N6-012 na PR #152 dodaje wspólny skip link/main target, visible keyboard focus dla service-menu i dostępne nazwy article complementary landmarks,
- istniejące article/home/category/topic/guides Browser QA reużywają jeden shared accessibility checker zamiast nowego równoległego systemu,
- Browser Smoke #108 oraz exact-head CI #538 są PASS; manualne screenshot/contrast evidence obejmuje mobile 390 i desktop 1024,
- pełny accessibility QA pozostaje otwarty do finalnego docs-sync CI, merge i post-merge CI.

### 2026-09-18 — v0.25

- NEWSROOM-N5-004 zmergowano przez PR #103 na `main@5704b3c3a8acde029001e28567980c5de8e27cdf`; publiczny design/layout nie zmienił się,
- article/home/category/guides/Product Bridge dostały wyłącznie niewizualne semantyczne `data-*` dla delegated analytics; czytanie i nawigacja nadal są SSR/link-first i nie zależą od JavaScript,
- jeden `newsroomAnalytics.ts` obsługuje article/module/category/pagination/Product Bridge/source/related clicks przez istniejący GA/consent layer; nie ma ręcznej logiki w każdym partialu ani content/PII w params,
- Browser Smoke #55 potwierdził dotychczasowy responsive rendering oraz dodatkowy JS-enabled analytics pass w `newsroom-article`; CI #384 i post-merge CI #385 były pełnym PASS,
- następnym taskiem wykonawczym jest NEWSROOM-N5-005 IndexNow integration review; nie wymaga on z góry zmian publicznego UI.

### 2026-09-18 — v0.24

- NEWSROOM-N5-003 zmergowano przez PR #101 na `main@18cd07233e3c8712cf2c7fc9e0c32018baef65d3`; public UI nie dostało nowego wizualnego komponentu ani design systemu,
- shared `layouts.public-content` emituje niewizualny head discovery link `rel="alternate" type="application/atom+xml"` dla crawlable newsroom/guide surfaces przy gate=true,
- gate=false zachowuje pre-launch placeholder behavior top-level hubów, blokuje feed do 404 i suppressuje discovery; gate=true udostępnia Atom feed bez zmiany istniejących page layouts,
- exact-head CI #380 i Browser Smoke #54 oraz post-merge CI #381 zakończyły PASS; nie ma deklaracji produkcyjnego CDN/Nginx feed smoke,
- następnym taskiem wykonawczym jest NEWSROOM-N5-004 Analytics hooks; nie wymaga on z góry zmian publicznego UI.

### 2026-09-18 — v0.23

- NEWSROOM-N4-008 zmergowano przez PR #95; finalny implementation head `bd63773bc1febdcc6aa8c2507c6621e908d63b09`, merge `main@3d7ac8ab8a3ed1c299cb0cdd4cb1ef8ac6b53f78`,
- article/guide detail renderuje crawlable primary-category link, explicit published topic links oraz deterministic „Powiązane materiały” do maks. 4,
- publiczne question/legal/sign pages reużywają wspólny `newsroom-reverse-links` i pokazują maks. 3 canonical eligible newsroom/guide targets; przy braku relacji moduł nie jest renderowany,
- TrafficSign pozostaje fail-closed dla `direct|example`; guide hub cards nie zostały zmienione i nadal traktują category jako label klasyfikacyjny,
- N4-008 reużywa istniejący public layout/design language i nie wymaga JavaScript do czytania linków,
- Browser Smoke #48 potwierdził `newsroom-semantic-links` oraz article/home/category/guides/topic; exact-head CI #361 i post-merge CI #362 zakończyły PASS,
- następnym etapem wykonawczym jest N5 zgodnie z backlogiem.

### 2026-09-18 — v0.22

- NEWSROOM-N4-007 zmergowano przez PR #93; finalny implementation head `812313afc38e30e53c59901d46c2d24188e3f6fa`, merge `main@a97373003c5a249a28761df15342f19e656c50c5`,
- `newsroom.topic` materializuje istniejący dossier contract: breadcrumbs, H1, editorial description, opcjonalny eligible featured, mixed-family chronological listing, SSR pagination i shared footer,
- draft/future/unknown/gate=false nie renderują publicznego topic UI, archived historyczny dostaje neutralną 410/noindex surface, a późniejszy below-baseline corpus nie wykonuje ukrytego HTTP flipu,
- N4-007 nie implementuje related legal/question modules ani automatycznych tag pages; explicit semantic/reverse links pozostają N4-008,
- Browser Smoke #39 potwierdził topic layout/canonical/no-overflow przy JS disabled na 360/390/430/768/1024/1280/1440 oraz brak regresji pozostałych newsroom surfaces; exact-head CI #348 i post-merge CI #349 zakończyły PASS.

### 2026-09-18 — v0.21

- NEWSROOM-N4-005 zmergowano przez PR #89 na `main@a9fb9ccfed058de88efdb6e0833b67911aeb09aa`; finalny implementation head `189219b3586d2df8e4ea73045318fd68f38f0fa1`,
- istniejące header primary links „Aktualności” i „Poradniki” oraz active-state prefixes zostały zachowane bez duplikatów; `documentNavigationPrefixes` nie wymagał zmiany,
- `PublicFooter::service_links` dostał pojedyncze wejścia do `/aktualnosci` i `/poradniki`, współdzielone przez Vue i Blade compact footer,
- `NewsroomNavigationIntegrationTest` chroni duplicate-free contract; `newsroom-guides` Browser QA sprawdza realny footer, a Browser Smoke #32 zakończył PASS dla wszystkich czterech newsroom jobów,
- exact-head CI #335 i post-merge CI #336 zakończyły PASS; NEWSROOM-N4-006 Cache jest następnym wykonywalnym taskiem.

### 2026-09-18 — v0.20

- NEWSROOM-N4-004 zmergowano przez PR #87 na `main@2bb22142b1e9bec803f9c3889c11000194f46783`; finalny implementation head `119cbd94d1fb6ff6f9f2025e726242190927266d`,
- `/poradniki` przy gate=true renderuje SSR `newsroom.guides`, a gate=false zachowuje pre-launch placeholder/noindex; route name i article detail layer pozostały bez zmian,
- UI ma evergreen variant z labelami poradników, lead card, grid, useful empty state i crawlable pagination; page 1 nie emituje `?page=1`,
- `/aktualnosci` ma `Zobacz wszystkie poradniki`; N4-005 nadal odpowiada za global navigation audit/integration,
- Browser Smoke #31 `newsroom-guides` PASS na 360/390/430/768/1024/1280/1440 z JS disabled i page-2 check; exact-head CI #331 i post-merge CI #332 także PASS,
- NEWSROOM-N4-005 Navigation integration jest następnym UI zakresem.

### 2026-09-18 — v0.19

- NEWSROOM-N4-003 zmergowano przez PR #85 na `main@84bcb2aeff57a1374def7af411c39db07a8fb38d`; finalny implementation head `8e4f707060fbaa348e9392f0b19beb7b4517beca`,
- category route renderuje SSR `newsroom.category` tylko przy gate=true i aktywnej kategorii; gate=false/inactive/unknown/invalid/out-of-range failują do 404,
- UI materializuje category header, chronologiczny listing, empty-state, related-category chips oraz crawlable paginację; hub category blocks mają `Zobacz wszystkie`,
- aktywna pusta kategoria zachowuje czytelny 200/noindex zamiast thin-page cards; page 1 nie linkuje do redundantnego `?page=1`,
- Browser Smoke #29 `newsroom-category` PASS na 360/390/430/768/1024/1280/1440 z JS disabled i page-2 check; exact-head CI #326 oraz post-merge CI #327 także PASS,
- NEWSROOM-N4-004 `/poradniki` hub jest następnym UI taskiem.

### 2026-09-18 — v0.18

- NEWSROOM-N4-002 zmergowano przez PR #83 na `main@04a3e961a40207bd65c41b684a5bf1cd8d2c5e10`; finalny implementation head `1036b67aa44b56fffb0bc1e9083d3a0d1d3963d0`,
- gate=true zastępuje MarketingPlaceholder na `/aktualnosci` SSR Blade `newsroom.home`, natomiast gate=false zachowuje istniejący 200 + noindex dark-deploy UX; `/poradniki` nadal nie jest publicznym hubem,
- wdrożony hub realizuje kolejność i responsive/empty-state contract sekcji 7 bez uruchamiania category/topic/feed routes; Product Bridge prowadzi do istniejącego bezpłatnego testu,
- Browser Smoke #27 `newsroom-home` PASS na 360/390/430/768/1024/1280/1440 z JS disabled i horizontal-overflow guard; exact-head CI #321 oraz post-merge CI #322 także PASS,
- NEWSROOM-N4-003 Category pages jest następnym UI taskiem.

### 2026-09-18 — v0.17

- NEWSROOM-N4-001 zmergowano przez PR #81 na `main@e0e06e9af8a6b02a63ef4b3e1eb2d772409ad974`,
- istniejący composer zachowuje fixed placements/fallback/dedupe/breaking contract, a category candidate loading ma teraz bounded query budget niezależny od liczby kategorii,
- `NewsroomHomeReadModelService` materializuje public-safe scalar arrays z canonical/category/author/hero metadata i respektuje `NewsroomPublicGate`,
- publiczny `/aktualnosci` nadal renderuje pre-launch placeholder 200 + noindex; N4-001 nie jest fałszywie utożsamione z Hub Blade ani cache,
- exact-head CI #314 i post-merge CI #315 są PASS; N4-002 jest następnym UI taskiem.

### 2026-09-18 — v0.16

- NEWSROOM-N3-008 zmergowano przez PR #79 na `main@23b952b77e39cd25fb39edc252faf05849946bd7`,
- `NEWSROOM_PUBLIC_ENABLED=false` pozostawia `/aktualnosci` i `/poradniki` jako istniejące pre-launch placeholdery 200 + noindex, ale blokuje detail/guide oraz historyczne redirecty przed ujawnieniem dark-deployed content,
- profil autora przy gate=false nie pokazuje publikacji Newsroomu; admin/private preview pozostają dostępne,
- exact-head CI #308, Browser Smoke #23 i post-merge CI #309 są PASS; publiczny hub/layout nadal należy do N4 i nie jest fałszywie oznaczony jako wdrożony.


### 2026-09-17 — v0.15

- NEWSROOM-N3-007 zmergowano przez PR #77 na `main@c68672f6aa7c41defaeec56debb541d5a60d9f4f` bez tworzenia drugiej strony autora,
- istniejący `/autorzy/{slug}` pokazuje Newsroom corpus z osobnymi stanami bieżącym, „W trakcie weryfikacji” i „Archiwum”; stany noindex/scheduled/withdrawn/inactive-category nie są listowane,
- `ContentAuthorProfileTest` chroni profile lifecycle i author links; exact-head CI #295 oraz post-merge CI #296 są PASS,
- następnym publicznym taskiem jest NEWSROOM-N3-008 rollout gate.

### 2026-09-17 — v0.14

- NEWSROOM-N3-006 zmergowano przez PR #75 i nie wprowadza nowej warstwy wizualnej: old article path wykonuje one-hop 301 do bieżącego canonical, a current article UI pozostaje powierzchnią N3-004/N3-005,
- current-canonical 200 oraz withdrawn 410 zachowują pierwszeństwo; stale/malformed redirect state failuje do istniejącej neutralnej 404 surface,
- query params nie są kopiowane do redirect target, więc historyczny link nie utrwala trackingowego wariantu URL,
- CI #279, Browser Smoke #21 i post-merge CI #280 są PASS; NEWSROOM-N3-007 author-profile integration jest następnym wykonywalnym taskiem.

### 2026-09-17 — v0.13

- NEWSROOM-N3-005 zmergowano przez PR #73; finalny PR implementation head `7d795b895865cda49ba94a4fec50533d7b0f7a97`, a zweryfikowany post-merge `main` to `fcc8074f89db141d522c5000742afc2e07a68565`,
- `NewsroomArticleProductBridgeService` i `newsroom/product-bridge-block.blade.php` materializują publiczne, fail-closed bloki questions/legal/signs/contextual CTA bez tworzenia drugiego systemu kart lub routingu,
- body target dla pytania, przepisu lub znaku musi być jednocześnie jawnie powiązany przez article-owned pivot i przejść istniejący public eligibility contract; inactive/draft/unlinked targets oraz private notes/official excerpt są pomijane,
- traffic sign z luźnym `relation_type=related` nie jest renderowany; finalna poprawka utrzymuje nadrzędny kontrakt sekcji 35 zamiast dopasowywać dokument do błędnego zachowania kodu,
- CTA prowadzą do istniejących tras testu, oficjalnej bazy pytań i nauki; reverse links pozostają NEWSROOM-N4-008,
- finalny exact-head CI #275 i Browser Smoke #20 są PASS; Browser Smoke #20 przeszedł pełną macierz 360/390/430/768/1024/1440, a post-merge CI #276 na `main@fcc8074f...` zakończył się pełnym PASS,
- następnym taskiem wykonawczym jest NEWSROOM-N3-006 historical redirect resolver; N3-007 author integration i N3-008 rollout gate pozostają otwarte.

### 2026-09-17 — v0.12

- NEWSROOM-N3-004 zmergowano przez PR #71 na `main@7398c5d930d38d6cc9d9953e53b4298df41cfce8`; publiczne article detail routes są podłączone do `ContentArticleController` i SSR Blade zamiast pre-launch 404,
- `newsroom.article` reużywa wspólny public layout i łączy N3-001 catalog, N3-002 SEO metadata oraz N3-003 schema graph z route-family breadcrumbs, byline/provenance, hero, regulatory context, body, sources, correction i author box,
- publiczny renderer obsługuje `rich_text`, `image`, `quote`, `table`, `context` i public-safe `related_article`; questions/legal/signs/product CTA pozostają świadomie odroczone do N3-005 i nie wyciekają jako placeholdery,
- archived/needs-review transparency oraz 404/410 unavailable surfaces są zmaterializowane; historyczne old-slug 301 nadal należy do N3-006, author-profile integration do N3-007, a `NEWSROOM_PUBLIC_ENABLED` do N3-008,
- dedykowany Browser Smoke #18 przeszedł całą macierz 360/390/430/768/1024/1440, a finalny CI #270 na merge commit zakończył się pełnym PASS,
- następnym taskiem wykonawczym jest NEWSROOM-N3-005 Product Bridge.

### 2026-09-17 — v0.11

- odnotowano wdrożony NEWSROOM-N3-003 backendowy `ContentArticleSchemaService` i jego service-level regression,
- schema service nie jest utożsamiany z gotowym article UI: nie dodano publicznego controller/Blade, body renderer ani JSON-LD response surface,
- detail routes `/aktualnosci/{articleSlug}` i `/poradniki/{articleSlug}` nadal pozostają 404; następnym krokiem jest N3-004 article Blade page + block renderer.

### 2026-09-17 — v0.10

- odnotowano wdrożony NEWSROOM-N3-002 backendowy `ContentArticleSeoService`,
- zapisano, że metadata service jest kompatybilny z istniejącym public-content layoutem, ale nie oznacza wdrożenia article page ani publicznego 200 renderer surface,
- detail routes `/aktualnosci/{articleSlug}` i `/poradniki/{articleSlug}` nadal pozostają 404; schema graph jest N3-003, a właściwy article Blade page N3-004.

### 2026-09-17 — v0.9

- odnotowano wdrożony NEWSROOM-N3-001 backendowy `ContentArticlePublicCatalogService`,
- rozdzielono gotowy read boundary od nadal niewdrożonych publicznych controllerów/Blade i zachowano pre-launch 404 dla detail routes,
- nie oznaczono article page, archived UI ani withdrawn 410 surface jako wdrożonych tylko dlatego, że backendowy resolver zwraca odpowiedni status semantyczny.

### 2026-09-16 — v0.8

- odnotowano wdrożony backendowy home composition service po N1-006,
- rozdzielono istniejący read-model kompozycji od nadal brakującego publicznego huba/rendererów,
- nie oznaczono żadnego publicznego newsroom componentu ani route controller jako wdrożonego.

### 2026-09-16 — v0.7

- zsynchronizowano stan UI z wdrożonym NEWSROOM-N0-002,
- udokumentowano dedykowane 200/noindex placeholdery `/aktualnosci` i `/poradniki`,
- zapisano, że future detail/category/topic/feed routes są już zarejestrowane, ale pozostają 404 bez publicznych rendererów,
- nie oznaczono żadnych newsroom-specific komponentów jako wdrożone.

### 2026-09-16 — v0.6

- latest/category/home chronology związano z activelyDistributed + first_published_at, bez sztucznego odświeżania po republish,
- dodano transparentny publiczny needs_review banner i inbound requirement dla indexable review-state content,
- source block rozróżnia public citation, citation bez URL i internal evidence.

### 2026-09-16 — v0.5

- dodano UI contract dla archived historical 200 page oraz withdrawn 410 bez renderowania treści,
- preview v1 wyrównano do admin-only/private-no-store/noindex-nofollow bez shareable signed URL.

### 2026-09-16 — v0.4

- doprecyzowano osobny breadcrumb contract dla newsroom article i guide,
- dodano opcjonalny hero caption jako figcaption, oddzielony od alt i credit,
- usunięto założenie o sztywnym limicie headline; pozostawiono redakcyjny warning dla nadmiernej długości.

### 2026-09-16 — v0.3

- doprecyzowano widoczne daty/czas dla newsów i ich zgodność z structured data,
- rozdzielono datę publikacji od effective/event dates,
- dodano publiczny author ProfilePage jako część UI DoD.

### 2026-09-15 — v0.2

- doprecyzowano homepage composition, fallback i globalną deduplikację kart,
- dodano rendering kontrolowanych bloków artykułu i branżowy regulatory/exam context box,
- dodano publiczne pochodzenie materiału, topic/dossier page i focal-point-aware crops,
- zapisano przyszłe miejsce dla audio/AI bez włączania tych funkcji do v1,
- nie dodano UI historii snapshotów wersji artykułu.

### 2026-09-15 — v0.1

- utworzono publiczny kontrakt UI/UX newsroomu,
- zdefiniowano lead hierarchy, latest stream, category blocks i product bridge,
- opisano pełny article layout,
- zablokowano SSR-first i wspólny header/footer,
- określono responsive, accessibility i component boundaries.
