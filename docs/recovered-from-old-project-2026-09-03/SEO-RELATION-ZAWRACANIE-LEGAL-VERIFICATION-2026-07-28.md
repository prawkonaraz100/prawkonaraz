# P0: audyt redakcyjno-prawny pilota „Zawracanie”

Status: `COMPLETE FOR DEFINED SCOPE / PUBLIC V2 FULL-TOPIC ACTIVE`
Data weryfikacji: `2026-07-28`
Zakres: aktywny run V2 nr `2` dla `secondary:zawracanie`

## Cel i granice decyzji

Ten dokument zamyka zaplanowany P0 self-review dziesięciu reprezentatywnych
pytań z pilota. Sprawdza on, czy publiczna treść, materiał pytania i podstawa
prawna prowadzą do zgodnego wniosku, zanim graf byłby kiedykolwiek pokazany
użytkownikom.

Nie jest to porada prawna ani samodzielna zgoda na publiczny canary. Po
oddzielnej decyzji właściciela, preview i backupach run nr 2 działa w
`mode=canary` z ekspozycją `100%` dla wszystkich 29 źródeł
`secondary:zawracanie`; szczegóły operacyjne są w
`docs/SEO-RELATION-V2-CANARY-RUNBOOK.md`.

## Metoda i materiał dowodowy

1. Wykonano tylko-odczytowy eksport produkcyjny: promptu, odpowiedzi,
   opublikowanego wyjaśnienia, metadanych, medium i `QuestionLegalReference`.
2. Sprawdzono pełne medium dla wszystkich 10 pytań: osiem obrazów oraz plakaty
   dwóch filmów. Dla filmów `1178` i `3465` obejrzano dodatkowo klatki z około
   `0,5`, `3`, `6` i `9` sekundy.
3. Każde pytanie ma na produkcji przypisane podstawy prawne o statusie
   `verified` i confidence `90`; zweryfikowano także ich zgodność z aktualnym
   źródłem urzędowym.
4. Porównanie wykonano z oficjalnym, opublikowanym tekstem Prawa o ruchu
   drogowym oraz rozporządzenia o znakach i sygnałach drogowych. Użyto w
   szczególności art. 5, 22 i 25 oraz §§ 22, 36, 44, 87 i 97.

## Aktualność podstaw prawnych

Punktem odniesienia jest urzędowy tekst jednolity ustawy – Prawo o ruchu
drogowym oraz urzędowy tekst rozporządzenia o znakach i sygnałach drogowych:

- [Prawo o ruchu drogowym – Dz.U. 2024 poz. 1251](https://eli.gov.pl/api/acts/DU/2024/1251/text.html),
- [rozporządzenie w sprawie znaków i sygnałów drogowych – tekst urzędowy](https://eli.gov.pl/api/acts/DU/2019/454/text.html).

Sprawdzono także obowiązujące zmiany z 2026 r. Rozporządzenie Dz.U. 2026
poz. 133 zmienia §§ 54, 62–67, 77, 84 i 86, a nie §§ 22, 36, 44, 87 lub 97.
Ustawa Dz.U. 2026 poz. 180 zmienia m.in. art. 2, 6, 53 i 66, a nie art. 22
ani 25. To uzasadnia wniosek, że wskazane niżej przepisy pozostają właściwą
podstawą dla audytowanego zakresu. [Dz.U. 2026 poz. 133](https://eli.gov.pl/api/acts/DU/2026/133/text/O/D20260133.pdf),
[Dz.U. 2026 poz. 180](https://eli.gov.pl/api/acts/DU/2026/180/text/O/D20260180.pdf).

## Macierz weryfikacji

| ID | Co potwierdza materiał | Reguła weryfikowana | Wynik |
|---:|---|---|---|
| 352 | Widoczny znak D-3 na jezdni jednokierunkowej. | D-3 oznacza ruch w jednym kierunku; zawracanie na drodze jednokierunkowej jest zakazane. | `PASS` — odpowiedź „NIE” i wyjaśnienie są zgodne. |
| 7468 | Pierwszym znakiem informacyjnym jest D-3. | Art. 22 ust. 6 pkt 1 oraz § 44 ust. 1. | `PASS` — odróżnienie zawracania od cofania jest prawidłowe. |
| 1178 | Film pokazuje A-7 dla kierującego i ruch na drodze z pierwszeństwem. | Znak A-7 i obowiązek ustąpienia; nie sam fakt zawracania drugiego pojazdu. | `PASS` — treść nie tworzy błędnej reguły „zawracający zawsze ustępuje”. |
| 1427 | Widoczny B-21 „zakaz skręcania w lewo”. | B-21 zakazuje skrętu w lewo, a także zawracania. | `PASS` — § 22 ust. 1–2 zastosowano poprawnie. |
| 1518 | Widoczny B-23 „zakaz zawracania”. | B-23 zakazuje zawracania do najbliższego skrzyżowania włącznie, nie zakazuje sam w sobie skrętu w lewo. | `PASS` — poprawny kontrast wobec B-21. |
| 3465 | Film pokazuje C-12; przy wjeździe nie ma A-7 nadającego pierwszeństwo pojazdom już na rondzie. | C-12 sam oznacza ruch okrężny; pierwszeństwo dla pojazdu na rondzie wynika z połączenia C-12 + A-7. Przy braku A-7 działa reguła z art. 25 ust. 1. | `PASS` — odpowiedź „TAK” jest zgodna z pokazanym układem. |
| 1433 | Widoczne pasy i pas dzielący umożliwiające oczekiwanie na manewr. | Art. 22 ust. 1 i ust. 6 pkt 4: szczególna ostrożność, brak zagrożenia i utrudnienia. | `PASS / WARUNKOWE` — poprawne wyłącznie jako ocena tego kadru; nie może być przedstawiane jako ogólne prawo do oczekiwania w pasie dzielącym. |
| 1473 | Zielony S-3 wskazuje wyłącznie kierunek w lewo, bez strzałki do zawracania. | S-3 dotyczy kierunków wskazanych strzałką. | `PASS` — odpowiedź „NIE” jest zgodna z § 97. |
| 1696 | Widoczny D-9 „Autostrada”. | Na autostradzie zawracanie jest zakazane. | `PASS` — art. 22 ust. 6 pkt 2 zastosowano prawidłowo. |
| 7150 | Lewy skrajny pas ma połączone P-8a i P-8b; widoczne są C-12 i A-7, bez B-23 ani S-3 ograniczającego kierunek. | Strzałka w lewo na skrajnym lewym pasie zezwala także na zawracanie, z wyjątkami B-23 i S-3. | `PASS` — odpowiedź „TAK” jest zgodna z § 87 ust. 1–2. |

Najważniejsze normy zastosowane w macierzy: art. 22 ust. 6 wymienia zakaz
zawracania na drodze jednokierunkowej i autostradzie oraz zakaz zależny od
zagrożenia lub utrudnienia ruchu; art. 25 ust. 1 reguluje pierwszeństwo przy
skrzyżowaniu. Rozporządzenie rozróżnia B-21 od B-23, C-12 od C-12+A-7, D-3,
strzałki P-8 oraz sygnał kierunkowy S-3. Te odczyty wynikają bezpośrednio z
urzędowych tekstów wskazanych powyżej.

## Wniosek dla grafu i subhubów

Audyt nie wykrył fałszywej relacji, która wymagałaby wycofania runu nr 2.
Potwierdza też, że powiązania powinny podkreślać **różnicę warunku prawnego**,
a nie jedynie wspólne słowo „zawracanie”:

- `352` i `7468`: wspólna reguła D-3 / droga jednokierunkowa;
- `1427` i `1518`: kontrast B-21 versus B-23;
- `1178` i `3465`: odrębne scenariusze pierwszeństwa — A-7 oraz rondo bez
  A-7 — nie powinny być scalane jako jedna prosta zasada;
- `1473` i `7150`: kontrast „S-3 tylko w lewo” versus P-8b na skrajnym lewym
  pasie;
- `1433`: materiał kontekstowy, nie kandydat na ogólną regułę lub osobny hub.

Pozostają w mocy wcześniej zapisane wykluczenia redakcyjne par
`1428|1490` oraz `10252|10435`; ten audyt ich nie zmienia.

## Bramka po audycie

| Kontrola | Wynik |
|---|---|
| Media reprezentatywne | `10/10` sprawdzonych; `2/2` filmów sprawdzonych klatkami |
| Zgodność treści, obrazu i podstawy prawnej | `10/10 PASS`, z jednym jawnym scenariuszem warunkowym (`1433`) |
| Wymagana zmiana danych pytania lub rankingu | `nie` |
| Publiczny HTML / indeksacja | `bez zmiany URL-i ani canonicali; V2 dla 29/29 źródeł topicu` |
| Publiczny canary V2 | `AKTYWNY — 100%, wszystkie 29 źródeł secondary:zawracanie` |

Canary uruchomiono po osobnej decyzji, backupie i pozytywnym preview. Kolejny
krok to obserwacja monitora, health/smoke i sygnałów SEO; przy błędzie należy
natychmiast wyłączyć `QUESTION_RELATIONS_V2_CANARY_ENABLED` zgodnie z
`docs/SEO-RELATION-V2-CANARY-RUNBOOK.md`.
