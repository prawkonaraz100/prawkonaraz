# PJ360 Unresolved And Local Gaps

## Cel

Ten dokument opisuje etap po zakonczonym `safe exact rollout`:

- exact-membership zostal juz wykorzystany do konca,
- kolejne bezpieczne paczki override nie sa juz generowane automatycznie,
- pozostale roznice do PJ360 trzeba rozbijac na:
  - `unresolved`,
  - `missing locally`,
  - `local-only`.

## Co oznaczaja te trzy klasy problemow

### `unresolved`

To pytania z PJ360, ktore znamy i mamy w extractorze exact-membership, ale matching do naszej bazy nie jest jeszcze bezpiecznie jednoznaczny.

Najczestsze powody:

- `ambiguous_local_match`
- `no_local_prompt_match`
- `media_kind_mismatch`

### `missing locally`

To przypadki, w ktorych PJ360 ma wiecej pytan dla danej sygnatury lub tematu niz my lokalnie.

To nie jest juz problem samej klasyfikacji.
To jest roznica katalogu:

- brak pytania w lokalnej bazie,
- albo brak odpowiedniego wariantu pytania w danej kategorii.

### `local-only`

To pytania, ktore mamy lokalnie w danej kategorii, ale nie znajduja pary w exact-membership PJ360.

Takie przypadki wymagaja osobnej decyzji:

- czy to sa poprawne lokalne rekordy spoza zakresu PJ360,
- czy duplikaty,
- czy stare warianty pytan,
- czy rekordy wymagajace recznego review.

## Nowy etap pracy

Po exact rollout dla `A..T` kolejnosc jest taka:

1. policzyc `unresolved` per kategoria i per temat,
2. policzyc `missing locally` per kategoria i per temat,
3. policzyc `local-only` per kategoria i per temat,
4. wyciagnac powtarzalne wzorce,
5. dopiero potem robic:
   - reczne review trudnych duplikatow,
   - import brakujacych pytan,
   - albo dodatkowe, swiadome override tam, gdzie exact matching nie wystarcza.

## Artefakty

Kanoniczne artefakty tego etapu:

- `output/analysis/pj360-exact-topic-membership/exact-topic-membership-all.json`
- `output/analysis/pj360-exact-topic-membership/post-sync-category-summary-wave-2.json`
- `output/analysis/pj360-exact-topic-membership/unresolved-local-gaps-summary.json`
- `output/analysis/pj360-exact-topic-membership/missing-locally-by-category-topic.json`
- `output/analysis/pj360-exact-topic-membership/ambiguous-local-match-by-category-topic.json`

## Stan po pierwszym przebiegu raportu

Raport `unresolved-local-gaps-summary.json` jest juz wygenerowany dla calego zakresu `A..T`.

Najwazniejsze globalne liczby:

- `ambiguous_local_match = 692`
- `no_local_prompt_match = 324`
- `answer_mismatch = 6`
- `media_kind_mismatch = 4`

To oznacza, ze glowne zrodlo pozostalych roznic nie siedzi juz w klasyfikacji tematow, tylko w:

- trudnych duplikatach lokalnych,
- brakujacych promptach lokalnie,
- i marginalnie w rozjazdach odpowiedzi / medium.

### Najwieksze globalne `missing locally` per temat

- `warning_signs = 46`
- `road_position_entry_exit_stopping = 35`
- `road_markings = 30`
- `informational_direction_and_supplementary_signs = 22`
- `intersections_with_priority_signs = 22`
- `vehicle_load_and_passenger_safety = 19`
- `speed_limits = 19`

### Najwieksze globalne `local-only` per temat

- `special_caution_exiting_and_securing_vehicle = 23`
- `vehicle_lights_and_signals = 20`
- `warning_signs = 11`
- `lane_change_and_turning = 11`
- `distances_and_braking = 8`

## Wzorce po kategoriach

### `B` i `B1`

Najwiekszy remaining gap po exact sync:

- `behaviour_towards_pedestrians_and_reduced_mobility`

Do tego stale wracaja:

- `road_markings`
- `intersections_with_traffic_lights`
- `warning_signs`
- `traffic_lights_and_controller_signals`

Profil problemu:

- duzo `ambiguous_local_match`
- duzo `no_local_prompt_match`
- prawie brak `local-only`

Czyli tu dominuje:

- trudny matching
- oraz realny brak lokalnych wariantow pytan

### `A`, `AM`, `A1`, `A2`, `T`

Najwiekszy gap:

- `special_caution_exiting_and_securing_vehicle`

Wspolny wzor:

- lekkie przeszacowanie `special_caution`
- lekkie niedoszacowanie:
  - `behaviour_towards_pedestrians_and_reduced_mobility`
  - `intersections_with_traffic_lights`
  - `traffic_lights_and_controller_signals`

### `C`, `C1`, `D`, `D1`

Najwiekszy gap:

- `intersections_with_priority_signs`

Wspolny wzor:

- lekkie przeszacowanie `intersections_with_priority_signs`
- lekkie niedoszacowanie `joining_traffic_and_equal_intersections`
- bardzo podobny rozklad do siebie nawzajem

To sugeruje, ze te kategorie mozna dalej domykac pakietowo, nie pojedynczo.

## Najrozsądniejsza kolejność dalszych prac

1. `ambiguous_local_match`
   Najwiekszy zwrot z inwestycji, bo to najwieksza klasa problemu.

2. `no_local_prompt_match`
   To od razu rozdzieli:
   - realny brak lokalnych pytan
   - od sytuacji, w ktorej prompt jest ten sam semantycznie, ale zapisany inaczej.

3. `special_caution` i `intersections_with_priority_signs`
   To sa teraz dwa glowne "resztkowe" bucket problemowe po exact sync.

## Operacyjny pivot dla `B`

Pierwsza kategoria, dla ktorej schodzimy juz do poziomu wykonywalnych worksetow review, to `B`.

Kanoniczne artefakty:

- [PJ360-B-AMBIGUOUS-AND-MISSING-WORKSET.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-B-AMBIGUOUS-AND-MISSING-WORKSET.md)
- [b-ambiguous-review.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-ambiguous-review.json)
- [b-import-gap-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-import-gap-shortlist.json)

To jest moment, w ktorym przestajemy patrzec tylko na liczniki per temat, a zaczynamy pracowac na:

- konkretnych nierozstrzygnietych duplikatach,
- konkretnej liscie brakow importowych,
- z rozdzieleniem `matching problem` od `catalog gap`.

## Wniosek operacyjny

Na tym etapie nie stroimy juz slepo klasyfikatora i nie robimy kolejnych hurtowych paczek override.

Od tej chwili domykamy zgodnosc z PJ360 przez:

- twarda diagnostyke roznic,
- kategoria po kategorii,
- temat po temacie,
- z oddzieleniem problemu `matching` od problemu `brakujacych danych`.
