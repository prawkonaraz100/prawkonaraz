# Kontrolowany canary V2 dla powiązanych pytań

Status: `PUBLIC V2 FULL-TOPIC ROLLOUT ACTIVE / MONITORING OK`
Ostatnia aktualizacja: `2026-07-28`
Zakres: jeden topic, jeden niezmienny run V2, deterministyczna kohorta stron pytań

## Cel i granica

Ten runbook opisuje techniczną ścieżkę ostrożnego pokazania V2 w jednym
topicu. Kod canary wdrożono produkcyjnie 28 lipca 2026 r. w commicie
`f70794c2`; po początkowej kohorcie 5% wdrożono dedykowaną, chronioną ścieżkę
promocji w commicie `48b0b24c`. Po osobnej zgodzie, świeżych backupach i
pozytywnym preview rollout `secondary:zawracanie` pozostaje w `mode=canary`,
ale jego ekspozycja wynosi `100%`.

Aktywna kohorta obejmuje `29/29` źródeł topicu. Każda z tych stron renderuje
V2 z 15 linkami; V1 pozostaje publicznym rendererem dla wszystkich pozostałych
topiców oraz natychmiastowym rollbackiem flagą runtime.

Uruchomienie `mode=canary` jest zmianą publicznego HTML. Wymaga odrębnej,
jednoznacznej zgody właściciela bezpośrednio przed wykonaniem oraz świeżego,
sprawdzonego backupu.

## Kontrakt bezpieczeństwa

Zwykłe żądanie publicznej strony otrzyma V2 tylko, gdy jednocześnie są
spełnione wszystkie warunki:

1. `QUESTION_RELATIONS_V2_ENABLED=true`;
2. `QUESTION_RELATIONS_V2_CANARY_ENABLED=true`;
3. primary topic pytania ma rollout `mode=canary`, poprawny aktywny run i
   ekspozycję od `1` do `100`;
4. pytanie wchodzi do stabilnej kohorty dla tego runu.

Brak któregokolwiek warunku pozostawia renderer V1. Domyślna wartość nowej
flagi to `false`; samo wdrożenie kodu przy tej wartości nie zmienia HTML ani
indeksacji. Natychmiastowy rollback publicznego outputu to ponowne ustawienie
`QUESTION_RELATIONS_V2_CANARY_ENABLED=false`, odświeżenie konfiguracji i
restart PHP-FPM. Snapshot oraz dane audytowe pozostają wtedy nienaruszone.

Przypisanie jest deterministyczne: SHA-256 z wersją algorytmu, seedem,
identyfikatorem topicu i `external_id` źródłowego publicznego wyjaśnienia,
następnie `mod 10 000`. Ten sam seed daje tę samą kohortę w podglądzie,
monitoringu i podczas normalnego SSR. Mechanizm nie stosuje losowania,
cookie ani bucketingu użytkownika.

## Co jest wdrożone i aktywne

- osobna flaga canary i harmonogram monitora (`04:15`), obecnie aktywne dla
  tego kontrolowanego canary;
- resolver publiczny, który zastępuje V1 wyłącznie dla przydzielonej kohorty;
- konfigurator `seo:configure-question-relation-canary` działający domyślnie
  tylko jako preview;
- osobny promotor `seo:promote-question-relation-canary`, który może wyłącznie
  podnieść istniejący, niezmienny canary do 100% po potwierdzeniu bieżącej
  ekspozycji, runu i seeda;
- jawny zapis wymagający równocześnie `--write`,
  `--confirm-public-canary` i obu flag runtime;
- quality gates: jeden opublikowany topic, właściwy walidowany run, minimum
  15 linków na źródło, opublikowane/rozwiązywalne źródła i targety, niepusta
  kohorta oraz brak konfliktu z pełnym V2;
- monitor `seo:monitor-question-relation-v2-canary`, który wykonuje audyt
  spójności V1 ↔ V2 bez zmieniania danych;
- testy funkcjonalne dla obu wyłączników, kohorty, zapisu jawnego, idempotencji
  i prawdziwego baseline V1.

Aktualny aktywny run do ewentualnego preview to `2` dla
`secondary:zawracanie`: 29 źródeł, 435 rekomendacji (181 `direct`, 254
`context`) oraz 15 linków na każde źródło. Wynik P0 opisują
`docs/SEO-RELATION-ZAWRACANIE-LEGAL-VERIFICATION-2026-07-28.md` i
`docs/SEO-RELATION-ZAWRACANIE-EDITORIAL-AUDIT.md`.

Tylko-odczytowy production preview z 2026-07-28 przeszedł wszystkie quality
gates dla początkowych `5%`, seeda `zawracanie-v2-canary-2026-07-28-1` i runu
`2`. Historyczna, stabilna kohorta obejmowała `2/29` źródeł: pytania `1178` i
`6019`. Po osobnej zgodzie utworzono ten canary, a następnie niezależnym
preview potwierdzono pełną kohortę `29/29` i promowano ten sam rollout do 100%.

## Kolejność operacyjna po osobnej zgodzie

1. Wdrożyć kod przy obu flagach canary nadal ustawionych na `false`. Wykonać
   standardowe health/smoke testy; nie zmieniać rolloutu w bazie.
2. Uruchomić tylko-odczytowy plan, na przykład dla 5%:

   ```bash
   php artisan seo:configure-question-relation-canary \
     --topic-key=secondary:zawracanie \
     --run=2 \
     --exposure=5 \
     --cohort-seed='zawracanie-v2-canary-2026-07-28-1' \
     --report=storage/app/seo-audits/zawracanie-v2-canary-preview.json \
     --json
   ```

   Preview nie zmienia bazy ani HTML. Musi zwrócić `quality_gates.passed=true`
   i listę co najmniej jednego `included_external_id`.
3. Dopiero po pozytywnym planie utworzyć i zweryfikować backup bazy oraz
   runtime/configu. Zapisać ścieżki i sumy kontrolne w dzienniku wdrożenia.
4. Włączyć obie flagi runtime. Globalna flaga V2 może być już włączona dla
   istniejącego shadow; nowa flaga canary sama w sobie nadal nie publikuje V2,
   dopóki rollout pozostaje `shadow`.
5. Wykonać jawny zapis tylko raz:

   ```bash
   php artisan seo:configure-question-relation-canary \
     --topic-key=secondary:zawracanie \
     --run=2 \
     --exposure=5 \
     --cohort-seed='zawracanie-v2-canary-2026-07-28-1' \
     --write \
     --confirm-public-canary \
     --report=storage/app/seo-audits/zawracanie-v2-canary-write.json \
     --json
   ```

   To jest moment rozpoczęcia publicznego canary. Konfigurator może zastąpić
   wyłącznie istniejący `shadow` o ekspozycji 0%; nie nadpisuje `mode=v2`,
   innego canary ani nieprawidłowego shadow.
6. Od razu sprawdzić zwykłe URL-e co najmniej jednego pytania z raportu
   `included_external_ids` i jednego spoza tej listy. Pierwsze powinno mieć
   V2, drugie V1; oba muszą mieć poprawne canonicale, HTTP 200 i komplet co
   najmniej 15 rozwiązywalnych linków.
7. Uruchomić monitor i zachować raport:

   ```bash
   php artisan seo:monitor-question-relation-v2-canary \
     --topic-key=secondary:zawracanie \
     --report=storage/app/seo-audits/zawracanie-v2-canary-monitor.json \
     --fail-on-errors \
     --json
   ```

   Następnie obserwować harmonogram o `04:15`, health/smoke oraz sygnały SEO.

## Warunki przerwania i rollback

Natychmiast przerwać albo wycofać output V2, gdy preview ma choć jeden blocker,
backup nie daje się zweryfikować, smoke test V1 nie przechodzi, monitor zwróci
błąd, link jest nierozwiązywalny albo pojawi się błąd SSR/canonicalu.

Rollback bez modyfikacji snapshotu:

```env
QUESTION_RELATIONS_V2_CANARY_ENABLED=false
```

Po odświeżeniu cache konfiguracji i restarcie PHP-FPM następne żądania wracają
do V1. Nie usuwać ręcznie runu, rekomendacji ani rolloutu w trakcie incydentu;
najpierw zachować dowody, raport monitora i backup.

Konfigurator celowo traktuje istniejący canary z innym seedem, runem lub
ekspozycją jako konflikt. Zmiana ekspozycji nie jest ukrytą operacją: do
promocji pełnego topicu służy wyłącznie osobna komenda, która wymaga tego
samego runu i seeda, jawnego `--from-exposure` oraz
`--confirm-full-topic-rollout`.

## Pierwsza aktywacja 5% — historyczna, 2026-07-28

Przed włączeniem utworzono backup bazy i runtime/configu:

```text
backups/database/2026/07/20260728-102354-prawkonarazpl-pgsql-pgsql-seo-relation-v2-canary-20260728.sql.gz
backups/database-manifests/2026/07/20260728-102354-prawkonarazpl-pgsql-pgsql-seo-relation-v2-canary-20260728.json
/var/www/prawkobit/shared/backups/runtime/v2-canary-runtime-20260728102420
```

Następnie włączono `QUESTION_RELATIONS_V2_CANARY_ENABLED=true`, wykonano
`seo:configure-question-relation-canary --write --confirm-public-canary` i
przełączono rollout nr 1 z `shadow 0%` na `canary 5%`, wskazujący run nr 2.
Runtime shadow wyłączono po aktywacji, ponieważ canary ma własny resolver i
monitor.

Kontrole po zapisie: monitor canary ma `0` błędów i `0` ostrzeżeń, 29/29
źródeł oraz 435/435 rozwiązywalnych linków; `ops:health-report` i
`ops:smoke-test` zwróciły `OK`. Serwerowo potwierdzono V2 dla 1178 i 6019
(po 15 linków), V1 dla 352 oraz HTTP 200 i canonicale dla publicznych URL-i.

## Promocja do 100% topicu — wykonana 2026-07-28

Po wdrożeniu commitu `48b0b24c` wykonano tylko-odczytowy plan:

```bash
php artisan seo:promote-question-relation-canary \
  --topic-key=secondary:zawracanie \
  --run=2 \
  --from-exposure=5 \
  --cohort-seed='zawracanie-v2-canary-2026-07-28-1' \
  --report=storage/app/seo-audits/zawracanie-v2-full-topic-promotion-preview-20260728.json \
  --json
```

Preview potwierdził 29/29 źródeł, 435/435 wybranych i rozwiązywalnych linków,
dokładnie 15 linków na źródło, brak nierozwiązywalnych źródeł/targetów oraz
wszystkie quality gates. Przed zapisem utworzono backup bazy i runtime:

```text
backups/database/2026/07/20260728-111328-prawkonarazpl-pgsql-pgsql-seo-relation-v2-full-topic-promotion-20260728.sql.gz
backups/database-manifests/2026/07/20260728-111328-prawkonarazpl-pgsql-pgsql-seo-relation-v2-full-topic-promotion-20260728.json
/var/www/prawkobit/shared/backups/runtime/v2-full-topic-promotion-runtime-20260728111343
```

Jawny zapis z `--write --confirm-full-topic-rollout` podniósł wyłącznie
ekspozycję rollout nr 1 z `5%` do `100%`; zachował ID rolloutu, run `2`, seed i
czas rozpoczęcia oraz zapisał historię promocji w metadanych. Monitor po
zapisie ma 0 błędów i 0 ostrzeżeń, `ops:health-report` i `ops:smoke-test`
zwróciły `OK`, a kontrola serwerowa potwierdziła V2 i 15 linków dla wszystkich
29 źródeł. Publiczne URL-e 352, 1178 i 1428 zwróciły HTTP 200 oraz canonical.

## Weryfikacja deployu kodu — 2026-07-28

Wdrożono wyłącznie kod i odświeżono cache Laravel/PHP-FPM; nie uruchamiano
migracji ani nie zmieniano rekordu rollout. Backup zmienianych ścieżek:

```text
/tmp/prawkonaraz-v2-canary-guard-backup-20260728101427
```

Bezpośrednio po wdrożeniu kodu potwierdzono: `QUESTION_RELATIONS_V2_CANARY_ENABLED=false`,
rollout `shadow` z ekspozycją `0%` i runem `2`, monitor canary z `0` błędów
(dwa oczekiwane ostrzeżenia: flaga wyłączona i brak `mode=canary`),
`ops:health-report=OK`, `ops:smoke-test=OK`, HTTP 200 dla health oraz zwykłej
strony pytania bez markera V2.

## Stan decyzji

| Kontrola | Stan |
|---|---|
| Kod canary | wdrożony i przetestowany |
| Wdrożenie kodu canary | wykonano 2026-07-28, commit `f70794c2` |
| Flaga canary na produkcji | `true` |
| Flaga shadow na produkcji | `false` |
| Rollout produkcyjny | `canary`, 100%, run 2 |
| Publiczny output V2 | aktywny dla wszystkich 29/29 źródeł `secondary:zawracanie` |
| Następny krok | obserwacja monitora, health/smoke i sygnałów SEO; każdy kolejny topic wymaga osobnej decyzji; rollback flagą przy błędzie |
