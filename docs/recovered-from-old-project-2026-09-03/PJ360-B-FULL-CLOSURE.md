# PJ360 B Full Closure

## Cel

Ten dokument prowadzi operacyjne domkniecie kategorii `B` do rozkladu tematow zgodnego z PJ360.

To nie jest juz etap:

- budowy audytu
- budowy classifier baseline
- budowy warstwy override

To jest etap:

- domykania calego `B`
- temat po temacie
- pytanie po pytaniu
- w kontrolowanych falach rolloutowych

## Status 2026-04-15

`Kat. B` jest juz po dwoch domknieciach:

1. domknieciu warstwy `topic assignment`
2. wykonanym batchu `missing-local import`

Wykonane artefakty:

- [pj360-b-missing-local-import.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/storage/app/import-batches/pj360-b-missing-local-import.json)
- [pj360-b-missing-local-import-overrides.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-missing-local-import-overrides.json)
- [b-missing-local-import-batch-report.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-import-batch-report.json)

Wynik batchu:

- `58` pytan zapisanych
- `58` override zapisanych
- po refreshu:
  - `local_total = 2195`
  - `target_total = 2187`
  - `missing_local_total = 3`
  - remaining `3` sa juz tylko `already_recoverable_same_category`

Czyli:

- realny backlog importowy `B` zostal wykonany,
- kolejne ruchy dla `B` nie sa juz importem brakow,
- tylko czyszczeniem remaining duplicate/matching cases albo `local-only cleanup`.

### Status mediow po imporcie

Na dzien `2026-04-15` import PJ360 dla `B` ma juz wykonany audit i enrichment mediow.

Artefakty tego etapu:

- [audit_and_enrich_pj360_media.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/audit_and_enrich_pj360_media.py)
- [pj360-media-audit-pre.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-media-audit/pj360-media-audit-pre.json)
- [pj360-media-audit-write.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-media-audit/pj360-media-audit-write.json)
- [pj360-media-audit-post.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-media-audit/pj360-media-audit-post.json)
- [PJ360-MEDIA-AUDIT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-MEDIA-AUDIT.md)

Stan koncowy po enrichment:

- w bazie lokalnej jest `53` pytan ze `source = pj360`,
- z tego `45` ma co najmniej jeden rekord `question_media`,
- `24` to assety `image`,
- `21` to assety `video`,
- `8` pozostaje pytaniami tekstowymi bez medium,
- `missing_media_files = 0`,
- `videos_missing_posters = 0`,
- `rows_missing_dimensions = 0`,
- `videos_missing_duration = 0`.

Co zostalo uzupelnione:

- `poster_path` dla wszystkich filmow PJ360,
- `width` i `height` dla wszystkich assetow z plikiem lokalnym,
- `duration_seconds` dla wszystkich assetow `video`,
- `bytes` tam, gdzie lokalny plik i rekord rozjezdzaly sie metadanymi.

Wniosek praktyczny:

- jezeli pytanie PJ360 nadal wyswietla medium niepoprawnie, to problem nie siedzi juz w brakujacym pliku ani brakujacym posterze,
- trzeba wtedy debugowac konkretny runtime/component renderujacy medium, a nie sam import.

Doprecyzowanie z runtime fix:

- czesc skryptow PJ360 miala na sztywno wpisane `storage/app/public-media`,
- live runtime korzysta z `MEDIA_LOCAL_ROOT`,
- po synchronizacji `imports/pj360` do live media rootu testowe URL-e assetow zaczely odpowiadac `200`,
- kolejne importy PJ360 musza juz respektowac `MEDIA_LOCAL_ROOT`, zeby nie powtorzyc tego rozjazdu.

## Prezentacja w UI

Po imporcie brakow `B` w runtime pojawily sie nowe pytania z identyfikatorem technicznym pochodzacym z warstwy importu.

Decyzja UX:

- nie pokazujemy uzytkownikowi surowego identyfikatora technicznego,
- w widokach sesji prezentujemy te rekordy jako:
  - `Pytanie dodatkowe`
  - `Numer referencyjny: 3133`
- oraz opis:
  - `Pytanie uzupełniające do nauki.`

Cel:

- nie eksponowac technicznego ID,
- nie udawac, ze to numer z oficjalnej bazy panstwowej,
- jednoczesnie jasno sygnalizowac, ze to wartosciowy material uzupelniajacy.

## Stan startowy

Aktualny stan lokalny `B`:

- `2137` pytan
- `31` tematow
- `content:classify-question-topics --refresh --category=B` daje `zaktualizowano: 0`
- `content:report-question-topic-assignment-delta --category=B` daje `changed_questions=0`

To oznacza, ze:

- lokalna baza `B` jest stabilna
- aktualny stan pytan jest zgodny z obecnym baseline `classifier + override`
- kolejne ruchy nie beda juz wynikały z prostego `current != classifier`

## Biezacy etap

Jestesmy juz po:

- audycie PJ360 vs nasze `/nauka`
- wdrozeniu warstwy `classifier + audited overrides`
- wdrozeniu spec-driven buildera review-only paczek override
- trzech pierwszych falach override dla `B`

To oznacza, ze `B` weszlo w etap:

- kontrolowanego domykania delty
- temat po temacie
- fala po fali
- z dokumentowanym `build -> review -> sync -> reclassify -> verify`

## Pivot: exact topic membership z PJ360

Na tym etapie `B` wchodzi w nowy, mocniejszy tryb domykania:

- nie tylko `classifier + audited overrides`
- ale `exact-membership` z PJ360 per temat.

Wczesniejsze artefakty PJ360 daly nam juz mocne mapowanie tozsamosci pytan:

- overlap promptow,
- korpus pytan,
- korpus wyjasnien,
- bardzo duze pokrycie lokalnej bazy.

Ale nie dawaly jeszcze pelnej listy:

- `B -> temat -> konkretne pytania`

bezposrednio z paginowanych stron `/kurs/<slug>`.

Nowa decyzja operacyjna:

1. dla `B` zaciagamy exact-membership z PJ360 topic pages,
2. mapujemy wszystkie pytania, ktore juz mamy lokalnie,
3. generujemy exact override package,
4. dopiero potem synchronizujemy override do systemu.

To jest sensowniejsze od dalszego strojenia samych heurystyk tam, gdzie PJ360 juz daje nam kanoniczna liste pytan dla tematu.

## Najwieksze delty `B`

Najmocniej przeszacowane:

- `Znaczenie zachowania szczegolnej ostroznosci...`
  - lokalnie `312`
  - PJ360 `61`
  - delta `+251`
- `Wlaczanie sie do ruchu, skrzyzowania rownorzedne`
  - lokalnie `180`
  - PJ360 `86`
  - delta `+94`
- `Technika kierowania pojazdem`
  - lokalnie `63`
  - PJ360 `31`
  - delta `+32`
- `Odstepy i hamowanie pojazdu`
  - lokalnie `69`
  - PJ360 `46`
  - delta `+23`

Najmocniej zanizone:

- `Znaki zakazu, nakazu`
  - lokalnie `27`
  - PJ360 `102`
  - delta `-75`
- `Skrzyzowania z sygnalizacja swietlna`
  - lokalnie `9`
  - PJ360 `59`
  - delta `-50`
- `Sygnaly swietlne, sygnaly dawane przez kierujacego ruchem`
  - lokalnie `53`
  - PJ360 `93`
  - delta `-40`
- `Zachowanie na przejazdach kolejowych i tramwajowych`
  - lokalnie `60`
  - PJ360 `97`
  - delta `-37`
- `Czynniki bezpieczenstwa odnoszace sie do pojazdu, ladunku i przewozonych osob`
  - lokalnie `54`
  - PJ360 `88`
  - delta `-34`

## Co juz wiemy o przyczynach

Pelny audit `B` pokaze teraz bardzo czytelny wzor:

- `special_caution_exiting_and_securing_vehicle`
  - glownie `fallback:basic = 265`
- `joining_traffic_and_equal_intersections`
  - glownie `keyword:equal_intersection_priority = 123`
  - oraz `keyword:generic_intersection = 43`
- `driving_technique`
  - glownie `fallback:specialist = 49`
- `distances_and_braking`
  - glownie `keyword:distances_braking = 69`

To oznacza, ze `B` nie jest juz problemem "kilku zlych pytan". To sa jeszcze duze klasy bledow.

## Dlaczego zwykla spec override juz nie wystarcza

Pierwsza wersja buildera paczek override potrafi filtrowac po:

- `current_topic_keys`
- `classifier_matched_by`

To wystarcza do:

- czystych, high-confidence batchy
- tematow pilotowych

Ale nie wystarcza do calkowitego domkniecia `B`, bo:

- `fallback:basic` obejmuje bardzo rozne typy pytan
- pytania z tym samym `matched_by` trzeba czasem rozbic dalej po:
  - fragmencie promptu
  - typie medium
  - kodzie znaku w `main_media_original`

## Kierunek etapu full closure

Najbezpieczniejszy kolejny ruch dla `B`:

1. nie robic jednego masowego reclassify
2. nie przepinac recznie setek rekordow po `question_id`
3. rozbudowac spec-driven package o bogatsze filtry review

Potrzebne filtry:

- `prompt_contains_any`
- `prompt_not_contains_any`
- `media_contains_any`
- `media_not_contains_any`
- opcjonalnie `external_ids`

Status:

- `DONE`
- builder review-only paczek override obsluguje juz te filtry
- mamy zielone testy dla:
  - `prompt`
  - `main_media_original`
  - `external_id`

To pozwoli budowac fale override typu:

- `fallback:basic + media D11 + prompt o skrecie -> lane_change_and_turning`
- `fallback:basic + media D24/D26 + prompt o przejezdzie -> rail_and_tram_crossings`
- `fallback:basic + prompt o pieszym -> behaviour_towards_pedestrians...`

bez ryzyka, ze jednym ruchem rozwalimy cala kategorie.

## Fale rolloutowe dla `B`

### Fala 1. Fallback cleanup

Cel:

- wyciagnac najbardziej oczywiste pytania z:
  - `special_caution`
  - `driving_technique`

Metoda:

- bogatsza spec package
- bez ruszania jeszcze calego classifiera
- pierwsze realne batch'e powinny teraz isc w:
  - `fallback:basic + prompt o pieszym/przejsciu`
  - `fallback:basic + media D24/D26 + prompt o przejezdzie`
  - `fallback:specialist + prompt o liczbie osob / DMC / ladunku`

Status:

- `DONE`
- spec:
  - [pj360-b-wave-1-fallback-cleanup-spec.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-1-fallback-cleanup-spec.json)
- review-only package:
  - [pj360-b-wave-1-fallback-cleanup-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-1-fallback-cleanup-package.json)
- dry-run sync:
  - `18/18` poprawnych rekordow
- realny sync:
  - `18` override zapisanych

Efekt po realnym sync i ponownym `content:classify-question-topics --refresh --category=B`:

- `special_caution`: `312 -> 297`
- `behaviour_towards_pedestrians_and_reduced_mobility`: `89 -> 98`
- `rail_and_tram_crossings`: `60 -> 66`
- `rescue_actions`: `18 -> 21`
- `driving_technique`: `63 -> 60`

Wniosek:

- fala 1 dala realny, poprawny ruch
- nie rozwalila innych tematow
- ale zebrala tylko najbardziej oczywiste przypadki
- glowny temat-smietnik `special_caution` nadal wymaga kilku kolejnych fal

### Fala 2. Skrzyzowania i sygnalizacja

Cel:

- sciagnac nadmiar z:
  - `joining_traffic_and_equal_intersections`
- podbic:
  - `intersections_with_traffic_lights`
  - `traffic_lights_and_controller_signals`
  - `controlled_crossings_and_public_transport_stops`

Status:

- `DONE`
- spec:
  - [pj360-b-wave-2-signals-and-signs-spec.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-2-signals-and-signs-spec.json)
- review-only package:
  - [pj360-b-wave-2-signals-and-signs-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-2-signals-and-signs-package.json)
- dry-run sync:
  - `15/15` poprawnych rekordow
- realny sync:
  - `15` override zapisanych

Efekt po realnym sync i ponownym `content:classify-question-topics --refresh --category=B`:

- `special_caution`: `297 -> 282`
- `traffic_lights_and_controller_signals`: `53 -> 57`
- `intersections_with_traffic_lights`: `9 -> 10`
- `informational_direction_and_supplementary_signs`: `80 -> 82`
- `warning_signs`: `95 -> 97`
- `prohibition_and_mandatory_signs`: `27 -> 31`
- `lane_change_and_turning`: `125 -> 126`
- `perception_decision_alcohol_fatigue`: `15 -> 16`

Wniosek:

- fala 2 dobrze wyciagnela najbardziej oczywiste pytania znakowe i sygnalizacyjne
- nie naruszyla trafionych tematow
- ale nadal zostaje duzy niedobor w:
  - `traffic_lights_and_controller_signals`
  - `intersections_with_traffic_lights`
  - `controlled_crossings_and_public_transport_stops`

### Fala 3. Tematy znakowe

Cel:

- podbic:
  - `warning_signs`
  - `prohibition_and_mandatory_signs`

Metoda:

- lepsze wykorzystanie `main_media_original`
- oraz spec package z filtrami prompt/media

Status:

- `DONE`
- spec:
  - [pj360-b-wave-3-speed-risk-merge-spec.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-3-speed-risk-merge-spec.json)
- review-only package:
  - [pj360-b-wave-3-speed-risk-merge-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-3-speed-risk-merge-package.json)
- dry-run sync:
  - `16/16` poprawnych rekordow
- realny sync:
  - `16` override zapisanych

Efekt po realnym sync i ponownym `content:classify-question-topics --refresh --category=B`:

- `speed_limits`: `30 -> 35`
- `risk_factors_conditions_and_weather`: `23 -> 29`
- `lane_change_and_turning`: `126 -> 131`
- `driving_technique`: `60 -> 53`
- `special_caution`: `282 -> 273`

Wniosek:

- fala 3 dobrze domknela najczystsze przypadki:
  - `speed_limits`
  - `risk_factors_conditions_and_weather`
  - `jazda na suwak` w `lane_change_and_turning`
- `driving_technique` schodzi, ale nadal jest za wysoko
- `special_caution` nadal pozostaje glownym tematem-smietnikiem

## Aktualny stan po falach 1-5

Najwazniejsze bieżące liczniki `B`:

- `warning_signs`: `97 / 114`
- `prohibition_and_mandatory_signs`: `32 / 102`
- `informational_direction_and_supplementary_signs`: `82 / 82`
- `road_markings`: `87 / 113`
- `traffic_lights_and_controller_signals`: `57 / 93`
- `joining_traffic_and_equal_intersections`: `163 / 86`
- `intersections_with_priority_signs`: `142 / 128`
- `intersections_with_traffic_lights`: `19 / 59`
- `controlled_crossings_and_public_transport_stops`: `11 / 27`
- `road_position_entry_exit_stopping`: `146 / 145`
- `lane_change_and_turning`: `136 / 148`
- `overtaking`: `153 / 164`
- `passing_reversing`: `50 / 62`
- `vehicle_lights_and_signals`: `59 / 54`
- `special_caution_exiting_and_securing_vehicle`: `263 / 61`
- `behaviour_towards_pedestrians_and_reduced_mobility`: `100 / 105`
- `behaviour_towards_cyclists_and_children`: `27 / 42`
- `rail_and_tram_crossings`: `66 / 97`
- `breakdown_accident_and_first_aid`: `39 / 60`
- `perception_decision_alcohol_fatigue`: `16 / 45`
- `speed_limits`: `35 / 41`
- `safety_equipment_and_restraints`: `22 / 22`
- `distances_and_braking`: `69 / 46`
- `risk_factors_conditions_and_weather`: `29 / 38`
- `driver_field_of_view`: `22 / 21`
- `driving_technique`: `52 / 31`
- `vehicle_load_and_passenger_safety`: `54 / 88`
- `owner_obligations_insurance_documents`: `35 / 30`
- `mechanical_aspects_of_safety`: `47 / 56`
- `rescue_actions`: `26 / 26`
- `driving_with_trailer`: `1 / 1`

Wnioski przekrojowe:

- idealnie trafione mamy juz:
  - `informational_direction_and_supplementary_signs`
  - `safety_equipment_and_restraints`
  - `rescue_actions`
  - `driving_with_trailer`
- bardzo blisko targetu sa:
  - `road_position_entry_exit_stopping`
  - `speed_limits`
  - `driver_field_of_view`
- glowny problem nadal siedzi w:
  - `special_caution_exiting_and_securing_vehicle`
  - `joining_traffic_and_equal_intersections`
  - `intersections_with_traffic_lights`
  - `prohibition_and_mandatory_signs`
  - `vehicle_load_and_passenger_safety`

### Fala 4. Piesi, przystanki i akcje ratunkowe

Cel:

- `rescue_actions`
- `breakdown_accident_and_first_aid`
- `behaviour_towards_pedestrians_and_reduced_mobility`
- `behaviour_towards_cyclists_and_children`
- `controlled_crossings_and_public_transport_stops`
- pojedyncze, bardzo czyste ruchy dla:
  - `intersections_with_traffic_lights`
  - `lane_change_and_turning`
  - `road_markings`

Status:

- `DONE`
- spec:
  - [pj360-b-wave-4-vulnerable-and-rescue-spec.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-4-vulnerable-and-rescue-spec.json)
- review-only package:
  - [pj360-b-wave-4-vulnerable-and-rescue-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-4-vulnerable-and-rescue-package.json)
- dry-run sync:
  - `14/14` poprawnych rekordow
- realny sync:
  - `14` override zapisanych

Efekt po realnym sync i ponownym `content:classify-question-topics --refresh --category=B`:

- `rescue_actions`: `21 -> 26`
- `breakdown_accident_and_first_aid`: `37 -> 39`
- `behaviour_towards_pedestrians_and_reduced_mobility`: `98 -> 100`
- `behaviour_towards_cyclists_and_children`: `26 -> 27`
- `controlled_crossings_and_public_transport_stops`: `10 -> 11`
- `intersections_with_traffic_lights`: `10 -> 11`
- `lane_change_and_turning`: `131 -> 132`
- `road_markings`: `86 -> 87`
- `special_caution`: `273 -> 263`
- `joining_traffic_and_equal_intersections`: `180 -> 177`
- `driving_technique`: `53 -> 52`

Wniosek:

- fala 4 domknela idealnie `rescue_actions`
- podbila kilka stale zanizonych tematow bez skutkow ubocznych
- dalej najwiekszy problem pozostaje w:
  - `special_caution`
  - `joining_traffic_and_equal_intersections`
  - `intersections_with_traffic_lights`
  - `prohibition_and_mandatory_signs`
  - `vehicle_load_and_passenger_safety`

### Fala 5. Sygnalizacja i manewry na skrzyzowaniu

Cel:

- podbic:
  - `intersections_with_traffic_lights`
  - `lane_change_and_turning`
  - `overtaking`
  - `prohibition_and_mandatory_signs`
- dalej zdejmowac nadmiar z:
  - `joining_traffic_and_equal_intersections`

Status:

- `DONE`
- spec:
  - [pj360-b-wave-5-signalized-and-lane-spec.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-5-signalized-and-lane-spec.json)
- review-only package:
  - [pj360-b-wave-5-signalized-and-lane-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-5-signalized-and-lane-package.json)
- dry-run sync:
  - `14/14` poprawnych rekordow
- realny sync:
  - `14` override zapisanych

Efekt po realnym sync i ponownym `content:classify-question-topics --refresh --category=B`:

- `intersections_with_traffic_lights`: `11 -> 19`
- `lane_change_and_turning`: `132 -> 136`
- `overtaking`: `152 -> 153`
- `prohibition_and_mandatory_signs`: `31 -> 32`
- `joining_traffic_and_equal_intersections`: `177 -> 163`

Wniosek:

- fala 5 bardzo dobrze trafila w pakiet `wjazd na skrzyzowanie przy sygnalizacji`
- to jest pierwszy ruch, ktory wyraznie zdjal nadmiar z `joining_traffic`
- `intersections_with_traffic_lights` sa nadal zanizone, ale ruch jest juz duzy i semantycznie czysty

## Definicja sukcesu dla `B`

`B` uznajemy za domkniete, gdy:

1. wszystkie `31` tematow pozostaja widoczne
2. mamy tylko male, kontrolowane delty do PJ360
3. nie ma juz tematow-smietnikow typu:
   - `special_caution = 312`
4. rollout jest odtwarzalny:
   - spec
   - build
   - review
   - sync
   - delta report

## Najblizszy krok wykonawczy

Najblizszy praktyczny ruch dla `B` jest teraz taki:

1. zejsc glebiej w pozostalą mase `special_caution`
2. przygotowac kolejna fale dla:
   - `traffic_lights_and_controller_signals`
   - `intersections_with_traffic_lights`
   - `controlled_crossings_and_public_transport_stops`
3. zaczac systematycznie zdejmowac nadmiar z:
   - `joining_traffic_and_equal_intersections`
4. osobno znalezc brakujace, bezpieczne wzory dla:
   - `vehicle_load_and_passenger_safety`
   - `breakdown_accident_and_first_aid`
   - `mechanical_aspects_of_safety`

## Lista zadan wykonawczych dla `B`

### Zadania domkniete

- [x] audit PJ360 vs nasze `/nauka`
- [x] target matrix dla tematow pilotowych
- [x] wdrozenie `classifier + audited overrides`
- [x] wdrozenie spec-driven buildera override
- [x] fala 1: fallback cleanup
- [x] fala 2: sygnaly i pytania znakowe
- [x] fala 3: predkosci, ryzyko i jazda na suwak
- [x] fala 4: piesi, przystanki i akcje ratunkowe
- [x] fala 5: sygnalizacja i manewry na skrzyzowaniu

### Zadania otwarte

- [x] extractor exact-membership `PJ360 /kurs/<topic>` dla `B`
- [x] builder exact override package dla `B`
- [x] pilot exact-membership dla `warning_signs`
- [x] rollout exact-membership dla pozostalych tematow `B`
- [ ] dry-run sync exact override package dla `B`
- [ ] realny sync exact override package dla `B`
- [ ] `content:classify-question-topics --refresh --category=B` po exact sync
- [ ] raport `B` po exact sync: before/after vs PJ360
- [ ] analiza `unresolved = 234` dla exact-membership `B`
- [ ] analiza pytan lokalnych `B`, ktorych nie ma w exact-membership PJ360
- [ ] fala 6: controlled crossings / kierujacy ruchem / przystanki
- [ ] fala 7: dalsza redukcja `joining_traffic_and_equal_intersections`
- [ ] fala 8: `vehicle_load_and_passenger_safety`
- [ ] fala 9: `breakdown_accident_and_first_aid`
- [ ] fala 10: `mechanical_aspects_of_safety`
- [ ] fala 11: domkniecie tematow znakowych `warning / prohibition`
- [ ] finalny raport `B before/after` i decyzja o merge do `main`

## Exact-membership checkpoint dla `B`

Pelny extractor exact-membership dla `B` jest juz wykonany.

Stan:

- `31/31` tematow
- `2187` pytan z PJ360
- exact override package:
  - `952` override do synchronizacji
  - `234` unresolved

To oznacza, ze dla `B` mamy juz kompletna kanoniczna liste:

- `PJ360 B -> temat -> konkretne pytania`

i od tego miejsca mozemy domykac `B` bardziej referencyjnie niz heurystycznie.

## Stan po realnym exact sync `B`

Wykonane:

- `dry-run` sync exact package:
  - `952` rekordy
  - `865` create
  - `87` update
- realny sync exact package:
  - `952` override zapisanych
- `content:classify-question-topics --refresh --category=B`:
  - `2137` pytan przetworzonych
  - `952` rekordy przepisane do nowych tematow

Najwazniejsze liczniki po exact sync:

- `warning_signs`: `107 / 114`
- `prohibition_and_mandatory_signs`: `97 / 102`
- `informational_direction_and_supplementary_signs`: `79 / 82`
- `road_markings`: `106 / 113`
- `traffic_lights_and_controller_signals`: `84 / 93`
- `joining_traffic_and_equal_intersections`: `91 / 86`
- `intersections_with_priority_signs`: `132 / 128`
- `intersections_with_traffic_lights`: `57 / 59`
- `controlled_crossings_and_public_transport_stops`: `24 / 27`
- `road_position_entry_exit_stopping`: `147 / 145`
- `lane_change_and_turning`: `142 / 148`
- `overtaking`: `161 / 164`
- `passing_reversing`: `60 / 62`
- `vehicle_lights_and_signals`: `53 / 54`
- `special_caution_exiting_and_securing_vehicle`: `72 / 61`
- `behaviour_towards_pedestrians_and_reduced_mobility`: `94 / 105`
- `behaviour_towards_cyclists_and_children`: `41 / 42`
- `rail_and_tram_crossings`: `98 / 97`
- `breakdown_accident_and_first_aid`: `59 / 60`
- `perception_decision_alcohol_fatigue`: `45 / 45`
- `speed_limits`: `37 / 41`
- `safety_equipment_and_restraints`: `24 / 22`
- `distances_and_braking`: `45 / 46`
- `risk_factors_conditions_and_weather`: `36 / 38`
- `driver_field_of_view`: `20 / 21`
- `driving_technique`: `32 / 31`
- `vehicle_load_and_passenger_safety`: `79 / 88`
- `owner_obligations_insurance_documents`: `35 / 30`
- `mechanical_aspects_of_safety`: `54 / 56`
- `rescue_actions`: `25 / 26`
- `driving_with_trailer`: `1 / 1`

Wniosek:

- `B` po exact sync jest juz blisko zgodnosci z PJ360,
- glowny problem nie jest juz w masowej blednej klasyfikacji,
- tylko w:
  - `unresolved = 234`,
  - globalnym `-50` pytan lokalnie vs PJ360,
  - kilku malych przesunieciach miedzy tematami.

## Stan po drugiej fali exact sync `B`

Po dopieciu grupowego exact-match dla duplikatow promptow:

- builder exact package zszedl do:
  - `40` dodatkowych override
  - `127` unresolved
- po realnym sync i `content:classify-question-topics --refresh --category=B` dostalismy:
  - `40` dodatkowych przepisanych pytan

Najwazniejsze liczniki po tej fali:

- `joining_traffic_and_equal_intersections`: `86 / 86`
- `mechanical_aspects_of_safety`: `56 / 56`
- `safety_equipment_and_restraints`: `22 / 22`
- `perception_decision_alcohol_fatigue`: `45 / 45`
- `prohibition_and_mandatory_signs`: `100 / 102`
- `lane_change_and_turning`: `146 / 148`
- `vehicle_load_and_passenger_safety`: `85 / 88`
- `warning_signs`: `109 / 114`
- `traffic_lights_and_controller_signals`: `88 / 93`
- `intersections_with_traffic_lights`: `53 / 59`
- `road_markings`: `106 / 113`
- `behaviour_towards_pedestrians_and_reduced_mobility`: `96 / 105`

Najwazniejszy operacyjny sygnal:

- ponowny build exact package po tej fali daje:
  - `exported_overrides = 0`
  - `unresolved = 127`

Wniosek:

- dla `B` wyczerpalismy juz bezpieczne exact override wynikajace wprost z PJ360 membership,
- pozostale roznice to juz:
  - trudniejsze `ambiguous_local_match`,
  - realne `no_local_prompt_match`,
  - lokalne przesuniecia wynikajace z globalnego `-50` pytan vs PJ360.

## Nowy etap po exact sync `B`

Od tego miejsca `B` rozbijamy juz jawnie na dwa backlogi:

1. `ambiguous_local_match`
2. `realne import gaps`

Kanoniczny runbook tego etapu:

- [PJ360-B-AMBIGUOUS-AND-MISSING-WORKSET.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-B-AMBIGUOUS-AND-MISSING-WORKSET.md)

Wykonywalne artefakty:

- [b-ambiguous-review.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-ambiguous-review.json)
- [b-import-gap-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-import-gap-shortlist.json)

Aktualny checkpoint tego etapu:

- `70` nierozstrzygnietych duplikatow dla `B`
- `54` realne `no_local_prompt_match` dla `B`
- `3` twarde `media_kind_mismatch`, ktore wymagaja review, ale nie powinny trafiać automatycznie do importu

To jest teraz glowny punkt wejscia do dalszej pracy nad `B`, bo:

- exact sync jest juz domkniety,
- builder exact paczek nie daje nowych bezpiecznych override,
- kolejne ruchy musza wynikac z review duplikatow albo z realnego backlogu importowego.

## Stan po pierwszej fali `retopic` dla `B`

Po direct diff `PJ360 exact -> local membership` zbudowalismy pierwsza paczke `retopic`, czyli pytan:

- obecnych juz lokalnie,
- ale nadal przypietych do zlego dzialu wzgledem PJ360.

Przebieg:

- pierwsza paczka `retopic` dala `29` bezpiecznych override
- `dry-run` sync przeszedl czysto:
  - `29` rekordow
  - `27` create
  - `2` update
- realny sync wszedl poprawnie

W trakcie diagnostyki batchowego refreshu jedno pytanie zostalo przepisane pojedynczym wywolaniem `assign()` na rekordzie testowym, a kolejny przebieg:

- `content:classify-question-topics --refresh --category=B`

domknal pozostale `28` zmian.

Najwazniejsze liczniki po tej fali:

- `warning_signs: 111 / 114`
- `prohibition_and_mandatory_signs: 100 / 102`
- `intersections_with_traffic_lights: 57 / 59`
- `controlled_crossings_and_public_transport_stops: 26 / 27`
- `traffic_lights_and_controller_signals: 88 / 93`
- `intersections_with_priority_signs: 122 / 128`
- `joining_traffic_and_equal_intersections: 92 / 86`
- `road_markings: 108 / 113`
- `overtaking: 158 / 164`

To jest duzy krok, bo w praktyce zostaly juz glownie male delty i trudniejsze przypadki signature-level.

Najwazniejszy bezpiecznik po tej fali:

- kolejny build paczki `retopic` daje juz:
  - `exported_overrides = 0`
  - `skipped_conflicts = 31`
  - `skipped_existing_override_conflicts = 29`

Wniosek:

- najczystsze ruchy `retopic` dla `B` sa juz wykorzystane,
- nie warto dalej iterowac tej samej paczki bez guardow,
- pozostaly dwa osobne backlogi:
  - prawdziwe konflikty membership PJ360 na tej samej sygnaturze,
  - proby odwrocenia juz aktywnych override, ktore guard celowo blokuje.

## Stan po falach `delta-aware` i finalnym dogieciu automatyki

Po pierwszej fali `retopic` dolozylismy jeszcze planner:

- [build_pj360_delta_aware_conflict_package.py](C:/Users/xxx/Desktop/serwistestyprawojazdy/scripts/build_pj360_delta_aware_conflict_package.py)

Ten krok bierze:

- prawdziwe konflikty membership PJ360,
- aktualna delte `B`,
- aktywne override,

i buduje tylko taki pakiet, w ktorym:

- jest unikalny zwyciezca po `remaining deficit`,
- ruch nie jest juz pokryty aktywnym override,
- remis zostaje w backlogu manualnym.

Przebieg dla `B`:

1. pierwsza fala `delta-aware`
   - `20` override
   - `11` manualnych konfliktow
2. odzyskana mini-fala `retopic`
   - `5` override
3. finalne fale `delta-aware`
   - `2` override

W czasie tej pracy dwa razy wystapil ten sam znany objaw techniczny:

- pierwszy `content:classify-question-topics --refresh --category=B` po syncu override przechodzil `0 updated`,
- dopiero kolejny refresh materializowal zmiany.

Jeden rekord testowy zostal tez uczciwie domkniety pojedynczym wywolaniem `QuestionTopicAssigner::assign()` w trakcie diagnostyki, po potwierdzeniu, ze override dziala poprawnie.

Stan koncowy automatyki dla `B`:

- [pj360-b-retopic-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-retopic-package.json)
  - `exported_overrides = 0`
  - `skipped_conflicts = 0`
- [pj360-b-delta-aware-conflict-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-delta-aware-conflict-package.json)
  - `exported_overrides = 0`
  - `manual_conflicts = 0`
  - `skipped_existing_override_same_target = 0`
- [pj360-b-manual-conflict-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-manual-conflict-package.json)
  - `4` jawnie rozstrzygniete konflikty final-pass dla `B`

Najwazniejsze liczniki `B` po tym etapie:

- `warning_signs: 111 / 114`
- `prohibition_and_mandatory_signs: 102 / 102`
- `controlled_crossings_and_public_transport_stops: 26 / 27`
- `intersections_with_traffic_lights: 59 / 59`
- `traffic_lights_and_controller_signals: 93 / 93`
- `lane_change_and_turning: 147 / 148`
- `overtaking: 162 / 164`
- `road_markings: 108 / 113`
- `intersections_with_priority_signs: 120 / 128`
- `special_caution_exiting_and_securing_vehicle: 54 / 61`

Wniosek:

- `kat. B` nie jest juz na etapie duzych automatycznych rolloutow,
- automatyka zostala domknieta do konca dla warstwy `retopic / conflict resolution`,
- zostal juz tylko:
  - `missing_local = 59`,
  - `available_other_topic_total = 34`,
  - i kilka lokalnych przesuniec miedzy tematami.

## Finalny pivot dla `B`: missing-local inventory

Po domknieciu:

- `exact sync`
- `retopic`
- `delta-aware conflict resolution`
- `manual final pass`

`kat. B` przestaje byc backlogiem tematycznym.

Od tego miejsca pracujemy juz na:

- [PJ360-B-MISSING-LOCAL-INVENTORY.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-B-MISSING-LOCAL-INVENTORY.md)
- [b-missing-local-inventory.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-inventory.json)

Aktualny checkpoint:

- `local_total_questions = 2137`
- `target_total_questions = 2187`
- `total_delta = -50`
- `missing_local_total = 61`
- `topics_with_missing_local = 26`

Najwieksze remaining braki `B`:

- `road_markings = 6`
- `warning_signs = 5`
- `road_position_entry_exit_stopping = 4`
- `speed_limits = 4`
- `vehicle_lights_and_signals = 4`

Wniosek operacyjny po wykonanym batchu:

- `B` jest zamkniete na poziomie przypisania do tematow,
- `B` nie wymaga juz kolejnych fal `retopic`,
- `B` nie ma juz realnego backlogu `truly_missing_local`,
- remaining backlog to juz tylko:
  - `3` przypadki `already_recoverable_same_category`
  - oraz lokalne nadmiary ponad exact membership PJ360.
