# Pilot „Zawracanie” — pierwsza ocena wspomagana, batch 02

Data: 2026-07-27
Status: `ASSISTED FIRST PASS COMPLETE / SECOND REVIEW REQUIRED`
Publiczny rollout: `OFF`

## Zakres i wynik

Batch obejmuje 99 semantycznych kandydatur o priorytecie `sredni`. Setny
rekord tego priorytetu jest technicznym `locked_negative` kolizji ID i nie
podlega ocenie podobieństwa.

| Wynik | Liczba |
|---|---:|
| pozytywne | 67 |
| negatywne | 32 |
| pewność `high` | 86 |
| pewność `medium` | 13 |
| zmieniony typ względem propozycji | 59 |
| wymagające drugiej oceny | 99 |

Rozkład zaakceptowanych typów:

| Typ | Liczba |
|---|---:|
| `rozszerzenie` | 25 |
| `nie_pomyl_z` | 14 |
| `kontrast` | 7 |
| `tematyczne` | 6 |
| `wariant` | 6 |
| `blizniacze` | 5 |
| `ta_sama_zasada` | 4 |

Batch uzupełnił brakujący wcześniej typ `blizniacze`. Po zsumowaniu partii
wysokiej i średniej wszystkie siedem typów ma co najmniej jeden pozytywny
przykład, ale nie oznacza to jeszcze ukończenia gold setu.

## Kryteria oceny

Najsilniejsze relacje w tej partii dotyczą:

- różnic między sygnalizatorami `S-3f` i `S-3e`,
- zakresu znaków `B-21` i `B-23`,
- zależności między zezwoleniem na manewr a jego bezkolizyjnością,
- właściwego pasa ruchu przy znakach poziomych `P-8a` i `P-8b`.

Odrzucono pary, w których dwie niezależne przyczyny zakazu łączyła jedynie
odpowiedź `NIE` albo ogólny temat zawracania. Przykładem są przypadkowe
połączenia mostu, autostrady, znaku `B-1`, `B-21` lub linii `P-4` bez wspólnej
reguły i bez typowej pułapki egzaminacyjnej.

## Artefakty

- decyzje audytowalne:
  `resources/seo/question-relation-gold-set/v1/review-batches/assisted-medium-priority-v1.json`,
- skumulowany CSV 408 par z ocenami wysokimi i średnimi:
  `output/seo-relation-zawracanie-20260727-medium/zawracanie-reviewed-assisted-through-medium-v1.csv`,
- skoroszyt niezależnej drugiej oceny partii średniej:
  `output/seo-relation-zawracanie-20260727-medium/zawracanie-medium-priority-review-v1.xlsx`,
- manifest hashy wyników:
  `output/seo-relation-zawracanie-20260727-medium/assisted-medium-priority-v1.manifest.json`.

## Walidacja skumulowana

Tryb `--allow-partial` zakończył się bez błędów:

- baseline: 408 rekordów,
- semantyczne: 394,
- ocenione: 190,
- pozytywne: 132,
- negatywne: 58,
- oczekujące: 204,
- locked negatives: 14,
- pokryte typy pozytywne: 7/7,
- błędy integralności: 0.

Nie wykonano `--write`, nie utworzono finalnego gold setu i nie uruchomiono
`shadow`, canary ani publicznego V2.

## Druga ocena

Skoroszyt zawiera 99 edytowalnych decyzji oraz jeden rekord
`locked_negative` oznaczony jako `not_required`. Drugi reviewer powinien:

1. ocenić każdą z 99 par niezależnie,
2. wybrać `approve`, `change` albo `reject`,
3. przy `change` wskazać poprawny typ,
4. przy `reject` podać uzasadnienie,
5. nie zmieniać rekordu `not_required`.

Dopiero po scaleniu drugiej oceny można uznać batch za ręcznie zatwierdzony.
