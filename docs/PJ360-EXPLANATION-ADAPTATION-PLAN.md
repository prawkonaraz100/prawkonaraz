# Plan adaptacji wyjasnien PJ360 do naszej bazy pytan

Data: 2026-04-09  
Status: plan wykonawczy po pelnym skanie bazy pytan i wyjasnien `prawo-jazdy-360`

## Status operacyjny po przebiegu 2026-04-09

Na koniec tego etapu:

- wszystkie kategorie poza `PT` zostaly domkniete,
- pozostaly backlog wyjasnien dotyczy juz tylko `PT`,
- `PT` pozostaje pelnoprawna kategoria tramwajowa, ale dalsza redakcja tej kolejki zostala swiadomie odlozona na pozniej,
- znane wyjatki zrodlowe dla `PT`, ktorych nie wolno zamykac automatem, to:
  - `4104`
  - `4105`
  - `2649`
  - `4146`

To oznacza, ze ten dokument nadal opisuje caly pipeline, ale biezacy checkpoint projektu jest formalnie zakonczony dla zakresu `non-PT`.

## 1. Cel

Zbudowac bezpieczny proces uzupelniania `questions.explanation` w naszej bazie pytan przy wykorzystaniu `prawo-jazdy-360` jako materialu referencyjnego, ale bez slepego kopiowania tresci i bez ryzyka przypisania wyjasnienia do zlego pytania.

Docelowo chcemy, zeby kazde pytanie mialo:

- poprawna tresc pytania,
- poprawna odpowiedz,
- zgodne medium glowne `image` albo `video`,
- krotkie i didaktyczne wyjasnienie w naszym formacie,
- opcjonalnie warstwe wizualna w naszym systemie `question_explanation_assets` i `question_explanation_annotations`.

## 2. Stan wejsciowy

### 2.1 Co mamy po stronie PJ360

Skan `prawo-jazdy-360` dal nam:

- `2916` stron pytan,
- `2916` pytan z tekstem wyjasnienia eksperta,
- `2283` pytania z dodatkowymi sekcjami typu `Znaki drogowe` albo `Kodeks drogowy`,
- `1638` pytan z wideo wyjasnienia.

Artefakty:

- `output/analysis/pj360-compare/external_questions.json`
- `output/analysis/pj360-compare/comparison_report.json`

### 2.2 Co mamy po naszej stronie

Po reimportcie `gov.pl` mamy:

- `3524` unikalne pytania zrodlowe `gov_id` lacznie we wszystkich kategoriach,
- `3099` unikalnych pytan zrodlowych w `11` kategoriach wspolnych z PJ360:
  - `A, AM, A1, A2, B, B1, C, C1, D, D1, T`
- `439` pytan w kategorii `PT`,
- `14` pytan `PT`, ktore nakladaja sie `gov_id` ze wspolnymi kategoriami,
- runtime gotowy na:
  - krotkie glowne wyjasnienie w `questions.explanation`,
  - jedna karte odpowiedzi z obrazem pomocniczym,
  - adnotacje na obrazie lub stopklatce wideo.

Wniosek:

- produkt jest gotowy na nasz zredagowany tekst wyjasnienia,
- produkt nie jest gotowy na import `1:1` calej wielosekcyjnej struktury PJ360.

## 3. Najwazniejsze ustalenia z porownania

### 3.1 Pokrycie pytan

W `11` wspolnych kategoriach:

- `3076` naszych pytan ma exact prompt overlap z PJ360,
- `3087` naszych pytan ma przynajmniej loose prompt overlap z PJ360,
- tylko `12` pytan w tych kategoriach nie ma zadnego sensownego odpowiednika nawet po luzniejszej normalizacji promptu.

To oznacza, ze sama baza referencyjna PJ360 pokrywa praktycznie caly wspolny katalog pytan.

### 3.2 Rzecz najwazniejsza: prompt alone is not safe

Nie wolno adaptowac wyjasnien tylko po tresci pytania.

Powody:

- `site_question_id` z PJ360 nie odpowiada naszemu `government_question_id`,
- po naszej stronie sa `138` duplikowane prompty obejmujace `362` wiersze pytan,
- w oficjalnym `gov.pl` sa pytania o identycznym promptcie, ale z innym medium i inna poprawna odpowiedzia.

Krytyczny przyklad:

- prompt: `Czy znak ten, wraz z tabliczka, zabrania zatrzymywania sie przed nim po tej stronie drogi, po ktorej sie znajduje?`
- w `gov.pl` wystepuja co najmniej dwa rekordy:
  - `11150` z odpowiedzia `N`,
  - `11151` z odpowiedzia `T`
- roznia sie medium, mimo identycznego promptu.

Wniosek:

- dopasowanie po samym promptcie moze przypisac wyjasnienie do zlego pytania,
- przy duplikowanych promptach musimy wymagac review albo dodatkowego rozroznienia po medium.

### 3.3 Glowny problem nie lezy w odpowiedziach

Po ostrym porownaniu rozjazdy wygladaja tak:

- `8` mismatchy odpowiedzi,
- `6` mismatchow kategorii,
- `9` mismatchow `PODSTAWOWY / SPECJALISTYCZNY`,
- `3` mismatchy rodzaju medium,
- `57` dopasowan niejednoznacznych,
- ale az `362` lokalne wiersze wpadaja do review przez duplikowane prompty.

Najwazniejszy blocker to nie `answer mismatch`, tylko prompt duplication i prompt collision.

## 4. Czego nie robimy

Nie robimy:

- bezposredniego importu tekstow PJ360 do `questions.explanation`,
- dopasowania po promptcie bez kolejki review,
- kopiowania `Kodeks drogowy` / `Znaki drogowe` `1:1` do runtime,
- publikacji automatycznej dla pytan z `Tier B`, `Tier C` i `PT`,
- slepego mieszania danych z roznych zrodel w jednym polu bez sladu pochodzenia.

## 5. Co robimy zamiast tego

Traktujemy PJ360 jako:

- korpus referencyjny,
- material do adaptacji,
- zrodlo inspiracji do zredagowania naszego, krotszego i bardziej produktowego wyjasnienia.

Docelowy przeplyw:

1. pobieramy i wersjonujemy korpus referencyjny,
2. budujemy kolejki bezpieczne i ryzykowne,
3. generujemy drafty tylko tam, gdzie match jest bezpieczny,
4. reviewujemy wszystkie przypadki niejednoznaczne,
5. publikujemy tylko finalny, nasz tekst do `questions.explanation`.

## 6. Kolejki adaptacji

Na podstawie skanu i dodatkowego buildera kolejek mamy teraz cztery zbiory pracy.

Artefakty:

- `output/analysis/pj360-compare/queues/summary.json`
- `output/analysis/pj360-compare/queues/tier-a-safe-auto.json`
- `output/analysis/pj360-compare/queues/tier-b-review.json`
- `output/analysis/pj360-compare/queues/tier-c-manual.json`
- `output/analysis/pj360-compare/queues/tier-pt-manual.json`

### 6.1 Tier A / safe auto

Liczba: `2694`

To sa pytania, dla ktorych:

- match jest `exact_prompt`,
- nie ma remisu w wyborze najlepszego dopasowania,
- odpowiedz jest zgodna,
- kategorie sa zgodne,
- `PODSTAWOWY / SPECJALISTYCZNY` jest zgodny,
- rodzaj medium jest zgodny,
- prompt jest unikalny po naszej stronie,
- prompt jest unikalny po stronie PJ360.

To jest jedyna pula, z ktorej wolno budowac drafty bez wczesniejszego manualnego review.

### 6.2 Tier B / review

Liczba: `393`

To sa pytania, ktore maja material referencyjny w PJ360, ale wymagaja review z co najmniej jednego powodu:

- `local_prompt_duplicate`
- `prompt_overlap_not_selected`
- `ambiguous_best_match`
- `answer_mismatch`
- `category_mismatch`
- `structure_scope_mismatch`
- `media_kind_mismatch`
- `loose_prompt_only`

To jest kolejka do manualnego sprawdzenia dopasowania przed jakakolwiek adaptacja tekstu.

### 6.3 Tier C / manual

Liczba: `12`

To sa pytania ze wspolnych `11` kategorii, ktore nie maja sensownego odpowiednika w PJ360 nawet po luzniejszej normalizacji promptu.

Dla nich trzeba napisac wyjasnienie od zera.

### 6.4 Tier PT / manual

Liczba: `439`

PJ360 nie daje nam pelnego korpusu dla `PT`, wiec ta kategoria wchodzi od razu do kolejki manualnej.

## 7. Jak powinien wygladac finalny tekst wyjasnienia

Nasze wyjasnienie nie powinno byc kopia PJ360.

Powinno byc:

- krotkie: zwykle `2-4` zdania,
- konkretne,
- zgodne z poprawna odpowiedzia,
- zgodne z widocznym medium,
- napisane prostym jezykiem kursanta,
- gotowe do wyswietlenia w obecnym runtime.

Rekomendowany format:

1. zdanie werdyktu:
   - dlaczego odpowiedz `Tak / Nie / A / B / C` jest poprawna,
2. zdanie obserwacyjne:
   - co na obrazie, filmie albo w sytuacji to potwierdza,
3. zdanie zasady:
   - jaka regula ruchu lub praktyczna zasada za tym stoi,
4. opcjonalnie jedno zdanie o typowej pulapce.

Nie robimy:

- dlugich cytatow z ustawy,
- lania wody,
- tekstu, ktory tylko powtarza odpowiedz bez wyjasnienia,
- zbyt bliskiego parafrazowania zewnetrznego zrodla.

## 8. Rekomendowana architektura danych

### 8.1 Co mozemy wykorzystac od razu

Na teraz finalny tekst mozemy wpisywac do:

- `questions.explanation`

Warstwa wizualna zostaje obok:

- `question_explanation_assets`
- `question_explanation_annotations`

### 8.2 Czego brakuje do pracy na skale

Ten brak jest juz zamkniety.

Mamy wdrozony staging danych referencyjnych w DB:

1. tabele `question_explanation_drafts`,
2. komendy:
   - `pj360:stage-explanation-drafts`
   - `pj360:explanation-draft-summary`
3. helper operatorski:
   - `scripts/stage_pj360_publish_candidates.ps1`

Ten staging jest celowo odseparowany od `questions.explanation`.

Czyli:

- mozemy bezpiecznie zaladowac kandydatow do review,
- mozemy policzyc, ile lokalnych rekordow obejmie publikacja,
- widzimy od razu rekordy, ktore maja juz lokalne wyjasnienia,
- nie dotykamy runtime ani finalnego pola produkcyjnego.

### 8.3 Aktualny stan stagingu

Po pierwszym pelnym zaladowaniu kolejki publikacyjnej do DB:

- `2713` draftow zostalo zapisanych w `question_explanation_drafts`,
- `2713` ma status `staged`,
- `0` ma status `staging_conflict`,
- staging obejmuje `14263` lokalne rekordy pytan,
- `2` wpisy stagingowe wskazuja na istniejace lokalne `questions.explanation` i wymagaja ostroznego review przed publikacja.

Artefakty:

- `output/analysis/pj360-compare/staging/pj360-explanation-draft-summary.json`
- `output/analysis/pj360-compare/staging/pj360-existing-local-explanations.json`

### 8.4 Preview publikacji bez zapisu do runtime

Mamy juz tez gotowy mechanizm preview/apply z tabeli stagingowej do `questions.explanation`.

Narzedzia:

- komenda `pj360:apply-explanation-drafts`
- helper `scripts/apply_pj360_explanation_drafts.ps1`

Wazne zasady:

- bez `--write` komenda robi tylko preview,
- bez `--overwrite-existing` nie nadpisuje istniejacych wyjasnien,
- mozna pracowac paczkami po `--limit` albo po konkretnym `--external-id`.

Na aktualnym pelnym preview:

- `2713` wpisow stagingu zostalo przeanalizowanych,
- `2711` jest w pelni stosowalnych,
- `2` sa czesciowo stosowalne przez juz istniejace lokalne `questions.explanation`,
- `0` wpisow jest bez targetu,
- `14261` lokalnych rekordow pytan kwalifikuje sie do aktualizacji bez overwrite istniejacych wyjasnien,
- `2` lokalne rekordy zostalyby pominiete jako juz wypelnione.

Artefakt:

- `output/analysis/pj360-compare/staging/pj360-apply-preview-summary.json`

### 8.5 Zamkniete wyjatki manualne

Dwa wyjatki z pierwszego preview zostaly juz recznie rozstrzygniete i zastosowane:

- `11402`
- `13447`

Dla obu:

- przygotowano recznie lepszy tekst produktu,
- zaktualizowano staging draftu,
- zastosowano `WRITE + OVERWRITE` tylko dla tych dwoch `external_id`,
- wszystkie `11 + 11` lokalnych rekordow kategorii dostaly finalne wyjasnienie.

Artefakty:

- `storage/app/manual/pj360-exception-resolutions.json`
- `output/analysis/pj360-compare/staging/pj360-exception-resolutions-summary.json`
- `output/analysis/pj360-compare/staging/pj360-exception-final-state.json`

Po zamknieciu tych dwoch wyjatkow nowy globalny preview dla pozostalej paczki daje:

- `2711` kandydatow,
- `2711` wpisow w pelni stosowalnych,
- `0` wyjatkow,
- `14241` rekordow pytan do aktualizacji.

Artefakt:

- `output/analysis/pj360-compare/staging/pj360-apply-preview-summary-post-exceptions.json`

### 8.6 Wykonany pelny apply do runtime

Pelny apply dla pozostalej paczki zostal juz wykonany bez `overwrite` dla innych rekordow, bo wyjatki byly zamkniete wczesniej recznie.

Stan koncowy:

- `2713` draftow ma status `applied`,
- `0` draftow zostalo w `staged`,
- `14263` rekordy pytan maja wypelnione `questions.explanation`,
- `0` rekordow jest w `applied_with_skips`.

Artefakty:

- `output/analysis/pj360-compare/staging/pj360-apply-write-summary.json`
- `output/analysis/pj360-compare/staging/pj360-post-write-state.json`

## 9. Plan wykonawczy

### Etap 0 / freeze i snapshot

- nie edytujemy masowo `questions.explanation` recznie w wielu miejscach naraz,
- trzymamy jeden aktualny snapshot PJ360,
- trzymamy jeden aktualny snapshot kolejek.

Definition of done:

- artefakty `external_questions.json`, `comparison_report.json` i `queues/*` sa aktualne.

### Etap 1 / review warstwy dopasowan

Cel:

- potwierdzic, ze `Tier A` jest rzeczywiscie bezpieczny,
- rozbic `Tier B` na mniejsze podkolejki.

Priorytet review:

1. `answer_mismatch`
2. `media_kind_mismatch`
3. `structure_scope_mismatch`
4. `category_mismatch`
5. `ambiguous_best_match`
6. `local_prompt_duplicate`
7. `prompt_overlap_not_selected`
8. `loose_prompt_only`

Definition of done:

- kazde pytanie z `Tier B` ma decyzje:
  - `mozna przypisac do PJ360 X`
  - `trzeba pisac od zera`
  - `trzeba rozstrzygnac po medium`

### Etap 2 / generator draftow dla Tier A

Budujemy osobne narzedzie, ktore:

- bierze nasze pytanie,
- bierze referencje z PJ360,
- generuje nasz, krotszy tekst wyjasnienia,
- nie kopiuje zrodla 1:1,
- zapisuje draft do stagingu albo eksportu review.

Generator nie powinien od razu publikowac do produkcji.

Definition of done:

- mamy drafty dla `Tier A`,
- kazdy draft ma link do pytania i do referencji z PJ360,
- da sie przejrzec je paczkami per kategoria.

### Etap 3 / manual review i redakcja

Zakres:

- review `Tier A`,
- rozstrzygniecie `Tier B`,
- reczne napisanie `Tier C`,
- reczne napisanie `PT`.

Definition of done:

- kazde pytanie ma status:
  - `approved`
  - `rewrite_needed`
  - `manual_only`

### Etap 4 / QA przed publikacja

Minimalne kontrole:

- czy tekst zgadza sie z poprawna odpowiedzia,
- czy tekst nie przeczy medium,
- czy tekst nie odwoluje sie do elementu, ktorego nie ma na obrazie lub wideo,
- czy tekst nie jest za dlugi,
- czy tekst nie jest zbyt podobny do zrodla,
- czy pytanie nie nalezy do prompt duplicate bez rozstrzygniecia.

Definition of done:

- zero znanych sprzecznosci `answer / media / text`,
- zero publikacji z nierozstrzygnietego `Tier B`.

### Etap 5 / publikacja i dalsze wzbogacanie

Po zatwierdzeniu tekstow:

- wpisujemy finalny tekst do `questions.explanation`,
- dla trudniejszych pytan dodajemy warstwe wizualna:
  - obraz pomocniczy,
  - adnotacje,
  - stopklatke wideo.

## 10. Narzedzia

### Juz gotowe

- `scripts/compare_pj360_catalog.py`
  - skan bazy pytan i wyjasnien PJ360,
  - porownanie z nasza baza `gov.pl`.

- `scripts/build_pj360_explanation_queues.py`
  - budowa kolejek `Tier A / B / C / PT`,
  - eksport do `json` i `csv`,
  - policzenie dominant blockers.

- `scripts/generate_pj360_explanation_drafts.py`
  - generator draftow review-only dla `Tier A`,
  - zapis paczek draftow do osobnego stagingu,
  - nie publikuje nic do `questions.explanation`.

- `scripts/validate_pj360_explanation_drafts.py`
  - walidator draftow z `Tier A`,
  - sprawdza podstawowe flagi jakosci i overlap ze zrodlem.

- `scripts/export_pj360_review_packets.py`
  - buduje rozlaczne paczki review:
    - `Tier B / high-risk`,
    - `Tier B / duplicate_or_overlap`,
    - `Tier A / flagged drafts`,
    - `Tier C`,
    - `Tier PT`.

- `scripts/suggest_pj360_high_risk_decisions.py`
  - wzbogaca `Tier B / high-risk` o lokalne media, prompt groups i rekomendacje decyzji review.

- `scripts/resolve_pj360_media_disambiguation.py`
  - bierze tylko przypadki `needs_media_disambiguation`,
  - porownuje realne media PJ360 z naszymi lokalnymi obrazami albo posterami,
  - uzywa hashy obrazu i pierwszej klatki z MP4, zeby rozstrzygnac prompt duplicate po medium.

- `scripts/build_pj360_tier_b_post_triage.py`
  - sklada w jeden stan decyzje z:
    - kolejek `Tier B`,
    - review answer mismatch,
    - media disambiguation,
  - buduje pierwszy realny backlog operacyjny po triage.

- `scripts/generate_pj360_tier_b_curated_drafts.py`
  - generuje drafty tylko dla tej czesci `Tier B`, ktora po triage ma:
    - `resolved_external_reference`
    - albo `reference_usable_after_answer_review`
  - tworzy ostrozna paczke draftow do dalszego QA, bez dotykania reszty `Tier B`.

### Wynik pierwszego przebiegu draftow

Na aktualnym snapshotcie:

- `2694` draftow zostalo wygenerowanych dla `Tier A`,
- `2692` draftow przeszlo walidacje bez flag,
- `2` drafty wymagaja dalszego review,
- glowny remaining blocker to:
  - `low_context_reference = 1`
  - `high_source_overlap = 1`,
- `high_source_overlap` zostal zbity do `1`.

### Wynik pierwszego przebiegu paczek review

Na aktualnym snapshotcie:

- `Tier B / high-risk`: `77`
- `Tier B / duplicate_or_overlap`: `316`
- `Tier A / flagged drafts`: `2`
- `Tier C / manual`: `12`
- `Tier PT / manual`: `439`

Artefakty:

- `output/analysis/pj360-compare/review-packets/summary.json`
- `output/analysis/pj360-compare/review-packets/README.md`
- `output/analysis/pj360-compare/review-packets/by-reason/*`
- `output/analysis/pj360-compare/review-packets/by-flag/*`
- `output/analysis/pj360-compare/review-packets/by-category/*`

### Wynik pierwszego przebiegu high-risk enrichment

Na aktualnym snapshotcie:

- `41` przypadkow wyglada na `duplicate_prompt_same_answer_media`
- `15` wyglada na `needs_media_disambiguation`
- `9` wyglada na `usable_text_scope_conflict`
- `6` wyglada na `likely_editorial_answer_mismatch`
- `2` wyglada na `manual_answer_conflict`
- `3` wyglada na `review_media_presence_only`
- `1` wyglada na `review_external_category_policy`

Artefakty:

- `output/analysis/pj360-compare/high-risk-review/high-risk-summary.json`
- `output/analysis/pj360-compare/high-risk-review/high-risk-enriched.json`
- `output/analysis/pj360-compare/high-risk-review/high-risk-enriched.csv`

### Wynik pierwszego przebiegu media disambiguation

Po dolozeniu matchera po hashach obrazu, pierwszej klatki wideo oraz sygnatur odpowiedzi:

- `13` przypadkow zamknelo sie jako `resolved_by_exact_media_hash`
- `1` przypadek zamknal sie jako `likely_resolved_by_media_hash`
- `1` przypadek zamknal sie jako `resolved_by_exact_answer_signature`

To oznacza, ze z pierwotnych `15` przypadkow `needs_media_disambiguation`:

- `15` jest juz rozstrzygnietych,
- `0` pozostaje w kolejce manualnej.

Najwazniejsze rozstrzygniecia nieoczywiste:

- `2243` z PJ360 pasuje do naszego `1369`, a nie do `2243`
- `7430` z PJ360 pasuje do naszego `4388`
- `13152` z PJ360 pasuje do naszego `11237`
- `10925` z PJ360 pasuje do naszego `10875`
- `942` z PJ360 pasuje do naszego `3549`
- `10887` zostal zamkniety po exact answer signature i wskazuje na nasze `10885`

Wazny niuans:

- te `15` rozstrzygniec oznacza, ze wiemy juz, do ktorego lokalnego `gov_id` nalezy dana strona PJ360,
- ale nie oznacza to jeszcze, ze pierwotne `15` lokalnych pytan wszystkie zachowuja referencje,
- w `7` przypadkach jedna strona PJ360 okazala sie nalezec do innego lokalnego duplikatu promptu, wiec pierwotne pytanie zostaje bez bezpiecznej referencji z PJ360.

Artefakty:

- `output/analysis/pj360-compare/media-disambiguation/media-disambiguation-summary.json`
- `output/analysis/pj360-compare/media-disambiguation/media-disambiguation-resolved.json`
- `output/analysis/pj360-compare/media-disambiguation/media-disambiguation-review-report.md`

### Wynik pierwszego przebiegu post-triage Tier B

Po zlozeniu wszystkich decyzji w jeden backlog:

- `Tier B` lacznie: `393`
- `15` rekordow ma juz rozstrzygnieta, bezpieczna referencje PJ360
- `7` rekordow traci referencje po redirect, bo jedyna strona PJ360 dla promptu nalezy do innego lokalnego `gov_id`
- `3` rekordy pozostaja `manual_only_answer_conflict`
- `6` rekordow to `reference_usable_after_answer_review`
- reszta to rozne poziomy review overlapu, scope albo polityki kategorii

Najwazniejsze liczby po triage:

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

Artefakty:

- `output/analysis/pj360-compare/post-triage/tier-b-post-triage-summary.json`
- `output/analysis/pj360-compare/post-triage/tier-b-post-triage-summary.md`
- `output/analysis/pj360-compare/post-triage/resolved_external_reference.csv`
- `output/analysis/pj360-compare/post-triage/manual_no_reference_after_redirect.csv`

### Wynik pierwszego przebiegu curated draftow Tier B

Po wygenerowaniu draftow tylko dla bezpiecznej czesci `Tier B`:

- `21` draftow zostalo przygotowanych,
- `21` przeszlo walidacje bez flag,
- w tym:
  - `15` z `resolved_external_reference`
  - `6` z `reference_usable_after_answer_review`

Artefakty:

- `output/analysis/pj360-compare/drafts/tier-b-curated-draft-packets.json`
- `output/analysis/pj360-compare/drafts/tier-b-curated-draft-summary.json`
- `output/analysis/pj360-compare/drafts/validation-tier-b-curated/tier-a-draft-validation-summary.json`

### Kolejka kandydatow do publikacji

Po zlozeniu czystego `Tier A` i curated `Tier B`:

- `2713` draftow nadaje sie do przejscia do kolejnego etapu publikacji
- w tym:
  - `2692` z `Tier A`
  - `21` z curated `Tier B`

Artefakty:

- `output/analysis/pj360-compare/publish-candidates/publish-candidates.json`
- `output/analysis/pj360-compare/publish-candidates/publish-candidates-summary.json`
- `output/analysis/pj360-compare/publish-candidates/publish-candidates.csv`

### Zalecane jako kolejny krok

1. mechanizm zatwierdzania draftow do `questions.explanation`
   - tylko z tabeli `question_explanation_drafts`
   - z blokada overwrite dla rekordow, ktore maja juz lokalne wyjasnienie
   - ten mechanizm jest juz wdrozony jako preview/apply, ale nie zostal jeszcze odpalony w trybie `--write`
2. zamkniecie `10` rekordow manualnych po triage:
   - `7` bez referencji po redirect
   - `3` realne konflikty odpowiedzi
3. wdrozenie publikacji:
   - `2692` czystych draftow `Tier A`
   - `21` czystych curated draftow z `Tier B`

## 11. Decyzja operacyjna

Tak, jestesmy gotowi zaczac adaptacje wyjasnien.

Ale tylko pod warunkiem, ze:

- `Tier A` traktujemy jako jedyny pelny kandydat do automatu,
- `Tier B` nie trafia do publikacji bez review,
- `Tier C` i `PT` piszemy od zera,
- nie kopiujemy tresci PJ360 `1:1`,
- przy duplikowanych promptach najpierw probujemy rozstrzygniecia po medium, a dopiero reszte zostawiamy do recznej decyzji.

To jest najbezpieczniejsza droga, zeby zrobic to skrupulatnie i bez pomylek.
