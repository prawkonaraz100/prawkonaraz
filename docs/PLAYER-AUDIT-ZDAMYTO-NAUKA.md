# Player Audit: ZdamyTo Nauka

Data audytu: 2026-03-27  
Zakres: realny audyt flow nauki pytan w ZdamyTo po zalogowaniu, z naciskiem na player pytania, media, przejscia i odczuwalna szybkosc.

Audytowane URL:
- [znaki-ostrzegawcze / nauka](https://www.zdamyto.com/testy-na-prawo-jazdy/kategoria-c/znaki-ostrzegawcze/nauka)
- [wyprzedzanie / nauka](https://www.zdamyto.com/testy-na-prawo-jazdy/kategoria-c/wyprzedzanie/nauka)
- [kategoria-c / nauka dzialami](https://www.zdamyto.com/testy-na-prawo-jazdy/kategoria-c/nauka)

Artefakty:
- [01-learn-initial.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/playwright/zdamyto-player-audit/01-learn-initial.png)
- [02-after-start.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/playwright/zdamyto-player-audit/02-after-start.png)
- [03-after-answer.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/playwright/zdamyto-player-audit/03-after-answer.png)
- [04-video-question.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/playwright/zdamyto-player-audit/04-video-question.png)
- [06-image-explanation.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/playwright/zdamyto-player-audit/06-image-explanation.png)
- [02-after-start-snapshot.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/playwright/zdamyto-player-audit/02-after-start-snapshot.md)
- [04-video-question-snapshot.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/playwright/zdamyto-player-audit/04-video-question-snapshot.md)
- [01-learn-initial-network.txt](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/playwright/zdamyto-player-audit/01-learn-initial-network.txt)
- [02-after-start-network.txt](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/playwright/zdamyto-player-audit/02-after-start-network.txt)
- [03-after-answer-network.txt](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/playwright/zdamyto-player-audit/03-after-answer-network.txt)
- [04-video-question-network.txt](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/playwright/zdamyto-player-audit/04-video-question-network.txt)

## 1. Executive Summary

Najwazniejsza przewaga ZdamyTo nie bierze sie z "ladnego UI", tylko z tego, ze player zachowuje sie jak lekka aplikacja stanow, a nie jak klasyczna strona z pelnym przeladowaniem po kazdej akcji.

Najwazniejsze cechy:
- staly shell ekranu, bez przeskakiwania layoutu
- bardzo przewidywalny uklad: media po lewej, sterowanie po prawej, odpowiedzi na dole
- osobny stan `przed startem`, ktory opoznia ujawnienie lub odtworzenie medium
- brak agresywnego feedbacku po kliknieciu odpowiedzi
- bardzo szybkie przejscia miedzy pytaniami
- `Uzasadnienie odpowiedzi` otwierane inline, bez wyjscia z playera

Wniosek dla nas:
`musimy upodobnic nasz panel nauki nie do kolorow ZdamyTo, tylko do ich modelu przechodzenia przez pytania`.

## 2. Metodyka

Audyt byl wykonany po zalogowaniu na realnym koncie kursanta, z wykorzystaniem Playwright i pomiarow runtime.

Sprawdzone stany:
- wejscie do dzialu nauki
- stan przed kliknieciem `Start`
- stan po ujawnieniu obrazka
- stan po zaznaczeniu odpowiedzi
- stan po otwarciu `Uzasadnienie odpowiedzi`
- przejscie do kolejnego pytania
- wariant pytania z wideo
- lista dzialow nauki z poziomu kategorii

Dodatkowe pomiary:
- czas ujawnienia obrazu po `Start`
- czas uruchomienia wideo po `Start`
- czas przejscia do kolejnego pytania
- obserwacja sieci po interakcjach

## 3. Anatomia Playera

### 3.1. Staly shell

Shell jest praktycznie niezmienny miedzy pytaniami:
- cienki top row z menu, jezykami i utility actions
- glowna kolumna z medium
- prawa szyna sterowania
- pas pytania tekstowego
- pas odpowiedzi
- dolna nawigacja `poprzednie / podsumowanie / nastepne`

To daje dwa efekty:
- oko uzytkownika zawsze wie, gdzie patrzec
- zmiana pytania nie powoduje reflow calej strony

### 3.2. Prawa szyna

Prawa szyna nie jest ozdobna. To panel operacyjny.

Zawiera:
- `Zakoncz probe`
- licznik pytan w probie
- progressbar czasu
- `Start`
- procent blednych odpowiedzi innych kursantow
- `Dodaj do ulubionych`
- `Uzasadnienie odpowiedzi`
- `Nastepne pytanie`

To jest wazne: `Start`, `Uzasadnienie` i `Nastepne pytanie` sa zawsze w tym samym miejscu. Uzytkownik nie musi ich szukac.

### 3.3. Strefa medium

Medium zajmuje najwieksza i najbardziej stabilna przestrzen ekranu.

Dla obrazu:
- przed startem jest placeholder i komunikat
- po starcie obraz po prostu wskakuje w to samo pole

Dla wideo:
- video element jest juz zamontowany w DOM
- przed startem jest gotowe, ale zatrzymane
- `Start` nie tworzy playera od zera, tylko uruchamia to, co juz czeka

To jest jedna z glownych przyczyn poczucia szybkosci.

### 3.4. Strefa odpowiedzi

Odpowiedzi sa bardzo proste:
- duze, szerokie przyciski
- wyrazny stan zaznaczenia
- brak nadmiarowych opisow
- brak automatycznego przejscia po kliknieciu

Po kliknieciu odpowiedzi player nie "wybucha":
- nie przewija strony
- nie przechodzi od razu dalej
- nie zalewa uzytkownika wynikiem

To daje poczucie kontroli.

## 4. Maszyna Stanow

### 4.1. Stan poczatkowy

W stanie poczatkowym:
- widoczne jest pytanie
- medium nie jest jeszcze aktywne
- `Start` jest aktywny
- `Nastepne pytanie` jest zablokowane

Uzytkownik ma jasny pierwszy krok: kliknac `Start`.

### 4.2. Stan po `Start` dla obrazu

Po kliknieciu `Start` dla obrazka:
- placeholder znika
- obraz laduje sie w tej samej ramce
- `Nastepne pytanie` staje sie aktywne
- timer zmienia stan

To jest szybkie i nie powoduje zmiany kompozycji.

### 4.3. Stan po `Start` dla wideo

Po kliknieciu `Start` dla wideo:
- video jest odtwarzane w tej samej ramce
- player jest `muted`
- player nie pokazuje natywnych kontrolek
- `autoplay` jest wlaczony

Z punktu widzenia UX:
- uzytkownik nie musi szukac play buttona w obrebie wideo
- wideo zachowuje sie jak integralna czesc pytania

### 4.4. Stan po zaznaczeniu odpowiedzi

Po zaznaczeniu odpowiedzi:
- wybrany przycisk dostaje stan aktywny
- nie ma automatycznego przejscia
- nie ma natychmiastowego odszyfrowania calego pytania

To jest model:
`wybierz -> opcjonalnie sprawdz uzasadnienie -> przejdz dalej`

### 4.5. Stan z uzasadnieniem

Po kliknieciu `Uzasadnienie odpowiedzi`:
- pod odpowiedziami rozwija sie panel inline
- pojawia sie tekst uzasadnienia
- pojawia sie dodatkowa ilustracja

To jest bardzo dobre rozwiazanie, bo:
- nie wyrywa z flow
- nie otwiera nowej strony
- nie chowa pytania

### 4.6. Stan przejscia do kolejnego pytania

Przejscie do kolejnego pytania jest bardzo szybkie:
- shell zostaje ten sam
- wymienia sie tylko tresc, medium i prawy panel danych
- nie zaobserwowano pelnego reloadu dokumentu

To sprawia wrazenie lokalnej kolejki pytan.

## 5. Pomiary

Pomiary z tej sesji audytowej:

- `Start -> obraz gotowy`: `71 ms`
- `Start -> wideo rusza`: `288 ms`
- `Nastepne pytanie -> nowe ID pytania`: `102 ms`

Dodatkowo dla wariantu wideo potwierdzone atrybuty:
- `autoplay = true`
- `muted = true`
- `controls = false`
- video bylo juz zamontowane przed kliknieciem `Start`

Wniosek:
- ZdamyTo wygrywa szybkoscia glownie dzieki temu, ze nie buduje pytania od nowa po kliknieciu
- media sa przygotowane wczesniej
- przejscia miedzy pytaniami wygladaja jak zmiana stanu, nie jak nawigacja

## 6. Obserwacje Sieci i Dzialania

Z przechwyconych requestow po interakcjach:
- dominowaly requesty analytics
- nie bylo widac ciezkiego same-origin API roundtrip po kazdym kliknieciu odpowiedzi
- nie zaobserwowano pelnego document navigation przy `Nastepne pytanie`

To nie jest twardy reverse engineering ich backendu, ale silna wskazowka, ze:
- pytania sa trzymane lokalnie w kolejce albo preloadowanym pakiecie
- interakcje sa obslugiwane przez lekki runtime JS

W konsoli aplikacji pojawialy sie tez logi typu:
- `que 0`
- `que 1`
- `que 2`
- kolejne ID pytan

To dodatkowo wzmacnia hipoteze kolejki pytan po stronie klienta.

## 7. Co Koniecznie Powinnismy Przeniesc

Nie kopiowac kolorow. Przeniesc mechanike.

Must-have:
- staly shell gracza pytania
- stala prawa szyna z najwazniejszymi akcjami
- brak pelnego reloadu przy nastepnym pytaniu
- preload lub lekki cache obecnego i kolejnego pytania
- media w stalej ramce bez skakania layoutu
- `Uzasadnienie` jako panel inline
- spokojne zachowanie po zaznaczeniu odpowiedzi

## 8. Czego Nie Powinnismy Kopiowac 1:1

- ich niebiesko-zolta kolorystyka
- duzej liczby utility ikon na gorze
- procentu "ile osob odpowiedzialo zle" jako stalego szumu, jesli naszym celem jest skupienie
- dolnej nawigacji `poprzednie / podsumowanie / nastepne`, jesli chcemy jeszcze prostszy model

## 9. Wplyw Na Nasz Projekt

Obecnie nasz panel nauki jest prostszy wizualnie, ale wolniejszy systemowo, bo wciaz bardziej przypomina render serwerowy na kazde przejscie.

Jesli chcemy byc "podobnie szybcy", to glowne roznice do nadrobienia nie sa w CSS, tylko w tym:
- ile danych pytania niesiemy od razu
- czy zmiana pytania jest lokalna czy serwerowa
- czy media sa gotowe zanim uzytkownik kliknie `Start` albo odpowiedz

## 10. TODO / PRD dla Naszego Playera

### P0 - Krytyczne

- przebudowac [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue) na lokalny state machine `preview -> active -> explanation -> next`
- nie robic pelnego przejscia Inertia po kazdej odpowiedzi
- trzymac w pamieci co najmniej:
  - biezace pytanie
  - nastepne pytanie
  - podstawowe meta calej sesji
- wyrenderowac media w stalej ramce o niezmiennym rozmiarze

### P1 - Media

- dla obrazu:
  - placeholder/stage przed startem
  - reveal bez zmiany layoutu
- dla wideo:
  - preload metadata lub gotowy player mount przed startem
  - `muted autoplay` po decyzji uzytkownika
  - brak ciezkich natywnych kontrolek w trybie nauki, jesli nie sa potrzebne

### P2 - Interakcje

- utrzymac opcje uzytkownika:
  - auto-przejscie po odpowiedzi
  - auto-odtwarzanie filmow
- ale zrobic to tak, by reczny model nadal byl domyslnie bardzo szybki

### P3 - Wyjasnienia

- przeniesc `Uzasadnienie` do lekkiego panelu inline pod odpowiedziami
- nie wypychac uzytkownika na osobny ekran
- obslugiwac dodatkowa ilustracje lub drugi asset wspierajacy

### P4 - Wydajnosc

- zmniejszyc zaleznosc od roundtripu backendowego per question
- jesli backend musi dostac odpowiedz od razu, to zapis robic asynchronicznie, a UI przechodzic optimistic
- lokalnie cache'owac payload kilku kolejnych pytan

## 11. Finalny Wniosek

ZdamyTo nie sprawia wrazenia szybkiego dlatego, ze jest lekkie graficznie. Sprawia takie wrazenie dlatego, ze:
- player ma malo stanow,
- strefy ekranu sa stale,
- media sa gotowe zanim uzytkownik ich potrzebuje,
- przejscie do kolejnego pytania dzieje sie prawie natychmiast.

Jesli chcemy byc podobni, musimy:
- zostawic nasz czarno-bialy, prosty styl
- ale przebudowac mechanike sesji tak, by zachowywala sie jak lokalny player pytan, a nie jak kolejna strona formularza.
