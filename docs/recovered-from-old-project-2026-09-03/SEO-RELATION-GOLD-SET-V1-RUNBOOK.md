# Gold set relacji pytań V1 — runbook pilota „Zawracanie”

Data przygotowania: 2026-07-27
Status: `GOLD SET FROZEN / ASSISTED SELF REVIEW / HUMAN REVIEW WAIVED`
Publiczny rollout: `OFF` — selektor V1 pozostaje bez zmian

## Cel

Pakiet tworzy powtarzalną próbkę referencyjną dla pilota „Zawracanie”. Nie
publikuje relacji, nie zapisuje bazy i nie uruchamia rankingu, `shadow`, `canary`
ani V2. Jego zadaniem jest oddzielenie:

- kandydatur algorytmicznych wymagających ręcznej oceny,
- technicznych odrzuceń wynikających z kolizji identyfikatorów,
- przyszłej prawdy referencyjnej używanej do pomiaru scoringu.

## Wynik pełnego przebiegu

| Metryka | Wartość |
|---|---:|
| pytania rdzeniowe `zawracanie` | 29 |
| kandydaci wspierający ze znaków i sygnalizacji | 43 |
| wszystkie pytania pilota | 72 |
| pary oczekujące na ocenę redakcyjną | 394 |
| techniczne negatywy kolizji ID | 14 |
| wszystkie rekordy pakietu | 408 |
| relacje wewnętrzne rdzenia obecne na produkcji | 60 |
| pytania rdzenia bez publicznej relacji wewnętrznej | 10 z 29 |

Pytania wspierające są kandydatami do dodatkowych membershipów, a nie
automatycznie zaakceptowanymi członkami klastra.

## Artefakty

Katalog: `resources/seo/question-relation-gold-set/v1/`

- `collision-resolution.json` — cztery jawnie rozstrzygnięte kolizje ID i
  oczekiwane liczności,
- `questions.json` — 29 pytań rdzeniowych i 43 kandydatów wspierających,
- `review.csv` — arkusz pracy redakcyjnej,
- `review.jsonl` — niezmieniona, kanoniczna próbka wejściowa,
- `manifest.json` — wersja danych, rozkłady, bramki jakości i SHA-256,
- `README.md` — skrócona instrukcja oceny.
- `review-batches/` — trzy audytowalne batche pierwszej oceny wspomaganej,
- `second-review/first-pass-review.csv` — deterministycznie scalone 394 decyzje
  pierwszej oceny oraz 14 niezmienionych `locked_negative`,
- `second-review/second-review.csv` — arkusz dla innej osoby, bez wypełnionych
  pól drugiej decyzji,
- `second-review/second-review-manifest.json` — liczności, wejściowe SHA-256 i
  hashe wygenerowanego pakietu.
- `assisted-self-review-v1.json` — jawny zakres drugiego passu AI, zastosowane
  kontrole i siedem korekt,
- `../v1-reviewed-assisted-self-review/` — ukończone decyzje, reconciliacja,
  raport rozbieżności oraz zamrożone `gold-set.jsonl` z manifestem.

Generator:

- `app/Support/QuestionRelationGoldSetBuilder.php`,
- `app/Console/Commands/BuildQuestionRelationGoldSetCommand.php`.

Walidator decyzji:

- `app/Support/QuestionRelationGoldSetReviewValidator.php`,
- `app/Console/Commands/ValidateQuestionRelationGoldSetCommand.php`.

Workflow niezależnej drugiej oceny:

- `app/Support/QuestionRelationSecondReviewWorkflow.php`,
- `app/Console/Commands/PrepareQuestionRelationSecondReviewCommand.php`,
- `app/Console/Commands/ReconcileQuestionRelationSecondReviewCommand.php`.

## Generowanie i preview

Preview jest domyślny i nie zapisuje plików:

```bash
php artisan seo:build-question-relation-gold-set \
  --path=/sciezka/do/outputs/seo-relations \
  --resolution=resources/seo/question-relation-gold-set/v1/collision-resolution.json
```

Zapis wymaga jawnych `--write` i `--output`:

```bash
php artisan seo:build-question-relation-gold-set \
  --path=/sciezka/do/outputs/seo-relations \
  --resolution=resources/seo/question-relation-gold-set/v1/collision-resolution.json \
  --output=resources/seo/question-relation-gold-set/v1 \
  --write
```

Komenda kończy się błędem i niczego nie zapisuje, jeżeli zmieni się choć jedna
oczekiwana liczność albo rozstrzygnięta encja nie odpowiada katalogowi.

## Zasady oceny redakcyjnej

Każdy wiersz `pending_editorial` wymaga decyzji człowieka:

1. Najpierw skopiuj seed `review.csv` do osobnego pliku roboczego, np.
   `output/zawracanie-reviewed.csv`. Nie edytuj kanonicznego seeda.
2. `gold_label=true`, jeżeli para powinna być wykorzystana edukacyjnie, albo
   `gold_label=false`, jeżeli nie powinna.
3. Ustaw `review_status=reviewed`.
4. Dla pozytywnej pary należy wybrać jeden dominujący `gold_relation_type`.
5. Dla negatywnej pary należy podać krótki `rejection_reason`.
6. Należy wypełnić `reviewer` i `reviewed_at` w ISO 8601.
7. Dowody, uzasadnienie i różnicę trzeba sprawdzić merytorycznie; wysoki score
   ani `source_selected_to_graph=true` nie oznaczają automatycznej akceptacji.

Rekordy `locked_negative` mają `gold_label=false`, ale są wyłączone z metryk
semantycznych. Odrzucamy w nich błędne podstawienie encji produkcyjnej pod
zduplikowany numer źródłowy. Nie twierdzimy, że dwa pełne pytania zapisane w
źródle nie są ze sobą tematycznie powiązane.

## Niezależny drugi review i rozbieżności

Pakiet dla drugiej osoby jest już wygenerowany w `second-review/`. Można go
odtworzyć deterministycznie; bez `--write` komenda wykonuje wyłącznie preview:

```bash
php artisan seo:prepare-question-relation-second-review \
  --baseline=resources/seo/question-relation-gold-set/v1/review.csv \
  --batch=resources/seo/question-relation-gold-set/v1/review-batches/assisted-high-priority-v1.json \
  --batch=resources/seo/question-relation-gold-set/v1/review-batches/assisted-medium-priority-v1.json \
  --batch=resources/seo/question-relation-gold-set/v1/review-batches/assisted-low-priority-v1.json
```

Drugi reviewer wypełnia wszystkie 394 wiersze `second-review.csv`:

1. `second_decision=approve` potwierdza pierwszy label i typ; pola
   `second_gold_*` mogą pozostać puste.
2. `second_decision=change` wymaga własnego `second_gold_label`, typu relacji
   albo powodu odrzucenia oraz `second_notes`.
3. Każdy wiersz wymaga `second_reviewer` różnego od `first_reviewer` oraz
   `second_reviewed_at` w pełnym ISO 8601.
4. Każde `change` jest rozbieżnością. Musi ją rozstrzygnąć trzecia osoba przez
   `adjudication_decision=first|second|custom`, `adjudicator`, `adjudicated_at`
   i `adjudication_notes`. Adjudicator musi różnić się od obu reviewerów.

Preview reconciliacji:

```bash
php artisan seo:reconcile-question-relation-second-review \
  --first-pass=resources/seo/question-relation-gold-set/v1/second-review/first-pass-review.csv \
  --second-review=resources/seo/question-relation-gold-set/v1/second-review/second-review.csv
```

Komenda blokuje zmienione pola dowodowe, tę samą osobę po obu stronach review,
niepełne decyzje oraz nierozstrzygnięte różnice. Jawne `--write --output=...`
zawsze zapisuje raport i CSV rozbieżności, ale tworzy `reconciled-review.csv`
wyłącznie po przejściu wszystkich bramek. Ten plik nadal musi przejść pełną
komendę `seo:validate-question-relation-gold-set`; reconciliacja sama nie
zamraża gold setu i niczego nie publikuje.

### Zastosowane odstępstwo: assisted self-review

27 lipca 2026 r. właściciel produktu jawnie zrezygnował z udziału człowieka i
polecił wykonać drugi pass przez Codex. Nie nazwano go niezależnym review.
Odstępstwo jest widoczne jako `review_mode=assisted_self_review`,
`human_review_waived=true` oraz `independent_review_claimed=false`.

Audyt objął komplet 394 par, w tym wszystkie decyzje średniej pewności,
60 semantycznych negatywów, korekty typu względem propozycji, skrajne score'y,
odstające metryki per typ i spójność wspólnych podstaw prawnych. Wynik:

- 387 decyzji utrzymanych,
- 7 jawnych korekt i 7 rozstrzygniętych rozbieżności,
- 3 zmiany negatyw → pozytyw,
- 4 korekty `tematyczne` → `ta_sama_zasada`,
- 337 pozytywów, 57 negatywów i 14 technicznych `locked_negative`,
- 0 pending, 0 invalid i pokrycie 7/7 typów.

Siedem korekt dotyczy art. 22 ust. 6 pkt 1: tunel, most i droga jednokierunkowa
nie są niezależnymi podstawami zakazu, lecz wariantami tej samej ustawowej
reguły. Powtarzalny przebieg ma trzy kroki:

```bash
php artisan seo:build-question-relation-assisted-self-review \
  --template=resources/seo/question-relation-gold-set/v1/second-review/second-review.csv \
  --audit=resources/seo/question-relation-gold-set/v1/assisted-self-review-v1.json \
  --output=resources/seo/question-relation-gold-set/v1-reviewed-assisted-self-review \
  --write

php artisan seo:reconcile-question-relation-second-review \
  --first-pass=resources/seo/question-relation-gold-set/v1/second-review/first-pass-review.csv \
  --second-review=resources/seo/question-relation-gold-set/v1-reviewed-assisted-self-review/completed-second-review.csv \
  --allow-assisted-self-review \
  --output=resources/seo/question-relation-gold-set/v1-reviewed-assisted-self-review \
  --write

php artisan seo:validate-question-relation-gold-set \
  --baseline=resources/seo/question-relation-gold-set/v1/review.jsonl \
  --review=resources/seo/question-relation-gold-set/v1-reviewed-assisted-self-review/reconciled-review.csv \
  --output=resources/seo/question-relation-gold-set/v1-reviewed-assisted-self-review \
  --write
```

Flaga `--allow-assisted-self-review` jest obowiązkowa. Bez niej ta sama osoba po
obu stronach review nadal poprawnie blokuje reconciliację.

## Ewaluacja bazowego scoringu

Zamrożony gold set został użyty wyłącznie offline do pomiaru dotychczasowego
`proposed_score` i `proposed_relation_type`. Komenda domyślnie wykonuje preview:

```bash
php artisan seo:evaluate-question-relation-gold-set \
  --gold-set=resources/seo/question-relation-gold-set/v1-reviewed-assisted-self-review/gold-set.jsonl
```

Wersjonowane wyniki znajdują się w katalogu `evaluation/` obok gold setu.
Najważniejsze wartości:

| Metryka | Wynik |
|---|---:|
| precision wszystkich kandydatur | 85,53% |
| precision@10 micro, dla dostępnych pozycji | 88,17% |
| precision@10 macro, pytania z min. 10 kandydatami | 90,00% |
| pytania z min. 10 kandydatami | 44 / 72 |
| pytania z min. 20 kandydatami | 2 / 72 |
| mediana kandydatów na pytanie | 12 |
| zgodność proponowanego typu z gold typem | 25,52% |

Próg `score >= 0.30` daje w próbce 97,74% precision i 77,15% recall liczony
wyłącznie wewnątrz gold setu. Próg `score >= 0.25` daje 92,81% precision oraz
88,13% recall. Są to punkty startowe do `shadow`, nie zgoda na publikację.

Wniosek produktowy: bieżący score nadaje się do budowy precyzyjnego rdzenia,
ale nie zapewnia 15 bezpośrednich kandydatów pod każdym pytaniem. Ranking V2
musi dopełniać rdzeń bezpiecznym kontekstem z właściwego huba lub subhuba.
Proponowany typ relacji nie może być publikowany bez osobnej korekty modelu,
ponieważ trafność 25,52% jest niewystarczająca.

## Walidacja i zamrożenie decyzji

Preview nie zapisuje plików i powinien być uruchamiany podczas pracy:

```bash
php artisan seo:validate-question-relation-gold-set \
  --baseline=resources/seo/question-relation-gold-set/v1/review.jsonl \
  --review=output/zawracanie-reviewed.csv \
  --allow-partial
```

`--allow-partial` akceptuje nietknięte wiersze `pending_editorial`, ale nadal
blokuje niepełną rozpoczętą decyzję, zmianę chronionych pól i naruszenie
`locked_negative`. Nie można go łączyć z `--write`.

Po przejściu wszystkich bramek wynik można zamrozić jawnie:

```bash
php artisan seo:validate-question-relation-gold-set \
  --baseline=resources/seo/question-relation-gold-set/v1/review.jsonl \
  --review=output/zawracanie-reviewed.csv \
  --output=resources/seo/question-relation-gold-set/v1-reviewed \
  --write
```

Walidator blokuje:

- brakujące, nieznane i powtórzone `pair_key`,
- zmianę dowolnego chronionego pola źródłowego,
- zmianę rekordu `locked_negative`,
- brak decyzji, reviewera albo pełnej daty ISO 8601,
- pozytyw bez poprawnego typu i negatyw bez powodu,
- nieznany typ relacji,
- brak semantycznych negatywów,
- brak pozytywnego przykładu typu obecnego w próbce.

Udany zapis tworzy `gold-set.jsonl` oraz `gold-set-manifest.json` z hashami
wejść i wyjścia. Ponowne uruchomienie na tych samych danych jest idempotentne.

## Definicja ukończonego gold setu

Pakiet jest ukończonym gold setem według jawnie zaakceptowanej polityki
`assisted_self_review`, ponieważ:

- wszystkie 394 rekordy `pending_editorial` mają decyzję,
- każda decyzja pozytywna ma typ relacji,
- każda decyzja negatywna ma powód odrzucenia,
- nie ma nieznanych typów ani powtórzonych `pair_key`,
- próbka zawiera pozytywne i negatywne przykłady obsługiwanych typów,
- osobno policzono kontrasty oraz „nie pomyl z”,
- odstępstwo od drugiej osoby jest zapisane w manifestach; nie sugerujemy, że
  próbkę sprawdził człowiek ani niezależny reviewer,
- walidator zamroził wynik jako kolejną, niezmienną wersję danych.

## Ranking shadow — wykonany

Ranking `question-relation-shadow-zawracanie-v1` został zbudowany i zamrożony
bez używania etykiet gold podczas selekcji. Rdzeń obejmuje wszystkie pary z
`score >= 0.30`, bez górnego limitu. Jeżeli rdzeń nie daje 15 pozycji, osobna
warstwa `hub_fallback` uzupełnia listę pytaniami z tego samego podtematu, a
następnie tematu głównego. Typ relacji jest zapisany wyłącznie diagnostycznie
dla rdzenia i nie jest zatwierdzony do publikacji.

Wynik: 72/72 pytań, 1 087 linków, 15–19 pozycji na pytanie, 532 linki rdzenia,
555 linków fallbacku, 97,74% precision rdzenia i 0 identycznych zestawów.
Balansowanie deterministyczne daje 7–19 linków przychodzących na pytanie.

Szczegółowy runbook, artefakty i ograniczenia:
`resources/seo/question-relation-shadow/zawracanie-v1/README.md`.

## Następne kroki

1. `DONE LOCAL`: zbudowano adapter preview snapshot JSONL → niezmienny run V2
   i rekomendacje niepubliczne, bez włączania rolloutu; oczekuje na wdrożenie i
   produkcyjny preview.
2. Oddzielić klasyfikator typu od decyzji „czy relacja istnieje”; obecna
   trafność typu w dodatnim rdzeniu shadow wynosi 18,08%.
3. Zaprojektować feature flag per klaster oraz atomowe publish/rollback.
4. Publiczny canary i pomiar CTR wymagają osobnej decyzji.

Pierwsza ocena wspomagana wszystkich 394 kandydatur została wykonana 27 lipca
2026 r. Wyniki, ograniczenia i procedurę drugiej oceny opisują dokumenty
`docs/SEO-RELATION-GOLD-SET-ASSISTED-REVIEW-BATCH-01.md`,
`docs/SEO-RELATION-GOLD-SET-ASSISTED-REVIEW-BATCH-02.md` oraz
`docs/SEO-RELATION-GOLD-SET-ASSISTED-REVIEW-BATCH-03.md`. Tego samego dnia
przygotowano wersjonowany pakiet drugiej oceny i automatyczną bramkę
niezależności, rozbieżności oraz adjudykacji. Następnie, po jawnej rezygnacji
właściciela z review człowieka, wykonano oznaczony drugi pass AI, rozstrzygnięto
7 korekt i zamrożono wynik bez zmiany publicznego selektora V1.
