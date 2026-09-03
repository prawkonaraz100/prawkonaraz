# PJ360 Tooling Runbook

## Cel

Ten dokument jest praktycznym runbookiem narzedzi do domykania zgodnosci:

- `PJ360 -> kategoria -> temat -> pytania`
- `local -> kategoria -> temat -> pytania`
- oraz bezpiecznego rolloutu zmian (`override`, `missing-local import`, `refresh`).

To jest dokument "jak to zrobic od zera", tak zeby kazda osoba w zespole mogla:

1. odtworzyc audit,
2. wygenerowac artefakty,
3. wdrozyc zmiany,
4. zweryfikowac wynik.

## Zakres

Runbook obejmuje pipeline dla tematow i pytan (A..T), a nie UI.

Glowne skrypty:

- `scripts/extract_pj360_exact_topic_membership.py`
- `scripts/export_local_effective_topic_membership.py`
- `scripts/compare_exact_and_local_topic_membership.py`
- `scripts/export_topic_retopic_candidate_shortlist.py`
- `scripts/build_pj360_retopic_override_package.py`
- `scripts/export_pj360_retopic_conflict_workset.py`
- `scripts/build_pj360_delta_aware_conflict_package.py`
- `scripts/export_pj360_missing_local_inventory.py`
- `scripts/analyze_missing_local_recovery_candidates.py`
- `scripts/build_pj360_missing_local_import_batch.py`

Komendy wdrozeniowe (Laravel/DB):

- `content:sync-question-topic-overrides`
- `content:classify-question-topics`
- `catalog:import-json`

## Model danych pipeline

Pipeline pracuje na trzech warstwach:

1. `exact-membership` z PJ360 (stan referencyjny).
2. `local-effective-membership` (stan realnie uzywany przez `/nauka`, po active override).
3. `exact-vs-local diff` (co sie pokrywa, co jest w zlym temacie, czego brakuje, co jest lokalnie ponad target).

Najwazniejsza zasada:

- porownujemy z `local-effective`, nie z surowym `question_topic_id`.

## Wymagania

1. Lokalna baza i aplikacja dzialaja (PostgreSQL + app).
2. Python ma `psycopg` (dla skryptow DB).
3. Dla extractora PJ360 ustawione sa:
- `PJ360_EMAIL`
- `PJ360_PASSWORD`

## Katalogi i artefakty

Glowny katalog wynikowy:

- `output/analysis/pj360-exact-topic-membership/`

Najwazniejsze pliki:

- `exact-topic-membership-all.json`
- `local-effective-topic-membership-all.json`
- `exact-vs-local-topic-membership-diff.json`
- `<cat>-retopic-candidate-shortlist.json`
- `<cat>-retopic-true-conflicts.json`
- `<cat>-retopic-override-guard-conflicts.json`
- `<cat>-missing-local-inventory.json`
- `<cat>-missing-local-recovery-candidates.json`
- `<cat>-missing-local-import-batch-report.json`

Paczki override:

- `resources/topic-overrides/pj360-<cat>-retopic-package.json`
- `resources/topic-overrides/pj360-<cat>-delta-aware-conflict-package.json`
- `resources/topic-overrides/pj360-<cat>-missing-local-import-overrides.json`
- opcjonalne fale reczne, np. `pj360-c-rebalance-wave6.json`

Batch importu pytan:

- `storage/app/import-batches/pj360-<cat>-missing-local-import.json`

## Standardowy flow per kategoria

Przyklad dla `C` (zmien `C` na dowolna kategorie).

### Krok 1: Zrzut referencji z PJ360 (exact)

```powershell
python scripts/extract_pj360_exact_topic_membership.py --categories C --output output/analysis/pj360-exact-topic-membership/exact-topic-membership-all.json
```

Uwagi:

- skrypt loguje sie na PJ360 i czyta paginacje tematow z `/kurs/<topic>`
- potrzebuje `PJ360_EMAIL` i `PJ360_PASSWORD`

### Krok 2: Zrzut lokalnego effective membership

```powershell
python scripts/export_local_effective_topic_membership.py --categories C
```

### Krok 3: Diff exact vs local

```powershell
python scripts/compare_exact_and_local_topic_membership.py --categories C
```

### Krok 4: Retopic shortlist

```powershell
python scripts/export_topic_retopic_candidate_shortlist.py --categories C
```

### Krok 5: Build paczki retopic

```powershell
python scripts/build_pj360_retopic_override_package.py --category C
```

### Krok 6: Export konfliktow retopic

```powershell
python scripts/export_pj360_retopic_conflict_workset.py --category C
```

### Krok 7: (Opcjonalnie) delta-aware planner konfliktow

```powershell
python scripts/build_pj360_delta_aware_conflict_package.py --category C
```

### Krok 8: Missing-local inventory

```powershell
python scripts/export_pj360_missing_local_inventory.py --categories C
```

### Krok 9: Recovery analiza dla missing-local

```powershell
python scripts/analyze_missing_local_recovery_candidates.py --inventory-json output/analysis/pj360-exact-topic-membership/c-missing-local-inventory.json --output output/analysis/pj360-exact-topic-membership/c-missing-local-recovery-candidates.json
```

### Krok 10: Build batch importowy (gdy sa realne braki)

```powershell
python scripts/build_pj360_missing_local_import_batch.py --category C
```

Opcjonalnie bez pobierania mediow:

```powershell
python scripts/build_pj360_missing_local_import_batch.py --category C --skip-download
```

## Wdrozenie zmian do bazy

### Override sync

Dry-run:

```powershell
docker compose exec -T app php artisan content:sync-question-topic-overrides resources/topic-overrides/pj360-c-retopic-package.json --dry-run
```

Import:

```powershell
docker compose exec -T app php artisan content:sync-question-topic-overrides resources/topic-overrides/pj360-c-retopic-package.json
```

### Import brakujacych pytan

Dry-run:

```powershell
docker compose exec -T app php artisan catalog:import-json storage/app/import-batches/pj360-c-missing-local-import.json --dry-run
```

Import:

```powershell
docker compose exec -T app php artisan catalog:import-json storage/app/import-batches/pj360-c-missing-local-import.json
```

### Reclassify po sync/import

```powershell
docker compose exec -T app php artisan content:classify-question-topics --refresh --category=C
```

### Re-audit po wdrozeniu

Po kazdym wdrozeniu wracamy do:

1. `export_local_effective_topic_membership.py`
2. `compare_exact_and_local_topic_membership.py`
3. `export_topic_retopic_candidate_shortlist.py`
4. `export_pj360_missing_local_inventory.py`

## Jak czytac liczby

Dla porownania zgodnosci tematow bierzemy:

- mianowniki `x / TOTAL` na UI,
- `pj360_count` vs `local_count` w diffie.

`x` (postep usera) nie bierze udzialu w ocenie zgodnosci topic assignment.

## Definicje statusow missing-local

- `already_recoverable_same_category`: pytanie juz mamy lokalnie w tej samej kategorii (zwykle inny identyfikator).
- `recoverable_same_category_non_ready`: jest w tej samej kategorii, ale nie jest `active_ready`.
- `recoverable_from_other_category`: mamy pytanie w innej kategorii i da sie sklonowac.
- `recoverable_from_other_category_non_ready`: jak wyzej, ale lokalny kandydat nie jest gotowy.
- `truly_missing_local`: realny brak importowy.

## Zasady bezpieczenstwa

1. Zawsze `--dry-run` przed sync/import.
2. Kazda fala override w osobnym pliku.
3. Nie nadpisuj historii fal jednym "mega plikiem".
4. Po kazdej fali rob checkpoint commit.
5. Nie odwracaj aktywnych override bez jawnego powodu (unikamy oscylacji tematow).

## Gotowy checklist "closure per kategoria"

1. Exact extract z PJ360.
2. Local effective export.
3. Diff exact vs local.
4. Retopic shortlist + package + conflicts.
5. Missing-local inventory + recovery.
6. Import brakow (jesli potrzebny).
7. Sync override.
8. Reclassify.
9. Recompute diff.
10. Zapis statusu w dedykowanym dokumencie `PJ360-<CAT>-FULL-CLOSURE.md`.

## Szybki eksport listy pytan z jednego tematu

Przyklad: kat. `C`, temat `warning_signs`.

```powershell
$src='output/analysis/pj360-exact-topic-membership/local-effective-topic-membership-C.json'
$out='output/analysis/pj360-exact-topic-membership/c-warning-signs-questions.txt'
$j=Get-Content -Raw $src | ConvertFrom-Json
$topic=$j.topics | Where-Object {$_.topic_key -eq 'warning_signs'}
$lines=@()
$lines += 'Kategoria: C'
$lines += 'Temat: Znaki ostrzegawcze'
$lines += ('Liczba pytan: ' + $topic.questions_count)
$lines += ''
$i=1
foreach($q in $topic.questions){ $lines += ('{0}. [{1}] {2}' -f $i, $q.external_id, $q.prompt); $i++ }
$lines | Set-Content -Encoding UTF8 $out
```

## Troubleshooting

### `ModuleNotFoundError: psycopg`

- zainstaluj `psycopg[binary]` dla uzywanego interpretera Pythona
- albo uruchamiaj skrypty przez to samo srodowisko, ktore ma dostep do DB

### `retopic package exported_overrides = 0`

To nie musi byc blad. Zwykle znaczy:

- zostaly konflikty 1->N,
- albo blokuje `existing override conflict`.

Wtedy idziemy przez:

- `c-retopic-true-conflicts.json`
- ewentualnie reczne paczki typu `manual-conflict` / `rebalance wave`.

### `missing_local_total > 0`, ale temat ma dobre liczniki

To zwykle sygnaly duplikatow / alternatywnych identity.
Sprawdz:

- `*-missing-local-recovery-candidates.json`
- status `already_recoverable_same_category`.

## Powiazane dokumenty

- [PJ360-EXACT-TOPIC-MEMBERSHIP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-EXACT-TOPIC-MEMBERSHIP.md)
- [PJ360-EXACT-VS-LOCAL-MEMBERSHIP-DIFF.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-EXACT-VS-LOCAL-MEMBERSHIP-DIFF.md)
- [PJ360-B-FULL-CLOSURE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-B-FULL-CLOSURE.md)
- [PJ360-C-FULL-CLOSURE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-C-FULL-CLOSURE.md)
- [PJ360-OVERRIDE-PACKAGE-SPEC.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-OVERRIDE-PACKAGE-SPEC.md)
