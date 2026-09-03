# Audyt opisów video sitemap — batch 001

Data audytu: 2026-05-29 15:50 Europe/Warsaw
Zakres: 50 pierwszych rekordów z `video-description-batch-001.csv`
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
| Minimalna długość | 188 znaków |
| Maksymalna długość | 232 znaki |
| Średnia długość | 215,1 znaków |
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

- Batch 001 został przygotowany na podstawie publicznych URL-i, promptów pytań i miniatur/posterów filmów.
- Opisy unikają formy `Tak/Nie`, nie cytują odpowiedzi i nie powtarzają obecnego szablonu produkcyjnego.
- Przy filmach korzystających z tego samego kadru opisy różnią się punktem obserwacji, kontekstem manewru albo rolą oznakowania.
- To nadal jest draft redakcyjny. Przed automatycznym podpięciem do sitemap warto zrobić finalny review batcha albo rozszerzyć audyt o pełne odtworzenie klipów, jeśli chcemy oceniać zmiany zachodzące po pierwszej klatce.
