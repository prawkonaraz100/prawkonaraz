# Coverage Matrix: PJ360 `testy` i `wyklady`

Data: 2026-03-26  
Status: coverage focus `100/100` dla dwoch kluczowych modulow referencyjnych

Dokument uzupelnia:
- [UI-AUDIT-PRAWO-JAZDY-360.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/UI-AUDIT-PRAWO-JAZDY-360.md)
- [TODO-PRD-PRAWO-JAZDY-360-ADAPTATION.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/TODO-PRD-PRAWO-JAZDY-360-ADAPTATION.md)

## 1. Cel

Ten dokument odpowiada na jedno konkretne pytanie:

`czy dwa najwazniejsze dla nas moduly referencyjne PJ360 sa zeskanowane do poziomu 100/100?`

Odpowiedz:
- `tak`, dla celu projektowego i implementacyjnego mamy `100/100` pokrycia rodzin stanów dla:
  - `https://www.prawo-jazdy-360.pl/testy-na-prawo-jazdy`
  - `https://www.prawo-jazdy-360.pl/wyklady`

Nie oznacza to, ze kliknelismy kazdy pojedynczy numer pytania albo kazdy slajd z 774.
Oznacza to, ze potwierdzilismy wszystkie istotne typy stanow i shelli potrzebne do odwzorowania UI.

## 2. Modul `testy-na-prawo-jazdy`

### Pokrycie: `15/15`

Potwierdzone stany i shelle:

1. publiczny landing testow  
2. demo / upsell above-the-fold  
3. start sesji egzaminacyjnej  
4. stan `in-progress`  
5. stan po zaznaczeniu odpowiedzi  
6. segmented progress `podstawowe / specjalistyczne`  
7. timer / countdown  
8. utility rail: `Zapisz pytanie`, `Odswiez pytanie`, `Zadaj pytanie`, `Pelny ekran`, `Zakoncz egzamin`  
9. modal / mini-flow `Zadaj pytanie`  
10. wynik egzaminu na tym samym shellu  
11. grid numerow pytan po wyniku  
12. review detail po kliknieciu numeru pytania  
13. explanation stack: `Wyjasnienie`, `Wyjasnienia eksperta`, kontekst / znaki  
14. FAQ + feedback form do wyjasnienia  
15. publiczna baza pytan: listing + detail pytania z locked explanation

### Wniosek dla nas

Modul `testy` jest rozpoznany kompletnie na poziomie:
- layoutu,
- rytmu informacji,
- utility actions,
- przejsc `exam -> result -> review`,
- publicznego shellu bazy pytan.

To wystarcza do przebudowy:
- [Show.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/StudySessions/Show.vue)
- [Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Questions/Index.vue)
- przyszlego publicznego detailu pytania

## 3. Modul `wyklady`

### Pokrycie: `12/12`

Potwierdzone stany i shelle:

1. overview `wyklady` z lewym rail em i wskazaniem kategorii  
2. status `Wersja demo`  
3. lista dzialow z liczba slajdow i progressem  
4. rozwijanie darmowego dzialu  
5. rozwijanie lekcji w dziale  
6. darmowy detail slajdu / player page (`?link=...`)  
7. breadcrumb / hierarchia `dzial -> lekcja -> slajd`  
8. player controls + nawigacja slajdu + CTA `Nastepny`  
9. transcript / tekst obok playera  
10. klik w lekcje zablokowana i zachowanie shellu paywallowego  
11. cards `Co zawieraja poszczegolne dzialy?` jako marketingowo-katalogowy fallback  
12. cross-sell do `szkolenie z instruktorem` i `testy`

### Wniosek dla nas

Modul `wyklady` jest rozpoznany kompletnie na poziomie:
- shellu overview,
- shellu playera,
- przejsc miedzy darmowym detalem a stanem zablokowanym,
- fallbackow i cross-selli.

To wystarcza do przebudowy:
- przyszlego overview `wykladow`
- przyszlego playera / detail view
- wzorca `curriculum + player + paywall fallback`

## 4. Wynik laczny

### Pokrycie laczne: `27/27 = 100/100`

- `testy`: `15/15`
- `wyklady`: `12/12`

W sensie projektowym mamy pelne pokrycie.

## 5. Czego swiadomie nie robilismy

- nie przechodzilismy zewnetrznej platnosci operatora,
- nie klikalismy wszystkich setek slajdow ani wszystkich pytań, bo nie daje to nowego shellu,
- nie mierzymy tu logiki biznesowej ani backendowej, tylko layout, kompozycje, stany i UX.

## 6. Co z tego wynika dla implementacji

Mozemy juz bezpiecznie traktowac jako wzorzec referencyjny:

### `testy`
- glowny shell produktu
- shell egzaminu
- shell wyniku
- shell review

### `wyklady`
- shell curriculum
- shell playera
- shell paywallowego fallbacku

To sa dwa moduly, od ktorych powinnismy zaczac adaptacje naszego UI.
