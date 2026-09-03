# Session Explanation UX Mock

## Status

Na `2026-03-30` hinty w aktywnej sesji sa uznane za domkniete produktowo.

Oznacza to, ze odhaczylismy juz:

- [x] miejsce wyswietlania hinta,
- [x] zachowanie po blednej odpowiedzi,
- [x] fallback bez `questions.explanation`,
- [x] standard tresci hinta,
- [x] bazowy wyglad hinta w playerze.

Otwarte pozostaja juz nie same hinty, tylko:

- workflow uzupelniania `questions.explanation`,
- review na koncu sesji,
- dalsze narzedzia adminowe i contentowe.

## Cel

Ten dokument opisuje zaakceptowany flow `dobrze / zle + wyjasnienie` w module `Sesja`.

Nie opisuje jeszcze finalnego pipeline'u contentowego. Najpierw domykamy:

- jak hint dziala w aktywnej sesji,
- co robimy, gdy baza nie ma jeszcze tresci,
- jaki standard tresci obowiazuje w trakcie nauki.

## Aktualna prawda o danych

Stan na `2026-03-28`:

- pytan w bazie: `18375`
- pytan z `questions.explanation`: `2`
- pytan bez `questions.explanation`: `18373`

Wniosek:

- nie wolno projektowac UX tak, jakby wyjasnienia byly juz kompletne,
- pierwszy mock musi dobrze dzialac w dwoch stanach:
  - `wyjasnienie istnieje`
  - `wyjasnienia jeszcze nie ma`

## Scope

Dotyczy tylko aktywnej sesji pod:

- `/nauka/teraz`

Dotyczy tylko trybu:

- `Dobrze / zle + wyjasnienie`

Nie dotyczy jeszcze:

- review na koncu sesji,
- workflow uzupelniania brakujacych wyjasnien,
- adminowych narzedzi edycji,
- dodatkowych kolumn typu `explanation_short`.

## Decyzja produktowa

### 1. Dobra odpowiedz

Po dobrej odpowiedzi:

- pokazujemy tylko szybki zielony sygnal na buttonie,
- nie pokazujemy karty wyjasnienia,
- sesja idzie dalej zgodnie z wybranym tempem.

### 2. Zla odpowiedz w trybie `Dobrze / zle + wyjasnienie`

Po zlej odpowiedzi:

- zaznaczona zla odpowiedz dostaje czerwony stan,
- poprawna odpowiedz moze dostac delikatny zielony stan,
- obszar pytania przechodzi chwilowo w stan `hint-only`,
- zamiast pytania pokazuje sie krotka wskazowka instruktora,
- w trakcie sesji nie robimy rozwijanego artykulu ani mini-analizy,
- hint nie wymaga dodatkowego kliku typu `Pokaz wyjasnienie`.

### 3. Gdy wyjasnienie istnieje

Pokazujemy:

- krotki hint wyciety z `questions.explanation`,
- tylko `1-2` zdania,
- bez przycisku `Pokaz calosc`,
- bez pelnej analizy w trakcie sesji.

### 4. Gdy wyjasnienia nie ma

Nie pokazujemy pustego miejsca i nie udajemy, ze wszystko jest gotowe.

Zamiast tego pokazujemy krotki fallback, np.:

- `Spojrz na zielony wariant. To on pokazuje, jaki sygnal byl tu najwazniejszy.`

To ma:

- domknac UX,
- uniknac wrazenia bledu,
- nie blokowac tempa sesji.

## Standard tresci hintow

### Cel

Hint ma odpowiedziec tylko na jedno pytanie:

- `dlaczego ta odpowiedz byla poprawna`

W trakcie sesji nie tlumaczymy jeszcze:

- podstawy prawnej,
- calej logiki przepisu,
- wyjatkow,
- szerokiego kontekstu egzaminacyjnego.

To wraca dopiero w review na koncu sesji.

### Zasada nadrzedna

Hint ma byc:

- szybka podpowiedzia od instruktora,
- jedna mysl do zapamietania,
- wskazaniem, na co patrzec nastepnym razem.

Hint nie ma byc:

- streszczeniem calego `explanation`,
- mini-artykulem,
- legalnym uzasadnieniem odpowiedzi.

### Ton

Hint ma brzmiec jak spokojna wskazowka od dobrego instruktora, a nie jak cytat z ustawy.

Ma byc:

- prosty,
- konkretny,
- ludzki,
- nastawiony na to, na co spojrzec.

Nie ma byc:

- urzedowy,
- mentorski,
- akademicki,
- legalistyczny.

### Dlugosc

Docelowy standard:

- domyslnie `1` zdanie,
- `2` zdania tylko wtedy, gdy bez tego nie da sie zachowac sensu,
- najlepiej `60-140` znakow,
- twardy limit roboczy w UI: okolo `145` znakow po skroceniu.

### Co powinno byc w hincie

Hint powinien:

- wskazac najwazniejszy sygnal,
- nazwac element, ktory rozstrzyga pytanie,
- pomoc kursantowi zapamietac, na co patrzec nastepnym razem.

Najlepszy szablon:

- `Zwróć uwagę na X. To ono/ten element rozstrzyga, że Y.`
- `Najważniejsze tutaj jest X. Ono przesądza o odpowiedzi.`
- `Patrz na X, nie na Y. To X daje tu odpowiedź.`

### Jak zaczynamy hint

Najlepsze początki:

- `Zwróć uwagę na...`
- `Najważniejsze tutaj jest...`
- `Patrz na...`
- `Tu decyduje...`

Unikamy wstepow typu:

- `Zgodnie z...`
- `Na podstawie...`
- `W tej sytuacji należy pamiętać...`
- `Prawidłowa odpowiedź to...`

### Czego unikamy

- `zgodnie z art....`
- `na podstawie przepisow ustawy...`
- definicji slownikowych,
- wielozdaniowej analizy,
- tlumaczenia wszystkiego naraz,
- tonu `to oczywiste`.

### Przyklady

Dobre:

- `Zwróć uwagę na znak, nie na pustą drogę. To on rozstrzyga, że trzeba zmniejszyć prędkość.`
- `Najważniejsza jest sygnalizacja, a nie sam układ skrzyżowania. To ona daje tu odpowiedź.`
- `Patrz na pieszego i jego pierwszeństwo. To ono przesądza, że trzeba ustąpić.`
- `Tu decyduje znak stop, nie to, że droga wygląda na pustą.`

Zle:

- `Zgodnie z art. 26 ustawy Prawo o ruchu drogowym kierujacy ma obowiazek...`
- `Jest to odpowiedz wynikajaca z caloksztaltu zasad ruchu drogowego...`
- `W tym pytaniu nalezy przeanalizowac ustawe, definicje i stan faktyczny...`

### Co robi UI z trescia

Na tym etapie runtime playera:

- czyści nadmiar spacji,
- bierze najpierw pierwsza mysl z `questions.explanation`,
- jesli pierwsze zdanie jest zbyt krótkie, moze dobrac drugie, zeby hint nie brzmial urwanie,
- obcina legalistyczne lub urzedowe wstepy,
- pilnuje limitu dlugosci,
- dobiera czas wyswietlania do dlugosci hinta.

To znaczy, ze:

- mozemy wpisywac pelniejsze `explanation` w bazie,
- ale w trakcie sesji i tak pokazujemy kursantowi tylko krotka, ludzka podpowiedz.

## Miejsce w UI

Aktualna decyzja UX jest taka:

- hint siedzi w strefie pytania,
- po bledzie zastepuje pytanie zamiast dochodzic jako kolejny blok,
- pozostaje blisko medium i odpowiedzi,
- nie spycha usera do dolu ekranu,
- nie konkuruje z promptem, bo prompt w tym stanie chwilowo znika.

Powod:

- w wariancie `pod odpowiedziami` user musial zjezdzac wzrokiem zbyt nisko,
- wariant `hint + pytanie naraz` byl zbyt ciasny i zaczynal nachodzic na prompt,
- wariant `hint-only` daje najczystszy stan po bledzie.

Nie robimy:

- modala,
- osobnego prawego drawera,
- dodatkowego bocznego panelu tylko dla wyjasnien.

## Zachowanie przy tempie sesji

### Auto-next wlaczony

W trybie `Dobrze / zle + wyjasnienie`:

- z prawdziwym wyjasnieniem dajemy krotka pauze na przeczytanie preview,
- bez wyjasnienia idziemy dalej szybciej, bo nie ma czego czytac.

### Ręczne przejscie

Jesli user ustawi:

- `Po odpowiedzi przejdz -> Sam klikam dalej`

to hint zostaje na ekranie do czasu klikniecia `Nastepne pytanie`.

## Kontrakt danych na teraz

Kanoniczne zrodlo prawdy pozostaje jedno:

- `questions.explanation`

Na etapie aktywnej sesji:

- hint w sesji wycinamy z tego samego pola,
- pelne omowienie zachowujemy na review i dalszy etap projektu,
- nie dodajemy jeszcze nowej kolumny.

## Konsekwencje dla backendu

Ten mock nie wymaga nowego modelu danych.

Wymaga tylko, aby payload sesji dalej zwracal:

- `currentQuestion.explanation`
- `questionPool[].explanation`
- batch preload `questions[].explanation`

To juz istnieje i powinno pozostac invariantem.

## Co bedzie pozniej

Po domknieciu UX hintow wracamy do:

1. workflow uzupelniania `questions.explanation`,
2. review na koncu sesji z pelnymi omowieniami,
3. decyzji, czy potrzebujemy osobnego `explanation_short`,
4. narzedzi adminowych / importowych do masowego uzupelniania brakow.

## Status decyzji

Na branchu `codex/session-explanation-experiments` zaakceptowalismy wariant:

- `hint-only w strefie pytania po bledzie`

To jest aktualna decyzja produktowa dla playera nauki i od tego momentu traktujemy ja jako kanoniczny kierunek dalszego rozwoju.
