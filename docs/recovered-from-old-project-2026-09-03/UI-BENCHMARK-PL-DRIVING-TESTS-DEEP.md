# UI Benchmark Deep Dive: Polskie Serwisy Testów na Prawo Jazdy

Data przegladu: `2026-03-21`

## Cel

Ten dokument rozwija [UI-BENCHMARK-PL-DRIVING-TESTS.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/UI-BENCHMARK-PL-DRIVING-TESTS.md) do poziomu warsztatowego.

Tutaj zapisujemy nie tylko ogolne wrazenia, ale konkretne wzorce:

- rytm sekcji,
- hierarchie hero,
- nawigacje,
- CTA,
- trust signals,
- zachowanie mobile,
- i wnioski, ktore mozna bezposrednio przelozyc na nasz frontend.

## Zakres

Dokladniej przeskanowane serwisy:

1. `Superprawko`
2. `Prawko.pl`
3. `Prawo-Jazdy-360.pl`
4. `IMAGE Prawo Jazdy`
5. `Teoria.pl`

To jest zestaw celowo mieszany:

- `Superprawko` jako najlepszy kierunek `app-first`,
- `Prawko.pl` i `Prawo-Jazdy-360.pl` jako liderzy `market scale / trust / conversion`,
- `IMAGE` jako wzor duzego, starego szkoleniowego katalogu,
- `Teoria.pl` jako wzor natychmiastowej uzytecznosci, ale rowniez antywzor portalowosci.

## Artefakty

Desktop:

- [superprawko-desktop.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/benchmark-pl/superprawko-desktop.png)
- [prawko-desktop.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/benchmark-pl/prawko-desktop.png)
- [prawojazdy360-desktop.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/benchmark-pl/prawojazdy360-desktop.png)
- [image-desktop.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/benchmark-pl/image-desktop.png)
- [teoria-desktop.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/benchmark-pl/teoria-desktop.png)

Mobile:

- [superprawko-mobile.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/benchmark-pl/superprawko-mobile.png)
- [prawko-mobile.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/benchmark-pl/prawko-mobile.png)
- [prawojazdy360-mobile.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/benchmark-pl/prawojazdy360-mobile.png)
- [image-mobile.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/benchmark-pl/image-mobile.png)
- [teoria-mobile.png](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/benchmark-pl/teoria-mobile.png)

## Szybki werdykt

Na rynku sa dzis trzy rozpoznawalne szkoly layoutu:

1. `App-first, lekki, screenshot-driven`
2. `Duzy marketingowo-edukacyjny kombajn`
3. `Portal testowy / SEO article z osadzonym narzedziem`

Najbardziej nowoczesny i zdyscyplinowany jest `Superprawko`.

Najmocniejsze sprzedazowo i dowodowo sa `Prawko.pl` i `Prawo-Jazdy-360.pl`.

Najbardziej przestarzale i katalogowe w odbiorze sa `IMAGE` i `Teoria.pl`, mimo ze obie marki maja praktyczna wartosc i rozpoznawalny content.

## Analiza per serwis

### 1. Superprawko

Zrodlo:

- [superprawko.pl](https://superprawko.pl/)

Typ layoutu:

- minimalistyczny `product landing`
- bardzo malo elementow pobocznych
- mocny hero z prawdziwym ekranem produktu

Struktura strony:

1. prosty topbar / header
2. hero z glownym haslem, jednym CTA i mockiem pytania egzaminacyjnego
3. sekcja zalet w formie prostych kart
4. kilka sekcji feature-driven z telefonami jako proof
5. FAQ
6. finalne CTA
7. spokojny footer

Co dziala:

- bardzo dobra dyscyplina hierarchii
- od razu widac, ze to jest produkt, a nie portal
- screeny nie sa dekoracja, tylko dowodem
- sekcje sa krotkie i latwe do przeskanowania
- dark UI buduje premium i techniczna wiarygodnosc

Co nie dziala:

- na starcie jest mniej klasycznych trust signals niz u wiekszych graczy
- dla czesci uzytkownikow moze byc zbyt lekkie i zbyt "startupowe"

Wniosek dla nas:

- to jest najlepszy punkt odniesienia dla `hero`, `rytmu sekcji` i `app-first narrative`
- warto kopiowac logike: `krotki claim -> screen -> szybkie proof points -> konkretne funkcje`
- nie warto kopiowac 1:1 oszczednosci trust section; u nas proof powinno byc odrobine mocniejsze

Mobile:

- bardzo dobrze sie sklada
- sekcje zachowuja rytm i nie zamieniaja sie w sciane tekstu
- nadal czytelne sa CTA i screeny

Ocena wzorcowa:

- `hero`: bardzo mocny
- `nawigacja`: bardzo dobra
- `proof`: dobra, ale moglaby byc mocniejsza
- `mobile`: bardzo dobre
- `rytm`: najlepszy z calej piatki

### 2. Prawko.pl

Zrodlo:

- [prawko.pl](https://prawko.pl/)

Typ layoutu:

- rozbudowany `growth + education + product landing`
- bardzo szeroka oferta upchnieta na jednej stronie

Struktura strony:

1. gesty header z wieloma sciezkami
2. hero z claimem i mockiem produktu
3. karty wartosci / korzysci
4. band promocyjny / lead magnet
5. sekcje poradnikowe i szkoleniowe
6. opinie / proof
7. blog
8. kolejne moduly kursowe i FAQ
9. rozbudowany footer

Co dziala:

- bardzo szybkie poczucie "duzej marki"
- duzo dowodow, ze produkt jest rozbudowany
- nawigacja od razu komunikuje skale oferty
- CTA i social proof sa dobrze widoczne

Co nie dziala:

- za duzo punktow decyzyjnych juz na starcie
- strona konkuruje sama ze soba o uwage
- produkt jest tylko jednym z wielu bytow na stronie
- rytm sekcji jest nierowny: obok czystych blokow pojawiaja sie duze przeskoki wizualne

Wniosek dla nas:

- warto podpatrzec `zaufanie`, `proof` i `komercyjna wiarygodnosc`
- nie warto kopiowac ciezaru nawigacji i gestosci strony
- jesli bierzemy inspiracje z Prawko, to tylko w zakresie:
  - trust signals,
  - duzych liczb,
  - pokazania calego ekosystemu pozniej, nie na starcie

Mobile:

- strona pozostaje funkcjonalna
- ale jest dluga i ciezka
- wiele sekcji wyglada jak kolejne landing blocks doklejone do siebie

Ocena wzorcowa:

- `hero`: dobry
- `nawigacja`: za ciezka
- `proof`: bardzo mocny
- `mobile`: poprawny, ale za dlugi
- `rytm`: nierowny

### 3. Prawo-Jazdy-360.pl

Zrodlo:

- [prawo-jazdy-360.pl](https://www.prawo-jazdy-360.pl/)

Typ layoutu:

- duzy portal edukacyjny nastawiony na konwersje
- mocne claimy, duzo sekcji, duzo targetow

Struktura strony:

1. hero z glowna obietnica zdania teorii
2. szybkie proof i statystyki
3. sekcje kategorii / aplikacji / wiedzy
4. moduly kursowe i benefitowe
5. duza ilosc segmentow dla roznych intencji
6. stopka z duzym zapleczem linkow

Co dziala:

- bardzo szybko wiadomo, dla kogo jest produkt
- kategorie i dowody skali sa widoczne wysoko
- serwis dobrze komunikuje "oficjalnosc", aktualnosc i skale
- ma wyrazny, rozpoznawalny kierunek kolorystyczny

Co nie dziala:

- strona jest jeszcze gestsza niz Prawko
- sekcje sa czesto bardzo podobne do siebie i zaczynaja sie zlewac
- za duzo gradientow, kart i targetowanych boksow bez wystarczajacej hierarchii

Wniosek dla nas:

- warto podpatrzec `widocznosc kategorii`, `statystyki`, `komunikaty o skali`
- nie warto kopiowac calej dlugosci i przeinwestowanego landing rhythm
- jesli inspiracja, to bardziej `co komunikowac`, a nie `jak wiele sekcji stawiac`

Mobile:

- na mobile robi sie bardzo dluga tasmowa struktura
- nadal jest czytelna, ale wyraznie meczy iloscia blokow
- dobry przyklad, jak wiele sekcji mozna jeszcze utrzymac technicznie, ale niekoniecznie UX-owo

Ocena wzorcowa:

- `hero`: dobry
- `nawigacja`: srednia
- `proof`: bardzo mocny
- `mobile`: czytelny, ale za dlugi
- `rytm`: za gesty

### 4. IMAGE Prawo Jazdy

Zrodlo:

- [imageprawojazdy.pl](https://imageprawojazdy.pl/)

Typ layoutu:

- stary `wydawniczo-szkoleniowy katalog + portal`
- mocne poczucie firmy szkoleniowej, slabsze poczucie nowoczesnej aplikacji

Struktura strony:

1. top strip / techniczne i reklamowe elementy
2. hero z haslami i badge'ami sklepow
3. sekcja benefity
4. darmowy egzamin
5. blog / news / artykuly
6. siatki kart contentowych
7. partnerzy
8. kolejne bloki informacyjne i dopiero potem footer

Co dziala:

- duza ilosc materialu buduje "poważna marke szkoleniowa"
- jest wyrazny sygnal `aplikacja mobilna + wersja online`
- dobrze widac, ze oferta jest szeroka i dojrzala

Co nie dziala:

- hero jest wizualnie slaby i przestarzaly
- strona ma za duzo elementow z roznych epok web designu
- wygląda bardziej jak sklep/wydawnictwo niz nowoczesny produkt edukacyjny
- za duzo siatek, kafli i blokow o podobnej wadze

Wniosek dla nas:

- warto podpatrzec `jak pokazuja breadth of offer`
- nie warto kopiowac warstwy wizualnej, rytmu i calej struktury landingu
- to jest dobry antywzor na to, jak zbyt wiele bytow konkuruje o uwage uzytkownika

Mobile:

- szczegolnie na mobile widac katalogowy charakter
- duzo miniaturowych kart i przeskokow
- slaba dyscyplina hierarchii

Ocena wzorcowa:

- `hero`: slaby
- `nawigacja`: przecietna
- `proof`: sredni
- `mobile`: slabe do sredniego
- `rytm`: chaotyczny

### 5. Teoria.pl

Zrodlo:

- [teoria.pl/prawo-jazdy/testy](https://www.teoria.pl/prawo-jazdy/testy)

Typ layoutu:

- `utility page with embedded exam`
- narzedzie jest niemal od razu w centrum

Struktura strony:

1. top bars / info bars / cookie modal
2. osadzony stan testu i progress
3. tekst wyjasniajacy jak wyglada egzamin
4. wybor kategorii
5. tryb rozwiazywania testow
6. dlugi blok objasniajacy i FAQ
7. stopka

Co dziala:

- ogromna natychmiastowosc uzytecznosci
- praktycznie od razu jestes w kontekscie testu
- bardzo mocny sygnal "tu juz mozesz cwiczyc"
- dobra strona dla intencji wyszukiwarkowej i użytkownika, ktory chce od razu klikac

Co nie dziala:

- to nie jest nowoczesny landing produktu
- wizualnie jest bardzo utilitarne
- okienka, bary i komunikaty techniczne obciazaja pierwszy ekran
- trudno tu zbudowac silny brand premium

Wniosek dla nas:

- warto zapamietac `natychmiastowy start`
- nie warto isc w `portal article + embedded test` jako glowna twarz produktu
- dobry kierunek dla nas to raczej:
  - landing produktowy na wejsciu,
  - i bardzo szybkie wejscie do realnego flow po zalogowaniu

Mobile:

- funkcjonalne, ale surowe
- nadal mocno tekstowe
- odczucie bardziej "narzedzie" niz "produkt"

Ocena wzorcowa:

- `hero`: praktycznie nie istnieje jako marketingowy hero
- `nawigacja`: wtornie wazna
- `proof`: slaby jako brand proof, mocny jako proof uzytecznosci
- `mobile`: poprawne, ale surowe
- `rytm`: bardzo techniczny

## Porownanie przekrojowe

### Nawigacja

- najlepsza: `Superprawko`
- najmocniejsza sprzedazowo: `Prawko.pl`
- najbardziej przeciazona: `Prawko.pl`, `Prawo-Jazdy-360.pl`, `IMAGE`
- najmniej istotna przez utility-first: `Teoria.pl`

### Hero

- najlepszy: `Superprawko`
- najsilniejszy claimowo: `Prawko.pl`, `Prawo-Jazdy-360.pl`
- najslabszy i najbardziej przestarzaly: `IMAGE`
- najbardziej funkcjonalny, ale najmniej brandowy: `Teoria.pl`

### Trust signals

- najlepsze: `Prawko.pl`, `Prawo-Jazdy-360.pl`
- wystarczajace, ale oszczedne: `Superprawko`
- rozproszone i mniej eleganckie: `IMAGE`

### Product proof

- najlepszy: `Superprawko`
- dobry: `Prawko.pl`
- sredni: `Prawo-Jazdy-360.pl`
- najslabszy: `IMAGE`
- funkcjonalny, nie marketingowy: `Teoria.pl`

### Mobile discipline

- najlepsza: `Superprawko`
- dobra, ale ciezka: `Prawko.pl`
- poprawna, ale zbyt dluga: `Prawo-Jazdy-360.pl`
- slaba: `IMAGE`
- surowa: `Teoria.pl`

## Co bierzemy od kogo

Od `Superprawko`:

- hero oparty o realny ekran produktu
- krotkie sekcje
- spokojny premium rytm
- mniej tekstu, wiecej produktu

Od `Prawko.pl`:

- social proof
- poczucie skali
- wiecej zaufania na wejściu

Od `Prawo-Jazdy-360.pl`:

- widoczne kategorie
- jasne komunikaty rynkowe
- sygnaly aktualnosci i zgodnosci z egzaminem

Od `Teoria.pl`:

- przypomnienie, ze uzytkownik chce szybko przejsc do realnego testu

Od `IMAGE`:

- glownie lekcja ostrzegawcza, zeby nie zamienic produktu w katalog i content farm

## Czego nie kopiowac

- ciezkiej nawigacji z wieloma rownoleglymi bytami
- portalowego rytmu SEO
- bardzo dlugich tasiemcowych landingow bez mocnych zmian tempa
- sekcji pisanych tylko pod wyszukiwarke, a nie pod czytelnika
- przypadkowych siatek kart i blogowych blokow na glownej

## Kierunek dla naszego frontu

### Glowne zalozenie

Nasz landing ma wygladac jak `nowoczesny produkt edukacyjny`, nie jak:

- portal,
- blog,
- katalog szkoleniowy,
- ani strona "sprzedajaca dostep do bazy" bez charakteru.

### Jak powinien wygladac nasz pierwszy ekran

1. mocny naglowek z jasna obietnica
2. prawdziwy ekran pytania / progresu / review queue
3. krotki proof rowniez nad foldem
4. bardzo jasne CTA
5. od razu widoczne kategorie lub przynajmniej kategoria `B`

### Jak powinien wygladac srodek strony

- jedna sekcja o metodzie nauki
- jedna sekcja o funkcjach `review queue`, `hard questions`, `analytics`
- jedna sekcja o "jak to dziala"
- jedna sekcja trust / liczby / gotowosc do egzaminu
- finalne CTA

Nie robic wiecej niz potrzeba.

### Jak powinno wygladac mobile

- mniej kart na ekran
- mocniejsze pionowe tempo
- zadnych malych, ciasnych siatek na stronie glownej
- kazda sekcja musi miec wyrazny powod istnienia

## Decyzja projektowa

Na tym etapie jako glowny benchmark dla redesignu traktujemy:

1. `Superprawko` jako wzor nowoczesnej dyscypliny
2. `Prawko.pl` jako wzor zaufania i skali
3. `Prawo-Jazdy-360.pl` jako wzor widocznosci kategorii i claimow

`Teoria.pl` i `IMAGE` zostaja jako wazne referencje konkurencyjne, ale bardziej w roli ostrzezen niz wzorow.
