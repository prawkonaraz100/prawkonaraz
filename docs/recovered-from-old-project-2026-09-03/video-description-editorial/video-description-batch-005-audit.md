# Audyt opisów video sitemap — batch 005

Data audytu: 2026-05-29 17:14 Europe/Warsaw
Zakres: 50 rekordów z `video-description-batch-005.csv`
Status: PASS dla roboczego draftu redakcyjnego

## Co sprawdzono

- kompletność pola `proposed_video_description`,
- unikalność dokładną i po normalizacji tekstu,
- długość opisów względem roboczego zakresu 160-320 znaków,
- powtarzalne początki opisów po 5 i 7 pierwszych słowach,
- obecność starych fraz szablonowych z produkcyjnej wersji sitemap,
- przypadkowe skopiowanie promptu pytania,
- przypadkowe pozostawienie obecnego opisu produkcyjnego,
- ryzykowne frazy brzmiące jak odpowiedź lub podpowiedź egzaminacyjna.

## Wynik

| Metryka | Wynik |
| --- | ---: |
| Liczba rekordów | 50 |
| Wypełnione opisy | 50 |
| Unikalne opisy | 50 |
| Grupy duplikatów po normalizacji | 0 |
| Minimalna długość | 178 znaków |
| Maksymalna długość | 206 znaków |
| Średnia długość | 192,8 znaków |
| Opisy krótsze niż 160 znaków | 0 |
| Opisy dłuższe niż 320 znaków | 0 |
| Złe początki typu `Tak`, `Nie`, `Czy`, `Film do pytania` | 0 |
| Stare frazy szablonowe | 0 |
| Opisy identyczne z promptem | 0 |
| Opisy identyczne z obecnym opisem produkcyjnym | 0 |
| Powtarzalne początki 7-wyrazowe | 0 |
| Powtarzalne początki 5-wyrazowe | 0 |
| Ryzykowne frazy odpowiedziowe | 0 |

## Uwagi redakcyjne

- Batch 005 obejmuje głównie kategorię AM: wyprzedzanie, omijanie, odstępy, światła, przystanki, zmianę pasa i postój.
- Przy powtarzających się kadrach opisy różnią punkt obserwacji: prawy pas, sąsiedni pojazd, przystanek, pobocze, most, sygnalizator albo wybrany pas ruchu.
- Opisy celowo unikają słów przeniesionych z promptów, takich jak `obowiązek`, `powinieneś`, `należy`, `wolno Ci`.
- To nadal draft redakcyjny. Przed podpięciem do sitemap rekomendowane jest zatwierdzenie batcha przez właściciela projektu.
