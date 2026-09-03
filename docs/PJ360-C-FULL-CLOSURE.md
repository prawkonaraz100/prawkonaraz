# PJ360 C Full Closure

## Cel

Ten dokument prowadzi operacyjne domkniecie kategorii `C` do rozkladu tematow PJ360.

Po zamknieciu `kat. B` przechodzimy na ten sam model pracy:

- exact membership z PJ360,
- porownanie `exact vs local`,
- retopic tylko tam, gdzie to bezpieczne,
- osobny backlog `missing_local`.

## Status Startowy 2026-04-15

Kanoniczne artefakty startowe dla `C`:

- [c-exact-vs-local-topic-membership-diff.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-exact-vs-local-topic-membership-diff.json)
- [c-ambiguous-review.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-ambiguous-review.json)
- [c-import-gap-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-import-gap-shortlist.json)
- [c-missing-local-inventory.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-missing-local-inventory.json)

Liczby startowe:

- `target_total = 1496`
- `local_total = 1478`
- `total_delta = -18`
- `matched_same_topic_total = 1440`
- `missing_local_total = 24`
- `topics_with_missing_local = 15`
- `ambiguous_count = 61`
- `import_gap_count = 23`
- `blocking_import_gap_count = 17`

Najwieksze worksety dla `C`:

- `ambiguous`
  - `special_caution_exiting_and_securing_vehicle = 11`
  - `intersections_with_priority_signs = 9`
  - `behaviour_towards_pedestrians_and_reduced_mobility = 8`
  - `vehicle_lights_and_signals = 6`
- `blocking import gaps`
  - `warning_signs = 4`
  - `informational_direction_and_supplementary_signs = 2`
  - `road_markings = 2`
  - `vehicle_load_and_passenger_safety = 2`

## Strategia C

Kolejnosc domykania:

1. retopic safe candidates dla `C`,
2. unresolved conflicts (`ambiguous`) tylko tam, gdzie nie rozwalamy juz zamknietych tematow,
3. import backlog z `blocking_import_gap_count`,
4. refresh + finalny diff i decyzja, czy `C` jest zamknieta jak `B`.

## Wave 1 (wykonana)

Pierwszy przebieg retopic dla `C` zostal wykonany:

- shortlist:
  - [c-retopic-candidate-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-retopic-candidate-shortlist.json)
- package:
  - [pj360-c-retopic-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-c-retopic-package.json)

Wynik sync:

- `created = 31`
- `updated = 0`
- `deactivated = 0`
- po sync:
  - `content:report-question-topic-assignment-delta --category=C`
    - `changed_questions = 31`

Po ponownym eksporcie shortlisty:

- `retopic_candidate_count: 73 -> 42`
- kolejny build package daje:
  - `exported_overrides = 0`
  - `skipped_conflicts = 21`

Interpretacja:

- najczystsza fala retopic dla `C` jest juz wykorzystana,
- dalszy ruch dla `C` to teraz glownie:
  - unresolved konflikty tematyczne,
  - backlog `blocking import gaps`.

## Wave 2 (wykonana)

W tej fali domkniety zostal backlog `missing_local` dla `C`.

Wykonane kroki:

1. recovery analiza:
   - [c-missing-local-recovery-candidates.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-missing-local-recovery-candidates.json)
2. import batch:
   - [pj360-c-missing-local-import.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/storage/app/import-batches/pj360-c-missing-local-import.json)
   - [pj360-c-missing-local-import-overrides.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-c-missing-local-import-overrides.json)
   - [c-missing-local-import-batch-report.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-missing-local-import-batch-report.json)
3. sync override + refresh:
   - import pytan: `23 created`
   - sync override: `23 created`
4. media audit dla nowego wsadu:
   - [pj360-media-audit-c-write.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-media-audit/pj360-media-audit-c-write.json)

Wynik po fali 2:

- `missing_local_total = 1`
- remaining brak to `already_recoverable_same_category`
- realny backlog importowy `C` jest praktycznie zamkniety

## Wave 3 (wykonana)

Dla konfliktow true-conflict odpalony zostal planner `delta-aware`.

Artefakty:

- [c-retopic-true-conflicts.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-retopic-true-conflicts.json)
- [pj360-c-delta-aware-conflict-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-c-delta-aware-conflict-package.json)

Wynik:

- `exported_overrides = 12`
- `manual_conflicts = 9`
- sync override: `12 created`

## Wave 4 (wykonana)

W tej fali rozstrzygniete zostaly konflikty reczne z `manual_conflicts`.

Artefakt:

- [pj360-c-manual-conflict-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-c-manual-conflict-package.json)

Wynik:

- `5` recznych override zapisanych,
- po tej fali zostaly juz glownie konflikty z aktywnymi override po obu stronach.

## Wave 5 (wykonana)

Po fali recznej uruchomiony zostal jeszcze jeden przebieg `retopic`.

Wynik:

- `retopic_candidate_count: 18 -> 4`,
- build `pj360-c-retopic-package.json` dal `exported_overrides = 2`,
- sync tej paczki wykonany (`2 created`).

## Aktualny Stan C (po Wave 5)

Snapshot:

- [c-exact-vs-local-topic-membership-diff.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-exact-vs-local-topic-membership-diff.json)
- [c-retopic-candidate-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-retopic-candidate-shortlist.json)
- [c-import-gap-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-import-gap-shortlist.json)

Najwazniejsze liczby:

- `total_delta = +5`
- `missing_local_total = 1` (already recoverable)
- `retopic_candidate_count = 4`
- `retopic package exported_overrides = 0` (zostaly tylko twarde konflikty)
- `true_conflicts = 9`

Wniosek:

- automatyczny i bezpieczny etap domykania `C` jest praktycznie wyczerpany,
- realny import backlog dla `C` jest zamkniety (`missing_local_total = 1`, already recoverable),
- do finalnego zamkniecia `C` zostaje ostatnia, reczna warstwa konfliktow tematowych.

## Wave 6 (wykonana)

W tej fali wykonany zostal reczny rebalance aktywnych override pod katem realnych delt tematowych.

Artefakt:

- [pj360-c-rebalance-wave6.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-c-rebalance-wave6.json)

Wynik sync:

- `dry-run`: `records = 14`, `created = 0`, `updated = 14`
- `import`: `records = 14`, `created = 0`, `updated = 14`
- refresh klasyfikacji:
  - `processed = 1501`
  - `updated = 14`

Najwazniejsze efekty po refresh:

- `Skrzyzowania z pierwszenstwem`: `81 -> 90` (domkniete do targetu `90`)
- `Wlaczanie sie do ruchu, rownorzedne`: `72 -> 64` (domkniete do targetu `64`)
- `Szczegolna ostroznosc...`: `35 -> 38` (domkniete do targetu `38`)
- `Skrzyzowania/przejscia z kierujacym`: `25 -> 26` (domkniete do targetu `26`)
- `Zmiana pasa/kierunku`: `120 -> 121` (domkniete do targetu `121`)

## Aktualny Stan C (po Wave 6)

Snapshot:

- [c-exact-vs-local-topic-membership-diff.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-exact-vs-local-topic-membership-diff.json)
- [c-retopic-candidate-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-retopic-candidate-shortlist.json)
- [c-retopic-true-conflicts.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-retopic-true-conflicts.json)
- [c-missing-local-inventory.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-missing-local-inventory.json)
- [c-missing-local-recovery-candidates.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/c-missing-local-recovery-candidates.json)

Najwazniejsze liczby:

- `total_delta = +5`
- `available_other_topic_total = 17` (spadek z `27`)
- `missing_local_total = 1` (`already_recoverable_same_category`)
- `retopic_candidate_count = 4`
- `true_conflicts = 2`
- liczba tematow z `delta != 0`: `5`

Pozostale delty (po Wave 6):

- `overtaking = +3`
- `road_position_entry_exit_stopping = -1`
- `road_markings = +1`
- `vehicle_lights_and_signals = +1`
- `warning_signs = +1`

Wniosek:

- warstwa `C` jest operacyjnie domknieta na poziomie krytycznych rozjazdow PJ360,
- zostal jedynie maly residual wynikajacy z lokalnych nadmiarow i konfliktow 1->N na sygnaturach pytan,
- dalsze dociskanie `C` do absolutnego 1:1 wymaga decyzji produktowej (deaktywacja/pominięcie lokalnych nadmiarow albo specjalna obsluga duplikatow sygnatur).

## Zasady Bezpieczenstwa

- Nie cofamy aktywnych override, jesli nowy ruch tylko zamienia jeden konflikt na inny.
- Kazda fala to osobny package i osobny checkpoint.
- Po kazdej fali obowiazkowo:
  - `content:sync-question-topic-overrides`
  - `content:classify-question-topics --refresh --category=C`
  - aktualizacja `c-exact-vs-local-topic-membership-diff.json`
