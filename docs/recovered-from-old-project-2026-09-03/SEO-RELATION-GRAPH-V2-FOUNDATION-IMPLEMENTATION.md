# Graf relacji pytań V2 — addytywny fundament danych

Status: `DEPLOYED ON PRODUCTION — PUBLIC V1 UNCHANGED`

Data implementacji: `2026-07-21`

Data wdrożenia produkcyjnego: `2026-07-27`

ADR: `SEO-LINK-V2-002 — ACCEPTED`

Migracja: `2026_07_21_231000_add_question_relation_graph_v2_foundation.php`

## Cel etapu

Etap A przygotowuje strukturę danych V2 bez zmiany publicznego selektora relacji. Nie wykonuje backfillu, nie publikuje rankingów i nie włącza trybu `shadow`, `canary` ani `v2`.

Po migracji produkcyjne V1 nadal korzysta z `PublicQuestionRelationsService` i obecnych statusów relacji. Nowe kolumny oraz tabele pozostają nieaktywne do czasu osobno zatwierdzonego backfillu.

## Zakres migracji

### Rozszerzone tabele

- `question_seo_topics`: `kind`, status publikacji, status jakości treści i metadata,
- `question_seo_topic_memberships`: wiele tematów na pytanie, `is_primary`, rola, status, confidence i ślad recenzji,
- `question_seo_topic_relations`: status, źródło, uzasadnienie, score, metadata i recenzent.

Unikalność membershipu zmienia się z jednego rekordu na pytanie na unikalną parę:

```text
(question_public_explanation_id, question_seo_topic_id)
```

PostgreSQL otrzymuje częściowy indeks gwarantujący najwyżej jeden zweryfikowany membership główny na pytanie. W SQLite pełny inwariant będzie egzekwowany przez serwis domenowy dodawany przed backfillem.

### Nowe tabele

- `question_relation_evidences` — wiele jawnych dowodów dla jednej pary pytań,
- `question_relation_ranking_runs` — wersjonowane snapshoty generatora per klaster,
- `question_relation_recommendations` — kierunkowe, precomputowane rekomendacje per pytanie,
- `question_relation_rollouts` — tryb `v1`, `shadow`, `canary` albo `v2` oraz aktywny snapshot klastra.

## Bezpieczeństwo V1

- żadne pole ani tabela V1 nie są usuwane,
- nie zmieniają się canonicale, routing, sitemap ani SSR,
- nie jest uruchamiany generator V2,
- istniejące membershipy nie są automatycznie klasyfikowane jako V2,
- nowe rollouty domyślnie mają tryb `v1`,
- awaryjny wyłącznik `QUESTION_RELATIONS_ENABLED` pozostaje bez zmian.

Rollback funkcjonalny na produkcji polega na pozostawieniu rolloutów w trybie `v1`. Produkcyjnego rollbacku nie wykonujemy przez cofanie migracji po zapisaniu membershipów dodatkowych. Techniczny `down()` istnieje dla pustego środowiska testowego i został sprawdzony, aby potwierdzić zachowanie tabel oraz unikalności V1.

## Walidacja wykonana przed wdrożeniem

- migracja `up()` na SQLite,
- zapis dwóch różnych membershipów dla jednego pytania,
- blokada duplikatu tej samej pary pytanie–temat,
- zapis dowodu, runu rankingu, rekomendacji i rolloutu,
- migracja `down()` i przywrócenie pojedynczej unikalności V1,
- pełna migracja `up()` na izolowanej bazie PostgreSQL 16,
- obecność częściowych indeksów `qstm_one_verified_primary` i `qrr_selected_rank_unique`,
- rollback ostatniej migracji na PostgreSQL i zachowanie tabel V1,
- test regresji publicznego komponentu relacji.

## Wynik wdrożenia produkcyjnego

Wdrożenie wykonano 27 lipca 2026 r. z pakietu utworzonego z dokładnego commita
`e67a8fa5` (`Add relation graph V2 data foundation`). Przed migracją wykonano
backup bazy i potwierdzono jego świeżość:

```text
backups/database/2026/07/20260727-145958-prawkonarazpl-pgsql-pgsql-seo-relation-v2-foundation-20260727.sql.gz
backups/database-manifests/2026/07/20260727-145958-prawkonarazpl-pgsql-pgsql-seo-relation-v2-foundation-20260727.json
```

Rozmiar skompresowanego backupu: `10 389 483 B`. Kopia zastępowanych plików
aplikacji znajduje się na serwerze w:

```text
/tmp/prawkonaraz-relation-v2-foundation-backup-20260727150120
```

Migracja `2026_07_21_231000_add_question_relation_graph_v2_foundation` została
wykonana poprawnie. Liczniki danych V1 przed i po wdrożeniu pozostały identyczne:

| Element | Przed | Po |
|---|---:|---:|
| tematy | 101 | 101 |
| membershipy | 2 176 | 2 176 |
| relacje | 6 877 | 6 877 |

Potwierdzono obecność czterech nowych tabel, poprawne `ops:health-report`,
`ops:smoke-test`, HTTP 200 dla health checku i strony pytania oraz brak zmiany
publicznego selektora i komponentu V1.

## Wykonana procedura wdrożenia produkcyjnego

1. Wykonano backup bazy i zapisano identyfikator artefaktu.
2. Potwierdzono, że publiczny selektor V2 nie jest podłączony do requestu.
3. Uruchomiono migrację w oknie wdrożeniowym:

   ```bash
   php artisan migrate --force
   ```

4. Sprawdzono obecność czterech nowych tabel i kluczowych indeksów.
5. Uruchomiono test strony pytania oraz health checki.
6. Porównano liczbę tematów, membershipów i relacji przed i po; liczniki nie zmieniły się.

## Zrealizowany Etap B

Etap B wykonano produkcyjnie 27 lipca 2026 r. jako osobny, idempotentny backfill:

1. `primary:* → macro`, a poprawne `secondary:* → topic`,
2. obecne membershipy → `verified`, `is_primary=true`, `role=primary`,
3. `symetryczna → symmetric` z kwarantanną pozostałych nieznanych kierunków,
4. evidences z `reason`, `difference`, anchorów i metadata V1,
5. raport liczników przed/po i osobny post-write preview z zerowym delta.

Backfill nie jest częścią migracji schematu i nie może uruchomić się automatycznie podczas deployu.
Publiczny selektor pozostał w V1. Dla kolejnego etapu przygotowano wersjonowany
pakiet pilota „Zawracanie”: 72 pytania, 394 pary do ręcznej oceny i 14
technicznych negatywów kolizji ID. Negatywy zabezpieczają mapowanie encji i są
wyłączone z metryk semantycznych. Gold set nie jest jeszcze ukończony — wymaga
decyzji redakcyjnej dla 394 kandydatur przed scoringiem `shadow`.
