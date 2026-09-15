# Newsroom / Media Portal Architecture

## 1. Status dokumentu

- **Status:** Proposed / canonical design before implementation
- **Obszar:** publiczny serwis informacyjny, newsroom, aktualności, poradniki i dystrybucja treści
- **Repozytorium:** `prawkonaraz100/prawkonaraz`
- **Bazowy stan kodu:** `main@4b10738705f3696bc2bcce730a707473eab8cd2b`
- **Data utworzenia:** 2026-09-15
- **Właściciel decyzji produktowej:** PrawkoNaRaz
- **Cel:** zaprojektować profesjonalny pion medialny bez dublowania istniejącej platformy, bez osobnego CMS/WordPressa i bez rozbijania modularnego monolitu.

Ten dokument jest **kanoniczną specyfikacją dla obszaru newsroom/media portal**. Nie zastępuje dokumentów nadrzędnych dotyczących całego systemu.

## 2. Dokumenty nadrzędne i zależności

W razie konfliktu obowiązuje następująca kolejność:

1. [STACK-DECISION.md](./STACK-DECISION.md) — wybór stacku technologicznego.
2. [ADR-001-MODULAR-MONOLITH.md](./ADR-001-MODULAR-MONOLITH.md) — granice architektury i decyzja o modularnym monolicie.
3. [SEO-CONTENT-ROADMAP.md](./SEO-CONTENT-ROADMAP.md) — strategia publicznego contentu, SEO, trust layer i workflow redakcyjny.
4. [MENU-SYSTEM-REFERENCE.md](./MENU-SYSTEM-REFERENCE.md) — kanoniczna referencja publicznej nawigacji.
5. **Ten dokument** — szczegółowa architektura newsroomu, portalu informacyjnego i powiązania z produktem.

Dokument nie zmienia stacku i nie wprowadza nowej aplikacji. Projekt newsroomu ma być naturalnym rozwinięciem istniejącego systemu.

### 2.1. Mapa dokumentacji wykonawczej newsroomu

Ten dokument opisuje decyzje nadrzędne. Szczegóły implementacyjne są rozdzielone celowo:

- [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md) — model domenowy, tabele, constraints, indeksy, serwisy, scheduling i redirecty.
- [NEWSROOM-ADMIN-CMS-SPEC.md](./NEWSROOM-ADMIN-CMS-SPEC.md) — panel Filament, formularze, workflow actions, checklisty, preview i uprawnienia.
- [NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md](./NEWSROOM-EDITORIAL-OPERATIONS-AND-GOVERNANCE.md) — proces redakcyjny, źródła, korekty, breaking, freshness, AI policy i governance.
- [NEWSROOM-PUBLIC-UI-UX-SPEC.md](./NEWSROOM-PUBLIC-UI-UX-SPEC.md) — kontrakt publicznego layoutu, komponenty, mobile, accessibility i performance UI.
- [NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md](./NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md) — canonical, schema, obrazy, news sitemap, feed, Discover, analytics i monitoring.
- [NEWSROOM-IMPLEMENTATION-BACKLOG.md](./NEWSROOM-IMPLEMENTATION-BACKLOG.md) — kolejność N0–N6, taski, zależności, granice PR-ów i Definition of Done.
- [NEWSROOM-TEST-RELEASE-AND-ROLLBACK-RUNBOOK.md](./NEWSROOM-TEST-RELEASE-AND-ROLLBACK-RUNBOOK.md) — test matrix, E2E, release, smoke, incident handling i rollback.

### 2.2. Jak rozstrzygać konflikt między dokumentami newsroomu

1. decyzja architektoniczna i granica systemu — ten dokument,
2. dane/invariants — Data Model and Domain Spec,
3. zachowanie backoffice — Admin CMS Spec,
4. reguły redakcyjne — Editorial Operations,
5. zachowanie publicznego UI — Public UI/UX Spec,
6. Search/dystrybucja/monitoring — SEO Distribution and Observability,
7. kolejność implementacji — Implementation Backlog,
8. test/deploy/rollback — Test Release and Rollback Runbook.

Jeżeli kod wymusi zmianę decyzji nadrzędnej, najpierw aktualizujemy ten dokument. Jeżeli zmienia się tylko szczegół wykonawczy, aktualizujemy właściwą specyfikację bez przepisywania całej architektury.

---

## 3. Problem do rozwiązania

PrawkoNaRaz ma rozbudowany produkt edukacyjny i rosnącą warstwę publicznego contentu, ale nie ma jeszcze pełnoprawnego serwisu informacyjnego, który:

- regularnie pozyskuje ruch informacyjny,
- reaguje na zmiany przepisów, egzaminów i rynku,
- buduje rozpoznawalność marki poza samym momentem nauki do egzaminu,
- prowadzi użytkownika z informacji do pytań, wyjaśnień, testów i produktu,
- tworzy własny kanał dystrybucji niezależny od płatnej reklamy,
- daje redakcji narzędzia do szybkiej, kontrolowanej i audytowalnej publikacji.

Celem nie jest stworzenie „bloga firmowego”. Celem jest **profesjonalny portal wertykalny** skupiony na prawie jazdy, kierowcach, przepisach, egzaminach, WORD, OSK i bezpieczeństwie ruchu.

Inspiracją może być hierarchia informacji znana z dużych portali, ale projekt **nie może kopiować layoutu, brandingu ani wzorców 1:1**. PrawkoNaRaz ma mieć własny, czytelny system redakcyjny.

---

## 4. Cele produktu

### 4.1. Cel główny

Zbudować publiczny pion medialny, który zwiększa zasięg informacyjny i prowadzi część ruchu do istniejącego produktu.

### 4.2. Cele wtórne

- budowa topical authority wokół prawa jazdy i kierowców,
- szybsze wejście w zapytania związane ze zmianami prawa i egzaminów,
- rozwój ruchu lokalnego wokół WORD,
- budowa profili autorów i warstwy zaufania,
- tworzenie materiałów cytowalnych na podstawie własnych, zagregowanych danych,
- wykorzystanie istniejących pytań, znaków i treści prawnych jako przewagi redakcyjnej,
- przygotowanie kanałów pod Google Search, Discover, social media, RSS i przyszłe integracje dystrybucyjne.

### 4.3. Antycele

Na pierwszym etapie nie budujemy:

- osobnego WordPressa,
- osobnej domeny lub subdomeny newsroomu,
- mikroserwisu contentowego,
- automatycznej masowej publikacji generowanej bez review,
- systemu reklamowego klasy dużego portalu,
- newsroomu ogólnotematycznego,
- agregatora kopiującego cudze newsy,
- paywalla dla zwykłych treści informacyjnych.

---

## 5. Aktualny stan implementacji

Stan sprawdzony na `main@4b10738705f3696bc2bcce730a707473eab8cd2b`.

### 5.1. Elementy już istniejące

Repo ma już istotny fundament:

- Laravel 12,
- Inertia + Vue 3 + TypeScript,
- Filament jako backoffice,
- PostgreSQL,
- publiczny layout Blade z:
  - canonical,
  - robots,
  - OpenGraph,
  - Twitter Cards,
  - author metadata,
  - published/modified time,
  - JSON-LD,
- modele i publiczne profile `ContentAuthor`,
- route `/autorzy/{authorSlug}`,
- rozbudowane sitemapy,
- breadcrumbs,
- schema services dla treści prawnych,
- publiczny klaster znaków drogowych,
- publiczna baza pytań,
- relacje z podstawami prawnymi,
- publiczna nawigacja z pozycją „Aktualności”,
- route `/aktualnosci`,
- route `/poradniki`,
- SEO/content roadmap,
- Filamentowy workflow dla części istniejącego contentu.

### 5.2. Elementy istniejące tylko jako placeholder

`/aktualnosci` i `/poradniki` obecnie renderują `Public/MarketingPlaceholder.vue`.

To oznacza, że:

- adresy i miejsce w IA już istnieją,
- nie istnieje właściwy model publikacji newsroomowej,
- nie istnieje lista artykułów,
- nie istnieje widok artykułu informacyjnego,
- nie istnieje redakcyjny CMS dla newsów.

### 5.3. Brakujące elementy

Nie ma obecnie kompletnego odpowiednika:

- `ContentArticle`,
- `ContentCategory`,
- tagów redakcyjnych,
- relacji artykuł ↔ pytanie,
- relacji artykuł ↔ podstawa prawna,
- workflow newsroomowego,
- modułu „pilne / ważne / featured”,
- news sitemap,
- feedu RSS/Atom,
- strony kategorii newsroomowej,
- strony pojedynczego newsa,
- rankingów najnowsze / najczęściej czytane,
- pomiaru ekspozycji i CTR modułów redakcyjnych.

### 5.4. Znana niespójność brandingu

Przed wdrożeniem schema `NewsArticle` należy uporządkować branding publishera.

W `HomePageController.php` występują jeszcze pozostałości marki „Orły na Drodze” w structured data i assetach, podczas gdy produkt działa jako PrawkoNaRaz.

**Gate:** przed uruchomieniem newsroomu `Organization`, publisher, logo, nazwa i publiczny branding muszą mieć jedno kanoniczne źródło.

---

## 6. Główne decyzje architektoniczne

### DEC-NR-001 — newsroom pozostaje w tym samym repo i systemie

**Decyzja:** newsroom jest modułem obecnej aplikacji Laravel.

**Uzasadnienie:**

- może bezpośrednio korzystać z pytań, znaków, autorów i podstaw prawnych,
- jeden auth i jeden backoffice,
- jeden deployment,
- jeden model audytu,
- jedna domena i wspólny autorytet SEO,
- brak synchronizacji między CMS a produktem.

### DEC-NR-002 — jedna domena kanoniczna

Publiczny portal działa pod:

`https://prawkonaraz.pl`

Bez oddzielnego `news.`, `blog.` i bez osobnej domeny.

### DEC-NR-003 — publiczny content newsroomowy jest SSR-first

Widoki indeksowalne powinny korzystać z tego samego podejścia co obecna publiczna warstwa contentowa:

- Blade/server rendering jako domyślna warstwa dokumentu,
- pełne meta i structured data w initial HTML,
- JavaScript tylko tam, gdzie daje wartość użytkową,
- brak zależności od hydration do odczytania głównej treści.

Inertia/Vue pozostaje właściwe dla powierzchni aplikacyjnych i interaktywnych, a Filament dla backoffice.

### DEC-NR-004 — jeden model artykułu z kontrolowanymi typami

Zamiast osobnych tabel `news`, `guides`, `analysis` projekt powinien mieć wspólny agregat `content_articles` z polem `type`.

Pierwsze typy:

- `news` — aktualność,
- `guide` — poradnik evergreen,
- `explainer` — wyjaśnienie zmiany lub zjawiska,
- `analysis` — analiza danych / trendu,
- `report` — materiał własny oparty na danych.

Nowy typ wymaga jawnej decyzji; nie tworzymy dowolnych stringów.

### DEC-NR-005 — produktowy home pozostaje bezpiecznie oddzielony na pierwszym etapie

Pierwszym pełnym hubem newsroomu jest:

`/aktualnosci`

Obecne `/` pozostaje stroną produktową.

Po uruchomieniu i zebraniu danych można dodać blok „Najnowsze informacje” na stronie głównej. Pełna zmiana `/` w portal hybrydowy wymaga osobnej decyzji opartej o dane.

### DEC-NR-006 — redakcja steruje stałymi slotami strony głównej

Newsroom v1 ma kontrolowany system ekspozycji redakcyjnej dla `/aktualnosci`.

Nie jest to page builder. Kod definiuje skończony zestaw powierzchni i slotów, np.:

- lead,
- secondary,
- category lead,
- guides lead,
- important_now / „Ważne teraz”.

Redaktor może przypisać artykuł do slotu oraz ustawić czas początku i końca ekspozycji. Brak ręcznego przypisania uruchamia deterministyczny fallback oparty o aktualnie opublikowane treści.

Układ DOM, liczba sekcji i typ komponentu pozostają pod kontrolą kodu.

### DEC-NR-007 — artykuł korzysta z kontrolowanej biblioteki bloków

Treść artykułu nie może ograniczać się do niekontrolowanego jednego pola HTML, jeżeli chcemy bezpiecznie i konsekwentnie renderować materiały redakcyjne.

Newsroom v1 przewiduje uporządkowany dokument z kontrolowanymi typami bloków, m.in.:

- rich text,
- image,
- quote,
- table,
- context/callout,
- related article,
- legal reference,
- question group,
- traffic sign group,
- product CTA,
- allowlisted embed.

To nadal nie jest uniwersalny page builder: redaktor nie definiuje dowolnego HTML, CSS, layoutu ani nowych typów komponentów.

Dokładna serializacja i komponent edytora są domykane w `NEWSROOM-N0-004`, ale jeden uporządkowany dokument blokowy ma być kanonicznym źródłem treści body.

### DEC-NR-008 — topic jest innym bytem niż tag

Tag służy lekkiej klasyfikacji i nie tworzy automatycznie publicznego URL.

Topic/dossier jest ręcznie zarządzanym hubem redakcyjnym o własnym:

- tytule,
- slugu,
- opisie,
- statusie publikacji,
- materiale wyróżnionym,
- zestawie powiązanych artykułów.

Model topics może wejść w v1, natomiast publiczne uruchomienie konkretnego huba wymaga realnego corpus i wartości dla użytkownika. Nie generujemy masowych thin pages z tagów.

### DEC-NR-009 — media mają art direction, nie tylko jedną ścieżkę obrazu

Hero ma przechowywać punkt zainteresowania/focal point. Media layer może generować kontrolowane warianty/cropy potrzebne dla:

- lead,
- standard card,
- compact card,
- social/OG.

Redaktor nie powinien ręcznie uploadować wielu niezależnych kopii tego samego obrazu, jeśli pipeline może przygotować warianty deterministycznie.

### DEC-NR-010 — audio i funkcje AI są zaplanowanym rozszerzeniem, nie gate v1

Architektura artykułu ma nie blokować późniejszego:

- odsłuchu artykułu,
- transkrypcji,
- kontrolowanego streszczenia,
- funkcji „zapytaj o ten artykuł”.

Nie tworzymy jednak pól/tabel ani publicznych controls tylko „na zapas”, dopóki nie rozpocznie się odpowiedni etap implementacji. Takie pochodne treści nie mogą samodzielnie publikować ani zmieniać faktów źródłowego artykułu.

### DEC-NR-011 — osobny system snapshotów wersji artykułu jest poza zakresem

Na wyraźną decyzję produktową nie projektujemy dodatkowego revision history z diff/restore snapshotów. Pozostaje istniejący model audytu zmian i publiczna polityka korekt.

---

## 7. Architektura informacji

### 7.1. Główne wejścia

```text
/
├── aktualnosci/
│   ├── kategoria/prawo-jazdy/
│   ├── kategoria/egzaminy/
│   ├── kategoria/przepisy/
│   ├── kategoria/word/
│   ├── kategoria/kierowcy/
│   ├── kategoria/osk/
│   └── temat/{topicSlug}/
├── poradniki/
├── przepisy/
├── znaki-drogowe/
├── oficjalna-baza-pytan-na-prawo-jazdy/
├── testy-na-prawo-jazdy/
└── nauka/
```

### 7.2. URL artykułu

Rekomendowany model:

`/aktualnosci/{slug}`

oraz:

`/poradniki/{slug}`

Nie umieszczamy daty w canonical URL. Data jest metadanym artykułu, nie częścią jego tożsamości.

### 7.3. URL kategorii

Rekomendowany i przyjęty do backlogu wykonawczego wariant:

`/aktualnosci/kategoria/{categorySlug}`

Jawny segment `kategoria` usuwa kolizję routingu między slugiem kategorii i slugiem artykułu. Slug kategorii jest stabilny i zarządzany centralnie. Każda zmiana tej decyzji wymaga aktualizacji architektury i testu konfliktów route.

### 7.4. URL topicu / dossier

`/aktualnosci/temat/{topicSlug}`

Topic ma własny publiczny URL dopiero po publikacji i spełnieniu warunków jakości opisanych w governance/SEO. Tag nie otrzymuje tego URL automatycznie.

### 7.5. Redirect governance

Zmiana opublikowanego sluga:

- zapisuje poprzedni URL,
- generuje trwały redirect do canonical,
- aktualizuje linkowanie wewnętrzne,
- nie tworzy dwóch indeksowalnych kopii.

---

## 8. Taksonomia newsroomu

### 8.1. Kategorie podstawowe v1

1. **Prawo jazdy**
   - PKK,
   - kurs,
   - wymagania,
   - kategorie uprawnień.

2. **Egzaminy**
   - teoria,
   - praktyka,
   - zmiany zasad,
   - pojazdy egzaminacyjne.

3. **Przepisy**
   - nowelizacje,
   - obowiązki kierowców,
   - pierwszeństwo,
   - prędkość,
   - znaki i organizacja ruchu.

4. **WORD**
   - informacje o ośrodkach,
   - zmiany lokalne,
   - terminy i organizacja egzaminów,
   - dane i statystyki.

5. **Kierowcy**
   - dokumenty,
   - mandaty i punkty,
   - bezpieczeństwo,
   - obowiązki po uzyskaniu uprawnień.

6. **OSK**
   - szkoły jazdy,
   - szkolenie,
   - wymagania,
   - rynek i cyfryzacja.

### 8.2. Tagi

Tag nie może zastępować kategorii.

Tagi służą do przecięcia tematów, np.:

- PKK,
- egzamin teoretyczny,
- egzamin praktyczny,
- młody kierowca,
- kategoria B,
- Ministerstwo Infrastruktury.

Tagi wymagają deduplikacji i normalizacji.

### 8.3. Geografia

Lokalność powinna być modelowana jawnie, nie tylko tagiem.

Docelowo:

- województwo,
- miasto,
- WORD,
- opcjonalny identyfikator jednostki.

Pozwoli to budować lokalne huby bez mnożenia przypadkowych URL.

---

## 9. Model danych

Poniższy model jest **docelowym projektem**, a nie opisem obecnej bazy.

### 9.1. `content_articles`

Poniższa lista jest skrótem architektonicznym. **Normatywny kontrakt pól, typów, constraints i indeksów znajduje się w [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md).**

Minimalny rdzeń:

```text
id
type
category_id
author_id
reviewer_id nullable
created_by_user_id nullable
updated_by_user_id nullable

title
slug
lead
body
key_points nullable

workflow_status
is_featured
is_breaking
editorial_priority
breaking_expires_at nullable

hero_image_path nullable
hero_image_alt nullable
hero_image_width nullable
hero_image_height nullable
og_image_path nullable

seo_title nullable
seo_description nullable
canonical_url nullable
robots nullable

scheduled_for nullable
published_at nullable
first_published_at nullable
reviewed_at nullable
needs_review_at nullable
source_checked_at nullable
freshness_review_due_at nullable
last_substantive_update_at nullable

created_at
updated_at
```

### 9.2. `content_categories`

```text
id
name
slug
description
position
is_active
seo_title
seo_description
created_at
updated_at
```

### 9.3. `content_tags`

```text
id
name
slug
created_at
updated_at
```

### 9.4. Pivoty

- `content_article_tag`
- `content_article_question`
- `content_article_legal_unit`
- opcjonalnie później `content_article_traffic_sign`

### 9.5. Źródła

Jeżeli artykuły mają korzystać z wielu źródeł, nie przechowujemy ich jako jednego dużego pola JSON bez semantyki.

Docelowo:

`content_article_sources`

z polami m.in.:

- `article_id`,
- `source_type`,
- `publisher`,
- `title`,
- `url`,
- `published_at`,
- `accessed_at`,
- `is_primary`.

To pozwala budować kontrolowany system aktualizacji i weryfikacji.

---

## 10. Statusy i workflow redakcyjny

### 10.1. Statusy

```text
draft
  ↓
in_review
  ↓
scheduled
  ↓
published
  ↓
needs_review
  ↓
archived
```

Dopuszczalny jest powrót `in_review -> draft`.

### 10.2. Reguły publikacji

Artykuł nie może być publicznie indeksowalny, jeśli:

- nie ma tytułu,
- nie ma leadu,
- nie ma autora,
- nie ma kategorii,
- nie ma treści,
- nie ma poprawnego sluga,
- ma niespójny `workflow_status` / `scheduled_for` / `published_at`,
- nie spełnia minimalnej checklisty źródeł dla typu wymagającego źródeł.

### 10.3. Breaking / pilne

`is_breaking` jest flagą redakcyjną o krótkim cyklu życia.

Wymagania:

- ma być używana oszczędnie,
- nie może zastępować kategorii,
- musi mieć timestamp ostatniej zmiany,
- powinna automatycznie tracić ekspozycję po określonym czasie lub po ręcznym zdjęciu flagi.

### 10.4. Aktualizacja artykułu

Każda istotna zmiana po publikacji powinna:

- aktualizować `updated_at`,
- opcjonalnie aktualizować publiczne `modified_time`,
- pozostawiać ślad w audycie,
- nie zmieniać automatycznie `first_published_at`.

---

## 11. Filament / backoffice newsroomu

### 11.1. Resource: Artykuły

Lista powinna obsługiwać:

- status,
- typ,
- kategorię,
- autora,
- datę publikacji,
- featured,
- breaking,
- needs review,
- wyszukiwanie po tytule/slugu,
- filtr „zaplanowane”,
- filtr „wymaga aktualizacji”.

### 11.2. Formularz artykułu

Sekcje:

1. Treść
2. Klasyfikacja
3. Autor / reviewer
4. Media
5. Powiązania produktowe
6. Źródła
7. SEO
8. Publikacja
9. Historia / audyt

### 11.3. Preview

Redaktor musi mieć możliwość podglądu artykułu przed publikacją bez wystawiania go do indeksacji.

### 11.4. Role

Pierwszy model może wykorzystać istniejące role administracyjne, ale przed wejściem większej redakcji należy rozdzielić:

- author,
- editor,
- reviewer,
- publisher/admin.

Nie należy rozszerzać uprawnień dopóki nie pojawi się realna potrzeba operacyjna.

---

## 12. Publiczny layout `/aktualnosci`

Portal ma mieć **hierarchię redakcyjną**, nie zwykłą siatkę kart.

### 12.1. Kolejność modułów v1

1. pasek ważnych tematów,
2. lead story,
3. 2–4 secondary stories,
4. „Najnowsze”,
5. sekcja „Zmiany w prawie”,
6. sekcja „Egzaminy i WORD”,
7. sekcja „Kierowcy”,
8. sekcja „Poradniki”,
9. „Najczęściej czytane” dopiero po wdrożeniu wiarygodnych danych,
10. moduł produktu: test / pytania / nauka.

### 12.2. Zasady UI

- biały, czytelny layout,
- mocna hierarchia typografii,
- ograniczona liczba kolorów,
- akcent marki zamiast agresywnego „breaking red” wszędzie,
- duże zdjęcia tylko tam, gdzie wspierają hierarchię,
- mobile-first,
- brak ciężkiego masonry,
- brak infinite scroll w v1,
- brak autoplay video na listach.

### 12.3. Moduły muszą być konfigurowalne przez kontrolowane placements

Redakcja steruje zawartością stałych powierzchni zdefiniowanych przez kod:

- lead story,
- secondary stories,
- category leads,
- guides lead,
- wybrane linki „Ważne teraz”.

Placement może mieć przedział aktywności. Resolver kompozycji strony wybiera aktywne ręczne przypisanie albo deterministyczny fallback.

Publiczny resolver ma również prowadzić zbiór już użytych artykułów, tak aby ten sam materiał nie był przypadkowo powtarzany w leadzie, secondary, latest i kolejnych sekcjach. Jeśli brakuje unikalnych kandydatów, sekcja może być krótsza zamiast powielać materiał.

Nie projektujemy pełnego page buildera: redaktor nie zmienia struktury layoutu, tylko obsadza z góry znane sloty.

---

## 13. Strona artykułu

### 13.1. Elementy obowiązkowe

- breadcrumbs,
- kategoria,
- H1,
- lead,
- autor,
- data publikacji,
- data aktualizacji, jeśli różni się istotnie,
- hero image,
- body,
- blok źródeł,
- powiązane treści,
- powiązane pytania/test,
- informacje o autorze,
- link do metodologii / zasad redakcyjnych.

### 13.2. Kontrolowane bloki artykułu

Body jest renderowane z kontrolowanej biblioteki bloków. Pozwala to zachować ten sam standard na desktopie, mobile, preview i przyszłych kanałach bez dopuszczania dowolnego HTML/CSS.

Bloki v1 obejmują co najmniej:

- rich text,
- image,
- quote,
- table,
- context/callout,
- related article,
- legal reference,
- question group,
- traffic sign group,
- product CTA,
- allowlisted embed.

### 13.3. W skrócie i kontekst branżowy

Dla dłuższych materiałów można stosować blok „W skrócie”, ale:

- musi być ręcznie lub kontrolowanie redagowany,
- nie może powtarzać całego leadu,
- nie może być ukrytym tekstem SEO.

Dla newsów regulacyjnych i egzaminacyjnych model powinien dodatkowo wspierać strukturalne informacje:

- co się zmienia,
- status zmiany/prawa,
- od kiedy,
- kogo dotyczy,
- wpływ na egzamin.

Dane te mają być używane do czytelnego boxu publicznego oraz kontroli redakcyjnej, a nie do automatycznego wymyślania treści.

### 13.4. Product bridge

Przykłady:

- artykuł o zmianie przepisów → powiązane przepisy i pytania,
- artykuł o znaku → karta znaku i pytania,
- artykuł o egzaminie → publiczny test/demo,
- poradnik → właściwy etap procesu użytkownika.

CTA powinno wynikać z kontekstu, nie być globalnym banerem „kup teraz”.

---

## 14. Content graph

Największą przewagą PrawkoNaRaz ma być połączenie newsroomu z istniejącą domeną wiedzy.

```text
ARTICLE
├── CATEGORY
├── TAGS
├── TOPICS
├── AUTHOR
├── SOURCES
├── LEGAL UNITS
├── TRAFFIC SIGNS
├── QUESTIONS
└── PRODUCT ACTION
```

### 14.1. Reguły

- relacje muszą być jawne i edytowalne,
- automatyczne rekomendacje mogą wspierać redakcję,
- publikowana relacja powinna być kontrolowana,
- nie tworzymy linków tylko dla SEO,
- link musi mieć wartość dla czytelnika.

### 14.2. Huby topic/dossier

Publiczny topic jest świadomie utworzoną powierzchnią redakcyjną, a nie automatyczną stroną tagu.

Może łączyć:

- newsy,
- poradniki,
- przepisy,
- pytania,
- znaki,
- analizy danych.

Publicacja topicu wymaga własnego opisu, odpowiedniego corpus i review. Z czasem można tworzyć huby tematyczne łączące:

- newsy,
- poradniki,
- przepisy,
- pytania,
- znaki,
- analizy danych.

---

## 15. SEO i dane strukturalne

### 15.1. Meta

Każdy artykuł:

- unikalny title,
- unikalny description,
- self-canonical domyślnie,
- `max-image-preview:large`,
- poprawny OG image,
- author metadata,
- published/modified metadata.

### 15.2. Schema

Typ schema wynika z typu treści:

- `NewsArticle` dla rzeczywistych newsów,
- `Article` dla części materiałów redakcyjnych,
- `BlogPosting` nie jest domyślnym wyborem dla newsroomu,
- `BreadcrumbList`,
- `Person` dla autora,
- spójny `Organization` jako publisher.

Nie deklarujemy schema, którego treść nie wspiera.

### 15.3. Sitemap

Docelowo:

- zwykła sitemap artykułów,
- osobna sitemap newsroomowa zgodna z aktualnymi wymaganiami wyszukiwarek,
- istniejący sitemap index rozszerzony o nowe zasoby.

Wymagania zewnętrznych platform należy zweryfikować ponownie w momencie implementacji zamiast zamrażać w kodzie nieaktualne limity.

### 15.4. Feed

V1 powinno przewidywać:

- RSS lub Atom dla najnowszych publikacji,
- opcjonalne feedy per kategoria później.

### 15.5. Canonical i duplikaty

Ten sam artykuł nie może żyć równolegle jako:

- `/aktualnosci/x`,
- `/poradniki/x`,
- `/kategoria/x`.

Typ i canonical URL są stabilne po publikacji.

---

## 16. Standard redakcyjny i źródła

### 16.1. Zasada źródła pierwotnego

Dla zmian prawa, administracji i egzaminów preferowane są:

- akty prawne,
- oficjalne komunikaty,
- strony ministerstw i instytucji,
- dokumenty publiczne,
- bezpośrednie dane źródłowe.

Media konkurencyjne mogą być źródłem tropu, ale nie powinny automatycznie zastępować źródła pierwotnego.

### 16.2. Rozdzielenie faktu i interpretacji

Artykuł powinien odróżniać:

- co zostało oficjalnie opublikowane,
- od kiedy obowiązuje,
- kogo dotyczy,
- co jest projektem lub zapowiedzią,
- co jest interpretacją redakcyjną.

### 16.3. Korekty

Musi istnieć proces korekt:

- błąd merytoryczny poprawiamy,
- przy istotnej korekcie odnotowujemy zmianę,
- nie podmieniamy historii tak, żeby zmieniać sens bez śladu.

### 16.4. AI

AI może wspierać:

- research,
- streszczenia robocze,
- tagowanie,
- propozycje linków,
- checklistę braków.

AI nie powinno samodzielnie publikować newsów bez review człowieka.

---

## 17. Media i obrazy

### 17.1. Wymagania

Każdy hero:

- ma alt,
- ma jawne wymiary,
- ma zoptymalizowany format,
- ma punkt zainteresowania/focal point,
- ma wariant do OG,
- może otrzymać deterministyczne cropy do lead/standard/compact,
- nie powoduje CLS,
- jest dostarczany przez istniejący media layer.

### 17.2. Prawa do materiałów

Nie kopiujemy losowych zdjęć z internetu.

Każdy asset powinien mieć znane pochodzenie:

- własny,
- licencjonowany,
- oficjalny materiał możliwy do wykorzystania,
- asset generowany zgodnie z polityką projektu.

### 17.3. Video

Video w newsroomie jest przyszłym rozszerzeniem. V1 nie wymaga osobnego playera redakcyjnego.

---

## 18. Dystrybucja

Newsroom powinien być projektowany pod wiele kanałów, ale źródłem treści pozostaje własna strona.

Kanały:

- Google Search,
- Google Discover,
- social media,
- newsletter w przyszłości,
- RSS,
- linkowanie wewnętrzne,
- partnerstwa OSK,
- materiały własne oparte na danych.

Nie implementujemy osobnych pipeline’ów dla każdego kanału przed uruchomieniem podstawowego newsroomu.

---

## 19. Analityka

### 19.1. Minimalne eventy

- article_view,
- article_scroll_depth,
- related_article_click,
- related_question_click,
- product_cta_click,
- category_click,
- source_click.

### 19.2. KPI newsroomu

Nie oceniamy newsroomu wyłącznie liczbą page views.

Obserwujemy:

- organic impressions,
- organic clicks,
- CTR,
- returning readers,
- article → question CTR,
- article → test CTR,
- article → registration CTR,
- średnią liczbę stron na wejście informacyjne,
- udział ruchu branded vs non-branded,
- widoczność kategorii i tematów.

### 19.3. Najczęściej czytane

Moduł „Najczęściej czytane” nie może opierać się na przypadkowym liczniku odsłon bez okna czasowego.

Wdrożenie wymaga jawnej definicji:

- zakres czasu,
- filtr botów,
- minimalny próg,
- zasada tie-break.

---

## 20. Wydajność

### 20.1. Założenia

Strony artykułów są read-heavy i powinny być tanie w obsłudze.

Wymagania:

- brak N+1,
- eager loading relacji potrzebnych do renderu,
- cache dla stabilnych elementów nawigacji i list,
- optymalizowane obrazy,
- brak ciężkiego JS do podstawowego czytania,
- lazy loading obrazów poza LCP,
- limit liczby modułów i kart per request.

### 20.2. Cache invalidation

Publikacja lub aktualizacja artykułu musi unieważniać:

- cache artykułu,
- cache właściwej kategorii,
- cache modułów strony `/aktualnosci`,
- feed,
- sitemap jeśli jest generowana/cachowana.

---

## 21. Bezpieczeństwo i audyt

- publiczne endpointy są read-only,
- zapis wyłącznie przez autoryzowany backoffice,
- preview wymaga autoryzacji lub podpisanego, krótkotrwałego URL,
- body i embed content muszą być sanityzowane,
- audytujemy publikację i istotne edycje,
- uploady przechodzą istniejące zasady mediów,
- nie dopuszczamy arbitrary HTML/JS od redaktora bez sanitizacji.

---

## 22. Test strategy

### 22.1. Backend

Testy:

- status workflow,
- publikacja i scheduling,
- slug uniqueness,
- canonical redirect,
- unpublished 404/noindex behavior,
- relacje article-question/legal,
- sitemap inclusion,
- feed inclusion,
- permission checks.

### 22.2. Frontend / rendering

Testy:

- meta,
- canonical,
- OG,
- structured data,
- breadcrumbs,
- hero dimensions,
- related content,
- mobile layout.

### 22.3. E2E

Golden path:

```text
admin creates draft
→ adds source
→ links questions
→ preview
→ publish
→ article visible publicly
→ article appears in category/hub
→ CTA opens correct product surface
```

Drugi golden path:

```text
scheduled article
→ not public before time
→ becomes public after time
→ appears in latest list
```

---

## 23. Etapy wdrożenia

Szczegółowy tasking i granice PR-ów są kanonicznie utrzymywane w [NEWSROOM-IMPLEMENTATION-BACKLOG.md](./NEWSROOM-IMPLEMENTATION-BACKLOG.md). Na poziomie architektury obowiązuje:

### Etap N0 — foundation

- publisher/Organization branding source of truth,
- finalny route contract,
- taxonomy v1,
- decyzja editor + sanitization.

**Exit criteria:** nie ma nierozstrzygniętej decyzji, która zmieniałaby schema, routing albo bezpieczeństwo body.

### Etap N1 — domain + database

- migrations,
- enumy,
- models/factories,
- slug redirects,
- publishing service,
- scheduler.

**Exit criteria:** domena może bezpiecznie przechować i deterministycznie opublikować artykuł bez CMS i publicznego renderera.

### Etap N2 — CMS + workflow

- ContentCategoryResource,
- ContentArticleResource,
- body editor,
- źródła,
- relacje,
- workflow actions,
- checklist,
- preview.

**Exit criteria:** redaktor może przygotować, sprawdzić, podejrzeć, zaplanować i opublikować artykuł bez edycji kodu.

### Etap N3 — publiczny artykuł

- public catalog service,
- SEO/schema services,
- Blade article page,
- sources/byline,
- product bridge,
- old-slug redirects.

**Exit criteria:** pojedynczy opublikowany artykuł jest poprawnie renderowany, indeksowalny i połączony z istniejącym produktem.

### Etap N4 — hub + kategorie + poradniki

- editorial home `/aktualnosci`,
- category pages,
- `/poradniki`,
- navigation integration,
- cache/invalidation.

**Exit criteria:** portal działa jako redakcyjny system wejść, a nie pojedyncza strona artykułu.

### Etap N5 — SEO + dystrybucja + analytics

- articles sitemap,
- news sitemap,
- RSS/Atom,
- analytics events,
- IndexNow review,
- link/content audit.

**Exit criteria:** publiczny corpus ma pełną warstwę dystrybucji i obserwowalności.

### Etap N6 — hardening + pierwszy rollout

- E2E golden paths,
- production scheduler smoke,
- production SEO validation,
- performance/security pass,
- pierwszy kontrolowany batch treści,
- Search Console observation.

**Exit criteria:** newsroom jest operacyjnie gotowy do regularnej publikacji.

### Po v1 — rozszerzenia zależne od danych

Dopiero po realnym ruchu i obserwacji:

- „najczęściej czytane”,
- personalizacja,
- lokalne huby WORD,
- newsletter,
- większe raporty własne,
- ewentualna rozbudowa home `/`.

---
## 24. Definition of Done v1

Newsroom v1 jest ukończony, gdy:

- artykuł powstaje bez zmiany kodu,
- ma autora, kategorię, źródła i workflow,
- preview działa bez publicznej indeksacji,
- publikacja i scheduling działają deterministycznie,
- `/aktualnosci` ma redakcyjną hierarchię,
- istnieje publiczna strona artykułu,
- canonical/meta/schema są poprawne,
- sitemap/feed uwzględniają właściwe rekordy,
- artykuł może być połączony z pytaniami i treścią prawną,
- działa co najmniej jeden kontekstowy most do produktu,
- są testy backend + rendering + E2E,
- nie ma niespójności publishera/brandingu,
- dokumentacja odzwierciedla faktyczny stan wdrożenia.

---

## 25. Decyzje zamknięte i otwarte przed kodowaniem

### 25.1. Decyzje zamknięte przez pakiet projektowy

1. `guide`, `news`, `explainer`, `analysis` i `report` korzystają ze wspólnego agregatu `content_articles`.
2. Źródła są osobną relacyjną tabelą `content_article_sources` już w v1.
3. Reviewer nie jest wymagany dla wszystkich newsów; wymóg wynika z typu/ryzyka materiału.
4. Lokalne huby WORD nie wchodzą do v1; model ma nie blokować ich późniejszego dodania.
5. `/` pozostaje produktowym home w v1.
6. Page builder nie wchodzi do v1; stosujemy kontrolowane moduły.
7. Komentarze użytkowników są poza scope v1.
8. Kategorie używają ścieżki `/aktualnosci/kategoria/{categorySlug}`.
9. Feed v1 używa `/aktualnosci/feed.xml`.
10. Publiczne strony newsroomu są SSR/Blade-first.

### 25.2. Otwarte decyzje N0 wymagające domknięcia przed implementacją zależnych elementów

- konkretny editor body i techniczna strategia sanitization (`NEWSROOM-N0-004`),
- finalne źródło brand name/logo/publisher po usunięciu pozostałości „Orły na Drodze” (`NEWSROOM-N0-001`),
- finalne wspólne design tokens używane przez newsroom po audycie obecnego publicznego UI.

Pozostałe szczegóły nie powinny blokować N1, jeśli nie wpływają na schema, bezpieczeństwo body albo publiczny routing.

---
## 26. Ukończone prace związane z tym obszarem

Na moment utworzenia dokumentu za ukończone uznajemy wyłącznie elementy rzeczywiście istniejące w kodzie:

- publiczny shell SEO,
- autorzy,
- publiczne profile autorów,
- część warstwy trust/legal,
- breadcrumbs,
- sitemapy,
- routes `/aktualnosci` i `/poradniki`,
- placeholdery tych tras,
- publiczna nawigacja prowadząca do aktualności,
- istniejące klastry pytań, znaków i przepisów, które mogą zostać powiązane z artykułami.

**Nie uznajemy newsroomu za zaimplementowany.**

---

## 27. Pozostałe zadania

Szczegółowym źródłem backlogu jest [NEWSROOM-IMPLEMENTATION-BACKLOG.md](./NEWSROOM-IMPLEMENTATION-BACKLOG.md). Najbliższa kolejność:

- [ ] `NEWSROOM-N0-001` — publisher branding source of truth,
- [ ] `NEWSROOM-N0-002` — test/utrwalenie przyjętego route contract,
- [ ] `NEWSROOM-N0-003` — deterministyczny taxonomy seed contract,
- [ ] `NEWSROOM-N0-004` — editor + sanitization decision,
- [ ] następnie N1 domain + database.

Pozostałe elementy N2–N6 są celowo utrzymywane w wykonawczym backlogu zamiast dublować tu checklistę.

---
## 28. Zasady utrzymania dokumentu

Po każdej implementacji dotyczącej newsroomu dokument musi zostać zaktualizowany tak, aby osobno pokazywał:

- **decyzje architektoniczne**,
- **aktualny stan implementacji**,
- **ukończone prace**,
- **pozostałe zadania**,
- **historię zmian**.

Nie wolno opisywać planu jako stanu wdrożonego.

Jeżeli implementacja odchodzi od tego dokumentu, należy:

1. zaktualizować decyzję,
2. podać powód,
3. opisać wpływ,
4. dopiero potem traktować nowy stan jako kanoniczny.

---

## 29. Historia zmian

### 2026-09-15 — v0.3

- przyjęto kontrolowane homepage placements jako część newsroom v1,
- przyjęto kontrolowany dokument blokowy artykułu zamiast nieograniczonego HTML,
- rozdzielono topic/dossier od tagów,
- dodano art direction przez focal point i generowane warianty obrazu,
- zaplanowano audio/AI jako rozszerzenia po v1 bez prealokowania schema,
- zapisano decyzję o braku osobnego revision-history snapshot systemu.

### 2026-09-15 — v0.2

- dodano mapę siedmiu wykonawczych dokumentów newsroomu i reguły rozstrzygania konfliktów,
- ujednolicono routing kategorii do `/aktualnosci/kategoria/{categorySlug}`,
- oznaczono szczegółowy Data Model Spec jako normatywny kontrakt schema,
- wyrównano etapy N0–N6 z wykonawczym backlogiem.

### 2026-09-15 — v0.1

- utworzono kanoniczny projekt newsroomu,
- zapisano stan istniejącego repo,
- potwierdzono użycie tego samego repo, domeny i modularnego monolitu,
- przyjęto SSR-first dla publicznej warstwy newsowej,
- zaprojektowano taxonomy, data model, workflow, Filament, portal hub, article page, SEO, dystrybucję i testy,
- wydzielono etapy N0–N6,
- wskazano niespójność publisher/brandingu jako gate przed schema `NewsArticle`.
