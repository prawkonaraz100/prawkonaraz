# Media Sitemap Readiness Plan

Status: w implementacji na branchu `codex/media-sitemap-readiness-audit`  
Data: 2026-05-28  
Domena: `https://prawkonaraz.pl`  
Zakres: media pytan, PJM, PJ360/explanation assets, image sitemap, przyszly video sitemap

## 1. Cel

Nie dodajemy image/video sitemap na slepo. Najpierw musimy wiedziec, czy publiczne URL-e mediow sa stabilne, crawlable i zgodne z wymaganiami Google.

Celem tego etapu jest:

- sprawdzic, ktore media publicznych pytan realnie istnieja na dysku,
- sprawdzic, ktore URL-e da sie bezpiecznie pokazac robotom,
- oddzielic obrazy pytan od filmow i posterow,
- potwierdzic, czy PJM/PJ360 nadaja sie do publicznej sitemap, czy zostaja tylko w produkcie,
- przygotowac bramke decyzyjna przed wdrozeniem video sitemap.

## 2. Wymagania Google, ktore nas dotycza

Zrodla:

- `https://developers.google.com/search/docs/crawling-indexing/sitemaps/image-sitemaps`
- `https://developers.google.com/search/docs/crawling-indexing/sitemaps/video-sitemaps`

Wnioski praktyczne:

- image sitemap moze byc osobna albo rozszerzac istniejaca sitemap,
- `image:loc` musi wskazywac URL obrazu, nie filmu,
- jeden wpis URL moze miec wiele obrazow, ale tylko jesli faktycznie naleza do tej strony,
- jezeli obrazy sa na osobnej domenie/CDN, ta domena powinna byc zweryfikowana w Search Console,
- video sitemap wymaga miniatury, tytulu, opisu oraz `content_loc` albo `player_loc`,
- wszystkie pliki z video sitemap musza byc dostepne bez logowania i nie moga byc blokowane przez `robots.txt` ani firewall.

## 3. Aktualny stan kodu

### Juz istnieje

- publiczne strony pytan: `/pytanie/{externalId}/{slug}`,
- sitemap per kategoria pytan,
- `image:image` w sitemap pytan,
- `QuestionMedia` dla obrazow i filmow,
- `QuestionSignLanguageAsset` dla PJM,
- `QuestionExplanationAsset` i `SharedQuestionExplanationAsset` dla assetow wyjasnien/PJ360,
- `MediaUrlResolver`, ktory buduje publiczne URL-e.

### Ryzyko znalezione w audycie kodu

W obecnej implementacji sitemap pytan uzywala `poster_url` oraz `url` z primary media. Dla wideo `url` oznacza plik `.mp4`, wiec raw video mogl trafic do `image:loc`.

To nie jest poprawny kontrakt image sitemap. Dla wideo do `image:image` moze trafic tylko poster/thumbnail, a nie film.

## 4. Nowe narzedzie audytowe

Dodana komenda:

```bash
php artisan seo:audit-question-media-readiness
```

Opcje:

```bash
php artisan seo:audit-question-media-readiness \
  --report=storage/app/reports/question-media-sitemap-readiness.json \
  --http \
  --http-limit=100 \
  --http-timeout=5 \
  --fail-on-errors
```

Znaczenie:

- `--report` zapisuje pelny JSON z wynikami,
- `--http` sprawdza publiczne URL-e przez HTTP HEAD/GET,
- `--http-limit=0` oznacza sprawdzenie wszystkich URL-i,
- `--fail-on-errors` zwraca blad komendy, gdy sa blokery.

## 5. Co sprawdza audyt

### Publiczne pytania

- liczba aktywnych publicznych rekordow,
- liczba kanonicznych `external_id`,
- liczba pytan z mediami,
- liczba pytan wymagajacych medium, ale bez rekordu media.

### `question_media`

- podzial po `image` / `video`,
- dyski storage,
- MIME type,
- warianty,
- czy plik istnieje na skonfigurowanym dysku,
- czy URL jest rozwiazywany,
- czy URL jest HTTPS,
- dla obrazow: czy wyglada jak obraz,
- dla filmow: czy jest poster, duration, wymiary i poprawny MIME.

### PJM

- aktywne assety `ready` / `review_required`,
- role: pytanie i odpowiedzi,
- storage/URL,
- metadane duration/wymiary.

Decyzja na teraz: PJM nie idzie do video sitemap, bo to media do flow nauki, nie publiczne host-page video.

### PJ360 / explanation assets

- lokalne i wspoldzielone assety wyjasnien z `file_path`,
- storage/URL,
- gotowosc techniczna.

Decyzja na teraz: explanation assets nie ida automatycznie do sitemap, dopoki nie sa stabilna czescia publicznej strony pytania jako glowny asset.

## 6. Bramki decyzyjne

### Image sitemap

Mozemy rozwijac image sitemap, gdy:

- audyt nie pokazuje bledow storage/URL dla publicznych obrazow,
- `image:loc` zawiera tylko obrazy lub postery,
- nie ma raw `.mp4` w `image:loc`,
- domena mediow jest zgodna z kanoniczna domena albo zweryfikowana w Search Console.

### Video sitemap

Mozemy wdrozyc video sitemap dopiero gdy:

- kazde publiczne wideo ma poster,
- plik wideo jest dostepny bez logowania,
- publiczna strona pytania faktycznie hostuje albo osadza to wideo,
- mamy tytul i opis zgodny z widoczna trescia strony,
- `content_loc` albo `player_loc` nie wskazuje tego samego URL-a co `loc`,
- audyt produkcyjny z `--http` przechodzi bez blokerow.

## 7. Plan wykonania

1. Dodac read-only komemde audytowa.
2. Naprawic kontrakt image sitemap: dla wideo do image sitemap trafia tylko poster.
3. Dodac testy:
   - audyt przechodzi dla obrazu, wideo z posterem i PJM,
   - audyt blokuje wideo bez posteru,
   - sitemap nie wpisuje `.mp4` do `image:loc`.
4. Uruchomic testy lokalnie.
5. Uruchomic audyt lokalnie bez HTTP.
6. Po deployu uruchomic audyt produkcyjny z raportem JSON.
7. Na podstawie raportu zdecydowac:
   - czy rozszerzamy image sitemap,
   - czy najpierw naprawiamy media,
   - czy projektujemy osobny video sitemap.

## 8. Status

- [x] Audyt kodu modeli i sitemap.
- [x] Komenda `seo:audit-question-media-readiness`.
- [x] Ochrona image sitemap przed raw video URL.
- [x] Testy pokrywajace glowny kontrakt.
- [x] Pelny audyt produkcyjny po deployu.
- [x] Decyzja o image/video sitemap po wynikach produkcyjnych.

## 9. Wynik produkcyjny 2026-05-28

Deploy wykonany na `https://prawkonaraz.pl`.

Raport produkcyjny:

```txt
/var/www/prawkobit/current/storage/app/reports/question-media-sitemap-readiness-20260528154821.json
```

Wynik komendy:

```txt
Publiczne pytania: rows=17044 canonical=3576 with_media=15798 requires_media_without_media=0
Question media: total=23345 images=15122 videos=8223 image_candidates=23317 video_candidates=8195
PJM: total=1976 | explanation_assets=17 | http_checked=250
Readiness: image_sitemap=ready video_sitemap=ready_for_implementation video_blockers=0
Warnings: 16390 total
```

Typy ostrzezen:

- `video_missing_dimensions=8195`,
- `video_missing_duration=8195`.

Interpretacja:

- nie ma blokerow storage/URL dla publicznych mediow,
- produkcyjne URL-e nie maja lokalnego problemu `http://`,
- image sitemap jest gotowa do bezpiecznego dalszego rozwijania,
- video sitemap jest technicznie gotowa do implementacji, ale przed finalnym rolloutem warto uzupelnic metadane filmow (`duration_seconds`, `width`, `height`), bo to poprawi jakosc danych i diagnostyke.

Kontrole po deployu:

- `php artisan seo:refresh-sitemaps` zakonczone sukcesem,
- `php artisan seo:audit-sitemaps` zakonczone sukcesem,
- `php artisan ops:smoke-test` zakonczone sukcesem,
- `php artisan ops:smoke-test --require-media` zakonczone sukcesem,
- publiczne `https://prawkonaraz.pl/sitemap.xml` i `https://prawkonaraz.pl/sitemaps/questions-b.xml` zwracaja `200`,
- w `public/sitemaps` nie ma juz wzorca `<image:loc>...mp4`.

## 10. Nastepny ruch

Najpierw nie dokladamy osobnej video sitemap, tylko:

1. zostawiamy obecna image sitemap z poprawionym kontraktem posterow,
2. monitorujemy Search Console po odswiezeniu sitemap,
3. przygotowujemy osobny, maly etap enrichmentu metadanych wideo,
4. dopiero potem projektujemy video sitemap XML z testami na wymagane pola Google.
