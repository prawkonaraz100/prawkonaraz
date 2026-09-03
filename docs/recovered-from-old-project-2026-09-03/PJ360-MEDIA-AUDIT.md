# PJ360 Media Audit

## Cel

Ten dokument zapisuje stan mediow dla pytan importowanych z PJ360 oraz wynik przebiegu `audit + enrich`.

Chodzi o dwie rozne klasy problemow:

- pytanie faktycznie nie ma medium,
- pytanie ma plik lokalnie, ale wyswietla sie slabiej przez brak metadanych lub posterow.

## Zakres

Na dzien `2026-04-15` audit objal pytania z `source = pj360` aktualnie obecne w bazie lokalnej.

Kanoniczny skrypt:

- [audit_and_enrich_pj360_media.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/audit_and_enrich_pj360_media.py)

Artefakty raportu:

- [pj360-media-audit-pre.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-media-audit/pj360-media-audit-pre.json)
- [pj360-media-audit-write.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-media-audit/pj360-media-audit-write.json)
- [pj360-media-audit-post.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-media-audit/pj360-media-audit-post.json)

## Stan Przed Enrichmentem

Dla `kat. B` przed enrichmentem:

- `questions_total = 53`
- `questions_with_media = 45`
- `questions_without_media = 8`
- `image_rows = 24`
- `video_rows = 21`
- `missing_media_files = 0`
- `videos_missing_posters = 21`

Wniosek:

- pliki mediowe byly juz lokalnie,
- ale wszystkie filmy PJ360 nie mialy `poster_path`,
- importer nie uzupelnial tez metadanych typu `width`, `height`, `duration_seconds`.

## Co Zrobil Enrichment

Skrypt:

1. sprawdzil, czy plik z `question_media.path` istnieje lokalnie,
2. probowal asset przez `ffprobe`,
3. wpisal do bazy:
   - `width`
   - `height`
   - `duration_seconds`
   - `bytes`
4. dla filmow bez posteru wygenerowal `full-poster.jpg` przez `ffmpeg`,
5. zapisal `poster_path` do `question_media`.

## Stan Po Enrichmentcie

Po enrichmentcie dla `kat. B`:

- `missing_media_files = 0`
- `videos_missing_posters = 0`
- `rows_missing_dimensions = 0`
- `videos_missing_duration = 0`

To oznacza:

- warstwa importu i przechowywania mediow PJ360 jest juz technicznie uzupelniona,
- remaining problemy z wyswietlaniem nie powinny byc juz zrzucane na brak posteru albo brak wymiarow.

## Runtime Fix 2026-04-15

Po pierwszym audycie wyszla jeszcze jedna, osobna przyczyna problemu z wyswietlaniem:

- rekordy `question_media` byly poprawne,
- payload runtime budowal poprawne URL-e,
- ale czesc skryptow PJ360 zapisywala pliki na sztywno do `storage/app/public-media`,
- podczas gdy runtime i serwer mediow obslugiwaly `MEDIA_LOCAL_ROOT`.

Efekt:

- baza wskazywala `imports/pj360/...`,
- URL publiczny trafial do serwera mediow,
- ale sam plik fizycznie lezal obok live rootu i konczyl jako `404`.

Naprawa:

1. skrypty PJ360 zostaly przepiete na `MEDIA_LOCAL_ROOT` z `.env`,
2. juz pobrane pliki `imports/pj360` zostaly zsynchronizowane do live media rootu,
3. testowe URL-e obrazu, filmu i posteru zaczely zwracac `200`.

To domyka warstwe:

- import pliku,
- enrichment metadanych,
- ekspozycja przez publiczny media root.

## Interpretacja

Jesli konkretne pytanie PJ360 nadal renderuje sie zle:

- najpierw sprawdzamy rekord w `question_media`,
- potem runtime / komponent renderujacy,
- dopiero na koncu wracamy do importu.

Nie nalezy mieszac pytan tekstowych bez medium z pytaniami, ktore medium maja, ale wyswietlaja sie nieidealnie.

## Kolejny Ruch

Jesli po enrichmentcie nadal zobaczymy zle wyswietlanie:

1. spisujemy `external_id`,
2. sprawdzamy lokalny rekord `question_media`,
3. porownujemy render w:
   - `/nauka/teraz`
   - `/egzamin`
4. debugujemy juz konkretny komponent frontendowy.
