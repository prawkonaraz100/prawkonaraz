# OSK V2.12 — Etap 5A: fundament źródłowych sesji czasu

**Status:** zakończony lokalnie; nie jest jeszcze podłączony do playera ani wystawiony przez HTTP.
**Data weryfikacji:** 2026-08-25.
**Branch implementacyjny:** `codex/osk-course-program-pilot-b`.

## 1. Cel i granica Etapu 5A

Etap 5A dostarcza tylko wiarygodny, odtwarzalny zapis źródłowy czasu dla nowej **Nauki teorii OSK**. Nie liczy jeszcze formalnego czasu ukończenia i nie zmienia zachowania obecnej nauki pytań pod `/nauka`.

Dodane elementy:

- `TimePolicy` — wersjonowana polityka czasu;
- `LearningSession` — źródłowa sesja przypięta do konkretnego enrollmentu i zamrożonej wersji programu;
- `LearningSessionHeartbeat` — append-only sygnał aktywnej sesji;
- `LearningSessionService` — jedyny zapis domenowy: start/wznowienie, heartbeat i świadome zamknięcie.

Nie dodano:

- automatycznego uruchamiania zegara przy wejściu na stronę;
- zapisu z komponentu Vue, endpointu ani widoku dla kursanta;
- postępu lekcji/modułu, `ModuleAssessment`, wyniku, evidence, `TheoryCompletionGate`, PDF/PAPER;
- jakiejkolwiek zmiany `StudySession`, `StudySessionAnswer`, `QuestionCollection`, checkoutu B2C lub istniejącego `/nauka`.

## 2. Polityka czasu V1

Domyślna polityka jest świadomie mała i wersjonowana:

| Parametr | Wartość V1 |
| --- | --- |
| heartbeat | 60 sekund |
| grace po utracie połączenia | 60 sekund |
| maksymalna ciągła sesja | 10 800 sekund / 3 godziny |
| strategia nakładających się urządzeń | `UNION` |

`TimePolicy` nie pozwala na cichą zmianę wersji, interwałów, strategii ani snapshotu JSON. Zmiana parametrów wymaga nowej wersji. `valid_to` może tylko zamknąć obowiązywanie danej polityki; sesje zachowują własny snapshot i hash.

Przy rozpoczęciu nowej sesji system fail-closed wymaga **dokładnie jednej** aktywnej polityki. Brak albo nakładanie się polityk blokuje zapis zamiast wybierać dowolną.

## 3. Co jest zapisywane

### `learning_sessions`

Każdy rekord zamraża:

- `CourseEnrollment` i `CourseVersion`;
- numer wersji programu i hash programu;
- politykę czasu, jej wersję, snapshot oraz hash;
- klucz sesji przeglądarki;
- czasy rozpoczęcia i ostatniego heartbeat-u;
- jednoznaczne, jednorazowe zamknięcie wraz z powodem.

Po utworzeniu nie można zmienić danych źródłowych. Otwarta sesja może wyłącznie przesunąć heartbeat do przodu lub zostać raz zamknięta. Zamkniętej sesji nie można edytować ani usuwać.

### `learning_session_heartbeats`

Heartbeat jest osobnym, append-only rekordem z kolejnością, czasem serwera i kluczem idempotencji. Nie można go edytować ani usuwać. Serwis nie przyjmuje czasu z klienta jako źródła prawdy.

Powtórzony request z tym samym kluczem idempotencji zwraca istniejący rekord, a heartbeat wysłany szybciej niż polityka nie tworzy nowego wpisu.

## 4. Wznowienie, urządzenia i zamknięcie

- Ten sam `client_session_key` wznawia wyłącznie własną, jeszcze otwartą sesję.
- Inny klucz może utworzyć drugi zapis źródłowy dla tego samego enrollmentu; późniejszy walidator policzy nakładanie według `UNION`, więc czas nie będzie sumowany podwójnie.
- Po przekroczeniu limitu ciągłej sesji rekord zamyka się dokładnie na granicy limitu.
- Po braku heartbeat-u rekord zamyka się dokładnie na granicy `heartbeat + grace`.
- System nie otwiera kolejnej sesji sam. Kolejne wejście wymaga jawnego wywołania startu/wznowienia przez przyszły flow kursanta.

Nie stosujemy heurystyk kamery, myszy, klawiatury, focusu, captcha ani arbitralnego odrzucania „zbyt szybkiej” nauki. Zapis obejmuje rzeczywisty czas serwera między świadomym startem, heartbeatami i zamknięciem.

## 5. Dostęp i izolacja

Każdy zapis używa istniejącego `TheoryLearningAccessService`:

- tylko właściciel aktywnego `StudentCourseAccess` może rozpocząć, wznowić, wysłać heartbeat lub zamknąć sesję;
- cudzy kursant, wygasły dostęp oraz niekompletny snapshot programu kończą się fail-closed;
- klucz innej przeglądarki nie daje dostępu do istniejącej sesji;
- całość działa w transakcjach z blokadami rekordów, aby kolejność heartbeatów i sekwencje były stabilne.

## 6. Weryfikacja lokalna

- `LearningSessionFoundationTest`: **6 testów / 41 asercji**.
- Sprawdzone są: snapshot programu/polityki, wznowienie tylko w tej samej przeglądarce, throttling, idempotencja, append-only, izolacja użytkowników, wygasły dostęp, timeout, limit 3 h, dwa urządzenia z `UNION` oraz fail-closed brak lub konflikt aktywnych polityk.
- Migracja `2026_08_25_130000_create_osk_time_evidence_foundation` przeszła lokalnie po `migrate --pretend` i `migrate`.

## 7. Następny bezpieczny krok

Etapy 5B–5D zostały zakończone technicznie lokalnie w osobnych kontraktach. Integracja playera znajduje się w [Etapie 5D](./OSK_V2_12_STAGE_5D_LESSON_PLAYER_SESSION_UX.md). Przed modelami postępu, assessmentu i `TheoryCompletionGate` trzeba wykonać ręczny smoke test na aktywnym enrollmentcie oraz zatwierdzić pilota treści.
