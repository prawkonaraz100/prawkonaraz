# Import shadow rankingu do niepublicznego runu V2

Status: `PRODUCTION SHADOW + INTERNAL PREVIEW/MONITORING ACTIVE / PUBLIC ROLLOUT OFF`

Ostatnia aktualizacja: 2026-07-28
Zakres: pilot `secondary:zawracanie`, bez zmiany publicznego rendereru V1

## Aktualny stan po korekcie redakcyjnej — 2026-07-28

Aktywny jest run V2 nr `2`, utworzony z wersjonowanego artefaktu
`zawracanie-v2-editorial-exclusions`. Zastąpił on atomowo wcześniejszy run nr
1 w tym samym rolloutcie nr 1, który nadal ma `mode=shadow` oraz ekspozycję
`0%`. Nie nastąpiła zmiana publicznego HTML ani selektora V1.

| Element | Stan aktywnego runu nr 2 |
|---|---:|
| źródła `core` | 29 |
| wybrane rekomendacje | 435 |
| `scope=direct` | 181 |
| fallback `same_subtopic` | 254 |
| rekomendacje na źródło | dokładnie 15 |
| SHA-256 rankingu | `21b3227156752dadf66e3ed7dc48361925ed53ef688ea17bc7557663549533c4` |
| audyt i monitor po imporcie | `ok`, 0 błędów, 0 ostrzeżeń |

Przed tą zmianą utworzono i zweryfikowano backup bazy:

```text
backups/database/2026/07/20260727-232004-prawkonarazpl-pgsql-pgsql-seo-relation-shadow-zawracanie-v2-editorial-20260728.sql.gz
```

Poniższe sekcje zachowują historię pierwszego importu runu nr 1. Parametry
`zawracanie-v1` i liczby 184/251 dotyczą tego historycznego snapshotu, nie
aktualnego runu nr 2.

## Cel

Komenda `seo:import-question-relation-shadow-run` mapuje zamrożony ranking
JSONL do istniejących tabel:

- `question_relation_ranking_runs`,
- `question_relation_recommendations`.

Nie tworzy ani nie modyfikuje `question_relation_rollouts`. Utworzony run ma
status `validated`, `published_at = null` i nie jest czytany przez publiczny
selektor V1.

## Granica klastra

Artefakt shadow zawiera ranking dla 72 pytań, ponieważ podczas kalibracji
sprawdzaliśmy także pytania wspierające. Run klastra „Zawracanie” może jednak
mieć źródła wyłącznie w swoim rdzeniu:

| Element | Liczba |
|---|---:|
| wszystkie rankingi źródłowe w artefakcie | 72 |
| źródła `core` importowane do runu | 29 |
| źródła `supporting_candidate` pomijane | 43 |
| rekomendacje dla rdzenia | 435 |
| rdzeń bezpośredni | 184 |
| fallback hubowy | 251 |
| rekomendacje per źródło rdzeniowe | 15 |

Pytania wspierające nadal mogą być targetami. Adapter wymaga, aby wszystkie
29 źródeł miało zweryfikowany główny membership w
`secondary:zawracanie`.

## Preview

```bash
php artisan seo:import-question-relation-shadow-run \
  --ranking=resources/seo/question-relation-shadow/zawracanie-v1/artifacts/shadow-ranking.jsonl \
  --manifest=resources/seo/question-relation-shadow/zawracanie-v1/artifacts/shadow-manifest.json \
  --evaluation=resources/seo/question-relation-shadow/zawracanie-v1/evaluation/shadow-evaluation.json \
  --topic-key=secondary:zawracanie \
  --report=output/question-relation-shadow-run-preview.json
```

Bez `--write` komenda jest tylko do odczytu. Weryfikuje:

- SHA-256 rankingu względem manifestu i ewaluacji,
- zgodność wersji datasetu i generatora,
- brak użycia gold labeli podczas selekcji,
- pozytywne quality gates shadow i brak zgody na publiczny rollout,
- dokładnie jeden opublikowany topic klastra,
- jedno opublikowane wyjaśnienie dla każdego wymaganego `external_id`,
- główne, zweryfikowane membershipy wszystkich źródeł `core`,
- minimum 15 ciągłych pozycji per źródło,
- brak duplikatu runu albo zgodność istniejącego niezmiennego snapshotu.

## Zapis niepubliczny

Dopiero jawne `--write` tworzy run i rekomendacje:

```bash
php artisan seo:import-question-relation-shadow-run \
  --ranking=resources/seo/question-relation-shadow/zawracanie-v1/artifacts/shadow-ranking.jsonl \
  --manifest=resources/seo/question-relation-shadow/zawracanie-v1/artifacts/shadow-manifest.json \
  --evaluation=resources/seo/question-relation-shadow/zawracanie-v1/evaluation/shadow-evaluation.json \
  --topic-key=secondary:zawracanie \
  --report=output/question-relation-shadow-run-write.json \
  --write
```

Idempotency key składa się z topicu, wersji wejścia zawierającej skrót
rankingu, wersji generatora i pełnego SHA-256 konfiguracji. PostgreSQL używa
blokady advisory wewnątrz transakcji. Istniejący, identyczny run daje zerowy
delta; różnica w rekomendacjach blokuje zapis zamiast modyfikować snapshot.

Mapowanie warstw:

| Shadow | V2 recommendation |
|---|---|
| `direct_score` | `scope=direct`, `group_key=closest` |
| `hub_fallback / same_secondary` | `scope=same_subtopic`, `group_key=context` |
| `hub_fallback / same_primary` | `scope=same_topic`, `group_key=context` |

Typ relacji nie steruje grupą UI, ponieważ jego trafność jest nadal za niska.
Pełne dane diagnostyczne pozostają w `score_components`.

## Wynik lokalnego preview

Bieżąca baza kontenera `local` nie jest kopią produkcji: zawiera 0 topiców i
16 testowych wyjaśnień. Preview poprawnie wykrył:

- brak `secondary:zawracanie`,
- 0/49 wymaganych opublikowanych wyjaśnień,
- 435 niezamapowanych rekomendacji.

Quality gates zwróciły `passed=false`; nie utworzono runu, rekomendacji ani
rolloutu. To oczekiwane zabezpieczenie, a nie błąd artefaktu.

## Produkcyjny zapis — 2026-07-27

Przed zapisem utworzono i zweryfikowano świeży backup PostgreSQL z manifestem:

```text
backups/database/2026/07/20260727-203725-prawkonarazpl-pgsql-pgsql-seo-relation-shadow-validated-20260727.sql.gz
```

Produkcyjny preview przeszedł wszystkie quality gates, a następnie `--write`
utworzył jeden niepubliczny snapshot:

| Element | Wynik |
|---|---:|
| ID runu | 1 |
| status runu | `validated` |
| `published_at` | `null` |
| źródła `core` | 29 |
| rekomendacje | 435 |
| `scope=direct` | 184 |
| `scope=same_subtopic` | 251 |
| rekomendacje na źródło | dokładnie 15 |
| rekordy rolloutów | 0 (stan po zapisie runu, przed aktywacją shadow) |

Wśród rekomendacji `direct` 98 wskazuje istniejący `question_relation_id`, a
86 pozostaje bez niego. To zamierzony, zachowany w snapshotcie wynik adaptera:
relacja i jej dowody są nadal dostępne w `score_components`, lecz importer nie
tworzy automatycznie nowych rekordów `question_relations`.

Drugi `--write` zakończył się jako `write_idempotent` z zerowym delta. Po
zapisie `ops:health-report`, `ops:smoke-test`, `/login` i `/api/v1/health`
zwróciły sukces. Publiczny selektor nadal działa wyłącznie w V1.

## Aktywacja produkcyjnego odczytu shadow — 2026-07-27

Po wdrożeniu warstwy odczytu wykonano osobny preview konfiguracji i świeży
backup PostgreSQL:

```text
backups/database/2026/07/20260727-212112-prawkonarazpl-pgsql-pgsql-seo-v2-shadow-mode-enable-20260727.sql.gz
```

Następnie utworzono rollout nr 1 dla runu nr 1 jako `mode=shadow` z ekspozycją
`0`. Powtórny zapis konfiguracji był idempotentny. Obie flagi środowiskowe są
włączone: `QUESTION_RELATIONS_V2_ENABLED=true` i
`QUESTION_RELATIONS_V2_SHADOW_ENABLED=true`.

Audyt po aktywacji ma status `ok`: porównuje 29 źródeł, 435 linków V1 i 435
linków V2, z 201 wspólnymi pozycjami i 0 błędów. Dla pytania 352 zestaw V1 ma
ten sam skrót przed i po aktywacji, a resolver shadow rozwiązał 15/15 targetów.
Health, smoke, endpoint pytania, `/login` i `/api/v1/health` zwróciły sukces.
SSR nie otrzymuje markera V2 ani zestawu V2.

## Testy i kolejny krok

Test integracyjny na izolowanym SQLite potwierdza:

- preview bez mutacji,
- transakcyjny zapis runu `validated`,
- poprawne powiązanie z istniejącą relacją, gdy jest dostępna,
- 15 rekomendacji i brak rolloutu,
- idempotentny drugi zapis,
- blokadę artefaktu po zmianie pliku bez aktualizacji manifestu.

28 lipca 2026 wdrożono chroniony ekran `admin/relacje-v2-shadow` oraz komendę
`seo:monitor-question-relation-v2-shadow`. Istnieje także czasowy, podpisany
podgląd pojedynczego URL-a generowany przez
`seo:make-question-relation-v2-preview-url`; jest `noindex`, nie zmienia
zwykłego SSR V1 i nie jest publicznym canary. Monitor ma na produkcji status
`ok` (1 rollout, 0 błędów, 0 ostrzeżeń) i jest planowany codziennie o `04:00`,
gdy obie flagi shadow są aktywne.

Następny krok redakcyjny to przegląd różnic w panelu i audyt całego klastra
„Zawracanie”. Publiczne canary i `mode=v2` pozostają osobną decyzją; V1 nadal
jest jedynym rendererem.
