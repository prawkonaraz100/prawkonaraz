# PJ360 B Missing Local Inventory

## Cel

Ten dokument zamyka etap `topic assignment` dla `kat. B` i otwiera kolejny etap:

- nie poprawiamy juz tematow,
- nie stroimy juz klasyfikatora dla `B`,
- nie budujemy juz kolejnych fal `retopic`,
- tylko pracujemy na jawnej liscie pytan, ktorych lokalnie nadal brakuje wzgledem PJ360.

To jest backlog:

- `PJ360 B -> temat -> brakujace pytania`

czyli najbardziej konkretna forma roznicy, jaka mamy po exact sync i domknieciu konfliktow.

## Kanoniczne artefakty

- [b-missing-local-inventory.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-inventory.json)
- [missing-local-inventory-all.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/missing-local-inventory-all.json)
- [local-effective-topic-membership-B.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/local-effective-topic-membership-B.json)
- [b-exact-vs-local-topic-membership-diff.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-exact-vs-local-topic-membership-diff.json)
- generator:
  - [export_pj360_missing_local_inventory.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/export_pj360_missing_local_inventory.py)
- batch import builder:
  - [build_pj360_missing_local_import_batch.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/build_pj360_missing_local_import_batch.py)
- wykonany batch import dla `B`:
  - [pj360-b-missing-local-import.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/storage/app/import-batches/pj360-b-missing-local-import.json)
  - [pj360-b-missing-local-import-overrides.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-missing-local-import-overrides.json)
  - [b-missing-local-import-batch-report.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-import-batch-report.json)

## Aktualny stan `B`

### Stan przed batchem importowym

Na podstawie exact-membership PJ360 i lokalnego `effective membership` dla `/nauka` przed importem brakow:

- `local_total_questions = 2137`
- `target_total_questions = 2187`
- `total_delta = -50`
- `missing_local_total = 61`
- `topics_with_missing_local = 26`

Wazna uwaga:

- `missing_local_total` jest wieksze niz bezwzgledne `total_delta`

bo po domknieciu topic assignment nadal mamy jednoczesnie:

- tematy niedopelnione,
- oraz tematy lekko przepelnione lokalnie.

Czyli ten backlog nie oznacza:

- "brakuje nam po prostu 61 nowych rekordow do bazy"

tylko:

- "dla 61 wystapien membership PJ360 nie mamy lokalnego odpowiednika w tej samej sygnaturze `prompt + accepted_answer + media_kind`".

### Stan po wykonanym batchu importowym

Po wykonaniu batchu importowego dla `B`:

- `58` pytan weszlo do bazy
- z tego:
  - `53` jako `truly_missing_local`
  - `5` jako `recoverable_from_other_category`
- sync override dla batchu:
  - `58 created`
- `content:classify-question-topics --refresh --category=B`:
  - `przetworzono = 2195`
  - `zaktualizowano = 26`

Najwazniejszy efekt:

- `missing_local_total = 3`
- `topics_with_missing_local = 2`
- wszystkie remaining przypadki sa juz:
  - `already_recoverable_same_category = 3`

Czyli:

- backlog realnego importu brakow `B` zostal praktycznie zamkniety,
- nie zostaly juz zadne przypadki `truly_missing_local`,
- nie zostaly juz zadne przypadki `recoverable_from_other_category`,
- remaining `3` nie wymagaja kolejnego importu danych, tylko rozstrzygniecia w warstwie matching / duplicate handling.

## Najwieksze luki tematyczne `B`

Najwieksze tematy `missing_local` przed wykonanym batchem importowym:

1. `road_markings`
   - `6`
   - stan: `108 / 113`
2. `warning_signs`
   - `5`
   - stan: `111 / 114`
3. `road_position_entry_exit_stopping`
   - `4`
   - stan: `140 / 145`
4. `speed_limits`
   - `4`
   - stan: `37 / 41`
5. `vehicle_lights_and_signals`
   - `4`
   - stan: `51 / 54`
6. `special_caution_exiting_and_securing_vehicle`
   - `3`
   - stan: `54 / 61`
7. `vehicle_load_and_passenger_safety`
   - `3`
   - stan: `85 / 88`
8. `overtaking`
   - `3`
   - stan: `162 / 164`

To byl prawdziwy backlog importowy `B`, a nie kolejna warstwa klasyfikacji.

## Breakdown odzyskiwalnosci

### Przed importem

Po sprawdzeniu brakow `B` na zywej bazie lokalnej przed importem:

- `61` lacznych brakow exact-signature
- `53` to `truly_missing_local`
- `5` to `recoverable_from_other_category`
- `3` to `already_recoverable_same_category`

Kanoniczne artefakty:

- [b-missing-local-recovery-candidates.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-recovery-candidates.json)
- [b-missing-local-truly_missing_local.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-truly_missing_local.json)
- [b-missing-local-recoverable_from_other_category.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-recoverable_from_other_category.json)
- [b-missing-local-already_recoverable_same_category.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-already_recoverable_same_category.json)

Wniosek praktyczny przed importem:

- nie wszystkie remaining braki `B` wymagaja nowego importu,
- ale zdecydowana wiekszosc tak,
- odzyskiwalne przypadki sa juz mniejszoscia i trzeba je traktowac jako osobna, mala fala robocza.

### Po imporcie

Po ponownym przeliczeniu recovery po wykonaniu batchu:

- `missing_local_total = 3`
- `recovery_status_counts`:
  - `already_recoverable_same_category = 3`

To oznacza, ze importowa czesc backlogu `B` zostala wykonana do konca.

## Przyklady brakujacych pytan

### `road_markings`

Przyklady brakujacych rekordow:

- `2061`
  - `Czy na widocznym po lewej stronie znaku poziomym między pasami ruchu możesz zatrzymać pojazd?`
- `2063`
  - `Czy na znaku poziomym po lewej stronie za przejściem dla pieszych możesz zatrzymać pojazd?`
- `2158`
  - `Czy z tego pasa ruchu możesz skręcić w lewo na skrzyżowaniu?`

### `warning_signs`

Przyklady brakujacych rekordow:

- `2094`
  - `Czy w przedstawionej sytuacji jesteś ostrzegany o wyznaczonym na drodze przejściu dla pieszych?`
- `2148`
  - `Czy ten znak ostrzega o możliwości poślizgu pojazdu spowodowanego zawilgoceniem jezdni?`
- `3133`
  - `Czy w przedstawionej sytuacji jesteś ostrzegany o możliwości napotkania na drodze dzikich zwierząt?`

### `road_position_entry_exit_stopping`

Przyklady brakujacych rekordow:

- `2153`
  - `Czy postój pojazdu na moście jest zabroniony?`
- `3540`
  - `Czy chcąc wysadzić pasażera, masz prawo zatrzymać pojazd na jezdni w miejscu, w którym się teraz znajdujesz?`
- `3545`
  - `Czy masz prawo zatrzymać pojazd pod wiaduktem, gdy znaki tego nie zabraniają?`

### `speed_limits`

Przyklady brakujacych rekordow:

- `2453`
  - `Widoczne na znaku ograniczenie prędkości obowiązuje:`
- `2466`
  - `Który ze wskazanych znaków dotyczy kierującego samochodem osobowym?`
- `2484`
  - `Na których pasach ruchu na autostradzie możesz jechać z prędkością 140 km/h?`

## Jak czytac `b-missing-local-inventory.json`

Kazdy temat zawiera:

- `actual`
- `target`
- `delta`
- `missing_local_count`
- `questions`

Kazdy rekord w `questions` zawiera:

- `internal_question_id` z PJ360
- `page`
- `position_on_page`
- `prompt`
- `accepted_answer`
- `question_media_kind`
- `question_media_url`
- `topic_slug`
- `local_count_any_topic`
- `local_topic_distribution`
- `local_candidate_questions`

To pozwala od razu rozdzielic trzy sytuacje:

1. pytania, ktorych realnie nie mamy lokalnie,
2. pytania, ktore mamy tylko czesciowo przez duplicate signature,
3. pytania, ktore wymagaja jeszcze review wariantu, mimo ze temat jest juz zamkniety.

## Decyzja operacyjna

`Kat. B` jest juz zamknieta na poziomie:

- `exact sync`
- `retopic`
- `delta-aware conflicts`
- `manual final pass`

Czyli:

- nie wracamy juz do masowego przepisywania tematow w `B`,
- nie cofamy aktywnych override bez nowego, bardzo twardego powodu,
- backlog importowy `B` zostal juz wykonany,
- kolejny ruch to tylko:
  - rozstrzygniecie `3` remaining `already_recoverable_same_category`,
  - albo przejscie do kolejnej kategorii.

## Najblizszy krok

Najblizszy sensowny krok dla `B` po wykonanym batchu:

1. rozstrzygnac `3` remaining `already_recoverable_same_category`,
2. zdecydowac, czy chcemy jeszcze czyscic `local-only` ponad PJ360,
3. albo przejsc kategoria po kategorii dalej z tym samym workflow:
   - inventory
   - recovery split
   - import batch
   - override sync
   - refresh i live diff.
