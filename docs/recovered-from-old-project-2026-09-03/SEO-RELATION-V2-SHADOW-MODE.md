# V2 shadow mode dla powiązanych pytań

Status: `HISTORICAL SHADOW / PRODUCTION V2 FULL-TOPIC ROLLOUT ACTIVE`
Ostatnia aktualizacja: 2026-07-28
Zakres: historyczne niepubliczne porównanie V1 z zatwierdzonym runem V2 dla jednego klastra

## Cel

Shadow mode odczytuje niezmienny snapshot V2 przy renderowaniu strony pytania,
ale nie zmienia żadnego pola przekazywanego do publicznego komponentu Blade.
Użytkownik, robot wyszukiwarki i SSR nadal otrzymują wyłącznie zestaw V1.

Tryb służy do sprawdzenia, czy zapisany run V2 jest rozwiązywalny na aktualnych
publicznych pytaniach oraz do porównania jego zestawów z V1 przed utworzeniem
wewnętrznego preview albo canary.

Shadow był etapem niepublicznym dla `secondary:zawracanie`. Po osobnej zgodzie
rollout nr 1 wskazuje ten sam walidowany run nr 2 jako `mode=canary` z
ekspozycją `100%`; wszystkie 29 źródeł topicu otrzymuje V2 z 15 linkami,
natomiast pozostałe topici nadal otrzymują V1. Aktywny opis operacyjny:
`docs/SEO-RELATION-V2-CANARY-RUNBOOK.md`. Runtime shadow jest obecnie
wyłączony; aktywny canary korzysta z własnego resolvera i monitora.

## Trzy wymagane bramki

Resolver V2 wykona odczyt tylko wtedy, gdy wszystkie warunki są spełnione:

1. `QUESTION_RELATIONS_V2_ENABLED=true` — globalna flaga V2;
2. `QUESTION_RELATIONS_V2_SHADOW_ENABLED=true` — flaga odczytu shadow;
3. główny, zweryfikowany topic pytania ma rekord `question_relation_rollouts`
   z `mode=shadow`, `exposure_percentage=0` oraz aktywnym runem o statusie
   `validated` albo `published`.

Brak dowolnej bramki zwraca V1 bez dodatkowego zapytania V2. Ustawienie
`QUESTION_RELATIONS_V2_SHADOW_ENABLED=false` jest natychmiastowym rollbackiem
trybu shadow; nie usuwa runu ani rekomendacji.

`mode=shadow` nie jest canary: jego ekspozycja zawsze wynosi `0`, a kod nie
zastępuje V1 w `PublicQuestionRelationsService`.

## Co odczytuje V2

`QuestionRelationV2ShadowResolver` odczytuje tylko rekomendacje `selected` z
aktywnego runu i wymaga:

- opublikowanego źródłowego i targetowego wyjaśnienia,
- zweryfikowanego primary membershipu źródła w topicu runu,
- kanonicznego publicznego pytania dla każdego targetu,
- statusu runu `validated` lub `published`.

Wynik trafia wyłącznie do wewnętrznego pola `shadow` zwracanego przez serwis.
Kontroler strony i komponent `related-question-groups` go nie renderują.
Nie są publikowane proponowane typy relacji; 86 rekordów `direct` bez
`question_relation_id` pozostaje bezpiecznie obsługiwanych przez snapshot.

## Wewnętrzny, podpisany preview i monitoring — produkcja 2026-07-28

Dostępny jest chroniony ekran Filament `admin/relacje-v2-shadow`. Panel wymaga
autoryzacji administratora; niezalogowane żądanie jest przekierowywane do
`/admin/login`.

Ekran porównuje dla wybranego pytania dokładny wynik V1 i snapshot V2, pokazuje
run, topic, overlap, nierozwiązywalne targety oraz czas odczytu obu ścieżek.
Resolver preview może odczytać istniejący `mode=shadow` albo `mode=canary`,
niezależnie od zwykłego publicznego SSR.

Do jednorazowej oceny układu na prawdziwej stronie jest też podpisany URL
`relation_preview=v2`, generowany komendą
`seo:make-question-relation-v2-preview-url`. Tylko ważny podpis czasowy
udostępnia V2 dla jednego pytania niezależnie od kohorty. Zwykły URL stosuje
reguły canary: przy obecnym rolloutcie V2 otrzymują wszystkie 29 źródeł
`secondary:zawracanie`, a inne topici nadal V1. Preview ma
`noindex,nofollow,noarchive`, nie emituje JSON-LD
i nie zmienia samego canary. Pełny runbook:
`docs/SEO-RELATION-V2-SIGNED-PREVIEW.md`.

Cykliczny, tylko-odczytowy monitor działa komendą:

```bash
php artisan seo:monitor-question-relation-v2-shadow \
  --topic-key=secondary:zawracanie \
  --fail-on-errors
```

Na produkcji harmonogram uruchamia ją codziennie o
`QUESTION_RELATIONS_V2_SHADOW_MONITOR_AT` (domyślnie `04:00`), tylko gdy obie
flagi runtime są aktywne. Zapisuje ostatni raport do
`storage/app/seo-audits/question-relation-v2-shadow-latest.json`.

Weryfikacja po deployu i korekcie redakcyjnej: monitor dla
`secondary:zawracanie` zwrócił `status=ok` (1 rollout, 1 topic, 0 błędów,
0 ostrzeżeń). Aktywny run nr 2 ma 29 źródeł core, 435 rekomendacji i dokładnie
15 rozwiązywalnych targetów na każde źródło. Audyt V1 ↔ V2 wykazał 201 wspólnych
linków oraz po 234 linki wyłącznie V1 i wyłącznie V2; jest to oczekiwana różnica
po korektach redakcyjnych, a nie zmiana publicznego wyniku. Podpisany preview
pytania 352 potwierdził V2=15 i meta robots `noindex,nofollow,noarchive`.
Backup runtime sprzed deployu preview:
`/var/www/prawkobit/shared/backups/runtime/v2-shadow-preview-runtime-202607272356`.

P0 weryfikację treści i materiałów reprezentatywnych pytań opisuje
`docs/SEO-RELATION-ZAWRACANIE-LEGAL-VERIFICATION-2026-07-28.md`. Jej wynik
nie zmienia trybu shadow ani nie stanowi aktywacji canary.

## Korekta aktywnego runu — 2026-07-28

Run nr 2 pochodzi z artefaktu
`resources/seo/question-relation-shadow/zawracanie-v2-editorial-exclusions/`.
Usuwa on dwie uprzednio zidentyfikowane, nieprawidłowe relacje `direct`
`1428 → 1490` i `10252 → 10435`; zamiast historycznych 184/251 ma 181
rekomendacji `direct` i 254 `context`. Zmiana została wykonana przez nowy,
niemodyfikowalny snapshot i atomową zamianę runu wskazywanego przez rollout,
po świeżym backupie bazy. Nie była to publiczna publikacja V2.

Szczegóły korekty: `docs/SEO-RELATION-SHADOW-RUN-IMPORT.md`; wynik P0:
`docs/SEO-RELATION-ZAWRACANIE-EDITORIAL-AUDIT.md`.

## Preview i aktywacja rekordu shadow

Przed zapisem wykonaj preview:

```bash
php artisan seo:configure-question-relation-shadow \
  --topic-key=secondary:zawracanie \
  --run=2 \
  --report=storage/app/seo-audits/zawracanie-shadow-rollout-preview.json \
  --json
```

Komenda sprawdza unikalność i publikację topicu, zgodność runu z topicem,
status runu, minimum 15 rekomendacji na źródło, publiczną rozwiązywalność
targetów oraz konflikt z istniejącym `canary`/`v2`.

Po świeżym backupie i pozytywnym preview jawny zapis ma postać:

```bash
php artisan seo:configure-question-relation-shadow \
  --topic-key=secondary:zawracanie \
  --run=2 \
  --write \
  --report=storage/app/seo-audits/zawracanie-shadow-rollout-write.json \
  --json
```

Zapis tworzy lub aktualizuje wyłącznie rekord `mode=shadow` z ekspozycją `0`.
Może atomowo wskazać nowy, zwalidowany run wyłącznie wtedy, gdy istniejący
rollout jest już `shadow` z ekspozycją `0`; nie może nadpisać `canary`, `v2`
ani nieprawidłowego shadow z inną ekspozycją. Nie zmienia statusu runu,
rekomendacji, `question_relations` ani publicznego HTML. Drugi identyczny zapis
jest idempotentny.

## Raport porównawczy V1 ↔ V2

Komenda jest tylko do odczytu, z wyjątkiem opcjonalnego pliku raportu:

```bash
php artisan seo:audit-question-relation-v2-shadow \
  --topic-key=secondary:zawracanie \
  --run=2 \
  --report=storage/app/seo-audits/zawracanie-v1-v2-shadow.json \
  --fail-on-errors \
  --json
```

Raport zawiera m.in.:

- liczbę źródeł i rekomendacji V2,
- minimum/maksimum linków na źródło,
- niedostępne źródła i targety,
- sumę linków V1, V2, wspólnych, wyłącznie V1 i wyłącznie V2,
- zgodność pozycji oraz ograniczoną próbkę różnic,
- stan obu flag i aktualnego rekordu rollout.

Brak rekordu shadow jest ostrzeżeniem, a nie zmianą danych — można więc
przeprowadzić audyt runu przed jego aktywacją.

## Historyczna kolejność pierwszej aktywacji — wykonana 2026-07-27

1. Wdrożono pakiet kodu przy obu flagach ustawionych początkowo na `false`.
   Runtime można odtworzyć z
   `/var/www/prawkobit/shared/backups/runtime/v2-shadow-runtime-202607272350`.
2. Audyt przed aktywacją potwierdził 29 źródeł, 435 linków V1 i 435 linków V2,
   201 wspólnych pozycji oraz 0 błędów. Jedynym oczekiwanym ostrzeżeniem był
   brak rekordu shadow przed jego utworzeniem.
3. Po pozytywnym preview utworzono i sprawdzono świeży backup PostgreSQL:
   `backups/database/2026/07/20260727-212112-prawkonarazpl-pgsql-pgsql-seo-v2-shadow-mode-enable-20260727.sql.gz`.
4. `seo:configure-question-relation-shadow --write` utworzył rollout nr 1
   dla runu nr 1 jako `mode=shadow`, `exposure_percentage=0`. Powtórzenie
   zapisu dało `write_idempotent` i zerowy delta.
5. Ustawiono `QUESTION_RELATIONS_V2_ENABLED=true` oraz
   `QUESTION_RELATIONS_V2_SHADOW_ENABLED=true`, odświeżono cache konfiguracji
   i zrestartowano PHP-FPM.
6. Audyt po aktywacji ma status `ok`: 29 źródeł, 435 linków V1, 435 linków V2,
   201 wspólnych pozycji i 0 błędów. Dla pytania 352 skrót zestawu V1 przed i
   po aktywacji jest identyczny, a resolver shadow rozwiązał komplet 15/15
   rekomendacji.
7. `ops:health-report`, `ops:smoke-test`, strona źródłowego pytania, `/login`
   i `/api/v1/health` zwróciły sukces. SSR nie zawiera markera V2 i renderuje
   wyłącznie komponent V1.

Kolejny bezpieczny krok to redakcyjny przegląd różnic V1 ↔ V2 z panelu,
a następnie audyt wszystkich pytań „Zawracanie” i propozycja subhubów.
Ten historyczny runbook shadow nie steruje już ruchem: rollout nr 1 działa jako
`canary 100%`, runtime shadow jest wyłączony, a aktywną procedurę obserwacji i
rollbacku opisuje `docs/SEO-RELATION-V2-CANARY-RUNBOOK.md`.
