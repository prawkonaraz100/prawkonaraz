# ADR-001: Modularny Monolit jako architektura startowa

## Status

Accepted

## Data

2026-03-18

## Kontekst

Projekt startuje jako:

- nowy produkt,
- z bardzo ograniczonym budzetem,
- z potrzeba szybkiego dowiezienia MVP,
- z ambicja wejscia pozniej w B2B i enterprise,
- z ryzykiem, ze zbyt wczesna nadarchitektura zje czas i budzet.

Jednoczesnie system ma kilka naturalnych obszarow domenowych:

- auth i profile,
- pytania i kategorie,
- sesje testowe,
- progres i powtorki,
- media,
- dashboard i analityka,
- pozniej organizacje B2B.

To tworzy pokuse, by od razu dzielic system na wiele osobnych uslug.

## Problem

Musimy wybrac architekture startowa, ktora:

- pozwala szybko budowac MVP,
- nie robi z operacji piekla,
- nie blokuje rozwoju do V2,
- nie wprowadza zbyt wielu granic sieciowych i deploymentowych za wczesnie.

## Decyzja

Na start wybieramy:

- `modularny monolit`

To oznacza:

- jedna aplikacja deployowalna,
- jedna glowna baza danych,
- wyraznie rozdzielone moduly domenowe,
- brak mikroserwisow na MVP,
- mozliwosc wydzielenia workera i komponentow pomocniczych pozniej.

## Co oznacza "modularny monolit" w tym projekcie

System powinien byc podzielony na moduly logiczne, na przyklad:

- `auth`
- `users`
- `categories`
- `questions`
- `media`
- `sessions`
- `progress`
- `analytics`
- `admin`
- w V2: `organizations`, `audit`

Kazdy modul powinien miec:

- wlasna logike domenowa,
- wlasne serwisy lub use-case layer,
- jawne granice odpowiedzialnosci,
- mozliwie maly wyciek zaleznosci do innych modulow.

## Uzasadnienie

Wybralismy modularny monolit, bo:

1. Najtaniej dowozi MVP.
2. Najlatwiej utrzymac go na jednym malym VPS.
3. Zmniejsza narzut operacyjny.
4. Ulatwia debugowanie.
5. Dobrze pasuje do jednej glownej bazy i jednego produktu.
6. Daje naturalna droge wzrostu do wydzielonego workera i V2.

## Odrzucone alternatywy

## 1. Mikroserwisy od startu

Odrzucone, bo:

- za drogie operacyjnie,
- za duzo deploymentow,
- za duzo granic sieciowych,
- za duzo problemow z auth, tracingiem i spojnocia,
- zbyt malo wartosci przy skali MVP.

## 2. Jeden wielki kod bez modulow

Odrzucone, bo:

- szybko zamienia sie w chaos,
- utrudnia rozwoj i testy,
- utrudnia wejscie w V2,
- zaciera granice domenowe.

## 3. Serverless-first wszystko

Odrzucone jako architektura glowna, bo:

- nie daje az takiej przewagi kosztowej przy tym typie produktu,
- komplikuje lokalne testowanie i czesc flow sesyjnych,
- i tak nie rozwiazuje sensownie wszystkiego bez dodatkowej zlozonosci.

## Konsekwencje pozytywne

- szybszy development,
- prostszy deploy,
- prostszy debugging,
- mniejsze koszty,
- mniej moving parts,
- latwiejsze utrzymanie malego zespolu.

## Konsekwencje negatywne

- jedna aplikacja pozostaje jednym artefaktem deployowym,
- trzeba pilnowac granic modulow recznie,
- przy wzroscie moze pojawic sie pokusa dokladania wszystkiego "gdziekolwiek",
- niektore obszary beda wymagaly wydzielenia pozniej.

## Jak ograniczamy ryzyka tej decyzji

1. Dokumentujemy moduly i ich odpowiedzialnosc.
2. Oddzielamy logike domenowa od frameworkowych szczegolow.
3. Nie mieszamy wszystkiego w jednej warstwie controllerow i action classes.
4. Traktujemy worker jako naturalny pierwszy kandydat do wydzielenia, ale nie jako mikroserwis "na pokaz".
5. Dla B2B dokladamy moduly organizacyjne, a nie nowy osobny system bez potrzeby.

## Kiedy ta decyzja powinna byc ponownie oceniona

Rewizja jest potrzebna, gdy:

- aplikacja ma kilka wyraznie niezaleznych domen z osobnym tempem zmian,
- worker i importy zyja juz praktycznie osobnym zyciem,
- pojedyncza aplikacja utrudnia deployment i skalowanie,
- pojawiaja sie twarde wymagania izolacji dla duzych klientow,
- system ma tak duza skale, ze granice procesowe przynosza realny zysk.

## Wplyw na implementacje

Ta decyzja implikuje:

- jedna glowna aplikacje,
- jedna glowna baza,
- wyrazny podzial kodu na moduly domenowe,
- osobne dokumenty dla API, DB i infrastruktury,
- brak przedwczesnego wejscia w mikroserwisy.

## Powiazane dokumenty

- [README.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/README.md)
- [ROADMAP-TECH.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ROADMAP-TECH.md)
- [INFRA-MVP-HETZNER-R2.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/INFRA-MVP-HETZNER-R2.md)
- [INFRA-V2-ENTERPRISE-B2B.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/INFRA-V2-ENTERPRISE-B2B.md)
