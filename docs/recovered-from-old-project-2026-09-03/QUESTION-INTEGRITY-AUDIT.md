# Question Integrity Audit

## 1. Cel dokumentu

Ten dokument opisuje kanoniczna bramke jakosci dla oficjalnej bazy pytan po kazdym nowym wsadzie `gov.pl`.

Jej cel to:

- wykryc pytania dodane, usuniete i zmienione miedzy kolejnymi pobraniami,
- odroznic zwykle zmiany redakcyjne od zmian krytycznych,
- zatrzymac publikacje wsadu, jesli zmienia sie tresc odpowiedzi albo medium rozstrzygajace pytanie,
- dac zespolowi prosty raport `co sie zmienilo` zamiast recznego porownywania calej bazy.

## 2. Dlaczego to jest potrzebne

Sam czysty reimport z `gov.pl` nie daje gwarancji, ze pytania sa nadal aktualne i poprawne.

Ryzyka sa realne:

- zmiana przepisow moze nie trafic od razu do bazy,
- pytanie moze zostac poprawione albo wycofane bez zmiany calego procesu importowego,
- odpowiedz poprawna moze sie zmienic przy tym samym numerze pytania,
- glowne medium moze zostac podmienione i zmienic sens pytania.

Dlatego po kazdym pelnym reimportcie potrzebujemy osobnego kroku:

`import -> klasyfikacja -> audit integralnosci -> decyzja redakcyjna`

## 3. Co porownujemy

Audyt buduje snapshot oficjalnego katalogu z aktualnej bazy i porownuje go z poprzednim snapshotem.

Klucz identyfikacji pytania:

- `category_code`
- `government_question_id`

Sledzone pola:

- `prompt`
- `explanation`
- `option_a`
- `option_b`
- `option_c`
- `correct_answer`
- `question_type`
- `points`
- `difficulty`
- `is_active`
- `topic_key`
- `structure_scope`
- `categories_original`
- `main_media_original`
- `pjm_question`

## 4. Zmiany krytyczne

Za krytyczne uznajemy zmiany w polach:

- `option_a`
- `option_b`
- `option_c`
- `correct_answer`
- `question_type`
- `main_media_original`

Te pola zmieniaja znaczenie pytania albo sposob, w jaki kursant powinien je rozwiazac.

Jesli audyt pokazuje zmiany krytyczne, wsad nie powinien byc traktowany jako gotowy do publikacji bez recznego review.

## 5. Gdzie leza raporty

Snapshoty i raporty zapisywane sa w:

- `storage/app/question-integrity/gov_full_catalog/snapshots/`
- `storage/app/question-integrity/gov_full_catalog/reports/`

Najwazniejsze pliki robocze:

- `latest-snapshot.jsonl`
- `latest-snapshot-meta.json`
- `latest-report.json`

Kazdy przebieg zapisuje:

- pelny snapshot `.jsonl`
- meta snapshotu `.json`
- raport `.json`
- log zmian `.jsonl`

## 6. Jak uruchamiamy audyt

### Recznie

```bash
php artisan content:audit-question-integrity
```

Opcjonalnie mozna nadac inny kanal lub zapisac kopie raportu:

```bash
php artisan content:audit-question-integrity --channel=gov_full_catalog --report=storage/app/import-reports/latest-integrity-report.json
```

### Automatycznie

Pelny, zakonczony sukcesem import serii manifestow:

```bash
php artisan catalog:import-manifest-series <path>
```

uruchamia audyt integralnosci automatycznie, jesli:

- to nie jest `--dry-run`,
- seria nie ma bledow,
- nie ograniczylismy importu przez `--from-chunk` lub `--to-chunk`.

Podsumowanie audytu trafia wtedy do:

- `content_import_runs.summary.integrity_audit`

## 7. Jak czytac wynik

Najwazniejsze liczniki w raporcie:

- `current_total`
- `previous_total`
- `added_total`
- `removed_total`
- `changed_total`
- `critical_total`
- `field_changes`

Interpretacja:

- `added_total > 0`
  nowe pytania pojawily sie w oficjalnym katalogu
- `removed_total > 0`
  pytania zniknely z nowszej wersji katalogu
- `changed_total > 0`
  tresc lub metadane istniejacych pytan zmienily sie
- `critical_total > 0`
  zmienily sie pola, ktore moga uniewaznic stary stan pytania

## 8. Decyzje po audycie

### Brak zmian

Jesli wynik to:

- `added=0`
- `removed=0`
- `changed=0`
- `critical=0`

to wsad mozna traktowac jako stabilny wzgledem poprzedniego snapshotu.

### Zmiany zwykle

Jesli pojawiaja sie tylko zmiany review:

- sprawdzamy `field_changes`,
- przegladamy sample w raporcie,
- podejmujemy decyzje, czy zmiana jest redakcyjna, czy wymaga dodatkowego ruchu.

### Zmiany krytyczne

Jesli `critical_total > 0`, robimy reczny pass:

1. otwieramy `changes.jsonl`,
2. filtrujemy rekordy `severity=critical`,
3. sprawdzamy pytania na tle aktualnej podstawy prawnej i oficjalnego wsadu,
4. dopiero potem zatwierdzamy nowy snapshot jako roboczo bezpieczny.

## 9. Minimalny workflow operatorski

Po nowym pobraniu z `gov.pl`:

1. przygotowujemy staging batch lub serie,
2. uruchamiamy `catalog:import-manifest-series --dry-run`,
3. uruchamiamy pelny `catalog:import-manifest-series`,
4. sprawdzamy wynik `integrity_audit`,
5. jesli sa zmiany krytyczne, blokujemy dalszy rollout i robimy review,
6. jesli zmian krytycznych brak, przechodzimy do `content:audit-delivery-readiness`,
7. dopiero potem robimy QA playera i decyzje redakcyjne.

## 10. Granice tego mechanizmu

Audyt integralnosci:

- nie rozstrzyga sam, czy pytanie jest zgodne z prawem,
- nie zastapi recznego review dla zmian krytycznych,
- nie porownuje nas z serwisami zewnetrznymi,
- nie generuje automatycznie nowych wyjasnien.

On odpowiada tylko na pytanie:

`Co zmienilo sie wzgledem poprzedniego oficjalnego snapshotu i czy zmiana dotyczy pol krytycznych?`

## 11. Status

Mechanizm jest wdrozony i zweryfikowany lokalnie na dwoch kolejnych przebiegach:

- baseline,
- drugi pass bez zmian (`0/0/0/0`),
- test automatyczny dla baseline + diff krytycznego,
- test automatyczny dla podpiecia audytu pod `catalog:import-manifest-series`,
- prosty widok adminowy w Filamencie:
  - dashboardowy status `Zielono / Zolto / Czerwono`,
  - badge `Audit bazy` na liscie i widoku `Import runs`.
