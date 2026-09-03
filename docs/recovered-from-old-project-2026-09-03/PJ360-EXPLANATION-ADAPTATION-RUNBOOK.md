# Runbook: przygotowanie i prowadzenie adaptacji wyjasnien PJ360

Data: 2026-04-09  
Status: operacyjny runbook dla kolejnych przebiegow porownania i adaptacji

Dokument uzupelnia:

- `docs/PJ360-EXPLANATION-ADAPTATION-PLAN.md`

## Decyzja operacyjna po przebiegu 2026-04-09

Na tym checkpointcie:

- dalsza praca nad wyjasnieniami poza `PT` zostala zakonczona,
- pozostaly backlog obejmuje juz tylko `PT`,
- `PT` traktujemy jako kategorie tramwajowa i nie usuwamy jej z produktu,
- ale kolejka `PT` zostaje odlozona na pozniejszy etap,
- znane wyjatki eksperckie dla `PT` pozostaja bez automatycznego domkniecia:
  - `4104`
  - `4105`
  - `2649`
  - `4146`

Runbook ponizej zostaje aktualny, ale kolejne kroki nalezy uruchamiac tylko wtedy, gdy wracamy do pracy nad `PT`.

## 1. Cel runbooka

Ten runbook opisuje:

- jak odtworzyc skan PJ360,
- jak odswiezyc porownanie z nasza baza,
- jak zbudowac kolejki `Tier A / B / C / PT`,
- jak prowadzic review i publikacje bez mieszania etapow.

## 2. Warunki wstepne

Wymagane:

- dzialajaca lokalna baza PostgreSQL z aktualnym importem `gov.pl`,
- aktualny katalog pytan w naszej aplikacji,
- dostep do logowania PJ360,
- Python z paczkami:
  - `requests`
  - `beautifulsoup4`
  - `psycopg`

Zmienne srodowiskowe:

```powershell
$env:PJ360_EMAIL="..."
$env:PJ360_PASSWORD="..."
```

Opcjonalnie:

```powershell
$env:PJ360_COMPARE_DB_DSN="host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit"
```

## 3. Narzedzia

### 3.1 Skrypt skanu i porownania

Plik:

- `scripts/compare_pj360_catalog.py`

Zadanie:

- loguje sie do PJ360,
- skanuje liste i detail wszystkich pytan,
- zapisuje pytania, wyjasnienia i sekcje referencyjne,
- porownuje wszystko z nasza baza w `11` wspolnych kategoriach.

Wyjscie:

- `output/analysis/pj360-compare/external_questions.json`
- `output/analysis/pj360-compare/local_questions.json`
- `output/analysis/pj360-compare/comparison_report.json`

### 3.2 Skrypt buildera kolejek

Plik:

- `scripts/build_pj360_explanation_queues.py`

Zadanie:

- odbudowuje matching na bazie zapisanych artefaktow,
- klasyfikuje pytania do `Tier A / B / C / PT`,
- eksportuje kolejki do `json` i `csv`.

Wyjscie:

- `output/analysis/pj360-compare/queues/summary.json`
- `output/analysis/pj360-compare/queues/README.md`
- `output/analysis/pj360-compare/queues/tier-a-safe-auto.json`
- `output/analysis/pj360-compare/queues/tier-b-review.json`
- `output/analysis/pj360-compare/queues/tier-c-manual.json`
- `output/analysis/pj360-compare/queues/tier-pt-manual.json`
- oraz odpowiedniki `.csv`

### 3.3 Generator draftow Tier A

Plik:

- `scripts/generate_pj360_explanation_drafts.py`

Zadanie:

- bierze tylko `Tier A / safe auto`,
- buduje draft `review-only`,
- zapisuje draft, source summary i flagi jakosci,
- nie publikuje do runtime.

Wyjscie:

- `output/analysis/pj360-compare/drafts/tier-a-draft-packets.json`
- `output/analysis/pj360-compare/drafts/tier-a-draft-summary.json`
- `output/analysis/pj360-compare/drafts/tier-a-draft-packets.csv`

### 3.4 Walidator draftow Tier A

Plik:

- `scripts/validate_pj360_explanation_drafts.py`

Zadanie:

- waliduje drafty wygenerowane dla `Tier A`,
- zapisuje liste flag i summary dla przebiegu.

Wyjscie:

- `output/analysis/pj360-compare/drafts/validation/tier-a-draft-validation.json`
- `output/analysis/pj360-compare/drafts/validation/tier-a-draft-validation-summary.json`
- `output/analysis/pj360-compare/drafts/validation/tier-a-draft-validation.csv`

### 3.5 Eksporter paczek review

Plik:

- `scripts/export_pj360_review_packets.py`

Zadanie:

- buduje rozlaczne paczki pracy dla review,
- eksportuje:
  - `Tier B / high-risk`,
  - `Tier B / duplicate_or_overlap`,
  - `Tier A / flagged drafts`,
  - `Tier C / manual`,
  - `Tier PT / manual`,
- dodatkowo zapisuje paczki:
  - per reason,
  - per validation flag,
  - per kategoria.

Wyjscie:

- `output/analysis/pj360-compare/review-packets/summary.json`
- `output/analysis/pj360-compare/review-packets/README.md`
- `output/analysis/pj360-compare/review-packets/by-reason/*`
- `output/analysis/pj360-compare/review-packets/by-flag/*`
- `output/analysis/pj360-compare/review-packets/by-category/*`

### 3.6 Enricher decyzji dla high-risk

Plik:

- `scripts/suggest_pj360_high_risk_decisions.py`

Zadanie:

- bierze `Tier B / high-risk`,
- dociaga lokalne media z bazy,
- pokazuje wszystkie lokalne `gov_id` dla tego samego promptu,
- nadaje rekomendacje decyzji review.

Wyjscie:

- `output/analysis/pj360-compare/high-risk-review/high-risk-summary.json`
- `output/analysis/pj360-compare/high-risk-review/high-risk-enriched.json`
- `output/analysis/pj360-compare/high-risk-review/high-risk-enriched.csv`

### 3.7 Rozstrzyganie media disambiguation

Plik:

- `scripts/resolve_pj360_media_disambiguation.py`

Zadanie:

- bierze tylko przypadki `needs_media_disambiguation`,
- loguje sie do PJ360 i pobiera realne media z pytania,
- dla obrazow porownuje hash obrazu,
- dla wideo wycina pierwsza klatke z MP4 i porownuje ja z naszym lokalnym posterem,
- probuje zamknac prompt duplicate po medium zamiast po samym promptcie.

Wyjscie:

- `output/analysis/pj360-compare/media-disambiguation/media-disambiguation-summary.json`
- `output/analysis/pj360-compare/media-disambiguation/media-disambiguation-resolved.json`

### 3.8 Post-triage Tier B

Plik:

- `scripts/build_pj360_tier_b_post_triage.py`

Zadanie:

- sklada w jedna decyzje:
  - surowe `Tier B`,
  - review `answer_mismatch`,
  - media disambiguation,
- rozroznia:
  - rekordy, ktore maja juz poprawna referencje PJ360,
  - rekordy, ktore po redirect tracą referencje,
  - rekordy tylko do lekkiego review,
  - rekordy twardo manualne.

Wyjscie:

- `output/analysis/pj360-compare/post-triage/tier-b-post-triage-summary.json`
- `output/analysis/pj360-compare/post-triage/tier-b-post-triage-summary.md`
- paczki `json` i `csv` per decyzja w `output/analysis/pj360-compare/post-triage/`

### 3.9 Curated drafty dla bezpiecznej czesci Tier B

Plik:

- `scripts/generate_pj360_tier_b_curated_drafts.py`

Zadanie:

- bierze tylko:
  - `resolved_external_reference`
  - `reference_usable_after_answer_review`
- pobiera pelny rekord PJ360 po `site_question_id`,
- generuje drafty w tym samym formacie co `Tier A`,
- pozwala osobno QA-owac mala, bezpieczna paczke z `Tier B`.

Wyjscie:

- `output/analysis/pj360-compare/drafts/tier-b-curated-draft-packets.json`
- `output/analysis/pj360-compare/drafts/tier-b-curated-draft-summary.json`
- `output/analysis/pj360-compare/drafts/validation-tier-b-curated/*`

### 3.10 Staging draftow PJ360 do DB

Pliki:

- `scripts/stage_pj360_publish_candidates.ps1`
- `app/Support/QuestionExplanationDraftStagingService.php`

Komendy:

- `php artisan pj360:stage-explanation-drafts`
- `php artisan pj360:explanation-draft-summary`

Zadanie:

- kopiuje finalna kolejke `publish-candidates.json` do katalogu widocznego z Dockera,
- laduje drafty do `question_explanation_drafts`,
- mapuje lokalne rekordy pytan po `external_id + categories`,
- oznacza wpisy z istniejacym `questions.explanation`,
- nie publikuje nic do runtime.

Wyjscie:

- `output/analysis/pj360-compare/staging/pj360-explanation-draft-summary.json`
- `output/analysis/pj360-compare/staging/pj360-existing-local-explanations.json`

### 3.11 Preview i apply draftow do `questions.explanation`

Pliki:

- `scripts/apply_pj360_explanation_drafts.ps1`
- `app/Support/QuestionExplanationDraftApplyService.php`

Komenda:

- `php artisan pj360:apply-explanation-drafts`

Zadanie:

- bierze wpisy z `question_explanation_drafts`,
- liczy, ile rekordow pytan kwalifikuje sie do aktualizacji,
- domyslnie robi tylko preview,
- po `--write` zapisuje drafty do `questions.explanation`,
- bez `--overwrite-existing` chroni istniejace wyjasnienia.

Wyjscie:

- `output/analysis/pj360-compare/staging/pj360-apply-preview-summary.json`

## 4. Standardowy przebieg

### Krok 1 / odswiez skan PJ360 i raport porownania

```powershell
python scripts/compare_pj360_catalog.py
```

Po wykonaniu sprawdz:

- czy `comparison_report.json` zostal nadpisany,
- czy liczba pytan zewnetrznych wyglada sensownie,
- czy nie pojawil sie blad logowania lub ratelimit.

### Krok 2 / zbuduj kolejki adaptacji

```powershell
python scripts/build_pj360_explanation_queues.py
```

Po wykonaniu sprawdz:

- `summary.json`
- `README.md`
- liczby `Tier A / B / C / PT`

### Krok 3 / zarchiwizuj snapshot przebiegu

W jednym miejscu trzymaj:

- raport porownania,
- kolejki,
- date przebiegu,
- branch albo commit,
- notatke o stanie bazy `gov.pl`.

Przy kolejnym przebiegu nie porownuj "na pamiec", tylko do ostatniego snapshotu.

### Krok 4 / wygeneruj drafty dla Tier A

```powershell
python scripts/generate_pj360_explanation_drafts.py
```

Po wykonaniu sprawdz:

- `tier-a-draft-summary.json`
- liczbe `publish_ready_count`
- liste `quality_flag_counts`

### Krok 5 / zwaliduj drafty

```powershell
python scripts/validate_pj360_explanation_drafts.py
```

Wazne:

- walidator uruchamiaj po generatorze, sekwencyjnie,
- nie puszczaj obu skryptow rownolegle, bo walidator moze przeczytac starszy snapshot draftow.

Po wykonaniu sprawdz:

- `tier-a-draft-validation-summary.json`
- czy topowe flagi sa zgodne z oczekiwaniem

### Krok 6 / wyeksportuj paczki review

```powershell
python scripts/export_pj360_review_packets.py
```

Po wykonaniu sprawdz:

- `review-packets/summary.json`
- `review-packets/README.md`
- czy liczby sa spojne:
  - `Tier B / high-risk`
  - `Tier B / duplicate_or_overlap`
  - `Tier A / flagged drafts`
  - `Tier C / manual`
  - `Tier PT / manual`

### Krok 7 / wzbogac high-risk o rekomendacje decyzji

```powershell
python scripts/suggest_pj360_high_risk_decisions.py
```

Po wykonaniu sprawdz:

- `high-risk-review/high-risk-summary.json`
- czy liczby rozbijaja sie sensownie na:
  - `duplicate_prompt_same_answer_media`
  - `needs_media_disambiguation`
  - `usable_text_scope_conflict`
- `likely_editorial_answer_mismatch`
- `manual_answer_conflict`

### Krok 8 / rozstrzygnij media disambiguation

```powershell
python scripts/resolve_pj360_media_disambiguation.py
```

Po wykonaniu sprawdz:

- `media-disambiguation/media-disambiguation-summary.json`
- ile przypadkow zamknelo sie jako:
  - `resolved_by_exact_media_hash`
  - `likely_resolved_by_media_hash`
  - `resolved_by_exact_answer_signature`
  - `resolved_by_accepted_answer_signature`
  - `still_manual`
  - `external_media_missing`
  - `local_media_missing`

Na aktualnym snapshotcie wynik jest taki:

- `13` `resolved_by_exact_media_hash`
- `1` `likely_resolved_by_media_hash`
- `1` `resolved_by_exact_answer_signature`

To zamyka cala kolejke `needs_media_disambiguation` `15/15`, bez pozostalych przypadkow manualnych.

Wazny niuans:

- ten krok rozstrzyga, do ktorego lokalnego `gov_id` nalezy strona PJ360,
- ale nie oznacza automatycznie, ze pierwotne lokalne pytanie zachowuje referencje,
- to trzeba sprawdzic jeszcze w post-triage.

### Krok 9 / zbuduj post-triage Tier B

```powershell
python scripts/build_pj360_tier_b_post_triage.py
```

Po wykonaniu sprawdz:

- `post-triage/tier-b-post-triage-summary.json`
- `post-triage/resolved_external_reference.csv`
- `post-triage/manual_no_reference_after_redirect.csv`
- `post-triage/manual_only_answer_conflict.csv`

Na aktualnym snapshotcie wynik jest taki:

- `Tier B` lacznie: `393`
- `resolved_external_reference`: `15`
- `manual_no_reference_after_redirect`: `7`
- `manual_only_answer_conflict`: `3`
- `reference_usable_after_answer_review`: `6`
- `review_scope_conflict`: `9`
- `review_media_presence_only`: `3`
- `review_external_category_policy`: `1`
- `review_loose_prompt_only`: `11`
- `review_ambiguous_overlap`: `41`
- `review_overlap_only`: `297`

### Krok 10 / wygeneruj curated drafty Tier B

```powershell
python scripts/generate_pj360_tier_b_curated_drafts.py
python scripts/validate_pj360_explanation_drafts.py --drafts-path output/analysis/pj360-compare/drafts/tier-b-curated-draft-packets.json --output-dir output/analysis/pj360-compare/drafts/validation-tier-b-curated
```

Po wykonaniu sprawdz:

- `drafts/tier-b-curated-draft-summary.json`
- `drafts/validation-tier-b-curated/tier-a-draft-validation-summary.json`

Na aktualnym snapshotcie wynik jest taki:

- `21` draftow curated `Tier B`
- `21` clean po walidacji
- `0` dodatkowych flag

### Krok 11 / zbuduj kolejke kandydatow do publikacji

```powershell
python scripts/build_pj360_publish_candidate_queue.py
```

Po wykonaniu sprawdz:

- `publish-candidates/publish-candidates-summary.json`
- `publish-candidates/publish-candidates.csv`

Na aktualnym snapshotcie wynik jest taki:

- `2713` kandydatow do publikacji
- `2692` z `Tier A`
- `21` z curated `Tier B`

### Krok 12 / zaladuj kandydatow do bezpiecznego stagingu DB

Najbezpieczniejsza droga w lokalnym setupie Docker:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/stage_pj360_publish_candidates.ps1
```

Alternatywa reczna:

```powershell
Copy-Item output\analysis\pj360-compare\publish-candidates\publish-candidates.json storage\app\tmp\pj360-publish-candidates.json -Force
docker compose exec -T app php artisan pj360:stage-explanation-drafts storage/app/tmp/pj360-publish-candidates.json --reset
docker compose exec -T app php artisan pj360:explanation-draft-summary --json=storage/app/tmp/pj360-explanation-draft-summary.json
Copy-Item storage\app\tmp\pj360-explanation-draft-summary.json output\analysis\pj360-compare\staging\pj360-explanation-draft-summary.json -Force
```

Wazne:

- kontener `app` nie widzi hostowego `output/analysis`, dopoki nie skopiujemy pliku do `storage/app/tmp`,
- ten krok nie dotyka `questions.explanation`,
- to jest tylko staging review przed przyszla publikacja.

Na aktualnym snapshotcie wynik jest taki:

- `2713` wpisow w `question_explanation_drafts`
- `2713` ze statusem `staged`
- `0` `staging_conflict`
- `14263` lokalnych rekordow pytan w zasiegu
- `2` wpisy stagingowe z istniejacym lokalnym `questions.explanation`

### Krok 13 / zrob preview publikacji bez zapisu do runtime

Najwygodniej:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/apply_pj360_explanation_drafts.ps1
```

Do malej proby:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/apply_pj360_explanation_drafts.ps1 -Limit 25
```

Recznie:

```powershell
docker compose exec -T app php artisan pj360:apply-explanation-drafts --json=storage/app/tmp/pj360-apply-preview-summary.json
Copy-Item storage\app\tmp\pj360-apply-preview-summary.json output\analysis\pj360-compare\staging\pj360-apply-preview-summary.json -Force
```

Na aktualnym pelnym preview wynik jest taki:

- `2713` kandydatow
- `2711` wpisow w pelni stosowalnych
- `2` wpisy czesciowo stosowalne
- `0` wpisow bez targetow
- `14261` rekordow pytan do aktualizacji bez overwrite
- `2` rekordy pytan do pominięcia jako juz wypelnione
- wyjatki sa wypisane na koncu raportu i po `external_id`: `11402`, `13447`

### Krok 13a / zamknij wyjatki manualne

Jesli wyjatki maja sensowne rozstrzygniecie redakcyjne, najpierw zaktualizuj staging:

```powershell
docker compose exec -T app php artisan pj360:apply-exception-resolutions --json=storage/app/tmp/pj360-exception-resolutions-summary.json
```

Potem zastosuj je tylko dla tych `external_id`:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/apply_pj360_explanation_drafts.ps1 -Write -OverwriteExisting -ExternalId 11402 13447 -SummaryCopyPath output/analysis/pj360-compare/staging/pj360-exception-apply-summary.json
```

Aktualny stan po zamknieciu wyjatkow:

- `11402` zamkniete
- `13447` zamkniete
- oba drafty maja status `applied`
- oba pokrywaja po `11` lokalnych rekordow kategorii
- po tym kroku globalny preview nie ma juz wyjatkow

### Krok 14 / faktyczny apply do runtime

Ten krok wykonuj dopiero po review:

```powershell
docker compose exec -T app php artisan pj360:apply-explanation-drafts --write --json=storage/app/tmp/pj360-apply-write-summary.json
```

Jesli swiadomie chcesz nadpisac istniejace wyjasnienia:

```powershell
docker compose exec -T app php artisan pj360:apply-explanation-drafts --write --overwrite-existing --json=storage/app/tmp/pj360-apply-write-summary.json
```

No-go:

- nie uruchamiaj `--write` na pelnej paczce bez przejrzenia `2` wpisow z istniejacymi lokalnymi wyjasnieniami,
- nie uzywaj `--overwrite-existing`, dopoki te wyjatki nie zostana zamkniete recznie.

## 5. Kolejnosc pracy na kolejkach

### 5.1 Najpierw Tier B, nie Tier A

Na pierwszy rzut oka kusi, zeby od razu generowac `Tier A`.
Praktycznie lepiej najpierw potwierdzic, ze reguly review sa stabilne.

Rekomendowana kolejnosc:

1. `answer_mismatch`
2. `media_kind_mismatch`
3. `structure_scope_mismatch`
4. `category_mismatch`
5. `ambiguous_best_match`
6. `local_prompt_duplicate`
7. `prompt_overlap_not_selected`
8. `loose_prompt_only`

Powod:

- pierwsze cztery grupy maja najwyzsze ryzyko merytoryczne,
- ostatnie cztery to glownie ryzyko mapowania i duplikatow.

### 5.2 Potem Tier A

Dopiero po zamknieciu najgrozniejszych przypadkow review uruchamiamy generator draftow dla `Tier A`.

### 5.3 Na koncu Tier C i PT

To jest kolejka stricte redakcyjna.
Tu nie czekamy na lepszy matching, tylko piszemy tekst od zera.

## 6. Reguly review

Kazde pytanie z `Tier B` musi dostac jedna z decyzji:

- `approved_match`
- `manual_only`
- `needs_media_disambiguation`
- `wrong_external_reference`

Minimalna karta review powinna zawierac:

- `gov_id`
- prompt
- kategorie lokalne
- odpowiedz lokalna
- medium lokalne
- `site_question_id`
- link do PJ360
- odpowiedz z PJ360
- medium z PJ360
- powod trafienia do review
- decyzje redakcyjna

## 7. Reguly generowania draftow

Generator draftow dla `Tier A` powinien:

- czytac nasze pytanie i poprawna odpowiedz,
- czytac tekst wyjasnienia z PJ360 tylko jako material referencyjny,
- tworzyc nowy tekst w naszym formacie,
- nie kopiowac sekcji `Kodeks drogowy` ani `Znaki drogowe` doslownie,
- nie cytowac dlugich fragmentow ustawy,
- nie publikowac automatycznie do produkcji.

Minimalne pola wyjsciowe draftu:

- `gov_id`
- `question_id` jesli pracujemy juz na rekordach produkcyjnych
- `source_site_question_id`
- `draft_text`
- `quality_flags`
- `review_status`

## 8. Reguly QA

Kazdy draft przed publikacja powinien przejsc 5 kontroli:

1. `answer_consistency`
2. `media_consistency`
3. `length_guard`
4. `style_guard`
5. `source_similarity_guard`

Minimalny checklist:

- czy tekst wskazuje poprawna odpowiedz,
- czy nie odwoluje sie do elementu, ktorego nie ma na obrazie albo wideo,
- czy nie myli kategorii albo rodzaju sytuacji,
- czy nie jest zbyt dlugi,
- czy nie jest zbyt podobny do zrodla PJ360.

## 9. Proponowany rytm wdrozenia

### Faza 1

- odswiezenie skanu,
- odswiezenie kolejek,
- review najwyzszego ryzyka w `Tier B`.

### Faza 2

- implementacja generatora draftow,
- wygenerowanie draftow `Tier A`,
- review losowej probki z kazdej kategorii.

Stan na `2026-04-09`:

- generator i walidator sa juz wdrozone,
- pierwszy przebieg dal `2694` draftow,
- `2692` przeszlo walidacje bez flag,
- `2` wymaga dalszego review,
- pozostale flagi to:
  - `low_context_reference = 1`
  - `high_source_overlap = 1`.
- eksporter paczek review jest juz wdrozony,
- pierwszy przebieg paczek dal:
  - `77` pytan `Tier B / high-risk`,
  - `316` pytan `Tier B / duplicate_or_overlap`,
  - `2` oflagowane drafty `Tier A`,
  - `12` pytan `Tier C`,
  - `439` pytan `PT`.
- pierwszy przebieg high-risk enrichment dal:
  - `41` przypadkow `duplicate_prompt_same_answer_media`,
  - `15` przypadkow `needs_media_disambiguation`,
  - `9` przypadkow `usable_text_scope_conflict`,
  - `6` przypadkow `likely_editorial_answer_mismatch`,
  - `2` przypadki `manual_answer_conflict`,
  - `3` przypadki `review_media_presence_only`,
  - `1` przypadek `review_external_category_policy`.
- pierwszy przebieg media disambiguation dal:
  - `13` przypadkow `resolved_by_exact_media_hash`,
  - `1` przypadek `likely_resolved_by_media_hash`,
  - `1` przypadek `resolved_by_exact_answer_signature`.
- pierwszy przebieg post-triage `Tier B` dal:
  - `15` rekordow `resolved_external_reference`,
  - `7` rekordow `manual_no_reference_after_redirect`,
  - `3` rekordy `manual_only_answer_conflict`,
  - `6` rekordow `reference_usable_after_answer_review`.
- pierwszy przebieg curated draftow `Tier B` dal:
  - `21` draftow,
  - `21` clean po walidacji.
- pierwszy przebieg kolejki publikacyjnej dal:
  - `2713` kandydatow,
  - `2692` z `Tier A`,
  - `21` z curated `Tier B`.
- pierwszy przebieg stagingu DB dal:
  - `2713` wpisow `staged`,
  - `0` konfliktow,
  - `14263` lokalnych rekordow w zasiegu,
  - `2` wpisy z juz istniejacym lokalnym `questions.explanation`.
- pierwszy pelny preview apply dal:
  - `2713` kandydatow,
  - `2711` wpisow w pelni stosowalnych,
  - `2` wpisy czesciowo stosowalne,
  - `14261` rekordow pytan do aktualizacji bez overwrite,
  - `2` rekordy pytan do pominięcia jako juz wypelnione.
- po manualnym zamknieciu `11402` i `13447` pozostaly preview dal:
  - `2711` kandydatow,
  - `2711` wpisow w pelni stosowalnych,
  - `0` wyjatkow,
  - `14241` rekordow pytan do aktualizacji.

### Krok 15 / pelny apply pozostalej paczki

Po zamknieciu wyjatkow wykonany zostal pelny apply:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/apply_pj360_explanation_drafts.ps1 -Write -SummaryCopyPath output/analysis/pj360-compare/staging/pj360-apply-write-summary.json
```

Stan koncowy po tym kroku:

- `2713` draftow `applied`
- `0` draftow `staged`
- `14263` pytan z wypelnionym `questions.explanation`
- `0` rekordow `applied_with_skips`

Artefakty:

- `output/analysis/pj360-compare/staging/pj360-apply-write-summary.json`
- `output/analysis/pj360-compare/staging/pj360-post-write-state.json`

### Faza 3

- zamkniecie `Tier B`,
- manualne wyjasnienia `Tier C`,
- manualne wyjasnienia `PT`.

### Faza 4

- masowe wpisanie approved tekstow do `questions.explanation`,
- QA w runtime,
- potem dopiero wzbogacanie pytan o warstwe wizualna.

## 10. Kontrole koncowe przed publikacja

Przed kazdym wiekszym wsadem zatwierdzonych wyjasnien:

1. sprawdz liczbe rekordow do publikacji,
2. sprawdz losowa probe:
   - min. `10` pytan `boolean`
   - min. `10` pytan `single_choice`
   - min. `5` pytan obrazkowych
   - min. `5` pytan wideo
   - min. `1` probe per wspolna kategoria
3. sprawdz `Tryb Nauka` po blednej odpowiedzi,
4. sprawdz admina na przykladowym pytaniu,
5. dopiero wtedy publikuj paczke.

## 11. No-go rules

Nie wolno:

- publikowac `Tier B` bez decyzji review,
- publikowac `Tier C` i `PT` bez tekstu napisanego od zera,
- traktowac `site_question_id` z PJ360 jako `government_question_id`,
- ufac promptowi, jesli pytanie nalezy do prompt duplicate,
- uznawac loose prompt match za pelny safe auto.

## 12. Minimalny zestaw komend

```powershell
python scripts/compare_pj360_catalog.py
python scripts/build_pj360_explanation_queues.py
python scripts/generate_pj360_explanation_drafts.py
python scripts/validate_pj360_explanation_drafts.py
python scripts/export_pj360_review_packets.py
python scripts/suggest_pj360_high_risk_decisions.py
python scripts/resolve_pj360_media_disambiguation.py
python scripts/build_pj360_tier_b_post_triage.py
python scripts/generate_pj360_tier_b_curated_drafts.py
python scripts/build_pj360_publish_candidate_queue.py
Get-Content output/analysis/pj360-compare/queues/summary.json
Get-Content output/analysis/pj360-compare/queues/README.md
Get-Content output/analysis/pj360-compare/drafts/tier-a-draft-summary.json
Get-Content output/analysis/pj360-compare/drafts/validation/tier-a-draft-validation-summary.json
Get-Content output/analysis/pj360-compare/review-packets/summary.json
Get-Content output/analysis/pj360-compare/high-risk-review/high-risk-summary.json
Get-Content output/analysis/pj360-compare/media-disambiguation/media-disambiguation-summary.json
Get-Content output/analysis/pj360-compare/post-triage/tier-b-post-triage-summary.json
Get-Content output/analysis/pj360-compare/drafts/tier-b-curated-draft-summary.json
Get-Content output/analysis/pj360-compare/publish-candidates/publish-candidates-summary.json
```

To jest podstawowy punkt startu dla kazdego kolejnego przebiegu.
