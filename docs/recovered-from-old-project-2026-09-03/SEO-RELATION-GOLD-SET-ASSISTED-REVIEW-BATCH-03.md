# Pilot „Zawracanie” — pierwsza ocena wspomagana, batch 03

Data: 2026-07-27
Status: `ASSISTED FIRST PASS COMPLETE / SECOND REVIEW REQUIRED`
Publiczny rollout: `OFF`

## Zakres i wynik

Batch obejmuje 204 semantyczne kandydatury o priorytecie `niski`. Trzy
dodatkowe rekordy tego priorytetu to techniczne `locked_negative` kolizji ID.

| Wynik | Liczba |
|---|---:|
| pozytywne | 202 |
| negatywne | 2 |
| pewność `high` | 189 |
| pewność `medium` | 15 |
| zmieniony typ względem propozycji | 165 |
| wymagające drugiej oceny | 204 |

Rozkład zaakceptowanych typów:

| Typ | Liczba |
|---|---:|
| `blizniacze` | 86 |
| `nie_pomyl_z` | 45 |
| `rozszerzenie` | 32 |
| `ta_sama_zasada` | 18 |
| `kontrast` | 9 |
| `wariant` | 7 |
| `tematyczne` | 5 |

Wysoki udział relacji pozytywnych nie wynika z obniżenia progu. Priorytet
`niski` oznaczał niższą pilność ręcznej weryfikacji, a ta kohorta zawierała
głównie silne duplikaty i warianty pytań o `S-3f`, `S-3e`, `B-21` oraz `B-23`.

## Kryteria oceny

Za wartościowe uznano między innymi:

- bliźniacze pytania sprawdzające tę samą regułę i ten sam znak,
- kontrasty `S-3f` zezwalającego na zawracanie i `S-3e`, który go nie dopuszcza,
- rozróżnienia zakresu znaków `B-21` i `B-23`,
- rozszerzenia łączące dopuszczalny kierunek z bezkolizyjnością sygnału,
- warianty prawidłowego pasa przy `P-8a` i `P-8b`.

Odrzucono dwie pary łączące niezależne miejsca zakazu bez wspólnej pułapki:
`D-3` z `D-9` oraz most z drogą jednokierunkową.

## Artefakty

- decyzje audytowalne:
  `resources/seo/question-relation-gold-set/v1/review-batches/assisted-low-priority-v1.json`,
- skumulowany CSV wszystkich 408 rekordów:
  `output/seo-relation-zawracanie-20260727-low/zawracanie-reviewed-assisted-through-low-v1.csv`,
- skoroszyt niezależnej drugiej oceny partii niskiej:
  `output/seo-relation-zawracanie-20260727-low/zawracanie-low-priority-review-v1.xlsx`,
- manifest hashy wyników:
  `output/seo-relation-zawracanie-20260727-low/assisted-low-priority-v1.manifest.json`.

## Pełna walidacja pierwszego przebiegu

Walidator uruchomiony bez `--allow-partial` i bez `--write` zakończył się
powodzeniem:

- baseline: 408 rekordów,
- semantyczne: 394,
- ocenione: 394,
- pozytywne: 334,
- negatywne: 60,
- oczekujące: 0,
- locked negatives: 14,
- pokryte typy pozytywne: 7/7,
- błędy integralności: 0.

To potwierdza kompletność techniczną pierwszego przebiegu, ale nie jest zgodą
na zamrożenie danych. Nie wykonano `--write`, nie utworzono finalnego gold setu
i nie uruchomiono `shadow`, canary ani publicznego V2.

## Druga ocena

Skoroszyt zawiera 204 edytowalne decyzje oraz trzy rekordy `locked_negative`
oznaczone jako `not_required`. Drugi reviewer powinien:

1. ocenić każdą z 204 par niezależnie,
2. wybrać `approve`, `change` albo `reject`,
3. przy `change` wskazać poprawny typ,
4. przy `reject` podać uzasadnienie,
5. nie zmieniać rekordów `not_required`.

Po drugiej ocenie wszystkich trzech batchy trzeba scalić rozbieżności,
ponownie uruchomić pełną walidację i dopiero wtedy podjąć osobną decyzję o
zamrożeniu gold setu.
