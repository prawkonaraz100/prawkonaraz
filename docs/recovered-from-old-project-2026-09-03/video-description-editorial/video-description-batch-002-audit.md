# Audyt opisów video sitemap — batch 002

Data audytu: 2026-05-29 15:59 Europe/Warsaw
Zakres: 50 rekordów z `video-description-batch-002.csv`
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
| Minimalna długość | 181 znaków |
| Maksymalna długość | 212 znaków |
| Średnia długość | 197,0 znaków |
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

- Batch 002 utrzymuje zaakceptowany standard batcha 001.
- Szczególną uwagę poświęcono kadrom powtarzającym się w kilku pytaniach: opisy różnią punkt obserwacji bez dodawania odpowiedzi.
- W opisach celowo uniknięto słów z promptów, które brzmią jak rozstrzygnięcie testu, np. `powinieneś`, `masz obowiązek`, `wolno Ci`.
- To nadal draft redakcyjny. Przed podpięciem do sitemap rekomendowane jest zatwierdzenie batcha przez właściciela projektu.
