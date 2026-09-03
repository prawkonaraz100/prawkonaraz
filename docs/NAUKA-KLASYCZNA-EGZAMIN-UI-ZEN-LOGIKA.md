# Nauka Klasyczna: Egzamin UI + Zen Logika

## Cel

Ten dokument opisuje docelowy model produktu dla trybu `Nauka klasyczna`.

Ma byc jednym zrodllem prawdy dla:

- tego, co chcemy osiagnac,
- gdzie jestesmy dzisiaj,
- czego nie wolno popsuc,
- jak rozdzielic `wyglad egzaminu` od `logiki nauki`,
- jak wdrozyc ten tryb bez regresji w `Zen mode` i `Egzaminie`.

Ten dokument nie jest jeszcze implementacja.
To jest spec produktu + mapa architektury dla kolejnych zmian.

## Executive Summary

Chcemy, aby `Nauka klasyczna` byla:

- wizualnie bardzo bliska trybowi `Egzamin`,
- funkcjonalnie rowna `Zen mode`,
- osobnym trybem produktu,
- bez psucia aktualnego `Egzaminu`,
- bez psucia aktualnego `Zen mode`.

Najwazniejsza zasada:

`Nauka klasyczna` nie moze byc ani oslabionym `Egzaminem`, ani tylko przemalowanym `Zen mode`.

To ma byc:

`egzaminowy shell + pelna logika nauki`.

## Problem, ktory rozwiazujemy

Dzisiaj uzytkownik widzi na ekranie `/nauka` 3 glowne kierunki:

- `Nauka klasyczna`
- `Zen mode`
- `Egzamin`

Ale semantycznie `Nauka klasyczna` nie jest jeszcze precyzyjnie zdefiniowana.

Biznesowo chcemy, aby:

- `Egzamin` dawal wierna symulacje warunkow panstwowych,
- `Zen mode` dawal najwygodniejszy, najbardziej wspierajacy tryb nauki,
- `Nauka klasyczna` dawala wyglad i rytm egzaminu, ale bez zabierania wygodnych funkcji nauki.

To oznacza, ze musimy rozdzielic:

1. mechanike sesji,
2. shell wizualny,
3. zestaw funkcji pomocniczych.

## Stan obecny

### Ekran wyboru trybu

Preset i opisy siedza w:

- [C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Index.vue)

Aktualnie w UI mamy:

- `Nauka klasyczna`
- `Zen mode`
- `Tryb rankingowy`
- `Egzamin`

### Aktywna sesja nauki

Glowne UI nauki siedzi w:

- [C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)
- [C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Layouts/SessionZenLayout.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Layouts/SessionZenLayout.vue)

To jest dzisiaj glowny player nauki i to w nim siedza funkcje, ktore traktujemy jako `pakiet Zen`.

### Aktywny egzamin

Osobny ekran egzaminu siedzi w:

- [C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Exam.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Exam.vue)
- [C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Layouts/SessionExamLayout.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Layouts/SessionExamLayout.vue)

Render rozgalezia sie w:

- [C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php)

Aktualna zasada:

- `mode = exam` -> komponent `StudySessions/Exam`
- pozostale sesje -> komponent `StudySessions/Show`

## Co chcemy osiagnac

Docelowo `Nauka klasyczna` ma spelniac jednoczesnie 2 warunki:

1. Ma wygladac jak egzamin:
- techniczny header,
- egzaminowy uklad medium,
- egzaminowy rytm top bar / tresc / odpowiedzi,
- egzaminowa kompozycja i hierarchia.

2. Ma zachowac pelny pakiet nauki:
- feedback jak w nauce,
- explanation i uzasadnienia,
- wygodne przejscia,
- ustawienia nauki,
- funkcje wspierajace zapamietywanie,
- brak restrykcji egzaminacyjnych.

To oznacza:

- `Egzamin` zachowuje realizm i ograniczenia,
- `Zen mode` zachowuje spokojny shell i obecne UX nauki,
- `Nauka klasyczna` staje sie `Exam UI shell + Learn/Zen feature set`.

## Czego nie wolno popsuc

### 1. Egzamin

Nie wolno:

- mieszac do `Egzaminu` funkcji pomocniczych z nauki,
- rozmywac jego restrykcji,
- przenosic do niego explainera, review flow i szkoleniowego feedbacku,
- rozwalic jego osobnego layoutu i logiki czasu.

### 2. Zen mode

Nie wolno:

- zabierac `Zen mode` jego spokojnego shellu,
- uzalezniac go od widoku egzaminu,
- wypychac go technicznym headerem egzaminowym.

### 3. Nauka klasyczna

Nie wolno zrobic z niej:

- fake egzaminu z restrykcjami,
- kopii `Zen mode` z kosmetycznym paskiem na gorze,
- trzeciego monolitu trudnego do utrzymania.

## Definicja trybow po zmianie

### Zen mode

Przeznaczenie:

- najspokojniejsza nauka,
- minimalny noise,
- maksymalne wsparcie zapamietywania.

Wyglad:

- obecny spokojny shell nauki.

Logika:

- obecna logika nauki.

### Egzamin

Przeznaczenie:

- realistyczna symulacja egzaminu panstwowego.

Wyglad:

- egzaminowy shell.

Logika:

- egzaminowa.

### Nauka klasyczna

Przeznaczenie:

- codzienny trening w ukladzie egzaminu.

Wyglad:

- egzaminowy shell.

Logika:

- logika nauki, nie logika egzaminu.

To jest kluczowy kontrakt produktu:

`Nauka klasyczna = nie egzamin, tylko nauka w skorze egzaminu`.

## Co z Zen mode przenosimy do Nauki klasycznej

Pelnym celem jest zachowanie wszystkich najwazniejszych funkcji treningowych.

Do przeniesienia:

- feedback naukowy po odpowiedzi,
- wyjasnienia / explanation panel,
- review-friendly flow,
- ustawienia sesji,
- preferencje usera,
- wszystkie pomocnicze funkcje video i autoplay z nauki,
- brak twardych ograniczen egzaminacyjnych,
- mozliwosc pozostania w treningowym rytmie, a nie tylko w rytmie testu panstwowego.

Jesli w `Zen mode` istnieja funkcje stricte wspierajace nauke, to `Nauka klasyczna` ma je zachowac.

Inaczej mowiac:

`Nauka klasyczna` ma stracic tylko shell wizualny `Zen mode`, a nie jego mozliwosci.

## Co z Egzaminu bierzemy tylko wizualnie

Do przeniesienia tylko jako prezentacja:

- layout top bara,
- techniczny charakter headera,
- proporcje medium,
- uklad pytania i odpowiedzi,
- hierarchia informacji na ekranie,
- egzaminowy rytm strony.

Nie przenosimy automatycznie:

- twardych timerow egzaminacyjnych,
- braku cofania,
- wymuszonego flow `preview/media/answer`,
- traktowania braku odpowiedzi jak bledu z timeoutem,
- logiki sekcji `basic/specialist`,
- progu zdawalnosci,
- egzaminowego wyniku.

## Kluczowa decyzja architektoniczna

Musimy rozdzielic:

1. `session mode`
2. `ui shell`
3. `feature pack`

### Session mode

To jest backendowa prawda o typie sesji.

Przyklad:

- `learn`
- `exam`

### UI shell

To jest tylko sposob prezentacji.

Przyklad:

- `zen`
- `exam_like`

### Feature pack

To jest zestaw aktywnych zachowan produktu.

Przyklad:

- `learn_features`
- `exam_features`

## Docelowy model dla Nauki klasycznej

Najbezpieczniejszy wariant:

- backendowy `mode` zostaje `learn`
- UI shell ustawiamy na `exam_like`
- feature pack ustawiamy na `learn_features`

To daje nam:

- brak mieszania Nauki klasycznej z prawdziwym `exam`
- brak ryzyka, ze backend zacznie traktowac sesje jak egzamin
- mozliwosc zachowania wszelkich funkcji nauki

## Czego nie robic

### Nie robic tego przez kopie-paste

Nie kopiowac calego `Exam.vue` i nie dokladac do niego po trochu funkcji nauki.

To by stworzylo drugi duzy monolit, ktory szybko sie rozjedzie z:

- `Show.vue`
- `Exam.vue`

### Nie spinac tego na samym CSS

Nie wystarczy "przebrane Show.vue".

Jesli shell ma byc egzaminowy, to kompozycja musi byc swiadoma:

- header,
- polozenie medium,
- uklad odpowiedzi,
- prawa lub boczna organizacja metadanych.

### Nie mapowac Nauki klasycznej na `exam`

To by automatycznie wciagnelo:

- zly flow,
- zle zasady czasu,
- zle ograniczenia,
- zly wynik koncowy.

## Gdzie jestesmy teraz

Na ten moment:

- mamy osobny shell `Egzaminu`,
- mamy osobny shell `Zen mode`,
- `Nauka klasyczna` istnieje produktowo jako preset na `/nauka`,
- ale nie ma jeszcze osobnego, swiadomie zaprojektowanego shellu `exam_like + learn`.

Czyli:

- produktowo wiemy, czego chcemy,
- technicznie mamy juz dwa punkty odniesienia,
- brakuje nam trzeciego, swiadomie zaprojektowanego wariantu.

## Proponowany kierunek wdrozenia

### Etap 1. Rozdzielenie logiki od shellu

Najpierw trzeba jasno oddzielic:

- logike nauki z [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)
- od wizualnej prezentacji gracza.

Docelowo logika nauki nie powinna byc przyspawana do jednego layoutu.

### Etap 2. Wydzielenie shellu exam_like dla nauki

Potrzebujemy nowego wariantu wizualnego dla sesji `learn`.

Robocza nazwa:

- `StudySessions/LearnExamShell.vue`
lub
- wariant shellu sterowany flaga `ui_shell = exam_like`

### Etap 3. Podpiecie Nauki klasycznej

Na ekranie `/nauka` preset `Nauka klasyczna` powinien startowac sesje:

- `mode = learn`
- `ui_shell = exam_like`
- `feature_pack = learn_features`

### Etap 4. Zachowanie Zen mode

`Zen mode` dalej:

- `mode = learn`
- `ui_shell = zen`
- `feature_pack = learn_features`

### Etap 5. Zachowanie Egzaminu

`Egzamin` dalej:

- `mode = exam`
- `ui_shell = exam`
- `feature_pack = exam_features`

## Rekomendowany model komponentowy

Docelowo powinnismy miec:

- wspolna logike sesji nauki
- rozne shelle wizualne
- rozne preset mappings

Najbardziej zdrowy kierunek:

1. wydzielic wspolne state i operacje z `Show.vue`
2. wydzielic wspolne bloki typu:
   - media stage
   - prompt
   - answers
   - explanation
   - session actions
3. skladac z nich dwa warianty nauki:
   - `Zen mode`
   - `Nauka klasyczna`

## Acceptance Criteria

Uznamy ten kierunek za dowieziony, jesli:

1. `Nauka klasyczna` wyglada jak egzamin.
2. `Nauka klasyczna` zachowuje wszystkie funkcje nauki z `Zen mode`.
3. `Zen mode` po wdrozeniu nie zmienia swojego zachowania.
4. `Egzamin` po wdrozeniu nie zmienia swojego zachowania.
5. Kod nie dubluje bez potrzeby calej logiki w 3 osobnych plikach.
6. Zmiana shellu nie zmienia backendowego typu sesji `learn`.

## Ryzyka

Najwieksze ryzyka:

- przypadkowe wciagniecie restrykcji `exam` do `learn`,
- skopiowanie zbyt duzej ilosci logiki i szybki rozjazd ekranow,
- za slabe rozdzielenie `shell` vs `behavior`,
- nadmierne skomplikowanie kontrolera i payloadu sesji.

## Najblizszy kolejny krok

Przed kodowaniem trzeba jeszcze doprecyzowac jeden punkt:

`czy Nauka klasyczna ma zachowac 100% funkcji Zen mode, czy tylko wybrane funkcje z pakietu nauki`.

Domyslna interpretacja z obecnej rozmowy:

- ma zachowac pelny pakiet funkcji Zen mode,
- zmieniamy tylko shell i kompozycje wizualna.

To jest aktualnie nasz kierunek referencyjny.

## Task list wdrozeniowy 1:1 na pliki

Ponizej jest konkretna mapa prac.

To nie sa jeszcze commity ani patche produktu.
To jest wykonawcza checklista dla kolejnych etapow.

### Etap 0. Zamrozenie kontraktu produktu

Cel:

- upewnic sie, ze wszyscy rozumieja ten sam kontrakt dla trybow.

Pliki:

- [C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/NAUKA-KLASYCZNA-EGZAMIN-UI-ZEN-LOGIKA.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/NAUKA-KLASYCZNA-EGZAMIN-UI-ZEN-LOGIKA.md)

Taski:

1. Zatwierdzic, ze `Nauka klasyczna` to osobny tryb produktu.
2. Zatwierdzic, ze `Nauka klasyczna` zachowuje pelny pakiet funkcji `Zen mode`.
3. Zatwierdzic, ze `Egzamin` i `Zen mode` nie zmieniaja swojej logiki.

### Etap 1. Wprowadzenie jawnego preset modelu

Cel:

- przestac traktowac preset jako sam kosmetyczny label na `/nauka`,
- przekazac dalej do sesji informacje, jaki shell ma byc uzyty.

Pliki:

- [C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Index.vue)
- [C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Requests/StudySessionStoreRequest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Requests/StudySessionStoreRequest.php)
- [C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php)

Taski:

1. Doprecyzowac presety startowe:
   - `learn_classic`
   - `zen`
   - `exam`
2. Nie mapowac `learn_classic` na backendowy `exam`.
3. Dla startu sesji przekazywac jawne pole typu:
   - `ui_shell`
   - albo `session_preset`
4. Trzymac backendowy `mode = learn` dla `learn_classic` i `zen`.

Rezultat etapu:

- backend wie, czy sesja `learn` ma byc pokazana jako `zen`, czy jako `exam_like`.

### Etap 2. Uporzadkowanie payloadu sesji

Cel:

- uniezaleznic render aktywnej sesji od zgadywania tylko po `mode`.

Pliki:

- [C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionManager.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/StudySessionManager.php)
- [C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php)

Taski:

1. Przy starcie sesji `learn` zapisac w payload:
   - `ui_shell = zen` albo `exam_like`
   - `feature_pack = learn`
2. Przy starcie sesji `exam` zapisac:
   - `ui_shell = exam`
   - `feature_pack = exam`
3. W kontrolerze renderu uzywac:
   - `mode`
   - `ui_shell`
   - `feature_pack`
   zamiast samego `mode`.

Rezultat etapu:

- system nie myli sesji nauki z egzaminem tylko dlatego, ze shell wyglada podobnie.

### Etap 3. Rozdzielenie logiki nauki od layoutu

Cel:

- przestac miec cala logike `learn` przyspawana do jednego wygladu.

Pliki:

- [C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)

Taski:

1. Wydzielic z `Show.vue` wspolne state i akcje nauki.
2. Wydzielic czesci typu:
   - aktywne pytanie,
   - odpowiedzi,
   - explanation,
   - przejscie do kolejnego pytania,
   - preferencje sesji,
   - zachowanie video.
3. Zostawic shell `Zen mode` jako jeden z konsumentow tej logiki, a nie jej jedyne miejsce zycia.

Rekomendowane kierunki:

- `useLearnSessionPlayer`
- `useLearnSessionPreferences`
- `useLearnSessionMedia`

Rezultat etapu:

- logika nauki jest wspolna,
- shell jest wymienny.

### Etap 4. Zbudowanie shellu `Nauka klasyczna`

Cel:

- dodac nowy ekran lub nowy wariant ekranu dla `learn + exam_like`.

Pliki:

- nowy komponent, np.:
  - [C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/LearnClassic.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/LearnClassic.vue)
  lub
  - nowy layout typu [C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Layouts/SessionLearnExamLayout.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Layouts/SessionLearnExamLayout.vue)
- opcjonalnie wspolne child komponenty:
  - `SessionLearnExamHeader.vue`
  - `SessionMediaStage.vue`
  - `SessionAnswerBlock.vue`
  - `SessionExplanationPanel.vue`

Taski:

1. Przeniesc do tego shellu wizualny jezyk egzaminu:
   - techniczny header
   - proporcje medium
   - uklad pytania
   - uklad odpowiedzi
2. Nie przenosic do niego restrykcji egzaminu.
3. Zachowac trainingowy flow odpowiedzi i explainera.

Rezultat etapu:

- `Nauka klasyczna` wyglada jak egzamin,
- ale zachowuje behavior nauki.

### Etap 5. Podpiecie routingu i renderu

Cel:

- poprawnie wybrac komponent aktywnej sesji.

Pliki:

- [C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/StudySessionController.php)

Taski:

1. Zostawic:
   - `exam + exam shell` -> `StudySessions/Exam`
2. Dodac:
   - `learn + zen shell` -> `StudySessions/Show` albo nowy `Zen` shell
   - `learn + exam_like shell` -> `StudySessions/LearnClassic`
3. Nie mieszac wyniku egzaminu z wynikiem nauki.

Rezultat etapu:

- wybor komponentu jest jawny i bezpieczny.

### Etap 6. Ochrona regresji

Cel:

- upewnic sie, ze nie rozwalimy juz dzialajacych trybow.

Pliki testowe:

- [C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/SessionPageTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/SessionPageTest.php)
- [C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/StudySessionFlowTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/StudySessionFlowTest.php)
- nowe testy, jesli potrzebne:
  - `LearnClassicSessionFlowTest`
  - `LearnClassicRenderingTest`

Taski:

1. Potwierdzic, ze `Egzamin` dalej renderuje `Exam.vue`.
2. Potwierdzic, ze `Zen mode` dalej renderuje aktualny shell nauki.
3. Potwierdzic, ze `Nauka klasyczna` renderuje nowy shell.
4. Potwierdzic, ze `Nauka klasyczna` ma te same funkcje nauki co `Zen mode`.
5. Potwierdzic, ze `Nauka klasyczna` nie uruchamia logiki egzaminowej.

## Minimalny plan wdrozenia

Jesli chcemy robic to bez chaosu, rekomendowana kolejnosc jest taka:

1. Ustalic kontrakt presetow na `/nauka`
2. Dolozyc `ui_shell` / `session_preset` do payloadu sesji
3. Rozdzielic wspolna logike nauki od `Show.vue`
4. Zbudowac nowy shell `LearnClassic`
5. Podpiac routing renderu
6. Dopic testy regresyjne

## Co bedzie wynikiem koncowym

Po dowiezieniu tego planu otrzymamy:

- `Zen mode` jako spokojny shell nauki,
- `Egzamin` jako restrykcyjna symulacja,
- `Nauka klasyczna` jako egzaminowy wizualnie trening z pelnym pakietem funkcji nauki.

To jest docelowy stan produktu, do ktorego dazymy.
