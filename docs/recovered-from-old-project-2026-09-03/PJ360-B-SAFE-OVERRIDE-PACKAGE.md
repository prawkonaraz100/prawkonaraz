# PJ360 B Safe Override Package

## Cel

Ten dokument zapisuje pierwszy curated, high-confidence batch override dla kategorii `B`.

To nie jest jeszcze finalny rollout wszystkich rozjazdow do PJ360. To jest:

- pierwszy bezpieczny pakiet
- oparty na ostrych filtrach `matched_by`
- bez `fallback:*`
- przygotowany tak, zeby dac realny zysk bez wrzucania szumu do override

## Status

Pakiet zostal wygenerowany na branchu:

- `codex/pj360-category-consistency-audit`

na podstawie:

- `content:audit-question-topic-reclassifier --category=B --scope=active_ready`

Wynik przebiegu:

- `changed_questions = 756`
- `exported_candidates = 134`
- `skipped_without_identity = 0`
- `skipped_low_confidence = 57`
- `skipped_by_matched_by_filter = 565`
- `skipped_by_topic_filter = 0`

To oznacza, ze:

- aktywnie wycielismy fallbacki
- aktywnie wycielismy duza czesc zmian poza allowlista
- zostal tylko najbezpieczniejszy zestaw pierwszych kandydatow dla `B`

## Filtry uzyte do batcha

### Matched By Allowlist

- `keyword:road_markings_reflectors`
- `keyword:traffic_signal`
- `keyword:public_transport_stop`
- `keyword:pedestrians`
- `keyword:transported_people`
- `keyword:lane_change_turning`
- `keyword:lane_change_signal_usage`

### Topic Allowlist

- `road_markings`
- `traffic_lights_and_controller_signals`
- `controlled_crossings_and_public_transport_stops`
- `behaviour_towards_pedestrians_and_reduced_mobility`
- `vehicle_load_and_passenger_safety`
- `lane_change_and_turning`

## Dokladna komenda

```powershell
docker compose exec -T app php artisan content:build-question-topic-override-package `
  resources/topic-overrides/pj360-b-safe-override-package-spec.json `
  --json=docs/pj360-b-safe-override-candidates.json
```

## Kanoniczna specyfikacja paczki

- [pj360-b-safe-override-package-spec.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-safe-override-package-spec.json)

To jest od teraz kanoniczny opis tej fali rolloutu. Trzymamy go poza `docs/`, bo ta sciezka musi byc jednoczesnie:

- wersjonowana w repo
- i wykonywalna przez komendy uruchamiane w kontenerze

## Przykladowe rekordy z batcha

### Road Markings

- `external_id = 599`
  - `road_position_entry_exit_stopping -> road_markings`
  - `matched_by = keyword:road_markings_reflectors`
- `external_id = 600`
  - `road_position_entry_exit_stopping -> road_markings`
  - `matched_by = keyword:road_markings_reflectors`

### Lane Change And Turning

- `external_id = 610`
  - `intersections_with_priority_signs -> lane_change_and_turning`
  - `matched_by = keyword:lane_change_turning`

## Dlaczego ten pakiet jest bezpieczny

1. Nie bierze `fallback:*`.
2. Nie bierze topicow poza allowlista.
3. Nie bierze pytan bez stabilnej tozsamosci:
   - `category_code`
   - `source`
   - `external_id`
4. Jest w pelni review-only przed synchronizacja override.

## Co robimy dalej

1. Synchronizujemy ten batch do `question_topic_overrides`.
2. Przeliczamy temat dla `B`.
3. Robimy raport delty `before / after`.
4. Oceniamy:
   - czy `road_markings`
   - `lane_change_and_turning`
   - `vehicle_load_and_passenger_safety`
   rzeczywiscie zblizyly sie do targetu PJ360.
5. Dopiero potem szykujemy kolejny curated batch.
