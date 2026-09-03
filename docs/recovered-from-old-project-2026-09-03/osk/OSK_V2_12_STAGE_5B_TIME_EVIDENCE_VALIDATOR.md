# OSK V2.12 — Etap 5B: walidacja źródłowego czasu

**Status:** zakończony lokalnie; brak endpointu, UI, automatycznego timera i deployu.
**Data weryfikacji:** 2026-08-25.
**Branch implementacyjny:** `codex/osk-course-program-pilot-b`.

Ten etap odtwarza czas z rekordów dodanych w Etapie 5A. Nie tworzy jeszcze formalnego evidence, postępu, ukończenia lekcji ani assessmentu.

## 1. Co zostało dodane

```text
app/Domain/Osk/TheoryLearning/TimeEvidenceValidator.php
app/Domain/Osk/TheoryLearning/TimeEvidenceInterval.php
app/Domain/Osk/TheoryLearning/TimeEvidenceValidationIssue.php
app/Domain/Osk/TheoryLearning/TimeEvidenceValidationResult.php
app/Domain/Osk/TheoryLearning/TimeEvidenceIssueCode.php
```

`TimeEvidenceValidator` jest serwisem odczytowym. Przyjmuje enrollment i punkt odcięcia `asOf`, a następnie zwraca:

- surowe, zaakceptowane przedziały per sesja;
- przedziały po połączeniu nakładających się urządzeń;
- łączny czas w sekundach;
- jawne problemy walidacji wraz z identyfikatorem sesji i heartbeat-u.

Walidator nie zapisuje, nie poprawia i nie zamyka rekordów źródłowych.

## 2. Zasady liczenia

- Początek przedziału to `started_at` sesji.
- Koniec otwartej sesji jest ograniczony przez `asOf`, maksymalny czas sesji oraz `last_heartbeat + heartbeat + grace`.
- Sesja zamknięta przez kursanta kończy się na `closed_at`.
- Zamknięcie z powodu timeoutu musi wypadać dokładnie na granicy timeoutu.
- Zamknięcie z powodu limitu musi wypadać dokładnie na granicy limitu ciągłej sesji.
- Heartbeat po granicy timeoutu, cofnięty timestamp, luka poza grace albo luka w sekwencji dyskwalifikują całą sesję z wyniku.
- Nakładające się przedziały z wielu urządzeń są łączone metodą `UNION`; czas nie jest sumowany podwójnie.
- Rekordy rozpoczynające się po historycznym `asOf` są pomijane, a heartbeat-y po `asOf` nie wpływają na odczyt historyczny.
- Sesja musi nadal wskazywać tę samą wersję programu i hash, które są przypięte do enrollmentu. Niezgodność kończy się wynikiem fail-closed.

Przedział jest półotwarty logicznie: czas liczony jest jako różnica końca i początku. Przedziały stykające się granicą są łączone, co nie zmienia sumy sekund.

## 3. Problemy walidacji

Kody są techniczne i nie są jeszcze komunikatami dla kursanta:

```text
INVALID_SESSION_SNAPSHOT
INVALID_HEARTBEAT_SEQUENCE
INVALID_HEARTBEAT_TIMESTAMP
HEARTBEAT_GAP_EXCEEDED
LAST_HEARTBEAT_MISMATCH
INVALID_CLOSE_BOUNDARY
UNSUPPORTED_OVERLAP_STRATEGY
```

Uszkodzona sesja nie jest po cichu naprawiana i nie dostarcza częściowego formalnego czasu. Późniejsza warstwa może skierować problem do ręcznego przeglądu, ale ten etap nie tworzy jeszcze `evidence` ani `LegalReviewFlag`.

## 4. Poza zakresem

- brak kontrolera i endpointów;
- brak zapisu z `LessonPlayer.vue`;
- brak automatycznego timera, workera i crona;
- brak postępu lekcji lub modułu;
- brak `ModuleAssessment` i `TheoryCompletionGate`;
- brak formalnego evidence, PDF/PAPER i egzaminu;
- brak zmian w `StudySession`, `QuestionCollection` oraz istniejącym `/nauka`.

## 5. Weryfikacja lokalna

Testy obejmują pojedynczy przedział, historyczne `asOf`, świadome zamknięcie, nakładanie dwóch urządzeń, lukę heartbeat-u oraz niezgodny hash polityki. `LearningSessionFoundationTest` przechodzi lokalnie: **11 testów / 64 asercje**.

Po Etapie 5B wykonano również lokalną regresję: `tests/Feature/Osk` (**47 testów / 313 asercji**), istniejący przepływ `/nauka` (`StudySessionFlowTest`, **35 testów / 824 asercje**) oraz kolekcje pytań, B2C access, checkout i audit log (**37 testów / 441 asercje**). `Pint`, `git diff --check` i odpowiedź modułu Vite dla `Osk/TheoryLearning/Index.vue` zakończyły się poprawnie.

Etap 5C wystawia jawne `start/resume`, `heartbeat` i `close`, a Etap 5D podłącza je do playera z świadomym startem, wznowieniem i obsługą błędów. Następne zadanie to ręczny smoke test pilota; nie należy jeszcze dodawać postępu, formalnego evidence ani `TheoryCompletionGate`.
