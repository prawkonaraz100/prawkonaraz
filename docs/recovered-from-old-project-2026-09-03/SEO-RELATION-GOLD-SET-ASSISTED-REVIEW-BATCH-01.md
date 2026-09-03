# Pilot „Zawracanie” — pierwsza ocena wspomagana, batch 01

Data: 2026-07-27
Status: `ASSISTED FIRST PASS COMPLETE / SECOND REVIEW REQUIRED`
Publiczny rollout: `OFF`

## Zakres i wynik

Rozkład `source_priority=wysoki` wymagał korekty interpretacji:

- 101 rekordów ma wysoki priorytet w pełnym pakiecie,
- 10 z nich to techniczne `locked_negative` kolizji ID,
- 91 to rzeczywiste kandydatury do oceny semantycznej.

Pierwsza, wspomagana ocena 91 kandydatur zakończyła się wynikiem:

| Wynik | Liczba |
|---|---:|
| pozytywne | 65 |
| negatywne | 26 |
| pewność `high` | 68 |
| pewność `medium` | 23 |
| zmieniony typ względem propozycji | 26 |
| wymagające drugiej oceny | 91 |

Rozkład zaakceptowanych typów:

| Typ | Liczba |
|---|---:|
| `kontrast` | 33 |
| `nie_pomyl_z` | 22 |
| `tematyczne` | 5 |
| `rozszerzenie` | 3 |
| `ta_sama_zasada` | 1 |
| `wariant` | 1 |

Batch nie zawiera pozytywnego `blizniacze`; typ występuje w dalszych 303
kandydaturach i pełna walidacja nadal będzie go wymagać.

## Zasady pierwszej oceny

Para była akceptowana, jeżeli:

- uczy tej samej reguły lub dwóch jej bezpośrednich zastosowań,
- tworzy użyteczny kontrast warunków dozwolonego i zabronionego manewru,
- rozróżnia znaki, sygnały albo oznakowanie będące typową pułapką,
- jedno pytanie rozszerza warunek sprawdzany przez drugie.

Para była odrzucana, jeżeli wspólne było tylko słowo „zawracanie”, przeciwna
odpowiedź albo bardzo szeroka kategoria zakazów bez wspólnej reguły i pułapki.

## Artefakty

- decyzje audytowalne:
  `resources/seo/question-relation-gold-set/v1/review-batches/assisted-high-priority-v1.json`,
- roboczy CSV wszystkich 408 par z naniesionym batchem:
  `output/seo-relation-zawracanie-20260727/zawracanie-reviewed-assisted-v1.csv`,
- skoroszyt do niezależnej drugiej oceny:
  `output/seo-relation-zawracanie-20260727/zawracanie-high-priority-review-v1.xlsx`,
- manifest hashy wyników:
  `output/seo-relation-zawracanie-20260727/assisted-high-priority-v1.manifest.json`.

## Walidacja

Tryb `--allow-partial` zakończył się bez błędów:

- baseline: 408 rekordów,
- semantyczne: 394,
- ocenione: 91,
- oczekujące: 303,
- locked negatives: 14,
- błędy integralności: 0.

Seed, dane chronione i techniczne negatywy nie zostały zmienione. Nie wykonano
`--write`, nie utworzono finalnego gold setu i nie uruchomiono `shadow` ani
publicznego V2.

## Druga ocena

Skoroszyt zawiera osobne kolumny `decyzja 2. oceny`, `typ po 2. ocenie`,
`drugi reviewer` i `notatki 2. oceny`. Drugi reviewer powinien:

1. ocenić każdą z 91 par niezależnie,
2. wybrać `approve`, `change` albo `reject`,
3. przy `change` wskazać poprawny typ,
4. przy `reject` podać uzasadnienie,
5. nie zmieniać 10 rekordów `not_required`.

Dopiero po scaleniu drugiej oceny można uznać batch za ręcznie zatwierdzony.
