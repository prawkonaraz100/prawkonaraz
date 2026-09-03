# Publiczne wyjasnienia pytan - runbook importu

## 1. Cel

Ten dokument opisuje powtarzalny sposob importu redakcyjnych tresci pytan z katalogu markdown, np.:

`D:/pytania-main/pytania-main/pytania/`

Zakres tego runbooka to:

- `Wyjasnienie`,
- `Haczyk egzaminacyjny`,
- `Najczestsze bledy`,
- `Nie pomyl z`,
- `Powiazane pytania`,
- `Podstawa prawna`,
- uzupelnianie brakujacych przepisow prawa, jezeli wystepuja w importowanych plikach, ale nie ma ich jeszcze w bazie.

To nie jest runbook importu oficjalnej bazy pytan gov.pl. Nie zmieniamy tutaj tresci pytania, odpowiedzi, mediow ani danych egzaminacyjnych. Ten proces uzupelnia publiczna warstwe nauki i SEO na stronach pytan.

## 2. Zrodlo danych

Wejsciem sa pliki `.md` z katalogu `pytania`. Kazdy plik odpowiada jednemu pytaniu i ma numer/id pytania.

Nie wolno slepo polegac na `index.jsonl`, jezeli jego liczba rekordow rozni sie od liczby plikow `.md`. W przebiegu z 2026-06-29 katalog mial `144` pliki markdown, a `index.jsonl` tylko `88` rekordow, wiec poprawnym zrodlem byly pliki `.md`.

Typowe pola front matter:

- `id`,
- `numer`,
- `slug`,
- `pytanie`,
- `typ`,
- `odpowiedz`,
- `punkty`,
- `zrodlo`,
- `aktualizacja_pytania`,
- `kategoria`,
- `klaster`,
- `tematy`,
- `znaki`,
- `nie_pomyl_z`,
- `powiazane_pytania`,
- `powiazania`,
- `podstawa_prawna`,
- `status`.

Typowe sekcje markdown:

- `## Odpowiedz`,
- `**Wyjasnienie:**`,
- `## Podstawa prawna`,
- `## Nie pomyl z`,
- `## Powiazane pytania`,
- `**Haczyk egzaminacyjny:**`,
- `**Trzy najczestsze bledy:**`.

W praktyce pliki moga zawierac polskie znaki w naglowkach, np. `Odpowiedź`, `Wyjaśnienie`, `Powiązane pytania`. Parser powinien obslugiwac oba warianty: z polskimi znakami i bez nich.

## 3. Docelowy model danych

Publiczne wyjasnienia i redakcyjne rozszerzenia zapisujemy w osobnej warstwie publicznej, a nie w `questions.explanation`.

Główne miejsca zapisu:

- publiczna warstwa wyjasnienia pytania: `question_public_explanations`,
- sekcja `Nie pomyl z`: pole/kolumna `dont_confuse_with` w publicznej warstwie wyjasnienia,
- powiazania pytan: relacje dla publicznych wyjasnien/pytan powiazanych,
- podstawa prawna pytania: relacje `question_legal_references`,
- akty prawne i jednostki: `legal_acts`, `legal_units`.

Najwazniejsza zasada: import redakcyjnych wyjasnien nie moze modyfikowac oficjalnego pytania, oficjalnej odpowiedzi, mediow ani promptu w `questions`.

## 4. Zasady bezpieczenstwa

Przed importem trzeba wykonac preview i sprawdzic raport. Import zapisujacy produkcje jest dozwolony dopiero wtedy, gdy preview jest czyste albo wszystkie odchylenia zostaly recznie przejrzane.

No-go rules:

- Nie importuj z samego `index.jsonl`, jezeli nie pokrywa pelnej liczby plikow markdown.
- Nie aktualizuj `questions.explanation` w tym procesie.
- Nie zmieniaj `questions.prompt`, `questions.answer`, mediow ani statusu pytania.
- Nie zapisuj importu, jezeli prompt lub odpowiedz nie pasuje do bazy, dopoki roznica nie zostanie recznie porownana.
- Nie przepuszczaj pytania, jezeli nie ma `body`, `exam_trap` albo dokladnie trzech najczestszych bledow.
- Nie nadpisuj powiazan prawnych, ktore sa juz zlinkowane z artykulem w serwisie.
- Nie tworz jednostki prawnej pod zlym aktem albo zlym `slug`.
- Nie wstawiaj notatki redakcyjnej jako `official_excerpt`, jezeli nie pochodzi ze zweryfikowanego oficjalnego zrodla.

## 5. Budowa payloadow

Najpierw parser powinien przejsc po wszystkich plikach `.md` i wygenerowac oddzielne payloady:

1. Pelne publiczne wyjasnienia.
2. Sekcja `Nie pomyl z`.
3. `Powiazane pytania`.
4. `Podstawa prawna`.
5. Raport kontrolny.

Zalecana konwencja nazw:

```text
output/public-explanations-update-YYYYMMDDHHMMSS.json
output/public-explanations-dont-confuse-update-YYYYMMDDHHMMSS.json
output/public-explanations-related-update-YYYYMMDDHHMMSS.json
output/legal-basis-update-YYYYMMDDHHMMSS.json
output/question-update-payload-report-YYYYMMDDHHMMSS.json
```

Pelne wyjasnienie powinno zawierac co najmniej:

- numer/id pytania,
- slug z pliku,
- pytanie z pliku,
- odpowiedz z pliku,
- tresc publicznego wyjasnienia,
- haczyk egzaminacyjny,
- trzy najczestsze bledy,
- status publikacji,
- metadane pomocnicze do raportu.

Sekcja `Najczestsze bledy` wystepuje w plikach w wiecej niz jednym formacie. Parser powinien obsluzyc co najmniej:

```text
**Blad 1:** Tytul
Opis bledu w kolejnym akapicie.
```

oraz:

```text
**Blad 1:** Tytul i opis w jednej linii
```

Efektem zawsze maja byc trzy niepuste rekordy. Jezeli opis nie jest oddzielony od tytulu, lepiej uzyc tej samej tresci jako tytulu i opisu niz zapisac pusty rekord.

## 6. Walidacje przed zapisem

Importer publicznych wyjasnien powinien walidowac:

- czy pytanie istnieje w bazie,
- czy odpowiedz z pliku zgadza sie z odpowiedzia w bazie,
- czy prompt z pliku zgadza sie z promptem w bazie albo jest na recznej allowliscie przejrzanych roznic,
- czy payload ma `body`,
- czy payload ma `exam_trap`,
- czy payload ma dokladnie trzy `common_mistakes`,
- czy dla istniejacych rekordow wlaczono jawne `--overwrite-existing`.

Importer `Nie pomyl z` i `Powiazane pytania` powinien dodatkowo wymagac, aby publiczne wyjasnienie danego pytania juz istnialo i bylo opublikowane. Dlatego kolejnosc importu ma znaczenie.

Importer powiazanych pytan powinien walidowac:

- czy pytanie zrodlowe istnieje,
- czy pytanie docelowe istnieje,
- czy relacja nie wskazuje na samo siebie,
- czy duplikaty nie tworza wielokrotnych wpisow tej samej relacji.

Importer podstaw prawnych powinien walidowac:

- czy akt prawny istnieje,
- czy jednostka prawna istnieje,
- czy jednostka prawna jest zweryfikowana,
- czy relacja pytanie-przepis nie duplikuje istniejacej relacji.

## 7. Reczne roznice promptow

Jezeli prompt z pliku minimalnie rozni sie od promptu w bazie, nie wolno automatycznie ignorowac tej roznicy.

Poprawna procedura:

1. Porownaj prompt z pliku z promptem w bazie.
2. Sprawdz odpowiedz i sens pytania.
3. Jezeli roznica jest techniczna lub redakcyjna, np. spacja przed znakiem zapytania albo markup `[red]...[/red]`, dopisz id pytania do allowlisty przejrzanych wariantow.
4. Zapisz w raporcie albo notatce, dlaczego wariant zostal zaakceptowany.

Przyklad z przebiegu 2026-06-29:

- `1480` - roznica dotyczyla spacji przed `?`,
- `1578` - baza miala markup `[red]...[/red]` i spacje przed `?`, ale odpowiedz i sens pytania byly zgodne.

Takie przypadki mozna importowac dopiero po recznym review.

## 8. Podstawy prawne

Regula biznesowa ustalona dla importu:

Jezeli pytanie ma juz relacje prawna zlinkowana z artykulem w serwisie, nie dotykamy jej.

Technicznie oznacza to:

- rekordy z `legal_content_page_id` ustawionym na wartosc niepusta sa pomijane,
- istniejace niezlinkowane relacje mozna aktualizowac,
- brakujace relacje mozna tworzyc,
- import powinien raportowac osobno `skipped_linked_article`.

Przy imporcie podstaw prawnych aktualizujemy tylko reszte, czyli:

- tworzymy brakujace relacje pytanie-przepis,
- poprawiamy niezlinkowane relacje, jezeli import zawiera nowszy lub pelniejszy opis,
- nie naruszamy recznie opracowanych artykulow publicznych.

Notatka redakcyjna z importu powinna trafic do publicznego pola relacji, np. `question_legal_references.public_note`. Nie nalezy jej zapisywac jako oficjalnego cytatu przepisu.

## 9. Brakujace przepisy w bazie

Jezeli payload podstaw prawnych wskazuje jednostke, ktorej nie ma w `legal_units`, trzeba najpierw uzupelnic baze przepisow.

Procedura:

1. Z preview importu podstaw prawnych zbierz liste brakujacych jednostek.
2. Sprawdz, do ktorego aktu prawnego nalezy dana jednostka.
3. Upewnij sie, ze uzywasz poprawnego `legal_acts.slug` z produkcji.
4. Przygotuj manifest brakujacych jednostek.
5. Uruchom preview importu jednostek prawnych.
6. Dopiero po czystym preview uruchom zapis jednostek.
7. Ponownie uruchom preview podstaw prawnych.

Nie wolno tworzyc jednostek pod "podobnym" aktem prawnym. W przebiegu 2026-06-29 dla aktu `Dz.U. 2019 poz. 2310` poprawnym slugiem produkcyjnym byl `znaki-i-sygnaly-drogowe`.

Jezeli nie pobrano oficjalnego brzmienia przepisu z oficjalnego zrodla, `official_excerpt` powinno zostac puste/null. To jest lepsze niz zapisanie parafrazy jako cytatu.

## 10. Kolejnosc importu na produkcji

Bezpieczna kolejnosc:

1. Skanuj wszystkie pliki `.md` i wygeneruj payloady.
2. Sprawdz raport payloadow lokalnie.
3. Skopiuj payloady i skrypty importu na produkcje do `/tmp`.
4. Uruchom preview pelnych publicznych wyjasnien.
5. Jezeli sa roznice promptow, porownaj je recznie i dopiero wtedy dopisz id do allowlisty.
6. Uruchom preview `Nie pomyl z`.
7. Uruchom preview `Powiazane pytania`.
8. Uruchom preview podstaw prawnych.
9. Jezeli brakuje jednostek prawnych, zaimportuj najpierw jednostki prawne i powtorz preview podstaw prawnych.
10. Wykonaj backup przed zapisami.
11. Zapisz pelne publiczne wyjasnienia.
12. Powtorz preview `Nie pomyl z`, `Powiazane pytania` i podstaw prawnych.
13. Zapisz `Nie pomyl z`.
14. Zapisz `Powiazane pytania`.
15. Zapisz podstawy prawne.
16. Zweryfikuj baze i publiczne strony pytan.

Przyklady komend powinny byc dostosowane do aktualnego srodowiska produkcyjnego:

```bash
scp output/public-explanations-update-YYYYMMDDHHMMSS.json root@HOST:/tmp/
scp output/import-public-explanations-reviewed.php root@HOST:/tmp/

ssh root@HOST 'cd /var/www/prawkobit/current && php /tmp/import-public-explanations-reviewed.php --payload=/tmp/public-explanations-update-YYYYMMDDHHMMSS.json --preview'
ssh root@HOST 'cd /var/www/prawkobit/current && php /tmp/import-public-explanations-reviewed.php --payload=/tmp/public-explanations-update-YYYYMMDDHHMMSS.json --write --overwrite-existing --allow-reviewed-prompt-variants'
```

Nie zapisujemy w dokumentacji repo prywatnych sciezek do kluczy SSH ani sekretow.

## 11. Backup i rollback

Przed kazdym zapisem produkcyjnym trzeba miec backup danych, ktore importer moze zmienic.

Minimum:

- snapshot publicznych wyjasnien dla importowanych pytan,
- snapshot `dont_confuse_with`,
- snapshot powiazanych pytan,
- snapshot relacji prawnych dla importowanych pytan,
- lista nowych lub zmienionych `legal_units`.

W przebiegu 2026-06-29 dodatkowy reczny backup przed zapisami zostal zapisany w `/tmp/question-update-prewrite-backup-YYYYMMDDHHMMSS.json`, a importery utworzyly wlasne backupy sekcyjne w `/tmp`.

Rollback powinien byc wykonywany z backupu, nie przez "odwrotny import" przygotowany z pamieci.

## 12. Weryfikacja po imporcie

Po zapisie sprawdz co najmniej kilka pytan z roznych typow:

- pytanie z pelnym wyjasnieniem,
- pytanie z `Nie pomyl z`,
- pytanie z powiazaniami,
- pytanie z podstawa prawna,
- pytanie, ktore mialo recznie zaakceptowana roznice promptu,
- pytanie, ktore wymuszalo dodanie brakujacej jednostki prawnej.

Kontrola bazy:

- status publicznego wyjasnienia to `published`,
- `body` nie jest puste,
- `exam_trap` nie jest puste,
- sa trzy najczestsze bledy,
- `dont_confuse_with` nie jest puste tam, gdzie bylo w pliku,
- liczba powiazanych pytan zgadza sie z payloadem,
- relacje prawne nie zostaly zdublowane,
- rekordy zlinkowane z artykulami zostaly pominiete.

Kontrola publicznej strony:

- strona pytania zwraca HTTP 200,
- widac `Wyjasnienie`,
- widac `Nie pomyl z`, jezeli bylo w pliku,
- widac `Powiazane pytania`, jezeli byly w pliku,
- widac blok prawny jako `Uzasadnienie prawne`,
- miniaturki znakow renderuja sie tam, gdzie system zna znak.

## 13. Znaki drogowe w tresci

Import zapisuje tresc. Miniaturki znakow sa renderowane runtime przez warstwe komponentow znakow, np. serwis rozpoznajacy referencje typu `A-10`, `B-20`, `D-1`.

Brak rekordu znaku w bazie nie powinien blokowac importu wyjasnienia. Powinien jednak trafic do osobnego audytu brakujacych znakow, bo uzytkownik widzi wtedy gorsze wyjasnienie.

Przy imporcie warto zapisac albo wygenerowac liste kodow znakow znalezionych w tresci, z podzialem:

- znak istnieje w bazie,
- znak nie istnieje w bazie,
- kod wyglada jak znak, ale wymaga recznej weryfikacji.

## 14. Narzedzia robocze i kod z importow

W przebiegu z 2026-07-04 powstaly albo zostaly poprawione ponizsze narzedzia. Sa w `output/`, czyli katalogu ignorowanym przez git. Nie usuwaj ich przy sprzataniu repo, jezeli nie przenosisz ich najpierw do trwalego miejsca, np. `scripts/question-import/`. Nastepny agent powinien je wykorzystac zamiast pisac parser i importery od nowa.

### Generator payloadow

Plik:

```text
output/analysis/generate-question-update-payloads.py
```

Przyklad uruchomienia:

```powershell
$stamp = Get-Date -Format yyyyMMddHHmmss
python output\analysis\generate-question-update-payloads.py `
  --source-dir 'D:\pytania-main\pytania-main\pytania' `
  --output-dir output `
  --stamp $stamp `
  --previous-report output\question-update-payload-report-YYYYMMDDHHMMSS.json
```

Generator robi piec artefaktow:

```text
output/public-explanations-update-STAMP.json
output/public-explanations-dont-confuse-update-STAMP.json
output/public-explanations-related-update-STAMP.json
output/legal-basis-update-STAMP.json
output/question-update-payload-report-STAMP.json
```

Wazne cechy generatora:

- czyta front matter przez `PyYAML`, a gdy lokalny Python nie ma tej biblioteki, uzywa fallback parsera dla podzbioru YAML stosowanego w plikach importu; fallback ma obslugiwac skalary, listy, listy obiektow i zagniezdzone listy,
- obsluguje naglowki z polskimi znakami i bez nich oraz oba formaty naglowkow: `## Wyjasnienie` / `**Wyjasnienie:**`, `## Haczyk egzaminacyjny` / `**Haczyk egzaminacyjny:**`, `## Trzy najczestsze bledy` / `**Trzy najczestsze bledy:**`,
- nie opiera importu na `index.jsonl`,
- parsuje `Wyjasnienie`, `Haczyk egzaminacyjny`, `Trzy najczestsze bledy`, `Nie pomyl z`, `Powiazane pytania`, `Podstawa prawna`,
- normalizuje odpowiedzi jednokrotnego wyboru zapisane z opisem, np. `A — 60 km/h`, do samego wariantu `A`; opis odpowiedzi zostaje w tresci wyjasnienia, ale porownanie z baza dotyczy litery wariantu,
- obsluguje format bledow `**Blad 1:** Tytul` oraz `**Blad 1: Tytul**`,
- dla `Nie pomyl z` nie odrzuca sekcji tylko dlatego, ze w tresci wystepuje slowo `brak`; za pusta uznaje tylko realne warianty typu `Brak wskazanych...`,
- wyciaga powiazania z list markdown, a jezeli trzeba, z front matter `powiazania`,
- raportuje puste powiazania jako `related_empty_external_ids`,
- zbiera kody znakow drogowych do audytu `sign_codes_by_question`,
- zapisuje hashe SHA-256 payloadow.

Generator ma wbudowane reguly dla podstaw prawnych:

- notatka po myslniku w front matter, np. `§ 95 ust. 1 pkt 4 - opis`, trafia do `public_note`, a do `provision` trafia czysta etykieta jednostki,
- zlozone przepisy sa rozbijane na pojedyncze jednostki, np. `art. 22 ust. 1, 5 i 6`, `§ 21 ust. 1-4`, `art. 162 § 1-2`, `art. 162 § 1 i 2`, `pkt 1-2`,
- zlozone odwolania do ustepow z powtorzonym `ust.` sa rozbijane na realne jednostki, np. `§ 11 ust. 1 i ust. 2 pkt 8` -> `§ 11 ust. 1` oraz `§ 11 ust. 2 pkt 8`, a `§ 21 ust. 1-2 i ust. 4` -> `§ 21 ust. 1`, `§ 21 ust. 2`, `§ 21 ust. 4`,
- przepisy laczone fraza `w zwiazku z` albo `w zw. z` sa rozbijane na osobne realne jednostki, np. `§ 97 ust. 2 w zwiazku z § 95 ust. 1 pkt 3` -> `§ 97 ust. 2` oraz `§ 95 ust. 1 pkt 3`,
- pozycje niebedace jednostka prawna, np. `znak P-2`, `tabliczka T-21`, `zalacznik`, sa pomijane z payloadu prawnego i raportowane w `legal_non_provision_skipped`,
- aliasy URL sa mapowane na akty istniejace w bazie, zeby nie tworzyc duplikatow aktow:
  - `https://eli.gov.pl/eli/DU/2024/1251/ogl` -> `https://eli.gov.pl/eli/DU/1997/602/ogl`,
  - `https://eli.gov.pl/eli/DU/2003/262/ogl` -> `https://eli.gov.pl/eli/DU/2024/502/ogl`,
  - `https://eli.gov.pl/eli/DU/2025/1226/ogl` -> `https://eli.gov.pl/eli/DU/2011/151/ogl`,
  - `https://eli.gov.pl/eli/DU/2026/141/ogl` -> `https://eli.gov.pl/eli/DU/2006/1410/ogl`.

Po wygenerowaniu payloadow zawsze sprawdz:

- `missing_count` musi byc `0`,
- liczba `full_payload` musi odpowiadac liczbie plikow `.md`,
- `dont_confuse_payload` nie powinien przypadkowo spasc przez bledne filtrowanie,
- `legal_public_note_null_rows` powinien byc przejrzany, ale nie blokuje importu, jezeli dotyczy znanych brakow notatek,
- `legal_non_provision_skipped` powinien zawierac tylko oznaczenia znakow, tabliczek, zalacznikow albo inne pozycje niebedace jednostkami prawnymi.

### Importery publicznych sekcji

Istniejace importery:

```text
output/import-public-explanations-reviewed.php
output/import-public-explanations-dont-confuse.php
output/import-public-explanations-related-questions.php
```

Kolejnosc jest istotna:

1. pelne publiczne wyjasnienia,
2. preview `Nie pomyl z`,
3. preview `Powiazane pytania`,
4. zapis `Nie pomyl z`,
5. zapis `Powiazane pytania`.

Przyklady:

```bash
php /tmp/import-public-explanations-reviewed.php /tmp/public-explanations-update-STAMP.json --overwrite-existing --allow-reviewed-prompt-variants
php /tmp/import-public-explanations-reviewed.php /tmp/public-explanations-update-STAMP.json --write --overwrite-existing --allow-reviewed-prompt-variants

php /tmp/import-public-explanations-dont-confuse.php /tmp/public-explanations-dont-confuse-update-STAMP.json --allow-reviewed-prompt-variants
php /tmp/import-public-explanations-dont-confuse.php /tmp/public-explanations-dont-confuse-update-STAMP.json --write --allow-reviewed-prompt-variants

php /tmp/import-public-explanations-related-questions.php /tmp/public-explanations-related-update-STAMP.json --allow-reviewed-prompt-variants
php /tmp/import-public-explanations-related-questions.php /tmp/public-explanations-related-update-STAMP.json --write --allow-reviewed-prompt-variants
```

Importer powiazanych pytan waliduje, czy pytanie docelowe istnieje w `questions`, czy relacja nie wskazuje na samo siebie i czy nie ma duplikatow.

### Importer podstaw prawnych z weryfikatorem

Uzywaj poprawionej wersji:

```text
output/import-question-legal-basis-reviewed.php
```

Nie uzywaj starej wersji `output/import-question-legal-basis.php` do nowych importow, jezeli zalezy nam na podpisanej weryfikacji. Stara wersja wpisywala `verified_by = null`.

Poprawiona wersja:

- wymaga opublikowanego autora `jakub-wisniewski`,
- pobiera go przez `ContentAuthor::defaultLegalReferenceVerifier()`,
- zapisuje `verified_by` przy nowych i aktualizowanych niezlinkowanych relacjach,
- nadal pomija relacje z `legal_content_page_id`,
- raportuje `skipped_linked_article`,
- ma pelna liste brakow w polu `missing_units`, nie tylko probke `missing_units_sample`.

Przyklady:

```bash
php /tmp/import-question-legal-basis-reviewed.php /tmp/legal-basis-update-STAMP.json
php /tmp/import-question-legal-basis-reviewed.php /tmp/legal-basis-update-STAMP.json --write
```

Preview z kodem wyjscia `1` nie musi oznaczac awarii. Najczesciej oznacza `missing_units_count > 0`. Najpierw uzupelnij `legal_units`, potem powtorz preview.

### Manifesty brakujacych jednostek prawnych

W przebiegu 2026-07-08 powstal pomocniczy generator manifestow:

```text
output/analysis/generate-missing-legal-unit-manifests.py
```

Uzyj go po nieudanym preview `import-question-legal-basis-reviewed.php`, jezeli raport ma pole `missing_units`. Skrypt:

- czyta brakujace jednostki z raportu preview,
- grupuje je po aktach prawnych,
- buduje `canonical_path`, `label`, `slug`, `type` i rodzicow,
- dopisuje brakujace jednostki nadrzedne, jezeli sa potrzebne do hierarchii,
- korzysta z metadanych aktow znalezionych w istniejacych plikach `output/legal-units-question-update-*.json`,
- ma dodatkowe metadane dla aktow, ktore pojawily sie w importach bez lokalnego manifestu, m.in. `ustawa-o-kierujacych-pojazdami`, `panstwowe-ratownictwo-medyczne`, `rejestracja-i-oznaczanie-pojazdow` i `wychowanie-w-trzezwosci`.

Przyklad:

```powershell
python output\analysis\generate-missing-legal-unit-manifests.py `
  --preview output\legal-preview-STAMP.json `
  --output-dir output `
  --stamp STAMP
```

Jezeli generator zglosi brak metadanych aktu, najpierw sprawdz akt na produkcji przez `output/inspect-legal-acts-for-question-update.php` albo przygotuj manifest aktu recznie. Dla nowego aktu skroc `legal_acts.title` do limitu 255 znakow, zachowujac jednoznaczny tytul. Generator nie pobiera oficjalnych excerptow z ELI/ISAP; dla nowych jednostek wpisuje `official_excerpt: null`.

Do importu jednostek uzywamy istniejacej komendy aplikacji:

```bash
php artisan legal-basis:import-units /tmp/legal-units-question-update-STAMP-ACT.json --json --sample-limit=50
php artisan legal-basis:import-units /tmp/legal-units-question-update-STAMP-ACT.json --write --json --sample-limit=50
```

Manifest ma ksztalt:

```json
{
  "act": {
    "slug": "znaki-i-sygnaly-drogowe",
    "title": "...",
    "short_title": "...",
    "publisher": "Dziennik Ustaw",
    "source_url": "https://eli.gov.pl/eli/DU/2019/2310/ogl",
    "eli_url": "https://eli.gov.pl/eli/DU/2019/2310/ogl",
    "isap_url": null,
    "effective_from": null,
    "last_checked_at": "YYYY-MM-DD HH:MM:SS",
    "status": "verified"
  },
  "units": []
}
```

Reguly `canonical_path`:

- `art. 27` -> `27`, typ `article`,
- `art. 27 ust. 1` -> `27/1`, typ `section`, rodzic `27`,
- `art. 66 ust. 1 pkt 5` -> `66/1/5`, typ `point`, rodzic `66/1`,
- `art. 162 § 2` -> `162/2`, typ `section`, rodzic `162`,
- `art. 135 ust. 1 pkt 2 lit. a` -> `135/1/2/a`, typ `letter`, rodzic `135/1/2`,
- jednostki z indeksami literowymi lub gornymi, np. `art. 2 pkt 16a` i `art. 129 ust. 2 pkt 2¹`, musza sortowac sie stabilnie; nie porownuj w sortowaniu bezposrednio liczb i stringow,
- `§ 95` -> `95`, typ `paragraph`,
- `§ 95 ust. 1` -> `95/1`, typ `section`, rodzic `95`,
- `§ 95 ust. 1 pkt 4` -> `95/1/4`, typ `point`, rodzic `95/1`,
- `§ 27 pkt 9 lit. e` -> `27/9/e`, typ `letter`, rodzic `27/9`,
- `zdanie koncowe` mozna zapisac jako osobna jednostke typu `sentence`, np. `35/1/zdanie-koncowe`.

Jezeli rodzic nie istnieje w bazie, dodaj go do manifestu razem z dzieckiem. Jezeli nie masz oficjalnego tekstu z ELI/ISAP, ustaw `official_excerpt` na `null`.

Uwaga na limity kolumn: `legal_acts.title` ma limit 255 znakow. Dla dlugich tytulow aktow uzyj skroconego, jednoznacznego tytulu i zachowaj pelniejszy sens w `short_title` albo w notatce importu.

### Narzedzia pomocnicze

Skrypty utworzone w przebiegu 2026-07-04:

```text
output/inspect-legal-acts-for-question-update.php
output/backup-legal-units-for-question-update.php
output/backup-question-update-20260630164113.php
output/verify-question-update.php
```

`inspect-legal-acts-for-question-update.php` pobiera z produkcji metadane aktow po URL-ach. Uzyj go przed przygotowaniem manifestow, zeby nie zgadywac slugow:

```bash
php /tmp/inspect-legal-acts-for-question-update.php URL [URL...]
```

`backup-legal-units-for-question-update.php` robi snapshot aktow i jednostek, ktore moga zostac zmienione przez manifesty:

```bash
php /tmp/backup-legal-units-for-question-update.php /tmp/legal-units-question-update-prewrite-backup-STAMP.json /tmp/legal-units-question-update-STAMP-ACT.json [...]
```

`backup-question-update-20260630164113.php` robi snapshot publicznych wyjasnien i relacji prawnych dla payloadu pytan:

```bash
php /tmp/backup-question-update-20260630164113.php /tmp/public-explanations-update-STAMP.json /tmp/legal-basis-update-STAMP.json /tmp/question-update-prewrite-backup-STAMP.json
```

`verify-question-update.php` jest weryfikatorem po imporcie. Sprawdza:

- czy istnieja wszystkie publiczne wyjasnienia z payloadu,
- czy maja status `published`,
- czy `body`, `exam_trap` i trzy bledy sa obecne,
- czy `dont_confuse_with` nie jest puste dla payloadu `Nie pomyl z`,
- czy liczba powiazanych pytan zgadza sie z payloadem,
- czy wszystkie jednostki i relacje prawne istnieja,
- czy niezlinkowane relacje prawne maja `verified_by = jakub-wisniewski`.

Przyklad:

```bash
php /tmp/verify-question-update.php \
  /tmp/public-explanations-update-STAMP.json \
  /tmp/public-explanations-dont-confuse-update-STAMP.json \
  /tmp/public-explanations-related-update-STAMP.json \
  /tmp/legal-basis-update-STAMP.json
```

Wynik poprawny powinien miec:

- `public_issues_count = 0`,
- `dont_confuse_missing_count = 0`,
- `related_issues_count = 0`,
- `legal_missing_count = 0`,
- `legal_without_verifier_count = 0`.

### Typowe pulapki z 2026-07-04

- Nie tworz duplikatu Prawa o ruchu drogowym dla tekstu jednolitego `DU/2024/1251`; mapuj go na istniejacy akt `DU/1997/602`.
- Nie tworz duplikatu warunkow technicznych pojazdow dla starego URL-a `DU/2003/262`; mapuj go na istniejacy akt `DU/2024/502`.
- Nie tworz duplikatu ustawy o kierujacych pojazdami dla tekstu jednolitego `DU/2025/1226`; mapuj go na istniejacy akt `DU/2011/151`.
- Nie tworz duplikatu ustawy o Panstwowym Ratownictwie Medycznym dla tekstu jednolitego `DU/2026/141`; mapuj go na istniejacy akt `DU/2006/1410`.
- Nie zapisuj `znak A-...`, `znak P-...`, `tabliczka T-...` jako `legal_units`; to sa oznaczenia znakow, nie jednostki aktu.
- Jezeli prompt mismatch jest nowy, najpierw porownaj prompt i odpowiedz z produkcja, dopiero potem dopisz id do allowlisty.
- Jezeli publiczne wyjasnienia nie sa jeszcze zapisane, importery `Nie pomyl z` i `Powiazane pytania` moga zwrocic blad brakujacego rekordu publicznego. To problem kolejnosci, nie payloadu.

## 15. Checklist dla kolejnego agenta

Przed startem:

- sprawdz liczbe plikow `.md`,
- sprawdz, czy `index.jsonl` jest kompletny,
- potwierdz, ze parser czyta sekcje z polskimi znakami i bez nich,
- wygeneruj payloady z timestampem,
- przeczytaj raport payloadow.

Przed zapisem:

- wykonaj preview wszystkich importerow,
- recznie przejrzyj roznice promptow,
- uzupelnij brakujace jednostki prawne,
- wykonaj backup,
- upewnij sie, ze relacje zlinkowane z artykulami sa pomijane.

Po zapisie:

- sprawdz kilka pytan SQL-em,
- sprawdz kilka publicznych URL-i,
- zapisz raporty importu w `output/`,
- dopisz do odpowiedniej notatki, co zostalo zaimportowane i jakie byly wyjatki.

Najwazniejsza mysl: import ma byc nudny, odtwarzalny i raportowany. Jezeli agent musi zgadywac, trzeba najpierw dopisac brakujaca regule do tego runbooka.
