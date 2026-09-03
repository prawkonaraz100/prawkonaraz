# Adaptive Learning Architecture

## 1. Cel

Ten dokument spina dwa osobne systemy:

- `Trener pamieci`
- `Question Explanation Visual System`

Jego celem jest odpowiedziec na pytanie:

- jak algorytm review ma korzystac z warstwy wyjasnien,
- kiedy pokazujemy jaki typ pomocy,
- jak wynik tej pomocy wraca do modelu pamieci usera.

To nie jest zamiennik dla dokumentow domenowych.

Ten dokument jest warstwa integracyjna pomiedzy:

- [PREMIUM-REVIEW-TRAINER-ROADMAP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PREMIUM-REVIEW-TRAINER-ROADMAP.md)
- [QUESTION-EXPLANATION-VISUAL-SYSTEM.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/QUESTION-EXPLANATION-VISUAL-SYSTEM.md)

## 1.1 Kolejnosc wdrozenia i zaleznosci

Ten dokument nie jest pierwszym krokiem wdrozeniowym.

Rekomendowana kolejnosc jest taka:

1. `QUESTION-EXPLANATION-VISUAL-SYSTEM.md`
2. `PREMIUM-REVIEW-TRAINER-ROADMAP.md`
3. `ADAPTIVE-LEARNING-ARCHITECTURE.md`

Powod:

- ten dokument zaklada, ze istnieje juz przynajmniej MVP warstwy wyjasnien wizualnych,
- zaklada tez, ze `Trener pamieci` ma juz osobny planner albo przynajmniej jawny punkt decyzyjny, do ktorego da sie podpiac interwencje,
- bez explainera ten dokument bylby tylko teoria o przyszlych interwencjach,
- bez planera review ten dokument nie mialby silnika, ktory wybiera kiedy i komu te interwencje pokazac.

W praktyce:

- bazowy fundament explainera jest juz realnie dostepny w kodzie:
  - blok referencyjny,
  - globalny toggle,
  - lokalny toggle w sesji,
- mamy juz tez bazowe annotations:
  - `question_explanation_annotations`,
  - overlaye `label / circle`,
  - formularzowe zarzadzanie w adminie,
- nadal nie mamy jeszcze bogatszej taxonomii interwencji,
- planner `Trenera pamieci` tez nie jest jeszcze w wersji premium,
- ten dokument powinien wejsc szerzej dopiero wtedy, gdy oba poprzednie fundamenty sa juz rozbudowane ponad obecny baseline.

## 2. Werdykt

Premium produkt edukacyjny nie powstanie z samego scheduleru powtorek ani z samego explainera.

Prawdziwa przewaga powstaje dopiero wtedy, gdy:

1. `Trener pamieci` wykrywa, czego user jeszcze nie umie.
2. `System wyjasnien` dobiera najlepszy sposob nauczenia tego konkretnego problemu.
3. Efekt tej interwencji wraca do modelu pamieci i poprawia kolejne decyzje algorytmu.

Czyli:

- `review trainer` decyduje `co i kiedy`,
- `explanation system` decyduje `jak wytlumaczyc`,
- `adaptive learning architecture` decyduje `jak te dwa systemy wspolpracuja`.

## 3. Granice odpowiedzialnosci

### 3.1 Trener pamieci

Odpowiada za:

- dobor pytania,
- priorytet kolejki,
- plan dzienny,
- batch,
- ocenę odpowiedzi,
- aktualizacje modelu pamieci,
- polityke ponownego powrotu pytania.

Nie odpowiada za:

- sam sposob wizualnego tlumaczenia,
- tresc explanation,
- layout kart edukacyjnych,
- overlaye i bloki znaku.

### 3.2 System wyjasnien

Odpowiada za:

- tekst explanation,
- blok referencyjny znaku / ilustracji,
- adnotacje na medium,
- warianty wyjasnien,
- warstwe dydaktyczna po odpowiedzi i w review.

Nie odpowiada za:

- kolejnosc pytan,
- priorytet due queue,
- polityke interwalow,
- planowanie batcha.

### 3.3 Warstwa integracyjna

Odpowiada za:

- wybor poziomu pomocy po odpowiedzi,
- mapowanie typu bledu na typ wyjasnienia,
- zbieranie sygnalow, czy interwencja pomogla,
- przekazywanie tych sygnalow z powrotem do silnika review.

## 4. Model produktu

Docelowy produkt nie jest juz tylko:

- baza pytan,
- player sesji,
- wynik.

Docelowy produkt jest:

- `adaptive coach`

ktory wykonuje petle:

1. wybierz pytanie
2. zbierz odpowiedz
3. zdiagnozuj rodzaj problemu
4. dobierz interwencje edukacyjna
5. zapisz wynik interwencji
6. zaktualizuj plan kolejnej nauki

## 5. Petla uczenia

```text
Question selection
-> answer event
-> memory diagnosis
-> intervention selection
-> explanation delivery
-> post-answer signal capture
-> progress update
-> next review scheduling
```

To jest podstawowa petla premium trenera.

## 6. Typy problemow, ktore system ma rozpoznawac

To jest klucz do polaczenia obu specyfikacji.

Silnik review nie powinien widziec tylko:

- `dobrze`
- `zle`

Powinien rozpoznawac bardziej uzyteczne klasy problemu:

### 6.1 Brak rozpoznania elementu na medium

Przyklad:

- user nie zauwaza znaku,
- nie rozpoznaje tabliczki,
- nie widzi istotnego pasa ruchu,
- nie wychwytuje kluczowego momentu na filmie.

Najlepsza interwencja:

- overlay na medium,
- marker,
- highlight,
- anotowany kadr.

### 6.2 Brak zrozumienia samego znaku lub symbolu

Przyklad:

- user myli znaczenie znaku,
- nie rozumie tabliczki,
- nie odroznia podobnych znakow.

Najlepsza interwencja:

- blok referencyjny znaku,
- obraz po lewej + explanation po prawej,
- porownanie podobnych znakow.

### 6.3 Brak zrozumienia reguly

Przyklad:

- user zna obraz, ale nie wie jaka zasade zastosowac,
- nie rozumie pierwszenstwa,
- nie rozumie logiki pytania.

Najlepsza interwencja:

- krotkie explanation tekstowe,
- regula,
- kontrprzyklad,
- rozpisanie "dlaczego nie B".

### 6.4 Odpowiedz trafiona, ale niestabilna

Przyklad:

- poprawna odpowiedz,
- ale bardzo wolna,
- albo po serii wczesniejszych pomylek.

Najlepsza interwencja:

- lekki feedback,
- brak ciezkiego explainera,
- szybszy powrot pytania w przyszlosci.

### 6.5 Pytanie stale problematyczne

Przyklad:

- user regularnie wraca do tego samego bledu,
- pomimo kilku odpowiedzi nadal nie utrwala pytania.

Najlepsza interwencja:

- mocniejsza warstwa wyjasnienia,
- dodatkowy material wizualny,
- status `leech`,
- specjalna polityka relearn.

## 7. Docelowy model interwencji

System nie powinien miec tylko `pokaz explanation` albo `nie pokazuj`.

Docelowo powinny istniec poziomy interwencji:

### Poziom 0 - Brak interwencji

Uzywany, gdy:

- user odpowiedzial stabilnie i szybko,
- pytanie nie wymaga dodatkowego nauczania.

### Poziom 1 - Minimalny hint

Uzywany, gdy:

- odpowiedz byla poprawna, ale wolna,
- albo pytanie jest jeszcze mlode w cyklu review.

Forma:

- krotki tekst,
- delikatna podpowiedz,
- bez ciezkiej warstwy wizualnej.

### Poziom 2 - Pelne explanation tekstowe

Uzywany, gdy:

- user potrzebuje reguly,
- ale nie ma jeszcze silnej potrzeby overlayu.

### Poziom 3 - Explanation + blok referencyjny

Uzywany, gdy:

- problem dotyczy samego znaku, symbolu lub przepisu wizualnego.

### Poziom 4 - Explanation + warstwa wizualna na medium

Uzywany, gdy:

- problem dotyczy rozpoznania elementu w scenie.

### Poziom 5 - Interwencja intensywna

Uzywany, gdy:

- pytanie jest leech,
- user wraca do tego samego bledu,
- potrzebne jest relearning + ciezsza pomoc wizualna.

## 8. Kiedy uruchamiamy jaka interwencje

### Regula bazowa v1

W pierwszych iteracjach nie potrzebujemy ML.

Wystarczy deterministiczna polityka:

- bledna odpowiedz + pytanie ma visual explanation asset
  -> poziom 3 albo 4
- bledna odpowiedz + pytanie bez assetow wizualnych
  -> poziom 2
- poprawna ale bardzo wolna
  -> poziom 1
- pytanie ze slabym streak i wysokim incorrect count
  -> poziom 3+ przy kolejnej porazce

### Regula v2

Po dodaniu event logu:

- mozemy mierzyc, po jakiej interwencji pytanie rzeczywiscie przestaje wracac,
- i na tej podstawie dobierac silniejsza lub slabsza pomoc.

## 9. Jak wynik interwencji wraca do trenera

To jest najwazniejszy punkt integracji.

Jesli pokazemy explanation albo overlay, to system powinien to odnotowac.

Nie chodzi o karanie usera.
Chodzi o zrozumienie:

- czy user potrzebowal pomocy,
- jakiej pomocy potrzebowal,
- czy ta pomoc poprawila retencje.

Potrzebujemy zapisywac:

- czy pokazano explanation,
- jaki poziom interwencji byl uzyty,
- czy pokazano blok referencyjny,
- czy pokazano overlay,
- czy pytanie zostalo pozniej rozwiazane poprawnie,
- po ilu dniach i z jaka jakoscia.

To powinno trafic do event logu review.

## 10. Nowe zdarzenia i sygnaly

Poza zwyklym `answer event` potrzebujemy:

- `intervention_presented`
- `intervention_dismissed`
- `intervention_completed`
- `question_recovered_after_intervention`
- `question_failed_again_after_intervention`

To pozwoli mierzyc nie tylko sam błąd, ale skutecznosc pomocy.

## 11. Integracja z modelem danych

### 11.1 `user_question_progress`

Model progresu pozostaje glownym stanem pytania.

Docelowo moze miec pochodne pola typu:

- `preferred_intervention_kind`
- `last_intervention_level`
- `last_intervention_at`
- `intervention_helpfulness_score`

Tych pol nie trzeba dodawac w MVP.
Wystarczy, ze beda zapisywane w event logu.

### 11.2 `review_events`

Tabela `review_events` powinna byc miejscem integracji obu systemow.

Musi umiec zapisac:

- answer snapshot,
- memory snapshot,
- intervention snapshot,
- algorithm version,
- planner version.

### 11.3 Assets i annotations

`Question Explanation Visual System` dalej trzyma:

- assety referencyjne,
- adnotacje,
- bloki wizualne,
- explanation stack.

Warstwa adaptive learning tylko decyduje:

- czy je odpalic,
- kiedy,
- z jakim priorytetem.

## 12. Architektura serwisow

Docelowo potrzebujemy takiego podzialu:

### `ReviewPlannerService`

Wybiera:

- jakie pytania trafic maja do batcha.

### `RecallScoringService`

Liczy:

- jakosc odpowiedzi i sygnaly pamieciowe.

### `LearningInterventionService`

Decyduje:

- jaki poziom interwencji pokazac po odpowiedzi.

To jest glowny serwis spinajacy review z explanation systemem.

### `ExplanationPresentationService`

Buduje payload dla frontendu:

- tekst,
- blok referencyjny,
- overlay,
- poziom interwencji,
- flagi widocznosci.

### `ReviewTelemetryService`

Zapisuje:

- zdarzenia,
- skutecznosc interwencji,
- porownania polityk.

## 13. Jak to powinno wygladac w UX

### 13.1 W sesji

User nie powinien widziec calej zlozonosci systemu.

Powinien czuc tylko:

- pytanie,
- odpowiedz,
- szybka sensowna pomoc,
- plynny rytm,
- zaufanie do trenera.

### 13.2 W review po sesji

Review powinno byc bogatsze niz aktywna sesja.

To tam mozemy pokazac:

- pelny explanation stack,
- pelne assety wizualne,
- porownania,
- oznaczone pułapki pytania.

### 13.3 W Trenerze pamieci

Produkt ma komunikowac:

- "wiemy, co masz robic teraz",
- "wiemy, czego jeszcze nie utrwaliles",
- "wiemy, jaka pomoc daje Ci najwieksza szanse zapamietania".

## 14. Fazy integracji

### Faza A - Integracja lekka

Cel:

- po prostu uruchomic warstwe wyjasnien po bledzie w review trainerze.

Zakres:

- bledna odpowiedz -> pokaz explanation
- jesli istnieje blok referencyjny -> pokaz blok
- bez inteligentnego doboru poziomu

To jest teraz realny pierwszy krok integracji, bo blok referencyjny jest juz wdrozony.

### Faza B - Integracja sterowana regułami

Cel:

- dobierac poziom interwencji na podstawie typu bledu.

Zakres:

- proste deterministiczne mapowanie:
  - symbol confusion
  - media miss
  - rule misunderstanding

### Faza C - Integracja adaptacyjna

Cel:

- mierzyc skutecznosc interwencji i dobierac lepsza pomoc.

Zakres:

- event log
- intervention telemetry
- adaptacja polityki do usera

## 15. Sprint 1 integracyjny

Pierwszy sprint integracji obu systemow powinien byc bardzo waski.

### Cel Sprintu 1

Po blednej odpowiedzi w `Trenerze pamieci` pokazac sensowna pomoc edukacyjna bez psucia rytmu sesji.

### Zakres Sprintu 1

1. `Trener pamieci` potrafi sprawdzic, czy pytanie ma:
   - pelne explanation,
   - blok referencyjny,
   - visual explanation asset.
2. Przy blednej odpowiedzi odpala deterministyczna polityke:
   - jesli jest blok referencyjny -> pokaz explanation + blok
   - jesli nie ma bloku -> pokaz pelne explanation
3. Zdarzenie odpowiedzi zapisuje:
   - `intervention_level`
   - `intervention_kind`
4. Front respektuje przyszly globalny toggle wizualnych objasnien.

### Poza zakresem Sprintu 1

- timed overlays na video
- rozpoznawanie typu bledu przez ML
- eksperymenty A/B
- automatyczne dostrajanie polityki do usera

### Definition of Done

- `Trener pamieci` umie juz odpalac warstwe dydaktyczna,
- explanation system nie jest osobnym bytem, tylko czescia petli uczenia,
- dane o interwencji sa gotowe do pozniejszej analityki.

Na dzis za gotowy fundament do tego sprintu uznajemy:

- istniejacy blok referencyjny,
- istniejace respektowanie `visual_explanations_enabled`,
- render tej warstwy w zwyklej sesji i w wyniku egzaminu.

Status implementacyjny po sprincie `sr_review reveal hydration`:

- aktywny `sr_review` nadal nie dostaje `correct_answer`, `explanation`, `explanation_asset` ani `explanation_annotations` przed odpowiedzia,
- po odpowiedzi webowy flow czyta tekst wyjasnienia, blok referencyjny i adnotacje z answer reveal,
- API answer response dla `sr_review` zwraca reveal dla wlasnie ocenionego pytania,
- nastepne pytanie w web/API nadal pozostaje bez reveal.

To oznacza, ze punkt "bledna odpowiedz -> pokaz explanation" ma juz techniczny fundament bez psucia roli trenera jako weryfikacji pamieci.

## 16. Najwieksze ryzyko

Najwiekszym bledem byloby zbudowanie:

- swietnego review scheduleru bez realnej warstwy nauczania,
  albo
- swietnych explainers bez powiazania z modelem pamieci.

To daloby dwa dobre moduly, ale nie daloby premium trenera.

Premium przewaga powstaje dopiero z ich polaczenia.

## 17. Decyzja kanoniczna

Przyjmujemy, ze:

- `Trener pamieci` i `Question Explanation Visual System` pozostaja osobnymi domenami,
- nie scalamy ich w jeden wielki dokument ani jeden wielki serwis,
- laczymy je przez warstwe `adaptive learning`,
- `review_events` beda glownym kontraktem analitycznym miedzy nimi,
- poziom interwencji edukacyjnej ma byc jawna decyzja systemu, nie przypadkowym efektem ubocznym UI.
