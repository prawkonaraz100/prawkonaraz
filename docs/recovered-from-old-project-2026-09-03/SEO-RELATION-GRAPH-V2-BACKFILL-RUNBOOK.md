# Graf relacji pytań V2 — runbook idempotentnego backfillu

Status: `PRODUCTION BACKFILL APPLIED AND VERIFIED — PUBLIC V1 UNCHANGED`

Data: `2026-07-27`

Komenda: `seo:backfill-question-relation-graph-v2`

## Cel

Etap B przenosi istniejące dane V1 do jawnego modelu V2 bez zmiany publicznego
selektora relacji. Backfill jest oddzielony od migracji schematu i domyślnie
działa wyłącznie w trybie odczytu.

Zakres wykonanego zapisu:

- poprawne `primary:*` klasyfikuje jako `macro`,
- poprawne `secondary:*` klasyfikuje jako `topic`,
- pojedynczy dotychczasowy membership oznacza jako główny i zweryfikowany,
- normalizuje kierunek `symetryczna` do `symmetric`,
- tworzy osobne evidences z `reason`, `difference`, obu anchorów i metadata V1,
- zachowuje pochodzenie oraz wersję backfillu w metadata.

Backfill nie tworzy rankingów, nie publikuje rekomendacji V2, nie zmienia
rolloutów i nie podłącza nowych tabel do publicznego requestu.

## Tryby komendy

### Preview — domyślny tryb bez zapisu

```bash
php artisan seo:backfill-question-relation-graph-v2 \
  --report=storage/app/reports/question-relation-graph-v2-backfill-preview.json \
  --json
```

Brak `--write` jest celowym zabezpieczeniem. Preview może zapisać wyłącznie
plik raportu JSON; nie aktualizuje tematów, membershipów, relacji ani evidences.

### Write — wymaga każdorazowo osobnej decyzji GO

```bash
php artisan seo:backfill-question-relation-graph-v2 \
  --write \
  --report=storage/app/reports/question-relation-graph-v2-backfill-write.json \
  --json
```

Trybu `--write` nie wolno uruchamiać tylko dlatego, że kod został wdrożony.
Wymaga zaakceptowanego raportu preview, aktualnego backupu i osobnej zgody.
Jedno zatwierdzone uruchomienie wykonano 27 lipca 2026 r.

## Quality gates

Komenda blokuje zapis, jeżeli wykryje co najmniej jeden z warunków:

- temat o nieprawidłowym kluczu lub hierarchii,
- więcej albo mniej niż jeden dotychczasowy membership dla pytania,
- opublikowane wyjaśnienie bez membershipu,
- naruszony inwariant wcześniej zbackfillowanego membershipu,
- nieznany kierunek relacji,
- duplikat klucza evidence dla tej samej relacji, typu i wersji backfillu.

Na PostgreSQL zapis jest wykonywany w jednej transakcji z advisory lockiem.
Przed mutacją komenda ponownie oblicza quality gates już po uzyskaniu blokady.

## Idempotencja

Wersja operacji: `v2-backfill-20260727-v1`.

- tematy i membershipy otrzymują znacznik wersji w metadata,
- evidence jest identyfikowane przez relację, typ i wersję,
- ponowne uruchomienie nie tworzy kolejnych rekordów,
- różniący się rekord evidence jest aktualizowany deterministycznie,
- raport po poprawnym powtórzeniu powinien pokazać zera w polach `would_*`.

## Procedura produkcyjnego preview

1. Sprawdzić status migracji fundamentu V2 i health checki.
2. Zapisać snapshot liczników oraz rozkład kierunków i stanów V2.
3. Uruchomić komendę bez `--write` i zachować raport JSON.
4. Ponownie odczytać te same liczniki.
5. Potwierdzić brak zmiany danych i poprawny wynik quality gates.
6. Uruchomić `ops:health-report` oraz `ops:smoke-test`.
7. Pobrać raport preview jako artefakt do decyzji `GO / poprawki / STOP`.

## Kryteria GO zastosowane przed zapisem

- `quality_gates.passed = true`,
- liczba tematów do klasyfikacji odpowiada istniejącej taksonomii,
- liczba membershipów odpowiada pełnemu pokryciu opublikowanych wyjaśnień,
- wszystkie kierunki są znane, a liczba normalizacji jest wyjaśniona,
- liczba desired evidences zgadza się z niepustymi polami źródłowymi,
- preview nie zmienił żadnego licznika ani stanu rekordu,
- aktualny backup jest potwierdzony,
- właściciel produktu zaakceptował osobne uruchomienie `--write`.

## Wynik produkcyjnego preview — 2026-07-27

Komendę uruchomiono na produkcji bez opcji `--write`. Quality gates przeszły
bez blockerów, a raport zawiera następujący plan:

| Element | Wynik preview |
|---|---:|
| tematy `macro` | 13 |
| tematy `topic` | 88 |
| tematy do klasyfikacji | 101 |
| membershipy do oznaczenia jako główne | 2 176 |
| kierunki `symetryczna` do normalizacji | 3 199 |
| istniejące evidences tej wersji | 0 |
| evidences do utworzenia | 19 819 |
| evidences `verified` | 3 824 |
| evidences `candidate` | 15 995 |
| blockery quality gates | 0 |

Rozkład planowanych evidences:

| Typ | Liczba |
|---|---:|
| `anchor_left_to_right` | 3 414 |
| `anchor_right_to_left` | 6 808 |
| `contrast` | 3 199 |
| `legacy_metadata` | 3 199 |
| `relation_reason` | 3 199 |

Checksumy pełnej zawartości czterech tabel przed i po preview były identyczne:

| Tabela | Rekordy | MD5 snapshotu przed i po |
|---|---:|---|
| `question_seo_topics` | 101 | `beaa09ba689eaefa26080944d239e994` |
| `question_seo_topic_memberships` | 2 176 | `4399749a830f1e187918e26f377dedbc` |
| `question_relations` | 6 877 | `b3653cbda82cf3895a5a175e4415871f` |
| `question_relation_evidences` | 0 | `ad1e41cebd43e64af1a28d4d70dc9e30` |

Raport produkcyjny:

```text
storage/app/reports/question-relation-graph-v2-backfill-preview-20260727.json
```

Kopia lokalna: `output/question-relation-graph-v2-backfill-preview-20260727.json`

SHA-256: `2C2141D141C2861C6913260042442AFB411D03CD3C44F8F9A7752FAB871F79F4`.

Po preview `ops:health-report` i `ops:smoke-test` zwróciły `OK`. Opcja
`--write` nie została uruchomiona.

## Wynik produkcyjnego zapisu — 2026-07-27

Bezpośrednio przed zapisem powtórzono preview i ponownie uzyskano 0 blockerów.
Następnie utworzono oraz sprawdzono świeży backup:

```text
backups/database/2026/07/20260727-161407-prawkonarazpl-pgsql-pgsql-seo-relation-v2-backfill-write-20260727.sql.gz
backups/database-manifests/2026/07/20260727-161407-prawkonarazpl-pgsql-pgsql-seo-relation-v2-backfill-write-20260727.json
```

| Właściwość backupu | Wartość |
|---|---|
| rozmiar | 10 391 973 B |
| test `gzip -t` | PASS |
| SHA-256 dumpa | `beab0471aae6fa7110cfa303118fe7bf29f551db62fd88473778a4b4fc428d84` |
| SHA-256 manifestu | `70339486be384aa2c344a3192a7e83fa71256f9f20a36ffb294a45f8ad4c9e55` |

Backfill wykonano w jednej transakcji z advisory lockiem PostgreSQL. Zastosowane
zmiany:

| Element | Zapisano |
|---|---:|
| klasyfikacje tematów | 101 |
| membershipy główne i zweryfikowane | 2 176 |
| normalizacje kierunku | 3 199 |
| utworzone evidences | 19 819 |
| zaktualizowane evidences | 0 |

Stan końcowy:

| Inwariant | Wynik |
|---|---:|
| `macro / published / legacy` | 13 |
| `topic / published / legacy` | 88 |
| `primary / verified` memberships | 2 176 |
| relacje `symmetric` | 6 877 |
| evidences `verified` | 3 824 |
| evidences `candidate` | 15 995 |
| zduplikowane główne membershipy | 0 |
| osierocone evidences | 0 |
| ranking runs | 0 |
| recommendations | 0 |
| rollouts | 0 |

Osobny preview po zapisie potwierdził idempotencję:

- `would_update topics = 0`,
- `would_update memberships = 0`,
- `would_normalize directions = 0`,
- `would_create evidences = 0`,
- `would_update evidences = 0`,
- `unchanged evidences = 19 819`,
- wszystkie quality gates przeszły.

Raporty produkcyjne:

```text
storage/app/reports/question-relation-graph-v2-backfill-prewrite-20260727.json
storage/app/reports/question-relation-graph-v2-backfill-write-20260727.json
storage/app/reports/question-relation-graph-v2-backfill-postwrite-preview-20260727.json
```

Po zapisie `ops:health-report`, `ops:smoke-test` oraz audyt sitemap zwróciły
`OK`. Strony pytań 99 i 2919 oraz sitemap zwracają HTTP 200. Publiczny selektor
pozostaje w V1, ponieważ tabele rankingów, rekomendacji i rolloutów są puste.

## Rollback

Preview nie wymaga rollbacku danych, ponieważ ich nie zmienia. Ewentualny plik
raportu można usunąć bez wpływu na aplikację.

Po wykonanym zapisie rollback funkcjonalny nadal polega na pozostawieniu
publicznego rolloutu w trybie `v1`. W razie konieczności przywrócenia danych
należy użyć zatwierdzonego backupu bazy; nie wolno wykonywać destrukcyjnego
`migrate:rollback` na produkcji.

## Walidacja kodu

- preview nie zmienia żadnej z czterech tabel wejściowych/wyjściowych,
- zapis testowy tworzy oczekiwane klasyfikacje i evidences,
- drugie uruchomienie daje zerowy delta i identyczny snapshot danych,
- nieznany kierunek blokuje całą operację,
- regresje fundamentu V2, publicznego selektora V1, strony pytania i audytu
  grafu pozostają zielone.
