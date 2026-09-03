# PJ360 Exact Vs Local Membership Diff

## Cel

Ten dokument opisuje trzeci poziom porownania po wdrozeniu:

1. exact-membership z PJ360
2. local effective membership po naszej stronie
3. bezposredni diff `PJ360 vs local`

Czyli przechodzimy z pytania:

- `ile jest pytan w temacie?`

do pytania:

- `ktore konkretne pytania sa u nas w dobrym temacie?`
- `ktore sa lokalnie, ale w innym temacie?`
- `ktorych w ogole lokalnie nie ma?`

## Kanoniczne artefakty

- [exact-vs-local-topic-membership-diff.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/exact-vs-local-topic-membership-diff.json)
- [b-exact-vs-local-topic-membership-diff.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-exact-vs-local-topic-membership-diff.json)
- [b-retopic-candidate-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-retopic-candidate-shortlist.json)

Artefakty sa generowane przez:

- [compare_exact_and_local_topic_membership.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/compare_exact_and_local_topic_membership.py)
- [export_topic_retopic_candidate_shortlist.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/export_topic_retopic_candidate_shortlist.py)

## Jak czytac diff

Dla kazdego tematu dostajemy:

- `pj360_count`
- `local_count`
- `delta`
- `matched_same_topic_count`
- `available_other_topic_count`
- `missing_local_count`
- `local_only_in_topic_count`

### Znaczenie pol

`matched_same_topic_count`

- PJ360 ma pytanie w danym temacie
- my tez mamy je lokalnie
- i jest juz w tym samym temacie

`available_other_topic_count`

- PJ360 ma pytanie w danym temacie
- my mamy je lokalnie
- ale siedzi pod innym tematem

To jest najczystsza warstwa "zlego bucketu" po naszej stronie.

`missing_local_count`

- PJ360 ma pytanie w danym temacie
- a my nie mamy sygnatury tego pytania nigdzie lokalnie

To jest sygnal backlogu importowego albo realnej roznicy katalogu.

`local_only_in_topic_count`

- my mamy w danym temacie wiecej lokalnych pytan o tej sygnaturze niz PJ360 w tym samym temacie

To nie musi oznaczac bledu importu.
To moze byc:

- lokalny wariant,
- duplikat,
- pytanie potrzebne przez inny temat PJ360,
- albo residual katalogowy po naszej stronie.

## Aktualny stan dla `B`

Po przeliczeniu `B`:

- `total_delta = -50`
- `matched_same_topic_total = 2089`
- `available_other_topic_total = 39`
- `missing_local_total = 59`
- `local_only_in_topic_total = 48`

To jest bardzo wazny checkpoint:

- `39` slotow PJ360 mamy juz lokalnie, ale pod innym tematem
- `59` slotow PJ360 nie znajduje lokalnego matchu nigdzie

To nie jest proste rownanie do `-50`, bo:

- liczymy tu membership slots per temat,
- a nie tylko globalna liczbe unikalnych lokalnych pytan

Ale operacyjnie ten diff rozdziela juz bardzo dobrze:

- problem topic drift
- od problemu realnego braku lokalnych pytan

## Najwazniejsze sygnaly dla `B`

Najwiekszy topic drift:

- `behaviour_towards_pedestrians_and_reduced_mobility`
- `intersections_with_traffic_lights`
- `traffic_lights_and_controller_signals`
- `road_markings`

Najwieksze realne luki lokalne:

- `road_markings`
- `warning_signs`
- `speed_limits`
- `vehicle_lights_and_signals`

Najwieksze dodatnie delty, ktore warto czytac razem z diffem:

- `road_position_entry_exit_stopping`
- `special_caution_exiting_and_securing_vehicle`

## Shortlista `retopic` dla `B`

Na bazie samego diffu da sie juz zbudowac czysta liste pytan, ktore:

- mamy lokalnie,
- PJ360 juz przypina do konkretnego tematu,
- ale u nas zyja jeszcze pod innym topic key.

Aktualny wynik dla `B`:

- `retopic_candidate_count = 62`

Najwieksze targety:

- `behaviour_towards_pedestrians_and_reduced_mobility = 17`
- `overtaking = 8`
- `controlled_crossings_and_public_transport_stops = 7`
- `traffic_lights_and_controller_signals = 6`
- `lane_change_and_turning = 6`

Najwieksze tematy zrodlowe:

- `special_caution_exiting_and_securing_vehicle = 24`
- `road_position_entry_exit_stopping = 18`
- `intersections_with_priority_signs = 8`
- `joining_traffic_and_equal_intersections = 4`

To jest juz bezposredni material do kolejnej paczki override / retopic, bez zgadywania na poziomie heurystyki klasyfikatora.

Po dodaniu filtra na juz aktywne override shortlista nie pokazuje juz rekordow, ktore zostaly juz zsynchronizowane, tylko realnie otwarty backlog.

Po pierwszej realnej fali `retopic` dla `B` ten backlog nie jest juz "nieskonczonym generatorem kolejnych paczek".

Po dodaniu guardu dla juz aktywnych override mamy teraz:

- `exported_overrides = 0`
- `31` prawdziwych konfliktow sygnatur
- `29` prob odwrocenia juz aktywnego override

Czyli direct diff nadal jest naszym kanonicznym zrodlem prawdy, ale kolejne ruchy musza juz isc przez:

- review `true conflicts`
- review `override guard conflicts`
- osobny backlog `missing_local`

## Wniosek operacyjny

Od tego momentu nie musimy juz zgadywac, czy temat jest:

- za niski przez zly bucket,
- czy za niski przez brak pytan lokalnych

Mamy to policzone wprost.

To jest teraz kanoniczny artefakt do:

- dalszego domykania `B`,
- potem przejscia kategoria po kategorii,
- i podejmowania decyzji:
  - `override / retopic`
  - `import`
  - `review local-only`
