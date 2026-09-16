# Newsroom Editorial Operations and Governance

## 1. Status

- Status: Proposed / implementation-ready operating model
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Powiązany model danych: [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md)
- Data: 2026-09-16
- Cel: zdefiniować sposób pracy redakcji tak, aby wdrożony CMS nie był tylko formularzem do wpisywania tekstu, ale kontrolowanym procesem publikacji.

---

## 2. Zasada nadrzędna

Newsroom PrawkoNaRaz ma działać jak wyspecjalizowana redakcja informacyjna, nie jak generator treści SEO.

Każda publikacja musi mieć odpowiedź na pięć pytań:

1. Co jest faktem?
2. Jakie jest źródło?
3. Kiedy informacja obowiązuje lub miała miejsce?
4. Kogo dotyczy?
5. Co użytkownik może zrobić dalej w PrawkoNaRaz?

Jeżeli nie da się odpowiedzieć na pytania 1–4, materiał nie powinien być publikowany jako news.

---

## 3. Zakres tematyczny

Redakcja obejmuje:

- prawo jazdy,
- egzaminy państwowe,
- WORD,
- przepisy ruchu drogowego,
- kierowców i obowiązki po uzyskaniu uprawnień,
- szkoły jazdy / OSK,
- bezpieczeństwo ruchu drogowego,
- dane i analizy związane z nauką i egzaminami.

Poza zakresem:

- ogólna motoryzacja bez związku z kierowcą, egzaminem lub bezpieczeństwem,
- newsy sensacyjne tylko dlatego, że generują zasięg,
- polityka partyjna bez bezpośredniego związku z regulacjami istotnymi dla odbiorców,
- kopiowanie depesz i artykułów innych mediów,
- publikacje sponsorowane udające niezależny materiał redakcyjny.

---

## 4. Typy materiałów

### 4.1. News

Cel:

- poinformować o nowym, konkretnym zdarzeniu lub zmianie.

Wymaga:

- aktualnego źródła,
- jasnej daty,
- rozróżnienia „weszło w życie” od „zapowiedziano/projekt”.

### 4.2. Guide

Cel:

- evergreen, instrukcja lub proces krok po kroku.

Wymaga:

- kompletności,
- okresowego review,
- jasnych warunków i wyjątków.

### 4.3. Explainer

Cel:

- wyjaśnić zmianę, zjawisko lub przepis.

Wymaga:

- źródła pierwotnego,
- prostego języka,
- oddzielenia faktu od interpretacji.

### 4.4. Analysis

Cel:

- interpretacja danych lub trendu.

Wymaga:

- metodologii,
- źródła danych,
- jawnych ograniczeń.

### 4.5. Report

Cel:

- oryginalny materiał PrawkoNaRaz oparty na danych własnych lub zebranych.

Wymaga:

- opisanej metodologii,
- agregacji/anonymizacji danych,
- okresu badania,
- liczebności próby, jeśli ma zastosowanie,
- wyraźnego rozdzielenia korelacji i przyczynowości.

### 4.6. Pochodzenie materiału (origin)

Typ artykułu opisuje jego formę. `origin_type` opisuje, skąd pochodzi praca redakcyjna.

Dozwolone znaczenia operacyjne:

- original — materiał własny oparty na własnym researchu/reportingu,
- compiled — opracowanie kilku jawnych źródeł,
- official_source — materiał oparty przede wszystkim na komunikacie/dokumencie instytucji,
- data_analysis — analiza własna danych,
- licensed_agency — materiał wykorzystujący treść agencyjną na podstawie rzeczywistej licencji.

Zasady:

- origin nie zastępuje listy źródeł,
- `licensed_agency` nie może być użyte tylko dlatego, że inny portal cytował agencję,
- publiczna etykieta ma być zgodna z rzeczywistym sposobem powstania materiału,
- autor PrawkoNaRaz pozostaje odpowiedzialny za finalną publikację, nawet gdy materiał jest opracowaniem.

---

## 5. Role redakcyjne

V1 może korzystać operacyjnie z administratorów, ale proces ma rozdzielać role pojęciowo.

### 5.1. Author

Odpowiada za:

- research,
- draft,
- źródła,
- prawidłowe daty,
- linkowanie do produktu,
- poprawki po review.

### 5.2. Editor

Odpowiada za:

- jakość tytułu i leadu,
- strukturę tekstu,
- zgodność z kategorią i typem,
- eliminację niepotrzebnej sensacyjności,
- spójność brand voice.

### 5.3. Reviewer

Wymagany dla materiałów o podwyższonym ryzyku:

- zmiany prawa,
- obowiązki kierowców,
- interpretacja regulacji,
- materiały formalne dla OSK,
- raporty mogące wpływać na decyzje użytkowników.

Reviewer nie jest automatycznie „prawnikiem”, jeśli nie ma takich kwalifikacji. Profil autora/reviewera nie może sugerować kwalifikacji, których nie posiada.

### 5.4. Publisher

Osoba/rola mająca uprawnienie do publicznego publish/schedule.

Publisher sprawdza checklistę, ale nie zastępuje autora i reviewera.

---

## 6. Macierz odpowiedzialności

| Czynność | Author | Editor | Reviewer | Publisher |
| --- | --- | --- | --- | --- |
| Research | R | C | C | I |
| Draft | R | C | I | I |
| Źródła | R | C | C | I |
| Copy edit | C | R | I | I |
| Review prawne | C | I | R | I |
| SEO metadata | R/C | R | I | I |
| Schedule | I | C | I | R |
| Publish | I | C | I | R |
| Correction | R | R | C | A |
| Archive | C | R | C | A |
| Withdraw | C | R | C | A |

Legenda:

- R — Responsible
- A — Accountable
- C — Consulted
- I — Informed

W małym zespole jedna osoba może pełnić kilka ról, ale checklisty i audit trail nadal obowiązują.

Ważne: role Author/Editor/Reviewer/Publisher w tym dokumencie są rolami procesu redakcyjnego, nie osobnymi rolami logowania Filament. V1 panel pozostaje admin-only. `ContentAuthor` opisuje autora/reviewera treści, a `User` z AuditLog opisuje faktycznego zalogowanego administratora wykonującego akcję.

Dlatego v1 może wymagać reviewer identity + reviewed_at, ale bez osobnego RBAC/linku ContentAuthor↔User nie twierdzimy, że system technicznie wymusza „four eyes” jako dwie różne zalogowane osoby.

---

## 7. Workflow

### 7.1. Draft

Materiał roboczy.

Może być niekompletny.
Nie jest publiczny.
Nie jest indeksowalny.

### 7.2. In review

Warunki wejścia:

- tytuł,
- lead,
- renderowalne body_blocks,
- kategoria,
- autor,
- źródła,
- podstawowe media lub jawna decyzja „bez hero”.

### 7.3. Scheduled

Warunki:

- przeszedł checklistę publikacyjną,
- ma dokładny scheduled_for,
- nie wymaga nierozstrzygniętego review,
- preview zaakceptowany.

### 7.4. Published

Publiczny i indeksowalny zgodnie z SEO policy.

### 7.5. Needs review

Materiał pozostaje publiczny, ale wymaga sprawdzenia.

Nie należy automatycznie noindexować materiału tylko dlatego, że data review minęła.

### 7.6. Archived

Materiał wycofany z normalnej dystrybucji, ale nie automatycznie usunięty z historii.

Dla wcześniej opublikowanego materiału v1:

- canonical URL nadal zwraca 200,
- znika z aktywnych hubów/latest/feed/news sitemap,
- może pozostać indexable albo otrzymać kontrolowane noindex z merytorycznego powodu,
- archive samo w sobie nie oznacza 301/404/410.

Jeśli materiał ma faktycznie zniknąć albo ma następcę, nie przeciążamy workflow `archived`.

### 7.7. Withdrawn

Używamy tylko dla jawnego takedownu:

- poważny błąd, którego nie można bezpiecznie pozostawić publicznie,
- wymóg prawny,
- naruszenie praw/licencji,
- przypadkowa publikacja materiału, który nie powinien być publiczny.

Wymaga `withdrawal_reason` i audit trail. Publiczny dawny URL zwraca 410, chyba że istnieje rzeczywisty następca z 301. Restore zawsze wraca do review, nie bezpośrednio do published.

---

## 8. Zasada „status prawny”

Każdy materiał o prawie musi jednoznacznie wskazywać, czy opisuje:

- obowiązujące prawo,
- uchwaloną zmianę z przyszłą datą wejścia w życie,
- projekt,
- konsultacje,
- zapowiedź,
- propozycję,
- interpretację.

Zakazane skróty redakcyjne:

- „wchodzi zmiana”, jeśli istnieje tylko projekt,
- „od dziś”, jeśli data wejścia w życie jest inna,
- „kierowcy muszą”, jeśli obowiązek dotyczy tylko części grupy.

---

## 9. Hierarchia źródeł

### Tier 1 — źródła pierwotne

Preferowane:

- Dziennik Ustaw / ELI,
- gov.pl,
- Ministerstwo Infrastruktury,
- Policja,
- CEPiK,
- oficjalne WORD,
- uchwały/regulaminy/komunikaty właściwych instytucji,
- dane publiczne z właściwego organu.

### Tier 2 — źródła bezpośrednie

- odpowiedź instytucji,
- komunikat prasowy,
- wywiad z osobą odpowiedzialną,
- dokument organizacji,
- dane otrzymane bezpośrednio.

### Tier 3 — źródła wtórne

- media,
- portale branżowe,
- publikacje ekspertów.

Tier 3 może naprowadzać na temat, ale dla zmian regulacyjnych należy szukać Tier 1/2.

### 9.1. Public citation vs internal evidence

Nie każde prawdziwe źródło ma publiczny URL.

- official/legislation/institution/report/media zwykle powinny mieć publiczny URL, jeśli taki istnieje,
- interview, odpowiedź bezpośrednia lub inne evidence może nie mieć URL,
- `is_publicly_cited=true` oznacza zgodę/redakcyjną decyzję na pokazanie citation,
- `is_publicly_cited=false` przechowuje źródło jako wewnętrzny evidence i nie ujawnia title/publisher/url publicznie,
- source `note` jest zawsze wewnętrzne.

Dla newsa prawnego, jeśli istnieje jawne źródło Tier 1, co najmniej jedno takie źródło powinno być publicznie cytowalne z linkiem.

---

## 10. Research checklist

Przed napisaniem materiału autor sprawdza:

- [ ] datę zdarzenia,
- [ ] datę publikacji źródła,
- [ ] datę wejścia w życie, jeśli dotyczy,
- [ ] status dokumentu,
- [ ] zakres podmiotowy,
- [ ] geograficzny zakres informacji,
- [ ] czy istnieje źródło pierwotne,
- [ ] czy wcześniejszy artykuł PrawkoNaRaz wymaga aktualizacji,
- [ ] czy temat ma powiązane pytania/przepisy/znaki.

---

## 11. Standard tytułów

Tytuł ma:

- opisywać najważniejszy fakt,
- unikać clickbaitu,
- nie sugerować pewności tam, gdzie jej nie ma,
- nie nadużywać „PILNE”, „SZOK”, „wszyscy kierowcy”,
- być zrozumiały poza kontekstem social media.

Przykład poprawny:

„Nowe zasady X od 1 stycznia 2027 r. Kogo obejmą?”

Przykład niepoprawny:

„Kierowcy w szoku! Wszystko zmieni się za chwilę”

---

## 12. Lead

Lead powinien odpowiedzieć w 2–4 zdaniach:

- co się wydarzyło,
- kogo dotyczy,
- kiedy,
- co jest najważniejszą konsekwencją.

Lead nie może być pustym teaserem typu „Sprawdź, co się zmieni”.

---

## 13. Struktura newsa

Rekomendowana:

1. H1
2. lead
3. „W skrócie” opcjonalnie
4. najważniejszy fakt
5. co dokładnie się zmienia
6. kogo dotyczy
7. od kiedy
8. źródło / podstawa
9. co to oznacza praktycznie
10. powiązane materiały PrawkoNaRaz

Nie każdy news musi mieć identyczne nagłówki, ale czytelnik nie powinien szukać daty lub zakresu zmiany w połowie tekstu.

---

## 14. Standard poradnika

Poradnik ma zawierać:

- jasny zakres,
- wymagania wstępne,
- kroki,
- wyjątki,
- koszty tylko z datą/źródłem, jeśli zmienne,
- FAQ tylko z realnymi pytaniami,
- datę ostatniego review,
- powiązany etap produktu.

---

## 15. Standard analizy danych

Każda analiza musi mieć blok „Metodologia”.

Minimum:

- źródło danych,
- zakres dat,
- liczebność,
- filtry,
- definicję metryki,
- znane ograniczenia.

Dane użytkowników:

- tylko agregowane,
- bez identyfikacji osób,
- bez publikowania małych grup mogących prowadzić do reidentyfikacji,
- zgodnie z polityką prywatności i decyzjami prawnymi projektu.

---

## 16. Correction policy

### 16.1. Drobna korekta

Literówka, formatowanie, błędny link bez wpływu na sens:

- poprawiamy,
- nie wymaga public correction note,
- audit trail zachowany.

### 16.2. Istotna korekta

Zmiana:

- daty,
- kwoty,
- zakresu obowiązku,
- statusu prawa,
- cytowanej wypowiedzi,
- wniosku analizy.

Wymaga:

- correction_note,
- last_substantive_update_at,
- ponownego review,
- publicznej informacji o korekcie, jeśli błąd mógł wpłynąć na odbiorcę.

### 16.3. Wycofanie materiału

Jeśli cały materiał jest nieprawdziwy lub nie powinien być publiczny:

- natychmiast zdejmujemy z modułów,
- używamy `Withdraw from public`, co daje 410 i zachowuje rekord/audit w backoffice,
- jeśli istnieje rzeczywisty następca, zamiast 410 stosujemy jawny 301,
- `archive` nie jest takedownem — zachowuje historyczny 200,
- dokumentujemy przyczynę,
- nie zostawiamy fałszywego tekstu tylko „dla SEO”.

---

## 17. Update policy

Aktualizacja istniejącego artykułu jest preferowana, gdy:

- to ten sam temat i intencja,
- zmieniają się szczegóły lub kolejny etap tej samej sprawy,
- stary URL ma już wartość i nadal odpowiada użytkownikowi.

Nowy artykuł jest preferowany, gdy:

- następuje odrębne wydarzenie,
- zmiana ma własną intencję wyszukiwania,
- stary materiał jest historycznym zapisem konkretnego zdarzenia.

Nie przepisujemy historycznego newsa tak, aby udawał nowy news.

---

## 18. Breaking / „Pilne”

Breaking jest stanem ekspozycji, nie typem treści.

Można użyć, gdy:

- wydarzenie jest naprawdę świeże,
- ma istotny wpływ na dużą część odbiorców,
- informacja jest potwierdzona.

Nie używać:

- dla zwykłego poradnika,
- dla starej informacji,
- dla informacji tylko po to, aby zwiększyć CTR.

Breaking powinno wygasać automatycznie zgodnie z breaking_expires_at.

---

## 19. Featured

Featured oznacza rekomendację redakcji do mocniejszej ekspozycji.

Nie musi być breaking.
Może być:

- ważny explainer,
- analiza,
- raport,
- poradnik sezonowy.

Featured jest decyzją redakcyjną, nie metryką popularności.

### 19.1. Homepage placements

Konkretne miejsce na `/aktualnosci` jest osobną decyzją od `is_featured`.

Redaktor może ręcznie obsadzić stały slot na określony czas.

Zasady:

- lead powinien reprezentować najważniejszy aktualny materiał, a nie tylko najnowszy,
- secondary powinny uzupełniać lead, nie powtarzać tego samego tematu bez potrzeby,
- category lead musi rzeczywiście należeć do danej kategorii lub mieć jawne uzasadnienie redakcyjne,
- nie używamy ręcznych placementów do utrzymywania nieaktualnego artykułu na górze bez powodu,
- wygasające placementy mają mieć sensowny `ends_at`, jeśli wydarzenie jest czasowe,
- resolver może wypełnić pusty slot fallbackiem.

### 19.2. Deduplikacja strony głównej

Na jednej stronie unikamy powtarzania tej samej karty w kilku modułach.

Jeśli materiał jest leadem:

- nie trafia ponownie do secondary,
- nie trafia ponownie do latest,
- nie powinien być powtarzany w category block na tej samej stronie.

Wyjątkiem jest breaking strip, który pełni rolę alertu, nie kolejnej karty.

---

### 19.3. Topic / dossier governance

Topic tworzymy, gdy temat:

- ma znaczenie dłuższe niż jeden news,
- ma lub będzie miał wystarczający corpus,
- pomaga użytkownikowi zrozumieć ciąg zdarzeń albo zagadnienie,
- wymaga własnego opisu redakcyjnego.

Nie tworzymy topicu:

- dla każdego taga,
- dla jednego artykułu tylko po to, by uzyskać dodatkowy URL,
- bez osoby odpowiedzialnej za utrzymanie.

Przykłady sensownych topiców:

- Zmiany w egzaminie 2027
- PKK
- Punkty karne
- WORD Warszawa — dopiero gdy istnieje wystarczająca, aktualizowalna zawartość.

---

## 20. Źródła w artykule

Publiczny blok źródeł ma być czytelny.

Zasady:

- nie chowamy podstawowych źródeł tylko w JSON-LD,
- źródło pierwotne powinno być linkowane, jeśli publicznie dostępne,
- nie publikujemy prywatnych danych kontaktowych z korespondencji,
- przy źródle dokumentu wskazujemy nazwę dokumentu/instytucji.

---

## 21. Cytaty

Cytat musi być:

- wierny,
- przypisany,
- osadzony w kontekście,
- nie może zmieniać znaczenia przez selektywne skrócenie.

Jeśli cytat pochodzi z publicznej publikacji, przestrzegamy praw autorskich i nie kopiujemy nadmiernych fragmentów.

---

## 22. Obrazy, kredyt i art direction

Przed publikacją hero:

- [ ] źródło/licencja znane,
- [ ] alt opisuje obraz jako tekst alternatywny,
- [ ] caption dodany, jeśli obraz wymaga kontekstu dla czytelnika,
- [ ] credit zapisany, jeśli wymagany,
- [ ] focal point ustawiony sensownie dla ważnych zdjęć,
- [ ] preview lead/standard/compact nie ucina kluczowej informacji,
- [ ] nie używamy zdjęcia sugerującego wydarzenie, którego obraz faktycznie nie przedstawia,
- [ ] nie używamy AI image jako „fotografii dokumentalnej” bez jasnego oznaczenia kontekstu.

Focal point opisuje kompozycję obrazu, nie jest narzędziem do manipulowania znaczeniem fotografii. Caption, alt i credit mają różne role i nie powinny być kopiowane automatycznie między sobą.

---

## 23. AI w redakcji

AI może pomagać w:

- research checklist,
- wyszukiwaniu powiązań w naszym corpusie,
- streszczeniu materiałów roboczych,
- propozycjach tytułów,
- poprawie języka,
- tagowaniu,
- wykrywaniu niespójności dat,
- propozycjach pytań do źródła.

AI nie może samodzielnie:

- ustalić stanu prawnego bez źródła,
- publikować,
- wymyślać cytatów,
- tworzyć źródeł,
- zmieniać istotnych faktów bez review,
- przedstawiać wygenerowanego tekstu jako wypowiedzi instytucji.

Autor/publisher odpowiada za finalny materiał.

### 23.1. Przyszłe funkcje AI dla czytelnika

Po v1 można rozważyć:

- skrócenie artykułu,
- pytania do artykułu,
- wyjaśnienie pojęcia.

Warunki:

- AI pracuje na aktualnej treści i jawnych źródłach,
- odpowiedź nie zmienia statusu prawa ani faktów źródłowego artykułu,
- użytkownik widzi, że odpowiedź jest generowana,
- funkcja nie publikuje osobnej, indeksowalnej kopii artykułu,
- błędna odpowiedź AI nie może automatycznie aktualizować artykułu.

### 23.2. Przyszły odsłuch

Audio może być generowaną pochodną zatwierdzonego artykułu.

Wymagania przed wdrożeniem:

- jednoznaczne powiązanie z wersją aktualnej publikowanej treści,
- możliwość ponownego wygenerowania po istotnej korekcie,
- player dostępny klawiaturą,
- brak autoplay.

Nie jest to wymaganie newsroom v1.

---

## 24. Polityka linkowania do produktu

Link do produktu musi być kontekstowy.

Dobre przykłady:

- news o znaku -> karta znaku,
- zmiana przepisu -> odpowiednie pytania,
- poradnik o teorii -> test demo,
- materiał o błędach -> najtrudniejsze pytania.

Złe przykłady:

- ten sam „Kup dostęp” po każdym akapicie,
- losowe pytania tylko dla zwiększenia liczby linków,
- linkowanie do prywatnej funkcji bez wyjaśnienia wymogu logowania.

---

## 25. Internal linking editorial checklist

- [ ] co najmniej jeden sensowny link do istniejącej wiedzy, jeśli istnieje,
- [ ] nie linkować tego samego anchoru wielokrotnie bez potrzeby,
- [ ] anchor opisuje cel,
- [ ] powiązane pytania są faktycznie powiązane,
- [ ] linki nie prowadzą do draftów/404.

---

## 26. Freshness policy

### 26.1. Materiały prawne

Trigger kolejki review:

- nowelizacja aktu,
- nowy komunikat organu,
- zmiana daty wejścia w życie,
- sygnał o błędzie,
- osiągnięcie freshness_review_due_at.

Sam termin `freshness_review_due_at` oznacza overdue i podnosi priorytet pracy, ale nie zmienia automatycznie workflow na `needs_review`. `needs_review` stosujemy, gdy istnieje konkretna przesłanka, że dalsza aktywna promocja bez ponownej weryfikacji jest niewłaściwa.

### 26.2. Guides

Standardowy review:

- co 90–180 dni zależnie od zmienności.

### 26.3. News

News historyczny nie musi być sztucznie „odświeżany”.
Jeśli zmienia się historia, dodajemy update lub nowy materiał zależnie od reguł z sekcji 17.

---

## 27. „Stan na dzień”

Dla treści o zmiennych wymaganiach można jawnie pokazywać:

„Stan informacji: 15 września 2026 r.”

To nie zastępuje daty publikacji i aktualizacji.

---

## 28. Zasady dla WORD / informacji lokalnych

Przy materiale lokalnym zawsze ustalamy:

- konkretny WORD,
- miasto,
- datę obowiązywania,
- czy zmiana jest stała czy tymczasowa.

Nie generalizujemy zmiany jednego WORD na całą Polskę.

---

## 29. Zasady dla kosztów i opłat

Kwota wymaga:

- daty,
- zakresu,
- źródła,
- informacji, czy cena jest urzędowa, rynkowa czy przykładowa.

Nie publikujemy jednej ceny jako „koszt prawa jazdy w Polsce”, jeśli jest zależna od OSK/miasta.

---

## 30. Zasady dla statystyk zdawalności

Należy wskazać:

- okres,
- ośrodek,
- kategorię,
- teorię/praktykę,
- źródło,
- definicję wskaźnika.

Nie porównujemy nieporównywalnych okresów bez wyjaśnienia.

---

## 31. Standard językowy

Styl:

- prosty,
- konkretny,
- neutralny,
- bez urzędowego żargonu, jeśli można go wyjaśnić,
- bez protekcjonalnego tonu,
- bez sensacyjnych dopowiedzeń.

Terminy prawne:

- zachowujemy nazwę oficjalną,
- wyjaśniamy prostym językiem,
- nie upraszczamy do poziomu zmiany znaczenia.

---

## 32. Publikacyjna checklista mandatory

Przed publish publisher potwierdza:

- [ ] title
- [ ] lead
- [ ] body
- [ ] type
- [ ] category
- [ ] author
- [ ] origin type
- [ ] sources
- [ ] prawidłowe daty
- [ ] status prawny/regulatory status, jeśli dotyczy
- [ ] effective_from / kogo dotyczy / wpływ na egzamin, jeśli wymagane
- [ ] hero/alt/focal point lub jawna decyzja bez hero
- [ ] OG alt poprawny dla dedykowanego OG assetu, jeśli różni się od hero
- [ ] publiczne URL-e assetów SEO nie wymagają auth/wygasającego podpisu
- [ ] SEO title/description lub poprawny fallback
- [ ] seo_title, jeśli różny od H1, zachowuje ten sam główny sens/claim i nie jest clickbaitem
- [ ] canonical
- [ ] preview desktop
- [ ] preview mobile
- [ ] crop preview dla kluczowego hero
- [ ] related content
- [ ] product bridge
- [ ] spelling/copy
- [ ] reviewer, jeśli wymagany
- [ ] publiczny profil autora istnieje i odpowiada wskazanemu authorowi
- [ ] materiał nie jest thin/scaled duplicate
- [ ] article-question links korzystają z istniejących encji/graphu pytań bez tworzenia drugiej taksonomii lub rankingu
- [ ] brak draft links
- [ ] brak nieautoryzowanych assetów

Checklistę warto odwzorować w Filament jako stan/validation, a nie tylko dokument.

---

## 33. Checklist po publikacji

Dla ważnego materiału:

- [ ] HTTP 200
- [ ] canonical poprawny
- [ ] meta title/description poprawne
- [ ] schema renderuje się
- [ ] obraz dostępny
- [ ] link źródła działa
- [ ] artykuł pojawia się w właściwym hubie
- [ ] feed/sitemap po odświeżeniu zawiera URL
- [ ] CTA prowadzi poprawnie
- [ ] brak noindex

---

## 34. Emergency correction

Jeśli opublikowano potencjalnie szkodliwy błąd:

1. publisher może natychmiast zdjąć featured/breaking,
2. jeśli pozostawienie URL 200 jest ryzykowne, administrator używa Withdraw from public,
3. weryfikujemy źródło i zakres błędu,
4. przygotowujemy poprawę poza publicznym low-level Save,
5. dodajemy correction note, jeśli korekta jest istotna,
6. zapisujemy audit,
7. po review używamy Apply public update albo — po wcześniejszym Withdraw — Restore to review + Publish.

`Archive` stosujemy tylko wtedy, gdy historyczny 200 jest świadomie właściwym rezultatem, nie jako „tymczasowe ukrycie” fałszywej treści.

Nie czekamy na pełny cykl redakcyjny, jeśli błędna informacja jest publiczna.

---

## 35. Materiały sponsorowane

Poza v1, ale policy należy ustalić od początku.

Jeśli pojawią się:

- muszą być jawnie oznaczone,
- sponsor nie może ukrywać autorstwa reklamy,
- oznaczenie musi być widoczne przed treścią,
- relacje linków zgodne z polityką wyszukiwarek,
- materiał sponsorowany nie może być oznaczany jako niezależny report.

---

## 36. Konflikt interesów

Autor/reviewer powinien ujawnić wewnętrznie konflikt, jeśli:

- materiał dotyczy partnera biznesowego,
- materiał dotyczy OSK/firmy, z którą istnieje relacja,
- publikacja może wpłynąć na własny interes.

W razie potrzeby publiczna nota transparentności.

---

## 37. UGC / komentarze

Komentarze użytkowników nie wchodzą do newsroom v1.

Powód:

- moderacja,
- spam,
- ryzyko prawne,
- dodatkowy system zgłoszeń.

Jeśli wrócą do scope, wymagają osobnej specyfikacji.

---

## 38. Social copy

Tytuł artykułu nie musi być identyczny z copy social.

Social copy:

- nie może przeinaczać artykułu,
- nie może obiecywać informacji, której tekst nie zawiera,
- powinno podawać najważniejszy fakt.

---

## 39. Newsletter

Poza v1.

Gdy wejdzie:

- wyraźny opt-in,
- osobna polityka częstotliwości,
- nie mieszamy konta produktowego z marketing consent bez podstawy.

---

## 40. Retencja materiałów

Nie usuwamy starych newsów tylko dlatego, że są stare.

Stary news może mieć wartość archiwalną.

Usuwamy/noindex/410 tylko, gdy:

- materiał nigdy nie powinien być publiczny,
- jest duplikatem bez wartości,
- wymaga tego prawo,
- nie da się go naprawić i pozostawienie szkodzi użytkownikowi.

---

### 40.1. Anti-scaled-content / programmatic publishing policy

Automatyzacja nie może tworzyć indeksowalnych stron wyłącznie dlatego, że istnieje kombinacja słów kluczowych, taga, miasta albo rekordu w bazie.

Każdy nowy publiczny URL musi mieć:

- jasno określoną intencję użytkownika,
- samodzielną wartość informacyjną,
- źródła lub jawne pochodzenie danych,
- redakcyjnego ownera,
- możliwość utrzymania/freshness,
- sensowne linkowanie w strukturze serwisu.

Niedozwolone bez osobnej decyzji jakościowej:

- automatyczny tag -> public page,
- masowe WORD/miasto pages z szablonowym tekstem bez unikalnych danych,
- seryjne parafrazy jednego newsa,
- AI-generated articles publikowane bez własnego researchu/review,
- strony tworzone wyłącznie po to, by złapać wariant frazy.

AI i generowanie programmatic mogą przyspieszać workflow, ale nie zastępują kryterium unikalnej wartości.

---

## 41. KPI redakcyjne

Jakość:

- correction rate,
- time-to-correction,
- procent artykułów z primary source,
- procent artykułów po review,
- freshness backlog.

Dystrybucja:

- impressions/clicks,
- CTR,
- Discover exposure, jeśli występuje,
- returning readers.

Produkt:

- article -> question click,
- article -> test click,
- article -> registration,
- article -> learning entry.

Nie premiujemy autora tylko page views, ponieważ prowadzi to do clickbaitu.

---

## 42. SLA redakcyjne — punkt startowy

Nie są to zobowiązania zewnętrzne, tylko cele operacyjne.

- breaking correction: natychmiast po potwierdzeniu błędu,
- istotny błąd zwykłego artykułu: ten sam dzień,
- broken source/link: do kolejnego review lub szybciej dla primary source,
- freshness overdue legal explainer: priorytet wysoki.

---

## 43. Filament — wymagania operacyjne

Lista artykułów powinna pokazywać:

- status,
- typ,
- kategoria,
- autor,
- reviewer,
- published_at / scheduled_for,
- origin type,
- regulatory status,
- źródła count,
- featured/breaking,
- freshness due.

W formularzu powinny być ostrzeżenia:

- brak primary source dla prawnego newsa,
- regulatory status bez spójnego źródła/daty,
- origin_type niezgodny z realnymi źródłami,
- scheduled_for w przeszłości,
- breaking bez expiry,
- published bez hero alt, jeśli hero istnieje,
- brak powiązań produktu nie blokuje publish, ale powinien być widoczny.

Aktualny stan po N2-005: source editor, finalny backend source-policy gate oraz article-owned relations/topics editor są wdrożone. Osobny computed warning „brak primary source dla prawnego newsa” i warning „brak powiązań produktu” nadal nie istnieją jako publication-checklist UI; pozostają późniejszym zakresem N2-007/N2-010. Nie zmienia to powyższych zasad redakcyjnych.

---

## 44. Preview

Preview v1:

- wygląda możliwie identycznie jak publiczna strona,
- jest dostępne wyłącznie dla zalogowanego administratora,
- ma `Cache-Control: private, no-store`,
- ma noindex,nofollow,
- nie wchodzi do sitemap/feed,
- nie pojawia się w publicznych hubach,
- nie jest liczone jako public article view,
- nie ma shareable signed tokenu.

Jeśli kiedyś potrzebny będzie external reviewer preview, wymaga osobnego threat modelu, TTL/revocation i audytu.

Preview całego `/aktualnosci` powinien dodatkowo pozwalać wybrać przyszły czas i zobaczyć zaplanowane placements/fallbacki przed publikacją.

---

## 45. Audit trail

Audit używa istniejącego `AuditLog`.

Powinien odpowiedzieć:

- który `User` administrator utworzył,
- kto edytował,
- kto zmienił status,
- kto opublikował,
- kto zmienił slug,
- kto usunął źródło,
- kto ustawił breaking/featured,
- kiedy wykonano istotną korektę.

Author/reviewer to osobne `ContentAuthor` identities. Audit metadata przechowuje stan/IDs/timestamps/reason, nie pełny body, lead ani prywatne notatki.

---

## 46. Definition of Done procesu redakcyjnego

Proces jest gotowy, gdy:

- redaktor nie musi edytować kodu,
- źródła są pierwszoklasowym elementem CMS,
- preview istnieje,
- scheduling jest bezpieczny,
- corrections mają workflow,
- homepage placements mają jawne zasady redakcyjne,
- topics nie są automatycznymi stronami tagów,
- origin/provenance jest spójny ze źródłami,
- reviewer policy da się egzekwować,
- breaking wygasa,
- freshness backlog jest widoczny,
- każdy publish zostawia audit,
- checklisty są częściowo egzekwowane technicznie.

---

## 47. Stan implementacji

Na 2026-09-16:

- istnieją `ContentAuthor` i istniejący `AuditLog`; `User` pozostaje aktorem operacji, a `ContentAuthor` publiczną tożsamością autora/reviewera,
- backendowy `ContentArticlePublishingService` i `newsroom:publish-due` istnieją; po PR #50 Filament ma pełne N2-006 workflow/exposure actions oraz stale-safe `Apply public update` dla już publicznego `ContentArticle`; publication checklist i pełny correction flow nadal nie są wdrożone,
- istnieją `ContentCategoryResource` i `ContentArticleResource`; article CMS ma kontrolowany body Builder/RichEditor, relationship source editor oraz article-owned questions/legal/signs/topics editor,
- istnieją model `ContentArticleSource`, source types v1, private-evidence flag i source ordering; N2-004 dodaje ich edycję oraz finalną source-policy validation,
- draft może istnieć bez source, natomiast news nie przechodzi review/publish bez source; prywatny interview/direct evidence może mieć URL null,
- ordinary Edit publicznie widocznego artykułu nadal nie zmienia publicznych sources ani article relations/topics przez zwykły Save; jawny `Apply public update` zapisuje aktualnie zmaterializowany public editor payload atomowo z loaded-state guardem,
- publiczny renderer citation/relations, origin/regulatory UI, computed publication warnings, preview i HomeComposer nadal nie istnieją; N2-012 pozostaje otwarte tylko dla HomeComposer stale-write.

---

## 48. Pozostałe zadania

- [ ] wdrożyć admin-only policies bez rozszerzania panel access i spiąć AuditLog User actor,
- [ ] odwzorować mandatory checklist w walidacji,
- [ ] wdrożyć origin/regulatory governance w CMS,
- [ ] wdrożyć homepage placements i future home preview,
- [ ] wdrożyć topic governance,
- [ ] wdrożyć focal-point review,
- [ ] wdrożyć admin-only private/no-store preview,
- [x] N2-006: workflow/schedule/exposure actions + atomowy stale-safe `Apply public update` dla `ContentArticle`,
- [ ] N2-012: analogiczny stale-write guard dla `NewsroomHomeComposer` po N2-009,
- [ ] wdrożyć corrections,
- [ ] wdrożyć freshness filters,
- [ ] przygotować publiczną stronę zasad redakcyjnych przed większym rolloutem.

---

## 49. Historia zmian

### 2026-09-16 — v0.10

- PR #50 zmergowano na `main@570f884a89869ec44d24f57f0506f4444d20a7d2` po exact-head CI #190; `quality` i `newsroom-postgres` PASS,
- redaktor ma jawny `Apply public update` dla już publicznego artykułu; ordinary public Save pozostaje ograniczony do bezpiecznej wewnętrznej ścieżki,
- stale-write guard używa deterministycznego loaded-state tokenu obejmującego article, sources i article-owned relations/topics, więc konflikt nie kończy się last-write-wins nawet przy same-second child mutation,
- public update audytuje `User` actor bez pełnego body/lead/private notes i aktualizuje `last_substantive_update_at` tylko dla semantycznej zmiany,
- N2-006 jest DONE; HomeComposer stale-write pozostaje późniejszą częścią N2-012, a następnym taskiem jest N2-007 Publication checklist,
- correction governance pozostaje bez zmian: obecny `Apply public update` nie oznacza jeszcze kompletnego `Apply correction` UI ani zmaterializowanego `correction_note` flow.

### 2026-09-16 — v0.9

- PR #48 zmergowano na `main@88533b04d74a839c3bbccf86b707909ccdd235f8`; exact-head CI #176 przeszedł dla `quality` i `newsroom-postgres`,
- redaktor ma teraz kontrolowane Edit/View actions dla review/schedule/publish/archive/withdraw/restore/republish oraz featured/breaking,
- withdraw nadal wymaga jawnego reason, restore nie usuwa tombstone przed skutecznym publish, a breaking wymaga published news z przyszłym expiry,
- exposure changes zapisują allowlisted audit zamiast pełnej treści,
- ordinary public Save pozostaje zablokowany; `Apply public update` i stale-write guard nadal są otwartym N2-012,
- governance nie uznaje N2-006 za DONE, dopóki ta zależność nie zostanie zmaterializowana.

### 2026-09-16 — v0.8

- zsynchronizowano bieżący stan governance z kodem po N2-005 bez zmiany source hierarchy, topic governance ani zasad publicznej kolejności,
- article CMS umożliwia teraz zarządzanie article-owned questions/legal/signs/topics relations; topics pozostają bez ręcznego rankingu, a target entities nie są mutowane przez sync,
- ordinary public Edit nie zmienia relations/topics; publiczne renderowanie relacji nadal pozostaje otwarte,
- warning „brak powiązań produktu” nadal jest przyszłym computed checklist item i nie jest fałszywie deklarowany jako wdrożony,
- finalny exact-head gate N2-005: `quality` 972 passed / 18 940 assertions / 2 skipped, Pint 1011 files PASS, frontend build PASS (9.49 s); `newsroom-postgres` 7 passed / 89 assertions.

### 2026-09-16 — v0.7

- zsynchronizowano bieżący stan governance z kodem po N2-004 bez zmiany source hierarchy ani standardów redakcyjnych,
- istniejący `ContentArticleSource` jest teraz pierwszoklasowo edytowany w article CMS; private evidence, nullable URL i source ordering są zachowane,
- backend finalnie waliduje source policy przed review/publish, ale redakcyjny wymóg szukania jawnego Tier 1 oraz computed warning o braku primary source pozostają niezmienioną policy i nie są deklarowane jako automatycznie wdrożone,
- potwierdzono, że backend workflow/scheduler istnieje, natomiast Filament workflow/checklist/correction UI nadal pozostaje otwarte,
- finalny exact-head gate N2-004: `quality` 968 passed / 18 917 assertions / 2 skipped, Pint 1010 files PASS, frontend build PASS; `newsroom-postgres` 7 passed / 89 assertions.

### 2026-09-16 — v0.6

- rozdzielono public citation od internal evidence i dopuszczono wiarygodne źródła bez URL bez wycieku prywatnych danych.

### 2026-09-16 — v0.5

- rozdzielono pojęciowe role redakcyjne od auth/RBAC; v1 Filament pozostaje admin-only,
- ContentAuthor reviewer/author oddzielono od User actora w AuditLog,
- preview v1 zamknięto do authenticated admin + private,no-store,
- archive otrzymało deterministyczną historyczną semantykę 200, a osobny withdrawn obsługuje jawny takedown 410,
- checklistę in-review wyrównano do body_blocks.

### 2026-09-16 — v0.4

- dodano rozdzielenie hero alt/caption/credit,
- dodano seo_title vs H1 editorial guard,
- zapisano stabilność route family po pierwszej publikacji,
- potwierdzono, że newsroom links do pytań nie tworzą drugiej taksonomii/rankingu.

### 2026-09-16 — v0.3

- dodano anti-scaled-content policy dla tags/topics/local WORD/AI,
- rozszerzono checklistę o OG alt, stabilne publiczne asset URLs i publiczny profil autora,
- doprecyzowano, że automatyzacja nie zastępuje unikalnej wartości redakcyjnej.

### 2026-09-15 — v0.2

- dodano jawny model pochodzenia materiału i zasady publicznej atrybucji,
- dodano governance dla homepage placements, deduplikacji i topic/dossier,
- rozszerzono media review o focal point i crop preview,
- opisano przyszłe audio/AI jako pochodne zatwierdzonego artykułu, poza v1,
- nie dodano procesu revision snapshot/diff/restore zgodnie z decyzją produktową.

### 2026-09-15 — v0.1

- utworzono operacyjny standard redakcyjny,
- zdefiniowano role i RACI,
- zdefiniowano source hierarchy,
- dodano correction/update/breaking/freshness policies,
- opisano AI policy, publikacyjne checklisty i emergency correction.
