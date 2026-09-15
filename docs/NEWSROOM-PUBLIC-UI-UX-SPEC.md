# Newsroom Public UI/UX Specification

## 1. Status

- Status: Proposed / implementation-ready UX contract
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Powiązane:
  - [MENU-SYSTEM-REFERENCE.md](./MENU-SYSTEM-REFERENCE.md)
  - [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md)
  - [NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md](./NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md)
- Data: 2026-09-15
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

Może zawierać 3–6 ręcznie lub systemowo wybranych tematów/linków.

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

Sekcja „Najnowsze” ma być chronologiczna.

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
5. byline + datePublished/dateModified
6. hero + credit
7. key points opcjonalnie
8. body
9. sources
10. correction note, jeśli istnieje
11. related legal/questions/signs
12. product bridge
13. author box
14. related articles

---

## 20. H1

Wymagania:

- jedno H1,
- bez sztucznego łamania słów,
- desktop target 42–56 px zależnie od długości,
- mobile 30–38 px,
- line-height umożliwiający długie polskie tytuły.

Nie ustawiamy sztywnej wysokości kontenera H1.

---

## 21. Lead artykułu

Wizualnie wyraźniejszy od body, ale nie większy niż H1.

Desktop:

- 19–22 px,
- max width zgodny z prose.

Mobile:

- 18–20 px.

---

## 22. Byline

Pokazuje:

- avatar opcjonalnie,
- nazwę autora jako link do /autorzy/{slug},
- datePublished,
- „Aktualizacja” tylko gdy last_substantive_update_at ma znaczenie.

Nie pokazujemy technicznego updated_at użytkownikowi.

---

## 23. Hero

Wymagania:

- width/height attributes,
- alt,
- credit jeśli wymagany,
- eager/fetchpriority high tylko jeśli hero jest LCP,
- responsive srcset, jeśli istniejący pipeline to wspiera,
- nie rozciągać małego assetu.

Aspect ratios preferowane:

- 16:9,
- 4:3 w wybranych kartach,
- osobny OG crop jeśli potrzebny.

---

## 24. Key points / „W skrócie”

Komponent:

- 2–5 punktów,
- semantyczna lista,
- tło subtelne,
- bez accordion dla kluczowych informacji.

Nie generować automatycznie z pierwszych zdań.

---

## 25. Body typography

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

## 26. H2/H3

H2:

- główne sekcje,
- nie tylko dla SEO.

H3:

- podsekcje.

Zakaz:

- przeskakiwania H1 -> H4 z powodów wizualnych.

---

## 27. Cytat

Blockquote:

- wyraźny, ale nie dekoracyjny do przesady,
- cytat + attribution,
- nie mieszać z calloutem redakcyjnym.

---

## 28. Tabele

Dla opłat/statystyk:

- semantic table,
- caption,
- headers,
- mobile overflow-x jako fallback,
- lepiej stacked representation tylko jeśli zachowuje relacje danych.

Tabel nie renderujemy jako obraz.

---

## 29. Sources block

Na końcu treści lub przed related modules.

Wygląd:

- heading „Źródła”,
- numerowana lub zwykła lista,
- publisher/title,
- link,
- opcjonalna data.

Nie ukrywamy źródeł w małym szarym tekście.

---

## 30. Correction note

Jeśli istotna korekta:

- widoczna,
- blisko metadanych albo na końcu z anchor,
- zawiera datę i krótki opis.

Nie stylizować jak błąd systemu.

---

## 31. Related questions

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

## 32. Related legal content

Dla artykułów prawnych:

- nazwa przepisu/tematu,
- krótki public note,
- link do /przepisy/...

Nie kopiujemy pełnego official excerpt do news article.

---

## 33. Related signs

Karta:

- kod znaku,
- obraz znaku,
- nazwa,
- link.

Nie pokazujemy, jeśli relacja jest tylko luźna.

---

## 34. Product CTA na artykule

CTA wynika z typu.

News o egzaminie:
- „Sprawdź się w teście”.

News o przepisie:
- „Zobacz pytania z tego zagadnienia”.

Guide:
- „Przejdź do nauki / testu”.

CTA nie może przykrywać treści sticky popupem v1.

---

## 35. Author box

Elementy:

- zdjęcie,
- imię/nazwisko,
- rola/opis,
- link „Więcej materiałów autora”.

Dane pochodzą z ContentAuthor.

---

## 36. Related articles

2–4 materiały.

Priorytet:

1. ręcznie lub semantycznie związane,
2. ta sama kategoria/tag,
3. nie pokazuj aktualnego artykułu.

---

## 37. Share controls

V1 optional.

Jeśli wdrażamy:

- kopiuj link,
- system share na mobile, jeśli wspierane,
- bez śledzących skryptów third-party przy samym renderze ikon.

---

## 38. Reading time

Nie jest wymagane.

Jeśli wdrożone:

- wyliczane deterministycznie,
- traktowane jako przybliżenie,
- nie zapisujemy ręcznie bez potrzeby.

---

## 39. Ads

Poza v1.

Layout nie powinien jednak uniemożliwiać późniejszego dodania jawnie oznaczonych slotów bez przebudowy całej struktury.

Nie projektujemy pustych reklamowych dziur w v1.

---

## 40. Empty states

### 40.1. Brak materiałów kategorii

Nie renderujemy pustej sekcji na homepage.

Na category page:

- komunikat „Nie ma jeszcze opublikowanych materiałów”,
- link do aktualności,
- noindex rozważyć, jeśli strona nie ma unikalnej wartości.

### 40.2. Brak hero

Karta ma wariant bez obrazu.
Nie używamy przypadkowego placeholder photo.

---

## 41. Error states

404 article:

- standardowa publiczna 404,
- link do /aktualnosci,
- opcjonalnie najnowsze materiały.

Nie redirectujemy każdego 404 do homepage.

500:

- bez wycieku stack trace,
- standardowe public error handling.

---

## 42. Preview UI

Preview:

- pasek u góry „PODGLĄD — materiał nieopublikowany”,
- informacja o statusie,
- opcjonalny link do edycji dla zalogowanego admina,
- noindex.

Preview nie może być pomylony z produkcyjną stroną przez redaktora.

---

## 43. Scheduled preview

Pokazuje:

- planowaną datę publikacji,
- status scheduled,
- finalny layout.

---

## 44. Accessibility

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

---

## 45. Link targets

Wewnętrzne:

- normalne linki bez target=_blank.

Źródła zewnętrzne:

- decyzja UX może pozostawić ten sam tab lub nowy; jeśli nowy, bezpieczny rel i dostępna informacja dla screen readera, jeśli potrzebna.

Nie stosujemy target=_blank mechanicznie dla każdego linku.

---

## 46. Mobile touch targets

Minimum praktyczne:

- 44x44 px dla controls,
- wystarczający spacing między nav links,
- pagination nie jako małe cyfry bez paddingu.

---

## 47. Sticky behavior

Global header może zachować istniejącą politykę.

Nie wdrażamy sticky sidebar/CTA w N3, jeśli nie ma danych, że pomaga.

---

## 48. Performance budgets UI

Newsroom page nie powinien dołączać dużych bibliotek tylko dla prostych interakcji.

Zasady:

- SSR,
- minimal JS,
- lazy media below fold,
- brak autoplay,
- brak ciężkich carousel libraries.

Docelowe metryki są w SEO/Observability spec.

---

## 49. CSS architecture

Preferowane:

- istniejący Tailwind/app.css pattern,
- komponentowe klasy/partials,
- wspólne tokeny.

Nie dodajemy inline style bloków per każdy article partial, jeśli można utrzymać styl w jednym miejscu.

---

## 50. Blade component map

Rekomendowane komponenty/partials:

- newsroom.section-nav
- newsroom.breaking-strip
- newsroom.story-card
- newsroom.story-list-item
- newsroom.lead-story
- newsroom.category-section
- newsroom.article-byline
- newsroom.key-points
- newsroom.sources
- newsroom.related-questions
- newsroom.related-legal
- newsroom.related-signs
- newsroom.product-bridge
- newsroom.author-box
- newsroom.pagination

Komponent nie powinien mieć dziesiątek wariantów sterowanych stringami.

---

## 51. Karty — warianty v1

Dozwolone:

- lead
- standard
- compact
- horizontal

Wystarczy.

Nie tworzymy osobnego komponentu dla każdej sekcji homepage.

---

## 52. Image behavior w kartach

- lead: 16:9
- standard: 16:9 lub 4:3 zgodnie z finalnym systemem
- compact: opcjonalny square/4:3 thumbnail
- object-fit cover tylko dla zdjęć, które można kadrować
- dla znaków drogowych nie używać agresywnego cover crop

---

## 53. Typ badges

Dozwolone:

- ANALIZA
- PORADNIK
- RAPORT
- PILNE/WAŻNE zgodnie z policy

Nie pokazujemy badge NEWS przy każdym newsie, jeśli kategoria już daje kontekst.

---

## 54. Responsive breakpoints

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

## 55. Header integration

PublicNavigation jest źródłem linków.

Implementacja newsroomu nie może:

- wstawić osobnego logo headera,
- skopiować menu do newsroomowego Blade,
- zduplikować auth controls.

Newsroom subnav jest drugim poziomem, a nie alternatywnym globalnym menu.

---

## 56. Footer integration

Używać istniejącego public footer.

Można dodać linki newsroomowe przez PublicFooter/PublicNavigation po decyzji menu, nie w hardcoded HTML konkretnej strony.

---

## 57. SEO content visibility

Główna treść:

- nie w accordion,
- nie po client-side fetch,
- nie po click „czytaj dalej”.

Pagination links są prawdziwymi href.

---

## 58. Loading states

Public SSR pages nie wymagają skeletonów initial load.

Jeśli przyszłe dynamic modules fetchują dane:

- progressive enhancement,
- skeleton tylko tam, gdzie faktycznie jest async.

---

## 59. Analytics hooks

Komponenty mogą mieć data attributes:

- data-analytics-module
- data-analytics-position
- data-article-id

Nie wkładamy logiki analitycznej do każdego Blade partiala ręcznie; public-content JS może delegować click tracking.

---

## 60. Wireframe /aktualnosci desktop

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

## 61. Wireframe article mobile

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

Body...
H2
Body...
H2
Body...

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

## 62. UI Definition of Done

Frontend newsroom v1 jest UI-complete, gdy:

- /aktualnosci działa desktop/mobile,
- category page działa z paginacją,
- article page działa dla wszystkich typów,
- brak hero ma poprawny wariant,
- long title nie rozwala layoutu,
- breadcrumbs poprawne,
- source block czytelny,
- related/product modules działają,
- focus/keyboard działa,
- 360/390/430 nie mają horizontal overflow,
- obrazy mają dimensions,
- JS nie jest wymagany do czytania,
- header/footer są wspólne z resztą serwisu.

---

## 63. Stan implementacji

Na moment utworzenia:

- /aktualnosci renderuje MarketingPlaceholder.vue,
- public-content Blade layout istnieje,
- public header/footer istnieją,
- globalna nawigacja zawiera Aktualności,
- newsroom-specific components nie istnieją.

---

## 64. Pozostałe zadania

- [ ] zatwierdzić design tokens N0,
- [ ] przygotować low-fidelity implementation layout,
- [ ] zbudować Blade components,
- [ ] zbudować hub,
- [ ] zbudować category page,
- [ ] zbudować article page,
- [ ] dodać responsive QA,
- [ ] dodać accessibility QA,
- [ ] dodać browser snapshots/golden E2E.

---

## 65. Historia zmian

### 2026-09-15 — v0.1

- utworzono publiczny kontrakt UI/UX newsroomu,
- zdefiniowano lead hierarchy, latest stream, category blocks i product bridge,
- opisano pełny article layout,
- zablokowano SSR-first i wspólny header/footer,
- określono responsive, accessibility i component boundaries.
