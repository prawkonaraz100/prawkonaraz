# Ranking 1v1 Handoff

## Status

Data: `2026-04-23`

Branch roboczy:

- `codex/ranking-1v1-handoff`

Ten dokument jest krótkim handoffem dla kolejnego developera AI.
Pełny plan produktu i architektury jest w:

- [TRYB-RANKINGOWY-1V1-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/TRYB-RANKINGOWY-1V1-PLAN.md)

## Co zostało zrobione

Dodany został osobny punkt wejścia dla trybu rankingowego:

- route `GET /nauka/ranking`
- kontroler [RankedSessionPageController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/RankedSessionPageController.php)
- ekran [Ranking.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Ranking.vue)

Na ekranie `/nauka` placeholder `Tryb rankingowy` został podpięty do realnej nawigacji:

- [Index.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Index.vue)

Dodana została lokalna symulacja kontraktu realtime:

- [rankingModeSimulation.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankingModeSimulation.ts)
- scenariusze:
  - `happy_path`
  - `disconnect_reconnect`
  - `disconnect_abandoned`
  - `walkover`
  - `server_full`

Dodane zostały testy:

- [rankingModeSimulation.test.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankingModeSimulation.test.ts)
- rozszerzenie [SessionPageTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/SessionPageTest.php)
- reducer eventów:
  - [rankedRealtimeEventReducer.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeEventReducer.ts)
  - [rankedRealtimeEventReducer.test.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeEventReducer.test.ts)
- clock/timery realtime:
  - [rankedRealtimeClock.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeClock.ts)
  - [rankedRealtimeClock.test.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeClock.test.ts)
- polling transport przygotowany pod przyszły swap na WebSocket:
  - [rankedRealtimePollingTransport.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimePollingTransport.ts)
  - [rankedRealtimePollingTransport.test.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimePollingTransport.test.ts)
- klient live streamu, dispatcher eventów i negocjacja transportu:
  - [rankedRealtimeStreamProtocol.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeStreamProtocol.ts)
  - [rankedRealtimeEventSourceClient.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeEventSourceClient.ts)
  - [rankedRealtimeEventSourceClient.test.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeEventSourceClient.test.ts)
  - [rankedRealtimeWebSocketClient.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeWebSocketClient.ts)
  - [rankedRealtimeWebSocketClient.test.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeWebSocketClient.test.ts)
  - [rankedRealtimeTransportClient.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeTransportClient.ts)
  - [rankedRealtimeTransportClient.test.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeTransportClient.test.ts)

Dodana została dokumentacja wdrożeniowa:

- [TRYB-RANKINGOWY-1V1-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/TRYB-RANKINGOWY-1V1-PLAN.md)

Dodany został pierwszy backendowy slice modułu rankingowego:

- migracje:
  - [2026_04_21_190000_create_ranked_player_ratings_table.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/database/migrations/2026_04_21_190000_create_ranked_player_ratings_table.php)
  - [2026_04_21_190100_create_ranked_matches_table.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/database/migrations/2026_04_21_190100_create_ranked_matches_table.php)
  - [2026_04_21_190200_create_ranked_match_players_table.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/database/migrations/2026_04_21_190200_create_ranked_match_players_table.php)
  - [2026_04_21_190300_create_ranked_queue_entries_table.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/database/migrations/2026_04_21_190300_create_ranked_queue_entries_table.php)
  - [2026_04_21_190400_create_ranked_match_answers_table.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/database/migrations/2026_04_21_190400_create_ranked_match_answers_table.php)
  - [2026_04_21_210000_add_presence_columns_to_ranked_match_players_table.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/database/migrations/2026_04_21_210000_add_presence_columns_to_ranked_match_players_table.php)
  - [2026_04_21_220000_create_ranked_match_events_table.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/database/migrations/2026_04_21_220000_create_ranked_match_events_table.php)
- modele:
  - [RankedPlayerRating.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Models/RankedPlayerRating.php)
  - [RankedMatch.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Models/RankedMatch.php)
  - [RankedMatchPlayer.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Models/RankedMatchPlayer.php)
  - [RankedQueueEntry.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Models/RankedQueueEntry.php)
  - [RankedMatchAnswer.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Models/RankedMatchAnswer.php)
  - [RankedMatchEvent.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Models/RankedMatchEvent.php)
- serwis i payload builder:
  - [RankedMatchService.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedMatchService.php)
  - [RankedMatchPresenceStore.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedMatchPresenceStore.php)
  - [RankedMatchPayloadBuilder.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedMatchPayloadBuilder.php)
  - [RankedMatchEventPayloadBuilder.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedMatchEventPayloadBuilder.php)
  - [RankedRealtimeConnectionState.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedRealtimeConnectionState.php)
  - [RankedRealtimeEventStreamPublisher.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedRealtimeEventStreamPublisher.php)
  - [RankedWebSocketTicketService.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedWebSocketTicketService.php)
- konfiguracja:
  - [ranked.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/config/ranked.php)
- API:
  - [ApiRankedQueueController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/ApiRankedQueueController.php)
  - [ApiRankedMatchController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/ApiRankedMatchController.php)
  - [ApiRankedStreamController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/ApiRankedStreamController.php)
  - `GET /api/v1/ranked/overview`
  - `GET /api/v1/ranked/stream`
  - `GET /api/v1/ranked/history`
  - `POST /api/v1/ranked/queue/join`
  - `POST /api/v1/ranked/queue/leave`
  - `GET /api/v1/ranked/matches/{match}`
  - `GET /api/v1/ranked/matches/{match}/events`
  - `POST /api/v1/ranked/matches/{match}/answers`
  - `POST /api/v1/ranked/matches/{match}/ready`
  - `POST /api/v1/ranked/matches/{match}/abandon`
  - `POST /api/v1/ranked/matches/{match}/pong`
- realtime:
  - [ServeRankedWebSocketCommand.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Console/Commands/ServeRankedWebSocketCommand.php)
  - docker service `ranked-websocket`
- testy:
  - [ApiRankedQueueTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/ApiRankedQueueTest.php)
  - [ApiRankedMatchTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Feature/ApiRankedMatchTest.php)
  - [RankedWebSocketTicketServiceTest.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/tests/Unit/Support/RankedWebSocketTicketServiceTest.php)

## Najważniejsza decyzja architektoniczna

Tryb rankingowy 1v1 nie został wciśnięty do obecnego `StudySession`.

Założenie na dalszy development:

- `StudySession` zostaje modułem jednoosobowym,
- ranking 1v1 ma być osobnym modułem realtime,
- obecna symulacja i SSE shim mają zostać docelowo wygaszone na rzecz właściwego kanałowego WebSocketa bez przebudowy całego UI.

## Co działa teraz

Da się wejść na `/nauka/ranking` i uruchomić scenariusze UI.

Ekran pokazuje:

- stan kolejki,
- pairing i countdown,
- przebieg meczu,
- reconnect window,
- `server full`,
- finalny wynik albo `abandoned`,
- raw event log z payloadem `api_version = 1.0`.

Domyślny widok został uproszczony pod testera człowieka:

- pierwszy ekran pokazuje przede wszystkim ranking innych użytkowników i prosty panel startu,
- ranking działa dziś produktowo na jednej publicznej kategorii `B`, więc użytkownik nie wybiera już kategorii ręcznie,
- stare query stringi `?category=...` są ignorowane przez flow rankingu i nie są już propagowane między ekranami,
- główny flow skupia się na `start ranking -> matchmaking -> graj -> wynik`,
- status kolejki i mecz nie zasłaniają już startu, a historia ostatnich meczów nie jest już pokazywana w lobby użytkownika,
- diagnostyka developerska nie jest już eksponowana w UI użytkownika,
- nie ma już `Rematch` ani osobnej historii w lobby użytkownika,
- leaderboard jest renderowany server-side z `ranked_player_ratings`, więc użytkownik od razu widzi realny ranking innych graczy po wejściu na `/nauka/ranking`.

Flow użytkownika został dodatkowo rozdzielony na osobne ekrany:

- `/nauka/ranking`:
  - lobby z rankingiem graczy, profilem użytkownika i CTA `Start ranking`,
- `/nauka/ranking/oczekiwanie`:
  - pełny ekran waiting/matchmaking z jednym timerem, krótkim komunikatem i bez rozpraszających tabel,
- `/nauka/ranking/mecz`:
  - skupiony ekran meczu z pytaniami i sekwencyjnym flow odpowiedzi bez `Poprzednie` / `Następne`,
- `/nauka/ranking/wynik`:
  - uproszczony ekran końca meczu `gracz vs gracz` z poprawnymi odpowiedziami, czasem odpowiedzi i zmianą ELO.

Przejścia między ekranami są automatyczne na bazie `overview`:

- `lobby -> waiting` po wejściu do kolejki,
- `waiting -> match` po przejściu meczu z `matched` do właściwego stanu gry,
- `match -> result` po `finished` albo `abandoned`,
- `waiting/match -> lobby` po opuszczeniu kolejki albo po wyczyszczeniu aktywnego flow.

Start meczu jest teraz fair dla obu stron:

- `queue.matched` nie uruchamia jeszcze licznika rundy,
- frontend po załadowaniu ekranu wysyła `POST /api/v1/ranked/matches/{match}/ready`,
- backend czeka na `ready` od obu graczy,
- gdy obie strony są gotowe, backend emituje `match.countdown_started`,
- po wspólnym countdownie backend dopiero przełącza mecz na `in_progress` i emituje `match.started`,
- odpowiedzi są akceptowane dopiero w `in_progress`,
- odpowiedź wysłana za wcześnie kończy się `422 MATCH_NOT_STARTED`.

Da się też korzystać z pierwszego prawdziwego backendu rankingowego:

- odczytać `overview`,
- odczytać `recent_match` w `overview`,
- odczytać ostatni wynik i historię meczów w warstwie danych,
- wejść do kolejki po HTTP,
- wyjść z kolejki po HTTP,
- automatycznie sparować dwóch użytkowników w tej samej kategorii.
- pobrać szczegóły aktywnego albo zakończonego meczu,
- potwierdzić gotowość ekranu meczu po HTTP przez `ready`,
- poddać aktywny mecz po HTTP przez `abandon`, co kończy pojedynek natychmiastową przegraną użytkownika,
- zapisać odpowiedzi gracza,
- utrzymywać heartbeat po HTTP przez `pong`,
- zobaczyć status `online/offline` obu graczy w payloadzie,
- trzymać source of truth heartbeatów i `last_seen` w cache store, który w dockerowym runtime działa na Redisie,
- wykryć disconnect przeciwnika po timeoutcie heartbeatów,
- zamknąć mecz jako `abandoned` po wygaśnięciu reconnect window,
- czyścić klucze presence po `finished` i `abandoned`,
- pobrać backendowy event feed zgodny z kontraktem `api_version = 1.0`,
- zobaczyć w UI prawdziwe eventy `queue.matched`, `match.started`, `match.opponent_answered`, `match.opponent_disconnected`, `match.opponent_reconnected`, `match.finished`, `match.abandoned`,
- dociągać eventy inkrementalnie po `after_event_id`,
- zredukować backendowy event feed do lokalnego projection state na froncie,
- auto-odświeżać `overview`, kiedy gracz stoi jeszcze w kolejce i czeka na pairing,
- pokazywać lokalny zegar queue wait, match timer i reconnect countdown bazujący na backendowych timestampach,
- utrzymywać osobną warstwę transportu pollingu gotową do podmiany na prawdziwy adapter WebSocket,
- odbierać live `overview.sync` i `match.events` przez EventSource z fallbackiem do pollingu,
- odbierać live `heartbeat` przez EventSource i odpowiadać `pong` na bazie prawdziwego eventu streamu,
- odbierać live `queue.left`, `queue.queued`, `queue.resumed`, `queue.server_full` i `queue.matched` jako osobne eventy streamu,
- odbierać live `error` na streamie i w przyszłym WS na tym samym kontrakcie co HTTP `error_event`,
- emitować dziś `error` + `queue.server_full` z backendowego SSE dla capacity blockera,
- negocjować transport realtime z backendem i preferować WebSocket, jeśli backend wystawi gotowy URL,
- przełączać się automatycznie z nieudanego WebSocketu na obecny SSE fallback bez przepisywania UI ani reduktora,
- wystawiać już pierwszy prawdziwy backendowy bridge WebSocket:
  - plain PHP server uruchamiany komendą `php artisan ranked:websocket-serve`,
  - ticket auth dla zalogowanego użytkownika,
  - ticketowany `websocket.url` przekazywany z backendu do frontendu,
  - wspólny publisher eventów dla SSE i WS, więc oba transporty emitują ten sam kontrakt payloadów,
  - dockerowy serwis `ranked-websocket`, który można postawić obok `app/web/redis`,
- rozwiązywać pełny backendowy mecz z planszy 40 pytań zamiast pierwszych 6 kart devowych,
- mierzyć `response_time_ms` od momentu otwarcia pytania w UI, więc da się już sensownie testować tie-break po czasie odpowiedzi,
- wykonywać `POST` do backendowego API z poziomu UI bez 419 po poprawce wspólnego `apiClient`:
  - klient preferuje `X-XSRF-TOKEN` z cookie i nie wysyła już przeterminowanego `X-CSRF-TOKEN`, kiedy cookie jest dostępne,
- aktualizować własny progres, planszę pytań i active question od razu po odpowiedzi gracza, bez czekania na ręczny refresh strony,
- automatycznie zamknąć mecz jako `finished` po przekroczeniu `started_at + duration_seconds`, nawet jeśli obaj gracze nie odpowiedzieli jeszcze na komplet pytań,
- odrzucić spóźnione odpowiedzi po limicie czasu bez cofania już zapisanego `match.finished`,
- pilnować globalnego limitu concurrent users po backendzie,
- wejść w prawdziwy stan `server_full` z payloadem capacity i ETA,
- trzymać użytkownika poza limitem w `ranked_queue_entries`,
- automatycznie wznowić matchmaking po zwolnieniu slotu w czasie kolejnych pollingów `overview`,
- zakończyć mecz i policzyć ELO,
- emitować bogatszy payload `match.finished` / `match.abandoned`:
  - `status`,
  - `finished_at` / `abandoned_at`,
  - `winner` / `winner_by_default` / `loser`,
  - current-user-aware snapshot:
    - `current_user`,
    - `opponent`,
    - `outcome`,
    - `result_label`,
  - `scores`,
  - `elo_changes`,
- emitować bogatszy payload `match.started` zgodny z lokalną symulacją:
  - `status`,
  - `player1` / `player2`,
  - pełną listę `questions`,
  - backendowy `revealed_at` schedule dla każdego pytania,
- emitować `match.countdown_started` jako osobny event warmupu:
  - `countdown_started_at`,
  - `countdown_seconds`,
  - `starts_at`,
- emitować bogatszy payload `match.opponent_answered`:
  - `current_question`,
  - `opponent_score`,
  - `last_answer`,
  - `answered_at`,
- emitować bogatszy payload `match.opponent_disconnected` / `match.opponent_reconnected`:
  - `reconnect_deadline` obok `reconnect_deadline_at`,
  - `timeout_seconds`,
  - `opponent_current_state` po reconnectcie,
- zwracać rankedowy HTTP error envelope dla `/api/v1/ranked/*`:
  - top-level `error`,
  - `error_event` w shape zgodnym z kontraktem realtime,
  - zachowane `errors` dla walidacji `422`,
  - kody:
    - `MATCH_NOT_FOUND`,
    - `MATCH_FINISHED`,
    - `INVALID_ANSWER`,
- redukować na froncie `match.opponent_answered` również z nested `opponent_score`, nie tylko ze starych pól top-level,
- redukować na froncie reconnect również z `reconnect_deadline` i `opponent_current_state`,
- pokazywać w `Ranking.vue` backendowe błędy na podstawie `error_event`, więc UI pokazuje już np. `MATCH_FINISHED` albo `INVALID_ANSWER` zamiast samego statusu HTTP,
- redukować na froncie połączony stream `queue + match`, a nie tylko eventy meczu:
  - `queue.left` jest już osobnym live eventem kanału queue, więc inne zakładki mogą zobaczyć opuszczenie kolejki bez czekania wyłącznie na `overview.sync`,
  - `queue.queued` jest już osobnym live eventem kanału queue, więc zwykłe oczekiwanie na pairing nie musi opierać się wyłącznie na syntetycznym `overview.sync`,
  - `queue.resumed` jest już osobnym live eventem kanału queue, więc powrót z `server_full` do zwykłego `queueing` nie czeka wyłącznie na `overview.sync`,
  - `queue.server_full` ustawia projection state z `position_in_queue`, `current_capacity`, `max_concurrent_players` i `estimated_wait_minutes`,
  - `queue.matched` czyści stan `server_full` i uruchamia countdown w projection state,
  - nowe wejście w `queueing`, `server_full` albo `matched` czyści już także runtime poprzedniego meczu:
    - `matchId`,
    - `finishedAt`,
    - `startedAt`,
    - snapshot przeciwnika,
  - `overview.sync` z `state = queued` i jednoczesnym `recent_match` nie nadpisuje już projection state z powrotem na `finished`,
  - reducer zachowuje teraz również `lastErrorCode` / `lastErrorMessage` dla queue-side capacity blockera,
  - reducer hydratuje też terminalny snapshot przeciwnika bezpośrednio z `match.finished` / `match.abandoned`, więc końcowy stan nie zależy już wyłącznie od wcześniejszych eventów live albo odświeżenia `overview`,
  - reducer hydratuje też terminalny snapshot bieżącego gracza:
    - `currentUserAnsweredCount`,
    - `currentUserCorrectAnswers`,
    - `currentUserEloChange`,
    - `outcome`,
    - `resultLabel`,
    - `resultCategoryName`,
    - `resultTotalQuestions`,
- zasilać frontendowy event log i projection state również syntetycznym `overview.sync`:
  - streamowy snapshot trafia do raw logu backendowego jako deduplikowalny event z `api_version = 1.0`,
  - reducer widzi teraz stan `queueing` jeszcze zanim dojdzie `queue.matched`,
  - `overview.sync` potrafi teraz reprezentować także czysty powrót do `idle`, więc leave/reset kolejki nie wymaga już cichego czyszczenia event logu po stronie UI,
  - kolejne identyczne snapshoty `idle -> idle` są teraz deduplikowane po stronie klienta, więc raw log nie puchnie od pustych powtórek `overview.sync`,
  - reducer potrafi też zbootstrapować z samego `overview.sync`:
    - `idle`,
    - `matched`,
    - `in_progress`,
    - `opponent_disconnected`,
    - `finished`,
    - `abandoned`,
  - reducer hydratuje z `overview.sync` również snapshot przeciwnika:
    - `opponentUsername`,
    - `opponentAnsweredCount`,
    - `opponentCorrectAnswers`,
  - reducer hydratuje z `overview.sync active_match.players` również własny progres gracza:
    - `currentUserAnsweredCount`,
    - `currentUserCorrectAnswers`,
  - po reloadzie strony projection state nie czeka już ślepo na kolejny event meczu, żeby pokazać aktywny albo właśnie zakończony pojedynek,
  - po reloadzie UI może od razu pokazać nazwę przeciwnika i jego progres nawet zanim dolecą kolejne eventy meczu albo szczegóły `active_match`,
  - po leave queue albo po czystym `idle` raw log backendowy zostaje zachowany i dostaje nowy snapshot zamiast zniknąć przez lokalny reset tablicy eventów,
  - panel „Reducer eventów” pokazuje więc także zwykłe czekanie w kolejce, a nie tylko `server_full`, `matched` i zdarzenia meczu,
  - raw log backendowy pokazuje już prawdziwy `queue.queued` z live streamu obok syntetycznego `overview.sync`, więc łatwiej odróżnić bootstrap snapshot od kanałowego eventu queue,
  - raw log backendowy pokazuje już też `queue.resumed`, więc zwolnienie slotu i wznowienie kolejki są widoczne jako osobny krok kanału queue,
  - raw log backendowy pokazuje też `queue.left`, więc opuszczenie kolejki jest widoczne jako osobny krok kanału queue, a nie tylko jako kolejny snapshot overview,
  - kontrakt streamu kanału queue ma już regresje dla `queue.server_full`, `queue.queued` i `queue.matched`, a helpery `queue.left` i `queue.resumed` są przypięte testami jednostkowymi kontrolera,
  - panel „Reducer eventów” pokazuje teraz też `Ty`, więc live projection state jest czytelny również dla własnego progresu w aktywnym meczu,
  - panel „Reducer eventów” pokazuje teraz też wynikowy `Powód` dla `finished/abandoned` na bazie projection state,
  - panel „Reducer eventów” pokazuje teraz też `Twój wynik`, `Outcome` i `ELO` dla terminalnego stanu bez czekania na osobny panel historii,
  - sekcja „Ostatni wynik” ma też fallback do projection state, więc terminalny wynik może się pojawić od razu po eventach live, nawet zanim backend odświeży `recent_match` albo historię,
  - fallback „Ostatni wynik” nie zgaduje już kategorii ani liczby pytań z aktualnego filtra UI, tylko bierze je z projection state terminalnego meczu,
- rozumieć `error` także w warstwie transportu:
  - parser streamu ignoruje puste/uszkodzone payloady zamiast wywalać klienta,
  - EventSource i WebSocket dispatchują custom `error` do osobnego handlera eventowego,
  - `Ranking.vue` pokazuje live `SERVER_FULL: ...` także wtedy, gdy komunikat przychodzi z SSE/WS zamiast z odpowiedzi HTTP,

Dodatkowo został zrobiony ręczny smoke test 2 graczy na żywym projekcie:

- dwa zalogowane konta weszły na `/nauka/ranking`,
- oba dołączyły do tej samej kolejki z UI,
- matchmaking utworzył prawdziwy mecz i live stream przeszedł do `matched`,
- odpowiedź jednego gracza uruchomiła `match.started`,
- drugi gracz zobaczył `match.opponent_answered`,
- po poprawce frontend aktualizuje też lokalny progres odpowiadającego gracza od razu po kliknięciu odpowiedzi.

## Czego jeszcze nie ma

Nie ma jeszcze docelowego, utwardzonego backendu realtime. Brakuje:

- produkcyjnie utwardzonego lifecycle WebSocketa:
  - lepszego reconnect/backoff,
  - restart strategy,
  - observability i monitoringu połączeń,
- kanałowego source of truth presence pod WebSocket zamiast obecnego cache-backed HTTP heartbeat flow,
- właściwego kanałowego queue/match backbone na Redisie pod WebSocket zamiast obecnego bridge `WS + SSE + polling fallback`,
- pełnego przeniesienia live flow na WebSocket bez zależności od obecnego SSE shimu,
- pełnego pokrycia edge-case'ów reconnect/disconnect oraz błędów match-side po stronie live transportu.

Najuczciwsza ocena stanu:

- to jest już testowalne MVP/dev alpha dla 2 zalogowanych graczy,
- to nie jest jeszcze produkt production-ready.

## Co powinien zrobić następny developer AI

Najbardziej naturalna kolejność:

1. uruchomić migracje i sprawdzić nowy backendowy slice rankingu
2. sprawdzić feature testy PHP dla:
   - queue flow
   - answer flow
   - recent match/history
   - pong/presence/abandon
3. rozszerzyć API o:
   - kanałowe presence na Redisie pod docelowy transport WS
   - docelowe emity eventów realtime na podstawie istniejącego wspólnego publishera
   - właściwy kanał queue na WebSockecie dla `queue.left` / `queue.queued` / `queue.resumed` / `queue.server_full` / `queue.matched`, bez zależności od obecnego SSE fallbacku
   - pełne pokrycie `error` na live streamie / WS także dla błędów match-side, nie tylko obecnego `SERVER_FULL`
   - ostateczne wyrównanie nazw i payloadów pod jeden kanał WebSocket bez obecnych aliasów kompatybilności (`reconnect_deadline` / `reconnect_deadline_at`)
4. podmienić lokalną symulację na adapter realtime:
   - queue subscription zamiast obecnego EventSource/polling mixu
   - match subscription zamiast obecnego EventSource/polling mixu
   - deduplikacja po `event.id`
   - reducer eventów
   - podmiana obecnego `rankedRealtimePollingTransport` + `rankedRealtimeEventSourceClient` na docelowy kanałowy WS, nie tylko bridge kompatybilności
   - zachowanie obecnych lokalnych clocków po przejściu na prawdziwe WS
5. dopisać realtime engine meczu:
   - `queue.matched`
   - `match.started`
   - `match.opponent_answered`
   - `match.finished`
   - `match.abandoned`
6. dopisać backendowe testy smoke dla disconnect/pong/server full

## Miejsca wejścia dla następnej osoby

Najpierw przeczytać:

- [TRYB-RANKINGOWY-1V1-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/TRYB-RANKINGOWY-1V1-PLAN.md)
- [RANKING-1V1-HANDOFF.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/RANKING-1V1-HANDOFF.md)

Potem wejść w te pliki:

- [RankedSessionPageController.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Http/Controllers/RankedSessionPageController.php)
- [Ranking.vue](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/Pages/Session/Ranking.vue)
- [rankedRealtimeClock.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeClock.ts)
- [rankedRealtimePollingTransport.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimePollingTransport.ts)
- [rankedRealtimeTransportClient.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeTransportClient.ts)
- [rankedRealtimeWebSocketClient.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeWebSocketClient.ts)
- [rankedRealtimeEventSourceClient.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeEventSourceClient.ts)
- [rankedRealtimeStreamProtocol.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankedRealtimeStreamProtocol.ts)
- [RankedRealtimeEventStreamPublisher.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedRealtimeEventStreamPublisher.php)
- [RankedWebSocketTicketService.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/RankedWebSocketTicketService.php)
- [ServeRankedWebSocketCommand.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Console/Commands/ServeRankedWebSocketCommand.php)
- [rankingModeSimulation.ts](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/js/utils/rankingModeSimulation.ts)
- [routes/web.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/routes/web.php)

## Weryfikacja wykonana w tej zmianie

Udało się uruchomić:

- `npm run test:unit`
- `npm run test:unit -- resources/js/lib/apiClient.test.ts resources/js/utils/rankedRealtimeEventReducer.test.ts`
- `npm run test:unit -- resources/js/utils/rankedRealtimeEventSourceClient.test.ts resources/js/utils/rankedRealtimeWebSocketClient.test.ts resources/js/utils/rankedRealtimeTransportClient.test.ts resources/js/utils/rankedRealtimeEventReducer.test.ts resources/js/lib/apiClient.test.ts`
- `npm run test:unit -- resources/js/utils/rankedRealtimeEventReducer.test.ts`
- `cmd /c npm run test:unit -- resources/js/utils/rankedRealtimeEventReducer.test.ts resources/js/utils/rankedRealtimeEventSourceClient.test.ts resources/js/utils/rankedRealtimeWebSocketClient.test.ts resources/js/utils/rankedRealtimeTransportClient.test.ts resources/js/lib/apiClient.test.ts`
- `cmd /c npm run test:unit -- resources/js/utils/rankedRealtimeEventReducer.test.ts resources/js/utils/rankedRealtimeClock.test.ts resources/js/lib/apiClient.test.ts`
- `npm run build`
- `docker compose exec -T app php artisan test tests/Feature/ApiRankedQueueTest.php`
- `docker compose exec -T app php artisan test tests/Feature/ApiRankedMatchTest.php`
- `docker compose exec -T app php artisan test tests/Feature/ApiRankedQueueTest.php tests/Feature/ApiRankedMatchTest.php`
- `docker compose exec -T app php artisan test tests/Feature/ApiRankedMatchTest.php tests/Feature/SessionPageTest.php`
- `docker compose exec -T app php artisan test tests/Feature/SessionPageTest.php`
- `docker compose exec -T app php artisan test tests/Feature/ApiRankedMatchTest.php tests/Feature/ApiErrorHandlingTest.php tests/Feature/ApiRankedQueueTest.php tests/Feature/SessionPageTest.php`
- `docker compose exec -T app php artisan test tests/Feature/ApiRankedQueueTest.php tests/Feature/ApiRankedMatchTest.php tests/Feature/ApiErrorHandlingTest.php tests/Feature/SessionPageTest.php`
- `docker compose exec -T app php artisan test tests/Feature/ApiRankedMatchTest.php`
- `docker compose exec -T app php artisan migrate --force`
- `docker compose exec -T app php artisan test tests/Feature/SessionPageTest.php tests/Unit/Support/RankedWebSocketTicketServiceTest.php tests/Unit/Http/ApiRankedStreamControllerTest.php`

## Stan worktree

Zmiany są lokalnie na branchu:

- `codex/ranking-1v1-handoff`

Na branchu jest już wcześniejszy commit bazowy, a bieżący etap uproszczonego UX i pierwszego bridge WebSocket jest lokalnie bez nowego commita i bez pusha.
