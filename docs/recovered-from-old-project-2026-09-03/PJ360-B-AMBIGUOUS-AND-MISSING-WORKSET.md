# PJ360 B Ambiguous And Missing Workset

## Cel

Ten dokument opisuje nowy, praktyczny etap domykania `kat. B` po zakonczonym exact sync:

- osobno rozbrajamy `ambiguous_local_match`,
- osobno wyciagamy shortlistę realnych brakow importowych,
- nie mieszamy juz problemu `matching layer` z problemem `brak lokalnego pytania`.

## Status 2026-04-15

Etap `missing_local import` dla `B` jest juz wykonany.

Po batchu:

- [pj360-b-missing-local-import.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/storage/app/import-batches/pj360-b-missing-local-import.json)
- [pj360-b-missing-local-import-overrides.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-missing-local-import-overrides.json)

stan remaining backlogu zmienil sie z:

- `54 import gaps`

na:

- `3 missing_local`
- wszystkie `3` w statusie `already_recoverable_same_category`

Czyli ten dokument zostaje jako historia worksetu i referencja do tego, jak rozbijalismy `B`, ale nie opisuje juz aktywnego backlogu importowego.

## Dlaczego ten etap jest potrzebny

Po dwoch falach exact sync dla `B` builder exact override package zwraca:

- `exported_overrides = 0`

To oznacza, ze:

- wszystkie bezpieczne, jednoznaczne exact move sa juz wykorzystane,
- pozostale roznice do PJ360 nie wynikaja z dalszego automatycznego syncu,
- trzeba przejsc na review worksety:
  - `ambiguous`,
  - `import gaps`.

## Kanoniczne artefakty dla `B`

- [b-ambiguous-review.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-ambiguous-review.json)
- [b-import-gap-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-import-gap-shortlist.json)
- [b-retopic-candidate-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-retopic-candidate-shortlist.json)
- [b-retopic-true-conflicts.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-retopic-true-conflicts.json)
- [b-retopic-override-guard-conflicts.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-retopic-override-guard-conflicts.json)
- [pj360-b-retopic-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-retopic-package.json)

Oba pliki sa generowane przez:

- [export_pj360_category_resolution_workset.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/export_pj360_category_resolution_workset.py)

Shortlista `retopic` jest generowana przez:

- [export_topic_retopic_candidate_shortlist.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/export_topic_retopic_candidate_shortlist.py)

Workset konfliktow `retopic` jest generowany przez:

- [build_pj360_retopic_override_package.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/build_pj360_retopic_override_package.py)
- [export_pj360_retopic_conflict_workset.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/export_pj360_retopic_conflict_workset.py)
- [build_pj360_delta_aware_conflict_package.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/build_pj360_delta_aware_conflict_package.py)

## Aktualny stan `B`

### Stan historyczny przed batchem importowym

Po wygenerowaniu worksetu dla `B` mamy:

- `ambiguous_count = 70`
- `import_gap_count = 54`
- `blocking_import_gap_count = 44`
- `non_import_mismatch_count = 3`

Struktura `ambiguous` po pierwszym rozbiciu:

- `topic_aligned_duplicate = 32`
- `cross_topic_conflict = 32`
- `mixed_topic_conflict = 6`

To jest bardzo wazne, bo znaczy:

- `32` przypadki nie blokuja juz tematu i wymagaja glownie decyzji identyfikacyjnej,
- `38` przypadkow nadal realnie wplywa na domkniecie rozkladu tematow `B`.

Najwieksze tematy w `ambiguous`:

- `intersections_with_priority_signs = 13`
- `special_caution_exiting_and_securing_vehicle = 11`
- `behaviour_towards_pedestrians_and_reduced_mobility = 9`
- `joining_traffic_and_equal_intersections = 6`
- `vehicle_lights_and_signals = 6`

Najwieksze tematy w realnych konfliktach tematycznych:

- `behaviour_towards_pedestrians_and_reduced_mobility = 8`
- `joining_traffic_and_equal_intersections = 4`
- `intersections_with_priority_signs = 4`
- `intersections_with_traffic_lights = 4`
- `road_markings = 3`
- `traffic_lights_and_controller_signals = 3`

Najwieksze tematy w realnych `import gaps`:

- `road_markings = 6`
- `warning_signs = 5`
- `road_position_entry_exit_stopping = 4`
- `vehicle_lights_and_signals = 4`
- `speed_limits = 4`

Najwieksze tematy w `blocking import gaps`:

- `road_markings = 6`
- `warning_signs = 5`
- `vehicle_lights_and_signals = 4`
- `speed_limits = 4`
- `overtaking = 3`

Mismatche, ktorych nie nalezalo automatycznie wrzucac do importu:

- `media_kind_mismatch = 3`

### Stan po wykonanym batchu importowym

Po imporcie brakow `B`:

- `missing_local_total = 3`
- `recovery_status_counts`:
  - `already_recoverable_same_category = 3`

Czyli:

- `truly_missing_local = 0`
- `recoverable_from_other_category = 0`
- remaining cases nie wymagaja juz nowego importu.

## Co jest w `b-retopic-candidate-shortlist.json`

To jest trzecia, bardzo praktyczna warstwa po exact sync:

- pytania, ktore mamy lokalnie,
- PJ360 juz wskazuje ich docelowy temat,
- ale u nas nadal siedza pod innym `current_topic_key`.

Aktualnie dla `B` mamy:

- `retopic_candidate_count = 62`

Najwieksze docelowe tematy:

- `behaviour_towards_pedestrians_and_reduced_mobility = 17`
- `overtaking = 8`
- `controlled_crossings_and_public_transport_stops = 7`
- `traffic_lights_and_controller_signals = 6`
- `lane_change_and_turning = 6`

Najwieksze tematy zrodlowe, z ktorych te pytania trzeba zdejmowac:

- `special_caution_exiting_and_securing_vehicle = 24`
- `road_position_entry_exit_stopping = 18`
- `intersections_with_priority_signs = 8`
- `joining_traffic_and_equal_intersections = 4`
- `lane_change_and_turning = 4`

To jest najczystsza warstwa "mamy to pytanie, ale w zlym dziale".

Po dodaniu filtra na aktywne override shortlista nie pokazuje juz pytan, ktore zostaly juz bezpiecznie zsynchronizowane.

Najwieksze relacje odfiltrowane jako proba odwrocenia aktywnego override:

- `joining_traffic_and_equal_intersections -> intersections_with_priority_signs = 11`
- `intersections_with_priority_signs -> joining_traffic_and_equal_intersections = 5`
- `controlled_crossings_and_public_transport_stops -> behaviour_towards_pedestrians_and_reduced_mobility = 2`

## Co zostalo po pierwszej fali `retopic`

Pierwsza fala `retopic` dla `B` zostala juz zsynchronizowana i zmaterializowana do bazy.

Po dodaniu guardu przeciw odwrotnym ruchom kolejny build paczki daje:

- `exported_overrides = 0`
- `skipped_conflicts = 31`
- `skipped_existing_override_conflicts = 29`

To znaczy:

- nie ma juz kolejnych bezpiecznych, automatycznych ruchow `retopic`,
- zostaly tylko:
  - prawdziwe konflikty sygnatur PJ360,
  - proby odwrocenia juz aktywnego override.

Najwieksze kombinacje prawdziwych konfliktow:

- `behaviour_towards_pedestrians_and_reduced_mobility | overtaking = 8`
- `behaviour_towards_pedestrians_and_reduced_mobility | controlled_crossings_and_public_transport_stops = 7`
- `lane_change_and_turning | traffic_lights_and_controller_signals = 4`

Najwieksze zrodla konfliktow po obecnym `current_topic_key`:

- `special_caution_exiting_and_securing_vehicle = 12`
- `road_position_entry_exit_stopping = 9`
- `intersections_with_priority_signs = 4`

Najwieksze aktywne override, ktore guard chroni przed odwroceniem:

- `joining_traffic_and_equal_intersections = 11`
- `intersections_with_priority_signs = 5`
- `intersections_with_traffic_lights = 4`
- `warning_signs = 2`

## Stan po falach `delta-aware conflict resolution`

Po pierwszej fali `retopic` weszlismy juz w etap chirurgicznego rozstrzygania prawdziwych konfliktow membership PJ360.

Na tym etapie zostaly wykonane:

- jedna duza fala `delta-aware`,
- jedna mini-fala odzyskanego `retopic`,
- jedna finalna fala `delta-aware`,

czyli tylko ruchy, ktore:

- mialy jednoznaczny efekt zmniejszajacy delte,
- nie cofaly aktywnych override,
- nie wchodzily w remisy tematow.

Efekt koncowy automatyki dla `B`:

- [pj360-b-retopic-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-retopic-package.json)
  - `exported_overrides = 0`
  - `skipped_conflicts = 0`
- [pj360-b-delta-aware-conflict-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-delta-aware-conflict-package.json)
  - `exported_overrides = 0`
  - `manual_conflicts = 0`
  - `skipped_existing_override_same_target = 0`
- [pj360-b-manual-conflict-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-manual-conflict-package.json)
  - `4` jawnie rozstrzygniete konflikty final-pass dla `B`

Najwazniejsze liczniki `B` po tych falach:

- `traffic_lights_and_controller_signals: 93 / 93`
- `behaviour_towards_pedestrians_and_reduced_mobility: 107 / 105`
- `lane_change_and_turning: 147 / 148`
- `overtaking: 162 / 164`
- `controlled_crossings_and_public_transport_stops: 26 / 27`
- `intersections_with_traffic_lights: 59 / 59`
- `prohibition_and_mandatory_signs: 102 / 102`
- `special_caution_exiting_and_securing_vehicle: 54 / 61`

Czyli:

- automatyczne ruchy dla `B` sa juz wyczerpane,
- `retopic_candidate_count` zszedl do `0`,
- backlog prawdziwych konfliktow tematycznych tez zszedl do `0`,
- a glowny remaining gap to juz:
  - `missing_local`,
  - oraz kilka lokalnych przesuniec wynikajacych z tego, ze mamy globalnie `-50` pytan vs PJ360.

Aktualny stan worksetow dla `B`:

- `retopic_candidate_count = 0`
- `true_conflicts = 0`
- `delta-aware manual_conflicts = 0`
- `blocking import gaps = 46`

## Co jest w `b-ambiguous-review.json`

To jest review pack dla nierozstrzygnietych duplikatow w `B`.

Kazdy rekord zawiera:

- pytanie PJ360 i docelowy temat,
- liczbe kandydatow lokalnych,
- rozklad kandydatow po obecnych tematach,
- pelna liste lokalnych kandydatow:
  - `question_id`
  - `external_id`
  - `source`
  - `current_topic_key`
  - `question_media_kind`
  - `main_media_original`
  - prompt i odpowiedz

To jest plik roboczy do:

- bezpiecznego rozstrzygania duplikatow,
- decyzji, czy potrzebujemy dodatkowej reguly matching layer,
- albo recznego override dla konkretnego wzoru pytania.

## Co jest w `b-import-gap-shortlist.json`

To jest czysta lista przypadkow, ktorych nie nalezy juz traktowac jako problem klasyfikacji.

Plik rozdziela:

- `records`
  - realne `no_local_prompt_match`
  - czyli kandydatow do backlogu importowego
- `non_import_mismatches`
  - `answer_mismatch`
  - `media_kind_mismatch`
  - czyli przypadki, ktore wymagaja review, ale nie powinny od razu trafic do importu

To jest bardzo wazne rozroznienie:

- `no_local_prompt_match` = realny brak lokalnego odpowiednika
- `answer/media mismatch` = problem zgodnosci wariantu, a nie pewny brak

## Kolejnosc pracy dla `B`

1. Przejsc przez [b-ambiguous-review.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-ambiguous-review.json) tematami o najwyzszym zwrocie:
   - `intersections_with_priority_signs`
   - `special_caution_exiting_and_securing_vehicle`
   - `behaviour_towards_pedestrians_and_reduced_mobility`
   - `joining_traffic_and_equal_intersections`
   - `vehicle_lights_and_signals`
   Przy review najpierw bierzemy rekordy z bucketow:
   - `cross_topic_conflict`
   - `mixed_topic_conflict`
   a dopiero potem `topic_aligned_duplicate`.
2. Potem przejsc przez [b-import-gap-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-import-gap-shortlist.json) i zbudowac shortlistę pytan do realnego importu.
   Najpierw bierzemy:
   - `blocking_records`
   a dopiero potem pozostale `records`, ktore nie poprawia juz samej delty tematu.
3. Dopiero po rozdzieleniu tych dwoch warstw robic kolejne decyzje:
   - matching rules,
   - import,
   - albo residual local-only review.

## Wniosek operacyjny

Na tym etapie `kat. B` jest juz poza faza masowego exact sync.

Od teraz pracujemy na dwoch osobnych backlogach:

- `B ambiguous review`
- `B import gap shortlist`

Rownolegle mamy juz trzeci backlog wykonawczy:

- `B retopic candidates`

To jest najbezpieczniejsza i najszybsza droga do dalszego domykania zgodnosci z PJ360 bez rozmywania problemu.

## Status po domknieciu `retopic / conflicts`

Ten dokument dalej zostaje jako historia etapu przejsciowego, ale aktualny stan `B` jest juz bardziej zaawansowany:

- `retopic_candidate_count = 0`
- `true_conflicts = 0`
- `manual_conflicts = 0`

To oznacza, ze backlog `B` nie siedzi juz w warstwie tematow, tylko w warstwie brakujacych lokalnie pytan.

Kanoniczny punkt wejscia do dalszej pracy:

- [PJ360-B-MISSING-LOCAL-INVENTORY.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-B-MISSING-LOCAL-INVENTORY.md)
- [b-missing-local-inventory.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-inventory.json)
