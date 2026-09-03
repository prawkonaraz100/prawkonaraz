# Audyt redakcyjny pilota „Zawracanie”

Status: `P0 COMPLETE / P1 CONTENT WORK REMAINS / SHADOW ONLY / PUBLIC CANARY OFF`

Data audytu: 2026-07-28

Zakres: produkcyjny topic `secondary:zawracanie`, run V2 nr `2`, rollout nr `1` w trybie `shadow` z ekspozycją `0%`.

## Decyzja po audycie

Nie uruchamiać publicznego canary bez osobnej decyzji właściciela. P0 zostało
zamknięte: dwa znane błędne linki `direct` wykluczono w wersjonowanym
artefakcie i aktywnym runie nr 2, a reprezentatywne pytania wraz z medium oraz
podstawami prawnymi przeszły self-review. Pozostaje praca P1: pełne dossier
treści, zatwierdzenie zakresów subhubów i redakcyjny przegląd różnic.

Ten dokument nie tworzy nowych publicznych URL-i, nie zmienia runu, rolloutu,
flag ani renderera V1.

## Stan potwierdzony na produkcji

| Kontrola | Wynik |
|---|---:|
| źródła V2 objęte runem | 29 |
| wybrane rekomendacje | 435 |
| rekomendacje na źródło | 15 |
| `closest` / `direct` | 181 |
| `context` / `same_subtopic` | 254 |
| źródła i targety nierozwiązywalne | 0 / 0 |
| błędy / ostrzeżenia audytu runtime | 0 / 0 |
| wspólne linki V1 i V2 | 201 z 435 (46,21%) |
| nowe względem V1 pozycje V2 | 234 |

Audyt runtime z 2026-07-28 ma status `ok`. Każdy zestaw V1 i V2 zawiera po
15 pytań, ale zmiana jest znacząca: średnio tylko 6,93 pozycji na źródło jest
wspólnych. To uzasadnia redakcyjne review, a nie jest błędem technicznym.

`closest` jest bezpieczną etykietą najbliższych kandydatów, a `context` oznacza
szerszy kontekst z tego samego podtematu. Nie należy przedstawiać 251 linków
`context` jako zweryfikowanej relacji „ta sama zasada” ani używać ich do
samodzielnego wniosku o intencji użytkownika.

## Materiał redakcyjny

Generator dossieru `zawracanie` zawiera 62 pytania kanoniczne, z czego 45 ma
zweryfikowaną podstawę prawną, a 17 wymaga uzupełnienia. To inny zakres niż
29 źródeł produkcyjnego runu: wszystkie 29 mają zweryfikowane primary
membership V2, lecz nie każde należy do tego samego dossieru prawnego.

### Proponowana struktura

Główny artykuł/hub pozostaje: **„Zawracanie: gdzie wolno, znaki i
sygnalizacja”**. Poniższe grupy są propozycją redakcyjnych subhubów; nie są
jeszcze rekordami `QuestionSeoTopic` ani stronami publicznymi.

| Priorytet | Proponowany subhub lub sekcja | Pokrycie kanoniczne / prawne | Źródła core V2 | Decyzja |
|---|---|---|---|---|
| P0 | `zawracanie-znak-b-21` — zakaz skrętu w lewo a zawracanie | 9 pytań z `§ 22 ust. 1–2` | 1427, 1428, 6019, 7274, 8359, 8391 | kandydat na samodzielny subhub |
| P0 | `zakaz-zawracania-b-23` — zakres zakazu i różnica względem B-21 | 11 pytań z `§ 22 ust. 5` | 1517, 1518, 8387, 8388, 8389, 10252 | kandydat na samodzielny subhub |
| P1 | `zawracanie-pas-strzalki-p-8-sygnalizator-s-3` | 15 pytań z `§ 87` i `§ 97` | 1473, 7150, 8439 | kandydat na subhub po weryfikacji 1473 i 7150 |
| P1 | `gdzie-nie-wolno-zawracac` — autostrada, ekspresówka, most, droga jednokierunkowa i bezpieczeństwo | 9 pytań z art. 22 ust. 6 | 352, 7468, 1490, 1491, 1492, 1514, 1696, 4211, 4596, 6181, 6185 | kandydat na samodzielny subhub |
| P2 | Pierwszeństwo przy zawracaniu i na rondzie | 1 pytanie z art. 25 ust. 2 + pytania pomostowe | 1178, 3465 | sekcja głównego hubu oraz link do istniejącego hubu pierwszeństwa; bez cienkiej strony |
| P2 | Miejsce oczekiwania i pas dzielący jezdnie | pytanie sytuacyjne | 1433 | sekcja „warunki manewru”, bez osobnego URL |

Pierwsze cztery grupy mają wystarczającą treść do przygotowania briefów
redakcyjnych. Dwie ostatnie są zbyt małe na autonomiczne strony i powinny być
traktowane jako kontrolowane mosty tematyczne, a nie sztucznie rozbudowane
huby.

## Wynik P0 przed canary

### P0 — znane błędne relacje bezpośrednie: zamknięte

Gold set oznacza poniższe skierowane pary jako `false`. Zostały dodane jako
jawne wykluczenia do artefaktu
`resources/seo/question-relation-shadow/zawracanie-v2-editorial-exclusions/`
i nie występują jako rekomendacje `direct` aktywnego runu nr 2:

| Źródło | Target | Score | Klasyfikacja generatora | Działanie |
|---|---:|---:|---|---|
| 1428 — B-21 na skrzyżowaniu | 1490 — autostrada | 0,3027 | `ta_sama_zasada` | `excluded` — dotyczą innych zasad prawnych |
| 10252 — B-23 | 10435 — podobne brzmienie | 0,3320 | `blizniacze` | `excluded` — osobne rozstrzygnięcie materiału wizualnego pozostaje P1 |

Korekta weszła przez nowy, walidowany run shadow, a nie przez ręczną zmianę
snapshotu. Audyt i monitor po imporcie runu nr 2 mają `0` błędów i `0`
ostrzeżeń.

### P0 — pokrycie podstawą prawną: zamknięte dla zdefiniowanej próbki

Sprawdzono pełne medium, treść i przypisania prawne dla dziesięciu pytań:
352, 7468, 1178, 1427, 1518, 3465, 1433, 1473, 1696 i 7150. Wynik to `10/10
PASS`; pytanie 1433 ma status `PASS / WARUNKOWE` i pozostaje przykładem
sytuacyjnym, nie ogólną regułą ani kandydatem na osobny hub. Szczegółowa
macierz, źródła ELI oraz sprawdzenie aktualności zmian prawnych znajdują się w
`docs/SEO-RELATION-ZAWRACANIE-LEGAL-VERIFICATION-2026-07-28.md`.

Ten wynik nie oznacza zamknięcia pełnego 62-pytaniowego dossier ani publikacji
subhubów. Jest bramką P0 dla aktywnego zakresu źródeł i reprezentatywnych
materiałów; każde nowe zastosowanie `direct` nadal wymaga weryfikacji obrazu
lub nagrania, a nie tylko podobieństwa promptów.

### P1 — fallback kontekstowy

254 z 435 rekomendacji (58,39%) to `context` z tego samego podtematu. Są
potrzebne, aby utrzymać minimum 15 linków, ale nie były oceniane jako relacje
semantyczne w gold secie. W publicznym komponencie mogą zostać pokazane tylko
pod uczciwą etykietą typu **„Więcej z tego tematu”**, oddzieloną od
najbliższych relacji. Nie mogą być opisane jako „ta sama zasada prawna”.

## Dalsza kolejność review (P1)

1. Zakończono: wykluczono w runie nr 2 pary `1428 → 1490` i `10252 → 10435`.
2. Zakończono: sprawdzono podstawy, medium i odpowiedzi 10 wskazanych źródeł.
3. W panelu `admin/relacje-v2-shadow` przejrzeć pozostałe źródła z 29,
   zaczynając od najmniejszego overlapu V1/V2: 1433 (4/15), 1427, 1428, 3465
   i 6185 (po 5/15).
4. Dla każdego z czterech dużych subhubów zatwierdzić zakres, tytuł, 3–5
   najważniejszych pytań oraz link do głównego artykułu. Dopiero potem
   utworzyć rekordy topiców i briefy stron.
5. Przy każdej kolejnej korekcie wygenerować nowy artefakt, zaimportować nowy
   run w `mode=shadow`, wykonać audyt V1 ↔ V2 oraz monitor. Publiczny canary
   wymaga odrębnej decyzji.

## Kryteria ukończenia etapu redakcyjnego

- 29 źródeł core ma przypisany jeden redakcyjny klaster i zweryfikowaną
  podstawę lub jawne uzasadnienie `context`.
- [x] Żadna znana para `false` z gold setu nie jest `direct` w aktywnym runie
  nr 2.
- Każdy kandydat na indeksowalny subhub ma własny zakres, unikalną treść oraz
  co najmniej trzy istotne pytania; mikrokategorie pozostają sekcjami lub
  linkami do istniejących hubów.
- [x] V1 pozostaje rendererem publicznym do czasu osobnego zatwierdzenia
  canary.

## Dowody i narzędzia

- dossier: `resources/legal-content/generated/agent-workspace/topics/zawracanie.json`,
- ranking offline: `resources/seo/question-relation-shadow/zawracanie-v2-editorial-exclusions/`,
- znane błędy: `evaluation/direct-labelled-errors.csv`,
- produkcyjna kontrola tylko do odczytu:

```bash
php artisan seo:audit-question-relation-v2-shadow \
  --topic-key=secondary:zawracanie \
  --sample-limit=29 \
  --fail-on-errors
```
