# Trzeci przeglad redakcyjny: pozostale wyjasnienia publiczne

Data: 2026-08-13
Status: zakonczony bezpieczny import produkcyjny; pozostaly backlog redakcyjny
Zakres: 217 wyjasnien pozostalych po dwoch poprzednich, bezpiecznych importach
zasad do `questions.explanation`.

## Wynik przegladu

| Grupa | Liczba | Decyzja |
| --- | ---: | --- |
| Gotowa, jawnie oznaczona regula | 92 | Kandydaci do osobnego preview i canary |
| Zasada w formie listy lub dwoch regul | 6 | Decyzja redakcyjna, bez automatu |
| Opis sytuacji bez gotowego skrotu | 119 | Rzeczywisty backlog redakcyjny |
| Razem | 217 | Wszystkie pozycje przypisane |

Nie powstala zadna regula na podstawie AI, podobienstwa tresci, numeru pytania
ani samej poprawnej odpowiedzi. Wszystkie 92 kandydaty maja w opublikowanym
zrodle jedna jawna etykiete oraz tekst reguly w tym samym akapicie albo
bezposrednio pod naglowkiem `Zapamietaj`.

## 92 gotowe reguly

Parser przyjmuje tylko zdefiniowane formy redakcyjne:

- `**Zasada do zapamietania: tresc**` w jednym pogrubieniu;
- etykiete i tresc w tym samym akapicie, takze po krotkim "schemacie
  myslenia";
- `Zapamietaj:` i `Regula do zapamietania:` w tym samym akapicie;
- naglowek `## Zapamietaj` oraz nastepujaca po nim jedna, pogrubiona regula;
- dotychczasowe formy `Zasada do zapamietania` i `Prosta zasada`.

Markdown wewnatrz reguly jest zachowywany. W szczegolnosci zapis
`**A-6c** ... **A-6e**` nie jest juz mylony z regula pogrubiona w calosci.
Regula ma nadal po normalizacji od 20 do 600 widocznych znakow.

### Kandydaci z alternatywna etykieta

`290`, `6739`, `6759`, `6777`, `6782`, `6832`, `7532`, `10100`, `10118`,
`10883`, `10890`, `10992`, `11005`, `11022`, `11073`, `11307`, `11425`,
`11442`, `11457`, `11475`, `11487`, `11489`, `11500`, `11515`, `11517`,
`13069`, `13391`, `13437`, `13441`, `13444`, `13455`, `13459`, `13463`,
`13467`, `13517`, `pj360:2464`, `pj360:2466`, `pj360:3451`.

### Kandydaci z poprawna regula i niestandardowym Markdownem

`1258`, `1523`, `3729`, `6483`, `6501`, `6503`, `6505`, `6506`, `6519`,
`6520`, `6535`, `6545`, `6546`, `6556`, `6557`, `6559`, `6562`, `6568`,
`6592`, `6595`, `6626`, `6644`, `6645`, `6763`, `6816`, `7133`, `7551`,
`8073`, `8116`, `9366`, `11080`, `13127`, `13142`, `13144`, `13150`,
`13155`, `13156`, `13161`, `13170`, `13175`, `13207`, `13226`, `13299`,
`13324`, `13385`, `13394`, `13404`, `13406`, `13448`, `pj360:2220`,
`pj360:2271`, `pj360:2469`, `pj360:2858`, `pj360:3944`.

Przyklady sprawdzone w audycie:

- `7133`: pozostaja wewnetrzne pogrubienia znakow `A-6c` i `A-6e`;
- `10100`: regula jest bezposrednio pod naglowkiem `Zapamietaj`;
- `13391`: regula ma rownowazna etykiete `Regula do zapamietania`.

## Szesci przypadkow wymagajacych decyzji

Nie sa bledne merytorycznie, ale nie maja pojedynczej, gotowej reguly w
standardzie wyswietlania nauki.

| External ID | Powod |
| --- | --- |
| `11018` | Naglowek oraz osobna regula liczbowa, ktora trzeba zatwierdzic jako jeden skrot. |
| `6194` | Trzy-elementowa lista rozrozniajaca wymijanie, omijanie i wyprzedzanie. |
| `6205` | Ta sama klasyfikacja manewrow, w innej kolejnosci. |
| `8317` | Dwie reguly `A-9` i `A-10` w jednym bloku. |
| `13575` | Dwa warianty pierwszenstwa na rondzie (`C-12` z `A-7` i bez niego). |
| `13629` | Dwie zaleznosci predkosci wzglednej, zapisane jako lista. |

Zostaly zatwierdzone redakcyjnie 2026-08-13. Nie zostaly dodane do ogolnego
parsera tekstowego: obsluguje je osobna, zamknieta lista zatwierdzonych zasad.

| External ID | Status |
| --- | --- |
| `11018` | Zaimportowane do 4 kategorii w runie `77` |
| `6194` | Zaimportowane do 11 kategorii w runie `79` |
| `6205` | Zaimportowane do 11 kategorii w runie `79` |
| `8317` | Zaimportowane do kategorii B w runie `79` |
| `13629` | Zaimportowane do 11 kategorii w runie `79` |
| `13575` | Pozostawione bez zmian: cala grupa 11 kategorii ma wczesniejsza reczna edycje |

Regula `13575` nie zostala wymuszona przez flage nadpisania. To celowe:
dotychczasowy tekst administratora ma pierwszenstwo przed automatycznym lub
seryjnym importem.

## 119 opisow do redakcji

Pozostale pozycje nie maja wyroznionej, krotkiej zasady. Zawieraja pelne
omowienie sytuacji, wyjasnienie odpowiedzi, ostrzezenie, wyjatek lub komentarz
techniczny. Nie nalezy z nich automatycznie wycinac ostatniego zdania ani
tworzyc skrotu bez zatwierdzenia merytorycznego.

Pelnym zrodlem do dalszej pracy jest
`output/reports/public-memory-rules-editorial-review-20260813.json`, ktore
zawiera tresc, pytanie, kategorie, medium i URL dla kazdego rekordu.

## Kontrola techniczna

- testy parsera: 14 testow;
- testy preview, zapisu, stalego manifestu i rollbacku: 12 testow;
- lacznie: 26 testow i 93 asercje;
- Laravel Pint: bez uwag.

## Wykonanie produkcyjne

Najpierw wdrozono tylko parser `PublicExplanationMemoryRuleExtractor` i
wyczyszczono cache aplikacji. Trzy reprezentatywne zrodla zostaly odczytane
bez zmiany danych: `10100`, `13391` i `7133`. W przypadku `7133` parser
zachowal osobne pogrubienia `A-6c` i `A-6e`.

| Etap | Run | Wynik |
| --- | ---: | --- |
| Preview wszystkich 92 kandydatow | `70` | 89 grup, 329 pytan gotowych; 3 grupy pominiete przez reczna edycje |
| Canary `13142` | preview `71`, zapis `72` | 1 grupa, 11 kategorii, bez pominiec |
| Pelny preview bez canary | `73` | 88 grup, 318 pytan gotowych; te same 3 grupy pominiete |
| Pelny zapis z manifestu `73` | `74` | 88 grup, 318 pytan zaktualizowanych, 0 pominiec po ponownej kontroli |
| Preview szesciu zatwierdzonych zasad | `75` | 5 grup, 38 pytan gotowych; `13575` pominiete przez reczna edycje |
| Canary redakcyjny `11018` | preview `76`, zapis `77` | 1 grupa, 4 kategorie, bez pominiec |
| Pelny zapis pozostalych zasad | preview `78`, zapis `79` | 4 grupy, 34 pytania, 0 pominiec po ponownej kontroli |

Canary zostalo sprawdzone na prawdziwym koncie testowym w `/nauka/teraz`.
Pytanie `13142` pokazalo krotka regule w komponencie wyjasnienia. Konto i
sesja testowa zostaly nastepnie usuniete z produkcji.

Grupy `6739`, `7133` i `13069` zostaly wykluczone jako calosc, poniewaz
zawieraja wczesniejsze reczne edycje administratora. Nie zostal nadpisany
zaden z ich rekordow.

Canary `11018` zostalo rowniez sprawdzone w prawdziwej sesji kategorii B.
Zatwierdzona regula wyswietla sie obok pytania i odpowiedzi. Tymczasowe konto
oraz sesja zostaly po kontroli usuniete.

## Kontrola po zapisie

- run `72`: 11 snapshotow ze statusem `applied`, 0 niezgodnych hashy;
- run `74`: 318 snapshotow ze statusem `applied`, 0 niezgodnych hashy;
- grupa `13441`: jeden identyczny tekst we wszystkich 11 kategoriach;
- rollback preview: run `72` ma 11 rekordow do odtworzenia i 0 konfliktow,
  run `74` ma 318 rekordow do odtworzenia i 0 konfliktow;
- run `77`: 4 snapshoty ze statusem `applied`, 0 niezgodnych hashy;
- run `79`: 34 snapshoty ze statusem `applied`, 0 niezgodnych hashy;
- grupa `6194`: jeden identyczny tekst we wszystkich 11 kategoriach;
- rollback preview: run `77` ma 4 rekordy do odtworzenia i 0 konfliktow,
  run `79` ma 34 rekordy do odtworzenia i 0 konfliktow;
- HTTP strony glownej: `200`, `/nauka/teraz` dla niezalogowanego uzytkownika:
  `302` do logowania, PHP-FPM aktywny, cache konfiguracji aktywny.

## Dalsza praca redakcyjna

Do pozniejszego, recznego przygotowania pozostaje 119 opisow sytuacji bez
jednej, jawnie oznaczonej zasady. Nie nalezy ich importowac automatycznie ani
skrotowac na podstawie samego kontekstu pytania.
