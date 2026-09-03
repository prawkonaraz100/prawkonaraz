# Kontrolowany canary V2 dla powiązanych pytań

Status: `IMPLEMENTED LOCALLY / NOT DEPLOYED / PUBLIC CANARY OFF`
Ostatnia aktualizacja: `2026-07-28`
Zakres: jeden topic, jeden niezmienny run V2, deterministyczna kohorta stron pytań

## Cel i granica

Ten runbook opisuje techniczną ścieżkę ostrożnego pokazania V2 małej,
powtarzalnej części ruchu jednego topicu. Nie jest zgodą na jej wykonanie.
W chwili sporządzenia dokumentu kod canary istnieje wyłącznie lokalnie, nie
został wdrożony, a produkcyjny rollout `secondary:zawracanie` nadal ma
`mode=shadow`, ekspozycję `0%` i publicznie renderuje V1.

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

## Co jest zaimplementowane lokalnie

- osobna flaga canary i harmonogram monitora (`04:15`), oba domyślnie
  wyłączone;
- resolver publiczny, który zastępuje V1 wyłącznie dla przydzielonej kohorty;
- konfigurator `seo:configure-question-relation-canary` działający domyślnie
  tylko jako preview;
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

## Kolejność operacyjna po osobnej zgodzie

1. Wdrożyć kod przy obu flagach canary nadal ustawionych na `false`. Wykonać
   standardowe health/smoke testy; nie zmieniać rolloutu w bazie.
2. Uruchomić tylko-odczytowy plan, na przykład dla 5%:

   ```bash
   php artisan seo:configure-question-relation-canary \
     --topic-key=secondary:zawracanie \
     --run=2 \
     --exposure=5 \
     --cohort-seed='zawracanie-v2-canary-2026-07-28' \
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
     --cohort-seed='zawracanie-v2-canary-2026-07-28' \
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

Obecna wersja celowo traktuje istniejący canary z innym seedem, runem lub
ekspozycją jako konflikt. Podniesienie ekspozycji nie jest ukrytą operacją:
wymaga osobnej, zaprojektowanej ścieżki zmiany i nowej zgody, zamiast
automatycznego rozszerzenia ruchu.

## Stan decyzji

| Kontrola | Stan |
|---|---|
| Kod canary | lokalny, przetestowany |
| Wdrożenie kodu canary | nie wykonano |
| Flaga canary na produkcji | `false` / brak nowej konfiguracji |
| Rollout produkcyjny | `shadow`, 0% |
| Publiczny canary | nie włączono |
| Następne wymagane uprawnienie | jednoznaczna zgoda na wdrożenie kodu i osobno na publiczny canary |
