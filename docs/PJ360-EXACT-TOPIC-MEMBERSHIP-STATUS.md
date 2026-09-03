# PJ360 Exact Topic Membership Status

## Stan

Jestesmy w przejsciu z modelu:

- `classifier + audited overrides`

do modelu:

- `PJ360 exact-membership + exact overrides`

Nie oznacza to wyrzucenia klasyfikatora.

Klasyfikator dalej zostaje:

- baseline dla wszystkich pytan,
- fallback dla kategorii i tematow, ktorych exact-membership jeszcze nie mamy.

Ale dla kategorii, ktore chcemy domknac `1:1` do PJ360, source-of-truth przesuwa sie na:

- exact liste pytan per temat z `/kurs`.

## Status 2026-04-15

Najwazniejszy checkpoint na dzis:

- `kat. B` ma juz zakonczony:
  - `exact sync`
  - `retopic / conflict closure`
  - `missing-local import batch`

Wynik dla `B` po imporcie brakow:

- `58` pytan dosypanych do bazy
- `58` override zapisanych
- `missing_local_total = 3`
- remaining `3` sa juz tylko `already_recoverable_same_category`

Czyli:

- exact-membership nie sluzy juz tylko do reclasyfikacji lokalnych pytan,
- exact-membership sluzy tez do realnego uzupelniania brakow danych lokalnych.

## Najblizszy krok

Dokonczony checkpoint dla `B`:

1. wyciagnelismy exact-membership dla `B` z paginowanych stron `/kurs/<slug>`,
2. extractor zwrocil:
   - `31/31` tematow,
   - `2187` pytan,
   - pelny zgodny rozklad z PJ360 dla kategorii `B`,
3. zbudowalismy pierwszy exact override package dla `B`,
4. package daje:
   - `membership_questions = 2187`
   - `local_questions = 2137`
   - `exported_overrides = 952`
   - `unresolved = 234`

Wniosek:

- exact-membership dla `B` jest juz operacyjnie gotowy,
- nie pracujemy juz na samych licznikach ani heurystykach,
- mamy kanoniczna liste `PJ360 B -> temat -> konkretne pytania`.

## Najblizszy krok wykonawczy

1. wykonac `dry-run` sync exact package dla `B`,
2. zsynchronizowac exact override do bazy,
3. przeliczyc `B` po override,
4. porownac stan `/nauka` z target matrix i policzyc pozostale:
   - realne braki danych,
   - unresolved matching,
   - lokalne pytania, ktorych PJ360 nie ma w exact-membership.

## Stan po realnym sync `B`

Wykonane:

1. `dry-run` sync:
   - `952` rekordy
   - `865` create
   - `87` update
2. realny sync:
   - `952` override zapisanych
3. `content:classify-question-topics --refresh --category=B`:
   - `przetworzono = 2137`
   - `zaktualizowano = 952`

Efekt:

- exact override dla `B` realnie wszedl do `question_topic_id`,
- po refreshu mamy juz stan bardzo bliski PJ360,
- najwieksze pozostale delty po exact sync to:
  - `special_caution_exiting_and_securing_vehicle: 72 / 61`
  - `behaviour_towards_pedestrians_and_reduced_mobility: 94 / 105`
  - `vehicle_load_and_passenger_safety: 79 / 88`
  - `traffic_lights_and_controller_signals: 84 / 93`
  - `warning_signs: 107 / 114`
  - `road_markings: 106 / 113`

Wniosek:

- exact-membership zamienil `B` z problemu heurystycznego w problem domkniecia ostatnich `unresolved` i realnych brakow danych,
- dalsza praca dla `B` powinna juz isc przez:
  - analize `unresolved = 234`,
  - analize lokalnych pytan `B`, ktorych PJ360 nie ma w exact-membership,
  - a nie przez kolejne szerokie fale heurystyczne.

## Stan po dopieciu grupowego exact-match

Po rozszerzeniu buildera o bezpieczne dopasowanie grupowe:

- `ambiguous_local_match` zostal czesciowo zdjety automatycznie,
- druga fala dala jeszcze `40` override,
- ponowny build exact package dla `B` daje juz:
  - `exported_overrides = 0`
  - `unresolved = 127`

To jest obecnie najlepszy mozliwy stan exact-membership dla `B` bez wchodzenia w reczne, ryzykowne decyzje o trudnych duplikatach.

## Nastepny ruch

1. zachowac `B` jako kanoniczny pilot exact-membership rollout,
2. uruchomic extractor exact-membership dla pozostalych kategorii `A..T`,
3. dla kazdej kategorii budowac exact override package po tym samym workflow,
4. osobno trzymac backlog trudnych przypadkow:
   - `ambiguous_local_match`
   - `no_local_prompt_match`

## Milestone: full extractor `A..T`

Extractor exact-membership zostal juz uruchomiony dla wszystkich kategorii porownawczych:

- `A: 31 tematow / 1451 pytan`
- `AM: 31 tematow / 1531 pytan`
- `A1: 31 tematow / 1434 pytan`
- `A2: 31 tematow / 1433 pytan`
- `B: 31 tematow / 2187 pytan`
- `B1: 31 tematow / 1472 pytan`
- `C: 31 tematow / 1496 pytan`
- `C1: 31 tematow / 1463 pytan`
- `D: 31 tematow / 1509 pytan`
- `D1: 31 tematow / 1496 pytan`
- `T: 31 tematow / 1326 pytan`

Wniosek:

- exact-membership nie jest juz eksperymentem dla `B`,
- mamy gotowy kanoniczny input PJ360 dla calego zakresu `A..T`,
- kolejny etap to juz produkcyjne budowanie i synchronizacja safe exact override package per kategoria.

## Milestone: safe exact rollout `A..T`

Wykonane:

1. zbudowanie exact override package dla wszystkich kategorii `A..T`,
2. pierwsza fala sync + refresh,
3. druga fala sync + refresh po dopieciu grupowego exact-match,
4. ponowny build package po drugiej fali.

Stan koncowy etapu:

- dla kazdej kategorii `A..T` builder zwraca teraz:
  - `exported_overrides = 0`
- czyli wyczerpalismy juz wszystkie bezpieczne przypisania, ktore wynikaja wprost z exact-membership PJ360 i obecnego matching layera

Artefakty po rolloutcie:

- pełny extractor:
  - [exact-topic-membership-all.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/exact-topic-membership-all.json)
- podsumowanie po drugiej fali:
  - [post-sync-category-summary-wave-2.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/post-sync-category-summary-wave-2.json)

Najwazniejszy wniosek:

- exact sync nie jest juz backlogiem do zrobienia,
- exact sync jest zakonczony,
- pozostale roznice do PJ360 wynikaja teraz z:
  - `unresolved`,
  - realnych brakow pytan lokalnie,
  - oraz duplikatow / trudniejszych przypadkow dopasowania.

## Przejscie do etapu `unresolved + local gaps`

Po zakonczonym safe exact rollout uruchomilismy nowy raport diagnostyczny:

- [unresolved-local-gaps-summary.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/unresolved-local-gaps-summary.json)

Najwazniejsze wnioski z tego etapu:

- globalnie dominuje `ambiguous_local_match = 692`
- potem `no_local_prompt_match = 324`
- czyli najwiekszy remaining problem nie jest juz w topic assignment,
- tylko w matching layerze i realnych brakach lokalnych

Od tego miejsca dalsze domykanie PJ360 powinno isc juz przez:

1. rozbrojenie `ambiguous_local_match`,
2. rozbrojenie `no_local_prompt_match`,
3. osobny backlog dla `local-only`.

## Milestone: local effective mirror

Mamy juz nie tylko exact-membership z PJ360, ale tez lokalny mirror w tym samym ksztalcie:

- [local-effective-topic-membership-all.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/local-effective-topic-membership-all.json)

To jest snapshot:

- `A..T`
- `active + ready`
- realne `question_topic_id`
- konkretne pytania per temat

Najwazniejszy efekt:

- po obu stronach mamy juz te same warstwy danych:
  - `kategoria`
  - `temat`
  - `ilosc pytan`
  - `jakie pytania`

Czyli od tego momentu mozemy porownywac PJ360 vs lokalnie nie tylko przez delty licznikow, ale tez przez pelne membership list.

## Milestone: direct membership diff

Mamy juz trzeci poziom artefaktu:

- bezposredni diff `PJ360 exact-membership vs local effective membership`

Kanoniczne artefakty:

- [exact-vs-local-topic-membership-diff.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/exact-vs-local-topic-membership-diff.json)
- [b-exact-vs-local-topic-membership-diff.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-exact-vs-local-topic-membership-diff.json)
- [PJ360-EXACT-VS-LOCAL-MEMBERSHIP-DIFF.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-EXACT-VS-LOCAL-MEMBERSHIP-DIFF.md)

To jest etap, w ktorym dla kazdego tematu widzimy juz osobno:

- `matched_same_topic`
- `available_other_topic`
- `missing_local`
- `local_only_in_topic`

Czyli wprost:

- co jest dobrze przypisane,
- co mamy, ale pod zlym tematem,
- czego nie mamy lokalnie,
- i co siedzi u nas ponad membership PJ360.

## Milestone: `B` missing-local import batch

Po direct diff i inventory brakow dla `B` zbudowalismy juz pelny batch importowy:

- [pj360-b-missing-local-import.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/storage/app/import-batches/pj360-b-missing-local-import.json)
- [pj360-b-missing-local-import-overrides.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-missing-local-import-overrides.json)
- [b-missing-local-import-batch-report.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-import-batch-report.json)

Batch objal:

- `53` przypadki `truly_missing_local`
- `5` przypadkow `recoverable_from_other_category`

Po realnym imporcie i refreshu `B`:

- `local_total = 2195`
- `target_total = 2187`
- `missing_local_total = 3`
- remaining `3` to juz tylko `already_recoverable_same_category`

Wniosek:

- etap importowego uzupelniania brakow `B` jest praktycznie zamkniety,
- kolejny realny ruch dla `B` to juz nie import, tylko:
  - czyszczenie `local-only`
  - albo przejscie do kolejnej kategorii.

## Milestone: pierwsza fala `retopic` dla `B`

Po direct diff dla `B` zbudowalismy pierwsza paczke `retopic`:

- `29` bezpiecznych override
- `27` create
- `2` update

Po realnym sync i materializacji tematow `B` najwazniejsze liczniki doszly do:

- `warning_signs: 111 / 114`
- `prohibition_and_mandatory_signs: 100 / 102`
- `intersections_with_traffic_lights: 57 / 59`
- `controlled_crossings_and_public_transport_stops: 26 / 27`
- `intersections_with_priority_signs: 122 / 128`
- `road_markings: 108 / 113`

Najwazniejszy sygnal statusowy po tej fali:

- kolejny build paczki `retopic` daje:
  - `exported_overrides = 0`
  - `skipped_conflicts = 31`
  - `skipped_existing_override_conflicts = 29`

Wniosek:

- najczystsza warstwa `retopic` dla `B` jest juz wykorzystana,
- kolejny etap nie jest juz automatycznym retopic rolloutem,
- tylko review:
  - `true conflicts`
  - `override guard conflicts`
  - oraz `missing locally`.

## Milestone: delta-aware closure dla `B`

Po pierwszej fali `retopic` dolozylismy jeszcze warstwe:

- [build_pj360_delta_aware_conflict_package.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/build_pj360_delta_aware_conflict_package.py)

Jej rola:

- wziac tylko prawdziwe konflikty,
- sprawdzic realna delte `B`,
- wykonac tylko takie ruchy, gdzie jest unikalny zwyciezca po `remaining deficit`,
- remisy zostawic jako backlog manualny.

Stan po wykonaniu wszystkich bezpiecznych fal dla `B`:

- zwykly `retopic`:
  - `exported_overrides = 0`
  - `skipped_conflicts = 0`
- `delta-aware conflict package`:
  - `exported_overrides = 0`
  - `manual_conflicts = 0`
  - `skipped_existing_override_same_target = 0`
- `manual final pass`:
  - `4` jawnie rozstrzygniete konflikty dla `B`

Najwazniejsze liczniki `B` po tym etapie:

- `traffic_lights_and_controller_signals: 93 / 93`
- `intersections_with_traffic_lights: 59 / 59`
- `prohibition_and_mandatory_signs: 102 / 102`
- `lane_change_and_turning: 147 / 148`
- `overtaking: 162 / 164`
- `controlled_crossings_and_public_transport_stops: 26 / 27`
- `warning_signs: 111 / 114`
- `special_caution_exiting_and_securing_vehicle: 54 / 61`

Najwazniejszy wniosek statusowy:

- automatyczne domykanie `B` dla warstwy `retopic / conflict resolution` jest juz zakonczone,
- `retopic_candidate_count` zszedl do `0`,
- oraz backlog `missing_local = 59`, ktory nie jest juz problemem klasyfikacji, tylko danych/matchingu.

## Milestone: exact missing-local inventory

Po domknieciu warstwy tematow dla `B` dolozylismy juz osobny exporter:

- [export_pj360_missing_local_inventory.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/export_pj360_missing_local_inventory.py)

Ktory bierze:

- exact-membership PJ360,
- local effective membership,

i zwraca juz nie tylko delty, ale pelna, jawna liste:

- `kategoria -> temat -> brakujace pytania`.

Kanoniczne artefakty:

- [missing-local-inventory-all.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/missing-local-inventory-all.json)
- [b-missing-local-inventory.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-inventory.json)
- [PJ360-B-MISSING-LOCAL-INVENTORY.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-B-MISSING-LOCAL-INVENTORY.md)

Aktualny checkpoint dla `B`:

- `local_total_questions = 2137`
- `target_total_questions = 2187`
- `total_delta = -50`
- `missing_local_total = 61`
- `topics_with_missing_local = 26`

Wniosek:

- `B` nie jest juz backlogiem topic assignment,
- `B` jest teraz backlogiem importowym / matchingowym,
- i od tego miejsca kolejne prace powinny isc przez `missing_local inventory`, a nie przez kolejne fale `retopic`.
