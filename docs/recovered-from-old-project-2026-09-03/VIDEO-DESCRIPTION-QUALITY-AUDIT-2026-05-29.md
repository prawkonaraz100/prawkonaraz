# Audyt jakosci `<video:description>` - 2026-05-29

## Cel

Sprawdzic, czy produkcyjne opisy w `https://prawkonaraz.pl/sitemaps/videos.xml`
sa nie tylko technicznie unikalne, ale tez wygladaja jak realnie unikalne,
redakcyjne opisy materialow wideo.

## Wynik krotki

Status: **niegotowe jako finalny standard PRO**.

Ostatnia naprawa poprawnie usunela najwiekszy blad: `video:description` nie
bierze juz odpowiedzi ani pola `explanation`. Produkcyjna sitemap jest
technicznie poprawna i bezpieczniejsza niz wczesniej.

Jednoczesnie audyt jakosci pokazuje, ze obecne opisy nadal sa widocznie
szablonowe. Sa unikalne 1:1, ale unikalnosc wynika glownie z numeru pytania,
kategorii i tresci promptu. To nie spelnia docelowego zalozenia: "naprawde
unikalne i wyjatkowe opisy, ktore nie wygladaja jak wygenerowane automatycznie".

## Dane z audytu produkcji

Zrodlo: `https://prawkonaraz.pl/sitemaps/videos.xml`

- Liczba `video:description`: `1215`
- Dokladnie unikalne opisy: `1215`
- Dokladne duplikaty: `0`
- Opisy zaczynajace sie od `Tak.` albo `Nie.`: `0`
- Stara fraza `Tak. Polecenie zatrzymania...`: `0`
- Minimalna dlugosc: `225` znakow
- Maksymalna dlugosc: `320` znakow
- Srednia dlugosc: `278.3` znakow

To jest dobry wynik techniczny.

## Problem jakosciowy

### 1. Tylko 5 bazowych szablonow

Po usunieciu numeru pytania i kategorii zostaje tylko `5` wariantow wstepu.
Rozklad:

- `261` - "Nagranie przypisane do pytania..."
- `245` - "Material wideo do pytania..."
- `244` - "Film przy pytaniu..."
- `241` - "Klip wideo dla pytania..."
- `224` - "Wideo powiazane z pytaniem..."

Wniosek: crawler i czlowiek moga latwo rozpoznac wzorzec automatycznego
generowania.

### 2. Za duza czesc opisu to boilerplate

- Srednio `61.8%` opisu to powtarzalny prefiks.
- `358` opisow ma co najmniej `65%` tekstu jako boilerplate.
- Najbardziej szablonowe opisy maja do `75.9%` prefiksu.

Przyklad:

`Klip wideo dla pytania 2397 dla kategorii A pokazuje sytuacje egzaminacyjna
na drodze, w ktorej liczy sie spokojna obserwacja, rozpoznanie zagrozen i
wlasciwa decyzja kierowcy. Temat sceny: w tej sytuacji wolno Ci kontynuowac
jazde.`

Wniosek: opis jest bezpieczny, ale nie jest redakcyjnie wyjatkowy.

### 3. Duza czesc opisow powtarza prompt pytania

- `630` opisow ma temat sceny praktycznie rowny promptowi pytania.
- `195` wpisow nalezy do grup z powtarzajacym sie tematem sceny.
- `97` wpisow jest duplikatem strukturalnym po usunieciu numeru pytania i
  kategorii.

Najczesciej powtarzane tematy:

- `19x` - "czy w tej sytuacji masz obowiazek zachowac szczegolna ostroznosc"
- `8x` - "czy w tej sytuacji masz obowiazek zatrzymac pojazd"
- `7x` - "czy w tej sytuacji masz prawo wyprzedzic pojazd, ktory jedzie przed toba"
- `5x` - "czy w tej sytuacji wolno ci zawrocic"

Wniosek: unikalnosc opisu jest czesto techniczna, a nie merytoryczna.

### 4. Slownictwo promptow nadal brzmi jak odpowiedz/test

`418` opisow zawiera slowa lub konstrukcje typu:

- `masz obowiazek`
- `musisz`
- `nalezy`
- `powinienes`
- `nie wolno`
- `mozesz`

To nie jest juz wyciek odpowiedzi z `explanation`, ale nadal brzmi bardziej
jak tresc pytania testowego niz opis filmu.

## Ocena ryzyka

### Niskie ryzyko techniczne

Sitemap dziala, audyt przechodzi, opisy sa w limicie i nie zawieraja starego
bledu z odpowiedzia.

### Srednie ryzyko SEO

Google raczej nie odrzuci sitemap tylko przez te opisy, ale warstwa tekstowa
nie daje pelnej wartosci. Opisy sa poprawne, lecz powtarzalne.

### Wysokie ryzyko jakosciowe wzgledem celu

Jesli celem jest content PRO, to obecny poziom nie wystarcza. To jest etap
bezpiecznego automatycznego fallbacku, a nie finalna redakcja.

## Przyczyna zrodlowa

Aktualny system nie ma osobnego zrodla prawdy dla redakcyjnego opisu filmu.
Generator korzysta z:

- numeru pytania,
- kategorii,
- promptu pytania,
- jednego z 5 szablonow.

Nie korzysta z faktycznej analizy kadru ani z recznie przygotowanego opisu
sceny. Bez takiego zrodla nie da sie uczciwie uzyskac opisow, ktore wygladaja
jak napisane indywidualnie dla 1215 filmow.

## Rekomendowany standard docelowy

Kazdy `video:description` powinien:

- opisywac scene, a nie pytanie,
- nie zaczynac sie od `Film do pytania...`,
- nie opierac unikalnosci na numerze pytania,
- miec ok. `180-320` znakow,
- zawierac konkret z kadru, np. skrzyzowanie, przejscie, sygnalizacje,
  rowerzyste, pojazd uprzywilejowany, policjanta, znak, tramwaj,
- nie zdradzac odpowiedzi,
- nie brzmiec jak tresc testu,
- byc przejrzany redakcyjnie albo co najmniej zatwierdzony w procesie QA.

## Przyklad obecny vs docelowy

Obecnie:

`Klip wideo dla pytania 100 dla kategorii A pokazuje sytuacje egzaminacyjna
na drodze, w ktorej liczy sie spokojna obserwacja, rozpoznanie zagrozen i
wlasciwa decyzja kierowcy. Temat sceny: ocena, czy w tej sytuacji masz obowiazek
zatrzymac pojazd.`

Docelowo:

`Nagranie pokazuje dojazd do miejsca, w ktorym osoba kierujaca ruchem daje
sygnal zatrzymania. Opis sceny skupia sie na obserwacji gestu, ustawieniu
pojazdu i reakcji kierowcy przed dalsza jazda.`

## Plan naprawy PRO

### Faza 1 - zostawic obecny generator jako fallback

Nie usuwac obecnego generatora. Jest bezpieczny i lepszy niz poprzedni stan.
Ma dzialac tylko wtedy, gdy nie mamy opisu redakcyjnego.

### Faza 2 - dodac redakcyjne zrodlo danych

Rekomendacja: dodac pole `seo_description` dla rekordu wideo, najlepiej na
poziomie `question_media` albo w metadatach `question_media.metadata`.

Najlepszy wariant dlugoterminowy:

- `question_media.seo_title`
- `question_media.seo_description`
- `question_media.seo_reviewed_at`
- `question_media.seo_reviewed_by`

MVP moze zaczac od `metadata.video_seo_description`, ale panel admina i audyt
beda wygodniejsze przy osobnych kolumnach.

### Faza 3 - przygotowac liste 1215 opisow do redakcji

Wygenerowac CSV/JSON:

- ID pytania,
- URL strony,
- obecny opis,
- prompt,
- kategoria,
- URL filmu,
- URL postera,
- pole na opis redakcyjny,
- status QA.

### Faza 4 - tworzyc opisy partiami

Nie robic 1215 opisow naraz w ciemno. Pracowac batchami:

- batch 1: `50` opisow,
- QA stylu,
- poprawka zasad,
- batch 2: `200` opisow,
- QA podobienstwa,
- reszta po stabilizacji wzorca.

### Faza 5 - audyt automatyczny przed publikacja

Kazdy opis redakcyjny musi przejsc:

- brak `Tak.` / `Nie.` na poczatku,
- brak `Poprawna odpowiedz`,
- dlugosc `180-320`,
- brak duplikatu dokladnego,
- brak duplikatu po normalizacji numerow/kategorii,
- niski udzial powtarzalnego boilerplate,
- brak startu od generycznego `Film do pytania`.

## Decyzja

Obecny stan produkcyjny zostaje jako bezpieczny fallback.

## Postep redakcyjny - batch 001

Utworzono roboczy workflow redakcyjny w katalogu:

`docs/video-description-editorial/`

Pliki:

- `video-description-editorial-master.csv` - pelny eksport `1215` rekordow z sitemap.
- `video-description-editorial-master.json` - pelny eksport `1215` rekordow w formacie JSON.
- `video-description-batch-001.csv` - pierwsze `50` rekordow do redakcji.
- `video-description-batch-001-proposals.json` - recznie przygotowane propozycje opisow dla batcha 001.
- `video-description-batch-001-audit.md` - wynik audytu jakosci batcha 001.
- `batch-001-posters-contact-sheet.jpg` - arkusz miniatur uzyty do kontroli wizualnej.

Wynik batcha 001:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `188-232` znaki, srednio `215.1`.

Status: batch 001 nadaje sie do finalnego review redakcyjnego. Nie jest jeszcze
podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 001

Jakosc batcha 001 zostala zaakceptowana jako wzorzec dalszej pracy. Rekordy
batcha 001 oznaczono statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 002

Utworzono drugi batch:

- `video-description-batch-002.csv`
- `video-description-batch-002-proposals.json`
- `video-description-batch-002-review.md`
- `video-description-batch-002-audit.md`
- `batch-002-posters-contact-sheet.jpg`

Wynik batcha 002:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `181-212` znakow, srednio `197.0`.

Status: batch 002 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Postep redakcyjny - batch 003

Utworzono trzeci batch:

- `video-description-batch-003.csv`
- `video-description-batch-003-proposals.json`
- `video-description-batch-003-review.md`
- `video-description-batch-003-audit.md`
- `batch-003-posters-contact-sheet.jpg`

Wynik batcha 003:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `179-209` znakow, srednio `191.0`.

Status: batch 003 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Postep redakcyjny - batch 004

Utworzono czwarty batch:

- `video-description-batch-004.csv`
- `video-description-batch-004-proposals.json`
- `video-description-batch-004-review.md`
- `video-description-batch-004-audit.md`
- `batch-004-posters-contact-sheet.jpg`

Wynik batcha 004:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `176-207` znakow, srednio `192.7`.

Status: batch 004 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batchy 002-004

Jakosc batchy 002-004 zostala zaakceptowana. Rekordy batchy 001-004 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 005

Utworzono piaty batch:

- `video-description-batch-005.csv`
- `video-description-batch-005-proposals.json`
- `video-description-batch-005-review.md`
- `video-description-batch-005-audit.md`
- `batch-005-posters-contact-sheet.jpg`

Wynik batcha 005:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `178-206` znakow, srednio `192.8`.

Status: batch 005 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 005

Jakosc batcha 005 zostala zaakceptowana. Rekordy batchy 001-005 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 006

Utworzono szosty batch:

- `video-description-batch-006.csv`
- `video-description-batch-006-proposals.json`
- `video-description-batch-006-review.md`
- `video-description-batch-006-audit.md`
- `batch-006-posters-contact-sheet.jpg`

Wynik batcha 006:

- `50/50` opisow uzupelnionych.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `177-204` znaki, srednio `188.0`.

Status: batch 006 zostal zaakceptowany po review i nie jest jeszcze podpiety do
sitemap ani do kodu aplikacji.

## Postep redakcyjny - batch 007

Utworzono siodmy batch:

- `video-description-batch-007.csv`
- `video-description-batch-007-proposals.json`
- `video-description-batch-007-review.md`
- `video-description-batch-007-audit.md`
- `batch-007-posters-contact-sheet.jpg`

Wynik batcha 007:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `169-202` znaki, srednio `187.2`.

Status: batch 007 zostal zaakceptowany po review i nie jest jeszcze podpiety do
sitemap ani do kodu aplikacji.

## Postep redakcyjny - batch 008

Utworzono osmy batch:

- `video-description-batch-008.csv`
- `video-description-batch-008-proposals.json`
- `video-description-batch-008-review.md`
- `video-description-batch-008-audit.md`
- `batch-008-posters-contact-sheet.jpg`

Wynik batcha 008:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `170-208` znakow, srednio `190.1`.

Status: batch 008 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 008

Jakosc batcha 008 zostala zaakceptowana. Rekordy batchy 001-008 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 009

Utworzono dziewiaty batch:

- `video-description-batch-009.csv`
- `video-description-batch-009-proposals.json`
- `video-description-batch-009-review.md`
- `video-description-batch-009-audit.md`
- `batch-009-posters-contact-sheet.jpg`

Wynik batcha 009:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `163-202` znaki, srednio `183.4`.

Status: batch 009 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 009

Jakosc batcha 009 zostala zaakceptowana. Rekordy batchy 001-009 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 010

Utworzono dziesiaty batch:

- `video-description-batch-010.csv`
- `video-description-batch-010-proposals.json`
- `video-description-batch-010-review.md`
- `video-description-batch-010-audit.md`
- `batch-010-posters-contact-sheet.jpg`

Wynik batcha 010:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `161-187` znakow, srednio `175.2`.

Status: batch 010 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 010

Jakosc batcha 010 zostala zaakceptowana. Rekordy batchy 001-010 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 011

Utworzono jedenasty batch:

- `video-description-batch-011.csv`
- `video-description-batch-011-proposals.json`
- `video-description-batch-011-review.md`
- `video-description-batch-011-audit.md`
- `batch-011-posters-contact-sheet.jpg`

Wynik batcha 011:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `161-188` znakow, srednio `170.3`.

Status: batch 011 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 011

Jakosc batcha 011 zostala zaakceptowana. Rekordy batchy 001-011 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 012

Utworzono dwunasty batch:

- `video-description-batch-012.csv`
- `video-description-batch-012-proposals.json`
- `video-description-batch-012-review.md`
- `video-description-batch-012-audit.md`
- `batch-012-posters-contact-sheet.jpg`

Wynik batcha 012:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `160-186` znakow, srednio `172.1`.

Status: batch 012 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 012

Jakosc batcha 012 zostala zaakceptowana. Rekordy batchy 001-012 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 013

Utworzono trzynasty batch:

- `video-description-batch-013.csv`
- `video-description-batch-013-proposals.json`
- `video-description-batch-013-review.md`
- `video-description-batch-013-audit.md`
- `batch-013-posters-contact-sheet.jpg`

Wynik batcha 013:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `161-180` znakow, srednio `167.3`.

Status: batch 013 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 013

Jakosc batcha 013 zostala zaakceptowana. Rekordy batchy 001-013 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 014

Utworzono czternasty batch:

- `video-description-batch-014.csv`
- `video-description-batch-014-proposals.json`
- `video-description-batch-014-review.md`
- `video-description-batch-014-audit.md`
- `batch-014-posters-contact-sheet.jpg`

Wynik batcha 014:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `160-182` znaki, srednio `168.7`.

Status: batch 014 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 014

Jakosc batcha 014 zostala zaakceptowana. Rekordy batchy 001-014 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 015

Utworzono pietnasty batch:

- `video-description-batch-015.csv`
- `video-description-batch-015-proposals.json`
- `video-description-batch-015-review.md`
- `video-description-batch-015-audit.md`
- `batch-015-posters-contact-sheet.jpg`

Wynik batcha 015:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `160-176` znakow, srednio `166.8`.

Status: batch 015 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 015

Jakosc batcha 015 zostala zaakceptowana. Rekordy batchy 001-015 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 016

Utworzono szesnasty batch:

- `video-description-batch-016.csv`
- `video-description-batch-016-proposals.json`
- `video-description-batch-016-review.md`
- `video-description-batch-016-audit.md`
- `batch-016-posters-contact-sheet.jpg`

Wynik batcha 016:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `188-223` znaki, srednio `204.4`.

Status: batch 016 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 016

Jakosc batcha 016 zostala zaakceptowana. Rekordy batchy 001-016 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 017

Utworzono siedemnasty batch:

- `video-description-batch-017.csv`
- `video-description-batch-017-proposals.json`
- `video-description-batch-017-review.md`
- `video-description-batch-017-audit.md`
- `batch-017-posters-contact-sheet.jpg`

Wynik batcha 017:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `181-218` znakow, srednio `201.4`.

Status: batch 017 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 017

Jakosc batcha 017 zostala zaakceptowana. Rekordy batchy 001-017 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 018

Utworzono osiemnasty batch:

- `video-description-batch-018.csv`
- `video-description-batch-018-proposals.json`
- `video-description-batch-018-review.md`
- `video-description-batch-018-audit.md`
- `batch-018-posters-contact-sheet.jpg`

Wynik batcha 018:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `183-222` znaki, srednio `201.7`.

Status: batch 018 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 018

Jakosc batcha 018 zostala zaakceptowana. Rekordy batchy 001-018 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 019

Utworzono dziewietnasty batch:

- `video-description-batch-019.csv`
- `video-description-batch-019-proposals.json`
- `video-description-batch-019-review.md`
- `video-description-batch-019-audit.md`
- `batch-019-posters-contact-sheet.jpg`

Wynik batcha 019:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `179-215` znakow, srednio `197.4`.

Status: batch 019 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 019

Jakosc batcha 019 zostala zaakceptowana. Rekordy batchy 001-019 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 020

Utworzono dwudziesty batch:

- `video-description-batch-020.csv`
- `video-description-batch-020-proposals.json`
- `video-description-batch-020-review.md`
- `video-description-batch-020-audit.md`
- `batch-020-posters-contact-sheet.jpg`

Wynik batcha 020:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `163-207` znakow, srednio `182.4`.

Status: batch 020 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 020

Jakosc batcha 020 zostala zaakceptowana. Rekordy batchy 001-020 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 021

Utworzono dwudziesty pierwszy batch:

- `video-description-batch-021.csv`
- `video-description-batch-021-proposals.json`
- `video-description-batch-021-review.md`
- `video-description-batch-021-audit.md`
- `batch-021-posters-contact-sheet.jpg`

Wynik batcha 021:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `165-203` znaki, srednio `186.0`.

Status: batch 021 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 021

Jakosc batcha 021 zostala zaakceptowana. Rekordy batchy 001-021 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 022

Utworzono dwudziesty drugi batch:

- `video-description-batch-022.csv`
- `video-description-batch-022-proposals.json`
- `video-description-batch-022-review.md`
- `video-description-batch-022-audit.md`
- `batch-022-posters-contact-sheet.jpg`

Wynik batcha 022:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `172-207` znakow, srednio `187.6`.

Status: batch 022 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 022

Jakosc batcha 022 zostala zaakceptowana. Rekordy batchy 001-022 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 023

Utworzono dwudziesty trzeci batch:

- `video-description-batch-023.csv`
- `video-description-batch-023-proposals.json`
- `video-description-batch-023-review.md`
- `video-description-batch-023-audit.md`
- `batch-023-posters-contact-sheet.jpg`

Wynik batcha 023:

- `50/50` opisow uzupelnionych jako `draft`.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `169-206` znakow, srednio `186.4`.

Status: batch 023 jest gotowy do review jakosciowego przez wlasciciela projektu.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Decyzja po review batcha 023

Jakosc batcha 023 zostala zaakceptowana. Rekordy batchy 001-023 oznaczono
statusem `accepted` w plikach roboczych.

## Postep redakcyjny - batch 024

Utworzono dwudziesty czwarty batch:

- `video-description-batch-024.csv`
- `video-description-batch-024-proposals.json`
- `video-description-batch-024-review.md`
- `video-description-batch-024-audit.md`
- `batch-024-posters-contact-sheet.jpg`

Wynik batcha 024:

- `50/50` opisow uzupelnionych i zaakceptowanych po zgodzie zbiorczej wlasciciela.
- `50/50` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `176-205` znakow, srednio `191.8`.

Status: batch 024 jest zaakceptowany redakcyjnie.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Postep redakcyjny - batch 025

Utworzono finalny, dwudziesty piaty batch:

- `video-description-batch-025.csv`
- `video-description-batch-025-proposals.json`
- `video-description-batch-025-review.md`
- `video-description-batch-025-audit.md`
- `batch-025-posters-contact-sheet.jpg`

Wynik batcha 025:

- `15/15` opisow uzupelnionych i zaakceptowanych po zgodzie zbiorczej wlasciciela.
- `15/15` opisow unikalnych.
- `0` duplikatow po normalizacji.
- `0` powtarzalnych poczatkow 5- i 7-wyrazowych.
- `0` starych fraz szablonowych.
- `0` opisow skopiowanych z promptu albo obecnego opisu produkcyjnego.
- `0` ryzykownych fraz odpowiedziowych w audycie.
- Dlugosc: `184-206` znakow, srednio `193.9`.

Status: batch 025 jest zaakceptowany redakcyjnie.
Nie jest jeszcze podpiety do sitemap ani do kodu aplikacji.

## Finalny status redakcji

Utworzono i zaakceptowano komplet opisow dla wszystkich rekordow w masterze:

- `1215/1215` rekordow ma status `accepted`.
- `0` rekordow ma status `todo`.
- `0` rekordow ma pusty proponowany opis.
- `0` opisow zaczyna sie od zakazanych form typu `Tak`, `Nie`, `Czy`, `Film do pytania`.
- `0` opisow zawiera stare frazy szablonowe.
- `0` opisow zawiera ryzykowne frazy odpowiedziowe z listy audytowej.
- `0` opisow jest kopia promptu albo obecnego opisu produkcyjnego.
- `0` duplikatow po normalizacji.
- Dlugosc calosci: `160-232` znaki, srednio `187.4`.

Finalny raport audytu znajduje sie w:

- `video-description-final-audit.md`

Nie traktujemy opisow roboczych jako jeszcze wdrozonych do produkcji.
Nastepny development powinien dodac redakcyjne pole opisu wideo i proces importu/QA
opisow, zanim zaczniemy podmieniac opisy w publicznych sitemapach.

## Implementacja techniczna opisow w sitemap video

Status: zrobione lokalnie w branchu `codex/video-description-editorial-workflow`.

Zakres implementacji:

- Dodano pole `question_media.seo_video_description`.
- Dodano importer CLI `seo:import-question-video-descriptions`.
- `QuestionVideoSeoDescriptionService` najpierw czyta opis redakcyjny z glownego medium wideo, a dopiero gdy go nie ma, wraca do starego generatora fallbackowego.
- `sitemaps/videos.xml` korzysta z redakcyjnego opisu tylko w tagu `<video:description>`.
- Nie zmieniono `questions.prompt`, `questions.explanation`, `option_a`, `option_b`, `option_c`, `correct_answer`.
- Nie zmieniono payloadu odtwarzacza pytan, timera egzaminu, adnotacji ani logiki `/nauka`.

Bezpiecznik architektoniczny:

- Tresci redakcyjne SEO sa przechowywane przy rekordzie medium (`question_media`), nie przy pytaniu (`questions`).
- Pole jest uzywane tylko przez publiczny generator video sitemap.
- Importer wypisuje wprost: `Pola pytania/odpowiedzi/explanation nie sa modyfikowane.`

Komendy wdrozeniowe:

```bash
php artisan migrate --force
php artisan seo:import-question-video-descriptions docs/video-description-editorial/video-description-editorial-master.csv --write --report=storage/app/video-description-import-write.json
php artisan seo:refresh-sitemaps
```

Jesli plik CSV nie jest dostepny w docelowym runtime, trzeba podac sciezke do skopiowanego pliku:

```bash
php artisan seo:import-question-video-descriptions storage/app/video-description-editorial-master.csv --write --report=storage/app/video-description-import-write.json
```

Weryfikacja lokalna wykonana 2026-06-01:

- `seo:import-question-video-descriptions` preview: `1215/1215` rekordow zaakceptowanych, `0` invalid, `0` unmatched.
- Import write: zaktualizowano `8144` rekordy `question_media` w lokalnej bazie.
- `seo:refresh-sitemaps` z `APP_URL=https://prawkonaraz.pl`: `Sitemap files generated and audit passed.`
- `sitemaps/videos.xml`: `1215` wpisow.
- Przyklad dla `/pytanie/99/...`: `<video:description>` zawiera opis redakcyjny sceny, a nie odpowiedz ani stary szablon.

Testy:

- `php artisan test --filter=QuestionVideoDescriptionImportCommandTest`
- `php artisan test --filter=SeoSitemapGenerationTest`
- `php artisan test --filter=QuestionMediaPayloadBuilderTest`
- `php artisan test --filter=StudySessionFlowTest`
- `php artisan test --filter=SessionPageTest`
- `php artisan test --filter=ApiSessionTest`

Wynik: wszystkie powyzsze testy przeszly. Szczegolnie wazne: test importu sprawdza,
ze po imporcie nie zmieniaja sie `prompt`, `explanation`, odpowiedzi ani `correct_answer`.

## Korekta sitemap video - tytuly i lastmod

Status: zrobione lokalnie w branchu `codex/video-description-editorial-workflow`.

Powod korekty:

- `video:title` byl ucinany przez `Str::limit(..., 110, '...')`, przez co czesc tytulow w `sitemaps/videos.xml` konczyla sie na `...`.
- W indeksie `sitemap.xml` wpis dla `sitemaps/static.xml` nie mial `lastmod`.

Decyzje:

- Usunieto sztuczne przycinanie `video:title`; tytul jest teraz pelny i nie konczy sie wymuszonym wielokropkiem.
- Dodano `lastmod` dla `static.xml` w indeksie sitemap.
- `lastmod` dla `static.xml` nie jest data wygenerowania pliku, tylko wynika z realnych zrodel: dat wpisow w `static.xml` oraz czasu modyfikacji plikow publicznej/static warstwy aplikacji.

Weryfikacja po `seo:refresh-sitemaps` z `APP_URL=https://prawkonaraz.pl`:

- `sitemaps/videos.xml`: `1215` wpisow.
- `video:title` konczacy sie na `...`: `0`.
- Najdluzszy `video:title`: `225` znakow.
- `sitemap.xml` ma `lastmod` dla `https://prawkonaraz.pl/sitemaps/static.xml`.
- `seo:refresh-sitemaps`: `Sitemap files generated and audit passed.`

Testy regresji:

- `SeoSitemapGenerationTest` sprawdza, ze `video:title` nie jest ucinany wielokropkiem.
- `SeoSitemapGenerationTest` sprawdza, ze wpis `static.xml` w indeksie ma `lastmod`.

## Korekta publicznych URL-i pytan pomocniczych

Status: zrobione lokalnie w branchu `codex/video-description-editorial-workflow`.

Problem:

- Czesc pytan importowanych ze zrodla technicznego miala `external_id` w formacie `pj360:2858`.
- Ten format przedostawal sie do publicznych URL-i w sitemapach, np. `/pytanie/pj360:2858/...`.
- Dwukropek i nazwa zrodla importu sa niepotrzebne w publicznym SEO URL-u.
- Nie mozna bylo jednak na slepo zamienic wszystkich takich identyfikatorow na sam numer, bo czesc numerow ma kolizje z normalnymi pytaniami, np. zwykle `3540` i pomocnicze `pj360:3540` oznaczaja rozne pytania.

Decyzje:

- Publiczne URL-e nigdy nie uzywaja juz `pj360` ani `pj360:`.
- Jesli techniczne `external_id` ma prefiks i nie koliduje z normalnym numerem, publiczny URL uzywa samego numeru, np. `/pytanie/2858/...`.
- Jesli sam numer koliduje z innym pytaniem, publiczny URL uzywa neutralnego aliasu, np. `/pytanie/pytanie-pomocnicze-3540/...`.
- Stare adresy techniczne, np. `/pytanie/pj360:3540/...`, sa obslugiwane tylko jako wejscie i przekierowuja 301 na kanoniczny neutralny adres.
- Nie zmieniono `questions.external_id` w bazie. To nadal techniczny identyfikator rekordu i zrodla importu.
- Nie zmieniono sciezek plikow mediow. Jesli media fizycznie leza pod `storage-bulk/imports/pj360/...`, to jest osobny temat aliasowania plikow i nie byl ruszany w tej korekcie, zeby nie dotykac odtwarzacza.

Weryfikacja po `seo:refresh-sitemaps` z `APP_URL=https://prawkonaraz.pl`:

- Wszystkie tagi `<loc>` w wygenerowanych sitemapach: `4703`.
- `<loc>` zawierajace `pj360`: `0`.
- `<loc>` zawierajace `/pytanie/pytanie-pomocnicze-`: `8`.
- W `sitemaps/videos.xml`: `1215` URL-i pytan, `0` z `pj360`, `3` z neutralnym aliasem `pytanie-pomocnicze-...`.

Testy regresji:

- `PublicQuestionDatabasePageTest` sprawdza redirect ze starego `pj360:3005` na czysty numer `3005`.
- `PublicQuestionDatabasePageTest` sprawdza kolizje `3540` vs `pj360:3540`: zwykly numer pokazuje zwykle pytanie, a pytanie pomocnicze dziala pod neutralnym aliasem.
- `SeoSitemapGenerationTest` sprawdza, ze sitemap generuje czyste URL-e bez publicznego prefiksu zrodla importu.
